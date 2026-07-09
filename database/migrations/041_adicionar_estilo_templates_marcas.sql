-- =====================================================
-- O Consultor — Migration 041: Estilo visual dos templates (marcas)
-- Data: 2026-07-08
-- Descrição: Os templates (imagens de referência) da marca passam a influenciar
--            as imagens geradas. Como o DALL-E não aceita imagem de referência,
--            usamos visão (GPT-4o) para descrever o estilo visual comum dos
--            templates e injetamos essa descrição no prompt de geração.
--            Esta coluna guarda (cache) essa descrição, recalculada quando os
--            templates mudam (upload/remoção).
-- =====================================================

USE o_consultor;

SET @col_existe = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'marcas' AND COLUMN_NAME = 'templates_estilo'
);

SET @sql = IF(@col_existe = 0,
    'ALTER TABLE marcas ADD COLUMN templates_estilo TEXT NULL',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
