<?php $tituloPagina = 'SOP - ' . $sop['titulo']; ?>
<?php ob_start(); ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li><a href="<?= APP_URL ?>/manual-operacional" class="hover:text-primary">Manual Operacional</a></li>
        <li>/</li>
        <li class="font-medium text-primary"><?= htmlspecialchars($sop['sop_codigo']) ?></li>
    </ol>
</nav>

<!-- Header do SOP -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
    <div class="flex flex-col md:flex-row items-start justify-between gap-4">
        <div class="flex-1">
            <div class="flex items-center gap-2 mb-2">
                <span class="px-2 py-0.5 rounded text-xs font-mono font-bold text-primary bg-primary/10"><?= htmlspecialchars($sop['sop_codigo']) ?></span>
                <span class="px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">v<?= $sop['versao'] ?></span>
                <span class="px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">✓ Aprovado</span>
                <span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700"><?= htmlspecialchars($sop['norma']) ?></span>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-1"><?= htmlspecialchars($sop['titulo']) ?></h1>
            <p class="text-sm text-gray-500"><?= htmlspecialchars($sop['empresa']) ?> — <?= htmlspecialchars($sop['setor']) ?></p>
            <p class="text-sm text-gray-700 mt-2"><?= htmlspecialchars($sop['objetivo']) ?></p>
        </div>
        <div class="flex gap-2 flex-shrink-0">
            <button onclick="exportarPDF('<?= $sop['id'] ?>')" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition">📄 Exportar PDF</button>
            <?php if (Auth::isAdmin() || Auth::isConsultor()): ?>
            <a href="<?= APP_URL ?>/sop/revisar?id=<?= $sop['id'] ?>" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">✏️ Editar</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Alertas Ativos (se houver) -->
<?php if (!empty($alertas_ativos)): ?>
<div class="mb-6">
    <?php foreach ($alertas_ativos as $alerta): ?>
    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-3 rounded-r-lg">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <h4 class="text-sm font-semibold text-red-800"><?= htmlspecialchars($alerta['titulo']) ?></h4>
                <p class="text-sm text-red-700 mt-1"><?= htmlspecialchars($alerta['descricao']) ?></p>
                <p class="text-xs text-red-600 mt-1">Criado em: <?= date('d/m/Y H:i', strtotime($alerta['data_criacao'])) ?></p>
            </div>
            <button onclick="abrirPlanoContencao('<?= $sop['id'] ?>', '<?= $alerta['nivel_sugerido'] ?>')" 
                    class="px-3 py-1 bg-red-600 text-white rounded text-xs font-medium hover:bg-red-700">
                Ver Plano de Contingência
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Escopo e Aplicabilidade -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">📋 Escopo e Aplicabilidade</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-green-800 mb-2">✓ Aplica-se a:</h4>
            <p class="text-sm text-green-700"><?= htmlspecialchars($sop['escopo_aplica']) ?></p>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-red-800 mb-2">✗ Não se aplica a:</h4>
            <p class="text-sm text-red-700"><?= htmlspecialchars($sop['escopo_nao_aplica']) ?></p>
        </div>
    </div>
</div>

