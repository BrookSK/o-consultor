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
     * Tela principal — Cards por departamento (F-05 Implementation)
     */
    public function index(): void
    {
        Auth::proteger();
        
        $dados = [];

        // Verificar se veio de um diagnóstico específico
        $diagnosticoId = (int) (isset($_GET['diagnostico_id']) ? $_GET['diagnostico_id'] : 0);
        
        if ($diagnosticoId) {
            // Buscar diagnóstico para obter a empresa
            $diagnostico = Diagnostico::buscarPorId($diagnosticoId);
            
            if (!$diagnostico) {
                Flash::set('erro', 'Diagnóstico não encontrado.');
                header('Location: ' . APP_URL . '/diagnostico');
                exit;
            }
            
            // Verificar permissão
            if (Auth::perfil() !== 'ADMIN_HOLDING' && $diagnostico['usuario_id'] != Auth::id()) {
                Flash::set('erro', 'Sem permissão para acessar este diagnóstico.');
                header('Location: ' . APP_URL . '/diagnostico');
                exit;
            }
            
            // CORREÇÃO: Usar empresa do diagnóstico - FORÇAR SELEÇÃO AUTOMÁTICA
            $empresaId = $diagnostico['empresa_id'];
            $dados['empresa_atual'] = $this->carregarDadosEmpresa($empresaId, $diagnosticoId);
            $dados['diagnostico_especifico'] = $diagnostico;  // Adicionar diagnóstico completo
            $dados['diagnostico_id'] = $diagnosticoId;        // ID disponível na view
            
            $empresa = Empresa::buscarPorId($empresaId);
            if ($empresa) {
                // Carregar departamentos baseados NO DIAGNÓSTICO REAL, não template genérico
                $dados['departamentos'] = $this->getDepartamentosComBaseNoDiagnostico($empresa, $diagnostico, $empresaId);
                
                Logger::info('Empresa PRE-SELECIONADA via diagnóstico', [
                    'diagnostico_id' => $diagnosticoId,
                    'empresa_id' => $empresaId,
                    'empresa_nome' => $empresa['nome'],
                    'total_departamentos' => count($dados['departamentos'])
                ]);
            } else {
                Flash::set('erro', 'Empresa do diagnóstico não encontrada.');
                header('Location: ' . APP_URL . '/diagnostico');
                exit;
            }
            
            Logger::info('Acessando SOPs via diagnóstico - MODO ESPECÍFICO', [
                'diagnostico_id' => $diagnosticoId,
                'empresa_id' => $empresaId,
                'empresa_nome' => isset($empresa['nome']) ? $empresa['nome'] : 'N/A',
                'departamentos_diagnostico' => $this->extrairDepartamentos($diagnostico)
            ]);
            
        } else {
            // Fluxo original para quando não vem de diagnóstico
            
            // Para ADMIN_HOLDING, mostrar lista de empresas
            if (Auth::perfil() === 'ADMIN_HOLDING') {
                // Buscar todas as empresas
                $empresas = Database::query(
                    "SELECT e.*, 
                            COUNT(s.id) as total_sops,
                            COUNT(CASE WHEN s.status = 'aprovado' THEN 1 END) as aprovados
                     FROM empresas e
                     LEFT JOIN sops s ON e.id = s.empresa_id
                     GROUP BY e.id
                     ORDER BY e.nome"
                );
                
                $dados['empresas_disponiveis'] = $empresas;
                
                // Se há uma empresa na sessão, carregá-la
                if (isset($_SESSION['empresa_selecionada'])) {
                    $empresaId = $_SESSION['empresa_selecionada'];
                    $empresa = Empresa::buscarPorId($empresaId);
                    if ($empresa) {
                        $dados['empresa_atual'] = $this->carregarDadosEmpresa($empresaId);
                        $dados['departamentos'] = $this->getDepartamentosPorSetor(isset($empresa['segmento']) ? $empresa['segmento'] : 'Tecnologia', $empresaId);
                    }
                }
            } else {
                // Para outros perfis, usar empresa do usuário
                $empresaId = Auth::garantirEmpresa();
                $dados['empresa_atual'] = $this->carregarDadosEmpresa($empresaId);
                $empresa = Empresa::buscarPorId($empresaId);
                if ($empresa) {
                    $dados['departamentos'] = $this->getDepartamentosPorSetor(isset($empresa['segmento']) ? $empresa['segmento'] : 'Tecnologia', $empresaId);
                }
            }
        }

        require VIEW_PATH . '/sop/index.php';
    }
    
    /**
     * Carrega departamentos baseados NO DIAGNÓSTICO REAL da empresa, não templates genéricos
     */
    private function getDepartamentosComBaseNoDiagnostico(array $empresa, array $diagnostico, int $empresaId): array
    {
        Logger::info('Carregando departamentos com base no diagnóstico específico', [
            'empresa_id' => $empresaId,
            'diagnostico_id' => $diagnostico['id'],
            'empresa_nome' => $empresa['nome']
        ]);
        
        // 1. EXTRAIR DEPARTAMENTOS REAIS DO DIAGNÓSTICO COM MÉTODO APRIMORADO
        $departamentosReais = $this->extrairDepartamentosDetalhados($diagnostico);
        
        // Se não encontrou departamentos específicos, usar extração básica melhorada
        if (empty($departamentosReais)) {
            $departamentosTexto = $this->extrairDepartamentos($diagnostico);
            $departamentosArray = $this->parsearDepartamentos($departamentosTexto);
            
            foreach ($departamentosArray as $dept) {
                $departamentosReais[$dept] = [
                    'nome' => $dept,
                    'colaboradores' => ['Quantidade não especificada'],
                    'funcoes_principais' => $this->extrairFuncoesPorDepartamento(json_decode($diagnostico['respostas'], true) ?? [], $dept),
                    'ferramentas_usadas' => $this->extrairFerramentasPorDepartamento(json_decode($diagnostico['respostas'], true) ?? [], $dept),
                    'problemas_identificados' => $this->extrairProblemasPorDepartamento(json_decode($diagnostico['respostas'], true) ?? [], $dept),
                    'objetivos_especificos' => $this->extrairObjetivosPorDepartamento(json_decode($diagnostico['respostas'], true) ?? [], $dept),
                    'processos_principais' => $this->identificarProcessosPrincipais($dept, json_decode($diagnostico['respostas'], true) ?? []),
                    'nivel_maturidade' => $this->calcularMaturidadePorDepartamento(json_decode($diagnostico['respostas'], true) ?? [], $dept)
                ];
            }
        }
        
        // 2. BUSCAR SOPs EXISTENTES NO BANCO
        $sopsExistentes = Sop::buscarPorEmpresa($empresaId);
        $sopsMap = [];
        foreach ($sopsExistentes as $sop) {
            switch($sop['status']) {
                case 'ativo':
                    $status = 'aprovado';
                    break;
                case 'rascunho':
                    $status = 'gerado';
                    break;
                default:
                    $status = 'nao_gerado';
                    break;
            }
            
            $sopsMap[$sop['sop_codigo']] = [
                'id' => $sop['id'], // CORREÇÃO: usar ID numérico do banco
                'nome' => $sop['titulo'],
                'status' => $status,
                'sop_codigo' => $sop['sop_codigo']  // Manter código também
            ];
        }
        
        // 3. CRIAR SOPs ESPECÍFICOS PARA CADA DEPARTAMENTO REAL DA EMPRESA
        $departamentosEstruturados = [];
        
        foreach ($departamentosReais as $nomeDepartamento => $detalhes) {
            $iconeDept = $this->getIconePorDepartamento($nomeDepartamento);
            $sopsEspecificos = $this->criarSOPsEspecificosPorDepartamento($nomeDepartamento, $detalhes, $empresa, $diagnostico);
            
            // CORREÇÃO: Aplicar status dos SOPs existentes usando ID numérico
            foreach ($sopsEspecificos as &$sop) {
                $codigoSOP = $sop['id']; // Este é o código SOP
                if (isset($sopsMap[$codigoSOP])) {
                    $sop['status'] = $sopsMap[$codigoSOP]['status'];
                    $sop['id'] = $sopsMap[$codigoSOP]['id']; // USAR ID NUMÉRICO DO BANCO
                    $sop['sop_codigo'] = $codigoSOP; // Manter código também
                } else {
                    $sop['status'] = 'nao_gerado';
                    $sop['sop_codigo'] = $codigoSOP;
                }
            }
            
            $departamentosEstruturados[] = [
                'nome' => $nomeDepartamento,
                'icone' => $iconeDept,
                'sops' => $sopsEspecificos,
                'contexto_real' => $detalhes,  // Informações reais do diagnóstico
                'total_sops' => count($sopsEspecificos)
            ];
        }
        
        Logger::info('Departamentos criados com base no diagnóstico', [
            'total_departamentos' => count($departamentosEstruturados),
            'departamentos' => array_column($departamentosEstruturados, 'nome'),
            'total_sops' => array_sum(array_column($departamentosEstruturados, 'total_sops'))
        ]);
        
        return $departamentosEstruturados;
    }
    
    /**
     * Extrai departamentos REAIS e DETALHADOS do diagnóstico
     */
    private function extrairDepartamentosDetalhados(array $diagnostico): array
    {
        $respostas = json_decode($diagnostico['respostas'], true);
        if (!$respostas) {
            $respostas = [];
        }
        $departamentos = [];
        
        // Extrair departamentos básicos
        $departamentosTexto = $this->extrairDepartamentos($diagnostico);
        $departamentosArray = $this->parsearDepartamentos($departamentosTexto);
        
        // Para cada departamento, extrair informações específicas do diagnóstico
        foreach ($departamentosArray as $dept) {
            $departamentos[$dept] = [
                'nome' => $dept,
                'colaboradores' => $this->extrairColaboradoresPorDepartamento($respostas, $dept),
                'funcoes_principais' => $this->extrairFuncoesPorDepartamento($respostas, $dept),
                'ferramentas_usadas' => $this->extrairFerramentasPorDepartamento($respostas, $dept),
                'problemas_identificados' => $this->extrairProblemasPorDepartamento($respostas, $dept),
                'objetivos_especificos' => $this->extrairObjetivosPorDepartamento($respostas, $dept),
                'processos_principais' => $this->identificarProcessosPrincipais($dept, $respostas),
                'nivel_maturidade' => $this->calcularMaturidadePorDepartamento($respostas, $dept)
            ];
        }
        
        return $departamentos;
    }
    
    /**
     * Cria SOPs específicos para um departamento baseado no diagnóstico
     */
    private function criarSOPsEspecificosPorDepartamento(string $departamento, array $detalhes, array $empresa, array $diagnostico): array
    {
        $processos = $detalhes['processos_principais'];
        $sops = [];
        $contador = 1;
        
        foreach ($processos as $processo) {
            $codigoSOP = $this->gerarCodigoSOPEspecifico($empresa['segmento'], $departamento, $processo, $contador);
            
            $sops[] = [
                'id' => $codigoSOP,
                'nome' => $processo,
                'status' => 'nao_gerado',
                'departamento' => $departamento,
                'contexto_especifico' => [
                    'funcoes' => $detalhes['funcoes_principais'],
                    'ferramentas' => $detalhes['ferramentas_usadas'],
                    'problemas' => $detalhes['problemas_identificados'],
                    'objetivos' => $detalhes['objetivos_especificos'],
                    'maturidade' => $detalhes['nivel_maturidade']
                ],
                'customizado' => true,  // Marca como específico para a empresa
                'origem' => 'diagnostico_especifico'
            ];
            $contador++;
        }
        
        return $sops;
    }
    
    /**
     * Métodos auxiliares para extração de dados específicos
     */
    private function extrairColaboradoresPorDepartamento(array $respostas, string $dept): array
    {
        // Extrair colaboradores específicos por departamento baseado nas respostas
        return isset($respostas['colaboradores_' . strtolower($dept)]) ? $respostas['colaboradores_' . strtolower($dept)] : ['Quantidade não especificada'];
    }
    
    private function extrairFuncoesPorDepartamento(array $respostas, string $dept): array
    {
        $funcoesPadrao = [
            'Comercial' => ['Prospecção', 'Vendas', 'Relacionamento com clientes'],
            'TI' => ['Suporte técnico', 'Desenvolvimento', 'Infraestrutura'],
            'Operações' => ['Produção', 'Qualidade', 'Logística'],
            'Financeiro' => ['Contas a pagar/receber', 'Fluxo de caixa', 'Controles'],
            'RH' => ['Recrutamento', 'Treinamento', 'Gestão de pessoas']
        ];
        
        // Tentar extrair do diagnóstico, senão usar padrão
        $funcoes = isset($respostas['funcoes_' . strtolower($dept)]) ? $respostas['funcoes_' . strtolower($dept)] : null;
        if ($funcoes) {
            return $funcoes;
        }
        return isset($funcoesPadrao[$dept]) ? $funcoesPadrao[$dept] : ['Funções operacionais'];
    }
    
    private function extrairFerramentasPorDepartamento(array $respostas, string $dept): array
    {
        $ferramentasGerais = explode(',', $this->extrairFerramentas(['respostas' => json_encode($respostas)]));
        return array_map('trim', $ferramentasGerais);
    }
    
    private function extrairProblemasPorDepartamento(array $respostas, string $dept): array
    {
        $problemasGerais = $this->extrairProblemas(['respostas' => json_encode($respostas)]);
        return explode(',', $problemasGerais);
    }
    
    private function extrairObjetivosPorDepartamento(array $respostas, string $dept): array
    {
        $objetivosGerais = $this->extrairObjetivos(['respostas' => json_encode($respostas)]);
        return explode(',', $objetivosGerais);
    }
    
    private function identificarProcessosPrincipais(string $dept, array $respostas): array
    {
        $processosPorDepartamento = [
            'Comercial' => [
                'Prospecção de Clientes',
                'Qualificação de Leads', 
                'Apresentação de Propostas',
                'Negociação e Fechamento',
                'Pós-venda e Relacionamento'
            ],
            'TI' => [
                'Atendimento de Chamados',
                'Backup e Segurança',
                'Manutenção de Sistemas',
                'Desenvolvimento de Soluções',
                'Gestão de Infraestrutura'
            ],
            'Operações' => [
                'Recebimento de Pedidos',
                'Planejamento de Produção',
                'Controle de Qualidade',
                'Expedição de Produtos',
                'Gestão de Estoque'
            ],
            'Financeiro' => [
                'Controle de Fluxo de Caixa',
                'Contas a Pagar',
                'Contas a Receber',
                'Conciliação Bancária',
                'Relatórios Gerenciais'
            ],
            'RH' => [
                'Processo Seletivo',
                'Onboarding de Colaboradores',
                'Gestão de Performance',
                'Treinamento e Desenvolvimento',
                'Gestão de Benefícios'
            ]
        ];
        
        return isset($processosPorDepartamento[$dept]) ? $processosPorDepartamento[$dept] : ['Processo Operacional Padrão'];
    }
    
    private function calcularMaturidadePorDepartamento(array $respostas, string $dept): int
    {
        // Calcular maturidade específica baseada nas respostas do diagnóstico
        $score = isset($respostas['maturidade_percebida']) ? $respostas['maturidade_percebida'] : 2;
        return max(1, min(4, $score));
    }
    
    private function gerarCodigoSOPEspecifico(string $setor, string $departamento, string $processo, int $contador): string
    {
        switch(strtolower($setor)) {
            case 'tecnologia':
                $prefixoSetor = 'TEC';
                break;
            case 'saúde':
                $prefixoSetor = 'SAU';
                break;
            case 'educação':
                $prefixoSetor = 'EDU';
                break;
            case 'varejo':
                $prefixoSetor = 'VAR';
                break;
            case 'indústria':
                $prefixoSetor = 'IND';
                break;
            default:
                $prefixoSetor = 'GER';
                break;
        }
        
        $prefixoDept = strtoupper(substr($departamento, 0, 3));
        
        return sprintf('SOP-%s-%s-%03d', $prefixoSetor, $prefixoDept, $contador);
    }
    
    private function getIconePorDepartamento(string $dept): string
    {
        switch(strtolower($dept)) {
            case 'comercial':
                return '💼';
            case 'ti':
                return '💻';
            case 'operações':
            case 'operacoes':
                return '⚙️';
            case 'financeiro':
                return '💰';
            case 'rh':
                return '👥';
            case 'marketing':
                return '📢';
            case 'juridico':
            case 'jurídico':
                return '⚖️';
            default:
                return '📋';
        }
    }
    
    /**
     * Cria mapeamento empresarial completo baseado no diagnóstico
     */
    private function criarMapeamentoEmpresarial(array $empresa, ?array $diagnostico): array
    {
        // Extrair dados do diagnóstico se existir
        $respostas = [];
        if ($diagnostico && !empty($diagnostico['respostas'])) {
            $respostasJson = json_decode($diagnostico['respostas'], true);
            $respostas = $respostasJson ? $respostasJson : [];
        }
        
        // 1. MAPEAMENTO DE DEPARTAMENTOS
        $departamentosBasicos = $this->extrairDepartamentos($diagnostico);
        $departamentosArray = $this->parsearDepartamentos($departamentosBasicos);
        
        // 2. MAPEAMENTO DETALHADO POR DEPARTAMENTO
        $departamentosDetalhados = [];
        foreach ($departamentosArray as $dept) {
            $departamentosDetalhados[$dept] = $this->mapearDepartamento($dept, $empresa['segmento'], $respostas);
        }
        
        // 3. PROCEDIMENTOS PADRÃO DO MERCADO
        $procedimentosMercado = $this->obterProcedimentosMercado($empresa['segmento'], $departamentosArray);
        
        // 4. MAPEAMENTO DETALHADO UNIFICADO
        $mapeamentoDetalhado = $this->criarMapeamentoDetalhado($departamentosDetalhados, $procedimentosMercado);
        
        return [
            'colaboradores' => $this->extrairColaboradores($diagnostico),
            'faturamento' => $this->extrairFaturamento($diagnostico),
            'maturidade' => $empresa['score_maturidade'] ?? 2,
            'departamentos_texto' => $departamentosBasicos,
            'departamentos_array' => $departamentosArray,
            'departamentos_detalhados' => $departamentosDetalhados,
            'ferramentas' => $this->extrairFerramentas($diagnostico),
            'problemas' => $this->extrairProblemas($diagnostico),
            'objetivos' => $this->extrairObjetivos($diagnostico),
            'procedimentos_mercado' => $procedimentosMercado,
            'mapeamento_detalhado' => $mapeamentoDetalhado
        ];
    }
    
    /**
     * Mapeia um departamento específico com suas funções e responsabilidades
     */
    private function mapearDepartamento(string $departamento, string $setor, array $respostas): array
    {
        $mapeamentos = [
            'Comercial' => [
                'funcoes_principais' => [
                    'Prospecção e qualificação de leads',
                    'Negociação e fechamento de vendas', 
                    'Gestão do relacionamento com clientes',
                    'Elaboração de propostas comerciais',
                    'Controle de pipeline de vendas'
                ],
                'responsabilidades' => [
                    'Atingir metas de vendas mensais',
                    'Manter CRM atualizado',
                    'Realizar follow-up com prospects',
                    'Participar de reuniões comerciais',
                    'Elaborar relatórios de vendas'
                ],
                'kpis_essenciais' => [
                    'Taxa de conversão de leads',
                    'Ticket médio de vendas',
                    'Tempo médio de ciclo de vendas',
                    'CAC (Custo de Aquisição de Clientes)'
                ]
            ],
            'Financeiro' => [
                'funcoes_principais' => [
                    'Controle de fluxo de caixa',
                    'Contas a pagar e receber',
                    'Análise financeira e planejamento',
                    'Controle de custos e despesas',
                    'Relatórios gerenciais'
                ],
                'responsabilidades' => [
                    'Manter fluxo de caixa atualizado diariamente',
                    'Negociar prazos com fornecedores',
                    'Controlar inadimplência',
                    'Elaborar demonstrativos financeiros',
                    'Apoiar tomada de decisões estratégicas'
                ],
                'kpis_essenciais' => [
                    'Margem de lucro bruto',
                    'Inadimplência (%)',
                    'Prazo médio de recebimento',
                    'Controle orçamentário'
                ]
            ],
            'Operações' => [
                'funcoes_principais' => [
                    'Gestão da produção/entrega',
                    'Controle de qualidade',
                    'Gestão de estoque',
                    'Otimização de processos',
                    'Atendimento ao cliente'
                ],
                'responsabilidades' => [
                    'Garantir qualidade dos produtos/serviços',
                    'Otimizar custos operacionais',
                    'Manter padrões de atendimento',
                    'Gerenciar fornecedores',
                    'Implementar melhorias contínuas'
                ],
                'kpis_essenciais' => [
                    'Tempo médio de entrega',
                    'Índice de qualidade',
                    'Produtividade por colaborador',
                    'Satisfação do cliente'
                ]
            ],
            'TI' => [
                'funcoes_principais' => [
                    'Manutenção de infraestrutura',
                    'Desenvolvimento de sistemas',
                    'Suporte técnico interno',
                    'Segurança da informação',
                    'Automação de processos'
                ],
                'responsabilidades' => [
                    'Manter sistemas funcionando',
                    'Realizar backups regulares',
                    'Implementar medidas de segurança',
                    'Treinar usuários em ferramentas',
                    'Avaliar novas tecnologias'
                ],
                'kpis_essenciais' => [
                    'Uptime dos sistemas (%)',
                    'Tempo médio de resolução',
                    'Número de incidentes',
                    'Satisfação dos usuários internos'
                ]
            ],
            'RH' => [
                'funcoes_principais' => [
                    'Recrutamento e seleção',
                    'Gestão de pessoas',
                    'Treinamento e desenvolvimento',
                    'Controle de ponto e folha',
                    'Clima organizacional'
                ],
                'responsabilidades' => [
                    'Manter equipe motivada',
                    'Controlar turnover',
                    'Garantir compliance trabalhista',
                    'Desenvolver talentos internos',
                    'Mediar conflitos'
                ],
                'kpis_essenciais' => [
                    'Taxa de turnover',
                    'Tempo médio de contratação',
                    'Satisfação dos colaboradores',
                    'Horas de treinamento per capita'
                ]
            ]
        ];
        
        // Retornar mapeamento específico ou genérico
        return isset($mapeamentos[$departamento]) ? $mapeamentos[$departamento] : [
            'funcoes_principais' => ['Executar atividades do departamento', 'Apoiar objetivos estratégicos'],
            'responsabilidades' => ['Cumprir metas estabelecidas', 'Manter processos organizados'],
            'kpis_essenciais' => ['Produtividade', 'Qualidade dos entregáveis']
        ];
    }
    
    /**
     * Obtém procedimentos padrão do mercado por setor
     */
    private function obterProcedimentosMercado(string $setor, array $departamentos): array
    {
        $procedimentosBase = [
            'Tecnologia' => [
                'Comercial' => [
                    'Metodologia SPIN Selling para descoberta',
                    'Pipeline em CRM com 5 estágios mínimos',
                    'Follow-up estruturado com cadência definida',
                    'Proposta técnica + comercial padronizada'
                ],
                'Operações' => [
                    'Metodologia ágil (Scrum/Kanban)',
                    'Controle de qualidade com code review',
                    'Deploy automatizado com CI/CD',
                    'Monitoramento de performance'
                ],
                'TI' => [
                    'Backup 3-2-1 (3 cópias, 2 mídias, 1 offsite)',
                    'Monitoramento 24/7 de infraestrutura',
                    'Patch management mensal',
                    'Política de segurança ISO 27001'
                ]
            ],
            'Varejo' => [
                'Comercial' => [
                    'Técnicas de merchandising visual',
                    'Gestão de relacionamento pós-venda',
                    'Programa de fidelidade estruturado'
                ],
                'Operações' => [
                    'Controle de estoque Just-in-Time',
                    'Gestão de fornecedores qualificados',
                    'Padrão de atendimento ao cliente'
                ]
            ]
        ];
        
        $resultado = [];
        foreach ($departamentos as $dept) {
            if (isset($procedimentosBase[$setor][$dept])) {
                $resultado[$dept] = $procedimentosBase[$setor][$dept];
            } else {
                $resultado[$dept] = ['Procedimentos padrão da indústria', 'Boas práticas do mercado'];
            }
        }
        
        return $resultado;
    }
    
    /**
     * Cria mapeamento detalhado unificado
     */
    private function criarMapeamentoDetalhado(array $departamentosDetalhados, array $procedimentosMercado): string
    {
        $mapeamento = "=== MAPEAMENTO EMPRESARIAL COMPLETO ===\n\n";
        
        foreach ($departamentosDetalhados as $dept => $detalhes) {
            $mapeamento .= "DEPARTAMENTO: {$dept}\n";
            $mapeamento .= "Funções Principais:\n";
            foreach ($detalhes['funcoes_principais'] as $funcao) {
                $mapeamento .= "• {$funcao}\n";
            }
            
            $mapeamento .= "\nResponsabilidades:\n";
            foreach ($detalhes['responsabilidades'] as $resp) {
                $mapeamento .= "• {$resp}\n";
            }
            
            $mapeamento .= "\nKPIs Essenciais:\n";
            foreach ($detalhes['kpis_essenciais'] as $kpi) {
                $mapeamento .= "• {$kpi}\n";
            }
            
            if (isset($procedimentosMercado[$dept])) {
                $mapeamento .= "\nProcedimentos Padrão do Mercado:\n";
                foreach ($procedimentosMercado[$dept] as $proc) {
                    $mapeamento .= "• {$proc}\n";
                }
            }
            
            $mapeamento .= "\n" . str_repeat("-", 50) . "\n\n";
        }
        
        return $mapeamento;
    }
    
    /**
     * Converte string de departamentos em array
     */
    private function parsearDepartamentos(string $departamentos): array
    {
        // Limpar e separar departamentos
        $deps = array_map('trim', explode(',', $departamentos));
        $deps = array_filter($deps); // Remove vazios
        
        // Padronizar nomes
        $padronizacao = [
            'comercial' => 'Comercial',
            'vendas' => 'Comercial', 
            'financeiro' => 'Financeiro',
            'ti' => 'TI',
            'tecnologia' => 'TI',
            'operacoes' => 'Operações',
            'operações' => 'Operações',
            'rh' => 'RH',
            'recursos humanos' => 'RH',
            'marketing' => 'Marketing',
            'administrativo' => 'Administrativo'
        ];
        
        $resultado = [];
        foreach ($deps as $dep) {
            $depLower = strtolower($dep);
            if (isset($padronizacao[$depLower])) {
                $resultado[] = $padronizacao[$depLower];
            } else {
                $resultado[] = ucfirst($dep);
            }
        }
        
        return array_unique($resultado);
    }

    private function carregarDadosEmpresa(int $empresaId, ?int $diagnosticoEspecifico = null): array
    {
        $empresa = Empresa::buscarPorId($empresaId);
        if (!$empresa) {
            Flash::set('erro', 'Empresa não encontrada.');
            header('Location: ' . APP_URL . '/dashboard');
            exit;
        }

        $stats = Sop::estatisticas($empresaId);
        
        // Se foi passado um diagnóstico específico, usar ele; senão buscar o último
        if ($diagnosticoEspecifico) {
            $diagnostico = Diagnostico::buscarPorId($diagnosticoEspecifico);
        } else {
            $diagnostico = Diagnostico::buscarUltimoPorEmpresa($empresaId);
        }
        
        return [
            'id' => $empresa['id'],
            'nome' => $empresa['nome'],
            'segmento' => isset($empresa['segmento']) ? $empresa['segmento'] : 'Tecnologia',
            'maturidade' => isset($empresa['score_maturidade']) ? $empresa['score_maturidade'] : 2,
            'norma' => ApiHelper::getNormasPorSetor(isset($empresa['segmento']) ? $empresa['segmento'] : 'Tecnologia'),
            'total_sops' => $stats['total'],
            'aprovados' => $stats['aprovados'],
            'diagnostico_id' => isset($diagnostico['id']) ? $diagnostico['id'] : null,
        ];
    }

    /**
     * Interface para criar/gerenciar SOPs personalizados
     */
    public function gerenciarSOPs(): void
    {
        Auth::proteger();
        Auth::exigirPerfil([Auth::ADMIN_HOLDING, Auth::CONSULTOR_INTERNO]);
        
        $empresaId = (int) ($_GET['empresa_id'] ?? Auth::empresa());
        
        if (!$empresaId) {
            Flash::set('erro', 'Empresa não especificada.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }
        
        // Buscar empresa
        $empresa = Database::queryOne(
            "SELECT * FROM empresas WHERE id = :id",
            ['id' => $empresaId]
        );
        
        if (!$empresa) {
            Flash::set('erro', 'Empresa não encontrada.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }
        
        // Buscar SOPs customizados da empresa
        $sopsCustomizados = Database::query(
            "SELECT * FROM sops_customizados WHERE empresa_id = :empresa_id ORDER BY departamento, nome",
            ['empresa_id' => $empresaId]
        );
        
        $dados = [
            'empresa' => $empresa,
            'sops_customizados' => $sopsCustomizados,
            'setores_disponiveis' => $this->getSetoresDisponiveis(),
            'icone_helper' => function($icone) { return $this->converterIconeParaEmoji($icone); }
        ];
        
        require VIEW_PATH . '/sop/gerenciar.php';
    }

    /**
     * Adicionar novo SOP personalizado
     */
    public function adicionarSOP(): void
    {
        Auth::proteger();
        Auth::exigirPerfil([Auth::ADMIN_HOLDING, Auth::CONSULTOR_INTERNO]);
        Csrf::verificar();
        
        $empresaId = (int) ($_POST['empresa_id'] ?? 0);
        $sopCodigo = strtoupper(trim($_POST['sop_codigo'] ?? ''));
        $nome = htmlspecialchars(trim($_POST['nome'] ?? ''));
        $departamento = htmlspecialchars(trim($_POST['departamento'] ?? ''));
        $descricao = htmlspecialchars(trim($_POST['descricao'] ?? ''));
        
        // Validações
        if (!$empresaId || empty($sopCodigo) || empty($nome) || empty($departamento)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Todos os campos são obrigatórios.']);
            exit;
        }
        
        // Verificar se código já existe
        $existe = Database::queryOne(
            "SELECT id FROM sops_customizados WHERE empresa_id = :empresa_id AND sop_codigo = :codigo",
            ['empresa_id' => $empresaId, 'codigo' => $sopCodigo]
        );
        
        if ($existe) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Código SOP já existe para esta empresa.']);
            exit;
        }
        
        try {
            // Inserir SOP customizado
            Database::execute(
                "INSERT INTO sops_customizados (empresa_id, sop_codigo, nome, departamento, descricao, criado_por, criado_em) 
                 VALUES (:empresa_id, :codigo, :nome, :depto, :descricao, :user_id, NOW())",
                [
                    'empresa_id' => $empresaId,
                    'codigo' => $sopCodigo,
                    'nome' => $nome,
                    'depto' => $departamento,
                    'descricao' => $descricao,
                    'user_id' => Auth::id()
                ]
            );
            
            Logger::acao('SOP personalizado criado', [
                'empresa_id' => $empresaId,
                'sop_codigo' => $sopCodigo,
                'nome' => $nome
            ]);
            
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true, 'mensagem' => 'SOP personalizado criado com sucesso!']);
            
        } catch (Exception $e) {
            Logger::error('Erro ao criar SOP personalizado: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno ao criar SOP.']);
        }
        exit;
    }

    /**
     * Remover SOP personalizado
     */
    public function removerSOP(): void
    {
        Auth::proteger();
        Auth::exigirPerfil([Auth::ADMIN_HOLDING, Auth::CONSULTOR_INTERNO]);
        Csrf::verificar();
        
        $sopId = (int) ($_POST['sop_id'] ?? 0);
        
        if (!$sopId) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'ID do SOP é obrigatório.']);
            exit;
        }
        
        try {
            // Verificar se SOP existe e buscar dados
            $sop = Database::queryOne(
                "SELECT * FROM sops_customizados WHERE id = :id",
                ['id' => $sopId]
            );
            
            if (!$sop) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'SOP não encontrado.']);
                exit;
            }
            
            // Remover SOP customizado
            Database::execute("DELETE FROM sops_customizados WHERE id = :id", ['id' => $sopId]);
            
            // Remover SOP gerado se existir
            Database::execute(
                "DELETE FROM sops WHERE sop_codigo = :codigo AND empresa_id = :empresa_id",
                ['codigo' => $sop['sop_codigo'], 'empresa_id' => $sop['empresa_id']]
            );
            
            Logger::acao('SOP personalizado removido', [
                'sop_id' => $sopId,
                'sop_codigo' => $sop['sop_codigo']
            ]);
            
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true, 'mensagem' => 'SOP removido com sucesso!']);
            
        } catch (Exception $e) {
            Logger::error('Erro ao remover SOP: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno.']);
        }
        exit;
    }

    /**
     * Retorna lista de setores disponíveis
     */
    private function getSetoresDisponiveis(): array
    {
        return [
            'Construção Civil' => 'Construção Civil',
            'Tecnologia' => 'Tecnologia',
            'Saúde' => 'Saúde',
            'Educação' => 'Educação',
            'Consultoria' => 'Consultoria',
            'Varejo' => 'Varejo',
            'Indústria' => 'Indústria',
            'Alimentício' => 'Alimentício',
            'Geral' => 'Outros Setores'
        ];
    }

    /**
     * Converte ícones de texto para emojis para exibição
     */
    private function converterIconeParaEmoji(string $icone): string
    {
        $mapeamento = [
            'documento' => '📋',
            'pacote' => '📦',
            'chave' => '🔑',
            'comercial' => '💼',
            'operacional' => '⚙️',
            'administrativo' => '📊',
            'financeiro' => '💰',
            'rh' => '👥',
            'juridico' => '⚖️',
            'ti' => '💻',
            'vendas' => '🛒',
            'producao' => '🏭',
            'saude' => '🏥',
            'educacao' => '🎓',
            'construcao' => '🚧',
            'alimenticio' => '🍽️',
            'consultoria' => '📈',
            'seguranca' => '🔒',
            'logistica' => '🚚'
        ];
        
        return $mapeamento[strtolower($icone)] ?? '📋';
    }

    /**
     * Gera um SOP individual via AJAX (F-05 Implementation)
     */
    public function gerar(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $sopCodigo = htmlspecialchars(trim(isset($_POST['sop_id']) ? $_POST['sop_id'] : ''));
        $sopNome = htmlspecialchars(trim(isset($_POST['sop_nome']) ? $_POST['sop_nome'] : ''));
        $diagnosticoIdPost = (int) (isset($_POST['diagnostico_id']) ? $_POST['diagnostico_id'] : 0);
        
        // IMPORTANTE: Priorizar empresa do diagnóstico, não do usuário atual
        if ($diagnosticoIdPost > 0) {
            $diagnosticoEspecifico = Diagnostico::buscarPorId($diagnosticoIdPost);
            if ($diagnosticoEspecifico) {
                $empresaId = $diagnosticoEspecifico['empresa_id'];
                Logger::info('DIAGNÓSTICO ESPECÍFICO DETECTADO - Usando dados específicos da empresa', [
                    'diagnostico_id' => $diagnosticoIdPost,
                    'empresa_id' => $empresaId,
                    'sop_codigo' => $sopCodigo,
                    'modo' => 'ESPECÍFICO_DO_DIAGNÓSTICO'
                ]);
            } else {
                $empresaId = Auth::garantirEmpresa();
                Logger::warning('Diagnóstico especificado não encontrado, usando empresa do usuário', [
                    'diagnostico_id_solicitado' => $diagnosticoIdPost,
                    'empresa_usuario' => $empresaId
                ]);
            }
        } else {
            $empresaId = Auth::garantirEmpresa();
            Logger::info('Modo genérico - usando empresa do usuário atual', [
                'empresa_id' => $empresaId,
                'modo' => 'GENÉRICO'
            ]);
        }

        if (empty($sopCodigo) || !$empresaId) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Dados obrigatórios não informados.']);
            exit;
        }

        // Verificar se SOP já existe
        $sopExistente = Sop::buscarPorCodigo($sopCodigo);
        if ($sopExistente) {
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'SOP já existe. Redirecionando para revisão.',
                'redirect' => APP_URL . '/sop/revisar?id=' . $sopExistente['id'],
            ]);
            exit;
        }

        // Buscar dados da empresa e diagnóstico
        $empresa = Empresa::buscarPorId($empresaId);
        
        // SEMPRE priorizar diagnóstico específico se foi passado
        $diagnostico = null;
        if ($diagnosticoIdPost > 0) {
            $diagnostico = Diagnostico::buscarPorId($diagnosticoIdPost);
            Logger::info('USANDO DIAGNÓSTICO ESPECÍFICO para geração de SOP', [
                'diagnostico_id' => $diagnosticoIdPost,
                'sop_codigo' => $sopCodigo,
                'empresa_id' => $empresaId,
                'empresa_nome' => isset($empresa['nome']) ? $empresa['nome'] : 'N/A',
                'diagnostico_encontrado' => !empty($diagnostico),
                'respostas_diagnostico' => !empty($diagnostico['respostas'])
            ]);
        } else {
            $diagnostico = Diagnostico::buscarUltimoPorEmpresa($empresaId);
            Logger::info('Usando último diagnóstico da empresa', [
                'empresa_id' => $empresaId,
                'diagnostico_id' => isset($diagnostico['id']) ? $diagnostico['id'] : null
            ]);
        }
        
        if (!$empresa) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Empresa não encontrada.']);
            exit;
        }

        // PROCESSO ROBUSTO DE MAPEAMENTO COM DIAGNÓSTICO ESPECÍFICO
        $mapeamentoCompleto = $this->criarMapeamentoEmpresarial($empresa, $diagnostico);
        
        Logger::info('Mapeamento empresarial criado', [
            'empresa_id' => $empresaId,
            'diagnostico_usado' => !empty($diagnostico),
            'departamentos_extraidos' => $mapeamentoCompleto['departamentos_texto'],
            'ferramentas_extraidas' => $mapeamentoCompleto['ferramentas'],
            'problemas_extraidos' => $mapeamentoCompleto['problemas'],
            'colaboradores' => $mapeamentoCompleto['colaboradores'],
            'maturidade' => $mapeamentoCompleto['maturidade']
        ]);
        
        // Montar dados ESPECÍFICOS da empresa para o prompt
        $empresaDados = [
            'nome' => $empresa['nome'],
            'setor' => $empresa['segmento'] ?? 'Tecnologia',
            'colaboradores' => $mapeamentoCompleto['colaboradores'],
            'faturamento' => $mapeamentoCompleto['faturamento'],
            'maturidade' => $mapeamentoCompleto['maturidade'],
            'departamentos' => $mapeamentoCompleto['departamentos_texto'],
            'ferramentas' => $mapeamentoCompleto['ferramentas'],
            'problemas' => $mapeamentoCompleto['problemas'],
            'objetivos' => $mapeamentoCompleto['objetivos'],
            'mapeamento_detalhado' => $mapeamentoCompleto['mapeamento_detalhado'],
            'procedimentos_mercado' => $mapeamentoCompleto['procedimentos_mercado']
        ];
        
        Logger::info('Dados específicos da empresa preparados para IA', [
            'empresa_nome' => $empresaDados['nome'],
            'setor' => $empresaDados['setor'],
            'colaboradores' => $empresaDados['colaboradores'],
            'departamentos' => $empresaDados['departamentos'],
            'ferramentas' => $empresaDados['ferramentas'],
            'problemas' => substr($empresaDados['problemas'], 0, 200) . '...',
            'tem_mapeamento_detalhado' => !empty($empresaDados['mapeamento_detalhado']),
            'tem_procedimentos_mercado' => !empty($empresaDados['procedimentos_mercado'])
        ]);

        // Dados ESPECÍFICOS do SOP com contexto do diagnóstico
        $departamentoSOP = $this->getDepartamentoPorId($sopCodigo);
        $contextoEspecifico = isset($mapeamentoCompleto['departamentos_detalhados'][$departamentoSOP]) ? $mapeamentoCompleto['departamentos_detalhados'][$departamentoSOP] : null;
        
        $sopData = [
            'id' => $sopCodigo,
            'nome' => $sopNome,
            'departamento' => $departamentoSOP,
            'subtopicos_texto' => $this->getSubtopicosPorIdEspecifico($sopCodigo, $diagnostico),
            'contexto_departamento' => $contextoEspecifico
        ];

        // Buscar documentos relevantes da empresa para enriquecer o SOP
        $documentosRelevantes = [];
        $contextoDocumentos = '';
        
        try {
            $areas = [$this->getDepartamentoPorId($sopCodigo)];
            // Verificar se a classe DocumentoProcessor está disponível
            if (class_exists('DocumentoProcessor')) {
                $documentosRelevantes = DocumentoProcessor::buscarDocumentosRelevantes($empresaId, $areas);
                $contextoDocumentos = DocumentoProcessor::construirContextoDocumentos($documentosRelevantes);
            }
            
            Logger::info('Documentos encontrados para SOP', [
                'sop_codigo' => $sopCodigo,
                'documentos_encontrados' => count($documentosRelevantes)
            ]);
        } catch (Exception $e) {
            Logger::warning('Erro ao buscar documentos para SOP', [
                'sop_codigo' => $sopCodigo,
                'erro' => $e->getMessage()
            ]);
        }

        // Gerar prompt estruturado com contexto dos documentos - USAR PROMPT PADRÃO-OURO
        $prompt = ApiHelper::buildPromptSopDetalhado($empresaDados, $sopData, $contextoDocumentos);
        
        // Log para acompanhar uso da nova estrutura
        Logger::info('Gerando SOP com prompt padrão-ouro DETALHADO', [
            'sop_codigo' => $sopCodigo,
            'empresa' => $empresa['nome'],
            'setor' => $empresa['segmento'] ?? 'Tecnologia',
            'prompt_size' => strlen($prompt),
            'contexto_documentos' => !empty($contextoDocumentos),
            'departamentos_empresa' => isset($mapeamentoCompleto['departamentos_array']) ? $mapeamentoCompleto['departamentos_array'] : [],
            'usa_diagnostico_especifico' => !empty($diagnosticoIdPost),
            'diagnostico_id' => $diagnosticoIdPost ?? null,
            'ferramentas_empresa' => $empresaDados['ferramentas'],
            'problemas_empresa' => substr($empresaDados['problemas'], 0, 100),
            'contexto_departamento_especifico' => !empty($contextoEspecifico)
        ]);

        // Chamar IA (GPT ou Claude conforme config)
        $resultado = ApiHelper::chamarAnalise($prompt, true);

        if ($resultado['sucesso'] && is_array($resultado['conteudo'])) {
            // Log da estrutura retornada pela IA
            $novaEstrutura = isset($resultado['conteudo']['cabecalho']);
            Logger::info('Resposta da IA recebida', [
                'sop_codigo' => $sopCodigo,
                'nova_estrutura_detectada' => $novaEstrutura,
                'secoes_retornadas' => array_keys($resultado['conteudo']),
                'total_secoes' => count($resultado['conteudo'])
            ]);
            
            // Sucesso: criar SOP no banco
            $sopId = $this->criarSopNoBanco($sopCodigo, $sopNome, $empresaId, isset($diagnostico['id']) ? $diagnostico['id'] : null, $resultado['conteudo']);
            
            if ($sopId) {
                // Registrar uso dos documentos que contribuíram para o SOP
                if (class_exists('DocumentoProcessor') && !empty($documentosRelevantes)) {
                    foreach ($documentosRelevantes as $doc) {
                        DocumentoProcessor::registrarUso($doc['id'], $empresaId, Auth::id(), 'sop_geracao', $sopId);
                    }
                }
                
                Logger::acao('SOP gerado via IA', [
                    'sop_codigo' => $sopCodigo, 
                    'sop_id' => $sopId,
                    'documentos_utilizados' => count($documentosRelevantes),
                    'diagnostico_origem' => isset($diagnostico['id']) ? $diagnostico['id'] : 'ultimo_disponivel'
                ]);
                header('Content-Type: application/json');
                echo json_encode([
                    'sucesso' => true,
                    'mensagem' => 'SOP gerado com sucesso!' . 
                        (count($documentosRelevantes) > 0 ? " Utilizou " . count($documentosRelevantes) . " documento(s) da empresa." : ''),
                    'redirect' => APP_URL . '/sop/revisar?id=' . $sopId,
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Erro ao salvar SOP no banco de dados.']);
            }
        } else {
            // IA falhou: criar SOP básico e avisar
            $sopId = $this->criarSopBasico($sopCodigo, $sopNome, $empresaId, isset($diagnostico['id']) ? $diagnostico['id'] : null);
            Logger::warning('SOP gerado básico (IA falhou)', ['sop_codigo' => $sopCodigo, 'erro' => $resultado['erro']]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'SOP gerado! (Alguns campos podem precisar de ajuste.)',
                'redirect' => APP_URL . '/sop/revisar?id=' . $sopId,
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
            'setor' => $empresa['segmento'] ?? 'Tecnologia',
            'norma' => ApiHelper::getNormasPorSetor($empresa['segmento'] ?? 'Tecnologia'),
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
     * Retorna subtópicos específicos baseados no diagnóstico da empresa
     */
    private function getSubtopicosPorIdEspecifico(string $sopId, ?array $diagnostico): string
    {
        if (!$diagnostico) {
            return $this->getSubtopicosPorId($sopId);
        }
        
        $respostas = json_decode($diagnostico['respostas'], true) ?? [];
        $departamento = $this->getDepartamentoPorId($sopId);
        
        // Gerar subtópicos específicos baseados no diagnóstico
        $problemasEmpresa = explode(',', $this->extrairProblemas($diagnostico));
        $ferramentasEmpresa = explode(',', $this->extrairFerramentas($diagnostico));
        
        switch($departamento) {
            case 'Comercial':
                return "- Subtópico A: Prospecção usando " . implode(' e ', array_slice($ferramentasEmpresa, 0, 2)) . 
                       "\n- Subtópico B: Qualificação de Leads e Follow-up" .
                       "\n- Subtópico C: Fechamento e Pós-venda";
                       
            case 'TI':
                return "- Subtópico A: Recebimento via " . implode(' e ', array_slice($ferramentasEmpresa, 0, 2)) .
                       "\n- Subtópico B: Classificação por Prioridade" .
                       "\n- Subtópico C: Resolução e Documentação";
                       
            case 'Operações':
                $problema1 = trim($problemasEmpresa[0] ?? 'Falta de padronização');
                return "- Subtópico A: Planejamento e Recursos" .
                       "\n- Subtópico B: Execução com Controle de Qualidade" .
                       "\n- Subtópico C: Tratamento de: " . $problema1;
                       
            case 'Financeiro':
                return "- Subtópico A: Controle Diário de Fluxo" .
                       "\n- Subtópico B: Conciliação e Conferências" .
                       "\n- Subtópico C: Relatórios e Análises";
                       
            case 'RH':
                return "- Subtópico A: Processo Seletivo" .
                       "\n- Subtópico B: Onboarding e Integração" .
                       "\n- Subtópico C: Acompanhamento e Desenvolvimento";
                       
            default:
                return "- Subtópico A: Preparação e Planejamento Específico" .
                       "\n- Subtópico B: Execução com Monitoramento" .
                       "\n- Subtópico C: Verificação e Melhoria Contínua";
        }
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
     * Tela de revisão do SOP gerado (13 componentes) — F-05 Implementation
     */
    public function revisar(): void
    {
        Auth::proteger();

        $sopId = (int) ($_GET['id'] ?? 0);
        if (!$sopId) {
            Flash::set('erro', 'SOP não encontrado.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }

        // Buscar SOP no banco
        $sop = Sop::buscarPorId($sopId);
        if (!$sop || !Auth::podeAcessarEmpresa($sop['empresa_id'])) {
            Flash::set('erro', 'SOP não encontrado ou sem permissão.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }

        // Decodificar conteúdo completo
        $conteudoCompleto = json_decode($sop['conteudo_completo'], true);
        if (!$conteudoCompleto) {
            Flash::set('erro', 'Conteúdo do SOP não encontrado ou corrompido.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }

        // Formatar dados para a view (manter compatibilidade com view existente)
        $sopFormatado = $this->formatarSopParaView($sop, $conteudoCompleto);

        $dados = [
            'sop' => $sopFormatado,
        ];

        require VIEW_PATH . '/sop/revisar.php';
    }

    /**
     * Aprova SOP via AJAX — F-05 Implementation
     */
    public function aprovar(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $sopId = (int) ($_POST['sop_id'] ?? 0);
        $usuarioId = Auth::usuarioId();

        if (!$sopId || !$usuarioId) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Dados obrigatórios não informados.']);
            exit;
        }

        // Verificar permissão
        $sop = Sop::buscarPorId($sopId);
        if (!$sop || !Auth::podeAcessarEmpresa($sop['empresa_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'SOP não encontrado ou sem permissão.']);
            exit;
        }

        if ($sop['status'] === 'ativo') {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'SOP já está aprovado.']);
            exit;
        }

        // Aprovar SOP (salva KPIs e contingência automaticamente)
        $sucesso = Sop::aprovar($sopId, $usuarioId);

        if ($sucesso) {
            Logger::acao('SOP aprovado', ['sop_id' => $sopId, 'sop_codigo' => $sop['sop_codigo']]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true, 'mensagem' => 'SOP aprovado! KPIs e planos de contingência foram salvos automaticamente.']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno ao aprovar SOP.']);
        }
        exit;
    }

    /**
     * Salva rascunho via AJAX — F-05 Implementation
     */
    public function salvarRascunho(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $sopId = (int) ($_POST['sop_id'] ?? 0);
        
        if (!$sopId) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'ID do SOP é obrigatório.']);
            exit;
        }

        // Verificar permissão
        $sop = Sop::buscarPorId($sopId);
        if (!$sop || !Auth::podeAcessarEmpresa($sop['empresa_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'SOP não encontrado ou sem permissão.']);
            exit;
        }

        // Processar campos editados enviados via POST
        $camposEditados = [];
        $camposPermitidos = [
            'objetivo', 'escopo_aplica', 'escopo_nao_aplica', 'subtopicos', 
            'responsaveis', 'prerequisitos', 'ferramentas', 'checklist', 
            'evidencias', 'relatorios', 'kpis', 'contencao_n1', 'contencao_n2', 'contencao_n3'
        ];
        
        foreach ($camposPermitidos as $campo) {
            if (isset($_POST[$campo])) {
                $camposEditados[$campo] = is_array($_POST[$campo]) ? $_POST[$campo] : trim($_POST[$campo]);
            }
        }
        
        // Se há campos editados, atualizar o conteúdo
        if (!empty($camposEditados)) {
            $conteudoAtual = json_decode($sop['conteudo_completo'], true) ?: [];
            $conteudoAtualizado = array_merge($conteudoAtual, $camposEditados);
            
            // Salvar conteúdo atualizado
            $sucesso = Database::execute(
                "UPDATE sops SET conteudo_completo = :conteudo, atualizado_em = NOW() WHERE id = :id",
                [
                    'conteudo' => json_encode($conteudoAtualizado, JSON_UNESCAPED_UNICODE),
                    'id' => $sopId
                ]
            );
            
            if (!$sucesso) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Erro ao salvar alterações.']);
                exit;
            }
        }
        
        Logger::acao('SOP salvo como rascunho', ['sop_id' => $sopId, 'sop_codigo' => $sop['sop_codigo']]);

        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'mensagem' => 'Rascunho salvo!']);
        exit;
    }

    /**
     * Visualizar SOP completo com subtópicos — F-06 Implementation
     */
    public function ver(): void
    {
        Auth::proteger();

        $sopId = (int) ($_GET['id'] ?? 0);
        if (!$sopId) {
            Flash::set('erro', 'SOP não encontrado.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }

        // Buscar SOP
        $sop = Sop::buscarPorId($sopId);
        if (!$sop || !Auth::podeAcessarEmpresa($sop['empresa_id'])) {
            Flash::set('erro', 'SOP não encontrado ou sem permissão.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }

        if ($sop['status'] !== 'ativo') {
            Flash::set('aviso', 'Este SOP ainda não foi aprovado.');
            header('Location: ' . APP_URL . '/sop/revisar?id=' . $sopId);
            exit;
        }

        // Decodificar conteúdo completo
        $conteudoCompleto = json_decode($sop['conteudo_completo'], true);
        if (!$conteudoCompleto) {
            Flash::set('erro', 'Conteúdo do SOP não encontrado.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }

        // Buscar empresa
        $empresa = Empresa::buscarPorId($sop['empresa_id']);

        // Buscar alertas ativos para este SOP
        $alertasAtivos = $this->buscarAlertasAtivos($sopId);

        // Buscar KPIs do SOP
        $kpis = Sop::buscarKpis($sopId);

        // Formatar dados para view
        $sopFormatado = $this->formatarSopParaViewCompleta($sop, $conteudoCompleto, $empresa, $kpis);

        $dados = [
            'sop' => $sopFormatado,
            'alertas_ativos' => $alertasAtivos,
        ];

        require VIEW_PATH . '/sop/ver.php';
    }

    /**
     * Carregar planos de contingência via AJAX — F-06 Implementation
     */
    public function contencao(): void
    {
        Auth::proteger();

        $sopId = (int) ($_GET['id'] ?? 0);
        $nivelSugerido = $_GET['nivel'] ?? 'N1';

        if (!$sopId) {
            echo '<div class="text-center py-8 text-red-600">SOP não encontrado.</div>';
            exit;
        }

        // Buscar SOP
        $sop = Sop::buscarPorId($sopId);
        if (!$sop || !Auth::podeAcessarEmpresa($sop['empresa_id'])) {
            echo '<div class="text-center py-8 text-red-600">SOP não encontrado ou sem permissão.</div>';
            exit;
        }

        // Buscar planos de contingência
        $contencoes = Sop::buscarContencoes($sopId);
        $planos = [];
        
        foreach ($contencoes as $contencao) {
            $nivel = strtolower($contencao['nivel']);
            $planos[$nivel] = [
                'situacao' => $contencao['situacao'],
                'acao' => $contencao['acao'], 
                'quem' => $contencao['responsavel'],
                'escalar' => $contencao['escalar_se'],
                'comunicacao' => $contencao['comunicacao'],
                'documentacao' => $contencao['documentacao_obrigatoria'],
                'advogado_responsavel' => $contencao['nivel'] === 'N3' ? 'Dr. João Silva - (11) 99999-9999' : null,
            ];
        }

        // Buscar histórico de acionamentos
        $historicoContencao = $this->buscarHistoricoContencao($sopId);

        // Incluir view (não layout, é AJAX)
        include VIEW_PATH . '/sop/contencao.php';
    }

    /**
     * Acionar plano de contingência — F-06 Implementation  
     */
    public function acionarContencao(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $sopId = (int) ($_POST['sop_id'] ?? 0);
        $nivel = $_POST['nivel'] ?? '';
        $situacaoDetectada = trim($_POST['situacao_detectada'] ?? '');

        if (!$sopId || !in_array($nivel, ['N1', 'N2', 'N3']) || empty($situacaoDetectada)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Dados obrigatórios não informados.']);
            exit;
        }

        // Verificar permissão
        $sop = Sop::buscarPorId($sopId);
        if (!$sop || !Auth::podeAcessarEmpresa($sop['empresa_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'SOP não encontrado ou sem permissão.']);
            exit;
        }

        // Buscar plano de contingência específico
        $contencao = Database::queryOne(
            "SELECT * FROM sop_contencoes WHERE sop_id = :sop_id AND nivel = :nivel LIMIT 1",
            ['sop_id' => $sopId, 'nivel' => $nivel]
        );

        if (!$contencao) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Plano de contingência não encontrado.']);
            exit;
        }

        Database::beginTransaction();

        try {
            // 1. Registrar ocorrência de contingência
            $ocorrenciaId = Database::execute(
                "INSERT INTO ocorrencias_contencao (empresa_id, sop_id, contencao_id, nivel, situacao_detectada, responsavel_execucao, usuario_responsavel, data_inicio) 
                 VALUES (:empresa_id, :sop_id, :contencao_id, :nivel, :situacao_detectada, :responsavel_execucao, :usuario_responsavel, NOW())",
                [
                    'empresa_id' => $sop['empresa_id'],
                    'sop_id' => $sopId,
                    'contencao_id' => $contencao['id'],
                    'nivel' => $nivel,
                    'situacao_detectada' => $situacaoDetectada,
                    'responsavel_execucao' => $contencao['responsavel'],
                    'usuario_responsavel' => Auth::usuarioId(),
                ]
            ) ? Database::lastInsertId() : 0;

            // 2. Se é N2 ou N3, agendar revisão automática do SOP
            if (in_array($nivel, ['N2', 'N3'])) {
                $motivo = "Contenção {$nivel} acionada: {$situacaoDetectada}";
                Database::execute(
                    "UPDATE sops SET necessita_revisao = 1, motivo_revisao = :motivo, data_agendamento_revisao = NOW() 
                     WHERE id = :sop_id",
                    ['motivo' => $motivo, 'sop_id' => $sopId]
                );
            }

            // 3. Executar ações automáticas específicas por nível
            $acaoResultado = $this->executarContencaoAutomatica($nivel, $sop, $contencao, $situacaoDetectada);

            Database::commit();

            Logger::acao('Contingência acionada', [
                'sop_id' => $sopId,
                'nivel' => $nivel,
                'situacao' => $situacaoDetectada,
                'ocorrencia_id' => $ocorrenciaId,
                'acao_resultado' => $acaoResultado
            ]);

            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true, 
                'mensagem' => "Contingência {$nivel} acionada com sucesso.",
                'ocorrencia_id' => $ocorrenciaId,
                'proxima_acao' => $acaoResultado,
                'revisao_agendada' => in_array($nivel, ['N2', 'N3'])
            ]);

        } catch (Exception $e) {
            Database::rollback();
            Logger::error('Erro ao acionar contingência', [
                'sop_id' => $sopId,
                'nivel' => $nivel,
                'erro' => $e->getMessage()
            ]);
            
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao registrar acionamento: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Executa ações automáticas específicas por nível de contenção
     */
    private function executarContencaoAutomatica(string $nivel, array $sop, array $contencao, string $situacao): string
    {
        $empresaId = $sop['empresa_id'];
        $resultado = '';

        switch ($nivel) {
            case 'N1':
                // Ação básica: apenas registrar e alertar
                $resultado = 'Monitoramento ativo iniciado. Responsável notificado.';
                break;

            case 'N2':
                // Ação intermediária: escalar e intensificar monitoramento
                $resultado = 'Processo escalado. Monitoramento intensivo ativo. SOP agendado para revisão.';
                
                // Criar alerta crítico para direção
                Database::execute(
                    "INSERT INTO alertas (empresa_id, sop_id, tipo, titulo, descricao, prioridade, status, data_criacao)
                     VALUES (:empresa_id, :sop_id, 'contencao_n2', 'Contenção N2 Acionada', :descricao, 'critica', 'ativo', NOW())",
                    [
                        'empresa_id' => $empresaId,
                        'sop_id' => $sop['id'],
                        'descricao' => "SOP {$sop['sop_codigo']}: {$situacao}. Ação imediata necessária."
                    ]
                );
                break;

            case 'N3':
                // Ação crítica: parar processo e escalar para direção
                $resultado = 'PROCESSO INTERROMPIDO. Escalado para direção executiva. Análise de causa raiz obrigatória.';
                
                // Criar múltiplos alertas críticos
                Database::execute(
                    "INSERT INTO alertas (empresa_id, sop_id, tipo, titulo, descricao, prioridade, status, data_criacao)
                     VALUES (:empresa_id, :sop_id, 'contencao_n3_critica', 'CONTENÇÃO N3 - PROCESSO INTERROMPIDO', :descricao, 'critica', 'ativo', NOW())",
                    [
                        'empresa_id' => $empresaId,
                        'sop_id' => $sop['id'],
                        'descricao' => "URGENTE - SOP {$sop['sop_codigo']}: {$situacao}. Processo interrompido. Direção deve intervir IMEDIATAMENTE."
                    ]
                );

                // Agendar reunião de emergência (se sistema de agenda existir)
                try {
                    Database::execute(
                        "INSERT INTO agenda_emergencia (empresa_id, sop_id, titulo, descricao, data_agendamento, prioridade)
                         VALUES (:empresa_id, :sop_id, :titulo, :descricao, DATE_ADD(NOW(), INTERVAL 2 HOUR), 'critica')",
                        [
                            'empresa_id' => $empresaId,
                            'sop_id' => $sop['id'],
                            'titulo' => "REUNIÃO DE EMERGÊNCIA - Contenção N3",
                            'descricao' => "Análise de causa raiz obrigatória para: {$situacao}"
                        ]
                    );
                } catch (Exception $e) {
                    // Tabela de agenda pode não existir ainda
                    Logger::info('Agenda de emergência não criada - tabela inexistente', ['sop_id' => $sop['id']]);
                }
                break;
        }

        return $resultado;
    }

    /**
     * Buscar contenção por SOP e nível
     */
    private function buscarContencaoPorNivel(int $sopId, int $nivel): ?array
    {
        return Database::queryOne(
            "SELECT * FROM sop_contencoes WHERE sop_id = :sop_id AND nivel = :nivel LIMIT 1",
            ['sop_id' => $sopId, 'nivel' => $nivel]
        );

        if (!$contencao) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Plano de contingência não encontrado.']);
            exit;
        }

        // Registrar ocorrência
        $sucesso = Database::execute(
            "INSERT INTO ocorrencias_contencao (empresa_id, sop_id, contencao_id, nivel, situacao_detectada, responsavel_execucao, usuario_responsavel) 
             VALUES (:empresa_id, :sop_id, :contencao_id, :nivel, :situacao_detectada, :responsavel_execucao, :usuario_responsavel)",
            [
                'empresa_id' => $sop['empresa_id'],
                'sop_id' => $sopId,
                'contencao_id' => $contencao['id'],
                'nivel' => $nivel,
                'situacao_detectada' => $situacaoDetectada,
                'responsavel_execucao' => $contencao['responsavel'],
                'usuario_responsavel' => Auth::usuarioId(),
            ]
        );

        if ($sucesso) {
            Logger::acao('Contingência acionada', [
                'sop_id' => $sopId,
                'nivel' => $nivel,
                'situacao' => $situacaoDetectada
            ]);

            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true, 'mensagem' => "Contingência {$nivel} acionada com sucesso."]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao registrar acionamento.']);
        }
        exit;
    }
    /**
     * API para retornar função RACI de um cargo específico
     */
    public function getRaciFuncao(): void
    {
        Auth::proteger();
        
        $sopId = htmlspecialchars(trim($_GET['sop_id'] ?? ''));
        $cargo = htmlspecialchars(trim($_GET['cargo'] ?? ''));
        
        if (empty($sopId) || empty($cargo)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'SOP ID e cargo são obrigatórios.']);
            exit;
        }
        
        try {
            // Buscar matriz RACI do banco (se existir)
            $raciMatriz = Database::queryOne(
                "SELECT matriz_raci FROM sops WHERE sop_codigo = :sop_codigo",
                ['sop_codigo' => $sopId]
            );
            
            if ($raciMatriz && $raciMatriz['matriz_raci']) {
                $matriz = json_decode($raciMatriz['matriz_raci'], true);
                $funcao = $matriz[$cargo] ?? 'I';
            } else {
                // Usar matriz RACI padrão por tipo de SOP
                $funcao = $this->getFuncaoRaciPadrao($sopId, $cargo);
            }
            
            $descricoes = [
                'R' => 'Responsável - Executa a atividade',
                'A' => 'Aprovador - Aprova e é accountable pelo resultado',
                'C' => 'Consultado - Fornece input/conhecimento',
                'I' => 'Informado - Recebe informação sobre o resultado',
                '-' => 'Não envolvido'
            ];
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'funcao' => $funcao,
                'descricao' => $descricoes[$funcao] ?? 'Função não definida',
                'sop_id' => $sopId,
                'cargo' => $cargo
            ]);
            
        } catch (Exception $e) {
            Logger::error('Erro ao buscar função RACI', ['erro' => $e->getMessage(), 'sop_id' => $sopId, 'cargo' => $cargo]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno.']);
        }
        exit;
    }

    /**
     * Retorna função RACI padrão baseada no tipo de SOP e cargo
     */
    private function getFuncaoRaciPadrao(string $sopId, string $cargo): string
    {
        // Mapear funções RACI baseado no padrão de SOP
        $raciPadroes = [
            // SOPs Comerciais
            'SOP-TI-COM-001' => [
                'Diretor TI' => 'A', 'Gerente Ops' => 'R', 'Analista N2' => 'C', 
                'Suporte N1' => 'I', 'Financeiro' => '-', 'Jurídico' => '-', 'RH' => '-'
            ],
            // SOPs Onboarding
            'SOP-TI-ONB-001' => [
                'Diretor TI' => 'A', 'Gerente Ops' => 'R', 'Analista N2' => 'R', 
                'Suporte N1' => 'C', 'Financeiro' => 'I', 'Jurídico' => 'C', 'RH' => '-'
            ],
            // SOPs Operacionais
            'SOP-TI-OPS-001' => [
                'Diretor TI' => 'I', 'Gerente Ops' => 'A', 'Analista N2' => 'R', 
                'Suporte N1' => 'R', 'Financeiro' => '-', 'Jurídico' => '-', 'RH' => '-'
            ],
            'SOP-TI-OPS-002' => [
                'Diretor TI' => 'I', 'Gerente Ops' => 'A', 'Analista N2' => 'R', 
                'Suporte N1' => 'C', 'Financeiro' => '-', 'Jurídico' => 'I', 'RH' => '-'
            ],
            // SOPs Financeiros
            'SOP-TI-FIN-001' => [
                'Diretor TI' => 'I', 'Gerente Ops' => 'C', 'Analista N2' => '-', 
                'Suporte N1' => '-', 'Financeiro' => 'R', 'Jurídico' => 'I', 'RH' => '-'
            ],
            // SOPs Jurídicos
            'SOP-TI-JUR-001' => [
                'Diretor TI' => 'A', 'Gerente Ops' => 'C', 'Analista N2' => 'I', 
                'Suporte N1' => 'I', 'Financeiro' => '-', 'Jurídico' => 'R', 'RH' => 'C'
            ],
        ];
        
        // Para SOPs de outros setores, usar padrão genérico baseado no departamento
        if (!isset($raciPadroes[$sopId])) {
            if (strpos($sopId, '-COM-') !== false) {
                return in_array($cargo, ['Diretor TI', 'Gerente Comercial']) ? 'A' : 
                       (in_array($cargo, ['Vendedor', 'Analista Comercial']) ? 'R' : 'I');
            } elseif (strpos($sopId, '-FIN-') !== false) {
                return in_array($cargo, ['Diretor Financeiro', 'Controller']) ? 'A' :
                       (in_array($cargo, ['Analista Financeiro', 'Contador']) ? 'R' : 'I');
            }
            return 'I'; // Padrão para SOPs não mapeados
        }
        
        return $raciPadroes[$sopId][$cargo] ?? 'I';
    }

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
     * Solicita ajuste da IA em seção específica — F-05 Implementation
     */
    public function ajustar(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $sopId = (int) ($_POST['sop_id'] ?? 0);
        $instrucao = trim($_POST['instrucao'] ?? '');
        $secoesAjustar = $_POST['secoes_a_ajustar'] ?? [];

        if (!$sopId || empty($instrucao)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'SOP ID e instruções são obrigatórios.']);
            exit;
        }

        // Verificar permissão
        $sop = Sop::buscarPorId($sopId);
        if (!$sop || !Auth::podeAcessarEmpresa($sop['empresa_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'SOP não encontrado ou sem permissão.']);
            exit;
        }

        $conteudoAtual = json_decode($sop['conteudo_completo'], true);
        if (!$conteudoAtual) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Conteúdo do SOP não encontrado.']);
            exit;
        }

        // Montar prompt para ajuste específico
        $promptAjuste = $this->buildPromptAjuste($conteudoAtual, $instrucao, $secoesAjustar);
        
        // Chamar IA
        $resultado = ApiHelper::chamarAnalise($promptAjuste, true);

        if ($resultado['sucesso'] && is_array($resultado['conteudo'])) {
            // Mesclar seções atualizadas
            $conteudoNovo = $this->mesclarConteudo($conteudoAtual, $resultado['conteudo'], $secoesAjustar);
            
            // Incrementar versão
            $versaoNova = Sop::incrementarVersao($sop['versao']);
            
            // Salvar histórico
            Sop::salvarHistorico($sopId, $sop['versao'], $versaoNova, $conteudoAtual, $instrucao, Auth::usuarioId());
            
            // Atualizar SOP
            $sucesso = Sop::atualizar($sopId, [
                'conteudo_completo' => $conteudoNovo,
                'versao' => $versaoNova,
                'motivo_alteracao' => $instrucao,
            ]);

            if ($sucesso) {
                Logger::acao('SOP ajustado via IA', ['sop_id' => $sopId, 'versao_nova' => $versaoNova]);
                header('Content-Type: application/json');
                echo json_encode([
                    'sucesso' => true, 
                    'mensagem' => "SOP atualizado para versão {$versaoNova}. Recarregando...",
                    'versao' => $versaoNova
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Erro ao salvar ajustes.']);
            }
        } else {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => $resultado['erro'] ?? 'IA não disponível para ajustes.']);
        }
        exit;
    }

    /**
     * Monta prompt para ajuste específico
     */
    private function buildPromptAjuste(array $conteudoAtual, string $instrucao, array $secoes): string
    {
        $conteudoJson = json_encode($conteudoAtual, JSON_UNESCAPED_UNICODE);
        $secoesStr = implode(', ', $secoes);

        return "SOP atual (JSON): {$conteudoJson}

INSTRUÇÃO DE AJUSTE: {$instrucao}

Seções a ajustar: {$secoesStr}

Regere APENAS as seções indicadas mantendo a mesma estrutura JSON. As demais seções devem permanecer EXATAMENTE iguais.
Responda APENAS com JSON válido contendo as seções atualizadas.";
    }

    /**
     * Mescla conteúdo atualizado com o existente
     */
    private function mesclarConteudo(array $original, array $atualizado, array $secoes): array
    {
        foreach ($secoes as $secao) {
            if (isset($atualizado[$secao])) {
                $original[$secao] = $atualizado[$secao];
            }
        }
        return $original;
    }
    public function kpis(): void
    {
        Auth::proteger();
        
        $empresaId = Auth::garantirEmpresa();

        // Buscar KPIs de todos os SOPs aprovados da empresa
        $kpisReais = Database::query(
            "SELECT k.*, s.titulo as sop_titulo, s.sop_codigo 
             FROM sop_kpis k 
             JOIN sops s ON k.sop_id = s.id 
             WHERE k.empresa_id = :empresa_id AND k.ativo = 1 AND s.status = 'ativo'
             ORDER BY k.zona_atual DESC, k.nome",
            ['empresa_id' => $empresaId]
        );

        // Se não há KPIs reais, mostrar empty state
        if (empty($kpisReais)) {
            $dados = ['kpis' => []];
        } else {
            $dados = ['kpis' => $this->formatarKpisParaView($kpisReais)];
        }

        require VIEW_PATH . '/sop/kpis.php';
    }

    /**
     * Registra valor de KPI via AJAX — F-06 Enhanced
     */
    public function registrarKpi(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $kpiId = (int) ($_POST['kpi_id'] ?? 0);
        $valorAtual = trim($_POST['valor'] ?? '');

        if (!$kpiId || empty($valorAtual)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'KPI ID e valor são obrigatórios.']);
            exit;
        }

        // Buscar KPI
        $kpi = Database::queryOne("SELECT * FROM sop_kpis WHERE id = :id AND empresa_id = :empresa_id", [
            'id' => $kpiId,
            'empresa_id' => Auth::empresa()
        ]);

        if (!$kpi) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'KPI não encontrado.']);
            exit;
        }

        // Determinar zona baseada no valor
        $zonaAtual = $this->determinarZonaKPI($valorAtual, $kpi);

        // Atualizar KPI
        $sucesso = Sop::atualizarKpi($kpiId, $valorAtual, $zonaAtual);

        if ($sucesso) {
            Logger::acao('KPI valor registrado', [
                'kpi_id' => $kpiId,
                'kpi_nome' => $kpi['nome'],
                'valor' => $valorAtual,
                'zona' => $zonaAtual
            ]);

            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Valor registrado!',
                'zona' => $zonaAtual
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao atualizar KPI.']);
        }
        exit;
    }

    /**
     * Determinar zona do KPI baseada no valor
     */
    private function determinarZonaKPI(string $valor, array $kpi): string
    {
        // Lógica simplificada - pode ser expandida para diferentes tipos de KPI
        $valorNumerico = (float) preg_replace('/[^\d.]/', '', $valor);
        
        // Para KPIs de percentual
        if (strpos($valor, '%') !== false) {
            if ($valorNumerico >= 95) return 'verde';
            if ($valorNumerico >= 80) return 'amarela';
            return 'vermelha';
        }
        
        // Para KPIs de tempo (assumindo que menor é melhor)
        if (strpos($valor, 'min') !== false || strpos($valor, 'h') !== false) {
            if ($valorNumerico <= 30) return 'verde';
            if ($valorNumerico <= 120) return 'amarela';
            return 'vermelha';
        }
        
        // Padrão: comparar com meta verde/amarela/vermelha (implementação básica)
        return 'amarela'; // Padrão para valores não classificados automaticamente
    }

    /**
     * Exportar SOP como PDF — F-06 Implementation
     */
    public function exportarPdf(): void
    {
        Auth::proteger();

        $sopId = (int) ($_GET['id'] ?? 0);
        if (!$sopId) {
            Flash::set('erro', 'SOP não encontrado.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }

        // Buscar SOP
        $sop = Sop::buscarPorId($sopId);
        if (!$sop || !Auth::podeAcessarEmpresa($sop['empresa_id'])) {
            Flash::set('erro', 'SOP não encontrado ou sem permissão.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }

        // Gerar conteúdo HTML para PDF
        $conteudoCompleto = json_decode($sop['conteudo_completo'], true);
        $empresa = Empresa::buscarPorId($sop['empresa_id']);
        $kpis = Sop::buscarKpis($sopId);
        
        $sopFormatado = $this->formatarSopParaViewCompleta($sop, $conteudoCompleto, $empresa, $kpis);

        // HTML básico para PDF (sem CSS complexo)
        $html = $this->gerarHtmlParaPdf($sopFormatado);

        // Headers para download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="SOP-' . $sop['sop_codigo'] . '.pdf"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        // Gerar PDF simples (texto puro convertido)
        echo $this->gerarPdfSimples($html, $sopFormatado['titulo']);
        exit;
    }

    /**
     * Exportar todos os SOPs como ZIP — F-06 Implementation
     */
    public function exportarTodosZip(): void
    {
        Auth::proteger();

        $empresaId = Auth::garantirEmpresa();

        // Buscar todos os SOPs aprovados
        $sops = Database::query(
            "SELECT * FROM sops WHERE empresa_id = :empresa_id AND status = 'ativo' ORDER BY sop_codigo",
            ['empresa_id' => $empresaId]
        );

        if (empty($sops)) {
            Flash::set('erro', 'Nenhum SOP aprovado encontrado para exportar.');
            header('Location: ' . APP_URL . '/manual-operacional');
            exit;
        }

        // Headers para download
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="SOPs-Manual-Operacional.zip"');
        header('Cache-Control: private, max-age=0, must-revalidate');

        // Criar ZIP em memória (implementação simplificada)
        $this->gerarZipSOPs($sops);
        exit;
    }

    /**
     * Gerar HTML limpo para PDF
     */
    private function gerarHtmlParaPdf(array $sop): string
    {
        $html = "
        <html>
        <body style='font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4;'>
            <h1 style='color: #333; border-bottom: 2px solid #0066cc; padding-bottom: 10px;'>
                {$sop['sop_codigo']} - {$sop['titulo']}
            </h1>
            
            <p><strong>Empresa:</strong> {$sop['empresa']}</p>
            <p><strong>Setor:</strong> {$sop['setor']}</p>
            <p><strong>Versão:</strong> {$sop['versao']}</p>
            <p><strong>Norma:</strong> {$sop['norma']}</p>
            
            <h2>Objetivo</h2>
            <p>{$sop['objetivo']}</p>
            
            <h2>Escopo</h2>
            <p><strong>Aplica-se a:</strong> {$sop['escopo_aplica']}</p>
            <p><strong>Não se aplica a:</strong> {$sop['escopo_nao_aplica']}</p>
        ";

        // Subtópicos
        foreach ($sop['subtopicos_completos'] as $subtopico) {
            $html .= "<h2>Subtópico {$subtopico['letra']}: {$subtopico['nome']}</h2>";
            $html .= "<p>{$subtopico['descricao']}</p>";
            
            $html .= "<h3>Procedimentos:</h3><ol>";
            foreach ($subtopico['procedimentos'] as $proc) {
                $html .= "<li>{$proc['acao']} <em>({$proc['responsavel']} - {$proc['prazo']})</em></li>";
            }
            $html .= "</ol>";
        }

        $html .= "</body></html>";
        return $html;
    }

    /**
     * Gerar PDF simples (conversão de HTML)
     */
    private function gerarPdfSimples(string $html, string $titulo): string
    {
        // Implementação simplificada - retorna HTML como "PDF"
        // Em produção, usar biblioteca como TCPDF ou DOMPDF
        return $html;
    }

    /**
     * Gerar ZIP com múltiplos SOPs
     */
    private function gerarZipSOPs(array $sops): void
    {
        // Implementação simplificada - retorna lista de SOPs como texto
        // Em produção, usar ZipArchive do PHP
        echo "Manual Operacional - SOPs:\n\n";
        foreach ($sops as $sop) {
            echo "- {$sop['sop_codigo']}: {$sop['titulo']}\n";
        }
    }

    /**
     * Formatar KPIs do banco para a view
     */
    private function formatarKpisParaView(array $kpis): array
    {
        return array_map(function($kpi) {
            return [
                'kpi' => $kpi['nome'],
                'sop' => $kpi['sop_codigo'] ?? 'N/A',
                'atual' => $kpi['valor_atual'] ?? 'Não medido',
                'meta_verde' => $kpi['meta_verde'],
                'meta_amarela' => $kpi['meta_amarela'],
                'meta_vermelha' => $kpi['meta_vermelha'],
                'zona' => $kpi['zona_atual'] ?? 'verde',
                'frequencia' => 'Conforme SOP',
                'responsavel' => $kpi['responsavel'] ?? 'A definir',
            ];
        }, $kpis);
    }

    /**
     * Formatar KPIs do banco para a view
     */
    private function criarSopNoBanco(string $sopCodigo, string $sopNome, int $empresaId, ?int $diagnosticoId, array $conteudoIA): int|false
    {
        // Detectar se é nova estrutura padrão-ouro (15 seções)
        $novaEstrutura = isset($conteudoIA['cabecalho']);
        
        if ($novaEstrutura) {
            // Nova estrutura padrão-ouro
            $titulo = $conteudoIA['cabecalho']['nome'] ?? $sopNome;
            $versao = $conteudoIA['cabecalho']['versao'] ?? '1.0';
            $conteudoResumo = 'SOP gerado via IA com estrutura padrão-ouro (15 seções completas)';
        } else {
            // Estrutura antiga (compatibilidade)
            $titulo = $sopNome;
            $versao = '1.0';
            $conteudoResumo = 'SOP gerado via IA com 13 componentes completos';
        }
        
        return Sop::criar([
            'empresa_id'        => $empresaId,
            'diagnostico_id'    => $diagnosticoId,
            'sop_codigo'        => $sopCodigo,
            'titulo'            => $titulo,
            'departamento'      => $this->getDepartamentoPorId($sopCodigo),
            'conteudo'          => $conteudoResumo,
            'conteudo_completo' => $conteudoIA,
            'versao'            => $versao,
            'status'            => 'rascunho',
            'gerado_por_ia'     => 1,
        ]);
    }

    /**
     * Cria SOP básico quando IA falha (com nova estrutura padrão-ouro)
     */
    private function criarSopBasico(string $sopCodigo, string $sopNome, int $empresaId, ?int $diagnosticoId): int|false
    {
        // Criar estrutura básica padrão-ouro quando IA falha
        $basicData = [
            'cabecalho' => [
                'codigo' => $sopCodigo,
                'nome' => $sopNome,
                'versao' => '1.0',
                'data_criacao' => date('Y-m-d'),
                'data_revisao' => date('Y-m-d', strtotime('+1 year')),
                'dono_processo' => 'A definir',
                'aprovador' => 'A definir'
            ],
            'objetivo' => 'Definir procedimento específico para ' . $sopNome,
            'escopo' => [
                'cobre' => 'A ser detalhado conforme necessidades da empresa',
                'nao_cobre' => 'Outros processos não relacionados a este procedimento'
            ],
            'glossario' => [
                ['termo' => 'Procedimento', 'definicao' => 'Sequência de passos para executar uma tarefa']
            ],
            'raci' => [
                'responsavel' => 'A definir',
                'aprovador' => 'A definir',
                'consultado' => 'A definir',
                'informado' => 'A definir'
            ],
            'pre_requisitos' => [
                'Definir acessos necessários',
                'Identificar informações obrigatórias',
                'Configurar ferramentas necessárias'
            ],
            'passo_a_passo' => [
                [
                    'passo' => 1,
                    'acao' => 'Definir passos específicos do procedimento',
                    'sistema' => 'A definir',
                    'responsavel' => 'A definir',
                    'tempo_estimado' => 'A definir',
                    'criterio_conclusao' => 'A definir critério específico'
                ]
            ],
            'pontos_controle' => [
                [
                    'checkpoint' => 'Verificação inicial',
                    'criterio_aceite' => 'A definir critério específico',
                    'acao_se_falhar' => 'A definir ação corretiva'
                ]
            ],
            'tratamento_excecoes' => [
                [
                    'cenario' => 'Erro genérico no processo',
                    'solucao' => 'A definir solução específica',
                    'escalar_para' => 'Supervisor'
                ]
            ],
            'ferramentas_sistemas' => [
                'Definir sistemas específicos utilizados'
            ],
            'kpis_processo' => [
                'tempo_medio_esperado' => 'A definir',
                'taxa_erro_aceitavel' => 'A definir',
                'sla_interno' => 'A definir',
                'meta_qualidade' => 'A definir'
            ],
            'riscos_nao_conformidades' => [
                [
                    'risco' => 'Não execução do procedimento',
                    'impacto' => 'A definir impacto no negócio',
                    'prevencao' => 'A definir medidas preventivas'
                ]
            ],
            'melhorias_recomendadas' => [
                'Detalhar procedimento específico baseado na realidade da empresa',
                'Definir métricas e controles adequados',
                'Estabelecer ferramentas e responsáveis específicos'
            ],
            'anexos' => [
                'checklist_rapido' => [
                    '☐ Verificar pré-requisitos',
                    '☐ Executar procedimento',
                    '☐ Validar resultado'
                ],
                'fluxograma_textual' => [
                    '1. Início → Verificar pré-requisitos',
                    '2. Executar → Seguir procedimento',
                    '3. Finalizar → Documentar resultado'
                ]
            ],
            'historico_revisoes' => [
                [
                    'versao' => '1.0',
                    'data' => date('Y-m-d'),
                    'mudanca' => 'Criação inicial (IA indisponível - estrutura básica)',
                    'responsavel' => 'Sistema O Consultor'
                ]
            ]
        ];
        
        return $this->criarSopNoBanco($sopCodigo, $sopNome, $empresaId, $diagnosticoId, $basicData);
    }

    /**
     * Formata SOP do banco para a view (compatível com nova estrutura de 15 seções)
     */
    private function formatarSopParaView(array $sop, array $conteudo): array
    {
        $empresa = Empresa::buscarPorId($sop['empresa_id']);
        
        // Compatibilidade com ambas as estruturas (antiga e nova padrão-ouro)
        $novaEstrutura = isset($conteudo['cabecalho']);
        
        if ($novaEstrutura) {
            // Nova estrutura padrão-ouro (15 seções)
            return [
                'id' => $sop['sop_codigo'] ?? $sop['id'],
                'nome' => $conteudo['cabecalho']['nome'] ?? $sop['titulo'],
                'versao' => $conteudo['cabecalho']['versao'] ?? $sop['versao'],
                'empresa' => $empresa['nome'] ?? 'Empresa',
                'setor' => $empresa['segmento'] ?? 'Tecnologia',
                'norma' => ApiHelper::getNormasPorSetor($empresa['segmento'] ?? 'Tecnologia'),
                
                // Mapeamento da nova estrutura para view existente
                'objetivo' => $conteudo['objetivo'] ?? '',
                'escopo_aplica' => $conteudo['escopo']['cobre'] ?? '',
                'escopo_nao_aplica' => $conteudo['escopo']['nao_cobre'] ?? '',
                'glossario' => $conteudo['glossario'] ?? [],
                'raci' => $conteudo['raci'] ?? [],
                'subtopicos' => $this->extrairSubtopicosDaNovaEstrutura($conteudo),
                'responsaveis' => $this->extrairResponsaveisDaNovaEstrutura($conteudo),
                'prerequisitos' => $conteudo['pre_requisitos'] ?? [],
                'ferramentas' => $conteudo['ferramentas_sistemas'] ?? [],
                'procedimento_subtopico_1' => $conteudo['passo_a_passo'] ?? [],
                'pontos_controle' => $conteudo['pontos_controle'] ?? [],
                'tratamento_excecoes' => $conteudo['tratamento_excecoes'] ?? [],
                'checklist' => $conteudo['anexos']['checklist_rapido'] ?? [],
                'fluxograma_textual' => $conteudo['anexos']['fluxograma_textual'] ?? [],
                'evidencias' => $this->extrairEvidenciasDaNovaEstrutura($conteudo),
                'relatorios' => $this->extrairRelatoriosDaNovaEstrutura($conteudo),
                'kpis' => $this->extrairKpisDaNovaEstrutura($conteudo),
                'riscos' => $conteudo['riscos_nao_conformidades'] ?? [],
                'melhorias_recomendadas' => $conteudo['melhorias_recomendadas'] ?? [],
                'contencao_n1' => $this->extrairContencaoN1($conteudo),
                'contencao_n2' => $this->extrairContencaoN2($conteudo),
                'contencao_n3' => $this->extrairContencaoN3($conteudo),
                'historico_revisoes' => $conteudo['historico_revisoes'] ?? [],
                
                // Campos específicos da nova estrutura
                'cabecalho' => $conteudo['cabecalho'] ?? [],
                'nova_estrutura' => true
            ];
        } else {
            // Estrutura antiga (compatibilidade)
            return [
                'id' => $sop['sop_codigo'] ?? $sop['id'],
                'nome' => $sop['titulo'],
                'versao' => $sop['versao'],
                'empresa' => $empresa['nome'] ?? 'Empresa',
                'setor' => $empresa['segmento'] ?? 'Tecnologia',
                'norma' => ApiHelper::getNormasPorSetor($empresa['segmento'] ?? 'Tecnologia'),
                'objetivo' => $conteudo['objetivo'] ?? '',
                'escopo_aplica' => $conteudo['escopo']['aplica_se'] ?? '',
                'escopo_nao_aplica' => $conteudo['escopo']['nao_aplica'] ?? '',
                'subtopicos' => $conteudo['subtopicos'] ?? [],
                'responsaveis' => $conteudo['responsaveis'] ?? [],
                'prerequisitos' => $conteudo['prerequisitos'] ?? [],
                'ferramentas' => $conteudo['ferramentas'] ?? [],
                'procedimento_subtopico_1' => $conteudo['procedimentos'][0]['passos'] ?? [],
                'checklist' => $conteudo['checklist'] ?? [],
                'evidencias' => $conteudo['evidencias'] ?? [],
                'relatorios' => $conteudo['relatorios'] ?? [],
                'kpis' => $conteudo['kpis'] ?? [],
                'contencao_n1' => $conteudo['contencao']['n1'] ?? [],
                'contencao_n2' => $conteudo['contencao']['n2'] ?? [],
                'contencao_n3' => $conteudo['contencao']['n3'] ?? [],
                'nova_estrutura' => false
            ];
        }
    }
    
    /**
     * Extrai subtópicos da nova estrutura para compatibilidade com view
     */
    private function extrairSubtopicosDaNovaEstrutura(array $conteudo): array
    {
        // Na nova estrutura, os subtópicos estão implícitos no passo_a_passo
        // Vamos criar subtópicos baseados na estrutura de passos
        if (empty($conteudo['passo_a_passo'])) {
            return [['nome' => 'Procedimento Principal', 'descricao' => 'Executar conforme passo a passo']];
        }
        
        // Agrupar passos em subtópicos lógicos (por exemplo, a cada 5-7 passos)
        $totalPassos = count($conteudo['passo_a_passo']);
        $subtopicos = [];
        
        if ($totalPassos <= 7) {
            $subtopicos[] = ['nome' => 'Procedimento Completo', 'descricao' => 'Executar todos os passos sequencialmente'];
        } else {
            $metade = ceil($totalPassos / 2);
            $subtopicos[] = ['nome' => 'Primeira Fase', 'descricao' => "Passos 1 a {$metade}"];
            $subtopicos[] = ['nome' => 'Segunda Fase', 'descricao' => "Passos " . ($metade + 1) . " a {$totalPassos}"];
        }
        
        return $subtopicos;
    }
    
    /**
     * Extrai responsáveis da nova estrutura RACI
     */
    private function extrairResponsaveisDaNovaEstrutura(array $conteudo): array
    {
        if (empty($conteudo['raci'])) {
            return [['papel' => 'Executor', 'cargo' => 'A definir']];
        }
        
        $responsaveis = [];
        $raci = $conteudo['raci'];
        
        if (!empty($raci['responsavel'])) {
            $responsaveis[] = ['papel' => 'Responsável (R)', 'cargo' => $raci['responsavel']];
        }
        if (!empty($raci['aprovador'])) {
            $responsaveis[] = ['papel' => 'Aprovador (A)', 'cargo' => $raci['aprovador']];
        }
        if (!empty($raci['consultado'])) {
            $responsaveis[] = ['papel' => 'Consultado (C)', 'cargo' => $raci['consultado']];
        }
        if (!empty($raci['informado'])) {
            $responsaveis[] = ['papel' => 'Informado (I)', 'cargo' => $raci['informado']];
        }
        
        return !empty($responsaveis) ? $responsaveis : [['papel' => 'Executor', 'cargo' => 'A definir']];
    }
    
    /**
     * Converte evidências implícitas da nova estrutura
     */
    private function extrairEvidenciasDaNovaEstrutura(array $conteudo): array
    {
        $evidencias = [];
        
        // Extrair evidências dos checkpoints
        if (!empty($conteudo['pontos_controle'])) {
            foreach ($conteudo['pontos_controle'] as $checkpoint) {
                $evidencias[] = "Evidência de " . $checkpoint['checkpoint'];
            }
        }
        
        // Adicionar evidências padrão
        $evidencias[] = "Registro de execução dos passos";
        $evidencias[] = "Validação dos critérios de conclusão";
        $evidencias[] = "Documentação de exceções tratadas";
        
        return $evidencias;
    }
    
    /**
     * Converte relatórios implícitos da nova estrutura
     */
    private function extrairRelatoriosDaNovaEstrutura(array $conteudo): array
    {
        $relatorios = [];
        
        // Baseado nos KPIs definidos
        if (!empty($conteudo['kpis_processo'])) {
            $kpis = $conteudo['kpis_processo'];
            if (!empty($kpis['tempo_medio_esperado'])) {
                $relatorios[] = ['oque' => 'Tempo de execução', 'para_quem' => 'Gestor do processo', 'frequencia' => 'Semanal', 'canal' => 'E-mail'];
            }
            if (!empty($kpis['taxa_erro_aceitavel'])) {
                $relatorios[] = ['oque' => 'Taxa de erros', 'para_quem' => 'Coordenação', 'frequencia' => 'Mensal', 'canal' => 'Dashboard'];
            }
        }
        
        // Relatórios padrão
        if (empty($relatorios)) {
            $relatorios[] = ['oque' => 'Status de execução', 'para_quem' => 'Responsável do processo', 'frequencia' => 'Conforme execução', 'canal' => 'Sistema'];
        }
        
        return $relatorios;
    }
    
    /**
     * Converte KPIs da nova estrutura
     */
    private function extrairKpisDaNovaEstrutura(array $conteudo): array
    {
        if (empty($conteudo['kpis_processo'])) {
            return [['kpi' => 'Taxa de sucesso', 'verde' => '95%', 'amarela' => '80%', 'vermelha' => '<80%', 'acao_vermelha' => 'Revisar processo']];
        }
        
        $kpisProcesso = $conteudo['kpis_processo'];
        $kpis = [];
        
        if (!empty($kpisProcesso['tempo_medio_esperado'])) {
            $tempo = $kpisProcesso['tempo_medio_esperado'];
            $kpis[] = [
                'kpi' => 'Tempo de execução',
                'verde' => "≤ {$tempo}",
                'amarela' => "≤ " . $this->calcularAumento($tempo, 20),
                'vermelha' => "> " . $this->calcularAumento($tempo, 50),
                'acao_vermelha' => 'Analisar gargalos e otimizar processo'
            ];
        }
        
        if (!empty($kpisProcesso['taxa_erro_aceitavel'])) {
            $taxa = $kpisProcesso['taxa_erro_aceitavel'];
            $kpis[] = [
                'kpi' => 'Taxa de erro',
                'verde' => "≤ {$taxa}",
                'amarela' => "≤ " . $this->calcularAumento($taxa, 50),
                'vermelha' => "> " . $this->calcularAumento($taxa, 100),
                'acao_vermelha' => 'Revisão urgente do procedimento'
            ];
        }
        
        // KPI padrão se não houver específicos
        if (empty($kpis)) {
            $kpis[] = [
                'kpi' => 'Conformidade do processo',
                'verde' => '≥95%',
                'amarela' => '80-94%',
                'vermelha' => '<80%',
                'acao_vermelha' => 'Retreinamento da equipe'
            ];
        }
        
        return $kpis;
    }
    
    /**
     * Calcula aumento percentual para KPIs
     */
    private function calcularAumento(string $valor, int $percentual): string
    {
        // Extrair número do valor (ex: "30 minutos" -> 30)
        preg_match('/(\d+(?:\.\d+)?)/', $valor, $matches);
        if (!empty($matches[1])) {
            $numero = floatval($matches[1]);
            $novoNumero = $numero * (1 + $percentual / 100);
            return str_replace($matches[1], number_format($novoNumero, 0), $valor);
        }
        return $valor;
    }
    
    /**
     * Extrai informações de contenção N1 (compatibilidade)
     */
    private function extrairContencaoN1(array $conteudo): array
    {
        // Na nova estrutura não há contenção separada, mas podemos inferir das exceções
        if (!empty($conteudo['tratamento_excecoes'])) {
            $primeiraExcecao = $conteudo['tratamento_excecoes'][0];
            return [
                'situacao' => $primeiraExcecao['cenario'] ?? 'Erro operacional',
                'acao' => $primeiraExcecao['solucao'] ?? 'Seguir procedimento de correção',
                'quem' => $primeiraExcecao['escalar_para'] ?? 'Responsável direto',
                'escalar' => 'Supervisor imediato'
            ];
        }
        
        return [
            'situacao' => 'Erro operacional padrão',
            'acao' => 'Verificar procedimento e corrigir',
            'quem' => 'Executor do processo',
            'escalar' => 'Supervisor direto'
        ];
    }
    
    /**
     * Extrai informações de contenção N2 (compatibilidade)
     */
    private function extrairContencaoN2(array $conteudo): array
    {
        return [
            'situacao' => 'Falha recorrente ou crítica',
            'acao' => 'Revisar processo completo',
            'quem' => 'Gestor do departamento',
            'escalar' => 'Gerência executiva'
        ];
    }
    
    /**
     * Extrai informações de contenção N3 (compatibilidade)
     */
    private function extrairContencaoN3(array $conteudo): array
    {
        return [
            'situacao' => 'Falha sistêmica com impacto no cliente',
            'acao' => 'Interromper processo e ativar plano de contingência',
            'quem' => 'Diretor responsável',
            'escalar' => 'CEO/Presidência',
            'comunicacao' => 'Comunicação imediata aos stakeholders',
            'documentacao' => 'Relatório detalhado de causa raiz obrigatório'
        ];
    }

    /**
     * Extrai dados do diagnóstico para o prompt
     */
    private function extrairColaboradores(?array $diagnostico): string
    {
        if (!$diagnostico || empty($diagnostico['respostas'])) {
            return '10-25';
        }
        
        $respostas = json_decode($diagnostico['respostas'], true);
        $internos = (int) ($respostas['colaboradores_internos'] ?? 10);
        $externos = (int) ($respostas['colaboradores_externos'] ?? 5);
        $total = $internos + $externos;
        
        if ($total <= 10) return '1-10';
        if ($total <= 25) return '11-25';  
        if ($total <= 50) return '26-50';
        if ($total <= 100) return '51-100';
        return '100+';
    }
    
    private function extrairFaturamento(?array $diagnostico): string
    {
        if (!$diagnostico || empty($diagnostico['respostas'])) {
            return 'R$ 100-500 mil';
        }
        
        $respostas = json_decode($diagnostico['respostas'], true);
        return isset($respostas['faturamento_mensal']) ? $respostas['faturamento_mensal'] : 'R$ 100-500 mil';
    }

    private function extrairDepartamentos(?array $diagnostico): string
    {
        if (!$diagnostico || empty($diagnostico['respostas'])) {
            return 'Comercial, TI, Operações, Financeiro, RH';
        }
        
        $respostas = json_decode($diagnostico['respostas'], true);
        if (!$respostas) {
            return 'Comercial, TI, Operações, Financeiro, RH';
        }
        
        // CORREÇÃO: Extrair departamentos de TODAS as perguntas do diagnóstico
        $departamentosEncontrados = [];
        
        // Buscar em diferentes campos do diagnóstico que podem conter departamentos
        $camposParaBuscar = [
            'departamentos', 'setores', 'areas_empresa', 'estrutura_empresa',
            'departamentos_empresa', 'areas_atuacao', 'estrutura_organizacional'
        ];
        
        foreach ($camposParaBuscar as $campo) {
            if (isset($respostas[$campo])) {
                if (is_array($respostas[$campo])) {
                    $departamentosEncontrados = array_merge($departamentosEncontrados, $respostas[$campo]);
                } else {
                    // Se for string, dividir por vírgulas ou quebras de linha
                    $depsTexto = str_replace([';', '\n', '\r'], ',', $respostas[$campo]);
                    $depsArray = array_map('trim', explode(',', $depsTexto));
                    $departamentosEncontrados = array_merge($departamentosEncontrados, $depsArray);
                }
            }
        }
        
        // Buscar também em perguntas que podem conter informação de departamentos
        $perguntasRelevantes = [
            'empresa_colaboradores', 'estrutura_atual', 'principais_areas',
            'equipe_atual', 'areas_responsabilidade', 'organograma'
        ];
        
        foreach ($perguntasRelevantes as $pergunta) {
            if (isset($respostas[$pergunta]) && is_string($respostas[$pergunta])) {
                $texto = strtolower($respostas[$pergunta]);
                
                // Detectar departamentos mencionados no texto
                $padroesDepto = [
                    'comercial' => 'Comercial',
                    'vendas' => 'Comercial',
                    'ti' => 'TI',
                    'tecnologia' => 'TI',
                    'operac' => 'Operações',
                    'produc' => 'Operações',
                    'financeiro' => 'Financeiro',
                    'contab' => 'Financeiro',
                    'rh' => 'RH',
                    'recursos humanos' => 'RH',
                    'pessoas' => 'RH',
                    'marketing' => 'Marketing',
                    'juridico' => 'Jurídico',
                    'legal' => 'Jurídico',
                    'administrativo' => 'Administrativo',
                    'compras' => 'Compras',
                    'logistica' => 'Logística',
                    'qualidade' => 'Qualidade'
                ];
                
                foreach ($padroesDepto as $padrao => $departamento) {
                    if (strpos($texto, $padrao) !== false) {
                        $departamentosEncontrados[] = $departamento;
                    }
                }
            }
        }
        
        // Limpar e padronizar departamentos
        $departamentosEncontrados = array_filter($departamentosEncontrados);
        $departamentosEncontrados = array_unique($departamentosEncontrados);
        $departamentosEncontrados = array_map('trim', $departamentosEncontrados);
        $departamentosEncontrados = array_filter($departamentosEncontrados, function($dept) {
            return !empty($dept) && strlen($dept) > 1;
        });
        
        // Se não encontrou nenhum departamento específico, usar padrão baseado no segmento da empresa
        if (empty($departamentosEncontrados)) {
            $segmentoEmpresa = isset($respostas['segmento']) ? $respostas['segmento'] : 'Tecnologia';
            
            switch(strtolower($segmentoEmpresa)) {
                case 'tecnologia':
                    return 'Comercial, TI, Operações, Financeiro, RH';
                case 'saude':
                case 'saúde':
                    return 'Atendimento, Clínico, Administrativo, Financeiro, RH';
                case 'educacao':
                case 'educação':
                    return 'Pedagógico, Administrativo, Financeiro, Coordenação, RH';
                case 'varejo':
                    return 'Comercial, Operações, Estoque, Financeiro, RH';
                case 'industria':
                case 'indústria':
                    return 'Produção, Comercial, Qualidade, Financeiro, RH, Compras';
                case 'servicos':
                case 'serviços':
                    return 'Comercial, Operações, Atendimento, Financeiro, RH';
                default:
                    return 'Comercial, Operações, Administrativo, Financeiro, RH';
            }
        }
        
        $departamentosFinais = implode(', ', $departamentosEncontrados);
        
        Logger::info('Departamentos extraídos do diagnóstico', [
            'departamentos_encontrados' => $departamentosEncontrados,
            'total_departamentos' => count($departamentosEncontrados),
            'string_final' => $departamentosFinais
        ]);
        
        return $departamentosFinais;
    }

    private function extrairFerramentas(?array $diagnostico): string
    {
        if (!$diagnostico || empty($diagnostico['respostas'])) {
            return 'E-mail, WhatsApp, Excel';
        }
        
        $respostas = json_decode($diagnostico['respostas'], true);
        return isset($respostas['ferramentas']) ? $respostas['ferramentas'] : 'E-mail, WhatsApp, Excel';
    }

    private function extrairProblemas(?array $diagnostico): string
    {
        if (!$diagnostico || empty($diagnostico['respostas'])) {
            return 'Processos não documentados, falta de padronização';
        }
        
        $respostas = json_decode($diagnostico['respostas'], true);
        return isset($respostas['pontos_melhoria']) ? $respostas['pontos_melhoria'] : 'Processos não documentados, falta de padronização';
    }
    
    private function extrairObjetivos(?array $diagnostico): string
    {
        if (!$diagnostico || empty($diagnostico['respostas'])) {
            return 'Crescer com processos organizados e estruturados';
        }
        
        $respostas = json_decode($diagnostico['respostas'], true);
        return isset($respostas['objetivo_12_meses']) ? $respostas['objetivo_12_meses'] : 'Crescer com processos organizados e estruturados';
    }

    /**
     * Retorna departamentos com SOPs baseados no setor da empresa
     */
    private function getDepartamentosPorSetor(string $setor, int $empresaId): array
    {
        Logger::info('Carregando departamentos por setor', ['setor' => $setor, 'empresa_id' => $empresaId]);
        
        // Buscar SOPs existentes no banco (padrão do sistema)
        $sopsExistentes = Sop::buscarPorEmpresa($empresaId);
        $sopsMap = [];
        foreach ($sopsExistentes as $sop) {
                $status = 'nao_gerado';
                switch($sop['status']) {
                    case 'ativo':
                        $status = 'aprovado';
                        break;
                    case 'rascunho':
                        $status = 'gerado';
                        break;
                    default:
                        $status = 'nao_gerado';
                        break;
                }
                
            $sopsMap[$sop['sop_codigo']] = [
                'id' => $sop['sop_codigo'],
                'nome' => $sop['titulo'],
                'status' => $status
            ];
        }

        // Templates por setor específico
        $templatesSOP = $this->getSOPsPorSetor($setor);
        Logger::info('Templates SOP carregados', ['setor' => $setor, 'templates' => count($templatesSOP)]);

        // Buscar SOPs customizados da empresa
        $sopsCustomizados = [];
        try {
            $sopsCustomizados = Database::query(
                "SELECT * FROM sops_customizados WHERE empresa_id = :empresa_id AND ativo = 1 ORDER BY departamento, nome",
                ['empresa_id' => $empresaId]
            );
        } catch (Exception $e) {
            // Tabela pode não existir ainda - continuar sem SOPs customizados
            Logger::warning('Tabela sops_customizados não encontrada', ['erro' => $e->getMessage()]);
        }

        // Aplicar status aos SOPs padrão do setor
        foreach ($templatesSOP as &$dept) {
            foreach ($dept['sops'] as &$sop) {
                if (isset($sopsMap[$sop['id']])) {
                    $sop['status'] = $sopsMap[$sop['id']]['status'];
                } else {
                    $sop['status'] = 'nao_gerado';
                }
            }
        }
        
        Logger::info('Departamentos processados', ['departamentos' => count($templatesSOP), 'dados' => $templatesSOP]);

        // Integrar SOPs customizados nos departamentos
        foreach ($sopsCustomizados as $sopCustomizado) {
            $departamento = $sopCustomizado['departamento'];
            $icone = $sopCustomizado['icone'] ?? '📋';
            
            // Procurar se departamento já existe
            $deptIndex = null;
            foreach ($templatesSOP as $index => $dept) {
                if (strtolower($dept['nome']) === strtolower($departamento)) {
                    $deptIndex = $index;
                    break;
                }
            }
            
            // Se departamento não existe, criar novo
            if ($deptIndex === null) {
                $templatesSOP[] = [
                    'nome' => $departamento,
                    'icone' => $this->converterIconeParaEmoji($sopCustomizado['icone'] ?? 'documento'),
                    'sops' => []
                ];
                $deptIndex = count($templatesSOP) - 1;
            }
            
            // Adicionar SOP customizado ao departamento
            $status = isset($sopsMap[$sopCustomizado['sop_codigo']]) 
                ? $sopsMap[$sopCustomizado['sop_codigo']]['status'] 
                : 'nao_gerado';
            
            $templatesSOP[$deptIndex]['sops'][] = [
                'id' => $sopCustomizado['sop_codigo'],
                'nome' => $sopCustomizado['nome'],
                'status' => $status,
                'customizado' => true,
                'descricao' => $sopCustomizado['descricao']
            ];
        }

        return $templatesSOP;
    }

    /**
     * Retorna SOPs específicos por setor da empresa
     */
    private function getSOPsPorSetor(string $setor): array
    {
        switch (strtolower($setor)) {
            case 'construção civil':
            case 'construcao civil':
                return $this->getSOPsConstrutoraCivil();
                
            case 'tecnologia':
            case 'ti':
            case 'software':
                return $this->getSOPsTecnologia();
                
            case 'saúde':
            case 'saude':
            case 'medicina':
                return $this->getSOPsSaude();
                
            case 'educação':
            case 'educacao':
            case 'ensino':
                return $this->getSOPsEducacao();
                
            case 'consultoria':
                return $this->getSOPsConsultoria();
                
            case 'varejo':
            case 'comercio':
                return $this->getSOPsVarejo();
                
            case 'industria':
            case 'industrial':
                return $this->getSOPsIndustrial();
                
            case 'alimenticio':
            case 'restaurante':
            case 'food':
                return $this->getSOPsAlimenticio();
                
            default:
                return $this->getSOPsGeral();
        }
    }

    /**
     * SOPs específicos para Construção Civil
     */
    private function getSOPsConstrutoraCivil(): array
    {
        return [
            [
                'nome' => 'Comercial',
                'icone' => '💼',
                'sops' => [
                    ['id' => 'SOP-CC-COM-001', 'nome' => 'Prospecção e visita técnica'],
                    ['id' => 'SOP-CC-COM-002', 'nome' => 'Orçamento e memorial descritivo'],
                    ['id' => 'SOP-CC-COM-003', 'nome' => 'Negociação e contrato de obra'],
                    ['id' => 'SOP-CC-COM-004', 'nome' => 'Aprovação de projetos e licenças'],
                ],
            ],
            [
                'nome' => 'Planejamento de Obra',
                'icone' => '📋',
                'sops' => [
                    ['id' => 'SOP-CC-PLAN-001', 'nome' => 'Cronograma executivo de obra'],
                    ['id' => 'SOP-CC-PLAN-002', 'nome' => 'Compra e logística de materiais'],
                    ['id' => 'SOP-CC-PLAN-003', 'nome' => 'Contratação de mão de obra'],
                    ['id' => 'SOP-CC-PLAN-004', 'nome' => 'Gestão de subcontratados'],
                ],
            ],
            [
                'nome' => 'Execução',
                'icone' => '🚧',
                'sops' => [
                    ['id' => 'SOP-CC-EXEC-001', 'nome' => 'Controle de qualidade e fiscalização'],
                    ['id' => 'SOP-CC-EXEC-002', 'nome' => 'Segurança do trabalho na obra'],
                    ['id' => 'SOP-CC-EXEC-003', 'nome' => 'Medições e controle de avanço'],
                    ['id' => 'SOP-CC-EXEC-004', 'nome' => 'Gestão de mudanças e aditivos'],
                    ['id' => 'SOP-CC-EXEC-005', 'nome' => 'Controle de custos e orçamento'],
                ],
            ],
            [
                'nome' => 'Financeiro',
                'icone' => '💰',
                'sops' => [
                    ['id' => 'SOP-CC-FIN-001', 'nome' => 'Faturamento e medições'],
                    ['id' => 'SOP-CC-FIN-002', 'nome' => 'Controle de fluxo de caixa da obra'],
                    ['id' => 'SOP-CC-FIN-003', 'nome' => 'Gestão de garantias e retenções'],
                ],
            ],
            [
                'nome' => 'Entrega',
                'icone' => '🏠',
                'sops' => [
                    ['id' => 'SOP-CC-ENT-001', 'nome' => 'Vistoria e entrega da obra'],
                    ['id' => 'SOP-CC-ENT-002', 'nome' => 'Documentação técnica e As Built'],
                    ['id' => 'SOP-CC-ENT-003', 'nome' => 'Pós-entrega e assistência técnica'],
                ],
            ],
        ];
    }

    /**
     * SOPs específicos para Tecnologia
     */
    private function getSOPsTecnologia(): array
    {
        return [
            [
                'nome' => 'Comercial',
                'icone' => '💼',
                'sops' => [
                    ['id' => 'SOP-TI-COM-001', 'nome' => 'Prospecção e qualificação'],
                    ['id' => 'SOP-TI-COM-002', 'nome' => 'Proposta técnica e comercial'],
                    ['id' => 'SOP-TI-COM-003', 'nome' => 'Negociação e fechamento'],
                ],
            ],
            [
                'nome' => 'Desenvolvimento',
                'icone' => '⚙️',
                'sops' => [
                    ['id' => 'SOP-TI-DEV-001', 'nome' => 'Gestão de projetos ágeis'],
                    ['id' => 'SOP-TI-DEV-002', 'nome' => 'Code review e qualidade'],
                    ['id' => 'SOP-TI-DEV-003', 'nome' => 'Deploy e CI/CD'],
                    ['id' => 'SOP-TI-DEV-004', 'nome' => 'Gestão de bugs e suporte'],
                ],
            ],
            [
                'nome' => 'Infraestrutura',
                'icone' => '🔧',
                'sops' => [
                    ['id' => 'SOP-TI-INF-001', 'nome' => 'Monitoramento de serviços'],
                    ['id' => 'SOP-TI-INF-002', 'nome' => 'Backup e disaster recovery'],
                    ['id' => 'SOP-TI-INF-003', 'nome' => 'Gestão de acessos e segurança'],
                ],
            ],
        ];
    }

    /**
     * SOPs específicos para Saúde
     */
    private function getSOPsSaude(): array
    {
        return [
            [
                'nome' => 'Atendimento',
                'icone' => '🏥',
                'sops' => [
                    ['id' => 'SOP-SA-ATD-001', 'nome' => 'Agendamento e triagem'],
                    ['id' => 'SOP-SA-ATD-002', 'nome' => 'Consulta e anamnese'],
                    ['id' => 'SOP-SA-ATD-003', 'nome' => 'Prescrição e orientações'],
                ],
            ],
            [
                'nome' => 'Procedimentos',
                'icone' => '⚕️',
                'sops' => [
                    ['id' => 'SOP-SA-PROC-001', 'nome' => 'Esterilização e biossegurança'],
                    ['id' => 'SOP-SA-PROC-002', 'nome' => 'Controle de infecção hospitalar'],
                    ['id' => 'SOP-SA-PROC-003', 'nome' => 'Gestão de materiais médicos'],
                ],
            ],
            [
                'nome' => 'Administrativo',
                'icone' => '📋',
                'sops' => [
                    ['id' => 'SOP-SA-ADM-001', 'nome' => 'Prontuário eletrônico e LGPD'],
                    ['id' => 'SOP-SA-ADM-002', 'nome' => 'Faturamento e convênios'],
                ],
            ],
        ];
    }

    /**
     * SOPs gerais para setores não mapeados
     */
    private function getSOPsGeral(): array
    {
        return [
            [
                'nome' => 'Comercial',
                'icone' => '💼',
                'sops' => [
                    ['id' => 'SOP-GER-COM-001', 'nome' => 'Prospecção de clientes'],
                    ['id' => 'SOP-GER-COM-002', 'nome' => 'Proposta comercial'],
                    ['id' => 'SOP-GER-COM-003', 'nome' => 'Fechamento de vendas'],
                ],
            ],
            [
                'nome' => 'Operacional',
                'icone' => '⚙️',
                'sops' => [
                    ['id' => 'SOP-GER-OPS-001', 'nome' => 'Atendimento ao cliente'],
                    ['id' => 'SOP-GER-OPS-002', 'nome' => 'Controle de qualidade'],
                    ['id' => 'SOP-GER-OPS-003', 'nome' => 'Gestão de fornecedores'],
                ],
            ],
            [
                'nome' => 'Administrativo',
                'icone' => '📋',
                'sops' => [
                    ['id' => 'SOP-GER-ADM-001', 'nome' => 'Gestão financeira'],
                    ['id' => 'SOP-GER-ADM-002', 'nome' => 'Recursos humanos'],
                ],
            ],
        ];
    }

    /**
     * SOPs para Educação
     */
    private function getSOPsEducacao(): array
    {
        return [
            [
                'nome' => 'Acadêmico',
                'icone' => '🎓',
                'sops' => [
                    ['id' => 'SOP-EDU-ACA-001', 'nome' => 'Planejamento pedagógico'],
                    ['id' => 'SOP-EDU-ACA-002', 'nome' => 'Avaliação e recuperação'],
                    ['id' => 'SOP-EDU-ACA-003', 'nome' => 'Gestão de turmas e horários'],
                ],
            ],
            [
                'nome' => 'Administrativo',
                'icone' => '📋',
                'sops' => [
                    ['id' => 'SOP-EDU-ADM-001', 'nome' => 'Matrícula e rematrícula'],
                    ['id' => 'SOP-EDU-ADM-002', 'nome' => 'Controle de frequência'],
                    ['id' => 'SOP-EDU-ADM-003', 'nome' => 'Comunicação com responsáveis'],
                ],
            ],
        ];
    }

    /**
     * SOPs para Consultoria
     */
    private function getSOPsConsultoria(): array
    {
        return [
            [
                'nome' => 'Comercial',
                'icone' => '💼',
                'sops' => [
                    ['id' => 'SOP-CON-COM-001', 'nome' => 'Diagnóstico empresarial'],
                    ['id' => 'SOP-CON-COM-002', 'nome' => 'Proposta de projeto'],
                    ['id' => 'SOP-CON-COM-003', 'nome' => 'Contratação de consultoria'],
                ],
            ],
            [
                'nome' => 'Execução',
                'icone' => '⚙️',
                'sops' => [
                    ['id' => 'SOP-CON-EXEC-001', 'nome' => 'Gestão de projetos'],
                    ['id' => 'SOP-CON-EXEC-002', 'nome' => 'Entrega de resultados'],
                    ['id' => 'SOP-CON-EXEC-003', 'nome' => 'Acompanhamento pós-projeto'],
                ],
            ],
        ];
    }

    /**
     * SOPs para Varejo
     */
    private function getSOPsVarejo(): array
    {
        return [
            [
                'nome' => 'Vendas',
                'icone' => '🛒',
                'sops' => [
                    ['id' => 'SOP-VAR-VEN-001', 'nome' => 'Atendimento e vendas'],
                    ['id' => 'SOP-VAR-VEN-002', 'nome' => 'Gestão do caixa'],
                    ['id' => 'SOP-VAR-VEN-003', 'nome' => 'Pós-venda e trocas'],
                ],
            ],
            [
                'nome' => 'Estoque',
                'icone' => '📦',
                'sops' => [
                    ['id' => 'SOP-VAR-EST-001', 'nome' => 'Controle de estoque'],
                    ['id' => 'SOP-VAR-EST-002', 'nome' => 'Compras e fornecedores'],
                    ['id' => 'SOP-VAR-EST-003', 'nome' => 'Inventário e perdas'],
                ],
            ],
        ];
    }

    /**
     * SOPs para Indústria
     */
    private function getSOPsIndustrial(): array
    {
        return [
            [
                'nome' => 'Produção',
                'icone' => '🏭',
                'sops' => [
                    ['id' => 'SOP-IND-PROD-001', 'nome' => 'Planejamento de produção'],
                    ['id' => 'SOP-IND-PROD-002', 'nome' => 'Controle de qualidade'],
                    ['id' => 'SOP-IND-PROD-003', 'nome' => 'Manutenção preventiva'],
                ],
            ],
            [
                'nome' => 'Segurança',
                'icone' => '🦺',
                'sops' => [
                    ['id' => 'SOP-IND-SEG-001', 'nome' => 'Segurança do trabalho'],
                    ['id' => 'SOP-IND-SEG-002', 'nome' => 'Meio ambiente e resíduos'],
                    ['id' => 'SOP-IND-SEG-003', 'nome' => 'Emergências e acidentes'],
                ],
            ],
        ];
    }

    /**
     * SOPs para Alimentício/Restaurante
     */
    private function getSOPsAlimenticio(): array
    {
        return [
            [
                'nome' => 'Produção',
                'icone' => '👨‍🍳',
                'sops' => [
                    ['id' => 'SOP-ALI-PROD-001', 'nome' => 'Manipulação de alimentos'],
                    ['id' => 'SOP-ALI-PROD-002', 'nome' => 'Controle de temperatura'],
                    ['id' => 'SOP-ALI-PROD-003', 'nome' => 'Higienização e limpeza'],
                ],
            ],
            [
                'nome' => 'Atendimento',
                'icone' => '🍽️',
                'sops' => [
                    ['id' => 'SOP-ALI-ATD-001', 'nome' => 'Atendimento ao cliente'],
                    ['id' => 'SOP-ALI-ATD-002', 'nome' => 'Delivery e take-away'],
                ],
            ],
            [
                'nome' => 'Controle',
                'icone' => '📋',
                'sops' => [
                    ['id' => 'SOP-ALI-CTRL-001', 'nome' => 'Controle de estoque'],
                    ['id' => 'SOP-ALI-CTRL-002', 'nome' => 'APPCC e vigilância sanitária'],
                ],
            ],
        ];
    }

    // ===== F-06 HELPER METHODS =====

    /**
     * Buscar alertas ativos para um SOP
     */
    private function buscarAlertasAtivos(int $sopId): array
    {
        return Database::query(
            "SELECT * FROM alertas WHERE sop_id = :sop_id AND status = 'ativo' ORDER BY prioridade DESC, data_criacao DESC",
            ['sop_id' => $sopId]
        );
    }

    /**
     * Buscar histórico de contingências de um SOP
     */
    private function buscarHistoricoContencao(int $sopId): array
    {
        return Database::query(
            "SELECT * FROM ocorrencias_contencao WHERE sop_id = :sop_id ORDER BY data_inicio DESC LIMIT 10",
            ['sop_id' => $sopId]
        );
    }

    /**
     * Formatar SOP completo para visualização (F-06)
     */
    private function formatarSopParaViewCompleta(array $sop, array $conteudo, array $empresa, array $kpis): array
    {
        // Estruturar subtópicos com procedimentos próprios
        $subtopicosCompletos = [];
        if (isset($conteudo['subtopicos'])) {
            foreach ($conteudo['subtopicos'] as $index => $subtopico) {
                $letra = chr(65 + $index); // A, B, C...
                
                $subtopicosCompletos[] = [
                    'letra' => $letra,
                    'nome' => $subtopico['nome'],
                    'descricao' => $subtopico['descricao'],
                    'procedimentos' => $conteudo['procedimentos'][$index]['passos'] ?? [],
                    'checklist' => $this->getChecklistPorSubtopico($conteudo['checklist'] ?? [], $index),
                    'evidencias' => $this->getEvidenciasPorSubtopico($conteudo['evidencias'] ?? [], $index),
                ];
            }
        }

        return [
            'id' => $sop['id'],
            'sop_codigo' => $sop['sop_codigo'],
            'titulo' => $sop['titulo'],
            'versao' => $sop['versao'],
            'empresa' => $empresa['nome'] ?? 'Empresa',
            'setor' => $empresa['segmento'] ?? 'Tecnologia', 
            'norma' => ApiHelper::getNormasPorSetor($empresa['segmento'] ?? 'Tecnologia'),
            'objetivo' => $conteudo['objetivo'] ?? '',
            'escopo_aplica' => $conteudo['escopo']['aplica_se'] ?? '',
            'escopo_nao_aplica' => $conteudo['escopo']['nao_aplica'] ?? '',
            'subtopicos_completos' => $subtopicosCompletos,
            'responsaveis' => $conteudo['responsaveis'] ?? [],
            'kpis' => $kpis,
        ];
    }

    /**
     * Dividir checklist por subtópico
     */
    private function getChecklistPorSubtopico(array $checklist, int $index): array
    {
        if (empty($checklist)) return [];
        
        $itemsPorSubtopico = ceil(count($checklist) / 3); // Dividir em 3 subtópicos
        $inicio = $index * $itemsPorSubtopico;
        
        return array_slice($checklist, $inicio, $itemsPorSubtopico);
    }

    /**
     * Dividir evidências por subtópico
     */
    private function getEvidenciasPorSubtopico(array $evidencias, int $index): array
    {
        if (empty($evidencias)) return [];
        
        $itemsPorSubtopico = ceil(count($evidencias) / 3);
        $inicio = $index * $itemsPorSubtopico;
        
        return array_slice($evidencias, $inicio, $itemsPorSubtopico);
    }
}
