<div class="p-6 space-y-6">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-800">Meu Perfil</h1>
        <p class="text-sm text-slate-500">Gerencie suas informações pessoais e segurança.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
            <h3 class="font-bold text-slate-800 mb-4 pb-2 border-b border-slate-100">Informações Básicas</h3>
            
            <form id="formProfile" class="space-y-6">
                <div class="flex items-center gap-4">
                    <div class="relative group">
                        <img id="avatarPreview" src="/<?php echo $user['avatar'] ?? 'assets/default-avatar.png'; ?>" 
                             onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>&background=random'"
                             class="w-20 h-20 rounded-full object-cover border-2 border-slate-100 shadow-sm">
                        
                        <label for="avatarInput" class="absolute inset-0 bg-black/40 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 cursor-pointer transition text-white">
                            <i class="fas fa-camera"></i>
                        </label>
                        <input type="file" name="avatar" id="avatarInput" accept="image/*" class="hidden" onchange="previewImage(this)">
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-700">Sua Foto</p>
                        <p class="text-xs text-slate-400">Clique na imagem para alterar. (JPG, PNG)</p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nome Completo</label>
                    <input type="text" name="name" id="profileName" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm focus:border-blue-500 outline-none" required>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">E-mail (Login)</label>
                    <input type="email" id="profileEmail" class="w-full bg-slate-100 border border-slate-200 rounded-lg p-2.5 text-sm text-slate-500 cursor-not-allowed" disabled title="Contate o suporte para alterar o e-mail">
                </div>

                <div class="pt-2 text-right">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-5 rounded-xl shadow-lg shadow-blue-200 transition">
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
            <h3 class="font-bold text-slate-800 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                <i class="fas fa-shield-alt text-blue-500"></i> Segurança
            </h3>
            
            <form id="formPassword" class="space-y-4">
                <div class="bg-yellow-50 text-yellow-700 p-3 rounded-lg text-xs mb-4">
                    <i class="fas fa-info-circle mr-1"></i> Para sua segurança, exigimos a senha atual para realizar a troca.
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Senha Atual</label>
                    <input type="password" name="current_password" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm focus:border-blue-500 outline-none" required>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nova Senha</label>
                    <input type="password" name="new_password" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm focus:border-blue-500 outline-none" required minlength="6">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Confirmar Nova Senha</label>
                    <input type="password" name="confirm_password" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-sm focus:border-blue-500 outline-none" required>
                </div>

                <div class="pt-2 text-right">
                    <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-bold py-2.5 px-5 rounded-xl shadow-lg transition">
                        Atualizar Senha
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // 1. Carrega Dados
    async function loadProfile() {
        const res = await apiFetch('/api/profile');
        if(res && res.data) {
            document.getElementById('profileName').value = res.data.name;
            document.getElementById('profileEmail').value = res.data.email;
        }
    }

    // 2. Preview de Imagem
    window.previewImage = function(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('avatarPreview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // 3. Salvar Perfil (FormData para upload)
    document.getElementById('formProfile').onsubmit = async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target); // Suporta arquivo automaticamente

        // Usamos fetch nativo aqui pois apiFetch é wrapper JSON, e upload precisa ser multipart
        try {
            const res = await fetch('/api/profile/save', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();
            
            if(json.success) {
                showToast('Perfil atualizado com sucesso!');
                // Atualiza avatar no header se houver
                if(json.avatar) {
                    // Recarregar página para atualizar todas as refs de sessão ou manipular DOM
                    setTimeout(() => location.reload(), 1000);
                }
            } else {
                showToast(json.error || 'Erro ao salvar', 'error');
            }
        } catch(err) { console.error(err); showToast('Erro de conexão', 'error'); }
    };

    // 4. Trocar Senha
    document.getElementById('formPassword').onsubmit = async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);

        if(data.new_password !== data.confirm_password) {
            showToast('As novas senhas não conferem', 'error');
            return;
        }

        const res = await apiFetch('/api/profile/password', { 
            method: 'POST', 
            body: JSON.stringify(data) 
        });

        if(res.success) {
            showToast('Senha alterada! Faça login novamente.');
            e.target.reset();
            setTimeout(() => window.location.href = `/${slug}/logout`, 2000);
        } else {
            showToast(res.error || 'Erro ao trocar senha', 'error');
        }
    };

    loadProfile();
</script>