<?php $tituloPagina = 'Manual Operacional'; ?>
<?php ob_start(); ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Manual Operacional</li>
    </ol>
</nav>

<!-- Header + Links rápidos -->
<div class="flex flex-col lg:flex-row items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Manual Operacional</h1>
        <p class="text-gray-500 mt-1"><?= htmlspecialchars($dados['empresa']) ?> — Padrão <?= htmlspecialchars($dados['norma']) ?></p>
    </div>
    <div class="flex gap-2">
        <?php if (Auth::perfil() === 'ADMIN_HOLDING' || Auth::perfil() === 'CONSULTOR_INTERNO'): ?>
        <a href="<?= APP_URL ?>/sop/gerenciar?empresa_id=<?= Auth::empresa() ?>" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">⚙️ Gerenciar SOPs</a>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/manual-operacional/raci" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">📊 Matriz RACI</a>
        <a href="<?= APP_URL ?>/manual-operacional/kpis" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">📈 Painel KPIs</a>
        <a href="<?= APP_URL ?>/sop/exportar-todos-zip" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700">📦 Exportar Todos (ZIP)</a>
    </div>
</div>

<!-- Banner primeiro acesso -->
<div class="bg-gradient-to-r from-primary/5 to-accent/5 border border-primary/20 rounded-lg p-5 mb-6">
    <p class="text-sm text-gray-700">
        Com base no diagnóstico de <strong><?= htmlspecialchars($dados['empresa']) ?></strong>, a IA identificou os seguintes departamentos e SOPs necessários para o setor <strong><?= htmlspecialchars($dados['setor']) ?></strong>.
        Cada SOP será gerado <strong>individualmente</strong> quando você clicar em "Gerar". Revise, ajuste e aprove.
    </p>
</div>

<!-- Barra de Progresso Geral -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-8">
    <div class="flex items-center justify-between mb-2">
        <span class="text-sm font-medium text-gray-700"><?= $dados['aprovados'] ?> de <?= $dados['total_sops'] ?> SOPs aprovados</span>
        <span class="text-sm font-bold text-primary"><?= $dados['total_sops'] > 0 ? round(($dados['aprovados'] / $dados['total_sops']) * 100) : 0 ?>%</span>
    </div>
    <div class="w-full h-3 bg-gray-200 rounded-full overflow-hidden">
        <div class="h-full bg-green-500 rounded-full transition-all" style="width: <?= $dados['total_sops'] > 0 ? round(($dados['aprovados'] / $dados['total_sops']) * 100) : 0 ?>%"></div>
    </div>
    <?php if ($dados['aprovados'] === $dados['total_sops'] && $dados['total_sops'] > 0): ?>
    <p class="text-sm text-green-700 font-medium mt-2">✓ Manual Operacional completo — <?= htmlspecialchars($dados['empresa']) ?> tem todos os processos padronizados no padrão <?= htmlspecialchars($dados['setor']) ?>.</p>
    <?php endif; ?>
</div>

<!-- Cards por Departamento -->
<div class="space-y-6">
<?php foreach ($dados['departamentos'] as $dept):
    $aprovados = count(array_filter($dept['sops'], fn($s) => $s['status'] === 'aprovado'));
    $total = count($dept['sops']);
?>
<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="text-2xl"><?= $dept['icone'] ?></span>
            <div>
                <h3 class="font-semibold text-gray-800"><?= htmlspecialchars($dept['nome']) ?></h3>
                <p class="text-xs text-gray-400"><?= $aprovados ?> aprovados de <?= $total ?></p>
            </div>
        </div>
        <?php if ($aprovados === $total): ?>
        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">✓ Completo</span>
        <?php endif; ?>
    </div>
    <div class="p-4">
        <div class="space-y-2">
            <?php foreach ($dept['sops'] as $sop):
                $statusConfig = match($sop['status']) {
                    'aprovado' => ['badge' => 'bg-green-100 text-green-700', 'label' => '✓ Aprovado', 'btn' => false],
                    'gerado' => ['badge' => 'bg-blue-100 text-blue-700', 'label' => '● Gerado', 'btn' => false],
                    'em_revisao' => ['badge' => 'bg-yellow-100 text-yellow-700', 'label' => '◎ Em revisão', 'btn' => false],
                    default => ['badge' => 'bg-gray-100 text-gray-500', 'label' => '○ Não gerado', 'btn' => true],
                };
            ?>
            <div class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition border border-gray-100">
                <div class="flex items-center gap-3">
                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold text-gray-500 bg-gray-100"><?= htmlspecialchars($sop['id']) ?></span>
                    <span class="text-sm text-gray-800"><?= htmlspecialchars($sop['nome']) ?></span>
                    <?php if (!empty($sop['customizado'])): ?>
                    <span class="px-2 py-0.5 rounded text-[9px] font-bold text-purple-600 bg-purple-100">PERSONALIZADO</span>
                    <?php endif; ?>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium <?= $statusConfig['badge'] ?>"><?= $statusConfig['label'] ?></span>
                    <?php if ($sop['status'] === 'nao_gerado'): ?>
                    <button onclick="gerarSop('<?= htmlspecialchars($sop['id']) ?>', '<?= htmlspecialchars($sop['nome']) ?>')"
                            class="px-3 py-1.5 bg-accent text-white rounded-lg text-xs font-medium hover:bg-orange-700 transition">
                        🤖 Gerar SOP
                    </button>
                    <?php elseif ($sop['status'] !== 'aprovado'): ?>
                    <a href="<?= APP_URL ?>/sop/revisar?id=<?= urlencode($sop['id']) ?>" 
                       class="px-3 py-1.5 bg-primary text-white rounded-lg text-xs font-medium hover:bg-primary-700 transition">
                        Revisar →
                    </a>
                    <?php else: ?>
                    <a href="<?= APP_URL ?>/sop/ver/<?= urlencode($sop['id']) ?>" 
                       class="px-3 py-1.5 bg-green-600 text-white rounded-lg text-xs font-medium hover:bg-green-700 transition">
                        Ver SOP →
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<!-- Loading Modal -->
<div id="modal-gerando" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-8 text-center max-w-sm w-full shadow-xl">
        <div class="inline-block w-10 h-10 border-4 border-gray-200 border-t-accent rounded-full animate-spin mb-4"></div>
        <p class="text-sm font-medium text-gray-800" id="gerando-titulo">Gerando SOP...</p>
        <p class="text-xs text-gray-500 mt-1" id="gerando-subtitulo">Aplicando padrão ITIL v4 adaptado ao nível 3 da empresa...</p>
    </div>
</div>

<script>
async function gerarSop(sopId, sopNome) {
    const modal = document.getElementById('modal-gerando');
    document.getElementById('gerando-titulo').textContent = 'Gerando SOP: ' + sopNome;
    document.getElementById('gerando-subtitulo').textContent = 'Aplicando padrão <?= htmlspecialchars($dados['norma']) ?> adaptado ao nível <?= $dados['maturidade'] ?> da empresa...';
    modal.classList.remove('hidden');

    const formData = new FormData();
    formData.append('csrf_token', '<?= Csrf::token() ?>');
    formData.append('sop_id', sopId);
    formData.append('sop_nome', sopNome);

    try {
        const res = await fetch('<?= APP_URL ?>/sop/gerar', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.sucesso) {
            window.location.href = data.redirect;
        } else {
            modal.classList.add('hidden');
            alert(data.erro || 'Erro ao gerar SOP.');
        }
    } catch (e) {
        modal.classList.add('hidden');
        alert('Erro de conexão.');
    }
}
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
