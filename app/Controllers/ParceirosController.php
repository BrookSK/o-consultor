<?php
/**
 * ParceirosController — Módulo de Parceiros
 * Vitrine, perfil, solicitação e gestão administrativa
 */

class ParceirosController
{
    public function index(): void
    {
        Auth::proteger();
        $dados = ['parceiros' => $this->getMock()];
        require VIEW_PATH . '/parceiros/index.php';
    }

    public function perfil(): void
    {
        Auth::proteger();
        $dados = ['parceiro' => $this->getPerfilMock()];
        require VIEW_PATH . '/parceiros/perfil.php';
    }

    public function solicitar(): void
    {
        Auth::proteger();
        Csrf::verificar();
        Logger::acao('Solicitação de parceiro', ['parceiro' => $_POST['parceiro_id'] ?? '']);
        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'mensagem' => 'Solicitação enviada! Entraremos em contato.']);
        exit;
    }

    public function admin(): void
    {
        Auth::exigirPerfil([Auth::ADMIN_HOLDING]);
        $dados = ['parceiros' => $this->getMock()];
        require VIEW_PATH . '/parceiros/admin.php';
    }

    public function atualizarStatus(): void
    {
        Auth::exigirPerfil([Auth::ADMIN_HOLDING]);
        Csrf::verificar();
        Logger::acao('Status de parceiro atualizado');
        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'mensagem' => 'Status atualizado!']);
        exit;
    }

    private function getMock(): array
    {
        return [
            ['id' => 1, 'nome' => 'CloudTech Soluções', 'categoria' => 'Tecnologia', 'especialidades' => ['Cloud AWS', 'Segurança', 'DevOps'], 'status' => 'homologado', 'avaliacao' => 4.8, 'sobre' => 'Especialista em infraestrutura cloud para PMEs.'],
            ['id' => 2, 'nome' => 'Marketing Pro Digital', 'categoria' => 'Marketing', 'especialidades' => ['SEO', 'Tráfego pago', 'Social media'], 'status' => 'homologado', 'avaliacao' => 4.5, 'sobre' => 'Agência focada em performance para empresas B2B.'],
            ['id' => 3, 'nome' => 'Jurídico Empresarial', 'categoria' => 'Jurídico', 'especialidades' => ['LGPD', 'Contratos', 'Societário'], 'status' => 'homologado', 'avaliacao' => 4.9, 'sobre' => 'Escritório especializado em direito empresarial e compliance.'],
            ['id' => 4, 'nome' => 'Contabilidade Express', 'categoria' => 'Finanças', 'especialidades' => ['Contabilidade', 'BPO financeiro', 'Planejamento tributário'], 'status' => 'em_avaliacao', 'avaliacao' => 4.2, 'sobre' => 'Contabilidade digital para empresas de tecnologia.'],
            ['id' => 5, 'nome' => 'RH Conecta', 'categoria' => 'RH', 'especialidades' => ['Recrutamento tech', 'Cultura organizacional', 'Treinamento'], 'status' => 'homologado', 'avaliacao' => 4.6, 'sobre' => 'Soluções de pessoas para empresas em crescimento.'],
            ['id' => 6, 'nome' => 'LogiSmart', 'categoria' => 'Logística', 'especialidades' => ['Last mile', 'Fulfillment', 'WMS'], 'status' => 'suspenso', 'avaliacao' => 3.8, 'sobre' => 'Logística inteligente para e-commerce.'],
        ];
    }

    private function getPerfilMock(): array
    {
        return [
            'id' => 1, 'nome' => 'CloudTech Soluções', 'categoria' => 'Tecnologia',
            'especialidades' => ['Cloud AWS', 'Segurança', 'DevOps', 'Backup', 'Monitoramento'],
            'status' => 'homologado', 'avaliacao' => 4.8,
            'sobre' => 'Empresa especializada em infraestrutura cloud para PMEs, com mais de 8 anos de experiência e certificações AWS, Azure e Google Cloud. Atendemos mais de 120 empresas no Brasil.',
            'portfolio' => [
                ['titulo' => 'Migração AWS para empresa de 200 usuários', 'resultado' => 'Redução de 40% no custo de infra'],
                ['titulo' => 'Implementação de SOC 24/7', 'resultado' => 'Zero incidentes de segurança em 18 meses'],
                ['titulo' => 'Disaster Recovery multi-region', 'resultado' => 'RTO de 15 minutos garantido'],
            ],
            'avaliacoes' => [
                ['nota' => 5, 'comentario' => 'Excelente suporte e proatividade. Recomendo.', 'data' => '2026-06-10'],
                ['nota' => 5, 'comentario' => 'Migração sem downtime. Time muito competente.', 'data' => '2026-05-20'],
                ['nota' => 4, 'comentario' => 'Bom trabalho, apenas o prazo ficou um pouco apertado.', 'data' => '2026-04-15'],
            ],
        ];
    }
}
