<?php
namespace App\Controllers;

use PDO;
use Exception;

class AlertsController extends ApiController {

    public function index() {
        try {
            // Filtros da Requisição
            $deviceId = $_GET['device_id'] ?? null;
            $type = $_GET['type'] ?? null;
            $startDate = $_GET['start'] ?? date('Y-m-d 00:00:00');
            $endDate = $_GET['end'] ?? date('Y-m-d 23:59:59');
            $limit = 500; // Limite de segurança para não travar o navegador

            // Restrição de Tenant e Permissão
            $restr = $this->getRestrictionSQL('v');

            // Construção da Query
            $sql = "SELECT e.id, e.type, e.eventtime, e.attributes, 
                           v.name as vehicle_name, v.plate, 
                           p.latitude, p.longitude, p.address, p.speed
                    FROM tc_events e
                    JOIN saas_vehicles v ON e.deviceid = v.traccar_device_id
                    LEFT JOIN tc_positions p ON e.positionid = p.id
                    WHERE v.tenant_id = ? $restr
                    AND e.eventtime BETWEEN ? AND ?";
            
            $params = [$this->tenant_id, $startDate, $endDate];

            if ($deviceId) {
                $sql .= " AND v.id = ?";
                $params[] = $deviceId;
            }

            if ($type) {
                $sql .= " AND e.type = ?";
                $params[] = $type;
            }

            $sql .= " ORDER BY e.eventtime DESC LIMIT $limit";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Tradução e Formatação (Backend processing)
            $translated = [];
            foreach ($rows as $row) {
                $row['type_label'] = $this->translateType($row['type'], $row['attributes']);
                $row['formatted_time'] = date('d/m/Y H:i:s', strtotime($row['eventtime']));
                // Decodifica atributos se necessário para front
                $row['attrs'] = json_decode($row['attributes'] ?? '{}', true);
                $translated[] = $row;
            }

            $this->json(['success' => true, 'data' => $translated]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    private function translateType($type, $attributesJson) {
        $map = [
            'deviceOverspeed'   => 'Excesso de Velocidade',
            'geofenceEnter'     => 'Entrou na Cerca',
            'geofenceExit'      => 'Saiu da Cerca',
            'ignitionOn'        => 'Ignição Ligada',
            'ignitionOff'       => 'Ignição Desligada',
            'deviceOffline'     => 'Dispositivo Offline',
            'deviceOnline'      => 'Dispositivo Online',
            'deviceStopped'     => 'Veículo Parado',
            'deviceMoving'      => 'Em Movimento',
            'maintenance'       => 'Manutenção Requerida',
            'alarm'             => 'Alarme SOS/Pânico'
        ];

        // Tenta pegar tipo específico de alarme se houver
        if ($type === 'alarm') {
            $attrs = json_decode($attributesJson, true);
            if (isset($attrs['alarm'])) {
                return 'Alarme: ' . ucfirst($attrs['alarm']);
            }
        }

        return $map[$type] ?? $type;
    }
}