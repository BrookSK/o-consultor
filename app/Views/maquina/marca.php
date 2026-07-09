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
    <div class="w-14 h-14 rounded-full bg-primary text-white flex items-center justify-center text-xl font-bold"><?= strtoupper(substr($marca['nome'], 0, 1)) ?></div>
    <div>
        <h1 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($marca['nome']) ?></h1>
        <p class="text-sm text-gray-500"><?= htmlspecialchars($marca['nicho']) ?> • Tom: <?= htmlspecialchars($marca['tom']) ?> • Arquétipo: <?= htmlspecialchars($marca['arquetipo']) ?></p>
    </div>
</div>

<!-- 4 Abas -->
<div x-data="{ aba: 'gerar' }">
    <div class="border-b border-gray-200 mb-6">
        <nav class="flex gap-0 overflow-x-auto">
            <button @click="aba = 'gerar'" :class="aba === 'gerar' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500'" class="px-5 py-3 text-sm whitespace-nowrap">⚡ Gerar Conteúdo</button>
            <button @click="aba = 'biblioteca'" :class="aba === 'biblioteca' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500'" class="px-5 py-3 text-sm whitespace-nowrap">📚 Biblioteca</button>
            <button @click="aba = 'templates'" :class="aba === 'templates' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500'" class="px-5 py-3 text-sm whitespace-nowrap">🎨 Templates</button>
            <button @click="aba = 'publicacao'" :class="aba === 'publicacao' ? 'border-b-2 border-primary text-primary font-semibold' : 'text-gray-500'" class="px-5 py-3 text-sm whitespace-nowrap">📅 Publicação</button>
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
                            <option value="educar">Educar</option><option value="engajar">Engajar</option><option value="converter">Converter</option><option value="inspirar">Inspirar</option><option value="informar">Informar</option>
                        </select>
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

    <!-- ABA BIBLIOTECA -->
    <div x-show="aba === 'biblioteca'" x-transition style="display:none;">
        <div class="flex flex-wrap gap-2 mb-4">
            <select class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs outline-none"><option>Todos tipos</option><option>Carrossel</option><option>Post</option><option>Story</option></select>
            <select class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs outline-none"><option>Todos status</option><option>Rascunho</option><option>Aprovado</option><option>Agendado</option><option>Publicado</option></select>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($dados['conteudos'] as $cont):
                $statusCfg = match($cont['status']) {
                    'aprovado' => ['badge' => 'bg-blue-100 text-blue-700', 'label' => 'Aprovado'],
                    'agendado' => ['badge' => 'bg-orange-100 text-orange-700', 'label' => 'Agendado'],
                    'publicado' => ['badge' => 'bg-green-100 text-green-700', 'label' => 'Publicado'],
                    default => ['badge' => 'bg-gray-100 text-gray-600', 'label' => 'Rascunho'],
                };
            ?>
            <div onclick="window.location.href='<?= APP_URL ?>/maquina-de-conteudo/editar'" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition cursor-pointer">
                <div class="h-32 bg-gradient-to-br from-primary to-primary/60 flex items-center justify-center text-white text-3xl">
                    <?= $cont['tipo'] === 'carrossel' ? '📋' : ($cont['tipo'] === 'story' ? '📱' : '🖼️') ?>
                </div>
                <div class="p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 font-medium"><?= ucfirst($cont['tipo']) ?><?= $cont['slides'] > 1 ? ' • ' . $cont['slides'] . ' slides' : '' ?></span>
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium <?= $statusCfg['badge'] ?>"><?= $statusCfg['label'] ?></span>
                    </div>
                    <p class="text-sm font-medium text-gray-800 line-clamp-2"><?= htmlspecialchars($cont['titulo']) ?></p>
                    <p class="text-xs text-gray-400 mt-2"><?= date('d/m/Y', strtotime($cont['data'])) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ABA TEMPLATES -->
    <div x-show="aba === 'templates'" x-transition style="display:none;">
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">Templates de referência visual da marca.</p>
            <label class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-700 cursor-pointer">
                + Adicionar Template
                <input type="file" accept="image/*" class="hidden" onchange="uploadTemplate(this)">
            </label>
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
                const card = criarCardTemplate(t.id, '<?= APP_URL ?>' + t.caminho, t.nome_original);
                grid.insertBefore(card, placeholder);
            });
        }
    } catch(e) { templatesCarregados = false; }
}

async function uploadTemplate(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];

    const fd = new FormData();
    fd.append('csrf_token', '<?= Csrf::token() ?>');
    fd.append('marca_id', '<?= $marca['id'] ?>');
    fd.append('arquivo', file);

    try {
        const res = await fetch('<?= APP_URL ?>/maquina-de-conteudo/upload-template', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.sucesso) {
            const grid = document.getElementById('grid-templates');
            const placeholder = document.getElementById('upload-placeholder');
            const card = criarCardTemplate(data.arquivo.id, data.arquivo.url, data.arquivo.nome);
            grid.insertBefore(card, placeholder);
            if (typeof Toast !== 'undefined') Toast.sucesso('Template salvo!');
        } else {
            alert(data.erro || 'Erro no upload.');
        }
    } catch(e) { alert('Erro de conexão.'); }

    input.value = '';
}

