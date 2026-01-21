<?php
if (!isset($_SESSION['user_id'])) exit;
?>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>

<div class="h-full flex flex-col bg-slate-50 relative overflow-hidden font-inter">
    
    <div class="px-8 py-6 bg-white border-b border-slate-200 flex justify-between items-center shadow-sm z-20 shrink-0">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-3">
                <div class="p-2 bg-indigo-100 text-indigo-600 rounded-lg"><i class="fas fa-wallet"></i></div>
                Gestão Financeira
            </h1>
            <p class="text-sm text-slate-500 mt-1 ml-11">Controle de faturamento, clientes e integrações.</p>
        </div>
        
        <div class="flex items-center gap-4">
            <div id="api-status" class="hidden px-4 py-2 rounded-full bg-red-50 text-red-600 text-xs font-bold border border-red-100 flex items-center gap-2 animate-pulse shadow-sm">
                <div class="w-2 h-2 rounded-full bg-red-500"></div> API Desconectada
            </div>
            
            <div id="balance-card" class="hidden group relative bg-slate-900 text-white pl-6 pr-8 py-3 rounded-2xl shadow-xl shadow-slate-200 border border-slate-700 flex flex-col items-end min-w-[200px] cursor-default transition-all hover:translate-y-[-2px]">
                <div class="absolute -left-6 -bottom-6 w-20 h-20 bg-white/5 rounded-full blur-2xl group-hover:bg-white/10 transition"></div>
                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-1 z-10">Saldo Disponível</span>
                <div class="flex items-center gap-3 z-10">
                    <span class="text-2xl font-bold text-emerald-400 tracking-tight" id="balance-value">R$ ...</span>
                    <button onclick="loadBalance()" class="text-slate-500 hover:text-white transition"><i class="fas fa-sync-alt text-xs"></i></button>
                </div>
            </div>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto custom-scroll p-8">
        <div class="max-w-[1600px] mx-auto space-y-8">

            <div class="bg-white p-1.5 rounded-xl border border-slate-200 inline-flex shadow-sm sticky top-0 z-30">
                <button onclick="switchTab('charges')" class="tab-btn px-6 py-2.5 rounded-lg text-sm font-bold transition-all duration-200 flex items-center gap-2 bg-indigo-50 text-indigo-700 shadow-sm" id="tab-charges">
                    <i class="fas fa-chart-pie"></i> Visão Geral
                </button>
                <button onclick="switchTab('customers')" class="tab-btn px-6 py-2.5 rounded-lg text-sm font-medium text-slate-500 hover:text-slate-700 hover:bg-slate-50 transition-all duration-200 flex items-center gap-2" id="tab-customers">
                    <i class="fas fa-users"></i> Clientes
                </button>
                <button onclick="switchTab('config')" class="tab-btn px-6 py-2.5 rounded-lg text-sm font-medium text-slate-500 hover:text-slate-700 hover:bg-slate-50 transition-all duration-200 flex items-center gap-2" id="tab-config">
                    <i class="fas fa-cogs"></i> Configurações
                </button>
            </div>

            <div id="view-charges" class="tab-content space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                    <div onclick="filterChargesStatus('RECEIVED')" class="cursor-pointer bg-white p-6 rounded-2xl border border-slate-100 shadow-sm hover:shadow-lg hover:border-emerald-200 transition group relative overflow-hidden">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-2">Recebidas</p>
                                <h3 class="text-3xl font-bold text-slate-800" id="kpi-received-count">-</h3>
                            </div>
                            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl group-hover:bg-emerald-600 group-hover:text-white transition shadow-sm"><i class="fas fa-check-circle text-xl"></i></div>
                        </div>
                        <div class="mt-4 h-1 w-full bg-slate-100 rounded-full overflow-hidden"><div class="h-full bg-emerald-500 w-3/4 rounded-full"></div></div>
                    </div>

                    <div onclick="filterChargesStatus('PENDING')" class="cursor-pointer bg-white p-6 rounded-2xl border border-slate-100 shadow-sm hover:shadow-lg hover:border-amber-200 transition group relative overflow-hidden">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-2">Pendentes</p>
                                <h3 class="text-3xl font-bold text-slate-800" id="kpi-pending-count">-</h3>
                            </div>
                            <div class="p-3 bg-amber-50 text-amber-500 rounded-xl group-hover:bg-amber-500 group-hover:text-white transition shadow-sm"><i class="fas fa-clock text-xl"></i></div>
                        </div>
                        <div class="mt-4 h-1 w-full bg-slate-100 rounded-full overflow-hidden"><div class="h-full bg-amber-500 w-1/2 rounded-full"></div></div>
                    </div>

                    <div onclick="filterChargesStatus('OVERDUE')" class="cursor-pointer bg-white p-6 rounded-2xl border border-slate-100 shadow-sm hover:shadow-lg hover:border-red-200 transition group relative overflow-hidden">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-2">Vencidas</p>
                                <h3 class="text-3xl font-bold text-slate-800" id="kpi-overdue-count">-</h3>
                            </div>
                            <div class="p-3 bg-red-50 text-red-500 rounded-xl group-hover:bg-red-500 group-hover:text-white transition shadow-sm"><i class="fas fa-exclamation-triangle text-xl"></i></div>
                        </div>
                        <div class="mt-4 h-1 w-full bg-slate-100 rounded-full overflow-hidden"><div class="h-full bg-red-500 w-1/4 rounded-full"></div></div>
                    </div>

                    <div onclick="filterChargesStatus('')" class="cursor-pointer bg-gradient-to-br from-indigo-600 to-indigo-800 text-white p-6 rounded-2xl shadow-lg shadow-indigo-200 relative overflow-hidden flex flex-col justify-between hover:scale-[1.02] transition">
                        <div class="absolute right-0 top-0 p-6 opacity-10"><i class="fas fa-wallet text-6xl"></i></div>
                        <div>
                            <p class="text-indigo-200 text-xs font-bold uppercase tracking-wider mb-1">Total na Tela</p>
                            <h3 class="text-3xl font-bold" id="kpi-total-val">R$ 0,00</h3>
                        </div>
                        <div class="mt-4 text-[10px] font-medium bg-white/10 w-fit px-2 py-1 rounded border border-white/10">Clique p/ Limpar Filtros</div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 flex flex-col">
                    <div class="p-5 border-b border-slate-100 flex flex-col lg:flex-row justify-between items-center gap-4 bg-slate-50/30">
                        <div class="flex items-center gap-3 w-full lg:w-auto">
                            <div id="active-filter-badge" class="hidden px-3 py-1 bg-indigo-100 text-indigo-700 rounded-lg text-xs font-bold uppercase items-center gap-2">
                                <span id="active-filter-name">FILTRO</span>
                                <button onclick="filterChargesStatus('')" class="hover:text-red-500"><i class="fas fa-times"></i></button>
                            </div>

                            <div class="relative group w-full md:w-64">
                                <i class="fas fa-search absolute left-4 top-3.5 text-slate-400"></i>
                                <input type="text" id="charge-search" placeholder="Buscar cliente..." 
                                       class="w-full pl-11 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white focus:border-indigo-500 outline-none text-sm transition shadow-sm"
                                       oninput="debouncedSearchCharges()">
                            </div>
                        </div>
                        
                        <button onclick="openModalCharge()" class="w-full lg:w-auto bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-indigo-100 transition flex items-center justify-center gap-2">
                            <i class="fas fa-plus"></i> Nova Cobrança
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-slate-50 text-[11px] uppercase text-slate-500 font-bold border-b border-slate-100">
                                <tr>
                                    <th class="p-5 pl-6 w-1/3">Cliente / Descrição</th>
                                    <th class="p-5">Valor</th>
                                    <th class="p-5">Vencimento</th>
                                    <th class="p-5 text-center">Meio</th>
                                    <th class="p-5 text-center">Status</th>
                                    <th class="p-5 pr-6 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="list-charges" class="text-sm divide-y divide-slate-50 text-slate-600"></tbody>
                        </table>
                    </div>

                    <div class="p-4 border-t border-slate-100 bg-slate-50/50 flex justify-between items-center rounded-b-2xl">
                        <span class="text-xs text-slate-500 font-medium bg-white px-3 py-1.5 rounded border border-slate-200" id="page-info">Página 1</span>
                        <div class="flex gap-2">
                            <button onclick="prevPage()" id="btn-prev" class="px-4 py-2 rounded-lg border border-slate-200 bg-white text-xs font-bold hover:bg-slate-50 disabled:opacity-50">Anterior</button>
                            <button onclick="nextPage()" id="btn-next" class="px-4 py-2 rounded-lg border border-slate-200 bg-white text-xs font-bold hover:bg-slate-50 disabled:opacity-50">Próximo</button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="view-customers" class="tab-content hidden space-y-6">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 flex flex-col min-h-[500px]">
                    <div class="p-5 border-b border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4 bg-slate-50/30">
                        <div class="relative w-full md:w-96 group">
                            <i class="fas fa-search absolute left-4 top-3.5 text-slate-400"></i>
                            <input type="text" id="customer-search" placeholder="Nome, CPF ou CNPJ..." 
                                   class="w-full pl-11 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white focus:border-blue-500 outline-none text-sm transition shadow-sm"
                                   oninput="debouncedSearchCustomers()">
                        </div>
                        <button onclick="openModalCustomer()" class="w-full md:w-auto bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl font-bold text-sm shadow-md transition flex items-center justify-center gap-2">
                            <i class="fas fa-user-plus"></i> Novo Cliente
                        </button>
                    </div>
                    
                    <div class="flex-1 overflow-x-auto custom-scroll">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-slate-50 text-[11px] uppercase text-slate-500 font-bold border-b border-slate-100">
                                <tr>
                                    <th class="p-5 pl-6">Nome</th>
                                    <th class="p-5">Documento</th>
                                    <th class="p-5">Contato</th> <th class="p-5 pr-6 text-right">Ações</th> </tr>
                            </thead>
                            <tbody id="list-customers" class="text-sm divide-y divide-slate-50 text-slate-600"></tbody>
                        </table>
                    </div>
                    <div class="p-4 border-t border-slate-100 bg-slate-50/50 flex justify-between items-center rounded-b-2xl">
                        <span class="text-xs text-slate-500 font-medium bg-white px-3 py-1 rounded border border-slate-200" id="cust-page-info">Página 1</span>
                        <div class="flex gap-2">
                            <button onclick="custPrevPage()" id="btn-cust-prev" class="px-3 py-1.5 rounded border border-slate-200 bg-white text-xs font-bold disabled:opacity-50">Ant.</button>
                            <button onclick="custNextPage()" id="btn-cust-next" class="px-3 py-1.5 rounded border border-slate-200 bg-white text-xs font-bold disabled:opacity-50">Prox.</button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="view-config" class="tab-content hidden animate-in zoom-in-95 duration-300">
                <div class="bg-white max-w-3xl mx-auto rounded-2xl shadow-lg border border-slate-200 p-10 text-center">
                    <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-6"><i class="fas fa-key text-3xl text-slate-400"></i></div>
                    <h3 class="text-2xl font-bold text-slate-800 mb-2">Integração Asaas</h3>
                    <p class="text-slate-500 mb-8 max-w-md mx-auto">Insira sua chave de API.</p>
                    <div class="mb-8 text-left max-w-lg mx-auto">
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-2 ml-1">Chave de API</label>
                        <div class="relative group">
                            <input type="password" id="config-apikey" class="w-full pl-12 pr-4 py-4 rounded-xl border border-slate-300 focus:border-indigo-500 outline-none font-mono text-sm bg-slate-50 transition">
                            <div class="absolute left-0 top-0 h-full w-12 flex items-center justify-center text-slate-400"><i class="fas fa-lock"></i></div>
                        </div>
                    </div>
                    <button onclick="saveConfig()" class="bg-slate-900 hover:bg-black text-white px-10 py-3.5 rounded-xl font-bold transition shadow-xl mx-auto block"><i class="fas fa-save"></i> Salvar</button>
                </div>
            </div>

        </div>
    </div>
