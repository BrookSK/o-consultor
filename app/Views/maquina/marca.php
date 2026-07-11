<?php $tituloPagina = $dados['marca']['nome']; ?>
<?php ob_start(); ?>
<?php $marca = $dados['marca']; ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li><a href="<?= APP_URL ?>/maquina-de-conteudo" class="hover:text-primary">Máquina de Conteúdo</a></li>
        <li>/</li>
        <li class="font-medium text-primary"><?= htmlspecialchars($marca['nome']) ?></li>
    </ol>
</nav>

<!-- Header da Marca -->
<div class="flex items-center gap-4 mb-6">
    <?php if (!empty($marca['logo_url'])): ?>
    <div class="w-14 h-14 rounded-full bg-white border border-gray-200 flex items-center justify-center overflow-hidden">
        <img src="<?= htmlspecialchars(APP_URL . $marca['logo_url']) ?>" class="max-w-full max-h-full object-contain">
    </div>
    <?php else: ?>
    <div class="w-14 h-14 rounded-full bg-primary text-white flex items-center justify-center text-xl font-bold"><?= strtoupper(substr($marca['nome'], 0, 1)) ?></div>
    <?php endif; ?>
    <div>
        <h1 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($marca['nome']) ?></h1>
        <p class="text-sm text-gray-500"><?= htmlspecialchars($marca['nicho']) ?> • Tom: <?= htmlspecialchars($marca['tom']) ?> • Arquétipo: <?= htmlspecialchars($marca['arquetipo']) ?></p>
    </div>
</div>