function criarCardTemplate(id, url, nome) {
    const card = document.createElement('div');
    card.className = 'relative rounded-lg overflow-hidden border border-gray-200 group';
    card.id = 'template-' + id;
    card.innerHTML = `
        <img src="${url}" class="w-full h-32 object-cover" loading="lazy">
        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
            <button onclick="removerTemplate(${id})" class="text-white text-xs bg-red-600 px-3 py-1.5 rounded hover:bg-red-700">Remover</button>
        </div>
        <p class="text-xs text-gray-600 p-2 truncate">${nome}</p>
    `;
    return card;
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

// Carregar dados ao mudar para aba publicação
document.addEventListener('DOMContentLoaded', function() {
    // Observar mudanças de aba
    document.addEventListener('click', function(e) {
        if (e.target.textContent?.includes('Publicação')) {
            setTimeout(() => {
                carregarDadosPublicacao();
                carregarCalendario();
            }, 100);
        }
    });
    // Nota: carregarTemplates() já é chamado no DOMContentLoaded do bloco de
    // templates acima — não chamar de novo aqui (causava templates duplicados).
});

// === GERAÇÃO DE CONTEÚDO ===

document.getElementById('sel-estilo-img').addEventListener('change', function() {
    document.getElementById('prompt-dalle-preview').classList.toggle('hidden', this.value !== 'ia');
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
                    ? `<img src="${s.imagem_url}" class="w-full aspect-[4/5] object-cover" loading="lazy">`
                    : (pendente
                        ? `<div class="w-full aspect-[4/5] bg-gray-100 flex items-center justify-center text-xs text-gray-400" id="slide-img-${index}"><div class="inline-block w-5 h-5 border-2 border-gray-300 border-t-accent rounded-full animate-spin mr-2"></div> Gerando imagem...</div>`
                        : `<div class="w-full aspect-[4/5] bg-gray-100 flex items-center justify-center text-3xl">🖼️</div>`);
                html += `<div class="border rounded-lg overflow-hidden" data-slide="${index}">
                    ${imgHtml}
                    <div class="p-3"><p class="text-xs text-gray-600">${s.texto || 'Slide ' + (index + 1)}</p></div>
                </div>`;
            });
            if (data.conteudo.legenda) {
                html += `<div class="p-3 bg-gray-50 rounded-lg"><p class="text-xs text-gray-500 font-medium mb-1">Legenda:</p><p class="text-xs text-gray-700 whitespace-pre-line">${data.conteudo.legenda}</p></div>`;
            }
            html += '<div class="flex gap-2 mt-3">';
            const editarUrl = data.redirect_url || '<?= APP_URL ?>/maquina-de-conteudo/editar';
            html += `<a href="${editarUrl}" class="flex-1 text-center px-3 py-2 bg-primary text-white rounded text-xs font-medium">✏️ Editar Conteúdo</a>`;
            html += '<span id="status-imagens" class="flex-1 px-3 py-2 border border-gray-300 rounded text-xs text-gray-500 text-center">💾 Rascunho salvo</span>';
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

// Gera as imagens dos slides pendentes SEQUENCIALMENTE (1 request por imagem),
// atualizando o preview conforme cada uma fica pronta.
async function gerarImagensSequencial(conteudoId, indices) {
    const status = document.getElementById('status-imagens');
    let feitas = 0;
    for (const idx of indices) {
        if (status) status.textContent = `🎨 Gerando imagem ${feitas + 1}/${indices.length}...`;
        try {
            const fd = new FormData();
            fd.append('csrf_token', '<?= Csrf::token() ?>');
            fd.append('conteudo_id', conteudoId);
            fd.append('slide_index', idx);
            const res = await fetch('<?= APP_URL ?>/maquina-de-conteudo/gerar-imagem-slide', { method: 'POST', body: fd });
            const data = await res.json();
            const container = document.querySelector(`[data-slide="${idx}"]`);
            if (data.sucesso && data.imagem_url && container) {
                const alvo = container.querySelector('img, div');
                const img = document.createElement('img');
                img.src = data.imagem_url;
                img.className = 'w-full aspect-[4/5] object-cover';
                img.loading = 'lazy';
                if (alvo) alvo.replaceWith(img);
                // Avisa se a referência (templates) não foi usada e por quê.
                if (data.metodo !== 'referencia' && data.aviso_ref && typeof Toast !== 'undefined') {
                    Toast.erro('Imagem gerada SEM os templates de referência. Motivo: ' + data.aviso_ref);
                }
            } else if (container) {
                const ph = container.querySelector('#slide-img-' + idx);
                if (ph) { ph.className = 'w-full h-32 bg-gray-100 flex items-center justify-center text-3xl'; ph.textContent = '🖼️'; }
            }
        } catch (e) { /* segue para a próxima imagem */ }
        feitas++;
    }
    if (status) status.textContent = '✅ Concluído';
    if (typeof Toast !== 'undefined') Toast.sucesso('Imagens geradas!');
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
