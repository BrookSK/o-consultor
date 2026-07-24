<?php
/**
 * Helper ConcorrenteColeta — Coleta e normalização de dados de concorrentes
 *
 * Orquestra: ScrapingBee (HTML público) -> extração/normalização -> snapshots
 * em concorrente_posts (histórico, sem sobrescrever) -> métricas de engajamento.
 *
 * Princípios do spec:
 *  §8.7 métricas indisponíveis viram NULL (nunca 0/valor inventado).
 *  §8.8 cada coleta cria registros novos (histórico).
 *  §8.10 engajamento absoluto e taxa estimada só quando há dados suficientes.
 *  §17/§19 controle de falhas, retry e pausa após falhas consecutivas.
 */

class ConcorrenteColeta
{
    /** Nº de falhas consecutivas para pausar automaticamente o concorrente. */
    private const MAX_FALHAS_CONSECUTIVAS = 5;

    /**
     * Executa uma coleta para um concorrente. Cria o registro de coleta, tenta
     * o scrape, normaliza os posts e atualiza métricas de agenda/falhas.
     *
     * @return array {sucesso: bool, coleta_id: int, posts: int, erro: ?string}
     */
    public static function executar(int $concorrenteId, int $empresaId, string $origem = 'manual'): array
    {
        $concorrente = Concorrente::buscar($concorrenteId, $empresaId);
        if (!$concorrente) {
            return ['sucesso' => false, 'coleta_id' => 0, 'posts' => 0, 'erro' => 'Concorrente não encontrado.'];
        }

        // Evita duas coletas simultâneas do mesmo perfil (spec §17).
        $emAndamento = Database::queryOne(
            "SELECT id FROM concorrente_coletas WHERE concorrente_id = :id AND status IN ('pendente','processando') LIMIT 1",
            ['id' => $concorrenteId]
        );
        if ($emAndamento) {
            return ['sucesso' => false, 'coleta_id' => (int) $emAndamento['id'], 'posts' => 0, 'erro' => 'Já existe uma coleta em andamento para este concorrente.'];
        }

        // Cria o registro de coleta.
        Database::execute(
            "INSERT INTO concorrente_coletas (concorrente_id, empresa_id, origem, status, iniciada_em, criado_em)
             VALUES (:cid, :eid, :origem, 'processando', NOW(), NOW())",
            ['cid' => $concorrenteId, 'eid' => $empresaId, 'origem' => $origem]
        );
        $coletaId = (int) Database::lastInsertId();

        // Scrape via ScrapingBee.
        $res = ScrapingBee::buscarHtml((string) $concorrente['url_publica'], [
            'render_js' => true,
        ]);

        if (!$res['sucesso']) {
            self::finalizarComErro($coletaId, $concorrenteId, (string) ($res['tipo_erro'] ?? 'erro_desconhecido'), (string) ($res['erro'] ?? 'Falha na coleta.'));
            return ['sucesso' => false, 'coleta_id' => $coletaId, 'posts' => 0, 'erro' => $res['erro']];
        }

        // Extrai/normaliza os posts do HTML (best-effort, tolerante a layout).
        $maxPosts = max(1, min(50, (int) ($concorrente['max_posts_por_coleta'] ?? 12)));
        $posts = self::extrairPosts((string) $res['html'], (string) $concorrente['plataforma'], $maxPosts);

        $seguidores = self::extrairSeguidores((string) $res['html']);
        if ($seguidores === null && isset($concorrente['seguidores'])) {
            $seguidores = $concorrente['seguidores'] !== null ? (int) $concorrente['seguidores'] : null;
        }

        // Persiste snapshots (histórico) e calcula engajamento.
        $salvos = 0;
        foreach ($posts as $post) {
            if (self::salvarPost($concorrenteId, $coletaId, $empresaId, (string) $concorrente['plataforma'], $post, $seguidores)) {
                $salvos++;
            }
        }

        $status = $salvos > 0 ? 'concluida' : 'parcial';
        Database::execute(
            "UPDATE concorrente_coletas
             SET status = :st, posts_coletados = :posts, seguidores_snapshot = :seg, finalizada_em = NOW()
             WHERE id = :id",
            ['st' => $status, 'posts' => $salvos, 'seg' => $seguidores, 'id' => $coletaId]
        );

        // Atualiza agenda e zera falhas consecutivas.
        Database::execute(
            "UPDATE concorrentes
             SET ultima_coleta_em = NOW(), proxima_coleta_em = :prox, falhas_consecutivas = 0
             WHERE id = :id",
            ['prox' => self::proximaColeta((string) $concorrente['frequencia_coleta']), 'id' => $concorrenteId]
        );

        // Atualiza seguidores do perfil quando descobertos.
        if ($seguidores !== null) {
            Database::execute("UPDATE concorrentes SET seguidores = :s WHERE id = :id", ['s' => $seguidores, 'id' => $concorrenteId]);
        }

        return ['sucesso' => true, 'coleta_id' => $coletaId, 'posts' => $salvos, 'erro' => $salvos === 0 ? 'Coleta concluída, porém nenhum post pôde ser extraído (layout público pode ter mudado).' : null];
    }

