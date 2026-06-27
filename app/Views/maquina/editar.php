<?php $tituloPagina = 'Editar Conteúdo'; ?>
<?php ob_start(); ?>
<?php $cont = $dados['conteudo']; ?>

<nav class="mb-6">
    <ol class="flex items-center text-sm text-gray-500 gap-2">
        <li><a href="<?= APP_URL ?>/dashboard" class="hover:text-primary">Dashboard</a></li>
        <li>/</li>
        <li><a href="<?= APP_URL ?>/maquina-de-conteudo" class="hover:text-primary">Máquina de Conteúdo</a></li>
        <li>/</li>
        <li class="font-medium text-primary">Editar Conteúdo</li>
    </ol>
</nav>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-gray-800">✏️ Editar: <?= htmlspecialchars($cont['tema']) ?></h1>
    <div class="flex gap-2">
        <span class="px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600"><?= ucfirst($cont['tipo']) ?></span>
        <span class="px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">Rascunho</span>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Painel lateral: thumbnails dos slides -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 sticky top-20">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">Slides</h4>
            <div class="space-y-2" x-data="{ slideAtivo: 0 }">
                <?php foreach ($cont['slides'] as $i => $slide): ?>
                <div class="border rounded-lg overflow-hidden cursor-pointer hover:ring-2 hover:ring-primary transition p-2 <?= $i === 0 ? 'ring-2 ring-primary' : '' ?>">
                    <div class="flex items-center gap-2">
                        <img src="<?= htmlspecialchars($slide['imagem_url']) ?>" class="w-12 h-12 rounded object-cover">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-gray-700 truncate"><?= $slide['tipo'] === 'capa' ? '📌 Capa' : ($slide['tipo'] === 'cta' ? '🚀 CTA' : 'Slide ' . $slide['numero']) ?></p>
                            <p class="text-[10px] text-gray-400 truncate"><?= htmlspecialchars(substr($slide['texto'], 0, 40)) ?>...</p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Painel principal: edição -->
    <div class="lg:col-span-2 space-y-4">
        <?php foreach ($cont['slides'] as $i => $slide): ?>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5" data-slide-index="<?= $i ?>">
            <div class="flex items-center justify-between mb-3">
                <h4 class="text-sm font-semibold text-gray-700"><?= $slide['tipo'] === 'capa' ? '📌 Slide de Capa' : ($slide['tipo'] === 'cta' ? '🚀 Slide CTA' : '📝 Slide ' . $slide['numero']) ?></h4>
                <span class="text-xs text-gray-400"><?= $slide['numero'] ?>/<?= count($cont['slides']) ?></span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Imagem -->
                <div>
                    <img src="<?= htmlspecialchars($slide['imagem_url']) ?>" class="w-full aspect-square rounded-lg object-cover border border-gray-200">
                    <div class="flex gap-2 mt-2">
                        <label class="flex-1 px-2 py-1.5 border border-gray-300 rounded text-xs hover:bg-gray-50 cursor-pointer text-center">
                            📤 Substituir
                            <input type="file" accept="image/*" class="hidden" onchange="uploadImagem(this, <?= $conteudo['id'] ?>, <?= $i ?>)">
                        </label>
                        <button onclick="regenerarImagem(<?= $conteudo['id'] ?>, <?= $i ?>)" class="flex-1 px-2 py-1.5 border border-gray-300 rounded text-xs hover:bg-gray-50">🔄 Regenerar</button>
                    </div>
                </div>
                <!-- Texto -->
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Texto do slide</label>
                    <textarea 
                        rows="6" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none"
                        onchange="atualizarTextoSlide(<?= $conteudo['id'] ?>, <?= $i ?>, this.value)"
                        placeholder="Digite o texto do slide..."
                    ><?= htmlspecialchars($slide['texto']) ?></textarea>
                    <?php if ($slide['tipo'] === 'cta'): ?>
                    <input 
                        type="text" 
                        placeholder="Texto do botão (opcional)"
                        class="w-full mt-2 px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary"
                        onchange="atualizarTextoSlide(<?= $conteudo['id'] ?>, <?= $i ?>, document.querySelector('textarea').value, this.value)"
                        value="<?= htmlspecialchars($slide['cta'] ?? '') ?>"
                    >
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Legenda e Hashtags -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">Legenda</h4>
            <textarea rows="5" class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary resize-none"><?= htmlspecialchars($cont['legenda']) ?></textarea>
            <div class="flex items-center justify-between mt-2">
                <p class="text-xs text-gray-400">Caracteres: <span id="char-count"><?= strlen($cont['legenda']) ?></span>/2200</p>
            </div>
            <div class="mt-3">
                <label class="block text-xs text-gray-500 mb-1">Hashtags</label>
                <input type="text" value="<?= htmlspecialchars($cont['hashtags']) ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-primary">
            </div>
        </div>

        <!-- Ações -->
        <div class="flex gap-3">
            <button onclick="aprovarConteudo(<?= $conteudo['id'] ?>)" class="flex-1 bg-green-600 text-white py-3 rounded-lg text-sm font-semibold hover:bg-green-700 transition">✓ Aprovar Conteúdo</button>
            <button onclick="salvarRascunho()" class="px-6 py-3 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">💾 Salvar</button>
            <button onclick="descartarConteudo()" class="px-6 py-3 border border-red-200 rounded-lg text-sm text-red-600 hover:bg-red-50">🗑️ Descartar</button>
        </div>
    </div>
