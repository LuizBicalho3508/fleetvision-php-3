<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config = { theme: { extend: {} }, corePlugins: { preflight: true } }</script>

<div class="p-4 h-[calc(100vh-80px)] flex flex-col bg-slate-50 font-sans">
    <div class="flex justify-between items-center mb-4 gap-4 shrink-0 bg-white p-3 rounded-xl shadow-sm border border-slate-200">
        <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
            <i class="fas fa-video text-blue-600"></i> Monitoramento SGBras
        </h1>
        
        <div class="flex gap-2 items-center">
            <select id="deviceSelect" class="w-64 border border-slate-300 rounded-lg p-2 text-sm" onchange="onDeviceChange()">
                <option value="">Selecione um Veículo...</option>
                <?php global $sgbrasVehicles; if(!empty($sgbrasVehicles)): foreach($sgbrasVehicles as $v): ?>
                    <option value="<?= $v['idno'] ?>"><?= $v['userKey'] ?></option>
                <?php endforeach; endif; ?>
            </select>
            <button onclick="document.getElementById('configModal').classList.remove('hidden')" class="text-slate-400 hover:text-blue-600 px-2"><i class="fas fa-cog"></i></button>
        </div>
    </div>

    <div class="flex border-b border-slate-200 mb-4 bg-white rounded-t-lg px-4">
        <button onclick="switchTab('live')" id="tab-live" class="py-3 px-6 text-sm font-bold text-blue-600 border-b-2 border-blue-600">Ao Vivo</button>
        <button onclick="switchTab('history')" id="tab-history" class="py-3 px-6 text-sm font-bold text-slate-500 hover:text-slate-700">Histórico de Alertas</button>
    </div>

    <div id="view-live" class="flex-1 grid grid-cols-1 lg:grid-cols-3 gap-4 min-h-0">
        <div class="lg:col-span-1 bg-white rounded-xl shadow-sm border border-slate-200 relative">
            <div id="map" class="w-full h-full rounded-xl z-0"></div>
            <div class="absolute bottom-2 left-2 bg-white/90 p-2 rounded shadow text-xs z-[400]">
                <strong id="infoSpeed" class="text-blue-600 text-lg">0 km/h</strong><br>
                <span id="infoTime" class="text-slate-500">--:--</span>
            </div>
        </div>

        <div class="lg:col-span-2 grid grid-cols-2 gap-2 bg-black p-2 rounded-xl shadow-inner overflow-y-auto content-start relative">
            <?php for($i=0; $i<4; $i++): ?>
            <div class="relative bg-gray-900 aspect-video rounded overflow-hidden border border-gray-800 group">
                <video id="video<?= $i ?>" class="w-full h-full object-cover" muted autoplay playsinline></video>
                <div class="absolute top-2 left-2 bg-red-600/80 text-white text-[9px] px-2 rounded backdrop-blur z-10">CAM <?= $i+1 ?></div>
                <div id="loader<?= $i ?>" class="absolute inset-0 flex items-center justify-center text-white hidden flex-col z-20 bg-black/50"></div>
            </div>
            <?php endfor; ?>
            <button onclick="startStreams()" class="absolute bottom-4 right-4 bg-blue-600 text-white p-3 rounded-full shadow hover:bg-blue-500 z-30"><i class="fas fa-sync-alt"></i></button>
        </div>
    </div>

    <div id="view-history" class="hidden flex-1 flex-col bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex gap-4 items-end bg-slate-50">
            <div><label class="text-xs font-bold text-slate-500 block mb-1">Início</label><input type="datetime-local" id="filterStart" class="border rounded p-2 text-sm"></div>
            <div><label class="text-xs font-bold text-slate-500 block mb-1">Fim</label><input type="datetime-local" id="filterEnd" class="border rounded p-2 text-sm"></div>
            <button onclick="loadHistory()" class="bg-blue-600 text-white px-6 py-2 rounded text-sm hover:bg-blue-700 shadow">Buscar</button>
        </div>
        
        <div class="flex-1 overflow-y-auto p-4">
            <div id="historyGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <p class="col-span-full text-center text-slate-400 py-10">Selecione um veículo e clique em buscar.</p>
            </div>
        </div>
    </div>
