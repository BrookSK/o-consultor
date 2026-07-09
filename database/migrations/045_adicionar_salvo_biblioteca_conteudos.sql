-- =====================================================
-- O Consultor — Migration 045: Flag "salvo na biblioteca" em conteudos_marca
-- Data: 2026-07-09
-- Descrição: A aba Biblioteca da Máquina de Conteúdo passa a exibir apenas
--            conteúdos APROVADOS/AGENDADOS/PUBLICADOS ou explicitamente salvos
--            para terminar depois (botão "Terminar depois"). Esta flag marca
--            os rascunhos que o usuário optou por manter na biblioteca.
-- =====================================================

USE o_consultor;

SET @c = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'conteudos_marca' AND COLUMN_NAME = 'salvo_biblioteca');
SET @s = IF(@c = 0, 'ALTER TABLE conteudos_marca ADD COLUMN salvo_biblioteca TINYINT(1) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
