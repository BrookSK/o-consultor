-- =====================================================
-- O Consultor — Migration 052: Módulo Criador de Vídeos (Reels)
-- Data: 2026-07-11
-- Descrição: Projeto de vídeo vinculado ao POST (conteudos_marca) por ID.
--            Guarda o estado completo do editor em JSON (ordem, durações,
--            movimentos, transições, narração, música, textos, exportação).
--            Não altera nenhuma tabela/fluxo existente.
-- =====================================================

USE o_consultor;

-- Projeto de vídeo: 1 por conteúdo (post). Reaberto pelo conteudo_id.
CREATE TABLE IF NOT EXISTS video_projetos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conteudo_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    estado_json MEDIUMTEXT NULL COMMENT 'Estado completo do editor (JSON)',
    video_url VARCHAR(500) NULL COMMENT 'Último vídeo exportado',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL,
    UNIQUE KEY uk_conteudo (conteudo_id),
    INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fila de exportação de vídeo (processada em background pelo FFmpeg).
CREATE TABLE IF NOT EXISTS fila_videos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    projeto_id INT UNSIGNED NOT NULL,
    conteudo_id INT UNSIGNED NOT NULL,
    status ENUM('pendente','processando','concluido','erro','cancelado') NOT NULL DEFAULT 'pendente',
    progresso TINYINT UNSIGNED NOT NULL DEFAULT 0,
    etapa VARCHAR(120) NULL,
    tentativas INT UNSIGNED NOT NULL DEFAULT 0,
    mensagem VARCHAR(500) NULL,
    video_url VARCHAR(500) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL,
    INDEX idx_status (status),
    INDEX idx_projeto (projeto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
