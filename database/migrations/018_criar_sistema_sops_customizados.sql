-- =====================================================
-- MIGRAÇÃO 018: Sistema de SOPs Customizados por Empresa
-- Permite que cada empresa tenha SOPs personalizados além dos padrões do setor
-- =====================================================

-- Tabela de SOPs customizados por empresa
CREATE TABLE IF NOT EXISTS sops_customizados (
    id INT PRIMARY KEY AUTO_INCREMENT,
    empresa_id INT NOT NULL,
    sop_codigo VARCHAR(50) NOT NULL,
    nome VARCHAR(255) NOT NULL,
    departamento VARCHAR(100) NOT NULL,
    descricao TEXT,
    icone VARCHAR(50) DEFAULT 'documento',
    ativo TINYINT(1) DEFAULT 1,
    criado_por INT NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_empresa_id (empresa_id),
    INDEX idx_sop_codigo (sop_codigo), 
    INDEX idx_departamento (departamento),
    INDEX idx_criado_por (criado_por),
    UNIQUE KEY unique_sop_empresa (empresa_id, sop_codigo)
);

-- =====================================================
-- INSERIR ALGUNS SOPs CUSTOMIZADOS DE EXEMPLO (OPCIONAL)
-- =====================================================

-- Descomente as linhas abaixo se desejar inserir exemplos:

/*
-- Para empresas de Construção Civil que já existam
INSERT IGNORE INTO sops_customizados (empresa_id, sop_codigo, nome, departamento, descricao, criado_por, icone) 
SELECT 
    e.id as empresa_id,
    'SOP-CC-CUSTOM-001' as sop_codigo,
    'Gestão de Almoxarifado' as nome,
    'Logística' as departamento,
    'Controle de entrada, saída e inventário de materiais de construção' as descricao,
    1 as criado_por,
    'pacote' as icone
FROM empresas e 
WHERE LOWER(e.segmento) LIKE '%construção%' OR LOWER(e.segmento) LIKE '%construcao%'
AND EXISTS (SELECT 1 FROM usuarios WHERE id = 1)
LIMIT 5;

-- Para empresas de Tecnologia
INSERT IGNORE INTO sops_customizados (empresa_id, sop_codigo, nome, departamento, descricao, criado_por, icone) 
SELECT 
    e.id as empresa_id,
    'SOP-TI-CUSTOM-001' as sop_codigo,
    'Gestão de Licenças de Software' as nome,
    'TI' as departamento,
    'Controle e renovação de licenças de software empresarial' as descricao,
    1 as criado_por,
    'chave' as icone
FROM empresas e 
WHERE LOWER(e.segmento) LIKE '%tecnologia%' OR LOWER(e.segmento) LIKE '%software%'
AND EXISTS (SELECT 1 FROM usuarios WHERE id = 1)
LIMIT 5;
*/

-- =====================================================
-- COMENTÁRIOS DA MIGRAÇÃO
-- =====================================================

/*
Esta migração cria o sistema de SOPs customizados que permite:

1. PERSONALIZAÇÃO POR EMPRESA:
   - Cada empresa pode criar SOPs específicos além dos padrões do setor
   - Código único por empresa para evitar conflitos
   - Departamentos personalizáveis

2. INTEGRAÇÃO COM SISTEMA PRINCIPAL:
   - SOPs customizados aparecem junto aos padrões do setor
   - Mesmo fluxo de geração e aprovação
   - Histórico de criação e modificação

3. GESTÃO ADMINISTRATIVA:
   - Apenas ADMIN_HOLDING e CONSULTOR_INTERNO podem criar/editar
   - Controle de ativação/desativação
   - Rastreabilidade de criação

4. FLEXIBILIDADE:
   - Ícones personalizáveis por departamento
   - Descrições detalhadas para contexto
   - Suporte a diferentes estruturas organizacionais

PRÓXIMOS PASSOS:
- Interface de gestão na área administrativa
- Importação/exportação de templates de SOPs
- Versionamento de SOPs customizados
*/