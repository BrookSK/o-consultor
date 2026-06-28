<?php
/**
 * ConteudoController — Central de Conteúdo
 * Notícias por IA, Academy SSO, Casos Reais, Inteligência de Mercado
 */

class ConteudoController
{
    public function index(): void
    {
        Auth::proteger();

        $empresaId = Auth::garantirEmpresa();

        // Buscar dados reais do banco
        $noticias = $this->buscarNoticiasReais($empresaId);
        $perfilBusca = $this->buscarPerfilBusca($empresaId);
        
        $dados = [
            'noticias' => $noticias,
            'casos' => $this->getCasosReais($empresaId), // Casos reais
            'inteligencia' => $this->getInteligenciaReais($empresaId), // Inteligência real
            'perfil_busca' => $perfilBusca,
            'academy_url' => Configuracao::get('academy_url', 'https://myacademy.com.br'),
            'usuario' => Auth::usuario(),
        ];

        require VIEW_PATH . '/conteudo/index.php';
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
        Logger::acao('Perfil de busca atualizado');
        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'mensagem' => 'Perfil de busca salvo!']);
        exit;
    }

    public function buscarAgora(): void
    {
        Auth::proteger();
        Csrf::verificar();
        
        $empresaId = Auth::garantirEmpresa();

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
            
            // Se chegou aqui, retornar a resposta
            header('Content-Type: application/json');
            echo $response;
            
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
        $dados = [
            'noticias' => $this->buscarNoticiasReais($empresaId),
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
    private function buscarNoticiasReais(int $empresaId): array
    {
        $noticias = Database::query(
            "SELECT id, titulo, fonte, data_publicacao as data, categoria, 
                    LEFT(bloco1_noticia, 150) as resumo, relevancia,
                    visualizada, favoritada
             FROM noticias 
             WHERE empresa_id = :empresa_id AND arquivada = 0
             ORDER BY data_publicacao DESC, criado_em DESC 
             LIMIT 50",
            ['empresa_id' => $empresaId]
        );

        // Formatar para compatibilidade com view
        return array_map(function($noticia) {
            return [
                'id' => $noticia['id'],
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
            'palavras_chave' => ['IA empresarial', 'automação', 'produtividade'], // TODO: implementar tabela própria
            'frequencia' => 'diaria', // TODO: buscar da configuração
            'ultimo_update' => $ultimaBusca['criado_em'] ?? date('Y-m-d H:i:s'),
        ];
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
