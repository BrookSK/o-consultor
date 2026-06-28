<?php
/**
 * AcademyController — SSO para Academy (F-08 Implementation)
 * O Consultor — Sistema Operacional Empresarial
 */

class AcademyController
{
    /**
     * SSO Academy — F-08 Implementation
     * Fluxo completo com validações e auditoria
     */
    public function sso(): void
    {
        // 1. Verificar autenticação
        if (!Auth::check()) {
            header('Location: ' . APP_URL . '/login');
            exit;
        }

        $usuario = Auth::usuario();

        try {
            // 2. Ler configurações SSO do banco
            $academyUrl = Configuracao::get('academy_url');
            $jwtSecret = Configuracao::get('academy_jwt_secret');
            $ssoRota = Configuracao::get('academy_sso_rota', '/sso');
            $tokenParam = Configuracao::get('academy_sso_parametro', 'token');
            $academyAtivo = Configuracao::get('academy_ativo', '0') === '1';

            // 3. Validar configuração
            if (!$academyAtivo) {
                Flash::set('erro', 'Integração Academy não está ativa. Contate o suporte.');
                header('Location: ' . APP_URL . '/central-de-conteudo');
                exit;
            }

            if (empty($jwtSecret)) {
                Flash::set('erro', 'Integração Academy não configurada. Contate o suporte.');
                header('Location: ' . APP_URL . '/central-de-conteudo');
                exit;
            }

            if (empty($academyUrl)) {
                Flash::set('erro', 'URL da Academy não configurada. Contate o suporte.');
                header('Location: ' . APP_URL . '/central-de-conteudo');
                exit;
            }

            // 4. Verificar se usuário tem email_academy vinculado
            if (empty($usuario['email_academy'])) {
                // Registrar tentativa sem conta vinculada
                AuditLog::registrar(
                    'sso_academy_sem_conta',
                    'academy',
                    'Tentativa de SSO sem conta Academy vinculada',
                    ['usuario_email' => $usuario['email']]
                );

                Flash::set('erro', 'Conta Academy não encontrada. Vincule sua conta Academy primeiro.');
                header('Location: ' . APP_URL . '/perfil#academy-section');
                exit;
            }

            // 5. Montar payload JWT
            $payload = [
                'sub' => $usuario['email_academy'], // Subject: email da Academy
                'name' => $usuario['nome'],
                'iat' => time(),
                'exp' => time() + 300, // Expira em 5 minutos (segurança)
                'iss' => 'o-consultor', // Issuer
                'aud' => 'my-academy', // Audience
            ];

            // 6. Gerar JWT
            $jwt = ApiHelper::gerarJwtAcademy($payload);

            // 7. Registrar log de auditoria
            AuditLog::registrar(
                'sso_academy',
                'academy',
                'Acesso SSO à Academy realizado com sucesso',
                [
                    'email_academy' => $usuario['email_academy'],
                    'usuario_id' => $usuario['id'],
                    'payload_sub' => $payload['sub'],
                    'token_exp' => $payload['exp'],
                ]
            );

            // 8. Montar URL final
            $urlFinal = rtrim($academyUrl, '/') . $ssoRota . '?' . $tokenParam . '=' . $jwt;

            // 9. Redirecionar para Academy (nova aba via JavaScript para não fechar O Consultor)
            ?>
            <!DOCTYPE html>
            <html>
            <head>
                <title>Redirecionando para Academy...</title>
                <script>
                    // Abrir Academy em nova aba
                    window.open('<?= htmlspecialchars($urlFinal, ENT_QUOTES) ?>', '_blank', 'width=1200,height=800');
                    
                    // Voltar para Central de Conteúdo após 2 segundos
                    setTimeout(() => {
                        window.location.href = '<?= APP_URL ?>/central-de-conteudo';
                    }, 2000);
                </script>
            </head>
            <body>
                <div style="font-family: Arial, sans-serif; text-align: center; margin-top: 100px;">
                    <h2>🎓 Abrindo Academy...</h2>
                    <p>Uma nova aba será aberta com sua Academy.</p>
                    <p>Você será redirecionado de volta em instantes...</p>
                    <div style="margin-top: 20px;">
                        <a href="<?= APP_URL ?>/central-de-conteudo" style="color: #1E3A5F; text-decoration: none;">
                            ← Voltar para Central de Conteúdo
                        </a>
                    </div>
                </div>
            </body>
            </html>
            <?php
            exit;

        } catch (Exception $e) {
            // Log do erro
            Logger::error('Erro no SSO Academy', [
                'erro' => $e->getMessage(),
                'usuario_id' => $usuario['id'] ?? null
            ]);

            // Registrar falha na auditoria
            AuditLog::registrar(
                'sso_academy_erro',
                'academy',
                'Erro durante SSO Academy: ' . $e->getMessage(),
                ['usuario_id' => $usuario['id'] ?? null]
            );

            Flash::set('erro', 'Erro ao acessar Academy. Tente novamente em alguns minutos.');
            header('Location: ' . APP_URL . '/central-de-conteudo');
            exit;
        }
    }

    /**
     * Exibe logs de SSO do usuário (para aba Academy no perfil)
     */
    public function logs(): void
    {
        Auth::proteger();

        $usuario = Auth::usuario();
        $logs = AuditLog::buscarSsoAcademy($usuario['id'], 20);

        header('Content-Type: application/json');
        echo json_encode([
            'sucesso' => true,
            'logs' => array_map(function($log) {
                return [
                    'data' => $log['timestamp'],
                    'acao' => $log['acao'],
                    'descricao' => $log['descricao'],
                    'ip' => $log['ip_address'],
                    'extras' => json_decode($log['dados_extras'] ?? '{}', true),
                ];
            }, $logs)
        ]);
        exit;
    }
}
