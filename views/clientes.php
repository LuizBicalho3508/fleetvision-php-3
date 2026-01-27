<div class="p-6 space-y-6">

    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Gestão de Clientes</h1>
            <p class="text-sm text-slate-500">Contratos, veículos e faturamento.</p>
        </div>
        <button onclick="openModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-blue-200 transition transform hover:-translate-y-0.5 flex items-center gap-2">
            <i class="fas fa-plus"></i> Novo Cliente
        </button>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase font-bold tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Cliente / Documento</th>
                        <th class="px-6 py-4 text-center">Frota Ativa</th>
                        <th class="px-6 py-4 text-center">Contrato / Venc.</th>
                        <th class="px-6 py-4 text-right">Valor Unit.</th>
                        <th class="px-6 py-4 text-right">Total Estimado</th>
                        <th class="px-6 py-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody id="tableBody" class="divide-y divide-slate-100">
                    <tr><td colspan="6" class="p-10 text-center text-slate-400"><i class="fas fa-circle-notch fa-spin text-2xl"></i></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="customerModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeModal('customerModal')"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white w-full max-w-2xl rounded-2xl shadow-2xl p-8 scale-100 transition-transform duration-300">
        
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-slate-800" id="modalTitle">Cliente</h3>
            <button onclick="closeModal('customerModal')" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:text-red-500 flex items-center justify-center transition"><i class="fas fa-times"></i></button>
        </div>
        
        <form id="formCustomer" class="space-y-4">
            <input type="hidden" name="id" id="inputId">
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nome Completo *</label>
                    <input type="text" name="name" id="inputName" required class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">CPF / CNPJ</label>
                    <input type="text" name="document" id="inputDoc" class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 outline-none">
                </div>
            </div>

            <div class="bg-emerald-50 p-4 rounded-xl border border-emerald-100">
                <h4 class="text-xs font-bold text-emerald-700 mb-3 uppercase flex items-center gap-2"><i class="fas fa-coins"></i> Configuração Financeira</h4>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Valor p/ Veículo</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-slate-400 text-xs">R$</span>
                            <input type="number" step="0.01" name="unit_price" id="inputPrice" class="w-full pl-8 border border-slate-300 rounded-lg p-2.5 focus:border-emerald-500 outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Dia Vencimento</label>
                        <input type="number" max="31" name="invoice_due_day" id="inputDueDay" placeholder="Ex: 10" class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-emerald-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Fim Contrato</label>
                        <input type="date" name="contract_due_date" id="inputContract" class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-emerald-500 outline-none">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">E-mail</label>
                    <input type="email" name="email" id="inputEmail" class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Telefone</label>
                    <input type="text" name="phone" id="inputPhone" class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 outline-none">
                </div>
            </div>
            
            <input type="text" name="address" id="inputAddress" placeholder="Endereço Completo" class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 outline-none">

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl shadow-lg transition transform active:scale-95">Salvar Cliente</button>
        </form>
    </div>
</div>

<div id="invoiceModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeModal('invoiceModal')"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden scale-100 transition-transform">
        <div class="bg-slate-50 p-4 border-b border-slate-200 flex justify-between items-center">
            <h3 class="font-bold text-slate-700"><i class="fas fa-file-invoice-dollar text-green-500 mr-2"></i> Prévia da Fatura</h3>
            <button onclick="closeModal('invoiceModal')"><i class="fas fa-times text-slate-400 hover:text-red-500"></i></button>
        </div>
        <div class="p-6">
            <p class="text-xs text-slate-400 mb-4 text-center bg-yellow-50 text-yellow-700 p-2 rounded border border-yellow-100">
                <i class="fas fa-info-circle"></i> Cálculo proporcional aos dias ativos neste mês.
            </p>
            <div id="invoiceContent" class="space-y-3 max-h-64 overflow-y-auto pr-2 custom-scrollbar"></div>
            
            <div class="mt-6 pt-4 border-t border-slate-200 flex justify-between items-center bg-slate-50 p-4 rounded-xl">
                <span class="text-sm font-bold text-slate-500 uppercase">Total a Pagar</span>
                <span class="text-2xl font-bold text-slate-800" id="invoiceTotal">R$ 0,00</span>
            </div>
        </div>
    </div>
