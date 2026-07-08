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
    <div class="flex flex-wrap items-center gap-2">
        <button onclick="abrirModalTarefaIA()"
                class="bg-accent text-white px-4 py-2 rounded-lg text-sm font-medium hover:opacity-90 transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            Criar com IA
        </button>
        <button onclick="document.getElementById('modal-tarefa').classList.remove('hidden')"
                class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nova Tarefa
        </button>
        <button onclick="document.getElementById('modal-reuniao').classList.remove('hidden')"
                class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-700 transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Registrar Reunião
        </button>
    </div>
</div>

<!-- Score de maturidade -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 mb-6">
    <div class="flex items-center justify-between mb-2">
        <span class="text-sm font-medium text-gray-700">Maturidade da empresa</span>
        <span class="text-sm font-bold text-accent"><?= round($plano['score_maturidade'] ?? 0) ?>%</span>
    </div>
    <div class="w-full h-3 bg-gray-200 rounded-full overflow-hidden">
        <div class="h-full bg-accent rounded-full transition-all" style="width: <?= round($plano['score_maturidade'] ?? 0) ?>%"></div>
    </div>
    <p class="text-xs text-gray-400 mt-1">
        Etapa <?= (int) ($plano['etapa_atual'] ?? 1) ?> de <?= (int) ($plano['total_etapas'] ?? 1) ?> ·
        parte de <?= round($plano['score_inicial'] ?? 0) ?>% (diagnóstico) e avança conforme as tarefas são concluídas.
    </p>
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
        <nav class="flex gap-0 flex-wrap">
            <button @click="abaAtiva = 'kanban'" :class="abaAtiva === 'kanban' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500'" class="px-5 py-3 text-sm transition">📋 Kanban</button>
            <button @click="abaAtiva = 'roadmap'" :class="abaAtiva === 'roadmap' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500'" class="px-5 py-3 text-sm transition">🗺️ Roadmap</button>
            <button @click="abaAtiva = 'lista'" :class="abaAtiva === 'lista' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500'" class="px-5 py-3 text-sm transition">📊 Lista</button>
            <button @click="abaAtiva = 'calendario'" :class="abaAtiva === 'calendario' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500'" class="px-5 py-3 text-sm transition">📅 Calendário</button>
            <button @click="abaAtiva = 'metricas'; setTimeout(() => window.renderCharts && window.renderCharts(), 100)" :class="abaAtiva === 'metricas' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500'" class="px-5 py-3 text-sm transition">📈 Métricas</button>
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
                    <span data-count class="w-5 h-5 rounded-full bg-<?= $coluna['cor'] ?>-200 text-<?= $coluna['cor'] ?>-700 text-xs flex items-center justify-center font-bold"><?= count($tarefasColuna) ?></span>
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
                            <p class="text-sm font-medium text-gray-800 leading-tight cursor-pointer hover:text-primary" onclick="abrirCard(<?= $tarefa['id'] ?>)"><?= htmlspecialchars($tarefa['titulo']) ?></p>
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
                                
                                <button onclick="abrirCard(<?= $tarefa['id'] ?>)" class="text-xs text-gray-400 hover:text-gray-600" title="Abrir card">
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

    <!-- ABA ROADMAP (fila completa de etapas) -->
    <div x-show="abaAtiva === 'roadmap'" x-transition style="display:none;">
        <?php $fila = $dados['fila'] ?? []; ?>
        <div class="bg-white rounded-lg border border-gray-200 p-4 mb-4">
            <p class="text-sm text-gray-600">
                Todas as etapas do plano já estão criadas na ordem correta. Cada etapa aparece no Kanban conforme a anterior é concluída —
                mas você pode <b>liberar manualmente</b> qualquer tarefa para o Kanban quando quiser, ou recolhê-la de volta.
            </p>
        </div>

        <?php if (empty($fila)): ?>
        <div class="bg-white rounded-lg border border-gray-200 p-8 text-center text-gray-500">Nenhuma tarefa no roadmap ainda.</div>
        <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($fila as $numEtapa => $tarefasEtapa):
                $totalEt = count($tarefasEtapa);
                $concluidasEt = count(array_filter($tarefasEtapa, fn($t) => ($t['status'] ?? '') === 'concluido'));
            ?>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-5 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                    <h4 class="text-sm font-bold text-gray-800">Etapa <?= (int) $numEtapa ?></h4>
                    <span class="text-xs text-gray-500"><?= $concluidasEt ?>/<?= $totalEt ?> concluídas</span>
                </div>
                <div class="divide-y divide-gray-100">
                    <?php foreach ($tarefasEtapa as $t):
                        $liberada = (int) ($t['liberada'] ?? 1) === 1;
                        $concluido = ($t['status'] ?? '') === 'concluido';
                        $prioBadge = match($t['prioridade'] ?? 'media') {
                            'alta' => 'bg-red-100 text-red-700',
                            'baixa' => 'bg-blue-100 text-blue-700',
                            default => 'bg-yellow-100 text-yellow-700',
                        };
                    ?>
                    <div class="px-5 py-3 flex items-center gap-3">
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold <?= $prioBadge ?>"><?= strtoupper(substr($t['prioridade'] ?? 'M', 0, 1)) ?></span>
                        <div class="flex-1 min-w-0 cursor-pointer" onclick="abrirCard(<?= (int) $t['id'] ?>)">
                            <p class="text-sm font-medium text-gray-800 <?= $concluido ? 'line-through text-gray-400' : '' ?> truncate hover:text-primary"><?= htmlspecialchars($t['titulo']) ?></p>
                            <p class="text-xs text-gray-400">
                                <?= htmlspecialchars($t['area'] ?? 'Geral') ?>
                                <?= !empty($t['prazo']) ? ' · ' . date('d/m/Y', strtotime($t['prazo'])) : '' ?>
                                · <span class="text-primary/70">ver detalhes</span>
                            </p>
                        </div>
                        <?php if ($concluido): ?>
                            <span class="text-xs font-medium text-green-600">✓ Concluída</span>
                        <?php elseif ($liberada): ?>
                            <span class="text-xs text-gray-400 mr-1">No Kanban</span>
                            <button onclick="toggleLiberacao(<?= (int) $t['id'] ?>, false, this)" class="text-xs px-3 py-1 rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50">Recolher</button>
                        <?php else: ?>
                            <span class="text-xs text-gray-400 mr-1">Na fila</span>
                            <button onclick="toggleLiberacao(<?= (int) $t['id'] ?>, true, this)" class="text-xs px-3 py-1 rounded-lg bg-primary text-white hover:bg-primary-700">Enviar ao Kanban</button>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
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

    <!-- ABA CALENDÁRIO (grade mensal) -->
    <div x-show="abaAtiva === 'calendario'" x-transition style="display:none;">
        <?php
            // Mapa de itens por data (YYYY-MM-DD) para o calendário.
            $itensCal = $dados['calendario'] ?? [];
            $eventosPorData = [];
            foreach ($itensCal as $it) {
                $d = $it['data'];
                if (!$d) continue;
                $eventosPorData[$d][] = [
                    'titulo' => $it['titulo'],
                    'hora' => $it['hora'] ? substr($it['hora'], 0, 5) : '',
                    'tipo' => $it['tipo'],
                    'status' => $it['status'] ?? '',
                ];
            }
        ?>
        <div x-data="calendarioPlano(<?= htmlspecialchars(json_encode($eventosPorData), ENT_QUOTES) ?>)" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <!-- Cabeçalho de navegação -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <button @click="mudarMes(-1)" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">‹</button>
                    <button @click="irHoje()" class="px-3 h-8 rounded-lg border border-gray-200 text-sm text-gray-600 hover:bg-gray-50">Hoje</button>
                    <button @click="mudarMes(1)" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">›</button>
                </div>
                <h3 class="text-lg font-bold text-primary" x-text="tituloMes"></h3>
                <div class="w-24"></div>
            </div>

            <!-- Cabeçalho dos dias da semana -->
            <div class="grid grid-cols-7 gap-px text-center text-xs font-semibold text-gray-500 mb-px">
                <template x-for="d in ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb']" :key="d">
                    <div class="py-2" x-text="d"></div>
                </template>
            </div>

            <!-- Grade dos dias -->
            <div class="grid grid-cols-7 gap-px bg-gray-100 rounded-lg overflow-hidden">
                <template x-for="(cel, idx) in celulas" :key="idx">
                    <div class="bg-white min-h-[92px] p-1.5 align-top"
                         :class="{ 'bg-gray-50': !cel.doMes }">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium"
                                  :class="cel.hoje ? 'bg-accent text-white rounded-full w-5 h-5 flex items-center justify-center' : (cel.doMes ? 'text-gray-700' : 'text-gray-300')"
                                  x-text="cel.dia"></span>
                        </div>
                        <div class="mt-1 space-y-1">
                            <template x-for="(ev, i) in cel.eventos" :key="i">
                                <div class="text-[10px] leading-tight truncate rounded px-1 py-0.5 text-white"
                                     :class="ev.status === 'concluido' ? 'bg-green-600' : (ev.tipo === 'reuniao' ? 'bg-purple-600' : 'bg-primary')"
                                     :title="ev.titulo + (ev.hora ? ' · ' + ev.hora : '')">
                                    <span x-show="ev.hora" x-text="ev.hora + ' '"></span><span x-text="ev.titulo"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
            <p class="text-xs text-gray-400 mt-3">Compromissos, reuniões e prazos de tarefas aparecem aqui. Crie com "Nova Tarefa" ou "Criar com IA".</p>
        </div>
    </div>

    <!-- ABA MÉTRICAS -->
    <div x-show="abaAtiva === 'metricas'" x-transition style="display:none;">
        <?php $metricas = $dados['metricas'] ?? []; ?>
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">Acompanhe KPIs da empresa. Registre valores na frequência definida para ver a evolução.</p>
            <button onclick="document.getElementById('modal-metrica').classList.remove('hidden')" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-700">+ Nova métrica</button>
        </div>

        <?php if (empty($metricas)): ?>
        <div class="bg-white rounded-lg border border-gray-200 p-8 text-center text-gray-500">
            Nenhuma métrica cadastrada. Clique em "Nova métrica" para começar a monitorar (financeiro, leads, conversão...).
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <?php foreach ($metricas as $m):
                $registros = $m['registros'] ?? [];
                $valores = array_map(fn($r) => (float) $r['valor'], $registros);
                $labels = array_map(fn($r) => date('d/m', strtotime($r['data_referencia'])), $registros);
                $ultimo = $m['ultimo_valor'];
            ?>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($m['nome']) ?></h4>
                        <p class="text-xs text-gray-400"><?= htmlspecialchars(ucfirst($m['categoria'])) ?> · <?= htmlspecialchars($m['frequencia']) ?><?= $m['meta'] !== null ? ' · meta: ' . htmlspecialchars($m['unidade'] . ' ' . $m['meta']) : '' ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-primary"><?= $ultimo !== null ? htmlspecialchars(($m['unidade'] ? $m['unidade'] . ' ' : '') . $ultimo) : '—' ?></p>
                        <button onclick="abrirRegistroMetrica(<?= (int) $m['id'] ?>, '<?= htmlspecialchars($m['nome'], ENT_QUOTES) ?>')" class="text-xs text-primary hover:underline">+ registrar</button>
                    </div>
                </div>
                <canvas id="chart-metrica-<?= (int) $m['id'] ?>" height="120"
                        data-labels='<?= htmlspecialchars(json_encode($labels), ENT_QUOTES) ?>'
                        data-valores='<?= htmlspecialchars(json_encode($valores), ENT_QUOTES) ?>'
                        data-nome='<?= htmlspecialchars($m['nome'], ENT_QUOTES) ?>'></canvas>
                <?php if (empty($registros)): ?>
                <p class="text-xs text-gray-400 text-center mt-2">Sem registros ainda.</p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
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

