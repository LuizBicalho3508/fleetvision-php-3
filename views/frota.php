<div class="p-6 space-y-6">

    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Minha Frota</h1>
            <p class="text-sm text-slate-500">Status e localização dos veículos.</p>
        </div>
        <button class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-sm transition flex items-center gap-2 opacity-50 cursor-not-allowed" title="Adição manual desativada nesta versão">
            <i class="fas fa-truck-monster"></i> Novo Veículo
        </button>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase font-bold">
                    <tr>
                        <th class="px-6 py-4">Veículo</th>
                        <th class="px-6 py-4">Identificador</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Última Posição</th>
                        <th class="px-6 py-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody id="fleetGrid" class="divide-y divide-slate-100">
                    <tr><td colspan="5" class="p-8 text-center text-slate-400">Carregando frota...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    async function loadFleet() {
        const vehicles = await apiFetch('/api/dashboard/data'); 
        
        if (!vehicles || vehicles.length === 0) {
            document.getElementById('fleetGrid').innerHTML = '<tr><td colspan="5" class="p-8 text-center text-slate-400">Nenhum veículo encontrado.</td></tr>';
            return;
        }

        document.getElementById('fleetGrid').innerHTML = vehicles.map(v => `
            <tr class="hover:bg-slate-50 transition">
                <td class="px-6 py-4">
                    <div class="font-bold text-slate-800">${v.plate}</div>
                    <div class="text-xs text-slate-500 mt-0.5">${v.name}</div>
                </td>
                <td class="px-6 py-4">
                    <span class="font-mono text-slate-500 bg-slate-100 px-2 py-1 rounded text-xs">${v.deviceid}</span>
                </td>
                <td class="px-6 py-4">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold ${v.speed > 0 ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600'}">
                        <span class="w-1.5 h-1.5 rounded-full ${v.speed > 0 ? 'bg-green-500' : 'bg-slate-400'}"></span>
                        ${v.speed > 0 ? 'Em Movimento' : 'Parado'}
                    </span>
                </td>
                <td class="px-6 py-4">
                    <div class="text-slate-700 truncate max-w-[200px]" title="${v.address || ''}">${v.address || 'Sem endereço'}</div>
                    <div class="text-xs text-slate-400 mt-0.5">${v.lastupdate ? new Date(v.lastupdate).toLocaleString() : 'Nunca'}</div>
                </td>
                <td class="px-6 py-4 text-right">
                    <button class="text-slate-400 hover:text-blue-600 transition" title="Editar (Em breve)"><i class="fas fa-cog"></i></button>
                </td>
            </tr>
        `).join('');
    }
    loadFleet();
</script>