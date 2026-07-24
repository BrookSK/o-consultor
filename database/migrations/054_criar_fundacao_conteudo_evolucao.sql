-- =====================================================
-- O Consultor — Migration 054: Fundação da Evolução da Central e Máquina de Conteúdo
-- Data: 2026-07-24
-- Descrição: Estrutura de dados (aditiva, não destrutiva) para as novas
--            funcionalidades da Central e da Máquina de Conteúdo:
--              - Configurações de Conteúdo por empresa
--              - Datas comemorativas (base normalizada, por nicho/região)
--              - Calendário editorial personalizado
--              - Scrap da Concorrência (concorrentes, coletas, posts, análises)
--
--            Todas as tabelas são criadas com IF NOT EXISTS e isoladas por
--            empresa_id (isolamento multi-tenant). Nenhuma tabela existente é
--            alterada de forma destrutiva; o fluxo atual de geração de conteúdo
--            (notícia/tema/biblioteca) permanece intacto.
-- =====================================================

USE o_consultor;

-- =====================================================
-- TABELA: configuracoes_conteudo
-- Configurações gerais de conteúdo por empresa. Guarda o padrão da empresa
-- para geração (ex.: gerar imagens automaticamente), fontes permitidas,
-- região/fuso e regras de repetição de temas.
-- =====================================================
CREATE TABLE IF NOT EXISTS configuracoes_conteudo (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,

    -- Frequência e formato padrão
    frequencia_padrao ENUM('diaria','3_dias','semanal','quinzenal','mensal') NOT NULL DEFAULT 'semanal',
    redes_sociais JSON NULL,               -- ["instagram","linkedin",...]
    formatos_preferidos JSON NULL,         -- ["carrossel","post","reels",...]
    idioma VARCHAR(50) NOT NULL DEFAULT 'Português',

    -- Localização (usada para datas regionais e contexto)
    pais VARCHAR(80) NULL DEFAULT 'Brasil',
    estado VARCHAR(80) NULL,
    cidade VARCHAR(120) NULL,
    regiao VARCHAR(120) NULL,
    fuso_horario VARCHAR(60) NOT NULL DEFAULT 'America/Sao_Paulo',

    -- Datas comemorativas / sugestões
    antecedencia_datas_dias INT UNSIGNED NOT NULL DEFAULT 7,
    qtd_sugestoes_semanais INT UNSIGNED NOT NULL DEFAULT 3,

    -- Fontes permitidas (o fluxo atual de notícias continua funcionando por padrão)
    permitir_noticias TINYINT(1) NOT NULL DEFAULT 1,
    permitir_concorrencia TINYINT(1) NOT NULL DEFAULT 1,
    permitir_datas_comemorativas TINYINT(1) NOT NULL DEFAULT 1,

    -- Geração de imagens: padrão da empresa (pode ser sobrescrito por geração)
    gerar_imagens_padrao TINYINT(1) NOT NULL DEFAULT 1,

    -- Anti-repetição de temas
    evitar_repeticao_temas TINYINT(1) NOT NULL DEFAULT 1,
    periodo_repeticao_dias INT UNSIGNED NOT NULL DEFAULT 30,

    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_empresa (empresa_id),
    CONSTRAINT fk_config_conteudo_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: datas_comemorativas
-- Base normalizada de datas (padrão, nacionais, regionais, profissionais,
-- setoriais, sazonais). Datas globais (empresa_id NULL) são compartilhadas;
-- datas específicas de uma empresa têm empresa_id preenchido. A IA classifica
-- a relevância por nicho/região.
-- =====================================================
CREATE TABLE IF NOT EXISTS datas_comemorativas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NULL,          -- NULL = data global/base; preenchido = específica da empresa

    nome VARCHAR(255) NOT NULL,
    tipo ENUM('nacional','internacional','regional','estadual','municipal',
              'profissional','comercial','sazonal','setorial','institucional') NOT NULL DEFAULT 'nacional',

    -- Ocorrência: mês/dia para recorrência anual; data_unica para eventos pontuais
    mes TINYINT UNSIGNED NULL,             -- 1-12 (recorrência anual)
    dia TINYINT UNSIGNED NULL,             -- 1-31 (recorrência anual)
    data_unica DATE NULL,                  -- evento de data única (não recorrente)
    recorrencia ENUM('anual','unica') NOT NULL DEFAULT 'anual',

    -- Abrangência geográfica
    pais VARCHAR(80) NULL DEFAULT 'Brasil',
    estado VARCHAR(80) NULL,
    municipio VARCHAR(120) NULL,
    regiao VARCHAR(120) NULL,

    -- Segmentação
    nichos JSON NULL,                      -- ["agronegocio","tecnologia",...]
    subnichos JSON NULL,

    -- Relevância (classificada pela IA por empresa quando aplicável)
    relevancia ENUM('alta','media','baixa','nao_recomendada') NULL,
    antecedencia_dias INT UNSIGNED NOT NULL DEFAULT 7,

    fonte VARCHAR(255) NULL,               -- origem da informação (base própria, serviço externo, IA)
    ativo TINYINT(1) NOT NULL DEFAULT 1,

    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_empresa (empresa_id),
    INDEX idx_tipo (tipo),
    INDEX idx_ocorrencia (mes, dia),
    INDEX idx_relevancia (relevancia),
    INDEX idx_ativo (ativo),
    CONSTRAINT fk_datas_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: calendario_conteudo
-- Calendário editorial personalizado por empresa. Cada item é uma sugestão de
-- publicação com origem (notícia, data comemorativa, concorrência, tema livre,
-- tendência). Liga-se opcionalmente ao conteúdo gerado na Máquina de Conteúdo.
-- =====================================================
CREATE TABLE IF NOT EXISTS calendario_conteudo (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,

    tema VARCHAR(255) NOT NULL,
    origem ENUM('noticia','data_comemorativa','concorrencia','conteudo_semanal','tema_manual','tendencia') NOT NULL DEFAULT 'tema_manual',

    -- Referências opcionais à origem
    noticia_id INT UNSIGNED NULL,
    data_comemorativa_id INT UNSIGNED NULL,
    concorrente_id INT UNSIGNED NULL,
    conteudo_id INT UNSIGNED NULL,         -- conteúdo gerado (conteudos_marca) quando houver

    data_evento DATE NULL,                 -- quando o evento/data ocorre
    data_publicacao_sugerida DATE NULL,    -- quando publicar (aplica antecedência)
    antecedencia_dias INT UNSIGNED NULL,

    formato_recomendado VARCHAR(80) NULL,  -- carrossel, post, reels...
    objetivo VARCHAR(120) NULL,
    responsavel VARCHAR(255) NULL,
    gerar_imagem TINYINT(1) NOT NULL DEFAULT 1,

    status ENUM('sugerido','planejado','gerado','em_revisao','aprovado','publicado','ignorado') NOT NULL DEFAULT 'sugerido',
    observacoes TEXT NULL,

    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_empresa (empresa_id),
    INDEX idx_status (status),
    INDEX idx_origem (origem),
    INDEX idx_data_pub (data_publicacao_sugerida),
    CONSTRAINT fk_calendario_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: concorrentes
-- Perfis públicos de concorrentes monitorados por empresa (isolado por empresa).
-- =====================================================
CREATE TABLE IF NOT EXISTS concorrentes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT UNSIGNED NOT NULL,

    nome VARCHAR(255) NOT NULL,            -- nome do concorrente
    nome_perfil VARCHAR(255) NULL,         -- @perfil / handle
    url_publica VARCHAR(500) NOT NULL,
    plataforma ENUM('instagram','linkedin','facebook','tiktok','youtube','blog') NOT NULL DEFAULT 'instagram',
    descricao TEXT NULL,
    categoria VARCHAR(120) NULL,           -- categoria/nicho

    frequencia_coleta ENUM('manual','diaria','3_dias','semanal','quinzenal','mensal') NOT NULL DEFAULT 'manual',
    max_posts_por_coleta INT UNSIGNED NOT NULL DEFAULT 12,

    principal TINYINT(1) NOT NULL DEFAULT 0,   -- concorrente principal
    status ENUM('ativo','pausado') NOT NULL DEFAULT 'ativo',

    seguidores INT UNSIGNED NULL,          -- quando publicamente disponível (para taxa de engajamento)
    observacoes TEXT NULL,
    data_inicio_acompanhamento DATE NULL,

    ultima_coleta_em DATETIME NULL,
    proxima_coleta_em DATETIME NULL,
    falhas_consecutivas INT UNSIGNED NOT NULL DEFAULT 0,

    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_empresa (empresa_id),
    INDEX idx_status (status),
    INDEX idx_plataforma (plataforma),
    INDEX idx_proxima_coleta (proxima_coleta_em),
    CONSTRAINT fk_concorrentes_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: concorrente_coletas
-- Histórico de coletas. Cada execução cria um registro (não sobrescreve),
-- permitindo comparação de crescimento e evolução ao longo do tempo.
-- =====================================================
CREATE TABLE IF NOT EXISTS concorrente_coletas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    concorrente_id INT UNSIGNED NOT NULL,
    empresa_id INT UNSIGNED NOT NULL,      -- desnormalizado p/ isolamento e consultas

    origem ENUM('manual','agendada') NOT NULL DEFAULT 'manual',
    status ENUM('pendente','processando','concluida','erro','parcial') NOT NULL DEFAULT 'pendente',

    -- Snapshot de perfil no momento da coleta
    seguidores_snapshot INT UNSIGNED NULL,
    posts_coletados INT UNSIGNED NOT NULL DEFAULT 0,

    -- Diagnóstico da coleta
    tipo_erro VARCHAR(80) NULL,            -- timeout, bloqueio, captcha, perfil_privado, etc.
    mensagem VARCHAR(1000) NULL,
    creditos_utilizados INT UNSIGNED NULL,

    iniciada_em DATETIME NULL,
    finalizada_em DATETIME NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_concorrente (concorrente_id),
    INDEX idx_empresa (empresa_id),
    INDEX idx_status (status),
    CONSTRAINT fk_coletas_concorrente FOREIGN KEY (concorrente_id) REFERENCES concorrentes(id) ON DELETE CASCADE,
    CONSTRAINT fk_coletas_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: concorrente_posts
-- Snapshot de cada publicação coletada. Métricas são NULÁVEIS de propósito:
-- NULL = não disponível/não coletada; 0 = valor real zero. Nunca inventar valores.
-- Cada coleta gera novos snapshots (ligados a concorrente_coletas) para
-- preservar histórico e permitir comparação de evolução.
-- =====================================================
CREATE TABLE IF NOT EXISTS concorrente_posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    concorrente_id INT UNSIGNED NOT NULL,
    coleta_id INT UNSIGNED NOT NULL,
    empresa_id INT UNSIGNED NOT NULL,      -- desnormalizado p/ isolamento

    post_ref VARCHAR(255) NULL,            -- ID/shortcode público do post na plataforma
    url VARCHAR(500) NULL,
    plataforma ENUM('instagram','linkedin','facebook','tiktok','youtube','blog') NOT NULL,
    tipo_conteudo ENUM('imagem','carrossel','reels','video','story','texto','artigo','link','live','short') NULL,

    data_publicacao DATETIME NULL,
    titulo VARCHAR(500) NULL,
    texto MEDIUMTEXT NULL,                 -- legenda/corpo
    hashtags JSON NULL,
    mencoes JSON NULL,

    imagem_capa_url VARCHAR(500) NULL,
    qtd_imagens INT UNSIGNED NULL,
    duracao_video_seg INT UNSIGNED NULL,

    -- Métricas públicas (NULL = não disponível; diferenciar de 0)
    curtidas INT UNSIGNED NULL,
    comentarios INT UNSIGNED NULL,
    visualizacoes INT UNSIGNED NULL,
    compartilhamentos INT UNSIGNED NULL,
    reacoes INT UNSIGNED NULL,

    -- Métricas calculadas (quando houver dados suficientes)
    engajamento_absoluto INT UNSIGNED NULL,
    taxa_engajamento DECIMAL(8,4) NULL,    -- % estimada (engajamento / seguidores * 100)

    metricas_indisponiveis JSON NULL,      -- lista das métricas não coletadas nesta publicação
    conteudo_bruto MEDIUMTEXT NULL,        -- HTML/JSON bruto para auditoria
    fonte_coleta VARCHAR(120) NULL,        -- ex.: scrapingbee
    status_coleta ENUM('ok','parcial','removido') NOT NULL DEFAULT 'ok',

    coletado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_concorrente (concorrente_id),
    INDEX idx_coleta (coleta_id),
    INDEX idx_empresa (empresa_id),
    INDEX idx_plataforma (plataforma),
    INDEX idx_data_pub (data_publicacao),
    INDEX idx_engajamento (engajamento_absoluto),
    CONSTRAINT fk_posts_concorrente FOREIGN KEY (concorrente_id) REFERENCES concorrentes(id) ON DELETE CASCADE,
    CONSTRAINT fk_posts_coleta FOREIGN KEY (coleta_id) REFERENCES concorrente_coletas(id) ON DELETE CASCADE,
    CONSTRAINT fk_posts_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: concorrente_analises
-- Resultado da análise automática (IA) após uma coleta: padrões, temas,
-- formatos, ganchos, CTAs, horários, oportunidades e lacunas. Guarda JSON
-- estruturado + resumo textual, ligado à coleta que a originou.
-- =====================================================
CREATE TABLE IF NOT EXISTS concorrente_analises (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    concorrente_id INT UNSIGNED NOT NULL,
    coleta_id INT UNSIGNED NULL,
    empresa_id INT UNSIGNED NOT NULL,

    resumo TEXT NULL,                      -- resumo textual da análise
    dados JSON NULL,                       -- estrutura: top_posts, formatos, temas, ganchos, ctas, horarios, lacunas...
    oportunidades JSON NULL,               -- oportunidades para o cliente

    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_concorrente (concorrente_id),
    INDEX idx_coleta (coleta_id),
    INDEX idx_empresa (empresa_id),
    CONSTRAINT fk_analises_concorrente FOREIGN KEY (concorrente_id) REFERENCES concorrentes(id) ON DELETE CASCADE,
    CONSTRAINT fk_analises_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- FIM DA MIGRATION 054
-- =====================================================