<!-- Modal Nova Tarefa (manual) -->
<div id="modal-tarefa" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">Nova Tarefa</h3>
            <button onclick="document.getElementById('modal-tarefa').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <form id="form-tarefa" class="p-6 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
            <input type="hidden" name="plano_id" value="<?= (int) $plano['id'] ?>">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Título *</label>
                <input type="text" name="titulo" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                <textarea name="descricao" rows="2" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                    <select name="tipo" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                        <option value="tarefa">Tarefa</option>
                        <option value="reuniao">Reunião</option>
                        <option value="entrega">Entrega</option>
                        <option value="compromisso">Compromisso</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prioridade</label>
                    <select name="prioridade" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                        <option value="alta">Alta</option>
                        <option value="media" selected>Média</option>
                        <option value="baixa">Baixa</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data</label>
                    <input type="date" name="prazo" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hora</label>
                    <input type="time" name="hora" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Responsável</label>
                    <input type="text" name="responsavel" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                </div>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('modal-tarefa').classList.add('hidden')" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-700">Salvar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Criar com IA -->
<div id="modal-tarefa-ia" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">⚡ Criar compromisso com IA</h3>
            <button onclick="document.getElementById('modal-tarefa-ia').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <div class="p-6 space-y-4">
            <p class="text-sm text-gray-500">Escreva ou fale naturalmente. Ex.: "reunião com o comercial sexta às 15h para revisar metas" ou "entregar proposta ao cliente dia 20".</p>
            <textarea id="ia-texto" rows="3" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none" placeholder="Descreva o compromisso..."></textarea>
            <div id="ia-preview" class="hidden text-sm bg-green-50 border border-green-200 rounded-lg p-3 text-green-800"></div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('modal-tarefa-ia').classList.add('hidden')" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancelar</button>
                <button type="button" id="btn-ia-criar" onclick="criarTarefaIA()" class="px-4 py-2 bg-accent text-white rounded-lg text-sm font-medium hover:opacity-90">Agendar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nova Métrica -->
