<?php $tituloPagina = 'Detalhar Serviços - Etapa 2B'; ?>
<?php ob_start(); ?>

<!-- Progresso das Etapas -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Gerando Manual Completo</h1>
        <div class="text-sm text-gray-600"><?= $dados['empresa']['nome'] ?></div>
    </div>
    
    <!-- Barra de Progresso -->
    <div class="mt-4 bg-gray-200 rounded-full h-3">
        <div class="bg-blue-600 h-3 rounded-full transition-all duration-300" style="width: <?= $dados['progresso']['progresso_percentual'] ?? 30 ?>%"></div>
    </div>
    
    <!-- Indicadores de Etapas -->
    <div class="flex items-center justify-between mt-4 text-sm">
        <div class="flex items-center space-x-2 text-green-600">
            <span class="w-6 h-6 bg-green-600 text-white rounded-full flex items-center justify-center text-xs">✓</span>
            <span>Etapa 1: Estrutura</span>
        </div>
        <div class="flex items-center space-x-2 text-green-600">
            <span class="w-6 h-6 bg-green-600 text-white rounded-full flex items-center justify-center text-xs">✓</span>
            <span>Etapa 2A: Mapeamento</span>
        </div>
        <div class="flex items-center space-x-2 text-purple-600 font-semibold">
            <span class="w-6 h-6 bg-purple-600 text-white rounded-full flex items-center justify-center text-xs">2B</span>
            <span>Etapa 2B: Detalhamento</span>
        </div>
        <div class="flex items-center space-x-2 text-gray-400">
            <span class="w-6 h-6 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center text-xs">3</span>
            <span>Etapa 3: SOPs</span>
        </div>
    </div>
</div>

<!-- Instruções -->
<div class="bg-purple-50 border border-purple-200 rounded-lg p-6 mb-8">
    <h3 class="font-semibold text-purple-900 mb-2">🎯 Etapa 2B: Detalhamento Individual</h3>
    <p class="text-purple-700 mb-3">
        Agora vamos detalhar <strong>cada serviço individualmente</strong> com máxima profundidade: 
        procedimentos completos, todos os problemas possíveis e soluções N1-N2-N3.
    </p>
    <div class="text-sm text-purple-600">
        <strong>O que acontece:</strong> Para cada serviço mapeado, fazemos uma chamada específica detalhando 
        passo-a-passo, cenários problemáticos, resistências humanas, falhas técnicas e níveis de contenção.
    </div>
</div>

<!-- Contador de Progresso -->
<div class="mb-6">
    <div class="bg-white border border-gray-200 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-gray-800">Progresso do Detalhamento</h3>
                <p class="text-sm text-gray-600">Serviços detalhados com problemas N1-N2-N3</p>
            </div>
            <div class="text-right">
                <div class="text-2xl font-bold text-purple-600" id="contador-progresso">0 / <?= array_sum(array_column($dados['servicos_mapeados'], 'total_servicos')) ?></div>
                <div class="text-sm text-gray-500">serviços completos</div>
            </div>
        </div>
    </div>
</div>

