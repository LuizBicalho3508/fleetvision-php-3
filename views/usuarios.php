<div class="p-6 space-y-6 h-full flex flex-col">
    
    <div class="flex justify-between items-center shrink-0">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Gerenciamento de Usuários</h1>
            <p class="text-sm text-slate-500">Controle quem acessa o sistema, seus perfis e permissões de frota.</p>
        </div>
        <button onclick="openModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-blue-200 transition flex items-center gap-2">
            <i class="fas fa-user-plus"></i> Novo Usuário
        </button>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 flex-1 overflow-hidden flex flex-col">
        <div class="overflow-x-auto custom-scrollbar flex-1">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-xs sticky top-0 z-10 shadow-sm">
                    <tr>
                        <th class="px-6 py-4 w-10"></th>
                        <th class="px-6 py-4">Nome / E-mail</th>
                        <th class="px-6 py-4">Perfil de Acesso</th>
                        <th class="px-6 py-4">Visibilidade (Cliente)</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody id="usersBody" class="divide-y divide-slate-50">
                    <tr><td colspan="6" class="p-12 text-center text-slate-400">Carregando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="userModal" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white w-full max-w-lg rounded-2xl shadow-2xl p-8 scale-100 transition-transform">
        
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-slate-800" id="modalTitle">Novo Usuário</h3>
            <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times text-xl"></i></button>
        </div>

        <form id="formUser" class="space-y-5">
            <input type="hidden" name="id" id="inputId">
            
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nome Completo</label>
                    <input type="text" name="name" id="inputName" required class="w-full border border-slate-300 rounded-lg p-2.5 outline-none focus:border-blue-500 transition">
                </div>
                
                <div class="col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">E-mail de Acesso</label>
                    <input type="email" name="email" id="inputEmail" required class="w-full border border-slate-300 rounded-lg p-2.5 outline-none focus:border-blue-500 transition">
                </div>

                <div class="col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Senha <span id="passHint" class="text-[10px] text-slate-400 font-normal lowercase">(deixe em branco para manter)</span></label>
                    <input type="password" name="password" id="inputPassword" class="w-full border border-slate-300 rounded-lg p-2.5 outline-none focus:border-blue-500 transition" placeholder="******">
                </div>
            </div>

            <div class="border-t border-slate-100 pt-4 grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Perfil (Função)</label>
                    <select name="role_id" id="inputRole" class="w-full border border-slate-300 rounded-lg p-2.5 outline-none focus:border-blue-500 bg-white transition cursor-pointer">
                        <option value="">-- Selecione --</option>
                    </select>
                    <p class="text-[10px] text-slate-400 mt-1">Define o que ele pode <b>fazer</b>.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Vincular Cliente</label>
                    <select name="customer_id" id="inputCustomer" class="w-full border border-slate-300 rounded-lg p-2.5 outline-none focus:border-blue-500 bg-white transition cursor-pointer">
                        <option value="">Acesso Global (Todos)</option>
                    </select>
                    <p class="text-[10px] text-slate-400 mt-1">Define o que ele pode <b>ver</b>.</p>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="status" id="inputStatus" value="active" class="sr-only peer" checked>
                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500"></div>
                    <span class="ml-3 text-sm font-medium text-slate-600">Usuário Ativo</span>
                </label>
            </div>

            <div class="pt-4 flex gap-3">
                <button type="button" onclick="closeModal()" class="flex-1 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold py-3 rounded-xl transition">Cancelar</button>
                <button type="submit" class="flex-[2] bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl shadow-lg shadow-blue-200 transition">Salvar Usuário</button>
            </div>
        </form>
    </div>
</div>

