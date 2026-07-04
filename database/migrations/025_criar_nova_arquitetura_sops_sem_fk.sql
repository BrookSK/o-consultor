-- Migração 025: Nova Arquitetura de SOPs (3 Etapas) - Sem Foreign Keys
-- Criação de tabelas para suportar a nova arquitetura profunda (versão sem FK)

-- Remover tabelas se existirem (para recriar)
DROP TABLE IF EXISTS `estruturas_temporarias`;
DROP TABLE IF EXISTS `manuais_completos`;

-- Tabela para estruturas temporárias (Etapa 1) - SEM FOREIGN KEY
CREATE TABLE `estruturas_temporarias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `diagnostico_id` int(11) NOT NULL,
  `estrutura_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `criado_em` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_diagnostico_id` (`diagnostico_id`),
  KEY `idx_criado_em` (`criado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para manuais completos (Etapa 3) - SEM FOREIGN KEY
CREATE TABLE `manuais_completos` (
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

-- Adicionar colunas na tabela sops para nova arquitetura (se não existirem)
ALTER TABLE `sops` 
ADD COLUMN IF NOT EXISTS `criticidade` tinyint(1) DEFAULT 2 COMMENT '1=crítico, 2=importante, 3=complementar',
ADD COLUMN IF NOT EXISTS `formato` enum('json_antigo', 'markdown_n3') DEFAULT 'json_antigo' COMMENT 'Formato do conteúdo',
ADD COLUMN IF NOT EXISTS `gatilho_entrada` text DEFAULT NULL COMMENT 'Gatilho que dispara o SOP',
ADD COLUMN IF NOT EXISTS `conteudo_completo` longtext DEFAULT NULL COMMENT 'Conteúdo completo em markdown para nova arquitetura',
ADD INDEX IF NOT EXISTS `idx_criticidade` (`criticidade`),
ADD INDEX IF NOT EXISTS `idx_formato` (`formato`);

-- Criar índices otimizados para performance
CREATE INDEX IF NOT EXISTS `idx_estruturas_temp_cleanup` ON `estruturas_temporarias` (`criado_em`);
CREATE INDEX IF NOT EXISTS `idx_manuais_empresa_diagnostico` ON `manuais_completos` (`empresa_id`, `diagnostico_id`);

-- Comentários das tabelas
ALTER TABLE `estruturas_temporarias` COMMENT = 'Armazena estruturas organizacionais temporárias da Etapa 1 (Nova Arquitetura)';
ALTER TABLE `manuais_completos` COMMENT = 'Manuais completos gerados na Etapa 3 da nova arquitetura';

-- Inserir dados de teste para verificar se as tabelas foram criadas corretamente
-- INSERT INTO `estruturas_temporarias` (`diagnostico_id`, `estrutura_json`, `criado_em`) VALUES (1, '{"teste": true}', NOW());
-- SELECT 'Tabelas da Nova Arquitetura criadas com sucesso!' AS resultado;