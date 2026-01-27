<?php
// GESTÃO DE MENU E SESSÃO
if (session_status() == PHP_SESSION_NONE) session_start();

$tenant = $_SESSION['tenant_data'] ?? [];
$slug   = $_SESSION['tenant_slug'] ?? 'admin';
$user   = $_SESSION['user_name'] ?? 'Usuário';

// Cores e Logo
$primaryColor   = $tenant['primary_color'] ?? '#2563eb';
$logoUrl        = !empty($tenant['logo_url']) ? $tenant['logo_url'] : null;
$tenantName     = $tenant['name'] ?? 'FleetVision';

// Identifica Página Ativa
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($uri, '/'));
$currentPage = end($pathParts);
if ($currentPage == $slug || empty($currentPage)) $currentPage = 'dashboard';

// DEFINIÇÃO DO MENU LATERAL
$menuSections = [
    'VISÃO GERAL' => [
        ['label' => 'Dashboard',   'icon' => 'fa-chart-pie',      'url' => 'dashboard', 'perm' => 'dashboard_view'],
        ['label' => 'Mapa Real',   'icon' => 'fa-map-marked-alt', 'url' => 'mapa',      'perm' => 'map_view'],
    ],
    'GESTÃO DE FROTA' => [
        ['label' => 'Veículos',    'icon' => 'fa-truck',          'url' => 'frota',      'perm' => 'vehicles_view'],
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
        ['label' => 'Usuários',    'icon' => 'fa-users',          'url' => 'usuarios',   'perm' => 'users_view'],
        ['label' => 'Relatórios',  'icon' => 'fa-file-alt',       'url' => 'relatorios', 'perm' => 'reports_view'],
    ],
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $tenantName; ?> | Painel</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        
        .nav-item-active {
            background-color: <?php echo $primaryColor; ?> !important;
            color: white !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .nav-item:hover:not(.nav-item-active) {
            color: <?php echo $primaryColor; ?>;
            background-color: #f8fafc;
        }
        /* Animação do Sino */
        @keyframes bell-shake {
            0% { transform: rotate(0); }
            15% { transform: rotate(5deg); }
            30% { transform: rotate(-5deg); }
            45% { transform: rotate(4deg); }
            60% { transform: rotate(-4deg); }
            75% { transform: rotate(2deg); }
            85% { transform: rotate(-2deg); }
            100% { transform: rotate(0); }
        }
        .animate-bell { animation: bell-shake 1s cubic-bezier(.36,.07,.19,.97) both; }
    </style>

    <script>
        window.apiFetch = async (url, options = {}) => {
            try {
                const response = await fetch(url, {
                    ...options,
                    headers: { 'Content-Type': 'application/json', ...options.headers }
                });
                const text = await response.text();
                try { 
                    return JSON.parse(text); 
                } catch (e) { 
                    console.error("Erro API (Não JSON):", text); 
                    return { error: 'Erro no servidor. Verifique o console.' }; 
                }
            } catch (err) { 
                console.error("Erro Conexão:", err); 
                return { error: 'Erro de conexão' }; 
            }
        };

        window.showToast = (msg, type='success') => {
            const color = type === 'error' ? 'bg-red-500' : 'bg-green-500';
            const el = document.createElement('div');
            el.className = `fixed bottom-4 right-4 ${color} text-white px-6 py-3 rounded-lg shadow-lg z-[9999] animate-bounce font-bold`;
            el.innerHTML = msg;
            document.body.appendChild(el);
            setTimeout(() => el.remove(), 3000);
        };
    </script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased h-screen overflow-hidden flex">

    <aside id="main-sidebar" class="bg-white w-64 h-full border-r border-slate-200 flex flex-col shrink-0 z-30 transition-transform duration-300 -translate-x-full md:translate-x-0 absolute md:relative shadow-xl md:shadow-none">
        <div class="h-16 flex items-center justify-center border-b border-slate-100 px-4 shrink-0">
            <?php if ($logoUrl): ?>
                <img src="<?php echo $logoUrl; ?>" alt="Logo" class="max-h-8 max-w-full object-contain">
            <?php else: ?>
                <span class="font-bold text-slate-800 text-lg truncate flex items-center gap-2">
                    <i class="fas fa-satellite-dish text-blue-600"></i> <?php echo $tenantName; ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="flex-1 overflow-y-auto py-6 px-4 space-y-6 custom-scrollbar">
            <?php foreach ($menuSections as $sectionTitle => $items): ?>
                <div>
                    <h3 class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">
                        <?php echo $sectionTitle; ?>
                    </h3>
                    <ul class="space-y-1">
                        <?php foreach ($items as $item): 
                            if (function_exists('hasPermission') && $item['perm'] !== 'all' && !hasPermission($item['perm'])) continue;
                            $isActive = ($currentPage === $item['url']);
                        ?>
                            <li>
                                <a href="/<?php echo $slug; ?>/<?php echo $item['url']; ?>" 
                                   class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 <?php echo $isActive ? 'nav-item-active' : 'text-slate-500'; ?>">
                                    <i class="fas <?php echo $item['icon']; ?> w-5 text-center"></i>
                                    <span><?php echo $item['label']; ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="p-4 border-t border-slate-100 text-center">
            <p class="text-[10px] text-slate-400">&copy; <?php echo date('Y'); ?> FleetVision Pro</p>
        </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0 h-full relative">
        
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 shrink-0 z-20">
            <button onclick="document.getElementById('main-sidebar').classList.toggle('-translate-x-full')" class="md:hidden text-slate-500 hover:text-blue-600">
                <i class="fas fa-bars text-xl"></i>
            </button>

            <h2 class="text-lg font-bold text-slate-700 hidden sm:block uppercase tracking-tight">
                <?php echo ucfirst($viewName ?? 'Painel'); ?>
            </h2>

            <div class="flex items-center gap-4">
                
                <div class="relative">
                    <button onclick="toggleAlertModal()" id="notifBell" class="text-slate-400 hover:text-blue-600 transition relative block p-2 rounded-full hover:bg-slate-50 focus:outline-none">
                        <i class="fas fa-bell text-xl"></i>
                        <span id="notifDot" class="absolute top-1 right-2 w-2.5 h-2.5 bg-red-500 border-2 border-white rounded-full hidden"></span>
                        <span id="notifPing" class="absolute top-1 right-2 w-2.5 h-2.5 bg-red-500 rounded-full animate-ping hidden"></span>
                    </button>

                    <div id="alertDropdown" class="hidden absolute right-0 top-12 w-80 bg-white rounded-xl shadow-2xl border border-slate-100 z-50 overflow-hidden transform transition-all duration-200 origin-top-right">
                        <div class="p-3 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                            <h3 class="text-xs font-bold text-slate-700 uppercase">Últimos Alertas</h3>
                            <a href="/<?php echo $slug; ?>/alertas" class="text-[10px] text-blue-600 hover:underline font-bold">Ver Histórico</a>
                        </div>
                        <div id="alertDropdownList" class="max-h-64 overflow-y-auto custom-scrollbar bg-white">
                            <div class="p-8 text-center text-xs text-slate-400 flex flex-col items-center">
                                <i class="fas fa-check-circle text-2xl mb-2 text-slate-200"></i> 
                                Tudo tranquilo por aqui.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3 pl-4 border-l border-slate-100">
                    <div class="text-right hidden md:block">
                        <div class="text-sm font-bold text-slate-700"><?php echo $user; ?></div>
                        <div class="text-[10px] text-slate-400 uppercase">Administrador</div>
                    </div>
                    <div class="w-9 h-9 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 border border-slate-200">
                        <i class="fas fa-user"></i>
                    </div>
                    <a href="/logout" class="text-slate-400 hover:text-red-500 ml-2" title="Sair">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-hidden relative">
            <?php 
                if (isset($viewPath) && file_exists($viewPath)) {
                    require $viewPath;
                } else {
                    echo '<div class="h-full flex flex-col items-center justify-center text-slate-400"><i class="fas fa-exclamation-circle text-4xl mb-2"></i><p>Página não encontrada.</p></div>';
                }
            ?>
        </main>
    </div>

    <div id="globalAlertContainer" class="fixed top-20 right-4 z-[9999] flex flex-col gap-3 pointer-events-none"></div>

    <audio id="alertSound" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>

    <script>
        // --- Lógica do Modal e Polling ---
        (function() {
            let processedAlerts = new Set();
            let audioUnlocked = false;
            let recentAlerts = []; // Store local para o modal

            // 1. Destrava Áudio
            const audio = document.getElementById('alertSound');
            const unlockAudio = () => {
                if(!audioUnlocked) {
                    audio.play().then(() => {
                        audio.pause(); audio.currentTime = 0; audioUnlocked = true;
                    }).catch(e => {});
                    document.removeEventListener('click', unlockAudio);
                }
            };
            document.addEventListener('click', unlockAudio);

            // 2. Toggle Modal
            window.toggleAlertModal = () => {
                const dropdown = document.getElementById('alertDropdown');
                dropdown.classList.toggle('hidden');
                
                if (!dropdown.classList.contains('hidden')) {
                    // Limpa notificações visuais ao abrir
                    document.getElementById('notifDot').classList.add('hidden');
                    document.getElementById('notifPing').classList.add('hidden');
                    document.getElementById('notifBell').classList.remove('text-red-500', 'animate-bell');
                    renderAlertDropdown();
                }
            };

            // 3. Renderiza Modal
            function renderAlertDropdown() {
                const list = document.getElementById('alertDropdownList');
                if (recentAlerts.length === 0) {
                    list.innerHTML = '<div class="p-8 text-center text-xs text-slate-400 flex flex-col items-center"><i class="fas fa-check-circle text-2xl mb-2 text-green-100"></i> Sem novos alertas.</div>';
                    return;
                }

                list.innerHTML = recentAlerts.slice(0, 15).map(a => `
                    <div class="p-3 border-b border-slate-50 hover:bg-slate-50 transition flex gap-3 items-start cursor-pointer">
                        <div class="w-8 h-8 rounded-full bg-${a.color}-50 text-${a.color}-600 flex items-center justify-center shrink-0 text-xs border border-${a.color}-100">
                            <i class="fas ${a.icon}"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex justify-between">
                                <p class="text-xs font-bold text-slate-700 leading-tight">${a.title}</p>
                                <span class="text-[9px] font-mono text-slate-400">${a.time}</span>
                            </div>
                            <p class="text-[10px] text-slate-500 mt-0.5"><i class="fas fa-car text-slate-300 mr-1"></i> ${a.plate}</p>
                        </div>
                    </div>
                `).join('');
            }

            // Fecha modal ao clicar fora
            document.addEventListener('click', (e) => {
                const dropdown = document.getElementById('alertDropdown');
                const bell = document.getElementById('notifBell');
                if (!dropdown.contains(e.target) && !bell.contains(e.target)) {
                    dropdown.classList.add('hidden');
                }
            });

            // 4. Cria Popup Lateral
            function spawnAlert(alert) {
                if(audioUnlocked) { audio.currentTime = 0; audio.play().catch(e => {}); }

                const container = document.getElementById('globalAlertContainer');
                const el = document.createElement('div');
                el.className = `pointer-events-auto w-80 p-4 rounded-xl shadow-2xl border-l-4 bg-white border-${alert.color}-500 transform translate-x-full transition-all duration-500 flex gap-3 relative`;
                el.innerHTML = `
                    <div class="shrink-0 w-10 h-10 rounded-full bg-${alert.color}-50 flex items-center justify-center text-${alert.color}-600">
                        <i class="fas ${alert.icon}"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="font-bold text-sm text-slate-800">${alert.title}</h4>
                        <div class="text-xs text-slate-500 truncate mt-0.5"><i class="fas fa-car mr-1"></i> ${alert.plate}</div>
                        <div class="text-[10px] text-slate-400 mt-1">${alert.time}</div>
                    </div>
                    <button onclick="this.parentElement.remove()" class="absolute top-2 right-2 text-slate-300 hover:text-slate-500"><i class="fas fa-times"></i></button>
                    <div class="absolute bottom-0 left-0 h-1 bg-${alert.color}-500 opacity-20 w-full" style="animation: shrink 5s linear forwards;"></div>
                `;
                
                const style = document.createElement('style');
                style.innerHTML = `@keyframes shrink { from { width: 100%; } to { width: 0%; } }`;
                el.appendChild(style);
                container.appendChild(el);
                
                requestAnimationFrame(() => el.classList.remove('translate-x-full'));
                setTimeout(() => {
                    el.classList.add('translate-x-full', 'opacity-0');
                    setTimeout(() => el.remove(), 500);
                }, 5000);
            }

            // 5. Polling
            async function checkAlerts() {
                try {
                    const res = await apiFetch('/sys/alerts/poll');
                    if (res.alerts && res.alerts.length > 0) {
                        let hasNew = false;
                        res.alerts.forEach(alert => {
                            if (!processedAlerts.has(alert.id)) {
                                spawnAlert(alert);
                                recentAlerts.unshift(alert); // Adiciona ao modal
                                processedAlerts.add(alert.id);
                                hasNew = true;
                                if(processedAlerts.size > 200) processedAlerts.clear();
                            }
                        });

                        // Se tem novos alertas e o modal está fechado, ativa o sino
                        const dropdown = document.getElementById('alertDropdown');
                        if (hasNew && dropdown.classList.contains('hidden')) {
                            document.getElementById('notifDot').classList.remove('hidden');
                            document.getElementById('notifPing').classList.remove('hidden');
                            document.getElementById('notifBell').classList.add('text-red-500', 'animate-bell');
                        }
                    }
                } catch (e) {}
            }

            // Inicia Polling (5s)
            setInterval(checkAlerts, 5000);
        })();
    </script>
</body>
</html>