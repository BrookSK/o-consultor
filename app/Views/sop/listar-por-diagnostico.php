<?php $tituloPagina = 'SOPs - ' . htmlspecialchars($dados['empresa']['nome']); ?>
<?php ob_start(); ?>

<style>
/* ===== Estilo dos SOPs por categoria (referência sops-dashboard) — escopo #sops-view ===== */
#sops-view{
    --sv-page:#F3F4F8; --sv-surface:#FFFFFF; --sv-ink:#171B33; --sv-ink-soft:#565B78;
    --sv-ink-mute:#8B8FA3; --sv-line:#E4E5EE; --sv-accent:#E8590C; --sv-accent-soft:#FDEBE0;
    --sv-accent-deep:#9A3A08; --sv-lane:#1E3A5F; --sv-lane-soft:#E8EDF3; --sv-lane-deep:#162D4A;
    --sv-ok:#2F9E44; --sv-ok-soft:#E4F6E8; --sv-ok-deep:#1F7A34;
    color:var(--sv-ink);
}
#sops-view .crumbs{font-size:13px;color:var(--sv-ink-mute);margin-bottom:18px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;}
#sops-view .crumbs a{color:var(--sv-ink-mute);text-decoration:none;}
#sops-view .crumbs a:hover{color:var(--sv-accent-deep);}
#sops-view .crumbs .current{color:var(--sv-ink);font-weight:500;}
#sops-view .sv-top{display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:28px;flex-wrap:wrap;gap:16px;}
#sops-view .sv-top h1{font-size:28px;font-weight:700;margin:0 0 6px;letter-spacing:-0.01em;}
#sops-view .sv-top .sv-sub{font-size:14px;color:var(--sv-ink-soft);margin:0;max-width:560px;}
#sops-view .date-box{text-align:right;font-size:12px;color:var(--sv-ink-mute);}
#sops-view .date-box strong{display:block;font-size:15px;color:var(--sv-ink);margin-top:3px;}

#sops-view .stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:34px;}
#sops-view .stat{background:var(--sv-surface);border:1px solid var(--sv-line);border-radius:14px;padding:16px 18px;position:relative;overflow:hidden;}
#sops-view .stat::before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--sv-bar,var(--sv-lane));}
#sops-view .stat .lab{font-size:12px;color:var(--sv-ink-mute);font-weight:500;margin-bottom:6px;}
#sops-view .stat .val{font-size:26px;font-weight:700;color:var(--sv-val,var(--sv-ink));}
#sops-view .stat.s1{--sv-bar:#378ADD;}
#sops-view .stat.s2{--sv-bar:var(--sv-lane);}
#sops-view .stat.s3{--sv-bar:var(--sv-ok);}
#sops-view .stat.s4{--sv-bar:var(--sv-accent);}
#sops-view .stat.s4 .val{color:var(--sv-accent-deep);}

#sops-view .sector-block{margin-bottom:36px;}
#sops-view .sector-head{background:var(--sv-surface);border:1px solid var(--sv-line);border-radius:16px 16px 0 0;padding:18px 22px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;cursor:pointer;user-select:none;}
#sops-view .sector-head.collapsed{border-radius:16px;}
#sops-view .sector-head:hover{background:#FAFBFD;}
#sops-view .sector-chev{color:var(--sv-ink-mute);font-size:12px;transition:transform .15s;flex-shrink:0;}
#sops-view .sector-head:not(.collapsed) .sector-chev{transform:rotate(90deg);}
#sops-view .sector-name{display:flex;align-items:center;gap:12px;}
#sops-view .sector-name .dot{width:34px;height:34px;border-radius:9px;background:var(--sv-lane-soft);color:var(--sv-lane-deep);display:flex;align-items:center;justify-content:center;font-size:16px;}
#sops-view .sector-name h2{font-size:19px;margin:0;letter-spacing:0.02em;font-weight:600;}
#sops-view .sector-meta{font-size:12.5px;color:var(--sv-ink-mute);margin-top:2px;}
#sops-view .core-tag{background:var(--sv-lane-soft);color:var(--sv-lane-deep);font-size:11px;font-weight:600;padding:2px 8px;border-radius:6px;margin-right:8px;}
#sops-view .badge-status{font-size:12px;font-weight:600;padding:6px 12px;border-radius:20px;}
#sops-view .badge-status.completo{background:var(--sv-ok-soft);color:var(--sv-ok-deep);}
#sops-view .badge-status.parcial{background:var(--sv-accent-soft);color:var(--sv-accent-deep);}
#sops-view .badge-status.pendente{background:#EEF0F5;color:var(--sv-ink-mute);}
#sops-view .add-servico{font-size:12px;font-weight:600;color:var(--sv-lane-deep);background:var(--sv-lane-soft);border:none;padding:6px 12px;border-radius:8px;cursor:pointer;}
#sops-view .add-servico:hover{background:#dbe4ef;}

