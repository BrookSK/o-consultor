<?php $tituloPagina = 'Gestão Hierárquica - Setor > Serviços > SOPs'; ?>
<?php ob_start(); ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li><a href="<?= APP_URL ?>/diagnostico" class="hover:text-primary">Diagnósticos</a></li>
        <li>/</li>
        <li><a href="<?= APP_URL ?>/diagnostico/resultado/<?= $dados['diagnostico_id'] ?>" class="hover:text-primary">Resultado</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Gestão Hierárquica</li>
    </ol>
</nav>

<!-- Header Principal -->
<div class="flex flex-col lg:flex-row items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Gestão Hierárquica de SOPs</h1>
        <p class="text-gray-500 mt-1">
            Sistema unificado: Setor → Serviços → SOPs
        </p>
        <div class="mt-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                🎯 Diagnóstico #<?= $dados['diagnostico_id'] ?>
            </span>
            <?php if ($dados['estrutura_existe']): ?>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                ✅ Estrutura Criada
            </span>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="flex gap-3">
        <a href="<?= APP_URL ?>/sop/listar-por-diagnostico?diagnostico_id=<?= $dados['diagnostico_id'] ?>" 
           class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 text-sm">
            📋 Ver Lista Simples
        </a>
        <?php if (!$dados['estrutura_existe']): ?>
        <button onclick="criarEstruturaOrganizacional()" 
                class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-medium">
            🏗️ Criar Estrutura Organizacional
        </button>
        <?php else: ?>
        <button onclick="regenerarEstrutura()" 
                class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 text-sm">
            🔄 Regenerar Estrutura
        </button>
        <button onclick="exportarManualCompleto()" 
                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
            📦 Exportar Manual Completo
        </button>
        <?php endif; ?>
    </div>
</div>

<?php if ($dados['estrutura_existe']): ?>
<!-- Progresso Geral -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
    <div class="flex items-center gap-3 mb-4">
        <span class="text-2xl">📊</span>
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Progresso Geral</h2>
            <p class="text-sm text-gray-500">Acompanhe o desenvolvimento da estrutura organizacional</p>
        </div>
    </div>
    
    <?php if (!empty($dados['progresso'])): ?>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
        <div class="text-center">
            <div class="text-2xl font-bold text-blue-600"><?= $dados['progresso']['servicos_total'] ?></div>
            <div class="text-sm text-gray-600">Serviços Mapeados</div>
        </div>
        <div class="text-center">
            <div class="text-2xl font-bold text-yellow-600"><?= $dados['progresso']['servicos_detalhados'] ?></div>
            <div class="text-sm text-gray-600">Serviços Detalhados</div>
        </div>
        <div class="text-center">
            <div class="text-2xl font-bold text-green-600"><?= $dados['progresso']['servicos_com_sop'] ?></div>
            <div class="text-sm text-gray-600">SOPs Gerados</div>
        </div>
        <div class="text-center">
            <div class="text-2xl font-bold text-purple-600"><?= number_format($dados['progresso']['percentual_conclusao'], 1) ?>%</div>
            <div class="text-sm text-gray-600">Conclusão</div>
        </div>
    </div>
    
    <!-- Barra de Progresso -->
    <div class="w-full h-4 bg-gray-200 rounded-full overflow-hidden mb-2">
        <div class="h-full bg-gradient-to-r from-blue-500 to-green-500 rounded-full transition-all" 
             style="width: <?= $dados['progresso']['percentual_conclusao'] ?>%"></div>
    </div>
    
    <div class="text-sm text-gray-600 text-center">
        <strong>Etapa Atual:</strong> 
        <span class="font-medium text-primary">
            <?php
            $etapas = [
                'mapeamento_setores' => 'Mapeamento de Setores',
                'mapeamento_servicos' => 'Mapeamento de Serviços',
                'detalhamento_servicos' => 'Detalhamento de Serviços',
                'geracao_sops' => 'Geração de SOPs',
                'concluido' => 'Concluído'
            ];
            echo $etapas[$dados['progresso']['etapa_atual']] ?? 'Iniciando';
            ?>
        </span>
    </div>
    <?php else: ?>
    <div class="text-center py-4 text-gray-500">
        Progresso será exibido após mapear os primeiros serviços
    </div>
    <?php endif; ?>
