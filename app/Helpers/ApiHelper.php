<?php
/**
 * ApiHelper — Integração centralizada com APIs de IA
 * O Consultor — Sistema Operacional Empresarial
 *
 * TODAS as chaves, modelos e configurações são lidas do banco de dados
 * via Model Configuracao. Nenhuma chave fica em código-fonte.
 */

class ApiHelper
{
    /** Arquivo de log específico para erros de IA */
    private const LOG_FILE = ROOT_PATH . '/storage/logs/ai_errors.log';

    /**
     * Lê configuração do banco (com fallback)
     */
    private static function config(string $chave, string $padrao = ''): string
    {
        return Configuracao::get($chave, $padrao);
    }

    /** Timeout (lido do banco) */
    private static function getTimeout(): int
    {
        return (int) self::config('api_timeout', '120');
    }

    /** Max tentativas (lido do banco) */
    private static function getMaxTentativas(): int
    {
        return (int) self::config('api_max_retries', '2');
    }

    // =========================================================================
    // MÉTODOS PRINCIPAIS DE CHAMADA POR PROVEDOR
    // =========================================================================

    /**
     * Chama a API da OpenAI (GPT-4o, GPT-4o-mini, etc.)
     * Chave e modelo são lidos do banco via tela de configurações.
     */
    public static function chamarOpenAI(string $prompt, ?string $model = null, bool $jsonMode = true, ?int $maxTokensOverride = null, ?int $timeoutOverride = null): array
    {
        $apiKey = self::config('openai_key');
        $model = $model ? $model : self::config('openai_modelo', 'gpt-4o');
        $maxTokens = $maxTokensOverride ?? (int) self::config('openai_max_tokens', '8192');

        if (empty($apiKey)) {
            return ['sucesso' => false, 'conteudo' => null, 'erro' => 'Chave OpenAI não configurada. Acesse Admin > Configurações > APIs.'];
        }

        $body = [
            'model'    => $model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'max_tokens' => $maxTokens,
        ];

        if ($jsonMode) {
            $body['response_format'] = ['type' => 'json_object'];
        }

        $resultado = self::executarCurl(
            'https://api.openai.com/v1/chat/completions',
            [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            $body,
            'OpenAI',
            $timeoutOverride
        );

        if (!$resultado['sucesso']) {
            return $resultado;
        }

        // Extrair conteúdo da resposta
        $conteudo = isset($resultado['dados']['choices'][0]['message']['content']) ? $resultado['dados']['choices'][0]['message']['content'] : null;

        if ($conteudo === null) {
            self::logErro('OpenAI', 'Resposta sem conteúdo', $resultado['dados']);
            return ['sucesso' => false, 'conteudo' => null, 'erro' => 'Resposta da API sem conteúdo.'];
        }

        // Validar JSON se modo JSON ativo
        if ($jsonMode) {
            $decoded = json_decode($conteudo, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                self::logErro('OpenAI', 'JSON inválido na resposta', ['raw' => substr($conteudo, 0, 500)]);
                return ['sucesso' => false, 'conteudo' => $conteudo, 'erro' => 'Resposta não é JSON válido.'];
            }
            return ['sucesso' => true, 'conteudo' => $decoded, 'erro' => null];
        }

        return ['sucesso' => true, 'conteudo' => $conteudo, 'erro' => null];
    }

    /**
     * Extrai o TEXTO de um documento (PDF) usando a própria OpenAI, quando a
     * extração local falha (ex.: PDFs com fontes subset/CID ou sem pdftotext no servidor).
     * Envia o arquivo como base64 (input_file) para a API Responses e pede a transcrição.
     *
     * @param string $caminhoArquivo Caminho absoluto do arquivo no disco
     * @param string $nomeArquivo    Nome original (com extensão)
     * @return array ['sucesso'=>bool, 'texto'=>string, 'erro'=>string|null]
     */
    public static function extrairTextoDocumentoViaIA(string $caminhoArquivo, string $nomeArquivo): array
    {
        $apiKey = self::config('openai_key');
        if (empty($apiKey)) {
            return ['sucesso' => false, 'texto' => '', 'erro' => 'Chave OpenAI não configurada.'];
        }
        if (!file_exists($caminhoArquivo)) {
            return ['sucesso' => false, 'texto' => '', 'erro' => 'Arquivo não encontrado.'];
        }

        // Modelo com suporte a leitura de arquivos/visão
        $model = self::config('openai_modelo_leitura', 'gpt-4o');
        $conteudo = base64_encode((string) file_get_contents($caminhoArquivo));
        $dataUrl = 'data:application/pdf;base64,' . $conteudo;

        $instrucao = 'Este documento é um material OPERACIONAL INTERNO fornecido pela própria empresa '
            . '(o usuário tem autorização total sobre ele). Sua tarefa é EXTRAIR e ORGANIZAR todas as '
            . 'informações úteis dele para servir de base a um procedimento operacional (SOP). '
            . 'Liste, de forma estruturada e fiel ao conteúdo: o que é o serviço/processo, o objetivo, '
            . 'o passo a passo de execução (na ordem), ferramentas e sistemas citados pelo nome, '
            . 'critérios, parâmetros, prazos, responsáveis, regras, checklists e situações de exceção. '
            . 'Preserve os termos, nomes de etapas e exemplos exatamente como aparecem. '
            . 'Não recuse: não é para reproduzir o documento como obra, e sim extrair os dados operacionais. '
            . 'Responda em texto corrido/tópicos, em português, apenas com o conteúdo extraído.';

        // Usa a API Responses (suporta input_file por base64)
        $body = [
            'model' => $model,
            'max_output_tokens' => 8000,
            'input' => [[
                'role' => 'user',
                'content' => [
                    ['type' => 'input_text', 'text' => $instrucao],
                    ['type' => 'input_file', 'filename' => $nomeArquivo, 'file_data' => $dataUrl],
                ],
            ]],
        ];

        $resultado = self::executarCurl(
            'https://api.openai.com/v1/responses',
            [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            $body,
            'OpenAI-Leitura',
            120
        );

        if (!$resultado['sucesso']) {
            return ['sucesso' => false, 'texto' => '', 'erro' => $resultado['erro'] ?? 'Falha na leitura via IA.'];
        }

        $texto = self::extrairTextoRespostaResponses($resultado['dados']);
        if (trim($texto) === '') {
            // Devolver o payload bruto para diagnóstico (log do servidor + retorno)
            $raw = json_encode($resultado['dados'], JSON_UNESCAPED_UNICODE);
            self::logErro('OpenAI-Leitura', 'Resposta sem texto de saída', ['raw' => substr((string) $raw, 0, 800)]);
            error_log('[O CONSULTOR][OpenAI-Leitura] Resposta sem texto de saida. RAW=' . substr((string) $raw, 0, 4000));
            return [
                'sucesso' => false,
                'texto' => '',
                'erro' => 'A IA respondeu, mas sem texto de saída. Resposta: ' . substr((string) $raw, 0, 300)
            ];
        }

        return ['sucesso' => true, 'texto' => $texto, 'erro' => null];
    }

    /**
     * Normaliza a resposta da API Responses da OpenAI extraindo o texto de saída.
     */
    private static function extrairTextoRespostaResponses(array $dados): string
    {
        // Formato mais novo: output_text agregado
        if (!empty($dados['output_text']) && is_string($dados['output_text'])) {
            return $dados['output_text'];
        }

        $texto = '';
        if (!empty($dados['output']) && is_array($dados['output'])) {
            foreach ($dados['output'] as $item) {
                // partes de texto podem estar em content[].text (type output_text)
                if (!empty($item['content']) && is_array($item['content'])) {
                    foreach ($item['content'] as $parte) {
                        if (isset($parte['text']) && is_string($parte['text'])) {
                            $texto .= $parte['text'] . "\n";
                        }
                    }
                }
                // fallback: alguns formatos retornam item['text'] direto
                if (isset($item['text']) && is_string($item['text'])) {
                    $texto .= $item['text'] . "\n";
                }
            }
        }
        // Formato Chat Completions (caso o endpoint responda nesse formato)
        if (trim($texto) === '' && isset($dados['choices'][0]['message']['content'])) {
            $c = $dados['choices'][0]['message']['content'];
            if (is_string($c)) $texto = $c;
        }
        return $texto;
    }

    /**
     * Chama a API da Anthropic (Claude)
     *
     * @param string $prompt Prompt completo
     * @param string $model  Modelo (claude-sonnet-4-20250514, claude-opus-4-20250514, etc.)
     * @return array ['sucesso' => bool, 'conteudo' => string|null, 'erro' => string|null]
     */
    public static function chamarAnthropic(string $prompt, ?string $model = null): array
    {
        $apiKey = self::config('anthropic_key');
        $model = $model ? $model : self::config('anthropic_modelo', 'claude-sonnet-4-20250514');

        if (empty($apiKey)) {
            return ['sucesso' => false, 'conteudo' => null, 'erro' => 'Chave Anthropic não configurada. Acesse Admin > Configurações > APIs.'];
        }

        $resultado = self::executarCurl(
            'https://api.anthropic.com/v1/messages',
            [
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
                'Content-Type: application/json',
            ],
            [
                'model'      => $model,
                'max_tokens' => 8192,
                'messages'   => [['role' => 'user', 'content' => $prompt]],
            ],
            'Anthropic'
        );

        if (!$resultado['sucesso']) {
            return $resultado;
        }

        $conteudo = isset($resultado['dados']['content'][0]['text']) ? $resultado['dados']['content'][0]['text'] : null;

        if ($conteudo === null) {
            self::logErro('Anthropic', 'Resposta sem conteúdo', $resultado['dados']);
            error_log('[O CONSULTOR][Anthropic] Resposta sem conteúdo | dados=' . json_encode($resultado['dados'] ?? [], JSON_UNESCAPED_UNICODE));
            return ['sucesso' => false, 'conteudo' => null, 'erro' => 'Resposta da API sem conteúdo.'];
        }

        // Tentar decodificar como JSON (com limpeza de cercas markdown/texto solto).
        $decoded = self::extrairJsonDeTexto($conteudo);
        if ($decoded !== null) {
            return ['sucesso' => true, 'conteudo' => $decoded, 'erro' => null];
        }

        self::logErro('Anthropic', 'Não foi possível extrair JSON da resposta', ['raw' => mb_substr($conteudo, 0, 800)]);
        error_log('[O CONSULTOR][Anthropic] JSON não extraído da resposta | RAW=' . mb_substr($conteudo, 0, 800));
        return ['sucesso' => false, 'conteudo' => $conteudo, 'erro' => 'A IA não retornou JSON válido (resposta em texto livre).'];
    }

    /**
     * Chama a API da Perplexity (busca em tempo real)
     *
     * @param string $prompt      Prompt de busca
     * @param string $model       Modelo (sonar, sonar-pro)
     * @param bool   $comImagens  Se true, pede à Perplexity para retornar imagens reais
     *                            encontradas na busca (return_images). A Perplexity faz a
     *                            busca de imagem de verdade — pedir para a IA "adivinhar"
     *                            uma URL de og:image dentro do JSON de resposta não funciona
     *                            de forma confiável (o modelo alucina URLs inválidas).
     * @return array ['sucesso' => bool, 'conteudo' => string|null, 'imagens' => array, 'erro' => string|null]
     */
    public static function chamarPerplexity(string $prompt, ?string $model = null, bool $comImagens = false): array
    {
        $apiKey = self::config('perplexity_key');
        // Modelos "llama-3.1-sonar-*" foram descontinuados pela Perplexity em 22/02/2025.
        $model = $model ? $model : self::config('perplexity_modelo', 'sonar');

        if (empty($apiKey)) {
            error_log('[O CONSULTOR][Perplexity] Chave não configurada (Admin > Configurações > APIs).');
            return ['sucesso' => false, 'conteudo' => null, 'imagens' => [], 'erro' => 'Chave Perplexity não configurada. Acesse Admin > Configurações > APIs.'];
        }

        $body = [
            'model'    => $model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ];
        if ($comImagens) {
            $body['return_images'] = true;
        }

        $resultado = self::executarCurl(
            'https://api.perplexity.ai/chat/completions',
            [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            $body,
            'Perplexity'
        );

        if (!$resultado['sucesso']) {
            $resultado['imagens'] = [];
            return $resultado;
        }

        // Imagens reais encontradas pela busca (array de {image_url, origin_url, title, ...}).
        $imagens = isset($resultado['dados']['images']) && is_array($resultado['dados']['images']) ? $resultado['dados']['images'] : [];

        $conteudo = isset($resultado['dados']['choices'][0]['message']['content']) ? $resultado['dados']['choices'][0]['message']['content'] : null;

        if ($conteudo === null) {
            self::logErro('Perplexity', 'Resposta sem conteúdo', $resultado['dados']);
            error_log('[O CONSULTOR][Perplexity] Resposta sem conteúdo | dados=' . json_encode($resultado['dados'] ?? [], JSON_UNESCAPED_UNICODE));
            return ['sucesso' => false, 'conteudo' => null, 'imagens' => [], 'erro' => 'Resposta da API sem conteúdo.'];
        }

        // A Perplexity raramente devolve JSON puro: costuma envolver em ```json ... ```
        // ou incluir texto/citações antes/depois. Tentamos limpar e extrair o JSON real.
        $decoded = self::extrairJsonDeTexto($conteudo);
        if ($decoded !== null) {
            return ['sucesso' => true, 'conteudo' => $decoded, 'imagens' => $imagens, 'erro' => null];
        }

        // Não foi possível extrair JSON válido: registrar amostra para diagnóstico e
        // reportar falha (em vez de devolver sucesso=true com uma string, que o
        // código chamador não consegue processar e falha silenciosamente).
        self::logErro('Perplexity', 'Não foi possível extrair JSON da resposta', ['raw' => mb_substr($conteudo, 0, 800)]);
        error_log('[O CONSULTOR][Perplexity] JSON não extraído da resposta | RAW=' . mb_substr($conteudo, 0, 800));
        return ['sucesso' => false, 'conteudo' => $conteudo, 'imagens' => $imagens, 'erro' => 'A Perplexity não retornou JSON válido (resposta em texto livre).'];
    }

    /**
     * Casa as imagens reais retornadas pela busca (return_images da Perplexity)
     * com cada notícia, comparando o domínio da origin_url da imagem com o
     * domínio da URL da notícia. Preenche $noticia['imagem_url'] quando encontrar.
     *
     * @param array $noticias Lista de notícias (cada uma com 'url')
     * @param array $imagens  Lista de imagens ['image_url','origin_url',...]
     * @return array Notícias com 'imagem_url' preenchido quando possível
     */
    public static function casarImagensComNoticias(array $noticias, array $imagens): array
    {
        if (empty($imagens)) {
            return $noticias;
        }

        $dominio = static function (string $url): string {
            $host = (string) (parse_url($url, PHP_URL_HOST) ?? '');
            return preg_replace('/^www\./', '', strtolower($host));
        };

        // Agrupa imagens disponíveis por domínio de origem, na ordem em que vieram.
        $imagensPorDominio = [];
        foreach ($imagens as $img) {
            $origem = (string) ($img['origin_url'] ?? '');
            $imgUrl = (string) ($img['image_url'] ?? '');
            if ($origem === '' || $imgUrl === '') continue;
            $imagensPorDominio[$dominio($origem)][] = $imgUrl;
        }

        foreach ($noticias as &$noticia) {
            if (!empty($noticia['imagem_url'])) continue; // já tem imagem (ex.: veio de outra fonte)
            $urlNoticia = (string) ($noticia['url'] ?? '');
            if ($urlNoticia === '') continue;
            $dom = $dominio($urlNoticia);
            if (!empty($imagensPorDominio[$dom])) {
                $noticia['imagem_url'] = array_shift($imagensPorDominio[$dom]);
            }
        }
        unset($noticia);

        return $noticias;
    }

    /**
     * Extrai um array/objeto JSON de um texto livre: remove cercas de código
     * markdown (```json ... ```) e, se ainda falhar, tenta capturar o primeiro
     * bloco [...]/{...} do texto. Retorna null se nada for aproveitável.
     */
    private static function extrairJsonDeTexto(string $texto): ?array
    {
        $limpo = trim($texto);
        // Remover cercas de código markdown, se houver.
        $limpo = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $limpo);
        $limpo = trim($limpo);

        $decoded = json_decode($limpo, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Tentar capturar o primeiro array JSON no texto.
        if (preg_match('/\[.*\]/s', $limpo, $m)) {
            $decoded = json_decode($m[0], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }
        // Tentar capturar o primeiro objeto JSON no texto.
        if (preg_match('/\{.*\}/s', $limpo, $m)) {
            $decoded = json_decode($m[0], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * Analisa imagens de referência (templates da marca) via visão (GPT-4o) e
     * devolve uma DESCRIÇÃO textual do estilo visual comum entre elas.
     * Essa descrição é usada para guiar o DALL-E (que não aceita imagem de
     * referência) a gerar imagens no mesmo estilo dos templates.
     *
     * @param array $urlsImagens URLs públicas (https) das imagens de referência
     * @return array ['sucesso'=>bool, 'estilo'=>string, 'erro'=>string|null]
     */
    public static function descreverEstiloTemplates(array $urlsImagens): array
    {
        $apiKey = self::config('openai_key');
        if (empty($apiKey)) {
            return ['sucesso' => false, 'estilo' => '', 'erro' => 'Chave OpenAI não configurada.'];
        }

        // Filtra URLs válidas e limita a 6 imagens (custo/tempo).
        $urls = array_values(array_filter($urlsImagens, fn($u) => filter_var($u, FILTER_VALIDATE_URL)));
        if (empty($urls)) {
            return ['sucesso' => false, 'estilo' => '', 'erro' => 'Nenhuma imagem de referência válida.'];
        }
        $urls = array_slice($urls, 0, 6);

        $instrucao = 'Você é diretor de arte. Analise as imagens de referência a seguir (são posts/templates de uma marca) '
            . 'e descreva, em um único parágrafo objetivo em português, o ESTILO VISUAL comum entre elas para servir de guia '
            . 'na geração de novas imagens: paleta de cores predominante, tipo de iluminação, composição/enquadramento, '
            . 'texturas/estética (ex.: minimalista, 3D, flat, fotográfico, cyberpunk), uso de gradientes, clima/mood e '
            . 'elementos gráficos recorrentes. NÃO descreva o texto escrito nas imagens nem o conteúdo específico — foque '
            . 'apenas no estilo visual reproduzível. Responda apenas com a descrição, sem títulos.';

        // Monta o content multimodal (texto + imagens) para o chat completions.
        $content = [['type' => 'text', 'text' => $instrucao]];
        foreach ($urls as $u) {
            $content[] = ['type' => 'image_url', 'image_url' => ['url' => $u]];
        }

        $model = self::config('openai_modelo_leitura', 'gpt-4o');
        $resultado = self::executarCurl(
            'https://api.openai.com/v1/chat/completions',
            [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            [
                'model' => $model,
                'messages' => [['role' => 'user', 'content' => $content]],
                'max_tokens' => 600,
            ],
            'OpenAI-Visao',
            90
        );

        if (!$resultado['sucesso']) {
            return ['sucesso' => false, 'estilo' => '', 'erro' => $resultado['erro'] ?? 'Falha na análise de estilo.'];
        }

        $texto = $resultado['dados']['choices'][0]['message']['content'] ?? '';
        $texto = trim((string) $texto);
        if ($texto === '') {
            return ['sucesso' => false, 'estilo' => '', 'erro' => 'A IA não retornou descrição de estilo.'];
        }

        return ['sucesso' => true, 'estilo' => $texto, 'erro' => null];
    }

    /**
     * Gera imagem via DALL-E 3
     *
     * @param string $prompt Prompt descritivo da imagem
     * @param string $size   Tamanho (1024x1024, 1792x1024, 1024x1792)
     * @return array ['sucesso' => bool, 'url' => string|null, 'erro' => string|null]
     */
    /**
     * Gera imagem USANDO IMAGENS DE REFERÊNCIA (os templates da marca).
     * Usa o endpoint de edições do gpt-image-1 (images/edits, multipart), que
     * aceita uma ou mais imagens como referência e condiciona a saída ao estilo
     * delas — diferente de images/generations, que só recebe texto.
     * Requer gpt-image-1 (dall-e não suporta múltiplas referências de estilo).
     *
     * @param string   $prompt          Instrução de conteúdo (o que a imagem deve mostrar)
     * @param string[] $caminhosLocais  Caminhos ABSOLUTOS no disco das imagens de referência
     * @param string   $size            Tamanho lógico (1024x1024 | 1024x1536 | 1536x1024)
     * @return array ['sucesso'=>bool, 'url'=>string|null (data URI/base64), 'erro'=>string|null]
     */
    public static function gerarImagemComReferencia(string $prompt, array $caminhosLocais, string $size = '1024x1536'): array
    {
        $apiKey = self::config('openai_key');
        if (empty($apiKey)) {
            return ['sucesso' => false, 'url' => null, 'erro' => 'Chave OpenAI não configurada.'];
        }

        // Filtra caminhos existentes (máx. 4 referências para custo/tempo).
        $refs = [];
        foreach ($caminhosLocais as $c) {
            if (is_string($c) && $c !== '' && is_file($c)) {
                $refs[] = $c;
                if (count($refs) >= 4) break;
            }
        }
        if (empty($refs)) {
            return ['sucesso' => false, 'url' => null, 'erro' => 'Nenhuma imagem de referência disponível no disco.'];
        }

        // gpt-image-1 aceita 1024x1024, 1024x1536 (retrato), 1536x1024 (paisagem).
        [$w, $h] = array_map('intval', array_pad(explode('x', $size), 2, 1024));
        $sizeModelo = $h > $w ? '1024x1536' : ($w > $h ? '1536x1024' : '1024x1024');

        $postfields = [
            'model'  => 'gpt-image-1',
            'prompt' => $prompt,
            'size'   => $sizeModelo,
            'n'      => 1,
        ];
        // Múltiplas imagens de referência. A API aceita o campo repetido image[].
        // Com uma única referência, usa-se 'image'; com várias, 'image[N]'.
        if (count($refs) === 1) {
            $c = $refs[0];
            $postfields['image'] = new \CURLFile($c, self::mimePorExtensao($c), 'ref.' . pathinfo($c, PATHINFO_EXTENSION));
        } else {
            foreach ($refs as $i => $caminho) {
                $postfields['image[' . $i . ']'] = new \CURLFile($caminho, self::mimePorExtensao($caminho), 'ref_' . $i . '.' . pathinfo($caminho, PATHINFO_EXTENSION));
            }
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.openai.com/v1/images/edits',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postfields,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $apiKey],
        ]);
        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            error_log('[O CONSULTOR][ImagemRef] RESULTADO=erro_curl cURL: ' . $err);
            return ['sucesso' => false, 'url' => null, 'erro' => 'Falha de conexão na geração com referência.'];
        }
        $dados = json_decode((string) $response, true);
        if ($httpCode < 200 || $httpCode >= 300) {
            $msg = $dados['error']['message'] ?? ('HTTP ' . $httpCode);
            error_log('[O CONSULTOR][ImagemRef] RESULTADO=http_' . $httpCode . ' modelo=gpt-image-1 refs=' . count($refs)
                . ' | ERRO=' . $msg . ' | BODY=' . substr((string) $response, 0, 800));
            return ['sucesso' => false, 'url' => null, 'erro' => $msg];
        }

        $item = $dados['data'][0] ?? [];
        if (!empty($item['b64_json'])) {
            error_log('[O CONSULTOR][ImagemRef] RESULTADO=ok modelo=gpt-image-1 refs=' . count($refs) . ' formato=b64');
            return ['sucesso' => true, 'url' => 'data:image/png;base64,' . $item['b64_json'], 'erro' => null];
        }
        if (!empty($item['url'])) {
            error_log('[O CONSULTOR][ImagemRef] RESULTADO=ok modelo=gpt-image-1 refs=' . count($refs) . ' formato=url');
            return ['sucesso' => true, 'url' => $item['url'], 'erro' => null];
        }
        error_log('[O CONSULTOR][ImagemRef] RESULTADO=sem_imagem BODY=' . substr((string) $response, 0, 500));
        return ['sucesso' => false, 'url' => null, 'erro' => 'Resposta sem imagem.'];
    }

    /**
     * Analisa UMA imagem de template (visão) e devolve uma descrição detalhada
     * do estilo/composição + para que tipos de conteúdo ela é mais adequada.
     * Usada no upload do template.
     *
     * @param string $urlImagem URL pública (https) da imagem
     * @return array ['sucesso'=>bool, 'descricao'=>string, 'adequado_para'=>string, 'erro'=>string|null]
     */
    public static function descreverTemplateIndividual(string $urlImagem): array
    {
        $apiKey = self::config('openai_key');
        if (empty($apiKey)) {
            return ['sucesso' => false, 'descricao' => '', 'adequado_para' => '', 'erro' => 'Chave OpenAI não configurada.'];
        }
        if (!filter_var($urlImagem, FILTER_VALIDATE_URL)) {
            return ['sucesso' => false, 'descricao' => '', 'adequado_para' => '', 'erro' => 'URL inválida.'];
        }

        $instrucao = 'Você é diretor de arte. Analise esta imagem de referência (um template/post de uma marca) e responda em JSON com: '
            . '{"descricao": "descrição VISUAL detalhada e reproduzível: estilo/estética (fotográfico, 3D, flat, ilustração, cyberpunk...), paleta de cores, iluminação, composição/enquadramento, textura, elementos gráficos e clima — para servir de instrução na geração de novas imagens no mesmo estilo. NÃO descreva o texto escrito na imagem.", '
            . '"adequado_para": "lista curta separada por vírgula dos tipos de conteúdo/post que combinam com este estilo (ex.: carrossel educativo, post de novidade, anúncio/CTA, citação, bastidores)"}. '
            . 'Responda APENAS o JSON.';

        $content = [
            ['type' => 'text', 'text' => $instrucao],
            ['type' => 'image_url', 'image_url' => ['url' => $urlImagem]],
        ];

        $model = self::config('openai_modelo_leitura', 'gpt-4o');
        $resultado = self::executarCurl(
            'https://api.openai.com/v1/chat/completions',
            ['Authorization: Bearer ' . $apiKey, 'Content-Type: application/json'],
            ['model' => $model, 'messages' => [['role' => 'user', 'content' => $content]], 'max_tokens' => 500],
            'OpenAI-Visao-Template',
            90
        );

        if (!$resultado['sucesso']) {
            return ['sucesso' => false, 'descricao' => '', 'adequado_para' => '', 'erro' => $resultado['erro'] ?? 'Falha na análise.'];
        }

        $texto = (string) ($resultado['dados']['choices'][0]['message']['content'] ?? '');
        $json = self::extrairJsonDeTexto($texto);
        if (is_array($json)) {
            return [
                'sucesso' => true,
                'descricao' => trim((string) ($json['descricao'] ?? '')),
                'adequado_para' => trim((string) ($json['adequado_para'] ?? '')),
                'erro' => null,
            ];
        }
        // Se não veio JSON, usa o texto puro como descrição.
        $texto = trim($texto);
        if ($texto !== '') {
            return ['sucesso' => true, 'descricao' => $texto, 'adequado_para' => '', 'erro' => null];
        }
        return ['sucesso' => false, 'descricao' => '', 'adequado_para' => '', 'erro' => 'A IA não retornou descrição.'];
    }

    /** MIME por extensão de arquivo de imagem. */
    private static function mimePorExtensao(string $caminho): string
    {
        $ext = strtolower(pathinfo($caminho, PATHINFO_EXTENSION));
        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'image/png',
        };
    }

    public static function gerarImagem(string $prompt, string $size = '1024x1024'): array
    {
        $apiKey = self::config('openai_key');

        if (empty($apiKey)) {
            return ['sucesso' => false, 'url' => null, 'erro' => 'Chave OpenAI não configurada para geração de imagens.'];
        }

        // Modelo de imagem configurável (padrão gpt-image-1 — dall-e-3 foi
        // descontinuado/indisponível em várias contas). Configure em
        // Admin > Configurações como 'openai_imagem_modelo' se necessário.
        $modelo = self::config('openai_imagem_modelo', 'gpt-image-1');

        $resultado = self::gerarImagemRequest($apiKey, $modelo, $prompt, $size);

        if (!$resultado['sucesso']) {
            $erro = $resultado['erro'] ?? '';
            // Se o modelo não existe/indisponível, tenta fallback automático.
            if (stripos($erro, 'does not exist') !== false || stripos($erro, 'invalid_value') !== false || stripos($erro, 'model') !== false) {
                foreach (['gpt-image-1', 'dall-e-3', 'dall-e-2'] as $fallback) {
                    if ($fallback === $modelo) continue;
                    $tentativa = self::gerarImagemRequest($apiKey, $fallback, $prompt, $size);
                    if ($tentativa['sucesso']) { $resultado = $tentativa; break; }
                }
            }
            // Se rejeitado por política de conteúdo, simplifica o prompt.
            if (!$resultado['sucesso'] && stripos($erro, 'content_policy') !== false) {
                $resultado = self::gerarImagemRequest($apiKey, $modelo, self::simplificarPromptImagem($prompt), $size);
            }
            if (!$resultado['sucesso']) {
                return ['sucesso' => false, 'url' => null, 'erro' => $resultado['erro'] ?? 'Imagem não gerada.'];
            }
        }

        // Extrai URL ou base64 da resposta (formatos variam entre modelos).
        $item = $resultado['dados']['data'][0] ?? [];
        if (!empty($item['url'])) {
            return ['sucesso' => true, 'url' => $item['url'], 'erro' => null];
        }
        if (!empty($item['b64_json'])) {
            // Devolve como data URI para reaproveitar a lógica de download existente.
            return ['sucesso' => true, 'url' => 'data:image/png;base64,' . $item['b64_json'], 'erro' => null];
        }

        self::logErro('Imagem', 'Resposta sem URL/b64 de imagem', $resultado['dados']);
        return ['sucesso' => false, 'url' => null, 'erro' => 'Imagem não retornada pela API.'];
    }

    /**
     * Executa a requisição de geração de imagem para um modelo específico,
     * montando o corpo conforme as particularidades de cada modelo.
     */
    private static function gerarImagemRequest(string $apiKey, string $modelo, string $prompt, string $size): array
    {
        // Normaliza o tamanho conforme o modelo (cada um aceita tamanhos diferentes):
        //  - orientação desejada é derivada do $size recebido (quadrado/retrato/paisagem);
        //  - gpt-image-1: 1024x1024 | 1024x1536 (retrato) | 1536x1024 (paisagem);
        //  - dall-e-3:    1024x1024 | 1024x1792 (retrato) | 1792x1024 (paisagem);
        //  - dall-e-2:    apenas 1024x1024.
        [$w, $h] = array_map('intval', array_pad(explode('x', $size), 2, 1024));
        $orientacao = $h > $w ? 'retrato' : ($w > $h ? 'paisagem' : 'quadrado');
        if (stripos($modelo, 'dall-e-2') !== false) {
            $sizeModelo = '1024x1024';
        } elseif (stripos($modelo, 'dall-e') !== false) {
            $sizeModelo = $orientacao === 'retrato' ? '1024x1792' : ($orientacao === 'paisagem' ? '1792x1024' : '1024x1024');
        } else { // gpt-image-1 e afins
            $sizeModelo = $orientacao === 'retrato' ? '1024x1536' : ($orientacao === 'paisagem' ? '1536x1024' : '1024x1024');
        }

        $body = [
            'model'  => $modelo,
            'prompt' => $prompt,
            'n'      => 1,
            'size'   => $sizeModelo,
        ];
        // 'quality' e 'response_format' têm valores/aceitação diferentes por modelo.
        if (stripos($modelo, 'dall-e') !== false) {
            $body['quality'] = 'standard';
            $body['response_format'] = 'url'; // dall-e retorna URL temporária
        }
        // gpt-image-1: retorna b64_json por padrão; não aceita response_format=url.

        return self::executarCurl(
            'https://api.openai.com/v1/images/generations',
            [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            $body,
            'Imagem(' . $modelo . ')',
            120
        );
    }

    // =========================================================================
    // MÉTODOS DE DECISÃO — QUAL API USAR
    // =========================================================================

    /**
     * Chama a API de análise/geração de texto (GPT ou Claude, conforme config)
     * Se ambas ativas: GPT padrão, Claude fallback.
     * Se apenas Claude: usa Claude.
     * Se nenhuma: retorna erro.
     */
    public static function chamarAnalise(string $prompt, bool $jsonMode = true): array
    {
        $gptAtivo = Configuracao::apiAtiva('openai');
        $claudeAtivo = Configuracao::apiAtiva('anthropic');

        if (!$gptAtivo && !$claudeAtivo) {
            return ['sucesso' => false, 'conteudo' => null, 'erro' => 'Nenhuma API de IA ativa. Configure em Admin > APIs de Conteúdo.'];
        }

        // Tentar GPT primeiro (se ativo)
        if ($gptAtivo) {
            $resultado = self::chamarOpenAI($prompt, null, $jsonMode);
            if ($resultado['sucesso']) {
                return $resultado;
            }
            // Fallback para Claude se GPT falhou e Claude está ativo
            if ($claudeAtivo) {
                self::logErro('Fallback', 'GPT falhou, tentando Claude', ['erro_gpt' => $resultado['erro']]);
                return self::chamarAnthropic($prompt);
            }
            return $resultado;
        }

        // Apenas Claude ativo
        return self::chamarAnthropic($prompt);
    }

    /**
     * Chama a API de busca (Perplexity prioritária, Claude como fallback genérico)
     */
    public static function chamarBusca(string $prompt): array
    {
        $perplexityAtivo = Configuracao::apiAtiva('perplexity');

        if ($perplexityAtivo) {
            return self::chamarPerplexity($prompt);
        }

        // Fallback: usar Claude/GPT para busca genérica
        return self::chamarAnalise($prompt, true);
    }

    // =========================================================================
    // PROMPTS ESPECIALIZADOS POR MÓDULO
    // =========================================================================

    /**
     * Gera prompt para SOP individual
     */
    public static function buildPromptSop(array $empresa, array $sop, string $contextoDocumentos = ''): string
    {
        $setor = isset($empresa['setor']) ? $empresa['setor'] : (isset($empresa['segmento']) ? $empresa['segmento'] : 'Tecnologia');
        $normas = self::getNormasPorSetor($setor);
        $setorEmpresa = $setor;

        return "Você é O Consultor, especialista em padronização operacional empresarial com profundo conhecimento em normas e padrões de mercado.

DADOS DA EMPRESA:
Nome: {$empresa['nome']}
Setor: {$setorEmpresa}
Porte: {$empresa['colaboradores']} colaboradores, faturamento {$empresa['faturamento']}, nível de maturidade {$empresa['maturidade']}/4
Departamentos ativos: {$empresa['departamentos']}
Ferramentas utilizadas: {$empresa['ferramentas']}
Problemas identificados: {$empresa['problemas']}
Objetivos estratégicos: {$empresa['objetivos']}

PADRÃO DE MERCADO APLICÁVEL:
{$normas}

{$contextoDocumentos}

SOP A GERAR: {$sop['id']} — {$sop['nome']}
DEPARTAMENTO: {$sop['departamento']}

SUBTÓPICOS OBRIGATÓRIOS PARA ESTE SOP:
{$sop['subtopicos_texto']}

INSTRUÇÕES DE QUALIDADE — OBRIGATÓRIAS:
1. PROFUNDIDADE: cada procedimento deve ser detalhado o suficiente para ser executado por qualquer colaborador sem supervisão.
2. ESPECIFICIDADE: use o nome das ferramentas reais da empresa nos procedimentos.
3. SUBTÓPICOS INDEPENDENTES: cada subtópico deve ter seu próprio conjunto completo de procedimentos (10+ passos ÚNICOS por subtópico).
4. PROCEDIMENTOS POR SUBTÓPICO: gere procedimentos específicos para cada subtópico, não genéricos.
5. PLANOS DE CONTINGÊNCIA: cada nível (N1/N2/N3) deve ter situação gatilho clara, ação passo a passo e responsáveis definidos.
6. KPIs: metas numéricas específicas com zonas verde/amarela/vermelha e ação automática.
7. N3 JURÍDICO: N3 deve incluir procedimento jurídico detalhado + comunicação externa + documentação legal.
8. DOCUMENTOS EXISTENTES: Se foram fornecidos documentos da empresa, use-os como base para adaptar os procedimentos à realidade atual da empresa, identificando o que já existe e o que precisa ser melhorado ou criado.

GERE O SOP COMPLETO COM OS 13 COMPONENTES:
1. objetivo (string 3-5 frases)
2. escopo (object: {aplica_se: string, nao_aplica: string})
3. subtopicos (array: [{nome, descricao}])
4. responsaveis (array: [{papel, cargo}])
5. prerequisitos (array de strings, mínimo 6)
6. ferramentas (array de strings)
7. procedimentos (array POR SUBTÓPICO: [{subtopico: \"A\", passos: [{passo, acao, responsavel, prazo, sistema, validacao}]}] — mínimo 10 passos ÚNICOS por subtópico)
8. checklist (array de strings, mínimo 12)
9. evidencias (array de strings, mínimo 5)
10. relatorios (array: [{oque, para_quem, frequencia, canal}])
11. kpis (array: [{kpi, verde, amarela, vermelha, acao_vermelha}])
12. contencao (object: {n1: {situacao, acao, quem, escalar}, n2: {situacao, acao, quem, escalar}, n3: {situacao, acao, quem, comunicacao, documentacao}})
13. versionamento (object: {versao: '1.0', data: '" . date('Y-m-d') . "', aprovador: 'Pendente'})

IMPORTANTE: O campo 'procedimentos' deve ser um ARRAY com um objeto para cada subtópico:
[
  {\"subtopico\": \"A\", \"passos\": [{passo: 1, acao: \"...\", responsavel: \"...\", prazo: \"...\", sistema: \"...\", validacao: \"...\"}]},
  {\"subtopico\": \"B\", \"passos\": [{passo: 1, acao: \"...\", responsavel: \"...\", prazo: \"...\", sistema: \"...\", validacao: \"...\"}]},
  {\"subtopico\": \"C\", \"passos\": [{passo: 1, acao: \"...\", responsavel: \"...\", prazo: \"...\", sistema: \"...\", validacao: \"...\"}]}
]

Responda APENAS em JSON válido. Não inclua texto fora do JSON.";
    }

    /**
     * CHAMADA 1 — Diagnóstico e Estrutura Organizacional 
     * Gera o "cérebro" do processo: diagnóstico + lista exata de setores/SOPs
     */
    public static function buildPromptDiagnosticoEstrutura(array $dadosEmpresa): string
    {
        return "Você é um consultor sênior de estruturação organizacional. Sua tarefa nesta etapa NÃO é escrever os SOPs — é diagnosticar a empresa e definir com precisão QUAIS setores e QUAIS SOPs ela precisa, para que cada um seja detalhado individualmente depois.

=========================== DADOS DE ENTRADA ===========================
Nome da empresa: {$dadosEmpresa['nome']}
Nicho/segmento: {$dadosEmpresa['nicho']}
Sub-nicho (se houver): {$dadosEmpresa['subnicho']}
Porte da empresa: {$dadosEmpresa['porte']}
Modelo de negócio: {$dadosEmpresa['modelo_negocio']}
Principais produtos/serviços: {$dadosEmpresa['produtos_servicos']}
Público-alvo: {$dadosEmpresa['publico_alvo']}
Número de funcionários atual: {$dadosEmpresa['num_funcionarios']}
Faturamento aproximado (opcional): {$dadosEmpresa['faturamento']}
Localização/regionalidade: {$dadosEmpresa['localizacao']}
Canais de venda: {$dadosEmpresa['canais_venda']}
Principais dores/desafios relatados: {$dadosEmpresa['dores_desafios']}
Ferramentas/sistemas já usados: {$dadosEmpresa['ferramentas_atuais']}
Estágio da empresa: {$dadosEmpresa['estagio']}

=========================== O QUE FAZER ===========================
1. Classifique o nicho em uma macro-categoria (construção civil, saúde/estética, e-commerce, educação, alimentação, imobiliário, jurídico, tecnologia, beleza, fitness, turismo, indústria, logística, consultoria, financeiro, marketing, automotivo, agronegócio, terceiro setor, ou outra).

2. Monte a estrutura de setores: os 10 setores base (Captação, Comercial, Financeiro, Atendimento, Suporte, Operacional, RH, Administrativo, TI, Qualidade) + os setores específicos exigidos pelo nicho.

3. Para cada setor, ADAPTADO ao nicho e porte informados (não genérico), liste os SOPs essenciais que esse setor precisa ter documentados — pense em TODAS as situações recorrentes e também nas situações de exceção/crise que alguém da equipe pode enfrentar sozinho.

4. Priorize os SOPs por criticidade (1 = crítico/gera risco ou perda financeira se não documentado, 2 = importante, 3 = complementar).

5. Não gere o conteúdo dos SOPs aqui — apenas identifique e liste. O conteúdo profundo será gerado em outra etapa.

=========================== FORMATO DE SAÍDA (APENAS JSON, sem texto fora do JSON, sem markdown, sem comentários) ===========================
{
  \"empresa\": \"{$dadosEmpresa['nome']}\",
  \"macro_categoria\": \"string\",
  \"diagnostico\": {
    \"nivel_maturidade\": \"inicial | em_estruturacao | em_crescimento | consolidada\",
    \"principais_riscos\": [\"string\", \"string\"],
    \"dores_conectadas_a_setores\": [
      {\"dor\": \"string\", \"setor_relacionado\": \"string\", \"explicacao\": \"string\"}
    ],
    \"prioridades_recomendadas\": [\"string\", \"string\"]
  },
  \"setores\": [
    {
      \"nome_setor\": \"string\",
      \"tipo\": \"base | especifico_do_nicho\",
      \"funcao_no_negocio\": \"string\",
      \"responsavel_sugerido\": \"string (cargo, considerando o porte)\",
      \"sops\": [
        {
          \"id_sop\": \"string (slug único, ex: financeiro-cobranca-inadimplencia)\",
          \"nome_sop\": \"string\",
          \"objetivo_resumido\": \"string (1-2 frases)\",
          \"criticidade\": 1,
          \"gatilho_de_entrada\": \"string (o que dispara a execução desse SOP)\"
        }
      ]
    }
  ]
}

Responda APENAS com o JSON válido. Não inclua texto adicional fora do JSON.";
    }

    /**
     * CHAMADA 3 — Gerador de SOP Profundo com NOVA ARQUITETURA N1-N2-N3
     * Esta chamada usa o detalhamento da Etapa 2B para gerar SOPs completos
     */
    public static function buildPromptSOPProfundo(array $contextosEmpresa, array $dadosSOP, array $detalhamentoCompleto = []): string
    {
        // Se houver detalhamento da Etapa 2B, incluir no contexto
        $contextoDetalhamento = '';
        if (!empty($detalhamentoCompleto)) {
            $contextoDetalhamento = "
=========================== DETALHAMENTO COMPLETO (Etapa 2B) ===========================
ATENÇÃO: Use este detalhamento como base OBRIGATÓRIA para construir o SOP. Todos os problemas e soluções N1-N2-N3 listados abaixo DEVEM estar no SOP final.

" . json_encode($detalhamentoCompleto, JSON_UNESCAPED_UNICODE) . "

";
        }

        return "Você é um especialista em documentação operacional e treinamento de equipes, com profundo conhecimento prático do nicho \"{$contextosEmpresa['nicho']}\" ({$contextosEmpresa['macro_categoria']}). Sua tarefa é criar UM ÚNICO SOP (Procedimento Operacional Padrão) extremamente detalhado e PRÁTICO, que qualquer pessoa nova na empresa consiga executar SOZINHA, incluindo cenários problemáticos e de crise.

=========================== CONTEXTO DA EMPRESA ===========================
Empresa: {$contextosEmpresa['nome']}
Nicho: {$contextosEmpresa['nicho']} | Macro-categoria: {$contextosEmpresa['macro_categoria']}
Porte: {$contextosEmpresa['porte']} | Estágio: {$contextosEmpresa['estagio']}
Modelo de negócio: {$contextosEmpresa['modelo_negocio']}
Produtos/serviços: {$contextosEmpresa['produtos_servicos']}
Público-alvo: {$contextosEmpresa['publico_alvo']}
Ferramentas/sistemas usados: {$contextosEmpresa['ferramentas_atuais']}
Nível de maturidade organizacional: {$contextosEmpresa['nivel_maturidade']}

{$contextoDetalhamento}=========================== SOP A CRIAR ===========================
Setor: {$dadosSOP['nome_setor']}
Responsável: {$dadosSOP['responsavel_sugerido']}
SOP: {$dadosSOP['nome_sop']}
Objetivo: {$dadosSOP['objetivo_resumido']}
Gatilho: {$dadosSOP['gatilho_de_entrada']}
Criticidade: {$dadosSOP['criticidade']}

=========================== ESTRUTURA OBRIGATÓRIA - 15 SEÇÕES COMPLETAS ===========================
Crie o SOP com TODAS as seções, sendo extremamente específico e prático:

# SOP: {$dadosSOP['nome_sop']}
**Código:** {$dadosSOP['id_sop']} | **Versão:** 1.0 | **Data:** " . date('d/m/Y') . "
**Setor:** {$dadosSOP['nome_setor']} | **Criticidade:** {$dadosSOP['criticidade']} | **Responsável:** {$dadosSOP['responsavel_sugerido']}

## 1. CABEÇALHO E IDENTIFICAÇÃO
- Código do SOP, versão, datas de criação/revisão
- Dono do processo, aprovador, revisores
- Histórico de mudanças (incluir versão 1.0 inicial)

## 2. OBJETIVO
Por que este SOP existe, o que garante, riscos de não seguir

## 3. ESCOPO  
O que este SOP cobre (inclui) e o que NÃO cobre (exclui)

## 4. GLOSSÁRIO
Termos técnicos específicos usados neste processo

## 5. MATRIZ RACI
R=Responsável, A=Aprovador, C=Consultado, I=Informado para cada etapa crítica

## 6. PRÉ-REQUISITOS
O que deve estar pronto/disponível antes de iniciar

## 7. PROCEDIMENTO PADRÃO (CENÁRIO IDEAL)
Passo-a-passo detalhado quando tudo funciona perfeitamente:
- Etapa 1: [ação específica] - Responsável: X - Tempo: Y min
- Etapa 2: [ação específica] - Responsável: X - Tempo: Y min
- Continue até completar todo o fluxo normal

## 8. PONTOS DE CONTROLE E VALIDAÇÃO
Verificações obrigatórias durante o processo

## 9. MAPA DE PROBLEMAS E CONTENÇÕES N1-N2-N3
**PROBLEMA 1: [Nome específico do problema]**
- **Sinais de alerta:** Como identificar
- **N1 (0-30min):** Ação imediata pelo próprio executor
- **N2 (30min-4h):** Escalação com supervisor/líder
- **N3 (4h+):** Medidas extremas, direção, contingência

**PROBLEMA 2: [Próximo problema específico]**
- **Sinais de alerta:** 
- **N1:** 
- **N2:** 
- **N3:** 

(Continue para TODOS os problemas possíveis - mínimo 5-8 problemas diferentes)

## 10. FERRAMENTAS E SISTEMAS
Lista detalhada com acesso, configurações específicas

## 11. KPIs E MÉTRICAS
Como medir se o processo está funcionando bem

## 12. RISCOS E NÃO-CONFORMIDADES
O que pode dar errado e como prevenir

## 13. DOCUMENTAÇÃO E EVIDÊNCIAS
O que deve ser registrado/arquivado obrigatoriamente

## 14. MELHORIAS RECOMENDADAS
Próximos passos para otimizar este processo

## 15. ANEXOS
- **Checklist Rápido:** 10-15 itens verificáveis
- **Fluxograma Textual:** Fluxo de decisão passo-a-passo
- **Templates:** Modelos de documentos necessários

=========================== INSTRUÇÕES CRÍTICAS ===========================
1. **REALISMO:** Use situações que REALMENTE acontecem no dia-a-dia
2. **PROBLEMAS N1-N2-N3:** Cada problema deve ter 3 níveis distintos de resposta
3. **ESPECIFICIDADE:** Nada genérico - tudo adaptado ao nicho da empresa
4. **EXECUTABILIDADE:** Qualquer pessoa deve conseguir seguir sozinha
5. **COMPLETUDE:** Todas as 15 seções obrigatórias devem estar presentes

Responda em Markdown bem estruturado, português do Brasil, formato profissional.";
    }

    // ===== NOVA ARQUITETURA DETALHADA - ETAPA 2A =====

    /**
     * ETAPA 2A: Lista TODOS os serviços possíveis para um setor específico
     * Uma chamada por setor identificado na Etapa 1
     */
    public static function buildPromptMontagemFinal(array $diagnosticoJson, array $estruturaJson, string $todosOsSops): string
    {
        $diagnostico = json_encode($diagnosticoJson, JSON_UNESCAPED_UNICODE);
        $estrutura = json_encode($estruturaJson, JSON_UNESCAPED_UNICODE);
        
        return "Você é um editor técnico. Você vai montar o MANUAL COMPLETO DA EMPRESA a partir de partes já geradas. Não invente novo conteúdo de SOP — apenas organize, conecte e complemente com as seções estruturais abaixo.

=========================== MATERIAL JÁ GERADO (input) ===========================
Diagnóstico da empresa (JSON da Chamada 1): {$diagnostico}
Lista de setores e SOPs (JSON da Chamada 1): {$estrutura}
Conteúdo completo de todos os SOPs gerados (Chamada 2): {$todosOsSops}

=========================== O QUE GERAR ===========================
# MANUAL COMPLETO DA EMPRESA — {$estruturaJson['empresa']}

## 1. Informações da Empresa
(sintetize os dados de entrada em texto legível)

## 2. Diagnóstico Inicial
(use o diagnóstico já gerado na Chamada 1, apresentado de forma legível)

## 3. Estrutura Organizacional Completa
(apresente o organograma textual: setores base + específicos do nicho, com função e responsável de cada um)

## 4. Fluxo de Processos Entre Setores
(mapeie como o trabalho flui entre os setores listados, do primeiro contato com cliente até o pós-venda/suporte, específico para o modelo de negócio informado)

## 5. SOPs por Setor
(organize TODOS os SOPs já gerados na Chamada 2, agrupados por setor, mantendo a estrutura N1/N2/N3/checklists de cada um — não resuma o conteúdo, apenas organize e insira)

## 6. Roadmap de Implementação
(sugira ordem de implementação: primeiros 30 dias, 90 dias, 6 meses, considerando criticidade dos SOPs definida na Chamada 1)

## 7. Próximos Passos Recomendados
(3 a 5 ações imediatas)

Regras: não altere o conteúdo técnico dos SOPs já gerados, apenas formate e conecte. Português do Brasil, Markdown com headers.

Responda em formato Markdown completo e bem estruturado.";
    }

    // ===== NOVA ARQUITETURA DETALHADA - ETAPA 2A =====

    /**
     * ETAPA 2A: Lista TODOS os serviços possíveis para um setor específico
     * Uma chamada por setor identificado na Etapa 1
     */
    public static function buildPromptListagemServicos(string $setor, array $dadosEmpresa): string
    {
        return "Você é um consultor especialista no setor \"{$setor}\" para empresas do nicho \"{$dadosEmpresa['nicho']}\" ({$dadosEmpresa['macro_categoria']}). Sua tarefa é listar TODOS os tipos de serviços/processos possíveis que este setor pode executar numa empresa deste perfil.

=========================== CONTEXTO DA EMPRESA ===========================
Nome: {$dadosEmpresa['nome']}
Nicho: {$dadosEmpresa['nicho']}
Porte: {$dadosEmpresa['porte']} funcionários
Modelo de negócio: {$dadosEmpresa['modelo_negocio']}
Produtos/serviços: {$dadosEmpresa['produtos_servicos']}
Público-alvo: {$dadosEmpresa['publico_alvo']}
Desafios principais: {$dadosEmpresa['dores_desafios']}
Ferramentas atuais: {$dadosEmpresa['ferramentas_atuais']}

=========================== MAPEAMENTO ULTRA-AMPLO DE SERVIÇOS ===========================
Pense em TODOS os serviços/processos que o setor \"{$setor}\" pode executar, incluindo:

## 1. PROCESSOS CORE (Essenciais)
- Atividades principais e específicas do setor
- Responsabilidades primárias no modelo de negócio
- Entregas obrigatórias para funcionamento da empresa

## 2. PROCESSOS OPERACIONAIS (Rotina)
- Tarefas diárias, semanais, mensais
- Manutenções preventivas e corretivas
- Monitoramentos e controles regulares

## 3. PROCESSOS ESTRATÉGICOS (Crescimento)
- Planejamento e desenvolvimento
- Inovação e melhorias
- Expansão e otimização

## 4. PROCESSOS DE INTEGRAÇÃO (Interface)
- Comunicação com outros setores
- Transferência de dados/informações
- Colaboração interdepartamental

## 5. PROCESSOS DE EXCEÇÃO (Não-padrão)
- Situações atípicas ou especiais
- Customizações e adaptações
- Demandas urgentes ou extraordinárias

## 6. PROCESSOS DE CRISE (Problemas)
- Falhas técnicas e operacionais
- Contingências e recuperação
- Gestão de incidentes críticos

## 7. PROCESSOS DE CONFORMIDADE (Governança)
- Auditorias e controles
- Documentação e compliance
- Segurança e privacidade

## 8. PROCESSOS SAZONAIS (Temporais)
- Atividades específicas de períodos
- Picos de demanda ou baixa temporada
- Fechamentos e balanços periódicos

=========================== EXEMPLOS POR SETOR ===========================

=========================== DIRETRIZES UNIVERSAIS PARA TODOS OS SETORES ===========================

**PENSE COMO UM ESPECIALISTA DO SETOR**: Você deve conhecer profundamente TODOS os processos possíveis, desde os mais básicos até os mais complexos.

**CUBRA TODO O ESPECTRO DE ATIVIDADES**:
- ✅ Processos diários, semanais, mensais, trimestrais, anuais
- ✅ Situações de rotina, exceção, crise e emergência  
- ✅ Atividades operacionais, táticas e estratégicas
- ✅ Integrações com outros departamentos
- ✅ Compliance, auditoria e controles internos
- ✅ Projetos de melhoria e implementação
- ✅ Treinamento e desenvolvimento de equipe

**CONSIDERE DIFERENTES CENÁRIOS**:
- 📊 **Rotina normal**: processos que funcionam perfeitamente
- 🔥 **Situações críticas**: prazos apertados, alta demanda, recursos limitados
- ⚠️ **Problemas técnicos**: falhas de sistema, equipamentos, comunicação
- 👥 **Resistência humana**: dificuldades de equipe, treinamento, mudanças
- 🏢 **Contexto empresarial**: crescimento, crise, reestruturação, sazonalidade

**GRANULARIDADE MÁXIMA**: 
Quebre processos grandes em sub-processos específicos. Por exemplo:
- Ao invés de \"Gestão de estoque\" → \"Controle de entrada de materiais\", \"Inventário cíclico mensal\", \"Análise de giro de produtos\", \"Gestão de produtos obsoletos\"
- Ao invés de \"Treinamento\" → \"Onboarding de novos funcionários\", \"Treinamento técnico em sistemas\", \"Capacitação em vendas\", \"Desenvolvimento de liderança\"

=========================== EXEMPLOS ULTRA-AMPLOS POR SETOR ===========================

**Se o setor for T.I./Tecnologia, inclua TODOS estes tipos:**
- **Infraestrutura:** backup diário, migração de servidores, implementação de cloud, disaster recovery, capacity planning, configuração de redes, manutenção de datacenter, virtualização, storage management
- **Desenvolvimento:** versionamento de código, deploy automatizado, integração de APIs, desenvolvimento mobile, testes unitários, code review, documentação técnica, refatoração de código
- **Segurança:** gestão de patches, controle de acesso, incident response, pentest, auditoria de vulnerabilidades, firewall management, compliance LGPD, backup de segurança
- **Dados:** ETL processes, data analytics, business intelligence, data governance, arquivamento, sincronização de sistemas, migração de dados, relatórios automatizados
- **Suporte:** helpdesk níveis 1/2/3, suporte remoto, instalação de software, troubleshooting, treinamento de usuários, gestão de inventário de TI
- **Projetos:** implementação de ERP, rollout de software, automação de processos, integração de sistemas, upgrade de infraestrutura, migração de plataformas

**Se o setor for Vendas/Comercial, inclua TODOS estes tipos:**
- **Prospecção:** geração de leads, qualificação de prospects, cold calling, email marketing, social selling, networking em eventos, pesquisa de mercado, análise de concorrentes
- **Negociação:** elaboração de propostas comerciais, apresentações de vendas, tratamento de objeções, fechamento de contratos, negociação de preços, follow-up pós-proposta
- **Relacionamento:** pós-venda ativo, programa de fidelização, upsell e cross-sell, gestão de contas-chave, customer success, renovação de contratos, relacionamento com parceiros
- **Gestão:** administração de pipeline, forecasting de vendas, análise de métricas de performance, gestão de territórios, coaching de equipe, planejamento estratégico
- **Suporte:** treinamento de produtos, criação de materiais de venda, configuração de CRM, automação de processos comerciais, análise de ROI de campanhas

**Se o setor for Marketing, inclua TODOS estes tipos:**
- **Marketing Digital:** gestão de redes sociais, campanhas Google Ads, SEO/SEM, email marketing, automação de marketing, marketing de conteúdo, influencer marketing
- **Criação de Conteúdo:** blog corporativo, produção de vídeos, design gráfico, cases de sucesso, whitepapers, webinars, podcasts, newsletters
- **Eventos e Relacionamento:** participação em feiras, organização de eventos corporativos, networking estratégico, parcerias comerciais, relações públicas
- **Branding:** desenvolvimento de identidade visual, posicionamento de marca, rebranding, manual de marca, comunicação corporativa, brand awareness
- **Analytics e Pesquisa:** análise de métricas, ROI de campanhas, pesquisa de mercado, customer journey mapping, análise de concorrência, market intelligence

**Se o setor for RH/Recursos Humanos, inclua TODOS estes tipos:**
- **Recrutamento e Seleção:** sourcing de candidatos, triagem de currículos, entrevistas técnicas e comportamentais, testes psicotécnicos, verificação de referências, onboarding completo
- **Desenvolvimento de Pessoas:** programas de treinamento, planos de carreira, avaliação de performance, feedback 360°, coaching interno, mentoring, sucessão de lideranças
- **Administração de Pessoal:** folha de pagamento, administração de benefícios, contratos de trabalho, políticas internas, gestão de ponto, controle de férias e licenças
- **Cultura e Engajamento:** pesquisa de clima organizacional, programa de engajamento, eventos corporativos, comunicação interna, diversidade e inclusão, employer branding
- **Compliance e Legal:** compliance trabalhista, auditoria de processos, relacionamento sindical, prevenção de passivos trabalhistas, gestão de contingências

**Se o setor for Financeiro/Contábil, inclua TODOS estes tipos:**
- **Contas a Pagar e Receber:** controle de recebimentos, gestão de pagamentos, conciliação bancária, cobrança de inadimplentes, negociação com fornecedores, gestão de fluxo de caixa
- **Controle Financeiro:** elaboração de orçamento, análise de custos e margens, controle de centros de custo, análise de variações, relatórios gerenciais, indicadores financeiros
- **Contabilidade:** escrituração contábil, balancetes mensais, demonstrações financeiras, conciliações contábeis, análise de balanço, auditoria contábil
- **Impostos e Fiscal:** apuração de tributos, declarações obrigatórias, planejamento tributário, compliance fiscal, gestão de contingências fiscais, relacionamento com Receita Federal
- **Tesouraria:** aplicações financeiras, análise de financiamentos, gestão de capital de giro, relacionamento bancário, análise de viabilidade de projetos, gestão de risco financeiro

**Se o setor for Operações/Produção, inclua TODOS estes tipos:**
- **Planejamento da Produção:** programação da produção, gestão de demanda, capacity planning, sequenciamento de ordens, balanceamento de linha, controle de materiais
- **Controle de Qualidade:** inspeção de produtos, auditoria de processos, gestão de não-conformidades, melhorias contínuas, certificações ISO, controle estatístico de qualidade
- **Logística e Suprimentos:** gestão de estoque, recebimento de materiais, expedição de produtos, controle de inventário, relacionamento com fornecedores, otimização de layout
- **Manutenção:** manutenção preventiva, corretiva e preditiva, gestão de ativos, planejamento de paradas programadas, controle de peças de reposição, TPM
- **Segurança e Meio Ambiente:** programa de segurança do trabalho, treinamentos de CIPA, análise de riscos, investigação de acidentes, gestão ambiental, compliance de normas

**Se o setor for Atendimento/Customer Service, inclua TODOS estes tipos:**
- **Suporte ao Cliente:** atendimento telefônico, chat online, suporte por email, gestão de tickets, escalação de problemas, suporte técnico especializado
- **Relacionamento com Cliente:** acompanhamento pós-venda, programa de fidelização, pesquisa de satisfação, gestão de reclamações, customer journey management
- **Customer Success:** onboarding de clientes, treinamento em produtos, análise de health score, programa de retenção, expansion revenue, análise de churn
- **Processos de Atendimento:** definição de SLA, criação de scripts, treinamento de equipe, métricas de qualidade, análise de performance, base de conhecimento

**Se o setor for Jurídico/Legal, inclua TODOS estes tipos:**
- **Contratos:** elaboração de contratos, revisão jurídica, negociação de cláusulas, gestão de aditivos, controle de vencimentos, análise de riscos contratuais
- **Compliance:** auditoria de conformidade, políticas internas, treinamentos regulatórios, gestão de riscos legais, compliance setorial específico
- **Contencioso:** gestão de processos judiciais, relacionamento com escritórios externos, acompanhamento de prazos processuais, análise de contingências
- **Societário:** assembleia de acionistas, alterações contratuais, registro em órgãos competentes, governança corporativa, due diligence
- **Propriedade Intelectual:** registro de marcas e patentes, proteção de ativos intangíveis, contratos de licenciamento, análise de infração

**Se o setor for Compras/Procurement, inclua TODOS estes tipos:**
- **Sourcing:** pesquisa de fornecedores, qualificação de suppliers, análise de mercado, benchmarking de preços, desenvolvimento de fornecedores
- **Negociação:** cotações e licitações, negociação de contratos, análise de propostas, gestão de SLA com fornecedores, renegociação de termos
- **Gestão de Fornecedores:** avaliação de performance, auditorias de fornecedores, relacionamento estratégico, gestão de riscos de supply chain
- **Processos de Compra:** requisições de compra, aprovações de gastos, controle orçamentário, análise de make-or-buy, gestão de categoria de produtos

**Se o setor for Logística/Supply Chain, inclua TODOS estes tipos:**
- **Armazenagem:** gestão de estoque, controle de inventário, layout de armazém, picking e packing, controle de temperatura/umidade
- **Transporte:** gestão de frota, roteirização, controle de entregas, gestão de transportadoras, rastreamento de cargas, otimização de rotas
- **Distribuição:** planejamento de distribuição, gestão de centros de distribuição, cross-docking, fulfillment, reverse logistics
- **Supply Planning:** planejamento de demanda, gestão de safety stock, análise de giro de estoque, S&OP (Sales & Operations Planning)

**Se o setor for Qualidade/QA, inclua TODOS estes tipos:**
- **Controle de Qualidade:** inspeção de entrada, controle de processo, inspeção final, testes de produto, calibração de equipamentos
- **Sistemas de Gestão:** implementação ISO 9001, auditoria interna, gestão de não-conformidades, ações corretivas e preventivas, melhoria contínua
- **Desenvolvimento de Produto:** validação de novos produtos, testes de durabilidade, análise de falhas, controle de mudanças de engenharia
- **Fornecedores:** qualificação de fornecedores, auditoria de qualidade, gestão de recall, controle de materiais críticos

=========================== CRITÉRIOS DE ABRANGÊNCIA ===========================

**SEJA ULTRA-ESPECÍFICO**: Para cada categoria, liste pelo menos 8-15 serviços diferentes
**PENSE EM CENÁRIOS REAIS**: Inclua serviços que realmente acontecem (não apenas teoria)
**CONSIDERE DIFERENTES COMPLEXIDADES**: Do mais simples ao mais complexo
**INCLUA INTEGRAÇÕES**: Serviços que envolvem outros setores
**ABORDE TODO O CICLO**: Do planejamento à execução e monitoramento

=========================== RESPOSTA ESPERADA ===========================
Forneça um JSON com esta estrutura:

```json
{
    \"setor\": \"{$setor}\",
    \"empresa_contexto\": \"{$dadosEmpresa['nome']} - {$dadosEmpresa['nicho']}\",
    \"total_servicos\": 0,
    \"servicos\": [
        {
            \"nome\": \"Nome específico do serviço\",
            \"categoria\": \"core|operacional|estrategico|integracao|excecao|crise|conformidade|sazonal\",
            \"criticidade\": \"alta|media|baixa\",
            \"frequencia\": \"diaria|semanal|mensal|trimestral|anual|sob_demanda|emergencial\",
            \"complexidade\": \"simples|media|alta\",
            \"descricao_resumida\": \"Uma frase explicando o que é\",
            \"integracao_setores\": [\"setor1\", \"setor2\"],
            \"recursos_principais\": [\"recurso1\", \"recurso2\"]
        }
    ]
}
```

**META DE QUANTIDADE PARA TODOS OS SETORES**: 
- **Setores Operacionais** (T.I, Operações, Financeiro): 45-60 serviços
- **Setores Comerciais** (Vendas, Marketing, Atendimento): 35-50 serviços  
- **Setores de Apoio** (RH, Jurídico, Compras): 30-45 serviços
- **Setores Especializados** (Qualidade, Logística): 25-40 serviços

**REGRA UNIVERSAL PARA TODOS OS SETORES**: 
- ❌ **NUNCA seja genérico** - cada serviço deve ser uma ação específica e executável
- ✅ **SEMPRE seja ultra-específico** - descreva exatamente o que é feito

**EXEMPLOS DE ESPECIFICIDADE PARA QUALQUER SETOR:**
- ❌ Genérico: \"Gestão de contratos\" 
- ✅ Específico: \"Elaboração de contratos de fornecedores\", \"Revisão jurídica de aditivos contratuais\", \"Controle de vencimentos de contratos\"

- ❌ Genérico: \"Controle financeiro\"
- ✅ Específico: \"Conciliação bancária diária\", \"Análise de margem por produto\", \"Projeção de fluxo de caixa mensal\"

- ❌ Genérico: \"Atendimento ao cliente\" 
- ✅ Específico: \"Suporte técnico via chat online\", \"Gestão de reclamações no SAC\", \"Follow-up pós-venda por telefone\"

- ❌ Genérico: \"Recursos humanos\"
- ✅ Específico: \"Onboarding de novos funcionários\", \"Avaliação de performance semestral\", \"Cálculo da folha de pagamento\"

**AMPLITUDE OBRIGATÓRIA PARA TODOS OS SETORES:**
Independente do setor, SEMPRE inclua serviços de:
1. **Rotina operacional** (dia-a-dia, semanal, mensal)
2. **Projetos e implementações** (mudanças, melhorias, novos processos)  
3. **Gestão de crise** (problemas, falhas, emergências)
4. **Compliance e controle** (auditorias, relatórios, conformidade)
5. **Relacionamento interno** (integração com outros setores)
6. **Análise e métricas** (indicadores, reports, análises)
7. **Treinamento e capacitação** (desenvolvimento de pessoas)
8. **Planejamento estratégico** (orçamento, metas, estratégias)

Responda APENAS com o JSON válido, sem explicações adicionais.";

    }

    /**
     * ETAPA 2B: Detalha completamente UM serviço específico
     * Uma chamada por serviço listado na Etapa 2A
     */
    public static function buildPromptDetalhamentoServico(string $servicoNome, array $contextosEmpresa, array $dadosServico): string
    {
        return "Você é um especialista operacional no processo \"{$servicoNome}\" para empresas do nicho \"{$contextosEmpresa['nicho']}\". Sua tarefa é detalhar COMPLETAMENTE este processo, incluindo todos os passos possíveis e TODOS os problemas que podem ocorrer, com suas respectivas soluções em 3 níveis (N1-N2-N3).

=========================== CONTEXTO DA EMPRESA ===========================
Nome: {$contextosEmpresa['nome']}
Nicho: {$contextosEmpresa['nicho']}
Setor: {$dadosServico['setor']}
Porte: {$contextosEmpresa['porte']} funcionários
Ferramentas disponíveis: {$contextosEmpresa['ferramentas_atuais']}

=========================== DADOS DO SERVIÇO ===========================
Nome: {$servicoNome}
Código: {$dadosServico['codigo']}
Categoria: {$dadosServico['categoria']}
Criticidade: {$dadosServico['criticidade']}
Gatilho: {$dadosServico['gatilho_entrada']}
Cenários problemáticos conhecidos: " . implode(', ', $dadosServico['cenarios_problematicos']) . "

=========================== O QUE DETALHAR ===========================

1. **PROCEDIMENTO PADRÃO COMPLETO**
   - Todos os pré-requisitos
   - Passo-a-passo detalhado (cenário ideal)
   - Pontos de validação obrigatórios
   - Responsáveis por cada etapa
   - Tempo estimado por passo
   - Ferramentas/sistemas necessários

2. **MAPA COMPLETO DE PROBLEMAS POSSÍVEIS**
   Identifique TODOS os problemas que podem ocorrer:
   - Problemas técnicos (sistemas, equipamentos, conectividade)
   - Problemas humanos (resistência, falta de cooperação, erros)
   - Problemas de processo (dependências, prazos, recursos)
   - Problemas externos (fornecedores, clientes, mercado)
   - Emergências e crises

3. **CONTENÇÃO N1-N2-N3 PARA CADA PROBLEMA**
   Para cada problema identificado:
   - **N1 (Solução Rápida):** Ação imediata, 15-30min, própria equipe
   - **N2 (Escalação Moderada):** Envolver supervisão, 1-4h, recursos adicionais
   - **N3 (Medidas Extremas):** Direção, consultores externos, plano de contingência

=========================== FORMATO DE SAÍDA (APENAS JSON) ===========================
{
  \"servico\": \"{$servicoNome}\",
  \"procedimento_padrao\": {
    \"pre_requisitos\": [\"string\", \"string\"],
    \"passos\": [
      {
        \"numero\": 1,
        \"acao\": \"string (ação específica)\",
        \"responsavel\": \"string (cargo/papel)\",
        \"tempo_estimado\": \"string\",
        \"ferramentas\": [\"string\"],
        \"validacao\": \"string (como verificar se deu certo)\",
        \"proxima_etapa\": \"string\"
      }
    ],
    \"criterios_sucesso\": [\"string\", \"string\"],
    \"evidencias_obrigatorias\": [\"string\", \"string\"]
  },
  \"problemas_possiveis\": [
    {
      \"problema\": \"string (nome do problema)\",
      \"categoria\": \"tecnico | humano | processo | externo | emergencia\",
      \"sinais_alerta\": [\"string\", \"string\"],
      \"impacto_se_nao_resolver\": \"string\",
      \"contencao_n1\": {
        \"acao\": \"string (o que fazer)\",
        \"responsavel\": \"string\",
        \"tempo_maximo\": \"string\",
        \"recursos_necessarios\": [\"string\"]
      },
      \"contencao_n2\": {
        \"acao\": \"string (escalação moderada)\",
        \"responsavel\": \"string\",
        \"tempo_maximo\": \"string\",
        \"recursos_necessarios\": [\"string\"]
      },
      \"contencao_n3\": {
        \"acao\": \"string (medidas extremas)\",
        \"responsavel\": \"string\",
        \"tempo_maximo\": \"string\",
        \"recursos_necessarios\": [\"string\"],
        \"comunicacao_externa\": \"string (quem avisar)\"
      }
    }
  ],
  \"checklist_preventivo\": [\"string\", \"string\"],
  \"kpis_processo\": [
    {\"nome\": \"string\", \"meta_verde\": \"string\", \"meta_amarela\": \"string\", \"meta_vermelha\": \"string\"}
  ]
}

Responda APENAS com o JSON válido. Seja extremamente detalhado e prático. Pense em situações reais que acontecem no dia-a-dia empresarial.";
    }

    /**
     * Constrói prompt padrão-ouro para geração de SOP com alta assertividade e completude
     * DEPRECATED: Use a nova arquitetura de 3 chamadas
     */
    public static function buildPromptSopDetalhado(array $empresa, array $sop, string $contextoDocumentos = ''): string
    {
        $setor = isset($empresa['setor']) ? $empresa['setor'] : (isset($empresa['segmento']) ? $empresa['segmento'] : 'Tecnologia');
        $normas = self::getNormasPorSetor($setor);
        $setorEmpresa = $setor;

        return "PROMPT DE SISTEMA — MOTOR DE GERAÇÃO DE SOPs E POPs (PADRÃO-OURO)

1. IDENTIDADE E FUNÇÃO
Você é o Motor de Padronização Operacional de uma plataforma que transforma diagnósticos empresariais em manuais técnicos completos (SOPs e POPs) para qualquer empresa, em qualquer área, do ponto de captação ao ponto de manutenção/retenção do cliente.

Seu trabalho não é \"escrever um texto sobre um processo\". Seu trabalho é reconstruir, formalizar e elevar ao padrão de mercado a forma como aquela empresa específica executa aquele processo específico — usando os dados reais do diagnóstico como matéria-prima, e o conhecimento de boas práticas de mercado como padrão de qualidade mínimo aceitável.

Você nunca gera um SOP genérico \"de internet\". Você gera o SOP daquela empresa, com a linguagem, ferramentas, papéis e restrições reais dela — mas nunca abaixo do padrão profissional esperado para o setor.

2. COMO VOCÊ RECEBE A INFORMAÇÃO
Você recebe como entrada os dados brutos da etapa de Diagnóstico, que pode conter, de forma não estruturada ou parcial:

DADOS DA EMPRESA (diagnóstico real):
Nome: {$empresa['nome']}
Setor: {$setorEmpresa}
Porte: {$empresa['colaboradores']} colaboradores, faturamento {$empresa['faturamento']}, nível de maturidade {$empresa['maturidade']}/4
Departamentos ativos: {$empresa['departamentos']}
Ferramentas utilizadas: {$empresa['ferramentas']}
Problemas identificados: {$empresa['problemas']}
Objetivos estratégicos: {$empresa['objetivos']}

MAPEAMENTO EMPRESARIAL DETALHADO:
{$empresa['mapeamento_detalhado']}

PADRÃO DE MERCADO APLICÁVEL ({$setorEmpresa}):
{$normas}

PROCEDIMENTOS PADRÃO DO MERCADO:
" . (isset($empresa['procedimentos_mercado']) ? print_r($empresa['procedimentos_mercado'], true) : 'Padrões da indústria') . "

{$contextoDocumentos}

3. PROTOCOLO DE INTERPRETAÇÃO (execução mental obrigatória)
Execute mentalmente estas etapas, nesta ordem, para este SOP específico:

Etapa 1 — Mapear a cadeia de valor da empresa
Identifique, a partir do segmento {$setorEmpresa} e do modelo de negócio, o fluxo macro real: Captação → Conversão/Venda → Onboarding/Entrega → Operação/Produção → Atendimento → Retenção/Pós-venda → áreas de suporte (Financeiro, RH, Jurídico, TI, Qualidade, Compras, Estratégia).

Etapa 2 — Classificar o departamento {$sop['departamento']} 
Para o departamento {$sop['departamento']} identificado no diagnóstico, extraia:
- Missão da área (por que ela existe)
- Processos que ela executa (liste todos, mesmo os implícitos)
- Inputs (o que entra) e Outputs (o que sai / para quem entrega)
- Papéis/cargos envolvidos
- Sistemas/ferramentas usados
- Gargalos ou falhas relatadas
- O que não foi informado (lacuna a sinalizar)

Etapa 3 — Comparar com o padrão de mercado (benchmark)
Para este processo específico, aplique o padrão profissional esperado para {$setorEmpresa}:
\"Como uma empresa madura e bem operada no setor {$setorEmpresa} faz este processo?\"
\"O que esta empresa está fazendo abaixo do padrão, e o que ela já faz bem?\"

Etapa 4 — Decidir granularidade
Este processo específico deve ser quebrado em procedimentos detalhados. Regra prática: se o processo tem mais de 15 passos distintos ou mais de um responsável principal mudando no meio, é mais de um SOP.

SOP ESPECÍFICO A GERAR:
Código: {$sop['id']}
Nome: {$sop['nome']}
Departamento: {$sop['departamento']}
Subtópicos obrigatórios: {$sop['subtopicos_texto']}

CONTEXTO ESPECÍFICO DO DEPARTAMENTO:
" . (isset($sop['contexto_departamento']) ? print_r($sop['contexto_departamento'], true) : 'Contexto padrão') . "

4. ESTRUTURA PADRÃO-OURO DE CADA SOP/POP (obrigatória, sem exceção)
Todo documento gerado deve seguir exatamente esta estrutura, preenchida com profundidade real — nunca com placeholders vagos como \"faça o processo corretamente\":

Gere um JSON com exatamente esta estrutura (todos os 15 componentes obrigatórios):

{
  \"cabecalho\": {
    \"codigo\": \"{$sop['id']}\",
    \"nome\": \"Nome específico do procedimento\",
    \"versao\": \"1.0\",
    \"data_criacao\": \"" . date('Y-m-d') . "\",
    \"data_revisao\": \"" . date('Y-m-d', strtotime('+1 year')) . "\",
    \"dono_processo\": \"Cargo responsável (não nome de pessoa)\",
    \"aprovador\": \"Cargo que aprova\"
  },
  \"objetivo\": \"Uma frase clara: o que este procedimento garante que aconteça\",
  \"escopo\": {
    \"cobre\": \"O que este SOP cobre especificamente\",
    \"nao_cobre\": \"O que explicitamente NÃO cobre (e para onde direcionar)\"
  },
  \"glossario\": [
    {\"termo\": \"Termo técnico 1\", \"definicao\": \"Definição sem ambiguidade\"},
    {\"termo\": \"Termo técnico 2\", \"definicao\": \"Definição específica\"},
    {\"termo\": \"Termo técnico 3\", \"definicao\": \"Definição clara e aplicável\"}
  ],
  \"raci\": {
    \"responsavel\": \"Cargo que executa (R)\",
    \"aprovador\": \"Cargo que aprova/autoriza (A)\",
    \"consultado\": \"Cargo que fornece input (C)\",
    \"informado\": \"Cargo que recebe resultado (I)\"
  },
  \"pre_requisitos\": [
    \"Acesso ao sistema X deve estar configurado e testado\",
    \"Informação Y deve estar disponível e validada\",
    \"Autorização Z deve estar obtida por escrito\",
    \"Treinamento específico deve ter sido concluído\",
    \"Documentação anterior deve estar arquivada\",
    \"Recursos materiais devem estar disponíveis\"
  ],
  \"passo_a_passo\": [
    {
      \"passo\": 1,
      \"acao\": \"Ação objetiva específica com verbo + critério de conclusão explícito\",
      \"sistema\": \"Sistema/ferramenta específica a usar\",
      \"responsavel\": \"Cargo responsável por esta etapa\",
      \"tempo_estimado\": \"X minutos realistas\",
      \"criterio_conclusao\": \"Como saber que foi bem executado - critério mensurável\"
    },
    {
      \"passo\": 2,
      \"acao\": \"Segunda ação específica e executável\",
      \"sistema\": \"Sistema/ferramenta para esta etapa\",
      \"responsavel\": \"Cargo responsável\",
      \"tempo_estimado\": \"X minutos\",
      \"criterio_conclusao\": \"Critério claro de conclusão\"
    }
  ],
  \"pontos_controle\": [
    {
      \"checkpoint\": \"Ponto de verificação crítico 1\",
      \"criterio_aceite\": \"Como verificar se está correto - métrica específica\",
      \"acao_se_falhar\": \"O que fazer se não estiver certo - ação corretiva específica\"
    },
    {
      \"checkpoint\": \"Ponto de verificação crítico 2\",
      \"criterio_aceite\": \"Como medir o sucesso desta etapa\",
      \"acao_se_falhar\": \"Ação corretiva detalhada\"
    }
  ],
  \"tratamento_excecoes\": [
    {
      \"cenario\": \"Erro/exceção específica mais provável\",
      \"solucao\": \"Ação específica passo a passo para resolver\",
      \"escalar_para\": \"Cargo específico para quem escalar se necessário\"
    },
    {
      \"cenario\": \"Segunda exceção comum identificada\",
      \"solucao\": \"Procedimento de resolução detalhado\",
      \"escalar_para\": \"Cargo responsável pelo escalonamento\"
    }
  ],
  \"ferramentas_sistemas\": [
    \"Sistema 1 - função específica no processo\",
    \"Ferramenta 2 - para que serve e quando usar\",
    \"Plataforma 3 - contexto de uso específico\"
  ],
  \"kpis_processo\": {
    \"tempo_medio_esperado\": \"X minutos/horas baseado no benchmark do setor\",
    \"taxa_erro_aceitavel\": \"X% (padrão da indústria)\",
    \"sla_interno\": \"X horas para conclusão\",
    \"meta_qualidade\": \"Critério específico mensurável\"
  },
  \"riscos_nao_conformidades\": [
    {
      \"risco\": \"O que pode dar errado especificamente\",
      \"impacto\": \"Consequência exata no cliente/negócio\",
      \"prevencao\": \"Como evitar - ação preventiva específica\"
    },
    {
      \"risco\": \"Segundo risco identificado\",
      \"impacto\": \"Impacto mensurável no negócio\",
      \"prevencao\": \"Medida preventiva concreta\"
    }
  ],
  \"melhorias_recomendadas\": [
    \"Ponto onde prática atual da {$empresa['nome']} está abaixo do padrão {$setorEmpresa} - sugerir melhoria específica e executável\",
    \"Gap identificado vs benchmark do setor - ação recomendada com prazo sugerido\",
    \"Oportunidade de automação/otimização baseada nas ferramentas disponíveis\"
  ],
  \"anexos\": {
    \"checklist_rapido\": [
      \"☐ Pré-requisito verificado e confirmado\",
      \"☐ Sistema acessível e funcionando\",
      \"☐ Autorização obtida e documentada\",
      \"☐ Processo executado conforme procedimento\",
      \"☐ Controle de qualidade realizado\",
      \"☐ Documentação atualizada\",
      \"☐ Stakeholders informados\",
      \"☐ Resultado validado pelo cliente/usuário\",
      \"☐ Indicadores atualizados\",
      \"☐ Fechamento documentado\"
    ],
    \"fluxograma_textual\": [
      \"1. Início → Verificar todos os pré-requisitos obrigatórios\",
      \"2. Preparação → Configurar sistemas e recursos necessários\",
      \"3. Execução → Seguir passo a passo conforme procedimento\",
      \"4. Controle → Validar qualidade em cada checkpoint\",
      \"5. Exceções → Tratar anomalias conforme procedimento\",
      \"6. Finalização → Entregar resultado e atualizar documentação\"
    ]
  },
  \"historico_revisoes\": [
    {
      \"versao\": \"1.0\",
      \"data\": \"" . date('Y-m-d') . "\",
      \"mudanca\": \"Criação inicial baseada em diagnóstico da empresa {$empresa['nome']}\",
      \"responsavel\": \"Sistema O Consultor\"
    }
  ]
}