</div>

<div id="modal-customer-history" class="fixed inset-0 bg-slate-900/60 hidden z-[110] flex items-center justify-center p-4 backdrop-blur-sm transition-opacity opacity-0">
    <div class="bg-white w-full max-w-4xl rounded-2xl shadow-2xl overflow-hidden transform scale-95 transition-all duration-300 flex flex-col max-h-[90vh]" id="modal-customer-history-content">
        <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50 shrink-0">
            <div>
                <h3 class="font-bold text-lg text-slate-800">Histórico Financeiro</h3>
                <p class="text-xs text-slate-500" id="hist-customer-name">Carregando...</p>
            </div>
            <button onclick="closeModal('modal-customer-history')" class="w-8 h-8 rounded-full hover:bg-red-50 text-slate-400 hover:text-red-500 transition flex items-center justify-center"><i class="fas fa-times"></i></button>
        </div>
        <div class="flex-1 overflow-auto custom-scroll p-0" id="modal-history-table-container">
            </div>
    </div>
</div>

<div id="modal-charge" class="fixed inset-0 bg-slate-900/60 hidden z-[100] flex items-center justify-center p-4 backdrop-blur-md transition-opacity opacity-0">
    <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden transform scale-95 transition-all duration-300" id="modal-charge-content">
        <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-lg text-slate-800">Nova Cobrança</h3>
            <button onclick="closeModal('modal-charge')" class="text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-6 space-y-5">
            <div class="relative">
                <label class="block text-xs font-bold text-slate-600 mb-1">CLIENTE</label>
                <input type="text" id="charge-customer-search" placeholder="Buscar..." class="w-full pl-10 p-3 border border-slate-200 rounded-xl bg-slate-50 outline-none text-sm">
                <i class="fas fa-user absolute left-3.5 top-8 text-slate-400"></i>
                <input type="hidden" id="charge-customer-id">
                <div id="customer-dropdown-list" class="absolute w-full bg-white border border-slate-200 rounded-xl mt-1 shadow-2xl max-h-48 overflow-y-auto hidden z-50"></div>
            </div>
            <div class="grid grid-cols-2 gap-5">
                <div><label class="block text-xs font-bold text-slate-600 mb-1">VALOR</label><input type="number" id="charge-value" class="w-full p-3 border border-slate-200 rounded-xl bg-slate-50 outline-none text-sm"></div>
                <div><label class="block text-xs font-bold text-slate-600 mb-1">VENCIMENTO</label><input type="date" id="charge-duedate" class="w-full p-3 border border-slate-200 rounded-xl bg-slate-50 outline-none text-sm"></div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">FORMA PAGAMENTO</label>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="payType" value="BOLETO" checked> Boleto</label>
                    <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="payType" value="PIX"> Pix</label>
                </div>
            </div>
            <div><label class="block text-xs font-bold text-slate-600 mb-1">DESCRIÇÃO</label><input type="text" id="charge-desc" class="w-full p-3 border border-slate-200 rounded-xl bg-slate-50 outline-none text-sm"></div>
            <button onclick="createCharge()" class="w-full bg-indigo-600 text-white font-bold py-3.5 rounded-xl hover:bg-indigo-700">Emitir</button>
        </div>
    </div>
