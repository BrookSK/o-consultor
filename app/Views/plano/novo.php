<?php $tituloPagina = 'Novo Plano de Ação'; ?>
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
<body class="bg-gray-50 min-h-screen" x-data="novoPlano()">

<div class="max-w-4xl mx-auto p-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Novo Plano de Ação</h1>
                <p class="text-gray-500">Step 1 de 3 — Informações Básicas</p>
            </div>
            <div class="text-right">
                <div class="text-sm text-gray-500 mb-1">Progresso</div>
                <div class="w-32 bg-gray-200 rounded-full h-2">
                    <div class="bg-primary h-2 rounded-full" style="width: 33%"></div>
                </div>
                <div class="text-xs text-gray-400 mt-1">33% concluído</div>
            </div>
        </div>
    </div>

    <!-- Formulário Step 1 -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
        <form @submit.prevent="salvarStep1()" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">

            <!-- Título do Plano -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Título do Plano *</label>
                <input type="text" name="titulo" x-model="form.titulo" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                       placeholder="Ex: Plano de Ação - Crescimento 2026">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Empresa -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Empresa *</label>
                    <select name="empresa_id" x-model="form.empresa_id" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">Selecione a empresa</option>
                        <?php foreach ($dados['empresas'] as $empresa): ?>
                            <option value="<?= $empresa['id'] ?>" <?= ($dados['diagnostico']['empresa_id'] ?? 0) == $empresa['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($empresa['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Diagnóstico (se disponível) -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Diagnóstico Base</label>
                    <select name="diagnostico_id" x-model="form.diagnostico_id"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">Sem diagnóstico (criação manual)</option>
                        <?php if ($dados['diagnostico']): ?>
                            <option value="<?= $dados['diagnostico']['id'] ?>" selected>
                                Diagnóstico de <?= date('d/m/Y', strtotime($dados['diagnostico']['criado_em'])) ?> - Score: <?= $dados['diagnostico']['pontuacao'] ?>%
                            </option>
                        <?php endif; ?>
                    </select>
                    <?php if ($dados['diagnostico']): ?>
                        <p class="text-xs text-green-600 mt-1">
                            ✓ Este plano será gerado automaticamente com base no diagnóstico
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Objetivo -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Objetivo Principal</label>
                <textarea name="objetivo" x-model="form.objetivo" rows="3"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none"
                          placeholder="Descreva o objetivo principal deste plano de ação..."></textarea>
                <div class="mt-2">
                    <div class="microfone-container" data-textarea="objetivo"></div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Período -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Data de Início</label>
                    <input type="date" name="periodo_inicio" x-model="form.periodo_inicio"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Data de Término</label>
                    <input type="date" name="periodo_fim" x-model="form.periodo_fim"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>
            </div>

            <!-- Botões de Navegação -->
            <div class="flex justify-between pt-6 border-t border-gray-100">
                <a href="<?= APP_URL ?>/plano-de-acao" 
                   class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                    ← Cancelar
                </a>
                
                <button type="submit" :disabled="loading"
                        class="px-8 py-3 bg-primary text-white rounded-lg hover:bg-primary-700 transition font-semibold flex items-center gap-2"
                        :class="{ 'opacity-50 cursor-not-allowed': loading }">
                    <span x-show="!loading">Próximo: Gerar Prioridades →</span>
                    <span x-show="loading" class="flex items-center gap-2">
                        <div class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                        Criando plano...
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

<script src="<?= APP_URL ?>/public/assets/js/microfone-transcricao.js"></script>
<script>
function novoPlano() {
    return {
        loading: false,
        form: {
            titulo: '',
            empresa_id: '<?= $dados['diagnostico']['empresa_id'] ?? '' ?>',
            diagnostico_id: '<?= $dados['diagnostico']['id'] ?? '' ?>',
            objetivo: '',
            periodo_inicio: '<?= date('Y-m-d') ?>',
            periodo_fim: '<?= date('Y-m-d', strtotime('+12 months')) ?>'
        },

        async salvarStep1() {
            if (!this.form.titulo.trim()) {
                alert('Título do plano é obrigatório');
                return;
            }

            if (!this.form.empresa_id) {
                alert('Empresa é obrigatória');
                return;
            }

            this.loading = true;

            try {
                const formData = new FormData();
                formData.append('csrf_token', '<?= Csrf::token() ?>');
                
                Object.keys(this.form).forEach(key => {
                    formData.append(key, this.form[key]);
                });

                const response = await fetch('<?= APP_URL ?>/plano-de-acao/salvar-step1', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.sucesso) {
                    window.location.href = result.redirect;
                } else {
                    alert(result.mensagem || 'Erro ao criar plano');
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