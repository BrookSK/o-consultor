-- =====================================================
-- O Consultor — Seed 055: Conta de DEMONSTRAÇÃO
-- Data: 2026-07-24
-- Descrição: Cria a empresa demo, o usuário demo e dados mockup em todos os
--            módulos. Idempotente (pode rodar várias vezes). Rodar DEPOIS da
--            migration 054 (para os módulos de conteúdo/concorrência/calendário).
--
--   Login:  demo@oconsultor.com.br
--   Senha:  demo@123
-- =====================================================

USE o_consultor;

-- =====================================================
-- 1) EMPRESA DEMO
-- =====================================================
INSERT INTO empresas (nome, cnpj, segmento, telefone, criado_em)
SELECT 'Demo Café & Cia', '00.000.000/0001-00', 'Alimentos e Bebidas', '(11) 90000-0000', NOW()
WHERE NOT EXISTS (SELECT 1 FROM empresas WHERE nome = 'Demo Café & Cia');

SET @empresa_id = (SELECT id FROM empresas WHERE nome = 'Demo Café & Cia' LIMIT 1);

-- Campos opcionais (migration 015). Ignorados automaticamente se as colunas não existirem
-- não é possível; se sua base não tiver essas colunas, remova a linha abaixo.
UPDATE empresas
   SET status = 'ativo', mrr = 2500.00, cidade = 'São Paulo', estado = 'SP',
       website = 'https://demo.oconsultor.com.br', score_maturidade = 68
 WHERE id = @empresa_id;

-- =====================================================
-- 2) USUÁRIO DEMO (senha: demo@123 — hash bcrypt verificado)
-- =====================================================
INSERT INTO usuarios (nome, email, senha, perfil, empresa_id, ativo, criado_em)
SELECT 'Conta Demonstração', 'demo@oconsultor.com.br',
       '$2y$10$mNPP7WagbL5aZ9JpcbHe1OUI32NlIre18B18WQ0c7Vtet3Gu.k8w.',
       'ADMIN_HOLDING', @empresa_id, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE email = 'demo@oconsultor.com.br');

-- Garante senha/empresa/perfil corretos mesmo se o usuário já existia.
UPDATE usuarios
   SET senha = '$2y$10$mNPP7WagbL5aZ9JpcbHe1OUI32NlIre18B18WQ0c7Vtet3Gu.k8w.',
       perfil = 'ADMIN_HOLDING', empresa_id = @empresa_id, ativo = 1
 WHERE email = 'demo@oconsultor.com.br';

SET @usuario_id = (SELECT id FROM usuarios WHERE email = 'demo@oconsultor.com.br' LIMIT 1);
UPDATE usuarios SET onboarding_concluido = 1 WHERE id = @usuario_id;

-- =====================================================
-- 3) DIAGNÓSTICO
-- =====================================================
INSERT INTO diagnosticos (empresa_id, usuario_id, respostas, pontuacao, status, criado_em)
SELECT @empresa_id, @usuario_id,
       JSON_OBJECT('empresa_nome','Demo Café & Cia','setor','Alimentos e Bebidas',
                   'principais_desafios','Padronização de processos e presença digital',
                   'objetivo_12_meses','Abrir 2 novas unidades mantendo o padrão'),
       68, 'concluido', NOW()
WHERE NOT EXISTS (SELECT 1 FROM diagnosticos WHERE empresa_id = @empresa_id);

-- =====================================================
-- 4) PLANO DE AÇÃO + TAREFAS
-- =====================================================
INSERT INTO planos (empresa_id, usuario_id, titulo, objetivo, status, progresso_calculado, total_tarefas, tarefas_concluidas, criado_em)
SELECT @empresa_id, @usuario_id, 'Plano de Crescimento 2026', 'Padronizar operação e escalar vendas',
       'ativo', 40, 5, 2, NOW()
WHERE NOT EXISTS (SELECT 1 FROM planos WHERE empresa_id = @empresa_id);

SET @plano_id = (SELECT id FROM planos WHERE empresa_id = @empresa_id ORDER BY id LIMIT 1);

