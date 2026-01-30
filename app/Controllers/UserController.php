<?php
namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use PDO;
use Exception;

class UserController extends ApiController {

    public function index() {
        $this->render('usuarios');
    }

    public function list() {
        try {
            $tenantId = $_SESSION['tenant_id'];
            $isSuperAdmin = in_array($_SESSION['user_id'], [1, 7]);
            
            // Adiciona Paginação e Busca (Resumido para focar na lógica do cliente)
            $search = $_GET['search'] ?? '';
            $page = $_GET['page'] ?? 1;
            
            $sql = "SELECT u.id, u.name, u.email, u.status, u.role_id, u.tenant_id, u.customer_id,
                           COALESCE(r.name, 'Sem Perfil') as role_name,
                           COALESCE(c.name, '-') as customer_name,
                           COALESCE(t.name, 'N/A') as tenant_name
                    FROM saas_users u 
                    LEFT JOIN saas_roles r ON u.role_id = r.id 
                    LEFT JOIN saas_tenants t ON u.tenant_id = t.id 
                    LEFT JOIN saas_customers c ON u.customer_id = c.id
                    WHERE 1=1 ";

            $params = [];

            if (!$isSuperAdmin) {
                $sql .= " AND u.tenant_id = ?";
                $params[] = $tenantId;
            }

            if (!empty($search)) {
                $sql .= " AND (u.name ILIKE ? OR u.email ILIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            $sql .= " ORDER BY u.id DESC";

            // Executa
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // UTF-8 Clean
            array_walk_recursive($data, function(&$item) {
                if (is_string($item)) $item = mb_convert_encoding($item, 'UTF-8', 'UTF-8');
            });

            $this->json(['data' => $data, 'is_super_admin' => $isSuperAdmin]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $id = $input['id'] ?? null;
            $name = $input['name'] ?? '';
            $email = $input['email'] ?? '';
            $password = $input['password'] ?? '';
            $role_id = $input['role_id'] ?? null;
            $status = $input['status'] ?? 'active';
            
            // --- NOVO CAMPO: CUSTOMER_ID ---
            // Se vier vazio, salva como NULL (usuário interno da empresa)
            $customer_id = !empty($input['customer_id']) ? $input['customer_id'] : null;

            $tenantId = $_SESSION['tenant_id'];
            if (in_array($_SESSION['user_id'], [1, 7]) && !empty($input['tenant_id'])) {
                $tenantId = $input['tenant_id'];
            }

            if ($id) {
                // UPDATE
                $sql = "UPDATE saas_users SET name = ?, email = ?, role_id = ?, customer_id = ?, status = ? WHERE id = ?";
                $this->pdo->prepare($sql)->execute([$name, $email, $role_id, $customer_id, $status, $id]);

                if (!empty($password)) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $this->pdo->prepare("UPDATE saas_users SET password = ? WHERE id = ?")->execute([$hash, $id]);
                }
            } else {
                // INSERT
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO saas_users (tenant_id, name, email, password, role_id, customer_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $this->pdo->prepare($sql)->execute([$tenantId, $name, $email, $hash, $role_id, $customer_id, $status]);
            }

            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
    
    // ... delete() mantém igual ...
    public function delete() {
         try {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? null;
            $this->pdo->prepare("DELETE FROM saas_users WHERE id = ?")->execute([$id]);
            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}
?>