<!-- Lista de Serviços por Setor -->
<div class="space-y-6">
    <?php 
    $servicoIndex = 0;
    foreach ($dados['servicos_mapeados'] as $setorData): 
        $servicos = json_decode($setorData['servicos_json'], true)['servicos'] ?? [];
    ?>
    
    <div class="bg-white border border-gray-200 rounded-lg">
        <!-- Header do Setor -->
        <div class="bg-gray-50 px-6 py-4 border-b">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($setorData['setor_nome']) ?></h2>
                <span class="px-3 py-1 bg-blue-100 text-blue-600 text-sm rounded-full">
                    <?= count($servicos) ?> serviços
                </span>
            </div>
        </div>
        
        <!-- Lista de Serviços -->
        <div class="p-6">
            <div class="grid gap-4">
                <?php foreach ($servicos as $servico): ?>
                <div class="border border-gray-200 rounded-lg p-4" id="servico-<?= $servicoIndex ?>">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-2">
                                <h3 class="font-semibold text-gray-800"><?= htmlspecialchars($servico['nome']) ?></h3>
                                <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-600 rounded">
                                    Crítico <?= $servico['criticidade'] ?>
                                </span>
                                <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 rounded">
                                    <?= ucfirst($servico['categoria']) ?>
                                </span>
                            </div>
                            <p class="text-gray-600 text-sm mb-2"><?= htmlspecialchars($servico['descricao_resumida']) ?></p>
                            <div class="text-xs text-gray-500">
                                <strong>Gatilho:</strong> <?= htmlspecialchars($servico['gatilho_entrada']) ?>
                            </div>
                            <?php if (!empty($servico['cenarios_problematicos'])): ?>
                            <div class="mt-2 text-xs text-orange-600">
                                <strong>Cenários conhecidos:</strong> <?= implode(', ', array_slice($servico['cenarios_problematicos'], 0, 2)) ?>
                                <?= count($servico['cenarios_problematicos']) > 2 ? '...' : '' ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Status e Ação -->
                        <div class="flex flex-col items-end space-y-2 ml-4">
                            <div class="status-servico" id="status-servico-<?= $servicoIndex ?>">
                                <span class="px-3 py-1 text-sm bg-gray-100 text-gray-600 rounded-full">Aguardando</span>
                            </div>
                            
                            <button type="button" 
                                    onclick="detalharServico('<?= htmlspecialchars($servico['nome']) ?>', '<?= htmlspecialchars($servico['codigo']) ?>', '<?= htmlspecialchars($setorData['setor_nome']) ?>', <?= $servicoIndex ?>)"
                                    class="btn-detalhar px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition"
                                    id="btn-detalhar-<?= $servicoIndex ?>">
                                🎯 Detalhar
                            </button>
                        </div>
                    </div>
                    
                    <!-- Área de Resultado (hidden inicialmente) -->
                    <div class="detalhamento-resultado mt-4 hidden" id="resultado-<?= $servicoIndex ?>">
                        <div class="border-t pt-4">
                            <h4 class="font-semibold text-green-700 mb-2">✅ Detalhamento Completo:</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3" id="detalhes-<?= $servicoIndex ?>">
                                <!-- Detalhes serão inseridos aqui via JavaScript -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- Loading -->
                    <div class="loading-servico mt-4 hidden" id="loading-<?= $servicoIndex ?>">
                        <div class="flex items-center space-x-3 text-purple-600">
                            <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 0 1 4 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Detalhando procedimentos e problemas N1-N2-N3...</span>
                        </div>
                    </div>
                </div>
                <?php 
                $servicoIndex++;
                endforeach; ?>
            </div>
        </div>
    </div>
    
    <?php endforeach; ?>
</div>

<!-- Botões de Ação -->
<div class="flex justify-between items-center mt-8 pt-6 border-t">
    <a href="<?= APP_URL ?>/sop/mapear-servicos?estrutura_id=<?= $dados['estrutura_id'] ?>" 
       class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
        ← Voltar ao Mapeamento
    </a>
    
    <button type="button" 
            onclick="prosseguirEtapa3()"
            class="px-8 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition disabled:bg-gray-300 disabled:cursor-not-allowed"
            id="btn-prosseguir"
            disabled>
        Prosseguir para Etapa 3: Gerar SOPs →
    </button>
</div>

<script>
let estruturaId = <?= $dados['estrutura_id'] ?>;
let servicosDetalhados = 0;
let totalServicos = <?= array_sum(array_column($dados['servicos_mapeados'], 'total_servicos')) ?>;

