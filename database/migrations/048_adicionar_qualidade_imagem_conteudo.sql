-- =====================================================
-- O Consultor — Migration 048: Qualidade da imagem por conteúdo
-- Data: 2026-07-11
-- Descrição: Guarda a QUALIDADE escolhida para a geração de imagens (low/medium/
--            high do gpt-image-1). Impacta diretamente o CUSTO por imagem.
--            Padrão 'low' (mais econômico).
-- =====================================================

USE o_consultor;

SET @c = (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'conteudos_marca' AND COLUMN_NAME = 'qualidade_imagem');
SET @s = IF(@c = 0, "ALTER TABLE conteudos_marca ADD COLUMN qualidade_imagem VARCHAR(10) NOT NULL DEFAULT 'low'", 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
