<?php $tituloPagina = 'Manual Operacional'; ?>
<?php ob_start(); ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Manual Operacional</li>
    </ol>
</nav>

<!-- Header Principal -->
<div class="flex flex-col lg:flex-row items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Manual Operacional</h1>
        <p class="text-gray-500 mt-1">Sistema inteligente de SOPs organizacionais</p>
    </div>
</div>

<!-- Sistema Hierárquico: Empresas > SOPs > Tipos > Detalhes -->
<div class="space-y-6">
    
    <!-- NÍVEL 1: Seleção de Empresa (para ADMIN_HOLDING) -->
    <?php if (Auth::perfil() === 'ADMIN_HOLDING'): ?>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center gap-3 mb-4">
            <span class="text-2xl">🏢</span>
            <div>
                <h2 class="text-lg font-semibold text-gray-800">1. Selecionar Empresa</h2>
                <p class="text-sm text-gray-500">Escolha a empresa para gerenciar seus SOPs</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php if (!empty($dados['empresas_disponiveis'])): ?>
                <?php foreach ($dados['empresas_disponiveis'] as $empresa): ?>
                <div class="border border-gray-200 rounded-lg p-4 hover:border-primary hover:bg-primary/5 cursor-pointer transition"
                     onclick="selecionarEmpresa(<?= $empresa['id'] ?>)"
                     id="empresa-<?= $empresa['id'] ?>">
                    <h3 class="font-medium text-gray-800"><?= htmlspecialchars($empresa['nome']) ?></h3>
                    <p class="text-sm text-gray-500"><?= htmlspecialchars($empresa['segmento']) ?></p>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded"><?= $empresa['total_sops'] ?? 0 ?> SOPs</span>
                        <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded"><?= $empresa['aprovados'] ?? 0 ?> Aprovados</span>
                    </div>
                    <!-- Indicador de seleção -->
                    <div class="mt-2 text-center opacity-0 transition-opacity" id="selected-<?= $empresa['id'] ?>">
                        <span class="text-xs font-medium text-primary">✓ Selecionada</span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full text-center py-8">
                    <p class="text-gray-500">Nenhuma empresa cadastrada no sistema</p>
                    <a href="<?= APP_URL ?>/admin/clientes" class="inline-block mt-2 px-4 py-2 bg-primary text-white rounded-lg text-sm">
                        Cadastrar Empresa
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- NÍVEL 2: Empresa Selecionada -->
    <?php if (!empty($dados['empresa_atual'])): ?>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <span class="text-2xl">🏭</span>
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">2. Empresa: <?= htmlspecialchars($dados['empresa_atual']['nome']) ?></h2>
                    <p class="text-sm text-gray-500">Setor: <?= htmlspecialchars($dados['empresa_atual']['segmento']) ?></p>
                </div>
            </div>
            <div class="flex gap-2">
                <?php if (Auth::perfil() === 'ADMIN_HOLDING' || Auth::perfil() === 'CONSULTOR_INTERNO'): ?>
                <button onclick="abrirModalNovoSOP()" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700">
                    ➕ Criar Novo SOP
                </button>
                <?php endif; ?>
                <button onclick="exportarTodosSops()" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700">
                    📦 Exportar Todos
                </button>
            </div>
        </div>

        <!-- Progresso Geral -->
        <div class="bg-gray-50 rounded-lg p-4 mb-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700"><?= $dados['empresa_atual']['aprovados'] ?> de <?= $dados['empresa_atual']['total_sops'] ?> SOPs aprovados</span>
                <span class="text-sm font-bold text-primary"><?= $dados['empresa_atual']['total_sops'] > 0 ? round(($dados['empresa_atual']['aprovados'] / $dados['empresa_atual']['total_sops']) * 100) : 0 ?>%</span>
            </div>
            <div class="w-full h-3 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-full bg-green-500 rounded-full transition-all" style="width: <?= $dados['empresa_atual']['total_sops'] > 0 ? round(($dados['empresa_atual']['aprovados'] / $dados['empresa_atual']['total_sops']) * 100) : 0 ?>%"></div>
            </div>
        </div>
    </div>

    <!-- NÍVEL 3: SOPs por Departamento/Categoria -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center gap-3 mb-4">
            <span class="text-2xl">📋</span>
            <div>
                <h2 class="text-lg font-semibold text-gray-800">3. SOPs Organizados por Departamento</h2>
                <p class="text-sm text-gray-500">Procedimentos baseados no setor <?= htmlspecialchars($dados['empresa_atual']['segmento']) ?></p>
            </div>
        </div>

        <div class="space-y-4">
            <?php if (!empty($dados['departamentos'])): ?>
                <?php foreach ($dados['departamentos'] as $dept): 
                    $aprovados = count(array_filter($dept['sops'], fn($s) => $s['status'] === 'aprovado'));
                    $total = count($dept['sops']);
                ?>
                <div class="border border-gray-200 rounded-lg">
                    <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between cursor-pointer"
                         onclick="toggleDepartamento('dept-<?= md5($dept['nome']) ?>')">
                        <div class="flex items-center gap-3">
                            <span class="text-xl"><?= $dept['icone'] ?></span>
                            <div>
                                <h3 class="font-medium text-gray-800"><?= htmlspecialchars($dept['nome']) ?></h3>
                                <p class="text-xs text-gray-500"><?= $aprovados ?>/<?= $total ?> SOPs aprovados</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <?php if ($aprovados === $total && $total > 0): ?>
                            <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded">✓ Completo</span>
                            <?php endif; ?>
                            <i class="fas fa-chevron-down text-gray-400 transform transition-transform" id="icon-dept-<?= md5($dept['nome']) ?>"></i>
                        </div>
                    </div>
                    
                    <!-- NÍVEL 4: Lista de SOPs do Departamento -->
                    <div class="hidden p-4" id="dept-<?= md5($dept['nome']) ?>">
                        <div class="space-y-2">
                            <?php if (!empty($dept['sops'])): ?>
                                <?php foreach ($dept['sops'] as $sop):
                                    $statusConfig = match($sop['status']) {
                                        'aprovado' => ['badge' => 'bg-green-100 text-green-700', 'label' => '✓ Aprovado', 'action' => 'ver'],
                                        'gerado' => ['badge' => 'bg-blue-100 text-blue-700', 'label' => '● Gerado', 'action' => 'revisar'],
                                        'em_revisao' => ['badge' => 'bg-yellow-100 text-yellow-700', 'label' => '◎ Em revisão', 'action' => 'revisar'],
                                        default => ['badge' => 'bg-gray-100 text-gray-500', 'label' => '○ Não gerado', 'action' => 'gerar'],
                                    };
                                ?>
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                                    <div class="flex items-center gap-3">
                                        <code class="px-2 py-1 bg-white text-xs text-gray-600 rounded"><?= htmlspecialchars($sop['id']) ?></code>
                                        <span class="text-sm text-gray-800"><?= htmlspecialchars($sop['nome']) ?></span>
                                        <?php if (!empty($sop['customizado'])): ?>
                                        <span class="px-2 py-1 bg-purple-100 text-purple-700 text-xs rounded font-medium">PERSONALIZADO</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-1 text-xs font-medium rounded <?= $statusConfig['badge'] ?>"><?= $statusConfig['label'] ?></span>
                                        <?php if ($statusConfig['action'] === 'gerar'): ?>
                                        <button onclick="gerarSop('<?= htmlspecialchars($sop['id']) ?>', '<?= htmlspecialchars($sop['nome']) ?>')"
                                                class="px-3 py-1 bg-orange-600 text-white text-xs rounded hover:bg-orange-700">
                                            🤖 Gerar
                                        </button>
                                        <?php elseif ($statusConfig['action'] === 'revisar'): ?>
                                        <a href="<?= APP_URL ?>/sop/revisar?id=<?= urlencode($sop['id']) ?>" 
                                           class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">
                                            📝 Revisar
                                        </a>
                                        <?php else: ?>
                                        <a href="<?= APP_URL ?>/sop/ver/<?= urlencode($sop['id']) ?>" 
                                           class="px-3 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700">
                                            👁️ Ver SOP
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-4 text-gray-500">
                                    <p>Nenhum SOP configurado para este departamento</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-8">
                    <p class="text-gray-500">Nenhum departamento configurado para esta empresa</p>
                    <p class="text-sm text-gray-400 mt-1">SOPs serão criados automaticamente baseados no setor da empresa</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- Modal para Criar Novo SOP -->
