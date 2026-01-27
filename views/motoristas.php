<div class="p-6 space-y-6 h-full flex flex-col">
    <div class="flex justify-between items-center shrink-0">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Gestão de Motoristas</h1>
            <p class="text-sm text-slate-500">Cadastre condutores, associe identyficadores (RFID/iButton) e gerencie CNHs.</p>
        </div>
        <button onclick="openModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-blue-200 transition flex items-center gap-2 transform active:scale-95">
            <i class="fas fa-user-plus"></i> Novo Motorista
        </button>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 flex-1 overflow-hidden flex flex-col">
        <div class="overflow-x-auto custom-scrollbar flex-1">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-xs sticky top-0 z-10 shadow-sm">
                    <tr>
                        <th class="px-6 py-4">Nome / Contato</th>
                        <th class="px-6 py-4">Identificação (RFID/CPF)</th>
                        <th class="px-6 py-4">Dados CNH</th>
                        <th class="px-6 py-4">Cliente Vinculado</th>
                        <th class="px-6 py-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody id="driversTable" class="divide-y divide-slate-50">
                    <tr><td colspan="5" class="p-12 text-center text-slate-400"><i class="fas fa-circle-notch fa-spin text-2xl"></i><br>Carregando dados...</td></tr>
                </tbody>
            </table>
        </div>
        <div class="bg-slate-50 border-t border-slate-200 p-3 text-xs text-slate-500 text-right">
            Total de registros: <span id="totalDrivers" class="font-bold">0</span>
        </div>
    </div>
</div>

