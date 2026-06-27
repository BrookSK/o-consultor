<?php 
// View: Planos de Contingência N1/N2/N3 (carregada via AJAX)
$nivelSugerido = $_GET['nivel'] ?? 'N1';
?>

<!-- Planos de Contingência N1, N2, N3 -->
<div class="space-y-6">
    <!-- N1 - Contingência Operacional -->
    <div class="border-l-4 border-l-blue-500 bg-blue-50/50 rounded-r-lg p-5 border border-blue-200 <?= $nivelSugerido === 'N1' ? 'ring-2 ring-blue-300' : '' ?>">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 rounded text-xs font-bold bg-blue-600 text-white">N1</span>
                <span class="text-sm font-semibold text-blue-800">Contingência Operacional</span>
                <?= $nivelSugerido === 'N1' ? '<span class="px-2 py-0.5 bg-yellow-100 text-yellow-800 text-xs rounded">⚠️ Nível Sugerido</span>' : '' ?>
            </div>
            <button onclick="acionarContencao('<?= $sop['id'] ?>', 'N1')" class="px-3 py-1.5 bg-blue-600 text-white rounded text-xs font-medium hover:bg-blue-700">
                🚀 Acionar N1
            </button>
        </div>
        
        <div class="space-y-3 text-sm">
            <div>
                <h5 class="font-medium text-blue-700 mb-1">🎯 Situação que ativa:</h5>
                <p class="text-blue-800 bg-blue-100 p-2 rounded"><?= htmlspecialchars($planos['n1']['situacao']) ?></p>
            </div>
            
            <div>
                <h5 class="font-medium text-blue-700 mb-1">⚡ Ação imediata (5+ passos):</h5>
                <div class="bg-blue-100 p-3 rounded font-mono text-xs">
                    <?= nl2br(htmlspecialchars($planos['n1']['acao'])) ?>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <h5 class="font-medium text-blue-700 mb-1">👤 Responsável + Prazo:</h5>
                    <p class="text-blue-800"><?= htmlspecialchars($planos['n1']['quem']) ?></p>
                </div>
                <div>
                    <h5 class="font-medium text-blue-700 mb-1">🔄 Escalar para N2 se:</h5>
                    <p class="text-blue-800"><?= htmlspecialchars($planos['n1']['escalar']) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- N2 - Contingência Gerencial -->
    <div class="border-l-4 border-l-yellow-500 bg-yellow-50/50 rounded-r-lg p-5 border border-yellow-200 <?= $nivelSugerido === 'N2' ? 'ring-2 ring-yellow-300' : '' ?>">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 rounded text-xs font-bold bg-yellow-600 text-white">N2</span>
                <span class="text-sm font-semibold text-yellow-800">Contingência Gerencial</span>
                <?= $nivelSugerido === 'N2' ? '<span class="px-2 py-0.5 bg-yellow-100 text-yellow-800 text-xs rounded">⚠️ Nível Sugerido</span>' : '' ?>
            </div>
            <button onclick="acionarContencao('<?= $sop['id'] ?>', 'N2')" class="px-3 py-1.5 bg-yellow-600 text-white rounded text-xs font-medium hover:bg-yellow-700">
                🚀 Acionar N2
            </button>
        </div>
        
        <div class="space-y-3 text-sm">
            <div>
                <h5 class="font-medium text-yellow-700 mb-1">🎯 Situação que ativa:</h5>
                <p class="text-yellow-800 bg-yellow-100 p-2 rounded"><?= htmlspecialchars($planos['n2']['situacao']) ?></p>
            </div>
            
            <div>
                <h5 class="font-medium text-yellow-700 mb-1">⚡ Ação gerencial (5+ passos):</h5>
                <div class="bg-yellow-100 p-3 rounded font-mono text-xs">
                    <?= nl2br(htmlspecialchars($planos['n2']['acao'])) ?>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <h5 class="font-medium text-yellow-700 mb-1">👤 Responsável + Prazo:</h5>
                    <p class="text-yellow-800"><?= htmlspecialchars($planos['n2']['quem']) ?></p>
                </div>
                <div>
                    <h5 class="font-medium text-yellow-700 mb-1">🔄 Escalar para N3 se:</h5>
                    <p class="text-yellow-800"><?= htmlspecialchars($planos['n2']['escalar']) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- N3 - Contingência Executiva/Jurídica -->
    <div class="border-l-4 border-l-red-500 bg-red-50/50 rounded-r-lg p-5 border border-red-200 <?= $nivelSugerido === 'N3' ? 'ring-2 ring-red-300' : '' ?>">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 rounded text-xs font-bold bg-red-600 text-white">N3</span>
                <span class="text-sm font-semibold text-red-800">Contingência Executiva/Jurídica</span>
                <?= $nivelSugerido === 'N3' ? '<span class="px-2 py-0.5 bg-yellow-100 text-yellow-800 text-xs rounded">⚠️ Nível Sugerido</span>' : '' ?>
            </div>
            <button onclick="acionarContencao('<?= $sop['id'] ?>', 'N3')" class="px-3 py-1.5 bg-red-600 text-white rounded text-xs font-medium hover:bg-red-700">
                🚀 Acionar N3
            </button>
        </div>
        
        <div class="space-y-3 text-sm">
            <div>
                <h5 class="font-medium text-red-700 mb-1">🎯 Situação crítica que ativa:</h5>
                <p class="text-red-800 bg-red-100 p-2 rounded"><?= htmlspecialchars($planos['n3']['situacao']) ?></p>
            </div>
            
            <div>
                <h5 class="font-medium text-red-700 mb-1">⚡ Ação executiva + jurídica:</h5>
                <div class="bg-red-100 p-3 rounded font-mono text-xs">
                    <?= nl2br(htmlspecialchars($planos['n3']['acao'])) ?>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <h5 class="font-medium text-red-700 mb-1">👤 Responsáveis:</h5>
                    <p class="text-red-800"><?= htmlspecialchars($planos['n3']['quem']) ?></p>
                </div>
                <div>
                    <h5 class="font-medium text-red-700 mb-1">📢 Comunicação externa:</h5>
                    <p class="text-red-800"><?= htmlspecialchars($planos['n3']['comunicacao'] ?? 'Comunicação definida caso a caso') ?></p>
                </div>
            </div>
            
            <div>
                <h5 class="font-medium text-red-700 mb-1">📋 Documentação obrigatória:</h5>
                <p class="text-red-800 bg-red-100 p-2 rounded"><?= htmlspecialchars($planos['n3']['documentacao'] ?? 'Toda documentação de impacto, ações tomadas e comunicações oficiais') ?></p>
            </div>

            <!-- Contatos Jurídicos -->
            <?php if (!empty($planos['n3']['advogado_responsavel'])): ?>
            <div>
                <h5 class="font-medium text-red-700 mb-1">⚖️ Advogado responsável:</h5>
                <p class="text-red-800"><?= htmlspecialchars($planos['n3']['advogado_responsavel']) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Histórico de Acionamentos (se houver) -->
