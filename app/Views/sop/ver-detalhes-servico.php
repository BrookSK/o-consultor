<?php $tituloPagina = 'Detalhes do Serviço: ' . htmlspecialchars($dados['servico']['nome_servico']); ?>
<?php ob_start(); ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li><a href="<?= APP_URL ?>/sop/gerenciar-hierarquia?diagnostico_id=<?= $dados['servico']['diagnostico_id'] ?>" class="hover:text-primary">Gestão Hierárquica</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Detalhes do Serviço</li>
    </ol>
</nav>

<?php $servico = $dados['servico']; ?>

<!-- Header do Serviço -->
<div class="flex flex-col lg:flex-row items-start justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-3 mb-2">
            <code class="px-3 py-1 bg-gray-100 text-gray-700 rounded font-mono text-sm"><?= htmlspecialchars($servico['codigo_servico']) ?></code>
            <?php
            switch($servico['status']) {
                case 'mapeado':
                    $statusConfig = ['bg' => 'bg-gray-100 text-gray-600', 'icon' => '○', 'label' => 'Mapeado'];
                    break;
                case 'detalhado':
                    $statusConfig = ['bg' => 'bg-blue-100 text-blue-700', 'icon' => '◐', 'label' => 'Detalhado'];
                    break;
                case 'sop_gerado':
                    $statusConfig = ['bg' => 'bg-green-100 text-green-700', 'icon' => '●', 'label' => 'SOP Gerado'];
                    break;
                case 'aprovado':
                    $statusConfig = ['bg' => 'bg-emerald-100 text-emerald-700', 'icon' => '✓', 'label' => 'Aprovado'];
                    break;
                default:
                    $statusConfig = ['bg' => 'bg-gray-100 text-gray-500', 'icon' => '?', 'label' => 'Indefinido'];
                    break;
            }
            ?>
            <span class="px-3 py-1 text-sm font-medium rounded <?= $statusConfig['bg'] ?>">
                <?= $statusConfig['icon'] ?> <?= $statusConfig['label'] ?>
            </span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($servico['nome_servico']) ?></h1>
        <p class="text-gray-500 mt-1">
            <strong>Setor:</strong> <?= htmlspecialchars($servico['nome_setor']) ?> | 
            <strong>Empresa:</strong> <?= htmlspecialchars($servico['nome_empresa']) ?>
        </p>
    </div>
    
    <div class="flex gap-3">
        <button onclick="editarServico()" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
            ✏️ Editar
        </button>
        <?php if (!empty($servico['sop_id'])): ?>
        <a href="<?= APP_URL ?>/sop/ver-sop-individual?id=<?= $servico['sop_id'] ?>" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700">
            📋 Ver SOP
        </a>
        <button onclick="processarServico(<?= $servico['id'] ?>, 'processo_completo')" class="px-4 py-2 bg-orange-600 text-white rounded-lg text-sm hover:bg-orange-700">
            🔄 Regenerar SOP
        </button>
        <?php else: ?>
        <button onclick="processarServico(<?= $servico['id'] ?>, 'processo_completo')" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700">
            📝 Gerar SOP Completo
        </button>
        <?php endif; ?>
        <button onclick="excluirServico()" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700">
            🗑️ Excluir
        </button>
    </div>
</div>

