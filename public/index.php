<?php
/**
 * Front Controller — Entrada única da aplicação
 * O Consultor — Sistema Operacional Empresarial
 *
 * Todas as requisições são direcionadas para este arquivo via .htaccess
 */

// Iniciar sessão
session_name('oconsultor_session');
session_start();

// Definir timezone
date_default_timezone_set('America/Sao_Paulo');

// Definir constante do caminho raiz
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('VIEW_PATH', APP_PATH . '/Views');
define('PUBLIC_PATH', __DIR__);

// Carregar configurações
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';
// Nota: config/api_keys.php apenas documenta que configs vêm do banco.
// Todas as chaves de API são gerenciadas em /admin/configuracoes e lidas via Model Configuracao.
require_once ROOT_PATH . '/config/api_keys.php';

// Autoload simples de classes
spl_autoload_register(function ($class) {
    $paths = [
        APP_PATH . '/Controllers/' . $class . '.php',
        APP_PATH . '/Models/' . $class . '.php',
        APP_PATH . '/Helpers/' . $class . '.php',
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Carregar helpers essenciais
require_once APP_PATH . '/Helpers/Session.php';
require_once APP_PATH . '/Helpers/Auth.php';
require_once APP_PATH . '/Helpers/Csrf.php';
require_once APP_PATH . '/Helpers/Flash.php';
require_once APP_PATH . '/Helpers/Logger.php';

// Carregar models essenciais (Database + Configuracao)
require_once APP_PATH . '/Models/Database.php';
require_once APP_PATH . '/Models/Configuracao.php';

// Obter URL da requisição
$url = $_GET['url'] ?? '';
$url = rtrim($url, '/');
$url = filter_var($url, FILTER_SANITIZE_URL);

// Inicializar e despachar o router
require_once APP_PATH . '/Router.php';

$router = new Router();
$router->despachar($url);
