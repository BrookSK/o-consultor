<?php
/**
 * AgendaController — BLOCO GESTÃO: Agenda Pessoal
 * O Consultor — Sistema Operacional Empresarial
 * 
 * Parte do sistema de três blocos: Operacional, Conteúdo, Gestão
 */

class AgendaController
{
    /**
     * Dashboard da agenda pessoal
     */
    public function index(): void
    {
        Auth::proteger();
        
        $empresaId = Auth::empresa();
        $usuarioId = Auth::id();
        
        try {
            // Compromissos de hoje
            $compromissosHoje = Database::query(
                "SELECT * FROM agenda_compromissos 
                 WHERE empresa_id = :empresa_id AND usuario_id = :usuario_id 
                 AND DATE(data_inicio) = CURDATE() 
                 AND status != 'cancelado'
                 ORDER BY data_inicio",
                ['empresa_id' => $empresaId, 'usuario_id' => $usuarioId]
            );

            // Compromissos próximos (próximos 7 dias)
            $proximosCompromisos = Database::query(
                "SELECT * FROM agenda_compromissos 
                 WHERE empresa_id = :empresa_id AND usuario_id = :usuario_id 
                 AND DATE(data_inicio) BETWEEN DATE_ADD(CURDATE(), INTERVAL 1 DAY) AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                 AND status != 'cancelado'
                 ORDER BY data_inicio LIMIT 10",
                ['empresa_id' => $empresaId, 'usuario_id' => $usuarioId]
            );

            // Reuniões de emergência (alertas N3)
            $reunioesEmergencia = Database::query(
                "SELECT * FROM agenda_emergencia 
                 WHERE empresa_id = :empresa_id AND status = 'agendado'
                 ORDER BY data_agendamento LIMIT 5",
                ['empresa_id' => $empresaId]
            );

            // Estatísticas do mês
            $statsAgenda = Database::queryOne(
                "SELECT 
                    COUNT(*) as total_compromissos,
                    COUNT(CASE WHEN status = 'concluido' THEN 1 END) as concluidos,
                    COUNT(CASE WHEN status = 'cancelado' THEN 1 END) as cancelados,
                    COUNT(CASE WHEN DATE(data_inicio) = CURDATE() THEN 1 END) as hoje
                 FROM agenda_compromissos 
                 WHERE empresa_id = :empresa_id AND usuario_id = :usuario_id 
                 AND MONTH(data_inicio) = MONTH(CURDATE())
                 AND YEAR(data_inicio) = YEAR(CURDATE())",
                ['empresa_id' => $empresaId, 'usuario_id' => $usuarioId]
            ) ?? [];

            $dados = [
                'compromissos_hoje' => $compromissosHoje,
                'proximos_compromissos' => $proximosCompromisos,
                'reunioes_emergencia' => $reunioesEmergencia,
                'stats' => $statsAgenda
            ];

            require VIEW_PATH . '/agenda/index.php';

        } catch (Exception $e) {
            Logger::error('Erro ao carregar agenda', ['erro' => $e->getMessage()]);
            Flash::set('erro', 'Erro ao carregar agenda.');
            header('Location: ' . APP_URL . '/dashboard');
            exit;
        }
    }

