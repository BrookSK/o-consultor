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
#sops-view .inativar-setor{font-size:12px;font-weight:600;color:var(--sv-ink-mute);background:#EEF0F5;border:none;padding:6px 12px;border-radius:8px;cursor:pointer;}
#sops-view .inativar-setor:hover{background:#e2e5ec;color:var(--sv-ink);}

#sops-view .lanes-wrap{background:var(--sv-surface);border:1px solid var(--sv-line);border-top:none;border-radius:0 0 16px 16px;padding:6px 0 20px;}
#sops-view .lane{padding:22px 22px 4px;border-bottom:1px solid var(--sv-line);}
#sops-view .lane:last-child{border-bottom:none;}
#sops-view .lane-head{display:flex;align-items:baseline;justify-content:space-between;margin-bottom:14px;gap:10px;}
#sops-view .lane-title{display:flex;align-items:center;gap:10px;}
#sops-view .lane-title h3{font-size:13px;font-weight:600;letter-spacing:0.06em;text-transform:uppercase;color:var(--sv-ink-soft);margin:0;}
#sops-view .lane-count{font-size:11.5px;color:var(--sv-lane-deep);background:var(--sv-lane-soft);padding:2px 8px;border-radius:20px;font-weight:600;}
#sops-view .az-tag{font-size:11px;color:var(--sv-ink-mute);letter-spacing:0.03em;}
#sops-view .stack{display:flex;flex-direction:column;gap:8px;padding-bottom:18px;}

#sops-view .bar{display:grid;grid-template-columns:18px 34px 116px 1fr auto auto auto;align-items:center;gap:14px;background:var(--sv-surface);border:1px solid var(--sv-line);border-left:3px solid var(--sv-lane);border-radius:10px;padding:11px 16px;transition:border-color .15s, background .15s;}
#sops-view .bar .sv-check{width:16px;height:16px;cursor:pointer;accent-color:var(--sv-lane);margin:0;}
#sops-view .bar.selected{border-color:var(--sv-lane);border-left-color:var(--sv-lane-deep);background:var(--sv-lane-soft);}
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

/* Abas SOPs / Setores inativos */
#sops-view .tabs{display:flex;gap:4px;border-bottom:1px solid var(--sv-line);margin-bottom:22px;}
#sops-view .tab{font-size:14px;font-weight:600;color:var(--sv-ink-mute);background:none;border:none;border-bottom:2px solid transparent;padding:10px 16px;cursor:pointer;margin-bottom:-1px;display:flex;align-items:center;gap:7px;}
#sops-view .tab:hover{color:var(--sv-ink-soft);}
#sops-view .tab.active{color:var(--sv-lane-deep);border-bottom-color:var(--sv-accent);}
#sops-view .tab .badge{font-size:11px;font-weight:700;background:var(--sv-lane-soft);color:var(--sv-lane-deep);border-radius:20px;padding:1px 8px;}
#sops-view .tab-inativos.active{color:var(--sv-accent-deep);border-bottom-color:var(--sv-accent);}
#sops-view .tabpanel{display:none;}
#sops-view .tabpanel.active{display:block;}
#sops-view .inativo-hint{background:var(--sv-accent-soft);border:1px solid #f3d9c6;color:var(--sv-accent-deep);border-radius:12px;padding:14px 18px;font-size:13.5px;margin-bottom:18px;}
#sops-view .inativo-servicos{padding:0 22px 18px;display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:8px;}
#sops-view .inativo-item{display:flex;align-items:center;gap:9px;padding:9px 12px;border:1px solid var(--sv-line);border-radius:9px;cursor:pointer;}
#sops-view .inativo-item:hover{border-color:var(--sv-lane);background:var(--sv-lane-soft);}
#sops-view .inativo-item.on{border-color:var(--sv-ok);background:var(--sv-ok-soft);}
#sops-view .inativo-item input{width:16px;height:16px;accent-color:var(--sv-ok);}
#sops-view .inativo-item .txt{font-size:13px;line-height:1.3;}
#sops-view .inativo-item .code{font-size:10px;color:var(--sv-ink-mute);font-family:ui-monospace,Menlo,monospace;}
#sops-view .inativo-actions{padding:0 22px 18px;display:flex;justify-content:flex-end;gap:8px;}
#sops-view .btn-ativar{font-size:12.5px;font-weight:600;color:#fff;background:var(--sv-ok);border:none;padding:8px 16px;border-radius:9px;cursor:pointer;}
#sops-view .btn-ativar:hover{background:var(--sv-ok-deep);}
#sops-view .btn-ativar:disabled{opacity:.5;cursor:not-allowed;}

