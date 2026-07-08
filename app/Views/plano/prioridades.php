<?php $tituloPagina = 'Prioridades do Plano'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $tituloPagina ?> — O Consultor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{colors:{primary:{DEFAULT:'#1E3A5F',700:'#162D4A'},accent:'#E07B00'}}}}</script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body{font-family:'Inter',sans-serif;}</style>
</head>
<body class="bg-gray-50 min-h-screen">

<div class="max-w-4xl mx-auto p-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Prioridades sugeridas</h1>
                <p class="text-gray-500">Step 2 de 3 — Revise, edite e selecione as prioridades</p>
            </div>
            <div class="text-right">
                <div class="text-sm text-gray-500 mb-1">Progresso</div>
                <div class="w-32 bg-gray-200 rounded-full h-2">
                    <div class="bg-primary h-2 rounded-full" style="width: 66%"></div>
                </div>
                <div class="text-xs text-gray-400 mt-1">66% concluído</div>
            </div>
        </div>
        <div class="text-sm text-gray-600">
            <span class="font-semibold text-gray-800"><?= htmlspecialchars($dados['plano']['titulo']) ?></span>
            <?php if (!empty($dados['plano']['objetivo'])): ?>
                — <?= htmlspecialchars($dados['plano']['objetivo']) ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($dados['prioridades'])): ?>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center text-gray-500">
        <p>Não foi possível gerar prioridades automaticamente. Volte e tente novamente.</p>
        <a href="<?= APP_URL ?>/plano-de-acao" class="inline-block mt-4 px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">← Voltar</a>
    </div>
    <?php else: ?>

    <form id="formPrioridades">
        <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
        <input type="hidden" name="plano_id" value="<?= (int) $dados['plano']['id'] ?>">

        <div class="flex items-center justify-between mb-3">
            <p class="text-sm text-gray-500">Selecionadas: <span id="contadorSel" class="font-semibold text-primary">0</span> de <?= count($dados['prioridades']) ?></p>
            <button type="button" onclick="marcarTodas()" class="text-sm text-primary hover:underline">Selecionar todas</button>
        </div>

        <div class="space-y-3">
            <?php foreach ($dados['prioridades'] as $p):
                $impactoBadge = match($p['impacto']) {
                    'alto' => 'bg-red-100 text-red-700',
                    'baixo' => 'bg-gray-100 text-gray-600',
                    default => 'bg-yellow-100 text-yellow-700',
                };
                $urgenciaBadge = match($p['urgencia']) {
                    'alta' => 'bg-red-100 text-red-700',
                    'baixa' => 'bg-gray-100 text-gray-600',
                    default => 'bg-yellow-100 text-yellow-700',
                };
            ?>
            <div class="bg-white rounded-lg border border-gray-200 p-4 prio-card">
                <div class="flex items-start gap-3">
                    <input type="checkbox" name="prioridades[]" value="<?= (int) $p['id'] ?>" checked
                           onchange="atualizarContador()"
                           class="chk-prio mt-1 w-4 h-4 text-primary rounded border-gray-300 focus:ring-primary">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <span class="text-xs font-semibold text-primary bg-primary/10 px-2 py-0.5 rounded"><?= htmlspecialchars($p['area']) ?></span>
                            <span class="text-xs font-medium px-2 py-0.5 rounded <?= $impactoBadge ?>">Impacto: <?= ucfirst($p['impacto']) ?></span>
                            <span class="text-xs font-medium px-2 py-0.5 rounded <?= $urgenciaBadge ?>">Urgência: <?= ucfirst($p['urgencia']) ?></span>
                        </div>
                        <p class="text-sm text-gray-500 mb-2"><b>Problema:</b> <?= htmlspecialchars($p['descricao_problema']) ?></p>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Ação sugerida (editável)</label>
                        <textarea name="prioridades_editadas[<?= (int) $p['id'] ?>]" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-primary"><?= htmlspecialchars($p['acao_sugerida']) ?></textarea>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="flex justify-between pt-6 mt-4 border-t border-gray-100">
            <a href="<?= APP_URL ?>/plano-de-acao" class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">← Cancelar</a>
            <button type="button" id="btnConfirmar" onclick="confirmarPrioridades()"
                    class="px-8 py-3 bg-primary text-white rounded-lg hover:bg-primary-700 transition font-semibold">
                Próximo: Criar Tarefas →
            </button>
        </div>
    </form>

    <?php endif; ?>
</div>

<script>
function atualizarContador() {
    const total = document.querySelectorAll('.chk-prio:checked').length;
    document.getElementById('contadorSel').textContent = total;
}
function marcarTodas() {
    document.querySelectorAll('.chk-prio').forEach(c => c.checked = true);
    atualizarContador();
}
document.addEventListener('DOMContentLoaded', atualizarContador);

async function confirmarPrioridades() {
    const selecionadas = document.querySelectorAll('.chk-prio:checked').length;
    if (selecionadas === 0) { alert('Selecione ao menos uma prioridade.'); return; }

    const btn = document.getElementById('btnConfirmar');
    btn.disabled = true;
    btn.textContent = 'Salvando...';

    try {
        const formData = new FormData(document.getElementById('formPrioridades'));
        const resp = await fetch('<?= APP_URL ?>/plano-de-acao/confirmar-prioridades', { method: 'POST', body: formData });
        const data = await resp.json();
        if (data.sucesso) {
            window.location.href = data.redirect;
        } else {
            alert(data.mensagem || 'Erro ao confirmar prioridades.');
            btn.disabled = false;
            btn.textContent = 'Próximo: Criar Tarefas →';
        }
    } catch (e) {
        alert('Erro de conexão. Tente novamente.');
        btn.disabled = false;
        btn.textContent = 'Próximo: Criar Tarefas →';
    }
}
</script>

</body>
</html>
