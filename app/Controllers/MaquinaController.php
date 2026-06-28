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
        $dados = [];
        require VIEW_PATH . '/maquina/nova-marca.php';
    }

    public function salvarMarca(): void
    {
        Auth::proteger();
        Csrf::verificar();
        // Em produção: salvar marca + uploads
        Logger::acao('Nova marca cadastrada', ['nome' => $_POST['nome'] ?? '']);
        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'mensagem' => 'Marca cadastrada!', 'redirect' => APP_URL . '/maquina-de-conteudo/marca?id=1']);
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
        
        // Carregar notícias disponíveis para usar como base
        $noticias = [];
        try {
            $noticias = Database::query(
                "SELECT id, titulo FROM noticias WHERE empresa_id = (SELECT empresa_id FROM usuarios WHERE id = :user_id) ORDER BY created_at DESC LIMIT 10",
                ['user_id' => Auth::id()]
            );
        } catch (\Exception $e) {
            // Tabela pode não existir - usar array vazio
            $noticias = [];
        }
        
        $dados = [
            'marca' => $marca,
            'conteudos' => $conteudos,
            'noticias' => $noticias,
        ];
        require VIEW_PATH . '/maquina/marca.php';
    }

    public function gerar(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $marcaId = (int) ($_POST['marca_id'] ?? 1);
        $tipo = htmlspecialchars(trim($_POST['tipo'] ?? 'carrossel'));
        $tema = htmlspecialchars(trim($_POST['tema'] ?? ''));
        $objetivo = htmlspecialchars(trim($_POST['objetivo'] ?? 'educar'));
        $estiloImagem = htmlspecialchars(trim($_POST['estilo_imagem'] ?? 'ia'));
        $noticiaId = (int) ($_POST['noticia_id'] ?? 0);

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

        // 2. Preparar base de notícia se selecionada
        $noticiaBase = null;
        if ($noticiaId > 0) {
            try {
                $noticia = Database::queryOne("SELECT titulo, bloco1_noticia, bloco2_significa, bloco3_o_que_fazer FROM noticias WHERE id = :id", ['id' => $noticiaId]);
                if ($noticia) {
                    $noticiaBase = $noticia['titulo'] . "\n\n" . $noticia['bloco1_noticia'] . "\n\n" . $noticia['bloco2_significa'] . "\n\n" . $noticia['bloco3_o_que_fazer'];
                }
            } catch (\Exception $e) {
                // Ignorar se tabela não existir
            }
        }

        // 3. PASSO 1 — Geração de texto via IA
        $prompt = ApiHelper::buildPromptConteudo($marca, $tipo, $tema, $objetivo, $noticiaBase);
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

        // 4. PASSO 2 — Geração e download de imagens (se IA ativo)
        $imagensLocais = [];
        if ($estiloImagem === 'ia' && isset($conteudoGerado['slides'])) {
            foreach ($conteudoGerado['slides'] as $index => &$slide) {
                if (!empty($slide['prompt_imagem'])) {
                    // Combinar prompt da marca + prompt do slide
                    $promptCompleto = $marca['prompt_dalle'] . ' — ' . $slide['prompt_imagem'] . ' — Não incluir texto, palavras ou números na imagem.';
                    
                    // Gerar imagem via DALL-E
                    $imgResult = ApiHelper::gerarImagem($promptCompleto, '1024x1024');
                    
                    if ($imgResult['sucesso'] && $imgResult['url']) {
                        // Baixar e salvar imagem localmente
                        $caminhoLocal = $this->baixarImagemDalle($imgResult['url'], $marcaId);
                        if ($caminhoLocal) {
                            $slide['imagem_url'] = APP_URL . $caminhoLocal;
                            $imagensLocais[$index] = [
                                'url_dalle' => $imgResult['url'],
                                'caminho_local' => $caminhoLocal,
                                'prompt_usado' => $promptCompleto
                            ];
                        } else {
                            $slide['imagem_url'] = 'https://placehold.co/1080x1080/1E3A5F/ffffff?text=Erro+Download';
                        }
                    } else {
                        // Tentar com prompt simplificado
                        $promptSimples = $marca['prompt_dalle'] . ' — estilo corporativo moderno, sem texto';
                        $imgResult2 = ApiHelper::gerarImagem($promptSimples, '1024x1024');
                        
                        if ($imgResult2['sucesso']) {
                            $caminhoLocal = $this->baixarImagemDalle($imgResult2['url'], $marcaId);
                            $slide['imagem_url'] = $caminhoLocal ? APP_URL . $caminhoLocal : 'https://placehold.co/1080x1080/1E3A5F/ffffff?text=Fallback';
                            $imagensLocais[$index] = [
                                'url_dalle' => $imgResult2['url'],
                                'caminho_local' => $caminhoLocal,
                                'prompt_usado' => $promptSimples
                            ];
                        } else {
                            $slide['imagem_url'] = 'https://placehold.co/1080x1080/1E3A5F/ffffff?text=Sem+Imagem';
                        }
                    }
                }
            }
            unset($slide);
        }

        // 5. Salvar no banco (rascunho automático)
        try {
            $conteudoId = Database::execute(
                "INSERT INTO conteudos_marca (marca_id, usuario_id, tipo, tema, objetivo, noticia_id, slides, legenda, hashtags, status, imagens_locais, criado_em) 
                 VALUES (:marca_id, :user_id, :tipo, :tema, :objetivo, :noticia_id, :slides, :legenda, :hashtags, 'rascunho', :imagens, NOW())",
                [
                    'marca_id' => $marcaId,
                    'user_id' => Auth::id(),
                    'tipo' => $tipo,
                    'tema' => $tema,
                    'objetivo' => $objetivo,
                    'noticia_id' => $noticiaId ?: null,
                    'slides' => json_encode($conteudoGerado['slides'] ?? []),
                    'legenda' => $conteudoGerado['legenda'] ?? '',
                    'hashtags' => $conteudoGerado['hashtags'] ?? '',
                    'imagens' => json_encode($imagensLocais)
                ]
            );
            $conteudoId = Database::lastInsertId();

            // Salvar imagens individuais na tabela de controle
            foreach ($imagensLocais as $slideIndex => $img) {
                Database::execute(
                    "INSERT INTO imagens_conteudo (conteudo_id, slide_index, caminho_original, caminho_local, url_dalle, prompt_usado, status, criado_em) 
                     VALUES (:conteudo_id, :slide_index, :caminho_orig, :caminho_local, :url_dalle, :prompt_usado, 'ativo', NOW())",
                    [
                        'conteudo_id' => $conteudoId,
                        'slide_index' => $slideIndex,
                        'caminho_orig' => $img['url_dalle'],
                        'caminho_local' => $img['caminho_local'],
                        'url_dalle' => $img['url_dalle'],
                        'prompt_usado' => $img['prompt_usado']
                    ]
                );
            }

        } catch (\Exception $e) {
            // Se falhar BD, usar sessão como fallback
            Session::set('conteudo_gerado', $conteudoGerado);
            $conteudoId = 0;
        }

        Logger::acao('Conteúdo gerado com IA', ['marca_id' => $marcaId, 'tipo' => $tipo, 'tema' => $tema, 'imagens_geradas' => count($imagensLocais)]);

        header('Content-Type: application/json');
        echo json_encode([
            'sucesso' => true,
            'conteudo_id' => $conteudoId,
            'conteudo' => $conteudoGerado,
            'redirect_url' => APP_URL . '/maquina-de-conteudo/editar/' . $conteudoId,
            'mensagem' => 'Conteúdo gerado e salvo como rascunho!'
        ]);
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
            // Fazer download da imagem
            $ch = curl_init($urlDalle);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $imagemData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200 || !$imagemData) {
                Logger::error('Falha no download DALL-E', ['url' => $urlDalle, 'http_code' => $httpCode]);
                return null;
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
                "SELECT m.prompt_dalle FROM marcas m 
                 JOIN conteudos_marca c ON m.id = c.marca_id 
                 WHERE c.id = :id",
                ['id' => $conteudoId]
            );
            
            if (!$marca) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Marca não encontrada.']);
                exit;
            }
            
            // Gerar nova imagem
            $promptCompleto = $marca['prompt_dalle'] . ' — ' . $promptEditado . ' — Não incluir texto na imagem.';
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
            $template = Database::queryOne("SELECT caminho FROM marca_templates WHERE id = :id", ['id' => $id]);
            if ($template && file_exists(PUBLIC_PATH . $template['caminho'])) {
                unlink(PUBLIC_PATH . $template['caminho']);
            }
            Database::execute("DELETE FROM marca_templates WHERE id = :id", ['id' => $id]);
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
