<?php
/**
 * MaquinaController — Máquina de Conteúdo com geração de imagens
 * Geração de carrosséis, posts e stories com IA (GPT + DALL-E)
 */

class MaquinaController
{
    public function index(): void
    {
        Auth::proteger();
        
        // Carregar marcas do banco (com fallback para mock)
        try {
            $marcas = Database::query(
                "SELECT m.*, e.nome as empresa_nome 
                 FROM marcas m 
                 LEFT JOIN empresas e ON m.empresa_id = e.id 
                 WHERE m.ativo = 1 
                 ORDER BY m.nome ASC"
            );
            
            // Se não tem marcas, retorna empty array para permitir criação
            if (empty($marcas)) {
                $marcas = [];
            }
            
        } catch (\Exception $e) {
            // Tabela pode não existir - retornar empty array
            $marcas = [];
        }
        
        $dados = ['marcas' => $marcas];
        require VIEW_PATH . '/maquina/index.php';
    }

    public function novaMarca(): void
    {
        Auth::proteger();
        Auth::exigirPerfil([Auth::ADMIN_HOLDING, Auth::CONSULTOR_INTERNO]);
        
        // Para ADMIN_HOLDING, buscar empresas disponíveis
        $empresasDisponiveis = [];
        if (Auth::perfil() === 'ADMIN_HOLDING') {
            try {
                $empresasDisponiveis = Database::query(
                    "SELECT id, nome, segmento, responsavel_id 
                     FROM empresas 
                     WHERE ativo = 1
                     ORDER BY nome ASC"
                );
            } catch (Exception $e) {
                Logger::warning('Erro ao carregar empresas para nova marca', ['erro' => $e->getMessage()]);
                $empresasDisponiveis = [];
            }
        }
        
        // Pré-carrega os dados da empresa atual para o wizard preencher ao abrir.
        // Para ADMIN_HOLDING, usa a empresa selecionada na barra do topo (se houver);
        // para CLIENTE/CONSULTOR, usa a empresa fixa da sessão.
        $dadosPreenchimento = null;
        $empresaAtual = Auth::empresa(); // ADMIN: empresa do topo (ou null); demais: empresa fixa
        if ($empresaAtual) {
            $dadosPreenchimento = $this->montarDadosEmpresa((int) $empresaAtual);
        }

        $dados = [
            'empresas_disponiveis' => $empresasDisponiveis,
            'dados_preenchimento' => $dadosPreenchimento,
        ];
        require VIEW_PATH . '/maquina/nova-marca.php';
    }

    /**
     * Endpoint JSON: retorna todos os dados conhecidos de uma empresa (cadastro,
     * diagnóstico, central de conteúdo e marca já cadastrada) para pré-preencher
     * o wizard de nova marca.
     */
    public function dadosEmpresa(): void
    {
        Auth::proteger();
        header('Content-Type: application/json');

        $empresaId = (int) ($_GET['empresa_id'] ?? 0);

        // Cliente/consultor só pode puxar dados da própria empresa.
        if (Auth::perfil() !== 'ADMIN_HOLDING') {
            $empresaId = (int) Auth::empresa();
        }

        if (!$empresaId) {
            echo json_encode(['sucesso' => false, 'erro' => 'Empresa não informada.']);
            exit;
        }

        $dados = $this->montarDadosEmpresa($empresaId);
        if ($dados === null) {
            echo json_encode(['sucesso' => false, 'erro' => 'Empresa não encontrada.']);
            exit;
        }

        echo json_encode(['sucesso' => true, 'dados' => $dados]);
        exit;
    }

    /**
     * Limpa o cache da descrição de estilo dos templates de uma marca.
     * Chamado quando um template é adicionado/removido.
     */
    private function invalidarEstiloTemplates(int $marcaId): void
    {
        try {
            Database::execute("UPDATE marcas SET templates_estilo = NULL WHERE id = :id", ['id' => $marcaId]);
        } catch (\Throwable $e) {
            // Coluna pode não existir ainda (migration 041); ignora.
        }
    }

