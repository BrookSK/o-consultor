<?php $tituloPagina = 'Selecionar Serviços - ' . htmlspecialchars($dados['empresa']['nome']); ?>
<?php ob_start(); ?>

<style>
#sel-view{--sv-page:#F3F4F8;--sv-surface:#FFFFFF;--sv-ink:#171B33;--sv-ink-soft:#565B78;--sv-ink-mute:#8B8FA3;--sv-line:#E4E5EE;--sv-accent:#E8590C;--sv-lane:#1E3A5F;--sv-lane-soft:#E8EDF3;--sv-lane-deep:#162D4A;--sv-ok:#2F9E44;--sv-ok-soft:#E4F6E8;color:var(--sv-ink);}
#sel-view .crumbs{font-size:13px;color:var(--sv-ink-mute);margin-bottom:18px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;}
#sel-view .crumbs a{color:var(--sv-ink-mute);text-decoration:none;}
#sel-view .crumbs a:hover{color:var(--sv-accent);}
#sel-view .crumbs .current{color:var(--sv-ink);font-weight:500;}
#sel-view .top{display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:22px;flex-wrap:wrap;gap:16px;}
#sel-view h1{font-size:26px;font-weight:700;margin:0 0 6px;letter-spacing:-0.01em;}
#sel-view .sub{font-size:14px;color:var(--sv-ink-soft);margin:0;max-width:640px;}
#sel-view .draft-badge{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;background:var(--sv-accent);color:#fff;padding:4px 12px;border-radius:20px;margin-bottom:10px;}

#sel-view .toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;background:var(--sv-surface);border:1px solid var(--sv-line);border-radius:12px;padding:12px 18px;margin-bottom:18px;}
#sel-view .toolbar .info{font-size:13.5px;color:var(--sv-ink-soft);}
#sel-view .toolbar .info strong{color:var(--sv-ink);}
#sel-view .toolbar .acts{display:flex;gap:8px;}
#sel-view .btn-mini{font-size:12.5px;font-weight:500;padding:7px 12px;border-radius:8px;border:1px solid var(--sv-line);background:#fff;color:var(--sv-ink-soft);cursor:pointer;}
#sel-view .btn-mini:hover{border-color:var(--sv-lane);color:var(--sv-lane-deep);}

#sel-view .sector{background:var(--sv-surface);border:1px solid var(--sv-line);border-radius:14px;margin-bottom:14px;overflow:hidden;}
#sel-view .sector-head{display:flex;align-items:center;gap:12px;padding:16px 20px;cursor:pointer;user-select:none;flex-wrap:wrap;}
#sel-view .sector-head:hover{background:#FAFBFD;}
#sel-view .chev{color:var(--sv-ink-mute);font-size:12px;transition:transform .15s;}
#sel-view .sector-head:not(.collapsed) .chev{transform:rotate(90deg);}
#sel-view .sector-dot{width:32px;height:32px;border-radius:9px;background:var(--sv-lane-soft);color:var(--sv-lane-deep);display:flex;align-items:center;justify-content:center;font-size:15px;}
#sel-view .sector-name{flex:1;min-width:0;}
#sel-view .sector-name h2{font-size:16px;font-weight:600;margin:0;}
#sel-view .sector-meta{font-size:12px;color:var(--sv-ink-mute);margin-top:2px;}
#sel-view .core-tag{background:var(--sv-lane-soft);color:var(--sv-lane-deep);font-size:11px;font-weight:600;padding:2px 8px;border-radius:6px;margin-right:6px;}
#sel-view .sel-count{font-size:12px;font-weight:600;color:var(--sv-ok);background:var(--sv-ok-soft);padding:4px 10px;border-radius:20px;}
#sel-view .sector-toggle-all{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--sv-ink-soft);}
#sel-view .sector-ignore{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--sv-ink-mute);}
#sel-view .sector-ignore input{accent-color:#c0392b;}
#sel-view .sector-mic{width:34px;height:34px;border-radius:9px;border:1px solid var(--sv-line);background:#fff;cursor:pointer;font-size:15px;display:flex;align-items:center;justify-content:center;}
#sel-view .sector-mic:hover{border-color:var(--sv-lane);}
#sel-view .sector-mic.rec{background:#c0392b;border-color:#c0392b;animation:selpulse 1s infinite;}
@keyframes selpulse{0%,100%{opacity:1;}50%{opacity:.5;}}
#sel-view .sector.ignored{opacity:.5;}
#sel-view .sector.ignored .sector-body{display:none !important;}