</div>

<div id="modal-customer" class="fixed inset-0 bg-slate-900/60 hidden z-[100] flex items-center justify-center p-4 backdrop-blur-md transition-opacity opacity-0">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden transform scale-95 transition-all duration-300" id="modal-customer-content">
        <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-lg text-slate-800">Novo Cliente</h3>
            <button onclick="closeModal('modal-customer')" class="text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-6 space-y-5">
            <div><label class="block text-xs font-bold text-slate-600 mb-1">NOME</label><input type="text" id="cust-name" class="w-full p-3 border border-slate-200 rounded-xl bg-slate-50 outline-none text-sm"></div>
            <div><label class="block text-xs font-bold text-slate-600 mb-1">DOC</label><input type="text" id="cust-cpf" class="w-full p-3 border border-slate-200 rounded-xl bg-slate-50 outline-none text-sm"></div>
            <div><label class="block text-xs font-bold text-slate-600 mb-1">EMAIL</label><input type="email" id="cust-email" class="w-full p-3 border border-slate-200 rounded-xl bg-slate-50 outline-none text-sm"></div>
            <div><label class="block text-xs font-bold text-slate-600 mb-1">CELULAR</label><input type="text" id="cust-phone" class="w-full p-3 border border-slate-200 rounded-xl bg-slate-50 outline-none text-sm mask-phone"></div>
            <button onclick="createCustomer()" class="w-full bg-blue-600 text-white font-bold py-3.5 rounded-xl hover:bg-blue-700">Salvar</button>
        </div>
    </div>
