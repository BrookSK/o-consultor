<?php

// Teste da nova estrutura de prompt padrão-ouro

require_once 'config/app.php';
require_once 'app/Helpers/ApiHelper.php';

// Dados de exemplo de empresa
$empresa = [
    'nome' => 'TechSolutions Ltda',
    'setor' => 'Tecnologia',
    'colaboradores' => '10-25',
    'faturamento' => 'R$ 100-500 mil',
    'maturidade' => 3,
    'departamentos' => 'Comercial, TI, Operações, Financeiro',
    'ferramentas' => 'WhatsApp, E-mail, Excel, CRM básico',
    'problemas' => 'Processos não documentados, dependência de pessoas-chave',
    'objetivos' => 'Crescer de forma organizada e estruturada',
    'mapeamento_detalhado' => 'Departamento Comercial: responsável por vendas e relacionamento',
    'procedimentos_mercado' => 'ITIL v4, ISO 27001 aplicável'
];

// Dados do SOP a gerar
$sop = [
    'id' => 'SOP-TI-OPS-001',
    'nome' => 'Gestão de Chamados e SLA',
    'departamento' => 'TI',
    'subtopicos_texto' => "- Subtópico A: Recebimento e Classificação\n- Subtópico B: Escalonamento\n- Subtópico C: Fechamento",
    'contexto_departamento' => [
        'funcoes_principais' => ['Suporte técnico', 'Manutenção sistemas'],
        'responsabilidades' => ['Resolver chamados', 'Manter SLA']
    ]
];

// Testar novo prompt
echo "=== TESTE DO NOVO PROMPT PADRÃO-OURO ===\n\n";

$prompt = ApiHelper::buildPromptSopDetalhado($empresa, $sop);

echo "Prompt gerado com sucesso!\n";
echo "Tamanho: " . strlen($prompt) . " caracteres\n\n";

// Mostrar algumas seções-chave do prompt
echo "--- INÍCIO DO PROMPT ---\n";
echo substr($prompt, 0, 500) . "...\n\n";

echo "--- ESTRUTURA JSON SOLICITADA ---\n";
if (strpos($prompt, 'cabecalho') !== false) {
    echo "✓ Nova estrutura padrão-ouro detectada (15 seções)\n";
} else {
    echo "✗ Estrutura antiga encontrada\n";
}

if (strpos($prompt, 'PROTOCOLO DE INTERPRETAÇÃO') !== false) {
    echo "✓ Protocolo de interpretação incluído\n";
}

if (strpos($prompt, 'REGRAS DE ASSERTIVIDADE') !== false) {
    echo "✓ Regras de assertividade incluídas\n";
}

if (strpos($prompt, 'CRITÉRIO DE VALIDAÇÃO') !== false) {
    echo "✓ Critérios de validação incluídos\n";
}

echo "\n--- FIM DO TESTE ---\n";

?>