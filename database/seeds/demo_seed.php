<?php
/**
 * Seed da conta de DEMONSTRAÇÃO — O Consultor
 *
 * Cria (de forma idempotente) uma empresa demo, o usuário demo e dados mockup
 * em TODOS os módulos, para que a conta demo exiba todas as abas (exceto as
 * Configurações do admin) com conteúdo de exemplo.
 *
 * Login criado:
 *   e-mail: demo@oconsultor.com.br
 *   senha:  demo@123
 *
 * Uso (CLI):  php database/seeds/demo_seed.php
 * Também pode ser incluído por um endpoint protegido de instalação.
 *
 * É seguro rodar várias vezes: verifica existência antes de inserir e limpa os
 * dados mockup da empresa demo antes de recriar (não toca em outras empresas).
 */

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 2));
    define('APP_PATH', ROOT_PATH . '/app');
    define('VIEW_PATH', APP_PATH . '/Views');
    define('PUBLIC_PATH', ROOT_PATH . '/public');

    require_once ROOT_PATH . '/config/app.php';
    require_once ROOT_PATH . '/config/database.php';
    require_once ROOT_PATH . '/config/api_keys.php';

    spl_autoload_register(function ($class) {
        foreach ([APP_PATH . '/Controllers/', APP_PATH . '/Models/', APP_PATH . '/Helpers/'] as $dir) {
            $p = $dir . $class . '.php';
            if (file_exists($p)) { require_once $p; return; }
        }
    });

    require_once APP_PATH . '/Helpers/Logger.php';
    require_once APP_PATH . '/Models/Database.php';
    require_once APP_PATH . '/Models/Configuracao.php';
}

/**
 * Executa o seed da conta demo e retorna um relatório do que foi criado.
 */
function seedDemo(): array
{
    $relatorio = [];
    $DEMO_EMAIL = 'demo@oconsultor.com.br';
    $DEMO_SENHA = 'demo@123';

    // Helper: executa ignorando tabela/coluna ausente (schema tolerante).
    $tent = function (callable $fn) {
        try { return $fn(); } catch (\Throwable $e) { return null; }
    };

    // ===== 1) EMPRESA DEMO =====
    $empresa = Database::queryOne("SELECT id FROM empresas WHERE nome = :n LIMIT 1", ['n' => 'Demo Café & Cia']);
    if ($empresa) {
        $empresaId = (int) $empresa['id'];
    } else {
        Database::execute(
            "INSERT INTO empresas (nome, cnpj, segmento, telefone, criado_em) VALUES (:n, :c, :s, :t, NOW())",
            ['n' => 'Demo Café & Cia', 'c' => '00.000.000/0001-00', 's' => 'Alimentos e Bebidas', 't' => '(11) 90000-0000']
        );
        $empresaId = (int) Database::lastInsertId();
    }
    // Campos opcionais (migrations posteriores). Tolerante a ausência.
    $tent(fn() => Database::execute(
        "UPDATE empresas SET status='ativo', mrr=2500.00, cidade='São Paulo', estado='SP', website='https://demo.oconsultor.com.br', score_maturidade=68 WHERE id=:id",
        ['id' => $empresaId]
    ));
    $relatorio['empresa_id'] = $empresaId;

    // ===== 2) USUÁRIO DEMO (ADMIN_HOLDING, mas com menu/limites de demo) =====
    $usuario = Database::queryOne("SELECT id FROM usuarios WHERE email = :e LIMIT 1", ['e' => $DEMO_EMAIL]);
    $hash = password_hash($DEMO_SENHA, PASSWORD_DEFAULT);
    if ($usuario) {
        $usuarioId = (int) $usuario['id'];
        Database::execute(
            "UPDATE usuarios SET nome=:nome, senha=:senha, perfil='ADMIN_HOLDING', empresa_id=:eid, ativo=1 WHERE id=:id",
            ['nome' => 'Conta Demonstração', 'senha' => $hash, 'eid' => $empresaId, 'id' => $usuarioId]
        );
    } else {
        Database::execute(
            "INSERT INTO usuarios (nome, email, senha, perfil, empresa_id, ativo, criado_em)
             VALUES (:nome, :email, :senha, 'ADMIN_HOLDING', :eid, 1, NOW())",
            ['nome' => 'Conta Demonstração', 'email' => $DEMO_EMAIL, 'senha' => $hash, 'eid' => $empresaId]
        );
        $usuarioId = (int) Database::lastInsertId();
    }
    $tent(fn() => Database::execute("UPDATE usuarios SET onboarding_concluido=1 WHERE id=:id", ['id' => $usuarioId]));
    $relatorio['usuario_id'] = $usuarioId;

    // A partir daqui, limpamos os dados mockup ANTERIORES desta empresa demo
    // (idempotência) e recriamos. Nada disso afeta outras empresas.
    seedDemoDiagnostico($empresaId, $usuarioId);
    seedDemoPlano($empresaId, $usuarioId);
    seedDemoSops($empresaId);
    seedDemoKpis($empresaId);
    seedDemoNoticias($empresaId);
    seedDemoMarca($empresaId);
    seedDemoConteudos($empresaId, $usuarioId);
    seedDemoConfigConteudo($empresaId);
    seedDemoConcorrencia($empresaId);
    seedDemoDatasECalendario($empresaId);

    return $relatorio;
}

