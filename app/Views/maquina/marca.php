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
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de conteúdo</label>
                        <select name="tipo" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                            <option value="carrossel">Carrossel (7 slides)</option>
                            <option value="post">Post único</option>
                            <option value="story">Story</option>
                            <option value="reels">Reels (texto)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tema/Assunto *</label>
                        <input type="text" name="tema" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary" placeholder="Ex: Como proteger sua empresa contra ransomware">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Objetivo</label>
                        <select name="objetivo" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                            <option value="educar">Educar</option><option value="engajar">Engajar</option><option value="converter">Converter</option><option value="inspirar">Inspirar</option><option value="informar">Informar</option>
                        </select>
                    </div>
                    <div x-data="{ usarNoticia: false }">
                        <label class="flex items-center gap-2 text-sm cursor-pointer"><input type="checkbox" x-model="usarNoticia" class="w-4 h-4 text-primary rounded"> Usar notícia da Central de Conteúdo</label>
                        <div x-show="usarNoticia" class="mt-2">
                            <select name="noticia_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
                                <option value="">Selecione...</option>
                                <?php foreach ($dados['noticias'] as $n): ?>
                                <option value="<?= $n['id'] ?>"><?= htmlspecialchars($n['titulo']) ?></option>
                                <?php endforeach; ?>
                            </select>
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
                <input type="file" accept="image/*" class="hidden" onchange="adicionarTemplate(this)">
            </label>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4" id="grid-templates">
            <label class="border-2 border-dashed border-gray-300 rounded-lg p-8 flex items-center justify-center text-gray-400 text-sm hover:border-primary hover:text-primary cursor-pointer transition">
                <span>+ Upload</span>
                <input type="file" accept="image/*" class="hidden" onchange="adicionarTemplate(this)">
            </label>
        </div>
        <p class="text-xs text-gray-400 mt-4">Envie imagens de posts que representam o estilo visual desejado. A IA usará como referência.</p>
    </div>

<script>
function adicionarTemplate(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    const reader = new FileReader();
    reader.onload = function(e) {
        const grid = document.getElementById('grid-templates');
        const card = document.createElement('div');
        card.className = 'relative rounded-lg overflow-hidden border border-gray-200 group';
        card.innerHTML = `
            <img src="${e.target.result}" class="w-full h-32 object-cover">
            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                <button onclick="this.closest('.relative').remove()" class="text-white text-xs bg-red-600 px-2 py-1 rounded">Remover</button>
            </div>
            <p class="text-xs text-gray-600 p-2 truncate">${file.name}</p>
        `;
        grid.insertBefore(card, grid.lastElementChild);
    };
    reader.readAsDataURL(file);
    input.value = '';
}
</script>

    <!-- ABA PUBLICAÇÃO -->
    <div x-show="aba === 'publicacao'" x-transition style="display:none;">
        <!-- Calendário simplificado -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h3 class="font-semibold text-gray-800 mb-4">📅 Calendário Editorial — Junho 2026</h3>
            <div class="grid grid-cols-7 gap-1 text-center text-xs">
                <div class="font-medium text-gray-500 py-2">Seg</div><div class="font-medium text-gray-500 py-2">Ter</div><div class="font-medium text-gray-500 py-2">Qua</div><div class="font-medium text-gray-500 py-2">Qui</div><div class="font-medium text-gray-500 py-2">Sex</div><div class="font-medium text-gray-500 py-2">Sáb</div><div class="font-medium text-gray-500 py-2">Dom</div>
                <?php for ($d = 1; $d <= 30; $d++):
                    $cor = match(true) {
                        $d === 22 => 'bg-green-200 text-green-800',
                        $d === 25 => 'bg-blue-200 text-blue-800',
                        $d === 26 => 'bg-orange-200 text-orange-800',
                        $d === 24 => 'bg-gray-200 text-gray-600',
                        default => 'hover:bg-gray-100',
                    };
                ?>
                <div class="py-2 rounded <?= $cor ?> cursor-pointer"><?= $d ?></div>
                <?php endfor; ?>
            </div>
            <div class="flex gap-4 mt-4 text-xs text-gray-500">
                <span class="flex items-center gap-1"><span class="w-3 h-3 bg-gray-200 rounded"></span> Rascunho</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 bg-blue-200 rounded"></span> Aprovado</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 bg-orange-200 rounded"></span> Agendado</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 bg-green-200 rounded"></span> Publicado</span>
            </div>
        </div>

        <!-- Lista de conteúdos aprovados -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800">Conteúdos prontos para publicação</h3>
            </div>
            <div class="p-4 space-y-3">
                <?php foreach (array_filter($dados['conteudos'], fn($c) => in_array($c['status'], ['aprovado', 'agendado'])) as $cont): ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-gray-800"><?= htmlspecialchars($cont['titulo']) ?></p>
                        <p class="text-xs text-gray-400"><?= ucfirst($cont['tipo']) ?> • <?= date('d/m/Y', strtotime($cont['data'])) ?></p>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="let d=prompt('Data para agendar (AAAA-MM-DD):'); if(d) alert('Agendado para '+d+'! (Em produção: salva no banco)')" class="px-3 py-1.5 border border-gray-300 rounded text-xs hover:bg-gray-100">📅 Agendar</button>
                        <button onclick="publicarAgora()" class="px-3 py-1.5 bg-green-600 text-white rounded text-xs hover:bg-green-700">Publicar</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal publicar -->
