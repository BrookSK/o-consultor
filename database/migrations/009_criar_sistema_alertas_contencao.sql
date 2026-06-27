-- =====================================================
-- O Consultor — Migration 009: Sistema de Alertas e Contingência
-- Data: 2026-06-27
-- Descrição: Tabelas para alertas automáticos e histórico de contingências
-- =====================================================

USE o_consultor;

-- =====================================================
-- TABELA: alertas (Alertas automáticos do sistema)
-- =====================================================
CREATE TABLE IF NOT EXISTS alertas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    sop_id INT UNSIGNED NOT NULL,
    kpi_id INT UNSIGNED NOT NULL,
    tipo ENUM('kpi_vermelho', 'contencao_sugerida', 'prazo_vencido') NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT NOT NULL,
    nivel_sugerido ENUM('N1', 'N2', 'N3') NULL,
    status ENUM('ativo', 'em_tratamento', 'resolvido', 'arquivado') NOT NULL DEFAULT 'ativo',
    data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_resolucao DATETIME NULL,
    responsavel_atual VARCHAR(255) NULL,
    prioridade ENUM('baixa', 'media', 'alta', 'critica') NOT NULL DEFAULT 'media',
    INDEX idx_empresa (empresa_id),
    INDEX idx_sop (sop_id),
    INDEX idx_status (status),
    INDEX idx_prioridade (prioridade),
    CONSTRAINT fk_alertas_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    CONSTRAINT fk_alertas_sop FOREIGN KEY (sop_id) REFERENCES sops(id) ON DELETE CASCADE,
    CONSTRAINT fk_alertas_kpi FOREIGN KEY (kpi_id) REFERENCES sop_kpis(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: ocorrencias_contencao (Histórico de acionamentos)
-- =====================================================
CREATE TABLE IF NOT EXISTS ocorrencias_contencao (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    sop_id INT UNSIGNED NOT NULL,
    contencao_id INT UNSIGNED NOT NULL,
    alerta_id INT UNSIGNED NULL,
    nivel ENUM('N1', 'N2', 'N3') NOT NULL,
    situacao_detectada TEXT NOT NULL,
    data_inicio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_resolucao DATETIME NULL,
    status ENUM('em_andamento', 'resolvido', 'escalado', 'cancelado') NOT NULL DEFAULT 'em_andamento',
    responsavel_execucao VARCHAR(255) NOT NULL,
    usuario_responsavel INT UNSIGNED NULL,
    observacoes_execucao LONGTEXT NULL,
    resolucao_final TEXT NULL,
    tempo_resolucao INT NULL, -- em minutos
    escalado_para_nivel ENUM('N2', 'N3') NULL,
    data_escalacao DATETIME NULL,
    INDEX idx_empresa (empresa_id),
    INDEX idx_sop (sop_id),
    INDEX idx_nivel (nivel),
    INDEX idx_status (status),
    INDEX idx_data_inicio (data_inicio),
    CONSTRAINT fk_ocorrencias_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    CONSTRAINT fk_ocorrencias_sop FOREIGN KEY (sop_id) REFERENCES sops(id) ON DELETE CASCADE,
    CONSTRAINT fk_ocorrencias_contencao FOREIGN KEY (contencao_id) REFERENCES sop_contencoes(id) ON DELETE CASCADE,
    CONSTRAINT fk_ocorrencias_alerta FOREIGN KEY (alerta_id) REFERENCES alertas(id) ON DELETE SET NULL,
    CONSTRAINT fk_ocorrencias_usuario FOREIGN KEY (usuario_responsavel) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: sop_procedimentos_subtopicos (Procedimentos por subtópico)
-- =====================================================
CREATE TABLE IF NOT EXISTS sop_procedimentos_subtopicos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sop_id INT UNSIGNED NOT NULL,
    subtopico_nome VARCHAR(255) NOT NULL,
    subtopico_letra CHAR(1) NOT NULL, -- A, B, C
    procedimentos JSON NOT NULL, -- Array de passos
    checklist JSON NULL, -- Checklist específico do subtópico
    evidencias JSON NULL, -- Evidências específicas
    ordem_exibicao INT NOT NULL DEFAULT 1,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sop (sop_id),
    INDEX idx_subtopico (subtopico_nome),
    CONSTRAINT fk_procedimentos_sop FOREIGN KEY (sop_id) REFERENCES sops(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- FUNÇÃO: Criar alerta automático quando KPI fica vermelho
-- =====================================================
DELIMITER $$

CREATE TRIGGER trigger_kpi_zona_vermelha 
    AFTER UPDATE ON sop_kpis 
    FOR EACH ROW
BEGIN
    -- Se KPI mudou para zona vermelha, criar alerta
    IF NEW.zona_atual = 'vermelha' AND (OLD.zona_atual != 'vermelha' OR OLD.zona_atual IS NULL) THEN
        INSERT INTO alertas (
            empresa_id, 
            sop_id, 
            kpi_id, 
            tipo, 
            titulo, 
            descricao, 
            nivel_sugerido,
            prioridade
        )
        SELECT 
            NEW.empresa_id,
            NEW.sop_id,
            NEW.id,
            'kpi_vermelho',
            CONCAT('KPI Crítico: ', NEW.nome),
            CONCAT('O KPI "', NEW.nome, '" está na zona vermelha (', NEW.valor_atual, '). Ação imediata necessária conforme plano de contingência.'),
            'N1',
            'alta'
        FROM sops s 
        WHERE s.id = NEW.sop_id;
    END IF;
END$$

DELIMITER ;

-- =====================================================
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- =====================================================
ALTER TABLE sops ADD INDEX idx_status_empresa (status, empresa_id);
ALTER TABLE sop_kpis ADD INDEX idx_zona_empresa (zona_atual, empresa_id);
ALTER TABLE sop_contencoes ADD INDEX idx_nivel_sop (nivel, sop_id);