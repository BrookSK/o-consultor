<?php $tituloPagina = 'Diagnóstico — Bloco 4 de 5'; ?>
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
<body class="bg-gray-50 min-h-screen" x-data="diagnosticoBloco4()">

<div class="max-w-4xl mx-auto p-6">
    <?php 
    $blocoAtual = 4;
    include VIEW_PATH . '/diagnostico/components/navegacao-blocos.php'; 
    ?>

    <!-- Formulário Bloco 4 -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
        <form @submit.prevent="salvarBloco()" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
            <input type="hidden" name="bloco" value="4">
            <input type="hidden" name="rascunho_id" value="<?= $dados['rascunho']['id'] ?>">

            <!-- Seção: Gestão de Pessoas -->
            <div class="border-l-4 border-green-500 pl-4">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">👥 Gestão de Pessoas</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Estrutura Organizacional -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Estrutura Organizacional</label>
                        <select name="estrutura_organizacional" x-model="form.estrutura_organizacional" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Selecione</option>
                            <option value="organograma-formal" <?= ($dados['rascunho']['estrutura_organizacional'] ?? '') === 'organograma-formal' ? 'selected' : '' ?>>Organograma formal e atualizado</option>
                            <option value="organograma-basico" <?= ($dados['rascunho']['estrutura_organizacional'] ?? '') === 'organograma-basico' ? 'selected' : '' ?>>Organograma básico</option>
                            <option value="estrutura-mental" <?= ($dados['rascunho']['estrutura_organizacional'] ?? '') === 'estrutura-mental' ? 'selected' : '' ?>>Apenas "na cabeça" do líder</option>
                            <option value="nao-tem" <?= ($dados['rascunho']['estrutura_organizacional'] ?? '') === 'nao-tem' ? 'selected' : '' ?>>Não possui estrutura definida</option>
                        </select>
                    </div>

                    <!-- Política de RH -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Políticas de RH Formalizadas</label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox" name="politicas_rh[]" value="manual-funcionario" 
                                       class="rounded border-gray-300 text-primary focus:ring-primary/20"
                                       <?= in_array('manual-funcionario', explode(',', $dados['rascunho']['politicas_rh'] ?? '')) ? 'checked' : '' ?>>
                                <span class="ml-2 text-sm">Manual do funcionário</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="politicas_rh[]" value="codigo-conduta" 
                                       class="rounded border-gray-300 text-primary focus:ring-primary/20"
                                       <?= in_array('codigo-conduta', explode(',', $dados['rascunho']['politicas_rh'] ?? '')) ? 'checked' : '' ?>>
                                <span class="ml-2 text-sm">Código de conduta</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="politicas_rh[]" value="plano-cargos" 
                                       class="rounded border-gray-300 text-primary focus:ring-primary/20"
                                       <?= in_array('plano-cargos', explode(',', $dados['rascunho']['politicas_rh'] ?? '')) ? 'checked' : '' ?>>
                                <span class="ml-2 text-sm">Plano de cargos e salários</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="politicas_rh[]" value="avaliacao-desempenho" 
                                       class="rounded border-gray-300 text-primary focus:ring-primary/20"
                                       <?= in_array('avaliacao-desempenho', explode(',', $dados['rascunho']['politicas_rh'] ?? '')) ? 'checked' : '' ?>>
                                <span class="ml-2 text-sm">Processo de avaliação de desempenho</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="politicas_rh[]" value="nenhuma" 
                                       class="rounded border-gray-300 text-primary focus:ring-primary/20"
                                       <?= in_array('nenhuma', explode(',', $dados['rascunho']['politicas_rh'] ?? '')) ? 'checked' : '' ?>>
                                <span class="ml-2 text-sm">Nenhuma política formalizada</span>
                            </label>
                        </div>
                    </div>

                    <!-- Turnover -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Taxa de Rotatividade (Turnover)</label>
                        <select name="taxa_turnover" x-model="form.taxa_turnover" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Selecione</option>
                            <option value="muito-baixa" <?= ($dados['rascunho']['taxa_turnover'] ?? '') === 'muito-baixa' ? 'selected' : '' ?>>Muito baixa (0-5%/ano)</option>
                            <option value="baixa" <?= ($dados['rascunho']['taxa_turnover'] ?? '') === 'baixa' ? 'selected' : '' ?>>Baixa (6-15%/ano)</option>
                            <option value="media" <?= ($dados['rascunho']['taxa_turnover'] ?? '') === 'media' ? 'selected' : '' ?>>Média (16-30%/ano)</option>
                            <option value="alta" <?= ($dados['rascunho']['taxa_turnover'] ?? '') === 'alta' ? 'selected' : '' ?>>Alta (31-50%/ano)</option>
                            <option value="muito-alta" <?= ($dados['rascunho']['taxa_turnover'] ?? '') === 'muito-alta' ? 'selected' : '' ?>>Muito alta (acima 50%/ano)</option>
                            <option value="nao-mede" <?= ($dados['rascunho']['taxa_turnover'] ?? '') === 'nao-mede' ? 'selected' : '' ?>>Não meço/acompanho</option>
                        </select>
                    </div>

                    <!-- Capacitação -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Programa de Capacitação</label>
                        <select name="programa_capacitacao" x-model="form.programa_capacitacao" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Selecione</option>
                            <option value="programa-formal" <?= ($dados['rascunho']['programa_capacitacao'] ?? '') === 'programa-formal' ? 'selected' : '' ?>>Programa formal de treinamento</option>
                            <option value="treinamentos-esporadicos" <?= ($dados['rascunho']['programa_capacitacao'] ?? '') === 'treinamentos-esporadicos' ? 'selected' : '' ?>>Treinamentos esporádicos</option>
                            <option value="capacitacao-informal" <?= ($dados['rascunho']['programa_capacitacao'] ?? '') === 'capacitacao-informal' ? 'selected' : '' ?>>Capacitação informal (no dia a dia)</option>
                            <option value="nao-tem" <?= ($dados['rascunho']['programa_capacitacao'] ?? '') === 'nao-tem' ? 'selected' : '' ?>>Não possui programa estruturado</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Seção: Gestão de Riscos -->
            <div class="border-l-4 border-red-500 pl-4">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">⚠️ Gestão de Riscos</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Mapeamento de Riscos -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Mapeamento de Riscos</label>
                        <select name="mapeamento_riscos" x-model="form.mapeamento_riscos" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Selecione</option>
                            <option value="matriz-formal" <?= ($dados['rascunho']['mapeamento_riscos'] ?? '') === 'matriz-formal' ? 'selected' : '' ?>>Matriz de riscos formalizada</option>
                            <option value="listagem-basica" <?= ($dados['rascunho']['mapeamento_riscos'] ?? '') === 'listagem-basica' ? 'selected' : '' ?>>Listagem básica de riscos</option>
                            <option value="conhecimento-tacito" <?= ($dados['rascunho']['mapeamento_riscos'] ?? '') === 'conhecimento-tacito' ? 'selected' : '' ?>>Conhece os riscos mas não mapeou</option>
                            <option value="nao-tem" <?= ($dados['rascunho']['mapeamento_riscos'] ?? '') === 'nao-tem' ? 'selected' : '' ?>>Nunca pensou sistematicamente nos riscos</option>
                        </select>
                    </div>

                    <!-- Seguros -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Cobertura de Seguros</label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox" name="seguros[]" value="responsabilidade-civil" 
                                       class="rounded border-gray-300 text-primary focus:ring-primary/20"
                                       <?= in_array('responsabilidade-civil', explode(',', $dados['rascunho']['seguros'] ?? '')) ? 'checked' : '' ?>>
                                <span class="ml-2 text-sm">Responsabilidade civil</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="seguros[]" value="patrimonial" 
                                       class="rounded border-gray-300 text-primary focus:ring-primary/20"
                                       <?= in_array('patrimonial', explode(',', $dados['rascunho']['seguros'] ?? '')) ? 'checked' : '' ?>>
                                <span class="ml-2 text-sm">Seguro patrimonial</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="seguros[]" value="d-o" 
                                       class="rounded border-gray-300 text-primary focus:ring-primary/20"
                                       <?= in_array('d-o', explode(',', $dados['rascunho']['seguros'] ?? '')) ? 'checked' : '' ?>>
                                <span class="ml-2 text-sm">Seguro D&O (diretores e administradores)</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="seguros[]" value="cyber" 
                                       class="rounded border-gray-300 text-primary focus:ring-primary/20"
                                       <?= in_array('cyber', explode(',', $dados['rascunho']['seguros'] ?? '')) ? 'checked' : '' ?>>
                                <span class="ml-2 text-sm">Cyber segurança</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="seguros[]" value="nenhum" 
                                       class="rounded border-gray-300 text-primary focus:ring-primary/20"
                                       <?= in_array('nenhum', explode(',', $dados['rascunho']['seguros'] ?? '')) ? 'checked' : '' ?>>
                                <span class="ml-2 text-sm">Não possui seguros</span>
                            </label>
                        </div>
                    </div>

                    <!-- Backup e Continuidade -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Backup e Continuidade de Negócio</label>
                        <select name="backup_continuidade" x-model="form.backup_continuidade" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Selecione</option>
                            <option value="plano-formal" <?= ($dados['rascunho']['backup_continuidade'] ?? '') === 'plano-formal' ? 'selected' : '' ?>>Plano de continuidade formal e testado</option>
                            <option value="backup-automatico" <?= ($dados['rascunho']['backup_continuidade'] ?? '') === 'backup-automatico' ? 'selected' : '' ?>>Backup automático em nuvem</option>
                            <option value="backup-manual" <?= ($dados['rascunho']['backup_continuidade'] ?? '') === 'backup-manual' ? 'selected' : '' ?>>Backup manual esporádico</option>
                            <option value="nao-tem" <?= ($dados['rascunho']['backup_continuidade'] ?? '') === 'nao-tem' ? 'selected' : '' ?>>Não possui backup estruturado</option>
                        </select>
                    </div>

                    <!-- Conformidade Regulatória -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Conformidade Regulatória</label>
                        <select name="conformidade_regulatoria" x-model="form.conformidade_regulatoria" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Selecione</option>
                            <option value="totalmente-conforme" <?= ($dados['rascunho']['conformidade_regulatoria'] ?? '') === 'totalmente-conforme' ? 'selected' : '' ?>>Totalmente em conformidade</option>
                            <option value="conforme-basico" <?= ($dados['rascunho']['conformidade_regulatoria'] ?? '') === 'conforme-basico' ? 'selected' : '' ?>>Conforme com obrigatórias básicas</option>
                            <option value="algumas-pendencias" <?= ($dados['rascunho']['conformidade_regulatoria'] ?? '') === 'algumas-pendencias' ? 'selected' : '' ?>>Algumas pendências menores</option>
                            <option value="muitas-pendencias" <?= ($dados['rascunho']['conformidade_regulatoria'] ?? '') === 'muitas-pendencias' ? 'selected' : '' ?>>Muitas pendências regulatórias</option>
                            <option value="nao-sei" <?= ($dados['rascunho']['conformidade_regulatoria'] ?? '') === 'nao-sei' ? 'selected' : '' ?>>Não sei o que é necessário</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Seção: Dependências Críticas -->
            <div class="border-l-4 border-yellow-500 pl-4">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">🔗 Dependências Críticas</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Dependência de Pessoas -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Dependência de Pessoas-Chave</label>
                        <select name="dependencia_pessoas" x-model="form.dependencia_pessoas" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Selecione</option>
                            <option value="totalmente-dependente" <?= ($dados['rascunho']['dependencia_pessoas'] ?? '') === 'totalmente-dependente' ? 'selected' : '' ?>>Totalmente dependente do proprietário/sócio</option>
                            <option value="algumas-pessoas" <?= ($dados['rascunho']['dependencia_pessoas'] ?? '') === 'algumas-pessoas' ? 'selected' : '' ?>>Depende de 2-3 pessoas específicas</option>
                            <option value="conhecimento-distribuido" <?= ($dados['rascunho']['dependencia_pessoas'] ?? '') === 'conhecimento-distribuido' ? 'selected' : '' ?>>Conhecimento distribuído na equipe</option>
                            <option value="processos-documentados" <?= ($dados['rascunho']['dependencia_pessoas'] ?? '') === 'processos-documentados' ? 'selected' : '' ?>>Processos documentados, baixa dependência</option>
                        </select>
                    </div>

                    <!-- Dependência de Fornecedores -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Dependência de Fornecedores/Clientes</label>
                        <select name="dependencia_fornecedores" x-model="form.dependencia_fornecedores" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Selecione</option>
                            <option value="um-fornecedor-critico" <?= ($dados['rascunho']['dependencia_fornecedores'] ?? '') === 'um-fornecedor-critico' ? 'selected' : '' ?>>1 fornecedor crítico (>80% dependência)</option>
                            <option value="poucos-fornecedores" <?= ($dados['rascunho']['dependencia_fornecedores'] ?? '') === 'poucos-fornecedores' ? 'selected' : '' ?>>2-3 fornecedores importantes</option>
                            <option value="fornecedores-diversificados" <?= ($dados['rascunho']['dependencia_fornecedores'] ?? '') === 'fornecedores-diversificados' ? 'selected' : '' ?>>Fornecedores diversificados</option>
                            <option value="cliente-concentrado" <?= ($dados['rascunho']['dependencia_fornecedores'] ?? '') === 'cliente-concentrado' ? 'selected' : '' ?>>>50% receita vem de 1-2 clientes</option>
                            <option value="carteira-pulverizada" <?= ($dados['rascunho']['dependencia_fornecedores'] ?? '') === 'carteira-pulverizada' ? 'selected' : '' ?>>Carteira de clientes pulverizada</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Observações -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Observações sobre Pessoas e Riscos</label>
                <textarea name="observacoes_bloco4" x-model="form.observacoes_bloco4" rows="4"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none"
                          placeholder="Descreva principais desafios de RH, riscos identificados, dependências críticas específicas, etc."><?= $dados['rascunho']['observacoes_bloco4'] ?? '' ?></textarea>
            </div>

            <?php 
            $blocoAtual = 4;
            $rascunho = $dados['rascunho'];
            $loading = 'loading';
            include VIEW_PATH . '/diagnostico/components/botoes-navegacao.php'; 
            ?>
        </form>
    </div>