#sops-view .bulk-bar{position:sticky;bottom:16px;z-index:20;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;background:var(--sv-surface);border:1px solid var(--sv-line);border-left:4px solid var(--sv-accent);border-radius:12px;padding:14px 20px;box-shadow:0 6px 20px rgba(23,27,51,0.12);margin-top:8px;}
#sops-view .bulk-info{font-size:13.5px;color:var(--sv-ink-soft);}
#sops-view .bulk-info strong{color:var(--sv-ink);}
#sops-view .bulk-actions{display:flex;align-items:center;gap:10px;}
#sops-view .bulk-clear{font-size:12.5px;font-weight:500;color:var(--sv-ink-soft);background:none;border:1px solid var(--sv-line);padding:8px 14px;border-radius:9px;cursor:pointer;}
#sops-view .bulk-clear:hover{border-color:var(--sv-ink-mute);color:var(--sv-ink);}
#sops-view .bulk-delete{font-size:12.5px;font-weight:600;color:#fff;background:#c0392b;border:1px solid #c0392b;padding:8px 16px;border-radius:9px;cursor:pointer;}
#sops-view .bulk-delete:hover{background:#a93226;}

@media (max-width:820px){
    #sops-view .stats{grid-template-columns:repeat(2,1fr);}
    #sops-view .bar{grid-template-columns:18px 28px 1fr auto;grid-template-areas:"chk letter code status" "chk letter title title" "chk letter tag actions";row-gap:6px;}
    #sops-view .bar .sv-check{grid-area:chk;align-self:start;margin-top:4px;}
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
        <div class="stat s2"><div class="lab">Serviços</div><div class="val" id="stat-servicos"><?= $dados['estatisticas']['total_servicos'] ?></div></div>
        <div class="stat s3"><div class="lab">SOPs gerados</div><div class="val" id="stat-sops"><?= $dados['estatisticas']['total_sops'] ?></div></div>
        <div class="stat s4"><div class="lab">Progresso</div><div class="val" id="stat-progresso"><?= $dados['estatisticas']['percentual_conclusao'] ?>%</div></div>
    </div>

    <?php $inativos = $dados['setores_inativos'] ?? []; ?>
    <!-- Abas -->
    <div class="tabs">
        <button class="tab active" data-tab="ativos" onclick="mudarAba('ativos')">
            📋 SOPs <span class="badge"><?= $dados['estatisticas']['total_setores'] ?></span>
        </button>
        <button class="tab tab-inativos" data-tab="inativos" onclick="mudarAba('inativos')">
            💤 Setores inativos <span class="badge"><?= count($inativos) ?></span>
        </button>
    </div>

    <!-- PAINEL: SOPs (setores ativos) -->
    <div class="tabpanel active" id="panel-ativos">

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
                <button class="inativar-setor" onclick="event.stopPropagation(); inativarSetor(<?= $setor['setor_id'] ?>, '<?= htmlspecialchars($setor['nome_setor'], ENT_QUOTES) ?>')" title="Inativar setor (sai da lista de SOPs)">💤 Inativar setor</button>
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
                        <div class="bar" data-servico-id="<?= $servico['id'] ?>" data-tem-sop="<?= $temSop ? '1' : '0' ?>">
                            <input type="checkbox" class="sv-check" value="<?= $servico['id'] ?>" data-nome="<?= htmlspecialchars($nome, ENT_QUOTES) ?>" onclick="event.stopPropagation()" onchange="onToggleCheck(this)">
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
                                <button class="icon-link" onclick="inativarServico(<?= $servico['id'] ?>, '<?= htmlspecialchars($nome, ENT_QUOTES) ?>')" title="Tirar da lista de SOPs (vai para Setores inativos)">Inativar</button>
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

        <!-- Barra de ações em massa -->
        <div id="bulk-bar" class="bulk-bar" hidden>
            <span class="bulk-info"><strong id="bulk-count">0</strong> serviço(s) selecionado(s)</span>
            <div class="bulk-actions">
                <button class="bulk-clear" onclick="limparSelecao()">Limpar seleção</button>
                <button class="bulk-clear" onclick="inativarSelecionados()">💤 Inativar selecionados</button>
                <button class="bulk-delete" onclick="excluirSelecionados()">🗑 Excluir selecionados</button>
            </div>
        </div>

        <p class="footer-note">Serviços empilhados verticalmente dentro de cada categoria, sempre em ordem alfabética (A → Z).</p>

    <?php elseif (empty($inativos)): ?>
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
    <?php else: ?>
    <div style="text-align:center;padding:40px 0;">
        <div style="font-size:48px;margin-bottom:10px;">💤</div>
        <h2 style="font-size:18px;font-weight:600;color:var(--sv-ink);margin:0 0 6px;">Nenhum setor ativo</h2>
        <p style="color:var(--sv-ink-soft);margin:0;">Todos os setores estão inativos. Abra a aba "Setores inativos" para ativar serviços.</p>
    </div>
    <?php endif; ?>

    </div><!-- /panel-ativos -->

    <!-- PAINEL: Setores inativos -->
    <div class="tabpanel" id="panel-inativos">
        <?php if (empty($inativos)): ?>
        <div style="text-align:center;padding:44px 0;color:var(--sv-ink-soft);">
            <div style="font-size:44px;margin-bottom:10px;">✅</div>
            <p style="margin:0;">Nenhum setor inativo. Todos os setores têm serviços selecionados.</p>
        </div>
        <?php else: ?>
        <div class="inativo-hint">
            💤 Estes setores foram deixados de fora dos SOPs (nenhum serviço selecionado). Marque os serviços desejados e clique em <b>Ativar selecionados</b> para trazê-los de volta.
        </div>

        <?php foreach ($inativos as $setorData): ?>
        <?php
            $setor = $setorData['setor'];
            $servicos = $setorData['servicos'];
            $iconeSetor = '📁';
            switch ($setor['tipo_setor'] ?? 'operacional') {
                case 'core': $iconeSetor = '⚙'; break;
                case 'apoio': $iconeSetor = '🛠'; break;
                case 'estrategico': $iconeSetor = '📋'; break;
            }
            $porSub = [];
            foreach ($servicos as $sv) { $porSub[$sv['subcategoria'] ?? 'Geral'][] = $sv; }
            ksort($porSub);
        ?>
        <div class="sector-block" data-inativo-block>
            <div class="sector-head collapsed" onclick="toggleSetor(this)" role="button" tabindex="0">
                <div class="sector-name">
                    <span class="sector-chev">▶</span>
                    <div class="dot"><?= $iconeSetor ?></div>
                    <div>
                        <h2><?= htmlspecialchars($setor['nome_setor']) ?></h2>
                        <div class="sector-meta">
                            <span class="core-tag"><?= ucfirst($setor['tipo_setor'] ?? 'geral') ?></span>
                            <?= (int) ($setor['total_disponivel'] ?? count($servicos)) ?> serviços disponíveis
                        </div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:12px;">
                    <span class="badge-status pendente">💤 Inativo</span>
                    <button class="add-servico" onclick="event.stopPropagation(); marcarTodosInativo(this, <?= $setor['setor_id'] ?>)">Selecionar todos</button>
                </div>
            </div>
            <div class="lanes-wrap" hidden>
                <div class="inativo-servicos" data-setor-inativo="<?= $setor['setor_id'] ?>">
                    <?php foreach ($servicos as $servico): ?>
                    <label class="inativo-item">
                        <input type="checkbox" class="inativo-check" value="<?= $servico['id'] ?>" onchange="onCheckInativo(this)">
                        <span class="txt">
                            <?= htmlspecialchars($servico['nome_servico'] ?? '') ?>
                            <span class="code"><?= htmlspecialchars($servico['codigo_servico'] ?? '') ?></span>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
                <div class="inativo-actions">
                    <button class="btn-ativar" data-ativar-setor="<?= $setor['setor_id'] ?>" onclick="ativarSetor(<?= $setor['setor_id'] ?>)" disabled>✓ Ativar selecionados</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div><!-- /panel-inativos -->

</div>

<!-- Modal: Adicionar Serviço (fluxo por IA) -->
<div id="modalAddServico" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 max-w-lg w-full mx-4 shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-1">
            <h3 class="text-lg font-semibold" id="addServicoTitulo">Adicionar Serviço</h3>
            <button onclick="fecharModal('modalAddServico')" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <p class="text-sm text-gray-500 mb-4">Descreva o serviço por texto ou voz, e/ou anexe um documento. A IA vai nomear, categorizar, definir a criticidade e gerar o SOP automaticamente.</p>
        <input type="hidden" id="add_setor_id">

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descrição do serviço</label>
                <div class="relative">
                    <textarea id="add_descricao" rows="5"
                              class="w-full px-3 py-2 pr-12 border border-gray-300 rounded-lg focus:outline-none focus:border-primary"
                              placeholder="Explique o que é o serviço, como é feito, por onde passa, ferramentas usadas... ou clique no microfone e fale."></textarea>
                    <button type="button" id="add_btn_mic" onclick="alternarGravacaoAdd()" title="Gravar por voz"
                            class="absolute right-2 bottom-2 w-9 h-9 rounded-full bg-primary text-white flex items-center justify-center hover:bg-primary-700">
                        🎤
                    </button>
                </div>
                <p id="add_mic_status" class="text-xs text-gray-400 mt-1 hidden"></p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Documento de apoio (opcional)</label>
                <input type="file" id="add_documento"
                       accept=".pdf,.doc,.docx,.txt,.md,.rtf,.csv,.html"
                       class="w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary file:text-white file:cursor-pointer hover:file:bg-primary-700 border border-gray-300 rounded-lg p-1">
                <p class="text-xs text-gray-400 mt-1">PDF, DOC, DOCX, TXT, MD, RTF, CSV ou HTML. A IA lê o conteúdo como base do SOP.</p>
            </div>
        </div>

        <div class="flex gap-3 mt-6">
            <button onclick="fecharModal('modalAddServico')" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg">Cancelar</button>
            <button id="add_btn_criar" onclick="criarServicoInteligente()" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-700">✨ Criar e gerar SOP</button>
        </div>
    </div>
</div>

<!-- Modal de progresso (criação + geração de SOP) -->
<div id="modalProgressoSop" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-8 text-center max-w-md w-full mx-4 shadow-xl">
        <div class="inline-block w-12 h-12 border-4 border-gray-200 border-t-primary rounded-full animate-spin mb-4"></div>
        <h3 class="text-lg font-medium text-gray-800 mb-2" id="progTitulo">Criando serviço...</h3>
        <p class="text-sm text-gray-500 mb-4" id="progSub">Analisando as informações com IA...</p>
        <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
            <div id="progBar" class="bg-primary h-3 rounded-full transition-all duration-500" style="width:8%"></div>
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

// ---- Abas SOPs / Setores inativos ----
function mudarAba(aba) {
    document.querySelectorAll('#sops-view .tab').forEach(t => t.classList.toggle('active', t.dataset.tab === aba));
    document.getElementById('panel-ativos').classList.toggle('active', aba === 'ativos');
    document.getElementById('panel-inativos').classList.toggle('active', aba === 'inativos');
}

// ---- Setores inativos: seleção e ativação ----
function onCheckInativo(cb) {
    cb.closest('.inativo-item')?.classList.toggle('on', cb.checked);
    atualizarBotaoAtivar(cb.closest('[data-setor-inativo]').dataset.setorInativo);
}

function marcarTodosInativo(btn, setorId) {
    const cont = document.querySelector('[data-setor-inativo="' + setorId + '"]');
    const checks = cont.querySelectorAll('.inativo-check');
    const marcarTodos = Array.from(checks).some(c => !c.checked);
    checks.forEach(c => { c.checked = marcarTodos; c.closest('.inativo-item')?.classList.toggle('on', marcarTodos); });
    btn.textContent = marcarTodos ? 'Limpar seleção' : 'Selecionar todos';
    atualizarBotaoAtivar(setorId);
}

function atualizarBotaoAtivar(setorId) {
    const cont = document.querySelector('[data-setor-inativo="' + setorId + '"]');
    const marcados = cont.querySelectorAll('.inativo-check:checked').length;
    const btn = document.querySelector('[data-ativar-setor="' + setorId + '"]');
    if (btn) btn.disabled = marcados === 0;
}

async function ativarSetor(setorId) {
    const cont = document.querySelector('[data-setor-inativo="' + setorId + '"]');
    const ids = Array.from(cont.querySelectorAll('.inativo-check:checked')).map(c => c.value);
    if (ids.length === 0) return;

    const btn = document.querySelector('[data-ativar-setor="' + setorId + '"]');
    btn.disabled = true; btn.textContent = 'Ativando...';
    try {
        const body = new URLSearchParams({ servico_ids: ids.join(','), csrf_token: CSRF_TOKEN });
        const r = await fetch('<?= APP_URL ?>/sop/ativar-servicos', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body });
        const d = await r.json();
        if (d.sucesso) {
            // Recarrega para o setor migrar para a aba de SOPs com os serviços ativados.
            location.reload();
        } else {
            alert('Erro: ' + (d.erro || 'desconhecido'));
            btn.disabled = false; btn.textContent = '✓ Ativar selecionados';
        }
    } catch (e) {
        alert('Erro de comunicação com o servidor.');
        btn.disabled = false; btn.textContent = '✓ Ativar selecionados';
    }
}