#sel-view .sector-body{border-top:1px solid var(--sv-line);padding:14px 20px 18px;}
#sel-view .lane{margin-bottom:14px;}
#sel-view .lane:last-child{margin-bottom:0;}
#sel-view .lane-title{font-size:12px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:var(--sv-ink-soft);margin:0 0 8px;border-bottom:1px solid var(--sv-line);padding-bottom:5px;}
#sel-view .items{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:8px;}
#sel-view .item{display:flex;align-items:center;gap:10px;padding:9px 12px;border:1px solid var(--sv-line);border-radius:9px;cursor:pointer;transition:border-color .12s,background .12s;}
#sel-view .item:hover{border-color:var(--sv-lane);background:var(--sv-lane-soft);}
#sel-view .item.on{border-color:var(--sv-ok);background:var(--sv-ok-soft);}
#sel-view .item input{width:16px;height:16px;accent-color:var(--sv-ok);cursor:pointer;flex-shrink:0;}
#sel-view .item .txt{font-size:13px;line-height:1.3;}
#sel-view .item .code{font-size:10.5px;color:var(--sv-ink-mute);font-family:ui-monospace,Menlo,monospace;}
#sel-view .gap-tag{display:inline-block;font-size:10.5px;font-weight:600;color:#B54708;background:#FEF0C7;border-radius:5px;padding:1px 6px;margin-left:6px;vertical-align:middle;}
#sel-view .item[data-estado="identificado"]{border-color:var(--sv-ok);}

#sel-view .footer-bar{position:sticky;bottom:16px;z-index:20;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;background:var(--sv-surface);border:1px solid var(--sv-line);border-left:4px solid var(--sv-ok);border-radius:12px;padding:14px 20px;box-shadow:0 6px 20px rgba(23,27,51,0.12);margin-top:18px;}
#sel-view .footer-bar .resumo{font-size:14px;color:var(--sv-ink-soft);}
#sel-view .footer-bar .resumo strong{color:var(--sv-ink);font-size:16px;}
#sel-view .btn-primary{font-size:14px;font-weight:600;color:#fff;background:var(--sv-lane);border:none;padding:11px 22px;border-radius:10px;cursor:pointer;}
#sel-view .btn-primary:hover{background:var(--sv-lane-deep);}
#sel-view .btn-primary:disabled{opacity:.5;cursor:not-allowed;}
</style>

