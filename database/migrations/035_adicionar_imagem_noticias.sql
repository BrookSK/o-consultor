-- =====================================================
-- O Consultor — Migration 035: Imagem nas notícias
-- Data: 2026-07-08
-- Descrição: Adiciona campo de imagem de capa às notícias, usado nos
--            cards da Central de Conteúdo (feed inline com imagem/headline/resumo).
-- =====================================================

USE o_consultor;

-- ALTER TABLE ... ADD COLUMN IF NOT EXISTS só existe a partir do MySQL 8.0.29.
-- Para funcionar em qualquer versão, verifica via information_schema antes de adicionar.
SET @coluna_existe = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'o_consultor' AND TABLE_NAME = 'noticias' AND COLUMN_NAME = 'imagem_url'
);

SET @sql = IF(@coluna_existe = 0,
    'ALTER TABLE noticias ADD COLUMN imagem_url VARCHAR(1000) NULL AFTER url',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- Correção: busca_logs.api_utilizada era NOT NULL sem valor padrão, mas o
-- registro é criado (criarLogBusca) ANTES de a API ser escolhida — o INSERT
-- falhava sempre, quebrando toda busca de notícias com "Erro interno".
-- =====================================================
ALTER TABLE busca_logs MODIFY COLUMN api_utilizada ENUM('perplexity', 'openai', 'anthropic') NULL;
