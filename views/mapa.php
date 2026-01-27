<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    /* --- SEU CSS ORIGINAL --- */
    @keyframes pulse-ring {
        0% { transform: scale(0.8); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
        70% { transform: scale(1.3); box-shadow: 0 0 0 10px rgba(34, 197, 94, 0); }
        100% { transform: scale(0.8); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
    }
    
    .marker-pin {
        position: relative; width: 40px; height: 40px;
        display: flex; justify-content: center; align-items: center;
    }

    .marker-icon-img {
        width: 32px; height: 32px; object-fit: contain; z-index: 20;
        filter: drop-shadow(0 2px 3px rgba(0,0,0,0.3));
        transition: transform 0.3s;
    }

    .pulsing-circle {
        position: absolute; top: 50%; left: 50%;
        width: 40px; height: 40px; margin-left: -20px; margin-top: -20px;
        border-radius: 50%; background: rgba(34, 197, 94, 0.2);
        z-index: 10; animation: pulse-ring 2s infinite;
    }

    /* PREMIUM POPUP (Melhorado) */
    .leaflet-popup-content-wrapper {
        border-radius: 12px; padding: 0; overflow: hidden;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2);
    }
    .leaflet-popup-content { margin: 0; width: 280px !important; }
    
    .custom-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
    .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
    .custom-scroll::-webkit-scrollbar-track { background: #f1f5f9; }

    /* CONTROLE DE CAMADAS (NOVO) */
    .layer-control {
        position: absolute; top: 10px; right: 10px; z-index: 500;
        background: white; padding: 5px; border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        display: flex; flex-direction: column; gap: 5px;
    }
    .layer-btn {
        width: 35px; height: 35px; border-radius: 6px; border: 1px solid #e2e8f0;
        background: white; color: #475569; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: all 0.2s;
    }
    .layer-btn:hover { background: #f1f5f9; color: #2563eb; }
    .layer-btn.active { background: #2563eb; color: white; border-color: #2563eb; }
</style>

<div class="flex flex-col h-screen overflow-hidden bg-slate-100 relative">
    
    <div id="map" class="flex-1 w-full z-0 relative">
        <div class="layer-control">
            <button onclick="setLayer('osm')" class="layer-btn active" title="Mapa Padrão"><i class="fas fa-map"></i></button>
            <button onclick="setLayer('satellite')" class="layer-btn" title="Satélite"><i class="fas fa-satellite"></i></button>
            <button onclick="setLayer('hybrid')" class="layer-btn" title="Híbrido"><i class="fas fa-layer-group"></i></button>
            <button onclick="setLayer('dark')" class="layer-btn" title="Modo Escuro"><i class="fas fa-moon"></i></button>
            <button onclick="setLayer('google')" class="layer-btn" title="Vias"><i class="fas fa-road"></i></button>
        </div>
    </div>

    <div class="bg-white border-t border-slate-200 shadow-[0_-5px_15px_rgba(0,0,0,0.05)] z-10 flex flex-col" style="height: 35vh; min-height: 250px;">
        
        <div class="px-4 py-2 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <div class="flex items-center gap-3">
                <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wide"><i class="fas fa-table mr-2"></i> Monitoramento</h3>
                <span id="totalVehicles" class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-xs font-bold">0 Veículos</span>
                
                <div class="relative ml-4">
                    <i class="fas fa-search absolute left-2 top-2 text-slate-400 text-xs"></i>
                    <input type="text" id="gridSearch" placeholder="Buscar Placa, Cliente, Motorista..." 
                           class="pl-7 pr-3 py-1 text-xs border border-slate-300 rounded-md w-64 focus:border-blue-500 outline-none">
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <label class="text-xs text-slate-500 font-bold">Linhas:</label>
                <select id="rowsPerPage" onchange="renderGrid()" class="bg-white border border-slate-300 text-xs rounded px-2 py-1 outline-none focus:border-blue-500">
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                    <option value="1000">Todas</option>
                </select>
                <button onclick="toggleFollowMode()" id="followBtn" class="ml-3 px-3 py-1 rounded bg-slate-200 text-slate-500 text-xs font-bold hover:bg-blue-500 hover:text-white transition">
                    <i class="fas fa-crosshairs"></i> Seguir: <span id="followTarget">Nenhum</span>
                </button>
            </div>
        </div>

        <div class="flex-1 overflow-auto custom-scroll p-0">
            <table class="w-full text-xs text-left border-collapse">
                <thead class="bg-slate-50 text-slate-500 font-bold sticky top-0 z-10 shadow-sm">
                    <tr>
                        <th class="p-3 border-b border-r border-slate-200 whitespace-nowrap text-center">St</th>
                        <th class="p-3 border-b border-r border-slate-200 whitespace-nowrap">Placa</th>
                        <th class="p-3 border-b border-r border-slate-200 whitespace-nowrap">Cliente</th>
                        <th class="p-3 border-b border-r border-slate-200 whitespace-nowrap">Motorista</th>
                        <th class="p-3 border-b border-r border-slate-200 whitespace-nowrap min-w-[200px]">Endereço</th>
                        <th class="p-3 border-b border-r border-slate-200 whitespace-nowrap">Data / Hora</th>
                        <th class="p-3 border-b border-r border-slate-200 whitespace-nowrap text-center">Velocidade</th>
                        <th class="p-3 border-b border-r border-slate-200 whitespace-nowrap text-center">Ignição</th>
                        <th class="p-3 border-b border-r border-slate-200 whitespace-nowrap text-center">Bat. Int.</th>
                        <th class="p-3 border-b border-r border-slate-200 whitespace-nowrap text-center">Voltagem</th>
                        <th class="p-3 border-b border-r border-slate-200 whitespace-nowrap text-center">Sat</th>
                        <th class="p-3 border-b border-r border-slate-200 whitespace-nowrap text-center">GSM</th>
                        <th class="p-3 border-b border-r border-slate-200 whitespace-nowrap">Lat / Long</th>
                        <th class="p-3 border-b border-slate-200 whitespace-nowrap">IMEI</th>
                    </tr>
                </thead>
                <tbody id="gridBody" class="divide-y divide-slate-100 bg-white">
                    </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // --- VARIÁVEIS GLOBAIS ---
    let map;
    let markers = {}; 
    let vehicleData = []; 
    let followedVehicleId = null; 
    let updateInterval;
    let addressCache = {}; 
    let currentLayer = null;

    // --- CAMADAS DE MAPA (TILES) ---
    const layers = {
        osm: L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', { attribution: '&copy; OpenStreetMap', maxZoom: 20 }),
        satellite: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: 'Esri', maxZoom: 19 }),
        hybrid: L.layerGroup([
            L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'),
            L.tileLayer('https://stamen-tiles-{s}.a.ssl.fastly.net/toner-lines/{z}/{x}/{y}{r}.png', { opacity: 0.5 })
        ]),
        dark: L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', { attribution: 'CartoDB', maxZoom: 20 }),
        google: L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', { attribution: 'Google', maxZoom: 20 })
    };

    // --- INICIALIZAÇÃO ---
    function initMap() {
        map = L.map('map', { zoomControl: false }).setView([-14.2350, -51.9253], 4);
        L.control.zoom({ position: 'bottomright' }).addTo(map);

        // Inicia com OSM
        setLayer('osm');

        fetchData();
        updateInterval = setInterval(fetchData, 5000);

        // Listener de Pesquisa
        document.getElementById('gridSearch').addEventListener('input', renderGrid);
    }

    // --- TROCA DE CAMADA ---
    window.setLayer = (type) => {
        if (currentLayer) map.removeLayer(currentLayer);
        
        if (type === 'hybrid') currentLayer = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', { maxZoom: 20 });
        else currentLayer = layers[type];
        
        map.addLayer(currentLayer);

        // Atualiza estilo dos botões
        document.querySelectorAll('.layer-btn').forEach(btn => btn.classList.remove('active'));
        const activeBtn = document.querySelector(`button[onclick="setLayer('${type}')"]`);
        if(activeBtn) activeBtn.classList.add('active');
    }

    // --- BUSCA DE DADOS ---
    async function fetchData() {
        try {
            const res = await apiFetch('/sys/mapa');
            if (res.data) {
                vehicleData = res.data;
                updateMapMarkers();
                renderGrid();
                document.getElementById('totalVehicles').innerText = `${vehicleData.length} Veículos`;
            }
        } catch (error) { console.error("Erro dados:", error); }
    }

    // --- MARKERS ---
    function updateMapMarkers() {
        const bounds = L.latLngBounds();

        vehicleData.forEach(v => {
            const vId = String(v.id);
            const latLng = [v.lat, v.lng];
            bounds.extend(latLng);

            const pulseClass = v.ignition ? 'pulsing-circle' : 'hidden';
            const customIcon = L.divIcon({
                className: 'custom-div-icon',
                html: `
                    <div class="marker-pin">
                        <div class="${pulseClass}"></div>
                        <img src="${v.icon}" class="marker-icon-img" style="transform: rotate(${v.course}deg)">
                    </div>
                `,
                iconSize: [40, 40],
                iconAnchor: [20, 20],
                popupAnchor: [0, -20]
            });

            if (markers[vId]) {
                const marker = markers[vId];
                marker.setLatLng(latLng);
                marker.setIcon(customIcon);
                
                if (marker.isPopupOpen()) {
                    const content = getPopupContent(v);
                    if(marker.getPopup().getContent() !== content) {
                         marker.setPopupContent(content);
                         if(!v.address) resolveAddress(v); 
                    }
                }
            } else {
                const marker = L.marker(latLng, { icon: customIcon }).addTo(map);
                marker.bindPopup(getPopupContent(v));
                marker.on('click', () => { focusVehicle(vId); });
                markers[vId] = marker;
            }

            if (followedVehicleId === vId) {
                map.panTo(latLng);
            }
        });

        if (!followedVehicleId && Object.keys(markers).length > 0 && map.getZoom() === 4) {
            map.fitBounds(bounds, { padding: [50, 50] });
        }
    }

    // --- POPUP MELHORADO ---
    function getPopupContent(v) {
        const statusColor = v.ignition ? 'text-green-600' : 'text-slate-400';
        const statusBg = v.ignition ? 'bg-green-50 border-green-100' : 'bg-slate-50 border-slate-100';
        const ignText = v.ignition ? 'LIGADA' : 'DESLIGADA';
        const addrText = v.address || addressCache[v.id] || '<span id="addr-popup-' + v.id + '" class="animate-pulse text-slate-400">Carregando endereço...</span>';
        
        if (!v.address && !addressCache[v.id]) resolveAddress(v);

        // Layout mais limpo e organizado
        return `
            <div class="font-sans text-slate-700">
                <div class="bg-slate-800 text-white p-3 flex justify-between items-center rounded-t-xl">
                    <div>
                        <h3 class="font-bold text-base leading-tight">${v.plate}</h3>
                        <p class="text-[10px] text-slate-400 uppercase">${v.model}</p>
                    </div>
                    <span class="text-[10px] bg-slate-700 px-2 py-1 rounded border border-slate-600">${v.status.toUpperCase()}</span>
                </div>
                
                <div class="p-3 bg-white rounded-b-xl shadow-sm">
                    <div class="flex items-start gap-2 text-xs text-slate-500 mb-3 border-b border-slate-50 pb-2">
                        <i class="fas fa-map-marker-alt text-red-500 mt-0.5"></i>
                        <span class="leading-tight">${addrText}</span>
                    </div>

                    <div class="flex items-center gap-2 text-xs text-slate-600 mb-3">
                        <i class="fas fa-user-circle text-blue-500"></i>
                        <span class="font-bold">${v.driver || 'Sem Motorista'}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs mb-3">
                        <div class="p-2 rounded border text-center ${statusBg}">
                            <span class="block text-[9px] text-slate-400 uppercase font-bold">Ignição</span>
                            <span class="font-bold ${statusColor}"><i class="fas fa-power-off"></i> ${ignText}</span>
                        </div>
                        <div class="p-2 rounded border border-slate-100 bg-slate-50 text-center">
                            <span class="block text-[9px] text-slate-400 uppercase font-bold">Velocidade</span>
                            <span class="font-bold text-slate-700 font-mono text-sm">${v.speed} <small>km/h</small></span>
                        </div>
                    </div>

                    <button onclick="focusVehicle('${v.id}')" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg font-bold text-xs transition shadow-md shadow-blue-100">
                        <i class="fas fa-crosshairs mr-1"></i> ACOMPANHAR
                    </button>
                    
                    <div class="text-[9px] text-right text-slate-300 mt-2">
                        ${formatTime(v.datetime)}
                    </div>
                </div>
            </div>
        `;
    }

    // --- GRID COM PESQUISA ---
    function renderGrid() {
        const tbody = document.getElementById('gridBody');
        const limit = parseInt(document.getElementById('rowsPerPage').value);
        const term = document.getElementById('gridSearch').value.toLowerCase(); // Termo de busca

        // Filtragem
        const filtered = vehicleData.filter(v => 
            v.plate.toLowerCase().includes(term) ||
            v.customer.toLowerCase().includes(term) ||
            v.driver.toLowerCase().includes(term) ||
            v.imei.includes(term)
        );

        const displayData = filtered.slice(0, limit);

        tbody.innerHTML = displayData.map(v => {
            const vId = String(v.id);
            const speedClass = getSpeedColor(v.speed, v.speed_limit, v.ignition);
            const battPct = v.battery_level || 0;
            const battColor = battPct > 20 ? 'bg-green-500' : 'bg-red-500';
            
            const displayAddr = v.address || addressCache[vId] || 'Calculando...';
            if(!v.address && !addressCache[vId]) resolveAddress(v);

            return `
                <tr class="hover:bg-blue-50 transition cursor-pointer border-b border-slate-50 group ${followedVehicleId === vId ? 'bg-blue-50 border-l-4 border-blue-500' : ''}" 
                    onclick="focusVehicle('${vId}')">
                    <td class="p-3 text-center"><i class="fas fa-circle ${v.status === 'online' ? 'text-green-500' : 'text-slate-300'} text-[8px]"></i></td>
                    <td class="p-3 font-bold text-slate-700">${v.plate}</td>
                    <td class="p-3 text-slate-600">${v.customer}</td>
                    <td class="p-3 text-slate-500 text-[10px]"><i class="fas fa-user text-slate-300"></i> ${v.driver}</td>
                    <td class="p-3 text-slate-500 max-w-[180px] truncate text-[10px]" id="addr-grid-${vId}">${displayAddr}</td>
                    <td class="p-3 text-slate-500 font-mono text-[10px]">${formatTime(v.datetime)}</td>
                    <td class="p-3 text-center ${speedClass} font-mono text-xs">${v.speed} <span class="text-[9px]">Km/h</span></td>
                    <td class="p-3 text-center"><span class="px-2 py-0.5 rounded text-[9px] font-bold ${v.ignition ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-400'}">${v.ignition ? 'ON' : 'OFF'}</span></td>
                    <td class="p-3 text-center">
                         <div class="flex items-center justify-center gap-1" title="${battPct}%">
                            <div class="w-5 h-2.5 border border-slate-400 rounded-sm relative p-px">
                                <div class="h-full ${battColor}" style="width: ${battPct}%"></div>
                            </div>
                        </div>
                    </td>
                    <td class="p-3 text-center font-mono text-slate-600 text-[10px]">${v.voltage ? v.voltage.toFixed(1) + 'v' : '-'}</td>
                    <td class="p-3 text-center text-slate-500 text-[10px]"><i class="fas fa-satellite text-blue-400"></i> ${v.satellites}</td>
                    <td class="p-3 text-center text-slate-500 text-[10px]"><i class="fas fa-signal text-green-500"></i> ${v.gsm_signal}%</td>
                    <td class="p-3 text-slate-400 text-[9px] font-mono">${v.lat.toFixed(4)}, ${v.lng.toFixed(4)}</td>
                    <td class="p-3 text-slate-400 text-[9px] font-mono">${v.imei}</td>
                </tr>
            `;
        }).join('');
    }

    // --- FUNÇÕES DE LÓGICA ---
    function focusVehicle(id) {
        id = String(id);
        const v = vehicleData.find(item => String(item.id) === id);
        
        if (!v) return;

        followedVehicleId = id;
        
        const btn = document.getElementById('followBtn');
        document.getElementById('followTarget').innerText = v.plate;
        btn.classList.remove('bg-slate-200', 'text-slate-500');
        btn.classList.add('bg-blue-600', 'text-white', 'animate-pulse');

        map.flyTo([v.lat, v.lng], 17, { animate: true, duration: 1.0 });

        if (markers[id]) markers[id].openPopup();
        renderGrid();
    }

    function toggleFollowMode() {
        followedVehicleId = null;
        document.getElementById('followTarget').innerText = 'Nenhum';
        const btn = document.getElementById('followBtn');
        btn.classList.add('bg-slate-200', 'text-slate-500');
        btn.classList.remove('bg-blue-600', 'text-white', 'animate-pulse');
        renderGrid();
    }

    async function resolveAddress(v) {
        const vId = String(v.id);
        if (addressCache[vId]) return;
        addressCache[vId] = "Buscando...";

        try {
            const res = await apiFetch(`/sys/mapa/geocode?lat=${v.lat}&lng=${v.lng}`);
            if (res.address) {
                addressCache[vId] = res.address;
                
                const gridEl = document.getElementById(`addr-grid-${vId}`);
                if(gridEl) gridEl.innerText = res.address;
                
                const popupEl = document.getElementById(`addr-popup-${vId}`);
                if(popupEl) popupEl.innerText = res.address;
            }
        } catch (e) {
            addressCache[vId] = "Erro endereço";
        }
    }

    function getSpeedColor(speed, limit, ignition) {
        if (!ignition) return 'text-slate-800'; 
        if (speed > limit) return 'text-red-600 font-bold animate-pulse'; 
        return 'text-green-600 font-bold'; 
    }

    function formatTime(isoString) {
        if (!isoString) return '-';
        const d = new Date(isoString);
        return d.toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'});
    }

    initMap();
</script>