<?php
namespace App\Controllers;

use App\Services\TraccarService;
use Exception;
use PDO;

class VehicleController extends ApiController {

    public function index() {
        $this->render('frota');
    }

    /**
     * Lista veículos com isolamento total de Tenant e Cliente
     */
    public function list() {
        try {
            if (session_status() === PHP_SESSION_NONE) session_start();

            // 1. SEGURANÇA: Captura e validação rigorosa dos IDs da Sessão
            // Se tenant_id não estiver na sessão, força 0 (não retorna nada)
            $tenantId = isset($_SESSION['tenant_id']) ? (int)$_SESSION['tenant_id'] : 0;
            
            // Filtro de Cliente (Opcional, para usuários finais)
            $customerId = null;
            if (isset($_SESSION['customer_id']) && $_SESSION['customer_id'] != 0) {
                $customerId = (int)$_SESSION['customer_id'];
            }

            $pdo = $this->pdo;
            $params = [];

            // 2. QUERY BLINDADA (Scalable & Secure)
            // Usamos COALESCE para evitar quebras no Front-end se relacionamentos falharem
            $sql = "SELECT v.*, 
                           COALESCE(c.name, 'Cliente N/A') as customer_name, 
                           COALESCE(s.model, 'Rastreador N/A') as tracker_model, 
                           s.imei as tracker_imei 
                    FROM saas_vehicles v
                    LEFT JOIN saas_customers c ON v.customer_id = c.id
                    LEFT JOIN saas_stock s ON v.stock_id = s.id
                    WHERE v.tenant_id = :tenant_id"; // <--- O Muro de Isolamento
            
            $params[':tenant_id'] = $tenantId;

            // 3. APLICAÇÃO CONDICIONAL DE FILTRO DE CLIENTE
            if ($customerId) {
                $sql .= " AND v.customer_id = :customer_id";
                $params[':customer_id'] = $customerId;
            }

            $sql .= " ORDER BY v.id DESC";

            // 4. EXECUÇÃO
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 5. SANITIZAÇÃO DE DADOS (UTF-8)
            // Essencial para evitar erros de JSON silenciosos
            if ($data) {
                array_walk_recursive($data, function(&$item) {
                    if (is_string($item) && !mb_detect_encoding($item, 'UTF-8', true)) {
                        $item = utf8_encode($item);
                    }
                });
            }

            // 6. RESPOSTA JSON SEGURA
            // Se houver erro no encode, retorna erro explicito em vez de tela branca
            $json = json_encode(['data' => $data]);
            if ($json === false) {
                throw new Exception("Erro ao processar dados (UTF-8/JSON): " . json_last_error_msg());
            }

            echo $json;
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }

    // --- MÉTODOS DE OPERAÇÃO ---

    public function getAvailableTrackers() {
        try {
            $tenantId = $_SESSION['tenant_id'] ?? 0;
            // Filtra rastreadores APENAS do tenant atual
            $sql = "SELECT id, model, imei, brand FROM saas_stock 
                    WHERE tenant_id = ? AND status = 'available' 
                    ORDER BY model ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$tenantId]);
            echo json_encode(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (Exception $e) { echo json_encode(['error' => $e->getMessage()]); }
    }

    public function store() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $tenantId = $_SESSION['tenant_id'] ?? 0; // Pega ID da sessão, nunca do POST
            
            if (empty($data['plate']) || empty($data['stock_id'])) {
                echo json_encode(['error' => 'Placa e Rastreador obrigatórios']); return;
            }

            // Define customer_id (Prioridade: Sessão > Form > Null)
            $custId = (!empty($_SESSION['customer_id'])) ? $_SESSION['customer_id'] : ($data['customer_id'] ?? null);

            $this->pdo->beginTransaction();

            // Lógica Traccar
            $stmt = $this->pdo->prepare("SELECT id, traccar_device_id, imei FROM saas_stock WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$data['stock_id'], $tenantId]); // Garante que estoque é do tenant
            $tracker = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($tracker && !empty($tracker['traccar_device_id'])) {
                $traccar = new TraccarService();
                try { 
                    $traccar->updateDevice($tracker['traccar_device_id'], strtoupper($data['plate']), $tracker['imei']); 
                } catch (Exception $e) {}
            }

            $sql = "INSERT INTO saas_vehicles (tenant_id, plate, stock_id, customer_id, status, active_since, identifier, name, brand, model, color, year) 
                    VALUES (?, ?, ?, ?, 'active', CURRENT_DATE, ?, ?, ?, ?, ?, ?)";
            
            $this->pdo->prepare($sql)->execute([
                $tenantId, 
                strtoupper($data['plate']), 
                $data['stock_id'], 
                $custId,
                $tracker['imei'] ?? null, 
                strtoupper($data['plate']),
                $data['brand'] ?? '', $data['model'] ?? '', $data['color'] ?? '', $data['year'] ?? ''
            ]);

            $this->pdo->prepare("UPDATE saas_stock SET status = 'installed' WHERE id = ?")->execute([$data['stock_id']]);
            $this->pdo->commit();
            echo json_encode(['success' => true]);

        } catch (Exception $e) { 
            if ($this->pdo->inTransaction()) $this->pdo->rollBack(); 
            echo json_encode(['error' => $e->getMessage()]); 
        }
    }

    public function update() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $tenantId = $_SESSION['tenant_id'] ?? 0;

            // Segurança: Só atualiza se o veículo pertencer ao Tenant da sessão
            $check = $this->pdo->prepare("SELECT id FROM saas_vehicles WHERE id = ? AND tenant_id = ?");
            $check->execute([$data['id'], $tenantId]);
            if (!$check->fetch()) { echo json_encode(['error' => 'Acesso Negado ou Veículo não encontrado']); return; }

            $custId = (!empty($_SESSION['customer_id'])) ? $_SESSION['customer_id'] : ($data['customer_id'] ?? null);

            $sql = "UPDATE saas_vehicles SET plate = ?, customer_id = ?, brand = ?, model = ?, color = ?, year = ? WHERE id = ?";
            $this->pdo->prepare($sql)->execute([
                strtoupper($data['plate']), $custId, 
                $data['brand']??'', $data['model']??'', $data['color']??'', $data['year']??'', 
                $data['id']
            ]);
            echo json_encode(['success' => true]);

        } catch (Exception $e) { echo json_encode(['error' => $e->getMessage()]); }
    }

    public function delete() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            // Segurança: DELETE com AND tenant_id obrigatório
            $this->pdo->prepare("DELETE FROM saas_vehicles WHERE id = ? AND tenant_id = ?")->execute([$input['id'], $_SESSION['tenant_id']]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) { echo json_encode(['error' => $e->getMessage()]); }
    }
}
?>