INSERT INTO plano_tarefas (plano_id, titulo, status, prioridade, criado_em)
SELECT * FROM (
    SELECT @plano_id AS a, 'Mapear processos da cozinha' AS b, 'concluido' AS c, 'alta' AS d, NOW() AS e
    UNION ALL SELECT @plano_id, 'Criar manual de atendimento', 'concluido', 'media', NOW()
    UNION ALL SELECT @plano_id, 'Implantar CRM de clientes', 'em_andamento', 'alta', NOW()
    UNION ALL SELECT @plano_id, 'Calendário de conteúdo mensal', 'pendente', 'media', NOW()
    UNION ALL SELECT @plano_id, 'Treinar equipe no novo padrão', 'pendente', 'baixa', NOW()
) t
WHERE NOT EXISTS (SELECT 1 FROM plano_tarefas WHERE plano_id = @plano_id);

-- =====================================================
-- 5) SOPs
-- =====================================================
INSERT INTO sops (empresa_id, titulo, departamento, conteudo, status, gerado_por_ia, criado_em)
SELECT * FROM (
    SELECT @empresa_id AS a, 'Padrão de Atendimento ao Cliente' AS b, 'Atendimento' AS c, 'Procedimento operacional padrão (demonstração).' AS d, 'ativo' AS e, 1 AS f, NOW() AS g
    UNION ALL SELECT @empresa_id, 'Preparo e Padronização do Espresso', 'Operações', 'Procedimento operacional padrão (demonstração).', 'ativo', 1, NOW()
    UNION ALL SELECT @empresa_id, 'Fechamento de Caixa Diário', 'Financeiro', 'Procedimento operacional padrão (demonstração).', 'ativo', 1, NOW()
    UNION ALL SELECT @empresa_id, 'Limpeza e Higienização', 'Operações', 'Procedimento operacional padrão (demonstração).', 'ativo', 1, NOW()
) t
WHERE NOT EXISTS (SELECT 1 FROM sops WHERE empresa_id = @empresa_id);

SET @sop_id = (SELECT id FROM sops WHERE empresa_id = @empresa_id ORDER BY id LIMIT 1);

-- =====================================================
-- 6) KPIs (sop_kpis)
-- =====================================================
INSERT INTO sop_kpis (empresa_id, sop_id, nome, meta_verde, meta_amarela, meta_vermelha, acao_vermelha, valor_atual, zona_atual, ativo, criado_em)
SELECT * FROM (
    SELECT @empresa_id AS a, @sop_id AS b, 'Tempo médio de atendimento' AS c, '3min' AS d, '5min' AS e, '8min' AS f, 'Rever processo imediatamente' AS g, '3min' AS h, 'verde' AS i, 1 AS j, NOW() AS k
    UNION ALL SELECT @empresa_id, @sop_id, 'Satisfação do cliente (NPS)', '70', '50', '30', 'Rever processo imediatamente', '72', 'verde', 1, NOW()
    UNION ALL SELECT @empresa_id, @sop_id, 'Ticket médio', 'R$ 35', 'R$ 25', 'R$ 18', 'Rever processo imediatamente', 'R$ 27', 'amarela', 1, NOW()
) t
WHERE NOT EXISTS (SELECT 1 FROM sop_kpis WHERE empresa_id = @empresa_id);

-- =====================================================
-- 7) NOTÍCIAS
-- =====================================================
INSERT INTO noticias (empresa_id, titulo, url, fonte, data_publicacao, categoria, relevancia, setor,
                      bloco1_noticia, bloco2_significa, bloco3_o_que_fazer, bloco4_pergunta, bloco5_conexao,
                      processado_via, criado_em)
