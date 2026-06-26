<?php $tituloPagina = 'Novo Plano de Ação'; ?>
<?php ob_start(); ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li><a href="<?= APP_URL ?>/plano-de-acao" class="hover:text-primary">Planos de Ação</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Novo Plano</li>
    </ol>
</nav>

<div x-data="planoWizard()" class="max-w-5xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Criar Plano de Ação</h1>
        <p class="text-gray-500 mt-1">Vincule um diagnóstico e gere prioridades com IA.</p>
    </div>

    <!-- Barra de Progresso -->
    <div class="mb-8">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-600">Etapa <span x-text="etapa"></span> de 3</span>
        </div>
        <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
            <div class="h-full bg-primary rounded-full transition-all duration-500" :style="'width:' + (etapa / 3 * 100) + '%'"></div>
        </div>
        <div class="flex justify-between mt-3">
            <button @click="etapa = 1" :class="etapa >= 1 ? 'bg-primary text-white' : 'bg-gray-200 text-gray-500'" class="px-4 py-1.5 rounded-full text-xs font-medium">1. Dados Gerais</button>
            <button @click="etapa >= 2 && (etapa = 2)" :class="etapa >= 2 ? 'bg-primary text-white' : 'bg-gray-200 text-gray-500'" class="px-4 py-1.5 rounded-full text-xs font-medium">2. Prioridades IA</button>
            <button @click="etapa >= 3 && (etapa = 3)" :class="etapa >= 3 ? 'bg-primary text-white' : 'bg-gray-200 text-gray-500'" class="px-4 py-1.5 rounded-full text-xs font-medium">3. Tarefas</button>
        </div>
    </div>

    <!-- ETAPA 1 — DADOS GERAIS -->
    <div x-show="etapa === 1" x-transition class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-6">Dados Gerais do Plano</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Empresa *</label>
                <select x-model="form.empresa_id" @change="carregarDiagnosticos()" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                    <option value="">Selecione...</option>
                    <?php foreach ($dados['empresas'] as $emp): ?>
                    <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Diagnóstico vinculado *</label>
                <select x-model="form.diagnostico_id" @change="selecionarDiagnostico()" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                    <option value="">Selecione a empresa primeiro...</option>
                    <template x-for="d in diagnosticosFiltrados" :key="d.id">
                        <option :value="d.id" x-text="d.empresa + ' — Score ' + d.score + ' (' + d.data + ')'"></option>
                    </template>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Título do Plano *</label>
                <input type="text" x-model="form.titulo" required
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"
                       placeholder="Ex: Plano de Estruturação Operacional — Empresa X">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Objetivo principal</label>
                <textarea x-model="form.objetivo" rows="3"
                          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none"
                          placeholder="Qual o principal resultado esperado com este plano?"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data início *</label>
                <input type="date" x-model="form.data_inicio" required
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data fim *</label>
                <input type="date" x-model="form.data_fim" required
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
            </div>
        </div>

        <!-- Info do diagnóstico selecionado -->
        <div x-show="diagSelecionado" x-transition class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <h4 class="text-sm font-semibold text-blue-800 mb-2">📋 Dados do Diagnóstico Vinculado</h4>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs text-blue-700">
                <div><strong>Setor:</strong> <span x-text="diagSelecionado?.setor"></span></div>
                <div><strong>Score:</strong> <span x-text="diagSelecionado?.score + '/4'"></span></div>
                <div><strong>Faturamento:</strong> <span x-text="diagSelecionado?.faturamento"></span></div>
                <div><strong>Departamentos:</strong> <span x-text="diagSelecionado?.departamentos"></span></div>
            </div>
            <p class="text-xs text-blue-600 mt-2"><strong>Problemas:</strong> <span x-text="diagSelecionado?.problemas"></span></p>
        </div>

        <div class="flex justify-end mt-6">
            <button @click="gerarPrioridades()" :disabled="!form.empresa_id || !form.diagnostico_id || !form.titulo"
                    class="px-5 py-2.5 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-700 transition disabled:opacity-50">
                Gerar Prioridades com IA →
            </button>
        </div>
    </div>

    <!-- ETAPA 2 — PRIORIDADES GERADAS PELA IA -->
    <div x-show="etapa === 2" x-transition class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">🤖 Prioridades Sugeridas pela IA</h2>
                <p class="text-sm text-gray-500 mt-1">Confirme, edite ou remova as prioridades geradas.</p>
            </div>
            <span class="text-sm text-gray-400" x-text="prioridades.filter(p => p.ativa).length + ' de ' + prioridades.length + ' ativas'"></span>
        </div>

        <!-- Loading -->
        <div x-show="gerandoPrioridades" class="flex items-center justify-center py-12">
            <div class="text-center">
                <div class="inline-block w-8 h-8 border-4 border-gray-200 border-t-accent rounded-full animate-spin"></div>
                <p class="text-sm text-gray-500 mt-3">Analisando diagnóstico e gerando prioridades...</p>
            </div>
        </div>

        <!-- Lista de prioridades -->
        <div x-show="!gerandoPrioridades" class="space-y-3">
            <template x-for="(prio, index) in prioridades" :key="index">
                <div :class="prio.ativa ? 'border-gray-200 bg-white' : 'border-gray-100 bg-gray-50 opacity-60'"
                     class="border rounded-lg p-4 transition">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3 flex-1">
                            <input type="checkbox" x-model="prio.ativa" class="w-4 h-4 mt-1 text-primary rounded border-gray-300">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs font-medium px-2 py-0.5 rounded bg-gray-100 text-gray-600" x-text="prio.area"></span>
                                    <span :class="{
                                        'bg-red-100 text-red-700': prio.impacto === 'Alto',
                                        'bg-yellow-100 text-yellow-700': prio.impacto === 'Médio',
                                        'bg-blue-100 text-blue-700': prio.impacto === 'Baixo'
                                    }" class="text-xs font-medium px-2 py-0.5 rounded" x-text="'Impacto ' + prio.impacto"></span>
                                    <span class="text-xs text-gray-400" x-text="'⏱ ' + prio.urgencia"></span>
                                </div>
                                <p class="text-sm font-medium text-gray-800" x-text="prio.problema"></p>
                                <p class="text-sm text-green-700 mt-1" x-text="'→ ' + prio.acao"></p>
                                <p class="text-xs text-gray-400 mt-1" x-text="'Origem: ' + prio.origem"></p>
                            </div>
                        </div>
                        <button @click="prioridades.splice(index, 1)" class="text-gray-400 hover:text-red-500 flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <div x-show="!gerandoPrioridades" class="flex items-center justify-between mt-6">
            <button @click="etapa = 1" class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">← Voltar</button>
            <button @click="gerarTarefas()" :disabled="prioridades.filter(p => p.ativa).length === 0"
                    class="px-5 py-2.5 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-700 transition disabled:opacity-50">
                Criar Tarefas →
            </button>
        </div>
    </div>

    <!-- ETAPA 3 — TAREFAS DETALHADAS -->
    <div x-show="etapa === 3" x-transition class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">📝 Tarefas Detalhadas</h2>
                <p class="text-sm text-gray-500 mt-1">Defina responsável e prazo para cada tarefa.</p>
            </div>
            <span class="text-sm text-gray-400" x-text="tarefas.length + ' tarefas'"></span>
        </div>

        <div class="space-y-4">
            <template x-for="(tarefa, index) in tarefas" :key="index">
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-6 h-6 rounded-full bg-primary text-white text-xs flex items-center justify-center font-semibold" x-text="index + 1"></span>
                        <span class="text-xs font-medium px-2 py-0.5 rounded bg-gray-100 text-gray-600" x-text="tarefa.area"></span>
                        <span :class="{
                            'bg-red-100 text-red-700': tarefa.prioridade === 'alta',
                            'bg-yellow-100 text-yellow-700': tarefa.prioridade === 'media',
                            'bg-blue-100 text-blue-700': tarefa.prioridade === 'baixa'
                        }" class="text-xs font-medium px-2 py-0.5 rounded" x-text="tarefa.prioridade.charAt(0).toUpperCase() + tarefa.prioridade.slice(1)"></span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="md:col-span-2">
                            <label class="block text-xs text-gray-500 mb-1">Título</label>
                            <input type="text" x-model="tarefa.titulo" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs text-gray-500 mb-1">Descrição</label>
                            <textarea x-model="tarefa.descricao" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Responsável</label>
                            <input type="text" x-model="tarefa.responsavel" placeholder="Nome do responsável"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Prazo</label>
                            <input type="date" x-model="tarefa.prazo"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div class="flex items-center justify-between mt-6">
            <button @click="etapa = 2" class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">← Voltar</button>
            <button @click="salvarPlano()" :disabled="enviando"
                    class="px-6 py-2.5 bg-accent text-white rounded-lg text-sm font-semibold hover:bg-orange-700 transition disabled:opacity-50 flex items-center gap-2">
                <span x-show="!enviando">✓ Salvar Plano de Ação</span>
                <span x-show="enviando">Salvando...</span>
            </button>
        </div>
    </div>
