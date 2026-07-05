-- Migration: adicionar coluna subcategoria em servicos_setor
-- Permite organizar os serviços do catálogo por subcategoria funcional.

ALTER TABLE `servicos_setor`
  ADD COLUMN IF NOT EXISTS `subcategoria` VARCHAR(200) DEFAULT NULL AFTER `nome_servico`;

CREATE INDEX IF NOT EXISTS idx_subcategoria ON servicos_setor (subcategoria);
