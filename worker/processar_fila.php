<?php
/**
 * Worker de processamento da FILA de geração de SOPs.
 *
 * Deve ser executado por um CRON JOB (ex: a cada 1 minuto):
 *   * * * * * /usr/bin/php /caminho/para/worker/processar_fila.php >> /caminho/worker/fila.log 2>&1
 *
 * Também pode ser disparado best-effort via exec() após enfileirar um pedido.
 *
 * Processa pedidos da tabela fila_geracao_sop. Cada execução processa
 * fases pendentes até esvaziar a fila ou atingir o limite de tempo.
 * Roda em CLI, sem timeout de proxy web.
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

// POOL DE CONCORRÊNCIA: permitimos até N workers simultâneos (um por SOP do lote),
// cada um com seu próprio slot de lock. O claim atômico da fila (processarProximoDaFila)
// garante que dois workers nunca peguem o MESMO pedido. Assim SOPs de serviços
// diferentes são gerados em paralelo, respeitando a dependência das fases DENTRO
// de cada serviço (fase N só roda após N-1 daquele serviço).
$MAX_WORKERS = 4;
$lockHandle = null;
for ($slot = 0; $slot < $MAX_WORKERS; $slot++) {
    $lockFile = ROOT_PATH . '/worker/fila_' . $slot . '.lock';
    $h = @fopen($lockFile, 'c');
    if ($h !== false && flock($h, LOCK_EX | LOCK_NB)) {
        $lockHandle = $h;
        break;
    }
    if ($h !== false) { fclose($h); }
}
if ($lockHandle === null) {
    // Todos os slots ocupados: já há workers suficientes rodando.
    echo "Pool de workers cheio (" . $MAX_WORKERS . "). Nada a fazer.\n";
    exit(0);
}

Logger::info('WORKER FILA INICIADO');

$controller = new SopController();
$inicio = time();
$limiteSegundos = 240; // processar por no máximo ~4 minutos por execução
$processados = 0;

try {
    while ((time() - $inicio) < $limiteSegundos) {
        $resultado = $controller->processarProximoDaFila();

        if (!empty($resultado['vazio'])) {
            // Fila vazia, encerrar
            break;
        }

        // Se retornou "processando" (outro processo pegou), aguardar e tentar de novo
        if (!empty($resultado['processando'])) {
            sleep(3);
            continue;
        }

        $processados++;

        // Pequena pausa entre fases para não sobrecarregar a API
        usleep(500000); // 0.5s
    }

    Logger::info('WORKER FILA FINALIZADO', ['fases_processadas' => $processados]);
    echo "Worker finalizado. Fases processadas: {$processados}\n";

} catch (Throwable $e) {
    Logger::error('WORKER FILA ERRO FATAL', [
        'erro' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    echo "Erro: " . $e->getMessage() . "\n";
} finally {
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
}

exit(0);
