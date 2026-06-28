<?php
/**
 * KpiController — Gestão de KPIs e Alertas
 * O Consultor — Sistema Operacional Empresarial
 *
 * F-07 Implementation: KPI monitoring, alerts, and AI-powered root cause analysis
 */

class KpiController
{
    /**
     * Painel principal de KPIs — F-07 Implementation
     */
    public function index(): void
    {
        Auth::proteger();
        
        $empresaId = Auth::empresa();
        if (!$empresaId) {
            Flash::set('erro', 'Empresa não identificada.');
            header('Location: ' . APP_URL . '/dashboard');
            exit;
        }

        // Buscar todos os KPIs de SOPs aprovados da empresa
        $kpis = Database::query(
            "SELECT k.*, s.titulo as sop_titulo, s.sop_codigo, s.departamento,
                    (SELECT COUNT(*) FROM kpi_registros kr WHERE kr.kpi_id = k.id) as total_medicoes,
                    (SELECT kr2.data_medicao FROM kpi_registros kr2 WHERE kr2.kpi_id = k.id ORDER BY kr2.data_medicao DESC LIMIT 1) as ultima_medicao
             FROM sop_kpis k 
             JOIN sops s ON k.sop_id = s.id 
             WHERE k.empresa_id = :empresa_id AND k.ativo = 1 AND s.status = 'ativo'
             ORDER BY k.zona_atual DESC, k.nome",
            ['empresa_id' => $empresaId]
        );

        // Estatísticas gerais
        $stats = $this->calcularEstatisticasKpis($kpis);

        // Alertas ativos relacionados a KPIs
        $alertasKpis = Database::query(
            "SELECT a.*, k.nome as kpi_nome, s.sop_codigo 
             FROM alertas a 
             JOIN sop_kpis k ON a.kpi_id = k.id 
             JOIN sops s ON a.sop_id = s.id 
             WHERE a.empresa_id = :empresa_id AND a.status = 'ativo' AND a.tipo IN ('kpi_critico', 'kpi_atencao')
             ORDER BY a.prioridade DESC, a.data_criacao DESC 
             LIMIT 10",
            ['empresa_id' => $empresaId]
        );

        $dados = [
            'kpis' => $kpis,
            'stats' => $stats,
            'alertas_ativos' => $alertasKpis,
        ];

        require VIEW_PATH . '/kpis/index.php';
    }

    /**
     * Ver detalhes de um KPI específico
     */
    public function ver(): void
    {
        Auth::proteger();

        $kpiId = (int) ($_GET['id'] ?? 0);
        if (!$kpiId) {
            Flash::set('erro', 'KPI não encontrado.');
            header('Location: ' . APP_URL . '/manual-operacional/kpis');
            exit;
        }

        // Buscar KPI
        $kpi = Database::queryOne(
            "SELECT k.*, s.titulo as sop_titulo, s.sop_codigo, s.id as sop_id, s.departamento
             FROM sop_kpis k 
             JOIN sops s ON k.sop_id = s.id 
             WHERE k.id = :id AND k.empresa_id = :empresa_id",
            ['id' => $kpiId, 'empresa_id' => Auth::empresa()]
        );

        if (!$kpi) {
            Flash::set('erro', 'KPI não encontrado ou sem permissão.');
            header('Location: ' . APP_URL . '/manual-operacional/kpis');
            exit;
        }

        // Histórico de valores (últimos 30 registros)
        $historico = Database::query(
            "SELECT * FROM kpi_registros 
             WHERE kpi_id = :kpi_id 
             ORDER BY data_medicao DESC, criado_em DESC 
             LIMIT 30",
            ['kpi_id' => $kpiId]
        );

        // Análise da IA mais recente (se houver)
        $analiseIA = Database::queryOne(
            "SELECT * FROM kpi_analises 
             WHERE kpi_id = :kpi_id 
             ORDER BY criado_em DESC 
             LIMIT 1",
            ['kpi_id' => $kpiId]
        );

        // Alertas relacionados a este KPI
        $alertas = Database::query(
            "SELECT * FROM alertas 
             WHERE kpi_id = :kpi_id 
             ORDER BY data_criacao DESC 
             LIMIT 10",
            ['kpi_id' => $kpiId]
        );

        $dados = [
            'kpi' => $kpi,
            'historico' => array_reverse($historico), // Cronológico para gráfico
            'analise_ia' => $analiseIA,
            'alertas' => $alertas,
        ];

        require VIEW_PATH . '/kpis/ver.php';
    }

