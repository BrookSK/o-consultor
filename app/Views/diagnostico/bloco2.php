<?php $tituloPagina = 'Diagnóstico — Bloco 2 de 5'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $tituloPagina ?> — O Consultor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{colors:{primary:{DEFAULT:'#1E3A5F',700:'#162D4A'},accent:'#E07B00'}}}}</script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>body{font-family:'Inter',sans-serif;}</style>
</head>
<body class="bg-gray-50 min-h-screen" x-data="diagnosticoBloco2()">

<div class="max-w-4xl mx-auto p-6">
    <!-- Header com Progresso -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Diagnóstico Empresarial</h1>
                <p class="text-gray-500">Bloco 2 de 5 — Estrutura Operacional</p>
            </div>
            <div class="text-right">
                <div class="text-sm text-gray-500 mb-1">Progresso</div>
                <div class="w-32 bg-gray-200 rounded-full h-2">
                    <div class="bg-primary h-2 rounded-full" style="width: 40%"></div>
                </div>
                <div class="text-xs text-gray-400 mt-1">40% concluído</div>
            </div>
        </div>
    </div>

    <!-- Formulário Bloco 2 -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
        <form @submit.prevent="salvarBloco()" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
            <input type="hidden" name="bloco" value="2">
            <input type="hidden" name="rascunho_id" value="<?= $dados['rascunho']['id'] ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Colaboradores Internos -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Colaboradores Internos</label>
                    <input type="number" name="colaboradores_internos" x-model="form.colaboradores_internos" min="0"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                           placeholder="0"
                           value="<?= $dados['rascunho']['colaboradores_internos'] ?? '' ?>">
                </div>

                <!-- Colaboradores Externos -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Colaboradores Externos/Terceirizados</label>
                    <input type="number" name="colaboradores_externos" x-model="form.colaboradores_externos" min="0"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                           placeholder="0"
                           value="<?= $dados['rascunho']['colaboradores_externos'] ?? '' ?>">
                </div>

                <!-- Departamentos -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-3">Departamentos/Setores Existentes</label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <?php 
                        $departamentosSelecionados = json_decode($dados['rascunho']['departamentos'] ?? '[]', true) ?: [];
                        foreach ($dados['opcoes']['departamentos'] as $dept): 
                        ?>
                        <label class="flex items-center gap-2 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox" name="departamentos[]" value="<?= $dept ?>" 
                                   class="text-primary focus:ring-primary/20"
                                   <?= in_array($dept, $departamentosSelecionados) ? 'checked' : '' ?>>
                            <span class="text-sm"><?= htmlspecialchars($dept) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Faturamento Mensal -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Faturamento Mensal</label>
                    <select name="faturamento_mensal" x-model="form.faturamento_mensal"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">Selecione</option>
                        <?php foreach ($dados['opcoes']['faturamento'] as $faturamento): ?>
                            <option value="<?= $faturamento ?>" <?= ($dados['rascunho']['faturamento_mensal'] ?? '') === $faturamento ? 'selected' : '' ?>>
                                <?= htmlspecialchars($faturamento) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Clientes Ativos -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Número de Clientes Ativos</label>
                    <input type="number" name="clientes_ativos" x-model="form.clientes_ativos" min="0"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                           placeholder="0"
                           value="<?= $dados['rascunho']['clientes_ativos'] ?? '' ?>">
                </div>
            </div>

            <!-- Produtos/Serviços -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Principais Produtos/Serviços</label>
                <textarea name="produtos_servicos" x-model="form.produtos_servicos" rows="3"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none"
                          placeholder="Descreva os principais produtos ou serviços oferecidos pela empresa..."><?= htmlspecialchars($dados['rascunho']['produtos_servicos'] ?? '') ?></textarea>
                <div class="mt-2">
                    <div class="microfone-container" data-textarea="produtos_servicos"></div>
                </div>
            </div>

            <!-- Botões de Navegação -->
            <div class="flex justify-between pt-6 border-t border-gray-100">
                <a href="<?= APP_URL ?>/diagnostico/bloco/1?rascunho_id=<?= $dados['rascunho']['id'] ?>" 
                   class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                    ← Bloco Anterior
                </a>
                
                <button type="submit" :disabled="loading"
                        class="px-8 py-3 bg-primary text-white rounded-lg hover:bg-primary-700 transition font-semibold flex items-center gap-2"
                        :class="{ 'opacity-50 cursor-not-allowed': loading }">
                    <span x-show="!loading">Próximo Bloco →</span>
                    <span x-show="loading" class="flex items-center gap-2">
                        <div class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                        Salvando...
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

<script src="<?= APP_URL ?>/public/assets/js/microfone-transcricao.js"></script>
<script>
function diagnosticoBloco2() {
    return {
        loading: false,
        form: {
            colaboradores_internos: '<?= $dados['rascunho']['colaboradores_internos'] ?? '' ?>',
            colaboradores_externos: '<?= $dados['rascunho']['colaboradores_externos'] ?? '' ?>',
            faturamento_mensal: '<?= addslashes($dados['rascunho']['faturamento_mensal'] ?? '') ?>',
            clientes_ativos: '<?= $dados['rascunho']['clientes_ativos'] ?? '' ?>',
            produtos_servicos: '<?= addslashes($dados['rascunho']['produtos_servicos'] ?? '') ?>'
        },

        async salvarBloco() {
            this.loading = true;

            try {
                const formData = new FormData();
                formData.append('csrf_token', '<?= Csrf::token() ?>');
                formData.append('bloco', '2');
                formData.append('rascunho_id', '<?= $dados['rascunho']['id'] ?>');
                
                Object.keys(this.form).forEach(key => {
                    formData.append(key, this.form[key]);
                });

                // Adicionar departamentos selecionados
                document.querySelectorAll('input[name="departamentos[]"]:checked').forEach(checkbox => {
                    formData.append('departamentos[]', checkbox.value);
                });

                const response = await fetch('<?= APP_URL ?>/diagnostico/salvar-bloco', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.sucesso) {
                    if (result.redirect) {
                        window.location.href = result.redirect;
                    } else {
                        // Por enquanto, vamos para o bloco 5 (último) para teste
                        window.location.href = '<?= APP_URL ?>/diagnostico/bloco/5?rascunho_id=<?= $dados['rascunho']['id'] ?>';
                    }
                } else {
                    alert(result.mensagem || 'Erro ao salvar bloco');
                }
            } catch (error) {
                alert('Erro na conexão. Tente novamente.');
            } finally {
                this.loading = false;
            }
        }
    };
}

// Inicializar microfones
document.addEventListener('DOMContentLoaded', function() {
    initializeMicrophones();
});
</script>

</body>
</html>