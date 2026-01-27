<?php
// index.php - Roteador Principal (Completo e Atualizado)

// 1. Configurações de Erro
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. AUTOLOAD MANUAL E ROBUSTO (Mantido do seu código)
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;

    $relative_class = substr($class, $len);
    
    // Caminho Padrão (PSR-4: app/Controllers/AuthController.php)
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
        return;
    } 
    
    // Fallback: Tenta minúsculo na pasta (app/controllers/...)
    $lowerFile = $base_dir . str_replace('Controllers/', 'controllers/', str_replace('\\', '/', $relative_class)) . '.php';
    if (file_exists($lowerFile)) {
        require $lowerFile;
        return;
    }
});

// 3. Sessão
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'lifetime' => 86400]);
    session_start();
}

// --- HELPER DE PERMISSÕES GLOBAL ---
function hasPermission($slug) {
    // 1. Superadmin tem acesso a TUDO (Passe Livre)
    if (($_SESSION['user_role'] ?? '') === 'superadmin') {
        return true;
    }
    
    // 2. Se não tem sessão de permissões, nega
    if (empty($_SESSION['user_permissions'])) {
        return false;
    }

    // 3. Verifica se a permissão existe no array carregado no login
    return in_array($slug, $_SESSION['user_permissions']);
}

use App\Core\Router;

if (!class_exists('App\Core\Router')) {
    die("ERRO CRÍTICO: O arquivo 'app/Core/Router.php' não foi encontrado.");
}

$router = new Router();

// ============================================================================
//                         1. ROTAS DE PÁGINAS (FRONTEND)
// ============================================================================

// Acesso Público / Genérico
$router->add('GET', 'landing', 'ViewController', 'generic', ['landing']);
$router->add('GET', 'login',   'ViewController', 'login');
$router->add('GET', 'recover', 'ViewController', 'generic', ['recover']);
$router->add('GET', 'logout',  'AuthController', 'logout');
$router->add('GET', 'reset',   'ViewController', 'generic', ['reset']);

// Páginas do Sistema (Requer Login)
// Adicionadas TODAS as views disponíveis na pasta /views
$pages = [
    'dashboard', 
    'mapa', 
    'frota', 
    'motoristas', 
    'clientes',      // <--- AQUI ESTAVA FALTANDO
    'financeiro', 
    'relatorios', 
    'usuarios', 
    'perfis', 
    'alertas', 
    'cercas', 
    'historico', 
    'estoque', 
    'ranking', 
    'jornada', 
    'icones', 
    'filiais', 
    'api_docs'
];

foreach ($pages as $p) {
    $router->add('GET', $p, 'ViewController', 'generic', [$p]);
}

// Páginas Administrativas (Superadmin)
$adminPages = [
    'admin_server', 
    'admin_usuarios_tenant', 
    'admin_crm', 
    'admin_gestao',
    'admin_teste'
];

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

// --- Dashboard & Monitoramento ---
$router->add('GET', 'sys/dashboard/kpis',   'DashboardController', 'getKpis');
$router->add('GET', 'sys/dashboard/data',   'DashboardController', 'getData');
$router->add('GET', 'sys/dashboard/alerts', 'DashboardController', 'getAlerts');
$router->add('GET', 'sys/mapa',             'TraccarController',   'getPositions');
$router->add('GET', 'sys/debug/status',     'DebugController',     'status');
// --- DASHBOARD ---
$router->add('GET', 'dashboard',           'DashboardController', 'index');
$router->add('GET', 'sys/dashboard/kpis',  'DashboardController', 'getKpis');
$router->add('GET', 'sys/dashboard/data',  'DashboardController', 'getData');

// --- Clientes ---
$router->add('GET',  'sys/customers',          'CustomerController', 'index');
$router->add('POST', 'sys/customers',          'CustomerController', 'store');
$router->add('POST', 'sys/customers/update',   'CustomerController', 'update');
$router->add('POST', 'sys/customers/delete',   'CustomerController', 'delete');

