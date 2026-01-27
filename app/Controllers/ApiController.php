<?php
namespace App\Controllers;

use App\Config\Database;

class ApiController {
    
    protected $pdo;
    protected $tenant_id;
    protected $user_id;
    protected $user_role;

    public function __construct() {
        // 1. Conecta ao Banco
        $this->pdo = Database::getConnection();

        // 2. Carrega dados da sessão (se existir), mas NÃO BLOQUEIA NADA AQUI
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->tenant_id = $_SESSION['tenant_id'] ?? null;
        $this->user_id   = $_SESSION['user_id'] ?? null;
        $this->user_role = $_SESSION['user_role'] ?? 'guest';
        
        // REMOVIDO: if (!isset($_SESSION['user_id'])) { ... }
        // O AuthMiddleware já fez essa verificação antes de chamar o Controller.
    }

    protected function json($data, $status = 200) {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }

    protected function getRestrictionSQL($alias = 'v') {
        if ($this->user_role === 'superadmin' && !$this->tenant_id) {
            return " "; 
        }
        return " AND {$alias}.tenant_id = " . intval($this->tenant_id) . " ";
    }
}