// ---- Seleção múltipla (persiste ao recolher, pois os checkboxes ficam no DOM) ----
const selecionados = new Map(); // id -> nome

function onToggleCheck(cb) {
    const id = cb.value;
    if (cb.checked) {
        selecionados.set(id, cb.dataset.nome || ('#' + id));
    } else {
        selecionados.delete(id);
    }
    cb.closest('.bar')?.classList.toggle('selected', cb.checked);
    atualizarBulkBar();
}

function atualizarBulkBar() {
    const bar = document.getElementById('bulk-bar');
    const count = document.getElementById('bulk-count');
    if (count) count.textContent = selecionados.size;
    if (bar) bar.hidden = selecionados.size === 0;
}

function limparSelecao() {
    selecionados.clear();
    document.querySelectorAll('#sops-view .sv-check').forEach(cb => {
        cb.checked = false;
        cb.closest('.bar')?.classList.remove('selected');
    });
    atualizarBulkBar();
}

async function excluirSelecionados() {
    const ids = Array.from(selecionados.keys());
    if (ids.length === 0) return;
    if (!confirm('Excluir ' + ids.length + ' serviço(s) selecionado(s)? Esta ação não pode ser desfeita.')) return;

    const btn = document.querySelector('#bulk-bar .bulk-delete');
    if (btn) { btn.disabled = true; btn.textContent = 'Excluindo...'; }

    try {
        const body = new URLSearchParams({ servico_ids: ids.join(','), csrf_token: CSRF_TOKEN });
        const r = await fetch('<?= APP_URL ?>/sop/excluir-servicos-lote', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body });
        const d = await r.json();
        if (d.sucesso) {
            // Remove do DOM apenas os que NÃO falharam, preservando posição/estado.
            const idsFalha = new Set((d.falhas || []).map(f => String(f.id)));
            const removidos = ids.filter(id => !idsFalha.has(String(id)));
            removerServicosDoDOM(removidos);
            if (idsFalha.size > 0) alert(idsFalha.size + ' serviço(s) não puderam ser excluídos.');
        } else {
            alert('Erro: ' + (d.erro || 'desconhecido'));
        }
    } catch (e) {
        alert('Erro de comunicação com o servidor.');
    } finally {
        if (btn) { btn.disabled = false; btn.textContent = '🗑 Excluir selecionados'; }
    }
}

