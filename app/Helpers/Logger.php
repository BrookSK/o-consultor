<?php
/**
 * Helper de Log
 * Registro de erros, ações críticas e histórico
 */

class Logger
{
    /**
     * Registra uma mensagem de log
     */
    public static function log(string $nivel, string $mensagem, array $contexto = []): void
    {
        $logDir = ROOT_PATH . '/storage/logs';

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $arquivo = $logDir . '/' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $usuario = Auth::check() ? Session::get('usuario_email') : 'guest';

        $linha = "[{$timestamp}] [{$nivel}] [{$usuario}] {$mensagem}";

        if (!empty($contexto)) {
            $linha .= ' | Contexto: ' . json_encode($contexto, JSON_UNESCAPED_UNICODE);
        }

        $linha .= PHP_EOL;

        file_put_contents($arquivo, $linha, FILE_APPEND | LOCK_EX);
    }

    /**
     * Log de informação
     */
    public static function info(string $mensagem, array $contexto = []): void
    {
        self::log('INFO', $mensagem, $contexto);
    }

    /**
     * Log de aviso
     */
    public static function warning(string $mensagem, array $contexto = []): void
    {
        self::log('WARNING', $mensagem, $contexto);
    }

    /**
     * Log de erro
     */
    public static function error(string $mensagem, array $contexto = []): void
    {
        self::log('ERROR', $mensagem, $contexto);
    }

    /**
     * Log de ação crítica do usuário
     */
    public static function acao(string $mensagem, array $contexto = []): void
    {
        self::log('ACAO', $mensagem, $contexto);
    }

    /**
     * Log de segurança
     */
    public static function seguranca(string $mensagem, array $contexto = []): void
    {
        self::log('SEGURANCA', $mensagem, $contexto);
    }
}
