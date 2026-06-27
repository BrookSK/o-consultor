-- =====================================================
-- O Consultor — Migration 007: Tabelas Plano de Ação
-- Data: 2026-06-26
-- Descrição: Criar tabelas para sistema de plano de ação completo
-- =====================================================

USE o_consultor;

-- =====================================================
-- TABELA: planos (Planos de Ação principais)
-- =====================================================
DROP TABLE IF EXISTS planos_acao; -- Remove tabela antiga
CREATE TABLE IF NOT EXISTS planos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    diagnostico_id INT UNSIGNED NULL,
    usuario_id INT UNSIGNED NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    objetivo TEXT NULL,
    periodo_inicio DATE NULL,
    periodo_fim DATE NULL,
    status ENUM('em_elaboracao', 'ativo', 'pausado', 'concluido', 'cancelado') NOT NULL DEFAULT 'em_elaboracao',
    progresso_calculado DECIMAL(5,2) NOT NULL DEFAULT 0,
    total_tarefas INT NOT NULL DEFAULT 0,
    tarefas_concluidas INT NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_empresa (empresa_id),
    INDEX idx_diagnostico (diagnostico_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_status (status),
    CONSTRAINT fk_planos_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    CONSTRAINT fk_planos_diagnostico FOREIGN KEY (diagnostico_id) REFERENCES diagnosticos(id) ON DELETE SET NULL,
    CONSTRAINT fk_planos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: plano_prioridades (Prioridades geradas pela IA)
-- =====================================================
CREATE TABLE IF NOT EXISTS plano_prioridades (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plano_id INT UNSIGNED NOT NULL,
    area VARCHAR(100) NOT NULL,
    descricao_problema TEXT NOT NULL,
    acao_sugerida TEXT NOT NULL,
    impacto ENUM('alto', 'medio', 'baixo') NOT NULL DEFAULT 'medio',
    urgencia ENUM('alta', 'media', 'baixa') NOT NULL DEFAULT 'media',
    bloco_origem INT NOT NULL, -- De qual bloco do diagnóstico veio (1-5)
    confirmada TINYINT(1) NOT NULL DEFAULT 0,
    ordem_prioridade INT NOT NULL DEFAULT 999,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_plano (plano_id),
    INDEX idx_confirmada (confirmada),
    INDEX idx_ordem (ordem_prioridade),
    CONSTRAINT fk_prioridades_plano FOREIGN KEY (plano_id) REFERENCES planos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: plano_tarefas (Tarefas do Kanban)
-- =====================================================
CREATE TABLE IF NOT EXISTS plano_tarefas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plano_id INT UNSIGNED NOT NULL,
    prioridade_id INT UNSIGNED NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT NULL,
    area VARCHAR(100) NULL,
    responsavel VARCHAR(255) NULL,
    prazo DATE NULL,
    prioridade ENUM('alta', 'media', 'baixa') NOT NULL DEFAULT 'media',
    status ENUM('pendente', 'em_andamento', 'bloqueado', 'concluido') NOT NULL DEFAULT 'pendente',
    ordem_kanban INT NOT NULL DEFAULT 999,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_plano (plano_id),
    INDEX idx_prioridade_origem (prioridade_id),
    INDEX idx_status (status),
    INDEX idx_prazo (prazo),
    INDEX idx_ordem (ordem_kanban),
    CONSTRAINT fk_tarefas_plano FOREIGN KEY (plano_id) REFERENCES planos(id) ON DELETE CASCADE,
    CONSTRAINT fk_tarefas_prioridade FOREIGN KEY (prioridade_id) REFERENCES plano_prioridades(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: plano_reunioes (Registro de reuniões)
-- =====================================================
CREATE TABLE IF NOT EXISTS plano_reunioes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plano_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    data_reuniao DATETIME NOT NULL,
    participantes TEXT NULL,
    decisoes TEXT NULL,
    proximos_passos TEXT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_plano (plano_id),
    INDEX idx_data (data_reuniao),
    CONSTRAINT fk_reunioes_plano FOREIGN KEY (plano_id) REFERENCES planos(id) ON DELETE CASCADE,
    CONSTRAINT fk_reunioes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;