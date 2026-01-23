<div class="h-full flex flex-col bg-slate-50">
    <div class="bg-white border-b border-slate-200 px-8 py-6 shadow-sm z-10 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Documentação da API <span class="text-indigo-600 text-sm font-mono bg-indigo-50 px-2 py-1 rounded ml-2">v1.0</span></h1>
            <p class="text-slate-500 text-sm mt-1">Referência para integração Mobile e Externa.</p>
        </div>
        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-lg text-xs font-bold border border-green-200 uppercase">Ambiente Ativo</span>
    </div>

    <div class="flex-1 flex overflow-hidden">
        <div class="w-64 bg-white border-r border-slate-200 overflow-y-auto hidden md:block p-4">
            <nav class="space-y-1">
                <a href="#auth" class="block px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 rounded transition">1. Autenticação</a>
                <a href="#monitoring" class="block px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 rounded transition">2. Monitoramento</a>
                <a href="#commands" class="block px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 rounded transition">3. Comandos</a>
                <a href="#financial" class="block px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 rounded transition">4. Financeiro</a>
            </nav>
        </div>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            
            <div class="mb-8 p-4 bg-blue-50 border-l-4 border-blue-500 text-blue-900 text-sm rounded-r">
                <p class="font-bold mb-1">Endpoint Base:</p>
                <code class="bg-white px-2 py-1 rounded border border-blue-200"><?php echo (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]"; ?>/api</code>
                <p class="mt-2">Todas as respostas são em <strong>JSON</strong>. Em caso de erro, o status HTTP será 4xx ou 5xx.</p>
            </div>

            <section id="auth" class="mb-12">
                <h3 class="text-xl font-bold text-slate-800 mb-4 border-b pb-2">1. Autenticação</h3>
                <?php renderDocEndpoint('POST', '/auth/login', 'Login no Sistema', 
                    'Inicia a sessão e retorna cookies de autenticação.',
                    ['email' => 'string', 'password' => 'string', 'slug' => 'string (tenant)'],
                    ['success' => true, 'redirect' => '/dashboard']
                ); ?>
            </section>

            <section id="monitoring" class="mb-12">
                <h3 class="text-xl font-bold text-slate-800 mb-4 border-b pb-2">2. Monitoramento</h3>
                <?php renderDocEndpoint('GET', '/mapa', 'Posições em Tempo Real', 
                    'Retorna lista de veículos com lat/lon, velocidade e status.',
                    [],
                    [['id' => 10, 'name' => 'Carro 01', 'lat' => -23.55, 'lon' => -46.63, 'speed' => 60]]
                ); ?>
            </section>
            
            </div>
    </div>
</div>

<?php
function renderDocEndpoint($method, $url, $title, $desc, $params, $response) {
    $colors = ['GET'=>'blue','POST'=>'green','DELETE'=>'red','PUT'=>'orange'];
    $c = $colors[$method] ?? 'gray';
    $id = md5($url.$method);
    
    echo "<div class='bg-white border border-slate-200 rounded-lg mb-6 overflow-hidden shadow-sm hover:shadow-md transition'>
        <div class='p-4 border-b border-slate-100 cursor-pointer bg-slate-50/50 flex justify-between items-center' onclick=\"document.getElementById('code-$id').classList.toggle('hidden')\">
            <div class='flex items-center gap-3'>
                <span class='px-2 py-1 rounded text-[10px] font-mono font-bold border border-$c-200 bg-$c-50 text-$c-700'>$method</span>
                <span class='font-mono text-xs text-slate-600'>$url</span>
                <span class='text-sm font-bold text-slate-700 ml-2'>$title</span>
            </div>
            <i class='fas fa-chevron-down text-slate-400 text-xs'></i>
        </div>
        <div id='code-$id' class='hidden bg-slate-900 p-4 text-xs font-mono text-slate-300'>
            <p class='text-slate-500 mb-2 uppercase font-bold text-[10px]'>Parâmetros</p>
            <pre class='mb-4 text-blue-300'>".json_encode($params, JSON_PRETTY_PRINT)."</pre>
            <p class='text-slate-500 mb-2 uppercase font-bold text-[10px]'>Resposta</p>
            <pre class='text-green-400'>".json_encode($response, JSON_PRETTY_PRINT)."</pre>
        </div>
    </div>";
}
?>