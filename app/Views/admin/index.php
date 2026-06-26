<?php $tituloPagina = 'Administração'; ?>
<?php ob_start(); ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Administração</li>
    </ol>
</nav>

<h1 class="text-2xl font-bold text-gray-800 mb-6">Painel Administrativo</h1>

<!-- KPIs -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4"><p class="text-xs text-gray-500">Usuários</p><p class="text-2xl font-bold text-primary mt-1"><?= $dados['totalUsuarios'] ?></p></div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4"><p class="text-xs text-gray-500">Empresas</p><p class="text-2xl font-bold text-primary mt-1"><?= $dados['totalEmpresas'] ?></p></div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4"><p class="text-xs text-gray-500">MRR</p><p class="text-2xl font-bold text-green-600 mt-1"><?= $dados['mrr'] ?></p></div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4"><p class="text-xs text-gray-500">Churn</p><p class="text-2xl font-bold text-red-600 mt-1"><?= $dados['churn'] ?></p></div>
</div>

<!-- Links rápidos -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    <a href="<?= APP_URL ?>/admin/usuarios" class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition">
        <h3 class="font-semibold text-gray-800 mb-1">👥 Usuários</h3>
        <p class="text-xs text-gray-500"><?= $dados['totalUsuarios'] ?> cadastrados</p>
    </a>
    <a href="<?= APP_URL ?>/admin/clientes" class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition">
        <h3 class="font-semibold text-gray-800 mb-1">🏢 Clientes</h3>
        <p class="text-xs text-gray-500"><?= $dados['totalEmpresas'] ?> empresas</p>
    </a>
    <a href="<?= APP_URL ?>/admin/logs" class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition">
        <h3 class="font-semibold text-gray-800 mb-1">📋 Logs</h3>
        <p class="text-xs text-gray-500">Histórico de ações</p>
    </a>
    <a href="<?= APP_URL ?>/admin/relatorios" class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition">
        <h3 class="font-semibold text-gray-800 mb-1">📊 Relatórios</h3>
        <p class="text-xs text-gray-500">Métricas e exportações</p>
    </a>
    <a href="<?= APP_URL ?>/admin/configuracoes" class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition">
        <h3 class="font-semibold text-gray-800 mb-1">⚙️ Configurações</h3>
        <p class="text-xs text-gray-500">APIs, Academy, módulos</p>
    </a>
</div>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
