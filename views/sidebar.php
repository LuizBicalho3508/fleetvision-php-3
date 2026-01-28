<?php
// views/sidebar.php

if (session_status() === PHP_SESSION_NONE) session_start();

// ---------------------------------------------------------
// 1. CORREÇÃO DE LÓGICA: Identidade Visual (URL > Sessão)
// ---------------------------------------------------------

// Passo A: Tenta pegar o slug da URL atual (Visualização)
$uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uriParts = explode('/', trim($uriPath, '/'));

// Lista de palavras reservadas que NÃO são tenants
$reservedPaths = ['sys', 'api', 'assets', 'login', 'dashboard', 'logout'];

$detectedSlug = null;
if (isset($uriParts[0]) && !in_array($uriParts[0], $reservedPaths)) {
    $detectedSlug = $uriParts[0];
}

// Passo B: Define o slug final (Prioriza URL, se falhar usa a Sessão)
// Isso permite que um Admin acesse /cliente-x/ e veja a logo do Cliente X
$currentSlug = $detectedSlug ?? ($_SESSION['tenant_slug'] ?? 'admin');

// ---------------------------------------------------------
// 2. Busca Dados no Banco (Com o slug correto)
// ---------------------------------------------------------

// Função de Correção de URL (Garante que funcione em qualquer pasta)
if (!function_exists('get_sidebar_safe_url')) {
    function get_sidebar_safe_url($dbUrl) {
        if (empty($dbUrl)) return '';
        if (filter_var($dbUrl, FILTER_VALIDATE_URL)) return $dbUrl;
        
        $cleanPath = ltrim(trim($dbUrl), '/');
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        $baseUrl   = ($scriptDir === '/' || $scriptDir === '\\') ? '' : $scriptDir;
        $baseUrl   = str_replace('\\', '/', $baseUrl); 
        
        // Se o caminho já começar com uploads, garante a raiz
        return $baseUrl . '/' . $cleanPath;
    }
}

