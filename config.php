<?php
/**
 * Secure Configuration for QuizMaster
 * 
 * This file loads environment variables and provides secure configuration settings.
 * Create a .env file in the project root with your database credentials.
 * 
 * Requires PHP 8.3+
 */

// PHP Version Check
if (PHP_VERSION_ID < 80300) {
    $message = sprintf(
        'QuizMaster requires PHP 8.3 or higher. Current version: %s',
        PHP_VERSION
    );
    if (php_sapi_name() === 'cli') {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    } else {
        http_response_code(500);
        die($message);
    }
}

// Load environment variables from .env file if it exists
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Database Configuration
define('DB_HOST', $_ENV['DB_HOST'] ?? 'db.fr-pari1.bengt.wasmernet.com');
define('DB_PORT', (int)($_ENV['DB_PORT'] ?? 10272));
define('DB_USERNAME', $_ENV['DB_USERNAME'] ?? 'a3f228567afe80000576f53a820d');
define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? '');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'dbGFjXeeTfzoBSkj45iZMzKb');

// Application Configuration
define('APP_NAME', 'QuizMaster');
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost');
define('SESSION_NAME', 'quizmaster_session');
define('CSRF_TOKEN_NAME', 'csrf_token');

// Security Configuration
define('PASSWORD_HASH_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_HASH_COST', 12);
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_SECURE', isset($_SERVER['HTTPS'])); // Use secure cookies in HTTPS
define('SESSION_HTTPONLY', true);
define('SESSION_SAMESITE', 'Strict');

// Error Reporting (disable display in production)
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/logs/error.log');

// Create logs directory if it doesn't exist
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}