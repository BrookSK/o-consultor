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
    <div class="flex items-center gap-2">
        <button onclick="exportarPdf(this)" id="btn-exportar-pdf" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-700 flex items-center gap-2">📄 Exportar PDF</button>
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
                    <img src="<?= htmlspecialchars($slide['imagem_url']) ?>" class="w-full max-h-[520px] rounded-lg object-contain bg-gray-900 border border-gray-200">
                    <div class="flex gap-2 mt-2">
                        <label class="flex-1 px-2 py-1.5 border border-gray-300 rounded text-xs hover:bg-gray-50 cursor-pointer text-center">
                            📤 Substituir
                            <input type="file" accept="image/*" class="hidden" onchange="uploadImagem(this, <?= $conteudo['id'] ?>, <?= $i ?>)">
                        </label>
                        <button onclick="regenerarImagem(<?= $conteudo['id'] ?>, <?= $i ?>)" class="flex-1 px-2 py-1.5 border border-gray-300 rounded text-xs hover:bg-gray-50">🔄 Regenerar</button>
                    </div>
                    <div class="flex gap-2 mt-2">
                        <button onclick="abrirEditorLogo(<?= $conteudo['id'] ?>, <?= $i ?>, '<?= htmlspecialchars($slide['imagem_url'], ENT_QUOTES) ?>')" class="flex-1 px-2 py-1.5 border border-primary/40 text-primary rounded text-xs hover:bg-primary/5">🎨 Posicionar logo</button>
                        <a href="<?= htmlspecialchars($slide['imagem_url']) ?>" download class="flex-1 px-2 py-1.5 border border-gray-300 rounded text-xs hover:bg-gray-50 text-center">⬇️ Baixar</a>
                    </div>
                    <a href="<?= APP_URL ?>/maquina-de-conteudo/imagem/prompt/<?= (int) $conteudo['id'] ?>/<?= $i ?>" target="_blank" class="block mt-2 text-center text-[11px] text-blue-600 hover:underline">🔍 Ver prompts (imagem, legenda e fonte usada)</a>
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
    const instrucao = window.prompt('O que ajustar nesta imagem? (ex.: corrigir a grafia da headline, fundo mais claro)', '');
    if (instrucao === null) return;

    const slideContainer = document.querySelector(`[data-slide-index="${slideIndex}"]`);
    const img = slideContainer ? slideContainer.querySelector('img') : null;
    if (img) img.style.opacity = '0.4';

    try {
        // Enfileira a regeneração (roda em background, sem timeout do proxy).
        const fd = new FormData();
        fd.append('csrf_token', '<?= Csrf::token() ?>');
        fd.append('conteudo_id', conteudoId);
        fd.append('slide_index', slideIndex);
        fd.append('instrucao', instrucao);
        const res = await fetch('<?= APP_URL ?>/maquina-de-conteudo/gerar-imagem-slide', { method: 'POST', body: fd });
        const data = await res.json();
        if (!data.sucesso) {
            if (img) img.style.opacity = '1';
            alert(data.erro || 'Erro ao enfileirar.');
            return;
        }
        if (typeof Toast !== 'undefined') Toast.sucesso('Regeneração iniciada...');

        // Polling do status desse slide.
        const esperar = (ms) => new Promise(r => setTimeout(r, ms));
        for (let t = 0; t < 150; t++) {
            fetch('<?= APP_URL ?>/maquina-de-conteudo/processar-imagens-bg?_=' + Date.now()).catch(() => {});
            await esperar(2500);
            let st;
            try {
                const r = await fetch('<?= APP_URL ?>/maquina-de-conteudo/status-imagens?conteudo_id=' + conteudoId + '&_=' + Date.now());
                st = await r.json();
            } catch (e) { continue; }
            const item = (st.itens || []).find(i => i.slide_index === slideIndex);
            if (item && item.mensagem) console.log('[IMG slide ' + slideIndex + '] status=' + item.status + '\n' + item.mensagem);
            if (item && item.status === 'concluido' && item.imagem_url) {
                if (img) { img.src = item.imagem_url + '?t=' + Date.now(); img.style.opacity = '1'; }
                if (typeof Toast !== 'undefined') Toast.sucesso('Imagem regenerada!');
                return;
            }
            if (item && item.status === 'erro') {
                if (img) img.style.opacity = '1';
                alert('Falha ao regenerar.');
                return;
            }
        }
        if (img) img.style.opacity = '1';
    } catch(e) {
        if (img) img.style.opacity = '1';
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

async function salvarRascunho() {
    const conteudoId = <?= (int) ($conteudo['id'] ?? 0) ?>;
    if (!conteudoId) {
        if (typeof Toast !== 'undefined') Toast.sucesso('Rascunho salvo!'); else alert('Rascunho salvo!');
        return;
    }
    try {
        const fd = new FormData();
        fd.append('csrf_token', '<?= Csrf::token() ?>');
        fd.append('conteudo_id', conteudoId);
        const res = await fetch('<?= APP_URL ?>/maquina-de-conteudo/salvar-biblioteca', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.sucesso) {
            if (typeof Toast !== 'undefined') Toast.sucesso('Salvo na biblioteca!'); else alert('Salvo na biblioteca!');
        } else {
            alert(data.erro || 'Erro ao salvar.');
        }
    } catch (e) {
        alert('Erro de conexão ao salvar.');
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

// ===================== EXPORTAR CARROSSEL EM PDF =====================
// URLs das imagens dos slides (na ordem), vindas do PHP.
const SLIDES_IMAGENS = <?= json_encode(array_values(array_map(fn($s) => (string) ($s['imagem_url'] ?? ''), $cont['slides'] ?? [])), JSON_UNESCAPED_SLASHES) ?>;
const PDF_NOME = <?= json_encode('carrossel-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower((string) ($cont['tema'] ?? 'conteudo')))) ?>;

// Carrega uma imagem como dataURL (mesma origem -> sem problema de CORS).
function carregarImagemDataUrl(url) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = () => {
            const canvas = document.createElement('canvas');
            canvas.width = img.naturalWidth;
            canvas.height = img.naturalHeight;
            canvas.getContext('2d').drawImage(img, 0, 0);
            try {
                resolve({ dataUrl: canvas.toDataURL('image/jpeg', 0.92), w: img.naturalWidth, h: img.naturalHeight });
            } catch (e) { reject(e); }
        };
        img.onerror = () => reject(new Error('Falha ao carregar imagem'));
        img.src = url + (url.includes('?') ? '&' : '?') + 'pdf=1';
    });
}

async function garantirJsPDF() {
    if (window.jspdf && window.jspdf.jsPDF) return window.jspdf.jsPDF;
    await new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
        s.onload = resolve;
        s.onerror = () => reject(new Error('Falha ao carregar jsPDF'));
        document.head.appendChild(s);
    });
    return window.jspdf.jsPDF;
}

