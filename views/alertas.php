<div class="p-6 space-y-6 h-full flex flex-col">
    
    <div class="flex justify-between items-center shrink-0">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Central de Alertas</h1>
            <p class="text-sm text-slate-500">Gerencie suas notificações e visualize o registro de eventos.</p>
        </div>
    </div>

    <div class="border-b border-slate-200 shrink-0">
        <nav class="-mb-px flex gap-8">
            <button onclick="switchTab('config')" id="tab-config-btn" class="group inline-flex items-center py-4 px-1 border-b-2 border-blue-600 font-bold text-sm text-blue-600 transition-all">
                <i class="fas fa-cog mr-2 group-hover:rotate-90 transition-transform"></i> Configuração
            </button>
            <button onclick="switchTab('history')" id="tab-history-btn" class="group inline-flex items-center py-4 px-1 border-b-2 border-transparent font-bold text-sm text-slate-500 hover:text-slate-700 hover:border-slate-300 transition-all">
                <i class="fas fa-history mr-2 text-slate-400 group-hover:text-slate-600"></i> Histórico
            </button>
        </nav>
    </div>

    <div class="flex-1 overflow-y-auto custom-scrollbar p-1">
        
        <div id="tab-config" class="max-w-4xl mx-auto py-4 animate-fade-in">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="font-bold text-lg text-slate-800">Preferências de Alerta</h3>
                    <p class="text-sm text-slate-500 mt-1">Escolha quais eventos devem gerar popup na tela e aviso sonoro.</p>
                </div>

                <form id="alertConfigForm" class="divide-y divide-slate-100">
                    <div class="flex items-center justify-between p-5 hover:bg-slate-50 transition">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-green-100 text-green-600 flex items-center justify-center text-xl shadow-sm">
                                <i class="fas fa-key"></i>
                            </div>
                            <div>
                                <div class="font-bold text-slate-700">Ignição (Ligada/Desligada)</div>
                                <div class="text-xs text-slate-400">Notificar quando o veículo for ligado ou desligado.</div>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="ignition" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600 shadow-inner"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between p-5 hover:bg-slate-50 transition">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-red-100 text-red-600 flex items-center justify-center text-xl shadow-sm">
                                <i class="fas fa-tachometer-alt"></i>
                            </div>
                            <div>
                                <div class="font-bold text-slate-700">Excesso de Velocidade</div>
                                <div class="text-xs text-slate-400">Quando ultrapassar o limite definido no cadastro do veículo.</div>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="overspeed" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:after:translate-x-full after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600 shadow-inner"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between p-5 hover:bg-slate-50 transition">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-xl shadow-sm">
                                <i class="fas fa-draw-polygon"></i>
                            </div>
                            <div>
                                <div class="font-bold text-slate-700">Cercas Virtuais</div>
                                <div class="text-xs text-slate-400">Entrada e saída de perímetros monitorados.</div>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="geofence" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:after:translate-x-full after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600 shadow-inner"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between p-5 hover:bg-slate-50 transition">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center text-xl shadow-sm">
                                <i class="fas fa-car-battery"></i>
                            </div>
                            <div>
                                <div class="font-bold text-slate-700">Bateria & Violação</div>
                                <div class="text-xs text-slate-400">Corte de bateria, alarme de pânico ou violação.</div>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="power" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:after:translate-x-full after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600 shadow-inner"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between p-5 hover:bg-slate-50 transition">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-yellow-100 text-yellow-600 flex items-center justify-center text-xl shadow-sm">
                                <i class="fas fa-tools"></i>
                            </div>
                            <div>
                                <div class="font-bold text-slate-700">Lembretes de Manutenção</div>
                                <div class="text-xs text-slate-400">Troca de óleo, pneus e revisões programadas.</div>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="maintenance" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:after:translate-x-full after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600 shadow-inner"></div>
                        </label>
                    </div>

                    <div class="p-6 bg-slate-50 rounded-b-2xl">
                        <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-slate-300 transition transform active:scale-[0.99] flex items-center justify-center gap-2">
                            <i class="fas fa-save"></i> Salvar Preferências
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div id="tab-history" class="hidden h-full flex flex-col animate-fade-in">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 flex flex-col h-full overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row gap-4 items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <div class="relative w-full sm:w-auto">
                            <i class="fas fa-calendar absolute left-3 top-2.5 text-slate-400"></i>
                            <input type="date" id="historyDateInput" class="pl-9 border border-slate-300 rounded-xl p-2 text-sm outline-none focus:border-blue-500 shadow-sm w-full">
                        </div>
                        <button onclick="loadHistory()" class="bg-blue-600 text-white px-5 py-2 rounded-xl text-sm font-bold hover:bg-blue-700 transition shadow-md shadow-blue-100">
                            Filtrar
                        </button>
                    </div>
                    <div class="text-xs text-slate-400 font-bold uppercase tracking-wider">
                        <span id="totalEvents">0</span> Eventos Encontrados
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto custom-scrollbar">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-500 text-xs uppercase font-bold sticky top-0 z-10 shadow-sm">
                            <tr>
                                <th class="px-6 py-4">Horário</th>
                                <th class="px-6 py-4">Evento</th>
                                <th class="px-6 py-4">Veículo / Placa</th>
                                <th class="px-6 py-4">Cliente</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody" class="divide-y divide-slate-50">
                            <tr><td colspan="4" class="p-12 text-center text-slate-400">Carregando dados...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // --- LÓGICA DE TABS ---
    function switchTab(tab) {
        document.getElementById('tab-config').classList.toggle('hidden', tab !== 'config');
        document.getElementById('tab-history').classList.toggle('hidden', tab !== 'history');
        
        // Estilos Botões
        const btnConfig = document.getElementById('tab-config-btn');
        const btnHistory = document.getElementById('tab-history-btn');

        if (tab === 'config') {
            btnConfig.className = 'group inline-flex items-center py-4 px-1 border-b-2 border-blue-600 font-bold text-sm text-blue-600 transition-all';
            btnHistory.className = 'group inline-flex items-center py-4 px-1 border-b-2 border-transparent font-bold text-sm text-slate-500 hover:text-slate-700 hover:border-slate-300 transition-all';
        } else {
            btnConfig.className = 'group inline-flex items-center py-4 px-1 border-b-2 border-transparent font-bold text-sm text-slate-500 hover:text-slate-700 hover:border-slate-300 transition-all';
            btnHistory.className = 'group inline-flex items-center py-4 px-1 border-b-2 border-blue-600 font-bold text-sm text-blue-600 transition-all';
            loadHistory(); // Carrega histórico ao clicar na aba
        }
    }

    // --- CONFIGURAÇÃO ---
    async function loadConfig() {
        const res = await apiFetch('/sys/alerts/settings');
        if(res.data) {
            const form = document.getElementById('alertConfigForm');
            for(const [key, val] of Object.entries(res.data)) {
                if(form[key]) form[key].checked = val;
            }
        }
    }

    document.getElementById('alertConfigForm').onsubmit = async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Salvando...'; btn.disabled = true;

        const data = {};
        // Captura todos os checkboxes
        ['ignition', 'overspeed', 'geofence', 'power', 'maintenance'].forEach(k => {
            const input = e.target.elements[k];
            data[k] = input ? input.checked : false;
        });

        try {
            const res = await apiFetch('/sys/alerts/settings', { method: 'POST', body: JSON.stringify(data) });
            if(res.success) showToast('Preferências salvas com sucesso!');
            else showToast('Erro ao salvar', 'error');
        } catch(e) { showToast('Erro de conexão', 'error'); } 
        finally { btn.innerHTML = originalHtml; btn.disabled = false; }
    };

    // --- HISTÓRICO ---
    async function loadHistory() {
        const dateInput = document.getElementById('historyDateInput');
        if(!dateInput.value) dateInput.valueAsDate = new Date();
        
        const date = dateInput.value;
        const tbody = document.getElementById('historyTableBody');
        tbody.innerHTML = '<tr><td colspan="4" class="p-12 text-center text-slate-400"><i class="fas fa-circle-notch fa-spin text-3xl mb-3 text-blue-300"></i><p>Buscando eventos...</p></td></tr>';

        try {
            const res = await apiFetch(`/sys/alerts/history?date=${date}`);
            
            if (!res.data || res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="p-12 text-center text-slate-400 bg-slate-50/50"><i class="fas fa-bell-slash text-3xl mb-3 opacity-50"></i><p>Nenhum alerta registrado nesta data.</p></td></tr>';
                document.getElementById('totalEvents').innerText = '0';
                return;
            }

            document.getElementById('totalEvents').innerText = res.data.length;

            tbody.innerHTML = res.data.map(alert => `
                <tr class="hover:bg-slate-50 transition border-b border-slate-50 last:border-0 group">
                    <td class="px-6 py-4">
                        <div class="font-bold text-slate-700 font-mono">${alert.time}</div>
                        <div class="text-[10px] text-slate-400">${alert.date}</div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-bold border bg-${alert.color}-50 text-${alert.color}-700 border-${alert.color}-200 shadow-sm">
                            <i class="fas ${alert.icon}"></i> ${alert.title}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="font-bold text-slate-800">${alert.plate}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2 text-slate-600">
                            <div class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-500">
                                ${alert.customer.charAt(0)}
                            </div>
                            <span class="text-sm">${alert.customer}</span>
                        </div>
                    </td>
                </tr>
            `).join('');
        } catch(e) {
            tbody.innerHTML = `<tr><td colspan="4" class="p-8 text-center text-red-400">Erro: ${e.message || 'Falha ao buscar'}</td></tr>`;
        }
    }

    // Inicialização
    loadConfig();
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('historyDateInput').valueAsDate = new Date();
    });
</script>

<style>
    .animate-fade-in { animation: fadeIn 0.3s ease-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>