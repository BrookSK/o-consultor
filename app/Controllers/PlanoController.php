<?php
/**
 * PlanoController — Módulo de Plano de Ação Estratégico
 * O Consultor — Sistema Operacional Empresarial
 *
 * F-04: Geração completa do Plano de Ação em 3 steps com IA
 */

class PlanoController
{
    /**
     * Lista de planos de ação
     */
    public function index(): void
    {
        Auth::proteger();

        $usuario = Auth::usuario();
        $planos = Plano::listarPorUsuario($usuario['id']);

        // Mapear status para labels e calcular dados
        foreach ($planos as &$plano) {
            $plano['status_label'] = match($plano['status']) {
                'em_elaboracao' => 'Em Elaboração',
                'ativo' => 'Ativo',
                'pausado' => 'Pausado',
                'concluido' => 'Concluído',
                'cancelado' => 'Cancelado',
                default => 'Rascunho'
            };
            
            $plano['progresso'] = (int) $plano['progresso_calculado'];
        }

        $dados = [
            'planos' => $planos,
        ];

        require VIEW_PATH . '/plano/index.php';
    }

    /**
     * Step 1: Criar plano básico
     */
    public function novo(): void
    {
        Auth::proteger();

        $diagnosticoId = (int) ($_GET['diagnostico_id'] ?? 0);
        $diagnostico = null;
        
        if ($diagnosticoId > 0) {
            $diagnostico = Diagnostico::buscarPorId($diagnosticoId);
        }

        // Buscar empresas disponíveis (para Admin/Consultor)
        $empresas = [];
        $usuario = Auth::usuario();
        
        if (Auth::perfil() === 'ADMIN_HOLDING' || Auth::perfil() === 'CONSULTOR_INTERNO') {
            $empresas = Database::query("SELECT id, nome FROM empresas ORDER BY nome ASC");
        } else {
            // Cliente vê apenas sua empresa
            if ($usuario['empresa_id']) {
                $empresas = [Database::queryOne("SELECT id, nome FROM empresas WHERE id = ?", [$usuario['empresa_id']])];
            }
        }

        // Buscar diagnósticos disponíveis para a empresa
        $diagnosticos = [];
        if ($diagnostico) {
            $diagnosticos = Database::query(
                "SELECT d.id, d.pontuacao, d.criado_em, e.nome as empresa_nome 
                 FROM diagnosticos d 
                 LEFT JOIN empresas e ON d.empresa_id = e.id 
                 WHERE d.empresa_id = ? AND d.status = 'concluido'
                 ORDER BY d.criado_em DESC",
                [$diagnostico['empresa_id']]
            );
        }

        $dados = [
            'diagnostico' => $diagnostico,
            'empresas' => $empresas,
            'diagnosticos' => $diagnosticos,
            'step' => 1
        ];

        require VIEW_PATH . '/plano/novo.php';
    }

    /**
     * Salvar Step 1 e ir para geração de prioridades
     */
    public function salvarStep1(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $titulo = htmlspecialchars(trim($_POST['titulo'] ?? ''));
        $objetivo = htmlspecialchars(trim($_POST['objetivo'] ?? ''));
        $empresaId = (int) ($_POST['empresa_id'] ?? 0);
        $diagnosticoId = (int) ($_POST['diagnostico_id'] ?? 0);
        $periodoInicio = $_POST['periodo_inicio'] ?? null;
        $periodoFim = $_POST['periodo_fim'] ?? null;

        if (empty($titulo) || $empresaId === 0) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Título e empresa são obrigatórios.']);
            exit;
        }

