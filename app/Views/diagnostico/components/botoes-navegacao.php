<?php
/**
 * Componente de botões de navegação inferior
 * 
 * Variáveis esperadas:
 * - $rascunho: dados do rascunho
 * - $blocoAtual: número do bloco atual (1-5)
 * - $loading: variável Alpine.js para loading (opcional)
 */

$maxBloco = max(1, (int)($rascunho['bloco_atual'] ?? 1));
$temAnterior = $blocoAtual > 1;
$temProximo = $blocoAtual < 5;
$blocoAnterior = $blocoAtual - 1;
$proximoBloco = $blocoAtual + 1;
?>

<!-- Botões de Navegação -->
<div class="flex justify-between items-center pt-6 border-t border-gray-100">
    <div class="flex gap-3">
        <a href="<?= APP_URL ?>/diagnostico" 
           class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
            ← Voltar ao Início
        </a>
        
        <button type="button" @click="limparRascunho()"
                class="px-6 py-3 border border-red-300 rounded-lg text-red-700 hover:bg-red-50 transition">
            🗑️ Limpar Rascunho
        </button>
    </div>
    
    <div class="flex gap-3 items-center">
        <!-- Botão Anterior -->
        <?php if ($temAnterior): ?>
            <a href="<?= APP_URL ?>/diagnostico/bloco/<?= $blocoAnterior ?>?rascunho_id=<?= $rascunho['id'] ?>"
               class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                ← Bloco <?= $blocoAnterior ?>
            </a>
        <?php else: ?>
            <button type="button" disabled
                    class="px-6 py-3 border border-gray-200 rounded-lg text-gray-400 cursor-not-allowed">
                ← Anterior
            </button>
        <?php endif; ?>
        
        <!-- Botão Salvar -->
        <button type="submit" 
                <?php if (isset($loading)): ?>:disabled="<?= $loading ?>"<?php endif; ?>
                class="px-8 py-3 bg-primary text-white rounded-lg hover:bg-primary-700 transition font-semibold flex items-center gap-2"
                <?php if (isset($loading)): ?>:class="{ 'opacity-50 cursor-not-allowed': <?= $loading ?> }"<?php endif; ?>>
            
            <?php if (isset($loading)): ?>
                <span x-show="!<?= $loading ?>">
            <?php endif; ?>
                    <?php if ($temProximo): ?>
                        Salvar e Continuar →
                    <?php else: ?>
                        Salvar Bloco
                    <?php endif; ?>
            <?php if (isset($loading)): ?>
                </span>
                <span x-show="<?= $loading ?>" class="flex items-center gap-2">
                    <div class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                    Salvando...
                </span>
            <?php endif; ?>
        </button>
        
        <!-- Botão Próximo (navegação direta) -->
        <?php if ($temProximo && $proximoBloco <= $maxBloco + 1): ?>
            <a href="<?= APP_URL ?>/diagnostico/bloco/<?= $proximoBloco ?>?rascunho_id=<?= $rascunho['id'] ?>"
               class="px-6 py-3 border border-primary text-primary rounded-lg hover:bg-primary/10 transition">
                Bloco <?= $proximoBloco ?> →
            </a>
        <?php endif; ?>
    </div>
</div>