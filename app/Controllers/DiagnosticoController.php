<?php
/**
 * DiagnosticoController — Módulo de Diagnóstico Empresarial
 * O Consultor — Sistema Operacional Empresarial
 *
 * ACESSO: ADMIN_HOLDING, CONSULTOR_INTERNO (cria para cliente), CLIENTE (preenche o próprio)
 */

class DiagnosticoController
{
    /**
     * Listagem de diagnósticos
     */
    public function index(): void
    {
        Auth::proteger();

        // Dados mockados — listagem
        $diagnosticos = [
            [
                'id' => 1,
                'empresa' => 'Tech Solutions',
                'setor' => 'Tecnologia',
                'responsavel' => 'João Consultor',
                'score' => 3,
                'pontuacao' => 72,
                'status' => 'concluido',
                'criado_em' => '2026-06-20',
            ],
            [
                'id' => 2,
                'empresa' => 'Varejo Express',
                'setor' => 'Varejo',
                'responsavel' => 'João Consultor',
                'score' => 2,
                'pontuacao' => 48,
                'status' => 'concluido',
                'criado_em' => '2026-06-18',
            ],
            [
                'id' => 3,
                'empresa' => 'FoodService',
                'setor' => 'Alimentação',
                'responsavel' => 'Maria Cliente',
                'score' => 1,
                'pontuacao' => 31,
                'status' => 'em_andamento',
                'criado_em' => '2026-06-25',
            ],
            [
                'id' => 4,
                'empresa' => 'Construtora ABC',
                'setor' => 'Construção',
                'responsavel' => 'João Consultor',
                'score' => 2,
                'pontuacao' => 55,
                'status' => 'concluido',
                'criado_em' => '2026-06-15',
            ],
            [
                'id' => 5,
                'empresa' => 'Digital Commerce',
                'setor' => 'Tecnologia',
                'responsavel' => 'Maria Cliente',
                'score' => 3,
                'pontuacao' => 68,
                'status' => 'em_andamento',
                'criado_em' => '2026-06-24',
            ],
        ];

        $dados = [
            'diagnosticos' => $diagnosticos,
        ];

        require VIEW_PATH . '/diagnostico/index.php';
    }

    /**
     * Wizard de novo diagnóstico (5 blocos)
     */
    public function novo(): void
    {
        Auth::proteger();

        // Opções para os selects do wizard
        $opcoes = $this->getOpcoesWizard();

        $dados = [
            'opcoes' => $opcoes,
        ];

        require VIEW_PATH . '/diagnostico/novo.php';
    }

