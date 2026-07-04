<?php 
$tituloPagina = 'Mapear Serviços - Etapa 2A'; 

// Garantir que existe um CSRF token válido
if (!Session::get('csrf_token')) {
    Session::set('csrf_token', Csrf::gerar());
}
$csrfToken = Session::get('csrf_token');
?>
<?php ob_start(); ?>

<!-- CSRF Token -->
<meta name="csrf-token" content="<?= $csrfToken ?>">

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
                        onclick="mapearSetorPorIndex(<?= $index ?>)"
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
                
                <!-- Gerenciamento Manual (initially hidden) -->
                <div class="mt-4 p-4 bg-purple-50 border border-purple-200 rounded-lg hidden" id="gerenciamento-manual-<?= $index ?>">
                    <h5 class="font-medium text-purple-900 mb-3">✏️ Gerenciamento Manual de Serviços</h5>
                    
                    <!-- Adicionar Serviço -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-purple-800 mb-2">Adicionar Novo Serviço:</label>
                        <div class="flex space-x-2">
                            <input type="text" 
                                   id="novo-servico-nome-<?= $index ?>" 
                                   placeholder="Nome do serviço (ex: Análise de performance mensal)" 
                                   class="flex-1 px-3 py-2 border border-purple-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                                   onkeypress="if(event.key==='Enter') adicionarServicoManual(<?= $index ?>)">
                            <select id="novo-servico-criticidade-<?= $index ?>" 
                                    class="px-3 py-2 border border-purple-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="baixa">Baixa</option>
                                <option value="media" selected>Média</option>
                                <option value="alta">Alta</option>
                            </select>
                            <button onclick="adicionarServicoManual(<?= $index ?>)" 
                                    class="px-4 py-2 bg-purple-600 text-white text-sm rounded-lg hover:bg-purple-700 transition">
                                ➕ Adicionar
                            </button>
                        </div>
                    </div>
                    
                    <!-- Lista de Serviços Manuais -->
                    <div id="servicos-manuais-<?= $index ?>">
                        <!-- Serviços manuais serão listados aqui -->
                    </div>
                    
                    <div class="flex justify-between items-center mt-4 pt-3 border-t border-purple-200">
                        <button onclick="ocultarGerenciamentoManual(<?= $index ?>)" 
                                class="text-sm text-purple-600 hover:text-purple-800">
                            ← Fechar Gerenciamento
                        </button>
                        <div class="text-xs text-purple-600">
                            Serviços manuais são salvos automaticamente
                        </div>
                    </div>
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
        
        <!-- Botão para Regenerar CSRF -->
        <button type="button" 
                onclick="regenerarCSRF()"
                class="px-6 py-2 border border-red-300 text-red-700 rounded-lg hover:bg-red-50">
            🔄 Regenerar Token
        </button>
        
        <!-- Botão de Teste Básico JavaScript -->
        <button type="button" 
                onclick="testeBasicoJS()"
                class="px-6 py-2 border border-blue-300 text-blue-700 rounded-lg hover:bg-blue-50">
            🧪 Teste JS
        </button>
        
        <!-- Botão para Executar Migração (Debug) -->
        <button type="button" 
                onclick="executarMigracaoTabelas()"
                class="px-6 py-2 border border-purple-300 text-purple-700 rounded-lg hover:bg-purple-50">
            🗃️ Verificar Tabelas
        </button>
        
        <!-- Botão para Diagnóstico Completo -->
        <button type="button" 
                onclick="diagnosticoCompleto()"
                class="px-6 py-2 border border-green-300 text-green-700 rounded-lg hover:bg-green-50">
            🔍 Diagnóstico Completo
        </button>
        
        <!-- Botão de Teste Direto -->
        <button type="button" 
                onclick="mapearSetorPorIndex(0)"
                class="px-6 py-2 border border-orange-300 text-orange-700 rounded-lg hover:bg-orange-50">
            🚀 Teste: Mapear Primeiro Setor
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
// Dados seguros via JSON
const dadosIniciais = <?= json_encode([
    'estruturaId' => (int) $dados['estrutura_id'],
    'totalSetores' => count($dados['setores']),
    'setores' => array_values($dados['setores']),
    'setoresNomes' => array_column($dados['setores'], 'nome_setor'),
    'appUrl' => APP_URL
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

// Variáveis globais seguras
let estruturaId = dadosIniciais.estruturaId;
let setoresMapeados = 0;
let totalSetores = dadosIniciais.totalSetores;
let appUrl = dadosIniciais.appUrl;

console.log('=== INICIALIZAÇÃO SEGURA ===');
console.log('Estrutura ID:', estruturaId);
console.log('Total Setores:', totalSetores);
console.log('Setores:', dadosIniciais.setoresNomes);

// Wrapper function to safely call mapearSetor using index
function mapearSetorPorIndex(index) {
    console.log('🔥 WRAPPER: mapearSetorPorIndex chamada com index:', index);
    
    if (!dadosIniciais.setoresNomes || !dadosIniciais.setoresNomes[index]) {
        console.error('ERRO: Setor não encontrado no índice:', index);
        alert('Erro: Setor não encontrado. Recarregue a página.');
        return;
    }
    
    const setorNome = dadosIniciais.setoresNomes[index];
    console.log('🔥 WRAPPER: Chamando mapearSetor com:', setorNome, index);
    
    return mapearSetor(setorNome, index);
}

// Mapear serviços de um setor específico
async function mapearSetor(setorNome, index) {
    console.log('🔥 FUNÇÃO MAPEAR SETOR CHAMADA 🔥');
    console.log('=== INICIANDO MAPEAMENTO ===');
    console.log('Setor:', setorNome, 'Tipo:', typeof setorNome);
    console.log('Index:', index, 'Tipo:', typeof index);
    console.log('URL da API:', '<?= APP_URL ?>/sop/executar-mapeamento-setor');
    
    // TESTE IMEDIATO: Verificar se chegou até aqui
    alert(`🔥 TESTE: Função mapearSetor chamada!\nSetor: ${setorNome}\nIndex: ${index}`);
    
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
    
    console.log('🔍 ELEMENTOS ENCONTRADOS:', {
        btnMapear: !!btnMapear,
        loading: !!loading, 
        status: !!status,
        resultado: !!resultado
    });
    
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
        const response = await fetch(appUrl + '/sop/executar-mapeamento-setor', {
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
        console.log('Status HTTP:', response.status);
        console.log('Headers da resposta:', Object.fromEntries(response.headers.entries()));

        let result;
        try {
            result = JSON.parse(responseText);
            console.log('JSON parseado com sucesso:', result);
            
            // Log detalhado do resultado
            if (result.sucesso) {
                console.log('✅ SUCESSO - Detalhes:', {
                    setor: result.setor,
                    total_servicos: result.total_servicos,
                    servico_mapeado_id: result.servico_mapeado_id,
                    tempo_processamento: result.tempo_processamento,
                    funcao_principal: result.funcao_principal,
                    servicos_nomes: result.servicos ? result.servicos.map(s => s.nome) : []
                });
            } else {
                console.error('❌ ERRO - Detalhes:', {
                    erro: result.erro,
                    codigo_erro: result.codigo_erro,
                    resultado_completo: result
                });
            }
            
        } catch (parseError) {
            console.error('ERRO ao fazer parse do JSON:', parseError);
            console.error('Resposta completa:', responseText);
            console.error('Possíveis problemas:', {
                'contem_html': responseText.includes('<html') || responseText.includes('<!DOCTYPE'),
                'contem_php_error': responseText.includes('PHP Parse error') || responseText.includes('PHP Fatal error'),
                'comeca_com_warning': responseText.trim().startsWith('Warning:'),
                'tamanho_resposta': responseText.length,
                'primeiro_char': responseText.charAt(0),
                'ultimos_100_chars': responseText.slice(-100)
            });
            
            // Tentar identificar o problema na resposta
            if (responseText.includes('PHP Parse error') || responseText.includes('PHP Fatal error')) {
                throw new Error('Erro PHP no servidor. Verifique os logs do servidor.\n\nErro: ' + responseText.substring(0, 200));
            } else if (responseText.includes('<html') || responseText.includes('<!DOCTYPE')) {
                throw new Error('Servidor retornou HTML ao invés de JSON. Possível erro de rota ou redirecionamento.');
            } else if (responseText.trim().startsWith('Warning:')) {
                throw new Error('Warning PHP está interferindo na resposta JSON.\n\nWarning: ' + responseText.substring(0, 300));
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
                        `<div class="text-sm p-3 bg-white rounded border border-gray-200 hover:border-blue-300 hover:shadow-sm transition-all group" id="servico-${index}-${i}">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="font-medium text-gray-800 group-hover:text-blue-600">${s.nome || s.titulo || s.servico || `Serviço ${i + 1}`}</div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        <span>Criticidade: ${s.criticidade || s.prioridade || 'Média'}</span>
                                        <span class="ml-3">Categoria: ${s.categoria || 'N/A'}</span>
                                        <span class="ml-3 px-1 py-0.5 bg-gray-100 rounded text-gray-600">Auto</span>
                                    </div>
                                </div>
                                <div class="flex space-x-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button onclick="detalharServicoIndividual(${index}, ${i}, '${(s.nome || s.titulo || s.servico || `Serviço ${i + 1}`).replace(/'/g, "\\'")}'); event.stopPropagation();" 
                                            class="px-2 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 transition"
                                            title="Detalhar este serviço">
                                        📋
                                    </button>
                                    <button onclick="gerarSopIndividual(${index}, ${i}, '${(s.nome || s.titulo || s.servico || `Serviço ${i + 1}`).replace(/'/g, "\\'")}'); event.stopPropagation();" 
                                            class="px-2 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700 transition"
                                            title="Gerar SOP">
                                        🔧
                                    </button>
                                    <button onclick="excluirServicoMapeado(${index}, ${i}); event.stopPropagation();" 
                                            class="px-2 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700 transition"
                                            title="Excluir este serviço">
                                        🗑️
                                    </button>
                                </div>
                            </div>
                        </div>`
                    ).join('');
                } else {
                    listaServicos.innerHTML = '<div class="text-sm text-gray-500 italic">Nenhum serviço específico foi mapeado.</div>';
                }
            }
            
            if (totalServicos) {
                totalServicos.innerHTML = `
                    <div class="flex items-center justify-between">
                        <span>${result.total_servicos || 0} serviços identificados</span>
                        <button onclick="mostrarGerenciamentoManual(${index})" 
                                class="text-xs px-2 py-1 bg-purple-100 text-purple-700 rounded hover:bg-purple-200 transition">
                            ✏️ Gerenciar Manualmente
                        </button>
                    </div>
                `;
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
            console.error('Código do erro:', result.codigo_erro);
            
            // Erro
            if (status) status.innerHTML = '<span class="px-3 py-1 text-sm bg-red-100 text-red-600 rounded-full">❌ Erro</span>';
            btnMapear.textContent = '🔄 Tentar Novamente';
            btnMapear.disabled = false;
            
            const mensagemErro = result.erro || 'Erro desconhecido no servidor';
            const codigoErro = result.codigo_erro ? `\n\nCódigo: ${result.codigo_erro}` : '';
            
            // Sugestões baseadas no tipo de erro
            let sugestao = '';
            if (result.codigo_erro === 'DADOS_INCOMPLETOS') {
                sugestao = '\n\nSolução: Os dados do diagnóstico estão incompletos. Recarregue a página ou refaça o diagnóstico.';
            } else if (result.codigo_erro === 'API_ERROR') {
                sugestao = '\n\nSolução: Erro na comunicação com a IA. Tente novamente em alguns segundos.';
            }
            
            alert('Erro no mapeamento:\n\n' + mensagemErro + codigoErro + sugestao);
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

// Função de diagnóstico completo do sistema
function diagnosticoCompleto() {
    console.log('🔍 INICIANDO DIAGNÓSTICO COMPLETO DO SISTEMA');
    
    const diagnostico = {
        timestamp: new Date().toISOString(),
        url_atual: window.location.href,
        user_agent: navigator.userAgent,
        
        // Variáveis JavaScript
        variaveis: {
            estruturaId: typeof estruturaId !== 'undefined' ? estruturaId : 'UNDEFINED',
            totalSetores: typeof totalSetores !== 'undefined' ? totalSetores : 'UNDEFINED',
            setoresMapeados: typeof setoresMapeados !== 'undefined' ? setoresMapeados : 'UNDEFINED',
            appUrl: typeof appUrl !== 'undefined' ? appUrl : 'UNDEFINED'
        },
        
        // Dados iniciais
        dados_iniciais: typeof dadosIniciais !== 'undefined' ? dadosIniciais : 'UNDEFINED',
        
        // CSRF
        csrf_meta_existe: !!document.querySelector('meta[name="csrf-token"]'),
        csrf_token: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        
        // Elementos DOM
        elementos: {},
        
        // Funções
        funcoes: {
            mapearSetor: typeof mapearSetor === 'function',
            mapearSetorPorIndex: typeof mapearSetorPorIndex === 'function',
            testarConexaoAPI: typeof testarConexaoAPI === 'function',
            regenerarCSRF: typeof regenerarCSRF === 'function'
        }
    };
    
    // Verificar elementos DOM para cada setor
    for (let i = 0; i < (diagnostico.variaveis.totalSetores || 0); i++) {
        diagnostico.elementos[`setor_${i}`] = {
            botao: !!document.getElementById(`btn-mapear-${i}`),
            loading: !!document.getElementById(`loading-${i}`),
            status: !!document.getElementById(`status-setor-${i}`),
            resultado: !!document.getElementById(`resultado-${i}`),
            onclick_attr: document.getElementById(`btn-mapear-${i}`)?.getAttribute('onclick')
        };
    }
    
    // Verificar console errors
    if (window.console && console.error) {
        console.log('📊 DIAGNÓSTICO COMPLETO:', diagnostico);
    }
    
    // Mostrar relatório
    const relatorio = `🔍 DIAGNÓSTICO COMPLETO DO SISTEMA:

📍 LOCALIZAÇÃO:
URL: ${diagnostico.url_atual}
Timestamp: ${diagnostico.timestamp}

🔧 VARIÁVEIS:
estruturaId: ${diagnostico.variaveis.estruturaId}
totalSetores: ${diagnostico.variaveis.totalSetores}
setoresMapeados: ${diagnostico.variaveis.setoresMapeados}
appUrl: ${diagnostico.variaveis.appUrl}

🔐 CSRF:
Meta tag existe: ${diagnostico.csrf_meta_existe ? 'SIM' : 'NÃO'}
Token presente: ${diagnostico.csrf_token ? 'SIM (' + diagnostico.csrf_token.length + ' chars)' : 'NÃO'}

⚙️ FUNÇÕES:
mapearSetor: ${diagnostico.funcoes.mapearSetor ? 'EXISTE' : 'AUSENTE'}
mapearSetorPorIndex: ${diagnostico.funcoes.mapearSetorPorIndex ? 'EXISTE' : 'AUSENTE'}
testarConexaoAPI: ${diagnostico.funcoes.testarConexaoAPI ? 'EXISTE' : 'AUSENTE'}
regenerarCSRF: ${diagnostico.funcoes.regenerarCSRF ? 'EXISTE' : 'AUSENTE'}

📋 ELEMENTOS DOM:
${Object.keys(diagnostico.elementos).map(setor => {
    const el = diagnostico.elementos[setor];
    return `${setor}: Btn=${el.botao ? '✅' : '❌'} Loading=${el.loading ? '✅' : '❌'} Status=${el.status ? '✅' : '❌'} Result=${el.resultado ? '✅' : '❌'}`;
}).join('\n')}

Verifique o console do navegador para detalhes completos.`;

    alert(relatorio);
    
    return diagnostico;
}

// Detalhar um serviço individual
async function detalharServicoIndividual(setorIndex, servicoIndex, servicoNome) {
    console.log('📋 DETALHANDO SERVIÇO INDIVIDUAL:', {
        setorIndex, 
        servicoIndex, 
        servicoNome,
        estruturaId
    });
    
    try {
        // Confirmação do usuário
        const confirmacao = confirm(`📋 DETALHAR SERVIÇO:\n\n"${servicoNome}"\n\nIsto irá gerar detalhamentos específicos para este serviço incluindo:\n• Processos N1, N2, N3\n• Cenários de problemas\n• Estratégias de contenção\n\nContinuar?`);
        
        if (!confirmacao) {
            return;
        }
        
        // Obter CSRF token
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
        
        if (!csrfToken) {
            throw new Error('Token CSRF não encontrado. Recarregue a página.');
        }
        
        // Preparar dados
        const formData = new FormData();
        formData.append('estrutura_id', String(estruturaId));
        formData.append('setor_index', String(setorIndex));
        formData.append('servico_index', String(servicoIndex));
        formData.append('servico_nome', servicoNome);
        formData.append('csrf_token', csrfToken);
        
        // Fazer requisição
        console.log('🔄 Fazendo requisição de detalhamento...');
        const response = await fetch(appUrl + '/sop/detalhar-servico-individual', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`HTTP ${response.status}: ${errorText}`);
        }
        
        const result = await response.json();
        
        if (result.sucesso) {
            console.log('✅ Detalhamento gerado:', result);
            
            // Abrir em nova aba ou mostrar modal
            const url = `${appUrl}/sop/ver-detalhamento-servico?detalhamento_id=${result.detalhamento_id}`;
            window.open(url, '_blank');
            
            alert(`✅ Detalhamento gerado com sucesso!\n\n• ${result.total_cenarios || 0} cenários identificados\n• ${result.total_processos || 0} processos mapeados\n\nAbrindo em nova aba...`);
            
        } else {
            throw new Error(result.erro || 'Erro desconhecido no detalhamento');
        }
        
    } catch (error) {
        console.error('❌ Erro no detalhamento:', error);
        alert(`❌ Erro ao detalhar serviço:\n\n${error.message}\n\nTente novamente ou contate o suporte.`);
    }
}

// Gerar SOP de um serviço individual
async function gerarSopIndividual(setorIndex, servicoIndex, servicoNome) {
    console.log('🔧 GERANDO SOP INDIVIDUAL:', {
        setorIndex, 
        servicoIndex, 
        servicoNome,
        estruturaId
    });
    
    try {
        // Confirmação do usuário
        const confirmacao = confirm(`🔧 GERAR SOP COMPLETO:\n\n"${servicoNome}"\n\nIsto irá gerar um SOP completo para este serviço específico incluindo:\n• Procedimentos operacionais\n• Fluxos de trabalho\n• Checklist de qualidade\n• Procedimentos de emergência\n\nContinuar?`);
        
        if (!confirmacao) {
            return;
        }
        
        // Obter CSRF token
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
        
        if (!csrfToken) {
            throw new Error('Token CSRF não encontrado. Recarregue a página.');
        }
        
        // Preparar dados
        const formData = new FormData();
        formData.append('estrutura_id', String(estruturaId));
        formData.append('setor_index', String(setorIndex));
        formData.append('servico_index', String(servicoIndex));
        formData.append('servico_nome', servicoNome);
        formData.append('csrf_token', csrfToken);
        
        // Fazer requisição
        console.log('🔄 Fazendo requisição de geração de SOP...');
        const response = await fetch(appUrl + '/sop/gerar-sop-individual', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`HTTP ${response.status}: ${errorText}`);
        }
        
        const result = await response.json();
        
        if (result.sucesso) {
            console.log('✅ SOP gerado:', result);
            
            // Abrir em nova aba
            const url = `${appUrl}/sop/ver-sop-individual?sop_id=${result.sop_id}`;
            window.open(url, '_blank');
            
            alert(`✅ SOP gerado com sucesso!\n\n• ${result.total_procedimentos || 0} procedimentos criados\n• ${result.total_checklists || 0} checklists incluídos\n\nAbrindo em nova aba...`);
            
        } else {
            throw new Error(result.erro || 'Erro desconhecido na geração do SOP');
        }
        
    } catch (error) {
        console.error('❌ Erro na geração do SOP:', error);
        alert(`❌ Erro ao gerar SOP:\n\n${error.message}\n\nTente novamente ou contate o suporte.`);
    }
}

// Excluir um serviço mapeado
function excluirServicoMapeado(setorIndex, servicoIndex) {
    console.log('🗑️ EXCLUINDO SERVIÇO:', {setorIndex, servicoIndex});
    
    const confirmacao = confirm('Tem certeza que deseja excluir este serviço do mapeamento?');
    if (!confirmacao) return;
    
    try {
        // Remove o elemento da tela
        const servicoElement = document.getElementById(`servico-${setorIndex}-${servicoIndex}`);
        if (servicoElement) {
            servicoElement.remove();
        }
        
        // Atualizar contador
        const totalElement = document.getElementById(`total-servicos-${setorIndex}`);
        if (totalElement) {
            const currentCount = document.querySelectorAll(`#lista-servicos-${setorIndex} > div`).length;
            totalElement.querySelector('span').textContent = `${currentCount} serviços identificados`;
        }
        
        console.log('✅ Serviço excluído da interface');
        
    } catch (error) {
        console.error('❌ Erro ao excluir serviço:', error);
        alert('Erro ao excluir serviço. Recarregue a página e tente novamente.');
    }
}

// Mostrar painel de gerenciamento manual
function mostrarGerenciamentoManual(setorIndex) {
    console.log('✏️ MOSTRANDO GERENCIAMENTO MANUAL para setor:', setorIndex);
    
    const painelGerenciamento = document.getElementById(`gerenciamento-manual-${setorIndex}`);
    if (painelGerenciamento) {
        painelGerenciamento.classList.remove('hidden');
        
        // Focar no campo de input
        const inputNome = document.getElementById(`novo-servico-nome-${setorIndex}`);
        if (inputNome) {
            inputNome.focus();
        }
        
        // Carregar serviços manuais existentes
        carregarServicosManuais(setorIndex);
    }
}

// Ocultar painel de gerenciamento manual
function ocultarGerenciamentoManual(setorIndex) {
    console.log('✏️ OCULTANDO GERENCIAMENTO MANUAL para setor:', setorIndex);
    
    const painelGerenciamento = document.getElementById(`gerenciamento-manual-${setorIndex}`);
    if (painelGerenciamento) {
        painelGerenciamento.classList.add('hidden');
    }
}

// Adicionar serviço manual
async function adicionarServicoManual(setorIndex) {
    console.log('➕ ADICIONANDO SERVIÇO MANUAL para setor:', setorIndex);
    
    const inputNome = document.getElementById(`novo-servico-nome-${setorIndex}`);
    const selectCriticidade = document.getElementById(`novo-servico-criticidade-${setorIndex}`);
    
    if (!inputNome || !selectCriticidade) {
        alert('Elementos do formulário não encontrados.');
        return;
    }
    
    const nomeServico = inputNome.value.trim();
    const criticidade = selectCriticidade.value;
    
    if (!nomeServico) {
        alert('Por favor, informe o nome do serviço.');
        inputNome.focus();
        return;
    }
    
    try {
        // Obter CSRF token
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
        
        if (!csrfToken) {
            throw new Error('Token CSRF não encontrado. Recarregue a página.');
        }
        
        // Obter dados do setor
        const setorNome = dadosIniciais.setores[setorIndex]?.nome_setor;
        if (!setorNome) {
            throw new Error('Setor não encontrado.');
        }
        
        // Preparar dados
        const formData = new FormData();
        formData.append('estrutura_id', String(estruturaId));
        formData.append('setor_index', String(setorIndex));
        formData.append('setor_nome', setorNome);
        formData.append('servico_nome', nomeServico);
        formData.append('criticidade', criticidade);
        formData.append('csrf_token', csrfToken);
        
        console.log('🔄 Salvando serviço manual...', {nomeServico, criticidade, setorNome});
        
        // Fazer requisição
        const response = await fetch(appUrl + '/sop/adicionar-servico-manual', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`HTTP ${response.status}: ${errorText}`);
        }
        
        const result = await response.json();
        
        if (result.sucesso) {
            console.log('✅ Serviço manual adicionado:', result);
            
            // Limpar formulário
            inputNome.value = '';
            selectCriticidade.value = 'media';
            
            // Recarregar lista de serviços manuais
            carregarServicosManuais(setorIndex);
            
            // Feedback visual
            inputNome.classList.add('border-green-500');
            setTimeout(() => {
                inputNome.classList.remove('border-green-500');
            }, 2000);
            
        } else {
            throw new Error(result.erro || 'Erro desconhecido ao adicionar serviço');
        }
        
    } catch (error) {
        console.error('❌ Erro ao adicionar serviço manual:', error);
        alert(`❌ Erro ao adicionar serviço:\n\n${error.message}`);
    }
}

// Carregar serviços manuais existentes
async function carregarServicosManuais(setorIndex) {
    console.log('📂 CARREGANDO SERVIÇOS MANUAIS para setor:', setorIndex);
    
    try {
        const response = await fetch(`${appUrl}/sop/listar-servicos-manuais?estrutura_id=${estruturaId}&setor_index=${setorIndex}`, {
            method: 'GET'
        });
        
        if (response.ok) {
            const result = await response.json();
            
            if (result.sucesso && result.servicos) {
                exibirServicosManuais(setorIndex, result.servicos);
            }
        }
        
    } catch (error) {
        console.error('❌ Erro ao carregar serviços manuais:', error);
    }
}

// Exibir serviços manuais na interface
function exibirServicosManuais(setorIndex, servicos) {
    const container = document.getElementById(`servicos-manuais-${setorIndex}`);
    if (!container) return;
    
    if (!servicos || servicos.length === 0) {
        container.innerHTML = '<div class="text-sm text-purple-600 italic">Nenhum serviço manual adicionado ainda.</div>';
        return;
    }
    
    container.innerHTML = servicos.map((servico, i) => `
        <div class="flex items-center justify-between p-2 bg-white border border-purple-200 rounded mb-2">
            <div class="flex-1">
                <div class="font-medium text-purple-900">${servico.nome}</div>
                <div class="text-xs text-purple-600">
                    Criticidade: ${servico.criticidade} • 
                    <span class="px-1 py-0.5 bg-purple-100 rounded">Manual</span>
                </div>
            </div>
            <div class="flex space-x-1">
                <button onclick="detalharServicoManual('${servico.nome}', ${setorIndex})" 
                        class="px-2 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700"
                        title="Detalhar">
                    📋
                </button>
                <button onclick="gerarSopServicoManual('${servico.nome}', ${setorIndex})" 
                        class="px-2 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700"
                        title="Gerar SOP">
                    🔧
                </button>
                <button onclick="excluirServicoManual('${servico.nome}', ${setorIndex})" 
                        class="px-2 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700"
                        title="Excluir">
                    🗑️
                </button>
            </div>
        </div>
    `).join('');
}

// Detalhar serviço manual
async function detalharServicoManual(servicoNome, setorIndex) {
    console.log('📋 DETALHANDO SERVIÇO MANUAL:', {servicoNome, setorIndex});
    return detalharServicoIndividual(setorIndex, -1, servicoNome);
}

// Gerar SOP de serviço manual
async function gerarSopServicoManual(servicoNome, setorIndex) {
    console.log('🔧 GERANDO SOP DE SERVIÇO MANUAL:', {servicoNome, setorIndex});
    return gerarSopIndividual(setorIndex, -1, servicoNome);
}

// Excluir serviço manual
async function excluirServicoManual(servicoNome, setorIndex) {
    console.log('🗑️ EXCLUINDO SERVIÇO MANUAL:', {servicoNome, setorIndex});
    
    const confirmacao = confirm(`Tem certeza que deseja excluir o serviço manual "${servicoNome}"?`);
    if (!confirmacao) return;
    
    try {
        // Obter CSRF token
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
        
        if (!csrfToken) {
            throw new Error('Token CSRF não encontrado.');
        }
        
        // Preparar dados
        const formData = new FormData();
        formData.append('estrutura_id', String(estruturaId));
        formData.append('setor_index', String(setorIndex));
        formData.append('servico_nome', servicoNome);
        formData.append('csrf_token', csrfToken);
        
        // Fazer requisição
        const response = await fetch(appUrl + '/sop/excluir-servico-manual', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.sucesso) {
            console.log('✅ Serviço manual excluído');
            carregarServicosManuais(setorIndex);
        } else {
            throw new Error(result.erro || 'Erro ao excluir');
        }
        
    } catch (error) {
        console.error('❌ Erro ao excluir serviço manual:', error);
        alert(`❌ Erro ao excluir serviço:\n\n${error.message}`);
    }
}

// Prosseguir para Etapa 2B
function prosseguirEtapa2B() {
    if (setoresMapeados < totalSetores) {
        alert('É necessário mapear todos os setores primeiro.');
        return;
    }
    
    window.location.href = `${appUrl}/sop/detalhar-servicos?estrutura_id=${estruturaId}`;
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
        console.log('URL da API:', appUrl + '/sop/executar-mapeamento-setor');
        console.log('Estrutura ID:', estruturaId);
        console.log('Total setores:', totalSetores);
        
        // Testar CSRF token
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
        console.log('CSRF Token encontrado:', csrfToken ? 'SIM' : 'NÃO');
        console.log('CSRF Token valor:', csrfToken);
        
        if (!csrfToken) {
            alert('ERRO: CSRF Token não encontrado!\n\nRecarregue a página.');
            return;
        }
        
        // Fazer uma requisição de teste com dados mínimos válidos
        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('estrutura_id', '1'); // Valor de teste
        formData.append('setor_nome', 'TESTE'); // Valor de teste
        
        const response = await fetch(appUrl + '/sop/executar-mapeamento-setor', {
            method: 'POST',
            body: formData
        });
        
        console.log('Teste - Status:', response.status);
        console.log('Teste - Headers:', Object.fromEntries(response.headers.entries()));
        
        const responseText = await response.text();
        console.log('Teste - Resposta completa:', responseText);
        
        let result = null;
        try {
            result = JSON.parse(responseText);
            console.log('Teste - JSON:', result);
        } catch (e) {
            console.log('Teste - Não é JSON válido');
        }
        
        if (response.status === 200) {
            if (result && result.sucesso === false) {
                alert(`⚠️ API RESPONDE MAS COM ERRO\n\nErro: ${result.erro}\nCódigo: ${result.codigo_erro || 'N/A'}`);
            } else {
                alert('✅ API FUNCIONANDO\n\nA API está respondendo corretamente.');
            }
        } else if (response.status === 403) {
            alert(`❌ ERRO 403 - ACESSO NEGADO\n\nProblema de autenticação ou CSRF.\n\nResposta: ${responseText.substring(0, 200)}`);
        } else {
            alert(`❌ ERRO HTTP ${response.status}\n\nResposta: ${responseText.substring(0, 200)}`);
        }
        
    } catch (error) {
        console.error('Erro no teste:', error);
        alert(`❌ ERRO DE CONEXÃO\n\nErro: ${error.message}\n\nVerifique o console para mais detalhes.`);
    }
}

// Regenerar token CSRF
async function regenerarCSRF() {
    console.log('=== REGENERANDO TOKEN CSRF ===');
    
    try {
        const response = await fetch(appUrl + '/sop/regenerar-csrf', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        });
        
        if (response.ok) {
            const result = await response.json();
            
            if (result.sucesso) {
                // Atualizar meta tag
                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                if (csrfMeta) {
                    csrfMeta.setAttribute('content', result.novo_token);
                    console.log('Novo token recebido do servidor:', result.novo_token);
                    
                    // Atualizar status visual
                    const csrfStatus = document.getElementById('csrf-status');
                    if (csrfStatus) {
                        csrfStatus.textContent = '✅ Renovado';
                        csrfStatus.className = 'font-mono text-green-600';
                    }
                    
                    alert('✅ Token CSRF regenerado com sucesso!\n\nAgora tente usar os botões de mapeamento.');
                }
            } else {
                throw new Error('Servidor retornou erro: ' + (result.erro || 'Desconhecido'));
            }
        } else {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
    } catch (error) {
        console.error('Erro ao regenerar CSRF:', error);
        
        // Fallback: gerar token temporário local
        const novoToken = 'csrf_local_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (csrfMeta) {
            csrfMeta.setAttribute('content', novoToken);
            
            const csrfStatus = document.getElementById('csrf-status');
            if (csrfStatus) {
                csrfStatus.textContent = '⚠️ Local';
                csrfStatus.className = 'font-mono text-orange-600';
            }
        }
        
        alert(`⚠️ Erro ao regenerar token no servidor.\n\nErro: ${error.message}\n\nFoi gerado um token local temporário, mas recomenda-se recarregar a página.`);
    }
}
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 PÁGINA CARREGADA - INICIANDO VERIFICAÇÕES 🚀');
    
    // Verificar se a função mapearSetor existe
    if (typeof mapearSetor === 'function') {
        console.log('✅ Função mapearSetor encontrada');
    } else {
        console.error('❌ Função mapearSetor NÃO encontrada');
        alert('ERRO CRÍTICO: Função mapearSetor não foi carregada!');
        return;
    }
    
    // Verificar variáveis globais
    console.log('📊 VARIÁVEIS GLOBAIS:', {
        estruturaId: typeof estruturaId !== 'undefined' ? estruturaId : 'UNDEFINED',
        totalSetores: typeof totalSetores !== 'undefined' ? totalSetores : 'UNDEFINED',
        setoresMapeados: typeof setoresMapeados !== 'undefined' ? setoresMapeados : 'UNDEFINED'
    });
    
    // Atualizar status do CSRF
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
    const csrfStatus = document.getElementById('csrf-status');
    
    console.log('🔐 CSRF TOKEN STATUS:', {
        meta_existe: !!csrfMeta,
        token_existe: !!csrfToken,
        token_tamanho: csrfToken ? csrfToken.length : 0,
        status_element: !!csrfStatus
    });
    
    if (csrfStatus) {
        if (csrfToken) {
            csrfStatus.textContent = '✅ Presente';
            csrfStatus.className = 'font-mono text-green-600';
        } else {
            csrfStatus.textContent = '❌ Ausente';
            csrfStatus.className = 'font-mono text-red-600';
            console.error('🚨 CSRF TOKEN AUSENTE!');
        }
    }
    
    // Verificar se todos os elementos necessários existem
    console.log('🔍 VERIFICAÇÃO DE ELEMENTOS:');
    let problemasEncontrados = 0;
    
    for (let i = 0; i < (typeof totalSetores !== 'undefined' ? totalSetores : 0); i++) {
        const btn = document.getElementById(`btn-mapear-${i}`);
        const loading = document.getElementById(`loading-${i}`);
        const status = document.getElementById(`status-setor-${i}`);
        const resultado = document.getElementById(`resultado-${i}`);
        
        const elementosOk = {
            botao: !!btn,
            loading: !!loading,
            status: !!status,
            resultado: !!resultado
        };
        
        console.log(`Setor ${i}:`, elementosOk);
        
        if (!btn) {
            console.error(`❌ Botão ${i} NÃO encontrado`);
            problemasEncontrados++;
        }
        
        // Teste do onclick
        if (btn && btn.onclick) {
            console.log(`✅ Botão ${i} tem evento onclick`);
        } else if (btn) {
            console.warn(`⚠️ Botão ${i} SEM evento onclick`);
        }
    }
    
    if (problemasEncontrados > 0) {
        console.error(`🚨 ${problemasEncontrados} problemas encontrados nos elementos!`);
        alert(`AVISO: ${problemasEncontrados} elementos não foram encontrados. Verifique o console.`);
    } else {
        console.log('✅ Todos os elementos encontrados com sucesso!');
    }
    
    // Se quiser iniciar automaticamente, descomente a linha abaixo:
    // mapearTodosSetores();
});

