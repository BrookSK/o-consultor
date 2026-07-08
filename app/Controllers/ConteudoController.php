<?php
/**
 * ConteudoController — Central de Conteúdo
 * Notícias por IA, Academy SSO, Casos Reais, Inteligência de Mercado
 */

class ConteudoController
{
    /** Quantidade de notícias por página no feed. */
    private const NOTICIAS_POR_PAGINA = 12;

    public function index(): void
    {
        Auth::proteger();

        // Para ADMIN_HOLDING em modo "Todas as empresas", Auth::empresa() retorna null.
        // Não usamos Auth::garantirEmpresa() aqui porque ele cai num fallback silencioso
        // para a primeira empresa do banco — o que fazia o admin editar/ver sites de
        // uma empresa diferente da que ele pensava, parecendo que salvar/apagar "não funcionava".
        $empresaId = Auth::empresa();
        if (!$empresaId) {
            Flash::set('erro', 'Selecione uma empresa específica (menu no topo) para gerenciar a Central de Conteúdo.');
            header('Location: ' . APP_URL . '/admin/clientes');
            exit;
        }

        // Paginação: mais nova (página 1) → mais antiga (páginas sequenciais).
        $pagina = max(1, (int) ($_GET['pagina'] ?? 1));

        // Buscar dados reais do banco
        $resultadoNoticias = $this->buscarNoticiasReais($empresaId, $pagina);
        $perfilBusca = $this->buscarPerfilBusca($empresaId);
        
        $dados = [
            'noticias' => $resultadoNoticias['itens'],
            'paginacao' => $resultadoNoticias['paginacao'],
            'casos' => $this->getCasosReais($empresaId), // Casos reais
            'inteligencia' => $this->getInteligenciaReais($empresaId), // Inteligência real
            'perfil_busca' => $perfilBusca,
            'academy_url' => Configuracao::get('academy_url', 'https://myacademy.com.br'),
            'usuario' => Auth::usuario(),
        ];

        require VIEW_PATH . '/conteudo/index.php';
    }

    /**
     * Retorna uma página do feed de notícias em JSON (para navegação de páginas via AJAX).
     */
    public function noticiasPagina(): void
    {
        Auth::proteger();
        header('Content-Type: application/json');

        $empresaId = Auth::empresa();
        if (!$empresaId) {
            echo json_encode(['sucesso' => false, 'erro' => 'Selecione uma empresa específica no menu do topo.']);
            exit;
        }
        $pagina = max(1, (int) ($_GET['pagina'] ?? 1));

        $resultado = $this->buscarNoticiasReais($empresaId, $pagina);

        echo json_encode([
            'sucesso' => true,
            'noticias' => $resultado['itens'],
            'paginacao' => $resultado['paginacao'],
        ]);
        exit;
    }

    public function noticiaDetalhe(): void
    {
        Auth::proteger();
        
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) {
            Flash::set('erro', 'Notícia não encontrada.');
            header('Location: ' . APP_URL . '/central-de-conteudo');
            exit;
        }

        // Buscar notícia real do banco
        $noticia = Database::queryOne(
            "SELECT * FROM noticias 
             WHERE id = :id AND (empresa_id = :empresa_id OR :is_admin = 1)",
            ['id' => $id, 'empresa_id' => Auth::empresa(), 'is_admin' => Auth::isAdmin()]
        );

        if (!$noticia) {
            Flash::set('erro', 'Notícia não encontrada ou sem permissão.');
            header('Location: ' . APP_URL . '/central-de-conteudo');
            exit;
        }

        // Marcar como visualizada
        Database::execute(
            "UPDATE noticias SET visualizada = 1 WHERE id = :id",
            ['id' => $id]
        );

