<?php $tituloPagina = $dados['plano']['titulo']; ?>
<?php ob_start(); ?>
<?php $plano = $dados['plano']; ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li><a href="<?= APP_URL ?>/plano-de-acao" class="hover:text-primary">Planos de Ação</a></li>
        <li>/</li>
        <li class="font-medium text-primary"><?= htmlspecialchars($plano['empresa']) ?></li>
    </ol>
</nav>

<!-- Header do Plano -->
<div class="flex flex-col lg:flex-row items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($plano['titulo']) ?></h1>
        <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($plano['objetivo']) ?></p>
        <div class="flex items-center gap-4 mt-2 text-xs text-gray-400">
            <span>📅 <?= date('d/m/Y', strtotime($plano['periodo']['inicio'])) ?> — <?= date('d/m/Y', strtotime($plano['periodo']['fim'])) ?></span>
            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Ativo</span>
        </div>
    </div>
    <button onclick="document.getElementById('modal-reuniao').classList.remove('hidden')"
            class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-700 transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        Registrar Reunião
    </button>
</div>

<!-- Painel de Progresso -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Progresso Geral -->
        <div class="md:col-span-2">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700">Progresso Geral</span>
                <span class="text-sm font-bold text-primary"><?= $plano['progresso'] ?>%</span>
            </div>
            <div class="w-full h-3 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-full bg-primary rounded-full transition-all" style="width: <?= $plano['progresso'] ?>%"></div>
            </div>
            <p class="text-xs text-gray-400 mt-1"><?= $plano['concluidas'] ?> de <?= $plano['total_tarefas'] ?> tarefas concluídas</p>
        </div>
        <!-- Métricas rápidas -->
        <div class="text-center">
            <p class="text-2xl font-bold text-orange-600">
                <?php echo count(array_filter($plano['tarefas'], fn($t) => strtotime($t['prazo']) < time() && $t['status'] !== 'concluido')); ?>
            </p>
            <p class="text-xs text-gray-500">Atrasadas</p>
        </div>
        <div class="text-center">
            <p class="text-2xl font-bold text-blue-600">
                <?php echo count(array_filter($plano['tarefas'], fn($t) => strtotime($t['prazo']) <= strtotime('+7 days') && strtotime($t['prazo']) >= time() && $t['status'] !== 'concluido')); ?>
            </p>
            <p class="text-xs text-gray-500">Vencem em 7 dias</p>
        </div>
    </div>

    <!-- Progresso por área -->
    <div class="mt-4 pt-4 border-t border-gray-100">
        <p class="text-xs font-medium text-gray-500 mb-2">Progresso por Área</p>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-2">
            <?php
            $areas = [];
            foreach ($plano['tarefas'] as $t) {
                $areas[$t['area']]['total'] = ($areas[$t['area']]['total'] ?? 0) + 1;
                if ($t['status'] === 'concluido') $areas[$t['area']]['feitas'] = ($areas[$t['area']]['feitas'] ?? 0) + 1;
            }
            foreach ($areas as $areaNome => $areaData):
                $feitas = $areaData['feitas'] ?? 0;
                $pct = round(($feitas / $areaData['total']) * 100);
            ?>
            <div class="text-center p-2 bg-gray-50 rounded">
                <p class="text-xs font-medium text-gray-700"><?= $areaNome ?></p>
                <p class="text-sm font-bold text-gray-800"><?= $pct ?>%</p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Abas: Kanban | Lista -->
