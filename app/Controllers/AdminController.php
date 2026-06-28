<?php
/**
 * AdminController — Painel Administrativo (ADMIN_HOLDING exclusivo)
 */

class AdminController
{
    private function protegerAdmin(): void
    {
        Auth::exigirPerfil([Auth::ADMIN_HOLDING]);
    }

    public function index(): void
    {
        $this->protegerAdmin();
        $dados = [
            'totalUsuarios' => 47, 'totalEmpresas' => 23,
            'totalDiagnosticos' => 128, 'totalSops' => 312,
            'mrr' => 'R$ 85.400', 'churn' => '2,1%',
            'academyVinculadas' => 31, 'academyPendentes' => 16,
        ];
        require VIEW_PATH . '/admin/index.php';
    }

    public function usuarios(): void
    {
        $this->protegerAdmin();
        
        // Buscar usuários reais do banco
        $sql = "SELECT u.*, e.nome as empresa_nome 
                FROM usuarios u 
                LEFT JOIN empresas e ON u.empresa_id = e.id 
                ORDER BY u.criado_em DESC";
        $usuarios = Database::query($sql);
        
        $dados = ['usuarios' => $usuarios];
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
        
        // Buscar empresas com dados completos
        $sql = "SELECT e.*, 
                       c.nome as consultor_nome,
                       r.nome as responsavel_nome,
                       COUNT(DISTINCT u.id) as total_usuarios,
                       COUNT(DISTINCT d.id) as total_diagnosticos,
                       COUNT(DISTINCT p.id) as total_planos,
                       COUNT(DISTINCT s.id) as total_sops
                FROM empresas e 
                LEFT JOIN usuarios c ON e.consultor_id = c.id
                LEFT JOIN usuarios r ON e.responsavel_id = r.id
                LEFT JOIN usuarios u ON u.empresa_id = e.id
                LEFT JOIN diagnosticos d ON d.empresa_id = e.id
                LEFT JOIN planos_acao p ON p.empresa_id = e.id
                LEFT JOIN sops s ON s.empresa_id = e.id AND s.status = 'ativo'
                {$whereClause}
                GROUP BY e.id
                ORDER BY e.criado_em DESC";
        
        $clientes = Database::query($sql, $params);
        
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
             FROM planos_acao p 
             LEFT JOIN usuarios u ON p.usuario_id = u.id
             LEFT JOIN plano_prioridades pp ON pp.plano_id = p.id
             LEFT JOIN plano_tarefas t ON t.prioridade_id = pp.id
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
        
        // Validação básica do formato da chave
        $formatosValidos = [
            'openai' => '/^sk-[a-zA-Z0-9]{20,}$/',
            'perplexity' => '/^pplx-[a-zA-Z0-9]{20,}$/',
            'anthropic' => '/^sk-ant-[a-zA-Z0-9]{20,}$/'
        ];
        
        if (!preg_match($formatosValidos[$provedor], $chave)) {
            $exemplos = [
                'openai' => 'sk-...',
                'perplexity' => 'pplx-...',
                'anthropic' => 'sk-ant-...'
            ];
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => false, 
                'erro' => "Formato inválido. Esperado: {$exemplos[$provedor]}"
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
            if (!Configuracao::get($provedor . '_ativo', '0') === '1') {
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
            'max_tokens' => 10
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
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $tempoMs = round((microtime(true) - $startTime) * 1000);
        curl_close($ch);
        
        if ($httpStatus === 200) {
            $decoded = json_decode($response, true);
            if (isset($decoded['choices'][0]['message']['content'])) {
                return ['sucesso' => true, 'tempo_ms' => $tempoMs, 'http_status' => $httpStatus];
            }
        }
        
        $decoded = json_decode($response, true);
        $erro = $decoded['error']['message'] ?? "HTTP {$httpStatus}";
        
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
            'model' => 'llama-3.1-sonar-small-128k-online',
            'messages' => [
                ['role' => 'user', 'content' => 'Responda apenas: ok']
            ],
            'max_tokens' => 10
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
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $tempoMs = round((microtime(true) - $startTime) * 1000);
        curl_close($ch);
        
        if ($httpStatus === 200) {
            $decoded = json_decode($response, true);
            if (isset($decoded['choices'][0]['message']['content'])) {
                return ['sucesso' => true, 'tempo_ms' => $tempoMs, 'http_status' => $httpStatus];
            }
        }
        
        $decoded = json_decode($response, true);
        $erro = $decoded['error']['message'] ?? "HTTP {$httpStatus}";
        
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
            'max_tokens' => 10,
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
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $tempoMs = round((microtime(true) - $startTime) * 1000);
        curl_close($ch);
        
        if ($httpStatus === 200) {
            $decoded = json_decode($response, true);
            if (isset($decoded['content'][0]['text'])) {
                return ['sucesso' => true, 'tempo_ms' => $tempoMs, 'http_status' => $httpStatus];
            }
        }
        
        $decoded = json_decode($response, true);
        $erro = $decoded['error']['message'] ?? "HTTP {$httpStatus}";
        
        return [
            'sucesso' => false, 
            'erro' => $erro,
            'tempo_ms' => $tempoMs,
            'http_status' => $httpStatus
        ];
    }

    // === MÉTODOS PRIVADOS F-13 ===

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
            'openai_key' => 'Chave de API da OpenAI (GPT + DALL-E + Whisper)',
            'openai_modelo' => 'Modelo padrão do OpenAI para geração de texto',
            'openai_modelo_mini' => 'Modelo econômico do OpenAI para tarefas simples',
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
     * Testa conexão SMTP e opcionalmente envia email de teste
     */
    public function testarSmtp(): void
    {
        $this->protegerAdmin();
        Csrf::verificar();

        $emailTeste = filter_input(INPUT_POST, 'email_teste', FILTER_SANITIZE_EMAIL);

        if (!empty($emailTeste)) {
            // Enviar email de teste real
            $resultado = Email::enviarNotificacao(
                $emailTeste,
                'Admin',
                'Teste de Email — O Consultor',
                'Este é um email de teste do sistema O Consultor.<br><br>Se você está lendo isso, a configuração SMTP está funcionando corretamente!<br><br><strong>Enviado em:</strong> ' . date('d/m/Y H:i:s'),
                'teste'
            );

            Logger::acao('Email de teste enviado', ['para' => $emailTeste, 'sucesso' => $resultado['sucesso']]);

            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => $resultado['sucesso'],
                'mensagem' => $resultado['sucesso'] ? "Email de teste enviado para {$emailTeste}!" : "Falha: " . $resultado['erro'],
            ]);
            exit;
        }

        // Apenas testar conexão
        $resultado = Email::testarConexao();
        Logger::acao('Teste SMTP executado', ['resultado' => $resultado['sucesso'] ? 'ok' : 'erro']);

        header('Content-Type: application/json');
        echo json_encode([
            'sucesso' => $resultado['sucesso'],
            'mensagem' => $resultado['sucesso'] ? $resultado['mensagem'] : "Falha: " . $resultado['erro'],
        ]);
        exit;
    }

