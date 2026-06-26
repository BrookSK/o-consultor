<?php $tituloPagina = 'Máquina de Conteúdo'; ?>
<?php ob_start(); ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Máquina de Conteúdo</li>
    </ol>
</nav>

<div class="flex items-center justify-between mb-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">🤖 Máquina de Conteúdo</h1>
        <p class="text-gray-500 mt-1">Geração de conteúdo com IA para suas marcas.</p>
    </div>
    <?php if (Auth::temAlgumPerfil([Auth::ADMIN_HOLDING, Auth::CONSULTOR_INTERNO])): ?>
    <a href="<?= APP_URL ?>/maquina-de-conteudo/nova-marca" class="bg-accent text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-orange-700 transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Cadastrar Nova Marca
    </a>
    <?php endif; ?>
</div>

<!-- Grid de Marcas -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($dados['marcas'] as $marca): ?>
    <a href="<?= APP_URL ?>/maquina-de-conteudo/marca?id=<?= $marca['id'] ?>" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition block">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 rounded-full bg-primary text-white flex items-center justify-center text-lg font-bold"><?= $marca['avatar'] ?></div>
            <div>
                <h3 class="font-semibold text-gray-800"><?= htmlspecialchars($marca['nome']) ?></h3>
                <p class="text-xs text-gray-400"><?= htmlspecialchars($marca['nicho']) ?></p>
            </div>
        </div>
        <div class="flex items-center justify-between">
            <span class="text-xs text-gray-400"><?= $marca['ultimo'] ? 'Último: ' . date('d/m/Y', strtotime($marca['ultimo'])) : 'Nenhum conteúdo' ?></span>
            <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $marca['status'] === 'ativo' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?>">
                <?= $marca['status'] === 'ativo' ? '✓ Brand Book criado' : '○ Pendente' ?>
            </span>
        </div>
    </a>
    <?php endforeach; ?>
</div>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
