-- 033 — Seleção de serviços que entram no Manual/SOPs
-- Permite que o usuário escolha, numa tela de rascunho (draft), quais serviços
-- de cada setor realmente farão parte dos SOPs da empresa. Serviços não
-- selecionados não aparecem na lista de SOPs gerados.
-- Default 1 para preservar o comportamento dos serviços já existentes.

ALTER TABLE `servicos_setor`
  ADD COLUMN IF NOT EXISTS `selecionado` TINYINT(1) NOT NULL DEFAULT 1
    COMMENT 'Se o serviço foi selecionado pelo usuário para compor os SOPs (1=sim, 0=não)'
    AFTER `status`;

CREATE INDEX IF NOT EXISTS idx_servico_selecionado ON servicos_setor (selecionado);
