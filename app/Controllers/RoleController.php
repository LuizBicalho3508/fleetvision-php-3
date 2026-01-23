<?php
namespace App\Controllers;

use PDO;
use Exception;

class RoleController extends ApiController {

    public function index() {
        try {
            $target_tenant = ($this->user_role === 'superadmin' && isset($_GET['tenant_id'])) 
                             ? $_GET['tenant_id'] 
                             : $this->tenant_id;
            
            // Busca ID do tenant admin para roles globais
            $stmtAdmin = $this->pdo->prepare("SELECT id FROM saas_tenants WHERE slug = 'admin' LIMIT 1");
            $stmtAdmin->execute();
            $adminTenantId = $stmtAdmin->fetchColumn() ?: 0;

            $sql = "SELECT id, name, tenant_id FROM saas_roles WHERE tenant_id = ? OR tenant_id = ? ORDER BY tenant_id ASC, name ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$target_tenant, $adminTenantId]);
            $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Marca roles globais
            foreach($roles as &$r) { 
                if ($r['tenant_id'] != $target_tenant) $r['name'] .= ' (Global)'; 
            }
            
            $this->json($roles);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store() {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        $name = $input['name'] ?? '';
        $permissions = is_array($input['permissions'] ?? []) 
                       ? json_encode($input['permissions']) 
                       : ($input['permissions'] ?? '[]');

        if (empty($name)) $this->json(['error' => 'Nome obrigatório'], 400);

        try {
            if ($id) {
                $stmt = $this->pdo->prepare("UPDATE saas_roles SET name = ?, permissions = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?");
                $stmt->execute([$name, $permissions, $id, $this->tenant_id]);
            } else {
                $stmt = $this->pdo->prepare("INSERT INTO saas_roles (tenant_id, name, permissions) VALUES (?, ?, ?)");
                $stmt->execute([$this->tenant_id, $name, $permissions]);
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
            $stmt = $this->pdo->prepare("DELETE FROM saas_roles WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$id, $this->tenant_id]);
            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}