    /**
     * Salva o diagnóstico completo via AJAX
     */
    public function salvar(): void
    {
        Auth::proteger();
        Csrf::verificar();

        // Coletar todos os dados dos 5 blocos
        $dadosForm = [
            // Bloco 1 — Identificação
            'empresa_nome' => htmlspecialchars(trim($_POST['empresa_nome'] ?? '')),
            'setor' => htmlspecialchars(trim($_POST['setor'] ?? '')),
            'descricao' => htmlspecialchars(trim(substr($_POST['descricao'] ?? '', 0, 300))),
            'tempo_existencia' => htmlspecialchars(trim($_POST['tempo_existencia'] ?? '')),
            'estrutura_societaria' => htmlspecialchars(trim($_POST['estrutura_societaria'] ?? '')),
            'unidades_filiais' => (int) ($_POST['unidades_filiais'] ?? 1),
            'lingua_principal' => htmlspecialchars(trim($_POST['lingua_principal'] ?? 'Portugues')),

            // Bloco 2 — Estrutura Operacional
            'colaboradores_internos' => (int) ($_POST['colaboradores_internos'] ?? 0),
            'colaboradores_externos' => (int) ($_POST['colaboradores_externos'] ?? 0),
            'departamentos' => $_POST['departamentos'] ?? [],
            'clientes_ativos' => (int) ($_POST['clientes_ativos'] ?? 0),
            'produtos_servicos' => htmlspecialchars(trim($_POST['produtos_servicos'] ?? '')),
            'faturamento_mensal' => htmlspecialchars(trim($_POST['faturamento_mensal'] ?? '')),
            'ticket_medio' => htmlspecialchars(trim($_POST['ticket_medio'] ?? '')),
            'sites_referencia' => htmlspecialchars(trim($_POST['sites_referencia'] ?? '')),

            // Bloco 3 — Operação Atual
            'processo_entrega' => htmlspecialchars(trim($_POST['processo_entrega'] ?? '')),
            'ferramentas_softwares' => htmlspecialchars(trim($_POST['ferramentas_softwares'] ?? '')),
            'fornecedores_criticos' => htmlspecialchars(trim($_POST['fornecedores_criticos'] ?? '')),
            'dependencia_pessoa' => htmlspecialchars(trim($_POST['dependencia_pessoa'] ?? '')),
            'integracoes' => htmlspecialchars(trim($_POST['integracoes'] ?? '')),
            'processos_documentados' => (int) ($_POST['processos_documentados'] ?? 0),
            'ferramentas_gestao' => $_POST['ferramentas_gestao'] ?? [],

            // Bloco 4 — Problemas e Riscos
            'problemas_operacionais' => htmlspecialchars(trim($_POST['problemas_operacionais'] ?? '')),
            'riscos_identificados' => htmlspecialchars(trim($_POST['riscos_identificados'] ?? '')),
            'incidentes_tipo' => htmlspecialchars(trim($_POST['incidentes_tipo'] ?? '')),
            'incidentes_descricao' => htmlspecialchars(trim($_POST['incidentes_descricao'] ?? '')),
            'areas_vulneraveis' => $_POST['areas_vulneraveis'] ?? [],
            'cliente_concentrado' => htmlspecialchars(trim($_POST['cliente_concentrado'] ?? 'nao')),
            'fornecedor_insubstituivel' => htmlspecialchars(trim($_POST['fornecedor_insubstituivel'] ?? 'nao')),
            'processos_sem_backup' => htmlspecialchars(trim($_POST['processos_sem_backup'] ?? 'nao')),

            // Bloco 5 — Contexto Estratégico
            'pontos_fortes' => htmlspecialchars(trim($_POST['pontos_fortes'] ?? '')),
            'pontos_melhoria' => htmlspecialchars(trim($_POST['pontos_melhoria'] ?? '')),
            'objetivo_12_meses' => htmlspecialchars(trim($_POST['objetivo_12_meses'] ?? '')),
            'maturidade_percebida' => (int) ($_POST['maturidade_percebida'] ?? 3),
            'planejamento_documentado' => htmlspecialchars(trim($_POST['planejamento_documentado'] ?? 'nao')),
            'frequencia_reunioes' => htmlspecialchars(trim($_POST['frequencia_reunioes'] ?? '')),
            'meta_faturamento' => htmlspecialchars(trim($_POST['meta_faturamento'] ?? 'nao')),
        ];

        // Calcular Score de Maturidade (1-4)
        $score = $this->calcularScore($dadosForm);

        // Gerar resultado
        $resultado = $this->gerarResultado($dadosForm, $score);

        Logger::acao('Diagnóstico empresarial salvo', [
            'empresa' => $dadosForm['empresa_nome'],
            'score' => $score,
        ]);

        // Salvar na sessão para exibir resultado
        Session::set('diagnostico_resultado', $resultado);
        Session::set('diagnostico_dados', $dadosForm);

        header('Content-Type: application/json');
        echo json_encode([
            'sucesso' => true,
            'score' => $score,
            'mensagem' => 'Diagnóstico concluído com sucesso!',
            'redirect' => APP_URL . '/diagnostico/resultado',
        ]);
        exit;
    }

    /**
     * Exibe o resultado do diagnóstico
     */
    public function resultado(): void
    {
        Auth::proteger();

        $resultado = Session::get('diagnostico_resultado');
        $dadosForm = Session::get('diagnostico_dados');

        // Se não houver resultado na sessão, usar mock
        if (!$resultado) {
            $resultado = $this->getResultadoMock();
            $dadosForm = ['empresa_nome' => 'Empresa Exemplo'];
        }

        $dados = [
            'resultado' => $resultado,
            'dadosForm' => $dadosForm,
        ];

        require VIEW_PATH . '/diagnostico/resultado.php';
    }