<div x-data="{ abaAtiva: 'kanban' }">
    <div class="border-b border-gray-200 mb-6">
        <nav class="flex gap-0">
            <button @click="abaAtiva = 'kanban'" :class="abaAtiva === 'kanban' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500'" class="px-5 py-3 text-sm transition">📋 Kanban</button>
            <button @click="abaAtiva = 'lista'" :class="abaAtiva === 'lista' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500'" class="px-5 py-3 text-sm transition">📊 Lista</button>
        </nav>
    </div>

    <!-- ABA KANBAN -->
    <div x-show="abaAtiva === 'kanban'" x-transition>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <?php
            $colunas = [
                'pendente' => ['label' => 'Pendente', 'cor' => 'gray', 'bg' => 'bg-gray-50'],
                'em_andamento' => ['label' => 'Em Andamento', 'cor' => 'blue', 'bg' => 'bg-blue-50'],
                'bloqueado' => ['label' => 'Bloqueado', 'cor' => 'red', 'bg' => 'bg-red-50'],
                'concluido' => ['label' => 'Concluído', 'cor' => 'green', 'bg' => 'bg-green-50'],
            ];
            foreach ($colunas as $statusKey => $coluna):
                $tarefasColuna = array_filter($plano['tarefas'], fn($t) => $t['status'] === $statusKey);
            ?>
            <div class="<?= $coluna['bg'] ?> rounded-lg p-3 min-h-[300px]" id="col-<?= $statusKey ?>">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-semibold text-gray-700"><?= $coluna['label'] ?></h4>
                    <span class="w-5 h-5 rounded-full bg-<?= $coluna['cor'] ?>-200 text-<?= $coluna['cor'] ?>-700 text-xs flex items-center justify-center font-bold"><?= count($tarefasColuna) ?></span>
                </div>
                <div class="space-y-2 kanban-column" data-status="<?= $statusKey ?>">
                    <?php foreach ($tarefasColuna as $tarefa):
                        $prioBadge = match($tarefa['prioridade']) {
                            'alta' => 'bg-red-100 text-red-700',
                            'media' => 'bg-yellow-100 text-yellow-700',
                            default => 'bg-blue-100 text-blue-700',
                        };
                        $vencida = strtotime($tarefa['prazo']) < time() && $tarefa['status'] !== 'concluido';
                        $semAtualizacao = (time() - strtotime($tarefa['atualizado_em'])) > (7 * 86400) && $tarefa['status'] !== 'concluido';
                    ?>
                    <div class="bg-white rounded-lg border border-gray-200 p-3 shadow-sm cursor-move hover:shadow-md transition kanban-card" data-id="<?= $tarefa['id'] ?>">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <p class="text-sm font-medium text-gray-800 leading-tight"><?= htmlspecialchars($tarefa['titulo']) ?></p>
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold <?= $prioBadge ?> flex-shrink-0"><?= strtoupper(substr($tarefa['prioridade'], 0, 1)) ?></span>
                        </div>
                        
                        <div class="flex items-center justify-between text-xs text-gray-400">
                            <span>👤 <?= htmlspecialchars($tarefa['responsavel']) ?></span>
                            <span class="<?= $vencida ? 'text-red-600 font-semibold' : '' ?>"><?= date('d/m', strtotime($tarefa['prazo'])) ?></span>
                        </div>
                        
                        <!-- Badges de status -->
                        <div class="mt-2 space-y-1">
                            <?php if ($vencida): ?>
                            <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-500 text-white">VENCIDA</span>
                            <?php endif; ?>
                            
                            <?php if ($semAtualizacao): ?>
                            <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold bg-yellow-500 text-white">SEM ATUALIZAÇÃO</span>
                            <?php endif; ?>
                            
                            <!-- F-12: Badge de parceiro solicitado -->
                            <div class="parceiro-status" data-tarefa-id="<?= $tarefa['id'] ?>">
                                <!-- Carregado via JavaScript -->
                            </div>
                        </div>
                        
                        <!-- F-12: Botões de ação -->
                        <div class="mt-3 pt-2 border-t border-gray-100">
                            <div class="flex items-center justify-between">
                                <button onclick="abrirModalParceiro(<?= $tarefa['id'] ?>, '<?= htmlspecialchars($tarefa['titulo']) ?>', '<?= htmlspecialchars($tarefa['area']) ?>')" 
                                        class="text-xs text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    Acionar Parceiro
                                </button>
                                
                                <button onclick="editarTarefa(<?= $tarefa['id'] ?>)" class="text-xs text-gray-400 hover:text-gray-600">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ABA LISTA -->
    <div x-show="abaAtiva === 'lista'" x-transition style="display:none;">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-3 border-b border-gray-100 flex items-center justify-between">
                <span class="text-sm text-gray-600 font-medium"><?= $plano['concluidas'] ?> de <?= $plano['total_tarefas'] ?> tarefas (<?= $plano['progresso'] ?>%)</span>
                <div class="flex gap-2">
                    <select class="px-2 py-1 border border-gray-200 rounded text-xs outline-none">
                        <option>Todas áreas</option>
                        <?php foreach (array_keys($areas) as $a): ?><option><?= $a ?></option><?php endforeach; ?>
                    </select>
                    <select class="px-2 py-1 border border-gray-200 rounded text-xs outline-none">
                        <option>Todos status</option>
                        <option>Pendente</option><option>Em Andamento</option><option>Bloqueado</option><option>Concluído</option>
                    </select>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-4 py-2.5 font-medium text-gray-500">Título</th>
                            <th class="text-left px-4 py-2.5 font-medium text-gray-500">Área</th>
                            <th class="text-left px-4 py-2.5 font-medium text-gray-500">Responsável</th>
                            <th class="text-left px-4 py-2.5 font-medium text-gray-500">Prazo</th>
                            <th class="text-left px-4 py-2.5 font-medium text-gray-500">Prioridade</th>
                            <th class="text-left px-4 py-2.5 font-medium text-gray-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($plano['tarefas'] as $tarefa):
                            $statusBadge = match($tarefa['status']) {
                                'concluido' => 'bg-green-100 text-green-700',
                                'em_andamento' => 'bg-blue-100 text-blue-700',
                                'bloqueado' => 'bg-red-100 text-red-700',
                                default => 'bg-gray-100 text-gray-600',
                            };
                            $statusLabel = match($tarefa['status']) {
                                'concluido' => 'Concluído',
                                'em_andamento' => 'Em Andamento',
                                'bloqueado' => 'Bloqueado',
                                default => 'Pendente',
                            };
                            $prioBadge = match($tarefa['prioridade']) {
                                'alta' => 'bg-red-100 text-red-700',
                                'media' => 'bg-yellow-100 text-yellow-700',
                                default => 'bg-blue-100 text-blue-700',
                            };
                            $vencida = strtotime($tarefa['prazo']) < time() && $tarefa['status'] !== 'concluido';
                        ?>
                        <tr class="hover:bg-gray-50 <?= $vencida ? 'bg-red-50/50' : '' ?>">
                            <td class="px-4 py-3 font-medium text-gray-800">
                                <?= htmlspecialchars($tarefa['titulo']) ?>
                                <?php if ($vencida): ?><span class="ml-1 text-[10px] bg-red-500 text-white px-1 rounded font-bold">VENCIDA</span><?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($tarefa['area']) ?></td>
                            <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($tarefa['responsavel']) ?></td>
                            <td class="px-4 py-3 <?= $vencida ? 'text-red-600 font-semibold' : 'text-gray-500' ?>"><?= date('d/m/Y', strtotime($tarefa['prazo'])) ?></td>
                            <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $prioBadge ?>"><?= ucfirst($tarefa['prioridade']) ?></span></td>
                            <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $statusBadge ?>"><?= $statusLabel ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Histórico de Reuniões -->
