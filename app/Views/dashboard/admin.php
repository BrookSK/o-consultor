<?php $tituloPagina = 'Dashboard'; ?>
<?php ob_start(); ?>

<!-- Loading Spinner -->
<div id="dashboard-loading" class="flex items-center justify-center py-20">
    <div class="text-center">
        <div class="inline-block w-10 h-10 border-4 border-gray-200 border-t-primary rounded-full animate-spin"></div>
        <p class="text-sm text-gray-500 mt-3">Carregando painel...</p>
    </div>
</div>

<!-- Dashboard Content (hidden until loaded) -->
<div id="dashboard-content" class="hidden">

<!-- Header: Saudação + Data -->
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($dados['saudacao']) ?>, <?= htmlspecialchars($dados['usuario']['nome']) ?>!</h1>
    <p class="text-gray-500 mt-1"><?= htmlspecialchars(ucfirst($dados['data_atual'])) ?></p>
</div>

<!-- KPI Cards (6 cards) -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
<?php foreach ($dados['kpis'] as $kpi): ?>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-md transition">
        <div class="flex items-center justify-between mb-2">
            <!-- Ícone -->
            <div class="w-9 h-9 rounded-lg flex items-center justify-center
                <?= match($kpi['cor']) {
                    'blue' => 'bg-blue-100 text-blue-600',
                    'green' => 'bg-green-100 text-green-600',
                    'purple' => 'bg-purple-100 text-purple-600',
                    'indigo' => 'bg-indigo-100 text-indigo-600',
                    'orange' => 'bg-orange-100 text-orange-600',
                    'red' => 'bg-red-100 text-red-600',
                    default => 'bg-gray-100 text-gray-600',
                } ?>">
                <?php echo match($kpi['icone']) {
                    'users' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>',
                    'clipboard' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>',
                    'target' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                    'book' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>',
                    'currency' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                    'alert' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>',
                    default => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>',
                }; ?>
            </div>
            <!-- Variação -->
            <span class="text-xs font-semibold flex items-center gap-0.5
                <?= $kpi['direcao'] === 'up' && $kpi['cor'] !== 'red' ? 'text-green-600' : ($kpi['direcao'] === 'down' && $kpi['cor'] === 'red' ? 'text-green-600' : ($kpi['direcao'] === 'up' && $kpi['cor'] === 'red' ? 'text-red-600' : 'text-gray-500')) ?>">
                <?php if ($kpi['direcao'] === 'up'): ?>
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z"/></svg>
                <?php elseif ($kpi['direcao'] === 'down'): ?>
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z"/></svg>
                <?php endif; ?>
                <?= htmlspecialchars($kpi['variacao']) ?>
            </span>
        </div>
        <p class="text-xs text-gray-500 mb-1"><?= htmlspecialchars($kpi['titulo']) ?></p>
        <p class="text-xl font-bold text-gray-800"><?= htmlspecialchars($kpi['valor']) ?></p>
    </div>
<?php endforeach; ?>
</div>

