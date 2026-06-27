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
        // Regenerar session_id por segurança (previne session fixation)
        Session::regenerar();
        
        Session::set('usuario_id', $usuario['id']);
        Session::set('usuario_nome', $usuario['nome']);
        Session::set('usuario_email', $usuario['email']);
        Session::set('usuario_perfil', $usuario['perfil']);
        Session::set('usuario_empresa_id', $usuario['empresa_id'] ?? null);
        Session::set('login_time', time());
        
        // Atualizar último login no banco
        try {
            Database::execute(
                "UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?", 
                [$usuario['id']]
            );
        } catch (Exception $e) {
            // Log do erro mas não impedir o login
            Logger::erro('Erro ao atualizar ultimo_login: ' . $e->getMessage());
        }
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
            // Salvar URL atual para redirecionamento após login
            $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
            if (!empty($currentUrl) && $currentUrl !== '/login' && $currentUrl !== '/logout') {
                Session::set('redirect_after_login', APP_URL . $currentUrl);
            }
            
            Flash::set('erro', 'Faça login para continuar.');
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
            // Log da tentativa de acesso não autorizado
            Logger::seguranca('Tentativa de acesso não autorizado', [
                'usuario_id' => self::usuario()['id'],
                'email' => self::usuario()['email'],
                'perfil' => self::perfil(),
                'perfis_permitidos' => $perfisPermitidos,
                'url_tentada' => $_SERVER['REQUEST_URI'] ?? '',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            // Retornar 403 Forbidden
            http_response_code(403);
            require VIEW_PATH . '/errors/403.php';
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

    /**
     * Retorna o ID da empresa do usuário logado
     */
    public static function empresa(): ?int
    {
        $empresaId = Session::get('usuario_empresa_id');
        return $empresaId ? (int) $empresaId : null;
    }

    /**
     * Retorna o ID do usuário logado
     */
    public static function usuarioId(): ?int
    {
        $usuarioId = Session::get('usuario_id');
        return $usuarioId ? (int) $usuarioId : null;
    }
}
