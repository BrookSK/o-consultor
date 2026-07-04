<?php $tituloPagina = 'Mapear Serviços - Etapa 2A'; ?>
<?php ob_start(); ?>

<!-- CSRF Token -->
<?php if (!Session::get('csrf_token')): ?>
    <?php Session::set('csrf_token', Csrf::gerar()); ?>
<?php endif; ?>
<meta name="csrf-token" content="<?= Session::get('csrf_token') ?>">

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

<!-- Debug Info (modo de desenvolvimento) -->
<div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6 text-sm">
    <h4 class="font-semibold text-gray-700 mb-2">ℹ️ Informações do Sistema</h4>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div>
            <span class="text-gray-500">Estrutura ID:</span>
            <span class="font-mono text-gray-800"><?= $dados['estrutura_id'] ?></span>
        </div>
        <div>
            <span class="text-gray-500">Total Setores:</span>
            <span class="font-mono text-gray-800"><?= count($dados['setores']) ?></span>
        </div>
        <div>
            <span class="text-gray-500">CSRF Token:</span>
            <span class="font-mono text-gray-800" id="csrf-status">Verificando...</span>
        </div>
        <div>
            <span class="text-gray-500">API URL:</span>
            <span class="font-mono text-gray-800"><?= APP_URL ?>/sop/executar-mapeamento-setor</span>
        </div>
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
                        onclick="mapearSetor(<?= json_encode($setor['nome_setor']) ?>, <?= $index ?>)"
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
    <div class="flex space-x-4">
        <a href="<?= APP_URL ?>/sop" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
            ← Voltar ao Manual
        </a>
        
        <!-- Botão de Debug/Teste -->
        <button type="button" 
                onclick="testarConexaoAPI()"
                class="px-6 py-2 border border-yellow-300 text-yellow-700 rounded-lg hover:bg-yellow-50">
            🔧 Testar API
        </button>
    </div>
    
    <button type="button" 
            onclick="prosseguirEtapa2B()"
            class="px-8 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition disabled:bg-gray-300 disabled:cursor-not-allowed"
            id="btn-prosseguir"
            disabled>
        Prosseguir para Etapa 2B: Detalhamento →
    </button>
</div>

<script>
let estruturaId = <?= (int) $dados['estrutura_id'] ?>;
let setoresMapeados = 0;
let totalSetores = <?= count($dados['setores']) ?>;

console.log('=== INICIALIZAÇÃO ===');
console.log('Estrutura ID:', estruturaId);
console.log('Total Setores:', totalSetores);
console.log('Setores:', <?= json_encode(array_column($dados['setores'], 'nome_setor')) ?>);