        try {
            // Criar plano com status em_elaboracao
            $planoId = Plano::criar([
                'empresa_id' => $empresaId,
                'diagnostico_id' => $diagnosticoId ?: null,
                'usuario_id' => Auth::id(),
                'titulo' => $titulo,
                'objetivo' => $objetivo,
                'periodo_inicio' => $periodoInicio,
                'periodo_fim' => $periodoFim,
                'status' => 'em_elaboracao'
            ]);

            if (!$planoId) {
                throw new Exception('Erro ao criar plano no banco de dados');
            }

            Logger::acao('Plano de Ação Step 1 criado', [
                'plano_id' => $planoId,
                'titulo' => $titulo,
                'empresa_id' => $empresaId,
                'diagnostico_id' => $diagnosticoId
            ]);

            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Plano criado! Gerando prioridades com IA...',
                'plano_id' => $planoId,
                'redirect' => APP_URL . '/plano-de-acao/prioridades/' . $planoId
            ]);

        } catch (Exception $e) {
            Logger::erro('Erro no Step 1 do plano: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao criar plano: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Step 2: Exibir prioridades geradas pela IA
     */
    public function prioridades(): void
    {
        Auth::proteger();

        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $planoId = null;
        if (preg_match('/\/plano-de-acao\/prioridades\/(\d+)/', $path, $matches)) {
            $planoId = (int) $matches[1];
        }

        if (!$planoId) {
            Flash::set('erro', 'Plano não encontrado.');
            header('Location: ' . APP_URL . '/plano-de-acao');
            exit;
        }

        $plano = Plano::buscarPorId($planoId);
        
        if (!$plano || $plano['status'] !== 'em_elaboracao') {
            Flash::set('erro', 'Plano não encontrado ou já finalizado.');
            header('Location: ' . APP_URL . '/plano-de-acao');
            exit;
        }

        // Verificar se prioridades já foram geradas
        $prioridades = Plano::buscarPrioridades($planoId);
        
        if (empty($prioridades)) {
            // Gerar prioridades com IA
            $prioridades = $this->gerarPrioridadesIA($plano);
            
            if (!empty($prioridades)) {
                Plano::salvarPrioridades($planoId, $prioridades);
                $prioridades = Plano::buscarPrioridades($planoId);
            }
        }

        $dados = [
            'plano' => $plano,
            'prioridades' => $prioridades,
            'step' => 2
        ];

        require VIEW_PATH . '/plano/prioridades.php';
    }

    /**
     * Confirmar prioridades selecionadas
     */
    public function confirmarPrioridades(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $planoId = (int) ($_POST['plano_id'] ?? 0);
        $prioridadesIds = $_POST['prioridades'] ?? [];
        $prioridadesEditadas = $_POST['prioridades_editadas'] ?? [];

        if (!$planoId) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Plano não encontrado.']);
            exit;
        }

        try {
            // Atualizar descrições editadas
            foreach ($prioridadesEditadas as $id => $novaDescricao) {
                if (!empty($novaDescricao)) {
                    Database::execute(
                        "UPDATE plano_prioridades SET acao_sugerida = :nova_descricao WHERE id = :id AND plano_id = :plano_id",
                        ['id' => $id, 'nova_descricao' => $novaDescricao, 'plano_id' => $planoId]
                    );
                }
            }

            // Confirmar prioridades selecionadas
            Plano::confirmarPrioridades($planoId, $prioridadesIds);

            Logger::acao('Prioridades confirmadas', [
                'plano_id' => $planoId,
                'total_confirmadas' => count($prioridadesIds)
            ]);

            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Prioridades confirmadas! Criando tarefas...',
                'redirect' => APP_URL . '/plano-de-acao/tarefas/' . $planoId
            ]);

        } catch (Exception $e) {
            Logger::erro('Erro ao confirmar prioridades: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao confirmar prioridades.']);
        }
        exit;
    }

    /**
     * Step 3: Criar tarefas para as prioridades
     */
    public function tarefas(): void
    {
        Auth::proteger();

        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $planoId = null;
        if (preg_match('/\/plano-de-acao\/tarefas\/(\d+)/', $path, $matches)) {
            $planoId = (int) $matches[1];
        }

        if (!$planoId) {
            Flash::set('erro', 'Plano não encontrado.');
            header('Location: ' . APP_URL . '/plano-de-acao');
            exit;
        }

        $plano = Plano::buscarPorId($planoId);
        $prioridadesConfirmadas = Database::query(
            "SELECT * FROM plano_prioridades WHERE plano_id = :plano_id AND confirmada = 1 ORDER BY ordem_prioridade ASC",
            ['plano_id' => $planoId]
        );

        if (empty($prioridadesConfirmadas)) {
            Flash::set('erro', 'Nenhuma prioridade foi confirmada.');
            header('Location: ' . APP_URL . '/plano-de-acao/prioridades/' . $planoId);
            exit;
        }

        $dados = [
            'plano' => $plano,
            'prioridades' => $prioridadesConfirmadas,
            'step' => 3
        ];

        require VIEW_PATH . '/plano/tarefas.php';
    }

    /**
     * Salvar tarefas e finalizar plano
     */
    public function salvarTarefas(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $planoId = (int) ($_POST['plano_id'] ?? 0);
        $tarefas = $_POST['tarefas'] ?? [];

        if (!$planoId || empty($tarefas)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Dados inválidos.']);
            exit;
        }

        try {
            // Criar tarefas
            $tarefasFormatadas = [];
            
            foreach ($tarefas as $prioridadeId => $tarefa) {
                if (!empty($tarefa['titulo'])) {
                    $tarefasFormatadas[] = [
                        'prioridade_id' => $prioridadeId,
                        'titulo' => htmlspecialchars(trim($tarefa['titulo'])),
                        'descricao' => htmlspecialchars(trim($tarefa['descricao'] ?? '')),
                        'area' => htmlspecialchars(trim($tarefa['area'] ?? '')),
                        'responsavel' => htmlspecialchars(trim($tarefa['responsavel'] ?? '')),
                        'prazo' => $tarefa['prazo'] ?? null,
                        'prioridade' => $tarefa['prioridade'] ?? 'media'
                    ];
                }
            }

            if (empty($tarefasFormatadas)) {
                throw new Exception('Nenhuma tarefa válida foi criada');
            }

            Plano::criarTarefasDePrioridades($planoId, $tarefasFormatadas);

            Logger::acao('Tarefas criadas e plano finalizado', [
                'plano_id' => $planoId,
                'total_tarefas' => count($tarefasFormatadas)
            ]);

            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Plano de Ação criado com sucesso!',
                'redirect' => APP_URL . '/plano-de-acao/' . $planoId
            ]);

        } catch (Exception $e) {
            Logger::erro('Erro ao salvar tarefas: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao criar tarefas.']);
        }
        exit;
    }

    /**
     * Visualização do plano (Kanban)
     */
    public function show(): void
    {
        Auth::proteger();

        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $planoId = null;
        if (preg_match('/\/plano-de-acao\/(\d+)$/', $path, $matches)) {
            $planoId = (int) $matches[1];
        }

        if (!$planoId) {
            Flash::set('erro', 'Plano não encontrado.');
            header('Location: ' . APP_URL . '/plano-de-acao');
            exit;
        }

        $plano = Plano::buscarPorId($planoId);
        if (!$plano) {
            Flash::set('erro', 'Plano não encontrado.');
            header('Location: ' . APP_URL . '/plano-de-acao');
            exit;
        }

        $kanban = Plano::buscarTarefasKanban($planoId);
        $reunioes = Plano::buscarReunioes($planoId);

        $dados = [
            'plano' => $plano,
            'kanban' => $kanban,
            'reunioes' => $reunioes
        ];

        require VIEW_PATH . '/plano/ver.php';
    }

    /**
     * Mover tarefa no Kanban via AJAX
     */
    public function moverTarefa(): void
    {
        Auth::proteger();
        
        $tarefaId = (int) ($_POST['tarefa_id'] ?? 0);
        $novoStatus = htmlspecialchars(trim($_POST['novo_status'] ?? ''));

        if (!$tarefaId || empty($novoStatus)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
            exit;
        }

        $sucesso = Plano::moverTarefa($tarefaId, $novoStatus);

        if ($sucesso) {
            Logger::acao('Tarefa movida no Kanban', [
                'tarefa_id' => $tarefaId,
                'novo_status' => $novoStatus
            ]);
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => $sucesso,
            'message' => $sucesso ? 'Tarefa atualizada!' : 'Erro ao atualizar tarefa.'
        ]);
        exit;
    }

    /**
     * Registrar reunião via AJAX
     */
    public function registrarReuniao(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $planoId = (int) ($_POST['plano_id'] ?? 0);
        $dataReuniao = $_POST['data_reuniao'] ?? '';
        $participantes = htmlspecialchars(trim($_POST['participantes'] ?? ''));
        $decisoes = htmlspecialchars(trim($_POST['decisoes'] ?? ''));
        $proximosPassos = htmlspecialchars(trim($_POST['proximos_passos'] ?? ''));

        if (!$planoId || empty($dataReuniao) || empty($decisoes)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Data da reunião e decisões são obrigatórias.']);
            exit;
        }

        try {
            $sucesso = Plano::registrarReuniao($planoId, Auth::id(), [
                'data_reuniao' => $dataReuniao,
                'participantes' => $participantes,
                'decisoes' => $decisoes,
                'proximos_passos' => $proximosPassos
            ]);

            if ($sucesso) {
                Logger::acao('Reunião registrada', ['plano_id' => $planoId, 'data' => $dataReuniao]);
            }

            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => $sucesso,
                'mensagem' => $sucesso ? 'Reunião registrada com sucesso!' : 'Erro ao registrar reunião.'
            ]);

        } catch (Exception $e) {
            Logger::erro('Erro ao registrar reunião: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno.']);
        }
        exit;
    }

    // === MÉTODOS PRIVADOS PARA IA ===

    /**
     * Gerar prioridades usando IA
     */
    private function gerarPrioridadesIA(array $plano): array
    {
        // Verificar se há diagnóstico vinculado
        if (empty($plano['diagnostico_id'])) {
            return $this->gerarPrioridadesPadrao();
        }

        // Buscar dados do diagnóstico
        $diagnostico = Diagnostico::buscarPorId($plano['diagnostico_id']);
        if (!$diagnostico) {
            return $this->gerarPrioridadesPadrao();
        }

        $respostas = json_decode($diagnostico['respostas'], true);
        if (!$respostas) {
            return $this->gerarPrioridadesPadrao();
        }

        // Verificar se OpenAI está configurada
        if (!$this->apiConfigurada()) {
            return $this->gerarPrioridadesBasedInDiagnostico($respostas);
        }

        try {
            $prompt = $this->construirPromptPrioridades($respostas, $plano);
            $response = ApiHelper::chamarOpenAI($prompt, 'gpt-4', true); // true para JSON

            if ($response && isset($response['content'])) {
                $prioridadesIA = json_decode($response['content'], true);
                return $this->formatarPrioridadesIA($prioridadesIA);
            }

        } catch (Exception $e) {
            Logger::erro('Erro ao gerar prioridades IA: ' . $e->getMessage());
        }

        // Fallback
        return $this->gerarPrioridadesBasedInDiagnostico($respostas);
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
     * Construir prompt para geração de prioridades
     */
    private function construirPromptPrioridades(array $respostas, array $plano): string
    {
        $empresa = $respostas['empresa_nome'] ?? 'Empresa';
        $setor = $respostas['setor'] ?? 'Não informado';
        
        return "Analise o diagnóstico empresarial completo e gere 8-12 prioridades de ação para o plano estratégico da empresa '{$empresa}' do setor '{$setor}'.

**DADOS DO DIAGNÓSTICO:**
- Colaboradores internos: " . ($respostas['colaboradores_internos'] ?? 0) . "
- Faturamento: " . ($respostas['faturamento_mensal'] ?? 'Não informado') . "
- Processos documentados: " . ($respostas['processos_documentados'] ?? 0) . "%
- Departamentos: " . implode(', ', $respostas['departamentos'] ?? []) . "
- Problemas operacionais: " . ($respostas['problemas_operacionais'] ?? 'Nenhum') . "
- Riscos: " . ($respostas['riscos_identificados'] ?? 'Nenhum') . "
- Objetivo 12 meses: " . ($respostas['objetivo_12_meses'] ?? 'Não definido') . "
- Pontos fortes: " . ($respostas['pontos_fortes'] ?? 'Não informado') . "
- Pontos de melhoria: " . ($respostas['pontos_melhoria'] ?? 'Não informado') . "

Retorne JSON com array de prioridades neste formato:
{
  \"prioridades\": [
    {
      \"area\": \"Comercial|Operações|Financeiro|Pessoas|TI|Estratégia\",
      \"descricao_problema\": \"Descrição clara do problema identificado\",
      \"acao_sugerida\": \"Ação específica e prática para resolver\",
      \"impacto\": \"alto|medio|baixo\",
      \"urgencia\": \"alta|media|baixa\",
      \"bloco_origem\": 1-5 (de qual bloco do diagnóstico vem)
    }
  ]
}";
    }

    /**
     * Formatar prioridades retornadas pela IA
     */
    private function formatarPrioridadesIA($prioridadesIA): array
    {
        $prioridades = [];
        
        if (isset($prioridadesIA['prioridades']) && is_array($prioridadesIA['prioridades'])) {
            foreach ($prioridadesIA['prioridades'] as $p) {
                $prioridades[] = [
                    'area' => $p['area'] ?? 'Geral',
                    'descricao_problema' => $p['descricao_problema'] ?? '',
                    'acao_sugerida' => $p['acao_sugerida'] ?? '',
                    'impacto' => $p['impacto'] ?? 'medio',
                    'urgencia' => $p['urgencia'] ?? 'media',
                    'bloco_origem' => $p['bloco_origem'] ?? 1
                ];
            }
        }
        
        return $prioridades;
    }

    /**
     * Gerar prioridades baseadas no diagnóstico (fallback sem IA)
     */
    private function gerarPrioridadesBasedInDiagnostico(array $respostas): array
    {
        $prioridades = [];

        // Processo sem backup = prioridade crítica
        if (($respostas['processos_sem_backup'] ?? 'nao') === 'sim') {
            $prioridades[] = [
                'area' => 'Operações',
                'descricao_problema' => 'Processos críticos dependem de pessoas específicas sem backup de conhecimento',
                'acao_sugerida' => 'Documentar processos críticos e treinar equipe de backup',
                'impacto' => 'alto',
                'urgencia' => 'alta',
                'bloco_origem' => 4
            ];
        }

        // Cliente concentrado
        if (($respostas['cliente_concentrado'] ?? 'nao') === 'sim') {
            $prioridades[] = [
                'area' => 'Comercial',
                'descricao_problema' => 'Concentração excessiva de faturamento em poucos clientes',
                'acao_sugerida' => 'Diversificar carteira de clientes e reduzir dependência',
                'impacto' => 'alto',
                'urgencia' => 'media',
                'bloco_origem' => 4
            ];
        }

        // Processos pouco documentados
        $processos = (int) ($respostas['processos_documentados'] ?? 0);
        if ($processos < 50) {
            $prioridades[] = [
                'area' => 'Operações',
                'descricao_problema' => "Apenas {$processos}% dos processos estão documentados",
                'acao_sugerida' => 'Mapear e documentar processos operacionais críticos',
                'impacto' => 'alto',
                'urgencia' => 'media',
                'bloco_origem' => 3
            ];
        }

        // Departamentos
        $departamentos = $respostas['departamentos'] ?? [];
        if (in_array('Comercial', $departamentos)) {
            $prioridades[] = [
                'area' => 'Comercial',
                'descricao_problema' => 'Necessidade de estruturação do processo comercial',
                'acao_sugerida' => 'Implementar CRM e definir funil de vendas estruturado',
                'impacto' => 'alto',
                'urgencia' => 'alta',
                'bloco_origem' => 2
            ];
        }

        return $prioridades;
    }

    /**
     * Gerar prioridades padrão quando não há diagnóstico
     */
    private function gerarPrioridadesPadrao(): array
    {
        return [
            [
                'area' => 'Estratégia',
                'descricao_problema' => 'Falta de planejamento estratégico estruturado',
                'acao_sugerida' => 'Definir missão, visão, valores e objetivos SMART',
                'impacto' => 'alto',
                'urgencia' => 'alta',
                'bloco_origem' => 5
            ],
            [
                'area' => 'Operações',
                'descricao_problema' => 'Processos operacionais não documentados',
                'acao_sugerida' => 'Mapear e documentar processos críticos da empresa',
                'impacto' => 'alto',
                'urgencia' => 'media',
                'bloco_origem' => 3
            ],
            [
                'area' => 'Comercial',
                'descricao_problema' => 'Processo de vendas não estruturado',
                'acao_sugerida' => 'Implementar CRM e definir funil de vendas',
                'impacto' => 'alto',
                'urgencia' => 'alta',
                'bloco_origem' => 2
            ],
            [
                'area' => 'Financeiro',
                'descricao_problema' => 'Controles financeiros básicos',
                'acao_sugerida' => 'Implementar controles de fluxo de caixa e indicadores',
                'impacto' => 'medio',
                'urgencia' => 'media',
                'bloco_origem' => 2
            ]
        ];
    }

    // ===== F-12: ACIONAMENTO DE PARCEIROS =====

    /**
     * Acionar parceiro para uma tarefa — F-12
     */
    public function acionarParceiro(): void
    {
        Auth::proteger();
        Csrf::verificar();
        
        $tarefaId = (int) ($_POST['tarefa_id'] ?? 0);
        $parceiroId = (int) ($_POST['parceiro_id'] ?? 0);
        $descricao = trim($_POST['descricao_necessidade'] ?? '');
        $urgencia = trim($_POST['urgencia'] ?? 'media');
        
        // Validações
        if ($tarefaId === 0 || $parceiroId === 0 || empty($descricao)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Dados incompletos. Preencha todos os campos obrigatórios.']);
            exit;
        }
        
        if (!in_array($urgencia, ['baixa', 'media', 'alta', 'critica'])) {
            $urgencia = 'media';
        }
        
        try {
            // Verificar se tarefa existe e pertence ao usuário
            $tarefa = Database::queryOne(
                "SELECT t.id, t.titulo, p.empresa_id 
                 FROM plano_tarefas t 
                 JOIN plano_prioridades pp ON t.prioridade_id = pp.id 
                 JOIN planos_acao p ON pp.plano_id = p.id 
                 WHERE t.id = :tarefa_id AND p.empresa_id = (
                     SELECT empresa_id FROM usuarios WHERE id = :user_id
                 )",
                ['tarefa_id' => $tarefaId, 'user_id' => Auth::id()]
            );
            
            if (!$tarefa) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Tarefa não encontrada ou sem permissão de acesso.']);
                exit;
            }
            
            // Verificar se parceiro existe e está homologado
            $parceiro = Database::queryOne(
                "SELECT id, nome, status FROM parceiros WHERE id = :id AND status = 'homologado'",
                ['id' => $parceiroId]
            );
            
            if (!$parceiro) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Parceiro não encontrado ou não homologado.']);
                exit;
            }
            
            // Verificar se já existe solicitação ativa para esta tarefa
            $solicitacaoExistente = Database::queryOne(
                "SELECT id FROM solicitacoes_parceiro 
                 WHERE tarefa_id = :tarefa_id AND status NOT IN ('concluido', 'cancelado')",
                ['tarefa_id' => $tarefaId]
            );
            
            if ($solicitacaoExistente) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Já existe uma solicitação ativa para esta tarefa.']);
                exit;
            }
            
            // Inserir solicitação
            $solicitacaoId = Database::execute(
                "INSERT INTO solicitacoes_parceiro (tarefa_id, parceiro_id, empresa_id, usuario_id, descricao_necessidade, urgencia, status, criado_em) 
                 VALUES (:tarefa_id, :parceiro_id, :empresa_id, :usuario_id, :descricao, :urgencia, 'solicitado', NOW())",
                [
                    'tarefa_id' => $tarefaId,
                    'parceiro_id' => $parceiroId,
                    'empresa_id' => $tarefa['empresa_id'],
                    'usuario_id' => Auth::id(),
                    'descricao' => $descricao,
                    'urgencia' => $urgencia
                ]
            );
            $solicitacaoId = Database::lastInsertId();
            
            // Criar alerta para admin
            $empresa = Database::queryOne("SELECT nome FROM empresas WHERE id = :id", ['id' => $tarefa['empresa_id']]);
            $empresaNome = $empresa['nome'] ?? 'Empresa';
            
            $mensagemAlerta = "Nova solicitação de parceiro: {$empresaNome} → {$parceiro['nome']} (Urgência: {$urgencia})";
            
            Database::execute(
                "INSERT INTO alertas (tipo, titulo, mensagem, empresa_id, origem_id, origem_tipo, urgencia, criado_em) 
                 VALUES ('parceiro_solicitado', :titulo, :mensagem, :empresa_id, :origem_id, 'solicitacao_parceiro', :urgencia, NOW())",
                [
                    'titulo' => 'Solicitação de Parceiro',
                    'mensagem' => $mensagemAlerta,
                    'empresa_id' => $tarefa['empresa_id'],
                    'origem_id' => $solicitacaoId,
                    'urgencia' => $urgencia
                ]
            );
            
            // Marcar tarefa como requerendo parceiro
            Database::execute(
                "UPDATE plano_tarefas SET requer_parceiro = 1 WHERE id = :id",
                ['id' => $tarefaId]
            );
            
            Logger::acao('Parceiro acionado', [
                'tarefa_id' => $tarefaId,
                'parceiro_id' => $parceiroId,
                'urgencia' => $urgencia,
                'solicitacao_id' => $solicitacaoId
            ]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'solicitacao_id' => $solicitacaoId,
                'mensagem' => "Parceiro {$parceiro['nome']} solicitado com sucesso!"
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Erro ao acionar parceiro', ['erro' => $e->getMessage(), 'tarefa_id' => $tarefaId]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno. Tente novamente.']);
        }
        exit;
    }

    /**
     * Listar parceiros homologados por categoria
     */
    public function listarParceiros(): void
    {
        Auth::proteger();
        
        $categoria = trim($_GET['categoria'] ?? '');
        $area = trim($_GET['area'] ?? '');
        
        try {
            $whereClause = "WHERE status = 'homologado'";
            $params = [];
            
            if (!empty($categoria)) {
                $whereClause .= " AND categoria = :categoria";
                $params['categoria'] = $categoria;
            }
            
            if (!empty($area)) {
                $whereClause .= " AND (JSON_CONTAINS(areas_atuacao, JSON_QUOTE(:area)) OR categoria LIKE CONCAT('%', :area2, '%'))";
                $params['area'] = $area;
                $params['area2'] = $area;
            }
            
            $parceiros = Database::query(
                "SELECT id, nome, categoria, areas_atuacao, nivel_experiencia, avaliacao_media, total_solicitacoes 
                 FROM parceiros 
                 {$whereClause}
                 ORDER BY avaliacao_media DESC, total_solicitacoes ASC",
                $params
            );
            
            // Decodificar áreas de atuação JSON
            foreach ($parceiros as &$parceiro) {
                $parceiro['areas_atuacao'] = json_decode($parceiro['areas_atuacao'], true) ?? [];
            }
            
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true, 'parceiros' => $parceiros]);
            
        } catch (\Exception $e) {
            Logger::error('Erro ao listar parceiros', ['erro' => $e->getMessage()]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao carregar parceiros.']);
        }
        exit;
    }

    /**
     * Obter status da solicitação de parceiro para uma tarefa
     */
    public function statusSolicitacaoParceiro(): void
    {
        Auth::proteger();
        
        $tarefaId = (int) ($_GET['tarefa_id'] ?? 0);
        
        if ($tarefaId === 0) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'ID da tarefa inválido.']);
            exit;
        }
        
        try {
            $solicitacao = Database::queryOne(
                "SELECT s.*, p.nome as parceiro_nome 
                 FROM solicitacoes_parceiro s 
                 JOIN parceiros p ON s.parceiro_id = p.id 
                 WHERE s.tarefa_id = :tarefa_id 
                 ORDER BY s.criado_em DESC 
                 LIMIT 1",
                ['tarefa_id' => $tarefaId]
            );
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'solicitacao' => $solicitacao
            ]);
            
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao verificar status.']);
        }
        exit;
    }
}