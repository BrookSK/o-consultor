-- =====================================================
-- O Consultor — Migration 047: Instrução de ajuste na fila de imagens
-- Data: 2026-07-09
-- Descrição: Permite regenerar UMA imagem com uma instrução de correção do
--            usuário (ex.: "corrigir grafia da headline") em BACKGROUND, sem
--            estourar o timeout do proxy. A instrução é guardada na fila.
-- =====================================================

USE o_consultor;

SET @c = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fila_imagens_conteudo' AND COLUMN_NAME = 'instrucao');
SET @s = IF(@c = 0, 'ALTER TABLE fila_imagens_conteudo ADD COLUMN instrucao TEXT NULL', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
