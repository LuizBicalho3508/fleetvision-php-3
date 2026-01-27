<?php
namespace App\Controllers;

use App\Services\TraccarService;
use PDO;
use Exception;

class DriverController extends ApiController {

    public function index() {
        $viewName = 'motoristas';
        // Carrega o layout principal
        if (file_exists(__DIR__ . '/../../views/layout.php')) {
            require __DIR__ . '/../../views/layout.php';
        } else {
            require __DIR__ . '/../../views/motoristas.php';
        }
    }

    // LISTAR (Com Isolamento de Dados)
    public function list() {
        try {
            $tenantId = $_SESSION['tenant_id'];
            $userCustomerId = $_SESSION['user_customer_id'] ?? null; // Isolamento
            
            $sql = "SELECT d.*, c.name as customer_name 
                    FROM saas_drivers d
                    LEFT JOIN saas_customers c ON d.customer_id = c.id
                    WHERE d.tenant_id = ?";
            
            $params = [$tenantId];

            // SE TIVER RESTRIÇÃO DE CLIENTE, APLICA FILTRO
            if (!empty($userCustomerId)) {
                $sql .= " AND d.customer_id = ?";
                $params[] = $userCustomerId;
            }
            
            $sql .= " ORDER BY d.name ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Formata status da CNH
            foreach ($drivers as &$d) {
                if(!empty($d['cnh_expiration'])) {
                    $d['cnh_status'] = (strtotime($d['cnh_expiration']) < time()) ? 'expired' : 'valid';
                } else {
                    $d['cnh_status'] = 'unknown';
                }
            }

            $this->json(['data' => $drivers]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // SALVAR (Completo + Sync Traccar + Isolamento)
    public function store() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $tenantId = $_SESSION['tenant_id'];
            $userCustomerId = $_SESSION['user_customer_id'] ?? null; // Isolamento
            
            if (empty($data['name']) || empty($data['document'])) {
                $this->json(['error' => 'Nome e Identificador (CPF/RFID) são obrigatórios.'], 400);
                return;
            }

            // LÓGICA DE SEGURANÇA PARA CLIENTE
            $targetCustomerId = !empty($data['customer_id']) ? $data['customer_id'] : null;

            // Se o usuário logado for restrito, ele SÓ pode criar/editar para o cliente dele
            if (!empty($userCustomerId)) {
                $targetCustomerId = $userCustomerId;
            }

            $this->pdo->beginTransaction();

            // PREPARA OS DADOS
            $params = [
                $data['name'], 
                $data['document'], 
                $targetCustomerId, // Usa o ID seguro calculado acima
                $data['cnh_number'] ?? null, 
                $data['cnh_category'] ?? null,
                !empty($data['cnh_expiration']) ? $data['cnh_expiration'] : null, 
                $data['phone'] ?? null, 
                $data['email'] ?? null, 
                !empty($data['birth_date']) ? $data['birth_date'] : null,
                $data['status'] ?? 'active'
            ];

            // 1. Salva/Atualiza Banco Local
            if (!empty($data['id'])) {
                // UPDATE
                $sql = "UPDATE saas_drivers SET 
                        name=?, document=?, customer_id=?, cnh_number=?, cnh_category=?, 
                        cnh_expiration=?, phone=?, email=?, birth_date=?, status=?
                        WHERE id=? AND tenant_id=?";
                
                // Adiciona ID e Tenant aos parâmetros
                $params[] = $data['id'];
                $params[] = $tenantId;

                // Se for restrito, garante que só edita os seus (segurança extra no WHERE)
                if (!empty($userCustomerId)) {
                    $sql .= " AND customer_id = ?";
                    $params[] = $userCustomerId;
                }

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);

                // Se não afetou nenhuma linha, pode ser tentativa de editar motorista de outro cliente
                if ($stmt->rowCount() === 0) {
                    // Verifica se o ID existe
                    $check = $this->pdo->prepare("SELECT id FROM saas_drivers WHERE id = ?");
                    $check->execute([$data['id']]);
                    if ($check->fetch()) {
                        // Existe mas não foi atualizado -> Permissão negada (pertence a outro cliente)
                        throw new Exception("Permissão negada para editar este motorista.");
                    }
                }

                $localId = $data['id'];
                
                // Pega ID Traccar para sincronia
                $stmtGet = $this->pdo->prepare("SELECT traccar_driver_id FROM saas_drivers WHERE id = ?");
                $stmtGet->execute([$localId]);
                $traccarId = $stmtGet->fetchColumn();

            } else {
                // INSERT
                $sql = "INSERT INTO saas_drivers (
                        name, document, customer_id, cnh_number, cnh_category, 
                        cnh_expiration, phone, email, birth_date, status, tenant_id
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id";
                
                $params[] = $tenantId;
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                $localId = $stmt->fetchColumn();
                $traccarId = null;
            }

            // 2. Sync Traccar
            $traccar = new TraccarService();
            try {
                if ($traccarId) {
                    $traccar->updateDriver($traccarId, $data['name'], $data['document']);
                } else {
                    $res = $traccar->addDriver($data['name'], $data['document']);
                    $traccarId = $res['id'];
                    $this->pdo->prepare("UPDATE saas_drivers SET traccar_driver_id = ? WHERE id = ?")->execute([$traccarId, $localId]);
                }
            } catch (Exception $e) {
                // Log silencioso ou Warning (Traccar pode estar offline, mas salvamos local)
            }

            $this->pdo->commit();
            $this->json(['success' => true]);

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // EXCLUIR (Com Isolamento)
    public function delete() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id']; // ID Local
            $tenantId = $_SESSION['tenant_id'];
            $userCustomerId = $_SESSION['user_customer_id'] ?? null;

            // Busca ID Traccar e Valida Propriedade
            $sqlInfo = "SELECT traccar_driver_id FROM saas_drivers WHERE id = ? AND tenant_id = ?";
            $paramsInfo = [$id, $tenantId];

            if (!empty($userCustomerId)) {
                $sqlInfo .= " AND customer_id = ?";
                $paramsInfo[] = $userCustomerId;
            }

            $stmt = $this->pdo->prepare($sqlInfo);
            $stmt->execute($paramsInfo);
            $traccarId = $stmt->fetchColumn();

            // Se não retornou nada, ou não existe ou não pertence ao cliente do usuário
            if ($traccarId === false) {
                 // Verifica se existe sem o filtro de cliente para dar erro correto
                 $check = $this->pdo->prepare("SELECT id FROM saas_drivers WHERE id = ?");
                 $check->execute([$id]);
                 if ($check->fetch()) {
                     $this->json(['error' => 'Você não tem permissão para excluir este motorista.'], 403);
                 } else {
                     $this->json(['error' => 'Motorista não encontrado.'], 404);
                 }
                 return;
            }

            // Remove do Banco Local
            $this->pdo->prepare("DELETE FROM saas_drivers WHERE id = ?")->execute([$id]);

            // Remove do Traccar
            if ($traccarId) {
                $traccar = new TraccarService();
                try {
                    $traccar->deleteDriver($traccarId);
                } catch(Exception $e) {} 
            }

            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}