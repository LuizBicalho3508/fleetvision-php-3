<?php
// index.php - Roteador Principal (Corrigido Definitivo)

// 1. Configurações Iniciais
ob_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. AUTOLOAD
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;

    $relative_class = substr($class, $len);
    
    // Tenta caminho padrão (PSR-4)
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
        return;
    } 
    
    // Tenta caminho com 'controllers' em minúsculo (Fallback Linux)
    $lowerFile = $base_dir . str_replace('Controllers/', 'controllers/', str_replace('\\', '/', $relative_class)) . '.php';
    if (file_exists($lowerFile)) {
        require $lowerFile;
        return;
    }
});

// 3. Sessão
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'path' => '/', 
        'httponly' => true, 
        'samesite' => 'Lax', 
        'lifetime' => 86400,
        'secure' => false // Mude para true se tiver HTTPS
    ]);
    session_start();
}

// Helper de Permissões
function hasPermission($slug) {
    if (($_SESSION['user_role'] ?? '') === 'superadmin') return true;
    if (empty($_SESSION['user_permissions'])) return false;
    return in_array($slug, $_SESSION['user_permissions']);
}

use App\Core\Router;

if (!class_exists('App\Core\Router')) {
    die("ERRO CRÍTICO: O arquivo 'app/Core/Router.php' não foi encontrado.");
}

$router = new Router();

// ============================================================================
//                         1. ROTAS DE PÁGINAS (VISUALIZAÇÃO)
// ============================================================================

// Públicas
$router->add('GET', 'landing', 'ViewController', 'generic', ['landing']);
$router->add('GET', 'login',   'ViewController', 'login');
$router->add('GET', 'recover', 'ViewController', 'generic', ['recover']);
$router->add('GET', 'logout',  'AuthController', 'logout');
$router->add('GET', 'reset',   'ViewController', 'generic', ['reset']);

// Páginas Internas (SaaS)
$pages = [
    'dashboard', 'mapa', 'frota', 'motoristas', 'clientes', 
    'financeiro', 'relatorios', 'usuarios', 'perfis', 
    'alertas', 'cercas', 'historico', 'estoque', 
    'ranking', 'jornada', 'icones', 'filiais', 'api_docs'
];
foreach ($pages as $p) {
    $router->add('GET', $p, 'ViewController', 'generic', [$p]);
}

// Páginas Admin (Superadmin)
$adminPages = ['admin_server', 'admin_usuarios_tenant', 'admin_crm', 'admin_gestao', 'admin_teste', 'admin_financeiro'];
foreach ($adminPages as $p) {
    $router->add('GET', $p, 'ViewController', 'generic', [$p]);
}

// ============================================================================
//                         2. ROTAS DA API (/sys)
// ============================================================================

// --- Autenticação ---
$router->add('POST', 'sys/auth/login',   'AuthController', 'login');
$router->add('POST', 'sys/auth/recover', 'AuthController', 'forgotPassword');
$router->add('POST', 'sys/auth/reset',   'AuthController', 'resetPassword');

// --- SGBras / Monitoramento / Proxy ---
$router->add('GET',  'sgbras_monitor',      'SGBrasController', 'index');
$router->add('POST', 'sys/sgbras/config',   'SGBrasController', 'saveConfig');
$router->add('POST', 'sys/sgbras/stream',   'SGBrasController', 'stream');
$router->add('POST', 'sys/sgbras/location', 'SGBrasController', 'location');
$router->add('GET',  'sys/sgbras/proxy',    'SGBrasController', 'proxy');
$router->add('POST', 'sys/sgbras/search',   'SGBrasController', 'search');

// --- Dashboard & Mapa ---
$router->add('GET', 'sys/dashboard/kpis',   'DashboardController', 'getKpis');
$router->add('GET', 'sys/dashboard/data',   'DashboardController', 'getData');
$router->add('GET', 'sys/dashboard/alerts', 'DashboardController', 'getAlerts');
$router->add('GET', 'sys/mapa',             'TraccarController',   'getPositions');
$router->add('GET', 'sys/mapa/geocode',     'TraccarController',   'geocode');

// --- Veículos (Frota) - CORRIGIDO ---
$router->add('GET',  'sys/vehicles',          'VehicleController', 'list'); // Retorna JSON
$router->add('GET',  'sys/vehicles/trackers', 'VehicleController', 'getAvailableTrackers'); // Novo
$router->add('POST', 'sys/vehicles',          'VehicleController', 'store');
$router->add('POST', 'sys/vehicles/update',   'VehicleController', 'update');
$router->add('POST', 'sys/vehicles/delete',   'VehicleController', 'delete');

// --- Clientes - CORRIGIDO ---
$router->add('GET',  'sys/customers',          'CustomerController', 'list'); // Retorna JSON
$router->add('POST', 'sys/customers',          'CustomerController', 'store');
$router->add('POST', 'sys/customers/update',   'CustomerController', 'update');
$router->add('POST', 'sys/customers/delete',   'CustomerController', 'delete');
$router->add('GET',  'sys/customers/vehicles', 'CustomerController', 'getVehicles');

// --- Motoristas ---
$router->add('GET',  'sys/drivers',        'DriverController', 'list');
$router->add('POST', 'sys/drivers/save',   'DriverController', 'store');
$router->add('POST', 'sys/drivers/delete', 'DriverController', 'delete');

