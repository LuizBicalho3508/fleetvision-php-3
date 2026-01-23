<div class="p-6 space-y-6 print:p-0">

    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 print:hidden">
        <div class="flex items-center gap-2 mb-4 border-b border-slate-100 pb-2">
            <i class="fas fa-file-alt text-blue-600"></i>
            <h2 class="font-bold text-slate-700">Gerador de Relatórios</h2>
        </div>

        <form id="reportForm" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Tipo de Relatório</label>
                <select name="type" id="repType" class="w-full border p-2 rounded-lg bg-slate-50 outline-none focus:border-blue-500">
                    <option value="summary">Resumo Gerencial (KM)</option>
                    <option value="events">Histórico de Eventos</option>
                    <option value="route">Log de Rota</option>
                </select>
            </div>
            
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Veículo</label>
                <select name="device_id" id="repDevice" class="w-full border p-2 rounded-lg bg-slate-50 outline-none focus:border-blue-500">
                    <option value="">Carregando...</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Início</label>
                    <input type="datetime-local" name="start" id="repStart" class="w-full border p-2 rounded-lg bg-slate-50 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Fim</label>
                    <input type="datetime-local" name="end" id="repEnd" class="w-full border p-2 rounded-lg bg-slate-50 text-xs">
                </div>
            </div>

            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-lg shadow-blue-200 transition h-[38px]">
                <i class="fas fa-sync-alt mr-1"></i> Gerar
            </button>
        </form>
    </div>

    <div id="reportResult" class="hidden bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden min-h-[300px] print:shadow-none print:border-0">
        
        <div class="p-6 border-b border-slate-100 bg-slate-50/50 print:bg-white print:p-0 print:mb-4">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800" id="resTitle">Título do Relatório</h1>
                    <div class="mt-2 space-y-1 text-sm text-slate-600">
                        <p><span class="font-bold text-slate-400">Veículo:</span> <span id="resVehicle">---</span></p>
                        <p><span class="font-bold text-slate-400">Período:</span> <span id="resPeriod">---</span></p>
                        <p class="print:block hidden"><span class="font-bold text-slate-400">Gerado em:</span> <?php echo date('d/m/Y H:i'); ?></p>
                    </div>
                </div>
                
                <div class="flex gap-2 print:hidden">
                    <button onclick="window.print()" class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-3 py-2 rounded-lg font-bold text-xs flex items-center gap-2 transition">
                        <i class="fas fa-print"></i> Imprimir
                    </button>
                    <button onclick="exportCSV()" class="bg-green-100 hover:bg-green-200 text-green-700 px-3 py-2 rounded-lg font-bold text-xs flex items-center gap-2 transition">
                        <i class="fas fa-file-excel"></i> CSV
                    </button>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase font-bold border-b border-slate-100 print:bg-white print:border-black">
                    <tr id="resHeader">
                        </tr>
                </thead>
                <tbody id="resBody" class="divide-y divide-slate-100 print:divide-slate-200">
                    </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // 1. Setup Inicial
    const now = new Date();
    const start = new Date(); start.setHours(0,0,0,0);
    const toISO = (d) => d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0') + 'T' + String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0');
    
    document.getElementById('repStart').value = toISO(start);
    document.getElementById('repEnd').value = toISO(now);

    let currentReportData = []; // Para exportação CSV

    // 2. Carregar Veículos
    async function loadVehicles() {
        const res = await apiFetch('/api/dashboard/data');
        const sel = document.getElementById('repDevice');
        sel.innerHTML = '';
        res.forEach(v => sel.innerHTML += `<option value="${v.id}">${v.plate} - ${v.name}</option>`);
    }
    loadVehicles();

    // 3. Gerar Relatório
    document.getElementById('reportForm').onsubmit = async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gerando...';
        btn.disabled = true;

        const params = new URLSearchParams(new FormData(e.target));
        // Ajuste manual de formato de data se necessário, mas ISO costuma passar bem
        
        try {
            const res = await apiFetch(`/api/reports/generate?${params.toString()}`);
            
            if(res.success) {
                renderReport(res);
                currentReportData = res; // Guarda para CSV
            } else {
                showToast(res.error || 'Erro ao gerar relatório', 'error');
            }
        } catch(err) {
            console.error(err);
            showToast('Erro de conexão', 'error');
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    };

    function renderReport(data) {
        document.getElementById('reportResult').classList.remove('hidden');
        document.getElementById('resTitle').innerText = data.title;
        document.getElementById('resVehicle').innerText = data.vehicle;
        document.getElementById('resPeriod').innerText = data.period;

        // Cabeçalho
        const thead = document.getElementById('resHeader');
        thead.innerHTML = data.columns.map(c => `<th class="px-6 py-3">${c}</th>`).join('');

        // Corpo
        const tbody = document.getElementById('resBody');
        tbody.innerHTML = data.data.map(row => {
            // Se row for array (índice numérico) ou objeto
            // A API retorna array de objetos, vamos iterar na ordem das colunas se possível, 
            // mas aqui simplifico assumindo que Object.values respeita a ordem ou ajustamos no Controller.
            // O Controller retorna chaves nomeadas. Vamos mapear valores.
            const values = Object.values(row);
            return `<tr class="print:break-inside-avoid">
                ${values.map(v => `<td class="px-6 py-3 font-medium text-slate-700">${v}</td>`).join('')}
            </tr>`;
        }).join('');
    }

    // 4. Exportar CSV
    window.exportCSV = function() {
        if(!currentReportData || !currentReportData.data) return;
        
        const cols = currentReportData.columns;
        const rows = currentReportData.data.map(r => Object.values(r));
        
        let csvContent = "data:text/csv;charset=utf-8,";
        csvContent += cols.join(",") + "\r\n"; // Header
        
        rows.forEach(rowArray => {
            const row = rowArray.map(v => `"${v}"`).join(","); // Aspas para segurança
            csvContent += row + "\r\n";
        });

        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", `relatorio_${Date.now()}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
</script>