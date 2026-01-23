<?php
namespace App\Controllers;

use PDO;
use Exception;

class UserController extends ApiController {

    public function index() {
        try {
            $sql = "SELECT u.id, u.name, u.email, u.role_id, u.customer_id, u.branch_id, u.active, u.tenant_id,
                           r.name as role_name, b.name as branch_name, c.name as customer_name, t.name as tenant_name 
                    FROM saas_users u 
                    LEFT JOIN saas_roles r ON u.role_id = r.id 
                    LEFT JOIN saas_branches b ON u.branch_id = b.id 
                    LEFT JOIN saas_customers c ON u.customer_id = c.id
                    LEFT JOIN saas_tenants t ON u.tenant_id = t.id";

            if ($this->user_role === 'superadmin') {
                $sql .= " ORDER BY t.name ASC, u.name ASC";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute();
            } else {
                $sql .= " WHERE u.tenant_id = ? ORDER BY u.name ASC";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$this->tenant_id]);
            }
            $this->json($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store() {
        // Validação de permissão simplificada
        if ($this->user_role !== 'admin' && $this->user_role !== 'superadmin') {
            $this->json(['error' => 'Sem permissão'], 403);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $pass = $input['password'] ?? '';
        
        $role = !empty($input['role_id']) ? $input['role_id'] : null;
        $cust = !empty($input['customer_id']) ? $input['customer_id'] : null;
        $branch = !empty($input['branch_id']) ? $input['branch_id'] : null;
        $active = (isset($input['active']) && $input['active']) ? 1 : 0;
        
        $target_tenant_id = ($this->user_role === 'superadmin' && !empty($input['tenant_id'])) 
                            ? $input['tenant_id'] 
                            : $this->tenant_id;

        if (empty($name) || empty($email)) $this->json(['error' => 'Nome e Email obrigatórios'], 400);

        try {
            // Check duplicidade
            $checkSql = "SELECT id FROM saas_users WHERE email = ? AND tenant_id = ?";
            $checkParams = [$email, $target_tenant_id];
            if ($id) { $checkSql .= " AND id != ?"; $checkParams[] = $id; }
            
            $stmtCheck = $this->pdo->prepare($checkSql);
            $stmtCheck->execute($checkParams);
            if ($stmtCheck->fetch()) $this->json(['error' => 'Email já em uso'], 400);

            if ($id) {
                // Update
                $sql = "UPDATE saas_users SET name=?, email=?, role_id=?, customer_id=?, branch_id=?, active=?, tenant_id=? WHERE id=?";
                $params = [$name, $email, $role, $cust, $branch, $active, $target_tenant_id, $id];
                
                if ($this->user_role !== 'superadmin') { 
                    $sql .= " AND tenant_id=?"; 
                    $params[] = $this->tenant_id; 
                }
                
                if (!empty($pass)) { 
                    // Injeta atualização de senha no meio da query
                    $sql = str_replace('active=?,', 'active=?, password=?,', $sql); 
                    // Insere o hash antes dos parametros finais (splice manual)
                    $newParams = array_slice($params, 0, 6);
                    $newParams[] = password_hash($pass, PASSWORD_DEFAULT);
                    $newParams = array_merge($newParams, array_slice($params, 6));
                    $params = $newParams;
                }
                
                $this->pdo->prepare($sql)->execute($params);
            } else {
                // Insert
                if(empty($pass)) $this->json(['error' => 'Senha obrigatória'], 400);
                $stmt = $this->pdo->prepare("INSERT INTO saas_users (tenant_id, name, email, password, role_id, customer_id, branch_id, status, active) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?)");
                $stmt->execute([$target_tenant_id, $name, $email, password_hash($pass, PASSWORD_DEFAULT), $role, $cust, $branch, $active]);
            }
            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function delete() {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        
        if (!$id || $id == $this->user_id) $this->json(['error' => 'Operação inválida'], 400);

        try {
            if ($this->user_role === 'superadmin') {
                $this->pdo->prepare("DELETE FROM saas_users WHERE id = ?")->execute([$id]);
            } else {
                $this->pdo->prepare("DELETE FROM saas_users WHERE id = ? AND tenant_id = ?")->execute([$id, $this->tenant_id]);
            }
            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}