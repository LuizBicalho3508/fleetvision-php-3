<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Gestão de Empresas (Tenants)</h1>
            <p class="text-sm text-slate-500">Gerencie clientes, identidade visual e acessos.</p>
        </div>
        <button onclick="openModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-bold shadow transition flex items-center gap-2">
            <i class="fas fa-plus"></i> Nova Empresa
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="w-full text-sm text-left">
            <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-xs">
                <tr>
                    <th class="px-6 py-4">ID</th>
                    <th class="px-6 py-4">Identidade</th> <th class="px-6 py-4">Cores</th>      <th class="px-6 py-4">Acesso (Slug)</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4 text-right">Ações</th>
                </tr>
            </thead>
            <tbody id="tableBody" class="divide-y divide-slate-100">
                <tr><td colspan="6" class="p-6 text-center text-slate-400">Carregando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div id="modalTenant" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal()"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white w-full max-w-2xl rounded-2xl shadow-2xl p-6 max-h-[90vh] overflow-y-auto">
        
        <div class="flex justify-between items-center mb-4 border-b pb-4">
            <h3 class="text-xl font-bold text-slate-800" id="modalTitle">Nova Empresa</h3>
            <button onclick="closeModal()" class="text-slate-400 hover:text-red-500"><i class="fas fa-times text-xl"></i></button>
        </div>
        
        <form id="formTenant" class="space-y-6">
            <input type="hidden" name="id" id="inputId">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nome da Empresa</label>
                        <input type="text" name="name" id="inputName" onkeyup="generateSlug(this.value)" required class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Slug (URL)</label>
                        <div class="flex items-center">
                            <span class="bg-slate-100 border border-r-0 border-slate-300 rounded-l-lg p-2.5 text-slate-500 text-sm">/</span>
                            <input type="text" name="slug" id="inputSlug" required class="w-full border border-slate-300 rounded-r-lg p-2.5 focus:border-blue-500 outline-none bg-slate-50 text-slate-600 font-mono text-sm">
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">Identificador único na URL. Apenas letras minúsculas e hifens.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Status</label>
                        <select name="status" id="inputStatus" class="w-full border border-slate-300 rounded-lg p-2.5 bg-white">
                            <option value="active">Ativo</option>
                            <option value="inactive">Inativo</option>
                        </select>
                    </div>
                </div>

                <div class="space-y-4 bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <h4 class="text-xs font-bold text-slate-400 uppercase border-b pb-2 mb-2">Identidade Visual</h4>
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Logo</label>
                        
                        <div id="previewLogoContainer" class="hidden mb-2 p-2 bg-white border border-dashed border-slate-300 rounded text-center">
                            <span class="text-[10px] text-slate-400 block mb-1">Atual:</span>
                            <img id="previewLogoImg" src="" class="h-10 mx-auto object-contain">
                        </div>

                        <input type="file" name="logo" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200 cursor-pointer">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Cor Primária</label>
                            <div class="flex gap-2">
                                <input type="color" id="inputColorPrimaryPicker" onchange="syncColorInput('primary', this.value)" class="h-10 w-10 p-0 border-0 rounded cursor-pointer">
                                <input type="text" name="primary_color" id="inputColorPrimaryText" onchange="syncColorInput('primary', this.value, true)" placeholder="#2563eb" class="flex-1 border border-slate-300 rounded px-2 text-sm uppercase font-mono">
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1">Botões, Links</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Cor Sidebar</label>
                            <div class="flex gap-2">
                                <input type="color" id="inputColorSidebarPicker" onchange="syncColorInput('sidebar', this.value)" class="h-10 w-10 p-0 border-0 rounded cursor-pointer">
                                <input type="text" name="sidebar_color" id="inputColorSidebarText" onchange="syncColorInput('sidebar', this.value, true)" placeholder="#ffffff" class="flex-1 border border-slate-300 rounded px-2 text-sm uppercase font-mono">
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1">Fundo do Menu</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-4 flex gap-3 border-t mt-2">
                <button type="button" onclick="closeModal()" class="flex-1 py-3 text-slate-500 font-bold hover:bg-slate-100 rounded-lg transition">Cancelar</button>
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg shadow-lg shadow-blue-500/30 transition transform active:scale-95">Salvar Empresa</button>
            </div>
        </form>
    </div>
</div>

