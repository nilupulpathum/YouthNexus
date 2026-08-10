<?php 

if($_SERVER['SERVER_NAME'] == 'localhost'){
    // Detect if running via PHP built-in server (document root is public/)
    // vs Apache/XAMPP (document root is htdocs/, needs full path)
    $port = $_SERVER['SERVER_PORT'] ?? '80';
    if(php_sapi_name() === 'cli-server'){
        // PHP built-in server: document root is already public/
        define('ROOT', 'http://localhost:' . $port);
    }else{
        // Apache/XAMPP: need full path to public directory
        define('ROOT', 'http://localhost:' . $port . '/YouthNexus/public');
    }
}else{
    define('ROOT', 'https://websitename.com');
}

define('DB_HOST', 'localhost');
define('DB_NAME', 'yovun_saviya');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');