<?php
/**
 * AuthController — Autenticação e cadastro
 * Login e cadastro usando tabela `usuarios` do banco de dados.
 */

class AuthController
{
    /**
     * Exibe tela de login
     */
    public function showLogin(): void
    {
        if (Auth::check()) {
            header('Location: ' . APP_URL . '/dashboard');
            exit;
        }

        require VIEW_PATH . '/auth/login.php';
    }

    /**
     * Processa o login — busca no banco de dados
     */
    public function login(): void
    {
        Csrf::verificar();

        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $senha = $_POST['senha'] ?? '';

        if (empty($email) || empty($senha)) {
            Flash::set('erro', 'Preencha todos os campos.');
            header('Location: ' . APP_URL . '/login');
            exit;
        }

        // Buscar usuário no banco de dados com dados da empresa
        $usuario = Database::queryOne(
            "SELECT u.*, e.status as empresa_status, e.nome as empresa_nome 
             FROM usuarios u 
             LEFT JOIN empresas e ON u.empresa_id = e.id 
             WHERE u.email = :email LIMIT 1",
            ['email' => $email]
        );

        // Verificar credenciais - mensagem genérica sempre (não revelar se email existe)
        if (!$usuario || !password_verify($senha, $usuario['senha'])) {
            Logger::seguranca('Tentativa de login falhou', [
                'email' => $email,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            Flash::set('erro', 'Credenciais inválidas.');
            header('Location: ' . APP_URL . '/login');
            exit;
        }

        // Verificar se usuário está ativo
        if (isset($usuario['ativo']) && !$usuario['ativo']) {
            Flash::set('erro', 'Sua conta está desativada. Entre em contato com o administrador.');
            header('Location: ' . APP_URL . '/login');
            exit;
        }

        // F-13: Verificar status da empresa para clientes
        if ($usuario['perfil'] === 'CLIENTE' && isset($usuario['empresa_status'])) {
            if ($usuario['empresa_status'] === 'cancelado') {
                Logger::seguranca('Tentativa de acesso de empresa cancelada', [
                    'usuario_id' => $usuario['id'],
                    'email' => $email,
                    'empresa_id' => $usuario['empresa_id'],
                    'empresa_nome' => $usuario['empresa_nome'] ?? 'N/A'
                ]);
                Flash::set('erro', 'Sua conta foi desativada. Para reativar, entre em contato conosco.');
                header('Location: ' . APP_URL . '/login');
                exit;
            }
            
            if ($usuario['empresa_status'] === 'suspenso') {
                Flash::set('erro', 'Sua conta está temporariamente suspensa. Entre em contato com seu consultor.');
                header('Location: ' . APP_URL . '/login');
                exit;
            }
            
            if ($usuario['empresa_status'] === 'pausado') {
                Flash::set('aviso', 'Sua conta está pausada. Algumas funcionalidades podem estar limitadas.');
                // Permitir login mas com limitações
            }
        }

        // Login bem-sucedido - regenerar session_id por segurança
        Auth::login($usuario);
        
        Logger::acao('Login realizado', [
            'usuario_id' => $usuario['id'],
            'email' => $usuario['email'],
            'perfil' => $usuario['perfil'],
            'empresa_status' => $usuario['empresa_status'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        Flash::set('sucesso', 'Bem-vindo, ' . $usuario['nome'] . '!');

        // Redirecionar baseado no perfil do usuário
        $redirectUrl = $this->getRedirectUrlByRole($usuario['perfil']);
        
        // Verificar se há URL para redirecionamento após login
        $redirectAfterLogin = Session::get('redirect_after_login');
        if ($redirectAfterLogin) {
            Session::remove('redirect_after_login');
            $redirectUrl = $redirectAfterLogin;
        }
        
        header('Location: ' . $redirectUrl);
        exit;
    }

    /**
     * Determina URL de redirecionamento baseado no perfil
     */
    private function getRedirectUrlByRole(string $perfil): string
    {
        return match($perfil) {
            'ADMIN_HOLDING' => APP_URL . '/admin',
            'CONSULTOR_INTERNO' => APP_URL . '/dashboard', 
            'CLIENTE' => APP_URL . '/dashboard',
            default => APP_URL . '/dashboard'
        };
    }

    /**
     * Exibe tela de cadastro
     */
    public function showCadastro(): void
    {
        if (Auth::check()) {
            header('Location: ' . APP_URL . '/dashboard');
            exit;
        }

        require VIEW_PATH . '/auth/cadastro.php';
    }

    /**
     * Processa o cadastro — salva no banco de dados
     */
    public function cadastro(): void
    {
        Csrf::verificar();

        $nome = htmlspecialchars(trim($_POST['nome'] ?? ''));
        $empresa = htmlspecialchars(trim($_POST['empresa'] ?? ''));
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $senha = $_POST['senha'] ?? '';
        $confirmarSenha = $_POST['confirmar_senha'] ?? '';

        // Validações conforme especificação
        $erros = [];
        if (empty($nome)) $erros[] = 'Nome é obrigatório.';
        if (empty($empresa)) $erros[] = 'Nome da empresa é obrigatório.';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = 'Email inválido.';
        if (strlen($senha) < 8) $erros[] = 'Senha deve ter no mínimo 8 caracteres.';
        if ($senha !== $confirmarSenha) $erros[] = 'As senhas não coincidem.';

        // Verificar se email já existe no banco
        if (empty($erros)) {
            $existente = User::buscarPorEmail($email);
            if ($existente) {
                $erros[] = 'Este email já está cadastrado.';
            }
        }

        if (!empty($erros)) {
            Flash::set('erro', implode(' ', $erros));
            header('Location: ' . APP_URL . '/cadastro');
            exit;
        }

        try {
            // 1. Criar empresa primeiro
            $empresaId = Empresa::criar([
                'nome' => $empresa,
                'segmento' => '', // Será preenchido no onboarding
            ]);

            if (!$empresaId) {
                throw new Exception('Erro ao criar empresa');
            }

            // 2. Criar usuário vinculado à empresa
            $usuarioId = User::criar([
                'nome' => $nome,
                'email' => $email,
                'senha' => $senha, // User::criar já faz hash com PASSWORD_DEFAULT (BCRYPT)
                'perfil' => 'CLIENTE',
                'empresa_id' => $empresaId,
                'onboarding_concluido' => 0 // Campo para controle do onboarding
            ]);

            if (!$usuarioId) {
                throw new Exception('Erro ao criar usuário');
            }

            // 3. Buscar dados completos do usuário criado
            $usuarioCompleto = User::buscarPorId($usuarioId);
            
            // 4. Login automático após cadastro (conforme especificação)
            Auth::login($usuarioCompleto);

            Logger::acao('Novo cadastro realizado', [
                'usuario_id' => $usuarioId,
                'empresa_id' => $empresaId,
                'email' => $email, 
                'empresa' => $empresa
            ]);

            Flash::set('sucesso', 'Conta criada com sucesso! Bem-vindo, ' . $nome . '!');
            
            // 5. Redirecionar para onboarding (primeiro acesso)
            header('Location: ' . APP_URL . '/onboarding');
            exit;

        } catch (Exception $e) {
            Logger::erro('Erro no cadastro: ' . $e->getMessage());
            Flash::set('erro', 'Erro ao criar conta. Tente novamente.');
            header('Location: ' . APP_URL . '/cadastro');
            exit;
        }
    }

    /**
     * Exibe tela de recuperação de senha
     */
    public function showRecuperarSenha(): void
    {
        require VIEW_PATH . '/auth/recuperar-senha.php';
    }

    /**
     * Processa recuperação de senha
     */
    public function recuperarSenha(): void
    {
        Csrf::verificar();

        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Flash::set('erro', 'Informe um email válido.');
            header('Location: ' . APP_URL . '/recuperar-senha');
            exit;
        }

        Logger::acao('Recuperação de senha solicitada', ['email' => $email]);
        Flash::set('sucesso', 'Se o email estiver cadastrado, você receberá as instruções de recuperação.');
        header('Location: ' . APP_URL . '/login');
        exit;
    }

    /**
     * Logout
     */
    public function logout(): void
    {
        // Log do logout antes de destruir a sessão
        if (Auth::check()) {
            Logger::acao('Logout realizado', [
                'usuario_id' => Auth::usuario()['id'],
                'email' => Auth::usuario()['email']
            ]);
        }
        
        Auth::logout();
        Flash::set('sucesso', 'Você saiu com segurança.');
        header('Location: ' . APP_URL . '/login');
        exit;
    }
}
