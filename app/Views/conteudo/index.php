<?php $tituloPagina = 'Central de Conteúdo'; ?>
<?php
/**
 * Renderiza um card de notícia: imagem de capa, headline e resumo.
 * O card inteiro leva à análise interna (5 blocos); um link secundário
 * abre a notícia na fonte original (URL externa).
 */
if (!function_exists('renderCardNoticia')) {
    function renderCardNoticia(array $noticia): string {
        $catBadge = match($noticia['categoria'] ?? '') {
            'Mercado' => 'bg-blue-100 text-blue-700',
            'Tecnologia' => 'bg-purple-100 text-purple-700',
            'Regulamentação' => 'bg-red-100 text-red-700',
            'Tendência' => 'bg-green-100 text-green-700',
            'Negócio' => 'bg-orange-100 text-orange-700',
            default => 'bg-gray-100 text-gray-700',
        };
        $relBadge = ($noticia['relevancia'] ?? '') === 'alta' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600';
        $imagem = trim((string) ($noticia['imagem_url'] ?? ''));
        $dataFmt = !empty($noticia['data']) ? date('d/m/Y', strtotime($noticia['data'])) : '';
        $urlExterna = trim((string) ($noticia['url'] ?? ''));

        ob_start(); ?>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition flex flex-col" data-noticia-id="<?= (int) $noticia['id'] ?>">
            <a href="<?= APP_URL ?>/central-de-conteudo/noticia?id=<?= (int) $noticia['id'] ?>" class="block">
                <?php if ($imagem !== ''): ?>
                <img src="<?= htmlspecialchars($imagem) ?>" alt="" loading="lazy"
                     onerror="this.closest('a').querySelector('.card-noticia-fallback').classList.remove('hidden'); this.remove();"
                     class="w-full h-40 object-cover bg-gray-100">
                <div class="card-noticia-fallback hidden w-full h-40 bg-gray-100 flex items-center justify-center text-3xl">📰</div>
                <?php else: ?>
                <div class="w-full h-40 bg-gray-100 flex items-center justify-center text-3xl">📰</div>
                <?php endif; ?>
            </a>
            <div class="p-4 flex flex-col flex-1">
                <div class="flex items-center gap-2 mb-2 flex-wrap">
                    <span class="text-xs text-gray-400 font-medium"><?= htmlspecialchars($noticia['fonte'] ?? '') ?></span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $catBadge ?>"><?= htmlspecialchars($noticia['categoria'] ?? '') ?></span>
                    <span class="text-xs text-gray-400"><?= $dataFmt ?></span>
                </div>
                <a href="<?= APP_URL ?>/central-de-conteudo/noticia?id=<?= (int) $noticia['id'] ?>" class="block">
                    <h3 class="text-sm font-semibold text-gray-800 mb-1 leading-snug hover:text-primary"><?= htmlspecialchars($noticia['titulo'] ?? '') ?></h3>
                </a>
                <p class="text-xs text-gray-500 flex-1"><?= htmlspecialchars($noticia['resumo'] ?? '') ?></p>
                <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100">
                    <span class="text-xs font-bold px-2 py-0.5 rounded <?= $relBadge ?>"><?= ucfirst($noticia['relevancia'] ?? '') ?></span>
                    <div class="flex items-center gap-3">
                        <a href="<?= APP_URL ?>/central-de-conteudo/noticia?id=<?= (int) $noticia['id'] ?>" class="text-xs text-primary font-medium hover:underline">Ver análise →</a>
                        <?php if ($urlExterna !== ''): ?>
                        <a href="<?= htmlspecialchars($urlExterna) ?>" target="_blank" rel="noopener noreferrer" class="text-xs text-gray-400 hover:text-gray-600" title="Abrir notícia original">🔗</a>
                        <?php endif; ?>
                        <button type="button" onclick="excluirNoticia(<?= (int) $noticia['id'] ?>, this)" class="text-xs text-gray-400 hover:text-red-600" title="Excluir notícia">🗑️</button>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
?>
<?php ob_start(); ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Central de Conteúdo</li>
    </ol>
</nav>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Central de Conteúdo</h1>
        <p class="text-gray-500 mt-1">Notícias, cursos e sua biblioteca de conteúdo.</p>
    </div>
    <?php if (Auth::temAlgumPerfil([Auth::ADMIN_HOLDING, Auth::CONSULTOR_INTERNO])): ?>
    <a href="<?= APP_URL ?>/central-de-conteudo/admin" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">⚙️ Gerenciar</a>
    <?php endif; ?>
</div>

<!-- Abas -->
<div x-data="{ aba: 'visao' }">
    <div class="border-b border-gray-200 mb-6">
        <nav class="flex gap-0 overflow-x-auto">
            <button @click="aba = 'visao'" :class="aba === 'visao' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500'" class="px-5 py-3 text-sm whitespace-nowrap transition">📊 Visão Geral</button>
            <button @click="aba = 'noticias'" :class="aba === 'noticias' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500'" class="px-5 py-3 text-sm whitespace-nowrap transition">📰 Notícias e Atualidades</button>
            <button @click="aba = 'calendario'; carregarCalendario()" :class="aba === 'calendario' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500'" class="px-5 py-3 text-sm whitespace-nowrap transition">📅 Calendário de Conteúdo</button>
            <button @click="aba = 'academy'" :class="aba === 'academy' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500'" class="px-5 py-3 text-sm whitespace-nowrap transition">🎓 Academy</button>
            <button @click="aba = 'biblioteca'" :class="aba === 'biblioteca' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500'" class="px-5 py-3 text-sm whitespace-nowrap transition">📚 Biblioteca</button>
            <button @click="aba = 'brandbook'" :class="aba === 'brandbook' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500'" class="px-5 py-3 text-sm whitespace-nowrap transition">📖 Brand Book</button>
            <button @click="aba = 'concorrencia'; carregarConcorrentes()" :class="aba === 'concorrencia' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500'" class="px-5 py-3 text-sm whitespace-nowrap transition">🔎 Análise</button>
            <button @click="aba = 'configuracoes'" :class="aba === 'configuracoes' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500'" class="px-5 py-3 text-sm whitespace-nowrap transition">⚙️ Configurações de Conteúdo</button>
        </nav>
    </div>

    <!-- ABA: VISÃO GERAL -->
    <div x-show="aba === 'visao'" x-transition>
        <?php $vg = $dados['visao_geral'] ?? []; $vgc = $vg['contadores'] ?? []; ?>

        <!-- Alertas -->
        <?php if (!empty($vg['alertas'])): ?>
        <div class="space-y-2 mb-6">
            <?php foreach ($vg['alertas'] as $al): ?>
            <div class="flex items-start gap-2 p-3 rounded-lg text-sm <?= ($al['nivel'] ?? '') === 'alerta' ? 'bg-red-50 border border-red-200 text-red-700' : 'bg-yellow-50 border border-yellow-200 text-yellow-800' ?>">
                <span><?= ($al['nivel'] ?? '') === 'alerta' ? '⛔' : '⚠️' ?></span>
                <span><?= htmlspecialchars($al['mensagem']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Contadores -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 text-center">
                <p class="text-3xl font-bold text-primary"><?= (int) ($vgc['planejados'] ?? 0) ?></p>
                <p class="text-xs text-gray-500 mt-1">Planejados</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 text-center">
                <p class="text-3xl font-bold text-green-600"><?= (int) ($vgc['gerados'] ?? 0) ?></p>
                <p class="text-xs text-gray-500 mt-1">Gerados</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 text-center">
                <p class="text-3xl font-bold text-yellow-600"><?= (int) ($vgc['em_revisao'] ?? 0) ?></p>
                <p class="text-xs text-gray-500 mt-1">Pendentes de revisão</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 text-center">
                <p class="text-3xl font-bold text-gray-800"><?= (int) ($vg['concorrentes']['total'] ?? 0) ?></p>
                <p class="text-xs text-gray-500 mt-1">Concorrentes monitorados</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Próximas datas -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-800 mb-3">📅 Próximas datas relevantes</h3>
                <?php if (empty($vg['proximas_datas'])): ?>
                <p class="text-sm text-gray-400">Nenhuma data identificada. Gere o calendário na aba correspondente.</p>
                <?php else: ?>
                <ul class="space-y-2 text-sm">
                    <?php foreach ($vg['proximas_datas'] as $d): ?>
                    <li class="flex items-center justify-between">
                        <span class="text-gray-700"><?= htmlspecialchars($d['nome']) ?></span>
                        <span class="text-xs text-gray-400"><?= date('d/m', strtotime($d['proxima_ocorrencia'])) ?> (<?= (int) $d['dias_ate'] ?>d)</span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>

            <!-- Notícias recentes -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-800 mb-3">📰 Notícias recentes</h3>
                <?php if (empty($vg['noticias_recentes'])): ?>
                <p class="text-sm text-gray-400">Nenhuma notícia recente.</p>
                <?php else: ?>
                <ul class="space-y-2 text-sm">
                    <?php foreach ($vg['noticias_recentes'] as $n): ?>
                    <li class="flex items-center justify-between gap-2">
                        <a href="<?= APP_URL ?>/central-de-conteudo/noticia?id=<?= (int) $n['id'] ?>" class="text-gray-700 hover:text-primary truncate"><?= htmlspecialchars($n['titulo']) ?></a>
                        <span class="text-xs text-gray-400 flex-shrink-0"><?= !empty($n['data']) ? date('d/m', strtotime($n['data'])) : '' ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>

            <!-- Melhores conteúdos de concorrentes -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-800 mb-3">🏆 Melhores conteúdos de concorrentes</h3>
                <?php if (empty($vg['melhores_concorrentes'])): ?>
                <p class="text-sm text-gray-400">Sem dados de concorrência ainda.</p>
                <?php else: ?>
                <ul class="space-y-2 text-sm">
                    <?php foreach ($vg['melhores_concorrentes'] as $p): ?>
                    <li class="flex items-center justify-between gap-2">
                        <span class="text-gray-700 truncate"><?= htmlspecialchars(mb_substr((string) ($p['titulo'] ?: 'Publicação'), 0, 50)) ?> <span class="text-gray-400">· <?= htmlspecialchars($p['concorrente']) ?></span></span>
                        <span class="text-xs font-medium text-gray-600 flex-shrink-0"><?= (int) $p['engajamento_absoluto'] ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>

            <!-- Concorrência: última coleta -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-800 mb-3">🔎 Concorrência</h3>
                <p class="text-sm text-gray-600">Concorrentes monitorados: <strong><?= (int) ($vg['concorrentes']['total'] ?? 0) ?></strong></p>
                <p class="text-sm text-gray-600 mt-1">Última coleta:
                    <strong><?= !empty($vg['concorrentes']['ultima_coleta']) ? date('d/m/Y H:i', strtotime($vg['concorrentes']['ultima_coleta'])) : 'Nunca' ?></strong>
                </p>
                <button @click="aba = 'concorrencia'; carregarConcorrentes()" class="mt-3 text-sm text-primary font-medium hover:underline">Ir para Scrap da Concorrência →</button>
            </div>
        </div>
    </div>

    <!-- ABA 1: NOTÍCIAS -->
    <div x-show="aba === 'noticias'" x-transition style="display:none;">
        <!-- Barra de ações -->
        <div class="flex items-center justify-between gap-3 mb-4">
            <button type="button" onclick="abrirModalConfig()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">✏️ Editar informações do conteúdo</button>
            <div class="flex items-center gap-3">
                <button onclick="buscarAgora()" class="px-4 py-2 bg-accent text-white rounded-lg text-sm font-medium hover:bg-orange-700">🔍 Buscar agora</button>
                <button type="button" onclick="limparNoticias()" class="px-4 py-2 border border-red-300 text-red-600 rounded-lg text-sm font-medium hover:bg-red-50">🗑️ Limpar todas</button>
            </div>
        </div>

        <!-- Filtros -->
        <div class="flex flex-wrap gap-2 mb-4">
            <select class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs outline-none"><option>Todas categorias</option><option>Mercado</option><option>Tecnologia</option><option>Regulamentação</option><option>Tendência</option><option>Negócio</option></select>
            <select class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs outline-none"><option>Toda relevância</option><option>Alta</option><option>Média</option><option>Baixa</option></select>
        </div>

        <!-- Feed de Notícias (cards com imagem, headline e resumo) -->
        <div id="feed-noticias" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php if (empty($dados['noticias'])): ?>
            <div id="feed-vazio" class="col-span-full bg-white rounded-lg border border-gray-200 p-8 text-center text-gray-500 text-sm">
                Nenhuma notícia ainda. Configure seu perfil de busca acima e clique em "Buscar agora".
            </div>
            <?php else: ?>
                <?php foreach ($dados['noticias'] as $noticia): echo renderCardNoticia($noticia); endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Paginação: mais nova (página 1) → mais antiga -->
        <?php $pag = $dados['paginacao'] ?? ['pagina_atual' => 1, 'total_paginas' => 1, 'total_itens' => 0]; ?>
        <div id="paginacao-noticias" class="flex items-center justify-between mt-6" data-pagina-atual="<?= (int) $pag['pagina_atual'] ?>" data-total-paginas="<?= (int) $pag['total_paginas'] ?>">
            <p id="paginacao-total" class="text-xs text-gray-400"><?= (int) $pag['total_itens'] ?> notícia(s) no total</p>
            <div id="paginacao-controles" class="flex items-center gap-1"></div>
        </div>
    </div>

    <!-- ABA: CALENDÁRIO DE CONTEÚDO -->
    <div x-show="aba === 'calendario'" x-transition style="display:none;">
        <div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">Calendário de Conteúdo</h2>
                <p class="text-sm text-gray-500">Datas comemorativas e sugestões relevantes ao seu nicho, com data ideal de publicação.</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="gerarCalendario(this)" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-700 transition">✨ Identificar datas do meu nicho</button>
                <button type="button" onclick="gerarSemanal(this)" class="px-4 py-2 border border-primary text-primary rounded-lg text-sm font-medium hover:bg-primary/5 transition">🗓️ Sugestões da semana</button>
                <button type="button" onclick="abrirModalItemCalendario()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">+ Item manual</button>
            </div>
        </div>

        <!-- Próximas datas -->
        <div id="calendario-proximas" class="mb-6"></div>

        <!-- Itens do calendário -->
        <div id="calendario-lista" class="space-y-3"></div>
        <div id="calendario-vazio" class="hidden bg-white rounded-lg border border-gray-200 p-8 text-center text-gray-500 text-sm">
            Seu calendário está vazio. Clique em "Identificar datas do meu nicho" para gerar sugestões, ou adicione um item manual.
        </div>
    </div>

    <!-- ABA 2: ACADEMY -->
    <div x-show="aba === 'academy'" x-transition style="display:none;">
        <div class="max-w-2xl mx-auto py-8">
            <!-- Card Central Academy -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-8 text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-primary/10 rounded-full flex items-center justify-center">
                    <span class="text-3xl">🎓</span>
                </div>
                <h2 class="text-xl font-bold text-gray-800 mb-2">My Academy</h2>
                <p class="text-sm text-gray-500 mb-6">Seus cursos estão na plataforma My Academy. Clique abaixo para acessar com seu login já configurado.</p>

                <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center font-bold">
                            <?= strtoupper(substr($dados['usuario']['nome'] ?? 'U', 0, 1)) ?>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-800"><?= htmlspecialchars($dados['usuario']['nome'] ?? '') ?></p>
                            <p class="text-xs text-gray-400"><?= htmlspecialchars($dados['usuario']['email'] ?? '') ?></p>
                        </div>
                    </div>
                </div>

                <p class="text-xs text-gray-500 mb-6">Acesse sua área de alunos, cursos, comunidade e certificados diretamente na plataforma.</p>

                <a href="<?= APP_URL ?>/academy/sso" target="_blank"
                   class="inline-block w-full bg-primary text-white py-3.5 rounded-lg font-semibold text-sm hover:bg-primary-700 transition">
                    🚀 Acessar meus cursos
                </a>

                <p class="text-xs text-gray-400 mt-4">Você será redirecionado para <?= htmlspecialchars($dados['academy_url']) ?> com login automático (SSO).</p>
            </div>
        </div>
    </div>

    <!-- ABA 3: BIBLIOTECA -->
    <div x-show="aba === 'biblioteca'" x-transition style="display:none;">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-1">📚 Biblioteca de conteúdo</h2>
            <p class="text-sm text-gray-500 mb-4">Envie PDFs (artigos, e-books, estudos, materiais de referência) para formar sua base de literatura. Esses documentos ficam disponíveis para uso na Máquina de Conteúdo.</p>

            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-primary transition">
                <input type="file" id="biblioteca-input" accept=".pdf" multiple class="hidden" onchange="enviarArquivosBiblioteca(this.files)">
                <label for="biblioteca-input" class="cursor-pointer">
                    <div class="text-4xl mb-2">📄</div>
                    <p class="text-sm font-medium text-primary">Clique para selecionar PDFs</p>
                    <p class="text-xs text-gray-400 mt-1">Somente arquivos PDF, até 5MB cada</p>
                </label>
                <div id="biblioteca-upload-status" class="text-xs text-gray-500 mt-3 hidden"></div>
            </div>
        </div>

        <div id="biblioteca-lista" class="space-y-2">
            <!-- Documentos carregados via JS -->
        </div>
        <div id="biblioteca-vazia" class="bg-white rounded-lg border border-gray-200 p-8 text-center text-gray-500 text-sm">
            Nenhum documento na biblioteca ainda. Envie PDFs acima para começar.
        </div>
    </div>

    <!-- ABA: BRAND BOOK -->
    <div x-show="aba === 'brandbook'" x-transition style="display:none;">
        <?php $marca = $dados['marca_brand_book'] ?? null; ?>
        <?php if (!$marca): ?>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center text-gray-500 text-sm">
            Nenhuma marca cadastrada para esta empresa. Crie uma marca na Máquina de Conteúdo para configurar o Brand Book.
        </div>
        <?php else: ?>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 max-w-3xl">
            <h3 class="font-semibold text-gray-800 mb-1">📖 Brand Book da Marca</h3>
            <p class="text-sm text-gray-500 mb-5">Configurações de identidade usadas na geração de conteúdo. Edite e salve.</p>

            <form id="form-branding" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
                <input type="hidden" name="marca_id" value="<?= (int) $marca['id'] ?>">

                <!-- Logo -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Logo da marca</label>
                    <div class="flex items-center gap-4">
                        <div class="w-24 h-24 rounded-lg border border-gray-200 bg-gray-50 flex items-center justify-center overflow-hidden">
                            <img id="logo-preview" src="<?= !empty($marca['logo_url']) ? htmlspecialchars(APP_URL . $marca['logo_url']) : '' ?>" class="max-w-full max-h-full object-contain <?= empty($marca['logo_url']) ? 'hidden' : '' ?>">
                            <span id="logo-vazio" class="text-xs text-gray-400 <?= !empty($marca['logo_url']) ? 'hidden' : '' ?>">Sem logo</span>
                        </div>
                        <div>
                            <label class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-700 cursor-pointer inline-block">
                                📤 Enviar logo (PNG/SVG)
                                <input type="file" accept=".png,.svg,.jpg,.jpeg,.webp" class="hidden" onchange="uploadLogo(this)">
                            </label>
                            <p class="text-xs text-gray-400 mt-1">Preferencialmente PNG com fundo transparente. Será posicionado de forma estratégica e equilibrada nas imagens geradas.</p>
                        </div>
                    </div>
                </div>

                <!-- Imagem de fechamento do carrossel -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Imagem de fechamento (último slide do carrossel)</label>
                    <div class="flex items-center gap-4">
                        <div class="w-24 h-28 rounded-lg border border-gray-200 bg-gray-50 flex items-center justify-center overflow-hidden">
                            <img id="fechamento-preview" src="<?= !empty($marca['imagem_fechamento_url']) ? htmlspecialchars(APP_URL . $marca['imagem_fechamento_url']) : '' ?>" class="max-w-full max-h-full object-contain <?= empty($marca['imagem_fechamento_url']) ? 'hidden' : '' ?>">
                            <span id="fechamento-vazio" class="text-xs text-gray-400 text-center px-1 <?= !empty($marca['imagem_fechamento_url']) ? 'hidden' : '' ?>">Sem imagem</span>
                        </div>
                        <div>
                            <label class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-700 cursor-pointer inline-block">
                                📤 Enviar imagem de fechamento
                                <input type="file" accept=".png,.jpg,.jpeg,.webp" class="hidden" onchange="uploadFechamento(this)">
                            </label>
                            <p class="text-xs text-gray-400 mt-1">Esta imagem fixa será usada como o ÚLTIMO slide dos carrosséis (fechamento), no lugar de uma gerada pela IA. Formato vertical (retrato) recomendado.</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Nome</label><input type="text" name="nome" value="<?= htmlspecialchars($marca['nome'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Nicho/Setor</label><input type="text" name="nicho" value="<?= htmlspecialchars($marca['nicho'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
                    <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Público-alvo</label><input type="text" name="publico_alvo" value="<?= htmlspecialchars($marca['publico_alvo'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Tom de voz</label><input type="text" name="tom" value="<?= htmlspecialchars($marca['tom'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Arquétipo</label><input type="text" name="arquetipo" value="<?= htmlspecialchars($marca['arquetipo'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Palavras que USA</label><input type="text" name="palavras_usa" value="<?= htmlspecialchars($marca['palavras_usa'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Palavras que NUNCA usa</label><input type="text" name="palavras_nunca" value="<?= htmlspecialchars($marca['palavras_nunca'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Diferenciais competitivos</label>
                    <textarea name="diferenciais_competitivos" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none"><?= htmlspecialchars($marca['diferenciais_competitivos'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Produtos/Serviços</label>
                    <textarea name="produtos_servicos" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none"><?= htmlspecialchars($marca['produtos_servicos'] ?? '') ?></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Concorrentes</label><input type="text" name="concorrentes" value="<?= htmlspecialchars($marca['concorrentes'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Objetivos de conteúdo</label><input type="text" name="objetivos_conteudo" value="<?= htmlspecialchars(is_array(json_decode($marca['objetivos_conteudo'] ?? '[]', true)) ? implode(', ', json_decode($marca['objetivos_conteudo'] ?? '[]', true)) : ($marca['objetivos_conteudo'] ?? '')) ?>" placeholder="Educar, Engajar, Converter..." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Formatos preferenciais</label><input type="text" name="formatos_preferenciais" value="<?= htmlspecialchars(is_array(json_decode($marca['formatos_preferenciais'] ?? '[]', true)) ? implode(', ', json_decode($marca['formatos_preferenciais'] ?? '[]', true)) : ($marca['formatos_preferenciais'] ?? '')) ?>" placeholder="Carrossel, Post, Story..." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Paleta de cores (hex, separados por vírgula)</label><input type="text" name="paleta_cores" value="<?= htmlspecialchars(is_array($marca['paleta_cores'] ?? null) ? implode(', ', $marca['paleta_cores']) : (is_array(json_decode($marca['paleta_cores'] ?? '[]', true)) ? implode(', ', json_decode($marca['paleta_cores'] ?? '[]', true)) : ($marca['paleta_cores'] ?? ''))) ?>" placeholder="#1E3A5F, #E07B00, #FFFFFF" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Fonte principal</label><input type="text" name="fonte_principal" value="<?= htmlspecialchars($marca['fonte_principal'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Fonte secundária</label><input type="text" name="fonte_secundaria" value="<?= htmlspecialchars($marca['fonte_secundaria'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Estilo visual</label><input type="text" name="estilo_visual" value="<?= htmlspecialchars($marca['estilo_visual'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Direção fotográfica</label><input type="text" name="direcao_foto" value="<?= htmlspecialchars($marca['direcao_foto'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prompt Master (base de toda geração de texto)</label>
                    <textarea name="prompt_master" rows="5" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none font-mono text-xs"><?= htmlspecialchars($marca['prompt_master'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prompt de estilo visual (base das imagens)</label>
                    <textarea name="prompt_dalle" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none font-mono text-xs"><?= htmlspecialchars($marca['prompt_dalle'] ?? '') ?></textarea>
                </div>

                <div class="pt-2">
                    <button type="submit" id="btn-salvar-branding" class="px-5 py-2.5 bg-primary text-white rounded-lg text-sm font-semibold hover:bg-primary-700">💾 Salvar Brand Book</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <!-- ABA: SCRAP DA CONCORRÊNCIA -->
    <div x-show="aba === 'concorrencia'" x-transition style="display:none;">
        <div class="flex items-center justify-between gap-3 mb-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">Scrap da Concorrência</h2>
                <p class="text-sm text-gray-500">Monitore perfis públicos de concorrentes e transforme os dados em inteligência para a Máquina de Conteúdo.</p>
            </div>
            <button type="button" onclick="abrirModalConcorrente()" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-700 transition">+ Adicionar concorrente</button>
        </div>

        <div id="concorrencia-aviso" class="hidden bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4 text-sm text-yellow-800"></div>

        <div id="concorrentes-lista" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Cards carregados via JS -->
        </div>
        <div id="concorrentes-vazio" class="hidden bg-white rounded-lg border border-gray-200 p-8 text-center text-gray-500 text-sm">
            Nenhum concorrente cadastrado ainda. Clique em "Adicionar concorrente" para começar.
        </div>
    </div>

    <!-- ABA 4: CONFIGURAÇÕES DE CONTEÚDO -->
    <div x-show="aba === 'configuracoes'" x-transition style="display:none;">
        <?php $cfg = $dados['config_conteudo'] ?? ConfiguracaoConteudo::padroes(); ?>
        <form id="form-config-conteudo" class="space-y-6" onsubmit="salvarConfigConteudo(event)">
            <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">

            <!-- Geração e fontes -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-1">Geração de conteúdo</h2>
                <p class="text-sm text-gray-500 mb-4">Define o padrão da empresa para a Máquina de Conteúdo. Cada geração ainda pode sobrescrever estas opções.</p>

                <label class="flex items-start gap-3 p-3 border border-gray-200 rounded-lg mb-3 cursor-pointer hover:bg-gray-50">
                    <input type="checkbox" name="gerar_imagens_padrao" value="1" <?= (int) ($cfg['gerar_imagens_padrao'] ?? 1) === 1 ? 'checked' : '' ?> class="mt-0.5 rounded border-gray-300 text-primary focus:ring-primary/20">
                    <span>
                        <span class="block text-sm font-medium text-gray-800">Gerar imagens automaticamente</span>
                        <span class="block text-xs text-gray-500">Quando desligado, o conteúdo (copy, títulos, legenda, CTA, hashtags) é gerado normalmente, sem consumir créditos de imagem.</span>
                    </span>
                </label>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-2">
                    <label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="permitir_noticias" value="1" <?= (int) ($cfg['permitir_noticias'] ?? 1) === 1 ? 'checked' : '' ?> class="rounded border-gray-300 text-primary focus:ring-primary/20"> Permitir uso de notícias</label>
                    <label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="permitir_concorrencia" value="1" <?= (int) ($cfg['permitir_concorrencia'] ?? 1) === 1 ? 'checked' : '' ?> class="rounded border-gray-300 text-primary focus:ring-primary/20"> Permitir uso de concorrência</label>
                    <label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="permitir_datas_comemorativas" value="1" <?= (int) ($cfg['permitir_datas_comemorativas'] ?? 1) === 1 ? 'checked' : '' ?> class="rounded border-gray-300 text-primary focus:ring-primary/20"> Permitir datas comemorativas</label>
                </div>
            </div>

            <!-- Frequência e formatos -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Frequência e formatos</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Frequência padrão</label>
                        <select name="frequencia_padrao" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                            <?php foreach (['diaria'=>'Diária','3_dias'=>'A cada 3 dias','semanal'=>'Semanal','quinzenal'=>'Quinzenal','mensal'=>'Mensal'] as $v=>$l): ?>
                            <option value="<?= $v ?>" <?= ($cfg['frequencia_padrao'] ?? 'semanal') === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sugestões semanais</label>
                        <input type="number" name="qtd_sugestoes_semanais" min="1" max="30" value="<?= (int) ($cfg['qtd_sugestoes_semanais'] ?? 3) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Idioma</label>
                        <input type="text" name="idioma" value="<?= htmlspecialchars($cfg['idioma'] ?? 'Português') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Redes sociais</label>
                        <div class="flex flex-wrap gap-3">
                            <?php $redes = (array) ($cfg['redes_sociais'] ?? []); foreach (['instagram'=>'Instagram','linkedin'=>'LinkedIn','facebook'=>'Facebook','tiktok'=>'TikTok','youtube'=>'YouTube'] as $v=>$l): ?>
                            <label class="flex items-center gap-1.5 text-sm text-gray-700"><input type="checkbox" name="redes_sociais[]" value="<?= $v ?>" <?= in_array($v, $redes, true) ? 'checked' : '' ?> class="rounded border-gray-300 text-primary focus:ring-primary/20"> <?= $l ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Formatos preferidos</label>
                        <div class="flex flex-wrap gap-3">
                            <?php $formatos = (array) ($cfg['formatos_preferidos'] ?? []); foreach (['carrossel'=>'Carrossel','post'=>'Post','reels'=>'Reels','story'=>'Story'] as $v=>$l): ?>
                            <label class="flex items-center gap-1.5 text-sm text-gray-700"><input type="checkbox" name="formatos_preferidos[]" value="<?= $v ?>" <?= in_array($v, $formatos, true) ? 'checked' : '' ?> class="rounded border-gray-300 text-primary focus:ring-primary/20"> <?= $l ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Região e datas -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Região e datas comemorativas</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">País</label>
                        <input type="text" name="pais" value="<?= htmlspecialchars($cfg['pais'] ?? 'Brasil') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                        <input type="text" name="estado" value="<?= htmlspecialchars($cfg['estado'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cidade</label>
                        <input type="text" name="cidade" value="<?= htmlspecialchars($cfg['cidade'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Antecedência (dias)</label>
                        <input type="number" name="antecedencia_datas_dias" min="0" max="90" value="<?= (int) ($cfg['antecedencia_datas_dias'] ?? 7) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                    </div>
                </div>
            </div>

            <!-- Anti-repetição -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Repetição de temas</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                    <label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="evitar_repeticao_temas" value="1" <?= (int) ($cfg['evitar_repeticao_temas'] ?? 1) === 1 ? 'checked' : '' ?> class="rounded border-gray-300 text-primary focus:ring-primary/20"> Evitar repetição de temas</label>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Período para considerar repetido (dias)</label>
                        <input type="number" name="periodo_repeticao_dias" min="1" max="365" value="<?= (int) ($cfg['periodo_repeticao_dias'] ?? 30) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-5 py-2.5 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-700 transition">Salvar configurações</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Editar informações do conteúdo -->
<div id="modal-config" class="fixed inset-0 z-50 hidden" aria-modal="true" role="dialog">
    <div class="absolute inset-0 bg-black/50" onclick="fecharModalConfig()"></div>
    <div class="relative min-h-full flex items-start justify-center p-4 overflow-y-auto">
        <div class="bg-white rounded-lg shadow-xl border border-gray-200 w-full max-w-2xl my-8">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Editar informações do conteúdo</h3>
                <button type="button" onclick="fecharModalConfig()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Setor/Nicho</label>
                    <input type="text" value="<?= htmlspecialchars($dados['perfil_busca']['setor']) ?>" disabled class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-gray-50 text-gray-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Língua</label>
                    <input type="text" value="<?= htmlspecialchars($dados['perfil_busca']['lingua']) ?>" disabled class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-gray-50 text-gray-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sites de referência</label>
                    <div id="lista-sites-busca" class="space-y-2">
                        <?php foreach ($dados['perfil_busca']['sites'] as $site): ?>
                        <div class="flex items-center gap-2 site-referencia-item">
                            <input type="url" value="<?= htmlspecialchars($site) ?>" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary" placeholder="https://...">
                            <button type="button" onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-600 text-lg">&times;</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" onclick="adicionarCampoSite()" class="text-sm text-primary font-medium hover:underline mt-2">+ Adicionar site</button>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Instruções de priorização (prompt mestre)</label>
                    <p class="text-xs text-gray-400 mb-2">Descreva, em texto livre, que tipo de notícia a IA deve priorizar ou evitar na busca do seu nicho. Ex.: "Priorize lançamentos de produtos, regulamentação e movimentos de concorrentes no setor de energia solar. Evite fofoca, esportes e notícias sem relação com negócios."</p>
                    <textarea id="instrucoes-busca" rows="5" maxlength="2000"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"
                              placeholder="Ex.: Priorize notícias sobre... Evite..."><?= htmlspecialchars($dados['perfil_busca']['instrucoes'] ?? '') ?></textarea>
                </div>
                <div class="md:col-span-2">
                    <span class="text-xs text-gray-400">Última busca: <?= !empty($dados['perfil_busca']['ultimo_update']) ? date('d/m/Y H:i', strtotime($dados['perfil_busca']['ultimo_update'])) : 'Nunca' ?></span>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200">
                <button type="button" onclick="fecharModalConfig()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">Cancelar</button>
                <button type="button" onclick="salvarPerfilBusca()" id="btn-salvar-perfil" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-700">💾 Salvar configurações</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Adicionar/editar concorrente -->
<div id="modal-concorrente" class="fixed inset-0 z-50 hidden" aria-modal="true" role="dialog">
    <div class="absolute inset-0 bg-black/50" onclick="fecharModalConcorrente()"></div>
    <div class="relative min-h-full flex items-start justify-center p-4 overflow-y-auto">
        <div class="bg-white rounded-lg shadow-xl border border-gray-200 w-full max-w-xl my-8">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Adicionar concorrente</h3>
                <button type="button" onclick="fecharModalConcorrente()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>
            <form id="form-concorrente" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4" onsubmit="salvarConcorrente(event)">
                <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome do concorrente *</label>
                    <input type="text" name="nome" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome do perfil (@)</label>
                    <input type="text" name="nome_perfil" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Plataforma</label>
                    <select name="plataforma" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                        <option value="instagram">Instagram</option>
                        <option value="linkedin">LinkedIn</option>
                        <option value="facebook">Facebook</option>
                        <option value="tiktok">TikTok</option>
                        <option value="youtube">YouTube</option>
                        <option value="blog">Blog / Site</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">URL pública *</label>
                    <input type="url" name="url_publica" required placeholder="https://..." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoria / nicho</label>
                    <input type="text" name="categoria" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Frequência de coleta</label>
                    <select name="frequencia_coleta" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                        <option value="manual">Manual</option>
                        <option value="diaria">Diária</option>
                        <option value="3_dias">A cada 3 dias</option>
                        <option value="semanal">Semanal</option>
                        <option value="quinzenal">Quinzenal</option>
                        <option value="mensal">Mensal</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Máx. posts por coleta</label>
                    <input type="number" name="max_posts_por_coleta" min="1" max="50" value="12" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Seguidores (se souber)</label>
                    <input type="number" name="seguidores" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                </div>
                <div class="md:col-span-2">
                    <label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="principal" value="1" class="rounded border-gray-300 text-primary focus:ring-primary/20"> Concorrente principal</label>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                    <textarea name="observacoes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></textarea>
                </div>
                <div class="md:col-span-2 flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                    <button type="button" onclick="fecharModalConcorrente()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-700">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: item manual do calendário -->
<div id="modal-item-calendario" class="fixed inset-0 z-50 hidden" aria-modal="true" role="dialog">
    <div class="absolute inset-0 bg-black/50" onclick="fecharModalItemCalendario()"></div>
    <div class="relative min-h-full flex items-start justify-center p-4 overflow-y-auto">
        <div class="bg-white rounded-lg shadow-xl border border-gray-200 w-full max-w-lg my-8">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Adicionar ao calendário</h3>
                <button type="button" onclick="fecharModalItemCalendario()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>
            <form id="form-item-calendario" class="p-6 space-y-4" onsubmit="salvarItemCalendario(event)">
                <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tema *</label>
                    <input type="text" name="tema" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data de publicação</label>
                        <input type="date" name="data_publicacao_sugerida" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Formato</label>
                        <select name="formato_recomendado" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                            <option value="">—</option>
                            <option value="carrossel">Carrossel</option>
                            <option value="post">Post</option>
                            <option value="reels">Reels</option>
                            <option value="story">Story</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Objetivo</label>
                        <input type="text" name="objetivo" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary" placeholder="educar, vender...">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Responsável</label>
                        <input type="text" name="responsavel" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="gerar_imagem" value="1" checked class="rounded border-gray-300 text-primary focus:ring-primary/20"> Gerar imagem ao criar o conteúdo</label>
                <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                    <button type="button" onclick="fecharModalItemCalendario()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-700">Adicionar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Escapador de HTML compartilhado pelas funções deste bloco (calendário/concorrência).
function esc(s) {
    return (s == null ? '' : String(s)).replace(/[&<>"]/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[ch]));
}

// ===== Brand Book (movido da Máquina de Conteúdo; mesmos endpoints) =====
<?php $marcaBB = $dados['marca_brand_book'] ?? null; if ($marcaBB): ?>
const BRAND_MARCA_ID = <?= (int) $marcaBB['id'] ?>;
document.getElementById('form-branding')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-salvar-branding');
    if (btn) { btn.disabled = true; btn.textContent = 'Salvando...'; }
    try {
        const res = await fetch('<?= APP_URL ?>/maquina-de-conteudo/salvar-branding', { method: 'POST', body: new FormData(this) });
        const data = await res.json();
        if (data.sucesso) {
            if (typeof Toast !== 'undefined') Toast.sucesso('Brand Book salvo!'); else alert('Brand Book salvo!');
        } else {
            alert(data.erro || 'Erro ao salvar.');
        }
    } catch (err) { alert('Erro de conexão.'); }
    if (btn) { btn.disabled = false; btn.textContent = '💾 Salvar Brand Book'; }
});

async function uploadLogo(input) {
    if (!input.files || !input.files[0]) return;
    const fd = new FormData();
    fd.append('csrf_token', '<?= Csrf::token() ?>');
    fd.append('marca_id', BRAND_MARCA_ID);
    fd.append('logo', input.files[0]);
    try {
        const res = await fetch('<?= APP_URL ?>/maquina-de-conteudo/upload-logo', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.sucesso && data.url) {
            const img = document.getElementById('logo-preview');
            const vazio = document.getElementById('logo-vazio');
            if (img) { img.src = data.url + '?t=' + Date.now(); img.classList.remove('hidden'); }
            if (vazio) vazio.classList.add('hidden');
            if (typeof Toast !== 'undefined') Toast.sucesso('Logo enviado!');
        } else {
            alert(data.erro || 'Erro no upload do logo.');
        }
    } catch (e) { alert('Erro de conexão.'); }
    input.value = '';
}

async function uploadFechamento(input) {
    if (!input.files || !input.files[0]) return;
    const fd = new FormData();
    fd.append('csrf_token', '<?= Csrf::token() ?>');
    fd.append('marca_id', BRAND_MARCA_ID);
    fd.append('imagem', input.files[0]);
    try {
        const res = await fetch('<?= APP_URL ?>/maquina-de-conteudo/upload-fechamento', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.sucesso && data.url) {
            const img = document.getElementById('fechamento-preview');
            const vazio = document.getElementById('fechamento-vazio');
            if (img) { img.src = data.url + '?t=' + Date.now(); img.classList.remove('hidden'); }
            if (vazio) vazio.classList.add('hidden');
            if (typeof Toast !== 'undefined') Toast.sucesso('Imagem de fechamento enviada!');
        } else {
            alert(data.erro || 'Erro no upload.');
        }
    } catch (e) { alert('Erro de conexão.'); }
    input.value = '';
}
<?php endif; ?>

// ===== Calendário de Conteúdo =====
function abrirModalItemCalendario() {
    const m = document.getElementById('modal-item-calendario');
    if (m) { m.classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
}
function fecharModalItemCalendario() {
    const m = document.getElementById('modal-item-calendario');
    if (m) { m.classList.add('hidden'); document.body.style.overflow = ''; }
}

const CAL_ORIGEM_LABEL = {
    noticia: '📰 Notícia', data_comemorativa: '📅 Data comemorativa', concorrencia: '🔎 Concorrência',
    conteudo_semanal: '🗓️ Semanal', tema_manual: '✍️ Tema manual', tendencia: '📈 Tendência'
};

async function carregarCalendario() {
    const lista = document.getElementById('calendario-lista');
    const vazio = document.getElementById('calendario-vazio');
    const proximasEl = document.getElementById('calendario-proximas');
    if (!lista) return;
    lista.innerHTML = '<p class="text-sm text-gray-400">Carregando...</p>';
    try {
        const res = await fetch('<?= APP_URL ?>/central-de-conteudo/calendario');
        const data = await res.json();
        if (!data.sucesso) { lista.innerHTML = ''; return; }

        // Próximas datas (chips informativos)
        const pd = data.proximas_datas || [];
        proximasEl.innerHTML = pd.length ? (
            '<div class="bg-white rounded-lg border border-gray-200 p-4">'
            + '<p class="text-sm font-semibold text-gray-700 mb-2">Próximas datas relevantes</p>'
            + '<div class="flex flex-wrap gap-2">'
            + pd.slice(0, 12).map(d => '<span class="inline-flex items-center gap-1 bg-primary/5 text-primary text-xs px-2 py-1 rounded-full">'
                + esc(d.nome) + ' • ' + new Date(d.proxima_ocorrencia + 'T00:00:00').toLocaleDateString('pt-BR')
                + ' <span class="text-gray-400">(' + d.dias_ate + 'd)</span></span>').join('')
            + '</div></div>'
        ) : '';

        const itens = data.itens || [];
        vazio.classList.toggle('hidden', itens.length > 0);
        lista.innerHTML = itens.map(cardCalendario).join('');
    } catch (e) {
        lista.innerHTML = '<p class="text-sm text-red-500">Erro ao carregar o calendário.</p>';
    }
}

function cardCalendario(it) {
    const dataPub = it.data_publicacao_sugerida ? new Date(it.data_publicacao_sugerida + 'T00:00:00').toLocaleDateString('pt-BR') : 'Sem data';
    const origem = CAL_ORIGEM_LABEL[it.origem] || it.origem;
    const genUrl = '<?= APP_URL ?>/maquina-de-conteudo?tema=' + encodeURIComponent(it.tema) + '&calendario_id=' + it.id;
    return '<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 flex items-center justify-between gap-4">'
        + '<div class="min-w-0">'
        + '<div class="flex items-center gap-2 mb-1"><span class="text-xs text-gray-400">' + esc(origem) + '</span>'
        + '<span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">' + esc(it.status) + '</span></div>'
        + '<p class="text-sm font-semibold text-gray-800 truncate">' + esc(it.tema) + '</p>'
        + '<p class="text-xs text-gray-400">📅 ' + dataPub + (it.formato_recomendado ? ' • ' + esc(it.formato_recomendado) : '') + '</p>'
        + '</div>'
        + '<div class="flex items-center gap-2 flex-shrink-0">'
        + '<a href="' + genUrl + '" class="text-xs px-2.5 py-1.5 bg-primary text-white rounded-md hover:bg-primary-700">Gerar conteúdo</a>'
        + '<button onclick="ignorarItemCalendario(' + it.id + ')" class="text-xs px-2.5 py-1.5 text-gray-400 hover:text-red-600">Ignorar</button>'
        + '</div></div>';
}

async function gerarCalendario(btn) {
    if (btn) { btn.disabled = true; btn.textContent = 'Identificando...'; }
    try {
        const fd = new FormData(); fd.append('csrf_token', CSRF);
        const res = await fetch('<?= APP_URL ?>/central-de-conteudo/calendario-gerar', { method: 'POST', body: fd });
        const data = await res.json();
        if (typeof Toast !== 'undefined') (data.sucesso ? Toast.sucesso(data.mensagem) : Toast.erro(data.erro)); else alert(data.mensagem || data.erro);
        carregarCalendario();
    } catch (e) { alert('Erro de conexão.'); }
    finally { if (btn) { btn.disabled = false; btn.textContent = '✨ Identificar datas do meu nicho'; } }
}

async function gerarSemanal(btn) {
    if (btn) { btn.disabled = true; btn.textContent = 'Gerando...'; }
    try {
        const fd = new FormData(); fd.append('csrf_token', CSRF);
        const res = await fetch('<?= APP_URL ?>/central-de-conteudo/calendario-gerar-semanal', { method: 'POST', body: fd });
        const data = await res.json();
        if (typeof Toast !== 'undefined') (data.sucesso ? Toast.sucesso(data.mensagem) : Toast.erro(data.erro)); else alert(data.mensagem || data.erro);
        carregarCalendario();
    } catch (e) { alert('Erro de conexão.'); }
    finally { if (btn) { btn.disabled = false; btn.textContent = '🗓️ Sugestões da semana'; } }
}

async function salvarItemCalendario(event) {
    event.preventDefault();
    const form = document.getElementById('form-item-calendario');
    const res = await fetch('<?= APP_URL ?>/central-de-conteudo/calendario-adicionar', { method: 'POST', body: new FormData(form) });
    const data = await res.json();
    if (data.sucesso) {
        if (typeof Toast !== 'undefined') Toast.sucesso(data.mensagem); else alert(data.mensagem);
        fecharModalItemCalendario(); form.reset(); carregarCalendario();
    } else { alert(data.erro || 'Erro.'); }
}

async function ignorarItemCalendario(id) {
    const fd = new FormData(); fd.append('csrf_token', CSRF); fd.append('id', id);
    const res = await fetch('<?= APP_URL ?>/central-de-conteudo/calendario-ignorar', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.sucesso) carregarCalendario(); else alert(data.erro || 'Erro.');
}

// ===== Scrap da Concorrência =====
const CSRF = '<?= Csrf::token() ?>';

function abrirModalConcorrente() {
    const m = document.getElementById('modal-concorrente');
    if (m) { m.classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
}
function fecharModalConcorrente() {
    const m = document.getElementById('modal-concorrente');
    if (m) { m.classList.add('hidden'); document.body.style.overflow = ''; }
}

async function carregarConcorrentes() {
    const lista = document.getElementById('concorrentes-lista');
    const vazio = document.getElementById('concorrentes-vazio');
    const aviso = document.getElementById('concorrencia-aviso');
    if (!lista) return;
    lista.innerHTML = '<p class="text-sm text-gray-400 col-span-full">Carregando...</p>';
    try {
        const res = await fetch('<?= APP_URL ?>/central-de-conteudo/concorrentes');
        const data = await res.json();
        if (!data.sucesso) { lista.innerHTML = ''; return; }

        if (!data.scrapingbee_ok && aviso) {
            aviso.classList.remove('hidden');
            aviso.textContent = 'Atenção: a integração de coleta ainda não está configurada. Você pode cadastrar concorrentes, mas as coletas só funcionarão após configurá-la em Admin > Configurações.';
        }

        const cs = data.concorrentes || [];
        vazio.classList.toggle('hidden', cs.length > 0);
        lista.innerHTML = cs.map(cardConcorrente).join('');
    } catch (e) {
        lista.innerHTML = '<p class="text-sm text-red-500 col-span-full">Erro ao carregar concorrentes.</p>';
    }
}

function cardConcorrente(c) {
    const eng = c.engajamento_medio == null ? 'n/d' : c.engajamento_medio;
    const ultima = c.ultima_coleta_em ? new Date(c.ultima_coleta_em.replace(' ', 'T')).toLocaleDateString('pt-BR') : 'Nunca';
    const statusBadge = c.status === 'ativo' ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600';
    return '<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">'
        + '<div class="flex items-start justify-between gap-2 mb-2">'
        + '<div class="min-w-0"><p class="text-sm font-semibold text-gray-800 truncate">' + esc(c.nome) + (c.principal ? ' ⭐' : '') + '</p>'
        + '<p class="text-xs text-gray-400 truncate">' + esc(c.plataforma) + (c.nome_perfil ? ' • ' + esc(c.nome_perfil) : '') + '</p></div>'
        + '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold ' + statusBadge + '">' + esc(c.status) + '</span></div>'
        + '<div class="grid grid-cols-3 gap-2 text-center my-3">'
        + '<div><p class="text-lg font-bold text-gray-800">' + c.posts + '</p><p class="text-[10px] text-gray-400">posts</p></div>'
        + '<div><p class="text-lg font-bold text-gray-800">' + eng + '</p><p class="text-[10px] text-gray-400">eng. médio</p></div>'
        + '<div><p class="text-xs font-medium text-gray-600">' + ultima + '</p><p class="text-[10px] text-gray-400">última coleta</p></div>'
        + '</div>'
        + '<div class="flex flex-wrap items-center gap-2 pt-3 border-t border-gray-100">'
        + '<button onclick="coletarConcorrente(' + c.id + ', this)" class="text-xs px-2.5 py-1.5 bg-primary text-white rounded-md hover:bg-primary-700">Analisar agora</button>'
        + '<a href="<?= APP_URL ?>/central-de-conteudo/concorrente?id=' + c.id + '" class="text-xs px-2.5 py-1.5 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Ver análise</a>'
        + '<button onclick="pausarConcorrente(' + c.id + ')" class="text-xs px-2.5 py-1.5 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">' + (c.status === 'ativo' ? 'Pausar' : 'Ativar') + '</button>'
        + '<button onclick="excluirConcorrente(' + c.id + ')" class="text-xs px-2.5 py-1.5 text-red-500 hover:text-red-700 ml-auto">Excluir</button>'
        + '</div></div>';
}

async function salvarConcorrente(event) {
    event.preventDefault();
    const form = document.getElementById('form-concorrente');
    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true; const orig = btn.textContent; btn.textContent = 'Salvando...';
    try {
        const res = await fetch('<?= APP_URL ?>/central-de-conteudo/concorrente-salvar', { method: 'POST', body: new FormData(form) });
        const data = await res.json();
        if (data.sucesso) {
            if (typeof Toast !== 'undefined') Toast.sucesso(data.mensagem); else alert(data.mensagem);
            fecharModalConcorrente(); form.reset(); carregarConcorrentes();
        } else {
            if (typeof Toast !== 'undefined') Toast.erro(data.erro); else alert(data.erro);
        }
    } catch (e) { alert('Erro de conexão.'); }
    finally { btn.disabled = false; btn.textContent = orig; }
}

async function coletarConcorrente(id, btn) {
    if (btn) { btn.disabled = true; btn.textContent = 'Coletando...'; }
    try {
        const fd = new FormData(); fd.append('csrf_token', CSRF); fd.append('id', id);
        const res = await fetch('<?= APP_URL ?>/central-de-conteudo/concorrente-coletar', { method: 'POST', body: fd });
        const data = await res.json();
        if (typeof Toast !== 'undefined') (data.sucesso ? Toast.sucesso(data.mensagem) : Toast.erro(data.erro)); else alert(data.mensagem || data.erro);
        carregarConcorrentes();
    } catch (e) { alert('Erro de conexão.'); }
    finally { if (btn) { btn.disabled = false; btn.textContent = 'Analisar agora'; } }
}

async function pausarConcorrente(id) {
    const fd = new FormData(); fd.append('csrf_token', CSRF); fd.append('id', id);
    const res = await fetch('<?= APP_URL ?>/central-de-conteudo/concorrente-pausar', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.sucesso) carregarConcorrentes(); else alert(data.erro || 'Erro.');
}

async function excluirConcorrente(id) {
    if (!confirm('Excluir este concorrente e todos os dados coletados?')) return;
    const fd = new FormData(); fd.append('csrf_token', CSRF); fd.append('id', id);
    const res = await fetch('<?= APP_URL ?>/central-de-conteudo/concorrente-excluir', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.sucesso) { if (typeof Toast !== 'undefined') Toast.sucesso(data.mensagem); carregarConcorrentes(); } else alert(data.erro || 'Erro.');
}

// ===== Configurações de Conteúdo =====
async function salvarConfigConteudo(event) {
    event.preventDefault();
    const form = document.getElementById('form-config-conteudo');
    const btn = form.querySelector('button[type="submit"]');
    const original = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Salvando...';

    try {
        const res = await fetch('<?= APP_URL ?>/central-de-conteudo/config-salvar', {
            method: 'POST',
            body: new FormData(form)
        });
        const data = await res.json();
        if (data.sucesso) {
            if (typeof Toast !== 'undefined') Toast.sucesso(data.mensagem || 'Configurações salvas!'); else alert(data.mensagem || 'Configurações salvas!');
        } else {
            const msg = data.erro || 'Erro ao salvar configurações.';
            if (typeof Toast !== 'undefined') Toast.erro(msg); else alert(msg);
        }
    } catch (e) {
        alert('Erro de conexão ao salvar as configurações.');
    } finally {
        btn.disabled = false;
        btn.textContent = original;
    }
}

// ===== Sites de referência =====
function adicionarCampoSite() {
    const div = document.createElement('div');
    div.className = 'flex items-center gap-2 site-referencia-item';
    div.innerHTML = '<input type="url" placeholder="https://..." class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">'
        + '<button type="button" onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-600 text-lg">&times;</button>';
    document.getElementById('lista-sites-busca').appendChild(div);
}

async function salvarPerfilBusca() {
    const btn = document.getElementById('btn-salvar-perfil');
    const inputs = document.querySelectorAll('#lista-sites-busca input');
    const sites = Array.from(inputs).map(i => i.value.trim()).filter(v => v !== '');

    btn.disabled = true;
    const original = btn.textContent;
    btn.textContent = 'Salvando...';

    try {
        const formData = new FormData();
        formData.append('csrf_token', '<?= Csrf::token() ?>');
        sites.forEach(s => formData.append('sites[]', s));
        const instrucoesEl = document.getElementById('instrucoes-busca');
        formData.append('instrucoes', instrucoesEl ? instrucoesEl.value : '');

        const res = await fetch('<?= APP_URL ?>/central-de-conteudo/perfil-busca', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.sucesso) {
            if (typeof Toast !== 'undefined') Toast.sucesso(data.mensagem); else alert(data.mensagem);
            fecharModalConfig();
        } else {
            const msg = data.erro || 'Erro ao salvar sites.';
            if (typeof Toast !== 'undefined') Toast.erro(msg); else alert(msg);
        }
    } catch (e) {
        alert('Erro de conexão ao salvar os sites.');
    } finally {
        btn.disabled = false;
        btn.textContent = original;
    }
}

// ===== Modal de configuração =====
function abrirModalConfig() {
    const m = document.getElementById('modal-config');
    if (m) { m.classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
}
function fecharModalConfig() {
    const m = document.getElementById('modal-config');
    if (m) { m.classList.add('hidden'); document.body.style.overflow = ''; }
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') fecharModalConfig();
});

// ===== Biblioteca (upload de PDFs) =====
function renderizarBiblioteca(documentos) {
    const lista = document.getElementById('biblioteca-lista');
    const vazia = document.getElementById('biblioteca-vazia');
    if (!lista || !vazia) return;

    if (!documentos || !documentos.length) {
        lista.innerHTML = '';
        vazia.classList.remove('hidden');
        return;
    }
    vazia.classList.add('hidden');

    const esc = (s) => String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    lista.innerHTML = documentos.map(d => {
        const data = d.data ? new Date(d.data).toLocaleDateString('pt-BR') : '';
        return '<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 flex items-center justify-between gap-4" data-doc-id="' + d.id + '">'
            + '<div class="flex items-center gap-3 min-w-0">'
            + '<span class="text-2xl">📄</span>'
            + '<div class="min-w-0">'
            + '<p class="text-sm font-medium text-gray-800 truncate">' + esc(d.nome) + '</p>'
            + '<p class="text-xs text-gray-400">' + esc(d.tamanho) + (data ? ' • ' + data : '') + '</p>'
            + '</div></div>'
            + '<button type="button" onclick="excluirDocumentoBiblioteca(' + d.id + ', this)" class="text-gray-400 hover:text-red-600 text-sm flex-shrink-0" title="Excluir">🗑️</button>'
            + '</div>';
    }).join('');
}

async function enviarArquivosBiblioteca(files) {
    if (!files || !files.length) return;
    const status = document.getElementById('biblioteca-upload-status');
    if (status) { status.classList.remove('hidden'); status.textContent = 'Enviando ' + files.length + ' arquivo(s)...'; }

    const formData = new FormData();
    formData.append('csrf_token', '<?= Csrf::token() ?>');
    for (let i = 0; i < files.length; i++) {
        formData.append('documentos[]', files[i]);
    }

    try {
        const res = await fetch('<?= APP_URL ?>/central-de-conteudo/biblioteca-upload', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.sucesso || Array.isArray(data.documentos)) {
            renderizarBiblioteca(data.documentos || []);
            const msg = data.mensagem || 'Upload concluído!';
            if (typeof Toast !== 'undefined') Toast.sucesso(msg); else alert(msg);
        } else {
            const msg = data.erro || 'Erro no upload.';
            if (typeof Toast !== 'undefined') Toast.erro(msg); else alert(msg);
        }
    } catch (e) {
        alert('Erro de conexão ao enviar arquivos.');
    } finally {
        if (status) status.classList.add('hidden');
        const input = document.getElementById('biblioteca-input');
        if (input) input.value = '';
    }
}

async function excluirDocumentoBiblioteca(id, btn) {
    if (!confirm('Excluir este documento da biblioteca?')) return;
    if (btn) btn.disabled = true;

    const formData = new FormData();
    formData.append('csrf_token', '<?= Csrf::token() ?>');
    formData.append('documento_id', id);

    try {
        const res = await fetch('<?= APP_URL ?>/central-de-conteudo/biblioteca-excluir', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.sucesso) {
            const card = document.querySelector('[data-doc-id="' + id + '"]');
            if (card) card.remove();
            const lista = document.getElementById('biblioteca-lista');
            if (lista && !lista.querySelector('[data-doc-id]')) renderizarBiblioteca([]);
            if (typeof Toast !== 'undefined') Toast.sucesso(data.mensagem); else alert(data.mensagem);
        } else {
            if (btn) btn.disabled = false;
            const msg = data.erro || 'Erro ao excluir.';
            if (typeof Toast !== 'undefined') Toast.erro(msg); else alert(msg);
        }
    } catch (e) {
        if (btn) btn.disabled = false;
        alert('Erro de conexão ao excluir documento.');
    }
}

// ===== Buscar notícias agora (enfileira + acompanha via polling, sem timeout do proxy) =====
async function buscarAgora() {
    const btn = document.querySelector('[onclick="buscarAgora()"]');
    const originalText = btn ? btn.textContent : '';
    if (btn) { btn.disabled = true; btn.textContent = '🔍 Buscando...'; }

    const formData = new FormData();
    formData.append('csrf_token', '<?= Csrf::token() ?>');

    try {
        const res = await fetch('<?= APP_URL ?>/central-de-conteudo/buscar-agora', { method: 'POST', body: formData });
        const data = await res.json();

        if (!data.sucesso) {
            const msg = data.erro || 'Erro ao buscar notícias.';
            if (typeof Toast !== 'undefined') Toast.erro(msg); else alert(msg);
            return;
        }

        await acompanharBuscaNoticias(data.fila_id, btn);

    } catch (e) {
        alert('Erro de conexão ao buscar notícias.');
    } finally {
        if (btn) { btn.disabled = false; btn.textContent = originalText; }
    }
}

// Faz polling do status da busca enfileirada e, ao concluir, recarrega o feed.
async function acompanharBuscaNoticias(filaId, btn) {
    const esperar = (ms) => new Promise(resolve => setTimeout(resolve, ms));
    const maxTentativas = 60; // ~2min (2s entre tentativas)

    for (let t = 0; t < maxTentativas; t++) {
        let status;
        try {
            const res = await fetch('<?= APP_URL ?>/noticias/status-fila-busca?fila_id=' + filaId + '&_=' + Date.now());
            status = await res.json();
        } catch (e) {
            await esperar(2000);
            continue;
        }

        if (!status.sucesso) break;
        if (btn && status.mensagem) btn.textContent = '🔍 ' + status.mensagem;

        // Fallback: se não há cron/exec disponível no servidor, processa 1 passo via HTTP.
        try {
            await fetch('<?= APP_URL ?>/noticias/processar-fila-busca?_=' + Date.now());
        } catch (e) { /* best-effort */ }

        if (status.concluido) {
            if (typeof Toast !== 'undefined') Toast.sucesso(status.mensagem); else alert(status.mensagem);
            await atualizarFeedComRecentes();
            return;
        }
        if (status.erro) {
            const msg = status.mensagem || 'Erro ao buscar notícias.';
            if (typeof Toast !== 'undefined') Toast.erro(msg); else alert(msg);
            return;
        }

        await esperar(2000);
    }

    // Tempo esgotado no acompanhamento: recarrega mesmo assim (a busca continua em background).
    await atualizarFeedComRecentes();
}

async function atualizarFeedComRecentes() {
    try {
        const res = await fetch('<?= APP_URL ?>/central-de-conteudo/noticias-recentes?_=' + Date.now());
        const data = await res.json();
        if (data.sucesso) {
            renderizarFeedNoticias(data.noticias);
            if (data.paginacao) renderizarPaginacao(data.paginacao);
            // Preenche as capas (og:image) que ainda estiverem faltando e recarrega.
            await preencherImagensFaltantes();
        }
    } catch (e) { /* silencioso */ }
}

// Extrai a og:image das páginas das notícias que ainda estão sem capa e,
// se preencher alguma, recarrega o feed para exibir as imagens.
async function preencherImagensFaltantes() {
    try {
        const res = await fetch('<?= APP_URL ?>/noticias/preencher-imagens?_=' + Date.now());
        const data = await res.json();
        if (data.sucesso && data.atualizadas > 0) {
            const res2 = await fetch('<?= APP_URL ?>/central-de-conteudo/noticias-recentes?_=' + Date.now());
            const data2 = await res2.json();
            if (data2.sucesso) {
                renderizarFeedNoticias(data2.noticias);
                if (data2.paginacao) renderizarPaginacao(data2.paginacao);
            }
        }
    } catch (e) { /* silencioso */ }
}

// Monta os cards de notícia no feed, no mesmo formato renderizado pelo PHP.
function renderizarFeedNoticias(noticias) {
    const feed = document.getElementById('feed-noticias');
    if (!feed) return;

    if (!noticias.length) {
        feed.innerHTML = '<div class="col-span-full bg-white rounded-lg border border-gray-200 p-8 text-center text-gray-500 text-sm">Nenhuma notícia encontrada.</div>';
        return;
    }

    const esc = (s) => String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    const catCores = {
        'Mercado': 'bg-blue-100 text-blue-700',
        'Tecnologia': 'bg-purple-100 text-purple-700',
        'Regulamentação': 'bg-red-100 text-red-700',
        'Tendência': 'bg-green-100 text-green-700',
        'Negócio': 'bg-orange-100 text-orange-700',
    };

    feed.innerHTML = noticias.map(n => {
        const catBadge = catCores[n.categoria] || 'bg-gray-100 text-gray-700';
        const relBadge = n.relevancia === 'alta' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600';
        const dataFmt = n.data ? new Date(n.data).toLocaleDateString('pt-BR') : '';
        const urlAnalise = '<?= APP_URL ?>/central-de-conteudo/noticia?id=' + n.id;
        const imgTag = n.imagem_url
            ? '<img src="' + esc(n.imagem_url) + '" alt="" loading="lazy" onerror="this.replaceWith(Object.assign(document.createElement(\'div\'),{className:\'w-full h-40 bg-gray-100 flex items-center justify-center text-3xl\',textContent:\'📰\'}))" class="w-full h-40 object-cover bg-gray-100">'
            : '<div class="w-full h-40 bg-gray-100 flex items-center justify-center text-3xl">📰</div>';
        const linkExterno = n.url ? '<a href="' + esc(n.url) + '" target="_blank" rel="noopener noreferrer" class="text-xs text-gray-400 hover:text-gray-600" title="Abrir notícia original">🔗</a>' : '';

        const btnExcluir = '<button type="button" onclick="excluirNoticia(' + n.id + ', this)" class="text-xs text-gray-400 hover:text-red-600" title="Excluir notícia">🗑️</button>';

        return '<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition flex flex-col" data-noticia-id="' + n.id + '">'
            + '<a href="' + urlAnalise + '" class="block">' + imgTag + '</a>'
            + '<div class="p-4 flex flex-col flex-1">'
            + '<div class="flex items-center gap-2 mb-2 flex-wrap">'
            + '<span class="text-xs text-gray-400 font-medium">' + esc(n.fonte) + '</span>'
            + '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold ' + catBadge + '">' + esc(n.categoria) + '</span>'
            + '<span class="text-xs text-gray-400">' + dataFmt + '</span>'
            + '</div>'
            + '<a href="' + urlAnalise + '" class="block"><h3 class="text-sm font-semibold text-gray-800 mb-1 leading-snug hover:text-primary">' + esc(n.titulo) + '</h3></a>'
            + '<p class="text-xs text-gray-500 flex-1">' + esc(n.resumo) + '</p>'
            + '<div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100">'
            + '<span class="text-xs font-bold px-2 py-0.5 rounded ' + relBadge + '">' + esc((n.relevancia || '').charAt(0).toUpperCase() + (n.relevancia || '').slice(1)) + '</span>'
            + '<div class="flex items-center gap-3">'
            + '<a href="' + urlAnalise + '" class="text-xs text-primary font-medium hover:underline">Ver análise →</a>'
            + linkExterno
            + btnExcluir
            + '</div></div></div></div>';
    }).join('');
}

// ===== Excluir notícia(s) =====
async function excluirNoticia(id, btn) {
    if (!confirm('Excluir esta notícia? Esta ação não pode ser desfeita.')) return;

    if (btn) btn.disabled = true;
    try {
        const formData = new FormData();
        formData.append('csrf_token', '<?= Csrf::token() ?>');
        formData.append('noticia_id', id);

        const res = await fetch('<?= APP_URL ?>/central-de-conteudo/excluir-noticia', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.sucesso) {
            const card = document.querySelector('[data-noticia-id="' + id + '"]');
            if (card) card.remove();
            if (typeof Toast !== 'undefined') Toast.sucesso(data.mensagem); else alert(data.mensagem);
            // Se o feed ficou vazio, recarrega para exibir o estado "sem notícias".
            const feed = document.getElementById('feed-noticias');
            if (feed && !feed.querySelector('[data-noticia-id]')) atualizarFeedComRecentes();
        } else {
            if (btn) btn.disabled = false;
            const msg = data.erro || 'Erro ao excluir notícia.';
            if (typeof Toast !== 'undefined') Toast.erro(msg); else alert(msg);
        }
    } catch (e) {
        if (btn) btn.disabled = false;
        alert('Erro de conexão ao excluir notícia.');
    }
}

async function limparNoticias() {
    if (!confirm('Excluir TODAS as notícias desta empresa? Esta ação não pode ser desfeita.')) return;

    try {
        const formData = new FormData();
        formData.append('csrf_token', '<?= Csrf::token() ?>');

        const res = await fetch('<?= APP_URL ?>/central-de-conteudo/limpar-noticias', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.sucesso) {
            if (typeof Toast !== 'undefined') Toast.sucesso(data.mensagem); else alert(data.mensagem);
            renderizarFeedNoticias([]);
            renderizarPaginacao({ pagina_atual: 1, total_paginas: 1, total_itens: 0 });
        } else {
            const msg = data.erro || 'Erro ao limpar notícias.';
            if (typeof Toast !== 'undefined') Toast.erro(msg); else alert(msg);
        }
    } catch (e) {
        alert('Erro de conexão ao limpar notícias.');
    }
}

// ===== Paginação do feed de notícias (mais nova = página 1) =====
function renderizarPaginacao(paginacao) {
    const wrap = document.getElementById('paginacao-noticias');
    const controles = document.getElementById('paginacao-controles');
    const totalTxt = document.getElementById('paginacao-total');
    if (!wrap || !controles) return;

    const { pagina_atual, total_paginas, total_itens } = paginacao;
    wrap.dataset.paginaAtual = pagina_atual;
    wrap.dataset.totalPaginas = total_paginas;
    if (totalTxt) totalTxt.textContent = total_itens + ' notícia(s) no total';

    if (total_paginas <= 1) { controles.innerHTML = ''; return; }

    const btn = (label, pagina, ativo, desabilitado) => {
        const base = 'px-3 py-1.5 rounded-lg text-xs font-medium transition';
        if (desabilitado) return '<span class="' + base + ' text-gray-300 cursor-not-allowed">' + label + '</span>';
        if (ativo) return '<span class="' + base + ' bg-primary text-white">' + label + '</span>';
        return '<button type="button" onclick="irParaPaginaNoticias(' + pagina + ')" class="' + base + ' border border-gray-300 text-gray-600 hover:bg-gray-50">' + label + '</button>';
    };

    // Janela de páginas ao redor da atual (máx. 5 números visíveis).
    let inicio = Math.max(1, pagina_atual - 2);
    let fim = Math.min(total_paginas, inicio + 4);
    inicio = Math.max(1, fim - 4);

    let html = '';
    html += btn('‹ Mais recentes', 1, false, pagina_atual === 1);
    html += btn('‹', pagina_atual - 1, false, pagina_atual === 1);
    for (let p = inicio; p <= fim; p++) {
        html += btn(String(p), p, p === pagina_atual, false);
    }
    html += btn('›', pagina_atual + 1, false, pagina_atual === total_paginas);
    html += btn('Mais antigas ›', total_paginas, false, pagina_atual === total_paginas);

    controles.innerHTML = html;
}

async function irParaPaginaNoticias(pagina) {
    const wrap = document.getElementById('paginacao-noticias');
    const totalPaginas = parseInt(wrap?.dataset.totalPaginas || '1', 10);
    pagina = Math.max(1, Math.min(pagina, totalPaginas));

    const feed = document.getElementById('feed-noticias');
    if (feed) feed.style.opacity = '0.5';

    try {
        const res = await fetch('<?= APP_URL ?>/central-de-conteudo/noticias-pagina?pagina=' + pagina);
        const data = await res.json();
        if (data.sucesso) {
            renderizarFeedNoticias(data.noticias);
            renderizarPaginacao(data.paginacao);
            document.getElementById('feed-noticias')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else {
            alert(data.erro || 'Erro ao carregar página.');
        }
    } catch (e) {
        alert('Erro de conexão ao trocar de página.');
    } finally {
        if (feed) feed.style.opacity = '1';
    }
}

// Renderiza a paginação inicial (dados já vieram do PHP no carregamento da página).
document.addEventListener('DOMContentLoaded', function() {
    const wrap = document.getElementById('paginacao-noticias');
    if (wrap) {
        renderizarPaginacao({
            pagina_atual: parseInt(wrap.dataset.paginaAtual || '1', 10),
            total_paginas: parseInt(wrap.dataset.totalPaginas || '1', 10),
            total_itens: parseInt(document.getElementById('paginacao-total')?.textContent || '0', 10),
        });
    }
    // Preenche capas faltantes das notícias já existentes (em background).
    if (document.querySelector('#feed-noticias')) {
        preencherImagensFaltantes();
    }
    // Renderiza a biblioteca com os documentos já carregados do PHP.
    renderizarBiblioteca(<?= json_encode($dados['biblioteca'] ?? [], JSON_UNESCAPED_UNICODE) ?>);
});
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
