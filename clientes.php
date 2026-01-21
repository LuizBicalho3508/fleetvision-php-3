<?php
// clientes.php - Versão Blindada (Safe Mode) & Cálculo Automático
if (session_status() === PHP_SESSION_NONE) {}
if (!isset($_SESSION['user_id'])) { echo "<script>window.location.href='/admin/login';</script>"; exit; }
?>

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
    body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
    .custom-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
    .custom-scroll::-webkit-scrollbar-track { background: transparent; }
    .custom-scroll::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 3px; }
    .swal2-popup { border-radius: 16px !important; font-family: 'Inter', sans-serif; }
    .loading-pulse { animation: pulse 1.5s infinite; background-color: #f1f5f9; color: transparent; border-radius: 4px; }
    @keyframes pulse { 0% { opacity: 0.6; } 50% { opacity: 1; } 100% { opacity: 0.6; } }
</style>

<div class="h-screen flex flex-col overflow-hidden">
    
    <div class="bg-white border-b border-gray-200 px-8 py-5 flex justify-between items-center z-10 shadow-sm shrink-0">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Gestão de Clientes</h1>
            <p class="text-sm text-gray-500 mt-0.5">Contratos, Veículos e Financeiro.</p>
        </div>
        <button onclick="openModalCliente()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl font-semibold text-sm shadow-lg shadow-indigo-100 transition transform active:scale-95 flex items-center gap-2">
            <i class="fas fa-plus"></i> Novo Cliente
        </button>
    </div>

    <div class="flex-1 overflow-y-auto custom-scroll p-8">
        <div class="max-w-[1800px] mx-auto space-y-6">
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between"><div><p class="text-xs text-gray-400 font-bold uppercase">Total Clientes</p><h3 class="text-2xl font-bold text-gray-800 mt-1" id="kpi-total">0</h3></div><div class="p-3 bg-blue-50 text-blue-600 rounded-lg"><i class="fas fa-users text-lg"></i></div></div>
                <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between"><div><p class="text-xs text-gray-400 font-bold uppercase">Receita (Total)</p><h3 class="text-2xl font-bold text-emerald-600 mt-1" id="kpi-mrr">R$ 0,00</h3></div><div class="p-3 bg-emerald-50 text-emerald-600 rounded-lg"><i class="fas fa-money-bill-wave text-lg"></i></div></div>
                <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between"><div><p class="text-xs text-gray-400 font-bold uppercase">Vencidos</p><h3 class="text-2xl font-bold text-amber-600 mt-1" id="kpi-expired">0</h3></div><div class="p-3 bg-amber-50 text-amber-600 rounded-lg"><i class="fas fa-clock text-lg"></i></div></div>
                <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between"><div><p class="text-xs text-gray-400 font-bold uppercase">Inadimplentes</p><h3 class="text-2xl font-bold text-red-600 mt-1" id="kpi-overdue">0</h3></div><div class="p-3 bg-red-50 text-red-600 rounded-lg"><i class="fas fa-exclamation-triangle text-lg"></i></div></div>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm flex flex-col">
                <div class="p-5 border-b border-gray-100 flex flex-col md:flex-row gap-4 justify-between items-center bg-gray-50/30 rounded-t-2xl">
                    <div class="relative w-full md:w-96 group">
                        <i class="fas fa-search absolute left-4 top-3.5 text-gray-400 group-focus-within:text-indigo-500 transition"></i>
                        <input type="text" id="search-input" onkeyup="applyFilters()" placeholder="Buscar..." class="w-full pl-11 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-100 focus:border-indigo-500 outline-none text-sm transition">
                    </div>
                    <div class="flex gap-3">
                        <select id="filter-status" onchange="applyFilters()" class="px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm text-gray-600 focus:border-indigo-500 outline-none cursor-pointer"><option value="all">Status: Todos</option><option value="ok">Em Dia</option><option value="overdue">Inadimplentes</option></select>
                        <select id="filter-asaas" onchange="applyFilters()" class="px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm text-gray-600 focus:border-indigo-500 outline-none cursor-pointer"><option value="all">Integração: Todas</option><option value="linked">Vinculados</option><option value="unlinked">Não Vinculados</option></select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                            <tr>
                                <th class="p-5 pl-6">Cliente</th>
                                <th class="p-5 text-center">Frota</th>
                                <th class="p-5">Financeiro (Asaas/Contrato)</th>
                                <th class="p-5">Vigência</th>
                                <th class="p-5 text-center">Status</th>
                                <th class="p-5 pr-6 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="lista-clientes" class="text-sm divide-y divide-gray-100 text-gray-600 bg-white"></tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-gray-100 bg-gray-50/50 rounded-b-2xl flex justify-between items-center text-xs text-gray-500"><span id="footer-count">Carregando...</span></div>
            </div>
        </div>
    </div>
</div>

<div id="modal-cliente" class="fixed inset-0 bg-gray-900/50 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm transition-opacity opacity-0">
    <div class="bg-white w-full max-w-3xl rounded-2xl shadow-2xl transform scale-95 transition-all duration-300 flex flex-col max-h-[90vh]" id="modal-cliente-content">
        <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50 rounded-t-2xl">
            <h3 class="font-bold text-lg text-gray-800" id="modal-title">Novo Cliente</h3>
            <button onclick="closeModal('modal-cliente')" class="text-gray-400 hover:text-red-500 transition"><i class="fas fa-times text-xl"></i></button>
        </div>
        
        <div class="p-6 overflow-y-auto custom-scroll">
            <form id="form-cliente" onsubmit="saveClient(event)" class="space-y-6">
                <input type="hidden" id="client-id">
                <input type="hidden" id="current-vehicle-count" value="0"> <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="md:col-span-2"><label class="block text-xs font-bold text-gray-600 mb-1.5 ml-1">NOME COMPLETO</label><input type="text" id="client-name" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-indigo-500 outline-none font-semibold text-gray-700"></div>
                    <div><label class="block text-xs font-bold text-gray-600 mb-1.5 ml-1">CPF/CNPJ</label><input type="text" id="client-doc" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-indigo-500 outline-none mask-doc"></div>
                    <div><label class="block text-xs font-bold text-gray-600 mb-1.5 ml-1">TELEFONE</label><input type="text" id="client-phone" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-indigo-500 outline-none mask-phone"></div>
                    <div><label class="block text-xs font-bold text-gray-600 mb-1.5 ml-1">EMAIL</label><input type="email" id="client-email" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-indigo-500 outline-none"></div>
                    <div><label class="block text-xs font-bold text-gray-600 mb-1.5 ml-1">ENDEREÇO</label><input type="text" id="client-address" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-indigo-500 outline-none"></div>
                </div>

                <div class="bg-indigo-50/50 p-5 rounded-xl border border-indigo-100">
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="text-xs font-bold text-indigo-800 uppercase flex items-center gap-2"><i class="fas fa-file-invoice-dollar"></i> Contrato</h4>
                        <span class="text-[10px] text-indigo-600 bg-indigo-100 px-2 py-1 rounded font-bold">Veículos Ativos: <span id="display-vehicle-count">0</span></span>
                    </div>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-xs font-bold text-indigo-900 mb-1.5">PREÇO UNITÁRIO</label>
                            <input type="number" step="0.01" id="client-unit-price" oninput="calculateTotal()" class="w-full px-3 py-2 rounded-lg border border-indigo-200 bg-white font-bold text-emerald-600 text-sm focus:ring-2 focus:ring-emerald-200 outline-none">
                        </div>
                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-xs font-bold text-indigo-900 mb-1.5">VALOR TOTAL</label>
                            <input type="number" step="0.01" id="client-value" class="w-full px-3 py-2 rounded-lg border border-indigo-200 bg-gray-50 font-bold text-indigo-700 text-sm" readonly>
                        </div>
                        <div class="col-span-2 md:col-span-1"><label class="block text-xs font-bold text-indigo-900 mb-1.5">VENCIMENTO</label><select id="client-day" class="w-full px-3 py-2 rounded-lg border border-indigo-200 bg-white cursor-pointer text-sm"><option value="5">05</option><option value="10" selected>10</option><option value="15">15</option><option value="20">20</option><option value="25">25</option><option value="30">30</option></select></div>
                        <div class="col-span-2 md:col-span-1"><label class="block text-xs font-bold text-indigo-900 mb-1.5">FIM VIGÊNCIA</label><input type="date" id="client-end" class="w-full px-3 py-2 rounded-lg border border-indigo-200 bg-white text-sm"></div>
                        <input type="hidden" id="client-start">
                    </div>
                </div>

                <div id="manual-status-container" class="bg-amber-50 p-4 rounded-xl border border-amber-100 hidden">
                    <label class="block text-xs font-bold text-amber-800 mb-2 uppercase">Status Manual</label>
                    <select id="client-status-manual" class="w-full px-4 py-2.5 rounded-xl border border-amber-200 bg-white text-amber-900 font-bold cursor-pointer"><option value="ok">✅ Em Dia</option><option value="overdue">❌ Inadimplente</option></select>
                </div>

                <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-3.5 rounded-xl hover:bg-indigo-700 shadow-md">Salvar</button>
            </form>
        </div>
    </div>
</div>

<div id="modal-link-asaas" class="fixed inset-0 bg-gray-900/60 hidden z-[60] flex items-center justify-center p-4 backdrop-blur-sm transition-opacity opacity-0"><div class="bg-white w-full max-w-md rounded-2xl shadow-2xl transform scale-95 transition-all duration-300" id="modal-link-content"><div class="p-6 border-b border-gray-100 bg-gray-50 rounded-t-2xl"><h3 class="font-bold text-lg text-gray-800">Vincular Asaas</h3></div><div class="p-6 space-y-4"><input type="hidden" id="link-local-id"><div class="relative"><input type="text" id="search-asaas" placeholder="Buscar no Asaas..." class="w-full pl-10 px-4 py-3 border border-gray-200 rounded-xl outline-none text-sm"><i class="fas fa-search absolute left-3.5 top-3.5 text-gray-400"></i><div id="asaas-results" class="absolute w-full bg-white border border-gray-200 rounded-xl mt-2 shadow-2xl max-h-48 overflow-y-auto hidden z-50"></div></div><div id="selected-asaas-info" class="hidden bg-indigo-50 p-3 rounded-xl border border-indigo-100 flex items-center gap-3"><div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center shrink-0"><i class="fas fa-user-check"></i></div><div class="overflow-hidden"><p class="text-xs font-bold text-indigo-900 truncate" id="sel-asaas-name">Nome</p><p class="text-[10px] text-indigo-600 truncate font-mono" id="sel-asaas-doc">Doc</p></div><input type="hidden" id="sel-asaas-id"></div><div class="flex gap-3 pt-2"><button onclick="closeModal('modal-link-asaas')" class="w-1/2 py-3 rounded-xl border border-gray-200 font-bold text-sm">Cancelar</button><button onclick="saveLink()" class="w-1/2 bg-indigo-600 text-white font-bold py-3 rounded-xl hover:bg-indigo-700 shadow-md">Confirmar</button></div></div></div></div>

<script>
    // --- SAFE UTILS (FUNÇÕES DE SEGURANÇA PARA EVITAR TRAVAMENTO) ---
    function setVal(id, value) {
        const el = document.getElementById(id);
        if (el) el.value = (value === null || value === undefined) ? '' : value;
    }
    
    function getVal(id) {
        const el = document.getElementById(id);
        return el ? el.value : '';
    }

    // --- SETUP ---
    $(document).ready(function(){
        $('.mask-phone').mask('(00) 00000-0000');
        var DocBehavior = function (val) { return val.replace(/\D/g, '').length === 11 ? '000.000.000-009' : '00.000.000/0000-00'; }, docOptions = { onKeyPress: function(val, e, field, options) { field.mask(DocBehavior.apply({}, arguments), options); } };
        $('.mask-doc').mask(DocBehavior, docOptions);
    });

    let allClients = [];
    let searchTimeout;

    document.addEventListener('DOMContentLoaded', loadClientes);

    async function loadClientes() {
        const tbody = document.getElementById('lista-clientes');
        try {
            const res = await fetch('/api_clientes.php?action=get_customers');
            const json = await res.json();
            if(!json.success) throw new Error(json.error);
            allClients = json.data || [];
            updateStats(); applyFilters();
        } catch(e) { tbody.innerHTML = `<tr><td colspan="6" class="p-10 text-center text-red-500">${e.message}</td></tr>`; }
    }

    // --- CÁLCULO AUTOMÁTICO (SAFE MODE) ---
    function calculateTotal() {
        // Usa getVal para não crashar se o campo não existir
        const price = parseFloat(getVal('client-unit-price')) || 0;
        const count = parseInt(getVal('current-vehicle-count')) || 0;
        const total = count > 0 ? price * count : 0;
        
        // Só atualiza se o campo total existir
        setVal('client-value', total.toFixed(2));
    }

    // --- RENDERIZAÇÃO INTELIGENTE (COM ASAAS FETCH) ---
    function renderTable(data) {
        const tbody = document.getElementById('lista-clientes');
        document.getElementById('footer-count').innerText = `Mostrando ${data.length} cliente(s)`;
        if(data.length === 0) { tbody.innerHTML = '<tr><td colspan="6" class="p-16 text-center text-gray-400">Vazio.</td></tr>'; return; }
        const today = new Date();

        tbody.innerHTML = data.map(c => {
            // Vigência
            let vigenciaHtml = '<span class="text-gray-400 text-xs">-</span>';
            if (c.contract_end) {
                try {
                    const parts = c.contract_end.split(' ')[0].split('-');
                    const end = new Date(parts[0], parts[1]-1, parts[2]); 
                    const diffTime = end - today;
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
                    if (diffDays < 0) vigenciaHtml = `<span class="bg-red-100 text-red-700 px-2 py-1 rounded text-[10px] font-bold">VENCIDO</span>`;
                    else if (diffDays < 30) vigenciaHtml = `<span class="bg-amber-100 text-amber-700 px-2 py-1 rounded text-[10px] font-bold">VENCE EM ${diffDays}d</span>`;
                    else vigenciaHtml = `<span class="bg-emerald-100 text-emerald-700 px-2 py-1 rounded text-[10px] font-bold">VIGENTE</span>`;
                } catch(e){}
            }

            // Nome
            let nameHtml = `<div class="font-bold text-gray-800 text-sm">${c.name}</div><div class="text-xs text-gray-400 mt-0.5 font-mono">${c.document||'-'}</div>`;
            
            // Coluna Financeira (Híbrida: Valor Local ou Carregando Asaas)
            let financeHtml = `<div class="font-bold text-gray-700 text-sm">R$ ${parseFloat(c.contract_value||0).toLocaleString('pt-BR',{minimumFractionDigits:2})}</div><div class="text-[10px] text-gray-400 font-bold uppercase mt-0.5">Venc. ${c.due_day||10} (Contrato)</div>`;

            if (c.asaas_customer_id) {
                const aName = c.asaas_customer_name || c.asaas_customer_id;
                nameHtml = `<div class="flex flex-col"><div class="font-bold text-gray-800 text-sm flex items-center gap-1">${aName} <i class="fas fa-check-circle text-blue-500 text-[10px]"></i></div><div class="text-[10px] text-gray-400 mt-0.5">Local: ${c.name}</div></div>`;
                
                // Loader visual para o valor do boleto
                financeHtml = `<div id="asaas-val-${c.id}" class="loading-pulse h-4 w-24 mb-1"></div><div id="asaas-date-${c.id}" class="text-[10px] text-gray-400">...</div>`;
            }

            let veiculosHtml = `<div class="flex items-center justify-center gap-2"><span class="font-bold text-gray-700 text-lg">${c.active_vehicles_count}</span><button onclick="showProRata(${c.id})" class="text-indigo-500 hover:text-indigo-700 bg-indigo-50 p-1.5 rounded-lg transition"><i class="fas fa-calculator text-xs"></i></button></div>`;

            let st = {cls:'bg-gray-100 text-gray-500', icon:'fa-circle', txt:'N/A'};
            if(c.financial_status === 'ok') st = {cls:'bg-emerald-50 text-emerald-700', icon:'fa-check', txt:'EM DIA'};
            if(c.financial_status === 'overdue') st = {cls:'bg-red-50 text-red-700', icon:'fa-times', txt:'DEVEDOR'};

            let linkAction = c.asaas_customer_id ? `<span class="text-[10px] font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded">VINCULADO</span>` : `<button onclick="openLinkModal(${c.id})" class="text-xs font-bold text-gray-500 hover:text-indigo-600 border border-gray-200 px-3 py-1.5 rounded flex items-center gap-1 bg-white"><i class="fas fa-plug text-[10px]"></i> Conectar</button>`;

            return `
            <tr class="hover:bg-gray-50/80 transition border-b border-gray-100 group">
                <td class="p-5 pl-6">${nameHtml}</td>
                <td class="p-5 text-center">${veiculosHtml}</td>
                <td class="p-5">${financeHtml}</td>
                <td class="p-5"><div class="mb-1">${vigenciaHtml}</div><div class="text-[10px] text-gray-400">Fim: ${c.contract_end ? c.contract_end.split(' ')[0].split('-').reverse().join('/') : '-'}</div></td>
                <td class="p-5 text-center"><span class="${st.cls} px-3 py-1 rounded-full text-[10px] font-bold uppercase inline-flex items-center gap-1.5">${st.txt}</span></td>
                <td class="p-5 pr-6 text-right flex items-center justify-end gap-3 mt-3">${linkAction}<button onclick="editClient(${c.id})" class="text-gray-400 hover:text-blue-600"><i class="fas fa-pen"></i></button><button onclick="deleteClient(${c.id})" class="text-gray-400 hover:text-red-600"><i class="fas fa-trash"></i></button></td>
            </tr>`;
        }).join('');

        // Busca valores reais no Asaas (Assíncrono)
        fetchAsaasData(data);
    }

    // --- FETCH ASAAS (Sem travar a tabela) ---
    async function fetchAsaasData(clients) {
        for (const c of clients) {
            if (c.asaas_customer_id) {
                try {
                    const res = await fetch(`/api_financeiro.php?action=asaas_proxy&asaas_endpoint=/payments&customer=${c.asaas_customer_id}&limit=1&sort=dueDate&order=asc&status=PENDING,OVERDUE`);
                    const json = await res.json();
                    
                    const elVal = document.getElementById(`asaas-val-${c.id}`);
                    const elDate = document.getElementById(`asaas-date-${c.id}`);
                    
                    if (elVal && json.data && json.data.length > 0) {
                        const pay = json.data[0]; // Pega o boleto pendente/vencido mais antigo
                        const valFmt = pay.value.toLocaleString('pt-BR', {minimumFractionDigits:2});
                        const dateFmt = new Date(pay.dueDate).toLocaleDateString('pt-BR');
                        const isOver = pay.status === 'OVERDUE';
                        
                        elVal.className = isOver ? "font-bold text-red-600 text-sm" : "font-bold text-gray-700 text-sm";
                        elVal.innerHTML = `R$ ${valFmt} <i class="fas fa-bolt text-amber-500 text-[10px]" title="Via Asaas"></i>`;
                        
                        elDate.innerHTML = `<span class="${isOver ? 'text-red-500 font-bold' : 'text-gray-500'}">Venc. ${dateFmt} (${pay.status})</span>`;
                    } else if (elVal) {
                        elVal.className = "text-emerald-600 text-xs font-bold";
                        elVal.innerHTML = '<i class="fas fa-check"></i> Tudo Pago';
                        elDate.innerText = "Sem pendências";
                    }
                } catch(e) { console.log("Erro Asaas Fetch", e); }
            }
        }
    }

    // --- MODAL ---
    function openModalCliente(id = null) {
        if(document.getElementById('form-cliente')) document.getElementById('form-cliente').reset();
        setVal('client-id', '');
        setVal('current-vehicle-count', '0');
        if(document.getElementById('display-vehicle-count')) document.getElementById('display-vehicle-count').innerText = '0';
        if(document.getElementById('client-value')) document.getElementById('client-value').readOnly = true;

        if(document.getElementById('modal-title')) document.getElementById('modal-title').innerText = 'Novo Cliente';
        const manualDiv = document.getElementById('manual-status-container');
        if(manualDiv) manualDiv.classList.remove('hidden');
        
        if (id) {
            const c = allClients.find(x => x.id == id);
            if(c) {
                setVal('client-id', c.id);
                setVal('client-name', c.name);
                setVal('client-doc', c.document);
                setVal('client-phone', c.phone);
                setVal('client-email', c.email);
                setVal('client-address', c.address);
                setVal('client-value', c.contract_value);
                setVal('client-unit-price', c.unit_price); 
                setVal('client-day', c.due_day);
                
                // Veículos e Cálculo
                setVal('current-vehicle-count', c.active_vehicles_count);
                if(document.getElementById('display-vehicle-count')) document.getElementById('display-vehicle-count').innerText = c.active_vehicles_count;

                // Safe Dates
                try { if(c.contract_start) setVal('client-start', c.contract_start.split('T')[0]); } catch(e){}
                try { if(c.contract_end) setVal('client-end', c.contract_end.split('T')[0]); } catch(e){}
                
                setVal('client-status-manual', c.financial_status || 'ok');
                if(document.getElementById('modal-title')) document.getElementById('modal-title').innerText = 'Editar Cliente';

                if (c.asaas_customer_id && manualDiv) manualDiv.classList.add('hidden');
            }
        }
        
        const m = document.getElementById('modal-cliente');
        m.classList.remove('hidden');
        setTimeout(() => { m.classList.remove('opacity-0'); document.getElementById('modal-cliente-content').classList.remove('scale-95'); }, 10);
    }
    
    window.editClient = (id) => openModalCliente(id);

    async function saveClient(e) {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        btn.innerHTML = '...'; btn.disabled = true;

        // Recalcula total antes de enviar
        calculateTotal();

        const data = {
            id: getVal('client-id'),
            name: getVal('client-name'),
            document: getVal('client-doc'),
            phone: getVal('client-phone'),
            email: getVal('client-email'),
            address: getVal('client-address'),
            contract_value: getVal('client-value'),
            unit_price: getVal('client-unit-price'),
            due_day: getVal('client-day'),
            contract_start: getVal('client-start'),
            contract_end: getVal('client-end'),
        };

        const manualDiv = document.getElementById('manual-status-container');
        if (manualDiv && !manualDiv.classList.contains('hidden')) {
            data.financial_status = getVal('client-status-manual');
        }

        try {
            const res = await fetch('/api_clientes.php?action=save_customer', { method: 'POST', body: JSON.stringify(data) });
            const json = await res.json();
            if(json.success) {
                Swal.fire({ icon: 'success', title: 'Salvo!', timer: 1500, showConfirmButton: false });
                closeModal('modal-cliente');
                loadClientes();
            } else throw new Error(json.error);
        } catch(err) { Swal.fire({ icon: 'error', title: 'Erro', text: err.message }); } 
        finally { btn.innerHTML = 'Salvar'; btn.disabled = false; }
    }

    // --- CÁLCULO PRO-RATA (Mantido) ---
    async function showProRata(clientId) {
        Swal.fire({ title: 'Calculando...', didOpen: () => { Swal.showLoading() } });
        try {
            const res = await fetch(`/api_clientes.php?action=calculate_pro_rata&client_id=${clientId}`);
            const data = await res.json();
            if(!data.success) throw new Error('Erro ao calcular.');

            let html = `
                <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 text-left text-sm mb-4">
                    <div class="flex justify-between mb-1"><span>Fatura:</span> <strong>${data.billing_date}</strong></div>
                    <div class="flex justify-between mb-1"><span>Ciclo:</span> <span>${data.cycle_start} até ${data.billing_date}</span></div>
                    <div class="flex justify-between text-indigo-600 font-bold"><span>Base Unitária:</span> <span>R$ ${data.unit_price_base}</span></div>
                </div>
                <div class="max-h-60 overflow-y-auto custom-scroll border border-gray-100 rounded-xl">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-100 font-bold text-gray-500"><tr><th class="p-2">Placa</th><th class="p-2">Início</th><th class="p-2">Fim</th><th class="p-2 text-right">Valor</th></tr></thead>
                        <tbody>${data.vehicles.map(v => `<tr class="border-b border-gray-50"><td class="p-2 font-bold">${v.plate}</td><td class="p-2 text-emerald-600">${v.start_date}</td><td class="p-2 text-red-500">${v.end_date}</td><td class="p-2 text-right font-mono text-indigo-700">R$ ${v.cost}</td></tr>`).join('')}</tbody>
                    </table>
                </div>
                <div class="mt-4 flex justify-between items-center bg-indigo-600 text-white p-4 rounded-xl shadow-lg"><span class="font-bold uppercase text-xs">Total Proporcional</span><span class="font-bold text-xl">R$ ${data.total_preview}</span></div>`;
            Swal.fire({ title: 'Simulação', html: html, width: 600, showCloseButton: true, showConfirmButton: false });
        } catch(e) { Swal.fire('Erro', 'Falha ao calcular.', 'error'); }
    }

    function updateStats() {
        document.getElementById('kpi-total').innerText = allClients.length;
        document.getElementById('kpi-mrr').innerText = allClients.reduce((acc, c) => acc + parseFloat(c.contract_value||0), 0).toLocaleString('pt-BR', {style:'currency', currency:'BRL'});
        document.getElementById('kpi-overdue').innerText = allClients.filter(c => c.financial_status === 'overdue').length;
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('kpi-expired').innerText = allClients.filter(c => c.contract_end && c.contract_end < today).length;
    }

    function applyFilters() {
        const term = document.getElementById('search-input').value.toLowerCase();
        const fStatus = document.getElementById('filter-status').value;
        const filtered = allClients.filter(c => {
            const matchesText = (c.name+c.document+(c.asaas_customer_name||'')).toLowerCase().includes(term);
            const matchesStatus = fStatus === 'all' ? true : c.financial_status === fStatus;
            return matchesText && matchesStatus;
        });
        renderTable(filtered);
    }

    window.deleteClient = async (id) => {
        const r = await Swal.fire({ title: 'Excluir?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Sim' });
        if (r.isConfirmed) {
            try { await fetch('/api_clientes.php?action=delete_customer', { method: 'POST', body: JSON.stringify({id}) }); loadClientes(); Swal.fire('Excluído!', '', 'success'); } catch(e) {}
        }
    }

    // Vínculo (Mantido igual)
    window.openLinkModal = (localId) => { document.getElementById('link-local-id').value = localId; document.getElementById('search-asaas').value = ''; document.getElementById('selected-asaas-info').classList.add('hidden'); document.getElementById('asaas-results').innerHTML = ''; openModal('modal-link-asaas'); }
    document.getElementById('search-asaas').addEventListener('input', function(e) { clearTimeout(searchTimeout); const val = e.target.value; if(val.length < 3) { document.getElementById('asaas-results').classList.add('hidden'); return; } searchTimeout = setTimeout(async () => { const list = document.getElementById('asaas-results'); list.classList.remove('hidden'); list.innerHTML = '<div class="p-3 text-center text-xs text-gray-400">Buscando...</div>'; try { const res = await fetch(`/api_financeiro.php?action=asaas_proxy&asaas_endpoint=/customers&name=${encodeURIComponent(val)}&limit=5`); const json = await res.json(); if(!json.data || json.data.length === 0) { list.innerHTML = '<div class="p-3 text-center text-xs text-gray-400">Nada.</div>'; return; } list.innerHTML = json.data.map(c => `<div onclick="selectAsaas('${c.id}', '${c.name}', '${c.cpfCnpj}')" class="p-3 hover:bg-indigo-50 cursor-pointer border-b border-gray-50 flex justify-between items-center group"><div><div class="font-bold text-gray-700 text-sm">${c.name}</div><div class="text-[10px] text-gray-400 font-mono">${c.cpfCnpj || 'S/ Doc'}</div></div><i class="fas fa-chevron-right text-gray-300 group-hover:text-indigo-400 text-xs"></i></div>`).join(''); } catch(e) {} }, 500); });
    window.selectAsaas = (id, name, doc) => { document.getElementById('sel-asaas-id').value = id; document.getElementById('sel-asaas-name').innerText = name; document.getElementById('sel-asaas-doc').innerText = doc; document.getElementById('selected-asaas-info').classList.remove('hidden'); document.getElementById('asaas-results').classList.add('hidden'); document.getElementById('search-asaas').value = ''; }
    window.saveLink = async () => { const btn = document.querySelector('button[onclick="saveLink()"]'); btn.innerHTML = '...'; btn.disabled = true; const localId = document.getElementById('link-local-id').value; const asaasId = document.getElementById('sel-asaas-id').value; const asaasName = document.getElementById('sel-asaas-name').innerText; if(!localId || !asaasId) return Swal.fire('Atenção', 'Selecione um cliente.', 'warning'); try { const res = await fetch('/api_clientes.php?action=link_asaas_customer', { method: 'POST', body: JSON.stringify({ local_id: localId, asaas_id: asaasId, asaas_name: asaasName }) }); if((await res.json()).success) { Swal.fire('Sucesso', 'Vinculado!', 'success'); closeModal('modal-link-asaas'); loadClientes(); } else throw new Error("Erro"); } catch(e) { Swal.fire('Erro', 'Falha ao vincular.', 'error'); } finally { btn.innerHTML = 'Confirmar'; btn.disabled = false; } }

    function openModal(id) { document.getElementById(id).classList.remove('hidden'); setTimeout(()=>document.getElementById(id+'-content').classList.remove('scale-95'),10); }
    function closeModal(id) { document.getElementById(id+'-content').classList.add('scale-95'); setTimeout(()=>document.getElementById(id).classList.add('hidden'),300); }
</script>