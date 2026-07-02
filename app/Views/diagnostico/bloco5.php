<?php $tituloPagina = 'Diagnóstico — Bloco 5 de 5'; ?>
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
<body class="bg-gray-50 min-h-screen" x-data="diagnosticoBloco5()">

<div class="max-w-4xl mx-auto p-6">
    <?php 
    $blocoAtual = 5;
    include VIEW_PATH . '/diagnostico/components/navegacao-blocos.php'; 
    ?>

    <!-- Formulário Bloco 5 -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
        <form @submit.prevent="salvarBloco()" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
            <input type="hidden" name="bloco" value="5">
            <input type="hidden" name="rascunho_id" value="<?= $dados['rascunho']['id'] ?>">

            <div class="space-y-6">
                <!-- Pontos Fortes -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Principais Pontos Fortes da Empresa</label>
                    <textarea name="pontos_fortes" x-model="form.pontos_fortes" rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none"
                              placeholder="Quais são os maiores diferenciais e pontos fortes da empresa?"><?= htmlspecialchars($dados['rascunho']['pontos_fortes'] ?? '') ?></textarea>
                    <div class="mt-2">
                        <div class="microfone-container" data-textarea="pontos_fortes"></div>
                    </div>
                </div>

                <!-- Pontos de Melhoria -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Principais Pontos de Melhoria</label>
                    <textarea name="pontos_melhoria" x-model="form.pontos_melhoria" rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none"
                              placeholder="Quais são as maiores oportunidades de melhoria identificadas?"><?= htmlspecialchars($dados['rascunho']['pontos_melhoria'] ?? '') ?></textarea>
                    <div class="mt-2">
                        <div class="microfone-container" data-textarea="pontos_melhoria"></div>
                    </div>
                </div>

                <!-- Objetivo 12 meses -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Principal Objetivo para os Próximos 12 Meses</label>
                    <textarea name="objetivo_12_meses" x-model="form.objetivo_12_meses" rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none"
                              placeholder="Qual é o principal objetivo ou meta da empresa para o próximo ano?"><?= htmlspecialchars($dados['rascunho']['objetivo_12_meses'] ?? '') ?></textarea>
                    <div class="mt-2">
                        <div class="microfone-container" data-textarea="objetivo_12_meses"></div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Maturidade Percebida -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Como você avalia a maturidade atual da empresa?</label>
                        <select name="maturidade_percebida" x-model="form.maturidade_percebida"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="1" <?= ($dados['rascunho']['maturidade_percebida'] ?? 3) == 1 ? 'selected' : '' ?>>1 - Inicial/Básica</option>
                            <option value="2" <?= ($dados['rascunho']['maturidade_percebida'] ?? 3) == 2 ? 'selected' : '' ?>>2 - Em desenvolvimento</option>
                            <option value="3" <?= ($dados['rascunho']['maturidade_percebida'] ?? 3) == 3 ? 'selected' : '' ?>>3 - Crescimento estruturado</option>
                            <option value="4" <?= ($dados['rascunho']['maturidade_percebida'] ?? 3) == 4 ? 'selected' : '' ?>>4 - Excelência operacional</option>
                        </select>
                    </div>

                    <!-- Planejamento Documentado -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">A empresa possui planejamento estratégico documentado?</label>
                        <select name="planejamento_documentado" x-model="form.planejamento_documentado"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="nao" <?= ($dados['rascunho']['planejamento_documentado'] ?? 'nao') === 'nao' ? 'selected' : '' ?>>Não</option>
                            <option value="sim" <?= ($dados['rascunho']['planejamento_documentado'] ?? 'nao') === 'sim' ? 'selected' : '' ?>>Sim</option>
                        </select>
                    </div>

                    <!-- Frequência de Reuniões -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Frequência de Reuniões Estratégicas</label>
                        <select name="frequencia_reunioes" x-model="form.frequencia_reunioes"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Selecione</option>
                            <?php foreach ($dados['opcoes']['frequencia_reunioes'] as $freq): ?>
                                <option value="<?= $freq ?>" <?= ($dados['rascunho']['frequencia_reunioes'] ?? '') === $freq ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($freq) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Meta de Faturamento -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">A empresa possui metas de faturamento definidas?</label>
                        <select name="meta_faturamento" x-model="form.meta_faturamento"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="nao" <?= ($dados['rascunho']['meta_faturamento'] ?? 'nao') === 'nao' ? 'selected' : '' ?>>Não</option>
                            <option value="sim" <?= ($dados['rascunho']['meta_faturamento'] ?? 'nao') === 'sim' ? 'selected' : '' ?>>Sim</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Botões de Navegação -->
            <div class="flex justify-between pt-6 border-t border-gray-100">
                <div class="flex gap-3">
                    <a href="<?= APP_URL ?>/diagnostico" 
                       class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                        ← Voltar ao Início
                    </a>
                    
                    <button type="button" @click="limparRascunho()"
                            class="px-6 py-3 border border-red-300 rounded-lg text-red-700 hover:bg-red-50 transition">
                        🗑️ Limpar Rascunho
                    </button>
                </div>
                
                <div class="flex gap-3 items-center">
                    <!-- Botão Anterior -->
                    <a href="<?= APP_URL ?>/diagnostico/bloco/4?rascunho_id=<?= $dados['rascunho']['id'] ?>"
                       class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                        ← Bloco 4
                    </a>
                    
                    <!-- Botão Salvar -->
                    <button type="submit" :disabled="loading"
                            class="px-6 py-3 border border-primary text-primary rounded-lg hover:bg-primary/5 transition font-medium"
                            :class="{ 'opacity-50 cursor-not-allowed': loading }">
                        <span x-show="!loading">Salvar Bloco</span>
                        <span x-show="loading">Salvando...</span>
                    </button>
                    
                    <!-- Gerar Diagnóstico -->
                    <button type="button" @click="gerarDiagnostico()" :disabled="loading || generating"
                            class="px-8 py-3 bg-accent text-white rounded-lg hover:bg-orange-700 transition font-semibold flex items-center gap-2"
                            :class="{ 'opacity-50 cursor-not-allowed': loading || generating }">
                        <span x-show="!generating">🚀 Gerar Diagnóstico</span>
                        <span x-show="generating" class="flex items-center gap-2">
                            <div class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                            Gerando com IA...
                        </span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="<?= APP_URL ?>/public/assets/js/microfone-transcricao.js"></script>
