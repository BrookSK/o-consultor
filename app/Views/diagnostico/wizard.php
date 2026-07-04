<?php $tituloPagina = 'Diagnóstico Empresarial — Wizard Completo'; ?>
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
<body class="bg-gray-50 min-h-screen" x-data="diagnosticoWizard()">

<div class="max-w-5xl mx-auto p-6">
    <!-- Navegação dos Blocos -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex justify-between items-center">
            <h1 class="text-xl font-bold text-gray-800">Diagnóstico Empresarial</h1>
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-600">Bloco</span>
                <span class="font-bold text-primary" x-text="blocoAtual"></span>
                <span class="text-sm text-gray-600">de 5</span>
            </div>
        </div>
        
        <!-- Barra de Progresso -->
        <div class="mt-4">
            <div class="flex justify-between items-center mb-2">
                <template x-for="bloco in [1,2,3,4,5]" :key="bloco">
                    <button @click="irParaBloco(bloco)" 
                            :class="bloco <= maxBlocoAcessivel ? 'cursor-pointer' : 'cursor-not-allowed'"
                            class="px-3 py-1 rounded text-sm font-medium transition"
                            :class="{
                                'bg-primary text-white': blocoAtual === bloco,
                                'bg-green-100 text-green-700 hover:bg-green-200': bloco < blocoAtual,
                                'bg-gray-200 text-gray-600 hover:bg-gray-300': bloco === maxBlocoAcessivel && bloco !== blocoAtual,
                                'bg-gray-100 text-gray-400': bloco > maxBlocoAcessivel
                            }">
                        <span x-text="titulos[bloco] || 'Bloco ' + bloco"></span>
                    </button>
                </template>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-primary h-2 rounded-full transition-all duration-500" 
                     :style="'width: ' + (blocoAtual * 20) + '%'"></div>
            </div>
        </div>
    </div>

    <!-- Formulário do Wizard -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
        <form @submit.prevent="salvarEAvancar()">
            <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
            <input type="hidden" name="rascunho_id" value="<?= $dados['rascunho']['id'] ?>">

            <!-- BLOCO 1 -->
            <div x-show="blocoAtual === 1" class="space-y-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6" x-text="titulos[1]"></h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Nome da Empresa -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Nome da Empresa *</label>
                        <input type="text" x-model="form.empresa_nome" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                               placeholder="Digite o nome completo da empresa">
                    </div>

                    <!-- Setor -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Setor de Atuação *</label>
                        <select x-model="form.setor" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Selecione o setor</option>
                            <?php foreach ($dados['opcoes']['setores'] as $setor): ?>
                                <option value="<?= $setor ?>"><?= htmlspecialchars($setor) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Tempo de Existência -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Tempo de Existência</label>
                        <select x-model="form.tempo_existencia"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Selecione</option>
                            <?php foreach ($dados['opcoes']['tempo_existencia'] as $tempo): ?>
                                <option value="<?= $tempo ?>"><?= htmlspecialchars($tempo) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Estrutura Societária -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Estrutura Societária</label>
                        <select x-model="form.estrutura_societaria"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Selecione</option>
                            <?php foreach ($dados['opcoes']['estrutura_societaria'] as $estrutura): ?>
                                <option value="<?= $estrutura ?>"><?= htmlspecialchars($estrutura) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Unidades/Filiais -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Número de Unidades/Filiais</label>
                        <input type="number" x-model="form.unidades_filiais" min="1"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                               placeholder="1">
                    </div>

                    <!-- Língua Principal -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Língua Principal</label>
                        <select x-model="form.lingua_principal"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <?php foreach ($dados['opcoes']['linguas'] as $lingua): ?>
                                <option value="<?= $lingua ?>"><?= htmlspecialchars($lingua) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Descrição -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Breve Descrição da Empresa</label>
                    <textarea x-model="form.descricao" rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none"
                              placeholder="Descreva brevemente a atividade principal da empresa, produtos/serviços oferecidos..."></textarea>
                </div>
            </div>

            <!-- BLOCO 2 -->
            <div x-show="blocoAtual === 2" class="space-y-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6" x-text="titulos[2]"></h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Colaboradores Internos -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Colaboradores Internos</label>
                        <input type="number" x-model="form.colaboradores_internos" min="0"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                               placeholder="0">
                    </div>

                    <!-- Colaboradores Externos -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Colaboradores Externos/Terceirizados</label>
                        <input type="number" x-model="form.colaboradores_externos" min="0"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                               placeholder="0">
                    </div>

                    <!-- Clientes Ativos -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Número de Clientes Ativos</label>
                        <input type="number" x-model="form.clientes_ativos" min="0"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                               placeholder="0">
                    </div>

                    <!-- Faturamento Mensal -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Faturamento Mensal</label>
                        <select x-model="form.faturamento_mensal"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Selecione</option>
                            <?php foreach ($dados['opcoes']['faturamento'] as $faturamento): ?>
                                <option value="<?= $faturamento ?>"><?= htmlspecialchars($faturamento) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Ticket Médio -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Ticket Médio (Valor médio por cliente)</label>
                        <input type="text" x-model="form.ticket_medio"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"
                               placeholder="Ex: R$ 1.500,00">
                    </div>

                    <!-- Sites de Referência -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Sites de Referência (Opcional)</label>
                        <textarea x-model="form.sites_referencia" rows="2"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none"
                                  placeholder="Sites do seu setor que vocês acompanham..."></textarea>
                    </div>
                </div>

                <!-- Departamentos -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-3">Departamentos/Setores Existentes</label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <?php foreach ($dados['opcoes']['departamentos'] as $dept): ?>
                        <label class="flex items-center gap-2 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox" x-model="form.departamentos" value="<?= $dept ?>" 
                                   class="text-primary focus:ring-primary/20">
                            <span class="text-sm"><?= htmlspecialchars($dept) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Principais Produtos/Serviços -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Principais Produtos/Serviços</label>
                    <textarea x-model="form.produtos_servicos" rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none"
                              placeholder="Descreva os principais produtos ou serviços oferecidos pela empresa..."></textarea>
                </div>
            </div>

            <!-- BLOCO 3 -->
            <div x-show="blocoAtual === 3" class="space-y-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6" x-text="titulos[3]"></h2>
                
                <!-- Seção: Estrutura Financeira -->
                <div class="border-l-4 border-accent pl-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">💰 Estrutura Financeira</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Sistema de Gestão Financeira -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Sistema de Gestão Financeira</label>
                            <select x-model="form.sistema_financeiro"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="">Selecione</option>
                                <option value="planilhas">Planilhas Excel/Google</option>
                                <option value="sistema-basico">Sistema básico (ex: Conta Azul, Nibo)</option>
                                <option value="erp-completo">ERP completo (ex: SAP, Protheus)</option>
                                <option value="nao-tem">Não possui controle estruturado</option>
                            </select>
                        </div>

                        <!-- Fluxo de Caixa -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Controle de Fluxo de Caixa</label>
                            <select x-model="form.controle_fluxo_caixa"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="">Selecione</option>
                                <option value="diario">Diário e atualizado</option>
                                <option value="semanal">Semanal</option>
                                <option value="mensal">Mensal</option>
                                <option value="eventual">Eventual/quando lembra</option>
                                <option value="nao-tem">Não possui controle</option>
                            </select>
                        </div>

                        <!-- Margem de Lucro -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Margem de Lucro Líquido (%)</label>
                            <select x-model="form.margem_lucro"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="">Selecione</option>
                                <option value="negativa">Negativa (prejuízo)</option>
                                <option value="0-5">0% - 5%</option>
                                <option value="6-10">6% - 10%</option>
                                <option value="11-20">11% - 20%</option>
                                <option value="acima-20">Acima de 20%</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Seção: Estrutura Comercial -->
                <div class="border-l-4 border-primary pl-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">🎯 Estrutura Comercial</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- CRM -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Sistema de CRM</label>
                            <select x-model="form.sistema_crm"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="">Selecione</option>
                                <option value="crm-profissional">CRM profissional (HubSpot, Salesforce)</option>
                                <option value="planilhas">Planilhas</option>
                                <option value="whatsapp-apenas">Apenas WhatsApp</option>
                                <option value="nao-tem">Não possui controle de leads</option>
                            </select>
                        </div>

                        <!-- Taxa de Conversão -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Taxa de Conversão de Leads</label>
                            <select x-model="form.taxa_conversao"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="">Selecione</option>
                                <option value="nao-sei">Não sei / Não meço</option>
                                <option value="menos-5">Menos de 5%</option>
                                <option value="5-15">5% - 15%</option>
                                <option value="16-30">16% - 30%</option>
                                <option value="acima-30">Acima de 30%</option>
                            </select>
                        </div>
                    </div>

                    <!-- Canais de Vendas -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Principais Canais de Vendas</label>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                            <label class="flex items-center gap-2 p-2 border border-gray-200 rounded cursor-pointer hover:bg-gray-50">
                                <input type="checkbox" x-model="form.canais_vendas" value="loja-fisica" 
                                       class="rounded border-gray-300 text-primary focus:ring-primary/20">
                                <span class="text-sm">Loja física</span>
                            </label>
                            <label class="flex items-center gap-2 p-2 border border-gray-200 rounded cursor-pointer hover:bg-gray-50">
                                <input type="checkbox" x-model="form.canais_vendas" value="site-proprio" 
                                       class="rounded border-gray-300 text-primary focus:ring-primary/20">
                                <span class="text-sm">Site próprio</span>
                            </label>
                            <label class="flex items-center gap-2 p-2 border border-gray-200 rounded cursor-pointer hover:bg-gray-50">
                                <input type="checkbox" x-model="form.canais_vendas" value="marketplaces" 
                                       class="rounded border-gray-300 text-primary focus:ring-primary/20">
                                <span class="text-sm">Marketplaces</span>
                            </label>
                            <label class="flex items-center gap-2 p-2 border border-gray-200 rounded cursor-pointer hover:bg-gray-50">
                                <input type="checkbox" x-model="form.canais_vendas" value="redes-sociais" 
                                       class="rounded border-gray-300 text-primary focus:ring-primary/20">
                                <span class="text-sm">Redes sociais</span>
                            </label>
                            <label class="flex items-center gap-2 p-2 border border-gray-200 rounded cursor-pointer hover:bg-gray-50">
                                <input type="checkbox" x-model="form.canais_vendas" value="indicacoes" 
                                       class="rounded border-gray-300 text-primary focus:ring-primary/20">
                                <span class="text-sm">Indicações</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Observações -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Observações sobre Financeiro/Comercial</label>
                    <textarea x-model="form.observacoes_bloco3" rows="4"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none"
                              placeholder="Descreva desafios específicos, metas comerciais, dificuldades financeiras, etc."></textarea>
                </div>
            </div>

            <!-- BLOCO 4 -->
            <div x-show="blocoAtual === 4" class="space-y-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6" x-text="titulos[4]"></h2>
                
                <!-- Seção: Gestão de Pessoas -->
                <div class="border-l-4 border-green-500 pl-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">👥 Gestão de Pessoas</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Estrutura Organizacional -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Estrutura Organizacional</label>
                            <select x-model="form.estrutura_organizacional"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="">Selecione</option>
                                <option value="organograma-formal">Organograma formal e atualizado</option>
                                <option value="organograma-basico">Organograma básico</option>
                                <option value="estrutura-mental">Apenas "na cabeça" do líder</option>
                                <option value="nao-tem">Não possui estrutura definida</option>
                            </select>
                        </div>

                        <!-- Turnover -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Taxa de Rotatividade (Turnover)</label>
                            <select x-model="form.taxa_turnover"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="">Selecione</option>
                                <option value="muito-baixa">Muito baixa (0-5%/ano)</option>
                                <option value="baixa">Baixa (6-15%/ano)</option>
                                <option value="media">Média (16-30%/ano)</option>
                                <option value="alta">Alta (31-50%/ano)</option>
                                <option value="muito-alta">Muito alta (acima 50%/ano)</option>
                                <option value="nao-mede">Não meço/acompanho</option>
                            </select>
                        </div>

                        <!-- Capacitação -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Programa de Capacitação</label>
                            <select x-model="form.programa_capacitacao"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="">Selecione</option>
                                <option value="programa-formal">Programa formal de treinamento</option>
                                <option value="treinamentos-esporadicos">Treinamentos esporádicos</option>
                                <option value="capacitacao-informal">Capacitação informal (no dia a dia)</option>
                                <option value="nao-tem">Não possui programa estruturado</option>
                            </select>
                        </div>
                    </div>

                    <!-- Políticas de RH -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Políticas de RH Formalizadas</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <label class="flex items-center gap-2 p-2 border border-gray-200 rounded cursor-pointer hover:bg-gray-50">
                                <input type="checkbox" x-model="form.politicas_rh" value="manual-funcionario" 
                                       class="rounded border-gray-300 text-primary focus:ring-primary/20">
                                <span class="text-sm">Manual do funcionário</span>
                            </label>
                            <label class="flex items-center gap-2 p-2 border border-gray-200 rounded cursor-pointer hover:bg-gray-50">
                                <input type="checkbox" x-model="form.politicas_rh" value="codigo-conduta" 
                                       class="rounded border-gray-300 text-primary focus:ring-primary/20">
                                <span class="text-sm">Código de conduta</span>
                            </label>
                            <label class="flex items-center gap-2 p-2 border border-gray-200 rounded cursor-pointer hover:bg-gray-50">
                                <input type="checkbox" x-model="form.politicas_rh" value="plano-cargos" 
                                       class="rounded border-gray-300 text-primary focus:ring-primary/20">
                                <span class="text-sm">Plano de cargos e salários</span>
                            </label>
                            <label class="flex items-center gap-2 p-2 border border-gray-200 rounded cursor-pointer hover:bg-gray-50">
                                <input type="checkbox" x-model="form.politicas_rh" value="avaliacao-desempenho" 
                                       class="rounded border-gray-300 text-primary focus:ring-primary/20">
                                <span class="text-sm">Processo de avaliação de desempenho</span>
                            </label>
                            <label class="flex items-center gap-2 p-2 border border-gray-200 rounded cursor-pointer hover:bg-gray-50">
                                <input type="checkbox" x-model="form.politicas_rh" value="nenhuma" 
                                       class="rounded border-gray-300 text-primary focus:ring-primary/20">
                                <span class="text-sm">Nenhuma política formalizada</span>
                            </label>
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
                            <select x-model="form.mapeamento_riscos"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="">Selecione</option>
                                <option value="matriz-formal">Matriz de riscos formalizada</option>
                                <option value="listagem-basica">Listagem básica de riscos</option>
                                <option value="conhecimento-tacito">Conhece os riscos mas não mapeou</option>
                                <option value="nao-tem">Nunca pensou sistematicamente nos riscos</option>
                            </select>
                        </div>

                        <!-- Backup e Continuidade -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Backup e Continuidade de Negócio</label>
                            <select x-model="form.backup_continuidade"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="">Selecione</option>
                                <option value="plano-formal">Plano de continuidade formal e testado</option>
                                <option value="backup-automatico">Backup automático em nuvem</option>
                                <option value="backup-manual">Backup manual esporádico</option>
                                <option value="nao-tem">Não possui backup estruturado</option>
                            </select>
                        </div>

                        <!-- Conformidade Regulatória -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Conformidade Regulatória</label>
                            <select x-model="form.conformidade_regulatoria"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="">Selecione</option>
                                <option value="totalmente-conforme">Totalmente em conformidade</option>
                                <option value="conforme-basico">Conforme com obrigatórias básicas</option>
                                <option value="algumas-pendencias">Algumas pendências menores</option>
                                <option value="muitas-pendencias">Muitas pendências regulatórias</option>
                                <option value="nao-sei">Não sei o que é necessário</option>
                            </select>
                        </div>
                    </div>

                    <!-- Seguros -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Cobertura de Seguros</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <label class="flex items-center gap-2 p-2 border border-gray-200 rounded cursor-pointer hover:bg-gray-50">
                                <input type="checkbox" x-model="form.seguros" value="responsabilidade-civil" 
                                       class="rounded border-gray-300 text-primary focus:ring-primary/20">
                                <span class="text-sm">Responsabilidade civil</span>
                            </label>
                            <label class="flex items-center gap-2 p-2 border border-gray-200 rounded cursor-pointer hover:bg-gray-50">
                                <input type="checkbox" x-model="form.seguros" value="patrimonial" 
                                       class="rounded border-gray-300 text-primary focus:ring-primary/20">
                                <span class="text-sm">Seguro patrimonial</span>
                            </label>
                            <label class="flex items-center gap-2 p-2 border border-gray-200 rounded cursor-pointer hover:bg-gray-50">
                                <input type="checkbox" x-model="form.seguros" value="d-o" 
                                       class="rounded border-gray-300 text-primary focus:ring-primary/20">
                                <span class="text-sm">Seguro D&O (diretores e administradores)</span>
                            </label>
                            <label class="flex items-center gap-2 p-2 border border-gray-200 rounded cursor-pointer hover:bg-gray-50">
                                <input type="checkbox" x-model="form.seguros" value="cyber" 
                                       class="rounded border-gray-300 text-primary focus:ring-primary/20">
                                <span class="text-sm">Cyber segurança</span>
                            </label>
                            <label class="flex items-center gap-2 p-2 border border-gray-200 rounded cursor-pointer hover:bg-gray-50">
                                <input type="checkbox" x-model="form.seguros" value="nenhum" 
                                       class="rounded border-gray-300 text-primary focus:ring-primary/20">
                                <span class="text-sm">Não possui seguros</span>
                            </label>
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
                            <select x-model="form.dependencia_pessoas"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="">Selecione</option>
                                <option value="totalmente-dependente">Totalmente dependente do proprietário/sócio</option>
                                <option value="algumas-pessoas">Depende de 2-3 pessoas específicas</option>
                                <option value="conhecimento-distribuido">Conhecimento distribuído na equipe</option>
                                <option value="processos-documentados">Processos documentados, baixa dependência</option>
                            </select>
                        </div>

                        <!-- Dependência de Fornecedores -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Dependência de Fornecedores/Clientes</label>
                            <select x-model="form.dependencia_fornecedores"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="">Selecione</option>
                                <option value="um-fornecedor-critico">1 fornecedor crítico (>80% dependência)</option>
                                <option value="poucos-fornecedores">2-3 fornecedores importantes</option>
                                <option value="fornecedores-diversificados">Fornecedores diversificados</option>
                                <option value="cliente-concentrado">>50% receita vem de 1-2 clientes</option>
                                <option value="carteira-pulverizada">Carteira de clientes pulverizada</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Observações -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Observações sobre Pessoas e Riscos</label>
                    <textarea x-model="form.observacoes_bloco4" rows="4"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none"
                              placeholder="Descreva principais desafios de RH, riscos identificados, dependências críticas específicas, etc."></textarea>
                </div>
            </div>

            <!-- BLOCO 5 -->
            <div x-show="blocoAtual === 5" class="space-y-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6" x-text="titulos[5]"></h2>
                
                <div class="space-y-6">
                    <!-- Pontos Fortes -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Principais Pontos Fortes da Empresa</label>
                        <textarea x-model="form.pontos_fortes" rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none"
                                  placeholder="Quais são os maiores diferenciais e pontos fortes da empresa?"></textarea>
                    </div>

                    <!-- Pontos de Melhoria -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Principais Pontos de Melhoria</label>
                        <textarea x-model="form.pontos_melhoria" rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none"
                                  placeholder="Quais são as maiores oportunidades de melhoria identificadas?"></textarea>
                    </div>

                    <!-- Objetivo 12 meses -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Principal Objetivo para os Próximos 12 Meses</label>
                        <textarea x-model="form.objetivo_12_meses" rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none"
                                  placeholder="Qual é o principal objetivo ou meta da empresa para o próximo ano?"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Maturidade Percebida -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Como você avalia a maturidade atual da empresa?</label>
                            <select x-model="form.maturidade_percebida"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="1">1 - Inicial/Básica</option>
                                <option value="2">2 - Em desenvolvimento</option>
                                <option value="3" selected>3 - Crescimento estruturado</option>
                                <option value="4">4 - Excelência operacional</option>
                            </select>
                        </div>

                        <!-- Planejamento Documentado -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">A empresa possui planejamento estratégico documentado?</label>
                            <select x-model="form.planejamento_documentado"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="nao">Não</option>
                                <option value="sim">Sim</option>
                            </select>
                        </div>

                        <!-- Frequência de Reuniões -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Frequência de Reuniões Estratégicas</label>
                            <select x-model="form.frequencia_reunioes"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="">Selecione</option>
                                <?php foreach ($dados['opcoes']['frequencia_reunioes'] as $freq): ?>
                                    <option value="<?= $freq ?>"><?= htmlspecialchars($freq) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Meta de Faturamento -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">A empresa possui metas de faturamento definidas?</label>
                            <select x-model="form.meta_faturamento"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="nao">Não</option>
                                <option value="sim">Sim</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botões de Navegação -->
            <div class="flex justify-between pt-6 border-t">
                <button type="button" @click="voltarBloco()" :disabled="blocoAtual === 1"
                        class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition">
                    ← Anterior
                </button>

                <div class="flex gap-3">
                    <!-- Botão Próximo/Finalizar -->
                    <button type="submit" :disabled="loading"
                            class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
                            :class="{ 'opacity-50': loading }">
                        <span x-show="!loading" x-text="blocoAtual === 5 ? 'Finalizar' : 'Próximo →'"></span>
                        <span x-show="loading">Salvando...</span>
                    </button>

                    <!-- Botão Gerar Diagnóstico -->
                    <button type="button" @click="gerarDiagnostico()" x-show="blocoAtual === 5" :disabled="generating"
                            class="px-6 py-3 bg-accent text-white rounded-lg hover:bg-orange-600 disabled:opacity-50 disabled:cursor-not-allowed transition"
                            :class="{ 'opacity-50': generating }">
                        <span x-show="!generating">🚀 Gerar Diagnóstico</span>
                        <span x-show="generating" class="flex items-center gap-2">
                            <div class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                            Gerando...
                        </span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
