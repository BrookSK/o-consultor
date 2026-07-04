<?php $tituloPagina = 'Painel de KPIs'; ?>
<?php ob_start(); ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li><a href="<?= APP_URL ?>/manual-operacional" class="hover:text-primary">Manual Operacional</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Painel de KPIs</li>
    </ol>
</nav>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">📈 Painel de KPIs</h1>
        <p class="text-gray-500 mt-1">KPIs nativos agregados de todos os SOPs aprovados.</p>
    </div>
    <button onclick="document.getElementById('modal-registrar-kpi').classList.remove('hidden')"
            class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-700 transition">
        + Registrar Valor
    </button>
</div>

<!-- Tabela de KPIs -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-8">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 font-medium text-gray-500">KPI</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500">SOP</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500">Atual</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500">Meta</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500">Zona</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500">Freq.</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500">Responsável</th>
                    <th class="text-left px-4 py-3 font-medium text-gray-500">Ação</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($dados['kpis'] as $kpi):
                    switch($kpi['zona']) {
                        case 'verde':
                            $zonaBadge = 'bg-green-100 text-green-700';
                            break;
                        case 'amarela':
                            $zonaBadge = 'bg-yellow-100 text-yellow-700';
                            break;
                        case 'vermelha':
                            $zonaBadge = 'bg-red-100 text-red-700';
                            break;
                        default:
                            $zonaBadge = 'bg-gray-100 text-gray-600';
                            break;
                    }
                    switch($kpi['zona']) {
                        case 'verde':
                            $zonaIcon = '●';
                            break;
                        case 'amarela':
                            $zonaIcon = '◎';
                            break;
                        case 'vermelha':
                            $zonaIcon = '⚠';
                            break;
                        default:
                            $zonaIcon = '○';
                            break;
                    }
                ?>
                <tr class="hover:bg-gray-50 <?= $kpi['zona'] === 'vermelha' ? 'bg-red-50/30' : '' ?>">
                    <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars($kpi['kpi']) ?></td>
                    <td class="px-4 py-3"><span class="text-xs font-mono text-gray-500"><?= htmlspecialchars($kpi['sop']) ?></span></td>
                    <td class="px-4 py-3 font-semibold text-gray-800"><?= htmlspecialchars($kpi['atual']) ?></td>
                    <td class="px-4 py-3 text-gray-600 text-xs"><?= htmlspecialchars($kpi['meta_verde']) ?></td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold <?= $zonaBadge ?>"><?= $zonaIcon ?> <?= ucfirst($kpi['zona']) ?></span>
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs"><?= htmlspecialchars($kpi['frequencia']) ?></td>
                    <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($kpi['responsavel']) ?></td>
                    <td class="px-4 py-3">
                        <?php if ($kpi['zona'] === 'vermelha'): ?>
                        <button onclick="alert('Em produção: abre modal com plano de contingência N1/N2/N3 do SOP.')" class="text-xs px-2 py-1 bg-red-600 text-white rounded font-medium hover:bg-red-700">Ver Contingência</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Gráfico de Tendência -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
    <h3 class="font-semibold text-gray-800 mb-4">Tendência — SLA de Chamados</h3>
    <canvas id="chart-kpi-tendencia" height="100"></canvas>
</div>

<!-- Modal Registrar KPI -->
<div id="modal-registrar-kpi" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">Registrar Valor de KPI</h3>
            <button onclick="document.getElementById('modal-registrar-kpi').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form id="form-kpi" class="p-6 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">KPI</label>
                <select name="kpi_nome" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                    <?php foreach ($dados['kpis'] as $kpi): ?>
                    <option value="<?= htmlspecialchars($kpi['kpi']) ?>"><?= htmlspecialchars($kpi['kpi']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Valor medido</label>
                <input type="text" name="valor" required placeholder="Ex: 94%, 12 min, etc." class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data da medição</label>
                <input type="date" name="data_medicao" value="<?= date('Y-m-d') ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('modal-registrar-kpi').classList.add('hidden')" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-700">Registrar</button>
            </div>
        </form>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.getElementById('form-kpi').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    try {
        const res = await fetch('<?= APP_URL ?>/manual-operacional/kpi-registrar', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.sucesso) {
            document.getElementById('modal-registrar-kpi').classList.add('hidden');
            alert(data.mensagem);
        }
    } catch(e) { alert('Erro.'); }
});

// Gráfico de tendência
const ctx = document.getElementById('chart-kpi-tendencia').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Sem 1', 'Sem 2', 'Sem 3', 'Sem 4', 'Sem 5', 'Sem 6', 'Sem 7', 'Sem 8'],
        datasets: [{
            label: 'SLA Chamados (%)',
            data: [88, 91, 89, 93, 95, 92, 94, 94],
            borderColor: '#1E3A5F',
            backgroundColor: 'rgba(30,58,95,0.1)',
            fill: true,
            tension: 0.3,
            pointBackgroundColor: '#1E3A5F'
        }, {
            label: 'Meta (95%)',
            data: [95, 95, 95, 95, 95, 95, 95, 95],
            borderColor: '#1a7a1a',
            borderDash: [5, 5],
            pointRadius: 0,
            fill: false
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } },
        scales: { y: { min: 80, max: 100, grid: { color: '#f3f4f6' } }, x: { grid: { display: false } } }
    }
});
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
