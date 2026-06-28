-- =====================================================
-- O Consultor — Migration 019: Sistema de Agenda Completo
-- Data: 2026-06-28
-- Descrição: BLOCO GESTÃO - Agenda Pessoal e Reuniões de Emergência
-- =====================================================

USE o_consultor;

-- =====================================================
-- TABELA: agenda_compromissos (Agenda pessoal dos usuários)
-- =====================================================
CREATE TABLE IF NOT EXISTS agenda_compromissos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT NULL,
    data_inicio DATETIME NOT NULL,
    data_fim DATETIME NULL,
    tipo ENUM('reuniao', 'ligacao', 'tarefa', 'evento', 'cliente', 'revisao_sop') NOT NULL DEFAULT 'reuniao',
    prioridade ENUM('baixa', 'media', 'alta', 'critica') NOT NULL DEFAULT 'media',
    status ENUM('agendado', 'em_andamento', 'concluido', 'cancelado', 'adiado') NOT NULL DEFAULT 'agendado',
    participantes JSON NULL, -- Array de IDs de usuários ou e-mails externos
    localizacao VARCHAR(255) NULL,
    link_reuniao VARCHAR(500) NULL, -- Google Meet, Zoom, etc.
    observacoes TEXT NULL,
    lembrete_minutos INT NULL DEFAULT 15, -- Minutos antes para lembrar
    notificado TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_empresa_usuario (empresa_id, usuario_id),
    INDEX idx_data_inicio (data_inicio),
    INDEX idx_status (status),
    INDEX idx_tipo (tipo),
    CONSTRAINT fk_agenda_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    CONSTRAINT fk_agenda_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: agenda_emergencia (Reuniões de emergência N3)
