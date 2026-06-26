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
    <a href="<?= APP_URL ?>/diagnostico/novo"
       class="bg-accent text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-orange-700 transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Novo Diagnóstico
    </a>
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
                    $scoreCor = match($diag['score']) {
                        4 => 'bg-[#1E3A5F] text-white',
                        3 => 'bg-green-100 text-green-800',
                        2 => 'bg-yellow-100 text-yellow-800',
                        default => 'bg-red-100 text-red-800',
                    };
                    $scoreLabel = match($diag['score']) {
                        4 => 'Excelência',
                        3 => 'Crescimento',
                        2 => 'Desenvolvimento',
                        default => 'Inicial',
                    };
                    $statusBadge = match($diag['status']) {
                        'concluido' => 'bg-green-100 text-green-700',
                        default => 'bg-blue-100 text-blue-700',
                    };
                    $statusLabel = match($diag['status']) {
                        'concluido' => 'Concluído',
                        default => 'Em andamento',
                    };
                ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4">
                        <p class="font-medium text-gray-800"><?= htmlspecialchars($diag['empresa']) ?></p>
                    </td>
                    <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars($diag['setor']) ?></td>
                    <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars($diag['responsavel']) ?></td>
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
                        <a href="<?= APP_URL ?>/diagnostico/resultado" class="text-primary hover:underline text-sm font-medium">Ver →</a>
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
            <button class="px-3 py-1 bg-gray-100 rounded text-xs hover:bg-gray-200">2</button>
        </div>
    </div>
</div>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