</div>

<!-- Lista de Serviços Rápida -->
<?php if (!empty($dados['hierarquia']['setores'])): ?>
<!-- Seção de Acesso Rápido aos Serviços -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
    <div class="flex items-center gap-3 mb-4">
        <span class="text-2xl">⚡</span>
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Acesso Rápido aos Serviços</h2>
            <p class="text-sm text-gray-500">Clique em qualquer serviço para processá-lo diretamente</p>
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        <?php 
        $todosServicos = [];
        foreach ($dados['hierarquia']['setores'] as $setor) {
            foreach ($setor['servicos'] as $servico) {
                $servico['setor_nome'] = $setor['nome_setor'];
                $todosServicos[] = $servico;
            }
        }
        
        // Ordenar por status para priorizar os que precisam de ação
        usort($todosServicos, function($a, $b) {
            $prioridade = ['mapeado' => 1, 'detalhado' => 2, 'sop_gerado' => 3, 'aprovado' => 4];
            return ($prioridade[$a['status']] ?? 5) - ($prioridade[$b['status']] ?? 5);
        });
        
        foreach (array_slice($todosServicos, 0, 12) as $servico): // Mostrar apenas 12 para não sobrecarregar
        ?>
        <div class="border border-gray-200 rounded-lg p-3 hover:border-blue-300 hover:bg-blue-50 transition cursor-pointer"
             onclick="processarServicoRapido(<?= $servico['id'] ?>, '<?= $servico['status'] ?>')">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs text-gray-500 font-medium"><?= htmlspecialchars($servico['setor_nome']) ?></span>
                <?php
                switch($servico['status']) {
                    case 'mapeado':
                        $statusBadge = 'bg-gray-100 text-gray-600';
                        $statusText = 'Mapeado';
                        break;
                    case 'detalhado':
                        $statusBadge = 'bg-blue-100 text-blue-700';
                        $statusText = 'Detalhado';
                        break;
                    case 'sop_gerado':
                        $statusBadge = 'bg-green-100 text-green-700';
                        $statusText = 'SOP Gerado';
                        break;
                    case 'aprovado':
                        $statusBadge = 'bg-emerald-100 text-emerald-700';
                        $statusText = 'Aprovado';
                        break;
                    default:
                        $statusBadge = 'bg-gray-100 text-gray-500';
                        $statusText = 'Indefinido';
                        break;
                }
                ?>
                <span class="px-2 py-1 text-xs rounded font-medium <?= $statusBadge ?>">
                    <?= $statusText ?>
                </span>
            </div>
            <h4 class="text-sm font-medium text-gray-800 mb-1"><?= htmlspecialchars($servico['nome_servico']) ?></h4>
            <div class="text-xs text-gray-500 mb-2">
                Código: <?= htmlspecialchars($servico['codigo_servico']) ?>
            </div>
            <div class="flex justify-between items-center">
                <span class="px-2 py-1 bg-purple-100 text-purple-700 text-xs rounded">
                    <?= ucfirst($servico['categoria']) ?>
                </span>
                
                <?php if ($servico['status'] === 'mapeado'): ?>
                <span class="text-xs text-blue-600 font-medium">👆 Clique para detalhar</span>
                <?php elseif ($servico['status'] === 'detalhado'): ?>
                <span class="text-xs text-green-600 font-medium">👆 Clique para gerar SOP</span>
                <?php elseif ($servico['status'] === 'sop_gerado' || $servico['status'] === 'aprovado'): ?>
                <span class="text-xs text-emerald-600 font-medium">👆 Clique para ver SOP</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php if (count($todosServicos) > 12): ?>
    <div class="text-center mt-4">
        <span class="text-sm text-gray-500">
            Mostrando 12 de <?= count($todosServicos) ?> serviços • Use a visualização hierárquica abaixo para ver todos
        </span>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Hierarquia Completa -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="flex items-center gap-3 mb-6">
        <span class="text-2xl">🏢</span>
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Estrutura Organizacional</h2>
            <p class="text-sm text-gray-500">Gerencie setores, serviços e SOPs de forma hierárquica</p>
        </div>
    </div>
    
    <?php if (!empty($dados['hierarquia']['setores'])): ?>
    <div class="space-y-4">
        <?php foreach ($dados['hierarquia']['setores'] as $setor): ?>
        <div class="border border-gray-200 rounded-lg">
            <!-- Cabeçalho do Setor -->
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between cursor-pointer"
                 onclick="toggleSetor('setor-<?= $setor['id'] ?>')">
                <div class="flex items-center gap-3">
                    <span class="text-xl">
                        <?php 
                        switch($setor['tipo_setor']) {
                            case 'core':
                                echo '⚙️';
                                break;
                            case 'apoio':
                                echo '🛠️';
                                break; 
                            case 'estrategico':
                                echo '📋';
                                break;
                            default:
                                echo '📁';
                                break;
                        }
                        ?>
                    </span>
                    <div>
                        <h3 class="font-medium text-gray-800"><?= htmlspecialchars($setor['nome_setor']) ?></h3>
                        <div class="flex items-center gap-2 text-xs text-gray-500">
                            <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded"><?= ucfirst($setor['tipo_setor']) ?></span>
                            <span><?= $setor['stats']['total_servicos'] ?> serviços</span>
                            <span class="text-green-600"><?= $setor['stats']['com_sop'] ?> SOPs</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Indicador de Status -->
                    <?php if ($setor['stats']['com_sop'] == $setor['stats']['total_servicos'] && $setor['stats']['total_servicos'] > 0): ?>
                    <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded font-medium">✓ Completo</span>
                    <?php elseif ($setor['stats']['com_sop'] > 0): ?>
                    <span class="px-2 py-1 bg-yellow-100 text-yellow-700 text-xs rounded font-medium">⚠ Parcial</span>
                    <?php else: ?>
                    <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded font-medium">○ Pendente</span>
                    <?php endif; ?>
                    
                    <i class="fas fa-chevron-down text-gray-400 transform transition-transform" id="icon-setor-<?= $setor['id'] ?>"></i>
                </div>
            </div>
            
            <!-- Serviços do Setor -->
            <div class="hidden" id="setor-<?= $setor['id'] ?>">
                <div class="p-4">
                    <?php if (!empty($setor['servicos'])): ?>
                    <div class="space-y-3">
                        <?php foreach ($setor['servicos'] as $servico): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <div class="flex items-center gap-3 flex-1">
                                <code class="px-2 py-1 bg-white text-xs text-gray-600 rounded font-mono"><?= htmlspecialchars($servico['codigo_servico']) ?></code>
                                
                                <!-- Nome do serviço clicável -->
                                <span class="text-sm text-gray-800 cursor-pointer hover:text-blue-600 font-medium" 
                                      onclick="processarServicoRapido(<?= $servico['id'] ?>, '<?= $servico['status'] ?>')"
                                      title="Clique para processar este serviço">
                                    <?= htmlspecialchars($servico['nome_servico']) ?>
                                </span>
                                
                                <!-- Badge de Status -->
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
                                <span class="px-2 py-1 text-xs font-medium rounded <?= $statusConfig['bg'] ?>">
                                    <?= $statusConfig['icon'] ?> <?= $statusConfig['label'] ?>
                                </span>
                                
                                <!-- Badge de Categoria -->
                                <span class="px-2 py-1 bg-purple-100 text-purple-700 text-xs rounded">
                                    <?= ucfirst($servico['categoria']) ?>
                                </span>
                            </div>
                            
                            <!-- Ações do Serviço -->
                            <div class="flex items-center gap-1">
                                <!-- Botão Ver Detalhes -->
                                <button onclick="verDetalhesServico(<?= $servico['id'] ?>)" 
                                        class="px-2 py-1 bg-gray-600 text-white text-xs rounded hover:bg-gray-700" title="Ver detalhes completos">
                                    👁️
                                </button>
                                
                                <?php if ($servico['status'] === 'mapeado'): ?>
                                <button onclick="processarServico(<?= $servico['id'] ?>, 'detalhar')" 
                                        class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">
                                    🔍 Detalhar
                                </button>
                                <button onclick="processarServico(<?= $servico['id'] ?>, 'processo_completo')" 
                                        class="px-3 py-1 bg-purple-600 text-white text-xs rounded hover:bg-purple-700">
                                    ⚡ Completo
                                </button>
                                <?php elseif ($servico['status'] === 'detalhado'): ?>
                                <button onclick="processarServico(<?= $servico['id'] ?>, 'gerar_sop')" 
                                        class="px-3 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700">
                                    📝 Gerar SOP
                                </button>
                                <?php elseif ($servico['status'] === 'sop_gerado' || $servico['status'] === 'aprovado'): ?>
                                <a href="<?= APP_URL ?>/sop/ver-sop-individual?id=<?= $servico['sop_id'] ?>" 
                                   class="px-3 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700">
                                    📋 Ver SOP
                                </a>
                                <button onclick="regenerarSopHierarquia(<?= $servico['sop_id'] ?>)" 
                                        class="px-3 py-1 bg-orange-600 text-white text-xs rounded hover:bg-orange-700">
                                    🔄 Regenerar
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4 text-gray-500">
                        <p class="mb-3">Nenhum serviço mapeado para este setor</p>
                        <div class="flex flex-wrap gap-2 justify-center">
                            <button onclick="adicionarServicoManual(<?= $setor['id'] ?>)" 
                                    class="px-3 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                                ➕ Adicionar Manual
                            </button>
                            <button onclick="adicionarServicoAudio(<?= $setor['id'] ?>)" 
                                    class="px-3 py-2 bg-orange-600 text-white rounded-lg text-sm hover:bg-orange-700">
                                🎤 Adicionar por Áudio
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="text-center py-8 text-gray-500">
        <p class="mb-4">Nenhum setor mapeado ainda</p>
        <button onclick="criarEstruturaOrganizacional()" 
                class="px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-medium">
            🏗️ Criar Estrutura Organizacional
        </button>
    </div>
    <?php endif; ?>
