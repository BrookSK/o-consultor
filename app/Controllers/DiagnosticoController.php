<?php
/**
 * DiagnosticoController — Módulo de Diagnóstico Empresarial
 * O Consultor — Sistema Operacional Empresarial
 *
 * ACESSO: ADMIN_HOLDING, CONSULTOR_INTERNO (cria para cliente), CLIENTE (preenche o próprio)
 */

class DiagnosticoController
{
    /**
     * Listagem de diagnósticos
     */
    public function index(): void
    {
        Auth::proteger();

        $usuario = Auth::usuario();
        $diagnosticos = [];
        
        // Buscar diagnósticos do usuário no banco
        if (Auth::perfil() === 'ADMIN_HOLDING') {
            // Admin vê todos os diagnósticos
            $diagnosticos = Database::query(
                "SELECT d.*, e.nome as empresa_nome, u.nome as responsavel_nome 
                 FROM diagnosticos d 
                 LEFT JOIN empresas e ON d.empresa_id = e.id 
                 LEFT JOIN usuarios u ON d.usuario_id = u.id 
                 ORDER BY d.criado_em DESC LIMIT 50"
            );
        } else {
            // Usuários vêem apenas os próprios diagnósticos ou da empresa
            $diagnosticos = Diagnostico::listarPorUsuario($usuario['id']);
        }
        
        // Mapear status para labels
        foreach ($diagnosticos as &$diag) {
            $diag['status_label'] = match($diag['status']) {
                'concluido' => 'Concluído',
                'em_andamento' => 'Em andamento',
                default => 'Rascunho'
            };
            
            // Calcular score se não existir
            if ($diag['pontuacao'] == 0 && $diag['status'] === 'concluido' && !empty($diag['respostas'])) {
                $respostas = json_decode($diag['respostas'], true) ?? [];
                $diag['pontuacao'] = $this->calcularScore($respostas);
            }
            
            $diag['score'] = match(true) {
                $diag['pontuacao'] >= 80 => 4,
                $diag['pontuacao'] >= 60 => 3,
                $diag['pontuacao'] >= 40 => 2,
                default => 1
            };
        }

        $dados = [
            'diagnosticos' => $diagnosticos,
        ];

        require VIEW_PATH . '/diagnostico/index.php';
    }

    /**
     * Iniciar novo diagnóstico (Bloco 1) - Inclui upload de documentos
     */
    public function novo(): void
    {
        Auth::proteger();
        
        $usuario = Auth::usuario();
        $empresaId = Auth::empresa();
        
        // Buscar ou criar rascunho em andamento
        $rascunho = Diagnostico::buscarOuCriarRascunho($usuario['id']);
        
        if (empty($rascunho)) {
            Flash::set('erro', 'Erro ao iniciar diagnóstico. Tente novamente.');
            header('Location: ' . APP_URL . '/diagnostico');
            exit;
        }
        
        // Buscar documentos já enviados pela empresa
        $documentosExistentes = [];
        if ($empresaId) {
            try {
                $documentosExistentes = Database::query(
                    "SELECT id, nome_original, tipo_documento, tamanho_bytes, processado_ia, criado_em 
                     FROM documentos_empresa 
                     WHERE empresa_id = :empresa_id AND ativo = 1 
                     ORDER BY criado_em DESC",
                    ['empresa_id' => $empresaId]
                );
            } catch (Exception $e) {
                // Tabela pode não existir ainda - ignorar erro silenciosamente
                $documentosExistentes = [];
                Logger::info('Tabela documentos_empresa não existe ainda', ['erro' => $e->getMessage()]);
            }
        }
        
        // Determinar em qual bloco deve começar
        $blocoAtual = (int) ($rascunho['bloco_atual'] ?? 1);
        
        // Redirecionar para o bloco correto se não for o primeiro
        if ($blocoAtual > 1) {
            header('Location: ' . APP_URL . '/diagnostico/bloco/' . $blocoAtual . '?rascunho_id=' . $rascunho['id']);
            exit;
        }
        
        // Opções para selects
        $opcoes = $this->getOpcoesWizard();
        
        $dados = [
            'rascunho' => $rascunho,
            'bloco_atual' => 1,
            'total_blocos' => 5,
            'opcoes' => $opcoes,
            'documentos_existentes' => $documentosExistentes
        ];

        require VIEW_PATH . '/diagnostico/bloco1.php';
    }
    
    /**
     * Exibir bloco específico do diagnóstico
     */
    public function bloco(): void
    {
        Auth::proteger();
        
        $bloco = (int) ($_GET['bloco'] ?? 1);
        $rascunhoId = (int) ($_GET['rascunho_id'] ?? 0);
        
        if ($bloco < 1 || $bloco > 5) {
            header('Location: ' . APP_URL . '/diagnostico/novo');
            exit;
        }
        
        // Buscar rascunho
        $rascunho = Database::queryOne("SELECT * FROM diagnosticos_rascunho WHERE id = :id AND usuario_id = :usuario_id", 
                                      ['id' => $rascunhoId, 'usuario_id' => Auth::id()]);
        
        if (!$rascunho || $rascunho['status'] !== 'em_andamento') {
            Flash::set('erro', 'Rascunho não encontrado ou já finalizado.');
            header('Location: ' . APP_URL . '/diagnostico/novo');
            exit;
        }
        
        // Verificar se pode acessar este bloco (deve ter preenchido os anteriores)
        if ($bloco > $rascunho['bloco_atual']) {
            header('Location: ' . APP_URL . '/diagnostico/bloco/' . $rascunho['bloco_atual'] . '?rascunho_id=' . $rascunhoId);
            exit;
        }
        
        $opcoes = $this->getOpcoesWizard();
        
        $dados = [
            'rascunho' => $rascunho,
            'bloco_atual' => $bloco,
            'total_blocos' => 5,
            'opcoes' => $opcoes
        ];
        
        require VIEW_PATH . '/diagnostico/bloco' . $bloco . '.php';
    }

