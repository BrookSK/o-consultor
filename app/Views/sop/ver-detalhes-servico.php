<?php
$tituloPagina = 'Detalhes do Serviço: ' . htmlspecialchars($dados['servico']['nome_servico']);
$servico = $dados['servico'];
$sop = $dados['sop'] ?? null;
$sopData = $sop['conteudo_array'] ?? null;
$temSop = !empty($servico['sop_id']) && !empty($sopData);

/**
 * Converte qualquer valor (string, array, objeto) em texto seguro para exibição.
 */
if (!function_exists('sopTexto')) {
    function sopTexto($valor): string {
        if ($valor === null) return '';
        if (is_string($valor)) return $valor;
        if (is_numeric($valor) || is_bool($valor)) return (string) $valor;
        if (is_array($valor)) {
            $partes = [];
            foreach ($valor as $k => $v) {
                $item = is_array($v) ? sopTexto($v) : (string) $v;
                if (!is_int($k)) $item = ucfirst(str_replace('_', ' ', (string) $k)) . ': ' . $item;
                $partes[] = $item;
            }
            return implode("\n", $partes);
        }
        return '';
    }
}
?>
<?php ob_start(); ?>

<style>
/* ===== Detalhe do serviço + SOP (referência sop-detail) — escopo #sop-detail-view ===== */
#sop-detail-view{
    --sd-surface:#FFFFFF; --sd-ink:#1A2036; --sd-ink-soft:#565C74; --sd-ink-mute:#8A8FA3;
    --sd-line:#E3E5ED; --sd-accent:#1E3A5F; --sd-accent-soft:#E8EDF3; --sd-accent-deep:#162D4A;
    --sd-ok:#1F9254; --sd-ok-soft:#E4F6EA; --sd-crit:#D64545; --sd-crit-soft:#FCEBEB;
    --sd-crit-deep:#A32E2E; --sd-page:#F3F4F8;
    color:var(--sd-ink);
}
#sop-detail-view .crumbs{font-size:13px;color:var(--sd-ink-mute);margin-bottom:18px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;}
#sop-detail-view .crumbs a{color:var(--sd-ink-mute);text-decoration:none;}
#sop-detail-view .crumbs a:hover{color:var(--sd-accent-deep);}
#sop-detail-view .crumbs .current{color:var(--sd-ink);font-weight:500;}

#sop-detail-view .card{background:var(--sd-surface);border:1px solid var(--sd-line);border-radius:14px;padding:18px 20px;margin-bottom:18px;}
#sop-detail-view .header-card{padding:22px 26px;}
#sop-detail-view .header-top{display:flex;justify-content:space-between;gap:20px;flex-wrap:wrap;}
#sop-detail-view .eyebrow{display:flex;align-items:center;gap:8px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:12px;color:var(--sd-accent-deep);background:var(--sd-accent-soft);padding:4px 10px;border-radius:7px;width:fit-content;margin-bottom:10px;}
#sop-detail-view h1{font-size:22px;font-weight:700;margin:0 0 6px;letter-spacing:-0.01em;}
#sop-detail-view .h1-sub{font-size:13px;color:var(--sd-ink-soft);margin:0;}
#sop-detail-view .meta-row{display:flex;gap:22px;margin-top:16px;flex-wrap:wrap;}
#sop-detail-view .meta-item{font-size:12px;color:var(--sd-ink-mute);}
#sop-detail-view .meta-item strong{display:block;font-size:13.5px;color:var(--sd-ink);font-weight:500;margin-top:2px;}
#sop-detail-view .status-pill{display:flex;align-items:center;gap:6px;font-weight:600;font-size:12.5px;padding:6px 12px;border-radius:20px;height:fit-content;}
#sop-detail-view .status-pill .dot{width:7px;height:7px;border-radius:50%;}
#sop-detail-view .status-pill.ok{background:var(--sd-ok-soft);color:var(--sd-ok);}
#sop-detail-view .status-pill.ok .dot{background:var(--sd-ok);}
#sop-detail-view .status-pill.pend{background:#EEF0F5;color:var(--sd-ink-mute);}
#sop-detail-view .status-pill.pend .dot{background:var(--sd-ink-mute);}

