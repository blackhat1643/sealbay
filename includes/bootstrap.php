<?php
/**
 * Application bootstrap — every public and admin page requires this file first.
 */
declare(strict_types=1);

if (!defined('ST_APP')) {
    define('ST_APP', true);
}
define('ST_ROOT', dirname(__DIR__));
define('ST_STORAGE', ST_ROOT . '/storage');

$GLOBALS['st_config'] = require __DIR__ . '/config.php';

require __DIR__ . '/functions.php';
require __DIR__ . '/db.php';
require __DIR__ . '/security.php';
require __DIR__ . '/content.php';
require __DIR__ . '/seo.php';
require __DIR__ . '/icons.php';
require __DIR__ . '/illustrations.php';
require __DIR__ . '/render.php';
require __DIR__ . '/payments.php';
require __DIR__ . '/shop.php';
require __DIR__ . '/enquiry.php';

date_default_timezone_set((string) config('app.timezone', 'Asia/Kolkata'));
mb_internal_encoding('UTF-8');

error_reporting(E_ALL);
ini_set('display_errors', is_dev() ? '1' : '0');
ini_set('log_errors', '1');
if (is_dir(ST_STORAGE . '/logs') && is_writable(ST_STORAGE . '/logs')) {
    ini_set('error_log', ST_STORAGE . '/logs/php-error.log');
}

// Buffer output so pages can still redirect or start a session after including partials.
ob_start();

if (PHP_SAPI !== 'cli') {
    send_security_headers();
}
