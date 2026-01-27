<div class="p-6 space-y-6">
    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Financeiro SaaS</h1>
            <p class="text-sm text-slate-500">Gestão de cobranças e faturamento por tenant.</p>
        </div>
        
        <div class="bg-white px-4 py-2 rounded-xl border border-slate-200 shadow-sm flex items-center gap-2">
            <span class="text-xs font-bold text-slate-400 uppercase">Ano de Referência:</span>
            <select id="yearFilter" onchange="loadFinance()" class="bg-transparent font-bold text-slate-700 outline-none cursor-pointer">
                <?php 
                $curr = date('Y');
                for($i=$curr; $i >= $curr-2; $i--) echo "<option value='$i'>$i</option>"; 
                ?>
            </select>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-xs">
                    <tr>
                        <th class="px-6 py-4">Tenant (Cliente)</th>
                        <th class="px-6 py-4 text-center">Veículos Ativos</th>
                        <th class="px-6 py-4 text-center">Valor Unit.</th>
                        <th class="px-6 py-4 text-right">Fatura Mensal</th>
                        <th class="px-6 py-4 text-center">Dia Venc.</th>
                        <th class="px-6 py-4 text-center">Status (Mês Atual)</th>
                        <th class="px-6 py-4 text-center">Ações</th>
                    </tr>
                </thead>
                <tbody id="financeTable" class="divide-y divide-slate-100">
                    <tr><td colspan="7" class="p-8 text-center text-slate-400"><i class="fas fa-circle-notch fa-spin"></i> Carregando dados...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="configModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm transition-opacity" onclick="closeModal('configModal')"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white w-full max-w-sm rounded-2xl shadow-2xl p-6">
        <h3 class="text-lg font-bold text-slate-800 mb-4">Configurar Cobrança</h3>
        <form id="formConfig" class="space-y-4">
            <input type="hidden" name="id" id="confId">
            
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Valor por Veículo (R$)</label>
                <div class="relative">
                    <span class="absolute left-3 top-2 text-slate-400">R$</span>
                    <input type="number" step="0.01" name="unit_price" id="confPrice" class="w-full pl-8 border border-slate-300 rounded-lg p-2 outline-none focus:border-blue-500">
                </div>
            </div>
            
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Dia de Vencimento</label>
                <input type="number" min="1" max="31" name="due_day" id="confDay" class="w-full border border-slate-300 rounded-lg p-2 outline-none focus:border-blue-500">
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl shadow transition">Salvar Configuração</button>
        </form>
    </div>
</div>

<div id="paymentModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm transition-opacity" onclick="closeModal('paymentModal')"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white w-full max-w-lg rounded-2xl shadow-2xl p-8">
        
        <div class="flex justify-between items-start mb-6">
            <div>
                <h3 class="text-xl font-bold text-slate-800" id="modalTenantName">Empresa</h3>
                <p class="text-sm text-slate-500">Controle de pagamentos - Ano <span id="modalYear"></span></p>
            </div>
            <button onclick="closeModal('paymentModal')" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times text-xl"></i></button>
        </div>

        <div class="grid grid-cols-3 sm:grid-cols-4 gap-3 mb-6" id="monthsGrid">
            </div>

        <div class="bg-blue-50 p-4 rounded-xl flex items-start gap-3">
            <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
            <p class="text-xs text-blue-700">Marque os meses que já foram pagos. As alterações são salvas automaticamente.</p>
        </div>
    </div>
</div>

