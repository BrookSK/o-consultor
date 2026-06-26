<?php
/**
 * Configurações fixas da aplicação (infraestrutura)
 * O Consultor — Sistema Operacional Empresarial
 *
 * SOMENTE configurações de infraestrutura PHP ficam aqui.
 * Tudo relacionado a APIs, Academy, chaves, etc. fica no banco
 * e é gerenciado pela tela /admin/configuracoes.
 */

// URL base (ajustar conforme ambiente)
define('APP_URL', 'https://app.oconsultor.digital');

// Ambiente
define('APP_ENV', 'development'); // development | production

// Aplicação
define('APP_NAME', 'O Consultor');
define('APP_VERSION', '1.0.0');
define('APP_TIMEZONE', 'America/Sao_Paulo');

// Chave de criptografia para dados sensíveis no banco (gere uma vez e não altere)
define('APP_KEY', 'oconsultor_2026_chave_criptografia_alterar_em_producao');

// Sessão
define('SESSION_LIFETIME', 7200);
define('SESSION_NAME', 'oconsultor_session');

// Caminhos
define('LOG_PATH', __DIR__ . '/../storage/logs/');
define('UPLOAD_PATH', __DIR__ . '/../public/uploads/');
define('UPLOAD_MAX_SIZE', 10485760); // 10MB
