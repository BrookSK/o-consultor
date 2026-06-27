<?php $tituloPagina = 'Gestão de Clientes'; ?>
<?php ob_start(); ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/admin" class="hover:text-primary">Admin</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Clientes</li>
    </ol>
</nav>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Gestão de Clientes</h1>
        <p class="text-sm text-gray-500 mt-1">Administre empresas clientes, consultores e status</p>
    </div>
    <a href="<?= APP_URL ?>/admin/clientes/novo" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-700 transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
        Cadastrar Cliente
    </a>
</div>

<!-- Filtros -->
<form method="GET" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Setor</label>
            <select name="setor" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                <option value="">Todos os setores</option>
                <?php foreach ($dados['setores'] as $setor): ?>
                <option value="<?= htmlspecialchars($setor) ?>" <?= ($dados['filtros']['setor'] === $setor) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($setor) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                <option value="">Todos os status</option>
                <option value="ativo" <?= ($dados['filtros']['status'] === 'ativo') ? 'selected' : '' ?>>✅ Ativo</option>
                <option value="pausado" <?= ($dados['filtros']['status'] === 'pausado') ? 'selected' : '' ?>>⏸️ Pausado</option>
                <option value="suspenso" <?= ($dados['filtros']['status'] === 'suspenso') ? 'selected' : '' ?>>⏹️ Suspenso</option>
                <option value="cancelado" <?= ($dados['filtros']['status'] === 'cancelado') ? 'selected' : '' ?>>❌ Cancelado</option>
            </select>
        </div>
        
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Consultor</label>
            <select name="consultor_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                <option value="">Todos os consultores</option>
                <?php foreach ($dados['consultores'] as $consultor): ?>
                <option value="<?= $consultor['id'] ?>" <?= ($dados['filtros']['consultor_id'] === (int)$consultor['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($consultor['nome']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="flex items-end gap-2">
            <button type="submit" class="flex-1 bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-700 transition">
                🔍 Filtrar
            </button>
            <?php if (array_filter($dados['filtros'])): ?>
            <a href="<?= APP_URL ?>/admin/clientes" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition">
                Limpar
            </a>
            <?php endif; ?>
        </div>
    </div>
</form>

<!-- Lista de clientes -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <?php if (!empty($dados['clientes'])): ?>
    
    <!-- Header da tabela -->
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="text-left px-4 py-3 font-medium text-gray-500">Empresa</th>
                    <th class="px-4 py-3 font-medium text-gray-500">Setor</th>
                    <th class="px-4 py-3 font-medium text-gray-500">Consultor</th>
                    <th class="px-4 py-3 font-medium text-gray-500">Status</th>
                    <th class="px-4 py-3 font-medium text-gray-500">MRR</th>
                    <th class="px-4 py-3 font-medium text-gray-500">Módulos</th>
                    <th class="px-4 py-3 font-medium text-gray-500">Desde</th>
                    <th class="px-4 py-3 font-medium text-gray-500">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            <?php foreach ($dados['clientes'] as $cliente):
                // Configurar badges de status
                $statusConfig = match($cliente['status']) {
                    'ativo' => ['bg-green-100 text-green-700', '✅'],
                    'pausado' => ['bg-yellow-100 text-yellow-700', '⏸️'],
                    'suspenso' => ['bg-orange-100 text-orange-700', '⏹️'],
                    'cancelado' => ['bg-red-100 text-red-700', '❌'],
                    default => ['bg-gray-100 text-gray-600', '❓']
                };
                
                // Calcular maturidade baseada no score
                $score = (int) ($cliente['score_maturidade'] ?? 0);
                if ($score >= 80) {
                    $maturidade = ['nivel' => 4, 'label' => 'Excelência', 'cor' => 'bg-[#1E3A5F] text-white'];
                } elseif ($score >= 60) {
                    $maturidade = ['nivel' => 3, 'label' => 'Crescimento', 'cor' => 'bg-green-100 text-green-800'];
                } elseif ($score >= 40) {
                    $maturidade = ['nivel' => 2, 'label' => 'Desenvolvimento', 'cor' => 'bg-yellow-100 text-yellow-800'];
                } else {
                    $maturidade = ['nivel' => 1, 'label' => 'Inicial', 'cor' => 'bg-red-100 text-red-800'];
                }
                
                $mrr = $cliente['mrr'] ? 'R$ ' . number_format($cliente['mrr'], 2, ',', '.') : 'Não informado';
            ?>
            <tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-3">
                    <div>
                        <p class="font-medium text-gray-800"><?= htmlspecialchars($cliente['nome']) ?></p>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars($cliente['responsavel_nome'] ?? 'Sem responsável') ?></p>
                        <?php if ($cliente['cnpj']): ?>
                        <p class="text-xs text-gray-400">CNPJ: <?= htmlspecialchars($cliente['cnpj']) ?></p>
                        <?php endif; ?>
                    </div>
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="text-xs text-gray-600"><?= htmlspecialchars($cliente['segmento'] ?? 'N/I') ?></span>
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="text-xs text-gray-600"><?= htmlspecialchars($cliente['consultor_nome'] ?? 'Não atribuído') ?></span>
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $statusConfig[0] ?>">
                        <?= $statusConfig[1] ?> <?= ucfirst($cliente['status']) ?>
                    </span>
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="font-medium text-gray-800"><?= $mrr ?></span>
                </td>
                <td class="px-4 py-3 text-center">
                    <div class="flex items-center justify-center gap-1">
                        <span class="text-xs bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded"><?= $cliente['total_diagnosticos'] ?> D</span>
                        <span class="text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded"><?= $cliente['total_planos'] ?> P</span>
                        <span class="text-xs bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded"><?= $cliente['total_sops'] ?> S</span>
                    </div>
                    <?php if ($score > 0): ?>
                    <div class="mt-1">
                        <span class="px-1.5 py-0.5 rounded-full text-xs font-bold <?= $maturidade['cor'] ?>">
                            N<?= $maturidade['nivel'] ?> <?= $maturidade['label'] ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="text-xs text-gray-400"><?= date('d/m/Y', strtotime($cliente['criado_em'])) ?></span>
                </td>
                <td class="px-4 py-3 text-center">
                    <a href="<?= APP_URL ?>/admin/clientes/perfil/<?= $cliente['id'] ?>" 
                       class="text-xs text-primary hover:underline font-medium">
                        Ver Perfil
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php else: ?>
    
    <!-- Empty state -->
    <div class="text-center py-12">
        <div class="text-6xl mb-4">🏢</div>
        <h3 class="text-lg font-medium text-gray-800 mb-2">Nenhum cliente encontrado</h3>
        <p class="text-sm text-gray-500 mb-6">
            <?php if (array_filter($dados['filtros'])): ?>
            Nenhum cliente corresponde aos filtros aplicados.
            <?php else: ?>
            Comece cadastrando seu primeiro cliente.
            <?php endif; ?>
        </p>
        
        <?php if (!array_filter($dados['filtros'])): ?>
        <a href="<?= APP_URL ?>/admin/clientes/novo" 
           class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-700 transition gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Cadastrar Primeiro Cliente
        </a>
        <?php endif; ?>
    </div>
    
    <?php endif; ?>
</div>

<!-- Estatísticas resumidas -->
<?php if (!empty($dados['clientes'])): ?>
<div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
    <?php
    $totalAtivos = count(array_filter($dados['clientes'], fn($c) => $c['status'] === 'ativo'));
    $totalMrr = array_sum(array_column($dados['clientes'], 'mrr'));
    $mediaScore = $dados['clientes'] ? array_sum(array_column($dados['clientes'], 'score_maturidade')) / count($dados['clientes']) : 0;
    ?>
    
    <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
        <p class="text-2xl font-bold text-green-600"><?= $totalAtivos ?></p>
        <p class="text-xs text-gray-500">Clientes Ativos</p>
    </div>
    
    <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
        <p class="text-2xl font-bold text-blue-600">R$ <?= number_format($totalMrr, 0, ',', '.') ?></p>
        <p class="text-xs text-gray-500">MRR Total</p>
    </div>
    
    <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
        <p class="text-2xl font-bold text-purple-600"><?= number_format($mediaScore, 0) ?>%</p>
        <p class="text-xs text-gray-500">Maturidade Média</p>
    </div>
    
    <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
        <p class="text-2xl font-bold text-orange-600"><?= count($dados['clientes']) ?></p>
        <p class="text-xs text-gray-500">Total de Clientes</p>
    </div>
</div>
<?php endif; ?>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