</div>

<?php else: ?>
<!-- Estrutura não existe -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
    <div class="mb-4">
        <span class="text-6xl">🏗️</span>
    </div>
    <h2 class="text-xl font-semibold text-gray-800 mb-2">Estrutura Organizacional não encontrada</h2>
    <p class="text-gray-600 mb-6">
        Para gerenciar a hierarquia Setor > Serviços > SOPs, primeiro é necessário criar a estrutura organizacional baseada no diagnóstico.
    </p>
    <button onclick="criarEstruturaOrganizacional()" 
            class="px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-medium">
        🏗️ Criar Estrutura Organizacional Agora
    </button>
</div>
<?php endif; ?>

<!-- Modal de Loading -->
<div id="modalLoading" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-8 text-center max-w-md w-full mx-4 shadow-xl">
        <div class="inline-block w-12 h-12 border-4 border-gray-200 border-t-primary rounded-full animate-spin mb-4"></div>
        <h3 class="text-lg font-medium text-gray-800 mb-2" id="loadingTitulo">Processando...</h3>
        <p class="text-sm text-gray-500" id="loadingSubtitulo">Aguarde...</p>
        <div class="mt-4">
            <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-full bg-primary rounded-full transition-all duration-1000" id="loadingProgress" style="width: 0%"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Adicionar Serviço Manual -->
