<?php
/**
 * Helper de Autenticação
 * Controle de login, permissões e proteção de rotas
 */

class Auth
{
    /**
     * Perfis disponíveis no sistema
     */
    const ADMIN_HOLDING = 'ADMIN_HOLDING';
    const CONSULTOR_INTERNO = 'CONSULTOR_INTERNO';
    const CLIENTE = 'CLIENTE';

    /**
     * Verifica se o usuário está autenticado
     */
    public static function check(): bool
    {
        return Session::has('usuario_id');
    }

    /**
     * Retorna o usuário logado
     */
    public static function usuario(): ?array
    {
        if (!self::check()) {
            return null;
        }

        return [
            'id' => Session::get('usuario_id'),
            'nome' => Session::get('usuario_nome'),
            'email' => Session::get('usuario_email'),
            'perfil' => Session::get('usuario_perfil'),
            'empresa_id' => Session::get('usuario_empresa_id'),
        ];
    }

    /**
     * Realiza o login do usuário (salva dados na sessão)
     */
    public static function login(array $usuario): void
    {
        Session::regenerar();
        Session::set('usuario_id', $usuario['id']);
        Session::set('usuario_nome', $usuario['nome']);
        Session::set('usuario_email', $usuario['email']);
        Session::set('usuario_perfil', $usuario['perfil']);
        Session::set('usuario_empresa_id', $usuario['empresa_id'] ?? null);
        Session::set('login_time', time());
    }

    /**
     * Realiza o logout
     */
    public static function logout(): void
    {
        Session::destroy();
    }

    /**
     * Protege uma rota — redireciona para login se não autenticado
     */
    public static function proteger(): void
    {
        if (!self::check()) {
            Flash::set('erro', 'Você precisa estar logado para acessar esta página.');
            header('Location: ' . APP_URL . '/login');
            exit;
        }
    }

    /**
     * Verifica se o usuário tem um perfil específico
     */
    public static function temPerfil(string $perfil): bool
    {
        return Session::get('usuario_perfil') === $perfil;
    }

    /**
     * Verifica se o usuário tem um dos perfis informados
     */
    public static function temAlgumPerfil(array $perfis): bool
    {
        return in_array(Session::get('usuario_perfil'), $perfis);
    }

    /**
     * Protege rota por perfil — redireciona se não autorizado
     */
    public static function exigirPerfil(array $perfisPermitidos): void
    {
        self::proteger();

        if (!self::temAlgumPerfil($perfisPermitidos)) {
            Flash::set('erro', 'Você não tem permissão para acessar esta página.');
            header('Location: ' . APP_URL . '/dashboard');
            exit;
        }
    }

    /**
     * Retorna o perfil do usuário logado
     */
    public static function perfil(): ?string
    {
        return Session::get('usuario_perfil');
    }

    /**
     * Verifica se é Admin Holding
     */
    public static function isAdmin(): bool
    {
        return self::temPerfil(self::ADMIN_HOLDING);
    }

    /**
     * Verifica se é Consultor Interno
     */
    public static function isConsultor(): bool
    {
        return self::temPerfil(self::CONSULTOR_INTERNO);
    }

    /**
     * Verifica se é Cliente
     */
    public static function isCliente(): bool
    {
        return self::temPerfil(self::CLIENTE);
    }
}
