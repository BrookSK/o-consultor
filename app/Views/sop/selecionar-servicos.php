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
        <div class="sector-head collapsed" onclick="toggleSetor(this)">
            <span class="chev">▶</span>
            <div class="sector-dot"><?= $icone ?></div>
            <div class="sector-name">
                <h2><?= htmlspecialchars($setor['nome_setor']) ?></h2>
                <div class="sector-meta"><span class="core-tag"><?= ucfirst($setor['tipo_setor'] ?? 'geral') ?></span><?= count($servicos) ?> serviços</div>
            </div>
            <span class="sel-count" data-setor-count>0/<?= count($servicos) ?></span>
            <label class="sector-toggle-all" onclick="event.stopPropagation()">
                <input type="checkbox" onchange="toggleSetorTodos(this)"> marcar setor
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

// Estado inicial dos contadores
atualizarContadores();
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
