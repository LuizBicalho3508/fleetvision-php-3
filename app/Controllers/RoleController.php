<?php
namespace App\Controllers;

use PDO;
use Exception;

class RoleController extends ApiController {

    public function index() {
        $this->render('perfis');
    }

    // LISTAR PERFIS
    public function list() {
        try {
            $tenantId = $_SESSION['tenant_id'];

            // Se for Super Admin (1 ou 7) e passar ?tenant_id=X, lista daquele tenant
            if (in_array($_SESSION['user_id'], [1, 7]) && !empty($_GET['tenant_id'])) {
                $tenantId = $_GET['tenant_id'];
            }

            // Busca perfis + contagem de usuários
            $sql = "SELECT r.*, 
                           (SELECT COUNT(*) FROM saas_users u WHERE u.role_id = r.id) as user_count 
                    FROM saas_roles r 
                    WHERE r.tenant_id = ? 
                    ORDER BY r.id DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$tenantId]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Decodifica o JSON de permissões para o frontend ler
            foreach ($data as &$role) {
                $role['permissions'] = json_decode($role['permissions'], true) ?? [];
            }

            $this->json(['data' => $data]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // SALVAR PERFIL (Criação ou Edição)
    public function store() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $id = $input['id'] ?? null;
            $name = $input['name'] ?? '';
            $desc = $input['description'] ?? '';
            $perms = $input['permissions'] ?? []; // Array de strings
            
            // Validação
            if (empty($name)) {
                $this->json(['error' => 'Nome do perfil é obrigatório'], 400);
                return;
            }

            // Tenant: Se for Super Admin criando para outro, usa o ID enviado, senão usa sessão
            $tenantId = $_SESSION['tenant_id'];
            if (in_array($_SESSION['user_id'], [1, 7]) && !empty($input['tenant_id'])) {
                $tenantId = $input['tenant_id'];
            }

            // Converte array para JSON para salvar no banco
            $permsJson = json_encode($perms);

            if ($id) {
                // UPDATE
                $sql = "UPDATE saas_roles SET name = ?, description = ?, permissions = ? WHERE id = ?";
                // Nota: Super Admin pode editar role de qualquer um, Admin Comum só do seu tenant
                // Adicionei validação de tenant_id no WHERE para segurança extra se não for superadmin
                if (!in_array($_SESSION['user_id'], [1, 7])) {
                    $sql .= " AND tenant_id = " . intval($_SESSION['tenant_id']);
                }
                $this->pdo->prepare($sql)->execute([$name, $desc, $permsJson, $id]);
            } else {
                // INSERT
                $sql = "INSERT INTO saas_roles (tenant_id, name, description, permissions) VALUES (?, ?, ?, ?)";
                $this->pdo->prepare($sql)->execute([$tenantId, $name, $desc, $permsJson]);
            }

            $this->json(['success' => true]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // DELETAR PERFIL
    public function delete() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? null;

            if (!$id) { $this->json(['error' => 'ID inválido'], 400); return; }

            // Verifica se tem usuários vinculados
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM saas_users WHERE role_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                $this->json(['error' => 'Não é possível excluir: Existem usuários vinculados a este perfil.'], 400);
                return;
            }

            // Delete
            if (in_array($_SESSION['user_id'], [1, 7])) {
                $this->pdo->prepare("DELETE FROM saas_roles WHERE id = ?")->execute([$id]);
            } else {
                $this->pdo->prepare("DELETE FROM saas_roles WHERE id = ? AND tenant_id = ?")->execute([$id, $_SESSION['tenant_id']]);
            }

            $this->json(['success' => true]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}
?>