<div id="sel-view">
    <div class="crumbs">
        <a href="<?= APP_URL ?>/dashboard">Dashboard</a> <span>/</span>
        <a href="<?= APP_URL ?>/diagnostico">Diagnósticos</a> <span>/</span>
        <a href="<?= APP_URL ?>/diagnostico/resultado/<?= $dados['diagnostico']['id'] ?>">Resultado</a> <span>/</span>
        <span class="current">Selecionar Serviços</span>
    </div>

    <div class="top">
        <div>
            <div class="draft-badge">✎ Rascunho</div>
            <h1>Selecione os serviços dos SOPs</h1>
            <p class="sub">Use o microfone de cada setor para conversar com a IA: ela pré-marca os serviços que existem ou são gaps, e esconde os que você disser que não usa. Depois é só revisar e confirmar — os selecionados vão compor a lista de SOPs.</p>
        </div>
    </div>

    <div class="toolbar">
        <div class="info"><strong id="tb-count">0</strong> de <?= $dados['total_servicos'] ?> serviços selecionados</div>
        <div class="acts">
            <button class="btn-mini" onclick="selecionarTodos(true)">Selecionar todos</button>
            <button class="btn-mini" onclick="selecionarTodos(false)">Limpar tudo</button>
            <button class="btn-mini" onclick="expandirTodos(true)">Expandir setores</button>
            <button class="btn-mini" onclick="expandirTodos(false)">Recolher setores</button>
        </div>
    </div>

    <?php foreach ($dados['setores'] as $bloco): ?>
    <?php
        $setor = $bloco['setor'];
        $servicos = $bloco['servicos'];
        $icone = '📁';
        switch ($setor['tipo_setor'] ?? 'apoio') {
            case 'core': $icone = '⚙'; break;
            case 'apoio': $icone = '🛠'; break;
            case 'estrategico': $icone = '📋'; break;
        }
        // Agrupar por subcategoria
        $porSub = [];
        foreach ($servicos as $sv) { $porSub[$sv['subcategoria'] ?: 'Geral'][] = $sv; }
        ksort($porSub);
    ?>
    <div class="sector" data-setor>
        <div class="sector-head collapsed" onclick="toggleSetor(this)"
             data-setor-id="<?= (int) $setor['id'] ?>" data-setor-nome="<?= htmlspecialchars($setor['nome_setor'], ENT_QUOTES) ?>">
            <span class="chev">▶</span>
            <div class="sector-dot"><?= $icone ?></div>
            <div class="sector-name">
                <h2><?= htmlspecialchars($setor['nome_setor']) ?></h2>
                <div class="sector-meta"><span class="core-tag"><?= ucfirst($setor['tipo_setor'] ?? 'geral') ?></span><span data-setor-total><?= count($servicos) ?></span> serviços</div>
            </div>
            <span class="sel-count" data-setor-count>0/<?= count($servicos) ?></span>
            <button type="button" class="sector-mic" title="Descrever o processo por voz e gerar serviços"
                    onclick="event.stopPropagation(); abrirVozSetor(<?= (int) $setor['id'] ?>, '<?= htmlspecialchars($setor['nome_setor'], ENT_QUOTES) ?>')">🎤</button>
            <label class="sector-toggle-all" onclick="event.stopPropagation()">
                <input type="checkbox" onchange="toggleSetorTodos(this)"> marcar setor
            </label>
            <label class="sector-ignore" onclick="event.stopPropagation()" title="Ignorar este setor (não entra nos SOPs)">
                <input type="checkbox" onchange="ignorarSetor(this)"> ignorar
            </label>
        </div>
        <div class="sector-body" hidden>
            <?php foreach ($porSub as $subcategoria => $itens): ?>
            <div class="lane">
                <p class="lane-title"><?= htmlspecialchars($subcategoria) ?></p>
                <div class="items">
                    <?php foreach ($itens as $sv): ?>
                    <?php
                        $estado = $sv['status_conversa'] ?? 'sugerido';
                        // No fluxo conversacional a marcação é governada pela conversa:
                        // só nasce marcado quem a IA classificou como "identificado".
                        // (desacoplado do 'selecionado' antigo, que vinha 1 por default).
                        $on = $estado === 'identificado';
                        $isGap = ((int) ($sv['gap_identificado'] ?? 0)) === 1;
                        $motivo = trim((string) ($sv['motivo_conversa'] ?? ''));
                    ?>
                    <label class="item <?= $on ? 'on' : '' ?>" data-servico-id="<?= $sv['id'] ?>" data-estado="<?= htmlspecialchars($estado) ?>">
                        <input type="checkbox" class="sv-check" value="<?= $sv['id'] ?>" <?= $on ? 'checked' : '' ?> onchange="onCheck(this)">
                        <span class="txt">
                            <?= htmlspecialchars($sv['nome_servico']) ?>
                            <?php if ($isGap): ?><span class="gap-tag" title="<?= htmlspecialchars($motivo) ?>">gap<?= $motivo !== '' ? ': ' . htmlspecialchars($motivo) : '' ?></span><?php endif; ?>
                            <span class="code"><?= htmlspecialchars($sv['codigo_servico'] ?? '') ?></span>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="footer-bar" id="footer-bar">
        <div class="resumo"><strong id="ft-count">0</strong> serviços selecionados vão compor seus SOPs</div>
        <button class="btn-primary" id="btn-confirmar" onclick="salvarSelecao()">✓ Confirmar e gerar SOPs</button>
    </div>
</div>

