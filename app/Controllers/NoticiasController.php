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
                "SELECT setor, lingua_principal FROM empresas WHERE id = :id",
                ['id' => $empresaId]
            );

            if (!$empresa) return false;

            $setor = $empresa['setor'] ?? 'Tecnologia';
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
        $isManual = ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['manual']));
        
        if ($isManual) {
            Auth::proteger();
            $empresaId = Auth::empresa();
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
            "SELECT nome, setor, lingua_principal FROM empresas WHERE id = :id",
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
        $setor = $empresa['setor'] ?? 'Tecnologia';
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

            $noticiasEncontradas = count($noticias ?? []);

            // Processar cada notícia
            foreach ($noticias as $noticia) {
                if (empty($noticia['url']) || empty($noticia['titulo'])) continue;

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

        } catch (Exception $e) {
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
    }

    private function atualizarLogBusca(int $logId, bool $sucesso, int $encontradas, int $novas, int $duplicadas, ?string $api, ?string $erro = null): void
    {
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
    }

    private function criarAlertaNovasNoticias(int $empresaId, int $quantidade): void
    {
        Database::execute(
            "INSERT INTO alertas (empresa_id, tipo, titulo, descricao, prioridade, status) 
             VALUES (:empresa_id, 'novo_conteudo', :titulo, :descricao, 'info', 'ativo')",
            [
                'empresa_id' => $empresaId,
                'titulo' => 'Novas notícias disponíveis',
                'descricao' => "{$quantidade} nova(s) notícia(s) do seu setor foram encontradas e processadas pela IA.",
            ]
        );
    }
}