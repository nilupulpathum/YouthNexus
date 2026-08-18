<?php
/**
 * Router for PHP built-in development server.
 * This file is only needed when using `php -S` command.
 * Apache with .htaccess handles routing automatically.
 * 
 * Usage: cd d:\Projects\YouthNexus
 *        C:\xampp\php\php.exe -S localhost:8080 -t public router.php
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Serve static files directly (CSS, JS, images, etc.)
$staticFile = $_SERVER['DOCUMENT_ROOT'] . $uri;
if ($uri !== '/' && is_file($staticFile)) {
    return false;
}

// Route all other requests through the MVC front controller
$trimmedUri = trim($uri, '/');
if ($trimmedUri !== '') {
    $_GET['url'] = $trimmedUri;
}

// Change to public dir so relative require paths work (../app/core/init.php)
chdir($_SERVER['DOCUMENT_ROOT']);

require $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'index.php';
