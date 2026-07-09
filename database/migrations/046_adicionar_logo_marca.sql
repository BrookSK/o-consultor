-- =====================================================
-- O Consultor — Migration 046: Logo da marca (Brand Book)
-- Data: 2026-07-09
-- Descrição: Guarda o caminho do logo enviado no Brand Book. O logo é
--            posicionado de forma estratégica e equilibrada nas imagens geradas.
-- =====================================================

USE o_consultor;

SET @c = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'marcas' AND COLUMN_NAME = 'logo_url');
SET @s = IF(@c = 0, 'ALTER TABLE marcas ADD COLUMN logo_url VARCHAR(500) NULL', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