<div id="driverModal" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>
    
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden scale-100 transition-transform">
        
        <div class="flex justify-between items-center px-8 py-5 border-b border-slate-100 bg-slate-50/50">
            <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <i class="fas fa-id-card-alt text-blue-600"></i> <span id="modalTitle">Ficha do Motorista</span>
            </h3>
            <button onclick="closeModal()" class="w-8 h-8 rounded-full bg-white border border-slate-200 hover:bg-red-50 hover:text-red-500 hover:border-red-200 transition flex items-center justify-center">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="max-h-[80vh] overflow-y-auto custom-scrollbar p-8">
            <form id="formDriver" class="space-y-6">
                <input type="hidden" name="id" id="inputId">
                
                <div class="space-y-4">
                    <h4 class="text-xs font-bold text-blue-600 uppercase tracking-wider mb-2 flex items-center gap-2 border-b border-blue-100 pb-2">
                        Dados Pessoais
                    </h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nome Completo <span class="text-red-500">*</span></label>
                            <input type="text" name="name" id="inputName" required class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 outline-none transition shadow-sm" placeholder="Ex: João da Silva">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Identificador (RFID/CPF) <span class="text-red-500">*</span></label>
                            <input type="text" name="document" id="inputDocument" required class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 outline-none font-mono transition shadow-sm" placeholder="Ex: 12345678900">
                            <p class="text-[10px] text-slate-400 mt-1">Código único para vincular ao rastreador.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Data de Nascimento</label>
                            <input type="date" name="birth_date" id="inputBirth" class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 outline-none bg-white transition shadow-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Telefone / Celular</label>
                            <input type="text" name="phone" id="inputPhone" class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 outline-none transition shadow-sm" placeholder="(00) 00000-0000">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Email</label>
                            <input type="email" name="email" id="inputEmail" class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 outline-none transition shadow-sm" placeholder="nome@exemplo.com">
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                     <h4 class="text-xs font-bold text-blue-600 uppercase tracking-wider mb-2 flex items-center gap-2 border-b border-blue-100 pb-2">
                        Vínculo
                    </h4>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Cliente Vinculado</label>
                        <select name="customer_id" id="inputCustomer" class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 outline-none bg-white transition shadow-sm cursor-pointer">
                            <option value="">-- Sem Vínculo (Frota Própria) --</option>
                            </select>
                        <p class="text-[10px] text-slate-400 mt-1">Selecione se este motorista atende um cliente específico.</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 flex items-center gap-2 border-b border-slate-200 pb-2">
                        <i class="fas fa-car"></i> Dados da Habilitação (CNH)
                    </h4>
                    
                    <div class="grid grid-cols-3 gap-4">
                        <div class="col-span-1">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nº Registro</label>
                            <input type="text" name="cnh_number" id="inputCnhNum" class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 outline-none transition shadow-sm">
                        </div>
                        <div class="col-span-1">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Categoria</label>
                            <select name="cnh_category" id="inputCnhCat" class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 outline-none bg-white transition shadow-sm cursor-pointer">
                                <option value="">--</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="AB">AB</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                                <option value="E">E</option>
                                <option value="AD">AD</option>
                                <option value="AE">AE</option>
                            </select>
                        </div>
                        <div class="col-span-1">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Validade</label>
                            <input type="date" name="cnh_expiration" id="inputCnhExp" class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 outline-none transition shadow-sm">
                        </div>
                    </div>
                </div>

                <div class="pt-6 flex gap-3 border-t border-slate-100 mt-4">
                    <button type="button" onclick="closeModal()" class="flex-1 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold py-3 rounded-xl transition">
                        Cancelar
                    </button>
                    <button type="submit" class="flex-[2] bg-slate-800 hover:bg-slate-900 text-white font-bold py-3 rounded-xl shadow-lg shadow-slate-300 transition transform active:scale-[0.99] flex justify-center items-center gap-2">
                        <i class="fas fa-save"></i> Salvar Motorista
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let customersList = [];

    // INICIALIZAÇÃO
    async function init() {
        // 1. Carrega lista de clientes para o Select
        try {
            const resCust = await apiFetch('/sys/customers');
            if(resCust.data) {
                customersList = resCust.data;
                const select = document.getElementById('inputCustomer');
                select.innerHTML = '<option value="">-- Sem Vínculo (Frota Própria) --</option>' + 
                    customersList.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
            }
        } catch(e) { console.error('Erro ao carregar clientes:', e); }

        // 2. Carrega lista de motoristas
        loadDrivers();
    }

    // CARREGAR TABELA
    async function loadDrivers() {
        const tbody = document.getElementById('driversTable');
        const counter = document.getElementById('totalDrivers');
        
        try {
            const res = await apiFetch('/sys/drivers'); // Chama o DriverController local
            
            if(!res.data || res.data.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="p-12 text-center text-slate-400 flex flex-col items-center">
                            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-3">
                                <i class="fas fa-users text-3xl text-slate-200"></i>
                            </div>
                            <p>Nenhum motorista cadastrado.</p>
                            <button onclick="openModal()" class="mt-3 text-blue-600 font-bold text-xs hover:underline">Cadastrar o primeiro</button>
                        </td>
                    </tr>`;
                counter.innerText = '0';
                return;
            }

            counter.innerText = res.data.length;

            tbody.innerHTML = res.data.map(d => {
                // Lógica Visual da CNH
                let cnhBadge = '<span class="text-slate-300 text-xs italic">Não informado</span>';
                if(d.cnh_expiration) {
                    const expDate = new Date(d.cnh_expiration);
                    const today = new Date();
                    const isExpired = expDate < today;
                    
                    const colorClass = isExpired 
                        ? 'bg-red-50 text-red-600 border-red-100' 
                        : 'bg-green-50 text-green-600 border-green-100';
                    
                    const icon = isExpired ? 'fa-exclamation-circle' : 'fa-check-circle';
                    const text = isExpired ? 'Vencida' : 'Válida';
                    
                    // Formata data DD/MM/AAAA
                    const dateStr = expDate.toLocaleDateString('pt-BR');
                    
                    cnhBadge = `
                        <div class="flex flex-col items-start gap-1">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold border ${colorClass}">
                                <i class="fas ${icon}"></i> ${text}
                            </span>
                            <span class="text-[10px] text-slate-400">Vence: ${dateStr}</span>
                        </div>`;
                }

                // Badge de Cliente
                const clientBadge = d.customer_name 
                    ? `<span class="inline-flex items-center gap-1.5 bg-blue-50 text-blue-700 px-2.5 py-1 rounded-full text-xs font-bold border border-blue-100">
                         <i class="fas fa-user-tie text-[10px] opacity-70"></i> ${d.customer_name}
                       </span>` 
                    : '<span class="text-slate-400 text-xs italic flex items-center gap-1"><i class="fas fa-building text-slate-200"></i> Frota Própria</span>';

                return `
                <tr class="hover:bg-slate-50 transition border-b border-slate-50 last:border-0 group">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 font-bold text-xs">
                                ${d.name.charAt(0).toUpperCase()}
                            </div>
                            <div>
                                <div class="font-bold text-slate-700">${d.name}</div>
                                <div class="text-[11px] text-slate-400 flex items-center gap-2 mt-0.5">
                                    ${d.phone ? `<span><i class="fas fa-phone-alt mr-1"></i>${d.phone}</span>` : ''}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col">
                            <span class="text-xs font-bold text-slate-600 font-mono bg-slate-100 px-2 py-0.5 rounded w-fit border border-slate-200">
                                ${d.document}
                            </span>
                            <span class="text-[10px] text-slate-400 mt-1">ID Traccar</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-xs text-slate-600 font-bold mb-1 flex items-center gap-1">
                            <i class="fas fa-id-card text-slate-300"></i> 
                            ${d.cnh_number || '-'} 
                            ${d.cnh_category ? `<span class="bg-slate-800 text-white px-1.5 rounded text-[10px] ml-1">${d.cnh_category}</span>` : ''}
                        </div>
                        ${cnhBadge}
                    </td>
                    <td class="px-6 py-4">
                        ${clientBadge}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex justify-end gap-2 opacity-80 group-hover:opacity-100 transition-opacity">
                            <button onclick='editDriver(${JSON.stringify(d)})' class="h-8 w-8 rounded-lg bg-white border border-slate-200 hover:border-blue-400 hover:text-blue-600 text-slate-400 transition shadow-sm flex items-center justify-center" title="Editar">
                                <i class="fas fa-pen text-xs"></i>
                            </button>
                            <button onclick="deleteDriver(${d.id})" class="h-8 w-8 rounded-lg bg-white border border-slate-200 hover:border-red-400 hover:text-red-600 text-slate-400 transition shadow-sm flex items-center justify-center" title="Excluir">
                                <i class="fas fa-trash text-xs"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `}).join('');

        } catch(e) {
            console.error(e);
            tbody.innerHTML = `<tr><td colspan="5" class="p-8 text-center text-red-500 font-bold">Erro ao carregar lista de motoristas.</td></tr>`;
        }
    }

    // MODAL CONTROL
    function openModal() {
        document.getElementById('formDriver').reset();
        document.getElementById('inputId').value = '';
        document.getElementById('modalTitle').innerText = 'Novo Motorista';
        document.getElementById('driverModal').classList.remove('hidden');
    }
    function closeModal() {
        document.getElementById('driverModal').classList.add('hidden');
    }

    // EDITAR
    window.editDriver = (d) => {
        document.getElementById('inputId').value = d.id;
        document.getElementById('inputName').value = d.name;
        document.getElementById('inputDocument').value = d.document;
        document.getElementById('inputCustomer').value = d.customer_id || '';
        
        // Dados Pessoais
        document.getElementById('inputBirth').value = d.birth_date || ''; // CORRIGIDO
        document.getElementById('inputPhone').value = d.phone || '';
        document.getElementById('inputEmail').value = d.email || '';
        
        // CNH
        document.getElementById('inputCnhNum').value = d.cnh_number || '';
        document.getElementById('inputCnhCat').value = d.cnh_category || '';
        document.getElementById('inputCnhExp').value = d.cnh_expiration || '';

        document.getElementById('modalTitle').innerText = 'Editar Motorista';
        document.getElementById('driverModal').classList.remove('hidden');
    }

    // SALVAR
    document.getElementById('formDriver').onsubmit = async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const oldHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Salvando...'; 
        btn.disabled = true;

        // Captura dados do form
        const data = Object.fromEntries(new FormData(e.target));
        
        // Normaliza campos vazios para null (opcional, o PHP já trata)
        if(!data.customer_id) data.customer_id = null;
        
        try {
            const res = await apiFetch('/sys/drivers/save', { 
                method: 'POST', 
                body: JSON.stringify(data) 
            });

            if(res.success) {
                showToast('Motorista salvo com sucesso!');
                closeModal();
                loadDrivers();
            } else {
                showToast(res.error || 'Erro ao salvar', 'error');
            }
        } catch(err) {
            showToast('Erro de conexão com o servidor', 'error');
        } finally {
            btn.innerHTML = oldHtml; 
            btn.disabled = false;
        }
    };

    // EXCLUIR
    window.deleteDriver = async (id) => {
        if(!confirm('Tem certeza que deseja excluir este motorista?\nEssa ação removerá o vínculo com o rastreador.')) return;
        
        try {
            const res = await apiFetch('/sys/drivers/delete', { 
                method: 'POST', 
                body: JSON.stringify({id}) 
            });
            
            if(res.success) { 
                showToast('Motorista removido.'); 
                loadDrivers(); 
            } else { 
                showToast(res.error || 'Erro ao remover', 'error'); 
            }
        } catch(e) {
            showToast('Erro de conexão', 'error');
        }
    }

    // Inicia
    init();
</script>