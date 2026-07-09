-- =====================================================
-- O Consultor — Migration 038: Instruções de busca de notícias (prompt mestre)
-- Data: 2026-07-08
-- Descrição: Permite ao usuário escrever instruções em texto livre que orientam
--            a IA/Perplexity sobre QUE TIPO de notícia priorizar na busca do seu
--            nicho (ex.: "priorize lançamentos de produtos e regulação; evite
--            fofoca de celebridades"). Fica na empresa (uma por empresa), junto
--            de segmento/língua, e é injetada no prompt de busca de notícias.
-- =====================================================

USE o_consultor;

SET @col_existe = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'empresas' AND COLUMN_NAME = 'instrucoes_busca_noticias'
);

SET @sql = IF(@col_existe = 0,
    'ALTER TABLE empresas ADD COLUMN instrucoes_busca_noticias TEXT NULL AFTER lingua_principal',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
