<?php
/**
 * SopController — Módulo de Manual Operacional com SOPs individuais
 * O Consultor — Sistema Operacional Empresarial
 *
 * PRINCÍPIO: Cada SOP é gerado INDIVIDUALMENTE com subtópicos específicos.
 * ACESSO: ADMIN/CONSULTOR (geram e editam), CLIENTE (revisa e aprova)
 */

class SopController
{
    /**
     * Tela principal — Cards por departamento
     */
    public function index(): void
    {
        Auth::proteger();

        $dados = [
            'empresa' => 'Tech Solutions',
            'setor' => 'Tecnologia',
            'maturidade' => 3,
            'norma' => 'ITIL v4 / ISO 27001',
            'departamentos' => $this->getDepartamentosMock(),
            'total_sops' => 0,
            'aprovados' => 0,
        ];

        // Contadores
        foreach ($dados['departamentos'] as $dept) {
            foreach ($dept['sops'] as $sop) {
                $dados['total_sops']++;
                if ($sop['status'] === 'aprovado') $dados['aprovados']++;
            }
        }

        require VIEW_PATH . '/sop/index.php';
    }

    /**
     * Gera um SOP individual via AJAX
     */
    public function gerar(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $sopId = htmlspecialchars(trim($_POST['sop_id'] ?? ''));
        $sopNome = htmlspecialchars(trim($_POST['sop_nome'] ?? ''));

        if (empty($sopId)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'ID do SOP é obrigatório.']);
            exit;
        }

        // Dados da empresa (em produção: ler do banco via Session/diagnostico)
        $empresa = [
            'nome' => Session::get('diagnostico_dados')['empresa_nome'] ?? 'Tech Solutions',
            'setor' => 'Tecnologia',
            'colaboradores' => '25',
            'faturamento' => 'R$ 300-500 mil',
            'maturidade' => 3,
            'departamentos' => 'Comercial, TI, Operações, Marketing, Financeiro',
            'ferramentas' => 'Zabbix, GLPI, Veeam, Azure, Microsoft 365, FortiGate',
            'problemas' => 'Processos não documentados, dependência de pessoas-chave',
            'objetivos' => 'Escalar faturamento em 40% em 12 meses com processos padronizados',
        ];

        $sopData = [
            'id' => $sopId,
            'nome' => $sopNome,
            'departamento' => $this->getDepartamentoPorId($sopId),
            'subtopicos_texto' => $this->getSubtopicosPorId($sopId),
        ];

        // Gerar prompt estruturado
        $prompt = ApiHelper::buildPromptSop($empresa, $sopData);

        // Chamar IA (GPT ou Claude conforme config)
        $resultado = ApiHelper::chamarAnalise($prompt, true);

        if ($resultado['sucesso'] && is_array($resultado['conteudo'])) {
            // Sucesso: formatar e salvar
            $sopGerado = $this->formatarRespostaIA($resultado['conteudo'], $sopId, $sopNome, $empresa);
            Session::set('sop_gerado', $sopGerado);
            Logger::acao('SOP gerado via IA com sucesso', ['sop_id' => $sopId]);

            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'SOP gerado com sucesso!',
                'redirect' => APP_URL . '/sop/revisar?id=' . urlencode($sopId),
            ]);
        } else {
            // Fallback: usar mock se IA falhar
            Logger::warning('SOP gerado com mock (IA falhou)', ['sop_id' => $sopId, 'erro' => $resultado['erro']]);
            Session::set('sop_gerado', $this->gerarSopMock($sopId, $sopNome));

            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'SOP gerado! (Alguns campos podem precisar de ajuste.)',
                'redirect' => APP_URL . '/sop/revisar?id=' . urlencode($sopId),
                'aviso' => $resultado['erro'],
            ]);
        }
        exit;
    }

    /**
     * Formata resposta da IA para o formato esperado pela view
     */
    private function formatarRespostaIA(array $ia, string $sopId, string $sopNome, array $empresa): array
    {
        return [
            'id' => $sopId,
            'nome' => $sopNome,
            'versao' => $ia['versionamento']['versao'] ?? '1.0',
            'empresa' => $empresa['nome'],
            'setor' => $empresa['setor'],
            'norma' => ApiHelper::getNormasPorSetor($empresa['setor']) ?? 'ISO 9001',
            'objetivo' => $ia['objetivo'] ?? '',
            'escopo_aplica' => $ia['escopo']['aplica_se'] ?? '',
            'escopo_nao_aplica' => $ia['escopo']['nao_aplica'] ?? '',
            'subtopicos' => $ia['subtopicos'] ?? [],
            'responsaveis' => $ia['responsaveis'] ?? [],
            'prerequisitos' => $ia['prerequisitos'] ?? [],
            'ferramentas' => $ia['ferramentas'] ?? [],
            'procedimento_subtopico_1' => $ia['procedimentos'][0]['passos'] ?? [],
            'checklist' => $ia['checklist'] ?? [],
            'evidencias' => $ia['evidencias'] ?? [],
            'relatorios' => $ia['relatorios'] ?? [],
            'kpis' => $ia['kpis'] ?? [],
            'contencao_n1' => $ia['contencao']['n1'] ?? [],
            'contencao_n2' => $ia['contencao']['n2'] ?? [],
            'contencao_n3' => $ia['contencao']['n3'] ?? [],
        ];
    }

    /**
     * Retorna departamento a partir do ID do SOP
     */
    private function getDepartamentoPorId(string $sopId): string
    {
        $map = ['COM' => 'Comercial', 'ONB' => 'Onboarding', 'OPS' => 'Operacional', 'FIN' => 'Financeiro', 'JUR' => 'Jurídico', 'RH' => 'RH'];
        preg_match('/SOP-\w+-(\w+)-/', $sopId, $m);
        return $map[$m[1] ?? ''] ?? 'Geral';
    }

    /**
     * Retorna subtópicos pré-definidos para o SOP
     */
    private function getSubtopicosPorId(string $sopId): string
    {
        $subtopicos = [
            'SOP-TI-ONB-001' => "- Subtópico A: Fornecedor Amistoso (quando coopera)\n- Subtópico B: Fornecedor Não Amistoso (quando dificulta)\n- Subtópico C: Etapas Seguras de Migração (sequência sem downtime)",
            'SOP-TI-OPS-002' => "- Subtópico A: Configuração da Rotina de Backup\n- Subtópico B: Validação e Teste de Restauração\n- Subtópico C: Resposta a Falha de Backup",
            'SOP-TI-OPS-001' => "- Subtópico A: Recebimento e Classificação do Chamado\n- Subtópico B: Escalonamento e Priorização\n- Subtópico C: Resolução e Fechamento com Validação do Cliente",
        ];
        return $subtopicos[$sopId] ?? "- Subtópico A: Preparação e planejamento\n- Subtópico B: Execução e monitoramento\n- Subtópico C: Verificação e encerramento";
    }

    /**
     * Tela de revisão do SOP gerado (13 componentes)
     */
    public function revisar(): void
    {
        Auth::proteger();

        $sopId = htmlspecialchars(trim($_GET['id'] ?? ''));
        $sopGerado = Session::get('sop_gerado');

        if (!$sopGerado) {
            $sopGerado = $this->gerarSopMock($sopId, 'SOP-TI-ONB-001 Recebimento e migração de clientes');
        }

        $dados = [
            'sop' => $sopGerado,
        ];

        require VIEW_PATH . '/sop/revisar.php';
    }

    /**
     * Aprova SOP via AJAX
     */
    public function aprovar(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $sopId = htmlspecialchars(trim($_POST['sop_id'] ?? ''));
        Logger::acao('SOP aprovado', ['sop_id' => $sopId]);

        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'mensagem' => 'SOP aprovado! KPIs enviados ao painel.']);
        exit;
    }

    /**
     * Salva rascunho via AJAX
     */
    public function salvarRascunho(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $sopId = htmlspecialchars(trim($_POST['sop_id'] ?? ''));
        Logger::acao('SOP salvo como rascunho', ['sop_id' => $sopId]);

        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'mensagem' => 'Rascunho salvo!']);
        exit;
    }

    /**
     * Matriz RACI
     */
    public function raci(): void
    {
        Auth::proteger();

        $dados = [
            'cargos' => ['Diretor TI', 'Gerente Ops', 'Analista N2', 'Suporte N1', 'Financeiro', 'Jurídico', 'RH'],
            'sops' => [
                ['id' => 'SOP-TI-COM-001', 'nome' => 'Prospecção e qualificação', 'raci' => ['A', 'R', 'C', 'I', '-', '-', '-']],
                ['id' => 'SOP-TI-ONB-001', 'nome' => 'Recebimento e migração', 'raci' => ['A', 'R', 'R', 'C', 'I', 'C', '-']],
                ['id' => 'SOP-TI-OPS-001', 'nome' => 'Gestão de chamados e SLA', 'raci' => ['I', 'A', 'R', 'R', '-', '-', '-']],
                ['id' => 'SOP-TI-OPS-002', 'nome' => 'Rotina de segurança e backups', 'raci' => ['I', 'A', 'R', 'C', '-', 'I', '-']],
                ['id' => 'SOP-TI-FIN-001', 'nome' => 'Faturamento e cobrança', 'raci' => ['I', 'C', '-', '-', 'R', 'I', '-']],
                ['id' => 'SOP-TI-JUR-001', 'nome' => 'LGPD e tratamento de dados', 'raci' => ['A', 'C', 'I', 'I', '-', 'R', 'C']],
            ],
        ];

        require VIEW_PATH . '/sop/raci.php';
    }

    /**
     * Painel de KPIs agregados
     */
    public function kpis(): void
    {
        Auth::proteger();

        $dados = [
            'kpis' => [
                ['kpi' => 'Tempo de migração sem downtime', 'sop' => 'SOP-TI-ONB-001', 'atual' => '12 min', 'meta_verde' => '0 min', 'meta_amarela' => '1-30 min', 'meta_vermelha' => '>30 min', 'zona' => 'amarela', 'frequencia' => 'Por migração', 'responsavel' => 'Gerente Ops'],
                ['kpi' => 'SLA de chamados atendidos', 'sop' => 'SOP-TI-OPS-001', 'atual' => '94%', 'meta_verde' => '>95%', 'meta_amarela' => '90-95%', 'meta_vermelha' => '<90%', 'zona' => 'amarela', 'frequencia' => 'Semanal', 'responsavel' => 'Analista N2'],
                ['kpi' => 'Backup validado com sucesso', 'sop' => 'SOP-TI-OPS-002', 'atual' => '100%', 'meta_verde' => '100%', 'meta_amarela' => '95-99%', 'meta_vermelha' => '<95%', 'zona' => 'verde', 'frequencia' => 'Diário', 'responsavel' => 'Suporte N1'],
                ['kpi' => 'Tempo de resposta cobrança', 'sop' => 'SOP-TI-FIN-001', 'atual' => '72h', 'meta_verde' => '<48h', 'meta_amarela' => '48-96h', 'meta_vermelha' => '>96h', 'zona' => 'amarela', 'frequencia' => 'Mensal', 'responsavel' => 'Financeiro'],
                ['kpi' => 'Conformidade LGPD', 'sop' => 'SOP-TI-JUR-001', 'atual' => '88%', 'meta_verde' => '>95%', 'meta_amarela' => '80-95%', 'meta_vermelha' => '<80%', 'zona' => 'amarela', 'frequencia' => 'Trimestral', 'responsavel' => 'Jurídico'],
            ],
        ];

        require VIEW_PATH . '/sop/kpis.php';
    }

    /**
     * Registra valor de KPI via AJAX
     */
    public function registrarKpi(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $kpiNome = htmlspecialchars(trim($_POST['kpi_nome'] ?? ''));
        $valor = htmlspecialchars(trim($_POST['valor'] ?? ''));
        Logger::acao('KPI valor registrado', ['kpi' => $kpiNome, 'valor' => $valor]);

        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'mensagem' => 'Valor registrado!']);
        exit;
    }

    // ===== DADOS MOCKADOS =====

    private function getDepartamentosMock(): array
    {
        return [
            [
                'nome' => 'Comercial',
                'icone' => '💼',
                'sops' => [
                    ['id' => 'SOP-TI-COM-001', 'nome' => 'Prospecção e qualificação', 'status' => 'aprovado'],
                    ['id' => 'SOP-TI-COM-002', 'nome' => 'Proposta técnica e comercial', 'status' => 'gerado'],
                    ['id' => 'SOP-TI-COM-003', 'nome' => 'Negociação e fechamento', 'status' => 'nao_gerado'],
                ],
            ],
            [
                'nome' => 'Onboarding',
                'icone' => '🚀',
                'sops' => [
                    ['id' => 'SOP-TI-ONB-001', 'nome' => 'Recebimento e migração de clientes', 'status' => 'em_revisao'],
                    ['id' => 'SOP-TI-ONB-002', 'nome' => 'Configuração inicial de ambiente', 'status' => 'nao_gerado'],
                    ['id' => 'SOP-TI-ONB-003', 'nome' => 'Treinamento e ativação', 'status' => 'nao_gerado'],
                ],
            ],
            [
                'nome' => 'Operacional',
                'icone' => '⚙️',
                'sops' => [
                    ['id' => 'SOP-TI-OPS-001', 'nome' => 'Gestão de chamados e SLA', 'status' => 'aprovado'],
                    ['id' => 'SOP-TI-OPS-002', 'nome' => 'Rotina de segurança e backups', 'status' => 'aprovado'],
                    ['id' => 'SOP-TI-OPS-003', 'nome' => 'Gestão de acessos e senhas', 'status' => 'gerado'],
                    ['id' => 'SOP-TI-OPS-004', 'nome' => 'Monitoramento de infraestrutura', 'status' => 'nao_gerado'],
                ],
            ],
            [
                'nome' => 'Financeiro',
                'icone' => '💰',
                'sops' => [
                    ['id' => 'SOP-TI-FIN-001', 'nome' => 'Faturamento e cobrança', 'status' => 'aprovado'],
                    ['id' => 'SOP-TI-FIN-002', 'nome' => 'Gestão de contratos e renovações', 'status' => 'nao_gerado'],
                ],
            ],
            [
                'nome' => 'Jurídico / Compliance',
                'icone' => '⚖️',
                'sops' => [
                    ['id' => 'SOP-TI-JUR-001', 'nome' => 'LGPD e tratamento de dados', 'status' => 'gerado'],
                    ['id' => 'SOP-TI-JUR-002', 'nome' => 'Gestão de contratos de prestação de serviço', 'status' => 'nao_gerado'],
                ],
            ],
            [
                'nome' => 'RH',
                'icone' => '👥',
                'sops' => [
                    ['id' => 'SOP-TI-RH-001', 'nome' => 'Contratação técnica', 'status' => 'nao_gerado'],
                    ['id' => 'SOP-TI-RH-002', 'nome' => 'Offboarding e reavaliação de acessos', 'status' => 'nao_gerado'],
                ],
            ],
        ];
    }

    private function gerarSopMock(string $sopId, string $sopNome): array
    {
        return [
            'id' => $sopId ?: 'SOP-TI-ONB-001',
            'nome' => $sopNome ?: 'Recebimento e migração de clientes',
            'versao' => '1.0',
            'empresa' => 'Tech Solutions',
            'setor' => 'Tecnologia',
            'norma' => 'ITIL v4 / ISO 27001',
            'objetivo' => 'Este SOP garante que o processo de recepção e migração de novos clientes seja executado de forma segura, minimizando riscos de perda de dados, falhas de continuidade de serviço e conflitos com fornecedores anteriores, independentemente da postura do provedor anterior (amistoso ou não amistoso). Define etapas claras de responsabilidade, comunicação e validação técnica em cada fase da transição.',
            'escopo_aplica' => 'Todos os processos de migração de novos clientes que possuam infraestrutura de TI gerenciada por fornecedor anterior. Aplica-se à equipe de Operações, Suporte N2 e Gerência Técnica.',
            'escopo_nao_aplica' => 'Clientes novos sem infraestrutura prévia (greenfield). Migrações internas entre ambientes já gerenciados pela Tech Solutions.',
            'subtopicos' => [
                ['nome' => 'Fornecedor Amistoso', 'descricao' => 'Quando o fornecedor anterior colabora ativamente com a transição, fornecendo acesso, documentação e suporte durante o processo.'],
                ['nome' => 'Fornecedor Não Amistoso', 'descricao' => 'Quando o fornecedor anterior dificulta, atrasa ou não coopera com a migração, exigindo abordagens alternativas e documentação legal.'],
                ['nome' => 'Etapas Seguras de Migração', 'descricao' => 'Sequência técnica padronizada de transferência de serviços garantindo zero downtime ou downtime mínimo planejado.'],
            ],
            'responsaveis' => [
                ['papel' => 'Executor', 'cargo' => 'Analista de Infraestrutura N2'],
                ['papel' => 'Aprovador', 'cargo' => 'Gerente de Operações'],
                ['papel' => 'Supervisor', 'cargo' => 'Diretor de TI'],
                ['papel' => 'Informado', 'cargo' => 'Cliente (ponto focal designado)'],
                ['papel' => 'Substituto', 'cargo' => 'Analista de Infraestrutura N2 (backup)'],
            ],
            'prerequisitos' => [
                'Contrato assinado com o novo cliente contendo cláusula de migração e SLA definido.',
                'Inventário completo do ambiente atual do cliente obtido (lista de serviços, senhas, acessos, domínios).',
                'Janela de manutenção agendada e comunicada a todos os usuários com 72h de antecedência.',
                'Ambiente de homologação preparado para testes de restauração antes da migração definitiva.',
                'Termo de responsabilidade assinado pelo cliente autorizando o início da migração.',
                'Equipe de plantão escalada para suporte durante as primeiras 48h pós-migração.',
            ],
            'ferramentas' => ['Zabbix', 'GLPI', 'Veeam Backup', 'Azure DevOps', 'Microsoft 365 Admin', 'FortiGate', 'RDP/SSH', 'Slack/Teams'],
            'procedimento_subtopico_1' => [
                ['passo' => 1, 'acao' => 'Agendar reunião de handover com fornecedor anterior e cliente, definindo escopo, cronograma e ponto focal de cada parte.', 'responsavel' => 'Gerente Ops', 'prazo' => 'D-15', 'sistema' => 'Teams/Email', 'validacao' => 'Ata de reunião assinada por todos'],
                ['passo' => 2, 'acao' => 'Solicitar ao fornecedor anterior o inventário completo: servidores, IPs, credenciais, licenças, contratos de terceiros e documentação de rede.', 'responsavel' => 'Analista N2', 'prazo' => 'D-12', 'sistema' => 'Email + GLPI', 'validacao' => 'Inventário recebido e conferido'],
                ['passo' => 3, 'acao' => 'Validar inventário recebido comparando com scan ativo de rede (Nmap/Zabbix discovery) para identificar ativos não declarados.', 'responsavel' => 'Analista N2', 'prazo' => 'D-10', 'sistema' => 'Zabbix', 'validacao' => 'Relatório de divergências zerado ou tratado'],
                ['passo' => 4, 'acao' => 'Realizar backup completo de todos os sistemas em produção no formato nativo, validar integridade via hash SHA-256 e restauração de teste em homologação.', 'responsavel' => 'Analista N2', 'prazo' => 'D-7', 'sistema' => 'Veeam', 'validacao' => 'Log de backup + teste de restore OK'],
                ['passo' => 5, 'acao' => 'Configurar ambiente de destino com infraestrutura espelhada, replicar configurações de firewall, DNS e políticas de grupo.', 'responsavel' => 'Analista N2', 'prazo' => 'D-5', 'sistema' => 'Azure/FortiGate', 'validacao' => 'Checklist de configuração 100%'],
                ['passo' => 6, 'acao' => 'Executar migração piloto com 1-2 serviços não críticos para validar o processo completo antes da migração geral.', 'responsavel' => 'Analista N2', 'prazo' => 'D-3', 'sistema' => 'Ambiente destino', 'validacao' => 'Serviço piloto funcional 24h sem falhas'],
                ['passo' => 7, 'acao' => 'Comunicar a todos os usuários o cronograma final de migração com instruções de contingência caso percebam indisponibilidade.', 'responsavel' => 'Gerente Ops', 'prazo' => 'D-2', 'sistema' => 'Email/Teams', 'validacao' => 'Confirmação de recebimento >90% usuários'],
                ['passo' => 8, 'acao' => 'Executar migração definitiva na janela agendada: transferir DNS, sincronizar dados finais, redirecionar tráfego e validar conectividade em tempo real.', 'responsavel' => 'Analista N2', 'prazo' => 'D-Day', 'sistema' => 'Todos', 'validacao' => 'Todos os serviços respondendo no destino'],
                ['passo' => 9, 'acao' => 'Monitorar ambiente 48h pós-migração com alertas proativos configurados no Zabbix para CPU, memória, disco e latência.', 'responsavel' => 'Suporte N1', 'prazo' => 'D+2', 'sistema' => 'Zabbix', 'validacao' => 'Zero alertas críticos em 48h'],
                ['passo' => 10, 'acao' => 'Formalizar encerramento com fornecedor anterior: solicitar cancelamento de serviços, confirmar devolução de credenciais e assinar termo de encerramento.', 'responsavel' => 'Gerente Ops', 'prazo' => 'D+5', 'sistema' => 'Email/Contrato', 'validacao' => 'Termo assinado + serviços cancelados'],
            ],
            'checklist' => [
                'Contrato de migração assinado pelo cliente',
                'Inventário de ativos validado e sem divergências',
                'Backup completo realizado com hash de integridade',
                'Teste de restauração executado com sucesso em homologação',
                'Ambiente de destino configurado e testado',
                'Migração piloto concluída sem falhas',
                'Comunicação enviada a todos os usuários (>90% confirmação)',
                'DNS redirecionado e propagação validada',
                'Todos os serviços funcionando no novo ambiente',
                'Monitoramento ativo configurado com alertas',
                'Credenciais do fornecedor anterior revogadas',
                'Termo de encerramento com fornecedor anterior assinado',
                'Relatório final de migração entregue ao cliente',
                'Feedback do cliente coletado (pesquisa pós-migração)',
            ],
            'evidencias' => [
                'Laudo de inventário assinado pelo cliente antes da migração',
                'Registro de backup com hash SHA-256 de validação',
                'Log de testes de restauração em homologação',
                'Termo de aceite de migração assinado pós-conclusão',
                'Relatório de monitoramento 48h pós-migração',
                'Termo de encerramento com fornecedor anterior',
                'Prints de todos os serviços funcionando no novo ambiente',
            ],
            'relatorios' => [
                ['oque' => 'Status da migração', 'para_quem' => 'Cliente + Diretor TI', 'frequencia' => 'Diário (durante migração)', 'canal' => 'Email + Teams'],
                ['oque' => 'Incidentes pós-migração', 'para_quem' => 'Gerente Ops + Cliente', 'frequencia' => 'Imediato', 'canal' => 'GLPI + Email'],
                ['oque' => 'Relatório final de migração', 'para_quem' => 'Cliente + Diretoria', 'frequencia' => 'Único (D+5)', 'canal' => 'PDF + Reunião'],
            ],
            'kpis' => [
                ['kpi' => 'Tempo de downtime na migração', 'verde' => '0 minutos', 'amarela' => '1-30 min (parcial)', 'vermelha' => '>30 min ou falha crítica', 'acao_vermelha' => 'Acionar plano de contingência N2 imediatamente e notificar cliente'],
                ['kpi' => 'Taxa de sucesso na migração', 'verde' => '100% serviços OK', 'amarela' => '95-99% (degradação leve)', 'vermelha' => '<95% serviços operacionais', 'acao_vermelha' => 'Rollback parcial + escalar para N2/N3'],
                ['kpi' => 'Satisfação do cliente pós-migração', 'verde' => 'NPS ≥ 9', 'amarela' => 'NPS 7-8', 'vermelha' => 'NPS ≤ 6', 'acao_vermelha' => 'Reunião imediata com cliente + plano de correção'],
            ],
            'contencao_n1' => [
                'situacao' => 'Serviço individual não responde após migração mas demais serviços estão operacionais. Impacto parcial e controlável.',
                'acao' => "1. Identificar o serviço afetado no Zabbix e isolar o problema (rede, aplicação, DNS).\n2. Verificar logs de erro do serviço específico no servidor de destino.\n3. Tentar restart do serviço com monitoramento ativo.\n4. Se não resolver em 15 min, redirecionar tráfego do serviço para backup/origem temporariamente.\n5. Documentar incidente no GLPI e comunicar ao cliente que está sendo tratado.",
                'quem' => 'Analista N2 — prazo máximo de 30 minutos para resolução ou escalação.',
                'escalar' => 'Se não resolver em 30 min OU se mais de 2 serviços forem afetados simultaneamente.',
            ],
            'contencao_n2' => [
                'situacao' => 'Múltiplos serviços falharam OU downtime ultrapassou 30 minutos OU cliente reporta impacto significativo em sua operação.',
                'acao' => "1. Acionar Gerente de Operações e informar status completo da situação.\n2. Avaliar viabilidade de rollback completo vs. correção individual.\n3. Se rollback: executar procedimento de reversão documentado (Veeam restore).\n4. Comunicar ao cliente: status, previsão de resolução e medidas sendo tomadas.\n5. Mobilizar equipe de plantão se fora do horário comercial.",
                'quem' => 'Gerente de Operações + Analista N2 — resposta em até 15 minutos após escalação.',
                'escalar' => 'Se rollback falhar OU downtime ultrapassar 2 horas OU cliente ameaçar ação legal.',
            ],
            'contencao_n3' => [
                'situacao' => 'Perda de dados confirmada OU downtime >2h com impacto financeiro ao cliente OU risco legal/contratual identificado OU fornecedor anterior agiu de má-fé.',
                'acao' => "1. Acionar Diretor de TI e Jurídico imediatamente.\n2. Preservar todas as evidências (logs, emails, backups) para fins legais.\n3. Comunicação formal ao cliente pelo Diretor com plano de recuperação detalhado.\n4. Se má-fé do fornecedor anterior: notificação extrajudicial imediata.\n5. Acionar seguro de responsabilidade civil se aplicável.",
                'quem' => 'Diretor de TI + Jurídico + CEO — resposta imediata.',
                'comunicacao' => 'Cliente informado a cada 30 min. Se impacto público: preparar nota oficial. Se dados pessoais: notificar ANPD conforme LGPD.',
                'documentacao' => 'Relatório de incidente detalhado, timeline de eventos, evidências de backup, comunicações realizadas, ações corretivas e preventivas.',
            ],
        ];
    }
}