// Acessar o serviço: abre a página de detalhes (ver/gerar SOP inline)
function acessarServico(servicoId) {
    window.location.href = '<?= APP_URL ?>/sop/ver-detalhes-servico?servico_id=' + servicoId;
}

function fecharModal(id) { document.getElementById(id).classList.add('hidden'); }

// ---- Adicionar (fluxo por IA) ----
function abrirModalAddServico(setorId, nomeSetor) {
    document.getElementById('add_setor_id').value = setorId;
    document.getElementById('add_descricao').value = '';
    document.getElementById('add_documento').value = '';
    const st = document.getElementById('add_mic_status');
    st.classList.add('hidden'); st.textContent = '';
    document.getElementById('addServicoTitulo').textContent = 'Adicionar Serviço — ' + nomeSetor;
    document.getElementById('modalAddServico').classList.remove('hidden');
}

// ---- Gravação de voz (transcrição via Whisper) ----
let addMediaRecorder = null;
let addAudioChunks = [];

// ---- Cronômetro regressivo de gravação (5:00 -> 0:00) ----
const REC_LIMITE_SEG = 300; // 5 minutos
function iniciarCronometro(statusEl, onFim) {
    let restante = REC_LIMITE_SEG;
    const fmt = (s) => String(Math.floor(s / 60)).padStart(1, '0') + ':' + String(s % 60).padStart(2, '0');
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

async function alternarGravacaoAdd() {
    const btn = document.getElementById('add_btn_mic');
    const status = document.getElementById('add_mic_status');

    // Parar se já estiver gravando
    if (addMediaRecorder && addMediaRecorder.state === 'recording') {
        addMediaRecorder.stop();
        return;
    }

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        alert('Seu navegador não suporta gravação de áudio.');
        return;
    }

    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        addAudioChunks = [];
        let cron = null;
        addMediaRecorder = new MediaRecorder(stream, {
            mimeType: MediaRecorder.isTypeSupported('audio/webm') ? 'audio/webm' : 'audio/mp4'
        });
        addMediaRecorder.ondataavailable = (e) => { if (e.data.size > 0) addAudioChunks.push(e.data); };
        addMediaRecorder.onstop = async () => {
            if (cron) clearInterval(cron);
            stream.getTracks().forEach(t => t.stop());
            btn.innerHTML = '🎤';
            btn.classList.remove('animate-pulse');
            status.textContent = 'Transcrevendo áudio...';
            await transcreverAudioAdd();
        };
        addMediaRecorder.start();
        btn.innerHTML = '⏹';
        btn.classList.add('animate-pulse');
        cron = iniciarCronometro(status, () => { if (addMediaRecorder && addMediaRecorder.state === 'recording') addMediaRecorder.stop(); });
    } catch (e) {
        alert('Não foi possível acessar o microfone.');
    }
}

