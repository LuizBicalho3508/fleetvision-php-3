<div class="p-6 space-y-6">
    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Frota Monitorada</h1>
            <p class="text-sm text-slate-500">Gestão de veículos e configurações.</p>
        </div>
        <button onclick="openModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-blue-200 transition flex items-center gap-2 transform hover:-translate-y-0.5">
            <i class="fas fa-plus-circle"></i> Novo Veículo
        </button>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Veículo / Placa</th>
                        <th class="px-6 py-4">Cliente Responsável</th>
                        <th class="px-6 py-4">Rastreador</th>
                        <th class="px-6 py-4 text-center">Configurações</th>
                        <th class="px-6 py-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody id="vehicleTable" class="divide-y divide-slate-100">
                    <tr><td colspan="5" class="p-12 text-center text-slate-400"><i class="fas fa-circle-notch fa-spin text-2xl mb-2"></i><br>Carregando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="vehicleModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white w-full max-w-3xl rounded-2xl shadow-2xl p-8 max-h-[95vh] overflow-y-auto scale-100 transition-transform duration-300">
        
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-slate-800" id="modalTitle">Adicionar Veículo</h3>
            <button onclick="closeModal()" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:bg-red-50 hover:text-red-500 transition flex items-center justify-center">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="formVehicle" class="space-y-6">
            <input type="hidden" name="id" id="inputId">
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Placa <span class="text-red-500">*</span></label>
                    <input type="text" name="plate" id="inputPlate" required placeholder="AAA-0000" class="w-full border border-slate-300 rounded-lg p-2.5 uppercase font-bold text-center tracking-widest focus:border-blue-500 outline-none bg-slate-50 focus:bg-white transition text-slate-700">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Marca</label>
                    <input type="text" name="brand" id="inputBrand" placeholder="Toyota" class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Modelo</label>
                    <input type="text" name="model" id="inputModel" placeholder="Corolla" class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 outline-none transition">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                 <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Cor</label>
                    <input type="text" name="color" id="inputColor" class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Ano</label>
                    <input type="text" name="year" id="inputYear" class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 outline-none transition">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Selecione o Ícone (Mapa)</label>
                <div id="iconSelector" class="grid grid-cols-6 sm:grid-cols-8 gap-2 max-h-32 overflow-y-auto p-2 border border-slate-200 rounded-xl bg-slate-50">
                    <div class="col-span-full text-center p-4 text-xs text-slate-400">Carregando ícones...</div>
                </div>
                <input type="hidden" name="icon" id="selectedIconInput" required>
            </div>

            <div id="linksSection" class="bg-blue-50 p-5 rounded-xl border border-blue-100">
                <h4 class="text-xs font-bold text-blue-600 mb-3 uppercase flex items-center gap-2"><i class="fas fa-link"></i> Vínculos Iniciais</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="relative group">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Cliente <span class="text-red-500">*</span></label>
                        <input type="text" id="searchCustomer" placeholder="Buscar cliente..." class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 outline-none">
                        <input type="hidden" name="customer_id" id="customerId">
                        <div id="customerResults" class="absolute w-full bg-white shadow-xl rounded-lg mt-1 max-h-40 overflow-y-auto hidden z-20 border border-slate-100"></div>
                    </div>

                    <div class="relative group">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Rastreador (Disponível) <span class="text-red-500">*</span></label>
                        <input type="text" id="searchTracker" placeholder="Buscar IMEI..." class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 outline-none">
                        <input type="hidden" name="stock_id" id="stockId">
                        <div id="trackerResults" class="absolute w-full bg-white shadow-xl rounded-lg mt-1 max-h-40 overflow-y-auto hidden z-20 border border-slate-100"></div>
                    </div>
                </div>
            </div>
            
            <div id="editWarning" class="hidden bg-orange-50 p-4 rounded-xl border border-orange-100 text-xs text-orange-700">
                <i class="fas fa-info-circle mr-1"></i> Para alterar o Cliente ou Rastreador, exclua este veículo e cadastre novamente.
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Velocidade Máx (km/h)</label>
                    <input type="number" name="speed_limit" id="inputSpeed" value="80" class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Média (km/L)</label>
                    <input type="number" step="0.1" name="km_per_liter" id="inputKml" value="10.0" class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 outline-none transition">
                </div>
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-blue-200 transition transform active:scale-95">
                <i class="fas fa-save mr-2"></i> Salvar Dados
            </button>
        </form>
    </div>
</div>

