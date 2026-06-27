<?php
/**
 * AdminController — Painel Administrativo (ADMIN_HOLDING exclusivo)
 */

class AdminController
{
    private function protegerAdmin(): void
    {
        if (!Auth::check()) { header('Location: ' . APP_URL . '/login'); exit; }
        if (!Auth::isAdmin()) {
            Flash::set('erro', 'Acesso não autorizado.');
            header('Location: ' . APP_URL . '/dashboard');
            exit;
        }
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
        $dados = ['usuarios' => $this->getUsuariosMock()];
        require VIEW_PATH . '/admin/usuarios.php';
    }

    public function salvarUsuario(): void
    {
        $this->protegerAdmin();
        Csrf::verificar();
        Logger::acao('Novo usuário criado', ['email' => $_POST['email'] ?? '']);
        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'mensagem' => 'Usuário criado!']);
        exit;
    }

    public function clientes(): void
    {
        $this->protegerAdmin();
        $dados = ['clientes' => $this->getClientesMock()];
        require VIEW_PATH . '/admin/clientes.php';
    }

    public function configuracoes(): void
    {
        $this->protegerAdmin();
        $dados = [
            'apis' => [
                ['nome' => 'Perplexity', 'chave' => 'pplx-****...a3f2', 'modelo' => 'sonar-pro', 'ativo' => true, 'status' => 'ativa', 'descricao' => 'Busca de notícias e conteúdos da web em tempo real.'],
                ['nome' => 'OpenAI (GPT)', 'chave' => 'sk-****...x9k1', 'modelo' => 'gpt-4o', 'ativo' => true, 'status' => 'ativa', 'descricao' => 'Análise, resumo e geração de conteúdo.'],
                ['nome' => 'Anthropic (Claude)', 'chave' => 'sk-ant-****...m2p7', 'modelo' => 'claude-sonnet-4-20250514', 'ativo' => false, 'status' => 'inativa', 'descricao' => 'Alternativa ao GPT para análise de conteúdo.'],
            ],
            'academy' => ['url' => 'https://myacademy.com.br', 'rota_sso' => '/sso', 'parametro' => 'token', 'status' => 'ativa'],
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

        if (empty($configs)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhum campo para salvar.']);
            exit;
        }

        $salvos = 0;
        $erros = [];

        foreach ($configs as $chave => $valor) {
            $chave = htmlspecialchars(trim($chave));
            $valor = trim($valor);
            $ok = Configuracao::set($chave, $valor, $grupo);
            if ($ok) {
                $salvos++;
            } else {
                $erros[] = $chave;
            }
        }

        // Limpar cache de configurações
        Configuracao::limparCache();

        Logger::acao('Configurações salvas', ['grupo' => $grupo, 'salvos' => $salvos, 'erros' => $erros]);

        if ($salvos > 0 && empty($erros)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => true, 'mensagem' => "{$salvos} configurações salvas com sucesso!"]);
        } elseif (!empty($erros)) {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao salvar. Verifique se a tabela "configuracoes" existe no banco. Execute a migration 002.']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhum campo encontrado para salvar.']);
        }
        exit;
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
            $resultados[] = ['api' => 'OpenAI', 'status' => 'inativa', 'tempo' => '-'];
        }

        // Testar Anthropic
        if (Configuracao::apiAtiva('anthropic')) {
            $resultados[] = array_merge(['api' => 'Anthropic'], ApiHelper::testarConexao('anthropic'));
        } else {
            $resultados[] = ['api' => 'Anthropic', 'status' => 'inativa', 'tempo' => '-'];
        }

        // Testar Perplexity
        if (Configuracao::apiAtiva('perplexity')) {
            $resultados[] = array_merge(['api' => 'Perplexity'], ApiHelper::testarConexao('perplexity'));
        } else {
            $resultados[] = ['api' => 'Perplexity', 'status' => 'inativa', 'tempo' => '-'];
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
            $resultado = Email::enviar(
                $emailTeste,
                'Email de Teste — O Consultor',
                Email::enviarNotificacao($emailTeste, 'Admin', 'Teste de Email', 'Este é um email de teste do sistema O Consultor. Se você está lendo isso, o SMTP está funcionando corretamente!') ? '' : '',
                'Admin',
                'outro'
            );

            // Forma mais simples: enviar direto
            $resultado = Email::enviar(
                $emailTeste,
                'Teste de Email — O Consultor',
                '<div style="font-family:Arial,sans-serif;padding:20px;"><h2 style="color:#1E3A5F;">✓ SMTP Funcionando!</h2><p>Este é um email de teste do sistema O Consultor.</p><p>Se você está lendo isso, a configuração SMTP está correta.</p><p style="color:#666;font-size:12px;">Enviado em: ' . date('d/m/Y H:i:s') . '</p></div>',
                'Admin',
                'outro'
            );

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
}
