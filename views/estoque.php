<div class="p-6 space-y-6">

    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Estoque de Rastreadores</h1>
            <p class="text-sm text-slate-500">Gestão completa de equipamentos e chips.</p>
        </div>
        <button onclick="openModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-blue-200 transition flex items-center gap-2 transform hover:-translate-y-0.5">
            <i class="fas fa-plus"></i>
            <span>Novo Dispositivo</span>
        </button>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="relative group">
            <i class="fas fa-search absolute left-4 top-3.5 text-slate-400 group-focus-within:text-blue-500 transition"></i>
            <input type="text" id="searchInput" placeholder="Buscar por Serial (IMEI), Modelo ou ICCID..." class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-50 outline-none transition font-medium text-slate-700">
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase font-bold tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Equipamento</th>
                        <th class="px-6 py-4">Serial (IMEI)</th>
                        <th class="px-6 py-4">Conectividade</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody id="stockTable" class="divide-y divide-slate-100">
                    <tr><td colspan="5" class="p-10 text-center text-slate-400"><i class="fas fa-circle-notch fa-spin text-2xl mb-2"></i><br>Carregando estoque...</td></tr>
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100 bg-slate-50 flex justify-between items-center">
            <span class="text-xs text-slate-400" id="totalCount">0 registros</span>
        </div>
    </div>
</div>

