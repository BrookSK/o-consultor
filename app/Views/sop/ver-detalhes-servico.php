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

/**
 * Renderiza um texto respeitando enumerações. Se o texto contiver itens
 * numerados inline (ex.: "1. ... 2. ... 3. ..."), converte em <ol> com <li>.
 * Caso contrário, devolve o texto com quebras de linha (nl2br).
 */
if (!function_exists('sopRenderTexto')) {
    function sopRenderTexto($valor): string {
        $texto = trim(sopTexto($valor));
        if ($texto === '') return '';

        // Detecta ao menos "1." e "2." para considerar como lista numerada.
        if (preg_match('/(^|\s)1[\.\)]\s+.*\s2[\.\)]\s+/s', $texto)) {
            // Quebra antes de cada "N." / "N)" (número seguido de . ou ) e espaço)
            $normalizado = preg_replace('/\s*(?<![\d])(\d{1,2})[\.\)]\s+/u', "\n$1. ", $texto);
            $linhas = preg_split('/\n+/', trim($normalizado));

            $itens = [];
            $introducao = '';
            $fecho = '';
            foreach ($linhas as $linha) {
                $linha = trim($linha);
                if ($linha === '') continue;
                if (preg_match('/^\d{1,2}\.\s+(.*)$/s', $linha, $m)) {
                    $itens[] = $m[1];
                } elseif (empty($itens)) {
                    $introducao .= ($introducao ? ' ' : '') . $linha;
                } else {
                    // texto após o último item numerado (ex.: "O resultado esperado...")
                    $fecho .= ($fecho ? ' ' : '') . $linha;
                }
            }

            if (count($itens) >= 2) {
                $html = '';
                if ($introducao !== '') {
                    $html .= '<p style="margin:0 0 8px;">' . nl2br(htmlspecialchars($introducao)) . '</p>';
                }
                $html .= '<ol class="sop-ol">';
                foreach ($itens as $it) {
                    $html .= '<li>' . nl2br(htmlspecialchars(trim($it))) . '</li>';
                }
                $html .= '</ol>';
                if ($fecho !== '') {
                    $html .= '<p style="margin:8px 0 0;">' . nl2br(htmlspecialchars($fecho)) . '</p>';
                }
                return $html;
            }
        }

        return nl2br(htmlspecialchars($texto));
    }
}

/**
 * Retorna true se o campo tem conteúdo real para exibir.
 * Trata como VAZIO: null, string vazia, só espaços, aspas literais ("\"\"", "''")
 * e arrays vazios — casos que passaram a ocorrer com as regras de não-repetição
 * dos SOPs (campos como scripts_operacionais_completos vindo em branco).
 */
if (!function_exists('sopTemConteudo')) {
    function sopTemConteudo($valor): bool {
        $texto = trim(sopTexto($valor));
        // remove aspas/apóstrofos e espaços das bordas (ex.: a IA às vezes devolve "\"\"")
        $texto = trim($texto, "\"' \t\n\r");
        return $texto !== '';
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
#sop-detail-view .exec-summary{background:var(--sd-accent-soft);border:1px solid var(--sd-accent);border-left:4px solid var(--sd-accent);border-radius:14px;padding:18px 22px;margin-bottom:18px;}
#sop-detail-view .exec-head{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:var(--sd-accent-deep);margin-bottom:12px;}
#sop-detail-view .exec-ic{font-size:14px;}
#sop-detail-view .exec-list{list-style:none;counter-reset:exec;margin:0;padding:0;display:flex;flex-direction:column;gap:9px;}
#sop-detail-view .exec-list li{counter-increment:exec;position:relative;padding-left:34px;font-size:14px;line-height:1.5;color:var(--sd-ink);font-weight:500;}
#sop-detail-view .exec-list li::before{content:counter(exec);position:absolute;left:0;top:-1px;width:22px;height:22px;border-radius:6px;background:var(--sd-accent);color:#fff;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;}
#sop-detail-view .exec-list li.exec-result{font-weight:700;color:var(--sd-ok-deep,#1F7A34);}
#sop-detail-view .exec-list li.exec-result::before{content:'✓';background:var(--sd-ok);}
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
#sop-detail-view .step-title{font-weight:600;font-size:13.5px;flex:1;}
#sop-detail-view .btn-ajuste-passo{margin-left:auto;font-size:11.5px;font-weight:600;color:var(--sd-accent);background:#fff;border:1px solid var(--sd-line);border-radius:7px;padding:4px 9px;cursor:pointer;white-space:nowrap;}
#sop-detail-view .btn-ajuste-passo:hover{border-color:var(--sd-accent);background:#FFF6F0;}
#sop-detail-view .step-body{padding:16px;display:flex;flex-direction:column;gap:14px;}
#sop-detail-view .sub{display:flex;flex-direction:column;gap:6px;}
#sop-detail-view .sub-label{display:flex;align-items:center;gap:7px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:var(--sd-ink-mute);}
#sop-detail-view .sub-label .dot{width:6px;height:6px;border-radius:50%;background:var(--sd-accent);}
#sop-detail-view .sub-text{font-size:13px;line-height:1.65;color:var(--sd-ink-soft);margin:0;}
#sop-detail-view .sop-ol{margin:4px 0 0;padding-left:22px;display:flex;flex-direction:column;gap:7px;}
#sop-detail-view .sop-ol li{font-size:13px;line-height:1.6;color:var(--sd-ink-soft);}
#sop-detail-view .sop-ol li::marker{color:var(--sd-accent);font-weight:700;}
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

/* ===== Impressão / Exportar PDF ===== */
@media print {
    /* Esconde tudo que não é o SOP: sidebar, topbar, botões, modais, toasts */
    body * { visibility: hidden !important; }
    #sop-detail-view, #sop-detail-view * { visibility: visible !important; }
    #sop-detail-view {
        position: absolute; left: 0; top: 0; width: 100%;
        padding: 0 !important; margin: 0 !important;
    }
    /* Layout: remover deslocamento da sidebar e paddings do main */
    header, aside, nav, .no-print, #modalPersonalizar, #modalLoading, .actions-row,
    #sop-detail-view .actions-row, #toast-container { display: none !important; }
    main, main > div { margin: 0 !important; padding: 0 !important; }

    /* Abrir todos os accordions e mostrar seu conteúdo */
    #sop-detail-view details { display: block !important; }
    #sop-detail-view details > *:not(summary) { display: block !important; }
    #sop-detail-view .phase-body { display: block !important; }
    #sop-detail-view .phase-body > * { margin-bottom: 12px; }
    #sop-detail-view .chev { display: none !important; }

    /* CHAVE PARA ELIMINAR OS VÁCUOS:
       Nada de "break-inside: avoid" em blocos grandes. Se um passo/fase não cabe
       no restante da folha, o navegador o empurra inteiro e deixa um vácuo enorme.
       Deixamos TODO o conteúdo fluir continuamente entre as páginas. */
    #sop-detail-view .card,
    #sop-detail-view .phase,
    #sop-detail-view .phase-body,
    #sop-detail-view .step,
    #sop-detail-view .step-body,
    #sop-detail-view .sub,
    #sop-detail-view .scenario,
    #sop-detail-view .crit-wrap,
    #sop-detail-view .exec-summary {
        break-inside: auto !important;
        page-break-inside: auto !important;
        overflow: visible !important;
    }

    /* Só os títulos/rótulos evitam ficar sozinhos no fim da página */
    #sop-detail-view .section-title,
    #sop-detail-view .sub-label,
    #sop-detail-view .step-head,
    #sop-detail-view .phase summary { break-after: avoid; page-break-after: avoid; }

    /* Evita 1-2 linhas soltas isoladas no topo/fim de página */
    #sop-detail-view p, #sop-detail-view li { orphans: 3; widows: 3; }

    /* Compacta margens para não ampliar vácuos no papel */
    #sop-detail-view .card,
    #sop-detail-view .phase,
    #sop-detail-view .exec-summary { margin-bottom: 10px !important; }

    /* Cores/sombras mais leves para papel */
    #sop-detail-view * { box-shadow: none !important; }
    @page { margin: 12mm; }
    /* Na impressão, mostrar sempre o SOP e esconder a aba de scripts */
    #sop-detail-view #aba-sop { display: block !important; }
    #sop-detail-view #aba-scripts { display: none !important; }
}