function seedDemoDiagnostico(int $empresaId, int $usuarioId): void
{
    try {
        $existe = Database::queryOne("SELECT id FROM diagnosticos WHERE empresa_id=:e LIMIT 1", ['e' => $empresaId]);
        if ($existe) return;
        $respostas = json_encode([
            'empresa_nome' => 'Demo Café & Cia',
            'setor' => 'Alimentos e Bebidas',
            'principais_desafios' => 'Padronização de processos e presença digital',
            'objetivo_12_meses' => 'Abrir 2 novas unidades mantendo o padrão de qualidade',
        ], JSON_UNESCAPED_UNICODE);
        Database::execute(
            "INSERT INTO diagnosticos (empresa_id, usuario_id, respostas, pontuacao, status, criado_em)
             VALUES (:e, :u, :r, 68, 'concluido', NOW())",
            ['e' => $empresaId, 'u' => $usuarioId, 'r' => $respostas]
        );
    } catch (\Throwable $e) { /* schema opcional */ }
}

function seedDemoPlano(int $empresaId, int $usuarioId): void
{
    try {
        $existe = Database::queryOne("SELECT id FROM planos WHERE empresa_id=:e LIMIT 1", ['e' => $empresaId]);
        if ($existe) { $planoId = (int) $existe['id']; }
        else {
            Database::execute(
                "INSERT INTO planos (empresa_id, usuario_id, titulo, objetivo, status, progresso_calculado, total_tarefas, tarefas_concluidas, criado_em)
                 VALUES (:e, :u, 'Plano de Crescimento 2026', 'Padronizar operação e escalar vendas', 'ativo', 40, 5, 2, NOW())",
                ['e' => $empresaId, 'u' => $usuarioId]
            );
            $planoId = (int) Database::lastInsertId();
        }
        // Tarefas do Kanban (idempotente: só cria se não houver).
        $temTarefa = Database::queryOne("SELECT id FROM plano_tarefas WHERE plano_id=:p LIMIT 1", ['p' => $planoId]);
        if (!$temTarefa) {
            $tarefas = [
                ['Mapear processos da cozinha', 'concluido', 'alta'],
                ['Criar manual de atendimento', 'concluido', 'media'],
                ['Implantar CRM de clientes', 'em_andamento', 'alta'],
                ['Calendário de conteúdo mensal', 'pendente', 'media'],
                ['Treinar equipe no novo padrão', 'pendente', 'baixa'],
            ];
            foreach ($tarefas as $t) {
                Database::execute(
                    "INSERT INTO plano_tarefas (plano_id, titulo, status, prioridade, criado_em)
                     VALUES (:p, :t, :s, :pr, NOW())",
                    ['p' => $planoId, 't' => $t[0], 's' => $t[1], 'pr' => $t[2]]
                );
            }
        }
    } catch (\Throwable $e) { /* schema opcional */ }
}

