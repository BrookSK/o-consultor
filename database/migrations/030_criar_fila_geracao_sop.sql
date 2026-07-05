-- Migration: Fila de geração de SOPs processada por cron
-- O Consultor - Sistema Operacional Empresarial
-- Permite gerar SOPs em background via cron, sem sofrer timeout do proxy web.

CREATE TABLE IF NOT EXISTS `fila_geracao_sop` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sop_id` int(11) NOT NULL,
  `servico_id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `status` enum('pendente','processando','concluido','erro') NOT NULL DEFAULT 'pendente',
  `fase_atual` int(11) NOT NULL DEFAULT 0,
  `total_fases` int(11) NOT NULL DEFAULT 3,
  `mensagem` varchar(500) DEFAULT NULL,
  `tentativas` int(11) NOT NULL DEFAULT 0,
  `criado_em` datetime NOT NULL,
  `iniciado_em` datetime DEFAULT NULL,
  `concluido_em` datetime DEFAULT NULL,
  `atualizado_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_sop_id` (`sop_id`),
  KEY `idx_servico_id` (`servico_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
