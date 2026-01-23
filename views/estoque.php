<div class="p-6 space-y-6">

    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Gestão de Ativos</h1>
            <p class="text-sm text-slate-500">Controle de estoque e manutenções da frota.</p>
        </div>
        
        <div class="bg-slate-200 p-1 rounded-xl flex">
            <button onclick="switchTab('stock')" id="btn-stock" class="px-4 py-2 rounded-lg text-sm font-bold transition bg-white text-slate-800 shadow-sm">Estoque</button>
            <button onclick="switchTab('maint')" id="btn-maint" class="px-4 py-2 rounded-lg text-sm font-bold transition text-slate-500 hover:text-slate-700">Manutenções</button>
        </div>
    </div>

    <div id="tab-stock" class="space-y-4 animate-in fade-in slide-in-from-bottom-2 duration-300">
        <div class="flex justify-end">
            <button onclick="openItemModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-sm flex items-center gap-2">
                <i class="fas fa-box-open"></i> Novo Item
            </button>
        </div>
        
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase font-bold">
                    <tr>
                        <th class="px-6 py-3">Item</th>
                        <th class="px-6 py-3">Categoria</th>
                        <th class="px-6 py-3">Quantidade</th>
                        <th class="px-6 py-3">Custo Unit.</th>
                        <th class="px-6 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody id="stockGrid" class="divide-y divide-slate-100"></tbody>
            </table>
        </div>
    </div>

    <div id="tab-maint" class="hidden space-y-4 animate-in fade-in slide-in-from-bottom-2 duration-300">
        <div class="flex justify-end">
            <button onclick="openMaintModal()" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-sm flex items-center gap-2">
                <i class="fas fa-tools"></i> Registrar Manutenção
            </button>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase font-bold">
                    <tr>
                        <th class="px-6 py-3">Data</th>
                        <th class="px-6 py-3">Veículo</th>
                        <th class="px-6 py-3">Serviço</th>
                        <th class="px-6 py-3">Valor</th>
                        <th class="px-6 py-3">Odômetro</th>
                    </tr>
                </thead>
                <tbody id="maintGrid" class="divide-y divide-slate-100"></tbody>
            </table>
        </div>
    </div>
</div>

<div id="modalItem" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden z-[100] flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <h3 class="font-bold text-lg mb-4">Item de Estoque</h3>
        <form id="formItem" class="space-y-3">
            <input type="hidden" name="id" id="itemId">
            <input type="text" name="item_name" id="itemName" placeholder="Nome do Item" class="w-full border p-2 rounded-lg" required>
            <select name="category" id="itemCat" class="w-full border p-2 rounded-lg bg-white">
                <option value="tracker">Rastreador</option>
                <option value="simcard">Chip M2M</option>
                <option value="part">Peça/Pneu</option>
                <option value="other">Outro</option>
            </select>
            <div class="grid grid-cols-2 gap-3">
                <input type="number" name="quantity" id="itemQty" placeholder="Qtd" class="w-full border p-2 rounded-lg" required>
                <input type="number" name="min_quantity" id="itemMin" placeholder="Mínimo" class="w-full border p-2 rounded-lg" value="5">
            </div>
            <input type="number" step="0.01" name="unit_cost" id="itemCost" placeholder="Custo Unitário (R$)" class="w-full border p-2 rounded-lg">
            
            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="closeModals()" class="px-4 py-2 text-slate-500">Cancelar</button>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-bold">Salvar</button>
            </div>
        </form>
    </div>
</div>

<div id="modalMaint" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden z-[100] flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <h3 class="font-bold text-lg mb-4">Nova Manutenção</h3>
        <form id="formMaint" class="space-y-3">
            <select name="vehicle_id" id="maintVehicle" class="w-full border p-2 rounded-lg bg-white" required>
                <option value="">Carregando veículos...</option>
            </select>
            <input type="text" name="service_type" placeholder="Tipo (ex: Troca de Óleo)" class="w-full border p-2 rounded-lg" required>
            <textarea name="description" placeholder="Detalhes do serviço..." class="w-full border p-2 rounded-lg"></textarea>
            <div class="grid grid-cols-2 gap-3">
                <input type="date" name="service_date" class="w-full border p-2 rounded-lg" required>
                <input type="number" step="0.01" name="cost" placeholder="Valor Total (R$)" class="w-full border p-2 rounded-lg" required>
            </div>
            <input type="number" name="odometer" placeholder="KM Atual" class="w-full border p-2 rounded-lg">
            
            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="closeModals()" class="px-4 py-2 text-slate-500">Cancelar</button>
                <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded-lg font-bold">Registrar</button>
            </div>
        </form>
    </div>
