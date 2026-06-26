<?php
/**
 * Helper de Sessão
 * Gerenciamento de sessões PHP nativas
 */

class Session
{
    /**
     * Define um valor na sessão
     */
    public static function set(string $chave, mixed $valor): void
    {
        $_SESSION[$chave] = $valor;
    }

    /**
     * Obtém um valor da sessão
     */
    public static function get(string $chave, mixed $padrao = null): mixed
    {
        return $_SESSION[$chave] ?? $padrao;
    }

    /**
     * Verifica se uma chave existe na sessão
     */
    public static function has(string $chave): bool
    {
        return isset($_SESSION[$chave]);
    }

    /**
     * Remove um valor da sessão
     */
    public static function remove(string $chave): void
    {
        unset($_SESSION[$chave]);
    }

    /**
     * Destrói toda a sessão
     */
    public static function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    /**
     * Regenera o ID da sessão (segurança)
     */
    public static function regenerar(): void
    {
        session_regenerate_id(true);
    }
}