async function exportarPdf(btn) {
    const imagens = (SLIDES_IMAGENS || []).filter(u => u && u.trim() !== '');
    if (imagens.length === 0) { alert('Nenhuma imagem gerada ainda para exportar.'); return; }
    if (btn) { btn.disabled = true; btn.textContent = '⏳ Gerando PDF...'; }
    try {
        const jsPDF = await garantirJsPDF();
        let pdf = null;
        for (let i = 0; i < imagens.length; i++) {
            let img;
            try { img = await carregarImagemDataUrl(imagens[i]); }
            catch (e) { continue; } // pula imagem que falhar
            // Cada página tem o tamanho exato da imagem (mantém proporção do slide).
            const orient = img.w >= img.h ? 'landscape' : 'portrait';
            if (pdf === null) {
                pdf = new jsPDF({ orientation: orient, unit: 'px', format: [img.w, img.h] });
            } else {
                pdf.addPage([img.w, img.h], orient);
            }
            pdf.addImage(img.dataUrl, 'JPEG', 0, 0, img.w, img.h);
        }
        if (pdf === null) { alert('Não foi possível carregar as imagens para o PDF.'); }
        else { pdf.save(PDF_NOME + '.pdf'); }
    } catch (e) {
        alert('Erro ao gerar o PDF: ' + (e.message || e));
    }
    if (btn) { btn.disabled = false; btn.textContent = '📄 Exportar PDF'; }
}

// ===================== EDITOR DE LOGO SOBRE A IMAGEM =====================
const LOGO_MARCA_URL = '<?= !empty($conteudo['logo_url']) ? htmlspecialchars(APP_URL . $conteudo['logo_url'], ENT_QUOTES) : '' ?>';
let editorLogo = {
    conteudoId: 0, slideIndex: 0,
    baseImg: null, logoImg: null,
    // posição/tamanho do logo em coordenadas do canvas
    x: 40, y: 40, w: 160, h: 160,
    arrastando: false, redimensionando: false,
    offsetX: 0, offsetY: 0,
};

