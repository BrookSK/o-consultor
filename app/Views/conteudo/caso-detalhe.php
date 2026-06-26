<?php $tituloPagina = 'Caso Real'; ?>
<?php ob_start(); ?>
<?php $caso = $dados['caso']; ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li><a href="<?= APP_URL ?>/central-de-conteudo" class="hover:text-primary">Central de Conteúdo</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Caso Real</li>
    </ol>
</nav>

<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600"><?= htmlspecialchars($caso['setor']) ?></span>
        <h1 class="text-xl font-bold text-gray-800 mt-2"><?= htmlspecialchars($caso['titulo']) ?></h1>
    </div>

    <!-- 6 Blocos -->
    <?php
    $blocos = [
        ['titulo' => 'Problema', 'conteudo' => $caso['problema'], 'cor' => 'red'],
        ['titulo' => 'Diagnóstico', 'conteudo' => $caso['diagnostico'], 'cor' => 'orange'],
        ['titulo' => 'Processo', 'conteudo' => $caso['processo'], 'cor' => 'blue'],
        ['titulo' => 'Implementação', 'conteudo' => $caso['implementacao'], 'cor' => 'purple'],
        ['titulo' => 'Resultado', 'conteudo' => $caso['resultado'], 'cor' => 'green'],
        ['titulo' => 'Lições Aprendidas', 'conteudo' => $caso['licoes'], 'cor' => 'gray'],
    ];
    foreach ($blocos as $i => $bloco):
        $bgClasses = match($bloco['cor']) {
            'red' => 'border-l-red-500 bg-red-50/30',
            'orange' => 'border-l-orange-500 bg-orange-50/30',
            'blue' => 'border-l-blue-500 bg-blue-50/30',
            'purple' => 'border-l-purple-500 bg-purple-50/30',
            'green' => 'border-l-green-500 bg-green-50/30',
            default => 'border-l-gray-400 bg-gray-50/30',
        };
    ?>
    <div class="border-l-4 rounded-r-lg p-5 mb-4 <?= $bgClasses ?>">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-2"><?= $i + 1 ?>. <?= $bloco['titulo'] ?></h3>
        <p class="text-sm text-gray-700 leading-relaxed"><?= nl2br(htmlspecialchars($bloco['conteudo'])) ?></p>
    </div>
    <?php endforeach; ?>

    <a href="<?= APP_URL ?>/central-de-conteudo" class="inline-block mt-4 px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">← Voltar</a>
</div>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
