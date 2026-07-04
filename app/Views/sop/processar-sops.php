<?php $tituloPagina = 'Gerando Manual Completo'; ?>
<?php ob_start(); ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li><a href="<?= APP_URL ?>/manual-operacional" class="hover:text-primary">Manual Operacional</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Gerando Manual Completo</li>
    </ol>
</nav>

<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="text-center">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Gerando Manual Operacional Completo</h1>
            <p class="text-gray-500"><?= htmlspecialchars($diagnosticoEstrutura['empresa']) ?></p>
            <div class="mt-4">
                <span class="inline-flex items-center gap-2 px-4 py-2 bg-blue-100 text-blue-700 rounded-lg font-medium">
                    🧠 Nova Arquitetura Profunda - <?= $this->contarTotalSOPs($diagnosticoEstrutura['setores']) ?> SOPs sendo gerados
                </span>
            </div>
        </div>
    </div>

    <!-- Progresso Geral -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold">Progresso da Geração</h2>
            <span id="progresso-texto" class="text-sm font-medium text-gray-600">0 de <?= $this->contarTotalSOPs($diagnosticoEstrutura['setores']) ?> SOPs gerados</span>
        </div>
        
        <div class="w-full bg-gray-200 rounded-full h-4 mb-4">
            <div id="barra-progresso" class="bg-blue-600 h-4 rounded-full transition-all duration-500" style="width: 0%"></div>
        </div>
        
        <div class="text-center">
            <button id="btn-iniciar" onclick="iniciarGeracaoCompleta()" class="px-8 py-3 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700">
                🚀 Iniciar Geração Completa
            </button>
            <button id="btn-montar-manual" onclick="montarManualFinal()" class="hidden px-8 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700">
                📚 Montar Manual Final
            </button>
        </div>
    </div>

    <!-- Lista de SOPs por Setor -->
    <div class="space-y-6">
        <?php foreach ($diagnosticoEstrutura['setores'] as $indexSetor => $setor): ?>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($setor['nome_setor']) ?></h3>
                        <p class="text-sm text-gray-500">
                            <?= ucfirst($setor['tipo']) ?> • <?= htmlspecialchars($setor['responsavel_sugerido']) ?> • <?= count($setor['sops']) ?> SOPs
                        </p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-700">
                        <?= htmlspecialchars($setor['funcao_no_negocio']) ?>
                    </span>
                </div>
            </div>

            <div class="p-6">
                <div class="space-y-3">
                    <?php 
                    $sopIndexGlobal = 0;
                    // Calcular índice global do SOP
                    for ($i = 0; $i < $indexSetor; $i++) {
                        $sopIndexGlobal += count($diagnosticoEstrutura['setores'][$i]['sops']);
                    }
                    ?>
                    
                    <?php foreach ($setor['sops'] as $indexSOP => $sop): ?>
                    <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg sop-item" 
                         data-sop-index="<?= $sopIndexGlobal + $indexSOP ?>">
                        
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <code class="px-2 py-1 bg-gray-100 text-xs text-gray-600 rounded"><?= htmlspecialchars($sop['id_sop']) ?></code>
                                <span class="px-2 py-1 text-xs font-medium rounded <?= $sop['criticidade'] == 1 ? 'bg-red-100 text-red-700' : ($sop['criticidade'] == 2 ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700') ?>">
                                    Criticidade <?= $sop['criticidade'] ?>
                                </span>
                            </div>
                            
                            <h4 class="font-medium text-gray-800 mb-1"><?= htmlspecialchars($sop['nome_sop']) ?></h4>
                            <p class="text-sm text-gray-500 mb-2"><?= htmlspecialchars($sop['objetivo_resumido']) ?></p>
                            <p class="text-xs text-gray-400">
                                <strong>Gatilho:</strong> <?= htmlspecialchars($sop['gatilho_de_entrada']) ?>
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="status-indicator" data-status="aguardando">
                                <span class="status-aguardando px-3 py-1 bg-gray-100 text-gray-600 text-sm rounded-lg">⏳ Aguardando</span>
                                <span class="status-gerando hidden px-3 py-1 bg-blue-100 text-blue-700 text-sm rounded-lg">🤖 Gerando...</span>
                                <span class="status-concluido hidden px-3 py-1 bg-green-100 text-green-700 text-sm rounded-lg">✅ Concluído</span>
                                <span class="status-erro hidden px-3 py-1 bg-red-100 text-red-700 text-sm rounded-lg">❌ Erro</span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal de Loading -->
<div id="modal-loading" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4 shadow-xl">
        <div class="text-center">
            <div class="inline-block w-16 h-16 border-4 border-gray-200 border-t-blue-600 rounded-full animate-spin mb-4"></div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2" id="modal-titulo">Processando...</h3>
            <p class="text-sm text-gray-500" id="modal-subtitulo">Aguarde...</p>
        </div>
    </div>
</div>

<script>
const ESTRUTURA_ID = <?= $estruturaId ?>;
const TOTAL_SOPS = <?= $this->contarTotalSOPs($diagnosticoEstrutura['setores']) ?>;
let sopsGerados = 0;
let processandoAtualmente = false;

