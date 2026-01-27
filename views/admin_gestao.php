<div class="p-6 h-full flex flex-col overflow-y-auto">
    
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-800">Personalização de Login</h1>
        <p class="text-sm text-slate-500">Configure a aparência da tela de entrada para cada cliente.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 pb-10">
        
        <div class="lg:col-span-1 bg-white p-6 rounded-2xl shadow-sm border border-slate-200 h-fit">
            
            <form id="formDesign" class="space-y-5">
                
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Selecione a Empresa</label>
                    <select id="selectTenant" class="w-full border border-slate-300 rounded-lg p-2.5 bg-slate-50 font-bold text-slate-700 outline-none focus:border-blue-500 transition" onchange="loadTenantSettings()">
                        <option value="">Carregando...</option>
                    </select>
                </div>

                <hr class="border-slate-100">

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Título de Boas-vindas</label>
                    <input type="text" name="login_title" id="inputTitle" placeholder="Ex: Bem-vindo" class="w-full border border-slate-300 rounded-lg p-2 text-sm focus:border-blue-500 outline-none" oninput="updatePreview()">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Subtítulo</label>
                    <input type="text" name="login_subtitle" id="inputSubtitle" placeholder="Ex: Acesse sua conta" class="w-full border border-slate-300 rounded-lg p-2 text-sm focus:border-blue-500 outline-none" oninput="updatePreview()">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Imagem de Fundo</label>
                    <div class="flex items-center gap-2">
                        <label class="flex-1 cursor-pointer bg-slate-50 border border-dashed border-slate-300 rounded-lg p-3 text-center hover:bg-slate-100 transition group">
                            <span class="text-xs text-slate-500 group-hover:text-blue-600"><i class="fas fa-image mr-1"></i> Escolher Arquivo</span>
                            <input type="file" name="background" id="inputBg" class="hidden" accept="image/*" onchange="previewBg(this)">
                        </label>
                        <button type="button" onclick="resetBg()" class="p-3 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg border border-transparent hover:border-red-100 transition" title="Restaurar Padrão">
                            <i class="fas fa-undo"></i>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Opacidade Card</label>
                        <input type="range" name="login_opacity" id="inputOpacity" min="0.5" max="1" step="0.05" value="0.95" class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-blue-600" oninput="updatePreview()">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Cor Botão</label>
                        <div class="flex items-center gap-2">
                            <input type="color" name="login_btn_color" id="inputColor" value="#2563eb" class="h-8 w-8 p-0 border-0 rounded cursor-pointer" oninput="updatePreview()">
                            <span class="text-xs font-mono text-slate-500" id="hexColor">#2563eb</span>
                        </div>
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl shadow-lg shadow-blue-200 transition transform hover:-translate-y-0.5">
                        <i class="fas fa-save mr-2"></i> Salvar Personalização
                    </button>
                </div>

            </form>
        </div>

        <div class="lg:col-span-2 bg-slate-900 rounded-2xl border-4 border-slate-800 shadow-2xl relative overflow-hidden flex items-center justify-center min-h-[500px]" id="previewContainer">
            
            <div id="previewBgLayer" class="absolute inset-0 bg-cover bg-center transition-all duration-500" style="background-image: url('https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?auto=format&fit=crop&q=80');"></div>
            <div class="absolute inset-0 bg-black/50"></div> 
            
            <div id="previewCard" class="relative w-full max-w-sm bg-white p-8 rounded-2xl shadow-2xl backdrop-blur-sm transition-all duration-300 transform scale-90 md:scale-100" style="background-color: rgba(255, 255, 255, 0.95);">
                
                <div class="text-center mb-8">
                    <div id="logoContainer" class="h-12 mx-auto mb-4 flex items-center justify-center">
                        <img id="previewLogo" src="" class="h-full object-contain hidden">
                        <i id="previewLogoPlaceholder" class="fas fa-cube text-4xl text-slate-300"></i>
                    </div>
                    
                    <h2 class="text-2xl font-bold text-slate-800" id="previewTitle">Bem-vindo</h2>
                    <p class="text-slate-500 text-sm" id="previewSubtitle">Faça login para continuar</p>
                </div>

                <div class="space-y-4 pointer-events-none opacity-80">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">E-mail</label>
                        <div class="w-full h-10 bg-slate-50 border border-slate-200 rounded-lg"></div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Senha</label>
                        <div class="w-full h-10 bg-slate-50 border border-slate-200 rounded-lg"></div>
                    </div>
                    
                    <button id="previewBtn" class="w-full py-3 rounded-xl text-white font-bold shadow-md transition-colors flex justify-center items-center gap-2" style="background-color: #2563eb;">
                        Entrar <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <div class="absolute bottom-4 right-4 bg-black/50 px-3 py-1 rounded-full text-white/70 text-xs font-mono border border-white/10">
                URL: /<span id="slugPreview">cliente</span>/login
            </div>
        </div>

    </div>
</div>