    /**
     * Marca a coleta como erro, registra e incrementa falhas consecutivas.
     * Pausa o concorrente após MAX_FALHAS_CONSECUTIVAS (spec §17/§19).
     */
    private static function finalizarComErro(int $coletaId, int $concorrenteId, string $tipoErro, string $mensagem): void
    {
        Database::execute(
            "UPDATE concorrente_coletas SET status = 'erro', tipo_erro = :tp, mensagem = :msg, finalizada_em = NOW() WHERE id = :id",
            ['tp' => $tipoErro, 'msg' => mb_substr($mensagem, 0, 1000), 'id' => $coletaId]
        );

        $row = Database::queryOne("SELECT falhas_consecutivas, frequencia_coleta FROM concorrentes WHERE id = :id", ['id' => $concorrenteId]);
        $falhas = (int) ($row['falhas_consecutivas'] ?? 0) + 1;

        if ($falhas >= self::MAX_FALHAS_CONSECUTIVAS) {
            Database::execute(
                "UPDATE concorrentes SET falhas_consecutivas = :f, status = 'pausado', proxima_coleta_em = NULL WHERE id = :id",
                ['f' => $falhas, 'id' => $concorrenteId]
            );
            Logger::error('Concorrente pausado após falhas consecutivas', ['concorrente_id' => $concorrenteId, 'falhas' => $falhas]);
        } else {
            Database::execute(
                "UPDATE concorrentes SET falhas_consecutivas = :f, proxima_coleta_em = :prox WHERE id = :id",
                ['f' => $falhas, 'prox' => self::proximaColeta((string) ($row['frequencia_coleta'] ?? 'manual')), 'id' => $concorrenteId]
            );
        }
    }

