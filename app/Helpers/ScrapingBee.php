<?php
/**
 * Helper ScrapingBee — Coleta de páginas públicas (HTML API)
 *
 * Responsabilidade (spec §13.3): acessar páginas públicas, renderizar
 * JavaScript, usar navegadores headless e proxies, e retornar o HTML bruto.
 * A extração/normalização final é feita pelo backend (ConcorrenteController).
 *
 * A chave da API vive na tabela `configuracoes` (chave: scrapingbee_key),
 * criptografada — nunca em código ou no navegador.
 *
 * Segurança (spec §18): valida/sanitiza URLs e só trabalha com conteúdo
 * publicamente acessível. Não tenta burlar autenticação.
 */

class ScrapingBee
{
    private const ENDPOINT = 'https://app.scrapingbee.com/api/v1/';

    /**
     * Indica se a integração está configurada (chave presente).
     */
    public static function configurada(): bool
    {
        $chave = trim((string) Configuracao::get('scrapingbee_key', ''));
        return $chave !== '';
    }

    /**
     * Valida se uma URL é pública e utilizável (http/https, host resolvível,
     * não aponta para rede interna/loopback). Retorna a URL normalizada ou null.
     */
    public static function sanitizarUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return null;
        }

        // Bloqueia SSRF óbvio: localhost, IPs privados/reservados.
        $host = strtolower($host);
        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return null;
        }
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if (!filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return null;
            }
        }

        return $url;
    }

    /**
     * Busca o HTML de uma página pública via ScrapingBee.
     *
     * @param string $url      URL pública já sanitizada
     * @param array  $opcoes   Sobrescreve defaults: render_js, timeout,
     *                         premium_proxy, wait, country_code
     * @return array {
     *     sucesso: bool, html: ?string, status_http: ?int,
     *     tipo_erro: ?string, erro: ?string, creditos: ?int
     * }
     */
    public static function buscarHtml(string $url, array $opcoes = []): array
    {
        $chave = trim((string) Configuracao::get('scrapingbee_key', ''));
        if ($chave === '') {
            return self::falha('nao_configurado', 'Chave da ScrapingBee não configurada (Admin > Configurações).');
        }

        $urlSanitizada = self::sanitizarUrl($url);
        if ($urlSanitizada === null) {
            return self::falha('url_invalida', 'URL inválida ou não pública.');
        }

        // Defaults (spec §12.2): JS habilitado, timeout e proxy premium configuráveis.
        $renderJs      = $opcoes['render_js']    ?? (Configuracao::get('scrapingbee_render_js', '1') === '1');
        $timeout       = (int) ($opcoes['timeout']       ?? (int) Configuracao::get('scrapingbee_timeout', '30'));
        $premiumProxy  = $opcoes['premium_proxy'] ?? (Configuracao::get('scrapingbee_premium_proxy', '0') === '1');
        $wait          = (int) ($opcoes['wait']          ?? 0);
        $countryCode   = trim((string) ($opcoes['country_code'] ?? Configuracao::get('scrapingbee_country', '')));

        $params = [
            'api_key'    => $chave,
            'url'        => $urlSanitizada,
            'render_js'  => $renderJs ? 'true' : 'false',
        ];
        if ($premiumProxy) {
            $params['premium_proxy'] = 'true';
        }
        if ($wait > 0) {
            $params['wait'] = (string) $wait;
        }
        if ($countryCode !== '') {
            $params['country_code'] = $countryCode;
        }

        $endpoint = self::ENDPOINT . '?' . http_build_query($params);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => max(30, $timeout + 20), // margem sobre o timeout do render
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
        ]);

        $html = curl_exec($ch);
        $statusHttp = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $creditos = null;
        // ScrapingBee expõe créditos usados no header Spb-Cost, mas como não
        // capturamos headers aqui, deixamos null (não inventar valores).
        $erroCurl = curl_error($ch);
        curl_close($ch);

        if ($html === false || $erroCurl !== '') {
            return self::falha('timeout', 'Falha de conexão com a ScrapingBee: ' . $erroCurl, $statusHttp, $creditos);
        }

        // Mapeia os status/erros previstos no spec §19.
        if ($statusHttp === 200) {
            return [
                'sucesso' => true,
                'html' => (string) $html,
                'status_http' => 200,
                'tipo_erro' => null,
                'erro' => null,
                'creditos' => $creditos,
            ];
        }

        $tipoErro = match (true) {
            $statusHttp === 401, $statusHttp === 403 => 'creditos_ou_bloqueio',
            $statusHttp === 404                      => 'pagina_inexistente',
            $statusHttp === 408, $statusHttp === 504 => 'timeout',
            $statusHttp === 429                      => 'rate_limit',
            $statusHttp >= 500                       => 'erro_scrapingbee',
            default                                  => 'erro_desconhecido',
        };

        return self::falha($tipoErro, 'ScrapingBee retornou HTTP ' . $statusHttp . '.', $statusHttp, $creditos);
    }

    /**
     * Testa a conexão com a ScrapingBee usse um alvo público simples.
     */
    public static function testarConexao(): array
    {
        if (!self::configurada()) {
            return ['sucesso' => false, 'erro' => 'Chave não configurada.'];
        }
        $res = self::buscarHtml('https://httpbin.org/html', ['render_js' => false, 'timeout' => 20]);
        return ['sucesso' => (bool) $res['sucesso'], 'erro' => $res['erro'] ?? null];
    }

    private static function falha(string $tipo, string $mensagem, ?int $status = null, ?int $creditos = null): array
    {
        return [
            'sucesso' => false,
            'html' => null,
            'status_http' => $status,
            'tipo_erro' => $tipo,
            'erro' => $mensagem,
            'creditos' => $creditos,
        ];
    }
}
