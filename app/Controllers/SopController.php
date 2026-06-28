<?php
/**
 * SopController — Módulo de Manual Operacional com SOPs individuais
 * O Consultor — Sistema Operacional Empresarial
 *
 * PRINCÍPIO: Cada SOP é gerado INDIVIDUALMENTE com subtópicos específicos.
 * ACESSO: ADMIN/CONSULTOR (geram e editam), CLIENTE (revisa e aprova)
 */

class SopController
{
    /**
     * Tela principal — Cards por departamento (F-05 Implementation)
     */
    public function index(): void
    {
        Auth::proteger();
        
        $dados = [];

        // Para ADMIN_HOLDING, mostrar lista de empresas
        if (Auth::perfil() === 'ADMIN_HOLDING') {
            // Buscar todas as empresas
            $empresas = Database::query(
                "SELECT e.*, 
                        COUNT(s.id) as total_sops,
                        COUNT(CASE WHEN s.status = 'aprovado' THEN 1 END) as aprovados
                 FROM empresas e
                 LEFT JOIN sops s ON e.id = s.empresa_id
                 GROUP BY e.id
                 ORDER BY e.nome"
            );
            
            $dados['empresas_disponiveis'] = $empresas;
            
            // Se há uma empresa na sessão, carregá-la
            if (isset($_SESSION['empresa_selecionada'])) {
                $empresaId = $_SESSION['empresa_selecionada'];
                $empresa = Empresa::buscarPorId($empresaId);
                if ($empresa) {
                    $dados['empresa_atual'] = $this->carregarDadosEmpresa($empresaId);
                    $dados['departamentos'] = $this->getDepartamentosPorSetor($empresa['segmento'] ?? 'Tecnologia', $empresaId);
                }
            }
        } else {
            // Para outros perfis, usar empresa do usuário
            $empresaId = Auth::garantirEmpresa();
            $dados['empresa_atual'] = $this->carregarDadosEmpresa($empresaId);
            $empresa = Empresa::buscarPorId($empresaId);
            if ($empresa) {
                $dados['departamentos'] = $this->getDepartamentosPorSetor($empresa['segmento'] ?? 'Tecnologia', $empresaId);
            }
        }

        require VIEW_PATH . '/sop/index.php';
    }
    
    private function carregarDadosEmpresa(int $empresaId): array
    {
        $empresa = Empresa::buscarPorId($empresaId);
        if (!$empresa) {
            Flash::set('erro', 'Empresa não encontrada.');
            header('Location: ' . APP_URL . '/dashboard');
            exit;
        }

        $stats = Sop::estatisticas($empresaId);
        $diagnostico = Diagnostico::buscarUltimoPorEmpresa($empresaId);
        
        return [
            'id' => $empresa['id'],
            'nome' => $empresa['nome'],
            'segmento' => $empresa['segmento'] ?? 'Tecnologia',
            'maturidade' => $empresa['score_maturidade'] ?? 2,
            'norma' => ApiHelper::getNormasPorSetor($empresa['segmento'] ?? 'Tecnologia'),
            'total_sops' => $stats['total'],
            'aprovados' => $stats['aprovados'],
            'diagnostico_id' => $diagnostico['id'] ?? null,
        ];
    }

    /**
     * Interface para criar/gerenciar SOPs personalizados
     */
    public function gerenciarSOPs(): void
    {
        Auth::proteger();
        Auth::exigirPerfil([Auth::ADMIN_HOLDING, Auth::CONSULTOR_INTERNO]);
        
        $empresaId = (int) ($_GET['empresa_id'] ?? Auth::empresa());
        
        if (!$empresaId) {
            Flash::set('erro', 'Empresa não especificada.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }
        
        // Buscar empresa
        $empresa = Database::queryOne(
            "SELECT * FROM empresas WHERE id = :id",
            ['id' => $empresaId]
        );
        
        if (!$empresa) {
            Flash::set('erro', 'Empresa não encontrada.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }
        
        // Buscar SOPs customizados da empresa
        $sopsCustomizados = Database::query(
            "SELECT * FROM sops_customizados WHERE empresa_id = :empresa_id ORDER BY departamento, nome",
            ['empresa_id' => $empresaId]
        );
        
        $dados = [
            'empresa' => $empresa,
            'sops_customizados' => $sopsCustomizados,
            'setores_disponiveis' => $this->getSetoresDisponiveis(),
            'icone_helper' => function($icone) { return $this->converterIconeParaEmoji($icone); }
        ];
        
        require VIEW_PATH . '/sop/gerenciar.php';
    }

    /**
     * Adicionar novo SOP personalizado
     */
    public function adicionarSOP(): void
    {
        Auth::proteger();
        Auth::exigirPerfil([Auth::ADMIN_HOLDING, Auth::CONSULTOR_INTERNO]);
        Csrf::verificar();
        
        $empresaId = (int) ($_POST['empresa_id'] ?? 0);
        $sopCodigo = strtoupper(trim($_POST['sop_codigo'] ?? ''));
        $nome = htmlspecialchars(trim($_POST['nome'] ?? ''));
        $departamento = htmlspecialchars(trim($_POST['departamento'] ?? ''));
        $descricao = htmlspecialchars(trim($_POST['descricao'] ?? ''));
        
        // Validações
        if (!$empresaId || empty($sopCodigo) || empty($nome) || empty($departamento)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Todos os campos são obrigatórios.']);
            exit;
        }
        
        // Verificar se código já existe
        $existe = Database::queryOne(
            "SELECT id FROM sops_customizados WHERE empresa_id = :empresa_id AND sop_codigo = :codigo",
            ['empresa_id' => $empresaId, 'codigo' => $sopCodigo]
        );
        
        if ($existe) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Código SOP já existe para esta empresa.']);
            exit;
        }
        
        try {
            // Inserir SOP customizado
            Database::execute(
                "INSERT INTO sops_customizados (empresa_id, sop_codigo, nome, departamento, descricao, criado_por, criado_em) 
                 VALUES (:empresa_id, :codigo, :nome, :depto, :descricao, :user_id, NOW())",
                [
                    'empresa_id' => $empresaId,
                    'codigo' => $sopCodigo,
                    'nome' => $nome,
                    'depto' => $departamento,
                    'descricao' => $descricao,
                    'user_id' => Auth::id()
                ]
            );
            
            Logger::acao('SOP personalizado criado', [
                'empresa_id' => $empresaId,
                'sop_codigo' => $sopCodigo,
                'nome' => $nome
            ]);
            
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true, 'mensagem' => 'SOP personalizado criado com sucesso!']);
            
        } catch (Exception $e) {
            Logger::error('Erro ao criar SOP personalizado: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno ao criar SOP.']);
        }
        exit;
    }