<?php if (!empty($historico_contencao)): ?>
<div class="mt-6 pt-6 border-t border-gray-200">
    <h4 class="text-md font-semibold text-gray-800 mb-4">📋 Histórico de Acionamentos</h4>
    <div class="space-y-3">
        <?php foreach ($historico_contencao as $ocorrencia): ?>
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="px-2 py-0.5 rounded text-xs font-bold <?= $ocorrencia['nivel'] === 'N3' ? 'bg-red-600 text-white' : ($ocorrencia['nivel'] === 'N2' ? 'bg-yellow-600 text-white' : 'bg-blue-600 text-white') ?>">
                            <?= $ocorrencia['nivel'] ?>
                        </span>
                        <span class="text-sm font-medium text-gray-800"><?= htmlspecialchars($ocorrencia['responsavel_execucao']) ?></span>
                        <span class="text-xs text-gray-500"><?= date('d/m/Y H:i', strtotime($ocorrencia['data_inicio'])) ?></span>
                    </div>
                    <p class="text-sm text-gray-700"><?= htmlspecialchars($ocorrencia['situacao_detectada']) ?></p>
                    <?php if ($ocorrencia['status'] === 'resolvido' && $ocorrencia['resolucao_final']): ?>
                    <p class="text-sm text-green-700 mt-1"><strong>Resolução:</strong> <?= htmlspecialchars($ocorrencia['resolucao_final']) ?></p>
                    <?php endif; ?>
                </div>
                <span class="px-2 py-0.5 rounded text-xs font-medium <?= $ocorrencia['status'] === 'resolvido' ? 'bg-green-100 text-green-700' : ($ocorrencia['status'] === 'em_andamento' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700') ?>">
                    <?= ucfirst($ocorrencia['status']) ?>
                </span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<script>
async function acionarContencao(sopId, nivel) {
    if (!confirm(`Confirma o acionamento do plano de contingência ${nivel}? Isso criará um registro de ocorrência.`)) return;
    
    const situacao = prompt(`Descreva brevemente a situação detectada (${nivel}):`);
    if (!situacao) return;
    
    const formData = new FormData();
    formData.append('csrf_token', '<?= Csrf::token() ?>');
    formData.append('sop_id', sopId);
    formData.append('nivel', nivel);
    formData.append('situacao_detectada', situacao);
    
    try {
        const res = await fetch('<?= APP_URL ?>/contencao/acionar', { method: 'POST', body: formData });
        const data = await res.json();
        
        if (data.sucesso) {
            alert('Contingência acionada! Registro criado no histórico.');
            // Recarregar a modal para mostrar a nova ocorrência
            location.reload();
        } else {
            alert(data.erro || 'Erro ao acionar contingência.');
        }
    } catch (e) {
        alert('Erro de conexão.');
    }
}
</script>