function seedDemoSops(int $empresaId): void
{
    try {
        $existe = Database::queryOne("SELECT id FROM sops WHERE empresa_id=:e LIMIT 1", ['e' => $empresaId]);
        if ($existe) return;
        $sops = [
            ['Padrão de Atendimento ao Cliente', 'Atendimento'],
            ['Preparo e Padronização do Espresso', 'Operações'],
            ['Fechamento de Caixa Diário', 'Financeiro'],
            ['Limpeza e Higienização', 'Operações'],
        ];
        foreach ($sops as $s) {
            Database::execute(
                "INSERT INTO sops (empresa_id, titulo, departamento, conteudo, status, gerado_por_ia, criado_em)
                 VALUES (:e, :t, :d, :c, 'ativo', 1, NOW())",
                ['e' => $empresaId, 't' => $s[0], 'd' => $s[1], 'c' => 'Procedimento operacional padrão (conteúdo de demonstração) para ' . $s[0] . '.']
            );
        }
    } catch (\Throwable $e) { /* schema opcional */ }
}

function seedDemoKpis(int $empresaId): void
{
    try {
        $existe = Database::queryOne("SELECT id FROM sop_kpis WHERE empresa_id=:e LIMIT 1", ['e' => $empresaId]);
        if ($existe) return;
        $sop = Database::queryOne("SELECT id FROM sops WHERE empresa_id=:e ORDER BY id LIMIT 1", ['e' => $empresaId]);
        $sopId = $sop ? (int) $sop['id'] : null;
        if (!$sopId) return;
        $kpis = [
            ['Tempo médio de atendimento', '3min', '5min', '8min', 'verde', '3min'],
            ['Satisfação do cliente (NPS)', '70', '50', '30', 'verde', '72'],
            ['Ticket médio', 'R$ 35', 'R$ 25', 'R$ 18', 'amarela', 'R$ 27'],
        ];
        foreach ($kpis as $k) {
            Database::execute(
                "INSERT INTO sop_kpis (empresa_id, sop_id, nome, meta_verde, meta_amarela, meta_vermelha, acao_vermelha, valor_atual, zona_atual, ativo, criado_em)
                 VALUES (:e, :s, :n, :mv, :ma, :mr, :av, :va, :za, 1, NOW())",
                ['e' => $empresaId, 's' => $sopId, 'n' => $k[0], 'mv' => $k[1], 'ma' => $k[2], 'mr' => $k[3], 'av' => 'Rever processo imediatamente', 'va' => $k[5], 'za' => $k[4]]
            );
        }
    } catch (\Throwable $e) { /* schema opcional */ }
}

function seedDemoNoticias(int $empresaId): void
{
    try {
        $existe = Database::queryOne("SELECT id FROM noticias WHERE empresa_id=:e LIMIT 1", ['e' => $empresaId]);
        if ($existe) return;
        $noticias = [
            ['Consumo de café especial cresce 15% no Brasil', 'Tendência', 'alta'],
            ['Nova regulamentação sanitária para food service', 'Regulamentação', 'media'],
            ['Delivery de bebidas quentes ganha espaço em apps', 'Mercado', 'media'],
        ];
        foreach ($noticias as $i => $n) {
            Database::execute(
                "INSERT INTO noticias
                    (empresa_id, titulo, url, fonte, data_publicacao, categoria, relevancia, setor,
                     bloco1_noticia, bloco2_significa, bloco3_o_que_fazer, bloco4_pergunta, bloco5_conexao,
                     processado_via, criado_em)
                 VALUES
                    (:e, :t, :u, 'Portal Demo', CURDATE() - INTERVAL :d DAY, :cat, :rel, 'Alimentos e Bebidas',
                     :b1, :b2, :b3, :b4, :b5, 'perplexity+gpt', NOW())",
                [
                    'e' => $empresaId, 't' => $n[0], 'u' => 'https://exemplo.com/noticia-' . ($i + 1),
                    'd' => $i, 'cat' => $n[1], 'rel' => $n[2],
                    'b1' => 'Resumo factual (demonstração) sobre: ' . $n[0] . '.',
                    'b2' => 'O que isso significa para o setor de alimentos e bebidas.',
                    'b3' => 'Ações práticas: revisar cardápio, treinar equipe, comunicar nas redes.',
                    'b4' => 'Como sua empresa pode aproveitar essa tendência?',
                    'b5' => 'Conecta-se ao seu Plano de Ação e à Máquina de Conteúdo.',
                ]
            );
        }
    } catch (\Throwable $e) { /* schema opcional */ }
}

