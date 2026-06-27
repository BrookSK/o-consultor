-- =====================================================
-- O Consultor — Migration 008: Tabelas SOPs Completas
-- Data: 2026-06-27
-- Descrição: Criação das tabelas para KPIs e contingência dos SOPs
-- =====================================================

USE o_consultor;

-- =====================================================
-- TABELA: sop_kpis (KPIs nativos de cada SOP)
-- =====================================================
CREATE TABLE IF NOT EXISTS sop_kpis (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    sop_id INT UNSIGNED NOT NULL,
    nome VARCHAR(255) NOT NULL,
    meta_verde VARCHAR(100) NOT NULL,
    meta_amarela VARCHAR(100) NOT NULL,
    meta_vermelha VARCHAR(100) NOT NULL,
    acao_vermelha TEXT NOT NULL,
    valor_atual VARCHAR(100) NULL,
    zona_atual ENUM('verde', 'amarela', 'vermelha') NULL,
    ultima_medicao DATETIME NULL,
    responsavel VARCHAR(255) NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_empresa (empresa_id),
    INDEX idx_sop (sop_id),
    INDEX idx_zona (zona_atual),
    CONSTRAINT fk_sop_kpis_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    CONSTRAINT fk_sop_kpis_sop FOREIGN KEY (sop_id) REFERENCES sops(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: sop_contencoes (Planos N1/N2/N3 de cada SOP)
-- =====================================================
CREATE TABLE IF NOT EXISTS sop_contencoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    sop_id INT UNSIGNED NOT NULL,
    nivel ENUM('N1', 'N2', 'N3') NOT NULL,
    situacao TEXT NOT NULL,
    acao LONGTEXT NOT NULL,
    responsavel VARCHAR(255) NOT NULL,
    prazo_resposta VARCHAR(100) NULL,
    escalar_se TEXT NULL,
    comunicacao TEXT NULL,
    documentacao_obrigatoria TEXT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_empresa (empresa_id),
    INDEX idx_sop (sop_id),
    INDEX idx_nivel (nivel),
    CONSTRAINT fk_sop_contencoes_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    CONSTRAINT fk_sop_contencoes_sop FOREIGN KEY (sop_id) REFERENCES sops(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ATUALIZAR TABELA: sops (adicionar campos F-05)
-- =====================================================
ALTER TABLE sops 
ADD COLUMN IF NOT EXISTS sop_codigo VARCHAR(50) NULL AFTER titulo,
ADD COLUMN IF NOT EXISTS diagnostico_id INT UNSIGNED NULL AFTER empresa_id,
ADD COLUMN IF NOT EXISTS conteudo_completo JSON NULL AFTER conteudo,
ADD COLUMN IF NOT EXISTS motivo_alteracao TEXT NULL AFTER versao,
ADD COLUMN IF NOT EXISTS aprovado_em DATETIME NULL AFTER atualizado_em,
ADD COLUMN IF NOT EXISTS aprovado_por INT UNSIGNED NULL AFTER aprovado_em,
ADD INDEX idx_sop_codigo (sop_codigo),
ADD INDEX idx_diagnostico (diagnostico_id),
ADD CONSTRAINT fk_sops_diagnostico FOREIGN KEY (diagnostico_id) REFERENCES diagnosticos(id) ON DELETE SET NULL,
ADD CONSTRAINT fk_sops_aprovado_por FOREIGN KEY (aprovado_por) REFERENCES usuarios(id) ON DELETE SET NULL;

-- =====================================================
-- TABELA: sop_historico_versoes (controle de versioning)
-- =====================================================
CREATE TABLE IF NOT EXISTS sop_historico_versoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sop_id INT UNSIGNED NOT NULL,
    versao_anterior VARCHAR(10) NOT NULL,
    versao_nova VARCHAR(10) NOT NULL,
    conteudo_anterior JSON NULL,
    motivo_alteracao TEXT NOT NULL,
    usuario_alteracao INT UNSIGNED NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sop (sop_id),
    INDEX idx_usuario (usuario_alteracao),
    CONSTRAINT fk_sop_historico_sop FOREIGN KEY (sop_id) REFERENCES sops(id) ON DELETE CASCADE,
    CONSTRAINT fk_sop_historico_usuario FOREIGN KEY (usuario_alteracao) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;