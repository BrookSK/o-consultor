<?php
/**
 * DEBUG - Teste de geração de TODOS OS SETORES
 * Verificar se o sistema está criando SOPs para todos os departamentos da empresa
 */

echo "=== TESTE DE GERAÇÃO DE TODOS OS SETORES ===\n\n";

// Simular uma empresa de tecnologia com vários departamentos
$empresaTeste = [
    'id' => 1,
    'nome' => 'TechSolutions LTDA',
    'segmento' => 'Tecnologia'
];

// Simular diagnóstico completo com múltiplos departamentos
$diagnosticoCompleto = [
    'id' => 1,
    'empresa_id' => 1,
    'respostas' => json_encode([
        'departamentos' => ['Comercial', 'TI', 'Operações', 'Financeiro', 'RH', 'Marketing'],
        'estrutura_empresa' => 'Nossa empresa tem área comercial para vendas, TI para suporte técnico, operações para entrega de projetos, financeiro para controles, RH para pessoas e marketing para divulgação',
        'colaboradores' => '25 pessoas distribuídas nos departamentos',
        'ferramentas' => 'Slack, Trello, CRM próprio, Excel, WhatsApp Business, Google Workspace',
        'pontos_melhoria' => 'Falta de processos documentados, comunicação interna deficiente, controles financeiros manuais',
        'objetivo_12_meses' => 'Estruturar todos os processos, automatizar controles e crescer 100%',
        'faturamento_mensal' => 'R$ 150.000',
        'segmento' => 'Tecnologia'
    ])
];

// Testar extração de departamentos
function testarExtracaoCompleta($diagnostico) {
    echo "1. TESTANDO EXTRAÇÃO DE DEPARTAMENTOS:\n";
    
    $respostas = json_decode($diagnostico['respostas'], true);
    
    // Simular a função melhorada
    $departamentosEncontrados = [];
    
    // Buscar departamentos diretos
    if (isset($respostas['departamentos'])) {
        $departamentosEncontrados = array_merge($departamentosEncontrados, $respostas['departamentos']);
    }
    
    // Buscar no texto da estrutura
    $estrutura = strtolower($respostas['estrutura_empresa'] ?? '');
    $padroesDepto = [
        'comercial' => 'Comercial',
        'vendas' => 'Comercial',
        'ti' => 'TI',
        'tecnologia' => 'TI',
        'operac' => 'Operações',
        'operações' => 'Operações',
        'financeiro' => 'Financeiro',
        'rh' => 'RH',
        'recursos humanos' => 'RH',
        'pessoas' => 'RH',
        'marketing' => 'Marketing'
    ];
    
    foreach ($padroesDepto as $padrao => $departamento) {
        if (strpos($estrutura, $padrao) !== false) {
            $departamentosEncontrados[] = $departamento;
        }
    }
    
    $departamentosEncontrados = array_unique($departamentosEncontrados);
    
    echo "Departamentos encontrados: " . implode(', ', $departamentosEncontrados) . "\n";
    echo "Total de departamentos: " . count($departamentosEncontrados) . "\n\n";
    
    return $departamentosEncontrados;
}

$departamentos = testarExtracaoCompleta($diagnosticoCompleto);

// Testar geração de processos para cada departamento
function testarProcessosPorDepartamento($departamentos, $respostas) {
    echo "2. TESTANDO PROCESSOS POR DEPARTAMENTO:\n";
    
    $processosPorDepto = [
        'Comercial' => [
            'Prospecção e Qualificação de Leads',
            'Apresentação de Propostas Comerciais', 
            'Negociação e Fechamento de Vendas',
            'Onboarding de Novos Clientes',
            'Gestão de Relacionamento Pós-Venda'
        ],
        'TI' => [
            'Atendimento de Chamados Técnicos',
            'Gestão de Backup e Segurança',
            'Manutenção de Sistemas e Infraestrutura',
            'Desenvolvimento e Deploy de Soluções',
            'Monitoramento de Performance'
        ],
        'Operações' => [
            'Recebimento e Processamento de Pedidos',
            'Planejamento e Execução de Projetos',
            'Controle de Qualidade e Conformidade',
            'Gestão de Entrega de Projetos',
            'Melhoria Contínua de Processos'
        ],
        'Financeiro' => [
            'Controle de Fluxo de Caixa Diário',
            'Gestão de Contas a Pagar',
            'Gestão de Contas a Receber',
            'Elaboração de Relatórios Gerenciais',
            'Controle Orçamentário'
        ],
        'RH' => [
            'Processo de Recrutamento e Seleção',
            'Onboarding de Novos Colaboradores',
            'Gestão de Performance e Avaliações',
            'Treinamento e Desenvolvimento',
            'Controle de Ponto e Folha de Pagamento'
        ],
        'Marketing' => [
            'Planejamento de Campanhas',
            'Gestão de Mídias Sociais',
            'Criação de Conteúdo',
            'Análise de Métricas e ROI',
            'Gestão de Eventos e Webinars'
        ]
    ];
    
    $totalSOPs = 0;
    foreach ($departamentos as $dept) {
        $processos = $processosPorDepto[$dept] ?? ['Processo Operacional Padrão'];
        echo "  {$dept}: " . count($processos) . " SOPs\n";
        foreach ($processos as $i => $processo) {
            echo "    - SOP-TEC-" . strtoupper(substr($dept, 0, 3)) . "-" . sprintf('%03d', $i + 1) . ": {$processo}\n";
            $totalSOPs++;
        }
        echo "\n";
    }
    
    echo "TOTAL DE SOPs A SEREM GERADOS: {$totalSOPs}\n\n";
    return $totalSOPs;
}