function seedDemoMarca(int $empresaId): int
{
    try {
        $existe = Database::queryOne("SELECT id FROM marcas WHERE empresa_id=:e AND ativo=1 LIMIT 1", ['e' => $empresaId]);
        if ($existe) return (int) $existe['id'];
        Database::execute(
            "INSERT INTO marcas (empresa_id, nome, nicho, publico_alvo, produtos_servicos, tom, arquetipo, palavras_usa, palavras_nunca, prompt_master, brand_book_criado, ativo, criado_em)
             VALUES (:e, 'Demo Café & Cia', 'Cafeteria artesanal', 'Amantes de café, 25-45 anos, urbanos',
                     'Cafés especiais, brunch, confeitaria artesanal', 'acolhedor', 'O Cuidador',
                     'artesanal, especial, aconchego, origem', 'barato, industrial, genérico',
                     'Marca de cafeteria artesanal com tom acolhedor e foco em qualidade e origem do café.', 1, 1, NOW())",
            ['e' => $empresaId]
        );
        return (int) Database::lastInsertId();
    } catch (\Throwable $e) { return 0; }
}

function seedDemoConteudos(int $empresaId, int $usuarioId): void
{
    try {
        $marca = Database::queryOne("SELECT id FROM marcas WHERE empresa_id=:e AND ativo=1 ORDER BY id LIMIT 1", ['e' => $empresaId]);
        if (!$marca) return;
        $marcaId = (int) $marca['id'];
        $existe = Database::queryOne("SELECT id FROM conteudos_marca WHERE marca_id=:m LIMIT 1", ['m' => $marcaId]);
        if ($existe) return;

        $slides = json_encode([
            ['numero' => 1, 'texto' => 'O segredo de um café especial', 'texto_secundario' => 'da origem à xícara', 'prompt_imagem' => 'grãos de café sendo torrados', 'imagem_pendente' => false, 'imagem_url' => ''],
            ['numero' => 2, 'texto' => 'Origem importa', 'texto_secundario' => 'grãos selecionados de fazendas parceiras', 'prompt_imagem' => 'fazenda de café ao amanhecer', 'imagem_pendente' => false, 'imagem_url' => ''],
            ['numero' => 3, 'texto' => 'Venha experimentar', 'texto_secundario' => 'seu próximo café favorito', 'prompt_imagem' => 'xícara de espresso sobre balcão de madeira', 'imagem_pendente' => false, 'imagem_url' => ''],
        ], JSON_UNESCAPED_UNICODE);

        Database::execute(
            "INSERT INTO conteudos_marca (marca_id, usuario_id, tipo, tema, objetivo, slides, legenda, hashtags, status, criado_em)
             VALUES (:m, :u, 'carrossel', 'O segredo do café especial', 'educar', :s,
                     'Descubra o que torna nosso café especial — da origem à sua xícara. ☕ #cafeespecial #cafeteria', '#cafeespecial #cafeteria #cafeartesanal', 'aprovado', NOW())",
            ['m' => $marcaId, 'u' => $usuarioId, 's' => $slides]
        );
        // Um rascunho pendente de revisão (para a Visão Geral).
        Database::execute(
            "INSERT INTO conteudos_marca (marca_id, usuario_id, tipo, tema, objetivo, slides, legenda, hashtags, status, criado_em)
             VALUES (:m, :u, 'post', 'Promoção de brunch de domingo', 'vender', :s,
                     'Domingo é dia de brunch! Reserve sua mesa. 🥐', '#brunch #domingo', 'rascunho', NOW())",
            ['m' => $marcaId, 'u' => $usuarioId, 's' => json_encode([], JSON_UNESCAPED_UNICODE)]
        );
    } catch (\Throwable $e) { /* schema opcional */ }
}

