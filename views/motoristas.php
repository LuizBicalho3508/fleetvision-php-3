<div class="p-6 space-y-6">

    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Motoristas</h1>
            <p class="text-sm text-slate-500">Gestão de condutores e identificação.</p>
        </div>
        <button onclick="openModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-sm transition flex items-center gap-2">
            <i class="fas fa-user-plus"></i> Novo Motorista
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="driversGrid">
        <div class="col-span-3 text-center py-10 text-slate-400">Carregando...</div>
    </div>
</div>

<div id="modalDriver" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden z-[100] flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex justify-between items-center">
            <h3 class="font-bold text-slate-800 text-lg">Dados do Motorista</h3>
            <button onclick="closeModal()" class="text-slate-400 hover:text-red-500 transition"><i class="fas fa-times text-xl"></i></button>
        </div>
        <form id="formDriver" class="p-6 space-y-4">
            <input type="hidden" name="id" id="driverId">
            
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nome Completo</label>
                <input type="text" name="name" id="driverName" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-blue-500 outline-none transition" required>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">CNH (Registro)</label>
                    <input type="text" name="cnh" id="driverCnh" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-blue-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Validade CNH</label>
                    <input type="date" name="cnh_validity" id="driverValid" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-blue-500 outline-none transition">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Telefone</label>
                    <input type="text" name="phone" id="driverPhone" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-blue-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">RFID / iButton</label>
                    <input type="text" name="rfid" id="driverRfid" placeholder="Ex: 00012345" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-blue-500 outline-none transition">
                </div>
            </div>

            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" name="active_check" id="driverActiveCheck" checked onchange="document.getElementById('driverStatus').value = this.checked ? 'active' : 'inactive'">
                <label for="driverActiveCheck" class="text-sm text-slate-600">Motorista Ativo</label>
                <input type="hidden" name="status" id="driverStatus" value="active">
            </div>

            <div class="pt-4 flex justify-end gap-3">
                <button type="button" onclick="closeModal()" class="px-5 py-2.5 rounded-xl text-slate-500 hover:bg-slate-100 font-bold text-sm transition">Cancelar</button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-blue-200 transition">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
    async function loadDrivers() {
        const res = await apiFetch('/api/drivers');
        const grid = document.getElementById('driversGrid');
        
        if(!res || !res.data || res.data.length === 0) {
            grid.innerHTML = '<div class="col-span-3 text-center text-slate-400 py-10">Nenhum motorista cadastrado.</div>';
            return;
        }

        grid.innerHTML = res.data.map(d => {
            // Status da CNH
            let cnhBadge = '';
            if (d.cnh_status === 'expired') cnhBadge = '<span class="text-xs text-red-600 font-bold bg-red-100 px-2 py-0.5 rounded ml-2">CNH Vencida</span>';
            if (d.cnh_status === 'warning') cnhBadge = '<span class="text-xs text-orange-600 font-bold bg-orange-100 px-2 py-0.5 rounded ml-2">Vence em breve</span>';

            return `
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 hover:border-blue-300 transition group relative">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-14 h-14 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center font-bold text-xl border border-slate-200">
                        ${d.name.substr(0,2).toUpperCase()}
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-800 text-lg">${d.name}</h4>
                        <div class="text-xs text-slate-500 flex items-center">
                            <i class="fas fa-id-card mr-1"></i> ${d.cnh || 'S/ CNH'}
                            ${cnhBadge}
                        </div>
                    </div>
                </div>
                
                <div class="space-y-2 text-sm text-slate-600 border-t border-slate-50 pt-3">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Telefone:</span>
                        <span class="font-medium">${d.phone || '-'}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">RFID:</span>
                        <span class="font-mono bg-slate-50 px-1 rounded text-xs">${d.rfid || '-'}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Validade:</span>
                        <span class="font-medium">${d.cnh_validity ? new Date(d.cnh_validity).toLocaleDateString() : '-'}</span>
                    </div>
                </div>

                <div class="absolute top-4 right-4 flex gap-2 opacity-0 group-hover:opacity-100 transition bg-white pl-2">
                    <button onclick='editDriver(${JSON.stringify(d)})' class="text-slate-400 hover:text-blue-600 transition"><i class="fas fa-pen p-1"></i></button>
                    <button onclick="deleteDriver(${d.id})" class="text-slate-400 hover:text-red-600 transition"><i class="fas fa-trash p-1"></i></button>
                </div>
            </div>
            `;
        }).join('');
    }

    // Ações de Formulário
    document.getElementById('formDriver').onsubmit = async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(e.target));
        const res = await apiFetch('/api/drivers/save', { method: 'POST', body: JSON.stringify(data) });
        if(res.success) { closeModal(); loadDrivers(); showToast('Motorista salvo!'); }
        else showToast(res.error, 'error');
    };

    function editDriver(d) {
        document.getElementById('driverId').value = d.id;
        document.getElementById('driverName').value = d.name;
        document.getElementById('driverCnh').value = d.cnh;
        document.getElementById('driverValid').value = d.cnh_validity;
        document.getElementById('driverPhone').value = d.phone;
        document.getElementById('driverRfid').value = d.rfid;
        
        const isActive = d.status === 'active';
        document.getElementById('driverActiveCheck').checked = isActive;
        document.getElementById('driverStatus').value = d.status;
        
        document.getElementById('modalDriver').classList.remove('hidden');
    }

    function deleteDriver(id) {
        if(confirm('Remover este motorista?')) {
            apiFetch('/api/drivers/delete', { method: 'POST', body: JSON.stringify({id}) }).then(() => loadDrivers());
        }
    }

    function openModal() {
        document.getElementById('formDriver').reset();
        document.getElementById('driverId').value = '';
        document.getElementById('modalDriver').classList.remove('hidden');
    }
    function closeModal() { document.getElementById('modalDriver').classList.add('hidden'); }

    loadDrivers();
</script>