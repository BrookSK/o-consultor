<?php $tituloPagina = 'Solicitações de Parceiros'; ?>
<?php ob_start(); ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li><a href="<?= APP_URL ?>/parceiros/admin" class="hover:text-primary">Parceiros</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Solicitações</li>
    </ol>
</nav>

<!-- Header -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-gray-800">🤝 Solicitações de Parceiros</h1>
        <p class="text-sm text-gray-500">Gerenciar solicitações de acionamento de parceiros por clientes</p>
    </div>
</div>

<!-- Filtros -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                <option value="">Todos os status</option>
                <option value="solicitado" <?= ($dados['filtros']['status'] === 'solicitado') ? 'selected' : '' ?>>🕐 Solicitado</option>
                <option value="em_contato" <?= ($dados['filtros']['status'] === 'em_contato') ? 'selected' : '' ?>>📞 Em contato</option>
                <option value="em_execucao" <?= ($dados['filtros']['status'] === 'em_execucao') ? 'selected' : '' ?>>⚡ Em execução</option>
                <option value="concluido" <?= ($dados['filtros']['status'] === 'concluido') ? 'selected' : '' ?>>✅ Concluído</option>
                <option value="cancelado" <?= ($dados['filtros']['status'] === 'cancelado') ? 'selected' : '' ?>>❌ Cancelado</option>
            </select>
        </div>
        
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Urgência</label>
            <select name="urgencia" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                <option value="">Todas urgências</option>
                <option value="critica" <?= ($dados['filtros']['urgencia'] === 'critica') ? 'selected' : '' ?>>🔴 Crítica</option>
                <option value="alta" <?= ($dados['filtros']['urgencia'] === 'alta') ? 'selected' : '' ?>>🟠 Alta</option>
                <option value="media" <?= ($dados['filtros']['urgencia'] === 'media') ? 'selected' : '' ?>>🟡 Média</option>
                <option value="baixa" <?= ($dados['filtros']['urgencia'] === 'baixa') ? 'selected' : '' ?>>🟢 Baixa</option>
            </select>
        </div>
        
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Empresa</label>
            <select name="empresa_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                <option value="">Todas empresas</option>
                <?php foreach ($dados['empresas'] as $empresa): ?>
                <option value="<?= $empresa['id'] ?>" <?= ($dados['filtros']['empresa_id'] === (int)$empresa['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($empresa['nome']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="flex items-end">
            <button type="submit" class="w-full bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-700 transition">
                🔍 Filtrar
            </button>
        </div>
    </form>
</div>

<!-- Lista de Solicitações -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <?php if (!empty($dados['solicitacoes'])): ?>
    
    <!-- Header da tabela -->
    <div class="px-6 py-3 border-b border-gray-100 bg-gray-50">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-4 text-xs font-medium text-gray-500 uppercase tracking-wider">
            <div>Cliente / Tarefa</div>
            <div>Parceiro</div>
            <div>Urgência</div>
            <div>Status</div>
            <div>Data</div>
            <div class="text-center">Ações</div>
        </div>
    </div>
    
    <!-- Corpo da tabela -->
    <div class="divide-y divide-gray-100">
        <?php foreach ($dados['solicitacoes'] as $solicitacao): 
            $urgenciaConfig = [
                'critica' => ['color' => 'bg-red-100 text-red-700', 'icon' => '🔴'],
                'alta' => ['color' => 'bg-orange-100 text-orange-700', 'icon' => '🟠'],
                'media' => ['color' => 'bg-yellow-100 text-yellow-700', 'icon' => '🟡'],
                'baixa' => ['color' => 'bg-green-100 text-green-700', 'icon' => '🟢']
            ];
            
            $statusConfig = [
                'solicitado' => ['color' => 'bg-yellow-100 text-yellow-700', 'label' => '🕐 Solicitado'],
                'em_contato' => ['color' => 'bg-blue-100 text-blue-700', 'label' => '📞 Em contato'],
                'em_execucao' => ['color' => 'bg-purple-100 text-purple-700', 'label' => '⚡ Em execução'],
                'concluido' => ['color' => 'bg-green-100 text-green-700', 'label' => '✅ Concluído'],
                'cancelado' => ['color' => 'bg-gray-100 text-gray-600', 'label' => '❌ Cancelado']
            ];
            
            $urgencia = $urgenciaConfig[$solicitacao['urgencia']] ?? $urgenciaConfig['media'];
            $status = $statusConfig[$solicitacao['status']] ?? $statusConfig['solicitado'];
        ?>
        <div class="p-6 hover:bg-gray-50 transition">
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-start">
                <!-- Cliente / Tarefa -->
                <div>
                    <p class="font-medium text-gray-800 text-sm"><?= htmlspecialchars($solicitacao['empresa_nome']) ?></p>
                    <p class="text-xs text-gray-600 mt-1"><?= htmlspecialchars($solicitacao['tarefa_titulo']) ?></p>
                    <p class="text-xs text-gray-400 mt-1">Por: <?= htmlspecialchars($solicitacao['usuario_nome']) ?></p>
                </div>
                
                <!-- Parceiro -->
                <div>
                    <p class="font-medium text-gray-800 text-sm"><?= htmlspecialchars($solicitacao['parceiro_nome']) ?></p>
                    <p class="text-xs text-gray-500"><?= htmlspecialchars($solicitacao['parceiro_categoria']) ?></p>
                    <?php if ($solicitacao['avaliacao_media']): ?>
                    <p class="text-xs text-yellow-600 mt-1">★ <?= number_format($solicitacao['avaliacao_media'], 1) ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Urgência -->
                <div>
                    <span class="px-2 py-1 rounded-full text-xs font-medium <?= $urgencia['color'] ?>">
                        <?= $urgencia['icon'] ?> <?= ucfirst($solicitacao['urgencia']) ?>
                    </span>
                </div>
                
                <!-- Status -->
                <div>
                    <span class="px-2 py-1 rounded-full text-xs font-medium <?= $status['color'] ?>">
                        <?= $status['label'] ?>
                    </span>
                </div>
                
                <!-- Data -->
                <div>
                    <p class="text-xs text-gray-600">Solicitado em:</p>
                    <p class="text-sm font-medium"><?= date('d/m/Y H:i', strtotime($solicitacao['criado_em'])) ?></p>
                    
                    <?php if ($solicitacao['data_conclusao']): ?>
                    <p class="text-xs text-gray-500 mt-1">Concluído: <?= date('d/m/Y', strtotime($solicitacao['data_conclusao'])) ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Ações -->
                <div class="text-center">
                    <button onclick="abrirModalStatus(<?= $solicitacao['id'] ?>, '<?= $solicitacao['status'] ?>', '<?= htmlspecialchars($solicitacao['observacoes_admin']) ?>')" 
                            class="bg-primary text-white px-3 py-1.5 rounded text-xs font-medium hover:bg-primary-700 transition">
                        Atualizar Status
                    </button>
                </div>
            </div>
            
            <!-- Descrição da necessidade -->
            <div class="mt-4 pt-4 border-t border-gray-100">
                <p class="text-xs font-medium text-gray-500 mb-1">Necessidade descrita:</p>
                <p class="text-sm text-gray-700"><?= nl2br(htmlspecialchars($solicitacao['descricao_necessidade'])) ?></p>
                
                <?php if (!empty($solicitacao['observacoes_admin'])): ?>
                <p class="text-xs font-medium text-gray-500 mt-3 mb-1">Observações da administração:</p>
                <p class="text-sm text-blue-700"><?= nl2br(htmlspecialchars($solicitacao['observacoes_admin'])) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php else: ?>
    
    <!-- Empty state -->
    <div class="text-center py-12">
        <div class="text-6xl mb-4">🤝</div>
        <h3 class="text-lg font-medium text-gray-800 mb-2">Nenhuma solicitação encontrada</h3>
        <p class="text-sm text-gray-500">
            <?php if (array_filter($dados['filtros'])): ?>
            Nenhuma solicitação corresponde aos filtros aplicados.
            <?php else: ?>
            Ainda não há solicitações de parceiros registradas.
            <?php endif; ?>
        </p>
        
        <?php if (array_filter($dados['filtros'])): ?>
        <a href="<?= APP_URL ?>/parceiros/solicitacoes" class="inline-block mt-4 text-primary hover:underline text-sm">
            Limpar filtros
        </a>
        <?php endif; ?>
    </div>
    
    <?php endif; ?>
</div>

<!-- Modal Atualizar Status -->
<div id="modal-status" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">Atualizar Status da Solicitação</h3>
            <button onclick="fecharModalStatus()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="form-status" class="p-6 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
            <input type="hidden" id="status-solicitacao-id" name="solicitacao_id" value="">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Novo status *</label>
                <select id="status-novo-status" name="novo_status" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                    <option value="solicitado">🕐 Solicitado</option>
                    <option value="em_contato">📞 Em contato</option>
                    <option value="em_execucao">⚡ Em execução</option>
                    <option value="concluido">✅ Concluído</option>
                    <option value="cancelado">❌ Cancelado</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Observações da administração</label>
                <textarea id="status-observacoes" name="observacoes" rows="4" 
                          placeholder="Registre detalhes sobre o contato, progresso ou eventuais problemas..."
                          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none"></textarea>
                <p class="text-xs text-gray-500 mt-1">Essas observações serão registradas no sistema para acompanhamento interno.</p>
            </div>
            
            <div class="bg-blue-50 p-3 rounded-lg">
                <h4 class="text-sm font-medium text-gray-700 mb-1">💡 Orientações por Status</h4>
                <ul class="text-xs text-gray-600 space-y-0.5">
                    <li><strong>Em contato:</strong> Parceiro foi notificado e está entrando em contato com o cliente</li>
                    <li><strong>Em execução:</strong> Serviço foi iniciado pelo parceiro</li>
                    <li><strong>Concluído:</strong> Trabalho finalizado - cliente será notificado para avaliar</li>
                    <li><strong>Cancelado:</strong> Solicitação cancelada por algum motivo</li>
                </ul>
            </div>
            
            <div class="flex justify-end gap-3 pt-4">
                <button type="button" onclick="fecharModalStatus()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-700">💾 Atualizar Status</button>
            </div>
        </form>
    </div>
</div>

<script>
// Variáveis globais
let statusModal = null;

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    // Focar no primeiro campo de filtro se não há resultados
    <?php if (empty($dados['solicitacoes']) && !array_filter($dados['filtros'])): ?>
    document.querySelector('select[name="status"]')?.focus();
    <?php endif; ?>
});

