<div class="p-6 space-y-6">
    
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Perfis de Acesso</h2>
            <p class="text-sm text-slate-500">Defina o que cada nível de usuário pode ver e fazer.</p>
        </div>
        <button onclick="openModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2">
            <i class="fas fa-plus"></i> Novo Perfil
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="rolesGrid">
        <div class="col-span-full text-center py-10 text-slate-400">
            <i class="fas fa-circle-notch fa-spin text-2xl"></i> Carregando perfis...
        </div>
    </div>
</div>

<div id="roleModal" class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl p-6 transform transition-all scale-100 max-h-[90vh] overflow-hidden flex flex-col">
        
        <div class="flex justify-between items-center mb-4 shrink-0">
            <h3 class="text-lg font-bold text-slate-800" id="modalTitle">Novo Perfil</h3>
            <button onclick="closeModal()" class="text-slate-400 hover:text-red-500 transition"><i class="fas fa-times text-xl"></i></button>
        </div>
        
        <div class="overflow-y-auto pr-2 custom-scrollbar flex-1">
            <form id="roleForm" class="space-y-6">
                <input type="hidden" name="id" id="roleId">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nome do Perfil</label>
                        <input type="text" name="name" id="roleName" required class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Ex: Gestor de Frota">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Descrição</label>
                        <input type="text" name="description" id="roleDesc" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Ex: Acesso total aos veículos">
                    </div>
                </div>

                <hr class="border-slate-100">

                <div>
                    <div class="flex justify-between items-end mb-3">
                        <label class="block text-xs font-bold text-slate-500 uppercase">Permissões de Acesso</label>
                        <button type="button" onclick="toggleAllPerms()" class="text-xs text-blue-600 hover:underline font-medium">Marcar/Desmarcar Todos</button>
                    </div>
                    
                    <div id="permissionsContainer" class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                        </div>
                </div>
            </form>
        </div>

        <div class="pt-4 mt-4 border-t border-slate-100 flex justify-end gap-3 shrink-0">
            <button type="button" onclick="closeModal()" class="px-4 py-2 text-slate-500 hover:bg-slate-100 rounded-lg font-medium">Cancelar</button>
            <button type="button" onclick="saveRole()" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium shadow-lg">Salvar Perfil</button>
        </div>
    </div>
</div>

<script>
// =========================================================
// 1. MAPA DE PERMISSÕES DO SISTEMA (CRÍTICO: NÃO REMOVA)
// Isso resolve o erro "undefined" no Object.entries
// =========================================================
const SYSTEM_PERMISSIONS = {
    'Painéis & Visão Geral': [
        { key: 'dashboard_view', label: 'Dashboard Principal' },
        { key: 'map_view',       label: 'Mapa em Tempo Real' },
        { key: 'map_history',    label: 'Histórico de Rotas' }
    ],
    'Gestão de Frota': [
        { key: 'vehicles_view',  label: 'Ver Veículos' },
        { key: 'vehicles_edit',  label: 'Editar Veículos' },
        { key: 'drivers_view',   label: 'Ver Motoristas' },
        { key: 'drivers_edit',   label: 'Editar Motoristas' },
        { key: 'stock_view',     label: 'Controle de Estoque' },
        { key: 'journey_view',   label: 'Controle de Jornada' },
        { key: 'ranking_view',   label: 'Ranking de Motoristas' }
    ],
    'Segurança & Monitoramento': [
        { key: 'alerts_view',    label: 'Gestão de Alertas' },
        { key: 'geofences_view', label: 'Cercas Virtuais' }
    ],
    'Administrativo': [
        { key: 'customers_view', label: 'Gestão de Clientes' },
        { key: 'financial_view', label: 'Módulo Financeiro' },
        { key: 'reports_view',   label: 'Relatórios' },
        { key: 'users_view',     label: 'Gestão de Usuários' },
        { key: 'settings_view',  label: 'Configurações do Sistema' } // Essencial para o Design
    ]
};

// =========================================================
// 2. LÓGICA DA PÁGINA
// =========================================================

document.addEventListener('DOMContentLoaded', loadRoles);

