<?php $tituloPagina = 'Novo Diagnóstico'; ?>
<?php ob_start(); ?>

<!-- Breadcrumb -->
<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li><a href="<?= APP_URL ?>/diagnostico" class="hover:text-primary">Diagnósticos</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Novo Diagnóstico</li>
    </ol>
</nav>

<div x-data="diagnosticoWizard()" class="max-w-4xl mx-auto">

    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Diagnóstico Empresarial</h1>
        <p class="text-gray-500 mt-1">Preencha os 5 blocos para gerar a avaliação de maturidade.</p>
    </div>

    <!-- Barra de Progresso -->
    <div class="mb-8">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-600">Bloco <span x-text="blocoAtual"></span> de 5</span>
            <span class="text-sm text-gray-400" x-text="Math.round((blocoAtual / 5) * 100) + '%'"></span>
        </div>
        <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
            <div class="h-full bg-accent rounded-full transition-all duration-500" :style="'width:' + (blocoAtual / 5 * 100) + '%'"></div>
        </div>
        <!-- Steps -->
        <div class="flex justify-between mt-3">
            <template x-for="(step, i) in steps" :key="i">
                <button @click="irParaBloco(i + 1)" 
                        :class="blocoAtual === i + 1 ? 'bg-primary text-white' : (i + 1 < blocoAtual ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-500')"
                        class="w-8 h-8 rounded-full text-xs font-semibold flex items-center justify-center transition" x-text="i + 1">
                </button>
            </template>
        </div>
        <div class="flex justify-between mt-1">
            <span class="text-[10px] text-gray-400 w-8 text-center">Ident.</span>
            <span class="text-[10px] text-gray-400 w-8 text-center">Estrut.</span>
            <span class="text-[10px] text-gray-400 w-8 text-center">Oper.</span>
            <span class="text-[10px] text-gray-400 w-8 text-center">Riscos</span>
            <span class="text-[10px] text-gray-400 w-8 text-center">Estrat.</span>
        </div>
    </div>

    <!-- Formulário -->
    <form id="form-diagnostico" @submit.prevent="salvar()">
        <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">

        <!-- BLOCO 1 — IDENTIFICAÇÃO -->
        <div x-show="blocoAtual === 1" x-transition class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">Identificação da Empresa</h2>
                    <p class="text-sm text-gray-500">Dados básicos da organização</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome da empresa *</label>
                    <input type="text" name="empresa_nome" x-model="form.empresa_nome" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none"
                           placeholder="Nome completo da empresa">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Setor / Nicho *</label>
                    <select name="setor" x-model="form.setor" required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                        <option value="">Selecione...</option>
                        <?php foreach ($dados['opcoes']['setores'] as $setor): ?>
                        <option value="<?= htmlspecialchars($setor) ?>"><?= htmlspecialchars($setor) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tempo de existência *</label>
                    <select name="tempo_existencia" x-model="form.tempo_existencia" required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                        <option value="">Selecione...</option>
                        <?php foreach ($dados['opcoes']['tempo_existencia'] as $te): ?>
                        <option value="<?= htmlspecialchars($te) ?>"><?= htmlspecialchars($te) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição resumida</label>
                    <textarea name="descricao" x-model="form.descricao" maxlength="300" rows="3"
                              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none resize-none"
                              placeholder="Breve descrição da empresa e atividade principal (máx 300 caracteres)"></textarea>
                    <p class="text-xs text-gray-400 mt-1" x-text="(form.descricao?.length || 0) + '/300'"></p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estrutura societária</label>
                    <select name="estrutura_societaria" x-model="form.estrutura_societaria"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                        <option value="">Selecione...</option>
                        <?php foreach ($dados['opcoes']['estrutura_societaria'] as $es): ?>
                        <option value="<?= htmlspecialchars($es) ?>"><?= htmlspecialchars($es) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Unidades / Filiais</label>
                    <input type="number" name="unidades_filiais" x-model="form.unidades_filiais" min="1"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none"
                           placeholder="1">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Língua principal</label>
                    <select name="lingua_principal" x-model="form.lingua_principal"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                        <?php foreach ($dados['opcoes']['linguas'] as $lingua): ?>
                        <option value="<?= htmlspecialchars($lingua) ?>"><?= htmlspecialchars($lingua) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- BLOCO 2 — ESTRUTURA OPERACIONAL -->
        <div x-show="blocoAtual === 2" x-transition class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-lg bg-green-100 text-green-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">Estrutura Operacional</h2>
                    <p class="text-sm text-gray-500">Equipe, departamentos e faturamento</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Colaboradores internos</label>
                    <input type="number" name="colaboradores_internos" x-model="form.colaboradores_internos" min="0"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none" placeholder="0">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Colaboradores externos</label>
                    <input type="number" name="colaboradores_externos" x-model="form.colaboradores_externos" min="0"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none" placeholder="0">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Departamentos existentes *</label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
                        <?php foreach ($dados['opcoes']['departamentos'] as $dept): ?>
                        <label class="flex items-center gap-2 p-2 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer text-sm">
                            <input type="checkbox" name="departamentos[]" value="<?= htmlspecialchars($dept) ?>"
                                   x-model="form.departamentos"
                                   class="w-4 h-4 text-primary rounded border-gray-300 focus:ring-primary">
                            <span class="text-gray-700"><?= htmlspecialchars($dept) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Clientes ativos</label>
                    <input type="number" name="clientes_ativos" x-model="form.clientes_ativos" min="0"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none" placeholder="0">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ticket médio</label>
                    <input type="text" name="ticket_medio" x-model="form.ticket_medio"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none" placeholder="R$ 0,00">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Principais produtos/serviços</label>
                    <textarea name="produtos_servicos" x-model="form.produtos_servicos" rows="2"
                              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none resize-none"
                              placeholder="Descreva os principais produtos ou serviços oferecidos"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Faturamento mensal</label>
                    <select name="faturamento_mensal" x-model="form.faturamento_mensal"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                        <option value="">Selecione...</option>
                        <?php foreach ($dados['opcoes']['faturamento'] as $f): ?>
                        <option value="<?= htmlspecialchars($f) ?>"><?= htmlspecialchars($f) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sites e portais de referência do setor <span class="text-gray-400">(opcional)</span></label>
                    <textarea name="sites_referencia" x-model="form.sites_referencia" rows="2"
                              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none resize-none"
                              placeholder="URLs de portais, blogs e sites que a empresa acompanha (usado para busca de conteúdo)"></textarea>
                </div>
            </div>
        </div>

        <!-- BLOCO 3 — OPERAÇÃO ATUAL -->
        <div x-show="blocoAtual === 3" x-transition class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">Operação Atual</h2>
                    <p class="text-sm text-gray-500">Processos, ferramentas e integrações</p>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Processo do primeiro contato até a entrega *</label>
                    <textarea name="processo_entrega" x-model="form.processo_entrega" rows="4" required
                              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none resize-none"
                              placeholder="Descreva como funciona desde o primeiro contato com o cliente até a entrega final do produto/serviço"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ferramentas e softwares utilizados</label>
                    <textarea name="ferramentas_softwares" x-model="form.ferramentas_softwares" rows="3"
                              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none resize-none"
                              placeholder="Liste as ferramentas e softwares utilizados no dia a dia (ERPs, CRMs, planilhas, etc.)"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fornecedores críticos</label>
                        <textarea name="fornecedores_criticos" x-model="form.fornecedores_criticos" rows="2"
                                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none resize-none"
                                  placeholder="Fornecedores essenciais para a operação"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dependências de pessoa</label>
                        <textarea name="dependencia_pessoa" x-model="form.dependencia_pessoa" rows="2"
                                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none resize-none"
                                  placeholder="Atividades que dependem de uma única pessoa"></textarea>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Integrações existentes</label>
                    <input type="text" name="integracoes" x-model="form.integracoes"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none"
                           placeholder="Ex: ERP integrado com e-commerce, CRM com email marketing...">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Processos documentados: <strong x-text="form.processos_documentados + '%'"></strong></label>
                    <input type="range" name="processos_documentados" x-model="form.processos_documentados" min="0" max="100" step="5"
                           class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-primary">
                    <div class="flex justify-between text-xs text-gray-400 mt-1">
                        <span>0% — Nenhum</span>
                        <span>50%</span>
                        <span>100% — Todos</span>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ferramentas de gestão utilizadas</label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2">
                        <?php foreach ($dados['opcoes']['ferramentas_gestao'] as $fg): ?>
                        <label class="flex items-center gap-2 p-2 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer text-sm">
                            <input type="checkbox" name="ferramentas_gestao[]" value="<?= htmlspecialchars($fg) ?>"
                                   x-model="form.ferramentas_gestao"
                                   class="w-4 h-4 text-primary rounded border-gray-300 focus:ring-primary">
                            <span class="text-gray-700 text-xs"><?= htmlspecialchars($fg) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- BLOCO 4 — PROBLEMAS E RISCOS -->
        <div x-show="blocoAtual === 4" x-transition class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-lg bg-red-100 text-red-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">Problemas e Riscos</h2>
                    <p class="text-sm text-gray-500">Vulnerabilidades e incidentes</p>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Problemas operacionais atuais *</label>
                    <textarea name="problemas_operacionais" x-model="form.problemas_operacionais" rows="3" required
                              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none resize-none"
                              placeholder="Quais são os principais problemas operacionais enfrentados atualmente?"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Riscos identificados</label>
                    <textarea name="riscos_identificados" x-model="form.riscos_identificados" rows="3"
                              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none resize-none"
                              placeholder="Riscos para a continuidade do negócio"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Incidentes graves (últimos 12 meses) — Tipo</label>
                        <input type="text" name="incidentes_tipo" x-model="form.incidentes_tipo"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none"
                               placeholder="Ex: Financeiro, Operacional, Jurídico...">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descrição do incidente</label>
                        <input type="text" name="incidentes_descricao" x-model="form.incidentes_descricao"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none"
                               placeholder="Breve descrição do que aconteceu">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Áreas mais vulneráveis</label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2">
                        <?php foreach ($dados['opcoes']['areas_vulneraveis'] as $av): ?>
                        <label class="flex items-center gap-2 p-2 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer text-sm">
                            <input type="checkbox" name="areas_vulneraveis[]" value="<?= htmlspecialchars($av) ?>"
                                   x-model="form.areas_vulneraveis"
                                   class="w-4 h-4 text-primary rounded border-gray-300 focus:ring-primary">
                            <span class="text-gray-700 text-xs"><?= htmlspecialchars($av) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cliente com +30% do faturamento?</label>
                        <select name="cliente_concentrado" x-model="form.cliente_concentrado"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                            <option value="nao">Não</option>
                            <option value="sim">Sim</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fornecedor insubstituível?</label>
                        <select name="fornecedor_insubstituivel" x-model="form.fornecedor_insubstituivel"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                            <option value="nao">Não</option>
                            <option value="sim">Sim</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Processos sem backup de conhecimento?</label>
                        <select name="processos_sem_backup" x-model="form.processos_sem_backup"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                            <option value="nao">Não</option>
                            <option value="sim">Sim</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- BLOCO 5 — CONTEXTO ESTRATÉGICO -->
        <div x-show="blocoAtual === 5" x-transition class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">Contexto Estratégico</h2>
                    <p class="text-sm text-gray-500">Visão de futuro e planejamento</p>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pontos fortes da empresa *</label>
                    <textarea name="pontos_fortes" x-model="form.pontos_fortes" rows="3" required
                              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none resize-none"
                              placeholder="O que a empresa faz muito bem? Quais são os diferenciais?"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pontos de melhoria *</label>
                    <textarea name="pontos_melhoria" x-model="form.pontos_melhoria" rows="3" required
                              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none resize-none"
                              placeholder="Onde a empresa precisa melhorar? Quais as maiores dificuldades?"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Objetivo estratégico para os próximos 12 meses</label>
                    <textarea name="objetivo_12_meses" x-model="form.objetivo_12_meses" rows="2"
                              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none resize-none"
                              placeholder="Qual é o principal objetivo da empresa para o próximo ano?"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Maturidade percebida pelo gestor: <strong x-text="form.maturidade_percebida + '/5'"></strong></label>
                    <input type="range" name="maturidade_percebida" x-model="form.maturidade_percebida" min="1" max="5" step="1"
                           class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-primary">
                    <div class="flex justify-between text-xs text-gray-400 mt-1">
                        <span>1 — Caótico</span>
                        <span>2</span>
                        <span>3 — Organizado</span>
                        <span>4</span>
                        <span>5 — Excelente</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Planejamento documentado?</label>
                        <select name="planejamento_documentado" x-model="form.planejamento_documentado"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                            <option value="nao">Não</option>
                            <option value="sim">Sim</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Frequência de reuniões</label>
                        <select name="frequencia_reunioes" x-model="form.frequencia_reunioes"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                            <option value="">Selecione...</option>
                            <?php foreach ($dados['opcoes']['frequencia_reunioes'] as $fr): ?>
                            <option value="<?= htmlspecialchars($fr) ?>"><?= htmlspecialchars($fr) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Meta de faturamento definida?</label>
                        <select name="meta_faturamento" x-model="form.meta_faturamento"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                            <option value="nao">Não</option>
                            <option value="sim">Sim</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navegação entre blocos -->
        <div class="flex items-center justify-between mt-6">
            <button type="button" @click="anterior()" x-show="blocoAtual > 1"
                    class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                ← Anterior
            </button>
            <div x-show="blocoAtual === 1"></div>

            <button type="button" @click="proximo()" x-show="blocoAtual < 5"
                    class="px-5 py-2.5 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-700 transition">
                Próximo →
            </button>

            <button type="submit" x-show="blocoAtual === 5" :disabled="enviando"
                    class="px-6 py-2.5 bg-accent text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition disabled:opacity-50 flex items-center gap-2">
                <span x-show="!enviando">✓ Concluir Diagnóstico</span>
                <span x-show="enviando" class="flex items-center gap-2">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    Processando...
                </span>
            </button>
        </div>
    </form>