-- =====================================================
CREATE TABLE IF NOT EXISTS agenda_emergencia (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    sop_id INT UNSIGNED NULL,
    alerta_id INT UNSIGNED NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT NOT NULL,
    data_agendamento DATETIME NOT NULL,
    prioridade ENUM('alta', 'critica') NOT NULL DEFAULT 'critica',
    status ENUM('agendado', 'em_andamento', 'resolvido', 'cancelado') NOT NULL DEFAULT 'agendado',
    tipo_emergencia ENUM('contencao_n3', 'falha_critica', 'revisao_urgente', 'auditoria') NOT NULL DEFAULT 'contencao_n3',
    participantes_obrigatorios JSON NOT NULL, -- IDs de usuários que devem participar
    resolucao TEXT NULL, -- Como foi resolvida a emergência
    acao_tomada TEXT NULL, -- Ação específica tomada
    data_resolucao DATETIME NULL,
    resolvido_por INT UNSIGNED NULL,
    impacto_operacional ENUM('baixo', 'medio', 'alto', 'critico') NOT NULL DEFAULT 'critico',
    tempo_resolucao INT NULL, -- Em minutos
    criado_automaticamente TINYINT(1) NOT NULL DEFAULT 1, -- Se foi criada pelo sistema
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_empresa (empresa_id),
    INDEX idx_sop (sop_id),
    INDEX idx_status (status),
    INDEX idx_data_agendamento (data_agendamento),
    INDEX idx_prioridade (prioridade),
    CONSTRAINT fk_emergencia_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    CONSTRAINT fk_emergencia_sop FOREIGN KEY (sop_id) REFERENCES sops(id) ON DELETE SET NULL,
    CONSTRAINT fk_emergencia_alerta FOREIGN KEY (alerta_id) REFERENCES alertas(id) ON DELETE SET NULL,
    CONSTRAINT fk_emergencia_resolvido FOREIGN KEY (resolvido_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: agenda_templates (Templates de compromissos recorrentes)
-- =====================================================
CREATE TABLE IF NOT EXISTS agenda_templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    nome_template VARCHAR(255) NOT NULL,
    titulo_padrao VARCHAR(255) NOT NULL,
    descricao_padrao TEXT NULL,
    tipo ENUM('reuniao', 'ligacao', 'tarefa', 'evento', 'cliente', 'revisao_sop') NOT NULL,
    duracao_minutos INT NOT NULL DEFAULT 60,
    prioridade ENUM('baixa', 'media', 'alta', 'critica') NOT NULL DEFAULT 'media',
    participantes_padrao JSON NULL,
    localizacao_padrao VARCHAR(255) NULL,
    lembrete_minutos INT NOT NULL DEFAULT 15,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_empresa_usuario (empresa_id, usuario_id),
    INDEX idx_ativo (ativo),
    CONSTRAINT fk_template_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    CONSTRAINT fk_template_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: agenda_notificacoes (Log de notificações enviadas)
-- =====================================================
CREATE TABLE IF NOT EXISTS agenda_notificacoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    compromisso_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    tipo_notificacao ENUM('lembrete', 'alteracao', 'cancelamento', 'urgencia') NOT NULL,
    canal ENUM('email', 'sistema', 'whatsapp', 'sms') NOT NULL DEFAULT 'sistema',
    enviado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status_envio ENUM('pendente', 'enviado', 'erro') NOT NULL DEFAULT 'pendente',
    erro_detalhes TEXT NULL,
    INDEX idx_compromisso (compromisso_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_status (status_envio),
    CONSTRAINT fk_notif_compromisso FOREIGN KEY (compromisso_id) REFERENCES agenda_compromissos(id) ON DELETE CASCADE,
    CONSTRAINT fk_notif_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERIR TEMPLATES PADRÃO DE COMPROMISSOS
-- =====================================================
INSERT INTO agenda_templates (empresa_id, usuario_id, nome_template, titulo_padrao, descricao_padrao, tipo, duracao_minutos, prioridade) VALUES
(1, 1, 'Reunião de Alinhamento', 'Reunião de Alinhamento Semanal', 'Reunião semanal para alinhamento de atividades e pendências', 'reuniao', 60, 'media'),
(1, 1, 'Ligação Comercial', 'Ligação com Cliente/Prospect', 'Ligação comercial para apresentação de proposta ou follow-up', 'ligacao', 30, 'alta'),
(1, 1, 'Revisão de SOP', 'Revisão de Procedimento Operacional', 'Revisão e atualização de SOP conforme ciclo de melhoria contínua', 'revisao_sop', 90, 'alta'),
(1, 1, 'Visita Cliente', 'Visita ao Cliente', 'Visita presencial para atendimento, apresentação ou suporte', 'cliente', 120, 'alta'),
(1, 1, 'Tarefa de Implementação', 'Implementação de Melhoria', 'Tarefa para implementar melhoria identificada em processo', 'tarefa', 240, 'media');

-- =====================================================
-- TRIGGER: Criar notificação automática para compromissos
-- =====================================================
DELIMITER $$

CREATE TRIGGER trigger_agenda_notificacao 
    AFTER INSERT ON agenda_compromissos 
    FOR EACH ROW
BEGIN
    -- Criar notificação de lembrete se tiver tempo definido
    IF NEW.lembrete_minutos IS NOT NULL AND NEW.lembrete_minutos > 0 THEN
        INSERT INTO agenda_notificacoes (compromisso_id, usuario_id, tipo_notificacao, canal, status_envio)
        VALUES (NEW.id, NEW.usuario_id, 'lembrete', 'sistema', 'pendente');
    END IF;
    
    -- Se é reunião crítica/emergência, notificar imediatamente
    IF NEW.prioridade = 'critica' THEN
        INSERT INTO agenda_notificacoes (compromisso_id, usuario_id, tipo_notificacao, canal, status_envio)
        VALUES (NEW.id, NEW.usuario_id, 'urgencia', 'sistema', 'pendente');
    END IF;
END$$

DELIMITER ;

-- =====================================================
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- =====================================================
ALTER TABLE agenda_compromissos 
    ADD INDEX idx_data_status (data_inicio, status),
    ADD INDEX idx_empresa_data (empresa_id, data_inicio),
    ADD INDEX idx_usuario_status (usuario_id, status);

ALTER TABLE agenda_emergencia 
    ADD INDEX idx_empresa_status (empresa_id, status),
    ADD INDEX idx_tipo_prioridade (tipo_emergencia, prioridade);