</div>

<script src="<?= APP_URL ?>/public/assets/js/diagnostico-comum.js"></script>
<script>
// Definir APP_URL para o JavaScript comum
const APP_URL = '<?= APP_URL ?>';

function diagnosticoBloco4() {
    return {
        loading: false,
        form: {
            estrutura_organizacional: '<?= $dados['rascunho']['estrutura_organizacional'] ?? '' ?>',
            taxa_turnover: '<?= $dados['rascunho']['taxa_turnover'] ?? '' ?>',
            programa_capacitacao: '<?= $dados['rascunho']['programa_capacitacao'] ?? '' ?>',
            mapeamento_riscos: '<?= $dados['rascunho']['mapeamento_riscos'] ?? '' ?>',
            backup_continuidade: '<?= $dados['rascunho']['backup_continuidade'] ?? '' ?>',
            conformidade_regulatoria: '<?= $dados['rascunho']['conformidade_regulatoria'] ?? '' ?>',
            dependencia_pessoas: '<?= $dados['rascunho']['dependencia_pessoas'] ?? '' ?>',
            dependencia_fornecedores: '<?= $dados['rascunho']['dependencia_fornecedores'] ?? '' ?>',
            observacoes_bloco4: '<?= $dados['rascunho']['observacoes_bloco4'] ?? '' ?>'
        },

        async salvarBloco() {
            this.loading = true;
            
            try {
                // Coletar dados dos checkboxes
                const politicas = [];
                document.querySelectorAll('input[name="politicas_rh[]"]:checked').forEach(cb => {
                    politicas.push(cb.value);
                });
                
                const seguros = [];
                document.querySelectorAll('input[name="seguros[]"]:checked').forEach(cb => {
                    seguros.push(cb.value);
                });
                
                // Preparar dados para envio
                const dados = { ...this.form };
                dados.politicas_rh = politicas.join(',');
                dados.seguros = seguros.join(',');
                
                const result = await salvarBlocoComum(4, '<?= $dados['rascunho']['id'] ?>', dados);
                
                if (result.sucesso) {
                    // Redirecionar para próximo bloco
                    window.location.href = '<?= APP_URL ?>/diagnostico/bloco/5?rascunho_id=<?= $dados['rascunho']['id'] ?>';
                }
            } catch (error) {
                // Erro já tratado na função comum
            } finally {
                this.loading = false;
            }
        },

        // Função para limpar rascunho (referenciada nos botões)
        limparRascunho() {
            return limparRascunho();
        }
    };
}
</script>

</body>
</html>