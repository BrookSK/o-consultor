<?php
/**
 * Model Plano — Planos de Ação
 */

class Plano
{
    /**
     * Buscar plano por ID
     */
    public static function buscarPorId(int $id): ?array
    {
        return Database::queryOne(
            "SELECT p.*, e.nome as empresa_nome, d.pontuacao as diagnostico_score 
             FROM planos p 
             LEFT JOIN empresas e ON p.empresa_id = e.id 
             LEFT JOIN diagnosticos d ON p.diagnostico_id = d.id 
             WHERE p.id = :id LIMIT 1",
            ['id' => $id]
        );
    }

    /**
     * Criar novo plano
     */
    public static function criar(array $dados): int|false
    {
        $sucesso = Database::execute(
            "INSERT INTO planos (empresa_id, diagnostico_id, usuario_id, titulo, objetivo, periodo_inicio, periodo_fim, status, criado_em) 
             VALUES (:empresa_id, :diagnostico_id, :usuario_id, :titulo, :objetivo, :periodo_inicio, :periodo_fim, :status, NOW())",
            [
                'empresa_id' => $dados['empresa_id'],
                'diagnostico_id' => $dados['diagnostico_id'] ?? null,
                'usuario_id' => $dados['usuario_id'],
                'titulo' => $dados['titulo'],
                'objetivo' => $dados['objetivo'] ?? null,
                'periodo_inicio' => $dados['periodo_inicio'] ?? null,
                'periodo_fim' => $dados['periodo_fim'] ?? null,
                'status' => $dados['status'] ?? 'em_elaboracao'
            ]
        );

        return $sucesso ? (int) Database::lastInsertId() : false;
    }

    /**
     * Atualizar plano
     */
    /**
     * Exclui um plano e todos os seus filhos (tarefas, prioridades, reuniões,
     * métricas, comentários e registros). Limpa manualmente por segurança,
     * caso alguma FK não tenha ON DELETE CASCADE.
     */
    public static function excluir(int $id): bool
    {
        try {
            try { Database::execute("DELETE c FROM plano_tarefa_comentarios c JOIN plano_tarefas t ON c.tarefa_id = t.id WHERE t.plano_id = :p", ['p' => $id]); } catch (Exception $e) {}
            try { Database::execute("DELETE r FROM plano_metricas_registros r JOIN plano_metricas m ON r.metrica_id = m.id WHERE m.plano_id = :p", ['p' => $id]); } catch (Exception $e) {}
            try { Database::execute("DELETE FROM plano_metricas WHERE plano_id = :p", ['p' => $id]); } catch (Exception $e) {}
            try { Database::execute("DELETE FROM plano_tarefas WHERE plano_id = :p", ['p' => $id]); } catch (Exception $e) {}
            try { Database::execute("DELETE FROM plano_prioridades WHERE plano_id = :p", ['p' => $id]); } catch (Exception $e) {}
            try { Database::execute("DELETE FROM plano_reunioes WHERE plano_id = :p", ['p' => $id]); } catch (Exception $e) {}
            return Database::execute("DELETE FROM planos WHERE id = :id", ['id' => $id]);
        } catch (Exception $e) {
            Logger::error('Erro ao excluir plano: ' . $e->getMessage());
            return false;
        }
    }

    public static function atualizar(int $id, array $dados): bool
    {
        $campos = [];
        $params = ['id' => $id];

        foreach (['titulo', 'objetivo', 'periodo_inicio', 'periodo_fim', 'status'] as $campo) {
            if (isset($dados[$campo])) {
                $campos[] = "$campo = :$campo";
                $params[$campo] = $dados[$campo];
            }
        }

        if (empty($campos)) {
            return false;
        }

        $campos[] = "atualizado_em = NOW()";
        $sql = "UPDATE planos SET " . implode(', ', $campos) . " WHERE id = :id";

        return Database::execute($sql, $params);
    }

