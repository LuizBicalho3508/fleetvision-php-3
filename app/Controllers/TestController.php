<?php
namespace App\Controllers;

use App\Services\TraccarService;
use PDO;
use Exception;

class TestController extends ApiController {

    /**
     * Carrega a página HTML (View) dentro do Layout Principal
     */
    public function index() {
        // 1. Define variáveis que o layout.php pode precisar (ex: Título, Menu Ativo)
        $viewName = 'teste'; 
        
        // 2. Define o caminho da View interna (O conteúdo da página de testes)
        // O layout.php deverá incluir este arquivo no local do conteúdo principal
        $viewPath = __DIR__ . '/../../views/teste.php';

        // 3. Carrega o Layout Principal
        // Isso garante que Sidebar, Header (CSS) e Footer (Scripts) sejam carregados
        if (file_exists(__DIR__ . '/../../views/layout.php')) {
            require __DIR__ . '/../../views/layout.php';
        } else {
            // Fallback caso não exista layout.php (carrega manuais)
            $this->loadManualLayout();
        }
    }

    /**
     * Fallback para carregar header/footer manualmente se layout.php não existir
     */
    private function loadManualLayout() {
        $header = __DIR__ . '/../../views/includes/header.php';
        $footer = __DIR__ . '/../../views/includes/footer.php';
        
        if (file_exists($header)) require $header;
        require __DIR__ . '/../../views/teste.php';
        if (file_exists($footer)) require $footer;
    }

    // ========================================================================
    //                               API METHODS
    // ========================================================================

    /**
     * API: Lista rastreadores do Estoque que possuem ID Traccar vinculado
     * Rota: GET /sys/teste/stock
     */
    public function listStock() {
        try {
            $tenantId = $_SESSION['tenant_id'];
            
            // Busca apenas equipamentos com traccar_device_id definido
            $sql = "SELECT id, model, imei, traccar_device_id, status 
                    FROM saas_stock 
                    WHERE tenant_id = ? AND traccar_device_id IS NOT NULL 
                    ORDER BY model ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$tenantId]);
            $this->json(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Pega dados ao vivo de um dispositivo (Status, Posição, JSON Raw)
     * Rota: GET /sys/teste/live?imei=XXXX
     */
    public function getDeviceLive() {
        try {
            $imei = $_GET['imei'] ?? null;
            if (!$imei) {
                $this->json(['error' => 'IMEI não informado'], 400);
                return;
            }

            $traccar = new TraccarService();
            
            // Busca dados do Traccar (Devices e Positions)
            $positions = $traccar->getPositions(); 
            $devices = $traccar->getDevices();

            // 1. Encontra o dispositivo na lista
            $targetDevice = null;
            foreach ($devices as $d) {
                if ($d['uniqueId'] == $imei) {
                    $targetDevice = $d;
                    break;
                }
            }

            if (!$targetDevice) {
                $this->json(['error' => 'Dispositivo offline ou não encontrado na API Traccar.']);
                return;
            }

            // 2. Encontra a última posição
            $targetPos = null;
            foreach ($positions as $p) {
                if ($p['deviceId'] == $targetDevice['id']) {
                    $targetPos = $p;
                    break;
                }
            }

            $this->json([
                'device' => $targetDevice,
                'position' => $targetPos,
                'raw' => $targetPos // Envia o pacote completo para o visualizador JSON
            ]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Envia Comandos (Bloqueio, Desbloqueio, Custom)
     * Rota: POST /sys/teste/command
     */
    public function sendCommand() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $traccarId = $data['traccar_device_id'] ?? null;
            $type = $data['type'] ?? null; 
            $params = $data['params'] ?? [];

            if (!$traccarId || !$type) {
                $this->json(['error' => 'ID e Tipo são obrigatórios'], 400);
                return;
            }

            $traccar = new TraccarService();
            $result = $traccar->sendCommand($traccarId, $type, $params);
            
            $this->json(['success' => true, 'result' => $result]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Busca histórico de rota (JSON Raw)
     * Rota: GET /sys/teste/history?id=X&date=Y-m-d
     */
    public function getHistory() {
        try {
            $traccarId = $_GET['id'] ?? null;
            $date = $_GET['date'] ?? null;

            if (!$traccarId || !$date) {
                $this->json(['error' => 'ID e Data obrigatórios'], 400);
                return;
            }

            // Converte data para UTC (ISO 8601) para consulta no Traccar
            $from = date('Y-m-d\TH:i:s\Z', strtotime($date . ' 00:00:00'));
            $to   = date('Y-m-d\TH:i:s\Z', strtotime($date . ' 23:59:59'));

            $traccar = new TraccarService();
            $route = $traccar->getRoute([$traccarId], $from, $to);

            $this->json(['data' => $route]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}