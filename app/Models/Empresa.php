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
     * Atualizar empresa por ID
     */
    public static function atualizar(int $id, array $dados): bool
    {
        $campos = [];
        $parametros = ['id' => $id];
        
        if (isset($dados['nome'])) {
            $campos[] = 'nome = :nome';
            $parametros['nome'] = $dados['nome'];
        }
        
        if (isset($dados['cnpj'])) {
            $campos[] = 'cnpj = :cnpj';
            $parametros['cnpj'] = $dados['cnpj'];
        }
        
        if (isset($dados['segmento'])) {
            $campos[] = 'segmento = :segmento';
            $parametros['segmento'] = $dados['segmento'];
        }
        
        if (isset($dados['colaboradores_internos'])) {
            $campos[] = 'colaboradores_internos = :colaboradores_internos';
            $parametros['colaboradores_internos'] = $dados['colaboradores_internos'];
        }
        
        if (isset($dados['faturamento_mensal'])) {
            $campos[] = 'faturamento_mensal = :faturamento_mensal';
            $parametros['faturamento_mensal'] = $dados['faturamento_mensal'];
        }
        
        if (isset($dados['principal_desafio'])) {
            $campos[] = 'principal_desafio = :principal_desafio';
            $parametros['principal_desafio'] = $dados['principal_desafio'];
        }
        
        if (empty($campos)) {
            return true; // Nada para atualizar
        }
        
        $sql = "UPDATE empresas SET " . implode(', ', $campos) . ", atualizado_em = NOW() WHERE id = :id";
        
        return Database::execute($sql, $parametros);
    }

    /**
     * Listar todas as empresas
     */
    public static function listar(): array
    {
        return Database::query("SELECT * FROM empresas ORDER BY nome ASC");
    }
}
