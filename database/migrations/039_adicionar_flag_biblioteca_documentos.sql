-- =====================================================
-- O Consultor — Migration 039: Flag de Biblioteca em documentos_empresa
-- Data: 2026-07-08
-- Descrição: Marca documentos (PDFs) enviados pela aba "Biblioteca" da Central
--            de Conteúdo. Esses PDFs formam a base de literatura da empresa e
--            ficam disponíveis para o passo seguinte (Máquina de Conteúdo).
--            A flag separa esses documentos dos enviados no diagnóstico/SOP.
-- =====================================================

USE o_consultor;

SET @col_existe = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'documentos_empresa' AND COLUMN_NAME = 'biblioteca'
);

SET @sql = IF(@col_existe = 0,
    'ALTER TABLE documentos_empresa ADD COLUMN biblioteca TINYINT(1) NOT NULL DEFAULT 0 AFTER usado_diagnostico',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
