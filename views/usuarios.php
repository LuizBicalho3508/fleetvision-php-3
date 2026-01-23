<div class="p-6 space-y-6">

    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Usuários do Sistema</h1>
            <p class="text-sm text-slate-500">Controle quem tem acesso à plataforma.</p>
        </div>
        <button onclick="openUserModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-sm transition flex items-center gap-2">
            <i class="fas fa-user-plus"></i> Adicionar Usuário
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="usersGrid">
        <div class="col-span-3 text-center py-10 text-slate-400">Carregando usuários...</div>
    </div>

</div>

<div id="modalUser" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden z-[100] flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex justify-between items-center">
            <h3 class="font-bold text-slate-800 text-lg">Usuário</h3>
            <button onclick="document.getElementById('modalUser').classList.add('hidden')" class="text-slate-400 hover:text-red-500 transition"><i class="fas fa-times text-xl"></i></button>
        </div>
        <form id="formUser" class="p-6 space-y-4">
            <input type="hidden" name="id" id="userId">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nome</label>
                <input type="text" name="name" id="userName" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-blue-500 outline-none transition" required>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Email</label>
                <input type="email" name="email" id="userEmail" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-blue-500 outline-none transition" required>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Senha</label>
                <input type="password" name="password" placeholder="Deixe vazio para manter" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-blue-500 outline-none transition">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Perfil de Acesso</label>
                <select name="role_id" id="userRole" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-blue-500 bg-white outline-none transition">
                    <option value="">Carregando...</option>
                </select>
            </div>
            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" name="active" id="userActive" class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500" checked>
                <label for="userActive" class="text-sm text-slate-600">Usuário Ativo</label>
            </div>
            
            <div class="pt-4 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('modalUser').classList.add('hidden')" class="px-5 py-2.5 rounded-xl text-slate-500 hover:bg-slate-100 font-bold text-sm transition">Cancelar</button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-blue-200 transition">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
    async function loadData() {
        const [users, roles] = await Promise.all([
            apiFetch('/api/users'),
            apiFetch('/api/roles')
        ]);

        const roleSelect = document.getElementById('userRole');
        roleSelect.innerHTML = '<option value="">Sem Perfil definido</option>' + 
            roles.map(r => `<option value="${r.id}">${r.name}</option>`).join('');

        const grid = document.getElementById('usersGrid');
        
        if(!users || users.length === 0) {
            grid.innerHTML = '<div class="col-span-3 text-center text-slate-400">Nenhum usuário encontrado.</div>';
            return;
        }

        grid.innerHTML = users.map(u => `
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 hover:border-blue-300 hover:shadow-md transition group relative">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center font-bold text-lg border border-slate-200">
                        ${u.name.substr(0,2).toUpperCase()}
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="font-bold text-slate-800 truncate">${u.name}</h4>
                        <p class="text-xs text-slate-400 truncate">${u.email}</p>
                        <span class="inline-block mt-1 text-[10px] uppercase font-bold tracking-wider ${u.role_name ? 'text-blue-600' : 'text-slate-400'}">
                            ${u.role_name || 'Sem Perfil'}
                        </span>
                    </div>
                </div>
                
                <div class="absolute top-4 right-4 flex gap-2 opacity-0 group-hover:opacity-100 transition">
                    <button onclick='editUser(${JSON.stringify(u)})' class="text-slate-400 hover:text-blue-600 transition p-1"><i class="fas fa-pen"></i></button>
                    <button onclick="deleteUser(${u.id})" class="text-slate-400 hover:text-red-600 transition p-1"><i class="fas fa-trash"></i></button>
                </div>

                ${u.active == 0 ? '<div class="absolute inset-0 bg-white/60 backdrop-blur-[1px] rounded-2xl flex items-center justify-center"><span class="bg-red-100 text-red-600 px-3 py-1 rounded-full text-xs font-bold">Inativo</span></div>' : ''}
            </div>
        `).join('');
    }

    function editUser(u) {
        document.getElementById('userId').value = u.id;
        document.getElementById('userName').value = u.name;
        document.getElementById('userEmail').value = u.email;
        document.getElementById('userRole').value = u.role_id || '';
        document.getElementById('userActive').checked = (u.active == 1);
        document.getElementById('modalUser').classList.remove('hidden');
    }

    document.getElementById('formUser').onsubmit = async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(e.target));
        data.active = document.getElementById('userActive').checked ? 1 : 0;
        
        const res = await apiFetch('/api/users/save', { method: 'POST', body: JSON.stringify(data) });
        if(res.success) { document.getElementById('modalUser').classList.add('hidden'); loadData(); showToast('Usuário salvo!'); }
        else showToast(res.error, 'error');
    };

    function deleteUser(id) {
        if(confirm('Tem certeza que deseja remover este usuário?')) {
            apiFetch('/api/users/delete', { method: 'POST', body: JSON.stringify({id}) })
            .then(() => loadData());
        }
    }

    function openUserModal() {
        document.getElementById('formUser').reset();
        document.getElementById('userId').value = '';
        document.getElementById('modalUser').classList.remove('hidden');
    }

    loadData();
</script>