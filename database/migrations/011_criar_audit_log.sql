-- =====================================================
-- O Consultor — Migration 011: Audit Log System
-- Data: 2026-06-27
-- Descrição: Sistema de auditoria para ações sensíveis (SSO, etc.)
-- =====================================================

USE o_consultor;

-- =====================================================
-- TABELA: audit_log (Log de auditoria)
-- =====================================================
CREATE TABLE IF NOT EXISTS audit_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    empresa_id INT UNSIGNED NULL,
    acao VARCHAR(100) NOT NULL,
    modulo VARCHAR(50) NOT NULL,
    descricao TEXT NULL,
    dados_extras JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_empresa_id (empresa_id),
    INDEX idx_acao (acao),
    INDEX idx_timestamp (timestamp),
    INDEX idx_modulo (modulo),
    CONSTRAINT fk_audit_log_user FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_audit_log_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERIR DADOS INICIAIS (exemplos para referência)
-- =====================================================
-- Os logs serão criados dinamicamente pelo sistema