    /**
     * Adicionar novo compromisso
     */
    public function adicionarCompromisos(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $titulo = htmlspecialchars(trim($_POST['titulo'] ?? ''));
        $descricao = htmlspecialchars(trim($_POST['descricao'] ?? ''));
        $dataInicio = $_POST['data_inicio'] ?? '';
        $dataFim = $_POST['data_fim'] ?? '';
        $tipo = htmlspecialchars($_POST['tipo'] ?? 'reuniao');
        $prioridade = htmlspecialchars($_POST['prioridade'] ?? 'media');

        if (empty($titulo) || empty($dataInicio)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Título e data de início são obrigatórios.']);
            exit;
        }

        // Validar data
        if (!strtotime($dataInicio)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Data de início inválida.']);
            exit;
        }

        try {
            $compromissoId = Database::execute(
                "INSERT INTO agenda_compromissos (empresa_id, usuario_id, titulo, descricao, data_inicio, data_fim, tipo, prioridade, status, criado_em)
                 VALUES (:empresa_id, :usuario_id, :titulo, :descricao, :data_inicio, :data_fim, :tipo, :prioridade, 'agendado', NOW())",
                [
                    'empresa_id' => Auth::empresa(),
                    'usuario_id' => Auth::id(),
                    'titulo' => $titulo,
                    'descricao' => $descricao,
                    'data_inicio' => $dataInicio,
                    'data_fim' => $dataFim ?: null,
                    'tipo' => $tipo,
                    'prioridade' => $prioridade
                ]
            ) ? Database::lastInsertId() : 0;

            if ($compromissoId) {
                Logger::acao('Compromisso adicionado', [
                    'compromisso_id' => $compromissoId,
                    'titulo' => $titulo,
                    'data_inicio' => $dataInicio
                ]);

                header('Content-Type: application/json');
                echo json_encode([
                    'sucesso' => true,
                    'mensagem' => 'Compromisso adicionado com sucesso!',
                    'compromisso_id' => $compromissoId
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Erro ao salvar compromisso.']);
            }

        } catch (Exception $e) {
            Logger::error('Erro ao adicionar compromisso', ['erro' => $e->getMessage()]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno.']);
        }
        exit;
    }

    /**
     * Marcar compromisso como concluído/cancelado
     */
    public function atualizarStatus(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $compromissoId = (int) ($_POST['compromisso_id'] ?? 0);
        $novoStatus = htmlspecialchars($_POST['status'] ?? '');

        if (!$compromissoId || !in_array($novoStatus, ['agendado', 'concluido', 'cancelado'])) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Dados inválidos.']);
            exit;
        }

        try {
            $sucesso = Database::execute(
                "UPDATE agenda_compromissos SET status = :status, atualizado_em = NOW() 
                 WHERE id = :id AND empresa_id = :empresa_id AND usuario_id = :usuario_id",
                [
                    'status' => $novoStatus,
                    'id' => $compromissoId,
                    'empresa_id' => Auth::empresa(),
                    'usuario_id' => Auth::id()
                ]
            );

            if ($sucesso) {
                Logger::acao('Status de compromisso atualizado', [
                    'compromisso_id' => $compromissoId,
                    'novo_status' => $novoStatus
                ]);

                header('Content-Type: application/json');
                echo json_encode([
                    'sucesso' => true,
                    'mensagem' => 'Status atualizado com sucesso!'
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Compromisso não encontrado.']);
            }

        } catch (Exception $e) {
            Logger::error('Erro ao atualizar status', ['erro' => $e->getMessage()]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno.']);
        }
        exit;
    }

    /**
     * Visualizar calendário mensal
     */
    public function calendario(): void
    {
        Auth::proteger();

        $mes = (int) ($_GET['mes'] ?? date('n'));
        $ano = (int) ($_GET['ano'] ?? date('Y'));
        $empresaId = Auth::empresa();
        $usuarioId = Auth::id();

        // Validar mês e ano
        if ($mes < 1 || $mes > 12 || $ano < 2024 || $ano > 2030) {
            $mes = date('n');
            $ano = date('Y');
        }

        try {
            // Buscar compromissos do mês
            $compromissosMes = Database::query(
                "SELECT * FROM agenda_compromissos 
                 WHERE empresa_id = :empresa_id AND usuario_id = :usuario_id 
                 AND MONTH(data_inicio) = :mes AND YEAR(data_inicio) = :ano
                 ORDER BY data_inicio",
                [
                    'empresa_id' => $empresaId,
                    'usuario_id' => $usuarioId,
                    'mes' => $mes,
                    'ano' => $ano
                ]
            );

            // Organizar por dia
            $compromissosPorDia = [];
            foreach ($compromissosMes as $compromisso) {
                $dia = date('j', strtotime($compromisso['data_inicio']));
                $compromissosPorDia[$dia][] = $compromisso;
            }

            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'compromissos_por_dia' => $compromissosPorDia,
                'mes' => $mes,
                'ano' => $ano
            ]);

        } catch (Exception $e) {
            Logger::error('Erro ao carregar calendário', ['erro' => $e->getMessage()]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao carregar calendário.']);
        }
        exit;
    }

    /**
     * Resolver reunião de emergência (N3)
     */
    public function resolverEmergencia(): void
    {
        Auth::proteger();
        Auth::exigirPerfil([Auth::ADMIN_HOLDING, Auth::CONSULTOR_INTERNO]);
        Csrf::verificar();

        $emergenciaId = (int) ($_POST['emergencia_id'] ?? 0);
        $resolucao = htmlspecialchars(trim($_POST['resolucao'] ?? ''));
        $acaoTomada = htmlspecialchars(trim($_POST['acao_tomada'] ?? ''));

        if (!$emergenciaId || empty($resolucao)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Dados obrigatórios não informados.']);
            exit;
        }

        try {
            // Buscar dados da emergência
            $emergencia = Database::queryOne(
                "SELECT * FROM agenda_emergencia WHERE id = :id AND empresa_id = :empresa_id",
                ['id' => $emergenciaId, 'empresa_id' => Auth::empresa()]
            );

            if (!$emergencia) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Reunião de emergência não encontrada.']);
                exit;
            }

            // Marcar como resolvida
            Database::execute(
                "UPDATE agenda_emergencia SET 
                 status = 'resolvido', 
                 resolucao = :resolucao, 
                 acao_tomada = :acao_tomada,
                 data_resolucao = NOW(),
                 resolvido_por = :usuario_id
                 WHERE id = :id",
                [
                    'resolucao' => $resolucao,
                    'acao_tomada' => $acaoTomada,
                    'usuario_id' => Auth::id(),
                    'id' => $emergenciaId
                ]
            );

            // Atualizar SOP se necessário (marcar revisão como concluída)
            if ($emergencia['sop_id']) {
                Database::execute(
                    "UPDATE sops SET 
                     necessita_revisao = 0,
                     data_ultima_revisao = NOW(),
                     observacoes_revisao = :observacoes
                     WHERE id = :sop_id",
                    [
                        'observacoes' => "Emergência N3 resolvida: {$resolucao}",
                        'sop_id' => $emergencia['sop_id']
                    ]
                );
            }

            Logger::acao('Emergência N3 resolvida', [
                'emergencia_id' => $emergenciaId,
                'sop_id' => $emergencia['sop_id'],
                'resolucao' => $resolucao
            ]);

            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Reunião de emergência resolvida com sucesso!'
            ]);

        } catch (Exception $e) {
            Logger::error('Erro ao resolver emergência', ['erro' => $e->getMessage()]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno.']);
        }
        exit;
    }
}