    /**
     * Listar planos por empresa ou usuário
     */
    public static function listarPorUsuario(int $usuarioId): array
    {
        // Admin vê todos, outros vêem apenas da própria empresa
        $usuario = User::buscarPorId($usuarioId);
        
        // Oculta planos cujo usuário criador está arquivado (ativo = 0).
        if ($usuario['perfil'] === 'ADMIN_HOLDING') {
            return Database::query(
                "SELECT p.*, e.nome as empresa_nome 
                 FROM planos p 
                 LEFT JOIN empresas e ON p.empresa_id = e.id 
                 LEFT JOIN usuarios pu ON p.usuario_id = pu.id
                 WHERE (pu.id IS NULL OR pu.ativo = 1)
                 ORDER BY p.criado_em DESC LIMIT 50"
            );
        } else {
            return Database::query(
                "SELECT p.*, e.nome as empresa_nome 
                 FROM planos p 
                 LEFT JOIN empresas e ON p.empresa_id = e.id 
                 LEFT JOIN usuarios pu ON p.usuario_id = pu.id
                 WHERE (p.usuario_id = :usuario_id OR p.empresa_id = :empresa_id)
                   AND (pu.id IS NULL OR pu.ativo = 1)
                 ORDER BY p.criado_em DESC",
                ['usuario_id' => $usuarioId, 'empresa_id' => $usuario['empresa_id']]
            );
        }
    }

