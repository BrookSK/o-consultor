<?php $tituloPagina = 'Diagnósticos'; ?>
<?php ob_start(); ?>

<!-- Breadcrumb -->
<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Diagnósticos</li>
    </ol>
</nav>

<!-- Header -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-8 gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Diagnósticos Empresariais</h1>
        <p class="text-gray-500 mt-1">Avaliação de maturidade das empresas clientes.</p>
    </div>
    <div class="flex gap-3">
        <a href="<?= APP_URL ?>/diagnostico/wizard"
           class="bg-primary text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-primary-700 transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            Wizard Diagnóstico
        </a>
    </div>
</div>

<!-- Filtros -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
    <div class="flex flex-wrap gap-3">
        <select class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
            <option value="">Todos os setores</option>
            <option>Tecnologia</option>
            <option>Varejo</option>
            <option>Alimentação</option>
            <option>Construção</option>
            <option>Serviços</option>
        </select>
        <select class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
            <option value="">Todos os status</option>
            <option>Em andamento</option>
            <option>Concluído</option>
        </select>
        <select class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
            <option value="">Todas as maturidades</option>
            <option>Nível 1 — Inicial</option>
            <option>Nível 2 — Desenvolvimento</option>
            <option>Nível 3 — Crescimento</option>
            <option>Nível 4 — Excelência</option>
        </select>
        <input type="text" placeholder="Buscar empresa..." 
               class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none w-48">
    </div>
</div>

<!-- Tabela de Diagnósticos -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-6 py-3 font-medium text-gray-500 cursor-pointer hover:text-primary">Empresa ↕</th>
                    <th class="text-left px-6 py-3 font-medium text-gray-500">Setor</th>
                    <th class="text-left px-6 py-3 font-medium text-gray-500">Responsável</th>
                    <th class="text-left px-6 py-3 font-medium text-gray-500 cursor-pointer hover:text-primary">Maturidade ↕</th>
                    <th class="text-left px-6 py-3 font-medium text-gray-500">Status</th>
                    <th class="text-left px-6 py-3 font-medium text-gray-500 cursor-pointer hover:text-primary">Data ↕</th>
                    <th class="text-left px-6 py-3 font-medium text-gray-500">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($dados['diagnosticos'] as $diag):
                    // Determinar cores e labels baseadas no score
                    switch($diag['score']) {
                        case 4:
                            $scoreCor = 'bg-[#1E3A5F] text-white';
                            $scoreLabel = 'Excelência';
                            break;
                        case 3:
                            $scoreCor = 'bg-green-100 text-green-800';
                            $scoreLabel = 'Crescimento';
                            break;
                        case 2:
                            $scoreCor = 'bg-yellow-100 text-yellow-800';
                            $scoreLabel = 'Desenvolvimento';
                            break;
                        default:
                            $scoreCor = 'bg-red-100 text-red-800';
                            $scoreLabel = 'Inicial';
                            break;
                    }
                    
                    // Determinar badge e label do status
                    if ($diag['status'] === 'concluido') {
                        $statusBadge = 'bg-green-100 text-green-700';
                        $statusLabel = 'Concluído';
                    } else {
                        $statusBadge = 'bg-blue-100 text-blue-700';
                        $statusLabel = 'Em andamento';
                    }
                ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4">
                        <p class="font-medium text-gray-800"><?= htmlspecialchars(isset($diag['empresa']) ? $diag['empresa'] : 'N/A') ?></p>
                    </td>
                    <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars(isset($diag['setor']) ? $diag['setor'] : 'N/A') ?></td>
                    <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars(isset($diag['responsavel']) ? $diag['responsavel'] : 'N/A') ?></td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold <?= $scoreCor ?>">
                            <span class="flex gap-0.5">
                                <?php for ($i = 1; $i <= 4; $i++): ?>
                                <span class="w-1.5 h-1.5 rounded-full <?= $i <= $diag['score'] ? 'bg-current' : 'bg-current opacity-30' ?>"></span>
                                <?php endfor; ?>
                            </span>
                            <?= $scoreLabel ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $statusBadge ?>"><?= $statusLabel ?></span>
                    </td>
                    <td class="px-6 py-4 text-gray-500"><?= date('d/m/Y', strtotime($diag['criado_em'])) ?></td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <a href="<?= APP_URL ?>/diagnostico/resultado/<?= $diag['id'] ?>" 
                               class="text-primary hover:underline text-sm font-medium">Ver →</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <!-- Paginação simples -->
    <div class="px-6 py-3 border-t border-gray-200 flex items-center justify-between text-sm text-gray-500">
        <span>Exibindo <?= count($dados['diagnosticos']) ?> diagnósticos</span>
        <div class="flex gap-1">
            <button class="px-3 py-1 bg-primary text-white rounded text-xs">1</button>
        </div>
    </div>
</div>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
