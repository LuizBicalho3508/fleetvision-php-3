<?php
namespace App\Controllers;

use App\Services\TraccarService;
use Exception;
use PDO;

class VehicleController extends ApiController {

    public function index() {
        try {
            $tenantId = $_SESSION['tenant_id'];
            
            $sql = "SELECT v.*, 
                           c.name as customer_name, 
                           s.model as tracker_model, 
                           s.imei as tracker_imei 
                    FROM saas_vehicles v
                    LEFT JOIN saas_customers c ON v.customer_id = c.id
                    LEFT JOIN saas_stock s ON v.stock_id = s.id
                    WHERE v.tenant_id = ? 
                    ORDER BY v.id DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$tenantId]);
            $this->json(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getAvailableTrackers() {
        try {
            $tenantId = $_SESSION['tenant_id'];
            $sql = "SELECT id, model, imei, brand FROM saas_stock 
                    WHERE tenant_id = ? AND status = 'available' 
                    ORDER BY model ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$tenantId]);
            $this->json(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // --- CADASTRAR VEÍCULO ---
    public function store() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $tenantId = $_SESSION['tenant_id'];

            if (empty($data['plate']) || empty($data['stock_id']) || empty($data['customer_id'])) {
                $this->json(['error' => 'Placa, Rastreador e Cliente são obrigatórios.'], 400);
                return;
            }

            $this->pdo->beginTransaction();

            // 1. Busca dados do Rastreador (Precisamos do IMEI para o campo identifier)
            $stmtStock = $this->pdo->prepare("SELECT id, traccar_device_id, imei FROM saas_stock WHERE id = ? AND status = 'available'");
            $stmtStock->execute([$data['stock_id']]);
            $tracker = $stmtStock->fetch(PDO::FETCH_ASSOC);

            if (!$tracker) {
                throw new Exception("Rastreador indisponível ou não encontrado.");
            }

            $plateUpper = strtoupper($data['plate']);

            // 2. Atualiza nome no Traccar
            if ($tracker['traccar_device_id']) {
                $traccar = new TraccarService();
                try {
                    $traccar->updateDevice($tracker['traccar_device_id'], $plateUpper, $tracker['imei']);
                } catch (Exception $e) {
                    error_log("Aviso Traccar: " . $e->getMessage());
                }
            }

            // 3. Insere Veículo (CORREÇÃO: Incluindo 'identifier' com o IMEI)
            $sql = "INSERT INTO saas_vehicles (
                tenant_id, identifier, name, plate, brand, model, color, year, 
                stock_id, customer_id, speed_limit, km_per_liter, icon, active_since, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_DATE, 'active')";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $tenantId, 
                $tracker['imei'], // 'identifier' recebe o IMEI
                $plateUpper,      // 'name' recebe a Placa
                $plateUpper,      // 'plate' recebe a Placa
                $data['brand'] ?? '', 
                $data['model'] ?? '', 
                $data['color'] ?? '', 
                $data['year'] ?? '',
                $data['stock_id'], 
                $data['customer_id'], 
                $data['speed_limit'] ?? 80, 
                $data['km_per_liter'] ?? 10.0,
                $data['icon'] ?? '/assets/img/car-default.png'
            ]);

            // 4. Marca Rastreador como Instalado
            $updStock = $this->pdo->prepare("UPDATE saas_stock SET status = 'installed' WHERE id = ?");
            $updStock->execute([$data['stock_id']]);

            $this->pdo->commit();
            $this->json(['success' => true]);

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // --- EDITAR VEÍCULO ---
    public function update() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $tenantId = $_SESSION['tenant_id'];
            $id = $data['id'] ?? null;

            if (!$id) {
                $this->json(['error' => 'ID obrigatório.'], 400);
                return;
            }

            $stmt = $this->pdo->prepare("SELECT * FROM saas_vehicles WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$id, $tenantId]);
            $current = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$current) {
                $this->json(['error' => 'Veículo não encontrado.'], 404);
                return;
            }

            $plateUpper = strtoupper($data['plate']);

            // Se mudou a placa, atualiza no Traccar
            if ($current['stock_id'] && $plateUpper !== strtoupper($current['plate'])) {
                $stmtStock = $this->pdo->prepare("SELECT traccar_device_id, imei FROM saas_stock WHERE id = ?");
                $stmtStock->execute([$current['stock_id']]);
                $tracker = $stmtStock->fetch(PDO::FETCH_ASSOC);

                if ($tracker && $tracker['traccar_device_id']) {
                    $traccar = new TraccarService();
                    try {
                        $traccar->updateDevice($tracker['traccar_device_id'], $plateUpper, $tracker['imei']);
                    } catch (Exception $e) {}
                }
            }

            // Atualiza Banco
            $sql = "UPDATE saas_vehicles SET 
                    name = ?, plate = ?, brand = ?, model = ?, color = ?, year = ?, 
                    speed_limit = ?, km_per_liter = ?, icon = ?
                    WHERE id = ? AND tenant_id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $plateUpper,
                $plateUpper,
                $data['brand'],
                $data['model'],
                $data['color'],
                $data['year'],
                $data['speed_limit'],
                $data['km_per_liter'],
                $data['icon'],
                $id,
                $tenantId
            ]);

            $this->json(['success' => true]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function delete() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'];

            $stmt = $this->pdo->prepare("SELECT stock_id FROM saas_vehicles WHERE id = ?");
            $stmt->execute([$id]);
            $veh = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($veh) {
                $this->pdo->prepare("UPDATE saas_stock SET status = 'available' WHERE id = ?")->execute([$veh['stock_id']]);
                $this->pdo->prepare("DELETE FROM saas_vehicles WHERE id = ?")->execute([$id]);
            }

            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}