SELECT * FROM (
    SELECT @empresa_id AS a, 'Consumo de café especial cresce 15% no Brasil' AS b, 'https://exemplo.com/noticia-1' AS c, 'Portal Demo' AS d, CURDATE() AS e, 'Tendência' AS f, 'alta' AS g, 'Alimentos e Bebidas' AS h,
           'Resumo factual (demonstração).' AS i, 'O que significa para o setor.' AS j, 'Ações: revisar cardápio, treinar equipe.' AS k, 'Como aproveitar essa tendência?' AS l, 'Conecta-se ao Plano e à Máquina de Conteúdo.' AS m, 'perplexity+gpt' AS n, NOW() AS o
    UNION ALL SELECT @empresa_id, 'Nova regulamentação sanitária para food service', 'https://exemplo.com/noticia-2', 'Portal Demo', CURDATE() - INTERVAL 1 DAY, 'Regulamentação', 'media', 'Alimentos e Bebidas',
           'Resumo factual (demonstração).', 'O que significa para o setor.', 'Ações práticas recomendadas.', 'Sua empresa está em conformidade?', 'Conecta-se aos SOPs.', 'perplexity+gpt', NOW()
    UNION ALL SELECT @empresa_id, 'Delivery de bebidas quentes ganha espaço em apps', 'https://exemplo.com/noticia-3', 'Portal Demo', CURDATE() - INTERVAL 2 DAY, 'Mercado', 'media', 'Alimentos e Bebidas',
           'Resumo factual (demonstração).', 'O que significa para o setor.', 'Ações práticas recomendadas.', 'Vale investir em delivery?', 'Conecta-se à Máquina de Conteúdo.', 'perplexity+gpt', NOW()
) t
WHERE NOT EXISTS (SELECT 1 FROM noticias WHERE empresa_id = @empresa_id);

-- =====================================================
-- 8) MARCA (Brand Book)
-- =====================================================
INSERT INTO marcas (empresa_id, nome, nicho, publico_alvo, produtos_servicos, tom, arquetipo,
                    palavras_usa, palavras_nunca, prompt_master, brand_book_criado, ativo, criado_em)
SELECT @empresa_id, 'Demo Café & Cia', 'Cafeteria artesanal', 'Amantes de café, 25-45 anos, urbanos',
       'Cafés especiais, brunch, confeitaria artesanal', 'acolhedor', 'O Cuidador',
       'artesanal, especial, aconchego, origem', 'barato, industrial, genérico',
       'Marca de cafeteria artesanal com tom acolhedor e foco em qualidade e origem do café.', 1, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM marcas WHERE empresa_id = @empresa_id AND ativo = 1);

SET @marca_id = (SELECT id FROM marcas WHERE empresa_id = @empresa_id AND ativo = 1 ORDER BY id LIMIT 1);

-- =====================================================
-- 9) CONTEÚDOS GERADOS (Máquina de Conteúdo)
-- =====================================================
INSERT INTO conteudos_marca (marca_id, usuario_id, tipo, tema, objetivo, slides, legenda, hashtags, status, criado_em)
SELECT @marca_id, @usuario_id, 'carrossel', 'O segredo do café especial', 'educar',
       JSON_ARRAY(
         JSON_OBJECT('numero',1,'texto','O segredo de um café especial','texto_secundario','da origem à xícara','prompt_imagem','grãos de café sendo torrados','imagem_pendente',false,'imagem_url',''),
         JSON_OBJECT('numero',2,'texto','Origem importa','texto_secundario','grãos selecionados de fazendas parceiras','prompt_imagem','fazenda de café ao amanhecer','imagem_pendente',false,'imagem_url',''),
         JSON_OBJECT('numero',3,'texto','Venha experimentar','texto_secundario','seu próximo café favorito','prompt_imagem','xícara de espresso sobre balcão','imagem_pendente',false,'imagem_url','')
       ),
       'Descubra o que torna nosso café especial — da origem à sua xícara. ☕', '#cafeespecial #cafeteria #cafeartesanal', 'aprovado', NOW()
WHERE @marca_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM conteudos_marca WHERE marca_id = @marca_id);

INSERT INTO conteudos_marca (marca_id, usuario_id, tipo, tema, objetivo, slides, legenda, hashtags, status, criado_em)
SELECT @marca_id, @usuario_id, 'post', 'Promoção de brunch de domingo', 'vender',
       JSON_ARRAY(), 'Domingo é dia de brunch! Reserve sua mesa.', '#brunch #domingo', 'rascunho', NOW()
