-- =====================================================
-- O Consultor — Migration 034: Plano de Ação como Consolidador
-- Data: 2026-07-07
-- Descrição: Liberação sequencial de etapas, tarefas com hora/tipo,
--            métricas/KPIs do plano com registros periódicos e gráfico.
-- =====================================================

USE o_consultor;

-- ---- Tarefas: etapa sequencial, hora, tipo e liberação ----
ALTER TABLE plano_tarefas ADD COLUMN ordem_etapa INT NOT NULL DEFAULT 1 AFTER prioridade_id;
ALTER TABLE plano_tarefas ADD COLUMN hora TIME NULL AFTER prazo;
ALTER TABLE plano_tarefas ADD COLUMN tipo ENUM('tarefa','reuniao','entrega','compromisso') NOT NULL DEFAULT 'tarefa' AFTER status;
ALTER TABLE plano_tarefas ADD COLUMN liberada TINYINT(1) NOT NULL DEFAULT 1 AFTER tipo;
ALTER TABLE plano_tarefas ADD COLUMN concluida_em DATETIME NULL AFTER liberada;

-- ---- Plano: score de maturidade acompanhado ----
ALTER TABLE planos ADD COLUMN score_maturidade DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER progresso_calculado;
ALTER TABLE planos ADD COLUMN score_inicial DECIMAL(5,2) NULL AFTER score_maturidade;
ALTER TABLE planos ADD COLUMN total_etapas INT NOT NULL DEFAULT 0 AFTER score_inicial;
ALTER TABLE planos ADD COLUMN etapa_atual INT NOT NULL DEFAULT 1 AFTER total_etapas;

-- =====================================================
-- TABELA: plano_metricas (KPIs/indicadores do plano)
-- =====================================================
CREATE TABLE IF NOT EXISTS plano_metricas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plano_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    categoria VARCHAR(60) NOT NULL DEFAULT 'geral', -- financeiro, leads, operacional, etc.
    unidade VARCHAR(30) NULL,                        -- R$, %, un, leads...
    meta DECIMAL(15,2) NULL,
    frequencia ENUM('semanal','quinzenal','mensal') NOT NULL DEFAULT 'mensal',
    direcao ENUM('cima','baixo') NOT NULL DEFAULT 'cima', -- cima = maior é melhor
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_plano (plano_id),
    INDEX idx_categoria (categoria),
    CONSTRAINT fk_metricas_plano FOREIGN KEY (plano_id) REFERENCES planos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: plano_metricas_registros (valores ao longo do tempo)
-- =====================================================
CREATE TABLE IF NOT EXISTS plano_metricas_registros (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    metrica_id INT UNSIGNED NOT NULL,
    valor DECIMAL(15,2) NOT NULL,
    data_referencia DATE NOT NULL,
    observacao VARCHAR(255) NULL,
    usuario_id INT UNSIGNED NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_metrica (metrica_id),
    INDEX idx_data (data_referencia),
    CONSTRAINT fk_metricas_reg_metrica FOREIGN KEY (metrica_id) REFERENCES plano_metricas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