<div id="stockModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white w-full max-w-2xl rounded-2xl shadow-2xl p-8 scale-100 transition-transform duration-300">
        
        <div class="flex justify-between items-center mb-6">
            <div>
                <h3 class="text-xl font-bold text-slate-800" id="modalTitle">Novo Rastreador</h3>
                <p class="text-xs text-slate-500 mt-1">Preencha os dados do equipamento.</p>
            </div>
            <button onclick="closeModal()" class="w-8 h-8 rounded-full bg-slate-100 text-slate-500 hover:bg-red-50 hover:text-red-500 transition flex items-center justify-center">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="formStock" class="space-y-5">
            <input type="hidden" name="id" id="inputId">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Marca</label>
                    <input type="text" name="brand" id="inputBrand" placeholder="Ex: Suntech" class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Modelo <span class="text-red-500">*</span></label>
                    <input type="text" name="model" id="inputModel" placeholder="Ex: ST310U" required class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 outline-none transition font-bold text-slate-700">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">IMEI (Serial) <span class="text-red-500">*</span></label>
                <div class="relative">
                    <i class="fas fa-barcode absolute left-3 top-3 text-slate-400"></i>
                    <input type="text" name="imei" id="inputImei" required class="w-full pl-10 border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 outline-none font-mono tracking-wider text-slate-700 font-bold bg-slate-50 focus:bg-white transition" placeholder="Apenas números">
                </div>
                <p class="text-[10px] text-slate-400 mt-1 ml-1">Este serial será sincronizado automaticamente com o Traccar.</p>
            </div>

            <div class="bg-slate-50 p-5 rounded-xl border border-slate-200/60 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-2 opacity-5">
                    <i class="fas fa-sim-card text-6xl"></i>
                </div>
                <h4 class="text-xs font-bold text-blue-600 mb-4 flex items-center gap-2">
                    <i class="fas fa-sim-card"></i> DADOS DO CHIP (SIM CARD)
                </h4>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Operadora</label>
                        <select name="operator" id="inputOperator" class="w-full border border-slate-300 rounded-lg p-2.5 bg-white focus:border-blue-500 outline-none cursor-pointer">
                            <option value="">Selecione...</option>
                            <option value="Vivo">Vivo</option>
                            <option value="Claro">Claro</option>
                            <option value="Tim">Tim</option>
                            <option value="Algar">Algar</option>
                            <option value="M2M">M2M Multi</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Linha (Tel)</label>
                        <input type="text" name="line_number" id="inputLine" placeholder="DD + Número" class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">ICCID (Serial do Chip)</label>
                    <input type="text" name="iccid" id="inputIccid" class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 outline-none font-mono text-slate-600">
                </div>
            </div>

            <div class="pt-2 flex gap-3">
                <button type="button" onclick="closeModal()" class="px-6 py-3 text-slate-500 font-bold hover:bg-slate-100 rounded-xl transition">Cancelar</button>
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl shadow-lg shadow-blue-200 transition transform active:scale-95">
                    <i class="fas fa-check mr-2"></i> Salvar Dados
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let stockList = [];

    async function loadStock() {
        const res = await apiFetch('/sys/stock');
        if (res.data) {
            stockList = res.data;
            renderTable(stockList);
        } else {
            document.getElementById('stockTable').innerHTML = '<tr><td colspan="5" class="p-8 text-center text-red-400">Erro ao carregar dados.</td></tr>';
        }
    }

    function renderTable(list) {
        const tbody = document.getElementById('stockTable');
        document.getElementById('totalCount').innerText = `${list.length} registros`;

        if (list.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="p-12 text-center text-slate-400">
                        <div class="flex flex-col items-center">
                            <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-4 text-slate-300 text-2xl">
                                <i class="fas fa-box-open"></i>
                            </div>
                            <p>Nenhum rastreador no estoque.</p>
                            <button onclick="openModal()" class="mt-4 text-blue-600 font-bold text-sm hover:underline">Cadastrar o primeiro</button>
                        </div>
                    </td>
                </tr>`;
            return;
        }

        tbody.innerHTML = list.map(item => `
            <tr class="hover:bg-slate-50 transition border-b border-slate-50 group">
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-lg shadow-sm">
                            <i class="fas fa-satellite-dish"></i>
                        </div>
                        <div>
                            <div class="font-bold text-slate-700">${item.model}</div>
                            <div class="text-xs text-slate-400 font-medium">${item.brand || 'Genérico'}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="font-mono text-sm font-bold text-slate-700 tracking-wide bg-slate-100 inline-block px-2 py-1 rounded border border-slate-200">
                        <i class="fas fa-barcode text-slate-400 mr-1 text-xs"></i>
                        ${item.imei || 'SEM SERIAL'}
                    </div>
                </td>
                <td class="px-6 py-4">
                    ${item.operator ? 
                        `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-white border border-slate-200 shadow-sm text-xs font-bold text-slate-700">
                            <div class="w-2 h-2 rounded-full bg-green-500"></div> ${item.operator}
                         </span>` : 
                        '<span class="text-slate-300 text-xs">-</span>'}
                    <div class="text-xs text-slate-400 mt-1 font-mono tracking-tight" title="ICCID">${item.iccid || ''}</div>
                </td>
                <td class="px-6 py-4">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold ${item.status === 'available' ? 'bg-emerald-100 text-emerald-700' : 'bg-indigo-100 text-indigo-700'}">
                        <span class="w-1.5 h-1.5 rounded-full ${item.status === 'available' ? 'bg-emerald-500' : 'bg-indigo-500'}"></span>
                        ${item.status === 'available' ? 'Disponível' : 'Em Uso'}
                    </span>
                </td>
                <td class="px-6 py-4 text-right">
                    <div class="flex justify-end gap-2 opacity-80 group-hover:opacity-100 transition-opacity">
                        <button onclick='editDevice(${JSON.stringify(item)})' 
                                class="bg-white border border-slate-200 hover:border-blue-300 hover:text-blue-600 text-slate-500 px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm transition flex items-center gap-2">
                            <i class="fas fa-pen"></i> <span class="hidden md:inline">Editar</span>
                        </button>
                        <button onclick="deleteDevice(${item.id})" 
                                class="bg-white border border-slate-200 hover:border-red-300 hover:text-red-600 text-slate-500 px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm transition flex items-center gap-2">
                            <i class="fas fa-trash"></i> <span class="hidden md:inline">Excluir</span>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    // --- SALVAR (Create ou Update) ---
    document.getElementById('formStock').onsubmit = async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Salvando...';
        btn.disabled = true;

        const data = Object.fromEntries(new FormData(e.target));
        const hasId = !!data.id;
        
        // Define Rota: POST (novo) ou POST update (edição)
        // Nota: Seu router pode não ter PUT configurado, então usamos POST para tudo ou uma rota específica
        const endpoint = hasId ? '/sys/stock/update' : '/sys/stock'; 
        
        // IMPORTANTE: Adicione a rota '/sys/stock/update' no index.php apontando para StockController::update

        try {
            const res = await apiFetch(endpoint, {
                method: 'POST',
                body: JSON.stringify(data)
            });

            if (res.success) {
                showToast(hasId ? 'Atualizado com sucesso!' : 'Cadastrado com sucesso!');
                closeModal();
                loadStock();
            } else {
                showToast(res.error || 'Erro ao salvar', 'error');
            }
        } catch (err) {
            console.error(err);
            showToast('Erro de conexão', 'error');
        } finally {
            btn.innerHTML = original;
            btn.disabled = false;
        }
    };

    // --- DELETAR ---
    async function deleteDevice(id) {
        if(!confirm('Tem certeza? O dispositivo será removido do sistema e do Traccar.')) return;
        
        const res = await apiFetch('/sys/stock/delete', {
            method: 'POST',
            body: JSON.stringify({ id })
        });
        
        if(res.success) { showToast('Dispositivo removido.'); loadStock(); }
        else { showToast(res.error || 'Erro ao remover', 'error'); }
    }

    // --- EDITAR ---
    window.editDevice = (item) => {
        document.getElementById('modalTitle').innerText = 'Editar Rastreador';
        document.getElementById('inputId').value = item.id;
        document.getElementById('inputBrand').value = item.brand || '';
        document.getElementById('inputModel').value = item.model || '';
        document.getElementById('inputImei').value = item.imei || '';
        document.getElementById('inputOperator').value = item.operator || '';
        document.getElementById('inputLine').value = item.line_number || '';
        document.getElementById('inputIccid').value = item.iccid || '';
        
        document.getElementById('stockModal').classList.remove('hidden');
    }

    // Filtro
    document.getElementById('searchInput').addEventListener('input', (e) => {
        const term = e.target.value.toLowerCase();
        const filtered = stockList.filter(i => 
            (i.imei && i.imei.includes(term)) || 
            (i.iccid && i.iccid.includes(term)) ||
            (i.model && i.model.toLowerCase().includes(term))
        );
        renderTable(filtered);
    });

    function openModal() {
        document.getElementById('formStock').reset();
        document.getElementById('inputId').value = '';
        document.getElementById('modalTitle').innerText = 'Novo Rastreador';
        document.getElementById('stockModal').classList.remove('hidden');
    }
    
    function closeModal() {
        document.getElementById('stockModal').classList.add('hidden');
    }

    loadStock();
</script>