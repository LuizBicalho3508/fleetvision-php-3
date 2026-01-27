<div class="p-6 space-y-6 h-full flex flex-col">
    
    <div class="flex justify-between items-center shrink-0">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Perfis de Acesso</h1>
            <p class="text-sm text-slate-500">Defina o que cada grupo de usuários pode ver ou editar.</p>
        </div>
        <button onclick="openModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-blue-200 transition flex items-center gap-2">
            <i class="fas fa-shield-alt"></i> Novo Perfil
        </button>
    </div>

    <div id="rolesGrid" class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="col-span-3 text-center p-12 text-slate-400">Carregando perfis...</div>
    </div>
</div>

<div id="roleModal" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white w-full max-w-4xl rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
        
        <div class="flex justify-between items-center px-8 py-5 border-b border-slate-100 bg-slate-50/50">
            <div>
                <h3 class="text-xl font-bold text-slate-800" id="modalTitle">Novo Perfil</h3>
                <p class="text-xs text-slate-500">Configure os acessos detalhados abaixo.</p>
            </div>
            <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times text-xl"></i></button>
        </div>

        <div class="flex-1 overflow-y-auto custom-scrollbar p-8">
            <form id="formRole" class="space-y-6">
                <input type="hidden" name="id" id="inputId">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nome do Perfil</label>
                        <input type="text" name="name" id="inputName" required placeholder="Ex: Gestor de Frota" class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Descrição</label>
                        <input type="text" name="description" id="inputDescription" placeholder="Acesso limitado a..." class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 outline-none">
                    </div>
                </div>

                <hr class="border-slate-100">

                <div>
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="font-bold text-slate-700 flex items-center gap-2"><i class="fas fa-lock text-blue-500"></i> Permissões do Sistema</h4>
                        <div class="space-x-2">
                            <button type="button" onclick="toggleAll(true)" class="text-xs text-blue-600 hover:underline">Marcar Tudo</button>
                            <span class="text-slate-300">|</span>
                            <button type="button" onclick="toggleAll(false)" class="text-xs text-slate-500 hover:underline">Desmarcar Tudo</button>
                        </div>
                    </div>

                    <div class="border border-slate-200 rounded-xl overflow-hidden">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-xs">
                                <tr>
                                    <th class="px-6 py-3 w-1/3">Módulo</th>
                                    <th class="px-6 py-3 text-center">Visualizar</th>
                                    <th class="px-6 py-3 text-center">Criar</th>
                                    <th class="px-6 py-3 text-center">Editar</th>
                                    <th class="px-6 py-3 text-center">Excluir</th>
                                    <th class="px-6 py-3 text-center">Outros</th>
                                </tr>
                            </thead>
                            <tbody id="permissionsBody" class="divide-y divide-slate-100">
                                </tbody>
                        </table>
                    </div>
                </div>
            </form>
        </div>

        <div class="p-6 border-t border-slate-100 bg-slate-50/50 flex justify-end gap-3">
            <button onclick="closeModal()" class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-600 font-bold hover:bg-white transition">Cancelar</button>
            <button onclick="document.getElementById('formRole').requestSubmit()" class="px-5 py-2.5 rounded-lg bg-blue-600 text-white font-bold hover:bg-blue-700 shadow-lg shadow-blue-200 transition">Salvar Perfil</button>
        </div>
    </div>
</div>

