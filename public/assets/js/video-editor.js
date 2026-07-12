/* Mini Editor de Vídeo (Reels) — O Consultor
 * Reutiliza as imagens já geradas do post. Estado vinculado ao post (conteudo_id).
 * Preview em canvas; exportação final via FFmpeg no backend.
 */
(function () {
    'use strict';
    const conf = window.VIDEO_CONF;
    if (!conf) return;

    // Estado do projeto (clonado do servidor).
    let estado = conf.estado || {};
    estado.imagens = estado.imagens || [];
    // Se o estado salvo não tiver imagens, reidrata com as imagens JÁ geradas do
    // post (o usuário não precisa adicionar nada manualmente).
    if ((!estado.imagens || estado.imagens.length === 0) && Array.isArray(conf.imagensPost) && conf.imagensPost.length > 0) {
        estado.imagens = conf.imagensPost.map(function (u) {
            return { url: u, duracao: 3, transicao: 'fade', movimento: 'zoom_in' };
        });
    }
    estado.narracao = estado.narracao || { url: '', volume: 1, texto: '' };
    estado.musica = estado.musica || { url: '', volume: 0.5, loop: true, reduzir_na_narracao: true };
    estado.textos = estado.textos || [];
    estado.formato = estado.formato || { w: 1080, h: 1920, fps: 30 };
    if (typeof estado.transicao_velocidade !== 'number') estado.transicao_velocidade = 0.5;
    if (typeof estado.movimento_auto !== 'boolean') estado.movimento_auto = false;

    let clipeSelecionado = 0;
    const imgCache = {}; // url -> HTMLImageElement

    const MOVIMENTOS = [
        ['estatico', 'Estático'], ['zoom_in', 'Zoom In'], ['zoom_out', 'Zoom Out'],
        ['esquerda_direita', 'Esquerda → Direita'], ['direita_esquerda', 'Direita → Esquerda'],
        ['cima_baixo', 'Cima → Baixo'], ['baixo_cima', 'Baixo → Cima'],
    ];
    const TRANSICOES = [
        ['fade', 'Fade'], ['dissolver', 'Dissolver'], ['slide_esquerda', 'Slide esquerda'],
        ['slide_direita', 'Slide direita'], ['zoom', 'Zoom'], ['blur', 'Blur'], ['nenhuma', 'Sem transição'],
    ];
    const MOV_AUTO_SEQ = ['zoom_in', 'esquerda_direita', 'zoom_out', 'direita_esquerda', 'cima_baixo', 'baixo_cima'];

    // ---------- Helpers ----------
    function esc(s) { return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }
    function carregarImg(url) {
        return new Promise((resolve) => {
            if (imgCache[url]) return resolve(imgCache[url]);
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = () => { imgCache[url] = img; resolve(img); };
            img.onerror = () => resolve(null);
            img.src = url;
        });
    }
    function movimentoEfetivo(idx) {
        if (estado.movimento_auto) return MOV_AUTO_SEQ[idx % MOV_AUTO_SEQ.length];
        return (estado.imagens[idx] && estado.imagens[idx].movimento) || 'estatico';
    }
    function duracaoTotal() {
        return estado.imagens.reduce((s, c) => s + (parseFloat(c.duracao) || 3), 0);
    }

    // ---------- Biblioteca de imagens ----------
    function renderBiblioteca() {
        const wrap = document.getElementById('lista-imagens');
        if (!wrap) return;
        wrap.innerHTML = '';
        estado.imagens.forEach((c, i) => {
            const card = document.createElement('div');
            card.className = 'border rounded-lg p-2 bg-white cursor-move ' + (i === clipeSelecionado ? 'ring-2 ring-primary' : '');
            card.draggable = true;
            card.dataset.idx = i;
            card.innerHTML =
                '<div class="flex gap-2">'
                + '<img src="' + esc(c.url) + '" class="w-14 h-14 object-cover rounded">'
                + '<div class="flex-1 min-w-0">'
                + '<div class="flex items-center justify-between">'
                + '<span class="text-[11px] text-gray-500">Cena ' + (i + 1) + '</span>'
                + '<div class="flex gap-1">'
                + '<button title="Duplicar" data-act="dup" class="text-gray-400 hover:text-primary text-xs">⧉</button>'
                + '<button title="Remover" data-act="del" class="text-gray-400 hover:text-red-500 text-xs">🗑</button>'
                + '</div></div>'
                + '<div class="flex items-center gap-1 mt-1">'
                + '<input type="number" step="0.5" min="0.5" value="' + (parseFloat(c.duracao) || 3) + '" data-act="dur" class="w-14 px-1 py-0.5 border rounded text-[11px]"><span class="text-[10px] text-gray-400">s</span>'
                + '</div>'
                + '<select data-act="mov" class="w-full mt-1 px-1 py-0.5 border rounded text-[11px]">' + MOVIMENTOS.map(m => '<option value="' + m[0] + '"' + (c.movimento === m[0] ? ' selected' : '') + '>' + m[1] + '</option>').join('') + '</select>'
                + '<select data-act="tr" class="w-full mt-1 px-1 py-0.5 border rounded text-[11px]">' + TRANSICOES.map(t => '<option value="' + t[0] + '"' + (c.transicao === t[0] ? ' selected' : '') + '>' + t[1] + '</option>').join('') + '</select>'
                + '</div></div>';

            card.addEventListener('click', () => { clipeSelecionado = i; renderTudo(); });
            card.querySelector('[data-act="del"]').addEventListener('click', (e) => { e.stopPropagation(); estado.imagens.splice(i, 1); if (clipeSelecionado >= estado.imagens.length) clipeSelecionado = Math.max(0, estado.imagens.length - 1); renderTudo(); });
            card.querySelector('[data-act="dup"]').addEventListener('click', (e) => { e.stopPropagation(); estado.imagens.splice(i + 1, 0, Object.assign({}, c)); renderTudo(); });
            card.querySelector('[data-act="dur"]').addEventListener('change', (e) => { c.duracao = Math.max(0.5, parseFloat(e.target.value) || 3); renderTimeline(); renderPreviewFrame(); });
            card.querySelector('[data-act="mov"]').addEventListener('change', (e) => { c.movimento = e.target.value; });
            card.querySelector('[data-act="tr"]').addEventListener('change', (e) => { c.transicao = e.target.value; });

            // Drag & drop para reordenar.
            card.addEventListener('dragstart', (e) => { e.dataTransfer.setData('idx', i); });
            card.addEventListener('dragover', (e) => e.preventDefault());
            card.addEventListener('drop', (e) => {
                e.preventDefault();
                const from = parseInt(e.dataTransfer.getData('idx'), 10);
                const to = i;
                if (isNaN(from) || from === to) return;
                const mv = estado.imagens.splice(from, 1)[0];
                estado.imagens.splice(to, 0, mv);
                clipeSelecionado = to;
                renderTudo();
            });
            wrap.appendChild(card);
        });
    }

    // ---------- Painel de propriedades do clipe ----------
    function renderPropsClipe() {
        const box = document.getElementById('props-clipe');
        if (!box) return;
        const c = estado.imagens[clipeSelecionado];
        if (!c) { box.innerHTML = 'Selecione uma imagem para editar.'; return; }
        box.innerHTML =
            '<p class="font-medium text-gray-700 mb-2">Cena ' + (clipeSelecionado + 1) + '</p>'
            + '<label class="block text-xs text-gray-500 mb-1">Duração (s)</label>'
            + '<input type="number" step="0.5" min="0.5" id="prop-dur" value="' + (parseFloat(c.duracao) || 3) + '" class="w-full px-3 py-2 border rounded text-sm mb-2">'
            + '<button id="prop-dur-todas" class="text-xs px-3 py-1.5 border border-primary/40 text-primary rounded hover:bg-primary/5 mb-3">Aplicar duração para todas</button>'
            + '<label class="block text-xs text-gray-500 mb-1">Movimento</label>'
            + '<select id="prop-mov" class="w-full px-3 py-2 border rounded text-sm mb-2">' + MOVIMENTOS.map(m => '<option value="' + m[0] + '"' + (c.movimento === m[0] ? ' selected' : '') + '>' + m[1] + '</option>').join('') + '</select>'
            + '<label class="block text-xs text-gray-500 mb-1">Transição</label>'
            + '<select id="prop-tr" class="w-full px-3 py-2 border rounded text-sm mb-2">' + TRANSICOES.map(t => '<option value="' + t[0] + '"' + (c.transicao === t[0] ? ' selected' : '') + '>' + t[1] + '</option>').join('') + '</select>'
            + '<button id="prop-tr-todas" class="text-xs px-3 py-1.5 border border-primary/40 text-primary rounded hover:bg-primary/5">Aplicar esta transição em todas</button>';

        box.querySelector('#prop-dur').addEventListener('change', (e) => { c.duracao = Math.max(0.5, parseFloat(e.target.value) || 3); renderBiblioteca(); renderTimeline(); });
        box.querySelector('#prop-dur-todas').addEventListener('click', () => { const d = c.duracao; estado.imagens.forEach(x => x.duracao = d); renderTudo(); });
        box.querySelector('#prop-mov').addEventListener('change', (e) => { c.movimento = e.target.value; renderBiblioteca(); });
        box.querySelector('#prop-tr').addEventListener('change', (e) => { c.transicao = e.target.value; renderBiblioteca(); });
        box.querySelector('#prop-tr-todas').addEventListener('click', () => { const t = c.transicao; estado.imagens.forEach(x => x.transicao = t); renderBiblioteca(); });
    }

    // ---------- Timeline ----------
    function renderTimeline() {
        const tr = document.getElementById('track-imagens');
        if (tr) {
            const total = duracaoTotal() || 1;
            tr.innerHTML = '';
            estado.imagens.forEach((c, i) => {
                const w = Math.max(24, ((parseFloat(c.duracao) || 3) / total) * 100);
                const el = document.createElement('div');
                el.className = 'h-8 rounded overflow-hidden shrink-0 ' + (i === clipeSelecionado ? 'ring-2 ring-primary' : '');
                el.style.width = w + '%';
                el.innerHTML = '<img src="' + esc(c.url) + '" class="w-full h-full object-cover">';
                el.addEventListener('click', () => { clipeSelecionado = i; renderTudo(); });
                tr.appendChild(el);
            });
        }
        const tn = document.getElementById('track-narracao');
        if (tn) tn.textContent = estado.narracao.url ? '🎙️ Narração carregada' : '—';
        const tm = document.getElementById('track-musica');
        if (tm) tm.textContent = estado.musica.url ? '🎵 Música carregada' : '—';
        const tt = document.getElementById('tempo-total');
        if (tt) tt.textContent = duracaoTotal().toFixed(1) + 's';
    }

    // ---------- Preview (canvas) ----------
    const canvas = document.getElementById('preview-canvas');
    const ctx = canvas ? canvas.getContext('2d') : null;
    let tocando = false;
    let tempo = 0; // segundos
    let ultimoTs = 0;

    function clipeNoTempo(t) {
        let acc = 0;
        for (let i = 0; i < estado.imagens.length; i++) {
            const d = parseFloat(estado.imagens[i].duracao) || 3;
            if (t < acc + d) return { idx: i, prog: (t - acc) / d };
            acc += d;
        }
        return { idx: estado.imagens.length - 1, prog: 1 };
    }

    async function renderPreviewFrame() {
        if (!ctx || estado.imagens.length === 0) return;
        const { idx, prog } = clipeNoTempo(tempo);
        const c = estado.imagens[idx];
        const img = await carregarImg(c.url);
        const W = canvas.width, H = canvas.height;
        ctx.fillStyle = '#000'; ctx.fillRect(0, 0, W, H);
        if (img) {
            // Preenche mantendo proporção (cover).
            const ir = img.width / img.height, cr = W / H;
            let dw, dh; if (ir > cr) { dh = H; dw = H * ir; } else { dw = W; dh = W / ir; }
            // Movimento simples (Ken Burns).
            const mov = movimentoEfetivo(idx);
            let scale = 1, ox = 0, oy = 0;
            if (mov === 'zoom_in') scale = 1 + 0.15 * prog;
            else if (mov === 'zoom_out') scale = 1.15 - 0.15 * prog;
            else if (mov === 'esquerda_direita') ox = -(dw - W) * prog;
            else if (mov === 'direita_esquerda') ox = -(dw - W) * (1 - prog);
            else if (mov === 'cima_baixo') oy = -(dh - H) * prog;
            else if (mov === 'baixo_cima') oy = -(dh - H) * (1 - prog);
            const sw = dw * scale, sh = dh * scale;
            const cx = (W - sw) / 2 + ox, cy = (H - sh) / 2 + oy;
            ctx.drawImage(img, cx, cy, sw, sh);
            // Fade de transição no começo do clipe.
            const vel = estado.transicao_velocidade || 0.5;
            const d = parseFloat(c.duracao) || 3;
            if ((c.transicao || 'fade') !== 'nenhuma' && prog * d < vel) {
                ctx.fillStyle = 'rgba(0,0,0,' + (1 - (prog * d) / vel) + ')';
                ctx.fillRect(0, 0, W, H);
            }
        }
        // Textos aplicáveis a esta cena.
        (estado.textos || []).forEach(t => {
            const alvos = (t.imagens || '').split(',').map(x => parseInt(x.trim(), 10) - 1).filter(x => !isNaN(x));
            if (alvos.length > 0 && alvos.indexOf(idx) === -1) return;
            ctx.fillStyle = t.cor || '#fff';
            const fs = ((t.tamanho || 48) * W / 1080);
            ctx.font = 'bold ' + fs + 'px system-ui, Arial';
            ctx.textAlign = 'center';
            const y = t.posicao === 'topo' ? H * 0.12 : (t.posicao === 'centro' ? H * 0.5 : H * 0.88);
            ctx.fillText(t.frase || '', W / 2, y);
        });
        // UI de tempo.
        const ta = document.getElementById('tempo-atual'); if (ta) ta.textContent = tempo.toFixed(1) + 's';
        const bp = document.getElementById('barra-progresso'); if (bp) bp.value = Math.round((tempo / (duracaoTotal() || 1)) * 1000);
    }

    function loop(ts) {
        if (!tocando) return;
        if (!ultimoTs) ultimoTs = ts;
        const dt = (ts - ultimoTs) / 1000; ultimoTs = ts;
        tempo += dt;
        if (tempo >= duracaoTotal()) { tempo = 0; tocando = false; atualizarBtnPlay(); }
        renderPreviewFrame();
        if (tocando) requestAnimationFrame(loop);
    }
    function atualizarBtnPlay() { const b = document.getElementById('btn-play'); if (b) b.textContent = tocando ? '⏸' : '▶'; }

    window.togglePlay = function () { tocando = !tocando; ultimoTs = 0; atualizarBtnPlay(); if (tocando) requestAnimationFrame(loop); };
    window.reiniciarPreview = function () { tempo = 0; renderPreviewFrame(); };
    window.avancarPreview = function () { tempo = Math.min(duracaoTotal(), tempo + 1); renderPreviewFrame(); };
    window.voltarPreview = function () { tempo = Math.max(0, tempo - 1); renderPreviewFrame(); };
    window.buscarTempo = function (v) { tempo = (v / 1000) * (duracaoTotal() || 1); renderPreviewFrame(); };

    // ---------- Configs gerais ----------
    window.toggleMovAuto = function (el) { estado.movimento_auto = el.checked; renderBiblioteca(); };
    window.setTransicaoVel = function (v) { estado.transicao_velocidade = parseFloat(v) || 0.5; };
    window.sincronizarComNarracao = function () {
        const a = document.getElementById('narracao-audio');
        if (!estado.narracao.url || !a || !a.duration || !isFinite(a.duration)) { alert('Carregue uma narração primeiro (e aguarde carregar).'); return; }
        const n = estado.imagens.length; if (n === 0) return;
        const d = Math.max(0.5, a.duration / n);
        estado.imagens.forEach(c => c.duracao = Math.round(d * 10) / 10);
        renderTudo();
    };

    // ---------- Imagens adicionais ----------
    window.adicionarImagem = async function (input) {
        if (!input.files || !input.files[0]) return;
        const fd = new FormData();
        fd.append('csrf_token', conf.csrf);
        fd.append('conteudo_id', conf.conteudoId);
        fd.append('imagem', input.files[0]);
        try {
            const r = await fetch(conf.base + '/maquina-de-conteudo/video/upload-imagem', { method: 'POST', body: fd });
            const d = await r.json();
            if (d.sucesso && d.url) { estado.imagens.push({ url: d.url, duracao: 3, transicao: 'fade', movimento: 'zoom_in' }); renderTudo(); }
            else alert(d.erro || 'Falha no upload.');
        } catch (e) { alert('Erro de conexão.'); }
        input.value = '';
    };

    // ---------- Áudio (narração / música) ----------
    window.uploadAudio = async function (input, tipo) {
        if (!input.files || !input.files[0]) return;
        const arq = input.files[0];
        const infoEl = document.getElementById(tipo === 'musica' ? 'musica-info' : 'narracao-info');
        if (infoEl) infoEl.textContent = 'Enviando ' + arq.name + '...';
        const fd = new FormData();
        fd.append('csrf_token', conf.csrf);
        fd.append('conteudo_id', conf.conteudoId);
        fd.append('tipo', tipo);
        fd.append('audio', arq);
        try {
            const r = await fetch(conf.base + '/maquina-de-conteudo/video/upload-audio', { method: 'POST', body: fd });
            const txt = await r.text();
            let d;
            try { d = JSON.parse(txt); }
            catch (e) {
                // Resposta não-JSON (ex.: 413 do servidor, 403 CSRF, erro PHP).
                const msg = r.status === 413 ? 'Arquivo muito grande para o servidor (limite de upload).'
                    : (r.status === 403 ? 'Sessão expirada. Recarregue a página.' : ('Erro do servidor (HTTP ' + r.status + '): ' + txt.slice(0, 200)));
                if (infoEl) infoEl.textContent = 'Falha no envio.';
                alert(msg);
                input.value = '';
                return;
            }
            if (d.sucesso && d.url) {
                if (tipo === 'narracao') { estado.narracao.url = d.url; aplicarNarracao(); }
                else { estado.musica.url = d.url; aplicarMusica(); }
                renderTimeline();
                salvarProjeto(false);
            } else { if (infoEl) infoEl.textContent = 'Falha no envio.'; alert(d.erro || 'Falha no upload.'); }
        } catch (e) { if (infoEl) infoEl.textContent = 'Falha no envio.'; alert('Erro de conexão: ' + (e.message || e)); }
        input.value = '';
    };
    function aplicarNarracao() {
        const info = document.getElementById('narracao-info');
        const ctr = document.getElementById('narracao-controles');
        const au = document.getElementById('narracao-audio');
        if (estado.narracao.url) { if (info) info.textContent = 'Narração carregada.'; if (ctr) ctr.classList.remove('hidden'); if (au) au.src = estado.narracao.url; }
        else { if (info) info.textContent = 'Sem narração.'; if (ctr) ctr.classList.add('hidden'); }
    }
    function aplicarMusica() {
        const info = document.getElementById('musica-info');
        const ctr = document.getElementById('musica-controles');
        const au = document.getElementById('musica-audio');
        if (estado.musica.url) { if (info) info.textContent = 'Música carregada.'; if (ctr) ctr.classList.remove('hidden'); if (au) au.src = estado.musica.url; }
        else { if (info) info.textContent = 'Sem música.'; if (ctr) ctr.classList.add('hidden'); }
    }
    window.setNarrVol = function (v) { estado.narracao.volume = parseFloat(v); };
    window.setMusVol = function (v) { estado.musica.volume = parseFloat(v); };
    window.setMusLoop = function (v) { estado.musica.loop = v; };
    window.setMusReduz = function (v) { estado.musica.reduzir_na_narracao = v; };
    window.removerNarracao = function () { estado.narracao.url = ''; aplicarNarracao(); renderTimeline(); };
    window.removerMusica = function () { estado.musica.url = ''; aplicarMusica(); renderTimeline(); };

    // ---------- Narração ElevenLabs ----------
    window.abrirModalVoz = function () {
        document.getElementById('voz-texto').value = estado.narracao.texto || '';
        document.getElementById('modal-voz').classList.remove('hidden');
    };
    window.fecharModalVoz = function () { document.getElementById('modal-voz').classList.add('hidden'); };
    window.carregarVozes = async function (btn) {
        if (btn) btn.disabled = true;
        try {
            const r = await fetch(conf.base + '/maquina-de-conteudo/video/vozes');
            const d = await r.json();
            const sel = document.getElementById('voz-select');
            if (d.sucesso && sel) {
                sel.innerHTML = '<option value="">Padrão</option>' + (d.vozes || []).map(v => '<option value="' + v.voice_id + '">' + esc(v.name) + '</option>').join('');
            } else alert(d.erro || 'Não foi possível carregar as vozes.');
        } catch (e) { alert('Erro de conexão.'); }
        if (btn) btn.disabled = false;
    };
    window.gerarVoz = async function (btn) {
        const texto = document.getElementById('voz-texto').value.trim();
        const voz = document.getElementById('voz-select').value;
        if (!texto) { alert('Escreva o texto da narração.'); return; }
        if (btn) { btn.disabled = true; btn.textContent = 'Gerando...'; }
        try {
            const fd = new FormData();
            fd.append('csrf_token', conf.csrf);
            fd.append('conteudo_id', conf.conteudoId);
            fd.append('texto', texto);
            fd.append('voz_id', voz);
            const r = await fetch(conf.base + '/maquina-de-conteudo/video/gerar-narracao', { method: 'POST', body: fd });
            const t = await r.text();
            let d; try { d = JSON.parse(t); } catch (e) { alert('Erro do servidor (HTTP ' + r.status + '): ' + t.slice(0, 200)); if (btn) { btn.disabled = false; btn.textContent = 'Gerar'; } return; }
            if (d.sucesso && d.url) {
                estado.narracao.url = d.url; estado.narracao.texto = texto; estado.narracao.voz_id = voz;
                estado.narracao.versoes = estado.narracao.versoes || []; estado.narracao.versoes.push(d.url);
                aplicarNarracao(); renderTimeline(); fecharModalVoz();
                if (typeof Toast !== 'undefined') Toast.sucesso('Narração gerada!');
            } else alert(d.erro || 'Falha ao gerar voz.');
        } catch (e) { alert('Erro de conexão.'); }
        if (btn) { btn.disabled = false; btn.textContent = 'Gerar'; }
    };

    // ---------- Textos ----------
    window.adicionarTexto = function () {
        const frase = document.getElementById('txt-frase').value.trim();
        if (!frase) return;
        estado.textos.push({
            frase: frase,
            cor: document.getElementById('txt-cor').value,
            tamanho: parseInt(document.getElementById('txt-tamanho').value, 10) || 48,
            posicao: document.getElementById('txt-posicao').value,
            imagens: document.getElementById('txt-imagens').value.trim(),
        });
        document.getElementById('txt-frase').value = '';
        renderTextos(); renderPreviewFrame();
    };
    function renderTextos() {
        const wrap = document.getElementById('lista-textos');
        if (!wrap) return;
        wrap.innerHTML = estado.textos.map((t, i) =>
            '<div class="flex items-center justify-between border rounded px-2 py-1">'
            + '<span class="truncate">' + esc(t.frase) + '</span>'
            + '<button data-i="' + i + '" class="text-red-500">🗑</button></div>'
        ).join('');
        wrap.querySelectorAll('button[data-i]').forEach(b => b.addEventListener('click', () => { estado.textos.splice(parseInt(b.dataset.i, 10), 1); renderTextos(); renderPreviewFrame(); }));
    }

    // ---------- Salvar / Exportar ----------
    window.salvarProjeto = async function (avisar) {
        try {
            const fd = new FormData();
            fd.append('csrf_token', conf.csrf);
            fd.append('conteudo_id', conf.conteudoId);
            fd.append('estado', JSON.stringify(estado));
            const r = await fetch(conf.base + '/maquina-de-conteudo/video/salvar', { method: 'POST', body: fd });
            const d = await r.json();
            if (avisar) { if (d.sucesso) { if (typeof Toast !== 'undefined') Toast.sucesso('Projeto salvo!'); else alert('Projeto salvo!'); } else alert(d.erro || 'Erro ao salvar.'); }
            return d.sucesso;
        } catch (e) { if (avisar) alert('Erro de conexão.'); return false; }
    };

    let pollTimer = null;
    window.exportarVideo = async function (btn) {
        if (estado.imagens.length === 0) { alert('Nenhuma imagem no vídeo.'); return; }
        if (btn) { btn.disabled = true; btn.textContent = '⏳ Enviando...'; }
        try {
            const fd = new FormData();
            fd.append('csrf_token', conf.csrf);
            fd.append('conteudo_id', conf.conteudoId);
            fd.append('estado', JSON.stringify(estado));
            const r = await fetch(conf.base + '/maquina-de-conteudo/video/exportar', { method: 'POST', body: fd });
            const d = await r.json();
            if (!d.sucesso) { alert(d.erro || 'Falha ao enfileirar.'); if (btn) { btn.disabled = false; btn.textContent = '⬇️ Exportar Vídeo'; } return; }
            document.getElementById('box-exportacao').classList.remove('hidden');
            iniciarPolling();
        } catch (e) { alert('Erro de conexão.'); }
        if (btn) { btn.disabled = false; btn.textContent = '⬇️ Exportar Vídeo'; }
    };
    function iniciarPolling() {
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = setInterval(async () => {
            // Aciona o worker em background e lê o status.
            fetch(conf.base + '/maquina-de-conteudo/video/processar-bg?_=' + Date.now()).catch(() => {});
            try {
                const r = await fetch(conf.base + '/maquina-de-conteudo/video/status?conteudo_id=' + conf.conteudoId + '&_=' + Date.now());
                const d = await r.json();
                if (!d.sucesso || d.status === 'nenhum') return;
                const barra = document.getElementById('exp-barra');
                const etapa = document.getElementById('exp-etapa');
                if (barra) barra.style.width = (d.progresso || 0) + '%';
                if (etapa) etapa.textContent = (d.etapa || '') + ' (' + (d.progresso || 0) + '%)';
                if (d.status === 'concluido' && d.video_url) {
                    clearInterval(pollTimer); pollTimer = null;
                    if (etapa) etapa.textContent = 'Concluído!';
                    document.getElementById('exp-resultado').classList.remove('hidden');
                    document.getElementById('exp-video').src = d.video_url;
                    document.getElementById('exp-download').href = d.video_url;
                    if (typeof Toast !== 'undefined') Toast.sucesso('Vídeo pronto!');
                } else if (d.status === 'erro') {
                    clearInterval(pollTimer); pollTimer = null;
                    if (etapa) etapa.textContent = 'Erro: ' + (d.mensagem || 'falha na renderização');
                }
            } catch (e) { /* continua */ }
        }, 3000);
    }

    // ---------- Init ----------
    function renderTudo() { renderBiblioteca(); renderPropsClipe(); renderTimeline(); renderTextos(); renderPreviewFrame(); }
    try { console.log('[VIDEO] editor iniciado. imagens=', (estado.imagens || []).length); } catch (e) {}
    aplicarNarracao(); aplicarMusica();
    renderTudo();
    // Autosave a cada 20s.
    setInterval(() => salvarProjeto(false), 20000);
    // Se já existe vídeo exportado, mostra.
    if (conf.videoUrl) {
        document.getElementById('box-exportacao').classList.remove('hidden');
        document.getElementById('exp-etapa').textContent = 'Última exportação';
        document.getElementById('exp-resultado').classList.remove('hidden');
        document.getElementById('exp-video').src = conf.videoUrl;
        document.getElementById('exp-download').href = conf.videoUrl;
    }
})();