    public function logs(): void
    {
        $this->protegerAdmin();
        $dados = ['logs' => $this->getLogsMock()];
        require VIEW_PATH . '/admin/logs.php';
    }

    public function relatorios(): void
    {
        $this->protegerAdmin();
        $dados = [];
        require VIEW_PATH . '/admin/relatorios.php';
    }

    // ===== MOCKS =====
    private function getUsuariosMock(): array
    {
        return [
            ['id' => 1, 'nome' => 'Administrador', 'email' => 'admin@oconsultor.com.br', 'perfil' => 'ADMIN_HOLDING', 'empresa' => 'Holding Digital', 'status' => 'ativo', 'criado_em' => '2026-01-01', 'ultima_atividade' => '2026-06-26 09:15', 'academy' => true],
            ['id' => 2, 'nome' => 'João Consultor', 'email' => 'consultor@oconsultor.com.br', 'perfil' => 'CONSULTOR_INTERNO', 'empresa' => 'Holding Digital', 'status' => 'ativo', 'criado_em' => '2026-02-15', 'ultima_atividade' => '2026-06-26 08:42', 'academy' => true],
            ['id' => 3, 'nome' => 'Maria Cliente', 'email' => 'cliente@empresa.com.br', 'perfil' => 'CLIENTE', 'empresa' => 'Tech Solutions', 'status' => 'ativo', 'criado_em' => '2026-03-20', 'ultima_atividade' => '2026-06-25 14:30', 'academy' => true],
            ['id' => 4, 'nome' => 'Pedro Rocha', 'email' => 'pedro@varejo.com.br', 'perfil' => 'CLIENTE', 'empresa' => 'Varejo Express', 'status' => 'ativo', 'criado_em' => '2026-04-10', 'ultima_atividade' => '2026-06-24 16:00', 'academy' => true],
            ['id' => 5, 'nome' => 'Ana Costa', 'email' => 'ana@foodservice.com.br', 'perfil' => 'CLIENTE', 'empresa' => 'FoodService', 'status' => 'inativo', 'criado_em' => '2026-05-01', 'ultima_atividade' => '2026-05-20 11:00', 'academy' => false],
            ['id' => 6, 'nome' => 'Lucas Tech', 'email' => 'lucas@digital.com.br', 'perfil' => 'CLIENTE', 'empresa' => 'Digital Commerce', 'status' => 'ativo', 'criado_em' => '2026-05-15', 'ultima_atividade' => '2026-06-26 07:50', 'academy' => false],
        ];
    }

