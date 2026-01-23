<div class="p-6">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Gestão de Filiais</h1>
            <p class="text-sm text-gray-500">Cadastre sedes, garagens ou unidades regionais.</p>
        </div>
        <button onclick="openModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded shadow-md font-bold transition flex items-center gap-2">
            <i class="fas fa-building"></i> Nova Filial
        </button>
    </div>

    <div class="bg-white rounded shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 uppercase text-xs font-bold border-b border-gray-200">
                    <th class="px-6 py-3">Nome da Filial</th>
                    <th class="px-6 py-3">Gerente / Responsável</th>
                    <th class="px-6 py-3">Telefone</th>
                    <th class="px-6 py-3">Endereço</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3 text-right">Ações</th>
                </tr>
            </thead>
            <tbody id="branchGrid" class="text-sm text-gray-700 divide-y divide-gray-100">
                <tr><td colspan="6" class="p-6 text-center text-gray-400">Carregando filiais...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div id="branchModal" class="fixed inset-0 bg-black/60 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-lg overflow-hidden animate-fade-in-down">
        
        <div class="bg-gray-100 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="font-bold text-gray-800 text-lg">Dados da Filial</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-red-500 text-xl">&times;</button>
        </div>
        
        <form id="formBranch" class="p-6 space-y-4">
            <input type="hidden" name="id" id="bId">
            
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Nome da Unidade</label>
                <input type="text" name="name" id="bName" class="w-full border border-gray-300 rounded p-2 focus:border-blue-500 outline-none" required placeholder="Ex: Matriz - São Paulo">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Gerente</label>
                    <input type="text" name="manager_name" id="bManager" class="w-full border border-gray-300 rounded p-2 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Telefone</label>
                    <input type="text" name="phone" id="bPhone" class="w-full border border-gray-300 rounded p-2 focus:border-blue-500 outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Endereço</label>
                <input type="text" name="address" id="bAddress" class="w-full border border-gray-300 rounded p-2 focus:border-blue-500 outline-none" placeholder="Rua, Número, Bairro...">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Status</label>
                <select name="status" id="bStatus" class="w-full border border-gray-300 rounded p-2 bg-white">
                    <option value="active">Ativa</option>
                    <option value="inactive">Inativa</option>
                </select>
            </div>

            <div class="pt-4 border-t border-gray-100 flex justify-end gap-3">
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-white border border-gray-300 text-gray-600 rounded hover:bg-gray-50 font-medium transition">Cancelar</button>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded font-bold shadow transition">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
    async function loadBranches() {
        const tbody = document.getElementById('branchGrid');
        try {
            const res = await apiFetch('/api/branches');
            
            if (!res.data || res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="p-8 text-center text-gray-400">Nenhuma filial cadastrada.</td></tr>';
                return;
            }

            tbody.innerHTML = res.data.map(b => {
                const statusHtml = b.status === 'active' 
                    ? '<span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold">Ativa</span>'
                    : '<span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs font-bold">Inativa</span>';

                // Escape JSON para evitar quebras no onclick
                const jsonSafe = JSON.stringify(b).replace(/"/g, '&quot;');

                return `
                    <tr class="hover:bg-blue-50 transition border-b border-gray-50">
                        <td class="px-6 py-4 font-bold text-gray-700">${b.name}</td>
                        <td class="px-6 py-4 text-gray-600">${b.manager_name || '-'}</td>
                        <td class="px-6 py-4 text-gray-600">${b.phone || '-'}</td>
                        <td class="px-6 py-4 text-gray-500 truncate max-w-xs" title="${b.address}">${b.address || '-'}</td>
                        <td class="px-6 py-4">${statusHtml}</td>
                        <td class="px-6 py-4 text-right">
                            <button onclick="editBranch(${jsonSafe})" class="text-blue-500 hover:text-blue-700 bg-blue-50 p-2 rounded mr-1 transition"><i class="fas fa-pen"></i></button>
                            <button onclick="deleteBranch(${b.id})" class="text-red-500 hover:text-red-700 bg-red-50 p-2 rounded transition"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                `;
            }).join('');
        } catch(e) {
            tbody.innerHTML = '<tr><td colspan="6" class="p-6 text-center text-red-400">Erro ao carregar dados.</td></tr>';
        }
    }

    // Modal Functions
    function openModal() {
        document.getElementById('formBranch').reset();
        document.getElementById('bId').value = '';
        document.getElementById('branchModal').classList.remove('hidden');
    }
    function closeModal() { document.getElementById('branchModal').classList.add('hidden'); }

    function editBranch(b) {
        document.getElementById('bId').value = b.id;
        document.getElementById('bName').value = b.name;
        document.getElementById('bManager').value = b.manager_name;
        document.getElementById('bPhone').value = b.phone;
        document.getElementById('bAddress').value = b.address;
        document.getElementById('bStatus').value = b.status;
        document.getElementById('branchModal').classList.remove('hidden');
    }

    // Submit
    document.getElementById('formBranch').onsubmit = async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(e.target));
        
        try {
            const res = await apiFetch('/api/branches/save', { method: 'POST', body: JSON.stringify(data) });
            if(res.success) {
                closeModal();
                loadBranches();
                showToast('Filial salva com sucesso!');
            } else {
                showToast(res.error, 'error');
            }
        } catch(e) { showToast('Erro de conexão', 'error'); }
    };

    // Delete
    function deleteBranch(id) {
        if(!confirm('Deseja excluir esta filial?')) return;
        
        apiFetch('/api/branches/delete', { method: 'POST', body: JSON.stringify({id}) })
            .then(res => {
                if(res.success) {
                    loadBranches();
                    showToast('Filial excluída.');
                } else {
                    showToast(res.error, 'error');
                }
            });
    }

    loadBranches();
</script>