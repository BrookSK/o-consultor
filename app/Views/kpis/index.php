<?php $tituloPagina = 'Painel de KPIs'; ?>
<?php ob_start(); ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li><a href="<?= APP_URL ?>/manual-operacional" class="hover:text-primary">Manual Operacional</a></li>
        <li>/</li>
        <li class="font-medium text-primary">KPIs</li>
    </ol>
</nav>

<!-- Header -->
<div class="flex flex-col lg:flex-row items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Painel de KPIs</h1>
        <p class="text-gray-500 mt-1">Monitoramento de indicadores de todos os SOPs aprovados</p>
    </div>
    <div class="flex gap-2">
        <a href="<?= APP_URL ?>/manual-operacional" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">← Manual Operacional</a>
    </div>
</div>

<!-- Alertas Críticos (se houver) -->
<?php if (!empty($alertas_ativos)): ?>
<div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-lg">
    <div class="flex items-start justify-between">
        <div>
            <h3 class="text-sm font-semibold text-red-800">🚨 Alertas Críticos Ativos</h3>
            <div class="mt-2 space-y-1">
                <?php foreach (array_slice($alertas_ativos, 0, 3) as $alerta): ?>
                <p class="text-sm text-red-700">
                    <strong><?= htmlspecialchars($alerta['kpi_nome']) ?></strong> (<?= htmlspecialchars($alerta['sop_codigo']) ?>) 
                    — <?= htmlspecialchars($alerta['titulo']) ?>
                </p>
                <?php endforeach; ?>
                <?php if (count($alertas_ativos) > 3): ?>
                <p class="text-xs text-red-600">... e mais <?= count($alertas_ativos) - 3 ?> alertas</p>
                <?php endif; ?>
            </div>
        </div>
        <span class="px-2 py-1 bg-red-600 text-white rounded text-xs font-semibold"><?= count($alertas_ativos) ?></span>
    </div>
</div>
<?php endif; ?>

<!-- Estatísticas Gerais -->
<div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total KPIs</p>
                <p class="text-2xl font-bold text-gray-800"><?= $stats['total'] ?></p>
            </div>
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">📊</div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Zona Verde</p>
                <p class="text-2xl font-bold text-green-600"><?= $stats['verde'] ?></p>
            </div>
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">🟢</div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Zona Amarela</p>
                <p class="text-2xl font-bold text-yellow-600"><?= $stats['amarela'] ?></p>
            </div>
            <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">🟡</div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Zona Vermelha</p>
                <p class="text-2xl font-bold text-red-600"><?= $stats['vermelha'] ?></p>
            </div>
            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">🔴</div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Sem Medição</p>
                <p class="text-2xl font-bold text-gray-500"><?= $stats['sem_medicao'] ?></p>
            </div>
            <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">⚪</div>
        </div>
    </div>
</div>

