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
        $dados = ['alertas' => $this->getAlertasMock()];
        require VIEW_PATH . '/alertas/index.php';
    }

    /**
     * Retorna alertas recentes (JSON — para dropdown do sino)
     */
    public function recentes(): void
    {
        Auth::proteger();
        $alertas = array_slice($this->getAlertasMock(), 0, 10);
        $naoLidos = count(array_filter($alertas, fn($a) => !$a['lido']));

        header('Content-Type: application/json');
        echo json_encode([
            'sucesso' => true,
            'alertas' => $alertas,
            'nao_lidos' => $naoLidos,
        ]);
        exit;
    }

    public function marcarLido(): void
    {
        Auth::proteger();
        Csrf::verificar();
        $id = (int) ($_POST['alerta_id'] ?? 0);
        Logger::acao('Alerta marcado como lido', ['id' => $id]);
        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true]);
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

    private function getAlertasMock(): array
    {
        return [
            ['id' => 1, 'tipo' => 'kpi_vermelho', 'titulo' => 'KPI em zona vermelha', 'descricao' => 'Taxa de Conversão: atual 9% (meta 15%). SOP-TI-COM-001.', 'modulo' => 'KPIs', 'prioridade' => 'alta', 'data' => '2026-06-26 09:00', 'lido' => false, 'link' => '/manual-operacional/kpis', 'sop_id' => 'SOP-TI-COM-001'],
            ['id' => 2, 'tipo' => 'tarefa_vencida', 'titulo' => 'Tarefa vencida', 'descricao' => '"Resolver integração ERP-Ecommerce" — venceu em 28/06. Responsável: Lucas Tech.', 'modulo' => 'Plano de Ação', 'prioridade' => 'alta', 'data' => '2026-06-26 08:30', 'lido' => false, 'link' => '/plano-de-acao/ver?id=1', 'sop_id' => null],
            ['id' => 3, 'tipo' => 'kpi_amarelo', 'titulo' => 'KPI em zona amarela', 'descricao' => 'SLA de chamados: 94% (meta >95%). Monitorando.', 'modulo' => 'KPIs', 'prioridade' => 'media', 'data' => '2026-06-26 08:00', 'lido' => false, 'link' => '/manual-operacional/kpis', 'sop_id' => 'SOP-TI-OPS-001'],
            ['id' => 4, 'tipo' => 'tarefa_sem_atualizacao', 'titulo' => 'Tarefa sem atualização 7+ dias', 'descricao' => '"Mapear processos de TI" — sem atualização desde 20/06.', 'modulo' => 'Plano de Ação', 'prioridade' => 'media', 'data' => '2026-06-26 07:00', 'lido' => false, 'link' => '/plano-de-acao/ver?id=1', 'sop_id' => null],
            ['id' => 5, 'tipo' => 'sop_pendente', 'titulo' => 'SOP sem aprovação 30+ dias', 'descricao' => 'SOP-TI-JUR-001 gerado há 35 dias e ainda em revisão.', 'modulo' => 'Manual Operacional', 'prioridade' => 'media', 'data' => '2026-06-25 16:00', 'lido' => true, 'link' => '/sop/revisar?id=SOP-TI-JUR-001', 'sop_id' => 'SOP-TI-JUR-001'],
            ['id' => 6, 'tipo' => 'reuniao_proxima', 'titulo' => 'Reunião em menos de 24h', 'descricao' => 'Comitê de Governança Mensal — amanhã às 10:00.', 'modulo' => 'Governança', 'prioridade' => 'info', 'data' => '2026-06-25 14:00', 'lido' => true, 'link' => '/governanca', 'sop_id' => null],
            ['id' => 7, 'tipo' => 'conteudo_novo', 'titulo' => 'Novo conteúdo relevante', 'descricao' => 'Nova regulamentação LGPD identificada para o seu setor.', 'modulo' => 'Central de Conteúdo', 'prioridade' => 'info', 'data' => '2026-06-25 10:00', 'lido' => true, 'link' => '/central-de-conteudo/noticia?id=1', 'sop_id' => null],
            ['id' => 8, 'tipo' => 'diagnostico_desatualizado', 'titulo' => 'Diagnóstico desatualizado', 'descricao' => 'Diagnóstico de Varejo Express tem 95 dias. Recomendamos atualizar.', 'modulo' => 'Diagnóstico', 'prioridade' => 'baixa', 'data' => '2026-06-24 09:00', 'lido' => true, 'link' => '/diagnostico', 'sop_id' => null],
            ['id' => 9, 'tipo' => 'sop_sem_revisao', 'titulo' => 'SOP sem revisão há 6+ meses', 'descricao' => 'SOP-TI-OPS-001 aprovado há 7 meses. Revisar para manter atualizado.', 'modulo' => 'Manual Operacional', 'prioridade' => 'baixa', 'data' => '2026-06-23 09:00', 'lido' => true, 'link' => '/sop/revisar?id=SOP-TI-OPS-001', 'sop_id' => 'SOP-TI-OPS-001'],
            ['id' => 10, 'tipo' => 'academy_sso', 'titulo' => 'Academy não vinculada', 'descricao' => 'Ana Costa tentou acessar Academy mas não tem conta vinculada.', 'modulo' => 'Academy', 'prioridade' => 'info', 'data' => '2026-06-22 15:00', 'lido' => true, 'link' => '/admin/configuracoes', 'sop_id' => null],
        ];
    }
}