<div id="modalNovoSOP" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 max-w-lg w-full mx-4 shadow-xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Criar Novo SOP Personalizado</h3>
            <button onclick="fecharModalNovoSOP()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="formNovoSOP">
            <input type="hidden" name="empresa_id" value="<?= $dados['empresa_atual']['id'] ?? '' ?>">
            <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Código do SOP *</label>
                    <input type="text" name="sop_codigo" placeholder="SOP-CUSTOM-001" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary"
                           pattern="[A-Z0-9-]+" maxlength="50" required>
                    <p class="text-xs text-gray-500 mt-1">Apenas letras maiúsculas, números e hífen</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome do SOP *</label>
                    <input type="text" name="nome" placeholder="Nome do procedimento" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary"
                           maxlength="255" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Departamento *</label>
                    <input type="text" name="departamento" placeholder="Ex: Administrativo, Operacional..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary"
                           maxlength="100" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                    <textarea name="descricao" rows="3" placeholder="Descreva o que este SOP deve abordar..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary"></textarea>
                </div>
            </div>
            
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="fecharModalNovoSOP()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="button" onclick="salvarNovoSOP()" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-700">
                    Criar SOP
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de Loading -->
<div id="modalLoading" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-8 text-center max-w-sm w-full mx-4 shadow-xl">
        <div class="inline-block w-10 h-10 border-4 border-gray-200 border-t-primary rounded-full animate-spin mb-4"></div>
        <p class="text-sm font-medium text-gray-800" id="loadingTitulo">Processando...</p>
        <p class="text-xs text-gray-500 mt-1" id="loadingSubtitulo">Aguarde...</p>
    </div>
