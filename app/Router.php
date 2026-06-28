<?php
/**
 * Router — Mapeamento de URLs para Controllers
 * O Consultor — Sistema Operacional Empresarial
 */

class Router
{
    private array $rotasGet = [];
    private array $rotasPost = [];

    public function __construct()
    {
        $this->registrarRotas();
    }

    /**
     * Registra todas as rotas da aplicação
     */
    private function registrarRotas(): void
    {
        // Autenticação
        $this->get('login', 'AuthController', 'showLogin');
        $this->post('login', 'AuthController', 'login');
        $this->get('cadastro', 'AuthController', 'showCadastro');
        $this->post('cadastro', 'AuthController', 'cadastro');
        $this->get('recuperar-senha', 'AuthController', 'showRecuperarSenha');
        $this->post('recuperar-senha', 'AuthController', 'recuperarSenha');
        $this->get('redefinir-senha', 'AuthController', 'showRedefinirSenha');
        $this->post('redefinir-senha', 'AuthController', 'redefinirSenha');
        $this->get('logout', 'AuthController', 'logout');

        // Dashboard
        $this->get('', 'DashboardController', 'index');
        $this->get('dashboard', 'DashboardController', 'index');

        // Diagnóstico
        $this->get('diagnostico', 'DiagnosticoController', 'index');
        $this->get('diagnostico/novo', 'DiagnosticoController', 'novo');
        $this->get('diagnostico/bloco', 'DiagnosticoController', 'bloco');
        $this->post('diagnostico/salvar-bloco', 'DiagnosticoController', 'salvarBloco');
        $this->post('diagnostico/upload-documentos', 'DiagnosticoController', 'uploadDocumentos');
        $this->post('diagnostico/gerar', 'DiagnosticoController', 'gerar');
        $this->post('diagnostico/salvar', 'DiagnosticoController', 'salvar');
        $this->get('diagnostico/resultado', 'DiagnosticoController', 'resultado');

        // Plano de Ação
        $this->get('plano-de-acao', 'PlanoController', 'index');
        $this->get('plano-de-acao/novo', 'PlanoController', 'novo');
        $this->post('plano-de-acao/salvar-step1', 'PlanoController', 'salvarStep1');
        $this->get('plano-de-acao/prioridades', 'PlanoController', 'prioridades');
        $this->post('plano-de-acao/confirmar-prioridades', 'PlanoController', 'confirmarPrioridades');
        $this->get('plano-de-acao/tarefas', 'PlanoController', 'tarefas');
        $this->post('plano-de-acao/salvar-tarefas', 'PlanoController', 'salvarTarefas');
        $this->get('plano-de-acao/ver', 'PlanoController', 'ver');
        $this->post('plano-de-acao/mover-tarefa', 'PlanoController', 'moverTarefa');
        $this->post('plano-de-acao/reuniao', 'PlanoController', 'registrarReuniao');
        
        // F-12: Acionamento de Parceiros via Plano
        $this->post('plano/acionar-parceiro', 'PlanoController', 'acionarParceiro');
        $this->get('plano/listar-parceiros', 'PlanoController', 'listarParceiros');
        $this->get('plano/status-solicitacao-parceiro', 'PlanoController', 'statusSolicitacaoParceiro');
        
        // Plano legado
        $this->post('plano-de-acao/salvar', 'PlanoController', 'salvar');
        $this->post('plano-de-acao/tarefa-status', 'PlanoController', 'atualizarTarefaStatus');

        // Manual Operacional (SOP)
        $this->get('manual-operacional', 'SopController', 'index');
        $this->get('sop/gerenciar', 'SopController', 'gerenciarSOPs');
        $this->post('sop/adicionar', 'SopController', 'adicionarSOP');
        $this->post('sop/remover', 'SopController', 'removerSOP');
        $this->post('sop/gerar', 'SopController', 'gerar');
        $this->get('sop/ver/{id}', 'SopController', 'ver');
        $this->get('sop/revisar', 'SopController', 'revisar');
        $this->post('sop/aprovar', 'SopController', 'aprovar');
        $this->post('sop/ajustar', 'SopController', 'ajustar');
        $this->post('sop/salvar-rascunho', 'SopController', 'salvarRascunho');
        $this->get('sop/contencao/{id}', 'SopController', 'contencao');
        $this->post('contencao/acionar', 'SopController', 'acionarContencao');
        $this->get('sop/exportar-pdf/{id}', 'SopController', 'exportarPdf');
        $this->get('sop/exportar-todos-zip', 'SopController', 'exportarTodosZip');
        $this->get('manual-operacional/raci', 'SopController', 'raci');
        $this->get('sop/raci-funcao', 'SopController', 'getRaciFuncao');
        $this->get('manual-operacional/kpis', 'KpiController', 'index');

        // KPI Management (F-07)
        $this->get('kpis/ver', 'KpiController', 'ver');
        $this->post('kpis/registrar', 'KpiController', 'registrar');
        $this->post('kpis/alerta/marcar-lido', 'KpiController', 'marcarAlertaLido');

        // Central de Conteúdo
        $this->get('central-de-conteudo', 'ConteudoController', 'index');
        $this->get('central-de-conteudo/noticia', 'ConteudoController', 'noticiaDetalhe');
        $this->get('central-de-conteudo/caso', 'ConteudoController', 'casoDetalhe');
        $this->post('central-de-conteudo/perfil-busca', 'ConteudoController', 'salvarPerfilBusca');
        $this->post('central-de-conteudo/buscar-agora', 'ConteudoController', 'buscarAgora');
        $this->post('central-de-conteudo/criar-conteudo', 'ConteudoController', 'criarConteudoDeNoticia');
        $this->get('central-de-conteudo/admin', 'ConteudoController', 'admin');

        // Sistema de Notícias por IA (F-09)
        $this->get('noticias', 'NoticiasController', 'index');
        $this->get('noticias/admin', 'NoticiasController', 'admin');
        $this->get('noticias/detalhe', 'NoticiasController', 'detalhe');
        $this->post('noticias/buscar-agora', 'NoticiasController', 'buscarAgora');
        $this->post('noticias/gerar-analise', 'NoticiasController', 'gerarAnalise');
        $this->post('noticias/favoritar', 'NoticiasController', 'favoritar');
        $this->post('noticias/arquivar', 'NoticiasController', 'arquivar');
        $this->post('noticias/busca-global', 'NoticiasController', 'executarBuscaGlobal');
        $this->get('noticias/perfil', 'NoticiasController', 'perfil');
        $this->post('noticias/salvar-perfil', 'NoticiasController', 'salvarPerfil');
        $this->get('noticias/buscar', 'NoticiasController', 'buscar');
        $this->post('noticias/inicializar-perfil', 'NoticiasController', 'inicializarPerfil');
        $this->post('noticias/adicionar-site', 'NoticiasController', 'adicionarSite');
        $this->post('noticias/remover-site', 'NoticiasController', 'removerSite');

        // Academy SSO
        $this->get('academy/sso', 'AcademyController', 'sso');
        $this->get('academy/logs', 'AcademyController', 'logs');
        $this->post('academy/desvincular', 'PerfilController', 'desvincularAcademy');

        // Máquina de Conteúdo (F-10 + F-11)
        $this->get('maquina-de-conteudo', 'MaquinaController', 'index');
        $this->get('maquina-de-conteudo/marca', 'MaquinaController', 'marca');
        $this->get('maquina-de-conteudo/nova-marca', 'MaquinaController', 'novaMarca');
        $this->post('maquina-de-conteudo/salvar-marca', 'MaquinaController', 'salvarMarca');
        $this->post('maquina/gerar', 'MaquinaController', 'gerar');
        $this->get('maquina-de-conteudo/editar', 'MaquinaController', 'editar');
        $this->post('maquina-de-conteudo/aprovar', 'MaquinaController', 'aprovar');
        $this->post('maquina-de-conteudo/regenerar-imagem', 'MaquinaController', 'regenerarImagem');
        $this->post('maquina-de-conteudo/upload-imagem', 'MaquinaController', 'uploadImagem');
        $this->post('maquina-de-conteudo/atualizar-slide', 'MaquinaController', 'atualizarSlide');
        $this->post('maquina-de-conteudo/upload-template', 'MaquinaController', 'uploadTemplate');
        $this->get('maquina-de-conteudo/templates', 'MaquinaController', 'listarTemplates');
        $this->post('maquina-de-conteudo/remover-template', 'MaquinaController', 'removerTemplate');
        
        // F-11: Publicação e Agendamento
        $this->post('maquina/agendar', 'MaquinaController', 'agendar');
        $this->get('maquina/download', 'MaquinaController', 'download');
        $this->post('maquina/marcar-publicado', 'MaquinaController', 'marcarPublicado');
        $this->get('maquina/calendario', 'MaquinaController', 'calendario');

        // Parceiros
        $this->get('parceiros', 'ParceirosController', 'index');
        $this->get('parceiros/perfil', 'ParceirosController', 'perfil');
        $this->post('parceiros/solicitar', 'ParceirosController', 'solicitar');
        $this->get('parceiros/admin', 'ParceirosController', 'admin');
        $this->post('parceiros/status', 'ParceirosController', 'atualizarStatus');
        
        // F-12: Admin de Solicitações de Parceiros
        $this->get('parceiros/solicitacoes', 'ParceirosController', 'solicitacoes');
        $this->post('parceiros/atualizar-status-solicitacao', 'ParceirosController', 'atualizarStatusSolicitacao');

        // Governança
        $this->get('governanca', 'GovernancaController', 'index');
        $this->post('governanca/reuniao', 'GovernancaController', 'salvarReuniao');
        $this->post('governanca/auditoria', 'GovernancaController', 'registrarAuditoria');

        // Admin
        $this->get('admin', 'AdminController', 'index');
        $this->get('admin/usuarios', 'AdminController', 'usuarios');
        $this->post('admin/usuarios/salvar', 'AdminController', 'salvarUsuario');
        $this->get('admin/clientes', 'AdminController', 'clientes');
        
        // F-13: Gestão de Clientes
        $this->get('admin/clientes/novo', 'AdminController', 'novoCliente');
        $this->post('admin/clientes/criar', 'AdminController', 'criarCliente');
        $this->get('admin/clientes/perfil', 'AdminController', 'perfilCliente');
        $this->post('admin/clientes/trocar-consultor', 'AdminController', 'trocarConsultor');
        $this->post('admin/clientes/alterar-status', 'AdminController', 'alterarStatusCliente');
        
        $this->get('admin/configuracoes', 'AdminController', 'configuracoes');
        $this->post('admin/testar-apis', 'AdminController', 'testarApis');
        $this->post('admin/testar-academy', 'AdminController', 'testarAcademy');
        $this->post('admin/testar-smtp', 'AdminController', 'testarSmtp');
        $this->post('admin/configuracoes/salvar', 'AdminController', 'salvarConfiguracoes');
        
        // F-14: Configuração de APIs de IA
        $this->post('admin/api/toggle', 'AdminController', 'toggleApi');
        $this->post('admin/api/salvar-chave', 'AdminController', 'salvarChaveApi');
        $this->post('admin/api/testar', 'AdminController', 'testarApiIndividual');
        $this->post('admin/smtp/salvar', 'AdminController', 'salvarSmtp');
        $this->post('admin/smtp/testar', 'AdminController', 'testarSmtp');
        $this->get('admin/logs', 'AdminController', 'logs');
        $this->get('admin/relatorios', 'AdminController', 'relatorios');
        $this->post('admin/selecionar-empresa', 'AdminController', 'selecionarEmpresa');
        
        // CRUD Usuários
        $this->get('admin/empresas/listar', 'AdminController', 'listarEmpresas');
        $this->get('admin/usuarios/{id}', 'AdminController', 'visualizarUsuario');
        $this->post('admin/usuarios/criar', 'AdminController', 'criarUsuario');
        $this->post('admin/usuarios/atualizar', 'AdminController', 'atualizarUsuario');
        $this->post('admin/usuarios/alterar-status', 'AdminController', 'alterarStatusUsuario');

        // Perfil
        $this->get('perfil', 'PerfilController', 'index');
        $this->post('perfil/salvar', 'PerfilController', 'salvar');
        $this->post('perfil/vincular-academy', 'PerfilController', 'vincularAcademy');
        $this->post('perfil/alterar-senha', 'PerfilController', 'alterarSenha');

        // Onboarding
        $this->get('onboarding', 'PerfilController', 'onboarding');
        $this->post('onboarding/step1', 'PerfilController', 'salvarStep');
        $this->post('onboarding/step2', 'PerfilController', 'salvarStep');
        $this->post('onboarding/step3', 'PerfilController', 'salvarStep');
        $this->post('onboarding/step4', 'PerfilController', 'salvarStep');
        $this->post('onboarding/salvar-step', 'PerfilController', 'salvarStep');
        $this->post('onboarding/concluir', 'PerfilController', 'concluirOnboarding');
        $this->post('onboarding/vincular-academy', 'PerfilController', 'vincularAcademy');

        // Alertas e Notificações
        $this->get('alertas', 'AlertaController', 'index');
        $this->post('alertas/marcar-lido', 'AlertaController', 'marcarLido');
        $this->post('alertas/resolver', 'AlertaController', 'resolver');
        $this->get('alertas/recentes', 'AlertaController', 'recentes');
        $this->post('alertas/marcar-todos-lidos', 'AlertaController', 'marcarTodosLidos');
        $this->post('alertas/preferencias', 'AlertaController', 'salvarPreferencias');

        // API Interna
        $this->post('api/transcricao', 'ApiController', 'transcricao');
    }