    /**
     * Limpa o texto de um slide: remove emojis e símbolos (que quebram o layout
     * e às vezes corrompem no encoding), normaliza espaços. Mantém acentos e
     * pontuação comum do português.
     */
    private function limparTextoSlide(string $texto): string
    {
        // Remove emojis e pictogramas (blocos Unicode de símbolos/emoji).
        $texto = preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2190}-\x{21FF}\x{2B00}-\x{2BFF}\x{FE00}-\x{FE0F}\x{1F1E6}-\x{1F1FF}]/u', '', $texto);
        // Remove caracteres de controle.
        $texto = preg_replace('/[\x{0000}-\x{001F}\x{007F}]/u', ' ', $texto);
        // Colapsa espaços.
        $texto = preg_replace('/[ \t]+/', ' ', (string) $texto);
        return trim((string) $texto);
    }

    /**
     * Retorna os caminhos ABSOLUTOS (no disco) dos templates de uma marca,
     * para serem enviados como imagens de referência ao gpt-image-1.
     */
    private function caminhosTemplatesLocais(int $marcaId, int $limite = 4): array
    {
        try {
            $templates = Database::query(
                "SELECT caminho FROM marca_templates WHERE marca_id = :id ORDER BY criado_em DESC LIMIT {$limite}",
                ['id' => $marcaId]
            );
        } catch (\Throwable $e) {
            return [];
        }
        $caminhos = [];
        foreach ($templates as $t) {
            $rel = (string) ($t['caminho'] ?? '');
            if ($rel === '') continue;
            $abs = (defined('PUBLIC_PATH') ? PUBLIC_PATH : '') . $rel;
            if (is_file($abs)) {
                $caminhos[] = $abs;
            }
        }
        return $caminhos;
    }

    /**
     * Tamanho da imagem conforme o tipo/rede. Instagram feed e story usam
     * formato vertical (retrato). Os tamanhos válidos da API são 1024x1024,
     * 1024x1792 (retrato) e 1792x1024 (paisagem).
     */
    private function tamanhoImagemPorTipo(string $tipo): string
    {
        switch ($tipo) {
            case 'story':
            case 'reels':
            case 'carrossel':
            case 'post':
                return '1024x1792'; // vertical (feed/story moderno do Instagram)
            default:
                return '1024x1792';
        }
    }

    /**
     * Retorna a descrição do estilo visual dos templates da marca (para guiar o
     * DALL-E). Usa cache em marcas.templates_estilo; se vazio e houver templates,
     * analisa as imagens via visão (GPT-4o) e persiste o resultado.
     * Retorna string vazia se não houver templates ou não for possível analisar.
     */
    private function obterEstiloTemplates(int $marcaId): string
    {
        // 1) Cache existente (coluna templates_estilo pode não existir ainda —
        //    nesse caso apenas seguimos para analisar os templates).
        $temColunaCache = true;
        try {
            $row = Database::queryOne("SELECT templates_estilo FROM marcas WHERE id = :id", ['id' => $marcaId]);
            if ($row && !empty(trim((string) ($row['templates_estilo'] ?? '')))) {
                return (string) $row['templates_estilo'];
            }
        } catch (\Throwable $e) {
            $temColunaCache = false; // coluna não existe; segue sem cache
        }

        // 2) Buscar templates da marca.
        try {
            $templates = Database::query(
                "SELECT caminho FROM marca_templates WHERE marca_id = :id ORDER BY criado_em DESC LIMIT 6",
                ['id' => $marcaId]
            );
        } catch (\Throwable $e) {
            return '';
        }
        if (empty($templates)) {
            return '';
        }

        // 3) Montar URLs públicas absolutas e analisar via visão.
        $urls = [];
        foreach ($templates as $t) {
            if (!empty($t['caminho'])) {
                $urls[] = APP_URL . $t['caminho'];
            }
        }

        $res = ApiHelper::descreverEstiloTemplates($urls);
        if (!$res['sucesso'] || $res['estilo'] === '') {
            return '';
        }

        // 4) Persistir cache (só se a coluna existir).
        if ($temColunaCache) {
            try {
                Database::execute(
                    "UPDATE marcas SET templates_estilo = :estilo WHERE id = :id",
                    ['estilo' => $res['estilo'], 'id' => $marcaId]
                );
            } catch (\Throwable $e) { /* segue sem cache */ }
        }

        return $res['estilo'];
    }

    /**
     * Retorna a lista de nomes de colunas existentes numa tabela.
     * Usado para montar INSERT/UPDATE só com colunas que realmente existem
     * (evita erro 1054 em bancos com schema desatualizado).
     */
    private function colunasDaTabela(string $tabela): array
    {
        try {
            $rows = Database::query(
                "SELECT COLUMN_NAME FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t",
                ['t' => $tabela]
            );
            return array_column($rows, 'COLUMN_NAME');
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Agrega os dados da empresa vindos de: tabela empresas, último diagnóstico
     * concluído, perfil de busca (central de conteúdo) e marca já cadastrada.
     * Retorna um array pronto para o front preencher os campos do wizard.
     */
    private function montarDadosEmpresa(int $empresaId): ?array
    {
        $empresa = Database::queryOne(
            "SELECT * FROM empresas WHERE id = :id",
            ['id' => $empresaId]
        );
        if (!$empresa) {
            return null;
        }

        // Base a partir do cadastro da empresa.
        $out = [
            'empresa_id'   => $empresaId,
            'empresa_nome' => $empresa['nome'] ?? '',
            'nome'         => $empresa['nome'] ?? '',
            'nicho'        => $empresa['segmento'] ?? '',
            'publico'      => '',
            'produtos'     => '',
            'diferenciais' => '',
            'concorrentes' => '',
            'sites'        => [],
            'instrucoes'   => '',
            'tem_marca'    => false,
        ];

        // Enriquecer com o último diagnóstico concluído (respostas JSON).
        try {
            $diag = Database::queryOne(
                "SELECT respostas FROM diagnosticos
                 WHERE empresa_id = :id AND status = 'concluido'
                 ORDER BY criado_em DESC LIMIT 1",
                ['id' => $empresaId]
            );
            if ($diag && !empty($diag['respostas'])) {
                $r = json_decode($diag['respostas'], true) ?: [];
                $out['produtos']     = $out['produtos']     ?: (string) ($r['produtos_servicos'] ?? '');
                $out['nicho']        = $out['nicho']        ?: (string) ($r['setor'] ?? '');
                $out['diferenciais'] = $out['diferenciais'] ?: (string) ($r['pontos_fortes'] ?? '');
                $out['publico']      = $out['publico']      ?: (string) ($r['publico_alvo'] ?? ($r['clientes_ativos'] ?? ''));
            }
        } catch (\Throwable $e) {
            // diagnóstico é opcional; ignora.
        }

        // Enriquecer com a Central de Conteúdo (sites de referência + instruções).
        try {
            $sites = Database::query(
                "SELECT site_url FROM empresa_perfil_busca WHERE empresa_id = :id AND ativo = 1 ORDER BY criado_em ASC",
                ['id' => $empresaId]
            );
            $out['sites'] = array_column($sites, 'site_url');
        } catch (\Throwable $e) { /* opcional */ }

        try {
            $inst = Database::queryOne(
                "SELECT instrucoes_busca_noticias FROM empresas WHERE id = :id",
                ['id' => $empresaId]
            );
            $out['instrucoes'] = (string) ($inst['instrucoes_busca_noticias'] ?? '');
        } catch (\Throwable $e) { /* coluna pode não existir */ }

        // Se já existe uma marca cadastrada para a empresa, usar como base (preenche tudo).
        try {
            $marca = Database::queryOne(
                "SELECT * FROM marcas WHERE empresa_id = :id AND ativo = 1 ORDER BY id DESC LIMIT 1",
                ['id' => $empresaId]
            );
            if ($marca) {
                $out['tem_marca'] = true;
                $out['nome']         = $marca['nome'] ?: $out['nome'];
                $out['nicho']        = $marca['nicho'] ?: $out['nicho'];
                $out['publico']      = $marca['publico_alvo'] ?: $out['publico'];
                $out['produtos']     = $marca['produtos_servicos'] ?: $out['produtos'];
                $out['diferenciais'] = $marca['diferenciais_competitivos'] ?: $out['diferenciais'];
                $out['concorrentes'] = $marca['concorrentes'] ?: $out['concorrentes'];
                $out['tom']          = $marca['tom'] ?? '';
                $out['arquetipo']    = $marca['arquetipo'] ?? '';
                $out['palavras_usa'] = $marca['palavras_usa'] ?? '';
                $out['palavras_nunca'] = $marca['palavras_nunca'] ?? '';
                $out['fonte_principal'] = $marca['fonte_principal'] ?? '';
                $out['fonte_secundaria'] = $marca['fonte_secundaria'] ?? '';
                $out['estilo_visual'] = $marca['estilo_visual'] ?? '';
                $out['direcao_foto'] = $marca['direcao_foto'] ?? '';
                $out['objetivos']    = json_decode($marca['objetivos_conteudo'] ?? '[]', true) ?: [];
                $out['formatos']     = json_decode($marca['formatos_preferenciais'] ?? '[]', true) ?: [];
                $out['paleta']       = json_decode($marca['paleta_cores'] ?? '[]', true) ?: [];
                $out['prompt_master'] = $marca['prompt_master'] ?? '';
            }
        } catch (\Throwable $e) { /* marca é opcional */ }

        return $out;
    }

    /**
     * Excluir (desativar) uma marca da empresa.
     */
    public function excluirMarca(): void
    {
        Auth::proteger();
        Auth::exigirPerfil([Auth::ADMIN_HOLDING, Auth::CONSULTOR_INTERNO]);
        Csrf::verificar();
        header('Content-Type: application/json');

        $marcaId = (int) ($_POST['marca_id'] ?? 0);
        if (!$marcaId) {
            echo json_encode(['sucesso' => false, 'erro' => 'ID da marca é obrigatório.']);
            exit;
        }

        try {
            // ADMIN_HOLDING pode excluir qualquer marca; demais só da própria empresa.
            if (Auth::perfil() === 'ADMIN_HOLDING') {
                $where = 'id = :id';
                $params = ['id' => $marcaId];
            } else {
                $where = 'id = :id AND empresa_id = :empresa_id';
                $params = ['id' => $marcaId, 'empresa_id' => (int) Auth::empresa()];
            }

            $sucesso = Database::execute(
                "UPDATE marcas SET ativo = 0 WHERE {$where}",
                $params
            );

            if ($sucesso) {
                Logger::acao('Marca excluída', ['marca_id' => $marcaId]);
                echo json_encode(['sucesso' => true, 'mensagem' => 'Marca excluída!']);
            } else {
                echo json_encode(['sucesso' => false, 'erro' => 'Marca não encontrada.']);
            }
        } catch (Exception $e) {
            Logger::error('Erro ao excluir marca: ' . $e->getMessage());
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao excluir marca.']);
        }
        exit;
    }

    public function salvarMarca(): void
    {
        Auth::proteger();
        Csrf::verificar();

        // ADMIN_HOLDING escolhe a empresa no wizard; os demais usam a empresa da sessão.
        if (Auth::perfil() === 'ADMIN_HOLDING') {
            $empresaId = (int) ($_POST['empresa_id'] ?? 0) ?: (int) Auth::empresa();
        } else {
            $empresaId = (int) Auth::empresa();
        }

        if (!$empresaId) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Selecione a empresa/cliente para associar a marca.']);
            exit;
        }

        // Coletar dados do formulário
        $dadosMarca = [
            'empresa_id' => $empresaId,
            'nome' => htmlspecialchars(trim($_POST['nome'] ?? '')),
            'nicho' => htmlspecialchars(trim($_POST['nicho'] ?? '')),
            'publico_alvo' => htmlspecialchars(trim($_POST['publico'] ?? '')),
            'produtos_servicos' => htmlspecialchars(trim($_POST['produtos'] ?? '')),
            'diferenciais_competitivos' => htmlspecialchars(trim($_POST['diferenciais'] ?? '')),
            'concorrentes' => htmlspecialchars(trim($_POST['concorrentes'] ?? '')),
            'objetivos_conteudo' => json_encode($_POST['objetivos'] ?? []),
            'tom' => htmlspecialchars(trim($_POST['tom'] ?? '')),
            'arquetipo' => htmlspecialchars(trim($_POST['arquetipo'] ?? '')),
            'palavras_usa' => htmlspecialchars(trim($_POST['palavras_usa'] ?? '')),
            'palavras_nunca' => htmlspecialchars(trim($_POST['palavras_nunca'] ?? '')),
            'formatos_preferenciais' => json_encode($_POST['formatos'] ?? []),
            'paleta_cores' => json_encode($_POST['paleta'] ?? []),
            'fonte_principal' => htmlspecialchars(trim($_POST['fonte_principal'] ?? '')),
            'fonte_secundaria' => htmlspecialchars(trim($_POST['fonte_secundaria'] ?? '')),
            'estilo_visual' => htmlspecialchars(trim($_POST['estilo_visual'] ?? '')),
            'direcao_foto' => htmlspecialchars(trim($_POST['direcao_foto'] ?? '')),
            'prompt_master' => htmlspecialchars(trim($_POST['prompt_master'] ?? '')),
            'prompt_dalle' => htmlspecialchars(trim($_POST['prompt_dalle'] ?? ''))
        ];

        if (empty($dadosMarca['nome'])) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Nome da marca é obrigatório']);
            exit;
        }

        // Campos extras que não vêm de $_POST mas devem ser gravados.
        $dadosMarca['brand_book_criado'] = 1;

        try {
            // Monta o INSERT dinamicamente apenas com as colunas que EXISTEM na
            // tabela. Em ambientes onde a `marcas` foi criada por uma versão antiga
            // (sem produtos_servicos etc.), isso evita o erro 1054 e salva o que dá.
            $colunasExistentes = $this->colunasDaTabela('marcas');
            $dadosFiltrados = array_intersect_key($dadosMarca, array_flip($colunasExistentes));

            if (empty($dadosFiltrados['empresa_id']) || empty($dadosFiltrados['nome'])) {
                // Garantia mínima (empresa_id e nome devem sempre existir na tabela).
                $dadosFiltrados['empresa_id'] = $empresaId;
                $dadosFiltrados['nome'] = $dadosMarca['nome'];
            }

            $colunas = array_keys($dadosFiltrados);
            $placeholders = array_map(fn($c) => ':' . $c, $colunas);
            $listaColunas = implode(', ', $colunas);
            $listaValores = implode(', ', $placeholders);
            // criado_em, se a coluna existir.
            $sufixoCriadoEm = in_array('criado_em', $colunasExistentes, true) ? ', criado_em' : '';
            $sufixoCriadoEmVal = in_array('criado_em', $colunasExistentes, true) ? ', NOW()' : '';

            Database::execute(
                "INSERT INTO marcas ({$listaColunas}{$sufixoCriadoEm}) VALUES ({$listaValores}{$sufixoCriadoEmVal})",
                $dadosFiltrados
            );

            $marcaId = Database::lastInsertId();

            Logger::acao('Nova marca cadastrada com integração', [
                'nome' => $dadosMarca['nome'],
                'empresa_id' => $empresaId,
                'marca_id' => $marcaId
            ]);

            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true, 
                'mensagem' => 'Marca cadastrada com sucesso!', 
                'marca_id' => $marcaId,
                'redirect' => APP_URL . '/maquina-de-conteudo/marca?id=' . $marcaId
            ]);

        } catch (Exception $e) {
            Logger::error('Erro ao salvar marca: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao salvar marca: ' . $e->getMessage()]);
        }

        exit;
    }

    public function marca(): void
    {
        Auth::proteger();
        $marcaId = (int) ($_GET['id'] ?? 1);
        
        // Carregar dados da marca
        $marca = $this->getMarcaPorId($marcaId);
        if (!$marca) {
            // Redirect se marca não existir
            header('Location: ' . APP_URL . '/maquina-de-conteudo');
            exit;
        }
        
        // Carregar conteúdos da marca
        $conteudos = [];
        try {
            $conteudos = Database::query(
                "SELECT id, tipo, tema as titulo, status, criado_em as data, 
                        JSON_LENGTH(slides) as slides
                 FROM conteudos_marca 
                 WHERE marca_id = :marca_id AND usuario_id = :user_id 
                 ORDER BY criado_em DESC 
                 LIMIT 10",
                ['marca_id' => $marcaId, 'user_id' => Auth::id()]
            );
        } catch (\Exception $e) {
            // Tabela pode não existir - usar array vazio
            $conteudos = [];
        }
        
        // Carregar notícias disponíveis para usar como base.
        // Usa a empresa da própria marca (funciona para ADMIN e cliente) e a
        // coluna correta de data (criado_em — não existe "created_at" aqui).
        $noticias = [];
        try {
            $noticias = Database::query(
                "SELECT id, titulo FROM noticias
                 WHERE empresa_id = :empresa_id AND arquivada = 0
                 ORDER BY data_publicacao DESC, criado_em DESC
                 LIMIT 30",
                ['empresa_id' => (int) ($marca['empresa_id'] ?? 0)]
            );
        } catch (\Exception $e) {
            // Fallback sem a coluna arquivada, caso o schema seja mais antigo.
            try {
                $noticias = Database::query(
                    "SELECT id, titulo FROM noticias WHERE empresa_id = :empresa_id ORDER BY criado_em DESC LIMIT 30",
                    ['empresa_id' => (int) ($marca['empresa_id'] ?? 0)]
                );
            } catch (\Exception $e2) {
                $noticias = [];
            }
        }
        
        // Documentos da Biblioteca (base de literatura) da empresa da marca,
        // para gerar conteúdo educativo via RAG.
        $biblioteca = [];
        try {
            require_once APP_PATH . '/Controllers/ConteudoController.php';
            $biblioteca = ConteudoController::obterLiteraturaBiblioteca((int) ($marca['empresa_id'] ?? 0));
            // Só expõe id/nome para a view (o conteúdo é usado no backend na geração).
            $biblioteca = array_map(fn($d) => ['id' => $d['id'], 'nome' => $d['nome']], $biblioteca);
        } catch (\Throwable $e) {
            $biblioteca = [];
        }

        $dados = [
            'marca' => $marca,
            'conteudos' => $conteudos,
            'noticias' => $noticias,
            'biblioteca' => $biblioteca,
        ];
        require VIEW_PATH . '/maquina/marca.php';
    }

    public function gerar(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $marcaId = (int) ($_POST['marca_id'] ?? 1);
        $tipo = htmlspecialchars(trim($_POST['tipo'] ?? 'carrossel'));
        // Quantidade de slides do carrossel (3 a 10). Só se aplica a carrossel.
        $qtdSlides = (int) ($_POST['qtd_slides'] ?? 7);
        $qtdSlides = max(3, min(10, $qtdSlides ?: 7));
        $tema = htmlspecialchars(trim($_POST['tema'] ?? ''));
        $objetivo = htmlspecialchars(trim($_POST['objetivo'] ?? 'educar'));
        $estiloImagem = htmlspecialchars(trim($_POST['estilo_imagem'] ?? 'ia'));
        $noticiaId = (int) ($_POST['noticia_id'] ?? 0);
        // Fonte do conteúdo: 'tema' (livre), 'noticia' ou 'biblioteca' (RAG na literatura).
        $fonte = htmlspecialchars(trim($_POST['fonte'] ?? ($noticiaId > 0 ? 'noticia' : 'tema')));
        $bibliotecaIds = array_map('intval', (array) ($_POST['biblioteca_ids'] ?? []));

        if (empty($tema)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Informe o tema do conteúdo.']);
            exit;
        }

        // 1. Carregar dados da marca
        $marca = $this->getMarcaPorId($marcaId);
        if (!$marca) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Marca não encontrada.']);
            exit;
        }

        // 1.5. Carregar dados contextuais da jornada do cliente.
        // Usa a empresa da própria marca (funciona para ADMIN e cliente).
        $contextoJornada = [];
        $empresaId = (int) ($marca['empresa_id'] ?? 0) ?: (int) Auth::empresa();
        if ($empresaId) {
            try {
                require_once APP_PATH . '/Helpers/JornadaCliente.php';
                $contextoJornada = JornadaCliente::extrairDadosContextuais($empresaId);
            } catch (\Throwable $e) {
                // Contexto da jornada é opcional; não deve impedir a geração.
                error_log('[O CONSULTOR][GERAR] Falha ao extrair jornada (ignorada): ' . $e->getMessage());
                $contextoJornada = [];
            }
        }

        // 2. Preparar a BASE do conteúdo conforme a fonte escolhida.
        //    - noticia: usa a análise da notícia selecionada.
        //    - biblioteca: faz RAG sobre os PDFs da literatura (trechos relevantes ao tema).
        //    - tema: sem base extra (conteúdo livre a partir do tema).
        $noticiaBase = null;   // base para posts a partir de notícia
        $literaturaBase = null; // base (RAG) para conteúdo educativo da biblioteca

        if ($fonte === 'noticia' && $noticiaId > 0) {
            try {
                $noticia = Database::queryOne("SELECT titulo, bloco1_noticia, bloco2_significa, bloco3_o_que_fazer FROM noticias WHERE id = :id", ['id' => $noticiaId]);
                if ($noticia) {
                    $noticiaBase = $noticia['titulo'] . "\n\n" . $noticia['bloco1_noticia'] . "\n\n" . $noticia['bloco2_significa'] . "\n\n" . $noticia['bloco3_o_que_fazer'];
                }
            } catch (\Exception $e) {
                // Ignorar se tabela não existir
            }
        } elseif ($fonte === 'biblioteca') {
            require_once APP_PATH . '/Controllers/ConteudoController.php';
            $literaturaBase = ConteudoController::recuperarTrechosRelevantes($empresaId, $tema, $bibliotecaIds);
            if ($literaturaBase === '') {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Não encontrei conteúdo utilizável na Biblioteca para este tema. Verifique se os PDFs foram processados.']);
                exit;
            }
        }

        // 3. PASSO 1 — Geração de texto via IA com contexto da jornada
        $prompt = ApiHelper::buildPromptConteudoContextualizado($marca, $tipo, $tema, $objetivo, $noticiaBase, $contextoJornada, $literaturaBase, $qtdSlides);
        $resIA = ApiHelper::chamarAnalise($prompt, true);

        if (!$resIA['sucesso'] || !is_array($resIA['conteudo'])) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Falha na geração de texto: ' . ($resIA['erro'] ?? 'API indisponível')]);
            exit;
        }

        $conteudoGerado = $resIA['conteudo'];
        $conteudoGerado['tipo'] = $tipo;
        $conteudoGerado['tema'] = $tema;
        $conteudoGerado['objetivo'] = $objetivo;
        $conteudoGerado['marca_id'] = $marcaId;
        $conteudoGerado['noticia_id'] = $noticiaId ?: null;

        // Limpa o TEXTO dos slides: remove emojis/símbolos e caracteres quebrados
        // (a IA às vezes insere emojis cujo encoding corrompe, ex.: "🚀"→"680").
        if (isset($conteudoGerado['slides']) && is_array($conteudoGerado['slides'])) {
            foreach ($conteudoGerado['slides'] as &$s) {
                if (isset($s['texto'])) $s['texto'] = $this->limparTextoSlide((string) $s['texto']);
                if (isset($s['texto_secundario'])) $s['texto_secundario'] = $this->limparTextoSlide((string) $s['texto_secundario']);
            }
            unset($s);
        }

        // 4. PASSO 2 — As imagens NÃO são geradas aqui (cada imagem no DALL-E leva
        //    ~10-20s; gerar N imagens numa única request estoura o timeout do proxy → 504).
        //    Marcamos os slides que precisam de imagem como "pendente" e o front-end
        //    dispara a geração de UMA imagem por vez via /maquina-de-conteudo/gerar-imagem-slide.
        $gerarImagens = ($estiloImagem === 'ia');
        $slidesPendentes = [];
        if (isset($conteudoGerado['slides']) && is_array($conteudoGerado['slides'])) {
            foreach ($conteudoGerado['slides'] as $index => &$slide) {
                if ($gerarImagens && !empty($slide['prompt_imagem'])) {
                    $slide['imagem_url'] = '';           // ainda será gerada
                    $slide['imagem_pendente'] = true;    // sinaliza para o front
                    $slidesPendentes[] = $index;
                } else {
                    $slide['imagem_pendente'] = false;
                }
            }
            unset($slide);
        }

        // 5. Salvar no banco (rascunho automático) — rápido, cabe no timeout.
        try {
            Database::execute(
                "INSERT INTO conteudos_marca (marca_id, usuario_id, tipo, tema, objetivo, noticia_id, slides, legenda, hashtags, status, imagens_locais, criado_em) 
                 VALUES (:marca_id, :user_id, :tipo, :tema, :objetivo, :noticia_id, :slides, :legenda, :hashtags, 'rascunho', :imagens, NOW())",
                [
                    'marca_id' => $marcaId,
                    'user_id' => Auth::id(),
                    'tipo' => $tipo,
                    'tema' => $tema,
                    'objetivo' => $objetivo,
                    'noticia_id' => $noticiaId ?: null,
                    'slides' => json_encode($conteudoGerado['slides'] ?? [], JSON_UNESCAPED_UNICODE),
                    'legenda' => $conteudoGerado['legenda'] ?? '',
                    'hashtags' => $conteudoGerado['hashtags'] ?? '',
                    'imagens' => json_encode([])
                ]
            );
            $conteudoId = Database::lastInsertId();
        } catch (\Exception $e) {
            // Se falhar BD, usar sessão como fallback
            Session::set('conteudo_gerado', $conteudoGerado);
            $conteudoId = 0;
        }

        Logger::acao('Conteúdo (texto) gerado com IA', ['marca_id' => $marcaId, 'tipo' => $tipo, 'tema' => $tema, 'slides_pendentes' => count($slidesPendentes)]);

        header('Content-Type: application/json');
        echo json_encode([
            'sucesso' => true,
            'conteudo_id' => $conteudoId,
            'conteudo' => $conteudoGerado,
            'slides_pendentes' => $slidesPendentes,     // índices que o front vai gerar
            'gerar_imagens' => $gerarImagens,
            'redirect_url' => APP_URL . '/maquina-de-conteudo/editar/' . $conteudoId,
            'mensagem' => 'Texto gerado! Gerando imagens...'
        ]);
        exit;
    }

    /**
     * Gera a imagem de UM slide de um conteúdo já criado (chamado pelo front,
     * um slide por vez, para não estourar o timeout do proxy).
     */
    public function gerarImagemSlide(): void
    {
        Auth::proteger();
        Csrf::verificar();
        @set_time_limit(90);
        header('Content-Type: application/json');

        $conteudoId = (int) ($_POST['conteudo_id'] ?? 0);
        $slideIndex = (int) ($_POST['slide_index'] ?? -1);
        if ($conteudoId <= 0 || $slideIndex < 0) {
            echo json_encode(['sucesso' => false, 'erro' => 'Parâmetros inválidos.']);
            exit;
        }

        try {
            $conteudo = Database::queryOne(
                "SELECT c.slides, c.marca_id, c.tipo, m.prompt_dalle
                 FROM conteudos_marca c JOIN marcas m ON c.marca_id = m.id
                 WHERE c.id = :id AND c.usuario_id = :user_id",
                ['id' => $conteudoId, 'user_id' => Auth::id()]
            );
            if (!$conteudo) {
                echo json_encode(['sucesso' => false, 'erro' => 'Conteúdo não encontrado.']);
                exit;
            }

            $slides = json_decode($conteudo['slides'], true) ?: [];
            if (!isset($slides[$slideIndex])) {
                echo json_encode(['sucesso' => false, 'erro' => 'Slide não encontrado.']);
                exit;
            }

            $marcaId = (int) $conteudo['marca_id'];
            $promptImagem = (string) ($slides[$slideIndex]['prompt_imagem'] ?? '');
            if ($promptImagem === '') {
                $slides[$slideIndex]['imagem_pendente'] = false;
                Database::execute("UPDATE conteudos_marca SET slides = :s WHERE id = :id", ['s' => json_encode($slides, JSON_UNESCAPED_UNICODE), 'id' => $conteudoId]);
                echo json_encode(['sucesso' => true, 'imagem_url' => '', 'vazio' => true]);
                exit;
            }

            // Formato vertical (retrato) para feed do Instagram; story também vertical.
            $tamanho = $this->tamanhoImagemPorTipo((string) ($conteudo['tipo'] ?? ''));

            $imgResult = null;
            $promptCompleto = '';

            // 1) PREFERENCIAL: gerar USANDO os templates da marca como REFERÊNCIA visual
            //    real (gpt-image-1 images/edits). É o que garante fidelidade ao estilo.
            $caminhosRef = $this->caminhosTemplatesLocais($marcaId);
            if (!empty($caminhosRef)) {
                $promptRef = 'Crie uma nova imagem VERTICAL para post de Instagram no MESMO estilo visual das imagens de referência fornecidas '
                    . '(mesma paleta, iluminação, composição, tipografia e estética). '
                    . 'Tema/assunto da imagem: ' . $promptImagem . '. '
                    . 'Mantenha coesão com a identidade das referências. NÃO copie o texto das referências; a imagem não deve conter texto, letras ou números.';
                $promptCompleto = $promptRef;
                $imgResult = ApiHelper::gerarImagemComReferencia($promptRef, $caminhosRef, $tamanho);
            }

            // 2) FALLBACK: sem templates ou se a geração por referência falhar,
            //    usa geração por texto guiada pela descrição do estilo.
            if (!$imgResult || empty($imgResult['sucesso']) || empty($imgResult['url'])) {
                $estiloTemplates = $this->obterEstiloTemplates($marcaId);
                $refTemplates = $estiloTemplates !== '' ? ' — Estilo visual de referência (siga fielmente): ' . $estiloTemplates : '';
                $promptCompleto = $conteudo['prompt_dalle'] . ' — ' . $promptImagem . $refTemplates . ' — Formato vertical para post de Instagram (retrato), composição centralizada com margens de segurança. Não incluir texto, palavras, letras ou números na imagem.';
                $imgResult = ApiHelper::gerarImagem($promptCompleto, $tamanho);

                if (!$imgResult['sucesso'] || empty($imgResult['url'])) {
                    $promptSimples = $conteudo['prompt_dalle'] . $refTemplates . ' — estilo corporativo moderno, formato vertical de Instagram, sem texto';
                    $imgResult = ApiHelper::gerarImagem($promptSimples, $tamanho);
                    $promptCompleto = $promptSimples;
                }
            }

            if (!$imgResult['sucesso'] || empty($imgResult['url'])) {
                echo json_encode(['sucesso' => false, 'erro' => $imgResult['erro'] ?? 'Falha ao gerar imagem.']);
                exit;
            }

            $caminhoLocal = $this->baixarImagemDalle($imgResult['url'], $marcaId);
            if (!$caminhoLocal) {
                echo json_encode(['sucesso' => false, 'erro' => 'Falha ao salvar a imagem.']);
                exit;
            }

            $urlPublica = APP_URL . $caminhoLocal;
            $slides[$slideIndex]['imagem_url'] = $urlPublica;
            $slides[$slideIndex]['imagem_pendente'] = false;

            Database::execute("UPDATE conteudos_marca SET slides = :s WHERE id = :id", ['s' => json_encode($slides, JSON_UNESCAPED_UNICODE), 'id' => $conteudoId]);

            // Registrar na tabela de controle (best-effort).
            try {
                Database::execute(
                    "INSERT INTO imagens_conteudo (conteudo_id, slide_index, caminho_original, caminho_local, url_dalle, prompt_usado, status, criado_em)
                     VALUES (:cid, :idx, :orig, :local, :dalle, :prompt, 'ativo', NOW())",
                    ['cid' => $conteudoId, 'idx' => $slideIndex, 'orig' => $caminhoLocal, 'local' => $caminhoLocal, 'dalle' => $caminhoLocal, 'prompt' => $promptCompleto]
                );
            } catch (\Throwable $e) { /* tabela opcional */ }

            echo json_encode(['sucesso' => true, 'imagem_url' => $urlPublica, 'slide_index' => $slideIndex]);
        } catch (\Throwable $e) {
            Logger::error('Erro ao gerar imagem do slide: ' . $e->getMessage());
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno ao gerar imagem.']);
        }
        exit;
    }

    public function editar(): void
    {
        Auth::proteger();
        $id = (int) ($_GET['id'] ?? 0);
        
        // Carregar conteúdo do banco ou da sessão
        $conteudo = null;
        if ($id > 0) {
            try {
                $conteudo = Database::queryOne(
                    "SELECT c.*, m.nome as marca_nome, m.prompt_dalle, m.paleta_cores 
                     FROM conteudos_marca c 
                     JOIN marcas m ON c.marca_id = m.id 
                     WHERE c.id = :id AND c.usuario_id = :user_id",
                    ['id' => $id, 'user_id' => Auth::id()]
                );
                if ($conteudo) {
                    $conteudo['slides'] = json_decode($conteudo['slides'], true) ?? [];
                    $conteudo['imagens_locais'] = json_decode($conteudo['imagens_locais'], true) ?? [];
                }
            } catch (\Exception $e) {
                // Tabela pode não existir ainda
            }
        }
        
        // Fallback para dados da sessão ou empty state
        if (!$conteudo) {
            $conteudo = Session::get('conteudo_gerado');
            if (!$conteudo) {
                // Se não há conteúdo, redirecionar para página principal
                Flash::set('erro', 'Conteúdo não encontrado.');
                header('Location: ' . APP_URL . '/maquina-de-conteudo');
                exit;
            }
            $conteudo['id'] = 0;
            $conteudo['marca_nome'] = 'Marca sem nome';
        }
        
        $dados = ['conteudo' => $conteudo];
        require VIEW_PATH . '/maquina/editar.php';
    }

    public function aprovar(): void
    {
        Auth::proteger();
        Csrf::verificar();
        
        $conteudoId = (int) ($_POST['conteudo_id'] ?? 0);
        
        if ($conteudoId === 0) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'ID inválido.']);
            exit;
        }
        
        try {
            Database::execute(
                "UPDATE conteudos_marca SET status = 'aprovado', atualizado_em = NOW() 
                 WHERE id = :id AND usuario_id = :user_id",
                ['id' => $conteudoId, 'user_id' => Auth::id()]
            );
            
            Logger::acao('Conteúdo aprovado', ['conteudo_id' => $conteudoId]);
            
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true, 'mensagem' => 'Conteúdo aprovado!']);
            
        } catch (\Exception $e) {
            Logger::error('Erro ao aprovar conteúdo', ['erro' => $e->getMessage()]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno.']);
        }
        exit;
    }

    /**
     * Agendar conteúdo para publicação
     */
    public function agendar(): void
    {
        Auth::proteger();
        Csrf::verificar();
        
        $conteudoId = (int) ($_POST['conteudo_id'] ?? 0);
        $dataHora = trim($_POST['data_hora_publicacao'] ?? '');
        
        if ($conteudoId === 0 || empty($dataHora)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Dados incompletos.']);
            exit;
        }
        
        // Validar formato da data
        $timestamp = strtotime($dataHora);
        if (!$timestamp || $timestamp < time()) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Data deve ser futura.']);
            exit;
        }
        
        try {
            // Verificar se conteúdo existe e está aprovado
            $conteudo = Database::queryOne(
                "SELECT id, status FROM conteudos_marca WHERE id = :id AND usuario_id = :user_id",
                ['id' => $conteudoId, 'user_id' => Auth::id()]
            );
            
            if (!$conteudo) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Conteúdo não encontrado.']);
                exit;
            }
            
            if ($conteudo['status'] !== 'aprovado') {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Apenas conteúdos aprovados podem ser agendados.']);
                exit;
            }
            
            // Agendar conteúdo
            Database::execute(
                "UPDATE conteudos_marca SET status = 'agendado', agendado_para = :data_hora WHERE id = :id",
                ['data_hora' => date('Y-m-d H:i:s', $timestamp), 'id' => $conteudoId]
            );
            
            Logger::acao('Conteúdo agendado', ['conteudo_id' => $conteudoId, 'agendado_para' => $dataHora]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Conteúdo agendado para ' . date('d/m/Y H:i', $timestamp) . '!'
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Erro ao agendar conteúdo', ['erro' => $e->getMessage()]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno.']);
        }
        exit;
    }

    /**
     * Download do conteúdo como ZIP
     */
    public function download(): void
    {
        Auth::proteger();
        
        $conteudoId = (int) ($_GET['id'] ?? 0);
        
        if ($conteudoId === 0) {
            http_response_code(400);
            echo 'ID inválido';
            exit;
        }
        
        try {
            // Carregar conteúdo completo
            $conteudo = Database::queryOne(
                "SELECT c.*, m.nome as marca_nome 
                 FROM conteudos_marca c 
                 JOIN marcas m ON c.marca_id = m.id 
                 WHERE c.id = :id AND c.usuario_id = :user_id",
                ['id' => $conteudoId, 'user_id' => Auth::id()]
            );
            
            if (!$conteudo) {
                http_response_code(404);
                echo 'Conteúdo não encontrado';
                exit;
            }
            
            // Carregar imagens do conteúdo
            $imagens = Database::query(
                "SELECT caminho_local FROM imagens_conteudo 
                 WHERE conteudo_id = :id AND status = 'ativo' 
                 ORDER BY slide_index",
                ['id' => $conteudoId]
            );
            
            // Criar ZIP temporário
            $zipPath = $this->criarZipConteudo($conteudo, $imagens);
            
            if (!$zipPath || !file_exists($zipPath)) {
                http_response_code(500);
                echo 'Erro na geração do ZIP';
                exit;
            }
            
            // Fazer download
            $nomeArquivo = 'conteudo_' . $conteudo['marca_nome'] . '_' . date('Y-m-d') . '.zip';
            $nomeArquivo = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $nomeArquivo);
            
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
            header('Content-Length: ' . filesize($zipPath));
            
            readfile($zipPath);
            
            // Limpar arquivo temporário
            unlink($zipPath);
            
            Logger::acao('Download de conteúdo', ['conteudo_id' => $conteudoId]);
            
        } catch (\Exception $e) {
            Logger::error('Erro no download', ['erro' => $e->getMessage(), 'conteudo_id' => $conteudoId]);
            http_response_code(500);
            echo 'Erro interno';
        }
        exit;
    }

    /**
     * Marcar conteúdo como publicado
     */
    public function marcarPublicado(): void
    {
        Auth::proteger();
        Csrf::verificar();
        
        $conteudoId = (int) ($_POST['conteudo_id'] ?? 0);
        $canal = trim($_POST['canal'] ?? 'manual');
        
        if ($conteudoId === 0) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'ID inválido.']);
            exit;
        }
        
        try {
            // Verificar se conteúdo existe
            $conteudo = Database::queryOne(
                "SELECT id, status FROM conteudos_marca WHERE id = :id AND usuario_id = :user_id",
                ['id' => $conteudoId, 'user_id' => Auth::id()]
            );
            
            if (!$conteudo) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Conteúdo não encontrado.']);
                exit;
            }
            
            // Atualizar para publicado
            Database::execute(
                "UPDATE conteudos_marca 
                 SET status = 'publicado', data_publicacao_real = NOW(), canal_publicacao = :canal 
                 WHERE id = :id",
                ['canal' => $canal, 'id' => $conteudoId]
            );
            
            Logger::acao('Conteúdo marcado como publicado', ['conteudo_id' => $conteudoId, 'canal' => $canal]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Conteúdo marcado como publicado!'
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Erro ao marcar como publicado', ['erro' => $e->getMessage()]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno.']);
        }
        exit;
    }

    /**
     * Carregar dados do calendário editorial
     */
    public function calendario(): void
    {
        Auth::proteger();
        
        $mes = (int) ($_GET['mes'] ?? date('n'));
        $ano = (int) ($_GET['ano'] ?? date('Y'));
        $marcaId = (int) ($_GET['marca_id'] ?? 0);
        
        try {
            $whereClause = $marcaId > 0 
                ? "WHERE c.marca_id = :marca_id AND c.usuario_id = :user_id" 
                : "WHERE c.usuario_id = :user_id";
            
            $params = ['user_id' => Auth::id()];
            if ($marcaId > 0) {
                $params['marca_id'] = $marcaId;
            }
            
            // Buscar conteúdos do mês
            $conteudos = Database::query(
                "SELECT c.id, c.tema, c.tipo, c.status, c.agendado_para, c.data_publicacao_real, m.nome as marca_nome
                 FROM conteudos_marca c 
                 JOIN marcas m ON c.marca_id = m.id 
                 {$whereClause}
                 AND (
                     (c.agendado_para IS NOT NULL AND MONTH(c.agendado_para) = :mes AND YEAR(c.agendado_para) = :ano)
                     OR 
                     (c.data_publicacao_real IS NOT NULL AND MONTH(c.data_publicacao_real) = :mes AND YEAR(c.data_publicacao_real) = :ano)
                 )
                 ORDER BY COALESCE(c.agendado_para, c.data_publicacao_real)",
                array_merge($params, ['mes' => $mes, 'ano' => $ano])
            );
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'conteudos' => $conteudos,
                'mes' => $mes,
                'ano' => $ano
            ]);
            
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao carregar calendário.']);
        }
        exit;
    }

    // ===== HELPER METHODS =====

    /**
     * Cria arquivo ZIP com conteúdo e imagens
     */
    private function criarZipConteudo(array $conteudo, array $imagens): ?string
    {
        try {
            // Criar diretório temporário
            $tempDir = sys_get_temp_dir() . '/conteudo_' . uniqid();
            if (!mkdir($tempDir, 0755, true)) {
                return null;
            }
            
            $slides = json_decode($conteudo['slides'], true) ?? [];
            
            // Criar arquivo de texto com informações
            $textoConteudo = "CONTEÚDO GERADO - {$conteudo['marca_nome']}\n";
            $textoConteudo .= "=====================================\n\n";
            $textoConteudo .= "Tema: {$conteudo['tema']}\n";
            $textoConteudo .= "Tipo: {$conteudo['tipo']}\n";
            $textoConteudo .= "Objetivo: {$conteudo['objetivo']}\n";
            $textoConteudo .= "Status: {$conteudo['status']}\n\n";
            
            if (!empty($slides)) {
                $textoConteudo .= "SLIDES:\n";
                $textoConteudo .= "-------\n";
                foreach ($slides as $i => $slide) {
                    $textoConteudo .= "Slide " . ($i + 1) . " ({$slide['tipo']}): {$slide['texto']}\n\n";
                }
            }
            
            if ($conteudo['legenda']) {
                $textoConteudo .= "LEGENDA:\n";
                $textoConteudo .= "--------\n";
                $textoConteudo .= "{$conteudo['legenda']}\n\n";
            }
            
            if ($conteudo['hashtags']) {
                $textoConteudo .= "HASHTAGS:\n";
                $textoConteudo .= "---------\n";
                $textoConteudo .= "{$conteudo['hashtags']}\n";
            }
            
            // Salvar arquivo de texto
            file_put_contents($tempDir . '/conteudo.txt', $textoConteudo);
            
            // Copiar imagens
            $imagemIndex = 1;
            foreach ($imagens as $img) {
                $caminhoOriginal = PUBLIC_PATH . $img['caminho_local'];
                if (file_exists($caminhoOriginal)) {
                    $extensao = pathinfo($caminhoOriginal, PATHINFO_EXTENSION);
                    $nomeImagem = 'slide_' . str_pad($imagemIndex, 2, '0', STR_PAD_LEFT) . '.' . $extensao;
                    copy($caminhoOriginal, $tempDir . '/' . $nomeImagem);
                    $imagemIndex++;
                }
            }
            
            // Criar ZIP
            $zipPath = sys_get_temp_dir() . '/conteudo_' . uniqid() . '.zip';
            $zip = new ZipArchive();
            
            if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
                return null;
            }
            
            // Adicionar todos os arquivos ao ZIP
            $files = scandir($tempDir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    $zip->addFile($tempDir . '/' . $file, $file);
                }
            }
            
            $zip->close();
            
            // Limpar diretório temporário
            $this->limparDiretorio($tempDir);
            rmdir($tempDir);
            
            return $zipPath;
            
        } catch (\Exception $e) {
            Logger::error('Erro ao criar ZIP', ['erro' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Remove todos os arquivos de um diretório
     */
    private function limparDiretorio(string $dir): void
    {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $fullPath = $dir . '/' . $file;
                if (is_file($fullPath)) {
                    unlink($fullPath);
                }
            }
        }
    }

    /**
     * Baixa imagem do DALL-E e salva localmente
     */
    private function baixarImagemDalle(string $urlDalle, int $marcaId): ?string
    {
        try {
            // Suporte a imagem em base64 (data URI), formato do gpt-image-1.
            if (str_starts_with($urlDalle, 'data:')) {
                $virgula = strpos($urlDalle, ',');
                $base64 = $virgula !== false ? substr($urlDalle, $virgula + 1) : '';
                $imagemData = base64_decode($base64, true);
                if ($imagemData === false || $imagemData === '') {
                    Logger::error('Falha ao decodificar imagem base64');
                    return null;
                }
            } else {
                // Fazer download da imagem por URL (dall-e).
                $ch = curl_init($urlDalle);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $imagemData = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode !== 200 || !$imagemData) {
                    Logger::error('Falha no download da imagem', ['http_code' => $httpCode]);
                    return null;
                }
            }
            
            // Criar diretório se não existir
            $dir = PUBLIC_PATH . '/uploads/conteudo/' . ($marcaId ?: 'temp');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // Salvar arquivo
            $nomeArquivo = 'dalle_' . uniqid() . '_' . time() . '.png';
            $caminhoCompleto = $dir . '/' . $nomeArquivo;
            $caminhoRelativo = '/uploads/conteudo/' . ($marcaId ?: 'temp') . '/' . $nomeArquivo;
            
            if (file_put_contents($caminhoCompleto, $imagemData) === false) {
                Logger::error('Falha ao salvar imagem local', ['caminho' => $caminhoCompleto]);
                return null;
            }
            
            return $caminhoRelativo;
            
        } catch (\Exception $e) {
            Logger::error('Erro no download DALL-E', ['erro' => $e->getMessage(), 'url' => $urlDalle]);
            return null;
        }
    }

    /**
     * Carrega marca do banco por ID
     */
    private function getMarcaPorId(int $marcaId): ?array
    {
        try {
            $marca = Database::queryOne("SELECT * FROM marcas WHERE id = :id AND ativo = 1", ['id' => $marcaId]);
            if ($marca && $marca['paleta_cores']) {
                $marca['paleta_cores'] = json_decode($marca['paleta_cores'], true);
            }
            return $marca;
        } catch (\Exception $e) {
            // Tabela pode não existir - retornar null para forçar redirecionamento
            return null;
        }
    }

    public function regenerarImagem(): void
    {
        Auth::proteger();
        Csrf::verificar();
        
        $conteudoId = (int) ($_POST['conteudo_id'] ?? 0);
        $slideIndex = (int) ($_POST['slide_index'] ?? 0);
        $promptEditado = htmlspecialchars(trim($_POST['prompt_editado'] ?? ''));
        
        if ($conteudoId === 0) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'ID do conteúdo inválido.']);
            exit;
        }
        
        try {
            // Carregar marca para prompt base
            $marca = Database::queryOne(
                "SELECT m.id, m.prompt_dalle FROM marcas m 
                 JOIN conteudos_marca c ON m.id = c.marca_id 
                 WHERE c.id = :id",
                ['id' => $conteudoId]
            );
            
            if (!$marca) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Marca não encontrada.']);
                exit;
            }
            
            // Estilo dos templates da marca (mesma referência usada na geração).
            $estiloTemplates = $this->obterEstiloTemplates((int) $marca['id']);
            $refTemplates = $estiloTemplates !== '' ? ' — Estilo visual de referência (siga fielmente): ' . $estiloTemplates : '';

            // Gerar nova imagem
            $promptCompleto = $marca['prompt_dalle'] . ' — ' . $promptEditado . $refTemplates . ' — Não incluir texto na imagem.';
            $imgResult = ApiHelper::gerarImagem($promptCompleto, '1024x1024');
            
            if (!$imgResult['sucesso']) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Falha na geração: ' . ($imgResult['erro'] ?? 'DALL-E indisponível')]);
                exit;
            }
            
            // Baixar nova imagem
            $caminhoLocal = $this->baixarImagemDalle($imgResult['url'], 0);
            if (!$caminhoLocal) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Falha no download da imagem.']);
                exit;
            }
            
            // Marcar imagem anterior como substituída
            Database::execute(
                "UPDATE imagens_conteudo SET status = 'substituido' 
                 WHERE conteudo_id = :id AND slide_index = :index AND status = 'ativo'",
                ['id' => $conteudoId, 'index' => $slideIndex]
            );
            
            // Inserir nova imagem
            Database::execute(
                "INSERT INTO imagens_conteudo (conteudo_id, slide_index, caminho_original, caminho_local, url_dalle, prompt_usado, status, criado_em) 
                 VALUES (:conteudo_id, :slide_index, :caminho_orig, :caminho_local, :url_dalle, :prompt_usado, 'ativo', NOW())",
                [
                    'conteudo_id' => $conteudoId,
                    'slide_index' => $slideIndex,
                    'caminho_orig' => $imgResult['url'],
                    'caminho_local' => $caminhoLocal,
                    'url_dalle' => $imgResult['url'],
                    'prompt_usado' => $promptCompleto
                ]
            );
            
            // Atualizar URL no JSON de slides
            $slides = Database::queryOne("SELECT slides FROM conteudos_marca WHERE id = :id", ['id' => $conteudoId]);
            if ($slides) {
                $slidesArray = json_decode($slides['slides'], true);
                if (isset($slidesArray[$slideIndex])) {
                    $slidesArray[$slideIndex]['imagem_url'] = APP_URL . $caminhoLocal;
                    Database::execute(
                        "UPDATE conteudos_marca SET slides = :slides WHERE id = :id",
                        ['slides' => json_encode($slidesArray), 'id' => $conteudoId]
                    );
                }
            }
            
            Logger::acao('Imagem regenerada', ['conteudo_id' => $conteudoId, 'slide_index' => $slideIndex]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'imagem_url' => APP_URL . $caminhoLocal,
                'mensagem' => 'Imagem regenerada!'
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Erro ao regenerar imagem', ['erro' => $e->getMessage(), 'conteudo_id' => $conteudoId]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno.']);
        }
        exit;
    }

    /**
     * Upload de imagem para substituir slide
     */
    public function uploadImagem(): void
    {
        Auth::proteger();
        Csrf::verificar();
        
        $conteudoId = (int) ($_POST['conteudo_id'] ?? 0);
        $slideIndex = (int) ($_POST['slide_index'] ?? 0);
        
        if (empty($_FILES['imagem']) || $_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Nenhum arquivo enviado.']);
            exit;
        }
        
        $arquivo = $_FILES['imagem'];
        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
        $permitidos = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (!in_array($extensao, $permitidos)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Formato não permitido. Use: jpg, png, webp']);
            exit;
        }
        
        // Validar tamanho (máx 5MB)
        if ($arquivo['size'] > 5 * 1024 * 1024) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Arquivo muito grande. Máximo: 5MB']);
            exit;
        }
        
        try {
            // Criar diretório
            $dir = PUBLIC_PATH . '/uploads/conteudo/' . $conteudoId;
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // Salvar arquivo
            $nomeArquivo = 'slide_' . $slideIndex . '_' . uniqid() . '.' . $extensao;
            $caminhoCompleto = $dir . '/' . $nomeArquivo;
            $caminhoRelativo = '/uploads/conteudo/' . $conteudoId . '/' . $nomeArquivo;
            
            if (!move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Falha ao salvar arquivo.']);
                exit;
            }
            
            // Marcar imagem anterior como substituída
            Database::execute(
                "UPDATE imagens_conteudo SET status = 'substituido' 
                 WHERE conteudo_id = :id AND slide_index = :index AND status = 'ativo'",
                ['id' => $conteudoId, 'index' => $slideIndex]
            );
            
            // Registrar nova imagem
            Database::execute(
                "INSERT INTO imagens_conteudo (conteudo_id, slide_index, caminho_original, caminho_local, prompt_usado, status, criado_em) 
                 VALUES (:conteudo_id, :slide_index, :caminho_orig, :caminho_local, 'Upload manual', 'ativo', NOW())",
                [
                    'conteudo_id' => $conteudoId,
                    'slide_index' => $slideIndex,
                    'caminho_orig' => $arquivo['name'],
                    'caminho_local' => $caminhoRelativo
                ]
            );
            
            // Atualizar JSON de slides
            $slides = Database::queryOne("SELECT slides FROM conteudos_marca WHERE id = :id", ['id' => $conteudoId]);
            if ($slides) {
                $slidesArray = json_decode($slides['slides'], true);
                if (isset($slidesArray[$slideIndex])) {
                    $slidesArray[$slideIndex]['imagem_url'] = APP_URL . $caminhoRelativo;
                    Database::execute(
                        "UPDATE conteudos_marca SET slides = :slides WHERE id = :id",
                        ['slides' => json_encode($slidesArray), 'id' => $conteudoId]
                    );
                }
            }
            
            Logger::acao('Imagem substituída via upload', ['conteudo_id' => $conteudoId, 'slide_index' => $slideIndex]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'imagem_url' => APP_URL . $caminhoRelativo,
                'mensagem' => 'Imagem substituída!'
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Erro no upload de imagem', ['erro' => $e->getMessage()]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno.']);
        }
        exit;
    }

    /**
     * Atualizar texto de um slide específico
     */
    public function atualizarSlide(): void
    {
        Auth::proteger();
        Csrf::verificar();
        
        $conteudoId = (int) ($_POST['conteudo_id'] ?? 0);
        $slideIndex = (int) ($_POST['slide_index'] ?? 0);
        $textoNovo = trim($_POST['texto'] ?? '');
        $ctaNovo = trim($_POST['cta'] ?? '');
        
        if ($conteudoId === 0) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'ID inválido.']);
            exit;
        }
        
        try {
            $slides = Database::queryOne("SELECT slides FROM conteudos_marca WHERE id = :id AND usuario_id = :user_id", ['id' => $conteudoId, 'user_id' => Auth::id()]);
            
            if (!$slides) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Conteúdo não encontrado.']);
                exit;
            }
            
            $slidesArray = json_decode($slides['slides'], true);
            if (!isset($slidesArray[$slideIndex])) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Slide não encontrado.']);
                exit;
            }
            
            // Atualizar texto
            $slidesArray[$slideIndex]['texto'] = $textoNovo;
            if (!empty($ctaNovo)) {
                $slidesArray[$slideIndex]['cta'] = $ctaNovo;
            }
            
            // Salvar no banco
            Database::execute(
                "UPDATE conteudos_marca SET slides = :slides, atualizado_em = NOW() WHERE id = :id",
                ['slides' => json_encode($slidesArray), 'id' => $conteudoId]
            );
            
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true, 'mensagem' => 'Texto atualizado!']);
            
        } catch (\Exception $e) {
            Logger::error('Erro ao atualizar slide', ['erro' => $e->getMessage()]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno.']);
        }
        exit;
    }

    /**
     * Upload de template — salva arquivo no servidor e registra no banco
     */
    public function uploadTemplate(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $marcaId = (int) ($_POST['marca_id'] ?? 1);

        if (empty($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Nenhum arquivo enviado.']);
            exit;
        }

        $arquivo = $_FILES['arquivo'];
        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
        $permitidos = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

        if (!in_array($extensao, $permitidos)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Formato não permitido. Use: ' . implode(', ', $permitidos)]);
            exit;
        }

        // Criar diretório se não existir
        $dir = PUBLIC_PATH . '/uploads/templates/' . $marcaId;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Nome único
        $nomeArquivo = uniqid('tpl_') . '.' . $extensao;
        $caminhoCompleto = $dir . '/' . $nomeArquivo;
        $caminhoRelativo = '/uploads/templates/' . $marcaId . '/' . $nomeArquivo;

        if (!move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Falha ao salvar arquivo.']);
            exit;
        }

        // Registrar no banco
        try {
            Database::execute(
                "INSERT INTO marca_templates (marca_id, nome_arquivo, caminho, nome_original, tipo, tamanho, criado_em) VALUES (:marca_id, :nome, :caminho, :original, :tipo, :tamanho, NOW())",
                [
                    'marca_id' => $marcaId,
                    'nome' => $nomeArquivo,
                    'caminho' => $caminhoRelativo,
                    'original' => $arquivo['name'],
                    'tipo' => $extensao,
                    'tamanho' => $arquivo['size'],
                ]
            );
        } catch (\Exception $e) {
            // Tabela pode não existir ainda — ignora mas mantém o arquivo
        }

        // Invalida o cache de estilo dos templates (será recalculado na próxima geração).
        $this->invalidarEstiloTemplates($marcaId);

        Logger::acao('Template uploaded', ['marca_id' => $marcaId, 'arquivo' => $nomeArquivo]);

        header('Content-Type: application/json');
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Template salvo!',
            'arquivo' => [
                'nome' => $arquivo['name'],
                'url' => APP_URL . $caminhoRelativo,
                'id' => Database::lastInsertId() ?? 0,
            ],
        ]);
        exit;
    }

    /**
     * Lista templates salvos de uma marca (JSON)
     */
    public function listarTemplates(): void
    {
        Auth::proteger();
        $marcaId = (int) ($_GET['marca_id'] ?? 1);

        try {
            $templates = Database::query(
                "SELECT id, nome_original, caminho, criado_em FROM marca_templates WHERE marca_id = :id ORDER BY criado_em DESC",
                ['id' => $marcaId]
            );
        } catch (\Exception $e) {
            $templates = [];
        }

        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'templates' => $templates]);
        exit;
    }

    /**
     * Remove um template
     */
    public function removerTemplate(): void
    {
        Auth::proteger();
        Csrf::verificar();
        $id = (int) ($_POST['template_id'] ?? 0);

        try {
            $template = Database::queryOne("SELECT marca_id, caminho FROM marca_templates WHERE id = :id", ['id' => $id]);
            if ($template && file_exists(PUBLIC_PATH . $template['caminho'])) {
                unlink(PUBLIC_PATH . $template['caminho']);
            }
            Database::execute("DELETE FROM marca_templates WHERE id = :id", ['id' => $id]);
            // Invalida o cache de estilo (os templates mudaram).
            if (!empty($template['marca_id'])) {
                $this->invalidarEstiloTemplates((int) $template['marca_id']);
            }
        } catch (\Exception $e) {}

        Logger::acao('Template removido', ['id' => $id]);
        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'mensagem' => 'Template removido!']);
        exit;
    }

    // ===== EMPTY STATE HELPERS =====

    /**
     * Criar marca padrão se não existir nenhuma
     */
    private function criarMarcaPadrao(): array
    {
        try {
            Database::execute(
                "INSERT INTO marcas (nome, nicho, publico, tom, arquetipo, prompt_dalle, ativo, criado_em) 
                 VALUES ('Exemplo Empresa', 'Tecnologia', 'PMEs', 'Profissional', 'Consultor', 'Imagem corporativa moderna', 1, NOW())"
            );
            
            return [
                'id' => Database::lastInsertId(),
                'nome' => 'Exemplo Empresa',
                'nicho' => 'Tecnologia',
                'ultimo' => date('Y-m-d'),
                'status' => 'ativo',
                'avatar' => 'E'
            ];
        } catch (Exception $e) {
            // Se falhar, retorna empty state
            return [];
        }
    }
}
