<?php 
$empresa = $dados['empresa'];
$tituloPagina = 'Perfil de ' . $empresa['nome']; 
?>
<?php ob_start(); ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/admin" class="hover:text-primary">Admin</a></li>
        <li>/</li>
        <li><a href="<?= APP_URL ?>/admin/clientes" class="hover:text-primary">Clientes</a></li>
        <li>/</li>
        <li class="font-medium text-primary"><?= htmlspecialchars($empresa['nome']) ?></li>
    </ol>
</nav>

<!-- Header da empresa -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
    <div class="flex items-start justify-between">
        <div class="flex-1">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center">
                    <span class="text-primary text-xl font-bold">
                        <?= strtoupper(substr($empresa['nome'], 0, 2)) ?>
                    </span>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($empresa['nome']) ?></h1>
                    <div class="flex items-center gap-4 mt-1">
                        <?php 
                        $statusConfig = match($empresa['status']) {
                            'ativo' => ['bg-green-100 text-green-700', '✅ Ativo'],
                            'pausado' => ['bg-yellow-100 text-yellow-700', '⏸️ Pausado'],
                            'suspenso' => ['bg-orange-100 text-orange-700', '⏹️ Suspenso'],
                            'cancelado' => ['bg-red-100 text-red-700', '❌ Cancelado'],
                            default => ['bg-gray-100 text-gray-600', '❓ Indefinido']
                        };
                        ?>
                        <span class="px-3 py-1 rounded-full text-sm font-medium <?= $statusConfig[0] ?>">
                            <?= $statusConfig[1] ?>
                        </span>
                        <span class="text-sm text-gray-500">
                            Cliente desde <?= date('d/m/Y', strtotime($empresa['criado_em'])) ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <p class="text-sm text-gray-500">Responsável</p>
                    <p class="font-medium text-gray-800">
                        <?= htmlspecialchars($empresa['responsavel_nome'] ?? 'Não definido') ?>
                    </p>
                    <?php if ($empresa['responsavel_email']): ?>
                    <p class="text-sm text-gray-500"><?= htmlspecialchars($empresa['responsavel_email']) ?></p>
                    <?php endif; ?>
                </div>
                
                <div>
                    <p class="text-sm text-gray-500">Consultor</p>
                    <p class="font-medium text-gray-800">
                        <?= htmlspecialchars($empresa['consultor_nome'] ?? 'Não atribuído') ?>
                    </p>
                    <?php if ($empresa['consultor_email']): ?>
                    <p class="text-sm text-gray-500"><?= htmlspecialchars($empresa['consultor_email']) ?></p>
                    <?php endif; ?>
                </div>
                
                <div>
                    <p class="text-sm text-gray-500">MRR</p>
                    <p class="font-medium text-gray-800">
                        <?= $empresa['mrr'] ? 'R$ ' . number_format($empresa['mrr'], 2, ',', '.') : 'Não informado' ?>
                    </p>
                    <?php if ($empresa['segmento']): ?>
                    <p class="text-sm text-gray-500"><?= htmlspecialchars($empresa['segmento']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="flex gap-2 ml-6">
            <?php if (!empty($dados['usuarios_cliente'])): ?>
            <button onclick="abrirModalAcesso()" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary/90 transition flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                Acessar como Cliente
            </button>
            <?php endif; ?>
            <button onclick="abrirModalConsultor()" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">
                Trocar Consultor
            </button>
            <button onclick="abrirModalStatus()" class="px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700 transition">
                Alterar Status
            </button>
        </div>
    </div>
</div>

<!-- Estatísticas rápidas -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 text-center">
        <div class="text-3xl font-bold text-blue-600"><?= count($dados['diagnosticos']) ?></div>
        <div class="text-sm text-gray-500 mt-1">Diagnósticos</div>
        <div class="text-xs text-gray-400 mt-1">
            <?= count(array_filter($dados['diagnosticos'], fn($d) => $d['status'] === 'concluido')) ?> concluídos
        </div>
    </div>
    
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 text-center">
        <div class="text-3xl font-bold text-green-600"><?= count($dados['planos']) ?></div>
        <div class="text-sm text-gray-500 mt-1">Planos de Ação</div>
        <div class="text-xs text-gray-400 mt-1">
            <?= count(array_filter($dados['planos'], fn($p) => $p['status'] === 'ativo')) ?> ativos
        </div>
    </div>
    
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 text-center">
        <div class="text-3xl font-bold text-purple-600"><?= count($dados['sops']) ?></div>
        <div class="text-sm text-gray-500 mt-1">SOPs Aprovados</div>
        <?php if (!empty($dados['sops'])): ?>
        <div class="text-xs text-gray-400 mt-1">
            <?= count(array_unique(array_column($dados['sops'], 'departamento'))) ?> departamentos
        </div>
        <?php endif; ?>
    </div>
    
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 text-center">
        <div class="text-3xl font-bold text-orange-600"><?= count($dados['kpis']) ?></div>
        <div class="text-sm text-gray-500 mt-1">KPIs Ativos</div>
        <?php if ($empresa['score_maturidade']): ?>
        <div class="text-xs text-gray-400 mt-1">
            Maturidade: <?= $empresa['score_maturidade'] ?>%
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Abas de conteúdo -->
<div x-data="{ abaAtiva: 'dados' }" class="bg-white rounded-lg shadow-sm border border-gray-200">
    <!-- Nav das abas -->
    <div class="border-b border-gray-200">
        <nav class="flex">
            <button @click="abaAtiva = 'dados'" :class="abaAtiva === 'dados' ? 'border-b-2 border-primary text-primary' : 'text-gray-500'" class="px-6 py-4 text-sm font-medium">
                📊 Dados da Empresa
            </button>
            <button @click="abaAtiva = 'diagnosticos'" :class="abaAtiva === 'diagnosticos' ? 'border-b-2 border-primary text-primary' : 'text-gray-500'" class="px-6 py-4 text-sm font-medium">
                🔍 Diagnósticos
            </button>
            <button @click="abaAtiva = 'planos'" :class="abaAtiva === 'planos' ? 'border-b-2 border-primary text-primary' : 'text-gray-500'" class="px-6 py-4 text-sm font-medium">
                📋 Planos de Ação
            </button>
            <button @click="abaAtiva = 'sops'" :class="abaAtiva === 'sops' ? 'border-b-2 border-primary text-primary' : 'text-gray-500'" class="px-6 py-4 text-sm font-medium">
                📖 SOPs
            </button>
            <button @click="abaAtiva = 'historico'" :class="abaAtiva === 'historico' ? 'border-b-2 border-primary text-primary' : 'text-gray-500'" class="px-6 py-4 text-sm font-medium">
                📜 Histórico
            </button>
        </nav>
    </div>
    
    <!-- Conteúdo das abas -->
    <div class="p-6">
        <!-- Aba Dados da Empresa -->
        <div x-show="abaAtiva === 'dados'" x-transition>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <h3 class="font-semibold text-gray-800 mb-4">Informações Básicas</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm text-gray-500">CNPJ</dt>
                            <dd class="font-medium text-gray-800"><?= $empresa['cnpj'] ?: 'Não informado' ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Telefone</dt>
                            <dd class="font-medium text-gray-800"><?= $empresa['telefone'] ?: 'Não informado' ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Website</dt>
                            <dd class="font-medium text-gray-800">
                                <?php if ($empresa['website']): ?>
                                <a href="<?= htmlspecialchars($empresa['website']) ?>" target="_blank" class="text-primary hover:underline">
                                    <?= htmlspecialchars($empresa['website']) ?>
                                </a>
                                <?php else: ?>
                                Não informado
                                <?php endif; ?>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Colaboradores</dt>
                            <dd class="font-medium text-gray-800"><?= $empresa['colaboradores_internos'] ?: 'Não informado' ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Faturamento Mensal</dt>
                            <dd class="font-medium text-gray-800"><?= $empresa['faturamento_mensal'] ?: 'Não informado' ?></dd>
                        </div>
                    </dl>
                </div>
                
                <div>
                    <h3 class="font-semibold text-gray-800 mb-4">Localização</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm text-gray-500">Endereço</dt>
                            <dd class="font-medium text-gray-800"><?= $empresa['endereco'] ?: 'Não informado' ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Cidade</dt>
                            <dd class="font-medium text-gray-800"><?= $empresa['cidade'] ?: 'Não informado' ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Estado</dt>
                            <dd class="font-medium text-gray-800"><?= $empresa['estado'] ?: 'Não informado' ?></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">CEP</dt>
                            <dd class="font-medium text-gray-800"><?= $empresa['cep'] ?: 'Não informado' ?></dd>
                        </div>
                    </dl>
                    
                    <?php if ($empresa['observacoes_admin']): ?>
                    <div class="mt-6">
                        <h3 class="font-semibold text-gray-800 mb-2">Observações Administrativas</h3>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                            <p class="text-sm text-yellow-800"><?= nl2br(htmlspecialchars($empresa['observacoes_admin'])) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Aba Diagnósticos -->
        <div x-show="abaAtiva === 'diagnosticos'" x-transition style="display: none;">
            <?php if (!empty($dados['diagnosticos'])): ?>
            <div class="space-y-4">
                <?php foreach ($dados['diagnosticos'] as $diagnostico): ?>
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-800">
                                Diagnóstico #<?= $diagnostico['id'] ?>
                                <?php if ($diagnostico['status'] === 'concluido'): ?>
                                <span class="ml-2 px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs">✅ Concluído</span>
                                <?php else: ?>
                                <span class="ml-2 px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded-full text-xs">🔄 Em andamento</span>
                                <?php endif; ?>
                            </p>
                            <p class="text-sm text-gray-500">
                                Por: <?= htmlspecialchars($diagnostico['usuario_nome']) ?> • 
                                <?= date('d/m/Y H:i', strtotime($diagnostico['criado_em'])) ?>
                            </p>
                            <?php if ($diagnostico['pontuacao'] > 0): ?>
                            <p class="text-sm text-gray-600 mt-1">Pontuação: <strong><?= $diagnostico['pontuacao'] ?>%</strong></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-8 text-gray-500">
                <div class="text-4xl mb-2">🔍</div>
                <p>Nenhum diagnóstico realizado ainda.</p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Aba Planos de Ação -->
        <div x-show="abaAtiva === 'planos'" x-transition style="display: none;">
            <?php if (!empty($dados['planos'])): ?>
            <div class="space-y-4">
                <?php foreach ($dados['planos'] as $plano): ?>
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-800"><?= htmlspecialchars($plano['titulo']) ?></p>
                            <p class="text-sm text-gray-500">
                                Por: <?= htmlspecialchars($plano['usuario_nome']) ?> • 
                                <?= date('d/m/Y', strtotime($plano['criado_em'])) ?>
                            </p>
                            <?php if ($plano['total_tarefas'] > 0): ?>
                            <p class="text-sm text-gray-600 mt-1">
                                <?= $plano['tarefas_concluidas'] ?>/<?= $plano['total_tarefas'] ?> tarefas concluídas
                                (<?= round(($plano['tarefas_concluidas'] / $plano['total_tarefas']) * 100) ?>%)
                            </p>
                            <?php endif; ?>
                        </div>
                        <a href="<?= APP_URL ?>/plano-de-acao/<?= $plano['id'] ?>" target="_blank" class="text-primary hover:underline text-sm">
                            Ver Plano
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-8 text-gray-500">
                <div class="text-4xl mb-2">📋</div>
                <p>Nenhum plano de ação criado ainda.</p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Aba SOPs -->
        <div x-show="abaAtiva === 'sops'" x-transition style="display: none;">
            <?php if (!empty($dados['sops'])): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($dados['sops'] as $sop): ?>
                <div class="border border-gray-200 rounded-lg p-4">
                    <p class="font-medium text-gray-800"><?= htmlspecialchars($sop['titulo']) ?></p>
                    <p class="text-sm text-gray-500"><?= htmlspecialchars($sop['departamento']) ?></p>
                    <p class="text-xs text-gray-400 mt-1">
                        Versão <?= $sop['versao'] ?> • <?= date('d/m/Y', strtotime($sop['atualizado_em'])) ?>
                    </p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-8 text-gray-500">
                <div class="text-4xl mb-2">📖</div>
                <p>Nenhum SOP aprovado ainda.</p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Aba Histórico -->
        <div x-show="abaAtiva === 'historico'" x-transition style="display: none;">
            <?php if (!empty($dados['historico'])): ?>
            <div class="space-y-4">
                <?php foreach ($dados['historico'] as $item): ?>
                <div class="border-l-4 border-l-primary/30 pl-4 py-2">
                    <div class="flex items-center justify-between">
                        <p class="font-medium text-gray-800">
                            <?= match($item['tipo_acao']) {
                                'criacao' => '✨ Cliente criado',
                                'troca_consultor' => '👥 Consultor alterado',
                                'mudanca_status' => '🔄 Status alterado',
                                'alteracao_dados' => '✏️ Dados alterados',
                                'cancelamento' => '❌ Cliente cancelado',
                                default => ucfirst(str_replace('_', ' ', $item['tipo_acao']))
                            } ?>
                        </p>
                        <span class="text-sm text-gray-400"><?= date('d/m/Y H:i', strtotime($item['criado_em'])) ?></span>
                    </div>
                    <p class="text-sm text-gray-600">Por: <?= htmlspecialchars($item['admin_nome']) ?></p>
                    <?php if ($item['observacoes']): ?>
                    <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($item['observacoes']) ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-8 text-gray-500">
                <div class="text-4xl mb-2">📜</div>
                <p>Nenhum histórico disponível.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Trocar Consultor -->
<div id="modal-consultor" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Trocar Consultor Responsável</h3>
        </div>
        <form id="form-consultor" class="p-6 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
            <input type="hidden" name="empresa_id" value="<?= $empresa['id'] ?>">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Novo consultor</label>
                <select name="consultor_id" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                    <option value="">Selecione o consultor</option>
                    <?php foreach ($dados['consultores'] as $consultor): ?>
                    <option value="<?= $consultor['id'] ?>" <?= ($consultor['id'] == $empresa['consultor_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($consultor['nome']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Motivo da troca</label>
                <textarea name="observacoes" rows="3" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none" placeholder="Descreva o motivo da troca de consultor..."></textarea>
            </div>
            
            <div class="flex justify-end gap-3 pt-4">
                <button type="button" onclick="fecharModalConsultor()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">Alterar Consultor</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Alterar Status -->
<div id="modal-status" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Alterar Status do Cliente</h3>
        </div>
        <form id="form-status" class="p-6 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
            <input type="hidden" name="empresa_id" value="<?= $empresa['id'] ?>">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Novo status</label>
                <select name="status" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                    <option value="ativo" <?= ($empresa['status'] === 'ativo') ? 'selected' : '' ?>>✅ Ativo</option>
                    <option value="pausado" <?= ($empresa['status'] === 'pausado') ? 'selected' : '' ?>>⏸️ Pausado</option>
                    <option value="suspenso" <?= ($empresa['status'] === 'suspenso') ? 'selected' : '' ?>>⏹️ Suspenso</option>
                    <option value="cancelado" <?= ($empresa['status'] === 'cancelado') ? 'selected' : '' ?>>❌ Cancelado</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Motivo/Observações</label>
                <textarea name="motivo" rows="3" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none" placeholder="Descreva o motivo da alteração de status..."></textarea>
            </div>
            
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                <p class="text-sm text-yellow-800">
                    <strong>Atenção:</strong> Alterar para "Cancelado" desativará todos os usuários da empresa.
                </p>
            </div>
            
            <div class="flex justify-end gap-3 pt-4">
                <button type="button" onclick="fecharModalStatus()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-medium hover:bg-orange-700">Alterar Status</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Acessar como Cliente -->
<div id="modal-acesso" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Acessar como Cliente</h3>
            <p class="text-sm text-gray-500 mt-1">Você entrará na plataforma com a visão deste usuário. Poderá voltar à sua conta a qualquer momento.</p>
        </div>
        <form id="form-acesso" method="POST" action="<?= APP_URL ?>/admin/clientes/acessar-como" class="p-6 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
            <input type="hidden" name="empresa_id" value="<?= $empresa['id'] ?>">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Usuário do cliente</label>
                <select name="usuario_id" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                    <?php foreach ($dados['usuarios_cliente'] as $uc): ?>
                    <option value="<?= (int) $uc['id'] ?>">
                        <?= htmlspecialchars($uc['nome']) ?> — <?= htmlspecialchars($uc['email']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                <p class="text-sm text-blue-800">
                    <strong>Nota:</strong> todas as ações realizadas durante o acesso serão feitas em nome do cliente. Um aviso ficará visível enquanto você estiver nesse modo.
                </p>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <button type="button" onclick="fecharModalAcesso()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary/90">Acessar</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalAcesso() {
    document.getElementById('modal-acesso').classList.remove('hidden');
}

function fecharModalAcesso() {
    document.getElementById('modal-acesso').classList.add('hidden');
}

function abrirModalConsultor() {
    document.getElementById('modal-consultor').classList.remove('hidden');
}

function fecharModalConsultor() {
    document.getElementById('modal-consultor').classList.add('hidden');
}

function abrirModalStatus() {
    document.getElementById('modal-status').classList.remove('hidden');
}

function fecharModalStatus() {
    document.getElementById('modal-status').classList.add('hidden');
}

// Form consultor
document.getElementById('form-consultor').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const button = this.querySelector('button[type="submit"]');
    const originalText = button.textContent;
    
    button.disabled = true;
    button.textContent = 'Alterando...';
    
    try {
        const res = await fetch('<?= APP_URL ?>/admin/clientes/trocar-consultor', { 
            method: 'POST', 
            body: formData 
        });
        const data = await res.json();
        
        if (data.sucesso) {
            fecharModalConsultor();
            alert(data.mensagem);
            window.location.reload();
        } else {
            alert(data.erro || 'Erro ao alterar consultor.');
        }
    } catch (e) {
        alert('Erro de conexão. Tente novamente.');
    } finally {
        button.disabled = false;
        button.textContent = originalText;
    }
});

// Form status
document.getElementById('form-status').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const button = this.querySelector('button[type="submit"]');
    const originalText = button.textContent;
    
    button.disabled = true;
    button.textContent = 'Alterando...';
    
    try {
        const res = await fetch('<?= APP_URL ?>/admin/clientes/alterar-status', { 
            method: 'POST', 
            body: formData 
        });
        const data = await res.json();
        
        if (data.sucesso) {
            fecharModalStatus();
            alert(data.mensagem);
            window.location.reload();
        } else {
            alert(data.erro || 'Erro ao alterar status.');
        }
    } catch (e) {
        alert('Erro de conexão. Tente novamente.');
    } finally {
        button.disabled = false;
        button.textContent = originalText;
    }
});

// Fechar modals com ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        fecharModalConsultor();
        fecharModalStatus();
        fecharModalAcesso();
    }
});
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>