<script src="<?= APP_URL ?>/public/assets/js/diagnostico-comum.js"></script>
<script>
// Definir APP_URL para o JavaScript comum
const APP_URL = '<?= APP_URL ?>';

function diagnosticoBloco5() {
    return {
        loading: false,
        generating: false,
        form: {
            pontos_fortes: '<?= addslashes($dados['rascunho']['pontos_fortes'] ?? '') ?>',
            pontos_melhoria: '<?= addslashes($dados['rascunho']['pontos_melhoria'] ?? '') ?>',
            objetivo_12_meses: '<?= addslashes($dados['rascunho']['objetivo_12_meses'] ?? '') ?>',
            maturidade_percebida: '<?= $dados['rascunho']['maturidade_percebida'] ?? 3 ?>',
            planejamento_documentado: '<?= $dados['rascunho']['planejamento_documentado'] ?? 'nao' ?>',
            frequencia_reunioes: '<?= addslashes($dados['rascunho']['frequencia_reunioes'] ?? '') ?>',
            meta_faturamento: '<?= $dados['rascunho']['meta_faturamento'] ?? 'nao' ?>'
        },

        async salvarBloco() {
            this.loading = true;

            try {
                const result = await salvarBlocoComum(5, '<?= $dados['rascunho']['id'] ?>', this.form);
                
                if (result.sucesso) {
                    showToast('Bloco 5 salvo com sucesso! Agora clique em "Gerar Diagnóstico" para finalizar.', 'success');
                }
            } catch (error) {
                // Erro já tratado na função comum
            } finally {
                this.loading = false;
            }
        },

        async gerarDiagnostico() {
            // Primeiro salvar o bloco atual silenciosamente
            if (!this.loading) {
                await this.salvarBlocoSilencioso();
            }

            this.generating = true;

            try {
                const formData = new FormData();
                formData.append('csrf_token', '<?= Csrf::token() ?>');
                formData.append('rascunho_id', '<?= $dados['rascunho']['id'] ?>');

                const response = await fetch('<?= APP_URL ?>/diagnostico/gerar', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.sucesso) {
                    window.location.href = result.redirect || '<?= APP_URL ?>/diagnostico/resultado';
                } else {
                    showToast(result.mensagem || 'Erro ao gerar diagnóstico', 'error');
                }
            } catch (error) {
                showToast('Erro na conexão. Tente novamente.', 'error');
            } finally {
                this.generating = false;
            }
        },

        async salvarBlocoSilencioso() {
            try {
                await salvarBlocoComum(5, '<?= $dados['rascunho']['id'] ?>', this.form);
            } catch (error) {
                // Silencioso - não precisa avisar se deu erro
            }
        },

        // Função para limpar rascunho (referenciada nos botões)
        limparRascunho() {
            return limparRascunho();
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