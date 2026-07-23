<?php
/**
 * AdminController — Painel Administrativo (ADMIN_HOLDING exclusivo)
 */

class AdminController
{
    /** Verifica (com cache) se uma tabela existe no banco atual. */
    private function tabelaExiste(string $tabela): bool
    {
        static $cache = [];
        if (isset($cache[$tabela])) return $cache[$tabela];
        try {
            $r = Database::queryOne(
                "SELECT COUNT(*) AS c FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t",
                ['t' => $tabela]
            );
            return $cache[$tabela] = ((int) ($r['c'] ?? 0) > 0);
        } catch (\Throwable $e) {
            return $cache[$tabela] = false;
        }
    }

    private function protegerAdmin(): void
    {
        // Verificar se usuário está autenticado
        if (!Auth::check()) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                // Requisição AJAX
                header('Content-Type: application/json');
                echo json_encode(['erro' => 'Usuário não autenticado. Faça login novamente.']);
                exit;
            } else {
                header('Location: ' . APP_URL . '/auth/login');
                exit;
            }
        }

        // Verificar perfil
        $usuario = Auth::usuario();
        if (!$usuario || $usuario['perfil'] !== Auth::ADMIN_HOLDING) {
            // Log do erro
            error_log("ACESSO NEGADO ADMIN: Usuário " . ($usuario['email'] ?? 'desconhecido') . " com perfil " . ($usuario['perfil'] ?? 'desconhecido') . " tentou acessar funcionalidade admin");
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                // Requisição AJAX
                header('Content-Type: application/json');
                echo json_encode([
                    'erro' => 'Acesso negado. Apenas administradores (ADMIN_HOLDING) podem acessar esta funcionalidade.',
                    'perfil_atual' => $usuario['perfil'] ?? 'desconhecido',
                    'perfil_necessario' => Auth::ADMIN_HOLDING
                ]);
                exit;
            } else {
                // Retornar 403 Forbidden para requisições normais
                http_response_code(403);
                require VIEW_PATH . '/errors/403.php';
                exit;
            }
        }
    }

    public function index(): void
    {
        $this->protegerAdmin();
        
        // Buscar estatísticas reais do banco
        try {
            $totalUsuarios = Database::queryOne("SELECT COUNT(*) as count FROM usuarios WHERE ativo = 1")['count'] ?? 0;
            $totalEmpresas = Database::queryOne("SELECT COUNT(*) as count FROM empresas WHERE status = 'ativo'")['count'] ?? 0;
            $totalDiagnosticos = Database::queryOne("SELECT COUNT(*) as count FROM diagnosticos WHERE status = 'concluido'")['count'] ?? 0;
            $totalSops = Database::queryOne("SELECT COUNT(*) as count FROM sops WHERE status = 'ativo'")['count'] ?? 0;
            
            // MRR total das empresas ativas
            $mrrTotal = Database::queryOne("SELECT SUM(mrr) as total FROM empresas WHERE status = 'ativo' AND mrr > 0")['total'] ?? 0;
            $mrrFormatado = 'R$ ' . number_format($mrrTotal, 2, ',', '.');
            
            // Calcular churn (empresas canceladas nos últimos 30 dias vs total ativo)
            $empresasCanceladas30d = Database::queryOne("SELECT COUNT(*) as count FROM empresas WHERE status = 'cancelado' AND DATE(atualizado_em) >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['count'] ?? 0;
            $churnPercent = $totalEmpresas > 0 ? round(($empresasCanceladas30d / $totalEmpresas) * 100, 1) : 0;
            
            // Academy - contar usuários com campo academy_vinculado
            $academyVinculadas = Database::queryOne("SELECT COUNT(*) as count FROM usuarios WHERE academy_vinculado = 1 AND ativo = 1")['count'] ?? 0;
            $academyPendentes = Database::queryOne("SELECT COUNT(*) as count FROM usuarios WHERE (academy_vinculado = 0 OR academy_vinculado IS NULL) AND ativo = 1 AND perfil = 'CLIENTE'")['count'] ?? 0;
            
        } catch (Exception $e) {
            Logger::error('Erro ao buscar estatísticas do dashboard: ' . $e->getMessage());
            // Fallback para valores zero em caso de erro
            $totalUsuarios = 0;
            $totalEmpresas = 0;
            $totalDiagnosticos = 0;
            $totalSops = 0;
            $mrrFormatado = 'R$ 0,00';
            $churnPercent = 0;
            $academyVinculadas = 0;
            $academyPendentes = 0;
        }
        
        $dados = [
            'totalUsuarios' => $totalUsuarios,
            'totalEmpresas' => $totalEmpresas,
            'totalDiagnosticos' => $totalDiagnosticos,
            'totalSops' => $totalSops,
            'mrr' => $mrrFormatado,
            'churn' => $churnPercent . '%',
            'academyVinculadas' => $academyVinculadas,
            'academyPendentes' => $academyPendentes,
        ];
        
        require VIEW_PATH . '/admin/index.php';
    }

    public function usuarios(): void
    {
        $this->protegerAdmin();
        
        // Buscar usuários reais do banco (ativos e arquivados separados).
        $sql = "SELECT u.*, e.nome as empresa_nome 
                FROM usuarios u 
                LEFT JOIN empresas e ON u.empresa_id = e.id 
                ORDER BY u.criado_em DESC";
        $todos = Database::query($sql);

        $ativos = array_values(array_filter($todos, fn($u) => (int) ($u['ativo'] ?? 1) === 1));
        $arquivados = array_values(array_filter($todos, fn($u) => (int) ($u['ativo'] ?? 1) === 0));

        $dados = [
            'usuarios' => $ativos,       // compatibilidade com a aba principal
            'ativos' => $ativos,
            'arquivados' => $arquivados,
        ];
        require VIEW_PATH . '/admin/usuarios.php';
    }

    public function salvarUsuario(): void
    {
        $this->protegerAdmin();
        Csrf::verificar();
        
        $dados = [
            'nome' => htmlspecialchars(trim($_POST['nome'] ?? '')),
            'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
            'senha' => password_hash($_POST['senha'], PASSWORD_DEFAULT),
            'perfil' => in_array($_POST['perfil'], ['ADMIN_HOLDING', 'CONSULTOR_INTERNO', 'CLIENTE']) ? $_POST['perfil'] : 'CLIENTE',
            'empresa_id' => !empty($_POST['empresa_id']) ? (int)$_POST['empresa_id'] : null,
            'ativo' => 1
        ];
        
        if (empty($dados['nome']) || empty($dados['email'])) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Nome e email são obrigatórios']);
            exit;
        }
        
        // Verificar se email já existe
        if (User::buscarPorEmail($dados['email'])) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Email já cadastrado']);
            exit;
        }
        
        $usuarioId = User::criar($dados);
        
        if ($usuarioId) {
            Logger::acao('Novo usuário criado', ['email' => $dados['email']]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true, 'mensagem' => 'Usuário criado com sucesso!']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao criar usuário']);
        }
        exit;
    }

    /**
     * Gestão de clientes - F-13
     */
    public function clientes(): void
    {
        $this->protegerAdmin();
        
        // Filtros
        $setor = trim($_GET['setor'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $consultorId = (int) ($_GET['consultor_id'] ?? 0);
        
        // Construir query com filtros
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (!empty($setor)) {
            $whereClause .= " AND e.segmento = :setor";
            $params['setor'] = $setor;
        }
        
        if (!empty($status)) {
            $whereClause .= " AND e.status = :status";
            $params['status'] = $status;
        }
        
        if ($consultorId > 0) {
            $whereClause .= " AND e.consultor_id = :consultor_id";
            $params['consultor_id'] = $consultorId;
        }
        
        // Tabelas opcionais (planos_acao e sops podem não existir se as migrations
        // 007/008 ainda não rodaram). Monta os JOINs/contagens só para as que existem
        // para não quebrar a listagem de clientes com fatal error (1146).
        $temPlanos = $this->tabelaExiste('planos');
        $temSops = $this->tabelaExiste('sops');

        $selPlanos = $temPlanos ? 'COUNT(DISTINCT p.id)' : '0';
        $selSops = $temSops ? 'COUNT(DISTINCT s.id)' : '0';
        $joinPlanos = $temPlanos ? 'LEFT JOIN planos p ON p.empresa_id = e.id' : '';
        $joinSops = $temSops ? "LEFT JOIN sops s ON s.empresa_id = e.id AND s.status = 'ativo'" : '';

        // Buscar empresas com dados completos
        $sql = "SELECT e.*, 
                       c.nome as consultor_nome,
                       r.nome as responsavel_nome,
                       COUNT(DISTINCT u.id) as total_usuarios,
                       COUNT(DISTINCT d.id) as total_diagnosticos,
                       {$selPlanos} as total_planos,
                       {$selSops} as total_sops
                FROM empresas e 
                LEFT JOIN usuarios c ON e.consultor_id = c.id
                LEFT JOIN usuarios r ON e.responsavel_id = r.id
                LEFT JOIN usuarios u ON u.empresa_id = e.id
                LEFT JOIN diagnosticos d ON d.empresa_id = e.id
                {$joinPlanos}
                {$joinSops}
                {$whereClause}
                GROUP BY e.id
                ORDER BY e.criado_em DESC";
        
        try {
            $clientes = Database::query($sql, $params);
        } catch (\Throwable $e) {
            // Fallback mínimo: lista as empresas sem as contagens agregadas.
            $clientes = Database::query(
                "SELECT e.*, c.nome as consultor_nome, r.nome as responsavel_nome,
                        0 as total_usuarios, 0 as total_diagnosticos, 0 as total_planos, 0 as total_sops
                 FROM empresas e
                 LEFT JOIN usuarios c ON e.consultor_id = c.id
                 LEFT JOIN usuarios r ON e.responsavel_id = r.id
                 {$whereClause}
                 ORDER BY e.criado_em DESC",
                $params
            );
        }
        
        // Buscar consultores para filtro
        $consultores = Database::query(
            "SELECT id, nome FROM usuarios WHERE perfil IN ('CONSULTOR_INTERNO', 'ADMIN_HOLDING') ORDER BY nome ASC"
        );
        
        // Buscar setores únicos
        $setores = Database::query(
            "SELECT DISTINCT segmento FROM empresas WHERE segmento IS NOT NULL ORDER BY segmento ASC"
        );
        
        $dados = [
            'clientes' => $clientes,
            'consultores' => $consultores,
            'setores' => array_column($setores, 'segmento'),
            'filtros' => [
                'setor' => $setor,
                'status' => $status,
                'consultor_id' => $consultorId
            ]
        ];
        
        require VIEW_PATH . '/admin/clientes.php';
    }

    /**
     * Formulário de novo cliente - F-13
     */
    public function novoCliente(): void
    {
        $this->protegerAdmin();
        
        // Buscar consultores disponíveis
        $consultores = Database::query(
            "SELECT id, nome, email FROM usuarios 
             WHERE perfil IN ('CONSULTOR_INTERNO', 'ADMIN_HOLDING') 
             ORDER BY nome ASC"
        );
        
        $dados = ['consultores' => $consultores];
        require VIEW_PATH . '/admin/cliente-novo.php';
    }

    /**
     * Criar novo cliente - F-13
     */
    public function criarCliente(): void
    {
        $this->protegerAdmin();
        Csrf::verificar();
        
        // Dados da empresa
        $nomeEmpresa = trim($_POST['nome_empresa'] ?? '');
        $cnpj = preg_replace('/[^0-9]/', '', $_POST['cnpj'] ?? '');
        $segmento = trim($_POST['segmento'] ?? '');
        $telefoneEmpresa = trim($_POST['telefone_empresa'] ?? '');
        $endereco = trim($_POST['endereco'] ?? '');
        $cidade = trim($_POST['cidade'] ?? '');
        $estado = trim($_POST['estado'] ?? '');
        $cep = preg_replace('/[^0-9]/', '', $_POST['cep'] ?? '');
        $website = trim($_POST['website'] ?? '');
        $mrr = (float) ($_POST['mrr'] ?? 0);
        $consultorId = (int) ($_POST['consultor_id'] ?? 0);
        $observacoes = trim($_POST['observacoes'] ?? '');
        
        // Dados do usuário responsável
        $nomeResponsavel = trim($_POST['nome_responsavel'] ?? '');
        $emailResponsavel = trim($_POST['email_responsavel'] ?? '');
        $telefoneResponsavel = trim($_POST['telefone_responsavel'] ?? '');
        $cargoResponsavel = trim($_POST['cargo_responsavel'] ?? '');
        
        // Validações
        $erros = [];
        
        if (empty($nomeEmpresa)) $erros[] = 'Nome da empresa é obrigatório';
        if (empty($nomeResponsavel)) $erros[] = 'Nome do responsável é obrigatório';
        if (empty($emailResponsavel)) $erros[] = 'Email do responsável é obrigatório';
        if (!filter_var($emailResponsavel, FILTER_VALIDATE_EMAIL)) $erros[] = 'Email inválido';
        if ($consultorId === 0) $erros[] = 'Selecione um consultor responsável';
        
        // Verificar se email já existe
        $emailExistente = Database::queryOne(
            "SELECT id FROM usuarios WHERE email = :email",
            ['email' => $emailResponsavel]
        );
        if ($emailExistente) $erros[] = 'Este email já está cadastrado no sistema';
        
        // Verificar se CNPJ já existe (se informado)
        if (!empty($cnpj)) {
            $cnpjExistente = Database::queryOne(
                "SELECT id FROM empresas WHERE cnpj = :cnpj",
                ['cnpj' => $cnpj]
            );
            if ($cnpjExistente) $erros[] = 'Este CNPJ já está cadastrado';
        }
        
        if (!empty($erros)) {
            Flash::set('erro', implode('<br>', $erros));
            header('Location: ' . APP_URL . '/admin/clientes/novo');
            exit;
        }
        
        try {
            Database::beginTransaction();
            
            // Criar empresa
            $empresaId = Database::execute(
                "INSERT INTO empresas (nome, cnpj, segmento, telefone, endereco, cidade, estado, cep, website, 
                                      consultor_id, mrr, data_contratacao, observacoes_admin, status, criado_em) 
                 VALUES (:nome, :cnpj, :segmento, :telefone, :endereco, :cidade, :estado, :cep, :website, 
                         :consultor_id, :mrr, :data_contratacao, :observacoes, 'ativo', NOW())",
                [
                    'nome' => $nomeEmpresa,
                    'cnpj' => $cnpj ?: null,
                    'segmento' => $segmento,
                    'telefone' => $telefoneEmpresa,
                    'endereco' => $endereco,
                    'cidade' => $cidade,
                    'estado' => $estado,
                    'cep' => $cep ?: null,
                    'website' => $website ?: null,
                    'consultor_id' => $consultorId,
                    'mrr' => $mrr,
                    'data_contratacao' => date('Y-m-d'),
                    'observacoes' => $observacoes
                ]
            );
            $empresaId = Database::lastInsertId();
            
            // Gerar senha temporária
            $senhaTemporaria = $this->gerarSenhaTemporaria();
            $senhaHash = password_hash($senhaTemporaria, PASSWORD_DEFAULT);
            
            // Criar usuário responsável
            $usuarioId = Database::execute(
                "INSERT INTO usuarios (nome, email, telefone, cargo, senha, senha_temporaria, primeiro_acesso, 
                                      perfil, empresa_id, ativo, criado_em) 
                 VALUES (:nome, :email, :telefone, :cargo, :senha, 1, 1, 'CLIENTE', :empresa_id, 1, NOW())",
                [
                    'nome' => $nomeResponsavel,
                    'email' => $emailResponsavel,
                    'telefone' => $telefoneResponsavel,
                    'cargo' => $cargoResponsavel,
                    'senha' => $senhaHash,
                    'empresa_id' => $empresaId
                ]
            );
            $usuarioId = Database::lastInsertId();
            
            // Atualizar empresa com responsável
            Database::execute(
                "UPDATE empresas SET responsavel_id = :responsavel_id WHERE id = :id",
                ['responsavel_id' => $usuarioId, 'id' => $empresaId]
            );
            
            // Registrar no histórico
            Database::execute(
                "INSERT INTO historico_cliente (empresa_id, usuario_admin_id, tipo_acao, dados_novos, criado_em) 
                 VALUES (:empresa_id, :admin_id, 'criacao', :dados, NOW())",
                [
                    'empresa_id' => $empresaId,
                    'admin_id' => Auth::id(),
                    'dados' => json_encode([
                        'empresa' => $nomeEmpresa,
                        'responsavel' => $nomeResponsavel,
                        'email' => $emailResponsavel,
                        'consultor_id' => $consultorId
                    ])
                ]
            );
            
            // Enviar email de boas-vindas
            $emailEnviado = $this->enviarEmailBoasVindas(
                $emailResponsavel, 
                $nomeResponsavel, 
                $nomeEmpresa, 
                $senhaTemporaria
            );
            
            // Criar alerta para o consultor
            Database::execute(
                "INSERT INTO alertas (tipo, titulo, mensagem, empresa_id, criado_em) 
                 VALUES ('novo_cliente', 'Novo Cliente Atribuído', :mensagem, :empresa_id, NOW())",
                [
                    'mensagem' => "Você foi designado como consultor responsável pela empresa {$nomeEmpresa}",
                    'empresa_id' => $empresaId
                ]
            );
            
            Database::commit();
            
            AuditLog::registrar(
                'admin_criar_cliente',
                'admin',
                "Novo cliente criado: {$nomeEmpresa}",
                [
                    'empresa_id' => $empresaId,
                    'empresa_nome' => $nomeEmpresa,
                    'usuario_id' => $usuarioId,
                    'email_enviado' => $emailEnviado
                ]
            );
            
            if ($emailEnviado) {
                Flash::set('sucesso', "Cliente {$nomeEmpresa} criado com sucesso! Email de boas-vindas enviado para {$emailResponsavel}");
            } else {
                Flash::set('aviso', "Cliente {$nomeEmpresa} criado, mas não foi possível enviar o email de boas-vindas. Verifique as configurações SMTP.");
            }
            
            header('Location: ' . APP_URL . '/admin/clientes');
            
        } catch (Exception $e) {
            Database::rollback();
            Logger::error('Erro ao criar cliente: ' . $e->getMessage());
            Flash::set('erro', 'Erro interno ao criar cliente. Tente novamente.');
            header('Location: ' . APP_URL . '/admin/clientes/novo');
        }
        exit;
    }

    /**
     * Perfil detalhado do cliente - F-13
     */
    public function perfilCliente(): void
    {
        $this->protegerAdmin();
        
        $empresaId = (int) ($_GET['id'] ?? 0);
        
        if ($empresaId === 0) {
            Flash::set('erro', 'Cliente não encontrado.');
            header('Location: ' . APP_URL . '/admin/clientes');
            exit;
        }
        
        // Dados da empresa
        $empresa = Database::queryOne(
            "SELECT e.*, c.nome as consultor_nome, c.email as consultor_email, 
                    r.nome as responsavel_nome, r.email as responsavel_email, r.telefone as responsavel_telefone
             FROM empresas e 
             LEFT JOIN usuarios c ON e.consultor_id = c.id
             LEFT JOIN usuarios r ON e.responsavel_id = r.id
             WHERE e.id = :id",
            ['id' => $empresaId]
        );
        
        if (!$empresa) {
            Flash::set('erro', 'Cliente não encontrado.');
            header('Location: ' . APP_URL . '/admin/clientes');
            exit;
        }
        
        // Diagnósticos
        $diagnosticos = Database::query(
            "SELECT d.*, u.nome as usuario_nome 
             FROM diagnosticos d 
             LEFT JOIN usuarios u ON d.usuario_id = u.id
             WHERE d.empresa_id = :empresa_id 
             ORDER BY d.criado_em DESC",
            ['empresa_id' => $empresaId]
        );
        
        // Planos de ação
        $planos = Database::query(
            "SELECT p.*, u.nome as usuario_nome,
                    COUNT(DISTINCT t.id) as total_tarefas,
                    COUNT(DISTINCT CASE WHEN t.status = 'concluido' THEN t.id END) as tarefas_concluidas
             FROM planos p 
             LEFT JOIN usuarios u ON p.usuario_id = u.id
             LEFT JOIN plano_tarefas t ON t.plano_id = p.id
             WHERE p.empresa_id = :empresa_id 
             GROUP BY p.id
             ORDER BY p.criado_em DESC",
            ['empresa_id' => $empresaId]
        );
        
        // SOPs aprovados
        $sops = Database::query(
            "SELECT * FROM sops 
             WHERE empresa_id = :empresa_id AND status = 'ativo'
             ORDER BY departamento ASC, titulo ASC",
            ['empresa_id' => $empresaId]
        );
        
        // KPIs ativos
        $kpis = Database::query(
            "SELECT k.*, s.titulo as sop_titulo
             FROM kpis k
             LEFT JOIN sops s ON k.sop_id = s.id
             WHERE k.empresa_id = :empresa_id AND k.ativo = 1
             ORDER BY k.nome ASC",
            ['empresa_id' => $empresaId]
        );
        
        // Histórico de ações
        $historico = Database::query(
            "SELECT h.*, u.nome as admin_nome 
             FROM historico_cliente h
             LEFT JOIN usuarios u ON h.usuario_admin_id = u.id
             WHERE h.empresa_id = :empresa_id 
             ORDER BY h.criado_em DESC 
             LIMIT 20",
            ['empresa_id' => $empresaId]
        );
        
        // Consultores para dropdown de troca
        $consultores = Database::query(
            "SELECT id, nome FROM usuarios 
             WHERE perfil IN ('CONSULTOR_INTERNO', 'ADMIN_HOLDING') 
             ORDER BY nome ASC"
        );
        
        $dados = [
            'empresa' => $empresa,
            'diagnosticos' => $diagnosticos,
            'planos' => $planos,
            'sops' => $sops,
            'kpis' => $kpis,
            'historico' => $historico,
            'consultores' => $consultores
        ];
        
        require VIEW_PATH . '/admin/cliente-perfil.php';
    }

    /**
     * Trocar consultor responsável - F-13
     */
    public function trocarConsultor(): void
    {
        $this->protegerAdmin();
        Csrf::verificar();
        
        $empresaId = (int) ($_POST['empresa_id'] ?? 0);
        $novoConsultorId = (int) ($_POST['consultor_id'] ?? 0);
        $observacoes = trim($_POST['observacoes'] ?? '');
        
        if ($empresaId === 0 || $novoConsultorId === 0) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Dados inválidos.']);
            exit;
        }
        
        try {
            // Buscar dados atuais
            $empresa = Database::queryOne(
                "SELECT e.nome, e.consultor_id, c.nome as consultor_atual 
                 FROM empresas e 
                 LEFT JOIN usuarios c ON e.consultor_id = c.id 
                 WHERE e.id = :id",
                ['id' => $empresaId]
            );
            
            $novoConsultor = Database::queryOne(
                "SELECT nome, email FROM usuarios WHERE id = :id",
                ['id' => $novoConsultorId]
            );
            
            if (!$empresa || !$novoConsultor) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Empresa ou consultor não encontrado.']);
                exit;
            }
            
            // Não trocar se for o mesmo consultor
            if ($empresa['consultor_id'] == $novoConsultorId) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'O consultor selecionado já é o responsável atual.']);
                exit;
            }
            
            Database::beginTransaction();
            
            // Atualizar consultor
            Database::execute(
                "UPDATE empresas SET consultor_id = :consultor_id WHERE id = :id",
                ['consultor_id' => $novoConsultorId, 'id' => $empresaId]
            );
            
            // Registrar no histórico
            Database::execute(
                "INSERT INTO historico_cliente (empresa_id, usuario_admin_id, tipo_acao, dados_anteriores, dados_novos, observacoes, criado_em) 
                 VALUES (:empresa_id, :admin_id, 'troca_consultor', :dados_anteriores, :dados_novos, :observacoes, NOW())",
                [
                    'empresa_id' => $empresaId,
                    'admin_id' => Auth::id(),
                    'dados_anteriores' => json_encode(['consultor_id' => $empresa['consultor_id'], 'consultor_nome' => $empresa['consultor_atual']]),
                    'dados_novos' => json_encode(['consultor_id' => $novoConsultorId, 'consultor_nome' => $novoConsultor['nome']]),
                    'observacoes' => $observacoes
                ]
            );
            
            // Criar alerta para o novo consultor
            Database::execute(
                "INSERT INTO alertas (tipo, titulo, mensagem, empresa_id, criado_em) 
                 VALUES ('novo_cliente', 'Empresa Atribuída', :mensagem, :empresa_id, NOW())",
                [
                    'mensagem' => "Você foi designado como consultor responsável pela empresa {$empresa['nome']}",
                    'empresa_id' => $empresaId
                ]
            );
            
            Database::commit();
            
            AuditLog::registrar(
                'admin_trocar_consultor',
                'admin',
                "Consultor alterado para empresa {$empresa['nome']}",
                [
                    'empresa_id' => $empresaId,
                    'consultor_anterior' => $empresa['consultor_id'],
                    'consultor_novo' => $novoConsultorId,
                    'observacoes' => $observacoes
                ]
            );
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'mensagem' => "Consultor alterado para {$novoConsultor['nome']} com sucesso!"
            ]);
            
        } catch (Exception $e) {
            Database::rollback();
            Logger::error('Erro ao trocar consultor: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno.']);
        }
        exit;
    }

    /**
     * Alterar status do cliente - F-13
     */
    public function alterarStatusCliente(): void
    {
        $this->protegerAdmin();
        Csrf::verificar();
        
        $empresaId = (int) ($_POST['empresa_id'] ?? 0);
        $novoStatus = trim($_POST['status'] ?? '');
        $motivo = trim($_POST['motivo'] ?? '');
        
        $statusValidos = ['ativo', 'pausado', 'cancelado', 'suspenso'];
        
        if ($empresaId === 0 || !in_array($novoStatus, $statusValidos)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Dados inválidos.']);
            exit;
        }
        
        try {
            // Buscar dados atuais
            $empresa = Database::queryOne(
                "SELECT nome, status FROM empresas WHERE id = :id",
                ['id' => $empresaId]
            );
            
            if (!$empresa) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Empresa não encontrada.']);
                exit;
            }
            
            if ($empresa['status'] === $novoStatus) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'A empresa já possui este status.']);
                exit;
            }
            
            Database::beginTransaction();
            
            // Atualizar status
            Database::execute(
                "UPDATE empresas SET status = :status WHERE id = :id",
                ['status' => $novoStatus, 'id' => $empresaId]
            );
            
            // Se cancelando, desativar usuários
            if ($novoStatus === 'cancelado') {
                Database::execute(
                    "UPDATE usuarios SET ativo = 0 WHERE empresa_id = :empresa_id",
                    ['empresa_id' => $empresaId]
                );
            }
            
            // Se reativando, ativar usuários
            if ($novoStatus === 'ativo' && in_array($empresa['status'], ['pausado', 'suspenso'])) {
                Database::execute(
                    "UPDATE usuarios SET ativo = 1 WHERE empresa_id = :empresa_id",
                    ['empresa_id' => $empresaId]
                );
            }
            
            // Registrar no histórico
            Database::execute(
                "INSERT INTO historico_cliente (empresa_id, usuario_admin_id, tipo_acao, dados_anteriores, dados_novos, observacoes, criado_em) 
                 VALUES (:empresa_id, :admin_id, 'mudanca_status', :dados_anteriores, :dados_novos, :motivo, NOW())",
                [
                    'empresa_id' => $empresaId,
                    'admin_id' => Auth::id(),
                    'dados_anteriores' => json_encode(['status' => $empresa['status']]),
                    'dados_novos' => json_encode(['status' => $novoStatus]),
                    'motivo' => $motivo
                ]
            );
            
            Database::commit();
            
            AuditLog::registrar(
                'admin_alterar_status_cliente',
                'admin',
                "Status da empresa {$empresa['nome']} alterado de {$empresa['status']} para {$novoStatus}",
                [
                    'empresa_id' => $empresaId,
                    'status_anterior' => $empresa['status'],
                    'status_novo' => $novoStatus,
                    'motivo' => $motivo
                ]
            );
            
            $statusLabel = match($novoStatus) {
                'ativo' => 'Ativo',
                'pausado' => 'Pausado',
                'cancelado' => 'Cancelado',
                'suspenso' => 'Suspenso'
            };
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'mensagem' => "Status da empresa alterado para '{$statusLabel}' com sucesso!"
            ]);
            
        } catch (Exception $e) {
            Database::rollback();
            Logger::error('Erro ao alterar status: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno.']);
        }
        exit;
    }

    // ===== F-14: CONFIGURAÇÃO DE APIs DE IA =====

    /**
     * Toggle ativo/inativo de uma API - F-14
     */
    public function toggleApi(): void
    {
        $this->protegerAdmin();
        Csrf::verificar();
        
        $provedor = trim($_POST['provedor'] ?? '');
        $status = (int) ($_POST['status'] ?? 0);
        
        $provedoresValidos = ['openai', 'perplexity', 'anthropic'];
        if (!in_array($provedor, $provedoresValidos)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Provedor inválido.']);
            exit;
        }
        
        try {
            $chaveStatus = $provedor . '_ativo';
            $novoStatus = $status ? '1' : '0';
            
            // Atualizar configuração
            $sucesso = Configuracao::set($chaveStatus, $novoStatus, 'api_ia', "Toggle API {$provedor}");
            
            if ($sucesso) {
                // Limpar cache de status se estiver desativando
                if ($status === 0) {
                    try {
                        Database::execute(
                            "UPDATE api_status_cache SET status = 'desconhecido', ultimo_teste = NULL WHERE provedor = :provedor",
                            ['provedor' => $provedor]
                        );
                    } catch (Exception $e) {
                        // Tabela pode não existir ainda - ignorar erro
                        Logger::info('Tabela api_status_cache não existe ainda', ['erro' => $e->getMessage()]);
                    }
                }
                
                $statusLabel = $status ? 'ativada' : 'desativada';
                
                AuditLog::registrar(
                    'api_toggle',
                    'admin',
                    "Toggle API {$provedor} para {$statusLabel}",
                    [
                        'provedor' => $provedor,
                        'novo_status' => $novoStatus,
                        'admin_id' => Auth::id()
                    ]
                );
                
                header('Content-Type: application/json');
                echo json_encode([
                    'sucesso' => true,
                    'mensagem' => "API {$provedor} {$statusLabel} com sucesso!",
                    'status' => $novoStatus
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Erro ao atualizar status da API.']);
            }
            
        } catch (Exception $e) {
            Logger::error('Erro ao toggle API: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno.']);
        }
        exit;
    }

    /**
     * Salvar chave de API com criptografia - F-14
     */
    public function salvarChaveApi(): void
    {
        $this->protegerAdmin();
        Csrf::verificar();
        
        $provedor = trim($_POST['provedor'] ?? '');
        $chave = trim($_POST['chave'] ?? '');
        
        $provedoresValidos = ['openai', 'perplexity', 'anthropic'];
        if (!in_array($provedor, $provedoresValidos)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Provedor inválido.']);
            exit;
        }
        
        if (empty($chave)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Chave de API é obrigatória.']);
            exit;
        }
        
        // DEBUG: Log para identificar o problema
        Logger::warning("Tentativa de salvar chave API", [
            'provedor' => $provedor,
            'chave_length' => strlen($chave),
            'chave_prefix' => substr($chave, 0, 10) . '...',
            'user_id' => Auth::id()
        ]);

        // Validação de formato por regex de prefixo foi removida: os provedores mudam o
        // formato das chaves com o tempo (ex.: chaves de projeto da OpenAI, chaves de
        // organização, etc.) e uma regex rígida rejeitava chaves reais e válidas.
        // Mantemos apenas uma checagem mínima de tamanho; a validade real é confirmada
        // pelo botão "Testar API", que faz uma chamada real ao provedor.
        if (mb_strlen($chave) < 8) {
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => false,
                'erro' => 'Chave muito curta. Cole a chave completa fornecida pelo provedor.'
            ]);
            exit;
        }

        try {
            // Salvar no banco (a criptografia é automática via model)
            $chaveConfig = $provedor . '_key';
            $sucesso = Configuracao::set(
                $chaveConfig, 
                $chave,  // Usar chave original, model criptografa automaticamente
                'api_ia', 
                "Chave da API {$provedor}"
            );
            
            if ($sucesso) {
                // Invalidar cache de status para forçar novo teste
                try {
                    Database::execute(
                        "UPDATE api_status_cache SET status = 'desconhecido', ultimo_teste = NULL WHERE provedor = :provedor",
                        ['provedor' => $provedor]
                    );
                } catch (Exception $e) {
                    // Tabela pode não existir ainda - ignorar erro
                    Logger::info('Tabela api_status_cache não existe ainda', ['erro' => $e->getMessage()]);
                }
                
                AuditLog::registrar(
                    'api_chave_alterada',
                    'admin',
                    "Chave da API {$provedor} foi alterada",
                    [
                        'provedor' => $provedor,
                        'admin_id' => Auth::id(),
                        'chave_prefixo' => substr($chave, 0, 8) . '...' // Log apenas início para auditoria
                    ]
                );
                
                header('Content-Type: application/json');
                echo json_encode([
                    'sucesso' => true,
                    'mensagem' => "Chave da API {$provedor} salva com sucesso!"
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Erro ao salvar chave da API.']);
            }
            
        } catch (Exception $e) {
            Logger::error('Erro ao salvar chave API: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno ao salvar chave.']);
        }
        exit;
    }

    /**
     * Testar API individual - F-14
     */
    public function testarApiIndividual(): void
    {
        $this->protegerAdmin();
        Csrf::verificar();
        
        $provedor = trim($_POST['provedor'] ?? '');
        
        $provedoresValidos = ['openai', 'perplexity', 'anthropic'];
        if (!in_array($provedor, $provedoresValidos)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Provedor inválido.']);
            exit;
        }
        
        try {
            // Verificar se API está ativa
            if (Configuracao::get($provedor . '_ativo', '0') !== '1') {
                header('Content-Type: application/json');
                echo json_encode([
                    'sucesso' => false,
                    'erro' => 'API está desativada. Ative primeiro.',
                    'status' => 'inativa'
                ]);
                exit;
            }
            
            // Verificar se chave existe
            $chave = Configuracao::get($provedor . '_key');
            if (empty($chave)) {
                header('Content-Type: application/json');
                echo json_encode([
                    'sucesso' => false,
                    'erro' => 'Chave de API não configurada.',
                    'status' => 'sem_chave'
                ]);
                exit;
            }
            
            // Fazer teste real da API
            $resultado = $this->fazerTesteApi($provedor, $chave);
            
            // Atualizar cache de status
            try {
                Database::execute(
                    "UPDATE api_status_cache SET 
                     status = :status, 
                     ultimo_teste = NOW(), 
                     erro_detalhes = :erro,
                     tempo_resposta_ms = :tempo,
                     tentativas_falhas = CASE WHEN :sucesso THEN 0 ELSE tentativas_falhas + 1 END,
                     proximo_teste = DATE_ADD(NOW(), INTERVAL 1 HOUR)
                     WHERE provedor = :provedor",
                    [
                        'provedor' => $provedor,
                        'status' => $resultado['sucesso'] ? 'ativa' : 'erro',
                        'erro' => $resultado['erro'] ?? null,
                        'tempo' => $resultado['tempo_ms'] ?? null,
                        'sucesso' => $resultado['sucesso']
                    ]
                );
            } catch (Exception $e) {
                // Tabela pode não existir ainda - ignorar erro
                Logger::info('Tabela api_status_cache não existe ainda', ['erro' => $e->getMessage()]);
            }
            
            // Log da tentativa
            Database::execute(
                "INSERT INTO api_usage_log 
                 (provedor, endpoint, status_http, sucesso, erro_detalhes, tempo_resposta_ms, usuario_id, contexto, criado_em)
                 VALUES (:provedor, 'test', :status_http, :sucesso, :erro, :tempo, :usuario_id, :contexto, NOW())",
                [
                    'provedor' => $provedor,
                    'status_http' => $resultado['http_status'] ?? null,
                    'sucesso' => $resultado['sucesso'],
                    'erro' => $resultado['erro'] ?? null,
                    'tempo' => $resultado['tempo_ms'] ?? null,
                    'usuario_id' => Auth::id(),
                    'contexto' => json_encode(['tipo' => 'teste_admin', 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'])
                ]
            );
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => $resultado['sucesso'],
                'mensagem' => $resultado['sucesso'] ? 
                    "✅ {$provedor} funcionando (resposta em {$resultado['tempo_ms']}ms)" : 
                    "❌ Erro: {$resultado['erro']}",
                'status' => $resultado['sucesso'] ? 'ativa' : 'erro',
                'tempo_ms' => $resultado['tempo_ms'] ?? null
            ]);
            
        } catch (Exception $e) {
            Logger::error('Erro no teste de API: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => false,
                'erro' => 'Erro interno no teste.',
                'status' => 'erro'
            ]);
        }
        exit;
    }

    // === MÉTODOS SMTP ===

    /**
     * Salvar configuração SMTP
     */
    public function salvarSmtp(): void
    {
        $this->protegerAdmin();
        Csrf::verificar();
        
        $configs = [
            'smtp_host' => trim($_POST['smtp_host'] ?? ''),
            'smtp_port' => (int)($_POST['smtp_port'] ?? 587),
            'smtp_email' => filter_input(INPUT_POST, 'smtp_email', FILTER_SANITIZE_EMAIL),
            'smtp_nome' => htmlspecialchars(trim($_POST['smtp_nome'] ?? '')),
            'smtp_usuario' => trim($_POST['smtp_usuario'] ?? ''),
            'smtp_senha' => trim($_POST['smtp_senha'] ?? ''),
            'smtp_seguranca' => in_array($_POST['smtp_seguranca'], ['tls', 'ssl', 'none']) ? $_POST['smtp_seguranca'] : 'tls'
        ];
        
        // Validações
        if (empty($configs['smtp_host']) || empty($configs['smtp_email']) || empty($configs['smtp_usuario']) || empty($configs['smtp_senha'])) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Todos os campos são obrigatórios.']);
            exit;
        }
        
        if (!filter_var($configs['smtp_email'], FILTER_VALIDATE_EMAIL)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'E-mail inválido.']);
            exit;
        }
        
        try {
            // Salvar cada configuração individualmente
            foreach ($configs as $chave => $valor) {
                $criptografar = ($chave === 'smtp_senha') ? 1 : 0;
                
                // Verificar se já existe
                $existe = Database::queryOne(
                    "SELECT id FROM configuracoes WHERE chave = :chave",
                    ['chave' => $chave]
                );
                
                if ($existe) {
                    Database::execute(
                        "UPDATE configuracoes SET valor = :valor, criptografado = :cripto WHERE chave = :chave",
                        ['valor' => $valor, 'cripto' => $criptografar, 'chave' => $chave]
                    );
                } else {
                    Database::execute(
                        "INSERT INTO configuracoes (chave, valor, descricao, categoria, criptografado) VALUES (:chave, :valor, :desc, 'smtp', :cripto)",
                        [
                            'chave' => $chave,
                            'valor' => $valor,
                            'desc' => "Configuração SMTP: {$chave}",
                            'cripto' => $criptografar
                        ]
                    );
                }
            }
            
            Logger::acao('Configuração SMTP salva', ['admin_id' => Auth::id()]);
            
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true, 'mensagem' => 'Configuração SMTP salva com sucesso!']);
            
        } catch (Exception $e) {
            Logger::error('Erro ao salvar SMTP', ['erro' => $e->getMessage()]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno ao salvar.']);
        }
        exit;
    }

    /**
     * Testar envio de e-mail SMTP
     */
    public function testarSmtp(): void
    {
        $this->protegerAdmin();
        Csrf::verificar();
        
        try {
            // Buscar configurações SMTP
            $configs = Database::query(
                "SELECT chave, valor FROM configuracoes WHERE categoria = 'smtp' AND chave LIKE 'smtp_%'"
            );
            
            if (empty($configs)) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Configuração SMTP não encontrada. Salve primeiro.']);
                exit;
            }
            
            // Montar array de configurações
            $smtp = [];
            foreach ($configs as $config) {
                $smtp[$config['chave']] = $config['valor'];
            }
            
            // Verificar se configuração está completa
            $camposRequeridos = ['smtp_host', 'smtp_email', 'smtp_usuario', 'smtp_senha'];
            foreach ($camposRequeridos as $campo) {
                if (empty($smtp[$campo])) {
                    header('Content-Type: application/json');
                    echo json_encode(['sucesso' => false, 'erro' => "Campo {$campo} não configurado."]);
                    exit;
                }
            }
            
            // Tentar enviar e-mail de teste
            $sucesso = $this->enviarEmailTeste($smtp);
            
            if ($sucesso) {
                Logger::acao('Teste SMTP realizado com sucesso');
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => true, 'mensagem' => 'E-mail de teste enviado com sucesso!']);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Falha ao enviar e-mail. Verifique as configurações.']);
            }
            
        } catch (Exception $e) {
            Logger::error('Erro no teste SMTP', ['erro' => $e->getMessage()]);
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Enviar e-mail de teste usando configurações SMTP
     */
    private function enviarEmailTeste(array $smtp): bool
    {
        try {
            $usuario = Auth::usuario();
            $emailDestino = $usuario['email'] ?? 'admin@exemplo.com';
            
            $assunto = 'Teste de Configuração SMTP - O Consultor';
            $mensagem = "
            <h2>Teste de Configuração SMTP</h2>
            <p>Este é um e-mail de teste para verificar a configuração SMTP do sistema O Consultor.</p>
            
            <p><strong>Dados do teste:</strong></p>
            <ul>
                <li>Servidor: {$smtp['smtp_host']}:{$smtp['smtp_port']}</li>
                <li>Usuário: {$smtp['smtp_usuario']}</li>
                <li>Segurança: " . (strtoupper($smtp['smtp_seguranca'] ?? 'TLS')) . "</li>
                <li>Data/Hora: " . date('d/m/Y H:i:s') . "</li>
            </ul>
            
            <p>Se você recebeu este e-mail, a configuração está funcionando corretamente!</p>
            
            <hr>
            <p><small>Este e-mail foi enviado automaticamente pelo sistema O Consultor.</small></p>
            ";
            
            // Headers do e-mail
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=UTF-8',
                'From: ' . ($smtp['smtp_nome'] ?? 'O Consultor') . ' <' . $smtp['smtp_email'] . '>',
                'Reply-To: ' . $smtp['smtp_email'],
                'X-Mailer: PHP/' . phpversion()
            ];
            
            // Tentar envio usando função mail() do PHP com configuração SMTP
            // Em produção, recomenda-se usar PHPMailer ou similar para SMTP direto
            return mail($emailDestino, $assunto, $mensagem, implode("\r\n", $headers));
            
        } catch (Exception $e) {
            Logger::error('Erro ao enviar e-mail de teste', ['erro' => $e->getMessage()]);
            return false;
        }
    }

    // === MÉTODOS PRIVADOS F-14 ===

    /**
     * Fazer teste real da API
     */
    private function fazerTesteApi(string $provedor, string $chave): array
    {
        $startTime = microtime(true);
        
        try {
            switch ($provedor) {
                case 'openai':
                    return $this->testarOpenAI($chave, $startTime);
                
                case 'perplexity':
                    return $this->testarPerplexity($chave, $startTime);
                
                case 'anthropic':
                    return $this->testarAnthropic($chave, $startTime);
                
                default:
                    return ['sucesso' => false, 'erro' => 'Provedor não suportado'];
            }
        } catch (Exception $e) {
            $tempoMs = round((microtime(true) - $startTime) * 1000);
            return [
                'sucesso' => false,
                'erro' => 'Exceção: ' . $e->getMessage(),
                'tempo_ms' => $tempoMs
            ];
        }
    }

    /**
     * Testar OpenAI
     */
    private function testarOpenAI(string $chave, float $startTime): array
    {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'user', 'content' => 'Responda apenas: ok']
            ],
            'max_tokens' => 16
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $chave
            ]
        ]);
        
        $response = curl_exec($ch);
        $curlErro = curl_error($ch);
        $curlErrno = curl_errno($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $tempoMs = round((microtime(true) - $startTime) * 1000);
        curl_close($ch);

        if ($response === false || $curlErro) {
            error_log('[O CONSULTOR][TESTE-OpenAI] Erro cURL (errno ' . $curlErrno . '): ' . $curlErro);
            return [
                'sucesso' => false,
                'erro' => 'Falha de conexão com a OpenAI: ' . ($curlErro ?: 'erro desconhecido') . ' (curl errno ' . $curlErrno . ')',
                'tempo_ms' => $tempoMs,
                'http_status' => $httpStatus,
            ];
        }

        if ($httpStatus === 200) {
            $decoded = json_decode($response, true);
            if (isset($decoded['choices'][0]['message']['content'])) {
                return ['sucesso' => true, 'tempo_ms' => $tempoMs, 'http_status' => $httpStatus];
            }
            error_log('[O CONSULTOR][TESTE-OpenAI] HTTP 200 mas sem conteúdo esperado | body=' . substr((string) $response, 0, 500));
        }
        
        $decoded = json_decode($response, true);
        $erro = $decoded['error']['message'] ?? "HTTP {$httpStatus}";
        error_log('[O CONSULTOR][TESTE-OpenAI] HTTP ' . $httpStatus . ': ' . $erro . ' | body=' . substr((string) $response, 0, 500));
        
        return [
            'sucesso' => false, 
            'erro' => $erro,
            'tempo_ms' => $tempoMs,
            'http_status' => $httpStatus
        ];
    }

    /**
     * Testar Perplexity
     */
    private function testarPerplexity(string $chave, float $startTime): array
    {
        $url = 'https://api.perplexity.ai/chat/completions';
        
        $data = [
            // Modelos "llama-3.1-sonar-*" foram descontinuados pela Perplexity em 22/02/2025.
            // Modelos atuais: sonar, sonar-pro, sonar-reasoning-pro, sonar-deep-research.
            'model' => 'sonar',
            'messages' => [
                ['role' => 'user', 'content' => 'Responda apenas: ok']
            ],
            'max_tokens' => 16
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $chave
            ]
        ]);
        
        $response = curl_exec($ch);
        $curlErro = curl_error($ch);
        $curlErrno = curl_errno($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $tempoMs = round((microtime(true) - $startTime) * 1000);
        curl_close($ch);

        // Falha de transporte (DNS, SSL, timeout, conexão recusada etc.) — antes era
        // engolida silenciosamente e caía no genérico "HTTP 0".
        if ($response === false || $curlErro) {
            error_log('[O CONSULTOR][TESTE-Perplexity] Erro cURL (errno ' . $curlErrno . '): ' . $curlErro);
            return [
                'sucesso' => false,
                'erro' => 'Falha de conexão com a Perplexity: ' . ($curlErro ?: 'erro desconhecido') . ' (curl errno ' . $curlErrno . ')',
                'tempo_ms' => $tempoMs,
                'http_status' => $httpStatus,
            ];
        }

        if ($httpStatus === 200) {
            $decoded = json_decode($response, true);
            if (isset($decoded['choices'][0]['message']['content'])) {
                return ['sucesso' => true, 'tempo_ms' => $tempoMs, 'http_status' => $httpStatus];
            }
            error_log('[O CONSULTOR][TESTE-Perplexity] HTTP 200 mas sem conteúdo esperado | body=' . substr((string) $response, 0, 500));
        }

        $decoded = json_decode($response, true);
        $erro = $decoded['error']['message'] ?? ($decoded['error']['type'] ?? null) ?? "HTTP {$httpStatus}";
        error_log('[O CONSULTOR][TESTE-Perplexity] HTTP ' . $httpStatus . ': ' . $erro . ' | body=' . substr((string) $response, 0, 500));

        return [
            'sucesso' => false, 
            'erro' => $erro,
            'tempo_ms' => $tempoMs,
            'http_status' => $httpStatus
        ];
    }

    /**
     * Testar Anthropic
     */
    private function testarAnthropic(string $chave, float $startTime): array
    {
        $url = 'https://api.anthropic.com/v1/messages';
        
        $data = [
            'model' => 'claude-3-haiku-20240307',
            'max_tokens' => 16,
            'messages' => [
                ['role' => 'user', 'content' => 'Responda apenas: ok']
            ]
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $chave,
                'anthropic-version: 2023-06-01'
            ]
        ]);
        
        $response = curl_exec($ch);
        $curlErro = curl_error($ch);
        $curlErrno = curl_errno($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $tempoMs = round((microtime(true) - $startTime) * 1000);
        curl_close($ch);

        if ($response === false || $curlErro) {
            error_log('[O CONSULTOR][TESTE-Anthropic] Erro cURL (errno ' . $curlErrno . '): ' . $curlErro);
            return [
                'sucesso' => false,
                'erro' => 'Falha de conexão com a Anthropic: ' . ($curlErro ?: 'erro desconhecido') . ' (curl errno ' . $curlErrno . ')',
                'tempo_ms' => $tempoMs,
                'http_status' => $httpStatus,
            ];
        }

        if ($httpStatus === 200) {
            $decoded = json_decode($response, true);
            if (isset($decoded['content'][0]['text'])) {
                return ['sucesso' => true, 'tempo_ms' => $tempoMs, 'http_status' => $httpStatus];
            }
            error_log('[O CONSULTOR][TESTE-Anthropic] HTTP 200 mas sem conteúdo esperado | body=' . substr((string) $response, 0, 500));
        }
        
        $decoded = json_decode($response, true);
        $erro = $decoded['error']['message'] ?? "HTTP {$httpStatus}";
        error_log('[O CONSULTOR][TESTE-Anthropic] HTTP ' . $httpStatus . ': ' . $erro . ' | body=' . substr((string) $response, 0, 500));
        
        return [
            'sucesso' => false, 
            'erro' => $erro,
            'tempo_ms' => $tempoMs,
            'http_status' => $httpStatus
        ];
    }

    /**
     * Helper para descrições SMTP
     */
    private function getDescricaoSmtp(string $chave): string
    {
        $descricoes = [
            'smtp_host' => 'Servidor SMTP',
            'smtp_port' => 'Porta SMTP',
            'smtp_email' => 'E-mail remetente',
            'smtp_nome' => 'Nome do remetente',
            'smtp_usuario' => 'Usuário SMTP',
            'smtp_senha' => 'Senha SMTP',
            'smtp_seguranca' => 'Tipo de segurança'
        ];
        
        return $descricoes[$chave] ?? $chave;
    } // === MÉTODOS PRIVADOS F-13 ===

    /**
     * Gerar senha temporária
     */
    private function gerarSenhaTemporaria(): string
    {
        $caracteres = '23456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz';
        $senha = '';
        for ($i = 0; $i < 8; $i++) {
            $senha .= $caracteres[random_int(0, strlen($caracteres) - 1)];
        }
        return $senha;
    }

    /**
     * Enviar email de boas-vindas
     */
    private function enviarEmailBoasVindas(string $email, string $nome, string $empresa, string $senha): bool
    {
        try {
            $assunto = "Bem-vindo ao O Consultor - Acesso à plataforma da {$empresa}";
            
            $corpo = "
                <h2>Bem-vindo ao O Consultor!</h2>
                <p>Olá <strong>{$nome}</strong>,</p>
                <p>Sua empresa <strong>{$empresa}</strong> foi cadastrada na nossa plataforma de consultoria empresarial.</p>
                
                <div style='background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 5px;'>
                    <h3>Dados de Acesso:</h3>
                    <p><strong>E-mail:</strong> {$email}</p>
                    <p><strong>Senha temporária:</strong> <code style='background: #e9ecef; padding: 2px 4px;'>{$senha}</code></p>
                    <p><strong>Link de acesso:</strong> <a href='" . APP_URL . "/login'>" . APP_URL . "/login</a></p>
                </div>
                
                <p><strong>Importante:</strong></p>
                <ul>
                    <li>Esta é uma senha temporária. Você será solicitado a alterá-la no primeiro acesso.</li>
                    <li>Recomendamos fazer login assim que receber este email.</li>
                    <li>Em caso de dúvidas, entre em contato com seu consultor responsável.</li>
                </ul>
                
                <p>Estamos aqui para ajudar sua empresa a crescer e se desenvolver!</p>
                
                <p>Atenciosamente,<br>
                <strong>Equipe O Consultor</strong></p>
            ";
            
            $emailEnviado = Email::enviar($email, $assunto, $corpo);
            
            // Registrar tentativa de envio
            Database::execute(
                "INSERT INTO emails_enviados (destinatario_email, assunto, corpo_html, tipo, status, enviado_em, criado_em) 
                 VALUES (:email, :assunto, :corpo, 'boas_vindas', :status, NOW(), NOW())",
                [
                    'email' => $email,
                    'assunto' => $assunto,
                    'corpo' => $corpo,
                    'status' => $emailEnviado ? 'enviado' : 'falhado'
                ]
            );
            
            return $emailEnviado;
            
        } catch (Exception $e) {
            Logger::error('Erro ao enviar email de boas-vindas: ' . $e->getMessage());
            return false;
        }
    }

    public function configuracoes(): void
    {
        $this->protegerAdmin();
        
        // Buscar todas as configurações por grupo
        $apis = [
            'OpenAI (GPT + DALL-E)' => [
                'grupo' => 'api_openai',
                'configs' => Configuracao::getGrupo('api_openai'),
                'status' => Configuracao::apiAtiva('openai') ? 'ativa' : 'inativa',
                'descricao' => 'Geração de texto (SOPs, análises), transcrição de áudio via Whisper e geração de imagens via DALL-E'
            ],
            'Anthropic (Claude)' => [
                'grupo' => 'api_anthropic',
                'configs' => Configuracao::getGrupo('api_anthropic'),
                'status' => Configuracao::apiAtiva('anthropic') ? 'ativa' : 'inativa',
                'descricao' => 'Alternativa ao GPT para análise de conteúdo e geração de SOPs'
            ],
            'Perplexity' => [
                'grupo' => 'api_perplexity', 
                'configs' => Configuracao::getGrupo('api_perplexity'),
                'status' => Configuracao::apiAtiva('perplexity') ? 'ativa' : 'inativa',
                'descricao' => 'Busca de notícias e conteúdos da web em tempo real para Central de Conteúdo'
            ]
        ];
        
        $dados = [
            'apis' => $apis,
            'academy' => Configuracao::getGrupo('academy'),
            'academy_ativo' => Configuracao::get('academy_ativo', '0') === '1',
            'smtp' => Configuracao::getGrupo('smtp'),
            'smtp_ativo' => Configuracao::get('smtp_ativo', '0') === '1',
            'api_config' => Configuracao::getGrupo('api_config'),
            'modulos' => Configuracao::getGrupo('modulos'),
            'notificacoes' => Configuracao::getGrupo('notificacoes'),
            'usuarios_academy' => [
                ['nome' => 'Maria Cliente', 'email' => 'cliente@empresa.com.br', 'vinculada' => true],
                ['nome' => 'Pedro Rocha', 'email' => 'pedro@varejo.com.br', 'vinculada' => true],
                ['nome' => 'Ana Costa', 'email' => 'ana@foodservice.com.br', 'vinculada' => false],
                ['nome' => 'Lucas Tech', 'email' => 'lucas@digital.com.br', 'vinculada' => false],
            ],
        ];
        require VIEW_PATH . '/admin/configuracoes.php';
    }

    /**
     * Salva configurações do admin (APIs, Academy, módulos, etc.)
     * Todas as chaves são salvas no banco via Model Configuracao.
     */
    public function salvarConfiguracoes(): void
    {
        $this->protegerAdmin();
        Csrf::verificar();

        $grupo = htmlspecialchars(trim($_POST['grupo'] ?? 'geral'));
        $configs = $_POST['config'] ?? [];

        foreach ($configs as $chave => $valor) {
            $chave = htmlspecialchars(trim($chave));
            $valor = trim($valor);
            Configuracao::set($chave, $valor, $grupo);
        }

        // Limpar cache de configurações
        Configuracao::limparCache();

        Logger::acao('Configurações salvas', ['grupo' => $grupo, 'chaves' => array_keys($configs)]);

        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'mensagem' => 'Configurações salvas com sucesso!']);
        exit;
    }

    /**
     * Retorna descrição amigável para uma chave de configuração
     */
    private function getDescricaoConfig(string $chave): string
    {
        $descricoes = [
            // APIs
            'openai_key' => 'Chave da API OpenAI',
            'perplexity_key' => 'Chave da API Perplexity',  
            'anthropic_key' => 'Chave da API Anthropic',
            
            // Academy
            'academy_url' => 'URL base da Academy',
            'academy_token' => 'Token de integração SSO',
            
            // SMTP
            'smtp_host' => 'Servidor SMTP',
            'smtp_port' => 'Porta SMTP',
            'smtp_usuario' => 'Usuário SMTP',
            'smtp_senha' => 'Senha SMTP',
        ];
        
        return $descricoes[$chave] ?? ucwords(str_replace(['_', '-'], ' ', $chave));
    }

    /**
     * Gerar descrições das configurações da API
     */
    private function getDescricaoConfiguracao(string $chave): string
    {
        $descricoes = [
            // OpenAI
            'openai_key' => 'Chave de API da OpenAI (GPT + DALL-E + Whisper)',
            'openai_modelo' => 'Modelo padrão do OpenAI para geração de texto',
            'openai_modelo_mini' => 'Modelo econômico do OpenAI para tarefas simples',
            'openai_imagem_modelo' => 'Modelo de geração de imagens (ex.: gpt-image-1, dall-e-3, dall-e-2)',
            'openai_max_tokens' => 'Máximo de tokens por resposta OpenAI',
            'openai_ativo' => 'Toggle: API OpenAI ativa ou inativa',
            
            'anthropic_key' => 'Chave de API da Anthropic (Claude)',
            'anthropic_modelo' => 'Modelo padrão do Claude',
            'anthropic_ativo' => 'Toggle: API Anthropic ativa ou inativa',
            
            'perplexity_key' => 'Chave de API da Perplexity',
            'perplexity_modelo' => 'Modelo de busca Perplexity',
            'perplexity_ativo' => 'Toggle: API Perplexity ativa ou inativa',

            // Academy
            'academy_url' => 'URL base da plataforma My Academy',
            'academy_jwt_secret' => 'Chave secreta JWT para SSO Academy',
            'academy_sso_rota' => 'Rota de SSO na Academy',
            'academy_sso_parametro' => 'Nome do parâmetro do token na URL',
            'academy_ativo' => 'Toggle: integração Academy ativa',

            // SMTP
            'smtp_host' => 'Servidor SMTP para envio de emails',
            'smtp_porta' => 'Porta do servidor SMTP',
            'smtp_usuario' => 'Usuário/Email de autenticação SMTP',
            'smtp_senha' => 'Senha do SMTP (criptografada)',
            'smtp_criptografia' => 'Tipo de criptografia SMTP',
            'smtp_remetente_email' => 'Email do remetente (From)',
            'smtp_remetente_nome' => 'Nome do remetente exibido',
            'smtp_ativo' => 'Toggle: envio de emails ativo',

            // API Config
            'api_timeout' => 'Timeout em segundos para chamadas de IA',
            'api_connect_timeout' => 'Timeout de conexão em segundos',
            'api_max_retries' => 'Número máximo de tentativas por chamada',

            // App
            'app_nome' => 'Nome da plataforma',
            'app_email_contato' => 'Email de contato principal',
            'app_idioma' => 'Idioma padrão do sistema',
            'app_cor_primaria' => 'Cor primária da identidade visual',
            'app_cor_accent' => 'Cor de destaque (CTAs)',
        ];

        return $descricoes[$chave] ?? 'Configuração do sistema';
    }

    public function testarApis(): void
    {
        $this->protegerAdmin();
        Csrf::verificar();

        $resultados = [];

        // Testar OpenAI
        if (Configuracao::apiAtiva('openai')) {
            $resultados[] = array_merge(['api' => 'OpenAI'], ApiHelper::testarConexao('openai'));
        } else {
            $chaveVazia = empty(Configuracao::get('openai_key'));
            $resultados[] = [
                'api' => 'OpenAI', 
                'status' => 'inativa', 
                'tempo' => '-', 
                'erro' => $chaveVazia ? 'Chave de API não configurada' : 'API desativada nas configurações'
            ];
        }

        // Testar Anthropic
        if (Configuracao::apiAtiva('anthropic')) {
            $resultados[] = array_merge(['api' => 'Anthropic'], ApiHelper::testarConexao('anthropic'));
        } else {
            $chaveVazia = empty(Configuracao::get('anthropic_key'));
            $resultados[] = [
                'api' => 'Anthropic', 
                'status' => 'inativa', 
                'tempo' => '-', 
                'erro' => $chaveVazia ? 'Chave de API não configurada' : 'API desativada nas configurações'
            ];
        }

        // Testar Perplexity
        if (Configuracao::apiAtiva('perplexity')) {
            $resultados[] = array_merge(['api' => 'Perplexity'], ApiHelper::testarConexao('perplexity'));
        } else {
            $chaveVazia = empty(Configuracao::get('perplexity_key'));
            $resultados[] = [
                'api' => 'Perplexity', 
                'status' => 'inativa', 
                'tempo' => '-', 
                'erro' => $chaveVazia ? 'Chave de API não configurada' : 'API desativada nas configurações'
            ];
        }

        Logger::acao('Teste de APIs executado', ['resultados' => $resultados]);
        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'resultados' => $resultados]);
        exit;
    }

    public function testarAcademy(): void
    {
        $this->protegerAdmin();
        Csrf::verificar();

        $jwt = ApiHelper::gerarJwtAcademy([
            'sub'  => 'teste@oconsultor.com.br',
            'name' => 'Teste Integração',
        ]);

        Logger::acao('Teste de integração Academy executado');
        header('Content-Type: application/json');
        echo json_encode([
            'sucesso' => true,
            'status' => 'ok',
            'mensagem' => 'JWT gerado com sucesso. Em produção, verificaria resposta HTTP 302 da Academy.',
            'jwt_preview' => substr($jwt, 0, 50) . '...',
        ]);
        exit;
    }


    /**
     * Verificar status de uma API específica
     */
    public function statusApi(): void
    {
        $this->protegerAdmin();
        Csrf::verificar();
        
        $provedor = $_POST['provedor'] ?? '';
        
        if (empty($provedor)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Provedor não especificado.']);
            exit;
        }
        
        try {
            // Verificar se a API está ativa
            $ativo = Configuracao::apiAtiva($provedor);
            
            // Verificar se a chave está configurada
            $chave = Configuracao::get($provedor . '_key');
            $configurada = !empty($chave);
            
            // Gerar chave mascarada para exibição
            $chaveMascarada = '';
            if ($configurada) {
                $chaveMascarada = substr($chave, 0, 8) . '••••••••••••••••';
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'provedor' => $provedor,
                'ativo' => $ativo,
                'configurada' => $configurada,
                'chave_mascarada' => $chaveMascarada
            ]);
            
        } catch (Exception $e) {
            Logger::error('Erro ao verificar status da API: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno.']);
        }
        exit;
    }

    public function logs(): void
    {
        $this->protegerAdmin();
        
        try {
            // Buscar logs reais do sistema
            $logs = Database::query(
                "SELECT al.*, u.nome as usuario_nome, e.nome as empresa_nome
                 FROM audit_log al
                 LEFT JOIN usuarios u ON al.usuario_id = u.id
                 LEFT JOIN empresas e ON al.empresa_id = e.id
                 ORDER BY al.criado_em DESC
                 LIMIT 100"
            );
            
            // Formatar logs para a view
            $logsFormatados = array_map(function($log) {
                return [
                    'id' => $log['id'],
                    'acao' => $log['acao'],
                    'usuario' => $log['usuario_nome'] ?? 'Sistema',
                    'empresa' => $log['empresa_nome'] ?? 'N/A',
                    'detalhes' => $log['detalhes'],
                    'ip' => $log['ip_address'] ?? 'N/A',
                    'data' => $log['criado_em'],
                    'timestamp' => date('d/m/Y H:i:s', strtotime($log['criado_em']))
                ];
            }, $logs);
            
        } catch (Exception $e) {
            Logger::error('Erro ao buscar logs: ' . $e->getMessage());
            // Fallback básico em caso de erro
            $logsFormatados = [
                [
                    'id' => 1,
                    'acao' => 'Sistema inicializado',
                    'usuario' => 'Sistema',
                    'empresa' => 'N/A',
                    'detalhes' => 'Logs sendo carregados do banco de dados',
                    'ip' => 'localhost',
                    'data' => date('Y-m-d H:i:s'),
                    'timestamp' => date('d/m/Y H:i:s')
                ]
            ];
        }
        
        $dados = ['logs' => $logsFormatados];
        require VIEW_PATH . '/admin/logs.php';
    }

    public function relatorios(): void
    {
        $this->protegerAdmin();
        $dados = [];
        require VIEW_PATH . '/admin/relatorios.php';
    }

    /**
     * Permite ao ADMIN_HOLDING selecionar qual empresa gerenciar
     */
    public function selecionarEmpresa(): void
    {
        $this->protegerAdmin();
        Csrf::verificar();
        
        $empresaId = (int) ($_POST['empresa_id'] ?? 0);
        
        if ($empresaId > 0) {
            // Verificar se a empresa existe
            $empresa = Database::queryOne("SELECT id, nome FROM empresas WHERE id = :id", ['id' => $empresaId]);
            if ($empresa) {
                Auth::selecionarEmpresa($empresaId);
                Logger::acao('Empresa selecionada pelo admin', ['empresa_id' => $empresaId, 'empresa_nome' => $empresa['nome']]);
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => true, 'mensagem' => "Empresa '{$empresa['nome']}' selecionada."]);
                exit;
            }
        }
        
        // Limpar seleção (acesso global)
        Auth::selecionarEmpresa(null);
        Logger::acao('Modo acesso global ativado pelo admin');
        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'mensagem' => 'Modo acesso global ativado.']);
        exit;
    }

    /**
     * Listar empresas para select
     */
    public function listarEmpresas(): void
    {
        $this->protegerAdmin();
        
        $empresas = Database::query("SELECT id, nome FROM empresas ORDER BY nome ASC");
        
        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'empresas' => $empresas]);
        exit;
    }

    /**
     * Buscar empresas para AJAX (usado no cadastro de usuários)
     */
    public function buscarEmpresas(): void
    {
        $this->protegerAdmin();
        
        $empresas = Database::query("SELECT id, nome FROM empresas WHERE status = 'ativo' ORDER BY nome ASC");
        
        header('Content-Type: application/json');
        echo json_encode(['empresas' => $empresas]);
        exit;
    }

    /**
     * Visualizar usuário específico
     */
    public function visualizarUsuario($matches = null): void
    {
        $this->protegerAdmin();
        
        // Se chamado via rota com ID no path
        $usuarioId = $matches ? (int)$matches[1] : (int)($_GET['id'] ?? 0);
        
        if ($usuarioId === 0) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'ID do usuário não informado.']);
            exit;
        }
        
        $usuario = Database::queryOne(
            "SELECT u.*, e.nome as empresa_nome 
             FROM usuarios u 
             LEFT JOIN empresas e ON u.empresa_id = e.id 
             WHERE u.id = :id",
            ['id' => $usuarioId]
        );
        
        if (!$usuario) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Usuário não encontrado.']);
            exit;
        }
        
        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'usuario' => $usuario]);
        exit;
    }

    /**
     * Criar novo usuário
     */
    public function criarUsuario(): void
    {
        $this->protegerAdmin();
        Csrf::verificar();
        
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $perfil = trim($_POST['perfil'] ?? 'CLIENTE');
        $empresaId = !empty($_POST['empresa_id']) ? (int)$_POST['empresa_id'] : null;
        $senha = trim($_POST['senha'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        
        // Validações
        if (empty($nome) || empty($email) || empty($senha)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Nome, email e senha são obrigatórios.']);
            exit;
        }
        
        if (strlen($senha) < 6) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'A senha deve ter pelo menos 6 caracteres.']);
            exit;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Email inválido.']);
            exit;
        }
        
        // Verificar se email já existe
        $emailExistente = Database::queryOne(
            "SELECT id FROM usuarios WHERE email = :email",
            ['email' => $email]
        );
        
        if ($emailExistente) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Este email já está cadastrado.']);
            exit;
        }
        
        // Para clientes, empresa é obrigatória
        if ($perfil !== 'ADMIN_HOLDING' && !$empresaId) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Empresa é obrigatória para este perfil.']);
            exit;
        }
        
        try {
            $usuarioId = Database::execute(
                "INSERT INTO usuarios (nome, email, telefone, senha, perfil, empresa_id, ativo, criado_em) 
                 VALUES (:nome, :email, :telefone, :senha, :perfil, :empresa_id, :ativo, NOW())",
                [
                    'nome' => $nome,
                    'email' => $email,
                    'telefone' => $telefone,
                    'senha' => password_hash($senha, PASSWORD_DEFAULT),
                    'perfil' => $perfil,
                    'empresa_id' => $empresaId,
                    'ativo' => $ativo
                ]
            );
            
            AuditLog::registrar(
                'admin_criar_usuario',
                'admin',
                "Novo usuário criado: {$nome} ({$email})",
                [
                    'usuario_id' => Database::lastInsertId(),
                    'perfil' => $perfil,
                    'empresa_id' => $empresaId
                ]
            );
            
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true, 'mensagem' => 'Usuário criado com sucesso!']);
            
        } catch (Exception $e) {
            Logger::error('Erro ao criar usuário: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno ao criar usuário.']);
        }
        exit;
    }

    /**
     * Atualizar usuário existente
     */
    public function atualizarUsuario(): void
    {
        $this->protegerAdmin();
        Csrf::verificar();
        
        $usuarioId = (int) ($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $perfil = trim($_POST['perfil'] ?? 'CLIENTE');
        $empresaId = !empty($_POST['empresa_id']) ? (int)$_POST['empresa_id'] : null;
        $senha = trim($_POST['senha'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        
        if ($usuarioId === 0) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'ID do usuário não informado.']);
            exit;
        }
        
        // Validações
        if (empty($nome) || empty($email)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Nome e email são obrigatórios.']);
            exit;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Email inválido.']);
            exit;
        }
        
        // Verificar se email já existe (exceto o próprio usuário)
        $emailExistente = Database::queryOne(
            "SELECT id FROM usuarios WHERE email = :email AND id != :usuario_id",
            ['email' => $email, 'usuario_id' => $usuarioId]
        );
        
        if ($emailExistente) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Este email já está sendo usado por outro usuário.']);
            exit;
        }
        
        // Para clientes, empresa é obrigatória
        if ($perfil !== 'ADMIN_HOLDING' && !$empresaId) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Empresa é obrigatória para este perfil.']);
            exit;
        }
        
        try {
            // Query base
            $campos = [
                'nome' => $nome,
                'email' => $email,
                'telefone' => $telefone,
                'perfil' => $perfil,
                'empresa_id' => $empresaId,
                'ativo' => $ativo,
                'usuario_id' => $usuarioId
            ];
            
            $sql = "UPDATE usuarios SET 
                    nome = :nome, 
                    email = :email, 
                    telefone = :telefone, 
                    perfil = :perfil, 
                    empresa_id = :empresa_id, 
                    ativo = :ativo";
            
            // Atualizar senha se fornecida
            if (!empty($senha)) {
                if (strlen($senha) < 6) {
                    header('Content-Type: application/json');
                    echo json_encode(['sucesso' => false, 'erro' => 'A senha deve ter pelo menos 6 caracteres.']);
                    exit;
                }
                $sql .= ", senha = :senha";
                $campos['senha'] = password_hash($senha, PASSWORD_DEFAULT);
            }
            
            $sql .= " WHERE id = :usuario_id";
            
            Database::execute($sql, $campos);
            
            AuditLog::registrar(
                'admin_atualizar_usuario',
                'admin',
                "Usuário atualizado: {$nome} ({$email})",
                [
                    'usuario_id' => $usuarioId,
                    'perfil' => $perfil,
                    'empresa_id' => $empresaId
                ]
            );
            
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true, 'mensagem' => 'Usuário atualizado com sucesso!']);
            
        } catch (Exception $e) {
            Logger::error('Erro ao atualizar usuário: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno ao atualizar usuário.']);
        }
        exit;
    }

    /**
     * Alterar status do usuário (ativar/desativar)
     */
    public function alterarStatusUsuario(): void
    {
        $this->protegerAdmin();
        Csrf::verificar();
        
        $usuarioId = (int) ($_POST['usuario_id'] ?? 0);
        $ativo = (int) ($_POST['ativo'] ?? 0);
        
        if ($usuarioId === 0) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'ID do usuário não informado.']);
            exit;
        }
        
        // Não permitir desativar o próprio usuário
        if ($usuarioId === Auth::id()) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Você não pode alterar o status da sua própria conta.']);
            exit;
        }
        
        try {
            $usuario = Database::queryOne(
                "SELECT nome, email FROM usuarios WHERE id = :id",
                ['id' => $usuarioId]
            );
            
            if (!$usuario) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Usuário não encontrado.']);
                exit;
            }
            
            Database::execute(
                "UPDATE usuarios SET ativo = :ativo WHERE id = :usuario_id",
                ['ativo' => $ativo, 'usuario_id' => $usuarioId]
            );
            
            $acao = $ativo ? 'ativado' : 'desativado';
            
            AuditLog::registrar(
                'admin_alterar_status_usuario',
                'admin',
                "Usuário {$acao}: {$usuario['nome']} ({$usuario['email']})",
                [
                    'usuario_id' => $usuarioId,
                    'novo_status' => $ativo
                ]
            );
            
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true, 'mensagem' => "Usuário {$acao} com sucesso!"]);
            
        } catch (Exception $e) {
            Logger::error('Erro ao alterar status do usuário: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno.']);
        }
        exit;
    }

    // ===== GESTÃO DE EMPRESAS =====

    /**
     * Listar todas as empresas
     */
    public function empresas(): void
    {
        $this->protegerAdmin();
        
        // Buscar empresas com informações completas
        $sql = "SELECT e.*, 
                       c.nome as consultor_nome,
                       r.nome as responsavel_nome,
                       r.email as responsavel_email,
                       COUNT(DISTINCT u.id) as total_usuarios
                FROM empresas e 
                LEFT JOIN usuarios c ON e.consultor_id = c.id
                LEFT JOIN usuarios r ON e.responsavel_id = r.id
                LEFT JOIN usuarios u ON u.empresa_id = e.id AND u.ativo = 1
                GROUP BY e.id
                ORDER BY e.criado_em DESC";
        
        $empresas = Database::query($sql);
        
        $dados = ['empresas' => $empresas];
        require VIEW_PATH . '/admin/empresas.php';
    }

    /**
     * Formulário de nova empresa
     */
    public function novaEmpresa(): void
    {
        $this->protegerAdmin();
        require VIEW_PATH . '/admin/empresa-nova.php';
    }

    /**
     * Criar nova empresa
     */
    public function criarEmpresa(): void
    {
        // Log de debug
        error_log("CRIANDO EMPRESA - Método chamado");
        error_log("REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'indefinido'));
        error_log("POST data: " . json_encode($_POST));
        error_log("Headers: " . json_encode(getallheaders()));
        
        $this->protegerAdmin();
        
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Método não permitido.']);
            exit;
        }
        
        // Verificar CSRF manualmente para ter controle da resposta
        if (!Csrf::validar()) {
            error_log("CSRF FALHOU: token enviado = " . ($_POST['csrf_token'] ?? 'nenhum') . ", token sessão = " . (Session::get('csrf_token') ?? 'nenhum'));
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Token CSRF inválido. Recarregue a página e tente novamente.']);
            exit;
        }
        
        $nome = trim($_POST['nome'] ?? '');
        $cnpj = preg_replace('/[^0-9]/', '', $_POST['cnpj'] ?? '');
        $segmento = trim($_POST['segmento'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $endereco = trim($_POST['endereco'] ?? '');
        $cidade = trim($_POST['cidade'] ?? '');
        $estado = trim($_POST['estado'] ?? '');
        $cep = preg_replace('/[^0-9]/', '', $_POST['cep'] ?? '');
        $website = trim($_POST['website'] ?? '');
        
        // Validações
        $erros = [];
        
        if (empty($nome)) {
            $erros[] = 'Nome da empresa é obrigatório';
        }
        
        // Verificar se CNPJ já existe (se informado)
        if (!empty($cnpj)) {
            if (strlen($cnpj) !== 14) {
                $erros[] = 'CNPJ deve ter 14 dígitos';
            }
            
            $cnpjExistente = Database::queryOne(
                "SELECT id FROM empresas WHERE cnpj = :cnpj",
                ['cnpj' => $cnpj]
            );
            if ($cnpjExistente) {
                $erros[] = 'Este CNPJ já está cadastrado';
            }
        }
        
        // Validar CEP se informado
        if (!empty($cep) && strlen($cep) !== 8) {
            $erros[] = 'CEP deve ter 8 dígitos';
        }
        
        if (!empty($erros)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => implode('<br>', $erros)]);
            exit;
        }
        
        try {
            // Criar empresa
            $empresaId = Database::execute(
                "INSERT INTO empresas (nome, cnpj, segmento, telefone, endereco, cidade, estado, cep, website, 
                                      status, criado_em) 
                 VALUES (:nome, :cnpj, :segmento, :telefone, :endereco, :cidade, :estado, :cep, :website, 
                         'ativo', NOW())",
                [
                    'nome' => $nome,
                    'cnpj' => $cnpj ?: null,
                    'segmento' => $segmento ?: null,
                    'telefone' => $telefone ?: null,
                    'endereco' => $endereco ?: null,
                    'cidade' => $cidade ?: null,
                    'estado' => $estado ?: null,
                    'cep' => $cep ?: null,
                    'website' => $website ?: null
                ]
            );
            
            $empresaId = Database::lastInsertId();
            
            AuditLog::registrar(
                'admin_criar_empresa',
                'admin',
                "Nova empresa criada: {$nome}",
                [
                    'empresa_id' => $empresaId,
                    'empresa_nome' => $nome,
                    'cnpj' => $cnpj ?: null
                ]
            );
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true, 
                'mensagem' => "Empresa '{$nome}' criada com sucesso!",
                'empresa_id' => $empresaId
            ]);
            
        } catch (Exception $e) {
            Logger::error('Erro ao criar empresa: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno ao criar empresa.']);
        }
        exit;
    }

    /**
     * Visualizar empresa específica
     */
    public function visualizarEmpresa(): void
    {
        $this->protegerAdmin();
        
        $empresaId = (int) ($_GET['id'] ?? 0);
        
        if ($empresaId === 0) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'ID da empresa não informado.']);
            exit;
        }
        
        $empresa = Database::queryOne(
            "SELECT e.*, 
                    c.nome as consultor_nome,
                    r.nome as responsavel_nome
             FROM empresas e 
             LEFT JOIN usuarios c ON e.consultor_id = c.id
             LEFT JOIN usuarios r ON e.responsavel_id = r.id
             WHERE e.id = :id",
            ['id' => $empresaId]
        );
        
        if (!$empresa) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Empresa não encontrada.']);
            exit;
        }
        
        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'empresa' => $empresa]);
        exit;
    }

    /**
     * Atualizar empresa existente
     */
    public function atualizarEmpresa(): void
    {
        $this->protegerAdmin();
        Csrf::verificar();
        
        $empresaId = (int) ($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $cnpj = preg_replace('/[^0-9]/', '', $_POST['cnpj'] ?? '');
        $segmento = trim($_POST['segmento'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $endereco = trim($_POST['endereco'] ?? '');
        $cidade = trim($_POST['cidade'] ?? '');
        $estado = trim($_POST['estado'] ?? '');
        $cep = preg_replace('/[^0-9]/', '', $_POST['cep'] ?? '');
        $website = trim($_POST['website'] ?? '');
        
        if ($empresaId === 0) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'ID da empresa não informado.']);
            exit;
        }
        
        // Validações
        $erros = [];
        
        if (empty($nome)) {
            $erros[] = 'Nome da empresa é obrigatório';
        }
        
        // Verificar se CNPJ já existe (exceto a própria empresa)
        if (!empty($cnpj)) {
            if (strlen($cnpj) !== 14) {
                $erros[] = 'CNPJ deve ter 14 dígitos';
            }
            
            $cnpjExistente = Database::queryOne(
                "SELECT id FROM empresas WHERE cnpj = :cnpj AND id != :empresa_id",
                ['cnpj' => $cnpj, 'empresa_id' => $empresaId]
            );
            if ($cnpjExistente) {
                $erros[] = 'Este CNPJ já está sendo usado por outra empresa';
            }
        }
        
        // Validar CEP se informado
        if (!empty($cep) && strlen($cep) !== 8) {
            $erros[] = 'CEP deve ter 8 dígitos';
        }
        
        if (!empty($erros)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => implode('<br>', $erros)]);
            exit;
        }
        
        try {
            Database::execute(
                "UPDATE empresas SET 
                 nome = :nome, 
                 cnpj = :cnpj, 
                 segmento = :segmento, 
                 telefone = :telefone, 
                 endereco = :endereco, 
                 cidade = :cidade, 
                 estado = :estado, 
                 cep = :cep, 
                 website = :website,
                 atualizado_em = NOW()
                 WHERE id = :empresa_id",
                [
                    'nome' => $nome,
                    'cnpj' => $cnpj ?: null,
                    'segmento' => $segmento ?: null,
                    'telefone' => $telefone ?: null,
                    'endereco' => $endereco ?: null,
                    'cidade' => $cidade ?: null,
                    'estado' => $estado ?: null,
                    'cep' => $cep ?: null,
                    'website' => $website ?: null,
                    'empresa_id' => $empresaId
                ]
            );
            
            AuditLog::registrar(
                'admin_atualizar_empresa',
                'admin',
                "Empresa atualizada: {$nome}",
                [
                    'empresa_id' => $empresaId,
                    'empresa_nome' => $nome
                ]
            );
            
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true, 'mensagem' => 'Empresa atualizada com sucesso!']);
            
        } catch (Exception $e) {
            Logger::error('Erro ao atualizar empresa: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno ao atualizar empresa.']);
        }
        exit;
    }

    /**
     * Excluir empresa (apenas se não tiver usuários ativos)
     */
    public function excluirEmpresa(): void
    {
        $this->protegerAdmin();
        Csrf::verificar();
        
        $empresaId = (int) ($_POST['empresa_id'] ?? 0);
        
        if ($empresaId === 0) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'ID da empresa não informado.']);
            exit;
        }
        
        try {
            // Verificar se existe
            $empresa = Database::queryOne(
                "SELECT nome FROM empresas WHERE id = :id",
                ['id' => $empresaId]
            );
            
            if (!$empresa) {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'erro' => 'Empresa não encontrada.']);
                exit;
            }
            
            // Verificar se tem usuários ativos vinculados
            $usuariosAtivos = Database::queryOne(
                "SELECT COUNT(*) as count FROM usuarios WHERE empresa_id = :empresa_id AND ativo = 1",
                ['empresa_id' => $empresaId]
            )['count'] ?? 0;
            
            if ($usuariosAtivos > 0) {
                header('Content-Type: application/json');
                echo json_encode([
                    'sucesso' => false, 
                    'erro' => "Não é possível excluir a empresa '{$empresa['nome']}' pois ela possui {$usuariosAtivos} usuário(s) ativo(s). Desative os usuários primeiro."
                ]);
                exit;
            }
            
            // Excluir empresa
            Database::execute(
                "DELETE FROM empresas WHERE id = :empresa_id",
                ['empresa_id' => $empresaId]
            );
            
            AuditLog::registrar(
                'admin_excluir_empresa',
                'admin',
                "Empresa excluída: {$empresa['nome']}",
                [
                    'empresa_id' => $empresaId,
                    'empresa_nome' => $empresa['nome']
                ]
            );
            
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true, 'mensagem' => "Empresa '{$empresa['nome']}' excluída com sucesso!"]);
            
        } catch (Exception $e) {
            Logger::error('Erro ao excluir empresa: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno.']);
        }
        exit;
    }
}