<script>
    let currentTenantId = null;

    // 1. Carregar Tenants (Rota corrigida para /sys/)
    async function loadTenantsList() {
        try {
            const res = await apiFetch('/sys/admin/tenants');
            const sel = document.getElementById('selectTenant');
            sel.innerHTML = '<option value="">-- Selecione a Empresa --</option>';
            
            if (res.data) {
                res.data.forEach(t => {
                    sel.innerHTML += `<option value="${t.id}" data-slug="${t.slug}">${t.name}</option>`;
                });
            }
        } catch(e) { console.error("Erro ao carregar tenants", e); }
    }

    // 2. Carregar Configurações (Rota corrigida para /sys/)
    async function loadTenantSettings() {
        const sel = document.getElementById('selectTenant');
        const id = sel.value;
        const slug = sel.options[sel.selectedIndex].getAttribute('data-slug');
        currentTenantId = id;
        
        document.getElementById('slugPreview').innerText = slug || 'cliente';

        if (!id) return;

        const res = await apiFetch(`/sys/admin/design?id=${id}`);
        if (res.success) {
            const d = res.data;
            
            // Preenche Inputs
            document.getElementById('inputOpacity').value = d.login_opacity || 0.95;
            document.getElementById('inputColor').value = d.login_btn_color || '#2563eb';
            document.getElementById('inputTitle').value = d.login_title || 'Bem-vindo';
            document.getElementById('inputSubtitle').value = d.login_subtitle || 'Faça login para continuar';
            
            // Update UI
            updatePreviewUI(d);
        }
    }

    // 3. Atualiza Preview (Visual)
    function updatePreview() {
        // Pega valores
        const op = document.getElementById('inputOpacity').value;
        const color = document.getElementById('inputColor').value;
        const title = document.getElementById('inputTitle').value || 'Bem-vindo';
        const subtitle = document.getElementById('inputSubtitle').value || 'Faça login para continuar';
        
        // Aplica
        document.getElementById('hexColor').innerText = color;
        
        const card = document.getElementById('previewCard');
        card.style.backgroundColor = `rgba(255, 255, 255, ${op})`;
        
        const btn = document.getElementById('previewBtn');
        btn.style.backgroundColor = color;
        
        document.getElementById('previewTitle').innerText = title;
        document.getElementById('previewSubtitle').innerText = subtitle;
    }

    function updatePreviewUI(data) {
        updatePreview();

        // Imagem de Fundo (Se não tiver, usa placeholder do Unsplash)
        const bgLayer = document.getElementById('previewBgLayer');
        if (data.login_bg_url) {
            bgLayer.style.backgroundImage = `url('${data.login_bg_url}')`;
        } else {
            bgLayer.style.backgroundImage = "url('https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?auto=format&fit=crop&q=80')";
        }

        // Logo
        const imgLogo = document.getElementById('previewLogo');
        const iconLogo = document.getElementById('previewLogoPlaceholder');
        
        if (data.logo_url) {
            imgLogo.src = data.logo_url;
            imgLogo.classList.remove('hidden');
            iconLogo.classList.add('hidden');
        } else {
            imgLogo.classList.add('hidden');
            iconLogo.classList.remove('hidden');
        }
    }

    // Preview de Arquivo Local
    function previewBg(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('previewBgLayer').style.backgroundImage = `url('${e.target.result}')`;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Resetar Fundo (Rota /sys/)
    async function resetBg() {
        if (!currentTenantId) return;
        if (!confirm('Voltar ao fundo padrão?')) return;

        const res = await apiFetch('/sys/admin/design/reset', { 
            method: 'POST', 
            body: JSON.stringify({ tenant_id: currentTenantId }) 
        });
        
        if (res.success) {
            loadTenantSettings();
            showToast('Fundo restaurado.');
        }
    }

    // Salvar (Rota /sys/)
    document.getElementById('formDesign').onsubmit = async (e) => {
        e.preventDefault();
        if (!currentTenantId) {
            showToast('Selecione uma empresa primeiro.', 'error');
            return;
        }

        const btn = e.target.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
        btn.disabled = true;

        const formData = new FormData(e.target);
        formData.append('tenant_id', currentTenantId);

        try {
            // Fetch direto para enviar FormData (upload de arquivo)
            // IMPORTANTE: Não usar apiFetch aqui pois ele tenta stringify JSON
            const res = await fetch('/sys/admin/design/save', { 
                method: 'POST', 
                body: formData 
            });
            
            const json = await res.json();

            if (json.success) {
                showToast('Design salvo com sucesso!');
                if (json.bg_url) {
                    document.getElementById('previewBgLayer').style.backgroundImage = `url('${json.bg_url}')`;
                }
            } else {
                showToast(json.error || 'Erro ao salvar', 'error');
            }
        } catch (err) {
            console.error(err);
            showToast('Erro de conexão ao salvar', 'error');
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    };

    // Inicialização
    loadTenantsList();
    updatePreview(); 
</script>