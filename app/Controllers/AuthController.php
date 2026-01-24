<?php
namespace App\Controllers;

use App\Config\Database;
use PDO;

class AuthController extends ApiController {

    public function __construct() {
        // AuthController não verifica sessão no construtor para evitar loop
        try {
            $this->pdo = Database::getConnection();
        } catch (\Exception $e) {
            $this->json(['error' => 'Database error'], 500);
        }
    }

    public function login() {
        $data = json_decode(file_get_contents("php://input"), true);
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $urlSlug = $data['slug'] ?? ''; // O slug que veio da URL de login

        if (!$email || !$password) {
            $this->json(['error' => 'Preencha todos os campos.'], 400);
            return;
        }

        // 1. Busca Usuário
        $stmt = $this->pdo->prepare("SELECT u.*, t.slug as tenant_slug, t.status as tenant_status 
                                     FROM saas_users u 
                                     JOIN saas_tenants t ON u.tenant_id = t.id 
                                     WHERE u.email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Validações
        if (!$user || !password_verify($password, $user['password'])) {
            $this->json(['error' => 'E-mail ou senha incorretos.'], 401);
            return;
        }

        if ($user['status'] !== 'active') {
            $this->json(['error' => 'Usuário inativo.'], 403);
            return;
        }

        if ($user['tenant_status'] !== 'active') {
            $this->json(['error' => 'Empresa bloqueada ou inativa.'], 403);
            return;
        }

        // 3. Salva na Sessão
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['tenant_id'] = $user['tenant_id'];
        $_SESSION['tenant_slug'] = $user['tenant_slug']; // Slug REAL do banco

        // Permissões (opcional, pode vir do banco)
        $_SESSION['user_permissions'] = ['dashboard', 'mapa', 'frota']; 
        if ($user['role'] === 'admin' || $user['role'] === 'superadmin') {
            $_SESSION['user_permissions'] = ['*'];
        }

        // 4. Define Redirecionamento Correto
        // Se for superadmin, pode ir para /admin, senão vai para o slug da empresa
        $redirectSlug = ($user['role'] === 'superadmin') ? 'admin' : $user['tenant_slug'];
        
        // Garante que a sessão seja escrita antes de responder
        session_write_close();

        $this->json([
            'success' => true,
            'redirect' => "/{$redirectSlug}/dashboard",
            'user' => [
                'name' => $user['name'],
                'role' => $user['role']
            ]
        ]);
    }

    public function logout() {
        session_destroy();
        header("Location: /login");
        exit;
    }
    
    // ... métodos recover/reset mantidos simples ...
    public function forgotPassword() { $this->json(['success'=>true]); }
    public function resetPassword() { $this->json(['success'=>true]); }
}