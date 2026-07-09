-- =====================================================
-- O Consultor — Migration 042: Modelo de geração de imagens configurável
-- Data: 2026-07-09
-- Descrição: O modelo 'dall-e-3' não está disponível em várias contas OpenAI
--            (erro 400 "The model 'dall-e-3' does not exist"). Torna o modelo
--            de imagem configurável na tela de APIs (grupo api_openai), com
--            padrão 'gpt-image-1' (modelo atual de geração de imagens).
-- =====================================================

USE o_consultor;

INSERT IGNORE INTO configuracoes (chave, valor, grupo_config, descricao, criptografado) VALUES
('openai_imagem_modelo', 'gpt-image-1', 'api_openai', 'Modelo de geração de imagens (gpt-image-1, dall-e-3, dall-e-2)', 0);