<div id="modal-publicar" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 text-center">
        <span class="text-4xl mb-4 inline-block">📢</span>
        <h3 class="text-lg font-semibold text-gray-800 mb-2">Publicação Automática em Breve</h3>
        <p class="text-sm text-gray-500 mb-4">A funcionalidade de publicação automática nas redes sociais chegará em breve. Por enquanto, faça o download do conteúdo e publique manualmente.</p>
        <button onclick="alert('Download será disponibilizado em breve. Por enquanto, clique com botão direito nas imagens e salve manualmente.')" class="w-full bg-primary text-white py-2.5 rounded-lg text-sm font-medium hover:bg-primary-700 mb-2">📥 Download do Conteúdo (ZIP)</button>
        <button onclick="document.getElementById('modal-publicar').classList.add('hidden')" class="w-full border border-gray-300 py-2.5 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Fechar</button>
    </div>
</div>

<script>
document.getElementById('sel-estilo-img').addEventListener('change', function() {
    document.getElementById('prompt-dalle-preview').classList.toggle('hidden', this.value !== 'ia');
});

document.getElementById('form-gerar').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-gerar');
    const preview = document.getElementById('resultado-preview');
    btn.disabled = true; btn.textContent = '⏳ Gerando texto... → imagem...';
    preview.innerHTML = '<div class="text-center"><div class="inline-block w-8 h-8 border-4 border-gray-200 border-t-accent rounded-full animate-spin"></div><p class="text-xs text-gray-500 mt-2">Gerando conteúdo e imagens...</p></div>';
    try {
        const fd = new FormData(this);
        const res = await fetch('<?= APP_URL ?>/maquina/gerar', { method:'POST', body:fd });
        const data = await res.json();
        if (data.sucesso) {
            let html = '<div class="space-y-3">';
            data.conteudo.slides.forEach(s => {
                html += `<div class="border rounded-lg overflow-hidden"><img src="${s.imagem_url}" class="w-full h-32 object-cover"><div class="p-3"><p class="text-xs text-gray-600">${s.texto}</p></div></div>`;
            });
            html += `<div class="p-3 bg-gray-50 rounded-lg"><p class="text-xs text-gray-500 font-medium mb-1">Legenda:</p><p class="text-xs text-gray-700 whitespace-pre-line">${data.conteudo.legenda}</p></div>`;
            html += '<div class="flex gap-2 mt-3"><a href="<?= APP_URL ?>/maquina-de-conteudo/editar" class="flex-1 text-center px-3 py-2 bg-primary text-white rounded text-xs font-medium">✏️ Editar</a><button class="flex-1 px-3 py-2 border border-gray-300 rounded text-xs">💾 Salvo</button></div>';
            html += '</div>';
            preview.innerHTML = html;
        } else { preview.innerHTML = '<p class="text-red-500 text-sm">' + (data.erro || 'Erro.') + '</p>'; }
    } catch(e) { preview.innerHTML = '<p class="text-red-500 text-sm">Erro de conexão.</p>'; }
    btn.disabled = false; btn.textContent = '⚡ Gerar Conteúdo';
});

function publicarAgora() { document.getElementById('modal-publicar').classList.remove('hidden'); }
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
