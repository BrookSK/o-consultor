-- =====================================================
-- O Consultor — Migration 003: Campo Academy na tabela usuários
-- Data: 2026-06-26
-- Descrição: Adiciona campo para vinculação com a plataforma My Academy
-- =====================================================

USE o_consultor;

-- Adicionar campo email_academy para vinculação SSO
ALTER TABLE usuarios
    ADD COLUMN email_academy VARCHAR(255) NULL AFTER empresa_id,
    ADD COLUMN onboarding_concluido TINYINT(1) NOT NULL DEFAULT 0 AFTER email_academy,
    ADD COLUMN telefone VARCHAR(20) NULL AFTER email,
    ADD COLUMN cargo VARCHAR(100) NULL AFTER telefone;

-- Índice para busca por email_academy
ALTER TABLE usuarios ADD INDEX idx_email_academy (email_academy);