<!-- Informações Básicas -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Informações Gerais -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">📋 Informações Gerais</h3>
        <div class="space-y-3">
            <div>
                <span class="text-sm font-medium text-gray-600">Categoria:</span>
                <span class="ml-2 px-2 py-1 bg-purple-100 text-purple-700 text-sm rounded"><?= ucfirst($servico['categoria']) ?></span>
            </div>
            <div>
                <span class="text-sm font-medium text-gray-600">Criticidade:</span>
                <span class="ml-2 px-2 py-1 <?php 
                    switch($servico['criticidade']) {
                        case 'alta':
                            echo 'bg-red-100 text-red-700';
                            break;
                        case 'media':
                            echo 'bg-yellow-100 text-yellow-700';
                            break;
                        case 'baixa':
                            echo 'bg-green-100 text-green-700';
                            break;
                        default:
                            echo 'bg-gray-100 text-gray-700';
                            break;
                    }
                ?> text-sm rounded"><?= ucfirst($servico['criticidade']) ?></span>
            </div>
            <div>
                <span class="text-sm font-medium text-gray-600">Frequência:</span>
                <span class="ml-2 text-sm text-gray-800"><?= ucfirst(str_replace('_', ' ', $servico['frequencia'])) ?></span>
            </div>
            <div>
                <span class="text-sm font-medium text-gray-600">Complexidade:</span>
                <span class="ml-2 text-sm text-gray-800"><?= ucfirst($servico['complexidade']) ?></span>
            </div>
            <div>
                <span class="text-sm font-medium text-gray-600">Origem:</span>
                <span class="ml-2 px-2 py-1 <?php
                    switch($servico['origem']) {
                        case 'automatico':
                            echo 'bg-blue-100 text-blue-700';
                            break;
                        case 'manual':
                            echo 'bg-green-100 text-green-700';
                            break;
                        case 'audio_transcricao':
                            echo 'bg-orange-100 text-orange-700';
                            break;
                        default:
                            echo 'bg-gray-100 text-gray-700';
                            break;
                    }
                ?> text-sm rounded">
                    <?php
                    switch($servico['origem']) {
                        case 'automatico':
                            echo '🤖 Automático';
                            break;
                        case 'manual':
                            echo '✋ Manual';
                            break;
                        case 'audio_transcricao':
                            echo '🎤 Áudio';
                            break;
                        default:
                            echo '❓ Indefinido';
                            break;
                    }
                    ?>
                </span>
            </div>
        </div>
    </div>
    
    <!-- Timeline -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">⏰ Timeline</h3>
        <div class="space-y-3 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-600">Criado:</span>
                <span class="text-gray-800"><?= date('d/m/Y H:i', strtotime($servico['criado_em'])) ?></span>
            </div>
            <?php if ($servico['detalhado_em']): ?>
            <div class="flex justify-between">
                <span class="text-gray-600">Detalhado:</span>
                <span class="text-gray-800"><?= date('d/m/Y H:i', strtotime($servico['detalhado_em'])) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($servico['sop_gerado_em']): ?>
            <div class="flex justify-between">
                <span class="text-gray-600">SOP Gerado:</span>
                <span class="text-gray-800"><?= date('d/m/Y H:i', strtotime($servico['sop_gerado_em'])) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($servico['atualizado_em']): ?>
            <div class="flex justify-between">
                <span class="text-gray-600">Última Atualização:</span>
                <span class="text-gray-800"><?= date('d/m/Y H:i', strtotime($servico['atualizado_em'])) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Setor Contexto -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">🏢 Contexto do Setor</h3>
        <div class="space-y-3 text-sm">
            <div>
                <span class="text-gray-600">Tipo de Setor:</span>
                <span class="ml-2 px-2 py-1 bg-gray-100 text-gray-700 rounded"><?= ucfirst($servico['tipo_setor']) ?></span>
            </div>
            <div>
                <span class="text-gray-600">Nicho da Empresa:</span>
                <span class="ml-2 text-gray-800"><?= ucfirst($servico['nicho']) ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Descrição -->
<?php if ($servico['descricao_resumida']): ?>
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">📝 Descrição</h3>
    <p class="text-gray-700 leading-relaxed"><?= nl2br(htmlspecialchars($servico['descricao_resumida'])) ?></p>
</div>
<?php endif; ?>

<!-- Transcrição de Áudio (se existir) -->
<?php if ($servico['audio_transcricao']): ?>
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">🎤 Transcrição de Áudio</h3>
    <div class="bg-gray-50 rounded-lg p-4">
        <pre class="text-sm text-gray-700 whitespace-pre-wrap font-mono"><?= htmlspecialchars($servico['audio_transcricao']) ?></pre>
    </div>
</div>
<?php endif; ?>