</div>

<div id="configModal" class="fixed inset-0 z-[1000] hidden bg-black/50 flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-sm rounded-lg shadow-xl p-6">
        <form onsubmit="saveConfig(event)">
            <input type="text" name="sgbras_user" placeholder="Usuário" class="w-full border p-2 mb-3 rounded" required>
            <input type="password" name="sgbras_pass" placeholder="Senha" class="w-full border p-2 mb-4 rounded" required>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded w-full">Salvar</button>
        </form>
    </div>
</div>

<div id="imgModal" class="fixed inset-0 z-[1000] hidden bg-black/95 flex items-center justify-center p-4" onclick="this.classList.add('hidden')">
    <img id="modalImage" src="" class="max-w-full max-h-screen rounded shadow-2xl">
</div>

<script>
let map, marker;
let hlsInstances = [];

document.addEventListener('DOMContentLoaded', () => {
    map = L.map('map').setView([-14.2, -51.9], 4);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
    
    // Set Datas
    const now = new Date();
    const start = new Date(now); start.setHours(0,0,0,0);
    const end = new Date(now); end.setHours(23,59,59,999);
    document.getElementById('filterStart').value = start.toISOString().slice(0,16);
    document.getElementById('filterEnd').value = end.toISOString().slice(0,16);
});

// Abas
function switchTab(tab) {
    document.getElementById('view-live').classList.toggle('hidden', tab !== 'live');
    document.getElementById('view-history').classList.toggle('hidden', tab !== 'history');
    
    // Estilo Botões
    const btnLive = document.getElementById('tab-live');
    const btnHistory = document.getElementById('tab-history');
    
    if(tab === 'live') {
        btnLive.className = "py-3 px-6 text-sm font-bold text-blue-600 border-b-2 border-blue-600";
        btnHistory.className = "py-3 px-6 text-sm font-bold text-slate-500 hover:text-slate-700";
        setTimeout(() => map.invalidateSize(), 100);
    } else {
        btnHistory.className = "py-3 px-6 text-sm font-bold text-blue-600 border-b-2 border-blue-600";
        btnLive.className = "py-3 px-6 text-sm font-bold text-slate-500 hover:text-slate-700";
        loadHistory();
    }
}

// Wrapper Fetch
async function apiFetch(url, formData) {
    try {
        const res = await fetch(url, { method: 'POST', body: formData });
        return await res.json();
    } catch (e) { return { success: false }; }
}

function onDeviceChange() {
    updateLocation();
    startStreams();
    // Limpa histórico
    document.getElementById('historyGrid').innerHTML = '<p class="col-span-full text-center text-slate-400 py-10">Clique em buscar para ver o histórico.</p>';
}

async function updateLocation() {
    const device = document.getElementById('deviceSelect').value;
    if(!device) return;
    const formData = new FormData(); formData.append('device', device);
    const json = await apiFetch('/sys/sgbras/location', formData);
    if(json.success) {
        const { lat, lng, speed, time } = json.data;
        if(marker) map.removeLayer(marker);
        marker = L.marker([lat, lng]).addTo(map);
        map.setView([lat, lng], 15);
        document.getElementById('infoSpeed').innerText = Math.round(speed) + ' km/h';
        document.getElementById('infoTime').innerText = time.split(' ')[1] || time;
    }
}

function startStreams() {
    const device = document.getElementById('deviceSelect').value;
    hlsInstances.forEach(hls => { if(hls) hls.destroy(); });
    hlsInstances = [];
    for (let i = 0; i < 4; i++) loadChannel(device, i);
}