<!-- Modal: entrevista conversacional por voz (classifica os serviços do setor) -->
<div id="modalVozSetor" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 max-w-lg w-full mx-4 shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-1">
            <h3 class="text-lg font-semibold">🎤 Entrevista — <span id="voz_setor_nome"></span></h3>
            <button onclick="fecharVozSetor()" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <p class="text-sm text-gray-500 mb-3">Conte como esse setor funciona hoje: o que vocês fazem, o que não fazem, o dia a dia. A IA vai marcar automaticamente os serviços que existem, sugerir gaps e esconder o que você disser que não usa.</p>
        <input type="hidden" id="voz_setor_id">
        <input type="hidden" id="voz_turno" value="1">

        <!-- Pergunta guiada da IA (aparece a partir do 2º turno) -->
        <div id="voz_pergunta_box" class="hidden mb-3 p-3 rounded-lg bg-blue-50 border border-blue-100 text-sm text-blue-900"></div>

        <div class="relative">
            <textarea id="voz_descricao" rows="5"
                      class="w-full px-3 py-2 pr-12 border border-gray-300 rounded-lg focus:outline-none focus:border-primary"
                      placeholder="Ex.: No comercial a gente recebe o lead, faz o primeiro contato, envia proposta e follow-up. A gente não trabalha com revenda nem parceiros."></textarea>
            <button type="button" id="voz_btn_mic" onclick="alternarGravacaoVoz()" title="Gravar por voz"
                    class="absolute right-2 bottom-2 w-9 h-9 rounded-full bg-primary text-white flex items-center justify-center hover:bg-primary-700">🎤</button>
        </div>
        <p id="voz_status" class="text-xs text-gray-400 mt-1 hidden"></p>

        <!-- Resumo do último turno -->
        <div id="voz_resumo" class="hidden mt-3 text-xs text-gray-600 bg-gray-50 rounded-lg p-3"></div>

        <div class="flex gap-3 mt-6">
            <button onclick="fecharVozSetor()" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg">Concluir</button>
            <button id="voz_btn_gerar" onclick="enviarTurnoConversa()" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-700">✨ Analisar resposta</button>
        </div>
    </div>
</div>

<script>
const CSRF_TOKEN = '<?= Csrf::token() ?>';
const DIAG_ID = <?= (int) $dados['diagnostico']['id'] ?>;

function toggleSetor(head) {
    const body = head.nextElementSibling;
    const col = head.classList.toggle('collapsed');
    if (body) body.hidden = col;
}
function expandirTodos(abrir) {
    document.querySelectorAll('#sel-view .sector-head').forEach(h => {
        h.classList.toggle('collapsed', !abrir);
        if (h.nextElementSibling) h.nextElementSibling.hidden = !abrir;
    });
}

function onCheck(cb) {
    cb.closest('.item')?.classList.toggle('on', cb.checked);
    atualizarContadores();
}

function toggleSetorTodos(master) {
    const setor = master.closest('[data-setor]');
    setor.querySelectorAll('.sv-check').forEach(cb => {
        cb.checked = master.checked;
        cb.closest('.item')?.classList.toggle('on', master.checked);
    });
    atualizarContadores();
}

function selecionarTodos(marcar) {
    document.querySelectorAll('#sel-view .sv-check').forEach(cb => {
        cb.checked = marcar;
        cb.closest('.item')?.classList.toggle('on', marcar);
    });
    document.querySelectorAll('#sel-view .sector-toggle-all input').forEach(m => m.checked = marcar);
    atualizarContadores();
}

function atualizarContadores() {
    let total = 0;
    document.querySelectorAll('#sel-view [data-setor]').forEach(setor => {
        const checks = setor.querySelectorAll('.sv-check');
        const marcados = setor.querySelectorAll('.sv-check:checked').length;
        total += marcados;
        const badge = setor.querySelector('[data-setor-count]');
        if (badge) badge.textContent = marcados + '/' + checks.length;
        const totalEl = setor.querySelector('[data-setor-total]');
        if (totalEl) totalEl.textContent = checks.length;
        const master = setor.querySelector('.sector-toggle-all input');
        if (master) {
            master.checked = marcados > 0 && marcados === checks.length;
            master.indeterminate = marcados > 0 && marcados < checks.length;
        }
    });
    document.getElementById('tb-count').textContent = total;
    document.getElementById('ft-count').textContent = total;
    const btn = document.getElementById('btn-confirmar');
    if (btn) btn.disabled = total === 0;
}

