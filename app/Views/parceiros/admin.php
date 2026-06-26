<?php $tituloPagina = 'Gestão de Parceiros'; ?>
<?php ob_start(); ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li><a href="<?= APP_URL ?>/parceiros" class="hover:text-primary">Parceiros</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Gestão</li>
    </ol>
</nav>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Gestão de Parceiros</h1>
    <button onclick="alert('Formulário de cadastro de parceiro será implementado em breve. Por enquanto, entre em contato com o suporte.')" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-700">+ Cadastrar Parceiro</button>
</div>

<!-- Workflow -->
<div class="flex gap-4 mb-6 text-xs">
    <span class="px-3 py-1.5 bg-blue-100 text-blue-700 rounded-full font-medium">Cadastrado →</span>
    <span class="px-3 py-1.5 bg-yellow-100 text-yellow-700 rounded-full font-medium">Em avaliação →</span>
    <span class="px-3 py-1.5 bg-green-100 text-green-700 rounded-full font-medium">Homologado</span>
    <span class="px-3 py-1.5 bg-red-100 text-red-700 rounded-full font-medium">Suspenso</span>
</div>

<!-- Tabela -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b"><tr><th class="text-left px-4 py-3 font-medium text-gray-500">Parceiro</th><th class="px-4 py-3 font-medium text-gray-500">Categoria</th><th class="px-4 py-3 font-medium text-gray-500">Avaliação</th><th class="px-4 py-3 font-medium text-gray-500">Status</th><th class="px-4 py-3 font-medium text-gray-500">Ações</th></tr></thead>
        <tbody class="divide-y divide-gray-100">
            <?php foreach ($dados['parceiros'] as $p):
                $sCfg = match($p['status']) { 'homologado' => 'bg-green-100 text-green-700', 'em_avaliacao' => 'bg-yellow-100 text-yellow-700', default => 'bg-red-100 text-red-700' };
            ?>
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars($p['nome']) ?></td>
                <td class="px-4 py-3 text-center text-gray-600"><?= htmlspecialchars($p['categoria']) ?></td>
                <td class="px-4 py-3 text-center"><?= $p['avaliacao'] ?> ⭐</td>
                <td class="px-4 py-3 text-center"><span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $sCfg ?>"><?= ucfirst(str_replace('_', ' ', $p['status'])) ?></span></td>
                <td class="px-4 py-3 text-center">
                    <select onchange="atualizarStatus(<?= $p['id'] ?>, this.value)" class="text-xs border border-gray-200 rounded px-2 py-1 outline-none">
                        <option value="em_avaliacao" <?= $p['status'] === 'em_avaliacao' ? 'selected' : '' ?>>Em avaliação</option>
                        <option value="homologado" <?= $p['status'] === 'homologado' ? 'selected' : '' ?>>Homologado</option>
                        <option value="suspenso" <?= $p['status'] === 'suspenso' ? 'selected' : '' ?>>Suspenso</option>
                    </select>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Critérios de Homologação -->
<div class="mt-6 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <h3 class="font-semibold text-gray-800 mb-3">Critérios de Homologação</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" checked class="w-4 h-4 text-primary rounded"> Qualidade comprovada (portfólio)</label>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" checked class="w-4 h-4 text-primary rounded"> Metodologia compatível</label>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" checked class="w-4 h-4 text-primary rounded"> Transparência nos processos</label>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" class="w-4 h-4 text-primary rounded"> Acordo de parceria assinado</label>
    </div>
</div>

<script>
async function atualizarStatus(id, status) {
    const fd = new FormData();
    fd.append('csrf_token', '<?= Csrf::token() ?>');
    fd.append('parceiro_id', id);
    fd.append('status', status);
    await fetch('<?= APP_URL ?>/parceiros/status', { method:'POST', body:fd });
}
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
