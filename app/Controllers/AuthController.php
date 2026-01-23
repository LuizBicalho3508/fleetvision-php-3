<?php
namespace App\Controllers;

use App\Config\Database;
use PDO;
use Exception;

class AuthController extends ApiController {

    public function __construct() {
        // Garante que a sessão esteja iniciada para manipular login/logout
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->pdo = Database::getConnection();
    }

    /**
     * Efetua o Login do Usuário.
     * CORREÇÃO: Faz JOIN com roles para salvar o nome do cargo na sessão.
     */
    public function login() {
        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        $tenantSlug = $input['slug'] ?? 'admin';

        if (empty($email) || empty($password)) {
            $this->json(['error' => 'Preencha e-mail e senha.'], 400);
            return;
        }

        try {
            // 1. Busca Usuário + Nome do Cargo + ID do Tenant
            // O JOIN em saas_roles é vital para mostrar 'Admin' ou 'Superadmin' na Topbar
            $sql = "SELECT u.id, u.tenant_id, u.name, u.email, u.password, u.avatar, u.role_id, 
                           r.name as role_name,
                           t.slug as db_slug
                    FROM saas_users u
                    LEFT JOIN saas_roles r ON u.role_id = r.id
                    LEFT JOIN saas_tenants t ON u.tenant_id = t.id
                    WHERE u.email = ? AND u.status = 'active' LIMIT 1";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // 2. Valida Senha
            if ($user && password_verify($password, $user['password'])) {
                
                // --- DEFINIÇÃO DA SESSÃO (CRUCIAL PARA O LAYOUT) ---
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];       // Resolve o "Visitante"
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_avatar'] = $user['avatar'];
                $_SESSION['tenant_id'] = $user['tenant_id'];
                
                // Normaliza o cargo para minúsculo (ex: 'Admin' -> 'admin') para facilitar checagens
                // Se role_name for nulo, assume 'user'
                $_SESSION['user_role'] = strtolower($user['role_name'] ?? 'user'); 
                
                // Define o slug correto (se o user tentou logar em /admin/login mas ele é da /empresaX/)
                $_SESSION['tenant_slug'] = $user['db_slug'] ?? $tenantSlug;

                // Busca permissões específicas deste perfil e salva na sessão (Cache de Permissão)
                if ($user['role_id']) {
                    $stmtPerms = $this->pdo->prepare("SELECT permissions FROM saas_roles WHERE id = ?");
                    $stmtPerms->execute([$user['role_id']]);
                    $pJson = $stmtPerms->fetchColumn();
                    $_SESSION['user_permissions'] = $pJson ? json_decode($pJson, true) : [];
                } else {
                    $_SESSION['user_permissions'] = [];
                }

                $this->json(['success' => true, 'redirect' => "/{$_SESSION['tenant_slug']}/dashboard"]);
            } else {
                $this->json(['error' => 'E-mail ou senha incorretos.'], 401);
            }
        } catch (Exception $e) {
            $this->json(['error' => 'Erro interno ao processar login.'], 500);
        }
    }

    /**
     * Efetua o Logout e redireciona.
     */
    public function logout() {
        // Guarda o slug para saber para onde redirecionar o login
        $slug = $_SESSION['tenant_slug'] ?? 'admin';
        
        // Limpa tudo
        session_unset();
        session_destroy();
        
        // Redireciona via HTTP (pois é acessado via GET /logout)
        header("Location: /$slug/login");
        exit;
    }

    /**
     * Solicita recuperação de senha (Gera Token e Envia E-mail).
     */
    public function forgotPassword() {
        $input = json_decode(file_get_contents('php://input'), true);
        $email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $tenantSlug = $input['slug'] ?? 'admin';

        if (!$email) {
            $this->json(['error' => 'E-mail inválido'], 400);
            return;
        }

        try {
            $stmt = $this->pdo->prepare("SELECT id, name FROM saas_users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Gera Token
                $token = bin2hex(random_bytes(32));

                // Limpa tokens antigos
                $this->pdo->prepare("DELETE FROM saas_password_resets WHERE email = ?")->execute([$email]);
                
                // Insere novo token
                $stmtInsert = $this->pdo->prepare("INSERT INTO saas_password_resets (email, token, created_at) VALUES (?, ?, NOW())");
                $stmtInsert->execute([$email, $token]);

                // Envia E-mail (Simulado)
                $this->sendRecoveryEmail($user['name'], $email, $token, $tenantSlug);
            }

            $this->json(['success' => true, 'message' => 'Se o e-mail existir, enviamos as instruções.']);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Redefine a senha usando o Token.
     */
    public function resetPassword() {
        $input = json_decode(file_get_contents('php://input'), true);
        $token = $input['token'] ?? '';
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        $confirm = $input['confirm_password'] ?? '';

        if (empty($token) || empty($email) || empty($password)) {
            $this->json(['error' => 'Dados incompletos.'], 400);
            return;
        }

        if ($password !== $confirm) {
            $this->json(['error' => 'As senhas não conferem.'], 400);
            return;
        }

        if (strlen($password) < 6) {
            $this->json(['error' => 'A senha deve ter no mínimo 6 caracteres.'], 400);
            return;
        }

        try {
            // Verifica Token e Validade (24h) - Compatível com PostgreSQL e MySQL
            $sql = "SELECT created_at FROM saas_password_resets 
                    WHERE email = ? AND token = ? 
                    AND created_at > NOW() - INTERVAL '24 hours' 
                    LIMIT 1";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$email, $token]);
            
            if (!$stmt->fetch()) {
                $this->json(['error' => 'Link inválido ou expirado.'], 400);
                return;
            }

            // Atualiza Senha
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $stmtUpdate = $this->pdo->prepare("UPDATE saas_users SET password = ? WHERE email = ?");
            $stmtUpdate->execute([$newHash, $email]);

            // Deleta Token usado
            $this->pdo->prepare("DELETE FROM saas_password_resets WHERE email = ?")->execute([$email]);

            $this->json(['success' => true, 'message' => 'Senha alterada com sucesso!']);

        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper de Envio de E-mail (Simulação)
     */
    private function sendRecoveryEmail($name, $email, $token, $slug) {
        $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        
        // Link aponta para a view de reset
        $link = "$protocol://$domain/$slug/reset?token=$token&email=$email";

        $subject = "Recuperação de Senha - FleetVision";
        $message = "Olá $name,\n\nClique no link para redefinir sua senha:\n$link\n\nIgnorar se não solicitou.";
        $headers = "From: no-reply@$domain";

        // Em produção, use PHPMailer
        @mail($email, $subject, $message, $headers);
    }
}