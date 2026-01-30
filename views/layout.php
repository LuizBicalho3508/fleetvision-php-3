<?php
// views/layout.php - VERSÃO CORRIGIDA (Scripts no Header)

if (session_status() === PHP_SESSION_NONE) session_start();

// Dados do Tenant e Usuário
$tenantData = $_SESSION['tenant_data'] ?? [];
$userName   = $_SESSION['user_name'] ?? 'Usuário';
$userRole   = $_SESSION['user_role'] ?? 'Visitante';
$userInitial = strtoupper(substr($userName, 0, 1));

// Cores
$primaryColor = $tenantData['primary_color'] ?? '#2563eb';
$bgColor      = '#f1f5f9'; 
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $tenantData['name'] ?? 'FleetVision' ?> | Sistema</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '<?= $primaryColor ?>',
                    }
                }
            }
        }
    </script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; background-color: <?= $bgColor ?>; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.1); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(0,0,0,0.2); }
        .dropdown-enter { animation: slideDown 0.2s ease-out forwards; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .toast-enter { animation: slideInRight 0.3s ease-out forwards; }
        @keyframes slideInRight { from { opacity: 0; transform: translateX(100%); } to { opacity: 1; transform: translateX(0); } }
    </style>

    <script>
        // 1. SHOW TOAST (Notificações)
        window.showToast = function(message, type = 'success') {
            const container = document.getElementById('toast-container');
            if (!container) return; // Segurança caso o DOM não esteja pronto

            const toast = document.createElement('div');
            let bgClass = type === 'success' ? 'bg-green-500' : (type === 'error' ? 'bg-red-500' : 'bg-blue-500');
            let icon = type === 'success' ? 'fa-check-circle' : (type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle');

            toast.className = `${bgClass} text-white px-6 py-4 rounded-lg shadow-xl flex items-center gap-3 min-w-[300px] toast-enter cursor-pointer transition-all hover:brightness-90`;
            toast.innerHTML = `<i class="fas ${icon} text-lg"></i><div class="flex-1 text-sm font-medium">${message}</div>`;
            
            toast.onclick = () => toast.remove();
            container.appendChild(toast);

            setTimeout(() => {
                if (toast.isConnected) {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateY(-10px)';
                    setTimeout(() => toast.remove(), 300);
                }
            }, 4000);
        };

        // 2. API FETCH (Comunicação com o Servidor)
        window.apiFetch = async function(url, options = {}) {
            const defaultHeaders = {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            };
            const config = { ...options, headers: { ...defaultHeaders, ...options.headers } };

            try {
                const response = await fetch(url, config);
                let data;
                const contentType = response.headers.get("content-type");
                if (contentType && contentType.indexOf("application/json") !== -1) {
                    data = await response.json();
                } else {
                    data = { success: response.ok, message: await response.text() };
                }

                if (!response.ok) throw new Error(data.error || data.message || 'Erro na requisição.');
                return data;
            } catch (error) {
                console.error('API Fetch Error:', error);
                if (!options.silent && typeof showToast === 'function') {
                    showToast(error.message || 'Falha de comunicação.', 'error');
                }
                throw error;
            }
        };

        // 3. Helpers de UI
        function toggleFullScreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(e => console.log(e));
            } else {
                if (document.exitFullscreen) document.exitFullscreen();
            }
        }
    </script>
</head>
<body class="text-slate-800 antialiased">

    <div id="toast-container" class="fixed top-5 right-5 z-[60] flex flex-col gap-2"></div>

    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="ml-64 min-h-screen flex flex-col transition-all duration-300" id="mainContent">
        
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 sticky top-0 z-40 border-b border-slate-100">
            <div class="flex items-center gap-4">
                <button class="md:hidden text-slate-500 hover:text-slate-700"><i class="fas fa-bars text-xl"></i></button>
                <h1 class="text-lg font-bold text-slate-700 uppercase tracking-wide"><?= ucfirst($viewName ?? 'Dashboard') ?></h1>
            </div>

            <div class="flex items-center gap-4">
                <button onclick="toggleFullScreen()" class="w-8 h-8 rounded-full flex items-center justify-center text-slate-400 hover:bg-slate-100 transition"><i class="fas fa-expand"></i></button>

                <div class="relative" id="userMenuContainer">
                    <button id="userMenuBtn" class="flex items-center gap-3 pl-3 pr-2 py-1.5 rounded-full hover:bg-slate-50 border border-transparent hover:border-slate-200 transition-all focus:outline-none">
                        <div class="text-right hidden md:block leading-tight">
                            <p class="text-sm font-bold text-slate-700"><?= $userName ?></p>
                            <p class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider"><?= $userRole ?></p>
                        </div>
                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-white font-bold shadow-md ring-2 ring-white" style="background-color: <?= $primaryColor ?>">
                            <?= $userInitial ?>
                        </div>
                        <i class="fas fa-chevron-down text-xs text-slate-400 ml-1 transition-transform duration-200" id="userMenuIcon"></i>
                    </button>

                    <div id="userDropdown" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-slate-100 py-2 z-50 origin-top-right dropdown-enter">
                        <div class="px-4 py-3 border-b border-slate-50 md:hidden">
                            <p class="text-sm font-bold text-slate-800"><?= $userName ?></p>
                            <p class="text-xs text-slate-500"><?= $userRole ?></p>
                        </div>
                        <div class="px-2 space-y-1">
                            <a href="/<?= $_SESSION['tenant_slug'] ?? 'admin' ?>/perfil" class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-slate-600 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors">
                                <i class="fas fa-user-circle w-5 text-center"></i> Meu Perfil
                            </a>
                            <?php if(\App\Middleware\AuthMiddleware::hasPermission('settings_view')): ?>
                            <a href="/<?= $_SESSION['tenant_slug'] ?? 'admin' ?>/filiais" class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-slate-600 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors">
                                <i class="fas fa-cog w-5 text-center"></i> Configurações
                            </a>
                            <?php endif; ?>
                        </div>
                        <div class="h-px bg-slate-100 my-2"></div>
                        <div class="px-2">
                            <a href="/<?= $_SESSION['tenant_slug'] ?? 'admin' ?>/logout" class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-red-600 rounded-lg hover:bg-red-50 transition-colors group">
                                <i class="fas fa-sign-out-alt w-5 text-center group-hover:scale-110 transition-transform"></i> Sair do Sistema
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 p-6 overflow-x-hidden">
            <?php require $viewPath; ?>
        </main>

        <footer class="h-10 border-t border-slate-200 bg-white flex items-center justify-center text-xs text-slate-400">
            &copy; <?= date('Y') ?> <?= $tenantData['name'] ?? 'FleetVision' ?>. Todos os direitos reservados.
        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const btn = document.getElementById('userMenuBtn');
            const menu = document.getElementById('userDropdown');
            const icon = document.getElementById('userMenuIcon');
            let isOpen = false;

            if(btn && menu) {
                function toggleMenu() {
                    isOpen = !isOpen;
                    menu.classList.toggle('hidden', !isOpen);
                    if(icon) icon.style.transform = isOpen ? 'rotate(180deg)' : 'rotate(0deg)';
                }
                btn.addEventListener('click', (e) => { e.stopPropagation(); toggleMenu(); });
                document.addEventListener('click', (e) => {
                    if (isOpen && !btn.contains(e.target) && !menu.contains(e.target)) toggleMenu();
                });
            }
        });
    </script>
</body>
</html>