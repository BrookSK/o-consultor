<?php $tituloPagina = 'Criar Tarefas do Plano'; ?>
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
                <h1 class="text-2xl font-bold text-gray-800">Criar tarefas</h1>
                <p class="text-gray-500">Step 3 de 3 — Defina responsável e prazo para cada prioridade</p>
            </div>
            <div class="text-right">
                <div class="text-sm text-gray-500 mb-1">Progresso</div>
                <div class="w-32 bg-gray-200 rounded-full h-2">
                    <div class="bg-primary h-2 rounded-full" style="width: 100%"></div>
                </div>
                <div class="text-xs text-gray-400 mt-1">100% concluído</div>
            </div>
        </div>
        <div class="text-sm text-gray-600">
            <span class="font-semibold text-gray-800"><?= htmlspecialchars($dados['plano']['titulo']) ?></span>
        </div>
    </div>

    <form id="formTarefas">
        <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
        <input type="hidden" name="plano_id" value="<?= (int) $dados['plano']['id'] ?>">

        <div class="space-y-4">
            <?php foreach ($dados['prioridades'] as $p): ?>
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-xs font-semibold text-primary bg-primary/10 px-2 py-0.5 rounded"><?= htmlspecialchars($p['area']) ?></span>
                    <span class="text-xs text-gray-400"><?= htmlspecialchars($p['descricao_problema']) ?></span>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Título da tarefa *</label>
                        <input type="text" name="tarefas[<?= (int) $p['id'] ?>][titulo]" required
                               value="<?= htmlspecialchars($p['acao_sugerida']) ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Descrição</label>
                        <textarea name="tarefas[<?= (int) $p['id'] ?>][descricao]" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-primary"
                                  placeholder="Detalhes da execução (opcional)"></textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Área</label>
                            <input type="text" name="tarefas[<?= (int) $p['id'] ?>][area]"
                                   value="<?= htmlspecialchars($p['area']) ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Responsável</label>
                            <input type="text" name="tarefas[<?= (int) $p['id'] ?>][responsavel]"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-primary"
                                   placeholder="Nome">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Prazo</label>
                            <input type="date" name="tarefas[<?= (int) $p['id'] ?>][prazo]"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Prioridade</label>
                            <select name="tarefas[<?= (int) $p['id'] ?>][prioridade]"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-primary">
                                <option value="alta" <?= $p['urgencia'] === 'alta' ? 'selected' : '' ?>>Alta</option>
                                <option value="media" <?= $p['urgencia'] === 'media' ? 'selected' : '' ?>>Média</option>
                                <option value="baixa" <?= $p['urgencia'] === 'baixa' ? 'selected' : '' ?>>Baixa</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="flex justify-between pt-6 mt-4 border-t border-gray-100">
            <a href="<?= APP_URL ?>/plano-de-acao/prioridades/<?= (int) $dados['plano']['id'] ?>" class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">← Voltar</a>
            <button type="button" id="btnFinalizar" onclick="salvarTarefas()"
                    class="px-8 py-3 bg-primary text-white rounded-lg hover:bg-primary-700 transition font-semibold">
                Finalizar Plano ✓
            </button>
        </div>
    </form>
</div>

<script>
async function salvarTarefas() {
    const btn = document.getElementById('btnFinalizar');
    btn.disabled = true;
    btn.textContent = 'Salvando...';

    try {
        const formData = new FormData(document.getElementById('formTarefas'));
        const resp = await fetch('<?= APP_URL ?>/plano-de-acao/salvar-tarefas', { method: 'POST', body: formData });
        const data = await resp.json();
        if (data.sucesso) {
            window.location.href = data.redirect;
        } else {
            alert(data.mensagem || 'Erro ao salvar tarefas.');
            btn.disabled = false;
            btn.textContent = 'Finalizar Plano ✓';
        }
    } catch (e) {
        alert('Erro de conexão. Tente novamente.');
        btn.disabled = false;
        btn.textContent = 'Finalizar Plano ✓';
    }
}
</script>

</body>
</html>