<!-- Subtópicos do SOP (F-06 Core) -->
<?php foreach ($sop['subtopicos_completos'] as $index => $subtopico): ?>
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
    <!-- Header do Subtópico -->
    <div class="bg-gradient-to-r from-primary/5 to-primary/10 px-6 py-4 border-b border-gray-200">
        <div class="flex items-center gap-3">
            <span class="w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center text-sm font-bold"><?= $subtopico['letra'] ?></span>
            <div>
                <h3 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($subtopico['nome']) ?></h3>
                <p class="text-sm text-gray-600"><?= htmlspecialchars($subtopico['descricao']) ?></p>
            </div>
        </div>
    </div>

    <!-- Procedimentos do Subtópico -->
    <div class="p-6">
        <h4 class="text-md font-semibold text-gray-800 mb-4">🔧 Procedimentos Operacionais</h4>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 w-12">#</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500">Ação Detalhada</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 w-28">Responsável</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 w-20">Prazo</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 w-24">Sistema</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-500 w-36">Validação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($subtopico['procedimentos'] as $proc): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-3 text-center font-bold text-primary"><?= $proc['passo'] ?></td>
                        <td class="px-3 py-3 text-gray-700"><?= htmlspecialchars($proc['acao']) ?></td>
                        <td class="px-3 py-3 text-gray-600 text-xs"><?= htmlspecialchars($proc['responsavel']) ?></td>
                        <td class="px-3 py-3 text-gray-600 text-xs"><?= htmlspecialchars($proc['prazo']) ?></td>
                        <td class="px-3 py-3 text-gray-600 text-xs"><?= htmlspecialchars($proc['sistema']) ?></td>
                        <td class="px-3 py-3 text-gray-500 text-xs"><?= htmlspecialchars($proc['validacao']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Checklist do Subtópico -->
        <?php if (!empty($subtopico['checklist'])): ?>
        <div class="mt-6">
            <h5 class="text-sm font-semibold text-gray-700 mb-3">☑️ Checklist — Subtópico <?= $subtopico['letra'] ?></h5>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                <?php foreach ($subtopico['checklist'] as $item): ?>
                <label class="flex items-start gap-2 p-2 rounded hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" class="w-4 h-4 mt-0.5 text-primary rounded border-gray-300">
                    <span class="text-sm text-gray-700"><?= htmlspecialchars($item) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Evidências Específicas -->
        <?php if (!empty($subtopico['evidencias'])): ?>
        <div class="mt-6">
            <h5 class="text-sm font-semibold text-gray-700 mb-3">📎 Evidências Obrigatórias — Subtópico <?= $subtopico['letra'] ?></h5>
            <ul class="space-y-1">
                <?php foreach ($subtopico['evidencias'] as $i => $evidencia): ?>
                <li class="flex items-start gap-2 text-sm text-gray-700">
                    <span class="font-bold text-primary"><?= $subtopico['letra'] ?>.<?= $i + 1 ?>.</span>
                    <span><?= htmlspecialchars($evidencia) ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>

<!-- KPIs do SOP -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">📊 KPIs Nativos</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-3 py-2 font-medium text-gray-500">KPI</th>
                    <th class="text-left px-3 py-2 font-medium text-gray-500">Valor Atual</th>
                    <th class="text-left px-3 py-2 font-medium text-gray-500">Meta</th>
                    <th class="text-left px-3 py-2 font-medium text-gray-500">Zona</th>
                    <th class="text-left px-3 py-2 font-medium text-gray-500">Ação se Vermelha</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($sop['kpis'] as $kpi): ?>
                <tr>
                    <td class="px-3 py-2 font-medium text-gray-800"><?= htmlspecialchars($kpi['nome']) ?></td>
                    <td class="px-3 py-2">
                        <span class="<?= $kpi['zona_atual'] === 'vermelha' ? 'text-red-600 font-bold' : ($kpi['zona_atual'] === 'amarela' ? 'text-yellow-600' : 'text-green-600') ?>">
                            <?= htmlspecialchars($kpi['valor_atual'] ?? 'Não medido') ?>
                        </span>
                    </td>
                    <td class="px-3 py-2 text-gray-600">
                        <div class="text-xs">
                            <div class="text-green-700">🟢 <?= htmlspecialchars($kpi['meta_verde']) ?></div>
                            <div class="text-yellow-700">🟡 <?= htmlspecialchars($kpi['meta_amarela']) ?></div>
                            <div class="text-red-700">🔴 <?= htmlspecialchars($kpi['meta_vermelha']) ?></div>
                        </div>
                    </td>
                    <td class="px-3 py-2">
                        <?php 
                        $zonaClass = match($kpi['zona_atual'] ?? 'verde') {
                            'vermelha' => 'bg-red-100 text-red-700',
                            'amarela' => 'bg-yellow-100 text-yellow-700',
                            default => 'bg-green-100 text-green-700'
                        };
                        ?>
                        <span class="px-2 py-1 rounded text-xs font-medium <?= $zonaClass ?>"><?= ucfirst($kpi['zona_atual'] ?? 'Verde') ?></span>
                    </td>
                    <td class="px-3 py-2 text-gray-600 text-xs"><?= htmlspecialchars($kpi['acao_vermelha']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Responsáveis RACI -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">👥 Matriz de Responsabilidades</h3>
    <table class="w-full text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="text-left px-3 py-2 font-medium text-gray-500">Papel</th>
                <th class="text-left px-3 py-2 font-medium text-gray-500">Cargo</th>
                <th class="text-left px-3 py-2 font-medium text-gray-500">Função</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php foreach ($sop['responsaveis'] as $resp): ?>
            <tr>
                <td class="px-3 py-2 font-medium text-gray-800"><?= htmlspecialchars($resp['papel']) ?></td>
                <td class="px-3 py-2 text-gray-700"><?= htmlspecialchars($resp['cargo']) ?></td>
                <td class="px-3 py-2 text-gray-600 text-xs"><?= $this->getRaciFuncao($resp['papel']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal de Plano de Contingência -->
<div id="modal-contencao" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">🚨 Planos de Contingência</h3>
                <button onclick="fecharModalContencao()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        <div id="conteudo-contencao" class="p-6">
            <!-- Conteúdo carregado via AJAX -->
        </div>
    </div>
</div>

<script>
async function abrirPlanoContencao(sopId, nivelSugerido) {
    const modal = document.getElementById('modal-contencao');
    const conteudo = document.getElementById('conteudo-contencao');
    
    modal.classList.remove('hidden');
    conteudo.innerHTML = '<div class="text-center py-8"><div class="animate-spin w-8 h-8 border-4 border-gray-200 border-t-primary rounded-full mx-auto mb-2"></div><p class="text-sm text-gray-600">Carregando planos de contingência...</p></div>';
    
    try {
        const res = await fetch(`<?= APP_URL ?>/sop/contencao/${sopId}?nivel=${nivelSugerido}`);
        const html = await res.text();
        conteudo.innerHTML = html;
    } catch (e) {
        conteudo.innerHTML = '<div class="text-center py-8 text-red-600">Erro ao carregar planos de contingência.</div>';
    }
}

function fecharModalContencao() {
    document.getElementById('modal-contencao').classList.add('hidden');
}

async function exportarPDF(sopId) {
    try {
        const res = await fetch(`<?= APP_URL ?>/sop/exportar-pdf/${sopId}`);
        const blob = await res.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `SOP-${sopId}.pdf`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    } catch (e) {
        alert('Erro ao exportar PDF.');
    }
}

// Fechar modal clicando fora
document.getElementById('modal-contencao').addEventListener('click', function(e) {
    if (e.target === this) {
        fecharModalContencao();
    }
});
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>

<?php
// Helper method for RACI function description
function getRaciFuncao(string $papel): string {
    return match(strtolower($papel)) {
        'executor' => 'Responsável por executar as tarefas',
        'aprovador' => 'Aprova decisões e resultados finais', 
        'supervisor' => 'Supervisiona execução e escalações',
        'informado' => 'Recebe atualizações de status',
        'consultor' => 'Fornece expertise técnica',
        'substituto' => 'Backup em caso de indisponibilidade',
        default => 'Participa do processo'
    };
}
?>