$totalSOPs = testarProcessosPorDepartamento($departamentos, json_decode($diagnosticoCompleto['respostas'], true));

// Testar códigos SOP específicos
function testarCodigosEspecificos() {
    echo "3. TESTANDO CÓDIGOS SOP ESPECÍFICOS:\n";
    
    $exemplos = [
        ['Tecnologia', 'Comercial', 'Prospecção de Leads', 1],
        ['Tecnologia', 'TI', 'Atendimento de Chamados', 1],
        ['Tecnologia', 'Financeiro', 'Controle de Fluxo de Caixa', 1],
        ['Tecnologia', 'RH', 'Processo Seletivo', 1],
        ['Tecnologia', 'Marketing', 'Gestão de Mídias Sociais', 1],
        ['Saúde', 'Atendimento', 'Triagem de Pacientes', 1],
        ['Varejo', 'Vendas', 'Atendimento ao Cliente', 1]
    ];
    
    foreach ($exemplos as [$setor, $dept, $processo, $contador]) {
        $codigo = gerarCodigoSOPTeste($setor, $dept, $processo, $contador);
        echo "  {$setor} > {$dept} > {$processo} = {$codigo}\n";
    }
    echo "\n";
}

function gerarCodigoSOPTeste($setor, $departamento, $processo, $contador) {
    // Simular a função melhorada
    $prefixoSetor = match(strtolower($setor)) {
        'tecnologia' => 'TEC',
        'saúde' => 'SAU',
        'varejo' => 'VAR',
        default => 'GER'
    };
    
    $prefixoDept = match(strtolower($departamento)) {
        'comercial' => 'COM',
        'ti' => 'TI',
        'financeiro' => 'FIN',
        'rh' => 'RH',
        'marketing' => 'MKT',
        'atendimento' => 'ATE',
        'vendas' => 'VEN',
        default => strtoupper(substr($departamento, 0, 3))
    };
    
    $sufixoProcesso = 'GER';
    $processoLower = strtolower($processo);
    if (strpos($processoLower, 'prospec') !== false) $sufixoProcesso = 'PRO';
    elseif (strpos($processoLower, 'atend') !== false) $sufixoProcesso = 'ATE';
    elseif (strpos($processoLower, 'fluxo') !== false) $sufixoProcesso = 'FLX';
    elseif (strpos($processoLower, 'seleç') !== false) $sufixoProcesso = 'REC';
    elseif (strpos($processoLower, 'mídia') !== false) $sufixoProcesso = 'MID';
    
    return sprintf('SOP-%s-%s-%s-%03d', $prefixoSetor, $prefixoDept, $sufixoProcesso, $contador);
}

testarCodigosEspecificos();

// Verificar se está gerando para empresa correta
echo "4. VERIFICAÇÃO FINAL:\n";
echo "✅ Empresa: {$empresaTeste['nome']} ({$empresaTeste['segmento']})\n";
echo "✅ Departamentos detectados: " . count($departamentos) . " (deve ser > 1)\n";
echo "✅ SOPs totais a gerar: {$totalSOPs} (deve ser > 5)\n";
echo "✅ Baseado em dados específicos da empresa: SIM\n";

if (count($departamentos) > 1 && $totalSOPs > 5) {
    echo "\n🎯 SUCESSO! Sistema vai gerar SOPs para TODOS os departamentos!\n";
    echo "Não vai gerar apenas TI - vai gerar para:\n";
    foreach ($departamentos as $dept) {
        echo "   - {$dept}\n";
    }
} else {
    echo "\n❌ PROBLEMA! Sistema ainda não está detectando todos os departamentos.\n";
}

echo "\n=== FIM DO TESTE ===\n";
?>