function abrirEditorLogo(conteudoId, slideIndex, imagemUrl) {
    editorLogo.conteudoId = conteudoId;
    editorLogo.slideIndex = slideIndex;
    document.getElementById('editor-logo-modal').classList.remove('hidden');
    const base = new Image();
    base.crossOrigin = 'anonymous';
    base.onload = () => {
        editorLogo.baseImg = base;
        const canvas = document.getElementById('editor-logo-canvas');
        // Mantém a resolução real da imagem no canvas.
        canvas.width = base.naturalWidth;
        canvas.height = base.naturalHeight;
        // posição inicial do logo: canto inferior direito
        const lw = Math.round(base.naturalWidth * 0.18);
        editorLogo.w = lw; editorLogo.h = lw;
        editorLogo.x = base.naturalWidth - lw - Math.round(base.naturalWidth * 0.05);
        editorLogo.y = base.naturalHeight - lw - Math.round(base.naturalHeight * 0.05);
        desenharEditorLogo();
    };
    base.onerror = () => alert('Não foi possível carregar a imagem (CORS). Use uma imagem gerada pelo sistema.');
    base.src = imagemUrl + (imagemUrl.includes('?') ? '&' : '?') + 'cors=1';

    if (LOGO_MARCA_URL) {
        const lg = new Image();
        lg.crossOrigin = 'anonymous';
        lg.onload = () => { editorLogo.logoImg = lg; desenharEditorLogo(); };
        lg.src = LOGO_MARCA_URL + (LOGO_MARCA_URL.includes('?') ? '&' : '?') + 'cors=1';
    }
}

function fecharEditorLogo() {
    document.getElementById('editor-logo-modal').classList.add('hidden');
}

function desenharEditorLogo() {
    const canvas = document.getElementById('editor-logo-canvas');
    if (!canvas || !editorLogo.baseImg) return;
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.drawImage(editorLogo.baseImg, 0, 0, canvas.width, canvas.height);
    if (editorLogo.logoImg) {
        ctx.drawImage(editorLogo.logoImg, editorLogo.x, editorLogo.y, editorLogo.w, editorLogo.h);
        // Moldura + handle de redimensionamento (canto inferior direito).
        ctx.strokeStyle = 'rgba(59,130,246,0.9)';
        ctx.lineWidth = Math.max(2, canvas.width / 400);
        ctx.strokeRect(editorLogo.x, editorLogo.y, editorLogo.w, editorLogo.h);
        const hs = Math.max(12, canvas.width / 60);
        ctx.fillStyle = 'rgba(59,130,246,0.95)';
        ctx.fillRect(editorLogo.x + editorLogo.w - hs, editorLogo.y + editorLogo.h - hs, hs, hs);
    }
}

// Converte coordenadas do mouse (tela) para coordenadas do canvas (resolução real).
function coordCanvas(canvas, ev) {
    const r = canvas.getBoundingClientRect();
    const px = (ev.touches ? ev.touches[0].clientX : ev.clientX) - r.left;
    const py = (ev.touches ? ev.touches[0].clientY : ev.clientY) - r.top;
    return { x: px * (canvas.width / r.width), y: py * (canvas.height / r.height) };
}

function editorLogoMouseDown(ev) {
    if (!editorLogo.logoImg) return;
    ev.preventDefault();
    const canvas = document.getElementById('editor-logo-canvas');
    const p = coordCanvas(canvas, ev);
    const hs = Math.max(12, canvas.width / 60);
    // Está no handle de resize?
    if (p.x >= editorLogo.x + editorLogo.w - hs && p.x <= editorLogo.x + editorLogo.w &&
        p.y >= editorLogo.y + editorLogo.h - hs && p.y <= editorLogo.y + editorLogo.h) {
        editorLogo.redimensionando = true;
        return;
    }
    // Está sobre o logo? (arrastar)
    if (p.x >= editorLogo.x && p.x <= editorLogo.x + editorLogo.w &&
        p.y >= editorLogo.y && p.y <= editorLogo.y + editorLogo.h) {
        editorLogo.arrastando = true;
        editorLogo.offsetX = p.x - editorLogo.x;
        editorLogo.offsetY = p.y - editorLogo.y;
    }
}

function editorLogoMouseMove(ev) {
    if (!editorLogo.arrastando && !editorLogo.redimensionando) return;
    ev.preventDefault();
    const canvas = document.getElementById('editor-logo-canvas');
    const p = coordCanvas(canvas, ev);
    if (editorLogo.redimensionando) {
        editorLogo.w = Math.max(24, p.x - editorLogo.x);
        editorLogo.h = editorLogo.w; // mantém proporção quadrada do bounding
    } else if (editorLogo.arrastando) {
        editorLogo.x = p.x - editorLogo.offsetX;
        editorLogo.y = p.y - editorLogo.offsetY;
    }
    desenharEditorLogo();
}

function editorLogoMouseUp() { editorLogo.arrastando = false; editorLogo.redimensionando = false; }