<!-- Detalhamento (se existir) -->
<?php if ($servico['detalhamento_json']): ?>
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">🔍 Detalhamento Completo</h3>
    
    <?php $detalhamento = $servico['detalhamento_json']; ?>
    
    <!-- Objetivo Principal -->
    <?php if (!empty($detalhamento['objetivo_principal'])): ?>
    <div class="mb-6">
        <h4 class="text-md font-semibold text-gray-800 mb-2">🎯 Objetivo Principal</h4>
        <p class="text-gray-700 bg-blue-50 p-3 rounded"><?= htmlspecialchars($detalhamento['objetivo_principal']) ?></p>
    </div>
    <?php endif; ?>
    
    <!-- Responsabilidades -->
    <?php if (!empty($detalhamento['responsabilidades'])): ?>
    <div class="mb-6">
        <h4 class="text-md font-semibold text-gray-800 mb-2">✅ Responsabilidades</h4>
        <ul class="list-disc list-inside space-y-1 text-gray-700">
            <?php foreach ($detalhamento['responsabilidades'] as $responsabilidade): ?>
            <li><?= htmlspecialchars($responsabilidade) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <!-- Processos Detalhados -->
    <?php if (!empty($detalhamento['processos_detalhados'])): ?>
    <div class="mb-6">
        <h4 class="text-md font-semibold text-gray-800 mb-2">⚙️ Processos Detalhados</h4>
        <div class="space-y-4">
            <?php foreach ($detalhamento['processos_detalhados'] as $processo): ?>
            <div class="border border-gray-200 rounded-lg p-4">
                <h5 class="font-medium text-gray-800 mb-2"><?= htmlspecialchars($processo['nome_processo']) ?></h5>
                <p class="text-gray-600 text-sm mb-3"><?= htmlspecialchars($processo['descricao']) ?></p>
                
                <!-- Etapas -->
                <?php if (!empty($processo['etapas'])): ?>
                <div class="mb-3">
                    <h6 class="text-sm font-medium text-gray-700 mb-1">Etapas:</h6>
                    <ol class="list-decimal list-inside space-y-1 text-sm text-gray-600 ml-2">
                        <?php foreach ($processo['etapas'] as $etapa): ?>
                        <li><?= htmlspecialchars($etapa) ?></li>
                        <?php endforeach; ?>
                    </ol>
                </div>
                <?php endif; ?>
                
                <!-- Info adicional -->
                <div class="grid grid-cols-2 gap-4 text-xs text-gray-500">
                    <div><strong>Tempo:</strong> <?= htmlspecialchars($processo['tempo_estimado'] ?? 'N/A') ?></div>
                    <div><strong>Frequência:</strong> <?= htmlspecialchars($processo['frequencia'] ?? 'N/A') ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Problemas Comuns -->
    <?php if (!empty($detalhamento['problemas_comuns'])): ?>
    <div class="mb-6">
        <h4 class="text-md font-semibold text-gray-800 mb-2">⚠️ Problemas Comuns</h4>
        <div class="space-y-3">
            <?php foreach ($detalhamento['problemas_comuns'] as $problema): ?>
            <div class="border border-orange-200 bg-orange-50 rounded-lg p-3">
                <div class="flex items-center gap-2 mb-2">
                    <h5 class="font-medium text-orange-800"><?= htmlspecialchars($problema['problema']) ?></h5>
                    <span class="px-2 py-1 bg-orange-200 text-orange-800 text-xs rounded"><?= htmlspecialchars($problema['impacto'] ?? 'Médio') ?></span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-2 text-sm">
                    <div>
                        <strong class="text-green-700">N1:</strong>
                        <span class="text-gray-700"><?= htmlspecialchars($problema['solucao_nivel1'] ?? 'N/A') ?></span>
                    </div>
                    <div>
                        <strong class="text-blue-700">N2:</strong>
                        <span class="text-gray-700"><?= htmlspecialchars($problema['solucao_nivel2'] ?? 'N/A') ?></span>
                    </div>
                    <div>
                        <strong class="text-purple-700">N3:</strong>
                        <span class="text-gray-700"><?= htmlspecialchars($problema['solucao_nivel3'] ?? 'N/A') ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Modal para Editar Serviço -->
