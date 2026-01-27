<?php
namespace App\Controllers;

use PDO;
use Exception;

class UserController extends ApiController {

    public function index() {
        $viewName = 'usuarios';
        if (file_exists(__DIR__ . '/../../views/layout.php')) {
            require __DIR__ . '/../../views/layout.php';
        } else {
            require __DIR__ . '/../../views/usuarios.php';
        }
    }

    // LISTAR USUÁRIOS (Corrigido erro 500)
    // LISTAR USUÁRIOS (Com Visão Global para Superadmin)
    public function list() {
        try {
            if (!isset($_SESSION['tenant_id'])) {
                $this->json(['error' => 'Sessão expirada'], 401);
                return;
            }
            
            $tenantId = $_SESSION['tenant_id'];
            // Verifica se é Superadmin
            $isSuperAdmin = ($_SESSION['user_role'] ?? '') === 'superadmin';

            // 1. Monta a Query Base
            // Adicionamos t.name (Tenant Name) e t.slug
            $sql = "SELECT u.id, u.name, u.email, u.status, u.role_id, u.customer_id, u.last_login, u.tenant_id,
                           r.name as role_name,
                           c.name as customer_name,
                           t.name as tenant_name, t.slug as tenant_slug
                    FROM saas_users u
                    LEFT JOIN saas_roles r ON u.role_id = r.id
                    LEFT JOIN saas_customers c ON u.customer_id = c.id
                    LEFT JOIN saas_tenants t ON u.tenant_id = t.id"; // Join com Tenants

            $params = [];

            // 2. Aplica Filtro (SÓ SE NÃO FOR SUPERADMIN)
            if (!$isSuperAdmin) {
                $sql .= " WHERE u.tenant_id = ?";
                $params[] = $tenantId;
            }

            // Ordenação: Se for superadmin, agrupa por empresa, depois por nome
            if ($isSuperAdmin) {
                $sql .= " ORDER BY t.name ASC, u.name ASC";
            } else {
                $sql .= " ORDER BY u.name ASC";
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 3. Auxiliares (Roles e Customers)
            // Se for superadmin, idealmente carregaria de todos, mas para evitar dropdown gigante,
            // mantemos o carregamento do tenant atual ou vazio para edição.
            // Para visualização, o passo 1 já resolve.
            
            $stmtRoles = $this->pdo->prepare("SELECT id, name FROM saas_roles WHERE tenant_id = ? ORDER BY name");
            $stmtRoles->execute([$tenantId]);
            $roles = $stmtRoles->fetchAll(PDO::FETCH_ASSOC);

            $stmtCust = $this->pdo->prepare("SELECT id, name FROM saas_customers WHERE tenant_id = ? ORDER BY name");
            $stmtCust->execute([$tenantId]);
            $customers = $stmtCust->fetchAll(PDO::FETCH_ASSOC);

            $this->json([
                'users' => $users,
                'roles' => $roles,
                'customers' => $customers,
                'is_superadmin' => $isSuperAdmin // Flag para o Frontend saber
            ]);

        } catch (Exception $e) {
            error_log("Erro List Users: " . $e->getMessage()); 
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // SALVAR USUÁRIO (Corrigido erro 400)
    public function store() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($_SESSION['tenant_id'])) {
                $this->json(['error' => 'Sessão inválida'], 401); 
                return;
            }
            $tenantId = $_SESSION['tenant_id'];

            // Validação mais flexível
            if (empty($data['name']) || empty($data['email'])) {
                $this->json(['error' => 'Nome e E-mail são obrigatórios.'], 400);
                return;
            }

            // Tratamento de nulos
            $roleId = !empty($data['role_id']) ? $data['role_id'] : null;
            $customerId = !empty($data['customer_id']) ? $data['customer_id'] : null;
            $status = $data['status'] ?? 'active';

            // Verifica duplicidade de email
            $sqlCheck = "SELECT id FROM saas_users WHERE email = ? AND tenant_id = ?";
            $paramsCheck = [$data['email'], $tenantId];
            
            if (!empty($data['id'])) {
                $sqlCheck .= " AND id != ?";
                $paramsCheck[] = $data['id'];
            }
            
            $stmtCheck = $this->pdo->prepare($sqlCheck);
            $stmtCheck->execute($paramsCheck);
            if ($stmtCheck->fetch()) {
                $this->json(['error' => 'Este e-mail já está em uso.'], 400);
                return;
            }

            // EDICÃO
            if (!empty($data['id'])) {
                $userId = $data['id'];
                
                $sql = "UPDATE saas_users SET name=?, email=?, role_id=?, customer_id=?, status=?";
                $params = [$data['name'], $data['email'], $roleId, $customerId, $status];

                if (!empty($data['password'])) {
                    $sql .= ", password=?";
                    $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
                }

                $sql .= " WHERE id=? AND tenant_id=?";
                $params[] = $userId;
                $params[] = $tenantId;

                $this->pdo->prepare($sql)->execute($params);

            } else {
                // CRIAÇÃO
                if (empty($data['password'])) {
                    $this->json(['error' => 'Senha é obrigatória para novos usuários.'], 400);
                    return;
                }

                $sql = "INSERT INTO saas_users (tenant_id, name, email, password, role_id, customer_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $params = [
                    $tenantId, 
                    $data['name'], 
                    $data['email'], 
                    password_hash($data['password'], PASSWORD_DEFAULT),
                    $roleId,
                    $customerId,
                    $status
                ];
                $this->pdo->prepare($sql)->execute($params);
            }

            $this->json(['success' => true]);

        } catch (Exception $e) {
            error_log("Erro Store User: " . $e->getMessage());
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function delete() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $userId = $data['id'];
            $tenantId = $_SESSION['tenant_id'];

            if ($userId == $_SESSION['user_id']) {
                $this->json(['error' => 'Você não pode excluir seu próprio usuário.'], 403);
                return;
            }

            $stmt = $this->pdo->prepare("DELETE FROM saas_users WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$userId, $tenantId]);

            $this->json(['success' => true]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}