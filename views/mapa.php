<div class="relative w-full h-[calc(100vh-80px)] overflow-hidden bg-slate-100">
    <div id="map" class="h-full w-full z-0"></div>
    
    <div class="absolute top-4 right-4 z-[500] bg-white/90 backdrop-blur-md p-4 rounded-2xl shadow-xl w-72 max-h-[80vh] overflow-y-auto border border-white/50 hidden md:block">
        <h3 class="font-bold text-slate-700 mb-3 border-b border-slate-200 pb-2 text-sm uppercase tracking-wider">Veículos Online</h3>
        <div id="map-vehicle-list" class="space-y-2">
            <p class="text-slate-400 text-xs text-center py-4">Carregando...</p>
        </div>
    </div>
</div>

<script>
    // Inicializa Mapa
    const map = L.map('map', { zoomControl: false }).setView([-14.2350, -51.9253], 4);
    
    // Zoom control na posição inferior direita
    L.control.zoom({ position: 'bottomright' }).addTo(map);

    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OSM & CartoDB',
        subdomains: 'abcd',
        maxZoom: 20
    }).addTo(map);

    const markers = {};

    async function updateMap() {
        const vehicles = await apiFetch('/api/dashboard/data');
        if(!vehicles) return;

        const listContainer = document.getElementById('map-vehicle-list');
        listContainer.innerHTML = '';

        let bounds = L.latLngBounds();
        let hasVehicles = false;

        vehicles.forEach(v => {
            // Verifica se tem lat/lon validos (assumindo que api/dashboard/data retorna isso)
            // Caso sua API retorne lat/lon dentro de um objeto aninhado ou com outro nome, ajuste aqui.
            // Exemplo: v.latitude e v.longitude
            const lat = parseFloat(v.latitude || 0);
            const lon = parseFloat(v.longitude || 0);

            if(lat === 0 && lon === 0) return;

            hasVehicles = true;
            bounds.extend([lat, lon]);

            // Ícone Customizado
            const colorClass = v.speed > 0 ? 'text-green-600' : 'text-slate-500';
            const iconHtml = `<div style="transform: rotate(${v.course||0}deg);" class="${colorClass} text-2xl drop-shadow-lg filter"><i class="fas fa-arrow-circle-up"></i></div>`;
            const icon = L.divIcon({ html: iconHtml, className: 'bg-transparent', iconSize: [24, 24], iconAnchor: [12, 12] });

            if (markers[v.id]) {
                markers[v.id].setLatLng([lat, lon]).setIcon(icon);
            } else {
                markers[v.id] = L.marker([lat, lon], {icon}).addTo(map)
                    .bindPopup(`<div class="text-center"><b class="text-slate-800">${v.plate}</b><br><span class="text-xs text-slate-500">${v.name}</span><br><span class="font-bold text-blue-600">${Math.round(v.speed||0)} km/h</span></div>`);
            }

            // Lista Lateral
            listContainer.innerHTML += `
                <div class="flex justify-between items-center p-2.5 hover:bg-white/80 rounded-lg cursor-pointer transition border border-transparent hover:border-slate-100 shadow-sm hover:shadow" onclick="map.flyTo([${lat}, ${lon}], 16)">
                    <div>
                        <div class="font-bold text-slate-700 text-sm">${v.plate}</div>
                        <div class="text-[10px] text-slate-400 uppercase">${v.name}</div>
                    </div>
                    <div class="${v.speed > 0 ? 'text-green-600' : 'text-slate-400'} font-bold text-xs bg-slate-50 px-2 py-1 rounded">${Math.round(v.speed||0)} km/h</div>
                </div>
            `;
        });

        // Auto-fit na primeira carga se houver veículos
        if (hasVehicles && !window.mapInitialized) {
            map.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 });
            window.mapInitialized = true;
        }
    }

    setInterval(updateMap, 5000);
    updateMap();
</script>