// Função para mapear todos automaticamente
async function mapearTodosSetores() {
    const setores = dadosIniciais.setoresNomes;
    
    for (let i = 0; i < totalSetores; i++) {
        if (setores[i]) {
            await mapearSetor(setores[i], i);
            await new Promise(resolve => setTimeout(resolve, 1000)); // Delay entre chamadas
        }
    }
}

// Teste básico de JavaScript e DOM
function testeBasicoJS() {
    console.log('🧪 INICIANDO TESTE BÁSICO JavaScript 🧪');
    
    try {
        // 1. Verificar variáveis globais
        const vars = {
            estruturaId: typeof estruturaId !== 'undefined' ? estruturaId : 'UNDEFINED',
            totalSetores: typeof totalSetores !== 'undefined' ? totalSetores : 'UNDEFINED',
            setoresMapeados: typeof setoresMapeados !== 'undefined' ? setoresMapeados : 'UNDEFINED',
            APP_URL: appUrl
        };
        
        console.log('📊 Variáveis:', vars);
        
        // 2. Verificar função mapearSetor
        const funcaoExiste = typeof mapearSetor === 'function';
        console.log('🔧 Função mapearSetor existe:', funcaoExiste);
        
        // 3. Testar primeiro botão se existir
        const primeiroBtn = document.getElementById('btn-mapear-0');
        console.log('🔘 Primeiro botão existe:', !!primeiroBtn);
        
        if (primeiroBtn) {
            console.log('🔘 Onclick do botão:', primeiroBtn.getAttribute('onclick'));
        }
        
        // 4. Verificar CSRF
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        console.log('🔐 CSRF Token:', csrfToken ? 'Presente (' + csrfToken.length + ' chars)' : 'AUSENTE');
        
        // 5. Teste de chamada manual
        if (funcaoExiste && typeof estruturaId !== 'undefined' && estruturaId) {
            const confirmacao = confirm('🧪 TESTE: Deseja tentar chamar mapearSetor(0) manualmente?');
            if (confirmacao) {
                console.log('🚀 Chamando mapearSetor manualmente...');
                // Não vamos chamar porque pode dar problema, só simular
                alert('✅ Teste simulado. Verifique o console para detalhes.');
            }
        }
        
        // Relatório
        const relatorio = `🧪 RELATÓRIO DO TESTE:
        
✅ JavaScript funcionando: SIM
${funcaoExiste ? '✅' : '❌'} Função mapearSetor: ${funcaoExiste ? 'EXISTE' : 'NÃO EXISTE'}
${vars.estruturaId !== 'UNDEFINED' ? '✅' : '❌'} estruturaId: ${vars.estruturaId}
${vars.totalSetores !== 'UNDEFINED' ? '✅' : '❌'} totalSetores: ${vars.totalSetores}
${!!primeiroBtn ? '✅' : '❌'} Primeiro botão: ${!!primeiroBtn ? 'ENCONTRADO' : 'NÃO ENCONTRADO'}
${!!csrfToken ? '✅' : '❌'} CSRF Token: ${!!csrfToken ? 'PRESENTE' : 'AUSENTE'}
        
Verifique o console para mais detalhes.`;

        alert(relatorio);
        
    } catch (error) {
        console.error('❌ ERRO no teste básico:', error);
        alert('❌ ERRO no teste básico: ' + error.message);
    }
}