<div id="modalAdicionarServico" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 max-w-lg w-full mx-4 shadow-xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Adicionar Serviço Manual</h3>
            <button onclick="fecharModalAdicionarServico()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="formAdicionarServico">
            <input type="hidden" name="setor_id" id="adicionarSetorId">
            <input type="hidden" name="empresa_id" value="<?= $dados['hierarquia']['estrutura']['empresa_id'] ?? 0 ?>">
            <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Serviço *</label>
                    <input type="text" name="nome_servico" placeholder="Ex: Atendimento ao Cliente, Controle de Estoque..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary"
                           maxlength="255" required>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                        <select name="categoria" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary">
                            <option value="operacional">Operacional</option>
                            <option value="core">Core</option>
                            <option value="estrategico">Estratégico</option>
                            <option value="integracao">Integração</option>
                            <option value="excecao">Exceção</option>
                            <option value="crise">Crise</option>
                            <option value="conformidade">Conformidade</option>
                            <option value="sazonal">Sazonal</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Criticidade</label>
                        <select name="criticidade" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary">
                            <option value="media">Média</option>
                            <option value="baixa">Baixa</option>
                            <option value="alta">Alta</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Frequência</label>
                    <select name="frequencia" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary">
                        <option value="diaria">Diária</option>
                        <option value="semanal">Semanal</option>
                        <option value="mensal">Mensal</option>
                        <option value="trimestral">Trimestral</option>
                        <option value="anual">Anual</option>
                        <option value="sob_demanda">Sob Demanda</option>
                        <option value="emergencial">Emergencial</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                    <textarea name="descricao" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary"
                              placeholder="Descreva brevemente o que este serviço envolve..."></textarea>
                </div>
            </div>
            
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="fecharModalAdicionarServico()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="button" onclick="salvarServicoManual()" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-700">
                    Adicionar Serviço
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para Adicionar Serviço por Áudio -->
<div id="modalAdicionarAudio" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 max-w-lg w-full mx-4 shadow-xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Adicionar Serviço por Áudio</h3>
            <button onclick="fecharModalAdicionarAudio()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="formAdicionarAudio" enctype="multipart/form-data">
            <input type="hidden" name="setor_id" id="adicionarAudioSetorId">
            <input type="hidden" name="empresa_id" value="<?= $dados['hierarquia']['estrutura']['empresa_id'] ?? 0 ?>">
            <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Arquivo de Áudio *</label>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                        <i class="fas fa-microphone text-3xl text-gray-400 mb-3"></i>
                        <input type="file" name="audio" id="audioFile" accept="audio/*" class="hidden" required>
                        <button type="button" onclick="document.getElementById('audioFile').click()" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Selecionar Arquivo de Áudio
                        </button>
                        <p class="text-xs text-gray-500 mt-2">
                            Formatos suportados: MP3, WAV, M4A, OGG<br>
                            Tamanho máximo: 25MB
                        </p>
                        <div id="audioFileInfo" class="hidden mt-2 text-sm text-green-600"></div>
                    </div>
                </div>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-start gap-2">
                        <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                        <div class="text-sm text-blue-700">
                            <strong>Dica:</strong> Grave um áudio explicando o serviço que você quer adicionar. 
                            Descreva o que é feito, como é feito e quais são os principais passos.
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="fecharModalAdicionarAudio()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="button" onclick="salvarServicoAudio()" class="flex-1 px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                    Transcrever e Adicionar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const DIAGNOSTICO_ID = <?= $dados['diagnostico_id'] ?>;
const ESTRUTURA_ID = <?= $dados['estrutura_id'] ?? 0 ?>;

