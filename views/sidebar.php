<?php
// views/components/sidebar.php

if (session_status() === PHP_SESSION_NONE) session_start();

$tenant = $_SESSION['tenant_data'] ?? [];
$slug   = $_SESSION['tenant_slug'] ?? 'admin';

// Normaliza o papel do usuário para evitar erros de Case Sensitive (Admin vs admin)
$userRole = strtolower($_SESSION['user_role'] ?? 'guest'); 

// Cores e Logo
$primaryColor   = $tenant['primary_color'] ?? '#2563eb';
$logoUrl        = !empty($tenant['logo_url']) ? $tenant['logo_url'] : null;
$tenantName     = $tenant['name'] ?? 'FleetVision';

// Identifica Página Ativa
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($uri, '/'));
$currentPage = end($pathParts);
if ($currentPage == $slug || empty($currentPage)) $currentPage = 'dashboard';

// --- DEFINIÇÃO DOS MENUS ---
$menuSections = [
    'VISÃO GERAL' => [
        ['label' => 'Dashboard',   'icon' => 'fa-chart-pie',      'url' => 'dashboard',  'perm' => 'dashboard_view'],
        ['label' => 'Mapa Real',   'icon' => 'fa-map-marked-alt', 'url' => 'mapa',       'perm' => 'map_view'],
    ],
    'GESTÃO DE FROTA' => [
        ['label' => 'Veículos',    'icon' => 'fa-truck',          'url' => 'veiculos',   'perm' => 'vehicles_view'],
        ['label' => 'Motoristas',  'icon' => 'fa-id-card',        'url' => 'motoristas', 'perm' => 'drivers_view'],
        ['label' => 'Estoque',     'icon' => 'fa-boxes',          'url' => 'estoque',    'perm' => 'stock_view'],
        ['label' => 'Laboratório', 'icon' => 'fa-flask',          'url' => 'teste',      'perm' => 'all'],
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
        ['label' => 'Financeiro',  'icon' => 'fa-file-invoice-dollar', 'url' => 'financeiro', 'perm' => 'financial_view'],
        ['label' => 'Usuários',    'icon' => 'fa-users-cog',      'url' => 'usuarios',   'perm' => 'users_view'],
        ['label' => 'Perfis',      'icon' => 'fa-shield-alt',     'url' => 'perfis',     'perm' => 'users_view'], 
        ['label' => 'Relatórios',  'icon' => 'fa-file-alt',       'url' => 'relatorios', 'perm' => 'reports_view'],
    ],
];

// Seção exclusiva do SuperAdmin (Adicionada ao array principal se for superadmin)
if ($userRole === 'superadmin') {
    $menuSections['SUPER ADMIN'] = [
        ['label' => 'Gestão Tenants', 'icon' => 'fa-building',    'url' => 'admin_usuarios_tenant', 'perm' => 'all'],
        ['label' => 'Financeiro SaaS','icon' => 'fa-dollar-sign', 'url' => 'admin_financeiro',      'perm' => 'all'],
        ['label' => 'Design Login',   'icon' => 'fa-paint-brush', 'url' => 'admin_gestao',          'perm' => 'all'],
        ['label' => 'Status Servidor','icon' => 'fa-server',      'url' => 'admin_server',          'perm' => 'all'],
    ];
}
?>

<style>
    .nav-item-active {
        background-color: <?= $primaryColor ?> !important;
        color: white !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    .nav-item:hover:not(.nav-item-active) {
        color: <?= $primaryColor ?>;
        background-color: #f8fafc;
    }
    aside::-webkit-scrollbar { width: 4px; }
    aside::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
</style>

<aside id="main-sidebar" class="bg-white w-64 h-screen border-r border-slate-200 flex flex-col fixed left-0 top-0 z-40 transition-transform duration-300 -translate-x-full md:translate-x-0 shadow-xl md:shadow-none">
    
    <div class="h-16 flex items-center justify-center border-b border-slate-100 px-4 shrink-0 bg-white">
        <?php if ($logoUrl): ?>
            <img src="<?= $logoUrl ?>" alt="Logo" class="max-h-8 max-w-full object-contain">
        <?php else: ?>
            <span class="font-bold text-slate-800 text-lg truncate uppercase tracking-widest">
                Fleet<span style="color: <?= $primaryColor ?>">Vision</span>
            </span>
        <?php endif; ?>
    </div>

    <div class="flex-1 overflow-y-auto py-6 px-4 space-y-6">
        <?php foreach ($menuSections as $sectionTitle => $items): 
            
            // --- PASSO 1: Verificar se a SEÇÃO deve ser exibida ---
            $hasSectionAccess = false;

            // REGRA MESTRE: Admin e Superadmin veem TUDO
            if (in_array($userRole, ['superadmin', 'admin'])) {
                $hasSectionAccess = true;
            } else {
                // Usuários comuns: verifica item a item
                foreach ($items as $item) {
                    if ($item['perm'] === 'all' || (function_exists('hasPermission') && hasPermission($item['perm']))) {
                        $hasSectionAccess = true;
                        break;
                    }
                }
            }

            // Se não tiver acesso a nada nesta seção, pula o título
            if (!$hasSectionAccess) continue;
        ?>
            <div>
                <h3 class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 select-none">
                    <?= $sectionTitle ?>
                </h3>
                <ul class="space-y-1">
                    <?php foreach ($items as $item): 
                        
                        // --- PASSO 2: Verificar se o ITEM deve ser exibido ---
                        $showItem = false;

                        // 1. Admin/Superadmin: Acesso Total (Bypass Permissões)
                        if (in_array($userRole, ['superadmin', 'admin'])) {
                            $showItem = true;
                        }
                        // 2. Item Público
                        elseif ($item['perm'] === 'all') {
                            $showItem = true;
                        }
                        // 3. Usuário com Permissão Específica
                        elseif (function_exists('hasPermission') && hasPermission($item['perm'])) {
                            $showItem = true;
                        }

                        if (!$showItem) continue;

                        $isActive = ($currentPage === $item['url']);
                    ?>
                        <li>
                            <a href="/<?= $slug ?>/<?= $item['url'] ?>" 
                               class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 <?= $isActive ? 'nav-item-active' : 'text-slate-500' ?>">
                                <i class="fas <?= $item['icon'] ?> w-5 text-center"></i>
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
            <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-white shadow-sm" style="background-color: <?= $primaryColor ?>">
                <?= substr($_SESSION['user_name'] ?? 'U', 0, 1) ?>
            </div>
            <div class="flex-1 overflow-hidden">
                <p class="text-xs font-bold truncate text-slate-700"><?= $_SESSION['user_name'] ?? 'Usuário' ?></p>
                <p class="text-[10px] text-slate-400 truncate capitalize"><?= $userRole ?></p>
            </div>
            <a href="/logout" class="text-slate-400 hover:text-red-500 transition px-2" title="Sair">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</aside>