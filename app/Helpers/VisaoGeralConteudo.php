<?php
/**
 * Helper VisaoGeralConteudo — Painel resumido da Central (spec §5)
 *
 * Consolida indicadores dos módulos de conteúdo de uma empresa: conteúdos
 * planejados/gerados/em revisão, próximas datas, notícias recentes,
 * concorrentes monitorados, última coleta, melhores conteúdos de concorrentes
 * e alertas (Brand Book incompleto, coletas com falha).
 *
 * Todos os acessos são tolerantes à ausência de tabelas (migração 054) e
 * isolados por empresa. Serve apenas como resumo; não substitui as abas.
 */

class VisaoGeralConteudo
{
    public static function montar(int $empresaId): array
    {
        return [
            'contadores'          => self::contadores($empresaId),
            'proximas_datas'      => self::proximasDatas($empresaId),
            'noticias_recentes'   => self::noticiasRecentes($empresaId),
            'concorrentes'        => self::concorrentesResumo($empresaId),
            'melhores_concorrentes' => self::melhoresConteudosConcorrentes($empresaId),
            'alertas'             => self::alertas($empresaId),
        ];
    }

    private static function scalar(string $sql, array $params, string $coluna = 'total'): int
    {
        try {
            return (int) (Database::queryOne($sql, $params)[$coluna] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private static function contadores(int $empresaId): array
    {
        // Conteúdos gerados/planejados vêm do calendário + conteudos_marca.
        $planejados = self::scalar(
            "SELECT COUNT(*) AS total FROM calendario_conteudo WHERE empresa_id = :e AND status IN ('sugerido','planejado')",
            ['e' => $empresaId]
        );
        $gerados = self::scalar(
            "SELECT COUNT(*) AS total FROM conteudos_marca c JOIN marcas m ON c.marca_id = m.id WHERE m.empresa_id = :e",
            ['e' => $empresaId]
        );
        $emRevisao = self::scalar(
            "SELECT COUNT(*) AS total FROM conteudos_marca c JOIN marcas m ON c.marca_id = m.id WHERE m.empresa_id = :e AND c.status = 'rascunho'",
            ['e' => $empresaId]
        );

        return [
            'planejados' => $planejados,
            'gerados' => $gerados,
            'em_revisao' => $emRevisao,
        ];
    }

    private static function proximasDatas(int $empresaId): array
    {
        try {
            return array_slice(DataComemorativa::proximas($empresaId, 45, false), 0, 5);
        } catch (\Throwable $e) {
            return [];
        }
    }

    private static function noticiasRecentes(int $empresaId): array
    {
        try {
            return Database::query(
                "SELECT id, titulo, data_publicacao AS data FROM noticias
                 WHERE empresa_id = :e AND arquivada = 0
                 ORDER BY data_publicacao DESC, criado_em DESC LIMIT 5",
                ['e' => $empresaId]
            );
        } catch (\Throwable $e) {
            return [];
        }
    }

    private static function concorrentesResumo(int $empresaId): array
    {
        try {
            $total = self::scalar("SELECT COUNT(*) AS total FROM concorrentes WHERE empresa_id = :e", ['e' => $empresaId]);
            $ultima = Database::queryOne(
                "SELECT MAX(ultima_coleta_em) AS ultima FROM concorrentes WHERE empresa_id = :e",
                ['e' => $empresaId]
            );
            return [
                'total' => $total,
                'ultima_coleta' => $ultima['ultima'] ?? null,
            ];
        } catch (\Throwable $e) {
            return ['total' => 0, 'ultima_coleta' => null];
        }
    }

    private static function melhoresConteudosConcorrentes(int $empresaId): array
    {
        try {
            return Database::query(
                "SELECT p.titulo, p.tipo_conteudo, p.engajamento_absoluto, c.nome AS concorrente
                 FROM concorrente_posts p JOIN concorrentes c ON p.concorrente_id = c.id
                 WHERE p.empresa_id = :e AND p.engajamento_absoluto IS NOT NULL
                 ORDER BY p.engajamento_absoluto DESC LIMIT 5",
                ['e' => $empresaId]
            );
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Alertas: Brand Book incompleto e concorrentes com falha de coleta.
     */
    private static function alertas(int $empresaId): array
    {
        $alertas = [];

        // Brand Book incompleto (spec §5): marca sem prompt_master/nicho.
        try {
            $marca = Database::queryOne(
                "SELECT nicho, publico_alvo, prompt_master, tom FROM marcas WHERE empresa_id = :e AND ativo = 1 ORDER BY id LIMIT 1",
                ['e' => $empresaId]
            );
            if (!$marca) {
                $alertas[] = ['tipo' => 'brand_book', 'nivel' => 'alerta', 'mensagem' => 'Nenhuma marca (Brand Book) cadastrada. A geração de conteúdo depende do Brand Book.'];
            } else {
                $faltando = [];
                if (empty($marca['nicho'])) $faltando[] = 'nicho';
                if (empty($marca['publico_alvo'])) $faltando[] = 'público-alvo';
                if (empty($marca['tom'])) $faltando[] = 'tom de voz';
                if (empty($marca['prompt_master'])) $faltando[] = 'prompt mestre';
                if (!empty($faltando)) {
                    $alertas[] = ['tipo' => 'brand_book', 'nivel' => 'aviso', 'mensagem' => 'Brand Book incompleto: faltam ' . implode(', ', $faltando) . '.'];
                }
            }
        } catch (\Throwable $e) { /* opcional */ }

        // Concorrentes com falhas de coleta (spec §5).
        try {
            $comFalha = Database::query(
                "SELECT nome, falhas_consecutivas FROM concorrentes
                 WHERE empresa_id = :e AND falhas_consecutivas > 0
                 ORDER BY falhas_consecutivas DESC LIMIT 5",
                ['e' => $empresaId]
            );
            foreach ($comFalha as $c) {
                $nivel = ((int) $c['falhas_consecutivas'] >= 5) ? 'alerta' : 'aviso';
                $alertas[] = [
                    'tipo' => 'coleta',
                    'nivel' => $nivel,
                    'mensagem' => 'Concorrente "' . $c['nome'] . '" com ' . (int) $c['falhas_consecutivas'] . ' falha(s) de coleta.',
                ];
            }
        } catch (\Throwable $e) { /* opcional */ }

        return $alertas;
    }
}