// --- Usuários ---
$router->add('GET',  'sys/users',        'UserController', 'list');
$router->add('POST', 'sys/users/save',   'UserController', 'store');
$router->add('POST', 'sys/users/delete', 'UserController', 'delete');

// --- Estoque (Rastreadores) ---
$router->add('GET',  'sys/stock',        'StockController', 'index');
$router->add('POST', 'sys/stock',        'StockController', 'store');
$router->add('POST', 'sys/stock/update', 'StockController', 'update');
$router->add('POST', 'sys/stock/delete', 'StockController', 'delete');

// --- Alertas & Cercas ---
$router->add('GET',  'sys/alerts',           'AlertsController', 'index');
$router->add('POST', 'sys/alerts/resolve',   'AlertsController', 'resolve');
$router->add('GET',  'sys/alerts/poll',      'SGBrasController', 'poll'); // Polling unificado
$router->add('POST', 'sys/alerts/poll',      'SGBrasController', 'poll');
$router->add('GET',  'sys/geofences',        'GeofenceController', 'index');
$router->add('POST', 'sys/geofences',        'GeofenceController', 'store');
$router->add('POST', 'sys/geofences/delete', 'GeofenceController', 'delete');

// --- Financeiro ---
$router->add('GET', 'sys/financial/invoices', 'FinancialController', 'index');
$router->add('GET', 'sys/financial/summary',  'FinancialController', 'summary');
// --- Financeiro Asaas (Tenant) ---
$router->add('GET',  'sys/financial/data',              'FinancialController', 'getDashboardData');
$router->add('GET',  'sys/financial/cards',             'FinancialController', 'getCardDetails');
$router->add('GET',  'sys/financial/customers',         'FinancialController', 'searchCustomers');
$router->add('GET',  'sys/financial/customer_invoices', 'FinancialController', 'getCustomerInvoices');
$router->add('POST', 'sys/financial/config',            'FinancialController', 'saveConfig');

// --- Relatórios & Jornada ---
$router->add('POST', 'sys/reports/generate', 'ReportController', 'generate');
$router->add('GET',  'sys/journey/filter',   'JourneyController', 'filter');
$router->add('GET',  'sys/ranking',          'RankingController', 'index');

// --- Diversos (Ícones, Filiais, Perfis) ---
$router->add('GET',  'sys/icons',         'IconController', 'index');
$router->add('POST', 'sys/icons/upload',  'IconController', 'upload');
$router->add('POST', 'sys/icons/delete',  'IconController', 'delete');
$router->add('GET',  'sys/branches',      'BranchController', 'index');
$router->add('POST', 'sys/branches',      'BranchController', 'store');
$router->add('GET',  'sys/roles',         'RoleController', 'list');
$router->add('GET',  'sys/roles/perms',   'RoleController', 'getPermissions');
$router->add('POST', 'sys/roles/save',    'RoleController', 'store');
$router->add('POST', 'sys/roles/delete',  'RoleController', 'delete');

// --- Testes (Lab) ---
$router->add('GET',  'sys/teste',         'TestController', 'index');
$router->add('GET',  'sys/teste/stock',   'TestController', 'listStock');
$router->add('GET',  'sys/teste/live',    'TestController', 'getDeviceLive');
$router->add('POST', 'sys/teste/command', 'TestController', 'sendCommand');
$router->add('GET',  'sys/teste/history', 'TestController', 'getHistory');

// --- Admin Super (API) ---
$router->add('GET',  'sys/admin/tenants',       'AdminTenantController', 'index');
$router->add('POST', 'sys/admin/tenants/save',  'AdminTenantController', 'save');
$router->add('POST', 'sys/admin/tenants/delete','AdminTenantController', 'delete');
$router->add('GET',  'sys/admin/design',        'AdminDesignController', 'getSettings');
$router->add('POST', 'sys/admin/design/save',   'AdminDesignController', 'save');
$router->add('POST', 'sys/admin/design/reset',  'AdminDesignController', 'resetBackground');
$router->add('GET',  'sys/admin/server/stats',  'AdminServerController', 'stats');
$router->add('GET',  'sys/admin/financial',     'AdminFinancialController', 'index');
$router->add('POST', 'sys/admin/financial/save','AdminFinancialController', 'saveSettings');
$router->add('POST', 'sys/admin/financial/pay', 'AdminFinancialController', 'togglePayment');

// --- Webhooks ---
$router->add('POST', 'api/webhook/asaas', 'WebhookController', 'asaas');
$router->add('GET',  'api/cron/process',  'CronController', 'process');


// ============================================================================
//                         3. DISPATCH (Processamento de URL)
// ============================================================================

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$path = trim($uri, '/');

// Ignora estáticos
if (preg_match('/\.(ico|png|jpg|jpeg|gif|css|js|txt|woff|woff2|ttf|map)$/', $path)) {
    exit;
}

$parts = explode('/', $path);
$reserved = ['sys', 'login', 'logout', 'landing', 'assets', 'uploads', 'api', 'admin_server'];

if (isset($parts[0]) && !in_array($parts[0], $reserved) && !empty($parts[0])) {
    $slug = array_shift($parts); 
    $_SESSION['tenant_slug'] = $slug; 
    $route = implode('/', $parts);
} else {
    $route = $path;
}

$route = trim($route, '/');
if (empty($route)) $route = 'landing';

$router->dispatch($route, $method);
?>