<?php
// views/recover.php
if (session_status() === PHP_SESSION_NONE) session_start();

// Configurações visuais (mesma lógica do login)
$tenant = $_SESSION['tenant_data'] ?? [
    'name' => 'FleetVision',
    'primary_color' => '#3b82f6',
    'login_bg_url' => 'https://images.unsplash.com/photo-1494548162494-384bba4ab999?auto=format&fit=crop&w=1950&q=80',
    'slug' => 'admin' // Fallback
];
$slug = $tenant['slug'] ?? 'admin';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha | <?php echo htmlspecialchars($tenant['name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .login-bg {
            background-image: url('<?php echo $tenant['login_bg_url']; ?>');
            background-size: cover;
            background-position: center;
        }
        .primary-btn {
            background-color: <?php echo $tenant['primary_color']; ?>;
            transition: 0.3s;
        }
        .primary-btn:hover { filter: brightness(110%); }
    </style>
</head>
<body class="login-bg h-screen w-full flex items-center justify-center font-sans">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

    <div class="relative z-10 w-full max-w-md p-8 bg-white rounded-2xl shadow-2xl m-4 border border-white/20">
        
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                <i class="fas fa-lock-open"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Esqueceu a senha?</h1>
            <p class="text-sm text-gray-500 mt-2">Informe seu e-mail e enviaremos as instruções.</p>
        </div>

        <div id="status-msg" class="hidden p-3 rounded-lg mb-4 text-sm font-medium flex items-center gap-2">
            <i class="fas fa-info-circle"></i> <span></span>
        </div>

        <form id="recoverForm" class="space-y-5">
            <input type="hidden" name="slug" value="<?php echo $slug; ?>">
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">E-mail Cadastrado</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="email" required class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition" placeholder="seu@email.com">
                </div>
            </div>

            <button type="submit" id="btnSubmit" class="primary-btn w-full text-white font-bold py-3.5 rounded-xl shadow-lg uppercase tracking-wider text-sm">
                Enviar Link
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="/<?php echo $slug; ?>/login" class="text-sm font-bold text-gray-400 hover:text-gray-600 transition flex items-center justify-center gap-2">
                <i class="fas fa-arrow-left"></i> Voltar para Login
            </a>
        </div>
    </div>

    <script>
        document.getElementById('recoverForm').onsubmit = async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btnSubmit');
            const msg = document.getElementById('status-msg');
            const originalText = btn.innerText;

            btn.disabled = true;
            btn.innerText = 'Enviando...';
            msg.className = 'hidden p-3 rounded-lg mb-4 text-sm font-medium flex items-center gap-2'; // Reset classes

            try {
                const data = Object.fromEntries(new FormData(e.target));
                
                const res = await fetch('/api/auth/recover', { 
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                
                const json = await res.json();

                msg.classList.remove('hidden');
                if (json.success) {
                    msg.classList.add('bg-green-100', 'text-green-700');
                    msg.querySelector('span').innerText = json.message;
                    e.target.reset();
                } else {
                    msg.classList.add('bg-red-100', 'text-red-700');
                    throw new Error(json.error || 'Erro desconhecido');
                }
            } catch (err) {
                msg.classList.remove('hidden', 'bg-green-100', 'text-green-700');
                msg.classList.add('bg-red-100', 'text-red-700');
                msg.querySelector('span').innerText = err.message;
            } finally {
                btn.disabled = false;
                btn.innerText = originalText;
            }
        };
    </script>
</body>
</html>