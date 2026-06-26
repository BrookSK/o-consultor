<?php $tituloPagina = 'Nova Marca'; ?>
<?php ob_start(); ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li><a href="<?= APP_URL ?>/maquina-de-conteudo" class="hover:text-primary">Máquina de Conteúdo</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Nova Marca</li>
    </ol>
</nav>

<div x-data="marcaWizard()" class="max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-800 mb-2">Cadastrar Nova Marca</h1>
    <p class="text-gray-500 mb-6 text-sm">Configure o Brand Book da marca em 5 etapas.</p>

    <!-- Progresso -->
    <div class="mb-8">
        <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
            <div class="h-full bg-accent rounded-full transition-all" :style="'width:' + (etapa / 5 * 100) + '%'"></div>
        </div>
        <div class="flex justify-between mt-2 text-[10px] text-gray-400">
            <span :class="etapa >= 1 && 'text-primary font-medium'">Briefing</span>
            <span :class="etapa >= 2 && 'text-primary font-medium'">Tom de Voz</span>
            <span :class="etapa >= 3 && 'text-primary font-medium'">Visual</span>
            <span :class="etapa >= 4 && 'text-primary font-medium'">Brand Book</span>
            <span :class="etapa >= 5 && 'text-primary font-medium'">Prompt Master</span>
        </div>
    </div>

    <!-- ETAPA 1: BRIEFING -->
    <div x-show="etapa === 1" x-transition class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">1. Briefing da Marca</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Nome da marca *</label><input type="text" x-model="form.nome" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary" placeholder="Nome da marca"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Nicho/Setor *</label><input type="text" x-model="form.nicho" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary" placeholder="Ex: Tecnologia/MSP"></div>
            <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Público-alvo (uma frase)</label><input type="text" x-model="form.publico" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary" placeholder="Ex: Empresários de PMEs que precisam de TI gerenciada"></div>
            <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Principais produtos/serviços</label><textarea x-model="form.produtos" rows="2" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none" placeholder="Liste os principais"></textarea></div>
            <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">3 Diferenciais competitivos</label><textarea x-model="form.diferenciais" rows="2" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none" placeholder="O que torna esta marca única?"></textarea></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Principais concorrentes</label><input type="text" x-model="form.concorrentes" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary" placeholder="Separados por vírgula"></div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Objetivos de conteúdo</label>
                <div class="flex flex-wrap gap-2 mt-1">
                    <template x-for="obj in ['Educar', 'Engajar', 'Converter', 'Inspirar', 'Informar', 'Vender']" :key="obj">
                        <label class="flex items-center gap-1.5 px-3 py-1.5 border border-gray-200 rounded-lg text-xs cursor-pointer hover:bg-gray-50" :class="form.objetivos.includes(obj) && 'border-primary bg-primary/5'">
                            <input type="checkbox" :value="obj" x-model="form.objetivos" class="w-3 h-3 text-primary rounded">
                            <span x-text="obj"></span>
                        </label>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- ETAPA 2: TOM DE VOZ -->
    <div x-show="etapa === 2" x-transition class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">2. Tom de Voz</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tom *</label>
                <select x-model="form.tom" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                    <option value="">Selecione...</option>
                    <option>Formal</option><option>Semiformal</option><option>Descontraído</option><option>Técnico</option><option>Inspirador</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Arquétipo *</label>
                <select x-model="form.arquetipo" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                    <option value="">Selecione...</option>
                    <option>Sábio</option><option>Herói</option><option>Criador</option><option>Cuidador</option><option>Governante</option><option>Explorador</option><option>Mago</option><option>Fora-da-lei</option>
                </select>
            </div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Palavras que a marca USA</label><textarea x-model="form.palavras_usa" rows="3" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none" placeholder="Ex: inovação, confiança, proteção, parceria..."></textarea></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Palavras que NUNCA usa</label><textarea x-model="form.palavras_nunca" rows="3" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none" placeholder="Ex: barato, gambiarra, talvez..."></textarea></div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Formato preferido de comunicação</label>
                <div class="flex flex-wrap gap-2">
                    <template x-for="fmt in ['Carrosséis', 'Posts únicos', 'Stories', 'Reels', 'Artigos', 'Newsletters']" :key="fmt">
                        <label class="flex items-center gap-1.5 px-3 py-1.5 border border-gray-200 rounded-lg text-xs cursor-pointer hover:bg-gray-50">
                            <input type="checkbox" :value="fmt" x-model="form.formatos" class="w-3 h-3 text-primary rounded"><span x-text="fmt"></span>
                        </label>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- ETAPA 3: IDENTIDADE VISUAL -->
    <div x-show="etapa === 3" x-transition class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">3. Identidade Visual e Referências</h2>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Paleta de cores (até 5)</label>
                <div class="flex gap-3">
                    <template x-for="(cor, i) in form.paleta" :key="i">
                        <input type="color" x-model="form.paleta[i]" class="w-10 h-10 rounded border border-gray-300 cursor-pointer">
                    </template>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Fonte principal</label><input type="text" x-model="form.fonte_principal" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary" placeholder="Ex: Inter"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Fonte secundária</label><input type="text" x-model="form.fonte_secundaria" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary" placeholder="Ex: Roboto Mono"></div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Estilo visual</label>
                <select x-model="form.estilo_visual" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                    <option>Minimalista/Clean</option><option>Corporativo</option><option>Moderno/Tech</option><option>Orgânico/Natural</option><option>Bold/Impacto</option><option>Luxo/Premium</option><option>Colorido/Vibrante</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Direção fotográfica</label>
                <select x-model="form.direcao_foto" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                    <option>Pessoas reais</option><option>Ilustrações</option><option>Misto</option><option>Produtos</option><option>Abstratos</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Upload de logo (PNG/SVG)</label>
                <input type="file" accept=".png,.svg" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Imagens de referência de carrosséis/posts (até 8)</label>
                <input type="file" accept="image/*" multiple class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                <p class="text-xs text-gray-400 mt-1">Envie exemplos de posts que você gosta para a IA aprender o estilo.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fotos próprias da empresa/produto (até 10)</label>
                <input type="file" accept="image/*" multiple class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
            </div>
        </div>
    </div>

    <!-- ETAPA 4: BRAND BOOK -->
    <div x-show="etapa === 4" x-transition class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div x-show="gerandoBrandBook" class="py-12 text-center">
            <div class="inline-block w-10 h-10 border-4 border-gray-200 border-t-accent rounded-full animate-spin mb-4"></div>
            <p class="text-sm text-gray-600">Gerando Brand Book com base no briefing e referências enviadas...</p>
        </div>
        <div x-show="!gerandoBrandBook">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">4. Brand Book Gerado pela IA</h2>
            <div class="space-y-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Essência da marca</label><textarea x-model="brandBook.essencia" rows="2" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none"></textarea></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Personalidade (5 adjetivos)</label><input type="text" x-model="brandBook.personalidade" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">A marca É...</label><textarea x-model="brandBook.marca_e" rows="3" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none"></textarea></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">A marca NÃO é...</label><textarea x-model="brandBook.marca_nao_e" rows="3" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none"></textarea></div>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Regras de comunicação</label><textarea x-model="brandBook.regras_comunicacao" rows="4" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none"></textarea></div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição do estilo visual para DALL-E (prompt base)</label>
                    <textarea x-model="brandBook.prompt_dalle" rows="3" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none font-mono text-xs"></textarea>
                    <p class="text-xs text-gray-400 mt-1">Este texto será usado como base para gerar todas as imagens da marca.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ETAPA 5: PROMPT MASTER -->
    <div x-show="etapa === 5" x-transition class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">5. Prompt Master</h2>
        <p class="text-sm text-gray-500 mb-4">Este prompt será usado como base para toda geração de conteúdo. Edite se necessário.</p>
        <textarea x-model="promptMaster" rows="16" class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none font-mono"></textarea>
    </div>

    <!-- Navegação -->
    <div class="flex items-center justify-between mt-6">
        <button x-show="etapa > 1" @click="etapa--" class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">← Anterior</button>
        <div x-show="etapa === 1"></div>
        <button x-show="etapa < 4" @click="etapa++" class="px-5 py-2.5 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-700">Próximo →</button>
        <button x-show="etapa === 4 && !gerandoBrandBook" @click="etapa = 5" class="px-5 py-2.5 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-700">Confirmar Brand Book →</button>
        <button x-show="etapa === 5" @click="salvar()" :disabled="enviando" class="px-6 py-2.5 bg-accent text-white rounded-lg text-sm font-semibold hover:bg-orange-700 disabled:opacity-50">
            <span x-show="!enviando">✓ Salvar e Começar a Gerar Conteúdo</span>
            <span x-show="enviando">Salvando...</span>
        </button>
    </div>
