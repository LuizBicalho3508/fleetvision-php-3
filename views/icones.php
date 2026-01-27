<?php
// Verifica se é superadmin para mostrar o checkbox "Global"
$isSuperAdmin = ($_SESSION['user_role'] ?? '') === 'superadmin';
?>

<div class="p-6 space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Biblioteca de Ícones</h1>
            <p class="text-sm text-slate-500">Personalize os marcadores do mapa.</p>
        </div>
        
        <button onclick="openUploadModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-lg transition flex items-center gap-2">
            <i class="fas fa-cloud-upload-alt"></i> Upload Novo
        </button>
    </div>

    <div>
        <h3 class="text-xs font-bold text-slate-400 uppercase mb-4">Meus Ícones & Globais</h3>
        <div id="iconsGrid" class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-4">
            <div class="col-span-full p-12 text-center text-slate-400">
                <i class="fas fa-circle-notch fa-spin text-2xl mb-2"></i><br>Carregando...
            </div>
        </div>
    </div>
</div>

<div id="uploadModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white w-full max-w-md rounded-2xl shadow-2xl p-6">
        
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-slate-800">Enviar Novo Ícone</h3>
            <button onclick="closeModal()"><i class="fas fa-times text-slate-400 hover:text-slate-600"></i></button>
        </div>

        <form id="formUpload" class="space-y-4">
            
            <div class="w-full h-32 bg-slate-50 border-2 border-dashed border-slate-300 rounded-xl flex flex-col items-center justify-center cursor-pointer hover:bg-slate-100 transition relative">
                <input type="file" name="file" id="fileInput" accept="image/png, image/jpeg, image/svg+xml" class="absolute inset-0 opacity-0 cursor-pointer" required onchange="previewImage(this)">
                <img id="imgPreview" class="hidden h-16 w-16 object-contain mb-2">
                <div id="placeholderPreview" class="text-center">
                    <i class="fas fa-image text-slate-300 text-2xl mb-1"></i>
                    <p class="text-xs text-slate-400">Clique para selecionar</p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nome do Ícone</label>
                <input type="text" name="name" id="nameInput" placeholder="Ex: Caminhão Azul" required class="w-full border border-slate-300 rounded-lg p-2.5 focus:border-blue-500 outline-none">
            </div>

            <?php if ($isSuperAdmin): ?>
            <div class="flex items-center gap-3 p-3 bg-indigo-50 rounded-lg border border-indigo-100">
                <input type="checkbox" name="is_global" id="checkGlobal" value="true" class="w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500">
                <label for="checkGlobal" class="text-sm font-bold text-indigo-700 cursor-pointer">
                    Disponível para todos (Global)
                </label>
            </div>
            <?php endif; ?>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl shadow transition">
                <i class="fas fa-save mr-2"></i> Salvar Ícone
            </button>
        </form>
    </div>
</div>

<script>
    async function loadIcons() {
        const grid = document.getElementById('iconsGrid');
        const res = await apiFetch('/sys/icons');

        if (!res.data || res.data.length === 0) {
            grid.innerHTML = `<div class="col-span-full text-center text-slate-400 py-10">Nenhum ícone encontrado.</div>`;
            return;
        }

        grid.innerHTML = res.data.map(icon => {
            // Badge para diferenciar Global de Próprio
            const badge = icon.type === 'global' 
                ? `<span class="absolute top-2 left-2 bg-indigo-100 text-indigo-600 text-[9px] font-bold px-1.5 py-0.5 rounded border border-indigo-200">GLOBAL</span>` 
                : '';
            
            // Botão de deletar (Superadmin deleta tudo, Tenant deleta só o dele)
            // Se for global e eu não sou superadmin, esconde o botão delete
            const isSuperAdmin = <?php echo $isSuperAdmin ? 'true' : 'false'; ?>;
            const canDelete = (icon.type === 'custom') || isSuperAdmin;

            const deleteBtn = canDelete ? `
                <button onclick="deleteIcon(${icon.id})" class="absolute top-2 right-2 bg-red-100 text-red-500 w-6 h-6 rounded-full flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition hover:bg-red-200 shadow-sm">
                    <i class="fas fa-trash"></i>
                </button>` : '';

            return `
            <div class="group relative bg-white border border-slate-200 rounded-xl p-4 flex flex-col items-center justify-center hover:shadow-lg transition aspect-square">
                ${badge}
                <img src="${icon.url}" class="h-10 w-10 object-contain mb-3 drop-shadow-sm">
                <div class="text-xs font-bold text-slate-600 truncate w-full text-center">${icon.name}</div>
                ${deleteBtn}
            </div>
            `;
        }).join('');
    }

    // Preview da Imagem no Modal
    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('imgPreview').src = e.target.result;
                document.getElementById('imgPreview').classList.remove('hidden');
                document.getElementById('placeholderPreview').classList.add('hidden');
                
                // Sugere nome baseado no arquivo
                if(document.getElementById('nameInput').value === '') {
                    let name = input.files[0].name.split('.')[0];
                    // Capitaliza primeira letra
                    name = name.charAt(0).toUpperCase() + name.slice(1);
                    document.getElementById('nameInput').value = name;
                }
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Submit Upload
    document.getElementById('formUpload').onsubmit = async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = 'Enviando...'; btn.disabled = true;

        const formData = new FormData(e.target);

        try {
            const res = await fetch('/sys/icons/upload', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();

            if (json.success) {
                showToast('Ícone salvo!');
                closeModal();
                loadIcons();
            } else {
                showToast(json.error || 'Erro', 'error');
            }
        } catch (err) {
            showToast('Erro de conexão', 'error');
        } finally {
            btn.innerHTML = originalHtml; btn.disabled = false;
        }
    };

    async function deleteIcon(id) {
        if(!confirm('Excluir este ícone?')) return;
        const res = await apiFetch('/sys/icons/delete', { method: 'POST', body: JSON.stringify({id}) });
        if(res.success) { showToast('Removido'); loadIcons(); }
        else { showToast(res.error || 'Erro', 'error'); }
    }

    function openUploadModal() {
        document.getElementById('formUpload').reset();
        document.getElementById('imgPreview').classList.add('hidden');
        document.getElementById('placeholderPreview').classList.remove('hidden');
        document.getElementById('uploadModal').classList.remove('hidden');
    }
    function closeModal() { document.getElementById('uploadModal').classList.add('hidden'); }

    loadIcons();
</script>