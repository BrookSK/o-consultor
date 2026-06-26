<?php
/**
 * ConteudoController — Central de Conteúdo
 * Notícias por IA, Academy SSO, Casos Reais, Inteligência de Mercado
 */

class ConteudoController
{
    public function index(): void
    {
        Auth::proteger();

        $dados = [
            'noticias' => $this->getNoticiasMock(),
            'casos' => $this->getCasosMock(),
            'inteligencia' => $this->getInteligenciaMock(),
            'perfil_busca' => $this->getPerfilBuscaMock(),
            'academy_url' => 'https://myacademy.com.br',
            'usuario' => Auth::usuario(),
        ];

        require VIEW_PATH . '/conteudo/index.php';
    }

    public function noticiaDetalhe(): void
    {
        Auth::proteger();
        $id = (int) ($_GET['id'] ?? 1);
        $dados = ['noticia' => $this->getNoticiaDetalheMock($id)];
        require VIEW_PATH . '/conteudo/noticia-detalhe.php';
    }

    public function casoDetalhe(): void
    {
        Auth::proteger();
        $dados = ['caso' => $this->getCasoDetalheMock()];
        require VIEW_PATH . '/conteudo/caso-detalhe.php';
    }

    public function salvarPerfilBusca(): void
    {
        Auth::proteger();
        Csrf::verificar();
        Logger::acao('Perfil de busca atualizado');
        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'mensagem' => 'Perfil de busca salvo!']);
        exit;
    }

    public function buscarAgora(): void
    {
        Auth::proteger();
        Csrf::verificar();
        Logger::acao('Busca de conteúdo disparada manualmente');
        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'mensagem' => 'Busca realizada! Novos conteúdos disponíveis.']);
        exit;
    }

    public function admin(): void
    {
        Auth::exigirPerfil([Auth::ADMIN_HOLDING, Auth::CONSULTOR_INTERNO]);
        $dados = [
            'noticias' => $this->getNoticiasMock(),
            'casos' => $this->getCasosMock(),
        ];
        require VIEW_PATH . '/conteudo/admin.php';
    }

    // ===== MOCKS =====

    private function getPerfilBuscaMock(): array
    {
        return [
            'setor' => 'Tecnologia',
            'lingua' => 'Português',
            'sites' => [
                'https://www.computerworld.com.br',
                'https://www.infoq.com/br',
                'https://devto.com.br',
                'https://canaltech.com.br',
                'https://tiinside.com.br',
            ],
            'palavras_chave' => ['cloud computing', 'cibersegurança', 'automação', 'IA empresarial'],
            'frequencia' => 'diaria',
            'ultimo_update' => '2026-06-26 08:30:00',
        ];
    }

    private function getNoticiasMock(): array
    {
        return [
            ['id' => 1, 'fonte' => 'Computerworld', 'titulo' => 'Novas regras de LGPD para empresas de TI entram em vigor em agosto', 'data' => '2026-06-26', 'categoria' => 'Regulamentação', 'resumo' => 'A ANPD publicou resolução ampliando obrigações de prestadores de serviço de TI em tratamento de dados sensíveis.', 'relevancia' => 'alta'],
            ['id' => 2, 'fonte' => 'InfoQ Brasil', 'titulo' => 'Adoção de IA em MSPs cresce 340% no último ano', 'data' => '2026-06-25', 'categoria' => 'Tecnologia', 'resumo' => 'Pesquisa mostra que prestadores de serviço gerenciado estão integrando IA em monitoramento e atendimento.', 'relevancia' => 'alta'],
            ['id' => 3, 'fonte' => 'CanalTech', 'titulo' => 'Microsoft anuncia mudanças no modelo de licenciamento CSP', 'data' => '2026-06-24', 'categoria' => 'Mercado', 'resumo' => 'Alterações no programa de parceiros afetam margens de revenda e modelo de faturamento.', 'relevancia' => 'media'],
            ['id' => 4, 'fonte' => 'TI Inside', 'titulo' => 'Ransomware mira PMEs brasileiras com ataques mais sofisticados', 'data' => '2026-06-23', 'categoria' => 'Tendência', 'resumo' => 'Relatório aponta aumento de 180% em ataques direcionados a empresas com 10-50 funcionários.', 'relevancia' => 'alta'],
            ['id' => 5, 'fonte' => 'Computerworld', 'titulo' => 'Mercado de cloud no Brasil deve movimentar R$ 45 bi até 2027', 'data' => '2026-06-22', 'categoria' => 'Negócio', 'resumo' => 'Estudo IDC projeta crescimento de 28% ao ano no segmento de infraestrutura como serviço.', 'relevancia' => 'media'],
        ];
    }

    private function getNoticiaDetalheMock(int $id): array
    {
        return [
            'id' => $id,
            'fonte' => 'Computerworld',
            'titulo' => 'Novas regras de LGPD para empresas de TI entram em vigor em agosto',
            'data' => '2026-06-26',
            'categoria' => 'Regulamentação',
            'relevancia' => 'alta',
            'bloco_noticia' => 'A ANPD publicou resolução CD/ANPD nº 15/2026 ampliando obrigações de prestadores de serviço de TI que atuam como operadores de dados pessoais. As novas regras exigem: (1) relatório de impacto obrigatório para contratos acima de 500 titulares; (2) notificação em 24h para qualquer incidente; (3) certificação anual de conformidade emitida por auditor independente.',
            'bloco_significa' => 'Para empresas de TI que gerenciam dados de clientes, isso significa que contratos existentes precisam ser revisados até agosto. O custo de conformidade pode aumentar 15-20% mas a penalidade por descumprimento é de 2% do faturamento (limitado a R$ 50 milhões). Empresas certificadas terão vantagem competitiva na conquista de clientes corporativos.',
            'bloco_fazer' => '1. Realizar inventário de todos os contratos com tratamento de dados pessoais.\n2. Identificar quais contratos ultrapassam 500 titulares.\n3. Agendar revisão contratual com jurídico até julho/2026.\n4. Iniciar processo de certificação LGPD com auditoria interna.\n5. Implementar monitoramento de incidentes com alerta automatizado em <24h.',
            'bloco_pergunta' => 'Quantos dos seus contratos atuais possuem cláusulas de tratamento de dados atualizadas? Qual o custo estimado para adequação completa?',
            'bloco_conexao' => 'O SOP-TI-JUR-001 (LGPD e tratamento de dados) do seu Manual Operacional já contempla as bases para essa adequação. Recomendamos revisá-lo com o novo marco regulatório e atualizar o checklist de evidências obrigatórias.',
        ];
    }

    private function getCasosMock(): array
    {
        return [
            ['id' => 1, 'titulo' => 'De 0% a 100% de processos documentados em 90 dias', 'setor' => 'Tecnologia', 'desafio' => 'Empresa com 12 colaboradores sem nenhum processo documentado.', 'resultado' => '16 SOPs criados, tempo de onboarding reduzido de 30 para 7 dias.', 'exclusivo' => false],
            ['id' => 2, 'titulo' => 'Redução de 60% no churn após diagnóstico operacional', 'setor' => 'SaaS', 'desafio' => 'Taxa de cancelamento mensal de 8%, sem visibilidade de causas.', 'resultado' => 'Churn reduzido para 3.2%, NPS subiu de 32 para 67.', 'exclusivo' => true],
            ['id' => 3, 'titulo' => 'Faturamento dobrou com reestruturação comercial', 'setor' => 'Varejo', 'desafio' => 'Loja física sem presença digital e dependente de fluxo orgânico.', 'resultado' => 'E-commerce implementado, faturamento de R$80k para R$165k/mês.', 'exclusivo' => false],
        ];
    }

    private function getCasoDetalheMock(): array
    {
        return [
            'titulo' => 'De 0% a 100% de processos documentados em 90 dias',
            'setor' => 'Tecnologia',
            'problema' => 'Empresa de TI com 12 colaboradores operava inteiramente sem processos documentados. Conhecimento concentrado em 2 pessoas. Cada novo colaborador levava 30 dias para se tornar produtivo. Qualidade de entrega inconsistente.',
            'diagnostico' => 'Diagnóstico apontou maturidade nível 1 (Inicial). Principais gaps: operações 100% verbais, dependência crítica de pessoas-chave, sem checklist de qualidade, sem padrão de atendimento.',
            'processo' => 'Plano de ação com 12 tarefas em 3 fases: Fase 1 (30 dias) — mapear 5 processos críticos; Fase 2 (60 dias) — gerar SOPs via IA e validar com equipe; Fase 3 (90 dias) — implementar, treinar e auditar.',
            'implementacao' => '16 SOPs gerados pela plataforma O Consultor com customização pelo gestor. Treinamento gravado em vídeo para cada SOP. Checklist operacional integrado ao sistema de chamados. KPIs nativos monitorados semanalmente.',
            'resultado' => 'Tempo de onboarding: 30 dias → 7 dias. Retrabalho: redução de 45%. Satisfação do cliente (NPS): 42 → 71. Escalabilidade: empresa contratou 4 pessoas sem queda de qualidade. ROI: investimento recuperado em 45 dias.',
            'licoes' => 'O principal fator de sucesso foi a geração individual de cada SOP com nível de detalhe que permite execução sem supervisão. Empresas que tentam documentar tudo de uma vez falham. O método de 1 SOP por vez com aprovação imediata garante aderência.',
        ];
    }

    private function getInteligenciaMock(): array
    {
        return [
            ['id' => 1, 'fonte' => 'Gartner', 'titulo' => 'Previsão: 75% dos MSPs usarão IA em operações até 2028', 'tipo' => 'Tendência', 'data' => '2026-06-25', 'relevancia' => 'alta', 'setor' => 'Tecnologia', 'impacto' => 'Empresas que não adotarem IA operacional perderão competitividade em 2 anos.', 'acao' => 'Iniciar piloto de IA em monitoramento ou atendimento N1 ainda em 2026.'],
            ['id' => 2, 'fonte' => 'ANPD', 'titulo' => 'Nova resolução exige DPO certificado para operadores de dados', 'tipo' => 'Regulamentação', 'data' => '2026-06-24', 'relevancia' => 'alta', 'setor' => 'Todos', 'impacto' => 'Custo de compliance aumentará. Empresas sem DPO podem ser multadas a partir de jan/2027.', 'acao' => 'Avaliar necessidade de DPO interno vs. terceirizado. Orçar certificação.'],
            ['id' => 3, 'fonte' => 'IDC Brasil', 'titulo' => 'Ticket médio de serviços gerenciados sobe 22% no Brasil', 'tipo' => 'Mercado', 'data' => '2026-06-22', 'relevancia' => 'media', 'setor' => 'Tecnologia', 'impacto' => 'Clientes estão dispostos a pagar mais por SLA diferenciado e proatividade.', 'acao' => 'Revisar precificação e incluir tiers de SLA premium no portfólio.'],
        ];
    }
}
