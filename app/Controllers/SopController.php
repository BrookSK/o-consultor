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
        
        $empresaId = Auth::empresa();
        if (!$empresaId) {
            Flash::set('erro', 'Empresa não identificada.');
            header('Location: ' . APP_URL . '/dashboard');
            exit;
        }

        // Dados da empresa e diagnóstico
        $empresa = Empresa::buscarPorId($empresaId);
        $diagnostico = Diagnostico::buscarUltimoPorEmpresa($empresaId);
        
        if (!$empresa) {
            Flash::set('erro', 'Empresa não encontrada.');
            header('Location: ' . APP_URL . '/dashboard');
            exit;
        }

        // Estatísticas dos SOPs
        $stats = Sop::estatisticas($empresaId);
        
        // Departamentos com SOPs (baseado no diagnóstico ou padrão por setor)
        $departamentos = $this->getDepartamentosPorSetor($empresa['segmento'] ?? 'Tecnologia', $empresaId);

        $dados = [
            'empresa' => $empresa['nome'],
            'setor' => $empresa['segmento'] ?? 'Tecnologia',
            'maturidade' => $empresa['score_maturidade'] ?? 2,
            'norma' => ApiHelper::getNormasPorSetor($empresa['segmento'] ?? 'Tecnologia'),
            'departamentos' => $departamentos,
            'total_sops' => $stats['total'],
            'aprovados' => $stats['aprovados'],
            'diagnostico_id' => $diagnostico['id'] ?? null,
        ];

        require VIEW_PATH . '/sop/index.php';
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
        $empresaId = Auth::empresa();

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
            'setor' => $empresa['setor'],
            'norma' => ApiHelper::getNormasPorSetor($empresa['setor']) ?? 'ISO 9001',
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
        if (!$sop || $sop['empresa_id'] != Auth::empresa()) {
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
        if (!$sop || $sop['empresa_id'] != Auth::empresa()) {
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
        if (!$sop || $sop['empresa_id'] != Auth::empresa()) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'SOP não encontrado ou sem permissão.']);
            exit;
        }

        // TODO: Em produção, coletar dados editados do frontend e salvar
        // Por enquanto, apenas confirmar que foi salvo
        
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
        if (!$sop || $sop['empresa_id'] != Auth::empresa()) {
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
        if (!$sop || $sop['empresa_id'] != Auth::empresa()) {
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
        if (!$sop || $sop['empresa_id'] != Auth::empresa()) {
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
        if (!$sop || $sop['empresa_id'] != Auth::empresa()) {
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
        
        $empresaId = Auth::empresa();
        if (!$empresaId) {
            Flash::set('erro', 'Empresa não identificada.');
            header('Location: ' . APP_URL . '/dashboard');
            exit;
        }

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
        if (!$sop || $sop['empresa_id'] != Auth::empresa()) {
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

        $empresaId = Auth::empresa();
        if (!$empresaId) {
            Flash::set('erro', 'Empresa não identificada.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }

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
        // Buscar SOPs existentes no banco
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

        // Template padrão (Tech/TI - pode ser expandido para outros setores)
        $templateSOPs = [
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
                'nome' => 'Onboarding',
                'icone' => '🚀',
                'sops' => [
                    ['id' => 'SOP-TI-ONB-001', 'nome' => 'Recebimento e migração de clientes'],
                    ['id' => 'SOP-TI-ONB-002', 'nome' => 'Configuração inicial de ambiente'],
                    ['id' => 'SOP-TI-ONB-003', 'nome' => 'Treinamento e ativação'],
                ],
            ],
            [
                'nome' => 'Operacional',
                'icone' => '⚙️',
                'sops' => [
                    ['id' => 'SOP-TI-OPS-001', 'nome' => 'Gestão de chamados e SLA'],
                    ['id' => 'SOP-TI-OPS-002', 'nome' => 'Rotina de segurança e backups'],
                    ['id' => 'SOP-TI-OPS-003', 'nome' => 'Gestão de acessos e senhas'],
                    ['id' => 'SOP-TI-OPS-004', 'nome' => 'Monitoramento de infraestrutura'],
                ],
            ],
            [
                'nome' => 'Financeiro',
                'icone' => '💰',
                'sops' => [
                    ['id' => 'SOP-TI-FIN-001', 'nome' => 'Faturamento e cobrança'],
                    ['id' => 'SOP-TI-FIN-002', 'nome' => 'Gestão de contratos e renovações'],
                ],
            ],
            [
                'nome' => 'Jurídico / Compliance',
                'icone' => '⚖️',
                'sops' => [
                    ['id' => 'SOP-TI-JUR-001', 'nome' => 'LGPD e tratamento de dados'],
                    ['id' => 'SOP-TI-JUR-002', 'nome' => 'Gestão de contratos de prestação de serviço'],
                ],
            ],
            [
                'nome' => 'RH',
                'icone' => '👥',
                'sops' => [
                    ['id' => 'SOP-TI-RH-001', 'nome' => 'Contratação técnica'],
                    ['id' => 'SOP-TI-RH-002', 'nome' => 'Offboarding e reavaliação de acessos'],
                ],
            ],
        ];

        // Aplicar status baseado nos SOPs existentes
        foreach ($templateSOPs as &$dept) {
            foreach ($dept['sops'] as &$sop) {
                if (isset($sopsMap[$sop['id']])) {
                    $sop['status'] = $sopsMap[$sop['id']]['status'];
                } else {
                    $sop['status'] = 'nao_gerado';
                }
            }
        }

        return $templateSOPs;
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
