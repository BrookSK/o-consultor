-- =====================================================
-- O Consultor — Migration 015: Sistema de Gestão de Cliente F-13
-- Data: 2026-06-27
-- Descrição: Sistema administrativo completo para gestão de clientes
-- =====================================================

USE o_consultor;

-- =====================================================
-- ATUALIZAR TABELA: empresas
-- =====================================================
ALTER TABLE empresas 
ADD COLUMN IF NOT EXISTS status ENUM('ativo', 'pausado', 'cancelado', 'suspenso') NOT NULL DEFAULT 'ativo' AFTER principal_desafio,
ADD COLUMN IF NOT EXISTS consultor_id INT UNSIGNED NULL AFTER status,
ADD COLUMN IF NOT EXISTS mrr DECIMAL(10,2) NULL COMMENT 'Monthly Recurring Revenue' AFTER consultor_id,
ADD COLUMN IF NOT EXISTS data_contratacao DATE NULL AFTER mrr,
ADD COLUMN IF NOT EXISTS observacoes_admin TEXT NULL AFTER data_contratacao,
ADD COLUMN IF NOT EXISTS endereco TEXT NULL AFTER observacoes_admin,
ADD COLUMN IF NOT EXISTS cidade VARCHAR(100) NULL AFTER endereco,
ADD COLUMN IF NOT EXISTS estado VARCHAR(2) NULL AFTER cidade,
ADD COLUMN IF NOT EXISTS cep VARCHAR(10) NULL AFTER estado,
ADD COLUMN IF NOT EXISTS website VARCHAR(255) NULL AFTER cep,
ADD INDEX IF NOT EXISTS idx_status (status),
ADD INDEX IF NOT EXISTS idx_consultor (consultor_id);

-- Adicionar constraint para consultor_id após verificar se não existe
SET @constraint_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
                         WHERE CONSTRAINT_SCHEMA = 'o_consultor' 
                         AND TABLE_NAME = 'empresas' 
                         AND CONSTRAINT_NAME = 'fk_empresas_consultor');

