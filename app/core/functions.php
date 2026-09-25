<?php 

function show($stuff){
    echo '<pre>';
    print_r($stuff);
    echo '</pre>';
}

if (!function_exists('e')) {
    function e($value) {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}
