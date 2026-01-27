<div class="p-6 space-y-6">

    <div class="flex flex-col md:flex-row justify-between items-end mb-2">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Visão Geral</h1>
            <p class="text-sm text-slate-500">Monitoramento em tempo real da operação.</p>
        </div>
        <div class="text-right">
            <span id="last-update" class="text-xs text-slate-400 font-mono">Atualizado: --:--</span>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 relative overflow-hidden group hover:shadow-md transition">
            <div class="absolute right-0 top-0 w-24 h-24 bg-blue-500 rounded-bl-full opacity-10 group-hover:scale-110 transition"></div>
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center"><i class="fas fa-truck"></i></div>
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Frota Total</span>
                </div>
                <h3 class="text-4xl font-bold text-slate-800" id="kpi-total">-</h3>
                <p class="text-xs text-slate-400 mt-1">Veículos cadastrados</p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 relative overflow-hidden group hover:shadow-md transition">
            <div class="absolute right-0 top-0 w-24 h-24 bg-green-500 rounded-bl-full opacity-10 group-hover:scale-110 transition"></div>
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-green-100 text-green-600 flex items-center justify-center"><i class="fas fa-wifi"></i></div>
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Online Agora</span>
                </div>
                <div class="flex items-end gap-2">
                    <h3 class="text-4xl font-bold text-green-600" id="kpi-online">-</h3>
                </div>
                <div class="w-full bg-slate-100 h-1.5 mt-3 rounded-full overflow-hidden">
                    <div id="bar-online" class="h-full bg-green-500 transition-all duration-1000" style="width: 0%"></div>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 relative overflow-hidden group hover:shadow-md transition">
            <div class="absolute right-0 top-0 w-24 h-24 bg-orange-500 rounded-bl-full opacity-10 group-hover:scale-110 transition"></div>
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center"><i class="fas fa-tachometer-alt"></i></div>
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Em Movimento</span>
                </div>
                <h3 class="text-4xl font-bold text-orange-600" id="kpi-moving">-</h3>
                <p class="text-xs text-slate-400 mt-1">Veículos rodando</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 flex flex-col">
        <div class="p-5 border-b border-slate-100 flex justify-between items-center">
            <h3 class="text-sm font-bold text-slate-700">Frota em Tempo Real</h3>
            <a href="frota" class="text-xs text-blue-600 font-bold hover:underline">Ver todos</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase font-bold">
                    <tr>
                        <th class="px-6 py-3">Veículo</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Velocidade</th>
                        <th class="px-6 py-3 text-right">Última Conexão</th>
                    </tr>
                </thead>
                <tbody id="fleet-list" class="divide-y divide-slate-100">
                    <tr><td colspan="4" class="p-6 text-center text-slate-400"><i class="fas fa-circle-notch fa-spin mr-2"></i> Carregando dados...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
    async function loadDashboard() {
        try {
            // CORREÇÃO: Usando rotas /sys/ definidas no index.php
            const kpis = await apiFetch('/sys/dashboard/kpis');
            
            if (kpis && !kpis.error) {
                // Atualiza KPIs
                const total = parseInt(kpis.total_vehicles) || 0;
                const online = parseInt(kpis.online) || 0;
                const moving = parseInt(kpis.moving) || 0;

                // Animação simples dos números (pode ser melhorada)
                document.getElementById('kpi-total').innerText = total;
                document.getElementById('kpi-online').innerText = online;
                document.getElementById('kpi-moving').innerText = moving;
                
                // Barra de progresso
                const pct = total > 0 ? Math.round((online / total) * 100) : 0;
                document.getElementById('bar-online').style.width = `${pct}%`;
            }

            // Carrega Lista da Frota
            const fleet = await apiFetch('/sys/dashboard/data'); // Rota corrigida
            const tbody = document.getElementById('fleet-list');
            
            if (fleet && Array.isArray(fleet) && fleet.length > 0) {
                // Limita a 5 itens no dashboard para não poluir
                const previewFleet = fleet.slice(0, 5);
                
                tbody.innerHTML = previewFleet.map(v => `
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-6 py-4 font-medium text-slate-700">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded bg-slate-100 flex items-center justify-center text-slate-500">
                                    <i class="fas fa-car"></i>
                                </div>
                                <div>
                                    ${v.plate || 'S/ Placa'} 
                                    <span class="text-xs text-slate-400 block font-normal">${v.name || 'Sem Nome'}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold ${v.speed > 0 ? 'bg-green-100 text-green-700' : (v.online ? 'bg-blue-50 text-blue-600' : 'bg-slate-100 text-slate-600')}">
                                <span class="w-1.5 h-1.5 rounded-full ${v.speed > 0 ? 'bg-green-500' : (v.online ? 'bg-blue-500' : 'bg-slate-400')}"></span>
                                ${v.speed > 0 ? 'Em Movimento' : (v.online ? 'Parado (Online)' : 'Offline')}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-slate-600 font-mono">${Math.round(v.speed || 0)} km/h</td>
                        <td class="px-6 py-4 text-right text-xs text-slate-500 font-mono">
                            ${v.lastupdate ? new Date(v.lastupdate).toLocaleString('pt-BR') : '-'}
                        </td>
                    </tr>
                `).join('');
                document.getElementById('last-update').innerText = 'Atualizado: ' + new Date().toLocaleTimeString('pt-BR');
            } else {
                tbody.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-slate-400">Nenhum veículo reportando dados no momento.</td></tr>';
            }
        } catch (e) { 
            console.error("Erro Dashboard:", e);
            document.getElementById('fleet-list').innerHTML = `<tr><td colspan="4" class="p-4 text-center text-red-400">Erro ao carregar dados. <br><small>${e.message}</small></td></tr>`;
        }
    }

    // Inicia e agenda atualização
    loadDashboard();
    
    // Armazena intervalo para limpar se trocar de página (se for SPA)
    if (window.dashboardInterval) clearInterval(window.dashboardInterval);
    window.dashboardInterval = setInterval(loadDashboard, 15000); // 15 segundos
</script>