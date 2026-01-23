<div class="p-6 space-y-6">
    <div class="bg-white border border-slate-200 px-6 py-5 flex flex-col md:flex-row justify-between items-center shadow-sm rounded-2xl">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                <i class="fas fa-server text-indigo-600"></i> Saúde do Servidor
            </h2>
            <p class="text-sm text-slate-500">Monitoramento em tempo real (VPS).</p>
        </div>
        <div class="mt-4 md:mt-0 flex items-center gap-2 text-xs font-mono text-slate-500 bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200">
            <i class="fas fa-clock"></i> Uptime: <span id="val-uptime" class="font-bold text-slate-700">Carregando...</span>
        </div>
    </div>

    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider pl-2">Serviços Críticos</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shadow-sm">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
                <div>
                    <h4 class="font-bold text-slate-700">Traccar GPS</h4>
                    <p class="text-xs text-slate-400 font-mono">Porta 8082</p>
                </div>
            </div>
            <span id="svc-traccar" class="px-3 py-1 bg-slate-100 text-slate-400 rounded-full text-xs font-bold">...</span>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shadow-sm">
                    <i class="fas fa-database"></i>
                </div>
                <div>
                    <h4 class="font-bold text-slate-700">PostgreSQL</h4>
                    <p class="text-xs text-slate-400 font-mono">Porta 5432</p>
                </div>
            </div>
            <span id="svc-postgres" class="px-3 py-1 bg-slate-100 text-slate-400 rounded-full text-xs font-bold">...</span>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl shadow-sm">
                    <i class="fas fa-globe"></i>
                </div>
                <div>
                    <h4 class="font-bold text-slate-700">Webserver</h4>
                    <p class="text-xs text-slate-400 font-mono">Nginx/PHP</p>
                </div>
            </div>
            <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold border border-green-200 flex items-center gap-2">
                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span> Online
            </span>
        </div>
    </div>

    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider pl-2 pt-4">Hardware</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <div class="flex justify-between items-center mb-4">
                <h4 class="font-bold text-slate-700 flex items-center gap-2"><i class="fas fa-microchip text-slate-400"></i> CPU</h4>
                <span id="txt-cpu" class="text-2xl font-black text-slate-700">0%</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden">
                <div id="bar-cpu" class="h-full bg-blue-500 transition-all duration-1000 rounded-full" style="width: 0%"></div>
            </div>
            <p class="text-xs text-slate-400 mt-2 text-right">Carga média</p>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <div class="flex justify-between items-center mb-4">
                <h4 class="font-bold text-slate-700 flex items-center gap-2"><i class="fas fa-memory text-slate-400"></i> Memória RAM</h4>
                <span id="txt-ram" class="text-2xl font-black text-slate-700">0%</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden">
                <div id="bar-ram" class="h-full bg-purple-500 transition-all duration-1000 rounded-full" style="width: 0%"></div>
            </div>
            <div class="flex justify-between mt-2 text-xs text-slate-500 font-mono">
                <span>Uso: <span id="val-ram-used" class="font-bold">0</span> MB</span>
                <span>Total: <span id="val-ram-total">0</span> MB</span>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <div class="flex justify-between items-center mb-4">
                <h4 class="font-bold text-slate-700 flex items-center gap-2"><i class="fas fa-hdd text-slate-400"></i> Armazenamento</h4>
                <span id="txt-disk" class="text-2xl font-black text-slate-700">0%</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden">
                <div id="bar-disk" class="h-full bg-orange-500 transition-all duration-1000 rounded-full" style="width: 0%"></div>
            </div>
            <div class="flex justify-between mt-2 text-xs text-slate-500 font-mono">
                <span>Uso: <span id="val-disk-used" class="font-bold">0</span> GB</span>
                <span>Total: <span id="val-disk-total">0</span> GB</span>
            </div>
        </div>

    </div>
</div>

<script>
    async function updateMetrics() {
        try {
            // Chama a nova API
            const res = await apiFetch('/api/admin/server/stats');
            
            if (!res) return; // Erro de conexão tratado pelo wrapper

            // Uptime
            document.getElementById('val-uptime').innerText = res.uptime;

            // Barras e Textos
            updateBar('cpu', res.cpu, '%');
            updateBar('ram', res.ram_pct, '%');
            updateBar('disk', res.disk_pct, '%');

            // Valores Detalhados
            document.getElementById('val-ram-used').innerText = res.ram_used;
            document.getElementById('val-ram-total').innerText = res.ram_total;
            document.getElementById('val-disk-used').innerText = res.disk_used;
            document.getElementById('val-disk-total').innerText = res.disk_total;

            // Serviços
            updateService('traccar', res.services.traccar);
            updateService('postgres', res.services.postgres);

        } catch(e) {
            console.error("Erro no Monitoramento:", e);
        }
    }

    function updateBar(id, val, suffix) {
        const bar = document.getElementById('bar-'+id);
        const txt = document.getElementById('txt-'+id);
        
        val = parseFloat(val) || 0;
        
        bar.style.width = val + '%';
        txt.innerText = val + suffix;

        // Cores Dinâmicas
        bar.className = 'h-full transition-all duration-1000 rounded-full ';
        if(val < 60) bar.className += 'bg-green-500';
        else if(val < 85) bar.className += 'bg-yellow-500';
        else bar.className += 'bg-red-500';
    }

    function updateService(id, isOnline) {
        const el = document.getElementById('svc-'+id);
        if(isOnline) {
            el.className = 'px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold border border-green-200 flex items-center gap-2 transition-all';
            el.innerHTML = '<span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span> Online';
        } else {
            el.className = 'px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-bold border border-red-200 flex items-center gap-2 transition-all';
            el.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Parado';
        }
    }

    // Atualiza a cada 5 segundos
    updateMetrics();
    setInterval(updateMetrics, 5000);
</script>