<div id="modal-metrica" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">Nova Métrica / KPI</h3>
            <button onclick="document.getElementById('modal-metrica').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <form id="form-metrica" class="p-6 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
            <input type="hidden" name="plano_id" value="<?= (int) $plano['id'] ?>">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome *</label>
                <input type="text" name="nome" required placeholder="Ex.: Faturamento mensal" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                    <select name="categoria" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                        <option value="financeiro">Financeiro</option>
                        <option value="leads">Leads</option>
                        <option value="comercial">Comercial</option>
                        <option value="operacional">Operacional</option>
                        <option value="geral">Geral</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Unidade</label>
                    <input type="text" name="unidade" placeholder="R$, %, leads..." class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Meta</label>
                    <input type="number" step="any" name="meta" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Frequência</label>
                    <select name="frequencia" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                        <option value="semanal">Semanal</option>
                        <option value="quinzenal">Quinzenal</option>
                        <option value="mensal" selected>Mensal</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Direção (o que é melhor)</label>
                    <select name="direcao" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                        <option value="cima">Maior é melhor</option>
                        <option value="baixo">Menor é melhor</option>
                    </select>
                </div>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('modal-metrica').classList.add('hidden')" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-700">Criar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Registrar valor de Métrica -->
<div id="modal-registro-metrica" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">Registrar valor — <span id="reg-metrica-nome"></span></h3>
            <button onclick="document.getElementById('modal-registro-metrica').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <form id="form-registro-metrica" class="p-6 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
            <input type="hidden" name="plano_id" value="<?= (int) $plano['id'] ?>">
            <input type="hidden" name="metrica_id" id="reg-metrica-id" value="">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor *</label>
                    <input type="number" step="any" name="valor" required class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Data</label>
                    <input type="date" name="data_referencia" value="<?= date('Y-m-d') ?>" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Observação</label>
                <input type="text" name="observacao" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('modal-registro-metrica').classList.add('hidden')" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-700">Salvar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Detalhe do Card (estilo Trello) -->
