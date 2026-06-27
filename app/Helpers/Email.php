<?php
/**
 * Email Helper — Envio de emails via SMTP
 * O Consultor — Sistema Operacional Empresarial
 *
 * Todas as configurações SMTP são lidas do banco via Model Configuracao
 */

class Email
{
    /**
     * Envia email via SMTP configurado
     */
    public static function enviar(string $para, string $assunto, string $corpo, string $paraNum = null, string $tipo = 'outro'): array
    {
        // Verificar se SMTP está ativo
        if (!Configuracao::get('smtp_ativo', '0') === '1') {
            return ['sucesso' => false, 'erro' => 'SMTP não está ativo nas configurações'];
        }

        try {
            $config = self::getConfigSMTP();
            
            // Validar configurações obrigatórias
            if (empty($config['host']) || empty($config['usuario']) || empty($config['senha'])) {
                return ['sucesso' => false, 'erro' => 'Configurações SMTP incompletas'];
            }

            // Criar conexão SMTP
            $smtp = self::conectarSMTP($config);
            if (!$smtp) {
                return ['sucesso' => false, 'erro' => 'Falha na conexão SMTP'];
            }

            // Enviar email
            $resultado = self::enviarViaSMTP($smtp, $config, $para, $paraNum, $assunto, $corpo);
            
            // Registrar no log de emails
            self::registrarEnvio($para, $paraNum, $assunto, $corpo, $tipo, $resultado);
            
            return $resultado;
            
        } catch (Exception $e) {
            Logger::erro('Erro no envio de email: ' . $e->getMessage());
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }

    /**
     * Testa conexão SMTP
     */
    public static function testarConexao(): array
    {
        if (Configuracao::get('smtp_ativo', '0') !== '1') {
            return ['sucesso' => false, 'erro' => 'SMTP não está ativo'];
        }

        try {
            $config = self::getConfigSMTP();
            
            if (empty($config['host']) || empty($config['usuario'])) {
                return ['sucesso' => false, 'erro' => 'Configurações SMTP incompletas'];
            }

            $smtp = self::conectarSMTP($config);
            if ($smtp) {
                fclose($smtp);
                return ['sucesso' => true, 'mensagem' => 'Conexão SMTP OK'];
            }
            
            return ['sucesso' => false, 'erro' => 'Falha na conexão SMTP'];
            
        } catch (Exception $e) {
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }

    /**
     * Recupera configurações SMTP do banco
     */
    private static function getConfigSMTP(): array
    {
        return [
            'host' => Configuracao::get('smtp_host', ''),
            'porta' => (int) Configuracao::get('smtp_porta', '587'),
            'usuario' => Configuracao::get('smtp_usuario', ''),
            'senha' => Configuracao::get('smtp_senha', ''),
            'criptografia' => Configuracao::get('smtp_criptografia', 'tls'),
            'remetente_email' => Configuracao::get('smtp_remetente_email', ''),
            'remetente_nome' => Configuracao::get('smtp_remetente_nome', 'O Consultor'),
        ];
    }

    /**
     * Conecta ao servidor SMTP
     */
    private static function conectarSMTP(array $config)
    {
        $host = $config['host'];
        $porta = $config['porta'];
        
        // Ajustar protocolo baseado na criptografia
        if ($config['criptografia'] === 'ssl') {
            $host = 'ssl://' . $host;
        }

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]
        ]);

        $smtp = stream_socket_client(
            "{$host}:{$porta}", 
            $errno, 
            $errstr, 
            30, 
            STREAM_CLIENT_CONNECT, 
            $context
        );

        if (!$smtp) {
            throw new Exception("Conexão SMTP falhou: {$errstr} ({$errno})");
        }

        // Ler resposta inicial
        $resposta = fgets($smtp);
        if (strpos($resposta, '220') !== 0) {
            fclose($smtp);
            throw new Exception("Servidor SMTP rejeitou conexão: {$resposta}");
        }