async function salvarSelecao() {
    const ids = Array.from(document.querySelectorAll('#sel-view .sv-check:checked')).map(c => c.value);
    if (ids.length === 0) { alert('Selecione ao menos um serviço.'); return; }

    const btn = document.getElementById('btn-confirmar');
    btn.disabled = true; btn.textContent = 'Salvando...';

    try {
        // 1. Salvar a seleção (mesma semântica de sempre)
        const body = new URLSearchParams({ diagnostico_id: DIAG_ID, servico_ids: ids.join(','), csrf_token: CSRF_TOKEN });
        const r = await fetch('<?= APP_URL ?>/sop/salvar-selecao-servicos', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body });
        const d = await r.json();
        if (!d.sucesso) { alert('Erro: ' + (d.erro || 'desconhecido')); btn.disabled = false; btn.textContent = '✓ Confirmar seleção'; return; }

        // 2. Disparar a geração de TODOS os selecionados de uma vez (lote paralelo)
        btn.textContent = 'Iniciando geração...';
        const body2 = new URLSearchParams({ diagnostico_id: DIAG_ID, csrf_token: CSRF_TOKEN });
        const r2 = await fetch('<?= APP_URL ?>/sop/gerar-selecionados-lote', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: body2 });
        const d2 = await r2.json();
        if (d2.sucesso && d2.lote_id) {
            acompanharLote(d2.lote_id, d2.total, d2.redirect);
        } else {
            // Se não conseguiu gerar em lote, ao menos leva para a listagem.
            window.location.href = d.redirect;
        }
    } catch (e) {
        alert('Erro de comunicação com o servidor.');
        btn.disabled = false; btn.textContent = '✓ Confirmar seleção';
    }
}

// Mostra o progresso do lote; o usuário pode sair — a geração segue em background.
function acompanharLote(loteId, total, redirect) {
    const bar = document.getElementById('footer-bar');
    if (bar) {
        bar.innerHTML = '<div class="resumo" style="flex:1">'
            + '<strong id="lote-pct">0%</strong> — gerando <strong>' + total + '</strong> SOP(s) em paralelo. '
            + 'Você pode sair desta tela; avisaremos quando terminar.'
            + '<div style="height:8px;background:#E4E5EE;border-radius:6px;margin-top:8px;overflow:hidden">'
            + '<div id="lote-progress" style="height:100%;width:0;background:var(--sv-ok);transition:width .4s"></div></div>'
            + '</div>'
            + '<button class="btn-primary" onclick="window.location.href=\'' + redirect + '\'">Ver SOPs agora →</button>';
    }
    const poll = setInterval(async () => {
        try {
            const r = await fetch('<?= APP_URL ?>/sop/status-lote?lote_id=' + encodeURIComponent(loteId));
            const d = await r.json();
            if (!d.sucesso) return;
            const pctEl = document.getElementById('lote-pct');
            const barEl = document.getElementById('lote-progress');
            if (pctEl) pctEl.textContent = (d.percentual || 0) + '%';
            if (barEl) barEl.style.width = (d.percentual || 0) + '%';
            if (d.finalizado) {
                clearInterval(poll);
                window.location.href = redirect;
            }
        } catch (e) { /* segue tentando */ }
    }, 4000);
}

// ---- Ignorar setor: desmarca tudo e marca visualmente como ignorado ----
function ignorarSetor(cb) {
    const setor = cb.closest('[data-setor]');
    setor.classList.toggle('ignored', cb.checked);
    if (cb.checked) {
        setor.querySelectorAll('.sv-check').forEach(c => { c.checked = false; c.closest('.item')?.classList.remove('on'); });
        const master = setor.querySelector('.sector-toggle-all input');
        if (master) { master.checked = false; master.indeterminate = false; }
        // recolher o corpo
        const head = setor.querySelector('.sector-head');
        head.classList.add('collapsed');
        const body = head.nextElementSibling;
        if (body) body.hidden = true;
    }
    atualizarContadores();
}

