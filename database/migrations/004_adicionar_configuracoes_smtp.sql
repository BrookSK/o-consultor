-- =====================================================
-- O Consultor — Migration 004: Configurações de SMTP/Email
-- Data: 2026-06-26
-- Descrição: Adiciona configurações de envio de email via SMTP
--            Gerenciadas pela tela /admin/configuracoes (aba Email)
-- =====================================================

USE o_consultor;

-- Grupo: smtp
INSERT INTO configuracoes (chave, valor, grupo_config, descricao, criptografado) VALUES
('smtp_host', '', 'smtp', 'Servidor SMTP (ex: smtp.gmail.com, smtp.hostinger.com)', 0),
('smtp_porta', '587', 'smtp', 'Porta SMTP (587 para TLS, 465 para SSL, 25 sem criptografia)', 0),
('smtp_usuario', '', 'smtp', 'Usuário/Email de autenticação SMTP', 0),
('smtp_senha', '', 'smtp', 'Senha do SMTP (criptografada no banco)', 1),
('smtp_criptografia', 'tls', 'smtp', 'Tipo de criptografia (tls / ssl / nenhuma)', 0),
('smtp_remetente_email', '', 'smtp', 'Email do remetente (From)', 0),
('smtp_remetente_nome', 'O Consultor', 'smtp', 'Nome do remetente exibido', 0),
('smtp_ativo', '0', 'smtp', 'Toggle: envio de emails ativo', 0);

-- Tabela de fila de emails (para controle e reenvio)
CREATE TABLE IF NOT EXISTS emails_enviados (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    destinatario_email VARCHAR(255) NOT NULL,
    destinatario_nome VARCHAR(255) NULL,
    assunto VARCHAR(255) NOT NULL,
    corpo TEXT NOT NULL,
    tipo ENUM('recuperacao_senha', 'convite_academy', 'notificacao', 'alerta', 'boas_vindas', 'outro') NOT NULL DEFAULT 'outro',
    status ENUM('pendente', 'enviado', 'erro') NOT NULL DEFAULT 'pendente',
    erro_mensagem TEXT NULL,
    tentativas TINYINT UNSIGNED NOT NULL DEFAULT 0,
    enviado_em DATETIME NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_tipo (tipo),
    INDEX idx_destinatario (destinatario_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