    /**
     * Registra uma rota GET
     */
    private function get(string $rota, string $controller, string $action): void
    {
        $this->rotasGet[$rota] = ['controller' => $controller, 'action' => $action];
    }

    /**
     * Registra uma rota POST
     */
    private function post(string $rota, string $controller, string $action): void
    {
        $this->rotasPost[$rota] = ['controller' => $controller, 'action' => $action];
    }

    /**
     * Despacha a requisição para o controller e action corretos
     */
    public function despachar(string $url): void
    {
        $metodo = $_SERVER['REQUEST_METHOD'];

        // Tratar rotas dinâmicas específicas
        if (preg_match('/^diagnostico\/resultado\/(\d+)$/', $url)) {
            $this->executarAction('DiagnosticoController', 'resultado');
            return;
        }
        
        if (preg_match('/^diagnostico\/bloco\/(\d+)$/', $url)) {
            $this->executarAction('DiagnosticoController', 'bloco');
            return;
        }
        
        if (preg_match('/^plano-de-acao\/prioridades\/(\d+)$/', $url)) {
            $this->executarAction('PlanoController', 'prioridades');
            return;
        }
        
        if (preg_match('/^plano-de-acao\/tarefas\/(\d+)$/', $url)) {
            $this->executarAction('PlanoController', 'tarefas');
            return;
        }
        
        if (preg_match('/^plano-de-acao\/(\d+)$/', $url)) {
            $this->executarAction('PlanoController', 'show');
            return;
        }

        // F-09: Detalhes de notícia dinâmico
        if (preg_match('/^noticias\/detalhe\/(\d+)$/', $url, $matches)) {
            $_GET['id'] = (int) $matches[1];
            $this->executarAction('NoticiasController', 'detalhe');
            return;
        }

        // F-13: Cliente perfil dinâmico
        if (preg_match('/^admin\/clientes\/perfil\/(\d+)$/', $url, $matches)) {
            $_GET['id'] = (int) $matches[1];
            $this->executarAction('AdminController', 'perfilCliente');
            return;
        }

        // CRUD Usuários - visualizar usuário
        if (preg_match('/^admin\/usuarios\/(\d+)$/', $url, $matches)) {
            $_GET['id'] = (int) $matches[1];
            $this->executarAction('AdminController', 'visualizarUsuario');
            return;
        }

        if (preg_match('/^maquina-de-conteudo\/editar\/(\d+)$/', $url, $matches)) {
            $_GET['id'] = (int) $matches[1];
            $this->executarAction('MaquinaController', 'editar');
            return;
        }

        if (preg_match('/^maquina\/download\/(\d+)$/', $url, $matches)) {
            $_GET['id'] = (int) $matches[1];
            $this->executarAction('MaquinaController', 'download');
            return;
        }

        if (preg_match('/^maquina\/marcar-publicado\/(\d+)$/', $url, $matches)) {
            $_POST['conteudo_id'] = (int) $matches[1];
            $this->executarAction('MaquinaController', 'marcarPublicado');
            return;
        }

        if ($metodo === 'GET' && isset($this->rotasGet[$url])) {
            $rota = $this->rotasGet[$url];
        } elseif ($metodo === 'POST' && isset($this->rotasPost[$url])) {
            $rota = $this->rotasPost[$url];
        } else {
            $this->erro404();
            return;
        }

        $this->executarAction($rota['controller'], $rota['action']);
    }

    /**
     * Executa uma action específica
     */
    private function executarAction(string $controllerName, string $actionName): void
    {
        $controllerFile = APP_PATH . '/Controllers/' . $controllerName . '.php';

        if (!file_exists($controllerFile)) {
            $this->erro404();
            return;
        }

        require_once $controllerFile;

        if (!class_exists($controllerName)) {
            $this->erro404();
            return;
        }

        $controller = new $controllerName();

        if (!method_exists($controller, $actionName)) {
            $this->erro404();
            return;
        }

        $controller->$actionName();
    }

    /**
     * Exibe página de erro 404
     */
    private function erro404(): void
    {
        http_response_code(404);
        if (file_exists(VIEW_PATH . '/errors/404.php')) {
            require VIEW_PATH . '/errors/404.php';
        } else {
            echo '<h1>404 — Página não encontrada</h1>';
            echo '<p>A página que você procura não existe.</p>';
            echo '<a href="' . APP_URL . '/login">Voltar ao início</a>';
        }
    }
}