// Toggle setor
function toggleSetor(setorId) {
    const setor = document.getElementById(setorId);
    const icon = document.getElementById('icon-' + setorId);
    
    if (setor.classList.contains('hidden')) {
        setor.classList.remove('hidden');
        icon.style.transform = 'rotate(180deg)';
    } else {
        setor.classList.add('hidden');
        icon.style.transform = 'rotate(0deg)';
    }
}

// Criar estrutura organizacional
async function criarEstruturaOrganizacional() {
    mostrarLoading('Criando Estrutura Organizacional', 'Analisando diagnóstico e definindo setores...');
    
    try {
        const response = await fetch('<?= APP_URL ?>/sop/gerar-manual-completo', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                diagnostico_id: DIAGNOSTICO_ID,
                csrf_token: '<?= Csrf::token() ?>'
            })
        });
        
        const data = await response.json();
        
        if (data.sucesso) {
            atualizarLoading('Estrutura Criada!', 'Redirecionando para gestão hierárquica...', 100);
            setTimeout(() => {
                // Garantir que redirect funciona corretamente
                const redirectUrl = data.redirect || '<?= APP_URL ?>/sop/gerenciar-hierarquia?diagnostico_id=' + DIAGNOSTICO_ID;
                window.location.href = redirectUrl;
            }, 2000);
        } else {
            esconderLoading();
            alert('Erro ao criar estrutura: ' + (data.erro || 'Erro desconhecido'));
        }
    } catch (error) {
        esconderLoading();
        console.error('Erro:', error);
        alert('Erro de comunicação com o servidor');
    }
}