    /**
     * Remover SOP personalizado
     */
    public function removerSOP(): void
    {
        Auth::proteger();
        Auth::exigirPerfil([Auth::ADMIN_HOLDING, Auth::CONSULTOR_INTERNO]);
        Csrf::verificar();
        
        $sopId = (int) ($_POST['sop_id'] ?? 0);
        
        if (!$sopId) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'ID do SOP é obrigatório.']);
            exit;
        }
        
        try {
            // Verificar se SOP existe e buscar dados
            $sop = Database::queryOne(
                "SELECT * FROM sops_customizados WHERE id = :id",
                ['id' => $sopId]
            );
            
            if (!$sop) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'SOP não encontrado.']);
                exit;
            }
            
            // Remover SOP customizado
            Database::execute("DELETE FROM sops_customizados WHERE id = :id", ['id' => $sopId]);
            
            // Remover SOP gerado se existir
            Database::execute(
                "DELETE FROM sops WHERE sop_codigo = :codigo AND empresa_id = :empresa_id",
                ['codigo' => $sop['sop_codigo'], 'empresa_id' => $sop['empresa_id']]
            );
            
            Logger::acao('SOP personalizado removido', [
                'sop_id' => $sopId,
                'sop_codigo' => $sop['sop_codigo']
            ]);
            
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true, 'mensagem' => 'SOP removido com sucesso!']);
            
        } catch (Exception $e) {
            Logger::error('Erro ao remover SOP: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno.']);
        }
        exit;
    }

    /**
     * Retorna lista de setores disponíveis
     */
    private function getSetoresDisponiveis(): array
    {
        return [
            'Construção Civil' => 'Construção Civil',
            'Tecnologia' => 'Tecnologia',
            'Saúde' => 'Saúde',
            'Educação' => 'Educação',
            'Consultoria' => 'Consultoria',
            'Varejo' => 'Varejo',
            'Indústria' => 'Indústria',
            'Alimentício' => 'Alimentício',
            'Geral' => 'Outros Setores'
        ];
    }

    /**
     * Converte ícones de texto para emojis para exibição
     */
    private function converterIconeParaEmoji(string $icone): string
    {
        $mapeamento = [
            'documento' => '📋',
            'pacote' => '📦',
            'chave' => '🔑',
            'comercial' => '💼',
            'operacional' => '⚙️',
            'administrativo' => '📊',
            'financeiro' => '💰',
            'rh' => '👥',
            'juridico' => '⚖️',
            'ti' => '💻',
            'vendas' => '🛒',
            'producao' => '🏭',
            'saude' => '🏥',
            'educacao' => '🎓',
            'construcao' => '🚧',
            'alimenticio' => '🍽️',
            'consultoria' => '📈',
            'seguranca' => '🔒',
            'logistica' => '🚚'
        ];
        
        return $mapeamento[strtolower($icone)] ?? '📋';
    }

    /**
     * Gera um SOP individual via AJAX (F-05 Implementation)
     */
    public function gerar(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $sopCodigo = htmlspecialchars(trim($_POST['sop_id'] ?? ''));
        $sopNome = htmlspecialchars(trim($_POST['sop_nome'] ?? ''));
        $empresaId = Auth::garantirEmpresa();

        if (empty($sopCodigo) || !$empresaId) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Dados obrigatórios não informados.']);
            exit;
        }

        // Verificar se SOP já existe
        $sopExistente = Sop::buscarPorCodigo($sopCodigo);
        if ($sopExistente) {
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'SOP já existe. Redirecionando para revisão.',
                'redirect' => APP_URL . '/sop/revisar?id=' . $sopExistente['id'],
            ]);
            exit;
        }

        // Buscar dados da empresa e diagnóstico
        $empresa = Empresa::buscarPorId($empresaId);
        $diagnostico = Diagnostico::buscarUltimoPorEmpresa($empresaId);
        
        if (!$empresa) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Empresa não encontrada.']);
            exit;
        }

        // Montar dados completos da empresa para o prompt
        $empresaDados = [
            'nome' => $empresa['nome'],
            'setor' => $empresa['segmento'] ?? 'Tecnologia',
            'colaboradores' => $diagnostico ? $this->extrairColaboradores($diagnostico) : '10-25',
            'faturamento' => $diagnostico ? $this->extrairFaturamento($diagnostico) : 'R$ 100-300 mil',
            'maturidade' => $empresa['score_maturidade'] ?? 2,
            'departamentos' => $diagnostico ? $this->extrairDepartamentos($diagnostico) : 'Comercial, TI, Operações, Financeiro',
            'ferramentas' => $diagnostico ? $this->extrairFerramentas($diagnostico) : 'E-mail, WhatsApp, Excel',
            'problemas' => $diagnostico ? $this->extrairProblemas($diagnostico) : 'Processos não documentados',
            'objetivos' => $diagnostico ? $this->extrairObjetivos($diagnostico) : 'Crescer com processos organizados',
        ];

        $sopData = [
            'id' => $sopCodigo,
            'nome' => $sopNome,
            'departamento' => $this->getDepartamentoPorId($sopCodigo),
            'subtopicos_texto' => $this->getSubtopicosPorId($sopCodigo),
        ];

        // Buscar documentos relevantes da empresa para enriquecer o SOP
        $documentosRelevantes = [];
        $contextoDocumentos = '';
        
        try {
            $areas = [$this->getDepartamentoPorId($sopCodigo)];
            // Verificar se a classe DocumentoProcessor está disponível
            if (class_exists('DocumentoProcessor')) {
                $documentosRelevantes = DocumentoProcessor::buscarDocumentosRelevantes($empresaId, $areas);
                $contextoDocumentos = DocumentoProcessor::construirContextoDocumentos($documentosRelevantes);
            }
            
            Logger::info('Documentos encontrados para SOP', [
                'sop_codigo' => $sopCodigo,
                'documentos_encontrados' => count($documentosRelevantes)
            ]);
        } catch (Exception $e) {
            Logger::warning('Erro ao buscar documentos para SOP', [
                'sop_codigo' => $sopCodigo,
                'erro' => $e->getMessage()
            ]);
        }

        // Gerar prompt estruturado com contexto dos documentos
        $prompt = ApiHelper::buildPromptSop($empresaDados, $sopData, $contextoDocumentos);

        // Chamar IA (GPT ou Claude conforme config)
        $resultado = ApiHelper::chamarAnalise($prompt, true);

        if ($resultado['sucesso'] && is_array($resultado['conteudo'])) {
            // Sucesso: criar SOP no banco
            $sopId = $this->criarSopNoBanco($sopCodigo, $sopNome, $empresaId, $diagnostico['id'] ?? null, $resultado['conteudo']);
            
            if ($sopId) {
                // Registrar uso dos documentos que contribuíram para o SOP
                if (class_exists('DocumentoProcessor') && !empty($documentosRelevantes)) {
                    foreach ($documentosRelevantes as $doc) {
                        DocumentoProcessor::registrarUso($doc['id'], $empresaId, Auth::id(), 'sop_geracao', $sopId);
                    }
                }
                
                Logger::acao('SOP gerado via IA', [
                    'sop_codigo' => $sopCodigo, 
                    'sop_id' => $sopId,
                    'documentos_utilizados' => count($documentosRelevantes)
                ]);
                header('Content-Type: application/json');
                echo json_encode([
                    'sucesso' => true,
                    'mensagem' => 'SOP gerado com sucesso!' . 
                        (count($documentosRelevantes) > 0 ? " Utilizou {count($documentosRelevantes)} documento(s) da empresa." : ''),
                    'redirect' => APP_URL . '/sop/revisar?id=' . $sopId,
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Erro ao salvar SOP no banco de dados.']);
            }
        } else {
            // IA falhou: criar SOP básico e avisar
            $sopId = $this->criarSopBasico($sopCodigo, $sopNome, $empresaId, $diagnostico['id'] ?? null);
            Logger::warning('SOP gerado básico (IA falhou)', ['sop_codigo' => $sopCodigo, 'erro' => $resultado['erro']]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'SOP gerado! (Alguns campos podem precisar de ajuste.)',
                'redirect' => APP_URL . '/sop/revisar?id=' . $sopId,
                'aviso' => $resultado['erro'],
            ]);
        }
        exit;
    }

    /**
     * Formata resposta da IA para o formato esperado pela view
     */
    private function formatarRespostaIA(array $ia, string $sopId, string $sopNome, array $empresa): array
    {
        return [
            'id' => $sopId,
            'nome' => $sopNome,
            'versao' => $ia['versionamento']['versao'] ?? '1.0',
            'empresa' => $empresa['nome'],
            'setor' => $empresa['segmento'] ?? 'Tecnologia',
            'norma' => ApiHelper::getNormasPorSetor($empresa['segmento'] ?? 'Tecnologia'),
            'objetivo' => $ia['objetivo'] ?? '',
            'escopo_aplica' => $ia['escopo']['aplica_se'] ?? '',
            'escopo_nao_aplica' => $ia['escopo']['nao_aplica'] ?? '',
            'subtopicos' => $ia['subtopicos'] ?? [],
            'responsaveis' => $ia['responsaveis'] ?? [],
            'prerequisitos' => $ia['prerequisitos'] ?? [],
            'ferramentas' => $ia['ferramentas'] ?? [],
            'procedimento_subtopico_1' => $ia['procedimentos'][0]['passos'] ?? [],
            'checklist' => $ia['checklist'] ?? [],
            'evidencias' => $ia['evidencias'] ?? [],
            'relatorios' => $ia['relatorios'] ?? [],
            'kpis' => $ia['kpis'] ?? [],
            'contencao_n1' => $ia['contencao']['n1'] ?? [],
            'contencao_n2' => $ia['contencao']['n2'] ?? [],
            'contencao_n3' => $ia['contencao']['n3'] ?? [],
        ];
    }

    /**
     * Retorna departamento a partir do ID do SOP
     */
    private function getDepartamentoPorId(string $sopId): string
    {
        $map = ['COM' => 'Comercial', 'ONB' => 'Onboarding', 'OPS' => 'Operacional', 'FIN' => 'Financeiro', 'JUR' => 'Jurídico', 'RH' => 'RH'];
        preg_match('/SOP-\w+-(\w+)-/', $sopId, $m);
        return $map[$m[1] ?? ''] ?? 'Geral';
    }

    /**
     * Retorna subtópicos pré-definidos para o SOP
     */
    private function getSubtopicosPorId(string $sopId): string
    {
        $subtopicos = [
            'SOP-TI-ONB-001' => "- Subtópico A: Fornecedor Amistoso (quando coopera)\n- Subtópico B: Fornecedor Não Amistoso (quando dificulta)\n- Subtópico C: Etapas Seguras de Migração (sequência sem downtime)",
            'SOP-TI-OPS-002' => "- Subtópico A: Configuração da Rotina de Backup\n- Subtópico B: Validação e Teste de Restauração\n- Subtópico C: Resposta a Falha de Backup",
            'SOP-TI-OPS-001' => "- Subtópico A: Recebimento e Classificação do Chamado\n- Subtópico B: Escalonamento e Priorização\n- Subtópico C: Resolução e Fechamento com Validação do Cliente",
        ];
        return $subtopicos[$sopId] ?? "- Subtópico A: Preparação e planejamento\n- Subtópico B: Execução e monitoramento\n- Subtópico C: Verificação e encerramento";
    }

    /**
     * Tela de revisão do SOP gerado (13 componentes) — F-05 Implementation
     */
    public function revisar(): void
    {
        Auth::proteger();

        $sopId = (int) ($_GET['id'] ?? 0);
        if (!$sopId) {
            Flash::set('erro', 'SOP não encontrado.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }

        // Buscar SOP no banco
        $sop = Sop::buscarPorId($sopId);
        if (!$sop || !Auth::podeAcessarEmpresa($sop['empresa_id'])) {
            Flash::set('erro', 'SOP não encontrado ou sem permissão.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }

        // Decodificar conteúdo completo
        $conteudoCompleto = json_decode($sop['conteudo_completo'], true);
        if (!$conteudoCompleto) {
            Flash::set('erro', 'Conteúdo do SOP não encontrado ou corrompido.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }

        // Formatar dados para a view (manter compatibilidade com view existente)
        $sopFormatado = $this->formatarSopParaView($sop, $conteudoCompleto);

        $dados = [
            'sop' => $sopFormatado,
        ];

        require VIEW_PATH . '/sop/revisar.php';
    }

    /**
     * Aprova SOP via AJAX — F-05 Implementation
     */
    public function aprovar(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $sopId = (int) ($_POST['sop_id'] ?? 0);
        $usuarioId = Auth::usuarioId();

        if (!$sopId || !$usuarioId) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Dados obrigatórios não informados.']);
            exit;
        }

        // Verificar permissão
        $sop = Sop::buscarPorId($sopId);
        if (!$sop || !Auth::podeAcessarEmpresa($sop['empresa_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'SOP não encontrado ou sem permissão.']);
            exit;
        }

        if ($sop['status'] === 'ativo') {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'SOP já está aprovado.']);
            exit;
        }

        // Aprovar SOP (salva KPIs e contingência automaticamente)
        $sucesso = Sop::aprovar($sopId, $usuarioId);

        if ($sucesso) {
            Logger::acao('SOP aprovado', ['sop_id' => $sopId, 'sop_codigo' => $sop['sop_codigo']]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true, 'mensagem' => 'SOP aprovado! KPIs e planos de contingência foram salvos automaticamente.']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno ao aprovar SOP.']);
        }
        exit;
    }

    /**
     * Salva rascunho via AJAX — F-05 Implementation
     */
    public function salvarRascunho(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $sopId = (int) ($_POST['sop_id'] ?? 0);
        
        if (!$sopId) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'ID do SOP é obrigatório.']);
            exit;
        }

        // Verificar permissão
        $sop = Sop::buscarPorId($sopId);
        if (!$sop || !Auth::podeAcessarEmpresa($sop['empresa_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'SOP não encontrado ou sem permissão.']);
            exit;
        }

        // Processar campos editados enviados via POST
        $camposEditados = [];
        $camposPermitidos = [
            'objetivo', 'escopo_aplica', 'escopo_nao_aplica', 'subtopicos', 
            'responsaveis', 'prerequisitos', 'ferramentas', 'checklist', 
            'evidencias', 'relatorios', 'kpis', 'contencao_n1', 'contencao_n2', 'contencao_n3'
        ];
        
        foreach ($camposPermitidos as $campo) {
            if (isset($_POST[$campo])) {
                $camposEditados[$campo] = is_array($_POST[$campo]) ? $_POST[$campo] : trim($_POST[$campo]);
            }
        }
        
        // Se há campos editados, atualizar o conteúdo
        if (!empty($camposEditados)) {
            $conteudoAtual = json_decode($sop['conteudo_completo'], true) ?: [];
            $conteudoAtualizado = array_merge($conteudoAtual, $camposEditados);
            
            // Salvar conteúdo atualizado
            $sucesso = Database::execute(
                "UPDATE sops SET conteudo_completo = :conteudo, atualizado_em = NOW() WHERE id = :id",
                [
                    'conteudo' => json_encode($conteudoAtualizado, JSON_UNESCAPED_UNICODE),
                    'id' => $sopId
                ]
            );
            
            if (!$sucesso) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Erro ao salvar alterações.']);
                exit;
            }
        }
        
        Logger::acao('SOP salvo como rascunho', ['sop_id' => $sopId, 'sop_codigo' => $sop['sop_codigo']]);

        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'mensagem' => 'Rascunho salvo!']);
        exit;
    }

    /**
     * Visualizar SOP completo com subtópicos — F-06 Implementation
     */
    public function ver(): void
    {
        Auth::proteger();

        $sopId = (int) ($_GET['id'] ?? 0);
        if (!$sopId) {
            Flash::set('erro', 'SOP não encontrado.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }

        // Buscar SOP
        $sop = Sop::buscarPorId($sopId);
        if (!$sop || !Auth::podeAcessarEmpresa($sop['empresa_id'])) {
            Flash::set('erro', 'SOP não encontrado ou sem permissão.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }

        if ($sop['status'] !== 'ativo') {
            Flash::set('aviso', 'Este SOP ainda não foi aprovado.');
            header('Location: ' . APP_URL . '/sop/revisar?id=' . $sopId);
            exit;
        }

        // Decodificar conteúdo completo
        $conteudoCompleto = json_decode($sop['conteudo_completo'], true);
        if (!$conteudoCompleto) {
            Flash::set('erro', 'Conteúdo do SOP não encontrado.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }

        // Buscar empresa
        $empresa = Empresa::buscarPorId($sop['empresa_id']);

        // Buscar alertas ativos para este SOP
        $alertasAtivos = $this->buscarAlertasAtivos($sopId);

        // Buscar KPIs do SOP
        $kpis = Sop::buscarKpis($sopId);

        // Formatar dados para view
        $sopFormatado = $this->formatarSopParaViewCompleta($sop, $conteudoCompleto, $empresa, $kpis);

        $dados = [
            'sop' => $sopFormatado,
            'alertas_ativos' => $alertasAtivos,
        ];

        require VIEW_PATH . '/sop/ver.php';
    }

    /**
     * Carregar planos de contingência via AJAX — F-06 Implementation
     */
    public function contencao(): void
    {
        Auth::proteger();

        $sopId = (int) ($_GET['id'] ?? 0);
        $nivelSugerido = $_GET['nivel'] ?? 'N1';

        if (!$sopId) {
            echo '<div class="text-center py-8 text-red-600">SOP não encontrado.</div>';
            exit;
        }

        // Buscar SOP
        $sop = Sop::buscarPorId($sopId);
        if (!$sop || !Auth::podeAcessarEmpresa($sop['empresa_id'])) {
            echo '<div class="text-center py-8 text-red-600">SOP não encontrado ou sem permissão.</div>';
            exit;
        }

        // Buscar planos de contingência
        $contencoes = Sop::buscarContencoes($sopId);
        $planos = [];
        
        foreach ($contencoes as $contencao) {
            $nivel = strtolower($contencao['nivel']);
            $planos[$nivel] = [
                'situacao' => $contencao['situacao'],
                'acao' => $contencao['acao'], 
                'quem' => $contencao['responsavel'],
                'escalar' => $contencao['escalar_se'],
                'comunicacao' => $contencao['comunicacao'],
                'documentacao' => $contencao['documentacao_obrigatoria'],
                'advogado_responsavel' => $contencao['nivel'] === 'N3' ? 'Dr. João Silva - (11) 99999-9999' : null,
            ];
        }

        // Buscar histórico de acionamentos
        $historicoContencao = $this->buscarHistoricoContencao($sopId);

        // Incluir view (não layout, é AJAX)
        include VIEW_PATH . '/sop/contencao.php';
    }

    /**
     * Acionar plano de contingência — F-06 Implementation  
     */
    public function acionarContencao(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $sopId = (int) ($_POST['sop_id'] ?? 0);
        $nivel = $_POST['nivel'] ?? '';
        $situacaoDetectada = trim($_POST['situacao_detectada'] ?? '');

        if (!$sopId || !in_array($nivel, ['N1', 'N2', 'N3']) || empty($situacaoDetectada)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Dados obrigatórios não informados.']);
            exit;
        }

        // Verificar permissão
        $sop = Sop::buscarPorId($sopId);
        if (!$sop || !Auth::podeAcessarEmpresa($sop['empresa_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'SOP não encontrado ou sem permissão.']);
            exit;
        }

        // Buscar plano de contingência específico
        $contencao = Database::queryOne(
            "SELECT * FROM sop_contencoes WHERE sop_id = :sop_id AND nivel = :nivel LIMIT 1",
            ['sop_id' => $sopId, 'nivel' => $nivel]
        );

        if (!$contencao) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Plano de contingência não encontrado.']);
            exit;
        }

        // Registrar ocorrência
        $sucesso = Database::execute(
            "INSERT INTO ocorrencias_contencao (empresa_id, sop_id, contencao_id, nivel, situacao_detectada, responsavel_execucao, usuario_responsavel) 
             VALUES (:empresa_id, :sop_id, :contencao_id, :nivel, :situacao_detectada, :responsavel_execucao, :usuario_responsavel)",
            [
                'empresa_id' => $sop['empresa_id'],
                'sop_id' => $sopId,
                'contencao_id' => $contencao['id'],
                'nivel' => $nivel,
                'situacao_detectada' => $situacaoDetectada,
                'responsavel_execucao' => $contencao['responsavel'],
                'usuario_responsavel' => Auth::usuarioId(),
            ]
        );

        if ($sucesso) {
            Logger::acao('Contingência acionada', [
                'sop_id' => $sopId,
                'nivel' => $nivel,
                'situacao' => $situacaoDetectada
            ]);

            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true, 'mensagem' => "Contingência {$nivel} acionada com sucesso."]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao registrar acionamento.']);
        }
        exit;
    }
    /**
     * API para retornar função RACI de um cargo específico
     */
    public function getRaciFuncao(): void
    {
        Auth::proteger();
        
        $sopId = htmlspecialchars(trim($_GET['sop_id'] ?? ''));
        $cargo = htmlspecialchars(trim($_GET['cargo'] ?? ''));
        
        if (empty($sopId) || empty($cargo)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'SOP ID e cargo são obrigatórios.']);
            exit;
        }
        
        try {
            // Buscar matriz RACI do banco (se existir)
            $raciMatriz = Database::queryOne(
                "SELECT matriz_raci FROM sops WHERE sop_codigo = :sop_codigo",
                ['sop_codigo' => $sopId]
            );
            
            if ($raciMatriz && $raciMatriz['matriz_raci']) {
                $matriz = json_decode($raciMatriz['matriz_raci'], true);
                $funcao = $matriz[$cargo] ?? 'I';
            } else {
                // Usar matriz RACI padrão por tipo de SOP
                $funcao = $this->getFuncaoRaciPadrao($sopId, $cargo);
            }
            
            $descricoes = [
                'R' => 'Responsável - Executa a atividade',
                'A' => 'Aprovador - Aprova e é accountable pelo resultado',
                'C' => 'Consultado - Fornece input/conhecimento',
                'I' => 'Informado - Recebe informação sobre o resultado',
                '-' => 'Não envolvido'
            ];
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'funcao' => $funcao,
                'descricao' => $descricoes[$funcao] ?? 'Função não definida',
                'sop_id' => $sopId,
                'cargo' => $cargo
            ]);
            
        } catch (Exception $e) {
            Logger::error('Erro ao buscar função RACI', ['erro' => $e->getMessage(), 'sop_id' => $sopId, 'cargo' => $cargo]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno.']);
        }
        exit;
    }

    /**
     * Retorna função RACI padrão baseada no tipo de SOP e cargo
     */
    private function getFuncaoRaciPadrao(string $sopId, string $cargo): string
    {
        // Mapear funções RACI baseado no padrão de SOP
        $raciPadroes = [
            // SOPs Comerciais
            'SOP-TI-COM-001' => [
                'Diretor TI' => 'A', 'Gerente Ops' => 'R', 'Analista N2' => 'C', 
                'Suporte N1' => 'I', 'Financeiro' => '-', 'Jurídico' => '-', 'RH' => '-'
            ],
            // SOPs Onboarding
            'SOP-TI-ONB-001' => [
                'Diretor TI' => 'A', 'Gerente Ops' => 'R', 'Analista N2' => 'R', 
                'Suporte N1' => 'C', 'Financeiro' => 'I', 'Jurídico' => 'C', 'RH' => '-'
            ],
            // SOPs Operacionais
            'SOP-TI-OPS-001' => [
                'Diretor TI' => 'I', 'Gerente Ops' => 'A', 'Analista N2' => 'R', 
                'Suporte N1' => 'R', 'Financeiro' => '-', 'Jurídico' => '-', 'RH' => '-'
            ],
            'SOP-TI-OPS-002' => [
                'Diretor TI' => 'I', 'Gerente Ops' => 'A', 'Analista N2' => 'R', 
                'Suporte N1' => 'C', 'Financeiro' => '-', 'Jurídico' => 'I', 'RH' => '-'
            ],
            // SOPs Financeiros
            'SOP-TI-FIN-001' => [
                'Diretor TI' => 'I', 'Gerente Ops' => 'C', 'Analista N2' => '-', 
                'Suporte N1' => '-', 'Financeiro' => 'R', 'Jurídico' => 'I', 'RH' => '-'
            ],
            // SOPs Jurídicos
            'SOP-TI-JUR-001' => [
                'Diretor TI' => 'A', 'Gerente Ops' => 'C', 'Analista N2' => 'I', 
                'Suporte N1' => 'I', 'Financeiro' => '-', 'Jurídico' => 'R', 'RH' => 'C'
            ],
        ];
        
        // Para SOPs de outros setores, usar padrão genérico baseado no departamento
        if (!isset($raciPadroes[$sopId])) {
            if (strpos($sopId, '-COM-') !== false) {
                return in_array($cargo, ['Diretor TI', 'Gerente Comercial']) ? 'A' : 
                       (in_array($cargo, ['Vendedor', 'Analista Comercial']) ? 'R' : 'I');
            } elseif (strpos($sopId, '-FIN-') !== false) {
                return in_array($cargo, ['Diretor Financeiro', 'Controller']) ? 'A' :
                       (in_array($cargo, ['Analista Financeiro', 'Contador']) ? 'R' : 'I');
            }
            return 'I'; // Padrão para SOPs não mapeados
        }
        
        return $raciPadroes[$sopId][$cargo] ?? 'I';
    }

    public function raci(): void
    {
        Auth::proteger();

        $dados = [
            'cargos' => ['Diretor TI', 'Gerente Ops', 'Analista N2', 'Suporte N1', 'Financeiro', 'Jurídico', 'RH'],
            'sops' => [
                ['id' => 'SOP-TI-COM-001', 'nome' => 'Prospecção e qualificação', 'raci' => ['A', 'R', 'C', 'I', '-', '-', '-']],
                ['id' => 'SOP-TI-ONB-001', 'nome' => 'Recebimento e migração', 'raci' => ['A', 'R', 'R', 'C', 'I', 'C', '-']],
                ['id' => 'SOP-TI-OPS-001', 'nome' => 'Gestão de chamados e SLA', 'raci' => ['I', 'A', 'R', 'R', '-', '-', '-']],
                ['id' => 'SOP-TI-OPS-002', 'nome' => 'Rotina de segurança e backups', 'raci' => ['I', 'A', 'R', 'C', '-', 'I', '-']],
                ['id' => 'SOP-TI-FIN-001', 'nome' => 'Faturamento e cobrança', 'raci' => ['I', 'C', '-', '-', 'R', 'I', '-']],
                ['id' => 'SOP-TI-JUR-001', 'nome' => 'LGPD e tratamento de dados', 'raci' => ['A', 'C', 'I', 'I', '-', 'R', 'C']],
            ],
        ];

        require VIEW_PATH . '/sop/raci.php';
    }

    /**
     * Solicita ajuste da IA em seção específica — F-05 Implementation
     */
    public function ajustar(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $sopId = (int) ($_POST['sop_id'] ?? 0);
        $instrucao = trim($_POST['instrucao'] ?? '');
        $secoesAjustar = $_POST['secoes_a_ajustar'] ?? [];

        if (!$sopId || empty($instrucao)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'SOP ID e instruções são obrigatórios.']);
            exit;
        }

        // Verificar permissão
        $sop = Sop::buscarPorId($sopId);
        if (!$sop || !Auth::podeAcessarEmpresa($sop['empresa_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'SOP não encontrado ou sem permissão.']);
            exit;
        }

        $conteudoAtual = json_decode($sop['conteudo_completo'], true);
        if (!$conteudoAtual) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Conteúdo do SOP não encontrado.']);
            exit;
        }

        // Montar prompt para ajuste específico
        $promptAjuste = $this->buildPromptAjuste($conteudoAtual, $instrucao, $secoesAjustar);
        
        // Chamar IA
        $resultado = ApiHelper::chamarAnalise($promptAjuste, true);

        if ($resultado['sucesso'] && is_array($resultado['conteudo'])) {
            // Mesclar seções atualizadas
            $conteudoNovo = $this->mesclarConteudo($conteudoAtual, $resultado['conteudo'], $secoesAjustar);
            
            // Incrementar versão
            $versaoNova = Sop::incrementarVersao($sop['versao']);
            
            // Salvar histórico
            Sop::salvarHistorico($sopId, $sop['versao'], $versaoNova, $conteudoAtual, $instrucao, Auth::usuarioId());
            
            // Atualizar SOP
            $sucesso = Sop::atualizar($sopId, [
                'conteudo_completo' => $conteudoNovo,
                'versao' => $versaoNova,
                'motivo_alteracao' => $instrucao,
            ]);

            if ($sucesso) {
                Logger::acao('SOP ajustado via IA', ['sop_id' => $sopId, 'versao_nova' => $versaoNova]);
                header('Content-Type: application/json');
                echo json_encode([
                    'sucesso' => true, 
                    'mensagem' => "SOP atualizado para versão {$versaoNova}. Recarregando...",
                    'versao' => $versaoNova
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Erro ao salvar ajustes.']);
            }
        } else {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => $resultado['erro'] ?? 'IA não disponível para ajustes.']);
        }
        exit;
    }

    /**
     * Monta prompt para ajuste específico
     */
    private function buildPromptAjuste(array $conteudoAtual, string $instrucao, array $secoes): string
    {
        $conteudoJson = json_encode($conteudoAtual, JSON_UNESCAPED_UNICODE);
        $secoesStr = implode(', ', $secoes);

        return "SOP atual (JSON): {$conteudoJson}

INSTRUÇÃO DE AJUSTE: {$instrucao}

Seções a ajustar: {$secoesStr}

Regere APENAS as seções indicadas mantendo a mesma estrutura JSON. As demais seções devem permanecer EXATAMENTE iguais.
Responda APENAS com JSON válido contendo as seções atualizadas.";
    }

    /**
     * Mescla conteúdo atualizado com o existente
     */
    private function mesclarConteudo(array $original, array $atualizado, array $secoes): array
    {
        foreach ($secoes as $secao) {
            if (isset($atualizado[$secao])) {
                $original[$secao] = $atualizado[$secao];
            }
        }
        return $original;
    }
    public function kpis(): void
    {
        Auth::proteger();
        
        $empresaId = Auth::garantirEmpresa();

        // Buscar KPIs de todos os SOPs aprovados da empresa
        $kpisReais = Database::query(
            "SELECT k.*, s.titulo as sop_titulo, s.sop_codigo 
             FROM sop_kpis k 
             JOIN sops s ON k.sop_id = s.id 
             WHERE k.empresa_id = :empresa_id AND k.ativo = 1 AND s.status = 'ativo'
             ORDER BY k.zona_atual DESC, k.nome",
            ['empresa_id' => $empresaId]
        );

        // Se não há KPIs reais, mostrar empty state
        if (empty($kpisReais)) {
            $dados = ['kpis' => []];
        } else {
            $dados = ['kpis' => $this->formatarKpisParaView($kpisReais)];
        }

        require VIEW_PATH . '/sop/kpis.php';
    }

    /**
     * Registra valor de KPI via AJAX — F-06 Enhanced
     */
    public function registrarKpi(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $kpiId = (int) ($_POST['kpi_id'] ?? 0);
        $valorAtual = trim($_POST['valor'] ?? '');

        if (!$kpiId || empty($valorAtual)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'KPI ID e valor são obrigatórios.']);
            exit;
        }

        // Buscar KPI
        $kpi = Database::queryOne("SELECT * FROM sop_kpis WHERE id = :id AND empresa_id = :empresa_id", [
            'id' => $kpiId,
            'empresa_id' => Auth::empresa()
        ]);

        if (!$kpi) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'KPI não encontrado.']);
            exit;
        }

        // Determinar zona baseada no valor
        $zonaAtual = $this->determinarZonaKPI($valorAtual, $kpi);

        // Atualizar KPI
        $sucesso = Sop::atualizarKpi($kpiId, $valorAtual, $zonaAtual);

        if ($sucesso) {
            Logger::acao('KPI valor registrado', [
                'kpi_id' => $kpiId,
                'kpi_nome' => $kpi['nome'],
                'valor' => $valorAtual,
                'zona' => $zonaAtual
            ]);

            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Valor registrado!',
                'zona' => $zonaAtual
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao atualizar KPI.']);
        }
        exit;
    }

    /**
     * Determinar zona do KPI baseada no valor
     */
    private function determinarZonaKPI(string $valor, array $kpi): string
    {
        // Lógica simplificada - pode ser expandida para diferentes tipos de KPI
        $valorNumerico = (float) preg_replace('/[^\d.]/', '', $valor);
        
        // Para KPIs de percentual
        if (strpos($valor, '%') !== false) {
            if ($valorNumerico >= 95) return 'verde';
            if ($valorNumerico >= 80) return 'amarela';
            return 'vermelha';
        }
        
        // Para KPIs de tempo (assumindo que menor é melhor)
        if (strpos($valor, 'min') !== false || strpos($valor, 'h') !== false) {
            if ($valorNumerico <= 30) return 'verde';
            if ($valorNumerico <= 120) return 'amarela';
            return 'vermelha';
        }
        
        // Padrão: comparar com meta verde/amarela/vermelha (implementação básica)
        return 'amarela'; // Padrão para valores não classificados automaticamente
    }

    /**
     * Exportar SOP como PDF — F-06 Implementation
     */
    public function exportarPdf(): void
    {
        Auth::proteger();

        $sopId = (int) ($_GET['id'] ?? 0);
        if (!$sopId) {
            Flash::set('erro', 'SOP não encontrado.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }

        // Buscar SOP
        $sop = Sop::buscarPorId($sopId);
        if (!$sop || !Auth::podeAcessarEmpresa($sop['empresa_id'])) {
            Flash::set('erro', 'SOP não encontrado ou sem permissão.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }

        // Gerar conteúdo HTML para PDF
        $conteudoCompleto = json_decode($sop['conteudo_completo'], true);
        $empresa = Empresa::buscarPorId($sop['empresa_id']);
        $kpis = Sop::buscarKpis($sopId);
        
        $sopFormatado = $this->formatarSopParaViewCompleta($sop, $conteudoCompleto, $empresa, $kpis);

        // HTML básico para PDF (sem CSS complexo)
        $html = $this->gerarHtmlParaPdf($sopFormatado);

        // Headers para download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="SOP-' . $sop['sop_codigo'] . '.pdf"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        // Gerar PDF simples (texto puro convertido)
        echo $this->gerarPdfSimples($html, $sopFormatado['titulo']);
        exit;
    }

    /**
     * Exportar todos os SOPs como ZIP — F-06 Implementation
     */
    public function exportarTodosZip(): void
    {
        Auth::proteger();

        $empresaId = Auth::garantirEmpresa();

        // Buscar todos os SOPs aprovados
        $sops = Database::query(
            "SELECT * FROM sops WHERE empresa_id = :empresa_id AND status = 'ativo' ORDER BY sop_codigo",
            ['empresa_id' => $empresaId]
        );

        if (empty($sops)) {
            Flash::set('erro', 'Nenhum SOP aprovado encontrado para exportar.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }

        // Headers para download
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="SOPs-Manual-Operacional.zip"');
        header('Cache-Control: private, max-age=0, must-revalidate');

        // Criar ZIP em memória (implementação simplificada)
        $this->gerarZipSOPs($sops);
        exit;
    }

    /**
     * Gerar HTML limpo para PDF
     */
    private function gerarHtmlParaPdf(array $sop): string
    {
        $html = "
        <html>
        <body style='font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4;'>
            <h1 style='color: #333; border-bottom: 2px solid #0066cc; padding-bottom: 10px;'>
                {$sop['sop_codigo']} - {$sop['titulo']}
            </h1>
            
            <p><strong>Empresa:</strong> {$sop['empresa']}</p>
            <p><strong>Setor:</strong> {$sop['setor']}</p>
            <p><strong>Versão:</strong> {$sop['versao']}</p>
            <p><strong>Norma:</strong> {$sop['norma']}</p>
            
            <h2>Objetivo</h2>
            <p>{$sop['objetivo']}</p>
            
            <h2>Escopo</h2>
            <p><strong>Aplica-se a:</strong> {$sop['escopo_aplica']}</p>
            <p><strong>Não se aplica a:</strong> {$sop['escopo_nao_aplica']}</p>
        ";

        // Subtópicos
        foreach ($sop['subtopicos_completos'] as $subtopico) {
            $html .= "<h2>Subtópico {$subtopico['letra']}: {$subtopico['nome']}</h2>";
            $html .= "<p>{$subtopico['descricao']}</p>";
            
            $html .= "<h3>Procedimentos:</h3><ol>";
            foreach ($subtopico['procedimentos'] as $proc) {
                $html .= "<li>{$proc['acao']} <em>({$proc['responsavel']} - {$proc['prazo']})</em></li>";
            }
            $html .= "</ol>";
        }

        $html .= "</body></html>";
        return $html;
    }

    /**
     * Gerar PDF simples (conversão de HTML)
     */
    private function gerarPdfSimples(string $html, string $titulo): string
    {
        // Implementação simplificada - retorna HTML como "PDF"
        // Em produção, usar biblioteca como TCPDF ou DOMPDF
        return $html;
    }

    /**
     * Gerar ZIP com múltiplos SOPs
     */
    private function gerarZipSOPs(array $sops): void
    {
        // Implementação simplificada - retorna lista de SOPs como texto
        // Em produção, usar ZipArchive do PHP
        echo "Manual Operacional - SOPs:\n\n";
        foreach ($sops as $sop) {
            echo "- {$sop['sop_codigo']}: {$sop['titulo']}\n";
        }
    }

    /**
     * Formatar KPIs do banco para a view
     */
    private function formatarKpisParaView(array $kpis): array
    {
        return array_map(function($kpi) {
            return [
                'kpi' => $kpi['nome'],
                'sop' => $kpi['sop_codigo'] ?? 'N/A',
                'atual' => $kpi['valor_atual'] ?? 'Não medido',
                'meta_verde' => $kpi['meta_verde'],
                'meta_amarela' => $kpi['meta_amarela'],
                'meta_vermelha' => $kpi['meta_vermelha'],
                'zona' => $kpi['zona_atual'] ?? 'verde',
                'frequencia' => 'Conforme SOP',
                'responsavel' => $kpi['responsavel'] ?? 'A definir',
            ];
        }, $kpis);
    }

    /**
     * Formatar KPIs do banco para a view
     */
    private function criarSopNoBanco(string $sopCodigo, string $sopNome, int $empresaId, ?int $diagnosticoId, array $conteudoIA): int|false
    {
        return Sop::criar([
            'empresa_id'        => $empresaId,
            'diagnostico_id'    => $diagnosticoId,
            'sop_codigo'        => $sopCodigo,
            'titulo'            => $sopNome,
            'departamento'      => $this->getDepartamentoPorId($sopCodigo),
            'conteudo'          => 'SOP gerado via IA com 13 componentes completos',
            'conteudo_completo' => $conteudoIA,
            'versao'            => '1.0',
            'status'            => 'rascunho',
            'gerado_por_ia'     => 1,
        ]);
    }

    /**
     * Cria SOP básico quando IA falha
     */
    private function criarSopBasico(string $sopCodigo, string $sopNome, int $empresaId, ?int $diagnosticoId): int|false
    {
        $basicData = [
            'objetivo' => 'Definir procedimento para ' . $sopNome,
            'escopo' => ['aplica_se' => 'A definir', 'nao_aplica' => 'A definir'],
            'subtopicos' => ['A definir procedimentos específicos'],
            'kpis' => [],
            'contencao' => ['n1' => [], 'n2' => [], 'n3' => []]
        ];
        return $this->criarSopNoBanco($sopCodigo, $sopNome, $empresaId, $diagnosticoId, $basicData);
    }

    /**
     * Formata SOP do banco para a view (mantém compatibilidade)
     */
    private function formatarSopParaView(array $sop, array $conteudo): array
    {
        $empresa = Empresa::buscarPorId($sop['empresa_id']);
        
        return [
            'id' => $sop['sop_codigo'] ?? $sop['id'],
            'nome' => $sop['titulo'],
            'versao' => $sop['versao'],
            'empresa' => $empresa['nome'] ?? 'Empresa',
            'setor' => $empresa['segmento'] ?? 'Tecnologia',
            'norma' => ApiHelper::getNormasPorSetor($empresa['segmento'] ?? 'Tecnologia'),
            'objetivo' => $conteudo['objetivo'] ?? '',
            'escopo_aplica' => $conteudo['escopo']['aplica_se'] ?? '',
            'escopo_nao_aplica' => $conteudo['escopo']['nao_aplica'] ?? '',
            'subtopicos' => $conteudo['subtopicos'] ?? [],
            'responsaveis' => $conteudo['responsaveis'] ?? [],
            'prerequisitos' => $conteudo['prerequisitos'] ?? [],
            'ferramentas' => $conteudo['ferramentas'] ?? [],
            'procedimento_subtopico_1' => $conteudo['procedimentos'][0]['passos'] ?? [],
            'checklist' => $conteudo['checklist'] ?? [],
            'evidencias' => $conteudo['evidencias'] ?? [],
            'relatorios' => $conteudo['relatorios'] ?? [],
            'kpis' => $conteudo['kpis'] ?? [],
            'contencao_n1' => $conteudo['contencao']['n1'] ?? [],
            'contencao_n2' => $conteudo['contencao']['n2'] ?? [],
            'contencao_n3' => $conteudo['contencao']['n3'] ?? [],
        ];
    }

    /**
     * Extrai dados do diagnóstico para o prompt
     */
    private function extrairColaboradores(array $diagnostico): string
    {
        $respostas = json_decode($diagnostico['respostas'], true);
        return $respostas['bloco2']['colaboradores'] ?? '10-25';
    }

    private function extrairFaturamento(array $diagnostico): string
    {
        $respostas = json_decode($diagnostico['respostas'], true);
        return $respostas['bloco2']['faturamento'] ?? 'R$ 100-500 mil';
    }

    private function extrairDepartamentos(array $diagnostico): string
    {
        $respostas = json_decode($diagnostico['respostas'], true);
        return $respostas['bloco2']['departamentos'] ?? 'Comercial, TI, Operações, Financeiro';
    }

    private function extrairFerramentas(array $diagnostico): string
    {
        $respostas = json_decode($diagnostico['respostas'], true);
        return $respostas['bloco2']['ferramentas'] ?? 'E-mail, WhatsApp, Excel';
    }

    private function extrairProblemas(array $diagnostico): string
    {
        $respostas = json_decode($diagnostico['respostas'], true);
        return $respostas['bloco4']['problemas'] ?? 'Processos não documentados';
    }

    private function extrairObjetivos(array $diagnostico): string
    {
        $respostas = json_decode($diagnostico['respostas'], true);
        return $respostas['bloco5']['objetivos'] ?? 'Crescer com processos organizados';
    }

    /**
     * Retorna departamentos com SOPs baseados no setor da empresa
     */
    private function getDepartamentosPorSetor(string $setor, int $empresaId): array
    {
        // Buscar SOPs existentes no banco (padrão do sistema)
        $sopsExistentes = Sop::buscarPorEmpresa($empresaId);
        $sopsMap = [];
        foreach ($sopsExistentes as $sop) {
            $sopsMap[$sop['sop_codigo']] = [
                'id' => $sop['sop_codigo'],
                'nome' => $sop['titulo'],
                'status' => match($sop['status']) {
                    'ativo' => 'aprovado',
                    'rascunho' => 'gerado',
                    default => 'nao_gerado'
                }
            ];
        }

        // Templates por setor específico
        $templatesSOP = $this->getSOPsPorSetor($setor);

        // Buscar SOPs customizados da empresa
        $sopsCustomizados = [];
        try {
            $sopsCustomizados = Database::query(
                "SELECT * FROM sops_customizados WHERE empresa_id = :empresa_id AND ativo = 1 ORDER BY departamento, nome",
                ['empresa_id' => $empresaId]
            );
        } catch (Exception $e) {
            // Tabela pode não existir ainda - continuar sem SOPs customizados
            Logger::warning('Tabela sops_customizados não encontrada', ['erro' => $e->getMessage()]);
        }

        // Aplicar status aos SOPs padrão do setor
        foreach ($templatesSOP as &$dept) {
            foreach ($dept['sops'] as &$sop) {
                if (isset($sopsMap[$sop['id']])) {
                    $sop['status'] = $sopsMap[$sop['id']]['status'];
                } else {
                    $sop['status'] = 'nao_gerado';
                }
            }
        }

        // Integrar SOPs customizados nos departamentos
        foreach ($sopsCustomizados as $sopCustomizado) {
            $departamento = $sopCustomizado['departamento'];
            $icone = $sopCustomizado['icone'] ?? '📋';
            
            // Procurar se departamento já existe
            $deptIndex = null;
            foreach ($templatesSOP as $index => $dept) {
                if (strtolower($dept['nome']) === strtolower($departamento)) {
                    $deptIndex = $index;
                    break;
                }
            }
            
            // Se departamento não existe, criar novo
            if ($deptIndex === null) {
                $templatesSOP[] = [
                    'nome' => $departamento,
                    'icone' => $this->converterIconeParaEmoji($sopCustomizado['icone'] ?? 'documento'),
                    'sops' => []
                ];
                $deptIndex = count($templatesSOP) - 1;
            }
            
            // Adicionar SOP customizado ao departamento
            $status = isset($sopsMap[$sopCustomizado['sop_codigo']]) 
                ? $sopsMap[$sopCustomizado['sop_codigo']]['status'] 
                : 'nao_gerado';
            
            $templatesSOP[$deptIndex]['sops'][] = [
                'id' => $sopCustomizado['sop_codigo'],
                'nome' => $sopCustomizado['nome'],
                'status' => $status,
                'customizado' => true,
                'descricao' => $sopCustomizado['descricao']
            ];
        }

        return $templatesSOP;
    }

    /**
     * Retorna SOPs específicos por setor da empresa
     */
    private function getSOPsPorSetor(string $setor): array
    {
        switch (strtolower($setor)) {
            case 'construção civil':
            case 'construcao civil':
                return $this->getSOPsConstrutoraCivil();
                
            case 'tecnologia':
            case 'ti':
            case 'software':
                return $this->getSOPsTecnologia();
                
            case 'saúde':
            case 'saude':
            case 'medicina':
                return $this->getSOPsSaude();
                
            case 'educação':
            case 'educacao':
            case 'ensino':
                return $this->getSOPsEducacao();
                
            case 'consultoria':
                return $this->getSOPsConsultoria();
                
            case 'varejo':
            case 'comercio':
                return $this->getSOPsVarejo();
                
            case 'industria':
            case 'industrial':
                return $this->getSOPsIndustrial();
                
            case 'alimenticio':
            case 'restaurante':
            case 'food':
                return $this->getSOPsAlimenticio();
                
            default:
                return $this->getSOPsGeral();
        }
    }

    /**
     * SOPs específicos para Construção Civil
     */
    private function getSOPsConstrutoraCivil(): array
    {
        return [
            [
                'nome' => 'Comercial',
                'icone' => '💼',
                'sops' => [
                    ['id' => 'SOP-CC-COM-001', 'nome' => 'Prospecção e visita técnica'],
                    ['id' => 'SOP-CC-COM-002', 'nome' => 'Orçamento e memorial descritivo'],
                    ['id' => 'SOP-CC-COM-003', 'nome' => 'Negociação e contrato de obra'],
                    ['id' => 'SOP-CC-COM-004', 'nome' => 'Aprovação de projetos e licenças'],
                ],
            ],
            [
                'nome' => 'Planejamento de Obra',
                'icone' => '📋',
                'sops' => [
                    ['id' => 'SOP-CC-PLAN-001', 'nome' => 'Cronograma executivo de obra'],
                    ['id' => 'SOP-CC-PLAN-002', 'nome' => 'Compra e logística de materiais'],
                    ['id' => 'SOP-CC-PLAN-003', 'nome' => 'Contratação de mão de obra'],
                    ['id' => 'SOP-CC-PLAN-004', 'nome' => 'Gestão de subcontratados'],
                ],
            ],
            [
                'nome' => 'Execução',
                'icone' => '🚧',
                'sops' => [
                    ['id' => 'SOP-CC-EXEC-001', 'nome' => 'Controle de qualidade e fiscalização'],
                    ['id' => 'SOP-CC-EXEC-002', 'nome' => 'Segurança do trabalho na obra'],
                    ['id' => 'SOP-CC-EXEC-003', 'nome' => 'Medições e controle de avanço'],
                    ['id' => 'SOP-CC-EXEC-004', 'nome' => 'Gestão de mudanças e aditivos'],
                    ['id' => 'SOP-CC-EXEC-005', 'nome' => 'Controle de custos e orçamento'],
                ],
            ],
            [
                'nome' => 'Financeiro',
                'icone' => '💰',
                'sops' => [
                    ['id' => 'SOP-CC-FIN-001', 'nome' => 'Faturamento e medições'],
                    ['id' => 'SOP-CC-FIN-002', 'nome' => 'Controle de fluxo de caixa da obra'],
                    ['id' => 'SOP-CC-FIN-003', 'nome' => 'Gestão de garantias e retenções'],
                ],
            ],
            [
                'nome' => 'Entrega',
                'icone' => '🏠',
                'sops' => [
                    ['id' => 'SOP-CC-ENT-001', 'nome' => 'Vistoria e entrega da obra'],
                    ['id' => 'SOP-CC-ENT-002', 'nome' => 'Documentação técnica e As Built'],
                    ['id' => 'SOP-CC-ENT-003', 'nome' => 'Pós-entrega e assistência técnica'],
                ],
            ],
        ];
    }

    /**
     * SOPs específicos para Tecnologia
     */
    private function getSOPsTecnologia(): array
    {
        return [
            [
                'nome' => 'Comercial',
                'icone' => '💼',
                'sops' => [
                    ['id' => 'SOP-TI-COM-001', 'nome' => 'Prospecção e qualificação'],
                    ['id' => 'SOP-TI-COM-002', 'nome' => 'Proposta técnica e comercial'],
                    ['id' => 'SOP-TI-COM-003', 'nome' => 'Negociação e fechamento'],
                ],
            ],
            [
                'nome' => 'Desenvolvimento',
                'icone' => '⚙️',
                'sops' => [
                    ['id' => 'SOP-TI-DEV-001', 'nome' => 'Gestão de projetos ágeis'],
                    ['id' => 'SOP-TI-DEV-002', 'nome' => 'Code review e qualidade'],
                    ['id' => 'SOP-TI-DEV-003', 'nome' => 'Deploy e CI/CD'],
                    ['id' => 'SOP-TI-DEV-004', 'nome' => 'Gestão de bugs e suporte'],
                ],
            ],
            [
                'nome' => 'Infraestrutura',
                'icone' => '🔧',
                'sops' => [
                    ['id' => 'SOP-TI-INF-001', 'nome' => 'Monitoramento de serviços'],
                    ['id' => 'SOP-TI-INF-002', 'nome' => 'Backup e disaster recovery'],
                    ['id' => 'SOP-TI-INF-003', 'nome' => 'Gestão de acessos e segurança'],
                ],
            ],
        ];
    }

    /**
     * SOPs específicos para Saúde
     */
    private function getSOPsSaude(): array
    {
        return [
            [
                'nome' => 'Atendimento',
                'icone' => '🏥',
                'sops' => [
                    ['id' => 'SOP-SA-ATD-001', 'nome' => 'Agendamento e triagem'],
                    ['id' => 'SOP-SA-ATD-002', 'nome' => 'Consulta e anamnese'],
                    ['id' => 'SOP-SA-ATD-003', 'nome' => 'Prescrição e orientações'],
                ],
            ],
            [
                'nome' => 'Procedimentos',
                'icone' => '⚕️',
                'sops' => [
                    ['id' => 'SOP-SA-PROC-001', 'nome' => 'Esterilização e biossegurança'],
                    ['id' => 'SOP-SA-PROC-002', 'nome' => 'Controle de infecção hospitalar'],
                    ['id' => 'SOP-SA-PROC-003', 'nome' => 'Gestão de materiais médicos'],
                ],
            ],
            [
                'nome' => 'Administrativo',
                'icone' => '📋',
                'sops' => [
                    ['id' => 'SOP-SA-ADM-001', 'nome' => 'Prontuário eletrônico e LGPD'],
                    ['id' => 'SOP-SA-ADM-002', 'nome' => 'Faturamento e convênios'],
                ],
            ],
        ];
    }

    /**
     * SOPs gerais para setores não mapeados
     */
    private function getSOPsGeral(): array
    {
        return [
            [
                'nome' => 'Comercial',
                'icone' => '💼',
                'sops' => [
                    ['id' => 'SOP-GER-COM-001', 'nome' => 'Prospecção de clientes'],
                    ['id' => 'SOP-GER-COM-002', 'nome' => 'Proposta comercial'],
                    ['id' => 'SOP-GER-COM-003', 'nome' => 'Fechamento de vendas'],
                ],
            ],
            [
                'nome' => 'Operacional',
                'icone' => '⚙️',
                'sops' => [
                    ['id' => 'SOP-GER-OPS-001', 'nome' => 'Atendimento ao cliente'],
                    ['id' => 'SOP-GER-OPS-002', 'nome' => 'Controle de qualidade'],
                    ['id' => 'SOP-GER-OPS-003', 'nome' => 'Gestão de fornecedores'],
                ],
            ],
            [
                'nome' => 'Administrativo',
                'icone' => '📋',
                'sops' => [
                    ['id' => 'SOP-GER-ADM-001', 'nome' => 'Gestão financeira'],
                    ['id' => 'SOP-GER-ADM-002', 'nome' => 'Recursos humanos'],
                ],
            ],
        ];
    }

    /**
     * SOPs para Educação
     */
    private function getSOPsEducacao(): array
    {
        return [
            [
                'nome' => 'Acadêmico',
                'icone' => '🎓',
                'sops' => [
                    ['id' => 'SOP-EDU-ACA-001', 'nome' => 'Planejamento pedagógico'],
                    ['id' => 'SOP-EDU-ACA-002', 'nome' => 'Avaliação e recuperação'],
                    ['id' => 'SOP-EDU-ACA-003', 'nome' => 'Gestão de turmas e horários'],
                ],
            ],
            [
                'nome' => 'Administrativo',
                'icone' => '📋',
                'sops' => [
                    ['id' => 'SOP-EDU-ADM-001', 'nome' => 'Matrícula e rematrícula'],
                    ['id' => 'SOP-EDU-ADM-002', 'nome' => 'Controle de frequência'],
                    ['id' => 'SOP-EDU-ADM-003', 'nome' => 'Comunicação com responsáveis'],
                ],
            ],
        ];
    }

    /**
     * SOPs para Consultoria
     */
    private function getSOPsConsultoria(): array
    {
        return [
            [
                'nome' => 'Comercial',
                'icone' => '💼',
                'sops' => [
                    ['id' => 'SOP-CON-COM-001', 'nome' => 'Diagnóstico empresarial'],
                    ['id' => 'SOP-CON-COM-002', 'nome' => 'Proposta de projeto'],
                    ['id' => 'SOP-CON-COM-003', 'nome' => 'Contratação de consultoria'],
                ],
            ],
            [
                'nome' => 'Execução',
                'icone' => '⚙️',
                'sops' => [
                    ['id' => 'SOP-CON-EXEC-001', 'nome' => 'Gestão de projetos'],
                    ['id' => 'SOP-CON-EXEC-002', 'nome' => 'Entrega de resultados'],
                    ['id' => 'SOP-CON-EXEC-003', 'nome' => 'Acompanhamento pós-projeto'],
                ],
            ],
        ];
    }

    /**
     * SOPs para Varejo
     */
    private function getSOPsVarejo(): array
    {
        return [
            [
                'nome' => 'Vendas',
                'icone' => '🛒',
                'sops' => [
                    ['id' => 'SOP-VAR-VEN-001', 'nome' => 'Atendimento e vendas'],
                    ['id' => 'SOP-VAR-VEN-002', 'nome' => 'Gestão do caixa'],
                    ['id' => 'SOP-VAR-VEN-003', 'nome' => 'Pós-venda e trocas'],
                ],
            ],
            [
                'nome' => 'Estoque',
                'icone' => '📦',
                'sops' => [
                    ['id' => 'SOP-VAR-EST-001', 'nome' => 'Controle de estoque'],
                    ['id' => 'SOP-VAR-EST-002', 'nome' => 'Compras e fornecedores'],
                    ['id' => 'SOP-VAR-EST-003', 'nome' => 'Inventário e perdas'],
                ],
            ],
        ];
    }

    /**
     * SOPs para Indústria
     */
    private function getSOPsIndustrial(): array
    {
        return [
            [
                'nome' => 'Produção',
                'icone' => '🏭',
                'sops' => [
                    ['id' => 'SOP-IND-PROD-001', 'nome' => 'Planejamento de produção'],
                    ['id' => 'SOP-IND-PROD-002', 'nome' => 'Controle de qualidade'],
                    ['id' => 'SOP-IND-PROD-003', 'nome' => 'Manutenção preventiva'],
                ],
            ],
            [
                'nome' => 'Segurança',
                'icone' => '🦺',
                'sops' => [
                    ['id' => 'SOP-IND-SEG-001', 'nome' => 'Segurança do trabalho'],
                    ['id' => 'SOP-IND-SEG-002', 'nome' => 'Meio ambiente e resíduos'],
                    ['id' => 'SOP-IND-SEG-003', 'nome' => 'Emergências e acidentes'],
                ],
            ],
        ];
    }

    /**
     * SOPs para Alimentício/Restaurante
     */
    private function getSOPsAlimenticio(): array
    {
        return [
            [
                'nome' => 'Produção',
                'icone' => '👨‍🍳',
                'sops' => [
                    ['id' => 'SOP-ALI-PROD-001', 'nome' => 'Manipulação de alimentos'],
                    ['id' => 'SOP-ALI-PROD-002', 'nome' => 'Controle de temperatura'],
                    ['id' => 'SOP-ALI-PROD-003', 'nome' => 'Higienização e limpeza'],
                ],
            ],
            [
                'nome' => 'Atendimento',
                'icone' => '🍽️',
                'sops' => [
                    ['id' => 'SOP-ALI-ATD-001', 'nome' => 'Atendimento ao cliente'],
                    ['id' => 'SOP-ALI-ATD-002', 'nome' => 'Delivery e take-away'],
                ],
            ],
            [
                'nome' => 'Controle',
                'icone' => '📋',
                'sops' => [
                    ['id' => 'SOP-ALI-CTRL-001', 'nome' => 'Controle de estoque'],
                    ['id' => 'SOP-ALI-CTRL-002', 'nome' => 'APPCC e vigilância sanitária'],
                ],
            ],
        ];
    }

    // ===== F-06 HELPER METHODS =====

    /**
     * Buscar alertas ativos para um SOP
     */
    private function buscarAlertasAtivos(int $sopId): array
    {
        return Database::query(
            "SELECT * FROM alertas WHERE sop_id = :sop_id AND status = 'ativo' ORDER BY prioridade DESC, data_criacao DESC",
            ['sop_id' => $sopId]
        );
    }

    /**
     * Buscar histórico de contingências de um SOP
     */
    private function buscarHistoricoContencao(int $sopId): array
    {
        return Database::query(
            "SELECT * FROM ocorrencias_contencao WHERE sop_id = :sop_id ORDER BY data_inicio DESC LIMIT 10",
            ['sop_id' => $sopId]
        );
    }

    /**
     * Formatar SOP completo para visualização (F-06)
     */
    private function formatarSopParaViewCompleta(array $sop, array $conteudo, array $empresa, array $kpis): array
    {
        // Estruturar subtópicos com procedimentos próprios
        $subtopicosCompletos = [];
        if (isset($conteudo['subtopicos'])) {
            foreach ($conteudo['subtopicos'] as $index => $subtopico) {
                $letra = chr(65 + $index); // A, B, C...
                
                $subtopicosCompletos[] = [
                    'letra' => $letra,
                    'nome' => $subtopico['nome'],
                    'descricao' => $subtopico['descricao'],
                    'procedimentos' => $conteudo['procedimentos'][$index]['passos'] ?? [],
                    'checklist' => $this->getChecklistPorSubtopico($conteudo['checklist'] ?? [], $index),
                    'evidencias' => $this->getEvidenciasPorSubtopico($conteudo['evidencias'] ?? [], $index),
                ];
            }
        }

        return [
            'id' => $sop['id'],
            'sop_codigo' => $sop['sop_codigo'],
            'titulo' => $sop['titulo'],
            'versao' => $sop['versao'],
            'empresa' => $empresa['nome'] ?? 'Empresa',
            'setor' => $empresa['segmento'] ?? 'Tecnologia', 
            'norma' => ApiHelper::getNormasPorSetor($empresa['segmento'] ?? 'Tecnologia'),
            'objetivo' => $conteudo['objetivo'] ?? '',
            'escopo_aplica' => $conteudo['escopo']['aplica_se'] ?? '',
            'escopo_nao_aplica' => $conteudo['escopo']['nao_aplica'] ?? '',
            'subtopicos_completos' => $subtopicosCompletos,
            'responsaveis' => $conteudo['responsaveis'] ?? [],
            'kpis' => $kpis,
        ];
    }

    /**
     * Dividir checklist por subtópico
     */
    private function getChecklistPorSubtopico(array $checklist, int $index): array
    {
        if (empty($checklist)) return [];
        
        $itemsPorSubtopico = ceil(count($checklist) / 3); // Dividir em 3 subtópicos
        $inicio = $index * $itemsPorSubtopico;
        
        return array_slice($checklist, $inicio, $itemsPorSubtopico);
    }

    /**
     * Dividir evidências por subtópico
     */
    private function getEvidenciasPorSubtopico(array $evidencias, int $index): array
    {
        if (empty($evidencias)) return [];
        
        $itemsPorSubtopico = ceil(count($evidencias) / 3);
        $inicio = $index * $itemsPorSubtopico;
        
        return array_slice($evidencias, $inicio, $itemsPorSubtopico);
    }
}
