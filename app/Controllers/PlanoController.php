<?php
/**
 * PlanoController — Módulo de Plano de Ação Estratégico
 * O Consultor — Sistema Operacional Empresarial
 *
 * ACESSO: ADMIN_HOLDING, CONSULTOR_INTERNO (gerenciam), CLIENTE (visualiza e atualiza)
 */

class PlanoController
{
    /**
     * Lista de planos de ação
     */
    public function index(): void
    {
        Auth::proteger();

        $dados = [
            'planos' => $this->getPlanosMock(),
        ];

        require VIEW_PATH . '/plano/index.php';
    }

    /**
     * Wizard de criação (3 etapas)
     */
    public function novo(): void
    {
        Auth::proteger();
        Auth::exigirPerfil([Auth::ADMIN_HOLDING, Auth::CONSULTOR_INTERNO]);

        $dados = [
            'empresas' => $this->getEmpresasMock(),
            'diagnosticos' => $this->getDiagnosticosMock(),
        ];

        require VIEW_PATH . '/plano/novo.php';
    }

    /**
     * Salva o plano via AJAX
     */
    public function salvar(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $titulo = htmlspecialchars(trim($_POST['titulo'] ?? ''));
        $empresaId = (int) ($_POST['empresa_id'] ?? 0);
        $diagnosticoId = (int) ($_POST['diagnostico_id'] ?? 0);
        $objetivo = htmlspecialchars(trim($_POST['objetivo'] ?? ''));
        $dataInicio = $_POST['data_inicio'] ?? '';
        $dataFim = $_POST['data_fim'] ?? '';
        $tarefas = json_decode($_POST['tarefas'] ?? '[]', true);

        if (empty($titulo) || $empresaId === 0) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Título e empresa são obrigatórios.']);
            exit;
        }

        Logger::acao('Plano de Ação criado', [
            'titulo' => $titulo,
            'empresa_id' => $empresaId,
            'total_tarefas' => count($tarefas),
        ]);

