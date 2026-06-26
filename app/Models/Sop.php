<?php
/**
 * Model SOP — Standard Operating Procedures (Manual Operacional)
 */

class Sop
{
    /**
     * Buscar por ID
     */
    public static function buscarPorId(int $id): ?array
    {
        return Database::queryOne(
            "SELECT * FROM sops WHERE id = :id LIMIT 1",
            ['id' => $id]
        );
    }

    /**
     * Buscar por empresa
     */
    public static function buscarPorEmpresa(int $empresaId): array
    {
        return Database::query(
            "SELECT * FROM sops WHERE empresa_id = :empresa_id ORDER BY criado_em DESC",
            ['empresa_id' => $empresaId]
        );
    }

    /**
     * Criar novo SOP
     */
    public static function criar(array $dados): int|false
    {
        $sucesso = Database::execute(
            "INSERT INTO sops (empresa_id, titulo, departamento, conteudo, gerado_por_ia, criado_em) 
             VALUES (:empresa_id, :titulo, :departamento, :conteudo, :gerado_por_ia, NOW())",
            [
                'empresa_id'   => $dados['empresa_id'],
                'titulo'       => $dados['titulo'],
                'departamento' => $dados['departamento'] ?? null,
                'conteudo'     => $dados['conteudo'],
                'gerado_por_ia' => $dados['gerado_por_ia'] ?? 0,
            ]
        );

        return $sucesso ? (int) Database::lastInsertId() : false;
    }
}
