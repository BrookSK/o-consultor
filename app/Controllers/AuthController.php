<?php
/**
 * AuthController — Autenticação e cadastro
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
     * Processa o login
     */
    public function login(): void
    {
        Csrf::verificar();

        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $senha = $_POST['senha'] ?? '';
        $lembrar = isset($_POST['lembrar']);

        if (empty($email) || empty($senha)) {
            Flash::set('erro', 'Preencha todos os campos.');
            header('Location: ' . APP_URL . '/login');
            exit;
        }

        // Dados mockados para desenvolvimento
        $usuariosMock = [
            'admin@oconsultor.com.br' => [
                'id' => 1,
                'nome' => 'Administrador',
                'email' => 'admin@oconsultor.com.br',
                'senha' => password_hash('admin123', PASSWORD_DEFAULT),
                'perfil' => 'ADMIN_HOLDING',
                'empresa_id' => null,
            ],
            'consultor@oconsultor.com.br' => [
                'id' => 2,
                'nome' => 'João Consultor',
                'email' => 'consultor@oconsultor.com.br',
                'senha' => password_hash('consultor123', PASSWORD_DEFAULT),
                'perfil' => 'CONSULTOR_INTERNO',
                'empresa_id' => null,
            ],
            'cliente@empresa.com.br' => [
                'id' => 3,
                'nome' => 'Maria Cliente',
                'email' => 'cliente@empresa.com.br',
                'senha' => password_hash('cliente123', PASSWORD_DEFAULT),
                'perfil' => 'CLIENTE',
                'empresa_id' => 1,
            ],
        ];

        $usuario = $usuariosMock[$email] ?? null;

        if (!$usuario || !password_verify($senha, $usuario['senha'])) {
            Logger::seguranca('Tentativa de login falhou', ['email' => $email]);
            Flash::set('erro', 'Email ou senha incorretos.');
            header('Location: ' . APP_URL . '/login');
            exit;
        }

        Auth::login($usuario);
        Logger::acao('Login realizado', ['usuario_id' => $usuario['id']]);
        Flash::set('sucesso', 'Bem-vindo, ' . $usuario['nome'] . '!');
        header('Location: ' . APP_URL . '/dashboard');
        exit;
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
     * Processa o cadastro
     */
    public function cadastro(): void
    {
        Csrf::verificar();

        $nome = htmlspecialchars(trim($_POST['nome'] ?? ''));
        $empresa = htmlspecialchars(trim($_POST['empresa'] ?? ''));
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $senha = $_POST['senha'] ?? '';
        $confirmarSenha = $_POST['confirmar_senha'] ?? '';

        // Validações
        $erros = [];
        if (empty($nome)) $erros[] = 'Nome é obrigatório.';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = 'Email inválido.';
        if (strlen($senha) < 6) $erros[] = 'Senha deve ter no mínimo 6 caracteres.';
        if ($senha !== $confirmarSenha) $erros[] = 'As senhas não coincidem.';

        if (!empty($erros)) {
            Flash::set('erro', implode(' ', $erros));
            header('Location: ' . APP_URL . '/cadastro');
            exit;
        }

        // Em produção: User::criar([...])
        Logger::acao('Novo cadastro', ['email' => $email, 'empresa' => $empresa]);
        Flash::set('sucesso', 'Cadastro realizado com sucesso! Faça login para continuar.');
        header('Location: ' . APP_URL . '/login');
        exit;
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

        // Em produção: gerar token, enviar email
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
        Logger::acao('Logout realizado');
        Auth::logout();
        header('Location: ' . APP_URL . '/login');
        exit;
    }
}
