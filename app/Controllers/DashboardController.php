<?php
/**
 * DashboardController — Painel principal por perfil
 * O Consultor — Sistema Operacional Empresarial
 */

class DashboardController
{
    /**
     * Exibe o dashboard conforme perfil do usuário
     */
    public function index(): void
    {
        Auth::proteger();

        $perfil = Auth::perfil();
        $usuario = Auth::usuario();

        // Saudação por horário
        $hora = (int) date('H');
        if ($hora < 12) {
            $saudacao = 'Bom dia';
        } elseif ($hora < 18) {
            $saudacao = 'Boa tarde';
        } else {
            $saudacao = 'Boa noite';
        }

        // Formatar data em português
        $diasSemana = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
        $meses = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        $dataFormatada = $diasSemana[(int)date('w')] . ', ' . date('d') . ' de ' . $meses[(int)date('n')] . ' de ' . date('Y');

        $dados = [
            'usuario' => $usuario,
            'saudacao' => $saudacao,
            'data_atual' => $dataFormatada,
        ];

        match ($perfil) {
            'ADMIN_HOLDING' => $this->dadosAdmin($dados),
            'CONSULTOR_INTERNO' => $this->dadosConsultor($dados),
            default => $this->dadosCliente($dados),
        };

        // Renderizar view correspondente
        $viewFile = match ($perfil) {
            'ADMIN_HOLDING' => '/dashboard/admin.php',
            'CONSULTOR_INTERNO' => '/dashboard/consultor.php',
            default => '/dashboard/cliente.php',
        };

        require VIEW_PATH . $viewFile;
    }