<?php if (!empty($plano['reunioes'])): ?>
<div class="mt-8 bg-white rounded-lg shadow-sm border border-gray-200">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800">📝 Histórico de Reuniões</h3>
    </div>
    <div class="p-4 space-y-3">
        <?php foreach ($plano['reunioes'] as $reuniao): ?>
        <div class="border-l-4 border-l-primary/30 pl-4 py-2">
            <div class="flex items-center gap-3 mb-1">
                <span class="text-sm font-semibold text-gray-800"><?= date('d/m/Y', strtotime($reuniao['data'])) ?></span>
                <span class="text-xs text-gray-400">👥 <?= htmlspecialchars($reuniao['participantes']) ?></span>
            </div>
            <p class="text-sm text-gray-600"><strong>Decisões:</strong> <?= htmlspecialchars($reuniao['decisoes']) ?></p>
            <p class="text-sm text-gray-500 mt-1"><strong>Próximos passos:</strong> <?= htmlspecialchars($reuniao['proximos_passos']) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Modal Registrar Reunião -->
<div id="modal-reuniao" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">Registrar Reunião</h3>
            <button onclick="document.getElementById('modal-reuniao').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="form-reuniao" class="p-6 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data da reunião *</label>
                <input type="date" name="data_reuniao" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Participantes</label>
                <input type="text" name="participantes" placeholder="Nomes separados por vírgula" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Decisões tomadas *</label>
                <textarea name="decisoes" required rows="3" placeholder="O que foi decidido nesta reunião?" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Próximos passos</label>
                <textarea name="proximos_passos" rows="2" placeholder="Ações definidas para a próxima semana" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none"></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('modal-reuniao').classList.add('hidden')" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-700">Salvar Reunião</button>
            </div>
        </form>
    </div>
</div>

