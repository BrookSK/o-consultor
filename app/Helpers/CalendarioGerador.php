<?php
/**
 * Helper CalendarioGerador — Identificação de nicho e datas relevantes (spec §6)
 *
 * Reúne o contexto do nicho (empresa + marca + diagnóstico via JornadaCliente)
 * e usa a IA para:
 *   1) sugerir datas comemorativas relevantes ao nicho/região (§6.2), e
 *   2) classificar a relevância (alta/média/baixa/não recomendada — §6.4).
 *
 * As datas identificadas são persistidas na base normalizada
 * (datas_comemorativas) para revisão/reutilização, e viram itens de
 * calendário (calendario_conteudo) aplicando a antecedência configurada.
 *
 * A identidade da marca (como falar) nunca é definida aqui — este helper só
 * cuida da FONTE/tema (sobre o que falar).
 */

class CalendarioGerador
{
    /**
     * Monta um resumo do contexto de nicho/empresa para o prompt.
     */
    public static function contextoNicho(int $empresaId): array
    {
        $ctx = ['empresa_id' => $empresaId];

        try {
            $empresa = Database::queryOne(
                "SELECT nome, segmento, lingua_principal FROM empresas WHERE id = :id",
                ['id' => $empresaId]
            );
            if ($empresa) {
                $ctx['empresa_nome'] = $empresa['nome'];
                $ctx['segmento'] = $empresa['segmento'] ?? null;
            }
        } catch (\Throwable $e) { /* opcional */ }

        try {
            $marca = Database::queryOne(
                "SELECT nicho, publico_alvo, produtos_servicos FROM marcas WHERE empresa_id = :id AND ativo = 1 LIMIT 1",
                ['id' => $empresaId]
            );
            if ($marca) {
                $ctx['nicho'] = $marca['nicho'] ?? null;
                $ctx['publico_alvo'] = $marca['publico_alvo'] ?? null;
                $ctx['produtos_servicos'] = $marca['produtos_servicos'] ?? null;
            }
        } catch (\Throwable $e) { /* opcional */ }

        // Região vem das Configurações de Conteúdo.
        $config = ConfiguracaoConteudo::obter($empresaId);
        $ctx['pais'] = $config['pais'] ?? 'Brasil';
        $ctx['estado'] = $config['estado'] ?? null;
        $ctx['cidade'] = $config['cidade'] ?? null;
        $ctx['antecedencia_dias'] = (int) ($config['antecedencia_datas_dias'] ?? 7);

        return $ctx;
    }