// Processar serviço de forma rápida (clique direto no card)
async function processarServicoRapido(servicoId, status) {
    let etapa, titulo, subtitulo;
    
    switch(status) {
        case 'mapeado':
            etapa = 'processo_completo';
            titulo = 'Processo Completo: Detalhar + Gerar SOP';
            subtitulo = 'Executando detalhamento e geração de SOP automaticamente...';
            break;
        case 'detalhado':
            etapa = 'gerar_sop';
            titulo = 'Gerando SOP';
            subtitulo = 'Criando procedimento operacional padrão...';
            break;
        case 'sop_gerado':
        case 'aprovado':
            // Redirecionar para visualização do SOP
            const servicoElement = document.querySelector(`[onclick*="processarServicoRapido(${servicoId}"]`);
            const codigo = servicoElement ? servicoElement.querySelector('div:contains("Código:")') : null;
            alert('Redirecionando para visualização do SOP...');
            // TODO: Implementar redirecionamento para SOP específico
            return;
        default:
            alert('Status do serviço não reconhecido');
            return;
    }
    
    await processarServico(servicoId, etapa, titulo, subtitulo);
}

// Processar serviço (detalhar, gerar SOP ou processo completo) - VERSÃO ATUALIZADA
async function processarServico(servicoId, etapa, tituloCustom = null, subtituloCustom = null) {
    const etapaTitulos = {
        'detalhar': 'Detalhando Serviço',
        'gerar_sop': 'Gerando SOP',
        'processo_completo': 'Processo Completo: Detalhar + Gerar SOP'
    };
    
    const etapaSubtitulos = {
        'detalhar': 'Analisando requisitos e definindo processos detalhados...',
        'gerar_sop': 'Criando procedimento operacional padrão...',
        'processo_completo': 'Executando detalhamento e geração de SOP automaticamente...'
    };
    
    const titulo = tituloCustom || etapaTitulos[etapa];
    const subtitulo = subtituloCustom || etapaSubtitulos[etapa];
    
    mostrarLoading(titulo, subtitulo);
    
    try {
        const response = await fetch('<?= APP_URL ?>/sop/processar-servico-completo', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                servico_id: servicoId,
                etapa: etapa,
                csrf_token: '<?= Csrf::token() ?>'
            })
        });
        
        const data = await response.json();
        
        if (data.sucesso) {
            atualizarLoading('Sucesso!', data.mensagem, 100);
            
            setTimeout(() => {
                if (data.sop_url) {
                    // Perguntar se quer ver o SOP ou recarregar a página
                    if (confirm('Processo concluído! Deseja visualizar o SOP gerado?')) {
                        window.open(data.sop_url, '_blank');
                    }
                }
                window.location.reload();
            }, 2000);
        } else {
            esconderLoading();
            alert('Erro no processamento: ' + (data.erro || 'Erro desconhecido'));
        }
    } catch (error) {
        esconderLoading();
        console.error('Erro:', error);
        alert('Erro de comunicação com o servidor');
    }
}

