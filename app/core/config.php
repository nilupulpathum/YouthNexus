<?php

if ($_SERVER['SERVER_NAME'] == 'localhost') {
    $port = $_SERVER['SERVER_PORT'] ?? '80';
    if (php_sapi_name() === 'cli-server') {
        define('ROOT', 'http://localhost:' . $port);
    } else {
        define('ROOT', 'http://localhost/YouthNexus/YouthNexus/public');
    }
} else {
    define('ROOT', 'https://websitename.com');
}

date_default_timezone_set('Asia/Colombo');

define('APP_ROOT', dirname(dirname(__FILE__))); // Points to /app

define('DB_HOST', 'localhost');
define('DB_NAME', 'youthnexus');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// PHPMailer SMTP credentials
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_USER', 'damikarajithuru@gmail.com');
define('MAIL_PASS', 'kkidjhebomlvtsvy');
define('MAIL_FROM', 'noreply@youthnexus.com');
define('MAIL_FROM_NAME', 'YouthNexus Pulse');