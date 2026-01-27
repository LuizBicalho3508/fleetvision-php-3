<?php
namespace App\Controllers;

use App\Services\TraccarService;
use PDO;
use Exception;

class DashboardController extends ApiController {

    public function index() {
        $viewName = 'dashboard';
        if (file_exists(__DIR__ . '/../../views/layout.php')) {
            require __DIR__ . '/../../views/layout.php';
        } else {
            require __DIR__ . '/../../views/dashboard.php';
        }
    }

    // KPI: Cards do Topo (Totais, Ativos, Motoristas)
    public function getKpis() {
        try {
            if (!isset($_SESSION['tenant_id'])) {
                $this->json(['error' => 'Sessão expirada'], 401);
                return;
            }
            $tenantId = $_SESSION['tenant_id'];
            $userCustomerId = $_SESSION['user_customer_id'] ?? null; // Isolamento

            // 1. Veículos (Total e Ativos)
            $sqlTotal = "SELECT COUNT(*) FROM saas_vehicles WHERE tenant_id = ? AND status = 'active'";
            $paramsTotal = [$tenantId];

            if ($userCustomerId) {
                $sqlTotal .= " AND customer_id = ?";
                $paramsTotal[] = $userCustomerId;
            }

            $stmt = $this->pdo->prepare($sqlTotal);
            $stmt->execute($paramsTotal);
            $totalVehicles = $stmt->fetchColumn();

            // 2. Motoristas
            $sqlDrivers = "SELECT COUNT(*) FROM saas_drivers WHERE tenant_id = ? AND status = 'active'";
            $paramsDrivers = [$tenantId];

            if ($userCustomerId) {
                $sqlDrivers .= " AND customer_id = ?";
                $paramsDrivers[] = $userCustomerId;
            }

            $stmtDr = $this->pdo->prepare($sqlDrivers);
            $stmtDr->execute($paramsDrivers);
            $totalDrivers = $stmtDr->fetchColumn();

            // 3. Status Real-Time (Via Traccar)
            // Precisamos buscar os devices para contar online/offline/ignição
            // Se o usuário for isolado, precisamos filtrar esses devices
            $online = 0;
            $offline = 0;
            $ignitionOn = 0;

            try {
                // Busca lista de IDs de veículos permitidos para este usuário
                $sqlIds = "SELECT identifier, imei FROM saas_vehicles WHERE tenant_id = ? AND status = 'active'";
                $paramsIds = [$tenantId];
                if ($userCustomerId) {
                    $sqlIds .= " AND customer_id = ?";
                    $paramsIds[] = $userCustomerId;
                }
                $stmtIds = $this->pdo->prepare($sqlIds);
                $stmtIds->execute($paramsIds);
                $myVehicles = $stmtIds->fetchAll(PDO::FETCH_ASSOC);

                // Cria mapa de identificadores permitidos
                $allowedUniqueIds = [];
                foreach ($myVehicles as $v) {
                    $key = preg_replace('/[^0-9]/', '', $v['identifier'] ?? $v['imei']);
                    if ($key) $allowedUniqueIds[$key] = true;
                }

                if (count($allowedUniqueIds) > 0) {
                    $traccar = new TraccarService();
                    $positions = $traccar->getPositions(); // Pega última posição de todos
                    $devices   = $traccar->getDevices();   // Pega status (online/offline)

                    // Mapa de Status
                    $deviceStatusMap = [];
                    foreach ($devices as $d) {
                        $deviceStatusMap[$d['id']] = $d['status']; // 'online', 'offline', 'unknown'
                        $deviceUniqueIdMap[$d['id']] = $d['uniqueId'];
                    }

                    foreach ($positions as $pos) {
                        $deviceId = $pos['deviceId'];
                        $uniqueId = $deviceUniqueIdMap[$deviceId] ?? null;

                        // SÓ CONTA SE O VEÍCULO PERTENCER AO USUÁRIO/CLIENTE
                        if ($uniqueId && isset($allowedUniqueIds[$uniqueId])) {
                            
                            // Status Online/Offline
                            $status = $deviceStatusMap[$deviceId] ?? 'offline';
                            if ($status === 'online') $online++; else $offline++;

                            // Ignição
                            $attrs = $pos['attributes'] ?? [];
                            $ign = false;
                            if (isset($attrs['ignition'])) $ign = (bool) $attrs['ignition'];
                            elseif (isset($attrs['motion'])) $ign = (bool) $attrs['motion'];
                            
                            if ($ign) $ignitionOn++;
                        }
                    }
                }

            } catch (Exception $eTraccar) {
                // Se o Traccar falhar, não quebra o dashboard, mostra zeros
                error_log("Erro Traccar Dashboard: " . $eTraccar->getMessage());
            }

            $this->json([
                'total_vehicles' => $totalVehicles,
                'total_drivers' => $totalDrivers,
                'online' => $online,
                'offline' => $offline,
                'ignition_on' => $ignitionOn,
                'ignition_off' => $totalVehicles - $ignitionOn
            ]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // Gráficos e Tabelas Rápidas
    public function getData() {
        try {
            if (!isset($_SESSION['tenant_id'])) return;
            $tenantId = $_SESSION['tenant_id'];
            $userCustomerId = $_SESSION['user_customer_id'] ?? null;

            // 1. Veículos por Status (Gráfico Donut)
            // Vamos simular dados baseados no banco se não tiver histórico de alertas ainda
            
            // Exemplo: Alertas Recentes (Últimos 5)
            // Se você tiver uma tabela saas_alerts, descomente e ajuste
            /*
            $sqlAlerts = "SELECT a.*, v.plate FROM saas_alerts a 
                          JOIN saas_vehicles v ON a.vehicle_id = v.id 
                          WHERE a.tenant_id = ? ORDER BY a.created_at DESC LIMIT 5";
            // Adicionar filtro customer_id aqui também...
            */

            // Retorno Mock (Placeholder) até você criar as tabelas de Alertas/Histórico
            // Isso evita o erro 500 enquanto o sistema cresce
            $this->json([
                'alerts_recent' => [], 
                'km_driven_week' => [120, 150, 180, 200, 90, 110, 130] // Dados Dummy para gráfico
            ]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}