    private function getClientesMock(): array
    {
        return [
            ['id' => 1, 'nome' => 'Tech Solutions', 'setor' => 'Tecnologia', 'consultor' => 'João Consultor', 'status' => 'ativo', 'mrr' => 'R$ 4.500', 'criado_em' => '2026-03-01', 'maturidade' => 3],
            ['id' => 2, 'nome' => 'Digital Commerce', 'setor' => 'Varejo', 'consultor' => 'João Consultor', 'status' => 'ativo', 'mrr' => 'R$ 3.200', 'criado_em' => '2026-04-15', 'maturidade' => 2],
            ['id' => 3, 'nome' => 'Varejo Express', 'setor' => 'Varejo', 'consultor' => 'João Consultor', 'status' => 'ativo', 'mrr' => 'R$ 2.800', 'criado_em' => '2026-05-01', 'maturidade' => 2],
            ['id' => 4, 'nome' => 'FoodService', 'setor' => 'Alimentação', 'consultor' => 'João Consultor', 'status' => 'pausado', 'mrr' => 'R$ 0', 'criado_em' => '2026-05-10', 'maturidade' => 1],
            ['id' => 5, 'nome' => 'Construtora ABC', 'setor' => 'Construção', 'consultor' => 'João Consultor', 'status' => 'concluido', 'mrr' => 'R$ 5.000', 'criado_em' => '2026-02-01', 'maturidade' => 4],
        ];
    }

    private function getLogsMock(): array
    {
        return [
            ['data' => '2026-06-26 09:15:32', 'usuario' => 'admin@oconsultor.com.br', 'acao' => 'Login', 'modulo' => 'Auth', 'detalhes' => 'Login realizado com sucesso', 'ip' => '192.168.1.100'],
            ['data' => '2026-06-26 08:42:10', 'usuario' => 'consultor@oconsultor.com.br', 'acao' => 'Geração de SOP', 'modulo' => 'Manual Operacional', 'detalhes' => 'SOP-TI-ONB-002 gerado para Tech Solutions', 'ip' => '192.168.1.101'],
            ['data' => '2026-06-25 16:30:00', 'usuario' => 'cliente@empresa.com.br', 'acao' => 'Aprovação de SOP', 'modulo' => 'Manual Operacional', 'detalhes' => 'SOP-TI-OPS-002 aprovado', 'ip' => '10.0.0.55'],
            ['data' => '2026-06-25 14:30:45', 'usuario' => 'cliente@empresa.com.br', 'acao' => 'Acesso SSO Academy', 'modulo' => 'Academy', 'detalhes' => 'Redirecionamento para myacademy.com.br', 'ip' => '10.0.0.55'],
            ['data' => '2026-06-25 10:15:00', 'usuario' => 'consultor@oconsultor.com.br', 'acao' => 'Aprovação de conteúdo', 'modulo' => 'Máquina de Conteúdo', 'detalhes' => 'Carrossel "5 sinais de backup" aprovado', 'ip' => '192.168.1.101'],
            ['data' => '2026-06-24 18:00:00', 'usuario' => 'admin@oconsultor.com.br', 'acao' => 'Alteração de cliente', 'modulo' => 'Admin', 'detalhes' => 'Status FoodService alterado para Pausado', 'ip' => '192.168.1.100'],
            ['data' => '2026-06-24 09:00:00', 'usuario' => 'consultor@oconsultor.com.br', 'acao' => 'Login', 'modulo' => 'Auth', 'detalhes' => 'Login realizado com sucesso', 'ip' => '192.168.1.101'],
            ['data' => '2026-06-23 15:45:00', 'usuario' => 'admin@oconsultor.com.br', 'acao' => 'Logout', 'modulo' => 'Auth', 'detalhes' => 'Sessão encerrada', 'ip' => '192.168.1.100'],
        ];
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
     * Visualizar usuário específico
     */
    public function visualizarUsuario(): void
    {
        $this->protegerAdmin();
        
        $usuarioId = (int) ($_GET['id'] ?? 0);
        
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
}