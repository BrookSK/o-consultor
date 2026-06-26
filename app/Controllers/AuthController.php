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

        // Buscar usuário no banco de dados
        $usuario = User::buscarPorEmail($email);

        if (!$usuario || !password_verify($senha, $usuario['senha'])) {
            Logger::seguranca('Tentativa de login falhou', ['email' => $email]);
            Flash::set('erro', 'Email ou senha incorretos.');
            header('Location: ' . APP_URL . '/login');
            exit;
        }

        // Verificar se está ativo
        if (isset($usuario['ativo']) && !$usuario['ativo']) {
            Flash::set('erro', 'Sua conta está desativada. Entre em contato com o administrador.');
            header('Location: ' . APP_URL . '/login');
            exit;
        }

        // Login bem-sucedido
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

        // Validações
        $erros = [];
        if (empty($nome)) $erros[] = 'Nome é obrigatório.';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = 'Email inválido.';
        if (strlen($senha) < 6) $erros[] = 'Senha deve ter no mínimo 6 caracteres.';
        if ($senha !== $confirmarSenha) $erros[] = 'As senhas não coincidem.';

        // Verificar se email já existe
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

        // Criar usuário no banco
        $novoId = User::criar([
            'nome' => $nome,
            'email' => $email,
            'senha' => $senha,
            'perfil' => 'CLIENTE',
            'empresa_id' => null,
        ]);

        if ($novoId) {
            Logger::acao('Novo cadastro realizado', ['email' => $email, 'empresa' => $empresa]);
            Flash::set('sucesso', 'Cadastro realizado com sucesso! Faça login para continuar.');
        } else {
            Flash::set('erro', 'Erro ao criar conta. Tente novamente.');
        }

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