</div>

<script>
// Selecionar empresa (para ADMIN_HOLDING)
function selecionarEmpresa(empresaId) {
    const formData = new FormData();
    formData.append('empresa_id', empresaId);
    formData.append('csrf_token', '<?= Csrf::token() ?>');
    
    fetch('<?= APP_URL ?>/admin/selecionar-empresa', {
        method: 'POST',
        body: formData
    }).then(() => {
        window.location.reload();
    }).catch(error => {
        console.error('Erro:', error);
        alert('Erro ao selecionar empresa');
    });
}

// Toggle departamento
function toggleDepartamento(deptId) {
    const dept = document.getElementById(deptId);
    const icon = document.getElementById('icon-' + deptId);
    
    if (dept.classList.contains('hidden')) {
        dept.classList.remove('hidden');
        icon.style.transform = 'rotate(180deg)';
    } else {
        dept.classList.add('hidden');
        icon.style.transform = 'rotate(0deg)';
    }
}

// Gerar SOP
async function gerarSop(sopId, sopNome) {
    const modal = document.getElementById('modalLoading');
    document.getElementById('loadingTitulo').textContent = 'Gerando SOP: ' + sopNome;
    document.getElementById('loadingSubtitulo').textContent = 'Aplicando padrões baseados no setor da empresa...';
    modal.classList.remove('hidden');

    const formData = new FormData();
    formData.append('csrf_token', '<?= Csrf::token() ?>');
    formData.append('sop_id', sopId);
    formData.append('sop_nome', sopNome);

    try {
        const response = await fetch('<?= APP_URL ?>/sop/gerar', { method: 'POST', body: formData });
        const data = await response.json();
        
        if (data.sucesso) {
            window.location.href = data.redirect;
        } else {
            modal.classList.add('hidden');
            alert(data.erro || 'Erro ao gerar SOP.');
        }
    } catch (error) {
        modal.classList.add('hidden');
        console.error('Erro:', error);
        alert('Erro de conexão.');
    }
}