</div>

<div id="vehiclesListModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeModal('vehiclesListModal')"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white w-full max-w-lg rounded-2xl shadow-2xl p-6 scale-100 transition-transform">
        <div class="flex justify-between items-center mb-4 border-b border-slate-100 pb-4">
            <h3 class="font-bold text-slate-700 text-lg">Veículos Vinculados</h3>
            <button onclick="closeModal('vehiclesListModal')"><i class="fas fa-times text-slate-400 hover:text-red-500 text-lg"></i></button>
        </div>
        <div id="vehiclesListContent" class="space-y-2 max-h-80 overflow-y-auto pr-2">
            </div>
    </div>
</div>

<script>
    async function loadCustomers() {
        const res = await apiFetch('/sys/customers');
        const tbody = document.getElementById('tableBody');

        if (!res.data || res.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="p-12 text-center text-slate-400 flex flex-col items-center"><i class="fas fa-users text-4xl mb-3 text-slate-200"></i><p>Nenhum cliente cadastrado.</p></td></tr>';
            return;
        }

        tbody.innerHTML = res.data.map(c => `
            <tr class="hover:bg-slate-50 transition border-b border-slate-50 group">
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm shadow-sm border border-indigo-100">
                            ${c.name.substring(0,2).toUpperCase()}
                        </div>
                        <div>
                            <div class="font-bold text-slate-800">${c.name}</div>
                            <div class="text-xs text-slate-400 font-mono">${c.document || 'S/ Doc'}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 text-center">
                    <button onclick="viewVehicles(${c.id})" class="inline-flex items-center gap-2 bg-white hover:bg-blue-50 text-slate-700 hover:text-blue-700 px-3 py-1 rounded-lg border border-slate-200 hover:border-blue-200 text-xs font-bold transition shadow-sm">
                        <i class="fas fa-car"></i> ${c.active_vehicles}
                    </button>
                </td>
                <td class="px-6 py-4 text-center text-xs">
                    <div class="text-slate-600">Dia <strong>${c.invoice_due_day || 10}</strong></div>
                    <div class="text-[10px] text-slate-400 mt-0.5">
                        ${c.contract_due_date ? 'Fim: ' + new Date(c.contract_due_date).toLocaleDateString() : 'Sem Contrato'}
                    </div>
                </td>
                <td class="px-6 py-4 text-right text-xs text-slate-600 font-mono">
                    R$ ${parseFloat(c.unit_price).toFixed(2)}
                </td>
                <td class="px-6 py-4 text-right font-bold text-slate-800 font-mono">
                    R$ ${parseFloat(c.estimated_total).toFixed(2)}
                </td>
                <td class="px-6 py-4 text-right">
                    <div class="flex justify-end gap-2 opacity-80 group-hover:opacity-100 transition-opacity">
                        <button onclick="viewInvoice(${c.id})" 
                                class="bg-white border border-slate-200 hover:border-green-400 hover:text-green-600 text-slate-500 px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm transition flex items-center gap-2" title="Visualizar Fatura">
                            <i class="fas fa-file-invoice-dollar"></i> <span class="hidden xl:inline">Fatura</span>
                        </button>

                        <button onclick='editCustomer(${JSON.stringify(c)})' 
                                class="bg-white border border-slate-200 hover:border-blue-400 hover:text-blue-600 text-slate-500 px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm transition flex items-center gap-2">
                            <i class="fas fa-pen"></i>
                        </button>
                        
                        <button onclick="deleteCustomer(${c.id})" 
                                class="bg-white border border-slate-200 hover:border-red-400 hover:text-red-600 text-slate-500 px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm transition flex items-center gap-2">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    // --- FUNÇÕES DE AÇÃO ---

    // 1. Ver Veículos
    async function viewVehicles(id) {
        const res = await apiFetch(`/sys/customers/vehicles?id=${id}`);
        const content = document.getElementById('vehiclesListContent');
        
        if (!res.data || res.data.length === 0) {
            content.innerHTML = '<div class="text-center text-slate-400 p-8 bg-slate-50 rounded-lg">Nenhum veículo vinculado.</div>';
        } else {
            content.innerHTML = res.data.map(v => `
                <div class="flex justify-between items-center p-3 bg-white hover:bg-slate-50 rounded-lg border border-slate-100 transition shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-slate-100 rounded-full flex items-center justify-center text-lg">
                            <img src="${v.icon}" class="w-5 h-5 object-contain" onerror="this.style.display='none'">
                        </div>
                        <div>
                            <div class="font-bold text-slate-700">${v.plate}</div>
                            <div class="text-xs text-slate-500">${v.brand} ${v.model}</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs font-mono text-slate-400">${v.imei || '-'}</div>
                        <div class="text-[10px] text-green-600 font-bold uppercase">${v.status}</div>
                    </div>
                </div>
            `).join('');
        }
        document.getElementById('vehiclesListModal').classList.remove('hidden');
    }

    // 2. Ver Fatura (Pro-Rata)
    async function viewInvoice(id) {
        const res = await apiFetch(`/sys/customers/invoice?id=${id}`);
        const content = document.getElementById('invoiceContent');
        
        if (!res.items) {
            content.innerHTML = '<div class="text-red-400 text-center">Erro ao calcular.</div>';
            return;
        }

        content.innerHTML = res.items.map(item => `
            <div class="flex justify-between items-center text-sm border-b border-slate-50 pb-2 mb-2 last:border-0">
                <div>
                    <div class="font-bold text-slate-700 flex items-center gap-2">
                        <i class="fas fa-car text-slate-400"></i> ${item.plate}
                    </div>
                    <div class="text-xs text-slate-400 mt-0.5">
                        ${item.desc} • Ativo desde ${new Date(item.since).toLocaleDateString()}
                    </div>
                </div>
                <div class="font-mono font-bold text-slate-700 bg-slate-100 px-2 py-1 rounded">R$ ${item.amount}</div>
            </div>
        `).join('');

        document.getElementById('invoiceTotal').innerText = `R$ ${res.total}`;
        document.getElementById('invoiceModal').classList.remove('hidden');
    }

    // 3. Salvar Cliente
    document.getElementById('formCustomer').onsubmit = async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...'; btn.disabled = true;

        const data = Object.fromEntries(new FormData(e.target));
        const isUpdate = !!data.id;
        const endpoint = isUpdate ? '/sys/customers/update' : '/sys/customers';

        try {
            const res = await apiFetch(endpoint, { method: 'POST', body: JSON.stringify(data) });
            if(res.success) {
                showToast('Salvo com sucesso!');
                closeModal('customerModal');
                loadCustomers();
            } else {
                showToast(res.error || 'Erro ao salvar', 'error');
            }
        } catch(err) {
            showToast('Erro de conexão', 'error');
        } finally {
            btn.innerHTML = original; btn.disabled = false;
        }
    };

    // 4. Excluir
    async function deleteCustomer(id) {
        if(!confirm('Atenção: A exclusão só é permitida se o cliente NÃO possuir veículos. Continuar?')) return;
        const res = await apiFetch('/sys/customers/delete', { method: 'POST', body: JSON.stringify({id}) });
        if(res.success) { showToast('Removido'); loadCustomers(); }
        else { showToast(res.error || 'Erro', 'error'); }
    }

    // Helpers Modal
    window.openModal = () => {
        document.getElementById('formCustomer').reset();
        document.getElementById('inputId').value = '';
        document.getElementById('modalTitle').innerText = 'Novo Cliente';
        document.getElementById('customerModal').classList.remove('hidden');
    }

    window.editCustomer = (c) => {
        document.getElementById('inputId').value = c.id;
        document.getElementById('inputName').value = c.name;
        document.getElementById('inputDoc').value = c.document || '';
        document.getElementById('inputEmail').value = c.email || '';
        document.getElementById('inputPhone').value = c.phone || '';
        document.getElementById('inputAddress').value = c.address || '';
        
        document.getElementById('inputPrice').value = c.unit_price || '';
        document.getElementById('inputDueDay').value = c.invoice_due_day || 10;
        document.getElementById('inputContract').value = c.contract_due_date || '';

        document.getElementById('modalTitle').innerText = 'Editar Cliente';
        document.getElementById('customerModal').classList.remove('hidden');
    }

    window.closeModal = (id) => document.getElementById(id).classList.add('hidden');

    loadCustomers();
</script>