WHERE @marca_id IS NOT NULL
  AND (SELECT COUNT(*) FROM conteudos_marca WHERE marca_id = @marca_id) < 2;

-- =====================================================
-- 10) CONFIGURAÇÕES DE CONTEÚDO (migration 054)
-- =====================================================
INSERT INTO configuracoes_conteudo
    (empresa_id, frequencia_padrao, redes_sociais, formatos_preferidos, idioma, pais, estado, cidade,
     antecedencia_datas_dias, qtd_sugestoes_semanais, permitir_noticias, permitir_concorrencia,
     permitir_datas_comemorativas, gerar_imagens_padrao, evitar_repeticao_temas, periodo_repeticao_dias, criado_em)
SELECT @empresa_id, 'semanal', JSON_ARRAY('instagram','facebook'), JSON_ARRAY('carrossel','post','reels'),
       'Português', 'Brasil', 'SP', 'São Paulo', 7, 3, 1, 1, 1, 1, 1, 30, NOW()
WHERE NOT EXISTS (SELECT 1 FROM configuracoes_conteudo WHERE empresa_id = @empresa_id);

-- =====================================================
-- 11) CONCORRENTES + COLETA + POSTS + ANÁLISE (migration 054)
-- =====================================================
INSERT INTO concorrentes (empresa_id, nome, nome_perfil, url_publica, plataforma, categoria,
                          frequencia_coleta, max_posts_por_coleta, principal, status, seguidores,
                          data_inicio_acompanhamento, ultima_coleta_em, criado_em)
SELECT * FROM (
    SELECT @empresa_id AS a, 'Café da Esquina' AS b, '@cafedaesquina' AS c, 'https://instagram.com/cafedaesquina' AS d, 'instagram' AS e, 'Cafeteria' AS f, 'semanal' AS g, 12 AS h, 1 AS i, 'ativo' AS j, 4200 AS k, CURDATE() AS l, NOW() AS m, NOW() AS n
    UNION ALL SELECT @empresa_id, 'Torra Nobre', '@torranobre', 'https://instagram.com/torranobre', 'instagram', 'Cafeteria', 'semanal', 12, 0, 'ativo', 8800, CURDATE(), NOW(), NOW()
) t
WHERE NOT EXISTS (SELECT 1 FROM concorrentes WHERE empresa_id = @empresa_id);

SET @conc_id = (SELECT id FROM concorrentes WHERE empresa_id = @empresa_id ORDER BY id LIMIT 1);

INSERT INTO concorrente_coletas (concorrente_id, empresa_id, origem, status, seguidores_snapshot, posts_coletados, iniciada_em, finalizada_em, criado_em)
SELECT @conc_id, @empresa_id, 'manual', 'concluida', 4200, 3, NOW(), NOW(), NOW()
WHERE @conc_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM concorrente_coletas WHERE concorrente_id = @conc_id);

SET @coleta_id = (SELECT id FROM concorrente_coletas WHERE concorrente_id = @conc_id ORDER BY id LIMIT 1);

INSERT INTO concorrente_posts (concorrente_id, coleta_id, empresa_id, plataforma, tipo_conteudo, data_publicacao,
                              titulo, curtidas, comentarios, visualizacoes, compartilhamentos,
                              engajamento_absoluto, taxa_engajamento, metricas_indisponiveis, fonte_coleta, status_coleta, coletado_em)
SELECT * FROM (
    SELECT @conc_id AS a, @coleta_id AS b, @empresa_id AS c, 'instagram' AS d, 'carrossel' AS e, NOW() - INTERVAL 2 DAY AS f,
           'Novo blend de inverno' AS g, 320 AS h, 45 AS i, NULL AS j, 12 AS k, 377 AS l, 8.9762 AS m, JSON_ARRAY('visualizacoes') AS n, 'scrapingbee' AS o, 'parcial' AS p, NOW() AS q
    UNION ALL SELECT @conc_id, @coleta_id, @empresa_id, 'instagram', 'reels', NOW() - INTERVAL 5 DAY,
           'Bastidores da torra', 890, 78, 15400, 40, 1008, 24.0000, JSON_ARRAY(), 'scrapingbee', 'ok', NOW()
    UNION ALL SELECT @conc_id, @coleta_id, @empresa_id, 'instagram', 'post', NOW() - INTERVAL 7 DAY,
           'Dica de preparo em casa', NULL, NULL, NULL, NULL, NULL, NULL, JSON_ARRAY('curtidas','comentarios','visualizacoes','compartilhamentos'), 'scrapingbee', 'parcial', NOW()
) t
WHERE @conc_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM concorrente_posts WHERE concorrente_id = @conc_id);

