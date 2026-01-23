<div class="relative w-full h-[calc(100vh-80px)] overflow-hidden bg-slate-100">
    
    <div id="map" class="h-full w-full z-0"></div>

    <div class="absolute top-4 left-4 right-4 md:right-auto md:w-[400px] z-[500] bg-white/95 backdrop-blur-md p-5 rounded-2xl shadow-xl border border-white/50 flex flex-col gap-4">
        
        <div class="flex justify-between items-center border-b border-slate-100 pb-2">
            <h3 class="font-bold text-slate-700 uppercase tracking-wider text-sm"><i class="fas fa-history mr-2"></i>Histórico de Rotas</h3>
        </div>

        <form id="historyForm" class="space-y-3">
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Veículo</label>
                <select id="selDevice" class="w-full bg-slate-50 border border-slate-200 text-slate-700 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5" required>
                    <option value="">Carregando...</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Início</label>
                    <input type="datetime-local" id="dateFrom" class="w-full bg-slate-50 border border-slate-200 text-slate-600 text-xs rounded-lg p-2" required>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Fim</label>
                    <input type="datetime-local" id="dateTo" class="w-full bg-slate-50 border border-slate-200 text-slate-600 text-xs rounded-lg p-2" required>
                </div>
            </div>

            <button type="submit" id="btnSearch" class="w-full text-white bg-blue-600 hover:bg-blue-700 font-bold rounded-lg text-sm px-5 py-2.5 text-center transition shadow-lg shadow-blue-200">
                <i class="fas fa-search mr-2"></i> Buscar Rota
            </button>
        </form>

        <div id="playbackControls" class="hidden pt-2 border-t border-slate-100 space-y-3">
            <div class="flex items-center justify-between text-xs text-slate-500 font-mono">
                <span id="currentTime">--:--</span>
                <span id="currentSpeed">0 km/h</span>
            </div>
            
            <input type="range" id="progressRange" min="0" value="0" class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-blue-600">

            <div class="flex items-center justify-center gap-4">
                <button onclick="changeSpeed()" class="text-xs font-bold text-slate-400 hover:text-blue-600 w-12 text-center" id="speedLabel">1x</button>
                <button onclick="togglePlay()" id="btnPlay" class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center hover:bg-blue-700 shadow-lg transition">
                    <i class="fas fa-play pl-1"></i>
                </button>
                <button onclick="clearMap()" class="text-xs font-bold text-slate-400 hover:text-red-500 w-12 text-center" title="Limpar">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // 1. Configuração do Mapa
    const map = L.map('map', { zoomControl: false }).setView([-14.2350, -51.9253], 4);
    L.control.zoom({ position: 'bottomright' }).addTo(map);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', { maxZoom: 19 }).addTo(map);

    // Variáveis Globais
    let routeLayer = null;
    let marker = null;
    let pathData = [];
    let isPlaying = false;
    let playbackSpeed = 1000; // ms entre pontos (quanto menor, mais rápido inicialmente, mas ajustaremos logica)
    let playbackIndex = 0;
    let playbackInterval = null;
    let speedMultiplier = 1;

    // 2. Carregar Veículos no Select
    async function loadVehicles() {
        const vehicles = await apiFetch('/api/dashboard/data'); // Reutilizando endpoint existente
        const sel = document.getElementById('selDevice');
        sel.innerHTML = '<option value="">Selecione um veículo</option>';
        if(vehicles) {
            vehicles.forEach(v => {
                sel.innerHTML += `<option value="${v.id}">${v.plate} - ${v.name}</option>`;
            });
        }
    }
    loadVehicles();

    // 3. Buscar Rota
    document.getElementById('historyForm').onsubmit = async (e) => {
        e.preventDefault();
        const deviceId = document.getElementById('selDevice').value;
        const from = document.getElementById('dateFrom').value.replace('T', ' ');
        const to = document.getElementById('dateTo').value.replace('T', ' ');

        // Ajuste simples para segundos
        const res = await apiFetch(`/api/history?device_id=${deviceId}&from=${from}:00&to=${to}:59`);

        if(res.success && res.data.length > 0) {
            pathData = res.data;
            drawRoute(pathData);
            document.getElementById('playbackControls').classList.remove('hidden');
            showToast(`${res.count} posições encontradas!`);
        } else {
            showToast('Nenhuma posição encontrada neste período.', 'error');
        }
    };

    // 4. Desenhar Rota
    function drawRoute(data) {
        clearMap();
        
        const latlngs = data.map(p => [p.latitude, p.longitude]);
        
        // Linha do trajeto
        routeLayer = L.polyline(latlngs, {color: '#3b82f6', weight: 4, opacity: 0.8}).addTo(map);
        
        // Ponto de partida (Verde)
        L.circleMarker(latlngs[0], {color: 'green', radius: 6}).addTo(map).bindPopup("Início");
        
        // Ponto final (Vermelho)
        L.circleMarker(latlngs[latlngs.length-1], {color: 'red', radius: 6}).addTo(map).bindPopup("Fim");

        map.fitBounds(routeLayer.getBounds(), {padding: [50, 50]});

        // Configurar Slider
        const slider = document.getElementById('progressRange');
        slider.max = data.length - 1;
        slider.value = 0;
        
        // Adicionar listener ao slider para "arrastar" o playback
        slider.oninput = function() {
            playbackIndex = parseInt(this.value);
            updateMarkerPosition(playbackIndex);
        };
    }

    // 5. Lógica de Playback
    function togglePlay() {
        const btn = document.getElementById('btnPlay');
        if (isPlaying) {
            pause();
            btn.innerHTML = '<i class="fas fa-play pl-1"></i>';
        } else {
            play();
            btn.innerHTML = '<i class="fas fa-pause"></i>';
        }
        isPlaying = !isPlaying;
    }

    function play() {
        if(playbackIndex >= pathData.length - 1) playbackIndex = 0; // Reinicia se estiver no fim

        playbackInterval = setInterval(() => {
            if (playbackIndex >= pathData.length - 1) {
                togglePlay(); // Fim da linha
                return;
            }
            playbackIndex++;
            updateMarkerPosition(playbackIndex);
            document.getElementById('progressRange').value = playbackIndex;
        }, 500 / speedMultiplier); // Velocidade base ajustada pelo multiplicador
    }

    function pause() {
        clearInterval(playbackInterval);
    }

    function updateMarkerPosition(index) {
        const point = pathData[index];
        if (!point) return;

        // Atualiza Info
        document.getElementById('currentTime').innerText = new Date(point.time).toLocaleTimeString();
        document.getElementById('currentSpeed').innerText = Math.round(point.speed * 1.852) + ' km/h'; // Nós para Km/h se necessario, ou direto se ja vier km/h

        // Cria ou move o marcador
        const iconHtml = `<div style="transform: rotate(${point.course}deg);" class="text-blue-700 text-3xl drop-shadow-md"><i class="fas fa-arrow-circle-up"></i></div>`;
        const icon = L.divIcon({ html: iconHtml, className: 'bg-transparent', iconSize: [30, 30], iconAnchor: [15, 15] });

        if (!marker) {
            marker = L.marker([point.latitude, point.longitude], {icon: icon, zIndexOffset: 1000}).addTo(map);
        } else {
            marker.setLatLng([point.latitude, point.longitude]).setIcon(icon);
        }
        
        // Opcional: Centralizar mapa no marcador enquanto move
        // map.panTo([point.latitude, point.longitude]); 
    }

    function changeSpeed() {
        const speeds = [1, 2, 5, 10];
        let idx = speeds.indexOf(speedMultiplier);
        idx = (idx + 1) % speeds.length;
        speedMultiplier = speeds[idx];
        document.getElementById('speedLabel').innerText = speedMultiplier + 'x';
        
        // Se estiver tocando, reinicia o intervalo com nova velocidade
        if(isPlaying) {
            pause();
            play();
        }
    }

    function clearMap() {
        if(routeLayer) map.removeLayer(routeLayer);
        if(marker) map.removeLayer(marker);
        map.eachLayer((layer) => {
            if(layer instanceof L.CircleMarker) map.removeLayer(layer);
        });
        document.getElementById('playbackControls').classList.add('hidden');
        pause();
        playbackIndex = 0;
        isPlaying = false;
        document.getElementById('btnPlay').innerHTML = '<i class="fas fa-play pl-1"></i>';
    }

    // Define data padrão (Hoje 00:00 até agora)
    const now = new Date();
    const today = new Date(); 
    today.setHours(0,0,0,0);
    // Ajuste fuso horário simples para input datetime-local
    const toLocalISO = (d) => { return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0') + 'T' + String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0'); };
    
    document.getElementById('dateFrom').value = toLocalISO(today);
    document.getElementById('dateTo').value = toLocalISO(now);

</script>