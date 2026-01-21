<?php
// usuarios.php - Gestão de Acesso com Indicador Financeiro
if (session_status() === PHP_SESSION_NONE) {}
if (!isset($_SESSION['user_id'])) { echo "<script>window.location.href='/admin/login';</script>"; exit; }
?>

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
    body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
    .swal2-popup { border-radius: 16px !important; font-family: 'Inter', sans-serif; }
    /* Efeito de bloqueio na linha */
    tr.blocked-row td { background-color: #fef2f2; color: #991b1b; }
    tr.blocked-row td .text-gray-500 { color: #b91c1c; }
</style>

<div class="h-screen flex flex-col overflow-hidden">
    
    <div class="bg-white border-b border-gray-200 px-8 py-5 flex justify-between items-center z-10 shadow-sm shrink-0">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Usuários e Permissões</h1>
            <p class="text-sm text-gray-500 mt-0.5">Gerencie quem acessa o sistema.</p>
        </div>
        <button onclick="openModalUser()" class="bg-slate-800 hover:bg-slate-900 text-white px-5 py-2.5 rounded-xl font-semibold text-sm shadow-lg transition transform active:scale-95 flex items-center gap-2">
            <i class="fas fa-user-plus"></i> Novo Usuário
        </button>
    </div>

    <div class="flex-1 overflow-y-auto p-8">
        <div class="max-w-[1600px] mx-auto">
            
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm flex flex-col">
                <div class="p-5 border-b border-gray-100 flex items-center gap-4 bg-gray-50/30 rounded-t-2xl">
                    <div class="relative w-full max-w-md group">
                        <i class="fas fa-search absolute left-4 top-3.5 text-gray-400"></i>
                        <input type="text" id="search-input" onkeyup="filterUsers()" placeholder="Buscar usuário, email ou empresa..." 
                               class="w-full pl-11 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-slate-100 focus:border-slate-500 outline-none text-sm transition">
                    </div>
                    <div class="flex gap-2">
                        <span class="text-xs font-bold px-3 py-2 bg-red-50 text-red-600 rounded-lg border border-red-100 hidden" id="blocked-count-badge">
                            <i class="fas fa-lock mr-1"></i> <span id="blocked-count">0</span> Bloqueados (Fin.)
                        </span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                            <tr>
                                <th class="p-5 pl-6">Usuário</th>
                                <th class="p-5">Empresa / Cliente</th>
                                <th class="p-5">Perfil</th>
                                <th class="p-5 text-center">Status</th>
                                <th class="p-5 pr-6 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="lista-usuarios" class="text-sm divide-y divide-gray-100 text-gray-600 bg-white"></tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<div id="modal-user" class="fixed inset-0 bg-gray-900/50 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm transition-opacity opacity-0">
    <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl transform scale-95 transition-all duration-300" id="modal-user-content">
        <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50 rounded-t-2xl">
            <h3 class="font-bold text-lg text-gray-800" id="modal-title">Usuário</h3>
            <button onclick="closeModal('modal-user')" class="text-gray-400 hover:text-red-500 transition"><i class="fas fa-times text-xl"></i></button>
        </div>
        <div class="p-6">
            <form id="form-user" onsubmit="saveUser(event)" class="space-y-5">
                <input type="hidden" id="user-id">
                
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1.5 ml-1">NOME COMPLETO</label>
                    <input type="text" id="user-name" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-slate-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1.5 ml-1">EMAIL (LOGIN)</label>
                    <input type="email" id="user-email" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-slate-500 outline-none">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1.5 ml-1">PERFIL DE ACESSO</label>
                        <select id="user-role" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-slate-500 outline-none bg-white cursor-pointer"></select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1.5 ml-1">STATUS</label>
                        <select id="user-active" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-slate-500 outline-none bg-white cursor-pointer">
                            <option value="1">Ativo</option>
                            <option value="0">Inativo</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1.5 ml-1">VINCULAR A CLIENTE (OPCIONAL)</label>
                    <select id="user-customer" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-slate-500 outline-none bg-white cursor-pointer">
                        <option value="">Sem vínculo (Acesso Interno)</option>
                        </select>
                    <p class="text-[10px] text-gray-400 mt-1 ml-1">* Se selecionar, o usuário só verá dados deste cliente.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1.5 ml-1">SENHA <span class="font-normal text-gray-400" id="pass-hint"></span></label>
                    <input type="password" id="user-pass" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-slate-500 outline-none" placeholder="••••••">
                </div>

                <button type="submit" class="w-full bg-slate-800 text-white font-bold py-3.5 rounded-xl hover:bg-slate-900 shadow-md transition active:scale-95">Salvar Usuário</button>
            </form>
        </div>
    </div>
</div>

<script>
    let allUsers = [];
    let rolesList = [];
    let customersList = [];

    document.addEventListener('DOMContentLoaded', async () => {
        await loadFormData();
        await loadUsers();
    });

    async function loadFormData() {
        try {
            const res = await fetch('/api_usuarios.php?action=get_form_data');
            const data = await res.json();
            if(data.success) {
                rolesList = data.roles;
                customersList = data.customers;
                populateSelects();
            }
        } catch(e) {}
    }

    function populateSelects() {
        const roleSel = document.getElementById('user-role');
        roleSel.innerHTML = rolesList.map(r => `<option value="${r.id}">${r.name}</option>`).join('');
        
        const custSel = document.getElementById('user-customer');
        custSel.innerHTML = '<option value="">Sem vínculo (Acesso Interno)</option>' + 
            customersList.map(c => {
                const flag = c.financial_status === 'overdue' ? '🔴 (Inadimplente)' : '';
                return `<option value="${c.id}">${c.name} ${flag}</option>`;
            }).join('');
    }

    async function loadUsers() {
        const tbody = document.getElementById('lista-usuarios');
        try {
            const res = await fetch('/api_usuarios.php?action=get_users');
            const json = await res.json();
            
            if(!json.success) throw new Error(json.error);
            
            allUsers = json.data || [];
            filterUsers();

        } catch(e) {
            tbody.innerHTML = `<tr><td colspan="5" class="p-10 text-center text-red-500">${e.message}</td></tr>`;
        }
    }

    function filterUsers() {
        const term = document.getElementById('search-input').value.toLowerCase();
        const filtered = allUsers.filter(u => 
            u.name.toLowerCase().includes(term) || 
            u.email.toLowerCase().includes(term) ||
            (u.customer_name && u.customer_name.toLowerCase().includes(term))
        );
        renderTable(filtered);
    }

    function renderTable(data) {
        const tbody = document.getElementById('lista-usuarios');
        let blockedCount = 0;

        if(data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="p-10 text-center text-gray-400">Nenhum usuário encontrado.</td></tr>';
            return;
        }

        tbody.innerHTML = data.map(u => {
            // Lógica de Status (Financeiro > Ativo/Inativo)
            let statusHtml = '';
            let rowClass = '';
            
            if (u.is_blocked_financial) {
                blockedCount++;
                statusHtml = `<span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-[10px] font-bold uppercase border border-red-200 flex items-center justify-center gap-1"><i class="fas fa-lock"></i> Bloqueado (Fin.)</span>`;
                rowClass = 'blocked-row'; // Classe CSS para pintar de vermelho
            } else if (u.active == 1) {
                statusHtml = `<span class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-full text-[10px] font-bold uppercase border border-emerald-200">Ativo</span>`;
            } else {
                statusHtml = `<span class="bg-gray-100 text-gray-500 px-3 py-1 rounded-full text-[10px] font-bold uppercase border border-gray-200">Inativo</span>`;
            }

            // Iniciais
            const initials = u.name.substring(0,2).toUpperCase();
            const avatarColor = u.is_blocked_financial ? 'bg-red-200 text-red-700' : 'bg-slate-200 text-slate-600';

            return `
            <tr class="hover:bg-gray-50/80 transition border-b border-gray-100 group ${rowClass}">
                <td class="p-5 pl-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full ${avatarColor} flex items-center justify-center font-bold text-xs shadow-sm">${initials}</div>
                        <div>
                            <div class="font-bold text-gray-800 text-sm">${u.name}</div>
                            <div class="text-xs text-gray-500">${u.email}</div>
                        </div>
                    </div>
                </td>
                <td class="p-5 text-sm">
                    ${u.customer_name ? `<span class="font-semibold text-slate-700"><i class="fas fa-building text-slate-400 mr-1"></i> ${u.customer_name}</span>` : '<span class="text-slate-400 italic">Interno</span>'}
                </td>
                <td class="p-5 text-sm text-slate-600">${u.role_name || '-'}</td>
                <td class="p-5 text-center">${statusHtml}</td>
                <td class="p-5 pr-6 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <button onclick="editUser(${u.id})" class="text-slate-400 hover:text-blue-600 p-2 rounded-lg hover:bg-white transition"><i class="fas fa-pen"></i></button>
                        <button onclick="deleteUser(${u.id})" class="text-slate-400 hover:text-red-600 p-2 rounded-lg hover:bg-white transition"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>`;
        }).join('');

        // Badge de Contagem
        const badge = document.getElementById('blocked-count-badge');
        if(blockedCount > 0) {
            badge.classList.remove('hidden');
            document.getElementById('blocked-count').innerText = blockedCount;
        } else {
            badge.classList.add('hidden');
        }
    }

    // --- CRUD ---
    function openModalUser(id = null) {
        document.getElementById('form-user').reset();
        document.getElementById('user-id').value = '';
        document.getElementById('modal-title').innerText = 'Novo Usuário';
        document.getElementById('pass-hint').innerText = '(Obrigatória)';
        
        if (id) {
            const u = allUsers.find(x => x.id == id);
            if(u) {
                document.getElementById('user-id').value = u.id;
                document.getElementById('user-name').value = u.name;
                document.getElementById('user-email').value = u.email;
                document.getElementById('user-role').value = u.role_id;
                document.getElementById('user-customer').value = u.customer_id || '';
                document.getElementById('user-active').value = u.active;
                document.getElementById('modal-title').innerText = 'Editar Usuário';
                document.getElementById('pass-hint').innerText = '(Deixe vazio para manter)';
            }
        }
        
        const m = document.getElementById('modal-user');
        m.classList.remove('hidden');
        setTimeout(() => { m.classList.remove('opacity-0'); document.getElementById('modal-user-content').classList.remove('scale-95'); }, 10);
    }
    window.editUser = (id) => openModalUser(id);

    async function saveUser(e) {
        e.preventDefault();
        const data = {
            id: document.getElementById('user-id').value,
            name: document.getElementById('user-name').value,
            email: document.getElementById('user-email').value,
            role_id: document.getElementById('user-role').value,
            customer_id: document.getElementById('user-customer').value,
            active: document.getElementById('user-active').value,
            password: document.getElementById('user-pass').value
        };

        try {
            const res = await fetch('/api_usuarios.php?action=save_user', { method: 'POST', body: JSON.stringify(data) });
            const json = await res.json();
            if(json.success) {
                Swal.fire({ icon: 'success', title: 'Salvo!', timer: 1500, showConfirmButton: false });
                closeModal('modal-user');
                loadUsers();
            } else throw new Error(json.error);
        } catch(err) { Swal.fire('Erro', err.message, 'error'); }
    }

    window.deleteUser = async (id) => {
        const r = await Swal.fire({ title: 'Excluir?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Sim' });
        if (r.isConfirmed) {
            try { await fetch('/api_usuarios.php?action=delete_user', { method: 'POST', body: JSON.stringify({id}) }); loadUsers(); } catch(e) {}
        }
    }

    function closeModal(id) {
        const m = document.getElementById(id);
        const c = m.querySelector('div[id$="-content"]');
        c.classList.add('scale-95'); m.classList.add('opacity-0');
        setTimeout(() => { m.classList.add('hidden'); c.classList.remove('scale-95'); }, 300);
    }
</script>