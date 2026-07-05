<?php
/**
 * Worker CLI para geração de SOP em background REAL.
 * Executado via exec() desanexado pelo SopController::processarServicoCompleto().
 *
 * Uso: php worker/gerar_sop.php <sop_id> <servico_id>
 *
 * Roda completamente fora do ciclo web (sem proxy/nginx/Apache),
 * portanto não sofre com timeout de 60s.
 */

// Definir constantes de caminho (equivalente ao public/index.php)
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('VIEW_PATH', APP_PATH . '/Views');

date_default_timezone_set('America/Sao_Paulo');

// Sem limite de tempo para o worker
@set_time_limit(0);
@ignore_user_abort(true);

// Carregar configurações
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/config/api_keys.php';

// Autoload
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

// Helpers e models essenciais
require_once APP_PATH . '/Helpers/Logger.php';
require_once APP_PATH . '/Models/Database.php';
require_once APP_PATH . '/Models/Configuracao.php';

// Ler argumentos
$sopId = (int) ($argv[1] ?? 0);
$servicoId = (int) ($argv[2] ?? 0);

if (!$sopId || !$servicoId) {
    Logger::error('WORKER: argumentos inválidos', ['argv' => $argv]);
    exit(1);
}

Logger::info('WORKER INICIADO', ['sop_id' => $sopId, 'servico_id' => $servicoId]);

try {
    // Buscar serviço
    $servico = Database::queryOne(
        "SELECT ss.*, se.nome_setor, se.tipo_setor, eo.nicho, eo.diagnostico_id
         FROM servicos_setor ss
         LEFT JOIN setores_empresa se ON ss.setor_id = se.id
         LEFT JOIN estruturas_organizacionais eo ON se.estrutura_id = eo.id
         WHERE ss.id = :id",
        ['id' => $servicoId]
    );

    if (!$servico) {
        Logger::error('WORKER: serviço não encontrado', ['servico_id' => $servicoId]);
        exit(1);
    }

    // Instanciar o controller e executar a geração
    $controller = new SopController();
    $controller->executarGeracaoSopBackgroundPublic($sopId, $servicoId, $servico);

    Logger::info('WORKER CONCLUÍDO', ['sop_id' => $sopId]);
    exit(0);

} catch (Throwable $e) {
    Logger::error('WORKER ERRO FATAL', [
        'erro' => $e->getMessage(),
        'sop_id' => $sopId,
        'trace' => $e->getTraceAsString()
    ]);
    exit(1);
}
