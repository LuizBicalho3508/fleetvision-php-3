<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="p-6 space-y-6">
    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Financeiro Asaas</h2>
            <p class="text-sm text-slate-500">Gestão integrada e inteligente.</p>
        </div>
        <div class="flex bg-slate-100 p-1 rounded-xl">
            <button onclick="switchTab('dashboard')" id="tab-dashboard" class="px-4 py-2 rounded-lg text-sm font-bold transition shadow-sm bg-white text-blue-600">
                Dashboard
            </button>
            <button onclick="switchTab('extract')" id="tab-extract" class="px-4 py-2 rounded-lg text-sm font-bold transition text-slate-500 hover:text-slate-700">
                Extrato
            </button>
            <button onclick="switchTab('customers')" id="tab-customers" class="px-4 py-2 rounded-lg text-sm font-bold transition text-slate-500 hover:text-slate-700">
                Clientes
            </button>
        </div>
        <button onclick="openConfigModal()" class="w-10 h-10 flex items-center justify-center rounded-lg bg-slate-800 text-white hover:bg-slate-900 transition shadow-lg">
            <i class="fas fa-cog"></i>
        </button>
    </div>

    <div id="setupAlert" class="hidden bg-orange-50 border border-orange-200 rounded-xl p-8 text-center animate-pulse">
        <i class="fas fa-plug text-4xl text-orange-400 mb-4"></i>
        <h3 class="text-xl font-bold text-slate-800">Conecte sua conta Asaas</h3>
        <p class="text-slate-600 mb-6 mt-2">Para visualizar gráficos e gerenciar cobranças, insira sua chave de API.</p>
        <button onclick="openConfigModal()" class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-2 rounded-lg font-bold shadow-md transition transform hover:scale-105">
            Configurar Agora
        </button>
    </div>

    <div id="view-dashboard" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-2xl p-6 text-white shadow-xl relative overflow-hidden">
                <div class="absolute -right-6 -top-6 opacity-20 w-32 h-32 bg-white rounded-full blur-2xl"></div>
                <p class="text-blue-100 text-xs font-bold uppercase tracking-wider mb-1">Saldo Atual</p>
                <h3 class="text-3xl font-bold mb-4" id="walletBalance">R$ ---</h3>
                <div class="flex gap-2 text-xs">
                    <span class="bg-white/20 px-2 py-1 rounded backdrop-blur-md"><i class="fas fa-check-circle mr-1"></i> Verificado</span>
                </div>
            </div>

            <div onclick="openInvoicesModal('today', 'Previsto Hoje')" class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm hover:shadow-md transition cursor-pointer group">
                <div class="flex justify-between">
                    <div>
                        <p class="text-slate-400 text-xs font-bold uppercase">A Receber (Hoje)</p>
                        <h3 class="text-2xl font-bold text-slate-800 mt-2" id="kpiToday">--</h3>
                    </div>
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-xl group-hover:scale-110 transition">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                </div>
            </div>

            <div onclick="openInvoicesModal('overdue', 'Inadimplência')" class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm hover:shadow-md transition cursor-pointer group">
                <div class="flex justify-between">
                    <div>
                        <p class="text-slate-400 text-xs font-bold uppercase">Vencidos</p>
                        <h3 class="text-2xl font-bold text-red-500 mt-2" id="kpiOverdue">--</h3>
                    </div>
                    <div class="w-12 h-12 bg-red-50 text-red-500 rounded-xl flex items-center justify-center text-xl group-hover:scale-110 transition">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <h4 class="font-bold text-slate-700 mb-4">Status de Cobranças (Amostra)</h4>
                <div class="h-64">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <h4 class="font-bold text-slate-700 mb-4">Volume Financeiro</h4>
                <div class="h-64 flex items-center justify-center text-slate-400 text-sm bg-slate-50 rounded-xl border border-dashed border-slate-200">
                    <p>Disponível em breve com histórico.</p>
                </div>
            </div>
        </div>
    </div>

    <div id="view-extract" class="hidden space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                <h3 class="font-bold text-slate-700 text-sm uppercase">Últimas 10 Movimentações</h3>
                <button onclick="loadDashboard()" class="text-blue-600 hover:text-blue-800 text-xs font-bold"><i class="fas fa-sync-alt mr-1"></i> Atualizar</button>
            </div>
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-[10px]">
                    <tr>
                        <th class="px-6 py-3">Data</th>
                        <th class="px-6 py-3">Cliente</th>
                        <th class="px-6 py-3">Forma Pagto</th>
                        <th class="px-6 py-3">Valor</th>
                        <th class="px-6 py-3 text-right">Status</th>
                    </tr>
                </thead>
                <tbody id="extractTableBody" class="divide-y divide-slate-100"></tbody>
            </table>
        </div>
    </div>

    <div id="view-customers" class="hidden space-y-6">
        <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex gap-2">
            <input type="text" id="customerSearchInput" placeholder="Nome, CPF ou CNPJ..." class="flex-1 px-4 py-2 border border-slate-300 rounded-lg outline-none focus:border-blue-500">
            <button onclick="searchCustomers()" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-blue-700 transition">Buscar</button>
        </div>
        <div id="customerResults" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="col-span-full text-center py-12 text-slate-400">
                <i class="fas fa-search text-3xl mb-3 opacity-30"></i>
                <p>Pesquise clientes na base do Asaas.</p>
            </div>
        </div>
    </div>
