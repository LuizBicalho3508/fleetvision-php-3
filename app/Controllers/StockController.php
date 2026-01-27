<?php
namespace App\Controllers;

use App\Services\TraccarService;
use Exception;
use PDO;

class StockController extends ApiController {

    public function index() {
        try {
            $tenantId = $_SESSION['tenant_id'];
            // Garante que o IMEI venha no select
            $sql = "SELECT id, tenant_id, imei, brand, model, iccid, operator, line_number, traccar_device_id, status 
                    FROM saas_stock WHERE tenant_id = ? ORDER BY id DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$tenantId]);
            
            $this->json(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $tenantId = $_SESSION['tenant_id'];

            // 1. Validação Básica
            if (empty($data['imei']) || empty($data['model'])) {
                $this->json(['error' => 'IMEI e Modelo são obrigatórios.'], 400);
                return;
            }

            // 2. Verifica Duplicidade Local
            // Verifica tanto por imei quanto por identifier para garantir
            $stmt = $this->pdo->prepare("SELECT id FROM saas_stock WHERE imei = ? OR identifier = ?");
            $stmt->execute([$data['imei'], $data['imei']]);
            if ($stmt->fetch()) {
                $this->json(['error' => 'Este IMEI já está cadastrado no sistema.'], 409);
                return;
            }

            // 3. Cadastra/Busca no Traccar
            $traccar = new TraccarService();
            $deviceName = ($data['model']) . ' - ' . $data['imei'];
            
            try {
                // Tenta buscar se já existe no Traccar (resiliência caso tenha falhado antes)
                $existing = $traccar->findDeviceByUniqueId($data['imei']);
                if ($existing) {
                    $traccarId = $existing['id'];
                } else {
                    $newDevice = $traccar->addDevice($deviceName, $data['imei']);
                    $traccarId = $newDevice['id'];
                }
            } catch (Exception $e) {
                $this->json(['error' => 'Erro Traccar: ' . $e->getMessage()], 500);
                return;
            }

            // 4. Salva no Banco Local (CORRIGIDO: identifier + type)
            // Inserimos o IMEI tanto na coluna 'imei' quanto na 'identifier' para satisfazer o banco
            $sql = "INSERT INTO saas_stock (
                        tenant_id, type, identifier, imei, brand, model, iccid, operator, line_number, traccar_device_id, status
                    ) VALUES (
                        ?, 'tracker', ?, ?, ?, ?, ?, ?, ?, ?, 'available'
                    )";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $tenantId,
                $data['imei'], // Valor para a coluna identifier (que estava dando erro)
                $data['imei'], // Valor para a coluna imei
                $data['brand'] ?? '',
                $data['model'],
                $data['iccid'] ?? '',
                $data['operator'] ?? '',
                $data['line_number'] ?? '',
                $traccarId
            ]);

            $this->json(['success' => true, 'id' => $this->pdo->lastInsertId()]);

        } catch (Exception $e) {
            error_log("Erro Stock Store: " . $e->getMessage());
            $this->json(['error' => 'Erro ao salvar no Banco: ' . $e->getMessage()], 500);
        }
    }

    // --- NOVO MÉTODO: EDITAR ---
    public function update() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $tenantId = $_SESSION['tenant_id'];
            $id = $data['id'] ?? null;

            if (!$id) { $this->json(['error' => 'ID obrigatório'], 400); return; }

            // Busca dados atuais
            $stmt = $this->pdo->prepare("SELECT * FROM saas_stock WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$id, $tenantId]);
            $current = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$current) { $this->json(['error' => 'Dispositivo não encontrado'], 404); return; }

            // Atualiza Traccar se mudar Nome ou IMEI
            if ($current['traccar_device_id']) {
                $traccar = new TraccarService();
                $newName = ($data['model']) . ' - ' . ($data['imei']);
                try {
                    // Atualiza no Traccar
                    $traccar->updateDevice($current['traccar_device_id'], $newName, $data['imei']);
                } catch (Exception $e) {
                    $this->json(['error' => 'Erro ao atualizar Traccar: ' . $e->getMessage()], 500);
                    return;
                }
            }

            // Atualiza Banco Local
            $sql = "UPDATE saas_stock SET imei = ?, brand = ?, model = ?, iccid = ?, operator = ?, line_number = ? WHERE id = ?";
            $this->pdo->prepare($sql)->execute([
                $data['imei'], $data['brand'], $data['model'], 
                $data['iccid'], $data['operator'], $data['line_number'], $id
            ]);

            $this->json(['success' => true]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function delete() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? null;
            $tenantId = $_SESSION['tenant_id'];

            $stmt = $this->pdo->prepare("SELECT traccar_device_id FROM saas_stock WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$id, $tenantId]);
            $device = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($device && $device['traccar_device_id']) {
                $traccar = new TraccarService();
                try { $traccar->removeDevice($device['traccar_device_id']); } catch (Exception $e) {}
            }

            $this->pdo->prepare("DELETE FROM saas_stock WHERE id = ?")->execute([$id]);
            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}