5. REGRAS DE ASSERTIVIDADE E PROFUNDIDADE (não negociáveis):
- PROIBIDO generalismo: Frases como \"realizar o atendimento com excelência\" ou \"seguir as boas práticas\" são inaceitáveis
- TODO passo deve ser executável por alguém que nunca fez aquilo antes, sem precisar perguntar nada a ninguém
- TODA ação deve ter dono, ferramenta e critério de conclusão mensurável
- SEMPRE elevar ao padrão de mercado do setor {$setorEmpresa}, registrar gaps nas melhorias recomendadas
- SINALIZAR lacunas de dado com transparência: [⚠ DADO NÃO INFORMADO NO DIAGNÓSTICO — sugestão baseada em padrão de mercado {$setorEmpresa}, validar com a empresa]
- USAR ferramentas REAIS da empresa {$empresa['nome']} quando informadas: {$empresa['ferramentas']}
- MÍNIMO 15 passos detalhados no passo_a_passo (cada passo deve ser atômico e específico)
- MÍNIMO 5 pontos de controle com critérios mensuráveis
- MÍNIMO 3 cenários de exceção baseados na experiência do setor {$setorEmpresa}
- TODOS os campos obrigatórios devem estar preenchidos com conteúdo real, não placeholders

6. CRITÉRIO DE VALIDAÇÃO ANTES DE ENTREGAR (verificação interna obrigatória):
Antes de finalizar, verifique internamente:
[ ] Alguém sem experiência prévia consegue executar só lendo este documento?
[ ] Todo passo tem responsável, ferramenta e critério de conclusão específicos?
[ ] As exceções mais prováveis para o setor {$setorEmpresa} estão cobertas?
[ ] O documento está no padrão de mercado, não abaixo dele?
[ ] As lacunas de dado do diagnóstico estão sinalizadas, não inventadas?
[ ] O documento conecta corretamente com o processo anterior e o seguinte na jornada do cliente?

