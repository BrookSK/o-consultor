<?php
/**
 * DiagnosticoController — Módulo de Diagnóstico Empresarial
 * O Consultor — Sistema Operacional Empresarial
 *
 * ACESSO: ADMIN_HOLDING, CONSULTOR_INTERNO (cria para cliente), CLIENTE (preenche o próprio)
 */

class DiagnosticoController
{
    /**
     * Listagem de diagnósticos
     */
    public function index(): void
    {
        Auth::proteger();

        $usuario = Auth::usuario();
        $diagnosticos = [];
        
        // Buscar diagnósticos do usuário no banco
        if (Auth::perfil() === 'ADMIN_HOLDING') {
            // Admin vê todos os diagnósticos
            $diagnosticos = Database::query(
                "SELECT d.*, e.nome as empresa_nome, u.nome as responsavel_nome 
                 FROM diagnosticos d 
                 LEFT JOIN empresas e ON d.empresa_id = e.id 
                 LEFT JOIN usuarios u ON d.usuario_id = u.id 
                 ORDER BY d.criado_em DESC LIMIT 50"
            );
        } else {
            // Usuários vêem apenas os próprios diagnósticos ou da empresa
            $diagnosticos = Diagnostico::listarPorUsuario($usuario['id']);
        }
        
        // Mapear status para labels
        foreach ($diagnosticos as &$diag) {
            $diag['status_label'] = match($diag['status']) {
                'concluido' => 'Concluído',
                'em_andamento' => 'Em andamento',
                default => 'Rascunho'
            };
            
            // Calcular score se não existir
            if ($diag['pontuacao'] == 0 && $diag['status'] === 'concluido' && !empty($diag['respostas'])) {
                $respostas = json_decode($diag['respostas'], true) ?? [];
                $diag['pontuacao'] = $this->calcularScore($respostas);
            }
            
            $diag['score'] = match(true) {
                $diag['pontuacao'] >= 80 => 4,
                $diag['pontuacao'] >= 60 => 3,
                $diag['pontuacao'] >= 40 => 2,
                default => 1
            };
        }

        $dados = [
            'diagnosticos' => $diagnosticos,
        ];

        require VIEW_PATH . '/diagnostico/index.php';
    }

    /**
     * Iniciar novo diagnóstico (Bloco 1) - Inclui upload de documentos
     */
    public function novo(): void
    {
        Auth::proteger();
        
        $usuario = Auth::usuario();
        $empresaId = Auth::empresa();
        
        // Para ADMIN_HOLDING, buscar empresas disponíveis
        $empresasDisponiveis = [];
        if (Auth::perfil() === 'ADMIN_HOLDING') {
            $empresasDisponiveis = Database::query(
                "SELECT e.id, e.nome, e.segmento, e.responsavel_id, u.nome as responsavel_nome
                 FROM empresas e 
                 LEFT JOIN usuarios u ON e.responsavel_id = u.id
                 ORDER BY e.nome ASC"
            );
        }
        
        // Buscar ou criar rascunho em andamento
        $rascunho = Diagnostico::buscarOuCriarRascunho($usuario['id']);
        
        if (empty($rascunho)) {
            Flash::set('erro', 'Erro ao iniciar diagnóstico. Tente novamente.');
            header('Location: ' . APP_URL . '/diagnostico');
            exit;
        }
        
        // Se o rascunho já tem empresa_id, preencher automaticamente com dados da empresa
        if (!empty($rascunho['empresa_id'])) {
            $empresaRascunho = Database::queryOne(
                "SELECT nome, segmento, responsavel_id FROM empresas WHERE id = :id",
                ['id' => $rascunho['empresa_id']]
            );
            
            if ($empresaRascunho && empty($rascunho['empresa_nome'])) {
                // Auto-preencher com dados da empresa
                Database::execute(
                    "UPDATE diagnosticos_rascunho SET empresa_nome = :nome, setor = :setor WHERE id = :id",
                    [
                        'nome' => $empresaRascunho['nome'],
                        'setor' => $empresaRascunho['segmento'],
                        'id' => $rascunho['id']
                    ]
                );
                
                $rascunho['empresa_nome'] = $empresaRascunho['nome'];
                $rascunho['setor'] = $empresaRascunho['segmento'];
            }
        }
        
        // Buscar documentos já enviados pela empresa
        $documentosExistentes = [];
        if ($empresaId) {
            try {
                $documentosExistentes = Database::query(
                    "SELECT id, nome_original, tipo_documento, tamanho_bytes, processado_ia, criado_em 
                     FROM documentos_empresa 
                     WHERE empresa_id = :empresa_id AND ativo = 1 
                     ORDER BY criado_em DESC",
                    ['empresa_id' => $empresaId]
                );
            } catch (Exception $e) {
                // Tabela pode não existir ainda - ignorar erro silenciosamente
                $documentosExistentes = [];
                Logger::info('Tabela documentos_empresa não existe ainda', ['erro' => $e->getMessage()]);
            }
        }
        
        // Determinar em qual bloco deve começar
        $blocoAtual = (int) ($rascunho['bloco_atual'] ?? 1);
        
        // Não redirecionar automaticamente - deixar o usuário escolher
        // Apenas garantir que não seja maior que 5
        if ($blocoAtual > 5) {
            $blocoAtual = 5;
        }
        
        // Opções para selects
        $opcoes = $this->getOpcoesWizard();
        
        $dados = [
            'rascunho' => $rascunho,
            'bloco_atual' => 1,
            'total_blocos' => 5,
            'opcoes' => $opcoes,
            'documentos_existentes' => $documentosExistentes,
            'empresas_disponiveis' => $empresasDisponiveis
        ];

        require VIEW_PATH . '/diagnostico/bloco1.php';
    }
    
    /**
     * Wizard único de diagnóstico (nova abordagem)
     */
    public function wizard(): void
    {
        Auth::proteger();
        
        $usuario = Auth::usuario();
        $empresaId = Auth::empresa();
        
        // Para ADMIN_HOLDING, buscar empresas disponíveis
        $empresasDisponiveis = [];
        if (Auth::perfil() === 'ADMIN_HOLDING') {
            $empresasDisponiveis = Database::query(
                "SELECT e.id, e.nome, e.segmento, e.responsavel_id, u.nome as responsavel_nome
                 FROM empresas e 
                 LEFT JOIN usuarios u ON e.responsavel_id = u.id
                 ORDER BY e.nome ASC"
            );
        }
        
        // Buscar ou criar rascunho em andamento
        $rascunho = Diagnostico::buscarOuCriarRascunho($usuario['id']);
        
        if (empty($rascunho)) {
            Flash::set('erro', 'Erro ao iniciar diagnóstico. Tente novamente.');
            header('Location: ' . APP_URL . '/diagnostico');
            exit;
        }
        
        // Auto-preencher com dados da empresa se disponível
        if (!empty($rascunho['empresa_id'])) {
            $empresaRascunho = Database::queryOne(
                "SELECT nome, segmento FROM empresas WHERE id = :id",
                ['id' => $rascunho['empresa_id']]
            );
            
            if ($empresaRascunho && empty($rascunho['empresa_nome'])) {
                Database::execute(
                    "UPDATE diagnosticos_rascunho SET empresa_nome = :nome, setor = :setor WHERE id = :id",
                    [
                        'nome' => $empresaRascunho['nome'],
                        'setor' => $empresaRascunho['segmento'],
                        'id' => $rascunho['id']
                    ]
                );
                
                $rascunho['empresa_nome'] = $empresaRascunho['nome'];
                $rascunho['setor'] = $empresaRascunho['segmento'];
            }
        }
        
        // Opções para selects
        $opcoes = $this->getOpcoesWizard();
        
        $dados = [
            'rascunho' => $rascunho,
            'opcoes' => $opcoes,
            'empresas_disponiveis' => $empresasDisponiveis
        ];

        require VIEW_PATH . '/diagnostico/wizard.php';
    }
    
