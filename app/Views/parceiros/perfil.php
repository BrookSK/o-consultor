<?php $tituloPagina = $dados['parceiro']['nome']; ?>
<?php ob_start(); ?>
<?php $p = $dados['parceiro']; ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li><a href="<?= APP_URL ?>/parceiros" class="hover:text-primary">Parceiros</a></li>
        <li>/</li>
        <li class="font-medium text-primary"><?= htmlspecialchars($p['nome']) ?></li>
    </ol>
</nav>

<!-- Header -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
    <div class="flex flex-col md:flex-row items-start justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-primary text-white flex items-center justify-center text-2xl font-bold"><?= strtoupper(substr($p['nome'], 0, 1)) ?></div>
            <div>
                <h1 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($p['nome']) ?></h1>
                <p class="text-sm text-gray-500"><?= htmlspecialchars($p['categoria']) ?> • <?= htmlspecialchars(ucfirst($p['status'])) ?></p>
                <div class="flex items-center gap-1 mt-1">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                    <svg class="w-4 h-4 <?= $s <= round($p['avaliacao']) ? 'text-yellow-400' : 'text-gray-200' ?>" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    <?php endfor; ?>
                    <span class="text-sm text-gray-500 ml-1"><?= $p['avaliacao'] ?>/5</span>
                </div>
            </div>
        </div>
        <button onclick="document.getElementById('modal-solicitar').classList.remove('hidden')" class="bg-accent text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-orange-700 transition">📩 Solicitar Parceiro</button>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <!-- Sobre -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-800 mb-3">Sobre</h3>
            <p class="text-sm text-gray-600"><?= htmlspecialchars($p['sobre']) ?></p>
            <div class="flex flex-wrap gap-2 mt-4">
                <?php foreach ($p['especialidades'] as $esp): ?>
                <span class="px-3 py-1 bg-primary/10 text-primary rounded-full text-xs font-medium"><?= htmlspecialchars($esp) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <!-- Portfólio -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-800 mb-3">Portfólio</h3>
            <div class="space-y-3">
                <?php foreach ($p['portfolio'] as $proj): ?>
                <div class="border-l-4 border-l-primary/30 pl-4 py-2">
                    <p class="text-sm font-medium text-gray-800"><?= htmlspecialchars($proj['titulo']) ?></p>
                    <p class="text-xs text-green-700">📈 <?= htmlspecialchars($proj['resultado']) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <!-- Avaliações -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 h-fit">
        <h3 class="font-semibold text-gray-800 mb-3">Avaliações</h3>
        <div class="space-y-4">
            <?php foreach ($p['avaliacoes'] as $av): ?>
            <div class="border-b border-gray-100 pb-3">
                <div class="flex items-center gap-1 mb-1">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                    <svg class="w-3 h-3 <?= $s <= $av['nota'] ? 'text-yellow-400' : 'text-gray-200' ?>" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    <?php endfor; ?>
                    <span class="text-xs text-gray-400 ml-1"><?= date('d/m/Y', strtotime($av['data'])) ?></span>
                </div>
                <p class="text-xs text-gray-600">"<?= htmlspecialchars($av['comentario']) ?>"</p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Modal Solicitar -->
<div id="modal-solicitar" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Solicitar Parceiro</h3>
        <form id="form-solicitar" class="space-y-3">
            <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
            <input type="hidden" name="parceiro_id" value="<?= $p['id'] ?>">
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Sua empresa</label><input type="text" value="<?= htmlspecialchars(Auth::usuario()['nome'] ?? '') ?>" disabled class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-gray-50"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Necessidade *</label><textarea name="necessidade" required rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none" placeholder="Descreva o que precisa..."></textarea></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Urgência</label><select name="urgencia" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"><option>Normal</option><option>Urgente</option><option>Baixa</option></select></div>
            <div class="flex gap-2 pt-2">
                <button type="button" onclick="document.getElementById('modal-solicitar').classList.add('hidden')" class="flex-1 border border-gray-300 py-2 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="flex-1 bg-primary text-white py-2 rounded-lg text-sm font-medium hover:bg-primary-700">Enviar</button>
            </div>
        </form>
    </div>
</div>
<script>
document.getElementById('form-solicitar').addEventListener('submit', async function(e) {
    e.preventDefault();
    const res = await fetch('<?= APP_URL ?>/parceiros/solicitar', { method:'POST', body: new FormData(this) });
    const data = await res.json();
    if (data.sucesso) { document.getElementById('modal-solicitar').classList.add('hidden'); alert(data.mensagem); }
});
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
