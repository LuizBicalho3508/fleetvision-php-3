<?php
namespace App\Controllers;

use PDO;
use Exception;

class VehicleController extends ApiController {

    public function index() {
        $this->render('frota');
    }

    public function list() {
        try {
            if (session_status() === PHP_SESSION_NONE) session_start();

            $tenantId = $_SESSION['tenant_id'];
            
            // Tratamento: Se for vazio, 0 ou string "0", vira NULL
            $customerId = $_SESSION['customer_id'] ?? null;
            if (empty($customerId) || $customerId == 0) {
                $customerId = null;
            }

            $pdo = $this->pdo;
            $params = [];
            $sql = "";
            $debugType = "";

            // =========================================================
            // CENÁRIO A: É CLIENTE (TEM ID)
            // =========================================================
            if ($customerId) {
                $debugType = "Modo Cliente (ID: $customerId)";
                $sql = "SELECT v.*, 
                               c.name as customer_name, 
                               s.model as tracker_model 
                        FROM saas_vehicles v
                        LEFT JOIN saas_customers c ON v.customer_id = c.id
                        LEFT JOIN saas_stock s ON v.stock_id = s.id
                        WHERE v.tenant_id = ? 
                        AND v.customer_id = ? 
                        ORDER BY v.id DESC";
                $params = [$tenantId, $customerId];
            } 
            // =========================================================
            // CENÁRIO B: É ADMIN (NÃO TEM ID)
            // =========================================================
            else {
                $debugType = "Modo Admin (Ver Tudo)";
                $sql = "SELECT v.*, 
                               c.name as customer_name, 
                               s.model as tracker_model 
                        FROM saas_vehicles v
                        LEFT JOIN saas_customers c ON v.customer_id = c.id
                        LEFT JOIN saas_stock s ON v.stock_id = s.id
                        WHERE v.tenant_id = ? 
                        ORDER BY v.id DESC";
                $params = [$tenantId];
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // UTF-8 Clean
            if ($data) {
                array_walk_recursive($data, function(&$item) {
                    if (is_string($item)) $item = mb_convert_encoding($item, 'UTF-8', 'UTF-8');
                });
            }

            // RETORNA DADOS + O SQL QUE FOI RODADO
            $this->json([
                'data' => $data,
                'debug_info' => [
                    'mode' => $debugType,
                    'sql_executed' => $sql,
                    'params_used' => $params,
                    'rows_found' => count($data)
                ]
            ]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
    
    // ... (Mantenha store, update, delete, getAvailableTrackers iguais) ...
    public function getAvailableTrackers() {
        try {
            $tenantId = $_SESSION['tenant_id'];
            $sql = "SELECT id, model, imei, brand FROM saas_stock WHERE tenant_id = ? AND status = 'available' ORDER BY model ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$tenantId]);
            $this->json(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (Exception $e) { $this->json(['error' => $e->getMessage()], 500); }
    }
    public function store() {
         try {
            $data = json_decode(file_get_contents('php://input'), true);
            $tenantId = $_SESSION['tenant_id'];
            if (empty($data['plate']) || empty($data['stock_id'])) { $this->json(['error' => 'Dados incompletos.'], 400); return; }
            $finalCustomerId = $_SESSION['customer_id'] ?? ($data['customer_id'] ?? null);
            $this->pdo->beginTransaction();
             $sql = "INSERT INTO saas_vehicles (tenant_id, plate, stock_id, customer_id, status, active_since) VALUES (?, ?, ?, ?, 'active', CURRENT_DATE)";
             $this->pdo->prepare($sql)->execute([$tenantId, $data['plate'], $data['stock_id'], $finalCustomerId]);
             $this->pdo->prepare("UPDATE saas_stock SET status = 'installed' WHERE id = ?")->execute([$data['stock_id']]);
            $this->pdo->commit();
            $this->json(['success' => true]);
        } catch (Exception $e) { if ($this->pdo->inTransaction()) $this->pdo->rollBack(); $this->json(['error' => $e->getMessage()], 500); }
    }
    public function update() {
         try {
            $data = json_decode(file_get_contents('php://input'), true);
            $this->pdo->prepare("UPDATE saas_vehicles SET plate = ?, customer_id = ? WHERE id = ?")->execute([$data['plate'], $data['customer_id'], $data['id']]);
            $this->json(['success' => true]);
         } catch (Exception $e) { $this->json(['error' => $e->getMessage()], 500); }
    }
    public function delete() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $this->pdo->prepare("DELETE FROM saas_vehicles WHERE id = ?")->execute([$input['id']]);
             $this->json(['success' => true]);
        } catch (Exception $e) { $this->json(['error' => $e->getMessage()], 500); }
    }
}
?>