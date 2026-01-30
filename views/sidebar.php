<?php
// views/sidebar.php - VERSÃO MINIMALISTA (Sem bordas, sem rodapé, sem cargo)

if (session_status() === PHP_SESSION_NONE) session_start();

use App\Middleware\AuthMiddleware;
use App\Config\Database;
use PDO;

// 1. CONTEXTO DA URL
$uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($uriPath, '/'));
$currentSlug = $pathParts[0] ?? 'admin';
$pageSlug    = $pathParts[1] ?? 'dashboard';

// 2. BUSCA DADOS DO TENANT
try {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("SELECT name, logo_url, primary_color, sidebar_color, sidebar_text_color FROM saas_tenants WHERE slug = ? LIMIT 1");
    $stmt->execute([$currentSlug]);
    $freshTenant = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($freshTenant) {
        $tenantData = $freshTenant;
        if(isset($_SESSION['tenant_data'])) {
            $_SESSION['tenant_data'] = array_merge($_SESSION['tenant_data'], $freshTenant);
        }
    } else {
        $tenantData = $_SESSION['tenant_data'] ?? [];
    }
} catch (Exception $e) {
    $tenantData = $_SESSION['tenant_data'] ?? [];
}

// 3. DEFINIÇÃO DE CORES
$sidebarColor     = $tenantData['sidebar_color'] ?? '#ffffff';
$sidebarTextColor = $tenantData['sidebar_text_color'] ?? '#334155';
$primaryColor     = $tenantData['primary_color'] ?? '#2563eb';

// Logo
$logoUrl = $tenantData['logo_url'] ?? '';
if (!empty($logoUrl) && $logoUrl[0] !== '/' && strpos($logoUrl, 'http') === false) {
    $logoUrl = '/' . $logoUrl;
}

// Contraste para Hover
$isDarkSidebar = (strtolower($sidebarColor) !== '#ffffff' && strtolower($sidebarColor) !== '#fff');
if ($sidebarColor === '#000000') $isDarkSidebar = true;
$hoverBg = $isDarkSidebar ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.05)';

// 4. MENU DEFINITION
$menuDefinition = [
    'VISÃO GERAL' => [
        ['label' => 'Dashboard',   'icon' => 'fa-chart-pie',      'url' => 'dashboard',  'perm' => 'dashboard_view'],
        ['label' => 'Mapa Real',   'icon' => 'fa-map-marked-alt', 'url' => 'mapa',       'perm' => 'map_view'],
    ],
    'FROTA & OPERAÇÃO' => [
        ['label' => 'Veículos',    'icon' => 'fa-truck',          'url' => 'frota',      'perm' => 'vehicles_view'],
        ['label' => 'Motoristas',  'icon' => 'fa-id-card',        'url' => 'motoristas', 'perm' => 'drivers_view'],
        ['label' => 'Estoque',     'icon' => 'fa-boxes',          'url' => 'estoque',    'perm' => 'stock_view'],
        ['label' => 'Laboratório', 'icon' => 'fa-flask',          'url' => 'teste',      'perm' => 'stock_view'], // Ajuste conforme permissão real
        ['label' => 'Jornada',     'icon' => 'fa-clock',          'url' => 'jornada',    'perm' => 'journey_view'],
        ['label' => 'Ranking',     'icon' => 'fa-trophy',         'url' => 'ranking',    'perm' => 'ranking_view'],
    ],
    'MONITORAMENTO' => [
        ['label' => 'Alertas',     'icon' => 'fa-bell',           'url' => 'alertas',    'perm' => 'alerts_view'],
        ['label' => 'Cercas',      'icon' => 'fa-draw-polygon',   'url' => 'cercas',     'perm' => 'geofences_view'],
        ['label' => 'Histórico',   'icon' => 'fa-route',          'url' => 'historico',  'perm' => 'map_history'],
        ['label' => 'Monitor SGB', 'icon' => 'fa-desktop',        'url' => 'sgbras_monitor', 'perm' => 'map_view'],
    ],
    'ADMINISTRATIVO' => [
        ['label' => 'Clientes',    'icon' => 'fa-users',          'url' => 'clientes',   'perm' => 'customers_view'],
        ['label' => 'Filiais',     'icon' => 'fa-building',       'url' => 'filiais',    'perm' => 'settings_view'],
        ['label' => 'Financeiro',  'icon' => 'fa-file-invoice-dollar', 'url' => 'financeiro', 'perm' => 'financial_view'],
        ['label' => 'Usuários',    'icon' => 'fa-user-cog',       'url' => 'usuarios',   'perm' => 'users_view'],
        ['label' => 'Perfis',      'icon' => 'fa-shield-alt',     'url' => 'perfis',     'perm' => 'users_view'],
        ['label' => 'Ícones',      'icon' => 'fa-icons',          'url' => 'icones',     'perm' => 'settings_view'],
        ['label' => 'Relatórios',  'icon' => 'fa-file-alt',       'url' => 'relatorios', 'perm' => 'reports_view'],
    ],
    'SISTEMA (SUPER)' => [
        ['label' => 'Tenants',     'icon' => 'fa-globe',          'url' => 'admin_usuarios_tenant', 'perm' => '*'],
        ['label' => 'Servidor',    'icon' => 'fa-server',         'url' => 'admin_server',          'perm' => '*'],
        ['label' => 'Gestão Global','icon'=> 'fa-cogs',           'url' => 'admin_gestao',          'perm' => '*'],
        ['label' => 'CRM Admin',   'icon' => 'fa-handshake',      'url' => 'admin_crm',             'perm' => '*'],
        ['label' => 'Finan. Admin','icon' => 'fa-money-check-alt','url' => 'admin_financeiro',      'perm' => '*'],
        ['label' => 'API Docs',    'icon' => 'fa-code',           'url' => 'api_docs',              'perm' => '*'],
        ['label' => 'Debug/Info',  'icon' => 'fa-bug',            'url' => 'admin_debug',           'perm' => '*'],
    ]
];
?>