#sops-view .lanes-wrap{background:var(--sv-surface);border:1px solid var(--sv-line);border-top:none;border-radius:0 0 16px 16px;padding:6px 0 20px;}
#sops-view .lane{padding:22px 22px 4px;border-bottom:1px solid var(--sv-line);}
#sops-view .lane:last-child{border-bottom:none;}
#sops-view .lane-head{display:flex;align-items:baseline;justify-content:space-between;margin-bottom:14px;gap:10px;}
#sops-view .lane-title{display:flex;align-items:center;gap:10px;}
#sops-view .lane-title h3{font-size:13px;font-weight:600;letter-spacing:0.06em;text-transform:uppercase;color:var(--sv-ink-soft);margin:0;}
#sops-view .lane-count{font-size:11.5px;color:var(--sv-lane-deep);background:var(--sv-lane-soft);padding:2px 8px;border-radius:20px;font-weight:600;}
#sops-view .az-tag{font-size:11px;color:var(--sv-ink-mute);letter-spacing:0.03em;}
#sops-view .stack{display:flex;flex-direction:column;gap:8px;padding-bottom:18px;}

#sops-view .bar{display:grid;grid-template-columns:34px 116px 1fr auto auto auto;align-items:center;gap:14px;background:var(--sv-surface);border:1px solid var(--sv-line);border-left:3px solid var(--sv-lane);border-radius:10px;padding:11px 16px;transition:border-color .15s, background .15s;}
#sops-view .bar:hover{border-color:var(--sv-lane);border-left-color:var(--sv-lane-deep);background:var(--sv-lane-soft);}
#sops-view .letter{width:26px;height:26px;border-radius:8px;background:var(--sv-lane);color:#fff;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;}
#sops-view .code{font-size:11px;color:var(--sv-ink-mute);white-space:nowrap;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;}
#sops-view .bar-title{font-size:13.5px;font-weight:500;line-height:1.3;min-width:0;cursor:pointer;}
#sops-view .bar-title:hover{color:var(--sv-lane-deep);}
#sops-view .op-tag{font-size:10.5px;font-weight:600;color:var(--sv-lane-deep);background:var(--sv-lane-soft);padding:3px 9px;border-radius:6px;white-space:nowrap;}
#sops-view .status{font-size:11px;color:var(--sv-ink-mute);display:flex;align-items:center;gap:5px;white-space:nowrap;}
#sops-view .status .ring{width:7px;height:7px;border-radius:50%;border:1.5px solid var(--sv-ink-mute);}
#sops-view .status.pronto{color:var(--sv-ok-deep);}
#sops-view .status.pronto .ring{background:var(--sv-ok);border-color:var(--sv-ok);}
#sops-view .row-actions{display:flex;align-items:center;gap:14px;white-space:nowrap;}
#sops-view .gen-sop{font-size:11.5px;font-weight:600;color:var(--sv-accent-deep);display:flex;align-items:center;gap:4px;cursor:pointer;border:none;background:none;padding:0;}
#sops-view .gen-sop::before{content:'⚡';font-size:10px;}
#sops-view .see-sop{font-size:11.5px;font-weight:600;color:var(--sv-ok-deep);cursor:pointer;border:none;background:none;padding:0;}
#sops-view .icon-link{color:var(--sv-ink-mute);cursor:pointer;font-size:12px;text-decoration:none;background:none;border:none;padding:0;}
#sops-view .icon-link:hover{color:var(--sv-ink);}
#sops-view .icon-link.danger{color:#c0392b;}
#sops-view .lane-empty{font-size:12.5px;color:var(--sv-ink-mute);padding:0 0 16px;}
#sops-view .footer-note{text-align:center;font-size:12px;color:var(--sv-ink-mute);margin-top:20px;}

@media (max-width:820px){
    #sops-view .stats{grid-template-columns:repeat(2,1fr);}
    #sops-view .bar{grid-template-columns:28px 1fr auto;grid-template-areas:"letter code status" "letter title title" "letter tag actions";row-gap:6px;}
    #sops-view .letter{grid-area:letter;}
    #sops-view .code{grid-area:code;}
    #sops-view .status{grid-area:status;justify-self:end;}
    #sops-view .bar-title{grid-area:title;}
    #sops-view .op-tag{grid-area:tag;justify-self:start;}
    #sops-view .row-actions{grid-area:actions;justify-self:end;}
}
</style>

