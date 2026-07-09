<?php
/**
 * Worker de geração de IMAGENS de conteúdo (Máquina de Conteúdo).
 *
 * A geração via gpt-image-1 com imagens de referência leva >60s por imagem,
 * o que estoura o timeout do proxy web. Este worker roda em CLI (sem proxy),
 * processando a fila fila_imagens_conteudo uma imagem por vez.
 *
 * Cron sugerido (a cada 1 min):
 *   * * * * * /usr/bin/php /caminho/worker/processar_imagens.php >> /caminho/worker/imagens.log 2>&1
 *
 * Também é disparado best-effort via exec() após enfileirar imagens.
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('VIEW_PATH', APP_PATH . '/Views');
define('PUBLIC_PATH', ROOT_PATH . '/public');

date_default_timezone_set('America/Sao_Paulo');
@set_time_limit(0);
@ignore_user_abort(true);

require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/config/api_keys.php';

spl_autoload_register(function ($class) {
    foreach ([APP_PATH . '/Controllers/', APP_PATH . '/Models/', APP_PATH . '/Helpers/'] as $dir) {
        $p = $dir . $class . '.php';
        if (file_exists($p)) { require_once $p; return; }
    }
});

require_once APP_PATH . '/Helpers/Logger.php';
require_once APP_PATH . '/Models/Database.php';
require_once APP_PATH . '/Models/Configuracao.php';

// Lock para evitar execuções concorrentes.
$lockFile = ROOT_PATH . '/worker/imagens.lock';
$lock = @fopen($lockFile, 'c');
if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
    echo "Worker de imagens já em execução.\n";
    exit(0);
}

Logger::info('WORKER IMAGENS INICIADO');
$controller = new MaquinaController();
$inicio = time();
$limite = 280; // ~4.5 min por execução
$processadas = 0;

try {
    while ((time() - $inicio) < $limite) {
        $res = $controller->processarProximaImagemFila();
        if (!empty($res['vazio'])) break;   // fila vazia
        $processadas++;
        usleep(300000); // 0.3s entre imagens
    }
    Logger::info('WORKER IMAGENS FINALIZADO', ['processadas' => $processadas]);
    echo "Worker de imagens finalizado. Processadas: {$processadas}\n";
} catch (Throwable $e) {
    Logger::error('WORKER IMAGENS ERRO', ['erro' => $e->getMessage()]);
    echo "Erro: " . $e->getMessage() . "\n";
} finally {
    flock($lock, LOCK_UN);
    fclose($lock);
}

exit(0);