    /**
     * Salvar bloco específico do diagnóstico
     */
    public function salvarBloco(): void
    {
        Auth::proteger();
        Csrf::verificar();
        
        $bloco = (int) ($_POST['bloco'] ?? 1);
        $rascunhoId = (int) ($_POST['rascunho_id'] ?? 0);
        
        if ($bloco < 1 || $bloco > 5) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Bloco inválido']);
            exit;
        }
        
        // Verificar se o rascunho pertence ao usuário
        $rascunho = Database::queryOne("SELECT * FROM diagnosticos_rascunho WHERE id = :id AND usuario_id = :usuario_id", 
                                      ['id' => $rascunhoId, 'usuario_id' => Auth::id()]);
        
        if (!$rascunho) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Rascunho não encontrado']);
            exit;
        }
        
        // Coletar dados do bloco
        $dadosBloco = [];
        
        switch ($bloco) {
            case 1:
                $dadosBloco = [
                    'empresa_nome' => htmlspecialchars(trim($_POST['empresa_nome'] ?? '')),
                    'setor' => htmlspecialchars(trim($_POST['setor'] ?? '')),
                    'descricao' => htmlspecialchars(trim($_POST['descricao'] ?? '')),
                    'tempo_existencia' => htmlspecialchars(trim($_POST['tempo_existencia'] ?? '')),
                    'estrutura_societaria' => htmlspecialchars(trim($_POST['estrutura_societaria'] ?? '')),
                    'unidades_filiais' => (int) ($_POST['unidades_filiais'] ?? 1),
                    'lingua_principal' => htmlspecialchars(trim($_POST['lingua_principal'] ?? 'Português'))
                ];
                
                if (empty($dadosBloco['empresa_nome'])) {
                    header('Content-Type: application/json');
                    echo json_encode(['sucesso' => false, 'mensagem' => 'Nome da empresa é obrigatório']);
                    exit;
                }
                break;
                
            case 2:
                $dadosBloco = [
                    'colaboradores_internos' => (int) ($_POST['colaboradores_internos'] ?? 0),
                    'colaboradores_externos' => (int) ($_POST['colaboradores_externos'] ?? 0),
                    'departamentos' => $_POST['departamentos'] ?? [],
                    'clientes_ativos' => (int) ($_POST['clientes_ativos'] ?? 0),
                    'produtos_servicos' => htmlspecialchars(trim($_POST['produtos_servicos'] ?? '')),
                    'faturamento_mensal' => htmlspecialchars(trim($_POST['faturamento_mensal'] ?? '')),
                    'ticket_medio' => htmlspecialchars(trim($_POST['ticket_medio'] ?? '')),
                    'sites_referencia' => htmlspecialchars(trim($_POST['sites_referencia'] ?? ''))
                ];
                break;
                
            case 3:
                $dadosBloco = [
                    'processo_entrega' => htmlspecialchars(trim($_POST['processo_entrega'] ?? '')),
                    'ferramentas_softwares' => htmlspecialchars(trim($_POST['ferramentas_softwares'] ?? '')),
                    'fornecedores_criticos' => htmlspecialchars(trim($_POST['fornecedores_criticos'] ?? '')),
                    'dependencia_pessoa' => htmlspecialchars(trim($_POST['dependencia_pessoa'] ?? '')),
                    'integracoes' => htmlspecialchars(trim($_POST['integracoes'] ?? '')),
                    'processos_documentados' => (int) ($_POST['processos_documentados'] ?? 0),
                    'ferramentas_gestao' => $_POST['ferramentas_gestao'] ?? []
                ];
                break;
                
            case 4:
                $dadosBloco = [
                    'problemas_operacionais' => htmlspecialchars(trim($_POST['problemas_operacionais'] ?? '')),
                    'riscos_identificados' => htmlspecialchars(trim($_POST['riscos_identificados'] ?? '')),
                    'incidentes_tipo' => htmlspecialchars(trim($_POST['incidentes_tipo'] ?? '')),
                    'incidentes_descricao' => htmlspecialchars(trim($_POST['incidentes_descricao'] ?? '')),
                    'areas_vulneraveis' => $_POST['areas_vulneraveis'] ?? [],
                    'cliente_concentrado' => htmlspecialchars(trim($_POST['cliente_concentrado'] ?? 'nao')),
                    'fornecedor_insubstituivel' => htmlspecialchars(trim($_POST['fornecedor_insubstituivel'] ?? 'nao')),
                    'processos_sem_backup' => htmlspecialchars(trim($_POST['processos_sem_backup'] ?? 'nao'))
                ];
                break;
                
            case 5:
                $dadosBloco = [
                    'pontos_fortes' => htmlspecialchars(trim($_POST['pontos_fortes'] ?? '')),
                    'pontos_melhoria' => htmlspecialchars(trim($_POST['pontos_melhoria'] ?? '')),
                    'objetivo_12_meses' => htmlspecialchars(trim($_POST['objetivo_12_meses'] ?? '')),
                    'maturidade_percebida' => (int) ($_POST['maturidade_percebida'] ?? 3),
                    'planejamento_documentado' => htmlspecialchars(trim($_POST['planejamento_documentado'] ?? 'nao')),
                    'frequencia_reunioes' => htmlspecialchars(trim($_POST['frequencia_reunioes'] ?? '')),
                    'meta_faturamento' => htmlspecialchars(trim($_POST['meta_faturamento'] ?? 'nao'))
                ];
                break;
        }
        
        try {
            // Salvar bloco no banco
            $sucesso = Diagnostico::salvarBlocoRascunho($rascunhoId, $bloco, $dadosBloco);
            
            if (!$sucesso) {
                throw new Exception('Erro ao salvar dados no banco');
            }
            
            Logger::acao('Bloco de diagnóstico salvo', [
                'rascunho_id' => $rascunhoId,
                'bloco' => $bloco,
                'empresa' => $dadosBloco['empresa_nome'] ?? 'N/A'
            ]);
            
            // Determinar próximo bloco
            $proximoBloco = $bloco < 5 ? $bloco + 1 : 5;
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Bloco ' . $bloco . ' salvo com sucesso!',
                'proximo_bloco' => $proximoBloco,
                'redirect' => $bloco < 5 ? 
                    APP_URL . '/diagnostico/bloco/' . $proximoBloco . '?rascunho_id=' . $rascunhoId :
                    null
            ]);
            
        } catch (Exception $e) {
            Logger::error('Erro ao salvar bloco diagnóstico: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao salvar: ' . $e->getMessage()]);
        }
        
        exit;
    }

    /**
     * Gerar diagnóstico completo com IA
     */
    public function gerar(): void
    {
        Auth::proteger();
        Csrf::verificar();
        
        $rascunhoId = (int) ($_POST['rascunho_id'] ?? 0);
        
        // Buscar rascunho
        $rascunho = Database::queryOne("SELECT * FROM diagnosticos_rascunho WHERE id = :id AND usuario_id = :usuario_id", 
                                      ['id' => $rascunhoId, 'usuario_id' => Auth::id()]);
        
        if (!$rascunho || $rascunho['status'] !== 'em_andamento') {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Rascunho não encontrado ou já finalizado']);
            exit;
        }
        
        // Verificar se todos os 5 blocos foram preenchidos
        if ($rascunho['bloco_atual'] < 5) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Complete todos os 5 blocos antes de gerar o diagnóstico']);
            exit;
        }
        
        try {
            // 1. Montar JSON completo com todos os dados dos 5 blocos
            $dadosCompletos = $this->montarDadosCompletos($rascunho);
            
            // 2. Chamar OpenAI para análise se as APIs estão configuradas
            $resultadoIA = null;
            $sitesReferencia = [];
            
            if ($this->apiConfigurada()) {
                // Buscar documentos relevantes da empresa
                $empresaId = $rascunho['empresa_id'] ?? Auth::empresa();
                $documentosRelevantes = [];
                $contextoDocumentos = '';
                
                if (class_exists('DocumentoProcessor') && DocumentoProcessor::isAvailable()) {
                    try {
                        $documentosRelevantes = DocumentoProcessor::buscarDocumentosRelevantes($empresaId);
                        $contextoDocumentos = DocumentoProcessor::construirContextoDocumentos($documentosRelevantes);
                    } catch (Exception $e) {
                        Logger::warning('Erro ao buscar documentos para diagnóstico', ['erro' => $e->getMessage()]);
                    }
                }
                
                $resultadoIA = $this->chamarOpenAIAnalise($dadosCompletos, $contextoDocumentos);
                if ($resultadoIA) {
                    $sitesReferencia = $resultadoIA['sites_referencia'] ?? [];
                }
                
                // Registrar uso dos documentos
                if (class_exists('DocumentoProcessor') && !empty($documentosRelevantes)) {
                    foreach ($documentosRelevantes as $doc) {
                        DocumentoProcessor::registrarUso($doc['id'], $empresaId, Auth::id(), 'diagnostico', null);
                    }
                }
            }
            
            // 3. Gerar diagnóstico completo no banco
            $diagnosticoId = Diagnostico::gerarDoRascunho($rascunhoId);
            
            if (!$diagnosticoId) {
                throw new Exception('Erro ao gerar diagnóstico completo');
            }
            
            // 4. Calcular score de maturidade
            $score = $this->calcularScore($dadosCompletos);
            
            // 5. Atualizar diagnóstico com pontuação e resultado da IA
            Database::execute(
                "UPDATE diagnosticos SET pontuacao = :pontuacao, observacoes = :observacoes WHERE id = :id",
                [
                    'id' => $diagnosticoId,
                    'pontuacao' => $score,
                    'observacoes' => $resultadoIA ? json_encode($resultadoIA) : 'Diagnóstico gerado sem IA'
                ]
            );
            
            // 6. Atualizar empresa com dados do diagnóstico
            $empresaId = $rascunho['empresa_id'];
            if (!$empresaId && !empty($rascunho['empresa_nome'])) {
                $empresaExistente = Database::queryOne("SELECT id FROM empresas WHERE nome = ?", [$rascunho['empresa_nome']]);
                $empresaId = $empresaExistente['id'] ?? null;
            }
            
            if ($empresaId) {
                Empresa::atualizar($empresaId, [
                    'score_maturidade' => $score,
                    'lingua_principal' => $rascunho['lingua_principal'] ?? 'Português',
                    'faturamento_mensal' => $rascunho['faturamento_mensal'],
                    'colaboradores_internos' => $rascunho['colaboradores_internos'],
                    'principal_desafio' => $rascunho['objetivo_12_meses']
                ]);
                
                // 7. Salvar sites de referência sugeridos pela IA
                if (!empty($sitesReferencia)) {
                    Diagnostico::salvarSitesReferencia($empresaId, $sitesReferencia);
                }
            }
            
            Logger::acao('Diagnóstico completo gerado', [
                'diagnostico_id' => $diagnosticoId,
                'empresa_id' => $empresaId,
                'score' => $score,
                'com_ia' => $resultadoIA !== null
            ]);
            
            // 8. Inicializar perfil de busca de notícias (F-09)
            try {
                $noticiasController = new NoticiasController();
                $noticiasController->inicializarPerfil($empresaId);
                Logger::acao('Perfil de notícias inicializado após diagnóstico', ['empresa_id' => $empresaId]);
            } catch (Exception $e) {
                Logger::warning('Falha ao inicializar perfil de notícias', [
                    'empresa_id' => $empresaId,
                    'erro' => $e->getMessage()
                ]);
                // Não interromper o fluxo principal por erro na inicialização de notícias
            }
            
            // 9. Preparar resultado para exibição
            $resultado = $this->gerarResultado($dadosCompletos, $score);
            if ($resultadoIA) {
                $resultado = array_merge($resultado, $resultadoIA);
            }
            
            $resultado['diagnostico_id'] = $diagnosticoId;
            $resultado['empresa_id'] = $empresaId;
            
            // Salvar na sessão
            Session::set('diagnostico_resultado', $resultado);
            Session::set('diagnostico_dados', $dadosCompletos);
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Diagnóstico gerado com sucesso!',
                'diagnostico_id' => $diagnosticoId,
                'score' => $score,
                'redirect' => APP_URL . '/diagnostico/resultado/' . $diagnosticoId
            ]);
            
        } catch (Exception $e) {
            Logger::error('Erro ao gerar diagnóstico: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao gerar diagnóstico: ' . $e->getMessage()]);
        }
        
        exit;
    }
    
    /**
     * Upload de documentos da empresa para enriquecer IA
     */
    public function uploadDocumentos(): void
    {
        Auth::proteger();
        Csrf::verificar();
        
        $empresaId = Auth::empresa();
        $usuarioId = Auth::id();
        
        if (!$empresaId) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Empresa não identificada.']);
            exit;
        }
        
        if (empty($_FILES['documentos']) || empty($_FILES['documentos']['tmp_name'])) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Nenhum documento foi enviado.']);
            exit;
        }
        
        // Verificar se a classe DocumentoProcessor está disponível
        if (!class_exists('DocumentoProcessor')) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Sistema de processamento de documentos não está disponível.']);
            exit;
        }
        
        try {
            // Processar uploads
            $resultados = DocumentoProcessor::processarUploads($_FILES['documentos'], $empresaId, $usuarioId);
            
            // Contar sucessos e falhas
            $sucessos = array_filter($resultados, fn($r) => $r['sucesso']);
            $falhas = array_filter($resultados, fn($r) => !$r['sucesso']);
            
            $mensagem = sprintf(
                '%d documento(s) enviado(s) com sucesso',
                count($sucessos)
            );
            
            if (!empty($falhas)) {
                $mensagem .= sprintf(', %d falha(s)', count($falhas));
            }
            
            Logger::acao('Upload de documentos para diagnóstico', [
                'empresa_id' => $empresaId,
                'total_enviados' => count($resultados),
                'sucessos' => count($sucessos),
                'falhas' => count($falhas)
            ]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => !empty($sucessos),
                'mensagem' => $mensagem,
                'resultados' => $resultados,
                'total_sucessos' => count($sucessos),
                'total_falhas' => count($falhas)
            ]);
        } catch (Exception $e) {
            Logger::error('Erro no upload de documentos: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno no processamento dos documentos.']);
        }
        
        exit;
    }

    /**
     * Salvar diagnóstico (método legado - mantido para compatibilidade)
     */
    public function salvar(): void
    {
        Auth::proteger();
        Csrf::verificar();

        // Coletar todos os dados dos 5 blocos
        $dadosForm = [
            // Bloco 1 — Identificação
            'empresa_nome' => htmlspecialchars(trim($_POST['empresa_nome'] ?? '')),
            'setor' => htmlspecialchars(trim($_POST['setor'] ?? '')),
            'descricao' => htmlspecialchars(trim(substr($_POST['descricao'] ?? '', 0, 300))),
            'tempo_existencia' => htmlspecialchars(trim($_POST['tempo_existencia'] ?? '')),
            'estrutura_societaria' => htmlspecialchars(trim($_POST['estrutura_societaria'] ?? '')),
            'unidades_filiais' => (int) ($_POST['unidades_filiais'] ?? 1),
            'lingua_principal' => htmlspecialchars(trim($_POST['lingua_principal'] ?? 'Portugues')),

            // Bloco 2 — Estrutura Operacional
            'colaboradores_internos' => (int) ($_POST['colaboradores_internos'] ?? 0),
            'colaboradores_externos' => (int) ($_POST['colaboradores_externos'] ?? 0),
            'departamentos' => $_POST['departamentos'] ?? [],
            'clientes_ativos' => (int) ($_POST['clientes_ativos'] ?? 0),
            'produtos_servicos' => htmlspecialchars(trim($_POST['produtos_servicos'] ?? '')),
            'faturamento_mensal' => htmlspecialchars(trim($_POST['faturamento_mensal'] ?? '')),
            'ticket_medio' => htmlspecialchars(trim($_POST['ticket_medio'] ?? '')),
            'sites_referencia' => htmlspecialchars(trim($_POST['sites_referencia'] ?? '')),

            // Bloco 3 — Operação Atual
            'processo_entrega' => htmlspecialchars(trim($_POST['processo_entrega'] ?? '')),
            'ferramentas_softwares' => htmlspecialchars(trim($_POST['ferramentas_softwares'] ?? '')),
            'fornecedores_criticos' => htmlspecialchars(trim($_POST['fornecedores_criticos'] ?? '')),
            'dependencia_pessoa' => htmlspecialchars(trim($_POST['dependencia_pessoa'] ?? '')),
            'integracoes' => htmlspecialchars(trim($_POST['integracoes'] ?? '')),
            'processos_documentados' => (int) ($_POST['processos_documentados'] ?? 0),
            'ferramentas_gestao' => $_POST['ferramentas_gestao'] ?? [],

            // Bloco 4 — Problemas e Riscos
            'problemas_operacionais' => htmlspecialchars(trim($_POST['problemas_operacionais'] ?? '')),
            'riscos_identificados' => htmlspecialchars(trim($_POST['riscos_identificados'] ?? '')),
            'incidentes_tipo' => htmlspecialchars(trim($_POST['incidentes_tipo'] ?? '')),
            'incidentes_descricao' => htmlspecialchars(trim($_POST['incidentes_descricao'] ?? '')),
            'areas_vulneraveis' => $_POST['areas_vulneraveis'] ?? [],
            'cliente_concentrado' => htmlspecialchars(trim($_POST['cliente_concentrado'] ?? 'nao')),
            'fornecedor_insubstituivel' => htmlspecialchars(trim($_POST['fornecedor_insubstituivel'] ?? 'nao')),
            'processos_sem_backup' => htmlspecialchars(trim($_POST['processos_sem_backup'] ?? 'nao')),

            // Bloco 5 — Contexto Estratégico
            'pontos_fortes' => htmlspecialchars(trim($_POST['pontos_fortes'] ?? '')),
            'pontos_melhoria' => htmlspecialchars(trim($_POST['pontos_melhoria'] ?? '')),
            'objetivo_12_meses' => htmlspecialchars(trim($_POST['objetivo_12_meses'] ?? '')),
            'maturidade_percebida' => (int) ($_POST['maturidade_percebida'] ?? 3),
            'planejamento_documentado' => htmlspecialchars(trim($_POST['planejamento_documentado'] ?? 'nao')),
            'frequencia_reunioes' => htmlspecialchars(trim($_POST['frequencia_reunioes'] ?? '')),
            'meta_faturamento' => htmlspecialchars(trim($_POST['meta_faturamento'] ?? 'nao')),
        ];

        if (empty($dadosForm['empresa_nome'])) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Nome da empresa é obrigatório']);
            exit;
        }

        try {
            // 1. Criar ou buscar empresa
            $empresaId = null;
            $empresaExistente = Database::queryOne("SELECT id FROM empresas WHERE nome = ?", [$dadosForm['empresa_nome']]);
            
            if ($empresaExistente) {
                $empresaId = $empresaExistente['id'];
            } else {
                // Criar nova empresa
                $dadosEmpresa = [
                    'nome' => $dadosForm['empresa_nome'],
                    'segmento' => $dadosForm['setor'],
                    'responsavel_id' => Auth::id()
                ];
                $empresaId = Empresa::criar($dadosEmpresa);
                
                if (!$empresaId) {
                    throw new Exception('Erro ao criar empresa');
                }
                
                // Atualizar usuário atual para ser da empresa criada se não tiver empresa
                $usuarioAtual = User::buscarPorId(Auth::id());
                if (empty($usuarioAtual['empresa_id'])) {
                    User::atualizar(Auth::id(), ['empresa_id' => $empresaId]);
                }
            }

            // 2. Salvar diagnóstico no banco
            $dadosDiagnostico = [
                'empresa_id' => $empresaId,
                'usuario_id' => Auth::id(),
                'respostas' => json_encode($dadosForm),
                'pontuacao' => 0,
                'status' => 'concluido',
                'observacoes' => 'Diagnóstico via wizard'
            ];

            // Calcular Score de Maturidade (1-4)
            $score = $this->calcularScore($dadosForm);
            $dadosDiagnostico['pontuacao'] = $score;

            $diagnosticoId = Diagnostico::criar($dadosDiagnostico);
            
            if (!$diagnosticoId) {
                throw new Exception('Erro ao salvar diagnóstico');
            }

            // Gerar resultado
            $resultado = $this->gerarResultado($dadosForm, $score);
            $resultado['diagnostico_id'] = $diagnosticoId;
            $resultado['empresa_id'] = $empresaId;

            Logger::acao('Diagnóstico empresarial salvo', [
                'empresa' => $dadosForm['empresa_nome'],
                'empresa_id' => $empresaId,
                'diagnostico_id' => $diagnosticoId,
                'score' => $score,
            ]);

            // Salvar na sessão para exibir resultado
            Session::set('diagnostico_resultado', $resultado);
            Session::set('diagnostico_dados', $dadosForm);

            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'score' => $score,
                'empresa_id' => $empresaId,
                'diagnostico_id' => $diagnosticoId,
                'mensagem' => 'Diagnóstico concluído e salvo com sucesso!',
                'redirect' => APP_URL . '/diagnostico/resultado',
            ]);
            
        } catch (Exception $e) {
            Logger::error('Erro ao salvar diagnóstico: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => false, 
                'mensagem' => 'Erro ao salvar diagnóstico: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Exibe o resultado do diagnóstico
     */
    public function resultado(): void
    {
        Auth::proteger();

        // Tentar pegar ID do diagnóstico da URL
        $diagnosticoId = null;
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if (preg_match('/\/diagnostico\/resultado\/(\d+)/', $path, $matches)) {
            $diagnosticoId = (int) $matches[1];
        }
        
        $resultado = null;
        $dadosForm = null;
        
        if ($diagnosticoId) {
            // Buscar diagnóstico específico no banco
            $diagnostico = Diagnostico::buscarPorId($diagnosticoId);
            
            if ($diagnostico && $diagnostico['usuario_id'] == Auth::id()) {
                $dadosForm = json_decode($diagnostico['respostas'], true) ?? [];
                $score = $diagnostico['pontuacao'] ?: $this->calcularScore($dadosForm);
                
                // Gerar resultado baseado nos dados
                $resultado = $this->gerarResultado($dadosForm, $score);
                $resultado['diagnostico_id'] = $diagnosticoId;
                $resultado['empresa_id'] = $diagnostico['empresa_id'];
                
                // Se há observações da IA, usar elas
                if (!empty($diagnostico['observacoes'])) {
                    $observacoesIA = json_decode($diagnostico['observacoes'], true);
                    if (is_array($observacoesIA)) {
                        $resultado = array_merge($resultado, $observacoesIA);
                    }
                }
            }
        }
        
        // Fallback para resultado na sessão (diagnóstico recém criado)
        if (!$resultado) {
            $resultado = Session::get('diagnostico_resultado');
            $dadosForm = Session::get('diagnostico_dados');
            
            // Limpar sessão após uso
            Session::remove('diagnostico_resultado');
            Session::remove('diagnostico_dados');
        }
        
        // Se ainda não houver resultado, mostrar empty state
        if (!$resultado) {
            Flash::erro('Resultado do diagnóstico não encontrado.');
            header('Location: ' . APP_URL . '/diagnostico');
            exit;
        }

        $dados = [
            'resultado' => $resultado,
            'dadosForm' => $dadosForm,
        ];

        require VIEW_PATH . '/diagnostico/resultado.php';
    }

    /**
     * Montar dados completos dos 5 blocos
     */
    private function montarDadosCompletos(array $rascunho): array
    {
        $dados = [];
        
        // Copiar todos os campos do rascunho (exceto metadados)
        $camposExcluir = ['id', 'empresa_id', 'usuario_id', 'bloco_atual', 'status', 'criado_em', 'atualizado_em'];
        
        foreach ($rascunho as $campo => $valor) {
            if (!in_array($campo, $camposExcluir) && $valor !== null) {
                // Decodificar JSON se necessário
                if (in_array($campo, ['departamentos', 'ferramentas_gestao', 'areas_vulneraveis'])) {
                    $dados[$campo] = json_decode($valor, true) ?? [];
                } else {
                    $dados[$campo] = $valor;
                }
            }
        }
        
        return $dados;
    }

    /**
     * Verificar se APIs estão configuradas
     */
    private function apiConfigurada(): bool
    {
        $openaiKey = Configuracao::buscar('api_openai_key');
        return !empty($openaiKey);
    }

    /**
     * Chamar OpenAI para análise completa
     */
    private function chamarOpenAIAnalise(array $dados, string $contextoDocumentos = ''): ?array
    {
        try {
            $prompt = ApiHelper::buildPromptDiagnostico($dados);
            
            if (!empty($contextoDocumentos)) {
                $prompt .= "\n\nDOCUMENTOS DA EMPRESA:\n" . $contextoDocumentos . "\n\nIMPORTANTE: Use as informações dos documentos para enriquecer sua análise e tornar as recomendações mais específicas e aderentes à realidade da empresa.";
            }
            
            $response = ApiHelper::chamarAnalise($prompt, true);
            
            if ($response['sucesso'] && is_array($response['conteudo'])) {
                return $response['conteudo'];
            }
            
        } catch (Exception $e) {
            Logger::error('Erro na análise IA do diagnóstico: ' . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Montar prompt para análise da IA
     */
    private function montarPromptAnalise(array $dados, string $contextoDocumentos = ''): string
    {
        $empresa = $dados['empresa_nome'] ?? 'Empresa';
        $setor = $dados['setor'] ?? 'Não informado';
        
        return "Analise o diagnóstico empresarial completo da empresa '{$empresa}' do setor '{$setor}' e retorne um JSON estruturado com:

**DADOS DA EMPRESA:**
- Nome: {$empresa}
- Setor: {$setor}
- Colaboradores: " . ($dados['colaboradores_internos'] ?? 0) . "
- Faturamento: " . ($dados['faturamento_mensal'] ?? 'Não informado') . "
- Processos documentados: " . ($dados['processos_documentados'] ?? 0) . "%
- Departamentos: " . implode(', ', $dados['departamentos'] ?? []) . "

**OPERAÇÃO ATUAL:**
- Processo de entrega: " . ($dados['processo_entrega'] ?? 'Não informado') . "
- Ferramentas: " . ($dados['ferramentas_softwares'] ?? 'Não informado') . "
- Dependências críticas: " . ($dados['dependencia_pessoa'] ?? 'Não informado') . "

**RISCOS IDENTIFICADOS:**
- Problemas: " . ($dados['problemas_operacionais'] ?? 'Nenhum reportado') . "
- Cliente concentrado: " . ($dados['cliente_concentrado'] ?? 'nao') . "
- Fornecedor insubstituível: " . ($dados['fornecedor_insubstituivel'] ?? 'nao') . "
- Processos sem backup: " . ($dados['processos_sem_backup'] ?? 'nao') . "

**OBJETIVOS:**
- Meta 12 meses: " . ($dados['objetivo_12_meses'] ?? 'Não definido') . "
- Pontos fortes: " . ($dados['pontos_fortes'] ?? 'Não informado') . "
- Pontos de melhoria: " . ($dados['pontos_melhoria'] ?? 'Não informado') . "

{$contextoDocumentos}

Com base nas informações do diagnóstico" . (!empty($contextoDocumentos) ? " e nos documentos internos da empresa" : "") . ", forneça:

Retorne JSON exatamente neste formato:
{
  \"score_maturidade\": 1-4,
  \"analise_por_area\": [
    {\"area\": \"Estratégia\", \"status\": \"adequado|atenção|crítico\", \"comentario\": \"análise detalhada\"},
    {\"area\": \"Operações\", \"status\": \"adequado|atenção|crítico\", \"comentario\": \"análise detalhada\"},
    {\"area\": \"Financeiro\", \"status\": \"adequado|atenção|crítico\", \"comentario\": \"análise detalhada\"},
    {\"area\": \"Pessoas\", \"status\": \"adequado|atenção|crítico\", \"comentario\": \"análise detalhada\"},
    {\"area\": \"Riscos\", \"status\": \"adequado|atenção|crítico\", \"comentario\": \"análise detalhada\"}
  ],
  \"mapa_de_riscos\": [
    {\"tipo\": \"categoria\", \"descricao\": \"descrição do risco\", \"criticidade\": \"alta|media|baixa\", \"acao\": \"ação recomendada\"}
  ],
  \"recomendacoes\": [\"recomendação 1\", \"recomendação 2\", \"recomendação 3\"],
  \"sops_urgentes\": [\"SOP 1 necessário\", \"SOP 2 necessário\"],
  \"sites_referencia\": [
    {\"url\": \"https://site1.com\", \"categoria\": \"Notícias do setor\"},
    {\"url\": \"https://site2.com\", \"categoria\": \"Referências técnicas\"}
  ]
}" . (!empty($contextoDocumentos) ? "

IMPORTANTE: Use as informações dos documentos internos para personalizar completamente a análise, adaptando as recomendações ao que já existe na empresa e identificando gaps específicos." : "");
    }
    }

    /**
     * Calcula o score de maturidade (1-4)
     */
    private function calcularScore(array $dados): int
    {
        $pontos = 0;
        $maxPontos = 20;

        // Processos documentados (0-100%)
        $pontos += match(true) {
            $dados['processos_documentados'] >= 75 => 4,
            $dados['processos_documentados'] >= 50 => 3,
            $dados['processos_documentados'] >= 25 => 2,
            default => 1,
        };

        // Departamentos estruturados
        $numDepts = is_array($dados['departamentos']) ? count($dados['departamentos']) : 0;
        $pontos += match(true) {
            $numDepts >= 6 => 4,
            $numDepts >= 4 => 3,
            $numDepts >= 2 => 2,
            default => 1,
        };

        // Planejamento
        $pontos += $dados['planejamento_documentado'] === 'sim' ? 4 : 1;

        // Maturidade percebida
        $pontos += match(true) {
            $dados['maturidade_percebida'] >= 4 => 4,
            $dados['maturidade_percebida'] >= 3 => 3,
            $dados['maturidade_percebida'] >= 2 => 2,
            default => 1,
        };

        // Riscos
        $temRiscos = ($dados['processos_sem_backup'] === 'sim' || $dados['fornecedor_insubstituivel'] === 'sim');
        $pontos += $temRiscos ? 1 : 4;

        // Calcular nível final (1-4)
        $percentual = ($pontos / $maxPontos) * 100;
        return match(true) {
            $percentual >= 80 => 4,
            $percentual >= 60 => 3,
            $percentual >= 40 => 2,
            default => 1,
        };
    }

    /**
     * Gera o resultado completo do diagnóstico
     */
    private function gerarResultado(array $dados, int $score): array
    {
        $niveis = [
            1 => ['label' => 'Inicial', 'cor' => '#CC2222', 'descricao' => 'A empresa está no estágio inicial de organização. Processos dependem de pessoas e não há padronização.'],
            2 => ['label' => 'Desenvolvimento', 'cor' => '#f59e0b', 'descricao' => 'A empresa está desenvolvendo processos. Há alguma documentação mas falta consistência.'],
            3 => ['label' => 'Crescimento', 'cor' => '#1a7a1a', 'descricao' => 'A empresa possui processos definidos e está em fase de crescimento estruturado.'],
            4 => ['label' => 'Excelência', 'cor' => '#1E3A5F', 'descricao' => 'A empresa opera com excelência. Processos são otimizados e mensurados continuamente.'],
        ];

        // Resumo por área
        $areas = [
            ['area' => 'Estratégia', 'status' => $score >= 3 ? 'adequado' : 'atenção', 'comentario' => $dados['planejamento_documentado'] === 'sim' ? 'Planejamento documentado e objetivos claros.' : 'Necessita formalizar planejamento estratégico.'],
            ['area' => 'Operações', 'status' => $dados['processos_documentados'] >= 50 ? 'adequado' : 'crítico', 'comentario' => $dados['processos_documentados'] . '% dos processos documentados. ' . ($dados['processos_documentados'] < 50 ? 'Urgente: mapear processos críticos.' : 'Manter evolução na documentação.')],
            ['area' => 'Financeiro', 'status' => $dados['meta_faturamento'] === 'sim' ? 'adequado' : 'atenção', 'comentario' => $dados['meta_faturamento'] === 'sim' ? 'Metas financeiras definidas.' : 'Definir metas financeiras claras e acompanháveis.'],
            ['area' => 'Pessoas', 'status' => $dados['processos_sem_backup'] === 'nao' ? 'adequado' : 'crítico', 'comentario' => $dados['processos_sem_backup'] === 'sim' ? 'RISCO: processos sem backup de conhecimento.' : 'Conhecimento distribuído adequadamente.'],
            ['area' => 'Riscos', 'status' => ($dados['fornecedor_insubstituivel'] === 'sim' || $dados['cliente_concentrado'] === 'sim') ? 'crítico' : 'adequado', 'comentario' => $dados['fornecedor_insubstituivel'] === 'sim' ? 'Dependência de fornecedor insubstituível identificada.' : 'Riscos de dependência controlados.'],
        ];

        // Mapa de riscos
        $riscos = [];
        if ($dados['processos_sem_backup'] === 'sim') {
            $riscos[] = ['tipo' => 'Operacional', 'descricao' => 'Processos sem backup de conhecimento', 'criticidade' => 'alta', 'acao' => 'Documentar SOPs e treinar equipe backup'];
        }
        if ($dados['fornecedor_insubstituivel'] === 'sim') {
            $riscos[] = ['tipo' => 'Fornecimento', 'descricao' => 'Fornecedor crítico insubstituível', 'criticidade' => 'alta', 'acao' => 'Mapear fornecedores alternativos'];
        }
        if ($dados['cliente_concentrado'] === 'sim') {
            $riscos[] = ['tipo' => 'Comercial', 'descricao' => 'Cliente com mais de 30% do faturamento', 'criticidade' => 'media', 'acao' => 'Diversificar carteira de clientes'];
        }
        if ($dados['processos_documentados'] < 30) {
            $riscos[] = ['tipo' => 'Operacional', 'descricao' => 'Baixo nível de documentação de processos', 'criticidade' => 'media', 'acao' => 'Criar programa de documentação com priorização'];
        }
        if (!empty($dados['incidentes_tipo'])) {
            $riscos[] = ['tipo' => htmlspecialchars($dados['incidentes_tipo']), 'descricao' => htmlspecialchars($dados['incidentes_descricao'] ?: 'Incidente reportado'), 'criticidade' => 'alta', 'acao' => 'Investigar causa raiz e criar plano de prevenção'];
        }

        return [
            'score' => $score,
            'nivel' => $niveis[$score],
            'pontuacao_percentual' => round(($score / 4) * 100),
            'areas' => $areas,
            'riscos' => $riscos,
            'empresa' => $dados['empresa_nome'],
        ];
    }

    /**
     * Retorna as opções para selects/multi-selects do wizard
     */
    private function getOpcoesWizard(): array
    {
        return [
            'setores' => [
                'Tecnologia', 'Varejo', 'Serviços', 'Saúde', 'Construção',
                'Educação', 'Financeiro', 'Indústria', 'Logística', 'Costura/Moda',
                'Alimentação', 'Jurídico', 'Imobiliário', 'Outro',
            ],
            'linguas' => ['Português', 'Inglês', 'Espanhol'],
            'departamentos' => [
                'Comercial', 'Marketing', 'Financeiro', 'RH', 'TI', 'Jurídico',
                'Operações', 'Atendimento', 'Projetos', 'Produção', 'Logística', 'Diretoria',
            ],
            'faturamento' => [
                'Até R$ 50 mil', 'R$ 50 mil - R$ 100 mil', 'R$ 100 mil - R$ 300 mil',
                'R$ 300 mil - R$ 500 mil', 'R$ 500 mil - R$ 1 milhão', 'Acima de R$ 1 milhão',
            ],
            'ferramentas_gestao' => [
                'ERP', 'CRM', 'Trello/Asana/Monday', 'Slack/Teams', 'Google Workspace',
                'Notion', 'Power BI', 'Excel/Planilhas', 'Sistema próprio', 'Nenhum',
            ],
            'areas_vulneraveis' => [
                'Comercial', 'Financeiro', 'Operações', 'TI/Sistemas', 'Jurídico',
                'Logística', 'RH/Pessoas', 'Marketing', 'Atendimento', 'Produção',
            ],
            'tempo_existencia' => [
                'Menos de 1 ano', '1 a 3 anos', '3 a 5 anos', '5 a 10 anos', 'Mais de 10 anos',
            ],
            'estrutura_societaria' => [
                'MEI', 'ME', 'EPP', 'Ltda', 'S/A', 'Eireli', 'SLU',
            ],
            'frequencia_reunioes' => [
                'Diária', 'Semanal', 'Quinzenal', 'Mensal', 'Esporádica', 'Não há',
            ],
        ];
    }
}
