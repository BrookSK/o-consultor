-- Migração 026: Tabelas para Nova Arquitetura Detalhada (Etapas 2A e 2B)
-- Suporte para mapeamento e detalhamento individual de serviços

-- Tabela para armazenar listagem de serviços por setor (Etapa 2A)
CREATE TABLE IF NOT EXISTS `servicos_mapeados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `estrutura_id` int(11) NOT NULL COMMENT 'FK para estruturas_temporarias',
  `setor_nome` varchar(100) NOT NULL,
  `setor_tipo` enum('base', 'especifico_do_nicho') DEFAULT 'base',
  `servicos_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Lista de todos os serviços possíveis do setor',
  `total_servicos` int(11) DEFAULT 0 COMMENT 'Quantidade de serviços identificados',
  `status` enum('mapeando', 'concluido', 'erro') DEFAULT 'mapeando',
  `criado_em` datetime NOT NULL,
  `processado_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_estrutura_id` (`estrutura_id`),
  KEY `idx_setor_nome` (`setor_nome`),
  KEY `idx_status` (`status`),
  KEY `idx_criado_em` (`criado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para armazenar detalhamento individual de cada serviço (Etapa 2B)
CREATE TABLE IF NOT EXISTS `servicos_detalhados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `servico_mapeado_id` int(11) NOT NULL COMMENT 'FK para servicos_mapeados',
  `estrutura_id` int(11) NOT NULL COMMENT 'FK para estruturas_temporarias (para facilitar consultas)',
  `setor_nome` varchar(100) NOT NULL,
  `servico_nome` varchar(200) NOT NULL,
  `servico_codigo` varchar(50) NOT NULL COMMENT 'Código único do serviço (ex: ti-manutencao-servidores)',
  `criticidade` tinyint(1) DEFAULT 2 COMMENT '1=crítico, 2=importante, 3=complementar',
  `detalhamento_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Detalhamento completo com todos os passos e problemas',
  `problemas_mapeados` int(11) DEFAULT 0 COMMENT 'Quantidade de problemas N1-N2-N3 identificados',
  `status` enum('detalhando', 'concluido', 'erro') DEFAULT 'detalhando',
  `criado_em` datetime NOT NULL,
  `processado_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_servico_mapeado_id` (`servico_mapeado_id`),
  KEY `idx_estrutura_id` (`estrutura_id`),
  KEY `idx_setor_nome` (`setor_nome`),
  KEY `idx_servico_codigo` (`servico_codigo`),
  KEY `idx_criticidade` (`criticidade`),
  KEY `idx_status` (`status`),
  KEY `idx_criado_em` (`criado_em`),
  UNIQUE KEY `unique_servico_por_estrutura` (`estrutura_id`, `servico_codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para rastrear progresso das etapas (controle de fluxo)
CREATE TABLE IF NOT EXISTS `progresso_manual` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `estrutura_id` int(11) NOT NULL,
  `diagnostico_id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `etapa_atual` enum('etapa1', 'etapa2a', 'etapa2b', 'etapa3', 'concluido') DEFAULT 'etapa1',
  `total_setores` int(11) DEFAULT 0,
  `setores_mapeados` int(11) DEFAULT 0,
  `total_servicos` int(11) DEFAULT 0,
  `servicos_detalhados` int(11) DEFAULT 0,
  `total_sops` int(11) DEFAULT 0,
  `sops_gerados` int(11) DEFAULT 0,
  `progresso_percentual` decimal(5,2) DEFAULT 0.00,
  `iniciado_em` datetime NOT NULL,
  `atualizado_em` datetime DEFAULT NULL,
  `concluido_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_estrutura` (`estrutura_id`),
  KEY `idx_diagnostico_id` (`diagnostico_id`),
  KEY `idx_empresa_id` (`empresa_id`),
  KEY `idx_etapa_atual` (`etapa_atual`),
  KEY `idx_progresso` (`progresso_percentual`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices compostos para performance em consultas complexas
CREATE INDEX IF NOT EXISTS `idx_servicos_setor_status` ON `servicos_mapeados` (`setor_nome`, `status`);
CREATE INDEX IF NOT EXISTS `idx_detalhados_setor_criticidade` ON `servicos_detalhados` (`setor_nome`, `criticidade`, `status`);
CREATE INDEX IF NOT EXISTS `idx_progresso_empresa_etapa` ON `progresso_manual` (`empresa_id`, `etapa_atual`);

-- Comentários das tabelas
ALTER TABLE `servicos_mapeados` COMMENT = 'Etapa 2A: Listagem de todos os serviços possíveis por setor';
ALTER TABLE `servicos_detalhados` COMMENT = 'Etapa 2B: Detalhamento individual de cada serviço com problemas N1-N2-N3';
ALTER TABLE `progresso_manual` COMMENT = 'Controle de progresso das 4 etapas da nova arquitetura';

-- Inserir dados de exemplo para validação
-- INSERT INTO `servicos_mapeados` (`estrutura_id`, `setor_nome`, `servicos_json`, `criado_em`) 
-- VALUES (1, 'TI', '{"teste": "Etapa 2A funcionando"}', NOW());

-- SELECT 'Tabelas da Nova Arquitetura Detalhada criadas com sucesso!' AS resultado;