const APP_URL = '<?= APP_URL ?>';

function diagnosticoWizard() {
    return {
        loading: false,
        generating: false,
        blocoAtual: 1,
        maxBlocoAcessivel: 1,
        titulos: {
            1: 'Identificação da Empresa',
            2: 'Estrutura Operacional',
            3: 'Financeiro e Comercial',
            4: 'Gestão de Pessoas e Riscos',
            5: 'Contexto Estratégico'
        },
        form: {
            // Bloco 1 - Identificação da Empresa
            empresa_nome: '<?= addslashes($dados['rascunho']['empresa_nome'] ?? '') ?>',
            setor: '<?= addslashes($dados['rascunho']['setor'] ?? '') ?>',
            tempo_existencia: '<?= addslashes($dados['rascunho']['tempo_existencia'] ?? '') ?>',
            estrutura_societaria: '<?= addslashes($dados['rascunho']['estrutura_societaria'] ?? '') ?>',
            unidades_filiais: '<?= $dados['rascunho']['unidades_filiais'] ?? 1 ?>',
            lingua_principal: '<?= addslashes($dados['rascunho']['lingua_principal'] ?? 'Português') ?>',
            descricao: '<?= addslashes($dados['rascunho']['descricao'] ?? '') ?>',

            // Bloco 2 - Estrutura Operacional
            colaboradores_internos: '<?= $dados['rascunho']['colaboradores_internos'] ?? '' ?>',
            colaboradores_externos: '<?= $dados['rascunho']['colaboradores_externos'] ?? '' ?>',
            clientes_ativos: '<?= $dados['rascunho']['clientes_ativos'] ?? '' ?>',
            faturamento_mensal: '<?= addslashes($dados['rascunho']['faturamento_mensal'] ?? '') ?>',
            ticket_medio: '<?= addslashes($dados['rascunho']['ticket_medio'] ?? '') ?>',
            sites_referencia: '<?= addslashes($dados['rascunho']['sites_referencia'] ?? '') ?>',
            departamentos: <?= json_encode(json_decode($dados['rascunho']['departamentos'] ?? '[]', true) ?: []) ?>,
            produtos_servicos: '<?= addslashes($dados['rascunho']['produtos_servicos'] ?? '') ?>',
            
            // Bloco 3 - Financeiro e Comercial
            sistema_financeiro: '<?= addslashes($dados['rascunho']['sistema_financeiro'] ?? '') ?>',
            controle_fluxo_caixa: '<?= addslashes($dados['rascunho']['controle_fluxo_caixa'] ?? '') ?>',
            margem_lucro: '<?= addslashes($dados['rascunho']['margem_lucro'] ?? '') ?>',
            sistema_crm: '<?= addslashes($dados['rascunho']['sistema_crm'] ?? '') ?>',
            taxa_conversao: '<?= addslashes($dados['rascunho']['taxa_conversao'] ?? '') ?>',
            canais_vendas: <?= json_encode(explode(',', $dados['rascunho']['canais_vendas'] ?? '') ?: []) ?>,
            observacoes_bloco3: '<?= addslashes($dados['rascunho']['observacoes_bloco3'] ?? '') ?>',

            // Bloco 4 - Gestão de Pessoas e Riscos
            estrutura_organizacional: '<?= addslashes($dados['rascunho']['estrutura_organizacional'] ?? '') ?>',
            taxa_turnover: '<?= addslashes($dados['rascunho']['taxa_turnover'] ?? '') ?>',
            programa_capacitacao: '<?= addslashes($dados['rascunho']['programa_capacitacao'] ?? '') ?>',
            politicas_rh: <?= json_encode(explode(',', $dados['rascunho']['politicas_rh'] ?? '') ?: []) ?>,
            mapeamento_riscos: '<?= addslashes($dados['rascunho']['mapeamento_riscos'] ?? '') ?>',
            backup_continuidade: '<?= addslashes($dados['rascunho']['backup_continuidade'] ?? '') ?>',
            conformidade_regulatoria: '<?= addslashes($dados['rascunho']['conformidade_regulatoria'] ?? '') ?>',
            seguros: <?= json_encode(explode(',', $dados['rascunho']['seguros'] ?? '') ?: []) ?>,
            dependencia_pessoas: '<?= addslashes($dados['rascunho']['dependencia_pessoas'] ?? '') ?>',
            dependencia_fornecedores: '<?= addslashes($dados['rascunho']['dependencia_fornecedores'] ?? '') ?>',
            observacoes_bloco4: '<?= addslashes($dados['rascunho']['observacoes_bloco4'] ?? '') ?>',

            // Bloco 5 - Contexto Estratégico
            pontos_fortes: '<?= addslashes($dados['rascunho']['pontos_fortes'] ?? '') ?>',
            pontos_melhoria: '<?= addslashes($dados['rascunho']['pontos_melhoria'] ?? '') ?>',
            objetivo_12_meses: '<?= addslashes($dados['rascunho']['objetivo_12_meses'] ?? '') ?>',
            maturidade_percebida: '<?= $dados['rascunho']['maturidade_percebida'] ?? 3 ?>',
            planejamento_documentado: '<?= addslashes($dados['rascunho']['planejamento_documentado'] ?? 'nao') ?>',
            frequencia_reunioes: '<?= addslashes($dados['rascunho']['frequencia_reunioes'] ?? '') ?>',
            meta_faturamento: '<?= addslashes($dados['rascunho']['meta_faturamento'] ?? 'nao') ?>'
        },

        init() {
            this.maxBlocoAcessivel = Math.max(1, <?= (int)($dados['rascunho']['bloco_atual'] ?? 1) ?>);
        },

        irParaBloco(bloco) {
            if (bloco <= this.maxBlocoAcessivel) {
                this.blocoAtual = bloco;
            }
        },

        voltarBloco() {
            if (this.blocoAtual > 1) {
                this.blocoAtual--;
            }
        },

        async salvarEAvancar() {
            console.log('salvarEAvancar chamado, bloco atual:', this.blocoAtual);
            this.loading = true;

            try {
                const formData = new FormData();
                formData.append('csrf_token', '<?= Csrf::token() ?>');
                formData.append('bloco', this.blocoAtual);
                formData.append('rascunho_id', '<?= $dados['rascunho']['id'] ?>');

                // Adicionar todos os dados do formulário
                Object.keys(this.form).forEach(key => {
                    const value = this.form[key];
                    
                    // Tratar arrays (checkboxes)
                    if (Array.isArray(value)) {
                        if (key === 'departamentos' && value.length > 0) {
                            value.forEach(v => formData.append('departamentos[]', v));
                        } else if (key === 'canais_vendas' && value.length > 0) {
                            value.forEach(v => formData.append('canais_vendas[]', v));
                        } else if (key === 'politicas_rh' && value.length > 0) {
                            value.forEach(v => formData.append('politicas_rh[]', v));
                        } else if (key === 'seguros' && value.length > 0) {
                            value.forEach(v => formData.append('seguros[]', v));
                        }
                    } else if (value && value !== '') {
                        formData.append(key, value);
                    }
                });

                console.log('Enviando dados para:', APP_URL + '/diagnostico/salvar-bloco');
                
                const response = await fetch(APP_URL + '/diagnostico/salvar-bloco', {
                    method: 'POST',
                    body: formData
                });

                console.log('Resposta recebida:', response.status);
                
                const result = await response.json();
                console.log('Resultado:', result);

                if (result.sucesso) {
                    // Atualizar bloco máximo acessível
                    this.maxBlocoAcessivel = Math.max(this.maxBlocoAcessivel, this.blocoAtual + 1);
                    
                    // Avançar para próximo bloco se não for o último
                    if (this.blocoAtual < 5) {
                        this.blocoAtual++;
                    } else {
                        // Estamos no bloco 5, forçar a atualização para bloco_atual = 5
                        await this.forcarAtualizacaoBloco5();
                        this.showToast('Diagnóstico salvo! Clique em "Gerar Diagnóstico" para finalizar.', 'success');
                    }
                } else {
                    this.showToast(result.mensagem || 'Erro ao salvar', 'error');
                }
            } catch (error) {
                console.error('Erro em salvarEAvancar:', error);
                this.showToast('Erro na conexão: ' + error.message, 'error');
            } finally {
                this.loading = false;
            }
        },

        async gerarDiagnostico() {
            console.log('gerarDiagnostico chamado');
            this.generating = true;

            try {
                // Primeiro, salvar o bloco atual se estamos no bloco 5
                if (this.blocoAtual === 5) {
                    console.log('Salvando bloco 5 antes de gerar diagnóstico...');
                    await this.salvarBlocoSilencioso();
                }

                const formData = new FormData();
                formData.append('csrf_token', '<?= Csrf::token() ?>');
                formData.append('rascunho_id', '<?= $dados['rascunho']['id'] ?>');

                console.log('Gerando diagnóstico para rascunho:', '<?= $dados['rascunho']['id'] ?>');

                const response = await fetch(APP_URL + '/diagnostico/gerar', {
                    method: 'POST',
                    body: formData
                });

                console.log('Resposta gerar:', response.status);
                
                const result = await response.json();
                console.log('Resultado gerar:', result);

                if (result.sucesso) {
                    console.log('Redirecionando para:', result.redirect);
                    window.location.href = result.redirect || APP_URL + '/diagnostico/resultado';
                } else {
                    this.showToast(result.mensagem || 'Erro ao gerar diagnóstico', 'error');
                }
            } catch (error) {
                console.error('Erro em gerarDiagnostico:', error);
                this.showToast('Erro na conexão: ' + error.message, 'error');
            } finally {
                this.generating = false;
            }
        },

        async salvarBlocoSilencioso() {
            try {
                const formData = new FormData();
                formData.append('csrf_token', '<?= Csrf::token() ?>');
                formData.append('bloco', this.blocoAtual);
                formData.append('rascunho_id', '<?= $dados['rascunho']['id'] ?>');

                // Adicionar todos os dados do formulário
                Object.keys(this.form).forEach(key => {
                    const value = this.form[key];
                    
                    if (Array.isArray(value)) {
                        if (key === 'departamentos' && value.length > 0) {
                            value.forEach(v => formData.append('departamentos[]', v));
                        } else if (key === 'canais_vendas' && value.length > 0) {
                            value.forEach(v => formData.append('canais_vendas[]', v));
                        } else if (key === 'politicas_rh' && value.length > 0) {
                            value.forEach(v => formData.append('politicas_rh[]', v));
                        } else if (key === 'seguros' && value.length > 0) {
                            value.forEach(v => formData.append('seguros[]', v));
                        }
                    } else if (value && value !== '') {
                        formData.append(key, value);
                    }
                });

                await fetch(APP_URL + '/diagnostico/salvar-bloco', {
                    method: 'POST',
                    body: formData
                });
            } catch (error) {
                console.error('Erro ao salvar silenciosamente:', error);
            }
        },

        showToast(message, type = 'success') {
            console.log('Toast:', type, message);
            // Toast simples
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg text-white shadow-md ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => toast.remove(), 5000);
        },

        async forcarAtualizacaoBloco5() {
            try {
                const formData = new FormData();
                formData.append('csrf_token', '<?= Csrf::token() ?>');
                formData.append('bloco', 5);
                formData.append('rascunho_id', '<?= $dados['rascunho']['id'] ?>');
                formData.append('forcar_bloco_5', 'true');
                
                console.log('Forçando atualização do bloco 5');
                
                const response = await fetch(APP_URL + '/diagnostico/salvar-bloco', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                console.log('Resultado forçar bloco 5:', result);
            } catch (error) {
                console.error('Erro ao forçar bloco 5:', error);
            }
        }
    };
}
</script>

</body>
</html>