    /**
     * Dados mockados para ADMIN_HOLDING
     */
    private function dadosAdmin(array &$dados): void
    {
        $dados['kpis'] = [
            [
                'titulo' => 'Clientes Ativos',
                'valor' => '47',
                'variacao' => '+12%',
                'direcao' => 'up',
                'icone' => 'users',
                'cor' => 'blue',
            ],
            [
                'titulo' => 'Diagnósticos no Mês',
                'valor' => '23',
                'variacao' => '+8%',
                'direcao' => 'up',
                'icone' => 'clipboard',
                'cor' => 'green',
            ],
            [
                'titulo' => 'Planos Ativos',
                'valor' => '34',
                'variacao' => '+15%',
                'direcao' => 'up',
                'icone' => 'target',
                'cor' => 'purple',
            ],
            [
                'titulo' => 'SOPs Gerados',
                'valor' => '312',
                'variacao' => '+23%',
                'direcao' => 'up',
                'icone' => 'book',
                'cor' => 'indigo',
            ],
            [
                'titulo' => 'MRR',
                'valor' => 'R$ 85.4k',
                'variacao' => '+5,2%',
                'direcao' => 'up',
                'icone' => 'currency',
                'cor' => 'orange',
            ],
            [
                'titulo' => 'Churn',
                'valor' => '2,1%',
                'variacao' => '-0,3%',
                'direcao' => 'down',
                'icone' => 'alert',
                'cor' => 'red',
            ],
        ];

        // Clientes por status (gráfico rosca)
        $dados['clientes_status'] = [
            'labels' => ['Ativo', 'Em Onboarding', 'Pausado', 'Churned'],
            'valores' => [35, 7, 3, 2],
            'cores' => ['#1a7a1a', '#E07B00', '#f59e0b', '#CC2222'],
        ];

        // Alertas críticos
        $dados['alertas_criticos'] = [
            ['cliente' => 'Tech Solutions', 'tipo' => 'Churn Risk', 'nivel' => 'alto', 'tempo' => 'Há 2h'],
            ['cliente' => 'Varejo Express', 'tipo' => 'Plano Atrasado', 'nivel' => 'medio', 'tempo' => 'Há 5h'],
            ['cliente' => 'FoodService', 'tipo' => 'Sem Login 30d', 'nivel' => 'alto', 'tempo' => 'Há 1d'],
            ['cliente' => 'Construtora ABC', 'tipo' => 'KPI Crítico', 'nivel' => 'medio', 'tempo' => 'Há 2d'],
        ];

        // Parceiros por categoria (gráfico barras)
        $dados['parceiros_categorias'] = [
            'labels' => ['Contabilidade', 'Jurídico', 'Marketing', 'Tecnologia', 'RH'],
            'valores' => [8, 5, 12, 6, 4],
        ];

        // Últimas atividades
        $dados['atividades'] = [
            ['acao' => 'Diagnóstico concluído', 'detalhe' => 'Empresa XYZ — Pontuação: 72%', 'tempo' => 'Há 25min', 'cor' => 'green'],
            ['acao' => 'Novo cliente cadastrado', 'detalhe' => 'Digital Commerce Ltda', 'tempo' => 'Há 1h', 'cor' => 'blue'],
            ['acao' => 'SOP gerado via IA', 'detalhe' => 'Processo de Vendas — Tech Solutions', 'tempo' => 'Há 2h', 'cor' => 'purple'],
            ['acao' => 'Alerta de churn', 'detalhe' => 'FoodService — Sem acesso há 30 dias', 'tempo' => 'Há 3h', 'cor' => 'red'],
            ['acao' => 'Plano atualizado', 'detalhe' => 'Construtora ABC — 4 ações concluídas', 'tempo' => 'Há 5h', 'cor' => 'orange'],
        ];

        // Mapa de Maturidade
        $dados['mapa_maturidade'] = [
            ['empresa' => 'Tech Solutions', 'nivel' => 4, 'label' => 'Excelência', 'cor' => '#1E3A5F'],
            ['empresa' => 'Varejo Express', 'nivel' => 3, 'label' => 'Crescimento', 'cor' => '#1a7a1a'],
            ['empresa' => 'FoodService', 'nivel' => 2, 'label' => 'Desenvolvimento', 'cor' => '#f59e0b'],
            ['empresa' => 'Construtora ABC', 'nivel' => 1, 'label' => 'Inicial', 'cor' => '#CC2222'],
            ['empresa' => 'Digital Commerce', 'nivel' => 3, 'label' => 'Crescimento', 'cor' => '#1a7a1a'],
            ['empresa' => 'Saúde+', 'nivel' => 2, 'label' => 'Desenvolvimento', 'cor' => '#f59e0b'],
            ['empresa' => 'EduTech', 'nivel' => 4, 'label' => 'Excelência', 'cor' => '#1E3A5F'],
            ['empresa' => 'AutoPeças BR', 'nivel' => 1, 'label' => 'Inicial', 'cor' => '#CC2222'],
        ];

        // Dados para abas (Diagnósticos, Planos, Conteúdo)
        $dados['tab_diagnosticos'] = [
            ['empresa' => 'Digital Commerce', 'pontuacao' => 82, 'status' => 'concluido', 'data' => '2026-06-25'],
            ['empresa' => 'Varejo Express', 'pontuacao' => 65, 'status' => 'concluido', 'data' => '2026-06-24'],
            ['empresa' => 'FoodService', 'pontuacao' => 41, 'status' => 'em_andamento', 'data' => '2026-06-23'],
            ['empresa' => 'Construtora ABC', 'pontuacao' => 58, 'status' => 'concluido', 'data' => '2026-06-20'],
        ];

        $dados['tab_planos'] = [
            ['empresa' => 'Tech Solutions', 'progresso' => 85, 'acoes_total' => 12, 'acoes_feitas' => 10],
            ['empresa' => 'Digital Commerce', 'progresso' => 60, 'acoes_total' => 8, 'acoes_feitas' => 5],
            ['empresa' => 'Varejo Express', 'progresso' => 30, 'acoes_total' => 10, 'acoes_feitas' => 3],
            ['empresa' => 'FoodService', 'progresso' => 15, 'acoes_total' => 6, 'acoes_feitas' => 1],
        ];

        $dados['tab_conteudo'] = [
            ['titulo' => 'Como reduzir churn em consultoria', 'tipo' => 'artigo', 'views' => 234, 'data' => '2026-06-25'],
            ['titulo' => 'Template: OKRs para PMEs', 'tipo' => 'template', 'views' => 189, 'data' => '2026-06-22'],
            ['titulo' => 'Webinar: Gestão Financeira', 'tipo' => 'video', 'views' => 412, 'data' => '2026-06-20'],
        ];
    }

