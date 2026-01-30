<div class="p-6 max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Personalização Visual</h2>
            <p class="text-sm text-slate-500">Configure a aparência do sistema e da tela de login.</p>
        </div>
        <button onclick="saveDesign()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg font-bold shadow-lg transition flex items-center gap-2">
            <i class="fas fa-save"></i> Salvar Alterações
        </button>
    </div>

    <form id="designForm" class="grid grid-cols-1 lg:grid-cols-3 gap-6" enctype="multipart/form-data">
        <input type="hidden" name="id" id="tenantId">

        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h3 class="font-bold text-slate-700 mb-4 flex items-center gap-2"><i class="fas fa-fingerprint text-blue-500"></i> Identidade</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Logo</label>
                        <div class="flex items-center gap-4">
                            <img id="previewLogo" src="" class="w-20 h-20 object-contain border rounded bg-slate-50 hidden">
                            <input type="file" name="logo" id="logoInput" class="hidden" accept="image/*" onchange="previewImage(this, 'previewLogo')">
                            <button type="button" onclick="document.getElementById('logoInput').click()" class="text-xs bg-slate-100 hover:bg-slate-200 px-3 py-2 rounded font-medium">Alterar Logo</button>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Primária</label>
                            <div class="flex items-center gap-2 border rounded p-1"><input type="color" name="primary_color" id="primary_color" class="w-8 h-8 rounded cursor-pointer border-none bg-transparent"></div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Secundária</label>
                            <div class="flex items-center gap-2 border rounded p-1"><input type="color" name="secondary_color" id="secondary_color" class="w-8 h-8 rounded cursor-pointer border-none bg-transparent"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h3 class="font-bold text-slate-700 mb-4"><i class="fas fa-columns text-purple-500"></i> Sidebar</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Fundo</label><div class="flex items-center gap-2 border rounded p-1"><input type="color" name="sidebar_color" id="sidebar_color" class="w-8 h-8 rounded cursor-pointer border-none bg-transparent"></div></div>
                    <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Texto</label><div class="flex items-center gap-2 border rounded p-1"><input type="color" name="sidebar_text_color" id="sidebar_text_color" class="w-8 h-8 rounded cursor-pointer border-none bg-transparent"></div></div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
            <h3 class="font-bold text-slate-700 mb-4"><i class="fas fa-user-lock text-green-500"></i> Tela de Login</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Wallpaper</label>
                        <input type="file" name="login_bg" id="bgInput" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" accept="image/*" onchange="previewImage(this, 'previewBg')">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Fundo Sólido</label><div class="flex items-center gap-2 border rounded p-1"><input type="color" name="login_bg_color" id="login_bg_color" class="w-8 h-8 rounded cursor-pointer"></div></div>
                        <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Card</label><div class="flex items-center gap-2 border rounded p-1"><input type="color" name="login_card_color" id="login_card_color" class="w-8 h-8 rounded cursor-pointer"></div></div>
                        <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Texto</label><div class="flex items-center gap-2 border rounded p-1"><input type="color" name="login_text_color" id="login_text_color" class="w-8 h-8 rounded cursor-pointer"></div></div>
                        <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Btn Texto</label><div class="flex items-center gap-2 border rounded p-1"><input type="color" name="login_btn_text_color" id="login_btn_text_color" class="w-8 h-8 rounded cursor-pointer"></div></div>
                        <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1">Input Bg</label><div class="flex items-center gap-2 border rounded p-1"><input type="color" name="login_input_bg_color" id="login_input_bg_color" class="w-8 h-8 rounded cursor-pointer"></div></div>
                    </div>
                </div>
                <div class="bg-slate-100 rounded-xl p-4 border border-slate-200 flex items-center justify-center relative overflow-hidden h-80" id="loginPreviewContainer">
                    <div id="loginPreviewBg" class="absolute inset-0 bg-cover bg-center"></div>
                    <div id="loginPreviewCard" class="relative z-10 w-full max-w-[200px] rounded-xl shadow-xl p-4 transform scale-90">
                        <div class="h-4 w-16 bg-slate-200 mx-auto mb-3 rounded opacity-50"></div>
                        <div class="space-y-2">
                            <div id="loginPreviewInput" class="h-6 w-full border rounded"></div>
                            <div id="loginPreviewBtn" class="h-6 w-full rounded flex items-center justify-center text-[10px] font-bold shadow">ENTRAR</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    const fieldIds = [
        'primary_color', 'secondary_color', 'sidebar_color', 'sidebar_text_color', 
        'login_card_color', 'login_text_color', 'login_bg_color', 'login_input_bg_color', 'login_btn_text_color'
    ];

    document.addEventListener('DOMContentLoaded', () => {
        loadSettings();
        fieldIds.forEach(id => {
            const el = document.getElementById(id);
            if(el) el.addEventListener('input', updatePreview);
        });
    });

    function updatePreview() {
        const bg = document.getElementById('login_bg_color').value;
        const card = document.getElementById('login_card_color').value;
        const btnBg = document.getElementById('primary_color').value;
        const btnTxt = document.getElementById('login_btn_text_color').value;
        const inp = document.getElementById('login_input_bg_color').value;

        const pBg = document.getElementById('loginPreviewBg');
        const pCard = document.getElementById('loginPreviewCard');
        const pBtn = document.getElementById('loginPreviewBtn');
        const pInp = document.getElementById('loginPreviewInput');
        
        if(pBg) pBg.style.backgroundColor = bg;
        if(pCard) pCard.style.backgroundColor = card;
        if(pBtn) { pBtn.style.backgroundColor = btnBg; pBtn.style.color = btnTxt; }
        if(pInp) pInp.style.backgroundColor = inp;
    }

    async function loadSettings() {
        try {
            const urlParams = new URLSearchParams(window.location.search);
            const id = urlParams.get('id');
            if(id) document.getElementById('tenantId').value = id;

            const url = id ? `/sys/admin/design?id=${id}` : '/sys/admin/design';
            const res = await apiFetch(url); // Aqui usamos apiFetch pois é GET (sem corpo complexo)

            fieldIds.forEach(id => {
                if(res[id] && document.getElementById(id)) {
                    document.getElementById(id).value = res[id];
                }
            });

            if(res.logo_url) {
                document.getElementById('previewLogo').src = res.logo_url;
                document.getElementById('previewLogo').classList.remove('hidden');
            }
            if(res.login_bg_url) {
                document.getElementById('loginPreviewBg').style.backgroundImage = `url('${res.login_bg_url}')`;
            }
            updatePreview();
        } catch (e) { console.error(e); }
    }

    function previewImage(input, previewId) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                if(previewId === 'previewBg') {
                     document.getElementById('loginPreviewBg').style.backgroundImage = `url('${e.target.result}')`;
                } else {
                    const el = document.getElementById(previewId);
                    el.src = e.target.result;
                    el.classList.remove('hidden');
                }
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // --- AQUI ESTÁ A CORREÇÃO PRINCIPAL ---
    async function saveDesign() {
        const btn = document.querySelector('button[onclick="saveDesign()"]');
        const originalText = btn.innerHTML;
        btn.innerHTML = 'Salvando...';
        btn.disabled = true;

        const form = document.getElementById('designForm');
        
        // 1. Garantia de ID (URL ou Hidden)
        const urlParams = new URLSearchParams(window.location.search);
        const idFromUrl = urlParams.get('id');
        if(idFromUrl) document.getElementById('tenantId').value = idFromUrl;

        // 2. Cria FormData Nativo
        const formData = new FormData(form);

        try {
            // 3. USA FETCH NATIVO (Ignora apiFetch para evitar cabeçalhos JSON errados)
            const response = await fetch('/sys/admin/design/save', {
                method: 'POST',
                body: formData 
                // NÃO defina Content-Type! O navegador define sozinho com o Boundary.
            });

            const res = await response.json();

            console.log("Servidor:", res);

            if (res.success) {
                showToast('Salvo com sucesso!', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(res.message || 'Erro ao salvar', 'error');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        } catch (error) {
            console.error(error);
            showToast('Erro de conexão', 'error');
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }
</script>