<?php
namespace App\Controllers;

use PDO;
use Exception;

class DashboardController extends ApiController {

    // Retorna os indicadores (Cards do topo)
    public function getKpis() {
        try {
            // 1. Total de Veículos
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM saas_vehicles WHERE tenant_id = ? AND status = 'active'");
            $stmt->execute([$this->tenant_id]);
            $totalVehicles = $stmt->fetchColumn();

            // 2. Veículos Online (Exemplo simples: considera online se tem 'motion' true na última posição)
            // Para maior precisão, precisaríamos cruzar com a tabela tc_positions do Traccar
            // Aqui faremos uma contagem baseada nos veículos cadastrados por enquanto
            
            // Simulação de status online/offline baseada em conexão recente
            // (Na prática, você faria um JOIN com tc_devices e tc_positions)
            $online = 0;
            $offline = 0;
            
            // Busca devices do Traccar vinculados
            $stmtDev = $this->pdo->prepare("SELECT traccar_device_id FROM saas_vehicles WHERE tenant_id = ?");
            $stmtDev->execute([$this->tenant_id]);
            $deviceIds = $stmtDev->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($deviceIds)) {
                $idsStr = implode(',', $deviceIds);
                // Consulta direta nas tabelas do Traccar (tc_devices)
                $sqlTraccar = "SELECT status FROM tc_devices WHERE id IN ($idsStr)";
                $stmtTc = $this->pdo->query($sqlTraccar);
                $statuses = $stmtTc->fetchAll(PDO::FETCH_COLUMN);
                
                foreach ($statuses as $st) {
                    if ($st === 'online') $online++;
                    else $offline++;
                }
            }

            // 3. Motoristas
            $stmtDrv = $this->pdo->prepare("SELECT COUNT(*) FROM saas_drivers WHERE tenant_id = ?");
            $stmtDrv->execute([$this->tenant_id]);
            $totalDrivers = $stmtDrv->fetchColumn();

            $this->json([
                'total_vehicles' => $totalVehicles,
                'online_vehicles' => $online,
                'offline_vehicles' => $offline, // Fallback se $online for 0
                'total_drivers' => $totalDrivers
            ]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // Retorna os dados da tabela principal
    public function getData() {
        try {
            $sql = "SELECT v.id, v.name, v.plate, v.traccar_device_id, 
                           d.name as driver_name,
                           b.name as branch_name
                    FROM saas_vehicles v
                    LEFT JOIN saas_drivers d ON v.driver_id = d.id
                    LEFT JOIN saas_branches b ON v.branch_id = b.id
                    WHERE v.tenant_id = ? 
                    ORDER BY v.name ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$this->tenant_id]);
            $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Enriquece com dados do Traccar (última posição) se possível
            // Aqui retornamos o básico para a tabela carregar rápido
            // O frontend pode buscar o status realtime via socket ou polling no /api/mapa

            $this->json(['data' => $vehicles]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // Retorna alertas recentes (Notificações)
    public function getAlerts() {
        try {
            // Se você tiver uma tabela de logs de eventos, use aqui.
            // Exemplo usando tc_events do Traccar cruzado com os devices do tenant
            
            $stmtDev = $this->pdo->prepare("SELECT traccar_device_id FROM saas_vehicles WHERE tenant_id = ?");
            $stmtDev->execute([$this->tenant_id]);
            $deviceIds = $stmtDev->fetchAll(PDO::FETCH_COLUMN);

            if (empty($deviceIds)) {
                $this->json(['data' => []]);
                return;
            }

            $idsStr = implode(',', $deviceIds);
            
            // Busca últimos 5 eventos
            $sqlEvents = "SELECT e.id, e.type, e.servertime, d.name as device_name 
                          FROM tc_events e
                          JOIN tc_devices d ON e.deviceid = d.id
                          WHERE e.deviceid IN ($idsStr)
                          ORDER BY e.servertime DESC LIMIT 5";
            
            $stmtEv = $this->pdo->query($sqlEvents);
            $events = $stmtEv->fetchAll(PDO::FETCH_ASSOC);

            // Tradução simples
            foreach ($events as &$ev) {
                $ev['type_label'] = $this->translateType($ev['type']);
                $ev['time_ago'] = $this->timeAgo($ev['servertime']);
            }

            $this->json(['data' => $events]);

        } catch (Exception $e) {
            // Retorna vazio em caso de erro para não quebrar o dashboard
            $this->json(['data' => []]);
        }
    }

    private function translateType($type) {
        $map = [
            'deviceOnline' => 'Ficou Online',
            'deviceOffline' => 'Ficou Offline',
            'deviceMoving' => 'Em Movimento',
            'deviceStopped' => 'Parou',
            'deviceOverspeed' => 'Excesso de Velocidade',
            'geofenceEnter' => 'Entrou na Cerca',
            'geofenceExit' => 'Saiu da Cerca',
            'ignitionOn' => 'Ignição Ligada',
            'ignitionOff' => 'Ignição Desligada'
        ];
        return $map[$type] ?? $type;
    }

    private function timeAgo($datetime) {
        $time = strtotime($datetime);
        $diff = time() - $time;
        
        if ($diff < 60) return 'Agora';
        if ($diff < 3600) return floor($diff / 60) . ' min atrás';
        if ($diff < 86400) return floor($diff / 3600) . 'h atrás';
        return date('d/m H:i', $time);
    }
}