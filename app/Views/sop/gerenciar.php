<?php
/**
 * Gerenciamento de SOPs Personalizados
 * Permite criar, editar e remover SOPs específicos da empresa
 */

require_once VIEW_PATH . '/layouts/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= APP_URL ?>/dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= APP_URL ?>/manual-operacional">Manual Operacional</a></li>
                    <li class="breadcrumb-item active">Gerenciar SOPs</li>
                </ol>
            </nav>
            <h2><i class="fas fa-cog text-primary me-2"></i>Gerenciar SOPs Personalizados</h2>
            <p class="text-muted"><?= htmlspecialchars($empresa['nome']) ?> - Setor: <?= htmlspecialchars($empresa['segmento']) ?></p>
        </div>
        <div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovoSOP">
                <i class="fas fa-plus me-2"></i>Novo SOP Personalizado
            </button>
            <a href="<?= APP_URL ?>/manual-operacional" class="btn btn-secondary ms-2">
                <i class="fas fa-arrow-left me-2"></i>Voltar
            </a>
        </div>
    </div>

    <!-- Informações sobre o Sistema -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Sistema Inteligente de SOPs:</strong> O sistema já possui SOPs específicos para o setor <strong><?= htmlspecialchars($empresa['segmento']) ?></strong>. 
                Aqui você pode adicionar SOPs personalizados únicos para sua empresa, que aparecerão junto aos SOPs padrão do setor.
            </div>
        </div>
    </div>

    <!-- Lista de SOPs Personalizados -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>SOPs Personalizados Criados</h5>
                    <span class="badge bg-primary"><?= count($sops_customizados) ?> SOP(s)</span>
                </div>
                <div class="card-body">
                    <?php if (empty($sops_customizados)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-file-plus fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Nenhum SOP personalizado criado</h5>
                            <p class="text-muted">Clique em "Novo SOP Personalizado" para criar procedimentos específicos da sua empresa.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Nome</th>
                                        <th>Departamento</th>
                                        <th>Descrição</th>
                                        <th>Status</th>
                                        <th>Criado em</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sops_customizados as $sop): ?>
                                        <tr>
                                            <td>
                                                <code class="bg-light px-2 py-1 rounded"><?= htmlspecialchars($sop['sop_codigo']) ?></code>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="me-2"><?= $icone_helper($sop['icone'] ?? 'documento') ?></span>
                                                    <strong><?= htmlspecialchars($sop['nome']) ?></strong>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?= htmlspecialchars($sop['departamento']) ?></span>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?= htmlspecialchars(substr($sop['descricao'], 0, 60)) ?><?= strlen($sop['descricao']) > 60 ? '...' : '' ?></small>
                                            </td>
                                            <td>
                                                <?php if ($sop['ativo']): ?>
                                                    <span class="badge bg-success">Ativo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inativo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?= date('d/m/Y H:i', strtotime($sop['criado_em'])) ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-primary" 
                                                            onclick="gerarSOP('<?= htmlspecialchars($sop['sop_codigo']) ?>', '<?= htmlspecialchars($sop['nome']) ?>')"
                                                            title="Gerar SOP com IA">
                                                        <i class="fas fa-magic"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-warning" 
                                                            onclick="editarSOP(<?= $sop['id'] ?>)"
                                                            title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger" 
                                                            onclick="removerSOP(<?= $sop['id'] ?>, '<?= htmlspecialchars($sop['nome']) ?>')"
                                                            title="Remover">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Novo SOP -->
