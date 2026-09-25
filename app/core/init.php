<?php

require 'config.php';
require 'functions.php';
require 'Database.php';
require 'Model.php';
require 'Controller.php';
require 'FinanceReceipt.php';
require 'EventEvidence.php';
require 'ClubOverview.php';
require 'CsvSecurity.php';
require 'App.php';

// Auto-load all model files
foreach (glob("../app/models/*.php") as $modelFile) {
    require_once $modelFile;
}
