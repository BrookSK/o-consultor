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
            "SELECT nome, segmento, lingua_principal FROM empresas WHERE id = :id",
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

        // Iniciar log de busca
        $logId = $this->criarLogBusca($empresaId, $isManual ? 'manual' : 'automatica', $sitesArray);

        $noticiasNovas = 0;
        $noticiasEncontradas = 0;
        $noticiasDuplicadas = 0;
        $apiUtilizada = null;

        try {
            // Tentar Perplexity primeiro
            if (Configuracao::apiAtiva('perplexity')) {
                $apiUtilizada = 'perplexity';
                $prompt = ApiHelper::buildPromptBuscaNoticias($setor, $lingua, $sitesArray);
                $resultado = ApiHelper::chamarPerplexity($prompt);

                if ($resultado['sucesso'] && is_array($resultado['conteudo'])) {
                    $noticias = $resultado['conteudo'];
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

                // Gerar análise dos 5 blocos
                $analise = $this->gerarAnaliseBlocos($noticia, $setor);

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
    private function gerarAnaliseBlocos(array $noticia, string $setor): ?array
    {
        try {
            $prompt = ApiHelper::buildPromptAnaliseNoticia(
                $setor,
                $noticia['titulo'],
                $noticia['resumo_bruto'] ?? $noticia['titulo']
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
     * Salvar notícia no banco de dados
     */
    private function salvarNoticia(int $empresaId, array $noticia, array $analise, string $apiUtilizada): bool
    {
        // imagem_url é opcional (a IA pode não encontrar uma imagem de capa).
        $imagemUrl = trim((string) ($noticia['imagem_url'] ?? ''));
        $imagemUrl = filter_var($imagemUrl, FILTER_VALIDATE_URL) ? $imagemUrl : null;

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
                    'titulo' => $noticia['titulo'],
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
                    'resumo_bruto' => $noticia['resumo_bruto'] ?? null,
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
                        'titulo' => $noticia['titulo'],
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
                        'resumo_bruto' => $noticia['resumo_bruto'] ?? null,
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
     * Buscar notícias agora (manual)
     */
    public function buscarAgora(): void
    {
        Auth::proteger();
        
        $input = json_decode(file_get_contents('php://input'), true);
        $empresaId = Auth::empresa();
        
        if (!$empresaId) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Empresa não identificada.']);
            exit;
        }
        
        try {
            // Contar notícias antes da busca
            $noticiasAntes = Database::queryOne(
                "SELECT COUNT(*) as total FROM noticias WHERE empresa_id = :empresa_id",
                ['empresa_id' => $empresaId]
            )['total'] ?? 0;
            
            // Executar busca
            $resultado = $this->processarEmpresa($empresaId, true);
            
            // Contar notícias depois da busca
            $noticiasDepois = Database::queryOne(
                "SELECT COUNT(*) as total FROM noticias WHERE empresa_id = :empresa_id",
                ['empresa_id' => $empresaId]
            )['total'] ?? 0;
            
            $novasNoticias = $noticiasDepois - $noticiasAntes;
            
            Logger::acao('Busca manual de notícias executada', [
                'empresa_id' => $empresaId,
                'noticias_encontradas' => $novasNoticias,
                'resultado' => $resultado
            ]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'novas_noticias' => $novasNoticias,
                'mensagem' => $novasNoticias > 0 
                    ? "Encontradas {$novasNoticias} novas notícias!" 
                    : 'Nenhuma nova notícia encontrada.'
            ]);
            
        } catch (\Throwable $e) {
            // Log detalhado no error_log do Plesk para diagnóstico.
            error_log('[O CONSULTOR][BUSCAR-NOTICIAS] ' . get_class($e) . ': ' . $e->getMessage()
                . ' | ' . $e->getFile() . ':' . $e->getLine()
                . ' | empresa=' . $empresaId
                . "\n" . $e->getTraceAsString());
            Logger::error('Erro na busca manual de notícias', [
                'tipo' => get_class($e),
                'mensagem' => $e->getMessage(),
                'arquivo' => $e->getFile(),
                'linha' => $e->getLine(),
                'empresa_id' => $empresaId,
            ]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao executar busca: ' . $e->getMessage()]);
        }
        exit;
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
                "SELECT nome, segmento FROM empresas WHERE id = :id",
                ['id' => Auth::empresa()]
            );
            
            $setor = $empresa['segmento'] ?? 'Geral';
            
            // Gerar análise via IA
            $analise = $this->gerarAnaliseBlocos($noticia, $setor);
            
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