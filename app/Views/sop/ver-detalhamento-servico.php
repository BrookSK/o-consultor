<?php 
$tituloPagina = $dados['titulo_pagina'] ?? 'Detalhamento de Serviço'; 
$detalhamento = $dados['detalhamento'];
$data = $dados['detalhamento_data'];
?>
<?php ob_start(); ?>

<div class="max-w-6xl mx-auto">
    <!-- Cabeçalho -->
    <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-bold text-gray-800">📋 Detalhamento de Serviço</h1>
            <div class="flex space-x-3">
                <a href="<?= APP_URL ?>/sop/gerar-sop-individual?servico_nome=<?= urlencode($detalhamento['servico_nome']) ?>&estrutura_id=<?= $detalhamento['estrutura_id'] ?>" 
                   class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    🔧 Gerar SOP Completo
                </a>
                <button onclick="window.print()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    🖨️ Imprimir
                </button>
            </div>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div><span class="text-gray-500">Serviço:</span> <span class="font-medium"><?= htmlspecialchars($detalhamento['servico_nome']) ?></span></div>
            <div><span class="text-gray-500">Setor:</span> <span class="font-medium"><?= htmlspecialchars($detalhamento['setor_nome']) ?></span></div>
            <div><span class="text-gray-500">Criado em:</span> <span class="font-medium"><?= date('d/m/Y H:i', strtotime($detalhamento['criado_em'])) ?></span></div>
            <div><span class="text-gray-500">ID:</span> <span class="font-mono">#<?= $detalhamento['id'] ?></span></div>
        </div>
    </div>
    
    <?php if ($data): ?>
    
    <!-- Descrição Geral -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
        <h2 class="text-lg font-semibold text-blue-900 mb-3">🎯 Visão Geral</h2>
        <div class="space-y-3">
            <p class="text-blue-800"><?= htmlspecialchars($data['descricao_completa'] ?? '') ?></p>
            <div class="text-sm text-blue-700"><strong>Objetivo:</strong> <?= htmlspecialchars($data['objetivo_principal'] ?? '') ?></div>
        </div>
    </div>
    
    <!-- Processos -->
    <?php if (!empty($data['processos'])): ?>
    <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">⚙️ Processos (<?= count($data['processos']) ?>)</h2>
        <div class="space-y-4">
            <?php foreach ($data['processos'] as $index => $processo): ?>
            <div class="border border-gray-200 rounded-lg p-4">
                <div class="flex items-start justify-between mb-2">
                    <h3 class="font-medium text-gray-800"><?= htmlspecialchars($processo['nome'] ?? '') ?></h3>
                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded"><?= htmlspecialchars($processo['tempo_estimado'] ?? '') ?></span>
                </div>
                <p class="text-gray-600 text-sm mb-3"><?= htmlspecialchars($processo['descricao'] ?? '') ?></p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                    <div><span class="text-gray-500">Responsável:</span> <?= htmlspecialchars($processo['responsavel'] ?? '') ?></div>
                    <div><span class="text-gray-500">Recursos:</span> <?= htmlspecialchars(implode(', ', $processo['recursos_necessarios'] ?? [])) ?></div>
                    <div><span class="text-gray-500">Indicadores:</span> <?= htmlspecialchars(implode(', ', $processo['indicadores'] ?? [])) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Cenários de Problemas -->
    <?php if (!empty($data['cenarios_problemas'])): ?>
    <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">🚨 Cenários de Problemas e Contenção (<?= count($data['cenarios_problemas']) ?>)</h2>
        <div class="space-y-6">
            <?php foreach ($data['cenarios_problemas'] as $index => $cenario): ?>
            <div class="border border-red-200 rounded-lg p-4 bg-red-50">
                <div class="flex items-start justify-between mb-3">
                    <h3 class="font-medium text-red-900"><?= htmlspecialchars($cenario['problema'] ?? '') ?></h3>
                    <div class="flex space-x-2">
                        <span class="text-xs px-2 py-1 rounded <?= ($cenario['frequencia'] ?? '') === 'alta' ? 'bg-red-100 text-red-700' : (($cenario['frequencia'] ?? '') === 'média' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700') ?>">
                            Freq: <?= ucfirst($cenario['frequencia'] ?? '') ?>
                        </span>
                        <span class="text-xs px-2 py-1 rounded <?= ($cenario['impacto'] ?? '') === 'alto' ? 'bg-red-100 text-red-700' : (($cenario['impacto'] ?? '') === 'médio' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700') ?>">
                            Impact: <?= ucfirst($cenario['impacto'] ?? '') ?>
                        </span>
                    </div>
                </div>
                
                <!-- Níveis de Contenção -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                    <!-- N1 -->
                    <div class="border border-orange-200 rounded-lg p-3 bg-orange-50">
                        <h4 class="font-medium text-orange-900 mb-2">🟡 N1 - Contenção Imediata</h4>
                        <div class="text-xs text-orange-800 mb-2"><strong>Tempo:</strong> <?= htmlspecialchars($cenario['n1_contencao']['tempo_limite'] ?? '') ?></div>
                        <div class="text-xs text-orange-800 mb-2"><strong>Responsável:</strong> <?= htmlspecialchars($cenario['n1_contencao']['responsavel'] ?? '') ?></div>
                        <ul class="text-xs text-orange-700 space-y-1">
                            <?php foreach ($cenario['n1_contencao']['acoes'] ?? [] as $acao): ?>
                            <li>• <?= htmlspecialchars($acao) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <!-- N2 -->
                    <div class="border border-yellow-200 rounded-lg p-3 bg-yellow-50">
                        <h4 class="font-medium text-yellow-900 mb-2">🟠 N2 - Escalação</h4>
                        <div class="text-xs text-yellow-800 mb-2"><strong>Tempo:</strong> <?= htmlspecialchars($cenario['n2_escalacao']['tempo_limite'] ?? '') ?></div>
                        <div class="text-xs text-yellow-800 mb-2"><strong>Responsável:</strong> <?= htmlspecialchars($cenario['n2_escalacao']['responsavel'] ?? '') ?></div>
                        <ul class="text-xs text-yellow-700 space-y-1">
                            <?php foreach ($cenario['n2_escalacao']['acoes'] ?? [] as $acao): ?>
                            <li>• <?= htmlspecialchars($acao) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <!-- N3 -->
                    <div class="border border-red-200 rounded-lg p-3 bg-red-100">
                        <h4 class="font-medium text-red-900 mb-2">🔴 N3 - Emergência</h4>
                        <div class="text-xs text-red-800 mb-2"><strong>Tempo:</strong> <?= htmlspecialchars($cenario['n3_emergencia']['tempo_limite'] ?? '') ?></div>
                        <div class="text-xs text-red-800 mb-2"><strong>Responsável:</strong> <?= htmlspecialchars($cenario['n3_emergencia']['responsavel'] ?? '') ?></div>
                        <ul class="text-xs text-red-700 space-y-1">
                            <?php foreach ($cenario['n3_emergencia']['acoes'] ?? [] as $acao): ?>
                            <li>• <?= htmlspecialchars($acao) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Fluxo de Trabalho -->
    <?php if (!empty($data['fluxo_trabalho'])): ?>
    <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">🔄 Fluxo de Trabalho</h2>
        
        <div class="mb-4">
            <div class="text-sm"><span class="font-medium text-green-700">Entrada:</span> <?= htmlspecialchars($data['fluxo_trabalho']['entrada'] ?? '') ?></div>
            <div class="text-sm mt-2"><span class="font-medium text-blue-700">Saída:</span> <?= htmlspecialchars($data['fluxo_trabalho']['saida'] ?? '') ?></div>
        </div>
        
        <?php if (!empty($data['fluxo_trabalho']['etapas'])): ?>
        <div class="space-y-3">
            <?php foreach ($data['fluxo_trabalho']['etapas'] as $etapa): ?>
            <div class="flex items-start space-x-3 p-3 border border-gray-200 rounded-lg">
                <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-medium">
                    <?= $etapa['ordem'] ?? '' ?>
                </div>
                <div class="flex-1">
                    <h4 class="font-medium text-gray-800"><?= htmlspecialchars($etapa['nome'] ?? '') ?></h4>
                    <p class="text-gray-600 text-sm mt-1"><?= htmlspecialchars($etapa['descricao'] ?? '') ?></p>
                    <div class="flex space-x-4 text-xs text-gray-500 mt-2">
                        <span><strong>Tempo:</strong> <?= htmlspecialchars($etapa['tempo'] ?? '') ?></span>
                        <span><strong>Validação:</strong> <?= htmlspecialchars($etapa['validacao'] ?? '') ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- Controle de Qualidade -->
    <?php if (!empty($data['qualidade_controle'])): ?>
    <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">✅ Controle de Qualidade</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <h4 class="font-medium text-gray-700 mb-2">Critérios</h4>
                <ul class="text-sm text-gray-600 space-y-1">
                    <?php foreach ($data['qualidade_controle']['criterios'] ?? [] as $criterio): ?>
                    <li>• <?= htmlspecialchars($criterio) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div>
                <h4 class="font-medium text-gray-700 mb-2">Checklist</h4>
                <ul class="text-sm text-gray-600 space-y-1">
                    <?php foreach ($data['qualidade_controle']['checklist'] ?? [] as $item): ?>
                    <li>☐ <?= htmlspecialchars($item) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div>
                <h4 class="font-medium text-gray-700 mb-2">Métricas</h4>
                <ul class="text-sm text-gray-600 space-y-1">
                    <?php foreach ($data['qualidade_controle']['metricas'] ?? [] as $metrica): ?>
                    <li>📊 <?= htmlspecialchars($metrica) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php else: ?>
    <div class="bg-red-50 border border-red-200 rounded-lg p-6">
        <h2 class="text-lg font-semibold text-red-800 mb-2">❌ Erro nos Dados</h2>
        <p class="text-red-700">Não foi possível carregar os dados do detalhamento. O JSON pode estar corrompido.</p>
    </div>
    <?php endif; ?>
    
    <!-- Ações -->
    <div class="flex justify-between items-center mt-8 pt-6 border-t">
        <a href="javascript:history.back()" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
            ← Voltar
        </a>
        
        <div class="flex space-x-3">
            <a href="<?= APP_URL ?>/sop/gerar-sop-individual?servico_nome=<?= urlencode($detalhamento['servico_nome']) ?>&estrutura_id=<?= $detalhamento['estrutura_id'] ?>" 
               class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                🔧 Gerar SOP Completo
            </a>
        </div>
    </div>
</div>

<?php $conteudo = ob_get_clean(); ?>
<?php include VIEW_PATH . '/layouts/layout.php'; ?>