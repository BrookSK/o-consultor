<?php $tituloPagina = 'Dashboard'; ?>
<?php ob_start(); ?>

<!-- Loading Spinner -->
<div id="dashboard-loading" class="flex items-center justify-center py-20">
    <div class="text-center">
        <div class="inline-block w-10 h-10 border-4 border-gray-200 border-t-primary rounded-full animate-spin"></div>
        <p class="text-sm text-gray-500 mt-3">Carregando painel...</p>
    </div>
</div>

<!-- Dashboard Content -->
<div id="dashboard-content" class="hidden">

<!-- Header -->
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($dados['saudacao']) ?>, <?= htmlspecialchars($dados['usuario']['nome']) ?>!</h1>
    <p class="text-gray-500 mt-1"><?= htmlspecialchars(ucfirst($dados['data_atual'])) ?></p>
</div>

<!-- KPI Cards (4 cards) -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
<?php foreach ($dados['kpis'] as $kpi): ?>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center
                <?= match($kpi['cor']) {
                    'blue' => 'bg-blue-100 text-blue-600',
                    'green' => 'bg-green-100 text-green-600',
                    'purple' => 'bg-purple-100 text-purple-600',
                    'orange' => 'bg-orange-100 text-orange-600',
                    'red' => 'bg-red-100 text-red-600',
                    default => 'bg-gray-100 text-gray-600',
                } ?>">
                <?php echo match($kpi['icone']) {
                    'users' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>',
                    'clipboard' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>',
                    'target' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                    'alert' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>',
                    default => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>',
                }; ?>
            </div>
            <!-- Variação com seta -->
            <span class="text-xs font-semibold flex items-center gap-0.5
                <?= $kpi['direcao'] === 'up' && $kpi['cor'] !== 'red' ? 'text-green-600' : ($kpi['direcao'] === 'down' ? 'text-green-600' : 'text-red-600') ?>">
                <?php if ($kpi['direcao'] === 'up'): ?>
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z"/></svg>
                <?php else: ?>
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z"/></svg>
                <?php endif; ?>
                <?= htmlspecialchars($kpi['variacao']) ?>
            </span>
        </div>
        <p class="text-sm text-gray-500"><?= htmlspecialchars($kpi['titulo']) ?></p>
        <p class="text-2xl font-bold text-gray-800 mt-1"><?= htmlspecialchars($kpi['valor']) ?></p>
    </div>
<?php endforeach; ?>
</div>

<!-- Grid: Agenda + Clientes Recentes -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

    <!-- Agenda da Semana -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800">📅 Agenda da Semana</h3>
        </div>
        <div class="p-4">
            <div class="space-y-2">
                <?php foreach ($dados['agenda'] as $evento): 
                    $tipoCor = match($evento['tipo']) {
                        'reuniao' => 'border-l-blue-500 bg-blue-50/50',
                        'diagnostico' => 'border-l-green-500 bg-green-50/50',
                        'plano' => 'border-l-purple-500 bg-purple-50/50',
                        'onboarding' => 'border-l-orange-500 bg-orange-50/50',
                        'apresentacao' => 'border-l-indigo-500 bg-indigo-50/50',
                        default => 'border-l-gray-300 bg-gray-50/50',
                    };
                ?>
                <div class="flex items-center gap-3 border-l-4 rounded-r-lg px-4 py-3 <?= $tipoCor ?>">
                    <div class="flex-shrink-0 text-center min-w-[60px]">
                        <p class="text-xs font-semibold text-gray-600"><?= htmlspecialchars($evento['dia']) ?></p>
                        <p class="text-xs text-gray-400"><?= htmlspecialchars($evento['hora']) ?></p>
                    </div>
                    <p class="text-sm text-gray-700 font-medium"><?= htmlspecialchars($evento['titulo']) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Clientes Recentes -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800">👥 Clientes Recentes</h3>
        </div>
        <div class="p-4">
            <div class="space-y-3">
                <?php foreach ($dados['clientes_recentes'] as $cl): 
                    $matCor = match($cl['maturidade']) {
                        4 => 'bg-[#1E3A5F]',
                        3 => 'bg-[#1a7a1a]',
                        2 => 'bg-[#f59e0b]',
                        default => 'bg-[#CC2222]',
                    };
                ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center text-sm font-bold text-primary">
                            <?= strtoupper(substr($cl['nome'], 0, 1)) ?>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-800"><?= htmlspecialchars($cl['nome']) ?></p>
                            <p class="text-xs text-gray-500"><?= htmlspecialchars($cl['ultima_interacao']) ?> • <?= $cl['dias'] ?> dia<?= $cl['dias'] > 1 ? 's' : '' ?> atrás</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <?php for ($i = 1; $i <= 4; $i++): ?>
                        <div class="w-2 h-2 rounded-full <?= $i <= $cl['maturidade'] ? $matCor : 'bg-gray-200' ?>"></div>
                        <?php endfor; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Alertas dos Clientes -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800">⚠️ Alertas dos Clientes</h3>
    </div>
    <div class="p-4 space-y-3">
        <?php foreach ($dados['alertas'] as $alerta): 
            $nivelClasse = match($alerta['nivel']) {
                'alto' => 'border-l-red-500 bg-red-50',
                'medio' => 'border-l-yellow-500 bg-yellow-50',
                default => 'border-l-blue-500 bg-blue-50',
            };
            $nivelBadge = match($alerta['nivel']) {
                'alto' => 'bg-red-100 text-red-700',
                'medio' => 'bg-yellow-100 text-yellow-700',
                default => 'bg-blue-100 text-blue-700',
            };
        ?>
        <div class="border-l-4 rounded-r-lg p-4 <?= $nivelClasse ?>">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($alerta['cliente']) ?></p>
                    <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($alerta['mensagem']) ?></p>
                </div>
                <span class="px-2 py-0.5 rounded-full text-xs font-medium flex-shrink-0 <?= $nivelBadge ?>">
                    <?= ucfirst($alerta['nivel']) ?>
                </span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

</div><!-- /dashboard-content -->

<script>
// Simular loading
setTimeout(() => {
    document.getElementById('dashboard-loading').classList.add('hidden');
    document.getElementById('dashboard-content').classList.remove('hidden');
}, 500);
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
