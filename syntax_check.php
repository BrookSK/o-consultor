<?php
/**
 * Simple syntax check for the fixed files
 */

// Test the Flash helper
require_once 'app/Helpers/Flash.php';

// Test basic Flash methods
try {
    session_start();
    Flash::erro('Test error');
    Flash::sucesso('Test success');
    Flash::aviso('Test warning');
    echo "Flash helper works correctly!\n";
} catch (Exception $e) {
    echo "Flash error: " . $e->getMessage() . "\n";
}

// Test the Logger helper
require_once 'app/Helpers/Logger.php';

try {
    Logger::error('Test error');
    Logger::warning('Test warning');
    echo "Logger helper works correctly!\n";
} catch (Exception $e) {
    echo "Logger error: " . $e->getMessage() . "\n";
}

echo "Syntax check completed - no fatal errors detected!\n";