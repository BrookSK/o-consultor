<?php
/**
 * Model CalendarioConteudo — Calendário editorial por empresa (spec §6)
 *
 * Cada item é uma sugestão de publicação com uma ORIGEM (notícia, data
 * comemorativa, concorrência, conteúdo semanal, tema manual, tendência) e,
 * opcionalmente, ligação ao conteúdo gerado na Máquina (conteudos_marca).
 *
 * Isolado por empresa. Tolerante à ausência da tabela (migration 054).
 */

class CalendarioConteudo
{
    public const ORIGENS = ['noticia','data_comemorativa','concorrencia','conteudo_semanal','tema_manual','tendencia'];
    public const STATUS  = ['sugerido','planejado','gerado','em_revisao','aprovado','publicado','ignorado'];

    /**
     * Lista os itens do calendário de uma empresa, opcionalmente por intervalo.
     */
    public static function listar(int $empresaId, ?string $de = null, ?string $ate = null): array
    {
        try {
            $sql = "SELECT c.*, n.titulo AS noticia_titulo, dc.nome AS data_nome, cc.nome AS concorrente_nome
                    FROM calendario_conteudo c
                    LEFT JOIN noticias n ON c.noticia_id = n.id
                    LEFT JOIN datas_comemorativas dc ON c.data_comemorativa_id = dc.id
                    LEFT JOIN concorrentes cc ON c.concorrente_id = cc.id
                    WHERE c.empresa_id = :empresa_id AND c.status <> 'ignorado'";
            $params = ['empresa_id' => $empresaId];
            if ($de) { $sql .= " AND c.data_publicacao_sugerida >= :de"; $params['de'] = $de; }
            if ($ate) { $sql .= " AND c.data_publicacao_sugerida <= :ate"; $params['ate'] = $ate; }
            $sql .= " ORDER BY c.data_publicacao_sugerida IS NULL, c.data_publicacao_sugerida ASC, c.criado_em DESC";
            return Database::query($sql, $params);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function buscar(int $id, int $empresaId): ?array
    {
        try {
            return Database::queryOne(
                "SELECT * FROM calendario_conteudo WHERE id = :id AND empresa_id = :empresa_id LIMIT 1",
                ['id' => $id, 'empresa_id' => $empresaId]
            );
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Cria um item de calendário. Calcula a data de publicação sugerida a
     * partir da data do evento e da antecedência, quando fornecidas.
     */
    public static function criar(int $empresaId, array $dados): int|false
    {
        $dataEvento = $dados['data_evento'] ?? null;
        $antecedencia = isset($dados['antecedencia_dias']) ? (int) $dados['antecedencia_dias'] : null;
        $dataPub = $dados['data_publicacao_sugerida'] ?? null;

        // Se não veio data de publicação mas veio evento + antecedência, calcula.
        if (!$dataPub && $dataEvento && $antecedencia !== null) {
            $ts = strtotime($dataEvento . " -{$antecedencia} days");
            if ($ts) $dataPub = date('Y-m-d', $ts);
        }

        try {
            $ok = Database::execute(
                "INSERT INTO calendario_conteudo
                    (empresa_id, tema, origem, noticia_id, data_comemorativa_id, concorrente_id, conteudo_id,
                     data_evento, data_publicacao_sugerida, antecedencia_dias, formato_recomendado, objetivo,
                     responsavel, gerar_imagem, status, observacoes, criado_em)
                 VALUES
                    (:empresa_id, :tema, :origem, :noticia_id, :data_com_id, :concorrente_id, :conteudo_id,
                     :data_evento, :data_pub, :antecedencia, :formato, :objetivo,
                     :responsavel, :gerar_imagem, :status, :observacoes, NOW())",
                [
                    'empresa_id'    => $empresaId,
                    'tema'          => $dados['tema'],
                    'origem'        => in_array($dados['origem'] ?? '', self::ORIGENS, true) ? $dados['origem'] : 'tema_manual',
                    'noticia_id'    => $dados['noticia_id'] ?? null,
                    'data_com_id'   => $dados['data_comemorativa_id'] ?? null,
                    'concorrente_id'=> $dados['concorrente_id'] ?? null,
                    'conteudo_id'   => $dados['conteudo_id'] ?? null,
                    'data_evento'   => $dataEvento,
                    'data_pub'      => $dataPub,
                    'antecedencia'  => $antecedencia,
                    'formato'       => $dados['formato_recomendado'] ?? null,
                    'objetivo'      => $dados['objetivo'] ?? null,
                    'responsavel'   => $dados['responsavel'] ?? null,
                    'gerar_imagem'  => array_key_exists('gerar_imagem', $dados) ? (!empty($dados['gerar_imagem']) ? 1 : 0) : 1,
                    'status'        => in_array($dados['status'] ?? '', self::STATUS, true) ? $dados['status'] : 'sugerido',
                    'observacoes'   => $dados['observacoes'] ?? null,
                ]
            );
            return $ok ? (int) Database::lastInsertId() : false;
        } catch (\Throwable $e) {
            Logger::error('Erro ao criar item de calendário', ['erro' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Evita duplicar sugestão da mesma origem/data (ex.: reprocessar o gerador).
     */
    public static function existeParaData(int $empresaId, int $dataComemorativaId): bool
    {
        try {
            $r = Database::queryOne(
                "SELECT id FROM calendario_conteudo
                 WHERE empresa_id = :eid AND data_comemorativa_id = :dc AND status <> 'ignorado' LIMIT 1",
                ['eid' => $empresaId, 'dc' => $dataComemorativaId]
            );
            return (bool) $r;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function atualizarStatus(int $id, int $empresaId, string $status): bool
    {
        if (!in_array($status, self::STATUS, true)) return false;
        try {
            return Database::execute(
                "UPDATE calendario_conteudo SET status = :s, atualizado_em = NOW() WHERE id = :id AND empresa_id = :eid",
                ['s' => $status, 'id' => $id, 'eid' => $empresaId]
            );
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Atualiza campos editáveis de um item (editar sugestão).
     */
    public static function atualizar(int $id, int $empresaId, array $dados): bool
    {
        $permitidas = ['tema','formato_recomendado','objetivo','responsavel','data_publicacao_sugerida','data_evento','gerar_imagem','observacoes'];
        $sets = [];
        $params = ['id' => $id, 'eid' => $empresaId];
        foreach ($permitidas as $c) {
            if (!array_key_exists($c, $dados)) continue;
            $sets[] = "{$c} = :{$c}";
            $params[$c] = $c === 'gerar_imagem' ? (!empty($dados[$c]) ? 1 : 0) : $dados[$c];
        }
        if (empty($sets)) return false;
        try {
            return Database::execute(
                "UPDATE calendario_conteudo SET " . implode(', ', $sets) . ", atualizado_em = NOW() WHERE id = :id AND empresa_id = :eid",
                $params
            );
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function vincularConteudo(int $id, int $empresaId, int $conteudoId): bool
    {
        try {
            return Database::execute(
                "UPDATE calendario_conteudo SET conteudo_id = :cid, status = 'gerado', atualizado_em = NOW()
                 WHERE id = :id AND empresa_id = :eid",
                ['cid' => $conteudoId, 'id' => $id, 'eid' => $empresaId]
            );
        } catch (\Throwable $e) {
            return false;
        }
    }
}
