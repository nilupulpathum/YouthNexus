<?php 

if($_SERVER['SERVER_NAME'] == 'localhost'){
    define('ROOT', 'http://localhost:8080/mvc/public');
}else{
    define('ROOT', 'https://websitename.com');
}

define('DB_HOST', 'localhost');
define('DB_NAME', 'yovun_saviya');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');