<!-- Tabela de KPIs -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800">KPIs por SOP</h3>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-6 py-3 font-medium text-gray-500">KPI</th>
                    <th class="text-left px-6 py-3 font-medium text-gray-500">SOP de Origem</th>
                    <th class="text-left px-6 py-3 font-medium text-gray-500">Valor Atual</th>
                    <th class="text-left px-6 py-3 font-medium text-gray-500">Meta</th>
                    <th class="text-left px-6 py-3 font-medium text-gray-500">Zona</th>
                    <th class="text-left px-6 py-3 font-medium text-gray-500">Última Medição</th>
                    <th class="text-left px-6 py-3 font-medium text-gray-500">Responsável</th>
                    <th class="text-right px-6 py-3 font-medium text-gray-500">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($kpis as $kpi): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div>
                            <p class="font-medium text-gray-800"><?= htmlspecialchars($kpi['nome']) ?></p>
                            <p class="text-xs text-gray-500"><?= $kpi['total_medicoes'] ?> medições registradas</p>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div>
                            <p class="text-sm font-medium text-primary"><?= htmlspecialchars($kpi['sop_codigo']) ?></p>
                            <p class="text-xs text-gray-500"><?= htmlspecialchars($kpi['sop_titulo']) ?></p>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <?php if ($kpi['valor_atual']): ?>
                        <span class="font-medium <?= $kpi['zona_atual'] === 'vermelha' ? 'text-red-600' : ($kpi['zona_atual'] === 'amarela' ? 'text-yellow-600' : 'text-green-600') ?>">
                            <?= htmlspecialchars($kpi['valor_atual']) ?>
                        </span>
                        <?php else: ?>
                        <span class="text-gray-400 text-sm">Não medido</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-xs space-y-0.5">
                            <div class="flex items-center gap-1">
                                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                <span><?= htmlspecialchars($kpi['meta_verde']) ?></span>
                            </div>
                            <div class="flex items-center gap-1">
                                <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
                                <span><?= htmlspecialchars($kpi['meta_amarela']) ?></span>
                            </div>
                            <div class="flex items-center gap-1">
                                <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                                <span><?= htmlspecialchars($kpi['meta_vermelha']) ?></span>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
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
                        <span class="px-2 py-1 rounded text-xs font-medium <?= $zonaClass ?>"><?= $zonaTexto ?></span>
                    </td>
                    <td class="px-6 py-4">
                        <?php if ($kpi['ultima_medicao']): ?>
                        <span class="text-sm text-gray-600"><?= date('d/m/Y', strtotime($kpi['ultima_medicao'])) ?></span>
                        <?php else: ?>
                        <span class="text-xs text-gray-400">Nunca</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm text-gray-600"><?= htmlspecialchars($kpi['responsavel'] ?: 'A definir') ?></span>
                    </td>
                    <td class="px-6 py-4 text-right space-x-2">
                        <button onclick="abrirModalRegistrar(<?= $kpi['id'] ?>, '<?= htmlspecialchars($kpi['nome']) ?>')" 
                                class="px-3 py-1 bg-blue-600 text-white rounded text-xs font-medium hover:bg-blue-700">
                            Registrar Valor
                        </button>
                        <a href="<?= APP_URL ?>/kpis/ver?id=<?= $kpi['id'] ?>" 
                           class="px-3 py-1 border border-gray-300 text-gray-700 rounded text-xs font-medium hover:bg-gray-50">
                            Ver Detalhes
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($kpis)): ?>
                <tr>
                    <td colspan="8" class="px-6 py-8 text-center">
                        <div class="text-gray-400">
                            <p class="text-lg">📊</p>
                            <p class="text-sm mt-2">Nenhum KPI encontrado</p>
                            <p class="text-xs text-gray-500">KPIs são criados automaticamente quando SOPs são aprovados</p>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Registrar Valor -->
<div id="modal-registrar" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg max-w-md w-full mx-4">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold" id="modal-titulo">Registrar Valor do KPI</h3>
        </div>
        <form id="form-registrar" class="p-6 space-y-4">
            <input type="hidden" id="kpi_id" name="kpi_id">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Valor</label>
                <input type="text" id="valor" name="valor" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary"
                       placeholder="Ex: 95%, 12 min, 100">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data da Medição</label>
                <input type="date" id="data" name="data" required 
                       value="<?= date('Y-m-d') ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Observações (opcional)</label>
                <textarea id="observacoes" name="observacoes" rows="2"
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

<script>
function abrirModalRegistrar(kpiId, kpiNome) {
    document.getElementById('kpi_id').value = kpiId;
    document.getElementById('modal-titulo').textContent = `Registrar Valor: ${kpiNome}`;
    document.getElementById('modal-registrar').classList.remove('hidden');
    document.getElementById('valor').focus();
}

function fecharModalRegistrar() {
    document.getElementById('modal-registrar').classList.add('hidden');
    document.getElementById('form-registrar').reset();
}

document.getElementById('form-registrar').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('csrf_token', '<?= Csrf::token() ?>');
    
    try {
        const res = await fetch('<?= APP_URL ?>/kpis/registrar', { method: 'POST', body: formData });
        const data = await res.json();
        
        if (data.sucesso) {
            alert(`Valor registrado! Zona: ${data.zona}${data.alerta_criado ? ' (Alerta criado)' : ''}`);
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