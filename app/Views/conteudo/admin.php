<?php $tituloPagina = 'Gerenciar Conteúdo'; ?>
<?php ob_start(); ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li><a href="<?= APP_URL ?>/central-de-conteudo" class="hover:text-primary">Central de Conteúdo</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Gerenciar</li>
    </ol>
</nav>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Gestão de Conteúdo</h1>
    <div class="flex gap-2">
        <button onclick="buscarTodos()" class="px-4 py-2 bg-accent text-white rounded-lg text-sm font-medium hover:bg-orange-700">🔍 Buscar para todos</button>
        <button onclick="alert('Formulário para adicionar case real será disponibilizado em breve.')" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-700">+ Adicionar Case</button>
    </div>
</div>

<!-- Notícias gerenciadas -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800">📰 Notícias (<?= count($dados['noticias']) ?>)</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr><th class="text-left px-4 py-2 font-medium text-gray-500">Título</th><th class="px-4 py-2 font-medium text-gray-500">Fonte</th><th class="px-4 py-2 font-medium text-gray-500">Data</th><th class="px-4 py-2 font-medium text-gray-500">Status</th><th class="px-4 py-2"></th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($dados['noticias'] as $n): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars($n['titulo']) ?></td>
                    <td class="px-4 py-3 text-gray-500 text-center"><?= htmlspecialchars($n['fonte']) ?></td>
                    <td class="px-4 py-3 text-gray-500 text-center"><?= date('d/m', strtotime($n['data'])) ?></td>
                    <td class="px-4 py-3 text-center"><span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Publicado</span></td>
                    <td class="px-4 py-3 text-right"><button onclick="alert('Notícia arquivada!')" class="text-xs text-red-500 hover:underline">Arquivar</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Cases gerenciados -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800">📋 Casos Reais (<?= count($dados['casos']) ?>)</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr><th class="text-left px-4 py-2 font-medium text-gray-500">Título</th><th class="px-4 py-2 font-medium text-gray-500">Setor</th><th class="px-4 py-2 font-medium text-gray-500">Exclusivo</th><th class="px-4 py-2"></th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($dados['casos'] as $c): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars($c['titulo']) ?></td>
                    <td class="px-4 py-3 text-gray-500 text-center"><?= htmlspecialchars($c['setor']) ?></td>
                    <td class="px-4 py-3 text-center"><?= $c['exclusivo'] ? '⭐' : '—' ?></td>
                    <td class="px-4 py-3 text-right"><button onclick="alert('Edição de case será disponibilizada em breve.')" class="text-xs text-primary hover:underline">Editar</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
async function buscarTodos() {
    const fd = new FormData();
    fd.append('csrf_token', '<?= Csrf::token() ?>');
    const res = await fetch('<?= APP_URL ?>/central-de-conteudo/buscar-agora', { method:'POST', body:fd });
    const data = await res.json();
    alert(data.mensagem || 'Feito.');
}
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
