<div class="p-6 space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Usuários</h2>
            <p class="text-sm text-slate-500">Gestão de acesso.</p>
        </div>
        <button onclick="openModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2">
            <i class="fas fa-plus"></i> Novo Usuário
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-slate-700 font-semibold uppercase text-xs">
                <tr>
                    <th class="px-6 py-4">Nome / Email</th>
                    <th class="px-6 py-4">Perfil</th>
                    <th class="px-6 py-4">Cliente Vinculado</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4 text-right">Ações</th>
                </tr>
            </thead>
            <tbody id="usersTableBody" class="divide-y divide-slate-100"></tbody>
        </table>
    </div>
</div>

<div id="userModal" class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
        <h3 class="text-lg font-bold text-slate-800 mb-4" id="modalTitle">Usuário</h3>
        <form id="userForm" class="space-y-4">
            <input type="hidden" name="id" id="userId">
            <input type="hidden" name="tenant_id" id="userTenantId"> <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nome</label>
                <input type="text" name="name" id="userName" required class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Email</label>
                <input type="email" name="email" id="userEmail" required class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Senha</label>
                <input type="password" name="password" id="userPass" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none" placeholder="******">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Perfil</label>
                    <select name="role_id" id="userRole" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none"></select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Status</label>
                    <select name="status" id="userStatus" class="w-full border border-slate-300 rounded-lg px-3 py-2 outline-none">
                        <option value="active">Ativo</option>
                        <option value="inactive">Inativo</option>
                    </select>
                </div>
            </div>

            <div class="bg-blue-50 p-3 rounded-lg border border-blue-100">
                <label class="block text-xs font-bold text-blue-700 uppercase mb-1">Vincular a Cliente (Opcional)</label>
                <select name="customer_id" id="userCustomer" class="w-full border border-blue-200 rounded-lg px-3 py-2 outline-none bg-white">
                    <option value="">-- Usuário Interno (Vê Tudo) --</option>
                    </select>
                <p class="text-[10px] text-blue-500 mt-1">Se selecionado, o usuário verá apenas veículos deste cliente.</p>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeModal()" class="px-4 py-2 text-slate-500 hover:bg-slate-100 rounded-lg font-medium">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg font-medium">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    loadUsers();
    loadRoles();
    loadCustomers(); // Carrega lista de clientes para o select
});

async function loadUsers() {
    const tbody = document.getElementById('usersTableBody');
    try {
        const res = await apiFetch('/sys/users');
        tbody.innerHTML = '';
        res.data.forEach(u => {
            const customerBadge = u.customer_id 
                ? `<span class="px-2 py-1 bg-orange-50 text-orange-700 text-xs font-bold rounded-md border border-orange-100"><i class="fas fa-user-tag mr-1"></i> ${u.customer_name}</span>`
                : `<span class="text-xs text-slate-400">Interno (Acesso Global)</span>`;

            tbody.innerHTML += `
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-4">
                        <div class="font-bold text-slate-800">${u.name}</div>
                        <div class="text-xs text-slate-500">${u.email}</div>
                    </td>
                    <td class="px-6 py-4"><span class="px-2 py-1 bg-blue-50 text-blue-700 text-xs font-bold rounded-md">${u.role_name}</span></td>
                    <td class="px-6 py-4">${customerBadge}</td>
                    <td class="px-6 py-4 text-xs font-bold ${u.status === 'active' ? 'text-green-600' : 'text-red-600'}">${u.status}</td>
                    <td class="px-6 py-4 text-right">
                        <button onclick='editUser(${JSON.stringify(u)})' class="text-blue-600 mx-1"><i class="fas fa-edit"></i></button>
                        <button onclick="deleteUser(${u.id})" class="text-red-600 mx-1"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `;
        });
    } catch (e) { console.error(e); }
}

async function loadRoles() {
    try {
        const res = await apiFetch('/sys/roles');
        const sel = document.getElementById('userRole');
        sel.innerHTML = '<option value="">Selecione...</option>';
        res.data.forEach(r => sel.innerHTML += `<option value="${r.id}">${r.name}</option>`);
    } catch(e) {}
}

async function loadCustomers() {
    try {
        // Assume que existe uma rota para listar clientes (ex: customers do SaaS)
        // Se não tiver, precisa criar no CustomerController
        const res = await apiFetch('/sys/customers'); 
        const sel = document.getElementById('userCustomer');
        // Mantém a opção padrão
        sel.innerHTML = '<option value="">-- Usuário Interno (Vê Tudo) --</option>';
        if(res.data) {
            res.data.forEach(c => sel.innerHTML += `<option value="${c.id}">${c.name}</option>`);
        }
    } catch(e) {}
}

function openModal() {
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('userModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('userModal').classList.add('hidden');
}

function editUser(u) {
    openModal();
    document.getElementById('userId').value = u.id;
    document.getElementById('userName').value = u.name;
    document.getElementById('userEmail').value = u.email;
    document.getElementById('userRole').value = u.role_id;
    document.getElementById('userCustomer').value = u.customer_id; // Seleciona cliente
    document.getElementById('userStatus').value = u.status;
}

document.getElementById('userForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    try {
        await apiFetch('/sys/users/save', { method: 'POST', body: JSON.stringify(data) });
        showToast('Salvo!');
        closeModal();
        loadUsers();
    } catch (e) { showToast(e.message, 'error'); }
});

async function deleteUser(id) {
    if(confirm('Excluir?')) {
        await apiFetch('/sys/users/delete', { method: 'POST', body: JSON.stringify({ id }) });
        loadUsers();
    }
}
</script>