#sop-detail-view .actions-row{display:flex;gap:8px;margin-top:18px;flex-wrap:wrap;}
#sop-detail-view .btn{font-size:12.5px;font-weight:500;padding:8px 14px;border-radius:9px;border:1px solid var(--sd-line);background:var(--sd-surface);color:var(--sd-ink-soft);cursor:pointer;display:flex;align-items:center;gap:6px;}
#sop-detail-view .btn:hover{border-color:var(--sd-accent);color:var(--sd-accent-deep);}
#sop-detail-view .btn.primary{background:var(--sd-accent);border-color:var(--sd-accent);color:#fff;}
#sop-detail-view .btn.primary:hover{background:var(--sd-accent-deep);}
#sop-detail-view .btn.gen{background:var(--sd-ok);border-color:var(--sd-ok);color:#fff;}
#sop-detail-view .btn.gen:hover{background:#1a7a44;}
#sop-detail-view .btn.danger{color:var(--sd-crit-deep);}
#sop-detail-view .btn.danger:hover{border-color:var(--sd-crit);color:var(--sd-crit-deep);}
#sop-detail-view .btn.spacer{margin-left:auto;}

#sop-detail-view .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:18px;}
#sop-detail-view .card h2{font-size:14px;font-weight:600;margin:0 0 12px;display:flex;align-items:center;gap:8px;}
#sop-detail-view .card h2 .ic{width:24px;height:24px;border-radius:7px;background:var(--sd-accent-soft);color:var(--sd-accent-deep);display:flex;align-items:center;justify-content:center;font-size:12px;}
#sop-detail-view .card p{margin:0;font-size:13px;line-height:1.6;color:var(--sd-ink-soft);}
#sop-detail-view .card p + p{margin-top:10px;}
#sop-detail-view .card p b{color:var(--sd-ink);font-weight:600;}
#sop-detail-view .checklist{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:9px;}
#sop-detail-view .checklist li{display:flex;gap:9px;font-size:13px;color:var(--sd-ink-soft);line-height:1.5;}
#sop-detail-view .checklist li::before{content:'✓';color:var(--sd-ok);font-weight:700;}
#sop-detail-view .resp-row{display:flex;gap:10px;margin-top:14px;flex-wrap:wrap;}
#sop-detail-view .resp-chip{background:var(--sd-page);border:1px solid var(--sd-line);border-radius:10px;padding:8px 12px;flex:1;min-width:150px;}
#sop-detail-view .resp-chip .role{font-size:10.5px;color:var(--sd-ink-mute);text-transform:uppercase;letter-spacing:0.05em;font-weight:600;}
#sop-detail-view .resp-chip .who{font-size:13px;font-weight:500;margin-top:2px;}
#sop-detail-view .info-line{display:flex;justify-content:space-between;gap:12px;font-size:13px;padding:6px 0;border-bottom:1px solid var(--sd-line);}
#sop-detail-view .info-line:last-child{border-bottom:none;}
#sop-detail-view .info-line .lbl{color:var(--sd-ink-mute);}
#sop-detail-view .info-line .val{color:var(--sd-ink);font-weight:500;text-align:right;}
#sop-detail-view .tag{display:inline-block;font-size:11.5px;padding:3px 9px;border-radius:7px;background:var(--sd-accent-soft);color:var(--sd-accent-deep);font-weight:500;}

#sop-detail-view .section-title{display:flex;align-items:center;gap:10px;margin:26px 0 2px;}
#sop-detail-view .section-title .badge{width:28px;height:28px;border-radius:9px;background:var(--sd-accent);color:#fff;display:flex;align-items:center;justify-content:center;font-size:14px;}
#sop-detail-view .section-title h2{font-size:17px;font-weight:700;margin:0;}
#sop-detail-view .section-title.crit .badge{background:var(--sd-crit);}
#sop-detail-view .section-sub{font-size:12.5px;color:var(--sd-ink-mute);margin:0 0 14px 38px;}

#sop-detail-view .phase{background:var(--sd-surface);border:1px solid var(--sd-line);border-radius:14px;overflow:hidden;margin-bottom:14px;}
#sop-detail-view .phase summary{list-style:none;cursor:pointer;padding:15px 20px;display:flex;align-items:center;gap:12px;}
#sop-detail-view .phase summary::-webkit-details-marker{display:none;}
#sop-detail-view .phase-num{width:26px;height:26px;border-radius:8px;background:var(--sd-accent-soft);color:var(--sd-accent-deep);font-weight:700;font-size:12px;display:flex;align-items:center;justify-content:center;}
#sop-detail-view .phase-name{font-weight:600;font-size:14.5px;flex:1;}
#sop-detail-view .phase-count{font-size:11.5px;color:var(--sd-ink-mute);background:var(--sd-page);padding:3px 9px;border-radius:20px;}
#sop-detail-view .chev{color:var(--sd-ink-mute);transition:transform .15s;font-size:11px;}
#sop-detail-view .phase[open] .chev{transform:rotate(90deg);}
#sop-detail-view .phase-body{border-top:1px solid var(--sd-line);padding:18px 20px 20px;display:flex;flex-direction:column;gap:16px;}
#sop-detail-view .phase-desc{font-size:13px;color:var(--sd-ink-soft);line-height:1.6;margin:0;}

