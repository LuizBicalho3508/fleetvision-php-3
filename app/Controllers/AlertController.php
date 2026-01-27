<?php
namespace App\Controllers;

use App\Services\TraccarService;
use PDO;
use Exception;
use DateTime;
use DateTimeZone;

class AlertController extends ApiController {

    public function index() {
        // Carrega Layout
        $viewName = 'alertas';
        if (file_exists(__DIR__ . '/../../views/layout.php')) {
            require __DIR__ . '/../../views/layout.php';
        } else {
            require __DIR__ . '/../../views/alertas.php';
        }
    }

    // ... (Mantenha getSettings e saveSettings iguais ao anterior) ...
    public function getSettings() {
        $tenantId = $_SESSION['tenant_id'];
        $stmt = $this->pdo->prepare("SELECT alert_config FROM saas_tenants WHERE id = ?");
        $stmt->execute([$tenantId]);
        $config = $stmt->fetchColumn();
        if(!$config) $config = '{"ignition":true, "overspeed":true, "geofence":true, "maintenance":true, "power":true}';
        $this->json(['data' => json_decode($config, true)]);
    }

    public function saveSettings() {
        $data = file_get_contents('php://input');
        $tenantId = $_SESSION['tenant_id'];
        $stmt = $this->pdo->prepare("UPDATE saas_tenants SET alert_config = ? WHERE id = ?");
        $stmt->execute([$data, $tenantId]);
        $this->json(['success' => true]);
    }