// NOVAS ROTAS AUXILIARES
$router->add('GET',  'sys/customers/vehicles', 'CustomerController', 'getVehicles');
$router->add('GET',  'sys/customers/invoice',  'CustomerController', 'getInvoicePreview');
// ...
$router->add('GET', 'teste', 'TestController', 'index');
// --- ROTAS DA API (Dados para o Javascript) ---
$router->add('GET',  'sys/teste/stock',      'TestController', 'listStock');
$router->add('GET',  'sys/teste/live',       'TestController', 'getDeviceLive');
$router->add('POST', 'sys/teste/command',    'TestController', 'sendCommand');
$router->add('GET',  'sys/teste/history',    'TestController', 'getHistory');
// index.php
// --- Página de Testes (Laboratório) ---
$router->add('GET',  'sys/teste',            'TestController', 'index');
$router->add('GET',  'sys/teste/stock',      'TestController', 'listStock');
$router->add('GET',  'sys/teste/live',       'TestController', 'getDeviceLive');
$router->add('POST', 'sys/teste/command',    'TestController', 'sendCommand');
$router->add('GET',  'sys/teste/history',    'TestController', 'getHistory');
// Rota principal do Mapa (Dados dos veículos)
$router->add('GET', 'sys/mapa', 'TraccarController', 'getPositions');

// Rota de Geocodificação (Busca de Endereço) - ESSENCIAL PARA O ENDEREÇO APARECER
$router->add('GET', 'sys/mapa/geocode', 'TraccarController', 'geocode');
// --- Motoristas (CRUD) ---
// --- GESTÃO DE MOTORISTAS ---
$router->add('GET',  'motoristas',           'DriverController', 'index');       // Página HTML
$router->add('GET',  'sys/drivers',          'DriverController', 'list');        // API Listar
$router->add('POST', 'sys/drivers/save',     'DriverController', 'store');       // API Salvar
$router->add('POST', 'sys/drivers/delete',   'DriverController', 'delete');      // API Excluir

// --- Usuários (CRUD) ---
// --- USUÁRIOS ---
$router->add('GET',  'usuarios',          'UserController', 'index');
$router->add('GET',  'sys/users',         'UserController', 'list');
$router->add('POST', 'sys/users/save',    'UserController', 'store');
$router->add('POST', 'sys/users/delete',  'UserController', 'delete');

// --- Financeiro ---
$router->add('GET', 'sys/financial/invoices', 'FinancialController', 'index');
$router->add('GET', 'sys/financial/summary',  'FinancialController', 'summary');

// --- Relatórios ---
$router->add('POST', 'sys/reports/generate', 'ReportController', 'generate');

// --- Funcionalidades Específicas ---
$router->add('GET',  'sys/alerts',           'AlertsController', 'index');
$router->add('POST', 'sys/alerts/resolve',   'AlertsController', 'resolve');

$router->add('GET',  'sys/geofences',        'GeofenceController', 'index');
$router->add('POST', 'sys/geofences',        'GeofenceController', 'store');
$router->add('POST', 'sys/geofences/delete', 'GeofenceController', 'delete');

// index.php
// --- JORNADA DE TRABALHO ---
$router->add('GET', 'jornada',            'JourneyController', 'index');  // Página HTML
$router->add('GET', 'sys/journey/filter', 'JourneyController', 'filter'); // API de Dados
// ... outras rotas ...

// --- Estoque de Rastreadores (CRUD Completo) ---
$router->add('GET',  'sys/stock',        'StockController', 'index');  // Listar
$router->add('POST', 'sys/stock',        'StockController', 'store');  // Cadastrar (Salvar Novo)
$router->add('POST', 'sys/stock/update', 'StockController', 'update'); // Editar (Faltava este)
$router->add('POST', 'sys/stock/delete', 'StockController', 'delete'); // Excluir (O erro 404 era aqui)

