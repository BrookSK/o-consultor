<?php
/**
 * Componente de navegação entre blocos do diagnóstico
 * 
 * Variáveis esperadas:
 * - $rascunho: dados do rascunho
 * - $blocoAtual: número do bloco atual (1-5)
 */

$maxBloco = max(1, (int)($rascunho['bloco_atual'] ?? 1));
$porcentagem = ($blocoAtual / 5) * 100;
$blocoTitulos = [
    1 => 'Identificação da Empresa',
    2 => 'Estrutura Operacional', 
    3 => 'Financeiro e Comercial',
    4 => 'Gestão de Pessoas e Riscos',
    5 => 'Contexto Estratégico'
];
?>

<!-- Header com Progresso e Navegação -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Diagnóstico Empresarial</h1>
            <p class="text-gray-500">
                Bloco <?= $blocoAtual ?> de 5 — <?= $blocoTitulos[$blocoAtual] ?? 'Bloco ' . $blocoAtual ?>
            </p>
        </div>
        <div class="text-right">
            <div class="text-sm text-gray-500 mb-1">Progresso</div>
            <div class="w-32 bg-gray-200 rounded-full h-2">
                <div class="bg-primary h-2 rounded-full" style="width: <?= $porcentagem ?>%"></div>
            </div>
            <div class="text-xs text-gray-400 mt-1"><?= round($porcentagem) ?>% concluído</div>
        </div>
    </div>

    <!-- Navegação entre blocos -->
    <div class="flex items-center justify-center gap-2 mt-4">
        <?php for ($i = 1; $i <= 5; $i++): ?>
            <?php
            $podeAcessar = $i <= $maxBloco + 1; // Pode acessar blocos preenchidos + próximo
            $ativo = $i == $blocoAtual;
            $preenchido = $i < $maxBloco || ($i == $maxBloco && $maxBloco > 1);
            ?>
            
            <?php if ($podeAcessar): ?>
                <a href="<?= APP_URL ?>/diagnostico/bloco/<?= $i ?>?rascunho_id=<?= $rascunho['id'] ?>" 
                   class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold transition
                          <?= $ativo ? 'bg-primary text-white ring-2 ring-primary/30' : 
                             ($preenchido ? 'bg-green-500 text-white hover:bg-green-600' : 'bg-gray-200 text-gray-700 hover:bg-gray-300') ?>"
                   title="<?= $blocoTitulos[$i] ?? 'Bloco ' . $i ?>">
                    <?= $preenchido ? '✓' : $i ?>
                </a>
            <?php else: ?>
                <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold bg-gray-100 text-gray-400 cursor-not-allowed"
                     title="<?= $blocoTitulos[$i] ?? 'Bloco ' . $i ?> (Bloqueado)">
                    <?= $i ?>
                </div>
            <?php endif; ?>
            
            <?php if ($i < 5): ?>
                <div class="w-6 h-0.5 <?= $i < $maxBloco ? 'bg-green-300' : 'bg-gray-300' ?>"></div>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
    
    <div class="text-center mt-3">
        <p class="text-xs text-gray-500">
            <?php if ($maxBloco > 1): ?>
                Você pode navegar livremente entre os blocos já preenchidos
            <?php else: ?>
                Complete este bloco para desbloquear o próximo
            <?php endif; ?>
        </p>
    </div>
</div>