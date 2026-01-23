<?php
namespace App\Controllers;

use PDO;
use Exception;

class StockController extends ApiController {

    // --- MÉTODOS DE ESTOQUE ---

    public function getItems() {
        try {
            $sql = "SELECT * FROM saas_stock WHERE tenant_id = ? ORDER BY item_name ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$this->tenant_id]);
            $this->json(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function saveItem() {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        $name = $input['item_name'] ?? '';

        if (empty($name)) $this->json(['error' => 'Nome do item obrigatório'], 400);

        try {
            if ($id) {
                $sql = "UPDATE saas_stock SET item_name=?, category=?, quantity=?, min_quantity=?, unit_cost=? WHERE id=? AND tenant_id=?";
                $params = [$name, $input['category'], $input['quantity'], $input['min_quantity'], $input['unit_cost'], $id, $this->tenant_id];
            } else {
                $sql = "INSERT INTO saas_stock (tenant_id, item_name, category, quantity, min_quantity, unit_cost) VALUES (?, ?, ?, ?, ?, ?)";
                $params = [$this->tenant_id, $name, $input['category'], $input['quantity'], $input['min_quantity'], $input['unit_cost']];
            }
            $this->pdo->prepare($sql)->execute($params);
            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteItem() {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        if (!$id) $this->json(['error' => 'ID inválido'], 400);

        $this->pdo->prepare("DELETE FROM saas_stock WHERE id = ? AND tenant_id = ?")->execute([$id, $this->tenant_id]);
        $this->json(['success' => true]);
    }

    // --- MÉTODOS DE MANUTENÇÃO ---

    public function getMaintenance() {
        try {
            // Join com veículos para mostrar a placa
            $sql = "SELECT m.*, v.plate, v.name as vehicle_name 
                    FROM saas_maintenance m 
                    JOIN saas_vehicles v ON m.vehicle_id = v.id 
                    WHERE m.tenant_id = ? 
                    ORDER BY m.service_date DESC LIMIT 50";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$this->tenant_id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Formatação de data e moeda
            foreach ($rows as &$r) {
                $r['formatted_date'] = date('d/m/Y', strtotime($r['service_date']));
                $r['formatted_cost'] = 'R$ ' . number_format($r['cost'], 2, ',', '.');
            }
            
            $this->json(['success' => true, 'data' => $rows]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function saveMaintenance() {
        $input = json_decode(file_get_contents('php://input'), true);
        $vehicleId = $input['vehicle_id'] ?? null;
        $type = $input['service_type'] ?? '';

        if (!$vehicleId || empty($type)) $this->json(['error' => 'Veículo e Tipo são obrigatórios'], 400);

        try {
            $sql = "INSERT INTO saas_maintenance (tenant_id, vehicle_id, service_type, description, cost, service_date, odometer, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $this->pdo->prepare($sql)->execute([
                $this->tenant_id,
                $vehicleId,
                $type,
                $input['description'] ?? '',
                $input['cost'] ?? 0,
                $input['service_date'] ?? date('Y-m-d'),
                $input['odometer'] ?? 0,
                $input['status'] ?? 'completed'
            ]);
            
            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}