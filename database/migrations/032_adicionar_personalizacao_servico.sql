-- 032 — Personalização de serviço: documento anexado + contexto extraído para IA
-- Permite que o usuário anexe um documento (PDF/DOCX/TXT...) e uma descrição
-- para regenerar o SOP consolidando o padrão do serviço + informações do documento.

ALTER TABLE `servicos_setor`
  ADD COLUMN IF NOT EXISTS `contexto_personalizacao` LONGTEXT DEFAULT NULL
    COMMENT 'Texto extraído do documento anexado pelo usuário, usado como contexto na geração do SOP'
    AFTER `descricao_resumida`,
  ADD COLUMN IF NOT EXISTS `documento_personalizacao_nome` VARCHAR(255) DEFAULT NULL
    COMMENT 'Nome original do documento anexado na personalização'
    AFTER `contexto_personalizacao`,
  ADD COLUMN IF NOT EXISTS `personalizado_em` DATETIME DEFAULT NULL
    COMMENT 'Data/hora da última personalização com documento'
    AFTER `documento_personalizacao_nome`;
