-- Migração 024: Nova Arquitetura de SOPs (3 Etapas)
-- Criação de tabelas para suportar a nova arquitetura profunda

-- Tabela para estruturas temporárias (Etapa 1)
CREATE TABLE IF NOT EXISTS `estruturas_temporarias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `diagnostico_id` int(11) NOT NULL,
  `estrutura_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `criado_em` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_diagnostico_id` (`diagnostico_id`),
  KEY `idx_criado_em` (`criado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para manuais completos (Etapa 3)
CREATE TABLE IF NOT EXISTS `manuais_completos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` int(11) NOT NULL,
  `diagnostico_id` int(11) NOT NULL,
  `conteudo_completo` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `versao` varchar(10) DEFAULT '1.0',
  `criado_em` datetime NOT NULL,
  `atualizado_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_empresa_id` (`empresa_id`),
  KEY `idx_diagnostico_id` (`diagnostico_id`),
  KEY `idx_criado_em` (`criado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar colunas na tabela sops para nova arquitetura
ALTER TABLE `sops` 
ADD COLUMN IF NOT EXISTS `criticidade` tinyint(1) DEFAULT 2 COMMENT '1=crítico, 2=importante, 3=complementar',
ADD COLUMN IF NOT EXISTS `formato` enum('json_antigo', 'markdown_n3') DEFAULT 'json_antigo' COMMENT 'Formato do conteúdo',
ADD COLUMN IF NOT EXISTS `gatilho_entrada` text DEFAULT NULL COMMENT 'Gatilho que dispara o SOP',
ADD INDEX IF NOT EXISTS `idx_criticidade` (`criticidade`),
ADD INDEX IF NOT EXISTS `idx_formato` (`formato`);

-- Criar índices otimizados para performance
CREATE INDEX IF NOT EXISTS `idx_estruturas_temp_cleanup` ON `estruturas_temporarias` (`criado_em`);
CREATE INDEX IF NOT EXISTS `idx_manuais_empresa_diagnostico` ON `manuais_completos` (`empresa_id`, `diagnostico_id`);

-- Comentários das tabelas
ALTER TABLE `estruturas_temporarias` COMMENT = 'Armazena estruturas organizacionais temporárias da Etapa 1';
ALTER TABLE `manuais_completos` COMMENT = 'Manuais completos gerados na Etapa 3 da nova arquitetura';

-- Limpeza automática de estruturas temporárias (older than 2 hours)
-- Esta query pode ser executada via cron job
-- DELETE FROM estruturas_temporarias WHERE criado_em < DATE_SUB(NOW(), INTERVAL 2 HOUR);