    /**
     * Exibir bloco específico do diagnóstico
     */
    public function bloco(): void
    {
        Auth::proteger();
        
        $bloco = (int) ($_GET['bloco'] ?? 1);
        $rascunhoId = (int) ($_GET['rascunho_id'] ?? 0);
        
        if ($bloco < 1 || $bloco > 5) {
            header('Location: ' . APP_URL . '/diagnostico/novo');
            exit;
        }
        
        // Buscar rascunho
        $rascunho = Database::queryOne("SELECT * FROM diagnosticos_rascunho WHERE id = :id AND usuario_id = :usuario_id", 
                                      ['id' => $rascunhoId, 'usuario_id' => Auth::id()]);
        
        if (!$rascunho || $rascunho['status'] !== 'em_andamento') {
            Flash::set('erro', 'Rascunho não encontrado ou já finalizado.');
            header('Location: ' . APP_URL . '/diagnostico/novo');
            exit;
        }
        
        // Verificar se pode acessar este bloco
        // Permitir acesso a qualquer bloco até o bloco_atual (blocos já preenchidos)
        // E permitir acesso ao próximo bloco (bloco_atual + 1) para continuar preenchendo
        $blocoMaximoPermitido = $rascunho['bloco_atual'] + 1;
        
        if ($bloco > $blocoMaximoPermitido || $bloco < 1) {
            // Só bloquear se tentar acessar blocos muito à frente ou inválidos
            Logger::warning('Tentativa de acesso a bloco inválido', [
                'bloco_solicitado' => $bloco,
                'bloco_atual' => $rascunho['bloco_atual'],
                'maximo_permitido' => $blocoMaximoPermitido,
                'rascunho_id' => $rascunhoId
            ]);
            
            // Redirecionar para o bloco atual
            header('Location: ' . APP_URL . '/diagnostico/bloco/' . $rascunho['bloco_atual'] . '?rascunho_id=' . $rascunhoId);
            exit;
        }
        
        $opcoes = $this->getOpcoesWizard();
        
        $dados = [
            'rascunho' => $rascunho,
            'bloco_atual' => $bloco,
            'total_blocos' => 5,
            'opcoes' => $opcoes
        ];
        
        require VIEW_PATH . '/diagnostico/bloco' . $bloco . '.php';
    }

    /**
     * Salvar bloco específico do diagnóstico
     */
    public function salvarBloco(): void
    {
        Auth::proteger();
        Csrf::verificar();
        
        $bloco = (int) ($_POST['bloco'] ?? 1);
        $rascunhoId = (int) ($_POST['rascunho_id'] ?? 0);
        
        if ($bloco < 1 || $bloco > 5) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Bloco inválido']);
            exit;
        }
        
        // Verificar se o rascunho pertence ao usuário
        $rascunho = Database::queryOne("SELECT * FROM diagnosticos_rascunho WHERE id = :id AND usuario_id = :usuario_id", 
                                      ['id' => $rascunhoId, 'usuario_id' => Auth::id()]);
        