<!-- F-12: Modal Acionar Parceiro -->
<div id="modal-parceiro" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">🤝 Acionar Parceiro</h3>
            <button onclick="fecharModalParceiro()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="form-acionar-parceiro" class="p-6 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
            <input type="hidden" id="parceiro-tarefa-id" name="tarefa_id" value="">
            
            <div class="bg-blue-50 p-3 rounded-lg">
                <p class="text-sm font-medium text-gray-700">Tarefa:</p>
                <p class="text-sm text-gray-600" id="parceiro-tarefa-titulo"></p>
                <p class="text-xs text-gray-500 mt-1">Área: <span id="parceiro-tarefa-area"></span></p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Parceiro *</label>
                <div id="lista-parceiros" class="space-y-2 max-h-40 overflow-y-auto">
                    <!-- Carregado via JavaScript -->
                    <div class="text-center py-4 text-gray-500">
                        <div class="inline-block w-4 h-4 border-2 border-gray-200 border-t-primary rounded-full animate-spin"></div>
                        <p class="text-sm mt-2">Carregando parceiros...</p>
                    </div>
                </div>
                <input type="hidden" id="parceiro-selecionado" name="parceiro_id" required>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Urgência *</label>
                <select name="urgencia" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                    <option value="baixa">🟢 Baixa - Até 30 dias</option>
                    <option value="media" selected>🟡 Média - Até 15 dias</option>
                    <option value="alta">🟠 Alta - Até 7 dias</option>
                    <option value="critica">🔴 Crítica - Até 3 dias</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descrição da necessidade *</label>
                <textarea name="descricao_necessidade" required rows="4" 
                          placeholder="Descreva detalhadamente o que precisa do parceiro para esta tarefa..."
                          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none"></textarea>
            </div>
            
            <div class="flex justify-end gap-3 pt-4">
                <button type="button" onclick="fecharModalParceiro()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-700">🚀 Solicitar Parceiro</button>
            </div>
        </form>
    </div>
</div>

<!-- Sortable.js CDN para drag-and-drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
// Variáveis globais F-12
let parceiroModal = null;
let parceirosDisponiveis = [];

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    // Carregar status de parceiros para todas as tarefas
    carregarStatusParceiros();
    
    // Configurar Kanban drag-and-drop
    configurarKanban();
});

// === F-12: PARCEIROS ===

async function abrirModalParceiro(tarefaId, titulo, area) {
    document.getElementById('parceiro-tarefa-id').value = tarefaId;
    document.getElementById('parceiro-tarefa-titulo').textContent = titulo;
    document.getElementById('parceiro-tarefa-area').textContent = area;
    document.getElementById('parceiro-selecionado').value = '';
    
    // Carregar parceiros da área
    await carregarParceirosArea(area);
    
    document.getElementById('modal-parceiro').classList.remove('hidden');
}

function fecharModalParceiro() {
    document.getElementById('modal-parceiro').classList.add('hidden');
    document.getElementById('form-acionar-parceiro').reset();
}

async function carregarParceirosArea(area) {
    const listaParceiros = document.getElementById('lista-parceiros');
    
    try {
        const res = await fetch(`<?= APP_URL ?>/plano/listar-parceiros?area=${encodeURIComponent(area)}`);
        const data = await res.json();
        
        if (data.sucesso && data.parceiros.length > 0) {
            let html = '';
            data.parceiros.forEach(parceiro => {
                const avaliacaoEstrelas = parceiro.avaliacao_media ? '★'.repeat(Math.floor(parceiro.avaliacao_media)) : 'Novo';
                html += `
                    <div class="border rounded-lg p-3 cursor-pointer hover:bg-blue-50 transition parceiro-opcao" 
                         onclick="selecionarParceiro(${parceiro.id}, '${parceiro.nome}')">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-800">${parceiro.nome}</p>
                                <p class="text-xs text-gray-500">${parceiro.categoria} • ${parceiro.nivel_experiencia || 'Pleno'}</p>
                                <p class="text-xs text-yellow-600">${avaliacaoEstrelas} ${parceiro.avaliacao_media ? '(' + parceiro.avaliacao_media + ')' : ''}</p>
                            </div>
                            <div class="text-xs text-gray-400">
                                ${parceiro.total_solicitacoes || 0} projetos
                            </div>
                        </div>
                    </div>
                `;
            });
            listaParceiros.innerHTML = html;
        } else {
            listaParceiros.innerHTML = '<p class="text-center text-gray-500 py-4">Nenhum parceiro homologado encontrado para esta área.</p>';
        }
    } catch (e) {
        listaParceiros.innerHTML = '<p class="text-center text-red-500 py-4">Erro ao carregar parceiros. Tente novamente.</p>';
    }
}