    /**
     * Persiste um snapshot de post, calculando engajamento e registrando quais
     * métricas ficaram indisponíveis (NULL). Nunca inventa valores (spec §8.7).
     */
    private static function salvarPost(int $concorrenteId, int $coletaId, int $empresaId, string $plataforma, array $post, ?int $seguidores): bool
    {
        // Métricas: NULL = não disponível; diferenciar de 0.
        $curtidas          = self::intOuNull($post['curtidas'] ?? null);
        $comentarios       = self::intOuNull($post['comentarios'] ?? null);
        $visualizacoes     = self::intOuNull($post['visualizacoes'] ?? null);
        $compartilhamentos = self::intOuNull($post['compartilhamentos'] ?? null);
        $reacoes           = self::intOuNull($post['reacoes'] ?? null);

        // Registra métricas indisponíveis para auditoria (spec §8.7).
        $indisponiveis = [];
        foreach (['curtidas' => $curtidas, 'comentarios' => $comentarios, 'visualizacoes' => $visualizacoes, 'compartilhamentos' => $compartilhamentos, 'reacoes' => $reacoes] as $nome => $val) {
            if ($val === null) $indisponiveis[] = $nome;
        }

        // Engajamento absoluto: só quando há ao menos uma interação disponível.
        // Não somamos visualizações (não é interação — spec §8.10).
        $componentes = array_filter([$curtidas, $comentarios, $compartilhamentos, $reacoes], fn($v) => $v !== null);
        $engajamentoAbs = !empty($componentes) ? array_sum($componentes) : null;

        // Taxa estimada só quando há seguidores > 0 e engajamento disponível.
        $taxa = ($engajamentoAbs !== null && $seguidores !== null && $seguidores > 0)
            ? round(($engajamentoAbs / $seguidores) * 100, 4)
            : null;

        try {
            return Database::execute(
                "INSERT INTO concorrente_posts
                    (concorrente_id, coleta_id, empresa_id, post_ref, url, plataforma, tipo_conteudo,
                     data_publicacao, titulo, texto, hashtags, mencoes, imagem_capa_url, qtd_imagens,
                     duracao_video_seg, curtidas, comentarios, visualizacoes, compartilhamentos, reacoes,
                     engajamento_absoluto, taxa_engajamento, metricas_indisponiveis, conteudo_bruto,
                     fonte_coleta, status_coleta, coletado_em)
                 VALUES
                    (:cid, :coleta, :eid, :ref, :url, :plataforma, :tipo,
                     :data_pub, :titulo, :texto, :hashtags, :mencoes, :capa, :qtd_img,
                     :dur, :curtidas, :comentarios, :views, :shares, :reacoes,
                     :eng_abs, :taxa, :indisp, :bruto,
                     'scrapingbee', :status_coleta, NOW())",
                [
                    'cid' => $concorrenteId, 'coleta' => $coletaId, 'eid' => $empresaId,
                    'ref' => $post['post_ref'] ?? null,
                    'url' => $post['url'] ?? null,
                    'plataforma' => $plataforma,
                    'tipo' => $post['tipo_conteudo'] ?? null,
                    'data_pub' => $post['data_publicacao'] ?? null,
                    'titulo' => isset($post['titulo']) ? mb_substr((string) $post['titulo'], 0, 500) : null,
                    'texto' => $post['texto'] ?? null,
                    'hashtags' => isset($post['hashtags']) ? json_encode($post['hashtags'], JSON_UNESCAPED_UNICODE) : null,
                    'mencoes' => isset($post['mencoes']) ? json_encode($post['mencoes'], JSON_UNESCAPED_UNICODE) : null,
                    'capa' => $post['imagem_capa_url'] ?? null,
                    'qtd_img' => self::intOuNull($post['qtd_imagens'] ?? null),
                    'dur' => self::intOuNull($post['duracao_video_seg'] ?? null),
                    'curtidas' => $curtidas, 'comentarios' => $comentarios, 'views' => $visualizacoes,
                    'shares' => $compartilhamentos, 'reacoes' => $reacoes,
                    'eng_abs' => $engajamentoAbs, 'taxa' => $taxa,
                    'indisp' => json_encode($indisponiveis, JSON_UNESCAPED_UNICODE),
                    'bruto' => isset($post['conteudo_bruto']) ? mb_substr((string) $post['conteudo_bruto'], 0, 60000) : null,
                    'status_coleta' => $post['status_coleta'] ?? 'ok',
                ]
            );
        } catch (\Throwable $e) {
            Logger::error('Erro ao salvar post de concorrente', ['erro' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Extração best-effort de posts a partir do HTML público. As plataformas
     * mudam de layout com frequência; por isso capturamos o que for
     * publicamente identificável e deixamos o resto como NULL (não inventar).
     * A normalização fina/por-plataforma pode evoluir sem alterar o schema.
     */
    private static function extrairPosts(string $html, string $plataforma, int $maxPosts): array
    {
        $posts = [];

        // Tenta blocos JSON-LD (comum em blogs/sites institucionais e alguns perfis).
        if (preg_match_all('#<script[^>]*type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#is', $html, $m)) {
            foreach ($m[1] as $bloco) {
                $json = json_decode(trim($bloco), true);
                if (!is_array($json)) continue;
                $itens = isset($json['@graph']) && is_array($json['@graph']) ? $json['@graph'] : [$json];
                foreach ($itens as $it) {
                    if (!is_array($it)) continue;
                    $tipo = strtolower((string) ($it['@type'] ?? ''));
                    if (!in_array($tipo, ['article', 'newsarticle', 'blogposting', 'socialmediaposting', 'videoobject'], true)) continue;
                    $posts[] = [
                        'post_ref' => $it['identifier'] ?? ($it['url'] ?? null),
                        'url' => $it['url'] ?? ($it['mainEntityOfPage'] ?? null),
                        'tipo_conteudo' => $tipo === 'videoobject' ? 'video' : 'artigo',
                        'data_publicacao' => self::normalizarData($it['datePublished'] ?? null),
                        'titulo' => $it['headline'] ?? ($it['name'] ?? null),
                        'texto' => $it['description'] ?? ($it['articleBody'] ?? null),
                        'imagem_capa_url' => is_array($it['image'] ?? null) ? ($it['image']['url'] ?? ($it['image'][0] ?? null)) : ($it['image'] ?? null),
                        'conteudo_bruto' => $bloco,
                        'status_coleta' => 'ok',
                    ];
                    if (count($posts) >= $maxPosts) break 2;
                }
            }
        }

        // Fallback: meta og: (pelo menos captura a página como 1 item utilizável).
        if (empty($posts)) {
            $titulo = self::metaConteudo($html, 'og:title') ?? self::tagTitle($html);
            $descr = self::metaConteudo($html, 'og:description');
            $imagem = self::metaConteudo($html, 'og:image');
            $urlOg = self::metaConteudo($html, 'og:url');
            if ($titulo || $descr) {
                $posts[] = [
                    'post_ref' => $urlOg,
                    'url' => $urlOg,
                    'tipo_conteudo' => 'artigo',
                    'data_publicacao' => null,
                    'titulo' => $titulo,
                    'texto' => $descr,
                    'imagem_capa_url' => $imagem,
                    'conteudo_bruto' => null,
                    'status_coleta' => 'parcial',
                ];
            }
        }

        return array_slice($posts, 0, $maxPosts);
    }

    /** Tenta descobrir o nº de seguidores no HTML (og:description costuma trazer). */
    private static function extrairSeguidores(string $html): ?int
    {
        $desc = self::metaConteudo($html, 'og:description') ?? '';
        // Ex.: "1,234 Followers" / "1.2M seguidores"
        if (preg_match('/([\d.,]+\s*[kKmM]?)\s*(seguidores|followers)/u', $desc, $m)) {
            return self::parseNumeroAbreviado($m[1]);
        }
        return null;
    }

    private static function metaConteudo(string $html, string $prop): ?string
    {
        if (preg_match('#<meta[^>]+(?:property|name)=["\']' . preg_quote($prop, '#') . '["\'][^>]+content=["\'](.*?)["\']#is', $html, $m)) {
            return html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        return null;
    }

    private static function tagTitle(string $html): ?string
    {
        if (preg_match('#<title[^>]*>(.*?)</title>#is', $html, $m)) {
            return html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        return null;
    }

    private static function parseNumeroAbreviado(string $valor): ?int
    {
        $valor = trim(mb_strtolower($valor));
        $mult = 1;
        if (str_contains($valor, 'k')) $mult = 1000;
        elseif (str_contains($valor, 'm')) $mult = 1000000;
        $num = (float) str_replace([',', '.', 'k', 'm', ' '], ['', '.', '', '', ''], $valor);
        // Heurística: se abreviado (k/m) trata o número como decimal com ponto.
        if ($mult > 1) {
            $num = (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', $valor));
        }
        $resultado = (int) round($num * $mult);
        return $resultado > 0 ? $resultado : null;
    }

    private static function normalizarData(?string $data): ?string
    {
        if (!$data) return null;
        $ts = strtotime($data);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }

    private static function intOuNull($valor): ?int
    {
        if ($valor === null || $valor === '') return null;
        if (!is_numeric($valor)) return null;
        return (int) $valor;
    }

    /** Calcula a data da próxima coleta a partir da frequência. */
    public static function proximaColeta(string $frequencia): ?string
    {
        return match ($frequencia) {
            'diaria'    => date('Y-m-d H:i:s', strtotime('+1 day')),
            '3_dias'    => date('Y-m-d H:i:s', strtotime('+3 days')),
            'semanal'   => date('Y-m-d H:i:s', strtotime('+7 days')),
            'quinzenal' => date('Y-m-d H:i:s', strtotime('+15 days')),
            'mensal'    => date('Y-m-d H:i:s', strtotime('+1 month')),
            default     => null, // manual: sem agendamento
        };
    }
}
