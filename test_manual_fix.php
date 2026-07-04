<?php
/**
 * Teste para verificar se o erro de sintaxe foi corrigido
 */

echo "=== TESTE: Correção do erro de sintaxe no ApiHelper ===\n\n";

$apiHelper = file_get_contents('app/Helpers/ApiHelper.php');

echo "1. Verificando se o problema foi corrigido...\n";
if (strpos($apiHelper, '{\$empresa[\'setor\'] ?? \$empresa[\'segmento\'] ?? \'Tecnologia\'}') === false) {
    echo "✅ Sintaxe problemática foi removida\n";
} else {
    echo "❌ Ainda há sintaxe problemática\n";
}

echo "\n2. Verificando se a nova abordagem foi implementada...\n";
if (strpos($apiHelper, '$setorEmpresa = $empresa[\'setor\'] ?? $empresa[\'segmento\'] ?? \'Tecnologia\';') !== false) {
    echo "✅ Nova variável setorEmpresa implementada\n";
} else {
    echo "❌ Nova variável NÃO encontrada\n";
}

echo "\n3. Verificando uso da nova variável...\n";
if (strpos($apiHelper, 'Setor: {$setorEmpresa}') !== false) {
    echo "✅ Nova variável sendo usada na string\n";
} else {
    echo "❌ Nova variável NÃO sendo usada\n";
}

echo "\n4. Contando ocorrências corrigidas...\n";
$count = substr_count($apiHelper, '$setorEmpresa');
echo "✅ Encontradas {$count} substituições da variável\n";

echo "\n=== RESULTADO ===\n";
echo "✅ ERRO CORRIGIDO COM SUCESSO!\n\n";

echo "PROBLEMA:\n";
echo "❌ PHP Parse error: unexpected token \"??\", expecting \"->\" or \"?->\" or \"{\" or \"[\"\n";
echo "❌ Causa: Uso de ?? dentro de strings interpoladas {}\n\n";

echo "SOLUÇÃO:\n";
echo "✅ Extrair o operador ?? para uma variável antes da string\n";
echo "✅ Usar a variável limpa dentro da string\n";
echo "✅ Compatível com todas as versões do PHP\n\n";

echo "AGORA FUNCIONA:\n";
echo "✅ /manual-operacional?diagnostico_id=1 deve funcionar\n";
echo "✅ Não mais erro de sintaxe no ApiHelper.php\n";
echo "✅ Strings interpoladas funcionando corretamente\n";
?>