        return $smtp;
    }

    /**
     * Envia email via conexão SMTP aberta
     */
    private static function enviarViaSMTP($smtp, array $config, string $para, ?string $paraNum, string $assunto, string $corpo): array
    {
        try {
            // EHLO
            fwrite($smtp, "EHLO " . $config['host'] . "\r\n");
            $resposta = fgets($smtp);

            // STARTTLS se necessário
            if ($config['criptografia'] === 'tls') {
                fwrite($smtp, "STARTTLS\r\n");
                fgets($smtp);
                stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                
                // EHLO novamente após TLS
                fwrite($smtp, "EHLO " . $config['host'] . "\r\n");
                fgets($smtp);
            }

            // Autenticação
            fwrite($smtp, "AUTH LOGIN\r\n");
            fgets($smtp);
            
            fwrite($smtp, base64_encode($config['usuario']) . "\r\n");
            fgets($smtp);
            
            fwrite($smtp, base64_encode($config['senha']) . "\r\n");
            $authResp = fgets($smtp);
            
            if (strpos($authResp, '235') !== 0) {
                throw new Exception("Falha na autenticação SMTP: {$authResp}");
            }

            // Remetente
            $from = $config['remetente_email'] ?: $config['usuario'];
            fwrite($smtp, "MAIL FROM: <{$from}>\r\n");
            fgets($smtp);

            // Destinatário
            fwrite($smtp, "RCPT TO: <{$para}>\r\n");
            fgets($smtp);

            // Dados
            fwrite($smtp, "DATA\r\n");
            fgets($smtp);

            // Headers e corpo
            $headers = self::construirHeaders($from, $config['remetente_nome'], $para, $paraNum, $assunto);
            $email = $headers . "\r\n" . $corpo . "\r\n.\r\n";
            
            fwrite($smtp, $email);
            $dataResp = fgets($smtp);

            // QUIT
            fwrite($smtp, "QUIT\r\n");
            fclose($smtp);

            if (strpos($dataResp, '250') !== 0) {
                throw new Exception("Falha no envio: {$dataResp}");
            }

            return ['sucesso' => true, 'mensagem' => 'Email enviado com sucesso'];
            
        } catch (Exception $e) {
            fclose($smtp);
            throw $e;
        }
    }

    /**
     * Constrói headers do email
     */
    private static function construirHeaders(string $from, string $fromName, string $para, ?string $paraNum, string $assunto): string
    {
        $boundary = '----=_Part_' . md5(uniqid());
        
        $headers = [
            "From: {$fromName} <{$from}>",
            "To: " . ($paraNum ? "{$paraNum} <{$para}>" : $para),
            "Subject: =?UTF-8?B?" . base64_encode($assunto) . "?=",
            "MIME-Version: 1.0",
            "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
            "Content-Transfer-Encoding: 8bit",
            "X-Mailer: O Consultor SMTP",
            "Date: " . date('r'),
        ];

        return implode("\r\n", $headers);
    }

    /**
     * Registra envio na tabela de logs
     */
    private static function registrarEnvio(string $para, ?string $paraNum, string $assunto, string $corpo, string $tipo, array $resultado): void
    {
        try {
            Database::execute(
                "INSERT INTO emails_enviados (destinatario_email, destinatario_nome, assunto, corpo, tipo, status, erro_mensagem, enviado_em, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $para,
                    $paraNum,
                    $assunto,
                    $corpo,
                    $tipo,
                    $resultado['sucesso'] ? 'enviado' : 'erro',
                    $resultado['sucesso'] ? null : $resultado['erro'],
                    $resultado['sucesso'] ? date('Y-m-d H:i:s') : null
                ]
            );
        } catch (Exception $e) {
            Logger::erro('Erro ao registrar envio de email: ' . $e->getMessage());
        }
    }

    /**
     * Envia notificação padrão do sistema
     */
    public static function enviarNotificacao(string $para, string $paraNum, string $titulo, string $mensagem, string $tipo = 'notificacao'): array
    {
        $corpo = self::templateNotificacao($titulo, $mensagem);
        return self::enviar($para, $titulo, $corpo, $paraNum, $tipo);
    }

    /**
     * Template HTML para notificações
     */
    private static function templateNotificacao(string $titulo, string $mensagem): string
    {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: #f8f9fa;'>
            <div style='background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
                <div style='text-align: center; margin-bottom: 30px;'>
                    <h1 style='color: #1E3A5F; margin: 0; font-size: 24px;'>O CONSULTOR</h1>
                    <div style='width: 50px; height: 3px; background: #E07B00; margin: 10px auto;'></div>
                </div>
                
                <h2 style='color: #333; font-size: 20px; margin-bottom: 20px;'>{$titulo}</h2>
                
                <div style='color: #555; line-height: 1.6; margin-bottom: 30px;'>
                    {$mensagem}
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='" . APP_URL . "/dashboard' style='background: #1E3A5F; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;'>
                        Acessar Plataforma
                    </a>
                </div>
                
                <div style='border-top: 1px solid #eee; padding-top: 20px; text-align: center; color: #999; font-size: 12px;'>
                    Este email foi enviado automaticamente pelo sistema O Consultor.<br>
                    Se você não deveria receber este email, entre em contato conosco.
                </div>
            </div>
        </div>";
    }
}