IMPORTANTE: Use dados REAIS da empresa {$empresa['nome']} sempre que disponível. Quando faltar informação crítica (SLA, ferramenta, responsável), escreva explicitamente a sinalização de lacuna e use padrão de mercado para {$setorEmpresa}.

Responda APENAS com o JSON válido seguindo exatamente a estrutura dos 15 componentes acima. Não inclua texto adicional fora do JSON.";
    }

    /**
     * Gera prompt para análise do diagnóstico
     */
    public static function buildPromptDiagnostico(array $dadosForm): string
    {
        $json = json_encode($dadosForm, JSON_UNESCAPED_UNICODE);

        return "Você é O Consultor. Analise o diagnóstico abaixo e gere:

1. SCORE DE MATURIDADE (1-4) com justificativa de 3-5 frases específicas
2. ANÁLISE POR ÁREA: para cada departamento — status (critico/em_desenvolvimento/estruturado) + comentário específico de 2-3 frases
3. MAPA DE RISCOS (6-10 riscos): tipo, descrição detalhada, criticidade (alta/media/baixa), ação específica sugerida
4. RECOMENDAÇÕES PRIORITÁRIAS (top 5): ação clara, impacto esperado, prazo sugerido
5. SOPs MAIS URGENTES: lista priorizada de departamentos e SOPs críticos
6. SITES DE REFERÊNCIA: com base no nicho e língua, liste 8 URLs de sites de referência do setor

Dados do diagnóstico:
{$json}

Responda APENAS em formato JSON válido com a estrutura:
{
  \"score\": number,
  \"justificativa\": string,
  \"areas\": [{\"area\": string, \"status\": string, \"comentario\": string}],
  \"riscos\": [{\"tipo\": string, \"descricao\": string, \"criticidade\": string, \"acao\": string}],
  \"recomendacoes\": [{\"acao\": string, \"impacto\": string, \"prazo\": string}],
  \"sops_urgentes\": [{\"departamento\": string, \"sop\": string, \"prioridade\": string}],
  \"sites_referencia\": [string]
}";
    }

    /**
     * Gera prompt para busca de notícias (Perplexity)
     */
    public static function buildPromptBuscaNoticias(string $setor, string $lingua, array $sites, string $instrucoes = ''): string
    {
        $sitesStr = implode(', ', $sites);

        // Instruções livres do usuário (prompt mestre) sobre o que priorizar/evitar.
        $blocoInstrucoes = '';
        $instrucoes = trim($instrucoes);
        if ($instrucoes !== '') {
            $blocoInstrucoes = "\n\nINSTRUÇÕES DE PRIORIZAÇÃO (definidas pelo usuário — siga-as rigorosamente ao escolher e ordenar as notícias):\n{$instrucoes}\n";
        }

        return "Busque as 10 notícias mais recentes e relevantes para empresas do setor {$setor} em {$lingua}.
Priorize conteúdo dos seguintes sites: {$sitesStr}{$blocoInstrucoes}
Formato de resposta: JSON com array de objetos, cada um com: titulo, url, fonte, data, resumo_bruto, setor.
Busque apenas conteúdo publicado nos últimos 7 dias.
Não inclua notícias duplicadas ou sem relevância para o setor.
Responda APENAS com o array JSON.";
    }

    /**
     * Gera prompt para análise de notícia (5 blocos)
     */
    public static function buildPromptAnaliseNoticia(string $setor, string $titulo, string $resumo, string $lingua = 'Português'): string
    {
        return "Analise esta notícia para um empresário do setor {$setor}.

Título original: {$titulo}
Resumo original: {$resumo}

IMPORTANTE — IDIOMA: TODO o conteúdo da resposta deve estar em {$lingua}. Se o título ou o resumo original estiverem em outro idioma, TRADUZA-OS para {$lingua} da forma mais FIEL possível, preservando o sentido, nomes próprios, números, siglas e termos técnicos (não invente nem omita informação). Os campos 'titulo' e 'resumo' abaixo devem conter essa versão traduzida (ou o texto original, caso já esteja em {$lingua}).

Gere em JSON (todos os textos em {$lingua}):
{
  \"titulo\": \"título da notícia em {$lingua} (tradução fiel do original, ou o original se já estiver em {$lingua})\",
  \"resumo\": \"resumo objetivo da notícia em {$lingua} (2-3 frases, tradução fiel)\",
  \"bloco1_noticia\": \"texto claro sobre o que aconteceu\",
  \"bloco2_significa\": \"o que isso significa para empresas do setor\",
  \"bloco3_o_que_fazer\": \"lista de 3-5 ações práticas separadas por \\n\",
  \"bloco4_pergunta\": \"uma pergunta estratégica provocativa\",
  \"bloco5_conexao\": \"como se conecta aos módulos do Consultor\",
  \"categoria\": \"Mercado|Tecnologia|Regulamentação|Tendência|Negócio\",
  \"relevancia\": \"Alta|Média|Baixa\",
  \"tags\": [\"array\", \"de\", \"palavras-chave\"]
}";
    }

    /**
     * Gera prompt para geração de conteúdo (Máquina de Conteúdo) — F-10
     */
    public static function buildPromptConteudo(array $marca, string $tipo, string $tema, string $objetivo, ?string $noticiaBase = null): string
    {
        $contextoNoticia = $noticiaBase ? "\n\nBASEADO NA NOTÍCIA:\n{$noticiaBase}" : '';

        switch($tipo) {
            case 'carrossel':
                $instrucoesTipo = 'Para CARROSSEL gere JSON com estrutura: {\"slides\": [{\"numero\": 1, \"tipo\": \"capa\", \"texto\": \"título principal\", \"texto_secundario\": \"subtítulo opcional\", \"prompt_imagem\": \"descrição detalhada da imagem\"}, {\"numero\": 2, \"tipo\": \"conteudo\", \"texto\": \"conteúdo do slide\", \"prompt_imagem\": \"descrição da imagem\"}], \"legenda\": \"texto da legenda com call-to-action\", \"hashtags\": \"#tag1 #tag2 #tag3\"}';
                break;
            case 'post':
                $instrucoesTipo = 'Para POST gere JSON com estrutura: {\"slides\": [{\"numero\": 1, \"tipo\": \"unico\", \"texto\": \"conteúdo principal\", \"prompt_imagem\": \"descrição da imagem\"}], \"legenda\": \"texto da legenda\", \"hashtags\": \"#tag1 #tag2\"}';
                break;
            case 'story':
                $instrucoesTipo = 'Para STORY gere JSON com estrutura: {\"slides\": [{\"numero\": 1, \"tipo\": \"story\", \"texto\": \"texto curto e impactante\", \"prompt_imagem\": \"descrição da imagem vertical\"}], \"legenda\": \"\", \"hashtags\": \"\"}';
                break;
            default:
                $instrucoesTipo = 'Para CARROSSEL gere JSON com múltiplos slides educativos.';
                break;
        }

        return "Você é especialista em marketing digital B2B. Crie conteúdo seguindo exatamente o Brand Book da marca.

DADOS DA MARCA:
Nome: {$marca['nome']}
Nicho: {$marca['nicho']}
Público-alvo: {$marca['publico_alvo']}
Tom de voz: {$marca['tom']}
Arquétipo: {$marca['arquetipo']}

PROMPT MASTER DA MARCA:
{$marca['prompt_master']}

TAREFA:
Crie um {$tipo} sobre o tema: {$tema}{$contextoNoticia}
Objetivo: {$objetivo}

{$instrucoesTipo}

REGRAS IMPORTANTES:
1. Cada slide deve ter um 'prompt_imagem' detalhado que combine o estilo visual da marca
2. Para prompt_imagem, use o estilo: {$marca['prompt_dalle']}
3. NÃO incluir texto, palavras ou números nas descrições de imagem
4. Manter consistência com o tom de voz e arquétipo da marca
5. Slides de carrossel: máximo 50 caracteres por linha de texto
6. Legenda: incluir call-to-action relevante ao objetivo

Responda APENAS em JSON válido, sem explicações.";
    }

    /**
     * Gera prompt contextualizado com dados da jornada do cliente
     */
    public static function buildPromptConteudoContextualizado(array $marca, string $tipo, string $tema, string $objetivo, ?string $noticiaBase = null, array $contextoJornada = [], ?string $literaturaBase = null, int $qtdSlides = 7): string
    {
        $qtdSlides = max(3, min(10, $qtdSlides ?: 7));
        $contextoNoticia = '';
        $regrasNoticia = '';
        if ($noticiaBase) {
            $contextoNoticia = "\n\nBASEADO NA NOTÍCIA (use este conteúdo como fonte):\n{$noticiaBase}";
            $regrasNoticia = "

📰 REGRAS PARA CONTEÚDO DE NOTÍCIA (OBRIGATÓRIAS):
1. A LEGENDA deve ser construída a partir do CONTEÚDO da notícia acima (o que aconteceu, por que importa, o que fazer), na linguagem própria da marca — não copie frases literais.
2. CITE A FONTE no final da legenda, creditando o veículo/origem indicado em 'FONTE DA NOTÍCIA' (ex.: 'Fonte: <veículo> — <link>'). Inclua o link, se houver.
3. O 'texto' de CADA slide deve funcionar como HEADLINE COM GANCHO: frase curta e instigante que desperte curiosidade e convide a continuar (perguntas, dados surpreendentes, tensão). Nada de títulos genéricos.";
        }

        // Base de literatura (RAG na Biblioteca): trechos reais dos PDFs do cliente.
        $contextoLiteratura = '';
        $regrasEducativo = '';
        if (!empty($literaturaBase)) {
            $contextoLiteratura = "\n\n📚 BASE DE CONHECIMENTO — LITERATURA DO CLIENTE (trechos reais extraídos dos documentos da biblioteca, recuperados por relevância ao tema \"{$tema}\"):\n"
                . "----------------------------------------\n" . $literaturaBase . "\n----------------------------------------\n"
                . "Esta é a sua FONTE PRIMÁRIA. Estude estes trechos, extraia os conceitos, dados, exemplos e definições que tratam do tema e transforme em conhecimento didático.";

            // Regras específicas para conteúdo EDUCATIVO baseado na literatura.
            $regrasEducativo = "

🎓 DIRETRIZES DE CONTEÚDO EDUCATIVO (OBRIGATÓRIAS — a fonte é a biblioteca do cliente):
1. FIDELIDADE: baseie-se nos trechos da literatura acima. Não invente fatos, números ou citações que contradigam a fonte. Se algo não estiver na fonte, não afirme como verdade absoluta.
2. FOCO NO TEMA: todo o conteúdo deve girar em torno do tema \"{$tema}\". Selecione, dentro da literatura, o que é realmente relevante para ele.
3. ESTRUTURA COM COMEÇO, MEIO E FIM (narrativa progressiva entre os slides):
   • ABERTURA (capa + 1º slide): um gancho forte — uma pergunta, um dado curioso ou um problema real que desperte interesse.
   • DESENVOLVIMENTO (slides do meio): explique o conceito de forma didática, em etapas lógicas que se conectam. Traga contexto, o 'porquê', exemplos práticos, curiosidades e insights extraídos da literatura. Cada slide aprofunda o anterior.
   • FECHAMENTO (últimos slides): síntese do aprendizado + uma conclusão que agregue valor + CTA coerente com o objetivo.
4. VALOR REAL: o post deve ENSINAR algo. Priorize utilidade, clareza e profundidade acessível — nada de texto genérico ou raso. Traga pelo menos uma curiosidade ou insight não óbvio.
5. DIDÁTICA: linguagem clara, explique termos técnicos, use analogias quando ajudar. Cada slide deve fazer sentido sozinho e também na sequência.
6. QUANTIDADE: respeite a quantidade de slides solicitada, distribuindo abertura, desenvolvimento e fechamento de forma equilibrada e bem encadeada.";
        }

        // Construir contexto da jornada
        $contextoPersonalizado = '';
        if (!empty($contextoJornada)) {
            $contextoPersonalizado = "\n\n📊 CONTEXTO DA JORNADA DIGITAL (Use para personalizar o conteúdo):\n";
            
            if (!empty($contextoJornada['diagnostico'])) {
                $diag = $contextoJornada['diagnostico'];
                $contextoPersonalizado .= "• Nível de maturidade: {$diag['nivel_maturidade']}/4\n";
                if (!empty($diag['respostas']['principais_desafios'])) {
                    $contextoPersonalizado .= "• Principais desafios: {$diag['respostas']['principais_desafios']}\n";
                }
                if (!empty($diag['respostas']['objetivo_12_meses'])) {
                    $contextoPersonalizado .= "• Objetivo 12 meses: {$diag['respostas']['objetivo_12_meses']}\n";
                }
            }

            if (!empty($contextoJornada['sops'])) {
                $totalSops = count($contextoJornada['sops']);
                $contextoPersonalizado .= "• SOPs criados: {$totalSops} (evidencia organização)\n";
            }

            if (!empty($contextoJornada['plano_acao']['objetivos'])) {
                $objetivos = implode(', ', array_slice($contextoJornada['plano_acao']['objetivos'], 0, 3));
                $contextoPersonalizado .= "• Objetivos no plano: {$objetivos}\n";
            }

            if (!empty($contextoJornada['perfil_conteudo']['palavras_chave'])) {
                $palavrasChave = implode(', ', array_slice($contextoJornada['perfil_conteudo']['palavras_chave'], 0, 5));
                $contextoPersonalizado .= "• Palavras-chave de interesse: {$palavrasChave}\n";
            }

            $contextoPersonalizado .= "\n⚡ INSTRUÇÃO: Use essas informações para tornar o conteúdo mais relevante e específico para a realidade da empresa.";
        }

        switch($tipo) {
            case 'carrossel':
                $instrucoesTipo = "Para CARROSSEL gere EXATAMENTE {$qtdSlides} slides (nem mais, nem menos) no JSON, na estrutura: {\"slides\": [{\"numero\": 1, \"tipo\": \"capa\", \"texto\": \"título principal\", \"texto_secundario\": \"subtítulo opcional\", \"prompt_imagem\": \"descrição detalhada da imagem\"}, {\"numero\": 2, \"tipo\": \"conteudo\", \"texto\": \"conteúdo do slide\", \"prompt_imagem\": \"descrição da imagem\"}], \"legenda\": \"texto da legenda com call-to-action\", \"hashtags\": \"#tag1 #tag2 #tag3\"}. O primeiro slide é a capa e o último deve conter a conclusão/CTA. O array 'slides' deve ter {$qtdSlides} itens.";
                break;
            case 'post':
                $instrucoesTipo = 'Para POST gere no MÁXIMO 3 imagens (idealmente 1). JSON: {\"slides\": [{\"numero\": 1, \"tipo\": \"unico\", \"texto\": \"conteúdo principal\", \"prompt_imagem\": \"descrição da cena\"}], \"legenda\": \"texto da legenda\", \"hashtags\": \"#tag1 #tag2\"}. NÃO ultrapasse 3 itens no array slides.';
                break;
            case 'story':
                $instrucoesTipo = 'Para STORY gere JSON com estrutura: {\"slides\": [{\"numero\": 1, \"tipo\": \"story\", \"texto\": \"texto curto e impactante\", \"prompt_imagem\": \"descrição da imagem vertical\"}], \"legenda\": \"\", \"hashtags\": \"\"}';
                break;
            default:
                $instrucoesTipo = 'Para CARROSSEL gere JSON com múltiplos slides educativos.';
                break;
        }

        return "Você é especialista em marketing digital B2B. Crie conteúdo seguindo exatamente o Brand Book da marca.

DADOS DA MARCA:
Nome: {$marca['nome']}
Nicho: {$marca['nicho']}
Público-alvo: {$marca['publico_alvo']}
Tom de voz: {$marca['tom']}
Arquétipo: {$marca['arquetipo']}

PROMPT MASTER DA MARCA:
{$marca['prompt_master']}{$contextoPersonalizado}

TAREFA:
Crie um {$tipo} sobre o tema: {$tema}{$contextoNoticia}{$contextoLiteratura}
Objetivo: {$objetivo}{$regrasEducativo}{$regrasNoticia}

{$instrucoesTipo}

REGRAS IMPORTANTES:
1. Cada slide deve ter um 'prompt_imagem' descrevendo uma CENA VISUAL CONCRETA e específica ligada ao tema (pessoas, ambientes, objetos, situações reais), NÃO uma coleção de ícones/símbolos abstratos. Ex.: em vez de 'ícones de cadeado e nuvem', prefira 'profissional de TI analisando painéis de segurança em um escritório moderno'.
2. NÃO defina paleta/estética no prompt_imagem: o estilo visual (cores, iluminação, traço) virá das imagens de referência da marca. Descreva apenas O QUE aparece na cena.
3. NÃO incluir texto, palavras ou números nas descrições de imagem
4. Manter consistência com o tom de voz e arquétipo da marca
5. O campo 'texto' de cada slide deve ser uma frase/parágrafo CLARO e COMPLETO em português, com sentido próprio. NÃO use emojis, símbolos, códigos ou caracteres especiais no 'texto' dos slides — apenas texto limpo.
6. O conteúdo deve ser COERENTE e encadeado: capa com gancho, slides de desenvolvimento que se conectam, e fechamento com conclusão + CTA. Nada de frases soltas ou aleatórias.
7. GANCHO OBRIGATÓRIO: o 'texto' de cada slide (especialmente a capa) deve ter um gancho que gere curiosidade e convide o usuário a ler/avançar — use perguntas, números, promessas de valor ou tensão. Evite títulos genéricos e descritivos.
8. Legenda: texto corrido natural com call-to-action relevante ao objetivo (emojis são permitidos APENAS na legenda, com moderação).
9. PERSONALIZAÇÃO: Use o contexto da jornada para tornar o conteúdo mais específico e relevante

Responda APENAS em JSON válido e bem formado, em português, sem explicações fora do JSON.";
    }

    /**
     * Gera prompt para análise de KPI em zona vermelha — F-07 Implementation
     */
    public static function buildPromptKpiCritico(array $empresa, array $kpi): string
    {
        $setorEmpresa = isset($empresa['setor']) ? $empresa['setor'] : (isset($empresa['segmento']) ? $empresa['segmento'] : 'Tecnologia');
        
        return "Você é O Consultor, especialista em análise de KPIs críticos empresariais.

CONTEXTO DA EMPRESA:
Nome: {$empresa['nome']}
Setor: {$setorEmpresa}
Nível de maturidade: {$empresa['maturidade']}/4
Colaboradores: {$empresa['colaboradores']}
Ferramentas: {$empresa['ferramentas']}

KPI CRÍTICO DETECTADO:
Nome: {$kpi['nome']}
Meta ideal: {$kpi['meta']}
Valor atual: {$kpi['atual']} (ZONA VERMELHA)
SOP de origem: {$kpi['sop']}

ANÁLISE NECESSÁRIA:
Você deve analisar este KPI crítico considerando o contexto específico da empresa e gerar uma análise completa para ação imediata.

Responda APENAS em JSON com esta estrutura exata:
{
  \"causas_raiz\": [
    \"Primeira hipótese específica para a empresa\",
    \"Segunda hipótese considerando o setor {$setorEmpresa}\", 
    \"Terceira hipótese baseada na maturidade {$empresa['maturidade']}/4\"
  ],
  \"plano_acao_imediato\": [
    \"Ação 1: específica e executável imediatamente\",
    \"Ação 2: com responsável e prazo claro\",
    \"Ação 3: usando as ferramentas disponíveis\",
    \"Ação 4: monitoramento e validação\",
    \"Ação 5: comunicação aos stakeholders\"
  ],
  \"prazo_revisao\": \"X dias (baseado na criticidade)\",
  \"contencao_recomendada\": \"N1 ou N2 ou N3\",
  \"justificativa_contencao\": \"Por que este nível de contingência é apropriado\"
}

