-- =====================================================
-- O Consultor — Migration 035: Imagem nas notícias
-- Data: 2026-07-08
-- Descrição: Adiciona campo de imagem de capa às notícias, usado nos
--            cards da Central de Conteúdo (feed inline com imagem/headline/resumo).
-- =====================================================

USE o_consultor;

ALTER TABLE noticias ADD COLUMN imagem_url VARCHAR(1000) NULL AFTER url;