// Modal Novo SOP
function abrirModalNovoSOP() {
    document.getElementById('modalNovoSOP').classList.remove('hidden');
}

function fecharModalNovoSOP() {
    document.getElementById('modalNovoSOP').classList.add('hidden');
    document.getElementById('formNovoSOP').reset();
}

// Converter código para maiúsculas
document.addEventListener('DOMContentLoaded', function() {
    const codigoInput = document.querySelector('input[name="sop_codigo"]');
    if (codigoInput) {
        codigoInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    }
});

// Salvar novo SOP
async function salvarNovoSOP() {
    const form = document.getElementById('formNovoSOP');
    const formData = new FormData(form);
    
    // Validações
    const codigo = formData.get('sop_codigo').trim();
    const nome = formData.get('nome').trim();
    const departamento = formData.get('departamento').trim();
    
    if (!codigo || !nome || !departamento) {
        alert('Preencha todos os campos obrigatórios');
        return;
    }
    
    if (!/^[A-Z0-9-]+$/.test(codigo)) {
        alert('Código deve conter apenas letras maiúsculas, números e hífen');
        return;
    }
    
    try {
        const response = await fetch('<?= APP_URL ?>/sop/adicionar', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.sucesso) {
            alert(result.mensagem);
            fecharModalNovoSOP();
            setTimeout(() => {
                window.location.reload();
            }, 500);
        } else {
            alert(result.erro || 'Erro ao criar SOP');
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro de conexão');
    }
}

// Exportar todos os SOPs
function exportarTodosSops() {
    window.location.href = '<?= APP_URL ?>/sop/exportar-todos-zip';
}
</script>

<script>
// Função para selecionar empresa e navegar para próxima hierarquia
function selecionarEmpresa(empresaId) {
    // Limpar seleções anteriores
    document.querySelectorAll('[id^="empresa-"]').forEach(el => {
        el.classList.remove('border-primary', 'bg-primary/10');
        el.classList.add('border-gray-200');
    });
    
    document.querySelectorAll('[id^="selected-"]').forEach(el => {
        el.style.opacity = '0';
    });
    
    // Marcar empresa selecionada
    const empresaEl = document.getElementById('empresa-' + empresaId);
    const selectedEl = document.getElementById('selected-' + empresaId);
    
    if (empresaEl && selectedEl) {
        empresaEl.classList.remove('border-gray-200');
        empresaEl.classList.add('border-primary', 'bg-primary/10');
        selectedEl.style.opacity = '1';
    }
    
    // Enviar seleção para o servidor e recarregar página com empresa selecionada
    fetch('<?= APP_URL ?>/admin/selecionar-empresa', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            empresa_id: empresaId,
            csrf_token: '<?= Csrf::token() ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            // Recarregar página para mostrar próxima hierarquia (SOPs)
            setTimeout(() => {
                window.location.reload();
            }, 500);
        } else {
            alert('Erro ao selecionar empresa: ' + (data.mensagem || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao comunicar com o servidor');
    });
}

// Outras funções para navegação hierárquica
function verSOP(sopId) {
    window.location.href = '<?= APP_URL ?>/sop/ver/' + sopId;
}

function exportarSOP(sopId) {
    window.location.href = '<?= APP_URL ?>/sop/exportar-pdf/' + sopId;
}

function exportarTodos() {
    window.location.href = '<?= APP_URL ?>/sop/exportar-todos-zip';
}

function buscarSOPs() {
    const termo = document.getElementById('busca-sops').value.toLowerCase();
    const cards = document.querySelectorAll('.sop-card');
    
    cards.forEach(card => {
        const titulo = card.dataset.titulo?.toLowerCase() || '';
        const tags = card.dataset.tags?.toLowerCase() || '';
        
        if (titulo.includes(termo) || tags.includes(termo)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>