#sop-detail-view .step{border:1px solid var(--sd-line);border-radius:12px;overflow:hidden;}
#sop-detail-view .step-head{display:flex;align-items:center;gap:10px;padding:12px 16px;background:var(--sd-page);border-bottom:1px solid var(--sd-line);}
#sop-detail-view .step-num{width:22px;height:22px;border-radius:50%;background:var(--sd-accent);color:#fff;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
#sop-detail-view .step-title{font-weight:600;font-size:13.5px;}
#sop-detail-view .step-body{padding:16px;display:flex;flex-direction:column;gap:14px;}
#sop-detail-view .sub{display:flex;flex-direction:column;gap:6px;}
#sop-detail-view .sub-label{display:flex;align-items:center;gap:7px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--sd-ink-mute);}
#sop-detail-view .sub-label .dot{width:6px;height:6px;border-radius:50%;background:var(--sd-accent);}
#sop-detail-view .sub-text{font-size:13px;line-height:1.65;color:var(--sd-ink-soft);margin:0;}
#sop-detail-view .script-block{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:12px;line-height:1.7;color:var(--sd-ink);background:var(--sd-page);border-left:3px solid var(--sd-accent);border-radius:0 8px 8px 0;padding:12px 14px;white-space:pre-line;}
#sop-detail-view .meta-strip{display:flex;gap:22px;font-size:12px;color:var(--sd-ink-mute);padding-top:10px;border-top:1px solid var(--sd-line);flex-wrap:wrap;}
#sop-detail-view .meta-strip b{color:var(--sd-ink-soft);font-weight:500;}
#sop-detail-view .note{display:flex;gap:8px;font-size:12px;background:#FCF1DD;color:#7A5209;border-radius:8px;padding:9px 12px;line-height:1.5;}

#sop-detail-view .crit-wrap{background:var(--sd-surface);border:1px solid var(--sd-crit);border-radius:14px;overflow:hidden;margin-bottom:18px;}
#sop-detail-view .crit-banner{background:var(--sd-crit-soft);color:var(--sd-crit-deep);padding:14px 20px;display:flex;align-items:center;gap:10px;font-weight:600;font-size:14px;}
#sop-detail-view .crit-body{padding:20px;display:flex;flex-direction:column;gap:14px;}
#sop-detail-view .scenario-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
#sop-detail-view .scenario{border:1px solid var(--sd-line);border-radius:12px;overflow:hidden;}
#sop-detail-view .scenario-head{display:flex;align-items:center;gap:8px;padding:10px 14px;background:var(--sd-crit-soft);flex-wrap:wrap;}
#sop-detail-view .scenario-tag{font-size:10px;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;color:var(--sd-crit-deep);background:#fff;padding:2px 8px;border-radius:6px;}
#sop-detail-view .scenario-title{font-size:13px;font-weight:600;color:var(--sd-crit-deep);}
#sop-detail-view .scenario-body{padding:12px 14px;display:flex;flex-direction:column;gap:10px;}
#sop-detail-view .scenario-row .sub-label{color:var(--sd-ink-mute);}
#sop-detail-view .scenario-row .sub-text{font-size:12.5px;}
#sop-detail-view .contain-note{background:var(--sd-crit-soft);border-radius:10px;padding:12px 14px;font-size:12.5px;color:var(--sd-crit-deep);line-height:1.6;}

#sop-detail-view .empty-sop{text-align:center;padding:44px 20px;border:1px dashed var(--sd-line);border-radius:14px;background:var(--sd-surface);}
#sop-detail-view .empty-sop .ico{font-size:44px;margin-bottom:10px;}
#sop-detail-view .empty-sop h3{font-size:17px;font-weight:600;margin:0 0 6px;}
#sop-detail-view .empty-sop p{font-size:13px;color:var(--sd-ink-soft);margin:0 0 20px;}

@media (max-width:900px){
    #sop-detail-view .grid-2,#sop-detail-view .scenario-grid{grid-template-columns:1fr;}
}
</style>

