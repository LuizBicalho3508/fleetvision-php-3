<div class="p-6 h-full flex flex-col">
    
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-800">Personalização de Login</h1>
        <p class="text-sm text-slate-500">Configure a aparência da tela de entrada para cada cliente.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 h-full">
        
        <div class="lg:col-span-1 bg-white p-6 rounded-2xl shadow-sm border border-slate-200 h-fit">
            
            <form id="formDesign" class="space-y-6">
                
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Selecione a Empresa</label>
                    <select id="selectTenant" class="w-full border border-slate-300 rounded-lg p-2.5 bg-slate-50 font-bold text-slate-700 outline-none focus:border-blue-500 transition" onchange="loadTenantSettings()">
                        <option value="">Carregando...</option>
                    </select>
                </div>

                <hr class="border-slate-100">

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Imagem de Fundo</label>
                    <div class="flex items-center gap-2">
                        <label class="flex-1 cursor-pointer bg-slate-50 border border-dashed border-slate-300 rounded-lg p-3 text-center hover:bg-slate-100 transition group">
                            <span class="text-xs text-slate-500 group-hover:text-blue-600"><i class="fas fa-image mr-1"></i> Escolher Arquivo</span>
                            <input type="file" name="background" id="inputBg" class="hidden" accept="image/*" onchange="previewBg(this)">
                        </label>
                        <button type="button" onclick="resetBg()" class="p-3 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg border border-transparent hover:border-red-100 transition" title="Remover Fundo">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">Recomendado: 1920x1080 (JPG/PNG)</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Opacidade do Card: <span id="opacityVal">95%</span></label>
                    <input type="range" name="login_opacity" id="inputOpacity" min="0.5" max="1" step="0.01" value="0.95" class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-blue-600" oninput="updatePreview()">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Cor do Botão de Entrar</label>
                    <div class="flex gap-2">
                        <input type="color" name="login_btn_color" id="inputColor" value="#2563eb" class="h-10 w-14 p-0 border-0 rounded cursor-pointer" oninput="updatePreview()">
                        <input type="text" id="inputColorTxt" class="flex-1 border border-slate-300 rounded-lg p-2 text-sm font-mono text-slate-600" readonly>
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl shadow-lg shadow-blue-200 transition transform hover:-translate-y-0.5">
                        <i class="fas fa-save mr-2"></i> Salvar Alterações
                    </button>
                    <p id="msgSuccess" class="text-center text-xs text-green-600 font-bold mt-3 hidden"><i class="fas fa-check-circle"></i> Salvo com sucesso!</p>
                </div>

            </form>
        </div>

        <div class="lg:col-span-2 bg-slate-100 rounded-2xl border-4 border-slate-800 shadow-2xl relative overflow-hidden flex items-center justify-center min-h-[500px]" id="previewContainer">
            
            <div id="previewBgLayer" class="absolute inset-0 bg-cover bg-center transition-all duration-500" style="background-image: url('assets/img/login-bg-default.jpg');"></div>
            <div class="absolute inset-0 bg-black/40"></div> <div id="previewCard" class="relative w-full max-w-sm bg-white p-8 rounded-2xl shadow-2xl backdrop-blur-sm transition-all duration-300" style="background-color: rgba(255, 255, 255, 0.95);">
                
                <div class="text-center mb-8">
                    <img id="previewLogo" src="assets/img/logo.png" class="h-12 mx-auto mb-4 object-contain">
                    <h2 class="text-2xl font-bold text-slate-800">Bem-vindo</h2>
                    <p class="text-slate-500 text-sm">Faça login para continuar</p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">E-mail</label>
                        <div class="w-full h-10 bg-slate-50 border border-slate-200 rounded-lg"></div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Senha</label>
                        <div class="w-full h-10 bg-slate-50 border border-slate-200 rounded-lg"></div>
                    </div>
                    
                    <button id="previewBtn" class="w-full py-3 rounded-xl text-white font-bold shadow-md transition-colors" style="background-color: #2563eb;">
                        Entrar no Sistema
                    </button>
                </div>

                <div class="mt-6 text-center">
                    <span class="text-xs text-slate-400">Preview em Tempo Real</span>
                </div>
            </div>

            <div class="absolute bottom-4 right-4 text-white/50 text-xs font-mono">
                /cliente-slug/login
            </div>
        </div>

    </div>
