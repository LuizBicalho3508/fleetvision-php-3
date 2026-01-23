<?php
namespace App\Controllers;

use PDO;
use Exception;

class RankingController extends ApiController {

    private $defaultRules = [
        'speed_limit' => 110,
        'speed_penalty' => 5,
        'idle_interval' => 30, // minutos
        'idle_penalty' => 2,
        'journey_continuous_penalty' => 10,
        'journey_daily_penalty' => 20
    ];

    // Obtém dados do Ranking
    public function index() {
        try {
            $start = $_GET['from'] ?? date('Y-m-01 00:00:00');
            $end = $_GET['to'] ?? date('Y-m-d 23:59:59');
            
            // 1. Carrega Regras
            $rules = $this->getRulesInternal();

            // 2. Busca Motoristas
            $sqlDrivers = "SELECT id, name, rfid FROM saas_drivers WHERE tenant_id = ?";
            $drivers = $this->pdo->prepare($sqlDrivers);
            $drivers->execute([$this->tenant_id]);
            $driverList = [];
            $rfidMap = [];

            foreach ($drivers->fetchAll(PDO::FETCH_ASSOC) as $d) {
                $driverList[$d['id']] = [
                    'id' => $d['id'],
                    'name' => $d['name'],
                    'score' => 100, // Começa com 100
                    'stats' => ['speed_count' => 0, 'idle_count' => 0, 'journey_violations' => 0],
                    'relevant' => false
                ];
                if ($d['rfid']) $rfidMap[$d['rfid']] = $d['id'];
            }

            // 3. Analisa Veículos e Telemetria
            $sqlVehicles = "SELECT id, name, plate, traccar_device_id, idle_threshold FROM saas_vehicles WHERE tenant_id = ?";
            $stmtV = $this->pdo->prepare($sqlVehicles);
            $stmtV->execute([$this->tenant_id]);
            
            $vehicleList = [];

            foreach ($stmtV->fetchAll(PDO::FETCH_ASSOC) as $v) {
                // Busca posições no intervalo
                $stmtPos = $this->pdo->prepare("SELECT servertime, speed, attributes FROM tc_positions WHERE deviceid = ? AND servertime BETWEEN ? AND ? ORDER BY servertime ASC");
                $stmtPos->execute([$v['traccar_device_id'], $start, $end]);
                $positions = $stmtPos->fetchAll(PDO::FETCH_ASSOC);

                $vStats = ['speed_count' => 0, 'idle_count' => 0, 'max_speed' => 0];
                $idleStart = null;
                $isSpeeding = false;
                $idleThreshold = ($v['idle_threshold'] ?: 5) * 60; // Padrão 5 min

                foreach ($positions as $pos) {
                    $speed = $pos['speed'] * 1.852; // Convert Knots -> Km/h
                    $attrs = json_decode($pos['attributes'], true);
                    $ign = $attrs['ignition'] ?? false;
                    $rfid = $attrs['driverUniqueId'] ?? null;
                    $time = strtotime($pos['servertime']);

                    if ($speed > $vStats['max_speed']) $vStats['max_speed'] = $speed;

                    // Identifica Motorista
                    $drvId = null;
                    if ($rfid && isset($rfidMap[$rfid])) {
                        $drvId = $rfidMap[$rfid];
                        $driverList[$drvId]['relevant'] = true;
                    }

                    // Excesso de Velocidade
                    if ($speed > $rules['speed_limit']) {
                        if (!$isSpeeding) {
                            $vStats['speed_count']++;
                            $isSpeeding = true;
                            if ($drvId) $driverList[$drvId]['stats']['speed_count']++;
                        }
                    } else {
                        $isSpeeding = false;
                    }

                    // Ociosidade (Motor ligado e parado)
                    if ($ign && $speed < 2) {
                        if ($idleStart === null) $idleStart = $time;
                    } else {
                        if ($idleStart !== null) {
                            if (($time - $idleStart) >= $idleThreshold) {
                                $vStats['idle_count']++;
                                if ($drvId) $driverList[$drvId]['stats']['idle_count']++;
                            }
                            $idleStart = null;
                        }
                    }
                }

                // Score Veículo
                $penalty = ($vStats['speed_count'] * $rules['speed_penalty']) + ($vStats['idle_count'] * $rules['idle_penalty']);
                $vehicleList[] = [
                    'name' => $v['name'],
                    'plate' => $v['plate'],
                    'score' => max(0, 100 - $penalty),
                    'stats' => $vStats
                ];
            }

            // 4. Finaliza Score Motoristas
            $finalDrivers = [];
            foreach ($driverList as $d) {
                if (!$d['relevant']) continue; // Pula motoristas sem atividade

                $penalty = ($d['stats']['speed_count'] * $rules['speed_penalty']) + 
                           ($d['stats']['idle_count'] * $rules['idle_penalty']);
                
                $d['score'] = max(0, 100 - $penalty);
                
                // Classificação (S, A, B, C)
                if ($d['score'] >= 90) $d['grade'] = 'A';
                elseif ($d['score'] >= 70) $d['grade'] = 'B';
                else $d['grade'] = 'C';

                $finalDrivers[] = $d;
            }

            // Ordenação
            usort($finalDrivers, fn($a, $b) => $b['score'] <=> $a['score']);
            usort($vehicleList, fn($a, $b) => $b['score'] <=> $a['score']);

            $this->json([
                'success' => true,
                'drivers' => $finalDrivers,
                'vehicles' => $vehicleList,
                'rules' => $rules
            ]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // Salva Regras
    public function saveRules() {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $this->json(['error' => 'Dados inválidos'], 400);

        try {
            // PostgreSQL Upsert (ON CONFLICT)
            $sql = "INSERT INTO saas_ranking_config (tenant_id, rules) VALUES (?, ?) 
                    ON CONFLICT (tenant_id) DO UPDATE SET rules = EXCLUDED.rules, updated_at = NOW()";
            
            $this->pdo->prepare($sql)->execute([$this->tenant_id, json_encode($input)]);
            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    private function getRulesInternal() {
        $stmt = $this->pdo->prepare("SELECT rules FROM saas_ranking_config WHERE tenant_id = ?");
        $stmt->execute([$this->tenant_id]);
        $json = $stmt->fetchColumn();
        return $json ? array_merge($this->defaultRules, json_decode($json, true)) : $this->defaultRules;
    }
}