<div id="modal-card" class="hidden fixed inset-0 bg-black/50 z-50 flex items-start justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full my-8">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <select id="card-status" class="text-sm border border-gray-300 rounded-lg px-2 py-1 outline-none focus:border-primary">
                    <option value="pendente">Pendente</option>
                    <option value="em_andamento">Em Andamento</option>
                    <option value="bloqueado">Bloqueado</option>
                    <option value="concluido">Concluído</option>
                </select>
            </div>
            <button onclick="fecharCard()" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-0">
            <!-- Coluna principal -->
            <div class="md:col-span-2 p-6 space-y-5">
                <input type="hidden" id="card-id" value="">
                <div>
                    <input type="text" id="card-titulo" class="w-full text-lg font-bold text-gray-800 border-0 border-b border-transparent hover:border-gray-200 focus:border-primary outline-none px-0 py-1" placeholder="Título">
                </div>

                <!-- Etiquetas / datas / prioridade -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">🏷️ Etiquetas (vírgula)</label>
                        <input type="text" id="card-etiquetas" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary" placeholder="Ex.: urgente, cliente">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Prioridade</label>
                        <select id="card-prioridade" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                            <option value="alta">Alta</option><option value="media">Média</option><option value="baixa">Baixa</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">📅 Início</label>
                        <input type="date" id="card-data-inicio" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">📅 Entrega</label>
                        <div class="flex gap-2">
                            <input type="date" id="card-prazo" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                            <input type="time" id="card-hora" class="w-28 px-2 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                        </div>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">👤 Responsável</label>
                        <input type="text" id="card-responsavel" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                    </div>
                </div>

                <!-- Contexto do diagnóstico (por que esta ação existe / como abordar) -->
                <div id="card-contexto" class="hidden bg-blue-50 border border-blue-100 rounded-lg p-3 text-sm">
                    <p class="font-semibold text-blue-800 mb-1">🎯 Por que esta ação</p>
                    <p class="text-blue-700" id="card-contexto-problema"></p>
                    <p class="font-semibold text-blue-800 mt-2 mb-1">✅ Como fazer (sugestão)</p>
                    <p class="text-blue-700" id="card-contexto-acao"></p>
                </div>

                <!-- Descrição (com colar imagem) -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">☰ Descrição</label>
                    <p class="text-xs text-gray-400 mb-1">Dica: cole (Ctrl+V) uma imagem diretamente aqui.</p>
                    <textarea id="card-descricao" rows="5" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-y" placeholder="Adicione uma descrição mais detalhada..."></textarea>
                    <div id="card-anexos" class="flex flex-wrap gap-2 mt-2"></div>
                </div>

                <!-- Checklist -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-sm font-semibold text-gray-700">☑ Checklist</label>
                        <span id="card-checklist-progresso" class="text-xs text-gray-400"></span>
                    </div>
                    <div id="card-checklist" class="space-y-1"></div>
                    <div class="flex gap-2 mt-2">
                        <input type="text" id="card-novo-item" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary" placeholder="Adicionar item...">
                        <button onclick="addChecklistItem()" class="px-3 py-2 bg-gray-100 rounded-lg text-sm hover:bg-gray-200">Adicionar</button>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                    <button onclick="fecharCard()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Fechar</button>
                    <button id="card-btn-salvar" onclick="salvarCard()" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-700">Salvar</button>
                </div>
            </div>

            <!-- Coluna lateral: comentários -->
            <div class="bg-gray-50 p-6 border-l border-gray-100">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">💬 Comentários e atividade</h4>
                <textarea id="card-comentario" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none mb-2" placeholder="Escrever um comentário..."></textarea>
                <button onclick="comentarCard()" class="w-full px-3 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-700 mb-4">Comentar</button>
                <div id="card-comentarios" class="space-y-3"></div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js para gráficos das métricas -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
                // Só age se realmente mudou de coluna.
                const origem = evt.from;
                const destino = evt.to;
                const tarefaId = evt.item.dataset.id;
                const novoStatus = destino.dataset.status;
                if (!tarefaId || !novoStatus) return;
                if (origem === destino) return; // reordenou na mesma coluna

                const formData = new FormData();
                formData.append('csrf_token', PLANO_CSRF);
                formData.append('tarefa_id', tarefaId);
                formData.append('novo_status', novoStatus);
                try {
                    const res = await fetch('<?= APP_URL ?>/plano-de-acao/mover-tarefa', {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: formData
                    });
                    const data = await res.json();
                    if (!data || !data.success) {
                        alert((data && data.message) || 'Não foi possível mover a tarefa.');
                        location.reload(); // volta ao estado real do servidor
                        return;
                    }
                    // Atualiza contadores e, se liberou nova etapa, recarrega para mostrá-la.
                    if (data.tarefas_liberadas > 0) {
                        location.reload();
                    } else {
                        atualizarContadoresKanban();
                    }
                } catch(e) {
                    alert('Erro de conexão ao mover a tarefa.');
                    location.reload();
                }
            }
        });
    });
}