async function tokenFrescoAdd() {
    try {
        const tr = await fetch('<?= APP_URL ?>/api/csrf-token', { headers: { 'Accept': 'application/json' } });
        if (tr.ok) { const td = await tr.json(); if (td.token) return td.token; }
    } catch (e) {}
    return CSRF_TOKEN;
}

async function transcreverAudioAdd() {
    const status = document.getElementById('add_mic_status');
    try {
        const token = await tokenFrescoAdd();
        const blob = new Blob(addAudioChunks, { type: 'audio/webm' });
        const fd = new FormData();
        fd.append('audio', blob, 'gravacao.webm');
        fd.append('csrf_token', token);
        const r = await fetch('<?= APP_URL ?>/api/transcricao', { method: 'POST', headers: { 'X-CSRF-Token': token }, body: fd });
        const d = await r.json();
        if (d.sucesso && d.transcricao) {
            const ta = document.getElementById('add_descricao');
            ta.value = (ta.value ? ta.value.trim() + '\n' : '') + d.transcricao.trim();
            status.textContent = 'Transcrição adicionada.';
            setTimeout(() => status.classList.add('hidden'), 2500);
        } else {
            status.textContent = 'Não foi possível transcrever: ' + (d.erro || 'erro');
        }
    } catch (e) {
        status.textContent = 'Erro ao transcrever o áudio.';
    }
}