<script>
    let rolesList = [];
    let customersList = [];

    async function loadUsers() {
        const tbody = document.getElementById('usersBody');
        
        try {
            const res = await apiFetch('/sys/users');
            
            rolesList = res.roles || [];
            customersList = res.customers || [];
            updateModalSelects();

            // Flag vinda do backend
            const isSuperAdmin = res.is_superadmin || false;

            if(!res.users || res.users.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="p-12 text-center text-slate-400">Nenhum usuário encontrado.</td></tr>';
                return;
            }

            tbody.innerHTML = res.users.map(u => {
                const initials = u.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
                
                const roleBadge = u.role_name 
                    ? `<span class="bg-purple-50 text-purple-700 px-2 py-1 rounded-md text-xs font-bold border border-purple-100">${u.role_name}</span>`
                    : `<span class="bg-slate-100 text-slate-500 px-2 py-1 rounded-md text-xs font-bold">Sem Perfil</span>`;

                const customerBadge = u.customer_name
                    ? `<span class="flex items-center gap-1 bg-blue-50 text-blue-700 px-2 py-1 rounded-md text-xs font-bold border border-blue-100"><i class="fas fa-building text-[10px]"></i> ${u.customer_name}</span>`
                    : `<span class="flex items-center gap-1 text-slate-400 text-xs italic"><i class="fas fa-globe text-slate-300"></i> Acesso Global</span>`;

                const statusColor = u.status === 'active' ? 'text-green-500' : 'text-red-400';

                // --- NOVO: BADGE DE TENANT (EMPRESA) ---
                // Só aparece se for Superadmin e o usuário pertencer a um tenant diferente do atual da sessão (opcional) ou sempre.
                let tenantBadge = '';
                if (isSuperAdmin && u.tenant_name) {
                    tenantBadge = `<div class="mt-1"><span class="bg-slate-800 text-white text-[9px] px-1.5 py-0.5 rounded uppercase tracking-wider">${u.tenant_name}</span></div>`;
                }

                return `
                    <tr class="hover:bg-slate-50 transition border-b border-slate-50 last:border-0 group">
                        <td class="px-6 py-4">
                            <div class="w-9 h-9 rounded-full bg-slate-200 flex items-center justify-center font-bold text-slate-500 text-xs">
                                ${initials}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-slate-700">${u.name}</div>
                            <div class="text-xs text-slate-400">${u.email}</div>
                            ${tenantBadge} </td>
                        <td class="px-6 py-4">${roleBadge}</td>
                        <td class="px-6 py-4">${customerBadge}</td>
                        <td class="px-6 py-4">
                            <i class="fas fa-circle ${statusColor} text-[8px]"></i>
                            <span class="text-xs ml-1 text-slate-600 capitalize">${u.status === 'active' ? 'Ativo' : 'Inativo'}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button onclick='editUser(${JSON.stringify(u)})' class="text-slate-400 hover:text-blue-600 mr-3 transition"><i class="fas fa-pen"></i></button>
                            ${!isSuperAdmin ? `<button onclick="deleteUser(${u.id})" class="text-slate-400 hover:text-red-600 transition"><i class="fas fa-trash"></i></button>` : ''} 
                            </td>
                    </tr>
                `;
            }).join('');

        } catch(e) {
            tbody.innerHTML = `<tr><td colspan="6" class="p-8 text-center text-red-400">Erro ao carregar usuários.</td></tr>`;
        }
    }
    
    function updateModalSelects() {
        const selRole = document.getElementById('inputRole');
        const selCust = document.getElementById('inputCustomer');

        // Preserva a primeira opção
        selRole.innerHTML = '<option value="">-- Selecione --</option>';
        selCust.innerHTML = '<option value="">Acesso Global (Todos)</option>';

        rolesList.forEach(r => {
            selRole.innerHTML += `<option value="${r.id}">${r.name}</option>`;
        });

        customersList.forEach(c => {
            selCust.innerHTML += `<option value="${c.id}">${c.name}</option>`;
        });
    }

    // ABRIR/FECHAR
    function openModal() {
        document.getElementById('formUser').reset();
        document.getElementById('inputId').value = '';
        document.getElementById('passHint').innerText = '(obrigatória)';
        document.getElementById('modalTitle').innerText = 'Novo Usuário';
        document.getElementById('userModal').classList.remove('hidden');
    }
    function closeModal() { document.getElementById('userModal').classList.add('hidden'); }

    // EDITAR
    window.editUser = (u) => {
        document.getElementById('inputId').value = u.id;
        document.getElementById('inputName').value = u.name;
        document.getElementById('inputEmail').value = u.email;
        document.getElementById('inputRole').value = u.role_id || '';
        document.getElementById('inputCustomer').value = u.customer_id || '';
        document.getElementById('inputStatus').checked = (u.status === 'active');
        
        document.getElementById('passHint').innerText = '(deixe em branco para manter)';
        document.getElementById('modalTitle').innerText = 'Editar Usuário';
        document.getElementById('userModal').classList.remove('hidden');
    }

    // SALVAR
    document.getElementById('formUser').onsubmit = async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(e.target));
        
        // Checkbox status fix
        data.status = document.getElementById('inputStatus').checked ? 'active' : 'inactive';

        try {
            const res = await apiFetch('/sys/users/save', { method: 'POST', body: JSON.stringify(data) });
            if(res.success) { showToast('Usuário salvo!'); closeModal(); loadUsers(); }
            else { showToast(res.error || 'Erro', 'error'); }
        } catch(err) { showToast('Erro de conexão', 'error'); }
    };

    // EXCLUIR
    window.deleteUser = async (id) => {
        if(!confirm('Tem certeza? Esta ação é irreversível.')) return;
        const res = await apiFetch('/sys/users/delete', { method: 'POST', body: JSON.stringify({id}) });
        if(res.success) { showToast('Usuário removido.'); loadUsers(); }
        else { showToast(res.error, 'error'); }
    }

    loadUsers();
</script>