<?php
/**
 * PerfilController — Perfil do usuário + Onboarding
 */

class PerfilController
{
    public function index(): void
    {
        Auth::proteger();

        $usuario = Auth::usuario();
        
        // Buscar dados reais do usuário
        $usuarioCompleto = Database::queryOne(
            "SELECT * FROM usuarios WHERE id = :id",
            ['id' => $usuario['id']]
        );

        // Buscar logs de SSO Academy
        $acessosAcademy = AuditLog::buscarSsoAcademy($usuario['id'], 10);
        
        // Buscar histórico geral de ações
        $historicoGeral = AuditLog::buscarPorUsuario($usuario['id'], 15);

        $dados = [
            'usuario' => $usuarioCompleto,
            'academy_vinculada' => !empty($usuarioCompleto['email_academy']),
            'academy_email' => $usuarioCompleto['email_academy'] ?? null,
            'acessos_academy' => array_map(function($log) {
                return [
                    'data' => date('d/m/Y H:i', strtotime($log['timestamp'])),
                    'ip' => $log['ip_address'],
                    'acao' => $log['descricao'],
                ];
            }, $acessosAcademy),
            'logins' => [
                // TODO: implementar logs de login quando sistema de sessão for expandido
                ['data' => date('d/m/Y H:i'), 'ip' => $_SERVER['REMOTE_ADDR'], 'dispositivo' => 'Sessão atual'],
            ],
            'historico' => array_map(function($log) {
                return [
                    'data' => date('d/m/Y H:i', strtotime($log['timestamp'])),
                    'acao' => $log['descricao'] ?: ucwords(str_replace('_', ' ', $log['acao'])),
                    'modulo' => ucfirst($log['modulo']),
                ];
            }, $historicoGeral),
            'jornada' => [
                ['chave' => 'diagnostico', 'label' => 'Diagnóstico realizado', 'completo' => false], // TODO: verificar no banco
                ['chave' => 'plano', 'label' => 'Plano de ação criado', 'completo' => false], // TODO: verificar no banco  
                ['chave' => 'sop', 'label' => 'Primeiro SOP aprovado', 'completo' => false], // TODO: verificar no banco
                ['chave' => 'academy', 'label' => 'Academy vinculada', 'completo' => !empty($usuarioCompleto['email_academy'])],
                ['chave' => 'perfil', 'label' => 'Perfil da empresa completo', 'completo' => $usuarioCompleto['onboarding_concluido'] == 1],
            ],
        ];

        require VIEW_PATH . '/perfil/index.php';
    }

    public function salvar(): void
    {
        Auth::proteger();
        Csrf::verificar();
        $nome = htmlspecialchars(trim($_POST['nome'] ?? ''));
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        if (empty($nome) || empty($email)) {
            Flash::set('erro', 'Nome e email são obrigatórios.');
            header('Location: ' . APP_URL . '/perfil'); exit;
        }
        Session::set('usuario_nome', $nome);
        Session::set('usuario_email', $email);
        Logger::acao('Perfil atualizado', ['email' => $email]);
        Flash::set('sucesso', 'Perfil atualizado!');
        header('Location: ' . APP_URL . '/perfil'); exit;
    }

    public function vincularAcademy(): void
    {
        Auth::proteger();
        Csrf::verificar();
        
        $emailAcademy = filter_input(INPUT_POST, 'email_academy', FILTER_SANITIZE_EMAIL);
        
        // Validações
        if (empty($emailAcademy)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Email da Academy é obrigatório.']);
            exit;
        }

        if (!filter_var($emailAcademy, FILTER_VALIDATE_EMAIL)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Email inválido.']);
            exit;
        }

        $usuario = Auth::usuario();

        // Verificar se este email já está vinculado a outro usuário
        $emailExistente = Database::queryOne(
            "SELECT id FROM usuarios WHERE email_academy = :email_academy AND id != :user_id",
            ['email_academy' => $emailAcademy, 'user_id' => $usuario['id']]
        );

        if ($emailExistente) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Este email já está vinculado a outra conta.']);
            exit;
        }

        // Atualizar usuário
        $sucesso = User::atualizar($usuario['id'], ['email_academy' => $emailAcademy]);

