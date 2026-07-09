<?php
/**
 * Worker de processamento da FILA de busca de notícias.
 *
 * Deve ser executado por um CRON JOB (ex: a cada 1 minuto):
 *   * * * * * /usr/bin/php /caminho/para/worker/processar_fila_noticias.php >> /caminho/worker/fila_noticias.log 2>&1
 *
 * Também pode ser disparado best-effort via exec() após enfileirar um pedido
 * (ver NoticiasController::dispararWorkerBuscaNoticias).
 *
 * Processa pedidos da tabela fila_busca_noticias em pequenos passos (1 chamada
 * de IA por vez: 1 busca + 1 análise por notícia), evitando o timeout do proxy
 * web que ocorria ao rodar tudo numa única requisição HTTP.
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('VIEW_PATH', APP_PATH . '/Views');

date_default_timezone_set('America/Sao_Paulo');

@set_time_limit(0);
@ignore_user_abort(true);

require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/config/api_keys.php';

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

require_once APP_PATH . '/Helpers/Logger.php';
require_once APP_PATH . '/Models/Database.php';
require_once APP_PATH . '/Models/Configuracao.php';

// Lock simples para evitar execuções concorrentes
$lockFile = ROOT_PATH . '/worker/fila_noticias.lock';
$lockHandle = @fopen($lockFile, 'c');
if ($lockHandle === false || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
    echo "Outra instância do worker já está em execução.\n";
    exit(0);
}

Logger::info('WORKER FILA NOTICIAS INICIADO');

$controller = new NoticiasController();
$inicio = time();
$limiteSegundos = 240; // processar por no máximo ~4 minutos por execução
$processados = 0;

try {
    while ((time() - $inicio) < $limiteSegundos) {
        $resultado = $controller->processarProximaEtapaBusca();

        if (!empty($resultado['vazio'])) {
            break;
        }

        if (!empty($resultado['processando'])) {
            sleep(3);
            continue;
        }

        $processados++;
        usleep(500000); // 0.5s entre passos, para não sobrecarregar a API
    }

    Logger::info('WORKER FILA NOTICIAS FINALIZADO', ['passos_processados' => $processados]);
    echo "Worker finalizado. Passos processados: {$processados}\n";

} catch (Throwable $e) {
    Logger::error('WORKER FILA NOTICIAS ERRO FATAL', [
        'erro' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    echo "Erro: " . $e->getMessage() . "\n";
} finally {
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
}

exit(0);
