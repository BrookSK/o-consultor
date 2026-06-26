<?php $tituloPagina = 'Notícia'; ?>
<?php ob_start(); ?>
<?php $noticia = $dados['noticia']; ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li><a href="<?= APP_URL ?>/central-de-conteudo" class="hover:text-primary">Central de Conteúdo</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Notícia</li>
    </ol>
</nav>

<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center gap-2 mb-3">
            <span class="text-xs text-gray-400"><?= htmlspecialchars($noticia['fonte']) ?></span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700"><?= htmlspecialchars($noticia['categoria']) ?></span>
            <span class="text-xs text-gray-400"><?= date('d/m/Y', strtotime($noticia['data'])) ?></span>
            <span class="px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-700">Relevância: <?= ucfirst($noticia['relevancia']) ?></span>
        </div>
        <h1 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($noticia['titulo']) ?></h1>
    </div>

    <!-- Bloco 1: A Notícia -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-4">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-3 flex items-center gap-2">
            <span class="w-6 h-6 rounded bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-600">1</span> A Notícia
        </h3>
        <p class="text-sm text-gray-700 leading-relaxed"><?= nl2br(htmlspecialchars($noticia['bloco_noticia'])) ?></p>
    </div>

    <!-- Bloco 2: O Que Significa -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-4">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-3 flex items-center gap-2">
            <span class="w-6 h-6 rounded bg-yellow-100 flex items-center justify-center text-xs font-bold text-yellow-700">2</span> O Que Significa
        </h3>
        <p class="text-sm text-gray-700 leading-relaxed"><?= nl2br(htmlspecialchars($noticia['bloco_significa'])) ?></p>
    </div>

    <!-- Bloco 3: O Que Fazer -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-4">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-3 flex items-center gap-2">
            <span class="w-6 h-6 rounded bg-green-100 flex items-center justify-center text-xs font-bold text-green-700">3</span> O Que Fazer
        </h3>
        <div class="text-sm text-gray-700 leading-relaxed whitespace-pre-line"><?= htmlspecialchars($noticia['bloco_fazer']) ?></div>
    </div>

    <!-- Bloco 4: Pergunta Estratégica -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-4">
        <h3 class="text-sm font-bold text-blue-800 uppercase tracking-wide mb-3 flex items-center gap-2">
            <span class="w-6 h-6 rounded bg-blue-200 flex items-center justify-center text-xs font-bold text-blue-700">4</span> Pergunta Estratégica
        </h3>
        <p class="text-sm text-blue-800 font-medium italic">"<?= htmlspecialchars($noticia['bloco_pergunta']) ?>"</p>
    </div>

    <!-- Bloco 5: Conexão com o Consultor -->
    <div class="bg-primary/5 border border-primary/20 rounded-lg p-6 mb-6">
        <h3 class="text-sm font-bold text-primary uppercase tracking-wide mb-3 flex items-center gap-2">
            <span class="w-6 h-6 rounded bg-primary/20 flex items-center justify-center text-xs font-bold text-primary">5</span> Conexão com O Consultor
        </h3>
        <p class="text-sm text-gray-700 leading-relaxed"><?= htmlspecialchars($noticia['bloco_conexao']) ?></p>
    </div>

    <!-- Ações -->
    <div class="flex flex-wrap gap-3">
        <button class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-700 transition">💾 Salvar notícia</button>
        <a href="<?= APP_URL ?>/maquina-de-conteudo" class="px-4 py-2 bg-accent text-white rounded-lg text-sm font-medium hover:bg-orange-700 transition">✨ Criar conteúdo a partir disso</a>
        <a href="<?= APP_URL ?>/central-de-conteudo" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">← Voltar</a>
    </div>
</div>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