/* ===== Abas SOP / Scripts ===== */
#sop-detail-view .sop-tabs{display:flex;gap:6px;margin:0 0 18px;border-bottom:2px solid var(--sd-line);}
#sop-detail-view .sop-tab{background:none;border:none;padding:10px 18px;font-size:14px;font-weight:600;color:var(--sd-ink-mute);cursor:pointer;border-bottom:3px solid transparent;margin-bottom:-2px;border-radius:6px 6px 0 0;transition:all .15s;}
#sop-detail-view .sop-tab:hover{color:var(--sd-ink-soft);background:var(--sd-accent-soft);}
#sop-detail-view .sop-tab.active{color:var(--sd-accent);border-bottom-color:var(--sd-accent);}

/* ===== Scripts de comunicação ===== */
#sop-detail-view .scripts-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:18px;flex-wrap:wrap;}
#sop-detail-view .scripts-title{font-size:18px;font-weight:700;color:var(--sd-ink);margin:0;}
#sop-detail-view .scripts-sub{font-size:13px;color:var(--sd-ink-mute);margin:4px 0 0;max-width:640px;}
#sop-detail-view .scripts-actions{display:flex;gap:8px;flex-wrap:wrap;}
#sop-detail-view .scripts-meta{font-size:12px;color:var(--sd-ink-mute);margin:0 0 14px;}
#sop-detail-view .script-cat{background:var(--sd-surface);border:1px solid var(--sd-line);border-radius:14px;padding:16px 18px;margin-bottom:16px;}
#sop-detail-view .script-cat-head h3{font-size:15px;font-weight:700;color:var(--sd-accent-deep);margin:0;}
#sop-detail-view .script-cat-head p{font-size:12.5px;color:var(--sd-ink-mute);margin:2px 0 12px;}
#sop-detail-view .script-msg{border:1px solid var(--sd-line);border-radius:12px;padding:12px 14px;margin-top:10px;background:var(--sd-page);}
#sop-detail-view .script-msg-head{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:6px;}
#sop-detail-view .script-msg-title{font-size:13.5px;font-weight:600;color:var(--sd-ink);}
#sop-detail-view .script-chip{display:inline-block;font-size:11px;font-weight:600;color:var(--sd-accent-deep);background:var(--sd-accent-soft);padding:2px 8px;border-radius:10px;margin-left:8px;}
#sop-detail-view .script-quando{font-size:12px;color:var(--sd-ink-soft);margin:0 0 8px;}
#sop-detail-view .script-texto{font-size:13px;line-height:1.6;color:var(--sd-ink);white-space:pre-line;background:var(--sd-surface);border-left:3px solid var(--sd-accent);border-radius:0 8px 8px 0;padding:10px 12px;}
#sop-detail-view .btn-copiar{background:var(--sd-accent);color:#fff;border:none;border-radius:8px;padding:6px 12px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;transition:background .15s;}
#sop-detail-view .btn-copiar:hover{background:var(--sd-accent-deep);}
#sop-detail-view .btn-copiar.copiado{background:var(--sd-ok);}
#sop-detail-view .scripts-empty{text-align:center;padding:48px 20px;color:var(--sd-ink-mute);}
#sop-detail-view .scripts-empty .ico{font-size:40px;margin-bottom:10px;}
#sop-detail-view .scripts-empty h3{font-size:16px;color:var(--sd-ink-soft);margin:0 0 6px;}
#sop-detail-view .scripts-empty p{font-size:13px;max-width:460px;margin:0 auto 16px;}
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
            <button class="btn" onclick="abrirPersonalizar()">🎛 Personalizar</button>
            <?php if ($temSop): ?>
            <button class="btn" onclick="abrirAjusteVoz()" title="Ajusta apenas o trecho que você citar, sem refazer o SOP">🎙 Ajuste rápido por voz</button>
            <button class="btn" onclick="processarServico(<?= $servico['id'] ?>)">↻ Regenerar SOP</button>
            <button class="btn" onclick="gerarScriptsComunicacao()">💬 Gerar scripts de comunicação</button>
            <button class="btn" onclick="imprimirSop()">🖨 Imprimir</button>
            <button class="btn primary spacer" onclick="imprimirSop()">⤓ Exportar PDF</button>
            <?php else: ?>
            <button class="btn gen spacer" onclick="processarServico(<?= $servico['id'] ?>)">⚡ Gerar SOP Completo</button>
            <?php endif; ?>
            <button class="btn danger" onclick="excluirServico()">🗑 Excluir</button>
        </div>
    </div>

