<?php
// views/sidebar.php

// 1. Garante sessão e define variáveis básicas
if (session_status() === PHP_SESSION_NONE) session_start();

$tenant   = $_SESSION['tenant_data'] ?? [];
$slug     = $_SESSION['tenant_slug'] ?? 'admin';
$userRole = strtolower($_SESSION['user_role'] ?? 'guest'); // Normaliza para minúsculo

// 2. Cores e Identidade Visual
$primaryColor = $tenant['primary_color'] ?? '#2563eb';
$tenantName   = $tenant['name'] ?? 'FleetVision';

// --- CORREÇÃO DA LOGO ---
// Se a logo não for uma URL externa (http) e não começar com barra, adiciona a barra.
$logoUrl = !empty($tenant['logo_url']) ? $tenant['logo_url'] : null;
if ($logoUrl && strpos($logoUrl, 'http') === false && substr($logoUrl, 0, 1) !== '/') {
    $logoUrl = '/' . $logoUrl;
}

// 3. Identifica Página Ativa
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($uri, '/'));
$currentPage = end($pathParts);
// Fallback para dashboard se estiver na raiz do slug
if ($currentPage == $slug || empty($currentPage)) $currentPage = 'dashboard';

// --- DEFINIÇÃO COMPLETA DO MENU ---
$menuSections = [
    'VISÃO GERAL' => [
        ['label' => 'Dashboard',   'icon' => 'fa-chart-pie',      'url' => 'dashboard',  'perm' => 'dashboard_view'],
        ['label' => 'Mapa Real',   'icon' => 'fa-map-marked-alt', 'url' => 'mapa',       'perm' => 'map_view'],
    ],
    'GESTÃO DE FROTA' => [
        ['label' => 'Veículos',    'icon' => 'fa-truck',          'url' => 'veiculos',   'perm' => 'vehicles_view'],
        ['label' => 'Motoristas',  'icon' => 'fa-id-card',        'url' => 'motoristas', 'perm' => 'drivers_view'],
        ['label' => 'Estoque',     'icon' => 'fa-boxes',          'url' => 'estoque',    'perm' => 'stock_view'],
        ['label' => 'Laboratório', 'icon' => 'fa-flask',          'url' => 'teste',      'perm' => 'all'], // view: teste.php
        ['label' => 'Jornada',     'icon' => 'fa-clock',          'url' => 'jornada',    'perm' => 'journey_view'],
        ['label' => 'Ranking',     'icon' => 'fa-trophy',         'url' => 'ranking',    'perm' => 'ranking_view'],
    ],
    'MONITORAMENTO' => [
        ['label' => 'Alertas',     'icon' => 'fa-bell',           'url' => 'alertas',    'perm' => 'alerts_view'],
        ['label' => 'Cercas',      'icon' => 'fa-draw-polygon',   'url' => 'cercas',     'perm' => 'geofences_view'],
        ['label' => 'Histórico',   'icon' => 'fa-history',        'url' => 'historico',  'perm' => 'history_view'],
    ],
    'ADMINISTRATIVO' => [
        ['label' => 'Clientes',    'icon' => 'fa-user-tie',       'url' => 'clientes',   'perm' => 'customers_view'],
        ['label' => 'Filiais',     'icon' => 'fa-building',       'url' => 'filiais',    'perm' => 'branches_view'], // view: filiais.php
        ['label' => 'Financeiro',  'icon' => 'fa-file-invoice-dollar', 'url' => 'financeiro', 'perm' => 'financial_view'],
        ['label' => 'Usuários',    'icon' => 'fa-users-cog',      'url' => 'usuarios',   'perm' => 'users_view'],
        ['label' => 'Perfis',      'icon' => 'fa-shield-alt',     'url' => 'perfis',     'perm' => 'users_view'],    // view: perfis.php
        ['label' => 'Ícones',      'icon' => 'fa-icons',          'url' => 'icones',     'perm' => 'icons_view'],    // view: icones.php
        ['label' => 'Relatórios',  'icon' => 'fa-file-alt',       'url' => 'relatorios', 'perm' => 'reports_view'],
        ['label' => 'Doc. API',    'icon' => 'fa-code',           'url' => 'api_docs',   'perm' => 'docs_view'],     // view: api_docs.php
    ],
    'CONFIGURAÇÕES' => [
        ['label' => 'Minha Conta', 'icon' => 'fa-user-circle',    'url' => 'perfil',     'perm' => 'all'],           // view: perfil.php
    ]
];

