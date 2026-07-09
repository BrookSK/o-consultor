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
<div x-data="{ aba: 'noticias' }">
    <div class="border-b border-gray-200 mb-6">
        <nav class="flex gap-0 overflow-x-auto">
            <button @click="aba = 'noticias'" :class="aba === 'noticias' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500'" class="px-5 py-3 text-sm whitespace-nowrap transition">📰 Notícias e Atualidades</button>
            <button @click="aba = 'academy'" :class="aba === 'academy' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500'" class="px-5 py-3 text-sm whitespace-nowrap transition">🎓 Academy</button>
            <button @click="aba = 'biblioteca'" :class="aba === 'biblioteca' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500'" class="px-5 py-3 text-sm whitespace-nowrap transition">📚 Biblioteca</button>
        </nav>
    </div>

    <!-- ABA 1: NOTÍCIAS -->
    <div x-show="aba === 'noticias'" x-transition>
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

<script>
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