    /**
     * Dados mockados para CONSULTOR_INTERNO
     */
    private function dadosConsultor(array &$dados): void
    {
        $dados['kpis'] = [
            [
                'titulo' => 'Clientes Ativos',
                'valor' => '12',
                'variacao' => '+2',
                'direcao' => 'up',
                'icone' => 'users',
                'cor' => 'blue',
            ],
            [
                'titulo' => 'Diagnósticos em Aberto',
                'valor' => '5',
                'variacao' => '-1',
                'direcao' => 'down',
                'icone' => 'clipboard',
                'cor' => 'orange',
            ],
            [
                'titulo' => 'Planos Pendentes',
                'valor' => '8',
                'variacao' => '+3',
                'direcao' => 'up',
                'icone' => 'target',
                'cor' => 'purple',
            ],
            [
                'titulo' => 'Alertas',
                'valor' => '3',
                'variacao' => '+1',
                'direcao' => 'up',
                'icone' => 'alert',
                'cor' => 'red',
            ],
        ];

        // Agenda da semana
        $dados['agenda'] = [
            ['dia' => 'Seg 23', 'hora' => '09:00', 'titulo' => 'Reunião — Tech Solutions', 'tipo' => 'reuniao'],
            ['dia' => 'Seg 23', 'hora' => '14:00', 'titulo' => 'Diagnóstico — FoodService', 'tipo' => 'diagnostico'],
            ['dia' => 'Ter 24', 'hora' => '10:00', 'titulo' => 'Revisão Plano — Varejo Express', 'tipo' => 'plano'],
            ['dia' => 'Qua 25', 'hora' => '09:30', 'titulo' => 'Onboarding — Digital Commerce', 'tipo' => 'onboarding'],
            ['dia' => 'Qui 26', 'hora' => '11:00', 'titulo' => 'Follow-up — Construtora ABC', 'tipo' => 'reuniao'],
            ['dia' => 'Sex 27', 'hora' => '15:00', 'titulo' => 'Apresentação SOPs — EduTech', 'tipo' => 'apresentacao'],
        ];

        // Clientes recentes com interações
        $dados['clientes_recentes'] = [
            ['nome' => 'Tech Solutions', 'ultima_interacao' => 'Diagnóstico concluído', 'dias' => 1, 'maturidade' => 4],
            ['nome' => 'Varejo Express', 'ultima_interacao' => 'Plano atualizado', 'dias' => 3, 'maturidade' => 3],
            ['nome' => 'FoodService', 'ultima_interacao' => 'SOP gerado', 'dias' => 5, 'maturidade' => 2],
            ['nome' => 'Construtora ABC', 'ultima_interacao' => 'Reunião realizada', 'dias' => 7, 'maturidade' => 1],
            ['nome' => 'Digital Commerce', 'ultima_interacao' => 'Cadastro realizado', 'dias' => 2, 'maturidade' => 3],
        ];

        // Alertas
        $dados['alertas'] = [
            ['cliente' => 'FoodService', 'mensagem' => 'Sem acesso há 30 dias — Risco de churn', 'nivel' => 'alto'],
            ['cliente' => 'Construtora ABC', 'mensagem' => 'Plano de ação com 70% de atraso', 'nivel' => 'medio'],
            ['cliente' => 'Varejo Express', 'mensagem' => 'KPI Financeiro abaixo da meta', 'nivel' => 'medio'],
        ];
    }

