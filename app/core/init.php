<?php

// Load local credential overrides (never committed to git)
if (file_exists(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}
require 'config.php';
require 'functions.php';
require 'Database.php';
require 'Model.php';
require 'Controller.php';
require 'FinanceReceipt.php';
require 'EventEvidence.php';
require 'ClubOverview.php';
require 'ZoneOverview.php';
require 'CsvSecurity.php';
require 'App.php';

// Auto-load all model files
foreach (glob("../app/models/*.php") as $modelFile) {
    require_once $modelFile;
}
