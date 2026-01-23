<div class="p-6 space-y-6">

    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Central de Alertas</h1>
            <p class="text-sm text-slate-500">Histórico de eventos e infrações da frota.</p>
        </div>
        <button onclick="alert('Funcionalidade futura: Exportar PDF/Excel')" class="text-slate-500 hover:text-blue-600 font-bold text-sm flex items-center gap-2 transition">
            <i class="fas fa-download"></i> Exportar
        </button>
    </div>

    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <form id="filterForm" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            
            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Período</label>
                <div class="flex gap-2">
                    <input type="datetime-local" name="start" id="dateStart" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 text-sm focus:border-blue-500 outline-none">
                    <input type="datetime-local" name="end" id="dateEnd" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 text-sm focus:border-blue-500 outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Veículo</label>
                <select id="vehicleSelect" name="device_id" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 text-sm focus:border-blue-500 outline-none">
                    <option value="">Todos os Veículos</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Tipo de Evento</label>
                <select id="typeSelect" name="type" class="w-full bg-slate-50 border border-slate-200 rounded-lg p-2 text-sm focus:border-blue-500 outline-none">
                    <option value="">Todos os Tipos</option>
                    <option value="deviceOverspeed">Excesso de Velocidade</option>
                    <option value="geofenceEnter">Entrada de Cerca</option>
                    <option value="geofenceExit">Saída de Cerca</option>
                    <option value="ignitionOn">Ignição Ligada</option>
                    <option value="ignitionOff">Ignição Desligada</option>
                    <option value="alarm">Alarmes/SOS</option>
                </select>
            </div>

            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-lg shadow-blue-200 transition h-[38px]">
                <i class="fas fa-filter mr-1"></i> Filtrar
            </button>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase font-bold">
                    <tr>
                        <th class="px-6 py-4">Data/Hora</th>
                        <th class="px-6 py-4">Veículo</th>
                        <th class="px-6 py-4">Evento</th>
                        <th class="px-6 py-4">Detalhes</th>
                        <th class="px-6 py-4 text-right">Local</th>
                    </tr>
                </thead>
                <tbody id="alertsGrid" class="divide-y divide-slate-100">
                    <tr><td colspan="5" class="p-8 text-center text-slate-400">Selecione os filtros e clique em buscar.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // 1. Inicialização de Datas (Hoje)
    const now = new Date();
    const start = new Date(); start.setHours(0,0,0,0);
    const end = new Date(); end.setHours(23,59,59,999);
    
    const toISO = (d) => d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0') + 'T' + String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0');
    
    document.getElementById('dateStart').value = toISO(start);
    document.getElementById('dateEnd').value = toISO(end);

    // 2. Carregar Veículos
    async function loadVehicles() {
        const vehicles = await apiFetch('/api/dashboard/data'); 
        const sel = document.getElementById('vehicleSelect');
        if(vehicles) {
            vehicles.forEach(v => {
                sel.innerHTML += `<option value="${v.id}">${v.plate} - ${v.name}</option>`;
            });
        }
    }
    loadVehicles();

    // 3. Buscar Alertas
    document.getElementById('filterForm').onsubmit = async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        const params = new URLSearchParams(new FormData(e.target));
        // Ajuste manual de datas para formato SQL se necessário, mas controller aceita ISO do input datetime-local
        
        try {
            const res = await apiFetch(`/api/alerts?${params.toString()}`);
            const tbody = document.getElementById('alertsGrid');
            
            if (!res || !res.data || res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="p-8 text-center text-slate-400">Nenhum alerta encontrado neste período.</td></tr>';
            } else {
                tbody.innerHTML = res.data.map(row => {
                    // Ícone baseado no tipo
                    let icon = 'fa-bell text-slate-400';
                    let bg = 'bg-slate-100';
                    if(row.type.includes('Overspeed')) { icon = 'fa-tachometer-alt text-red-500'; bg = 'bg-red-50'; }
                    else if(row.type.includes('geofence')) { icon = 'fa-map-marker-alt text-orange-500'; bg = 'bg-orange-50'; }
                    else if(row.type.includes('ignitionOn')) { icon = 'fa-key text-green-500'; bg = 'bg-green-50'; }
                    else if(row.type.includes('alarm')) { icon = 'fa-exclamation-triangle text-red-600 animate-pulse'; bg = 'bg-red-100'; }

                    // Detalhes extras (velocidade, cerca)
                    let details = '-';
                    if (row.speed > 0) details = Math.round(row.speed * 1.852) + ' km/h'; // Converter nós para km/h se vier cru do banco
                    if (row.attrs && row.attrs.geofenceId) details += ' (Cerca ID: ' + row.attrs.geofenceId + ')';

                    return `
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 font-mono text-xs text-slate-500">${row.formatted_time}</td>
                            <td class="px-6 py-4 font-bold text-slate-700">${row.plate}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full ${bg} flex items-center justify-center shadow-sm">
                                        <i class="fas ${icon}"></i>
                                    </div>
                                    <span class="text-sm font-medium text-slate-700">${row.type_label}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500">${details}</td>
                            <td class="px-6 py-4 text-right">
                                <a href="https://www.google.com/maps?q=${row.latitude},${row.longitude}" target="_blank" class="text-blue-500 hover:text-blue-700 font-bold text-xs">
                                    <i class="fas fa-map-marked-alt mr-1"></i> Ver no Mapa
                                </a>
                            </td>
                        </tr>
                    `;
                }).join('');
            }
        } catch(err) {
            console.error(err);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-filter mr-1"></i> Filtrar';
        }
    };
    
    // Auto-carregar ao abrir (opcional, pode pesar se tiver muitos dados, melhor esperar filtro)
    // document.getElementById('filterForm').requestSubmit();
</script>