</div>

<script>
    let currentTenantId = null;

    // 1. Carregar Tenants no Select
    async function loadTenantsList() {
        const res = await apiFetch('/api/admin/tenants');
        const sel = document.getElementById('selectTenant');
        sel.innerHTML = '<option value="">-- Selecione --</option>';
        
        if (res.data) {
            res.data.forEach(t => {
                sel.innerHTML += `<option value="${t.id}">${t.name} (${t.slug})</option>`;
            });
        }
    }

    // 2. Carregar Configurações do Tenant Selecionado
    async function loadTenantSettings() {
        const id = document.getElementById('selectTenant').value;
        currentTenantId = id;
        
        if (!id) return;

        const res = await apiFetch(`/api/admin/design?id=${id}`);
        if (res.success) {
            const d = res.data;
            
            // Preenche Inputs
            document.getElementById('inputOpacity').value = d.login_opacity || 0.95;
            document.getElementById('inputColor').value = d.login_btn_color || '#2563eb';
            
            // Preview
            const bgUrl = d.login_bg_url ? d.login_bg_url : ''; // Se vazio, usa padrão CSS ou cor
            const logoUrl = d.logo_url ? d.logo_url : ''; // Logo da empresa

            updatePreviewUI(d.login_opacity, d.login_btn_color, bgUrl, logoUrl);
        }
    }

    // 3. Atualiza Preview Visual (Live)
    function updatePreview() {
        const op = document.getElementById('inputOpacity').value;
        const color = document.getElementById('inputColor').value;
        
        document.getElementById('inputColorTxt').value = color;
        document.getElementById('opacityVal').innerText = Math.round(op * 100) + '%';
        
        const card = document.getElementById('previewCard');
        const btn = document.getElementById('previewBtn');

        card.style.backgroundColor = `rgba(255, 255, 255, ${op})`;
        btn.style.backgroundColor = color;
    }

    function updatePreviewUI(opacity, color, bgUrl, logoUrl) {
        // Seta inputs
        document.getElementById('inputOpacity').value = opacity;
        document.getElementById('inputColor').value = color;
        
        // Seta visual
        updatePreview(); // Aplica cores/opacidade

        // Background
        const bgLayer = document.getElementById('previewBgLayer');
        if (bgUrl) {
            bgLayer.style.backgroundImage = `url('${bgUrl}')`;
        } else {
            bgLayer.style.backgroundImage = ''; // Remove inline, volta pro CSS padrão ou cor
            bgLayer.style.backgroundColor = '#1e293b'; // Fallback
        }

        // Logo
        if (logoUrl) {
            document.getElementById('previewLogo').src = logoUrl;
        } else {
            document.getElementById('previewLogo').src = ''; // Fallback
        }
    }

    // Preview de arquivo local (antes de salvar)
    function previewBg(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('previewBgLayer').style.backgroundImage = `url('${e.target.result}')`;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Resetar Fundo (API)
    async function resetBg() {
        if (!currentTenantId) return;
        if (!confirm('Remover imagem personalizada e voltar ao padrão?')) return;

        const res = await apiFetch('/api/admin/design/reset', { 
            method: 'POST', 
            body: JSON.stringify({ tenant_id: currentTenantId }) 
        });
        
        if (res.success) {
            loadTenantSettings(); // Recarrega para limpar
            showToast('Fundo removido.');
        }
    }

    // Salvar
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
            const res = await fetch('/api/admin/design/save', { method: 'POST', body: formData });
            const json = await res.json();

            if (json.success) {
                showToast('Design atualizado com sucesso!');
                // Se retornou nova URL de BG, atualiza cache visual
                if (json.bg_url) {
                    document.getElementById('previewBgLayer').style.backgroundImage = `url('${json.bg_url}')`;
                }
            } else {
                showToast(json.error || 'Erro ao salvar', 'error');
            }
        } catch (err) {
            showToast('Erro de conexão', 'error');
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    };

    // Init
    loadTenantsList();
    updatePreview(); // Seta estado inicial
</script>