    /**
     * Gera/atualiza datas relevantes ao nicho via IA e as persiste na base.
     * Retorna o número de datas criadas.
     */
    public static function sugerirDatas(int $empresaId): array
    {
        if (!Configuracao::apiAtiva('openai') && !Configuracao::apiAtiva('anthropic')) {
            return ['sucesso' => false, 'erro' => 'Nenhuma API de IA ativa para identificar datas.', 'criadas' => 0];
        }

        $ctx = self::contextoNicho($empresaId);
        $ctxJson = json_encode($ctx, JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
Você é um estrategista de conteúdo. Com base no contexto da empresa abaixo, liste datas comemorativas RELEVANTES para o conteúdo dela nos próximos 12 meses. Considere datas nacionais, do nicho/setor, profissionais, sazonais e regionais (país/estado/cidade informados). NÃO liste datas irrelevantes ao negócio.

Contexto da empresa:
{$ctxJson}

Para CADA data, classifique a relevância como "alta", "media", "baixa" ou "nao_recomendada" considerando relação com o nicho, público, produtos/serviços, região e potencial editorial/comercial.

Responda em JSON válido:
{
  "datas": [
    {
      "nome": "Dia do ...",
      "tipo": "nacional|internacional|regional|estadual|municipal|profissional|comercial|sazonal|setorial|institucional",
      "mes": 1-12,
      "dia": 1-31,
      "relevancia": "alta|media|baixa|nao_recomendada",
      "nichos": ["..."],
      "antecedencia_dias": 7
    }
  ]
}
Não inclua texto fora do JSON. Máximo de 30 datas.
PROMPT;

        $res = ApiHelper::chamarAnalise($prompt, true, 3000);
        if (!$res['sucesso'] || !is_array($res['conteudo']) || empty($res['conteudo']['datas'])) {
            return ['sucesso' => false, 'erro' => 'A IA não retornou datas válidas.', 'criadas' => 0];
        }

        $criadas = 0;
        foreach ($res['conteudo']['datas'] as $d) {
            if (empty($d['nome']) || empty($d['mes']) || empty($d['dia'])) continue;

            // Evita duplicar a mesma data (nome+mes+dia) já cadastrada para a empresa.
            $existe = Database::queryOne(
                "SELECT id FROM datas_comemorativas
                 WHERE empresa_id = :eid AND nome = :nome AND mes = :mes AND dia = :dia LIMIT 1",
                ['eid' => $empresaId, 'nome' => $d['nome'], 'mes' => (int) $d['mes'], 'dia' => (int) $d['dia']]
            );
            if ($existe) continue;

            $ok = DataComemorativa::criar($empresaId, [
                'nome'              => $d['nome'],
                'tipo'              => $d['tipo'] ?? 'nacional',
                'mes'               => (int) $d['mes'],
                'dia'               => (int) $d['dia'],
                'recorrencia'       => 'anual',
                'pais'              => $ctx['pais'] ?? 'Brasil',
                'estado'            => $ctx['estado'] ?? null,
                'municipio'         => $ctx['cidade'] ?? null,
                'nichos'            => $d['nichos'] ?? null,
                'relevancia'        => $d['relevancia'] ?? 'media',
                'antecedencia_dias' => (int) ($d['antecedencia_dias'] ?? ($ctx['antecedencia_dias'] ?? 7)),
                'fonte'             => 'IA (identificação de nicho)',
            ]);
            if ($ok) $criadas++;
        }

        Logger::acao('Datas comemorativas sugeridas pela IA', ['empresa_id' => $empresaId, 'criadas' => $criadas]);
        return ['sucesso' => true, 'criadas' => $criadas];
    }

    /**
     * Geração LIVRE/SEMANAL (spec §6 "conteúdo livre ou semanal"): sugere temas
     * de conteúdo para a semana com base no nicho, respeitando a quantidade
     * configurada (qtd_sugestoes_semanais) e a anti-repetição de temas.
     * Cada tema vira um item de calendário com origem "conteudo_semanal".
     *
     * @return array {sucesso: bool, criados: int, erro?: string}
     */
    public static function gerarSemanal(int $empresaId): array
    {
        if (!Configuracao::apiAtiva('openai') && !Configuracao::apiAtiva('anthropic')) {
            return ['sucesso' => false, 'erro' => 'Nenhuma API de IA ativa para gerar sugestões semanais.', 'criados' => 0];
        }

        $config = ConfiguracaoConteudo::obter($empresaId);
        $qtd = max(1, min(14, (int) ($config['qtd_sugestoes_semanais'] ?? 3)));
        $evitarRepeticao = (int) ($config['evitar_repeticao_temas'] ?? 1) === 1;
        $periodoDias = max(1, (int) ($config['periodo_repeticao_dias'] ?? 30));

        // Temas recentes para a IA evitar repetição (spec §12.1).
        $temasRecentes = [];
        if ($evitarRepeticao) {
            try {
                $rows = Database::query(
                    "SELECT tema FROM calendario_conteudo
                     WHERE empresa_id = :e AND criado_em >= DATE_SUB(NOW(), INTERVAL :dias DAY)
                     ORDER BY criado_em DESC LIMIT 50",
                    ['e' => $empresaId, 'dias' => $periodoDias]
                );
                $temasRecentes = array_column($rows, 'tema');
            } catch (\Throwable $e) { $temasRecentes = []; }
        }

        $ctx = self::contextoNicho($empresaId);
        $ctxJson = json_encode($ctx, JSON_UNESCAPED_UNICODE);
        $evitarJson = json_encode(array_slice($temasRecentes, 0, 30), JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
Você é um estrategista de conteúdo. Sugira {$qtd} temas de publicação para a próxima semana, relevantes ao nicho da empresa abaixo. Os temas devem ser variados, úteis para o público e adequados ao negócio.

Contexto da empresa:
{$ctxJson}

NÃO repita nem crie variações próximas destes temas já usados recentemente:
{$evitarJson}

Responda em JSON válido:
{
  "temas": [
    { "tema": "...", "formato_recomendado": "carrossel|post|reels|story", "objetivo": "educar|vender|engajar|institucional" }
  ]
}
Não inclua texto fora do JSON.
PROMPT;

        $res = ApiHelper::chamarAnalise($prompt, true, 2000);
        if (!$res['sucesso'] || !is_array($res['conteudo']) || empty($res['conteudo']['temas'])) {
            return ['sucesso' => false, 'erro' => 'A IA não retornou temas válidos.', 'criados' => 0];
        }

        $criados = 0;
        $baseData = new DateTimeImmutable('today');
        $i = 0;
        foreach ($res['conteudo']['temas'] as $t) {
            $tema = trim((string) ($t['tema'] ?? ''));
            if ($tema === '') continue;

            // Distribui as publicações ao longo dos próximos dias úteis da semana.
            $dataPub = $baseData->modify('+' . (($i % 7) + 1) . ' days')->format('Y-m-d');

            $ok = CalendarioConteudo::criar($empresaId, [
                'tema'                     => $tema,
                'origem'                   => 'conteudo_semanal',
                'formato_recomendado'      => $t['formato_recomendado'] ?? null,
                'objetivo'                 => $t['objetivo'] ?? null,
                'data_publicacao_sugerida' => $dataPub,
                'status'                   => 'sugerido',
            ]);
            if ($ok) { $criados++; $i++; }
        }

        Logger::acao('Sugestões semanais geradas', ['empresa_id' => $empresaId, 'criados' => $criados]);
        return ['sucesso' => true, 'criados' => $criados];
    }

    /**
     * Popula o calendário com as próximas datas relevantes (alta/média) que
     * ainda não têm item, aplicando a antecedência para a data de publicação.
     * Retorna o número de itens criados.
     */
    public static function popularCalendario(int $empresaId, int $janelaDias = 90): array
    {
        $proximas = DataComemorativa::proximas($empresaId, $janelaDias, false);
        $criados = 0;

        foreach ($proximas as $data) {
            if (CalendarioConteudo::existeParaData($empresaId, (int) $data['id'])) continue;

            $antecedencia = (int) ($data['antecedencia_dias'] ?? 7);
            $ok = CalendarioConteudo::criar($empresaId, [
                'tema'                 => $data['nome'],
                'origem'               => 'data_comemorativa',
                'data_comemorativa_id' => (int) $data['id'],
                'data_evento'          => $data['proxima_ocorrencia'] ?? null,
                'antecedencia_dias'    => $antecedencia,
                'status'               => 'sugerido',
            ]);
            if ($ok) $criados++;
        }

        return ['sucesso' => true, 'criados' => $criados];
    }
}
