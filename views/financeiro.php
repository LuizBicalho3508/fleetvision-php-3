<div class="p-6 space-y-6">

    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Minhas Faturas</h1>
            <p class="text-sm text-slate-500">Gerencie seus pagamentos e mensalidades.</p>
        </div>
        
        <div class="bg-blue-50 text-blue-700 px-4 py-2 rounded-lg text-xs font-bold border border-blue-100 flex items-center gap-2">
            <i class="fas fa-info-circle"></i>
            <span>Mantenha em dia para evitar bloqueio automático.</span>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase font-bold">
                    <tr>
                        <th class="px-6 py-4">Vencimento</th>
                        <th class="px-6 py-4">Descrição</th>
                        <th class="px-6 py-4">Valor</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody id="invoiceGrid" class="divide-y divide-slate-100">
                    <tr><td colspan="5" class="p-8 text-center text-slate-400">Carregando faturas...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
            <h3 class="font-bold text-slate-700 mb-2">Dados do Contrato</h3>
            <p class="text-sm text-slate-500">Plano: <span class="font-bold text-slate-800">Rastreamento Pro</span></p>
            <p class="text-sm text-slate-500">Renovação: <span class="font-bold text-slate-800">Mensal</span></p>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-slate-700 mb-1">Forma de Pagamento</h3>
                <p class="text-xs text-slate-400">Boleto Bancário / PIX</p>
            </div>
            <i class="fas fa-barcode text-3xl text-slate-300"></i>
        </div>
    </div>
</div>

<script>
    async function loadInvoices() {
        const res = await apiFetch('/api/financeiro');
        
        const tbody = document.getElementById('invoiceGrid');

        if (!res || !res.data || res.data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="p-10 text-center">
                        <div class="text-slate-300 text-4xl mb-3"><i class="fas fa-file-invoice-dollar"></i></div>
                        <p class="text-slate-500 font-bold">Nenhuma fatura encontrada.</p>
                        <p class="text-slate-400 text-xs">Tudo certo por aqui!</p>
                    </td>
                </tr>`;
            return;
        }

        tbody.innerHTML = res.data.map(inv => {
            // Estilos de Status
            let statusClass = 'bg-slate-100 text-slate-600';
            let icon = '';
            
            if (inv.status === 'RECEIVED' || inv.status === 'CONFIRMED') {
                statusClass = 'bg-green-100 text-green-700';
                icon = '<i class="fas fa-check-circle mr-1"></i>';
            } else if (inv.status === 'OVERDUE') {
                statusClass = 'bg-red-100 text-red-700';
                icon = '<i class="fas fa-exclamation-circle mr-1"></i>';
            } else if (inv.status === 'PENDING') {
                statusClass = 'bg-yellow-50 text-yellow-700 border border-yellow-100';
                icon = '<i class="far fa-clock mr-1"></i>';
            }

            // Botão de Ação (Link para PDF/Pagamento)
            const actionBtn = (inv.status === 'PENDING' || inv.status === 'OVERDUE') 
                ? `<a href="${inv.invoice_url}" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1 justify-center shadow-blue-200 shadow-sm"><i class="fas fa-barcode"></i> Pagar</a>`
                : `<a href="${inv.invoice_url}" target="_blank" class="text-blue-500 hover:text-blue-700 text-xs font-bold"><i class="fas fa-external-link-alt"></i> Recibo</a>`;

            return `
                <tr class="hover:bg-slate-50 transition group">
                    <td class="px-6 py-4 font-mono text-xs text-slate-600 font-bold">
                        ${inv.formatted_date}
                    </td>
                    <td class="px-6 py-4 font-medium text-slate-700">
                        ${inv.description}
                    </td>
                    <td class="px-6 py-4 font-bold text-slate-800">
                        ${inv.formatted_value}
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold ${statusClass}">
                            ${icon} ${inv.status_label}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        ${actionBtn}
                    </td>
                </tr>
            `;
        }).join('');
    }

    loadInvoices();
</script>