// ---- Gerar serviços do setor por voz/texto ----
let vozRecorder = null;
let vozChunks = [];

function abrirVozSetor(setorId, nomeSetor) {
    document.getElementById('voz_setor_id').value = setorId;
    document.getElementById('voz_turno').value = '1';
    document.getElementById('voz_setor_nome').textContent = nomeSetor;
    document.getElementById('voz_descricao').value = '';
    document.getElementById('voz_pergunta_box').classList.add('hidden');
    document.getElementById('voz_resumo').classList.add('hidden');
    const st = document.getElementById('voz_status');
    st.classList.add('hidden'); st.textContent = '';
    document.getElementById('modalVozSetor').classList.remove('hidden');
}
function fecharVozSetor() {
    if (vozRecorder && vozRecorder.state === 'recording') { try { vozRecorder.stop(); } catch(e){} }
    document.getElementById('modalVozSetor').classList.add('hidden');
}

const REC_LIMITE_SEG = 300; // 5 minutos
function iniciarCronometro(statusEl, onFim) {
    let restante = REC_LIMITE_SEG;
    const fmt = (s) => Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
    statusEl.classList.remove('hidden');
    statusEl.textContent = '🔴 Gravando... ' + fmt(restante) + ' restante · clique para parar.';
    const timer = setInterval(() => {
        restante--;
        if (restante <= 0) {
            clearInterval(timer);
            statusEl.textContent = 'Tempo máximo atingido. Finalizando...';
            if (typeof onFim === 'function') onFim();
            return;
        }
        statusEl.textContent = '🔴 Gravando... ' + fmt(restante) + ' restante · clique para parar.';
    }, 1000);
    return timer;
}

async function alternarGravacaoVoz() {
    const btn = document.getElementById('voz_btn_mic');
    const st = document.getElementById('voz_status');
    if (vozRecorder && vozRecorder.state === 'recording') { vozRecorder.stop(); return; }
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) { alert('Seu navegador não suporta gravação.'); return; }
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        vozChunks = [];
        let cron = null;
        vozRecorder = new MediaRecorder(stream, { mimeType: MediaRecorder.isTypeSupported('audio/webm') ? 'audio/webm' : 'audio/mp4' });
        vozRecorder.ondataavailable = e => { if (e.data.size > 0) vozChunks.push(e.data); };
        vozRecorder.onstop = async () => {
            if (cron) clearInterval(cron);
            stream.getTracks().forEach(t => t.stop());
            btn.classList.remove('rec'); btn.textContent = '🎤';
            st.classList.remove('hidden'); st.textContent = 'Transcrevendo áudio...';
            await transcreverVoz();
        };
        vozRecorder.start();
        btn.classList.add('rec'); btn.textContent = '⏹';
        cron = iniciarCronometro(st, () => { if (vozRecorder && vozRecorder.state === 'recording') vozRecorder.stop(); });
    } catch (e) { alert('Não foi possível acessar o microfone.'); }
}

async function tokenFresco() {
    try {
        const tr = await fetch('<?= APP_URL ?>/api/csrf-token', { headers: { 'Accept': 'application/json' } });
        if (tr.ok) { const td = await tr.json(); if (td.token) return td.token; }
    } catch (e) {}
    return CSRF_TOKEN;
}

async function transcreverVoz() {
    const st = document.getElementById('voz_status');
    try {
        const token = await tokenFresco();
        const blob = new Blob(vozChunks, { type: 'audio/webm' });
        const fd = new FormData();
        fd.append('audio', blob, 'gravacao.webm');
        fd.append('csrf_token', token);
        const r = await fetch('<?= APP_URL ?>/api/transcricao', { method: 'POST', headers: { 'X-CSRF-Token': token }, body: fd });
        const d = await r.json();
        if (d.sucesso && d.transcricao) {
            const ta = document.getElementById('voz_descricao');
            ta.value = (ta.value ? ta.value.trim() + '\n' : '') + d.transcricao.trim();
            st.textContent = 'Transcrição adicionada.';
            setTimeout(() => st.classList.add('hidden'), 2500);
        } else {
            st.textContent = 'Não foi possível transcrever: ' + (d.erro || 'erro');
        }
    } catch (e) { st.textContent = 'Erro ao transcrever o áudio.'; }
}

