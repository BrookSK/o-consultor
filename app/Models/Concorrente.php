<?php
/**
 * Model Concorrente — Scrap da Concorrência
 *
 * CRUD de concorrentes monitorados e acesso a coletas/posts/análises.
 * TODAS as consultas são isoladas por empresa_id (spec §18): um cliente
 * nunca acessa concorrentes de outro. Tolerante à ausência das tabelas
 * (migration 054): métodos de leitura retornam vazio em vez de quebrar.
 */

class Concorrente
{
    public const PLATAFORMAS = ['instagram', 'linkedin', 'facebook', 'tiktok', 'youtube', 'blog'];
    public const FREQUENCIAS = ['manual', 'diaria', '3_dias', 'semanal', 'quinzenal', 'mensal'];

    /**
     * Lista os concorrentes de uma empresa.
     */
    public static function listar(int $empresaId): array
    {
        try {
            return Database::query(
                "SELECT * FROM concorrentes WHERE empresa_id = :empresa_id ORDER BY principal DESC, nome ASC",
                ['empresa_id' => $empresaId]
            );
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Busca um concorrente garantindo que pertence à empresa (isolamento).
     */
    public static function buscar(int $id, int $empresaId): ?array
    {
        try {
            return Database::queryOne(
                "SELECT * FROM concorrentes WHERE id = :id AND empresa_id = :empresa_id LIMIT 1",
                ['id' => $id, 'empresa_id' => $empresaId]
            );
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Cria um concorrente. Retorna o ID ou false.
     */
    public static function criar(int $empresaId, array $dados): int|false
    {
        try {
            $ok = Database::execute(
                "INSERT INTO concorrentes
                    (empresa_id, nome, nome_perfil, url_publica, plataforma, descricao, categoria,
                     frequencia_coleta, max_posts_por_coleta, principal, status, seguidores,
                     observacoes, data_inicio_acompanhamento, criado_em)
                 VALUES
                    (:empresa_id, :nome, :nome_perfil, :url_publica, :plataforma, :descricao, :categoria,
                     :frequencia_coleta, :max_posts, :principal, 'ativo', :seguidores,
                     :observacoes, :data_inicio, NOW())",
                [
                    'empresa_id'        => $empresaId,
                    'nome'              => $dados['nome'],
                    'nome_perfil'       => $dados['nome_perfil'] ?? null,
                    'url_publica'       => $dados['url_publica'],
                    'plataforma'        => in_array($dados['plataforma'] ?? '', self::PLATAFORMAS, true) ? $dados['plataforma'] : 'instagram',
                    'descricao'         => $dados['descricao'] ?? null,
                    'categoria'         => $dados['categoria'] ?? null,
                    'frequencia_coleta' => in_array($dados['frequencia_coleta'] ?? '', self::FREQUENCIAS, true) ? $dados['frequencia_coleta'] : 'manual',
                    'max_posts'         => max(1, min(50, (int) ($dados['max_posts_por_coleta'] ?? 12))),
                    'principal'         => !empty($dados['principal']) ? 1 : 0,
                    'seguidores'        => isset($dados['seguidores']) && $dados['seguidores'] !== '' ? (int) $dados['seguidores'] : null,
                    'observacoes'       => $dados['observacoes'] ?? null,
                    'data_inicio'       => $dados['data_inicio_acompanhamento'] ?? date('Y-m-d'),
                ]
            );
            return $ok ? (int) Database::lastInsertId() : false;
        } catch (\Throwable $e) {
            Logger::error('Erro ao criar concorrente', ['empresa_id' => $empresaId, 'erro' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Atualiza um concorrente (restrito à empresa).
     */
    public static function atualizar(int $id, int $empresaId, array $dados): bool
    {
        $permitidas = [
            'nome', 'nome_perfil', 'url_publica', 'plataforma', 'descricao', 'categoria',
            'frequencia_coleta', 'max_posts_por_coleta', 'principal', 'status', 'seguidores',
            'observacoes',
        ];
        $sets = [];
        $params = ['id' => $id, 'empresa_id' => $empresaId];
        foreach ($permitidas as $campo) {
            if (!array_key_exists($campo, $dados)) continue;
            $sets[] = "{$campo} = :{$campo}";
            $params[$campo] = is_bool($dados[$campo]) ? ($dados[$campo] ? 1 : 0) : $dados[$campo];
        }
        if (empty($sets)) return false;

        try {
            return Database::execute(
                "UPDATE concorrentes SET " . implode(', ', $sets) . ", atualizado_em = NOW()
                 WHERE id = :id AND empresa_id = :empresa_id",
                $params
            );
        } catch (\Throwable $e) {
            Logger::error('Erro ao atualizar concorrente', ['id' => $id, 'erro' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Exclui um concorrente (e, por FK ON DELETE CASCADE, suas coletas/posts/análises).
     */
    public static function excluir(int $id, int $empresaId): bool
    {
        try {
            return Database::execute(
                "DELETE FROM concorrentes WHERE id = :id AND empresa_id = :empresa_id",
                ['id' => $id, 'empresa_id' => $empresaId]
            );
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Métricas resumidas por concorrente (nº de posts coletados, engajamento
     * médio, melhor post) para a listagem. Isolado por empresa.
     */
    public static function resumo(int $id, int $empresaId): array
    {
        $vazio = ['posts' => 0, 'engajamento_medio' => null, 'melhor_post' => null];
        try {
            $agg = Database::queryOne(
                "SELECT COUNT(*) AS posts, AVG(engajamento_absoluto) AS eng_medio
                 FROM concorrente_posts
                 WHERE concorrente_id = :id AND empresa_id = :empresa_id",
                ['id' => $id, 'empresa_id' => $empresaId]
            );
            $melhor = Database::queryOne(
                "SELECT titulo, url, engajamento_absoluto
                 FROM concorrente_posts
                 WHERE concorrente_id = :id AND empresa_id = :empresa_id AND engajamento_absoluto IS NOT NULL
                 ORDER BY engajamento_absoluto DESC LIMIT 1",
                ['id' => $id, 'empresa_id' => $empresaId]
            );
            return [
                'posts' => (int) ($agg['posts'] ?? 0),
                'engajamento_medio' => isset($agg['eng_medio']) && $agg['eng_medio'] !== null ? round((float) $agg['eng_medio']) : null,
                'melhor_post' => $melhor ?: null,
            ];
        } catch (\Throwable $e) {
            return $vazio;
        }
    }
}