// Recalcula os números das colunas do Kanban após um drag.
function atualizarContadoresKanban() {
    document.querySelectorAll('.kanban-column').forEach(col => {
        const status = col.dataset.status;
        const n = col.querySelectorAll('.kanban-card').length;
        const badge = document.querySelector('#col-' + status + ' [data-count]');
        if (badge) badge.textContent = n;
    });
}

function editarTarefa(id) {
    alert('Funcionalidade de edição será implementada em breve.');
}

// ===== Modal Detalhe do Card =====
let cardChecklist = [];
const PLANO_ID = <?= (int) $plano['id'] ?>;

async function abrirCard(tarefaId) {
    try {
        const res = await fetch('<?= APP_URL ?>/plano-de-acao/tarefa-detalhe?plano_id=' + PLANO_ID + '&tarefa_id=' + tarefaId);
        const data = await res.json();
        if (!data.sucesso) { alert(data.erro || 'Erro ao abrir card.'); return; }
        const t = data.tarefa;
        document.getElementById('card-id').value = t.id;
        document.getElementById('card-titulo').value = t.titulo || '';
        document.getElementById('card-descricao').value = t.descricao || '';
        document.getElementById('card-responsavel').value = t.responsavel || '';
        document.getElementById('card-data-inicio').value = t.data_inicio || '';
        document.getElementById('card-prazo').value = t.prazo || '';
        document.getElementById('card-hora').value = t.hora ? String(t.hora).substring(0,5) : '';
        document.getElementById('card-prioridade').value = t.prioridade || 'media';
        document.getElementById('card-status').value = t.status || 'pendente';
        document.getElementById('card-etiquetas').value = (t.etiquetas || []).join(', ');
        // Contexto do diagnóstico (só para tarefas do plano)
        const ctxBox = document.getElementById('card-contexto');
        if (t.contexto_prioridade && (t.contexto_prioridade.descricao_problema || t.contexto_prioridade.acao_sugerida)) {
            document.getElementById('card-contexto-problema').textContent = t.contexto_prioridade.descricao_problema || '—';
            document.getElementById('card-contexto-acao').textContent = t.contexto_prioridade.acao_sugerida || '—';
            ctxBox.classList.remove('hidden');
        } else {
            ctxBox.classList.add('hidden');
        }
        cardChecklist = Array.isArray(t.checklist) ? t.checklist : [];
        renderChecklist();
        renderAnexos(t.anexos || []);
        renderComentarios(t.comentarios || []);
        document.getElementById('card-comentario').value = '';
        document.getElementById('modal-card').classList.remove('hidden');
    } catch (e) { alert('Erro de conexão.'); }
}
function fecharCard() { document.getElementById('modal-card').classList.add('hidden'); }

