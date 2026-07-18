<?php
/**
 * Model Base — Conexão com banco de dados via PDO
 * Singleton para reutilizar a conexão
 */

class Database
{
    private static ?PDO $instancia = null;

    /**
     * Retorna a instância do PDO (Singleton)
     */
    public static function getConexao(): PDO
    {
        if (self::$instancia === null) {
            try {
                $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
                self::$instancia = new PDO($dsn, DB_USER, DB_PASS, DB_OPTIONS);
            } catch (PDOException $e) {
                Logger::error('Erro na conexão com o banco de dados', ['erro' => $e->getMessage()]);
                die('Erro na conexão com o banco de dados. Verifique as configurações.');
            }
        }

        return self::$instancia;
    }

    /**
     * Executa uma query preparada e retorna todos os resultados
     */
    public static function query(string $sql, array $params = []): array
    {
        $stmt = self::getConexao()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Executa uma query preparada e retorna uma linha
     */
    public static function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = self::getConexao()->prepare($sql);
        $stmt->execute($params);
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    /**
     * Executa uma query (INSERT, UPDATE, DELETE) e retorna sucesso
     */
    public static function execute(string $sql, array $params = []): bool
    {
        $stmt = self::getConexao()->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Executa uma query (UPDATE/DELETE) e retorna o número de linhas afetadas.
     * Usado para "claim" atômico de itens de fila em execução concorrente.
     */
    public static function executeAffected(string $sql, array $params = []): int
    {
        $stmt = self::getConexao()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Retorna o último ID inserido
     */
    public static function lastInsertId(): string
    {
        return self::getConexao()->lastInsertId();
    }

    /**
     * Inicia uma transação
     */
    public static function beginTransaction(): bool
    {
        return self::getConexao()->beginTransaction();
    }

    /**
     * Confirma uma transação
     */
    public static function commit(): bool
    {
        return self::getConexao()->commit();
    }

    /**
     * Reverte uma transação
     */
    public static function rollback(): bool
    {
        return self::getConexao()->rollBack();
    }
}
