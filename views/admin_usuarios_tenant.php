<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Gestão de Empresas (Tenants)</h1>
            <p class="text-sm text-slate-500">Gerencie os clientes SaaS e seus acessos.</p>
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
                    <th class="px-6 py-4">Empresa</th>
                    <th class="px-6 py-4">URL (Slug)</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4 text-right">Ações</th>
                </tr>
            </thead>
            <tbody id="tableBody" class="divide-y divide-slate-100">
                <tr><td colspan="5" class="p-6 text-center text-slate-400">Carregando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div id="modalTenant" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal()"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white w-full max-w-md rounded-2xl shadow-2xl p-6">
        
        <h3 class="text-lg font-bold text-slate-800 mb-4" id="modalTitle">Nova Empresa</h3>
        
        <form id="formTenant" class="space-y-4">
            <input type="hidden" name="id" id="inputId">
            
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nome da Empresa</label>
                <input type="text" name="name" id="inputName" required class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Slug (URL)</label>
                <div class="flex items-center">
                    <span class="bg-slate-100 border border-r-0 border-slate-300 rounded-l-lg p-2.5 text-slate-500 text-sm">/</span>
                    <input type="text" name="slug" id="inputSlug" required class="w-full border border-slate-300 rounded-r-lg p-2.5 focus:border-blue-500 outline-none" placeholder="empresa-x">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Logo</label>
                <input type="file" name="logo" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Status</label>
                <select name="status" id="inputStatus" class="w-full border border-slate-300 rounded-lg p-2.5 bg-white">
                    <option value="active">Ativo</option>
                    <option value="inactive">Inativo</option>
                </select>
            </div>

            <div class="pt-2 flex gap-3">
                <button type="button" onclick="closeModal()" class="flex-1 py-2.5 text-slate-500 font-bold hover:bg-slate-100 rounded-lg transition">Cancelar</button>
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-lg shadow transition">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
    async function loadTenants() {
        try {
            // Rota corrigida: /sys/admin/tenants
            const res = await apiFetch('/sys/admin/tenants'); 
            const tbody = document.getElementById('tableBody');
            
            if (!res.data || res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="p-6 text-center text-slate-400">Nenhuma empresa cadastrada.</td></tr>';
                return;
            }

            tbody.innerHTML = res.data.map(t => {
                // Tratamento seguro da imagem
                let logoSrc = '';
                if (t.logo_url) {
                    // Garante que tenha a barra inicial se não for URL completa
                    logoSrc = t.logo_url.startsWith('http') || t.logo_url.startsWith('/') 
                              ? t.logo_url 
                              : '/' + t.logo_url;
                }
                
                const logoHtml = logoSrc 
                    ? `<img src="${logoSrc}" class="h-8 w-auto object-contain max-w-[100px]" onerror="this.style.display='none'">` 
                    : `<div class="h-8 w-8 bg-slate-100 rounded flex items-center justify-center text-slate-400"><i class="fas fa-building"></i></div>`;

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
                        <span class="bg-blue-50 text-blue-600 px-2 py-1 rounded text-xs font-mono">/${t.slug}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold ${t.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">
                            <span class="w-1.5 h-1.5 rounded-full ${t.status === 'active' ? 'bg-green-500' : 'bg-red-500'}"></span>
                            ${t.status === 'active' ? 'Ativo' : 'Inativo'}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <button onclick='editTenant(${JSON.stringify(t)})' class="text-blue-500 hover:bg-blue-50 p-2 rounded transition"><i class="fas fa-edit"></i></button>
                        <button onclick="deleteTenant(${t.id})" class="text-red-400 hover:bg-red-50 p-2 rounded transition"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `}).join('');
        } catch(e) {
            console.error(e);
            showToast('Erro ao carregar empresas.', 'error');
        }
    }

    // --- FORM SUBMIT (Salvar) ---
    // --- FORM SUBMIT (Salvar - Versão Corrigida) ---
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
            
            // LER COMO TEXTO PRIMEIRO para evitar o crash "Unexpected token <"
            const rawText = await res.text();
            let json;

            try {
                json = JSON.parse(rawText);
            } catch (err) {
                console.error("ERRO BRUTO DO SERVIDOR:", rawText); // AQUI APARECERÁ O ERRO REAL NO CONSOLE
                throw new Error("O servidor retornou um erro não-JSON. Verifique o console (F12).");
            }

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
        if(!confirm('Tem certeza? Isso pode bloquear o acesso dos usuários desta empresa.')) return;
        
        // Rota corrigida: /sys/admin/tenants/delete
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

    // --- MODAL UTILS ---
    function openModal() {
        document.getElementById('formTenant').reset();
        document.getElementById('inputId').value = '';
        document.getElementById('modalTitle').innerText = 'Nova Empresa';
        document.getElementById('modalTenant').classList.remove('hidden');
    }

    function editTenant(t) {
        document.getElementById('inputId').value = t.id;
        document.getElementById('inputName').value = t.name;
        document.getElementById('inputSlug').value = t.slug;
        document.getElementById('inputStatus').value = t.status;
        document.getElementById('modalTitle').innerText = 'Editar Empresa';
        document.getElementById('modalTenant').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('modalTenant').classList.add('hidden');
    }

    loadTenants();
</script>