        header('Content-Type: application/json');
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Plano de Ação criado com sucesso!',
            'redirect' => APP_URL . '/plano-de-acao/ver?id=1',
        ]);
        exit;
    }

    /**
     * Visualização do plano (Kanban + Lista)
     */
    public function ver(): void
    {
        Auth::proteger();

        $dados = [
            'plano' => $this->getPlanoDetalhadoMock(),
        ];

        require VIEW_PATH . '/plano/ver.php';
    }

    /**
     * Atualiza status de uma tarefa via AJAX
     */
    public function atualizarTarefaStatus(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $tarefaId = (int) ($_POST['tarefa_id'] ?? 0);
        $novoStatus = htmlspecialchars(trim($_POST['status'] ?? ''));

        $statusValidos = ['pendente', 'em_andamento', 'bloqueado', 'concluido'];
        if (!in_array($novoStatus, $statusValidos)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Status inválido.']);
            exit;
        }

        Logger::acao('Tarefa status atualizado', ['tarefa_id' => $tarefaId, 'status' => $novoStatus]);

        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'mensagem' => 'Status atualizado!']);
        exit;
    }

    /**
     * Registra reunião via AJAX
     */
    public function registrarReuniao(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $data = htmlspecialchars(trim($_POST['data_reuniao'] ?? ''));
        $participantes = htmlspecialchars(trim($_POST['participantes'] ?? ''));
        $decisoes = htmlspecialchars(trim($_POST['decisoes'] ?? ''));
        $proximosPassos = htmlspecialchars(trim($_POST['proximos_passos'] ?? ''));

        if (empty($data) || empty($decisoes)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Data e decisões são obrigatórias.']);
            exit;
        }

        Logger::acao('Reunião registrada no Plano de Ação', ['data' => $data]);

        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'mensagem' => 'Reunião registrada com sucesso!']);
        exit;
    }

    // ===== DADOS MOCKADOS =====

    private function getPlanosMock(): array
    {
        return [
            ['id' => 1, 'empresa' => 'Tech Solutions', 'data' => '2026-06-10', 'total_tarefas' => 12, 'concluidas' => 10, 'progresso' => 83, 'status' => 'ativo'],
            ['id' => 2, 'empresa' => 'Digital Commerce', 'data' => '2026-06-15', 'total_tarefas' => 8, 'concluidas' => 5, 'progresso' => 63, 'status' => 'ativo'],
            ['id' => 3, 'empresa' => 'Varejo Express', 'data' => '2026-06-01', 'total_tarefas' => 10, 'concluidas' => 3, 'progresso' => 30, 'status' => 'em_elaboracao'],
            ['id' => 4, 'empresa' => 'FoodService', 'data' => '2026-05-20', 'total_tarefas' => 6, 'concluidas' => 1, 'progresso' => 17, 'status' => 'pausado'],
            ['id' => 5, 'empresa' => 'Construtora ABC', 'data' => '2026-04-01', 'total_tarefas' => 15, 'concluidas' => 15, 'progresso' => 100, 'status' => 'concluido'],
        ];
    }

    private function getEmpresasMock(): array
    {
        return [
            ['id' => 1, 'nome' => 'Tech Solutions'],
            ['id' => 2, 'nome' => 'Digital Commerce'],
            ['id' => 3, 'nome' => 'Varejo Express'],
            ['id' => 4, 'nome' => 'FoodService'],
            ['id' => 5, 'nome' => 'Construtora ABC'],
        ];
    }

    private function getDiagnosticosMock(): array
    {
        return [
            ['id' => 1, 'empresa_id' => 1, 'empresa' => 'Tech Solutions', 'score' => 3, 'data' => '2026-06-05', 'setor' => 'Tecnologia', 'departamentos' => 'Comercial, TI, Marketing, Operações', 'problemas' => 'Falta de CRM, processos não documentados', 'faturamento' => 'R$ 300-500 mil'],
            ['id' => 2, 'empresa_id' => 2, 'empresa' => 'Digital Commerce', 'score' => 2, 'data' => '2026-06-12', 'setor' => 'Varejo', 'departamentos' => 'Comercial, Logística, Marketing', 'problemas' => 'Logística lenta, alto churn', 'faturamento' => 'R$ 100-300 mil'],
            ['id' => 3, 'empresa_id' => 3, 'empresa' => 'Varejo Express', 'score' => 2, 'data' => '2026-05-28', 'setor' => 'Varejo', 'departamentos' => 'Comercial, Financeiro', 'problemas' => 'Controle financeiro deficiente', 'faturamento' => 'R$ 50-100 mil'],
        ];
    }

    private function getPlanoDetalhadoMock(): array
    {
        return [
            'id' => 1,
            'titulo' => 'Plano de Ação — Tech Solutions',
            'empresa' => 'Tech Solutions',
            'objetivo' => 'Estruturar processos comerciais e operacionais para escalar o faturamento em 40% nos próximos 12 meses.',
            'periodo' => ['inicio' => '2026-06-10', 'fim' => '2026-12-10'],
            'status' => 'ativo',
            'total_tarefas' => 12,
            'concluidas' => 8,
            'progresso' => 67,
            'tarefas' => [
                ['id' => 1, 'titulo' => 'Implementar CRM (HubSpot)', 'area' => 'Comercial', 'responsavel' => 'João Silva', 'prazo' => '2026-07-01', 'prioridade' => 'alta', 'status' => 'concluido', 'descricao' => 'Configurar e treinar equipe no HubSpot CRM.', 'atualizado_em' => '2026-06-25'],
                ['id' => 2, 'titulo' => 'Documentar processo de vendas', 'area' => 'Comercial', 'responsavel' => 'Maria Souza', 'prazo' => '2026-07-10', 'prioridade' => 'alta', 'status' => 'concluido', 'descricao' => 'Mapear e documentar todo o funil de vendas.', 'atualizado_em' => '2026-06-24'],
                ['id' => 3, 'titulo' => 'Definir metas trimestrais', 'area' => 'Estratégia', 'responsavel' => 'Carlos Lima', 'prazo' => '2026-07-05', 'prioridade' => 'alta', 'status' => 'concluido', 'descricao' => 'OKRs para Q3 2026.', 'atualizado_em' => '2026-06-22'],
                ['id' => 4, 'titulo' => 'Criar dashboard financeiro', 'area' => 'Financeiro', 'responsavel' => 'Ana Costa', 'prazo' => '2026-07-15', 'prioridade' => 'media', 'status' => 'em_andamento', 'descricao' => 'Power BI com KPIs financeiros.', 'atualizado_em' => '2026-06-26'],
                ['id' => 5, 'titulo' => 'Treinar equipe comercial', 'area' => 'Pessoas', 'responsavel' => 'Pedro Rocha', 'prazo' => '2026-07-20', 'prioridade' => 'media', 'status' => 'em_andamento', 'descricao' => 'Programa de capacitação em vendas consultivas.', 'atualizado_em' => '2026-06-25'],
                ['id' => 6, 'titulo' => 'Mapear processos de TI', 'area' => 'Operações', 'responsavel' => 'Lucas Tech', 'prazo' => '2026-07-25', 'prioridade' => 'media', 'status' => 'pendente', 'descricao' => 'Documentar infraestrutura e processos de deploy.', 'atualizado_em' => '2026-06-20'],
                ['id' => 7, 'titulo' => 'Configurar automação de marketing', 'area' => 'Marketing', 'responsavel' => 'Julia Mkt', 'prazo' => '2026-08-01', 'prioridade' => 'baixa', 'status' => 'pendente', 'descricao' => 'Fluxos de email e lead scoring.', 'atualizado_em' => '2026-06-18'],
                ['id' => 8, 'titulo' => 'Criar SOP de onboarding', 'area' => 'Pessoas', 'responsavel' => 'Maria Souza', 'prazo' => '2026-08-10', 'prioridade' => 'baixa', 'status' => 'pendente', 'descricao' => 'Procedimento padrão de integração de novos colaboradores.', 'atualizado_em' => '2026-06-15'],
                ['id' => 9, 'titulo' => 'Resolver integração ERP-Ecommerce', 'area' => 'Operações', 'responsavel' => 'Lucas Tech', 'prazo' => '2026-06-28', 'prioridade' => 'alta', 'status' => 'bloqueado', 'descricao' => 'Aguardando retorno do fornecedor do ERP.', 'atualizado_em' => '2026-06-20'],
                ['id' => 10, 'titulo' => 'Implementar controle de NPS', 'area' => 'Comercial', 'responsavel' => 'Pedro Rocha', 'prazo' => '2026-06-20', 'prioridade' => 'media', 'status' => 'concluido', 'descricao' => 'Pesquisa automatizada pós-venda.', 'atualizado_em' => '2026-06-19'],
                ['id' => 11, 'titulo' => 'Revisar precificação', 'area' => 'Financeiro', 'responsavel' => 'Ana Costa', 'prazo' => '2026-06-22', 'prioridade' => 'alta', 'status' => 'concluido', 'descricao' => 'Análise de margem e competitividade.', 'atualizado_em' => '2026-06-21'],
                ['id' => 12, 'titulo' => 'Backup de conhecimento crítico', 'area' => 'Operações', 'responsavel' => 'João Silva', 'prazo' => '2026-06-25', 'prioridade' => 'alta', 'status' => 'concluido', 'descricao' => 'Documentar processos chave dependentes de uma pessoa.', 'atualizado_em' => '2026-06-24'],
            ],
            'reunioes' => [
                ['data' => '2026-06-20', 'participantes' => 'João, Maria, Carlos', 'decisoes' => 'Priorizar CRM. Prazo para NPS antecipado.', 'proximos_passos' => 'Lucas verificar integração ERP até sexta.'],
                ['data' => '2026-06-13', 'participantes' => 'João, Ana, Pedro', 'decisoes' => 'Definir metas Q3. Revisar precificação.', 'proximos_passos' => 'Ana entregar análise de margem até 20/06.'],
            ],
        ];
    }
}
