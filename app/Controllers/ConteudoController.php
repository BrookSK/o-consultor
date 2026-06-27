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

        $empresaId = Auth::empresa();
        if (!$empresaId) {
            Flash::erro('Empresa não identificada.');
            header('Location: ' . APP_URL . '/dashboard');
            exit;
        }

        // Buscar dados reais do banco
        $noticias = $this->buscarNoticiasReais($empresaId);
        $perfilBusca = $this->buscarPerfilBusca($empresaId);
        
        $dados = [
            'noticias' => $noticias,
            'casos' => $this->getCasosMock(), // Manter mock por enquanto
            'inteligencia' => $this->getInteligenciaMock(), // Manter mock por enquanto
            'perfil_busca' => $perfilBusca,
            'academy_url' => Configuracao::get('academy_url', 'https://myacademy.com.br'),
            'usuario' => Auth::usuario(),
        ];

        require VIEW_PATH . '/conteudo/index.php';
    }

    public function noticiaDetalhe(): void
    {
        Auth::proteger();
        
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) {
            Flash::erro('Notícia não encontrada.');
            header('Location: ' . APP_URL . '/central-de-conteudo');
            exit;
        }

        // Buscar notícia real do banco
        $noticia = Database::queryOne(
            "SELECT * FROM noticias 
             WHERE id = :id AND empresa_id = :empresa_id",
            ['id' => $id, 'empresa_id' => Auth::empresa()]
        );

        if (!$noticia) {
            Flash::erro('Notícia não encontrada ou sem permissão.');
            header('Location: ' . APP_URL . '/central-de-conteudo');
            exit;
        }

        // Marcar como visualizada
        Database::execute(
            "UPDATE noticias SET visualizada = 1 WHERE id = :id",
            ['id' => $id]
        );

        $dados = ['noticia' => $this->formatarNoticiaDetalhes($noticia)];
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
        
        $empresaId = Auth::empresa();
        if (!$empresaId) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Empresa não identificada.']);
            exit;
        }

        // Verificar se alguma API está configurada
        if (!Configuracao::apiAtiva('perplexity') && !Configuracao::apiAtiva('openai') && !Configuracao::apiAtiva('anthropic')) {
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => false, 
                'erro' => 'Nenhuma API de conteúdo configurada. Configure em Admin > APIs de Conteúdo.'
            ]);
            exit;
        }

        // Usar NoticiasController para processar
        $noticiasController = new NoticiasController();
        
        try {
            // Simular GET request com manual=1
            $_GET['manual'] = '1';
            
            // Capturar output buffer
            ob_start();
            $noticiasController->buscar();
            $response = ob_get_clean();
            
            // Se chegou aqui, retornar a resposta
            header('Content-Type: application/json');
            echo $response;
            
        } catch (Exception $e) {
            Logger::error('Erro na busca manual de notícias', [
                'erro' => $e->getMessage(),
                'empresa_id' => $empresaId
            ]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => false,
                'erro' => 'Erro ao buscar notícias. Tente novamente em alguns minutos.'
            ]);
        }
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

    // ===== REAL DATA METHODS =====

    /**
     * Buscar notícias reais do banco de dados
     */
    private function buscarNoticiasReais(int $empresaId): array
    {
        $noticias = Database::query(
            "SELECT id, titulo, fonte, data_publicacao as data, categoria, 
                    LEFT(bloco1_noticia, 150) as resumo, relevancia,
                    visualizada, favoritada
             FROM noticias 
             WHERE empresa_id = :empresa_id AND arquivada = 0
             ORDER BY data_publicacao DESC, criado_em DESC 
             LIMIT 50",
            ['empresa_id' => $empresaId]
        );

        // Formatar para compatibilidade com view
        return array_map(function($noticia) {
            return [
                'id' => $noticia['id'],
                'fonte' => $noticia['fonte'],
                'titulo' => $noticia['titulo'],
                'data' => $noticia['data'],
                'categoria' => $noticia['categoria'],
                'resumo' => $noticia['resumo'] . '...',
                'relevancia' => $noticia['relevancia'],
                'nova' => !$noticia['visualizada'],
                'favoritada' => (bool) $noticia['favoritada'],
            ];
        }, $noticias);
    }

    /**
     * Buscar perfil de busca real da empresa
     */
    private function buscarPerfilBusca(int $empresaId): array
    {
        // Buscar dados da empresa
        $empresa = Database::queryOne(
            "SELECT setor, lingua_principal FROM empresas WHERE id = :id",
            ['id' => $empresaId]
        );

        // Buscar sites de referência
        $sites = Database::query(
            "SELECT site_url FROM empresa_perfil_busca 
             WHERE empresa_id = :empresa_id AND ativo = 1
             ORDER BY adicionado_por DESC, criado_em ASC",
            ['empresa_id' => $empresaId]
        );

        // Buscar último log de busca
        $ultimaBusca = Database::queryOne(
            "SELECT criado_em FROM busca_logs 
             WHERE empresa_id = :empresa_id 
             ORDER BY criado_em DESC LIMIT 1",
            ['empresa_id' => $empresaId]
        );

        return [
            'setor' => $empresa['setor'] ?? 'Tecnologia',
            'lingua' => $empresa['lingua_principal'] ?? 'Português',
            'sites' => array_column($sites, 'site_url'),
            'palavras_chave' => ['IA empresarial', 'automação', 'produtividade'], // TODO: implementar tabela própria
            'frequencia' => 'diaria', // TODO: buscar da configuração
            'ultimo_update' => $ultimaBusca['criado_em'] ?? date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Formatar notícia para visualização detalhada
     */
    private function formatarNoticiaDetalhes(array $noticia): array
    {
        return [
            'id' => $noticia['id'],
            'fonte' => $noticia['fonte'],
            'titulo' => $noticia['titulo'],
            'data' => $noticia['data_publicacao'],
            'categoria' => $noticia['categoria'],
            'relevancia' => $noticia['relevancia'],
            'bloco_noticia' => $noticia['bloco1_noticia'],
            'bloco_significa' => $noticia['bloco2_significa'],
            'bloco_fazer' => $noticia['bloco3_o_que_fazer'],
            'bloco_pergunta' => $noticia['bloco4_pergunta'],
            'bloco_conexao' => $noticia['bloco5_conexao'],
            'tags' => json_decode($noticia['tags'] ?? '[]', true),
            'url' => $noticia['url'],
        ];
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
