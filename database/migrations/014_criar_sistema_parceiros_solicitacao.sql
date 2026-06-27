-- =====================================================
-- O Consultor — Migration 014: Sistema de Solicitação de Parceiros F-12
-- Data: 2026-06-27
-- Descrição: Sistema completo para acionamento de parceiros via plano de ação
-- =====================================================

USE o_consultor;

-- =====================================================
-- TABELA: solicitacoes_parceiro
-- =====================================================
CREATE TABLE IF NOT EXISTS solicitacoes_parceiro (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tarefa_id INT UNSIGNED NOT NULL,
    parceiro_id INT UNSIGNED NOT NULL,
    empresa_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    descricao_necessidade TEXT NOT NULL,
    urgencia ENUM('baixa', 'media', 'alta', 'critica') NOT NULL DEFAULT 'media',
    status ENUM('solicitado', 'em_contato', 'em_execucao', 'concluido', 'cancelado') NOT NULL DEFAULT 'solicitado',
    observacoes_admin TEXT NULL,
    data_contato DATETIME NULL,
    data_inicio_execucao DATETIME NULL,
    data_conclusao DATETIME NULL,
    avaliacao_cliente INT NULL CHECK (avaliacao_cliente >= 1 AND avaliacao_cliente <= 5),
    comentario_avaliacao TEXT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tarefa (tarefa_id),
    INDEX idx_parceiro (parceiro_id),
    INDEX idx_empresa (empresa_id),
    INDEX idx_status (status),
    INDEX idx_urgencia (urgencia),
    CONSTRAINT fk_solicitacoes_tarefa FOREIGN KEY (tarefa_id) REFERENCES plano_tarefas(id) ON DELETE CASCADE,
    CONSTRAINT fk_solicitacoes_parceiro FOREIGN KEY (parceiro_id) REFERENCES parceiros(id) ON DELETE CASCADE,
    CONSTRAINT fk_solicitacoes_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    CONSTRAINT fk_solicitacoes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ATUALIZAR TABELA: parceiros (adicionar campos necessários)
-- =====================================================
ALTER TABLE parceiros ADD COLUMN IF NOT EXISTS areas_atuacao JSON NULL AFTER categoria;
ALTER TABLE parceiros ADD COLUMN IF NOT EXISTS status ENUM('pendente', 'homologado', 'suspenso', 'inativo') NOT NULL DEFAULT 'pendente' AFTER areas_atuacao;
ALTER TABLE parceiros ADD COLUMN IF NOT EXISTS nivel_experiencia ENUM('junior', 'pleno', 'senior', 'especialista') NULL AFTER status;
ALTER TABLE parceiros ADD COLUMN IF NOT EXISTS avaliacao_media DECIMAL(3,2) NULL AFTER nivel_experiencia;
ALTER TABLE parceiros ADD COLUMN IF NOT EXISTS total_solicitacoes INT UNSIGNED NOT NULL DEFAULT 0 AFTER avaliacao_media;

-- =====================================================
-- ATUALIZAR TABELA: plano_tarefas (adicionar flag parceiro)
-- =====================================================
ALTER TABLE plano_tarefas ADD COLUMN IF NOT EXISTS requer_parceiro TINYINT(1) NOT NULL DEFAULT 0 AFTER descricao;
ALTER TABLE plano_tarefas ADD COLUMN IF NOT EXISTS categoria_parceiro VARCHAR(100) NULL AFTER requer_parceiro;

-- =====================================================
-- INSERIR PARCEIROS DE EXEMPLO
-- =====================================================
INSERT IGNORE INTO parceiros (id, nome, categoria, areas_atuacao, contato, telefone, website, status, nivel_experiencia, avaliacao_media) VALUES
(1, 'TechSolutions Pro', 'Tecnologia', '["Infraestrutura de TI", "Segurança da Informação", "Cloud Computing"]', 'contato@techsolutions.com.br', '(11) 9999-1234', 'https://techsolutions.com.br', 'homologado', 'senior', 4.8),
(2, 'Consultoria Financeira Express', 'Financeiro', '["Gestão Financeira", "Controladoria", "Planejamento Tributário"]', 'atendimento@finexpress.com.br', '(11) 8888-5678', 'https://finexpress.com.br', 'homologado', 'especialista', 4.9),
(3, 'Marketing Digital Hub', 'Marketing', '["Marketing Digital", "Redes Sociais", "SEO/SEM"]', 'hello@mktdigital.com.br', '(11) 7777-9012', 'https://mktdigitalhub.com.br', 'homologado', 'pleno', 4.6),
(4, 'RH Estratégico', 'Recursos Humanos', '["Recrutamento", "Treinamentos", "Gestão de Pessoas"]', 'contato@rhestrategico.com.br', '(11) 6666-3456', 'https://rhestrategico.com.br', 'homologado', 'senior', 4.7),
(5, 'Jurídico Empresarial +', 'Jurídico', '["Direito Empresarial", "Trabalhista", "Contratos"]', 'advocacia@juridicopro.com.br', '(11) 5555-7890', 'https://juridicopro.com.br', 'homologado', 'especialista', 4.9),
(6, 'Consultoria Operacional', 'Operações', '["Processos", "Qualidade", "Lean Manufacturing"]', 'ops@consultoriaoperacional.com.br', '(11) 4444-2345', 'https://consultoriaoperacional.com.br', 'pendente', 'pleno', NULL);

-- =====================================================
-- INSERIR CATEGORIAS DE PARCEIROS PADRÃO
-- =====================================================
CREATE TABLE IF NOT EXISTS categorias_parceiro (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descricao TEXT NULL,
    areas_relacionadas JSON NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO categorias_parceiro (nome, descricao, areas_relacionadas) VALUES
('Tecnologia', 'Parceiros especializados em soluções tecnológicas', '["TI", "Sistemas", "Infraestrutura", "Segurança"]'),
('Financeiro', 'Consultores financeiros e contábeis', '["Contabilidade", "Financeiro", "Controladoria", "Tributário"]'),
('Marketing', 'Agências e consultores de marketing', '["Marketing", "Vendas", "Digital", "Comunicação"]'),
('Recursos Humanos', 'Consultores de gestão de pessoas', '["RH", "Pessoas", "Treinamento", "Recrutamento"]'),
('Jurídico', 'Escritórios de advocacia empresarial', '["Legal", "Jurídico", "Contratos", "Compliance"]'),
('Operações', 'Consultores de processos e operações', '["Processos", "Qualidade", "Operações", "Logística"]');

-- =====================================================
-- CRIAR TRIGGER PARA ATUALIZAR CONTADOR DE SOLICITAÇÕES
-- =====================================================
DELIMITER //
CREATE TRIGGER IF NOT EXISTS update_parceiro_stats AFTER INSERT ON solicitacoes_parceiro
FOR EACH ROW BEGIN
    UPDATE parceiros 
    SET total_solicitacoes = total_solicitacoes + 1 
    WHERE id = NEW.parceiro_id;
END//
DELIMITER ;