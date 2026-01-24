<div class="p-6 bg-gray-50 min-h-screen">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-stethoscope text-blue-600"></i> Diagnóstico do Sistema</h1>
        <button onclick="runTests()" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 transition">
            <i class="fas fa-play"></i> Rodar Testes
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
            <h2 class="font-bold text-lg mb-4 border-b pb-2">Status do Ambiente</h2>
            <div id="envStatus" class="space-y-3 text-sm">
                <p class="text-gray-500">Aguardando execução...</p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
            <h2 class="font-bold text-lg mb-4 border-b pb-2">Teste de Rotas (API)</h2>
            <ul id="routeList" class="space-y-2">
                </ul>
        </div>
    </div>

    <div class="mt-6 bg-slate-900 text-slate-300 p-4 rounded-lg font-mono text-xs overflow-x-auto h-64 shadow-inner">
        <div class="font-bold text-white mb-2 uppercase">Log de Respostas:</div>
        <div id="consoleLog"></div>
    </div>
</div>

<script>
    const endpoints = [
        { name: 'Status Sistema', url: '/sys/debug/status', method: 'GET' },
        { name: 'Dashboard KPIs', url: '/sys/dashboard/kpis', method: 'GET' },
        { name: 'Dashboard Dados', url: '/sys/dashboard/data', method: 'GET' },
        { name: 'Tenants (Admin)', url: '/sys/admin/tenants', method: 'GET' },
        { name: 'Motoristas', url: '/sys/drivers', method: 'GET' },
        { name: 'Server Stats', url: '/sys/admin/server/stats', method: 'GET' }
    ];

    async function runTests() {
        const envDiv = document.getElementById('envStatus');
        const listDiv = document.getElementById('routeList');
        const logDiv = document.getElementById('consoleLog');
        
        envDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
        listDiv.innerHTML = '';
        logDiv.innerHTML = '';

        // 1. Testa Status Base
        try {
            const res = await fetch('/api/debug/status');
            const data = await res.json();
            
            logDiv.innerHTML += `> GET /api/debug/status: ${JSON.stringify(data)}\n\n`;

            if(res.ok) {
                const dbIcon = data.database.connected ? '<i class="fas fa-check text-green-500"></i>' : '<i class="fas fa-times text-red-500"></i>';
                envDiv.innerHTML = `
                    <div class="flex justify-between"><span>PHP Versão:</span> <strong>${data.php_version}</strong></div>
                    <div class="flex justify-between"><span>Database:</span> <span>${dbIcon} ${data.database.connected ? 'Conectado' : 'Erro'}</span></div>
                    <div class="flex justify-between"><span>Empresas no Banco:</span> <strong>${data.database.tenants_count}</strong></div>
                    <div class="flex justify-between"><span>Sessão Tenant:</span> <strong>${data.session.tenant_slug}</strong></div>
                    <div class="flex justify-between"><span>Usuário ID:</span> <strong>${data.session.user_id}</strong></div>
                `;
            } else {
                envDiv.innerHTML = `<span class="text-red-600 font-bold">Erro ao conectar na API de Debug (HTTP ${res.status})</span>`;
            }
        } catch(e) {
            envDiv.innerHTML = `<span class="text-red-600 font-bold">Falha crítica: ${e.message}</span>`;
        }

        // 2. Testa Rotas
        for (const ep of endpoints) {
            const li = document.createElement('li');
            li.className = 'flex justify-between items-center p-2 bg-gray-50 rounded border border-gray-100';
            li.innerHTML = `<span>${ep.name} <code class="text-xs text-blue-500 ml-2">${ep.url}</code></span> <i class="fas fa-spinner fa-spin text-gray-400"></i>`;
            listDiv.appendChild(li);

            try {
                const start = performance.now();
                const res = await fetch(ep.url, { method: ep.method });
                const duration = Math.round(performance.now() - start);
                
                let icon = '';
                let txtColor = '';

                // Tenta ler o JSON ou Texto
                const text = await res.text();
                logDiv.innerHTML += `> ${ep.method} ${ep.url} (${res.status}): ${text.substring(0, 150)}...\n`;

                if (res.ok) {
                    icon = '<i class="fas fa-check-circle text-green-500"></i>';
                    txtColor = 'text-green-700';
                } else if (res.status === 404) {
                    icon = '<i class="fas fa-question-circle text-orange-500" title="Rota não encontrada"></i>';
                    txtColor = 'text-orange-600';
                } else if (res.status === 401) {
                    icon = '<i class="fas fa-lock text-yellow-500" title="Não autorizado"></i>';
                    txtColor = 'text-yellow-600';
                } else {
                    icon = '<i class="fas fa-exclamation-triangle text-red-500"></i>';
                    txtColor = 'text-red-600';
                }

                li.innerHTML = `
                    <span class="${txtColor} font-medium">${ep.name}</span>
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-gray-400 font-mono">${res.status}</span>
                        <span class="text-xs text-gray-400 font-mono">${duration}ms</span>
                        ${icon}
                    </div>
                `;

            } catch (err) {
                li.innerHTML = `
                    <span class="text-red-600">${ep.name}</span>
                    <span class="text-xs text-red-500">Erro de Rede</span>
                `;
                logDiv.innerHTML += `> ERRO ${ep.url}: ${err.message}\n`;
            }
        }
    }
</script>