<!-- 4 Abas -->
<div x-data="{ aba: 'gerar' }" x-init="$watch('aba', v => { if (v === 'publicacao' && typeof carregarCalendario === 'function') { carregarDadosPublicacao(); carregarCalendario(); } })">
    <div class="border-b border-gray-200 mb-6">
        <nav class="flex gap-0 overflow-x-auto">
            <button @click="aba = 'gerar'" :class="aba === 'gerar' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500'" class="px-5 py-3 text-sm whitespace-nowrap">⚡ Gerar Conteúdo</button>
            <button @click="aba = 'branding'" :class="aba === 'branding' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500'" class="px-5 py-3 text-sm whitespace-nowrap">📖 Brand Book</button>
            <button @click="aba = 'biblioteca'" :class="aba === 'biblioteca' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500'" class="px-5 py-3 text-sm whitespace-nowrap">📚 Biblioteca</button>
            <button @click="aba = 'templates'" :class="aba === 'templates' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500'" class="px-5 py-3 text-sm whitespace-nowrap">🎨 Templates</button>
            <button @click="aba = 'publicacao'; setTimeout(() => { carregarDadosPublicacao(); carregarCalendario(); }, 50)" :class="aba === 'publicacao' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500'" class="px-5 py-3 text-sm whitespace-nowrap">📅 Publicação</button>
        </nav>
    </div>

    <!-- ABA GERAR -->
    <div x-show="aba === 'gerar'" x-transition>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Formulário -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Configurar Geração</h3>
                <form id="form-gerar" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
                    <input type="hidden" name="marca_id" value="<?= $marca['id'] ?>">
                    <div x-data="{ tipo: 'carrossel' }">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de conteúdo</label>
                        <select name="tipo" x-model="tipo" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                            <option value="carrossel">Carrossel</option>
                            <option value="post">Post único</option>
                            <option value="story">Story</option>
                            <option value="reels">Reels (texto)</option>
                        </select>
                        <!-- Quantidade de slides (apenas carrossel) -->
                        <div x-show="tipo === 'carrossel'" x-transition class="mt-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Quantidade de slides</label>
                            <select name="qtd_slides" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                                <?php for ($s = 3; $s <= 10; $s++): ?>
                                <option value="<?= $s ?>" <?= $s === 7 ? 'selected' : '' ?>><?= $s ?> slides</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tema/Assunto *</label>
                        <input type="text" name="tema" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary" placeholder="Ex: Como proteger sua empresa contra ransomware">
                        <p class="text-xs text-gray-400 mt-1">Na fonte "Biblioteca", a IA busca na sua literatura os trechos que tratam deste tema, lê e monta um conteúdo educativo (começo, meio e fim).</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Objetivo</label>
                        <select name="objetivo" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                            <option value="educar">🎓 Educar (Educativo)</option>
                            <option value="engajar">💬 Engajar (Engajamento)</option>
                            <option value="converter">🎯 Converter (Conversão)</option>
                            <option value="inspirar">💥 Inspirar (Impacto)</option>
                            <option value="informar">📰 Informar (Notícias)</option>
                            <option value="institucional">🏢 Institucional</option>
                        </select>
                        <p class="text-xs text-gray-400 mt-1">A IA escolhe o template classificado com o objetivo correspondente.</p>
                    </div>
                    <div x-data="{ fonte: 'tema' }">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fonte do conteúdo</label>
                        <select name="fonte" x-model="fonte" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                            <option value="tema">Apenas o tema (livre)</option>
                            <option value="noticia">📰 Notícia da Central de Conteúdo</option>
                            <option value="biblioteca">📚 Biblioteca (conteúdo educativo)</option>
                        </select>

                        <!-- Notícia -->
                        <div x-show="fonte === 'noticia'" x-transition class="mt-2">
                            <select name="noticia_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                                <option value="">Selecione a notícia...</option>
                                <?php foreach ($dados['noticias'] as $n): ?>
                                <option value="<?= $n['id'] ?>"><?= htmlspecialchars($n['titulo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="text-xs text-gray-400 mt-1">O conteúdo será baseado na análise desta notícia.</p>
                        </div>

                        <!-- Biblioteca -->
                        <div x-show="fonte === 'biblioteca'" x-transition class="mt-2">
                            <?php if (empty($dados['biblioteca'])): ?>
                            <p class="text-xs text-gray-500 bg-gray-50 border border-gray-200 rounded-lg p-3">
                                Nenhum documento na Biblioteca. Adicione PDFs na Central de Conteúdo &gt; Biblioteca para gerar conteúdo educativo a partir da sua literatura.
                            </p>
                            <?php else: ?>
                            <label class="block text-xs text-gray-500 mb-1">Documentos de referência (a IA vai ler e usar os mais relevantes ao tema)</label>
                            <div class="max-h-40 overflow-y-auto border border-gray-200 rounded-lg p-2 space-y-1">
                                <?php foreach ($dados['biblioteca'] as $doc): ?>
                                <label class="flex items-center gap-2 text-sm cursor-pointer py-1">
                                    <input type="checkbox" name="biblioteca_ids[]" value="<?= (int) $doc['id'] ?>" class="w-4 h-4 text-primary rounded">
                                    <span class="truncate">📄 <?= htmlspecialchars($doc['nome']) ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Deixe todos desmarcados para a IA considerar toda a biblioteca.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estilo de imagem</label>
                        <select name="estilo_imagem" id="sel-estilo-img" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                            <option value="ia">Gerar com IA (DALL-E)</option>
                            <option value="foto">Usar foto própria da empresa</option>
                            <option value="sem">Sem imagem</option>
                        </select>
                    </div>
                    <!-- Qualidade da imagem (impacta custo). Só aparece quando "Gerar com IA". -->
                    <div id="bloco-qualidade">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Qualidade da imagem</label>
                        <select name="qualidade_imagem" id="sel-qualidade-img" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                            <option value="low" selected>Econômica — mais barata (~R$ 0,06/imagem)</option>
                            <option value="medium">Equilibrada — recomendada (~R$ 0,23/imagem)</option>
                            <option value="high">Alta — mais cara e detalhada (~R$ 0,90/imagem)</option>
                        </select>
                        <p class="text-xs text-gray-400 mt-1" id="txt-qualidade-hint">Valores aproximados por imagem. Um carrossel de 7 slides gera 7 imagens.</p>
                    </div>
                    <div id="prompt-dalle-preview" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prompt DALL-E (editável)</label>
                        <textarea name="prompt_imagem" rows="3" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none font-mono text-xs"><?= htmlspecialchars($marca['prompt_dalle']) ?></textarea>
                    </div>
                    <button type="submit" id="btn-gerar" class="w-full bg-accent text-white py-3 rounded-lg text-sm font-semibold hover:bg-orange-700 transition">⚡ Gerar Conteúdo</button>
                </form>
            </div>

            <!-- Preview Resultado -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Preview</h3>
                <div id="resultado-preview" class="min-h-[400px] flex items-center justify-center text-gray-400 text-sm">
                    <p>O conteúdo gerado aparecerá aqui.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ABA BRAND BOOK -->
    <div x-show="aba === 'branding'" x-transition style="display:none;">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 max-w-3xl">
            <h3 class="font-semibold text-gray-800 mb-1">📖 Brand Book da Marca</h3>
            <p class="text-sm text-gray-500 mb-5">Configurações de identidade usadas na geração de conteúdo. Edite e salve.</p>

            <form id="form-branding" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
                <input type="hidden" name="marca_id" value="<?= (int) $marca['id'] ?>">

                <!-- Logo -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Logo da marca</label>
                    <div class="flex items-center gap-4">
                        <div class="w-24 h-24 rounded-lg border border-gray-200 bg-gray-50 flex items-center justify-center overflow-hidden">
                            <img id="logo-preview" src="<?= !empty($marca['logo_url']) ? htmlspecialchars(APP_URL . $marca['logo_url']) : '' ?>" class="max-w-full max-h-full object-contain <?= empty($marca['logo_url']) ? 'hidden' : '' ?>">
                            <span id="logo-vazio" class="text-xs text-gray-400 <?= !empty($marca['logo_url']) ? 'hidden' : '' ?>">Sem logo</span>
                        </div>
                        <div>
                            <label class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-700 cursor-pointer inline-block">
                                📤 Enviar logo (PNG/SVG)
                                <input type="file" accept=".png,.svg,.jpg,.jpeg,.webp" class="hidden" onchange="uploadLogo(this)">
                            </label>
                            <p class="text-xs text-gray-400 mt-1">Preferencialmente PNG com fundo transparente. Será posicionado de forma estratégica e equilibrada nas imagens geradas.</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Nome</label><input type="text" name="nome" value="<?= htmlspecialchars($marca['nome'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Nicho/Setor</label><input type="text" name="nicho" value="<?= htmlspecialchars($marca['nicho'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
                    <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Público-alvo</label><input type="text" name="publico_alvo" value="<?= htmlspecialchars($marca['publico_alvo'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Tom de voz</label><input type="text" name="tom" value="<?= htmlspecialchars($marca['tom'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Arquétipo</label><input type="text" name="arquetipo" value="<?= htmlspecialchars($marca['arquetipo'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Palavras que USA</label><input type="text" name="palavras_usa" value="<?= htmlspecialchars($marca['palavras_usa'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Palavras que NUNCA usa</label><input type="text" name="palavras_nunca" value="<?= htmlspecialchars($marca['palavras_nunca'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Diferenciais competitivos</label>
                    <textarea name="diferenciais_competitivos" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none"><?= htmlspecialchars($marca['diferenciais_competitivos'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Produtos/Serviços</label>
                    <textarea name="produtos_servicos" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none"><?= htmlspecialchars($marca['produtos_servicos'] ?? '') ?></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Concorrentes</label><input type="text" name="concorrentes" value="<?= htmlspecialchars($marca['concorrentes'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Objetivos de conteúdo</label><input type="text" name="objetivos_conteudo" value="<?= htmlspecialchars(is_array(json_decode($marca['objetivos_conteudo'] ?? '[]', true)) ? implode(', ', json_decode($marca['objetivos_conteudo'] ?? '[]', true)) : ($marca['objetivos_conteudo'] ?? '')) ?>" placeholder="Educar, Engajar, Converter..." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Formatos preferenciais</label><input type="text" name="formatos_preferenciais" value="<?= htmlspecialchars(is_array(json_decode($marca['formatos_preferenciais'] ?? '[]', true)) ? implode(', ', json_decode($marca['formatos_preferenciais'] ?? '[]', true)) : ($marca['formatos_preferenciais'] ?? '')) ?>" placeholder="Carrossel, Post, Story..." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Paleta de cores (hex, separados por vírgula)</label><input type="text" name="paleta_cores" value="<?= htmlspecialchars(is_array($marca['paleta_cores'] ?? null) ? implode(', ', $marca['paleta_cores']) : (is_array(json_decode($marca['paleta_cores'] ?? '[]', true)) ? implode(', ', json_decode($marca['paleta_cores'] ?? '[]', true)) : ($marca['paleta_cores'] ?? ''))) ?>" placeholder="#1E3A5F, #E07B00, #FFFFFF" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Fonte principal</label><input type="text" name="fonte_principal" value="<?= htmlspecialchars($marca['fonte_principal'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Fonte secundária</label><input type="text" name="fonte_secundaria" value="<?= htmlspecialchars($marca['fonte_secundaria'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Estilo visual</label><input type="text" name="estilo_visual" value="<?= htmlspecialchars($marca['estilo_visual'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Direção fotográfica</label><input type="text" name="direcao_foto" value="<?= htmlspecialchars($marca['direcao_foto'] ?? '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"></div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prompt Master (base de toda geração de texto)</label>
                    <textarea name="prompt_master" rows="5" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none font-mono text-xs"><?= htmlspecialchars($marca['prompt_master'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prompt de estilo visual (base das imagens)</label>
                    <textarea name="prompt_dalle" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none font-mono text-xs"><?= htmlspecialchars($marca['prompt_dalle'] ?? '') ?></textarea>
                </div>

                <div class="pt-2">
                    <button type="submit" id="btn-salvar-branding" class="px-5 py-2.5 bg-primary text-white rounded-lg text-sm font-semibold hover:bg-primary-700">💾 Salvar Brand Book</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ABA BIBLIOTECA -->
    <div x-show="aba === 'biblioteca'" x-transition style="display:none;">
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">Conteúdos aprovados, agendados, publicados ou salvos para terminar depois.</p>
            <button type="button" onclick="excluirConteudosSelecionados()" id="btn-excluir-massa" class="hidden px-3 py-1.5 border border-red-300 text-red-600 rounded-lg text-xs font-medium hover:bg-red-50">🗑️ Excluir selecionados</button>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" id="grid-biblioteca">
            <?php if (empty($dados['conteudos'])): ?>
            <div class="col-span-full bg-white rounded-lg border border-gray-200 p-8 text-center text-gray-500 text-sm">
                Nenhum conteúdo na biblioteca. Aprove um conteúdo ou use "Terminar depois" na aba Gerar Conteúdo.
            </div>
            <?php else: ?>
            <?php foreach ($dados['conteudos'] as $cont):
                $statusCfg = match($cont['status']) {
                    'aprovado' => ['badge' => 'bg-blue-100 text-blue-700', 'label' => 'Aprovado'],
                    'agendado' => ['badge' => 'bg-orange-100 text-orange-700', 'label' => 'Agendado'],
                    'publicado' => ['badge' => 'bg-green-100 text-green-700', 'label' => 'Publicado'],
                    default => ['badge' => 'bg-gray-100 text-gray-600', 'label' => 'Rascunho'],
                };
            ?>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition relative" data-conteudo-id="<?= (int) $cont['id'] ?>">
                <div class="absolute top-2 left-2 z-10">
                    <input type="checkbox" class="chk-conteudo w-4 h-4 rounded" value="<?= (int) $cont['id'] ?>" onclick="event.stopPropagation(); atualizarBotaoMassa();">
                </div>
                <button type="button" onclick="event.stopPropagation(); excluirConteudo(<?= (int) $cont['id'] ?>, this)" class="absolute top-2 right-2 z-10 text-white/80 hover:text-red-300 text-sm" title="Excluir">🗑️</button>
                <div onclick="window.location.href='<?= APP_URL ?>/maquina-de-conteudo/editar/<?= (int) $cont['id'] ?>'" class="cursor-pointer">
                    <?php if (!empty($cont['thumb'])): ?>
                    <img src="<?= htmlspecialchars($cont['thumb']) ?>" alt="" loading="lazy" class="w-full h-40 object-contain bg-gray-900">
                    <?php else: ?>
                    <div class="h-32 bg-gradient-to-br from-primary to-primary/60 flex items-center justify-center text-white text-3xl">
                        <?= $cont['tipo'] === 'carrossel' ? '📋' : ($cont['tipo'] === 'story' ? '📱' : '🖼️') ?>
                    </div>
                    <?php endif; ?>
                    <div class="p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 font-medium"><?= ucfirst($cont['tipo']) ?><?= $cont['slides'] > 1 ? ' • ' . $cont['slides'] . ' slides' : '' ?></span>
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium <?= $statusCfg['badge'] ?>"><?= $statusCfg['label'] ?></span>
                        </div>
                        <p class="text-sm font-medium text-gray-800 line-clamp-2"><?= htmlspecialchars($cont['titulo']) ?></p>
                        <p class="text-xs text-gray-400 mt-2"><?= date('d/m/Y', strtotime($cont['data'])) ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- ABA TEMPLATES -->
    <div x-show="aba === 'templates'" x-transition style="display:none;">
        <!-- Perfil visual consolidado (modelo próprio da marca) -->
        <div class="bg-gradient-to-br from-primary/5 to-white rounded-lg border border-primary/20 p-5 mb-5">
            <div class="flex items-center justify-between mb-2">
                <h3 class="font-semibold text-gray-800 flex items-center gap-2">🎯 Perfil visual da marca (modelo próprio)</h3>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="salvarPerfil(this)" class="px-3 py-1.5 bg-primary text-white rounded-lg text-xs font-medium hover:bg-primary-700">💾 Salvar</button>
                    <button type="button" onclick="recalcularPerfil(this)" class="px-3 py-1.5 border border-primary/40 text-primary rounded-lg text-xs font-medium hover:bg-primary/10">🔄 Recalcular com IA</button>
                </div>
            </div>
            <p class="text-xs text-gray-500 mb-3">Este texto é usado como guia de estilo na geração das imagens. Edite à mão e clique em Salvar, ou use Recalcular para a IA sintetizar a partir de todas as referências enviadas.</p>
            <textarea id="perfil-templates" rows="6" class="w-full text-sm text-gray-700 bg-white border border-gray-200 rounded-lg p-3 outline-none focus:border-primary resize-y" placeholder="Ainda não há perfil. Envie templates abaixo (ou escreva aqui) e clique em Salvar. É o guia de estilo usado na geração das imagens."><?= htmlspecialchars($dados['perfil_templates'] ?? '') ?></textarea>
        </div>

        <div class="flex flex-wrap items-end justify-between gap-3 mb-4">
            <p class="text-sm text-gray-500">Templates de referência visual da marca. Classifique cada um por objetivo para a IA escolher o mais adequado ao gerar.</p>
            <div class="flex items-end gap-2">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Objetivo do template</label>
                    <select id="sel-categoria-template" class="px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                        <option value="">Deixar a IA classificar</option>
                        <option value="noticia">📰 Notícias</option>
                        <option value="engajamento">💬 Engajamento</option>
                        <option value="impacto">💥 Impacto</option>
                        <option value="educativo">🎓 Educativo</option>
                        <option value="conversao">🎯 Conversão</option>
                        <option value="institucional">🏢 Institucional</option>
                    </select>
                </div>
                <label class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-700 cursor-pointer">
                    + Adicionar Template
                    <input type="file" accept="image/*" class="hidden" onchange="uploadTemplate(this)">
                </label>
            </div>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4" id="grid-templates">
            <!-- Templates carregados do banco aparecerão aqui -->
            <label class="border-2 border-dashed border-gray-300 rounded-lg p-8 flex items-center justify-center text-gray-400 text-sm hover:border-primary hover:text-primary cursor-pointer transition" id="upload-placeholder">
                <span>+ Upload</span>
                <input type="file" accept="image/*" class="hidden" onchange="uploadTemplate(this)">
            </label>
        </div>
        <p class="text-xs text-gray-400 mt-4">Envie imagens de posts que representam o estilo visual desejado. A IA usará como referência.</p>
    </div>

<script>
// ===== Brand Book: salvar configurações e upload de logo =====
document.getElementById('form-branding')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-salvar-branding');
    if (btn) { btn.disabled = true; btn.textContent = 'Salvando...'; }
    try {
        const res = await fetch('<?= APP_URL ?>/maquina-de-conteudo/salvar-branding', { method: 'POST', body: new FormData(this) });
        const data = await res.json();
        if (data.sucesso) {
            if (typeof Toast !== 'undefined') Toast.sucesso('Brand Book salvo!'); else alert('Brand Book salvo!');
        } else {
            alert(data.erro || 'Erro ao salvar.');
        }
    } catch (err) { alert('Erro de conexão.'); }
    if (btn) { btn.disabled = false; btn.textContent = '💾 Salvar Brand Book'; }
});

async function uploadLogo(input) {
    if (!input.files || !input.files[0]) return;
    const fd = new FormData();
    fd.append('csrf_token', '<?= Csrf::token() ?>');
    fd.append('marca_id', '<?= (int) $marca['id'] ?>');
    fd.append('logo', input.files[0]);
    try {
        const res = await fetch('<?= APP_URL ?>/maquina-de-conteudo/upload-logo', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.sucesso && data.url) {
            const img = document.getElementById('logo-preview');
            const vazio = document.getElementById('logo-vazio');
            if (img) { img.src = data.url + '?t=' + Date.now(); img.classList.remove('hidden'); }
            if (vazio) vazio.classList.add('hidden');
            if (typeof Toast !== 'undefined') Toast.sucesso('Logo enviado!');
        } else {
            alert(data.erro || 'Erro no upload do logo.');
        }
    } catch (e) { alert('Erro de conexão.'); }
    input.value = '';
}

// Salva o rascunho na biblioteca para terminar depois.
async function terminarDepois(conteudoId, btn) {
    if (btn) { btn.disabled = true; btn.textContent = 'Salvando...'; }
    try {
        const fd = new FormData();
        fd.append('csrf_token', '<?= Csrf::token() ?>');
        fd.append('conteudo_id', conteudoId);
        const res = await fetch('<?= APP_URL ?>/maquina-de-conteudo/salvar-biblioteca', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.sucesso) {
            if (btn) { btn.textContent = '✓ Salvo na biblioteca'; }
            if (typeof Toast !== 'undefined') Toast.sucesso(data.mensagem || 'Salvo na biblioteca!');
        } else {
            if (btn) { btn.disabled = false; btn.textContent = '📌 Terminar depois'; }
            alert(data.erro || 'Erro ao salvar.');
        }
    } catch (e) {
        if (btn) { btn.disabled = false; btn.textContent = '📌 Terminar depois'; }
        alert('Erro de conexão.');
    }
}

// ===== Biblioteca: exclusão individual e em massa =====
function atualizarBotaoMassa() {
    const marcados = document.querySelectorAll('.chk-conteudo:checked').length;
    const btn = document.getElementById('btn-excluir-massa');
    if (btn) btn.classList.toggle('hidden', marcados === 0);
}

async function excluirConteudo(id, btn) {
    if (!confirm('Excluir este conteúdo? Esta ação não pode ser desfeita.')) return;
    if (btn) btn.disabled = true;
    try {
        const fd = new FormData();
        fd.append('csrf_token', '<?= Csrf::token() ?>');
        fd.append('conteudo_id', id);
        const res = await fetch('<?= APP_URL ?>/maquina-de-conteudo/excluir-conteudo', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.sucesso) {
            document.querySelector('[data-conteudo-id="' + id + '"]')?.remove();
            if (typeof Toast !== 'undefined') Toast.sucesso('Conteúdo excluído!');
            atualizarBotaoMassa();
        } else {
            if (btn) btn.disabled = false;
            alert(data.erro || 'Erro ao excluir.');
        }
    } catch (e) { if (btn) btn.disabled = false; alert('Erro de conexão.'); }
}

async function excluirConteudosSelecionados() {
    const ids = Array.from(document.querySelectorAll('.chk-conteudo:checked')).map(c => c.value);
    if (ids.length === 0) return;
    if (!confirm('Excluir ' + ids.length + ' conteúdo(s) selecionado(s)? Esta ação não pode ser desfeita.')) return;
    try {
        const fd = new FormData();
        fd.append('csrf_token', '<?= Csrf::token() ?>');
        ids.forEach(id => fd.append('ids[]', id));
        const res = await fetch('<?= APP_URL ?>/maquina-de-conteudo/excluir-conteudos', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.sucesso) {
            ids.forEach(id => document.querySelector('[data-conteudo-id="' + id + '"]')?.remove());
            if (typeof Toast !== 'undefined') Toast.sucesso(data.mensagem || 'Conteúdos excluídos!');
            atualizarBotaoMassa();
        } else {
            alert(data.erro || 'Erro ao excluir.');
        }
    } catch (e) { alert('Erro de conexão.'); }
}

// Carregar templates salvos ao abrir a aba
document.addEventListener('DOMContentLoaded', carregarTemplates);

let templatesCarregados = false;
async function carregarTemplates() {
    // Evita renderização duplicada caso a função seja chamada mais de uma vez.
    if (templatesCarregados) return;
    templatesCarregados = true;
    try {
        const res = await fetch('<?= APP_URL ?>/maquina-de-conteudo/templates?marca_id=<?= $marca['id'] ?>');
        const data = await res.json();
        // Remove cards já renderizados (mantém o placeholder de upload).
        document.querySelectorAll('#grid-templates [id^="template-"]').forEach(el => el.remove());
        if (data.sucesso && data.templates.length > 0) {
            const grid = document.getElementById('grid-templates');
            const placeholder = document.getElementById('upload-placeholder');
            data.templates.forEach(t => {
                const card = criarCardTemplate(t.id, '<?= APP_URL ?>' + t.caminho, t.nome_original, t.descricao || '', t.categoria || '');
                grid.insertBefore(card, placeholder);
            });
        }
    } catch(e) { templatesCarregados = false; }
}

// Atualiza a caixa do perfil consolidado (modelo próprio da marca).
function atualizarPerfilNaTela(texto) {
    const box = document.getElementById('perfil-templates');
    if (!box) return;
    box.value = texto;
}

// Salva o texto do perfil editado manualmente (é o guia de estilo usado na geração).
async function salvarPerfil(btn) {
    const box = document.getElementById('perfil-templates');
    if (!box) return;
    if (btn) { btn.disabled = true; btn.textContent = '💾 Salvando...'; }
    try {
        const fd = new FormData();
        fd.append('csrf_token', '<?= Csrf::token() ?>');
        fd.append('marca_id', '<?= (int) $marca['id'] ?>');
        fd.append('perfil', box.value);
        const res = await fetch('<?= APP_URL ?>/maquina-de-conteudo/salvar-perfil-templates', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.sucesso) {
            if (typeof Toast !== 'undefined') Toast.sucesso('Perfil salvo! Será usado na geração das imagens.');
        } else {
            alert(data.erro || 'Não foi possível salvar.');
        }
    } catch (e) { alert('Erro de conexão.'); }
    if (btn) { btn.disabled = false; btn.textContent = '💾 Salvar'; }
}

// Recalcula o perfil consolidado sob demanda (sintetiza todas as referências).
async function recalcularPerfil(btn) {
    if (btn) { btn.disabled = true; btn.textContent = '🔄 Recalculando...'; }
    try {
        const fd = new FormData();
        fd.append('csrf_token', '<?= Csrf::token() ?>');
        fd.append('marca_id', '<?= (int) $marca['id'] ?>');
        const res = await fetch('<?= APP_URL ?>/maquina-de-conteudo/recalcular-perfil-templates', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.sucesso) {
            atualizarPerfilNaTela(data.perfil || 'Sem perfil (envie templates com descrição).');
            if (typeof Toast !== 'undefined') Toast.sucesso('Perfil da marca recalculado!');
        } else {
            alert(data.erro || 'Não foi possível recalcular.');
        }
    } catch (e) { alert('Erro de conexão.'); }
    if (btn) { btn.disabled = false; btn.textContent = '🔄 Recalcular com IA'; }
}

async function uploadTemplate(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];

    const fd = new FormData();
    fd.append('csrf_token', '<?= Csrf::token() ?>');
    fd.append('marca_id', '<?= $marca['id'] ?>');
    fd.append('arquivo', file);
    const catSel = document.getElementById('sel-categoria-template');
    if (catSel && catSel.value) fd.append('categoria', catSel.value);

    try {
        const res = await fetch('<?= APP_URL ?>/maquina-de-conteudo/upload-template', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.sucesso) {
            const grid = document.getElementById('grid-templates');
            const placeholder = document.getElementById('upload-placeholder');
            const card = criarCardTemplate(data.arquivo.id, data.arquivo.url, data.arquivo.nome, data.arquivo.descricao || '', data.arquivo.categoria || '');
            grid.insertBefore(card, placeholder);
            // Atualiza o perfil consolidado (modelo próprio) já com a nova referência.
            if (typeof data.perfil_marca === 'string' && data.perfil_marca.trim() !== '') {
                atualizarPerfilNaTela(data.perfil_marca);
            }
            if (typeof Toast !== 'undefined') Toast.sucesso('Template salvo! Perfil da marca atualizado.');
        } else {
            alert(data.erro || 'Erro no upload.');
        }
    } catch(e) { alert('Erro de conexão.'); }

    input.value = '';
}

const CATEGORIAS_TEMPLATE = {
    'noticia': '📰 Notícias', 'engajamento': '💬 Engajamento', 'impacto': '💥 Impacto',
    'educativo': '🎓 Educativo', 'conversao': '🎯 Conversão', 'institucional': '🏢 Institucional'
};

function criarCardTemplate(id, url, nome, descricao, categoria) {
    const esc = (s) => String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    const cat = (categoria || '').toLowerCase();
    let opcoes = '<option value="">Sem classificação</option>';
    for (const k in CATEGORIAS_TEMPLATE) {
        opcoes += `<option value="${k}" ${k === cat ? 'selected' : ''}>${CATEGORIAS_TEMPLATE[k]}</option>`;
    }
    const card = document.createElement('div');
    card.className = 'rounded-lg overflow-hidden border border-gray-200 bg-white';
    card.id = 'template-' + id;
    card.innerHTML = `
        <div class="relative group">
            <img src="${url}" class="w-full h-32 object-cover" loading="lazy">
            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                <button onclick="removerTemplate(${id})" class="text-white text-xs bg-red-600 px-3 py-1.5 rounded hover:bg-red-700">Remover</button>
            </div>
        </div>
        <div class="p-2">
            <p class="text-xs text-gray-600 truncate mb-1">${esc(nome)}</p>
            <label class="block text-[10px] text-gray-400 mb-1">Objetivo (a IA usa para escolher o template certo)</label>
            <select class="w-full px-2 py-1.5 border border-gray-200 rounded text-[11px] text-gray-700 bg-white mb-2" onchange="atualizarCategoriaTemplate(${id}, this.value)">${opcoes}</select>
            <label class="block text-[10px] text-gray-400 mb-1">Descrição gerada pela IA (estilo detectado)</label>
            <textarea class="w-full px-2 py-1.5 border border-gray-200 rounded text-[11px] text-gray-600 bg-gray-50 resize-none" rows="4" readonly placeholder="${descricao ? '' : 'Sem descrição (reenvie para a IA analisar).'}">${esc(descricao)}</textarea>
        </div>
    `;
    return card;
}

async function atualizarCategoriaTemplate(id, categoria) {
    try {
        const fd = new FormData();
        fd.append('csrf_token', '<?= Csrf::token() ?>');
        fd.append('template_id', id);
        fd.append('categoria', categoria);
        const res = await fetch('<?= APP_URL ?>/maquina-de-conteudo/atualizar-categoria-template', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.sucesso) {
            if (typeof Toast !== 'undefined') Toast.sucesso('Objetivo do template atualizado!');
        } else {
            alert(data.erro || 'Não foi possível atualizar.');
        }
    } catch (e) { alert('Erro de conexão.'); }
}

async function removerTemplate(id) {
    if (!confirm('Remover este template?')) return;
    const fd = new FormData();
    fd.append('csrf_token', '<?= Csrf::token() ?>');
    fd.append('template_id', id);
    try {
        const res = await fetch('<?= APP_URL ?>/maquina-de-conteudo/remover-template', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.sucesso) {
            document.getElementById('template-' + id)?.remove();
            if (typeof Toast !== 'undefined') Toast.sucesso('Template removido!');
        }
    } catch(e) {}
}
</script>

    <!-- ABA PUBLICAÇÃO -->
    <div x-show="aba === 'publicacao'" x-transition style="display:none;">
        <!-- Calendário Editorial -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-800">📅 Calendário Editorial</h3>
                <div class="flex items-center gap-2">
                    <select id="mes-calendario" class="px-3 py-1.5 border border-gray-300 rounded text-sm" onchange="carregarCalendario()">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $m == date('n') ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                        <?php endfor; ?>
                    </select>
                    <select id="ano-calendario" class="px-3 py-1.5 border border-gray-300 rounded text-sm" onchange="carregarCalendario()">
                        <?php for ($a = date('Y') - 1; $a <= date('Y') + 2; $a++): ?>
                        <option value="<?= $a ?>" <?= $a == date('Y') ? 'selected' : '' ?>><?= $a ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            
            <!-- Grade do calendário -->
            <div id="calendario-grid" class="grid grid-cols-7 gap-1 text-center text-xs">
                <div class="font-medium text-gray-500 py-2">Dom</div>
                <div class="font-medium text-gray-500 py-2">Seg</div>
                <div class="font-medium text-gray-500 py-2">Ter</div>
                <div class="font-medium text-gray-500 py-2">Qua</div>
                <div class="font-medium text-gray-500 py-2">Qui</div>
                <div class="font-medium text-gray-500 py-2">Sex</div>
                <div class="font-medium text-gray-500 py-2">Sáb</div>
                <!-- Dias serão carregados via JavaScript -->
            </div>
            
            <!-- Legenda -->
            <div class="flex flex-wrap gap-4 mt-4 text-xs text-gray-500">
                <span class="flex items-center gap-1"><span class="w-3 h-3 bg-gray-200 rounded"></span> Rascunho</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 bg-blue-200 rounded"></span> Aprovado</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 bg-orange-200 rounded"></span> Agendado</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 bg-green-200 rounded"></span> Publicado</span>
            </div>
        </div>

        <!-- Lista de Conteúdos por Status -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Aguardando Publicação -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">📋 Aguardando Publicação</h3>
                    <p class="text-xs text-gray-500 mt-1">Conteúdos aprovados prontos para agendar</p>
                </div>
                <div id="lista-aguardando" class="p-4 space-y-3">
                    <!-- Carregado via JavaScript -->
                    <div class="text-center text-gray-400 py-8">
                        <p class="text-sm">Carregando conteúdos...</p>
                    </div>
                </div>
            </div>
            
            <!-- Agendados -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">🕐 Conteúdos Agendados</h3>
                    <p class="text-xs text-gray-500 mt-1">Programados para publicação automática</p>
                </div>
                <div id="lista-agendados" class="p-4 space-y-3">
                    <!-- Carregado via JavaScript -->
                    <div class="text-center text-gray-400 py-8">
                        <p class="text-sm">Carregando agendamentos...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Agendar -->
<div id="modal-agendar" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-sm w-full p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-2">📅 Agendar Publicação</h3>
        <p class="text-sm text-gray-500 mb-4" id="agendar-titulo-post"></p>
        <div class="space-y-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data</label>
                <input type="date" id="agendar-data" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Horário</label>
                <input type="time" id="agendar-hora" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary" value="10:00">
            </div>
        </div>
        <input type="hidden" id="agendar-conteudo-id" value="">
        <div class="flex gap-2 mt-5">
            <button onclick="fecharModalAgendar()" class="flex-1 border border-gray-300 py-2.5 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancelar</button>
            <button onclick="confirmarAgendamento()" class="flex-1 bg-primary text-white py-2.5 rounded-lg text-sm font-medium hover:bg-primary-700">Confirmar</button>
        </div>
    </div>
</div>

<!-- Modal Publicar -->
<div id="modal-publicar" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <div class="text-center">
            <span class="text-4xl mb-4 inline-block">📢</span>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Publicação Manual</h3>
            <p class="text-sm text-gray-500 mb-4">A publicação automática chegará em breve. Por enquanto, faça o download do conteúdo e publique manualmente.</p>
        </div>
        
        <div class="space-y-3">
            <button onclick="baixarConteudo()" class="w-full bg-primary text-white py-2.5 rounded-lg text-sm font-medium hover:bg-primary-700 flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                📥 Download do Conteúdo (ZIP)
            </button>
            
            <button onclick="marcarComoPublicado()" class="w-full bg-green-600 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-green-700">
                ✅ Marcar como Publicado
            </button>
            
            <button onclick="fecharModalPublicar()" class="w-full border border-gray-300 py-2.5 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                Fechar
            </button>
        </div>
        
        <input type="hidden" id="publicar-conteudo-id" value="">
    </div>
</div>

<script>
let conteudosData = {
    aguardando: [],
    agendados: [],
    publicados: []
};

// O carregamento do calendário/listas é acionado pelo Alpine ao abrir a aba
// Publicação (ver x-init/@click no topo). Este bloco ficou apenas como nota.

// === GERAÇÃO DE CONTEÚDO ===

document.getElementById('sel-estilo-img').addEventListener('change', function() {
    document.getElementById('prompt-dalle-preview').classList.toggle('hidden', this.value !== 'ia');
    // O seletor de qualidade só faz sentido quando a imagem é gerada por IA.
    const blocoQ = document.getElementById('bloco-qualidade');
    if (blocoQ) blocoQ.classList.toggle('hidden', this.value !== 'ia');
});

document.getElementById('form-gerar').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-gerar');
    const preview = document.getElementById('resultado-preview');
    
    // Estado de carregamento
    btn.disabled = true;
    btn.textContent = '⏳ Gerando texto...';
    preview.innerHTML = '<div class="text-center"><div class="inline-block w-8 h-8 border-4 border-gray-200 border-t-accent rounded-full animate-spin"></div><p class="text-xs text-gray-500 mt-2">Passo 1: Gerando texto...</p></div>';
    
    try {
        const fd = new FormData(this);
        const res = await fetch('<?= APP_URL ?>/maquina/gerar', { method:'POST', body:fd });
        const data = await res.json();
        
        if (data.sucesso) {
            // Renderiza o texto imediatamente; as imagens são geradas uma a uma depois.
            const slides = data.conteudo.slides || [];
            let html = '<div class="space-y-3">';
            slides.forEach((s, index) => {
                const pendente = s.imagem_pendente || (data.slides_pendentes || []).includes(index);
                const imgHtml = s.imagem_url
                    ? `<img src="${s.imagem_url}" class="w-full max-h-[480px] object-contain bg-gray-900" loading="lazy">`
                    : (pendente
                        ? `<div class="w-full aspect-[4/5] bg-gray-100 flex flex-col items-center justify-center text-xs text-gray-400 gap-2" id="slide-img-${index}">
                                <span class="flex items-center"><span class="inline-block w-5 h-5 border-2 border-gray-300 border-t-accent rounded-full animate-spin mr-2"></span> <span class="slide-status-txt">Na fila...</span></span>
                                <button type="button" onclick="cancelarImagemSlide(${index})" class="px-2 py-1 border border-red-300 text-red-600 rounded text-[11px] hover:bg-red-50 slide-cancel-btn">Cancelar</button>
                           </div>`
                        : `<div class="w-full aspect-[4/5] bg-gray-100 flex items-center justify-center text-3xl">🖼️</div>`);
                html += `<div class="border rounded-lg overflow-hidden" data-slide="${index}">
                    ${imgHtml}
                    <div class="p-3"><p class="text-xs text-gray-600">${s.texto || 'Slide ' + (index + 1)}</p></div>
                </div>`;
            });
            if (data.conteudo.legenda) {
                html += `<div class="p-3 bg-gray-50 rounded-lg"><p class="text-xs text-gray-500 font-medium mb-1">Legenda:</p><p class="text-xs text-gray-700 whitespace-pre-line">${data.conteudo.legenda}</p></div>`;
            }
            html += '<div class="flex flex-wrap gap-2 mt-3 items-center">';
            const editarUrl = data.redirect_url || '<?= APP_URL ?>/maquina-de-conteudo/editar';
            html += `<a href="${editarUrl}" class="px-3 py-2 bg-primary text-white rounded text-xs font-medium">✏️ Editar Conteúdo</a>`;
            if (data.conteudo_id) {
                html += `<button type="button" onclick="terminarDepois(${data.conteudo_id}, this)" class="px-3 py-2 border border-gray-300 rounded text-xs font-medium text-gray-700 hover:bg-gray-50">📌 Terminar depois</button>`;
            }
            html += '<span id="status-imagens" class="flex-1 px-3 py-2 border border-gray-300 rounded text-xs text-gray-500 text-center">💾 Rascunho salvo</span>';
            html += '<button type="button" id="btn-cancelar-imagens" onclick="cancelarTodasImagens()" class="hidden px-3 py-2 border border-red-300 text-red-600 rounded text-xs font-medium hover:bg-red-50">✕ Cancelar todas</button>';
            html += '</div></div>';
            preview.innerHTML = html;

            if (typeof Toast !== 'undefined') Toast.sucesso(data.mensagem || 'Texto gerado!');

            // Gera as imagens pendentes uma a uma (sem estourar timeout do proxy).
            const pendentes = data.slides_pendentes || [];
            if (data.gerar_imagens && data.conteudo_id && pendentes.length > 0) {
                await gerarImagensSequencial(data.conteudo_id, pendentes);
            }
        } else {
            preview.innerHTML = '<p class="text-red-500 text-sm">' + (data.erro || 'Erro na geração.') + '</p>';
        }
    } catch(e) {
        console.error('Erro na geração:', e);
        preview.innerHTML = '<p class="text-red-500 text-sm">Erro de conexão. Tente novamente.</p>';
    }
    
    // Restaurar botão
    btn.disabled = false;
    btn.textContent = '⚡ Gerar Conteúdo';
});

// Controle de cancelamento da geração de imagens.
let geracaoImagens = {
    cancelarTudo: false,       // cancela toda a fila
    canceladosSlide: {},       // { idx: true } — slides cancelados individualmente
    controllerAtual: null,     // AbortController da request em andamento
    idxAtual: null,            // slide sendo gerado agora
};

// Cancela um slide específico (marca na fila do servidor; o que ainda não
// começou não será gerado).
function cancelarImagemSlide(idx) {
    geracaoImagens.canceladosSlide[idx] = true;
    if (geracaoImagens.conteudoId) cancelarImagemSlideServidor(geracaoImagens.conteudoId, idx);
    marcarSlideCancelado(idx);
}

// Cancela toda a geração restante (marca todos os pendentes na fila do servidor).
function cancelarTodasImagens() {
    geracaoImagens.cancelarTudo = true;
    if (geracaoImagens.conteudoId) {
        const fd = new FormData();
        fd.append('csrf_token', '<?= Csrf::token() ?>');
        fd.append('conteudo_id', geracaoImagens.conteudoId);
        fetch('<?= APP_URL ?>/maquina-de-conteudo/cancelar-imagens', { method: 'POST', body: fd }).catch(() => {});
    }
    const status = document.getElementById('status-imagens');
    if (status) status.textContent = '✕ Cancelando...';
}

// Ajusta o placeholder de um slide para o estado "cancelado".
function marcarSlideCancelado(idx) {
    const ph = document.getElementById('slide-img-' + idx);
    if (ph && !ph.querySelector('img')) {
        ph.className = 'w-full aspect-[4/5] bg-gray-100 flex items-center justify-center text-xs text-gray-400';
        ph.textContent = 'Cancelado';
    }
}

// As imagens são geradas em BACKGROUND (worker no servidor) porque cada uma
// leva mais que o timeout do proxy. Aqui apenas fazemos POLLING do status e
// atualizamos o preview conforme cada imagem fica pronta.
async function gerarImagensSequencial(conteudoId, indices) {
    const status = document.getElementById('status-imagens');
    const btnCancelarTodas = document.getElementById('btn-cancelar-imagens');
    if (btnCancelarTodas) btnCancelarTodas.classList.remove('hidden');

    geracaoImagens = { cancelarTudo: false, canceladosSlide: {}, controllerAtual: null, idxAtual: null, conteudoId };

    const esperar = (ms) => new Promise(r => setTimeout(r, ms));
    const total = indices.length;
    const maxTentativas = 300; // ~10 min (2s entre polls) — imagens levam ~60s cada

    for (let t = 0; t < maxTentativas; t++) {
        if (geracaoImagens.cancelarTudo) break;

        let data;
        try {
            const res = await fetch('<?= APP_URL ?>/maquina-de-conteudo/status-imagens?conteudo_id=' + conteudoId + '&_=' + Date.now());
            data = await res.json();
        } catch (e) { await esperar(2000); continue; }

        if (!data || !data.sucesso) { await esperar(2000); continue; }

        // Dispara o processador em BACKGROUND. Esse endpoint responde na hora
        // (fecha a conexão) e segue gerando as imagens no servidor, sem estourar
        // o timeout do proxy. O lock garante uma única execução simultânea.
        if (data.pendentes > 0) {
            fetch('<?= APP_URL ?>/maquina-de-conteudo/processar-imagens-bg?_=' + Date.now()).catch(() => {});
        }

        // Loga no console do navegador o detalhe de cada item (payload + custo),
        // sem repetir a mesma linha a cada poll.
        window.__logImgVistos = window.__logImgVistos || {};
        (data.itens || []).forEach(item => {
            if (item.mensagem) {
                const chave = item.slide_index + ':' + item.status + ':' + item.mensagem;
                if (!window.__logImgVistos[chave]) {
                    window.__logImgVistos[chave] = true;
                    console.log('[IMG slide ' + item.slide_index + '] status=' + item.status + '\n' + item.mensagem);
                }
            }
        });

        let prontas = 0;
        (data.itens || []).forEach(item => {
            const idx = item.slide_index;
            const container = document.querySelector(`[data-slide="${idx}"]`);
            if (item.status === 'concluido' && item.imagem_url && container) {
                prontas++;
                const jaTem = container.querySelector('img');
                if (!jaTem) {
                    const alvo = container.querySelector('div');
                    const img = document.createElement('img');
                    img.src = item.imagem_url;
                    img.className = 'w-full max-h-[480px] object-contain bg-gray-900';
                    img.loading = 'lazy';
                    if (alvo) alvo.replaceWith(img);
                    // Adiciona controles de regeneração com instrução dentro do card.
                    adicionarControlesRegeneracao(container, conteudoId, idx);
                }
            } else if (item.status === 'erro' && container) {
                const p = container.querySelector('#slide-img-' + idx);
                if (p && !p.querySelector('img')) { p.className = 'w-full aspect-[4/5] bg-gray-100 flex items-center justify-center text-xs text-red-400'; p.textContent = 'Falha'; }
            }
        });

        if (status) status.textContent = `🎨 Gerando imagens ${prontas}/${total}...`;

        if (data.concluido) {
            if (status) status.textContent = '✅ Concluído';
            if (btnCancelarTodas) btnCancelarTodas.classList.add('hidden');
            if (typeof Toast !== 'undefined') Toast.sucesso('Imagens geradas!');
            return;
        }

        await esperar(2000);
    }

    if (btnCancelarTodas) btnCancelarTodas.classList.add('hidden');
    if (geracaoImagens.cancelarTudo) {
        if (status) status.textContent = '✕ Cancelado (as imagens já em geração podem concluir no servidor)';
    } else if (status) {
        status.textContent = '⏳ As imagens continuam sendo geradas em segundo plano. Recarregue em instantes.';
    }
}

// Adiciona, dentro do card do slide, um textarea + botão para regenerar a
// imagem com uma instrução de ajuste do usuário (corrige só aquela imagem).
function adicionarControlesRegeneracao(container, conteudoId, idx) {
    if (!container || container.querySelector('.regen-box')) return;
    const box = document.createElement('div');
    box.className = 'regen-box p-3 border-t border-gray-100';
    box.innerHTML =
        '<textarea class="regen-txt w-full px-2 py-1.5 border border-gray-300 rounded text-xs outline-none focus:border-primary resize-none" rows="2" placeholder="O que ajustar nesta imagem? Ex.: fundo mais claro, incluir uma pessoa, tom mais sério..."></textarea>'
        + '<button type="button" class="regen-btn mt-2 w-full px-3 py-1.5 bg-primary text-white rounded text-xs font-medium hover:bg-primary-700">🔄 Regenerar esta imagem</button>'
        + '<a href="<?= APP_URL ?>/maquina-de-conteudo/imagem/prompt/' + conteudoId + '/' + idx + '" target="_blank" class="block mt-2 text-center text-[11px] text-blue-600 hover:underline">🔍 Ver prompts (imagem, legenda e fonte usada)</a>';
    container.appendChild(box);
    box.querySelector('.regen-btn').addEventListener('click', () => regenerarComInstrucao(conteudoId, idx, container));
}

async function regenerarComInstrucao(conteudoId, idx, container) {
    const txt = container.querySelector('.regen-txt');
    const btn = container.querySelector('.regen-btn');
    const instrucao = txt ? txt.value.trim() : '';
    if (btn) { btn.disabled = true; btn.textContent = '🔄 Na fila...'; }

    const img = container.querySelector('img');
    if (img) img.style.opacity = '0.4';

    try {
        // 1) Enfileira a regeneração (com a instrução de correção).
        const fd = new FormData();
        fd.append('csrf_token', '<?= Csrf::token() ?>');
        fd.append('conteudo_id', conteudoId);
        fd.append('slide_index', idx);
        fd.append('instrucao', instrucao);
        const res = await fetch('<?= APP_URL ?>/maquina-de-conteudo/gerar-imagem-slide', { method: 'POST', body: fd });
        const data = await res.json();
        if (!data.sucesso) {
            if (img) img.style.opacity = '1';
            if (btn) { btn.disabled = false; btn.textContent = '🔄 Regenerar esta imagem'; }
            alert(data.erro || 'Falha ao enfileirar.');
            return;
        }

        // 2) Processa em background e faz polling do status DESSE slide.
        const esperar = (ms) => new Promise(r => setTimeout(r, ms));
        for (let t = 0; t < 150; t++) {
            fetch('<?= APP_URL ?>/maquina-de-conteudo/processar-imagens-bg?_=' + Date.now()).catch(() => {});
            await esperar(2500);
            let st;
            try {
                const r = await fetch('<?= APP_URL ?>/maquina-de-conteudo/status-imagens?conteudo_id=' + conteudoId + '&_=' + Date.now());
                st = await r.json();
            } catch (e) { continue; }
            const item = (st.itens || []).find(i => i.slide_index === idx);
            if (item && item.mensagem) console.log('[IMG slide ' + idx + '] status=' + item.status + '\n' + item.mensagem);
            if (item && item.status === 'concluido' && item.imagem_url) {
                if (img) { img.src = item.imagem_url + '?t=' + Date.now(); img.style.opacity = '1'; }
                if (btn) { btn.disabled = false; btn.textContent = '🔄 Regenerar esta imagem'; }
                if (typeof Toast !== 'undefined') Toast.sucesso('Imagem regenerada!');
                return;
            }
            if (item && item.status === 'erro') {
                if (img) img.style.opacity = '1';
                if (btn) { btn.disabled = false; btn.textContent = '🔄 Regenerar esta imagem'; }
                if (typeof Toast !== 'undefined') Toast.erro('Falha ao regenerar.'); else alert('Falha ao regenerar.');
                return;
            }
        }
        // Tempo esgotado
        if (img) img.style.opacity = '1';
        if (btn) { btn.disabled = false; btn.textContent = '🔄 Regenerar esta imagem'; }
    } catch (e) {
        if (img) img.style.opacity = '1';
        if (btn) { btn.disabled = false; btn.textContent = '🔄 Regenerar esta imagem'; }
        alert('Erro de conexão ao regenerar.');
    }
}

// Cancela a geração de UM slide no servidor (marca como cancelado na fila).
async function cancelarImagemSlideServidor(conteudoId, idx) {
    try {
        const fd = new FormData();
        fd.append('csrf_token', '<?= Csrf::token() ?>');
        fd.append('conteudo_id', conteudoId);
        fd.append('slide_index', idx);
        await fetch('<?= APP_URL ?>/maquina-de-conteudo/cancelar-imagem-slide', { method: 'POST', body: fd });
    } catch (e) { /* best-effort */ }
}

// === CALENDÁRIO EDITORIAL ===

async function carregarCalendario() {
    const mes = document.getElementById('mes-calendario').value;
    const ano = document.getElementById('ano-calendario').value;
    
    try {
        const res = await fetch(`<?= APP_URL ?>/maquina/calendario?marca_id=<?= $marca['id'] ?>&mes=${mes}&ano=${ano}`);
        const data = await res.json();
        
        if (data.sucesso) {
            renderizarCalendario(data.mes, data.ano, data.conteudos);
        }
    } catch(e) {
        console.error('Erro ao carregar calendário:', e);
    }
}

function renderizarCalendario(mes, ano, conteudos) {
    const grid = document.getElementById('calendario-grid');
    const diasSemana = grid.querySelectorAll('div:nth-child(-n+7)'); // Manter cabeçalho
    
    // Limpar dias anteriores (manter cabeçalho)
    const diasAntigos = grid.querySelectorAll('div:nth-child(n+8)');
    diasAntigos.forEach(dia => dia.remove());
    
    // Calcular primeiro dia do mês e quantos dias tem
    const primeiroDia = new Date(ano, mes - 1, 1).getDay();
    const diasNoMes = new Date(ano, mes, 0).getDate();
    
    // Adicionar espaços vazios antes do primeiro dia
    for (let i = 0; i < primeiroDia; i++) {
        const espacoVazio = document.createElement('div');
        espacoVazio.className = 'py-2';
        grid.appendChild(espacoVazio);
    }
    
    // Adicionar dias do mês
    for (let dia = 1; dia <= diasNoMes; dia++) {
        const divDia = document.createElement('div');
        divDia.className = 'py-2 rounded cursor-pointer hover:bg-gray-100 relative';
        divDia.textContent = dia;
        
        // Verificar se há conteúdo neste dia
        const conteudoDoDia = conteudos.filter(c => {
            const dataConteudo = new Date(c.agendado_para || c.data_publicacao_real);
            return dataConteudo.getDate() === dia;
        });
        
        if (conteudoDoDia.length > 0) {
            const primeiroConteudo = conteudoDoDia[0];
            const cor = primeiroConteudo.status === 'agendado' ? 'bg-orange-200 text-orange-800' : 
                       primeiroConteudo.status === 'publicado' ? 'bg-green-200 text-green-800' : 'bg-blue-200 text-blue-800';
            
            divDia.className = `py-2 rounded cursor-pointer ${cor}`;
            divDia.title = conteudoDoDia.map(c => c.tema).join(' | ') + ' (clique para abrir)';
            // Clicar no dia abre a edição do conteúdo (o primeiro do dia).
            divDia.addEventListener('click', () => {
                window.location.href = '<?= APP_URL ?>/maquina-de-conteudo/editar/' + primeiroConteudo.id;
            });
            
            if (conteudoDoDia.length > 1) {
                const badge = document.createElement('span');
                badge.className = 'absolute -top-1 -right-1 bg-gray-600 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center';
                badge.textContent = conteudoDoDia.length;
                divDia.appendChild(badge);
            }
        }
        
        grid.appendChild(divDia);
    }
}

// === LISTAS DE CONTEÚDO ===

async function carregarDadosPublicacao() {
    try {
        const res = await fetch(`<?= APP_URL ?>/maquina-de-conteudo/marca?id=<?= $marca['id'] ?>&dados=publicacao&ajax=1`);
        if (!res.ok) {
            // Fallback: usar dados já disponíveis
            renderizarListasConteudo();
            return;
        }
        
        const data = await res.json();
        if (data.sucesso) {
            conteudosData = data.conteudos;
            renderizarListasConteudo();
        }
    } catch(e) {
        console.error('Erro ao carregar dados:', e);
        // Usar dados mock ou já disponíveis
        renderizarListasConteudo();
    }
}

function renderizarListasConteudo() {
    renderizarListaAguardando();
    renderizarListaAgendados();
}

function renderizarListaAguardando() {
    const lista = document.getElementById('lista-aguardando');
    
    // Simular dados aprovados dos conteúdos já disponíveis
    const conteudosAprovados = <?= json_encode(array_filter($dados['conteudos'], fn($c) => $c['status'] === 'aprovado')) ?>;
    
    if (conteudosAprovados.length === 0) {
        lista.innerHTML = '<div class="text-center text-gray-400 py-8"><p class="text-sm">Nenhum conteúdo aguardando publicação</p></div>';
        return;
    }
    
    let html = '';
    conteudosAprovados.forEach(conteudo => {
        html += `
            <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg border border-blue-200">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-800">${conteudo.titulo || conteudo.tema}</p>
                    <p class="text-xs text-gray-500">${conteudo.tipo} • Aprovado em ${new Date(conteudo.data || conteudo.criado_em).toLocaleDateString('pt-BR')}</p>
                </div>
                <div class="flex gap-2">
                    <button onclick="abrirModalAgendar('${conteudo.titulo || conteudo.tema}', ${conteudo.id})" class="px-3 py-1.5 border border-gray-300 bg-white rounded text-xs hover:bg-gray-50">📅 Agendar</button>
                    <button onclick="abrirModalPublicar(${conteudo.id})" class="px-3 py-1.5 bg-green-600 text-white rounded text-xs hover:bg-green-700">📢 Publicar</button>
                </div>
            </div>
        `;
    });
    
    lista.innerHTML = html;
}

function renderizarListaAgendados() {
    const lista = document.getElementById('lista-agendados');
    
    // Simular dados agendados
    const conteudosAgendados = <?= json_encode(array_filter($dados['conteudos'], fn($c) => $c['status'] === 'agendado')) ?>;
    
    if (conteudosAgendados.length === 0) {
        lista.innerHTML = '<div class="text-center text-gray-400 py-8"><p class="text-sm">Nenhum conteúdo agendado</p></div>';
        return;
    }
    
    let html = '';
    conteudosAgendados.forEach(conteudo => {
        const dataAgendamento = new Date(conteudo.agendado_para || conteudo.data);
        html += `
            <div class="flex items-center justify-between p-3 bg-orange-50 rounded-lg border border-orange-200">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-800">${conteudo.titulo || conteudo.tema}</p>
                    <p class="text-xs text-gray-500">📅 ${dataAgendamento.toLocaleDateString('pt-BR')} às ${dataAgendamento.toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'})}</p>
                </div>
                <div class="flex gap-2">
                    <button onclick="reagendar(${conteudo.id})" class="px-3 py-1.5 border border-orange-300 bg-white rounded text-xs hover:bg-gray-50">📝 Reagendar</button>
                    <button onclick="abrirModalPublicar(${conteudo.id})" class="px-3 py-1.5 bg-green-600 text-white rounded text-xs hover:bg-green-700">📢 Publicar Agora</button>
                </div>
            </div>
        `;
    });
    
    lista.innerHTML = html;
}

// === MODAL FUNCTIONS ===

function abrirModalAgendar(titulo, conteudoId) {
    document.getElementById('agendar-titulo-post').textContent = titulo;
    document.getElementById('agendar-conteudo-id').value = conteudoId;
    document.getElementById('modal-agendar').classList.remove('hidden');
}

function fecharModalAgendar() {
    document.getElementById('modal-agendar').classList.add('hidden');
}

function abrirModalPublicar(conteudoId) {
    document.getElementById('publicar-conteudo-id').value = conteudoId;
    document.getElementById('modal-publicar').classList.remove('hidden');
}

function fecharModalPublicar() {
    document.getElementById('modal-publicar').classList.add('hidden');
}

// === AGENDAMENTO ===

async function confirmarAgendamento() {
    const conteudoId = document.getElementById('agendar-conteudo-id').value;
    const data = document.getElementById('agendar-data').value;
    const hora = document.getElementById('agendar-hora').value;
    
    if (!data || !hora) {
        alert('Preencha data e horário.');
        return;
    }
    
    const dataHora = data + ' ' + hora + ':00';
    
    const fd = new FormData();
    fd.append('csrf_token', '<?= Csrf::token() ?>');
    fd.append('conteudo_id', conteudoId);
    fd.append('data_hora_publicacao', dataHora);
    
    try {
        const res = await fetch('<?= APP_URL ?>/maquina/agendar', { method: 'POST', body: fd });
        const data = await res.json();
        
        if (data.sucesso) {
            fecharModalAgendar();
            if (typeof Toast !== 'undefined') {
                Toast.sucesso(data.mensagem);
            } else {
                alert(data.mensagem);
            }
            // Recarregar dados
            carregarDadosPublicacao();
            carregarCalendario();
        } else {
            alert(data.erro || 'Erro ao agendar.');
        }
    } catch(e) {
        alert('Erro de conexão.');
    }
}

function reagendar(conteudoId) {
    // Reutilizar modal de agendamento
    abrirModalAgendar('Reagendar conteúdo', conteudoId);
}

// === PUBLICAÇÃO ===

async function baixarConteudo() {
    const conteudoId = document.getElementById('publicar-conteudo-id').value;
    
    if (!conteudoId) {
        alert('ID do conteúdo não encontrado.');
        return;
    }
    
    // Abrir download em nova aba
    window.open(`<?= APP_URL ?>/maquina/download/${conteudoId}`, '_blank');
    
    if (typeof Toast !== 'undefined') {
        Toast.sucesso('Download iniciado!');
    }
}

async function marcarComoPublicado() {
    const conteudoId = document.getElementById('publicar-conteudo-id').value;
    
    if (!conteudoId) {
        alert('ID do conteúdo não encontrado.');
        return;
    }
    
    if (!confirm('Marcar este conteúdo como publicado?')) {
        return;
    }
    
    const fd = new FormData();
    fd.append('csrf_token', '<?= Csrf::token() ?>');
    fd.append('conteudo_id', conteudoId);
    fd.append('canal', 'manual');
    
    try {
        const res = await fetch('<?= APP_URL ?>/maquina/marcar-publicado', { method: 'POST', body: fd });
        const data = await res.json();
        
        if (data.sucesso) {
            fecharModalPublicar();
            if (typeof Toast !== 'undefined') {
                Toast.sucesso(data.mensagem);
            } else {
                alert(data.mensagem);
            }
            // Recarregar dados
            carregarDadosPublicacao();
            carregarCalendario();
        } else {
            alert(data.erro || 'Erro ao marcar como publicado.');
        }
    } catch(e) {
        alert('Erro de conexão.');
    }
}

// === LEGACY SUPPORT (manter compatibilidade) ===

function publicarAgora() {
    // Implementação antiga - redirecionar para nova
    abrirModalPublicar(0); // ID será definido contextualmente
}
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
