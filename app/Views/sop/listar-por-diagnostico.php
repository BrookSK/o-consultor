<?php $tituloPagina = 'SOPs Gerados - ' . $dados['empresa']['nome']; ?>
<?php ob_start(); ?>

<!-- Breadcrumb -->
<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li><a href="<?= APP_URL ?>/diagnostico" class="hover:text-primary">Diagnósticos</a></li>
        <li>/</li>
        <li><a href="<?= APP_URL ?>/diagnostico/resultado/<?= $dados['diagnostico']['id'] ?>" class="hover:text-primary">Resultado</a></li>
        <li>/</li>
        <li class="font-medium text-primary">SOPs Gerados</li>
    </ol>
</nav>

<!-- Header -->
<div class="mb-8">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">SOPs Gerados</h1>
            <p class="text-gray-600 mt-1">
                Procedimentos Operacionais Padrão gerados para <strong><?= htmlspecialchars($dados['empresa']['nome']) ?></strong>
                <?php if ($dados['usar_nova_arquitetura']): ?>
                    <span class="inline-block ml-2 px-2 py-1 bg-purple-100 text-purple-700 text-xs rounded-full">Nova Arquitetura ✨</span>
                <?php endif; ?>
            </p>
        </div>
        <div class="text-right">
            <div class="text-sm text-gray-500">Diagnóstico realizado em</div>
            <div class="font-semibold text-gray-800">
                <?= date('d/m/Y', strtotime($dados['diagnostico']['criado_em'])) ?>
            </div>
        </div>
    </div>

    <!-- Estatísticas -->
    <?php if ($dados['usar_nova_arquitetura']): ?>
    <!-- Nova Arquitetura - Estatísticas Avançadas -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-blue-600">Setores Mapeados</div>
                    <div class="text-2xl font-bold text-blue-700"><?= $dados['total_setores_mapeados'] ?></div>
                </div>
                <div class="text-blue-400">📂</div>
            </div>
        </div>

        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-purple-600">Serviços Detalhados</div>
                    <div class="text-2xl font-bold text-purple-700"><?= $dados['total_servicos_detalhados'] ?></div>
                </div>
                <div class="text-purple-400">🧠</div>
            </div>
        </div>

        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-red-600">Serviços Críticos</div>
                    <div class="text-2xl font-bold text-red-700"><?= $dados['estatisticas']['servicos_criticos'] ?></div>
                </div>
                <div class="text-red-400">⚠️</div>
            </div>
        </div>

        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-green-600">Progresso</div>
                    <div class="text-2xl font-bold text-green-700"><?= $dados['progresso']['progresso_percentual'] ?? 0 ?>%</div>
                </div>
                <div class="text-green-400">📊</div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Arquitetura Tradicional -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-blue-600">SOPs Tradicionais</div>
                    <div class="text-2xl font-bold text-blue-700"><?= $dados['total_sops_tradicional'] ?? 0 ?></div>
                </div>
                <div class="text-blue-400">📄</div>
            </div>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-gray-600">Nova Arquitetura</div>
                    <div class="text-2xl font-bold text-gray-700">-</div>
                </div>
                <div class="text-gray-400">🚫</div>
            </div>
        </div>

        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-green-600">Total de SOPs</div>
                    <div class="text-2xl font-bold text-green-700"><?= $dados['total_geral'] ?></div>
                </div>
                <div class="text-green-400">📋</div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($dados['usar_nova_arquitetura']): ?>
<!-- NOVA ARQUITETURA - SETORES MAPEADOS -->
<?php if (empty($dados['setores'])): ?>
<div class="text-center py-12">
    <div class="mx-auto w-24 h-24 bg-purple-100 rounded-full flex items-center justify-center mb-4">
        <span class="text-4xl">🧠</span>
    </div>
    <h3 class="text-lg font-semibold text-gray-700 mb-2">Nova Arquitetura em Progresso</h3>
    <p class="text-gray-500 mb-6">
        A estrutura foi criada mas ainda está sendo processada.<br>
        Volte em alguns minutos para ver os setores mapeados.
    </p>
    <a href="<?= APP_URL ?>/diagnostico/resultado/<?= $dados['diagnostico']['id'] ?>" 
       class="inline-flex items-center px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
        ← Voltar ao Resultado do Diagnóstico
    </a>
</div>
<?php else: ?>