</div>

<div id="invoicesModal" class="fixed inset-0 bg-black/60 hidden z-50 flex items-center justify-center backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[85vh] flex flex-col overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <div>
                <h3 class="text-lg font-bold text-slate-800" id="invoicesModalTitle">Detalhes</h3>
                <p class="text-xs text-slate-500" id="invoicesModalSubtitle">Lista de boletos</p>
            </div>
            <button onclick="document.getElementById('invoicesModal').classList.add('hidden')" class="text-slate-400 hover:text-red-500 text-xl"><i class="fas fa-times"></i></button>
        </div>
        <div class="flex-1 overflow-y-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 sticky top-0 z-10 text-[10px] font-bold uppercase text-slate-500">
                    <tr>
                        <th class="px-6 py-3">Vencimento</th>
                        <th class="px-6 py-3">Cliente</th>
                        <th class="px-6 py-3">Descrição</th>
                        <th class="px-6 py-3">Valor</th>
                        <th class="px-6 py-3 text-right">Status</th>
                        <th class="px-6 py-3 text-right">Ação</th>
                    </tr>
                </thead>
                <tbody id="invoicesModalBody" class="divide-y divide-slate-100"></tbody>
            </table>
        </div>
    </div>
</div>

<div id="configModal" class="fixed inset-0 bg-black/60 hidden z-[60] flex items-center justify-center backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
        <h3 class="text-lg font-bold text-slate-800 mb-4">API Asaas</h3>
        <form id="configForm" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Chave de API</label>
                <input type="text" name="api_key" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs font-mono outline-none focus:border-blue-500" placeholder="$aact_...">
            </div>
            <button type="submit" class="w-full bg-slate-800 text-white py-2 rounded-lg font-bold hover:bg-slate-900 transition">Salvar</button>
        </form>
        <button onclick="document.getElementById('configModal').classList.add('hidden')" class="w-full mt-3 text-xs text-slate-400 hover:text-slate-600">Cancelar</button>
    </div>
</div>

<script>
let charts = {};

function switchTab(tab) {
    ['dashboard', 'extract', 'customers'].forEach(t => {
        document.getElementById(`view-${t}`).classList.add('hidden');
        document.getElementById(`tab-${t}`).className = "px-4 py-2 rounded-lg text-sm font-bold transition text-slate-500 hover:text-slate-700 hover:bg-slate-50";
    });
    
    document.getElementById(`view-${tab}`).classList.remove('hidden');
    document.getElementById(`tab-${tab}`).className = "px-4 py-2 rounded-lg text-sm font-bold transition shadow-sm bg-white text-blue-600";
}

document.addEventListener('DOMContentLoaded', loadDashboard);

async function loadDashboard() {
    try {
        const res = await apiFetch('/sys/financial/data');
        if(!res.configured) {
            document.getElementById('setupAlert').classList.remove('hidden');
            document.getElementById('view-dashboard').classList.add('opacity-30', 'pointer-events-none');
            return;
        }

        // Preenche KPI
        const fmt = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
        document.getElementById('walletBalance').innerText = fmt.format(res.balance || 0);
        document.getElementById('kpiOverdue').innerText = res.totals.overdue || 0;
        document.getElementById('kpiToday').innerText = res.totals.today || 0;

        // Renderiza Tabela Extrato
        renderTable(res.payments, 'extractTableBody', false);

        // Renderiza Gráfico
        renderChart(res.chartData);

    } catch(e) { console.error(e); }
}