// ---- Criar serviço inteligente + gerar SOP + redirecionar ----
async function criarServicoInteligente() {
    const descricao = document.getElementById('add_descricao').value.trim();
    const fileInput = document.getElementById('add_documento');
    const temArquivo = fileInput.files && fileInput.files.length > 0;

    if (!descricao && !temArquivo) {
        alert('Descreva o serviço (texto ou voz) ou anexe um documento.');
        return;
    }

    const btn = document.getElementById('add_btn_criar');
    btn.disabled = true;

    const fd = new FormData();
    fd.append('setor_id', document.getElementById('add_setor_id').value);
    fd.append('descricao', descricao);
    fd.append('csrf_token', CSRF_TOKEN);
    if (temArquivo) {
        fd.append('documento', fileInput.files[0]);
        fd.append('documento_nome', fileInput.files[0].name);
    }

    fecharModal('modalAddServico');
    mostrarProgresso('Criando serviço...', 'Analisando as informações com IA...', 10);

    try {
        const r = await fetch('<?= APP_URL ?>/sop/criar-servico-inteligente', { method: 'POST', body: fd });
        const d = await r.json();
        if (!d.sucesso) {
            esconderProgresso();
            btn.disabled = false;
            alert('Erro: ' + (d.erro || 'desconhecido'));
            return;
        }
        atualizarProgresso(25, 'Serviço "' + (d.nome_servico || '') + '" criado. Gerando SOP...');
        await acompanharGeracaoSopModal(d.sop_id, d.servico_id);
    } catch (e) {
        esconderProgresso();
        btn.disabled = false;
        alert('Erro de comunicação com o servidor.');
    }
}