<!-- Lista de Setores da Nova Arquitetura -->
<div class="space-y-8">
    <?php foreach ($dados['setores'] as $setorNome => $setor): ?>
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <!-- Header do Setor -->
        <div class="bg-gradient-to-r from-purple-50 to-blue-50 px-6 py-4 border-b">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">
                        📂 <?= htmlspecialchars($setorNome) ?>
                        <span class="ml-2 px-2 py-1 text-xs bg-purple-100 text-purple-700 rounded">
                            <?= ucfirst($setor['info']['setor_tipo']) ?>
                        </span>
                    </h2>
                    <div class="text-sm text-gray-600 mt-1">
                        <?= count($setor['servicos_mapeados']) ?> serviços mapeados • 
                        <?= count($setor['servicos_detalhados']) ?> detalhados
                    </div>
                </div>
                <span class="px-3 py-1 bg-blue-100 text-blue-600 text-sm rounded-full">
                    <?= $setor['info']['status'] === 'concluido' ? '✅ Concluído' : '🔄 Processando' ?>
                </span>
            </div>
        </div>

        <div class="p-6">
            <!-- Serviços Mapeados (Visão Geral) -->
            <?php if (!empty($setor['servicos_mapeados'])): ?>
            <div class="mb-6">
                <h3 class="text-md font-semibold text-gray-700 mb-3 flex items-center gap-2">
                    <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                    Serviços Identificados (<?= count($setor['servicos_mapeados']) ?>)
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                    <?php foreach ($setor['servicos_mapeados'] as $servico): ?>
                    <div class="text-xs p-2 bg-blue-50 border border-blue-200 rounded text-blue-700">
                        <?= htmlspecialchars($servico['nome'] ?? $servico) ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Serviços Detalhados -->
            <?php if (!empty($setor['servicos_detalhados'])): ?>
            <div>
                <h3 class="text-md font-semibold text-gray-700 mb-3 flex items-center gap-2">
                    <span class="w-2 h-2 bg-purple-500 rounded-full"></span>
                    Procedimentos Completos N1-N2-N3 (<?= count($setor['servicos_detalhados']) ?>)
                </h3>
                <div class="grid gap-3">
                    <?php foreach ($setor['servicos_detalhados'] as $servico): ?>
                    <div class="flex items-center justify-between p-4 border border-purple-200 bg-purple-50 rounded-lg hover:bg-purple-100 transition">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-1">
                                <h4 class="font-semibold text-purple-800"><?= htmlspecialchars($servico['servico_nome']) ?></h4>
                                
                                <!-- Badges de Criticidade -->
                                <?php 
                                switch($servico['criticidade']) {
                                    case 1:
                                        $critBadge = 'bg-red-100 text-red-700';
                                        break;
                                    case 2:
                                        $critBadge = 'bg-yellow-100 text-yellow-700';
                                        break;
                                    case 3:
                                        $critBadge = 'bg-blue-100 text-blue-700';
                                        break;
                                    default:
                                        $critBadge = 'bg-gray-100 text-gray-700';
                                        break;
                                }
                                switch($servico['criticidade']) {
                                    case 1:
                                        $critLabel = 'Crítico';
                                        break;
                                    case 2:
                                        $critLabel = 'Importante';
                                        break;
                                    case 3:
                                        $critLabel = 'Complementar';
                                        break;
                                    default:
                                        $critLabel = 'N/A';
                                        break;
                                }
                                ?>
                                <span class="px-2 py-1 text-xs font-medium <?= $critBadge ?> rounded">
                                    <?= $critLabel ?>
                                </span>
                                
                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-600 rounded">
                                    <?= $servico['problemas_mapeados'] ?? 0 ?> Problemas N1-N2-N3
                                </span>
                            </div>
                            <div class="text-sm text-purple-600">
                                <strong>Código:</strong> <?= htmlspecialchars($servico['servico_codigo']) ?> • 
                                <strong>Processado:</strong> <?= $servico['data_criacao_formatada'] ?>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button onclick="verDetalhamentoCompleto(<?= $servico['id'] ?>, '<?= htmlspecialchars($servico['servico_nome']) ?>')" 
                                    class="px-4 py-2 bg-purple-600 text-white text-sm rounded-lg hover:bg-purple-700 transition flex items-center gap-2">
                                🧠 Ver Detalhamento
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Nenhum serviço detalhado ainda -->
            <?php if (empty($setor['servicos_detalhados']) && !empty($setor['servicos_mapeados'])): ?>
            <div class="text-center py-6 bg-yellow-50 border border-yellow-200 rounded-lg">
                <div class="text-yellow-600 mb-2">⏳ Serviços mapeados, aguardando detalhamento</div>
                <div class="text-sm text-yellow-700">
                    Os serviços foram identificados mas ainda não foram processados individualmente.<br>
                    O detalhamento com problemas N1-N2-N3 será realizado em breve.
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php endif; ?>