function renderChecklist() {
    const cont = document.getElementById('card-checklist');
    const feitos = cardChecklist.filter(i => i.feito).length;
    document.getElementById('card-checklist-progresso').textContent = cardChecklist.length ? (feitos + '/' + cardChecklist.length) : '';
    cont.innerHTML = cardChecklist.map((item, i) =>
        '<label class="flex items-center gap-2 text-sm">' +
        '<input type="checkbox" ' + (item.feito ? 'checked' : '') + ' onchange="toggleChecklist(' + i + ')" class="w-4 h-4 rounded border-gray-300">' +
        '<span class="' + (item.feito ? 'line-through text-gray-400' : 'text-gray-700') + '">' + escapeHtml(item.texto) + '</span>' +
        '<button onclick="removeChecklist(' + i + ')" class="ml-auto text-gray-300 hover:text-red-500 text-xs">✕</button>' +
        '</label>'
    ).join('');
}
function addChecklistItem() {
    const inp = document.getElementById('card-novo-item');
    const v = inp.value.trim();
    if (!v) return;
    cardChecklist.push({ texto: v, feito: false });
    inp.value = '';
    renderChecklist();
}
function toggleChecklist(i) { cardChecklist[i].feito = !cardChecklist[i].feito; renderChecklist(); }
function removeChecklist(i) { cardChecklist.splice(i, 1); renderChecklist(); }