</div>

<script>
function marcaWizard() {
    return {
        etapa: 1, enviando: false, gerandoBrandBook: false,
        form: {
            nome: '', nicho: '', publico: '', produtos: '', diferenciais: '', concorrentes: '', objetivos: [],
            tom: '', arquetipo: '', palavras_usa: '', palavras_nunca: '', formatos: [],
            paleta: ['#1E3A5F', '#E07B00', '#FFFFFF', '#F5F7FA', '#1a7a1a'],
            fonte_principal: 'Inter', fonte_secundaria: '', estilo_visual: 'Minimalista/Clean', direcao_foto: 'Misto',
        },
        brandBook: {
            essencia: 'Uma marca que transforma complexidade tecnológica em simplicidade para seus clientes, transmitindo confiança e expertise.',
            personalidade: 'Confiável, Técnica, Proativa, Acessível, Inovadora',
            marca_e: 'Parceira estratégica, proativa, transparente, técnica mas acessível, preocupada com resultados',
            marca_nao_e: 'Genérica, reativa, complexa sem necessidade, distante, promete o que não entrega',
            regras_comunicacao: '1. Sempre usar dados e exemplos reais\n2. Evitar jargões sem explicação\n3. Tom profissional mas humano\n4. CTA claro em todo conteúdo\n5. Nunca falar mal de concorrentes',
            prompt_dalle: 'Imagem tecnológica, clean, fundo azul escuro (#1E3A5F), elementos geométricos sutis, ícones de tecnologia/cloud/segurança, estilo corporativo moderno, sem texto sobreposto, iluminação suave gradiente',
        },
        promptMaster: 'Você é o redator da marca [NOME]. Identidade: [ESSÊNCIA]. Público: [PÚBLICO]. Tom: [TOM]. Arquétipo: [ARQUÉTIPO].\n\nPalavras que usa: [LISTA]. Nunca usa: [LISTA].\nEstilo visual: [DESCRIÇÃO]. CTAs: diretos e claros.\nRegras: nunca ser genérico, sempre dar exemplos práticos, usar dados quando possível.\nO que nunca fazer: prometer resultados irreais, usar linguagem agressiva, ignorar o contexto do leitor.',
        init() {
            this.$watch('etapa', (val) => {
                if (val === 4) { this.gerandoBrandBook = true; setTimeout(() => this.gerandoBrandBook = false, 2500); }
            });
        },
        async salvar() {
            this.enviando = true;
            const fd = new FormData();
            fd.append('csrf_token', '<?= Csrf::token() ?>');
            fd.append('nome', this.form.nome);
            fd.append('nicho', this.form.nicho);
            try {
                const res = await fetch('<?= APP_URL ?>/maquina-de-conteudo/salvar-marca', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.sucesso) window.location.href = data.redirect;
            } catch(e) { alert('Erro.'); }
            this.enviando = false;
        }
    };
}
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
