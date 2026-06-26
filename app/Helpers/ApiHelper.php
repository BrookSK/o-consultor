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
    public static function chamarOpenAI(string $prompt, ?string $model = null, bool $jsonMode = true): array
    {
        $apiKey = self::config('openai_key');
        $model = $model ?? self::config('openai_modelo', 'gpt-4o');
        $maxTokens = (int) self::config('openai_max_tokens', '8192');

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
            'OpenAI'
        );

        if (!$resultado['sucesso']) {
            return $resultado;
        }

        // Extrair conteúdo da resposta
        $conteudo = $resultado['dados']['choices'][0]['message']['content'] ?? null;

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
     * Chama a API da Anthropic (Claude)
     *
     * @param string $prompt Prompt completo
     * @param string $model  Modelo (claude-sonnet-4-20250514, claude-opus-4-20250514, etc.)
     * @return array ['sucesso' => bool, 'conteudo' => string|null, 'erro' => string|null]
     */
    public static function chamarAnthropic(string $prompt, ?string $model = null): array
    {
        $apiKey = self::config('anthropic_key');
        $model = $model ?? self::config('anthropic_modelo', 'claude-sonnet-4-20250514');

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

        $conteudo = $resultado['dados']['content'][0]['text'] ?? null;

        if ($conteudo === null) {
            self::logErro('Anthropic', 'Resposta sem conteúdo', $resultado['dados']);
            return ['sucesso' => false, 'conteudo' => null, 'erro' => 'Resposta da API sem conteúdo.'];
        }

        // Tentar decodificar como JSON
        $decoded = json_decode($conteudo, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return ['sucesso' => true, 'conteudo' => $decoded, 'erro' => null];
        }

        return ['sucesso' => true, 'conteudo' => $conteudo, 'erro' => null];
    }

    /**
     * Chama a API da Perplexity (busca em tempo real)
     *
     * @param string $prompt Prompt de busca
     * @param string $model  Modelo (sonar, sonar-pro)
     * @return array ['sucesso' => bool, 'conteudo' => string|null, 'erro' => string|null]
     */
    public static function chamarPerplexity(string $prompt, ?string $model = null): array
    {
        $apiKey = self::config('perplexity_key');
        $model = $model ?? self::config('perplexity_modelo', 'sonar-pro');

        if (empty($apiKey)) {
            return ['sucesso' => false, 'conteudo' => null, 'erro' => 'Chave Perplexity não configurada. Acesse Admin > Configurações > APIs.'];
        }

        $resultado = self::executarCurl(
            'https://api.perplexity.ai/chat/completions',
            [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            [
                'model'    => $model,
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ],
            'Perplexity'
        );

        if (!$resultado['sucesso']) {
            return $resultado;
        }

        $conteudo = $resultado['dados']['choices'][0]['message']['content'] ?? null;

        if ($conteudo === null) {
            self::logErro('Perplexity', 'Resposta sem conteúdo', $resultado['dados']);
            return ['sucesso' => false, 'conteudo' => null, 'erro' => 'Resposta da API sem conteúdo.'];
        }

        $decoded = json_decode($conteudo, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return ['sucesso' => true, 'conteudo' => $decoded, 'erro' => null];
        }

        return ['sucesso' => true, 'conteudo' => $conteudo, 'erro' => null];
    }

    /**
     * Gera imagem via DALL-E 3
     *
     * @param string $prompt Prompt descritivo da imagem
     * @param string $size   Tamanho (1024x1024, 1792x1024, 1024x1792)
     * @return array ['sucesso' => bool, 'url' => string|null, 'erro' => string|null]
     */
    public static function gerarImagem(string $prompt, string $size = '1024x1024'): array
    {
        $apiKey = self::config('openai_key');

        if (empty($apiKey)) {
            return ['sucesso' => false, 'url' => null, 'erro' => 'Chave OpenAI não configurada para DALL-E.'];
        }

        $resultado = self::executarCurl(
            'https://api.openai.com/v1/images/generations',
            [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            [
                'model'   => 'dall-e-3',
                'prompt'  => $prompt,
                'n'       => 1,
                'size'    => $size,
                'quality' => 'standard',
            ],
            'DALL-E'
        );

        if (!$resultado['sucesso']) {
            // Tentar com prompt simplificado se DALL-E rejeitou
            if (strpos($resultado['erro'] ?? '', 'content_policy') !== false) {
                $promptSimplificado = self::simplificarPromptImagem($prompt);
                $resultado = self::executarCurl(
                    'https://api.openai.com/v1/images/generations',
                    [
                        'Authorization: Bearer ' . $apiKey,
                        'Content-Type: application/json',
                    ],
                    ['model' => 'dall-e-3', 'prompt' => $promptSimplificado, 'n' => 1, 'size' => $size, 'quality' => 'standard'],
                    'DALL-E (retry simplificado)'
                );
                if (!$resultado['sucesso']) {
                    return ['sucesso' => false, 'url' => null, 'erro' => 'Imagem não gerada — prompt rejeitado após ajuste.'];
                }
            } else {
                return $resultado;
            }
        }

        $url = $resultado['dados']['data'][0]['url'] ?? null;

        if (!$url) {
            self::logErro('DALL-E', 'Resposta sem URL de imagem', $resultado['dados']);
            return ['sucesso' => false, 'url' => null, 'erro' => 'Imagem não retornada pela API.'];
        }

        return ['sucesso' => true, 'url' => $url, 'erro' => null];
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
    public static function buildPromptSop(array $empresa, array $sop): string
    {
        $normas = self::getNormasPorSetor($empresa['setor']);

        return "Você é O Consultor, especialista em padronização operacional empresarial com profundo conhecimento em normas e padrões de mercado.

DADOS DA EMPRESA:
Nome: {$empresa['nome']}
Setor: {$empresa['setor']}
Porte: {$empresa['colaboradores']} colaboradores, faturamento {$empresa['faturamento']}, nível de maturidade {$empresa['maturidade']}/4
Departamentos ativos: {$empresa['departamentos']}
Ferramentas utilizadas: {$empresa['ferramentas']}
Problemas identificados: {$empresa['problemas']}
Objetivos estratégicos: {$empresa['objetivos']}

PADRÃO DE MERCADO APLICÁVEL:
{$normas}

SOP A GERAR: {$sop['id']} — {$sop['nome']}
DEPARTAMENTO: {$sop['departamento']}

SUBTÓPICOS OBRIGATÓRIOS PARA ESTE SOP:
{$sop['subtopicos_texto']}

INSTRUÇÕES DE QUALIDADE — OBRIGATÓRIAS:
1. PROFUNDIDADE: cada procedimento deve ser detalhado o suficiente para ser executado por qualquer colaborador sem supervisão.
2. ESPECIFICIDADE: use o nome das ferramentas reais da empresa nos procedimentos.
3. SUBTÓPICOS: cada subtópico deve ter seu próprio conjunto completo de procedimentos (10+ passos).
4. PLANOS DE CONTINGÊNCIA: cada nível (N1/N2/N3) deve ter situação gatilho clara, ação passo a passo e responsáveis definidos.
5. KPIs: metas numéricas específicas com zonas verde/amarela/vermelha e ação automática.

GERE O SOP COMPLETO COM OS 13 COMPONENTES:
1. objetivo (string 3-5 frases)
2. escopo (object: {aplica_se: string, nao_aplica: string})
3. subtopicos (array: [{nome, descricao}])
4. responsaveis (array: [{papel, cargo}])
5. prerequisitos (array de strings, mínimo 6)
6. ferramentas (array de strings)
7. procedimentos (array por subtópico: [{subtopico, passos: [{passo, acao, responsavel, prazo, sistema, validacao}]}] — mínimo 10 passos)
8. checklist (array de strings, mínimo 12)
9. evidencias (array de strings, mínimo 5)
10. relatorios (array: [{oque, para_quem, frequencia, canal}])
11. kpis (array: [{kpi, verde, amarela, vermelha, acao_vermelha}])
12. contencao (object: {n1: {situacao, acao, quem, escalar}, n2: {situacao, acao, quem, escalar}, n3: {situacao, acao, quem, comunicacao, documentacao}})
13. versionamento (object: {versao: '1.0', data: '" . date('Y-m-d') . "', aprovador: 'Pendente'})

Responda APENAS em JSON válido. Não inclua texto fora do JSON.";
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

Responda em JSON com a estrutura:
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
    public static function buildPromptBuscaNoticias(string $setor, string $lingua, array $sites): string
    {
        $sitesStr = implode(', ', $sites);

        return "Busque as 10 notícias mais recentes e relevantes para empresas do setor {$setor} em {$lingua}.
Priorize conteúdo dos seguintes sites: {$sitesStr}
Formato de resposta: JSON com array de objetos, cada um com: titulo, url, fonte, data, resumo_bruto, setor.
Busque apenas conteúdo publicado nos últimos 7 dias.
Não inclua notícias duplicadas ou sem relevância para o setor.
Responda APENAS com o array JSON.";
    }

    /**
     * Gera prompt para análise de notícia (5 blocos)
     */
    public static function buildPromptAnaliseNoticia(string $setor, string $titulo, string $resumo): string
    {
        return "Analise esta notícia para um empresário do setor {$setor}:
Título: {$titulo}
Resumo: {$resumo}

Gere em JSON:
{
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
     * Gera prompt para geração de conteúdo (Máquina de Conteúdo)
     */
    public static function buildPromptConteudo(array $marca, string $tipo, string $tema, string $objetivo, ?string $noticiaBase = null): string
    {
        $contextoNoticia = $noticiaBase ? "\nCom base nesta notícia: {$noticiaBase}" : '';

        $instrucaoTipo = $tipo === 'carrossel'
            ? 'Para CARROSSEL gere JSON com: {slides: [{numero, tipo (capa/conteudo/cta), texto_principal, texto_secundario, prompt_imagem}], legenda, hashtags: [], prompt_imagem_padrao}'
            : 'Para POST gere JSON com: {titulo_visual, subtitulo, legenda, hashtags: [], prompt_imagem}';

        return "{$marca['prompt_master']}

Crie um {$tipo} sobre o tema: {$tema}{$contextoNoticia}
Objetivo: {$objetivo}.

{$instrucaoTipo}

O campo prompt_imagem deve combinar o estilo visual da marca com o conteúdo do slide.
Estilo base para imagens: {$marca['prompt_dalle']}
Não incluir texto ou palavras nas imagens geradas.

Responda APENAS em JSON válido.";
    }

    /**
     * Gera prompt para análise de KPI em zona vermelha
     */
    public static function buildPromptKpiCritico(array $empresa, array $kpi): string
    {
        return "KPI crítico — empresa {$empresa['nome']}, setor {$empresa['setor']}, ferramentas: {$empresa['ferramentas']}:
KPI: {$kpi['nome']}, Meta: {$kpi['meta']}, Atual: {$kpi['atual']}, SOP de origem: {$kpi['sop']}.
Nível de maturidade da empresa: {$empresa['maturidade']}/4.

Gere em JSON:
{
  \"causas_raiz\": [\"3 hipóteses específicas\"],
  \"plano_acao_imediato\": [\"3-5 passos específicos e executáveis\"],
  \"prazo_revisao\": \"X dias\",
  \"contencao_recomendada\": \"N1|N2|N3\",
  \"justificativa_contencao\": \"texto explicando por que este nível\"
}";
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
    private static function executarCurl(string $url, array $headers, array $body, string $provedor): array
    {
        $timeout = self::getTimeout();
        $maxTentativas = self::getMaxTentativas();

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
                sleep(2);
                continue;
            }

            // Erro de cURL
            if ($erro) {
                self::logErro($provedor, "Erro cURL: {$erro}", ['url' => $url, 'errno' => $errNo]);
                return ['sucesso' => false, 'conteudo' => null, 'erro' => "API {$provedor} indisponível: {$erro}", 'dados' => null];
            }

            // HTTP error
            if ($httpCode < 200 || $httpCode >= 300) {
                $dados = json_decode($resposta, true);
                $msgErro = $dados['error']['message'] ?? $dados['error']['type'] ?? "HTTP {$httpCode}";
                self::logErro($provedor, "HTTP {$httpCode}: {$msgErro}", ['url' => $url, 'body' => substr($resposta, 0, 500)]);
                return ['sucesso' => false, 'conteudo' => null, 'erro' => "API {$provedor}: {$msgErro}", 'dados' => $dados];
            }

            // Sucesso
            $dados = json_decode($resposta, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                self::logErro($provedor, 'Resposta não é JSON válido', ['raw' => substr($resposta, 0, 500)]);
                return ['sucesso' => false, 'conteudo' => null, 'erro' => 'Resposta inválida da API.', 'dados' => null];
            }

            return ['sucesso' => true, 'dados' => $dados, 'erro' => null];
        }

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

        return $normas[$setor] ?? 'ISO 9001:2015 como base universal, adaptada ao setor ' . $setor;
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
            'erro'   => $resultado['erro'] ?? null,
        ];
    }
}