function renderAnexos(anexos) {
    const cont = document.getElementById('card-anexos');
    cont.innerHTML = (anexos || []).map(a =>
        '<a href="' + a.url + '" target="_blank" class="block"><img src="' + a.url + '" class="w-20 h-20 object-cover rounded-lg border border-gray-200"></a>'
    ).join('');
}
function renderComentarios(coms) {
    const cont = document.getElementById('card-comentarios');
    if (!coms.length) { cont.innerHTML = '<p class="text-xs text-gray-400">Sem comentários ainda.</p>'; return; }
    cont.innerHTML = coms.map(c =>
        '<div class="text-sm"><span class="font-semibold text-gray-700">' + escapeHtml(c.usuario_nome || 'Usuário') + '</span>' +
        '<span class="text-xs text-gray-400 ml-2">' + (c.criado_em ? c.criado_em.substring(0,16).replace('T',' ') : '') + '</span>' +
        '<p class="text-gray-600 mt-0.5">' + escapeHtml(c.texto) + '</p></div>'
    ).join('');
}
function escapeHtml(s) { return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

async function salvarCard() {
    const btn = document.getElementById('card-btn-salvar');
    btn.disabled = true; btn.textContent = 'Salvando...';
    const id = document.getElementById('card-id').value;
    try {
        // Salvar campos do card
        const fd = new FormData();
        fd.append('csrf_token', PLANO_CSRF);
        fd.append('plano_id', PLANO_ID);
        fd.append('tarefa_id', id);
        fd.append('titulo', document.getElementById('card-titulo').value);
        fd.append('descricao', document.getElementById('card-descricao').value);
        fd.append('responsavel', document.getElementById('card-responsavel').value);
        fd.append('data_inicio', document.getElementById('card-data-inicio').value);
        fd.append('prazo', document.getElementById('card-prazo').value);
        fd.append('hora', document.getElementById('card-hora').value);
        fd.append('prioridade', document.getElementById('card-prioridade').value);
        fd.append('etiquetas', document.getElementById('card-etiquetas').value);
        fd.append('checklist', JSON.stringify(cardChecklist));
        await fetch('<?= APP_URL ?>/plano-de-acao/salvar-tarefa-detalhe', { method: 'POST', body: fd });

        // Atualizar status (se mudou) via a rota de mover.
        const st = document.getElementById('card-status').value;
        const fd2 = new FormData();
        fd2.append('csrf_token', PLANO_CSRF);
        fd2.append('tarefa_id', id);
        fd2.append('novo_status', st);
        await fetch('<?= APP_URL ?>/plano-de-acao/mover-tarefa', { method: 'POST', body: fd2 });

        location.reload();
    } catch (e) { alert('Erro ao salvar.'); btn.disabled = false; btn.textContent = 'Salvar'; }
}

async function comentarCard() {
    const texto = document.getElementById('card-comentario').value.trim();
    if (!texto) return;
    const id = document.getElementById('card-id').value;
    const fd = new FormData();
    fd.append('csrf_token', PLANO_CSRF);
    fd.append('plano_id', PLANO_ID);
    fd.append('tarefa_id', id);
    fd.append('texto', texto);
    try {
        const res = await fetch('<?= APP_URL ?>/plano-de-acao/comentar-tarefa', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.sucesso) {
            document.getElementById('card-comentario').value = '';
            abrirCard(id); // recarrega comentários
        } else { alert(data.erro || 'Erro ao comentar.'); }
    } catch (e) { alert('Erro de conexão.'); }
}

// Colar imagem na descrição → faz upload e insere o link no texto.
document.addEventListener('DOMContentLoaded', function() {
    const desc = document.getElementById('card-descricao');
    if (!desc) return;
    desc.addEventListener('paste', async function(e) {
        const items = (e.clipboardData || e.originalEvent.clipboardData || {}).items || [];
        for (const item of items) {
            if (item.type && item.type.indexOf('image') === 0) {
                e.preventDefault();
                const file = item.getAsFile();
                const reader = new FileReader();
                reader.onload = async function(ev) {
                    const id = document.getElementById('card-id').value;
                    const fd = new FormData();
                    fd.append('csrf_token', PLANO_CSRF);
                    fd.append('plano_id', PLANO_ID);
                    fd.append('tarefa_id', id);
                    fd.append('imagem', ev.target.result);
                    try {
                        const res = await fetch('<?= APP_URL ?>/plano-de-acao/upload-imagem-tarefa', { method: 'POST', body: fd });
                        const data = await res.json();
                        if (data.sucesso) {
                            desc.value += (desc.value ? '\n' : '') + data.url;
                            // mostrar preview
                            const cont = document.getElementById('card-anexos');
                            const a = document.createElement('a'); a.href = data.url; a.target = '_blank';
                            a.innerHTML = '<img src="' + data.url + '" class="w-20 h-20 object-cover rounded-lg border border-gray-200">';
                            cont.appendChild(a);
                        } else { alert(data.erro || 'Falha no upload da imagem.'); }
                    } catch (err) { alert('Erro ao enviar imagem.'); }
                };
                reader.readAsDataURL(file);
            }
        }
    });
});

// Liberar/recolher tarefa da fila (Roadmap) para o Kanban.
async function toggleLiberacao(tarefaId, liberar, btn) {
    btn.disabled = true;
    btn.textContent = '...';
    try {
        const fd = new FormData();
        fd.append('csrf_token', '<?= Csrf::token() ?>');
        fd.append('plano_id', '<?= (int) $plano['id'] ?>');
        fd.append('tarefa_id', tarefaId);
        fd.append('liberar', liberar ? '1' : '0');
        const res = await fetch('<?= APP_URL ?>/plano-de-acao/liberar-tarefa', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.sucesso) { location.reload(); }
        else { alert(data.erro || 'Erro ao atualizar.'); btn.disabled = false; btn.textContent = liberar ? 'Enviar ao Kanban' : 'Recolher'; }
    } catch (e) { alert('Erro de conexão.'); btn.disabled = false; btn.textContent = liberar ? 'Enviar ao Kanban' : 'Recolher'; }
}

// Componente Alpine do calendário mensal.
function calendarioPlano(eventos) {
    const hoje = new Date();
    return {
        eventos: eventos || {},
        ano: hoje.getFullYear(),
        mes: hoje.getMonth(), // 0-11
        meses: ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'],
        get tituloMes() { return this.meses[this.mes] + ' ' + this.ano; },
        get celulas() {
            const cels = [];
            const primeiro = new Date(this.ano, this.mes, 1);
            const inicioSemana = primeiro.getDay(); // 0=Dom
            const diasNoMes = new Date(this.ano, this.mes + 1, 0).getDate();
            const hojeStr = this.fmt(new Date());
            // Dias do mês anterior para preencher a primeira semana
            const diasMesAnterior = new Date(this.ano, this.mes, 0).getDate();
            for (let i = inicioSemana - 1; i >= 0; i--) {
                const dia = diasMesAnterior - i;
                const dt = new Date(this.ano, this.mes - 1, dia);
                cels.push(this.montarCel(dt, false, hojeStr));
            }
            // Dias do mês atual
            for (let d = 1; d <= diasNoMes; d++) {
                const dt = new Date(this.ano, this.mes, d);
                cels.push(this.montarCel(dt, true, hojeStr));
            }
            // Completar até múltiplo de 7 (6 semanas = 42 no máximo)
            while (cels.length % 7 !== 0) {
                const ultimo = cels.length - inicioSemana - diasNoMes + 1;
                const dt = new Date(this.ano, this.mes + 1, ultimo);
                cels.push(this.montarCel(dt, false, hojeStr));
            }
            return cels;
        },
        montarCel(dt, doMes, hojeStr) {
            const iso = this.fmt(dt);
            return {
                dia: dt.getDate(),
                doMes: doMes,
                hoje: iso === hojeStr,
                eventos: this.eventos[iso] || [],
            };
        },
        fmt(dt) {
            const m = String(dt.getMonth() + 1).padStart(2, '0');
            const d = String(dt.getDate()).padStart(2, '0');
            return dt.getFullYear() + '-' + m + '-' + d;
        },
        mudarMes(delta) {
            this.mes += delta;
            if (this.mes < 0) { this.mes = 11; this.ano--; }
            else if (this.mes > 11) { this.mes = 0; this.ano++; }
        },
        irHoje() { const h = new Date(); this.ano = h.getFullYear(); this.mes = h.getMonth(); },
    };
}

const PLANO_CSRF = '<?= Csrf::token() ?>';

// ===== Nova Tarefa (manual) =====
document.getElementById('form-tarefa').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true; btn.textContent = 'Salvando...';
    try {
        const res = await fetch('<?= APP_URL ?>/plano-de-acao/criar-tarefa', { method: 'POST', body: new FormData(this) });
        const data = await res.json();
        if (data.sucesso) { location.reload(); }
        else { alert(data.erro || 'Erro ao criar tarefa.'); btn.disabled = false; btn.textContent = 'Salvar'; }
    } catch (err) { alert('Erro de conexão.'); btn.disabled = false; btn.textContent = 'Salvar'; }
});