<div id="sop-detail-view">

    <!-- Breadcrumb -->
    <div class="crumbs">
        <a href="<?= APP_URL ?>/dashboard">Dashboard</a> <span>/</span>
        <?php if (!empty($servico['diagnostico_id'])): ?>
        <a href="<?= APP_URL ?>/sop/listar-por-diagnostico?diagnostico_id=<?= $servico['diagnostico_id'] ?>">SOPs Gerados</a> <span>/</span>
        <?php endif; ?>
        <span class="current"><?= htmlspecialchars($servico['nome_servico']) ?></span>
    </div>

    <!-- Header do serviço -->
    <div class="card header-card">
        <div class="header-top">
            <div>
                <div class="eyebrow"><span>#</span><?= htmlspecialchars($servico['codigo_servico']) ?></div>
                <h1><?= htmlspecialchars($servico['nome_servico']) ?></h1>
                <p class="h1-sub">
                    Setor: <?= htmlspecialchars($servico['nome_setor']) ?> · Empresa: <?= htmlspecialchars($servico['nome_empresa']) ?>
                </p>
            </div>
            <?php if ($temSop): ?>
            <div class="status-pill ok"><span class="dot"></span>SOP Gerado</div>
            <?php else: ?>
            <div class="status-pill pend"><span class="dot"></span>Aguardando geração</div>
            <?php endif; ?>
        </div>

        <div class="meta-row">
            <div class="meta-item">Categoria<strong><?= ucfirst($servico['categoria']) ?></strong></div>
            <div class="meta-item">Criticidade<strong><?= ucfirst($servico['criticidade']) ?></strong></div>
            <div class="meta-item">Frequência<strong><?= ucfirst(str_replace('_', ' ', $servico['frequencia'])) ?></strong></div>
            <?php if ($temSop && !empty($servico['sop_gerado_em'])): ?>
            <div class="meta-item">SOP gerado em<strong><?= date('d/m/Y · H:i', strtotime($servico['sop_gerado_em'])) ?></strong></div>
            <?php endif; ?>
            <div class="meta-item">ID interno<strong>#<?= $servico['id'] ?></strong></div>
        </div>

        <div class="actions-row">
            <button class="btn" onclick="editarServico()">✎ Editar serviço</button>
            <?php if ($temSop): ?>
            <button class="btn" onclick="processarServico(<?= $servico['id'] ?>)">↻ Regenerar SOP</button>
            <button class="btn" onclick="window.print()">🖨 Imprimir</button>
            <button class="btn primary spacer" onclick="window.print()">⤓ Exportar PDF</button>
            <?php else: ?>
            <button class="btn gen spacer" onclick="processarServico(<?= $servico['id'] ?>)">⚡ Gerar SOP Completo</button>
            <?php endif; ?>
            <button class="btn danger" onclick="excluirServico()">🗑 Excluir</button>
        </div>
    </div>

