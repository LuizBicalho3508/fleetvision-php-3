<div class="p-6 h-[calc(100vh-64px)] flex flex-col">
    <div class="flex justify-between items-center mb-6 shrink-0">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">CRM de Vendas</h1>
            <p class="text-sm text-gray-500">Gerencie leads e negociações do seu SaaS.</p>
        </div>
        <button onclick="openModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded shadow font-bold transition flex items-center gap-2">
            <i class="fas fa-user-plus"></i> Novo Lead
        </button>
    </div>

    <div class="flex-1 overflow-x-auto overflow-y-hidden pb-4">
        <div class="flex gap-6 h-full min-w-[1000px]">
            
            <div class="flex-1 flex flex-col bg-gray-100 rounded-xl border border-gray-200">
                <div class="p-3 border-b border-gray-200 bg-gray-50 rounded-t-xl flex justify-between items-center">
                    <h3 class="font-bold text-gray-600 uppercase text-xs tracking-wider">Novos</h3>
                    <span class="bg-blue-100 text-blue-700 text-xs font-bold px-2 py-0.5 rounded-full" id="count-novo">0</span>
                </div>
                <div class="flex-1 p-3 overflow-y-auto space-y-3 kanban-col" data-status="novo" id="col-novo">
                    </div>
            </div>

            <div class="flex-1 flex flex-col bg-gray-100 rounded-xl border border-gray-200">
                <div class="p-3 border-b border-gray-200 bg-gray-50 rounded-t-xl flex justify-between items-center">
                    <h3 class="font-bold text-gray-600 uppercase text-xs tracking-wider">Em Negociação</h3>
                    <span class="bg-yellow-100 text-yellow-700 text-xs font-bold px-2 py-0.5 rounded-full" id="count-em_negociacao">0</span>
                </div>
                <div class="flex-1 p-3 overflow-y-auto space-y-3 kanban-col" data-status="em_negociacao" id="col-em_negociacao"></div>
            </div>

            <div class="flex-1 flex flex-col bg-green-50 rounded-xl border border-green-200">
                <div class="p-3 border-b border-green-200 bg-green-100 rounded-t-xl flex justify-between items-center">
                    <h3 class="font-bold text-green-700 uppercase text-xs tracking-wider">Fechado (Ganho)</h3>
                    <span class="bg-white text-green-700 text-xs font-bold px-2 py-0.5 rounded-full" id="count-fechado">0</span>
                </div>
                <div class="flex-1 p-3 overflow-y-auto space-y-3 kanban-col" data-status="fechado" id="col-fechado"></div>
            </div>

            <div class="flex-1 flex flex-col bg-red-50 rounded-xl border border-red-200">
                <div class="p-3 border-b border-red-200 bg-red-100 rounded-t-xl flex justify-between items-center">
                    <h3 class="font-bold text-red-700 uppercase text-xs tracking-wider">Perdido</h3>
                    <span class="bg-white text-red-700 text-xs font-bold px-2 py-0.5 rounded-full" id="count-perdido">0</span>
                </div>
                <div class="flex-1 p-3 overflow-y-auto space-y-3 kanban-col" data-status="perdido" id="col-perdido"></div>
            </div>

        </div>
    </div>
</div>

