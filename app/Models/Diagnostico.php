<?php
/**
 * Model Diagnostico — Diagnósticos empresariais
 */

class Diagnostico
{
    /**
     * Buscar por ID
     */
    public static function buscarPorId(int $id): ?array
    {
        return Database::queryOne(
            "SELECT * FROM diagnosticos WHERE id = :id LIMIT 1",
            ['id' => $id]
        );
    }

    /**
     * Buscar por empresa
     */
    public static function buscarPorEmpresa(int $empresaId): array
    {
        return Database::query(
            "SELECT * FROM diagnosticos WHERE empresa_id = :empresa_id ORDER BY criado_em DESC",
            ['empresa_id' => $empresaId]
        );
    }

    /**
     * Criar novo diagnóstico
     */
    public static function criar(array $dados): int|false
    {
        $sucesso = Database::execute(
            "INSERT INTO diagnosticos (empresa_id, usuario_id, respostas, pontuacao, status, criado_em) 
             VALUES (:empresa_id, :usuario_id, :respostas, :pontuacao, :status, NOW())",
            [
                'empresa_id' => $dados['empresa_id'],
                'usuario_id' => $dados['usuario_id'],
                'respostas'  => json_encode($dados['respostas'] ?? []),
                'pontuacao'  => $dados['pontuacao'] ?? 0,
                'status'     => $dados['status'] ?? 'em_andamento',
            ]
        );

        return $sucesso ? (int) Database::lastInsertId() : false;
    }

    /**
     * Atualizar diagnóstico
     */
    public static function atualizar(int $id, array $dados): bool
    {
        return Database::execute(
            "UPDATE diagnosticos SET respostas = :respostas, pontuacao = :pontuacao, status = :status, atualizado_em = NOW() WHERE id = :id",
            [
                'id'        => $id,
                'respostas' => json_encode($dados['respostas']),
                'pontuacao' => $dados['pontuacao'],
                'status'    => $dados['status'],
            ]
        );
    }
}
