<?php

if (($_SERVER['SERVER_NAME'] ?? 'localhost') === 'localhost') {
    $port = $_SERVER['SERVER_PORT'] ?? '80';
    if (php_sapi_name() === 'cli-server') {
        define('ROOT', 'http://localhost:' . $port);
    } elseif (php_sapi_name() === 'cli') {
        define('ROOT', 'http://localhost/YouthNexus/YouthNexus/public');
    } else {
        // Match the checkout Apache is serving, including a separate Git worktree.
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/YouthNexus/YouthNexus/public/index.php';
        $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        $portSuffix = $port === '80' ? '' : ':' . (int) $port;
        define('ROOT', 'http://localhost' . $portSuffix . $basePath);
    }
} else {
    define('ROOT', 'https://websitename.com');
}

date_default_timezone_set('Asia/Colombo');

// Bump this value when shared assets change so browsers reload the new UI.
define('ASSET_VERSION', '20260926-divisional-ui-final1');

define('APP_ROOT', dirname(dirname(__FILE__))); // Points to /app

define('DB_HOST', getenv('YN_DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('YN_DB_NAME') ?: 'youthnexus');
define('DB_USER', getenv('YN_DB_USER') ?: 'root');
define('DB_PASS', getenv('YN_DB_PASS') ?: '');
define('DB_CHARSET', getenv('YN_DB_CHARSET') ?: 'utf8mb4');

// PHPMailer SMTP credentials
define('MAIL_HOST', getenv('YN_MAIL_HOST') ?: 'smtp.gmail.com');
define('MAIL_USER', getenv('YN_MAIL_USER') ?: '');
define('MAIL_PASS', getenv('YN_MAIL_PASS') ?: '');
define('MAIL_FROM', getenv('YN_MAIL_FROM') ?: 'noreply@youthnexus.com');
define('MAIL_FROM_NAME', getenv('YN_MAIL_FROM_NAME') ?: 'YouthNexus');
