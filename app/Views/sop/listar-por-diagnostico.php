<?php $tituloPagina = 'SOPs - ' . htmlspecialchars($dados['empresa']['nome']); ?>
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
            <h1 class="text-2xl font-bold text-gray-800">SOPs - <?= htmlspecialchars($dados['empresa']['nome']) ?></h1>
            <p class="text-gray-600 mt-1">
                Procedimentos Operacionais Padrão organizados por setores
                <br><span class="text-sm text-gray-500">🎯 Clique em qualquer serviço para ver ou gerenciar seu SOP</span>
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
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-blue-600">Setores</div>
                    <div class="text-2xl font-bold text-blue-700"><?= $dados['estatisticas']['total_setores'] ?></div>
                </div>
                <div class="text-blue-400">🏢</div>
            </div>
        </div>

        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-purple-600">Serviços</div>
                    <div class="text-2xl font-bold text-purple-700"><?= $dados['estatisticas']['total_servicos'] ?></div>
                </div>
                <div class="text-purple-400">⚙️</div>
            </div>
        </div>

        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-green-600">SOPs Gerados</div>
                    <div class="text-2xl font-bold text-green-700"><?= $dados['estatisticas']['total_sops'] ?></div>
                </div>
                <div class="text-green-400">📋</div>
            </div>
        </div>

        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-orange-600">Progresso</div>
                    <div class="text-2xl font-bold text-orange-700"><?= $dados['estatisticas']['percentual_conclusao'] ?>%</div>
                </div>
                <div class="text-orange-400">📊</div>
            </div>
        </div>
    </div>
</div>