</div>

<script>
function planoWizard() {
    return {
        etapa: 1,
        enviando: false,
        gerandoPrioridades: false,
        form: { empresa_id: '', diagnostico_id: '', titulo: '', objetivo: '', data_inicio: '', data_fim: '' },
        diagnosticos: <?= json_encode($dados['diagnosticos']) ?>,
        diagnosticosFiltrados: [],
        diagSelecionado: null,
        prioridades: [],
        tarefas: [],

        carregarDiagnosticos() {
            this.diagnosticosFiltrados = this.diagnosticos.filter(d => d.empresa_id == this.form.empresa_id);
            this.form.diagnostico_id = '';
            this.diagSelecionado = null;
        },

        selecionarDiagnostico() {
            this.diagSelecionado = this.diagnosticos.find(d => d.id == this.form.diagnostico_id) || null;
        },

        gerarPrioridades() {
            if (!this.form.empresa_id || !this.form.diagnostico_id || !this.form.titulo) {
                alert('Preencha todos os campos obrigatórios.');
                return;
            }
            this.etapa = 2;
            this.gerandoPrioridades = true;

            // Simular geração IA (em produção: chamaria API)
            setTimeout(() => {
                this.prioridades = [
                    { area: 'Comercial', problema: 'Ausência de CRM — vendas desorganizadas', acao: 'Implementar CRM e definir processo comercial estruturado', impacto: 'Alto', urgencia: 'Imediato', origem: 'Bloco 3 — Operação', ativa: true },
                    { area: 'Operações', problema: 'Apenas 25% dos processos documentados', acao: 'Criar programa de documentação com SOPs prioritários', impacto: 'Alto', urgencia: '30 dias', origem: 'Bloco 3 — Operação', ativa: true },
                    { area: 'Financeiro', problema: 'Sem dashboard financeiro para tomada de decisão', acao: 'Implementar painel financeiro com KPIs críticos', impacto: 'Alto', urgencia: '30 dias', origem: 'Bloco 5 — Estratégia', ativa: true },
                    { area: 'Estratégia', problema: 'Metas não formalizadas e sem acompanhamento', acao: 'Definir OKRs trimestrais com rituais de acompanhamento', impacto: 'Alto', urgencia: 'Imediato', origem: 'Bloco 5 — Estratégia', ativa: true },
                    { area: 'Pessoas', problema: 'Conhecimento concentrado em poucas pessoas', acao: 'Mapear conhecimento crítico e criar backup', impacto: 'Alto', urgencia: '30 dias', origem: 'Bloco 4 — Riscos', ativa: true },
                    { area: 'Marketing', problema: 'Sem automação de marketing digital', acao: 'Configurar fluxos de nurturing e lead scoring', impacto: 'Médio', urgencia: '60 dias', origem: 'Bloco 2 — Estrutura', ativa: true },
                    { area: 'Comercial', problema: 'Sem medição de satisfação (NPS)', acao: 'Implementar pesquisa NPS automatizada pós-venda', impacto: 'Médio', urgencia: '30 dias', origem: 'Bloco 3 — Operação', ativa: true },
                    { area: 'Pessoas', problema: 'Sem processo de onboarding formalizado', acao: 'Criar SOP de onboarding para novos colaboradores', impacto: 'Médio', urgencia: '60 dias', origem: 'Bloco 2 — Estrutura', ativa: true },
                    { area: 'Operações', problema: 'Integração ERP-Ecommerce instável', acao: 'Estabilizar integração e criar monitoramento', impacto: 'Alto', urgencia: 'Imediato', origem: 'Bloco 3 — Operação', ativa: true },
                    { area: 'Financeiro', problema: 'Precificação não revisada há 12 meses', acao: 'Realizar análise de margem e ajustar pricing', impacto: 'Médio', urgencia: '60 dias', origem: 'Bloco 5 — Estratégia', ativa: true },
                ];
                this.gerandoPrioridades = false;
            }, 2000);
        },

        gerarTarefas() {
            this.tarefas = this.prioridades.filter(p => p.ativa).map((p, i) => ({
                titulo: p.acao,
                descricao: 'Problema: ' + p.problema,
                area: p.area,
                responsavel: '',
                prazo: '',
                prioridade: p.impacto === 'Alto' ? 'alta' : (p.impacto === 'Médio' ? 'media' : 'baixa'),
                status: 'pendente',
            }));
            this.etapa = 3;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        async salvarPlano() {
            this.enviando = true;
            const formData = new FormData();
            formData.append('csrf_token', '<?= Csrf::token() ?>');
            formData.append('empresa_id', this.form.empresa_id);
            formData.append('diagnostico_id', this.form.diagnostico_id);
            formData.append('titulo', this.form.titulo);
            formData.append('objetivo', this.form.objetivo);
            formData.append('data_inicio', this.form.data_inicio);
            formData.append('data_fim', this.form.data_fim);
            formData.append('tarefas', JSON.stringify(this.tarefas));

            try {
                const res = await fetch('<?= APP_URL ?>/plano-de-acao/salvar', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.sucesso) {
                    window.location.href = data.redirect;
                } else {
                    alert(data.erro || 'Erro ao salvar.');
                }
            } catch (e) { alert('Erro de conexão.'); }
            finally { this.enviando = false; }
        }
    };
}
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