function selecionarParceiro(id, nome) {
    document.getElementById('parceiro-selecionado').value = id;
    
    // Atualizar visual de seleção
    document.querySelectorAll('.parceiro-opcao').forEach(el => {
        el.classList.remove('bg-blue-100', 'border-blue-500');
        el.classList.add('border-gray-300');
    });
    
    event.target.closest('.parceiro-opcao').classList.add('bg-blue-100', 'border-blue-500');
}

async function carregarStatusParceiros() {
    const statusElements = document.querySelectorAll('.parceiro-status');
    
    for (const element of statusElements) {
        const tarefaId = element.dataset.tarefaId;
        
        try {
            const res = await fetch(`<?= APP_URL ?>/plano/status-solicitacao-parceiro?tarefa_id=${tarefaId}`);
            const data = await res.json();
            
            if (data.sucesso && data.solicitacao) {
                const sol = data.solicitacao;
                const statusConfig = {
                    'solicitado': { color: 'bg-yellow-100 text-yellow-700', label: '🕐 Solicitado' },
                    'em_contato': { color: 'bg-blue-100 text-blue-700', label: '📞 Em contato' },
                    'em_execucao': { color: 'bg-purple-100 text-purple-700', label: '⚡ Em execução' },
                    'concluido': { color: 'bg-green-100 text-green-700', label: '✅ Concluído' },
                    'cancelado': { color: 'bg-gray-100 text-gray-600', label: '❌ Cancelado' }
                };
                
                const config = statusConfig[sol.status] || statusConfig.solicitado;
                element.innerHTML = `<span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold ${config.color}">${config.label}</span>`;
            }
        } catch (e) {
            // Silencioso - não há solicitação para esta tarefa
        }
    }
}

// Form submission
document.getElementById('form-acionar-parceiro').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const parceiroId = document.getElementById('parceiro-selecionado').value;
    if (!parceiroId) {
        alert('Selecione um parceiro.');
        return;
    }
    
    const formData = new FormData(this);
    const button = this.querySelector('button[type="submit"]');
    const originalText = button.textContent;
    
    button.disabled = true;
    button.textContent = '⏳ Solicitando...';
    
    try {
        const res = await fetch('<?= APP_URL ?>/plano/acionar-parceiro', { method: 'POST', body: formData });
        const data = await res.json();
        
        if (data.sucesso) {
            fecharModalParceiro();
            if (typeof Toast !== 'undefined') {
                Toast.sucesso(data.mensagem);
            } else {
                alert(data.mensagem);
            }
            
            // Recarregar status de parceiros
            setTimeout(carregarStatusParceiros, 1000);
        } else {
            alert(data.erro || 'Erro ao solicitar parceiro.');
        }
    } catch (e) {
        alert('Erro de conexão. Tente novamente.');
    } finally {
        button.disabled = false;
        button.textContent = originalText;
    }
});

// === KANBAN & REUNIÕES (código existente) ===

// Form reunião
document.getElementById('form-reuniao').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    try {
        const res = await fetch('<?= APP_URL ?>/plano-de-acao/reuniao', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.sucesso) {
            document.getElementById('modal-reuniao').classList.add('hidden');
            if (typeof Toast !== 'undefined') Toast.sucesso(data.mensagem);
            else alert(data.mensagem);
        } else {
            alert(data.erro || 'Erro.');
        }
    } catch (err) { alert('Erro de conexão.'); }
});

function configurarKanban() {
    document.querySelectorAll('.kanban-column').forEach(col => {
        new Sortable(col, {
            group: 'kanban',
            animation: 150,
            ghostClass: 'opacity-40',
            onEnd: async function(evt) {
                const tarefaId = evt.item.dataset.id;
                const novoStatus = evt.to.dataset.status;
                const formData = new FormData();
                formData.append('csrf_token', '<?= Csrf::token() ?>');
                formData.append('tarefa_id', tarefaId);
                formData.append('status', novoStatus);
                try {
                    await fetch('<?= APP_URL ?>/plano-de-acao/tarefa-status', { method: 'POST', body: formData });
                } catch(e) {}
            }
        });
    });
}

function editarTarefa(id) {
    alert('Funcionalidade de edição será implementada em breve.');
}
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