<div id="modalEditarServico" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 max-w-lg w-full mx-4 shadow-xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Editar Serviço</h3>
            <button onclick="fecharModalEditar()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="formEditarServico">
            <input type="hidden" name="servico_id" value="<?= $servico['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Serviço *</label>
                    <input type="text" name="nome_servico" value="<?= htmlspecialchars($servico['nome_servico']) ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary"
                           maxlength="255" required>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                        <select name="categoria" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary">
                            <option value="core" <?= $servico['categoria'] == 'core' ? 'selected' : '' ?>>Core</option>
                            <option value="operacional" <?= $servico['categoria'] == 'operacional' ? 'selected' : '' ?>>Operacional</option>
                            <option value="estrategico" <?= $servico['categoria'] == 'estrategico' ? 'selected' : '' ?>>Estratégico</option>
                            <option value="integracao" <?= $servico['categoria'] == 'integracao' ? 'selected' : '' ?>>Integração</option>
                            <option value="excecao" <?= $servico['categoria'] == 'excecao' ? 'selected' : '' ?>>Exceção</option>
                            <option value="crise" <?= $servico['categoria'] == 'crise' ? 'selected' : '' ?>>Crise</option>
                            <option value="conformidade" <?= $servico['categoria'] == 'conformidade' ? 'selected' : '' ?>>Conformidade</option>
                            <option value="sazonal" <?= $servico['categoria'] == 'sazonal' ? 'selected' : '' ?>>Sazonal</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Criticidade</label>
                        <select name="criticidade" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary">
                            <option value="baixa" <?= $servico['criticidade'] == 'baixa' ? 'selected' : '' ?>>Baixa</option>
                            <option value="media" <?= $servico['criticidade'] == 'media' ? 'selected' : '' ?>>Média</option>
                            <option value="alta" <?= $servico['criticidade'] == 'alta' ? 'selected' : '' ?>>Alta</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Frequência</label>
                    <select name="frequencia" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary">
                        <option value="diaria" <?= $servico['frequencia'] == 'diaria' ? 'selected' : '' ?>>Diária</option>
                        <option value="semanal" <?= $servico['frequencia'] == 'semanal' ? 'selected' : '' ?>>Semanal</option>
                        <option value="mensal" <?= $servico['frequencia'] == 'mensal' ? 'selected' : '' ?>>Mensal</option>
                        <option value="trimestral" <?= $servico['frequencia'] == 'trimestral' ? 'selected' : '' ?>>Trimestral</option>
                        <option value="anual" <?= $servico['frequencia'] == 'anual' ? 'selected' : '' ?>>Anual</option>
                        <option value="sob_demanda" <?= $servico['frequencia'] == 'sob_demanda' ? 'selected' : '' ?>>Sob Demanda</option>
                        <option value="emergencial" <?= $servico['frequencia'] == 'emergencial' ? 'selected' : '' ?>>Emergencial</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                    <textarea name="descricao" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary"
                              placeholder="Descreva o serviço..."><?= htmlspecialchars($servico['descricao_resumida'] ?? '') ?></textarea>
                </div>
            </div>
            
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="fecharModalEditar()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="button" onclick="salvarEdicao()" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-700">
                    Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de Loading -->