        if (!$rascunho) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Rascunho não encontrado']);
            exit;
        }
        
        // Coletar dados do bloco
        $dadosBloco = [];
        
        switch ($bloco) {
            case 1:
                $dadosBloco = [
                    'empresa_nome' => htmlspecialchars(trim($_POST['empresa_nome'] ?? '')),
                    'setor' => htmlspecialchars(trim($_POST['setor'] ?? '')),
                    'descricao' => htmlspecialchars(trim($_POST['descricao'] ?? '')),
                    'tempo_existencia' => htmlspecialchars(trim($_POST['tempo_existencia'] ?? '')),
                    'estrutura_societaria' => htmlspecialchars(trim($_POST['estrutura_societaria'] ?? '')),
                    'unidades_filiais' => (int) ($_POST['unidades_filiais'] ?? 1),
                    'lingua_principal' => htmlspecialchars(trim($_POST['lingua_principal'] ?? 'Português'))
                ];
                
                // Validação mais flexível: se empresa já está no rascunho, não exigir campos vazios
                $empresaJaSelecionada = !empty($rascunho['empresa_id']) || !empty($rascunho['empresa_nome']);
                
                if (empty($dadosBloco['empresa_nome']) && !$empresaJaSelecionada) {
                    header('Content-Type: application/json');
                    echo json_encode(['sucesso' => false, 'mensagem' => 'Nome da empresa é obrigatório']);
                    exit;
                }
                
                if (empty($dadosBloco['setor']) && !$empresaJaSelecionada) {
                    header('Content-Type: application/json');
                    echo json_encode(['sucesso' => false, 'mensagem' => 'Setor de atuação é obrigatório']);
                    exit;
                }
                
                // Se empresa já selecionada e campos vieram vazios, manter dados existentes
                if ($empresaJaSelecionada) {
                    if (empty($dadosBloco['empresa_nome'])) $dadosBloco['empresa_nome'] = $rascunho['empresa_nome'];
                    if (empty($dadosBloco['setor'])) $dadosBloco['setor'] = $rascunho['setor'];
                }
                break;
                
            case 2:
                $dadosBloco = [
                    'colaboradores_internos' => (int) ($_POST['colaboradores_internos'] ?? 0),
                    'colaboradores_externos' => (int) ($_POST['colaboradores_externos'] ?? 0),
                    'departamentos' => $_POST['departamentos'] ?? [],
                    'clientes_ativos' => (int) ($_POST['clientes_ativos'] ?? 0),
                    'produtos_servicos' => htmlspecialchars(trim($_POST['produtos_servicos'] ?? '')),
                    'faturamento_mensal' => htmlspecialchars(trim($_POST['faturamento_mensal'] ?? '')),
                    'ticket_medio' => htmlspecialchars(trim($_POST['ticket_medio'] ?? '')),
                    'sites_referencia' => htmlspecialchars(trim($_POST['sites_referencia'] ?? ''))
                ];
                
                // Validação básica para bloco 2
                if (empty($dadosBloco['produtos_servicos'])) {
                    header('Content-Type: application/json');
                    echo json_encode(['sucesso' => false, 'mensagem' => 'Descrição dos produtos/serviços é obrigatória']);
                    exit;
                }
                
                break;
                
            case 3:
                $dadosBloco = [
                    'faturamento_mensal' => htmlspecialchars(trim($_POST['faturamento_mensal'] ?? '')),
                    'margem_lucro' => htmlspecialchars(trim($_POST['margem_lucro'] ?? '')),
                    'sistema_financeiro' => htmlspecialchars(trim($_POST['sistema_financeiro'] ?? '')),
                    'controle_fluxo_caixa' => htmlspecialchars(trim($_POST['controle_fluxo_caixa'] ?? '')),
                    'canais_vendas' => $_POST['canais_vendas'] ?? [],
                    'sistema_crm' => htmlspecialchars(trim($_POST['sistema_crm'] ?? '')),
                    'taxa_conversao' => htmlspecialchars(trim($_POST['taxa_conversao'] ?? '')),
                    'ticket_medio' => htmlspecialchars(trim($_POST['ticket_medio'] ?? '')),
                    'observacoes_bloco3' => htmlspecialchars(trim($_POST['observacoes_bloco3'] ?? ''))
                ];
                break;
                
            case 4:
                $dadosBloco = [
                    'estrutura_organizacional' => htmlspecialchars(trim($_POST['estrutura_organizacional'] ?? '')),
                    'politicas_rh' => $_POST['politicas_rh'] ?? [],
                    'taxa_turnover' => htmlspecialchars(trim($_POST['taxa_turnover'] ?? '')),
                    'programa_capacitacao' => htmlspecialchars(trim($_POST['programa_capacitacao'] ?? '')),
                    'mapeamento_riscos' => htmlspecialchars(trim($_POST['mapeamento_riscos'] ?? '')),
                    'seguros' => $_POST['seguros'] ?? [],
                    'backup_continuidade' => htmlspecialchars(trim($_POST['backup_continuidade'] ?? '')),
                    'conformidade_regulatoria' => htmlspecialchars(trim($_POST['conformidade_regulatoria'] ?? '')),
                    'dependencia_pessoas' => htmlspecialchars(trim($_POST['dependencia_pessoas'] ?? '')),
                    'dependencia_fornecedores' => htmlspecialchars(trim($_POST['dependencia_fornecedores'] ?? '')),
                    'observacoes_bloco4' => htmlspecialchars(trim($_POST['observacoes_bloco4'] ?? ''))
                ];
                break;
                
            case 5:
                $dadosBloco = [
                    'pontos_fortes' => htmlspecialchars(trim($_POST['pontos_fortes'] ?? '')),
                    'pontos_melhoria' => htmlspecialchars(trim($_POST['pontos_melhoria'] ?? '')),
                    'objetivo_12_meses' => htmlspecialchars(trim($_POST['objetivo_12_meses'] ?? '')),
                    'maturidade_percebida' => (int) ($_POST['maturidade_percebida'] ?? 3),
                    'planejamento_documentado' => htmlspecialchars(trim($_POST['planejamento_documentado'] ?? 'nao')),
                    'frequencia_reunioes' => htmlspecialchars(trim($_POST['frequencia_reunioes'] ?? '')),
                    'meta_faturamento' => htmlspecialchars(trim($_POST['meta_faturamento'] ?? 'nao'))
                ];
                break;
        }
        
        try {
            // Salvar bloco no banco
            $sucesso = Diagnostico::salvarBlocoRascunho($rascunhoId, $bloco, $dadosBloco);
            
            if (!$sucesso) {
                throw new Exception('Erro ao salvar dados no banco de dados. Verifique se todos os campos obrigatórios foram preenchidos.');
            }
            
            Logger::acao('Bloco de diagnóstico salvo', [
                'rascunho_id' => $rascunhoId,
                'bloco' => $bloco,
                'empresa' => $dadosBloco['empresa_nome'] ?? 'N/A',
                'dados_salvos' => count($dadosBloco) . ' campos',
                'debug_dados' => $dadosBloco,
                'bloco_atual_antes' => $rascunho['bloco_atual']
            ]);
            
            // Atualizar bloco_atual para o próximo bloco se necessário
            $proximoBloco = min($bloco + 1, 5); // Limitar a 5
            $novoBlocoAtual = max($rascunho['bloco_atual'], $proximoBloco);
            
            // Se é bloco 5 e tem flag especial, forçar bloco_atual = 5
            if ($bloco == 5 && isset($_POST['forcar_bloco_5'])) {
                $novoBlocoAtual = 5;
            }
            
            // Só atualizar se realmente avançou
            if ($novoBlocoAtual > $rascunho['bloco_atual'] && $novoBlocoAtual <= 5) {
                Database::execute(
                    "UPDATE diagnosticos_rascunho SET bloco_atual = :bloco_atual WHERE id = :id",
                    ['bloco_atual' => $novoBlocoAtual, 'id' => $rascunhoId]
                );
                
                Logger::acao('Bloco atual atualizado', [
                    'rascunho_id' => $rascunhoId,
                    'bloco_anterior' => $rascunho['bloco_atual'],
                    'novo_bloco_atual' => $novoBlocoAtual,
                    'forcado_bloco_5' => isset($_POST['forcar_bloco_5'])
                ]);
            }
            
            // Determinar próximo bloco para redirecionamento
            $proximoBlocoRedirect = $bloco < 5 ? $bloco + 1 : 5;
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Bloco ' . $bloco . ' salvo com sucesso!',
                'proximo_bloco' => $proximoBlocoRedirect,
                'redirect' => $bloco < 5 ? 
                    APP_URL . '/diagnostico/bloco/' . $proximoBlocoRedirect . '?rascunho_id=' . $rascunhoId :
                    APP_URL . '/diagnostico/bloco/' . $bloco . '?rascunho_id=' . $rascunhoId, // Manter no mesmo bloco se for o último
                'bloco_atual' => $novoBlocoAtual
            ]);
            
        } catch (Exception $e) {
            Logger::error('Erro ao salvar bloco diagnóstico', [
                'erro' => $e->getMessage(),
                'rascunho_id' => $rascunhoId,
                'bloco' => $bloco,
                'dados_enviados' => $dadosBloco,
                'trace' => $e->getTraceAsString()
            ]);
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => false, 
                'mensagem' => 'Erro ao salvar: ' . $e->getMessage(),
                'debug_info' => [
                    'rascunho_id' => $rascunhoId,
                    'bloco' => $bloco,
                    'campos_enviados' => array_keys($dadosBloco),
                    'dados_recebidos' => $dadosBloco,
                    'erro_detalhado' => $e->getMessage()
                ]
            ]);
        }
        
        exit;
    }

    /**
     * Gerar diagnóstico completo com IA
     */
    public function gerar(): void
    {
        // Começar com headers corretos
        header('Content-Type: application/json');
        
        try {
            Logger::info('=== INÍCIO GERAÇÃO DIAGNÓSTICO ===');
            
            Auth::proteger();
            Csrf::verificar();
            
            $rascunhoId = (int) ($_POST['rascunho_id'] ?? 0);
            
            Logger::info('Parâmetros recebidos', ['rascunho_id' => $rascunhoId, 'usuario_id' => Auth::id()]);
            
            if (!$rascunhoId) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'ID do rascunho não fornecido']);
                exit;
            }
            
            // Buscar rascunho
            $rascunho = Database::queryOne("SELECT * FROM diagnosticos_rascunho WHERE id = :id AND usuario_id = :usuario_id", 
                                          ['id' => $rascunhoId, 'usuario_id' => Auth::id()]);
            
            if (!$rascunho) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Rascunho não encontrado']);
                exit;
            }
            
            Logger::info('Rascunho encontrado', [
                'id' => $rascunho['id'],
                'status' => $rascunho['status'],
                'bloco_atual' => $rascunho['bloco_atual'],
                'empresa_nome' => $rascunho['empresa_nome'] ?? 'vazio'
            ]);
            
            if ($rascunho['status'] !== 'em_andamento') {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Rascunho já foi finalizado']);
                exit;
            }
            
            // Validações relaxadas
            if (empty($rascunho['empresa_nome'])) {
                echo json_encode(['sucesso' => false, 'mensagem' => 'Nome da empresa é obrigatório']);
                exit;
            }
            
            Logger::info('Validações básicas passaram');
            
            // Tentar criar o diagnóstico básico primeiro
            Logger::info('Montando dados completos...');
            $dadosCompletos = $this->montarDadosCompletos($rascunho);
            Logger::info('Dados completos montados', ['total_campos' => count($dadosCompletos)]);
            
            Logger::info('Gerando diagnóstico no banco...');
            $diagnosticoId = Diagnostico::gerarDoRascunho($rascunhoId);
            
            if (!$diagnosticoId) {
                Logger::error('Falha ao criar diagnóstico no banco');
                echo json_encode(['sucesso' => false, 'mensagem' => 'Falha ao criar diagnóstico no banco de dados']);
                exit;
            }
            
            Logger::info('Diagnóstico criado com sucesso', ['diagnostico_id' => $diagnosticoId]);
            
            // Calcular score
            $score = $this->calcularScore($dadosCompletos);
            Logger::info('Score calculado', ['score' => $score]);
            
            // Atualizar com pontuação
            Database::execute(
                "UPDATE diagnosticos SET pontuacao = :pontuacao WHERE id = :id",
                ['id' => $diagnosticoId, 'pontuacao' => $score]
            );
            
            Logger::info('=== DIAGNÓSTICO GERADO COM SUCESSO ===');
            
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Diagnóstico gerado com sucesso!',
                'diagnostico_id' => $diagnosticoId,
                'score' => $score,
                'redirect' => APP_URL . '/diagnostico/resultado/' . $diagnosticoId
            ]);
            
        } catch (Exception $e) {
            Logger::error('ERRO NA GERAÇÃO DE DIAGNÓSTICO', [
                'erro' => $e->getMessage(),
                'linha' => $e->getLine(),
                'arquivo' => basename($e->getFile()),
                'trace' => $e->getTraceAsString()
            ]);
            
            echo json_encode([
                'sucesso' => false, 
                'mensagem' => 'Erro interno: ' . $e->getMessage(),
                'debug_info' => [
                    'linha' => $e->getLine(),
                    'arquivo' => basename($e->getFile())
                ]
            ]);
        }
        
        exit;
    }
    
    /**
     * Upload de documentos da empresa para enriquecer IA
     */
    public function uploadDocumentos(): void
    {
        Auth::proteger();
        Csrf::verificar();
        
        $empresaId = Auth::empresa();
        $usuarioId = Auth::id();
        
        if (!$empresaId) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Empresa não identificada.']);
            exit;
        }
        
        if (empty($_FILES['documentos']) || empty($_FILES['documentos']['tmp_name'])) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Nenhum documento foi enviado.']);
            exit;
        }
        
        // Verificar se a classe DocumentoProcessor está disponível
        if (!class_exists('DocumentoProcessor')) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Sistema de processamento de documentos não está disponível.']);
            exit;
        }
        
        try {
            // Processar uploads
            $resultados = DocumentoProcessor::processarUploads($_FILES['documentos'], $empresaId, $usuarioId);
            
            // Contar sucessos e falhas
            $sucessos = array_filter($resultados, fn($r) => $r['sucesso']);
            $falhas = array_filter($resultados, fn($r) => !$r['sucesso']);
            
            $mensagem = sprintf(
                '%d documento(s) enviado(s) com sucesso',
                count($sucessos)
            );
            
            if (!empty($falhas)) {
                $mensagem .= sprintf(', %d falha(s)', count($falhas));
            }
            
            Logger::acao('Upload de documentos para diagnóstico', [
                'empresa_id' => $empresaId,
                'total_enviados' => count($resultados),
                'sucessos' => count($sucessos),
                'falhas' => count($falhas)
            ]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => !empty($sucessos),
                'mensagem' => $mensagem,
                'resultados' => $resultados,
                'total_sucessos' => count($sucessos),
                'total_falhas' => count($falhas)
            ]);
        } catch (Exception $e) {
            Logger::error('Erro no upload de documentos: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'erro' => 'Erro interno no processamento dos documentos.']);
        }
        
        exit;
    }

    /**
     * Selecionar empresa para diagnóstico (ADMIN_HOLDING apenas)
     */
    public function selecionarEmpresa(): void
    {
        Auth::proteger();
        Csrf::verificar();
        
        if (Auth::perfil() !== 'ADMIN_HOLDING') {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso não autorizado']);
            exit;
        }
        
        $empresaId = (int) ($_POST['empresa_id'] ?? 0);
        $rascunhoId = (int) ($_POST['rascunho_id'] ?? 0);
        
        if (!$empresaId || !$rascunhoId) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Empresa e rascunho são obrigatórios']);
            exit;
        }
        
        try {
            // Buscar dados da empresa
            $empresa = Database::queryOne(
                "SELECT nome, segmento FROM empresas WHERE id = :id",
                ['id' => $empresaId]
            );
            
            if (!$empresa) {
                throw new Exception('Empresa não encontrada');
            }
            
            // Atualizar rascunho com dados da empresa
            $sucesso = Database::execute(
                "UPDATE diagnosticos_rascunho SET empresa_id = :empresa_id, empresa_nome = :empresa_nome, setor = :setor WHERE id = :rascunho_id AND usuario_id = :usuario_id",
                [
                    'empresa_id' => $empresaId,
                    'empresa_nome' => $empresa['nome'],
                    'setor' => $empresa['segmento'],
                    'rascunho_id' => $rascunhoId,
                    'usuario_id' => Auth::id()
                ]
            );
            
            if ($sucesso) {
                Logger::acao('Empresa selecionada para diagnóstico', [
                    'empresa_id' => $empresaId,
                    'empresa_nome' => $empresa['nome'],
                    'rascunho_id' => $rascunhoId
                ]);
                
                header('Content-Type: application/json');
                echo json_encode([
                    'sucesso' => true,
                    'mensagem' => 'Empresa selecionada com sucesso',
                    'dados' => [
                        'empresa_nome' => $empresa['nome'],
                        'setor' => $empresa['segmento']
                    ]
                ]);
            } else {
                throw new Exception('Erro ao atualizar rascunho com dados da empresa');
            }
        } catch (Exception $e) {
            Logger::error('Erro ao selecionar empresa para diagnóstico: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro: ' . $e->getMessage()]);
        }
        
        exit;
    }

    /**
     * Limpar rascunho de diagnóstico
     */
    public function limparRascunho(): void
    {
        Auth::proteger();
        Csrf::verificar();
        
        $usuarioId = Auth::id();
        
        try {
            // Buscar rascunho em andamento
            $rascunho = Database::queryOne(
                "SELECT id FROM diagnosticos_rascunho WHERE usuario_id = :usuario_id AND status = 'em_andamento'", 
                ['usuario_id' => $usuarioId]
            );
            
            if ($rascunho) {
                // Excluir rascunho
                $sucesso = Database::execute(
                    "DELETE FROM diagnosticos_rascunho WHERE id = :id AND usuario_id = :usuario_id",
                    ['id' => $rascunho['id'], 'usuario_id' => $usuarioId]
                );
                
                if ($sucesso) {
                    Logger::acao('Rascunho de diagnóstico limpo', [
                        'rascunho_id' => $rascunho['id'],
                        'usuario_id' => $usuarioId
                    ]);
                    
                    header('Content-Type: application/json');
                    echo json_encode(['sucesso' => true, 'mensagem' => 'Rascunho limpo com sucesso!']);
                } else {
                    throw new Exception('Erro ao excluir rascunho do banco de dados');
                }
            } else {
                header('Content-Type: application/json');
                echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhum rascunho encontrado para limpar']);
            }
        } catch (Exception $e) {
            Logger::error('Erro ao limpar rascunho: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao limpar rascunho: ' . $e->getMessage()]);
        }
        
        exit;
    }

    /**
     * Debug do fluxo de diagnóstico (TEMPORARY)
     */
    public function debug(): void
    {
        Auth::proteger();
        
        $usuario = Auth::usuario();
        
        echo "<h1>Debug Diagnóstico</h1>";
        echo "<h2>Usuário Atual:</h2>";
        echo "<pre>" . print_r($usuario, true) . "</pre>";
        
        $rascunho = Database::queryOne(
            "SELECT * FROM diagnosticos_rascunho WHERE usuario_id = :usuario_id AND status = 'em_andamento' ORDER BY criado_em DESC LIMIT 1",
            ['usuario_id' => $usuario['id']]
        );
        
        echo "<h2>Rascunho Atual:</h2>";
        echo "<pre>" . print_r($rascunho, true) . "</pre>";
        
        if ($rascunho) {
            echo "<h2>Teste de Acesso aos Blocos:</h2>";
            for ($i = 1; $i <= 5; $i++) {
                $podeAcessar = $i <= ($rascunho['bloco_atual'] + 1);
                echo "Bloco $i: " . ($podeAcessar ? "✅ PODE ACESSAR" : "❌ NÃO PODE ACESSAR") . "<br>";
                if ($podeAcessar) {
                    echo "<a href='" . APP_URL . "/diagnostico/bloco/$i?rascunho_id=" . $rascunho['id'] . "'>Testar Acesso ao Bloco $i</a><br>";
                }
            }
        }
        
        if (Auth::perfil() === 'ADMIN_HOLDING') {
            $empresas = Database::query(
                "SELECT e.id, e.nome, e.segmento, e.responsavel_id, u.nome as responsavel_nome
                 FROM empresas e 
                 LEFT JOIN usuarios u ON e.responsavel_id = u.id
                 ORDER BY e.nome ASC"
            );
            
            echo "<h2>Empresas Disponíveis:</h2>";
            echo "<pre>" . print_r($empresas, true) . "</pre>";
        }
        
        exit;
    }

    /**
     * Salvar diagnóstico (método legado - mantido para compatibilidade)
     */
    public function salvar(): void
    {
        Auth::proteger();
        Csrf::verificar();

        // Coletar todos os dados dos 5 blocos
        $dadosForm = [
            // Bloco 1 — Identificação
            'empresa_nome' => htmlspecialchars(trim($_POST['empresa_nome'] ?? '')),
            'setor' => htmlspecialchars(trim($_POST['setor'] ?? '')),
            'descricao' => htmlspecialchars(trim(substr($_POST['descricao'] ?? '', 0, 300))),
            'tempo_existencia' => htmlspecialchars(trim($_POST['tempo_existencia'] ?? '')),
            'estrutura_societaria' => htmlspecialchars(trim($_POST['estrutura_societaria'] ?? '')),
            'unidades_filiais' => (int) ($_POST['unidades_filiais'] ?? 1),
            'lingua_principal' => htmlspecialchars(trim($_POST['lingua_principal'] ?? 'Portugues')),

            // Bloco 2 — Estrutura Operacional
            'colaboradores_internos' => (int) ($_POST['colaboradores_internos'] ?? 0),
            'colaboradores_externos' => (int) ($_POST['colaboradores_externos'] ?? 0),
            'departamentos' => $_POST['departamentos'] ?? [],
            'clientes_ativos' => (int) ($_POST['clientes_ativos'] ?? 0),
            'produtos_servicos' => htmlspecialchars(trim($_POST['produtos_servicos'] ?? '')),
            'faturamento_mensal' => htmlspecialchars(trim($_POST['faturamento_mensal'] ?? '')),
            'ticket_medio' => htmlspecialchars(trim($_POST['ticket_medio'] ?? '')),
            'sites_referencia' => htmlspecialchars(trim($_POST['sites_referencia'] ?? '')),

            // Bloco 3 — Operação Atual
            'processo_entrega' => htmlspecialchars(trim($_POST['processo_entrega'] ?? '')),
            'ferramentas_softwares' => htmlspecialchars(trim($_POST['ferramentas_softwares'] ?? '')),
            'fornecedores_criticos' => htmlspecialchars(trim($_POST['fornecedores_criticos'] ?? '')),
            'dependencia_pessoa' => htmlspecialchars(trim($_POST['dependencia_pessoa'] ?? '')),
            'integracoes' => htmlspecialchars(trim($_POST['integracoes'] ?? '')),
            'processos_documentados' => (int) ($_POST['processos_documentados'] ?? 0),
            'ferramentas_gestao' => $_POST['ferramentas_gestao'] ?? [],

            // Bloco 4 — Problemas e Riscos
            'problemas_operacionais' => htmlspecialchars(trim($_POST['problemas_operacionais'] ?? '')),
            'riscos_identificados' => htmlspecialchars(trim($_POST['riscos_identificados'] ?? '')),
            'incidentes_tipo' => htmlspecialchars(trim($_POST['incidentes_tipo'] ?? '')),
            'incidentes_descricao' => htmlspecialchars(trim($_POST['incidentes_descricao'] ?? '')),
            'areas_vulneraveis' => $_POST['areas_vulneraveis'] ?? [],
            'cliente_concentrado' => htmlspecialchars(trim($_POST['cliente_concentrado'] ?? 'nao')),
            'fornecedor_insubstituivel' => htmlspecialchars(trim($_POST['fornecedor_insubstituivel'] ?? 'nao')),
            'processos_sem_backup' => htmlspecialchars(trim($_POST['processos_sem_backup'] ?? 'nao')),

            // Bloco 5 — Contexto Estratégico
            'pontos_fortes' => htmlspecialchars(trim($_POST['pontos_fortes'] ?? '')),
            'pontos_melhoria' => htmlspecialchars(trim($_POST['pontos_melhoria'] ?? '')),
            'objetivo_12_meses' => htmlspecialchars(trim($_POST['objetivo_12_meses'] ?? '')),
            'maturidade_percebida' => (int) ($_POST['maturidade_percebida'] ?? 3),
            'planejamento_documentado' => htmlspecialchars(trim($_POST['planejamento_documentado'] ?? 'nao')),
            'frequencia_reunioes' => htmlspecialchars(trim($_POST['frequencia_reunioes'] ?? '')),
            'meta_faturamento' => htmlspecialchars(trim($_POST['meta_faturamento'] ?? 'nao')),
        ];

        if (empty($dadosForm['empresa_nome'])) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Nome da empresa é obrigatório']);
            exit;
        }

        try {
            // 1. Criar ou buscar empresa
            $empresaId = null;
            $empresaExistente = Database::queryOne("SELECT id FROM empresas WHERE nome = ?", [$dadosForm['empresa_nome']]);
            
            if ($empresaExistente) {
                $empresaId = $empresaExistente['id'];
            } else {
                // Criar nova empresa
                $dadosEmpresa = [
                    'nome' => $dadosForm['empresa_nome'],
                    'segmento' => $dadosForm['setor'],
                    'responsavel_id' => Auth::id()
                ];
                $empresaId = Empresa::criar($dadosEmpresa);
                
                if (!$empresaId) {
                    throw new Exception('Erro ao criar empresa');
                }
                
                // Atualizar usuário atual para ser da empresa criada se não tiver empresa
                $usuarioAtual = User::buscarPorId(Auth::id());
                if (empty($usuarioAtual['empresa_id'])) {
                    User::atualizar(Auth::id(), ['empresa_id' => $empresaId]);
                }
            }

            // 2. Salvar diagnóstico no banco
            $dadosDiagnostico = [
                'empresa_id' => $empresaId,
                'usuario_id' => Auth::id(),
                'respostas' => json_encode($dadosForm),
                'pontuacao' => 0,
                'status' => 'concluido',
                'observacoes' => 'Diagnóstico via wizard'
            ];

            // Calcular Score de Maturidade (1-4)
            $score = $this->calcularScore($dadosForm);
            $dadosDiagnostico['pontuacao'] = $score;

            $diagnosticoId = Diagnostico::criar($dadosDiagnostico);
            
            if (!$diagnosticoId) {
                throw new Exception('Erro ao salvar diagnóstico');
            }

            // Gerar resultado
            $resultado = $this->gerarResultado($dadosForm, $score);
            $resultado['diagnostico_id'] = $diagnosticoId;
            $resultado['empresa_id'] = $empresaId;

            Logger::acao('Diagnóstico empresarial salvo', [
                'empresa' => $dadosForm['empresa_nome'],
                'empresa_id' => $empresaId,
                'diagnostico_id' => $diagnosticoId,
                'score' => $score,
            ]);

            // Salvar na sessão para exibir resultado
            Session::set('diagnostico_resultado', $resultado);
            Session::set('diagnostico_dados', $dadosForm);

            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'score' => $score,
                'empresa_id' => $empresaId,
                'diagnostico_id' => $diagnosticoId,
                'mensagem' => 'Diagnóstico concluído e salvo com sucesso!',
                'redirect' => APP_URL . '/diagnostico/resultado',
            ]);
            
        } catch (Exception $e) {
            Logger::error('Erro ao salvar diagnóstico: ' . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => false, 
                'mensagem' => 'Erro ao salvar diagnóstico: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Exibe o resultado do diagnóstico
     */
    public function resultado(): void
    {
        Auth::proteger();

        // Tentar pegar ID do diagnóstico da URL
        $diagnosticoId = null;
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if (preg_match('/\/diagnostico\/resultado\/(\d+)/', $path, $matches)) {
            $diagnosticoId = (int) $matches[1];
        }
        
        $resultado = null;
        $dadosForm = null;
        
        if ($diagnosticoId) {
            // Buscar diagnóstico específico no banco
            $diagnostico = Diagnostico::buscarPorId($diagnosticoId);
            
            if ($diagnostico && $diagnostico['usuario_id'] == Auth::id()) {
                $dadosForm = json_decode($diagnostico['respostas'], true) ?? [];
                $score = $diagnostico['pontuacao'] ?: $this->calcularScore($dadosForm);
                
                // Gerar resultado baseado nos dados
                $resultado = $this->gerarResultado($dadosForm, $score);
                $resultado['diagnostico_id'] = $diagnosticoId;
                $resultado['empresa_id'] = $diagnostico['empresa_id'];
                
                // Se há observações da IA, usar elas
                if (!empty($diagnostico['observacoes'])) {
                    $observacoesIA = json_decode($diagnostico['observacoes'], true);
                    if (is_array($observacoesIA)) {
                        $resultado = array_merge($resultado, $observacoesIA);
                    }
                }
            }
        }
        
        // Fallback para resultado na sessão (diagnóstico recém criado)
        if (!$resultado) {
            $resultado = Session::get('diagnostico_resultado');
            $dadosForm = Session::get('diagnostico_dados');
            
            // Limpar sessão após uso
            Session::remove('diagnostico_resultado');
            Session::remove('diagnostico_dados');
        }
        
        // Se ainda não houver resultado, mostrar empty state
        if (!$resultado) {
            Flash::set('erro', 'Resultado do diagnóstico não encontrado.');
            header('Location: ' . APP_URL . '/diagnostico');
            exit;
        }

        $dados = [
            'resultado' => $resultado,
            'dadosForm' => $dadosForm,
        ];

        require VIEW_PATH . '/diagnostico/resultado.php';
    }

    /**
     * Montar dados completos dos 5 blocos
     */
    private function montarDadosCompletos(array $rascunho): array
    {
        $dados = [];
        
        // Copiar todos os campos do rascunho (exceto metadados)
        $camposExcluir = ['id', 'empresa_id', 'usuario_id', 'bloco_atual', 'status', 'criado_em', 'atualizado_em'];
        
        foreach ($rascunho as $campo => $valor) {
            if (!in_array($campo, $camposExcluir) && $valor !== null) {
                // Decodificar JSON se necessário
                if (in_array($campo, ['departamentos', 'ferramentas_gestao', 'areas_vulneraveis'])) {
                    $dados[$campo] = json_decode($valor, true) ?? [];
                } else {
                    $dados[$campo] = $valor;
                }
            }
        }
        
        return $dados;
    }

    /**
     * Verificar se APIs estão configuradas
     */
    private function apiConfigurada(): bool
    {
        $openaiKey = Configuracao::buscar('api_openai_key');
        return !empty($openaiKey);
    }

    /**
     * Chamar OpenAI para análise completa
     */
    private function chamarOpenAIAnalise(array $dados, string $contextoDocumentos = ''): ?array
    {
        try {
            $prompt = ApiHelper::buildPromptDiagnostico($dados);
            
            if (!empty($contextoDocumentos)) {
                $prompt .= "\n\nDOCUMENTOS DA EMPRESA:\n" . $contextoDocumentos . "\n\nIMPORTANTE: Use as informações dos documentos para enriquecer sua análise e tornar as recomendações mais específicas e aderentes à realidade da empresa.";
            }
            
            $response = ApiHelper::chamarAnalise($prompt, true);
            
            if ($response['sucesso'] && is_array($response['conteudo'])) {
                return $response['conteudo'];
            }
            
        } catch (Exception $e) {
            Logger::error('Erro na análise IA do diagnóstico: ' . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Montar prompt para análise da IA
     */
    private function montarPromptAnalise(array $dados, string $contextoDocumentos = ''): string
    {
        $empresa = $dados['empresa_nome'] ?? 'Empresa';
        $setor = $dados['setor'] ?? 'Não informado';
        
        return "Analise o diagnóstico empresarial completo da empresa '{$empresa}' do setor '{$setor}' e retorne um JSON estruturado com:

**DADOS DA EMPRESA:**
- Nome: {$empresa}
- Setor: {$setor}
- Colaboradores: " . ($dados['colaboradores_internos'] ?? 0) . "
- Faturamento: " . ($dados['faturamento_mensal'] ?? 'Não informado') . "
- Processos documentados: " . ($dados['processos_documentados'] ?? 0) . "%
- Departamentos: " . implode(', ', $dados['departamentos'] ?? []) . "

**OPERAÇÃO ATUAL:**
- Processo de entrega: " . ($dados['processo_entrega'] ?? 'Não informado') . "
- Ferramentas: " . ($dados['ferramentas_softwares'] ?? 'Não informado') . "
- Dependências críticas: " . ($dados['dependencia_pessoa'] ?? 'Não informado') . "

**RISCOS IDENTIFICADOS:**
- Problemas: " . ($dados['problemas_operacionais'] ?? 'Nenhum reportado') . "
- Cliente concentrado: " . ($dados['cliente_concentrado'] ?? 'nao') . "
- Fornecedor insubstituível: " . ($dados['fornecedor_insubstituivel'] ?? 'nao') . "
- Processos sem backup: " . ($dados['processos_sem_backup'] ?? 'nao') . "

**OBJETIVOS:**
- Meta 12 meses: " . ($dados['objetivo_12_meses'] ?? 'Não definido') . "
- Pontos fortes: " . ($dados['pontos_fortes'] ?? 'Não informado') . "
- Pontos de melhoria: " . ($dados['pontos_melhoria'] ?? 'Não informado') . "

{$contextoDocumentos}

Com base nas informações do diagnóstico" . (!empty($contextoDocumentos) ? " e nos documentos internos da empresa" : "") . ", forneça:

Retorne JSON exatamente neste formato:
{
  \"score_maturidade\": 1-4,
  \"analise_por_area\": [
    {\"area\": \"Estratégia\", \"status\": \"adequado|atenção|crítico\", \"comentario\": \"análise detalhada\"},
    {\"area\": \"Operações\", \"status\": \"adequado|atenção|crítico\", \"comentario\": \"análise detalhada\"},
    {\"area\": \"Financeiro\", \"status\": \"adequado|atenção|crítico\", \"comentario\": \"análise detalhada\"},
    {\"area\": \"Pessoas\", \"status\": \"adequado|atenção|crítico\", \"comentario\": \"análise detalhada\"},
    {\"area\": \"Riscos\", \"status\": \"adequado|atenção|crítico\", \"comentario\": \"análise detalhada\"}
  ],
  \"mapa_de_riscos\": [
    {\"tipo\": \"categoria\", \"descricao\": \"descrição do risco\", \"criticidade\": \"alta|media|baixa\", \"acao\": \"ação recomendada\"}
  ],
  \"recomendacoes\": [\"recomendação 1\", \"recomendação 2\", \"recomendação 3\"],
  \"sops_urgentes\": [\"SOP 1 necessário\", \"SOP 2 necessário\"],
  \"sites_referencia\": [
    {\"url\": \"https://site1.com\", \"categoria\": \"Notícias do setor\"},
    {\"url\": \"https://site2.com\", \"categoria\": \"Referências técnicas\"}
  ]
}" . (!empty($contextoDocumentos) ? "

IMPORTANTE: Use as informações dos documentos internos para personalizar completamente a análise, adaptando as recomendações ao que já existe na empresa e identificando gaps específicos." : "");
    }

    /**
     * Calcula o score de maturidade (1-4)
     */
    private function calcularScore(array $dados): int
    {
        $pontos = 0;
        $maxPontos = 35; // Aumentado para incluir novos critérios

        // 1. Processos documentados (0-100%)
        $pontos += match(true) {
            ($dados['processos_documentados'] ?? 0) >= 75 => 5,
            ($dados['processos_documentados'] ?? 0) >= 50 => 4,
            ($dados['processos_documentados'] ?? 0) >= 25 => 3,
            ($dados['processos_documentados'] ?? 0) >= 10 => 2,
            default => 1,
        };

        // 2. Departamentos estruturados
        $numDepts = is_array($dados['departamentos'] ?? null) ? count($dados['departamentos']) : 0;
        $pontos += match(true) {
            $numDepts >= 6 => 4,
            $numDepts >= 4 => 3,
            $numDepts >= 2 => 2,
            default => 1,
        };

        // 3. Planejamento estratégico
        $pontos += ($dados['planejamento_documentado'] ?? 'nao') === 'sim' ? 4 : 1;

        // 4. Maturidade percebida
        $pontos += match(true) {
            ($dados['maturidade_percebida'] ?? 1) >= 4 => 4,
            ($dados['maturidade_percebida'] ?? 1) >= 3 => 3,
            ($dados['maturidade_percebida'] ?? 1) >= 2 => 2,
            default => 1,
        };

        // 5. Estrutura financeira (novo - bloco 3)
        $pontoFinanceiro = 0;
        if (($dados['sistema_financeiro'] ?? '') === 'erp-completo') $pontoFinanceiro += 2;
        elseif (($dados['sistema_financeiro'] ?? '') === 'sistema-basico') $pontoFinanceiro += 1;
        
        if (($dados['controle_fluxo_caixa'] ?? '') === 'diario') $pontoFinanceiro += 2;
        elseif (($dados['controle_fluxo_caixa'] ?? '') === 'semanal') $pontoFinanceiro += 1;
        
        if (in_array($dados['margem_lucro'] ?? '', ['11-20', 'acima-20'])) $pontoFinanceiro += 1;
        
        $pontos += min($pontoFinanceiro, 4); // Max 4 pontos para financeiro

        // 6. Estrutura comercial (novo - bloco 3)
        $pontoComercial = 0;
        if (($dados['sistema_crm'] ?? '') === 'crm-profissional') $pontoComercial += 2;
        elseif (($dados['sistema_crm'] ?? '') === 'planilhas') $pontoComercial += 1;
        
        if (in_array($dados['taxa_conversao'] ?? '', ['16-30', 'acima-30'])) $pontoComercial += 2;
        elseif (($dados['taxa_conversao'] ?? '') === '5-15') $pontoComercial += 1;
        
        $pontos += min($pontoComercial, 4); // Max 4 pontos para comercial

        // 7. Gestão de pessoas (novo - bloco 4)
        $pontoPessoas = 0;
        if (in_array($dados['estrutura_organizacional'] ?? '', ['organograma-formal', 'organograma-basico'])) $pontoPessoas += 2;
        
        if (($dados['programa_capacitacao'] ?? '') === 'programa-formal') $pontoPessoas += 2;
        elseif (($dados['programa_capacitacao'] ?? '') === 'treinamentos-esporadicos') $pontoPessoas += 1;
        
        if (in_array($dados['taxa_turnover'] ?? '', ['muito-baixa', 'baixa'])) $pontoPessoas += 1;
        
        $pontos += min($pontoPessoas, 4); // Max 4 pontos para pessoas

        // 8. Gestão de riscos (novo - bloco 4)
        $pontoRiscos = 0;
        if (in_array($dados['mapeamento_riscos'] ?? '', ['matriz-formal', 'listagem-basica'])) $pontoRiscos += 2;
        
        if (($dados['backup_continuidade'] ?? '') === 'plano-formal') $pontoRiscos += 2;
        elseif (($dados['backup_continuidade'] ?? '') === 'backup-automatico') $pontoRiscos += 1;
        
        if (in_array($dados['conformidade_regulatoria'] ?? '', ['totalmente-conforme', 'conforme-basico'])) $pontoRiscos += 1;
        
        $pontos += min($pontoRiscos, 4); // Max 4 pontos para riscos

        // 9. Dependências críticas (penalização - bloco 4)
        $penalizacao = 0;
        if (($dados['dependencia_pessoas'] ?? '') === 'totalmente-dependente') $penalizacao += 2;
        elseif (($dados['dependencia_pessoas'] ?? '') === 'algumas-pessoas') $penalizacao += 1;
        
        if (in_array($dados['dependencia_fornecedores'] ?? '', ['um-fornecedor-critico', 'cliente-concentrado'])) $penalizacao += 1;
        
        $pontos = max(1, $pontos - $penalizacao); // Não permitir pontuação menor que 1

        // 10. Riscos operacionais tradicionais (revisado)
        $temRiscosOperacionais = (
            ($dados['processos_sem_backup'] ?? 'nao') === 'sim' ||
            ($dados['fornecedor_insubstituivel'] ?? 'nao') === 'sim' ||
            ($dados['cliente_concentrado'] ?? 'nao') === 'sim'
        );
        $pontos += $temRiscosOperacionais ? 1 : 3;

        // Calcular nível final (1-4)
        $percentual = ($pontos / $maxPontos) * 100;
        return match(true) {
            $percentual >= 85 => 4, // Excelência
            $percentual >= 65 => 3, // Crescimento
            $percentual >= 40 => 2, // Desenvolvimento 
            default => 1,          // Inicial
        };
    }

    /**
     * Gera o resultado completo do diagnóstico
     */
    private function gerarResultado(array $dados, int $score): array
    {
        $niveis = [
            1 => ['label' => 'Inicial', 'cor' => '#CC2222', 'descricao' => 'A empresa está no estágio inicial de organização. Processos dependem de pessoas e não há padronização.'],
            2 => ['label' => 'Desenvolvimento', 'cor' => '#f59e0b', 'descricao' => 'A empresa está desenvolvendo processos. Há alguma documentação mas falta consistência.'],
            3 => ['label' => 'Crescimento', 'cor' => '#1a7a1a', 'descricao' => 'A empresa possui processos definidos e está em fase de crescimento estruturado.'],
            4 => ['label' => 'Excelência', 'cor' => '#1E3A5F', 'descricao' => 'A empresa opera com excelência. Processos são otimizados e mensurados continuamente.'],
        ];

        // Analisar todas as 5 áreas com dados dos novos blocos
        $areas = [
            // Estratégia (Bloco 1)
            [
                'area' => 'Estratégia', 
                'status' => ($dados['planejamento_documentado'] ?? 'nao') === 'sim' ? 'adequado' : 'atenção', 
                'comentario' => ($dados['planejamento_documentado'] ?? 'nao') === 'sim' 
                    ? 'Planejamento documentado e objetivos claros.' 
                    : 'Necessita formalizar planejamento estratégico com metas mensuráveis.'
            ],
            
            // Operações (Bloco 2)
            [
                'area' => 'Operações', 
                'status' => ($dados['processos_documentados'] ?? 0) >= 50 ? 'adequado' : 'crítico', 
                'comentario' => ($dados['processos_documentados'] ?? 0) . '% dos processos documentados. ' . 
                    (($dados['processos_documentados'] ?? 0) < 50 
                        ? 'Urgente: mapear e documentar processos críticos.' 
                        : 'Manter evolução na documentação e padronização.')
            ],
            
            // Financeiro (Bloco 3 - novo)
            [
                'area' => 'Financeiro', 
                'status' => $this->analisarStatusFinanceiro($dados),
                'comentario' => $this->gerarComentarioFinanceiro($dados)
            ],
            
            // Comercial (Bloco 3 - novo)
            [
                'area' => 'Comercial', 
                'status' => $this->analisarStatusComercial($dados),
                'comentario' => $this->gerarComentarioComercial($dados)
            ],
            
            // Pessoas (Bloco 4 - novo)
            [
                'area' => 'Pessoas', 
                'status' => $this->analisarStatusPessoas($dados),
                'comentario' => $this->gerarComentarioPessoas($dados)
            ],
            
            // Riscos (Bloco 4 - expandido)
            [
                'area' => 'Riscos', 
                'status' => $this->analisarStatusRiscos($dados),
                'comentario' => $this->gerarComentarioRiscos($dados)
            ]
        ];

        // Mapa de riscos expandido com dados dos novos blocos
        $riscos = [];
        
        // Riscos operacionais
        if (($dados['processos_sem_backup'] ?? 'nao') === 'sim') {
            $riscos[] = [
                'tipo' => 'Operacional', 
                'descricao' => 'Processos críticos sem backup de conhecimento', 
                'criticidade' => 'alta', 
                'acao' => 'Documentar SOPs e treinar equipe de backup para processos críticos'
            ];
        }
        
        // Riscos financeiros (bloco 3)
        if (($dados['margem_lucro'] ?? '') === 'negativa') {
            $riscos[] = [
                'tipo' => 'Financeiro', 
                'descricao' => 'Empresa operando com prejuízo', 
                'criticidade' => 'crítica', 
                'acao' => 'Análise urgente de custos e revisão do modelo de negócio'
            ];
        }
        
        if (($dados['controle_fluxo_caixa'] ?? '') === 'nao-tem') {
            $riscos[] = [
                'tipo' => 'Financeiro', 
                'descricao' => 'Ausência de controle de fluxo de caixa', 
                'criticidade' => 'alta', 
                'acao' => 'Implementar controle diário de fluxo de caixa'
            ];
        }
        
        // Riscos comerciais (bloco 3)
        if (($dados['sistema_crm'] ?? '') === 'nao-tem') {
            $riscos[] = [
                'tipo' => 'Comercial', 
                'descricao' => 'Falta de controle de leads e oportunidades', 
                'criticidade' => 'média', 
                'acao' => 'Implementar sistema básico de CRM para controlar pipeline de vendas'
            ];
        }
        
        if (($dados['taxa_conversao'] ?? '') === 'nao-sei') {
            $riscos[] = [
                'tipo' => 'Comercial', 
                'descricao' => 'Taxa de conversão desconhecida', 
                'criticidade' => 'média', 
                'acao' => 'Implementar medição de conversão para otimizar vendas'
            ];
        }
        
        // Riscos de pessoas (bloco 4)
        if (($dados['dependencia_pessoas'] ?? '') === 'totalmente-dependente') {
            $riscos[] = [
                'tipo' => 'Pessoas', 
                'descricao' => 'Dependência total do proprietário/sócio', 
                'criticidade' => 'crítica', 
                'acao' => 'Distribuir conhecimento, documentar processos e desenvolver lideranças'
            ];
        }
        
        if (in_array($dados['taxa_turnover'] ?? '', ['alta', 'muito-alta'])) {
            $riscos[] = [
                'tipo' => 'Pessoas', 
                'descricao' => 'Alta rotatividade de colaboradores', 
                'criticidade' => 'alta', 
                'acao' => 'Investigar causas do turnover e implementar programa de retenção'
            ];
        }
        
        // Riscos de continuidade (bloco 4)
        if (($dados['backup_continuidade'] ?? '') === 'nao-tem') {
            $riscos[] = [
                'tipo' => 'Continuidade', 
                'descricao' => 'Ausência de backup e plano de continuidade', 
                'criticidade' => 'alta', 
                'acao' => 'Implementar backup automático e plano básico de continuidade'
            ];
        }
        
        // Riscos de fornecedores/clientes
        if (($dados['dependencia_fornecedores'] ?? '') === 'um-fornecedor-critico') {
            $riscos[] = [
                'tipo' => 'Fornecimento', 
                'descricao' => 'Dependência crítica de um fornecedor (>80%)', 
                'criticidade' => 'alta', 
                'acao' => 'Identificar e homologar fornecedores alternativos'
            ];
        }
        
        if (($dados['dependencia_fornecedores'] ?? '') === 'cliente-concentrado') {
            $riscos[] = [
                'tipo' => 'Comercial', 
                'descricao' => 'Concentração de receita em poucos clientes', 
                'criticidade' => 'média', 
                'acao' => 'Diversificar carteira de clientes e reduzir dependência'
            ];
        }
        
        // Riscos de conformidade (bloco 4)
        if (in_array($dados['conformidade_regulatoria'] ?? '', ['muitas-pendencias', 'nao-sei'])) {
            $riscos[] = [
                'tipo' => 'Regulatório', 
                'descricao' => 'Pendências ou desconhecimento de exigências regulatórias', 
                'criticidade' => 'alta', 
                'acao' => 'Auditoria regulatória e regularização das pendências'
            ];
        }

        return [
            'score' => $score,
            'nivel' => $niveis[$score],
            'pontuacao_percentual' => round(($score / 4) * 100),
            'areas' => $areas,
            'riscos' => $riscos,
            'empresa' => $dados['empresa_nome'] ?? 'Empresa',
            'total_riscos_criticos' => count(array_filter($riscos, fn($r) => $r['criticidade'] === 'crítica')),
            'total_riscos_altos' => count(array_filter($riscos, fn($r) => $r['criticidade'] === 'alta')),
            'recomendacoes_prioritarias' => $this->gerarRecomendacoesPrioritarias($dados, $riscos)
        ];
    }
    
    private function analisarStatusFinanceiro(array $dados): string
    {
        if (($dados['margem_lucro'] ?? '') === 'negativa') return 'crítico';
        if (($dados['controle_fluxo_caixa'] ?? '') === 'nao-tem') return 'crítico';
        if (($dados['sistema_financeiro'] ?? '') === 'nao-tem') return 'atenção';
        if (($dados['controle_fluxo_caixa'] ?? '') === 'diario' && ($dados['sistema_financeiro'] ?? '') !== 'planilhas') return 'adequado';
        return 'atenção';
    }
    
    private function gerarComentarioFinanceiro(array $dados): string
    {
        $comentarios = [];
        
        if (($dados['margem_lucro'] ?? '') === 'negativa') {
            $comentarios[] = "CRÍTICO: Empresa operando com prejuízo";
        } elseif (in_array($dados['margem_lucro'] ?? '', ['11-20', 'acima-20'])) {
            $comentarios[] = "Margem de lucro saudável";
        }
        
        if (($dados['controle_fluxo_caixa'] ?? '') === 'diario') {
            $comentarios[] = "Controle de fluxo de caixa em dia";
        } elseif (($dados['controle_fluxo_caixa'] ?? '') === 'nao-tem') {
            $comentarios[] = "URGENTE: Implementar controle de fluxo de caixa";
        }
        
        if (($dados['sistema_financeiro'] ?? '') === 'erp-completo') {
            $comentarios[] = "ERP robusto para gestão financeira";
        }
        
        return !empty($comentarios) ? implode('. ', $comentarios) . '.' : 'Estrutura financeira básica implementada.';
    }
    
    private function analisarStatusComercial(array $dados): string
    {
        if (($dados['sistema_crm'] ?? '') === 'nao-tem' && ($dados['taxa_conversao'] ?? '') === 'nao-sei') return 'crítico';
        if (($dados['sistema_crm'] ?? '') === 'crm-profissional' && in_array($dados['taxa_conversao'] ?? '', ['16-30', 'acima-30'])) return 'adequado';
        return 'atenção';
    }
    
    private function gerarComentarioComercial(array $dados): string
    {
        $comentarios = [];
        
        if (($dados['sistema_crm'] ?? '') === 'crm-profissional') {
            $comentarios[] = "CRM profissional implementado";
        } elseif (($dados['sistema_crm'] ?? '') === 'nao-tem') {
            $comentarios[] = "URGENTE: Implementar sistema de controle de leads";
        }
        
        if (($dados['taxa_conversao'] ?? '') === 'nao-sei') {
            $comentarios[] = "Implementar medição de conversão";
        } elseif (in_array($dados['taxa_conversao'] ?? '', ['16-30', 'acima-30'])) {
            $comentarios[] = "Taxa de conversão adequada";
        }
        
        $canais = explode(',', $dados['canais_vendas'] ?? '');
        if (count($canais) >= 3) {
            $comentarios[] = "Boa diversificação de canais de venda";
        }
        
        return !empty($comentarios) ? implode('. ', $comentarios) . '.' : 'Estrutura comercial em desenvolvimento.';
    }
    
    private function analisarStatusPessoas(array $dados): string
    {
        if (($dados['dependencia_pessoas'] ?? '') === 'totalmente-dependente') return 'crítico';
        if (in_array($dados['taxa_turnover'] ?? '', ['alta', 'muito-alta'])) return 'crítico';
        if (($dados['programa_capacitacao'] ?? '') === 'programa-formal' && ($dados['estrutura_organizacional'] ?? '') === 'organograma-formal') return 'adequado';
        return 'atenção';
    }
    
    private function gerarComentarioPessoas(array $dados): string
    {
        $comentarios = [];
        
        if (($dados['dependencia_pessoas'] ?? '') === 'totalmente-dependente') {
            $comentarios[] = "CRÍTICO: Dependência total do proprietário";
        } elseif (($dados['dependencia_pessoas'] ?? '') === 'processos-documentados') {
            $comentarios[] = "Conhecimento bem distribuído na equipe";
        }
        
        if (($dados['estrutura_organizacional'] ?? '') === 'organograma-formal') {
            $comentarios[] = "Organograma formal implementado";
        }
        
        if (($dados['programa_capacitacao'] ?? '') === 'programa-formal') {
            $comentarios[] = "Programa estruturado de capacitação";
        }
        
        if (in_array($dados['taxa_turnover'] ?? '', ['muito-baixa', 'baixa'])) {
            $comentarios[] = "Baixa rotatividade - boa retenção";
        } elseif (in_array($dados['taxa_turnover'] ?? '', ['alta', 'muito-alta'])) {
            $comentarios[] = "ATENÇÃO: Alta rotatividade de pessoal";
        }
        
        return !empty($comentarios) ? implode('. ', $comentários) . '.' : 'Gestão de pessoas em estruturação.';
    }
    
    private function analisarStatusRiscos(array $dados): string
    {
        $riscosAltos = 0;
        
        if (($dados['mapeamento_riscos'] ?? '') === 'nao-tem') $riscosAltos++;
        if (($dados['backup_continuidade'] ?? '') === 'nao-tem') $riscosAltos++;
        if (in_array($dados['conformidade_regulatoria'] ?? '', ['muitas-pendencias', 'nao-sei'])) $riscosAltos++;
        if (($dados['dependencia_fornecedores'] ?? '') === 'um-fornecedor-critico') $riscosAltos++;
        
        if ($riscosAltos >= 3) return 'crítico';
        if ($riscosAltos >= 1) return 'atenção';
        return 'adequado';
    }
    
    private function gerarComentarioRiscos(array $dados): string
    {
        $comentarios = [];
        
        if (($dados['mapeamento_riscos'] ?? '') === 'matriz-formal') {
            $comentarios[] = "Matriz de riscos formalizada";
        } elseif (($dados['mapeamento_riscos'] ?? '') === 'nao-tem') {
            $comentarios[] = "URGENTE: Mapear riscos do negócio";
        }
        
        if (($dados['backup_continuidade'] ?? '') === 'plano-formal') {
            $comentarios[] = "Plano de continuidade testado";
        } elseif (($dados['backup_continuidade'] ?? '') === 'nao-tem') {
            $comentarios[] = "Implementar backup e continuidade";
        }
        
        $seguros = explode(',', $dados['seguros'] ?? '');
        if (in_array('nenhum', $seguros)) {
            $comentarios[] = "Considerar seguros básicos";
        } elseif (count($seguros) >= 2) {
            $comentarios[] = "Boa cobertura de seguros";
        }
        
        return !empty($comentarios) ? implode('. ', $comentarios) . '.' : 'Gestão de riscos em desenvolvimento.';
    }
    
    private function gerarRecomendacoesPrioritarias(array $dados, array $riscos): array
    {
        $recomendacoes = [];
        
        // Prioridade 1: Riscos críticos
        foreach ($riscos as $risco) {
            if ($risco['criticidade'] === 'crítica') {
                $recomendacoes[] = [
                    'prioridade' => 1,
                    'area' => $risco['tipo'],
                    'acao' => $risco['acao'],
                    'prazo' => 'Imediato (0-30 dias)'
                ];
            }
        }
        
        // Prioridade 2: Documentação de processos
        if (($dados['processos_documentados'] ?? 0) < 50) {
            $recomendacoes[] = [
                'prioridade' => 2,
                'area' => 'Operacional',
                'acao' => 'Documentar os 5 processos mais críticos da empresa',
                'prazo' => 'Curto prazo (30-90 dias)'
            ];
        }
        
        // Prioridade 3: Estrutura básica
        if (($dados['planejamento_documentado'] ?? 'nao') === 'nao') {
            $recomendacoes[] = [
                'prioridade' => 3,
                'area' => 'Estratégica',
                'acao' => 'Criar planejamento estratégico com metas claras',
                'prazo' => 'Médio prazo (90-180 dias)'
            ];
        }
        
        return $recomendacoes;
    }

    /**
     * Retorna as opções para selects/multi-selects do wizard
     */
    private function getOpcoesWizard(): array
    {
        return [
            'setores' => [
                'Tecnologia', 'Varejo', 'Serviços', 'Saúde', 'Construção',
                'Educação', 'Financeiro', 'Indústria', 'Logística', 'Costura/Moda',
                'Alimentação', 'Jurídico', 'Imobiliário', 'Outro',
            ],
            'linguas' => ['Português', 'Inglês', 'Espanhol'],
            'departamentos' => [
                'Comercial', 'Marketing', 'Financeiro', 'RH', 'TI', 'Jurídico',
                'Operações', 'Atendimento', 'Projetos', 'Produção', 'Logística', 'Diretoria',
            ],
            'faturamento' => [
                'Até R$ 50 mil', 'R$ 50 mil - R$ 100 mil', 'R$ 100 mil - R$ 300 mil',
                'R$ 300 mil - R$ 500 mil', 'R$ 500 mil - R$ 1 milhão', 'Acima de R$ 1 milhão',
            ],
            'ferramentas_gestao' => [
                'ERP', 'CRM', 'Trello/Asana/Monday', 'Slack/Teams', 'Google Workspace',
                'Notion', 'Power BI', 'Excel/Planilhas', 'Sistema próprio', 'Nenhum',
            ],
            'areas_vulneraveis' => [
                'Comercial', 'Financeiro', 'Operações', 'TI/Sistemas', 'Jurídico',
                'Logística', 'RH/Pessoas', 'Marketing', 'Atendimento', 'Produção',
            ],
            'tempo_existencia' => [
                'Menos de 1 ano', '1 a 3 anos', '3 a 5 anos', '5 a 10 anos', 'Mais de 10 anos',
            ],
            'estrutura_societaria' => [
                'MEI', 'ME', 'EPP', 'Ltda', 'S/A', 'Eireli', 'SLU',
            ],
            'frequencia_reunioes' => [
                'Diária', 'Semanal', 'Quinzenal', 'Mensal', 'Esporádica', 'Não há',
            ],
        ];
    }
}
