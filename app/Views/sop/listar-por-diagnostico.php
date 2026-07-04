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
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-blue-600">SOPs Tradicionais</div>
                    <div class="text-2xl font-bold text-blue-700"><?= $dados['total_sops_tradicional'] ?></div>
                </div>
                <div class="text-blue-400">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"/>
                        <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-purple-600">Nova Arquitetura N1-N2-N3</div>
                    <div class="text-2xl font-bold text-purple-700"><?= $dados['total_sops_nova_arquitetura'] ?></div>
                </div>
                <div class="text-purple-400">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-green-600">Total de SOPs</div>
                    <div class="text-2xl font-bold text-green-700"><?= $dados['total_geral'] ?></div>
                </div>
                <div class="text-green-400">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (empty($dados['sops_por_departamento'])): ?>
<!-- Nenhum SOP Encontrado -->
<div class="text-center py-12">
    <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
    </div>
    <h3 class="text-lg font-semibold text-gray-700 mb-2">Nenhum SOP Gerado Ainda</h3>
    <p class="text-gray-500 mb-6">
        Ainda não foram gerados SOPs para este diagnóstico.<br>
        Use o botão "Manual Completo" na tela de resultado para iniciar a geração.
    </p>
    <a href="<?= APP_URL ?>/diagnostico/resultado/<?= $dados['diagnostico']['id'] ?>" 
       class="inline-flex items-center px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-700 transition">
        ← Voltar ao Resultado do Diagnóstico
    </a>
</div>
<?php else: ?>

<!-- Lista de SOPs por Departamento -->
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
                    <?= count($departamento['sops_tradicionais']) + count($departamento['sops_nova_arquitetura']) ?> SOPs
                </span>
            </div>
        </div>

        <div class="p-6">
            <!-- SOPs Tradicionais -->
            <?php if (!empty($departamento['sops_tradicionais'])): ?>
            <div class="mb-6">
                <h3 class="text-md font-semibold text-gray-700 mb-3 flex items-center gap-2">
                    <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                    SOPs Tradicionais (<?= count($departamento['sops_tradicionais']) ?>)
                </h3>
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
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                Ver SOP
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- SOPs Nova Arquitetura -->
            <?php if (!empty($departamento['sops_nova_arquitetura'])): ?>
            <div>
                <h3 class="text-md font-semibold text-gray-700 mb-3 flex items-center gap-2">
                    <span class="w-2 h-2 bg-purple-500 rounded-full"></span>
                    Nova Arquitetura N1-N2-N3 (<?= count($departamento['sops_nova_arquitetura']) ?>)
                </h3>
                <div class="grid gap-3">
                    <?php foreach ($departamento['sops_nova_arquitetura'] as $sop): ?>
                    <div class="flex items-center justify-between p-4 border border-purple-200 bg-purple-50 rounded-lg hover:bg-purple-100 transition">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-1">
                                <h4 class="font-semibold text-purple-800"><?= htmlspecialchars($sop['servico_nome']) ?></h4>
                                <span class="px-2 py-1 text-xs font-medium bg-purple-200 text-purple-700 rounded">
                                    Criticidade <?= $sop['criticidade'] ?? 2 ?>
                                </span>
                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-600 rounded">
                                    <?= $sop['problemas_mapeados'] ?? 0 ?> Problemas N1-N2-N3
                                </span>
                            </div>
                            <div class="text-sm text-purple-600">
                                <strong>Código:</strong> <?= htmlspecialchars($sop['servico_codigo']) ?> • 
                                <strong>Criado:</strong> <?= $sop['data_criacao_formatada'] ?>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button onclick="verSopNovaArquitetura(<?= $sop['id'] ?>)" 
                                    class="px-4 py-2 bg-purple-600 text-white text-sm rounded-lg hover:bg-purple-700 transition flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Ver Detalhes
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Botões de Ação -->
<div class="flex justify-between items-center mt-8 pt-6 border-t">
    <a href="<?= APP_URL ?>/diagnostico/resultado/<?= $dados['diagnostico']['id'] ?>" 
       class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Voltar ao Resultado
    </a>
    
    <div class="flex gap-3">
        <a href="<?= APP_URL ?>/sop?diagnostico_id=<?= $dados['diagnostico']['id'] ?>" 
           class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Gerar Mais SOPs
        </a>
        
        <?php if ($dados['total_geral'] > 0): ?>
        <button onclick="exportarTodosSops()" 
                class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Exportar Todos
        </button>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>

<!-- Modal para SOPs Nova Arquitetura -->
<div id="modal-sop-nova-arquitetura" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-hidden">
        <div class="flex items-center justify-between p-6 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Detalhamento Completo - Nova Arquitetura</h3>
            <button onclick="fecharModalSop()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-80px)]" id="conteudo-modal-sop">
            <!-- Conteúdo será inserido via JavaScript -->
        </div>
    </div>
</div>

<script>
// Ver SOP da Nova Arquitetura
async function verSopNovaArquitetura(sopId) {
    const modal = document.getElementById('modal-sop-nova-arquitetura');
    const conteudo = document.getElementById('conteudo-modal-sop');
    
    // Mostrar loading
    conteudo.innerHTML = `
        <div class="text-center py-8">
            <div class="inline-block w-8 h-8 border-4 border-gray-200 border-t-purple-600 rounded-full animate-spin mb-4"></div>
            <div class="text-gray-500">Carregando detalhamento...</div>
        </div>
    `;
    modal.classList.remove('hidden');
    
    try {
        // Buscar detalhamento (simulado - implementar endpoint real se necessário)
        conteudo.innerHTML = `
            <div class="space-y-6">
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                    <h4 class="font-semibold text-purple-800 mb-2">📋 Procedimento Padrão</h4>
                    <p class="text-sm text-purple-700">Passo-a-passo detalhado para execução em cenário ideal.</p>
                </div>
                
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <h4 class="font-semibold text-red-800 mb-2">⚠️ Problemas Mapeados N1-N2-N3</h4>
                    <p class="text-sm text-red-700">Cenários problemáticos com soluções em 3 níveis de contenção.</p>
                </div>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="font-semibold text-blue-800 mb-2">🎯 KPIs e Controles</h4>
                    <p class="text-sm text-blue-700">Métricas para monitoramento e pontos de controle.</p>
                </div>
                
                <div class="text-center">
                    <p class="text-sm text-gray-500">SOP ID: ${sopId}</p>
                    <button onclick="alert('Funcionalidade em desenvolvimento')" 
                            class="mt-4 px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                        Ver SOP Completo
                    </button>
                </div>
            </div>
        `;
    } catch (error) {
        conteudo.innerHTML = `
            <div class="text-center py-8 text-red-600">
                <div class="mb-2">❌ Erro ao carregar</div>
                <div class="text-sm">${error.message}</div>
            </div>
        `;
    }
}

// Fechar modal
function fecharModalSop() {
    document.getElementById('modal-sop-nova-arquitetura').classList.add('hidden');
}

// Exportar todos os SOPs
function exportarTodosSops() {
    if (confirm('Deseja exportar todos os SOPs gerados para este diagnóstico?')) {
        window.open('<?= APP_URL ?>/sop/exportar-todos-zip?diagnostico_id=<?= $dados["diagnostico"]["id"] ?>', '_blank');
    }
}

// Fechar modal ao clicar fora
document.getElementById('modal-sop-nova-arquitetura').addEventListener('click', function(e) {
    if (e.target === this) {
        fecharModalSop();
    }
});
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php include VIEW_PATH . '/layouts/layout.php'; ?>