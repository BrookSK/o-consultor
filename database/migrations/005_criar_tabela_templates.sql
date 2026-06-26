-- =====================================================
-- O Consultor — Migration 005: Tabela de Templates de Marca
-- Data: 2026-06-26
-- Descrição: Armazena templates visuais de referência por marca
-- =====================================================

USE o_consultor;

CREATE TABLE IF NOT EXISTS marca_templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    marca_id INT UNSIGNED NOT NULL,
    nome_arquivo VARCHAR(255) NOT NULL,
    caminho VARCHAR(500) NOT NULL,
    nome_original VARCHAR(255) NOT NULL,
    tipo VARCHAR(50) NULL,
    tamanho INT UNSIGNED NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_marca (marca_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