</div>

<script>
    const API_URL = '/api_financeiro.php';
    const LIMIT = 10;
    
    let chargeOffset = 0; let chargeFilter = ''; let chargeStatusFilter = '';
    let custOffset = 0; let custFilter = '';
    const customerCache = {};

    function debounce(func, wait) {
        let timeout;
        return function(...args) { clearTimeout(timeout); timeout = setTimeout(() => func.apply(this, args), wait); };
    }

    const debouncedSearchCharges = debounce(() => { chargeFilter = document.getElementById('charge-search').value; chargeOffset = 0; loadCharges(); }, 600);
    const debouncedSearchCustomers = debounce(() => { custFilter = document.getElementById('customer-search').value; custOffset = 0; loadCustomers(); }, 600);

    $(document).ready(function(){ $('.mask-phone').mask('(00) 00000-0000'); });

    document.addEventListener('DOMContentLoaded', async () => {
        const hasConfig = await checkConfig();
        if(hasConfig) { 
            loadKPIs(); 
            loadCharges(); 
        }
        setupAutocomplete();
    });

    // --- LÓGICA DE FILTRO POR STATUS (CARDS) ---
    function filterChargesStatus(status) {
        chargeStatusFilter = status;
        chargeOffset = 0;
        
        // UI Feedback
        const badge = document.getElementById('active-filter-badge');
        const badgeName = document.getElementById('active-filter-name');
        
        if(status) {
            let label = status === 'RECEIVED' ? 'RECEBIDAS' : (status === 'PENDING' ? 'PENDENTES' : 'VENCIDAS');
            badgeName.innerText = label;
            badge.classList.remove('hidden');
            badge.classList.add('flex');
        } else {
            badge.classList.add('hidden');
            badge.classList.remove('flex');
        }
        
        loadCharges();
    }

    // --- CARREGAMENTO DE COBRANÇAS ---
    async function loadCharges() {
        const list = document.getElementById('list-charges');
        list.innerHTML = '<tr><td colspan="6" class="p-5 text-center text-gray-400">Carregando...</td></tr>';
        
        let endpoint = `/payments?limit=${LIMIT}&offset=${chargeOffset}`;
        if(chargeFilter) endpoint += `&description=${encodeURIComponent(chargeFilter)}`;
        if(chargeStatusFilter) endpoint += `&status=${chargeStatusFilter}`;

        try {
            const res = await fetch(`${API_URL}?action=asaas_proxy&asaas_endpoint=${endpoint}`);
            const data = await res.json();
            
            if(!data.data || data.data.length === 0) { 
                list.innerHTML = '<tr><td colspan="6" class="p-10 text-center text-slate-400 italic">Nenhuma cobrança encontrada.</td></tr>'; 
                document.getElementById('kpi-total-val').innerText = "R$ 0,00";
                return; 
            }

            let pageTotal = 0;
            const rows = data.data.map(c => {
                pageTotal += c.value;
                return renderChargeRow(c);
            }).join('');
            
            list.innerHTML = rows;
            document.getElementById('kpi-total-val').innerText = `R$ ${pageTotal.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
            document.getElementById('page-info').innerText = `Página ${Math.floor(chargeOffset/LIMIT) + 1}`;
            document.getElementById('btn-prev').disabled = (chargeOffset === 0);
            document.getElementById('btn-next').disabled = (!data.hasMore);
        } catch(e) { list.innerHTML = `<tr><td colspan="6" class="p-5 text-red-500 text-center">Erro ao carregar dados.</td></tr>`; }
    }

    // --- CARREGAMENTO DE CLIENTES (CORRIGIDO E MELHORADO) ---
    async function loadCustomers() {
        const list = document.getElementById('list-customers');
        list.innerHTML = '<tr><td colspan="4" class="p-5 text-center text-gray-400">Carregando clientes...</td></tr>';
        
        let endpoint = `/customers?limit=${LIMIT}&offset=${custOffset}`;
        if(custFilter) endpoint += `&name=${encodeURIComponent(custFilter)}`; 

        try {
            const res = await fetch(`${API_URL}?action=asaas_proxy&asaas_endpoint=${endpoint}`);
            const data = await res.json();
            
            if(!data.data || data.data.length === 0) { 
                list.innerHTML = '<tr><td colspan="4" class="p-10 text-center text-slate-400 italic">Nenhum cliente encontrado.</td></tr>'; 
                return; 
            }

            list.innerHTML = data.data.map(c => {
                customerCache[c.id] = c.name;
                return `
                <tr class="hover:bg-slate-50 border-b border-slate-50 transition">
                    <td class="p-5 pl-6 font-bold text-slate-700">${c.name}</td>
                    <td class="p-5 text-slate-500 font-mono text-xs">${c.cpfCnpj || '-'}</td>
                    <td class="p-5 text-slate-500 text-sm">
                        ${c.mobilePhone ? `<i class="fas fa-phone-alt text-slate-300 mr-1"></i> ${c.mobilePhone}` : '<span class="text-slate-300">-</span>'}
                        <div class="text-xs text-slate-400">${c.email || ''}</div>
                    </td>
                    <td class="p-5 pr-6 text-right">
                        <button onclick="viewCustomerHistory('${c.id}', '${c.name}')" class="w-8 h-8 inline-flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-400 hover:text-indigo-600 hover:border-indigo-200 hover:shadow-md transition" title="Ver Histórico Financeiro">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>`;
            }).join('');
            
            document.getElementById('cust-page-info').innerText = `Página ${Math.floor(custOffset/LIMIT) + 1}`;
            document.getElementById('btn-cust-prev').disabled = (custOffset === 0);
            document.getElementById('btn-cust-next').disabled = (!data.hasMore);
        } catch(e) { list.innerHTML = `<tr><td colspan="4" class="p-5 text-red-500 text-center">Erro ao carregar clientes.</td></tr>`; }
    }

    // --- MODAL DE HISTÓRICO DO CLIENTE ---
    async function viewCustomerHistory(customerId, customerName) {
        const modal = document.getElementById('modal-customer-history');
        document.getElementById('hist-customer-name').innerText = customerName;
        const container = document.getElementById('modal-history-table-container');
        
        container.innerHTML = '<div class="p-10 text-center text-slate-400"><i class="fas fa-circle-notch fa-spin text-2xl"></i><br>Buscando histórico...</div>';
        
        modal.classList.remove('hidden');
        setTimeout(() => { modal.classList.remove('opacity-0'); document.getElementById('modal-customer-history-content').classList.add('scale-100'); }, 10);

        try {
            // Busca cobranças apenas deste cliente
            const res = await fetch(`${API_URL}?action=asaas_proxy&asaas_endpoint=/payments&customer=${customerId}&limit=50`);
            const data = await res.json();
            
            if(!data.data || data.data.length === 0) { 
                container.innerHTML = '<div class="p-10 text-center text-slate-500 flex flex-col items-center"><i class="fas fa-folder-open text-4xl mb-2 text-slate-200"></i>Nenhum registro encontrado para este cliente.</div>'; 
                return; 
            }
            
            container.innerHTML = `
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase sticky top-0 z-10 shadow-sm">
                        <tr>
                            <th class="p-4 pl-6">Descrição</th>
                            <th class="p-4">Valor</th>
                            <th class="p-4">Vencimento</th>
                            <th class="p-4 text-center">Status</th>
                            <th class="p-4 pr-6 text-right">Ação</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-50">
                        ${data.data.map(c => {
                            let st = { cls: 'bg-slate-100 text-slate-600', label: c.status };
                            if(['RECEIVED', 'CONFIRMED'].includes(c.status)) st = { cls: 'bg-emerald-100 text-emerald-700', label: 'Pago' };
                            else if(c.status === 'OVERDUE') st = { cls: 'bg-red-100 text-red-700', label: 'Vencido' };
                            else if(c.status === 'PENDING') st = { cls: 'bg-amber-100 text-amber-700', label: 'Aberto' };
                            
                            return `
                            <tr class="hover:bg-slate-50 transition">
                                <td class="p-4 pl-6 text-slate-700 font-medium">${c.description || 'Cobrança'}</td>
                                <td class="p-4 font-mono font-bold text-slate-600">R$ ${c.value.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</td>
                                <td class="p-4 text-slate-500">${new Date(c.dueDate).toLocaleDateString('pt-BR')}</td>
                                <td class="p-4 text-center"><span class="${st.cls} px-2 py-0.5 rounded text-[10px] font-bold uppercase">${st.label}</span></td>
                                <td class="p-4 pr-6 text-right">
                                    <a href="${c.invoiceUrl}" target="_blank" class="text-indigo-600 hover:text-indigo-800 font-bold text-xs flex items-center justify-end gap-1"><i class="fas fa-external-link-alt"></i> Ver</a>
                                </td>
                            </tr>`;
                        }).join('')}
                    </tbody>
                </table>`;
        } catch(e) { container.innerHTML = '<div class="p-10 text-red-500 text-center">Erro ao carregar histórico.</div>'; }
    }

    function renderChargeRow(c) {
        let st = { cls: 'bg-slate-100 text-slate-600', label: c.status };
        if(['RECEIVED', 'CONFIRMED'].includes(c.status)) st = { cls: 'bg-emerald-100 text-emerald-700', label: 'Recebido' };
        else if(c.status === 'OVERDUE') st = { cls: 'bg-red-100 text-red-700', label: 'Vencido' };
        else if(c.status === 'PENDING') st = { cls: 'bg-amber-100 text-amber-700', label: 'Pendente' };

        if(!customerCache[c.customer]) {
            fetch(`${API_URL}?action=asaas_proxy&asaas_endpoint=/customers/${c.customer}`)
                .then(r=>r.json()).then(d=>{ if(d.name) { customerCache[c.customer]=d.name; document.getElementById(`cust-${c.id}`).innerText=d.name; } });
        }

        return `
        <tr class="hover:bg-indigo-50/30 border-b border-slate-50 transition">
            <td class="p-5 pl-6">
                <div class="font-bold text-slate-700 text-sm" id="cust-${c.id}">${customerCache[c.customer] || 'Carregando...'}</div>
                <div class="text-xs text-slate-400 mt-0.5">${c.description || 'Cobrança Avulsa'}</div>
            </td>
            <td class="p-5 font-mono text-sm text-slate-700 font-bold">R$ ${c.value.toLocaleString('pt-BR', {minimumFractionDigits: 2})}</td>
            <td class="p-5 text-sm text-slate-500 font-medium">${new Date(c.dueDate).toLocaleDateString('pt-BR')}</td>
            <td class="p-5 text-center"><span class="${st.cls} px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase">${st.label}</span></td>
            <td class="p-5 pr-6 text-right">
                <a href="${c.invoiceUrl}" target="_blank" class="text-indigo-600 hover:text-indigo-800 font-bold text-xs flex items-center justify-end gap-1"><i class="fas fa-barcode"></i> BOLETO</a>
            </td>
        </tr>`;
    }

    function switchTab(tab) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.tab-btn').forEach(el => { el.classList.remove('bg-indigo-50', 'text-indigo-700', 'shadow-sm'); el.classList.add('text-slate-500', 'hover:bg-slate-50'); });
        document.getElementById(`view-${tab}`).classList.remove('hidden');
        document.getElementById(`tab-${tab}`).classList.add('bg-indigo-50', 'text-indigo-700', 'shadow-sm');
        document.getElementById(`tab-${tab}`).classList.remove('text-slate-500', 'hover:bg-slate-50');
        if(tab === 'customers') loadCustomers();
    }

    async function checkConfig() {
        try {
            const res = await fetch(`${API_URL}?action=asaas_get_config`);
            if(!res.ok) throw new Error('API Off');
            const data = await res.json();
            if (!data.has_token) { switchTab('config'); document.getElementById('api-status').classList.remove('hidden'); return false; }
            loadBalance(); return true;
        } catch (e) { switchTab('config'); document.getElementById('api-status').classList.remove('hidden'); return false; }
    }

    async function saveConfig() {
        const key = document.getElementById('config-apikey').value;
        if(!key) return alert('Chave vazia');
        try {
            await fetch(`${API_URL}?action=asaas_save_config`, { method: 'POST', body: JSON.stringify({ apiKey: key }) });
            alert('Salvo!'); location.reload();
        } catch(e) { alert('Erro.'); }
    }

    async function createCustomer() {
        const body = { name: document.getElementById('cust-name').value, cpfCnpj: document.getElementById('cust-cpf').value, email: document.getElementById('cust-email').value, mobilePhone: document.getElementById('cust-phone').value };
        if(!body.name || !body.cpfCnpj) return alert('Preencha os dados.');
        try {
            await fetch(`${API_URL}?action=asaas_proxy&asaas_endpoint=/customers`, { method: 'POST', body: JSON.stringify(body) });
            alert('Cliente salvo!'); closeModal('modal-customer'); loadCustomers();
        } catch(e) { alert('Erro.'); }
    }

    async function createCharge() {
        const body = { customer: document.getElementById('charge-customer-id').value, billingType: document.querySelector('input[name="payType"]:checked').value, value: parseFloat(document.getElementById('charge-value').value), dueDate: document.getElementById('charge-duedate').value, description: document.getElementById('charge-desc').value };
        if(!body.customer || !body.value) return alert('Preencha os dados.');
        try {
            await fetch(`${API_URL}?action=asaas_proxy&asaas_endpoint=/payments`, { method: 'POST', body: JSON.stringify(body) });
            alert('Cobrança gerada!'); closeModal('modal-charge'); loadCharges(); loadKPIs();
        } catch(e) { alert('Erro.'); }
    }

    async function loadBalance() {
        try {
            const res = await fetch(`${API_URL}?action=asaas_proxy&asaas_endpoint=/finance/balance`);
            const data = await res.json();
            if(data.balance !== undefined) { document.getElementById('balance-value').innerText = `R$ ${data.balance.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`; document.getElementById('balance-card').classList.remove('hidden'); }
        } catch(e){}
    }

    async function loadKPIs() {
        const getKpi = async (s) => (await (await fetch(`${API_URL}?action=asaas_proxy&asaas_endpoint=/payments&status=${s}&limit=1`)).json()).totalCount || 0;
        document.getElementById('kpi-received-count').innerText = await getKpi('RECEIVED');
        document.getElementById('kpi-pending-count').innerText = await getKpi('PENDING');
        document.getElementById('kpi-overdue-count').innerText = await getKpi('OVERDUE');
    }

    function setupAutocomplete() {
        const input = document.getElementById('charge-customer-search');
        const list = document.getElementById('customer-dropdown-list');
        let timeout = null;
        input.addEventListener('input', () => {
            clearTimeout(timeout);
            const val = input.value.trim();
            if(val.length < 2) { list.classList.add('hidden'); return; }
            list.classList.remove('hidden'); list.innerHTML = '<div class="p-2 text-xs text-gray-400">Buscando...</div>';
            timeout = setTimeout(async () => {
                const res = await fetch(`${API_URL}?action=asaas_proxy&asaas_endpoint=/customers&name=${encodeURIComponent(val)}&limit=5`);
                const data = await res.json();
                list.innerHTML = (data.data || []).map(c => `<div onclick="selectCustomer('${c.id}', '${c.name}')" class="p-2 hover:bg-gray-100 cursor-pointer text-sm">${c.name} <small class="text-gray-400">${c.cpfCnpj}</small></div>`).join('') || '<div class="p-2 text-xs">Nada encontrado</div>';
            }, 400);
        });
        document.addEventListener('click', (e) => { if(!input.contains(e.target)) list.classList.add('hidden'); });
    }

    function selectCustomer(id, name) { document.getElementById('charge-customer-id').value = id; document.getElementById('charge-customer-search').value = name; document.getElementById('customer-dropdown-list').classList.add('hidden'); }
    function nextPage() { chargeOffset += LIMIT; loadCharges(); }
    function prevPage() { if(chargeOffset >= LIMIT) { chargeOffset -= LIMIT; loadCharges(); } }
    function custNextPage() { custOffset += LIMIT; loadCustomers(); }
    function custPrevPage() { if(custOffset >= LIMIT) { custOffset -= LIMIT; loadCustomers(); } }
    function openModalCharge() { openModal('modal-charge'); }
    function openModalCustomer() { openModal('modal-customer'); }
    function openModal(id) { document.getElementById(id).classList.remove('hidden'); setTimeout(() => { document.getElementById(id).classList.remove('opacity-0'); document.getElementById(id+'-content').classList.add('scale-100'); }, 10); }
    function closeModal(id) { document.getElementById(id+'-content').classList.remove('scale-100'); document.getElementById(id).classList.add('opacity-0'); setTimeout(() => document.getElementById(id).classList.add('hidden'), 300); }
</script>