<div class="p-6 space-y-6">
    <div class="flex flex-col md:flex-row justify-between items-center bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                <i class="fas fa-trophy text-yellow-500"></i> Ranking e Performance
            </h1>
            <p class="text-sm text-slate-500">Gamificação para incentivar a direção segura.</p>
        </div>
        <div class="flex gap-2">
            <button onclick="openRulesModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-sm font-bold transition">
                <i class="fas fa-cog"></i> Regras
            </button>
            <div class="flex bg-slate-50 border border-slate-200 rounded-lg px-2 items-center">
                <input type="date" id="rankStart" class="bg-transparent text-xs border-none outline-none text-slate-600 w-24">
                <span class="text-slate-300 mx-1">-</span>
                <input type="date" id="rankEnd" class="bg-transparent text-xs border-none outline-none text-slate-600 w-24">
            </div>
            <button onclick="loadRanking()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-bold shadow-blue-200 shadow-lg transition">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6" id="podiumContainer">
        </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between">
            <h3 class="font-bold text-slate-700">Tabela de Classificação</h3>
            <div class="flex gap-2">
                <button onclick="switchView('drivers')" id="btnDrivers" class="text-xs font-bold px-3 py-1 bg-blue-100 text-blue-700 rounded-full transition">Motoristas</button>
                <button onclick="switchView('vehicles')" id="btnVehicles" class="text-xs font-bold px-3 py-1 bg-slate-100 text-slate-500 rounded-full hover:bg-slate-200 transition">Veículos</button>
            </div>
        </div>
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-500 uppercase text-xs font-bold">
                <tr>
                    <th class="px-6 py-3">Pos.</th>
                    <th class="px-6 py-3">Nome / Placa</th>
                    <th class="px-6 py-3">Score</th>
                    <th class="px-6 py-3 text-center">Velocidade</th>
                    <th class="px-6 py-3 text-center">Ociosidade</th>
                    <th class="px-6 py-3 text-center">Nota</th>
                </tr>
            </thead>
            <tbody id="rankingTable" class="divide-y divide-slate-100"></tbody>
        </table>
    </div>
</div>

