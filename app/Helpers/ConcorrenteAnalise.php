<?php
/**
 * Helper ConcorrenteAnalise — Análise automática dos dados coletados (IA)
 *
 * A partir dos posts coletados de um concorrente, gera a análise do spec §8.9:
 * top posts, formatos, temas, ganchos, CTAs, hashtags, horários, dias,
 * frequência, padrões e, principalmente, LACUNAS/OPORTUNIDADES para o cliente.
 *
 * A IA NÃO copia textos/identidade (spec §9): analisa estrutura, tema, gancho,
 * formato, emoção, CTA e padrão de desempenho. O resultado é salvo em
 * concorrente_analises (JSON estruturado + resumo textual).
 */

class ConcorrenteAnalise
{
    /**
     * Gera e persiste a análise do concorrente. Usa os últimos posts coletados.
     *
     * @return array {sucesso: bool, analise_id?: int, erro?: string}
     */
    public static function gerar(int $concorrenteId, int $empresaId, ?int $coletaId = null): array
    {
        $concorrente = Concorrente::buscar($concorrenteId, $empresaId);
        if (!$concorrente) {
            return ['sucesso' => false, 'erro' => 'Concorrente não encontrado.'];
        }

        // Amostra dos posts mais recentes (limite defensivo para o prompt).
        $posts = Database::query(
            "SELECT tipo_conteudo, data_publicacao, titulo, texto, hashtags,
                    curtidas, comentarios, visualizacoes, compartilhamentos,
                    engajamento_absoluto, taxa_engajamento
             FROM concorrente_posts
             WHERE concorrente_id = :id AND empresa_id = :eid
             ORDER BY coletado_em DESC
             LIMIT 40",
            ['id' => $concorrenteId, 'eid' => $empresaId]
        );

        if (empty($posts)) {
            return ['sucesso' => false, 'erro' => 'Nenhum post coletado para analisar. Rode uma coleta primeiro.'];
        }

        // Métricas agregadas calculadas no backend (não dependem da IA).
        $agregados = self::agregar($posts);

        if (!Configuracao::apiAtiva('openai') && !Configuracao::apiAtiva('anthropic')) {
            // Sem IA: salva ao menos a análise quantitativa (top posts, formatos).
            return self::salvar($concorrenteId, $empresaId, $coletaId, self::resumoSemIA($agregados), [
                'agregados' => $agregados,
            ], []);
        }

        $prompt = self::montarPrompt($concorrente, $posts, $agregados);
        $res = ApiHelper::chamarAnalise($prompt, true, 3000);

        if (!$res['sucesso'] || !is_array($res['conteudo'])) {
            // Fallback quantitativo se a IA falhar.
            return self::salvar($concorrenteId, $empresaId, $coletaId, self::resumoSemIA($agregados), [
                'agregados' => $agregados,
            ], []);
        }

        $dados = $res['conteudo'];
        $dados['agregados'] = $agregados;
        $resumo = (string) ($dados['resumo'] ?? self::resumoSemIA($agregados));
        $oportunidades = $dados['oportunidades'] ?? [];

        return self::salvar($concorrenteId, $empresaId, $coletaId, $resumo, $dados, $oportunidades);
    }

    /** Cálculos quantitativos independentes da IA. */
    private static function agregar(array $posts): array
    {
        $formatos = [];
        $horas = [];
        $diasSemana = [];
        $comEng = [];

        foreach ($posts as $p) {
            $fmt = $p['tipo_conteudo'] ?: 'desconhecido';
            $formatos[$fmt] = ($formatos[$fmt] ?? 0) + 1;

            if (!empty($p['data_publicacao'])) {
                $ts = strtotime($p['data_publicacao']);
                if ($ts) {
                    $horas[(int) date('H', $ts)] = ($horas[(int) date('H', $ts)] ?? 0) + 1;
                    $diasSemana[(int) date('N', $ts)] = ($diasSemana[(int) date('N', $ts)] ?? 0) + 1;
                }
            }
            if ($p['engajamento_absoluto'] !== null) {
                $comEng[] = $p;
            }
        }

        // Top posts por engajamento absoluto (só os que têm métrica disponível).
        usort($comEng, fn($a, $b) => (int) $b['engajamento_absoluto'] <=> (int) $a['engajamento_absoluto']);
        $top = array_slice(array_map(fn($p) => [
            'titulo' => $p['titulo'],
            'tipo' => $p['tipo_conteudo'],
            'engajamento' => (int) $p['engajamento_absoluto'],
        ], $comEng), 0, 5);

        arsort($formatos);
        arsort($horas);
        arsort($diasSemana);

        return [
            'total_posts' => count($posts),
            'posts_com_engajamento' => count($comEng),
            'formatos' => $formatos,
            'top_posts' => $top,
            'melhores_horas' => array_slice(array_keys($horas), 0, 3),
            'melhores_dias' => array_slice(array_keys($diasSemana), 0, 3),
        ];
    }