    // --- POLLING (Para Popup e Sino) ---
    public function checkNew() {
        try {
            $tenantId = $_SESSION['tenant_id'];
            
            // 1. Configurações
            $stmt = $this->pdo->prepare("SELECT alert_config FROM saas_tenants WHERE id = ?");
            $stmt->execute([$tenantId]);
            $config = json_decode($stmt->fetchColumn() ?? '{}', true);

            // 2. Veículos do Tenant
            $stmtDev = $this->pdo->prepare("
                SELECT v.plate, s.traccar_device_id, c.name as customer_name
                FROM saas_vehicles v
                JOIN saas_stock s ON v.stock_id = s.id
                LEFT JOIN saas_customers c ON v.customer_id = c.id
                WHERE v.tenant_id = ? AND v.status = 'active' AND s.traccar_device_id IS NOT NULL
            ");
            $stmtDev->execute([$tenantId]);
            $vehicles = $stmtDev->fetchAll(PDO::FETCH_ASSOC);
            
            $vehicleMap = [];
            $deviceIds = [];
            foreach ($vehicles as $v) {
                $vehicleMap[$v['traccar_device_id']] = $v;
                $deviceIds[] = $v['traccar_device_id'];
            }

            if (empty($deviceIds)) { $this->json(['alerts' => []]); return; }

            // 3. Busca Eventos (Últimos 20 segundos)
            $now = new DateTime('now', new DateTimeZone('UTC'));
            $to = $now->format('Y-m-d\TH:i:s\Z');
            $from = $now->modify('-20 seconds')->format('Y-m-d\TH:i:s\Z');

            $traccar = new TraccarService();
            $events = $traccar->getEvents($deviceIds, $from, $to);

            $alerts = $this->processEvents($events, $vehicleMap, $config);

            $this->json(['alerts' => $alerts]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // --- HISTÓRICO COMPLETO ---
    public function getHistory() {
        try {
            $tenantId = $_SESSION['tenant_id'];
            $date = $_GET['date'] ?? date('Y-m-d');

            // Config e Veículos (Mesma lógica acima)
            // ... (Para otimizar, poderia ser um método privado helper, mas repetirei por clareza)
            $stmtDev = $this->pdo->prepare("
                SELECT v.plate, s.traccar_device_id, c.name as customer_name
                FROM saas_vehicles v
                JOIN saas_stock s ON v.stock_id = s.id
                LEFT JOIN saas_customers c ON v.customer_id = c.id
                WHERE v.tenant_id = ? AND s.traccar_device_id IS NOT NULL
            ");
            $stmtDev->execute([$tenantId]);
            $vehicles = $stmtDev->fetchAll(PDO::FETCH_ASSOC);
            
            $vehicleMap = [];
            $deviceIds = [];
            foreach ($vehicles as $v) {
                $vehicleMap[$v['traccar_device_id']] = $v;
                $deviceIds[] = $v['traccar_device_id'];
            }

            if (empty($deviceIds)) { $this->json(['data' => []]); return; }

            // Range do Dia Inteiro
            $from = date('Y-m-d\TH:i:s\Z', strtotime($date . ' 00:00:00'));
            $to   = date('Y-m-d\TH:i:s\Z', strtotime($date . ' 23:59:59'));

            $traccar = new TraccarService();
            $events = $traccar->getEvents($deviceIds, $from, $to);

            // Processa todos (ignorando config de filtro, pois histórico mostra tudo)
            $history = $this->processEvents($events, $vehicleMap, null); // null config = mostra tudo

            $this->json(['data' => array_reverse($history)]); // Mais recente primeiro

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // Helper para formatar eventos
    // Helper para formatar eventos (CORRIGIDO)
    private function processEvents($events, $vehicleMap, $filterConfig = null) {
        $typeMap = [
            'deviceOverspeed' => ['label' => 'Excesso de Velocidade', 'key' => 'overspeed', 'icon' => 'fa-tachometer-alt', 'color' => 'red'],
            'ignitionOn'      => ['label' => 'Ignição Ligada',      'key' => 'ignition',  'icon' => 'fa-key',            'color' => 'green'],
            'ignitionOff'     => ['label' => 'Ignição Desligada',   'key' => 'ignition',  'icon' => 'fa-power-off',      'color' => 'slate'],
            'geofenceEnter'   => ['label' => 'Entrou na Cerca',     'key' => 'geofence',  'icon' => 'fa-map-marker-alt', 'color' => 'blue'],
            'geofenceExit'    => ['label' => 'Saiu da Cerca',       'key' => 'geofence',  'icon' => 'fa-share-square',   'color' => 'orange'],
            'alarm'           => ['label' => 'Alarme SOS',          'key' => 'power',     'icon' => 'fa-bell',           'color' => 'red'],
            'powerCut'        => ['label' => 'Bateria Cortada',     'key' => 'power',     'icon' => 'fa-car-battery',    'color' => 'red'],
            'maintenance'     => ['label' => 'Manutenção',          'key' => 'maintenance', 'icon' => 'fa-tools',        'color' => 'yellow']
        ];

        $results = [];
        foreach ($events as $ev) {
            $type = $ev['type'];
            if (!isset($typeMap[$type])) continue;
            
            $def = $typeMap[$type];

            // Filtro de Configuração
            if ($filterConfig !== null && empty($filterConfig[$def['key']])) continue;

            $devId = $ev['deviceId'];
            if (isset($vehicleMap[$devId])) {
                $veh = $vehicleMap[$devId];
                
                // CORREÇÃO: Verifica se serverTime existe, senão usa agora
                $timeString = $ev['serverTime'] ?? $ev['eventTime'] ?? 'now';
                
                try {
                    $dateObj = new DateTime($timeString);
                    $dateObj->setTimezone(new DateTimeZone('America/Porto_Velho')); // Seu fuso
                } catch (Exception $e) {
                    $dateObj = new DateTime('now');
                }

                $results[] = [
                    'id' => $ev['id'],
                    'plate' => $veh['plate'],
                    'customer' => $veh['customer_name'] ?? '---',
                    'title' => $def['label'],
                    'time' => $dateObj->format('H:i:s'),
                    'date' => $dateObj->format('d/m/Y'),
                    'full_date' => $dateObj->format('Y-m-d H:i:s'),
                    'icon' => $def['icon'],
                    'color' => $def['color']
                ];
            }
        }
        return $results;
    }
}