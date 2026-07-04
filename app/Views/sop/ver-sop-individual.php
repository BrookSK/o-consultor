<?php 
$tituloPagina = $dados['titulo_pagina'] ?? 'SOP Individual'; 
$sop = $dados['sop'];
$data = $dados['sop_data'];
?>
<?php ob_start(); ?>

<div class="max-w-6xl mx-auto">
    <!-- Cabeçalho -->
    <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-bold text-gray-800">🔧 <?= htmlspecialchars($data['sop_titulo'] ?? 'SOP Individual') ?></h1>
            <div class="flex space-x-3">
                <button onclick="iniciarGravacaoVoz()" 
                        id="btn-microfone"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition flex items-center space-x-2"
                        title="Gravar descrição por voz usando Whisper AI">
                    <span id="microfone-icon">🎤</span>
                    <span id="microfone-texto">Gravar Voz</span>
                </button>
                <button onclick="alternarModoEdicao()" 
                        id="btn-editar"
                        class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                    ✏️ Editar
                </button>
                <button onclick="window.print()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    🖨️ Imprimir
                </button>
                <button onclick="exportarPDF()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    📄 Exportar PDF
                </button>
            </div>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
            <div><span class="text-gray-500">Serviço:</span> <span class="font-medium"><?= htmlspecialchars($sop['servico_nome']) ?></span></div>
            <div><span class="text-gray-500">Setor:</span> <span class="font-medium"><?= htmlspecialchars($sop['setor_nome']) ?></span></div>
            <div><span class="text-gray-500">Versão:</span> <span class="font-medium"><?= htmlspecialchars($data['versao'] ?? '1.0') ?></span></div>
            <div><span class="text-gray-500">Data:</span> <span class="font-medium"><?= htmlspecialchars($data['data_criacao'] ?? date('Y-m-d')) ?></span></div>
            <div><span class="text-gray-500">ID:</span> <span class="font-mono">#<?= $sop['id'] ?></span></div>
        </div>
    </div>
    
    <?php if ($data): ?>
    
    <!-- Modal de Transcrição por Voz -->
    <div id="modal-transcricao" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800">🎤 Transcrição por Voz - Whisper AI</h3>
                <button onclick="fecharModalTranscricao()" class="text-gray-500 hover:text-gray-700">✕</button>
            </div>
            
            <div id="status-gravacao" class="mb-4">
                <div class="flex items-center space-x-3">
                    <div id="indicador-gravacao" class="w-4 h-4 bg-gray-300 rounded-full"></div>
                    <span id="texto-status">Pressione o botão para iniciar a gravação</span>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Instruções:</label>
                <div class="text-sm text-gray-600 bg-blue-50 p-3 rounded">
                    Descreva detalhadamente o processo, incluindo:
                    • Passos específicos do procedimento
                    • Situações problemáticas e soluções
                    • Tempos estimados e responsáveis
                    • Pontos de controle e validação
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Transcrição:</label>
                <textarea id="transcricao-texto" 
                          class="w-full h-32 p-3 border border-gray-300 rounded-lg resize-none"
                          placeholder="A transcrição aparecerá aqui..."></textarea>
            </div>
            
            <div class="flex justify-between">
                <div class="flex space-x-2">
                    <button onclick="iniciarPararGravacao()" 
                            id="btn-gravar"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                        🎤 Iniciar Gravação
                    </button>
                    <button onclick="limparTranscricao()" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        🗑️ Limpar
                    </button>
                </div>
                <div class="flex space-x-2">
                    <button onclick="fecharModalTranscricao()" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button onclick="processarTranscricao()" 
                            id="btn-processar"
                            class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition"
                            disabled>
                        🤖 Gerar SOP com IA
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Informações Gerais -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
        <h2 class="text-lg font-semibold text-blue-900 mb-4">📋 Informações Gerais</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <h4 class="font-medium text-blue-800 mb-2">Objetivo</h4>
                <p class="text-blue-700 text-sm editavel" data-field="objetivo" contenteditable="false"><?= htmlspecialchars($data['objetivo'] ?? '') ?></p>
            </div>
            <div>
                <h4 class="font-medium text-blue-800 mb-2">Escopo</h4>
                <p class="text-blue-700 text-sm editavel" data-field="escopo" contenteditable="false"><?= htmlspecialchars($data['escopo'] ?? '') ?></p>
            </div>
        </div>
        
        <?php if (!empty($data['responsaveis'])): ?>
        <div class="mt-4 pt-4 border-t border-blue-200">
            <h4 class="font-medium text-blue-800 mb-2">Responsáveis</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                <div><span class="text-blue-600">Executor:</span> <?= htmlspecialchars($data['responsaveis']['executor_principal'] ?? '') ?></div>
                <div><span class="text-blue-600">Supervisor:</span> <?= htmlspecialchars($data['responsaveis']['supervisor'] ?? '') ?></div>
                <div><span class="text-blue-600">Aprovador:</span> <?= htmlspecialchars($data['responsaveis']['aprovador'] ?? '') ?></div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Pré-requisitos e Recursos -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Pré-requisitos -->
        <?php if (!empty($data['pre_requisitos'])): ?>
        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">⚡ Pré-requisitos</h3>
            <ul class="space-y-2 text-sm">
                <?php foreach ($data['pre_requisitos'] as $requisito): ?>
                <li class="flex items-start space-x-2">
                    <span class="text-green-600 mt-1">✓</span>
                    <span class="text-gray-700"><?= htmlspecialchars($requisito) ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <!-- Recursos Necessários -->
        <?php if (!empty($data['recursos_necessarios'])): ?>
        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">🛠️ Recursos Necessários</h3>
            <?php foreach ($data['recursos_necessarios'] as $tipo => $recursos): ?>
            <?php if (!empty($recursos)): ?>
            <div class="mb-3">
                <h4 class="font-medium text-gray-700 mb-1"><?= ucfirst($tipo) ?></h4>
                <ul class="text-sm text-gray-600 space-y-1">
                    <?php foreach ($recursos as $recurso): ?>
                    <li>• <?= htmlspecialchars($recurso) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Procedimentos -->
    <?php if (!empty($data['procedimentos'])): ?>
    <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">📝 Procedimentos Operacionais</h2>
        
        <?php foreach ($data['procedimentos'] as $faseIndex => $fase): ?>
        <div class="mb-8 <?= $faseIndex > 0 ? 'border-t pt-6' : '' ?>">
            <h3 class="text-lg font-medium text-gray-800 mb-3 bg-gray-100 p-3 rounded-lg">
                🔸 <?= htmlspecialchars($fase['fase'] ?? "Fase " . ($faseIndex + 1)) ?>
            </h3>
            
            <?php if (!empty($fase['descricao'])): ?>
            <p class="text-gray-600 text-sm mb-4"><?= htmlspecialchars($fase['descricao']) ?></p>
            <?php endif; ?>
            
            <?php if (!empty($fase['passos'])): ?>
            <div class="space-y-4">
                <?php foreach ($fase['passos'] as $passo): ?>
                <div class="flex items-start space-x-4 p-4 border border-gray-200 rounded-lg hover:bg-gray-50">
                    <div class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-medium text-sm">
                        <?= $passo['passo'] ?? '' ?>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium text-gray-800 mb-2 editavel" data-field="procedimentos[<?= $faseIndex ?>].passos[<?= key($fase['passos']) ?>].acao" contenteditable="false"><?= htmlspecialchars($passo['acao'] ?? '') ?></h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                            <div><span class="text-gray-500">Responsável:</span> <span class="text-gray-700"><?= htmlspecialchars($passo['responsavel'] ?? '') ?></span></div>
                            <div><span class="text-gray-500">Tempo:</span> <span class="text-gray-700"><?= htmlspecialchars($passo['tempo_estimado'] ?? '') ?></span></div>
                            <div><span class="text-gray-500">Qualidade:</span> <span class="text-gray-700"><?= htmlspecialchars($passo['criterio_qualidade'] ?? '') ?></span></div>
                        </div>
                        
                        <?php if (!empty($passo['observacoes'])): ?>
                        <div class="mt-2 p-2 bg-yellow-50 border border-yellow-200 rounded text-xs">
                            <span class="text-yellow-700">💡 <strong>Observação:</strong> <?= htmlspecialchars($passo['observacoes']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Pontos de Controle -->
    <?php if (!empty($data['pontos_controle'])): ?>
    <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">🎯 Pontos de Controle</h2>
        <div class="space-y-4">
            <?php foreach ($data['pontos_controle'] as $index => $ponto): ?>
            <div class="border border-orange-200 rounded-lg p-4 bg-orange-50">
                <h4 class="font-medium text-orange-900 mb-2">Controle #<?= $index + 1 ?> - <?= htmlspecialchars($ponto['momento'] ?? '') ?></h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                    <div>
                        <span class="text-orange-700 font-medium">O que verificar:</span>
                        <p class="text-orange-800"><?= htmlspecialchars($ponto['o_que_verificar'] ?? '') ?></p>
                    </div>
                    <div>
                        <span class="text-orange-700 font-medium">Critério de aceitação:</span>
                        <p class="text-orange-800"><?= htmlspecialchars($ponto['criterio_aceitacao'] ?? '') ?></p>
                    </div>
                    <div>
                        <span class="text-orange-700 font-medium">Se não conforme:</span>
                        <p class="text-orange-800"><?= htmlspecialchars($ponto['acao_se_nao_conforme'] ?? '') ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Procedimentos de Emergência -->
    <?php if (!empty($data['procedimentos_emergencia']['situacoes_criticas'])): ?>
    <div class="bg-white border border-red-200 rounded-lg p-6 mb-6">
        <h2 class="text-lg font-semibold text-red-800 mb-4">🚨 Procedimentos de Emergência</h2>
        <div class="space-y-4">
            <?php foreach ($data['procedimentos_emergencia']['situacoes_criticas'] as $situacao): ?>
            <div class="border border-red-300 rounded-lg p-4 bg-red-50">
                <h4 class="font-medium text-red-900 mb-3"><?= htmlspecialchars($situacao['situacao'] ?? '') ?></h4>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
                    <div>
                        <span class="text-red-700 font-medium">Sinais de alerta:</span>
                        <ul class="text-red-800 mt-1">
                            <?php foreach ($situacao['sinais_alerta'] ?? [] as $sinal): ?>
                            <li>• <?= htmlspecialchars($sinal) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div>
                        <span class="text-red-700 font-medium">Ação imediata:</span>
                        <p class="text-red-800 mt-1"><?= htmlspecialchars($situacao['acao_imediata'] ?? '') ?></p>
                    </div>
                    <div>
                        <span class="text-red-700 font-medium">Notificar:</span>
                        <p class="text-red-800 mt-1"><?= htmlspecialchars($situacao['quem_notificar'] ?? '') ?></p>
                    </div>
                    <div>
                        <span class="text-red-700 font-medium">Tempo máximo:</span>
                        <p class="text-red-800 mt-1 font-mono"><?= htmlspecialchars($situacao['tempo_resposta_maximo'] ?? '') ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Checklists -->
    <?php if (!empty($data['checklists'])): ?>
    <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">☑️ Checklists</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach ($data['checklists'] as $momento => $itens): ?>
            <?php if (!empty($itens)): ?>
            <div>
                <h4 class="font-medium text-gray-700 mb-3"><?= ucwords(str_replace('_', ' ', $momento)) ?></h4>
                <ul class="space-y-2">
                    <?php foreach ($itens as $item): ?>
                    <li class="flex items-start space-x-2">
                        <input type="checkbox" class="mt-1 rounded border-gray-300">
                        <span class="text-sm text-gray-700"><?= htmlspecialchars($item) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Indicadores de Performance -->
    <?php if (!empty($data['indicadores_performance'])): ?>
    <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">📊 Indicadores de Performance</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ($data['indicadores_performance'] as $indicador): ?>
            <div class="border border-gray-200 rounded-lg p-4">
                <h4 class="font-medium text-gray-800"><?= htmlspecialchars($indicador['nome'] ?? '') ?></h4>
                <div class="grid grid-cols-1 gap-2 text-sm mt-2">
                    <div><span class="text-gray-500">Fórmula:</span> <code class="bg-gray-100 px-2 py-1 rounded"><?= htmlspecialchars($indicador['formula'] ?? '') ?></code></div>
                    <div><span class="text-gray-500">Meta:</span> <span class="font-medium text-green-600"><?= htmlspecialchars($indicador['meta'] ?? '') ?></span></div>
                    <div><span class="text-gray-500">Frequência:</span> <?= htmlspecialchars($indicador['frequencia_medicao'] ?? '') ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php else: ?>
    <div class="bg-red-50 border border-red-200 rounded-lg p-6">
        <h2 class="text-lg font-semibold text-red-800 mb-2">❌ Erro nos Dados</h2>
        <p class="text-red-700">Não foi possível carregar os dados do SOP. O JSON pode estar corrompido.</p>
    </div>
    <?php endif; ?>
    
    <!-- Ações -->
    <div class="flex justify-between items-center mt-8 pt-6 border-t">
        <a href="javascript:history.back()" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
            ← Voltar
        </a>
        
        <div class="flex space-x-3">
            <button onclick="window.print()" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                🖨️ Imprimir SOP
            </button>
            <button onclick="exportarPDF()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                📄 Exportar PDF
            </button>
        </div>
    </div>
</div>

<script>
let modoEdicao = false;
let mediaRecorder = null;
let audioChunks = [];
let sopData = <?= json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
let sopId = <?= (int) $sop['id'] ?>;

// Alternar modo de edição
function alternarModoEdicao() {
    modoEdicao = !modoEdicao;
    
    const btnEditar = document.getElementById('btn-editar');
    const editaveis = document.querySelectorAll('.editavel');
    
    if (modoEdicao) {
        // Ativar edição
        btnEditar.innerHTML = '💾 Salvar';
        btnEditar.className = 'px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition';
        
        editaveis.forEach(el => {
            el.contentEditable = true;
            el.classList.add('border', 'border-purple-300', 'rounded', 'p-1', 'bg-purple-50');
        });
        
        console.log('✏️ Modo de edição ativado');
        
    } else {
        // Salvar alterações
        salvarAlteracoes();
        
        btnEditar.innerHTML = '✏️ Editar';
        btnEditar.className = 'px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition';
        
        editaveis.forEach(el => {
            el.contentEditable = false;
            el.classList.remove('border', 'border-purple-300', 'rounded', 'p-1', 'bg-purple-50');
        });
        
        console.log('💾 Modo de edição desativado');
    }
}

// Salvar alterações do SOP
async function salvarAlteracoes() {
    console.log('💾 SALVANDO ALTERAÇÕES DO SOP');
    
    try {
        // Coletar dados editados
        const dadosEditados = {};
        const editaveis = document.querySelectorAll('.editavel');
        
        editaveis.forEach(el => {
            const field = el.getAttribute('data-field');
            if (field) {
                dadosEditados[field] = el.textContent.trim();
            }
        });
        
        console.log('Dados coletados:', dadosEditados);
        
        // Enviar para o servidor
        const response = await fetch(`/sop/salvar-alteracoes-sop`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({
                sop_id: sopId,
                alteracoes: dadosEditados
            })
        });
        
        if (response.ok) {
            const result = await response.json();
            if (result.sucesso) {
                console.log('✅ Alterações salvas com sucesso');
                // Feedback visual
                document.body.classList.add('flash-verde');
                setTimeout(() => document.body.classList.remove('flash-verde'), 1000);
            } else {
                throw new Error(result.erro || 'Erro ao salvar');
            }
        } else {
            throw new Error(`HTTP ${response.status}`);
        }
        
    } catch (error) {
        console.error('❌ Erro ao salvar alterações:', error);
        alert(`Erro ao salvar alterações:\n\n${error.message}`);
    }
}

