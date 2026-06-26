<?php $tituloPagina = 'Resultado do Diagnóstico'; ?>
<?php ob_start(); ?>

<!-- Breadcrumb -->
<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li><a href="<?= APP_URL ?>/diagnostico" class="hover:text-primary">Diagnósticos</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Resultado</li>
    </ol>
</nav>

<?php
    $resultado = $dados['resultado'];
    $nivel = $resultado['nivel'];
?>

<!-- Header com Score -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
    <div class="flex flex-col md:flex-row items-center gap-6">
        <!-- Score Circle -->
        <div class="flex-shrink-0">
            <div class="w-28 h-28 rounded-full border-8 flex items-center justify-center"
                 style="border-color: <?= $nivel['cor'] ?>">
                <div class="text-center">
                    <p class="text-3xl font-bold" style="color: <?= $nivel['cor'] ?>"><?= $resultado['score'] ?></p>
                    <p class="text-xs text-gray-500">de 4</p>
                </div>
            </div>
        </div>
        <!-- Info -->
        <div class="flex-1 text-center md:text-left">
            <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($resultado['empresa']) ?></h1>
            <div class="flex items-center justify-center md:justify-start gap-2 mt-2">
                <span class="px-3 py-1 rounded-full text-sm font-semibold text-white" style="background-color: <?= $nivel['cor'] ?>">
                    Nível <?= $resultado['score'] ?> — <?= htmlspecialchars($nivel['label']) ?>
                </span>
            </div>
            <p class="text-gray-600 mt-3 text-sm max-w-xl"><?= htmlspecialchars($nivel['descricao']) ?></p>
        </div>
        <!-- Indicadores visuais -->
        <div class="flex-shrink-0 flex gap-2">
            <?php for ($i = 1; $i <= 4; $i++): 
                $nivelCores = [1 => '#CC2222', 2 => '#f59e0b', 3 => '#1a7a1a', 4 => '#1E3A5F'];
            ?>
            <div class="w-4 h-16 rounded-full <?= $i <= $resultado['score'] ? '' : 'opacity-20' ?>"
                 style="background-color: <?= $nivelCores[$i] ?>"></div>
            <?php endfor; ?>
        </div>
    </div>
</div>

<!-- Resumo por Área -->
<div class="mb-8">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">📊 Resumo por Área</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($resultado['areas'] as $area):
            $statusConfig = match($area['status']) {
                'adequado' => ['bg' => 'bg-green-50 border-green-200', 'badge' => 'bg-green-100 text-green-700', 'icon' => '✓'],
                'atenção' => ['bg' => 'bg-yellow-50 border-yellow-200', 'badge' => 'bg-yellow-100 text-yellow-700', 'icon' => '⚠'],
                'crítico' => ['bg' => 'bg-red-50 border-red-200', 'badge' => 'bg-red-100 text-red-700', 'icon' => '✗'],
                default => ['bg' => 'bg-gray-50 border-gray-200', 'badge' => 'bg-gray-100 text-gray-700', 'icon' => '—'],
            };
        ?>
        <div class="rounded-lg border p-4 <?= $statusConfig['bg'] ?>">
            <div class="flex items-center justify-between mb-2">
                <h4 class="font-semibold text-gray-800"><?= htmlspecialchars($area['area']) ?></h4>
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?= $statusConfig['badge'] ?>">
                    <?= $statusConfig['icon'] ?> <?= ucfirst($area['status']) ?>
                </span>
            </div>
            <p class="text-sm text-gray-600"><?= htmlspecialchars($area['comentario']) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Mapa de Riscos -->
<?php if (!empty($resultado['riscos'])): ?>
<div class="mb-8">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">🚨 Mapa de Riscos</h2>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-6 py-3 font-medium text-gray-500">Tipo</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500">Descrição</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500">Criticidade</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500">Ação Sugerida</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($resultado['riscos'] as $risco):
                        $critBadge = match($risco['criticidade']) {
                            'alta' => 'bg-red-100 text-red-700',
                            'media' => 'bg-yellow-100 text-yellow-700',
                            default => 'bg-blue-100 text-blue-700',
                        };
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 font-medium text-gray-800"><?= htmlspecialchars($risco['tipo']) ?></td>
                        <td class="px-6 py-3 text-gray-600"><?= htmlspecialchars($risco['descricao']) ?></td>
                        <td class="px-6 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?= $critBadge ?>">
                                <?= ucfirst($risco['criticidade']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-3 text-gray-600"><?= htmlspecialchars($risco['acao']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Confirmação + Ações -->
<div class="bg-gradient-to-r from-primary/5 to-accent/5 rounded-lg border border-primary/20 p-6 mb-6">
    <div class="flex items-start gap-4">
        <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <div>
            <h3 class="text-lg font-semibold text-gray-800">Dados Registrados com Sucesso</h3>
            <p class="text-sm text-gray-600 mt-1">
                O sistema usará estas informações para gerar seu <strong>Plano de Ação</strong> e <strong>SOPs personalizados</strong> com base no diagnóstico da empresa.
            </p>
        </div>
    </div>
</div>

<!-- Botões de ação -->
<div class="flex flex-col sm:flex-row gap-4">
    <a href="<?= APP_URL ?>/plano-de-acao" 
       class="flex-1 bg-primary text-white px-6 py-4 rounded-lg font-medium text-sm hover:bg-primary-700 transition text-center flex items-center justify-center gap-3">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>
            <span class="block font-semibold">Gerar Plano de Ação</span>
            <span class="block text-xs text-white/70">Ações prioritárias baseadas no diagnóstico</span>
        </span>
    </a>

    <a href="<?= APP_URL ?>/manual-operacional" 
       class="flex-1 bg-accent text-white px-6 py-4 rounded-lg font-medium text-sm hover:bg-orange-700 transition text-center flex items-center justify-center gap-3">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
        </svg>
        <span>
            <span class="block font-semibold">Gerar Manual Operacional</span>
            <span class="block text-xs text-white/70">SOPs personalizados por departamento</span>
        </span>
    </a>
</div>

<!-- Link voltar -->
<div class="mt-6 text-center">
    <a href="<?= APP_URL ?>/diagnostico" class="text-sm text-gray-500 hover:text-primary">← Voltar para lista de diagnósticos</a>
</div>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