<div id="modalLoading" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-8 text-center max-w-md w-full mx-4 shadow-xl">
        <div class="inline-block w-12 h-12 border-4 border-gray-200 border-t-primary rounded-full animate-spin mb-4"></div>
        <h3 class="text-lg font-medium text-gray-800 mb-2" id="loadingTitulo">Processando...</h3>
        <p class="text-sm text-gray-500 mb-4" id="loadingSubtitulo">Aguarde...</p>

        <!-- Barra de progresso -->
        <div class="w-full bg-gray-200 rounded-full h-3 mb-2 overflow-hidden">
            <div id="loadingBar" class="bg-primary h-3 rounded-full transition-all duration-500 ease-out" style="width: 5%"></div>
        </div>
        <p class="text-xs text-gray-400" id="loadingEtapa">Etapa 0 de 3</p>

        <!-- Indicadores de etapas -->
        <div class="flex justify-between mt-4 text-xs">
            <div id="etapa1" class="flex flex-col items-center text-gray-400 flex-1">
                <span class="w-6 h-6 rounded-full border-2 border-gray-300 flex items-center justify-center mb-1" id="etapa1-circle">1</span>
                <span>Resumo</span>
            </div>
            <div id="etapa2" class="flex flex-col items-center text-gray-400 flex-1">
                <span class="w-6 h-6 rounded-full border-2 border-gray-300 flex items-center justify-center mb-1" id="etapa2-circle">2</span>
                <span>Procedimentos</span>
            </div>
            <div id="etapa3" class="flex flex-col items-center text-gray-400 flex-1">
                <span class="w-6 h-6 rounded-full border-2 border-gray-300 flex items-center justify-center mb-1" id="etapa3-circle">3</span>
                <span>Situações Críticas</span>
            </div>
        </div>
    </div>
</div>

<script>
// Abrir modal de edição
function editarServico() {
    document.getElementById('modalEditarServico').classList.remove('hidden');
}

// Fechar modal de edição
function fecharModalEditar() {
    document.getElementById('modalEditarServico').classList.add('hidden');
}

// Salvar edição
async function salvarEdicao() {
    const form = document.getElementById('formEditarServico');
    const formData = new FormData(form);
    
    try {
        const response = await fetch('<?= APP_URL ?>/sop/editar-servico-manual', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.sucesso) {
            alert('Serviço atualizado com sucesso!');
            fecharModalEditar();
            window.location.reload();
        } else {
            alert('Erro ao atualizar serviço: ' + (data.erro || 'Erro desconhecido'));
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro de comunicação com o servidor');
    }
}

// Processar serviço via GERAÇÃO EM BACKGROUND + polling (evita timeout do proxy)
async function processarServico(servicoId, etapa) {
    const CSRF = '<?= Csrf::token() ?>';
    const URL_INICIAR = '<?= APP_URL ?>/sop/processar-servico-completo';
    const URL_STATUS = '<?= APP_URL ?>/sop/status-servico-sop';

    const fasesLabel = {
        0: 'Iniciando geração...',
        1: 'Gerando resumo e estrutura (1/3)...',
        2: 'Gerando procedimentos operacionais (2/3)...',
        3: 'Gerando situações críticas (3/3)...'
    };

    const URL_PROCESSAR = '<?= APP_URL ?>/sop/processar-fila';

    try {
        // 1. Enfileirar o pedido (resposta instantânea, não trava)
        mostrarLoading('Gerando SOP Completo', 'Adicionando à fila de processamento...');
        atualizarProgresso(1, 'Adicionando à fila...');

        const respInicio = await fetch(URL_INICIAR, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                servico_id: servicoId,
                csrf_token: CSRF
            })
        });

        const dataInicio = await respInicio.json();
        if (!dataInicio.sucesso) {
            esconderLoading();
            alert('Erro ao enfileirar: ' + (dataInicio.erro || 'Erro desconhecido'));
            return;
        }

        const sopId = dataInicio.sop_id;
        let concluido = false;

        // 2. Processar as fases: cada chamada processa UMA fase e retorna.
        // Chamamos em loop até concluir. Como cada chamada faz só uma
        // requisição de IA, cabe no timeout do proxy.
        const fasesTexto = {
            1: 'Gerando resumo e estrutura...',
            2: 'Gerando procedimentos operacionais...',
            3: 'Gerando situações críticas e gestão de crises...'
        };

        for (let i = 0; i < 4 && !concluido; i++) {
            // Atualiza o feedback visual antes de cada fase
            const respStatus = await fetch(URL_STATUS + '?sop_id=' + sopId + '&_=' + Date.now());
            const status = await respStatus.json();
            const faseInfo = (status.fase_atual || 0) + 1;
            atualizarProgresso(faseInfo <= 3 ? faseInfo : 3, fasesTexto[faseInfo] || 'Processando...');

            // Processar a próxima fase
            const respProc = await fetch(URL_PROCESSAR + '?_=' + Date.now());
            const proc = await respProc.json();

            if (!proc.sucesso) {
                esconderLoading();
                alert('Erro na geração: ' + (proc.erro || 'Erro desconhecido'));
                return;
            }

            if (proc.vazio || proc.concluido || (proc.fase && proc.fase >= 3)) {
                concluido = true;
            }
        }

        // 3. Confirmar conclusão
        atualizarProgresso(3, 'SOP completo gerado com sucesso!');
        setTimeout(() => {
            esconderLoading();
            window.location.href = '<?= APP_URL ?>/sop/ver-sop-individual?id=' + sopId;
        }, 800);

    } catch (error) {
        esconderLoading();
        console.error('Erro:', error);
        alert('Erro de comunicação com o servidor. Se uma fase demorou demais, tente regenerar.');
    }
}