</div>

<script>
function diagnosticoWizard() {
    return {
        blocoAtual: 1,
        enviando: false,
        steps: ['Identificação', 'Estrutura', 'Operação', 'Riscos', 'Estratégia'],
        form: {
            // Bloco 1
            empresa_nome: '',
            setor: '',
            descricao: '',
            tempo_existencia: '',
            estrutura_societaria: '',
            unidades_filiais: 1,
            lingua_principal: 'Português',
            // Bloco 2
            colaboradores_internos: 0,
            colaboradores_externos: 0,
            departamentos: [],
            clientes_ativos: 0,
            produtos_servicos: '',
            faturamento_mensal: '',
            ticket_medio: '',
            sites_referencia: '',
            // Bloco 3
            processo_entrega: '',
            ferramentas_softwares: '',
            fornecedores_criticos: '',
            dependencia_pessoa: '',
            integracoes: '',
            processos_documentados: 30,
            ferramentas_gestao: [],
            // Bloco 4
            problemas_operacionais: '',
            riscos_identificados: '',
            incidentes_tipo: '',
            incidentes_descricao: '',
            areas_vulneraveis: [],
            cliente_concentrado: 'nao',
            fornecedor_insubstituivel: 'nao',
            processos_sem_backup: 'nao',
            // Bloco 5
            pontos_fortes: '',
            pontos_melhoria: '',
            objetivo_12_meses: '',
            maturidade_percebida: 3,
            planejamento_documentado: 'nao',
            frequencia_reunioes: '',
            meta_faturamento: 'nao',
        },

        proximo() {
            if (this.validarBlocoAtual()) {
                this.blocoAtual++;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        anterior() {
            this.blocoAtual--;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        irParaBloco(n) {
            if (n <= this.blocoAtual) {
                this.blocoAtual = n;
            }
        },

        validarBlocoAtual() {
            if (this.blocoAtual === 1) {
                if (!this.form.empresa_nome || !this.form.setor || !this.form.tempo_existencia) {
                    alert('Preencha os campos obrigatórios: Nome da empresa, Setor e Tempo de existência.');
                    return false;
                }
            }
            if (this.blocoAtual === 3) {
                if (!this.form.processo_entrega) {
                    alert('Descreva o processo do primeiro contato até a entrega.');
                    return false;
                }
            }
            if (this.blocoAtual === 4) {
                if (!this.form.problemas_operacionais) {
                    alert('Descreva os problemas operacionais atuais.');
                    return false;
                }
            }
            return true;
        },

        async salvar() {
            if (!this.validarBlocoAtual()) return;
            if (!this.form.pontos_fortes || !this.form.pontos_melhoria) {
                alert('Preencha os pontos fortes e pontos de melhoria.');
                return;
            }

            this.enviando = true;

            const formData = new FormData();
            formData.append('csrf_token', document.querySelector('[name=csrf_token]').value);

            // Adicionar todos os campos
            for (const [key, value] of Object.entries(this.form)) {
                if (Array.isArray(value)) {
                    value.forEach(v => formData.append(key + '[]', v));
                } else {
                    formData.append(key, value);
                }
            }

            try {
                const response = await fetch('<?= APP_URL ?>/diagnostico/salvar', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.sucesso) {
                    window.location.href = data.redirect;
                } else {
                    alert(data.erro || 'Erro ao salvar o diagnóstico.');
                }
            } catch (error) {
                alert('Erro de conexão com o servidor.');
            } finally {
                this.enviando = false;
            }
        }
    };
}
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
