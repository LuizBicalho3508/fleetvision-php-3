<?php
namespace App\Middleware;

use App\Config\Database;
use PDO;

class AuthMiddleware {

    public static function handle() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $uri = $_SERVER['REQUEST_URI'];
        $path = parse_url($uri, PHP_URL_PATH);
        $path = trim($path, '/');
        $parts = explode('/', $path);

        // --- EXCEÇÕES ---
        if (strpos($path, 'api/webhook') === 0) return;
        if (strpos($path, 'api/cron') === 0) return;
        if (strpos($path, 'sys/auth') === 0) return; 
        if (strpos($path, 'sys/debug') === 0) return;

        $publicPages = ['login', 'recover', 'reset', 'landing', 'assets', 'uploads', 'favicon.ico'];
        $segmentToCheck = (isset($parts[1]) && !in_array($parts[0], ['sys', 'api'])) ? $parts[1] : $parts[0];
        
        // Carrega contexto mesmo em páginas públicas (para mostrar logo/cores)
        if (in_array($segmentToCheck, $publicPages) || empty($path)) {
            $slugCandidate = (isset($parts[0]) && !in_array($parts[0], ['sys', 'login', 'landing'])) ? $parts[0] : 'admin';
            self::loadTenantContext($slugCandidate);
            return;
        }

        // --- PROTEÇÃO ---
        $slug = $parts[0] ?? 'admin';
        if ($slug === 'sys' || $slug === 'api') {
            if (!isset($_SESSION['user_id'])) {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['error' => 'Sessão expirada.']);
                exit;
            }
            return; 
        }

        $tenant = self::loadTenantContext($slug);

        if (!isset($_SESSION['user_id'])) {
            $redirectSlug = $tenant['slug'] ?? 'admin';
            header("Location: /{$redirectSlug}/login");
            exit;
        }

        // Bloqueio Financeiro
        $userRole = $_SESSION['user_role'] ?? 'user';
        if ($userRole !== 'superadmin') {
            $pdo = Database::getConnection();
            $sql = "SELECT c.financial_status, c.name FROM saas_users u JOIN saas_customers c ON u.customer_id = c.id WHERE u.id = ? AND u.tenant_id = ? LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_SESSION['user_id'], $tenant['id']]);
            $customerData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($customerData && ($customerData['financial_status'] === 'overdue' || $customerData['financial_status'] === 'blocked')) {
                if ($segmentToCheck !== 'logout') self::renderBlockScreen($customerData['name']);
            }
        }
    }

    private static function loadTenantContext($slugCandidate) {
        // [OPCIONAL] Removemos o cache de sessão para garantir que as alterações reflitam imediatamente
        // if (isset($_SESSION['tenant_slug']) ... ) return ...; 

        $pdo = Database::getConnection();
        
        // Tenta buscar com as colunas novas. Se der erro (coluna não existe), busca com as antigas.
        try {
            $sql = "SELECT id, name, slug, logo_url, primary_color, secondary_color, 
                           login_bg_url, login_opacity, login_btn_color, login_title, login_subtitle,
                           sidebar_color, login_card_color
                    FROM saas_tenants WHERE slug = ? LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$slugCandidate]);
            $tenant = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            // Fallback se as colunas novas ainda não existirem no banco
            $sql = "SELECT id, name, slug, logo_url, primary_color, secondary_color, 
                           login_bg_url, login_opacity, login_btn_color, login_title, login_subtitle
                    FROM saas_tenants WHERE slug = ? LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$slugCandidate]);
            $tenant = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // Fallback Padrão
        if (!$tenant) {
            $stmt->execute(['admin']);
            $tenant = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$tenant) {
                $tenant = [
                    'id' => 1, 'name' => 'FleetVision', 'slug' => 'admin',
                    'primary_color' => '#2563eb', 'login_title' => 'Bem-vindo',
                    'sidebar_color' => '#ffffff', 'login_card_color' => '#ffffff'
                ];
            }
        }

        // [IMPORTANTE] Removemos a manipulação de caminho daqui. 
        // Deixamos o View (login.php e sidebar.php) lidar com get_safe_url.
        // Isso evita que '/uploads' vire '//uploads' ou '/fleetvision/fleetvision/uploads'.

        $_SESSION['tenant_id']   = $tenant['id'];
        $_SESSION['tenant_data'] = $tenant;
        $_SESSION['tenant_slug'] = $tenant['slug'];

        return $tenant;
    }

    private static function renderBlockScreen($customerName) {
        http_response_code(403);
        $slug = $_SESSION['tenant_slug'] ?? 'admin';
        echo "<h1>Acesso Suspenso para $customerName</h1><a href='/$slug/logout'>Sair</a>";
        exit;
    }
}
?>