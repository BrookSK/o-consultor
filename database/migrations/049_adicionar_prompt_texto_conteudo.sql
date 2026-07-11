-- =====================================================
-- O Consultor — Migration 049: Guardar o prompt de TEXTO e metadados de fonte
-- Data: 2026-07-11
-- Descrição: Salva o prompt COMPLETO enviado à IA para gerar o texto/legenda
--            (com notícia/biblioteca/jornada injetados) e a fonte usada, para
--            auditoria/transparência na tela "ver prompt".
-- =====================================================

USE o_consultor;

SET @c = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'conteudos_marca' AND COLUMN_NAME = 'prompt_texto');
SET @s = IF(@c = 0, 'ALTER TABLE conteudos_marca ADD COLUMN prompt_texto MEDIUMTEXT NULL', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c2 = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'conteudos_marca' AND COLUMN_NAME = 'fonte_conteudo');
SET @s2 = IF(@c2 = 0, "ALTER TABLE conteudos_marca ADD COLUMN fonte_conteudo VARCHAR(20) NULL", 'SELECT 1');
PREPARE st2 FROM @s2; EXECUTE st2; DEALLOCATE PREPARE st2;