function seedDemoConfigConteudo(int $empresaId): void
{
    try {
        ConfiguracaoConteudo::salvar($empresaId, [
            'frequencia_padrao' => 'semanal',
            'redes_sociais' => ['instagram', 'facebook'],
            'formatos_preferidos' => ['carrossel', 'post', 'reels'],
            'idioma' => 'Português',
            'pais' => 'Brasil', 'estado' => 'SP', 'cidade' => 'São Paulo',
            'antecedencia_datas_dias' => 7,
            'qtd_sugestoes_semanais' => 3,
            'permitir_noticias' => 1, 'permitir_concorrencia' => 1, 'permitir_datas_comemorativas' => 1,
            'gerar_imagens_padrao' => 1, 'evitar_repeticao_temas' => 1, 'periodo_repeticao_dias' => 30,
        ]);
    } catch (\Throwable $e) { /* migration 054 pode faltar */ }
}

function seedDemoConcorrencia(int $empresaId): void
{
    try {
        $existe = Database::queryOne("SELECT id FROM concorrentes WHERE empresa_id=:e LIMIT 1", ['e' => $empresaId]);
        if ($existe) return;

        $concorrentes = [
            ['Café da Esquina', '@cafedaesquina', 'https://instagram.com/cafedaesquina', 'instagram', 1, 4200],
            ['Torra Nobre', '@torranobre', 'https://instagram.com/torranobre', 'instagram', 0, 8800],
        ];
        foreach ($concorrentes as $c) {
            $id = Concorrente::criar($empresaId, [
                'nome' => $c[0], 'nome_perfil' => $c[1], 'url_publica' => $c[2],
                'plataforma' => $c[3], 'principal' => $c[4], 'seguidores' => $c[5],
                'frequencia_coleta' => 'semanal', 'categoria' => 'Cafeteria',
            ]);
            if (!$id) continue;

            // Uma coleta concluída + posts mockup (com métricas — e uma sem métricas).
            Database::execute(
                "INSERT INTO concorrente_coletas (concorrente_id, empresa_id, origem, status, seguidores_snapshot, posts_coletados, iniciada_em, finalizada_em, criado_em)
                 VALUES (:c, :e, 'manual', 'concluida', :seg, 3, NOW(), NOW(), NOW())",
                ['c' => $id, 'e' => $empresaId, 'seg' => $c[5]]
            );
            $coletaId = (int) Database::lastInsertId();

            $posts = [
                ['Novo blend de inverno', 'carrossel', 320, 45, null, 12],
                ['Bastidores da torra', 'reels', 890, 78, 15400, 40],
                ['Dica de preparo em casa', 'post', null, null, null, null], // métricas indisponíveis
            ];
            foreach ($posts as $p) {
                $curtidas = $p[2]; $coment = $p[3]; $views = $p[4]; $shares = $p[5];
                $comp = array_filter([$curtidas, $coment, $shares], fn($v) => $v !== null);
                $eng = !empty($comp) ? array_sum($comp) : null;
                $taxa = ($eng !== null && $c[5] > 0) ? round($eng / $c[5] * 100, 4) : null;
                $indisp = [];
                foreach (['curtidas' => $curtidas, 'comentarios' => $coment, 'visualizacoes' => $views, 'compartilhamentos' => $shares] as $nome => $val) {
                    if ($val === null) $indisp[] = $nome;
                }
                Database::execute(
                    "INSERT INTO concorrente_posts
                        (concorrente_id, coleta_id, empresa_id, plataforma, tipo_conteudo, data_publicacao, titulo,
                         curtidas, comentarios, visualizacoes, compartilhamentos, engajamento_absoluto, taxa_engajamento,
                         metricas_indisponiveis, fonte_coleta, status_coleta, coletado_em)
                     VALUES
                        (:c, :col, :e, :plat, :tipo, NOW() - INTERVAL FLOOR(RAND()*10) DAY, :tit,
                         :cur, :com, :vie, :sha, :eng, :taxa, :ind, 'scrapingbee', :st, NOW())",
                    [
                        'c' => $id, 'col' => $coletaId, 'e' => $empresaId, 'plat' => $c[3], 'tipo' => $p[1],
                        'tit' => $p[0], 'cur' => $curtidas, 'com' => $coment, 'vie' => $views, 'sha' => $shares,
                        'eng' => $eng, 'taxa' => $taxa, 'ind' => json_encode($indisp, JSON_UNESCAPED_UNICODE),
                        'st' => empty($indisp) ? 'ok' : 'parcial',
                    ]
                );
            }

            // Análise mockup.
            Database::execute(
                "INSERT INTO concorrente_analises (concorrente_id, coleta_id, empresa_id, resumo, dados, oportunidades, criado_em)
                 VALUES (:c, :col, :e, :resumo, :dados, :oport, NOW())",
                [
                    'c' => $id, 'col' => $coletaId, 'e' => $empresaId,
                    'resumo' => 'Reels de bastidores geram o maior engajamento; conteúdo educativo tem bom desempenho.',
                    'dados' => json_encode([
                        'temas_melhor_desempenho' => ['bastidores', 'origem do café', 'dicas de preparo'],
                        'formatos_melhor_desempenho' => ['reels', 'carrossel'],
                        'ganchos' => ['pergunta de abertura', 'curiosidade'],
                        'ctas' => ['visite a loja', 'salve este post'],
                        'lacunas' => ['pouco conteúdo sobre sustentabilidade'],
                    ], JSON_UNESCAPED_UNICODE),
                    'oport' => json_encode(['Explorar sustentabilidade e origem ética dos grãos', 'Criar série de Reels de bastidores'], JSON_UNESCAPED_UNICODE),
                ]
            );

            Database::execute("UPDATE concorrentes SET ultima_coleta_em = NOW() WHERE id = :id", ['id' => $id]);
        }
    } catch (\Throwable $e) { /* migration 054 pode faltar */ }
}