<div id="sops-view">

    <!-- Breadcrumb -->
    <div class="crumbs">
        <a href="<?= APP_URL ?>/dashboard">Dashboard</a> <span>/</span>
        <a href="<?= APP_URL ?>/diagnostico">Diagnósticos</a> <span>/</span>
        <a href="<?= APP_URL ?>/diagnostico/resultado/<?= $dados['diagnostico']['id'] ?>">Resultado</a> <span>/</span>
        <span class="current">SOPs Gerados — por categoria</span>
    </div>

    <!-- Topo -->
    <div class="sv-top">
        <div>
            <h1>SOPs · <?= htmlspecialchars($dados['empresa']['nome']) ?></h1>
            <p class="sv-sub">Procedimentos Operacionais Padrão, agrupados por categoria e empilhados em faixas horizontais, ordenados de A a Z dentro de cada categoria.</p>
        </div>
        <div class="date-box">
            Diagnóstico realizado em
            <strong><?= date('d/m/Y', strtotime($dados['diagnostico']['criado_em'])) ?></strong>
        </div>
    </div>

    <!-- Estatísticas -->
    <div class="stats">
        <div class="stat s1"><div class="lab">Setores</div><div class="val"><?= $dados['estatisticas']['total_setores'] ?></div></div>
        <div class="stat s2"><div class="lab">Serviços</div><div class="val"><?= $dados['estatisticas']['total_servicos'] ?></div></div>
        <div class="stat s3"><div class="lab">SOPs gerados</div><div class="val"><?= $dados['estatisticas']['total_sops'] ?></div></div>
        <div class="stat s4"><div class="lab">Progresso</div><div class="val"><?= $dados['estatisticas']['percentual_conclusao'] ?>%</div></div>
    </div>

    <?php if (!empty($dados['setores_organizados'])): ?>
        <?php foreach ($dados['setores_organizados'] as $setorData): ?>
        <?php
            $setor = $setorData['setor'];
            $servicos = $setorData['servicos'];
            $totalServicos = $setor['total_servicos'] ?? 0;
            $totalSops = $setor['total_sops'] ?? 0;

            $iconeSetor = '📁';
            switch ($setor['tipo_setor'] ?? 'operacional') {
                case 'core': $iconeSetor = '⚙'; break;
                case 'apoio': $iconeSetor = '🛠'; break;
                case 'estrategico': $iconeSetor = '📋'; break;
            }

            // Agrupar serviços por subcategoria (lanes)
            $porSub = [];
            foreach ($servicos as $sv) {
                $sub = $sv['subcategoria'] ?? 'Geral';
                $porSub[$sub][] = $sv;
            }
            ksort($porSub);
        ?>

        <!-- Cabeçalho do Setor (clicável para expandir/recolher) -->
        <div class="sector-block" data-setor-block>
        <div class="sector-head collapsed" onclick="toggleSetor(this)" role="button" tabindex="0">
            <div class="sector-name">
                <span class="sector-chev">▶</span>
                <div class="dot"><?= $iconeSetor ?></div>
                <div>
                    <h2><?= htmlspecialchars($setor['nome_setor']) ?></h2>
                    <div class="sector-meta">
                        <span class="core-tag"><?= ucfirst($setor['tipo_setor'] ?? 'geral') ?></span>
                        <?= $totalServicos ?> serviços · <?= $totalSops ?> SOPs gerados
                    </div>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:12px;">
                <?php if ($totalServicos > 0 && $totalSops >= $totalServicos): ?>
                    <span class="badge-status completo">✓ Completo (<?= $totalSops ?>/<?= $totalServicos ?>)</span>
                <?php elseif ($totalSops > 0): ?>
                    <span class="badge-status parcial">↗ Parcial (<?= $totalSops ?>/<?= $totalServicos ?>)</span>
                <?php else: ?>
                    <span class="badge-status pendente">○ Pendente</span>
                <?php endif; ?>
                <button class="add-servico" onclick="event.stopPropagation(); abrirModalAddServico(<?= $setor['setor_id'] ?>, '<?= htmlspecialchars($setor['nome_setor'], ENT_QUOTES) ?>')">➕ Adicionar serviço</button>
            </div>
        </div>

        <!-- Faixas (lanes) por subcategoria -->
        <div class="lanes-wrap" hidden>
            <?php if (!empty($servicos)): ?>
                <?php foreach ($porSub as $subcategoria => $itens): ?>
                <div class="lane">
                    <div class="lane-head">
                        <div class="lane-title">
                            <h3><?= htmlspecialchars($subcategoria) ?></h3>
                            <span class="lane-count"><?= count($itens) ?> serviços</span>
                        </div>
                        <span class="az-tag">A → Z</span>
                    </div>
                    <div class="stack">
                        <?php foreach ($itens as $servico): ?>
                        <?php
                            $nome = $servico['nome_servico'] ?? 'Serviço sem nome';
                            $letra = strtoupper(mb_substr(trim($nome), 0, 1));
                            $temSop = ($servico['status_final'] ?? '') === 'sop_gerado';
                        ?>
                        <div class="bar">
                            <div class="letter"><?= htmlspecialchars($letra) ?></div>
                            <span class="code"><?= htmlspecialchars($servico['codigo_servico'] ?? 'N/A') ?></span>
                            <span class="bar-title" onclick="acessarServico(<?= $servico['id'] ?>)"><?= htmlspecialchars($nome) ?></span>
                            <span class="op-tag"><?= ucfirst($servico['categoria'] ?? 'geral') ?></span>
                            <?php if ($temSop): ?>
                            <span class="status pronto"><span class="ring"></span>SOP pronto</span>
                            <?php elseif (($servico['status_final'] ?? '') === 'detalhado'): ?>
                            <span class="status"><span class="ring"></span>Detalhado</span>
                            <?php else: ?>
                            <span class="status"><span class="ring"></span>Mapeado</span>
                            <?php endif; ?>
                            <div class="row-actions">
                                <?php if ($temSop): ?>
                                <button class="see-sop" onclick="acessarServico(<?= $servico['id'] ?>)">Ver SOP</button>
                                <?php else: ?>
                                <button class="gen-sop" onclick="acessarServico(<?= $servico['id'] ?>)">Gerar SOP</button>
                                <?php endif; ?>
                                <button class="icon-link" onclick="abrirModalEditServico(<?= $servico['id'] ?>, '<?= htmlspecialchars($nome, ENT_QUOTES) ?>', '<?= htmlspecialchars($servico['categoria'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($servico['criticidade'] ?? '', ENT_QUOTES) ?>')">Editar</button>
                                <button class="icon-link danger" onclick="excluirServico(<?= $servico['id'] ?>, '<?= htmlspecialchars($nome, ENT_QUOTES) ?>')">Excluir</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="lane">
                    <p class="lane-empty">Nenhum serviço neste setor. Use "Adicionar serviço" para criar.</p>
                </div>
            <?php endif; ?>
        </div>
        </div>
        <?php endforeach; ?>

        <p class="footer-note">Serviços empilhados verticalmente dentro de cada categoria, sempre em ordem alfabética (A → Z).</p>

    <?php else: ?>
    <!-- Sem estrutura/SOPs -->
    <div style="text-align:center;padding:48px 0;">
        <div style="font-size:56px;margin-bottom:12px;">📋</div>
        <h2 style="font-size:20px;font-weight:600;color:var(--sv-ink);margin:0 0 8px;">Nenhum SOP encontrado</h2>
        <p style="color:var(--sv-ink-soft);margin:0 0 22px;">Para começar a usar SOPs, primeiro você precisa gerar a estrutura organizacional.</p>
        <a href="<?= APP_URL ?>/sop?diagnostico_id=<?= $dados['diagnostico']['id'] ?>"
           style="display:inline-block;padding:12px 24px;background:var(--sv-lane);color:#fff;border-radius:10px;font-weight:600;text-decoration:none;">
            🚀 Iniciar Geração de SOPs
        </a>
    </div>
    <?php endif; ?>

