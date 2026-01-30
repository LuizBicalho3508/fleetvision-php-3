<?php
// views/sidebar.php - VERSÃO CORRIGIDA COM FALLBACK SUPER ADMIN

if (session_status() === PHP_SESSION_NONE) session_start();

// =========================================================
// 1. DETECÇÃO ROBUSTA
// =========================================================
$uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uriParts = explode('/', trim($uriPath, '/'));
$reservedPaths = ['sys', 'api', 'assets', 'login', 'dashboard', 'logout', 'landing'];

// Tenta pegar o slug da URL
$detectedSlug = null;
if (isset($uriParts[0]) && !in_array($uriParts[0], $reservedPaths)) {
    $detectedSlug = $uriParts[0];
}

// Slug Final (Sessão só se URL falhar)
$currentSlug = $detectedSlug ?? ($_SESSION['tenant_slug'] ?? 'admin');
$currentSlug = strtolower(trim($currentSlug));

// É o Painel Admin?
$isAdminPanel = ($currentSlug === 'admin');

// Permissões
$userRole = strtolower(trim($_SESSION['user_role'] ?? 'guest'));
$isSuperAdmin = in_array($userRole, ['superadmin', 'admin']); 

// =========================================================
// 2. BUSCA DADOS VISUAIS
// =========================================================
if (!function_exists('get_sidebar_safe_url')) {
    function get_sidebar_safe_url($dbUrl) {
        if (empty($dbUrl)) return '';
        if (filter_var($dbUrl, FILTER_VALIDATE_URL)) return $dbUrl;
        $cleanPath = ltrim(trim($dbUrl), '/');
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        $baseUrl = ($scriptDir === '/' || $scriptDir === '\\') ? '' : $scriptDir;
        return str_replace('\\', '/', $baseUrl) . '/' . $cleanPath;
    }
}

