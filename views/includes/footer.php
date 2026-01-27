<div id="globalAlertContainer" class="fixed top-20 right-4 z-[9999] flex flex-col gap-3 pointer-events-none"></div>

<audio id="alertSound" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>

<script>
(function() {
    let processedAlerts = new Set();
    let audioUnlocked = false;

    // 1. Destrava Áudio (Browsers modernos bloqueiam autoplay)
    const audio = document.getElementById('alertSound');
    const unlockAudio = () => {
        if(!audioUnlocked) {
            audio.play().then(() => {
                audio.pause();
                audio.currentTime = 0;
                audioUnlocked = true;
            }).catch(e => {});
            document.removeEventListener('click', unlockAudio);
            document.removeEventListener('keydown', unlockAudio);
        }
    };
    document.addEventListener('click', unlockAudio);
    document.addEventListener('keydown', unlockAudio);

    // 2. Cria Popup Visual
    function spawnAlert(alert) {
        // Toca Som
        if(audioUnlocked) {
            audio.currentTime = 0;
            audio.play().catch(e => console.log("Audio block"));
        }

        const container = document.getElementById('globalAlertContainer');
        const theme = `bg-white border-${alert.color}-500`;
        const iconColor = `text-${alert.color}-600`;

        const el = document.createElement('div');
        el.className = `pointer-events-auto w-80 p-4 rounded-xl shadow-2xl border-l-4 ${theme} transform translate-x-full transition-all duration-500 flex gap-3 relative bg-white`;
        el.innerHTML = `
            <div class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center ${iconColor}">
                <i class="fas ${alert.icon}"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-[10px] font-bold text-slate-400 mb-0.5">${alert.time}</p>
                <h4 class="font-bold text-sm text-slate-800">${alert.title}</h4>
                <div class="text-xs text-slate-500 truncate"><i class="fas fa-car mr-1"></i> ${alert.plate}</div>
            </div>
            <button onclick="this.parentElement.remove()" class="text-slate-300 hover:text-slate-500"><i class="fas fa-times"></i></button>
        `;

        container.appendChild(el);
        requestAnimationFrame(() => el.classList.remove('translate-x-full'));
        setTimeout(() => {
            el.classList.add('translate-x-full', 'opacity-0');
            setTimeout(() => el.remove(), 500);
        }, 5000);
    }

    // 3. Atualiza Sino
    function updateBell(hasAlerts) {
        const dot = document.getElementById('notifDot');
        const ping = document.getElementById('notifPing');
        const bell = document.getElementById('notifBell');
        
        if (dot && ping && bell) {
            if (hasAlerts) {
                dot.classList.remove('hidden');
                ping.classList.remove('hidden');
                bell.classList.add('text-red-500', 'animate-pulse'); // Ícone fica vermelho e pulsa
                bell.classList.remove('text-slate-400');
            } else {
                // Remove apenas se o usuário visitou a página (lógica feita na pagina alertas)
                // Ou mantém limpo se não tiver novos
            }
        }
    }

    // 4. Polling Principal
    async function checkAlerts() {
        try {
            const res = await apiFetch('/sys/alerts/poll');
            if (res.alerts && res.alerts.length > 0) {
                let hasNew = false;
                res.alerts.forEach(alert => {
                    if (!processedAlerts.has(alert.id)) {
                        spawnAlert(alert);
                        processedAlerts.add(alert.id);
                        hasNew = true;
                    }
                });

                if (hasNew) updateBell(true);
            }
        } catch (e) {}
    }

    // Roda a cada 5s
    setInterval(checkAlerts, 5000);
    
    // Zera o sino se estiver na página de alertas
    if(window.location.pathname.includes('/alertas')) {
        const dot = document.getElementById('notifDot');
        if(dot) {
            dot.classList.add('hidden');
            document.getElementById('notifPing').classList.add('hidden');
            document.getElementById('notifBell').classList.remove('text-red-500', 'animate-pulse');
        }
    }

})();
</script>