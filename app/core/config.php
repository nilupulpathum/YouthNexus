<?php

if ($_SERVER['SERVER_NAME'] == 'localhost') {
    define('ROOT', 'http://localhost/YouthNexus/YouthNexus/public');
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
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
} else {
    define('MAIL_HOST', 'smtp.gmail.com');
    define('MAIL_USER', 'your-email@gmail.com');
    define('MAIL_PASS', 'your-app-password');
    define('MAIL_FROM', 'noreply@youthnexus.com');
    define('MAIL_FROM_NAME', 'YouthNexus Pulse');
}