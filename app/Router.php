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
        $this->get('logout', 'AuthController', 'logout');

        // Dashboard
        $this->get('', 'DashboardController', 'index');
        $this->get('dashboard', 'DashboardController', 'index');

        // Diagnóstico
        $this->get('diagnostico', 'DiagnosticoController', 'index');
        $this->get('diagnostico/novo', 'DiagnosticoController', 'novo');
        $this->post('diagnostico/salvar', 'DiagnosticoController', 'salvar');
        $this->get('diagnostico/resultado', 'DiagnosticoController', 'resultado');

        // Plano de Ação
        $this->get('plano-de-acao', 'PlanoController', 'index');
        $this->get('plano-de-acao/novo', 'PlanoController', 'novo');
        $this->post('plano-de-acao/salvar', 'PlanoController', 'salvar');
        $this->get('plano-de-acao/ver', 'PlanoController', 'ver');
        $this->post('plano-de-acao/tarefa-status', 'PlanoController', 'atualizarTarefaStatus');
        $this->post('plano-de-acao/reuniao', 'PlanoController', 'registrarReuniao');

        // Manual Operacional (SOP)
        $this->get('manual-operacional', 'SopController', 'index');
        $this->post('sop/gerar', 'SopController', 'gerar');
        $this->get('sop/revisar', 'SopController', 'revisar');
        $this->post('sop/aprovar', 'SopController', 'aprovar');
        $this->post('sop/salvar-rascunho', 'SopController', 'salvarRascunho');
        $this->get('manual-operacional/raci', 'SopController', 'raci');
        $this->get('manual-operacional/kpis', 'SopController', 'kpis');
        $this->post('manual-operacional/kpi-registrar', 'SopController', 'registrarKpi');

        // Central de Conteúdo
        $this->get('central-de-conteudo', 'ConteudoController', 'index');
        $this->get('central-de-conteudo/noticia', 'ConteudoController', 'noticiaDetalhe');
        $this->get('central-de-conteudo/caso', 'ConteudoController', 'casoDetalhe');
        $this->post('central-de-conteudo/perfil-busca', 'ConteudoController', 'salvarPerfilBusca');
        $this->post('central-de-conteudo/buscar-agora', 'ConteudoController', 'buscarAgora');
        $this->get('central-de-conteudo/admin', 'ConteudoController', 'admin');

        // Academy SSO
        $this->get('academy/sso', 'AcademyController', 'sso');

        // Máquina de Conteúdo
        $this->get('maquina-de-conteudo', 'MaquinaController', 'index');
        $this->get('maquina-de-conteudo/marca', 'MaquinaController', 'marca');
        $this->get('maquina-de-conteudo/nova-marca', 'MaquinaController', 'novaMarca');
        $this->post('maquina-de-conteudo/salvar-marca', 'MaquinaController', 'salvarMarca');
        $this->post('maquina/gerar', 'MaquinaController', 'gerar');
        $this->get('maquina-de-conteudo/editar', 'MaquinaController', 'editar');
        $this->post('maquina-de-conteudo/aprovar', 'MaquinaController', 'aprovar');
        $this->post('maquina-de-conteudo/regenerar-imagem', 'MaquinaController', 'regenerarImagem');
        $this->post('maquina-de-conteudo/upload-template', 'MaquinaController', 'uploadTemplate');
        $this->get('maquina-de-conteudo/templates', 'MaquinaController', 'listarTemplates');
        $this->post('maquina-de-conteudo/remover-template', 'MaquinaController', 'removerTemplate');

        // Parceiros
        $this->get('parceiros', 'ParceirosController', 'index');
        $this->get('parceiros/perfil', 'ParceirosController', 'perfil');
        $this->post('parceiros/solicitar', 'ParceirosController', 'solicitar');
        $this->get('parceiros/admin', 'ParceirosController', 'admin');
        $this->post('parceiros/status', 'ParceirosController', 'atualizarStatus');

        // Governança
        $this->get('governanca', 'GovernancaController', 'index');
        $this->post('governanca/reuniao', 'GovernancaController', 'salvarReuniao');
        $this->post('governanca/auditoria', 'GovernancaController', 'registrarAuditoria');

        // Admin
        $this->get('admin', 'AdminController', 'index');
        $this->get('admin/usuarios', 'AdminController', 'usuarios');
        $this->post('admin/usuarios/salvar', 'AdminController', 'salvarUsuario');
        $this->get('admin/clientes', 'AdminController', 'clientes');
        $this->get('admin/configuracoes', 'AdminController', 'configuracoes');
        $this->post('admin/testar-apis', 'AdminController', 'testarApis');
        $this->post('admin/testar-academy', 'AdminController', 'testarAcademy');
        $this->post('admin/testar-smtp', 'AdminController', 'testarSmtp');
        $this->post('admin/configuracoes/salvar', 'AdminController', 'salvarConfiguracoes');
        $this->get('admin/logs', 'AdminController', 'logs');
        $this->get('admin/relatorios', 'AdminController', 'relatorios');

        // Perfil
        $this->get('perfil', 'PerfilController', 'index');
        $this->post('perfil/salvar', 'PerfilController', 'salvar');
        $this->post('perfil/vincular-academy', 'PerfilController', 'vincularAcademy');
        $this->post('perfil/alterar-senha', 'PerfilController', 'alterarSenha');

        // Onboarding
        $this->get('onboarding', 'PerfilController', 'onboarding');
        $this->post('onboarding/concluir', 'PerfilController', 'concluirOnboarding');

        // Alertas e Notificações
        $this->get('alertas', 'AlertaController', 'index');
        $this->post('alertas/marcar-lido', 'AlertaController', 'marcarLido');
        $this->post('alertas/resolver', 'AlertaController', 'resolver');
        $this->get('alertas/recentes', 'AlertaController', 'recentes');
        $this->post('alertas/preferencias', 'AlertaController', 'salvarPreferencias');
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

        if ($metodo === 'GET' && isset($this->rotasGet[$url])) {
            $rota = $this->rotasGet[$url];
        } elseif ($metodo === 'POST' && isset($this->rotasPost[$url])) {
            $rota = $this->rotasPost[$url];
        } else {
            $this->erro404();
            return;
        }

        $controllerName = $rota['controller'];
        $actionName = $rota['action'];

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