</div>

<!-- Modal: Adicionar Serviço -->
<div id="modalAddServico" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 max-w-lg w-full mx-4 shadow-xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold" id="addServicoTitulo">Adicionar Serviço</h3>
            <button onclick="fecharModal('modalAddServico')" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <input type="hidden" id="add_setor_id">
        <div class="space-y-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Serviço *</label>
                <input type="text" id="add_nome" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Ex: Controle de estoque de insumos">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Subcategoria</label>
                <input type="text" id="add_subcategoria" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Ex: Personalizado" value="Personalizado">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                    <select id="add_categoria" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="operacional">Operacional</option>
                        <option value="core">Core</option>
                        <option value="estrategico">Estratégico</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Criticidade</label>
                    <select id="add_criticidade" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="media">Média</option>
                        <option value="baixa">Baixa</option>
                        <option value="alta">Alta</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="flex gap-3 mt-6">
            <button onclick="fecharModal('modalAddServico')" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg">Cancelar</button>
            <button onclick="salvarNovoServico()" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-700">Adicionar</button>
        </div>
    </div>
</div>

<!-- Modal: Editar Serviço -->
<div id="modalEditServico" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 max-w-lg w-full mx-4 shadow-xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Editar Serviço</h3>
            <button onclick="fecharModal('modalEditServico')" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <input type="hidden" id="edit_servico_id">
        <div class="space-y-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Serviço *</label>
                <input type="text" id="edit_nome" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                    <select id="edit_categoria" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="operacional">Operacional</option>
                        <option value="core">Core</option>
                        <option value="estrategico">Estratégico</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Criticidade</label>
                    <select id="edit_criticidade" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="media">Média</option>
                        <option value="baixa">Baixa</option>
                        <option value="alta">Alta</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="flex gap-3 mt-6">
            <button onclick="fecharModal('modalEditServico')" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg">Cancelar</button>
            <button onclick="salvarEdicaoServico()" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-700">Salvar</button>
        </div>
    </div>