// Detalhar um serviço específico
async function detalharServico(servicoNome, servicoCodigo, setorNome, index) {
    const btnDetalhar = document.getElementById(`btn-detalhar-${index}`);
    const loading = document.getElementById(`loading-${index}`);
    const status = document.getElementById(`status-servico-${index}`);
    const resultado = document.getElementById(`resultado-${index}`);
    
    // UI: Iniciando
    btnDetalhar.disabled = true;
    btnDetalhar.textContent = 'Detalhando...';
    loading.classList.remove('hidden');
    status.innerHTML = '<span class="px-3 py-1 text-sm bg-purple-100 text-purple-600 rounded-full">Detalhando...</span>';
    
    try {
        const formData = new FormData();
        formData.append('estrutura_id', estruturaId);
        formData.append('servico_nome', servicoNome);
        formData.append('servico_codigo', servicoCodigo);
        formData.append('setor_nome', setorNome);
        formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
        
        const response = await fetch('<?= APP_URL ?>/sop/executar-detalhamento-servico', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.sucesso) {
            // Sucesso: mostrar detalhamento
            status.innerHTML = '<span class="px-3 py-1 text-sm bg-green-100 text-green-600 rounded-full">✅ Detalhado</span>';
            
            // Mostrar resumo do detalhamento
            const detalhes = document.getElementById(`detalhes-${index}`);
            detalhes.innerHTML = `
                <div class="text-sm p-3 bg-green-50 rounded border">
                    <div class="font-medium text-green-800">Procedimento Padrão</div>
                    <div class="text-green-600">Passo-a-passo completo</div>
                </div>
                <div class="text-sm p-3 bg-red-50 rounded border">
                    <div class="font-medium text-red-800">Problemas Mapeados</div>
                    <div class="text-red-600">${result.problemas_mapeados} cenários N1-N2-N3</div>
                </div>
                <div class="text-sm p-3 bg-blue-50 rounded border">
                    <div class="font-medium text-blue-800">Pronto para SOP</div>
                    <div class="text-blue-600">Detalhamento salvo</div>
                </div>
            `;
            
            resultado.classList.remove('hidden');
            
            // Contar progresso
            servicosDetalhados++;
            atualizarContadorProgresso();
            verificarProgressoCompleto();
            
            btnDetalhar.textContent = '✅ Detalhado';
            btnDetalhar.classList.replace('bg-purple-600', 'bg-green-600');
            btnDetalhar.classList.replace('hover:bg-purple-700', 'hover:bg-green-700');
            
        } else {
            // Erro
            status.innerHTML = '<span class="px-3 py-1 text-sm bg-red-100 text-red-600 rounded-full">❌ Erro</span>';
            btnDetalhar.textContent = '🔄 Tentar Novamente';
            btnDetalhar.disabled = false;
            alert('Erro: ' + result.erro);
        }
    } catch (error) {
        console.error('Erro no detalhamento:', error);
        status.innerHTML = '<span class="px-3 py-1 text-sm bg-red-100 text-red-600 rounded-full">❌ Erro</span>';
        btnDetalhar.textContent = '🔄 Tentar Novamente';
        btnDetalhar.disabled = false;
        alert('Erro de conexão. Tente novamente.');
    } finally {
        loading.classList.add('hidden');
    }
}

// Atualizar contador de progresso
function atualizarContadorProgresso() {
    const contador = document.getElementById('contador-progresso');
    contador.textContent = `${servicosDetalhados} / ${totalServicos}`;
}

// Verificar se todos os serviços foram detalhados
function verificarProgressoCompleto() {
    const btnProsseguir = document.getElementById('btn-prosseguir');
    
    if (servicosDetalhados >= totalServicos) {
        btnProsseguir.disabled = false;
        btnProsseguir.classList.replace('bg-gray-300', 'bg-green-600');
        btnProsseguir.classList.add('animate-pulse');
        
        // Mostrar mensagem de sucesso
        showToast('🎉 Todos os serviços detalhados! Agora vamos gerar os SOPs completos.', 'success');
    }
}

// Prosseguir para Etapa 3
function prosseguirEtapa3() {
    if (servicosDetalhados < totalServicos) {
        alert('É necessário detalhar todos os serviços primeiro.');
        return;
    }
    
    window.location.href = `<?= APP_URL ?>/sop/processar-sops?estrutura_id=${estruturaId}`;
}

// Toast notifications
function showToast(message, type = 'info') {
    // TODO: Implementar sistema de toast
    console.log(`${type.toUpperCase()}: ${message}`);
}

// Função para detalhar todos automaticamente (para testes)
async function detalharTodosServicos() {
    const botoes = document.querySelectorAll('.btn-detalhar');
    for (let i = 0; i < botoes.length; i++) {
        if (!botoes[i].disabled) {
            botoes[i].click();
            await new Promise(resolve => setTimeout(resolve, 2000)); // Delay entre chamadas
        }
    }
}

// Auto-start para demonstração (remover em produção)
document.addEventListener('DOMContentLoaded', function() {
    // Se quiser iniciar automaticamente todos, descomente:
    // setTimeout(detalharTodosServicos, 2000);
});
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php include VIEW_PATH . '/layouts/layout.php'; ?>