function mostrarProgresso(titulo, sub, pct) {
    document.getElementById('progTitulo').textContent = titulo;
    document.getElementById('progSub').textContent = sub;
    document.getElementById('progBar').style.width = (pct || 8) + '%';
    document.getElementById('modalProgressoSop').classList.remove('hidden');
}
function atualizarProgresso(pct, sub) {
    document.getElementById('progBar').style.width = pct + '%';
    if (sub) document.getElementById('progSub').textContent = sub;
}
function esconderProgresso() { document.getElementById('modalProgressoSop').classList.add('hidden'); }

// Processa a fila fase a fase e redireciona para a página de detalhes do serviço ao concluir.
async function acompanharGeracaoSopModal(sopId, servicoId) {
    const URL_STATUS = '<?= APP_URL ?>/sop/status-servico-sop';
    const URL_PROCESSAR = '<?= APP_URL ?>/sop/processar-fila';
    const esperar = (ms) => new Promise(res => setTimeout(res, ms));
    const pctFase = { 1: 30, 2: 40, 3: 50, 4: 62, 5: 72, 6: 82, 7: 90, 8: 98 };

    async function status() {
        try { const r = await fetch(URL_STATUS + '?sop_id=' + sopId + '&_=' + Date.now()); return await r.json(); }
        catch (e) { return null; }
    }

    let concluido = false;
    for (let t = 0; t < 32 && !concluido; t++) {
        const st = await status();
        if (st && st.sucesso) {
            if (st.status_geracao === 'concluido') { concluido = true; break; }
            if (st.status_geracao === 'erro') { esconderProgresso(); alert('Erro na geração: ' + (st.mensagem || 'desconhecido')); return; }
            const prox = (st.fase_atual || 0) + 1;
            atualizarProgresso(pctFase[prox] || 30, 'Gerando SOP (etapa ' + Math.min(prox, 8) + ' de 8)...');
        }
        try {
            const rp = await fetch(URL_PROCESSAR + '?_=' + Date.now());
            const proc = await rp.json();
            if (proc && proc.sucesso === false) { esconderProgresso(); alert('Erro na geração: ' + (proc.erro || 'desconhecido')); return; }
            if (proc && (proc.concluido || (proc.fase && proc.fase >= 8))) { concluido = true; break; }
        } catch (e) { await esperar(3000); }
    }

    if (!concluido) {
        const st = await status();
        if (st && st.status_geracao === 'concluido') concluido = true;
    }

    if (concluido) {
        atualizarProgresso(100, 'SOP gerado! Redirecionando...');
        window.location.href = '<?= APP_URL ?>/sop/ver-detalhes-servico?servico_id=' + servicoId;
    } else {
        esconderProgresso();
        // Mesmo sem concluir 100% no navegador, o serviço já existe: leva para a página dele.
        window.location.href = '<?= APP_URL ?>/sop/ver-detalhes-servico?servico_id=' + servicoId;
    }
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

// ---- Inativar (tira da lista de SOPs; vai para "Setores inativos") ----
async function inativarServico(servicoId, nome) {
    if (!confirm('Inativar o serviço "' + nome + '"? Ele sai da lista de SOPs e vai para "Setores inativos" (pode reativar depois).')) return;
    try {
        const body = new URLSearchParams({ servico_ids: servicoId, csrf_token: CSRF_TOKEN });
        const r = await fetch('<?= APP_URL ?>/sop/inativar-servicos', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body });
        const d = await r.json();
        if (d.sucesso) { location.reload(); } else { alert('Erro: ' + (d.erro || 'desconhecido')); }
    } catch (e) { alert('Erro de comunicação com o servidor.'); }
}

async function inativarSetor(setorId, nome) {
    if (!confirm('Inativar o setor "' + nome + '" inteiro? Todos os serviços dele saem da lista de SOPs (vão para "Setores inativos").')) return;
    try {
        const body = new URLSearchParams({ setor_id: setorId, csrf_token: CSRF_TOKEN });
        const r = await fetch('<?= APP_URL ?>/sop/inativar-servicos', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body });
        const d = await r.json();
        if (d.sucesso) { location.reload(); } else { alert('Erro: ' + (d.erro || 'desconhecido')); }
    } catch (e) { alert('Erro de comunicação com o servidor.'); }
}

