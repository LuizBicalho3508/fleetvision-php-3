<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FleetVision - Gestão de Frotas Inteligente</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .hero-bg {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            position: relative;
            overflow: hidden;
        }
        .hero-bg::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url('https://www.transparenttextures.com/patterns/cubes.png');
            opacity: 0.1;
        }
        .blob {
            position: absolute;
            filter: blur(80px);
            z-index: 0;
            opacity: 0.4;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800">

    <nav class="fixed w-full z-50 bg-white/90 backdrop-blur-md border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-6 h-20 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold">
                    <i class="fas fa-satellite-dish"></i>
                </div>
                <span class="text-xl font-bold text-slate-800 tracking-tight">FLEET<span class="text-blue-600">VISION</span></span>
            </div>
            <div class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600">
                <a href="#funcionalidades" class="hover:text-blue-600 transition">Funcionalidades</a>
                <a href="#beneficios" class="hover:text-blue-600 transition">Benefícios</a>
                <a href="#contato" class="hover:text-blue-600 transition">Contato</a>
            </div>
            <a href="/login" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-full font-bold text-sm transition shadow-lg shadow-blue-500/30">
                Acessar Sistema
            </a>
        </div>
    </nav>

    <header class="hero-bg min-h-screen flex items-center pt-20 relative">
        <div class="blob bg-blue-500 w-96 h-96 rounded-full top-20 left-10"></div>
        <div class="blob bg-purple-500 w-96 h-96 rounded-full bottom-20 right-10"></div>

        <div class="max-w-7xl mx-auto px-6 grid md:grid-cols-2 gap-12 items-center relative z-10">
            <div class="text-white space-y-6">
                <span class="px-4 py-1.5 rounded-full bg-blue-500/20 border border-blue-400/30 text-blue-300 text-xs font-bold uppercase tracking-wider">
                    Nova Versão 3.0 Disponível
                </span>
                <h1 class="text-5xl md:text-6xl font-extrabold leading-tight">
                    O Controle Total da Sua <span class="text-blue-400">Frota</span> em Tempo Real.
                </h1>
                <p class="text-lg text-slate-300 leading-relaxed max-w-lg">
                    Reduza custos, aumente a segurança e tenha visibilidade completa dos seus veículos com a plataforma de telemetria mais avançada do mercado.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 pt-4">
                    <a href="/login" class="px-8 py-4 bg-blue-600 hover:bg-blue-500 text-white rounded-xl font-bold text-lg text-center transition shadow-xl shadow-blue-600/20">
                        Começar Agora
                    </a>
                    <a href="#funcionalidades" class="px-8 py-4 bg-white/10 hover:bg-white/20 border border-white/20 backdrop-blur text-white rounded-xl font-bold text-lg text-center transition">
                        Saiba Mais
                    </a>
                </div>
                <div class="pt-8 flex items-center gap-4 text-sm text-slate-400">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-check-circle text-green-400"></i> Monitoramento 24h
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-check-circle text-green-400"></i> App Mobile
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-check-circle text-green-400"></i> Relatórios PDF
                    </div>
                </div>
            </div>
            <div class="relative hidden md:block">
                <div class="relative bg-slate-800 rounded-2xl border border-slate-700 shadow-2xl p-4 rotate-3 hover:rotate-0 transition duration-500">
                    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-32 h-6 bg-slate-900 rounded-b-xl z-20"></div>
                    <div class="bg-slate-900 rounded-xl overflow-hidden aspect-video border border-slate-700 relative">
                        <div class="absolute inset-0 bg-slate-800 flex items-center justify-center">
                            <i class="fas fa-map-marked-alt text-slate-700 text-9xl opacity-20"></i>
                        </div>
                        <div class="absolute top-4 left-4 w-48 h-12 bg-slate-700/50 rounded-lg animate-pulse"></div>
                        <div class="absolute bottom-4 right-4 w-12 h-12 bg-blue-600 rounded-full shadow-lg flex items-center justify-center text-white">
                            <i class="fas fa-location-arrow"></i>
                        </div>
                        <div class="absolute top-20 right-10 bg-white p-3 rounded-lg shadow-lg flex items-center gap-3 animate-bounce">
                            <div class="w-8 h-8 bg-green-100 text-green-600 rounded-full flex items-center justify-center"><i class="fas fa-truck"></i></div>
                            <div>
                                <div class="text-xs font-bold text-slate-800">Veículo 05</div>
                                <div class="text-[10px] text-green-600 font-bold">Em Movimento</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <section id="funcionalidades" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <h2 class="text-3xl font-bold text-slate-800 mb-4">Tudo o que você precisa em um só lugar</h2>
                <p class="text-slate-500">Nossa plataforma integra rastreamento, gestão de manutenção e controle financeiro para maximizar a eficiência da sua operação.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="p-8 rounded-2xl bg-slate-50 border border-slate-100 hover:shadow-xl transition group">
                    <div class="w-14 h-14 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-3">Rastreamento Preciso</h3>
                    <p class="text-slate-500 text-sm leading-relaxed">
                        Visualize todos os seus veículos em tempo real no mapa. Histórico de rotas, paradas e velocidade com precisão.
                    </p>
                </div>

                <div class="p-8 rounded-2xl bg-slate-50 border border-slate-100 hover:shadow-xl transition group">
                    <div class="w-14 h-14 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-3">Relatórios Gerenciais</h3>
                    <p class="text-slate-500 text-sm leading-relaxed">
                        Tome decisões baseadas em dados. Relatórios de consumo, quilometragem, excesso de velocidade e muito mais.
                    </p>
                </div>

                <div class="p-8 rounded-2xl bg-slate-50 border border-slate-100 hover:shadow-xl transition group">
                    <div class="w-14 h-14 bg-orange-100 text-orange-600 rounded-xl flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-3">Aplicativo Mobile</h3>
                    <p class="text-slate-500 text-sm leading-relaxed">
                        Controle sua frota na palma da mão. Disponível para Android e iOS, com notificações push em tempo real.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-slate-900 text-slate-400 py-12 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 bg-blue-600 rounded flex items-center justify-center text-white text-xs">
                    <i class="fas fa-satellite-dish"></i>
                </div>
                <span class="text-lg font-bold text-white tracking-tight">FLEET<span class="text-blue-500">VISION</span></span>
            </div>
            <div class="text-sm">
                &copy; <?php echo date('Y'); ?> FleetVision. Todos os direitos reservados.
            </div>
            <div class="flex gap-4">
                <a href="#" class="hover:text-white transition"><i class="fab fa-instagram"></i></a>
                <a href="#" class="hover:text-white transition"><i class="fab fa-linkedin"></i></a>
                <a href="#" class="hover:text-white transition"><i class="fab fa-whatsapp"></i></a>
            </div>
        </div>
    </footer>

</body>
</html>