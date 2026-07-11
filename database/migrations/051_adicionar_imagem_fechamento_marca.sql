-- =====================================================
-- O Consultor — Migration 051: Imagem de fechamento da marca (Brand Book)
-- Data: 2026-07-11
-- Descrição: Imagem fixa (upload no Brand Book) usada como ÚLTIMO slide dos
--            carrosséis (fechamento), em vez de ser gerada pela IA.
-- =====================================================

USE o_consultor;

SET @c = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'marcas' AND COLUMN_NAME = 'imagem_fechamento_url');
SET @s = IF(@c = 0, 'ALTER TABLE marcas ADD COLUMN imagem_fechamento_url VARCHAR(500) NULL', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
