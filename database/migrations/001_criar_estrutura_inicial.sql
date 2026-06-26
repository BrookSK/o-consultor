-- =====================================================
-- O Consultor — Migration 001: Estrutura Inicial
-- Data: 2026-06-26
-- Descrição: Criação das tabelas base do sistema
-- =====================================================

-- Criar banco de dados
CREATE DATABASE IF NOT EXISTS o_consultor
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE o_consultor;

-- =====================================================
-- TABELA: empresas
-- =====================================================
CREATE TABLE IF NOT EXISTS empresas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    cnpj VARCHAR(18) NULL,
    segmento VARCHAR(100) NULL,
    telefone VARCHAR(20) NULL,
    responsavel_id INT UNSIGNED NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cnpj (cnpj),
    INDEX idx_segmento (segmento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: usuarios
-- =====================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    perfil ENUM('ADMIN_HOLDING', 'CONSULTOR_INTERNO', 'CLIENTE') NOT NULL DEFAULT 'CLIENTE',
    empresa_id INT UNSIGNED NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    ultimo_login DATETIME NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_perfil (perfil),
    INDEX idx_empresa (empresa_id),
    CONSTRAINT fk_usuarios_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: diagnosticos
-- =====================================================
CREATE TABLE IF NOT EXISTS diagnosticos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    respostas JSON NULL,
    pontuacao INT NOT NULL DEFAULT 0,
    status ENUM('em_andamento', 'concluido') NOT NULL DEFAULT 'em_andamento',
    observacoes TEXT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_empresa (empresa_id),
    INDEX idx_usuario (usuario_id),
    CONSTRAINT fk_diagnosticos_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    CONSTRAINT fk_diagnosticos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: planos_acao
-- =====================================================
CREATE TABLE IF NOT EXISTS planos_acao (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    diagnostico_id INT UNSIGNED NULL,
    titulo VARCHAR(255) NOT NULL,
    area VARCHAR(100) NULL,
    prioridade ENUM('alta', 'media', 'baixa') NOT NULL DEFAULT 'media',
    status ENUM('pendente', 'em_andamento', 'concluido') NOT NULL DEFAULT 'pendente',
    prazo DATE NULL,
    responsavel VARCHAR(255) NULL,
    descricao TEXT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_empresa (empresa_id),
    INDEX idx_status (status),
    CONSTRAINT fk_planos_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    CONSTRAINT fk_planos_diagnostico FOREIGN KEY (diagnostico_id) REFERENCES diagnosticos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: sops (Procedimentos Operacionais)
-- =====================================================
CREATE TABLE IF NOT EXISTS sops (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    departamento VARCHAR(100) NULL,
    conteudo LONGTEXT NOT NULL,
    versao VARCHAR(10) NOT NULL DEFAULT '1.0',
    status ENUM('rascunho', 'ativo', 'arquivado') NOT NULL DEFAULT 'rascunho',
    gerado_por_ia TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_empresa (empresa_id),
    INDEX idx_departamento (departamento),
    CONSTRAINT fk_sops_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: conteudos (Central de Conteúdo)
-- =====================================================
CREATE TABLE IF NOT EXISTS conteudos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    tipo ENUM('artigo', 'video', 'template', 'ebook') NOT NULL,
    categoria VARCHAR(100) NULL,
    descricao TEXT NULL,
    url VARCHAR(500) NULL,
    duracao VARCHAR(20) NULL,
    publicado_em DATETIME NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tipo (tipo),
    INDEX idx_categoria (categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: parceiros
-- =====================================================
CREATE TABLE IF NOT EXISTS parceiros (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    categoria VARCHAR(100) NULL,
    contato VARCHAR(255) NULL,
    telefone VARCHAR(20) NULL,
    website VARCHAR(255) NULL,
    status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_categoria (categoria),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: logs_sistema
-- =====================================================
CREATE TABLE IF NOT EXISTS logs_sistema (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NULL,
    acao VARCHAR(255) NOT NULL,
    descricao TEXT NULL,
    ip VARCHAR(45) NULL,
    user_agent TEXT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario_id),
    INDEX idx_acao (acao),
    INDEX idx_criado (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: notificacoes
-- =====================================================
CREATE TABLE IF NOT EXISTS notificacoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    tipo ENUM('email', 'webhook', 'sistema') NOT NULL DEFAULT 'sistema',
    titulo VARCHAR(255) NOT NULL,
    mensagem TEXT NULL,
    lida TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario_id),
    INDEX idx_lida (lida),
    CONSTRAINT fk_notificacoes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: conteudos_gerados (Máquina de Conteúdo)
-- =====================================================
CREATE TABLE IF NOT EXISTS conteudos_gerados (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    tema VARCHAR(255) NOT NULL,
    plataforma VARCHAR(50) NULL,
    tom VARCHAR(50) NULL,
    conteudo_texto LONGTEXT NULL,
    conteudo_imagem_url VARCHAR(500) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario_id),
    CONSTRAINT fk_conteudos_gerados_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DADOS INICIAIS (Seed)
-- =====================================================

-- Empresa padrão
INSERT INTO empresas (nome, cnpj, segmento) VALUES
('Holding Digital', '00.000.000/0001-00', 'Tecnologia'),
('Empresa Exemplo Ltda', '11.111.111/0001-11', 'Comércio');

-- Usuários iniciais (senhas: admin123, consultor123, cliente123)
INSERT INTO usuarios (nome, email, senha, perfil, empresa_id) VALUES
('Administrador', 'admin@oconsultor.com.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ADMIN_HOLDING', NULL),
('João Consultor', 'consultor@oconsultor.com.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'CONSULTOR_INTERNO', NULL),
('Maria Cliente', 'cliente@empresa.com.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'CLIENTE', 2);