<script>
    const monthsNames = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    let currentData = [];

    // Carrega dados da API
    async function loadFinance() {
        const year = document.getElementById('yearFilter').value;
        const res = await apiFetch(`/sys/admin/financial?year=${year}`);
        
        if (res.data) {
            currentData = res.data;
            renderTable(res.data);
        } else {
            document.getElementById('financeTable').innerHTML = '<tr><td colspan="7" class="text-center p-8 text-red-500">Erro ao carregar dados.</td></tr>';
        }
    }

    // Renderiza a tabela principal
    function renderTable(list) {
        const tbody = document.getElementById('financeTable');
        const currentMonth = new Date().getMonth() + 1;
        const currentDay = new Date().getDate();

        if (list.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="p-8 text-center text-slate-400">Nenhum tenant encontrado.</td></tr>';
            return;
        }

        tbody.innerHTML = list.map(t => {
            // Lógica de Status (Pago / Atrasado / Aberto)
            const isPaid = t.payments_year.includes(currentMonth);
            const isLate = !isPaid && currentDay > t.due_day;
            
            let statusBadge = '';
            if (isPaid) {
                statusBadge = '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700"><i class="fas fa-check-circle"></i> Pago</span>';
            } else if (isLate) {
                statusBadge = '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 animate-pulse"><i class="fas fa-exclamation-circle"></i> Atrasado</span>';
            } else {
                statusBadge = '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-700"><i class="fas fa-clock"></i> Aberto</span>';
            }

            return `
            <tr class="hover:bg-slate-50 transition border-b border-slate-50">
                <td class="px-6 py-4">
                    <div class="font-bold text-slate-800">${t.name}</div>
                    <div class="text-xs text-slate-400 font-mono">/${t.slug}</div>
                </td>
                <td class="px-6 py-4 text-center">
                    <span class="bg-slate-100 text-slate-600 px-2 py-1 rounded text-xs font-bold font-mono border border-slate-200">
                        ${t.active_vehicles} veíc.
                    </span>
                </td>
                <td class="px-6 py-4 text-center text-xs text-slate-600">
                    R$ ${parseFloat(t.unit_price).toFixed(2)}
                </td>
                <td class="px-6 py-4 text-right">
                    <span class="font-bold text-slate-800">R$ ${t.total_amount.toFixed(2)}</span>
                </td>
                <td class="px-6 py-4 text-center text-xs text-slate-500">
                    Dia ${t.due_day}
                </td>
                <td class="px-6 py-4 text-center">
                    ${statusBadge}
                </td>
                <td class="px-6 py-4 text-center">
                    <div class="flex items-center justify-center gap-2">
                        <button onclick='openConfig(${JSON.stringify(t)})' class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Configurar Valores">
                            <i class="fas fa-cog"></i>
                        </button>
                        <button onclick='openPayments(${t.id}, "${t.name}")' class="p-2 text-slate-400 hover:text-green-600 hover:bg-green-50 rounded-lg transition" title="Gerenciar Pagamentos">
                            <i class="fas fa-calendar-check"></i>
                        </button>
                    </div>
                </td>
            </tr>
            `;
        }).join('');
    }

    // --- FUNÇÕES DE MODAL E AÇÕES ---

    // 1. Configurar Valores
    window.openConfig = (t) => {
        document.getElementById('confId').value = t.id;
        document.getElementById('confPrice').value = t.unit_price;
        document.getElementById('confDay').value = t.due_day;
        document.getElementById('configModal').classList.remove('hidden');
    }

    document.getElementById('formConfig').onsubmit = async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(e.target));
        
        const res = await apiFetch('/sys/admin/financial/save', {
            method: 'POST',
            body: JSON.stringify(data)
        });

        if (res.success) {
            showToast('Configuração salva com sucesso!');
            closeModal('configModal');
            loadFinance();
        } else {
            showToast('Erro ao salvar.', 'error');
        }
    };

    // 2. Gerenciar Pagamentos (Modal Checkboxes)
    window.openPayments = (id, name) => {
        const t = currentData.find(item => item.id == id);
        const paidMonths = t.payments_year || [];
        const year = document.getElementById('yearFilter').value;
        
        document.getElementById('modalTenantName').innerText = name;
        document.getElementById('modalYear').innerText = year;
        
        const grid = document.getElementById('monthsGrid');
        grid.innerHTML = monthsNames.map((m, index) => {
            const monthNum = index + 1;
            const isChecked = paidMonths.includes(monthNum);
            
            return `
            <label class="cursor-pointer select-none group">
                <input type="checkbox" class="peer hidden" 
                       ${isChecked ? 'checked' : ''}
                       onchange="toggleMonth(${id}, ${monthNum}, this.checked)">
                
                <div class="p-3 rounded-xl border border-slate-200 text-center transition-all duration-200
                            peer-checked:bg-green-500 peer-checked:border-green-500 peer-checked:text-white
                            group-hover:border-blue-300">
                    <div class="text-sm font-bold mb-1">${m}</div>
                    <div class="text-[10px] opacity-80 uppercase">${isChecked ? 'PAGO' : 'ABERTO'}</div>
                </div>
            </label>
            `;
        }).join('');

        document.getElementById('paymentModal').classList.remove('hidden');
    }

    window.toggleMonth = async (id, month, isChecked) => {
        const year = document.getElementById('yearFilter').value;
        
        // Atualização Otimista (Visual)
        const t = currentData.find(item => item.id == id);
        if (isChecked) t.payments_year.push(month);
        else t.payments_year = t.payments_year.filter(m => m !== month);

        // Envia para API
        await apiFetch('/sys/admin/financial/pay', {
            method: 'POST',
            body: JSON.stringify({ id, month, year, action: isChecked ? 'add' : 'remove' })
        });
        
        // Atualiza tabela principal
        renderTable(currentData);
    }

    window.closeModal = (id) => {
        document.getElementById(id).classList.add('hidden');
    }

    // Inicialização
    loadFinance();
</script>