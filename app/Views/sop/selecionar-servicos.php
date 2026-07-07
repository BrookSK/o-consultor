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
            <p class="sub">Marque apenas os serviços que realmente fazem parte da sua empresa. Só os selecionados vão compor a lista de SOPs — os demais ficam de fora e não são gerados.</p>
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
                    <?php $on = ((int) ($sv['selecionado'] ?? 1)) === 1; ?>
                    <label class="item <?= $on ? 'on' : '' ?>">
                        <input type="checkbox" class="sv-check" value="<?= $sv['id'] ?>" <?= $on ? 'checked' : '' ?> onchange="onCheck(this)">
                        <span class="txt">
                            <?= htmlspecialchars($sv['nome_servico']) ?>
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

    <div class="footer-bar">
        <div class="resumo"><strong id="ft-count">0</strong> serviços selecionados vão compor seus SOPs</div>
        <button class="btn-primary" id="btn-confirmar" onclick="salvarSelecao()">✓ Confirmar seleção</button>
    </div>
</div>

<!-- Modal: descrever processo do setor por voz/texto -->
<div id="modalVozSetor" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 max-w-lg w-full mx-4 shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-1">
            <h3 class="text-lg font-semibold">🎤 Descrever processo — <span id="voz_setor_nome"></span></h3>
            <button onclick="fecharVozSetor()" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <p class="text-sm text-gray-500 mb-4">Conte como esse setor funciona hoje: o que é feito, as atividades, o dia a dia. A IA vai transformar sua descrição em serviços e adicioná-los à lista.</p>
        <input type="hidden" id="voz_setor_id">
        <div class="relative">
            <textarea id="voz_descricao" rows="6"
                      class="w-full px-3 py-2 pr-12 border border-gray-300 rounded-lg focus:outline-none focus:border-primary"
                      placeholder="Ex.: No comercial a gente recebe o lead, faz o primeiro contato, envia proposta, faz follow-up..."></textarea>
            <button type="button" id="voz_btn_mic" onclick="alternarGravacaoVoz()" title="Gravar por voz"
                    class="absolute right-2 bottom-2 w-9 h-9 rounded-full bg-primary text-white flex items-center justify-center hover:bg-primary-700">🎤</button>
        </div>
        <p id="voz_status" class="text-xs text-gray-400 mt-1 hidden"></p>
        <div class="flex gap-3 mt-6">
            <button onclick="fecharVozSetor()" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg">Cancelar</button>
            <button id="voz_btn_gerar" onclick="gerarServicosDoSetor()" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-700">✨ Gerar serviços</button>
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
        const body = new URLSearchParams({ diagnostico_id: DIAG_ID, servico_ids: ids.join(','), csrf_token: CSRF_TOKEN });
        const r = await fetch('<?= APP_URL ?>/sop/salvar-selecao-servicos', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body });
        const d = await r.json();
        if (d.sucesso) { window.location.href = d.redirect; }
        else { alert('Erro: ' + (d.erro || 'desconhecido')); btn.disabled = false; btn.textContent = '✓ Confirmar seleção'; }
    } catch (e) {
        alert('Erro de comunicação com o servidor.');
        btn.disabled = false; btn.textContent = '✓ Confirmar seleção';
    }
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
    document.getElementById('voz_setor_nome').textContent = nomeSetor;
    document.getElementById('voz_descricao').value = '';
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

async function gerarServicosDoSetor() {
    const setorId = document.getElementById('voz_setor_id').value;
    const descricao = document.getElementById('voz_descricao').value.trim();
    if (!descricao) { alert('Descreva o processo (fale ou digite).'); return; }

    const btn = document.getElementById('voz_btn_gerar');
    btn.disabled = true; btn.textContent = 'Gerando...';
    try {
        const body = new URLSearchParams({ setor_id: setorId, descricao: descricao, csrf_token: CSRF_TOKEN });
        const r = await fetch('<?= APP_URL ?>/sop/gerar-servicos-setor-voz', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body });
        const d = await r.json();
        if (d.sucesso) {
            adicionarServicosNaTela(setorId, d.criados);
            fecharVozSetor();
        } else {
            alert('Erro: ' + (d.erro || 'desconhecido'));
        }
    } catch (e) {
        alert('Erro de comunicação com o servidor.');
    } finally {
        btn.disabled = false; btn.textContent = '✨ Gerar serviços';
    }
}

// Insere os serviços recém-criados na lista do setor, já marcados.
function adicionarServicosNaTela(setorId, criados) {
    if (!criados || !criados.length) return;
    const head = document.querySelector('.sector-head[data-setor-id="' + setorId + '"]');
    if (!head) { location.reload(); return; }
    const setor = head.closest('[data-setor]');
    const body = setor.querySelector('.sector-body');

    // Agrupar por subcategoria: reaproveita lane existente ou cria nova
    criados.forEach(sv => {
        const sub = sv.subcategoria || 'Personalizado';
        let lane = Array.from(body.querySelectorAll('.lane')).find(l => l.querySelector('.lane-title')?.textContent.trim() === sub);
        if (!lane) {
            lane = document.createElement('div');
            lane.className = 'lane';
            lane.innerHTML = '<p class="lane-title">' + escaparHtml(sub) + '</p><div class="items"></div>';
            body.appendChild(lane);
        }
        const items = lane.querySelector('.items');
        const label = document.createElement('label');
        label.className = 'item on';
        label.innerHTML = '<input type="checkbox" class="sv-check" value="' + sv.id + '" checked onchange="onCheck(this)">'
            + '<span class="txt">' + escaparHtml(sv.nome_servico) + ' <span class="code">novo</span></span>';
        items.appendChild(label);
    });

    // Abrir o setor para o usuário ver os novos serviços
    head.classList.remove('collapsed');
    body.hidden = false;
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