// Processar serviço (detalhar, gerar SOP ou processo completo) - VERSÃO ORIGINAL REMOVIDA

// Ver detalhamento de serviço
function verDetalhamento(servicoId) {
    window.open('<?= APP_URL ?>/sop/ver-detalhamento-servico?servico_id=' + servicoId, '_blank');
}

// Ver detalhes completos do serviço (NOVA FUNÇÃO)
function verDetalhesServico(servicoId) {
    window.open('<?= APP_URL ?>/sop/ver-detalhes-servico?servico_id=' + servicoId, '_blank');
}

// Adicionar serviço manual
function adicionarServicoManual(setorId) {
    document.getElementById('adicionarSetorId').value = setorId;
    document.getElementById('modalAdicionarServico').classList.remove('hidden');
}

// Fechar modal adicionar serviço
function fecharModalAdicionarServico() {
    document.getElementById('modalAdicionarServico').classList.add('hidden');
    document.getElementById('formAdicionarServico').reset();
}

// Salvar serviço manual
async function salvarServicoManual() {
    const form = document.getElementById('formAdicionarServico');
    const formData = new FormData(form);
    
    // Validação básica
    const nomeServico = formData.get('nome_servico').trim();
    if (!nomeServico) {
        alert('Nome do serviço é obrigatório');
        return;
    }
    
    mostrarLoading('Adicionando Serviço', 'Salvando informações...');
    
    try {
        const response = await fetch('<?= APP_URL ?>/sop/adicionar-servico-manual', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.sucesso) {
            esconderLoading();
            alert('Serviço adicionado com sucesso!');
            fecharModalAdicionarServico();
            window.location.reload();
        } else {
            esconderLoading();
            alert('Erro ao adicionar serviço: ' + (data.erro || 'Erro desconhecido'));
        }
    } catch (error) {
        esconderLoading();
        console.error('Erro:', error);
        alert('Erro de comunicação com o servidor');
    }
}

// Adicionar serviço por áudio
function adicionarServicoAudio(setorId) {
    document.getElementById('adicionarAudioSetorId').value = setorId;
    document.getElementById('modalAdicionarAudio').classList.remove('hidden');
}

// Fechar modal adicionar por áudio
function fecharModalAdicionarAudio() {
    document.getElementById('modalAdicionarAudio').classList.add('hidden');
    document.getElementById('formAdicionarAudio').reset();
    document.getElementById('audioFileInfo').classList.add('hidden');
}

// Salvar serviço por áudio
async function salvarServicoAudio() {
    const form = document.getElementById('formAdicionarAudio');
    const formData = new FormData(form);
    const audioFile = document.getElementById('audioFile').files[0];
    
    // Validação
    if (!audioFile) {
        alert('Selecione um arquivo de áudio');
        return;
    }
    
    // Validar tamanho (25MB)
    if (audioFile.size > 25 * 1024 * 1024) {
        alert('Arquivo muito grande. Máximo 25MB.');
        return;
    }
    
    mostrarLoading('Transcrevendo Áudio', 'Processando áudio e criando serviço...');
    
    try {
        const response = await fetch('<?= APP_URL ?>/sop/adicionar-servico-audio', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.sucesso) {
            esconderLoading();
            alert('Serviço criado com sucesso a partir da transcrição!\n\nNome: ' + data.nome_servico + '\nCódigo: ' + data.codigo_servico);
            fecharModalAdicionarAudio();
            window.location.reload();
        } else {
            esconderLoading();
            alert('Erro ao processar áudio: ' + (data.erro || 'Erro desconhecido'));
        }
    } catch (error) {
        esconderLoading();
        console.error('Erro:', error);
        alert('Erro de comunicação com o servidor');
    }
}

