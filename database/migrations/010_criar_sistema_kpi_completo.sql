-- =====================================================
-- O Consultor — Migration 010: Sistema KPI Completo
-- Data: 2026-06-27
-- Descrição: Tabelas para registros de KPI e análises de IA
-- =====================================================

USE o_consultor;

-- =====================================================
-- TABELA: kpi_registros (Histórico de valores dos KPIs)
-- =====================================================
CREATE TABLE IF NOT EXISTS kpi_registros (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kpi_id INT UNSIGNED NOT NULL,
    valor VARCHAR(100) NOT NULL,
    data_medicao DATE NOT NULL,
    zona_calculada ENUM('verde', 'amarela', 'vermelha') NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    observacoes TEXT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_kpi (kpi_id),
    INDEX idx_data (data_medicao),
    INDEX idx_zona (zona_calculada),
    CONSTRAINT fk_kpi_registros_kpi FOREIGN KEY (kpi_id) REFERENCES sop_kpis(id) ON DELETE CASCADE,
    CONSTRAINT fk_kpi_registros_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: kpi_analises (Análises de IA para KPIs críticos)
-- =====================================================
CREATE TABLE IF NOT EXISTS kpi_analises (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kpi_id INT UNSIGNED NOT NULL,
    registro_id INT UNSIGNED NOT NULL,
    causas_raiz JSON NOT NULL,
    plano_acao_imediato JSON NOT NULL,
    prazo_revisao VARCHAR(50) NOT NULL,
    contencao_recomendada ENUM('N1', 'N2', 'N3') NOT NULL,
    justificativa_contencao TEXT NOT NULL,
    contexto_empresa JSON NULL,
    prompt_utilizado TEXT NULL,
    confiabilidade_analise DECIMAL(3,2) NULL, -- 0.00 a 1.00
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_kpi (kpi_id),
    INDEX idx_contencao (contencao_recomendada),
    INDEX idx_criado (criado_em),
    CONSTRAINT fk_kpi_analises_kpi FOREIGN KEY (kpi_id) REFERENCES sop_kpis(id) ON DELETE CASCADE,
    CONSTRAINT fk_kpi_analises_registro FOREIGN KEY (registro_id) REFERENCES kpi_registros(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ATUALIZAR TABELA: alertas (adicionar campos específicos para KPIs)
-- =====================================================
ALTER TABLE alertas 
ADD COLUMN IF NOT EXISTS kpi_registro_id INT UNSIGNED NULL AFTER kpi_id,
ADD COLUMN IF NOT EXISTS valor_detectado VARCHAR(100) NULL AFTER nivel_sugerido,
ADD COLUMN IF NOT EXISTS zona_anterior ENUM('verde', 'amarela', 'vermelha') NULL AFTER valor_detectado,
ADD COLUMN IF NOT EXISTS zona_atual ENUM('verde', 'amarela', 'vermelha') NULL AFTER zona_anterior,
ADD COLUMN IF NOT EXISTS analise_ia_id INT UNSIGNED NULL AFTER zona_atual,
ADD COLUMN IF NOT EXISTS lido TINYINT(1) NOT NULL DEFAULT 0 AFTER status,
ADD COLUMN IF NOT EXISTS lido_em DATETIME NULL AFTER lido,
ADD INDEX idx_lido (lido),
ADD CONSTRAINT fk_alertas_kpi_registro FOREIGN KEY (kpi_registro_id) REFERENCES kpi_registros(id) ON DELETE SET NULL,
ADD CONSTRAINT fk_alertas_analise_ia FOREIGN KEY (analise_ia_id) REFERENCES kpi_analises(id) ON DELETE SET NULL;

-- =====================================================
-- FUNÇÃO: Atualizar trigger para KPIs fora da meta
-- =====================================================
DROP TRIGGER IF EXISTS trigger_kpi_zona_vermelha;

DELIMITER $$

CREATE TRIGGER trigger_kpi_registro_alerta 
    AFTER INSERT ON kpi_registros 
    FOR EACH ROW
BEGIN
    DECLARE sop_id_var INT;
    DECLARE empresa_id_var INT;
    DECLARE kpi_nome_var VARCHAR(255);
    DECLARE tipo_alerta VARCHAR(50);
    DECLARE prioridade_alerta VARCHAR(20);
    
    -- Buscar dados do KPI
    SELECT k.sop_id, k.empresa_id, k.nome 
    INTO sop_id_var, empresa_id_var, kpi_nome_var
    FROM sop_kpis k 
    WHERE k.id = NEW.kpi_id;
    
    -- Determinar tipo e prioridade baseado na zona
    IF NEW.zona_calculada = 'vermelha' THEN
        SET tipo_alerta = 'kpi_critico';
        SET prioridade_alerta = 'critica';
    ELSEIF NEW.zona_calculada = 'amarela' THEN
        SET tipo_alerta = 'kpi_atencao';
        SET prioridade_alerta = 'alta';
    ELSE
        -- Zona verde, não criar alerta
        SET tipo_alerta = NULL;
    END IF;
    
    -- Criar alerta se necessário
    IF tipo_alerta IS NOT NULL THEN
        INSERT INTO alertas (
            empresa_id, 
            sop_id, 
            kpi_id, 
            kpi_registro_id,
            tipo, 
            titulo, 
            descricao, 
            nivel_sugerido,
            prioridade,
            valor_detectado,
            zona_atual
        ) VALUES (
            empresa_id_var,
            sop_id_var,
            NEW.kpi_id,
            NEW.id,
            tipo_alerta,
            CONCAT('KPI ', IF(NEW.zona_calculada = 'vermelha', 'Crítico', 'em Atenção'), ': ', kpi_nome_var),
            CONCAT('O KPI "', kpi_nome_var, '" está na zona ', NEW.zona_calculada, ' com valor: ', NEW.valor, '. ', 
                   IF(NEW.zona_calculada = 'vermelha', 'Ação imediata necessária conforme plano de contingência.', 'Monitoramento necessário.')),
            IF(NEW.zona_calculada = 'vermelha', 'N1', NULL),
            prioridade_alerta,
            NEW.valor,
            NEW.zona_calculada
        );
    END IF;
END$$

DELIMITER ;

-- =====================================================
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- =====================================================
ALTER TABLE sop_kpis ADD INDEX idx_empresa_ativo (empresa_id, ativo);
ALTER TABLE alertas ADD INDEX idx_empresa_status_lido (empresa_id, status, lido);
ALTER TABLE kpi_registros ADD INDEX idx_kpi_data_desc (kpi_id, data_medicao DESC);