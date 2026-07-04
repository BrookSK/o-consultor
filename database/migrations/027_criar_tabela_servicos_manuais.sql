-- Migração 027: Criar tabela para serviços manuais
-- Data: 2026-07-04
-- Objetivo: Permitir adição, edição e exclusão manual de serviços em cada setor

CREATE TABLE IF NOT EXISTS servicos_manuais (
    id INT PRIMARY KEY AUTO_INCREMENT,
    estrutura_id INT NOT NULL,
    setor_nome VARCHAR(255) NOT NULL,
    servico_nome VARCHAR(500) NOT NULL,
    criticidade ENUM('baixa', 'media', 'alta') DEFAULT 'media',
    descricao TEXT NULL,
    categoria VARCHAR(100) DEFAULT 'manual',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    empresa_id INT NOT NULL,
    
    -- Índices para performance
    INDEX idx_estrutura_setor (estrutura_id, setor_nome),
    INDEX idx_empresa (empresa_id),
    INDEX idx_criado_em (criado_em),
    
    -- Evitar duplicatas
    UNIQUE KEY unique_servico_setor (estrutura_id, setor_nome, servico_nome, empresa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comentários da tabela
ALTER TABLE servicos_manuais COMMENT = 'Armazena serviços adicionados manualmente pelos usuários em cada setor';

-- Verificar se a tabela foi criada
SELECT 'Tabela servicos_manuais criada com sucesso' as status;