function iniciarGeracaoCompleta() {
    if (processandoAtualmente) return;
    
    processandoAtualmente = true;
    document.getElementById('btn-iniciar').disabled = true;
    document.getElementById('btn-iniciar').textContent = '🔄 Processando...';
    
    // Processar SOPs sequencialmente para controlar rate limit
    processarProximoSOP(0);
}

async function processarProximoSOP(sopIndex) {
    if (sopIndex >= TOTAL_SOPS) {
        // Todos os SOPs foram processados
        finalizarGeracao();
        return;
    }
    
    const sopElement = document.querySelector(`[data-sop-index="${sopIndex}"]`);
    if (!sopElement) {
        console.error('SOP element não encontrado:', sopIndex);
        processarProximoSOP(sopIndex + 1);
        return;
    }
    
    // Atualizar status visual
    atualizarStatusSOP(sopElement, 'gerando');
    
    try {
        const response = await fetch('<?= APP_URL ?>/sop/gerar-sop-individual', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                estrutura_id: ESTRUTURA_ID,
                sop_index: sopIndex,
                csrf_token: '<?= Csrf::token() ?>'
            })
        });
        
        const data = await response.json();
        
        if (data.sucesso) {
            atualizarStatusSOP(sopElement, 'concluido');
            sopsGerados++;
            atualizarProgresso();
            
            console.log(`SOP ${sopIndex + 1}/${TOTAL_SOPS} gerado:`, data.sop_nome);
            
            // Pequeno delay para evitar sobrecarregar a API
            setTimeout(() => {
                processarProximoSOP(sopIndex + 1);
            }, 1000);
            
        } else {
            atualizarStatusSOP(sopElement, 'erro');
            console.error('Erro ao gerar SOP:', data.erro);
            
            // Continuar mesmo com erro
            setTimeout(() => {
                processarProximoSOP(sopIndex + 1);
            }, 500);
        }
        
    } catch (error) {
        console.error('Erro de rede:', error);
        atualizarStatusSOP(sopElement, 'erro');
        
        // Continuar mesmo com erro
        setTimeout(() => {
            processarProximoSOP(sopIndex + 1);
        }, 500);
    }
}

function atualizarStatusSOP(element, status) {
    const statusIndicator = element.querySelector('.status-indicator');
    
    // Esconder todos os status
    statusIndicator.querySelectorAll('[class*="status-"]').forEach(el => {
        el.classList.add('hidden');
    });
    
    // Mostrar status atual
    statusIndicator.querySelector(`.status-${status}`).classList.remove('hidden');
    statusIndicator.setAttribute('data-status', status);
}

function atualizarProgresso() {
    const porcentagem = Math.round((sopsGerados / TOTAL_SOPS) * 100);
    
    document.getElementById('progresso-texto').textContent = `${sopsGerados} de ${TOTAL_SOPS} SOPs gerados`;
    document.getElementById('barra-progresso').style.width = `${porcentagem}%`;
}

function finalizarGeracao() {
    processandoAtualmente = false;
    
    document.getElementById('btn-iniciar').classList.add('hidden');
    document.getElementById('btn-montar-manual').classList.remove('hidden');
    
    // Mostrar mensagem de sucesso
    const modal = document.getElementById('modal-loading');
    document.getElementById('modal-titulo').textContent = 'SOPs Gerados com Sucesso!';
    document.getElementById('modal-subtitulo').textContent = `${sopsGerados} SOPs foram criados. Agora você pode montar o manual final.`;
    modal.classList.remove('hidden');
    
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 3000);
    
    console.log('Geração completa finalizada!', {
        total_sops: TOTAL_SOPS,
        sops_gerados: sopsGerados
    });
}

async function montarManualFinal() {
    document.getElementById('modal-loading').classList.remove('hidden');
    document.getElementById('modal-titulo').textContent = 'Montando Manual Final';
    document.getElementById('modal-subtitulo').textContent = 'Consolidando todos os SOPs em um documento único...';
    
    try {
        const response = await fetch('<?= APP_URL ?>/sop/montar-manual-final', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                estrutura_id: ESTRUTURA_ID,
                csrf_token: '<?= Csrf::token() ?>'
            })
        });
        
        const data = await response.json();
        
        if (data.sucesso) {
            window.location.href = data.redirect;
        } else {
            alert('Erro ao montar manual final: ' + (data.erro || 'Erro desconhecido'));
            document.getElementById('modal-loading').classList.add('hidden');
        }
        
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro de conexão ao montar manual final');
        document.getElementById('modal-loading').classList.add('hidden');
    }
}

// Auto-scroll para acompanhar progresso
function scrollToCurrentSOP() {
    const currentSOP = document.querySelector('[data-status="gerando"]');
    if (currentSOP) {
        currentSOP.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

// Interceptar se usuário tentar sair da página durante processamento
window.addEventListener('beforeunload', function(e) {
    if (processandoAtualmente) {
        e.preventDefault();
        e.returnValue = 'A geração dos SOPs está em andamento. Tem certeza que deseja sair?';
        return e.returnValue;
    }
});
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>