// Regenerar estrutura
async function regenerarEstrutura() {
    if (!confirm('Tem certeza que deseja regenerar a estrutura organizacional? Isso pode afetar serviços já detalhados.')) {
        return;
    }
    
    criarEstruturaOrganizacional();
}

// Exportar manual completo
function exportarManualCompleto() {
    window.location.href = '<?= APP_URL ?>/sop/exportar-todos-zip?estrutura_id=' + ESTRUTURA_ID;
}

// Funções de loading
function mostrarLoading(titulo, subtitulo) {
    document.getElementById('loadingTitulo').textContent = titulo;
    document.getElementById('loadingSubtitulo').textContent = subtitulo;
    document.getElementById('loadingProgress').style.width = '10%';
    document.getElementById('modalLoading').classList.remove('hidden');
    
    // Simular progresso
    let progresso = 10;
    const intervalo = setInterval(() => {
        progresso += Math.random() * 15;
        if (progresso > 90) {
            progresso = 90;
            clearInterval(intervalo);
        }
        document.getElementById('loadingProgress').style.width = progresso + '%';
    }, 500);
}

function atualizarLoading(titulo, subtitulo, progresso = null) {
    document.getElementById('loadingTitulo').textContent = titulo;
    document.getElementById('loadingSubtitulo').textContent = subtitulo;
    if (progresso !== null) {
        document.getElementById('loadingProgress').style.width = progresso + '%';
    }
}

function esconderLoading() {
    document.getElementById('modalLoading').classList.add('hidden');
}

// Regenerar SOP a partir da hierarquia
async function regenerarSopHierarquia(sopId) {
    if (!confirm('Deseja regenerar este SOP? Esta ação irá recriar todo o conteúdo usando IA.')) {
        return;
    }
    
    mostrarLoading('Regenerando SOP', 'Recriando procedimento operacional...');
    
    try {
        const response = await fetch('<?= APP_URL ?>/sop/regenerar-sop-individual', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= Csrf::token() ?>'
            },
            body: JSON.stringify({
                sop_id: sopId
            })
        });
        
        const data = await response.json();
        
        if (data.sucesso) {
            atualizarLoading('SOP Regenerado!', 'SOP atualizado com sucesso', 100);
            
            setTimeout(() => {
                esconderLoading();
                if (confirm('SOP regenerado com sucesso! Deseja visualizar o resultado?')) {
                    window.open('<?= APP_URL ?>/sop/ver-sop-individual?id=' + sopId, '_blank');
                }
            }, 2000);
        } else {
            esconderLoading();
            alert('Erro ao regenerar SOP: ' + (data.erro || 'Erro desconhecido'));
        }
    } catch (error) {
        esconderLoading();
        console.error('Erro:', error);
        alert('Erro de comunicação com o servidor');
    }
}

// Expandir todos os setores por padrão se houver poucos
document.addEventListener('DOMContentLoaded', function() {
    const setores = document.querySelectorAll('[id^="setor-"]');
    
    // Expandir todos os setores automaticamente para melhor usabilidade
    setores.forEach(setor => {
        setor.classList.remove('hidden');
        const icon = document.getElementById('icon-' + setor.id);
        if (icon) icon.style.transform = 'rotate(180deg)';
    });
    
    // Listener para arquivo de áudio
    const audioInput = document.getElementById('audioFile');
    if (audioInput) {
        audioInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const infoDiv = document.getElementById('audioFileInfo');
            
            if (file) {
                const sizeInMB = (file.size / 1024 / 1024).toFixed(2);
                infoDiv.textContent = `Arquivo selecionado: ${file.name} (${sizeInMB} MB)`;
                infoDiv.classList.remove('hidden');
            } else {
                infoDiv.classList.add('hidden');
            }
        });
    }
});
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>