    /**
     * Calcula o score de maturidade (1-4)
     */
    private function calcularScore(array $dados): int
    {
        $pontos = 0;
        $maxPontos = 20;

        // Processos documentados (0-100%)
        $pontos += match(true) {
            $dados['processos_documentados'] >= 75 => 4,
            $dados['processos_documentados'] >= 50 => 3,
            $dados['processos_documentados'] >= 25 => 2,
            default => 1,
        };

        // Departamentos estruturados
        $numDepts = is_array($dados['departamentos']) ? count($dados['departamentos']) : 0;
        $pontos += match(true) {
            $numDepts >= 6 => 4,
            $numDepts >= 4 => 3,
            $numDepts >= 2 => 2,
            default => 1,
        };

        // Planejamento
        $pontos += $dados['planejamento_documentado'] === 'sim' ? 4 : 1;

        // Maturidade percebida
        $pontos += match(true) {
            $dados['maturidade_percebida'] >= 4 => 4,
            $dados['maturidade_percebida'] >= 3 => 3,
            $dados['maturidade_percebida'] >= 2 => 2,
            default => 1,
        };

        // Riscos
        $temRiscos = ($dados['processos_sem_backup'] === 'sim' || $dados['fornecedor_insubstituivel'] === 'sim');
        $pontos += $temRiscos ? 1 : 4;

        // Calcular nível final (1-4)
        $percentual = ($pontos / $maxPontos) * 100;
        return match(true) {
            $percentual >= 80 => 4,
            $percentual >= 60 => 3,
            $percentual >= 40 => 2,
            default => 1,
        };
    }

    /**
     * Gera o resultado completo do diagnóstico
     */
    private function gerarResultado(array $dados, int $score): array
    {
        $niveis = [
            1 => ['label' => 'Inicial', 'cor' => '#CC2222', 'descricao' => 'A empresa está no estágio inicial de organização. Processos dependem de pessoas e não há padronização.'],
            2 => ['label' => 'Desenvolvimento', 'cor' => '#f59e0b', 'descricao' => 'A empresa está desenvolvendo processos. Há alguma documentação mas falta consistência.'],
            3 => ['label' => 'Crescimento', 'cor' => '#1a7a1a', 'descricao' => 'A empresa possui processos definidos e está em fase de crescimento estruturado.'],
            4 => ['label' => 'Excelência', 'cor' => '#1E3A5F', 'descricao' => 'A empresa opera com excelência. Processos são otimizados e mensurados continuamente.'],
        ];

        // Resumo por área
        $areas = [
            ['area' => 'Estratégia', 'status' => $score >= 3 ? 'adequado' : 'atenção', 'comentario' => $dados['planejamento_documentado'] === 'sim' ? 'Planejamento documentado e objetivos claros.' : 'Necessita formalizar planejamento estratégico.'],
            ['area' => 'Operações', 'status' => $dados['processos_documentados'] >= 50 ? 'adequado' : 'crítico', 'comentario' => $dados['processos_documentados'] . '% dos processos documentados. ' . ($dados['processos_documentados'] < 50 ? 'Urgente: mapear processos críticos.' : 'Manter evolução na documentação.')],
            ['area' => 'Financeiro', 'status' => $dados['meta_faturamento'] === 'sim' ? 'adequado' : 'atenção', 'comentario' => $dados['meta_faturamento'] === 'sim' ? 'Metas financeiras definidas.' : 'Definir metas financeiras claras e acompanháveis.'],
            ['area' => 'Pessoas', 'status' => $dados['processos_sem_backup'] === 'nao' ? 'adequado' : 'crítico', 'comentario' => $dados['processos_sem_backup'] === 'sim' ? 'RISCO: processos sem backup de conhecimento.' : 'Conhecimento distribuído adequadamente.'],
            ['area' => 'Riscos', 'status' => ($dados['fornecedor_insubstituivel'] === 'sim' || $dados['cliente_concentrado'] === 'sim') ? 'crítico' : 'adequado', 'comentario' => $dados['fornecedor_insubstituivel'] === 'sim' ? 'Dependência de fornecedor insubstituível identificada.' : 'Riscos de dependência controlados.'],
        ];

        // Mapa de riscos
        $riscos = [];
        if ($dados['processos_sem_backup'] === 'sim') {
            $riscos[] = ['tipo' => 'Operacional', 'descricao' => 'Processos sem backup de conhecimento', 'criticidade' => 'alta', 'acao' => 'Documentar SOPs e treinar equipe backup'];
        }
        if ($dados['fornecedor_insubstituivel'] === 'sim') {
            $riscos[] = ['tipo' => 'Fornecimento', 'descricao' => 'Fornecedor crítico insubstituível', 'criticidade' => 'alta', 'acao' => 'Mapear fornecedores alternativos'];
        }
        if ($dados['cliente_concentrado'] === 'sim') {
            $riscos[] = ['tipo' => 'Comercial', 'descricao' => 'Cliente com mais de 30% do faturamento', 'criticidade' => 'media', 'acao' => 'Diversificar carteira de clientes'];
        }
        if ($dados['processos_documentados'] < 30) {
            $riscos[] = ['tipo' => 'Operacional', 'descricao' => 'Baixo nível de documentação de processos', 'criticidade' => 'media', 'acao' => 'Criar programa de documentação com priorização'];
        }
        if (!empty($dados['incidentes_tipo'])) {
            $riscos[] = ['tipo' => htmlspecialchars($dados['incidentes_tipo']), 'descricao' => htmlspecialchars($dados['incidentes_descricao'] ?: 'Incidente reportado'), 'criticidade' => 'alta', 'acao' => 'Investigar causa raiz e criar plano de prevenção'];
        }

        return [
            'score' => $score,
            'nivel' => $niveis[$score],
            'pontuacao_percentual' => round(($score / 4) * 100),
            'areas' => $areas,
            'riscos' => $riscos,
            'empresa' => $dados['empresa_nome'],
        ];
    }

