<?php
/**
 * Layout principal — Header, Sidebar, Footer compartilhados
 * O Consultor — Sistema Operacional Empresarial
 */

$usuario = Auth::usuario();
$perfil = Auth::perfil();
$paginaAtual = $_GET['url'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tituloPagina ?? 'O Consultor') ?> — O Consultor</title>
    
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { DEFAULT: '#1E3A5F', 50: '#E8EDF3', 100: '#D1DBE7', 600: '#1E3A5F', 700: '#162D4A', 800: '#0F1F35' },
                        accent: '#E07B00',
                        success: '#1a7a1a',
                        danger: '#CC2222',
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/custom.css">
    
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
        .sidebar-link.active { background-color: rgba(255,255,255,0.1); border-left: 3px solid #E07B00; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen" x-data="{ sidebarOpen: true }">

    <!-- Skip to main content (Accessibility) -->
    <a href="#main-content" class="sr-only">Ir para o conteúdo principal</a>

    <!-- Top Bar -->
    <header class="fixed top-0 left-0 right-0 z-50 bg-white border-b border-gray-200 shadow-sm" role="banner">
        <div class="flex items-center justify-between h-16 px-4">
            <!-- Logo + Toggle -->
            <div class="flex items-center gap-4">
                <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 hover:text-primary" aria-label="Alternar menu">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <a href="<?= APP_URL ?>/dashboard" class="flex items-center">
                    <span class="text-xl font-bold text-primary tracking-wide">O CONSULTOR</span>
                </a>
            </div>

            <!-- Notificações (Sino) + User Menu -->
            <div class="flex items-center gap-3" x-data="{ userMenu: false, notifOpen: false, notifCount: 4 }">
                <!-- Sino -->
                <div class="relative">
                    <button @click="notifOpen = !notifOpen" class="relative p-2 text-gray-500 hover:text-primary hover:bg-gray-100 rounded-lg transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <span x-show="notifCount > 0" class="absolute -top-0.5 -right-0.5 w-4.5 h-4.5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center" x-text="notifCount"></span>
                    </button>
                    <!-- Dropdown Notificações -->
                    <div x-show="notifOpen" @click.away="notifOpen = false" x-transition
                         class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border border-gray-200 z-50 overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-800">Notificações</span>
                            <a href="<?= APP_URL ?>/alertas" class="text-xs text-primary hover:underline">Ver todos</a>
                        </div>
                        <div class="max-h-80 overflow-y-auto divide-y divide-gray-100" id="notif-dropdown-list">
                            <div class="p-3 text-xs text-gray-500 text-center">Carregando...</div>
                        </div>
                    </div>
                </div>

                <span class="hidden md:block text-sm text-gray-600"><?= htmlspecialchars($usuario['nome'] ?? '') ?></span>
                <div class="relative">
                    <button @click="userMenu = !userMenu" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-100">
                        <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center text-sm font-semibold">
                            <?= strtoupper(substr($usuario['nome'] ?? 'U', 0, 1)) ?>
                        </div>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="userMenu" @click.away="userMenu = false" x-transition
                         class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-50">
                        <a href="<?= APP_URL ?>/perfil" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Meu Perfil</a>
                        <a href="<?= APP_URL ?>/alertas" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Notificações</a>
                        <hr class="my-1">
                        <a href="<?= APP_URL ?>/logout" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">Sair</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Sidebar -->
    <aside class="fixed top-16 left-0 bottom-0 z-40 w-64 bg-primary text-white transition-transform duration-300 flex flex-col"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
        
        <!-- Menu com scroll -->
        <nav class="flex-1 overflow-y-auto p-4 space-y-1">
            <?php
            // Menu conforme perfil
            $menuAdmin = [
                ['url' => 'dashboard', 'label' => 'Dashboard', 'icone' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                ['url' => 'admin/usuarios', 'label' => 'Clientes', 'icone' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                ['url' => 'diagnostico', 'label' => 'Diagnósticos', 'icone' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
                ['url' => 'plano-de-acao', 'label' => 'Planos de Ação', 'icone' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['url' => 'manual-operacional', 'label' => 'Manual Operacional', 'icone' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
                ['url' => 'central-de-conteudo', 'label' => 'Central de Conteúdo', 'icone' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
                ['url' => 'maquina-de-conteudo', 'label' => 'Máquina de Conteúdo', 'icone' => 'M13 10V3L4 14h7v7l9-11h-7z'],
                ['url' => 'parceiros', 'label' => 'Parceiros', 'icone' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
                ['url' => 'governanca', 'label' => 'Governança', 'icone' => 'M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3'],
                ['url' => 'admin', 'label' => 'Configurações', 'icone' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
            ];

            $menuConsultor = [
                ['url' => 'dashboard', 'label' => 'Dashboard', 'icone' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                ['url' => 'admin/usuarios', 'label' => 'Clientes', 'icone' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                ['url' => 'diagnostico', 'label' => 'Diagnósticos', 'icone' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
                ['url' => 'plano-de-acao', 'label' => 'Planos de Ação', 'icone' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['url' => 'manual-operacional', 'label' => 'Manual Operacional', 'icone' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
                ['url' => 'parceiros', 'label' => 'Parceiros', 'icone' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
            ];

            $menuCliente = [
                ['url' => 'dashboard', 'label' => 'Meu Painel', 'icone' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                ['url' => 'diagnostico', 'label' => 'Diagnóstico', 'icone' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
                ['url' => 'plano-de-acao', 'label' => 'Plano de Ação', 'icone' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['url' => 'manual-operacional', 'label' => 'Manual da Empresa', 'icone' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
                ['url' => 'central-de-conteudo', 'label' => 'Central de Conteúdo', 'icone' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
                ['url' => 'academy/sso', 'label' => 'Academy', 'icone' => 'M12 14l9-5-9-5-9 5 9 5z M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z'],
                ['url' => 'perfil', 'label' => 'Minha Conta', 'icone' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
            ];

            $menu = match($perfil) {
                'ADMIN_HOLDING' => $menuAdmin,
                'CONSULTOR_INTERNO' => $menuConsultor,
                default => $menuCliente,
            };

            foreach ($menu as $item):
                $ativo = (strpos($paginaAtual, $item['url']) === 0 || ($item['url'] === 'dashboard' && empty($paginaAtual)));
            ?>
            <a href="<?= APP_URL ?>/<?= $item['url'] ?>" 
               class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-white/80 hover:text-white hover:bg-white/10 transition-all <?= $ativo ? 'active' : '' ?>">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="<?= $item['icone'] ?>"/>
                </svg>
                <span><?= $item['label'] ?></span>
            </a>
            <?php endforeach; ?>
        </nav>

        <!-- Perfil Badge no sidebar (fixo embaixo) -->
        <div class="flex-shrink-0 p-4 border-t border-white/10 bg-primary">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-accent text-white flex items-center justify-center text-xs font-bold">
                    <?= strtoupper(substr($usuario['nome'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-white truncate"><?= htmlspecialchars($usuario['nome'] ?? '') ?></p>
                    <p class="text-xs text-white/50"><?= $perfil ?></p>
                </div>
            </div>
        </div>
    </aside>

    <!-- Mobile Overlay (fecha sidebar ao clicar fora) -->
    <div x-show="sidebarOpen" @click="sidebarOpen = false" 
         class="fixed inset-0 bg-black/50 z-30 lg:hidden" x-transition.opacity></div>

    <!-- Main Content -->
    <main id="main-content" role="main" aria-label="Conteúdo principal" class="pt-16 transition-all duration-300" :class="sidebarOpen ? 'lg:ml-64' : 'lg:ml-0'">
        <div class="p-6">
            <!-- Flash Messages -->
            <?= Flash::renderizar() ?>

            <!-- Toast Container -->
            <div id="toast-container" class="fixed top-20 right-4 z-50 space-y-2"></div>

            <!-- Page Content -->
            <?php if (isset($conteudo)) echo $conteudo; ?>

        </div>
    </main>

    <!-- Custom JS -->
    <script src="<?= APP_URL ?>/assets/js/app.js"></script>

    <!-- Sistema de Notificações -->
    <script>
    // Carregar notificações no dropdown do sino
    async function carregarNotificacoes() {
        try {
            const res = await fetch('<?= APP_URL ?>/alertas/recentes');
            const data = await res.json();
            if (!data.sucesso) return;

            const lista = document.getElementById('notif-dropdown-list');
            if (!lista) return;

            if (data.alertas.length === 0) {
                lista.innerHTML = '<div class="p-4 text-xs text-gray-500 text-center">Nenhuma notificação.</div>';
                return;
            }

            const prioIcons = { alta: '🔴', media: '🟡', baixa: '🔵', info: 'ℹ️' };
            lista.innerHTML = data.alertas.map(a => `
                <a href="<?= APP_URL ?>${a.link}" class="block px-4 py-3 hover:bg-gray-50 transition ${!a.lido ? 'bg-blue-50/30' : ''}">
                    <div class="flex items-start gap-2">
                        <span class="text-xs mt-0.5">${prioIcons[a.prioridade] || 'ℹ️'}</span>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-gray-800 ${!a.lido ? 'font-semibold' : ''}">${a.titulo}</p>
                            <p class="text-[10px] text-gray-500 truncate mt-0.5">${a.descricao}</p>
                        </div>
                        <span class="text-[10px] text-gray-400 flex-shrink-0">${new Date(a.data).toLocaleDateString('pt-BR', {day:'2-digit', month:'2-digit', hour:'2-digit', minute:'2-digit'})}</span>
                    </div>
                </a>
            `).join('');

        } catch(e) { /* silenciar erros de rede */ }
    }

    // Toast in-app (canto inferior direito)
    function showNotifToast(mensagem, tipo = 'info') {
        const cores = { sucesso: 'bg-green-600', erro: 'bg-red-600', aviso: 'bg-yellow-600', info: 'bg-blue-600' };
        const container = document.getElementById('toast-container');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `${cores[tipo] || cores.info} text-white px-4 py-3 rounded-lg shadow-lg text-sm max-w-sm toast-enter flex items-center gap-2`;
        toast.innerHTML = `<span>${mensagem}</span><button onclick="this.parentElement.remove()" class="ml-2 text-white/70 hover:text-white">&times;</button>`;
        container.appendChild(toast);
        setTimeout(() => { toast.classList.add('toast-exit'); setTimeout(() => toast.remove(), 300); }, 5000);
    }

    // Carregar notificações ao abrir dropdown
    document.addEventListener('DOMContentLoaded', () => {
        carregarNotificacoes();
        // Atualizar a cada 60 segundos
        setInterval(carregarNotificacoes, 60000);
    });
    </script>
</body>
</html>
