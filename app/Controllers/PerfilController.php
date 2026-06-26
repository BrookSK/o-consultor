<?php
/**
 * PerfilController — Perfil do usuário + Onboarding
 */

class PerfilController
{
    public function index(): void
    {
        Auth::proteger();

        $dados = [
            'usuario' => Auth::usuario(),
            'academy_vinculada' => true, // mock
            'academy_email' => 'cliente@empresa.com.br',
            'acessos_academy' => [
                ['data' => '2026-06-26 09:15', 'ip' => '192.168.1.50'],
                ['data' => '2026-06-25 14:30', 'ip' => '192.168.1.50'],
                ['data' => '2026-06-22 10:00', 'ip' => '10.0.0.55'],
            ],
            'logins' => [
                ['data' => '2026-06-26 09:15', 'ip' => '192.168.1.50', 'dispositivo' => 'Chrome / Windows'],
                ['data' => '2026-06-25 14:30', 'ip' => '192.168.1.50', 'dispositivo' => 'Chrome / Windows'],
                ['data' => '2026-06-24 08:00', 'ip' => '10.0.0.55', 'dispositivo' => 'Safari / macOS'],
                ['data' => '2026-06-23 09:45', 'ip' => '192.168.1.50', 'dispositivo' => 'Chrome / Windows'],
                ['data' => '2026-06-22 10:00', 'ip' => '192.168.1.50', 'dispositivo' => 'Chrome / Windows'],
            ],
            'historico' => [
                ['data' => '2026-06-26 09:15', 'acao' => 'Login realizado', 'modulo' => 'Auth'],
                ['data' => '2026-06-25 16:00', 'acao' => 'SOP-TI-OPS-002 aprovado', 'modulo' => 'Manual Operacional'],
                ['data' => '2026-06-25 14:30', 'acao' => 'Acesso Academy via SSO', 'modulo' => 'Academy'],
                ['data' => '2026-06-24 15:00', 'acao' => 'Conteúdo carrossel gerado', 'modulo' => 'Máquina de Conteúdo'],
                ['data' => '2026-06-24 10:00', 'acao' => 'Tarefa atualizada: CRM', 'modulo' => 'Plano de Ação'],
                ['data' => '2026-06-23 09:00', 'acao' => 'Diagnóstico concluído', 'modulo' => 'Diagnóstico'],
            ],
            'jornada' => [
                ['chave' => 'diagnostico', 'label' => 'Diagnóstico realizado', 'completo' => true],
                ['chave' => 'plano', 'label' => 'Plano de ação criado', 'completo' => true],
                ['chave' => 'sop', 'label' => 'Primeiro SOP aprovado', 'completo' => true],
                ['chave' => 'academy', 'label' => 'Academy vinculada', 'completo' => true],
                ['chave' => 'perfil', 'label' => 'Perfil da empresa completo', 'completo' => false],
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
        Logger::acao('Academy vinculada', ['email_academy' => $emailAcademy]);
        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'mensagem' => 'Academy vinculada com sucesso!']);
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
        $dados = ['usuario' => Auth::usuario()];
        require VIEW_PATH . '/perfil/onboarding.php';
    }

    public function concluirOnboarding(): void
    {
        Auth::proteger();
        Csrf::verificar();
        Session::set('onboarding_concluido', true);
        Logger::acao('Onboarding concluído');
        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'redirect' => APP_URL . '/diagnostico/novo']);
        exit;
    }
}