    /**
     * Registrar novo valor de KPI — F-07 Core
     */
    public function registrar(): void
    {
        Auth::proteger();
        Csrf::verificar();

        $kpiId = (int) ($_POST['kpi_id'] ?? 0);
        $valor = trim($_POST['valor'] ?? '');
        $dataMedicao = $_POST['data'] ?? date('Y-m-d');
        $observacoes = trim($_POST['observacoes'] ?? '');

        if (!$kpiId || empty($valor)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'KPI ID e valor são obrigatórios.']);
            exit;
        }

        // Validar data
        if (!$this->validarData($dataMedicao)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Data inválida.']);
            exit;
        }

        // Buscar KPI
        $kpi = Database::queryOne(
            "SELECT k.*, s.empresa_id, s.id as sop_id, s.sop_codigo 
             FROM sop_kpis k 
             JOIN sops s ON k.sop_id = s.id 
             WHERE k.id = :id AND s.empresa_id = :empresa_id",
            ['id' => $kpiId, 'empresa_id' => Auth::empresa()]
        );

        if (!$kpi) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'KPI não encontrado.']);
            exit;
        }

        Database::beginTransaction();

        try {
            // 1. Calcular zona baseada no valor
            $zonaCalculada = $this->calcularZonaKPI($valor, $kpi);

            // 2. Registrar valor no histórico
            $registroId = $this->salvarRegistroKPI($kpiId, $valor, $dataMedicao, $zonaCalculada, $observacoes);

            if (!$registroId) {
                throw new Exception('Erro ao salvar registro de KPI');
            }

            // 3. Atualizar KPI principal
            $this->atualizarKPIPrincipal($kpiId, $valor, $zonaCalculada);

            // 4. Se zona vermelha, gerar análise da IA
            $analiseIA = null;
            if ($zonaCalculada === 'vermelha') {
                $analiseIA = $this->gerarAnaliseIA($kpi, $valor, $registroId);
            }

            Database::commit();

            Logger::acao('KPI valor registrado', [
                'kpi_id' => $kpiId,
                'kpi_nome' => $kpi['nome'],
                'valor' => $valor,
                'zona' => $zonaCalculada,
                'analise_ia' => $analiseIA ? 'gerada' : 'nao_necessaria'
            ]);

            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Valor registrado com sucesso!',
                'zona' => $zonaCalculada,
                'analise_ia' => $analiseIA,
                'alerta_criado' => in_array($zonaCalculada, ['amarela', 'vermelha'])
            ]);

        } catch (Exception $e) {
            Database::rollback();
            Logger::error('Erro ao registrar KPI', ['erro' => $e->getMessage(), 'kpi_id' => $kpiId]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno ao registrar valor.']);
        }
        
        exit;
    }

    /**
     * Processar alerta de KPI fora da meta e acionar contenção
     */
    public function processarAlerta(): void
    {
        Auth::proteger();
        Csrf::verificar();
        
        $kpiId = (int) ($_POST['kpi_id'] ?? 0);
        $empresaId = Auth::empresa();
        
        if (!$kpiId || !$empresaId) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Dados inválidos']);
            exit;
        }

        try {
            // Buscar KPI e verificar se está fora da meta
            $kpi = Database::queryOne(
                "SELECT k.*, s.titulo as sop_titulo, s.id as sop_id
                 FROM sop_kpis k 
                 LEFT JOIN sops s ON k.sop_id = s.id
                 WHERE k.id = :kpi_id AND k.empresa_id = :empresa_id",
                ['kpi_id' => $kpiId, 'empresa_id' => $empresaId]
            );

            if (!$kpi) {
                throw new Exception('KPI não encontrado');
            }

            // Verificar se KPI está fora da meta
            $foradameta = $this->verificarKpiForiDaMeta($kpi);
            
            if ($foradameta['fora_da_meta']) {
                // 1. Criar alerta automático
                $alertaId = $this->criarAlertaAutomatico($kpi, $foradameta, $empresaId);
                
                // 2. Acionar contenção automática baseada na criticidade
                $nivelContencao = $this->determinarNivelContencao($foradameta['desvio_percentual']);
                $contencaoId = $this->acionarContencaoAutomatica($kpi['sop_id'], $nivelContencao, $alertaId, $empresaId);
                
                // 3. Agendar revisão do SOP se necessário
                if ($nivelContencao >= 2) { // N2 ou N3
                    $this->agendarRevisaoSop($kpi['sop_id'], $empresaId, "KPI {$kpi['nome']} fora da meta por {$foradameta['desvio_percentual']}%");
                }

                Logger::acao('Loop melhoria contínua acionado', [
                    'kpi_id' => $kpiId,
                    'alerta_id' => $alertaId,
                    'contencao_id' => $contencaoId,
                    'nivel_contencao' => $nivelContencao,
                    'desvio' => $foradameta['desvio_percentual']
                ]);

                header('Content-Type: application/json');
                echo json_encode([
                    'sucesso' => true,
                    'alerta_criado' => $alertaId,
                    'contencao_acionada' => $contencaoId,
                    'nivel_contencao' => "N{$nivelContencao}",
                    'proxima_acao' => $nivelContencao >= 2 ? 'Revisão do SOP agendada' : 'Monitoramento ativo'
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => true, 'status' => 'KPI dentro da meta']);
            }

        } catch (Exception $e) {
            Logger::error('Erro no loop de melhoria contínua', [
                'kpi_id' => $kpiId,
                'erro' => $e->getMessage()
            ]);
            
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
        }
        
        exit;
    }

    /**
     * Determina nível de contenção baseado no desvio
     */
    private function determinarNivelContencao(float $desvioPercentual): int
    {
        if ($desvioPercentual >= 50) return 3; // N3 - Crítico
        if ($desvioPercentual >= 25) return 2; // N2 - Alto
        return 1; // N1 - Baixo
    }

    /**
     * Cria alerta automático para KPI fora da meta
     */
    private function criarAlertaAutomatico(array $kpi, array $foradameta, int $empresaId): int
    {
        $prioridade = match(true) {
            $foradameta['desvio_percentual'] >= 50 => 'critica',
            $foradameta['desvio_percentual'] >= 25 => 'alta',
            default => 'media'
        };

        return Database::execute(
            "INSERT INTO alertas (empresa_id, tipo, titulo, descricao, prioridade, kpi_id, dados_contexto, status, criado_em)
             VALUES (:empresa_id, 'kpi_fora_meta', :titulo, :descricao, :prioridade, :kpi_id, :contexto, 'ativo', NOW())",
            [
                'empresa_id' => $empresaId,
                'titulo' => "KPI {$kpi['nome']} fora da meta",
                'descricao' => "Meta: {$kpi['meta']} | Atual: {$foradameta['valor_atual']} | Desvio: {$foradameta['desvio_percentual']}%",
                'prioridade' => $prioridade,
                'kpi_id' => $kpi['id'],
                'contexto' => json_encode($foradameta)
            ]
        ) ? Database::lastInsertId() : 0;
    }

    /**
     * Aciona contenção automática
     */
    private function acionarContencaoAutomatica(int $sopId, int $nivel, int $alertaId, int $empresaId): int
    {
        $acoes = match($nivel) {
            3 => ['Parar processo imediatamente', 'Escalar para direção', 'Análise de causa raiz urgente'],
            2 => ['Revisar procedimento', 'Retreinar equipe', 'Monitoramento intensivo'],
            1 => ['Atenção redobrada', 'Verificar execução', 'Acompanhar próximas medições']
        };

        return Database::execute(
            "INSERT INTO contencoes (empresa_id, sop_id, alerta_id, nivel, acoes, status, acionado_automaticamente, criado_em)
             VALUES (:empresa_id, :sop_id, :alerta_id, :nivel, :acoes, 'ativo', 1, NOW())",
            [
                'empresa_id' => $empresaId,
                'sop_id' => $sopId,
                'alerta_id' => $alertaId,
                'nivel' => $nivel,
                'acoes' => json_encode($acoes)
            ]
        ) ? Database::lastInsertId() : 0;
    }

    /**
     * Agenda revisão automática do SOP
     */
    private function agendarRevisaoSop(int $sopId, int $empresaId, string $motivo): void
    {
        Database::execute(
            "UPDATE sops SET necessita_revisao = 1, motivo_revisao = :motivo, data_agendamento_revisao = NOW() 
             WHERE id = :sop_id AND empresa_id = :empresa_id",
            [
                'sop_id' => $sopId,
                'empresa_id' => $empresaId,
                'motivo' => $motivo
            ]
        );
    }
    /**
     * Verifica se KPI está fora da meta e calcula desvio
     */
    private function verificarKpiForiDaMeta(array $kpi): array
    {
        $valorAtual = $this->extrairNumero($kpi['valor_atual'] ?? '0');
        $metaVerde = $this->extrairNumero($kpi['meta_verde'] ?? '100');
        
        // Determinar se "maior é melhor" ou "menor é melhor"
        $maiorEMelhor = $this->determinarDirecaoKPI($kpi);
        
        $foradameta = false;
        $desvioPercentual = 0;
        
        if ($maiorEMelhor) {
            // Para KPIs onde maior é melhor (ex: SLA, eficiência)
            if ($valorAtual < $metaVerde) {
                $foradameta = true;
                $desvioPercentual = round((($metaVerde - $valorAtual) / $metaVerde) * 100, 2);
            }
        } else {
            // Para KPIs onde menor é melhor (ex: tempo de resposta, erros)
            if ($valorAtual > $metaVerde) {
                $foradameta = true;
                $desvioPercentual = round((($valorAtual - $metaVerde) / $metaVerde) * 100, 2);
            }
        }
        
        return [
            'fora_da_meta' => $foradameta,
            'valor_atual' => $kpi['valor_atual'],
            'meta_verde' => $kpi['meta_verde'],
            'desvio_percentual' => $desvioPercentual,
            'direcao_kpi' => $maiorEMelhor ? 'maior_melhor' : 'menor_melhor'
        ];
    }
    {
        Auth::proteger();
        Csrf::verificar();

        $alertaId = (int) ($_POST['alerta_id'] ?? 0);
        
        if (!$alertaId) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'ID do alerta é obrigatório.']);
            exit;
        }

        // Verificar permissão
        $alerta = Database::queryOne(
            "SELECT * FROM alertas WHERE id = :id AND empresa_id = :empresa_id",
            ['id' => $alertaId, 'empresa_id' => Auth::empresa()]
        );

        if (!$alerta) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Alerta não encontrado.']);
            exit;
        }

        $sucesso = Database::execute(
            "UPDATE alertas SET lido = 1, lido_em = NOW() WHERE id = :id",
            ['id' => $alertaId]
        );

        if ($sucesso) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao marcar como lido.']);
        }
        exit;
    }

    /**
     * Buscar alertas não lidos (para TopBar)
     */
    public function alertasNaoLidos(): void
    {
        Auth::proteger();

        $empresaId = Auth::empresa();
        if (!$empresaId) {
            header('Content-Type: application/json');
            echo json_encode(['alertas' => [], 'total' => 0]);
            exit;
        }

        $alertas = Database::query(
            "SELECT a.*, k.nome as kpi_nome, s.sop_codigo 
             FROM alertas a 
             LEFT JOIN sop_kpis k ON a.kpi_id = k.id 
             LEFT JOIN sops s ON a.sop_id = s.id 
             WHERE a.empresa_id = :empresa_id AND a.lido = 0 AND a.status = 'ativo'
             ORDER BY a.prioridade DESC, a.data_criacao DESC 
             LIMIT 20",
            ['empresa_id' => $empresaId]
        );

        header('Content-Type: application/json');
        echo json_encode([
            'alertas' => $alertas,
            'total' => count($alertas)
        ]);
        exit;
    }

    // ===== HELPER METHODS =====

    /**
     * Calcular zona do KPI baseada no valor — F-07 Core Logic
     */
    private function calcularZonaKPI(string $valor, array $kpi): string
    {
        // Extrair número do valor
        $valorNumerico = $this->extrairNumero($valor);
        
        // Extrair números das metas
        $metaVerde = $this->extrairNumero($kpi['meta_verde']);
        $metaAmarela = $this->extrairNumero($kpi['meta_amarela']);
        $metaVermelha = $this->extrairNumero($kpi['meta_vermelha']);

        // Determinar se "maior é melhor" ou "menor é melhor"
        $maiorEMelhor = $this->determinarDirecaoKPI($kpi);

        if ($maiorEMelhor) {
            // Maior é melhor (ex: % de SLA, % de disponibilidade)
            if ($valorNumerico >= $metaVerde) return 'verde';
            if ($valorNumerico >= $metaAmarela) return 'amarela';
            return 'vermelha';
        } else {
            // Menor é melhor (ex: tempo de resposta, downtime)
            if ($valorNumerico <= $metaVerde) return 'verde';
            if ($valorNumerico <= $metaAmarela) return 'amarela';
            return 'vermelha';
        }
    }

    /**
     * Determinar se para o KPI "maior é melhor"
     */
    private function determinarDirecaoKPI(array $kpi): bool
    {
        $nomeKpi = strtolower($kpi['nome']);
        
        // KPIs onde maior é melhor
        $maiorEMelhorPatterns = ['sla', 'disponibilidade', 'sucesso', 'satisfacao', 'eficiencia', 'aprovacao', '%'];
        
        foreach ($maiorEMelhorPatterns as $pattern) {
            if (strpos($nomeKpi, $pattern) !== false) {
                return true;
            }
        }
        
        // KPIs onde menor é melhor (padrão para tempo, falhas, etc.)
        return false;
    }

    /**
     * Extrair número de string (remove unidades)
     */
    private function extrairNumero(string $valor): float
    {
        // Remove tudo exceto números, pontos e vírgulas
        $numero = preg_replace('/[^\d.,]/', '', $valor);
        
        // Converte vírgula para ponto
        $numero = str_replace(',', '.', $numero);
        
        return (float) $numero;
    }

    /**
     * Salvar registro de KPI no histórico
     */
    private function salvarRegistroKPI(int $kpiId, string $valor, string $data, string $zona, string $observacoes): int|false
    {
        $sucesso = Database::execute(
            "INSERT INTO kpi_registros (kpi_id, valor, data_medicao, zona_calculada, usuario_id, observacoes) 
             VALUES (:kpi_id, :valor, :data_medicao, :zona_calculada, :usuario_id, :observacoes)",
            [
                'kpi_id' => $kpiId,
                'valor' => $valor,
                'data_medicao' => $data,
                'zona_calculada' => $zona,
                'usuario_id' => Auth::usuarioId(),
                'observacoes' => $observacoes ?: null,
            ]
        );

        return $sucesso ? (int) Database::lastInsertId() : false;
    }

    /**
     * Atualizar KPI principal com último valor
     */
    private function atualizarKPIPrincipal(int $kpiId, string $valor, string $zona): bool
    {
        return Database::execute(
            "UPDATE sop_kpis SET valor_atual = :valor_atual, zona_atual = :zona_atual, ultima_medicao = NOW() WHERE id = :id",
            [
                'id' => $kpiId,
                'valor_atual' => $valor,
                'zona_atual' => $zona,
            ]
        );
    }

    /**
     * Gerar análise da IA para KPI crítico — F-07 Core
     */
    private function gerarAnaliseIA(array $kpi, string $valor, int $registroId): ?array
    {
        try {
            // Buscar contexto da empresa
            $empresa = Empresa::buscarPorId($kpi['empresa_id']);
            $diagnostico = Diagnostico::buscarUltimoPorEmpresa($kpi['empresa_id']);

            // Montar contexto para IA
            $contextoEmpresa = [
                'nome' => $empresa['nome'],
                'setor' => $empresa['segmento'] ?? 'Tecnologia',
                'maturidade' => $empresa['score_maturidade'] ?? 2,
                'colaboradores' => $diagnostico ? $this->extrairDadosDiagnostico($diagnostico, 'colaboradores') : '10-25',
                'ferramentas' => $diagnostico ? $this->extrairDadosDiagnostico($diagnostico, 'ferramentas') : 'Básicas',
            ];

            // Gerar prompt para análise
            $prompt = ApiHelper::buildPromptKpiCritico($contextoEmpresa, [
                'nome' => $kpi['nome'],
                'meta' => $kpi['meta_verde'],
                'atual' => $valor,
                'sop' => $kpi['sop_codigo'],
            ]);

            // Chamar IA
            $resultado = ApiHelper::chamarAnalise($prompt, true);

            if ($resultado['sucesso'] && is_array($resultado['conteudo'])) {
                // Salvar análise no banco
                $analiseId = $this->salvarAnaliseIA($kpi['id'], $registroId, $resultado['conteudo'], $contextoEmpresa, $prompt);
                
                if ($analiseId) {
                    return $resultado['conteudo'];
                }
            }

        } catch (Exception $e) {
            Logger::error('Erro ao gerar análise de IA para KPI', [
                'kpi_id' => $kpi['id'],
                'erro' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Salvar análise da IA no banco
     */
    private function salvarAnaliseIA(int $kpiId, int $registroId, array $analise, array $contexto, string $prompt): int|false
    {
        $sucesso = Database::execute(
            "INSERT INTO kpi_analises (kpi_id, registro_id, causas_raiz, plano_acao_imediato, prazo_revisao, contencao_recomendada, justificativa_contencao, contexto_empresa, prompt_utilizado) 
             VALUES (:kpi_id, :registro_id, :causas_raiz, :plano_acao_imediato, :prazo_revisao, :contencao_recomendada, :justificativa_contencao, :contexto_empresa, :prompt_utilizado)",
            [
                'kpi_id' => $kpiId,
                'registro_id' => $registroId,
                'causas_raiz' => json_encode($analise['causas_raiz'] ?? []),
                'plano_acao_imediato' => json_encode($analise['plano_acao_imediato'] ?? []),
                'prazo_revisao' => $analise['prazo_revisao'] ?? '7 dias',
                'contencao_recomendada' => $analise['contencao_recomendada'] ?? 'N1',
                'justificativa_contencao' => $analise['justificativa_contencao'] ?? '',
                'contexto_empresa' => json_encode($contexto),
                'prompt_utilizado' => $prompt,
            ]
        );

        return $sucesso ? (int) Database::lastInsertId() : false;
    }

    /**
     * Validar formato de data
     */
    private function validarData(string $data): bool
    {
        $dt = DateTime::createFromFormat('Y-m-d', $data);
        return $dt && $dt->format('Y-m-d') === $data;
    }

    /**
     * Calcular estatísticas dos KPIs
     */
    private function calcularEstatisticasKpis(array $kpis): array
    {
        $total = count($kpis);
        $verde = 0;
        $amarela = 0;
        $vermelha = 0;
        $semMedicao = 0;

        foreach ($kpis as $kpi) {
            if (!$kpi['valor_atual']) {
                $semMedicao++;
            } else {
                switch ($kpi['zona_atual']) {
                    case 'verde': $verde++; break;
                    case 'amarela': $amarela++; break;
                    case 'vermelha': $vermelha++; break;
                }
            }
        }

        return [
            'total' => $total,
            'verde' => $verde,
            'amarela' => $amarela,
            'vermelha' => $vermelha,
            'sem_medicao' => $semMedicao,
        ];
    }

    /**
     * Extrair dados do diagnóstico
     */
    private function extrairDadosDiagnostico(array $diagnostico, string $campo): string
    {
        $respostas = json_decode($diagnostico['respostas'], true);
        
        switch ($campo) {
            case 'colaboradores':
                return $respostas['bloco2']['colaboradores'] ?? '10-25';
            case 'ferramentas':
                return $respostas['bloco2']['ferramentas'] ?? 'Básicas';
            default:
                return 'Não informado';
        }
    }
}