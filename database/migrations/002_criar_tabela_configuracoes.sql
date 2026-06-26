-- =====================================================
-- O Consultor — Migration 002: Tabela de Configurações
-- Data: 2026-06-26
-- Descrição: Todas as configurações do sistema (APIs, Academy, módulos, etc.)
--            são gerenciadas via interface admin e armazenadas nesta tabela.
--            Nenhuma chave ou secret fica em arquivo de código.
-- =====================================================

USE o_consultor;

-- =====================================================
-- TABELA: configuracoes
-- Armazena TODAS as configurações dinâmicas do sistema.
-- Valores sensíveis (chaves de API) são criptografados com AES-256-CBC.
-- =====================================================
CREATE TABLE IF NOT EXISTS configuracoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT NULL,
    grupo_config VARCHAR(50) NOT NULL DEFAULT 'geral',
    descricao VARCHAR(255) NULL,
    criptografado TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_grupo (grupo_config),
    INDEX idx_chave (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DADOS INICIAIS — Configurações padrão
-- Os valores de chaves de API ficam vazios. O admin preenche pela tela.
-- =====================================================

-- Grupo: geral
INSERT INTO configuracoes (chave, valor, grupo_config, descricao, criptografado) VALUES
('app_nome', 'O Consultor', 'geral', 'Nome da plataforma', 0),
('app_email_contato', 'contato@oconsultor.com.br', 'geral', 'Email de contato principal', 0),
('app_idioma', 'pt-BR', 'geral', 'Idioma padrão do sistema', 0),
('app_cor_primaria', '#1E3A5F', 'geral', 'Cor primária da identidade visual', 0),
('app_cor_accent', '#E07B00', 'geral', 'Cor de destaque (CTAs)', 0);

-- Grupo: api_openai
INSERT INTO configuracoes (chave, valor, grupo_config, descricao, criptografado) VALUES
('openai_key', '', 'api_openai', 'Chave de API da OpenAI (GPT + DALL-E)', 1),
('openai_modelo', 'gpt-4o', 'api_openai', 'Modelo padrão para geração de texto', 0),
('openai_modelo_mini', 'gpt-4o-mini', 'api_openai', 'Modelo econômico para tarefas simples', 0),
('openai_max_tokens', '8192', 'api_openai', 'Máximo de tokens por resposta', 0),
('openai_ativo', '0', 'api_openai', 'Toggle: API ativa ou inativa', 0);

-- Grupo: api_anthropic
INSERT INTO configuracoes (chave, valor, grupo_config, descricao, criptografado) VALUES
('anthropic_key', '', 'api_anthropic', 'Chave de API da Anthropic (Claude)', 1),
('anthropic_modelo', 'claude-sonnet-4-20250514', 'api_anthropic', 'Modelo padrão do Claude', 0),
('anthropic_ativo', '0', 'api_anthropic', 'Toggle: API ativa ou inativa', 0);

-- Grupo: api_perplexity
INSERT INTO configuracoes (chave, valor, grupo_config, descricao, criptografado) VALUES
('perplexity_key', '', 'api_perplexity', 'Chave de API da Perplexity', 1),
('perplexity_modelo', 'sonar-pro', 'api_perplexity', 'Modelo de busca (sonar / sonar-pro)', 0),
('perplexity_ativo', '0', 'api_perplexity', 'Toggle: API ativa ou inativa', 0);

-- Grupo: academy
INSERT INTO configuracoes (chave, valor, grupo_config, descricao, criptografado) VALUES
('academy_url', 'https://myacademy.com.br', 'academy', 'URL base da plataforma My Academy', 0),
('academy_jwt_secret', '', 'academy', 'Chave secreta JWT compartilhada para SSO', 1),
('academy_sso_rota', '/sso', 'academy', 'Rota de SSO na Academy', 0),
('academy_sso_parametro', 'token', 'academy', 'Nome do parâmetro do token na URL', 0),
('academy_ativo', '0', 'academy', 'Toggle: integração Academy ativa', 0);

-- Grupo: api_config (timeouts e retry)
INSERT INTO configuracoes (chave, valor, grupo_config, descricao, criptografado) VALUES
('api_timeout', '120', 'api_config', 'Timeout em segundos para chamadas de IA', 0),
('api_connect_timeout', '10', 'api_config', 'Timeout de conexão em segundos', 0),
('api_max_retries', '2', 'api_config', 'Número máximo de tentativas (1 original + retries)', 0);

-- Grupo: notificacoes
INSERT INTO configuracoes (chave, valor, grupo_config, descricao, criptografado) VALUES
('notif_diagnostico_concluido', '1', 'notificacoes', 'Notificar quando diagnóstico é concluído', 0),
('notif_sop_aprovado', '1', 'notificacoes', 'Notificar quando SOP é aprovado', 0),
('notif_kpi_vermelho', '1', 'notificacoes', 'Notificar quando KPI entra em zona vermelha', 0),
('notif_tarefa_vencida', '1', 'notificacoes', 'Notificar quando tarefa vence', 0),
('notif_conteudo_novo', '1', 'notificacoes', 'Notificar quando novo conteúdo disponível', 0),
('notif_novo_cadastro', '1', 'notificacoes', 'Notificar quando novo cliente se cadastra', 0),
('notif_login_inativo', '0', 'notificacoes', 'Notificar login após 30 dias inativo', 0);

-- Grupo: modulos (toggles por módulo — habilitar/desabilitar)
INSERT INTO configuracoes (chave, valor, grupo_config, descricao, criptografado) VALUES
('modulo_diagnostico', '1', 'modulos', 'Módulo de Diagnóstico Empresarial', 0),
('modulo_plano_acao', '1', 'modulos', 'Módulo de Plano de Ação', 0),
('modulo_manual_operacional', '1', 'modulos', 'Módulo de Manual Operacional (SOPs)', 0),
('modulo_central_conteudo', '1', 'modulos', 'Módulo Central de Conteúdo', 0),
('modulo_maquina_conteudo', '1', 'modulos', 'Módulo Máquina de Conteúdo', 0),
('modulo_academy', '1', 'modulos', 'Módulo Academy (SSO)', 0),
('modulo_parceiros', '1', 'modulos', 'Módulo de Parceiros', 0),
('modulo_governanca', '1', 'modulos', 'Módulo de Governança', 0);
