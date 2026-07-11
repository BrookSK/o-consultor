-- =====================================================
-- O Consultor — Migration 050: Categoria/objetivo do template
-- Data: 2026-07-11
-- Descrição: Permite classificar cada template por OBJETIVO/categoria
--            (noticia, engajamento, impacto, educativo, conversao, institucional).
--            Usado para escolher o template mais adequado ao objetivo do conteúdo.
-- =====================================================

USE o_consultor;

SET @c = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'marca_templates' AND COLUMN_NAME = 'categoria');
SET @s = IF(@c = 0, "ALTER TABLE marca_templates ADD COLUMN categoria VARCHAR(30) NULL", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