<!-- FLUXO LINEAR: Setores > Serviços > SOPs -->
<div class="space-y-6">
    <?php if (!empty($dados['setores_organizados'])): ?>
        <?php foreach ($dados['setores_organizados'] as $setorData): ?>
        <?php $setor = $setorData['setor']; ?>
        <?php $servicos = $setorData['servicos']; ?>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <!-- Cabeçalho do Setor -->
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-xl">
                            <?php 
                            switch($setor['tipo_setor'] ?? 'operacional') {
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
                            <h2 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($setor['nome_setor']) ?></h2>
                            <div class="flex items-center gap-2 text-sm text-gray-500">
                                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded"><?= ucfirst($setor['tipo_setor'] ?? 'geral') ?></span>
                                <span><?= $setor['total_servicos'] ?? 0 ?> serviços</span>
                                <span class="text-green-600"><?= $setor['total_sops'] ?? 0 ?> SOPs</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <?php 
                        $totalServicos = $setor['total_servicos'] ?? 0;
                        $totalSops = $setor['total_sops'] ?? 0;
                        ?>
                        <!-- Indicador de Status do Setor -->
                        <?php if ($totalSops == $totalServicos && $totalServicos > 0): ?>
                        <span class="px-3 py-1 bg-green-100 text-green-700 text-sm rounded-full font-medium">✓ Completo</span>
                        <?php elseif ($totalSops > 0): ?>
                        <span class="px-3 py-1 bg-yellow-100 text-yellow-700 text-sm rounded-full font-medium">⚠ Parcial (<?= $totalSops ?>/<?= $totalServicos ?>)</span>
                        <?php else: ?>
                        <span class="px-3 py-1 bg-gray-100 text-gray-600 text-sm rounded-full font-medium">○ Pendente</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Lista de Serviços do Setor (agrupada por subcategoria) -->
            <div class="p-6">
                <!-- Botão de adicionar serviço ao setor -->
                <div class="flex justify-end mb-4">
                    <button onclick="abrirModalAddServico(<?= $setor['setor_id'] ?>, '<?= htmlspecialchars($setor['nome_setor'], ENT_QUOTES) ?>')"
                            class="px-3 py-1.5 bg-primary text-white text-sm rounded-lg hover:bg-primary-700">
                        ➕ Adicionar serviço
                    </button>
                </div>

                <?php if (!empty($servicos)): ?>
                    <?php
                    // Agrupar serviços por subcategoria
                    $porSub = [];
                    foreach ($servicos as $sv) {
                        $sub = $sv['subcategoria'] ?? 'Geral';
                        $porSub[$sub][] = $sv;
                    }
                    ?>
                    <?php foreach ($porSub as $subcategoria => $itens): ?>
                    <div class="mb-6">
                        <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3 border-b border-gray-100 pb-1">
                            <?= htmlspecialchars($subcategoria) ?>
                            <span class="text-gray-400 font-normal">(<?= count($itens) ?>)</span>
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php foreach ($itens as $servico): ?>
                            <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 hover:bg-blue-50 transition">
                                <!-- Cabeçalho do Serviço -->
                                <div class="flex items-center justify-between mb-3">
                                    <code class="text-xs font-mono bg-gray-100 px-2 py-1 rounded"><?= htmlspecialchars($servico['codigo_servico'] ?? 'N/A') ?></code>
                                    <?php
                                    switch($servico['status_final'] ?? 'mapeado') {
                                        case 'mapeado':
                                            $statusConfig = ['bg' => 'bg-gray-100 text-gray-600', 'icon' => '○', 'label' => 'Mapeado'];
                                            break;
                                        case 'detalhado':
                                            $statusConfig = ['bg' => 'bg-blue-100 text-blue-700', 'icon' => '◐', 'label' => 'Detalhado'];
                                            break;
                                        case 'sop_gerado':
                                            $statusConfig = ['bg' => 'bg-green-100 text-green-700', 'icon' => '●', 'label' => 'SOP Pronto'];
                                            break;
                                        default:
                                            $statusConfig = ['bg' => 'bg-gray-100 text-gray-500', 'icon' => '?', 'label' => 'Indefinido'];
                                            break;
                                    }
                                    ?>
                                    <span class="px-2 py-1 text-xs font-medium rounded <?= $statusConfig['bg'] ?>">
                                        <?= $statusConfig['icon'] ?> <?= $statusConfig['label'] ?>
                                    </span>
                                </div>

                                <!-- Nome (clicável para acessar/gerar SOP) -->
                                <h3 class="font-medium text-gray-800 mb-2 leading-tight cursor-pointer hover:text-blue-600"
                                    onclick="acessarServico(<?= $servico['id'] ?>, '<?= $servico['status_final'] ?>', <?= $servico['sop_id'] ?? 'null' ?>)">
                                    <?= htmlspecialchars($servico['nome_servico'] ?? 'Serviço sem nome') ?>
                                </h3>

                                <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
                                    <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded"><?= ucfirst($servico['categoria'] ?? 'geral') ?></span>
                                    <?php if (($servico['status_final'] ?? '') === 'sop_gerado'): ?>
                                    <span class="text-green-600 font-medium">👆 Ver SOP</span>
                                    <?php else: ?>
                                    <span class="text-gray-600 font-medium">👆 Gerar SOP</span>
                                    <?php endif; ?>
                                </div>

                                <!-- Ações CRUD -->
                                <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
                                    <button onclick="abrirModalEditServico(<?= $servico['id'] ?>, '<?= htmlspecialchars($servico['nome_servico'], ENT_QUOTES) ?>', '<?= htmlspecialchars($servico['categoria'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($servico['criticidade'] ?? '', ENT_QUOTES) ?>')"
                                            class="text-xs text-blue-600 hover:underline">✏️ Editar</button>
                                    <button onclick="excluirServico(<?= $servico['id'] ?>, '<?= htmlspecialchars($servico['nome_servico'], ENT_QUOTES) ?>')"
                                            class="text-xs text-red-600 hover:underline">🗑️ Excluir</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <div class="text-center py-8 text-gray-500">
                    <p>Nenhum serviço neste setor. Use "Adicionar serviço" para criar.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
    <!-- Se não existem setores/SOPs -->
    <div class="text-center py-12">
        <div class="text-6xl mb-4">📋</div>
        <h2 class="text-xl font-semibold text-gray-800 mb-2">Nenhum SOP encontrado</h2>
        <p class="text-gray-600 mb-6">
            Para começar a usar SOPs, primeiro você precisa gerar a estrutura organizacional.
        </p>
        <a href="<?= APP_URL ?>/sop?diagnostico_id=<?= $dados['diagnostico']['id'] ?>" 
           class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-700 font-medium">
            🚀 Iniciar Geração de SOPs
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Modal: Adicionar Serviço -->
<div id="modalAddServico" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 max-w-lg w-full mx-4 shadow-xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold" id="addServicoTitulo">Adicionar Serviço</h3>
            <button onclick="fecharModal('modalAddServico')" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <input type="hidden" id="add_setor_id">
        <div class="space-y-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Serviço *</label>
                <input type="text" id="add_nome" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Ex: Controle de estoque de insumos">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Subcategoria</label>
                <input type="text" id="add_subcategoria" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Ex: Personalizado" value="Personalizado">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                    <select id="add_categoria" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="operacional">Operacional</option>
                        <option value="core">Core</option>
                        <option value="estrategico">Estratégico</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Criticidade</label>
                    <select id="add_criticidade" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="media">Média</option>
                        <option value="baixa">Baixa</option>
                        <option value="alta">Alta</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="flex gap-3 mt-6">
            <button onclick="fecharModal('modalAddServico')" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg">Cancelar</button>
            <button onclick="salvarNovoServico()" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-700">Adicionar</button>
        </div>
    </div>
</div>

<!-- Modal: Editar Serviço -->
<div id="modalEditServico" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 max-w-lg w-full mx-4 shadow-xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Editar Serviço</h3>
            <button onclick="fecharModal('modalEditServico')" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <input type="hidden" id="edit_servico_id">
        <div class="space-y-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Serviço *</label>
                <input type="text" id="edit_nome" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                    <select id="edit_categoria" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="operacional">Operacional</option>
                        <option value="core">Core</option>
                        <option value="estrategico">Estratégico</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Criticidade</label>
                    <select id="edit_criticidade" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="media">Média</option>
                        <option value="baixa">Baixa</option>
                        <option value="alta">Alta</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="flex gap-3 mt-6">
            <button onclick="fecharModal('modalEditServico')" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg">Cancelar</button>
            <button onclick="salvarEdicaoServico()" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-700">Salvar</button>
        </div>
    </div>
</div>

<script>
const CSRF_TOKEN = '<?= Csrf::token() ?>';

// Acessar o serviço: abre a página de detalhes (ver/gerar SOP)
function acessarServico(servicoId, status, sopId) {
    window.location.href = '<?= APP_URL ?>/sop/ver-detalhes-servico?servico_id=' + servicoId;
}

function fecharModal(id) { document.getElementById(id).classList.add('hidden'); }

// ---- Adicionar ----
function abrirModalAddServico(setorId, nomeSetor) {
    document.getElementById('add_setor_id').value = setorId;
    document.getElementById('add_nome').value = '';
    document.getElementById('add_subcategoria').value = 'Personalizado';
    document.getElementById('addServicoTitulo').textContent = 'Adicionar Serviço — ' + nomeSetor;
    document.getElementById('modalAddServico').classList.remove('hidden');
}

async function salvarNovoServico() {
    const nome = document.getElementById('add_nome').value.trim();
    if (!nome) { alert('Informe o nome do serviço.'); return; }
    const body = new URLSearchParams({
        setor_id: document.getElementById('add_setor_id').value,
        nome_servico: nome,
        subcategoria: document.getElementById('add_subcategoria').value.trim() || 'Personalizado',
        categoria: document.getElementById('add_categoria').value,
        criticidade: document.getElementById('add_criticidade').value,
        csrf_token: CSRF_TOKEN
    });
    try {
        const r = await fetch('<?= APP_URL ?>/sop/adicionar-servico-manual', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body });
        const d = await r.json();
        if (d.sucesso) { location.reload(); } else { alert('Erro: ' + (d.erro || 'desconhecido')); }
    } catch (e) { alert('Erro de comunicação com o servidor.'); }
}