<div class="modal fade" id="modalNovoSOP" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Novo SOP Personalizado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formNovoSOP">
                    <input type="hidden" name="empresa_id" value="<?= $empresa['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= Csrf::getToken() ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sop_codigo" class="form-label">Código do SOP *</label>
                                <input type="text" class="form-control" id="sop_codigo" name="sop_codigo" 
                                       placeholder="SOP-CUSTOM-001" maxlength="50" required
                                       pattern="[A-Z0-9-]+" title="Use apenas letras maiúsculas, números e hífen">
                                <div class="form-text">Exemplo: SOP-CUSTOM-001 (apenas letras maiúsculas, números e hífen)</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="departamento" class="form-label">Departamento *</label>
                                <input type="text" class="form-control" id="departamento" name="departamento" 
                                       placeholder="Administrativo" maxlength="100" required>
                                <div class="form-text">Ex: Administrativo, Operacional, RH, etc.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome do SOP *</label>
                        <input type="text" class="form-control" id="nome" name="nome" 
                               placeholder="Processo específico da empresa" maxlength="255" required>
                        <div class="form-text">Nome claro e descritivo do procedimento</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3" 
                                  placeholder="Descreva brevemente o que este SOP vai abordar..."></textarea>
                        <div class="form-text">Esta descrição ajuda a IA a gerar um SOP mais específico</div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-lightbulb me-2"></i>
                        <strong>Dica:</strong> Após criar o SOP personalizado, ele aparecerá na lista de SOPs do Manual Operacional 
                        junto com os SOPs padrão do setor. Você poderá gerar o conteúdo usando IA ou editá-lo manualmente.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="salvarNovoSOP()">
                    <i class="fas fa-save me-2"></i>Criar SOP
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Converter para maiúsculas enquanto digita no código
document.getElementById('sop_codigo').addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});

async function salvarNovoSOP() {
    const form = document.getElementById('formNovoSOP');
    const formData = new FormData(form);
    
    // Validações básicas
    const codigo = formData.get('sop_codigo').trim();
    const nome = formData.get('nome').trim();
    const departamento = formData.get('departamento').trim();
    
    if (!codigo || !nome || !departamento) {
        mostrarToast('Preencha todos os campos obrigatórios', 'error');
        return;
    }
    
    if (!/^[A-Z0-9-]+$/.test(codigo)) {
        mostrarToast('Código deve conter apenas letras maiúsculas, números e hífen', 'error');
        return;
    }
    
    try {
        const response = await fetch('<?= APP_URL ?>/sop/adicionar', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.sucesso) {
            mostrarToast(result.mensagem, 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            mostrarToast(result.erro || 'Erro ao criar SOP', 'error');
        }
    } catch (error) {
        console.error('Erro:', error);
        mostrarToast('Erro de conexão', 'error');
    }
}

async function removerSOP(sopId, nome) {
    if (!confirm(`Tem certeza que deseja remover o SOP "${nome}"?\n\nEsta ação não pode ser desfeita.`)) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('sop_id', sopId);
        formData.append('csrf_token', '<?= Csrf::getToken() ?>');
        
        const response = await fetch('<?= APP_URL ?>/sop/remover', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.sucesso) {
            mostrarToast(result.mensagem, 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            mostrarToast(result.erro || 'Erro ao remover SOP', 'error');
        }
    } catch (error) {
        console.error('Erro:', error);
        mostrarToast('Erro de conexão', 'error');
    }
}

function gerarSOP(sopCodigo, nome) {
    // Redirecionar para a geração do SOP
    window.location.href = `<?= APP_URL ?>/manual-operacional?gerar=${encodeURIComponent(sopCodigo)}`;
}

function editarSOP(sopId) {
    // TODO: Implementar edição inline ou modal
    mostrarToast('Funcionalidade de edição em desenvolvimento', 'info');
}

function mostrarToast(mensagem, tipo) {
    // Criar elemento de toast
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${tipo === 'success' ? 'success' : tipo === 'error' ? 'danger' : 'info'} border-0`;
    toast.setAttribute('role', 'alert');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${mensagem}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    // Adicionar ao container de toasts (criar se não existir)
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '1055';
        document.body.appendChild(container);
    }
    
    container.appendChild(toast);
    
    // Inicializar e mostrar o toast
    const bsToast = new bootstrap.Toast(toast, { delay: 4000 });
    bsToast.show();
    
    // Remover elemento após esconder
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}
</script>

<?php require_once VIEW_PATH . '/layouts/footer.php'; ?>