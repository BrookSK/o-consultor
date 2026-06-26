<?php
/**
 * Model Configuracao — Gerencia todas as configurações do sistema
 * 
 * Todas as chaves de API, URLs, secrets, toggles e preferências
 * são armazenadas na tabela `configuracoes` no banco de dados.
 * Nunca em arquivos .env, config PHP ou código-fonte.
 *
 * Valores sensíveis (chaves de API) são criptografados com openssl
 * usando a APP_KEY definida em config/app.php.
 */

class Configuracao
{
    /** Cache em memória para evitar queries repetidas na mesma request */
    private static array $cache = [];

    /** Campos que devem ser criptografados no banco */
    private const CAMPOS_SENSIVEIS = [
        'openai_key', 'anthropic_key', 'perplexity_key',
        'academy_jwt_secret',
    ];

    /**
     * Obtém um valor de configuração do banco
     * Retorna o valor padrão se não encontrado
     */
    public static function get(string $chave, ?string $padrao = null): ?string
    {
        // Verificar cache primeiro
        if (isset(self::$cache[$chave])) {
            return self::$cache[$chave];
        }

        // Verificar cache de sessão (evita queries a cada page load)
        $cacheKey = 'config_' . $chave;
        if (isset($_SESSION[$cacheKey])) {
            self::$cache[$chave] = $_SESSION[$cacheKey];
            return $_SESSION[$cacheKey];
        }

        // Buscar do banco
        try {
            $resultado = Database::queryOne(
                "SELECT valor, criptografado FROM configuracoes WHERE chave = :chave LIMIT 1",
                ['chave' => $chave]
            );

            if ($resultado === null) {
                return $padrao;
            }

            $valor = $resultado['valor'];

            // Descriptografar se necessário
            if ($resultado['criptografado']) {
                $valor = self::descriptografar($valor);
            }

            // Salvar no cache
            self::$cache[$chave] = $valor;
            $_SESSION[$cacheKey] = $valor;

            return $valor;
        } catch (\Exception $e) {
            // Se o banco não estiver disponível, retorna padrão
            return $padrao;
        }
    }

    /**
     * Salva um valor de configuração no banco
     */
    public static function set(string $chave, ?string $valor, string $grupo = 'geral', string $descricao = ''): bool
    {
        $criptografado = in_array($chave, self::CAMPOS_SENSIVEIS);
        $valorParaSalvar = $criptografado && $valor ? self::criptografar($valor) : $valor;

        try {
            // Verificar se já existe
            $existe = Database::queryOne(
                "SELECT id FROM configuracoes WHERE chave = :chave LIMIT 1",
                ['chave' => $chave]
            );

            if ($existe) {
                Database::execute(
                    "UPDATE configuracoes SET valor = :valor, criptografado = :cripto, atualizado_em = NOW() WHERE chave = :chave",
                    ['valor' => $valorParaSalvar, 'cripto' => $criptografado ? 1 : 0, 'chave' => $chave]
                );
            } else {
                Database::execute(
                    "INSERT INTO configuracoes (chave, valor, grupo_config, descricao, criptografado, criado_em, atualizado_em) VALUES (:chave, :valor, :grupo, :desc, :cripto, NOW(), NOW())",
                    ['chave' => $chave, 'valor' => $valorParaSalvar, 'grupo' => $grupo, 'desc' => $descricao, 'cripto' => $criptografado ? 1 : 0]
                );
            }

            // Limpar cache
            unset(self::$cache[$chave]);
            unset($_SESSION['config_' . $chave]);

            return true;
        } catch (\Exception $e) {
            Logger::error('Erro ao salvar configuração', ['chave' => $chave, 'erro' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Obtém todas as configurações de um grupo
     */
    public static function getGrupo(string $grupo): array
    {
        try {
            $resultados = Database::query(
                "SELECT chave, valor, descricao, criptografado FROM configuracoes WHERE grupo_config = :grupo ORDER BY chave",
                ['grupo' => $grupo]
            );

            $configs = [];
            foreach ($resultados as $row) {
                $valor = $row['criptografado'] ? self::descriptografar($row['valor']) : $row['valor'];
                $configs[$row['chave']] = [
                    'valor' => $valor,
                    'descricao' => $row['descricao'],
                    'sensivel' => (bool) $row['criptografado'],
                ];
            }
            return $configs;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Limpa todo o cache de configurações (chamar após salvar no admin)
     */
    public static function limparCache(): void
    {
        self::$cache = [];
        // Limpar todas as chaves de config da sessão
        foreach ($_SESSION as $key => $val) {
            if (str_starts_with($key, 'config_')) {
                unset($_SESSION[$key]);
            }
        }
    }

    /**
     * Verifica se uma API está ativa (chave configurada e toggle ativo)
     */
    public static function apiAtiva(string $provedor): bool
    {
        $toggle = self::get($provedor . '_ativo', '0');
        $chave = self::get($provedor . '_key', '');
        return $toggle === '1' && !empty($chave);
    }

    // ===== CRIPTOGRAFIA =====

    private static function criptografar(string $valor): string
    {
        $method = 'aes-256-cbc';
        $key = hash('sha256', APP_KEY, true);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
        $encrypted = openssl_encrypt($valor, $method, $key, 0, $iv);
        return base64_encode($iv . '::' . $encrypted);
    }

    private static function descriptografar(string $valor): string
    {
        $method = 'aes-256-cbc';
        $key = hash('sha256', APP_KEY, true);
        $parts = explode('::', base64_decode($valor), 2);
        if (count($parts) !== 2) return '';
        return openssl_decrypt($parts[1], $method, $key, 0, $parts[0]) ?: '';
    }
}