<?php if ($temSop): ?>
    <!-- Abas: SOP / Scripts de comunicação -->
    <div class="sop-tabs no-print">
        <button class="sop-tab active" data-tab="sop" onclick="trocarAba('sop')">📋 SOP</button>
        <button class="sop-tab" data-tab="scripts" onclick="trocarAba('scripts')">💬 Scripts de comunicação</button>
    </div>
<?php endif; ?>

    <div id="aba-sop">

<?php if ($temSop): ?>
    <?php $data = $sopData; ?>

    <!-- Indicador de origem da personalização -->
    <?php $origem = $data['origem_personalizacao'] ?? 'padrao'; $ehGap = !empty($data['gap_identificado']); ?>
    <?php if ($origem === 'conversa'): ?>
    <div style="display:flex;gap:8px;align-items:center;margin-bottom:16px;padding:10px 14px;border-radius:10px;background:#E4F6E8;border:1px solid #B7E4C0;color:#237032;font-size:13px;">
        🎙 <span><strong>Gerado com base na sua conversa.</strong> Reflete o que você descreveu sobre a operação.
        <?php if ($ehGap): ?><span style="margin-left:6px;padding:1px 7px;border-radius:5px;background:#FEF0C7;color:#B54708;font-weight:600;font-size:11px;">gap identificado</span><?php endif; ?></span>
    </div>
    <?php elseif ($origem === 'documento'): ?>
    <div style="display:flex;gap:8px;align-items:center;margin-bottom:16px;padding:10px 14px;border-radius:10px;background:#EEF0FB;border:1px solid #C7CEF2;color:#3A3F8F;font-size:13px;">
        📎 <span><strong>Personalizado com material da empresa.</strong> Baseado no documento/descrição fornecidos.</span>
    </div>
    <?php else: ?>
    <div style="display:flex;gap:8px;align-items:center;margin-bottom:16px;padding:10px 14px;border-radius:10px;background:#F3F4F8;border:1px solid #E4E5EE;color:#565B78;font-size:13px;">
        📘 <span><strong>Boas práticas padrão do nicho.</strong> Nenhuma informação específica vinculada. Use “Personalizar” para adaptá-lo à sua realidade.</span>
    </div>
    <?php endif; ?>

    <!-- Resumo executivo assertivo (topo) -->
    <?php if (!empty($data['resumo_executivo_topicos'])): ?>
    <div class="exec-summary">
        <div class="exec-head"><span class="exec-ic">⚡</span>Resumo executivo</div>
        <ol class="exec-list">
            <?php foreach ((array) $data['resumo_executivo_topicos'] as $topico): ?>
                <?php
                    $txt = is_array($topico) ? implode(' — ', $topico) : (string) $topico;
                    $isResultado = stripos(ltrim($txt), 'resultado') === 0;
                ?>
                <li class="<?= $isResultado ? 'exec-result' : '' ?>"><?= htmlspecialchars($txt) ?></li>
            <?php endforeach; ?>
        </ol>
    </div>
    <?php endif; ?>

    <!-- Informações gerais -->
    <div class="card">
        <h2><span class="ic">◈</span>Informações gerais</h2>
        <div class="grid-2">
            <div>
                <p><b>Objetivo</b></p>
                <div><?= sopRenderTexto($data['objetivo'] ?? $servico['descricao_resumida'] ?? '—') ?></div>
            </div>
            <div>
                <p><b>Escopo</b></p>
                <div><?= sopRenderTexto($data['escopo'] ?? '—') ?></div>
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

    <!-- Gatilhos de início -->
    <?php if (!empty($data['gatilhos_inicio'])): ?>
    <div class="card">
        <h2><span class="ic">⚑</span>Quando executar (gatilhos de início)</h2>
        <ul class="checklist">
            <?php foreach ((array) $data['gatilhos_inicio'] as $gatilho): ?>
            <li><?= htmlspecialchars(is_array($gatilho) ? implode(' — ', $gatilho) : (string) $gatilho) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

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
    <div class="section-title"><span class="badge">▤</span><h2>Procedimento técnico de execução</h2></div>
    <p class="section-sub">Etapas técnicas sequenciais que guiam a execução do serviço do início ao fim, com parâmetros e critérios técnicos.</p>

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
                <div class="phase-desc"><?= sopRenderTexto($descFase) ?></div>
                <?php endif; ?>

                <?php $chavePassosFase = isset($fase['passos_operacionais_detalhados']) ? 'passos_operacionais_detalhados' : 'passos'; ?>
                <?php foreach ($passosDaFase as $pIndex => $passo): ?>
                <?php $tituloPasso = sopTexto($passo['acao_operacional'] ?? $passo['acao'] ?? 'Etapa ' . ($pIndex + 1)); ?>
                <div class="step">
                    <div class="step-head">
                        <span class="step-num"><?= $passo['passo'] ?? ($pIndex + 1) ?></span>
                        <span class="step-title"><?= htmlspecialchars($tituloPasso) ?></span>
                        <?php if ($temSop): ?>
                        <button class="btn-ajuste-passo no-print"
                                title="Ajustar apenas este passo por voz"
                                onclick='abrirAjusteVoz(["procedimentos", <?= (int) $faseIndex ?>, <?= json_encode($chavePassosFase) ?>, <?= (int) $pIndex ?>], <?= json_encode("Passo " . ($passo["passo"] ?? ($pIndex + 1)) . ": " . mb_substr($tituloPasso, 0, 60), JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>🎙 Ajustar</button>
                        <?php endif; ?>
                    </div>
                    <div class="step-body">
                        <?php $detalhamento = $passo['detalhamento_operacional_completo'] ?? $passo['detalhamento'] ?? ''; ?>
                        <?php if (!empty($detalhamento)): ?>
                        <div class="sub">
                            <div class="sub-label"><span class="dot"></span>Detalhamento operacional</div>
                            <div class="sub-text"><?= sopRenderTexto($detalhamento) ?></div>
                        </div>
                        <?php endif; ?>

                        <?php $scripts = $passo['scripts_operacionais_completos'] ?? $passo['scripts_modelos'] ?? ''; ?>
                        <?php if (sopTemConteudo($scripts)): ?>
                        <div class="sub">
                            <div class="sub-label"><span class="dot"></span>Script operacional</div>
                            <div class="script-block"><?= sopRenderTexto($scripts) ?></div>
                        </div>
                        <?php endif; ?>

                        <?php $metodologias = $passo['metodologias_operacionais'] ?? $passo['tecnicas_avancadas'] ?? ''; ?>
                        <?php if (sopTemConteudo($metodologias)): ?>
                        <div class="sub">
                            <div class="sub-label"><span class="dot"></span>Metodologias operacionais</div>
                            <div class="sub-text"><?= sopRenderTexto($metodologias) ?></div>
                        </div>
                        <?php endif; ?>

                        <?php $validacoes = $passo['validacoes_operacionais'] ?? $passo['situacoes_especiais'] ?? ''; ?>
                        <?php if (sopTemConteudo($validacoes)): ?>
                        <div class="sub">
                            <div class="sub-label"><span class="dot"></span>Validações operacionais</div>
                            <div class="sub-text"><?= sopRenderTexto($validacoes) ?></div>
                        </div>
                        <?php endif; ?>

                        <?php $ferramentas = $passo['ferramentas_operacionais'] ?? ''; ?>
                        <?php if (sopTemConteudo($ferramentas)): ?>
                        <div class="sub">
                            <div class="sub-label"><span class="dot"></span>Ferramentas operacionais</div>
                            <div class="sub-text"><?= sopRenderTexto($ferramentas) ?></div>
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
                        <?php if (sopTemConteudo($observacoes)): ?>
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
    <div class="section-title crit"><span class="badge">⚠</span><h2>Adversidades técnicas e problemas de execução</h2></div>
    <p class="section-sub">Problemas técnicos comuns durante o serviço, fornecedores/terceiros que falham e como resolver ou contornar cada situação.</p>

    <div class="crit-wrap">
        <div class="crit-banner">⚠ Adversidades técnicas e planos de contingência</div>
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

            <div class="contain-note"><b>Ponto de contenção:</b> registre toda adversidade técnica e a solução aplicada no sistema de gestão antes de encerrar o serviço, mesmo quando resolvida na hora — isso alimenta a melhoria contínua e evita retrabalho futuro.</div>
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
    </div><!-- /#aba-sop -->

