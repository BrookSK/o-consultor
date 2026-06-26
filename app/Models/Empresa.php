<?php
/**
 * Model Empresa — Gerenciamento de empresas clientes
 */

class Empresa
{
    /**
     * Buscar empresa por ID
     */
    public static function buscarPorId(int $id): ?array
    {
        return Database::queryOne(
            "SELECT * FROM empresas WHERE id = :id LIMIT 1",
            ['id' => $id]
        );
    }

    /**
     * Criar nova empresa
     */
    public static function criar(array $dados): int|false
    {
        $sucesso = Database::execute(
            "INSERT INTO empresas (nome, cnpj, segmento, responsavel_id, criado_em) 
             VALUES (:nome, :cnpj, :segmento, :responsavel_id, NOW())",
            [
                'nome'           => $dados['nome'],
                'cnpj'           => $dados['cnpj'] ?? null,
                'segmento'       => $dados['segmento'] ?? null,
                'responsavel_id' => $dados['responsavel_id'] ?? null,
            ]
        );

        return $sucesso ? (int) Database::lastInsertId() : false;
    }

    /**
     * Listar todas as empresas
     */
    public static function listar(): array
    {
        return Database::query("SELECT * FROM empresas ORDER BY nome ASC");
    }
}