<div id="leadModal" class="fixed inset-0 bg-black/60 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-lg overflow-hidden animate-fade-in-down">
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="font-bold text-gray-800 text-lg">Detalhes do Lead</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-red-500 text-xl">&times;</button>
        </div>
        
        <form id="formLead" class="p-6 space-y-4">
            <input type="hidden" name="id" id="lId">
            
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Empresa / Nome</label>
                <input type="text" name="name" id="lName" class="w-full border border-gray-300 rounded p-2 focus:border-blue-500 outline-none" required>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Contato</label>
                    <input type="text" name="contact_name" id="lContact" class="w-full border border-gray-300 rounded p-2 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Telefone/WhatsApp</label>
                    <input type="text" name="phone" id="lPhone" class="w-full border border-gray-300 rounded p-2 focus:border-blue-500 outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">E-mail</label>
                <input type="email" name="email" id="lEmail" class="w-full border border-gray-300 rounded p-2 focus:border-blue-500 outline-none">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Status</label>
                    <select name="status" id="lStatus" class="w-full border border-gray-300 rounded p-2 bg-white">
                        <option value="novo">Novo</option>
                        <option value="em_negociacao">Em Negociação</option>
                        <option value="fechado">Fechado</option>
                        <option value="perdido">Perdido</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Origem</label>
                    <select name="source" id="lSource" class="w-full border border-gray-300 rounded p-2 bg-white">
                        <option value="site">Site</option>
                        <option value="indicacao">Indicação</option>
                        <option value="google">Google Ads</option>
                        <option value="outros">Outros</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Anotações</label>
                <textarea name="notes" id="lNotes" rows="3" class="w-full border border-gray-300 rounded p-2 focus:border-blue-500 outline-none text-sm"></textarea>
            </div>

            <div class="pt-4 border-t border-gray-100 flex justify-end gap-2">
                <button type="button" id="btnDelete" onclick="deleteLead()" class="hidden px-4 py-2 bg-red-50 text-red-500 rounded hover:bg-red-100 font-bold mr-auto">Excluir</button>
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-white border border-gray-300 text-gray-600 rounded hover:bg-gray-50 font-medium">Cancelar</button>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded font-bold shadow">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
    async function loadCRM() {
        // Limpa colunas
        ['novo', 'em_negociacao', 'fechado', 'perdido'].forEach(s => {
            document.getElementById(`col-${s}`).innerHTML = '';
            document.getElementById(`count-${s}`).innerText = '0';
        });

        const res = await apiFetch('/api/admin/crm');
        if(!res || !res.data) return;

        // Contadores
        const counts = { novo:0, em_negociacao:0, fechado:0, perdido:0 };

        res.data.forEach(lead => {
            const s = lead.status || 'novo';
            if(counts[s] !== undefined) counts[s]++;
            
            const col = document.getElementById(`col-${s}`);
            if(col) {
                // Escapa JSON
                const json = JSON.stringify(lead).replace(/"/g, '&quot;');
                
                const timeAgo = new Date(lead.updated_at).toLocaleDateString('pt-BR');

                col.innerHTML += `
                    <div class="bg-white p-3 rounded-lg shadow-sm border border-gray-200 cursor-pointer hover:shadow-md transition group relative" onclick="editLead(${json})">
                        <div class="flex justify-between items-start mb-1">
                            <h4 class="font-bold text-gray-800 text-sm">${lead.name}</h4>
                            <span class="text-[10px] text-gray-400">${timeAgo}</span>
                        </div>
                        <p class="text-xs text-gray-500 truncate"><i class="fas fa-user mr-1 text-gray-300"></i> ${lead.contact_name || 'Sem contato'}</p>
                        ${lead.phone ? `<p class="text-xs text-gray-500 mt-1"><i class="fas fa-phone mr-1 text-gray-300"></i> ${lead.phone}</p>` : ''}
                        
                        <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition">
                            <button class="text-blue-400 hover:text-blue-600"><i class="fas fa-pen text-xs"></i></button>
                        </div>
                    </div>
                `;
            }
        });

        // Atualiza Badges
        for(const k in counts) {
            document.getElementById(`count-${k}`).innerText = counts[k];
        }
    }

    // Modal
    function openModal() {
        document.getElementById('formLead').reset();
        document.getElementById('lId').value = '';
        document.getElementById('btnDelete').classList.add('hidden');
        document.getElementById('leadModal').classList.remove('hidden');
    }
    function closeModal() { document.getElementById('leadModal').classList.add('hidden'); }

    function editLead(l) {
        document.getElementById('lId').value = l.id;
        document.getElementById('lName').value = l.name;
        document.getElementById('lContact').value = l.contact_name;
        document.getElementById('lEmail').value = l.email;
        document.getElementById('lPhone').value = l.phone;
        document.getElementById('lStatus').value = l.status;
        document.getElementById('lSource').value = l.source;
        document.getElementById('lNotes').value = l.notes;
        
        document.getElementById('btnDelete').classList.remove('hidden');
        document.getElementById('leadModal').classList.remove('hidden');
    }

    // Drag & Drop (Simulado via Select no Modal por enquanto para simplicidade)
    // Para um Kanban real Drag&Drop, precisaríamos da lib SortableJS.
    // Como você pediu praticidade, a edição via modal é mais segura e rápida de implementar agora.

    // Salvar
    document.getElementById('formLead').onsubmit = async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(e.target));
        
        const res = await apiFetch('/api/admin/crm/save', { method: 'POST', body: JSON.stringify(data) });
        if(res.success) {
            closeModal();
            loadCRM();
            showToast('Lead salvo!');
        } else {
            showToast(res.error, 'error');
        }
    };

    // Deletar
    async function deleteLead() {
        const id = document.getElementById('lId').value;
        if(!id || !confirm('Excluir este lead permanentemente?')) return;

        const res = await apiFetch('/api/admin/crm/delete', { method: 'POST', body: JSON.stringify({id}) });
        if(res.success) {
            closeModal();
            loadCRM();
            showToast('Lead removido.');
        } else {
            showToast(res.error, 'error');
        }
    }

    loadCRM();
</script>