<?php if ($temSop): ?>
    <!-- Aba: Scripts de comunicação -->
    <div id="aba-scripts" class="hidden">
        <div class="scripts-head">
            <div>
                <h2 class="scripts-title">💬 Scripts de comunicação</h2>
                <p class="scripts-sub">Mensagens e diálogos modelo, prontos para copiar, cobrindo as situações de comunicação deste serviço.</p>
            </div>
            <div class="scripts-actions">
                <button class="btn" onclick="abrirPersonalizarScripts()">🎛 Personalizar</button>
                <button class="btn primary" onclick="gerarScriptsComunicacao()" id="btnRegerarScripts">↻ Regenerar</button>
            </div>
        </div>
        <div id="scriptsContainer">
            <?php $scriptsCom = $dados['scripts_comunicacao'] ?? null; ?>
            <?php if (!empty($scriptsCom['categorias'])): ?>
                <p class="scripts-meta">Gerado em <?= htmlspecialchars($scriptsCom['gerado_em'] ?? '') ?></p>
                <?php foreach ((array) $scriptsCom['categorias'] as $cat): ?>
                <div class="script-cat">
                    <div class="script-cat-head">
                        <h3><?= htmlspecialchars(sopTexto($cat['categoria'] ?? 'Categoria')) ?></h3>
                        <?php if (!empty($cat['descricao'])): ?><p><?= htmlspecialchars(sopTexto($cat['descricao'])) ?></p><?php endif; ?>
                    </div>
                    <?php foreach ((array) ($cat['mensagens'] ?? []) as $msg): ?>
                    <div class="script-msg">
                        <div class="script-msg-head">
                            <div>
                                <span class="script-msg-title"><?= htmlspecialchars(sopTexto($msg['titulo'] ?? 'Mensagem')) ?></span>
                                <?php if (!empty($msg['canal'])): ?><span class="script-chip"><?= htmlspecialchars(sopTexto($msg['canal'])) ?></span><?php endif; ?>
                            </div>
                            <button class="btn-copiar" onclick="copiarScript(this)">📋 Copiar</button>
                        </div>
                        <?php if (!empty($msg['quando_usar'])): ?>
                        <p class="script-quando"><b>Quando usar:</b> <?= htmlspecialchars(sopTexto($msg['quando_usar'])) ?></p>
                        <?php endif; ?>
                        <div class="script-texto"><?= nl2br(htmlspecialchars(sopTexto($msg['mensagem'] ?? ''))) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="scripts-empty">
                    <div class="ico">💬</div>
                    <h3>Nenhum script de comunicação gerado ainda</h3>
                    <p>Clique em "Gerar scripts de comunicação" para que a IA crie mensagens e diálogos modelo cobrindo as situações deste serviço.</p>
                    <button class="btn primary" style="margin:0 auto;" onclick="gerarScriptsComunicacao()">💬 Gerar scripts de comunicação</button>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

