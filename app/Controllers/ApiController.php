<?php
namespace App\Controllers;

use App\Config\Database;

class ApiController {
    protected $pdo;
    protected $tenant_id;
    protected $user_id;
    protected $user_role;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');

        $this->pdo = Database::getConnection();

        // Verificação Básica de Sessão
        if (!isset($_SESSION['user_id'])) {
            $this->json(['error' => 'Sessão expirada'], 403);
        }

        $this->tenant_id = $_SESSION['tenant_id'];
        $this->user_id = $_SESSION['user_id'];
        $this->user_role = $_SESSION['user_role'] ?? 'user';
    }

    protected function json($data, $status = 200) {
        http_response_code($status);
        echo json_encode($data);
        exit;
    }

    protected function getRestrictionSQL($alias = 'v') {
        if ($this->user_role === 'admin' || $this->user_role === 'superadmin') {
            return "";
        }

        // Lógica legada de cliente logado
        $stmt = $this->pdo->prepare("SELECT customer_id FROM saas_users WHERE id = ?");
        $stmt->execute([$this->user_id]);
        $custId = $stmt->fetchColumn();

        if ($custId) {
            return " AND ($alias.client_id = $custId OR $alias.user_id = {$this->user_id})";
        }
        return " AND $alias.user_id = {$this->user_id}";
    }
}