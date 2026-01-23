<?php
namespace App\Controllers;

use PDO;
use Exception;

class DriverController extends ApiController {

    public function index() {
        try {
            $sql = "SELECT * FROM saas_drivers WHERE tenant_id = ? ORDER BY name ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$this->tenant_id]);
            $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Verifica vencimento de CNH
            foreach ($drivers as &$d) {
                $d['cnh_status'] = 'ok';
                if ($d['cnh_validity']) {
                    $validity = new \DateTime($d['cnh_validity']);
                    $now = new \DateTime();
                    $diff = $now->diff($validity);
                    
                    if ($validity < $now) {
                        $d['cnh_status'] = 'expired';
                    } elseif ($diff->days < 30 && $diff->invert == 0) {
                        $d['cnh_status'] = 'warning';
                    }
                }
            }

            $this->json(['success' => true, 'data' => $drivers]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store() {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        $name = $input['name'] ?? '';
        
        if (empty($name)) $this->json(['error' => 'Nome é obrigatório'], 400);

        try {
            if ($id) {
                $sql = "UPDATE saas_drivers SET name=?, cnh=?, cnh_validity=?, phone=?, rfid=?, status=? WHERE id=? AND tenant_id=?";
                $this->pdo->prepare($sql)->execute([
                    $name, 
                    $input['cnh']??'', 
                    !empty($input['cnh_validity']) ? $input['cnh_validity'] : null, 
                    $input['phone']??'', 
                    $input['rfid']??'', 
                    $input['status']??'active', 
                    $id, 
                    $this->tenant_id
                ]);
            } else {
                $sql = "INSERT INTO saas_drivers (tenant_id, name, cnh, cnh_validity, phone, rfid, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $this->pdo->prepare($sql)->execute([
                    $this->tenant_id, 
                    $name, 
                    $input['cnh']??'', 
                    !empty($input['cnh_validity']) ? $input['cnh_validity'] : null, 
                    $input['phone']??'', 
                    $input['rfid']??'', 
                    'active'
                ]);
            }
            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function delete() {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;

        if (!$id) $this->json(['error' => 'ID inválido'], 400);

        try {
            $this->pdo->prepare("DELETE FROM saas_drivers WHERE id = ? AND tenant_id = ?")->execute([$id, $this->tenant_id]);
            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}