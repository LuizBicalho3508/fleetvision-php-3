<div class="p-6">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Gestão de Empresas (Tenants)</h1>
            <p class="text-sm text-gray-500">Cadastre seus clientes SaaS aqui. Eles terão URL própria.</p>
        </div>
        <button onclick="openModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded shadow-md font-bold transition flex items-center gap-2">
            <i class="fas fa-plus"></i> Nova Empresa
        </button>
    </div>

    <div class="bg-white rounded shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 uppercase text-xs font-bold border-b border-gray-200">
                    <th class="px-6 py-3">ID</th>
                    <th class="px-6 py-3">Logo</th>
                    <th class="px-6 py-3">Nome da Empresa</th>
                    <th class="px-6 py-3">URL (Slug)</th>
                    <th class="px-6 py-3">Identidade Visual</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3 text-right">Ações</th>
                </tr>
            </thead>
            <tbody id="tenantGrid" class="text-sm text-gray-700 divide-y divide-gray-100">
                <tr><td colspan="7" class="p-6 text-center text-gray-400">Carregando empresas...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div id="tenantModal" class="fixed inset-0 bg-black/60 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm transition-opacity">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-lg overflow-hidden transform scale-100 transition-transform">
        
        <div class="bg-gray-100 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="font-bold text-gray-800 text-lg">Dados da Empresa</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-red-500 text-xl">&times;</button>
        </div>
        
        <form id="formTenant" class="p-6 space-y-4">
            <input type="hidden" name="id" id="tId">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Nome Fantasia</label>
                    <input type="text" name="name" id="tName" class="w-full border border-gray-300 rounded p-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Slug (URL)</label>
                    <div class="flex">
                        <span class="bg-gray-100 border border-r-0 border-gray-300 p-2 text-xs rounded-l flex items-center text-gray-500">/</span>
                        <input type="text" name="slug" id="tSlug" class="w-full border border-gray-300 rounded-r p-2 focus:border-blue-500 outline-none transition" placeholder="cliente-x" required>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Logotipo (PNG/JPG)</label>
                <input type="file" name="logo" class="w-full text-sm text-gray-500 border border-gray-300 rounded cursor-pointer bg-gray-50 focus:outline-none file:mr-4 file:py-2 file:px-4 file:rounded-l file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700">
                <p class="text-xs text-gray-400 mt-1">Recomendado: Fundo transparente, máx 2MB.</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Cor Primária</label>
                    <div class="flex items-center gap-2">
                        <input type="color" name="primary_color" id="tColor1" class="h-9 w-12 p-0 border border-gray-300 rounded cursor-pointer" value="#3b82f6">
                        <input type="text" id="tColor1Txt" class="w-full border border-gray-300 rounded p-2 text-xs bg-gray-50" readonly>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Cor Secundária (Sidebar)</label>
                    <div class="flex items-center gap-2">
                        <input type="color" name="secondary_color" id="tColor2" class="h-9 w-12 p-0 border border-gray-300 rounded cursor-pointer" value="#1e293b">
                        <input type="text" id="tColor2Txt" class="w-full border border-gray-300 rounded p-2 text-xs bg-gray-50" readonly>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Situação</label>
                <select name="status" id="tStatus" class="w-full border border-gray-300 rounded p-2 bg-white focus:border-blue-500 outline-none">
                    <option value="active">Ativo (Acesso Liberado)</option>
                    <option value="inactive">Bloqueado (Inadimplente)</option>
                </select>
            </div>

            <div class="pt-4 border-t border-gray-100 flex justify-end gap-3">
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-white border border-gray-300 text-gray-600 rounded hover:bg-gray-50 font-medium transition">Cancelar</button>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded font-bold shadow transition">Salvar Dados</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Sincronia de Cores
    const syncColor = (picker, txt) => {
        document.getElementById(picker).addEventListener('input', (e) => document.getElementById(txt).value = e.target.value);
    }
    syncColor('tColor1', 'tColor1Txt');
    syncColor('tColor2', 'tColor2Txt');

    async function loadTenants() {
        const res = await apiFetch('/api/admin/tenants');
        const tbody = document.getElementById('tenantGrid');
        
        if (!res || !res.data || res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="p-8 text-center text-gray-400">Nenhum tenant cadastrado.</td></tr>';
            return;
        }

        tbody.innerHTML = res.data.map(t => {
            const statusClass = t.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
            const statusLabel = t.status === 'active' ? 'Ativo' : 'Bloqueado';
            
            const logo = t.logo_url 
                ? `<img src="/${t.logo_url}" class="h-8 max-w-[100px] object-contain">` 
                : '<span class="text-xs text-gray-400 italic">Padrão</span>';

            const colors = `
                <div class="flex gap-2">
                    <div class="w-5 h-5 rounded shadow-sm" style="background:${t.primary_color}" title="Primária: ${t.primary_color}"></div>
                    <div class="w-5 h-5 rounded shadow-sm" style="background:${t.secondary_color}" title="Secundária: ${t.secondary_color}"></div>
                </div>`;

            // Escape simples para o JSON
            const jsonSafe = JSON.stringify(t).replace(/"/g, '&quot;');

            return `
                <tr class="hover:bg-blue-50 transition border-b border-gray-50 group">
                    <td class="px-6 py-4 font-mono text-xs text-gray-500">#${t.id}</td>
                    <td class="px-6 py-4">${logo}</td>
                    <td class="px-6 py-4 font-bold text-gray-700">${t.name}</td>
                    <td class="px-6 py-4">
                        <a href="/${t.slug}/login" target="_blank" class="text-blue-600 hover:underline font-medium text-xs bg-blue-50 px-2 py-1 rounded">
                            /${t.slug} <i class="fas fa-external-link-alt ml-1"></i>
                        </a>
                    </td>
                    <td class="px-6 py-4">${colors}</td>
                    <td class="px-6 py-4">
                        <span class="${statusClass} px-2 py-1 rounded text-xs font-bold uppercase">${statusLabel}</span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <button onclick="editTenant(${jsonSafe})" class="text-blue-500 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 p-2 rounded transition mr-1" title="Editar">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button onclick="deleteTenant(${t.id})" class="text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 p-2 rounded transition" title="Excluir">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // Modal
    function openModal() {
        document.getElementById('formTenant').reset();
        document.getElementById('tId').value = '';
        document.getElementById('tColor1').value = '#3b82f6';
        document.getElementById('tColor2').value = '#1e293b';
        document.getElementById('tColor1Txt').value = '#3b82f6';
        document.getElementById('tColor2Txt').value = '#1e293b';
        document.getElementById('tenantModal').classList.remove('hidden');
    }
    function closeModal() {
        document.getElementById('tenantModal').classList.add('hidden');
    }

    // Editar
    function editTenant(t) {
        document.getElementById('tId').value = t.id;
        document.getElementById('tName').value = t.name;
        document.getElementById('tSlug').value = t.slug;
        document.getElementById('tStatus').value = t.status;
        
        document.getElementById('tColor1').value = t.primary_color || '#3b82f6';
        document.getElementById('tColor1Txt').value = t.primary_color || '#3b82f6';
        
        document.getElementById('tColor2').value = t.secondary_color || '#1e293b';
        document.getElementById('tColor2Txt').value = t.secondary_color || '#1e293b';

        document.getElementById('tenantModal').classList.remove('hidden');
    }

    // Submit (Upload)
    document.getElementById('formTenant').onsubmit = async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const oldText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
        btn.disabled = true;

        const formData = new FormData(e.target);

        try {
            const res = await fetch('/api/admin/tenants/save', { method: 'POST', body: formData });
            const json = await res.json();
            
            if(json.success) {
                closeModal();
                loadTenants();
                showToast('Empresa salva com sucesso!');
            } else {
                showToast(json.error || 'Erro ao salvar', 'error');
            }
        } catch(err) {
            showToast('Erro de conexão.', 'error');
        } finally {
            btn.innerHTML = oldText;
            btn.disabled = false;
        }
    }

    // Deletar
    function deleteTenant(id) {
        if(!confirm('ATENÇÃO: Excluir uma empresa pode deixar usuários órfãos.\nTem certeza absoluta?')) return;
        
        apiFetch('/api/admin/tenants/delete', {
            method: 'POST',
            body: JSON.stringify({id})
        }).then(res => {
            if(res.success) {
                loadTenants();
                showToast('Empresa excluída.');
            } else {
                showToast(res.error, 'error');
            }
        });
    }

    loadTenants();
</script>