    /**
     * Resultado mock para visualização
     */
    private function getResultadoMock(): array
    {
        return [
            'score' => 2,
            'nivel' => ['label' => 'Desenvolvimento', 'cor' => '#f59e0b', 'descricao' => 'A empresa está desenvolvendo processos. Há alguma documentação mas falta consistência.'],
            'pontuacao_percentual' => 50,
            'areas' => [
                ['area' => 'Estratégia', 'status' => 'atenção', 'comentario' => 'Necessita formalizar planejamento estratégico.'],
                ['area' => 'Operações', 'status' => 'crítico', 'comentario' => '25% dos processos documentados. Urgente: mapear processos críticos.'],
                ['area' => 'Financeiro', 'status' => 'adequado', 'comentario' => 'Metas financeiras definidas.'],
                ['area' => 'Pessoas', 'status' => 'crítico', 'comentario' => 'RISCO: processos sem backup de conhecimento.'],
                ['area' => 'Riscos', 'status' => 'atenção', 'comentario' => 'Dependência de fornecedor insubstituível identificada.'],
            ],
            'riscos' => [
                ['tipo' => 'Operacional', 'descricao' => 'Processos sem backup de conhecimento', 'criticidade' => 'alta', 'acao' => 'Documentar SOPs e treinar equipe backup'],
                ['tipo' => 'Fornecimento', 'descricao' => 'Fornecedor crítico insubstituível', 'criticidade' => 'alta', 'acao' => 'Mapear fornecedores alternativos'],
                ['tipo' => 'Comercial', 'descricao' => 'Cliente com mais de 30% do faturamento', 'criticidade' => 'media', 'acao' => 'Diversificar carteira de clientes'],
            ],
            'empresa' => 'Empresa Exemplo',
        ];
    }

    /**
     * Retorna as opções para selects/multi-selects do wizard
     */
    private function getOpcoesWizard(): array
    {
        return [
            'setores' => [
                'Tecnologia', 'Varejo', 'Serviços', 'Saúde', 'Construção',
                'Educação', 'Financeiro', 'Indústria', 'Logística', 'Costura/Moda',
                'Alimentação', 'Jurídico', 'Imobiliário', 'Outro',
            ],
            'linguas' => ['Português', 'Inglês', 'Espanhol'],
            'departamentos' => [
                'Comercial', 'Marketing', 'Financeiro', 'RH', 'TI', 'Jurídico',
                'Operações', 'Atendimento', 'Projetos', 'Produção', 'Logística', 'Diretoria',
            ],
            'faturamento' => [
                'Até R$ 50 mil', 'R$ 50 mil - R$ 100 mil', 'R$ 100 mil - R$ 300 mil',
                'R$ 300 mil - R$ 500 mil', 'R$ 500 mil - R$ 1 milhão', 'Acima de R$ 1 milhão',
            ],
            'ferramentas_gestao' => [
                'ERP', 'CRM', 'Trello/Asana/Monday', 'Slack/Teams', 'Google Workspace',
                'Notion', 'Power BI', 'Excel/Planilhas', 'Sistema próprio', 'Nenhum',
            ],
            'areas_vulneraveis' => [
                'Comercial', 'Financeiro', 'Operações', 'TI/Sistemas', 'Jurídico',
                'Logística', 'RH/Pessoas', 'Marketing', 'Atendimento', 'Produção',
            ],
            'tempo_existencia' => [
                'Menos de 1 ano', '1 a 3 anos', '3 a 5 anos', '5 a 10 anos', 'Mais de 10 anos',
            ],
            'estrutura_societaria' => [
                'MEI', 'ME', 'EPP', 'Ltda', 'S/A', 'Eireli', 'SLU',
            ],
            'frequencia_reunioes' => [
                'Diária', 'Semanal', 'Quinzenal', 'Mensal', 'Esporádica', 'Não há',
            ],
        ];
    }
}
