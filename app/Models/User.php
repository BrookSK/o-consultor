<?php
/**
 * Model User — Gerenciamento de usuários
 */

class User
{
    /**
     * Busca usuário por email
     */
    public static function buscarPorEmail(string $email): ?array
    {
        return Database::queryOne(
            "SELECT * FROM usuarios WHERE email = :email LIMIT 1",
            ['email' => $email]
        );
    }

    /**
     * Busca usuário por ID
     */
    public static function buscarPorId(int $id): ?array
    {
        return Database::queryOne(
            "SELECT * FROM usuarios WHERE id = :id LIMIT 1",
            ['id' => $id]
        );
    }

    /**
     * Criar novo usuário
     */
    public static function criar(array $dados): int|false
    {
        $sucesso = Database::execute(
            "INSERT INTO usuarios (nome, email, senha, perfil, empresa_id, criado_em) 
             VALUES (:nome, :email, :senha, :perfil, :empresa_id, NOW())",
            [
                'nome'       => $dados['nome'],
                'email'      => $dados['email'],
                'senha'      => password_hash($dados['senha'], PASSWORD_DEFAULT),
                'perfil'     => $dados['perfil'] ?? 'CLIENTE',
                'empresa_id' => $dados['empresa_id'] ?? null,
            ]
        );

        return $sucesso ? (int) Database::lastInsertId() : false;
    }

    /**
     * Atualizar dados do usuário
     */
    public static function atualizar(int $id, array $dados): bool
    {
        $campos = [];
        $params = ['id' => $id];

        foreach (['nome', 'email', 'perfil', 'empresa_id'] as $campo) {
            if (isset($dados[$campo])) {
                $campos[] = "{$campo} = :{$campo}";
                $params[$campo] = $dados[$campo];
            }
        }

        if (isset($dados['senha']) && !empty($dados['senha'])) {
            $campos[] = "senha = :senha";
            $params['senha'] = password_hash($dados['senha'], PASSWORD_DEFAULT);
        }

        if (empty($campos)) {
            return false;
        }

        $sql = "UPDATE usuarios SET " . implode(', ', $campos) . ", atualizado_em = NOW() WHERE id = :id";
        return Database::execute($sql, $params);
    }

    /**
     * Listar todos os usuários
     */
    public static function listar(int $limite = 50, int $offset = 0): array
    {
        return Database::query(
            "SELECT id, nome, email, perfil, empresa_id, criado_em FROM usuarios ORDER BY criado_em DESC LIMIT :limite OFFSET :offset",
            ['limite' => $limite, 'offset' => $offset]
        );
    }

    /**
     * Verificar senha
     */
    public static function verificarSenha(string $senha, string $hash): bool
    {
        return password_verify($senha, $hash);
    }
}
