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
        
        if ($usuario['perfil'] === 'ADMIN_HOLDING') {
            return Database::query(
                "SELECT p.*, e.nome as empresa_nome 
                 FROM planos p 
                 LEFT JOIN empresas e ON p.empresa_id = e.id 
                 ORDER BY p.criado_em DESC LIMIT 50"
            );
        } else {
            return Database::query(
                "SELECT p.*, e.nome as empresa_nome 
                 FROM planos p 
                 LEFT JOIN empresas e ON p.empresa_id = e.id 
                 WHERE p.usuario_id = :usuario_id OR p.empresa_id = :empresa_id
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
        $tarefas = Database::query(
            "SELECT * FROM plano_tarefas WHERE plano_id = :plano_id ORDER BY ordem_kanban ASC, criado_em ASC",
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
}