<script>
    let definitions = {};

    async function loadRoles() {
        const grid = document.getElementById('rolesGrid');
        try {
            const res = await apiFetch('/sys/roles');
            definitions = res.definitions; // Guarda estrutura para usar no modal
            
            if(!res.roles || res.roles.length === 0) {
                grid.innerHTML = '<div class="col-span-3 text-center p-8 border-2 border-dashed border-slate-200 rounded-xl text-slate-400">Nenhum perfil criado.</div>';
                return;
            }

            grid.innerHTML = res.roles.map(r => `
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm hover:border-blue-300 transition-all group relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-4 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button onclick='editRole(${JSON.stringify(r)})' class="text-blue-500 hover:bg-blue-50 p-2 rounded"><i class="fas fa-pen"></i></button>
                        ${!r.is_system ? `<button onclick="deleteRole(${r.id})" class="text-red-500 hover:bg-red-50 p-2 rounded"><i class="fas fa-trash"></i></button>` : ''}
                    </div>
                    
                    <div class="w-12 h-12 rounded-xl ${r.is_system ? 'bg-purple-100 text-purple-600' : 'bg-blue-100 text-blue-600'} flex items-center justify-center text-xl mb-4">
                        <i class="fas ${r.is_system ? 'fa-crown' : 'fa-user-tag'}"></i>
                    </div>
                    
                    <h3 class="font-bold text-lg text-slate-800 mb-1">${r.name}</h3>
                    <p class="text-sm text-slate-500 mb-4 h-10 overflow-hidden">${r.description || 'Sem descrição.'}</p>
                    
                    <div class="flex items-center gap-2 text-xs font-bold text-slate-400 bg-slate-50 px-3 py-2 rounded-lg w-fit">
                        <i class="fas fa-users"></i> ${r.users_count} Usuários vinculados
                    </div>
                </div>
            `).join('');

        } catch(e) { console.error(e); }
    }

    // --- RENDERIZA O FORMULÁRIO DINÂMICO ---
    function renderPermissionsForm(selectedPerms = []) {
        const tbody = document.getElementById('permissionsBody');
        tbody.innerHTML = '';

        for (const [moduleKey, moduleData] of Object.entries(definitions)) {
            const tr = document.createElement('tr');
            tr.className = "hover:bg-slate-50 transition";
            
            // Coluna Nome
            let html = `<td class="px-6 py-4 font-bold text-slate-700">${moduleData.label}</td>`;

            // Colunas de Ação Padrão (View, Create, Edit, Delete)
            ['view', 'create', 'edit', 'delete'].forEach(action => {
                html += `<td class="px-6 py-4 text-center">`;
                
                if (moduleData.actions.includes(action)) {
                    const slug = `${moduleKey}_${action}`;
                    const checked = selectedPerms.includes(slug) ? 'checked' : '';
                    html += `
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="permissions[]" value="${slug}" ${checked} class="w-5 h-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500 perm-check">
                        </label>
                    `;
                } else {
                    html += `<span class="text-slate-200 text-xs">-</span>`;
                }
                html += `</td>`;
            });

            // Coluna Outros (Export, Config, etc)
            const otherActions = moduleData.actions.filter(a => !['view', 'create', 'edit', 'delete'].includes(a));
            html += `<td class="px-6 py-4 text-center">`;
            if (otherActions.length > 0) {
                otherActions.forEach(action => {
                    const slug = `${moduleKey}_${action}`;
                    const checked = selectedPerms.includes(slug) ? 'checked' : '';
                    // Tradução rápida visual
                    let icon = action === 'export' ? 'fa-file-export' : 'fa-cog';
                    let title = action === 'export' ? 'Exportar' : 'Configurar';
                    
                    html += `
                        <label class="inline-flex items-center cursor-pointer mr-2" title="${title}">
                            <input type="checkbox" name="permissions[]" value="${slug}" ${checked} class="w-4 h-4 rounded border-slate-300 text-purple-600 focus:ring-purple-500 perm-check">
                            <i class="fas ${icon} ml-1 text-slate-400 text-xs"></i>
                        </label>
                    `;
                });
            } else { html += `<span class="text-slate-200 text-xs">-</span>`; }
            html += `</td>`;

            tr.innerHTML = html;
            tbody.appendChild(tr);
        }
    }

    function openModal() {
        document.getElementById('formRole').reset();
        document.getElementById('inputId').value = '';
        document.getElementById('modalTitle').innerText = 'Novo Perfil';
        renderPermissionsForm([]); // Renderiza vazio
        document.getElementById('roleModal').classList.remove('hidden');
    }

    async function editRole(role) {
        document.getElementById('inputId').value = role.id;
        document.getElementById('inputName').value = role.name;
        document.getElementById('inputDescription').value = role.description;
        document.getElementById('modalTitle').innerText = 'Editar Perfil';
        
        // Busca permissões atuais do perfil
        const res = await apiFetch(`/sys/roles/perms?id=${role.id}`);
        renderPermissionsForm(res.permissions || []);
        
        document.getElementById('roleModal').classList.remove('hidden');
    }

    function closeModal() { document.getElementById('roleModal').classList.add('hidden'); }

    function toggleAll(state) {
        document.querySelectorAll('.perm-check').forEach(el => el.checked = state);
    }

    // SALVAR
    document.getElementById('formRole').onsubmit = async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        // Coleta permissões manualmente (checkboxes array)
        const perms = [];
        document.querySelectorAll('.perm-check:checked').forEach(el => perms.push(el.value));
        
        const payload = {
            id: formData.get('id'),
            name: formData.get('name'),
            description: formData.get('description'),
            permissions: perms
        };

        try {
            const res = await apiFetch('/sys/roles/save', { method: 'POST', body: JSON.stringify(payload) });
            if(res.success) { showToast('Perfil salvo!'); closeModal(); loadRoles(); }
            else { showToast(res.error || 'Erro', 'error'); }
        } catch(err) { showToast('Erro de conexão', 'error'); }
    };

    // EXCLUIR
    window.deleteRole = async (id) => {
        if(!confirm('Tem certeza? Usuários com este perfil ficarão sem acesso.')) return;
        const res = await apiFetch('/sys/roles/delete', { method: 'POST', body: JSON.stringify({id}) });
        if(res.success) { showToast('Removido'); loadRoles(); }
        else { showToast(res.error, 'error'); }
    }

    loadRoles();
</script>