<?php
/**
 * FinanceiroController — BLOCO GESTÃO: Módulo Financeiro
 * O Consultor — Sistema Operacional Empresarial
 * 
 * Parte do sistema de três blocos: Operacional, Conteúdo, Gestão
 */

class FinanceiroController
{
    /**
     * Dashboard financeiro
     */
    public function index(): void
    {
        Auth::proteger();
        
        $empresaId = Auth::empresa();
        $mesAtual = date('Y-m');
        
        try {
            // KPIs financeiros do mês atual
            $kpisFinanceiros = Database::queryOne(
                "SELECT 
                    COALESCE(SUM(CASE WHEN tipo = 'receita' AND status = 'pago' THEN valor END), 0) as receitas_recebidas,
                    COALESCE(SUM(CASE WHEN tipo = 'despesa' AND status = 'pago' THEN valor END), 0) as despesas_pagas,
                    COALESCE(SUM(CASE WHEN tipo = 'receita' AND status = 'pendente' THEN valor END), 0) as receitas_pendentes,
                    COALESCE(SUM(CASE WHEN tipo = 'despesa' AND status = 'pendente' THEN valor END), 0) as despesas_pendentes,
                    COUNT(CASE WHEN data_vencimento < CURDATE() AND status = 'pendente' THEN 1 END) as contas_vencidas
                 FROM financeiro_transacoes 
                 WHERE empresa_id = :empresa_id AND DATE_FORMAT(data_competencia, '%Y-%m') = :mes",
                ['empresa_id' => $empresaId, 'mes' => $mesAtual]
            ) ?? [];

            // Calcular resultado do mês
            $resultadoMes = ($kpisFinanceiros['receitas_recebidas'] ?? 0) - ($kpisFinanceiros['despesas_pagas'] ?? 0);
            $kpisFinanceiros['resultado_mes'] = $resultadoMes;

            // Transações recentes
            $transacoesRecentes = Database::query(
                "SELECT * FROM financeiro_transacoes 
                 WHERE empresa_id = :empresa_id 
                 ORDER BY criado_em DESC LIMIT 10",
                ['empresa_id' => $empresaId]
            );

            // Contas a vencer nos próximos 7 dias
            $contasVencer = Database::query(
                "SELECT * FROM financeiro_transacoes 
                 WHERE empresa_id = :empresa_id 
                 AND status = 'pendente'
                 AND data_vencimento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                 ORDER BY data_vencimento",
                ['empresa_id' => $empresaId]
            );

            // Evolução mensal (últimos 6 meses)
            $evolucaoMensal = Database::query(
                "SELECT 
                    DATE_FORMAT(data_competencia, '%Y-%m') as mes,
                    COALESCE(SUM(CASE WHEN tipo = 'receita' AND status = 'pago' THEN valor END), 0) as receitas,
                    COALESCE(SUM(CASE WHEN tipo = 'despesa' AND status = 'pago' THEN valor END), 0) as despesas
                 FROM financeiro_transacoes 
                 WHERE empresa_id = :empresa_id 
                 AND data_competencia >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                 GROUP BY DATE_FORMAT(data_competencia, '%Y-%m')
                 ORDER BY mes",
                ['empresa_id' => $empresaId]
            );

            // Principais categorias de despesa
            $categoriasDespesa = Database::query(
                "SELECT 
                    categoria,
                    COALESCE(SUM(valor), 0) as total,
                    COUNT(*) as quantidade
                 FROM financeiro_transacoes 
                 WHERE empresa_id = :empresa_id 
                 AND tipo = 'despesa'
                 AND DATE_FORMAT(data_competencia, '%Y-%m') = :mes
                 GROUP BY categoria
                 ORDER BY total DESC LIMIT 5",
                ['empresa_id' => $empresaId, 'mes' => $mesAtual]
            );

            $dados = [
                'kpis' => $kpisFinanceiros,
                'transacoes_recentes' => $transacoesRecentes,
                'contas_vencer' => $contasVencer,
                'evolucao_mensal' => $evolucaoMensal,
                'categorias_despesa' => $categoriasDespesa
            ];

            require VIEW_PATH . '/financeiro/index.php';

        } catch (Exception $e) {
            Logger::error('Erro ao carregar dashboard financeiro', ['erro' => $e->getMessage()]);
            Flash::set('erro', 'Erro ao carregar dados financeiros.');
            header('Location: ' . APP_URL . '/dashboard');
            exit;
        }
    }