        $dados = ['noticia' => $this->formatarNoticiaDetalhes($noticia)];
        require VIEW_PATH . '/conteudo/noticia-detalhe.php';
    }

    public function casoDetalhe(): void
    {
        Auth::proteger();
        $dados = ['caso' => $this->getCasoRealPorId((int) ($_GET['id'] ?? 1))];
        require VIEW_PATH . '/conteudo/caso-detalhe.php';
    }

    public function salvarPerfilBusca(): void
    {
        Auth::proteger();
        Csrf::verificar();
        header('Content-Type: application/json');

        // Mesmo motivo do index(): não usar garantirEmpresa() aqui, pois ele faria
        // o admin em modo "Todas as empresas" salvar sites na empresa #1 silenciosamente.
        $empresaId = Auth::empresa();
        if (!$empresaId) {
            echo json_encode(['sucesso' => false, 'erro' => 'Selecione uma empresa específica no menu do topo antes de configurar os sites.']);
            exit;
        }

        // Sites de referência enviados pelo usuário (array de URLs, pode ter vazios/inválidos).
        $sitesEnviados = (array) ($_POST['sites'] ?? []);
        $sitesValidos = [];
        foreach ($sitesEnviados as $url) {
            $url = trim((string) $url);
            if ($url === '') continue;
            // Aceita sem protocolo (ex.: "tecmundo.com.br") completando com https://.
            if (!preg_match('#^https?://#i', $url)) {
                $url = 'https://' . $url;
            }
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                $sitesValidos[] = $url;
            }
        }

        try {
            // A lista exibida na tela é a fonte da verdade: substitui TODOS os sites
            // da empresa (inclusive os que a IA adicionou automaticamente), para que
            // o usuário consiga editar/remover qualquer site, não só os que ele mesmo criou.
            Database::execute(
                "DELETE FROM empresa_perfil_busca WHERE empresa_id = :empresa_id",
                ['empresa_id' => $empresaId]
            );

            foreach (array_unique($sitesValidos) as $url) {
                Database::execute(
                    "INSERT INTO empresa_perfil_busca (empresa_id, site_url, ativo, adicionado_por, criado_em)
                     VALUES (:empresa_id, :site_url, 1, 'usuario', NOW())",
                    ['empresa_id' => $empresaId, 'site_url' => $url]
                );
            }

            Logger::acao('Perfil de busca atualizado', ['empresa_id' => $empresaId, 'total_sites' => count($sitesValidos)]);

            echo json_encode(['sucesso' => true, 'mensagem' => 'Perfil de busca salvo!', 'sites' => $sitesValidos]);
        } catch (Exception $e) {
            Logger::error('Erro ao salvar perfil de busca: ' . $e->getMessage());
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao salvar: ' . $e->getMessage()]);
        }
        exit;
    }

    public function buscarAgora(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $empresaId = Auth::empresa();
        if (!$empresaId) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Selecione uma empresa específica no menu do topo antes de buscar notícias.']);
            exit;
        }

        // Verificar se alguma API está configurada
        if (!Configuracao::apiAtiva('perplexity') && !Configuracao::apiAtiva('openai') && !Configuracao::apiAtiva('anthropic')) {
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => false, 
                'erro' => 'Nenhuma API de conteúdo configurada. Configure em Admin > APIs de Conteúdo.'
            ]);
            exit;
        }

        // Usar NoticiasController para processar
        $noticiasController = new NoticiasController();
        
        try {
            // Simular GET request com manual=1
            $_GET['manual'] = '1';
            
            // Capturar output buffer
            ob_start();
            $noticiasController->buscar();
            $response = ob_get_clean();

            $resultado = json_decode($response, true) ?? ['sucesso' => false, 'erro' => 'Resposta inválida da busca.'];

            // Devolver também a lista de notícias atualizada (com imagem/resumo),
            // para o front renderizar os cards inline sem precisar recarregar a página.
            // Após uma busca nova, sempre volta para a página 1 (mais recentes).
            if (!empty($resultado['sucesso'])) {
                $pagina1 = $this->buscarNoticiasReais($empresaId, 1);
                $resultado['noticias'] = $pagina1['itens'];
                $resultado['paginacao'] = $pagina1['paginacao'];
            }

            header('Content-Type: application/json');
            echo json_encode($resultado);
            
        } catch (Exception $e) {
            Logger::error('Erro na busca manual de notícias', [
                'erro' => $e->getMessage(),
                'empresa_id' => $empresaId
            ]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => false,
                'erro' => 'Erro ao buscar notícias. Tente novamente em alguns minutos.'
            ]);
        }
        exit;
    }

    public function admin(): void
    {
        Auth::exigirPerfil([Auth::ADMIN_HOLDING, Auth::CONSULTOR_INTERNO]);
        
        $empresaId = Auth::empresa();
        if (!$empresaId) {
            Flash::set('erro', 'É necessário selecionar uma empresa para gerenciar conteúdo.');
            header('Location: ' . APP_URL . '/admin/clientes');
            exit;
        }
        
        $dados = [
            'noticias' => $this->buscarNoticiasReais($empresaId)['itens'],
            'casos' => $this->getCasosReais($empresaId),
        ];
        require VIEW_PATH . '/conteudo/admin.php';
    }

    /**
     * Criar conteúdo para Máquina de Conteúdo a partir de notícia
     */
    public function criarConteudoDeNoticia(): void
    {
        Auth::proteger();
        Csrf::verificar();
        
        $noticiaId = (int) ($_POST['noticia_id'] ?? 0);
        $marcaId = (int) ($_POST['marca_id'] ?? 0);
        $tipoConteudo = trim($_POST['tipo_conteudo'] ?? 'carrossel');
        
        if ($noticiaId === 0) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'ID da notícia é obrigatório.']);
            exit;
        }
        
        try {
            // Verificar se a notícia existe e pertence à empresa do usuário
            $noticia = Database::queryOne(
                "SELECT * FROM noticias WHERE id = :id AND empresa_id = :empresa_id",
                ['id' => $noticiaId, 'empresa_id' => Auth::empresa()]
            );
            
            if (!$noticia) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Notícia não encontrada.']);
                exit;
            }
            
            // Se marca não fornecida, tentar buscar marca padrão
            if ($marcaId === 0) {
                $marca = Database::queryOne(
                    "SELECT id FROM marcas WHERE ativo = 1 ORDER BY id LIMIT 1"
                );
                $marcaId = $marca['id'] ?? 1;
            }
            
            Logger::acao('Conteúdo criado a partir de notícia', [
                'noticia_id' => $noticiaId,
                'marca_id' => $marcaId,
                'tipo' => $tipoConteudo
            ]);
            
            // Redirecionar para máquina de conteúdo com parâmetros pré-preenchidos
            $redirectUrl = APP_URL . "/maquina-de-conteudo/marca?id={$marcaId}&noticia_id={$noticiaId}&tipo={$tipoConteudo}&tema=" . urlencode($noticia['titulo']);
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Redirecionando para Máquina de Conteúdo...',
                'redirect' => $redirectUrl
            ]);
            
        } catch (Exception $e) {
            Logger::error('Erro ao criar conteúdo de notícia: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno.']);
        }
        exit;
    }

    // ===== REAL DATA METHODS =====

    /**
     * Buscar notícias reais do banco de dados
     */
    /**
     * Busca notícias paginadas: da mais nova (página 1) para a mais antiga
     * (páginas sequenciais 1, 2, 3...). Retorna ['itens' => [...], 'paginacao' => [...]].
     */
    private function buscarNoticiasReais(int $empresaId, int $pagina = 1): array
    {
        $pagina = max(1, $pagina);
        $porPagina = self::NOTICIAS_POR_PAGINA;
        $offset = ($pagina - 1) * $porPagina;

        $total = (int) (Database::queryOne(
            "SELECT COUNT(*) as total FROM noticias WHERE empresa_id = :empresa_id AND arquivada = 0",
            ['empresa_id' => $empresaId]
        )['total'] ?? 0);

        $totalPaginas = max(1, (int) ceil($total / $porPagina));
        // Evita pedir uma página além do fim (ex.: após arquivar itens).
        if ($pagina > $totalPaginas) {
            $pagina = $totalPaginas;
            $offset = ($pagina - 1) * $porPagina;
        }

        try {
            $noticias = Database::query(
                "SELECT id, titulo, url, imagem_url, fonte, data_publicacao as data, categoria, 
                        LEFT(bloco1_noticia, 150) as resumo, relevancia,
                        visualizada, favoritada
                 FROM noticias 
                 WHERE empresa_id = :empresa_id AND arquivada = 0
                 ORDER BY data_publicacao DESC, criado_em DESC 
                 LIMIT {$porPagina} OFFSET {$offset}",
                ['empresa_id' => $empresaId]
            );
        } catch (Exception $e) {
            // Coluna imagem_url pode não existir ainda (migration 035 não rodada).
            $noticias = Database::query(
                "SELECT id, titulo, url, fonte, data_publicacao as data, categoria, 
                        LEFT(bloco1_noticia, 150) as resumo, relevancia,
                        visualizada, favoritada
                 FROM noticias 
                 WHERE empresa_id = :empresa_id AND arquivada = 0
                 ORDER BY data_publicacao DESC, criado_em DESC 
                 LIMIT {$porPagina} OFFSET {$offset}",
                ['empresa_id' => $empresaId]
            );
        }

        // Formatar para compatibilidade com view
        $itens = array_map(function($noticia) {
            return [
                'id' => $noticia['id'],
                'url' => $noticia['url'] ?? null,
                'imagem_url' => $noticia['imagem_url'] ?? null,
                'fonte' => $noticia['fonte'],
                'titulo' => $noticia['titulo'],
                'data' => $noticia['data'],
                'categoria' => $noticia['categoria'],
                'resumo' => $noticia['resumo'] . '...',
                'relevancia' => $noticia['relevancia'],
                'nova' => !$noticia['visualizada'],
                'favoritada' => (bool) $noticia['favoritada'],
            ];
        }, $noticias);

        return [
            'itens' => $itens,
            'paginacao' => [
                'pagina_atual' => $pagina,
                'total_paginas' => $totalPaginas,
                'total_itens' => $total,
                'por_pagina' => $porPagina,
            ],
        ];
    }

    /**
     * Buscar perfil de busca real da empresa
     */
    private function buscarPerfilBusca(int $empresaId): array
    {
        // Buscar dados da empresa
        $empresa = Database::queryOne(
            "SELECT segmento, lingua_principal FROM empresas WHERE id = :id",
            ['id' => $empresaId]
        );

        // Buscar sites de referência
        $sites = Database::query(
            "SELECT site_url FROM empresa_perfil_busca 
             WHERE empresa_id = :empresa_id AND ativo = 1
             ORDER BY adicionado_por DESC, criado_em ASC",
            ['empresa_id' => $empresaId]
        );

        // Buscar último log de busca
        $ultimaBusca = Database::queryOne(
            "SELECT criado_em FROM busca_logs 
             WHERE empresa_id = :empresa_id 
             ORDER BY criado_em DESC LIMIT 1",
            ['empresa_id' => $empresaId]
        );

        return [
            'setor' => $empresa['segmento'] ?? 'Tecnologia',
            'lingua' => $empresa['lingua_principal'] ?? 'Português',
            'sites' => array_column($sites, 'site_url'),
        ];

        // Buscar palavras-chave e configurações reais da empresa
        try {
            $configBusca = Database::queryOne(
                "SELECT palavras_chave, frequencia_busca FROM configuracoes_conteudo WHERE empresa_id = :empresa_id",
                ['empresa_id' => $empresa['id']]
            );
            
            $palavrasChave = $configBusca ? explode(',', $configBusca['palavras_chave']) : ['IA empresarial', 'automação', 'produtividade'];
            $frequencia = $configBusca['frequencia_busca'] ?? 'diaria';
        } catch (Exception $e) {
            $palavrasChave = ['IA empresarial', 'automação', 'produtividade'];
            $frequencia = 'diaria';
        }

        $dados['perfil_busca']['palavras_chave'] = $palavrasChave;
        $dados['perfil_busca']['frequencia'] = $frequencia;
        $dados['perfil_busca']['ultimo_update'] = $ultimaBusca['criado_em'] ?? date('Y-m-d H:i:s');
    }

    /**
     * Formatar notícia para visualização detalhada
     */
    private function formatarNoticiaDetalhes(array $noticia): array
    {
        return [
            'id' => $noticia['id'],
            'fonte' => $noticia['fonte'],
            'titulo' => $noticia['titulo'],
            'data' => $noticia['data_publicacao'],
            'categoria' => $noticia['categoria'],
            'relevancia' => $noticia['relevancia'],
            'bloco_noticia' => $noticia['bloco1_noticia'],
            'bloco_significa' => $noticia['bloco2_significa'],
            'bloco_fazer' => $noticia['bloco3_o_que_fazer'],
            'bloco_pergunta' => $noticia['bloco4_pergunta'],
            'bloco_conexao' => $noticia['bloco5_conexao'],
            'tags' => json_decode($noticia['tags'] ?? '[]', true),
            'url' => $noticia['url'],
        ];
    }

    // ===== REAL DATA IMPLEMENTATIONS =====

    /**
     * Buscar casos reais do sistema
     */
    private function getCasosReais(int $empresaId): array
    {
        // Por enquanto, retorna empty array
        // Em versões futuras, implementar sistema de casos de sucesso
        return [];
    }

    /**
     * Buscar caso real por ID
     */
    private function getCasoRealPorId(int $casoId): array
    {
        // Por enquanto, retorna empty array
        // Em versões futuras, buscar caso específico do banco
        return ['titulo' => 'Caso não encontrado', 'setor' => 'N/A', 'problema' => 'Sistema de casos em desenvolvimento'];
    }

    /**
     * Buscar inteligência de mercado real
     */
    private function getInteligenciaReais(int $empresaId): array
    {
        // Por enquanto, retorna empty array
        // Em versões futuras, implementar análise de tendências baseada em dados reais
        return [];
    }
}
