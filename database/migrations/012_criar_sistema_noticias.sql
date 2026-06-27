-- =====================================================
-- O Consultor — Migration 012: Sistema de Notícias por IA
-- Data: 2026-06-27
-- Descrição: F-09 - Sistema completo de notícias com Perplexity + GPT/Claude
-- =====================================================

USE o_consultor;

-- =====================================================
-- TABELA: noticias (Notícias processadas pela IA)
-- =====================================================
CREATE TABLE IF NOT EXISTS noticias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    titulo VARCHAR(500) NOT NULL,
    url VARCHAR(1000) NOT NULL,
    fonte VARCHAR(255) NOT NULL,
    data_publicacao DATE NOT NULL,
    categoria ENUM('Mercado', 'Tecnologia', 'Regulamentação', 'Tendência', 'Negócio') NOT NULL,
    relevancia ENUM('alta', 'media', 'baixa') NOT NULL DEFAULT 'media',
    setor VARCHAR(100) NOT NULL,
    
    -- Conteúdo processado pela IA (5 blocos)
    bloco1_noticia TEXT NOT NULL COMMENT 'O que aconteceu (factual)',
    bloco2_significa TEXT NOT NULL COMMENT 'O que significa para o setor',
    bloco3_o_que_fazer TEXT NOT NULL COMMENT 'Ações práticas (lista)',
    bloco4_pergunta VARCHAR(500) NOT NULL COMMENT 'Pergunta estratégica',
    bloco5_conexao TEXT NOT NULL COMMENT 'Conexão com módulos O Consultor',
    
    -- Metadados
    tags JSON NULL COMMENT 'Array de tags/palavras-chave',
    resumo_bruto TEXT NULL COMMENT 'Resumo original do Perplexity',
    processado_via ENUM('perplexity+gpt', 'perplexity+claude', 'claude_fallback', 'gpt_fallback') NOT NULL,
    
    -- Controle
    visualizada TINYINT(1) NOT NULL DEFAULT 0,
    favoritada TINYINT(1) NOT NULL DEFAULT 0,
    arquivada TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_empresa (empresa_id),
    INDEX idx_url (url(191)) COMMENT 'Evitar duplicatas',
    INDEX idx_data_publicacao (data_publicacao),
    INDEX idx_categoria (categoria),
    INDEX idx_relevancia (relevancia),
    INDEX idx_setor (setor),
    INDEX idx_visualizada (visualizada),
    INDEX idx_criado_em (criado_em),
    
    CONSTRAINT fk_noticias_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ATUALIZAR TABELA: empresa_perfil_busca (melhorar estrutura)
-- =====================================================
ALTER TABLE empresa_perfil_busca 
ADD COLUMN IF NOT EXISTS ativo TINYINT(1) NOT NULL DEFAULT 1 AFTER site_url,
ADD COLUMN IF NOT EXISTS adicionado_por ENUM('usuario', 'ia') NOT NULL DEFAULT 'usuario' AFTER ativo,
ADD INDEX IF NOT EXISTS idx_ativo (ativo),
ADD INDEX IF NOT EXISTS idx_adicionado_por (adicionado_por);

-- =====================================================
-- TABELA: busca_logs (Log de buscas automáticas)
-- =====================================================
CREATE TABLE IF NOT EXISTS busca_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    tipo_busca ENUM('automatica', 'manual') NOT NULL,
    api_utilizada ENUM('perplexity', 'openai', 'anthropic') NOT NULL,
    sites_referencia JSON NOT NULL COMMENT 'Sites usados na busca',
    noticias_encontradas INT UNSIGNED NOT NULL DEFAULT 0,
    noticias_novas INT UNSIGNED NOT NULL DEFAULT 0,
    noticias_duplicadas INT UNSIGNED NOT NULL DEFAULT 0,
    tempo_processamento INT UNSIGNED NULL COMMENT 'Segundos',
    sucesso TINYINT(1) NOT NULL DEFAULT 1,
    erro_detalhes TEXT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_empresa (empresa_id),
    INDEX idx_tipo_busca (tipo_busca),
    INDEX idx_api_utilizada (api_utilizada),
    INDEX idx_criado_em (criado_em),
    INDEX idx_sucesso (sucesso),
    
    CONSTRAINT fk_busca_logs_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- CONFIGURAÇÕES ADICIONAIS (se não existirem)
-- =====================================================
INSERT IGNORE INTO configuracoes (chave, valor, grupo_config, descricao, criptografado) VALUES
('noticias_ativo', '1', 'apis', 'Sistema de notícias ativo', 0),
('noticias_frequencia_horas', '24', 'apis', 'Intervalo entre buscas automáticas (horas)', 0),
('noticias_max_por_busca', '20', 'apis', 'Máximo de notícias por busca', 0),
('noticias_dias_historico', '30', 'apis', 'Dias de histórico a manter', 0);

-- =====================================================
-- DADOS INICIAIS PARA TESTE
-- =====================================================
-- Os dados serão criados dinamicamente pelo sistema