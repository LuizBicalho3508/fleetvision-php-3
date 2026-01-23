<div class="p-6 space-y-6">

    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Perfis de Acesso</h1>
            <p class="text-sm text-slate-500">Defina o que cada grupo pode ver e fazer.</p>
        </div>
        <button onclick="openRoleModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-sm transition flex items-center gap-2">
            <i class="fas fa-plus"></i> Novo Perfil
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="rolesGrid">
        <div class="col-span-3 text-center py-10 text-slate-400">Carregando perfis...</div>
    </div>
</div>

<div id="modalRole" class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden z-[100] flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex justify-between items-center">
            <h3 class="font-bold text-slate-800 text-lg">Configurar Perfil</h3>
            <button onclick="document.getElementById('modalRole').classList.add('hidden')" class="text-slate-400 hover:text-red-500 transition"><i class="fas fa-times text-xl"></i></button>
        </div>
        <form id="formRole" class="p-6 space-y-4">
            <input type="hidden" name="id" id="roleId">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nome do Perfil</label>
                <input type="text" name="name" id="roleName" placeholder="Ex: Motorista, Gerente..." class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-blue-500 outline-none transition" required>
            </div>
            
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                <p class="text-xs font-bold text-slate-400 uppercase mb-3 tracking-wider">Permissões Habilitadas</p>
                <div class="grid grid-cols-2 gap-3 text-sm text-slate-600 max-h-48 overflow-y-auto custom-scrollbar">
                    <label class="flex items-center gap-2 cursor-pointer hover:text-blue-600"><input type="checkbox" name="permissions[]" value="map_view" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"> Ver Mapa</label>
                    <label class="flex items-center gap-2 cursor-pointer hover:text-blue-600"><input type="checkbox" name="permissions[]" value="vehicles_view" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"> Ver Frota</label>
                    <label class="flex items-center gap-2 cursor-pointer hover:text-blue-600"><input type="checkbox" name="permissions[]" value="customers_view" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"> Clientes</label>
                    <label class="flex items-center gap-2 cursor-pointer hover:text-blue-600"><input type="checkbox" name="permissions[]" value="users_view" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"> Usuários</label>
                    <label class="flex items-center gap-2 cursor-pointer hover:text-blue-600"><input type="checkbox" name="permissions[]" value="reports_view" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"> Relatórios</label>
                    <label class="flex items-center gap-2 cursor-pointer hover:text-blue-600"><input type="checkbox" name="permissions[]" value="roles_manage" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"> Gerir Perfis</label>
                    <label class="flex items-center gap-2 cursor-pointer hover:text-blue-600"><input type="checkbox" name="permissions[]" value="commands_send" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"> Comandos</label>
                </div>
            </div>

            <div class="pt-4 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('modalRole').classList.add('hidden')" class="px-5 py-2.5 rounded-xl text-slate-500 hover:bg-slate-100 font-bold text-sm transition">Cancelar</button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-blue-200 transition">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
    async function loadRoles() {
        const roles = await apiFetch('/api/roles');
        const grid = document.getElementById('rolesGrid');
        if(!roles) return;

        grid.innerHTML = roles.map(r => `
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 hover:border-blue-300 transition group relative">
                <div class="flex justify-between items-start mb-2">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-lg"><i class="fas fa-id-badge"></i></div>
                    
                    <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition">
                         <button onclick='editRole(${JSON.stringify(r)})' class="w-8 h-8 rounded hover:bg-slate-100 text-slate-400 hover:text-blue-600 transition"><i class="fas fa-pen"></i></button>
                         <button onclick="deleteRole(${r.id})" class="w-8 h-8 rounded hover:bg-slate-100 text-slate-400 hover:text-red-600 transition"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
                
                <h4 class="font-bold text-slate-800 text-lg">${r.name}</h4>
                <p class="text-xs text-slate-400 mt-1">ID do sistema: ${r.id}</p>
            </div>
        `).join('');
    }

    function editRole(r) {
        document.getElementById('roleId').value = r.id;
        document.getElementById('roleName').value = r.name.replace(' (Global)', '');
        
        // Reset e marca permissões (simulação: idealmente viria do backend)
        document.querySelectorAll('input[name="permissions[]"]').forEach(el => el.checked = false);
        document.getElementById('modalRole').classList.remove('hidden');
    }

    document.getElementById('formRole').onsubmit = async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(e.target));
        data.permissions = Array.from(document.querySelectorAll('input[name="permissions[]"]:checked')).map(cb => cb.value);

        const res = await apiFetch('/api/roles/save', { method: 'POST', body: JSON.stringify(data) });
        if(res.success) { document.getElementById('modalRole').classList.add('hidden'); loadRoles(); showToast('Perfil salvo!'); }
        else showToast(res.error, 'error');
    };

    function deleteRole(id) {
        if(confirm('Excluir este perfil? Usuários vinculados podem perder acesso.')) {
            apiFetch('/api/roles/delete', { method: 'POST', body: JSON.stringify({id}) }).then(() => loadRoles());
        }
    }

    function openRoleModal() {
        document.getElementById('formRole').reset();
        document.getElementById('roleId').value = '';
        document.getElementById('modalRole').classList.remove('hidden');
    }

    loadRoles();
</script>