<div class="p-6 space-y-6 h-full flex flex-col">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 shrink-0">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Jornada do Motorista</h1>
            <p class="text-sm text-slate-500">Controle de horas e conformidade com a Lei 13.103.</p>
        </div>
        
        <div class="flex flex-wrap items-center gap-3 bg-white p-2 rounded-xl shadow-sm border border-slate-200">
            <div class="flex items-center gap-2 px-2 border-r border-slate-100 pr-4">
                <i class="fas fa-calendar-alt text-slate-400"></i>
                <input type="date" id="filterStart" class="text-sm text-slate-600 outline-none w-32 bg-transparent">
                <span class="text-slate-300">até</span>
                <input type="date" id="filterEnd" class="text-sm text-slate-600 outline-none w-32 bg-transparent">
            </div>
            <div class="px-2">
                <select id="filterDriver" class="text-sm text-slate-600 outline-none bg-transparent min-w-[180px] cursor-pointer">
                    <option value="">Todos os Motoristas</option>
                </select>
            </div>
            <button onclick="loadJourney()" class="bg-blue-600 text-white w-9 h-9 rounded-lg flex items-center justify-center hover:bg-blue-700 transition shadow-md shadow-blue-100 active:scale-95">
                <i class="fas fa-search"></i>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 shrink-0">
        
        <div onclick="applyLocalFilter('all')" class="cursor-pointer bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl p-5 text-white shadow-lg shadow-blue-100 relative overflow-hidden group hover:scale-[1.02] transition-transform filter-card ring-offset-2" id="card-all">
            <div class="relative z-10">
                <p class="text-blue-100 text-xs font-bold uppercase tracking-wider mb-1">Horas Totais</p>
                <h3 class="text-3xl font-bold" id="sumHours">00:00</h3>
            </div>
            <i class="fas fa-clock absolute right-4 bottom-4 text-blue-400 text-5xl opacity-30"></i>
        </div>

        <div onclick="applyLocalFilter('all')" class="cursor-pointer bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:border-blue-300 transition-colors group">
            <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">Trechos</p>
            <h3 class="text-3xl font-bold text-slate-700" id="sumTrips">0</h3>
            <div class="absolute right-6 bottom-6 w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 group-hover:bg-blue-50 group-hover:text-blue-500 transition-colors">
                <i class="fas fa-route"></i>
            </div>
        </div>

        <div onclick="applyLocalFilter('violation')" class="cursor-pointer bg-white rounded-2xl p-5 border border-slate-100 shadow-sm relative overflow-hidden hover:border-red-300 transition-colors filter-card ring-offset-2" id="card-violation">
            <div class="relative z-10">
                <p class="text-red-400 text-xs font-bold uppercase tracking-wider mb-1">Violações 13.103</p>
                <h3 class="text-3xl font-bold text-red-600" id="sumViolations">0</h3>
                <p class="text-[10px] text-red-300 mt-1">> 5.5h sem parada</p>
            </div>
            <div class="absolute right-4 bottom-4 w-12 h-12 rounded-full bg-red-50 flex items-center justify-center text-red-500">
                <i class="fas fa-exclamation-triangle text-xl"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
            <p class="text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">Status Geral</p>
            <h3 class="text-lg font-bold text-green-600 flex items-center gap-2 mt-2">
                <i class="fas fa-check-circle"></i> <span id="statusText">Regular</span>
            </h3>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 flex-1 overflow-hidden flex flex-col">
        <div class="p-4 border-b border-slate-100 bg-slate-50/50 font-bold text-slate-700 text-sm flex justify-between items-center">
            <span id="listTitle">Detalhamento de Atividades</span>
            <span id="filteredCount" class="text-xs text-slate-400 font-normal"></span>
        </div>
        
        <div class="flex-1 overflow-y-auto custom-scrollbar p-0">
            <table class="w-full text-sm text-left border-collapse">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-xs sticky top-0 shadow-sm z-10">
                    <tr>
                        <th class="px-6 py-4">Condutor / Veículo</th>
                        <th class="px-6 py-4">Início</th>
                        <th class="px-6 py-4">Fim</th>
                        <th class="px-6 py-4">Duração</th>
                        <th class="px-6 py-4 text-center">Lei 13.103</th>
                        <th class="px-6 py-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody id="journeyBody" class="divide-y divide-slate-50">
                    <tr><td colspan="6" class="p-12 text-center text-slate-400">Carregando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Armazena dados globais para filtragem local rápida
    let globalJourneyData = [];
    let currentFilter = 'all';

    async function init() {
        // --- DATA DE HOJE (Padrão) ---
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('filterStart').value = today;
        document.getElementById('filterEnd').value = today;

        // Carrega Motoristas
        try {
            const resDr = await apiFetch('/sys/drivers');
            if(resDr.data) {
                const sel = document.getElementById('filterDriver');
                resDr.data.forEach(d => {
                    const opt = document.createElement('option');
                    opt.value = d.id;
                    opt.innerText = d.name;
                    sel.appendChild(opt);
                });
            }
        } catch(e) {}

        loadJourney();
    }

    async function loadJourney() {
        const start = document.getElementById('filterStart').value;
        const end = document.getElementById('filterEnd').value;
        const driver = document.getElementById('filterDriver').value;
        const tbody = document.getElementById('journeyBody');

        tbody.innerHTML = '<tr><td colspan="6" class="p-12 text-center text-slate-400"><i class="fas fa-circle-notch fa-spin text-2xl text-blue-300"></i></td></tr>';

        try {
            const url = `/sys/journey/filter?start=${start}&end=${end}&driver=${driver}`;
            const res = await apiFetch(url);

            if (!res.data || res.data.length === 0) {
                globalJourneyData = [];
                renderTable(); // Renderiza vazio
                updateSummary({total_hours:'00:00', total_trips:0, violations:0});
                return;
            }

            globalJourneyData = res.data;
            updateSummary(res.summary);
            renderTable();

        } catch(e) {
            tbody.innerHTML = `<tr><td colspan="6" class="p-12 text-center text-red-400">Erro ao carregar dados.</td></tr>`;
        }
    }

    // --- FILTRAGEM LOCAL (INTERATIVA) ---
    function applyLocalFilter(type) {
        currentFilter = type;
        
        // UI Feedback dos Cards
        document.querySelectorAll('.filter-card').forEach(el => el.classList.remove('ring-2', 'ring-blue-400', 'ring-red-400'));
        if (type === 'all') document.getElementById('card-all').classList.add('ring-2', 'ring-blue-400');
        if (type === 'violation') document.getElementById('card-violation').classList.add('ring-2', 'ring-red-400');

        renderTable();
    }

    function renderTable() {
        const tbody = document.getElementById('journeyBody');
        
        // Filtra os dados
        const filteredData = globalJourneyData.filter(item => {
            if (currentFilter === 'violation') return item.is_13103_violation;
            return true; // 'all'
        });

        // Atualiza contador
        document.getElementById('filteredCount').innerText = `${filteredData.length} registros exibidos`;

        if (filteredData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="p-12 text-center text-slate-400 flex flex-col items-center"><i class="fas fa-folder-open text-3xl mb-2 opacity-30"></i><p>Nenhum registro encontrado com este filtro.</p></td></tr>';
            return;
        }

        tbody.innerHTML = filteredData.map(item => {
            const isOpen = item.status === 'open';
            
            const statusBadge = isOpen 
                ? '<span class="px-2 py-1 rounded bg-green-100 text-green-700 text-[10px] font-bold animate-pulse">EM ANDAMENTO</span>'
                : '<span class="px-2 py-1 rounded bg-slate-100 text-slate-500 text-[10px] font-bold">FINALIZADA</span>';

            let lawBadge = '<span class="text-green-500 text-xs font-bold"><i class="fas fa-check"></i> OK</span>';
            if (item.is_13103_violation) {
                lawBadge = '<span class="inline-flex items-center gap-1 px-2 py-1 rounded bg-red-50 text-red-600 text-[10px] font-bold border border-red-100"><i class="fas fa-exclamation-triangle"></i> VIOLAÇÃO (>5.5h)</span>';
            }

            return `
                <tr class="hover:bg-slate-50 transition group border-b border-slate-50 last:border-0">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm border border-blue-100 shadow-sm">
                                ${(item.driver_name || '?').charAt(0)}
                            </div>
                            <div>
                                <div class="font-bold text-slate-700 text-sm">${item.driver_name || 'Não Identificado'}</div>
                                <div class="text-[10px] text-slate-400 flex items-center gap-1">
                                    <i class="fas fa-truck text-[9px]"></i> ${item.plate || '---'}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-slate-600 text-xs">
                        <div class="font-bold text-slate-700">${item.start_time_display}</div>
                        <div class="text-[10px] text-slate-400">${item.start_date_display}</div>
                    </td>
                    <td class="px-6 py-4 text-slate-600 text-xs">
                        ${isOpen ? '<span class="text-green-600 italic font-bold">-- : --</span>' : `
                            <div class="font-bold text-slate-700">${item.end_time_display}</div>
                            <div class="text-[10px] text-slate-400">${item.end_date_display}</div>
                        `}
                    </td>
                    <td class="px-6 py-4 font-mono font-bold text-slate-700 text-xs">
                        ${item.duration_formatted}
                    </td>
                    <td class="px-6 py-4 text-center">
                        ${lawBadge}
                    </td>
                    <td class="px-6 py-4 text-center">
                        ${statusBadge}
                    </td>
                </tr>
            `;
        }).join('');
    }

    function updateSummary(summary) {
        document.getElementById('sumHours').innerText = summary.total_hours;
        document.getElementById('sumTrips').innerText = summary.total_trips;
        document.getElementById('sumViolations').innerText = summary.violations;
        
        const statusEl = document.getElementById('statusText');
        if (summary.violations > 0) {
            statusEl.innerHTML = 'Atenção';
            statusEl.className = 'text-red-500 font-bold';
        } else {
            statusEl.innerHTML = 'Regular';
            statusEl.className = 'text-green-600 font-bold';
        }
    }

    init();
</script>