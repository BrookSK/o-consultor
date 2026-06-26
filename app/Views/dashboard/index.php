<?php
/**
 * Fallback — Redireciona para o dashboard do perfil correspondente.
 * Este arquivo não deveria ser chamado diretamente.
 * O DashboardController renderiza admin.php, consultor.php ou cliente.php.
 */
header('Location: ' . APP_URL . '/dashboard');
exit;
