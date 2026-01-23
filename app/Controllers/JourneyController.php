<?php
namespace App\Controllers;

use PDO;
use Exception;

class JourneyController extends ApiController {

    private $MAX_CONTINUOUS = 19800; // 5.5h em segundos
    private $MAX_DAILY = 36000;      // 10h em segundos
    private $MIN_REST = 1800;        // 30 min em segundos

    public function index() {
        try {
            // Busca Jornadas do Dia
            $sql = "
                SELECT 
                    j.driver_id, d.name as driver_name, d.rfid_tag,
                    j.vehicle_id, v.name as vehicle_name, v.plate,
                    j.start_time, j.end_time,
                    EXTRACT(EPOCH FROM (COALESCE(j.end_time, NOW()) - j.start_time)) as duration,
                    CASE WHEN j.end_time IS NULL THEN 1 ELSE 0 END as is_open
                FROM saas_driver_journeys j
                JOIN saas_drivers d ON j.driver_id = d.id
                JOIN saas_vehicles v ON j.vehicle_id = v.id
                WHERE j.tenant_id = ? 
                AND (DATE(j.start_time) = CURRENT_DATE OR j.end_time IS NULL)
                ORDER BY j.driver_id, j.start_time ASC
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$this->tenant_id]);
            $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Processa Dados
            $drivers = [];
            foreach ($raw as $r) {
                $did = $r['driver_id'];
                if (!isset($drivers[$did])) {
                    $drivers[$did] = [
                        'name' => $r['driver_name'],
                        'current_vehicle' => '---',
                        'status' => 'descanso',
                        'total_driving' => 0,
                        'continuous_driving' => 0,
                        'violations' => [],
                        'last_end' => 0
                    ];
                }

                $duration = (float)$r['duration'];
                $start = strtotime($r['start_time']);
                $end = $r['end_time'] ? strtotime($r['end_time']) : time();

                // Verifica Descanso (Zera contínuo se parou > 30min)
                if ($drivers[$did]['last_end'] > 0) {
                    if (($start - $drivers[$did]['last_end']) >= $this->MIN_REST) {
                        $drivers[$did]['continuous_driving'] = 0;
                    }
                }

                $drivers[$did]['total_driving'] += $duration;
                $drivers[$did]['continuous_driving'] += $duration;
                $drivers[$did]['last_end'] = $end;

                if ($r['is_open']) {
                    $drivers[$did]['status'] = 'dirigindo';
                    $drivers[$did]['current_vehicle'] = $r['plate'];
                }
            }

            // Valida Infrações
            foreach ($drivers as &$d) {
                if ($d['total_driving'] > $this->MAX_DAILY) $d['violations'][] = 'Máximo Diário Excedido';
                if ($d['continuous_driving'] > $this->MAX_CONTINUOUS) $d['violations'][] = 'Máximo Contínuo Excedido';
                
                // Formata Tempos
                $d['fmt_total'] = gmdate("H:i", $d['total_driving']);
                $d['fmt_cont'] = gmdate("H:i", $d['continuous_driving']);
                
                $d['health'] = empty($d['violations']) ? 'ok' : 'critical';
            }

            $this->json(['success' => true, 'data' => array_values($drivers)]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}