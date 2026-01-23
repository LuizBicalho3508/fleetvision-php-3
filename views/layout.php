<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Dados Globais da Sessão
$tenant = $_SESSION['tenant_data'] ?? [];
$slug = $_SESSION['tenant_slug'] ?? 'admin';
$user = $_SESSION['user_name'] ?? 'Usuário';
$role = $_SESSION['user_role'] ?? 'user';

// 2. Definição da Função de Permissão (CRUCIAL PARA A SIDEBAR)
if (!function_exists('hasPermission')) {
    function hasPermission($perm) {
        $role = $_SESSION['user_role'] ?? 'user';
        
        // Superadmin e Admin têm acesso irrestrito
        if ($role === 'superadmin' || $role === 'admin') {
            return true;
        }
        
        // Para outros, verifica a lista de permissões na sessão
        $perms = $_SESSION['user_permissions'] ?? [];
        // Se a permissão estiver no array ou se for um array vazio (permitir tudo por enquanto se não houver lógica estrita)
        // Ajuste conforme sua lógica de permissões:
        return in_array($perm, $perms);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FleetVision</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="/style.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <script>
        // --- FUNÇÃO API FETCH GLOBAL (CORREÇÃO DO ERRO JS) ---
        async function apiFetch(endpoint, options = {}) {
            try {
                const res = await fetch(endpoint, options);
                
                // Redireciona se sessão expirou
                if (res.status === 401) {
                    window.location.href = '/<?php echo $slug; ?>/login';
                    return;
                }
                
                // Trata 404
                if (res.status === 404) {
                    console.error('API não encontrada:', endpoint);
                    return { error: 'Recurso não encontrado (404)' };
                }

                return await res.json();
            } catch (error) {
                console.error('Erro na API:', error);
                return { error: 'Erro de conexão' };
            }
        }

        // --- TOAST GLOBAL ---
        function showToast(msg, type = 'success') {
            const container = document.getElementById('toast-container') || document.body;
            const div = document.createElement('div');
            
            // Estilo do Toast
            const colorClass = type === 'success' ? 'bg-green-600' : 'bg-red-600';
            div.className = `fixed top-5 right-5 z-[9999] px-6 py-4 rounded-lg shadow-xl text-white font-bold transform transition-all duration-300 translate-y-0 opacity-100 flex items-center gap-3 ${colorClass}`;
            div.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> <span>${msg}</span>`;
            
            container.appendChild(div);
            
            // Remove após 3 segundos
            setTimeout(() => {
                div.style.opacity = '0';
                div.style.transform = 'translateY(-20px)';
                setTimeout(() => div.remove(), 300);
            }, 3000);
        }
    </script>
</head>
<body class="bg-slate-50 font-sans h-screen flex overflow-hidden">

    <?php 
    // Garante que o caminho da sidebar está correto
    // Se sidebar.php estiver na raiz, use: include __DIR__ . '/../sidebar.php';
    // Se sidebar.php estiver em views, use:
    if (file_exists(__DIR__ . '/sidebar.php')) {
        include __DIR__ . '/sidebar.php'; 
    } elseif (file_exists(__DIR__ . '/../sidebar.php')) {
        include __DIR__ . '/../sidebar.php';
    } else {
        echo "<div class='w-64 bg-red-800 text-white p-4'>Erro: sidebar.php não encontrado</div>";
    }
    ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 shrink-0 z-30 shadow-sm">
            <div class="flex items-center gap-4">
                <button class="md:hidden text-slate-500 hover:text-blue-600" onclick="document.getElementById('main-sidebar').classList.toggle('-translate-x-full')">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="text-lg font-bold text-slate-700 capitalize flex items-center gap-2">
                    <?php 
                        // Título Dinâmico
                        echo ucfirst(str_replace(['_', '-'], ' ', basename($viewName ?? 'Dashboard', '.php'))); 
                    ?>
                </h2>
            </div>
            
            <div class="flex items-center gap-4">
                <div class="text-right hidden md:block">
                    <div class="text-sm font-bold text-slate-700"><?php echo htmlspecialchars($user); ?></div>
                    <div class="text-xs text-slate-400 font-bold uppercase"><?php echo htmlspecialchars($role); ?></div>
                </div>
                <div class="h-10 w-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold border border-blue-200">
                    <?php echo strtoupper(substr($user, 0, 2)); ?>
                </div>
                <a href="/logout" class="ml-2 text-red-400 hover:text-red-600 transition" title="Sair">
                    <i class="fas fa-sign-out-alt text-lg"></i>
                </a>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 relative p-0">
            <?php 
                if (isset($viewPath) && file_exists($viewPath)) {
                    include $viewPath;
                } else {
                    echo "<div class='flex flex-col items-center justify-center h-full text-slate-400'>
                            <i class='fas fa-bug text-4xl mb-4'></i>
                            <p>Erro: View não encontrada.</p>
                            <code class='text-xs bg-slate-200 p-1 rounded mt-2'>" . htmlspecialchars($viewPath ?? 'N/A') . "</code>
                          </div>";
                }
            ?>
        </main>
    </div>

    <div id="toast-container"></div>

</body>
</html>