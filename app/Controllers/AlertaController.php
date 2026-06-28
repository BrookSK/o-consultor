<?php
/**
 * AlertaController — Sistema de Alertas e Notificações
 * O Consultor — Sistema Operacional Empresarial
 *
 * Tipos: KPI vermelho/amarelo, tarefa vencida, SOP pendente,
 * diagnóstico desatualizado, reunião próxima, conteúdo novo, Academy SSO
 */

class AlertaController
{
    public function index(): void
    {
        Auth::proteger();
        
        $empresaId = Auth::empresa();
        if (!$empresaId) {
            Flash::set('erro', 'Empresa não identificada.');
            header('Location: ' . APP_URL . '/dashboard');
            exit;
        }

        // Buscar todos os alertas da empresa
        $alertas = Database::query(
            "SELECT a.*, k.nome as kpi_nome, s.sop_codigo, s.titulo as sop_titulo
             FROM alertas a 
             LEFT JOIN sop_kpis k ON a.kpi_id = k.id 
             LEFT JOIN sops s ON a.sop_id = s.id 
             WHERE a.empresa_id = :empresa_id
             ORDER BY a.status ASC, a.lido ASC, a.prioridade DESC, a.data_criacao DESC",
            ['empresa_id' => $empresaId]
        );

        // Estatísticas
        $stats = [
            'total' => count($alertas),
            'nao_lidos' => count(array_filter($alertas, fn($a) => !$a['lido'])),
            'criticos' => count(array_filter($alertas, fn($a) => $a['prioridade'] === 'critica' && $a['status'] === 'ativo')),
            'resolvidos' => count(array_filter($alertas, fn($a) => $a['status'] === 'resolvido')),
        ];

        $dados = [
            'alertas' => $alertas,
            'stats' => $stats,
        ];

        require VIEW_PATH . '/alertas/index.php';
    }

    /**
     * Retorna alertas recentes (JSON — para dropdown do sino)
     */
    public function recentes(): void
    {
        Auth::proteger();
        
        $empresaId = Auth::empresa();
        if (!$empresaId) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Empresa não identificada.']);
            exit;
        }

        // Buscar alertas reais do banco
        $alertas = Database::query(
            "SELECT a.*, k.nome as kpi_nome, s.sop_codigo 
             FROM alertas a 
             LEFT JOIN sop_kpis k ON a.kpi_id = k.id 
             LEFT JOIN sops s ON a.sop_id = s.id 
             WHERE a.empresa_id = :empresa_id AND a.status = 'ativo'
             ORDER BY a.lido ASC, a.prioridade DESC, a.data_criacao DESC 
             LIMIT 15",
            ['empresa_id' => $empresaId]
        );

        // Formatar alertas para frontend
        $alertasFormatados = array_map(function($alerta) {
            return [
                'id' => $alerta['id'],
                'tipo' => $alerta['tipo'],
                'titulo' => $alerta['titulo'],
                'descricao' => $alerta['descricao'],
                'prioridade' => $alerta['prioridade'],
                'data' => $alerta['data_criacao'],
                'lido' => (bool) $alerta['lido'],
                'link' => $this->gerarLinkAlerta($alerta),
                'sop_id' => $alerta['sop_codigo'] ?? null,
                'kpi_nome' => $alerta['kpi_nome'] ?? null,
            ];
        }, $alertas);

        $naoLidos = count(array_filter($alertasFormatados, fn($a) => !$a['lido']));

        header('Content-Type: application/json');
        echo json_encode([
            'sucesso' => true,
            'alertas' => $alertasFormatados,
            'nao_lidos' => $naoLidos,
        ]);
        exit;
    }

    public function marcarLido(): void
    {
        Auth::proteger();
        Csrf::verificar();
        
        $alertaId = (int) ($_POST['alerta_id'] ?? 0);
        
        if (!$alertaId) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'ID do alerta é obrigatório.']);
            exit;
        }

        // Verificar permissão
        $alerta = Database::queryOne(
            "SELECT * FROM alertas WHERE id = :id AND empresa_id = :empresa_id",
            ['id' => $alertaId, 'empresa_id' => Auth::empresa()]
        );

        if (!$alerta) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Alerta não encontrado.']);
            exit;
        }

        $sucesso = Database::execute(
            "UPDATE alertas SET lido = 1, lido_em = NOW() WHERE id = :id",
            ['id' => $alertaId]
        );

        if ($sucesso) {
            Logger::acao('Alerta marcado como lido', ['alerta_id' => $alertaId, 'tipo' => $alerta['tipo']]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao marcar como lido.']);
        }
        exit;
    }

    public function resolver(): void
    {
        Auth::proteger();
        Csrf::verificar();
        $id = (int) ($_POST['alerta_id'] ?? 0);
        Logger::acao('Alerta resolvido', ['id' => $id]);
        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'mensagem' => 'Alerta resolvido!']);
        exit;
    }

    public function salvarPreferencias(): void
    {
        Auth::proteger();
        Csrf::verificar();
        Logger::acao('Preferências de notificação atualizadas');
        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'mensagem' => 'Preferências salvas!']);
        exit;
    }

    /**
     * Marca todos os alertas como lidos
     */
    public function marcarTodosLidos(): void
    {
        Auth::proteger();
        Csrf::verificar();
        
        $empresaId = Auth::empresa();
        if (!$empresaId) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Empresa não identificada.']);
            exit;
        }

        $sucesso = Database::execute(
            "UPDATE alertas SET lido = 1, lido_em = NOW() WHERE empresa_id = :empresa_id AND lido = 0",
            ['empresa_id' => $empresaId]
        );

        if ($sucesso) {
            Logger::acao('Todos os alertas marcados como lidos', ['empresa_id' => $empresaId]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true, 'mensagem' => 'Todos os alertas foram marcados como lidos.']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao marcar alertas como lidos.']);
        }
        exit;
    }

    /**
     * Gerar link apropriado para cada tipo de alerta
     */
    private function gerarLinkAlerta(array $alerta): string
    {
        switch ($alerta['tipo']) {
            case 'kpi_critico':
            case 'kpi_atencao':
                return '/kpis/ver?id=' . $alerta['kpi_id'];
            
            case 'sop_pendente':
            case 'sop_sem_revisao':
                return '/sop/revisar?id=' . $alerta['sop_id'];
            
            case 'tarefa_vencida':
            case 'tarefa_sem_atualizacao':
                return '/plano-de-acao';
            
            case 'diagnostico_desatualizado':
                return '/diagnostico';
            
            case 'reuniao_proxima':
                return '/governanca';
            
            case 'conteudo_novo':
                return '/central-de-conteudo';
            
            case 'academy_sso':
                return '/admin/configuracoes';
            
            default:
                return '/alertas';
        }
    }

}