<?php else: ?>
<!-- ARQUITETURA TRADICIONAL (FALLBACK) -->
<?php if (empty($dados['sops_por_departamento'])): ?>
<div class="text-center py-12">
    <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
    </div>
    <h3 class="text-lg font-semibold text-gray-700 mb-2">Nenhum SOP Gerado Ainda</h3>
    <p class="text-gray-500 mb-6">
        Ainda não foram gerados SOPs para este diagnóstico.<br>
        Use o botão <strong>"🧠 Manual Completo"</strong> na tela de resultado para gerar a nova arquitetura.
    </p>
    <a href="<?= APP_URL ?>/diagnostico/resultado/<?= $dados['diagnostico']['id'] ?>" 
       class="inline-flex items-center px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-700 transition">
        ← Voltar ao Resultado do Diagnóstico
    </a>
</div>
<?php else: ?>

<!-- SOPs Tradicionais -->
<div class="space-y-8">
    <?php foreach ($dados['sops_por_departamento'] as $departamento): ?>
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <!-- Header do Departamento -->
        <div class="bg-gray-50 px-6 py-4 border-b">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800">
                    📂 <?= htmlspecialchars($departamento['nome']) ?>
                </h2>
                <span class="px-3 py-1 bg-blue-100 text-blue-600 text-sm rounded-full">
                    <?= count($departamento['sops_tradicionais']) ?> SOPs Tradicionais
                </span>
            </div>
        </div>

        <div class="p-6">
            <div class="grid gap-3">
                <?php foreach ($departamento['sops_tradicionais'] as $sop): ?>
                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-1">
                            <h4 class="font-semibold text-gray-800"><?= htmlspecialchars($sop['titulo']) ?></h4>
                            <span class="px-2 py-1 text-xs font-medium rounded
                                <?php if ($sop['status'] === 'ativo'): ?>
                                    bg-green-100 text-green-600
                                <?php elseif ($sop['status'] === 'rascunho'): ?>
                                    bg-yellow-100 text-yellow-600
                                <?php else: ?>
                                    bg-gray-100 text-gray-600
                                <?php endif; ?>">
                                <?= $sop['status_formatado'] ?>
                            </span>
                        </div>
                        <div class="text-sm text-gray-500">
                            <strong>Código:</strong> <?= htmlspecialchars($sop['sop_codigo'] ?? 'N/A') ?> • 
                            <strong>Criado:</strong> <?= $sop['data_criacao_formatada'] ?>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="<?= APP_URL ?>/sop/ver?id=<?= $sop['id'] ?>" 
                           class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                            Ver SOP
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php endif; ?>
<?php endif; ?>

<!-- Botões de Ação -->
<div class="flex justify-between items-center mt-8 pt-6 border-t">
    <a href="<?= APP_URL ?>/diagnostico/resultado/<?= $dados['diagnostico']['id'] ?>" 
       class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 flex items-center gap-2">
        ← Voltar ao Resultado
    </a>
    
    <div class="flex gap-3">
        <?php if (!$dados['usar_nova_arquitetura']): ?>
        <a href="<?= APP_URL ?>/diagnostico/resultado/<?= $dados['diagnostico']['id'] ?>" 
           class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition flex items-center gap-2">
            🧠 Gerar Nova Arquitetura
        </a>
        <?php endif; ?>
        
        <?php if (($dados['usar_nova_arquitetura'] && !empty($dados['setores'])) || (!$dados['usar_nova_arquitetura'] && !empty($dados['sops_por_departamento']))): ?>
        <button onclick="exportarTodosSops()" 
                class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center gap-2">
            📥 Exportar Todos
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para Detalhamento da Nova Arquitetura -->
<div id="modal-detalhamento" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-5xl w-full max-h-[90vh] overflow-hidden">
        <div class="flex items-center justify-between p-6 border-b">
            <h3 class="text-lg font-semibold text-gray-800" id="modal-titulo-detalhamento">Detalhamento Completo</h3>
            <button onclick="fecharModalDetalhamento()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-80px)]" id="conteudo-modal-detalhamento">
            <!-- Conteúdo será inserido via JavaScript -->
        </div>
    </div>
</div>

