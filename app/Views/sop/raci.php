<?php $tituloPagina = 'Matriz RACI'; ?>
<?php ob_start(); ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li><a href="<?= APP_URL ?>/manual-operacional" class="hover:text-primary">Manual Operacional</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Matriz RACI</li>
    </ol>
</nav>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Matriz RACI</h1>
    <p class="text-gray-500 mt-1">Responsável (R), Aprovador (A), Consultado (C), Informado (I), Substituto (S)</p>
</div>

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 font-medium text-gray-500 sticky left-0 bg-gray-50">SOP</th>
                    <?php foreach ($dados['cargos'] as $cargo): ?>
                    <th class="px-3 py-3 font-medium text-gray-500 text-center text-xs"><?= htmlspecialchars($cargo) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($dados['sops'] as $sop): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 sticky left-0 bg-white">
                        <span class="text-xs font-mono text-gray-400"><?= htmlspecialchars($sop['id']) ?></span>
                        <p class="text-sm font-medium text-gray-800"><?= htmlspecialchars($sop['nome']) ?></p>
                    </td>
                    <?php foreach ($sop['raci'] as $valor):
                        $corRaci = match($valor) {
                            'R' => 'bg-blue-600 text-white',
                            'A' => 'bg-red-600 text-white',
                            'C' => 'bg-yellow-500 text-white',
                            'I' => 'bg-green-600 text-white',
                            'S' => 'bg-purple-600 text-white',
                            default => 'bg-gray-100 text-gray-300',
                        };
                    ?>
                    <td class="px-3 py-3 text-center">
                        <span class="inline-flex w-7 h-7 rounded-full items-center justify-center text-xs font-bold <?= $corRaci ?>"><?= $valor ?></span>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Legenda -->
<div class="mt-4 flex flex-wrap gap-4 text-xs">
    <span class="flex items-center gap-1"><span class="w-5 h-5 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold">R</span> Responsável</span>
    <span class="flex items-center gap-1"><span class="w-5 h-5 rounded-full bg-red-600 text-white flex items-center justify-center font-bold">A</span> Aprovador</span>
    <span class="flex items-center gap-1"><span class="w-5 h-5 rounded-full bg-yellow-500 text-white flex items-center justify-center font-bold">C</span> Consultado</span>
    <span class="flex items-center gap-1"><span class="w-5 h-5 rounded-full bg-green-600 text-white flex items-center justify-center font-bold">I</span> Informado</span>
    <span class="flex items-center gap-1"><span class="w-5 h-5 rounded-full bg-purple-600 text-white flex items-center justify-center font-bold">S</span> Substituto</span>
</div>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