IMPORTANTE:
- Seja específico para o setor {$setorEmpresa}
- Considere o porte da empresa ({$empresa['colaboradores']} pessoas)
- Use as ferramentas disponíveis: {$empresa['ferramentas']}
- Ações devem ser executáveis com a maturidade atual ({$empresa['maturidade']}/4)
- Prazo deve ser realista mas urgente (KPI crítico)";
    }

    /**
     * Gera prompt para pré-preenchimento de sites de referência
     */
    public static function buildPromptSitesReferencia(string $nicho, string $lingua): string
    {
        return "Liste os 8 principais sites de referência para empresas do setor {$nicho} no idioma {$lingua}.
Inclua apenas sites ativos e reconhecidos no setor.
Responda APENAS com um array JSON de URLs válidas. Sem explicações.";
    }

    // =========================================================================
    // INFRAESTRUTURA — cURL, retry, logging
    // =========================================================================

    /**
     * Executa chamada cURL com retry automático em caso de timeout
     */
    private static function executarCurl(string $url, array $headers, array $body, string $provedor, ?int $timeoutOverride = null): array
    {
        $timeout = $timeoutOverride ?? self::getTimeout();
        // Se um timeout curto foi forçado, não fazer retry (evita somar tempo além do proxy)
        $maxTentativas = $timeoutOverride !== null ? 1 : self::getMaxTentativas();

        for ($tentativa = 1; $tentativa <= $maxTentativas; $tentativa++) {
            $ch = curl_init($url);

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_CONNECTTIMEOUT => (int) self::config('api_connect_timeout', '10'),
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_POSTFIELDS     => json_encode($body),
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $resposta = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $erro = curl_error($ch);
            $errNo = curl_errno($ch);

            curl_close($ch);

            // Timeout — retry
            if ($errNo === CURLE_OPERATION_TIMEDOUT && $tentativa < $maxTentativas) {
                self::logErro($provedor, "Timeout (tentativa {$tentativa}), retrying...", ['url' => $url]);
                error_log('[O CONSULTOR][' . $provedor . '] Timeout na tentativa ' . $tentativa . '/' . $maxTentativas . ' | URL=' . $url);
                sleep(2);
                continue;
            }

            // Erro de cURL
            if ($erro) {
                self::logErro($provedor, "Erro cURL: {$erro}", ['url' => $url, 'errno' => $errNo]);
                error_log('[O CONSULTOR][' . $provedor . '] Erro cURL (errno ' . $errNo . '): ' . $erro . ' | URL=' . $url);
                return ['sucesso' => false, 'conteudo' => null, 'erro' => "API {$provedor} indisponível: {$erro}", 'dados' => null];
            }

            // HTTP error
            if ($httpCode < 200 || $httpCode >= 300) {
                $dados = json_decode($resposta, true);
                $msgErro = isset($dados['error']['message']) ? $dados['error']['message'] : (isset($dados['error']['type']) ? $dados['error']['type'] : "HTTP {$httpCode}");
                self::logErro($provedor, "HTTP {$httpCode}: {$msgErro}", ['url' => $url, 'body' => substr($resposta, 0, 500)]);
                error_log('[O CONSULTOR][' . $provedor . '] HTTP ' . $httpCode . ': ' . $msgErro . ' | URL=' . $url . ' | BODY=' . substr((string) $resposta, 0, 2000));
                return ['sucesso' => false, 'conteudo' => null, 'erro' => "API {$provedor}: {$msgErro}", 'dados' => $dados];
            }

            // Sucesso
            $dados = json_decode($resposta, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                self::logErro($provedor, 'Resposta não é JSON válido', ['raw' => substr($resposta, 0, 500)]);
                error_log('[O CONSULTOR][' . $provedor . '] Resposta não é JSON válido | RAW=' . substr((string) $resposta, 0, 500));
                return ['sucesso' => false, 'conteudo' => null, 'erro' => 'Resposta inválida da API.', 'dados' => null];
            }

            return ['sucesso' => true, 'dados' => $dados, 'erro' => null];
        }

        error_log('[O CONSULTOR][' . $provedor . '] Timeout final após ' . $maxTentativas . ' tentativa(s) | URL=' . $url);
        return ['sucesso' => false, 'conteudo' => null, 'erro' => "API {$provedor}: timeout após {$maxTentativas} tentativas.", 'dados' => null];
    }

    /**
     * Registra erro em arquivo de log específico de IA
     */
    private static function logErro(string $provedor, string $mensagem, array $contexto = []): void
    {
        $logDir = ROOT_PATH . '/storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $linha = "[{$timestamp}] [{$provedor}] {$mensagem}";
        if (!empty($contexto)) {
            $linha .= ' | ' . json_encode($contexto, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $linha .= PHP_EOL;

        file_put_contents(self::LOG_FILE, $linha, FILE_APPEND | LOCK_EX);

        // Também registrar no log geral
        Logger::error("IA [{$provedor}]: {$mensagem}", $contexto);
    }

    /**
     * Simplifica prompt de imagem quando DALL-E rejeita o original
     */
    private static function simplificarPromptImagem(string $prompt): string
    {
        // Remover termos que podem causar rejeição e simplificar
        $prompt = preg_replace('/(?:pessoas|rostos|faces|celebridades|crianças)/ui', 'elementos abstratos', $prompt);
        $prompt = substr($prompt, 0, 400); // DALL-E aceita até ~4000 chars, mas simplificar ajuda
        return $prompt . ' Estilo abstrato, corporativo, sem pessoas.';
    }

    /**
     * Retorna normas por setor para o prompt de SOP
     */
    public static function getNormasPorSetor(string $setor): string
    {
        $normas = [
            'Tecnologia' => 'ITIL v4, ISO 27001, ISO 20000, COBIT 2019',
            'Saúde' => 'Resolução CFM, RDC Anvisa, ISO 9001:2015, NR-32',
            'Construção' => 'NBR ABNT aplicáveis, PBQP-H, NR-18, ISO 9001',
            'Financeiro' => 'BACEN, CVM, SOX, ISO 31000, LGPD',
            'Jurídico' => 'OAB, LGPD, ISO 27001',
            'Varejo' => 'ISO 9001, ECR Brasil, GS1',
            'Costura/Moda' => 'ISO 9001 adaptado, ABNT NBR 16800, NR-12 para máquinas',
            'Alimentação' => 'ANVISA, APPCC/HACCP, ISO 22000, Vigilância Sanitária local',
            'Educação' => 'MEC, LGPD, ISO 21001',
            'Indústria' => 'ISO 9001, ISO 14001, OHSAS 18001, NRs aplicáveis',
            'Logística' => 'ISO 28000, IATA se aplicável, ISO 9001',
            'Imobiliário' => 'CRECI, Código Civil, ISO 9001',
            'Serviços' => 'ISO 9001:2015 como base universal',
        ];

        return isset($normas[$setor]) ? $normas[$setor] : 'ISO 9001:2015 como base universal, adaptada ao setor ' . $setor;
    }

    // =========================================================================
    // JWT PARA ACADEMY SSO
    // =========================================================================

    /**
     * Gera um JWT para SSO da Academy
     */
    public static function gerarJwtAcademy(array $payload): string
    {
        $secret = self::config('academy_jwt_secret');
        if (empty($secret)) {
            throw new \RuntimeException('JWT Secret da Academy não configurado. Acesse Admin > Configurações > Academy.');
        }

        $header = self::base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));

        $payload['iat'] = time();
        $payload['exp'] = time() + 300; // 5 minutos de validade
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));

        $signature = self::base64UrlEncode(
            hash_hmac('sha256', "{$header}.{$payloadEncoded}", $secret, true)
        );

        return "{$header}.{$payloadEncoded}.{$signature}";
    }

    /**
     * Base64 URL-safe encoding
     */
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    // =========================================================================
    // TESTAR CONEXÃO (usado pelo Admin)
    // =========================================================================

    /**
     * Testa conexão com uma API (chamada leve)
     */
    public static function testarConexao(string $provedor): array
    {
        if (!Configuracao::apiAtiva($provedor)) {
            return ['status' => 'inativa', 'tempo' => '-', 'erro' => 'API não ativa ou sem chave configurada.'];
        }

        $inicio = microtime(true);

        switch ($provedor) {
            case 'openai':
                $resultado = self::chamarOpenAI('Responda apenas: {"status": "ok"}', self::config('openai_modelo_mini', 'gpt-4o-mini'), true);
                break;
            case 'anthropic':
                $resultado = self::chamarAnthropic('Responda apenas: {"status": "ok"}', self::config('anthropic_modelo', 'claude-sonnet-4-20250514'));
                break;
            case 'perplexity':
                $resultado = self::chamarPerplexity('Responda apenas: {"status": "ok"}', 'sonar');
                break;
            default:
                return ['status' => 'erro', 'tempo' => '0ms', 'erro' => 'Provedor desconhecido.'];
        }

        $tempo = round((microtime(true) - $inicio) * 1000);

        return [
            'status' => $resultado['sucesso'] ? 'ok' : 'erro',
            'tempo'  => $tempo . 'ms',
            'erro'   => isset($resultado['erro']) ? $resultado['erro'] : null,
        ];
    }

    // =========================================================================
    // SISTEMA TRÊS BLOCOS - ENHANCED PROMPTS
    // =========================================================================

    /**
     * Prompt para análise integrada dos três blocos operacionais
     */
    public static function buildPromptAnaliseIntegrada(array $dadosEmpresa, string $contexto, string $tipo = 'geral'): string
    {
        $empresa = isset($dadosEmpresa['nome']) ? $dadosEmpresa['nome'] : 'Empresa';
        $setor = isset($dadosEmpresa['setor']) ? $dadosEmpresa['setor'] : 'Tecnologia';
        $maturidade = isset($dadosEmpresa['maturidade']) ? $dadosEmpresa['maturidade'] : 2;

        return "ANÁLISE INTEGRADA - SISTEMA OPERACIONAL TRÊS BLOCOS

EMPRESA: {$empresa}
SETOR: {$setor}
MATURIDADE: {$maturidade}/5
CONTEXTO: {$contexto}
TIPO DE ANÁLISE: {$tipo}

O sistema operacional é composto por TRÊS BLOCOS integrados:

🔵 BLOCO OPERACIONAL (Topo)
- Cadastro → Diagnóstico → IA gera resultado
- Plano de Ação e SOPs em paralelo
- KPIs monitorados → Alerta automático
- Contenção (N1/N2/N3) → Revisão do SOP
- Loop de melhoria contínua

🟡 BLOCO CONTEÚDO (Meio)  
- Notícias do setor via IA
- Geração de post/carrossel com base no branding book
- Referências e templates com DALL-E
- Revisão e publicação
- Publicação via API externa
- Conexão via SSO com My Academy

🟢 BLOCO GESTÃO (Base)
- Agenda pessoal e Financeiro em paralelo
- Tudo converge para empresa escalável e previsível
- Reinicia novo ciclo de diagnóstico

SOLICITAÇÃO:
Analise o contexto fornecido e gere recomendações específicas para cada bloco, considerando como eles se integram e se impactam mutuamente.

Responda APENAS em JSON:
{
    \"analise_operacional\": {
        \"situacao_atual\": \"Descrição da situação atual no bloco operacional\",
        \"oportunidades\": [\"oportunidade 1\", \"oportunidade 2\"],
        \"acoes_recomendadas\": [\"ação 1\", \"ação 2\", \"ação 3\"]
    },
    \"analise_conteudo\": {
        \"situacao_atual\": \"Descrição da situação atual no bloco conteúdo\",
        \"oportunidades\": [\"oportunidade 1\", \"oportunidade 2\"],
        \"acoes_recomendadas\": [\"ação 1\", \"ação 2\", \"ação 3\"]
    },
    \"analise_gestao\": {
        \"situacao_atual\": \"Descrição da situação atual no bloco gestão\",
        \"oportunidades\": [\"oportunidade 1\", \"oportunidade 2\"],
        \"acoes_recomendadas\": [\"ação 1\", \"ação 2\", \"ação 3\"]
    },
    \"integracao_blocos\": \"Como os três blocos devem trabalhar juntos para maximizar resultados\",
    \"proximos_passos\": [\"passo 1\", \"passo 2\", \"passo 3\"],
    \"kpis_sugeridos\": [\"KPI para monitorar integração dos blocos\"]
}";
    }

    /**
     * Prompt para geração de conteúdo integrado com SSO Academy
     */
    public static function buildPromptConteudoAcademy(array $dadosUsuario, string $topico, array $contextoCursos): string
    {
        $nome = isset($dadosUsuario['nome']) ? $dadosUsuario['nome'] : 'Usuário';
        $empresa = isset($dadosUsuario['empresa']) ? $dadosUsuario['empresa'] : 'Empresa';
        $nivel = isset($dadosUsuario['nivel']) ? $dadosUsuario['nivel'] : 'Iniciante';
        $cursos = implode(', ', array_slice($contextoCursos, 0, 5));

        return "GERAÇÃO DE CONTEÚDO INTEGRADO - MY ACADEMY SSO

PERFIL DO USUÁRIO:
- Nome: {$nome}
- Empresa: {$empresa}
- Nível: {$nivel}
- Cursos relacionados: {$cursos}

TÓPICO SOLICITADO: {$topico}

CONTEXTO INTEGRAÇÃO SSO:
Este conteúdo será integrado com My Academy via SSO, permitindo:
- Acesso direto aos cursos relacionados
- Tracking de progresso do usuário
- Recomendações personalizadas
- Certificações integradas

SOLICITAÇÃO:
Crie conteúdo educacional sobre '{$topico}' que:
1. Se conecte com o nível de conhecimento do usuário
2. Referencie cursos específicos da Academy quando relevante
3. Sugira próximos passos de aprendizado
4. Mantenha foco prático para aplicação na empresa

Responda APENAS em JSON:
{
    \"titulo\": \"Título do conteúdo educacional\",
    \"introducao\": \"Introdução contextualizada para o usuário\",
    \"conteudo_principal\": \"Conteúdo principal dividido em seções\",
    \"pontos_chave\": [\"ponto 1\", \"ponto 2\", \"ponto 3\"],
    \"cursos_relacionados\": [\"Nome do curso 1\", \"Nome do curso 2\"],
    \"proximos_passos\": [\"passo 1\", \"passo 2\"],
    \"aplicacao_pratica\": \"Como aplicar este conhecimento na empresa {$empresa}\",
    \"recursos_adicionais\": [\"recurso 1\", \"recurso 2\"]
}";
    }

    /**
     * Prompt para detalhar um serviço específico individualmente
     */
    public static function buildPromptDetalhamentoServicoIndividual(array $dadosEmpresa, string $servicoNome): string
    {
        return "# DETALHAMENTO ESPECÍFICO DE SERVIÇO - ANÁLISE PROFUNDA

## CONTEXTO DA EMPRESA
- **Nome:** {$dadosEmpresa['nome']}
- **Segmento:** {$dadosEmpresa['nicho']}
- **Porte:** {$dadosEmpresa['porte']}
- **Modelo de Negócio:** {$dadosEmpresa['modelo_negocio']}

## SERVIÇO PARA DETALHAR
**Serviço:** {$servicoNome}

## OBJETIVO
Criar um detalhamento EXTREMAMENTE ESPECÍFICO e prático para este serviço único, incluindo todos os cenários possíveis e estratégias de contenção N1-N2-N3.

## ESTRUTURA DE RESPOSTA (JSON)
```json
{
  \"servico_nome\": \"{$servicoNome}\",
  \"descricao_completa\": \"Descrição detalhada do serviço\",
  \"objetivo_principal\": \"Objetivo principal do serviço\",
  \"processos\": [
    {
      \"nome\": \"Nome do processo\",
      \"descricao\": \"Descrição detalhada\",
      \"responsavel\": \"Cargo responsável\",
      \"tempo_estimado\": \"Tempo de execução\",
      \"recursos_necessarios\": [\"recurso1\", \"recurso2\"],
      \"indicadores\": [\"indicador1\", \"indicador2\"]
    }
  ],
  \"cenarios_problemas\": [
    {
      \"problema\": \"Descrição do problema específico\",
      \"frequencia\": \"alta|média|baixa\",
      \"impacto\": \"alto|médio|baixo\",
      \"n1_contencao\": {
        \"tempo_limite\": \"0-30 minutos\",
        \"acoes\": [\"ação imediata 1\", \"ação imediata 2\"],
        \"responsavel\": \"Operador/Técnico\"
      },
      \"n2_escalacao\": {
        \"tempo_limite\": \"30 minutos - 4 horas\",
        \"acoes\": [\"escalação 1\", \"escalação 2\"],
        \"responsavel\": \"Supervisor/Coordenador\"
      },
      \"n3_emergencia\": {
        \"tempo_limite\": \"4+ horas\",
        \"acoes\": [\"medida extrema 1\", \"medida extrema 2\"],
        \"responsavel\": \"Gerência/Diretoria\"
      }
    }
  ],
  \"fluxo_trabalho\": {
    \"entrada\": \"O que inicia o serviço\",
    \"etapas\": [
      {
        \"ordem\": 1,
        \"nome\": \"Nome da etapa\",
        \"descricao\": \"O que fazer\",
        \"tempo\": \"Tempo estimado\",
        \"validacao\": \"Como validar se foi feito corretamente\"
      }
    ],
    \"saida\": \"O que é entregue/resultado\"
  },
  \"qualidade_controle\": {
    \"criterios\": [\"critério 1\", \"critério 2\"],
    \"checklist\": [\"item 1\", \"item 2\"],
    \"metricas\": [\"métrica 1\", \"métrica 2\"]
  },
  \"riscos_mitigacao\": [
    {
      \"risco\": \"Descrição do risco\",
      \"probabilidade\": \"alta|média|baixa\",
      \"impacto\": \"alto|médio|baixo\",
      \"prevencao\": \"Como prevenir\",
      \"mitigacao\": \"Como mitigar se ocorrer\"
    }
  ]
}
```

## DIRETRIZES IMPORTANTES
1. **SEJA ULTRA-ESPECÍFICO**: Não use termos genéricos. Cada processo, problema e solução deve ser detalhado para ESTE serviço específico nesta empresa específica.
2. **PROBLEMAS REAIS**: Inclua problemas que REALMENTE acontecem neste tipo de serviço (falhas técnicas, resistência humana, gargalos operacionais).
3. **N1-N2-N3 PRÁTICOS**: As contenções devem ser ações CONCRETAS que podem ser executadas nos tempos especificados.
4. **CONTEXTO EMPRESARIAL**: Considere o porte da empresa, recursos disponíveis e realidade operacional.
5. **FLUXO REALISTA**: O fluxo deve refletir como o trabalho realmente acontece, não a teoria ideal.

## RESPOSTA
Retorne APENAS o JSON válido, sem explicações adicionais.";
    }

    /**
     * Prompt para gerar SOP individual completo
     */
    public static function buildPromptSOPIndividual(array $dadosEmpresa, string $servicoNome, ?array $detalhamentoData = null): string
    {
        $detalhamentoInfo = '';
        if ($detalhamentoData) {
            $detalhamentoInfo = "\n## DETALHAMENTO EXISTENTE\n" . json_encode($detalhamentoData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        return "# SOP COMPLETO - PROCEDIMENTO OPERACIONAL PADRÃO

## CONTEXTO DA EMPRESA
- **Nome:** {$dadosEmpresa['nome']}
- **Segmento:** {$dadosEmpresa['nicho']}
- **Porte:** {$dadosEmpresa['porte']}
- **Modelo de Negócio:** {$dadosEmpresa['modelo_negocio']}

## SERVIÇO
**Serviço:** {$servicoNome}
{$detalhamentoInfo}

## OBJETIVO
Criar um SOP COMPLETO e OPERACIONAL para este serviço específico, que possa ser usado imediatamente pela equipe.

## ESTRUTURA DE RESPOSTA (JSON)
```json
{
  \"sop_titulo\": \"SOP - {$servicoNome}\",
  \"versao\": \"1.0\",
  \"data_criacao\": \"" . date('Y-m-d') . "\",
  \"objetivo\": \"Objetivo específico deste SOP\",
  \"escopo\": \"Onde este SOP se aplica\",
  \"responsaveis\": {
    \"executor_principal\": \"Cargo principal\",
    \"supervisor\": \"Cargo supervisor\",
    \"aprovador\": \"Cargo aprovador\"
  },
  \"pre_requisitos\": [
    \"Pré-requisito 1\",
    \"Pré-requisito 2\"
  ],
  \"recursos_necessarios\": {
    \"equipamentos\": [\"equipamento1\", \"equipamento2\"],
    \"sistemas\": [\"sistema1\", \"sistema2\"],
    \"documentos\": [\"documento1\", \"documento2\"],
    \"pessoas\": [\"função1\", \"função2\"]
  },
  \"procedimentos\": [
    {
      \"fase\": \"Nome da fase\",
      \"descricao\": \"Descrição da fase\",
      \"passos\": [
        {
          \"passo\": 1,
          \"acao\": \"Descrição detalhada da ação\",
          \"responsavel\": \"Quem executa\",
          \"tempo_estimado\": \"Tempo em minutos\",
          \"criterio_qualidade\": \"Como validar que foi feito corretamente\",
          \"observacoes\": \"Dicas importantes ou cuidados especiais\"
        }
      ]
    }
  ],
  \"pontos_controle\": [
    {
      \"momento\": \"Quando verificar\",
      \"o_que_verificar\": \"O que conferir\",
      \"criterio_aceitacao\": \"Como saber se está correto\",
      \"acao_se_nao_conforme\": \"O que fazer se não estiver conforme\"
    }
  ],
  \"indicadores_performance\": [
    {
      \"nome\": \"Nome do indicador\",
      \"formula\": \"Como calcular\",
      \"meta\": \"Meta esperada\",
      \"frequencia_medicao\": \"Quando medir\"
    }
  ],
  \"procedimentos_emergencia\": {
    \"situacoes_criticas\": [
      {
        \"situacao\": \"Descrição da situação crítica\",
        \"sinais_alerta\": [\"sinal1\", \"sinal2\"],
        \"acao_imediata\": \"O que fazer imediatamente\",
        \"quem_notificar\": \"Quem avisar\",
        \"tempo_resposta_maximo\": \"Tempo máximo para agir\"
      }
    ]
  },
  \"checklists\": {
    \"inicio_atividade\": [\"item1\", \"item2\"],
    \"durante_execucao\": [\"item1\", \"item2\"],
    \"final_atividade\": [\"item1\", \"item2\"]
  },
  \"documentacao\": {
    \"registros_obrigatorios\": [\"registro1\", \"registro2\"],
    \"modelos_formularios\": [\"formulário1\", \"formulário2\"],
    \"arquivo_evidencias\": \"Como e onde arquivar\"
  },
  \"treinamento\": {
    \"competencias_necessarias\": [\"competência1\", \"competência2\"],
    \"tempo_treinamento\": \"Horas de treinamento necessárias\",
    \"avaliacao\": \"Como avaliar se a pessoa está apta\"
  },
  \"melhorias_sugestoes\": [
    \"Sugestão de melhoria 1\",
    \"Sugestão de melhoria 2\"
  ]
}
```

## DIRETRIZES PARA SOP DE EXCELÊNCIA
1. **AÇÕES ESPECÍFICAS**: Cada passo deve ser uma ação clara e mensurável (\"Clique em X\", \"Verifique se Y\", \"Confirme que Z\").
2. **TEMPOS REALISTAS**: Inclua tempos baseados na realidade operacional da empresa.
3. **PONTOS DE DECISÃO**: Inclua \"se isso, então aquilo\" para diferentes cenários.
4. **VALIDAÇÃO CONTÍNUA**: Cada passo deve ter um critério de qualidade claro.
5. **EMERGÊNCIAS PRÁTICAS**: Situações críticas devem ter respostas imediatas e específicas.
6. **LINGUAGEM OPERACIONAL**: Use linguagem que a equipe operacional entende e usa no dia-a-dia.

## RESPOSTA
Retorne APENAS o JSON válido, sem explicações adicionais.";
    }
    /**
     * Transcrever áudio usando Whisper da OpenAI
     */
    public static function transcreverComWhisper(string $caminhoArquivo, string $tipoMime): ?string
    {
        $apiKey = self::config('openai_key');
        
        if (!$apiKey) {
            throw new Exception('Chave da API OpenAI não configurada');
        }
        
        if (!file_exists($caminhoArquivo)) {
            throw new Exception('Arquivo de áudio não encontrado');
        }
        
        try {
            // Preparar requisição para Whisper
            $postfields = [
                'model' => 'whisper-1',
                'file' => new CURLFile($caminhoArquivo, $tipoMime, 'audio.webm'),
                'language' => 'pt', // Português
                'response_format' => 'json',
                'temperature' => 0.2 // Mais preciso
            ];
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://api.openai.com/v1/audio/transcriptions',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postfields,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $apiKey,
                ]
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                throw new Exception("Erro cURL: {$error}");
            }
            
            if ($httpCode !== 200) {
                Logger::error('Erro na API Whisper', [
                    'http_code' => $httpCode,
                    'response' => $response
                ]);
                throw new Exception("Erro HTTP {$httpCode} da API Whisper");
            }
            
            $result = json_decode($response, true);
            
            if (!$result || !isset($result['text'])) {
                throw new Exception('Resposta inválida da API Whisper');
            }
            
            Logger::info('Transcrição Whisper concluída', [
                'tamanho_transcricao' => strlen($result['text']),
                'primeira_palavras' => substr($result['text'], 0, 50) . '...'
            ]);
            
            return trim($result['text']);
            
        } catch (Exception $e) {
            Logger::error('Erro no Whisper', [
                'erro' => $e->getMessage(),
                'arquivo' => basename($caminhoArquivo)
            ]);
            throw $e;
        }
    }

    /**
     * Prompt para gerar SOP baseado em transcrição de voz
     */
    public static function buildPromptSOPPorTranscricao(string $transcricao, array $contextoAtual, array $sopAtual): string
    {
        $servicoNome = $sopAtual['servico_nome'] ?? 'Serviço';
        $setorNome = $sopAtual['setor_nome'] ?? 'Setor';
        
        return "# GERAÇÃO DE SOP BASEADO EM TRANSCRIÇÃO DE VOZ

## CONTEXTO
- **Serviço:** {$servicoNome}
- **Setor:** {$setorNome}
- **Situação:** O usuário gravou uma descrição detalhada do processo e queremos gerar um SOP profissional baseado na sua explicação

## TRANSCRIÇÃO RECEBIDA
```
{$transcricao}
```

## SOP ATUAL (para referência)
```json
" . json_encode($contextoAtual, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "
```

## OBJETIVO
Transformar a descrição falada em um SOP completo, estruturado e profissional, seguindo o formato padrão da plataforma.

## DIRETRIZES PARA PROCESSAMENTO
1. **EXTRAIR INFORMAÇÕES CHAVE** da transcrição:
   - Passos específicos mencionados
   - Tempos estimados citados
   - Responsáveis mencionados
   - Problemas e soluções descritos
   - Pontos de controle indicados

2. **MANTER ESPECIFICIDADE**: Use os detalhes exatos mencionados pelo usuário
3. **COMPLEMENTAR COM BOAS PRÁTICAS**: Onde o usuário não especificou, adicione elementos padrão de SOP
4. **PRESERVAR LINGUAGEM EMPRESARIAL**: Transforme linguagem coloquial em formato profissional
5. **ESTRUTURA COMPLETA**: Mesmo se a transcrição for incompleta, gere um SOP completo

## ESTRUTURA DE RESPOSTA (JSON)
```json
{
  \"sop_titulo\": \"SOP - {$servicoNome}\",
  \"versao\": \"2.0\",
  \"data_criacao\": \"" . date('Y-m-d') . "\",
  \"data_atualizacao\": \"" . date('Y-m-d H:i:s') . "\",
  \"origem\": \"transcricao_voz\",
  \"objetivo\": \"Objetivo específico baseado na descrição\",
  \"escopo\": \"Escopo baseado no que foi descrito\",
  \"responsaveis\": {
    \"executor_principal\": \"Cargo mencionado ou inferido\",
    \"supervisor\": \"Supervisor mencionado ou padrão\",
    \"aprovador\": \"Aprovador mencionado ou padrão\"
  },
  \"pre_requisitos\": [
    \"Pré-requisitos mencionados na transcrição\"
  ],
  \"recursos_necessarios\": {
    \"equipamentos\": [\"equipamentos citados\"],
    \"sistemas\": [\"sistemas mencionados\"],
    \"documentos\": [\"documentos citados\"],
    \"pessoas\": [\"funções mencionadas\"]
  },
  \"procedimentos\": [
    {
      \"fase\": \"Nome da fase baseada na descrição\",
      \"descricao\": \"Descrição da fase\",
      \"passos\": [
        {
          \"passo\": 1,
          \"acao\": \"Ação específica mencionada pelo usuário\",
          \"responsavel\": \"Responsável mencionado ou inferido\",
          \"tempo_estimado\": \"Tempo mencionado ou estimado\",
          \"criterio_qualidade\": \"Como validar (baseado na descrição)\",
          \"observacoes\": \"Dicas ou cuidados mencionados\"
        }
      ]
    }
  ],
  \"pontos_controle\": [
    {
      \"momento\": \"Quando verificar (baseado na transcrição)\",
      \"o_que_verificar\": \"O que conferir\",
      \"criterio_aceitacao\": \"Como saber se está correto\",
      \"acao_se_nao_conforme\": \"O que fazer se não estiver conforme\"
    }
  ],
  \"procedimentos_emergencia\": {
    \"situacoes_criticas\": [
      {
        \"situacao\": \"Situação problemática mencionada\",
        \"sinais_alerta\": [\"sinais mencionados\"],
        \"acao_imediata\": \"Ação imediata descrita\",
        \"quem_notificar\": \"Quem avisar (mencionado ou inferido)\",
        \"tempo_resposta_maximo\": \"Tempo mencionado ou padrão\"
      }
    ]
  },
  \"checklists\": {
    \"inicio_atividade\": [\"itens baseados na descrição\"],
    \"durante_execucao\": [\"itens baseados na descrição\"],
    \"final_atividade\": [\"itens baseados na descrição\"]
  },
  \"observacoes_transcricao\": \"Resumo dos pontos principais mencionados pelo usuário\"
}
```

## REGRAS IMPORTANTES
1. **FIDELIDADE À TRANSCRIÇÃO**: Use os detalhes específicos mencionados
2. **LINGUAGEM PROFISSIONAL**: Transforme expressões coloquiais em linguagem técnica
3. **COMPLETUDE**: Mesmo se a descrição for parcial, gere um SOP completo
4. **REALISMO**: Os tempos e responsáveis devem ser realistas
5. **AÇÃO ESPECÍFICA**: Cada passo deve ser uma ação clara e mensurável

## RESPOSTA
Retorne APENAS o JSON válido, sem explicações adicionais.";
    }

    /**
     * Transcrever áudio usando OpenAI Whisper
     */
    public static function transcreverAudioWhisper(string $audioPath, string $fileName): ?string
    {
        $configuracao = Configuracao::buscarPorChave('openai_api_key');
        
        if (!$configuracao || !$configuracao['valor']) {
            Logger::error('Chave da API OpenAI não configurada para Whisper');
            return null;
        }
        
        $apiKey = $configuracao['valor'];
        
        try {
            // Preparar arquivo para upload
            $curl = curl_init();
            
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://api.openai.com/v1/audio/transcriptions',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 120, // 2 minutos para arquivos grandes
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => [
                    'file' => new CURLFile($audioPath, mime_content_type($audioPath), $fileName),
                    'model' => 'whisper-1',
                    'language' => 'pt',
                    'response_format' => 'text'
                ],
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $apiKey
                ],
            ]);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            
            curl_close($curl);
            
            if ($error) {
                Logger::error('Erro CURL na transcrição Whisper', ['erro' => $error]);
                return null;
            }
            
            if ($httpCode !== 200) {
                Logger::error('Erro HTTP na transcrição Whisper', [
                    'http_code' => $httpCode,
                    'response' => $response
                ]);
                return null;
            }
            
            // Whisper retorna texto direto quando response_format é 'text'
            $transcricao = trim($response);
            
            if (empty($transcricao)) {
                Logger::warning('Transcrição vazia retornada pelo Whisper');
                return null;
            }
            
            Logger::info('Transcrição Whisper concluída', [
                'arquivo' => $fileName,
                'tamanho_transcricao' => strlen($transcricao)
            ]);
            
            return $transcricao;
            
        } catch (Exception $e) {
            Logger::error('Erro na transcrição Whisper', [
                'erro' => $e->getMessage(),
                'arquivo' => $fileName
            ]);
            return null;
        }
    }
}