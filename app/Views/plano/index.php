<?php $tituloPagina = 'Planos de Ação'; ?>
<?php ob_start(); ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Planos de Ação</li>
    </ol>
</nav>

<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-8 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Planos de Ação Estratégicos</h1>
        <p class="text-gray-500 mt-1">Gerencie os planos de ação baseados nos diagnósticos.</p>
    </div>
    <?php if (Auth::temAlgumPerfil([Auth::ADMIN_HOLDING, Auth::CONSULTOR_INTERNO])): ?>
    <a href="<?= APP_URL ?>/plano-de-acao/novo"
       class="bg-accent text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-orange-700 transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Novo Plano
    </a>
    <?php endif; ?>
</div>

<!-- Filtros -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
    <div class="flex flex-wrap gap-3">
        <select class="px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
            <option value="">Todas as empresas</option>
            <option>Tech Solutions</option>
            <option>Digital Commerce</option>
            <option>Varejo Express</option>
            <option>FoodService</option>
            <option>Construtora ABC</option>
        </select>
        <select class="px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
            <option value="">Todos os status</option>
            <option>Em elaboração</option>
            <option>Ativo</option>
            <option>Concluído</option>
            <option>Pausado</option>
        </select>
        <select class="px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
            <option value="">Todos os meses</option>
            <option>Junho 2026</option>
            <option>Maio 2026</option>
            <option>Abril 2026</option>
        </select>
    </div>
</div>

<!-- Tabela de Planos -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-6 py-3 font-medium text-gray-500">Empresa</th>
                    <th class="text-left px-6 py-3 font-medium text-gray-500">Data</th>
                    <th class="text-left px-6 py-3 font-medium text-gray-500">Tarefas</th>
                    <th class="text-left px-6 py-3 font-medium text-gray-500">Progresso</th>
                    <th class="text-left px-6 py-3 font-medium text-gray-500">Status</th>
                    <th class="text-left px-6 py-3 font-medium text-gray-500">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($dados['planos'] as $plano):
                    $statusConfig = match($plano['status']) {
                        'ativo' => ['badge' => 'bg-green-100 text-green-700', 'label' => 'Ativo'],
                        'em_elaboracao' => ['badge' => 'bg-blue-100 text-blue-700', 'label' => 'Em elaboração'],
                        'concluido' => ['badge' => 'bg-gray-100 text-gray-700', 'label' => 'Concluído'],
                        'pausado' => ['badge' => 'bg-yellow-100 text-yellow-700', 'label' => 'Pausado'],
                        default => ['badge' => 'bg-gray-100 text-gray-600', 'label' => 'Indefinido'],
                    };
                    $barCor = $plano['progresso'] >= 80 ? 'bg-green-500' : ($plano['progresso'] >= 50 ? 'bg-blue-500' : ($plano['progresso'] >= 30 ? 'bg-yellow-500' : 'bg-red-500'));
                ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 font-medium text-gray-800"><?= htmlspecialchars($plano['empresa']) ?></td>
                    <td class="px-6 py-4 text-gray-500"><?= date('d/m/Y', strtotime($plano['data'])) ?></td>
                    <td class="px-6 py-4 text-gray-600">
                        <span class="font-semibold text-gray-800"><?= $plano['concluidas'] ?></span> / <?= $plano['total_tarefas'] ?>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <div class="w-24 h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full rounded-full <?= $barCor ?>" style="width: <?= $plano['progresso'] ?>%"></div>
                            </div>
                            <span class="text-xs font-medium text-gray-600"><?= $plano['progresso'] ?>%</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium <?= $statusConfig['badge'] ?>"><?= $statusConfig['label'] ?></span>
                    </td>
                    <td class="px-6 py-4">
                        <a href="<?= APP_URL ?>/plano-de-acao/ver?id=<?= $plano['id'] ?>" class="text-primary hover:underline text-sm font-medium">Abrir →</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
