<?php
/**
 * Worker de COLETA AGENDADA de concorrentes (Scrap da Concorrência).
 *
 * Percorre os concorrentes ativos cuja próxima coleta (proxima_coleta_em) já
 * venceu e executa a coleta + análise. Respeita a frequência configurada em
 * cada concorrente e o isolamento por empresa. Não executa coletas simultâneas
 * do mesmo perfil (garantido em ConcorrenteColeta::executar).
 *
 * Cron sugerido (a cada 15 min):
 *   *\/15 * * * * /usr/bin/php /caminho/worker/coletar_concorrentes.php >> /caminho/worker/concorrentes.log 2>&1
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

// Lock para evitar execuções concorrentes do próprio worker.
$lockFile = ROOT_PATH . '/worker/concorrentes.lock';
$lock = @fopen($lockFile, 'c');
if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
    echo "Worker de concorrentes já em execução.\n";
    exit(0);
}

// Sem chave da ScrapingBee não há o que coletar.
if (!ScrapingBee::configurada()) {
    echo "ScrapingBee não configurada. Nada a coletar.\n";
    flock($lock, LOCK_UN);
    exit(0);
}

Logger::info('WORKER CONCORRENTES INICIADO');
$inicio = time();
$limite = 280; // ~4.5 min por execução
$processados = 0;

try {
    // Perfis ativos, com agendamento vencido. Ordena pelos mais atrasados.
    $vencidos = Database::query(
        "SELECT id, empresa_id FROM concorrentes
         WHERE status = 'ativo'
           AND frequencia_coleta <> 'manual'
           AND proxima_coleta_em IS NOT NULL
           AND proxima_coleta_em <= NOW()
         ORDER BY proxima_coleta_em ASC
         LIMIT 50"
    );

    foreach ($vencidos as $c) {
        if ((time() - $inicio) >= $limite) break;

        $concorrenteId = (int) $c['id'];
        $empresaId = (int) $c['empresa_id'];

        $res = ConcorrenteColeta::executar($concorrenteId, $empresaId, 'agendada');
        if (!empty($res['sucesso'])) {
            // Análise best-effort após a coleta.
            try {
                ConcorrenteAnalise::gerar($concorrenteId, $empresaId, $res['coleta_id'] ?? null);
            } catch (\Throwable $e) {
                Logger::error('Falha na análise agendada', ['concorrente_id' => $concorrenteId, 'erro' => $e->getMessage()]);
            }
        }
        $processados++;
        sleep(1); // respiro entre perfis (rate limit amigável)
    }

    Logger::info('WORKER CONCORRENTES FINALIZADO', ['processados' => $processados]);
} catch (\Throwable $e) {
    Logger::error('Erro no worker de concorrentes: ' . $e->getMessage());
} finally {
    flock($lock, LOCK_UN);
    @fclose($lock);
}

echo "Concorrentes processados: {$processados}\n";
