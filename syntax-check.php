<?php
// Arquivo para verificar sintaxe do SopController
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Verificando sintaxe do SopController...\n";

$file = __DIR__ . '/app/Controllers/SopController.php';

if (file_exists($file)) {
    $content = file_get_contents($file);
    
    // Verificar se há problemas óbvios
    $openBraces = substr_count($content, '{');
    $closeBraces = substr_count($content, '}');
    
    echo "Chaves abertas: {$openBraces}\n";
    echo "Chaves fechadas: {$closeBraces}\n";
    echo "Diferença: " . ($openBraces - $closeBraces) . "\n\n";
    
    // Tentar fazer parse
    $result = exec("php -l {$file} 2>&1", $output, $returnCode);
    
    echo "Resultado da verificação de sintaxe:\n";
    echo implode("\n", $output) . "\n";
    echo "Código de retorno: {$returnCode}\n";
    
} else {
    echo "Arquivo não encontrado: {$file}\n";
}
?>