-- =====================================================
-- O Consultor — Migration 017: Sistema de Documentos da Empresa para IA
-- Data: 2026-06-28
-- Descrição: Upload e processamento de documentos internos para enriquecer IA
-- =====================================================

USE o_consultor;

-- =====================================================
-- TABELA: documentos_empresa
-- =====================================================
CREATE TABLE IF NOT EXISTS documentos_empresa (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    nome_arquivo VARCHAR(255) NOT NULL,
    nome_original VARCHAR(255) NOT NULL,
    caminho_arquivo VARCHAR(500) NOT NULL,
    tipo_documento ENUM('manual', 'procedimento', 'politica', 'fluxograma', 'checklist', 'template', 'organograma', 'outro') NOT NULL DEFAULT 'outro',
    tipo_mime VARCHAR(100) NOT NULL,
    tamanho_bytes BIGINT UNSIGNED NOT NULL,
    hash_arquivo CHAR(64) NOT NULL COMMENT 'SHA-256 do arquivo para evitar duplicatas',
    
    -- Processamento por IA
    processado_ia TINYINT(1) NOT NULL DEFAULT 0,
    conteudo_extraido LONGTEXT NULL COMMENT 'Texto extraído do documento pela IA',
    insights_ia TEXT NULL COMMENT 'Insights identificados pela IA',
    areas_relacionadas JSON NULL COMMENT 'Áreas/departamentos relacionados identificados',
    processos_identificados JSON NULL COMMENT 'Processos identificados no documento',
    data_processamento DATETIME NULL,
    
    -- Metadata e controle
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    usado_diagnostico TINYINT(1) NOT NULL DEFAULT 0,
    usado_sop_ids JSON NULL COMMENT 'IDs dos SOPs que usaram este documento',
    observacoes TEXT NULL,
    
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_empresa (empresa_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_tipo (tipo_documento),
    INDEX idx_processado (processado_ia),
    INDEX idx_hash (hash_arquivo),
    INDEX idx_ativo (ativo),
    
    CONSTRAINT fk_docs_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    CONSTRAINT fk_docs_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    
    UNIQUE KEY uk_empresa_hash (empresa_id, hash_arquivo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: documento_tags
-- =====================================================
CREATE TABLE IF NOT EXISTS documento_tags (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    documento_id BIGINT UNSIGNED NOT NULL,
    tag VARCHAR(100) NOT NULL,
    relevancia DECIMAL(3,2) NOT NULL DEFAULT 1.00 COMMENT 'Relevância da tag (0.00 a 1.00)',
    origem ENUM('usuario', 'ia_automatica') NOT NULL DEFAULT 'usuario',
    
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_documento (documento_id),
    INDEX idx_tag (tag),
    INDEX idx_relevancia (relevancia),
    
    CONSTRAINT fk_tags_documento FOREIGN KEY (documento_id) REFERENCES documentos_empresa(id) ON DELETE CASCADE,
    
    UNIQUE KEY uk_documento_tag (documento_id, tag)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: log_uso_documentos
-- =====================================================
CREATE TABLE IF NOT EXISTS log_uso_documentos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    documento_id BIGINT UNSIGNED NOT NULL,
    empresa_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    
    -- Contexto de uso
    contexto_uso ENUM('diagnostico', 'sop_geracao', 'plano_acao') NOT NULL,
    referencia_id INT UNSIGNED NULL COMMENT 'ID do diagnóstico, SOP, etc.',
    
    -- Informações sobre o uso
    trechos_utilizados TEXT NULL COMMENT 'Trechos específicos do documento utilizados',
    contribuicao_qualidade DECIMAL(3,2) NULL COMMENT 'Avaliação da contribuição (0.00 a 1.00)',
    
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_documento (documento_id),
    INDEX idx_empresa (empresa_id),
    INDEX idx_contexto (contexto_uso),
    INDEX idx_referencia (referencia_id),
    
    CONSTRAINT fk_log_documento FOREIGN KEY (documento_id) REFERENCES documentos_empresa(id) ON DELETE CASCADE,
    CONSTRAINT fk_log_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    CONSTRAINT fk_log_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- CONFIGURAÇÕES PARA PROCESSAMENTO DE DOCUMENTOS
-- =====================================================
INSERT IGNORE INTO configuracoes (chave, valor, descricao, grupo_config, criptografado) VALUES
('docs_max_file_size', '10485760', 'Tamanho máximo de arquivo em bytes (10MB)', 'documentos', 0),
('docs_allowed_types', '["pdf", "doc", "docx", "txt", "md", "rtf"]', 'Tipos de arquivo permitidos para upload', 'documentos', 0),
('docs_ia_auto_process', '1', 'Processar documentos automaticamente com IA', 'documentos', 0),
('docs_retention_days', '365', 'Dias para manter documentos no sistema', 'documentos', 0);

-- =====================================================
-- TRIGGER: Atualizar timestamp em documento_tags
-- =====================================================
DELIMITER //
CREATE TRIGGER IF NOT EXISTS documento_update_timestamp
BEFORE UPDATE ON documentos_empresa
FOR EACH ROW BEGIN
    SET NEW.atualizado_em = CURRENT_TIMESTAMP;
END//
DELIMITER ;

-- =====================================================
-- VIEW: Resumo de documentos por empresa
-- =====================================================
CREATE OR REPLACE VIEW vw_documentos_empresa_resumo AS
SELECT 
    e.id as empresa_id,
    e.nome as empresa_nome,
    COUNT(d.id) as total_documentos,
    COUNT(CASE WHEN d.processado_ia = 1 THEN 1 END) as documentos_processados,
    COUNT(CASE WHEN d.usado_diagnostico = 1 THEN 1 END) as usados_diagnostico,
    SUM(d.tamanho_bytes) as tamanho_total,
    MAX(d.criado_em) as ultimo_upload,
    GROUP_CONCAT(DISTINCT d.tipo_documento) as tipos_documentos
FROM empresas e
LEFT JOIN documentos_empresa d ON e.id = d.empresa_id AND d.ativo = 1
GROUP BY e.id, e.nome;