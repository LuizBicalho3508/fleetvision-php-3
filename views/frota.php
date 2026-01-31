<div class="p-6 space-y-6">
    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Gestão de Frota</h2>
            <p class="text-sm text-slate-500">Gerencie todos os veículos ativos e seus rastreadores.</p>
        </div>
        <button onclick="openModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2 shadow-sm">
            <i class="fas fa-plus"></i> Novo Veículo
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-slate-700 font-semibold uppercase text-xs">
                    <tr>
                        <th class="px-6 py-4">Veículo / Placa</th>
                        <th class="px-6 py-4">Cliente</th>
                        <th class="px-6 py-4">Rastreador (IMEI)</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody id="vehicleTableBody" class="divide-y divide-slate-100">
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-slate-400">
                            <i class="fas fa-circle-notch fa-spin text-2xl mb-2"></i><br>
                            Carregando frota...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="vehicleModal" class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl p-6 transform transition-all scale-100">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-slate-800" id="modalTitle">Novo Veículo</h3>
            <button onclick="closeModal()" class="text-slate-400 hover:text-red-500 transition"><i class="fas fa-times text-xl"></i></button>
        </div>
        
        <form id="vehicleForm" class="space-y-4">
            <input type="hidden" name="id" id="vehicleId">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Placa *</label>
                    <input type="text" name="plate" id="vehiclePlate" required class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none focus:border-blue-500 uppercase" placeholder="ABC-1234">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Cliente *</label>
                    <select name="customer_id" id="vehicleCustomer" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none focus:border-blue-500 bg-white">
                        <option value="">Carregando...</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Marca</label>
                    <input type="text" name="brand" id="vehicleBrand" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none focus:border-blue-500" placeholder="Ex: Toyota">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Modelo</label>
                    <input type="text" name="model" id="vehicleModel" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none focus:border-blue-500" placeholder="Ex: Corolla">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Cor</label>
                    <input type="text" name="color" id="vehicleColor" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none focus:border-blue-500" placeholder="Ex: Prata">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Ano</label>
                    <input type="text" name="year" id="vehicleYear" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none focus:border-blue-500" placeholder="Ex: 2024">
                </div>
            </div>

            <div class="bg-slate-50 p-4 rounded-lg border border-slate-100">
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Rastreador Vinculado *</label>
                <select name="stock_id" id="vehicleStock" required class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none focus:border-blue-500 bg-white">
                    <option value="">Selecione um equipamento disponível...</option>
                </select>
                <p class="text-[10px] text-slate-400 mt-1">Apenas rastreadores com status 'available' aparecem aqui.</p>
            </div>

            <div class="pt-4 flex justify-end gap-3">
                <button type="button" onclick="closeModal()" class="px-4 py-2 text-slate-500 hover:bg-slate-100 rounded-lg font-medium transition">Cancelar</button>
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium shadow-lg transition">Salvar Veículo</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        loadVehicles();
        loadSelects();
    });

    // --- CARREGAR VEÍCULOS (Com tratamento de erro) ---
    async function loadVehicles() {
        const tbody = document.getElementById('vehicleTableBody');
        
        try {
            console.log("Iniciando carregamento da frota...");
            const res = await apiFetch('/sys/vehicles');
            
            console.log("Resposta API Veículos:", res);

            if (!res.data || res.data.length === 0) {
                let msg = 'Nenhum veículo encontrado.';
                if(res.debug_message) msg += `<br><span class="text-xs text-red-400">Debug: ${res.debug_message}</span>`;
                
                tbody.innerHTML = `<tr><td colspan="5" class="px-6 py-10 text-center text-slate-500">${msg}</td></tr>`;
                return;
            }

            tbody.innerHTML = '';
            
            res.data.forEach(v => {
                // TRATAMENTO DE NULOS (Blindagem)
                const plate = v.plate || 'SEM PLACA';
                const model = v.model || v.brand || 'Veículo';
                const customerName = v.customer_name || '<span class="text-red-300 italic">Sem Cliente</span>';
                const trackerModel = v.tracker_model || '-';
                const trackerImei = v.identifier || v.tracker_imei || '<span class="text-orange-300">Sem IMEI</span>';
                
                const html = `
                    <tr class="hover:bg-slate-50 transition border-b border-slate-50 last:border-0">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs shadow-sm">
                                    <i class="fas fa-car"></i>
                                </div>
                                <div>
                                    <div class="font-bold text-slate-800">${plate}</div>
                                    <div class="text-xs text-slate-500">${model}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm font-medium text-slate-700">${customerName}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-xs font-mono bg-slate-100 px-2 py-1 rounded inline-block border border-slate-200">
                                ${trackerImei}
                            </div>
                            <div class="text-[10px] text-slate-400 mt-1">${trackerModel}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold ${v.status === 'active' ? 'bg-green-50 text-green-700 border border-green-100' : 'bg-red-50 text-red-700 border border-red-100'}">
                                <span class="w-1.5 h-1.5 rounded-full ${v.status === 'active' ? 'bg-green-500' : 'bg-red-500'}"></span>
                                ${v.status === 'active' ? 'Ativo' : 'Inativo'}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button onclick='editVehicle(${JSON.stringify(v)})' class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="deleteVehicle(${v.id})" class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Excluir">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                tbody.insertAdjacentHTML('beforeend', html);
            });

        } catch (error) {
            console.error("Erro Fatal Frota:", error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="px-6 py-10 text-center text-red-500">
                        <i class="fas fa-exclamation-triangle text-3xl mb-2"></i><br>
                        <strong>Erro ao carregar dados:</strong> ${error.message}<br>
                        <span class="text-xs text-slate-400">Verifique o console (F12) para detalhes técnicos.</span>
                    </td>
                </tr>
            `;
            showToast('Erro ao carregar frota.', 'error');
        }
    }

    // --- CARREGAR SELECTS (Clientes e Rastreadores) ---
    async function loadSelects() {
        try {
            // Clientes
            const resCust = await apiFetch('/sys/customers');
            const selCust = document.getElementById('vehicleCustomer');
            selCust.innerHTML = '<option value="">Selecione...</option>';
            if(resCust.data) {
                resCust.data.forEach(c => {
                    selCust.innerHTML += `<option value="${c.id}">${c.name}</option>`;
                });
            }

            // Rastreadores (Estoque)
            const resStock = await apiFetch('/sys/vehicles/trackers');
            const selStock = document.getElementById('vehicleStock');
            selStock.innerHTML = '<option value="">Selecione...</option>';
            if(resStock.data) {
                resStock.data.forEach(s => {
                    selStock.innerHTML += `<option value="${s.id}">${s.model} - ${s.imei}</option>`;
                });
            }
        } catch(e) { console.error("Erro Selects:", e); }
    }

    // --- MODAL & AÇÕES ---
    function openModal() {
        document.getElementById('vehicleForm').reset();
        document.getElementById('vehicleId').value = '';
        document.getElementById('modalTitle').innerText = 'Novo Veículo';
        document.getElementById('vehicleModal').classList.remove('hidden');
        // Ao abrir novo, precisamos recarregar rastreadores disponíveis
        loadSelects(); 
    }

    function closeModal() {
        document.getElementById('vehicleModal').classList.add('hidden');
    }

    function editVehicle(v) {
        openModal();
        document.getElementById('modalTitle').innerText = 'Editar Veículo';
        document.getElementById('vehicleId').value = v.id;
        document.getElementById('vehiclePlate').value = v.plate;
        document.getElementById('vehicleBrand').value = v.brand;
        document.getElementById('vehicleModel').value = v.model;
        document.getElementById('vehicleColor').value = v.color;
        document.getElementById('vehicleYear').value = v.year;
        
        // Define cliente
        if(v.customer_id) document.getElementById('vehicleCustomer').value = v.customer_id;
        
        // Rastreador: na edição, o atual não aparece como "disponível", então é complexo.
        // Simplificação: bloqueia edição de rastreador ou mostra aviso
        const selStock = document.getElementById('vehicleStock');
        // Adiciona opção temporária do atual para não ficar vazio
        if(v.stock_id) {
            const opt = document.createElement('option');
            opt.value = v.stock_id;
            opt.text = `${v.tracker_model || 'Atual'} - ${v.identifier || v.tracker_imei}`;
            opt.selected = true;
            selStock.add(opt);
        }
    }

    document.getElementById('vehicleForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(e.target));
        const url = data.id ? '/sys/vehicles/update' : '/sys/vehicles/save';
        
        try {
            const res = await apiFetch(url, { method: 'POST', body: JSON.stringify(data) });
            showToast('Veículo salvo com sucesso!');
            closeModal();
            loadVehicles();
        } catch (error) {
            showToast(error.message, 'error');
        }
    });

    async function deleteVehicle(id) {
        if(!confirm('Tem certeza? O rastreador voltará para o estoque.')) return;
        try {
            await apiFetch('/sys/vehicles/delete', { method: 'POST', body: JSON.stringify({ id }) });
            showToast('Veículo excluído.');
            loadVehicles();
        } catch (error) {
            showToast(error.message, 'error');
        }
    }
</script>