<?php $tituloPagina = 'KPI - ' . $kpi['nome']; ?>
<?php ob_start(); ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li><a href="<?= APP_URL ?>/manual-operacional/kpis" class="hover:text-primary">KPIs</a></li>
        <li>/</li>
        <li class="font-medium text-primary"><?= htmlspecialchars($kpi['nome']) ?></li>
    </ol>
</nav>

<!-- Header do KPI -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
    <div class="flex flex-col md:flex-row items-start justify-between gap-4">
        <div class="flex-1">
            <div class="flex items-center gap-3 mb-2">
                <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($kpi['nome']) ?></h1>
                <?php 
                $zonaClass = match($kpi['zona_atual'] ?? 'sem_medicao') {
                    'vermelha' => 'bg-red-100 text-red-700',
                    'amarela' => 'bg-yellow-100 text-yellow-700',
                    'verde' => 'bg-green-100 text-green-700',
                    default => 'bg-gray-100 text-gray-700'
                };
                $zonaTexto = match($kpi['zona_atual'] ?? 'sem_medicao') {
                    'vermelha' => '🔴 Crítica',
                    'amarela' => '🟡 Atenção', 
                    'verde' => '🟢 OK',
                    default => '⚪ Sem medição'
                };
                ?>
                <span class="px-3 py-1 rounded-full text-sm font-medium <?= $zonaClass ?>"><?= $zonaTexto ?></span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <p class="text-gray-500">SOP de Origem</p>
                    <p class="font-medium">
                        <a href="<?= APP_URL ?>/sop/ver/<?= $kpi['sop_id'] ?>" class="text-primary hover:underline">
                            <?= htmlspecialchars($kpi['sop_codigo']) ?>
                        </a>
                    </p>
                    <p class="text-xs text-gray-500"><?= htmlspecialchars($kpi['sop_titulo']) ?></p>
                </div>
                <div>
                    <p class="text-gray-500">Valor Atual</p>
                    <p class="font-bold text-lg <?= $kpi['zona_atual'] === 'vermelha' ? 'text-red-600' : ($kpi['zona_atual'] === 'amarela' ? 'text-yellow-600' : 'text-green-600') ?>">
                        <?= htmlspecialchars($kpi['valor_atual'] ?: 'Não medido') ?>
                    </p>
                </div>
                <div>
                    <p class="text-gray-500">Responsável</p>
                    <p class="font-medium"><?= htmlspecialchars($kpi['responsavel'] ?: 'A definir') ?></p>
                </div>
            </div>
        </div>
        
        <div class="flex gap-2">
            <button onclick="abrirModalRegistrar()" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">
                📊 Registrar Valor
            </button>
            <button onclick="abrirPlanoContencao()" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700">
                🚨 Ver Plano de Contingência
            </button>
        </div>
    </div>
</div>