$tenantData = null;
try {
    if (class_exists('App\Config\Database')) {
        $pdo = \App\Config\Database::getConnection();
        $stmt = $pdo->prepare("SELECT name, logo_url, primary_color, sidebar_color FROM saas_tenants WHERE slug = ? LIMIT 1");
        $stmt->execute([$currentSlug]);
        $tenantData = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) { }

// Configuração Visual
$tenantName   = $tenantData['name'] ?? 'FleetVision';
$logoUrl      = get_sidebar_safe_url($tenantData['logo_url'] ?? '');
// Se for Admin Panel e não tiver cor definida, usa um fundo escuro profissional
$sidebarColor = !empty($tenantData['sidebar_color']) ? $tenantData['sidebar_color'] : ($isAdminPanel ? '#0f172a' : '#ffffff');
$primaryColor = !empty($tenantData['primary_color']) ? $tenantData['primary_color'] : '#2563eb';

// Contraste
$isDarkSidebar = (strtolower($sidebarColor) !== '#ffffff' && strtolower($sidebarColor) !== '#fff' && strtolower($sidebarColor) !== 'white');
if ($sidebarColor === '#000000') $isDarkSidebar = true;

$textColor   = $isDarkSidebar ? '#ffffff' : '#475569';
$borderColor = $isDarkSidebar ? 'rgba(255,255,255,0.1)' : '#e2e8f0';
$iconColor   = $isDarkSidebar ? 'rgba(255,255,255,0.7)' : '#94a3b8';

// =========================================================
// 3. ESTRUTURA DE MENUS
// =========================================================

$menuSections = [];

// --- CENÁRIO A: ESTOU NA URL /admin/ (PAINEL MASTER) ---
if ($isAdminPanel) {
    $menuSections = [
        'GESTÃO MASTER' => [
            ['label' => 'Dashboard',       'icon' => 'fa-chart-line',    'url' => 'dashboard',             'perm' => 'all'],
            ['label' => 'Tenants',         'icon' => 'fa-building',      'url' => 'admin_usuarios_tenant', 'perm' => 'all'],
            ['label' => 'CRM / Leads',     'icon' => 'fa-bullhorn',      'url' => 'admin_crm',             'perm' => 'all'],
            ['label' => 'Financeiro SaaS', 'icon' => 'fa-dollar-sign',   'url' => 'admin_financeiro',      'perm' => 'all'],
        ],
        'SISTEMA & CONFIG' => [
            ['label' => 'Status Servidor', 'icon' => 'fa-server',        'url' => 'admin_server',          'perm' => 'all'],
            ['label' => 'Personalização',  'icon' => 'fa-paint-brush',   'url' => 'admin_gestao',          'perm' => 'all'],
            ['label' => 'Ícones',          'icon' => 'fa-icons',         'url' => 'icones',                'perm' => 'all'],
            ['label' => 'Filiais',         'icon' => 'fa-network-wired', 'url' => 'filiais',               'perm' => 'all'],
            ['label' => 'Doc. API',        'icon' => 'fa-code',          'url' => 'api_docs',              'perm' => 'all'],
        ],
        'RELATÓRIOS' => [
            ['label' => 'Gerar Relatório', 'icon' => 'fa-print',         'url' => 'relatorios_gerar',      'perm' => 'all'],
            ['label' => 'Histórico',       'icon' => 'fa-file-alt',      'url' => 'relatorios',            'perm' => 'all'],
        ],
        'ACESSO' => [
            ['label' => 'Usuários Admin',  'icon' => 'fa-users-cog',     'url' => 'usuarios',              'perm' => 'all'],
            ['label' => 'Minha Conta',     'icon' => 'fa-user-circle',   'url' => 'perfil',                'perm' => 'all'],
        ]
    ];
} 
// --- CENÁRIO B: ESTOU EM UM CLIENTE (ex: /cliente-x/) ---
else {
    $menuSections = [
        'VISÃO GERAL' => [
            ['label' => 'Dashboard',   'icon' => 'fa-chart-pie',      'url' => 'dashboard',  'perm' => 'dashboard_view'],
            ['label' => 'Mapa Real',   'icon' => 'fa-map-marked-alt', 'url' => 'mapa',       'perm' => 'map_view'],
        ],
        'FROTA' => [
            ['label' => 'Veículos',    'icon' => 'fa-truck',          'url' => 'frota',      'perm' => 'vehicles_view'],
            ['label' => 'Motoristas',  'icon' => 'fa-id-card',        'url' => 'motoristas', 'perm' => 'drivers_view'],
            ['label' => 'Estoque',     'icon' => 'fa-boxes',          'url' => 'estoque',    'perm' => 'stock_view'],
            ['label' => 'Jornada',     'icon' => 'fa-clock',          'url' => 'jornada',    'perm' => 'journey_view'],
            ['label' => 'Ranking',     'icon' => 'fa-trophy',         'url' => 'ranking',    'perm' => 'ranking_view'],
        ],
        'MONITORAMENTO' => [
            ['label' => 'Alertas',     'icon' => 'fa-bell',           'url' => 'alertas',    'perm' => 'alerts_view'],
            ['label' => 'Cercas',      'icon' => 'fa-draw-polygon',   'url' => 'cercas',     'perm' => 'geofences_view'],
            ['label' => 'Histórico',   'icon' => 'fa-history',        'url' => 'historico',  'perm' => 'history_view'],
            ['label' => 'Laboratório', 'icon' => 'fa-flask',          'url' => 'teste',      'perm' => 'all'],
        ],
        'GESTÃO' => [
            ['label' => 'Clientes',    'icon' => 'fa-user-tie',       'url' => 'clientes',   'perm' => 'customers_view'],
            ['label' => 'Filiais',     'icon' => 'fa-building',       'url' => 'filiais',    'perm' => 'branches_view'],
            ['label' => 'Financeiro',  'icon' => 'fa-file-invoice-dollar', 'url' => 'financeiro', 'perm' => 'financial_view'],
            ['label' => 'Usuários',    'icon' => 'fa-users-cog',      'url' => 'usuarios',   'perm' => 'users_view'],
            ['label' => 'Perfis',      'icon' => 'fa-shield-alt',     'url' => 'perfis',     'perm' => 'users_view'],
            ['label' => 'Relatórios',  'icon' => 'fa-file-alt',       'url' => 'relatorios', 'perm' => 'reports_view'],
            ['label' => 'Câmeras SGBras', 'icon' => 'fa-video', 'url' => 'sgbras_monitor', 'perm' => 'all'],
        ],
        'CONFIGURAÇÕES' => [
            ['label' => 'Minha Conta', 'icon' => 'fa-user-circle',    'url' => 'perfil',     'perm' => 'all'],
        ]
    ];

    // --- FALLBACK: SUPER ADMIN VÊ AS FERRAMENTAS MESMO NO CLIENTE ---
    if ($isSuperAdmin) {
        $menuSections['SUPER ADMIN'] = [
            ['label' => 'Tenants',         'icon' => 'fa-building',    'url' => 'admin_usuarios_tenant', 'perm' => 'all'],
            ['label' => 'Personalização',  'icon' => 'fa-paint-brush', 'url' => 'admin_gestao',          'perm' => 'all'],
            ['label' => 'Status Servidor', 'icon' => 'fa-server',      'url' => 'admin_server',          'perm' => 'all'],
            ['label' => 'Financeiro SaaS', 'icon' => 'fa-dollar-sign', 'url' => 'admin_financeiro',      'perm' => 'all'],
            ['label' => 'Debug / Logs',    'icon' => 'fa-bug',         'url' => 'admin_debug',           'perm' => 'all'],
        ];
    }
}

// Checagem de Acesso
$checkAccess = function($perm) use ($isAdminPanel, $isSuperAdmin) {
    if ($isAdminPanel) return true; // No painel admin, libera tudo
    if ($isSuperAdmin) return true; // Superadmin vê tudo
    if ($perm === 'all') return true;
    if (function_exists('hasPermission') && hasPermission($perm)) return true;
    return false;
};

// Highlight
$pathSegments = explode('/', trim($uriPath, '/'));
$currentPage = end($pathSegments);
if ($currentPage == $currentSlug || empty($currentPage)) $currentPage = 'dashboard';
?>

<style>
    .sidebar-wrapper {
        background-color: <?= $sidebarColor ?> !important;
        border-right: 1px solid <?= $borderColor ?> !important;
    }
    .nav-item-active {
        background-color: <?= $primaryColor ?> !important;
        color: #ffffff !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    .nav-item-active i { color: #ffffff !important; }
    .nav-item-link:hover:not(.nav-item-active) {
        background-color: <?= $isDarkSidebar ? 'rgba(255,255,255,0.1)' : '#f8fafc' ?> !important;
        color: <?= $primaryColor ?> !important;
    }
    .nav-item-link:hover:not(.nav-item-active) i {
        color: <?= $primaryColor ?> !important;
    }
    aside::-webkit-scrollbar { width: 4px; }
    aside::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
</style>

<aside id="main-sidebar" class="sidebar-wrapper w-64 h-screen flex flex-col fixed left-0 top-0 z-40 transition-transform duration-300 -translate-x-full md:translate-x-0 font-sans shadow-xl md:shadow-none">
    
    <div class="h-16 flex items-center justify-center px-4 shrink-0" style="border-bottom: 1px solid <?= $borderColor ?>">
        <?php if (!empty($logoUrl)): ?>
            <img src="<?= htmlspecialchars($logoUrl) ?>" 
                 alt="<?= htmlspecialchars($tenantName) ?>" 
                 class="max-h-10 max-w-full object-contain hover:scale-105 transition"
                 onerror="this.style.display='none'; document.getElementById('logo-text').classList.remove('hidden');">
            <span id="logo-text" class="font-bold text-lg uppercase hidden tracking-widest" style="color: <?= $textColor ?>">
                <?= substr($tenantName, 0, 15) ?>
            </span>
        <?php else: ?>
            <span class="font-bold text-lg uppercase tracking-widest" style="color: <?= $textColor ?>">
                Fleet<span style="color: <?= $primaryColor ?>">Vision</span>
            </span>
        <?php endif; ?>
    </div>

    <div class="px-2 pt-2">
        <div class="text-[9px] bg-slate-800 text-white p-1 rounded opacity-50 hover:opacity-100 transition cursor-help text-center" title="Debug Info">
            Slug: <?= $currentSlug ?> | Role: <?= $userRole ?> | AdminPanel: <?= $isAdminPanel ? 'SIM' : 'NÃO' ?>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto py-4 px-4 space-y-6">
        <?php foreach ($menuSections as $sectionTitle => $items): 
            $hasVisible = false;
            foreach ($items as $item) { 
                $perm = $item['perm'] ?? 'all';
                if ($checkAccess($perm)) { $hasVisible = true; break; } 
            }
            if (!$hasVisible) continue;
        ?>
            <div>
                <h3 class="px-3 text-[10px] font-bold uppercase tracking-widest mb-2 opacity-60" style="color: <?= $textColor ?>">
                    <?= $sectionTitle ?>
                </h3>
                <ul class="space-y-1">
                    <?php foreach ($items as $item): 
                        $perm = $item['perm'] ?? 'all';
                        if (!$checkAccess($perm)) continue;
                        $isActive = ($currentPage === $item['url']);
                    ?>
                        <li>
                            <a href="/<?= $currentSlug ?>/<?= $item['url'] ?>" 
                               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition group nav-item-link <?= $isActive ? 'nav-item-active' : '' ?>"
                               style="<?= $isActive ? '' : "color: $textColor;" ?>">
                                
                                <i class="fas <?= $item['icon'] ?> w-5 text-center transition group-hover:scale-110" 
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
            <a href="/<?= $currentSlug ?>/perfil" class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-white shadow-md hover:ring-2 hover:ring-offset-1 transition" style="background-color: <?= $primaryColor ?>">
                <?= substr($_SESSION['user_name'] ?? 'U', 0, 1) ?>
            </a>
            <div class="flex-1 overflow-hidden">
                <p class="text-xs font-bold truncate" style="color: <?= $textColor ?>">
                    <?= $_SESSION['user_name'] ?? 'Usuário' ?>
                </p>
                <p class="text-[10px] truncate capitalize opacity-70" style="color: <?= $textColor ?>">
                    <?= $userRole ?>
                </p>
            </div>
            <a href="/logout" class="opacity-60 hover:opacity-100 hover:text-red-500 transition px-2" style="color: <?= $textColor ?>" title="Sair">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</aside>