<script>
    // --- LÓGICA DE DADOS ---
    async function loadTenants() {
        try {
            const res = await apiFetch('/sys/admin/tenants'); 
            const tbody = document.getElementById('tableBody');
            
            if (!res.data || res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="p-6 text-center text-slate-400">Nenhuma empresa cadastrada.</td></tr>';
                return;
            }

            tbody.innerHTML = res.data.map(t => {
                // Tratamento seguro da imagem (Caminho Absoluto)
                let logoSrc = '';
                if (t.logo_url) {
                    logoSrc = t.logo_url.startsWith('http') || t.logo_url.startsWith('/') 
                              ? t.logo_url 
                              : '/' + t.logo_url;
                }
                
                const logoHtml = logoSrc 
                    ? `<img src="${logoSrc}" class="h-8 w-auto object-contain max-w-[100px]" onerror="this.style.display='none'">` 
                    : `<div class="h-8 w-8 bg-slate-100 rounded flex items-center justify-center text-slate-400"><i class="fas fa-building"></i></div>`;

                // Cores (Fallback visual)
                const pColor = t.primary_color || '#2563eb';
                const sColor = t.sidebar_color || '#ffffff';

                return `
                <tr class="hover:bg-slate-50 transition border-b border-slate-50">
                    <td class="px-6 py-4 font-mono text-xs text-slate-400">#${t.id}</td>
                    
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            ${logoHtml}
                            <span class="font-bold text-slate-700">${t.name}</span>
                        </div>
                    </td>

                    <td class="px-6 py-4">
                        <div class="flex gap-2 text-xs">
                            <div class="flex flex-col items-center group cursor-help">
                                <div class="w-6 h-6 rounded-full border border-slate-200 shadow-sm" style="background-color: ${pColor}"></div>
                                <span class="opacity-0 group-hover:opacity-100 absolute -mt-6 bg-black text-white px-1 rounded text-[10px] transition">${pColor}</span>
                            </div>
                            <div class="flex flex-col items-center group cursor-help">
                                <div class="w-6 h-6 rounded-full border border-slate-200 shadow-sm relative" style="background-color: ${sColor}">
                                    ${sColor.toLowerCase() === '#ffffff' ? '<span class="absolute inset-0 flex items-center justify-center text-[8px] text-slate-400">W</span>' : ''}
                                </div>
                                <span class="opacity-0 group-hover:opacity-100 absolute -mt-6 bg-black text-white px-1 rounded text-[10px] transition">${sColor}</span>
                            </div>
                        </div>
                    </td>

                    <td class="px-6 py-4">
                        <span class="bg-blue-50 text-blue-600 px-2 py-1 rounded text-xs font-mono select-all">/${t.slug}</span>
                    </td>

                    <td class="px-6 py-4">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold ${t.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">
                            <span class="w-1.5 h-1.5 rounded-full ${t.status === 'active' ? 'bg-green-500' : 'bg-red-500'}"></span>
                            ${t.status === 'active' ? 'Ativo' : 'Inativo'}
                        </span>
                    </td>

                    <td class="px-6 py-4 text-right">
                        <button onclick='editTenant(${JSON.stringify(t)})' class="text-blue-500 hover:bg-blue-50 p-2 rounded transition" title="Editar"><i class="fas fa-edit"></i></button>
                        <button onclick="deleteTenant(${t.id})" class="text-red-400 hover:bg-red-50 p-2 rounded transition" title="Excluir"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `}).join('');
        } catch(e) {
            console.error(e);
            showToast('Erro ao carregar empresas.', 'error');
        }
    }

    // --- FORM SUBMIT (Salvar) ---
    document.getElementById('formTenant').onsubmit = async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
        btn.disabled = true;

        const formData = new FormData(e.target);

        try {
            const res = await fetch('/sys/admin/tenants/save', { 
                method: 'POST', 
                body: formData 
            });
            
            const rawText = await res.text();
            let json;

            try { json = JSON.parse(rawText); } 
            catch (err) { throw new Error("Erro no servidor: " + rawText); }

            if (json.success) {
                showToast('Empresa salva com sucesso!');
                closeModal();
                loadTenants();
            } else {
                showToast(json.error || 'Erro ao salvar', 'error');
            }
        } catch (err) {
            console.error(err);
            showToast('Erro: ' + err.message, 'error');
        } finally {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    };

    // --- DELETE ---
    async function deleteTenant(id) {
        if(!confirm('ATENÇÃO: Isso pode bloquear o acesso de todos os usuários desta empresa. Deseja continuar?')) return;
        
        const res = await apiFetch('/sys/admin/tenants/delete', {
            method: 'POST',
            body: JSON.stringify({ id })
        });

        if(res.success) {
            showToast('Empresa removida.');
            loadTenants();
        } else {
            showToast('Erro ao remover.', 'error');
        }
    }

    // --- MODAL UTILS & HELPERS ---
    
    // Gera URL amigável automaticamente enquanto digita
    function generateSlug(text) {
        // Só gera se for novo cadastro (ID vazio)
        if(document.getElementById('inputId').value === '') {
            const slug = text.toLowerCase()
                             .trim()
                             .replace(/[^\w\s-]/g, '') // Remove caracteres especiais
                             .replace(/[\s_-]+/g, '-') // Substitui espaços por hifens
                             .replace(/^-+|-+$/g, ''); // Remove hifens do começo/fim
            document.getElementById('inputSlug').value = slug;
        }
    }

    function syncColorInput(type, value, isText = false) {
        if(isText) {
            document.getElementById(`inputColor${type.charAt(0).toUpperCase() + type.slice(1)}Picker`).value = value;
        } else {
            document.getElementById(`inputColor${type.charAt(0).toUpperCase() + type.slice(1)}Text`).value = value;
        }
    }

    function openModal() {
        document.getElementById('formTenant').reset();
        document.getElementById('inputId').value = '';
        document.getElementById('modalTitle').innerText = 'Nova Empresa';
        
        // Reset Cores
        syncColorInput('primary', '#2563eb');
        syncColorInput('sidebar', '#ffffff');
        
        document.getElementById('previewLogoContainer').classList.add('hidden');
        document.getElementById('modalTenant').classList.remove('hidden');
    }

    function editTenant(t) {
        document.getElementById('inputId').value = t.id;
        document.getElementById('inputName').value = t.name;
        document.getElementById('inputSlug').value = t.slug;
        document.getElementById('inputStatus').value = t.status;
        
        // Seta as cores (ou fallback)
        syncColorInput('primary', t.primary_color || '#2563eb');
        syncColorInput('sidebar', t.sidebar_color || '#ffffff');

        // Preview Logo
        if(t.logo_url) {
            const logoSrc = t.logo_url.startsWith('/') ? t.logo_url : '/' + t.logo_url;
            document.getElementById('previewLogoImg').src = logoSrc;
            document.getElementById('previewLogoContainer').classList.remove('hidden');
        } else {
            document.getElementById('previewLogoContainer').classList.add('hidden');
        }

        document.getElementById('modalTitle').innerText = 'Editar Empresa';
        document.getElementById('modalTenant').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('modalTenant').classList.add('hidden');
    }

    loadTenants();
</script>