    private static function montarPrompt(array $concorrente, array $posts, array $agregados): string
    {
        $amostra = [];
        foreach (array_slice($posts, 0, 25) as $p) {
            $amostra[] = [
                'tipo' => $p['tipo_conteudo'],
                'titulo' => mb_substr((string) $p['titulo'], 0, 160),
                'texto' => mb_substr((string) $p['texto'], 0, 400),
                'engajamento' => $p['engajamento_absoluto'],
            ];
        }

        $ctx = json_encode([
            'concorrente' => $concorrente['nome'],
            'plataforma' => $concorrente['plataforma'],
            'agregados' => $agregados,
            'amostra_posts' => $amostra,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return <<<PROMPT
Você é um estrategista de conteúdo. Analise os dados públicos coletados de um concorrente e produza inteligência acionável. NÃO copie textos, legendas ou identidade visual — analise apenas ESTRUTURA, TEMA, GANCHO, FORMATO, EMOÇÃO, CTA e PADRÃO DE DESEMPENHO.

Dados coletados:
{$ctx}

Responda em JSON válido com esta estrutura exata:
{
  "resumo": "resumo executivo em 2-3 frases do que funciona para este concorrente",
  "temas_recorrentes": ["..."],
  "temas_melhor_desempenho": ["..."],
  "formatos_melhor_desempenho": ["..."],
  "ganchos": ["padrões de abertura/gancho identificados"],
  "estruturas_texto": ["..."],
  "ctas": ["chamadas para ação recorrentes"],
  "padroes_linguagem": ["tom, emoções, estilo"],
  "lacunas": ["assuntos/oportunidades pouco explorados por este concorrente"],
  "oportunidades": ["recomendações concretas de conteúdo original para o cliente explorar essas lacunas, adaptadas ao Brand Book do cliente"]
}
Não inclua nenhum texto fora do JSON.
PROMPT;
    }

    private static function resumoSemIA(array $agregados): string
    {
        $fmt = array_key_first($agregados['formatos'] ?? []) ?: 'n/d';
        return sprintf(
            'Análise quantitativa: %d posts coletados, %d com métricas de engajamento. Formato mais usado: %s.',
            $agregados['total_posts'] ?? 0,
            $agregados['posts_com_engajamento'] ?? 0,
            $fmt
        );
    }

    /**
     * Monta a BASE textual de inteligência competitiva para a Máquina de
     * Conteúdo (fonte "concorrência", spec §9). Reúne os padrões das análises
     * mais recentes dos concorrentes selecionados + os títulos dos posts de
     * melhor desempenho (como referência de TEMA/estrutura, nunca para cópia).
     *
     * @param int   $empresaId
     * @param int[] $concorrenteIds  vazio = todos os concorrentes da empresa
     * @param string $metrica        métrica que define "melhor desempenho"
     * @return string  base pronta para injeção no prompt, ou '' se não houver dados
     */
    public static function montarBaseParaGeracao(int $empresaId, array $concorrenteIds = [], string $metrica = 'engajamento_absoluto'): string
    {
        $metricasValidas = ['engajamento_absoluto', 'curtidas', 'comentarios', 'visualizacoes', 'compartilhamentos'];
        if (!in_array($metrica, $metricasValidas, true)) {
            $metrica = 'engajamento_absoluto';
        }

        $filtroConc = '';
        $params = ['eid' => $empresaId];
        if (!empty($concorrenteIds)) {
            $ids = array_map('intval', $concorrenteIds);
            $filtroConc = ' AND concorrente_id IN (' . implode(',', $ids) . ')';
        }

        // Análises mais recentes por concorrente.
        try {
            $analises = Database::query(
                "SELECT a.concorrente_id, a.resumo, a.dados, a.oportunidades, c.nome
                 FROM concorrente_analises a
                 JOIN concorrentes c ON a.concorrente_id = c.id
                 WHERE a.empresa_id = :eid" . ($filtroConc ? str_replace('concorrente_id', 'a.concorrente_id', $filtroConc) : '') . "
                 ORDER BY a.criado_em DESC",
                $params
            );
        } catch (\Throwable $e) {
            $analises = [];
        }

        // Posts de melhor desempenho pela métrica escolhida.
        try {
            $posts = Database::query(
                "SELECT titulo, tipo_conteudo, {$metrica} AS metrica
                 FROM concorrente_posts
                 WHERE empresa_id = :eid AND {$metrica} IS NOT NULL" . $filtroConc . "
                 ORDER BY {$metrica} DESC
                 LIMIT 10",
                $params
            );
        } catch (\Throwable $e) {
            $posts = [];
        }

        if (empty($analises) && empty($posts)) {
            return '';
        }

        $linhas = [];

        // Consolida padrões das análises (sem repetir por concorrente).
        $vistos = [];
        foreach ($analises as $a) {
            if (isset($vistos[$a['concorrente_id']])) continue;
            $vistos[$a['concorrente_id']] = true;

            $d = json_decode($a['dados'] ?? '[]', true) ?: [];
            $linhas[] = 'Concorrente "' . $a['nome'] . '": ' . trim((string) $a['resumo']);
            foreach ([
                'Temas que performaram' => $d['temas_melhor_desempenho'] ?? [],
                'Formatos que performaram' => $d['formatos_melhor_desempenho'] ?? [],
                'Ganchos' => $d['ganchos'] ?? [],
                'CTAs' => $d['ctas'] ?? [],
                'Lacunas/oportunidades' => $d['lacunas'] ?? [],
            ] as $rotulo => $lista) {
                if (!empty($lista)) {
                    $linhas[] = '  - ' . $rotulo . ': ' . implode('; ', array_slice((array) $lista, 0, 6));
                }
            }
        }

        if (!empty($posts)) {
            $linhas[] = "\nTemas/títulos de melhor desempenho (referência de assunto, NÃO copiar):";
            foreach ($posts as $p) {
                $t = trim((string) ($p['titulo'] ?? ''));
                if ($t === '') continue;
                $linhas[] = '  - [' . ($p['tipo_conteudo'] ?: 'n/d') . '] ' . mb_substr($t, 0, 120) . ' (' . $metrica . ': ' . (int) $p['metrica'] . ')';
            }
        }

        return trim(implode("\n", $linhas));
    }

    /**
     * Monta a base a partir de UMA publicação específica do concorrente
     * (fluxo análogo ao de "gerar a partir de uma notícia"). Usa apenas o
     * conteúdo/estrutura como inspiração — nunca para cópia (spec §9).
     *
     * @return string base pronta para o prompt, ou '' se o post não existir.
     */
    public static function montarBaseDeUmPost(int $empresaId, int $postId): string
    {
        try {
            $p = Database::queryOne(
                "SELECT p.titulo, p.texto, p.tipo_conteudo, p.hashtags,
                        p.curtidas, p.comentarios, p.visualizacoes, p.compartilhamentos, p.engajamento_absoluto,
                        c.nome AS concorrente_nome
                 FROM concorrente_posts p
                 JOIN concorrentes c ON p.concorrente_id = c.id
                 WHERE p.id = :id AND p.empresa_id = :e
                 LIMIT 1",
                ['id' => $postId, 'e' => $empresaId]
            );
        } catch (\Throwable $e) {
            return '';
        }

        if (!$p) {
            return '';
        }

        $hashtags = '';
        $hj = json_decode((string) ($p['hashtags'] ?? '[]'), true);
        if (is_array($hj) && !empty($hj)) {
            $hashtags = implode(' ', array_slice($hj, 0, 10));
        }

        $metricas = [];
        foreach (['curtidas' => 'curtidas', 'comentarios' => 'comentários', 'visualizacoes' => 'visualizações', 'compartilhamentos' => 'compartilhamentos', 'engajamento_absoluto' => 'engajamento'] as $col => $rot) {
            if ($p[$col] !== null) {
                $metricas[] = $rot . ': ' . (int) $p[$col];
            }
        }

        $linhas = [];
        $linhas[] = 'Publicação de referência do concorrente "' . $p['concorrente_nome'] . '" (use apenas como INSPIRAÇÃO de estrutura/tema/gancho/formato/CTA — NÃO copie):';
        if (!empty($p['tipo_conteudo'])) $linhas[] = 'Formato: ' . $p['tipo_conteudo'];
        if (!empty($p['titulo'])) $linhas[] = 'Título/gancho: ' . $p['titulo'];
        if (!empty($p['texto'])) $linhas[] = 'Texto/legenda: ' . mb_substr((string) $p['texto'], 0, 800);
        if ($hashtags !== '') $linhas[] = 'Hashtags usadas: ' . $hashtags;
        if (!empty($metricas)) $linhas[] = 'Desempenho: ' . implode(', ', $metricas);

        return trim(implode("\n", $linhas));
    }

    private static function salvar(int $concorrenteId, int $empresaId, ?int $coletaId, string $resumo, array $dados, array $oportunidades): array
    {
        try {
            Database::execute(
                "INSERT INTO concorrente_analises (concorrente_id, coleta_id, empresa_id, resumo, dados, oportunidades, criado_em)
                 VALUES (:cid, :coleta, :eid, :resumo, :dados, :oport, NOW())",
                [
                    'cid' => $concorrenteId,
                    'coleta' => $coletaId,
                    'eid' => $empresaId,
                    'resumo' => $resumo,
                    'dados' => json_encode($dados, JSON_UNESCAPED_UNICODE),
                    'oport' => json_encode($oportunidades, JSON_UNESCAPED_UNICODE),
                ]
            );
            return ['sucesso' => true, 'analise_id' => (int) Database::lastInsertId()];
        } catch (\Throwable $e) {
            Logger::error('Erro ao salvar análise de concorrente', ['erro' => $e->getMessage()]);
            return ['sucesso' => false, 'erro' => 'Não foi possível salvar a análise.'];
        }
    }
}
