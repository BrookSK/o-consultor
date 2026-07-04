<?php $tituloPagina = 'Mapear Serviços - Etapa 2A'; ?>
<?php ob_start(); ?>

<!-- Progresso das Etapas -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Gerando Manual Completo</h1>
        <div class="text-sm text-gray-600"><?= $dados['empresa']['nome'] ?></div>
    </div>
    
    <!-- Barra de Progresso -->
    <div class="mt-4 bg-gray-200 rounded-full h-3">
        <div class="bg-blue-600 h-3 rounded-full transition-all duration-300" style="width: <?= $dados['progresso']['progresso_percentual'] ?? 5 ?>%"></div>
    </div>
    
    <!-- Indicadores de Etapas -->
    <div class="flex items-center justify-between mt-4 text-sm">
        <div class="flex items-center space-x-2 text-green-600">
            <span class="w-6 h-6 bg-green-600 text-white rounded-full flex items-center justify-center text-xs">✓</span>
            <span>Etapa 1: Estrutura</span>
        </div>
        <div class="flex items-center space-x-2 text-blue-600 font-semibold">
            <span class="w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs">2A</span>
            <span>Etapa 2A: Mapeamento</span>
        </div>
        <div class="flex items-center space-x-2 text-gray-400">
            <span class="w-6 h-6 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center text-xs">2B</span>
            <span>Etapa 2B: Detalhamento</span>
        </div>
        <div class="flex items-center space-x-2 text-gray-400">
            <span class="w-6 h-6 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center text-xs">3</span>
            <span>Etapa 3: SOPs</span>
        </div>
    </div>
</div>

<!-- Instruções -->
<div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
    <h3 class="font-semibold text-blue-900 mb-2">📋 Etapa 2A: Mapeamento de Serviços</h3>
    <p class="text-blue-700 mb-3">
        Vamos mapear <strong>todos os tipos de serviços possíveis</strong> para cada setor da empresa.
        Isso garantirá que nenhum processo importante seja esquecido.
    </p>
    <div class="text-sm text-blue-600">
        <strong>O que acontece:</strong> Para cada setor, fazemos uma chamada específica para listar todos os serviços/processos possíveis, 
        incluindo situações rotineiras, críticas, exceções e emergências.
    </div>
</div>

<!-- Lista de Setores para Mapear -->
<div class="space-y-4">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">
        Setores Identificados (<?= count($dados['setores']) ?> total)
    </h2>
    
    <?php foreach ($dados['setores'] as $index => $setor): ?>
    <div class="bg-white border border-gray-200 rounded-lg p-6" id="setor-<?= $index ?>">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <div class="flex items-center space-x-3 mb-2">
                    <h3 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($setor['nome_setor']) ?></h3>
                    <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 rounded">
                        <?= ucfirst($setor['tipo']) ?>
                    </span>
                </div>
                <p class="text-gray-600 mb-3"><?= htmlspecialchars($setor['funcao_no_negocio']) ?></p>
                <div class="text-sm text-gray-500">
                    <strong>Responsável:</strong> <?= htmlspecialchars($setor['responsavel_sugerido']) ?>
                </div>
            </div>
            
            <!-- Status e Ação -->
            <div class="flex flex-col items-end space-y-2">
                <div class="status-setor" id="status-setor-<?= $index ?>">
                    <span class="px-3 py-1 text-sm bg-gray-100 text-gray-600 rounded-full">Aguardando</span>
                </div>
                
                <button type="button" 
                        onclick="mapearSetor('<?= htmlspecialchars($setor['nome_setor']) ?>', <?= $index ?>)"
                        class="btn-mapear px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition"
                        id="btn-mapear-<?= $index ?>">
                    🔍 Mapear Serviços
                </button>
            </div>
        </div>
        
        <!-- Área de Resultado (hidden inicialmente) -->
        <div class="servicos-mapeados mt-4 hidden" id="resultado-<?= $index ?>">
            <div class="border-t pt-4">
                <h4 class="font-semibold text-green-700 mb-2">✅ Serviços Mapeados:</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2" id="lista-servicos-<?= $index ?>">
                    <!-- Serviços serão inseridos aqui via JavaScript -->
                </div>
                <div class="mt-2 text-sm text-green-600" id="total-servicos-<?= $index ?>">
                    <!-- Total será inserido aqui -->
                </div>
            </div>
        </div>
        
        <!-- Loading -->
        <div class="loading-setor mt-4 hidden" id="loading-<?= $index ?>">
            <div class="flex items-center space-x-3 text-blue-600">
                <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 0 1 4 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Mapeando todos os serviços possíveis...</span>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Botões de Ação -->
<div class="flex justify-between items-center mt-8 pt-6 border-t">
    <a href="<?= APP_URL ?>/sop" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
        ← Voltar ao Manual
    </a>
    
    <button type="button" 
            onclick="prosseguirEtapa2B()"
            class="px-8 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition disabled:bg-gray-300 disabled:cursor-not-allowed"
            id="btn-prosseguir"
            disabled>
        Prosseguir para Etapa 2B: Detalhamento →
    </button>