// Salva a imagem combinada (base + logo posicionado) no servidor.
async function salvarImagemComLogo() {
    const canvas = document.getElementById('editor-logo-canvas');
    if (!canvas || !editorLogo.baseImg) return;
    const btn = document.getElementById('btn-salvar-logo');
    if (btn) { btn.disabled = true; btn.textContent = 'Salvando...'; }
    // Redesenha SEM a moldura/handle para exportar limpo.
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.drawImage(editorLogo.baseImg, 0, 0, canvas.width, canvas.height);
    if (editorLogo.logoImg) ctx.drawImage(editorLogo.logoImg, editorLogo.x, editorLogo.y, editorLogo.w, editorLogo.h);
    // Converte o canvas em BLOB e envia como ARQUIVO (multipart) — evita o
    // limite de "no files data" do ModSecurity (413) que ocorre ao enviar o
    // base64 como campo de texto.
    let blob;
    try {
        blob = await new Promise((resolve, reject) => {
            canvas.toBlob(b => b ? resolve(b) : reject(new Error('toBlob falhou')), 'image/png');
        });
    } catch (e) {
        alert('Falha ao exportar (a imagem pode bloquear por CORS).');
        if (btn) { btn.disabled = false; btn.textContent = '💾 Salvar imagem com logo'; }
        return;
    }

    try {
        const fd = new FormData();
        fd.append('csrf_token', '<?= Csrf::token() ?>');
        fd.append('conteudo_id', editorLogo.conteudoId);
        fd.append('slide_index', editorLogo.slideIndex);
        fd.append('imagem', blob, 'imagem-editada.png');
        const res = await fetch('<?= APP_URL ?>/maquina-de-conteudo/salvar-imagem-editada', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.sucesso && data.imagem_url) {
            // Atualiza a imagem do slide na página.
            const card = document.querySelector(`[data-slide-index="${editorLogo.slideIndex}"] img`);
            if (card) card.src = data.imagem_url + '?t=' + Date.now();
            if (typeof Toast !== 'undefined') Toast.sucesso('Imagem com logo salva!');
            fecharEditorLogo();
        } else {
            alert(data.erro || 'Erro ao salvar.');
        }
    } catch (e) { alert('Erro de conexão.'); }
    if (btn) { btn.disabled = false; btn.textContent = '💾 Salvar imagem com logo'; }
}
</script>

<!-- Modal: Editor de logo sobre a imagem -->
<div id="editor-logo-modal" class="hidden fixed inset-0 bg-black/70 z-[60] flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full max-h-[92vh] overflow-auto p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-gray-800">🎨 Posicionar logo na imagem</h3>
            <button onclick="fecharEditorLogo()" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
        </div>
        <?php if (empty($conteudo['logo_url'])): ?>
        <p class="text-sm text-amber-600 bg-amber-50 border border-amber-200 rounded p-3 mb-3">Nenhum logo cadastrado no Brand Book desta marca. Envie o logo no Brand Book para posicioná-lo aqui.</p>
        <?php endif; ?>
        <p class="text-xs text-gray-500 mb-2">Arraste o logo para posicionar e use o quadradinho azul (canto inferior direito do logo) para redimensionar.</p>
        <div class="w-full bg-gray-900 rounded-lg flex items-center justify-center" style="height:60vh;">
            <canvas id="editor-logo-canvas" class="touch-none" style="max-width:100%;max-height:60vh;object-fit:contain;"
                onmousedown="editorLogoMouseDown(event)" onmousemove="editorLogoMouseMove(event)" onmouseup="editorLogoMouseUp()" onmouseleave="editorLogoMouseUp()"
                ontouchstart="editorLogoMouseDown(event)" ontouchmove="editorLogoMouseMove(event)" ontouchend="editorLogoMouseUp()"></canvas>
        </div>
        <div class="flex flex-wrap gap-2 mt-4">
            <button id="btn-salvar-logo" onclick="salvarImagemComLogo()" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-700">💾 Salvar imagem com logo</button>
            <button onclick="baixarCanvasEditor(true)" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">⬇️ Baixar com logo</button>
            <button onclick="baixarCanvasEditor(false)" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">⬇️ Baixar sem logo</button>
            <button onclick="fecharEditorLogo()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Cancelar</button>
        </div>
    </div>
</div>
<script>
// Download da imagem (canvas) direto no navegador — com ou sem o logo.
function baixarCanvasEditor(comLogo) {
    const canvas = document.getElementById('editor-logo-canvas');
    if (!canvas || !editorLogo.baseImg) return;
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.drawImage(editorLogo.baseImg, 0, 0, canvas.width, canvas.height);
    if (comLogo && editorLogo.logoImg) ctx.drawImage(editorLogo.logoImg, editorLogo.x, editorLogo.y, editorLogo.w, editorLogo.h);
    try {
        const a = document.createElement('a');
        a.download = comLogo ? 'imagem-com-logo.png' : 'imagem-original.png';
        a.href = canvas.toDataURL('image/png');
        a.click();
    } catch (e) { alert('Falha ao exportar (CORS).'); }
    desenharEditorLogo();
}
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
