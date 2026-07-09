-- =====================================================
-- O Consultor — Migration 043: Fila de geração de imagens de conteúdo
-- Data: 2026-07-09
-- Descrição: A geração de imagem via gpt-image-1 com imagens de referência
--            (templates) leva mais de 60s por imagem, estourando o timeout do
--            proxy (504). Esta fila permite gerar as imagens em BACKGROUND
--            (processo não limitado pelo proxy) enquanto o navegador apenas
--            consulta o status e exibe cada imagem quando fica pronta.
-- =====================================================

USE o_consultor;

CREATE TABLE IF NOT EXISTS fila_imagens_conteudo (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conteudo_id INT UNSIGNED NOT NULL,
    slide_index INT NOT NULL,
    status ENUM('pendente','processando','concluido','erro','cancelado') NOT NULL DEFAULT 'pendente',
    tentativas INT UNSIGNED NOT NULL DEFAULT 0,
    mensagem VARCHAR(500) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL,
    INDEX idx_conteudo (conteudo_id),
    INDEX idx_status (status),
    UNIQUE KEY uk_conteudo_slide (conteudo_id, slide_index)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