$tenantData = null;
try {
    if (!class_exists('App\Config\Database')) {
        $possiblePath = __DIR__ . '/../app/Config/Database.php';
        if (file_exists($possiblePath)) require_once $possiblePath;
    }
    
    if (class_exists('App\Config\Database')) {
        $pdo = \App\Config\Database::getConnection();
        // Busca cores e logo
        $stmt = $pdo->prepare("SELECT name, logo_url, primary_color, sidebar_color FROM saas_tenants WHERE slug = ? LIMIT 1");
        $stmt->execute([$currentSlug]);
        $tenantData = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) { }

// ---------------------------------------------------------
// 3. Definição de Variáveis Visuais
// ---------------------------------------------------------

$tenantName   = $tenantData['name'] ?? 'FleetVision';
$logoUrl      = get_sidebar_safe_url($tenantData['logo_url'] ?? '');

// Cores com fallback
$primaryColor = !empty($tenantData['primary_color']) ? $tenantData['primary_color'] : '#2563eb';
$sidebarColor = !empty($tenantData['sidebar_color']) ? $tenantData['sidebar_color'] : '#ffffff';

// Lógica de Contraste (Se o fundo for escuro, texto branco)
// Verifica se é preto, cinza escuro ou cores fortes
$isDarkSidebar = (
    strtolower($sidebarColor) !== '#ffffff' && 
    strtolower($sidebarColor) !== '#fff' && 
    strtolower($sidebarColor) !== 'white'
);

// Se for preto (#000000) forçamos o modo Dark
if ($sidebarColor === '#000000') $isDarkSidebar = true;

$textColor     = $isDarkSidebar ? '#ffffff' : '#475569'; 
$borderColor   = $isDarkSidebar ? 'rgba(255,255,255,0.1)' : '#e2e8f0';
$iconColor     = $isDarkSidebar ? 'rgba(255,255,255,0.7)' : '#94a3b8';

// ---------------------------------------------------------
// 4. Menu e Permissões (Isso continua vindo da SESSÃO do Usuário)
// ---------------------------------------------------------
$userRole = strtolower(trim($_SESSION['user_role'] ?? 'guest'));
$isSystemAdmin = in_array($userRole, ['superadmin', 'admin']);

$checkAccess = function($perm) use ($isSystemAdmin) {
    if ($isSystemAdmin) return true;
    if ($perm === 'all') return true;
    if (function_exists('hasPermission') && hasPermission($perm)) return true;
    return false;
};

// Estrutura do Menu
$menuSections = [
    'VISÃO GERAL' => [
        ['label' => 'Dashboard',   'icon' => 'fa-chart-pie',      'url' => 'dashboard',  'perm' => 'dashboard_view'],
        ['label' => 'Mapa Real',   'icon' => 'fa-map-marked-alt', 'url' => 'mapa',       'perm' => 'map_view'],
    ],
    'FROTA' => [
        ['label' => 'Veículos',    'icon' => 'fa-truck',          'url' => 'frota',      'perm' => 'vehicles_view'],
        ['label' => 'Motoristas',  'icon' => 'fa-id-card',        'url' => 'motoristas', 'perm' => 'drivers_view'],
        ['label' => 'Estoque',     'icon' => 'fa-boxes',          'url' => 'estoque',    'perm' => 'stock_view'],
        ['label' => 'Manutenção',  'icon' => 'fa-wrench',         'url' => 'manutencao', 'perm' => 'maintenance_view'], 
        ['label' => 'Jornada',     'icon' => 'fa-clock',          'url' => 'jornada',    'perm' => 'journey_view'],
        ['label' => 'Ranking',     'icon' => 'fa-trophy',         'url' => 'ranking',    'perm' => 'ranking_view'],
    ],
    'MONITORAMENTO' => [
        ['label' => 'Alertas',     'icon' => 'fa-bell',           'url' => 'alertas',    'perm' => 'alerts_view'],
        ['label' => 'Cercas',      'icon' => 'fa-draw-polygon',   'url' => 'cercas',     'perm' => 'geofences_view'],
        ['label' => 'Histórico',   'icon' => 'fa-history',        'url' => 'historico',  'perm' => 'history_view'],
        ['label' => 'Laboratório', 'icon' => 'fa-flask',          'url' => 'teste',      'perm' => 'all'],
    ],
    'ADMINISTRAÇÃO' => [
        ['label' => 'Clientes',    'icon' => 'fa-user-tie',       'url' => 'clientes',   'perm' => 'customers_view'],
        ['label' => 'Filiais',     'icon' => 'fa-building',       'url' => 'filiais',    'perm' => 'branches_view'],
        ['label' => 'Financeiro',  'icon' => 'fa-file-invoice-dollar', 'url' => 'financeiro', 'perm' => 'financial_view'],
        ['label' => 'Usuários',    'icon' => 'fa-users-cog',      'url' => 'usuarios',   'perm' => 'users_view'],
        ['label' => 'Perfis',      'icon' => 'fa-shield-alt',     'url' => 'perfis',     'perm' => 'users_view'],
        ['label' => 'Relatórios',  'icon' => 'fa-file-alt',       'url' => 'relatorios', 'perm' => 'reports_view'],
        ['label' => 'Ícones',      'icon' => 'fa-icons',          'url' => 'icones',     'perm' => 'icons_view'],
        ['label' => 'Doc. API',    'icon' => 'fa-code',           'url' => 'api_docs',   'perm' => 'docs_view'],
    ],
    'CONFIGURAÇÕES' => [
        ['label' => 'Minha Conta', 'icon' => 'fa-user-circle',    'url' => 'perfil',     'perm' => 'all'],
    ]
];

if ($userRole === 'superadmin') {
    $menuSections['SUPER ADMIN'] = [
        ['label' => 'Tenants',        'icon' => 'fa-building',    'url' => 'admin_usuarios_tenant', 'perm' => 'all'],
        ['label' => 'CRM / Leads',    'icon' => 'fa-bullhorn',    'url' => 'admin_crm',             'perm' => 'all'],
        ['label' => 'Financeiro SaaS','icon' => 'fa-dollar-sign', 'url' => 'admin_financeiro',      'perm' => 'all'],
        ['label' => 'Design Login',   'icon' => 'fa-paint-brush', 'url' => 'admin_gestao',          'perm' => 'all'],
        ['label' => 'Servidor',       'icon' => 'fa-server',      'url' => 'admin_server',          'perm' => 'all'],
    ];
}

// Página Atual para Highlight do Menu
$pathSegments = explode('/', trim($uriPath, '/'));
$currentPage = end($pathSegments);
if ($currentPage == $currentSlug || empty($currentPage)) $currentPage = 'dashboard';
?>

<style>
    /* Estilos dinâmicos */
    .sidebar-wrapper {
        background-color: <?= $sidebarColor ?> !important;
        border-right: 1px solid <?= $borderColor ?> !important;
    }
    
    /* Item Ativo */
    .nav-item-active {
        background-color: <?= $primaryColor ?> !important;
        color: #ffffff !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    .nav-item-active i { color: #ffffff !important; }
    
    /* Item Hover */
    .nav-item-link:hover:not(.nav-item-active) {
        background-color: <?= $isDarkSidebar ? 'rgba(255,255,255,0.1)' : '#f8fafc' ?> !important;
        color: <?= $primaryColor ?> !important;
    }
    .nav-item-link:hover:not(.nav-item-active) i {
        color: <?= $primaryColor ?> !important;
    }
    
    /* Scrollbar */
    aside::-webkit-scrollbar { width: 4px; }
    aside::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    aside::-webkit-scrollbar-track { background: transparent; }
</style>

<aside id="main-sidebar" class="sidebar-wrapper w-64 h-screen flex flex-col fixed left-0 top-0 z-40 transition-transform duration-300 -translate-x-full md:translate-x-0 font-sans shadow-xl md:shadow-none">
    
    <div class="h-16 flex items-center justify-center px-4 shrink-0" style="border-bottom: 1px solid <?= $borderColor ?>">
        <?php if (!empty($logoUrl)): ?>
            <img src="<?= htmlspecialchars($logoUrl) ?>" 
                 alt="<?= htmlspecialchars($tenantName) ?>" 
                 class="max-h-10 max-w-full object-contain transition-transform hover:scale-105"
                 onerror="this.style.display='none'; document.getElementById('logo-text-fallback').style.display='block';">
            
            <span id="logo-text-fallback" class="font-bold text-lg truncate uppercase tracking-widest hidden" style="color: <?= $textColor ?>">
                <?= htmlspecialchars(substr($tenantName, 0, 15)) ?>
            </span>
        <?php else: ?>
            <span class="font-bold text-lg truncate uppercase tracking-widest" style="color: <?= $textColor ?>">
                Fleet<span style="color: <?= $primaryColor ?>">Vision</span>
            </span>
        <?php endif; ?>
    </div>

    <div class="flex-1 overflow-y-auto py-6 px-4 space-y-6">
        
        <?php if (isset($_GET['debug'])): ?>
            <div class="text-[10px] bg-red-500 text-white p-2 rounded mb-2">
                Slug URL: <?= $detectedSlug ?? 'N/A' ?><br>
                Slug Final: <?= $currentSlug ?><br>
                Cor: <?= $sidebarColor ?>
            </div>
        <?php endif; ?>

        <?php foreach ($menuSections as $sectionTitle => $items): 
            $hasVisible = false;
            foreach ($items as $item) { if ($checkAccess($item['perm'])) { $hasVisible = true; break; } }
            if (!$hasVisible) continue;
        ?>
            <div>
                <h3 class="px-3 text-[10px] font-bold uppercase tracking-widest mb-2 select-none opacity-60" style="color: <?= $textColor ?>">
                    <?= $sectionTitle ?>
                </h3>
                <ul class="space-y-1">
                    <?php foreach ($items as $item): 
                        if (!$checkAccess($item['perm'])) continue;
                        $isActive = ($currentPage === $item['url']);
                    ?>
                        <li>
                            <a href="/<?= $currentSlug ?>/<?= $item['url'] ?>" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 group nav-item-link <?= $isActive ? 'nav-item-active' : '' ?>"
                               style="<?= $isActive ? '' : "color: $textColor;" ?>">
                                
                                <i class="fas <?= $item['icon'] ?> w-5 text-center transition-transform duration-200 group-hover:scale-110"
                                   style="<?= $isActive ? '' : "color: $iconColor" ?>"></i>
                                
                                <span><?= $item['label'] ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="p-4" style="border-top: 1px solid <?= $borderColor ?>; background-color: <?= $isDarkSidebar ? 'rgba(0,0,0,0.2)' : 'rgba(0,0,0,0.02)' ?>">
        <div class="flex items-center gap-3">
            <a href="/<?= $currentSlug ?>/perfil" 
               class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-white shadow-md hover:ring-2 hover:ring-offset-2 transition" 
               style="background-color: <?= $primaryColor ?>; ring-color: <?= $primaryColor ?>" 
               title="Meu Perfil">
                <?= substr($_SESSION['user_name'] ?? 'U', 0, 1) ?>
            </a>
            <div class="flex-1 overflow-hidden">
                <p class="text-xs font-bold truncate" style="color: <?= $textColor ?>" title="<?= $_SESSION['user_name'] ?? '' ?>">
                    <?= $_SESSION['user_name'] ?? 'Visitante' ?>
                </p>
                <p class="text-[10px] truncate capitalize opacity-70" style="color: <?= $textColor ?>">
                    <?= $userRole === 'superadmin' ? 'Super Admin' : ($userRole === 'admin' ? 'Admin' : 'Usuário') ?>
                </p>
            </div>
            <a href="/logout" class="opacity-60 hover:opacity-100 hover:text-red-500 transition px-2" style="color: <?= $textColor ?>" title="Sair">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</aside>