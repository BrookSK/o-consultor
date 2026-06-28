<?php
/**
 * Painel Administrativo de Notícias (F-09)
 * Visualização para ADMIN_HOLDING e CONSULTOR_INTERNO
 */

require_once VIEW_PATH . '/layouts/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-newspaper text-primary me-2"></i>Painel Administrativo - Notícias</h2>
            <p class="text-muted">Gerenciamento global do sistema de notícias por IA</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary" onclick="executarBuscaGlobal()">
                <i class="fas fa-sync me-2"></i>Buscar Todas as Empresas
            </button>
        </div>
    </div>

    <!-- Estatísticas por Empresa -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-bar me-2"></i>Estatísticas por Empresa</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($stats)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Nenhuma notícia encontrada no sistema.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Empresa</th>
                                        <th>Total de Notícias</th>
                                        <th>Não Visualizadas</th>
                                        <th>Favoritas</th>
                                        <th>Alta Relevância</th>
                                        <th>Última Notícia</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats as $stat): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($stat['empresa_nome']) ?></strong></td>
                                            <td>
                                                <span class="badge bg-primary"><?= $stat['total_noticias'] ?></span>
                                            </td>
                                            <td>
                                                <?php if ($stat['nao_visualizadas'] > 0): ?>
                                                    <span class="badge bg-warning"><?= $stat['nao_visualizadas'] ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">0</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-success"><?= $stat['favoritas'] ?></span>
                                            </td>
                                            <td>
                                                <?php if ($stat['alta_relevancia'] > 0): ?>
                                                    <span class="badge bg-danger"><?= $stat['alta_relevancia'] ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">0</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($stat['ultima_noticia']): ?>
                                                    <small class="text-muted"><?= date('d/m/Y H:i', strtotime($stat['ultima_noticia'])) ?></small>
                                                <?php else: ?>
                                                    <small class="text-muted">-</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="executarBuscaEmpresa(<?= $stat['empresa_id'] ?>)">
                                                    <i class="fas fa-sync me-1"></i>Buscar
                                                </button>
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

    <!-- Notícias Recentes -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-clock me-2"></i>Notícias Recentes</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($noticias_recentes)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Nenhuma notícia recente encontrada.
                        </div>
                    <?php else: ?>
                        <?php foreach ($noticias_recentes as $noticia): ?>
                            <div class="d-flex align-items-start mb-3 pb-3 border-bottom">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <strong class="text-primary me-2"><?= htmlspecialchars($noticia['empresa_nome']) ?></strong>
                                        <span class="badge bg-<?= match($noticia['relevancia']) {
                                            'alta' => 'danger',
                                            'media' => 'warning',
                                            default => 'secondary'
                                        } ?>"><?= ucfirst($noticia['relevancia'] ?? 'baixa') ?></span>
                                        <?php if ($noticia['favorita']): ?>
                                            <i class="fas fa-heart text-danger ms-2"></i>
                                        <?php endif; ?>
                                        <?php if ($noticia['arquivada']): ?>
                                            <i class="fas fa-archive text-muted ms-2" title="Arquivada"></i>
                                        <?php endif; ?>
                                    </div>
                                    <h6 class="mb-2"><?= htmlspecialchars($noticia['titulo']) ?></h6>
                                    <div class="d-flex align-items-center text-muted small">
                                        <span class="me-3"><i class="fas fa-globe me-1"></i><?= htmlspecialchars($noticia['fonte']) ?></span>
                                        <span class="me-3"><i class="fas fa-calendar me-1"></i><?= date('d/m/Y', strtotime($noticia['data_publicacao'])) ?></span>
                                        <span><i class="fas fa-tag me-1"></i><?= htmlspecialchars($noticia['categoria']) ?></span>
                                    </div>
                                </div>
                                <div class="ms-3">
                                    <div class="btn-group">
                                        <a href="<?= APP_URL ?>/noticias/detalhe/<?= $noticia['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                onclick="arquivarNoticia(<?= $noticia['id'] ?>, <?= $noticia['arquivada'] ? 'false' : 'true' ?>)">
                                            <i class="fas fa-<?= $noticia['arquivada'] ? 'undo' : 'archive' ?>"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Logs de Busca -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-history me-2"></i>Últimas Buscas</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($logs_busca)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Nenhum log de busca encontrado.
                        </div>
                    <?php else: ?>
                        <?php foreach ($logs_busca as $log): ?>
                            <div class="d-flex align-items-start mb-3 pb-3 border-bottom">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-1">
                                        <strong class="small"><?= htmlspecialchars($log['empresa_nome']) ?></strong>
                                        <span class="badge bg-<?= $log['sucesso'] ? 'success' : 'danger' ?> ms-2">
                                            <?= $log['sucesso'] ? 'Sucesso' : 'Erro' ?>
                                        </span>
                                    </div>
                                    <div class="small text-muted mb-2">
                                        <?= ucfirst($log['tipo_busca']) ?> • <?= date('d/m H:i', strtotime($log['criado_em'])) ?>
                                    </div>
                                    <?php if ($log['sucesso']): ?>
                                        <div class="small">
                                            <i class="fas fa-plus text-success me-1"></i><?= $log['noticias_novas'] ?? 0 ?> novas
                                            <span class="text-muted ms-2">de <?= $log['noticias_encontradas'] ?? 0 ?> encontradas</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="small text-danger">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            <?= htmlspecialchars($log['erro_detalhes'] ?? 'Erro desconhecido') ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function executarBuscaGlobal() {
    const btn = event.target.closest('button');
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Executando...';
    
    try {
        const response = await fetch('<?= APP_URL ?>/noticias/busca-global', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= Csrf::getToken() ?>'
            }
        });
        
        const result = await response.json();
        
        if (result.sucesso) {
            mostrarToast('Busca executada com sucesso!', 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            mostrarToast(result.erro || 'Erro na busca', 'error');
        }
    } catch (error) {
        mostrarToast('Erro de conexão', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

async function executarBuscaEmpresa(empresaId) {
    const btn = event.target.closest('button');
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Buscando...';
    
    try {
        const response = await fetch(`<?= APP_URL ?>/noticias/buscar?empresa_id=${empresaId}`, {
            method: 'GET'
        });
        
        const result = await response.json();
        
        if (result.sucesso) {
            mostrarToast(`Busca concluída! ${result.noticias_novas || 0} novas notícias`, 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            mostrarToast(result.erro || 'Erro na busca', 'error');
        }
    } catch (error) {
        mostrarToast('Erro de conexão', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

async function arquivarNoticia(noticiaId, arquivar) {
    try {
        const response = await fetch('<?= APP_URL ?>/noticias/arquivar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= Csrf::getToken() ?>'
            },
            body: JSON.stringify({
                noticia_id: noticiaId,
                arquivar: arquivar
            })
        });
        
        const result = await response.json();
        
        if (result.sucesso) {
            mostrarToast(result.mensagem, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            mostrarToast(result.erro || 'Erro na operação', 'error');
        }
    } catch (error) {
        mostrarToast('Erro de conexão', 'error');
    }
}
</script>

<?php require_once VIEW_PATH . '/layouts/footer.php'; ?>