</div>

<script>
const CSRF_TOKEN = '<?= Csrf::token() ?>';

// Expandir/recolher setor (carrega minimizado por padrão)
function toggleSetor(head) {
    const wrap = head.nextElementSibling; // .lanes-wrap
    const colapsado = head.classList.toggle('collapsed');
    if (wrap) wrap.hidden = colapsado;
}

// Acessar o serviço: abre a página de detalhes (ver/gerar SOP inline)
function acessarServico(servicoId) {
    window.location.href = '<?= APP_URL ?>/sop/ver-detalhes-servico?servico_id=' + servicoId;
}

function fecharModal(id) { document.getElementById(id).classList.add('hidden'); }

// ---- Adicionar ----
function abrirModalAddServico(setorId, nomeSetor) {
    document.getElementById('add_setor_id').value = setorId;
    document.getElementById('add_nome').value = '';
    document.getElementById('add_subcategoria').value = 'Personalizado';
    document.getElementById('addServicoTitulo').textContent = 'Adicionar Serviço — ' + nomeSetor;
    document.getElementById('modalAddServico').classList.remove('hidden');
}

async function salvarNovoServico() {
    const nome = document.getElementById('add_nome').value.trim();
    if (!nome) { alert('Informe o nome do serviço.'); return; }
    const body = new URLSearchParams({
        setor_id: document.getElementById('add_setor_id').value,
        nome_servico: nome,
        subcategoria: document.getElementById('add_subcategoria').value.trim() || 'Personalizado',
        categoria: document.getElementById('add_categoria').value,
        criticidade: document.getElementById('add_criticidade').value,
        csrf_token: CSRF_TOKEN
    });
    try {
        const r = await fetch('<?= APP_URL ?>/sop/adicionar-servico-manual', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body });
        const d = await r.json();
        if (d.sucesso) { location.reload(); } else { alert('Erro: ' + (d.erro || 'desconhecido')); }
    } catch (e) { alert('Erro de comunicação com o servidor.'); }
}

// ---- Editar ----
function abrirModalEditServico(servicoId, nome, categoria, criticidade) {
    document.getElementById('edit_servico_id').value = servicoId;
    document.getElementById('edit_nome').value = nome;
    if (categoria) document.getElementById('edit_categoria').value = categoria;
    if (criticidade) document.getElementById('edit_criticidade').value = criticidade;
    document.getElementById('modalEditServico').classList.remove('hidden');
}

async function salvarEdicaoServico() {
    const nome = document.getElementById('edit_nome').value.trim();
    if (!nome) { alert('Informe o nome do serviço.'); return; }
    const body = new URLSearchParams({
        servico_id: document.getElementById('edit_servico_id').value,
        nome_servico: nome,
        categoria: document.getElementById('edit_categoria').value,
        criticidade: document.getElementById('edit_criticidade').value,
        csrf_token: CSRF_TOKEN
    });
    try {
        const r = await fetch('<?= APP_URL ?>/sop/editar-servico-manual', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body });
        const d = await r.json();
        if (d.sucesso) { location.reload(); } else { alert('Erro: ' + (d.erro || 'desconhecido')); }
    } catch (e) { alert('Erro de comunicação com o servidor.'); }
}

// ---- Excluir ----
async function excluirServico(servicoId, nome) {
    if (!confirm('Excluir o serviço "' + nome + '"? Esta ação não pode ser desfeita.')) return;
    const body = new URLSearchParams({ servico_id: servicoId, csrf_token: CSRF_TOKEN });
    try {
        const r = await fetch('<?= APP_URL ?>/sop/excluir-servico', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body });
        const d = await r.json();
        if (d.sucesso) { location.reload(); } else { alert('Erro: ' + (d.erro || 'desconhecido')); }
    } catch (e) { alert('Erro de comunicação com o servidor.'); }
}
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
