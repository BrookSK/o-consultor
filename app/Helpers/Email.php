<?php
/**
 * Email Helper — Envio de emails via SMTP
 * O Consultor — Sistema Operacional Empresarial
 *
 * Todas as configurações SMTP são lidas do banco (tabela configuracoes).
 * Gerenciadas pela tela /admin/configuracoes > aba Email/SMTP.
 * Usa fsockopen/stream_socket para SMTP puro (sem dependência externa).
 */

class Email
{
    /**
     * Envia um email via SMTP
     *
     * @param string $para Email do destinatário
     * @param string $assunto Assunto do email
     * @param string $corpo Corpo HTML do email
     * @param string $nomePara Nome do destinatário (opcional)
     * @param string $tipo Tipo para log (recuperacao_senha, convite_academy, etc.)
     * @return array ['sucesso' => bool, 'erro' => string|null]
     */
    public static function enviar(string $para, string $assunto, string $corpo, string $nomePara = '', string $tipo = 'outro'): array
    {
        // Verificar se SMTP está ativo
        if (Configuracao::get('smtp_ativo', '0') !== '1') {
            return ['sucesso' => false, 'erro' => 'Envio de email desativado. Ative em Admin > Configurações > Email.'];
        }

        $host = Configuracao::get('smtp_host', '');
        $porta = (int) Configuracao::get('smtp_porta', '587');
        $usuario = Configuracao::get('smtp_usuario', '');
        $senha = Configuracao::get('smtp_senha', '');
        $criptografia = Configuracao::get('smtp_criptografia', 'tls');
        $remetenteEmail = Configuracao::get('smtp_remetente_email', $usuario);
        $remetenteNome = Configuracao::get('smtp_remetente_nome', 'O Consultor');

        if (empty($host) || empty($usuario) || empty($senha)) {
            return ['sucesso' => false, 'erro' => 'SMTP não configurado. Preencha host, usuário e senha em Admin > Configurações > Email.'];
        }

        // Registrar tentativa no banco
        $emailId = self::registrarFila($para, $nomePara, $assunto, $corpo, $tipo);

        // Tentar envio via SMTP
        try {
            $resultado = self::enviarSmtp($host, $porta, $usuario, $senha, $criptografia, $remetenteEmail, $remetenteNome, $para, $nomePara, $assunto, $corpo);

            if ($resultado['sucesso']) {
                self::atualizarStatus($emailId, 'enviado');
                Logger::acao('Email enviado', ['para' => $para, 'assunto' => $assunto, 'tipo' => $tipo]);
            } else {
                self::atualizarStatus($emailId, 'erro', $resultado['erro']);
                Logger::error('Falha ao enviar email', ['para' => $para, 'erro' => $resultado['erro']]);
            }

            return $resultado;
        } catch (\Exception $e) {
            self::atualizarStatus($emailId, 'erro', $e->getMessage());
            Logger::error('Exceção no envio de email', ['para' => $para, 'erro' => $e->getMessage()]);
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }

    /**
     * Envio SMTP puro via socket
     */
    private static function enviarSmtp(string $host, int $porta, string $usuario, string $senha, string $criptografia, string $de, string $deNome, string $para, string $paraNome, string $assunto, string $corpo): array
    {
        $prefix = ($criptografia === 'ssl') ? 'ssl://' : '';
        $socket = @fsockopen($prefix . $host, $porta, $errno, $errstr, 15);

        if (!$socket) {
            return ['sucesso' => false, 'erro' => "Não foi possível conectar ao SMTP: {$errstr} ({$errno})"];
        }

        // Ler saudação
        $resp = self::lerResposta($socket);
        if (substr($resp, 0, 3) !== '220') {
            fclose($socket);
            return ['sucesso' => false, 'erro' => "SMTP saudação inválida: {$resp}"];
        }

        // EHLO
        self::enviarComando($socket, "EHLO " . gethostname());
        self::lerResposta($socket);

        // STARTTLS se necessário
        if ($criptografia === 'tls') {
            self::enviarComando($socket, "STARTTLS");
            $resp = self::lerResposta($socket);
            if (substr($resp, 0, 3) !== '220') {
                fclose($socket);
                return ['sucesso' => false, 'erro' => "STARTTLS falhou: {$resp}"];
            }
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
            self::enviarComando($socket, "EHLO " . gethostname());
            self::lerResposta($socket);
        }

        // AUTH LOGIN
        self::enviarComando($socket, "AUTH LOGIN");
        self::lerResposta($socket);
        self::enviarComando($socket, base64_encode($usuario));
        self::lerResposta($socket);
        self::enviarComando($socket, base64_encode($senha));
        $resp = self::lerResposta($socket);
        if (substr($resp, 0, 3) !== '235') {
            fclose($socket);
            return ['sucesso' => false, 'erro' => "Autenticação SMTP falhou. Verifique usuário e senha."];
        }

        // MAIL FROM
        self::enviarComando($socket, "MAIL FROM:<{$de}>");
        self::lerResposta($socket);

        // RCPT TO
        self::enviarComando($socket, "RCPT TO:<{$para}>");
        self::lerResposta($socket);

        // DATA
        self::enviarComando($socket, "DATA");
        self::lerResposta($socket);

        // Headers + Body
        $headers = "From: {$deNome} <{$de}>\r\n";
        $headers .= "To: {$paraNome} <{$para}>\r\n";
        $headers .= "Subject: {$assunto}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "\r\n";
        $headers .= $corpo . "\r\n.\r\n";

        self::enviarComando($socket, $headers);
        $resp = self::lerResposta($socket);

        // QUIT
        self::enviarComando($socket, "QUIT");
        fclose($socket);

        if (substr($resp, 0, 3) === '250') {
            return ['sucesso' => true, 'erro' => null];
        }

        return ['sucesso' => false, 'erro' => "SMTP resposta inesperada: {$resp}"];
    }

    private static function enviarComando($socket, string $cmd): void
    {
        fwrite($socket, $cmd . "\r\n");
    }

    private static function lerResposta($socket): string
    {
        $resp = '';
        while ($line = fgets($socket, 515)) {
            $resp .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        return trim($resp);
    }

    // ===== FILA DE EMAILS NO BANCO =====

    private static function registrarFila(string $para, string $nome, string $assunto, string $corpo, string $tipo): int
    {
        try {
            Database::execute(
                "INSERT INTO emails_enviados (destinatario_email, destinatario_nome, assunto, corpo, tipo, status, criado_em) VALUES (:para, :nome, :assunto, :corpo, :tipo, 'pendente', NOW())",
                ['para' => $para, 'nome' => $nome, 'assunto' => $assunto, 'corpo' => $corpo, 'tipo' => $tipo]
            );
            return (int) Database::lastInsertId();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private static function atualizarStatus(int $id, string $status, ?string $erro = null): void
    {
        if ($id === 0) return;
        try {
            Database::execute(
                "UPDATE emails_enviados SET status = :status, erro_mensagem = :erro, tentativas = tentativas + 1, enviado_em = IF(:status2 = 'enviado', NOW(), NULL) WHERE id = :id",
                ['status' => $status, 'erro' => $erro, 'status2' => $status, 'id' => $id]
            );
        } catch (\Exception $e) {}
    }

    // ===== TEMPLATES DE EMAIL =====

    /**
     * Email de recuperação de senha
     */
    public static function enviarRecuperacaoSenha(string $para, string $nome, string $token): array
    {
        $link = APP_URL . '/recuperar-senha?token=' . $token;
        $corpo = self::template("Recuperação de Senha", "
            <p>Olá, <strong>{$nome}</strong>!</p>
            <p>Você solicitou a recuperação de senha. Clique no botão abaixo para definir uma nova senha:</p>
            <p style='text-align:center; margin: 24px 0;'>
                <a href='{$link}' style='background:#1E3A5F; color:#fff; padding:12px 24px; border-radius:8px; text-decoration:none; font-weight:600;'>Redefinir Senha</a>
            </p>
            <p style='font-size:12px; color:#666;'>Se você não solicitou isso, ignore este email. O link expira em 1 hora.</p>
        ");
        return self::enviar($para, 'Recuperação de Senha — O Consultor', $corpo, $nome, 'recuperacao_senha');
    }

    /**
     * Convite para Academy
     */
    public static function enviarConviteAcademy(string $para, string $nome): array
    {
        $corpo = self::template("Convite My Academy", "
            <p>Olá, <strong>{$nome}</strong>!</p>
            <p>Sua conta na plataforma <strong>My Academy</strong> está pronta! Acesse seus cursos, trilhas e certificações.</p>
            <p style='text-align:center; margin: 24px 0;'>
                <a href='" . Configuracao::get('academy_url', 'https://myacademy.com.br') . "' style='background:#E07B00; color:#fff; padding:12px 24px; border-radius:8px; text-decoration:none; font-weight:600;'>Acessar My Academy</a>
            </p>
            <p style='font-size:12px; color:#666;'>Use o email {$para} para fazer login.</p>
        ");
        return self::enviar($para, 'Seu acesso à Academy está pronto! — O Consultor', $corpo, $nome, 'convite_academy');
    }

    /**
     * Notificação genérica
     */
    public static function enviarNotificacao(string $para, string $nome, string $assunto, string $mensagem): array
    {
        $corpo = self::template($assunto, "<p>Olá, <strong>{$nome}</strong>!</p><p>{$mensagem}</p>");
        return self::enviar($para, $assunto . ' — O Consultor', $corpo, $nome, 'notificacao');
    }

    /**
     * Template HTML base para emails
     */
    private static function template(string $titulo, string $conteudo): string
    {
        return "
        <div style='font-family:Inter,Arial,sans-serif; max-width:600px; margin:0 auto; background:#fff;'>
            <div style='background:#1E3A5F; padding:24px; text-align:center;'>
                <h1 style='color:#fff; margin:0; font-size:20px; font-weight:700; letter-spacing:1px;'>O CONSULTOR</h1>
            </div>
            <div style='padding:32px 24px;'>
                <h2 style='color:#1E3A5F; margin:0 0 16px; font-size:18px;'>{$titulo}</h2>
                {$conteudo}
            </div>
            <div style='background:#F5F7FA; padding:16px 24px; text-align:center; font-size:11px; color:#999;'>
                <p>© " . date('Y') . " O Consultor — Sistema Operacional Empresarial</p>
            </div>
        </div>";
    }

    /**
     * Testa a conexão SMTP (usado pelo admin)
     */
    public static function testarConexao(): array
    {
        $host = Configuracao::get('smtp_host', '');
        $porta = (int) Configuracao::get('smtp_porta', '587');
        $criptografia = Configuracao::get('smtp_criptografia', 'tls');

        if (empty($host)) {
            return ['sucesso' => false, 'erro' => 'Host SMTP não configurado.'];
        }

        $prefix = ($criptografia === 'ssl') ? 'ssl://' : '';
        $socket = @fsockopen($prefix . $host, $porta, $errno, $errstr, 10);

        if (!$socket) {
            return ['sucesso' => false, 'erro' => "Conexão falhou: {$errstr} ({$errno})"];
        }

        $resp = self::lerResposta($socket);
        fclose($socket);

        if (substr($resp, 0, 3) === '220') {
            return ['sucesso' => true, 'erro' => null, 'mensagem' => "Conexão OK com {$host}:{$porta}"];
        }

        return ['sucesso' => false, 'erro' => "Resposta inesperada: {$resp}"];
    }
}