async function inativarSelecionados() {
    const ids = Array.from(selecionados.keys());
    if (ids.length === 0) return;
    if (!confirm('Inativar ' + ids.length + ' serviço(s) selecionado(s)? Eles saem da lista de SOPs e vão para "Setores inativos".')) return;
    try {
        const body = new URLSearchParams({ servico_ids: ids.join(','), csrf_token: CSRF_TOKEN });
        const r = await fetch('<?= APP_URL ?>/sop/inativar-servicos', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body });
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
        if (d.sucesso) {
            removerServicosDoDOM([String(servicoId)]);
        } else {
            alert('Erro: ' + (d.erro || 'desconhecido'));
        }
    } catch (e) { alert('Erro de comunicação com o servidor.'); }
}

// Remove as linhas dos serviços do DOM e atualiza contadores, SEM recarregar
// a página — preserva a posição de scroll e o estado (aberto/fechado) dos setores.
function removerServicosDoDOM(ids) {
    const setoresAfetados = new Set();

    ids.forEach(id => {
        selecionados.delete(String(id));
        const bar = document.querySelector('#sops-view .bar[data-servico-id="' + id + '"]');
        if (!bar) return;
        const block = bar.closest('.sector-block');
        const lane = bar.closest('.lane');
        bar.remove();
        if (block) setoresAfetados.add(block);
        // Se a subcategoria (lane) ficou vazia, remove a faixa inteira
        if (lane && lane.querySelectorAll('.bar').length === 0) lane.remove();
    });

    setoresAfetados.forEach(atualizarContadoresSetor);
    atualizarEstatisticasGlobais();
    atualizarBulkBar();
}

// Recalcula os contadores exibidos no cabeçalho de um setor a partir do DOM.
function atualizarContadoresSetor(block) {
    const bars = block.querySelectorAll('.bar');
    const total = bars.length;
    let sops = 0;
    bars.forEach(b => { if (b.dataset.temSop === '1') sops++; });

    // Atualiza contadores por subcategoria (lane-count)
    block.querySelectorAll('.lane').forEach(lane => {
        const n = lane.querySelectorAll('.bar').length;
        const c = lane.querySelector('.lane-count');
        if (c) c.textContent = n + ' serviços';
    });

    // Atualiza a meta do setor ("X serviços · Y SOPs gerados")
    const meta = block.querySelector('.sector-meta');
    if (meta) {
        const tag = meta.querySelector('.core-tag');
        const tipo = tag ? tag.textContent.trim() : '';
        meta.innerHTML = (tag ? '<span class="core-tag">' + tipo + '</span> ' : '') + total + ' serviços · ' + sops + ' SOPs gerados';
    }

    // Atualiza o badge de status do setor
    const badge = block.querySelector('.badge-status');
    if (badge) {
        badge.classList.remove('completo', 'parcial', 'pendente');
        if (total > 0 && sops >= total) {
            badge.classList.add('completo');
            badge.textContent = '✓ Completo (' + sops + '/' + total + ')';
        } else if (sops > 0) {
            badge.classList.add('parcial');
            badge.textContent = '↗ Parcial (' + sops + '/' + total + ')';
        } else {
            badge.classList.add('pendente');
            badge.textContent = '○ Pendente';
        }
    }
}

// Recalcula os cards de estatísticas globais (Serviços, SOPs, Progresso) a partir do DOM.
function atualizarEstatisticasGlobais() {
    const bars = document.querySelectorAll('#sops-view .bar');
    const totalServicos = bars.length;
    let totalSops = 0;
    bars.forEach(b => { if (b.dataset.temSop === '1') totalSops++; });
    const progresso = totalServicos > 0 ? Math.round((totalSops / totalServicos) * 1000) / 10 : 0;

    const elServ = document.getElementById('stat-servicos');
    const elSops = document.getElementById('stat-sops');
    const elProg = document.getElementById('stat-progresso');
    if (elServ) elServ.textContent = totalServicos;
    if (elSops) elSops.textContent = totalSops;
    if (elProg) elProg.textContent = progresso + '%';
}
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