</div>

<!-- Modal Personalizar Serviço -->
<div id="modalPersonalizar" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 max-w-lg w-full mx-4 shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-1">
            <h3 class="text-lg font-semibold">🎛 Personalizar Serviço</h3>
            <button onclick="fecharPersonalizar()" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <p class="text-sm text-gray-500 mb-4">Descreva as particularidades e/ou anexe um documento. Ao salvar, o SOP será regenerado consolidando o padrão do serviço com estas informações.</p>

        <form id="formPersonalizar">
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição / instruções específicas</label>
                    <textarea name="descricao" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary"
                              placeholder="Ex.: como este serviço é feito na sua empresa, ferramentas, etapas próprias, critérios..."><?= htmlspecialchars($servico['descricao_resumida'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Documento de apoio (opcional)</label>
                    <input type="file" name="documento" id="perso_documento"
                           accept=".pdf,.doc,.docx,.txt,.md,.rtf,.csv,.html"
                           class="w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary file:text-white file:cursor-pointer hover:file:bg-primary-700 border border-gray-300 rounded-lg p-1">
                    <p class="text-xs text-gray-400 mt-1">PDF, DOC, DOCX, TXT, MD, RTF, CSV ou HTML • até 1GB. O arquivo é comprimido automaticamente no envio (sem perder informação) e a IA lê o conteúdo como base real do serviço.</p>
                    <?php if (!empty($servico['documento_personalizacao_nome'])): ?>
                    <p class="text-xs text-green-600 mt-1">📎 Documento atual: <?= htmlspecialchars($servico['documento_personalizacao_nome']) ?> (envie outro para substituir)</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button type="button" onclick="fecharPersonalizar()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancelar</button>
                <button type="button" id="btnSalvarPersonalizar" onclick="salvarPersonalizacao()" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-700">Salvar e regenerar SOP</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Personalizar Scripts de Comunicação -->
<div id="modalPersonalizarScripts" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 max-w-lg w-full mx-4 shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-1">
            <h3 class="text-lg font-semibold">🎛 Personalizar comunicação</h3>
            <button onclick="fecharPersonalizarScripts()" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <p class="text-sm text-gray-500 mb-4">Defina o tom de voz, saudações, assinatura e regras de comunicação da empresa. Ao aplicar, os scripts são regenerados alinhados a estas diretrizes.</p>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Diretrizes de comunicação</label>
                <textarea id="scripts_instrucao" rows="5"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-primary"
                          placeholder="Ex.: tom cordial e próximo; sempre chamar o cliente pelo primeiro nome; assinar como 'Equipe [Empresa]'; usar 'você' e não 'senhor'; evitar gírias; incluir link de agendamento quando fizer sentido."><?= htmlspecialchars($dados['scripts_comunicacao']['instrucao_personalizacao'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="flex gap-3 mt-6">
            <button type="button" onclick="fecharPersonalizarScripts()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancelar</button>
            <button type="button" id="btnAplicarScripts" onclick="aplicarPersonalizarScripts()" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-700">Aplicar e regenerar</button>
        </div>
    </div>
</div>

<!-- Modal Ajuste rápido por voz (patch incremental — NÃO refaz o SOP inteiro) -->
<div id="modalAjusteVoz" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 max-w-lg w-full mx-4 shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-1">
            <h3 class="text-lg font-semibold" id="ajuste_titulo">🎙 Ajuste rápido por voz</h3>
            <button onclick="fecharAjusteVoz()" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <p class="text-sm text-gray-500 mb-4" id="ajuste_dica">Diga só o que quer mudar (ex.: "no passo de aprovação, quem aprova é o gerente financeiro"). A IA ajusta <strong>apenas o trecho citado</strong> e mantém o resto do SOP — sem refazer tudo.</p>

        <div class="relative">
            <textarea id="ajuste_texto" rows="5"
                      class="w-full px-3 py-2 pr-12 border border-gray-300 rounded-lg focus:outline-none focus:border-primary"
                      placeholder="Ex.: Nos scripts de comunicação, use um tom mais formal. / No procedimento de execução, inclua a conferência no sistema X antes de finalizar."></textarea>
            <button type="button" id="ajuste_btn_mic" onclick="alternarGravacaoAjuste()" title="Gravar por voz"
                    class="absolute right-2 bottom-2 w-9 h-9 rounded-full bg-primary text-white flex items-center justify-center hover:bg-primary-700">🎤</button>
        </div>
        <p id="ajuste_status" class="text-xs text-gray-400 mt-1 hidden"></p>

        <div class="flex gap-3 mt-6">
            <button type="button" onclick="fecharAjusteVoz()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancelar</button>
            <button type="button" id="ajuste_btn_aplicar" onclick="aplicarAjusteVoz()" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-700">✏️ Aplicar ajuste</button>
        </div>
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
        <p class="text-xs text-gray-400 mb-5" id="loadingEtapa">Etapa 0 de 8</p>
        <button type="button" id="btnCancelarGeracao" onclick="cancelarGeracaoSop()"
                class="px-5 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm hover:bg-gray-50">
            Cancelar
        </button>
    </div>
</div>

<script>
// Imprimir/Exportar PDF: abre todos os accordions antes de imprimir e restaura depois.
function imprimirSop() {
    const abertos = [];
    document.querySelectorAll('#sop-detail-view details').forEach(d => {
        abertos.push([d, d.open]);
        d.open = true;
    });
    const restaurar = () => { abertos.forEach(([d, estava]) => { d.open = estava; }); window.removeEventListener('afterprint', restaurar); };
    window.addEventListener('afterprint', restaurar);
    setTimeout(() => window.print(), 150);
}

// Abrir/fechar modal de personalização
function abrirPersonalizar() { document.getElementById('modalPersonalizar').classList.remove('hidden'); }
function fecharPersonalizar() { document.getElementById('modalPersonalizar').classList.add('hidden'); }

// Comprime um arquivo com gzip (lossless) usando a API nativa CompressionStream.
// Retorna um Blob comprimido, ou null se o navegador não suportar.
async function comprimirArquivoGzip(file) {
    if (typeof CompressionStream === 'undefined' || !file.stream) return null;
    try {
        const streamComprimido = file.stream().pipeThrough(new CompressionStream('gzip'));
        const blob = await new Response(streamComprimido).blob();
        // Só usa o comprimido se realmente reduziu o tamanho.
        return blob.size < file.size ? blob : null;
    } catch (e) {
        return null;
    }
}

// Salvar personalização: envia dados + documento, e ao concluir regenera o SOP
// automaticamente (mesmo fluxo de fila/polling), recarregando esta página no fim.
async function salvarPersonalizacao() {
    const form = document.getElementById('formPersonalizar');
    const formData = new FormData(form);
    const btn = document.getElementById('btnSalvarPersonalizar');
    const fileInput = document.getElementById('perso_documento');
    const temArquivo = fileInput && fileInput.files && fileInput.files.length > 0;

    btn.disabled = true;
    btn.textContent = temArquivo ? 'Comprimindo documento...' : 'Salvando...';

    // Compressão lossless (gzip) no navegador para reduzir o peso do upload.
    // Nenhuma informação é perdida — o servidor descomprime antes de ler.
    if (temArquivo) {
        try {
            const original = fileInput.files[0];
            const comprimido = await comprimirArquivoGzip(original);
            if (comprimido) {
                formData.delete('documento');
                formData.append('documento', comprimido, original.name + '.gz');
                formData.append('documento_gzip', '1');
                formData.append('documento_nome', original.name);
                formData.append('documento_tamanho_original', String(original.size));
            }
            btn.textContent = 'Enviando e lendo documento...';
        } catch (e) {
            // Se o navegador não suportar compressão, envia o arquivo original.
            console.warn('Compressão indisponível, enviando original:', e);
            btn.textContent = 'Enviando e lendo documento...';
        }
    }

    try {
        const response = await fetch('<?= APP_URL ?>/sop/personalizar-servico', { method: 'POST', body: formData });
        const data = await response.json();

        if (!data.sucesso) {
            alert('Erro ao personalizar: ' + (data.erro || 'Erro desconhecido'));
            btn.disabled = false;
            btn.textContent = 'Salvar e regenerar SOP';
            return;
        }

        fecharPersonalizar();
        btn.disabled = false;
        btn.textContent = 'Salvar e regenerar SOP';

        // Iniciar a geração do SOP a partir do sop_id já enfileirado.
        const msg = data.documento_lido
            ? 'Documento lido. Gerando SOP com base nas suas informações...'
            : 'Gerando SOP com base nas suas informações...';
        await acompanharGeracaoSop(data.sop_id, msg);

    } catch (error) {
        alert('Erro de comunicação com o servidor.');
        btn.disabled = false;
        btn.textContent = 'Salvar e regenerar SOP';
    }
}

// Loading helpers
let geracaoCancelada = false;

function mostrarLoading(titulo, subtitulo) {
    document.getElementById('loadingTitulo').textContent = titulo;
    document.getElementById('loadingSubtitulo').textContent = subtitulo;
    document.getElementById('modalLoading').classList.remove('hidden');
}
function esconderLoading() { document.getElementById('modalLoading').classList.add('hidden'); }

// Cancela o acompanhamento da geração (interrompe o polling no navegador).
// Observação: fases já iniciadas no servidor terminam sozinhas; o cancelamento
// apenas para de acompanhar e não recarrega a página com o resultado.
function cancelarGeracaoSop() {
    if (!confirm('Deseja cancelar a geração do SOP? O que já foi gerado até aqui será mantido.')) return;
    geracaoCancelada = true;
    esconderLoading();
}
function atualizarProgresso(fase, mensagem) {
    const percentuais = { 0: 4, 1: 12, 2: 25, 3: 38, 4: 50, 5: 62, 6: 75, 7: 88, 8: 100 };
    const pct = percentuais[fase] ?? 4;
    const bar = document.getElementById('loadingBar');
    if (bar) bar.style.width = pct + '%';
    const etapaLabel = document.getElementById('loadingEtapa');
    if (etapaLabel) etapaLabel.textContent = 'Etapa ' + fase + ' de 8';
    if (mensagem) document.getElementById('loadingSubtitulo').textContent = mensagem;
}

const SERVICO_ID = <?= (int) $servico['id'] ?>;
const SOP_ID = <?= (int) ($servico['sop_id'] ?? 0) ?>;
const CSRF_TOKEN_PAGINA = '<?= Csrf::token() ?>';

// ===== Abas SOP / Scripts =====
function trocarAba(aba) {
    document.querySelectorAll('.sop-tab').forEach(t => t.classList.toggle('active', t.dataset.tab === aba));
    const abaSop = document.getElementById('aba-sop');
    const abaScripts = document.getElementById('aba-scripts');
    if (abaSop) abaSop.classList.toggle('hidden', aba !== 'sop');
    if (abaScripts) abaScripts.classList.toggle('hidden', aba !== 'scripts');
}

// ===== Scripts de comunicação =====
async function gerarScriptsComunicacao(instrucao) {
    const CSRF = '<?= Csrf::token() ?>';
    trocarAba('scripts');
    mostrarLoading('Gerando scripts de comunicação', 'A IA está criando as mensagens modelo...');
    try {
        const body = { servico_id: SERVICO_ID, csrf_token: CSRF };
        if (typeof instrucao === 'string' && instrucao.trim() !== '') body.instrucao_personalizacao = instrucao.trim();
        const resp = await fetch('<?= APP_URL ?>/sop/gerar-scripts-comunicacao', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(body)
        });
        const data = await resp.json();
        esconderLoading();
        if (!data.sucesso) { alert(data.erro || 'Falha ao gerar os scripts.'); return; }
        renderizarScripts(data.scripts);
    } catch (e) {
        esconderLoading();
        alert('Erro ao gerar os scripts de comunicação.');
    }
}

function renderizarScripts(scripts) {
    const cont = document.getElementById('scriptsContainer');
    if (!cont || !scripts || !scripts.categorias) return;
    const esc = (s) => String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    let html = '';
    if (scripts.gerado_em) html += '<p class="scripts-meta">Gerado em ' + esc(scripts.gerado_em) + '</p>';
    scripts.categorias.forEach(cat => {
        html += '<div class="script-cat"><div class="script-cat-head"><h3>' + esc(cat.categoria || 'Categoria') + '</h3>';
        if (cat.descricao) html += '<p>' + esc(cat.descricao) + '</p>';
        html += '</div>';
        (cat.mensagens || []).forEach(msg => {
            html += '<div class="script-msg"><div class="script-msg-head"><div>'
                + '<span class="script-msg-title">' + esc(msg.titulo || 'Mensagem') + '</span>'
                + (msg.canal ? '<span class="script-chip">' + esc(msg.canal) + '</span>' : '')
                + '</div><button class="btn-copiar" onclick="copiarScript(this)">📋 Copiar</button></div>';
            if (msg.quando_usar) html += '<p class="script-quando"><b>Quando usar:</b> ' + esc(msg.quando_usar) + '</p>';
            html += '<div class="script-texto">' + esc(msg.mensagem || '').replace(/\n/g, '<br>') + '</div></div>';
        });
        html += '</div>';
    });
    cont.innerHTML = html;
}

function copiarScript(btn) {
    const bloco = btn.closest('.script-msg');
    const texto = bloco ? (bloco.querySelector('.script-texto')?.innerText || '') : '';
    const finalizar = () => {
        const original = btn.innerHTML;
        btn.innerHTML = '✓ Copiado';
        btn.classList.add('copiado');
        setTimeout(() => { btn.innerHTML = original; btn.classList.remove('copiado'); }, 1800);
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(texto).then(finalizar).catch(() => fallbackCopiar(texto, finalizar));
    } else {
        fallbackCopiar(texto, finalizar);
    }
}
function fallbackCopiar(texto, cb) {
    const ta = document.createElement('textarea');
    ta.value = texto; ta.style.position = 'fixed'; ta.style.opacity = '0';
    document.body.appendChild(ta); ta.select();
    try { document.execCommand('copy'); } catch (e) {}
    document.body.removeChild(ta);
    if (cb) cb();
}

// ===== Ajuste rápido por voz (patch incremental) =====
let ajusteRecorder = null;
let ajusteChunks = [];

// Escopo opcional do ajuste: quando o usuário clica "Ajustar" num passo específico,
// guardamos o caminho (path) daquele nó para a IA agir SÓ ali.
let ajusteEscopoPath = null;

function abrirAjusteVoz(path, rotulo) {
    ajusteEscopoPath = Array.isArray(path) ? path : null;
    document.getElementById('ajuste_texto').value = '';
    const st = document.getElementById('ajuste_status');
    st.classList.add('hidden'); st.textContent = '';

    // Título/dica do modal refletem o escopo escolhido.
    const titulo = document.getElementById('ajuste_titulo');
    const dica = document.getElementById('ajuste_dica');
    if (ajusteEscopoPath) {
        if (titulo) titulo.textContent = '🎙 Ajustar: ' + (rotulo || 'trecho selecionado');
        if (dica) dica.textContent = 'Você selecionou um trecho específico. Diga só o que mudar nele — o resto do SOP não será tocado.';
    } else {
        if (titulo) titulo.textContent = '🎙 Ajuste rápido por voz';
        if (dica) dica.textContent = 'Cite o trecho que quer mudar (ex.: "no passo de envio, troque e-mail por fechar na call"). A IA ajusta apenas o ponto citado.';
    }
    document.getElementById('modalAjusteVoz').classList.remove('hidden');
}
function fecharAjusteVoz() {
    if (ajusteRecorder && ajusteRecorder.state === 'recording') { try { ajusteRecorder.stop(); } catch (e) {} }
    document.getElementById('modalAjusteVoz').classList.add('hidden');
}

async function alternarGravacaoAjuste() {
    const btn = document.getElementById('ajuste_btn_mic');
    const st = document.getElementById('ajuste_status');
    if (ajusteRecorder && ajusteRecorder.state === 'recording') { ajusteRecorder.stop(); return; }
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) { alert('Seu navegador não suporta gravação.'); return; }
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        ajusteChunks = [];
        ajusteRecorder = new MediaRecorder(stream, { mimeType: MediaRecorder.isTypeSupported('audio/webm') ? 'audio/webm' : 'audio/mp4' });
        ajusteRecorder.ondataavailable = e => { if (e.data.size > 0) ajusteChunks.push(e.data); };
        ajusteRecorder.onstop = async () => {
            stream.getTracks().forEach(t => t.stop());
            btn.textContent = '🎤'; btn.classList.remove('animate-pulse');
            st.classList.remove('hidden'); st.textContent = 'Transcrevendo áudio...';
            await transcreverAjuste();
        };
        ajusteRecorder.start();
        btn.textContent = '⏹'; btn.classList.add('animate-pulse');
        st.classList.remove('hidden'); st.textContent = '🔴 Gravando... clique para parar.';
    } catch (e) { alert('Não foi possível acessar o microfone.'); }
}

async function transcreverAjuste() {
    const st = document.getElementById('ajuste_status');
    try {
        const blob = new Blob(ajusteChunks, { type: 'audio/webm' });
        const fd = new FormData();
        fd.append('audio', blob, 'ajuste.webm');
        fd.append('csrf_token', CSRF_TOKEN_PAGINA);
        const r = await fetch('<?= APP_URL ?>/api/transcricao', { method: 'POST', headers: { 'X-CSRF-Token': CSRF_TOKEN_PAGINA }, body: fd });
        const d = await r.json();
        if (d.sucesso && d.transcricao) {
            const ta = document.getElementById('ajuste_texto');
            ta.value = (ta.value ? ta.value.trim() + '\n' : '') + d.transcricao.trim();
            st.textContent = 'Transcrição adicionada.';
            setTimeout(() => st.classList.add('hidden'), 2000);
        } else {
            st.textContent = 'Não foi possível transcrever: ' + (d.erro || 'erro');
        }
    } catch (e) { st.textContent = 'Erro ao transcrever o áudio.'; }
}

async function aplicarAjusteVoz() {
    const texto = document.getElementById('ajuste_texto').value.trim();
    if (!texto) { alert('Diga ou digite o ajuste desejado.'); return; }
    if (!SOP_ID) { alert('SOP ainda não gerado — gere o SOP antes de ajustar.'); return; }

    const btn = document.getElementById('ajuste_btn_aplicar');
    btn.disabled = true; btn.textContent = 'Aplicando...';
    try {
        const params = { sop_id: SOP_ID, transcricao: texto, csrf_token: CSRF_TOKEN_PAGINA };
        // Se o usuário escolheu um trecho específico, envia o caminho para a IA
        // agir SÓ ali (edição travada no nó selecionado).
        if (ajusteEscopoPath) { params.path = JSON.stringify(ajusteEscopoPath); }
        const body = new URLSearchParams(params);
        const r = await fetch('<?= APP_URL ?>/sop/patch-sop-voz', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
        const d = await r.json();
        if (d.sucesso) {
            fecharAjusteVoz();
            alert('✅ ' + (d.mensagem || ('Seção "' + d.secao + '" atualizada (v' + d.versao + ').')));
            window.location.reload();
        } else {
            alert('Erro: ' + (d.erro || 'desconhecido'));
        }
    } catch (e) {
        alert('Erro de comunicação com o servidor.');
    } finally {
        btn.disabled = false; btn.textContent = '✏️ Aplicar ajuste';
    }
}

// Personalizar comunicação (modal)
function abrirPersonalizarScripts() { document.getElementById('modalPersonalizarScripts').classList.remove('hidden'); }
function fecharPersonalizarScripts() { document.getElementById('modalPersonalizarScripts').classList.add('hidden'); }
function aplicarPersonalizarScripts() {
    const instrucao = document.getElementById('scripts_instrucao').value || '';
    fecharPersonalizarScripts();
    gerarScriptsComunicacao(instrucao);
}

// Gerar/Regenerar SOP — enfileira e acompanha a geração.
async function processarServico(servicoId) {
    const CSRF = '<?= Csrf::token() ?>';
    try {
        mostrarLoading('Gerando SOP Completo', 'Preparando geração...');
        atualizarProgresso(1, 'Preparando geração...');

        const respInicio = await fetch('<?= APP_URL ?>/sop/processar-servico-completo', {
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
        await acompanharGeracaoSop(dataInicio.sop_id, 'Gerando SOP...');
    } catch (error) {
        esconderLoading();
        alert('Erro de comunicação com o servidor.');
    }
}

// Acompanha (polling) a geração de um SOP JÁ enfileirado (sop_id) processando a
// fila fase a fase. Ao concluir, recarrega ESTA página para exibir o SOP inline.
async function acompanharGeracaoSop(sopId, mensagemInicial) {
    const URL_STATUS = '<?= APP_URL ?>/sop/status-servico-sop';
    const URL_PROCESSAR = '<?= APP_URL ?>/sop/processar-fila';

    const fasesTexto = {
        0: 'Preparando geração...',
        1: 'Gerando resumo e estrutura (1/8)...',
        2: 'Gerando fase: Preparação (2/8)...',
        3: 'Gerando fase: Diagnóstico e planejamento (3/8)...',
        4: 'Gerando fase: Execução inicial (4/8)...',
        5: 'Gerando fase: Execução principal (5/8)...',
        6: 'Gerando fase: Controle de qualidade (6/8)...',
        7: 'Gerando fase: Fechamento (7/8)...',
        8: 'Gerando adversidades e riscos (8/8)...'
    };

    const esperar = (ms) => new Promise(resolve => setTimeout(resolve, ms));

    async function consultarStatus() {
        try {
            const resp = await fetch(URL_STATUS + '?sop_id=' + sopId + '&_=' + Date.now());
            return await resp.json();
        } catch (e) { return null; }
    }

    geracaoCancelada = false;
    mostrarLoading('Gerando SOP Completo', mensagemInicial || 'Processando...');
    atualizarProgresso(1, mensagemInicial || 'Processando...');

    let concluido = false;
    const maxTentativas = 32;

    for (let t = 0; t < maxTentativas && !concluido; t++) {
        if (geracaoCancelada) return; // usuário cancelou o acompanhamento
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
            if (geracaoCancelada) return;
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

    if (geracaoCancelada) return;

    if (!concluido) {
        const stFinal = await consultarStatus();
        if (stFinal && stFinal.status_geracao === 'concluido') concluido = true;
    }

    if (concluido) {
        atualizarProgresso(8, 'SOP completo gerado com sucesso!');
        window.location.href = '<?= APP_URL ?>/sop/ver-detalhes-servico?servico_id=' + SERVICO_ID;
    } else {
        esconderLoading();
        alert('A geração não pôde ser concluída. Tente novamente ou verifique com o suporte.');
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