// ... restante do arquivo ...
// ...
// --- Veículos (Frota) ---
$router->add('GET',  'sys/vehicles',        'VehicleController', 'index');
$router->add('POST', 'sys/vehicles',        'VehicleController', 'store');
$router->add('POST', 'sys/vehicles/delete', 'VehicleController', 'delete');
$router->add('GET',  'sys/stock/available', 'VehicleController', 'getAvailableTrackers'); // Rota do Autocomplete
// index.php
$router->add('POST', 'sys/vehicles/update', 'VehicleController', 'update');
// ...
// --- Ícones (Personalização) ---
$router->add('GET',  'sys/icons',        'IconController', 'index');
$router->add('POST', 'sys/icons/upload', 'IconController', 'upload'); // Corrige o erro 404
$router->add('POST', 'sys/icons/delete', 'IconController', 'delete');

$router->add('GET',  'sys/ranking',          'RankingController', 'index');
$router->add('GET',  'sys/journeys',         'JourneyController', 'index');

$router->add('GET',  'sys/branches',         'BranchController', 'index');
$router->add('POST', 'sys/branches',         'BranchController', 'store');

$router->add('GET',  'perfis',              'RoleController', 'index');
$router->add('GET',  'sys/roles',           'RoleController', 'list');
$router->add('GET',  'sys/roles/perms',     'RoleController', 'getPermissions');
$router->add('POST', 'sys/roles/save',      'RoleController', 'store');
$router->add('POST', 'sys/roles/delete',    'RoleController', 'delete');

// --- Administração (Superadmin) ---
$router->add('GET',  'sys/admin/tenants',      'AdminTenantController', 'index');
$router->add('POST', 'sys/admin/tenants/save', 'AdminTenantController', 'save');
$router->add('POST', 'sys/admin/tenants/delete','AdminTenantController','delete');
$router->add('GET',  'sys/admin/design',       'AdminDesignController', 'getSettings');
$router->add('POST', 'sys/admin/design/save',  'AdminDesignController', 'save');
$router->add('POST', 'sys/admin/design/reset', 'AdminDesignController', 'resetBackground');
$router->add('GET',  'sys/admin/server',       'AdminServerController', 'status');
// ...
// --- Financeiro Superadmin ---
$router->add('GET',  'admin_financeiro',          'ViewController',           'generic', ['admin_financeiro']);
$router->add('GET',  'sys/admin/financial',       'AdminFinancialController', 'index');
$router->add('POST', 'sys/admin/financial/save',  'AdminFinancialController', 'saveSettings');
$router->add('POST', 'sys/admin/financial/pay',   'AdminFinancialController', 'togglePayment');
// ...
// ALERTAS E NOTIFICAÇÕES
$router->add('GET',  'alertas',              'AlertController', 'index');
$router->add('GET',  'sys/alerts/settings',  'AlertController', 'getSettings');
$router->add('POST', 'sys/alerts/settings',  'AlertController', 'saveSettings');
$router->add('GET',  'sys/alerts/poll',      'AlertController', 'checkNew');
$router->add('GET',  'sys/alerts/history',   'AlertController', 'getHistory');

// --- Webhooks / Cron ---
$router->add('POST', 'api/webhook/asaas', 'WebhookController', 'asaas');
$router->add('GET',  'api/cron/process',  'CronController', 'process');


// ============================================================================
//                         3. DISPATCH (Processamento)
// ============================================================================

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$path = trim($uri, '/');

// Ignora arquivos estáticos (Imagens, CSS, JS, Favicon, Robots)
if (preg_match('/\.(ico|png|jpg|jpeg|gif|css|js|txt|woff|woff2|ttf|map)$/', $path)) {
    exit;
}

$parts = explode('/', $path);
$reserved = ['sys', 'login', 'logout', 'landing', 'assets', 'uploads', 'api'];

if (isset($parts[0]) && !in_array($parts[0], $reserved) && !empty($parts[0])) {
    // É uma URL com Slug de Cliente (ex: /cliente/dashboard)
    $slug = array_shift($parts); 
    $_SESSION['tenant_slug'] = $slug; 
    $route = implode('/', $parts);
} else {
    // É uma URL Global ou de Sistema
    $route = $path;
}

$route = trim($route, '/');
if (empty($route)) $route = 'landing';

// Executa a rota
$router->dispatch($route, $method);
?>