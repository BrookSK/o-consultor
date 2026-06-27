<?php $tituloPagina = 'Diagnóstico — Bloco 1 de 5'; ?>
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
<body class="bg-gray-50 min-h-screen" x-data="diagnosticoBloco1()">

<div class="max-w-4xl mx-auto p-6">
    <!-- Header com Progresso -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Diagnóstico Empresarial</h1>
                <p class="text-gray-500">Bloco 1 de 5 — Identificação da Empresa</p>
            </div>
            <div class="text-right">
                <div class="text-sm text-gray-500 mb-1">Progresso</div>
                <div class="w-32 bg-gray-200 rounded-full h-2">
                    <div class="bg-primary h-2 rounded-full" style="width: 20%"></div>
                </div>
                <div class="text-xs text-gray-400 mt-1">20% concluído</div>
            </div>
        </div>
    </div>

    <!-- Formulário Bloco 1 -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
        <form @submit.prevent="salvarBloco()" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
            <input type="hidden" name="bloco" value="1">
            <input type="hidden" name="rascunho_id" value="<?= $dados['rascunho']['id'] ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Nome da Empresa -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nome da Empresa *</label>
                    <input type="text" name="empresa_nome" x-model="form.empresa_nome" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                           placeholder="Digite o nome completo da empresa"
                           value="<?= htmlspecialchars($dados['rascunho']['empresa_nome'] ?? '') ?>">
                </div>

                <!-- Setor -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Setor de Atuação *</label>
                    <select name="setor" x-model="form.setor" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">Selecione o setor</option>
                        <?php foreach ($dados['opcoes']['setores'] as $setor): ?>
                            <option value="<?= $setor ?>" <?= ($dados['rascunho']['setor'] ?? '') === $setor ? 'selected' : '' ?>>
                                <?= htmlspecialchars($setor) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Tempo de Existência -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Tempo de Existência</label>
                    <select name="tempo_existencia" x-model="form.tempo_existencia"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">Selecione</option>
                        <?php foreach ($dados['opcoes']['tempo_existencia'] as $tempo): ?>
                            <option value="<?= $tempo ?>" <?= ($dados['rascunho']['tempo_existencia'] ?? '') === $tempo ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tempo) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Estrutura Societária -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Estrutura Societária</label>
                    <select name="estrutura_societaria" x-model="form.estrutura_societaria"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">Selecione</option>
                        <?php foreach ($dados['opcoes']['estrutura_societaria'] as $estrutura): ?>
                            <option value="<?= $estrutura ?>" <?= ($dados['rascunho']['estrutura_societaria'] ?? '') === $estrutura ? 'selected' : '' ?>>
                                <?= htmlspecialchars($estrutura) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Unidades/Filiais -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Número de Unidades/Filiais</label>
                    <input type="number" name="unidades_filiais" x-model="form.unidades_filiais" min="1"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                           placeholder="1"
                           value="<?= $dados['rascunho']['unidades_filiais'] ?? 1 ?>">
                </div>

                <!-- Língua Principal -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Língua Principal</label>
                    <select name="lingua_principal" x-model="form.lingua_principal"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <?php foreach ($dados['opcoes']['linguas'] as $lingua): ?>
                            <option value="<?= $lingua ?>" <?= ($dados['rascunho']['lingua_principal'] ?? 'Português') === $lingua ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lingua) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Descrição -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Breve Descrição da Empresa</label>
                    <textarea name="descricao" x-model="form.descricao" rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none"
                              placeholder="Descreva brevemente a atividade principal da empresa, produtos/serviços oferecidos..."><?= htmlspecialchars($dados['rascunho']['descricao'] ?? '') ?></textarea>
                    <div class="mt-2">
                        <div class="microfone-container" data-textarea="descricao"></div>
                    </div>
                </div>
            </div>

            <!-- Botões de Navegação -->
            <div class="flex justify-between pt-6 border-t border-gray-100">
                <a href="<?= APP_URL ?>/diagnostico" 
                   class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                    ← Cancelar
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
function diagnosticoBloco1() {
    return {
        loading: false,
        form: {
            empresa_nome: '<?= addslashes($dados['rascunho']['empresa_nome'] ?? '') ?>',
            setor: '<?= addslashes($dados['rascunho']['setor'] ?? '') ?>',
            descricao: '<?= addslashes($dados['rascunho']['descricao'] ?? '') ?>',
            tempo_existencia: '<?= addslashes($dados['rascunho']['tempo_existencia'] ?? '') ?>',
            estrutura_societaria: '<?= addslashes($dados['rascunho']['estrutura_societaria'] ?? '') ?>',
            unidades_filiais: '<?= $dados['rascunho']['unidades_filiais'] ?? 1 ?>',
            lingua_principal: '<?= addslashes($dados['rascunho']['lingua_principal'] ?? 'Português') ?>'
        },

        async salvarBloco() {
            if (!this.form.empresa_nome.trim()) {
                alert('Nome da empresa é obrigatório');
                return;
            }

            if (!this.form.setor) {
                alert('Setor de atuação é obrigatório');
                return;
            }

            this.loading = true;

            try {
                const formData = new FormData();
                formData.append('csrf_token', '<?= Csrf::token() ?>');
                formData.append('bloco', '1');
                formData.append('rascunho_id', '<?= $dados['rascunho']['id'] ?>');
                
                Object.keys(this.form).forEach(key => {
                    formData.append(key, this.form[key]);
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
                        window.location.href = '<?= APP_URL ?>/diagnostico/bloco/2?rascunho_id=<?= $dados['rascunho']['id'] ?>';
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