// Excluir serviço
async function excluirServico() {
    if (!confirm('Tem certeza que deseja excluir este serviço? Esta ação não pode ser desfeita.')) {
        return;
    }
    
    mostrarLoading('Excluindo Serviço', 'Aguarde...');
    
    try {
        const response = await fetch('<?= APP_URL ?>/sop/excluir-servico', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                servico_id: <?= $servico['id'] ?>,
                csrf_token: '<?= Csrf::token() ?>'
            })
        });
        
        const data = await response.json();
        
        if (data.sucesso) {
            esconderLoading();
            alert(data.mensagem);
            window.location.href = '<?= APP_URL ?>/sop/gerenciar-hierarquia?diagnostico_id=<?= $servico['diagnostico_id'] ?>';
        } else {
            esconderLoading();
            alert('Erro ao excluir: ' + (data.erro || 'Erro desconhecido'));
        }
    } catch (error) {
        esconderLoading();
        console.error('Erro:', error);
        alert('Erro de comunicação com o servidor');
    }
}

// Funções de loading
function mostrarLoading(titulo, subtitulo) {
    document.getElementById('loadingTitulo').textContent = titulo;
    document.getElementById('loadingSubtitulo').textContent = subtitulo;
    document.getElementById('modalLoading').classList.remove('hidden');
}

function esconderLoading() {
    document.getElementById('modalLoading').classList.add('hidden');
}

// Atualiza a barra de progresso e os indicadores de etapa (fase 0 a 3)
function atualizarProgresso(fase, mensagem) {
    const percentuais = { 0: 5, 1: 33, 2: 66, 3: 100 };
    const pct = percentuais[fase] ?? 5;

    const bar = document.getElementById('loadingBar');
    if (bar) bar.style.width = pct + '%';

    const etapaLabel = document.getElementById('loadingEtapa');
    if (etapaLabel) etapaLabel.textContent = 'Etapa ' + fase + ' de 3';

    if (mensagem) {
        document.getElementById('loadingSubtitulo').textContent = mensagem;
    }

    // Marcar etapas concluídas/ativas
    for (let i = 1; i <= 3; i++) {
        const circle = document.getElementById('etapa' + i + '-circle');
        const wrapper = document.getElementById('etapa' + i);
        if (!circle || !wrapper) continue;

        if (i < fase) {
            // concluída
            circle.className = 'w-6 h-6 rounded-full bg-green-500 border-2 border-green-500 text-white flex items-center justify-center mb-1';
            circle.innerHTML = '✓';
            wrapper.classList.remove('text-gray-400');
            wrapper.classList.add('text-green-600');
        } else if (i === fase) {
            // ativa
            circle.className = 'w-6 h-6 rounded-full bg-primary border-2 border-primary text-white flex items-center justify-center mb-1 animate-pulse';
            circle.innerHTML = i;
            wrapper.classList.remove('text-gray-400');
            wrapper.classList.add('text-primary', 'font-semibold');
        } else {
            // pendente
            circle.className = 'w-6 h-6 rounded-full border-2 border-gray-300 flex items-center justify-center mb-1';
            circle.innerHTML = i;
            wrapper.classList.add('text-gray-400');
            wrapper.classList.remove('text-primary', 'text-green-600', 'font-semibold');
        }
    }
}
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>