<?php if ($temSop): ?>
    <?php $data = $sopData; ?>

    <!-- Informações gerais -->
    <div class="card">
        <h2><span class="ic">◈</span>Informações gerais</h2>
        <div class="grid-2">
            <div>
                <p><b>Objetivo</b></p>
                <p><?= nl2br(htmlspecialchars(sopTexto($data['objetivo'] ?? $servico['descricao_resumida'] ?? '—'))) ?></p>
            </div>
            <div>
                <p><b>Escopo</b></p>
                <p><?= nl2br(htmlspecialchars(sopTexto($data['escopo'] ?? '—'))) ?></p>
            </div>
        </div>
        <?php if (!empty($data['responsaveis'])): ?>
        <div class="resp-row">
            <div class="resp-chip"><div class="role">Executor</div><div class="who"><?= htmlspecialchars(sopTexto($data['responsaveis']['executor_principal'] ?? '—')) ?></div></div>
            <div class="resp-chip"><div class="role">Supervisor</div><div class="who"><?= htmlspecialchars(sopTexto($data['responsaveis']['supervisor'] ?? '—')) ?></div></div>
            <div class="resp-chip"><div class="role">Aprovador</div><div class="who"><?= htmlspecialchars(sopTexto($data['responsaveis']['aprovador'] ?? '—')) ?></div></div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Pré-requisitos / Recursos -->
    <?php if (!empty($data['pre_requisitos']) || !empty($data['recursos_necessarios'])): ?>
    <div class="grid-2">
        <?php if (!empty($data['pre_requisitos'])): ?>
        <div class="card">
            <h2><span class="ic">☑</span>Pré-requisitos</h2>
            <ul class="checklist">
                <?php foreach ((array) $data['pre_requisitos'] as $requisito): ?>
                <li><?= htmlspecialchars(is_array($requisito) ? implode(' — ', $requisito) : (string) $requisito) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        <?php if (!empty($data['recursos_necessarios'])): ?>
        <div class="card">
            <h2><span class="ic">🛠</span>Recursos necessários</h2>
            <ul class="checklist">
                <?php foreach ((array) $data['recursos_necessarios'] as $recursos): ?>
                    <?php foreach ((array) $recursos as $recurso): ?>
                    <li><?= htmlspecialchars(is_array($recurso) ? implode(' — ', $recurso) : (string) $recurso) ?></li>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Procedimentos operacionais -->
    <?php if (!empty($data['procedimentos'])): ?>
    <div class="section-title"><span class="badge">▤</span><h2>Procedimentos operacionais</h2></div>
    <p class="section-sub">Fases sequenciais do procedimento operacional padrão.</p>

    <?php foreach ($data['procedimentos'] as $faseIndex => $fase): ?>
        <?php
            $passosDaFase = $fase['passos_operacionais_detalhados'] ?? $fase['passos'] ?? [];
            $descFase = $fase['descricao_operacional'] ?? $fase['descricao'] ?? '';
        ?>
        <details class="phase" <?= $faseIndex === 0 ? 'open' : '' ?>>
            <summary>
                <span class="phase-num"><?= $faseIndex + 1 ?></span>
                <span class="phase-name"><?= htmlspecialchars(sopTexto($fase['fase'] ?? 'Fase ' . ($faseIndex + 1))) ?></span>
                <span class="phase-count"><?= count($passosDaFase) ?> etapa<?= count($passosDaFase) === 1 ? '' : 's' ?></span>
                <span class="chev">▶</span>
            </summary>
            <div class="phase-body">
                <?php if (!empty($descFase)): ?>
                <p class="phase-desc"><?= nl2br(htmlspecialchars(sopTexto($descFase))) ?></p>
                <?php endif; ?>

                <?php foreach ($passosDaFase as $pIndex => $passo): ?>
                <div class="step">
                    <div class="step-head">
                        <span class="step-num"><?= $passo['passo'] ?? ($pIndex + 1) ?></span>
                        <span class="step-title"><?= htmlspecialchars(sopTexto($passo['acao_operacional'] ?? $passo['acao'] ?? 'Etapa ' . ($pIndex + 1))) ?></span>
                    </div>
                    <div class="step-body">
                        <?php $detalhamento = $passo['detalhamento_operacional_completo'] ?? $passo['detalhamento'] ?? ''; ?>
                        <?php if (!empty($detalhamento)): ?>
                        <div class="sub">
                            <div class="sub-label"><span class="dot"></span>Detalhamento operacional</div>
                            <p class="sub-text"><?= nl2br(htmlspecialchars(sopTexto($detalhamento))) ?></p>
                        </div>
                        <?php endif; ?>

                        <?php $scripts = $passo['scripts_operacionais_completos'] ?? $passo['scripts_modelos'] ?? ''; ?>
                        <?php if (!empty($scripts)): ?>
                        <div class="sub">
                            <div class="sub-label"><span class="dot"></span>Script operacional</div>
                            <div class="script-block"><?= htmlspecialchars(sopTexto($scripts)) ?></div>
                        </div>
                        <?php endif; ?>

                        <?php $metodologias = $passo['metodologias_operacionais'] ?? $passo['tecnicas_avancadas'] ?? ''; ?>
                        <?php if (!empty($metodologias)): ?>
                        <div class="sub">
                            <div class="sub-label"><span class="dot"></span>Metodologias operacionais</div>
                            <p class="sub-text"><?= nl2br(htmlspecialchars(sopTexto($metodologias))) ?></p>
                        </div>
                        <?php endif; ?>

                        <?php $validacoes = $passo['validacoes_operacionais'] ?? $passo['situacoes_especiais'] ?? ''; ?>
                        <?php if (!empty($validacoes)): ?>
                        <div class="sub">
                            <div class="sub-label"><span class="dot"></span>Validações operacionais</div>
                            <p class="sub-text"><?= nl2br(htmlspecialchars(sopTexto($validacoes))) ?></p>
                        </div>
                        <?php endif; ?>

                        <?php $ferramentas = $passo['ferramentas_operacionais'] ?? ''; ?>
                        <?php if (!empty($ferramentas)): ?>
                        <div class="sub">
                            <div class="sub-label"><span class="dot"></span>Ferramentas operacionais</div>
                            <p class="sub-text"><?= nl2br(htmlspecialchars(sopTexto($ferramentas))) ?></p>
                        </div>
                        <?php endif; ?>

                        <?php
                            $responsavel = sopTexto($passo['responsavel_operacional'] ?? $passo['responsavel'] ?? '');
                            $tempo = sopTexto($passo['tempo_operacional_estimado'] ?? $passo['tempo_estimado'] ?? '');
                            $qualidade = sopTexto($passo['criterios_qualidade_operacionais'] ?? $passo['criterio_qualidade'] ?? '');
                        ?>
                        <?php if ($responsavel || $tempo || $qualidade): ?>
                        <div class="meta-strip">
                            <?php if ($responsavel): ?><span><b>Responsável</b> <?= htmlspecialchars($responsavel) ?></span><?php endif; ?>
                            <?php if ($tempo): ?><span><b>Tempo estimado</b> <?= htmlspecialchars($tempo) ?></span><?php endif; ?>
                            <?php if ($qualidade): ?><span><b>Qualidade</b> <?= htmlspecialchars($qualidade) ?></span><?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <?php $observacoes = $passo['observacoes_operacionais'] ?? $passo['observacoes'] ?? ''; ?>
                        <?php if (!empty($observacoes)): ?>
                        <div class="note">⚠ <?= htmlspecialchars(sopTexto($observacoes)) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </details>
    <?php endforeach; ?>
    <?php endif; ?>

    <!-- Situações críticas -->
    <?php
        $cenarios = $data['gestao_situacoes_fora_controle']['cenarios_criticos_obrigatorios']
            ?? $data['procedimentos_emergencia']['situacoes_criticas'] ?? [];
        $scriptsDificeis = $data['gestao_situacoes_fora_controle']['scripts_situacoes_dificeis'] ?? [];
    ?>
    <?php if (!empty($cenarios) || !empty($scriptsDificeis)): ?>
    <div class="section-title crit"><span class="badge">⚠</span><h2>Gestão de situações críticas</h2></div>
    <p class="section-sub">Cenários críticos e protocolos de emergência para atendimentos fora do fluxo padrão.</p>

    <div class="crit-wrap">
        <div class="crit-banner">⚠ Cenários críticos e protocolos de emergência</div>
        <div class="crit-body">
            <?php if (!empty($cenarios)): ?>
            <div class="scenario-grid">
                <?php foreach ($cenarios as $cenario): ?>
                <div class="scenario">
                    <div class="scenario-head">
                        <span class="scenario-tag"><?= htmlspecialchars(sopTexto($cenario['tipo_crise'] ?? 'Crise')) ?></span>
                        <span class="scenario-title"><?= htmlspecialchars(sopTexto($cenario['situacao_especifica'] ?? $cenario['situacao'] ?? '')) ?></span>
                    </div>
                    <div class="scenario-body">
                        <?php $sinais = $cenario['sinais_identificacao'] ?? $cenario['sinais_alerta'] ?? ''; ?>
                        <?php if (!empty($sinais)): ?>
                        <div class="scenario-row"><div class="sub-label">Como identificar</div><p class="sub-text"><?= htmlspecialchars(sopTexto($sinais)) ?></p></div>
                        <?php endif; ?>
                        <?php $acao = $cenario['acao_imediata_contencao'] ?? $cenario['acao_imediata'] ?? ''; ?>
                        <?php if (!empty($acao)): ?>
                        <div class="scenario-row"><div class="sub-label">Ação imediata</div><p class="sub-text"><?= htmlspecialchars(sopTexto($acao)) ?></p></div>
                        <?php endif; ?>
                        <?php if (!empty($cenario['tecnicas_desescalacao'])): ?>
                        <div class="scenario-row"><div class="sub-label">Técnicas de desescalada</div><p class="sub-text"><?= htmlspecialchars(sopTexto($cenario['tecnicas_desescalacao'])) ?></p></div>
                        <?php endif; ?>
                        <?php $escalar = $cenario['quando_escalar'] ?? $cenario['quem_notificar'] ?? ''; ?>
                        <?php if (!empty($escalar)): ?>
                        <div class="scenario-row"><div class="sub-label">Quando escalar</div><p class="sub-text"><?= htmlspecialchars(sopTexto($escalar)) ?></p></div>
                        <?php endif; ?>
                        <?php if (!empty($cenario['script_comunicacao_crise'])): ?>
                        <div class="scenario-row"><div class="sub-label">Script na crise</div><div class="script-block"><?= htmlspecialchars(sopTexto($cenario['script_comunicacao_crise'])) ?></div></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($scriptsDificeis)): ?>
            <div class="scenario-grid">
                <?php foreach ((array) $scriptsDificeis as $situacao => $script): ?>
                <div class="scenario">
                    <div class="scenario-head"><span class="scenario-title"><?= htmlspecialchars(ucwords(str_replace('_', ' ', (string) $situacao))) ?></span></div>
                    <div class="scenario-body">
                        <div class="script-block"><?= htmlspecialchars(sopTexto($script)) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="contain-note"><b>Ponto de contenção:</b> registre toda escalação crítica no sistema de gestão antes de encerrar o atendimento, mesmo quando resolvida na hora.</div>
        </div>
    </div>
    <?php endif; ?>

