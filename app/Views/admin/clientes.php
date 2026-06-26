<?php $tituloPagina = 'Clientes'; ?>
<?php ob_start(); ?>

<nav class="mb-6"><ol class="flex items-center text-sm text-gray-500 gap-2"><li><a href="<?= APP_URL ?>/admin" class="hover:text-primary">Admin</a></li><li>/</li><li class="font-medium text-primary">Clientes</li></ol></nav>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Gestão de Clientes</h1>
    <button class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-700">+ Cadastrar Cliente</button>
</div>

<div class="flex flex-wrap gap-2 mb-4">
    <select class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs outline-none"><option>Todos setores</option><option>Tecnologia</option><option>Varejo</option><option>Alimentação</option><option>Construção</option></select>
    <select class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs outline-none"><option>Todos status</option><option>Ativo</option><option>Pausado</option><option>Concluído</option></select>
    <select class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs outline-none"><option>Todos consultores</option><option>João Consultor</option></select>
</div>

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b"><tr>
                <th class="text-left px-4 py-3 font-medium text-gray-500">Empresa</th>
                <th class="px-4 py-3 font-medium text-gray-500">Setor</th>
                <th class="px-4 py-3 font-medium text-gray-500">Consultor</th>
                <th class="px-4 py-3 font-medium text-gray-500">Status</th>
                <th class="px-4 py-3 font-medium text-gray-500">MRR</th>
                <th class="px-4 py-3 font-medium text-gray-500">Maturidade</th>
                <th class="px-4 py-3 font-medium text-gray-500">Desde</th>
                <th class="px-4 py-3 font-medium text-gray-500">Ações</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-100">
            <?php foreach ($dados['clientes'] as $c):
                $stBadge = match($c['status']) { 'ativo' => 'bg-green-100 text-green-700', 'pausado' => 'bg-yellow-100 text-yellow-700', 'concluido' => 'bg-blue-100 text-blue-700', default => 'bg-gray-100 text-gray-600' };
                $matCor = match($c['maturidade']) { 4 => 'bg-[#1E3A5F] text-white', 3 => 'bg-green-100 text-green-800', 2 => 'bg-yellow-100 text-yellow-800', default => 'bg-red-100 text-red-800' };
                $matLabel = match($c['maturidade']) { 4 => 'Excelência', 3 => 'Crescimento', 2 => 'Desenvolvimento', default => 'Inicial' };
            ?>
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars($c['nome']) ?></td>
                <td class="px-4 py-3 text-center text-gray-600 text-xs"><?= htmlspecialchars($c['setor']) ?></td>
                <td class="px-4 py-3 text-center text-gray-600 text-xs"><?= htmlspecialchars($c['consultor']) ?></td>
                <td class="px-4 py-3 text-center"><span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $stBadge ?>"><?= ucfirst($c['status']) ?></span></td>
                <td class="px-4 py-3 text-center font-medium text-gray-800"><?= $c['mrr'] ?></td>
                <td class="px-4 py-3 text-center"><span class="px-2 py-0.5 rounded-full text-xs font-bold <?= $matCor ?>">N<?= $c['maturidade'] ?> <?= $matLabel ?></span></td>
                <td class="px-4 py-3 text-center text-gray-400 text-xs"><?= date('d/m/Y', strtotime($c['criado_em'])) ?></td>
                <td class="px-4 py-3 text-center"><button class="text-xs text-primary hover:underline">Ver perfil</button></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
