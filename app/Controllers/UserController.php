<?php
namespace App\Controllers;

use PDO;
use Exception;

class UserController extends ApiController {

    public function index() {
        // Carrega a view HTML
        $viewName = 'usuarios';
        if (file_exists(__DIR__ . '/../../views/layout.php')) {
            require __DIR__ . '/../../views/layout.php';
        } else {
            require __DIR__ . '/../../views/usuarios.php';
        }
    }

    // LISTAR USUÁRIOS
    public function list() {
        // Limpa buffer para evitar lixo no JSON
        if (ob_get_level()) ob_clean();

        try {
            if (session_status() === PHP_SESSION_NONE) session_start();

            if (!isset($_SESSION['tenant_id'])) {
                $this->json(['error' => 'Sessão expirada'], 401);
                return;
            }

            $tenantId = $_SESSION['tenant_id'];
            $userRole = strtolower($_SESSION['user_role'] ?? 'guest');
            $isSuperAdmin = ($userRole === 'superadmin');

            // 1. QUERY SEGURA
            // Removi last_login temporariamente para teste.
            // Usamos COALESCE para garantir que não venha NULL nos nomes.
            $sql = "SELECT 
                        u.id, 
                        COALESCE(u.name, 'Sem Nome') as name, 
                        COALESCE(u.email, 'sem@email.com') as email, 
                        u.status, 
                        u.role_id, 
                        u.customer_id, 
                        u.tenant_id,
                        COALESCE(r.name, '') as role_name,
                        COALESCE(c.name, '') as customer_name,
                        COALESCE(t.name, 'Empresa Desconhecida') as tenant_name
                    FROM saas_users u
                    LEFT JOIN saas_roles r ON u.role_id = r.id
                    LEFT JOIN saas_customers c ON u.customer_id = c.id
                    LEFT JOIN saas_tenants t ON u.tenant_id = t.id"; 

            $params = [];

            // 2. FILTRO
            if (!$isSuperAdmin) {
                $sql .= " WHERE u.tenant_id = ?";
                $params[] = $tenantId;
            }

            // 3. ORDENAÇÃO
            if ($isSuperAdmin) {
                $sql .= " ORDER BY u.tenant_id ASC, u.name ASC";
            } else {
                $sql .= " ORDER BY u.name ASC";
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 4. DADOS AUXILIARES (Dropdowns)
            $roles = [];
            $customers = [];

            // Carrega dropdowns apenas para o tenant atual (evita vazamento de dados de outras empresas para o superadmin na tela de edição)
            // Se for superadmin querendo editar, ele precisaria selecionar o tenant primeiro, mas para listagem isso basta.
            $stmtRoles = $this->pdo->prepare("SELECT id, name FROM saas_roles WHERE tenant_id = ? ORDER BY name");
            $stmtRoles->execute([$tenantId]);
            $roles = $stmtRoles->fetchAll(PDO::FETCH_ASSOC);

            $stmtCust = $this->pdo->prepare("SELECT id, name FROM saas_customers WHERE tenant_id = ? ORDER BY name");
            $stmtCust->execute([$tenantId]);
            $customers = $stmtCust->fetchAll(PDO::FETCH_ASSOC);

            // Retorno JSON
            $this->json([
                'users' => $users,
                'roles' => $roles,
                'customers' => $customers,
                'is_superadmin' => $isSuperAdmin
            ]);

        } catch (Exception $e) {
            // Em caso de erro, retorna JSON válido com o erro
            http_response_code(500);
            echo json_encode([
                'error' => 'Erro interno ao listar usuários.',
                'debug_message' => $e->getMessage()
            ]);
            exit;
        }
    }

    // SALVAR USUÁRIO
    public function store() {
        if (ob_get_level()) ob_clean();

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (session_status() === PHP_SESSION_NONE) session_start();
            $tenantId = $_SESSION['tenant_id'];

            if (empty($data['name']) || empty($data['email'])) {
                $this->json(['error' => 'Dados incompletos.'], 400);
                return;
            }

            // Normaliza Inputs
            $roleId = !empty($data['role_id']) ? $data['role_id'] : null;
            $customerId = !empty($data['customer_id']) ? $data['customer_id'] : null;
            $status = $data['status'] ?? 'active';

            // Verifica E-mail Duplicado
            $sqlCheck = "SELECT id FROM saas_users WHERE email = ? AND tenant_id = ?";
            $paramsCheck = [$data['email'], $tenantId];
            
            if (!empty($data['id'])) {
                $sqlCheck .= " AND id != ?";
                $paramsCheck[] = $data['id'];
            }
            
            $stmtCheck = $this->pdo->prepare($sqlCheck);
            $stmtCheck->execute($paramsCheck);
            if ($stmtCheck->fetch()) {
                $this->json(['error' => 'E-mail já cadastrado.'], 400);
                return;
            }

            // EDIÇÃO
            if (!empty($data['id'])) {
                $sql = "UPDATE saas_users SET name=?, email=?, role_id=?, customer_id=?, status=?";
                $params = [$data['name'], $data['email'], $roleId, $customerId, $status];

                if (!empty($data['password'])) {
                    $sql .= ", password=?";
                    $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
                }

                $sql .= " WHERE id=? AND tenant_id=?";
                $params[] = $data['id'];
                $params[] = $tenantId;

                $this->pdo->prepare($sql)->execute($params);
            } 
            // CRIAÇÃO
            else {
                if (empty($data['password'])) {
                    $this->json(['error' => 'Senha obrigatória.'], 400);
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
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }

    // EXCLUIR
    public function delete() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (session_status() === PHP_SESSION_NONE) session_start();
            $tenantId = $_SESSION['tenant_id'];

            if ($data['id'] == $_SESSION['user_id']) {
                $this->json(['error' => 'Não pode excluir a si mesmo.'], 403);
                return;
            }

            $this->pdo->prepare("DELETE FROM saas_users WHERE id = ? AND tenant_id = ?")->execute([$data['id'], $tenantId]);

            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}