// ===== Criar com IA =====
function abrirModalTarefaIA() {
    document.getElementById('ia-preview').classList.add('hidden');
    document.getElementById('ia-texto').value = '';
    document.getElementById('modal-tarefa-ia').classList.remove('hidden');
}
async function criarTarefaIA() {
    const texto = document.getElementById('ia-texto').value.trim();
    if (!texto) { alert('Descreva o compromisso.'); return; }
    const btn = document.getElementById('btn-ia-criar');
    btn.disabled = true; btn.textContent = 'Processando...';
    try {
        const fd = new FormData();
        fd.append('csrf_token', PLANO_CSRF);
        fd.append('plano_id', '<?= (int) $plano['id'] ?>');
        fd.append('texto', texto);
        const res = await fetch('<?= APP_URL ?>/plano-de-acao/criar-tarefa-ia', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.sucesso) {
            const t = data.tarefa || {};
            const prev = document.getElementById('ia-preview');
            prev.textContent = '✓ Agendado: ' + (t.titulo || '') + (t.data ? ' — ' + t.data : '') + (t.hora ? ' ' + t.hora : '');
            prev.classList.remove('hidden');
            setTimeout(() => location.reload(), 900);
        } else {
            alert(data.erro || 'Não consegui agendar.');
            btn.disabled = false; btn.textContent = 'Agendar';
        }
    } catch (err) { alert('Erro de conexão.'); btn.disabled = false; btn.textContent = 'Agendar'; }
}

// ===== Métricas =====
document.getElementById('form-metrica').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true; btn.textContent = 'Criando...';
    try {
        const res = await fetch('<?= APP_URL ?>/plano-de-acao/criar-metrica', { method: 'POST', body: new FormData(this) });
        const data = await res.json();
        if (data.sucesso) { location.reload(); }
        else { alert(data.erro || 'Erro ao criar métrica.'); btn.disabled = false; btn.textContent = 'Criar'; }
    } catch (err) { alert('Erro de conexão.'); btn.disabled = false; btn.textContent = 'Criar'; }
});

function abrirRegistroMetrica(id, nome) {
    document.getElementById('reg-metrica-id').value = id;
    document.getElementById('reg-metrica-nome').textContent = nome;
    document.getElementById('modal-registro-metrica').classList.remove('hidden');
}
document.getElementById('form-registro-metrica').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true; btn.textContent = 'Salvando...';
    try {
        const res = await fetch('<?= APP_URL ?>/plano-de-acao/registrar-metrica', { method: 'POST', body: new FormData(this) });
        const data = await res.json();
        if (data.sucesso) { location.reload(); }
        else { alert(data.erro || 'Erro ao registrar.'); btn.disabled = false; btn.textContent = 'Salvar'; }
    } catch (err) { alert('Erro de conexão.'); btn.disabled = false; btn.textContent = 'Salvar'; }
});

// Renderizar gráficos das métricas quando a aba abrir (e no load).
window.renderCharts = function renderCharts() {
    if (typeof Chart === 'undefined') return;
    document.querySelectorAll('canvas[id^="chart-metrica-"]').forEach(cv => {
        if (cv.dataset.rendered) return;
        let labels = [], valores = [];
        try { labels = JSON.parse(cv.dataset.labels || '[]'); valores = JSON.parse(cv.dataset.valores || '[]'); } catch (e) {}
        if (!labels.length) return;
        cv.dataset.rendered = '1';
        new Chart(cv, {
            type: 'line',
            data: { labels: labels, datasets: [{ label: cv.dataset.nome || 'Valor', data: valores, borderColor: '#1E3A5F', backgroundColor: 'rgba(30,58,95,0.1)', fill: true, tension: 0.3, pointRadius: 3 }] },
            options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: false } } }
        });
    });
}
document.addEventListener('DOMContentLoaded', () => setTimeout(window.renderCharts, 300));
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