SET @sql = IF(@constraint_exists = 0, 
              'ALTER TABLE empresas ADD CONSTRAINT fk_empresas_consultor FOREIGN KEY (consultor_id) REFERENCES usuarios(id) ON DELETE SET NULL',
              'SELECT "Constraint already exists" as message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- ATUALIZAR TABELA: usuarios
-- =====================================================
ALTER TABLE usuarios 
ADD COLUMN IF NOT EXISTS senha_temporaria TINYINT(1) NOT NULL DEFAULT 0 AFTER senha,
ADD COLUMN IF NOT EXISTS primeiro_acesso TINYINT(1) NOT NULL DEFAULT 1 AFTER senha_temporaria,
ADD COLUMN IF NOT EXISTS telefone VARCHAR(20) NULL AFTER email,
ADD COLUMN IF NOT EXISTS cargo VARCHAR(100) NULL AFTER telefone,
ADD COLUMN IF NOT EXISTS data_nascimento DATE NULL AFTER cargo,
ADD COLUMN IF NOT EXISTS resetar_senha_token VARCHAR(255) NULL AFTER data_nascimento,
ADD COLUMN IF NOT EXISTS resetar_senha_expira DATETIME NULL AFTER resetar_senha_token,
ADD INDEX IF NOT EXISTS idx_senha_token (resetar_senha_token);

-- =====================================================
-- TABELA: historico_cliente
-- =====================================================
CREATE TABLE IF NOT EXISTS historico_cliente (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    usuario_admin_id INT UNSIGNED NOT NULL,
    tipo_acao ENUM('criacao', 'troca_consultor', 'mudanca_status', 'alteracao_dados', 'cancelamento') NOT NULL,
    dados_anteriores JSON NULL,
    dados_novos JSON NULL,
    observacoes TEXT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_empresa (empresa_id),
    INDEX idx_admin (usuario_admin_id),
    INDEX idx_tipo (tipo_acao),
    INDEX idx_data (criado_em),
    CONSTRAINT fk_historico_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    CONSTRAINT fk_historico_admin FOREIGN KEY (usuario_admin_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: emails_enviados
-- =====================================================
CREATE TABLE IF NOT EXISTS emails_enviados (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    destinatario_email VARCHAR(255) NOT NULL,
    assunto VARCHAR(255) NOT NULL,
    corpo_html LONGTEXT NULL,
    corpo_texto TEXT NULL,
    tipo ENUM('boas_vindas', 'troca_consultor', 'cancelamento', 'geral') NOT NULL,
    status ENUM('enviado', 'falhado', 'pendente') NOT NULL DEFAULT 'pendente',
    erro_detalhes TEXT NULL,
    empresa_id INT UNSIGNED NULL,
    usuario_id INT UNSIGNED NULL,
    enviado_em DATETIME NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_destinatario (destinatario_email),
    INDEX idx_tipo (tipo),
    INDEX idx_status (status),
    INDEX idx_empresa (empresa_id),
    CONSTRAINT fk_emails_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE SET NULL,
    CONSTRAINT fk_emails_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ATUALIZAR DADOS: Configurar consultores existentes
-- =====================================================
UPDATE usuarios SET perfil = 'CONSULTOR_INTERNO' WHERE email = 'consultor@oconsultor.com.br';

-- Associar empresas existentes aos consultores
UPDATE empresas SET consultor_id = (
    SELECT id FROM usuarios WHERE perfil = 'CONSULTOR_INTERNO' LIMIT 1
) WHERE consultor_id IS NULL AND id > 1;

-- =====================================================
-- INSERIR DADOS DE EXEMPLO
-- =====================================================

-- Inserir mais consultores para exemplo
INSERT IGNORE INTO usuarios (nome, email, senha, perfil, telefone, cargo) VALUES
('Ana Consultora', 'ana.consultora@oconsultor.com.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'CONSULTOR_INTERNO', '(11) 99999-1234', 'Consultora Senior'),
('Carlos Consultor', 'carlos.consultor@oconsultor.com.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'CONSULTOR_INTERNO', '(11) 99999-5678', 'Consultor Senior'),
('Lucia Consultora', 'lucia.consultora@oconsultor.com.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'CONSULTOR_INTERNO', '(11) 99999-9012', 'Consultora Plena');

-- Inserir empresas de exemplo com dados completos
INSERT IGNORE INTO empresas (
    nome, cnpj, segmento, telefone, endereco, cidade, estado, cep, website,
    status, consultor_id, mrr, data_contratacao, colaboradores_internos, 
    faturamento_mensal, score_maturidade, observacoes_admin
) VALUES
('TechSolutions Pro Ltda', '12.345.678/0001-90', 'Tecnologia', '(11) 3456-7890', 
 'Av. Paulista, 1000 - Sala 501', 'São Paulo', 'SP', '01310-100', 'https://techsolutions.com.br',
 'ativo', (SELECT id FROM usuarios WHERE email = 'ana.consultora@oconsultor.com.br'), 4500.00, '2026-03-01', 25, 
 'R$ 150.000 - R$ 300.000', 3, 'Cliente com boa maturidade tecnológica'),

('Digital Commerce SA', '23.456.789/0001-01', 'E-commerce', '(11) 2345-6789',
 'Rua Augusta, 500 - 8º andar', 'São Paulo', 'SP', '01305-000', 'https://digitalcommerce.com.br',
 'ativo', (SELECT id FROM usuarios WHERE email = 'carlos.consultor@oconsultor.com.br'), 3200.00, '2026-04-15', 18,
 'R$ 80.000 - R$ 150.000', 2, 'Em crescimento acelerado no digital'),

('Varejo Express Ltda', '34.567.890/0001-12', 'Varejo', '(11) 1234-5678',
 'Rua das Palmeiras, 200', 'São Paulo', 'SP', '01234-000', NULL,
 'ativo', (SELECT id FROM usuarios WHERE email = 'lucia.consultora@oconsultor.com.br'), 2800.00, '2026-05-01', 35,
 'R$ 300.000 - R$ 500.000', 2, 'Empresa tradicional modernizando processos'),

('FoodService Brasil', '45.678.901/0001-23', 'Alimentação', '(11) 9876-5432',
 'Av. Brigadeiro, 300 - Conjunto 12', 'São Paulo', 'SP', '04567-000', 'https://foodservice.com.br',
 'pausado', (SELECT id FROM usuarios WHERE email = 'ana.consultora@oconsultor.com.br'), 0.00, '2026-05-10', 12,
 'R$ 50.000 - R$ 80.000', 1, 'Contrato pausado temporariamente - questões internas'),

('Construtora ABC Ltda', '56.789.012/0001-34', 'Construção Civil', '(11) 8765-4321',
 'Rua dos Engenheiros, 150', 'São Paulo', 'SP', '05432-100', 'https://construtorabc.com.br',
 'cancelado', (SELECT id FROM usuarios WHERE email = 'carlos.consultor@oconsultor.com.br'), 0.00, '2026-02-01', 80,
 'R$ 1.000.000+', 4, 'Projeto concluído com sucesso - cliente não renovou'),

('StartupTech Innovation', '67.890.123/0001-45', 'Tecnologia', '(11) 7654-3210',
 'Rua Startup, 50 - Hub Inovação', 'São Paulo', 'SP', '01234-567', 'https://startuptech.io',
 'ativo', (SELECT id FROM usuarios WHERE email = 'lucia.consultora@oconsultor.com.br'), 1500.00, '2026-06-01', 8,
 'R$ 30.000 - R$ 50.000', 1, 'Startup em estágio inicial - grande potencial');

-- Atualizar empresas existentes com novos dados
UPDATE empresas SET 
    status = 'ativo',
    consultor_id = (SELECT id FROM usuarios WHERE perfil = 'CONSULTOR_INTERNO' LIMIT 1),
    mrr = 2000.00,
    data_contratacao = '2026-01-15'
WHERE id <= 2 AND nome IN ('Holding Digital', 'Empresa Exemplo Ltda');

-- =====================================================
-- CRIAR TRIGGER PARA HISTORICO
-- =====================================================
DELIMITER //
CREATE TRIGGER IF NOT EXISTS empresa_historico_update 
AFTER UPDATE ON empresas
FOR EACH ROW BEGIN
    IF OLD.status != NEW.status OR OLD.consultor_id != NEW.consultor_id THEN
        INSERT INTO historico_cliente (empresa_id, usuario_admin_id, tipo_acao, dados_anteriores, dados_novos, criado_em)
        VALUES (
            NEW.id,
            @admin_user_id, -- Será setado no PHP
            CASE 
                WHEN OLD.status != NEW.status THEN 'mudanca_status'
                WHEN OLD.consultor_id != NEW.consultor_id THEN 'troca_consultor'
                ELSE 'alteracao_dados'
            END,
            JSON_OBJECT('status', OLD.status, 'consultor_id', OLD.consultor_id),
            JSON_OBJECT('status', NEW.status, 'consultor_id', NEW.consultor_id),
            NOW()
        );
    END IF;
END//
DELIMITER ;