// Envia um turno da conversa: classifica os serviços do setor e atualiza a tela.
async function enviarTurnoConversa() {
    const setorId = document.getElementById('voz_setor_id').value;
    const transcricao = document.getElementById('voz_descricao').value.trim();
    const turno = parseInt(document.getElementById('voz_turno').value || '1', 10);
    if (!transcricao) { alert('Fale ou digite sua resposta.'); return; }

    const btn = document.getElementById('voz_btn_gerar');
    btn.disabled = true; btn.textContent = 'Analisando...';
    try {
        const body = new URLSearchParams({ setor_id: setorId, transcricao: transcricao, turno: turno, csrf_token: CSRF_TOKEN });
        const r = await fetch('<?= APP_URL ?>/sop/classificar-conversa-setor', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body });
        const d = await r.json();
        if (!d.sucesso) { alert('Erro: ' + (d.erro || 'desconhecido')); return; }

        aplicarClassificacaoNaTela(setorId, d.servicos || []);

        // Resumo do turno
        const resumo = d.resumo || {};
        const rEl = document.getElementById('voz_resumo');
        rEl.innerHTML = '✓ <strong>' + (resumo.identificados || 0) + '</strong> identificados · '
            + '<strong>' + (resumo.gaps || 0) + '</strong> gaps · '
            + '<strong>' + (resumo.excluidos || 0) + '</strong> removidos deste setor.';
        rEl.classList.remove('hidden');

        // Próxima pergunta guiada (se houver)
        const perguntas = d.perguntas_seguimento || [];
        const pBox = document.getElementById('voz_pergunta_box');
        if (perguntas.length) {
            pBox.textContent = '💬 ' + perguntas[0];
            pBox.classList.remove('hidden');
            document.getElementById('voz_descricao').value = '';
            document.getElementById('voz_turno').value = String(turno + 1);
            btn.textContent = '✨ Responder';
        } else {
            pBox.classList.add('hidden');
            setTimeout(fecharVozSetor, 1200);
        }
    } catch (e) {
        alert('Erro de comunicação com o servidor.');
    } finally {
        btn.disabled = false;
        if (btn.textContent === 'Analisando...') btn.textContent = '✨ Analisar resposta';
    }
}

// Aplica na tela o resultado da classificação: marca identificados, remove excluídos, anota gaps.
function aplicarClassificacaoNaTela(setorId, servicos) {
    if (!servicos || !servicos.length) return;
    const head = document.querySelector('.sector-head[data-setor-id="' + setorId + '"]');
    if (!head) { location.reload(); return; }
    const setor = head.closest('[data-setor]');

    servicos.forEach(sv => {
        const item = setor.querySelector('.item[data-servico-id="' + sv.id + '"]');
        if (!item) return;
        if (sv.estado === 'excluido') { item.remove(); return; }
        item.dataset.estado = sv.estado;
        const cb = item.querySelector('.sv-check');
        if (cb) { cb.checked = !!sv.selecionado; item.classList.toggle('on', !!sv.selecionado); }
        // Anotação de gap
        const txt = item.querySelector('.txt');
        if (sv.gap && txt && !txt.querySelector('.gap-tag')) {
            const tag = document.createElement('span');
            tag.className = 'gap-tag';
            tag.textContent = sv.motivo ? ('gap: ' + sv.motivo) : 'gap';
            const code = txt.querySelector('.code');
            txt.insertBefore(tag, code || null);
        }
    });

    // Remover lanes que ficaram vazias após excluir serviços
    setor.querySelectorAll('.lane').forEach(l => {
        if (!l.querySelector('.item')) l.remove();
    });

    // Abrir o setor para o usuário revisar
    head.classList.remove('collapsed');
    const body = setor.querySelector('.sector-body');
    if (body) body.hidden = false;
    setor.classList.remove('ignored');
    const ign = setor.querySelector('.sector-ignore input');
    if (ign) ign.checked = false;

    atualizarContadores();
}

function escaparHtml(s) {
    const d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
}

// Estado inicial dos contadores
atualizarContadores();
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
