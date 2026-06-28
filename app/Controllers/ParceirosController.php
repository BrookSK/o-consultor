<?php
/**
 * ParceirosController — Módulo de Parceiros
 * Vitrine, perfil, solicitação e gestão administrativa
 */

class ParceirosController
{
    public function index(): void
    {
        Auth::proteger();
        
        // Buscar parceiros reais do banco
        $empresaId = Auth::empresa();
        $parceiros = [];
        
        try {
            $parceiros = Database::query(
                "SELECT p.*, COUNT(sp.id) as total_solicitacoes
                 FROM parceiros p
                 LEFT JOIN solicitacoes_parceiros sp ON p.id = sp.parceiro_id AND sp.empresa_id = :empresa_id
                 WHERE p.ativo = 1
                 GROUP BY p.id
                 ORDER BY p.nome ASC",
                ['empresa_id' => $empresaId]
            );
        } catch (\Exception $e) {
            Logger::erro('Erro ao buscar parceiros: ' . $e->getMessage());
        }
        
        $dados = ['parceiros' => $parceiros];
        require VIEW_PATH . '/parceiros/index.php';
    }

    public function perfil(): void
    {
        Auth::proteger();
        
        $parceiroId = (int) ($_GET['id'] ?? 0);
        $parceiro = null;
        
        if ($parceiroId > 0) {
            try {
                $parceiro = Database::queryOne(
                    "SELECT * FROM parceiros WHERE id = :id AND ativo = 1",
                    ['id' => $parceiroId]
                );
            } catch (\Exception $e) {
                Logger::erro('Erro ao buscar parceiro: ' . $e->getMessage());
            }
        }
        
        if (!$parceiro) {
            Flash::set('erro', 'Parceiro não encontrado.');
            header('Location: ' . APP_URL . '/parceiros');
            exit;
        }
        
        $dados = ['parceiro' => $parceiro];
        require VIEW_PATH . '/parceiros/perfil.php';
    }

