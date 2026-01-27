<?php
namespace App\Controllers;

use PDO;
use Exception;

class RoleController extends ApiController {

    // Definição Central das Permissões do Sistema
    // Aqui você mapeia tudo o que existe no seu software
    private $modules = [
        'dashboard'  => ['label' => 'Dashboard',       'actions' => ['view']],
        'map'        => ['label' => 'Mapa em Tempo Real', 'actions' => ['view']],
        'vehicles'   => ['label' => 'Veículos',        'actions' => ['view', 'create', 'edit', 'delete']],
        'drivers'    => ['label' => 'Motoristas',      'actions' => ['view', 'create', 'edit', 'delete']],
        'journey'    => ['label' => 'Jornada',         'actions' => ['view', 'export']],
        'stock'      => ['label' => 'Estoque',         'actions' => ['view', 'create', 'edit', 'delete']],
        'alerts'     => ['label' => 'Alertas',         'actions' => ['view', 'config']],
        'customers'  => ['label' => 'Clientes',        'actions' => ['view', 'create', 'edit', 'delete']],
        'users'      => ['label' => 'Usuários',        'actions' => ['view', 'create', 'edit', 'delete']],
        'financial'  => ['label' => 'Financeiro',      'actions' => ['view', 'create', 'edit', 'delete']],
        'reports'    => ['label' => 'Relatórios',      'actions' => ['view', 'export']]
    ];

    public function index() {
        $viewName = 'perfis';
        if (file_exists(__DIR__ . '/../../views/layout.php')) {
            require __DIR__ . '/../../views/layout.php';
        } else {
            require __DIR__ . '/../../views/perfis.php';
        }
    }

    // API: Lista Perfis e Definições
    public function list() {
        try {
            $tenantId = $_SESSION['tenant_id'];

            // Busca Perfis
            $stmt = $this->pdo->prepare("SELECT * FROM saas_roles WHERE tenant_id = ? ORDER BY id ASC");
            $stmt->execute([$tenantId]);
            $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Conta usuários por perfil
            foreach($roles as &$role) {
                $stmtCount = $this->pdo->prepare("SELECT COUNT(*) FROM saas_users WHERE role_id = ?");
                $stmtCount->execute([$role['id']]);
                $role['users_count'] = $stmtCount->fetchColumn();
            }

            $this->json([
                'roles' => $roles,
                'definitions' => $this->modules // Envia a estrutura para o frontend montar a tabela
            ]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // API: Busca Permissões de um Perfil Específico
    public function getPermissions() {
        try {
            $roleId = $_GET['id'];
            $stmt = $this->pdo->prepare("SELECT permission_slug FROM saas_role_permissions WHERE role_id = ?");
            $stmt->execute([$roleId]);
            $perms = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $this->json(['permissions' => $perms]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // API: Salvar (Criar ou Editar)
    public function store() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $tenantId = $_SESSION['tenant_id'];
            
            if (empty($data['name'])) {
                $this->json(['error' => 'Nome do perfil é obrigatório.'], 400);
                return;
            }

            $this->pdo->beginTransaction();

            if (!empty($data['id'])) {
                // Edição
                $roleId = $data['id'];
                
                // Proteção: Não deixa editar nome do Admin do Sistema se quiser travar
                // $stmtCheck = $this->pdo->prepare("SELECT is_system FROM saas_roles WHERE id = ?"); ...

                $stmt = $this->pdo->prepare("UPDATE saas_roles SET name = ?, description = ? WHERE id = ? AND tenant_id = ?");
                $stmt->execute([$data['name'], $data['description'] ?? '', $roleId, $tenantId]);

                // Limpa permissões antigas para recriar
                $this->pdo->prepare("DELETE FROM saas_role_permissions WHERE role_id = ?")->execute([$roleId]);

            } else {
                // Criação
                $stmt = $this->pdo->prepare("INSERT INTO saas_roles (tenant_id, name, description) VALUES (?, ?, ?) RETURNING id");
                $stmt->execute([$tenantId, $data['name'], $data['description'] ?? '']);
                $roleId = $stmt->fetchColumn();
            }

            // Insere Novas Permissões
            if (!empty($data['permissions']) && is_array($data['permissions'])) {
                $ins = $this->pdo->prepare("INSERT INTO saas_role_permissions (role_id, permission_slug) VALUES (?, ?)");
                foreach ($data['permissions'] as $perm) {
                    $ins->execute([$roleId, $perm]);
                }
            }

            $this->pdo->commit();
            $this->json(['success' => true]);

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // API: Excluir
    public function delete() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $roleId = $data['id'];

            // Verifica se é sistema
            $stmt = $this->pdo->prepare("SELECT is_system FROM saas_roles WHERE id = ?");
            $stmt->execute([$roleId]);
            if ($stmt->fetchColumn()) {
                $this->json(['error' => 'Perfis de sistema não podem ser excluídos.'], 403);
                return;
            }

            // Verifica se tem usuários
            $stmtCount = $this->pdo->prepare("SELECT COUNT(*) FROM saas_users WHERE role_id = ?");
            $stmtCount->execute([$roleId]);
            if ($stmtCount->fetchColumn() > 0) {
                $this->json(['error' => 'Existem usuários vinculados a este perfil. Migre-os antes de excluir.'], 400);
                return;
            }

            $this->pdo->prepare("DELETE FROM saas_roles WHERE id = ?")->execute([$roleId]);
            $this->json(['success' => true]);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}