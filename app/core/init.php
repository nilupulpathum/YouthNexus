<?php

require 'config.php';
require 'Database.php';
require 'Model.php';
require 'Controller.php';
require 'App.php';

// Auto-load all model files
foreach (glob("../app/models/*.php") as $modelFile) {
    require_once $modelFile;
}

// Load PHPMailer library (Fix #8: single loading point, no manual requires in controllers)
foreach (glob("../app/core/phpmailer/*.php") as $mailerFile) {
    require_once $mailerFile;
}