// Executar migração das tabelas da nova arquitetura
async function executarMigracaoTabelas() {
    console.log('🗃️ EXECUTANDO VERIFICAÇÃO DAS TABELAS');
    
    try {
        // Obter CSRF token
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
        
        if (!csrfToken) {
            alert('ERRO: Token CSRF não encontrado. Recarregue a página.');
            return;
        }
        
        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        
        const response = await fetch(appUrl + '/sop/verificar-criar-tabelas', {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            const result = await response.json();
            
            if (result.sucesso) {
                console.log('✅ Resultado da verificação:', result);
                
                let mensagem = '✅ VERIFICAÇÃO DE TABELAS CONCLUÍDA\n\n';
                if (result.tabelas_criadas && result.tabelas_criadas.length > 0) {
                    mensagem += `Tabelas criadas: ${result.tabelas_criadas.join(', ')}\n\n`;
                }
                if (result.tabelas_existentes && result.tabelas_existentes.length > 0) {
                    mensagem += `Tabelas já existentes: ${result.tabelas_existentes.join(', ')}\n\n`;
                }
                mensagem += 'Agora você pode tentar usar os botões de mapeamento.';
                
                alert(mensagem);
            } else {
                throw new Error(result.erro || 'Erro desconhecido na verificação das tabelas');
            }
        } else {
            const errorText = await response.text();
            throw new Error(`HTTP ${response.status}: ${errorText}`);
        }
        
    } catch (error) {
        console.error('Erro na verificação das tabelas:', error);
        alert(`❌ ERRO na verificação das tabelas:\n\n${error.message}\n\nVerifique o console para mais detalhes.`);
    }
}
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php include VIEW_PATH . '/layouts/layout.php'; ?>