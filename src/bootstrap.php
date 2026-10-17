<?php
declare(strict_types=1);

/*
 * Loaded at the top of every page. Sets up config, errors, the session,
 * security headers and the helper libraries.
 */

define('ROOT_DIR', dirname(__DIR__));

require ROOT_DIR . '/src/env.php';
if (!load_env(ROOT_DIR . '/.env') && env('DB_NAME') === null) {
    // Fail with a clear message instead of a confusing database error.
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit("Site not configured: the .env file was not found or can't be read.\n"
        . "Expected it next to the src/ and public/ folders (in the same folder as README.md).\n"
        . "Copy .env.example to .env and fill it in. See README.md.\n");
}

$isProduction = env('APP_ENV', 'local') === 'production';

error_reporting(E_ALL);
ini_set('display_errors', $isProduction ? '0' : '1');
ini_set('log_errors', '1');
ini_set('error_log', ROOT_DIR . '/storage/logs/php-error.log');
date_default_timezone_set('Africa/Lagos');

require ROOT_DIR . '/src/helpers.php';
require ROOT_DIR . '/src/db.php';
require ROOT_DIR . '/src/csrf.php';
require ROOT_DIR . '/src/auth.php';
require ROOT_DIR . '/src/paystack.php';
require ROOT_DIR . '/src/payments.php';

if (PHP_SAPI !== 'cli' && !defined('NO_SESSION')) {
    send_security_headers();
    start_secure_session($isProduction);
}
