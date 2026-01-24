<?php
// views/login.php
if (session_status() === PHP_SESSION_NONE) session_start();

// Verifica se já está logado
if (isset($_SESSION['user_id'])) {
    $slug = $_SESSION['tenant_slug'] ?? 'admin';
    header("Location: /$slug/dashboard");
    exit;
}

// 1. Identifica o Tenant (Pela URL ou Padrão)
// O Router define $tenantSlug se a URL for /cliente/login
$currentSlug = $tenantSlug ?? 'admin'; 
$tenantData = null;

// Busca dados do tenant para personalizar a tela (Opcional, falha silenciosa se não der)
try {
    $pdo = \App\Config\Database::getConnection();
    $stmt = $pdo->prepare("SELECT name, logo_url, login_bg_url, login_opacity, login_btn_color, primary_color FROM saas_tenants WHERE slug = ? LIMIT 1");
    $stmt->execute([$currentSlug]);
    $tenantData = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Falha silenciosa
}

// Define Estilos
$appName = $tenantData['name'] ?? 'FleetVision';
$logoUrl = !empty($tenantData['logo_url']) ? '/' . $tenantData['logo_url'] : '/assets/img/logo_full.png'; 
$bgUrl = !empty($tenantData['login_bg_url']) ? '/' . $tenantData['login_bg_url'] : '/assets/img/login_bg_default.jpg';
$cardOpacity = $tenantData['login_opacity'] ?? 0.95;
$btnColor = $tenantData['login_btn_color'] ?? '#2563eb';
$primaryColor = $tenantData['primary_color'] ?? '#2563eb';

// Estilo do Background
$bgStyle = !empty($tenantData['login_bg_url']) 
    ? "background-image: url('$bgUrl'); background-size: cover; background-position: center;"
    : "background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);";
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo htmlspecialchars($appName); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
        
        .login-bg { <?php echo $bgStyle; ?> }
        
        .glass-card {
            background-color: rgba(255, 255, 255, <?php echo $cardOpacity; ?>);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .btn-custom {
            background-color: <?php echo $btnColor; ?>;
            transition: all 0.3s ease;
        }
        .btn-custom:hover {
            filter: brightness(90%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .fade-in-up { animation: fadeInUp 0.5s ease-out; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="login-bg h-screen w-full flex items-center justify-center p-4">

    <div class="glass-card w-full max-w-md rounded-2xl shadow-2xl overflow-hidden fade-in-up">
        <div class="p-8 md:p-10">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-slate-800 tracking-tight">
                    FLEET<span style="color: <?php echo $primaryColor; ?>">VISION</span>
                </h1>
                <p class="text-slate-500 text-sm mt-2">Bem-vindo ao <?php echo htmlspecialchars($appName); ?></p>
            </div>

            <form id="loginForm" class="space-y-5">
                <input type="hidden" name="slug" value="<?php echo htmlspecialchars($currentSlug); ?>">
                
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1 ml-1">E-mail Corporativo</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-4 top-3.5 text-slate-400"></i>
                        <input type="email" name="email" class="w-full pl-11 pr-4 py-3 rounded-xl border border-slate-200 bg-white/50 focus:bg-white focus:border-blue-500 outline-none transition text-slate-700" placeholder="seu@email.com" required autofocus>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-1 ml-1">
                        <label class="block text-xs font-bold text-slate-500 uppercase">Senha</label>
                        <a href="/sys/auth/recover" class="text-xs font-medium text-blue-500 hover:text-blue-700 transition">Esqueceu a senha?</a>
                    </div>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-4 top-3.5 text-slate-400"></i>
                        <input type="password" name="password" id="password" class="w-full pl-11 pr-12 py-3 rounded-xl border border-slate-200 bg-white/50 focus:bg-white focus:border-blue-500 outline-none transition text-slate-700" placeholder="••••••••" required>
                        <button type="button" onclick="togglePass()" class="absolute right-4 top-3.5 text-slate-400 hover:text-slate-600 transition">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" id="btnSubmit" class="btn-custom w-full py-3.5 rounded-xl text-white font-bold text-lg shadow-lg flex items-center justify-center gap-2 mt-2">
                    <span>Aceder à Conta</span>
                    <i class="fas fa-arrow-right text-sm opacity-80"></i>
                </button>
            </form>

            <div id="msgError" class="hidden mt-4 p-3 bg-red-50 border border-red-100 rounded-lg text-red-600 text-sm text-center flex items-center justify-center gap-2 animate-pulse">
                <i class="fas fa-exclamation-circle"></i> <span id="msgText">Erro</span>
            </div>
        </div>
        
        <div class="bg-slate-50/80 px-8 py-4 border-t border-slate-100 text-center">
            <p class="text-xs text-slate-400">
                &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($appName); ?>. Todos os direitos reservados.
            </p>
        </div>
    </div>

    <script>
        function togglePass() {
            const input = document.getElementById('password');
            const icon = document.getElementById('eyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        document.getElementById('loginForm').onsubmit = async (e) => {
            e.preventDefault();
            
            const btn = document.getElementById('btnSubmit');
            const originalContent = btn.innerHTML;
            const msgBox = document.getElementById('msgError');
            const msgText = document.getElementById('msgText');

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Validando...';
            msgBox.classList.add('hidden');

            const data = Object.fromEntries(new FormData(e.target));

            try {
                // *** AQUI ESTÁ A CORREÇÃO PRINCIPAL ***
                // Trocamos /api/auth/login por /sys/auth/login
                const response = await fetch('/sys/auth/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                // Tenta ler o JSON. Se falhar, captura o texto para debug
                const text = await response.text();
                let result;
                try {
                    result = JSON.parse(text);
                } catch (err) {
                    throw new Error("Erro do Servidor: " + text.substring(0, 50));
                }

                if (result.success) {
                    btn.innerHTML = '<i class="fas fa-check"></i> Sucesso!';
                    btn.classList.remove('btn-custom');
                    btn.classList.add('bg-green-500');
                    
                    setTimeout(() => {
                        window.location.href = result.redirect || '/<?php echo $currentSlug; ?>/dashboard';
                    }, 500);
                } else {
                    throw new Error(result.error || 'Credenciais inválidas.');
                }

            } catch (err) {
                console.error(err);
                msgText.innerText = err.message;
                msgBox.classList.remove('hidden');
            } finally {
                if (!msgBox.classList.contains('hidden') || !btn.innerHTML.includes('Sucesso')) {
                    btn.disabled = false;
                    btn.innerHTML = originalContent;
                }
            }
        };
    </script>
</body>
</html>