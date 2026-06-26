<?php
/**
 * Helper CSRF
 * Proteção contra Cross-Site Request Forgery
 */

class Csrf
{
    /**
     * Gera um token CSRF e armazena na sessão
     */
    public static function gerar(): string
    {
        $token = bin2hex(random_bytes(32));
        Session::set('csrf_token', $token);
        return $token;
    }

    /**
     * Retorna o token atual (ou gera um novo)
     */
    public static function token(): string
    {
        $token = Session::get('csrf_token');
        if (!$token) {
            $token = self::gerar();
        }
        return $token;
    }

    /**
     * Retorna o campo hidden HTML com o token
     */
    public static function campo(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::token()) . '">';
    }

    /**
     * Valida o token enviado no formulário
     */
    public static function validar(?string $tokenEnviado = null): bool
    {
        if ($tokenEnviado === null) {
            $tokenEnviado = $_POST['csrf_token'] ?? '';
        }

        $tokenSessao = Session::get('csrf_token');

        if (empty($tokenEnviado) || empty($tokenSessao)) {
            return false;
        }

        return hash_equals($tokenSessao, $tokenEnviado);
    }

    /**
     * Valida e aborta se inválido
     */
    public static function verificar(): void
    {
        if (!self::validar()) {
            http_response_code(403);
            die('Token CSRF inválido. Recarregue a página e tente novamente.');
        }
    }
}
