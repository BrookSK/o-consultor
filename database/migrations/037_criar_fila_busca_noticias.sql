-- =====================================================
-- O Consultor — Migration 037: Fila de busca de notícias
-- Data: 2026-07-08
-- Descrição: A busca de notícias faz 1 chamada de busca (Perplexity/IA) +
--            1 chamada de análise POR notícia encontrada (até 10). Rodando
--            tudo em uma única requisição HTTP, isso facilmente passa dos
--            60-120s e o proxy (Nginx/Apache) mata a conexão com 504/timeout
--            — mesmo que as notícias já tenham sido salvas no banco.
--            Esta fila permite processar a busca em pequenos passos
--            (1 chamada de IA por vez), do mesmo jeito que a geração de SOPs
--            (ver fila_geracao_sop), sem sofrer timeout do proxy.
-- =====================================================

USE o_consultor;

CREATE TABLE IF NOT EXISTS fila_busca_noticias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    tipo_busca ENUM('manual', 'automatica') NOT NULL DEFAULT 'manual',
    status ENUM('pendente', 'processando', 'concluido', 'erro') NOT NULL DEFAULT 'pendente',
    etapa ENUM('buscar', 'analisar') NOT NULL DEFAULT 'buscar',
    log_id INT UNSIGNED NULL,
    api_utilizada VARCHAR(20) NULL,
    noticias_pendentes JSON NULL COMMENT 'Notícias já buscadas, ainda não analisadas/salvas',
    noticias_encontradas INT UNSIGNED NOT NULL DEFAULT 0,
    noticias_novas INT UNSIGNED NOT NULL DEFAULT 0,
    noticias_duplicadas INT UNSIGNED NOT NULL DEFAULT 0,
    mensagem VARCHAR(500) NULL,
    tentativas INT UNSIGNED NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    iniciado_em DATETIME NULL,
    concluido_em DATETIME NULL,
    atualizado_em DATETIME NULL,
    INDEX idx_status (status),
    INDEX idx_empresa (empresa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
