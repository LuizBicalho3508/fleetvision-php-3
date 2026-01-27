<?php
namespace App\Controllers;

use PDO;
use Exception;

class AuthController extends ApiController {

    public function login() {
        try {
            // Recebe JSON
            $input = json_decode(file_get_contents('php://input'), true);
            $email = $input['email'] ?? '';
            $password = $input['password'] ?? '';
            
            // Slug vindo da URL (opcional, fallback)
            $slugFromUrl = $input['slug'] ?? 'admin';

            if (empty($email) || empty($password)) {
                $this->json(['error' => 'Preencha e-mail e senha.'], 400);
                return;
            }

            // 1. Busca Usuário e Status do Tenant
            // ADICIONADO: u.customer_id (Crucial para o Isolamento de Dados)
            $sql = "SELECT u.id, u.name, u.password, u.role, u.tenant_id, u.status, u.role_id, u.customer_id,
                           t.status as tenant_status 
                    FROM saas_users u 
                    JOIN saas_tenants t ON u.tenant_id = t.id 
                    WHERE u.email = ? LIMIT 1";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // 2. Validações de Segurança
            if (!$user || !password_verify($password, $user['password'])) {
                // Delay artificial para evitar Brute Force (Timing Attack)
                usleep(300000); // 300ms
                $this->json(['error' => 'Credenciais inválidas.'], 401);
                return;
            }

            if ($user['status'] !== 'active') {
                $this->json(['error' => 'Usuário inativo.'], 403);
                return;
            }

            if ($user['tenant_status'] !== 'active') {
                $this->json(['error' => 'Acesso da empresa suspenso.'], 403);
                return;
            }

            // 3. Inicia Sessão
            if (session_status() === PHP_SESSION_NONE) session_start();
            session_regenerate_id(true); // Previne Session Fixation

            // 4. Carrega Dados do Tenant (Visual e Config)
            $stmtTenant = $this->pdo->prepare("
                SELECT id, name, slug, logo_url, primary_color, secondary_color 
                FROM saas_tenants WHERE id = ? LIMIT 1
            ");
            $stmtTenant->execute([$user['tenant_id']]);
            $tenantData = $stmtTenant->fetch(PDO::FETCH_ASSOC);

            if (!$tenantData) {
                $this->json(['error' => 'Erro estrutural: Tenant não encontrado.'], 500);
                return;
            }

            // 5. CARREGA PERMISSÕES (ACL)
            $permissions = [];
            
            // Se for usuário normal (não superadmin) e tiver um perfil, busca as permissões
            if ($user['role'] !== 'superadmin' && !empty($user['role_id'])) {
                $stmtPerms = $this->pdo->prepare("SELECT permission_slug FROM saas_role_permissions WHERE role_id = ?");
                $stmtPerms->execute([$user['role_id']]);
                $permissions = $stmtPerms->fetchAll(PDO::FETCH_COLUMN);
            }

            // 6. Preenche a Sessão
            $_SESSION['user_id']          = $user['id'];
            $_SESSION['user_name']        = $user['name'];
            $_SESSION['user_role']        = $user['role'];      // 'admin', 'user', 'superadmin'
            $_SESSION['user_role_id']     = $user['role_id'];   // ID do perfil personalizado
            $_SESSION['user_permissions'] = $permissions;       // Array de slugs (ex: ['vehicles_view', 'map_view'])
            
            // --- NOVO: ISOLAMENTO DE DADOS ---
            // Salva o ID do cliente vinculado. Se for NULL, o usuário é "Global" (vê tudo).
            $_SESSION['user_customer_id'] = $user['customer_id']; 

            // Dados do Tenant
            $_SESSION['tenant_id']        = $tenantData['id'];
            $_SESSION['tenant_name']      = $tenantData['name'];
            $_SESSION['tenant_slug']      = $tenantData['slug'];
            $_SESSION['tenant_data']      = $tenantData;        // Cores e logo para o layout

            // 7. Retorno Sucesso
            $this->json([
                'success' => true, 
                'redirect' => "/{$tenantData['slug']}/dashboard"
            ]);

        } catch (Exception $e) {
            // Log do erro real no servidor (não exibe para o usuário)
            error_log("Erro Login: " . $e->getMessage());
            $this->json(['error' => 'Erro interno no servidor.'], 500);
        }
    }

    public function logout() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // Tenta descobrir o slug para redirecionar corretamente
        $slug = $_SESSION['tenant_slug'] ?? 'admin';
        
        // Destrói tudo
        $_SESSION = [];
        session_destroy();
        
        // Redireciona
        header("Location: /$slug/login");
        exit;
    }

    // Funcionalidades futuras
    public function forgotPassword() {
        $this->json(['error' => 'Funcionalidade em desenvolvimento.'], 501);
    }
}