<script>
// Ver detalhamento completo da Nova Arquitetura
async function verDetalhamentoCompleto(servicoId, servicoNome) {
    const modal = document.getElementById('modal-detalhamento');
    const titulo = document.getElementById('modal-titulo-detalhamento');
    const conteudo = document.getElementById('conteudo-modal-detalhamento');
    
    titulo.textContent = `Detalhamento: ${servicoNome}`;
    
    // Mostrar loading
    conteudo.innerHTML = `
        <div class="text-center py-8">
            <div class="inline-block w-8 h-8 border-4 border-gray-200 border-t-purple-600 rounded-full animate-spin mb-4"></div>
            <div class="text-gray-500">Carregando detalhamento completo...</div>
        </div>
    `;
    modal.classList.remove('hidden');
    
    try {
        // TODO: Implementar endpoint para buscar detalhamento real
        // Por enquanto, mostrar estrutura simulada
        setTimeout(() => {
            conteudo.innerHTML = `
                <div class="space-y-6">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                        <h4 class="font-semibold text-blue-800 mb-3 flex items-center gap-2">
                            📋 Procedimento Padrão
                        </h4>
                        <p class="text-sm text-blue-700 mb-3">Passo-a-passo detalhado para execução em cenário ideal:</p>
                        <div class="bg-white p-4 rounded border">
                            <div class="text-sm text-gray-700">
                                <strong>Serviço ID:</strong> ${servicoId}<br>
                                <strong>Status:</strong> Detalhamento completo disponível<br>
                                <strong>Sistema:</strong> ${<?= json_encode($dados['sistema'] ?? 'indefinido') ?>}
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                        <h4 class="font-semibold text-yellow-800 mb-3">⚠️ Problemas N2 (Supervisão)</h4>
                        <p class="text-sm text-yellow-700">Situações que requerem intervenção de supervisão/coordenação</p>
                    </div>
                    
                    <div class="bg-red-50 border border-red-200 rounded-lg p-6">
                        <h4 class="font-semibold text-red-800 mb-3">🚨 Problemas N3 (Direção)</h4>
                        <p class="text-sm text-red-700">Escalações para direção ou gerência estratégica</p>
                    </div>
                    
                    <div class="text-center">
                        <p class="text-gray-500 text-sm">
                            💡 <strong>Implementação em Desenvolvimento:</strong><br>
                            O detalhamento completo N1-N2-N3 será integrado em breve com dados reais do banco.
                        </p>
                    </div>
                </div>
            `;
        }, 1000);
    } catch (error) {
        console.error('Erro ao carregar detalhamento:', error);
        conteudo.innerHTML = `
            <div class="text-center py-8">
                <div class="text-red-500 mb-4">❌</div>
                <div class="text-gray-700">Erro ao carregar detalhamento</div>
                <button onclick="fecharModalDetalhamento()" class="mt-4 px-4 py-2 bg-gray-600 text-white rounded-lg">
                    Fechar
                </button>
            </div>
        `;
    }
}

// Fechar modal de detalhamento
function fecharModalDetalhamento() {
    document.getElementById('modal-detalhamento').classList.add('hidden');
}

// Exportar todos os SOPs
function exportarTodosSops() {
    const diagnosticoId = <?= $dados['diagnostico']['id'] ?>;
    const url = `<?= APP_URL ?>/sop/exportar-todos-zip?diagnostico_id=${diagnosticoId}`;
    window.location.href = url;
}

// Redirecionar para gestão hierárquica se disponível
<?php if ($dados['usar_nova_arquitetura'] && ($dados['sistema'] ?? '') === 'hierarquico'): ?>
function gerenciarHierarquicamente() {
    window.location.href = `<?= APP_URL ?>/sop/gerenciar-hierarquia?diagnostico_id=<?= $dados['diagnostico']['id'] ?>`;
}

// Adicionar botão se for sistema hierárquico
document.addEventListener('DOMContentLoaded', function() {
    const botoesAcao = document.querySelector('.flex.gap-3');
    if (botoesAcao) {
        const botaoHierarquico = document.createElement('button');
        botaoHierarquico.onclick = gerenciarHierarquicamente;
        botaoHierarquico.className = 'px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition flex items-center gap-2';
        botaoHierarquico.innerHTML = '🏢 Gestão Hierárquica';
        botoesAcao.appendChild(botaoHierarquico);
    }
});
<?php endif; ?>

// Fechar modal com ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        fecharModalDetalhamento();
    }
});
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>