function renderChart(data) {
    const ctx = document.getElementById('statusChart').getContext('2d');
    if(charts.status) charts.status.destroy();

    charts.status = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Recebidos', 'Vencidos', 'Pendentes'],
            datasets: [{
                data: [data.received, data.overdue, data.pending],
                backgroundColor: ['#22c55e', '#ef4444', '#eab308'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'right' } }
        }
    });
}

function renderTable(data, elId, showLink) {
    const tbody = document.getElementById(elId);
    tbody.innerHTML = '';
    
    if(!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-10 text-center text-slate-400">Sem registros.</td></tr>';
        return;
    }

    const fmt = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
    data.forEach(p => {
        const date = new Date(p.dueDate || p.dateCreated).toLocaleDateString('pt-BR');
        // AQUI ESTÁ A CORREÇÃO DO NOME:
        const clientName = p.customerName || p.customer || 'Cliente';
        
        let statusColor = 'bg-slate-100 text-slate-600';
        if(p.status === 'OVERDUE') statusColor = 'bg-red-50 text-red-600';
        if(p.status === 'RECEIVED') statusColor = 'bg-green-50 text-green-600';
        if(p.status === 'PENDING') statusColor = 'bg-yellow-50 text-yellow-600';

        const link = showLink && p.invoiceUrl 
            ? `<a href="${p.invoiceUrl}" target="_blank" class="text-blue-600 hover:underline"><i class="fas fa-external-link-alt"></i></a>` 
            : '-';

        const html = `
            <tr class="hover:bg-slate-50 border-b border-slate-50 last:border-0">
                <td class="px-6 py-3 text-xs font-mono text-slate-500">${date}</td>
                <td class="px-6 py-3 font-medium text-slate-700">${clientName}</td>
                <td class="px-6 py-3 text-xs text-slate-500">${p.description || 'Cobrança'}</td>
                <td class="px-6 py-3 font-bold text-slate-800">${fmt.format(p.value)}</td>
                <td class="px-6 py-3 text-right"><span class="px-2 py-1 rounded text-[10px] font-bold uppercase ${statusColor}">${p.status}</span></td>
                ${showLink ? `<td class="px-6 py-3 text-right">${link}</td>` : ''}
            </tr>
        `;
        tbody.insertAdjacentHTML('beforeend', html);
    });
}

// Busca Clientes
async function searchCustomers() {
    const q = document.getElementById('customerSearchInput').value;
    const box = document.getElementById('customerResults');
    box.innerHTML = '<div class="col-span-full text-center py-10"><i class="fas fa-spinner fa-spin text-blue-500"></i></div>';
    
    try {
        const res = await apiFetch(`/sys/financial/customers?q=${q}`);
        box.innerHTML = '';
        if(!res.data || res.data.length === 0) {
            box.innerHTML = '<div class="col-span-full text-center text-slate-400">Nada encontrado.</div>';
            return;
        }
        res.data.forEach(c => {
            box.innerHTML += `
                <div onclick="openInvoicesModal('customer', '${c.name}', '${c.id}')" class="bg-white p-4 rounded-xl border border-slate-200 hover:border-blue-400 cursor-pointer transition shadow-sm">
                    <h4 class="font-bold text-slate-700">${c.name}</h4>
                    <p class="text-xs text-slate-400 mt-1">${c.cpfCnpj || 'Doc não inf.'}</p>
                    <p class="text-xs text-blue-500 mt-3 font-medium">Ver Financeiro <i class="fas fa-arrow-right"></i></p>
                </div>
            `;
        });
    } catch(e) { console.error(e); }
}

// Modal Drill Down
async function openInvoicesModal(type, title, param) {
    document.getElementById('invoicesModal').classList.remove('hidden');
    document.getElementById('invoicesModalTitle').innerText = title;
    document.getElementById('invoicesModalBody').innerHTML = '<tr><td colspan="6" class="text-center py-10"><i class="fas fa-spinner fa-spin"></i></td></tr>';

    let url = `/sys/financial/cards?type=${type}`;
    if(type === 'customer') url = `/sys/financial/customer_invoices?id=${param}`;

    try {
        const res = await apiFetch(url);
        const list = (type === 'customer') ? res.invoices : res.data;
        renderTable(list, 'invoicesModalBody', true);
    } catch(e) { console.error(e); }
}

// Config
document.getElementById('configForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    try {
        await apiFetch('/sys/financial/config', { method: 'POST', body: JSON.stringify({ api_key: e.target.api_key.value }) });
        showToast('Salvo!');
        location.reload();
    } catch(e) { showToast(e.message, 'error'); }
});
</script>