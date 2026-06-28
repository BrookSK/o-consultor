<?php
/**
 * Helper Flash Messages
 * Mensagens temporárias exibidas uma única vez
 */

class Flash
{
    /**
     * Define uma mensagem flash
     */
    public static function set(string $tipo, string $mensagem): void
    {
        $_SESSION['flash'][$tipo] = $mensagem;
    }

    /**
     * Obtém e remove uma mensagem flash
     */
    public static function get(string $tipo): ?string
    {
        $mensagem = $_SESSION['flash'][$tipo] ?? null;
        unset($_SESSION['flash'][$tipo]);
        return $mensagem;
    }

    /**
     * Verifica se existe mensagem de um tipo
     */
    public static function has(string $tipo): bool
    {
        return isset($_SESSION['flash'][$tipo]);
    }

    /**
     * Obtém todas as mensagens e limpa
     */
    public static function todas(): array
    {
        $mensagens = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $mensagens;
    }

    /**
     * Renderiza as mensagens flash como HTML (alerts)
     */
    public static function renderizar(): string
    {
        $mensagens = self::todas();
        $html = '';

        $classes = [
            'sucesso' => 'bg-green-100 border-green-400 text-green-700',
            'erro' => 'bg-red-100 border-red-400 text-red-700',
            'aviso' => 'bg-yellow-100 border-yellow-400 text-yellow-700',
            'info' => 'bg-blue-100 border-blue-400 text-blue-700',
        ];

        foreach ($mensagens as $tipo => $mensagem) {
            $classe = $classes[$tipo] ?? $classes['info'];
            $html .= '<div class="border-l-4 p-4 mb-4 rounded ' . $classe . '" role="alert">';
            $html .= '<p>' . htmlspecialchars($mensagem) . '</p>';
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Atalhos para métodos comuns
     */
    public static function sucesso(string $mensagem): void
    {
        self::set('sucesso', $mensagem);
    }

    public static function erro(string $mensagem): void
    {
        self::set('erro', $mensagem);
    }

    public static function aviso(string $mensagem): void
    {
        self::set('aviso', $mensagem);
    }

    public static function info(string $mensagem): void
    {
        self::set('info', $mensagem);
    }
}