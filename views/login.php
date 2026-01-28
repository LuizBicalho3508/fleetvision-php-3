<?php
// views/login.php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Redirecionamento se já logado
if (isset($_SESSION['user_id'])) {
    $slug = $_SESSION['tenant_slug'] ?? 'admin';
    header("Location: /$slug/dashboard");
    exit;
}

// 2. Detecção Robusta do Slug (URL > Sessão > Padrão)
$currentSlug = $tenantSlug ?? ($_SESSION['tenant_slug'] ?? 'admin');

// Tenta pegar o slug da URL se a variável não vier do roteador (Fallback)
if (!isset($tenantSlug)) {
    $uriParts = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
    if (isset($uriParts[0]) && !in_array($uriParts[0], ['login', 'sys', 'api', 'assets'])) {
        $currentSlug = $uriParts[0];
    }
}

// 3. Busca Dados do Tenant no Banco
$tenantData = null;
try {
    // Usa o namespace correto da sua classe Database
    if (class_exists('App\Config\Database')) {
        $pdo = \App\Config\Database::getConnection();
        $stmt = $pdo->prepare("SELECT name, logo_url, login_bg_url, login_opacity, login_btn_color, login_card_color, primary_color FROM saas_tenants WHERE slug = ? LIMIT 1");
        $stmt->execute([$currentSlug]);
        $tenantData = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) { 
    // Silencia erro de banco para não expor dados, usa fallback visual
}

// --- FUNÇÃO DE CORREÇÃO DE CAMINHOS (O SEGREDO DA CORREÇÃO) ---
function get_safe_url($dbUrl) {
    if (empty($dbUrl)) return '';
    
    // Se já for link completo (https://...), retorna direto
    if (filter_var($dbUrl, FILTER_VALIDATE_URL)) return $dbUrl;
    
    // Remove barras extras e espaços
    $cleanPath = ltrim(trim($dbUrl), '/');
    
    // Detecta a pasta do projeto automaticamente (ex: /fleetvision)
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $baseUrl   = ($scriptDir === '/' || $scriptDir === '\\') ? '' : $scriptDir;
    $baseUrl   = str_replace('\\', '/', $baseUrl); // Fix para Windows
    
    return $baseUrl . '/' . $cleanPath;
}

// 4. Configuração Visual (Com Correção de Caminho aplicada)
$appName      = $tenantData['name'] ?? 'FleetVision';
$logoUrl      = get_safe_url($tenantData['logo_url'] ?? '');
$bgUrl        = get_safe_url($tenantData['login_bg_url'] ?? '');
// Se não tiver imagem de fundo configurada, usa uma padrão do Unsplash
if (empty($bgUrl)) {
    $bgUrl = 'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?auto=format&fit=crop&q=80';
}

$cardOpacity  = $tenantData['login_opacity'] ?? 0.95;
$cardColor    = $tenantData['login_card_color'] ?? '#ffffff'; // Suporte a cor do cartão
$btnColor     = $tenantData['login_btn_color'] ?? '#2563eb';
$primaryColor = $tenantData['primary_color'] ?? '#2563eb';

// CSS Dinâmico
$bgStyle = "background-image: url('$bgUrl'); background-size: cover; background-position: center; background-color: $primaryColor;";
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
            background-color: <?php echo $cardColor; ?>; /* Usa a cor configurada no banco */
            opacity: <?php echo $cardOpacity; ?>;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        /* Garante que o conteúdo dentro do card não fique transparente se o card tiver opacidade */
        .glass-card-content {
            opacity: 1 !important; 
        }

        .btn-custom {
            background-color: <?php echo $btnColor; ?>;
            color: white;
            transition: all 0.3s ease;
        }
        .btn-custom:hover {
            filter: brightness(90%);
            transform: translateY(-1px);
        }
    </style>
</head>
<body class="login-bg h-screen w-full flex items-center justify-center p-4">

    <div class="absolute inset-0 bg-black/40 z-0"></div>

    <div class="glass-card w-full max-w-md rounded-2xl shadow-2xl overflow-hidden relative z-10">
        <div class="p-8 glass-card-content">
            <div class="text-center mb-6">
                <?php if ($logoUrl): ?>
                    <img src="<?php echo htmlspecialchars($logoUrl); ?>" 
                         alt="Logo" 
                         class="h-16 mx-auto mb-4 object-contain transition-transform hover:scale-105"
                         onerror="this.style.display='none'; document.getElementById('text-logo').classList.remove('hidden');">
                    
                    <h1 id="text-logo" class="text-3xl font-bold text-slate-800 tracking-tight mb-2 hidden">
                        <?php echo htmlspecialchars($appName); ?>
                    </h1>
                <?php else: ?>
                    <h1 class="text-3xl font-bold text-slate-800 tracking-tight mb-2">
                        <?php echo htmlspecialchars($appName); ?>
                    </h1>
                <?php endif; ?>
                
                <p class="text-slate-500 text-sm">Bem-vindo ao <?php echo htmlspecialchars($appName); ?></p>
            </div>

            <form id="loginForm" class="space-y-4">
                <input type="hidden" name="slug" value="<?php echo htmlspecialchars($currentSlug); ?>">
                
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1 ml-1">E-mail</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-4 top-3.5 text-slate-400"></i>
                        <input type="email" name="email" class="w-full pl-11 pr-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 outline-none text-slate-700" placeholder="seu@email.com" required>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-1 ml-1">
                        <label class="block text-xs font-bold text-slate-600 uppercase">Senha</label>
                    </div>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-4 top-3.5 text-slate-400"></i>
                        <input type="password" name="password" id="password" class="w-full pl-11 pr-12 py-3 rounded-xl border border-slate-200 focus:border-blue-500 outline-none text-slate-700" placeholder="••••••••" required>
                        <button type="button" onclick="document.getElementById('password').type = document.getElementById('password').type === 'password' ? 'text' : 'password'" class="absolute right-4 top-3.5 text-slate-400 hover:text-slate-600">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" id="btnSubmit" class="btn-custom w-full py-3.5 rounded-xl font-bold text-lg shadow-lg flex items-center justify-center gap-2 mt-4">
                    <span>Entrar</span>
                </button>
            </form>

            <div id="msgError" class="hidden mt-4 p-3 bg-red-50 border border-red-100 rounded-lg text-red-600 text-sm text-center">
                <i class="fas fa-exclamation-circle mr-1"></i> <span id="msgText">Erro</span>
            </div>
        </div>
        
        <div class="bg-slate-50/50 px-8 py-3 border-t border-slate-100 text-center relative z-20">
            <p class="text-xs text-slate-500">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($appName); ?></p>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').onsubmit = async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btnSubmit');
            const original = btn.innerHTML;
            const msgBox = document.getElementById('msgError');
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Validando...';
            msgBox.classList.add('hidden');

            try {
                // Fetch para a API
                const res = await fetch('/sys/auth/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(Object.fromEntries(new FormData(e.target)))
                });

                const text = await res.text();
                let json;
                try { 
                    json = JSON.parse(text); 
                } catch(e) { 
                    console.error('Resposta inválida:', text);
                    throw new Error('Erro no servidor. Verifique o console.'); 
                }

                if (json.success) {
                    btn.innerHTML = '<i class="fas fa-check"></i> Sucesso!';
                    btn.classList.remove('btn-custom');
                    btn.classList.add('bg-green-500', 'text-white');
                    
                    setTimeout(() => {
                        window.location.href = json.redirect;
                    }, 500);
                } else {
                    throw new Error(json.error || 'Erro desconhecido');
                }
            } catch (err) {
                console.error(err);
                document.getElementById('msgText').innerText = err.message;
                msgBox.classList.remove('hidden');
                btn.disabled = false;
                btn.innerHTML = original;
            }
        };
    </script>
</body>
</html>