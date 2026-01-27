<?php
namespace App\Middleware;

use App\Config\Database;
use PDO;

class AuthMiddleware {

    public static function handle() {
        // 1. Inicia Sessão
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $uri = $_SERVER['REQUEST_URI'];
        $path = parse_url($uri, PHP_URL_PATH);
        $path = trim($path, '/');
        $parts = explode('/', $path);

        // ====================================================================
        // 2. EXCEÇÕES GLOBAIS (Rotas Públicas e APIs do Sistema)
        // ====================================================================
        
        // Webhooks (Asaas, Cron) - Acesso Externo
        if (strpos($path, 'api/webhook') === 0) return;
        if (strpos($path, 'api/cron') === 0) return;

        // APIs Internas Críticas (Login e Debug)
        if (strpos($path, 'sys/auth') === 0) return; 
        if (strpos($path, 'sys/debug') === 0) return;

        // Páginas Públicas do Frontend (Login, Assets)
        $publicPages = ['login', 'recover', 'reset', 'landing', 'assets', 'uploads', 'favicon.ico'];
        
        // Verifica qual é a "ação" da URL (ex: /cliente/LOGIN)
        // Se o primeiro segmento não for 'sys', o segundo segmento costuma ser a ação.
        $segmentToCheck = (isset($parts[1]) && !in_array($parts[0], ['sys', 'api'])) ? $parts[1] : $parts[0];
        
        // SE FOR PÁGINA PÚBLICA:
        // Carregamos o Tenant (para mostrar logo/cores) mas NÃO bloqueamos o acesso.
        if (in_array($segmentToCheck, $publicPages) || empty($path)) {
            // Tenta adivinhar o slug pela URL
            $slugCandidate = (isset($parts[0]) && !in_array($parts[0], ['sys', 'login', 'landing'])) ? $parts[0] : 'admin';
            self::loadTenantContext($slugCandidate);
            return;
        }

        // ====================================================================
        // 3. IDENTIFICAÇÃO E BLOQUEIO (Rotas Protegidas)
        // ====================================================================
        
        $slug = $parts[0] ?? 'admin';
        
        // Ignora rotas de API interna se a sessão já foi validada pelo Controller
        if ($slug === 'sys' || $slug === 'api') {
            if (!isset($_SESSION['user_id'])) {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['error' => 'Sessão expirada.']);
                exit;
            }
            return; 
        }

        // Carrega o Tenant na Sessão
        $tenant = self::loadTenantContext($slug);

        // 4. VERIFICAÇÃO DE LOGIN
        if (!isset($_SESSION['user_id'])) {
            $redirectSlug = $tenant['slug'] ?? 'admin';
            header("Location: /{$redirectSlug}/login");
            exit;
        }

        // 5. BLOQUEIO FINANCEIRO (SaaS)
        $userRole = $_SESSION['user_role'] ?? 'user';
        
        if ($userRole !== 'superadmin') {
            $pdo = Database::getConnection();
            // Verifica status financeiro do cliente dono do usuário
            $sql = "SELECT c.financial_status, c.name 
                    FROM saas_users u 
                    JOIN saas_customers c ON u.customer_id = c.id 
                    WHERE u.id = ? AND u.tenant_id = ? 
                    LIMIT 1";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_SESSION['user_id'], $tenant['id']]);
            $customerData = $stmt->fetch(PDO::FETCH_ASSOC);

            // Bloqueia se estiver vencido ou bloqueado (exceto logout)
            if ($customerData && 
               ($customerData['financial_status'] === 'overdue' || $customerData['financial_status'] === 'blocked')) {
                if ($segmentToCheck !== 'logout') {
                    self::renderBlockScreen($customerData['name']);
                }
            }
        }
    }

    /**
     * Carrega dados do Tenant e Salva na Sessão
     * (Inclui personalização visual completa)
     */
    private static function loadTenantContext($slugCandidate) {
        // Cache de Sessão: Evita ir ao banco se já temos os dados desse slug
        if (isset($_SESSION['tenant_slug']) && $_SESSION['tenant_slug'] === $slugCandidate && isset($_SESSION['tenant_data'])) {
            return $_SESSION['tenant_data'];
        }

        $pdo = Database::getConnection();
        
        // Busca TODOS os campos de design
        $sql = "SELECT id, name, slug, logo_url, primary_color, secondary_color, 
                       login_bg_url, login_opacity, login_btn_color, login_title, login_subtitle 
                FROM saas_tenants WHERE slug = ? LIMIT 1";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$slugCandidate]);
        $tenant = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fallback se não encontrar
        if (!$tenant) {
            $stmt->execute(['admin']); // Tenta admin
            $tenant = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Fallback final Hardcoded
            if (!$tenant) {
                $tenant = [
                    'id' => 1, 'name' => 'FleetVision', 'slug' => 'admin',
                    'primary_color' => '#2563eb', 'login_title' => 'Bem-vindo'
                ];
            }
        }

        // CORREÇÃO DE CAMINHOS DE IMAGEM
        if (!empty($tenant['logo_url']) && $tenant['logo_url'][0] !== '/') {
            $tenant['logo_url'] = '/' . $tenant['logo_url'];
        }
        if (!empty($tenant['login_bg_url']) && $tenant['login_bg_url'][0] !== '/') {
            $tenant['login_bg_url'] = '/' . $tenant['login_bg_url'];
        }

        // Salva na Sessão
        $_SESSION['tenant_id'] = $tenant['id'];
        $_SESSION['tenant_data'] = $tenant;
        $_SESSION['tenant_slug'] = $tenant['slug'];

        return $tenant;
    }

    /**
     * Tela de Bloqueio Financeiro HTML
     */
    private static function renderBlockScreen($customerName) {
        http_response_code(403);
        $slug = $_SESSION['tenant_slug'] ?? 'admin';
        ?>
        <!DOCTYPE html>
        <html lang="pt-br">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Acesso Suspenso</title>
            <script src="https://cdn.tailwindcss.com"></script>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        </head>
        <body class="bg-slate-100 h-screen flex items-center justify-center p-4">
            <div class="bg-white max-w-lg w-full p-8 rounded-2xl shadow-xl text-center border-t-4 border-red-500">
                <div class="w-20 h-20 bg-red-100 text-red-500 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl">
                    <i class="fas fa-lock"></i>
                </div>
                <h1 class="text-2xl font-bold text-slate-800 mb-2">Acesso Suspenso</h1>
                <p class="text-slate-500 mb-6">
                    Olá <strong><?php echo htmlspecialchars($customerName); ?></strong>,<br>
                    Identificamos uma pendência financeira ou bloqueio administrativo em sua conta.
                </p>
                <a href="/<?php echo $slug; ?>/logout" class="block w-full bg-slate-800 hover:bg-slate-900 text-white font-bold py-3 rounded-xl transition">
                    Sair e Entrar com outra conta
                </a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}
?>