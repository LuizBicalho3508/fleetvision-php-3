<div class="p-6 space-y-6">
    
    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Gestão de Clientes</h1>
            <p class="text-sm text-slate-500">Administre contratos e acessos dos clientes.</p>
        </div>
        <button onclick="openModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-sm transition flex items-center gap-2">
            <i class="fas fa-plus"></i> Novo Cliente
        </button>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase font-bold">
                    <tr>
                        <th class="px-6 py-4">Nome / Documento</th>
                        <th class="px-6 py-4">Contato</th>
                        <th class="px-6 py-4">Financeiro</th>
                        <th class="px-6 py-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody id="customersTable" class="divide-y divide-slate-100 text-slate-600">
                    <tr><td colspan="4" class="p-8 text-center text-slate-400">Carregando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="modalClient" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden z-[100] flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all scale-100">
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex justify-between items-center">
            <h3 class="font-bold text-slate-800 text-lg">Dados do Cliente</h3>
            <button onclick="closeModal()" class="text-slate-400 hover:text-red-500 transition"><i class="fas fa-times text-xl"></i></button>
        </div>
        <form id="formClient" class="p-6 space-y-4">
            <input type="hidden" name="id" id="clientId">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nome Completo</label>
                <input type="text" name="name" id="clientName" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition" required>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Documento</label>
                    <input type="text" name="document" id="clientDoc" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-blue-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Telefone</label>
                    <input type="text" name="phone" id="clientPhone" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-blue-500 outline-none transition">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Endereço</label>
                <input type="text" name="address" id="clientAddress" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-blue-500 outline-none transition">
            </div>
            
            <div class="pt-4 flex justify-end gap-3">
                <button type="button" onclick="closeModal()" class="px-5 py-2.5 rounded-xl text-slate-500 hover:bg-slate-100 font-bold text-sm transition">Cancelar</button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-blue-200 transition">Salvar Cliente</button>
            </div>
        </form>
    </div>
</div>

<script>
    const table = document.getElementById('customersTable');
    const modal = document.getElementById('modalClient');
    const form = document.getElementById('formClient');

    async function loadCustomers() {
        const res = await apiFetch('/api/customers');
        if (!res || !res.data) return;
        
        table.innerHTML = res.data.map(c => `
            <tr class="hover:bg-slate-50 transition group">
                <td class="px-6 py-4">
                    <div class="font-bold text-slate-700">${c.name}</div>
                    <div class="text-xs text-slate-400 font-mono mt-0.5">${c.document || '---'}</div>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm">${c.email || ''}</div>
                    <div class="text-xs text-slate-400 mt-0.5">${c.phone || ''}</div>
                </td>
                <td class="px-6 py-4">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold ${c.financial_status === 'overdue' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'}">
                        ${c.financial_status === 'overdue' ? 'Pendente' : 'Regular'}
                    </span>
                </td>
                <td class="px-6 py-4 text-right">
                    <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition">
                        <button onclick='editClient(${JSON.stringify(c)})' class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition"><i class="fas fa-pen"></i></button>
                        <button onclick="deleteClient(${c.id})" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-red-600 hover:bg-red-50 transition"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    form.onsubmit = async (e) => {
        e.preventDefault();
        const body = JSON.stringify(Object.fromEntries(new FormData(form)));
        const res = await apiFetch('/api/customers/save', { method: 'POST', body });
        if(res.success) { showToast('Salvo com sucesso!'); closeModal(); loadCustomers(); }
        else showToast(res.error || 'Erro ao salvar', 'error');
    };

    function editClient(c) {
        document.getElementById('clientId').value = c.id;
        document.getElementById('clientName').value = c.name;
        document.getElementById('clientDoc').value = c.document;
        document.getElementById('clientPhone').value = c.phone;
        document.getElementById('clientAddress').value = c.address;
        modal.classList.remove('hidden');
    }

    function deleteClient(id) {
        if(!confirm('Deseja realmente excluir este cliente?')) return;
        apiFetch('/api/customers/delete', { method: 'POST', body: JSON.stringify({id}) })
            .then(res => { if(res.success) loadCustomers(); else showToast(res.error, 'error'); });
    }

    function openModal() { form.reset(); document.getElementById('clientId').value = ''; modal.classList.remove('hidden'); }
    function closeModal() { modal.classList.add('hidden'); }

    loadCustomers();
</script>