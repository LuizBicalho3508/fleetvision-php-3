<div class="p-6">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Biblioteca de Ícones 3D</h1>
            <p class="text-sm text-gray-500">Personalize a visualização da sua frota no mapa.</p>
        </div>
        <button onclick="openModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded shadow-md font-bold transition flex items-center gap-2">
            <i class="fas fa-cloud-upload-alt"></i> Enviar Novo Ícone
        </button>
    </div>

    <div id="iconsGrid" class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-6">
        <div class="col-span-full py-10 text-center text-gray-400">
            <i class="fas fa-spinner fa-spin text-2xl"></i> Carregando biblioteca...
        </div>
    </div>
</div>

<div id="uploadModal" class="fixed inset-0 bg-black/60 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-md overflow-hidden animate-fade-in-down">
        
        <div class="bg-gray-100 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="font-bold text-gray-800 text-lg">Upload de Ícone</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-red-500 text-xl">&times;</button>
        </div>
        
        <form id="formIcon" class="p-6 space-y-4">
            
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Nome do Ícone</label>
                <input type="text" name="name" placeholder="Ex: Caminhão Tanque" class="w-full border border-gray-300 rounded p-2 focus:border-blue-500 outline-none" required>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Arquivo de Imagem</label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:bg-gray-50 transition cursor-pointer relative">
                    <input type="file" name="icon" id="fileInput" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" accept="image/png, image/jpeg, image/webp" required onchange="previewImage(this)">
                    <div id="previewArea">
                        <i class="fas fa-image text-3xl text-gray-300 mb-2"></i>
                        <p class="text-xs text-gray-500">Clique ou arraste aqui<br>(PNG com fundo transparente)</p>
                    </div>
                    <img id="imgPreview" class="hidden max-h-24 mx-auto mt-2">
                </div>
            </div>

            <div class="pt-4 border-t border-gray-100 flex justify-end gap-3">
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-white border border-gray-300 text-gray-600 rounded hover:bg-gray-50 font-medium transition">Cancelar</button>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded font-bold shadow transition">Enviar</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Preview da imagem antes do upload
    function previewImage(input) {
        const preview = document.getElementById('imgPreview');
        const area = document.getElementById('previewArea');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
                area.classList.add('hidden');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    async function loadIcons() {
        const grid = document.getElementById('iconsGrid');
        try {
            const res = await apiFetch('/api/icons');
            
            if (!res.data || res.data.length === 0) {
                grid.innerHTML = `
                    <div class="col-span-full flex flex-col items-center justify-center py-16 text-gray-400 border-2 border-dashed border-gray-200 rounded-xl bg-gray-50">
                        <i class="fas fa-images text-4xl mb-3"></i>
                        <p>Nenhum ícone personalizado.</p>
                        <button onclick="openModal()" class="mt-4 text-blue-500 hover:underline text-sm">Faça o primeiro upload</button>
                    </div>`;
                return;
            }

            grid.innerHTML = res.data.map(icon => `
                <div class="group relative bg-white border border-gray-200 rounded-xl p-4 flex flex-col items-center hover:shadow-lg transition hover:border-blue-300">
                    <div class="h-16 w-16 flex items-center justify-center mb-3 bg-gray-50 rounded-lg p-2">
                        <img src="${icon.url}" alt="${icon.name}" class="max-h-full max-w-full object-contain filter drop-shadow-sm">
                    </div>
                    <p class="text-xs font-bold text-gray-700 text-center truncate w-full" title="${icon.name}">${icon.name}</p>
                    
                    <button onclick="deleteIcon(${icon.id})" class="absolute top-2 right-2 text-red-400 hover:text-red-600 bg-white rounded-full p-1 shadow-sm opacity-0 group-hover:opacity-100 transition" title="Excluir">
                        <i class="fas fa-trash-alt text-xs"></i>
                    </button>
                </div>
            `).join('');

        } catch(e) {
            grid.innerHTML = '<div class="col-span-full text-center text-red-400">Erro ao carregar ícones.</div>';
        }
    }

    // Modal Logic
    function openModal() {
        document.getElementById('formIcon').reset();
        document.getElementById('imgPreview').classList.add('hidden');
        document.getElementById('previewArea').classList.remove('hidden');
        document.getElementById('uploadModal').classList.remove('hidden');
    }
    function closeModal() { document.getElementById('uploadModal').classList.add('hidden'); }

    // Upload
    document.getElementById('formIcon').onsubmit = async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const oldTxt = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
        btn.disabled = true;

        const formData = new FormData(e.target);

        try {
            const res = await fetch('/api/icons/save', { method: 'POST', body: formData });
            const json = await res.json();

            if (json.success) {
                closeModal();
                loadIcons();
                showToast('Ícone adicionado com sucesso!');
            } else {
                showToast(json.error || 'Erro no upload', 'error');
            }
        } catch (err) {
            showToast('Erro de conexão', 'error');
        } finally {
            btn.innerHTML = oldTxt;
            btn.disabled = false;
        }
    };

    // Delete
    function deleteIcon(id) {
        if(!confirm('Deseja remover este ícone? Veículos usando ele voltarão ao padrão.')) return;

        apiFetch('/api/icons/delete', { method: 'POST', body: JSON.stringify({id}) })
            .then(res => {
                if(res.success) {
                    loadIcons();
                    showToast('Ícone removido.');
                } else {
                    showToast(res.error, 'error');
                }
            });
    }

    loadIcons();
</script>