<!-- Visão Operacional (Grid 2x2) -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

    <!-- Clientes por Status (Rosca) -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800">Clientes por Status</h3>
        </div>
        <div class="p-6 flex items-center justify-center">
            <canvas id="chart-clientes-status" width="280" height="280"></canvas>
        </div>
    </div>

    <!-- Alertas Críticos (Tabela) -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800">Alertas Críticos</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left px-4 py-2 font-medium text-gray-500">Cliente</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-500">Tipo</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-500">Nível</th>
                        <th class="text-left px-4 py-2 font-medium text-gray-500">Tempo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($dados['alertas_criticos'] as $alerta): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars($alerta['cliente']) ?></td>
                        <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($alerta['tipo']) ?></td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $alerta['nivel'] === 'alto' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700' ?>">
                                <?= ucfirst($alerta['nivel']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-400 text-xs"><?= htmlspecialchars($alerta['tempo']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Parceiros por Categoria (Barras) -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800">Parceiros por Categoria</h3>
        </div>
        <div class="p-6">
            <canvas id="chart-parceiros" width="400" height="250"></canvas>
        </div>
    </div>

    <!-- Últimas Atividades (Log) -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800">Últimas Atividades</h3>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                <?php foreach ($dados['atividades'] as $ativ): ?>
                <div class="flex items-start gap-3">
                    <div class="w-2.5 h-2.5 rounded-full mt-1.5 flex-shrink-0 bg-<?= $ativ['cor'] ?>-500"></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-700"><?= htmlspecialchars($ativ['acao']) ?></p>
                        <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($ativ['detalhe']) ?></p>
                    </div>
                    <span class="text-xs text-gray-400 flex-shrink-0"><?= htmlspecialchars($ativ['tempo']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Abas: Diagnósticos | Planos | Conteúdo -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-8" x-data="{ abaAtiva: 'diagnosticos' }">
    <div class="border-b border-gray-200">
        <nav class="flex gap-0 px-6">
            <button @click="abaAtiva = 'diagnosticos'" 
                    :class="abaAtiva === 'diagnosticos' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500 hover:text-gray-700'"
                    class="px-4 py-3 text-sm transition">Diagnósticos</button>
            <button @click="abaAtiva = 'planos'"
                    :class="abaAtiva === 'planos' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500 hover:text-gray-700'"
                    class="px-4 py-3 text-sm transition">Planos</button>
            <button @click="abaAtiva = 'conteudo'"
                    :class="abaAtiva === 'conteudo' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500 hover:text-gray-700'"
                    class="px-4 py-3 text-sm transition">Conteúdo</button>
        </nav>
    </div>

    <!-- Tab Diagnósticos -->
    <div x-show="abaAtiva === 'diagnosticos'" class="p-6">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-4 py-2 font-medium text-gray-500">Empresa</th>
                    <th class="text-left px-4 py-2 font-medium text-gray-500">Pontuação</th>
                    <th class="text-left px-4 py-2 font-medium text-gray-500">Status</th>
                    <th class="text-left px-4 py-2 font-medium text-gray-500">Data</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($dados['tab_diagnosticos'] as $diag): ?>
                <tr>
                    <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars($diag['empresa']) ?></td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <div class="w-20 h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full rounded-full <?= $diag['pontuacao'] >= 70 ? 'bg-green-500' : ($diag['pontuacao'] >= 50 ? 'bg-yellow-500' : 'bg-red-500') ?>"
                                     style="width: <?= $diag['pontuacao'] ?>%"></div>
                            </div>
                            <span class="text-xs text-gray-600"><?= $diag['pontuacao'] ?>%</span>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $diag['status'] === 'concluido' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?>">
                            <?= $diag['status'] === 'concluido' ? 'Concluído' : 'Em andamento' ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500"><?= date('d/m/Y', strtotime($diag['data'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Tab Planos -->
    <div x-show="abaAtiva === 'planos'" class="p-6" style="display: none;">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-4 py-2 font-medium text-gray-500">Empresa</th>
                    <th class="text-left px-4 py-2 font-medium text-gray-500">Progresso</th>
                    <th class="text-left px-4 py-2 font-medium text-gray-500">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($dados['tab_planos'] as $plano): ?>
                <tr>
                    <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars($plano['empresa']) ?></td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <div class="w-24 h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-primary rounded-full" style="width: <?= $plano['progresso'] ?>%"></div>
                            </div>
                            <span class="text-xs text-gray-600"><?= $plano['progresso'] ?>%</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-gray-600 text-xs"><?= $plano['acoes_feitas'] ?>/<?= $plano['acoes_total'] ?> concluídas</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Tab Conteúdo -->
    <div x-show="abaAtiva === 'conteudo'" class="p-6" style="display: none;">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-4 py-2 font-medium text-gray-500">Título</th>
                    <th class="text-left px-4 py-2 font-medium text-gray-500">Tipo</th>
                    <th class="text-left px-4 py-2 font-medium text-gray-500">Views</th>
                    <th class="text-left px-4 py-2 font-medium text-gray-500">Data</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($dados['tab_conteudo'] as $cont): ?>
                <tr>
                    <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars($cont['titulo']) ?></td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700"><?= ucfirst($cont['tipo']) ?></span>
                    </td>
                    <td class="px-4 py-3 text-gray-600"><?= $cont['views'] ?></td>
                    <td class="px-4 py-3 text-gray-500"><?= date('d/m/Y', strtotime($cont['data'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Mapa de Maturidade -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800">Mapa de Maturidade dos Clientes</h3>
        <p class="text-xs text-gray-400 mt-1">Classificação de 1 (Inicial) a 4 (Excelência)</p>
    </div>
    <div class="p-6">
        <!-- Legenda -->
        <div class="flex flex-wrap gap-4 mb-6">
            <span class="flex items-center gap-2 text-xs"><span class="w-3 h-3 rounded-full bg-[#CC2222]"></span> Nível 1 — Inicial</span>
            <span class="flex items-center gap-2 text-xs"><span class="w-3 h-3 rounded-full bg-[#f59e0b]"></span> Nível 2 — Desenvolvimento</span>
            <span class="flex items-center gap-2 text-xs"><span class="w-3 h-3 rounded-full bg-[#1a7a1a]"></span> Nível 3 — Crescimento</span>
            <span class="flex items-center gap-2 text-xs"><span class="w-3 h-3 rounded-full bg-[#1E3A5F]"></span> Nível 4 — Excelência</span>
        </div>
        <!-- Grid de clientes -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
            <?php foreach ($dados['mapa_maturidade'] as $mp): ?>
            <div class="border rounded-lg p-3 text-center hover:shadow-sm transition" style="border-left: 4px solid <?= $mp['cor'] ?>">
                <p class="text-sm font-medium text-gray-800 truncate"><?= htmlspecialchars($mp['empresa']) ?></p>
                <div class="flex items-center justify-center gap-1 mt-2">
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                    <div class="w-3 h-3 rounded-full <?= $i <= $mp['nivel'] ? '' : 'opacity-20' ?>" style="background-color: <?= $mp['cor'] ?>"></div>
                    <?php endfor; ?>
                </div>
                <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($mp['label']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

</div><!-- /dashboard-content -->

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Simular loading
setTimeout(() => {
    document.getElementById('dashboard-loading').classList.add('hidden');
    document.getElementById('dashboard-content').classList.remove('hidden');
    initCharts();
}, 600);

function initCharts() {
    // Gráfico Rosca — Clientes por Status
    const ctxRosca = document.getElementById('chart-clientes-status').getContext('2d');
    new Chart(ctxRosca, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($dados['clientes_status']['labels']) ?>,
            datasets: [{
                data: <?= json_encode($dados['clientes_status']['valores']) ?>,
                backgroundColor: <?= json_encode($dados['clientes_status']['cores']) ?>,
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            cutout: '60%',
            plugins: {
                legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true, pointStyle: 'circle' } }
            }
        }
    });

    // Gráfico Barras — Parceiros por Categoria
    const ctxBarras = document.getElementById('chart-parceiros').getContext('2d');
    new Chart(ctxBarras, {
        type: 'bar',
        data: {
            labels: <?= json_encode($dados['parceiros_categorias']['labels']) ?>,
            datasets: [{
                label: 'Parceiros',
                data: <?= json_encode($dados['parceiros_categorias']['valores']) ?>,
                backgroundColor: '#1E3A5F',
                borderRadius: 4,
                barThickness: 32
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f3f4f6' } },
                x: { grid: { display: false } }
            }
        }
    });
}
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
