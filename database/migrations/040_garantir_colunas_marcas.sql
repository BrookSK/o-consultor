-- =====================================================
-- O Consultor — Migration 040: Garantir colunas da tabela marcas
-- Data: 2026-07-08
-- Descrição: Em alguns ambientes a tabela `marcas` foi criada por uma versão
--            antiga e ficou sem colunas do Brand Book completo (erro 1054:
--            "Unknown column 'produtos_servicos'"). Esta migration adiciona,
--            de forma idempotente, todas as colunas esperadas pelo wizard.
-- =====================================================

USE o_consultor;

DROP PROCEDURE IF EXISTS add_col_marcas;

DELIMITER //
CREATE PROCEDURE add_col_marcas(IN col_name VARCHAR(64), IN col_def TEXT)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'marcas' AND COLUMN_NAME = col_name
    ) THEN
        SET @ddl = CONCAT('ALTER TABLE marcas ADD COLUMN ', col_name, ' ', col_def);
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

CALL add_col_marcas('nicho', 'VARCHAR(100) NULL');
CALL add_col_marcas('publico_alvo', 'TEXT NULL');
CALL add_col_marcas('produtos_servicos', 'TEXT NULL');
CALL add_col_marcas('diferenciais_competitivos', 'TEXT NULL');
CALL add_col_marcas('concorrentes', 'TEXT NULL');
CALL add_col_marcas('objetivos_conteudo', 'JSON NULL');
CALL add_col_marcas('tom', 'VARCHAR(50) NULL');
CALL add_col_marcas('arquetipo', 'VARCHAR(50) NULL');
CALL add_col_marcas('palavras_usa', 'TEXT NULL');
CALL add_col_marcas('palavras_nunca', 'TEXT NULL');
CALL add_col_marcas('formatos_preferenciais', 'JSON NULL');
CALL add_col_marcas('paleta_cores', 'JSON NULL');
CALL add_col_marcas('fonte_principal', 'VARCHAR(100) NULL');
CALL add_col_marcas('fonte_secundaria', 'VARCHAR(100) NULL');
CALL add_col_marcas('estilo_visual', 'TEXT NULL');
CALL add_col_marcas('direcao_foto', 'VARCHAR(50) NULL');
CALL add_col_marcas('prompt_master', 'LONGTEXT NULL');
CALL add_col_marcas('prompt_dalle', 'LONGTEXT NULL');
CALL add_col_marcas('brand_book_criado', 'TINYINT(1) NOT NULL DEFAULT 0');
CALL add_col_marcas('ativo', 'TINYINT(1) NOT NULL DEFAULT 1');

DROP PROCEDURE IF EXISTS add_col_marcas;
