<div class="p-6 space-y-6 print:p-0 print:space-y-0">

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 print:hidden">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Relatórios Avançados</h1>
            <p class="text-sm text-slate-500">Analise o desempenho e histórico da frota.</p>
        </div>
    </div>

    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 print:hidden">
        <div class="flex items-center gap-2 mb-4 border-b border-slate-100 pb-2">
            <i class="fas fa-filter text-blue-600"></i>
            <h2 class="font-bold text-slate-700 text-sm uppercase tracking-wider">Configurar Relatório</h2>
        </div>

        <form id="reportForm" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Tipo</label>
                <div class="relative">
                    <i class="fas fa-file-alt absolute left-3 top-3 text-slate-400"></i>
                    <select name="type" id="repType" class="w-full pl-9 pr-3 py-2 border border-slate-200 rounded-lg bg-slate-50 outline-none focus:border-blue-500 text-sm appearance-none cursor-pointer">
                        <option value="summary">Resumo Gerencial (KM e Velocidade)</option>
                        <option value="events">Histórico de Eventos e Alertas</option>
                        <option value="route">Log de Rota (Ponto a Ponto)</option>
                    </select>
                </div>
            </div>
            
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Veículo</label>
                <div class="relative">
                    <i class="fas fa-truck absolute left-3 top-3 text-slate-400"></i>
                    <select name="device_id" id="repDevice" class="w-full pl-9 pr-3 py-2 border border-slate-200 rounded-lg bg-slate-50 outline-none focus:border-blue-500 text-sm appearance-none cursor-pointer">
                        <option value="">Carregando frota...</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Início</label>
                    <input type="datetime-local" name="start" id="repStart" class="w-full border border-slate-200 p-2 rounded-lg bg-slate-50 text-xs outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Fim</label>
                    <input type="datetime-local" name="end" id="repEnd" class="w-full border border-slate-200 p-2 rounded-lg bg-slate-50 text-xs outline-none focus:border-blue-500">
                </div>
            </div>

            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-lg shadow-blue-200 transition h-[38px] flex items-center justify-center gap-2">
                <i class="fas fa-search"></i> Gerar Relatório
            </button>
        </form>
    </div>

    <div id="reportResult" class="hidden bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden min-h-[400px] print:shadow-none print:border-0 print:block">
        
        <div class="p-6 border-b border-slate-100 bg-slate-50/50 print:bg-white print:p-0 print:mb-4 print:border-b-2 print:border-black">
            <div class="flex justify-between items-start">
                <div class="flex items-start gap-4">
                    <div class="hidden print:block">
                        </div>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-800 print:text-black" id="resTitle">Título do Relatório</h1>
                        <div class="mt-2 space-y-1 text-sm text-slate-600 print:text-black">
                            <p><span class="font-bold text-slate-400 print:text-black">Veículo:</span> <span id="resVehicle">---</span></p>
                            <p><span class="font-bold text-slate-400 print:text-black">Período:</span> <span id="resPeriod">---</span></p>
                            <p class="print:block hidden text-xs mt-2 text-slate-400">Gerado em: <?php echo date('d/m/Y H:i'); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="flex gap-2 print:hidden">
                    <button onclick="window.print()" class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-3 py-2 rounded-lg font-bold text-xs flex items-center gap-2 transition border border-slate-200">
                        <i class="fas fa-print"></i> Imprimir
                    </button>
                    <button onclick="exportCSV()" class="bg-green-100 hover:bg-green-200 text-green-700 px-3 py-2 rounded-lg font-bold text-xs flex items-center gap-2 transition border border-green-200">
                        <i class="fas fa-file-excel"></i> Excel / CSV
                    </button>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase font-bold border-b border-slate-100 print:bg-white print:text-black print:border-black">
                    <tr id="resHeader">
                        </tr>
                </thead>
                <tbody id="resBody" class="divide-y divide-slate-100 print:divide-slate-200">
                    <tr><td class="p-10 text-center text-slate-400">Gere um relatório acima para ver os dados.</td></tr>
                </tbody>
            </table>
        </div>
        
        <div class="p-4 bg-slate-50 border-t border-slate-100 text-xs text-slate-400 text-center print:bg-white print:text-black print:mt-4">
            Sistema FleetVision &copy; <?php echo date('Y'); ?>
        </div>
    </div>
</div>