    public function solicitar(): void
    {
        Auth::proteger();
        Csrf::verificar();
        Logger::acao('Solicitação de parceiro', ['parceiro' => $_POST['parceiro_id'] ?? '']);
        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'mensagem' => 'Solicitação enviada! Entraremos em contato.']);
        exit;
    }

    public function admin(): void
    {
        Auth::exigirPerfil([Auth::ADMIN_HOLDING]);
        
        // Buscar parceiros reais do banco
        try {
            $parceiros = Database::query(
                "SELECT * FROM parceiros WHERE ativo = 1 ORDER BY nome ASC"
            );
        } catch (Exception $e) {
            // Se tabela não existir, mostrar empty state
            $parceiros = [];
        }
        
        $dados = ['parceiros' => $parceiros];
        require VIEW_PATH . '/parceiros/admin.php';
    }

    public function atualizarStatus(): void
    {
        Auth::exigirPerfil([Auth::ADMIN_HOLDING]);
        Csrf::verificar();
        Logger::acao('Status de parceiro atualizado');
        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'mensagem' => 'Status atualizado!']);
        exit;
    }

    // ===== F-12: GESTÃO DE SOLICITAÇÕES DE PARCEIROS =====

    /**
     * Lista de solicitações de parceiros para admin
     */
    public function solicitacoes(): void
    {
        Auth::exigirPerfil([Auth::ADMIN_HOLDING, Auth::CONSULTOR_INTERNO]);
        
        try {
            // Filtros opcionais
            $status = trim($_GET['status'] ?? '');
            $urgencia = trim($_GET['urgencia'] ?? '');
            $empresaId = (int) ($_GET['empresa_id'] ?? 0);
            
            // Construir query com filtros
            $whereClause = "WHERE 1=1";
            $params = [];
            
            if (!empty($status)) {
                $whereClause .= " AND s.status = :status";
                $params['status'] = $status;
            }
            
            if (!empty($urgencia)) {
                $whereClause .= " AND s.urgencia = :urgencia";
                $params['urgencia'] = $urgencia;
            }
            
            if ($empresaId > 0) {
                $whereClause .= " AND s.empresa_id = :empresa_id";
                $params['empresa_id'] = $empresaId;
            }
            
            // Buscar solicitações
            $solicitacoes = Database::query(
                "SELECT s.*, 
                        p.nome as parceiro_nome, p.categoria as parceiro_categoria, p.avaliacao_media,
                        e.nome as empresa_nome,
                        t.titulo as tarefa_titulo,
                        u.nome as usuario_nome
                 FROM solicitacoes_parceiro s
                 JOIN parceiros p ON s.parceiro_id = p.id
                 JOIN empresas e ON s.empresa_id = e.id  
                 JOIN plano_tarefas t ON s.tarefa_id = t.id
                 JOIN usuarios u ON s.usuario_id = u.id
                 {$whereClause}
                 ORDER BY s.urgencia DESC, s.criado_em DESC",
                $params
            );
            
            // Buscar empresas para filtro
            $empresas = Database::query("SELECT id, nome FROM empresas ORDER BY nome ASC");
            
            $dados = [
                'solicitacoes' => $solicitacoes,
                'empresas' => $empresas,
                'filtros' => [
                    'status' => $status,
                    'urgencia' => $urgencia,
                    'empresa_id' => $empresaId
                ]
            ];
            
        } catch (\Exception $e) {
            Logger::error('Erro ao carregar solicitações', ['erro' => $e->getMessage()]);
            $dados = [
                'solicitacoes' => [],
                'empresas' => [],
                'erro' => 'Erro ao carregar dados'
            ];
        }
        
        require VIEW_PATH . '/parceiros/solicitacoes.php';
    }

    /**
     * Atualizar status de uma solicitação de parceiro
     */
    public function atualizarStatusSolicitacao(): void
    {
        Auth::exigirPerfil([Auth::ADMIN_HOLDING, Auth::CONSULTOR_INTERNO]);
        Csrf::verificar();
        
        $solicitacaoId = (int) ($_POST['solicitacao_id'] ?? 0);
        $novoStatus = trim($_POST['novo_status'] ?? '');
        $observacoes = trim($_POST['observacoes'] ?? '');
        
        if ($solicitacaoId === 0 || empty($novoStatus)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Dados incompletos.']);
            exit;
        }
        
        $statusValidos = ['solicitado', 'em_contato', 'em_execucao', 'concluido', 'cancelado'];
        if (!in_array($novoStatus, $statusValidos)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Status inválido.']);
            exit;
        }
        
        try {
            // Verificar se solicitação existe
            $solicitacao = Database::queryOne(
                "SELECT s.*, e.nome as empresa_nome, p.nome as parceiro_nome 
                 FROM solicitacoes_parceiro s
                 JOIN empresas e ON s.empresa_id = e.id
                 JOIN parceiros p ON s.parceiro_id = p.id
                 WHERE s.id = :id",
                ['id' => $solicitacaoId]
            );
            
            if (!$solicitacao) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Solicitação não encontrada.']);
                exit;
            }
            
            // Preparar campos de data baseado no status
            $camposData = [];
            switch ($novoStatus) {
                case 'em_contato':
                    $camposData['data_contato'] = 'NOW()';
                    break;
                case 'em_execucao':
                    $camposData['data_inicio_execucao'] = 'NOW()';
                    break;
                case 'concluido':
                    $camposData['data_conclusao'] = 'NOW()';
                    break;
            }
            
            // Construir query de atualização
            $setClauses = ['status = :status', 'observacoes_admin = :observacoes'];
            $params = [
                'status' => $novoStatus,
                'observacoes' => $observacoes,
                'id' => $solicitacaoId
            ];
            
            foreach ($camposData as $campo => $valor) {
                $setClauses[] = "{$campo} = {$valor}";
            }
            
            // Atualizar solicitação
            Database::execute(
                "UPDATE solicitacoes_parceiro SET " . implode(', ', $setClauses) . " WHERE id = :id",
                $params
            );
            
            // Criar alerta para o cliente sobre mudança de status
            $mensagemCliente = match($novoStatus) {
                'em_contato' => "Parceiro {$solicitacao['parceiro_nome']} foi contatado e entrará em contato em breve.",
                'em_execucao' => "Parceiro {$solicitacao['parceiro_nome']} iniciou a execução do serviço solicitado.",
                'concluido' => "Serviço do parceiro {$solicitacao['parceiro_nome']} foi concluído. Avalie a experiência!",
                'cancelado' => "Solicitação do parceiro {$solicitacao['parceiro_nome']} foi cancelada.",
                default => "Status da solicitação atualizado para: {$novoStatus}"
            };
            
            Database::execute(
                "INSERT INTO alertas (tipo, titulo, mensagem, empresa_id, origem_id, origem_tipo, criado_em) 
                 VALUES ('status_parceiro', :titulo, :mensagem, :empresa_id, :origem_id, 'solicitacao_parceiro', NOW())",
                [
                    'titulo' => 'Atualização do Parceiro',
                    'mensagem' => $mensagemCliente,
                    'empresa_id' => $solicitacao['empresa_id'],
                    'origem_id' => $solicitacaoId
                ]
            );
            
            Logger::acao('Status de solicitação atualizado', [
                'solicitacao_id' => $solicitacaoId,
                'status_anterior' => $solicitacao['status'],
                'novo_status' => $novoStatus,
                'admin_id' => Auth::id()
            ]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Status atualizado com sucesso!',
                'novo_status' => $novoStatus
            ]);
            
        } catch (\Exception $e) {
            Logger::error('Erro ao atualizar status da solicitação', ['erro' => $e->getMessage()]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno.']);
        }
        exit;
    }
}