    /**
     * Adicionar nova transação
     */
    public function adicionarTransacao(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $tipo = htmlspecialchars($_POST['tipo'] ?? '');
        $descricao = htmlspecialchars(trim($_POST['descricao'] ?? ''));
        $valor = floatval($_POST['valor'] ?? 0);
        $categoria = htmlspecialchars($_POST['categoria'] ?? '');
        $dataVencimento = $_POST['data_vencimento'] ?? '';
        $dataCompetencia = $_POST['data_competencia'] ?? date('Y-m-d');
        $status = htmlspecialchars($_POST['status'] ?? 'pendente');
        $recorrente = (bool) ($_POST['recorrente'] ?? false);
        $frequencia = htmlspecialchars($_POST['frequencia'] ?? 'mensal');

        if (!in_array($tipo, ['receita', 'despesa']) || empty($descricao) || $valor <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Dados obrigatórios inválidos.']);
            exit;
        }

        if (!strtotime($dataVencimento)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Data de vencimento inválida.']);
            exit;
        }

        try {
            $transacaoId = Database::execute(
                "INSERT INTO financeiro_transacoes (
                    empresa_id, tipo, descricao, valor, categoria, data_vencimento, 
                    data_competencia, status, recorrente, frequencia, criado_em
                 ) VALUES (
                    :empresa_id, :tipo, :descricao, :valor, :categoria, :data_vencimento, 
                    :data_competencia, :status, :recorrente, :frequencia, NOW()
                 )",
                [
                    'empresa_id' => Auth::empresa(),
                    'tipo' => $tipo,
                    'descricao' => $descricao,
                    'valor' => $valor,
                    'categoria' => $categoria,
                    'data_vencimento' => $dataVencimento,
                    'data_competencia' => $dataCompetencia,
                    'status' => $status,
                    'recorrente' => $recorrente ? 1 : 0,
                    'frequencia' => $recorrente ? $frequencia : null
                ]
            ) ? Database::lastInsertId() : 0;

            if ($transacaoId) {
                // Se é recorrente, criar próximas ocorrências
                if ($recorrente) {
                    $this->criarRecorrencias($transacaoId, $tipo, $descricao, $valor, $categoria, $dataVencimento, $frequencia);
                }

                Logger::acao('Transação financeira adicionada', [
                    'transacao_id' => $transacaoId,
                    'tipo' => $tipo,
                    'valor' => $valor,
                    'recorrente' => $recorrente
                ]);

                header('Content-Type: application/json');
                echo json_encode([
                    'sucesso' => true,
                    'mensagem' => 'Transação adicionada com sucesso!',
                    'transacao_id' => $transacaoId
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Erro ao salvar transação.']);
            }

        } catch (Exception $e) {
            Logger::error('Erro ao adicionar transação', ['erro' => $e->getMessage()]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno.']);
        }
        exit;
    }

    /**
     * Marcar transação como paga/recebida
     */
    public function marcarPago(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $transacaoId = (int) ($_POST['transacao_id'] ?? 0);
        $dataPagamento = $_POST['data_pagamento'] ?? date('Y-m-d');
        $valorPago = floatval($_POST['valor_pago'] ?? 0);
        $observacoes = htmlspecialchars(trim($_POST['observacoes'] ?? ''));

        if (!$transacaoId) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'ID da transação obrigatório.']);
            exit;
        }

        try {
            // Buscar transação
            $transacao = Database::queryOne(
                "SELECT * FROM financeiro_transacoes 
                 WHERE id = :id AND empresa_id = :empresa_id AND status = 'pendente'",
                ['id' => $transacaoId, 'empresa_id' => Auth::empresa()]
            );

            if (!$transacao) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Transação não encontrada ou já paga.']);
                exit;
            }

            // Se valor pago não informado, usar valor original
            if ($valorPago <= 0) {
                $valorPago = $transacao['valor'];
            }

            // Marcar como pago
            $sucesso = Database::execute(
                "UPDATE financeiro_transacoes SET 
                 status = 'pago', 
                 data_pagamento = :data_pagamento,
                 valor_pago = :valor_pago,
                 observacoes_pagamento = :observacoes,
                 atualizado_em = NOW()
                 WHERE id = :id",
                [
                    'data_pagamento' => $dataPagamento,
                    'valor_pago' => $valorPago,
                    'observacoes' => $observacoes,
                    'id' => $transacaoId
                ]
            );