<div id="rulesModal" class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
        <h3 class="font-bold text-lg mb-4 text-slate-800">Configurar Pesos</h3>
        <form id="formRules" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Limite Velocidade (km/h)</label>
                    <input type="number" name="speed_limit" class="w-full border p-2 rounded bg-slate-50 font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-red-500 mb-1">Pena (Pontos)</label>
                    <input type="number" name="speed_penalty" class="w-full border border-red-100 p-2 rounded bg-red-50 font-mono text-red-700">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Limite Ocioso (min)</label>
                    <input type="number" name="idle_interval" class="w-full border p-2 rounded bg-slate-50 font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-red-500 mb-1">Pena (Pontos)</label>
                    <input type="number" name="idle_penalty" class="w-full border border-red-100 p-2 rounded bg-red-50 font-mono text-red-700">
                </div>
            </div>
            <div class="pt-4 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('rulesModal').classList.add('hidden')" class="px-4 py-2 text-slate-500 hover:bg-slate-100 rounded font-bold transition">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-bold transition">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
    let currentData = { drivers: [], vehicles: [] };
    let currentView = 'drivers';

    // Init Datas
    const now = new Date();
    document.getElementById('rankEnd').valueAsDate = now;
    now.setDate(1); 
    document.getElementById('rankStart').valueAsDate = now;

    async function loadRanking() {
        const s = document.getElementById('rankStart').value;
        const e = document.getElementById('rankEnd').value;
        
        try {
            const res = await apiFetch(`/api/ranking?from=${s} 00:00:00&to=${e} 23:59:59`);
            if(res.success) {
                currentData = res;
                renderView();
                // Popula modal com regras atuais
                const f = document.getElementById('formRules');
                f.speed_limit.value = res.rules.speed_limit;
                f.speed_penalty.value = res.rules.speed_penalty;
                f.idle_interval.value = res.rules.idle_interval;
                f.idle_penalty.value = res.rules.idle_penalty;
            } else {
                showToast(res.error, 'error');
            }
        } catch(e) { showToast('Erro ao carregar dados', 'error'); }
    }

    function renderView() {
        const list = currentData[currentView];
        const tbody = document.getElementById('rankingTable');
        const podium = document.getElementById('podiumContainer');
        
        tbody.innerHTML = ''; podium.innerHTML = '';

        if(list.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="p-8 text-center text-slate-400">Sem dados para o período.</td></tr>';
            return;
        }

        // Top 3 Podium
        list.slice(0, 3).forEach((item, i) => {
            const colors = i===0 ? 'from-yellow-100 to-white text-yellow-700 border-yellow-200' : (i===1 ? 'from-slate-100 to-white text-slate-700 border-slate-200' : 'from-orange-100 to-white text-orange-700 border-orange-200');
            const icon = i===0 ? 'fa-crown' : (i===1 ? 'fa-medal' : 'fa-award');
            
            podium.innerHTML += `
                <div class="bg-gradient-to-b ${colors} border p-6 rounded-2xl shadow-sm flex flex-col items-center relative overflow-hidden">
                    <i class="fas ${icon} text-4xl mb-2 opacity-50"></i>
                    <div class="text-3xl font-bold mb-1">${i+1}º</div>
                    <div class="font-bold text-lg truncate w-full text-center">${item.name}</div>
                    <div class="text-xs opacity-75">${item.plate || 'Motorista'}</div>
                    <div class="mt-4 text-4xl font-black">${item.score}</div>
                    <div class="text-[10px] uppercase font-bold tracking-widest opacity-50">Score</div>
                </div>
            `;
        });

        // Tabela Completa
        list.forEach((item, i) => {
            let gradeColor = item.score >= 90 ? 'bg-green-100 text-green-700' : (item.score >= 70 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700');
            let grade = item.score >= 90 ? 'A' : (item.score >= 70 ? 'B' : 'C');

            tbody.innerHTML += `
                <tr class="hover:bg-slate-50 transition border-b border-slate-50">
                    <td class="px-6 py-4 font-bold text-slate-400">#${i+1}</td>
                    <td class="px-6 py-4 font-medium text-slate-700">
                        ${item.name} <span class="text-xs text-slate-400 ml-1">${item.plate || ''}</span>
                    </td>
                    <td class="px-6 py-4 font-bold text-slate-800">${item.score}</td>
                    <td class="px-6 py-4 text-center">
                        ${item.stats.speed_count > 0 ? `<span class="bg-red-50 text-red-600 px-2 py-1 rounded text-xs font-bold">-${item.stats.speed_count}</span>` : '<span class="text-slate-300">-</span>'}
                    </td>
                    <td class="px-6 py-4 text-center">
                        ${item.stats.idle_count > 0 ? `<span class="bg-orange-50 text-orange-600 px-2 py-1 rounded text-xs font-bold">-${item.stats.idle_count}</span>` : '<span class="text-slate-300">-</span>'}
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="${gradeColor} w-8 h-8 flex items-center justify-center rounded-full font-bold text-xs mx-auto">${grade}</span>
                    </td>
                </tr>
            `;
        });
    }

    function switchView(view) {
        currentView = view;
        document.getElementById('btnDrivers').className = view === 'drivers' ? 'text-xs font-bold px-3 py-1 bg-blue-100 text-blue-700 rounded-full transition' : 'text-xs font-bold px-3 py-1 bg-slate-100 text-slate-500 rounded-full hover:bg-slate-200 transition';
        document.getElementById('btnVehicles').className = view === 'vehicles' ? 'text-xs font-bold px-3 py-1 bg-blue-100 text-blue-700 rounded-full transition' : 'text-xs font-bold px-3 py-1 bg-slate-100 text-slate-500 rounded-full hover:bg-slate-200 transition';
        renderView();
    }

    function openRulesModal() { document.getElementById('rulesModal').classList.remove('hidden'); }

    // Salvar Regras
    document.getElementById('formRules').onsubmit = async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(e.target));
        const res = await apiFetch('/api/ranking/rules', { method: 'POST', body: JSON.stringify(data) });
        if(res.success) {
            document.getElementById('rulesModal').classList.add('hidden');
            loadRanking();
            showToast('Regras atualizadas!');
        } else {
            showToast(res.error, 'error');
        }
    };

    loadRanking();
</script>