<style>
    /* Link Ativo com Sombra Suave */
    .sidebar-link.active {
        background-color: <?= $primaryColor ?> !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px -3px <?= $primaryColor ?>90; /* Sombra mais difusa */
        font-weight: 600;
    }
    .sidebar-link:hover:not(.active) {
        background-color: <?= $hoverBg ?> !important;
    }
    
    /* Scrollbar minimalista e invisível até hover */
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: transparent; border-radius: 4px; }
    .custom-scrollbar:hover::-webkit-scrollbar-thumb { background: rgba(150,150,150,0.2); }
</style>

<aside class="w-64 h-screen fixed left-0 top-0 flex flex-col z-50 transition-all"
       style="background-color: <?= $sidebarColor ?>; box-shadow: 4px 0 24px rgba(0,0,0,0.02);"> 
    
    <div class="h-20 flex items-center justify-center px-6 shrink-0 mt-2">
        <?php if (!empty($logoUrl)): ?>
            <img src="<?= $logoUrl ?>" class="max-h-12 max-w-full object-contain">
        <?php else: ?>
            <span class="font-bold text-xl uppercase tracking-widest" style="color: <?= $sidebarTextColor ?>">
                Fleet<span style="color: <?= $primaryColor ?>">Vision</span>
            </span>
        <?php endif; ?>
    </div>

    <div class="flex-1 overflow-y-auto py-2 px-4 space-y-6 custom-scrollbar pb-10">
        <?php foreach ($menuDefinition as $sectionTitle => $items): 
            $visibleItems = array_filter($items, function($item) {
                return AuthMiddleware::hasPermission($item['perm']);
            });
            if (empty($visibleItems)) continue;
        ?>
            <div>
                <h3 class="px-3 text-[10px] font-bold uppercase tracking-widest mb-3 opacity-40" style="color: <?= $sidebarTextColor ?>">
                    <?= $sectionTitle ?>
                </h3>
                
                <ul class="space-y-1.5">
                    <?php foreach ($visibleItems as $item): 
                        $isActive = ($pageSlug === $item['url']);
                        $linkTextColor = $isActive ? '#ffffff' : $sidebarTextColor;
                    ?>
                        <li>
                            <a href="/<?= $currentSlug ?>/<?= $item['url'] ?>" 
                               class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group <?= $isActive ? 'active' : '' ?>"
                               style="color: <?= $linkTextColor ?>">
                                
                                <i class="fas <?= $item['icon'] ?> w-5 text-center transition-transform group-hover:scale-110"></i>
                                <span><?= $item['label'] ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </div>

    </aside>