// ---- Editar ----
function abrirModalEditServico(servicoId, nome, categoria, criticidade) {
    document.getElementById('edit_servico_id').value = servicoId;
    document.getElementById('edit_nome').value = nome;
    if (categoria) document.getElementById('edit_categoria').value = categoria;
    if (criticidade) document.getElementById('edit_criticidade').value = criticidade;
    document.getElementById('modalEditServico').classList.remove('hidden');
}

async function salvarEdicaoServico() {
    const nome = document.getElementById('edit_nome').value.trim();
    if (!nome) { alert('Informe o nome do serviço.'); return; }
    const body = new URLSearchParams({
        servico_id: document.getElementById('edit_servico_id').value,
        nome_servico: nome,
        categoria: document.getElementById('edit_categoria').value,
        criticidade: document.getElementById('edit_criticidade').value,
        csrf_token: CSRF_TOKEN
    });
    try {
        const r = await fetch('<?= APP_URL ?>/sop/editar-servico-manual', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body });
        const d = await r.json();
        if (d.sucesso) { location.reload(); } else { alert('Erro: ' + (d.erro || 'desconhecido')); }
    } catch (e) { alert('Erro de comunicação com o servidor.'); }
}

// ---- Excluir ----
async function excluirServico(servicoId, nome) {
    if (!confirm('Excluir o serviço "' + nome + '"? Esta ação não pode ser desfeita.')) return;
    const body = new URLSearchParams({ servico_id: servicoId, csrf_token: CSRF_TOKEN });
    try {
        const r = await fetch('<?= APP_URL ?>/sop/excluir-servico', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body });
        const d = await r.json();
        if (d.sucesso) { location.reload(); } else { alert('Erro: ' + (d.erro || 'desconhecido')); }
    } catch (e) { alert('Erro de comunicação com o servidor.'); }
}
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
