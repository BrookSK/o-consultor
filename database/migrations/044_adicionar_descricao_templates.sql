-- =====================================================
-- O Consultor — Migration 044: Descrição por IA dos templates
-- Data: 2026-07-09
-- Descrição: Ao subir um template, a IA (visão) lê a imagem e gera uma
--            descrição detalhada do estilo/composição. Essa descrição é usada
--            para (1) a IA escolher qual template melhor se adapta ao tipo de
--            conteúdo e (2) como complemento/fallback no prompt de geração.
-- =====================================================

USE o_consultor;

SET @c1 = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'marca_templates' AND COLUMN_NAME = 'descricao');
SET @s1 = IF(@c1 = 0, 'ALTER TABLE marca_templates ADD COLUMN descricao TEXT NULL', 'SELECT 1');
PREPARE st1 FROM @s1; EXECUTE st1; DEALLOCATE PREPARE st1;

SET @c2 = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'marca_templates' AND COLUMN_NAME = 'adequado_para');
SET @s2 = IF(@c2 = 0, 'ALTER TABLE marca_templates ADD COLUMN adequado_para VARCHAR(255) NULL COMMENT ''Tipos de conteúdo que combinam com este template''', 'SELECT 1');
PREPARE st2 FROM @s2; EXECUTE st2; DEALLOCATE PREPARE st2;