<!-- Análise da IA (se KPI estiver crítico) -->
<?php if ($analise_ia && $kpi['zona_atual'] === 'vermelha'): ?>
<div class="bg-red-50 border-l-4 border-red-500 p-6 mb-6 rounded-r-lg">
    <h3 class="text-lg font-semibold text-red-800 mb-4">🤖 Análise de IA - KPI Crítico</h3>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Causas Raiz -->
        <div>
            <h4 class="font-medium text-red-700 mb-2">🎯 Possíveis Causas Raiz:</h4>
            <ul class="space-y-1">
                <?php foreach (json_decode($analise_ia['causas_raiz'], true) as $causa): ?>
                <li class="text-sm text-red-800 flex items-start gap-2">
                    <span class="w-1.5 h-1.5 bg-red-600 rounded-full mt-2 flex-shrink-0"></span>
                    <?= htmlspecialchars($causa) ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <!-- Plano de Ação -->
        <div>
            <h4 class="font-medium text-red-700 mb-2">⚡ Plano de Ação Imediato:</h4>
            <ol class="space-y-1">
                <?php foreach (json_decode($analise_ia['plano_acao_imediato'], true) as $i => $acao): ?>
                <li class="text-sm text-red-800 flex items-start gap-2">
                    <span class="w-5 h-5 bg-red-600 text-white rounded-full text-xs flex items-center justify-center flex-shrink-0 mt-0.5"><?= $i + 1 ?></span>
                    <?= htmlspecialchars($acao) ?>
                </li>
                <?php endforeach; ?>
            </ol>
        </div>
    </div>
    
    <div class="mt-4 pt-4 border-t border-red-200 flex items-center justify-between text-sm">
        <div class="text-red-700">
            <strong>Prazo para revisão:</strong> <?= htmlspecialchars($analise_ia['prazo_revisao']) ?> | 
            <strong>Contingência sugerida:</strong> <?= htmlspecialchars($analise_ia['contencao_recomendada']) ?>
        </div>
        <div class="text-red-600 text-xs">
            Análise gerada em <?= date('d/m/Y H:i', strtotime($analise_ia['criado_em'])) ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Metas e Histórico -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Metas -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">🎯 Metas Definidas</h3>
        <div class="space-y-3">
            <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg border border-green-200">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <span class="text-sm font-medium text-green-800">Verde (Ideal)</span>
                </div>
                <span class="text-sm font-bold text-green-700"><?= htmlspecialchars($kpi['meta_verde']) ?></span>
            </div>
            
            <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                    <span class="text-sm font-medium text-yellow-800">Amarela (Atenção)</span>
                </div>
                <span class="text-sm font-bold text-yellow-700"><?= htmlspecialchars($kpi['meta_amarela']) ?></span>
            </div>
            
            <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg border border-red-200">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                    <span class="text-sm font-medium text-red-800">Vermelha (Crítica)</span>
                </div>
                <span class="text-sm font-bold text-red-700"><?= htmlspecialchars($kpi['meta_vermelha']) ?></span>
            </div>
        </div>
        
        <div class="mt-4 p-3 bg-gray-50 rounded-lg">
            <p class="text-xs text-gray-600"><?= htmlspecialchars($kpi['acao_vermelha']) ?></p>
        </div>
    </div>
    
    <!-- Gráfico de Evolução -->
    <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">📈 Evolução Histórica</h3>
        
        <?php if (!empty($historico)): ?>
        <div class="h-64">
            <canvas id="grafico-kpi" width="100%" height="100%"></canvas>
        </div>
        <?php else: ?>
        <div class="h-64 flex items-center justify-center text-gray-400">
            <div class="text-center">
                <p class="text-lg">📊</p>
                <p class="text-sm mt-2">Nenhum valor registrado ainda</p>
                <p class="text-xs">Registre o primeiro valor para ver o gráfico</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Histórico de Registros -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800">📋 Histórico de Registros</h3>
    </div>
    
    <?php if (!empty($historico)): ?>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-6 py-3 font-medium text-gray-500">Data</th>
                    <th class="text-left px-6 py-3 font-medium text-gray-500">Valor</th>
                    <th class="text-left px-6 py-3 font-medium text-gray-500">Zona</th>
                    <th class="text-left px-6 py-3 font-medium text-gray-500">Observações</th>
                    <th class="text-left px-6 py-3 font-medium text-gray-500">Registrado por</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach (array_reverse($historico) as $registro): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 text-sm text-gray-800"><?= date('d/m/Y', strtotime($registro['data_medicao'])) ?></td>
                    <td class="px-6 py-3">
                        <span class="font-medium <?= $registro['zona_calculada'] === 'vermelha' ? 'text-red-600' : ($registro['zona_calculada'] === 'amarela' ? 'text-yellow-600' : 'text-green-600') ?>">
                            <?= htmlspecialchars($registro['valor']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-3">
                        <?php 
                        $zonaClass = match($registro['zona_calculada']) {
                            'vermelha' => 'bg-red-100 text-red-700',
                            'amarela' => 'bg-yellow-100 text-yellow-700',
                            'verde' => 'bg-green-100 text-green-700'
                        };
                        $zonaTexto = match($registro['zona_calculada']) {
                            'vermelha' => '🔴 Crítica',
                            'amarela' => '🟡 Atenção', 
                            'verde' => '🟢 OK'
                        };
                        ?>
                        <span class="px-2 py-1 rounded text-xs font-medium <?= $zonaClass ?>"><?= $zonaTexto ?></span>
                    </td>
                    <td class="px-6 py-3 text-sm text-gray-600"><?= htmlspecialchars($registro['observacoes'] ?: '-') ?></td>
                    <td class="px-6 py-3 text-sm text-gray-500"><?= date('d/m H:i', strtotime($registro['criado_em'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="p-8 text-center text-gray-400">
        <p class="text-lg">📊</p>
        <p class="text-sm mt-2">Nenhum registro encontrado</p>
        <p class="text-xs">Use o botão "Registrar Valor" para adicionar medições</p>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Registrar Valor -->
<div id="modal-registrar" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg max-w-md w-full mx-4">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold">📊 Registrar Novo Valor</h3>
            <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($kpi['nome']) ?></p>
        </div>
        <form id="form-registrar" class="p-6 space-y-4">
            <input type="hidden" name="kpi_id" value="<?= $kpi['id'] ?>">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Valor</label>
                <input type="text" name="valor" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary"
                       placeholder="Ex: 95%, 12 min, 100">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data da Medição</label>
                <input type="date" name="data" required value="<?= date('Y-m-d') ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Observações (opcional)</label>
                <textarea name="observacoes" rows="2"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary resize-none"
                          placeholder="Contexto ou observações sobre esta medição"></textarea>
            </div>
            
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded-lg font-medium hover:bg-blue-700">
                    Registrar
                </button>
                <button type="button" onclick="fecharModalRegistrar()" class="flex-1 border border-gray-300 text-gray-700 py-2 rounded-lg font-medium hover:bg-gray-50">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Gráfico de evolução histórica
<?php if (!empty($historico)): ?>
const ctx = document.getElementById('grafico-kpi').getContext('2d');
const historico = <?= json_encode($historico) ?>;

const labels = historico.map(r => new Date(r.data_medicao).toLocaleDateString('pt-BR'));
const valores = historico.map(r => parseFloat(r.valor.replace(/[^\d.,]/g, '').replace(',', '.')));
const zonas = historico.map(r => r.zona_calculada);

const cores = zonas.map(zona => {
    switch(zona) {
        case 'verde': return 'rgba(34, 197, 94, 0.8)';
        case 'amarela': return 'rgba(251, 191, 36, 0.8)';
        case 'vermelha': return 'rgba(239, 68, 68, 0.8)';
        default: return 'rgba(156, 163, 175, 0.8)';
    }
});

new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: '<?= htmlspecialchars($kpi['nome']) ?>',
            data: valores,
            borderColor: 'rgba(59, 130, 246, 1)',
            backgroundColor: cores,
            pointBackgroundColor: cores,
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 6,
            tension: 0.1,
            fill: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Valor'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Data'
                }
            }
        },
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    afterLabel: function(context) {
                        const zona = zonas[context.dataIndex];
                        return `Zona: ${zona.charAt(0).toUpperCase() + zona.slice(1)}`;
                    }
                }
            }
        }
    }
});
<?php endif; ?>

// Modal functions
function abrirModalRegistrar() {
    document.getElementById('modal-registrar').classList.remove('hidden');
    document.querySelector('input[name="valor"]').focus();
}

function fecharModalRegistrar() {
    document.getElementById('modal-registrar').classList.add('hidden');
    document.getElementById('form-registrar').reset();
}

function abrirPlanoContencao() {
    window.location.href = '<?= APP_URL ?>/sop/ver/<?= $kpi['sop_id'] ?>#contencao';
}

// Form submission
document.getElementById('form-registrar').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('csrf_token', '<?= Csrf::token() ?>');
    
    try {
        const res = await fetch('<?= APP_URL ?>/kpis/registrar', { method: 'POST', body: formData });
        const data = await res.json();
        
        if (data.sucesso) {
            alert(`Valor registrado! Zona: ${data.zona}${data.alerta_criado ? ' (Alerta criado automaticamente)' : ''}`);
            location.reload();
        } else {
            alert(data.erro || 'Erro ao registrar valor.');
        }
    } catch (e) {
        alert('Erro de conexão.');
    }
});

// Fechar modal clicando fora
document.getElementById('modal-registrar').addEventListener('click', function(e) {
    if (e.target === this) {
        fecharModalRegistrar();
    }
});
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>