</div>

<script>
let estruturaId = <?= $dados['estrutura_id'] ?>;
let setoresMapeados = 0;
let totalSetores = <?= count($dados['setores']) ?>;

// Mapear serviços de um setor específico
async function mapearSetor(setorNome, index) {
    const btnMapear = document.getElementById(`btn-mapear-${index}`);
    const loading = document.getElementById(`loading-${index}`);
    const status = document.getElementById(`status-setor-${index}`);
    const resultado = document.getElementById(`resultado-${index}`);
    
    // UI: Iniciando
    btnMapear.disabled = true;
    btnMapear.textContent = 'Mapeando...';
    loading.classList.remove('hidden');
    status.innerHTML = '<span class="px-3 py-1 text-sm bg-blue-100 text-blue-600 rounded-full">Mapeando...</span>';
    
    try {
        const formData = new FormData();
        formData.append('estrutura_id', estruturaId);
        formData.append('setor_nome', setorNome);
        formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
        
        const response = await fetch('<?= APP_URL ?>/sop/executar-mapeamento-setor', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.sucesso) {
            // Sucesso: mostrar serviços mapeados
            status.innerHTML = '<span class="px-3 py-1 text-sm bg-green-100 text-green-600 rounded-full">✅ Concluído</span>';
            
            // Mostrar lista de serviços
            const listaServicos = document.getElementById(`lista-servicos-${index}`);
            const totalServicos = document.getElementById(`total-servicos-${index}`);
            
            listaServicos.innerHTML = result.servicos.map(s => 
                `<div class="text-sm p-2 bg-gray-50 rounded border">
                    <div class="font-medium">${s.nome}</div>
                    <div class="text-xs text-gray-500">Criticidade: ${s.criticidade}</div>
                </div>`
            ).join('');
            
            totalServicos.textContent = `${result.total_servicos} serviços identificados`;
            resultado.classList.remove('hidden');
            
            // Contar progresso
            setoresMapeados++;
            verificarProgressoCompleto();
            
            btnMapear.textContent = '✅ Mapeado';
            btnMapear.classList.replace('bg-blue-600', 'bg-green-600');
            btnMapear.classList.replace('hover:bg-blue-700', 'hover:bg-green-700');
            
        } else {
            // Erro
            status.innerHTML = '<span class="px-3 py-1 text-sm bg-red-100 text-red-600 rounded-full">❌ Erro</span>';
            btnMapear.textContent = '🔄 Tentar Novamente';
            btnMapear.disabled = false;
            alert('Erro: ' + result.erro);
        }
    } catch (error) {
        console.error('Erro no mapeamento:', error);
        status.innerHTML = '<span class="px-3 py-1 text-sm bg-red-100 text-red-600 rounded-full">❌ Erro</span>';
        btnMapear.textContent = '🔄 Tentar Novamente';
        btnMapear.disabled = false;
        alert('Erro de conexão. Tente novamente.');
    } finally {
        loading.classList.add('hidden');
    }
}

// Verificar se todos os setores foram mapeados
function verificarProgressoCompleto() {
    const btnProsseguir = document.getElementById('btn-prosseguir');
    
    if (setoresMapeados >= totalSetores) {
        btnProsseguir.disabled = false;
        btnProsseguir.classList.replace('bg-gray-300', 'bg-green-600');
        btnProsseguir.classList.add('animate-pulse');
        
        // Mostrar mensagem de sucesso
        showToast('🎉 Todos os setores mapeados! Você pode prosseguir para o detalhamento.', 'success');
    }
}

// Prosseguir para Etapa 2B
function prosseguirEtapa2B() {
    if (setoresMapeados < totalSetores) {
        alert('É necessário mapear todos os setores primeiro.');
        return;
    }
    
    window.location.href = `<?= APP_URL ?>/sop/detalhar-servicos?estrutura_id=${estruturaId}`;
}

// Toast notifications
function showToast(message, type = 'info') {
    // TODO: Implementar sistema de toast
    console.log(`${type.toUpperCase()}: ${message}`);
}

// Auto-mapear todos os setores ao carregar (opcional)
document.addEventListener('DOMContentLoaded', function() {
    // Se quiser iniciar automaticamente, descomente a linha abaixo:
    // mapearTodosSetores();
});

// Função para mapear todos automaticamente
async function mapearTodosSetores() {
    for (let i = 0; i < totalSetores; i++) {
        const setorNome = '<?= implode("','", array_column($dados['setores'], 'nome_setor')) ?>'.split(',')[i];
        await mapearSetor(setorNome, i);
        await new Promise(resolve => setTimeout(resolve, 1000)); // Delay entre chamadas
    }
}
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php include VIEW_PATH . '/layouts/layout.php'; ?>