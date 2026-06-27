-- =====================================================
-- O Consultor — Migration 006: Tabela Diagnósticos Rascunho
-- Data: 2026-06-26
-- Descrição: Criar tabela para salvar rascunho dos diagnósticos por blocos
-- =====================================================

USE o_consultor;

-- =====================================================
-- TABELA: diagnosticos_rascunho
-- =====================================================
CREATE TABLE IF NOT EXISTS diagnosticos_rascunho (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NULL,
    usuario_id INT UNSIGNED NOT NULL,
    bloco_atual INT NOT NULL DEFAULT 1,
    status ENUM('em_andamento', 'concluido') NOT NULL DEFAULT 'em_andamento',
    
    -- Bloco 1: Identificação da Empresa
    empresa_nome VARCHAR(255) NULL,
    setor VARCHAR(100) NULL,
    descricao TEXT NULL,
    tempo_existencia VARCHAR(50) NULL,
    estrutura_societaria VARCHAR(50) NULL,
    unidades_filiais INT NULL,
    lingua_principal VARCHAR(50) NULL DEFAULT 'Português',
    
    -- Bloco 2: Estrutura Operacional
    colaboradores_internos INT NULL,
    colaboradores_externos INT NULL,
    departamentos JSON NULL,
    clientes_ativos INT NULL,
    produtos_servicos TEXT NULL,
    faturamento_mensal VARCHAR(100) NULL,
    ticket_medio VARCHAR(100) NULL,
    sites_referencia TEXT NULL,
    
    -- Bloco 3: Operação Atual
    processo_entrega TEXT NULL,
    ferramentas_softwares TEXT NULL,
    fornecedores_criticos TEXT NULL,
    dependencia_pessoa TEXT NULL,
    integracoes TEXT NULL,
    processos_documentados INT NULL,
    ferramentas_gestao JSON NULL,
    
    -- Bloco 4: Problemas e Riscos
    problemas_operacionais TEXT NULL,
    riscos_identificados TEXT NULL,
    incidentes_tipo VARCHAR(255) NULL,
    incidentes_descricao TEXT NULL,
    areas_vulneraveis JSON NULL,
    cliente_concentrado ENUM('sim', 'nao') NULL,
    fornecedor_insubstituivel ENUM('sim', 'nao') NULL,
    processos_sem_backup ENUM('sim', 'nao') NULL,
    
    -- Bloco 5: Contexto Estratégico
    pontos_fortes TEXT NULL,
    pontos_melhoria TEXT NULL,
    objetivo_12_meses TEXT NULL,
    maturidade_percebida INT NULL,
    planejamento_documentado ENUM('sim', 'nao') NULL,
    frequencia_reunioes VARCHAR(50) NULL,
    meta_faturamento ENUM('sim', 'nao') NULL,
    
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_usuario (usuario_id),
    INDEX idx_empresa (empresa_id),
    INDEX idx_status (status),
    CONSTRAINT fk_diagnosticos_rascunho_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_diagnosticos_rascunho_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: empresa_perfil_busca (Sites de referência)
-- =====================================================
CREATE TABLE IF NOT EXISTS empresa_perfil_busca (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    site_url VARCHAR(500) NOT NULL,
    categoria VARCHAR(100) NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    sugerido_por_ia TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_empresa (empresa_id),
    CONSTRAINT fk_perfil_busca_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar campos na tabela empresas para salvar resultado do diagnóstico
ALTER TABLE empresas 
ADD COLUMN score_maturidade INT NULL AFTER segmento,
ADD COLUMN lingua_principal VARCHAR(50) NULL AFTER score_maturidade,
ADD COLUMN faturamento_mensal VARCHAR(100) NULL AFTER lingua_principal,
ADD COLUMN colaboradores_internos INT NULL AFTER faturamento_mensal,
ADD COLUMN principal_desafio TEXT NULL AFTER colaboradores_internos;