// Iniciar gravação de voz
function iniciarGravacaoVoz() {
    console.log('🎤 INICIANDO MODAL DE TRANSCRIÇÃO');
    
    // Verificar suporte do navegador
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        alert('Seu navegador não suporta gravação de áudio. Use Chrome, Firefox ou Safari atualizado.');
        return;
    }
    
    // Mostrar modal
    document.getElementById('modal-transcricao').classList.remove('hidden');
    
    // Reset do estado
    audioChunks = [];
    document.getElementById('transcricao-texto').value = '';
    document.getElementById('btn-processar').disabled = true;
}

// Iniciar/parar gravação
async function iniciarPararGravacao() {
    const btnGravar = document.getElementById('btn-gravar');
    const indicador = document.getElementById('indicador-gravacao');
    const textoStatus = document.getElementById('texto-status');
    
    if (!mediaRecorder || mediaRecorder.state === 'inactive') {
        // Iniciar gravação
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ 
                audio: {
                    sampleRate: 16000,
                    channelCount: 1,
                    echoCancellation: true,
                    noiseSuppression: true
                } 
            });
            
            mediaRecorder = new MediaRecorder(stream, {
                mimeType: MediaRecorder.isTypeSupported('audio/webm') ? 'audio/webm' : 'audio/mp4'
            });
            
            mediaRecorder.ondataavailable = (event) => {
                if (event.data.size > 0) {
                    audioChunks.push(event.data);
                }
            };
            
            mediaRecorder.onstop = () => {
                console.log('🎤 Gravação finalizada, processando...');
                processarAudioGravado();
            };
            
            mediaRecorder.start();
            
            // UI para gravando
            btnGravar.innerHTML = '⏹️ Parar Gravação';
            btnGravar.className = 'px-4 py-2 bg-red-700 text-white rounded-lg hover:bg-red-800 transition';
            indicador.className = 'w-4 h-4 bg-red-500 rounded-full animate-pulse';
            textoStatus.textContent = 'Gravando... Fale claramente sobre o processo';
            
            console.log('🎤 Gravação iniciada');
            
        } catch (error) {
            console.error('❌ Erro ao acessar microfone:', error);
            alert('Erro ao acessar o microfone. Verifique as permissões do navegador.');
        }
        
    } else if (mediaRecorder.state === 'recording') {
        // Parar gravação
        mediaRecorder.stop();
        mediaRecorder.stream.getTracks().forEach(track => track.stop());
        
        // UI para processando
        btnGravar.innerHTML = '⏳ Processando...';
        btnGravar.disabled = true;
        indicador.className = 'w-4 h-4 bg-yellow-500 rounded-full animate-spin';
        textoStatus.textContent = 'Processando áudio com Whisper AI...';
        
        console.log('⏹️ Gravação parada');
    }
}

