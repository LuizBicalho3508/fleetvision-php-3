<?php
// index.php - Roteador Principal

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Autoload
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

// Sessão
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict', 'lifetime' => 86400]);
    session_start();
}

use App\Core\Router;
$router = new Router();

// ============================================================================
//                         1. ROTAS DE VISUALIZAÇÃO (PÁGINAS)
// ============================================================================
// Estas rotas carregam os arquivos .php visuais (HTML)

// Acesso Público
$router->add('GET', 'login',          'ViewController', 'login');
$router->add('GET', 'recover',        'ViewController', 'generic', ['recover']); // recover.php
$router->add('GET', 'reset',          'ViewController', 'generic', ['reset']);   // reset.php

// Acesso Restrito (O próprio arquivo .php da view deve verificar a sessão)
$router->add('GET', 'dashboard',              'ViewController', 'dashboard');
$router->add('GET', 'mapa',                   'ViewController', 'generic', ['mapa']);
$router->add('GET', 'frota',                  'ViewController', 'generic', ['frota']);
$router->add('GET', 'motoristas',             'ViewController', 'generic', ['motoristas']);
$router->add('GET', 'clientes',               'ViewController', 'generic', ['clientes']);
$router->add('GET', 'financeiro',             'ViewController', 'generic', ['financeiro']);
$router->add('GET', 'relatorios',             'ViewController', 'generic', ['relatorios']);
$router->add('GET', 'usuarios',               'ViewController', 'generic', ['usuarios']);
$router->add('GET', 'perfis',                 'ViewController', 'generic', ['perfis']);
$router->add('GET', 'alertas',                'ViewController', 'generic', ['alertas']);
$router->add('GET', 'cercas',                 'ViewController', 'generic', ['cercas']);
$router->add('GET', 'historico',              'ViewController', 'generic', ['historico']);
$router->add('GET', 'estoque',                'ViewController', 'generic', ['estoque']);
$router->add('GET', 'perfil',                 'ViewController', 'generic', ['perfil']); // Se existir perfil.php

// Módulos Novos (Views)
$router->add('GET', 'ranking',                'ViewController', 'generic', ['ranking']); // ranking.php (se existir view separada) ou usar ranking_motoristas.php
$router->add('GET', 'jornada',                'ViewController', 'generic', ['jornada']);
$router->add('GET', 'icones',                 'ViewController', 'generic', ['icones']);
$router->add('GET', 'filiais',                'ViewController', 'generic', ['filiais']);

// Superadmin (Views)
$router->add('GET', 'admin_server',           'ViewController', 'adminServer');
$router->add('GET', 'admin_usuarios_tenant',  'ViewController', 'adminTenants');
$router->add('GET', 'admin_crm',              'ViewController', 'adminCrm');
$router->add('GET', 'admin_gestao',           'ViewController', 'adminGestao');

// Logout (Redireciona)
$router->add('GET', 'logout', 'AuthController', 'logout');


// ============================================================================
//                         2. ROTAS DA API (DADOS JSON)
// ============================================================================

// Auth
$router->add('POST', 'api/auth/login',     'AuthController', 'login');
$router->add('POST', 'api/auth/recover',   'AuthController', 'forgotPassword');
$router->add('POST', 'api/auth/reset',     'AuthController', 'resetPassword');

// Admin
$router->add('GET',  'api/admin/tenants',        'AdminTenantController', 'index');
$router->add('POST', 'api/admin/tenants/save',   'AdminTenantController', 'store');
$router->add('POST', 'api/admin/tenants/delete', 'AdminTenantController', 'delete');
$router->add('GET',  'api/admin/server/stats',   'AdminServerController', 'stats');
$router->add('GET',  'api/admin/crm',            'AdminCrmController', 'index');
$router->add('POST', 'api/admin/crm/save',       'AdminCrmController', 'store');
$router->add('POST', 'api/admin/crm/delete',     'AdminCrmController', 'delete');
$router->add('POST', 'api/admin/crm/status',     'AdminCrmController', 'updateStatus');
$router->add('GET',  'api/admin/design',         'AdminDesignController', 'getSettings');
$router->add('POST', 'api/admin/design/save',    'AdminDesignController', 'save');
$router->add('POST', 'api/admin/design/reset',   'AdminDesignController', 'resetBackground');

// Sistema
$router->add('GET',  'api/dashboard/data',   'DashboardController', 'getData');
$router->add('GET',  'api/drivers',          'DriverController', 'index');
$router->add('POST', 'api/drivers/save',     'DriverController', 'store');
$router->add('POST', 'api/drivers/delete',   'DriverController', 'delete');
$router->add('GET',  'api/branches',         'BranchController', 'index');
$router->add('POST', 'api/branches/save',    'BranchController', 'store');
$router->add('POST', 'api/branches/delete',  'BranchController', 'delete');
$router->add('GET',  'api/icons',            'IconController', 'index');
$router->add('POST', 'api/icons/save',       'IconController', 'store');
$router->add('POST', 'api/icons/delete',     'IconController', 'delete');
$router->add('GET',  'api/ranking',          'RankingController', 'index');
$router->add('POST', 'api/ranking/rules',    'RankingController', 'saveRules');
$router->add('GET',  'api/journey',          'JourneyController', 'index');

// --- Adicione dentro da seção 1 (VISUALIZAÇÃO) ---
$router->add('GET', 'api_docs', 'ViewController', 'generic', ['api_docs']);

// --- Adicione dentro da seção 2 (API) ---

// Cron Jobs e Automação
$router->add('GET', 'api/cron/notifications', 'CronController', 'runNotifications');
$router->add('POST', 'api/admin/sync_global', 'CronController', 'syncGlobalRules');

// ============================================================================
//                         3. PROCESSAMENTO DA URL
// ============================================================================

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$path = trim($uri, '/');
$parts = explode('/', $path);

$reservedPrefixes = ['api', 'logout', 'assets', 'uploads', 'favicon.ico'];

if (isset($parts[0]) && !in_array($parts[0], $reservedPrefixes)) {
    // URL: /clienteX/pagina -> Slug: clienteX, Rota: /pagina
    $slug = array_shift($parts); 
    $_SESSION['tenant_slug'] = $slug; 
    
    // Se sobrou algo na URL, usa como rota. Se não, vai pro dashboard/login.
    $uri = empty($parts) ? '/login' : '/' . implode('/', $parts);
} elseif (empty($path)) {
    // Raiz do domínio -> Login
    $uri = '/login';
}

$router->dispatch($uri, $method);
?>