async function loadChannel(device, chn) {
    const video = document.getElementById('video' + chn);
    const loader = document.getElementById('loader' + chn);
    loader.innerHTML = '<i class="fas fa-circle-notch fa-spin text-2xl"></i>';
    loader.classList.remove('hidden');

    const formData = new FormData();
    formData.append('device', device);
    formData.append('channel', chn);
    
    const json = await apiFetch('/sys/sgbras/stream', formData);

    if (json.success && json.url) {
        if (Hls.isSupported()) {
            const hls = new Hls({
                // Configuração Anti-404 (Latência Alta)
                liveSyncDurationCount: 5, 
                maxMaxBufferLength: 30,
                manifestLoadingTimeOut: 15000,
                fragLoadingMaxRetry: 10,
                fragLoadingRetryDelay: 1000,
                debug: false
            });
            hls.loadSource(json.url);
            hls.attachMedia(video);
            hls.on(Hls.Events.MANIFEST_PARSED, () => {
                video.play().catch(() => { video.muted = true; video.play(); });
                loader.classList.add('hidden');
            });
            hls.on(Hls.Events.ERROR, (e, data) => {
                if (data.fatal) {
                    if(data.type === Hls.ErrorTypes.NETWORK_ERROR) hls.startLoad();
                    else { hls.destroy(); loader.innerHTML = '<span class="text-xs text-gray-500">Off</span>'; loader.classList.remove('hidden'); }
                }
            });
            hlsInstances[chn] = hls;
        } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
            video.src = json.url;
            video.addEventListener('loadedmetadata', () => { video.play(); loader.classList.add('hidden'); });
        }
    } else { loader.innerHTML = '<span class="text-xs text-gray-500">Off</span>'; }
}

async function loadHistory() {
    const device = document.getElementById('deviceSelect').value;
    if(!device) return alert('Selecione um veículo');
    
    const grid = document.getElementById('historyGrid');
    grid.innerHTML = '<p class="col-span-full text-center text-blue-600"><i class="fas fa-circle-notch fa-spin text-2xl"></i><br>Buscando histórico...</p>';
    
    const formData = new FormData();
    formData.append('device', device);
    formData.append('start', document.getElementById('filterStart').value.replace('T', ' ')+':00');
    formData.append('end', document.getElementById('filterEnd').value.replace('T', ' ')+':59');

    const json = await apiFetch('/sys/sgbras/search', formData);
    
    if(json.data && json.data.length > 0) {
        grid.innerHTML = json.data.map(item => `
            <div class="bg-white border border-slate-200 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition group">
                <div class="h-40 bg-gray-100 relative cursor-pointer" onclick="openImage('${item.full_image}')">
                    ${item.full_image 
                        ? `<img src="${item.full_image}" class="w-full h-full object-cover">` 
                        : `<div class="w-full h-full flex items-center justify-center text-slate-300"><i class="fas fa-image text-3xl"></i></div>`
                    }
                    <div class="absolute top-2 right-2 ${item.type_class} text-white text-xs font-bold px-2 py-1 rounded shadow">
                        ${item.type_label}
                    </div>
                </div>
                <div class="p-3">
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-xs font-bold text-slate-700"><i class="far fa-clock mr-1"></i> ${item.time_str}</span>
                    </div>
                    <div class="text-xs text-slate-500 truncate" title="${item.position}">
                        <i class="fas fa-map-marker-alt text-red-400 mr-1"></i> ${item.position}
                    </div>
                </div>
            </div>
        `).join('');
    } else {
        grid.innerHTML = '<p class="col-span-full text-center text-slate-400 py-10">Nenhum evento encontrado neste período.</p>';
    }
}

function openImage(url) {
    if(url && url !== 'null') {
        document.getElementById('modalImage').src = url;
        document.getElementById('imgModal').classList.remove('hidden');
    }
}

async function saveConfig(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    await apiFetch('/sys/sgbras/config', formData);
    location.reload();
}
</script>