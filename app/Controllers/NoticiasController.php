<?php
/**
 * NoticiasController — Sistema de Notícias por IA (F-09 Implementation)
 * O Consultor — Sistema Operacional Empresarial
 * 
 * Perplexity + GPT/Claude para notícias automáticas do setor
 */

class NoticiasController
{
    /**
     * Inicializar perfil de busca com sites de referência (F-09)
     * Chamado automaticamente após diagnóstico
     */
    public function inicializarPerfil(?int $empresaId = null): bool
    {
        $empresaId = $empresaId ?? Auth::empresa();
        if (!$empresaId) return false;

        try {
            // Verificar se já tem sites cadastrados
            $sitesExistentes = Database::queryOne(
                "SELECT COUNT(*) as total FROM empresa_perfil_busca WHERE empresa_id = :empresa_id",
                ['empresa_id' => $empresaId]
            );

            if ($sitesExistentes['total'] > 0) {
                return true; // Já inicializado
            }

            // Buscar dados da empresa
            $empresa = Database::queryOne(
                "SELECT segmento, lingua_principal FROM empresas WHERE id = :id",
                ['id' => $empresaId]
            );

            if (!$empresa) return false;

            $setor = $empresa['segmento'] ?? 'Tecnologia';
            $lingua = $empresa['lingua_principal'] ?? 'Português';

            // Verificar se alguma API está ativa
            if (!Configuracao::apiAtiva('openai') && !Configuracao::apiAtiva('anthropic')) {
                Logger::warning('Inicialização de perfil sem APIs ativas', [
                    'empresa_id' => $empresaId,
                    'setor' => $setor
                ]);
                return false;
            }

            // Gerar sites de referência via IA
            $prompt = ApiHelper::buildPromptSitesReferencia($setor, $lingua);
            $resultado = ApiHelper::chamarAnalise($prompt, true);

            if (!$resultado['sucesso']) {
                Logger::error('Erro ao gerar sites de referência', [
                    'empresa_id' => $empresaId,
                    'erro' => $resultado['erro']
                ]);
                return false;
            }

            $sites = is_array($resultado['conteudo']) ? $resultado['conteudo'] : [];
            
            // Inserir sites no banco
            foreach ($sites as $url) {
                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    Database::execute(
                        "INSERT INTO empresa_perfil_busca (empresa_id, site_url, categoria, ativo, adicionado_por) 
                         VALUES (:empresa_id, :site_url, :categoria, 1, 'ia')",
                        [
                            'empresa_id' => $empresaId,
                            'site_url' => $url,
                            'categoria' => $setor,
                        ]
                    );
                }
            }

            Logger::acao('Perfil de busca inicializado', [
                'empresa_id' => $empresaId,
                'sites_adicionados' => count($sites),
                'setor' => $setor
            ]);

            return true;

        } catch (Exception $e) {
            Logger::error('Erro ao inicializar perfil de busca', [
                'erro' => $e->getMessage(),
                'empresa_id' => $empresaId
            ]);
            return false;
        }
    }

    /**
     * Buscar notícias (manual ou automática) - F-09 Core
     */
    public function buscar(): void
    {
        // "Manual" = disparado pelo usuário (botão "Buscar agora"), via GET (?manual=1)
        // ou POST (chamado internamente por ConteudoController::buscarAgora()).
        // "Automática" = job/cron, identificado por empresa_id na querystring.
        $isManual = isset($_GET['manual']) || $_SERVER['REQUEST_METHOD'] === 'POST';

        if ($isManual) {
            Auth::proteger();
            $empresaId = Auth::empresa();

            // Admin em modo "Todas as empresas" não tem empresa_id na sessão.
            // Sem isso, cairia no fallback de processarTodasEmpresas() abaixo,
            // rodando a busca para TODAS as empresas do banco em vez da que o
            // usuário pediu — comportamento inesperado que aparecia como
            // "Erro interno ao buscar notícias" sempre que qualquer empresa falhasse.
            if (!$empresaId) {
                header('Content-Type: application/json');
                echo json_encode([
                    'sucesso' => false,
                    'erro' => 'Selecione uma empresa específica no menu do topo antes de buscar notícias.',
                ]);
                exit;
            }
        } else {
            // Busca automática - processar todas as empresas ou específica
            $empresaId = (int) ($_GET['empresa_id'] ?? 0);
        }

        $inicioProcessamento = microtime(true);

        try {
            if ($empresaId) {
                $resultado = $this->processarEmpresa($empresaId, $isManual);
            } else {
                // Busca automática para todas as empresas
                $resultado = $this->processarTodasEmpresas();
            }

            $tempo = round((microtime(true) - $inicioProcessamento) * 1000);

            if ($isManual) {
                header('Content-Type: application/json');
                echo json_encode([
                    'sucesso' => $resultado['sucesso'],
                    'mensagem' => $resultado['mensagem'] ?? 'Busca concluída!',
                    'erro' => $resultado['erro'] ?? null,
                    'noticias_novas' => $resultado['noticias_novas'] ?? 0,
                    'tempo_ms' => $tempo,
                ]);
                exit;
            }

        } catch (Exception $e) {
            Logger::error('Erro na busca de notícias', [
                'erro' => $e->getMessage(),
                'empresa_id' => $empresaId,
                'manual' => $isManual
            ]);

            if ($isManual) {
                header('Content-Type: application/json');
                echo json_encode([
                    'sucesso' => false,
                    'erro' => 'Erro interno ao buscar notícias. Tente novamente.'
                ]);
                exit;
            }
        }
    }

    /**
     * Processar busca de notícias para uma empresa específica
     */
    private function processarEmpresa(int $empresaId, bool $isManual): array
    {
        // Buscar configuração da empresa
        $empresa = Database::queryOne(
            "SELECT nome, segmento, lingua_principal, instrucoes_busca_noticias FROM empresas WHERE id = :id",
            ['id' => $empresaId]
        );

        if (!$empresa) {
            return ['sucesso' => false, 'erro' => 'Empresa não encontrada'];
        }

        // Buscar sites de referência ativos
        $sites = Database::query(
            "SELECT site_url FROM empresa_perfil_busca 
             WHERE empresa_id = :empresa_id AND ativo = 1 
             ORDER BY adicionado_por DESC, criado_em ASC",
            ['empresa_id' => $empresaId]
        );

        if (empty($sites)) {
            // Tentar inicializar perfil automaticamente
            if (!$this->inicializarPerfil($empresaId)) {
                return ['sucesso' => false, 'erro' => 'Nenhum site de referência configurado'];
            }
            
            // Buscar novamente após inicialização
            $sites = Database::query(
                "SELECT site_url FROM empresa_perfil_busca WHERE empresa_id = :empresa_id AND ativo = 1",
                ['empresa_id' => $empresaId]
            );
        }

        $sitesArray = array_column($sites, 'site_url');
        $setor = $empresa['segmento'] ?? 'Tecnologia';
        $lingua = $empresa['lingua_principal'] ?? 'Português';
        $instrucoes = (string) ($empresa['instrucoes_busca_noticias'] ?? '');

        // Iniciar log de busca
        $logId = $this->criarLogBusca($empresaId, $isManual ? 'manual' : 'automatica', $sitesArray);

        $noticiasNovas = 0;
        $noticiasEncontradas = 0;
        $noticiasDuplicadas = 0;
        $apiUtilizada = null;

        try {
            $imagensBusca = [];

            // Tentar Perplexity primeiro
            if (Configuracao::apiAtiva('perplexity')) {
                $apiUtilizada = 'perplexity';
                $prompt = ApiHelper::buildPromptBuscaNoticias($setor, $lingua, $sitesArray, $instrucoes);
                $resultado = ApiHelper::chamarPerplexity($prompt, null, true);

                if ($resultado['sucesso'] && is_array($resultado['conteudo'])) {
                    $noticias = $resultado['conteudo'];
                    $imagensBusca = $resultado['imagens'] ?? [];
                } else {
                    // Fallback para Claude/GPT
                    $apiUtilizada = Configuracao::apiAtiva('anthropic') ? 'anthropic' : 'openai';
                    $resultado = ApiHelper::chamarAnalise($prompt, true);
                    $noticias = $resultado['sucesso'] ? $resultado['conteudo'] : [];
                }
            } else {
                // Usar Claude/GPT diretamente
                $apiUtilizada = Configuracao::apiAtiva('anthropic') ? 'anthropic' : 'openai';
                $prompt = "Busque as 10 notícias mais recentes do setor {$setor} em {$lingua}. Retorne JSON: [{titulo, url, fonte, data, resumo_bruto, setor}]";
                $resultado = ApiHelper::chamarAnalise($prompt, true);
                $noticias = $resultado['sucesso'] ? $resultado['conteudo'] : [];
            }

            // Normalizar: a IA pode devolver a lista aninhada em chaves como 'noticias'/'resultados'.
            if (!is_array($noticias)) {
                $noticias = [];
            } elseif (isset($noticias['noticias']) && is_array($noticias['noticias'])) {
                $noticias = $noticias['noticias'];
            } elseif (isset($noticias['resultados']) && is_array($noticias['resultados'])) {
                $noticias = $noticias['resultados'];
            }
            // Se veio um único objeto (associativo) em vez de lista, embrulha.
            if (!empty($noticias) && array_keys($noticias) !== range(0, count($noticias) - 1)) {
                $noticias = [$noticias];
            }

            // Casar imagens reais (return_images) com cada notícia pelo domínio da URL.
            if (!empty($imagensBusca)) {
                $noticias = ApiHelper::casarImagensComNoticias($noticias, $imagensBusca);
            }

            $noticiasEncontradas = count($noticias);

            // Processar cada notícia
            foreach ($noticias as $noticia) {
                if (!is_array($noticia) || empty($noticia['url']) || empty($noticia['titulo'])) continue;

                // Verificar duplicata
                $existe = Database::queryOne(
                    "SELECT id FROM noticias WHERE empresa_id = :empresa_id AND url = :url",
                    ['empresa_id' => $empresaId, 'url' => $noticia['url']]
                );

                if ($existe) {
                    $noticiasDuplicadas++;
                    continue;
                }

                // Gerar análise dos 5 blocos (na língua configurada, traduzindo se necessário)
                $analise = $this->gerarAnaliseBlocos($noticia, $setor, $lingua);

                if ($analise) {
                    // Salvar notícia no banco
                    $this->salvarNoticia($empresaId, $noticia, $analise, $apiUtilizada);
                    $noticiasNovas++;
                }
            }

            // Atualizar log com sucesso
            $this->atualizarLogBusca($logId, true, $noticiasEncontradas, $noticiasNovas, $noticiasDuplicadas, $apiUtilizada);

            // Criar alerta se há notícias novas
            if ($noticiasNovas > 0) {
                $this->criarAlertaNovasNoticias($empresaId, $noticiasNovas);
            }

            return [
                'sucesso' => true,
                'mensagem' => "Busca concluída! {$noticiasNovas} notícias novas encontradas.",
                'noticias_novas' => $noticiasNovas,
                'api_utilizada' => $apiUtilizada,
            ];

        } catch (\Throwable $e) {
            error_log('[O CONSULTOR][PROCESSAR-EMPRESA] ' . get_class($e) . ': ' . $e->getMessage()
                . ' | ' . $e->getFile() . ':' . $e->getLine() . ' | empresa=' . $empresaId);
            // Atualizar log com erro
            $this->atualizarLogBusca($logId, false, $noticiasEncontradas, $noticiasNovas, $noticiasDuplicadas, $apiUtilizada, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Gerar análise de 5 blocos para uma notícia
     */
    private function gerarAnaliseBlocos(array $noticia, string $setor, string $lingua = 'Português'): ?array
    {
        try {
            $prompt = ApiHelper::buildPromptAnaliseNoticia(
                $setor,
                $noticia['titulo'],
                $noticia['resumo_bruto'] ?? $noticia['titulo'],
                $lingua
            );

            $resultado = ApiHelper::chamarAnalise($prompt, true);

            if ($resultado['sucesso'] && is_array($resultado['conteudo'])) {
                return $resultado['conteudo'];
            }

            return null;

        } catch (Exception $e) {
            Logger::error('Erro ao gerar análise da notícia', [
                'titulo' => $noticia['titulo'],
                'erro' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Extrai a imagem de capa da página de uma notícia lendo as meta tags
     * og:image / twitter:image (padrão Open Graph, usado por todos os portais).
     * Retorna a URL absoluta da imagem ou null se não encontrar/acessar.
     */
    /**
     * Wrapper público para extrair a imagem de capa de uma página de notícia.
     * Reutilizado por outros controllers (ex.: ConteudoController no detalhe).
     */
    public function extrairImagemNoticia(string $url): ?string
    {
        return $this->extrairImagemDaPagina($url);
    }

    private function extrairImagemDaPagina(string $url): ?string
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 5,
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => false,
                // Baixa só o começo do HTML (as meta tags ficam no <head>).
                CURLOPT_RANGE          => '0-131072',
                CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; OConsultorBot/1.0; +https://app.oconsultor.digital)',
                CURLOPT_HTTPHEADER     => ['Accept: text/html,application/xhtml+xml'],
            ]);
            $html = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if (!$html || $httpCode >= 400) {
                return null;
            }

            // Procura og:image / twitter:image (com property ou name, em qualquer ordem de atributos).
            $padroes = [
                '/<meta[^>]+(?:property|name)=["\']og:image(?::url)?["\'][^>]+content=["\']([^"\']+)["\']/i',
                '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+(?:property|name)=["\']og:image(?::url)?["\']/i',
                '/<meta[^>]+(?:property|name)=["\']twitter:image(?::src)?["\'][^>]+content=["\']([^"\']+)["\']/i',
                '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+(?:property|name)=["\']twitter:image(?::src)?["\']/i',
            ];
            foreach ($padroes as $padrao) {
                if (preg_match($padrao, $html, $m) && !empty($m[1])) {
                    $img = html_entity_decode(trim($m[1]), ENT_QUOTES);
                    // Resolve URLs relativas/protocol-relative para absolutas.
                    if (str_starts_with($img, '//')) {
                        $scheme = parse_url($url, PHP_URL_SCHEME) ?: 'https';
                        $img = $scheme . ':' . $img;
                    } elseif (str_starts_with($img, '/')) {
                        $scheme = parse_url($url, PHP_URL_SCHEME) ?: 'https';
                        $host = parse_url($url, PHP_URL_HOST);
                        if ($host) $img = $scheme . '://' . $host . $img;
                    }
                    if (filter_var($img, FILTER_VALIDATE_URL)) {
                        return mb_substr($img, 0, 1000);
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log('[O CONSULTOR][OG-IMAGE] Falha ao extrair imagem de ' . $url . ': ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Salvar notícia no banco de dados
     */
    private function salvarNoticia(int $empresaId, array $noticia, array $analise, string $apiUtilizada): bool
    {
        // imagem_url é opcional. 1º tenta a que veio da busca (return_images);
        // se não tiver, extrai a og:image da própria página da notícia — que é
        // a imagem de capa que todo portal de notícia expõe nas meta tags.
        $imagemUrl = trim((string) ($noticia['imagem_url'] ?? ''));
        $imagemUrl = filter_var($imagemUrl, FILTER_VALIDATE_URL) ? $imagemUrl : null;
        if ($imagemUrl === null && !empty($noticia['url'])) {
            $imagemUrl = $this->extrairImagemDaPagina((string) $noticia['url']);
        }

        // Título e resumo salvos na língua configurada: a análise (IA) devolve a
        // versão traduzida de forma fiel. Se por algum motivo não vier, cai para
        // o texto original da fonte.
        $titulo = trim((string) ($analise['titulo'] ?? '')) ?: (string) $noticia['titulo'];
        $resumo = trim((string) ($analise['resumo'] ?? '')) ?: ($noticia['resumo_bruto'] ?? null);

        try {
            return Database::execute(
                "INSERT INTO noticias (
                    empresa_id, titulo, url, imagem_url, fonte, data_publicacao, categoria, relevancia, setor,
                    bloco1_noticia, bloco2_significa, bloco3_o_que_fazer, bloco4_pergunta, bloco5_conexao,
                    tags, resumo_bruto, processado_via
                 ) VALUES (
                    :empresa_id, :titulo, :url, :imagem_url, :fonte, :data_publicacao, :categoria, :relevancia, :setor,
                    :bloco1, :bloco2, :bloco3, :bloco4, :bloco5,
                    :tags, :resumo_bruto, :processado_via
                 )",
                [
                    'empresa_id' => $empresaId,
                    'titulo' => $titulo,
                    'url' => $noticia['url'],
                    'imagem_url' => $imagemUrl,
                    'fonte' => $noticia['fonte'] ?? 'Desconhecida',
                    'data_publicacao' => $noticia['data'] ?? date('Y-m-d'),
                    'categoria' => $analise['categoria'] ?? 'Mercado',
                    'relevancia' => $analise['relevancia'] ?? 'media',
                    'setor' => $noticia['setor'] ?? 'Geral',
                    'bloco1' => $analise['bloco1_noticia'] ?? '',
                    'bloco2' => $analise['bloco2_significa'] ?? '',
                    'bloco3' => $analise['bloco3_o_que_fazer'] ?? '',
                    'bloco4' => $analise['bloco4_pergunta'] ?? '',
                    'bloco5' => $analise['bloco5_conexao'] ?? '',
                    'tags' => json_encode($analise['tags'] ?? []),
                    'resumo_bruto' => $resumo,
                    'processado_via' => $apiUtilizada === 'perplexity' ? 'perplexity+gpt' : ($apiUtilizada . '_fallback'),
                ]
            );
        } catch (Exception $e) {
            // Coluna imagem_url pode não existir ainda (migration 035 não rodada);
            // tenta de novo sem ela para não bloquear o salvamento da notícia.
            if (stripos($e->getMessage(), 'imagem_url') !== false) {
                return Database::execute(
                    "INSERT INTO noticias (
                        empresa_id, titulo, url, fonte, data_publicacao, categoria, relevancia, setor,
                        bloco1_noticia, bloco2_significa, bloco3_o_que_fazer, bloco4_pergunta, bloco5_conexao,
                        tags, resumo_bruto, processado_via
                     ) VALUES (
                        :empresa_id, :titulo, :url, :fonte, :data_publicacao, :categoria, :relevancia, :setor,
                        :bloco1, :bloco2, :bloco3, :bloco4, :bloco5,
                        :tags, :resumo_bruto, :processado_via
                     )",
                    [
                        'empresa_id' => $empresaId,
                        'titulo' => $titulo,
                        'url' => $noticia['url'],
                        'fonte' => $noticia['fonte'] ?? 'Desconhecida',
                        'data_publicacao' => $noticia['data'] ?? date('Y-m-d'),
                        'categoria' => $analise['categoria'] ?? 'Mercado',
                        'relevancia' => $analise['relevancia'] ?? 'media',
                        'setor' => $noticia['setor'] ?? 'Geral',
                        'bloco1' => $analise['bloco1_noticia'] ?? '',
                        'bloco2' => $analise['bloco2_significa'] ?? '',
                        'bloco3' => $analise['bloco3_o_que_fazer'] ?? '',
                        'bloco4' => $analise['bloco4_pergunta'] ?? '',
                        'bloco5' => $analise['bloco5_conexao'] ?? '',
                        'tags' => json_encode($analise['tags'] ?? []),
                        'resumo_bruto' => $resumo,
                        'processado_via' => $apiUtilizada === 'perplexity' ? 'perplexity+gpt' : ($apiUtilizada . '_fallback'),
                    ]
                );
            }
            throw $e;
        }
    }

    /**
     * Processar busca para todas as empresas (cron job)
     */
    private function processarTodasEmpresas(): array
    {
        $empresas = Database::query(
            "SELECT id, nome FROM empresas WHERE ativo = 1 ORDER BY id"
        );

        $sucessos = 0;
        $erros = 0;

        foreach ($empresas as $empresa) {
            try {
                $resultado = $this->processarEmpresa($empresa['id'], false);
                if ($resultado['sucesso']) {
                    $sucessos++;
                } else {
                    $erros++;
                }
            } catch (Exception $e) {
                $erros++;
                Logger::error('Erro ao processar empresa no cron', [
                    'empresa_id' => $empresa['id'],
                    'erro' => $e->getMessage()
                ]);
            }
        }

        return [
            'sucesso' => true,
            'empresas_processadas' => $sucessos + $erros,
            'sucessos' => $sucessos,
            'erros' => $erros,
        ];
    }

    /**
     * Adicionar site de referência
     */
    public function adicionarSite(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $url = trim($_POST['url'] ?? '');
        $categoria = trim($_POST['categoria'] ?? 'Geral');

        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'URL inválida.']);
            exit;
        }

        $empresaId = Auth::empresa();

        // Verificar duplicata
        $existe = Database::queryOne(
            "SELECT id FROM empresa_perfil_busca WHERE empresa_id = :empresa_id AND site_url = :url",
            ['empresa_id' => $empresaId, 'url' => $url]
        );

        if ($existe) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Este site já está cadastrado.']);
            exit;
        }

        $sucesso = Database::execute(
            "INSERT INTO empresa_perfil_busca (empresa_id, site_url, categoria, ativo, adicionado_por) 
             VALUES (:empresa_id, :url, :categoria, 1, 'usuario')",
            [
                'empresa_id' => $empresaId,
                'url' => $url,
                'categoria' => $categoria,
            ]
        );

        if ($sucesso) {
            Logger::acao('Site de referência adicionado', ['url' => $url]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true, 'mensagem' => 'Site adicionado com sucesso!']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao adicionar site.']);
        }
        exit;
    }

    /**
     * Remover site de referência
     */
    public function removerSite(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $siteId = (int) ($_POST['site_id'] ?? 0);

        if (!$siteId) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'ID do site é obrigatório.']);
            exit;
        }

        $sucesso = Database::execute(
            "DELETE FROM empresa_perfil_busca 
             WHERE id = :id AND empresa_id = :empresa_id",
            ['id' => $siteId, 'empresa_id' => Auth::empresa()]
        );

        if ($sucesso) {
            Logger::acao('Site de referência removido', ['site_id' => $siteId]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true, 'mensagem' => 'Site removido com sucesso!']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao remover site.']);
        }
        exit;
    }

    // ===== HELPER METHODS =====

    private function criarLogBusca(int $empresaId, string $tipo, array $sites): int
    {
        // O log é auxiliar/diagnóstico: uma falha aqui (ex.: coluna api_utilizada
        // NOT NULL sem valor ainda definido, ou tabela ausente) NUNCA deve impedir
        // a busca de notícias em si. Retorna 0 quando não é possível criar o log.
        try {
            Database::execute(
                "INSERT INTO busca_logs (empresa_id, tipo_busca, sites_referencia) 
                 VALUES (:empresa_id, :tipo, :sites)",
                [
                    'empresa_id' => $empresaId,
                    'tipo' => $tipo,
                    'sites' => json_encode($sites),
                ]
            );
            return (int) Database::lastInsertId();
        } catch (Exception $e) {
            error_log('[O CONSULTOR][BUSCA-LOGS] Falha ao criar log (ignorada): ' . $e->getMessage());
            return 0;
        }
    }

    private function atualizarLogBusca(int $logId, bool $sucesso, int $encontradas, int $novas, int $duplicadas, ?string $api, ?string $erro = null): void
    {
        if ($logId <= 0) return; // criarLogBusca falhou antes; nada a atualizar.
        try {
            Database::execute(
                "UPDATE busca_logs SET 
                    api_utilizada = :api,
                    noticias_encontradas = :encontradas,
                    noticias_novas = :novas,
                    noticias_duplicadas = :duplicadas,
                    sucesso = :sucesso,
                    erro_detalhes = :erro
                 WHERE id = :id",
                [
                    'id' => $logId,
                    'api' => $api,
                    'encontradas' => $encontradas,
                    'novas' => $novas,
                    'duplicadas' => $duplicadas,
                    'sucesso' => $sucesso ? 1 : 0,
                    'erro' => $erro,
                ]
            );
        } catch (Exception $e) {
            error_log('[O CONSULTOR][BUSCA-LOGS] Falha ao atualizar log (ignorada): ' . $e->getMessage());
        }
    }

    private function criarAlertaNovasNoticias(int $empresaId, int $quantidade): void
    {
        // Alerta é só um "aviso" complementar à busca — nunca deve quebrar o fluxo
        // principal (notícias já foram salvas com sucesso neste ponto).
        try {
            Database::execute(
                "INSERT INTO alertas (empresa_id, tipo, titulo, descricao, prioridade, status) 
                 VALUES (:empresa_id, 'novo_conteudo', :titulo, :descricao, 'info', 'ativo')",
                [
                    'empresa_id' => $empresaId,
                    'titulo' => 'Novas notícias disponíveis',
                    'descricao' => "{$quantidade} nova(s) notícia(s) do seu setor foram encontradas e processadas pela IA.",
                ]
            );
        } catch (\Throwable $e) {
            error_log('[O CONSULTOR][ALERTA-NOTICIAS] Falha ao criar alerta (ignorada): ' . $e->getMessage());
        }
    }

    /**
     * Buscar notícias agora (manual) — ENFILEIRA a busca em vez de executá-la
     * de forma síncrona. A busca faz 1 chamada de IA para buscar + 1 chamada de
     * IA por notícia encontrada (até 10): rodando tudo numa única requisição
     * HTTP isso passa fácil de 60-120s e o proxy (Nginx/Apache) mata a conexão
     * com 504/timeout, mesmo que as notícias já tenham sido salvas no banco.
     * O front-end acompanha o progresso via polling (ver enfileirarBusca()).
     */
    public function buscarAgora(): void
    {
        Auth::proteger();

        $empresaId = Auth::empresa();
        if (!$empresaId) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Empresa não identificada.']);
            exit;
        }

        try {
            $filaId = $this->enfileirarBusca($empresaId, 'manual');
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'fila_id' => $filaId,
                'mensagem' => 'Busca iniciada, processando...',
            ]);
        } catch (\Throwable $e) {
            error_log('[O CONSULTOR][BUSCAR-NOTICIAS] ' . get_class($e) . ': ' . $e->getMessage()
                . ' | ' . $e->getFile() . ':' . $e->getLine() . ' | empresa=' . $empresaId);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao iniciar busca: ' . $e->getMessage()]);
        }
        exit;
    }

    // =========================================================================
    // FILA DE BUSCA DE NOTÍCIAS — processamento em pequenos passos (sem timeout)
    // Mesmo padrão usado pela fila_geracao_sop (ver SopController).
    // =========================================================================

    /**
     * Garante que a tabela fila_busca_noticias existe (evita depender de migration manual).
     */
    private function garantirTabelaFilaBusca(): void
    {
        Database::execute(
            "CREATE TABLE IF NOT EXISTS fila_busca_noticias (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                empresa_id INT UNSIGNED NOT NULL,
                tipo_busca ENUM('manual', 'automatica') NOT NULL DEFAULT 'manual',
                status ENUM('pendente', 'processando', 'concluido', 'erro') NOT NULL DEFAULT 'pendente',
                etapa ENUM('buscar', 'analisar') NOT NULL DEFAULT 'buscar',
                log_id INT UNSIGNED NULL,
                api_utilizada VARCHAR(20) NULL,
                noticias_pendentes JSON NULL,
                noticias_encontradas INT UNSIGNED NOT NULL DEFAULT 0,
                noticias_novas INT UNSIGNED NOT NULL DEFAULT 0,
                noticias_duplicadas INT UNSIGNED NOT NULL DEFAULT 0,
                mensagem VARCHAR(500) NULL,
                tentativas INT UNSIGNED NOT NULL DEFAULT 0,
                criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                iniciado_em DATETIME NULL,
                concluido_em DATETIME NULL,
                atualizado_em DATETIME NULL,
                INDEX idx_status (status),
                INDEX idx_empresa (empresa_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        // Garante a coluna imagem_url em noticias (migration 035 pode não ter rodado).
        $this->garantirColunaImagemUrl();
    }

    /**
     * Garante que a coluna noticias.imagem_url existe (idempotente).
     * Evita depender de a migration 035 ter sido executada manualmente.
     */
    private function garantirColunaImagemUrl(): void
    {
        try {
            $existe = Database::queryOne(
                "SELECT COUNT(*) AS total FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'noticias' AND COLUMN_NAME = 'imagem_url'"
            );
            if ((int) ($existe['total'] ?? 0) === 0) {
                Database::execute("ALTER TABLE noticias ADD COLUMN imagem_url VARCHAR(1000) NULL AFTER url");
            }
        } catch (\Throwable $e) {
            error_log('[O CONSULTOR][IMAGEM-URL] Falha ao garantir coluna imagem_url (ignorada): ' . $e->getMessage());
        }
    }

    /**
     * Enfileira uma busca de notícias para a empresa. Retorna o id da fila.
     */
    public function enfileirarBusca(int $empresaId, string $tipo = 'manual'): int
    {
        $this->garantirTabelaFilaBusca();

        // Remove pedidos travados (pendente/erro) antigos da mesma empresa antes de enfileirar um novo.
        Database::execute(
            "DELETE FROM fila_busca_noticias WHERE empresa_id = :empresa_id AND status IN ('pendente', 'erro')",
            ['empresa_id' => $empresaId]
        );

        Database::execute(
            "INSERT INTO fila_busca_noticias (empresa_id, tipo_busca, status, etapa, mensagem, criado_em, atualizado_em)
             VALUES (:empresa_id, :tipo, 'pendente', 'buscar', 'Aguardando processamento...', NOW(), NOW())",
            ['empresa_id' => $empresaId, 'tipo' => $tipo]
        );
        $filaId = (int) Database::lastInsertId();

        $this->dispararWorkerBuscaNoticias();

        return $filaId;
    }

    /**
     * Dispara o worker CLI (worker/processar_fila_noticias.php) como processo desanexado.
     * Best-effort: se não conseguir, o cron (se configurado) é o mecanismo garantido.
     */
    private function dispararWorkerBuscaNoticias(): void
    {
        if (!function_exists('exec')) return;
        $disabled = explode(',', str_replace(' ', '', (string) ini_get('disable_functions')));
        if (in_array('exec', $disabled, true)) return;

        $candidatos = [PHP_BINDIR . '/php', '/opt/plesk/php/8.3/bin/php', '/opt/plesk/php/8.2/bin/php', '/usr/bin/php', 'php'];
        $phpBin = 'php';
        foreach ($candidatos as $c) {
            if ($c === 'php' || @is_executable($c)) { $phpBin = $c; break; }
        }

        $script = ROOT_PATH . '/worker/processar_fila_noticias.php';
        if (!file_exists($script)) return;

        $logWorker = ROOT_PATH . '/worker/fila_noticias.log';
        $cmd = escapeshellcmd($phpBin) . ' ' . escapeshellarg($script)
            . ' >> ' . escapeshellarg($logWorker) . ' 2>&1 &';
        @exec($cmd);
    }

    /**
     * Endpoint de POLLING: retorna o status atual de uma fila de busca.
     */
    public function statusBuscaFila(): void
    {
        Auth::proteger();
        header('Content-Type: application/json');

        $filaId = (int) ($_GET['fila_id'] ?? 0);
        if (!$filaId) {
            echo json_encode(['sucesso' => false, 'erro' => 'fila_id não informado.']);
            exit;
        }

        $pedido = Database::queryOne(
            "SELECT status, etapa, mensagem, noticias_novas, noticias_encontradas, noticias_duplicadas
             FROM fila_busca_noticias WHERE id = :id",
            ['id' => $filaId]
        );

        if (!$pedido) {
            echo json_encode(['sucesso' => false, 'erro' => 'Fila não encontrada.']);
            exit;
        }

        echo json_encode([
            'sucesso' => true,
            'status' => $pedido['status'],
            'etapa' => $pedido['etapa'],
            'mensagem' => $pedido['mensagem'],
            'concluido' => $pedido['status'] === 'concluido',
            'erro' => $pedido['status'] === 'erro',
            'noticias_novas' => (int) $pedido['noticias_novas'],
            'noticias_encontradas' => (int) $pedido['noticias_encontradas'],
        ]);
        exit;
    }

    /**
     * Endpoint HTTP para processar UM passo da fila (fallback quando não há cron/exec).
     * Cada chamada faz apenas 1 chamada de IA, cabendo dentro do timeout do proxy.
     */
    public function processarFilaBuscaHttp(): void
    {
        Auth::proteger();
        @set_time_limit(70);
        header('Content-Type: application/json');

        $resultado = $this->processarProximaEtapaBusca();
        echo json_encode($resultado);
        exit;
    }

    /**
     * Processa UM passo da fila de busca (a etapa "buscar" OU a análise de
     * UMA notícia da etapa "analisar"). Chamado pelo worker CLI em loop ou
     * pelo polling HTTP do front-end.
     */
    public function processarProximaEtapaBusca(): array
    {
        $pedido = Database::queryOne(
            "SELECT * FROM fila_busca_noticias
             WHERE status = 'pendente'
                OR (status = 'processando' AND atualizado_em < (NOW() - INTERVAL 240 SECOND))
             ORDER BY criado_em ASC LIMIT 1"
        );

        if (!$pedido) {
            $emProcesso = Database::queryOne("SELECT id FROM fila_busca_noticias WHERE status = 'processando' LIMIT 1");
            if ($emProcesso) {
                return ['sucesso' => true, 'processando' => true, 'mensagem' => 'Processando...'];
            }
            return ['sucesso' => true, 'vazio' => true, 'mensagem' => 'Fila vazia.'];
        }

        $filaId = (int) $pedido['id'];

        Database::execute(
            "UPDATE fila_busca_noticias SET status = 'processando', iniciado_em = COALESCE(iniciado_em, NOW()), atualizado_em = NOW() WHERE id = :id",
            ['id' => $filaId]
        );

        try {
            if ($pedido['etapa'] === 'buscar') {
                return $this->executarEtapaBuscar($pedido);
            }
            return $this->executarEtapaAnalisar($pedido);
        } catch (\Throwable $e) {
            error_log('[O CONSULTOR][FILA-BUSCA-NOTICIAS] ' . get_class($e) . ': ' . $e->getMessage()
                . ' | ' . $e->getFile() . ':' . $e->getLine() . ' | fila_id=' . $filaId);
            Database::execute(
                "UPDATE fila_busca_noticias SET status = 'erro', mensagem = :msg, tentativas = tentativas + 1, atualizado_em = NOW() WHERE id = :id",
                ['msg' => substr($e->getMessage(), 0, 490), 'id' => $filaId]
            );
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }

    /**
     * Etapa 1: faz a busca (1 chamada de IA) e separa as notícias que precisam
     * de análise (as duplicadas já são descartadas aqui, sem gastar chamada de IA).
     */
    private function executarEtapaBuscar(array $pedido): array
    {
        $filaId = (int) $pedido['id'];
        $empresaId = (int) $pedido['empresa_id'];

        $empresa = Database::queryOne(
            "SELECT nome, segmento, lingua_principal, instrucoes_busca_noticias FROM empresas WHERE id = :id",
            ['id' => $empresaId]
        );
        if (!$empresa) {
            Database::execute(
                "UPDATE fila_busca_noticias SET status = 'erro', mensagem = 'Empresa não encontrada', atualizado_em = NOW() WHERE id = :id",
                ['id' => $filaId]
            );
            return ['sucesso' => false, 'erro' => 'Empresa não encontrada'];
        }

        $sites = Database::query(
            "SELECT site_url FROM empresa_perfil_busca WHERE empresa_id = :empresa_id AND ativo = 1 ORDER BY adicionado_por DESC, criado_em ASC",
            ['empresa_id' => $empresaId]
        );
        if (empty($sites)) {
            if (!$this->inicializarPerfil($empresaId)) {
                Database::execute(
                    "UPDATE fila_busca_noticias SET status = 'erro', mensagem = 'Nenhum site de referência configurado', atualizado_em = NOW() WHERE id = :id",
                    ['id' => $filaId]
                );
                return ['sucesso' => false, 'erro' => 'Nenhum site de referência configurado'];
            }
            $sites = Database::query(
                "SELECT site_url FROM empresa_perfil_busca WHERE empresa_id = :empresa_id AND ativo = 1",
                ['empresa_id' => $empresaId]
            );
        }

        $sitesArray = array_column($sites, 'site_url');
        $setor = $empresa['segmento'] ?? 'Tecnologia';
        $lingua = $empresa['lingua_principal'] ?? 'Português';
        $instrucoes = (string) ($empresa['instrucoes_busca_noticias'] ?? '');

        $logId = $pedido['log_id'] ?: $this->criarLogBusca($empresaId, $pedido['tipo_busca'], $sitesArray);

        // Uma única chamada de IA nesta etapa.
        $imagensBusca = [];
        if (Configuracao::apiAtiva('perplexity')) {
            $apiUtilizada = 'perplexity';
            $prompt = ApiHelper::buildPromptBuscaNoticias($setor, $lingua, $sitesArray, $instrucoes);
            $resultado = ApiHelper::chamarPerplexity($prompt, null, true);
            if ($resultado['sucesso'] && is_array($resultado['conteudo'])) {
                $noticias = $resultado['conteudo'];
                $imagensBusca = $resultado['imagens'] ?? [];
            } else {
                $apiUtilizada = Configuracao::apiAtiva('anthropic') ? 'anthropic' : 'openai';
                $resultado = ApiHelper::chamarAnalise($prompt, true);
                $noticias = $resultado['sucesso'] ? $resultado['conteudo'] : [];
            }
        } else {
            $apiUtilizada = Configuracao::apiAtiva('anthropic') ? 'anthropic' : 'openai';
            $prompt = "Busque as 10 notícias mais recentes do setor {$setor} em {$lingua}. Retorne JSON: [{titulo, url, fonte, data, resumo_bruto, setor}]";
            $resultado = ApiHelper::chamarAnalise($prompt, true);
            $noticias = $resultado['sucesso'] ? $resultado['conteudo'] : [];
        }

        if (!is_array($noticias)) {
            $noticias = [];
        } elseif (isset($noticias['noticias']) && is_array($noticias['noticias'])) {
            $noticias = $noticias['noticias'];
        } elseif (isset($noticias['resultados']) && is_array($noticias['resultados'])) {
            $noticias = $noticias['resultados'];
        }
        if (!empty($noticias) && array_keys($noticias) !== range(0, count($noticias) - 1)) {
            $noticias = [$noticias];
        }

        // Casar imagens reais (return_images) com cada notícia pelo domínio da URL.
        if (!empty($imagensBusca)) {
            $noticias = ApiHelper::casarImagensComNoticias($noticias, $imagensBusca);
        }

        $noticiasEncontradas = count($noticias);
        $pendentes = [];
        $duplicadas = 0;

        foreach ($noticias as $noticia) {
            if (!is_array($noticia) || empty($noticia['url']) || empty($noticia['titulo'])) continue;
            $existe = Database::queryOne(
                "SELECT id FROM noticias WHERE empresa_id = :empresa_id AND url = :url",
                ['empresa_id' => $empresaId, 'url' => $noticia['url']]
            );
            if ($existe) { $duplicadas++; continue; }
            $pendentes[] = $noticia;
        }

        if (empty($pendentes)) {
            $this->atualizarLogBusca($logId, true, $noticiasEncontradas, 0, $duplicadas, $apiUtilizada);
            Database::execute(
                "UPDATE fila_busca_noticias SET status = 'concluido', log_id = :log_id, api_utilizada = :api,
                    noticias_encontradas = :encontradas, noticias_duplicadas = :duplicadas,
                    mensagem = 'Busca concluída! Nenhuma notícia nova encontrada.', concluido_em = NOW(), atualizado_em = NOW()
                 WHERE id = :id",
                ['log_id' => $logId, 'api' => $apiUtilizada, 'encontradas' => $noticiasEncontradas, 'duplicadas' => $duplicadas, 'id' => $filaId]
            );
            return ['sucesso' => true, 'concluido' => true, 'mensagem' => 'Busca concluída! Nenhuma notícia nova encontrada.'];
        }

        $mensagem = "Encontradas {$noticiasEncontradas} notícia(s), analisando...";
        Database::execute(
            "UPDATE fila_busca_noticias SET status = 'pendente', etapa = 'analisar', log_id = :log_id, api_utilizada = :api,
                noticias_pendentes = :pendentes, noticias_encontradas = :encontradas, noticias_duplicadas = :duplicadas,
                mensagem = :mensagem, atualizado_em = NOW()
             WHERE id = :id",
            [
                'log_id' => $logId, 'api' => $apiUtilizada,
                'pendentes' => json_encode($pendentes, JSON_UNESCAPED_UNICODE),
                'encontradas' => $noticiasEncontradas, 'duplicadas' => $duplicadas,
                'mensagem' => $mensagem, 'id' => $filaId,
            ]
        );

        return ['sucesso' => true, 'concluido' => false, 'mensagem' => $mensagem];
    }

    /**
     * Etapa 2: analisa (1 chamada de IA) e salva UMA notícia da lista pendente.
     * Chamado repetidamente até a lista esvaziar.
     */
    private function executarEtapaAnalisar(array $pedido): array
    {
        $filaId = (int) $pedido['id'];
        $empresaId = (int) $pedido['empresa_id'];
        $logId = (int) $pedido['log_id'];
        $apiUtilizada = $pedido['api_utilizada'] ?: 'openai';

        $pendentes = json_decode((string) $pedido['noticias_pendentes'], true) ?: [];
        if (empty($pendentes)) {
            return $this->concluirFilaBusca($filaId, $logId, $apiUtilizada, (int) $pedido['noticias_encontradas'], (int) $pedido['noticias_novas'], (int) $pedido['noticias_duplicadas'], $empresaId);
        }

        $noticia = array_shift($pendentes);

        $empresa = Database::queryOne("SELECT segmento, lingua_principal FROM empresas WHERE id = :id", ['id' => $empresaId]);
        $setor = $empresa['segmento'] ?? ($noticia['setor'] ?? 'Geral');
        $lingua = $empresa['lingua_principal'] ?? 'Português';

        $novasNoticias = (int) $pedido['noticias_novas'];
        $analise = $this->gerarAnaliseBlocos($noticia, $setor, $lingua);
        if ($analise) {
            $this->salvarNoticia($empresaId, $noticia, $analise, $apiUtilizada);
            $novasNoticias++;
        } else {
            error_log('[O CONSULTOR][FILA-BUSCA-NOTICIAS] Análise falhou, notícia descartada: ' . ($noticia['titulo'] ?? ''));
        }

        if (empty($pendentes)) {
            return $this->concluirFilaBusca($filaId, $logId, $apiUtilizada, (int) $pedido['noticias_encontradas'], $novasNoticias, (int) $pedido['noticias_duplicadas'], $empresaId);
        }

        $restantes = count($pendentes);
        $mensagem = "Analisando notícias... ({$restantes} restante(s))";
        Database::execute(
            "UPDATE fila_busca_noticias SET status = 'pendente', noticias_pendentes = :pendentes, noticias_novas = :novas, mensagem = :mensagem, atualizado_em = NOW() WHERE id = :id",
            ['pendentes' => json_encode($pendentes, JSON_UNESCAPED_UNICODE), 'novas' => $novasNoticias, 'mensagem' => $mensagem, 'id' => $filaId]
        );

        return ['sucesso' => true, 'concluido' => false, 'mensagem' => $mensagem];
    }

    /**
     * Preenche a imagem de capa (og:image) de notícias já salvas que estão sem
     * imagem. Processa em pequenos lotes para não estourar tempo/limite.
     * Retorna quantas foram atualizadas.
     */
    public function backfillImagensNoticias(int $empresaId, int $limite = 8): int
    {
        try {
            $semImagem = Database::query(
                "SELECT id, url FROM noticias
                 WHERE empresa_id = :empresa_id
                   AND (imagem_url IS NULL OR imagem_url = '')
                   AND url IS NOT NULL AND url <> ''
                 ORDER BY criado_em DESC
                 LIMIT {$limite}",
                ['empresa_id' => $empresaId]
            );
        } catch (\Throwable $e) {
            // Coluna imagem_url pode não existir ainda (migration 035 não rodada).
            return 0;
        }

        $atualizadas = 0;
        foreach ($semImagem as $n) {
            $img = $this->extrairImagemDaPagina((string) $n['url']);
            if ($img !== null) {
                try {
                    Database::execute(
                        "UPDATE noticias SET imagem_url = :img WHERE id = :id",
                        ['img' => $img, 'id' => $n['id']]
                    );
                    $atualizadas++;
                } catch (\Throwable $e) {
                    error_log('[O CONSULTOR][OG-IMAGE-BACKFILL] Falha ao atualizar notícia ' . $n['id'] . ': ' . $e->getMessage());
                }
            }
        }
        return $atualizadas;
    }

    /**
     * Endpoint: preenche imagens faltantes das notícias já salvas (chamado pelo
     * front após carregar o feed, para exibir capas sem precisar refazer a busca).
     */
    public function preencherImagensFaltantes(): void
    {
        Auth::proteger();
        @set_time_limit(90);
        header('Content-Type: application/json');

        $empresaId = Auth::empresa();
        if (!$empresaId) {
            echo json_encode(['sucesso' => false, 'erro' => 'Empresa não identificada.']);
            exit;
        }

        $atualizadas = $this->backfillImagensNoticias($empresaId, 12);
        echo json_encode(['sucesso' => true, 'atualizadas' => $atualizadas]);
        exit;
    }

    private function concluirFilaBusca(int $filaId, int $logId, string $apiUtilizada, int $encontradas, int $novas, int $duplicadas, int $empresaId): array
    {
        $this->atualizarLogBusca($logId, true, $encontradas, $novas, $duplicadas, $apiUtilizada);
        if ($novas > 0) {
            $this->criarAlertaNovasNoticias($empresaId, $novas);
        }
        $mensagem = "Busca concluída! {$novas} notícia(s) nova(s) encontrada(s).";
        Database::execute(
            "UPDATE fila_busca_noticias SET status = 'concluido', noticias_novas = :novas, noticias_pendentes = NULL, mensagem = :mensagem, concluido_em = NOW(), atualizado_em = NOW() WHERE id = :id",
            ['novas' => $novas, 'mensagem' => $mensagem, 'id' => $filaId]
        );
        return ['sucesso' => true, 'concluido' => true, 'mensagem' => $mensagem, 'noticias_novas' => $novas];
    }

    /**
     * Gerar análise de 5 blocos para uma notícia
     */
    public function gerarAnalise(): void
    {
        Auth::proteger();
        
        $input = json_decode(file_get_contents('php://input'), true);
        $noticiaId = (int) ($input['noticia_id'] ?? 0);
        
        if ($noticiaId === 0) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'ID da notícia não informado.']);
            exit;
        }
        
        try {
            // Buscar a notícia
            $noticia = Database::queryOne(
                "SELECT * FROM noticias WHERE id = :id AND empresa_id = :empresa_id",
                ['id' => $noticiaId, 'empresa_id' => Auth::empresa()]
            );
            
            if (!$noticia) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Notícia não encontrada.']);
                exit;
            }
            
            // Verificar se já tem análise
            if (!empty($noticia['analise_blocos'])) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Esta notícia já possui análise.']);
                exit;
            }
            
            // Buscar dados da empresa para contexto
            $empresa = Database::queryOne(
                "SELECT nome, segmento, lingua_principal FROM empresas WHERE id = :id",
                ['id' => Auth::empresa()]
            );
            
            $setor = $empresa['segmento'] ?? 'Geral';
            $lingua = $empresa['lingua_principal'] ?? 'Português';
            
            // Gerar análise via IA (na língua configurada, traduzindo se necessário)
            $analise = $this->gerarAnaliseBlocos($noticia, $setor, $lingua);
            
            if ($analise) {
                // Salvar análise no banco
                Database::execute(
                    "UPDATE noticias SET 
                     analise_blocos = :analise,
                     analisado = 1,
                     data_analise = NOW()
                     WHERE id = :id",
                    [
                        'analise' => json_encode($analise),
                        'id' => $noticiaId
                    ]
                );
                
                Logger::acao('Análise de notícia gerada', [
                    'noticia_id' => $noticiaId,
                    'empresa_id' => Auth::empresa(),
                    'blocos_gerados' => count($analise)
                ]);
                
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => true, 'mensagem' => 'Análise gerada com sucesso!']);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Falha ao gerar análise. Verifique a configuração das APIs.']);
            }
            
        } catch (Exception $e) {
            Logger::error('Erro ao gerar análise: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno ao gerar análise.']);
        }
        exit;
    }

    /**
     * Favoritar/desfavoritar notícia
     */
    public function favoritar(): void
    {
        Auth::proteger();
        
        $input = json_decode(file_get_contents('php://input'), true);
        $noticiaId = (int) ($input['noticia_id'] ?? 0);
        
        if ($noticiaId === 0) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'ID da notícia não informado.']);
            exit;
        }
        
        try {
            // Verificar se a notícia pertence à empresa do usuário
            $noticia = Database::queryOne(
                "SELECT id, favorita FROM noticias WHERE id = :id AND empresa_id = :empresa_id",
                ['id' => $noticiaId, 'empresa_id' => Auth::empresa()]
            );
            
            if (!$noticia) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Notícia não encontrada.']);
                exit;
            }
            
            $novoStatus = !$noticia['favorita'];
            
            Database::execute(
                "UPDATE noticias SET favorita = :favorita WHERE id = :id",
                ['favorita' => $novoStatus, 'id' => $noticiaId]
            );
            
            Logger::acao('Notícia favoritada/desfavoritada', [
                'noticia_id' => $noticiaId,
                'novo_status' => $novoStatus
            ]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'favorita' => $novoStatus,
                'mensagem' => $novoStatus ? 'Adicionada aos favoritos!' : 'Removida dos favoritos!'
            ]);
            
        } catch (Exception $e) {
            Logger::error('Erro ao favoritar notícia: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno.']);
        }
        exit;
    }

    /**
     * Obter perfil de busca da empresa
     */
    public function perfil(): void
    {
        Auth::proteger();
        
        $empresaId = Auth::empresa();
        
        try {
            $sites = Database::query(
                "SELECT site_url FROM empresa_perfil_busca WHERE empresa_id = :empresa_id ORDER BY criado_em ASC",
                ['empresa_id' => $empresaId]
            );
            
            $sitesArray = array_column($sites, 'site_url');
            
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true, 'sites' => $sitesArray]);
            
        } catch (Exception $e) {
            Logger::error('Erro ao buscar perfil: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno.']);
        }
        exit;
    }

    /**
     * Salvar perfil de busca
     */
    public function salvarPerfil(): void
    {
        Auth::proteger();
        
        $input = json_decode(file_get_contents('php://input'), true);
        $sites = $input['sites'] ?? [];
        $empresaId = Auth::empresa();
        
        if (!is_array($sites)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Dados inválidos.']);
            exit;
        }
        
        try {
            // Remover todos os sites atuais
            Database::execute(
                "DELETE FROM empresa_perfil_busca WHERE empresa_id = :empresa_id",
                ['empresa_id' => $empresaId]
            );
            
            // Adicionar novos sites
            foreach ($sites as $site) {
                if (!empty($site) && filter_var($site, FILTER_VALIDATE_URL)) {
                    Database::execute(
                        "INSERT INTO empresa_perfil_busca (empresa_id, site_url, criado_em) VALUES (:empresa_id, :site, NOW())",
                        ['empresa_id' => $empresaId, 'site' => $site]
                    );
                }
            }
            
            Logger::acao('Perfil de busca de notícias atualizado', [
                'empresa_id' => $empresaId,
                'total_sites' => count($sites)
            ]);
            
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true, 'mensagem' => 'Perfil salvo com sucesso!']);
            
        } catch (Exception $e) {
            Logger::error('Erro ao salvar perfil: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno.']);
        }
        exit;
    }

    /**
     * Exibir detalhes de uma notícia
     */
    public function detalhe(): void
    {
        Auth::proteger();
        
        $noticiaId = (int) ($_GET['id'] ?? 0);
        
        if ($noticiaId === 0) {
            Flash::set('erro', 'Notícia não encontrada.');
            header('Location: ' . APP_URL . '/noticias');
            exit;
        }
        
        try {
            // Buscar a notícia
            $noticia = Database::queryOne(
                "SELECT * FROM noticias WHERE id = :id AND empresa_id = :empresa_id",
                ['id' => $noticiaId, 'empresa_id' => Auth::empresa()]
            );
            
            if (!$noticia) {
                Flash::set('erro', 'Notícia não encontrada.');
                header('Location: ' . APP_URL . '/noticias');
                exit;
            }
            
            // Buscar notícias relacionadas (mesma categoria)
            $relacionadas = Database::query(
                "SELECT id, titulo, data_publicacao FROM noticias 
                 WHERE empresa_id = :empresa_id 
                 AND categoria = :categoria 
                 AND id != :noticia_id 
                 ORDER BY data_publicacao DESC 
                 LIMIT 5",
                [
                    'empresa_id' => Auth::empresa(),
                    'categoria' => $noticia['categoria'] ?? '',
                    'noticia_id' => $noticiaId
                ]
            );
            
            $dados = [
                'noticia' => $noticia,
                'relacionadas' => $relacionadas
            ];
            
            require VIEW_PATH . '/noticias/detalhe.php';
            
        } catch (Exception $e) {
            Logger::error('Erro ao carregar detalhes da notícia: ' . $e->getMessage());
            Flash::set('erro', 'Erro ao carregar notícia.');
            header('Location: ' . APP_URL . '/noticias');
            exit;
        }
    }

    /**
     * Exibir lista de notícias
     */
    public function index(): void
    {
        Auth::proteger();
        require VIEW_PATH . '/noticias/index.php';
    }

    /**
     * Painel administrativo de notícias
     */
    public function admin(): void
    {
        Auth::exigirPerfil([Auth::ADMIN_HOLDING, Auth::CONSULTOR_INTERNO]);
        
        try {
            // Estatísticas gerais de notícias
            $stats = Database::query(
                "SELECT 
                    empresa_id,
                    e.nome as empresa_nome,
                    COUNT(*) as total_noticias,
                    COUNT(CASE WHEN visualizada = 0 THEN 1 END) as nao_visualizadas,
                    COUNT(CASE WHEN favorita = 1 THEN 1 END) as favoritas,
                    COUNT(CASE WHEN relevancia = 'alta' THEN 1 END) as alta_relevancia,
                    MAX(criado_em) as ultima_noticia
                FROM noticias n
                JOIN empresas e ON n.empresa_id = e.id
                GROUP BY empresa_id, e.nome
                ORDER BY e.nome"
            );
            
            // Notícias recentes de todas as empresas
            $noticiasRecentes = Database::query(
                "SELECT n.*, e.nome as empresa_nome
                FROM noticias n
                JOIN empresas e ON n.empresa_id = e.id
                ORDER BY n.criado_em DESC
                LIMIT 20"
            );
            
            // Logs de busca
            $logsBusca = Database::query(
                "SELECT bl.*, e.nome as empresa_nome
                FROM busca_logs bl
                JOIN empresas e ON bl.empresa_id = e.id
                ORDER BY bl.criado_em DESC
                LIMIT 10"
            );
            
            $dados = [
                'stats' => $stats,
                'noticias_recentes' => $noticiasRecentes,
                'logs_busca' => $logsBusca
            ];
            
            require VIEW_PATH . '/noticias/admin.php';
            
        } catch (Exception $e) {
            Logger::error('Erro ao carregar admin de notícias: ' . $e->getMessage());
            Flash::set('erro', 'Erro ao carregar dados administrativos.');
            header('Location: ' . APP_URL . '/dashboard');
        }
    }

    /**
     * Arquivar/desarquivar notícia
     */
    public function arquivar(): void
    {
        Auth::proteger();
        
        $noticiaId = (int) ($_POST['noticia_id'] ?? 0);
        $arquivar = (bool) ($_POST['arquivar'] ?? false);
        
        if ($noticiaId === 0) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'ID inválido.']);
            exit;
        }
        
        try {
            // Verificar permissão
            $where = Auth::perfil() === 'ADMIN_HOLDING' ? "id = :id" : "id = :id AND empresa_id = :empresa_id";
            $params = ['id' => $noticiaId];
            if (Auth::perfil() !== 'ADMIN_HOLDING') {
                $params['empresa_id'] = Auth::empresa();
            }
            
            $sucesso = Database::execute(
                "UPDATE noticias SET arquivada = :arquivada WHERE {$where}",
                array_merge($params, ['arquivada' => $arquivar ? 1 : 0])
            );
            
            if ($sucesso) {
                Logger::acao($arquivar ? 'Notícia arquivada' : 'Notícia desarquivada', ['noticia_id' => $noticiaId]);
                header('Content-Type: application/json');
                echo json_encode([
                    'sucesso' => true,
                    'mensagem' => $arquivar ? 'Notícia arquivada!' : 'Notícia desarquivada!'
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Notícia não encontrada.']);
            }
            
        } catch (Exception $e) {
            Logger::error('Erro ao arquivar notícia: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno.']);
        }
        exit;
    }

    /**
     * Executar busca para todas as empresas (cron/admin)
     */
    public function executarBuscaGlobal(): void
    {
        Auth::exigirPerfil([Auth::ADMIN_HOLDING]);
        
        try {
            $resultado = $this->processarTodasEmpresas();
            
            Logger::acao('Busca global de notícias executada', $resultado);
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'resultado' => $resultado
            ]);
            
        } catch (Exception $e) {
            Logger::error('Erro na busca global: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
        }
        exit;
    }
}