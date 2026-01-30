<?php
if (session_status() === PHP_SESSION_NONE) session_start();
use App\Config\Database;
use PDO;

// 1. Busca Tenant
$uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($uriPath, '/'));
$tenantSlug = $pathParts[0] ?? 'admin';

$tenantData = $_SESSION['tenant_data'] ?? [];

try {
    if (empty($tenantData) || ($tenantData['slug'] ?? '') !== $tenantSlug) {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM saas_tenants WHERE slug = ? LIMIT 1");
        $stmt->execute([$tenantSlug]);
        $fresh = $stmt->fetch(PDO::FETCH_ASSOC);
        $tenantData = $fresh ? $fresh : [];
    }
} catch (Exception $e) {}

// 2. Variáveis de Design (Com Fallbacks)
$primaryColor     = $tenantData['primary_color'] ?? '#2563eb';
$secondaryColor   = $tenantData['secondary_color'] ?? '#1e293b';

$loginBgColor     = $tenantData['login_bg_color'] ?? '#f3f4f6';
$loginBgUrl       = $tenantData['login_bg_url'] ?? '';

$loginCardColor   = $tenantData['login_card_color'] ?? '#ffffff';
$loginTextColor   = $tenantData['login_text_color'] ?? '#334155';
$loginInputBg     = $tenantData['login_input_bg_color'] ?? '#ffffff';
$loginBtnText     = $tenantData['login_btn_text_color'] ?? '#ffffff';

// Ajuste Logo
$logoUrl = $tenantData['logo_url'] ?? '';
if (!empty($logoUrl) && $logoUrl[0] !== '/' && strpos($logoUrl, 'http') === false) $logoUrl = '/' . $logoUrl;
if (!empty($loginBgUrl) && $loginBgUrl[0] !== '/' && strpos($loginBgUrl, 'http') === false) $loginBgUrl = '/' . $loginBgUrl;

// Background Style
$bgStyle = "background-color: $loginBgColor;";
if (!empty($loginBgUrl)) {
    $bgStyle = "background-image: url('$loginBgUrl'); background-size: cover; background-position: center;";
} else {
    $bgStyle = "background: linear-gradient(135deg, $primaryColor 0%, $secondaryColor 100%);";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?= $tenantData['name'] ?? 'Sistema' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { <?= $bgStyle ?> min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .glass-effect { backdrop-filter: blur(10px); }
        .input-field { background-color: <?= $loginInputBg ?>; color: #333; }
        .input-field::placeholder { color: #999; }
    </style>
</head>
<body class="p-4">

    <div class="w-full max-w-md rounded-2xl shadow-2xl overflow-hidden transform transition-all hover:scale-[1.01] relative"
         style="background-color: <?= $loginCardColor ?>">
        
        <div class="p-8 md:p-10 relative z-10">
            <div class="text-center mb-8">
                <?php if (!empty($logoUrl)): ?>
                    <img src="<?= $logoUrl ?>" alt="Logo" class="h-16 mx-auto object-contain mb-4">
                <?php else: ?>
                    <h1 class="text-3xl font-bold uppercase tracking-widest" style="color: <?= $loginTextColor ?>">
                        Fleet<span style="color: <?= $primaryColor ?>">Vision</span>
                    </h1>
                <?php endif; ?>
                <p class="text-sm font-medium opacity-70" style="color: <?= $loginTextColor ?>">
                    Bem-vindo de volta
                </p>
            </div>

            <form id="loginForm" class="space-y-6">
                <input type="hidden" name="tenant_slug" value="<?= $tenantSlug ?>">

                <div>
                    <label class="block text-xs font-bold uppercase mb-2 opacity-80" style="color: <?= $loginTextColor ?>">Email</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </span>
                        <input type="email" name="email" required 
                               class="w-full pl-10 pr-4 py-3 rounded-lg border border-gray-200 focus:border-blue-500 focus:ring-2 outline-none transition-all input-field"
                               placeholder="seu@email.com">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase mb-2 opacity-80" style="color: <?= $loginTextColor ?>">Senha</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </span>
                        <input type="password" name="password" required 
                               class="w-full pl-10 pr-4 py-3 rounded-lg border border-gray-200 focus:border-blue-500 focus:ring-2 outline-none transition-all input-field"
                               placeholder="••••••••">
                    </div>
                    <div class="flex justify-end mt-2">
                        <a href="#" class="text-xs hover:underline opacity-80" style="color: <?= $loginTextColor ?>">Esqueceu a senha?</a>
                    </div>
                </div>

                <button type="submit" 
                        class="w-full py-3 rounded-lg font-bold shadow-lg transition-all transform hover:translate-y-[-1px] hover:shadow-xl"
                        style="background-color: <?= $primaryColor ?>; color: <?= $loginBtnText ?>">
                    ENTRAR
                </button>
            </form>
        </div>
        
        <div class="py-4 text-center bg-black/5 text-xs font-medium opacity-60" style="color: <?= $loginTextColor ?>">
            &copy; <?= date('Y') ?> <?= $tenantData['name'] ?? 'Sistema' ?>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            const originalText = btn.innerText;
            
            btn.disabled = true;
            btn.innerText = 'Autenticando...';
            btn.style.opacity = '0.7';

            try {
                const formData = new FormData(e.target);
                const response = await fetch('/sys/auth/login', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    btn.innerText = 'Redirecionando...';
                    btn.style.backgroundColor = '#10b981';
                    setTimeout(() => window.location.href = data.redirect, 500);
                } else {
                    throw new Error(data.error || 'Erro de login');
                }
            } catch (error) {
                alert(error.message);
                btn.disabled = false;
                btn.innerText = originalText;
                btn.style.opacity = '1';
                btn.style.backgroundColor = '<?= $primaryColor ?>';
            }
        });
    </script>
</body>
</html>