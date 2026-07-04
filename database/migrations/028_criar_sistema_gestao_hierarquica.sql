-- Migration para criar sistema hierárquico de gestão Setor > Serviços > SOPs
-- O Consultor - Sistema Operacional Empresarial

-- 1. Tabela para estruturas organizacionais permanentes (não temporárias)
CREATE TABLE IF NOT EXISTS `estruturas_organizacionais` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `diagnostico_id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `nome_empresa` varchar(255) NOT NULL,
  `nicho` varchar(200) NOT NULL,
  `macro_categoria` varchar(200) NOT NULL,
  `estrutura_completa_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_setores` int(11) DEFAULT 0,
  `status` enum('ativo', 'arquivado') DEFAULT 'ativo',
  `criado_em` datetime NOT NULL,
  `atualizado_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_diagnostico_id` (`diagnostico_id`),
  KEY `idx_empresa_id` (`empresa_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabela para setores da empresa
CREATE TABLE IF NOT EXISTS `setores_empresa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `estrutura_id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `nome_setor` varchar(100) NOT NULL,
  `tipo_setor` enum('core', 'apoio', 'estrategico') DEFAULT 'core',
  `descricao` text,
  `responsavel_sugerido` varchar(200),
  `prioridade` int(11) DEFAULT 1,
  `funcao_principal` text,
  `contexto_especifico` json DEFAULT NULL,
  `total_servicos` int(11) DEFAULT 0,
  `total_sops` int(11) DEFAULT 0,
  `status` enum('mapeado', 'em_desenvolvimento', 'concluido') DEFAULT 'mapeado',
  `criado_em` datetime NOT NULL,
  `atualizado_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_setor_estrutura` (`estrutura_id`, `nome_setor`),
  KEY `idx_empresa_id` (`empresa_id`),
  KEY `idx_status` (`status`),
  FOREIGN KEY (`estrutura_id`) REFERENCES `estruturas_organizacionais`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabela para serviços por setor (com detalhamento completo)
CREATE TABLE IF NOT EXISTS `servicos_setor` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setor_id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `nome_servico` varchar(255) NOT NULL,
  `codigo_servico` varchar(50) NOT NULL,
  `categoria` enum('core','operacional','estrategico','integracao','excecao','crise','conformidade','sazonal') DEFAULT 'operacional',
  `criticidade` enum('alta','media','baixa') DEFAULT 'media',
  `frequencia` enum('diaria','semanal','mensal','trimestral','anual','sob_demanda','emergencial') DEFAULT 'mensal',
  `complexidade` enum('simples','media','alta') DEFAULT 'media',
  `descricao_resumida` text,
  `detalhamento_completo` json DEFAULT NULL COMMENT 'Detalhamento da IA com processos, problemas, soluções N1/N2/N3',
  `integracao_setores` json DEFAULT NULL COMMENT 'Array com setores que interagem',
  `recursos_principais` json DEFAULT NULL COMMENT 'Array com recursos necessários',
  `tem_sop` boolean DEFAULT FALSE,
  `sop_id` int(11) DEFAULT NULL,
  `origem` enum('automatico','manual','audio_transcricao') DEFAULT 'automatico',
  `audio_transcricao` text DEFAULT NULL COMMENT 'Transcrição de áudio se origem foi áudio',
  `status` enum('mapeado','detalhado','sop_gerado','aprovado') DEFAULT 'mapeado',
  `criado_em` datetime NOT NULL,
  `detalhado_em` datetime DEFAULT NULL,
  `sop_gerado_em` datetime DEFAULT NULL,
  `atualizado_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_codigo_servico` (`codigo_servico`),
  KEY `idx_setor_id` (`setor_id`),
  KEY `idx_empresa_id` (`empresa_id`),
  KEY `idx_categoria` (`categoria`),
  KEY `idx_criticidade` (`criticidade`),
  KEY `idx_status` (`status`),
  KEY `idx_origem` (`origem`),
  KEY `idx_sop_id` (`sop_id`),
  FOREIGN KEY (`setor_id`) REFERENCES `setores_empresa`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabela para gestão de progresso hierárquico
CREATE TABLE IF NOT EXISTS `progresso_hierarquico` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `estrutura_id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `setores_mapeados` int(11) DEFAULT 0,
  `setores_total` int(11) DEFAULT 0,
  `servicos_mapeados` int(11) DEFAULT 0,
  `servicos_detalhados` int(11) DEFAULT 0,
  `servicos_com_sop` int(11) DEFAULT 0,
  `servicos_total` int(11) DEFAULT 0,
  `percentual_conclusao` decimal(5,2) DEFAULT 0.00,
  `etapa_atual` enum('mapeamento_setores','mapeamento_servicos','detalhamento_servicos','geracao_sops','concluido') DEFAULT 'mapeamento_setores',
  `ultima_atividade` datetime DEFAULT NULL,
  `criado_em` datetime NOT NULL,
  `atualizado_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_estrutura` (`estrutura_id`),
  KEY `idx_empresa_id` (`empresa_id`),
  KEY `idx_etapa_atual` (`etapa_atual`),
  FOREIGN KEY (`estrutura_id`) REFERENCES `estruturas_organizacionais`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Remover dependência da tabela sops_gerados_nova_arquitetura que pode não existir
-- A vinculação será feita por código, não por Foreign Key

-- 6. Índices adicionais para performance
CREATE INDEX IF NOT EXISTS idx_servico_status ON servicos_setor (status, criado_em);
CREATE INDEX IF NOT EXISTS idx_setor_servicos ON servicos_setor (setor_id, status);
CREATE INDEX IF NOT EXISTS idx_empresa_hierarquia ON setores_empresa (empresa_id, status);

-- 7. View para facilitar consultas hierárquicas
CREATE OR REPLACE VIEW vw_hierarquia_completa AS
SELECT 
    eo.id as estrutura_id,
    eo.empresa_id,
    eo.nome_empresa,
    eo.nicho,
    se.id as setor_id,
    se.nome_setor,
    se.tipo_setor,
    se.total_servicos as setor_total_servicos,
    se.total_sops as setor_total_sops,
    se.status as setor_status,
    ss.id as servico_id,
    ss.nome_servico,
    ss.codigo_servico,
    ss.categoria as servico_categoria,
    ss.criticidade as servico_criticidade,
    ss.origem as servico_origem,
    ss.status as servico_status,
    ss.tem_sop,
    ss.sop_id,
    CASE 
        WHEN ss.status = 'aprovado' THEN 'Concluído'
        WHEN ss.status = 'sop_gerado' THEN 'SOP Gerado'
        WHEN ss.status = 'detalhado' THEN 'Detalhado'
        WHEN ss.status = 'mapeado' THEN 'Mapeado'
        ELSE 'Pendente'
    END as status_legivel,
    ph.percentual_conclusao,
    ph.etapa_atual
FROM estruturas_organizacionais eo
LEFT JOIN setores_empresa se ON eo.id = se.estrutura_id
LEFT JOIN servicos_setor ss ON se.id = ss.setor_id
LEFT JOIN progresso_hierarquico ph ON eo.id = ph.estrutura_id
WHERE eo.status = 'ativo'
ORDER BY eo.empresa_id, se.nome_setor, ss.nome_servico;