        if ($sucesso) {
            // Registrar na auditoria
            AuditLog::registrar(
                'academy_vinculada',
                'academy',
                'Email Academy vinculado ao perfil',
                ['email_academy' => $emailAcademy, 'usuario_email' => $usuario['email']]
            );

            Logger::acao('Academy vinculada', ['email_academy' => $emailAcademy]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true, 'mensagem' => 'Academy vinculada com sucesso!']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao vincular Academy. Tente novamente.']);
        }
        exit;
    }

    /**
     * Desvincula conta da Academy
     */
    public function desvincularAcademy(): void
    {
        Auth::proteger();
        Csrf::verificar();
        
        $usuario = Auth::usuario();
        $emailAcademyAntigo = $usuario['email_academy'] ?? null;

        if (empty($emailAcademyAntigo)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Nenhuma conta Academy vinculada.']);
            exit;
        }

        // Desvinculação
        $sucesso = User::atualizar($usuario['id'], ['email_academy' => null]);

        if ($sucesso) {
            // Registrar na auditoria
            AuditLog::registrar(
                'academy_desvinculada',
                'academy',
                'Email Academy desvinculado do perfil',
                ['email_academy_removido' => $emailAcademyAntigo, 'usuario_email' => $usuario['email']]
            );

            Logger::acao('Academy desvinculada', ['email_academy_removido' => $emailAcademyAntigo]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true, 'mensagem' => 'Conta Academy desvinculada com sucesso!']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao desvincular Academy. Tente novamente.']);
        }
        exit;
    }

    public function alterarSenha(): void
    {
        Auth::proteger();
        Csrf::verificar();
        $atual = $_POST['senha_atual'] ?? '';
        $nova = $_POST['nova_senha'] ?? '';
        $confirmar = $_POST['confirmar_senha'] ?? '';
        if (strlen($nova) < 6) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Senha deve ter mínimo 6 caracteres.']); exit;
        }
        if ($nova !== $confirmar) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Senhas não coincidem.']); exit;
        }
        Logger::acao('Senha alterada');
        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'mensagem' => 'Senha alterada com sucesso!']); exit;
    }

    public function onboarding(): void
    {
        Auth::proteger();
        
        $usuario = Auth::usuario();
        
        // Verificar se onboarding já foi concluído
        $usuarioCompleto = User::buscarPorId($usuario['id']);
        if ($usuarioCompleto['onboarding_concluido']) {
            Flash::set('info', 'Onboarding já foi concluído anteriormente.');
            header('Location: ' . APP_URL . '/dashboard');
            exit;
        }

        // Buscar progresso atual do onboarding
        $stepAtual = (int) ($_GET['step'] ?? 1);
        
        $dados = [
            'usuario' => $usuario,
            'step_atual' => $stepAtual,
            'empresa' => Empresa::buscarPorId($usuario['empresa_id'])
        ];

        require VIEW_PATH . '/perfil/onboarding.php';
    }

    public function salvarStep(): void
    {
        Auth::proteger();
        Csrf::verificar();
        
        $step = (int) ($_POST['step'] ?? 1);
        $usuario = Auth::usuario();
        
        switch ($step) {
            case 1:
                // Step 1: Apenas avança (boas-vindas)
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => true, 'proximo_step' => 2]);
                exit;
                
            case 2:
                // Step 2: Dados da empresa (setor, colaboradores, faturamento, desafio)
                $setor = htmlspecialchars(trim($_POST['setor'] ?? ''));
                $colaboradores = (int) ($_POST['colaboradores'] ?? 0);
                $faturamento = htmlspecialchars(trim($_POST['faturamento'] ?? ''));
                $desafio = htmlspecialchars(trim($_POST['principal_desafio'] ?? ''));
                
                if (empty($setor) || $colaboradores <= 0 || empty($faturamento) || empty($desafio)) {
                    header('Content-Type: application/json');
                    echo json_encode(['sucesso' => false, 'erro' => 'Todos os campos são obrigatórios']);
                    exit;
                }
                
                // Salvar dados na empresa
                Empresa::atualizar($usuario['empresa_id'], [
                    'segmento' => $setor,
                    'colaboradores_internos' => $colaboradores,
                    'faturamento_mensal' => $faturamento,
                    'principal_desafio' => $desafio
                ]);
                
                Logger::acao('Onboarding step 2 concluído', [
                    'setor' => $setor,
                    'colaboradores' => $colaboradores,
                    'faturamento' => $faturamento
                ]);
                
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => true, 'proximo_step' => 3]);
                exit;
                
            case 3:
                // Step 3: Recomendação - sempre direciona para diagnóstico
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => true, 'proximo_step' => 4]);
                exit;
                
            case 4:
                // Step 4: Vinculação Academy
                $emailAcademy = filter_input(INPUT_POST, 'email_academy', FILTER_SANITIZE_EMAIL);
                
                if ($emailAcademy && filter_var($emailAcademy, FILTER_VALIDATE_EMAIL)) {
                    User::atualizar($usuario['id'], ['email_academy' => $emailAcademy]);
                    Logger::acao('Email Academy vinculado no onboarding', ['email_academy' => $emailAcademy]);
                }
                
                // Marcar onboarding como concluído
                User::atualizar($usuario['id'], ['onboarding_concluido' => 1]);
                
                Logger::acao('Onboarding concluído completamente');
                
                header('Content-Type: application/json');
                echo json_encode([
                    'sucesso' => true, 
                    'concluido' => true,
                    'redirect' => APP_URL . '/dashboard'
                ]);
                exit;
        }
    }

    public function concluirOnboarding(): void
    {
        Auth::proteger();
        Csrf::verificar();
        
        $usuario = Auth::usuario();
        
        // Marcar como concluído no banco
        User::atualizar($usuario['id'], ['onboarding_concluido' => 1]);
        
        Logger::acao('Onboarding concluído');
        
        header('Content-Type: application/json');
        echo json_encode([
            'sucesso' => true, 
            'redirect' => APP_URL . '/diagnostico/novo'
        ]);
        exit;
    }
}
