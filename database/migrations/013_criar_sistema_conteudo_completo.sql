-- =====================================================
-- O Consultor — Migration 013: Sistema Completo de Conteúdo F-10
-- Data: 2026-06-27
-- Descrição: Estrutura completa para geração de conteúdo com DALL-E
-- =====================================================

USE o_consultor;

-- =====================================================
-- TABELA: marcas (Marcas do cliente)
-- =====================================================
CREATE TABLE IF NOT EXISTS marcas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,
    nome VARCHAR(255) NOT NULL,
    nicho VARCHAR(100) NULL,
    publico_alvo TEXT NULL,
    tom VARCHAR(50) NULL,
    arquetipo VARCHAR(50) NULL,
    paleta_cores JSON NULL,
    fonte_principal VARCHAR(100) NULL,
    fonte_secundaria VARCHAR(100) NULL,
    estilo_visual TEXT NULL,
    prompt_master LONGTEXT NULL,
    prompt_dalle LONGTEXT NULL,
    brand_book_criado TINYINT(1) NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_empresa (empresa_id),
    INDEX idx_ativo (ativo),
    CONSTRAINT fk_marcas_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: conteudos_marca (Conteúdos gerados para marcas)
-- =====================================================
CREATE TABLE IF NOT EXISTS conteudos_marca (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    marca_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    tipo ENUM('carrossel', 'post', 'story', 'reels') NOT NULL,
    tema VARCHAR(255) NOT NULL,
    objetivo VARCHAR(100) NULL,
    noticia_id INT UNSIGNED NULL,
    slides JSON NULL,
    legenda TEXT NULL,
    hashtags VARCHAR(500) NULL,
    status ENUM('rascunho', 'aprovado', 'agendado', 'publicado') NOT NULL DEFAULT 'rascunho',
    agendado_para DATETIME NULL,
    data_publicacao_real DATETIME NULL,
    canal_publicacao VARCHAR(50) NULL,
    metadados_publicacao JSON NULL,
    imagens_locais JSON NULL,
    dados_geracao JSON NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_marca (marca_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_status (status),
    INDEX idx_agendado (agendado_para),
    CONSTRAINT fk_conteudos_marca FOREIGN KEY (marca_id) REFERENCES marcas(id) ON DELETE CASCADE,
    CONSTRAINT fk_conteudos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: imagens_conteudo (Imagens locais dos conteúdos)
-- =====================================================
CREATE TABLE IF NOT EXISTS imagens_conteudo (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conteudo_id INT UNSIGNED NOT NULL,
    slide_index INT NOT NULL,
    caminho_original VARCHAR(500) NOT NULL,
    caminho_local VARCHAR(500) NOT NULL,
    url_dalle VARCHAR(500) NULL,
    prompt_usado TEXT NULL,
    tamanho_arquivo INT UNSIGNED NOT NULL DEFAULT 0,
    dimensoes VARCHAR(20) NULL,
    status ENUM('ativo', 'substituido', 'removido') NOT NULL DEFAULT 'ativo',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_conteudo (conteudo_id),
    INDEX idx_slide (conteudo_id, slide_index),
    CONSTRAINT fk_imagens_conteudo FOREIGN KEY (conteudo_id) REFERENCES conteudos_marca(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERIR MARCAS DE EXEMPLO
-- =====================================================
INSERT IGNORE INTO marcas (id, empresa_id, nome, nicho, publico_alvo, tom, arquetipo, paleta_cores, fonte_principal, fonte_secundaria, estilo_visual, prompt_master, prompt_dalle, brand_book_criado) VALUES
(1, 1, 'Tech Solutions', 'Tecnologia/MSP', 'Empresários de PMEs que precisam de TI gerenciada', 'Semiformal', 'Sábio', '["#1E3A5F", "#E07B00", "#FFFFFF", "#F5F7FA", "#1a7a1a"]', 'Inter', 'Roboto Mono', 'Tecnológico, clean, fundo escuro com destaques em laranja', 'Você é especialista em marketing digital B2B para empresas de MSP e TI. Crie conteúdo educativo que posicione a marca como especialista técnica mas acessível.', 'Imagem tecnológica, clean, fundo azul escuro (#1E3A5F), elementos geométricos sutis, ícones de tecnologia/cloud/segurança em destaque, estilo corporativo moderno, sem texto sobreposto, iluminação suave gradiente', 1),
(2, 2, 'Varejo Express', 'Varejo', 'Lojistas e comerciantes locais', 'Amigável', 'Companheiro', '["#FF6B35", "#F7F7F7", "#2C3E50", "#3498DB", "#E74C3C"]', 'Poppins', 'Open Sans', 'Vibrante, acolhedor, cores quentes', 'Especialista em varejo local com foco em vendas e relacionamento com cliente. Linguagem próxima e prática.', 'Ambiente comercial vibrante, cores quentes laranja e azul, elementos de vendas e atendimento, estilo amigável e acolhedor, sem pessoas, sem texto na imagem', 1),
(3, 2, 'FoodService Pro', 'Alimentação', 'Restaurantes, bares e food service', 'Profissional', 'Especialista', '["#8B4513", "#FF8C00", "#FFFFFF", "#228B22", "#DC143C"]', 'Montserrat', 'Lato', 'Clean, gastronômico, foco em qualidade', 'Consultoria especializada em food service. Foco em processos, qualidade e rentabilidade do setor alimentício.', 'Ambiente gastronômico profissional, tons terrosos e alaranjados, utensílios e equipamentos de cozinha, estilo clean e higiênico, sem pessoas, sem texto', 0);