<script>
    // 1. Configuração Inicial
    const now = new Date();
    const start = new Date(); start.setHours(0,0,0,0);
    
    // Formata Data Local para Input datetime-local
    const toISO = (d) => {
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        const hours = String(d.getHours()).padStart(2, '0');
        const mins = String(d.getMinutes()).padStart(2, '0');
        return `${year}-${month}-${day}T${hours}:${mins}`;
    };
    
    document.getElementById('repStart').value = toISO(start);
    document.getElementById('repEnd').value = toISO(now);

    let currentReportData = null; // Armazena dados para exportação

    // 2. Carregar Lista de Veículos
    async function loadVehicles() {
        const sel = document.getElementById('repDevice');
        try {
            const res = await apiFetch('/api/dashboard/data'); // Usa endpoint cacheado de frota
            sel.innerHTML = '';
            
            if (res && res.length > 0) {
                res.forEach(v => {
                    sel.innerHTML += `<option value="${v.id}">${v.plate} - ${v.name}</option>`;
                });
            } else {
                sel.innerHTML = '<option value="">Nenhum veículo encontrado</option>';
            }
        } catch (e) {
            sel.innerHTML = '<option value="">Erro ao carregar</option>';
            console.error(e);
        }
    }
    loadVehicles();

    // 3. Gerar Relatório (Submit)
    document.getElementById('reportForm').onsubmit = async (e) => {
        e.preventDefault();
        
        const btn = e.target.querySelector('button[type="submit"]');
        const originalContent = btn.innerHTML;
        
        // Estado de Carregamento
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
        btn.disabled = true;
        btn.classList.add('opacity-75');

        const params = new URLSearchParams(new FormData(e.target));
        
        try {
            // Ajusta segundos se necessário, mas o controller aceita Y-m-d H:i
            const res = await apiFetch(`/api/reports/generate?${params.toString()}`);
            
            if(res.success) {
                currentReportData = res;
                renderReport(res);
                showToast('Relatório gerado com sucesso!', 'success');
            } else {
                showToast(res.error || 'Erro ao gerar dados.', 'error');
            }
        } catch(err) {
            console.error(err);
            showToast('Erro de conexão com o servidor.', 'error');
        } finally {
            // Restaura Botão
            btn.innerHTML = originalContent;
            btn.disabled = false;
            btn.classList.remove('opacity-75');
        }
    };

    // 4. Renderizar Tabela
    function renderReport(data) {
        const container = document.getElementById('reportResult');
        container.classList.remove('hidden');
        
        // Scroll suave até o resultado
        container.scrollIntoView({ behavior: 'smooth', block: 'start' });

        // Preenche Infos
        document.getElementById('resTitle').innerText = data.title;
        document.getElementById('resVehicle').innerText = data.vehicle;
        document.getElementById('resPeriod').innerText = data.period;

        // Preenche Cabeçalho
        const thead = document.getElementById('resHeader');
        thead.innerHTML = data.columns.map(c => `<th class="px-6 py-3 whitespace-nowrap">${c}</th>`).join('');

        // Preenche Corpo
        const tbody = document.getElementById('resBody');
        
        if (data.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="100%" class="p-8 text-center text-slate-400">Nenhum registro encontrado para este período.</td></tr>';
            return;
        }

        tbody.innerHTML = data.data.map((row, index) => {
            // Zebra striping para impressão
            const bgClass = index % 2 === 0 ? 'bg-white' : 'bg-slate-50 print:bg-slate-100';
            
            // Tratamento dinâmico das colunas
            const cells = Object.values(row).map(val => `
                <td class="px-6 py-3 font-medium text-slate-700 border-b border-slate-50 print:border-slate-300 print:text-black">
                    ${val}
                </td>
            `).join('');
            
            return `<tr class="${bgClass} print:break-inside-avoid">${cells}</tr>`;
        }).join('');
    }

    // 5. Exportar CSV
    window.exportCSV = function() {
        if(!currentReportData || !currentReportData.data || currentReportData.data.length === 0) {
            showToast('Gere um relatório com dados primeiro.', 'warning');
            return;
        }
        
        const cols = currentReportData.columns;
        const rows = currentReportData.data.map(r => Object.values(r));
        
        // BOM para Excel reconhecer UTF-8
        let csvContent = "\uFEFF"; 
        
        // Cabeçalho
        csvContent += cols.join(";") + "\r\n";
        
        // Linhas
        rows.forEach(rowArray => {
            const row = rowArray.map(v => {
                // Escapa aspas e remove quebras de linha
                let str = String(v).replace(/"/g, '""'); 
                return `"${str}"`;
            }).join(";");
            csvContent += row + "\r\n";
        });

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        
        const link = document.createElement("a");
        link.setAttribute("href", url);
        link.setAttribute("download", `relatorio_fleetvision_${Date.now()}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
</script>