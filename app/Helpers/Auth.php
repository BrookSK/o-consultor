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

    /** E-mail da conta de demonstração (dados mockup, sem config de admin). */
    const DEMO_EMAIL = 'demo@oconsultor.com.br';

    /**
     * Indica se a sessão atual é a conta de demonstração.
     * A conta demo enxerga todas as abas (exceto Configurações do admin) com
     * dados mockup, mas não pode alterar configurações do sistema.
     */
    public static function isDemo(): bool
    {
        return strtolower((string) Session::get('usuario_email')) === self::DEMO_EMAIL;
    }

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

        // Conta demo (ADMIN_HOLDING): fixa a própria empresa demo como
        // "empresa selecionada", para que as telas de conteúdo (que dependem de
        // Auth::empresa()) exibam os dados mockup dessa empresa automaticamente.
        if (strtolower((string) $usuario['email']) === self::DEMO_EMAIL && !empty($usuario['empresa_id'])) {
            Session::set('admin_empresa_selecionada', (int) $usuario['empresa_id']);
        }
        
        // Atualizar último login no banco
        try {
            Database::execute(
                "UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?", 
                [$usuario['id']]
            );
        } catch (Exception $e) {
            // Log do erro mas não impedir o login
            Logger::error('Erro ao atualizar ultimo_login: ' . $e->getMessage());
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
     * Indica se o usuário atual está impersonando (acessando como) outro usuário
     */
    public static function impersonando(): bool
    {
        return Session::has('impersonador_id');
    }

    /**
     * Nome do administrador que iniciou a impersonação
     */
    public static function impersonadorNome(): ?string
    {
        return Session::get('impersonador_nome');
    }

    /**
     * Inicia a impersonação: guarda os dados do usuário atual (admin) e
     * assume a identidade do usuário informado, sem destruir a sessão.
     */
    public static function impersonar(array $usuario): void
    {
        // Só grava o backup na primeira vez (evita empilhar impersonações)
        if (!self::impersonando()) {
            Session::set('impersonador_id', Session::get('usuario_id'));
            Session::set('impersonador_nome', Session::get('usuario_nome'));
            Session::set('impersonador_email', Session::get('usuario_email'));
            Session::set('impersonador_perfil', Session::get('usuario_perfil'));
            Session::set('impersonador_empresa_id', Session::get('usuario_empresa_id'));
        }

        Session::set('usuario_id', $usuario['id']);
        Session::set('usuario_nome', $usuario['nome']);
        Session::set('usuario_email', $usuario['email']);
        Session::set('usuario_perfil', $usuario['perfil']);
        Session::set('usuario_empresa_id', $usuario['empresa_id'] ?? null);

        // Limpa qualquer seleção de empresa feita como admin
        Session::remove('admin_empresa_selecionada');
    }

    /**
     * Encerra a impersonação e restaura a identidade original do administrador
     */
    public static function pararImpersonacao(): void
    {
        if (!self::impersonando()) {
            return;
        }

        Session::set('usuario_id', Session::get('impersonador_id'));
        Session::set('usuario_nome', Session::get('impersonador_nome'));
        Session::set('usuario_email', Session::get('impersonador_email'));
        Session::set('usuario_perfil', Session::get('impersonador_perfil'));
        Session::set('usuario_empresa_id', Session::get('impersonador_empresa_id'));

        Session::remove('impersonador_id');
        Session::remove('impersonador_nome');
        Session::remove('impersonador_email');
        Session::remove('impersonador_perfil');
        Session::remove('impersonador_empresa_id');
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
     * Para ADMIN_HOLDING, permite acesso global
     */
    public static function empresa(): ?int
    {
        // ADMIN_HOLDING tem acesso global - pode gerenciar qualquer empresa
        if (self::isAdmin()) {
            // Se não há empresa específica na sessão, pode retornar null (acesso total)
            return Session::get('admin_empresa_selecionada');
        }
        
        $empresaId = Session::get('usuario_empresa_id');
        return $empresaId ? (int) $empresaId : null;
    }

    /**
     * Define qual empresa o ADMIN_HOLDING quer gerenciar temporariamente
     */
    public static function selecionarEmpresa(?int $empresaId): void
    {
        if (self::isAdmin()) {
            Session::set('admin_empresa_selecionada', $empresaId);
        }
    }

    /**
     * Retorna o ID da empresa para operações que exigem empresa específica
     * Para ADMIN_HOLDING, retorna a primeira empresa disponível se não selecionada
     */
    public static function empresaObrigatoria(): ?int
    {
        $empresaId = self::empresa();
        
        // Se é ADMIN_HOLDING e não tem empresa selecionada, pegar a primeira disponível
        if (self::isAdmin() && !$empresaId) {
            try {
                $primeiraEmpresa = Database::queryOne("SELECT id FROM empresas ORDER BY id ASC LIMIT 1");
                return $primeiraEmpresa ? (int) $primeiraEmpresa['id'] : null;
            } catch (Exception $e) {
                return null;
            }
        }
        
        return $empresaId;
    }

    /**
     * Retorna o ID do usuário logado
     */
    public static function usuarioId(): ?int
    {
        $usuarioId = Session::get('usuario_id');
        return $usuarioId ? (int) $usuarioId : null;
    }

    /**
     * Alias for usuarioId() for convenience
     */
    public static function id(): ?int
    {
        return self::usuarioId();
    }
    /**
     * Middleware para garantir acesso a empresa (ADMIN_HOLDING tem acesso global)
     * Retorna empresa_id ou redireciona com erro
     */
    public static function garantirEmpresa(): int
    {
        // Para ADMIN_HOLDING, sempre permitir acesso (pegar primeira empresa se necessário)
        if (self::isAdmin()) {
            $empresaId = self::empresaObrigatoria();
            if (!$empresaId) {
                Flash::set('erro', 'Nenhuma empresa encontrada no sistema.');
                header('Location: ' . APP_URL . '/admin');
                exit;
            }
            return $empresaId;
        }
        
        // Para outros perfis, empresa deve estar definida
        $empresaId = self::empresa();
        if (!$empresaId) {
            Flash::set('erro', 'Empresa não identificada.');
            header('Location: ' . APP_URL . '/dashboard');
            exit;
        }
        
        return $empresaId;
    }
    /**
     * Verifica se o usuário tem permissão para acessar um recurso de uma empresa
     * ADMIN_HOLDING tem acesso a todas as empresas
     */
    public static function podeAcessarEmpresa(int $empresaId): bool
    {
        // ADMIN_HOLDING pode acessar qualquer empresa
        if (self::isAdmin()) {
            return true;
        }
        
        // Outros perfis só podem acessar sua própria empresa
        return self::empresa() === $empresaId;
    }
}