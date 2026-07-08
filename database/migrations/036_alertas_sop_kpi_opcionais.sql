-- =====================================================
-- O Consultor — Migration 036: sop_id/kpi_id opcionais em alertas
-- Data: 2026-07-08
-- Descrição: A tabela `alertas` foi criada (migration 009) com sop_id e
--            kpi_id como NOT NULL, pensando só em alertas ligados a SOP/KPI.
--            Porém vários fluxos criam alertas genéricos (notícias novas,
--            novo cliente, solicitação de parceiro) sem informar esses
--            campos. Sem STRICT MODE, o MySQL usa o valor implícito 0 para
--            colunas INT NOT NULL sem default, e a FK falha com erro 1452
--            (não existe sops.id = 0 nem sop_kpis.id = 0).
-- =====================================================

USE o_consultor;

ALTER TABLE alertas
    MODIFY COLUMN sop_id INT UNSIGNED NULL,
    MODIFY COLUMN kpi_id INT UNSIGNED NULL;
