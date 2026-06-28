-- =====================================================
-- O Consultor — Migration 021: Adicionar Campos dos Blocos 3 e 4 ao Diagnóstico
-- Data: 2026-06-28
-- Descrição: Adicionar campos dos blocos 3 (Estrutura Financeira/Comercial) e 4 (Pessoas/Riscos)
-- =====================================================

USE o_consultor;

-- Adicionar campos do Bloco 3: Estrutura Financeira e Comercial
ALTER TABLE diagnosticos_rascunho 
ADD COLUMN margem_lucro VARCHAR(50) NULL AFTER faturamento_mensal,
ADD COLUMN sistema_financeiro VARCHAR(100) NULL AFTER margem_lucro,
ADD COLUMN controle_fluxo_caixa VARCHAR(50) NULL AFTER sistema_financeiro,
ADD COLUMN canais_vendas TEXT NULL AFTER controle_fluxo_caixa,
ADD COLUMN sistema_crm VARCHAR(100) NULL AFTER canais_vendas,
ADD COLUMN taxa_conversao VARCHAR(50) NULL AFTER sistema_crm,
ADD COLUMN observacoes_bloco3 TEXT NULL AFTER taxa_conversao;

-- Adicionar campos do Bloco 4: Gestão de Pessoas e Riscos
ALTER TABLE diagnosticos_rascunho 
ADD COLUMN estrutura_organizacional VARCHAR(100) NULL AFTER observacoes_bloco3,
ADD COLUMN politicas_rh TEXT NULL AFTER estrutura_organizacional,
ADD COLUMN taxa_turnover VARCHAR(50) NULL AFTER politicas_rh,
ADD COLUMN programa_capacitacao VARCHAR(100) NULL AFTER taxa_turnover,
ADD COLUMN mapeamento_riscos VARCHAR(100) NULL AFTER programa_capacitacao,
ADD COLUMN seguros TEXT NULL AFTER mapeamento_riscos,
ADD COLUMN backup_continuidade VARCHAR(100) NULL AFTER seguros,
ADD COLUMN conformidade_regulatoria VARCHAR(100) NULL AFTER backup_continuidade,
ADD COLUMN dependencia_pessoas VARCHAR(100) NULL AFTER conformidade_regulatoria,
ADD COLUMN dependencia_fornecedores VARCHAR(100) NULL AFTER dependencia_pessoas,
ADD COLUMN observacoes_bloco4 TEXT NULL AFTER dependencia_fornecedores;

-- Atualizar campos já existentes no Bloco 2 se necessário
-- (ticket_medio já existe, mas pode precisar de ajuste)

-- Comentário de verificação
SELECT 'Campos dos Blocos 3 e 4 adicionados com sucesso!' as status;