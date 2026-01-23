<?php
namespace App\Controllers;

use PDO;
use Exception;

class ReportController extends ApiController {

    public function generate() {
        try {
            $type = $_GET['type'] ?? 'summary';
            $deviceId = $_GET['device_id'] ?? null;
            $start = $_GET['start'] ?? date('Y-m-d 00:00:00');
            $end = $_GET['end'] ?? date('Y-m-d 23:59:59');

            if (!$deviceId) $this->json(['error' => 'Selecione um veículo'], 400);

            switch ($type) {
                case 'summary':
                    $this->getSummaryReport($deviceId, $start, $end);
                    break;
                case 'events':
                    $this->getEventsReport($deviceId, $start, $end);
                    break;
                case 'route':
                    $this->getRouteListReport($deviceId, $start, $end);
                    break;
                default:
                    $this->json(['error' => 'Tipo de relatório inválido'], 400);
            }

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // Relatório 1: Resumo Gerencial (KM, Velocidade Max, Média)
    private function getSummaryReport($deviceId, $start, $end) {
        // Busca veículo
        $v = $this->getVehicle($deviceId);

        // Busca posições para calcular totais
        // PostgreSQL: Extrai 'distance' do JSONB attributes (distância em metros entre pontos)
        // Se seu Traccar usa 'totalDistance' acumulativo, a lógica seria MAX - MIN.
        // Vamos usar SUM(distance) que é mais seguro para versões variadas.
        $sql = "SELECT 
                    SUM(COALESCE(CAST(attributes::json->>'distance' AS FLOAT), 0)) as total_meters,
                    MAX(speed) as max_speed,
                    AVG(speed) as avg_speed,
                    COUNT(*) as total_points
                FROM tc_positions 
                WHERE deviceid = ? AND devicetime BETWEEN ? AND ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$v['traccar_device_id'], $start, $end]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        $totalKm = round(($stats['total_meters'] ?? 0) / 1000, 2);
        $maxSpeed = round(($stats['max_speed'] ?? 0) * 1.852, 0); // Nós para Km/h
        $avgSpeed = round(($stats['avg_speed'] ?? 0) * 1.852, 0);

        // Retorna estrutura de relatório
        $this->json([
            'success' => true,
            'title' => 'Resumo Gerencial',
            'period' => "$start até $end",
            'vehicle' => "{$v['plate']} - {$v['name']}",
            'columns' => ['Métrica', 'Valor'],
            'data' => [
                ['metric' => 'Distância Percorrida', 'value' => "$totalKm km"],
                ['metric' => 'Velocidade Máxima', 'value' => "$maxSpeed km/h"],
                ['metric' => 'Velocidade Média', 'value' => "$avgSpeed km/h"],
                ['metric' => 'Pontos Rastreados', 'value' => $stats['total_points']]
            ]
        ]);
    }

    // Relatório 2: Listagem de Eventos
    private function getEventsReport($deviceId, $start, $end) {
        $v = $this->getVehicle($deviceId);

        $sql = "SELECT e.eventtime, e.type, p.address 
                FROM tc_events e
                LEFT JOIN tc_positions p ON e.positionid = p.id
                WHERE e.deviceid = ? AND e.eventtime BETWEEN ? AND ?
                ORDER BY e.eventtime ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$v['traccar_device_id'], $start, $end]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [];
        foreach($rows as $r) {
            $data[] = [
                'time' => date('d/m/Y H:i:s', strtotime($r['eventtime'])),
                'type' => $this->translateEventType($r['type']),
                'address' => $r['address'] ?? 'Endereço não identificado'
            ];
        }

        $this->json([
            'success' => true,
            'title' => 'Relatório de Eventos',
            'period' => "$start até $end",
            'vehicle' => "{$v['plate']} - {$v['name']}",
            'columns' => ['Data/Hora', 'Evento', 'Localização'],
            'data' => $data
        ]);
    }

    // Relatório 3: Listagem de Rotas (Ponto a Ponto simplificado)
    private function getRouteListReport($deviceId, $start, $end) {
        $v = $this->getVehicle($deviceId);

        // Pega pontos a cada 5 minutos para não explodir o relatório
        // Lógica de "amostragem" simplificada
        $sql = "SELECT devicetime, speed, address 
                FROM tc_positions 
                WHERE deviceid = ? AND devicetime BETWEEN ? AND ?
                AND speed > 0 -- Apenas em movimento
                ORDER BY devicetime ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$v['traccar_device_id'], $start, $end]);
        $allRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [];
        // Filtra para exibir 1 ponto a cada ~10 registros para economizar papel/tela
        foreach($allRows as $k => $r) {
            if ($k % 10 == 0) { 
                $data[] = [
                    'time' => date('d/m/Y H:i', strtotime($r['devicetime'])),
                    'speed' => round($r['speed'] * 1.852) . ' km/h',
                    'address' => $r['address']
                ];
            }
        }

        $this->json([
            'success' => true,
            'title' => 'Detalhamento de Rota (Amostragem)',
            'period' => "$start até $end",
            'vehicle' => "{$v['plate']} - {$v['name']}",
            'columns' => ['Hora', 'Velocidade', 'Local'],
            'data' => $data
        ]);
    }

    // Helpers
    private function getVehicle($id) {
        $stmt = $this->pdo->prepare("SELECT traccar_device_id, plate, name FROM saas_vehicles WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$id, $this->tenant_id]);
        $v = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$v) {
            $this->json(['error' => 'Veículo não encontrado'], 404);
        }
        return $v;
    }

    private function translateEventType($type) {
        $map = [
            'deviceOverspeed' => 'Excesso Velocidade',
            'geofenceEnter' => 'Entrada Cerca',
            'geofenceExit' => 'Saída Cerca',
            'ignitionOn' => 'Ignição ON',
            'ignitionOff' => 'Ignição OFF'
        ];
        return $map[$type] ?? $type;
    }
}