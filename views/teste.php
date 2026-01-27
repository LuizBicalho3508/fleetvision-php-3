<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/atom-one-dark.min.css">

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/highlight.min.js"></script>

<style>
    /* Ajustes Locais */
    .hljs { background: transparent; padding: 0; font-size: 11px; font-family: 'Fira Code', monospace; line-height: 1.5; }
    .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    /* Garante que o mapa fique atrás de modais se houver */
    .leaflet-container { z-index: 0; }
</style>

<div class="flex h-[calc(100vh-64px)] w-full overflow-hidden bg-slate-50">
    
    <div class="w-80 bg-white border-r border-slate-200 flex flex-col shadow-sm z-10 h-full">
        <div class="p-4 border-b border-slate-100 bg-white">
            <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wide">Laboratório</h3>
            <p class="text-xs text-slate-400 mt-1">Selecione para auditar.</p>
            
            <div class="relative mt-3">
                <i class="fas fa-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                <input type="text" id="searchDevice" placeholder="Buscar..." 
                       class="w-full bg-slate-50 border border-slate-200 rounded-lg text-sm pl-8 p-2 outline-none focus:border-blue-500 transition">
            </div>
        </div>
        
        <div id="deviceList" class="flex-1 overflow-y-auto p-2 space-y-2 custom-scrollbar">
            <div class="text-center text-slate-400 mt-10 text-xs flex flex-col items-center">
                <i class="fas fa-circle-notch fa-spin mb-2"></i> 
                <span>Carregando...</span>
            </div>
        </div>
    </div>

    <div class="flex-1 flex flex-col relative h-full" id="mainPanel">
        
        <div id="deviceHeader" class="bg-white border-b border-slate-200 px-6 py-3 flex justify-between items-center shadow-sm hidden z-10 shrink-0">
            <div>
                <h1 class="text-xl font-bold text-slate-800 flex items-center gap-3">
                    <span id="headerModel">Modelo</span>
                    <span id="headerStatus" class="text-[10px] px-2 py-0.5 rounded bg-slate-100 text-slate-500 font-bold uppercase tracking-wider border border-slate-200">OFFLINE</span>
                </h1>
                <div class="text-xs font-mono text-slate-500 mt-1 flex gap-3">
                    <span class="flex items-center gap-1"><i class="fas fa-barcode text-slate-300"></i> <span id="headerImei">---</span></span>
                    <span class="text-slate-300">|</span>
                    <span class="flex items-center gap-1">ID: <span id="headerId">---</span></span>
                </div>
            </div>
            <div>
                <button onclick="toggleAutoRefresh()" id="btnAutoRefresh" class="text-xs font-bold text-slate-500 border border-slate-300 px-3 py-1.5 rounded hover:bg-slate-50 hover:text-blue-600 transition flex items-center gap-2 bg-white">
                    <i class="fas fa-sync"></i> <span class="hidden sm:inline">Auto-Refresh: OFF</span>
                </button>
            </div>
        </div>

        <div id="tabsContainer" class="bg-white border-b border-slate-200 px-6 flex gap-6 text-sm font-bold text-slate-500 hidden shrink-0">
            <button onclick="switchTab('dashboard')" class="tab-btn active border-b-[3px] border-blue-600 text-blue-600 py-3 transition hover:text-blue-700">Monitoramento</button>
            <button onclick="switchTab('commands')" class="tab-btn border-b-[3px] border-transparent hover:text-slate-800 hover:border-slate-300 py-3 transition">Comandos</button>
            <button onclick="switchTab('json')" class="tab-btn border-b-[3px] border-transparent hover:text-slate-800 hover:border-slate-300 py-3 transition">JSON Raw</button>
            <button onclick="switchTab('history')" class="tab-btn border-b-[3px] border-transparent hover:text-slate-800 hover:border-slate-300 py-3 transition">Histórico</button>
        </div>

        <div id="emptyState" class="flex-1 flex flex-col items-center justify-center text-slate-400 bg-slate-50">
            <div class="bg-white p-8 rounded-full shadow-sm mb-4 border border-slate-100">
                <i class="fas fa-flask text-5xl text-blue-100"></i>
            </div>
            <p class="font-bold text-slate-600">Laboratório de Testes</p>
            <p class="text-xs mt-1">Selecione um equipamento na lista.</p>
        </div>

        <div id="contentArea" class="flex-1 overflow-hidden relative hidden">
            
            <div id="tab-dashboard" class="tab-content h-full flex flex-col md:flex-row p-4 gap-4">
                <div class="flex-1 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden relative min-h-[300px]">
                    <div id="map" class="h-full w-full z-0"></div>
                </div>
                
                <div class="w-full md:w-72 bg-white rounded-xl shadow-sm border border-slate-200 flex flex-col h-full max-h-full">
                    <div class="p-3 border-b border-slate-100 font-bold text-slate-700 text-xs bg-slate-50/50 rounded-t-xl flex justify-between items-center">
                        <span>TELEMETRIA</span>
                        <i class="fas fa-satellite-dish text-blue-500 animate-pulse"></i>
                    </div>
                    <div id="telemetryGrid" class="flex-1 overflow-y-auto p-3 space-y-2 text-xs custom-scrollbar">
                        <p class="text-slate-400 text-center mt-10">Aguardando dados...</p>
                    </div>
                </div>
            </div>

            <div id="tab-commands" class="tab-content h-full p-8 hidden overflow-y-auto custom-scrollbar">
                <div class="max-w-xl mx-auto bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h3 class="font-bold text-base text-slate-800 mb-6 flex items-center gap-2 pb-4 border-b border-slate-100">
                        <i class="fas fa-terminal text-slate-400"></i> Enviar Comandos
                    </h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Tipo</label>
                            <select id="cmdType" class="w-full border border-slate-300 rounded-lg p-2 text-sm outline-none focus:border-blue-500 bg-white" onchange="toggleCustomCmd()">
                                <option value="custom">Customizado (GPRS)</option>
                                <option value="engineStop">Bloqueio (Stop)</option>
                                <option value="engineResume">Desbloqueio (Resume)</option>
                                <option value="positionSingle">Pedir Posição</option>
                                <option value="rebootDevice">Reiniciar</option>
                            </select>
                        </div>

                        <div id="customCmdArea">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Comando</label>
                            <input type="text" id="cmdString" placeholder="Ex: reboot" class="w-full border border-slate-300 rounded-lg p-2 font-mono text-sm outline-none focus:border-blue-500">
                        </div>

                        <button onclick="sendCommand()" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-bold py-2.5 rounded-lg transition flex items-center justify-center gap-2 shadow-lg shadow-slate-200 mt-2">
                            <i class="fas fa-paper-plane"></i> Enviar
                        </button>
                    </div>

                    <div id="cmdResponse" class="mt-6 hidden">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Log do Servidor</label>
                        <pre class="bg-slate-900 text-green-400 p-3 rounded-lg text-[10px] font-mono overflow-x-auto border border-slate-700 shadow-inner max-h-40 custom-scrollbar"></pre>
                    </div>
                </div>
            </div>

            <div id="tab-json" class="tab-content h-full p-0 hidden overflow-hidden bg-slate-900">
                <div class="h-full overflow-auto custom-scrollbar p-4">
                    <pre><code class="language-json" id="jsonViewer">Waiting for data...</code></pre>
                </div>
            </div>

            <div id="tab-history" class="tab-content h-full p-4 hidden flex-col gap-4">
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex gap-3 items-end">
                    <div class="flex-1">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Data</label>
                        <input type="date" id="historyDate" class="w-full border border-slate-300 rounded-lg p-2 text-sm outline-none focus:border-blue-500">
                    </div>
                    <button onclick="loadHistory()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition shadow-md text-sm">
                        Consultar
                    </button>
                </div>
                
                <div class="flex-1 bg-slate-900 rounded-xl shadow-inner border border-slate-700 overflow-hidden flex flex-col">
                    <div class="p-2 bg-slate-800 border-b border-slate-700 text-[10px] text-slate-400 font-bold flex justify-between px-3 items-center">
                        <span>LOGS TRACCAR</span>
                        <span id="historyCount" class="bg-slate-700 px-2 py-0.5 rounded text-white">0</span>
                    </div>
                    <div class="flex-1 overflow-auto p-4 custom-scrollbar">
                         <pre><code class="language-json" id="historyViewer">// Selecione data...</code></pre>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    // --- Lógica Principal ---
    let selectedDevice = null;
    let refreshTimer = null;
    let map = null;
    let marker = null;
    let devices = [];

    // Init
    async function init() {
        const res = await apiFetch('/sys/teste/stock');
        const listEl = document.getElementById('deviceList');
        
        if (!res.data || res.data.length === 0) {
            listEl.innerHTML = `<div class="p-8 text-center text-slate-400 text-xs">Estoque vazio ou sem ID Traccar.</div>`;
            return;
        }

        devices = res.data;
        renderList(devices);

        // Mapa
        map = L.map('map', { zoomControl: false }).setView([-14.2350, -51.9253], 4);
        L.control.zoom({ position: 'bottomright' }).addTo(map);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png').addTo(map);
    }

    function renderList(list) {
        const listEl = document.getElementById('deviceList');
        if(list.length === 0) {
            listEl.innerHTML = '<div class="p-4 text-center text-xs text-slate-400">Nada encontrado.</div>';
            return;
        }

        listEl.innerHTML = list.map(d => `
            <div onclick="selectDevice(${d.traccar_device_id}, '${d.imei}', '${d.model}')" 
                 class="device-item p-3 border border-slate-100 rounded-lg cursor-pointer hover:border-blue-300 hover:shadow-sm transition bg-white mb-2 group relative"
                 data-id="${d.traccar_device_id}">
                <div class="flex justify-between items-center mb-1">
                    <span class="font-bold text-slate-700 text-sm group-hover:text-blue-700 transition">${d.model}</span>
                    <span class="text-[9px] bg-slate-50 px-1.5 py-0.5 rounded text-slate-400 border border-slate-100 font-mono">ID: ${d.traccar_device_id}</span>
                </div>
                <div class="flex items-center text-[10px] text-slate-400 font-mono gap-1">
                    <i class="fas fa-barcode opacity-50"></i> ${d.imei}
                </div>
            </div>
        `).join('');
    }

    // Seleção
    function selectDevice(traccarId, imei, model) {
        selectedDevice = { id: traccarId, imei, model };

        // UI
        document.getElementById('emptyState').classList.add('hidden');
        document.getElementById('contentArea').classList.remove('hidden');
        document.getElementById('deviceHeader').classList.remove('hidden');
        document.getElementById('tabsContainer').classList.remove('hidden');

        document.getElementById('headerModel').innerText = model;
        document.getElementById('headerImei').innerText = imei;
        document.getElementById('headerId').innerText = traccarId;

        // Highlight
        document.querySelectorAll('.device-item').forEach(el => {
            el.classList.remove('border-blue-500', 'bg-blue-50', 'ring-1', 'ring-blue-500');
            el.classList.add('border-slate-100', 'bg-white');
        });
        const activeItem = document.querySelector(`.device-item[data-id="${traccarId}"]`);
        if(activeItem) {
            activeItem.classList.remove('border-slate-100', 'bg-white');
            activeItem.classList.add('border-blue-500', 'bg-blue-50', 'ring-1', 'ring-blue-500');
        }

        // Reset Data
        document.getElementById('jsonViewer').innerText = 'Aguardando dados...';
        document.getElementById('telemetryGrid').innerHTML = '<div class="h-full flex flex-col items-center justify-center text-slate-400"><i class="fas fa-satellite-dish text-xl mb-2 animate-bounce opacity-50"></i><p class="text-xs">Sincronizando...</p></div>';
        document.getElementById('cmdResponse').classList.add('hidden');

        setTimeout(() => map.invalidateSize(), 300);

        fetchLiveData();
        startAutoRefresh(); 
    }

    // Live Data
    async function fetchLiveData() {
        if(!selectedDevice) return;

        try {
            const res = await apiFetch(`/sys/teste/live?imei=${selectedDevice.imei}`);
            
            if(res.error) {
                updateStatusBadge('ERRO API', 'red');
                return;
            }

            const pos = res.position;
            const dev = res.device;

            const status = dev.status || 'unknown';
            const colors = { 'online': 'green', 'offline': 'red', 'unknown': 'slate' };
            updateStatusBadge(status.toUpperCase(), colors[status] || 'slate');

            const jsonCode = document.getElementById('jsonViewer');
            jsonCode.textContent = JSON.stringify(res.raw, null, 2);
            hljs.highlightElement(jsonCode);

            if(pos) {
                const latLng = [pos.latitude, pos.longitude];
                if(marker) marker.setLatLng(latLng);
                else marker = L.marker(latLng).addTo(map);
                map.setView(latLng, 16);
            }

            if(pos && pos.attributes) {
                const spd = (pos.speed * 1.852).toFixed(0);
                const ign = pos.attributes.ignition;
                
                let html = `
                    <div class="grid grid-cols-2 gap-2 mb-3">
                        <div class="bg-slate-50 p-2 rounded border border-slate-100 text-center">
                            <span class="block text-[9px] text-slate-400 uppercase font-bold">Velocidade</span>
                            <span class="font-bold text-xl text-slate-700 font-mono">${spd}</span>
                        </div>
                        <div class="bg-slate-50 p-2 rounded border border-slate-100 text-center">
                            <span class="block text-[9px] text-slate-400 uppercase font-bold">Ignição</span>
                            <span class="font-bold text-xl ${ign ? 'text-green-500' : 'text-slate-300'}">
                                <i class="fas fa-power-off"></i>
                            </span>
                        </div>
                    </div>
                `;
                
                for (const [key, value] of Object.entries(pos.attributes)) {
                    if(['ignition', 'distance', 'totalDistance', 'motion'].includes(key)) continue;
                    html += `
                        <div class="flex justify-between border-b border-slate-50 py-1.5 last:border-0 text-[10px]">
                            <span class="text-slate-500 font-mono">${key}</span>
                            <span class="font-bold text-slate-700 break-all ml-2 text-right">${value}</span>
                        </div>
                    `;
                }
                html += `<div class="mt-3 text-[9px] text-slate-300 text-center bg-slate-50 py-1 rounded">Time: ${new Date(pos.serverTime).toLocaleTimeString()}</div>`;
                document.getElementById('telemetryGrid').innerHTML = html;
            }

        } catch(e) { console.error(e); }
    }

    function updateStatusBadge(text, color) {
        const el = document.getElementById('headerStatus');
        el.innerText = text;
        el.className = `text-[10px] px-2 py-0.5 rounded font-bold tracking-wider bg-${color}-100 text-${color}-600 border border-${color}-200`;
    }

    function toggleCustomCmd() {
        const type = document.getElementById('cmdType').value;
        const area = document.getElementById('customCmdArea');
        if(type === 'custom') area.classList.remove('hidden');
        else area.classList.add('hidden');
    }

    async function sendCommand() {
        if(!selectedDevice) return;
        const type = document.getElementById('cmdType').value;
        const cmdStr = document.getElementById('cmdString').value;
        
        const payload = { traccar_device_id: selectedDevice.id, type: type, params: {} };
        if(type === 'custom') {
            if(!cmdStr) return showToast('Digite o comando', 'error');
            payload.params = { data: cmdStr };
        }

        const btn = document.querySelector('#tab-commands button');
        const oldHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>'; btn.disabled = true;

        try {
            const res = await apiFetch('/sys/teste/command', { method: 'POST', body: JSON.stringify(payload) });
            const respBox = document.getElementById('cmdResponse');
            const respPre = respBox.querySelector('pre');
            respBox.classList.remove('hidden');
            
            if(res.success) {
                showToast('Enviado!');
                respPre.textContent = JSON.stringify(res.result, null, 2);
                respPre.className = "bg-slate-900 text-green-400 p-3 rounded-lg text-[10px] font-mono overflow-x-auto";
            } else {
                respPre.textContent = "Erro: " + res.error;
                respPre.className = "bg-red-50 text-red-600 p-3 rounded-lg text-[10px] font-mono overflow-x-auto";
            }
        } catch(e) { showToast('Erro', 'error'); } 
        finally { btn.innerHTML = oldHtml; btn.disabled = false; }
    }

    async function loadHistory() {
        if(!selectedDevice) return;
        const date = document.getElementById('historyDate').value;
        if(!date) return showToast('Data?', 'error');

        const viewer = document.getElementById('historyViewer');
        viewer.innerText = 'Buscando...';
        
        try {
            const res = await apiFetch(`/sys/teste/history?id=${selectedDevice.id}&date=${date}`);
            if(res.data && res.data.length > 0) {
                document.getElementById('historyCount').innerText = res.data.length;
                viewer.textContent = JSON.stringify(res.data, null, 2);
                hljs.highlightElement(viewer);
            } else {
                document.getElementById('historyCount').innerText = '0';
                viewer.innerText = 'Vazio.';
            }
        } catch(e) { viewer.innerText = 'Erro: ' + e.message; }
    }

    function switchTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        document.getElementById(`tab-${tabName}`).classList.remove('hidden');
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active', 'border-blue-600', 'text-blue-600');
            btn.classList.add('border-transparent');
        });
        const activeBtn = Array.from(document.querySelectorAll('.tab-btn')).find(b => b.getAttribute('onclick').includes(tabName));
        if(activeBtn) {
            activeBtn.classList.add('active', 'border-blue-600', 'text-blue-600');
            activeBtn.classList.remove('border-transparent');
        }
        if(tabName === 'dashboard') setTimeout(() => map.invalidateSize(), 200);
    }

    function toggleAutoRefresh() {
        const btn = document.getElementById('btnAutoRefresh');
        if(refreshTimer) {
            clearInterval(refreshTimer); refreshTimer = null;
            btn.innerHTML = '<i class="fas fa-sync"></i> <span class="hidden sm:inline">Auto-Refresh: OFF</span>';
            btn.classList.remove('text-blue-600', 'bg-blue-50');
        } else {
            startAutoRefresh();
            btn.innerHTML = '<i class="fas fa-sync fa-spin"></i> <span class="hidden sm:inline">Auto-Refresh: ON</span>';
            btn.classList.add('text-blue-600', 'bg-blue-50');
        }
    }

    function startAutoRefresh() {
        if(refreshTimer) clearInterval(refreshTimer);
        refreshTimer = setInterval(fetchLiveData, 5000);
        const btn = document.getElementById('btnAutoRefresh');
        btn.innerHTML = '<i class="fas fa-sync fa-spin"></i> <span class="hidden sm:inline">Auto-Refresh: ON</span>';
        btn.classList.add('text-blue-600', 'bg-blue-50');
    }

    document.getElementById('searchDevice').addEventListener('input', (e) => {
        const term = e.target.value.toLowerCase();
        const filtered = devices.filter(d => d.imei.includes(term) || d.model.toLowerCase().includes(term));
        renderList(filtered);
    });

    init();
</script>