// Processar áudio gravado com Whisper
async function processarAudioGravado() {
    console.log('🤖 PROCESSANDO ÁUDIO COM WHISPER');
    
    try {
        if (audioChunks.length === 0) {
            throw new Error('Nenhum áudio foi gravado');
        }
        
        // Converter chunks para blob
        const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
        console.log('Áudio preparado:', audioBlob.size, 'bytes');
        
        // Preparar FormData para Whisper
        const formData = new FormData();
        formData.append('audio', audioBlob, 'gravacao.webm');
        formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'));
        
        // Enviar para transcrição
        const response = await fetch('/sop/transcrever-audio-whisper', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`Erro HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        
        if (result.sucesso && result.transcricao) {
            console.log('✅ Transcrição recebida:', result.transcricao);
            
            // Mostrar transcrição na textarea
            document.getElementById('transcricao-texto').value = result.transcricao;
            document.getElementById('btn-processar').disabled = false;
            
            // UI para sucesso
            document.getElementById('texto-status').textContent = 'Transcrição concluída! Revise e clique em "Gerar SOP"';
            document.getElementById('indicador-gravacao').className = 'w-4 h-4 bg-green-500 rounded-full';
            
        } else {
            throw new Error(result.erro || 'Erro na transcrição');
        }
        
    } catch (error) {
        console.error('❌ Erro na transcrição:', error);
        
        document.getElementById('texto-status').textContent = 'Erro na transcrição. Tente novamente.';
        document.getElementById('indicador-gravacao').className = 'w-4 h-4 bg-red-500 rounded-full';
        
        alert(`Erro na transcrição:\n\n${error.message}`);
    } finally {
        // Resetar botão de gravação
        const btnGravar = document.getElementById('btn-gravar');
        btnGravar.innerHTML = '🎤 Nova Gravação';
        btnGravar.className = 'px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition';
        btnGravar.disabled = false;
    }
}

// Processar transcrição para gerar SOP
async function processarTranscricao() {
    const transcricao = document.getElementById('transcricao-texto').value.trim();
    
    if (!transcricao) {
        alert('Nenhuma transcrição encontrada. Grave um áudio primeiro.');
        return;
    }
    
    console.log('🤖 PROCESSANDO TRANSCRIÇÃO PARA SOP');
    
    try {
        document.getElementById('btn-processar').disabled = true;
        document.getElementById('btn-processar').innerHTML = '⏳ Gerando SOP...';
        
        // Enviar transcrição para processamento
        const response = await fetch('/sop/gerar-sop-por-transcricao', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({
                sop_id: sopId,
                transcricao: transcricao,
                contexto_atual: sopData
            })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.sucesso) {
            console.log('✅ SOP gerado a partir da transcrição');
            
            // Fechar modal e recarregar página para mostrar novo SOP
            fecharModalTranscricao();
            
            setTimeout(() => {
                window.location.reload();
            }, 1000);
            
        } else {
            throw new Error(result.erro || 'Erro ao gerar SOP');
        }
        
    } catch (error) {
        console.error('❌ Erro ao processar transcrição:', error);
        alert(`Erro ao gerar SOP:\n\n${error.message}`);
    } finally {
        document.getElementById('btn-processar').disabled = false;
        document.getElementById('btn-processar').innerHTML = '🤖 Gerar SOP com IA';
    }
}

// Fechar modal de transcrição
function fecharModalTranscricao() {
    document.getElementById('modal-transcricao').classList.add('hidden');
    
    // Parar gravação se estiver ativa
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.stop();
        mediaRecorder.stream.getTracks().forEach(track => track.stop());
    }
    
    // Reset do estado
    audioChunks = [];
}

// Limpar transcrição
function limparTranscricao() {
    document.getElementById('transcricao-texto').value = '';
    document.getElementById('btn-processar').disabled = true;
    
    // Reset do visual
    document.getElementById('texto-status').textContent = 'Pressione o botão para iniciar a gravação';
    document.getElementById('indicador-gravacao').className = 'w-4 h-4 bg-gray-300 rounded-full';
}

// Função original de exportar PDF
function exportarPDF() {
    alert('🚧 Função de exportar PDF será implementada em breve!');
}

// CSS para feedback visual
const style = document.createElement('style');
style.textContent = `
    .flash-verde {
        animation: flashVerde 0.5s ease-in-out;
    }
    
    @keyframes flashVerde {
        0% { background-color: transparent; }
        50% { background-color: rgba(34, 197, 94, 0.1); }
        100% { background-color: transparent; }
    }
    
    .editavel:focus {
        outline: 2px solid #8b5cf6;
        outline-offset: 2px;
    }
`;
document.head.appendChild(style);
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php include VIEW_PATH . '/layouts/layout.php'; ?>