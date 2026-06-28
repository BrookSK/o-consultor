-- =====================================================
-- O Consultor — Migration 020: Sistema Financeiro Completo
-- Data: 2026-06-28
-- Descrição: BLOCO GESTÃO - Módulo Financeiro com Fluxo de Caixa e Projeções
-- =====================================================

USE o_consultor;

-- =====================================================
-- TABELA: financeiro_transacoes (Transações financeiras)
-- =====================================================
CREATE TABLE IF NOT EXISTS financeiro_transacoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NULL, -- Quem criou a transação
    tipo ENUM('receita', 'despesa') NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(15,2) NOT NULL,
    valor_pago DECIMAL(15,2) NULL, -- Valor efetivamente pago (pode diferir do valor original)
    categoria VARCHAR(100) NOT NULL,
    subcategoria VARCHAR(100) NULL,
    data_vencimento DATE NOT NULL,
    data_pagamento DATE NULL,
    data_competencia DATE NOT NULL, -- Mês de competência da transação
    status ENUM('pendente', 'pago', 'vencido', 'cancelado', 'parcelado') NOT NULL DEFAULT 'pendente',
    
    -- Campos para transações recorrentes
    recorrente TINYINT(1) NOT NULL DEFAULT 0,
    frequencia ENUM('mensal', 'trimestral', 'semestral', 'anual') NULL,
    transacao_pai_id INT UNSIGNED NULL, -- Referência à transação original se for recorrência
    
    -- Campos para controle de pagamento
    forma_pagamento VARCHAR(50) NULL, -- Dinheiro, Cartão, PIX, Boleto, etc.
    conta_banco VARCHAR(100) NULL,
    numero_documento VARCHAR(100) NULL, -- Número da nota fiscal, boleto, etc.
    centro_custo VARCHAR(100) NULL,
    
    -- Observações e anexos
    observacoes TEXT NULL,
    observacoes_pagamento TEXT NULL, -- Observações específicas do pagamento
    anexos JSON NULL, -- Array de caminhos para arquivos anexados
    
    -- Campos de auditoria
    aprovado_por INT UNSIGNED NULL, -- Para transações que precisam de aprovação
    data_aprovacao DATETIME NULL,
    criado_por INT UNSIGNED NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_empresa (empresa_id),
    INDEX idx_tipo (tipo),
    INDEX idx_status (status),
    INDEX idx_data_vencimento (data_vencimento),
    INDEX idx_data_competencia (data_competencia),
    INDEX idx_categoria (categoria),
    INDEX idx_recorrente (recorrente),
    INDEX idx_empresa_competencia (empresa_id, data_competencia),
    INDEX idx_empresa_tipo_status (empresa_id, tipo, status),
    
    CONSTRAINT fk_transacao_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    CONSTRAINT fk_transacao_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_transacao_pai FOREIGN KEY (transacao_pai_id) REFERENCES financeiro_transacoes(id) ON DELETE SET NULL,
    CONSTRAINT fk_transacao_aprovador FOREIGN KEY (aprovado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_transacao_criador FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: financeiro_categorias (Categorias de receitas e despesas)
-- =====================================================
CREATE TABLE IF NOT EXISTS financeiro_categorias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    nome VARCHAR(100) NOT NULL,
    tipo ENUM('receita', 'despesa', 'ambos') NOT NULL DEFAULT 'ambos',
    cor_hex VARCHAR(7) NULL DEFAULT '#3B82F6', -- Cor para visualização em gráficos
    icone VARCHAR(50) NULL DEFAULT 'dollar-sign',
    descricao TEXT NULL,
    categoria_pai_id INT UNSIGNED NULL, -- Para subcategorias
    ordem_exibicao INT NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_empresa (empresa_id),
    INDEX idx_tipo (tipo),
    INDEX idx_ativo (ativo),
    UNIQUE KEY uk_empresa_nome (empresa_id, nome),
    
    CONSTRAINT fk_categoria_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    CONSTRAINT fk_categoria_pai FOREIGN KEY (categoria_pai_id) REFERENCES financeiro_categorias(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: financeiro_contas (Contas bancárias e cartões)
-- =====================================================
CREATE TABLE IF NOT EXISTS financeiro_contas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    nome VARCHAR(100) NOT NULL,
    tipo ENUM('conta_corrente', 'poupanca', 'cartao_credito', 'cartao_debito', 'dinheiro', 'pix') NOT NULL,
    banco VARCHAR(100) NULL,
    agencia VARCHAR(20) NULL,
    numero_conta VARCHAR(30) NULL,
    saldo_inicial DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    saldo_atual DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    limite_disponivel DECIMAL(15,2) NULL, -- Para cartões de crédito
    data_fechamento_cartao INT NULL, -- Dia do mês para cartões
    data_vencimento_cartao INT NULL, -- Dia do mês para cartões
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    padrao TINYINT(1) NOT NULL DEFAULT 0, -- Conta padrão para novas transações
    observacoes TEXT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_empresa (empresa_id),
    INDEX idx_tipo (tipo),
    INDEX idx_ativo (ativo),
    INDEX idx_padrao (padrao),
    
    CONSTRAINT fk_conta_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: financeiro_orcamento (Orçamento mensal por categoria)
-- =====================================================
CREATE TABLE IF NOT EXISTS financeiro_orcamento (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    ano_mes VARCHAR(7) NOT NULL, -- Formato: 2026-06
    categoria VARCHAR(100) NOT NULL,
    tipo ENUM('receita', 'despesa') NOT NULL,
    valor_orcado DECIMAL(15,2) NOT NULL,
    valor_realizado DECIMAL(15,2) NOT NULL DEFAULT 0.00, -- Atualizado automaticamente
    percentual_realizado DECIMAL(5,2) GENERATED ALWAYS AS (
        CASE 
            WHEN valor_orcado = 0 THEN 0 
            ELSE ROUND((valor_realizado / valor_orcado) * 100, 2)
        END
    ) STORED,
    observacoes TEXT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_empresa (empresa_id),
    INDEX idx_ano_mes (ano_mes),
    INDEX idx_categoria (categoria),
    INDEX idx_tipo (tipo),
    UNIQUE KEY uk_empresa_mes_categoria (empresa_id, ano_mes, categoria, tipo),
    
    CONSTRAINT fk_orcamento_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: financeiro_fluxo_caixa (Snapshot diário do fluxo de caixa)
-- =====================================================
CREATE TABLE IF NOT EXISTS financeiro_fluxo_caixa (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    data_referencia DATE NOT NULL,
    saldo_inicial DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    receitas_dia DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    despesas_dia DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    saldo_final DECIMAL(15,2) GENERATED ALWAYS AS (saldo_inicial + receitas_dia - despesas_dia) STORED,
    receitas_previstas_proximos_30_dias DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    despesas_previstas_proximos_30_dias DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    projecao_saldo_30_dias DECIMAL(15,2) GENERATED ALWAYS AS (saldo_final + receitas_previstas_proximos_30_dias - despesas_previstas_proximos_30_dias) STORED,
    observacoes TEXT NULL,
    processado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_empresa (empresa_id),
    INDEX idx_data_referencia (data_referencia),
    UNIQUE KEY uk_empresa_data (empresa_id, data_referencia),
    
    CONSTRAINT fk_fluxo_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERIR CATEGORIAS PADRÃO
-- =====================================================
INSERT INTO financeiro_categorias (empresa_id, nome, tipo, cor_hex, icone) VALUES 
-- Receitas
(1, 'Vendas de Produtos', 'receita', '#10B981', 'shopping-cart'),
(1, 'Prestação de Serviços', 'receita', '#059669', 'briefcase'),
(1, 'Consultoria', 'receita', '#047857', 'users'),
(1, 'Royalties', 'receita', '#065F46', 'award'),
(1, 'Juros Recebidos', 'receita', '#064E3B', 'trending-up'),
(1, 'Outras Receitas', 'receita', '#6B7280', 'plus-circle'),

-- Despesas Operacionais
(1, 'Salários e Encargos', 'despesa', '#EF4444', 'users'),
(1, 'Aluguel', 'despesa', '#DC2626', 'home'),
(1, 'Energia Elétrica', 'despesa', '#B91C1C', 'zap'),
(1, 'Telefone/Internet', 'despesa', '#991B1B', 'phone'),
(1, 'Material de Escritório', 'despesa', '#7F1D1D', 'edit-3'),

-- Despesas Administrativas
(1, 'Contabilidade', 'despesa', '#F59E0B', 'calculator'),
(1, 'Advocacia', 'despesa', '#D97706', 'shield'),
(1, 'Seguros', 'despesa', '#B45309', 'umbrella'),
(1, 'Licenças de Software', 'despesa', '#92400E', 'monitor'),
(1, 'Marketing', 'despesa', '#78350F', 'megaphone'),

-- Despesas Financeiras
(1, 'Juros Pagos', 'despesa', '#8B5CF6', 'trending-down'),
(1, 'Tarifas Bancárias', 'despesa', '#7C3AED', 'credit-card'),
(1, 'Impostos', 'despesa', '#6D28D9', 'file-text');

-- =====================================================
-- INSERIR CONTAS PADRÃO
-- =====================================================
INSERT INTO financeiro_contas (empresa_id, nome, tipo, banco, padrao) VALUES
(1, 'Conta Corrente Principal', 'conta_corrente', 'Banco do Brasil', 1),
(1, 'Cartão de Crédito Empresarial', 'cartao_credito', 'Banco do Brasil', 0),
(1, 'Dinheiro em Caixa', 'dinheiro', NULL, 0);

-- =====================================================
-- TRIGGER: Atualizar orçamento realizado automaticamente
-- =====================================================
DELIMITER $$

CREATE TRIGGER trigger_atualizar_orcamento_insert
    AFTER INSERT ON financeiro_transacoes
    FOR EACH ROW
BEGIN
    IF NEW.status = 'pago' THEN
        -- Atualizar valor realizado no orçamento
        INSERT INTO financeiro_orcamento (empresa_id, ano_mes, categoria, tipo, valor_orcado, valor_realizado)
        VALUES (NEW.empresa_id, DATE_FORMAT(NEW.data_competencia, '%Y-%m'), NEW.categoria, NEW.tipo, 0, NEW.valor_pago)
        ON DUPLICATE KEY UPDATE 
        valor_realizado = valor_realizado + NEW.valor_pago;
    END IF;
END$$

CREATE TRIGGER trigger_atualizar_orcamento_update
    AFTER UPDATE ON financeiro_transacoes
    FOR EACH ROW
BEGIN
    IF OLD.status != 'pago' AND NEW.status = 'pago' THEN
        -- Transação foi marcada como paga
        INSERT INTO financeiro_orcamento (empresa_id, ano_mes, categoria, tipo, valor_orcado, valor_realizado)
        VALUES (NEW.empresa_id, DATE_FORMAT(NEW.data_competencia, '%Y-%m'), NEW.categoria, NEW.tipo, 0, NEW.valor_pago)
        ON DUPLICATE KEY UPDATE 
        valor_realizado = valor_realizado + NEW.valor_pago;
    ELSEIF OLD.status = 'pago' AND NEW.status != 'pago' THEN
        -- Transação foi desmarcada como paga
        UPDATE financeiro_orcamento 
        SET valor_realizado = valor_realizado - OLD.valor_pago
        WHERE empresa_id = OLD.empresa_id 
        AND ano_mes = DATE_FORMAT(OLD.data_competencia, '%Y-%m')
        AND categoria = OLD.categoria 
        AND tipo = OLD.tipo;
    END IF;
END$$

DELIMITER ;

-- =====================================================
-- FUNÇÃO: Processar fluxo de caixa diário (para cron job)
-- =====================================================
DELIMITER $$

CREATE PROCEDURE ProcessarFluxoCaixaDiario(IN empresa_id_param INT)
BEGIN
    DECLARE data_ontem DATE DEFAULT DATE_SUB(CURDATE(), INTERVAL 1 DAY);
    DECLARE saldo_inicial_val DECIMAL(15,2) DEFAULT 0.00;
    DECLARE receitas_dia_val DECIMAL(15,2) DEFAULT 0.00;
    DECLARE despesas_dia_val DECIMAL(15,2) DEFAULT 0.00;
    DECLARE receitas_30_dias DECIMAL(15,2) DEFAULT 0.00;
    DECLARE despesas_30_dias DECIMAL(15,2) DEFAULT 0.00;

    -- Buscar saldo inicial (saldo final do dia anterior)
    SELECT COALESCE(saldo_final, 0) INTO saldo_inicial_val
    FROM financeiro_fluxo_caixa 
    WHERE empresa_id = empresa_id_param 
    AND data_referencia = DATE_SUB(data_ontem, INTERVAL 1 DAY)
    LIMIT 1;

    -- Calcular receitas e despesas do dia
    SELECT 
        COALESCE(SUM(CASE WHEN tipo = 'receita' AND status = 'pago' THEN valor_pago END), 0),
        COALESCE(SUM(CASE WHEN tipo = 'despesa' AND status = 'pago' THEN valor_pago END), 0)
    INTO receitas_dia_val, despesas_dia_val
    FROM financeiro_transacoes
    WHERE empresa_id = empresa_id_param 
    AND data_pagamento = data_ontem;

    -- Calcular projeções para próximos 30 dias
    SELECT 
        COALESCE(SUM(CASE WHEN tipo = 'receita' THEN valor END), 0),
        COALESCE(SUM(CASE WHEN tipo = 'despesa' THEN valor END), 0)
    INTO receitas_30_dias, despesas_30_dias
    FROM financeiro_transacoes
    WHERE empresa_id = empresa_id_param 
    AND status = 'pendente'
    AND data_vencimento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY);

    -- Inserir/atualizar registro do fluxo de caixa
    INSERT INTO financeiro_fluxo_caixa (
        empresa_id, data_referencia, saldo_inicial, receitas_dia, despesas_dia,
        receitas_previstas_proximos_30_dias, despesas_previstas_proximos_30_dias
    ) VALUES (
        empresa_id_param, data_ontem, saldo_inicial_val, receitas_dia_val, despesas_dia_val,
        receitas_30_dias, despesas_30_dias
    ) ON DUPLICATE KEY UPDATE
        saldo_inicial = saldo_inicial_val,
        receitas_dia = receitas_dia_val,
        despesas_dia = despesas_dia_val,
        receitas_previstas_proximos_30_dias = receitas_30_dias,
        despesas_previstas_proximos_30_dias = despesas_30_dias,
        processado_em = NOW();
END$$

DELIMITER ;

-- =====================================================
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- =====================================================
ALTER TABLE financeiro_transacoes 
    ADD INDEX idx_vencimento_status (data_vencimento, status),
    ADD INDEX idx_pagamento_tipo (data_pagamento, tipo),
    ADD INDEX idx_empresa_recorrente (empresa_id, recorrente);

-- =====================================================
-- VIEWS PARA RELATÓRIOS RÁPIDOS
-- =====================================================
CREATE OR REPLACE VIEW view_resumo_financeiro_mensal AS
SELECT 
    t.empresa_id,
    e.nome as empresa_nome,
    DATE_FORMAT(t.data_competencia, '%Y-%m') as ano_mes,
    t.tipo,
    t.categoria,
    COUNT(*) as quantidade_transacoes,
    SUM(t.valor) as valor_orcado,
    SUM(CASE WHEN t.status = 'pago' THEN t.valor_pago ELSE 0 END) as valor_realizado,
    SUM(CASE WHEN t.status = 'pendente' THEN t.valor ELSE 0 END) as valor_pendente,
    ROUND(
        CASE 
            WHEN SUM(t.valor) = 0 THEN 0 
            ELSE (SUM(CASE WHEN t.status = 'pago' THEN t.valor_pago ELSE 0 END) / SUM(t.valor)) * 100
        END, 2
    ) as percentual_realizado
FROM financeiro_transacoes t
JOIN empresas e ON t.empresa_id = e.id
GROUP BY t.empresa_id, e.nome, ano_mes, t.tipo, t.categoria;

CREATE OR REPLACE VIEW view_contas_vencidas AS
SELECT 
    t.*,
    e.nome as empresa_nome,
    DATEDIFF(CURDATE(), t.data_vencimento) as dias_vencido,
    CASE 
        WHEN DATEDIFF(CURDATE(), t.data_vencimento) <= 7 THEN 'Recém vencida'
        WHEN DATEDIFF(CURDATE(), t.data_vencimento) <= 30 THEN 'Vencida há 1 mês'
        WHEN DATEDIFF(CURDATE(), t.data_vencimento) <= 90 THEN 'Vencida há 3 meses'
        ELSE 'Vencida há mais de 3 meses'
    END as classificacao_atraso
FROM financeiro_transacoes t
JOIN empresas e ON t.empresa_id = e.id
WHERE t.status = 'pendente' AND t.data_vencimento < CURDATE();