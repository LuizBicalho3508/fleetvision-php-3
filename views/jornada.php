<div class="p-6 space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-800"><i class="fas fa-stopwatch text-blue-600 mr-2"></i> Controle de Jornada</h1>
            <p class="text-sm text-slate-500">Monitoramento em tempo real (Lei 13.103).</p>
        </div>
        <button onclick="loadJornada()" class="w-10 h-10 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition">
            <i class="fas fa-sync-alt"></i>
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-5 rounded-2xl shadow-sm border-l-4 border-green-500">
            <p class="text-xs font-bold text-slate-400 uppercase">Em Conformidade</p>
            <h3 class="text-3xl font-bold text-green-600" id="countOk">0</h3>
        </div>
        <div class="bg-white p-5 rounded-2xl shadow-sm border-l-4 border-red-500">
            <p class="text-xs font-bold text-slate-400 uppercase">Com Infrações</p>
            <h3 class="text-3xl font-bold text-red-600" id="countCritical">0</h3>
        </div>
        <div class="bg-white p-5 rounded-2xl shadow-sm border-l-4 border-blue-500">
            <p class="text-xs font-bold text-slate-400 uppercase">Dirigindo Agora</p>
            <h3 class="text-3xl font-bold text-blue-600" id="countDriving">0</h3>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="journeyGrid">
        </div>
</div>

<script>
    async function loadJornada() {
        const grid = document.getElementById('journeyGrid');
        grid.innerHTML = '<div class="col-span-3 text-center py-10"><i class="fas fa-spinner fa-spin text-2xl text-blue-500"></i></div>';

        try {
            const res = await apiFetch('/api/journey');
            if(!res.success) throw new Error(res.error);

            let ok=0, crit=0, driv=0;
            grid.innerHTML = '';

            if(res.data.length === 0) {
                grid.innerHTML = '<div class="col-span-3 text-center text-slate-400">Nenhuma jornada iniciada hoje.</div>';
                updateCounts(0,0,0);
                return;
            }

            res.data.forEach(d => {
                if(d.health === 'ok') ok++; else crit++;
                if(d.status === 'dirigindo') driv++;

                const statusBadge = d.status === 'dirigindo' 
                    ? '<span class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs font-bold animate-pulse">Dirigindo</span>'
                    : '<span class="bg-slate-100 text-slate-500 px-2 py-1 rounded text-xs font-bold">Descanso</span>';

                const healthBorder = d.health === 'ok' ? 'border-slate-200' : 'border-red-300 ring-2 ring-red-50';
                
                let violationsHtml = '';
                if(d.violations.length > 0) {
                    violationsHtml = `<div class="mt-3 p-2 bg-red-50 rounded text-xs text-red-600 font-bold border border-red-100">
                        <i class="fas fa-exclamation-triangle mr-1"></i> ${d.violations[0]}
                    </div>`;
                }

                grid.innerHTML += `
                    <div class="bg-white p-6 rounded-2xl shadow-sm border ${healthBorder} hover:shadow-md transition">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="font-bold text-slate-800 text-lg">${d.name}</h3>
                                <p class="text-xs text-slate-500">${d.current_vehicle}</p>
                            </div>
                            ${statusBadge}
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 text-sm mb-2">
                            <div>
                                <p class="text-xs text-slate-400 uppercase font-bold">Contínuo</p>
                                <p class="font-mono font-bold text-slate-700 ${d.continuous_driving > 19800 ? 'text-red-500':''}">${d.fmt_cont}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 uppercase font-bold">Diário</p>
                                <p class="font-mono font-bold text-slate-700 ${d.total_driving > 36000 ? 'text-red-500':''}">${d.fmt_total}</p>
                            </div>
                        </div>
                        
                        <div class="w-full bg-slate-100 h-1.5 rounded-full mt-2 overflow-hidden">
                            <div class="h-full bg-blue-500" style="width: ${(d.total_driving / 36000)*100}%"></div>
                        </div>
                        
                        ${violationsHtml}
                    </div>
                `;
            });
            
            updateCounts(ok, crit, driv);

        } catch(e) { console.error(e); }
    }

    function updateCounts(ok, crit, driv) {
        document.getElementById('countOk').innerText = ok;
        document.getElementById('countCritical').innerText = crit;
        document.getElementById('countDriving').innerText = driv;
    }

    loadJornada();
    setInterval(loadJornada, 60000); // Atualiza a cada minuto
</script>