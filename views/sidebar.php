<?php
// views/sidebar.php
global $slug, $tenant; // Garante acesso global
$page = $pageName ?? 'dashboard';
$sideSlug = $slug ?? 'admin';
$role = $_SESSION['user_role'] ?? 'user';
?>

<aside id="main-sidebar" class="w-64 bg-slate-900 text-white flex flex-col shadow-2xl z-40 flex-shrink-0 transition-all duration-300 ease-in-out overflow-hidden" style="background-color: var(--secondary);">
    
    <div class="h-16 flex items-center justify-center border-b border-white/10 px-4 flex-shrink-0">
        <?php if(!empty($tenant['logo_url'])): ?>
            <img src="<?php echo $tenant['logo_url']; ?>" class="max-h-10 object-contain transition-all duration-300">
        <?php else: ?>
            <h1 class="text-lg font-bold uppercase tracking-widest truncate text-white">
                FLEET<span class="text-blue-500">VISION</span>
            </h1>
        <?php endif; ?>
    </div>

    <nav class="flex-1 overflow-y-auto py-2 overflow-x-hidden custom-scroll">
        
        <a href="/<?php echo $sideSlug; ?>/dashboard" class="sidebar-link <?php echo $page=='dashboard'?'active':''; ?>">
            <i class="fas fa-chart-pie w-5 mr-2 text-center"></i> <span>Dashboard</span>
        </a>

        <?php if(hasPermission('map_view')): ?>
        <a href="/<?php echo $sideSlug; ?>/mapa" class="sidebar-link <?php echo $page=='mapa'?'active':''; ?>">
            <i class="fas fa-map w-5 mr-2 text-center"></i> <span>Mapa & Grid</span>
        </a>
        <?php endif; ?>

        <div class="sidebar-header">Operacional</div>
        
        <?php if(hasPermission('vehicles_view')): ?>
        <a href="/<?php echo $sideSlug; ?>/frota" class="sidebar-link <?php echo $page=='frota'?'active':''; ?>">
            <i class="fas fa-truck w-5 mr-2 text-center"></i> <span>Veículos</span>
        </a>
        <?php endif; ?>

        <?php if(hasPermission('drivers_view')): ?>
        <a href="/<?php echo $sideSlug; ?>/motoristas" class="sidebar-link <?php echo $page=='motoristas'?'active':''; ?>">
            <i class="fas fa-id-card w-5 mr-2 text-center"></i> <span>Motoristas</span>
        </a>
        <?php endif; ?>

        <?php if(hasPermission('geofences_view')): ?>
        <a href="/<?php echo $sideSlug; ?>/cercas" class="sidebar-link <?php echo $page=='cercas'?'active':''; ?>">
            <i class="fas fa-draw-polygon w-5 mr-2 text-center"></i> <span>Cercas Virtuais</span>
        </a>
        <?php endif; ?>

        <?php if(hasPermission('alerts_view')): ?>
        <a href="/<?php echo $sideSlug; ?>/alertas" class="sidebar-link <?php echo $page=='alertas'?'active':''; ?>">
            <i class="fas fa-bell w-5 mr-2 text-center"></i> <span>Alertas</span>
        </a>
        <?php endif; ?>

        <?php if(hasPermission('map_history')): ?>
        <a href="/<?php echo $sideSlug; ?>/historico" class="sidebar-link <?php echo $page=='historico'?'active':''; ?>">
            <i class="fas fa-history w-5 mr-2 text-center"></i> <span>Replay de Rota</span>
        </a>
        <?php endif; ?>
        
        <?php if(hasPermission('journal_view')): ?>
        <a href="/<?php echo $sideSlug; ?>/jornada" class="sidebar-link <?php echo $page=='jornada'?'active':''; ?>">
            <i class="fas fa-business-time w-5 mr-2 text-center"></i> <span>Jornada</span>
        </a>
        <?php endif; ?>

        <div class="sidebar-header">Gestão</div>
        
        <?php if(hasPermission('customers_view')): ?>
        <a href="/<?php echo $sideSlug; ?>/clientes" class="sidebar-link <?php echo $page=='clientes'?'active':''; ?>">
            <i class="fas fa-users w-5 mr-2 text-center"></i> <span>Clientes</span>
        </a>
        <?php endif; ?>
        
        <?php if(hasPermission('financial_view')): ?>
        <a href="/<?php echo $sideSlug; ?>/financeiro" class="sidebar-link <?php echo $page=='financeiro'?'active':''; ?>">
            <i class="fas fa-file-invoice-dollar w-5 mr-2 text-center"></i> <span>Financeiro</span>
        </a>
        <?php endif; ?>

        <?php if(hasPermission('reports_view')): ?>
        <a href="/<?php echo $sideSlug; ?>/relatorios" class="sidebar-link <?php echo $page=='relatorios'?'active':''; ?>">
            <i class="fas fa-file-alt w-5 mr-2 text-center"></i> <span>Relatórios</span>
        </a>
        <?php endif; ?>

        <?php if(hasPermission('users_view') || hasPermission('roles_manage')): ?>
        <div class="sidebar-header">Administração</div>
        
        <a href="/<?php echo $sideSlug; ?>/usuarios" class="sidebar-link <?php echo $page=='usuarios'?'active':''; ?>">
            <i class="fas fa-users-cog w-5 mr-2 text-center"></i> <span>Usuários</span>
        </a>

        <a href="/<?php echo $sideSlug; ?>/perfis" class="sidebar-link <?php echo $page=='perfis'?'active':''; ?>">
            <i class="fas fa-user-shield w-5 mr-2 text-center"></i> <span>Perfis de Acesso</span>
        </a>
        <?php endif; ?>
        
        <?php if(hasPermission('branches_manage')): ?>
        <a href="/<?php echo $sideSlug; ?>/filiais" class="sidebar-link <?php echo $page=='filiais'?'active':''; ?>">
            <i class="fas fa-code-branch w-5 mr-2 text-center"></i> <span>Filiais</span>
        </a>
        <?php endif; ?>

        <div class="sidebar-header">Técnico</div>
        
        <?php if(hasPermission('stock_view')): ?>
        <a href="/<?php echo $sideSlug; ?>/estoque" class="sidebar-link <?php echo $page=='estoque'?'active':''; ?>">
            <i class="fas fa-boxes w-5 mr-2 text-center"></i> <span>Estoque</span>
        </a>
        <?php endif; ?>

        <?php if(hasPermission('tech_config')): ?>
        <a href="/<?php echo $sideSlug; ?>/icones" class="sidebar-link <?php echo $page=='icones'?'active':''; ?>">
            <i class="fas fa-icons w-5 mr-2 text-center"></i> <span>Ícones 3D</span>
        </a>
        <?php endif; ?>

        <?php if($role === 'superadmin'): ?>
        <div class="sidebar-header text-yellow-500">Super Admin</div>
        <a href="/<?php echo $sideSlug; ?>/admin_server" class="sidebar-link <?php echo $page=="admin_server"?"active":""; ?>">
            <i class="fas fa-server w-5 mr-2 text-center"></i> <span>Saúde Servidor</span>
        </a>
        <a href="/<?php echo $sideSlug; ?>/admin_usuarios_tenant" class="sidebar-link <?php echo $page=='admin_usuarios_tenant'?'active':''; ?>">
            <i class="fas fa-building w-5 mr-2 text-center"></i> <span>Empresas (Tenants)</span>
        </a>
        <a href="/<?php echo $sideSlug; ?>/admin_crm" class="sidebar-link <?php echo $page=='admin_crm'?'active':''; ?>">
            <i class="fas fa-briefcase w-5 mr-2 text-center"></i> <span>CRM Master</span>
        </a>
        <a href="/<?php echo $sideSlug; ?>/admin_gestao" class="sidebar-link <?php echo $page=='admin_gestao'?'active':''; ?>">
            <i class="fas fa-paint-brush w-5 mr-2 text-center"></i> <span>Personalização</span>
        </a>
        <a href="/<?php echo $sideSlug; ?>/api_docs" class="sidebar-link <?php echo $page=='api_docs'?'active':''; ?>">
            <i class="fas fa-code w-5 mr-2 text-center"></i> <span>API Docs</span>
        </a>
        <?php endif; ?>

    </nav>
</aside>