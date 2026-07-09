<?php $tituloPagina = 'Máquina de Conteúdo'; ?>
<?php ob_start(); ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Máquina de Conteúdo</li>
    </ol>
</nav>

<div class="flex items-center justify-between mb-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">🤖 Máquina de Conteúdo</h1>
        <p class="text-gray-500 mt-1">Geração de conteúdo com IA para suas marcas.</p>
    </div>
    <?php if (Auth::temAlgumPerfil([Auth::ADMIN_HOLDING, Auth::CONSULTOR_INTERNO])): ?>
    <a href="<?= APP_URL ?>/maquina-de-conteudo/nova-marca" class="bg-accent text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-orange-700 transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Cadastrar Nova Marca
    </a>
    <?php endif; ?>
</div>

<!-- Grid de Marcas -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($dados['marcas'] as $marca): ?>
    <div class="relative bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition" data-marca-id="<?= (int) $marca['id'] ?>">
        <?php if (Auth::temAlgumPerfil([Auth::ADMIN_HOLDING, Auth::CONSULTOR_INTERNO])): ?>
        <button type="button" onclick="excluirMarca(<?= (int) $marca['id'] ?>, '<?= htmlspecialchars(addslashes($marca['nome']), ENT_QUOTES) ?>', this)"
                class="absolute top-3 right-3 text-gray-300 hover:text-red-600 text-sm z-10" title="Excluir marca">🗑️</button>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/maquina-de-conteudo/marca?id=<?= $marca['id'] ?>" class="block">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 rounded-full bg-primary text-white flex items-center justify-center text-lg font-bold"><?= strtoupper(substr($marca['nome'], 0, 1)) ?></div>
                <div>
                    <h3 class="font-semibold text-gray-800"><?= htmlspecialchars($marca['nome']) ?></h3>
                    <p class="text-xs text-gray-400"><?= htmlspecialchars($marca['nicho']) ?></p>
                </div>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-xs text-gray-400"><?= isset($marca['atualizado_em']) && $marca['atualizado_em'] ? 'Último: ' . date('d/m/Y', strtotime($marca['atualizado_em'])) : 'Nenhum conteúdo' ?></span>
                <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= ($marca['brand_book_criado'] ?? false) ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?>">
                    <?= ($marca['brand_book_criado'] ?? false) ? '✓ Brand Book criado' : '○ Pendente' ?>
                </span>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<?php if (empty($dados['marcas'])): ?>
<div class="bg-white rounded-lg border border-gray-200 p-8 text-center text-gray-500 text-sm">
    Nenhuma marca cadastrada ainda. Clique em "Cadastrar Nova Marca" para começar.
</div>
<?php endif; ?>

<script>
async function excluirMarca(id, nome, btn) {
    if (!confirm('Excluir a marca "' + nome + '"? Esta ação não pode ser desfeita.')) return;
    if (btn) btn.disabled = true;

    const fd = new FormData();
    fd.append('csrf_token', '<?= Csrf::token() ?>');
    fd.append('marca_id', id);

    try {
        const res = await fetch('<?= APP_URL ?>/maquina-de-conteudo/excluir-marca', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.sucesso) {
            const card = document.querySelector('[data-marca-id="' + id + '"]');
            if (card) card.remove();
            if (typeof Toast !== 'undefined') Toast.sucesso(data.mensagem); else alert(data.mensagem);
        } else {
            if (btn) btn.disabled = false;
            const msg = data.erro || 'Erro ao excluir marca.';
            if (typeof Toast !== 'undefined') Toast.erro(msg); else alert(msg);
        }
    } catch (e) {
        if (btn) btn.disabled = false;
        alert('Erro de conexão ao excluir marca.');
    }
}
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
