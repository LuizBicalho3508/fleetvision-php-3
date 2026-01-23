<?php
namespace App\Middleware;

use App\Config\Database;
use PDO;

class AuthMiddleware {

    public static function handle() {
        // 1. Inicia Sessão se necessário
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $uri = $_SERVER['REQUEST_URI'];
        $path = parse_url($uri, PHP_URL_PATH);
        $path = trim($path, '/');
        $parts = explode('/', $path);

        // ====================================================================
        // 2. EXCEÇÕES GLOBAIS (Rotas Públicas e Webhooks)
        // ====================================================================
        
        // Permite Webhooks do Asaas/Gateway sem verificação de sessão ou tenant
        if (strpos($path, 'api/webhook') === 0) {
            return; 
        }
        // Dentro do método handle(), junto com as outras exceções:
        if (strpos($path, 'api/cron') === 0) {
            return; // Permite execução do Cron sem login
        }

        // Permite endpoints de Autenticação da API (Login, Recovery, Reset)
        if (strpos($path, 'api/auth') === 0) {
            return;
        }

        // ====================================================================
        // 3. IDENTIFICAÇÃO DO TENANT (Multicliente)
        // ====================================================================
        
        $pdo = Database::getConnection();
        
        // O primeiro segmento da URL é o slug do tenant (ex: /empresa-x/dashboard)
        // Se for uma chamada de API direta que não começa com 'api', assume admin ou trata depois
        $slug = $parts[0] ?? 'admin';

        // Evita tentar buscar tenant se a rota for 'api' na raiz (ex: api/status)
        if ($slug === 'api') {
            // Se for API protegida, confia na Sessão já existente ou Token
            // Para simplificar, vamos deixar passar e os Controllers da API validam sessão
            return; 
        }

        // Busca informações do Tenant no banco
        $stmt = $pdo->prepare("SELECT id, name, slug, login_bg_url, primary_color, secondary_color, logo_url FROM saas_tenants WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        $tenant = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fallback: Se slug não existe, carrega o 'admin' ou o primeiro disponível
        if (!$tenant) {
            $stmt->execute(['admin']);
            $tenant = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Se nem admin existir (banco vazio), cria array dummy para não quebrar
            if (!$tenant) {
                $tenant = ['id' => 1, 'name' => 'FleetVision', 'slug' => 'admin', 'primary_color' => '#3b82f6'];
            }
        }

        // Salva contexto do Tenant na sessão
        $_SESSION['tenant_id'] = $tenant['id'];
        $_SESSION['tenant_data'] = $tenant;
        $_SESSION['tenant_slug'] = $tenant['slug'];

        // ====================================================================
        // 4. VERIFICAÇÃO DE AUTENTICAÇÃO (Login Wall)
        // ====================================================================

        // Páginas visuais que não exigem login
        $publicPages = ['login', 'recover', 'reset', '404'];
        $currentPage = $parts[1] ?? 'dashboard'; // O segundo segmento é a página (ex: /slug/PAGINA)

        // Se a página atual NÃO é pública e o usuário NÃO está logado
        if (!in_array($currentPage, $publicPages)) {
            if (!isset($_SESSION['user_id'])) {
                // Redireciona para o login do tenant atual
                header("Location: /{$tenant['slug']}/login");
                exit;
            }

            // ====================================================================
            // 5. BLOQUEIO FINANCEIRO (SaaS)
            // ====================================================================
            
            $userRole = $_SESSION['user_role'] ?? 'user';
            $userId = $_SESSION['user_id'];

            // Superadmin nunca é bloqueado
            if ($userRole !== 'superadmin') {
                // Verifica status financeiro do Cliente vinculado ao Usuário
                // Nota: O bloqueio é no nível do Cliente (Customer), não do Usuário individual
                $sql = "SELECT c.financial_status, c.name 
                        FROM saas_users u 
                        JOIN saas_customers c ON u.customer_id = c.id 
                        WHERE u.id = ? AND u.tenant_id = ? 
                        LIMIT 1";
                
                $stmtStatus = $pdo->prepare($sql);
                $stmtStatus->execute([$userId, $tenant['id']]);
                $customerData = $stmtStatus->fetch(PDO::FETCH_ASSOC);

                if ($customerData && $customerData['financial_status'] === 'overdue') {
                    // Mata a execução e mostra tela de bloqueio
                    self::renderBlockScreen($customerData['name']);
                }
            }
        }
    }

    /**
     * Renderiza uma tela de bloqueio simples e direta.
     */
    private static function renderBlockScreen($customerName) {
        http_response_code(403);
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
                    Identificamos uma pendência financeira em sua conta. O acesso ao sistema foi temporariamente bloqueado.
                </p>
                
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 mb-6 text-sm text-left">
                    <p class="font-bold text-slate-700 mb-1"><i class="fas fa-question-circle mr-1"></i> Como resolver?</p>
                    <ul class="list-disc list-inside text-slate-500 space-y-1 ml-1">
                        <li>Acesse seu e-mail para ver os boletos pendentes.</li>
                        <li>Caso já tenha pago, aguarde a compensação bancária.</li>
                        <li>Entre em contato com o suporte financeiro.</li>
                    </ul>
                </div>

                <a href="/<?php echo $_SESSION['tenant_slug'] ?? 'admin'; ?>/logout" class="block w-full bg-slate-800 hover:bg-slate-900 text-white font-bold py-3 rounded-xl transition">
                    Voltar para Login
                </a>
            </div>
        </body>
        </html>
        <?php
        exit; // Impede carregamento do resto do sistema
    }
}
?>