-- 053 — Geração Conversacional de SOPs por Voz
-- O Consultor — Sistema Operacional Empresarial
--
-- Substitui a seleção manual de serviços por uma entrevista guiada por voz,
-- setor a setor. A IA classifica os serviços do catálogo em:
--   identificado -> pré-marcado na tela de seleção
--   sugerido     -> aparece desmarcado
--   excluido     -> não aparece (usuário negou explicitamente)
--
-- Também registra o histórico de transcrições por setor e o vínculo entre
-- cada serviço "identificado" e o trecho de conversa que o originou (usado
-- tanto na geração inicial quanto no refinamento por patch posterior).
--
-- NÃO altera o fluxo de Diagnóstico (Parte A) nem o catálogo determinístico.

-- =====================================================================
-- 0. Ativação manual de setor (em setores_empresa)
--    Permite que um setor apareça na aba "ativos" da listagem de SOPs mesmo
--    sem nenhum serviço selecionado ainda — o usuário ativa o setor e conversa
--    ali mesmo (mic inline) para revelar/criar os serviços, sem ser levado de
--    volta à tela de seleção de todos os setores.
-- =====================================================================
ALTER TABLE `setores_empresa`
  ADD COLUMN IF NOT EXISTS `ativado_manual` TINYINT(1) NOT NULL DEFAULT 0
    COMMENT 'Setor ativado manualmente (aparece em "ativos" mesmo sem serviços selecionados; conversa inline revela os serviços)';

-- =====================================================================
-- 1. Estado de conversa por serviço (em servicos_setor)
-- =====================================================================
ALTER TABLE `servicos_setor`
  ADD COLUMN IF NOT EXISTS `status_conversa` ENUM('identificado','sugerido','excluido') NOT NULL DEFAULT 'sugerido'
    COMMENT 'Estado do serviço após a entrevista por voz: identificado (pré-marcado), sugerido (desmarcado), excluido (oculto)'
    AFTER `selecionado`,
  ADD COLUMN IF NOT EXISTS `gap_identificado` TINYINT(1) NOT NULL DEFAULT 0
    COMMENT 'Serviço confirmado como gap relevante (empresa não faz hoje, mas deveria)'
    AFTER `status_conversa`,
  ADD COLUMN IF NOT EXISTS `motivo_conversa` VARCHAR(500) DEFAULT NULL
    COMMENT 'Anotação curta do motivo do gap ou observação da conversa (ex.: "não avisam hoje")'
    AFTER `gap_identificado`,
  ADD COLUMN IF NOT EXISTS `trecho_conversa` LONGTEXT DEFAULT NULL
    COMMENT 'Trecho da transcrição que originou/motivou este serviço, usado como contexto na geração e no patch'
    AFTER `motivo_conversa`,
  ADD COLUMN IF NOT EXISTS `conversa_id` INT(11) DEFAULT NULL
    COMMENT 'Referência à conversa (conversas_setor) que classificou este serviço'
    AFTER `trecho_conversa`;

CREATE INDEX IF NOT EXISTS idx_servico_status_conversa ON servicos_setor (status_conversa);

-- Normalizar estruturas JÁ existentes (criadas antes deste fluxo): no fluxo
-- conversacional os serviços começam DESMARCADOS até a entrevista classificá-los.
-- Só faz sentido para serviços automáticos do catálogo que nunca passaram por
-- conversa (status_conversa = 'sugerido' e origem automática/mapeada).
UPDATE `servicos_setor`
   SET `selecionado` = 0
 WHERE `status_conversa` = 'sugerido';

-- =====================================================================
-- 2. Histórico de transcrições por setor (auditoria + evitar repetir perguntas)
-- =====================================================================
CREATE TABLE IF NOT EXISTS `conversas_setor` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `estrutura_id` INT(11) DEFAULT NULL,
  `setor_id` INT(11) NOT NULL,
  `empresa_id` INT(11) NOT NULL,
  `diagnostico_id` INT(11) DEFAULT NULL,
  `turno` INT(11) NOT NULL DEFAULT 1 COMMENT 'Número do turno da conversa multi-turno do setor',
  `transcricao` LONGTEXT DEFAULT NULL COMMENT 'Texto transcrito (STT) da fala do usuário neste turno',
  `classificacao_json` LONGTEXT DEFAULT NULL COMMENT 'Resultado bruto da classificação da IA (JSON)',
  `perguntas_seguimento_json` LONGTEXT DEFAULT NULL COMMENT 'Perguntas de acompanhamento sugeridas pela IA (JSON)',
  `criado_em` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_setor_id` (`setor_id`),
  KEY `idx_empresa_id` (`empresa_id`),
  KEY `idx_diagnostico_id` (`diagnostico_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 3. Referência da transcrição de origem no SOP (para patch incremental)
-- =====================================================================
ALTER TABLE `sops`
  ADD COLUMN IF NOT EXISTS `conversa_id` INT(11) DEFAULT NULL
    COMMENT 'Conversa de setor que originou este SOP (contexto para refinamento por voz)'
    AFTER `diagnostico_id`;

-- =====================================================================
-- 4. Fila de geração: suporte a lote e concorrência real
-- =====================================================================
ALTER TABLE `fila_geracao_sop`
  ADD COLUMN IF NOT EXISTS `lote_id` VARCHAR(40) DEFAULT NULL
    COMMENT 'Agrupa os serviços disparados numa mesma confirmação (para notificar por setor/lote)'
    AFTER `empresa_id`,
  ADD COLUMN IF NOT EXISTS `setor_id` INT(11) DEFAULT NULL
    COMMENT 'Setor do serviço (permite notificar quando os SOPs de um setor terminam)'
    AFTER `lote_id`;

CREATE INDEX IF NOT EXISTS idx_fila_lote ON fila_geracao_sop (lote_id);
CREATE INDEX IF NOT EXISTS idx_fila_setor ON fila_geracao_sop (setor_id);

-- =====================================================================
-- 5. Notificações in-app (ex.: "SOPs do setor Comercial ficaram prontos")
-- =====================================================================
CREATE TABLE IF NOT EXISTS `notificacoes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `empresa_id` INT(11) DEFAULT NULL,
  `usuario_id` INT(11) DEFAULT NULL,
  `tipo` VARCHAR(50) NOT NULL DEFAULT 'sop' COMMENT 'sop | sistema | etc.',
  `titulo` VARCHAR(255) NOT NULL,
  `mensagem` VARCHAR(500) DEFAULT NULL,
  `link` VARCHAR(255) DEFAULT NULL,
  `lida` TINYINT(1) NOT NULL DEFAULT 0,
  `criado_em` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_empresa_id` (`empresa_id`),
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_lida` (`lida`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
