<?php $tituloPagina = 'Diagnóstico — Bloco 3 de 5'; ?>
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
<body class="bg-gray-50 min-h-screen" x-data="diagnosticoBloco3()">

<div class="max-w-4xl mx-auto p-6">
    <!-- Header com Progresso -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Diagnóstico Empresarial</h1>
                <p class="text-gray-500">Bloco 3 de 5 — Estrutura Financeira e Comercial</p>
            </div>
            <div class="text-right">
                <div class="text-sm text-gray-500 mb-1">Progresso</div>
                <div class="w-32 bg-gray-200 rounded-full h-2">
                    <div class="bg-primary h-2 rounded-full" style="width: 60%"></div>
                </div>
                <div class="text-xs text-gray-400 mt-1">60% concluído</div>
            </div>
        </div>
    </div>

    <!-- Formulário Bloco 3 -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
        <form @submit.prevent="salvarBloco()" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
            <input type="hidden" name="bloco" value="3">
            <input type="hidden" name="rascunho_id" value="<?= $dados['rascunho']['id'] ?>">

            <!-- Seção: Estrutura Financeira -->
            <div class="border-l-4 border-accent pl-4">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">💰 Estrutura Financeira</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Faturamento Mensal -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Faturamento Médio Mensal</label>
                        <select name="faturamento_mensal" x-model="form.faturamento_mensal" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Selecione uma faixa</option>
                            <option value="ate-10k" <?= ($dados['rascunho']['faturamento_mensal'] ?? '') === 'ate-10k' ? 'selected' : '' ?>>Até R$ 10.000</option>
                            <option value="10k-50k" <?= ($dados['rascunho']['faturamento_mensal'] ?? '') === '10k-50k' ? 'selected' : '' ?>>R$ 10.001 - R$ 50.000</option>
                            <option value="50k-100k" <?= ($dados['rascunho']['faturamento_mensal'] ?? '') === '50k-100k' ? 'selected' : '' ?>>R$ 50.001 - R$ 100.000</option>
                            <option value="100k-500k" <?= ($dados['rascunho']['faturamento_mensal'] ?? '') === '100k-500k' ? 'selected' : '' ?>>R$ 100.001 - R$ 500.000</option>
                            <option value="acima-500k" <?= ($dados['rascunho']['faturamento_mensal'] ?? '') === 'acima-500k' ? 'selected' : '' ?>>Acima de R$ 500.000</option>
                        </select>
                    </div>

                    <!-- Lucratividade -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Margem de Lucro Líquido (%)</label>
                        <select name="margem_lucro" x-model="form.margem_lucro" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Selecione</option>
                            <option value="negativa" <?= ($dados['rascunho']['margem_lucro'] ?? '') === 'negativa' ? 'selected' : '' ?>>Negativa (prejuízo)</option>
                            <option value="0-5" <?= ($dados['rascunho']['margem_lucro'] ?? '') === '0-5' ? 'selected' : '' ?>>0% - 5%</option>
                            <option value="6-10" <?= ($dados['rascunho']['margem_lucro'] ?? '') === '6-10' ? 'selected' : '' ?>>6% - 10%</option>
                            <option value="11-20" <?= ($dados['rascunho']['margem_lucro'] ?? '') === '11-20' ? 'selected' : '' ?>>11% - 20%</option>
                            <option value="acima-20" <?= ($dados['rascunho']['margem_lucro'] ?? '') === 'acima-20' ? 'selected' : '' ?>>Acima de 20%</option>
                        </select>
                    </div>

                    <!-- Sistema de Gestão Financeira -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Sistema de Gestão Financeira</label>
                        <select name="sistema_financeiro" x-model="form.sistema_financeiro" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Selecione</option>
                            <option value="planilhas" <?= ($dados['rascunho']['sistema_financeiro'] ?? '') === 'planilhas' ? 'selected' : '' ?>>Planilhas Excel/Google</option>
                            <option value="sistema-basico" <?= ($dados['rascunho']['sistema_financeiro'] ?? '') === 'sistema-basico' ? 'selected' : '' ?>>Sistema básico (ex: Conta Azul, Nibo)</option>
                            <option value="erp-completo" <?= ($dados['rascunho']['sistema_financeiro'] ?? '') === 'erp-completo' ? 'selected' : '' ?>>ERP completo (ex: SAP, Protheus)</option>
                            <option value="nao-tem" <?= ($dados['rascunho']['sistema_financeiro'] ?? '') === 'nao-tem' ? 'selected' : '' ?>>Não possui controle estruturado</option>
                        </select>
                    </div>

                    <!-- Fluxo de Caixa -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Controle de Fluxo de Caixa</label>
                        <select name="controle_fluxo_caixa" x-model="form.controle_fluxo_caixa" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Selecione</option>
                            <option value="diario" <?= ($dados['rascunho']['controle_fluxo_caixa'] ?? '') === 'diario' ? 'selected' : '' ?>>Diário e atualizado</option>
                            <option value="semanal" <?= ($dados['rascunho']['controle_fluxo_caixa'] ?? '') === 'semanal' ? 'selected' : '' ?>>Semanal</option>
                            <option value="mensal" <?= ($dados['rascunho']['controle_fluxo_caixa'] ?? '') === 'mensal' ? 'selected' : '' ?>>Mensal</option>
                            <option value="eventual" <?= ($dados['rascunho']['controle_fluxo_caixa'] ?? '') === 'eventual' ? 'selected' : '' ?>>Eventual/quando lembra</option>
                            <option value="nao-tem" <?= ($dados['rascunho']['controle_fluxo_caixa'] ?? '') === 'nao-tem' ? 'selected' : '' ?>>Não possui controle</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Seção: Estrutura Comercial -->
            <div class="border-l-4 border-primary pl-4">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">🎯 Estrutura Comercial</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Canais de Vendas -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Principais Canais de Vendas</label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox" name="canais_vendas[]" value="loja-fisica" 
                                       class="rounded border-gray-300 text-primary focus:ring-primary/20"
                                       <?= in_array('loja-fisica', explode(',', $dados['rascunho']['canais_vendas'] ?? '')) ? 'checked' : '' ?>>
                                <span class="ml-2 text-sm">Loja física</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="canais_vendas[]" value="site-proprio" 
                                       class="rounded border-gray-300 text-primary focus:ring-primary/20"
                                       <?= in_array('site-proprio', explode(',', $dados['rascunho']['canais_vendas'] ?? '')) ? 'checked' : '' ?>>
                                <span class="ml-2 text-sm">Site próprio</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="canais_vendas[]" value="marketplaces" 
                                       class="rounded border-gray-300 text-primary focus:ring-primary/20"
                                       <?= in_array('marketplaces', explode(',', $dados['rascunho']['canais_vendas'] ?? '')) ? 'checked' : '' ?>>
                                <span class="ml-2 text-sm">Marketplaces (Mercado Livre, Amazon)</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="canais_vendas[]" value="redes-sociais" 
                                       class="rounded border-gray-300 text-primary focus:ring-primary/20"
                                       <?= in_array('redes-sociais', explode(',', $dados['rascunho']['canais_vendas'] ?? '')) ? 'checked' : '' ?>>
                                <span class="ml-2 text-sm">Redes sociais</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="canais_vendas[]" value="indicacoes" 
                                       class="rounded border-gray-300 text-primary focus:ring-primary/20"
                                       <?= in_array('indicacoes', explode(',', $dados['rascunho']['canais_vendas'] ?? '')) ? 'checked' : '' ?>>
                                <span class="ml-2 text-sm">Indicações</span>
                            </label>
                        </div>
                    </div>

                    <!-- CRM -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Sistema de CRM</label>
                        <select name="sistema_crm" x-model="form.sistema_crm" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Selecione</option>
                            <option value="crm-profissional" <?= ($dados['rascunho']['sistema_crm'] ?? '') === 'crm-profissional' ? 'selected' : '' ?>>CRM profissional (HubSpot, Salesforce)</option>
                            <option value="planilhas" <?= ($dados['rascunho']['sistema_crm'] ?? '') === 'planilhas' ? 'selected' : '' ?>>Planilhas</option>
                            <option value="whatsapp-apenas" <?= ($dados['rascunho']['sistema_crm'] ?? '') === 'whatsapp-apenas' ? 'selected' : '' ?>>Apenas WhatsApp</option>
                            <option value="nao-tem" <?= ($dados['rascunho']['sistema_crm'] ?? '') === 'nao-tem' ? 'selected' : '' ?>>Não possui controle de leads</option>
                        </select>
                    </div>

                    <!-- Taxa de Conversão -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Taxa de Conversão de Leads</label>
                        <select name="taxa_conversao" x-model="form.taxa_conversao" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Selecione</option>
                            <option value="nao-sei" <?= ($dados['rascunho']['taxa_conversao'] ?? '') === 'nao-sei' ? 'selected' : '' ?>>Não sei / Não meço</option>
                            <option value="menos-5" <?= ($dados['rascunho']['taxa_conversao'] ?? '') === 'menos-5' ? 'selected' : '' ?>>Menos de 5%</option>
                            <option value="5-15" <?= ($dados['rascunho']['taxa_conversao'] ?? '') === '5-15' ? 'selected' : '' ?>>5% - 15%</option>
                            <option value="16-30" <?= ($dados['rascunho']['taxa_conversao'] ?? '') === '16-30' ? 'selected' : '' ?>>16% - 30%</option>
                            <option value="acima-30" <?= ($dados['rascunho']['taxa_conversao'] ?? '') === 'acima-30' ? 'selected' : '' ?>>Acima de 30%</option>
                        </select>
                    </div>

                    <!-- Ticket Médio -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Ticket Médio por Venda</label>
                        <select name="ticket_medio" x-model="form.ticket_medio" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Selecione uma faixa</option>
                            <option value="ate-100" <?= ($dados['rascunho']['ticket_medio'] ?? '') === 'ate-100' ? 'selected' : '' ?>>Até R$ 100</option>
                            <option value="100-500" <?= ($dados['rascunho']['ticket_medio'] ?? '') === '100-500' ? 'selected' : '' ?>>R$ 100 - R$ 500</option>
                            <option value="500-1500" <?= ($dados['rascunho']['ticket_medio'] ?? '') === '500-1500' ? 'selected' : '' ?>>R$ 500 - R$ 1.500</option>
                            <option value="1500-5000" <?= ($dados['rascunho']['ticket_medio'] ?? '') === '1500-5000' ? 'selected' : '' ?>>R$ 1.500 - R$ 5.000</option>
                            <option value="acima-5000" <?= ($dados['rascunho']['ticket_medio'] ?? '') === 'acima-5000' ? 'selected' : '' ?>>Acima de R$ 5.000</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Observações -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Observações sobre Financeiro/Comercial</label>
                <textarea name="observacoes_bloco3" x-model="form.observacoes_bloco3" rows="4"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none"
                          placeholder="Descreva desafios específicos, metas comerciais, dificuldades financeiras, etc."><?= $dados['rascunho']['observacoes_bloco3'] ?? '' ?></textarea>
            </div>

            <!-- Botões de Navegação -->
            <div class="flex justify-between items-center pt-6 border-t">
                <a href="<?= APP_URL ?>/diagnostico/bloco?bloco=2&id=<?= $dados['rascunho']['id'] ?>" 
                   class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 flex items-center">
                    ← Bloco Anterior
                </a>

                <div class="text-center">
                    <p class="text-sm text-gray-600">Salvamento automático ativado</p>
                    <p x-show="salvando" class="text-xs text-primary">💾 Salvando...</p>
                    <p x-show="!salvando && ultimoSalvo" class="text-xs text-green-600">✓ Salvo às <span x-text="ultimoSalvo"></span></p>
                </div>

                <button type="submit" 
                        class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-700 flex items-center">
                    Próximo Bloco →
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function diagnosticoBloco3() {
    return {
        form: {
            faturamento_mensal: '<?= $dados['rascunho']['faturamento_mensal'] ?? '' ?>',
            margem_lucro: '<?= $dados['rascunho']['margem_lucro'] ?? '' ?>',
            sistema_financeiro: '<?= $dados['rascunho']['sistema_financeiro'] ?? '' ?>',
            controle_fluxo_caixa: '<?= $dados['rascunho']['controle_fluxo_caixa'] ?? '' ?>',
            sistema_crm: '<?= $dados['rascunho']['sistema_crm'] ?? '' ?>',
            taxa_conversao: '<?= $dados['rascunho']['taxa_conversao'] ?? '' ?>',
            ticket_medio: '<?= $dados['rascunho']['ticket_medio'] ?? '' ?>',
            observacoes_bloco3: '<?= $dados['rascunho']['observacoes_bloco3'] ?? '' ?>'
        },
        salvando: false,
        ultimoSalvo: null,

        init() {
            // Salvamento automático a cada 30 segundos
            setInterval(() => this.salvarAutomatico(), 30000);
            
            // Salvar ao alterar campos importantes
            this.$watch('form', () => {
                clearTimeout(this.timeoutSalvar);
                this.timeoutSalvar = setTimeout(() => this.salvarAutomatico(), 2000);
            });
        },

        async salvarBloco() {
            this.salvando = true;
            
            try {
                const formData = new FormData();
                formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
                formData.append('bloco', '3');
                formData.append('rascunho_id', document.querySelector('input[name="rascunho_id"]').value);
                
                // Adicionar todos os campos
                Object.keys(this.form).forEach(key => {
                    formData.append(key, this.form[key]);
                });
                
                // Adicionar checkboxes dos canais de vendas
                const canais = [];
                document.querySelectorAll('input[name="canais_vendas[]"]:checked').forEach(cb => {
                    canais.push(cb.value);
                });
                formData.append('canais_vendas', canais.join(','));
                
                const response = await fetch('<?= APP_URL ?>/diagnostico/salvar-bloco', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.sucesso) {
                    // Redirecionar para próximo bloco
                    window.location.href = '<?= APP_URL ?>/diagnostico/bloco?bloco=4&id=<?= $dados['rascunho']['id'] ?>';
                } else {
                    alert('Erro ao salvar: ' + data.erro);
                }
                
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro de conexão ao salvar');
            } finally {
                this.salvando = false;
            }
        },

        async salvarAutomatico() {
            if (this.salvando) return;
            
            this.salvando = true;
            
            try {
                const formData = new FormData();
                formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
                formData.append('bloco', '3');
                formData.append('rascunho_id', document.querySelector('input[name="rascunho_id"]').value);
                formData.append('auto_save', '1');
                
                Object.keys(this.form).forEach(key => {
                    formData.append(key, this.form[key]);
                });
                
                const canais = [];
                document.querySelectorAll('input[name="canais_vendas[]"]:checked').forEach(cb => {
                    canais.push(cb.value);
                });
                formData.append('canais_vendas', canais.join(','));
                
                const response = await fetch('<?= APP_URL ?>/diagnostico/salvar-bloco', {
                    method: 'POST',
                    body: formData
                });
                
                if (response.ok) {
                    this.ultimoSalvo = new Date().toLocaleTimeString('pt-BR', { 
                        hour: '2-digit', 
                        minute: '2-digit' 
                    });
                }
                
            } catch (error) {
                console.error('Erro no salvamento automático:', error);
            } finally {
                this.salvando = false;
            }
        }
    }
}
</script>

</body>
</html>