            if ($sucesso) {
                // Verificar se há diferença no valor (desconto/juros)
                $diferenca = $valorPago - $transacao['valor'];
                $tipoDiferenca = '';
                
                if ($diferenca > 0) {
                    $tipoDiferenca = 'juros';
                } elseif ($diferenca < 0) {
                    $tipoDiferenca = 'desconto';
                }

                Logger::acao('Transação marcada como paga', [
                    'transacao_id' => $transacaoId,
                    'tipo' => $transacao['tipo'],
                    'valor_original' => $transacao['valor'],
                    'valor_pago' => $valorPago,
                    'diferenca' => $diferenca,
                    'tipo_diferenca' => $tipoDiferenca
                ]);

                $mensagem = $transacao['tipo'] === 'receita' 
                    ? 'Receita marcada como recebida!' 
                    : 'Despesa marcada como paga!';

                if ($diferenca != 0) {
                    $mensagem .= ' (Valor diferente do original)';
                }

                header('Content-Type: application/json');
                echo json_encode([
                    'sucesso' => true,
                    'mensagem' => $mensagem,
                    'diferenca' => $diferenca
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Erro ao atualizar transação.']);
            }

        } catch (Exception $e) {
            Logger::error('Erro ao marcar como pago', ['erro' => $e->getMessage()]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno.']);
        }
        exit;
    }

    /**
     * Relatório financeiro por período
     */
    public function relatorio(): void
    {
        Auth::proteger();

        $dataInicio = $_GET['inicio'] ?? date('Y-m-01');
        $dataFim = $_GET['fim'] ?? date('Y-m-t');
        $empresaId = Auth::empresa();

        try {
            // Validar datas
            if (!strtotime($dataInicio) || !strtotime($dataFim)) {
                throw new Exception('Datas inválidas');
            }

            // Resumo do período
            $resumoPeriodo = Database::queryOne(
                "SELECT 
                    COALESCE(SUM(CASE WHEN tipo = 'receita' AND status = 'pago' THEN valor_pago END), 0) as receitas_recebidas,
                    COALESCE(SUM(CASE WHEN tipo = 'despesa' AND status = 'pago' THEN valor_pago END), 0) as despesas_pagas,
                    COALESCE(SUM(CASE WHEN tipo = 'receita' AND status = 'pendente' THEN valor END), 0) as receitas_pendentes,
                    COALESCE(SUM(CASE WHEN tipo = 'despesa' AND status = 'pendente' THEN valor END), 0) as despesas_pendentes,
                    COUNT(CASE WHEN tipo = 'receita' THEN 1 END) as total_receitas,
                    COUNT(CASE WHEN tipo = 'despesa' THEN 1 END) as total_despesas
                 FROM financeiro_transacoes 
                 WHERE empresa_id = :empresa_id 
                 AND data_competencia BETWEEN :inicio AND :fim",
                [
                    'empresa_id' => $empresaId,
                    'inicio' => $dataInicio,
                    'fim' => $dataFim
                ]
            ) ?? [];

            // Transações detalhadas
            $transacoes = Database::query(
                "SELECT * FROM financeiro_transacoes 
                 WHERE empresa_id = :empresa_id 
                 AND data_competencia BETWEEN :inicio AND :fim
                 ORDER BY data_competencia DESC, tipo, valor DESC",
                [
                    'empresa_id' => $empresaId,
                    'inicio' => $dataInicio,
                    'fim' => $dataFim
                ]
            );

            // Análise por categoria
            $analiseCategoria = Database::query(
                "SELECT 
                    categoria,
                    tipo,
                    COALESCE(SUM(CASE WHEN status = 'pago' THEN valor_pago ELSE valor END), 0) as total,
                    COUNT(*) as quantidade,
                    AVG(valor) as valor_medio
                 FROM financeiro_transacoes 
                 WHERE empresa_id = :empresa_id 
                 AND data_competencia BETWEEN :inicio AND :fim
                 GROUP BY categoria, tipo
                 ORDER BY total DESC",
                [
                    'empresa_id' => $empresaId,
                    'inicio' => $dataInicio,
                    'fim' => $dataFim
                ]
            );

            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'periodo' => ['inicio' => $dataInicio, 'fim' => $dataFim],
                'resumo' => $resumoPeriodo,
                'transacoes' => $transacoes,
                'analise_categoria' => $analiseCategoria
            ]);

        } catch (Exception $e) {
            Logger::error('Erro ao gerar relatório financeiro', ['erro' => $e->getMessage()]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao gerar relatório.']);
        }
        exit;
    }

