<?php
namespace App\Controllers;

use App\Config\Database;
use PDO;
use Exception;

class AuthController extends ApiController {

    /**
     * Realiza o Login no sistema
     */
    public function login() {
        try {
            // 1. Detecta entrada de dados (JSON ou Form Data)
            $input = $_POST;
            if (empty($input)) {
                $json = json_decode(file_get_contents('php://input'), true);
                $input = $json ?? [];
            }

            $email = trim($input['email'] ?? '');
            $password = $input['password'] ?? '';
            $tenantSlug = trim($input['tenant_slug'] ?? 'admin');

            if (empty($email) || empty($password)) {
                $this->json(['error' => 'Email e senha são obrigatórios.'], 400);
                return;
            }

            // 2. Busca o Tenant (Ambiente)
            $stmt = $this->pdo->prepare("SELECT * FROM saas_tenants WHERE slug = ? LIMIT 1");
            $stmt->execute([$tenantSlug]);
            $tenant = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$tenant) {
                $this->json(['error' => 'Ambiente (Tenant) não encontrado.'], 404);
                return;
            }

            // 3. Busca o Usuário (Dentro do Tenant específico)
            // IMPORTANTE: Já buscamos o 'customer_id' e 'role_id' aqui
            $stmt = $this->pdo->prepare("SELECT * FROM saas_users WHERE email = ? AND tenant_id = ? LIMIT 1");
            $stmt->execute([$email, $tenant['id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // 4. Verifica Senha e Existência
            if (!$user || !password_verify($password, $user['password'])) {
                $this->json(['error' => 'Credenciais inválidas.'], 401);
                return;
            }

            // 5. Verifica Status
            if ($user['status'] !== 'active') {
                $this->json(['error' => 'Usuário inativo ou bloqueado. Contate o administrador.'], 403);
                return;
            }

            // 6. Configura a Sessão
            if (session_status() === PHP_SESSION_NONE) {
                // Define tempo de vida da sessão (24 horas)
                ini_set('session.gc_maxlifetime', 86400);
                session_set_cookie_params(86400);
                session_start();
            }

            // 7. Lógica de Permissões (Roles)
            $permissions = [];
            $roleName = 'Usuário';

            // Se for Super Admin (IDs Mágicos), dá permissão total
            if (in_array($user['id'], [1, 7])) {
                $permissions = ['*']; // Wildcard para acesso total
                $roleName = 'Super Admin';
            } 
            // Se for usuário normal, busca as permissões no banco
            elseif (!empty($user['role_id'])) {
                $stmtRole = $this->pdo->prepare("SELECT name, permissions FROM saas_roles WHERE id = ?");
                $stmtRole->execute([$user['role_id']]);
                $roleData = $stmtRole->fetch(PDO::FETCH_ASSOC);

                if ($roleData) {
                    $roleName = $roleData['name'];
                    // Decodifica o JSON salvo no banco (Ex: ["map_view", "dashboard_view"])
                    $decoded = json_decode($roleData['permissions'], true);
                    if (is_array($decoded)) {
                        $permissions = $decoded;
                    }
                }
            }

            // 8. POPULA A SESSÃO (CRÍTICO PARA O FUNCIONAMENTO)
            $_SESSION['user_id']          = $user['id'];
            $_SESSION['user_name']        = $user['name'];
            $_SESSION['user_email']       = $user['email'];
            
            // Controle de Acesso e Permissões
            $_SESSION['user_role']        = $roleName;
            $_SESSION['user_permissions'] = $permissions;
            
            // Controle de Escopo de Dados (Filtro por Cliente)
            // Se customer_id for NULL, ele é usuário interno (vê tudo do tenant)
            // Se tiver ID, ele é restrito aos dados desse cliente
            $_SESSION['customer_id']      = $user['customer_id'] ?? null; 

            // Contexto do Tenant
            $_SESSION['tenant_id']        = $tenant['id'];
            $_SESSION['tenant_slug']      = $tenant['slug'];
            $_SESSION['tenant_data']      = $tenant; // Cores, logo, configs

            // 9. Define Redirecionamento
            // Se for o painel admin geral, vai para /admin/dashboard
            // Se for tenant, vai para /slug-do-cliente/dashboard
            $baseSlug = ($tenant['slug'] === 'admin') ? 'admin' : $tenant['slug'];
            $redirect = "/{$baseSlug}/dashboard";

            // 10. Retorno Sucesso JSON
            $this->json([
                'success' => true,
                'redirect' => $redirect,
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'role' => $roleName,
                    'customer_id' => $user['customer_id'], // Retorna para debug se precisar
                    'permissions_count' => count($permissions)
                ]
            ]);

        } catch (Exception $e) {
            // Em produção, idealmente logar o erro em arquivo e retornar msg genérica
            $this->json(['error' => 'Erro interno ao realizar login: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Realiza o Logout e limpa a sessão
     */
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // Tenta manter o slug para redirecionar para o login correto
        $slug = $_SESSION['tenant_slug'] ?? 'admin';
        
        // Limpa todas as variáveis de sessão
        $_SESSION = [];
        
        // Destroi o cookie da sessão
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Destroi a sessão no servidor
        session_destroy();

        // Redireciona para a tela de login
        header("Location: /{$slug}/login");
        exit;
    }

    /**
     * Método auxiliar para verificar sessão ativa (opcional, para uso em SPA/AJAX)
     */
    public function check() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (isset($_SESSION['user_id'])) {
            $this->json([
                'authenticated' => true,
                'user' => [
                    'id' => $_SESSION['user_id'],
                    'name' => $_SESSION['user_name'],
                    'role' => $_SESSION['user_role'],
                    'customer_id' => $_SESSION['customer_id'] ?? null
                ]
            ]);
        } else {
            $this->json(['authenticated' => false], 401);
        }
    }
}
?>