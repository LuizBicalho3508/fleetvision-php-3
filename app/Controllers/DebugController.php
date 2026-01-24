<?php
namespace App\Controllers;

use App\Config\Database;
use PDO;
use Exception;

class DebugController extends ApiController {

    public function __construct() {
        // Sobrescreve o construtor para não exigir sessão neste teste específico
        // ou mantenha parent::__construct() se quiser exigir login
        if (session_status() === PHP_SESSION_NONE) session_start();
        try {
            $this->pdo = Database::getConnection();
        } catch (Exception $e) {
            $this->pdo = null;
        }
    }

    public function status() {
        $dbStatus = false;
        $dbError = null;
        $tenantCount = 0;

        // Teste de Banco
        if ($this->pdo) {
            try {
                $dbStatus = true;
                $tenantCount = $this->pdo->query("SELECT COUNT(*) FROM saas_tenants")->fetchColumn();
            } catch (Exception $e) {
                $dbStatus = false;
                $dbError = $e->getMessage();
            }
        }

        // Dados da Sessão
        $sessionData = [
            'tenant_slug' => $_SESSION['tenant_slug'] ?? 'Não definido',
            'user_id' => $_SESSION['user_id'] ?? 'Não logado',
            'role' => $_SESSION['user_role'] ?? '---'
        ];

        $this->json([
            'status' => 'online',
            'timestamp' => date('Y-m-d H:i:s'),
            'php_version' => phpversion(),
            'database' => [
                'connected' => $dbStatus,
                'error' => $dbError,
                'tenants_count' => $tenantCount
            ],
            'session' => $sessionData
        ]);
    }
}