    /**
     * Projeção financeira (próximos 3 meses)
     */
    public function projecao(): void
    {
        Auth::proteger();
        $empresaId = Auth::empresa();

        try {
            // Buscar transações recorrentes ativas
            $transacoesRecorrentes = Database::query(
                "SELECT * FROM financeiro_transacoes 
                 WHERE empresa_id = :empresa_id 
                 AND recorrente = 1 
                 AND status = 'pendente'
                 ORDER BY data_vencimento",
                ['empresa_id' => $empresaId]
            );

            // Gerar projeção para próximos 3 meses
            $projecao = [];
            $dataAtual = new DateTime();
            
            for ($i = 0; $i < 3; $i++) {
                $mes = (clone $dataAtual)->modify("+{$i} month");
                $mesFormatado = $mes->format('Y-m');
                
                $projecao[$mesFormatado] = [
                    'mes' => $mes->format('M/Y'),
                    'receitas_previstas' => 0,
                    'despesas_previstas' => 0,
                    'resultado_projetado' => 0,
                    'detalhes' => []
                ];

                // Calcular valores baseados nas recorrências
                foreach ($transacoesRecorrentes as $transacao) {
                    if ($this->transacaoOcorreNoMes($transacao, $mes)) {
                        if ($transacao['tipo'] === 'receita') {
                            $projecao[$mesFormatado]['receitas_previstas'] += $transacao['valor'];
                        } else {
                            $projecao[$mesFormatado]['despesas_previstas'] += $transacao['valor'];
                        }
                        
                        $projecao[$mesFormatado]['detalhes'][] = [
                            'descricao' => $transacao['descricao'],
                            'tipo' => $transacao['tipo'],
                            'valor' => $transacao['valor'],
                            'categoria' => $transacao['categoria']
                        ];
                    }
                }

                $projecao[$mesFormatado]['resultado_projetado'] = 
                    $projecao[$mesFormatado]['receitas_previstas'] - $projecao[$mesFormatado]['despesas_previstas'];
            }

            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'projecao' => array_values($projecao)
            ]);

        } catch (Exception $e) {
            Logger::error('Erro ao gerar projeção financeira', ['erro' => $e->getMessage()]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao gerar projeção.']);
        }
        exit;
    }

    // ===== HELPER METHODS =====

    /**
     * Criar recorrências futuras de uma transação
     */
    private function criarRecorrencias(int $transacaoOriginalId, string $tipo, string $descricao, float $valor, string $categoria, string $dataVencimento, string $frequencia): void
    {
        $empresaId = Auth::empresa();
        $proximasOcorrencias = 12; // Criar próximas 12 ocorrências

        $dataBase = new DateTime($dataVencimento);
        
        for ($i = 1; $i <= $proximasOcorrencias; $i++) {
            $proximaData = clone $dataBase;
            
            switch ($frequencia) {
                case 'mensal':
                    $proximaData->modify("+{$i} month");
                    break;
                case 'trimestral':
                    $proximaData->modify("+" . ($i * 3) . " months");
                    break;
                case 'semestral':
                    $proximaData->modify("+" . ($i * 6) . " months");
                    break;
                case 'anual':
                    $proximaData->modify("+{$i} year");
                    break;
                default:
                    continue 2; // Skip invalid frequency
            }

            Database::execute(
                "INSERT INTO financeiro_transacoes (
                    empresa_id, tipo, descricao, valor, categoria, data_vencimento, 
                    data_competencia, status, recorrente, frequencia, 
                    transacao_pai_id, criado_em
                 ) VALUES (
                    :empresa_id, :tipo, :descricao, :valor, :categoria, :data_vencimento, 
                    :data_competencia, 'pendente', 1, :frequencia, 
                    :transacao_pai_id, NOW()
                 )",
                [
                    'empresa_id' => $empresaId,
                    'tipo' => $tipo,
                    'descricao' => $descricao,
                    'valor' => $valor,
                    'categoria' => $categoria,
                    'data_vencimento' => $proximaData->format('Y-m-d'),
                    'data_competencia' => $proximaData->format('Y-m-d'),
                    'frequencia' => $frequencia,
                    'transacao_pai_id' => $transacaoOriginalId
                ]
            );
        }
    }

    /**
     * Verificar se uma transação recorrente ocorre em um mês específico
     */
    private function transacaoOcorreNoMes(array $transacao, DateTime $mes): bool
    {
        $dataVencimento = new DateTime($transacao['data_vencimento']);
        $frequencia = $transacao['frequencia'];

        switch ($frequencia) {
            case 'mensal':
                return true; // Ocorre todo mês

            case 'trimestral':
                $diferencaMeses = $dataVencimento->diff($mes)->m + ($dataVencimento->diff($mes)->y * 12);
                return $diferencaMeses % 3 === 0;

            case 'semestral':
                $diferencaMeses = $dataVencimento->diff($mes)->m + ($dataVencimento->diff($mes)->y * 12);
                return $diferencaMeses % 6 === 0;

            case 'anual':
                return $dataVencimento->format('m') === $mes->format('m');

            default:
                return false;
        }
    }
}