<script>
    let customersList = [];
    let trackersList = [];
    let iconsList = [];

    async function init() {
        loadVehicles();
        
        // Cache de dados
        const resCust = await apiFetch('/sys/customers');
        if(resCust.data) customersList = resCust.data; 

        const resTrack = await apiFetch('/sys/stock/available');
        if(resTrack.data) trackersList = resTrack.data;

        const resIcons = await apiFetch('/sys/icons');
        if(resIcons.data) {
            iconsList = resIcons.data;
            renderIconSelector();
        }
    }

    // Renderiza Seletor de Ícones
    function renderIconSelector() {
        const container = document.getElementById('iconSelector');
        if(iconsList.length === 0) {
            container.innerHTML = `<div class="col-span-full text-center text-xs text-slate-400">Sem ícones personalizados.</div>`;
            return;
        }
        container.innerHTML = iconsList.map(icon => `
            <div onclick="selectIcon('${icon.url}', this)" 
                 class="icon-option cursor-pointer p-2 rounded-lg border border-slate-200 hover:border-blue-500 hover:bg-blue-50 transition flex items-center justify-center h-12"
                 data-url="${icon.url}">
                <img src="${icon.url}" class="max-h-8 max-w-full object-contain">
            </div>
        `).join('');
    }

    window.selectIcon = (url, element) => {
        document.querySelectorAll('.icon-option').forEach(el => {
            el.classList.remove('border-blue-600', 'bg-blue-100', 'ring-2', 'ring-blue-200');
            el.classList.add('border-slate-200');
        });
        element.classList.remove('border-slate-200');
        element.classList.add('border-blue-600', 'bg-blue-100', 'ring-2', 'ring-blue-200');
        document.getElementById('selectedIconInput').value = url;
    }

    // LISTAR VEÍCULOS
    async function loadVehicles() {
        const res = await apiFetch('/sys/vehicles');
        const tbody = document.getElementById('vehicleTable');
        
        if(!res.data || res.data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="p-12 text-center text-slate-400">
                        <div class="flex flex-col items-center">
                            <i class="fas fa-car text-4xl mb-3 text-slate-200"></i>
                            <p>Nenhum veículo encontrado.</p>
                        </div>
                    </td>
                </tr>`;
            return;
        }

        tbody.innerHTML = res.data.map(v => `
            <tr class="hover:bg-slate-50 transition border-b border-slate-50 group">
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center p-1 shadow-sm">
                            <img src="${v.icon}" class="max-h-full max-w-full object-contain" onerror="this.src='https://cdn-icons-png.flaticon.com/512/741/741407.png'">
                        </div>
                        <div>
                            <div class="font-bold text-slate-800 text-lg tracking-tight">${v.plate}</div>
                            <div class="text-[10px] text-slate-400 uppercase font-bold">${v.brand || ''} ${v.model || ''}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-xs font-bold border border-blue-100">
                            ${v.customer_name ? v.customer_name.substring(0,1).toUpperCase() : '?'}
                        </div>
                        <span class="text-sm font-medium text-slate-700">${v.customer_name || 'Sem vínculo'}</span>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="text-xs font-mono text-slate-600 bg-slate-100 px-2 py-1 rounded inline-block border border-slate-200">
                        <i class="fas fa-satellite-dish text-slate-400 mr-1"></i> ${v.tracker_model || '?'}
                    </div>
                    <div class="text-[10px] text-slate-400 mt-1 ml-1">${v.tracker_imei || 'S/ Serial'}</div>
                </td>
                <td class="px-6 py-4 text-center">
                   <div class="flex flex-col gap-1 items-center">
                       <span class="inline-flex items-center gap-1 bg-white text-slate-600 px-2 py-0.5 rounded text-[10px] font-bold border border-slate-200 shadow-sm w-24 justify-center">
                            <i class="fas fa-tachometer-alt text-red-400"></i> ${v.speed_limit} km/h
                       </span>
                       <span class="inline-flex items-center gap-1 bg-white text-slate-600 px-2 py-0.5 rounded text-[10px] font-bold border border-slate-200 shadow-sm w-24 justify-center">
                            <i class="fas fa-gas-pump text-green-400"></i> ${v.km_per_liter} km/L
                       </span>
                   </div>
                </td>
                <td class="px-6 py-4 text-right">
                    <div class="flex justify-end gap-2 opacity-80 group-hover:opacity-100 transition-opacity">
                        <button onclick='editVehicle(${JSON.stringify(v)})' 
                                class="bg-white border border-slate-200 hover:border-blue-300 hover:text-blue-600 text-slate-500 px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm transition flex items-center gap-2">
                            <i class="fas fa-pen"></i> <span class="hidden md:inline">Editar</span>
                        </button>
                        
                        <button onclick="deleteVehicle(${v.id})" 
                                class="bg-white border border-slate-200 hover:border-red-300 hover:text-red-600 text-slate-500 px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm transition flex items-center gap-2">
                            <i class="fas fa-trash"></i> <span class="hidden md:inline">Excluir</span>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    // --- FUNÇÃO EDITAR ---
    window.editVehicle = (v) => {
        // Preenche campos
        document.getElementById('modalTitle').innerText = 'Editar Veículo';
        document.getElementById('inputId').value = v.id;
        document.getElementById('inputPlate').value = v.plate;
        document.getElementById('inputBrand').value = v.brand || '';
        document.getElementById('inputModel').value = v.model || '';
        document.getElementById('inputColor').value = v.color || '';
        document.getElementById('inputYear').value = v.year || '';
        document.getElementById('inputSpeed').value = v.speed_limit || 80;
        document.getElementById('inputKml').value = v.km_per_liter || 10;
        
        // Seleciona Ícone Visualmente
        const iconUrl = v.icon;
        document.getElementById('selectedIconInput').value = iconUrl;
        
        // Remove seleção antiga
        document.querySelectorAll('.icon-option').forEach(el => el.classList.remove('border-blue-600', 'bg-blue-100'));
        // Tenta achar o ícone na lista para marcar
        const targetOption = document.querySelector(`.icon-option[data-url="${iconUrl}"]`);
        if(targetOption) {
            targetOption.classList.remove('border-slate-200');
            targetOption.classList.add('border-blue-600', 'bg-blue-100');
        }

        // Esconde vínculos (não editáveis aqui) e mostra aviso
        document.getElementById('linksSection').classList.add('hidden');
        document.getElementById('editWarning').classList.remove('hidden');
        
        // Remove obrigatoriedade dos inputs escondidos
        document.getElementById('customerId').removeAttribute('required');
        document.getElementById('stockId').removeAttribute('required');

        document.getElementById('vehicleModal').classList.remove('hidden');
    }

    // --- SUBMIT ---
    document.getElementById('formVehicle').onsubmit = async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Salvando...'; btn.disabled = true;

        const data = Object.fromEntries(new FormData(e.target));
        const isUpdate = !!data.id;
        
        // Define Rota
        const endpoint = isUpdate ? '/sys/vehicles/update' : '/sys/vehicles';

        try {
            const res = await apiFetch(endpoint, { method: 'POST', body: JSON.stringify(data) });
            if(res.success) {
                showToast(isUpdate ? 'Atualizado com sucesso!' : 'Cadastrado com sucesso!');
                closeModal();
                init();
            } else {
                showToast(res.error || 'Erro', 'error');
            }
        } catch(err) {
            showToast('Erro de conexão', 'error');
        } finally {
            btn.innerHTML = originalHtml; btn.disabled = false;
        }
    };

    // Autocomplete (Mantido igual)
    const inpCust = document.getElementById('searchCustomer');
    const listCust = document.getElementById('customerResults');
    inpCust.addEventListener('input', (e) => {
        const term = e.target.value.toLowerCase();
        if(term.length < 1) { listCust.classList.add('hidden'); return; }
        const filtered = customersList.filter(c => c.name.toLowerCase().includes(term));
        listCust.innerHTML = filtered.map(c => `<div onclick="setCustomer(${c.id}, '${c.name}')" class="p-2 hover:bg-blue-50 cursor-pointer text-sm border-b">${c.name}</div>`).join('');
        listCust.classList.remove('hidden');
    });
    window.setCustomer = (id, name) => { document.getElementById('customerId').value = id; inpCust.value = name; listCust.classList.add('hidden'); }

    const inpTrack = document.getElementById('searchTracker');
    const listTrack = document.getElementById('trackerResults');
    inpTrack.addEventListener('input', (e) => {
        const term = e.target.value.toLowerCase();
        if(term.length < 1) { listTrack.classList.add('hidden'); return; }
        const filtered = trackersList.filter(t => t.imei.includes(term));
        listTrack.innerHTML = filtered.map(t => `<div onclick="setTracker(${t.id}, '${t.imei}')" class="p-2 hover:bg-blue-50 cursor-pointer text-sm border-b">${t.model} - ${t.imei}</div>`).join('');
        listTrack.classList.remove('hidden');
    });
    window.setTracker = (id, label) => { document.getElementById('stockId').value = id; inpTrack.value = label; listTrack.classList.add('hidden'); }

    // Helpers
    function openModal() {
        document.getElementById('formVehicle').reset();
        document.getElementById('inputId').value = '';
        document.getElementById('modalTitle').innerText = 'Adicionar Veículo';
        
        // Mostra vínculos
        document.getElementById('linksSection').classList.remove('hidden');
        document.getElementById('editWarning').classList.add('hidden');
        
        document.getElementById('vehicleModal').classList.remove('hidden');
    }
    function closeModal() { document.getElementById('vehicleModal').classList.add('hidden'); }
    window.deleteVehicle = async (id) => {
        if(!confirm('Excluir? O rastreador será liberado.')) return;
        const res = await apiFetch('/sys/vehicles/delete', { method: 'POST', body: JSON.stringify({id}) });
        if(res.success) { showToast('Removido'); init(); }
    }

    init();
</script>