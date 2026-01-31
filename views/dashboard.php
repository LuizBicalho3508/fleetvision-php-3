<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="p-6 space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800" id="dashTitle">Painel de Controle</h2>
            <p class="text-sm text-slate-500" id="dashSubtitle">Carregando dados...</p>
        </div>
        <div class="flex gap-2">
            <button onclick="loadKpis()" class="bg-blue-50 text-blue-600 hover:bg-blue-100 px-4 py-2 rounded-lg text-sm font-bold transition">
                <i class="fas fa-sync-alt mr-2"></i> Atualizar
            </button>
        </div>
    </div>

    <div id="kpiGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 h-32 animate-pulse"></div>
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 h-32 animate-pulse"></div>
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 h-32 animate-pulse"></div>
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 h-32 animate-pulse"></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
            <h3 class="font-bold text-slate-700 mb-4" id="chartTitle">Análise</h3>
            <div class="h-64">
                <canvas id="mainChart"></canvas>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
            <h3 class="font-bold text-slate-700 mb-4" id="widgetTitle">Ações Rápidas</h3>
            <div id="widgetContent" class="space-y-3">
                </div>
        </div>
    </div>
</div>

<script>
let mainChartInstance = null;

document.addEventListener('DOMContentLoaded', () => {
    loadKpis();
});

async function loadKpis() {
    try {
        const res = await apiFetch('/sys/dashboard/kpis');
        renderDashboard(res);
    } catch (e) {
        showToast('Erro ao carregar dashboard: ' + e.message, 'error');
    }
}

function renderDashboard(data) {
    // 1. Configura Textos Baseado no Tipo
    const titles = {
        'superadmin': ['Visão Global (God Mode)', 'Monitoramento de Infraestrutura e Tenants'],
        'tenant_admin': ['Gestão Empresarial', 'Visão geral do seu negócio de rastreamento'],
        'client': ['Gestão de Frota', 'Acompanhe sua operação em tempo real']
    };
    
    const texts = titles[data.view_type] || ['Dashboard', 'Bem-vindo'];
    document.getElementById('dashTitle').innerText = texts[0];
    document.getElementById('dashSubtitle').innerText = texts[1];

    // 2. Renderiza Cards
    const grid = document.getElementById('kpiGrid');
    grid.innerHTML = '';

    data.cards.forEach(card => {
        // Cores personalizadas para Tailwind
        const colors = {
            'blue': 'bg-blue-50 text-blue-600',
            'green': 'bg-green-50 text-green-600',
            'red': 'bg-red-50 text-red-600',
            'orange': 'bg-orange-50 text-orange-600',
            'purple': 'bg-purple-50 text-purple-600',
            'gray': 'bg-slate-100 text-slate-600'
        };
        const colorClass = colors[card.color] || colors['blue'];

        const html = `
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition group">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">${card.title}</p>
                        <h3 class="text-2xl font-bold text-slate-800">${card.value}</h3>
                    </div>
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl ${colorClass} group-hover:scale-110 transition">
                        <i class="fas ${card.icon}"></i>
                    </div>
                </div>
            </div>
        `;
        grid.insertAdjacentHTML('beforeend', html);
    });

    // 3. Renderiza Gráfico
    document.getElementById('chartTitle').innerText = data.chart_label;
    renderChart(data.chart_label, data.chart_data);

    // 4. Renderiza Widget Lateral (Ações Rápidas)
    renderWidget(data.view_type);
}

function renderWidget(type) {
    const box = document.getElementById('widgetContent');
    box.innerHTML = '';
    
    let actions = [];

    if (type === 'superadmin') {
        document.getElementById('widgetTitle').innerText = 'Administração do Servidor';
        actions = [
            { label: 'Gerenciar Tenants', link: '/sys/admin/tenants', icon: 'fa-globe', color: 'bg-slate-800 text-white' },
            { label: 'Reiniciar Serviços', link: '#', icon: 'fa-power-off', color: 'bg-red-600 text-white' },
            { label: 'Logs de Erro', link: '/sys/admin/logs', icon: 'fa-bug', color: 'bg-slate-100 text-slate-600' }
        ];
    } else if (type === 'tenant_admin') {
        document.getElementById('widgetTitle').innerText = 'Atalhos de Gestão';
        actions = [
            { label: 'Novo Cliente', link: '/clientes', icon: 'fa-user-plus', color: 'bg-blue-600 text-white' },
            { label: 'Cadastrar Veículo', link: '/frota', icon: 'fa-car', color: 'bg-green-600 text-white' },
            { label: 'Enviar Cobrança', link: '/financeiro', icon: 'fa-file-invoice-dollar', color: 'bg-slate-100 text-slate-600' }
        ];
    } else {
        document.getElementById('widgetTitle').innerText = 'Operação';
        actions = [
            { label: 'Ver no Mapa', link: '/mapa', icon: 'fa-map-marked-alt', color: 'bg-blue-600 text-white' },
            { label: 'Relatório de Rota', link: '/relatorios', icon: 'fa-route', color: 'bg-slate-100 text-slate-600' },
            { label: 'Contatar Suporte', link: '#', icon: 'fa-headset', color: 'bg-slate-100 text-slate-600' }
        ];
    }

    actions.forEach(act => {
        const html = `
            <a href="${act.link}" class="flex items-center gap-3 p-3 rounded-xl transition hover:opacity-90 ${act.color}">
                <i class="fas ${act.icon} w-6 text-center"></i>
                <span class="font-medium text-sm">${act.label}</span>
            </a>
        `;
        box.insertAdjacentHTML('beforeend', html);
    });
}

function renderChart(label, dataPoints) {
    const ctx = document.getElementById('mainChart').getContext('2d');
    
    if (mainChartInstance) mainChartInstance.destroy();

    mainChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'], // Labels fixas por enquanto
            datasets: [{
                label: label,
                data: dataPoints,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#2563eb',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [2, 4] } },
                x: { grid: { display: false } }
            }
        }
    });
}
</script>