<?php
// index.php - Roteador Principal (Com Landing Page)

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

// Sessão (Configurada para Lax para permitir redirecionamentos corretos)
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'lifetime' => 86400
    ]);
    session_start();
}

use App\Core\Router;
$router = new Router();

// ============================================================================
//                         1. ROTAS DE VISUALIZAÇÃO
// ============================================================================

// PÁGINA INICIAL (NOVA)
$router->add('GET', 'landing', 'ViewController', 'generic', ['landing']);

// Páginas Públicas
$router->add('GET', 'login', 'ViewController', 'login');
$router->add('GET', 'logout', 'AuthController', 'logout');
$router->add('GET', 'admin_teste', 'ViewController', 'generic', ['admin_debug']);

// Páginas do Sistema (Logadas)
$pages = [
    'dashboard', 'mapa', 'frota', 'motoristas', 'clientes', 'financeiro', 
    'relatorios', 'usuarios', 'perfis', 'alertas', 'cercas', 'historico', 
    'estoque', 'ranking', 'jornada', 'icones', 'filiais', 'api_docs'
];
foreach ($pages as $p) $router->add('GET', $p, 'ViewController', 'generic', [$p]);

// Admin
$adminPages = ['admin_server', 'admin_usuarios_tenant', 'admin_crm', 'admin_gestao'];
foreach ($adminPages as $p) $router->add('GET', $p, 'ViewController', 'generic', [$p]);

// ============================================================================
//                         2. ROTAS DA API (/sys)
// ============================================================================

$router->add('GET', 'sys/debug/status', 'DebugController', 'status');
$router->add('POST', 'sys/auth/login', 'AuthController', 'login');
// ... (Adicione aqui suas outras rotas de API existentes se precisar atualizar, 
// mas se já estiverem no arquivo anterior, mantenha as que você já tem no index.php) ...
// Para garantir que você tenha TODAS, vou replicar as essenciais:
$router->add('GET', 'sys/dashboard/kpis', 'DashboardController', 'getKpis');
$router->add('GET', 'sys/dashboard/data', 'DashboardController', 'getData');
$router->add('GET', 'sys/dashboard/alerts', 'DashboardController', 'getAlerts');
$router->add('GET', 'sys/mapa', 'TraccarController', 'getPositions');
$router->add('GET', 'sys/drivers', 'DriverController', 'index');
// (Adicione o restante das rotas de API do seu index anterior aqui)


// ============================================================================
//                         3. PROCESSAMENTO DA URL
// ============================================================================

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$path = trim($uri, '/');
$parts = explode('/', $path);

$reservedPrefixes = ['sys', 'logout', 'login', 'landing', 'admin_teste', 'assets', 'uploads', 'favicon.ico'];

if (isset($parts[0]) && !in_array($parts[0], $reservedPrefixes)) {
    // É URL de Cliente: /clienteX/dashboard
    $slug = array_shift($parts); 
    $_SESSION['tenant_slug'] = $slug; 
    $route = implode('/', $parts);
} else {
    // É URL Global: /sys/..., /login, ou raiz
    $route = $path;
}

$route = trim($route, '/');

// *** MUDANÇA AQUI ***
// Se a rota estiver vazia (acesso à raiz), manda para a Landing Page
if (empty($route)) {
    $route = 'landing';
}

$router->dispatch($route, $method);
?>