</div>

<script>
async function aprovarConteudo(conteudoId) {
    if (!conteudoId) {
        alert('ID do conteúdo inválido.');
        return;
    }
    
    const fd = new FormData();
    fd.append('csrf_token', '<?= Csrf::token() ?>');
    fd.append('conteudo_id', conteudoId);
    
    try {
        const res = await fetch('<?= APP_URL ?>/maquina-de-conteudo/aprovar', { method:'POST', body:fd });
        const data = await res.json();
        
        if (data.sucesso) {
            if (typeof Toast !== 'undefined') {
                Toast.sucesso(data.mensagem);
            } else {
                alert(data.mensagem);
            }
            // Redirecionar para biblioteca da marca
            setTimeout(() => {
                window.location.href = '<?= APP_URL ?>/maquina-de-conteudo/marca?id=1&aba=biblioteca';
            }, 1000);
        } else {
            alert(data.erro || 'Erro ao aprovar.');
        }
    } catch(e) {
        console.error('Erro:', e);
        alert('Erro de conexão.');
    }
}

async function uploadImagem(input, conteudoId, slideIndex) {
    if (!input.files || !input.files[0]) return;
    
    const fd = new FormData();
    fd.append('csrf_token', '<?= Csrf::token() ?>');
    fd.append('conteudo_id', conteudoId);
    fd.append('slide_index', slideIndex);
    fd.append('imagem', input.files[0]);
    
    try {
        const res = await fetch('<?= APP_URL ?>/maquina-de-conteudo/upload-imagem', { method: 'POST', body: fd });
        const data = await res.json();
        
        if (data.sucesso) {
            // Atualizar imagem na tela
            const img = input.closest('.grid').querySelector('img');
            if (img) {
                img.src = data.imagem_url;
            }
            if (typeof Toast !== 'undefined') {
                Toast.sucesso(data.mensagem);
            }
        } else {
            alert(data.erro || 'Erro no upload.');
        }
    } catch(e) {
        alert('Erro de conexão.');
    }
    
    input.value = ''; // Limpar input
}

async function regenerarImagem(conteudoId, slideIndex) {
    const prompt = prompt('Digite o prompt para a nova imagem:', '');
    if (!prompt) return;
    
    const fd = new FormData();
    fd.append('csrf_token', '<?= Csrf::token() ?>');
    fd.append('conteudo_id', conteudoId);
    fd.append('slide_index', slideIndex);
    fd.append('prompt_editado', prompt);
    
    try {
        const res = await fetch('<?= APP_URL ?>/maquina-de-conteudo/regenerar-imagem', { method: 'POST', body: fd });
        const data = await res.json();
        
        if (data.sucesso) {
            // Atualizar imagem na tela
            const slideContainer = document.querySelector(`[data-slide-index="${slideIndex}"]`);
            const img = slideContainer ? slideContainer.querySelector('img') : null;
            if (img) {
                img.src = data.imagem_url;
            }
            if (typeof Toast !== 'undefined') {
                Toast.sucesso(data.mensagem);
            }
        } else {
            alert(data.erro || 'Erro na regeneração.');
        }
    } catch(e) {
        alert('Erro de conexão.');
    }
}

async function atualizarTextoSlide(conteudoId, slideIndex, texto, cta = '') {
    const fd = new FormData();
    fd.append('csrf_token', '<?= Csrf::token() ?>');
    fd.append('conteudo_id', conteudoId);
    fd.append('slide_index', slideIndex);
    fd.append('texto', texto);
    fd.append('cta', cta);
    
    try {
        const res = await fetch('<?= APP_URL ?>/maquina-de-conteudo/atualizar-slide', { method: 'POST', body: fd });
        const data = await res.json();
        
        if (data.sucesso) {
            // Feedback sutil de salvamento
            if (typeof Toast !== 'undefined') {
                Toast.sucesso('Texto salvo automaticamente');
            }
        }
    } catch(e) {
        console.error('Erro ao salvar texto:', e);
    }
}

function salvarRascunho() {
    if (typeof Toast !== 'undefined') {
        Toast.sucesso('Rascunho salvo automaticamente!');
    } else {
        alert('Rascunho salvo!');
    }
}

function descartarConteudo() {
    if (confirm('Descartar este conteúdo? Esta ação não pode ser desfeita.')) {
        window.location.href = '<?= APP_URL ?>/maquina-de-conteudo/marca?id=1';
    }
}

// Contador de caracteres para legenda
const legendaTextarea = document.querySelector('textarea[rows="5"]');
if (legendaTextarea) {
    legendaTextarea.addEventListener('input', function() {
        const charCount = document.getElementById('char-count');
        if (charCount) {
            charCount.textContent = this.value.length;
        }
    });
}
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