// Mapear serviços de um setor específico
async function mapearSetor(setorNome, index) {
    console.log('=== INICIANDO MAPEAMENTO ===');
    console.log('Setor:', setorNome, 'Tipo:', typeof setorNome);
    console.log('Index:', index, 'Tipo:', typeof index);
    console.log('URL da API:', '<?= APP_URL ?>/sop/executar-mapeamento-setor');
    
    // Validar parâmetros
    if (!setorNome || setorNome === '') {
        console.error('ERRO: Nome do setor inválido:', setorNome);
        alert('Erro: Nome do setor inválido.');
        return;
    }
    
    if (index === undefined || index === null) {
        console.error('ERRO: Índice inválido:', index);
        alert('Erro: Índice do setor inválido.');
        return;
    }
    
    const btnMapear = document.getElementById(`btn-mapear-${index}`);
    const loading = document.getElementById(`loading-${index}`);
    const status = document.getElementById(`status-setor-${index}`);
    const resultado = document.getElementById(`resultado-${index}`);
    
    if (!btnMapear) {
        console.error('ERRO: Botão não encontrado:', `btn-mapear-${index}`);
        alert('Erro: Botão não encontrado. Recarregue a página.');
        return;
    }
    
    // UI: Iniciando
    btnMapear.disabled = true;
    btnMapear.textContent = 'Mapeando...';
    if (loading) loading.classList.remove('hidden');
    if (status) status.innerHTML = '<span class="px-3 py-1 text-sm bg-blue-100 text-blue-600 rounded-full">Mapeando...</span>';
    
    try {
        // Obter CSRF token
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
        
        console.log('CSRF Token obtido:', csrfToken ? 'SIM (tamanho: ' + csrfToken.length + ')' : 'NÃO');
        console.log('Estrutura ID:', estruturaId);
        
        if (!csrfToken) {
            throw new Error('CSRF token não encontrado. A sessão pode ter expirado.');
        }
        
        if (!estruturaId || estruturaId === 0) {
            throw new Error('ID da estrutura inválido: ' + estruturaId);
        }
        
        const formData = new FormData();
        formData.append('estrutura_id', String(estruturaId));
        formData.append('setor_nome', String(setorNome));
        formData.append('csrf_token', csrfToken);
        
        console.log('=== DADOS SENDO ENVIADOS ===');
        console.log('estrutura_id:', estruturaId, '(string:', String(estruturaId) + ')');
        console.log('setor_nome:', setorNome, '(string:', String(setorNome) + ')');
        console.log('csrf_token:', csrfToken ? 'Presente' : 'Ausente');
        
        console.log('=== FAZENDO REQUISIÇÃO ===');
        const response = await fetch('<?= APP_URL ?>/sop/executar-mapeamento-setor', {
            method: 'POST',
            body: formData
        });
        
        console.log('Response Status:', response.status);
        console.log('Response Headers:', response.headers);
        console.log('Response OK:', response.ok);
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Resposta HTTP não-OK:', errorText);
            throw new Error(`HTTP ${response.status}: ${response.statusText}\n\nResposta: ${errorText.substring(0, 500)}`);
        }
        
        const responseText = await response.text();
        console.log('=== RESPOSTA RECEBIDA ===');
        console.log('Tamanho da resposta:', responseText.length);
        console.log('Primeiros 500 chars:', responseText.substring(0, 500));
        
        let result;
        try {
            result = JSON.parse(responseText);
            console.log('JSON parseado com sucesso:', result);
        } catch (parseError) {
            console.error('ERRO ao fazer parse do JSON:', parseError);
            console.error('Resposta completa:', responseText);
            
            // Tentar identificar o problema na resposta
            if (responseText.includes('PHP Parse error') || responseText.includes('PHP Fatal error')) {
                throw new Error('Erro PHP no servidor. Verifique os logs do servidor.\n\nErro: ' + responseText.substring(0, 200));
            } else if (responseText.includes('<html') || responseText.includes('<!DOCTYPE')) {
                throw new Error('Servidor retornou HTML ao invés de JSON. Possível erro de rota ou redirecionamento.');
            } else {
                throw new Error('Resposta inválida do servidor. Não é um JSON válido.\n\nResposta: ' + responseText.substring(0, 300));
            }
        }
        
        console.log('=== RESULTADO PROCESSADO ===');
        console.log('Sucesso:', result.sucesso);
        
        if (result.sucesso) {
            console.log('Total de serviços:', result.total_servicos);
            console.log('Serviços:', result.servicos);
            
            // Sucesso: mostrar serviços mapeados
            if (status) status.innerHTML = '<span class="px-3 py-1 text-sm bg-green-100 text-green-600 rounded-full">✅ Concluído</span>';
            
            // Mostrar lista de serviços
            const listaServicos = document.getElementById(`lista-servicos-${index}`);
            const totalServicos = document.getElementById(`total-servicos-${index}`);
            
            if (listaServicos && result.servicos && Array.isArray(result.servicos)) {
                if (result.servicos.length > 0) {
                    listaServicos.innerHTML = result.servicos.map((s, i) => 
                        `<div class="text-sm p-2 bg-gray-50 rounded border">
                            <div class="font-medium">${s.nome || s.titulo || s.servico || `Serviço ${i + 1}`}</div>
                            <div class="text-xs text-gray-500">Criticidade: ${s.criticidade || s.prioridade || 'Média'}</div>
                        </div>`
                    ).join('');
                } else {
                    listaServicos.innerHTML = '<div class="text-sm text-gray-500 italic">Nenhum serviço específico foi mapeado.</div>';
                }
            }
            
            if (totalServicos) {
                totalServicos.textContent = `${result.total_servicos || 0} serviços identificados`;
            }
            
            if (resultado) resultado.classList.remove('hidden');
            
            // Contar progresso
            setoresMapeados++;
            verificarProgressoCompleto();
            
            btnMapear.textContent = '✅ Mapeado';
            btnMapear.classList.replace('bg-blue-600', 'bg-green-600');
            btnMapear.classList.replace('hover:bg-blue-700', 'hover:bg-green-700');
            
        } else {
            console.error('Erro retornado pela API:', result.erro);
            
            // Erro
            if (status) status.innerHTML = '<span class="px-3 py-1 text-sm bg-red-100 text-red-600 rounded-full">❌ Erro</span>';
            btnMapear.textContent = '🔄 Tentar Novamente';
            btnMapear.disabled = false;
            
            const mensagemErro = result.erro || 'Erro desconhecido no servidor';
            alert('Erro no mapeamento:\n\n' + mensagemErro);
        }
        
    } catch (error) {
        console.error('=== ERRO GERAL ===');
        console.error('Tipo:', error.name);
        console.error('Mensagem:', error.message);
        console.error('Stack:', error.stack);
        
        if (status) status.innerHTML = '<span class="px-3 py-1 text-sm bg-red-100 text-red-600 rounded-full">❌ Erro</span>';
        btnMapear.textContent = '🔄 Tentar Novamente';
        btnMapear.disabled = false;
        
        let mensagemUsuario = 'Erro de conexão ou processamento:\n\n' + error.message;
        
        // Sugerir soluções baseadas no tipo de erro
        if (error.message.includes('CSRF')) {
            mensagemUsuario += '\n\nSolução: Recarregue a página para obter um novo token de segurança.';
        } else if (error.message.includes('estrutura inválido')) {
            mensagemUsuario += '\n\nSolução: Volte à página anterior e inicie novamente o processo.';
        } else if (error.message.includes('HTTP 404')) {
            mensagemUsuario += '\n\nSolução: Verifique se a URL da API está correta.';
        } else if (error.message.includes('HTTP 500')) {
            mensagemUsuario += '\n\nSolução: Erro interno do servidor. Contate o suporte técnico.';
        }
        
        alert(mensagemUsuario);
        
    } finally {
        if (loading) loading.classList.add('hidden');
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

// Função de teste da API
async function testarConexaoAPI() {
    console.log('=== INICIANDO TESTE DA API ===');
    
    try {
        // Testar informações básicas
        console.log('URL da API:', '<?= APP_URL ?>/sop/executar-mapeamento-setor');
        console.log('Estrutura ID:', estruturaId);
        console.log('Total setores:', totalSetores);
        
        // Testar CSRF token
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
        console.log('CSRF Token encontrado:', csrfToken ? 'SIM' : 'NÃO');
        
        if (!csrfToken) {
            alert('ERRO: CSRF Token não encontrado!\n\nRecarregue a página.');
            return;
        }
        
        // Fazer uma requisição de teste simples
        const response = await fetch('<?= APP_URL ?>/sop/executar-mapeamento-setor', {
            method: 'POST',
            body: new FormData() // Requisição vazia para testar rota
        });
        
        console.log('Teste de rota - Status:', response.status);
        console.log('Teste de rota - Headers:', response.headers);
        
        const responseText = await response.text();
        console.log('Teste de rota - Resposta:', responseText.substring(0, 500));
        
        if (response.status === 200) {
            alert('✅ ROTA OK\n\nA rota da API está funcionando. O problema pode estar nos dados enviados.');
        } else {
            alert(`❌ PROBLEMA NA ROTA\n\nStatus: ${response.status}\nResposta: ${responseText.substring(0, 200)}`);
        }
        
    } catch (error) {
        console.error('Erro no teste:', error);
        alert(`❌ ERRO DE CONEXÃO\n\nErro: ${error.message}\n\nVerifique o console para mais detalhes.`);
    }
}

// Auto-mapear todos os setores ao carregar (opcional)
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== PÁGINA CARREGADA ===');
    
    // Verificar se a função mapearSetor existe
    if (typeof mapearSetor === 'function') {
        console.log('✅ Função mapearSetor encontrada');
    } else {
        console.error('❌ Função mapearSetor NÃO encontrada');
    }
    
    // Atualizar status do CSRF
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
    const csrfStatus = document.getElementById('csrf-status');
    
    if (csrfStatus) {
        if (csrfToken) {
            csrfStatus.textContent = '✅ Presente';
            csrfStatus.className = 'font-mono text-green-600';
        } else {
            csrfStatus.textContent = '❌ Ausente';
            csrfStatus.className = 'font-mono text-red-600';
        }
    }
    
    // Verificar se todos os elementos necessários existem
    console.log('=== VERIFICAÇÃO DE ELEMENTOS ===');
    for (let i = 0; i < totalSetores; i++) {
        const btn = document.getElementById(`btn-mapear-${i}`);
        if (btn) {
            console.log(`✅ Botão ${i} encontrado`);
        } else {
            console.error(`❌ Botão ${i} NÃO encontrado`);
        }
    }
    
    // Se quiser iniciar automaticamente, descomente a linha abaixo:
    // mapearTodosSetores();
});

// Função para mapear todos automaticamente
async function mapearTodosSetores() {
    const setores = <?= json_encode(array_column($dados['setores'], 'nome_setor')) ?>;
    
    for (let i = 0; i < totalSetores; i++) {
        if (setores[i]) {
            await mapearSetor(setores[i], i);
            await new Promise(resolve => setTimeout(resolve, 1000)); // Delay entre chamadas
        }
    }
}
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php include VIEW_PATH . '/layouts/layout.php'; ?>