<?php else: ?>
    <!-- Estado sem SOP: gerar diretamente aqui -->
    <div class="empty-sop">
        <div class="ico">⚡</div>
        <h3>SOP ainda não gerado para este serviço</h3>
        <p>Clique em "Gerar SOP Completo" para que a IA produza o procedimento operacional completo. Ele será exibido aqui mesmo, nesta página.</p>
        <button class="btn gen" style="margin:0 auto;" onclick="processarServico(<?= $servico['id'] ?>)">⚡ Gerar SOP Completo</button>
    </div>
<?php endif; ?>

</div>

<!-- Modal para Editar Serviço -->
<div id="modalEditarServico" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 max-w-lg w-full mx-4 shadow-xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Editar Serviço</h3>
            <button onclick="fecharModalEditar()" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>

        <form id="formEditarServico">
            <input type="hidden" name="servico_id" value="<?= $servico['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Serviço *</label>
                    <input type="text" name="nome_servico" value="<?= htmlspecialchars($servico['nome_servico']) ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary" maxlength="255" required>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                        <select name="categoria" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary">
                            <option value="core" <?= $servico['categoria'] == 'core' ? 'selected' : '' ?>>Core</option>
                            <option value="operacional" <?= $servico['categoria'] == 'operacional' ? 'selected' : '' ?>>Operacional</option>
                            <option value="estrategico" <?= $servico['categoria'] == 'estrategico' ? 'selected' : '' ?>>Estratégico</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Criticidade</label>
                        <select name="criticidade" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary">
                            <option value="baixa" <?= $servico['criticidade'] == 'baixa' ? 'selected' : '' ?>>Baixa</option>
                            <option value="media" <?= $servico['criticidade'] == 'media' ? 'selected' : '' ?>>Média</option>
                            <option value="alta" <?= $servico['criticidade'] == 'alta' ? 'selected' : '' ?>>Alta</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                    <textarea name="descricao" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary"
                              placeholder="Descreva o serviço..."><?= htmlspecialchars($servico['descricao_resumida'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button type="button" onclick="fecharModalEditar()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancelar</button>
                <button type="button" onclick="salvarEdicao()" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-700">Salvar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de Loading -->
<div id="modalLoading" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-8 text-center max-w-md w-full mx-4 shadow-xl">
        <div class="inline-block w-12 h-12 border-4 border-gray-200 border-t-primary rounded-full animate-spin mb-4"></div>
        <h3 class="text-lg font-medium text-gray-800 mb-2" id="loadingTitulo">Processando...</h3>
        <p class="text-sm text-gray-500 mb-4" id="loadingSubtitulo">Aguarde...</p>
        <div class="w-full bg-gray-200 rounded-full h-3 mb-2 overflow-hidden">
            <div id="loadingBar" class="bg-primary h-3 rounded-full transition-all duration-500 ease-out" style="width: 5%"></div>
        </div>
        <p class="text-xs text-gray-400" id="loadingEtapa">Etapa 0 de 8</p>
    </div>
</div>

<script>
// Abrir/fechar modal de edição
function editarServico() { document.getElementById('modalEditarServico').classList.remove('hidden'); }
function fecharModalEditar() { document.getElementById('modalEditarServico').classList.add('hidden'); }

// Salvar edição
async function salvarEdicao() {
    const form = document.getElementById('formEditarServico');
    const formData = new FormData(form);
    try {
        const response = await fetch('<?= APP_URL ?>/sop/editar-servico-manual', { method: 'POST', body: formData });
        const data = await response.json();
        if (data.sucesso) {
            fecharModalEditar();
            window.location.reload();
        } else {
            alert('Erro ao atualizar serviço: ' + (data.erro || 'Erro desconhecido'));
        }
    } catch (error) {
        alert('Erro de comunicação com o servidor');
    }
}

// Loading helpers
function mostrarLoading(titulo, subtitulo) {
    document.getElementById('loadingTitulo').textContent = titulo;
    document.getElementById('loadingSubtitulo').textContent = subtitulo;
    document.getElementById('modalLoading').classList.remove('hidden');
}
function esconderLoading() { document.getElementById('modalLoading').classList.add('hidden'); }
function atualizarProgresso(fase, mensagem) {
    const percentuais = { 0: 4, 1: 12, 2: 25, 3: 38, 4: 50, 5: 62, 6: 75, 7: 88, 8: 100 };
    const pct = percentuais[fase] ?? 4;
    const bar = document.getElementById('loadingBar');
    if (bar) bar.style.width = pct + '%';
    const etapaLabel = document.getElementById('loadingEtapa');
    if (etapaLabel) etapaLabel.textContent = 'Etapa ' + fase + ' de 8';
    if (mensagem) document.getElementById('loadingSubtitulo').textContent = mensagem;
}

// Gerar/Regenerar SOP — geração em background + polling. Ao concluir,
// RECARREGA esta mesma página de detalhes para exibir o SOP inline.
async function processarServico(servicoId) {
    const CSRF = '<?= Csrf::token() ?>';
    const URL_INICIAR = '<?= APP_URL ?>/sop/processar-servico-completo';
    const URL_STATUS = '<?= APP_URL ?>/sop/status-servico-sop';
    const URL_PROCESSAR = '<?= APP_URL ?>/sop/processar-fila';

    const fasesTexto = {
        0: 'Preparando geração...',
        1: 'Gerando resumo e estrutura (1/8)...',
        2: 'Gerando fase: Preparação (2/8)...',
        3: 'Gerando fase: Primeiro Contato (3/8)...',
        4: 'Gerando fase: Levantamento e Diagnóstico (4/8)...',
        5: 'Gerando fase: Execução Principal (5/8)...',
        6: 'Gerando fase: Controle e Objeções (6/8)...',
        7: 'Gerando fase: Finalização (7/8)...',
        8: 'Gerando situações críticas e riscos (8/8)...'
    };

    const esperar = (ms) => new Promise(resolve => setTimeout(resolve, ms));
    let sopId = 0;

    async function consultarStatus() {
        try {
            const resp = await fetch(URL_STATUS + '?sop_id=' + sopId + '&_=' + Date.now());
            return await resp.json();
        } catch (e) { return null; }
    }

    try {
        mostrarLoading('Gerando SOP Completo', 'Preparando geração...');
        atualizarProgresso(1, 'Preparando geração...');

        const respInicio = await fetch(URL_INICIAR, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ servico_id: servicoId, csrf_token: CSRF })
        });
        const dataInicio = await respInicio.json();
        if (!dataInicio.sucesso) {
            esconderLoading();
            alert('Erro ao iniciar: ' + (dataInicio.erro || 'Erro desconhecido'));
            return;
        }
        sopId = dataInicio.sop_id;

        let concluido = false;
        const maxTentativas = 32;

        for (let t = 0; t < maxTentativas && !concluido; t++) {
            const stAntes = await consultarStatus();
            if (stAntes && stAntes.sucesso) {
                if (stAntes.status_geracao === 'concluido') { concluido = true; break; }
                if (stAntes.status_geracao === 'erro') {
                    esconderLoading();
                    alert('Erro na geração: ' + (stAntes.mensagem || 'Erro desconhecido'));
                    return;
                }
                const proxima = (stAntes.fase_atual || 0) + 1;
                atualizarProgresso(proxima <= 8 ? proxima : 8, fasesTexto[proxima] || 'Processando...');
            }

            try {
                const respProc = await fetch(URL_PROCESSAR + '?_=' + Date.now());
                const proc = await respProc.json();
                if (proc && proc.sucesso === false) {
                    esconderLoading();
                    alert('Erro na geração: ' + (proc.erro || 'Erro desconhecido'));
                    return;
                }
                if (proc && (proc.concluido || (proc.fase && proc.fase >= 8))) { concluido = true; break; }
            } catch (e) {
                await esperar(3000);
            }
        }

        if (!concluido) {
            const stFinal = await consultarStatus();
            if (stFinal && stFinal.status_geracao === 'concluido') concluido = true;
        }

        if (concluido) {
            atualizarProgresso(8, 'SOP completo gerado com sucesso!');
            // Recarrega ESTA página de detalhes; o SOP será exibido inline.
            window.location.href = '<?= APP_URL ?>/sop/ver-detalhes-servico?servico_id=' + servicoId;
        } else {
            esconderLoading();
            alert('A geração não pôde ser concluída. Tente novamente ou verifique com o suporte.');
        }
    } catch (error) {
        esconderLoading();
        alert('Erro de comunicação com o servidor.');
    }
}

// Excluir serviço
async function excluirServico() {
    if (!confirm('Tem certeza que deseja excluir este serviço? Esta ação não pode ser desfeita.')) return;
    mostrarLoading('Excluindo Serviço', 'Aguarde...');
    try {
        const response = await fetch('<?= APP_URL ?>/sop/excluir-servico', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ servico_id: <?= $servico['id'] ?>, csrf_token: '<?= Csrf::token() ?>' })
        });
        const data = await response.json();
        esconderLoading();
        if (data.sucesso) {
            <?php if (!empty($servico['diagnostico_id'])): ?>
            window.location.href = '<?= APP_URL ?>/sop/listar-por-diagnostico?diagnostico_id=<?= $servico['diagnostico_id'] ?>';
            <?php else: ?>
            window.location.href = '<?= APP_URL ?>/sop';
            <?php endif; ?>
        } else {
            alert('Erro ao excluir: ' + (data.erro || 'Erro desconhecido'));
        }
    } catch (error) {
        esconderLoading();
        alert('Erro de comunicação com o servidor');
    }
}
</script>

<?php $conteudo = ob_get_clean(); ?>
<?php require VIEW_PATH . '/layouts/layout.php'; ?>