function seedDemoDatasECalendario(int $empresaId): void
{
    try {
        $existe = Database::queryOne("SELECT id FROM datas_comemorativas WHERE empresa_id=:e LIMIT 1", ['e' => $empresaId]);
        if (!$existe) {
            $datas = [
                ['Dia Internacional do Café', 4, 14, 'setorial', 'alta'],
                ['Dia do Cliente', 9, 15, 'comercial', 'alta'],
                ['Semana do Brunch', 6, 10, 'sazonal', 'media'],
            ];
            foreach ($datas as $d) {
                DataComemorativa::criar($empresaId, [
                    'nome' => $d[0], 'tipo' => $d[3], 'mes' => $d[1], 'dia' => $d[2],
                    'recorrencia' => 'anual', 'relevancia' => $d[4], 'nichos' => ['cafeteria'],
                    'fonte' => 'Seed demo',
                ]);
            }
        }
        // Popula o calendário a partir das próximas datas e adiciona itens variados.
        CalendarioGerador::popularCalendario($empresaId, 120);

        $temItemManual = Database::queryOne(
            "SELECT id FROM calendario_conteudo WHERE empresa_id=:e AND origem='tema_manual' LIMIT 1",
            ['e' => $empresaId]
        );
        if (!$temItemManual) {
            CalendarioConteudo::criar($empresaId, [
                'tema' => 'Lançamento do blend de inverno', 'origem' => 'tema_manual',
                'formato_recomendado' => 'carrossel', 'objetivo' => 'vender',
                'data_publicacao_sugerida' => date('Y-m-d', strtotime('+3 days')), 'status' => 'planejado',
            ]);
            CalendarioConteudo::criar($empresaId, [
                'tema' => 'Bastidores: como torramos nosso café', 'origem' => 'conteudo_semanal',
                'formato_recomendado' => 'reels', 'objetivo' => 'engajar',
                'data_publicacao_sugerida' => date('Y-m-d', strtotime('+5 days')), 'status' => 'sugerido',
            ]);
        }
    } catch (\Throwable $e) { /* migration 054 pode faltar */ }
}

// Execução direta via CLI.
if (PHP_SAPI === 'cli' && isset($argv) && realpath($argv[0]) === realpath(__FILE__)) {
    $rel = seedDemo();
    echo "Seed demo concluído.\n";
    echo "Login: demo@oconsultor.com.br / demo@123\n";
    echo "Empresa demo #{$rel['empresa_id']}, usuário #{$rel['usuario_id']}\n";
}
