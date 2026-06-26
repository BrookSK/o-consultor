<?php
/**
 * GovernancaController — Módulo de Governança
 * Hierarquia, reuniões, compliance e princípios
 */

class GovernancaController
{
    public function index(): void
    {
        Auth::proteger();
        Auth::exigirPerfil([Auth::ADMIN_HOLDING, Auth::CONSULTOR_INTERNO]);

        $dados = [
            'reunioes' => [
                ['id' => 1, 'data' => '2026-06-26', 'hora' => '10:00', 'titulo' => 'Comitê de Governança Mensal', 'participantes' => 'Diretoria + Consultores', 'status' => 'agendada'],
                ['id' => 2, 'data' => '2026-06-19', 'hora' => '14:00', 'titulo' => 'Revisão de Compliance Q2', 'participantes' => 'Jurídico + Operações', 'status' => 'realizada'],
                ['id' => 3, 'data' => '2026-06-12', 'hora' => '09:30', 'titulo' => 'Alinhamento de Parceiros', 'participantes' => 'Gerência + Parceiros', 'status' => 'realizada'],
            ],
            'compliance' => [
                ['item' => 'LGPD — Política de privacidade atualizada', 'status' => 'conforme', 'ultima' => '2026-06-15'],
                ['item' => 'Contratos de parceiros revisados', 'status' => 'conforme', 'ultima' => '2026-06-10'],
                ['item' => 'Backup de dados — teste de restore', 'status' => 'conforme', 'ultima' => '2026-06-20'],
                ['item' => 'Auditoria de acessos (usuários e permissões)', 'status' => 'pendente', 'ultima' => '2026-05-01'],
                ['item' => 'Seguro de responsabilidade civil vigente', 'status' => 'conforme', 'ultima' => '2026-03-01'],
                ['item' => 'Certificação ISO 27001 — renovação', 'status' => 'atencao', 'ultima' => '2026-01-15'],
            ],
        ];

        require VIEW_PATH . '/governanca/index.php';
    }

    public function salvarReuniao(): void
    {
        Auth::proteger();
        Csrf::verificar();
        Logger::acao('Reunião de governança registrada');
        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'mensagem' => 'Reunião registrada!']);
        exit;
    }

    public function registrarAuditoria(): void
    {
        Auth::proteger();
        Csrf::verificar();
        Logger::acao('Auditoria de compliance registrada');
        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'mensagem' => 'Auditoria registrada!']);
        exit;
    }
}
