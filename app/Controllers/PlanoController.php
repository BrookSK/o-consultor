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

        // Mapear status para labels e normalizar chaves esperadas pela view
        foreach ($planos as &$plano) {
            $plano['status_label'] = match($plano['status']) {
                'em_elaboracao' => 'Em Elaboração',
                'ativo' => 'Ativo',
                'pausado' => 'Pausado',
                'concluido' => 'Concluído',
                'cancelado' => 'Cancelado',
                default => 'Rascunho'
            };

            $plano['progresso'] = (int) ($plano['progresso_calculado'] ?? 0);
            $plano['empresa'] = $plano['empresa_nome'] ?? 'Empresa';
            $plano['data'] = $plano['criado_em'] ?? date('Y-m-d');
            $plano['concluidas'] = (int) ($plano['tarefas_concluidas'] ?? 0);
            $plano['total_tarefas'] = (int) ($plano['total_tarefas'] ?? 0);
        }
        unset($plano);

        $dados = [
            'planos' => $planos,
        ];

        require VIEW_PATH . '/plano/index.php';
    }

    /**
     * Exclui um plano de ação (e todos os seus dados).
     */
    public function excluir(): void
    {
        Auth::proteger();
        Csrf::verificar();
        header('Content-Type: application/json');

        $planoId = (int) ($_POST['plano_id'] ?? 0);
        if (!$planoId) {
            echo json_encode(['sucesso' => false, 'erro' => 'Plano não informado.']);
            exit;
        }

        $plano = Plano::buscarPorId($planoId);
        if (!$plano) {
            echo json_encode(['sucesso' => false, 'erro' => 'Plano não encontrado.']);
            exit;
        }

        // Permissão: admin/consultor OU dono do plano (mesma empresa).
        $ehAdmin = in_array(Auth::perfil(), ['ADMIN_HOLDING', 'CONSULTOR_INTERNO']);
        $empresaUsuario = Auth::empresa();
        if (!$ehAdmin && $empresaUsuario !== null && (int) $plano['empresa_id'] !== (int) $empresaUsuario) {
            echo json_encode(['sucesso' => false, 'erro' => 'Sem permissão para excluir este plano.']);
            exit;
        }

        $ok = Plano::excluir($planoId);
        if ($ok) {
            Logger::acao('Plano de ação excluído', ['plano_id' => $planoId]);
        }
        echo json_encode(['sucesso' => (bool) $ok]);
        exit;
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
     * Visualização do plano (Kanban) - route /plano-de-acao/ver?id=X
     */
    public function ver(): void
    {
        Auth::proteger();

        $planoId = (int) ($_GET['id'] ?? 0);
        
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
        $plano = $this->normalizarPlanoParaView($plano, $kanban, $reunioes);

        $dados = [
            'plano' => $plano,
            'kanban' => $kanban,
            'reunioes' => $reunioes,
            'calendario' => Plano::buscarCalendario($planoId),
            'metricas' => Plano::buscarMetricas($planoId),
            'fila' => Plano::buscarFilaCompleta($planoId),
        ];

        require VIEW_PATH . '/plano/ver.php';
    }

    /**
     * Normaliza o array do plano para as chaves esperadas pela view (ver.php):
     * empresa, periodo[inicio|fim], progresso, concluidas, total_tarefas e a
     * lista achatada de tarefas (a partir do kanban agrupado por status).
     */
    private function normalizarPlanoParaView(array $plano, array $kanban, array $reunioes = []): array
    {
        $plano['empresa'] = $plano['empresa_nome'] ?? 'Empresa';
        $plano['objetivo'] = $plano['objetivo'] ?? '';
        $plano['score_maturidade'] = $plano['score_maturidade'] ?? 0;
        $plano['score_inicial'] = $plano['score_inicial'] ?? 0;
        $plano['etapa_atual'] = $plano['etapa_atual'] ?? 1;
        $plano['total_etapas'] = $plano['total_etapas'] ?? 1;
        $plano['progresso'] = (int) ($plano['progresso_calculado'] ?? 0);
        $plano['concluidas'] = (int) ($plano['tarefas_concluidas'] ?? 0);
        $plano['total_tarefas'] = (int) ($plano['total_tarefas'] ?? 0);
        $plano['periodo'] = [
            'inicio' => $plano['periodo_inicio'] ?? $plano['criado_em'] ?? date('Y-m-d'),
            'fim' => $plano['periodo_fim'] ?? $plano['criado_em'] ?? date('Y-m-d'),
        ];

        // Achatar o kanban (agrupado por status) numa única lista de tarefas.
        $tarefas = [];
        foreach ($kanban as $grupo) {
            foreach ((array) $grupo as $t) { $tarefas[] = $t; }
        }
        // Garantir campos que a view acessa direto, evitando warnings.
        foreach ($tarefas as &$t) {
            $t['prazo'] = $t['prazo'] ?? null;
            $t['status'] = $t['status'] ?? 'pendente';
            $t['prioridade'] = $t['prioridade'] ?? 'media';
            $t['area'] = $t['area'] ?? 'Geral';
            $t['responsavel'] = $t['responsavel'] ?? '—';
            $t['atualizado_em'] = $t['atualizado_em'] ?? $t['criado_em'] ?? date('Y-m-d H:i:s');
        }
        unset($t);
        $plano['tarefas'] = $tarefas;

        // Normalizar reuniões: a view usa a chave 'data' (o model retorna 'data_reuniao').
        $reunioesNorm = [];
        foreach ($reunioes as $r) {
            $r['data'] = $r['data_reuniao'] ?? $r['data'] ?? ($r['criado_em'] ?? date('Y-m-d'));
            $r['participantes'] = $r['participantes'] ?? '';
            $r['decisoes'] = $r['decisoes'] ?? '';
            $r['proximos_passos'] = $r['proximos_passos'] ?? '';
            $reunioesNorm[] = $r;
        }
        $plano['reunioes'] = $reunioesNorm;

        return $plano;
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
        $plano = $this->normalizarPlanoParaView($plano, $kanban, $reunioes);

        $dados = [
            'plano' => $plano,
            'kanban' => $kanban,
            'reunioes' => $reunioes,
            'calendario' => Plano::buscarCalendario($planoId),
            'metricas' => Plano::buscarMetricas($planoId),
            'fila' => Plano::buscarFilaCompleta($planoId),
        ];

        require VIEW_PATH . '/plano/ver.php';
    }

    /**
     * Libera/recolhe uma tarefa da fila para o Kanban (via AJAX).
     */
    public function liberarTarefa(): void
    {
        Auth::proteger();
        Csrf::verificar();
        header('Content-Type: application/json');

        $planoId = (int) ($_POST['plano_id'] ?? 0);
        $tarefaId = (int) ($_POST['tarefa_id'] ?? 0);
        $liberar = (($_POST['liberar'] ?? '1') === '1');
        if (!$planoId || !$tarefaId) {
            echo json_encode(['sucesso' => false, 'erro' => 'Dados inválidos.']);
            exit;
        }
        if (!Plano::tarefaPertenceAoPlano($tarefaId, $planoId)) {
            echo json_encode(['sucesso' => false, 'erro' => 'Tarefa não pertence a este plano.']);
            exit;
        }
        $ok = Plano::definirLiberacaoTarefa($tarefaId, $planoId, $liberar);
        echo json_encode(['sucesso' => (bool) $ok]);
        exit;
    }

    /**
     * Detalhe de uma tarefa (JSON) para o modal do card.
     */
    public function tarefaDetalhe(): void
    {
        Auth::proteger();
        header('Content-Type: application/json');
        $planoId = (int) ($_GET['plano_id'] ?? 0);
        $tarefaId = (int) ($_GET['tarefa_id'] ?? 0);
        if (!$planoId || !$tarefaId) { echo json_encode(['sucesso' => false, 'erro' => 'Dados inválidos.']); exit; }
        $t = Plano::buscarTarefaDetalhe($tarefaId, $planoId);
        if (!$t) { echo json_encode(['sucesso' => false, 'erro' => 'Tarefa não encontrada.']); exit; }
        echo json_encode(['sucesso' => true, 'tarefa' => $t]);
        exit;
    }

    /**
     * Salvar os campos do card (título, descrição, datas, checklist, etiquetas...).
     */
    public function salvarTarefaDetalhe(): void
    {
        Auth::proteger();
        Csrf::verificar();
        header('Content-Type: application/json');
        $planoId = (int) ($_POST['plano_id'] ?? 0);
        $tarefaId = (int) ($_POST['tarefa_id'] ?? 0);
        $titulo = trim($_POST['titulo'] ?? '');
        if (!$planoId || !$tarefaId || $titulo === '') { echo json_encode(['sucesso' => false, 'erro' => 'Título é obrigatório.']); exit; }
        if (!Plano::tarefaPertenceAoPlano($tarefaId, $planoId)) { echo json_encode(['sucesso' => false, 'erro' => 'Sem permissão.']); exit; }

        $checklist = [];
        if (!empty($_POST['checklist'])) {
            $decoded = json_decode($_POST['checklist'], true);
            if (is_array($decoded)) { $checklist = $decoded; }
        }
        $etiquetas = [];
        if (!empty($_POST['etiquetas'])) {
            $etiquetas = is_array($_POST['etiquetas']) ? $_POST['etiquetas'] : array_map('trim', explode(',', $_POST['etiquetas']));
        }

        $ok = Plano::salvarTarefaDetalhe($tarefaId, $planoId, [
            'titulo' => $titulo,
            'descricao' => trim($_POST['descricao'] ?? ''),
            'responsavel' => trim($_POST['responsavel'] ?? ''),
            'data_inicio' => $_POST['data_inicio'] ?? null,
            'prazo' => $_POST['prazo'] ?? null,
            'hora' => $_POST['hora'] ?? null,
            'prioridade' => $_POST['prioridade'] ?? 'media',
            'etiquetas' => $etiquetas,
            'checklist' => $checklist,
        ]);
        echo json_encode(['sucesso' => (bool) $ok]);
        exit;
    }

    /**
     * Adiciona comentário a uma tarefa.
     */
    public function comentarTarefa(): void
    {
        Auth::proteger();
        Csrf::verificar();
        header('Content-Type: application/json');
        $planoId = (int) ($_POST['plano_id'] ?? 0);
        $tarefaId = (int) ($_POST['tarefa_id'] ?? 0);
        $texto = trim($_POST['texto'] ?? '');
        if (!$planoId || !$tarefaId || $texto === '') { echo json_encode(['sucesso' => false, 'erro' => 'Comentário vazio.']); exit; }
        if (!Plano::tarefaPertenceAoPlano($tarefaId, $planoId)) { echo json_encode(['sucesso' => false, 'erro' => 'Sem permissão.']); exit; }
        $usuario = Auth::usuario();
        $ok = Plano::adicionarComentarioTarefa($tarefaId, Auth::id(), $usuario['nome'] ?? 'Usuário', $texto);
        echo json_encode(['sucesso' => (bool) $ok, 'usuario_nome' => $usuario['nome'] ?? 'Usuário']);
        exit;
    }

    /**
     * Sugestão de IA para uma tarefa do plano: gera "como fazer" (descrição)
     * e um checklist em rascunho, com base no diagnóstico e no contexto da tarefa.
     * Não salva nada — devolve o rascunho para o usuário editar/aceitar.
     */
    public function sugerirTarefaIA(): void
    {
        Auth::proteger();
        Csrf::verificar();
        header('Content-Type: application/json');

        $planoId = (int) ($_POST['plano_id'] ?? 0);
        $tarefaId = (int) ($_POST['tarefa_id'] ?? 0);
        if (!$planoId || !$tarefaId) { echo json_encode(['sucesso' => false, 'erro' => 'Dados inválidos.']); exit; }

        $t = Plano::buscarTarefaDetalhe($tarefaId, $planoId);
        if (!$t) { echo json_encode(['sucesso' => false, 'erro' => 'Tarefa não encontrada.']); exit; }

        // Contexto: empresa/diagnóstico + a própria tarefa.
        $plano = Plano::buscarPorId($planoId);
        $empresa = !empty($plano['empresa_id']) ? Empresa::buscarPorId((int) $plano['empresa_id']) : [];
        $ctx = $t['contexto_prioridade'] ?? [];
        $nomeEmpresa = $empresa['nome'] ?? 'a empresa';
        $segmento = $empresa['segmento'] ?? 'geral';

        $prompt = "Você é um consultor de gestão. Com base no diagnóstico da empresa, detalhe COMO EXECUTAR esta ação do plano de ação e proponha um checklist prático.\n\n"
            . "Empresa: {$nomeEmpresa} (segmento: {$segmento}).\n"
            . "Ação (tarefa): " . ($t['titulo'] ?? '') . "\n"
            . "Área: " . ($t['area'] ?? 'Geral') . "\n"
            . (!empty($ctx['descricao_problema']) ? "Problema identificado: " . $ctx['descricao_problema'] . "\n" : '')
            . (!empty($ctx['acao_sugerida']) ? "Direção sugerida: " . $ctx['acao_sugerida'] . "\n" : '')
            . "\nDevolva SOMENTE JSON no formato:\n"
            . "{\n"
            . "  \"como_fazer\": \"passo a passo objetivo de COMO executar esta ação nesta empresa (4 a 8 frases, prático e específico, sem enrolação)\",\n"
            . "  \"checklist\": [\"item verificável 1\", \"item verificável 2\", \"...\"]\n"
            . "}\n"
            . "O checklist deve ter de 4 a 8 itens curtos, acionáveis e na ordem de execução. Responda APENAS com o JSON.";

        try {
            $resp = ApiHelper::chamarAnalise($prompt, true);
            if (empty($resp['sucesso'])) {
                echo json_encode(['sucesso' => false, 'erro' => $resp['erro'] ?? 'IA indisponível.']);
                exit;
            }
            $dados = $resp['conteudo'];
            if (is_string($dados)) { $dados = json_decode($dados, true); }
            if (!is_array($dados)) { echo json_encode(['sucesso' => false, 'erro' => 'Resposta da IA inválida.']); exit; }

            $comoFazer = trim((string) ($dados['como_fazer'] ?? ''));
            $checklistRaw = $dados['checklist'] ?? [];
            $checklist = [];
            foreach ((array) $checklistRaw as $item) {
                $txt = is_array($item) ? ($item['texto'] ?? '') : (string) $item;
                $txt = trim($txt);
                if ($txt !== '') { $checklist[] = ['texto' => $txt, 'feito' => false]; }
            }

            echo json_encode(['sucesso' => true, 'como_fazer' => $comoFazer, 'checklist' => $checklist]);
            exit;
        } catch (Exception $e) {
            Logger::erro('Erro sugerirTarefaIA: ' . $e->getMessage());
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao gerar sugestão: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * Upload de imagem colada/anexada na descrição do card.
     * Aceita dataURL base64 (colar imagem) e salva em public/uploads.
     */
    public function uploadImagemTarefa(): void
    {
        Auth::proteger();
        Csrf::verificar();
        header('Content-Type: application/json');
        $planoId = (int) ($_POST['plano_id'] ?? 0);
        $tarefaId = (int) ($_POST['tarefa_id'] ?? 0);
        $dataUrl = $_POST['imagem'] ?? '';
        if (!$planoId || !$tarefaId || $dataUrl === '') { echo json_encode(['sucesso' => false, 'erro' => 'Imagem inválida.']); exit; }
        if (!Plano::tarefaPertenceAoPlano($tarefaId, $planoId)) { echo json_encode(['sucesso' => false, 'erro' => 'Sem permissão.']); exit; }

        // Extrair base64 do dataURL
        if (!preg_match('/^data:image\/(png|jpe?g|gif|webp);base64,(.+)$/', $dataUrl, $m)) {
            echo json_encode(['sucesso' => false, 'erro' => 'Formato de imagem não suportado.']);
            exit;
        }
        $ext = $m[1] === 'jpeg' ? 'jpg' : $m[1];
        $bin = base64_decode($m[2]);
        if ($bin === false || strlen($bin) > 8 * 1024 * 1024) { // limite 8MB
            echo json_encode(['sucesso' => false, 'erro' => 'Imagem inválida ou muito grande (máx. 8MB).']);
            exit;
        }
        $dir = (defined('ROOT_PATH') ? ROOT_PATH : getcwd()) . '/public/uploads/plano/' . $planoId;
        if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
        $nome = 'card_' . $tarefaId . '_' . uniqid() . '.' . $ext;
        if (file_put_contents($dir . '/' . $nome, $bin) === false) {
            echo json_encode(['sucesso' => false, 'erro' => 'Falha ao salvar a imagem.']);
            exit;
        }
        $url = APP_URL . '/public/uploads/plano/' . $planoId . '/' . $nome;
        Plano::adicionarAnexoTarefa($tarefaId, $planoId, $url);
        echo json_encode(['sucesso' => true, 'url' => $url]);
        exit;
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

        $liberadas = 0;
        $planoId = 0;
        if ($sucesso) {
            // As etapas de pós-processamento NÃO podem derrubar a resposta do drag.
            try {
                $t = Database::queryOne("SELECT plano_id FROM plano_tarefas WHERE id = :id", ['id' => $tarefaId]);
                $planoId = (int) ($t['plano_id'] ?? 0);
                if ($novoStatus === 'concluido') {
                    Database::execute("UPDATE plano_tarefas SET concluida_em = NOW() WHERE id = :id AND concluida_em IS NULL", ['id' => $tarefaId]);
                } else {
                    Database::execute("UPDATE plano_tarefas SET concluida_em = NULL WHERE id = :id", ['id' => $tarefaId]);
                }
                if ($planoId) {
                    $liberadas = Plano::liberarProximaEtapa($planoId);
                    Plano::atualizarScoreMaturidade($planoId);
                }
            } catch (Exception $e) {
                Logger::erro('Pós-processamento moverTarefa falhou (ignorado): ' . $e->getMessage());
            }
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => $sucesso,
            'message' => $sucesso ? 'Tarefa atualizada!' : 'Erro ao atualizar tarefa.',
            'tarefas_liberadas' => $liberadas,
            'plano_id' => $planoId
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

    /**
     * Verifica se já existe plano para um diagnóstico (usado pelo botão do resultado).
     */
    public function planoExiste(): void
    {
        Auth::proteger();
        header('Content-Type: application/json');
        $diagnosticoId = (int) ($_GET['diagnostico_id'] ?? 0);
        if (!$diagnosticoId) {
            echo json_encode(['sucesso' => false, 'existe' => false]);
            exit;
        }
        $plano = Database::queryOne(
            "SELECT id FROM planos WHERE diagnostico_id = :d ORDER BY criado_em DESC LIMIT 1",
            ['d' => $diagnosticoId]
        );
        echo json_encode([
            'sucesso' => true,
            'existe' => (bool) $plano,
            'redirect_ver' => $plano ? (APP_URL . '/plano-de-acao/ver?id=' . (int) $plano['id']) : null,
            'gerar_novo' => APP_URL . '/plano-de-acao/gerar-automatico?diagnostico_id=' . $diagnosticoId,
            'regerar' => APP_URL . '/plano-de-acao/gerar-automatico?diagnostico_id=' . $diagnosticoId . '&forcar=1',
        ]);
        exit;
    }

    /**
     * GERAÇÃO AUTOMÁTICA — a partir do diagnóstico, cria o plano completo
     * (prioridades + tarefas organizadas em ETAPAS sequenciais) de uma vez,
     * e redireciona direto ao Kanban. Usado pelo botão "Gerar Plano de Ação".
     */
    public function gerarAutomatico(): void
    {
        Auth::proteger();

        $diagnosticoId = (int) ($_GET['diagnostico_id'] ?? 0);
        if (!$diagnosticoId) {
            Flash::set('erro', 'Diagnóstico não informado.');
            header('Location: ' . APP_URL . '/plano-de-acao');
            exit;
        }

        $diagnostico = Diagnostico::buscarPorId($diagnosticoId);
        if (!$diagnostico) {
            Flash::set('erro', 'Diagnóstico não encontrado.');
            header('Location: ' . APP_URL . '/plano-de-acao');
            exit;
        }

        // Se já existe plano para este diagnóstico:
        // - sem forçar: abre o existente (comportamento antigo)
        // - forçar=1: REGENERA do zero (apaga o plano anterior e recria)
        $forcar = (($_GET['forcar'] ?? '0') === '1');
        $existente = Database::queryOne(
            "SELECT id FROM planos WHERE diagnostico_id = :d ORDER BY criado_em DESC LIMIT 1",
            ['d' => $diagnosticoId]
        );
        if ($existente && !$forcar) {
            header('Location: ' . APP_URL . '/plano-de-acao/ver?id=' . (int) $existente['id']);
            exit;
        }
        if ($existente && $forcar) {
            // Remove o plano anterior e seus filhos. As tabelas de plano têm FK ON DELETE
            // CASCADE, mas limpamos manualmente também (métricas podem não ter FK se a
            // estrutura foi criada via garantirEstruturaConsolidador).
            $pid = (int) $existente['id'];
            try {
                Database::execute("DELETE r FROM plano_metricas_registros r JOIN plano_metricas m ON r.metrica_id = m.id WHERE m.plano_id = :p", ['p' => $pid]);
            } catch (Exception $e) { /* tabela pode não existir */ }
            try { Database::execute("DELETE FROM plano_metricas WHERE plano_id = :p", ['p' => $pid]); } catch (Exception $e) {}
            try { Database::execute("DELETE FROM plano_tarefas WHERE plano_id = :p", ['p' => $pid]); } catch (Exception $e) {}
            try { Database::execute("DELETE FROM plano_prioridades WHERE plano_id = :p", ['p' => $pid]); } catch (Exception $e) {}
            try { Database::execute("DELETE FROM plano_reunioes WHERE plano_id = :p", ['p' => $pid]); } catch (Exception $e) {}
            Database::execute("DELETE FROM planos WHERE id = :id", ['id' => $pid]);
        }

        try {
            $empresa = Empresa::buscarPorId((int) $diagnostico['empresa_id']);
            $nomeEmpresa = $empresa['nome'] ?? 'Empresa';

            // 1) Criar o plano
            $planoId = Plano::criar([
                'empresa_id' => (int) $diagnostico['empresa_id'],
                'diagnostico_id' => $diagnosticoId,
                'usuario_id' => Auth::id(),
                'titulo' => 'Plano de Ação — ' . $nomeEmpresa,
                'objetivo' => 'Plano gerado automaticamente a partir do diagnóstico.',
                'periodo_inicio' => date('Y-m-d'),
                'periodo_fim' => date('Y-m-d', strtotime('+12 months')),
                'status' => 'ativo'
            ]);
            if (!$planoId) {
                throw new Exception('Falha ao criar o plano.');
            }

            Plano::garantirEstruturaConsolidador();

            // Score inicial = pontuação do diagnóstico (maturidade de partida).
            $scoreInicial = (float) ($diagnostico['pontuacao'] ?? 0);
            Database::execute(
                "UPDATE planos SET score_inicial = :s1, score_maturidade = :s2 WHERE id = :id",
                ['s1' => $scoreInicial, 's2' => $scoreInicial, 'id' => $planoId]
            );

            // 2) Gerar prioridades (IA com fallback) e salvá-las já confirmadas.
            $planoRow = Plano::buscarPorId($planoId);
            $prioridades = $this->gerarPrioridadesIA($planoRow);
            if (!empty($prioridades)) {
                Plano::salvarPrioridades($planoId, $prioridades);
            }
            $prioridadesSalvas = Plano::buscarPrioridades($planoId);
            // confirmar todas
            Plano::confirmarPrioridades($planoId, array_map(fn($p) => $p['id'], $prioridadesSalvas));

            // 3) Transformar prioridades em tarefas por ETAPAS (por urgência/impacto).
            $tarefas = $this->montarTarefasEmEtapas($prioridadesSalvas);
            Plano::criarTarefasEmEtapas($planoId, $tarefas);
            Plano::liberarProximaEtapa($planoId);
            Plano::atualizarScoreMaturidade($planoId);

            Logger::acao('Plano gerado automaticamente do diagnóstico', ['plano_id' => $planoId, 'diagnostico_id' => $diagnosticoId]);

            header('Location: ' . APP_URL . '/plano-de-acao/ver?id=' . $planoId);
            exit;

        } catch (Exception $e) {
            Logger::erro('Erro na geração automática do plano: ' . $e->getMessage());
            Flash::set('erro', 'Erro ao gerar o plano: ' . $e->getMessage());
            header('Location: ' . APP_URL . '/plano-de-acao');
            exit;
        }
    }

    /**
     * Distribui as prioridades em etapas sequenciais. As mais urgentes/impactantes
     * ficam nas primeiras etapas. Cada etapa agrupa até 3 tarefas.
     */
    private function montarTarefasEmEtapas(array $prioridades): array
    {
        // Ordenar por urgência e impacto (alta primeiro).
        $peso = ['alta' => 3, 'media' => 2, 'baixa' => 1];
        usort($prioridades, function ($a, $b) use ($peso) {
            $ua = ($peso[$a['urgencia']] ?? 2) + ($peso[$a['impacto']] ?? 2);
            $ub = ($peso[$b['urgencia']] ?? 2) + ($peso[$b['impacto']] ?? 2);
            return $ub <=> $ua;
        });

        $tarefas = [];
        $porEtapa = 3;
        foreach ($prioridades as $i => $p) {
            $etapa = intdiv($i, $porEtapa) + 1;
            $tarefas[] = [
                'prioridade_id' => $p['id'],
                'ordem_etapa' => $etapa,
                'titulo' => $p['acao_sugerida'],
                'descricao' => $p['descricao_problema'],
                'area' => $p['area'],
                'prioridade' => $p['urgencia'] ?? 'media',
                'prazo' => date('Y-m-d', strtotime('+' . ($etapa * 15) . ' days')),
            ];
        }
        return $tarefas;
    }

    /**
     * Criar tarefa manualmente (form simples) no plano.
     */
    public function criarTarefaManual(): void
    {
        Auth::proteger();
        Csrf::verificar();
        header('Content-Type: application/json');

        $planoId = (int) ($_POST['plano_id'] ?? 0);
        $titulo = trim($_POST['titulo'] ?? '');
        if (!$planoId || $titulo === '') {
            echo json_encode(['sucesso' => false, 'erro' => 'Informe ao menos o título e o plano.']);
            exit;
        }

        try {
            $id = Plano::criarTarefa($planoId, [
                'titulo' => $titulo,
                'descricao' => trim($_POST['descricao'] ?? ''),
                'area' => trim($_POST['area'] ?? ''),
                'responsavel' => trim($_POST['responsavel'] ?? ''),
                'prazo' => !empty($_POST['prazo']) ? $_POST['prazo'] : null,
                'hora' => !empty($_POST['hora']) ? $_POST['hora'] : null,
                'prioridade' => $_POST['prioridade'] ?? 'media',
                'tipo' => $_POST['tipo'] ?? 'tarefa',
            ]);
            echo json_encode(['sucesso' => (bool) $id, 'id' => $id]);
        } catch (Exception $e) {
            Logger::erro('Erro ao criar tarefa manual: ' . $e->getMessage());
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao criar tarefa: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Criar tarefa/compromisso por IA a partir de uma frase em linguagem natural.
     * Ex.: "reunião com o time comercial sexta às 15h para revisar metas".
     * A IA extrai título, tipo, data, hora, responsável e prioridade.
     */
    public function criarTarefaIA(): void
    {
        Auth::proteger();
        Csrf::verificar();
        header('Content-Type: application/json');

        $planoId = (int) ($_POST['plano_id'] ?? 0);
        $texto = trim($_POST['texto'] ?? '');
        if (!$planoId || $texto === '') {
            echo json_encode(['sucesso' => false, 'erro' => 'Descreva o compromisso.']);
            exit;
        }

        $hoje = date('Y-m-d');
        $diaSemana = date('N'); // 1=seg..7=dom
        $prompt = "Você é um assistente que agenda compromissos. A partir da frase do usuário, extraia UM compromisso e devolva SOMENTE JSON.\n"
            . "Hoje é {$hoje} (dia da semana {$diaSemana}, 1=segunda ... 7=domingo). Interprete expressões como 'amanhã', 'sexta', 'semana que vem', 'daqui 3 dias' em relação a hoje e devolva a data ABSOLUTA no formato YYYY-MM-DD.\n"
            . "Frase: \"{$texto}\"\n\n"
            . "Formato JSON:\n"
            . "{\n"
            . "  \"titulo\": \"título curto e claro do compromisso\",\n"
            . "  \"descricao\": \"detalhes se houver, senão string vazia\",\n"
            . "  \"tipo\": \"tarefa|reuniao|entrega|compromisso\",\n"
            . "  \"data\": \"YYYY-MM-DD (se não houver data clara, use {$hoje})\",\n"
            . "  \"hora\": \"HH:MM em 24h, ou vazio se não mencionada\",\n"
            . "  \"responsavel\": \"nome se mencionado, senão vazio\",\n"
            . "  \"prioridade\": \"alta|media|baixa\"\n"
            . "}\n"
            . "Responda APENAS com o JSON.";

        try {
            $resp = ApiHelper::chamarAnalise($prompt, true);

            if (empty($resp['sucesso'])) {
                Logger::erro('criarTarefaIA: IA indisponível', ['erro' => $resp['erro'] ?? '']);
                echo json_encode(['sucesso' => false, 'erro' => $resp['erro'] ?? 'IA indisponível. Verifique se a API está ativa em Admin > APIs.']);
                exit;
            }

            // Normalizar o conteúdo (pode vir como array já decodificado, string JSON,
            // ou JSON embrulhado em texto/markdown).
            $dados = $resp['conteudo'];
            if (is_string($dados)) {
                $txt = trim($dados);
                // Remover cercas de código markdown se houver
                $txt = preg_replace('/^```(?:json)?|```$/m', '', $txt);
                $dec = json_decode(trim($txt), true);
                if (!is_array($dec)) {
                    // tentar extrair o primeiro objeto {...}
                    if (preg_match('/\{.*\}/s', $txt, $m)) {
                        $dec = json_decode($m[0], true);
                    }
                }
                $dados = $dec;
            }

            // Se veio aninhado (ex.: {"compromisso": {...}}), tentar descer um nível.
            if (is_array($dados) && empty($dados['titulo'])) {
                foreach ($dados as $v) {
                    if (is_array($v) && !empty($v['titulo'])) { $dados = $v; break; }
                }
            }

            // Aceitar variações de chave para o título.
            $titulo = '';
            if (is_array($dados)) {
                $titulo = trim((string) ($dados['titulo'] ?? $dados['título'] ?? $dados['title'] ?? $dados['nome'] ?? ''));
            }

            if (empty($titulo)) {
                Logger::erro('criarTarefaIA: sem titulo no retorno', ['retorno' => is_array($dados) ? json_encode($dados, JSON_UNESCAPED_UNICODE) : (string) $dados]);
                // Fallback: usar a própria frase do usuário como título, agendando para hoje.
                $titulo = mb_substr($texto, 0, 120);
            }

            $data = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dados['data'] ?? '') ? $dados['data'] : $hoje;
            $horaRaw = $dados['hora'] ?? '';
            $hora = preg_match('/^\d{1,2}:\d{2}$/', $horaRaw) ? substr('0' . $horaRaw, -5) : null;

            $id = Plano::criarTarefa($planoId, [
                'titulo' => $titulo,
                'descricao' => $dados['descricao'] ?? '',
                'responsavel' => $dados['responsavel'] ?? '',
                'prazo' => $data,
                'hora' => $hora,
                'prioridade' => in_array($dados['prioridade'] ?? 'media', ['alta','media','baixa']) ? $dados['prioridade'] : 'media',
                'tipo' => in_array($dados['tipo'] ?? 'tarefa', ['tarefa','reuniao','entrega','compromisso']) ? $dados['tipo'] : 'tarefa',
            ]);

            if (!$id) {
                echo json_encode(['sucesso' => false, 'erro' => 'Não foi possível salvar o compromisso.']);
                exit;
            }

            echo json_encode(['sucesso' => true, 'id' => $id, 'tarefa' => [
                'titulo' => $titulo, 'data' => $data, 'hora' => $hora, 'tipo' => $dados['tipo'] ?? 'tarefa'
            ]]);
            exit;

        } catch (Exception $e) {
            Logger::erro('Erro criarTarefaIA: ' . $e->getMessage());
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao processar com IA: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * Criar uma métrica/KPI do plano.
     */
    public function criarMetrica(): void
    {
        Auth::proteger();
        Csrf::verificar();
        header('Content-Type: application/json');

        $planoId = (int) ($_POST['plano_id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        if (!$planoId || $nome === '') {
            echo json_encode(['sucesso' => false, 'erro' => 'Informe o nome da métrica.']);
            exit;
        }
        $id = Plano::criarMetrica($planoId, [
            'nome' => $nome,
            'categoria' => trim($_POST['categoria'] ?? 'geral'),
            'unidade' => trim($_POST['unidade'] ?? ''),
            'meta' => is_numeric($_POST['meta'] ?? '') ? (float) $_POST['meta'] : null,
            'frequencia' => $_POST['frequencia'] ?? 'mensal',
            'direcao' => $_POST['direcao'] ?? 'cima',
        ]);
        echo json_encode(['sucesso' => (bool) $id, 'id' => $id]);
        exit;
    }

    /**
     * Registrar um valor de uma métrica (medição periódica).
     */
    public function registrarMetrica(): void
    {
        Auth::proteger();
        Csrf::verificar();
        header('Content-Type: application/json');

        $planoId = (int) ($_POST['plano_id'] ?? 0);
        $metricaId = (int) ($_POST['metrica_id'] ?? 0);
        $valor = $_POST['valor'] ?? '';
        if (!$planoId || !$metricaId || !is_numeric($valor)) {
            echo json_encode(['sucesso' => false, 'erro' => 'Dados inválidos.']);
            exit;
        }
        if (!Plano::metricaPertenceAoPlano($metricaId, $planoId)) {
            echo json_encode(['sucesso' => false, 'erro' => 'Métrica não pertence a este plano.']);
            exit;
        }
        $dataRef = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['data_referencia'] ?? '') ? $_POST['data_referencia'] : date('Y-m-d');
        $ok = Plano::registrarMetrica($metricaId, (float) $valor, $dataRef, trim($_POST['observacao'] ?? '') ?: null, Auth::id());
        echo json_encode(['sucesso' => $ok]);
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
            $response = ApiHelper::chamarAnalise($prompt, true); // Com fallback automático

            if ($response && $response['sucesso'] && $response['conteudo']) {
                $prioridadesIA = $response['conteudo'];
                if (is_string($prioridadesIA)) {
                    $prioridadesIA = json_decode($prioridadesIA, true);
                }
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
        // Considera qualquer provedor de IA ativo (chave configurada + toggle ligado).
        return Configuracao::apiAtiva('openai')
            || Configuracao::apiAtiva('anthropic')
            || Configuracao::apiAtiva('perplexity');
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
