-- =====================================================
-- O Consultor — Migration 016: Configuração de APIs de IA F-14
-- Data: 2026-06-27
-- Descrição: Sistema de configuração de APIs de IA com criptografia
-- =====================================================

USE o_consultor;

-- =====================================================
-- ATUALIZAR TABELA: configuracoes
-- =====================================================
-- Adicionar campos para configurações de APIs de IA se não existirem
INSERT IGNORE INTO configuracoes (chave, valor, descricao, grupo_config, criptografado) VALUES

-- PERPLEXITY API
('perplexity_ativo', '0', 'Ativar/desativar API Perplexity', 'api_ia', 0),
('perplexity_key', '', 'Chave da API Perplexity (pplx-xxx)', 'api_ia', 1),
('perplexity_modelo', 'llama-3.1-sonar-small-128k-online', 'Modelo Perplexity para busca', 'api_ia', 0),

-- OPENAI API  
('openai_ativo', '0', 'Ativar/desativar API OpenAI (GPT)', 'api_ia', 0),
('openai_key', '', 'Chave da API OpenAI (sk-xxx)', 'api_ia', 1),
('openai_modelo', 'gpt-4', 'Modelo OpenAI padrão', 'api_ia', 0),
('openai_modelo_imagem', 'dall-e-3', 'Modelo DALL-E para imagens', 'api_ia', 0),

-- ANTHROPIC API (CLAUDE)
('anthropic_ativo', '0', 'Ativar/desativar API Anthropic (Claude)', 'api_ia', 0),
('anthropic_key', '', 'Chave da API Anthropic (sk-ant-xxx)', 'api_ia', 1),
('anthropic_modelo', 'claude-3-sonnet-20240229', 'Modelo Claude padrão', 'api_ia', 0),

-- CONFIGURAÇÕES AVANÇADAS
('api_timeout', '30', 'Timeout para chamadas de API (segundos)', 'api_ia', 0),
('api_max_tokens', '4000', 'Máximo de tokens por request', 'api_ia', 0),
('api_temperature', '0.7', 'Temperatura padrão (0-1)', 'api_ia', 0),
('api_fallback_enabled', '1', 'Habilitar fallback entre APIs', 'api_ia', 0),

-- CONFIGURAÇÕES DE CRIPTOGRAFIA
('encryption_method', 'AES-256-CBC', 'Método de criptografia para chaves sensíveis', 'seguranca', 0);

-- =====================================================
-- TABELA: api_usage_log
-- =====================================================
CREATE TABLE IF NOT EXISTS api_usage_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provedor ENUM('openai', 'perplexity', 'anthropic') NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    modelo VARCHAR(100) NULL,
    tokens_usados INT UNSIGNED NULL,
    custo_estimado DECIMAL(10,4) NULL,
    tempo_resposta_ms INT UNSIGNED NULL,
    status_http INT NULL,
    sucesso TINYINT(1) NOT NULL DEFAULT 0,
    erro_detalhes TEXT NULL,
    usuario_id INT UNSIGNED NULL,
    empresa_id INT UNSIGNED NULL,
    contexto JSON NULL COMMENT 'Dados sobre o uso (módulo, funcionalidade, etc)',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_provedor (provedor),
    INDEX idx_usuario (usuario_id),
    INDEX idx_empresa (empresa_id),
    INDEX idx_data (criado_em),
    CONSTRAINT fk_api_log_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_api_log_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: api_status_cache
-- =====================================================
CREATE TABLE IF NOT EXISTS api_status_cache (
    provedor ENUM('openai', 'perplexity', 'anthropic') NOT NULL PRIMARY KEY,
    status ENUM('ativa', 'erro', 'timeout', 'desconhecido') NOT NULL DEFAULT 'desconhecido',
    ultimo_teste DATETIME NULL,
    erro_detalhes TEXT NULL,
    tempo_resposta_ms INT UNSIGNED NULL,
    proximo_teste DATETIME NULL,
    tentativas_falhas INT UNSIGNED NOT NULL DEFAULT 0,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir status inicial
INSERT IGNORE INTO api_status_cache (provedor, status) VALUES 
('openai', 'desconhecido'),
('perplexity', 'desconhecido'),
('anthropic', 'desconhecido');

-- =====================================================
-- DADOS DE EXEMPLO: Modelos disponíveis
-- =====================================================
INSERT IGNORE INTO configuracoes (chave, valor, descricao, grupo_config, criptografado) VALUES

-- MODELOS DISPONÍVEIS (para dropdowns)
('modelos_openai', '["gpt-4", "gpt-4-turbo", "gpt-3.5-turbo"]', 'Modelos OpenAI disponíveis', 'api_ia', 0),
('modelos_perplexity', '["llama-3.1-sonar-small-128k-online", "llama-3.1-sonar-large-128k-online", "llama-3.1-sonar-huge-128k-online"]', 'Modelos Perplexity disponíveis', 'api_ia', 0),
('modelos_anthropic', '["claude-3-sonnet-20240229", "claude-3-opus-20240229", "claude-3-haiku-20240307"]', 'Modelos Anthropic disponíveis', 'api_ia', 0),
('modelos_dalle', '["dall-e-3", "dall-e-2"]', 'Modelos DALL-E disponíveis', 'api_ia', 0);

-- =====================================================
-- TRIGGER: Log de mudanças em configurações sensíveis
-- =====================================================
DELIMITER //
CREATE TRIGGER IF NOT EXISTS config_api_change_log 
AFTER UPDATE ON configuracoes
FOR EACH ROW BEGIN
    IF OLD.grupo_config = 'api_ia' AND OLD.criptografado = 1 AND OLD.valor != NEW.valor THEN
        INSERT INTO logs_sistema (acao, descricao, usuario_id, criado_em)
        VALUES (
            'config_api_alterada',
            CONCAT('Chave de API alterada: ', NEW.chave),
            @current_user_id,
            NOW()
        );
    END IF;
END//
DELIMITER ;