    /**
     * Dados mockados para CLIENTE
     */
    private function dadosCliente(array &$dados): void
    {
        $usuario = $dados['usuario'];
        
        // Verificar se onboarding foi concluído
        $usuarioCompleto = User::buscarPorId($usuario['id']);
        $onboardingConcluido = $usuarioCompleto['onboarding_concluido'] ?? false;
        
        // Calcular progresso da jornada
        $jornada = [
            ['chave' => 'onboarding', 'label' => 'Onboarding completo', 'completo' => $onboardingConcluido],
            ['chave' => 'diagnostico', 'label' => 'Diagnóstico realizado', 'completo' => false], // TODO: verificar no banco
            ['chave' => 'plano', 'label' => 'Plano de ação criado', 'completo' => false], // TODO: verificar no banco  
            ['chave' => 'sop', 'label' => 'Primeiro SOP aprovado', 'completo' => false], // TODO: verificar no banco
            ['chave' => 'academy', 'label' => 'Academy vinculada', 'completo' => !empty($usuarioCompleto['email_academy'])],
        ];
        
        $totalEtapas = count($jornada);
        $etapasConcluidas = array_reduce($jornada, fn($acc, $item) => $acc + ($item['completo'] ? 1 : 0), 0);
        $percentualConclusao = $totalEtapas > 0 ? round(($etapasConcluidas / $totalEtapas) * 100) : 0;
        
        $dados['onboarding_concluido'] = $onboardingConcluido;
        $dados['jornada'] = $jornada;
        $dados['percentual_conclusao'] = $percentualConclusao;
        
        $dados['kpis'] = [
            [
                'titulo' => 'Maturidade',
                'valor' => 'Nível 3',
                'variacao' => '+1 nível',
                'direcao' => 'up',
                'icone' => 'chart',
                'cor' => 'green',
            ],
            [
                'titulo' => 'SOPs Criados',
                'valor' => '12',
                'variacao' => '+3',
                'direcao' => 'up',
                'icone' => 'book',
                'cor' => 'purple',
            ],
            [
                'titulo' => 'KPIs Monitorados',
                'valor' => '8',
                'variacao' => '+2',
                'direcao' => 'up',
                'icone' => 'target',
                'cor' => 'blue',
            ],
            [
                'titulo' => 'Próxima Reunião',
                'valor' => '2 dias',
                'variacao' => 'Qui, 10h',
                'direcao' => 'neutral',
                'icone' => 'calendar',
                'cor' => 'orange',
            ],
        ];

        // Plano de Ação do cliente
        $dados['plano_acao'] = [
            ['titulo' => 'Implementar CRM', 'responsavel' => 'João Silva', 'prazo' => '2026-07-10', 'status' => 'em_andamento'],
            ['titulo' => 'Documentar processo de vendas', 'responsavel' => 'Maria Souza', 'prazo' => '2026-07-05', 'status' => 'concluido'],
            ['titulo' => 'Criar dashboard financeiro', 'responsavel' => 'Carlos Lima', 'prazo' => '2026-07-15', 'status' => 'pendente'],
            ['titulo' => 'Definir metas trimestrais', 'responsavel' => 'Ana Costa', 'prazo' => '2026-07-01', 'status' => 'em_andamento'],
            ['titulo' => 'Treinar equipe comercial', 'responsavel' => 'Pedro Rocha', 'prazo' => '2026-07-20', 'status' => 'pendente'],
        ];

        // Alertas KPI
        $dados['alertas_kpi'] = [
            ['kpi' => 'Taxa de Conversão', 'meta' => '15%', 'atual' => '9%', 'acao_sugerida' => 'Revisar funil de vendas e qualificar leads'],
            ['kpi' => 'Ticket Médio', 'meta' => 'R$ 2.500', 'atual' => 'R$ 1.800', 'acao_sugerida' => 'Implementar upsell no processo de vendas'],
            ['kpi' => 'NPS', 'meta' => '70', 'atual' => '55', 'acao_sugerida' => 'Mapear detratores e criar plano de ação'],
        ];

        // Conteúdo recomendado
        $dados['conteudo_recomendado'] = [
            ['titulo' => 'Como implementar CRM em PMEs', 'tipo' => 'artigo', 'duracao' => '7 min'],
            ['titulo' => 'Template: Dashboard Financeiro', 'tipo' => 'template', 'duracao' => ''],
            ['titulo' => 'Webinar: Metas SMART para equipes', 'tipo' => 'video', 'duracao' => '22 min'],
        ];
    }
}