    /**
     * Salvar prioridades geradas pela IA
     */
    public static function salvarPrioridades(int $planoId, array $prioridades): bool
    {
        try {
            // Limpar prioridades existentes
            Database::execute("DELETE FROM plano_prioridades WHERE plano_id = :plano_id", ['plano_id' => $planoId]);
            
            // Inserir novas prioridades
            foreach ($prioridades as $index => $prioridade) {
                Database::execute(
                    "INSERT INTO plano_prioridades (plano_id, area, descricao_problema, acao_sugerida, impacto, urgencia, bloco_origem, ordem_prioridade, criado_em) 
                     VALUES (:plano_id, :area, :descricao_problema, :acao_sugerida, :impacto, :urgencia, :bloco_origem, :ordem, NOW())",
                    [
                        'plano_id' => $planoId,
                        'area' => $prioridade['area'],
                        'descricao_problema' => $prioridade['descricao_problema'],
                        'acao_sugerida' => $prioridade['acao_sugerida'],
                        'impacto' => $prioridade['impacto'] ?? 'medio',
                        'urgencia' => $prioridade['urgencia'] ?? 'media',
                        'bloco_origem' => $prioridade['bloco_origem'] ?? 1,
                        'ordem' => $index + 1
                    ]
                );
            }
            
            return true;
        } catch (Exception $e) {
            Logger::error('Erro ao salvar prioridades: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Buscar prioridades do plano
     */
    public static function buscarPrioridades(int $planoId): array
    {
        return Database::query(
            "SELECT * FROM plano_prioridades WHERE plano_id = :plano_id ORDER BY ordem_prioridade ASC",
            ['plano_id' => $planoId]
        );
    }

    /**
     * Confirmar prioridades selecionadas
     */
    public static function confirmarPrioridades(int $planoId, array $prioridadeIds): bool
    {
        try {
            // Desmarcar todas
            Database::execute(
                "UPDATE plano_prioridades SET confirmada = 0 WHERE plano_id = :plano_id",
                ['plano_id' => $planoId]
            );
            
            // Marcar as selecionadas
            if (!empty($prioridadeIds)) {
                $placeholders = implode(',', array_fill(0, count($prioridadeIds), '?'));
                Database::execute(
                    "UPDATE plano_prioridades SET confirmada = 1 WHERE plano_id = ? AND id IN ($placeholders)",
                    array_merge([$planoId], $prioridadeIds)
                );
            }
            
            return true;
        } catch (Exception $e) {
            Logger::error('Erro ao confirmar prioridades: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Criar tarefas a partir das prioridades confirmadas
     */
    public static function criarTarefasDePrioridades(int $planoId, array $tarefasData): bool
    {
        try {
            foreach ($tarefasData as $tarefa) {
                Database::execute(
                    "INSERT INTO plano_tarefas (plano_id, prioridade_id, titulo, descricao, area, responsavel, prazo, prioridade, status, criado_em) 
                     VALUES (:plano_id, :prioridade_id, :titulo, :descricao, :area, :responsavel, :prazo, :prioridade, 'pendente', NOW())",
                    [
                        'plano_id' => $planoId,
                        'prioridade_id' => $tarefa['prioridade_id'] ?? null,
                        'titulo' => $tarefa['titulo'],
                        'descricao' => $tarefa['descricao'] ?? null,
                        'area' => $tarefa['area'] ?? null,
                        'responsavel' => $tarefa['responsavel'] ?? null,
                        'prazo' => $tarefa['prazo'] ?? null,
                        'prioridade' => $tarefa['prioridade'] ?? 'media'
                    ]
                );
            }
            
            // Atualizar contadores do plano
            self::atualizarProgresso($planoId);
            
            // Mudar status para ativo
            self::atualizar($planoId, ['status' => 'ativo']);
            
            return true;
        } catch (Exception $e) {
            Logger::error('Erro ao criar tarefas: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Buscar tarefas do plano agrupadas por status
     */
    public static function buscarTarefasKanban(int $planoId): array
    {
        self::garantirEstruturaConsolidador();
        // Só tarefas LIBERADAS aparecem no Kanban (liberação progressiva por etapa).
        // Tarefas de etapas futuras (liberada=0) ficam ocultas até a etapa anterior concluir.
        $tarefas = Database::query(
            "SELECT * FROM plano_tarefas WHERE plano_id = :plano_id AND COALESCE(liberada,1) = 1 ORDER BY ordem_etapa ASC, ordem_kanban ASC, criado_em ASC",
            ['plano_id' => $planoId]
        );

        // Agrupar por status
        $kanban = [
            'pendente' => [],
            'em_andamento' => [],
            'bloqueado' => [],
            'concluido' => []
        ];

        foreach ($tarefas as $tarefa) {
            // Adicionar flags de status
            $tarefa['vencida'] = !empty($tarefa['prazo']) && $tarefa['prazo'] < date('Y-m-d') && $tarefa['status'] !== 'concluido';
            $tarefa['sem_atualizacao'] = !empty($tarefa['atualizado_em']) && strtotime($tarefa['atualizado_em']) < strtotime('-7 days');
            
            $kanban[$tarefa['status']][] = $tarefa;
        }

        return $kanban;
    }

    /**
     * Mover tarefa entre colunas do Kanban
     */
    public static function moverTarefa(int $tarefaId, string $novoStatus): bool
    {
        $statusValidos = ['pendente', 'em_andamento', 'bloqueado', 'concluido'];
        
        if (!in_array($novoStatus, $statusValidos)) {
            return false;
        }

        try {
            // Buscar plano_id da tarefa
            $tarefa = Database::queryOne("SELECT plano_id FROM plano_tarefas WHERE id = :id", ['id' => $tarefaId]);
            
            if (!$tarefa) {
                return false;
            }

            // Atualizar status da tarefa
            $sucesso = Database::execute(
                "UPDATE plano_tarefas SET status = :status, atualizado_em = NOW() WHERE id = :id",
                ['id' => $tarefaId, 'status' => $novoStatus]
            );

            if ($sucesso) {
                // Recalcular progresso do plano
                self::atualizarProgresso($tarefa['plano_id']);
            }

            return $sucesso;
        } catch (Exception $e) {
            Logger::error('Erro ao mover tarefa: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Atualizar progresso calculado do plano
     */
    public static function atualizarProgresso(int $planoId): void
    {
        $stats = Database::queryOne(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'concluido' THEN 1 ELSE 0 END) as concluidas
             FROM plano_tarefas WHERE plano_id = :plano_id",
            ['plano_id' => $planoId]
        );

        $total = (int) $stats['total'];
        $concluidas = (int) $stats['concluidas'];
        $progresso = $total > 0 ? round(($concluidas / $total) * 100, 2) : 0;

        Database::execute(
            "UPDATE planos SET total_tarefas = :total, tarefas_concluidas = :concluidas, progresso_calculado = :progresso, atualizado_em = NOW() WHERE id = :id",
            [
                'id' => $planoId,
                'total' => $total,
                'concluidas' => $concluidas,
                'progresso' => $progresso
            ]
        );
    }

    /**
     * Registrar reunião
     */
    public static function registrarReuniao(int $planoId, int $usuarioId, array $dados): bool
    {
        return Database::execute(
            "INSERT INTO plano_reunioes (plano_id, usuario_id, data_reuniao, participantes, decisoes, proximos_passos, criado_em) 
             VALUES (:plano_id, :usuario_id, :data_reuniao, :participantes, :decisoes, :proximos_passos, NOW())",
            [
                'plano_id' => $planoId,
                'usuario_id' => $usuarioId,
                'data_reuniao' => $dados['data_reuniao'],
                'participantes' => $dados['participantes'] ?? null,
                'decisoes' => $dados['decisoes'],
                'proximos_passos' => $dados['proximos_passos'] ?? null
            ]
        );
    }

    /**
     * Buscar reuniões do plano
     */
    public static function buscarReunioes(int $planoId): array
    {
        return Database::query(
            "SELECT r.*, u.nome as usuario_nome 
             FROM plano_reunioes r 
             LEFT JOIN usuarios u ON r.usuario_id = u.id 
             WHERE r.plano_id = :plano_id 
             ORDER BY r.data_reuniao DESC",
            ['plano_id' => $planoId]
        );
    }

    // ===================================================================
    // CONSOLIDADOR: etapas sequenciais, tarefas, calendário e métricas
    // ===================================================================

    /**
     * Garante que as colunas/tabelas da migration 034 existem (idempotente),
     * evitando depender da execução manual da migration em produção.
     */
    public static function garantirEstruturaConsolidador(): void
    {
        $alters = [
            "ALTER TABLE plano_tarefas ADD COLUMN ordem_etapa INT NOT NULL DEFAULT 1",
            "ALTER TABLE plano_tarefas ADD COLUMN hora TIME NULL",
            "ALTER TABLE plano_tarefas ADD COLUMN tipo VARCHAR(20) NOT NULL DEFAULT 'tarefa'",
            "ALTER TABLE plano_tarefas ADD COLUMN liberada TINYINT(1) NOT NULL DEFAULT 1",
            "ALTER TABLE plano_tarefas ADD COLUMN concluida_em DATETIME NULL",
            "ALTER TABLE planos ADD COLUMN score_maturidade DECIMAL(5,2) NOT NULL DEFAULT 0",
            "ALTER TABLE planos ADD COLUMN score_inicial DECIMAL(5,2) NULL",
            "ALTER TABLE planos ADD COLUMN total_etapas INT NOT NULL DEFAULT 0",
            "ALTER TABLE planos ADD COLUMN etapa_atual INT NOT NULL DEFAULT 1",
        ];
        foreach ($alters as $sql) {
            try { Database::execute($sql); } catch (Exception $e) { /* já existe */ }
        }
        try {
            Database::execute(
                "CREATE TABLE IF NOT EXISTS plano_metricas (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    plano_id INT UNSIGNED NOT NULL,
                    nome VARCHAR(150) NOT NULL,
                    categoria VARCHAR(60) NOT NULL DEFAULT 'geral',
                    unidade VARCHAR(30) NULL,
                    meta DECIMAL(15,2) NULL,
                    frequencia VARCHAR(20) NOT NULL DEFAULT 'mensal',
                    direcao VARCHAR(10) NOT NULL DEFAULT 'cima',
                    ativo TINYINT(1) NOT NULL DEFAULT 1,
                    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    atualizado_em DATETIME NULL,
                    INDEX idx_plano (plano_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
        } catch (Exception $e) { /* já existe */ }
        try {
            Database::execute(
                "CREATE TABLE IF NOT EXISTS plano_metricas_registros (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    metrica_id INT UNSIGNED NOT NULL,
                    valor DECIMAL(15,2) NOT NULL,
                    data_referencia DATE NOT NULL,
                    observacao VARCHAR(255) NULL,
                    usuario_id INT UNSIGNED NULL,
                    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_metrica (metrica_id),
                    INDEX idx_data (data_referencia)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
        } catch (Exception $e) { /* já existe */ }
    }

    /**
     * Cria uma tarefa avulsa no plano (usada pela criação por IA/manual).
     */
    public static function criarTarefa(int $planoId, array $t): int|false
    {
        self::garantirEstruturaConsolidador();
        $ok = Database::execute(
            "INSERT INTO plano_tarefas (plano_id, prioridade_id, ordem_etapa, titulo, descricao, area, responsavel, prazo, hora, prioridade, status, tipo, liberada, criado_em)
             VALUES (:plano_id, NULL, :ordem_etapa, :titulo, :descricao, :area, :responsavel, :prazo, :hora, :prioridade, 'pendente', :tipo, 1, NOW())",
            [
                'plano_id' => $planoId,
                'ordem_etapa' => (int) ($t['ordem_etapa'] ?? self::proximaEtapaLivre($planoId)),
                'titulo' => $t['titulo'],
                'descricao' => $t['descricao'] ?? null,
                'area' => $t['area'] ?? null,
                'responsavel' => $t['responsavel'] ?? null,
                'prazo' => $t['prazo'] ?? null,
                'hora' => $t['hora'] ?? null,
                'prioridade' => in_array($t['prioridade'] ?? 'media', ['alta','media','baixa']) ? $t['prioridade'] : 'media',
                'tipo' => in_array($t['tipo'] ?? 'tarefa', ['tarefa','reuniao','entrega','compromisso']) ? $t['tipo'] : 'tarefa',
            ]
        );
        if ($ok) {
            // Capturar o ID ANTES de qualquer outra query (atualizarProgresso roda
            // UPDATE/SELECT e zeraria o lastInsertId da conexão).
            $novoId = (int) Database::lastInsertId();
            self::atualizarProgresso($planoId);
            return $novoId > 0 ? $novoId : false;
        }
        return false;
    }

    private static function proximaEtapaLivre(int $planoId): int
    {
        $row = Database::queryOne("SELECT COALESCE(MAX(ordem_etapa),0) AS m FROM plano_tarefas WHERE plano_id = :p", ['p' => $planoId]);
        return ((int) ($row['m'] ?? 0)) + 1;
    }

    /**
     * Retorna TODAS as tarefas do plano (liberadas e não liberadas),
     * agrupadas por etapa e em ordem — a "fila" completa do roadmap.
     */
    public static function buscarFilaCompleta(int $planoId): array
    {
        self::garantirEstruturaConsolidador();
        // Só as tarefas do PLANO (originadas das prioridades do diagnóstico) entram no roadmap.
        // Compromissos avulsos (criados manualmente ou por IA) têm prioridade_id NULL e
        // NÃO aparecem aqui — eles vivem no Kanban/Calendário.
        $tarefas = Database::query(
            "SELECT * FROM plano_tarefas WHERE plano_id = :p AND prioridade_id IS NOT NULL
             ORDER BY ordem_etapa ASC, ordem_kanban ASC, criado_em ASC",
            ['p' => $planoId]
        );
        $porEtapa = [];
        foreach ($tarefas as $t) {
            $etapa = (int) ($t['ordem_etapa'] ?? 1);
            $porEtapa[$etapa][] = $t;
        }
        ksort($porEtapa);
        return $porEtapa;
    }

    /**
     * Libera (mostra no Kanban) ou recolhe (esconde) uma tarefa específica.
     */
    public static function definirLiberacaoTarefa(int $tarefaId, int $planoId, bool $liberada): bool
    {
        self::garantirEstruturaConsolidador();
        return Database::execute(
            "UPDATE plano_tarefas SET liberada = :lib, atualizado_em = NOW() WHERE id = :id AND plano_id = :p",
            ['lib' => $liberada ? 1 : 0, 'id' => $tarefaId, 'p' => $planoId]
        );
    }

    public static function tarefaPertenceAoPlano(int $tarefaId, int $planoId): bool
    {
        $r = Database::queryOne("SELECT id FROM plano_tarefas WHERE id = :id AND plano_id = :p", ['id' => $tarefaId, 'p' => $planoId]);
        return !empty($r);
    }

    /**
     * Exclui uma tarefa do plano (e seus comentários), recalculando o progresso.
     */
    public static function excluirTarefa(int $tarefaId, int $planoId): bool
    {
        try { Database::execute("DELETE FROM plano_tarefa_comentarios WHERE tarefa_id = :t", ['t' => $tarefaId]); } catch (Exception $e) {}
        $ok = Database::execute("DELETE FROM plano_tarefas WHERE id = :id AND plano_id = :p", ['id' => $tarefaId, 'p' => $planoId]);
        if ($ok) { self::atualizarProgresso($planoId); self::atualizarScoreMaturidade($planoId); }
        return $ok;
    }

    // ===================================================================
    // DETALHE DO CARD (estilo Trello): descrição, checklist, etiquetas,
    // datas, anexos (imagens coladas) e comentários.
    // ===================================================================

    public static function garantirColunasDetalheTarefa(): void
    {
        $alters = [
            "ALTER TABLE plano_tarefas ADD COLUMN data_inicio DATE NULL",
            "ALTER TABLE plano_tarefas ADD COLUMN checklist LONGTEXT NULL",
            "ALTER TABLE plano_tarefas ADD COLUMN etiquetas VARCHAR(500) NULL",
            "ALTER TABLE plano_tarefas ADD COLUMN anexos LONGTEXT NULL",
        ];
        foreach ($alters as $sql) {
            try { Database::execute($sql); } catch (Exception $e) { /* já existe */ }
        }
        try {
            Database::execute(
                "CREATE TABLE IF NOT EXISTS plano_tarefa_comentarios (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    tarefa_id INT UNSIGNED NOT NULL,
                    usuario_id INT UNSIGNED NULL,
                    usuario_nome VARCHAR(150) NULL,
                    texto TEXT NOT NULL,
                    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_tarefa (tarefa_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
        } catch (Exception $e) { /* já existe */ }
    }

    /** Detalhe completo de uma tarefa (para o modal do card). */
    public static function buscarTarefaDetalhe(int $tarefaId, int $planoId): ?array
    {
        self::garantirEstruturaConsolidador();
        self::garantirColunasDetalheTarefa();
        $t = Database::queryOne(
            "SELECT * FROM plano_tarefas WHERE id = :id AND plano_id = :p",
            ['id' => $tarefaId, 'p' => $planoId]
        );
        if (!$t) return null;
        // Contexto da prioridade de origem (problema + ação sugerida), quando for tarefa do plano.
        $t['contexto_prioridade'] = null;
        if (!empty($t['prioridade_id'])) {
            $pr = Database::queryOne(
                "SELECT area, descricao_problema, acao_sugerida, impacto, urgencia FROM plano_prioridades WHERE id = :id",
                ['id' => $t['prioridade_id']]
            );
            if ($pr) { $t['contexto_prioridade'] = $pr; }
        }
        $t['checklist'] = !empty($t['checklist']) ? (json_decode($t['checklist'], true) ?: []) : [];
        $t['anexos'] = !empty($t['anexos']) ? (json_decode($t['anexos'], true) ?: []) : [];
        $t['etiquetas'] = !empty($t['etiquetas']) ? array_values(array_filter(array_map('trim', explode(',', $t['etiquetas'])))) : [];
        $t['comentarios'] = Database::query(
            "SELECT texto, usuario_nome, criado_em FROM plano_tarefa_comentarios WHERE tarefa_id = :id ORDER BY criado_em ASC",
            ['id' => $tarefaId]
        );
        return $t;
    }

    /** Salva os campos editáveis do card. */
    public static function salvarTarefaDetalhe(int $tarefaId, int $planoId, array $d): bool
    {
        self::garantirColunasDetalheTarefa();
        return Database::execute(
            "UPDATE plano_tarefas SET
                titulo = :titulo,
                descricao = :descricao,
                responsavel = :responsavel,
                data_inicio = :data_inicio,
                prazo = :prazo,
                hora = :hora,
                prioridade = :prioridade,
                etiquetas = :etiquetas,
                checklist = :checklist,
                atualizado_em = NOW()
             WHERE id = :id AND plano_id = :p",
            [
                'titulo' => $d['titulo'],
                'descricao' => $d['descricao'] ?? null,
                'responsavel' => $d['responsavel'] ?? null,
                'data_inicio' => !empty($d['data_inicio']) ? $d['data_inicio'] : null,
                'prazo' => !empty($d['prazo']) ? $d['prazo'] : null,
                'hora' => !empty($d['hora']) ? $d['hora'] : null,
                'prioridade' => in_array($d['prioridade'] ?? 'media', ['alta','media','baixa']) ? $d['prioridade'] : 'media',
                'etiquetas' => !empty($d['etiquetas']) ? implode(',', (array) $d['etiquetas']) : null,
                'checklist' => isset($d['checklist']) ? json_encode($d['checklist'], JSON_UNESCAPED_UNICODE) : null,
                'id' => $tarefaId,
                'p' => $planoId,
            ]
        );
    }

    /** Anexa uma imagem (URL) à tarefa. */
    public static function adicionarAnexoTarefa(int $tarefaId, int $planoId, string $url): bool
    {
        self::garantirColunasDetalheTarefa();
        $t = Database::queryOne("SELECT anexos FROM plano_tarefas WHERE id = :id AND plano_id = :p", ['id' => $tarefaId, 'p' => $planoId]);
        if (!$t) return false;
        $anexos = !empty($t['anexos']) ? (json_decode($t['anexos'], true) ?: []) : [];
        $anexos[] = ['url' => $url, 'em' => date('Y-m-d H:i:s')];
        return Database::execute(
            "UPDATE plano_tarefas SET anexos = :a, atualizado_em = NOW() WHERE id = :id AND plano_id = :p",
            ['a' => json_encode($anexos, JSON_UNESCAPED_UNICODE), 'id' => $tarefaId, 'p' => $planoId]
        );
    }

    public static function adicionarComentarioTarefa(int $tarefaId, ?int $usuarioId, string $usuarioNome, string $texto): bool
    {
        self::garantirColunasDetalheTarefa();
        return Database::execute(
            "INSERT INTO plano_tarefa_comentarios (tarefa_id, usuario_id, usuario_nome, texto, criado_em)
             VALUES (:t, :u, :un, :tx, NOW())",
            ['t' => $tarefaId, 'u' => $usuarioId, 'un' => $usuarioNome, 'tx' => $texto]
        );
    }

    /**
     * Cria as tarefas de um plano organizadas em ETAPAS sequenciais.
     * Apenas a etapa 1 nasce liberada; as demais são liberadas conforme
     * as anteriores forem concluídas (liberação progressiva no Kanban).
     */
    public static function criarTarefasEmEtapas(int $planoId, array $tarefas): void
    {
        self::garantirEstruturaConsolidador();
        foreach ($tarefas as $t) {
            $etapa = (int) ($t['ordem_etapa'] ?? 1);
            Database::execute(
                "INSERT INTO plano_tarefas (plano_id, prioridade_id, ordem_etapa, titulo, descricao, area, responsavel, prazo, prioridade, status, tipo, liberada, criado_em)
                 VALUES (:plano_id, :prioridade_id, :ordem_etapa, :titulo, :descricao, :area, :responsavel, :prazo, :prioridade, 'pendente', 'tarefa', :liberada, NOW())",
                [
                    'plano_id' => $planoId,
                    'prioridade_id' => $t['prioridade_id'] ?? null,
                    'ordem_etapa' => $etapa,
                    'titulo' => $t['titulo'],
                    'descricao' => $t['descricao'] ?? null,
                    'area' => $t['area'] ?? null,
                    'responsavel' => $t['responsavel'] ?? null,
                    'prazo' => $t['prazo'] ?? null,
                    'prioridade' => in_array($t['prioridade'] ?? 'media', ['alta','media','baixa']) ? $t['prioridade'] : 'media',
                    'liberada' => $etapa === 1 ? 1 : 0,
                ]
            );
        }
        $totalEtapas = 0;
        foreach ($tarefas as $t) { $totalEtapas = max($totalEtapas, (int) ($t['ordem_etapa'] ?? 1)); }
        Database::execute(
            "UPDATE planos SET total_etapas = :te, etapa_atual = 1 WHERE id = :id",
            ['te' => $totalEtapas, 'id' => $planoId]
        );
        self::atualizarProgresso($planoId);
    }

    /**
     * Ao concluir tarefas, libera a próxima etapa quando a atual termina.
     * Retorna quantas tarefas novas foram liberadas.
     */
    public static function liberarProximaEtapa(int $planoId): int
    {
        self::garantirEstruturaConsolidador();
        // Descobrir a menor etapa que ainda tem tarefa não concluída.
        $etapaCorrente = Database::queryOne(
            "SELECT MIN(ordem_etapa) AS etapa FROM plano_tarefas WHERE plano_id = :p AND status <> 'concluido'",
            ['p' => $planoId]
        );
        $etapa = $etapaCorrente['etapa'] ?? null;
        if ($etapa === null) {
            return 0; // tudo concluído
        }
        // Liberar todas as tarefas dessa etapa corrente (caso ainda travadas).
        $afetadas = Database::execute(
            "UPDATE plano_tarefas SET liberada = 1 WHERE plano_id = :p AND ordem_etapa = :e AND liberada = 0",
            ['p' => $planoId, 'e' => (int) $etapa]
        );
        Database::execute("UPDATE planos SET etapa_atual = :e WHERE id = :id", ['e' => (int) $etapa, 'id' => $planoId]);
        return is_int($afetadas) ? $afetadas : 0;
    }

    /**
     * Atualiza o score de maturidade do plano proporcional ao progresso.
     * Parte do score_inicial (diagnóstico) e caminha até 100 conforme conclui.
     */
    public static function atualizarScoreMaturidade(int $planoId): void
    {
        $p = Database::queryOne("SELECT progresso_calculado, score_inicial FROM planos WHERE id = :id", ['id' => $planoId]);
        if (!$p) return;
        $base = $p['score_inicial'] !== null ? (float) $p['score_inicial'] : 0.0;
        $prog = (float) $p['progresso_calculado'];
        // score caminha do base até 100 conforme o progresso das tarefas.
        $score = round($base + ($prog / 100) * (100 - $base), 2);
        Database::execute("UPDATE planos SET score_maturidade = :s WHERE id = :id", ['s' => $score, 'id' => $planoId]);
    }

    /**
     * Itens de calendário: tarefas com prazo + reuniões.
     */
    public static function buscarCalendario(int $planoId): array
    {
        self::garantirEstruturaConsolidador();
        $itens = [];
        $tarefas = Database::query(
            "SELECT id, titulo, prazo, hora, tipo, status, prioridade, area, responsavel
             FROM plano_tarefas WHERE plano_id = :p AND prazo IS NOT NULL ORDER BY prazo ASC",
            ['p' => $planoId]
        );
        foreach ($tarefas as $t) {
            $itens[] = [
                'id' => 'tarefa-' . $t['id'],
                'titulo' => $t['titulo'],
                'data' => $t['prazo'],
                'hora' => $t['hora'],
                'tipo' => $t['tipo'] ?: 'tarefa',
                'status' => $t['status'],
                'meta' => ['area' => $t['area'], 'responsavel' => $t['responsavel'], 'prioridade' => $t['prioridade']],
            ];
        }
        $reunioes = Database::query(
            "SELECT id, data_reuniao, participantes FROM plano_reunioes WHERE plano_id = :p ORDER BY data_reuniao ASC",
            ['p' => $planoId]
        );
        foreach ($reunioes as $r) {
            $itens[] = [
                'id' => 'reuniao-' . $r['id'],
                'titulo' => 'Reunião' . (!empty($r['participantes']) ? ' — ' . $r['participantes'] : ''),
                'data' => substr($r['data_reuniao'], 0, 10),
                'hora' => substr($r['data_reuniao'], 11, 5) ?: null,
                'tipo' => 'reuniao',
                'status' => 'agendado',
                'meta' => [],
            ];
        }
        return $itens;
    }

    // ---- Métricas / KPIs do plano ----
    public static function criarMetrica(int $planoId, array $m): int|false
    {
        self::garantirEstruturaConsolidador();
        $ok = Database::execute(
            "INSERT INTO plano_metricas (plano_id, nome, categoria, unidade, meta, frequencia, direcao, ativo, criado_em)
             VALUES (:plano_id, :nome, :categoria, :unidade, :meta, :frequencia, :direcao, 1, NOW())",
            [
                'plano_id' => $planoId,
                'nome' => $m['nome'],
                'categoria' => $m['categoria'] ?? 'geral',
                'unidade' => $m['unidade'] ?? null,
                'meta' => $m['meta'] ?? null,
                'frequencia' => in_array($m['frequencia'] ?? 'mensal', ['semanal','quinzenal','mensal']) ? $m['frequencia'] : 'mensal',
                'direcao' => in_array($m['direcao'] ?? 'cima', ['cima','baixo']) ? $m['direcao'] : 'cima',
            ]
        );
        return $ok ? (int) Database::lastInsertId() : false;
    }

    public static function buscarMetricas(int $planoId): array
    {
        self::garantirEstruturaConsolidador();
        $metricas = Database::query(
            "SELECT * FROM plano_metricas WHERE plano_id = :p AND ativo = 1 ORDER BY categoria, nome",
            ['p' => $planoId]
        );
        foreach ($metricas as &$m) {
            $m['registros'] = Database::query(
                "SELECT valor, data_referencia, observacao FROM plano_metricas_registros
                 WHERE metrica_id = :m ORDER BY data_referencia ASC",
                ['m' => $m['id']]
            );
            $m['ultimo_valor'] = !empty($m['registros']) ? end($m['registros'])['valor'] : null;
        }
        unset($m);
        return $metricas;
    }

    public static function registrarMetrica(int $metricaId, float $valor, string $dataRef, ?string $obs, ?int $usuarioId): bool
    {
        self::garantirEstruturaConsolidador();
        return Database::execute(
            "INSERT INTO plano_metricas_registros (metrica_id, valor, data_referencia, observacao, usuario_id, criado_em)
             VALUES (:m, :v, :d, :o, :u, NOW())",
            ['m' => $metricaId, 'v' => $valor, 'd' => $dataRef, 'o' => $obs, 'u' => $usuarioId]
        );
    }

    public static function metricaPertenceAoPlano(int $metricaId, int $planoId): bool
    {
        $r = Database::queryOne("SELECT id FROM plano_metricas WHERE id = :m AND plano_id = :p", ['m' => $metricaId, 'p' => $planoId]);
        return !empty($r);
    }
}