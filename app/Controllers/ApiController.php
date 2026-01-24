<?php
namespace App\Controllers;

use App\Config\Database;
use Exception;

class ApiController {
    protected $pdo;
    protected $tenant_id;

    public function __construct() {
        // Garante sessão
        if (session_status() === PHP_SESSION_NONE) session_start();

        // 1. Verifica Login
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Sessão expirada']);
            exit;
        }

        // 2. Conecta ao Banco
        try {
            $this->pdo = Database::getConnection();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database connection failed']);
            exit;
        }

        // 3. Define Tenant ID
        // Se for Superadmin na rota /admin, usa o tenant_id da sessão (do login)
        // Se for usuário comum, usa o tenant_id da sessão também.
        // A segurança está no fato de que o tenant_id vem do banco no AuthController.
        $this->tenant_id = $_SESSION['tenant_id'];
    }

    protected function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}