// Modal de status
function abrirModalStatus(solicitacaoId, statusAtual, observacoesAtuais) {
    document.getElementById('status-solicitacao-id').value = solicitacaoId;
    document.getElementById('status-novo-status').value = statusAtual;
    document.getElementById('status-observacoes').value = observacoesAtuais || '';
    document.getElementById('modal-status').classList.remove('hidden');
    
    // Focar no select de status
    setTimeout(() => {
        document.getElementById('status-novo-status').focus();
    }, 100);
}

function fecharModalStatus() {
    document.getElementById('modal-status').classList.add('hidden');
    document.getElementById('form-status').reset();
}

// Form de atualização de status
document.getElementById('form-status').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const button = this.querySelector('button[type="submit"]');
    const originalText = button.textContent;
    
    button.disabled = true;
    button.textContent = '⏳ Atualizando...';
    
    try {
        const res = await fetch('<?= APP_URL ?>/parceiros/atualizar-status-solicitacao', { 
            method: 'POST', 
            body: formData 
        });
        const data = await res.json();
        
        if (data.sucesso) {
            fecharModalStatus();
            
            // Toast ou alert
            if (typeof Toast !== 'undefined') {
                Toast.sucesso(data.mensagem);
            } else {
                alert(data.mensagem);
            }
            
            // Recarregar página para mostrar mudanças
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            alert(data.erro || 'Erro ao atualizar status.');
        }
    } catch (e) {
        console.error('Erro:', e);
        alert('Erro de conexão. Tente novamente.');
    } finally {
        button.disabled = false;
        button.textContent = originalText;
    }
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // ESC para fechar modal
    if (e.key === 'Escape') {
        fecharModalStatus();
    }
});

// Auto-refresh das solicitações pendentes (opcional)
<?php if (count(array_filter($dados['solicitacoes'], fn($s) => in_array($s['status'], ['solicitado', 'em_contato', 'em_execucao']))) > 0): ?>
// Recarregar a cada 5 minutos se há solicitações ativas
setTimeout(() => {
    if (document.visibilityState === 'visible') {
        window.location.reload();
    }
}, 5 * 60 * 1000);
<?php endif; ?>
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>