async function loadRoles() {
    const container = document.getElementById('rolesGrid');
    
    try {
        const res = await apiFetch('/sys/roles'); // Chama o controller list()
        container.innerHTML = '';

        if (res.data.length === 0) {
            container.innerHTML = `<div class="col-span-full text-center py-10 text-slate-400">Nenhum perfil encontrado. Crie o primeiro!</div>`;
            return;
        }

        res.data.forEach(role => {
            // Conta quantas permissões ativas
            const permCount = Array.isArray(role.permissions) ? role.permissions.length : 0;
            const isMaster = role.permissions.includes('*'); // Se for Super Admin

            container.innerHTML += `
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 hover:shadow-md transition p-5 flex flex-col justify-between h-full">
                    <div>
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="text-lg font-bold text-slate-800">${role.name}</h3>
                            <i class="fas fa-shield-alt text-slate-200 text-2xl"></i>
                        </div>
                        <p class="text-sm text-slate-500 mb-4 h-10 overflow-hidden text-ellipsis">${role.description || 'Sem descrição'}</p>
                        
                        <div class="flex gap-2 mb-4">
                            <span class="px-2 py-1 bg-blue-50 text-blue-700 text-xs font-bold rounded-md">
                                <i class="fas fa-users mr-1"></i> ${role.user_count} Usuários
                            </span>
                            <span class="px-2 py-1 bg-purple-50 text-purple-700 text-xs font-bold rounded-md">
                                <i class="fas fa-key mr-1"></i> ${isMaster ? 'Acesso Total' : permCount + ' Permissões'}
                            </span>
                        </div>
                    </div>

                    <div class="flex gap-2 mt-4 pt-4 border-t border-slate-100">
                        <button onclick='editRole(${JSON.stringify(role)})' class="flex-1 py-2 text-sm font-bold text-blue-600 hover:bg-blue-50 rounded-lg transition">
                            Editar
                        </button>
                        <button onclick="deleteRole(${role.id})" class="flex-1 py-2 text-sm font-bold text-red-600 hover:bg-red-50 rounded-lg transition">
                            Excluir
                        </button>
                    </div>
                </div>
            `;
        });

    } catch (error) {
        container.innerHTML = `<div class="col-span-full text-center text-red-500">Erro ao carregar: ${error.message}</div>`;
    }
}

// Renderiza o Formulário de Permissões (Onde dava erro antes)
function renderPermissionsForm(activePerms = []) {
    const container = document.getElementById('permissionsContainer');
    container.innerHTML = '';

    // Agora iteramos sobre SYSTEM_PERMISSIONS que está definido!
    Object.entries(SYSTEM_PERMISSIONS).forEach(([category, perms]) => {
        let html = `
            <div class="bg-slate-50 p-4 rounded-lg border border-slate-100">
                <h4 class="text-xs font-bold text-slate-400 uppercase mb-3 border-b border-slate-200 pb-1">${category}</h4>
                <div class="space-y-2">
        `;

        perms.forEach(p => {
            // Verifica se está ativo (ou se é superadmin *)
            const isChecked = activePerms.includes(p.key) || activePerms.includes('*') ? 'checked' : '';
            
            html += `
                <label class="flex items-center gap-3 cursor-pointer group">
                    <input type="checkbox" name="perms[]" value="${p.key}" ${isChecked} 
                           class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500 cursor-pointer">
                    <span class="text-sm text-slate-600 group-hover:text-slate-900 transition">${p.label}</span>
                </label>
            `;
        });

        html += `</div></div>`;
        container.innerHTML += html;
    });
}

function openModal() {
    document.getElementById('roleForm').reset();
    document.getElementById('roleId').value = '';
    document.getElementById('modalTitle').innerText = 'Novo Perfil';
    document.getElementById('roleModal').classList.remove('hidden');
    
    renderPermissionsForm([]); // Renderiza tudo desmarcado
}

function editRole(role) {
    document.getElementById('roleId').value = role.id;
    document.getElementById('roleName').value = role.name;
    document.getElementById('roleDesc').value = role.description;
    document.getElementById('modalTitle').innerText = 'Editar Perfil';
    
    document.getElementById('roleModal').classList.remove('hidden');
    
    // Garante que role.permissions seja array
    const perms = Array.isArray(role.permissions) ? role.permissions : [];
    renderPermissionsForm(perms);
}

function closeModal() {
    document.getElementById('roleModal').classList.add('hidden');
}

// Botão Marcar Todos
function toggleAllPerms() {
    const checkboxes = document.querySelectorAll('input[name="perms[]"]');
    const allChecked = Array.from(checkboxes).every(c => c.checked);
    checkboxes.forEach(c => c.checked = !allChecked);
}

// Salvar
async function saveRole() {
    const id = document.getElementById('roleId').value;
    const name = document.getElementById('roleName').value;
    const desc = document.getElementById('roleDesc').value;
    
    // Coleta Checkboxes marcados
    const permissions = Array.from(document.querySelectorAll('input[name="perms[]"]:checked'))
                             .map(cb => cb.value);

    if(!name) {
        showToast('Nome é obrigatório', 'error');
        return;
    }

    try {
        await apiFetch('/sys/roles/save', {
            method: 'POST',
            body: JSON.stringify({ id, name, description: desc, permissions })
        });
        
        showToast('Perfil salvo com sucesso!');
        closeModal();
        loadRoles();
    } catch (error) {
        showToast(error.message, 'error');
    }
}

// Excluir
async function deleteRole(id) {
    if(!confirm('Tem certeza? Isso pode afetar usuários vinculados.')) return;
    try {
        await apiFetch('/sys/roles/delete', {
            method: 'POST',
            body: JSON.stringify({ id })
        });
        showToast('Perfil excluído.');
        loadRoles();
    } catch (error) {
        showToast(error.message, 'error');
    }
}
</script>