</div>

<script>
    // --- Tabs Logic ---
    function switchTab(tab) {
        document.getElementById('tab-stock').classList.add('hidden');
        document.getElementById('tab-maint').classList.add('hidden');
        document.getElementById('btn-stock').className = 'px-4 py-2 rounded-lg text-sm font-bold transition text-slate-500 hover:text-slate-700';
        document.getElementById('btn-maint').className = 'px-4 py-2 rounded-lg text-sm font-bold transition text-slate-500 hover:text-slate-700';
        
        document.getElementById('tab-' + tab).classList.remove('hidden');
        document.getElementById('btn-' + tab).className = 'px-4 py-2 rounded-lg text-sm font-bold transition bg-white text-slate-800 shadow-sm';
        
        if(tab === 'stock') loadStock();
        else loadMaint();
    }

    // --- Estoque ---
    async function loadStock() {
        const res = await apiFetch('/api/stock');
        const grid = document.getElementById('stockGrid');
        if(!res || !res.data) return;
        
        grid.innerHTML = res.data.map(i => {
            const lowStock = i.quantity <= i.min_quantity ? '<span class="text-xs bg-red-100 text-red-600 px-2 rounded font-bold ml-2">Baixo</span>' : '';
            return `
            <tr class="hover:bg-slate-50">
                <td class="px-6 py-3 font-bold text-slate-700">${i.item_name}</td>
                <td class="px-6 py-3 capitalize text-slate-500">${i.category}</td>
                <td class="px-6 py-3 font-mono">${i.quantity} ${lowStock}</td>
                <td class="px-6 py-3 text-slate-600">R$ ${i.unit_cost}</td>
                <td class="px-6 py-3 text-right">
                    <button onclick="deleteItem(${i.id})" class="text-slate-300 hover:text-red-500"><i class="fas fa-trash"></i></button>
                </td>
            </tr>`;
        }).join('');
    }

    document.getElementById('formItem').onsubmit = async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(e.target));
        const res = await apiFetch('/api/stock/save', { method: 'POST', body: JSON.stringify(data) });
        if(res.success) { closeModals(); loadStock(); showToast('Item salvo!'); }
    };

    function deleteItem(id) {
        if(confirm('Excluir item?')) {
            apiFetch('/api/stock/delete', { method: 'POST', body: JSON.stringify({id}) }).then(() => loadStock());
        }
    }

    // --- Manutenção ---
    async function loadMaint() {
        const res = await apiFetch('/api/maintenance');
        const grid = document.getElementById('maintGrid');
        
        grid.innerHTML = res.data.map(m => `
            <tr class="hover:bg-slate-50">
                <td class="px-6 py-3 text-xs text-slate-500 font-mono">${m.formatted_date}</td>
                <td class="px-6 py-3 font-bold text-slate-700">${m.plate}</td>
                <td class="px-6 py-3">${m.service_type}</td>
                <td class="px-6 py-3 font-bold text-orange-600">${m.formatted_cost}</td>
                <td class="px-6 py-3 text-xs font-mono">${m.odometer} km</td>
            </tr>
        `).join('');
    }

    document.getElementById('formMaint').onsubmit = async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(e.target));
        const res = await apiFetch('/api/maintenance/save', { method: 'POST', body: JSON.stringify(data) });
        if(res.success) { closeModals(); loadMaint(); showToast('Manutenção registrada!'); }
    };

    // --- Helpers ---
    async function openMaintModal() {
        // Carrega veículos para o select
        const res = await apiFetch('/api/dashboard/data');
        const sel = document.getElementById('maintVehicle');
        sel.innerHTML = '<option value="">Selecione...</option>';
        res.forEach(v => sel.innerHTML += `<option value="${v.id}">${v.plate} - ${v.name}</option>`);
        
        document.getElementById('formMaint').reset();
        document.getElementById('modalMaint').classList.remove('hidden');
    }

    function openItemModal() {
        document.getElementById('formItem').reset();
        document.getElementById('itemId').value = '';
        document.getElementById('modalItem').classList.remove('hidden');
    }
    
    function closeModals() {
        document.getElementById('modalItem').classList.add('hidden');
        document.getElementById('modalMaint').classList.add('hidden');
    }

    loadStock(); // Inicia na aba estoque
</script>