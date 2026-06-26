<?php $tituloPagina = 'Parceiros'; ?>
<?php ob_start(); ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Parceiros</li>
    </ol>
</nav>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Rede de Parceiros</h1>
        <p class="text-gray-500 mt-1">Parceiros homologados da holding.</p>
    </div>
    <?php if (Auth::isAdmin()): ?>
    <a href="<?= APP_URL ?>/parceiros/admin" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">⚙️ Gestão</a>
    <?php endif; ?>
</div>

<!-- Filtros -->
<div class="flex flex-wrap gap-2 mb-6">
    <select class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs outline-none"><option>Todas categorias</option><option>Tecnologia</option><option>Marketing</option><option>Jurídico</option><option>Finanças</option><option>RH</option><option>Logística</option></select>
    <select class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs outline-none"><option>Todos status</option><option>Homologado</option><option>Em avaliação</option><option>Suspenso</option></select>
    <select class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs outline-none"><option>Avaliação</option><option>4+ estrelas</option><option>3+ estrelas</option></select>
    <input type="text" placeholder="Buscar..." class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs outline-none w-40">
</div>

<!-- Grid de Parceiros -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
<?php foreach ($dados['parceiros'] as $p):
    $statusCfg = match($p['status']) {
        'homologado' => ['badge' => 'bg-green-100 text-green-700', 'label' => '✓ Homologado'],
        'em_avaliacao' => ['badge' => 'bg-yellow-100 text-yellow-700', 'label' => '◎ Em avaliação'],
        'suspenso' => ['badge' => 'bg-red-100 text-red-700', 'label' => '✗ Suspenso'],
        default => ['badge' => 'bg-gray-100 text-gray-600', 'label' => '—'],
    };
    $catCor = match($p['categoria']) {
        'Tecnologia' => 'bg-blue-100 text-blue-700',
        'Marketing' => 'bg-purple-100 text-purple-700',
        'Jurídico' => 'bg-red-100 text-red-700',
        'Finanças' => 'bg-green-100 text-green-700',
        'RH' => 'bg-orange-100 text-orange-700',
        'Logística' => 'bg-indigo-100 text-indigo-700',
        default => 'bg-gray-100 text-gray-700',
    };
?>
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition">
    <div class="flex items-start justify-between mb-3">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-full bg-primary/10 flex items-center justify-center text-lg font-bold text-primary"><?= strtoupper(substr($p['nome'], 0, 1)) ?></div>
            <div>
                <h3 class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($p['nome']) ?></h3>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-medium <?= $catCor ?>"><?= htmlspecialchars($p['categoria']) ?></span>
            </div>
        </div>
        <span class="px-2 py-0.5 rounded-full text-[10px] font-medium <?= $statusCfg['badge'] ?>"><?= $statusCfg['label'] ?></span>
    </div>
    <div class="flex flex-wrap gap-1 mb-3">
        <?php foreach ($p['especialidades'] as $esp): ?>
        <span class="px-2 py-0.5 bg-gray-100 rounded text-[10px] text-gray-600"><?= htmlspecialchars($esp) ?></span>
        <?php endforeach; ?>
    </div>
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-1">
            <?php for ($s = 1; $s <= 5; $s++): ?>
            <svg class="w-3.5 h-3.5 <?= $s <= round($p['avaliacao']) ? 'text-yellow-400' : 'text-gray-200' ?>" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            <?php endfor; ?>
            <span class="text-xs text-gray-500 ml-1"><?= $p['avaliacao'] ?></span>
        </div>
        <a href="<?= APP_URL ?>/parceiros/perfil?id=<?= $p['id'] ?>" class="text-xs text-primary font-medium hover:underline">Ver Perfil →</a>
    </div>
</div>
<?php endforeach; ?>
</div>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