INSERT INTO concorrente_analises (concorrente_id, coleta_id, empresa_id, resumo, dados, oportunidades, criado_em)
SELECT @conc_id, @coleta_id, @empresa_id,
       'Reels de bastidores geram o maior engajamento; conteúdo educativo tem bom desempenho.',
       JSON_OBJECT('temas_melhor_desempenho', JSON_ARRAY('bastidores','origem do café','dicas de preparo'),
                   'formatos_melhor_desempenho', JSON_ARRAY('reels','carrossel'),
                   'ganchos', JSON_ARRAY('pergunta de abertura','curiosidade'),
                   'ctas', JSON_ARRAY('visite a loja','salve este post'),
                   'lacunas', JSON_ARRAY('pouco conteúdo sobre sustentabilidade')),
       JSON_ARRAY('Explorar sustentabilidade e origem ética dos grãos','Criar série de Reels de bastidores'), NOW()
WHERE @conc_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM concorrente_analises WHERE concorrente_id = @conc_id);

-- =====================================================
-- 12) DATAS COMEMORATIVAS + CALENDÁRIO (migration 054)
-- =====================================================
INSERT INTO datas_comemorativas (empresa_id, nome, tipo, mes, dia, recorrencia, pais, nichos, relevancia, antecedencia_dias, fonte, ativo, criado_em)
SELECT * FROM (
    SELECT @empresa_id AS a, 'Dia Internacional do Café' AS b, 'setorial' AS c, 4 AS d, 14 AS e, 'anual' AS f, 'Brasil' AS g, JSON_ARRAY('cafeteria') AS h, 'alta' AS i, 7 AS j, 'Seed demo' AS k, 1 AS l, NOW() AS m
    UNION ALL SELECT @empresa_id, 'Dia do Cliente', 'comercial', 9, 15, 'anual', 'Brasil', JSON_ARRAY('cafeteria'), 'alta', 7, 'Seed demo', 1, NOW()
    UNION ALL SELECT @empresa_id, 'Semana do Brunch', 'sazonal', 6, 10, 'anual', 'Brasil', JSON_ARRAY('cafeteria'), 'media', 7, 'Seed demo', 1, NOW()
) t
WHERE NOT EXISTS (SELECT 1 FROM datas_comemorativas WHERE empresa_id = @empresa_id);

INSERT INTO calendario_conteudo (empresa_id, tema, origem, formato_recomendado, objetivo, data_publicacao_sugerida, status, criado_em)
SELECT * FROM (
    SELECT @empresa_id AS a, 'Lançamento do blend de inverno' AS b, 'tema_manual' AS c, 'carrossel' AS d, 'vender' AS e, CURDATE() + INTERVAL 3 DAY AS f, 'planejado' AS g, NOW() AS h
    UNION ALL SELECT @empresa_id, 'Bastidores: como torramos nosso café', 'conteudo_semanal', 'reels', 'engajar', CURDATE() + INTERVAL 5 DAY, 'sugerido', NOW()
    UNION ALL SELECT @empresa_id, 'Dia Internacional do Café', 'data_comemorativa', 'post', 'engajar', CURDATE() + INTERVAL 10 DAY, 'sugerido', NOW()
) t
WHERE NOT EXISTS (SELECT 1 FROM calendario_conteudo WHERE empresa_id = @empresa_id);

-- =====================================================
-- FIM DO SEED 055
-- Login: demo@oconsultor.com.br  /  Senha: demo@123
-- =====================================================
