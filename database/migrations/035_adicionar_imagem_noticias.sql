-- =====================================================
-- O Consultor — Migration 035: Imagem nas notícias
-- Data: 2026-07-08
-- Descrição: Adiciona campo de imagem de capa às notícias, usado nos
--            cards da Central de Conteúdo (feed inline com imagem/headline/resumo).
-- =====================================================

USE o_consultor;

ALTER TABLE noticias ADD COLUMN imagem_url VARCHAR(1000) NULL AFTER url;

-- =====================================================
-- Correção: busca_logs.api_utilizada era NOT NULL sem valor padrão, mas o
-- registro é criado (criarLogBusca) ANTES de a API ser escolhida — o INSERT
-- falhava sempre, quebrando toda busca de notícias com "Erro interno".
-- =====================================================
ALTER TABLE busca_logs MODIFY COLUMN api_utilizada ENUM('perplexity', 'openai', 'anthropic') NULL;
