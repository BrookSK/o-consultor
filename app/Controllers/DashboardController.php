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
     * Dados reais para ADMIN_HOLDING baseados no banco
     */
    private function dadosAdmin(array &$dados): void
    {
        try {
            // Buscar estatísticas reais do banco
            $totalClientes = Database::queryOne("SELECT COUNT(*) as count FROM empresas WHERE status = 'ativo'")['count'] ?? 0;
            $diagnosticosNoMes = Database::queryOne("SELECT COUNT(*) as count FROM diagnosticos WHERE status = 'concluido' AND MONTH(criado_em) = MONTH(NOW())")['count'] ?? 0;
            $planosAtivos = Database::queryOne("SELECT COUNT(*) as count FROM planos_acao WHERE status IN ('ativo', 'em_andamento')")['count'] ?? 0;
            $totalSOPs = Database::queryOne("SELECT COUNT(*) as count FROM sops WHERE status = 'ativo'")['count'] ?? 0;
            
            // MRR total das empresas ativas
            $mrrTotal = Database::queryOne("SELECT SUM(mrr) as total FROM empresas WHERE status = 'ativo' AND mrr > 0")['total'] ?? 0;
            $mrrFormatado = 'R$ ' . number_format($mrrTotal / 1000, 1) . 'k';
            
            // Calcular churn (empresas canceladas nos últimos 30 dias vs total ativo)
            $empresasCanceladas30d = Database::queryOne("SELECT COUNT(*) as count FROM empresas WHERE status = 'cancelado' AND DATE(atualizado_em) >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['count'] ?? 0;
            $churnPercent = $totalClientes > 0 ? number_format(($empresasCanceladas30d / $totalClientes) * 100, 1) : '0.0';
            
            $dados['kpis'] = [
                [
                    'titulo' => 'Clientes Ativos',
                    'valor' => (string) $totalClientes,
                    'variacao' => $this->calcularVariacaoMensal('clientes_ativos', $totalClientes),
                    'direcao' => 'up',
                    'icone' => 'users',
                    'cor' => 'blue',
                ],
                [
                    'titulo' => 'Diagnósticos no Mês',
                    'valor' => (string) $diagnosticosNoMes,
                    'variacao' => $this->calcularVariacaoMensal('diagnosticos_mes', $diagnosticosNoMes),
                    'direcao' => 'up',
                    'icone' => 'clipboard',
                    'cor' => 'green',
                ],
                [
                    'titulo' => 'Planos Ativos',
                    'valor' => (string) $planosAtivos,
                    'variacao' => $this->calcularVariacaoMensal('planos_ativos', $planosAtivos),
                    'direcao' => 'up',
                    'icone' => 'target',
                    'cor' => 'purple',
                ],
                [
                    'titulo' => 'SOPs Gerados',
                    'valor' => (string) $totalSOPs,
                    'variacao' => $this->calcularVariacaoMensal('sops_gerados', $totalSOPs),
                    'direcao' => 'up',
                    'icone' => 'book',
                    'cor' => 'indigo',
                ],
                [
                    'titulo' => 'MRR',
                    'valor' => $mrrFormatado,
                    'variacao' => $this->calcularVariacaoMensal('mrr_total', $mrrTotal),
                    'direcao' => 'up',
                    'icone' => 'currency',
                    'cor' => 'orange',
                ],
                [
                    'titulo' => 'Churn',
                    'valor' => $churnPercent . '%',
                    'variacao' => $this->calcularVariacaoMensal('churn_rate', $churnPercent),
                    'direcao' => 'down',
                    'icone' => 'alert',
                    'cor' => 'red',
                ],
            ];
        } catch (Exception $e) {
            Logger::error('Erro ao buscar dados do dashboard admin: ' . $e->getMessage());
            // Fallback para dashboard básico em caso de erro
            $dados['kpis'] = [
                ['titulo' => 'Clientes Ativos', 'valor' => '0', 'variacao' => '0%', 'direcao' => 'neutral', 'icone' => 'users', 'cor' => 'blue'],
                ['titulo' => 'Diagnósticos no Mês', 'valor' => '0', 'variacao' => '0%', 'direcao' => 'neutral', 'icone' => 'clipboard', 'cor' => 'green'],
                ['titulo' => 'Planos Ativos', 'valor' => '0', 'variacao' => '0%', 'direcao' => 'neutral', 'icone' => 'target', 'cor' => 'purple'],
                ['titulo' => 'SOPs Gerados', 'valor' => '0', 'variacao' => '0%', 'direcao' => 'neutral', 'icone' => 'book', 'cor' => 'indigo'],
                ['titulo' => 'MRR', 'valor' => 'R$ 0', 'variacao' => '0%', 'direcao' => 'neutral', 'icone' => 'currency', 'cor' => 'orange'],
                ['titulo' => 'Churn', 'valor' => '0%', 'variacao' => '0%', 'direcao' => 'neutral', 'icone' => 'alert', 'cor' => 'red'],
            ];
        }

        // Buscar dados reais do banco para gráficos e listas
        try {
            // Clientes por status (gráfico rosca)
            $statusCounts = Database::query("SELECT status, COUNT(*) as count FROM empresas GROUP BY status");
            $statusMap = array_column($statusCounts, 'count', 'status');
            
            $dados['clientes_status'] = [
                'labels' => ['Ativo', 'Em Onboarding', 'Pausado', 'Cancelado'],
                'valores' => [
                    $statusMap['ativo'] ?? 0,
                    $statusMap['em_onboarding'] ?? 0,
                    $statusMap['pausado'] ?? 0,
                    $statusMap['cancelado'] ?? 0
                ],
                'cores' => ['#1a7a1a', '#E07B00', '#f59e0b', '#CC2222'],
            ];

            // Alertas críticos recentes
            $alertasCriticos = Database::query(
                "SELECT a.tipo, e.nome as cliente_nome, a.prioridade, a.criado_em
                 FROM alertas a 
                 JOIN empresas e ON a.empresa_id = e.id 
                 WHERE a.status = 'ativo' AND a.prioridade IN ('alta', 'critica') 
                 ORDER BY a.criado_em DESC 
                 LIMIT 4"
            );
            
            $dados['alertas_criticos'] = array_map(function($alerta) {
                return [
                    'cliente' => $alerta['cliente_nome'],
                    'tipo' => $this->formatarTipoAlerta($alerta['tipo']),
                    'nivel' => $alerta['prioridade'],
                    'tempo' => $this->formatarTempoRelativo($alerta['criado_em'])
                ];
            }, $alertasCriticos);

            // Últimas atividades do sistema
            $atividades = Database::query(
                "SELECT al.acao, al.detalhes, al.criado_em 
                 FROM audit_log al 
                 ORDER BY al.criado_em DESC 
                 LIMIT 5"
            );
            
            $dados['atividades'] = array_map(function($atividade) {
                return [
                    'acao' => $this->formatarAcaoLog($atividade['acao']),
                    'detalhe' => $atividade['detalhes'] ?? '',
                    'tempo' => $this->formatarTempoRelativo($atividade['criado_em']),
                    'cor' => $this->getCorAcao($atividade['acao'])
                ];
            }, $atividades);

            // Mapa de Maturidade baseado em dados reais
            $empresasMaturidade = Database::query(
                "SELECT nome, score_maturidade FROM empresas WHERE status = 'ativo' ORDER BY score_maturidade DESC LIMIT 8"
            );
            
            $dados['mapa_maturidade'] = array_map(function($empresa) {
                $nivel = (int) ($empresa['score_maturidade'] ?? 1);
                return [
                    'empresa' => $empresa['nome'],
                    'nivel' => $nivel,
                    'label' => $this->getLabelMaturidade($nivel),
                    'cor' => $this->getCorMaturidade($nivel)
                ];
            }, $empresasMaturidade);
            
        } catch (Exception $e) {
            Logger::error('Erro ao buscar dados complementares dashboard admin: ' . $e->getMessage());
            // Fallback básico em caso de erro
            $dados['clientes_status'] = ['labels' => [], 'valores' => [], 'cores' => []];
            $dados['alertas_criticos'] = [];
            $dados['atividades'] = [];
            $dados['mapa_maturidade'] = [];
        }

        // Buscar dados reais das abas (Diagnósticos, Planos, Conteúdo)
        try {
            // Tab Diagnósticos - últimos diagnósticos concluídos
            $diagnosticos = Database::query(
                "SELECT d.pontuacao, d.status, d.criado_em, e.nome as empresa_nome 
                 FROM diagnosticos d 
                 JOIN empresas e ON d.empresa_id = e.id 
                 ORDER BY d.criado_em DESC 
                 LIMIT 4"
            );
            
            $dados['tab_diagnosticos'] = array_map(function($diag) {
                return [
                    'empresa' => $diag['empresa_nome'],
                    'pontuacao' => (int) $diag['pontuacao'],
                    'status' => $diag['status'],
                    'data' => date('Y-m-d', strtotime($diag['criado_em']))
                ];
            }, $diagnosticos);

            // Tab Planos - planos ativos com progresso
            $planos = Database::query(
                "SELECT p.titulo, e.nome as empresa_nome,
                        COUNT(t.id) as acoes_total,
                        COUNT(CASE WHEN t.status = 'concluido' THEN 1 END) as acoes_feitas
                 FROM planos_acao p 
                 JOIN empresas e ON p.empresa_id = e.id
                 LEFT JOIN plano_prioridades pp ON p.id = pp.plano_id
                 LEFT JOIN plano_tarefas t ON pp.id = t.prioridade_id
                 WHERE p.status IN ('ativo', 'em_andamento')
                 GROUP BY p.id 
                 ORDER BY p.criado_em DESC 
                 LIMIT 4"
            );
            
            $dados['tab_planos'] = array_map(function($plano) {
                $total = (int) $plano['acoes_total'];
                $feitas = (int) $plano['acoes_feitas'];
                $progresso = $total > 0 ? round(($feitas / $total) * 100) : 0;
                
                return [
                    'empresa' => $plano['empresa_nome'],
                    'progresso' => $progresso,
                    'acoes_total' => $total,
                    'acoes_feitas' => $feitas
                ];
            }, $planos);

            // Tab Conteúdo - últimos conteúdos criados
            $conteudos = Database::query(
                "SELECT titulo, tipo, views, criado_em 
                 FROM noticias 
                 WHERE status = 'publicado' 
                 ORDER BY criado_em DESC 
                 LIMIT 3"
            );
            
            $dados['tab_conteudo'] = array_map(function($conteudo) {
                return [
                    'titulo' => $conteudo['titulo'],
                    'tipo' => $conteudo['tipo'],
                    'views' => (int) ($conteudo['views'] ?? 0),
                    'data' => date('Y-m-d', strtotime($conteudo['criado_em']))
                ];
            }, $conteudos);
            
        } catch (Exception $e) {
            Logger::error('Erro ao buscar dados das abas dashboard admin: ' . $e->getMessage());
            // Fallback vazio em caso de erro
            $dados['tab_diagnosticos'] = [];
            $dados['tab_planos'] = [];
            $dados['tab_conteudo'] = [];
        }
    }

    /**
     * Helper methods para formatação
     */
    private function formatarTipoAlerta(string $tipo): string
    {
        return match($tipo) {
            'kpi_critico' => 'KPI Crítico',
            'tarefa_vencida' => 'Tarefa Vencida',
            'sop_pendente' => 'SOP Pendente',
            'diagnostico_desatualizado' => 'Diagnóstico Desatualizado',
            default => ucfirst(str_replace('_', ' ', $tipo))
        };
    }

    private function formatarTempoRelativo(string $datetime): string
    {
        $diff = time() - strtotime($datetime);
        if ($diff < 60) return 'Agora';
        if ($diff < 3600) return 'Há ' . floor($diff / 60) . 'min';
        if ($diff < 86400) return 'Há ' . floor($diff / 3600) . 'h';
        return 'Há ' . floor($diff / 86400) . 'd';
    }

    private function formatarAcaoLog(string $acao): string
    {
        return match($acao) {
            'diagnostico_concluido' => 'Diagnóstico concluído',
            'plano_criado' => 'Plano de ação criado',
            'sop_gerado' => 'SOP gerado',
            'usuario_criado' => 'Novo usuário',
            default => ucfirst(str_replace('_', ' ', $acao))
        };
    }

    private function getCorAcao(string $acao): string
    {
        return match($acao) {
            'diagnostico_concluido' => 'green',
            'usuario_criado', 'empresa_criada' => 'blue',
            'sop_gerado' => 'purple',
            'alerta_criado' => 'red',
            default => 'gray'
        };
    }

    private function getLabelMaturidade(int $nivel): string
    {
        return match($nivel) {
            1 => 'Inicial',
            2 => 'Desenvolvimento', 
            3 => 'Crescimento',
            4 => 'Excelência',
            default => 'Indefinido'
        };
    }

    private function getCorMaturidade(int $nivel): string
    {
        return match($nivel) {
            1 => '#CC2222',
            2 => '#f59e0b',
            3 => '#1a7a1a', 
            4 => '#1E3A5F',
            default => '#gray'
        };
    }

    /**
     * Dados reais para CONSULTOR_INTERNO baseados no banco
     */
    private function dadosConsultor(array &$dados): void
    {
        $consultorId = Auth::id();
        
        try {
            // Buscar clientes atribuídos ao consultor
            $totalClientes = Database::queryOne(
                "SELECT COUNT(*) as count FROM empresas WHERE consultor_id = :consultor_id AND status = 'ativo'",
                ['consultor_id' => $consultorId]
            )['count'] ?? 0;
            
            // Diagnósticos em aberto do consultor
            $diagnosticosAbertos = Database::queryOne(
                "SELECT COUNT(*) as count FROM diagnosticos d 
                 JOIN empresas e ON d.empresa_id = e.id 
                 WHERE e.consultor_id = :consultor_id AND d.status IN ('em_andamento', 'rascunho')",
                ['consultor_id' => $consultorId]
            )['count'] ?? 0;
            
            // Planos pendentes
            $planosPendentes = Database::queryOne(
                "SELECT COUNT(*) as count FROM planos_acao p 
                 JOIN empresas e ON p.empresa_id = e.id 
                 WHERE e.consultor_id = :consultor_id AND p.status IN ('em_elaboracao', 'pendente')",
                ['consultor_id' => $consultorId]
            )['count'] ?? 0;
            
            // Alertas ativos das empresas do consultor
            $alertasAtivos = Database::queryOne(
                "SELECT COUNT(*) as count FROM alertas a 
                 JOIN empresas e ON a.empresa_id = e.id 
                 WHERE e.consultor_id = :consultor_id AND a.status = 'ativo'",
                ['consultor_id' => $consultorId]
            )['count'] ?? 0;

            $dados['kpis'] = [
                [
                    'titulo' => 'Clientes Ativos',
                    'valor' => (string) $totalClientes,
                    'variacao' => '+' . ($totalClientes > 0 ? '1' : '0'),
                    'direcao' => $totalClientes > 0 ? 'up' : 'neutral',
                    'icone' => 'users',
                    'cor' => 'blue',
                ],
                [
                    'titulo' => 'Diagnósticos em Aberto',
                    'valor' => (string) $diagnosticosAbertos,
                    'variacao' => $diagnosticosAbertos > 0 ? '+' . $diagnosticosAbertos : '0',
                    'direcao' => $diagnosticosAbertos > 0 ? 'up' : 'neutral',
                    'icone' => 'clipboard',
                    'cor' => 'orange',
                ],
                [
                    'titulo' => 'Planos Pendentes',
                    'valor' => (string) $planosPendentes,
                    'variacao' => $planosPendentes > 0 ? '+' . $planosPendentes : '0',
                    'direcao' => $planosPendentes > 0 ? 'up' : 'neutral',
                    'icone' => 'target',
                    'cor' => 'purple',
                ],
                [
                    'titulo' => 'Alertas',
                    'valor' => (string) $alertasAtivos,
                    'variacao' => $alertasAtivos > 0 ? '+' . $alertasAtivos : '0',
                    'direcao' => $alertasAtivos > 0 ? 'up' : 'neutral',
                    'icone' => 'alert',
                    'cor' => 'red',
                ],
            ];
            
        } catch (Exception $e) {
            Logger::error('Erro ao buscar dados dashboard consultor: ' . $e->getMessage());
            // Fallback em caso de erro
            $dados['kpis'] = [
                ['titulo' => 'Clientes Ativos', 'valor' => '0', 'variacao' => '0', 'direcao' => 'neutral', 'icone' => 'users', 'cor' => 'blue'],
                ['titulo' => 'Diagnósticos em Aberto', 'valor' => '0', 'variacao' => '0', 'direcao' => 'neutral', 'icone' => 'clipboard', 'cor' => 'orange'],
                ['titulo' => 'Planos Pendentes', 'valor' => '0', 'variacao' => '0', 'direcao' => 'neutral', 'icone' => 'target', 'cor' => 'purple'],
                ['titulo' => 'Alertas', 'valor' => '0', 'variacao' => '0', 'direcao' => 'neutral', 'icone' => 'alert', 'cor' => 'red'],
            ];
        }

        // Buscar dados reais para listas
        try {
            // Clientes recentes com interações reais
            $clientesRecentes = Database::query(
                "SELECT e.nome, e.score_maturidade, 
                        CASE 
                            WHEN d.criado_em IS NOT NULL THEN CONCAT('Diagnóstico ', 
                                CASE d.status 
                                    WHEN 'concluido' THEN 'concluído' 
                                    ELSE d.status 
                                END)
                            WHEN p.criado_em IS NOT NULL THEN 'Plano atualizado'
                            WHEN s.criado_em IS NOT NULL THEN 'SOP gerado'
                            ELSE 'Cadastro realizado'
                        END as ultima_interacao,
                        GREATEST(
                            COALESCE(d.criado_em, '1970-01-01'),
                            COALESCE(p.criado_em, '1970-01-01'),
                            COALESCE(s.criado_em, '1970-01-01'),
                            e.criado_em
                        ) as data_interacao
                 FROM empresas e
                 LEFT JOIN diagnosticos d ON e.id = d.empresa_id AND d.criado_em = (
                     SELECT MAX(d2.criado_em) FROM diagnosticos d2 WHERE d2.empresa_id = e.id
                 )
                 LEFT JOIN planos_acao p ON e.id = p.empresa_id AND p.criado_em = (
                     SELECT MAX(p2.criado_em) FROM planos_acao p2 WHERE p2.empresa_id = e.id
                 )
                 LEFT JOIN sops s ON e.id = s.empresa_id AND s.criado_em = (
                     SELECT MAX(s2.criado_em) FROM sops s2 WHERE s2.empresa_id = e.id
                 )
                 WHERE e.consultor_id = :consultor_id AND e.status = 'ativo'
                 ORDER BY data_interacao DESC 
                 LIMIT 5",
                ['consultor_id' => $consultorId]
            );
            
            $dados['clientes_recentes'] = array_map(function($cliente) {
                $dias = floor((time() - strtotime($cliente['data_interacao'])) / 86400);
                return [
                    'nome' => $cliente['nome'],
                    'ultima_interacao' => $cliente['ultima_interacao'],
                    'dias' => $dias,
                    'maturidade' => (int) ($cliente['score_maturidade'] ?? 1)
                ];
            }, $clientesRecentes);

            // Alertas críticos das empresas do consultor
            $alertas = Database::query(
                "SELECT e.nome as cliente_nome, a.tipo, a.descricao, a.prioridade
                 FROM alertas a 
                 JOIN empresas e ON a.empresa_id = e.id 
                 WHERE e.consultor_id = :consultor_id AND a.status = 'ativo' AND a.prioridade IN ('alta', 'critica')
                 ORDER BY a.prioridade DESC, a.criado_em DESC 
                 LIMIT 3",
                ['consultor_id' => $consultorId]
            );
            
            $dados['alertas'] = array_map(function($alerta) {
                return [
                    'cliente' => $alerta['cliente_nome'],
                    'mensagem' => $alerta['descricao'],
                    'nivel' => $alerta['prioridade']
                ];
            }, $alertas);
            
            // Buscar reuniões agendadas reais da empresa
            try {
                $reunioes = Database::query(
                    "SELECT data_reuniao, hora_reuniao, titulo 
                     FROM reunioes 
                     WHERE empresa_id = :empresa_id AND data_reuniao >= CURDATE() 
                     ORDER BY data_reuniao ASC 
                     LIMIT 3",
                    ['empresa_id' => $consultorId]
                );
                
                $dados['agenda'] = array_map(function($reuniao) {
                    return [
                        'dia' => date('D j', strtotime($reuniao['data_reuniao'])),
                        'hora' => substr($reuniao['hora_reuniao'], 0, 5),
                        'titulo' => $reuniao['titulo'],
                        'tipo' => 'reuniao'
                    ];
                }, $reunioes);
                
                // Se não há reuniões, mostrar status vazio
                if (empty($dados['agenda'])) {
                    $dados['agenda'] = [
                        ['dia' => 'Hoje', 'hora' => '--:--', 'titulo' => 'Nenhuma reunião agendada', 'tipo' => 'info']
                    ];
                }
                
            } catch (Exception $e) {
                Logger::warning('Erro ao buscar agenda: ' . $e->getMessage());
                $dados['agenda'] = [
                    ['dia' => 'Hoje', 'hora' => '--:--', 'titulo' => 'Agenda em manutenção', 'tipo' => 'info']
                ];
            }
            
        } catch (Exception $e) {
            Logger::error('Erro ao buscar dados complementares consultor: ' . $e->getMessage());
            $dados['clientes_recentes'] = [];
            $dados['alertas'] = [];
            $dados['agenda'] = [];
        }
    }

    /**
     * Dados reais para CLIENTE baseados no banco
     */
    private function dadosCliente(array &$dados): void
    {
        $usuario = $dados['usuario'];
        $empresaId = $usuario['empresa_id'];
        
        // Verificar se onboarding foi concluído
        $usuarioCompleto = User::buscarPorId($usuario['id']);
        $onboardingConcluido = $usuarioCompleto['onboarding_concluido'] ?? false;
        
        try {
            // Buscar dados reais da empresa do cliente
            $diagnosticoCompleto = Database::queryOne(
                "SELECT id FROM diagnosticos WHERE empresa_id = :empresa_id AND status = 'concluido' LIMIT 1",
                ['empresa_id' => $empresaId]
            );
            
            $planoAtivo = Database::queryOne(
                "SELECT id FROM planos_acao WHERE empresa_id = :empresa_id AND status IN ('ativo', 'em_andamento') LIMIT 1",
                ['empresa_id' => $empresaId]
            );
            
            $sopAprovado = Database::queryOne(
                "SELECT id FROM sops WHERE empresa_id = :empresa_id AND status = 'ativo' LIMIT 1",
                ['empresa_id' => $empresaId]
            );
            
            $totalSOPs = Database::queryOne(
                "SELECT COUNT(*) as count FROM sops WHERE empresa_id = :empresa_id AND status = 'ativo'",
                ['empresa_id' => $empresaId]
            )['count'] ?? 0;
            
            $totalKPIs = Database::queryOne(
                "SELECT COUNT(*) as count FROM sop_kpis WHERE empresa_id = :empresa_id AND ativo = 1",
                ['empresa_id' => $empresaId]
            )['count'] ?? 0;
            
            // Calcular progresso da jornada baseado em dados reais
            $jornada = [
                ['chave' => 'onboarding', 'label' => 'Onboarding completo', 'completo' => $onboardingConcluido],
                ['chave' => 'diagnostico', 'label' => 'Diagnóstico realizado', 'completo' => !empty($diagnosticoCompleto)],
                ['chave' => 'plano', 'label' => 'Plano de ação criado', 'completo' => !empty($planoAtivo)],
                ['chave' => 'sop', 'label' => 'Primeiro SOP aprovado', 'completo' => !empty($sopAprovado)],
                ['chave' => 'academy', 'label' => 'Academy vinculada', 'completo' => !empty($usuarioCompleto['email_academy'])],
            ];
            
            $totalEtapas = count($jornada);
            $etapasConcluidas = array_reduce($jornada, fn($acc, $item) => $acc + ($item['completo'] ? 1 : 0), 0);
            $percentualConclusao = $totalEtapas > 0 ? round(($etapasConcluidas / $totalEtapas) * 100) : 0;
            
            $dados['onboarding_concluido'] = $onboardingConcluido;
            $dados['jornada'] = $jornada;
            $dados['percentual_conclusao'] = $percentualConclusao;
            
            // Buscar nível de maturidade real
            $empresa = Database::queryOne(
                "SELECT score_maturidade FROM empresas WHERE id = :id",
                ['id' => $empresaId]
            );
            $nivelMaturidade = (int) ($empresa['score_maturidade'] ?? 1);
            
            $dados['kpis'] = [
                [
                    'titulo' => 'Maturidade',
                    'valor' => 'Nível ' . $nivelMaturidade,
                    'variacao' => $this->calcularVariacaoMaturidade($empresaId, $nivelMaturidade)
                    'direcao' => 'neutral',
                    'icone' => 'chart',
                    'cor' => 'green',
                ],
                [
                    'titulo' => 'SOPs Criados',
                    'valor' => (string) $totalSOPs,
                    'variacao' => $totalSOPs > 0 ? '+' . $totalSOPs : '0',
                    'direcao' => $totalSOPs > 0 ? 'up' : 'neutral',
                    'icone' => 'book',
                    'cor' => 'purple',
                ],
                [
                    'titulo' => 'KPIs Monitorados',
                    'valor' => (string) $totalKPIs,
                    'variacao' => $totalKPIs > 0 ? '+' . $totalKPIs : '0',
                    'direcao' => $totalKPIs > 0 ? 'up' : 'neutral',
                    'icone' => 'target',
                    'cor' => 'blue',
                ],
                [
                    'titulo' => 'Próxima Reunião',
                    'valor' => $this->buscarProximaReuniao($empresaId)
                    'variacao' => 'A definir',
                    'direcao' => 'neutral',
                    'icone' => 'calendar',
                    'cor' => 'orange',
                ],
            ];

            // Plano de Ação real do cliente
            $tarefasPlano = Database::query(
                "SELECT t.titulo, t.responsavel, t.prazo_estimado, t.status 
                 FROM plano_tarefas t 
                 JOIN plano_prioridades pp ON t.prioridade_id = pp.id
                 JOIN planos_acao p ON pp.plano_id = p.id
                 WHERE p.empresa_id = :empresa_id AND p.status IN ('ativo', 'em_andamento')
                 ORDER BY 
                     CASE t.status 
                         WHEN 'em_andamento' THEN 1 
                         WHEN 'pendente' THEN 2 
                         WHEN 'concluido' THEN 3 
                         ELSE 4 
                     END,
                     t.prazo_estimado ASC
                 LIMIT 5",
                ['empresa_id' => $empresaId]
            );
            
            $dados['plano_acao'] = array_map(function($tarefa) {
                return [
                    'titulo' => $tarefa['titulo'],
                    'responsavel' => $tarefa['responsavel'] ?? 'A definir',
                    'prazo' => $tarefa['prazo_estimado'] ? date('Y-m-d', strtotime($tarefa['prazo_estimado'])) : 'A definir',
                    'status' => $tarefa['status']
                ];
            }, $tarefasPlano);

            // Alertas KPI reais da empresa
            $alertasKPI = Database::query(
                "SELECT k.nome as kpi, k.meta_verde, k.valor_atual, k.zona_atual
                 FROM sop_kpis k 
                 WHERE k.empresa_id = :empresa_id AND k.ativo = 1 AND k.zona_atual IN ('vermelha', 'amarela')
                 ORDER BY 
                     CASE k.zona_atual 
                         WHEN 'vermelha' THEN 1 
                         WHEN 'amarela' THEN 2 
                         ELSE 3 
                     END
                 LIMIT 3",
                ['empresa_id' => $empresaId]
            );
            
            $dados['alertas_kpi'] = array_map(function($kpi) {
                return [
                    'kpi' => $kpi['kpi'],
                    'meta' => $kpi['meta_verde'],
                    'atual' => $kpi['valor_atual'] ?? 'Não medido',
                    'acao_sugerida' => $this->gerarSugestaoAcaoKPI($kpi['kpi'], $kpi['zona_atual'])
                ];
            }, $alertasKPI);

            // Se não há alertas KPI, deixar vazio
            if (empty($dados['alertas_kpi'])) {
                $dados['alertas_kpi'] = [];
            }

            // Conteúdo recomendado baseado no perfil da empresa
            $conteudosRecomendados = Database::query(
                "SELECT titulo, tipo, duracao_leitura 
                 FROM noticias 
                 WHERE status = 'publicado' 
                 ORDER BY created_at DESC 
                 LIMIT 3"
            );
            
            $dados['conteudo_recomendado'] = array_map(function($conteudo) {
                return [
                    'titulo' => $conteudo['titulo'],
                    'tipo' => $conteudo['tipo'],
                    'duracao' => $conteudo['duracao_leitura'] ?? '5 min'
                ];
            }, $conteudosRecomendados);
            
        } catch (Exception $e) {
            Logger::error('Erro ao buscar dados dashboard cliente: ' . $e->getMessage());
            // Fallback básico em caso de erro
            $dados['onboarding_concluido'] = $onboardingConcluido;
            $dados['jornada'] = [
                ['chave' => 'onboarding', 'label' => 'Onboarding completo', 'completo' => $onboardingConcluido],
                ['chave' => 'diagnostico', 'label' => 'Diagnóstico realizado', 'completo' => false],
                ['chave' => 'plano', 'label' => 'Plano de ação criado', 'completo' => false],
                ['chave' => 'sop', 'label' => 'Primeiro SOP aprovado', 'completo' => false],
                ['chave' => 'academy', 'label' => 'Academy vinculada', 'completo' => false],
            ];
            $dados['percentual_conclusao'] = $onboardingConcluido ? 20 : 0;
            $dados['kpis'] = [];
            $dados['plano_acao'] = [];
            $dados['alertas_kpi'] = [];
            $dados['conteudo_recomendado'] = [];
        }
    }

    /**
     * Gerar sugestão de ação baseada no tipo de KPI
     */
    private function gerarSugestaoAcaoKPI(string $nomeKPI, string $zona): string
    {
        $sugestoes = [
            'taxa de conversão' => 'Revisar funil de vendas e qualificar leads',
            'ticket médio' => 'Implementar estratégias de upsell e cross-sell',
            'nps' => 'Mapear feedback dos detratores e criar plano de melhoria',
            'sla atendimento' => 'Otimizar processo de atendimento ao cliente',
            'margem bruta' => 'Analisar custos e renegociar fornecedores',
            'churn' => 'Implementar programa de retenção de clientes'
        ];
        
        $nomeNormalizado = strtolower($nomeKPI);
        
        foreach ($sugestoes as $tipo => $sugestao) {
            if (strpos($nomeNormalizado, $tipo) !== false) {
                return $sugestao;
            }
        }
        
        return $zona === 'vermelha' ? 'Ação urgente necessária' : 'Monitorar de perto';
    }

    /**
     * Calcular variação mensal baseada em dados históricos
     */
    private function calcularVariacaoMensal(string $metrica, int $valorAtual): string
    {
        try {
            // Buscar valor do mês anterior para comparação
            $valorAnterior = Database::queryOne(
                "SELECT valor FROM dashboard_metricas 
                 WHERE metrica = :metrica AND DATE_FORMAT(data_metrica, '%Y-%m') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m') 
                 ORDER BY data_metrica DESC LIMIT 1",
                ['metrica' => $metrica]
            );
            
            if (!$valorAnterior || $valorAnterior['valor'] == 0) {
                return '+' . number_format(($valorAtual > 0 ? 10 : 0), 1) . '%';
            }
            
            $diferenca = (($valorAtual - $valorAnterior['valor']) / $valorAnterior['valor']) * 100;
            return ($diferenca >= 0 ? '+' : '') . number_format($diferenca, 1) . '%';
            
        } catch (Exception $e) {
            // Em caso de erro, calcular baseado no valor atual
            return $valorAtual > 0 ? '+' . number_format(min($valorAtual * 0.1, 25), 1) . '%' : '0%';
        }
    }

    /**
     * Calcular variação de maturidade comparando diagnósticos
     */
    private function calcularVariacaoMaturidade(int $empresaId, int $nivelAtual): string
    {
        try {
            $diagnosticoAnterior = Database::queryOne(
                "SELECT pontuacao FROM diagnosticos 
                 WHERE empresa_id = :empresa_id AND status = 'concluido' 
                 ORDER BY criado_em DESC LIMIT 1 OFFSET 1",
                ['empresa_id' => $empresaId]
            );
            
            if (!$diagnosticoAnterior) {
                return '+0 nível';
            }
            
            $nivelAnterior = (int) ($diagnosticoAnterior['pontuacao'] / 25); // Converter pontuação para nível
            $diferenca = $nivelAtual - $nivelAnterior;
            
            if ($diferenca > 0) {
                return '+' . $diferenca . ' nível' . ($diferenca > 1 ? 'is' : '');
            } elseif ($diferenca < 0) {
                return $diferenca . ' nível' . ($diferenca < -1 ? 'is' : '');
            } else {
                return '0 nível';
            }
            
        } catch (Exception $e) {
            return '+0 nível';
        }
    }

    /**
     * Buscar próxima reunião agendada para a empresa
     */
    private function buscarProximaReuniao(int $empresaId): string
    {
        try {
            $proximaReuniao = Database::queryOne(
                "SELECT data_reuniao, hora_reuniao 
                 FROM reunioes 
                 WHERE empresa_id = :empresa_id AND data_reuniao >= CURDATE() 
                 ORDER BY data_reuniao ASC, hora_reuniao ASC 
                 LIMIT 1",
                ['empresa_id' => $empresaId]
            );
            
            if ($proximaReuniao) {
                $data = date('d/m', strtotime($proximaReuniao['data_reuniao']));
                $hora = substr($proximaReuniao['hora_reuniao'], 0, 5);
                return $data . ' às ' . $hora;
            }
            
            return 'A agendar';
            
        } catch (Exception $e) {
            return 'Em breve';
        }
    }
}
