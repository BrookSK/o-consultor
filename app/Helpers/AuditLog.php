<?php
/**
 * AuditLog — Sistema de auditoria para ações sensíveis
 * O Consultor — Sistema Operacional Empresarial
 */

class AuditLog
{
    /**
     * Registra uma ação de auditoria
     */
    public static function registrar(
        string $acao, 
        string $modulo, 
        ?string $descricao = null, 
        ?array $dadosExtras = null,
        ?int $userId = null,
        ?int $empresaId = null
    ): bool {
        try {
            // Se não especificado, usa usuário logado
            if ($userId === null && Auth::check()) {
                $userId = Auth::usuarioId();
            }
            
            // Se não especificado, usa empresa do usuário logado
            if ($empresaId === null && Auth::check()) {
                $empresaId = Auth::empresa();
            }

            $sucesso = Database::execute(
                "INSERT INTO audit_log (user_id, empresa_id, acao, modulo, descricao, dados_extras, ip_address, user_agent, timestamp) 
                 VALUES (:user_id, :empresa_id, :acao, :modulo, :descricao, :dados_extras, :ip_address, :user_agent, NOW())",
                [
                    'user_id' => $userId,
                    'empresa_id' => $empresaId,
                    'acao' => $acao,
                    'modulo' => $modulo,
                    'descricao' => $descricao,
                    'dados_extras' => $dadosExtras ? json_encode($dadosExtras, JSON_UNESCAPED_UNICODE) : null,
                    'ip_address' => self::getClientIP(),
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                ]
            );

            return (bool) $sucesso;

        } catch (Exception $e) {
            Logger::error('Erro ao registrar audit log', [
                'erro' => $e->getMessage(),
                'acao' => $acao,
                'modulo' => $modulo
            ]);
            return false;
        }
    }

    /**
     * Busca logs de auditoria por usuário
     */
    public static function buscarPorUsuario(int $userId, int $limite = 50): array
    {
        return Database::query(
            "SELECT * FROM audit_log 
             WHERE user_id = :user_id 
             ORDER BY timestamp DESC 
             LIMIT :limite",
            ['user_id' => $userId, 'limite' => $limite]
        );
    }

    /**
     * Busca logs de auditoria por empresa
     */
    public static function buscarPorEmpresa(int $empresaId, int $limite = 100): array
    {
        return Database::query(
            "SELECT al.*, u.nome as usuario_nome 
             FROM audit_log al
             LEFT JOIN usuarios u ON al.user_id = u.id
             WHERE al.empresa_id = :empresa_id 
             ORDER BY al.timestamp DESC 
             LIMIT :limite",
            ['empresa_id' => $empresaId, 'limite' => $limite]
        );
    }

    /**
     * Busca logs específicos de SSO Academy
     */
    public static function buscarSsoAcademy(?int $userId = null, int $limite = 20): array
    {
        $where = "acao = 'sso_academy'";
        $params = ['limite' => $limite];

        if ($userId !== null) {
            $where .= " AND user_id = :user_id";
            $params['user_id'] = $userId;
        }

        return Database::query(
            "SELECT al.*, u.nome as usuario_nome, u.email
             FROM audit_log al
             LEFT JOIN usuarios u ON al.user_id = u.id
             WHERE {$where}
             ORDER BY al.timestamp DESC 
             LIMIT :limite",
            $params
        );
    }

    /**
     * Estatísticas de auditoria
     */
    public static function estatisticas(?int $empresaId = null): array
    {
        $where = $empresaId ? "WHERE empresa_id = :empresa_id" : "";
        $params = $empresaId ? ['empresa_id' => $empresaId] : [];

        $dados = Database::query(
            "SELECT 
                acao,
                modulo,
                COUNT(*) as total,
                MAX(timestamp) as ultimo_acesso
             FROM audit_log 
             {$where}
             GROUP BY acao, modulo
             ORDER BY total DESC",
            $params
        );

        $total = Database::queryOne(
            "SELECT COUNT(*) as total FROM audit_log {$where}",
            $params
        )['total'] ?? 0;

        return [
            'total_logs' => $total,
            'acoes_por_tipo' => $dados,
        ];
    }

    /**
     * Obtém IP real do cliente (considerando proxies)
     */
    private static function getClientIP(): string
    {
        // Headers que podem conter o IP real
        $headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                
                // Validar se é um IP válido
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}