// --- MENU EXCLUSIVO DO SUPERADMIN ---
if ($userRole === 'superadmin') {
    $menuSections['SUPER ADMIN'] = [
        ['label' => 'Gestão Tenants', 'icon' => 'fa-building',    'url' => 'admin_usuarios_tenant', 'perm' => 'all'],
        ['label' => 'CRM / Leads',    'icon' => 'fa-bullhorn',    'url' => 'admin_crm',             'perm' => 'all'], // view: admin_crm.php
        ['label' => 'Financeiro SaaS','icon' => 'fa-dollar-sign', 'url' => 'admin_financeiro',      'perm' => 'all'],
        ['label' => 'Design Login',   'icon' => 'fa-paint-brush', 'url' => 'admin_gestao',          'perm' => 'all'],
        ['label' => 'Status Servidor','icon' => 'fa-server',      'url' => 'admin_server',          'perm' => 'all'],
        ['label' => 'Debug System',   'icon' => 'fa-bug',         'url' => 'admin_debug',           'perm' => 'all'], // view: admin_debug.php
    ];
}

// Verifica se é um administrador (Super ou Tenant) para bypass de permissões
$isSystemAdmin = in_array($userRole, ['superadmin', 'admin']);

// Função auxiliar (Closure) para verificar acesso ao item
$checkAccess = function($perm) use ($isSystemAdmin) {
    if ($isSystemAdmin) return true; // Admin vê tudo
    if ($perm === 'all') return true; // Item público
    if (function_exists('hasPermission') && hasPermission($perm)) return true; // Permissão específica
    return false;
};
?>

<style>
    /* Item Ativo: Usa a cor do tenant */
    .nav-item-active {
        background-color: <?= $primaryColor ?> !important;
        color: white !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    /* Hover no item inativo */
    .nav-item:hover:not(.nav-item-active) {
        color: <?= $primaryColor ?>;
        background-color: #f8fafc;
    }
    /* Scrollbar fina e elegante */
    aside::-webkit-scrollbar { width: 4px; }
    aside::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    aside::-webkit-scrollbar-track { background: transparent; }
</style>

<aside id="main-sidebar" class="bg-white w-64 h-screen border-r border-slate-200 flex flex-col fixed left-0 top-0 z-40 transition-transform duration-300 -translate-x-full md:translate-x-0 shadow-xl md:shadow-none font-sans">
    
    <div class="h-16 flex items-center justify-center border-b border-slate-100 px-4 shrink-0 bg-white">
        <?php if ($logoUrl): ?>
            <img src="<?= $logoUrl ?>" 
                 alt="<?= $tenantName ?>" 
                 class="max-h-10 max-w-full object-contain transition-all hover:scale-105"
                 onerror="this.style.display='none'; document.getElementById('logo-fallback').style.display='block';">
            
            <span id="logo-fallback" class="font-bold text-slate-800 text-lg truncate uppercase tracking-widest hidden">
                Fleet<span style="color: <?= $primaryColor ?>">Vision</span>
            </span>
        <?php else: ?>
            <span class="font-bold text-slate-800 text-lg truncate uppercase tracking-widest">
                Fleet<span style="color: <?= $primaryColor ?>">Vision</span>
            </span>
        <?php endif; ?>
    </div>

    <div class="flex-1 overflow-y-auto py-6 px-4 space-y-6">
        <?php foreach ($menuSections as $sectionTitle => $items): 
            
            // Verifica se há pelo menos um item visível nesta seção
            $hasVisibleItems = false;
            foreach ($items as $item) {
                if ($checkAccess($item['perm'])) {
                    $hasVisibleItems = true;
                    break;
                }
            }

            // Se a seção estiver vazia para este usuário, pula
            if (!$hasVisibleItems) continue;
        ?>
            <div>
                <h3 class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 select-none">
                    <?= $sectionTitle ?>
                </h3>
                <ul class="space-y-1">
                    <?php foreach ($items as $item): 
                        if (!$checkAccess($item['perm'])) continue;
                        $isActive = ($currentPage === $item['url']);
                    ?>
                        <li>
                            <a href="/<?= $slug ?>/<?= $item['url'] ?>" 
                               class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 group <?= $isActive ? 'nav-item-active' : 'text-slate-500' ?>">
                                <i class="fas <?= $item['icon'] ?> w-5 text-center group-hover:scale-110 transition-transform duration-200"></i>
                                <span><?= $item['label'] ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="p-4 border-t border-slate-100 bg-slate-50">
        <div class="flex items-center gap-3">
            <a href="/<?= $slug ?>/perfil" class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-white shadow-md hover:ring-2 hover:ring-offset-2 transition ring-slate-200" style="background-color: <?= $primaryColor ?>" title="Meu Perfil">
                <?= substr($_SESSION['user_name'] ?? 'U', 0, 1) ?>
            </a>
            <div class="flex-1 overflow-hidden">
                <p class="text-xs font-bold truncate text-slate-700" title="<?= $_SESSION['user_name'] ?? '' ?>">
                    <?= $_SESSION['user_name'] ?? 'Visitante' ?>
                </p>
                <p class="text-[10px] text-slate-400 truncate capitalize">
                    <?= $userRole === 'admin' ? 'Administrador' : ($userRole === 'superadmin' ? 'Super Admin' : 'Usuário') ?>
                </p>
            </div>
            <a href="/logout" class="text-slate-400 hover:text-red-500 transition px-2" title="Sair do Sistema">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</aside>