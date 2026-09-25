<?php
/** Additive schema and catalog entries for shared divisional reports. */
require_once __DIR__ . '/../app/core/config.php';

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    echo "Connected to database.\n";
    $check = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
    foreach (['User', 'Division'] as $table) {
        $check->execute([$table]);
        if ((int) $check->fetchColumn() === 0) throw new RuntimeException("Required table `$table` does not exist.");
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS `ReportTypeCatalog` (
        `report_type_id` INT AUTO_INCREMENT PRIMARY KEY,
        `category` ENUM('Financial','Assets','Events','Attendance','Club Health','User Interactions') NOT NULL,
        `type_name` VARCHAR(120) NOT NULL, `description` TEXT NULL,
        `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
        UNIQUE KEY `uq_cat_type` (`category`,`type_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `Report` (
        `report_id` INT AUTO_INCREMENT PRIMARY KEY, `report_type_id` INT NULL,
        `scope_level` VARCHAR(50) NOT NULL DEFAULT 'Divisional', `scope_id` INT NULL,
        `date_range_start` DATE NULL, `date_range_end` DATE NULL,
        `format` ENUM('PDF','CSV','OnScreen') NOT NULL DEFAULT 'OnScreen',
        `file_path` VARCHAR(500) NULL, `data_snapshot` LONGTEXT NULL,
        `status` VARCHAR(50) NOT NULL DEFAULT 'Active',
        `generated_by` INT NULL, `generated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_report_scope` (`scope_level`,`scope_id`,`status`),
        INDEX `idx_report_type` (`report_type_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $reportColumns = $pdo->query("SHOW COLUMNS FROM `Report`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('data_snapshot', $reportColumns, true)) {
        $pdo->exec("ALTER TABLE `Report` ADD COLUMN `data_snapshot` LONGTEXT NULL AFTER `file_path`");
        echo "Added immutable data snapshots to Report.\n";
    }
    if (!in_array('archived_at', $reportColumns, true)) {
        $pdo->exec("ALTER TABLE `Report` ADD COLUMN `archived_at` DATETIME NULL AFTER `status`");
        echo "Added archive timestamp to Report.\n";
    }
    if (!in_array('archived_by', $reportColumns, true)) {
        $pdo->exec("ALTER TABLE `Report` ADD COLUMN `archived_by` INT NULL AFTER `archived_at`, ADD INDEX `idx_report_archived_by` (`archived_by`)");
        echo "Added archive actor to Report.\n";
    }
    $seeds = [
        ['User Interactions','Club Registration Status','Club registration applications and decisions within the selected period.',1],
        ['Events','Event Approval Summary','Event approval requests and decisions within the selected period.',1],
        ['Events','Event Status Summary','Divisional and club events grouped by their current status.',2],
        ['Events','Event Attendance Rate','Recorded attendance and attendance rates for events in the division.',3],
        ['Financial','Divisional Financial Summary','Income, expenses, net movement, and reconciliation by category.',1],
        ['Financial','Club Fund Allocation Report','Funds allocated from the division to clubs during the selected period.',2],
        ['Financial','Void Request Activity','Club and divisional void requests and their decisions.',3],
        ['Financial','Club Audit Compliance Report','Club audit completion and unresolved financial findings.',4],
        ['Assets','Asset Transfer Report','Assets transferred from the division store to clubs.',1],
        ['Club Health','Club Health Summary','Latest calculated health score for each club in the selected period.',1],
    ];
    $insert = $pdo->prepare('INSERT INTO ReportTypeCatalog (category,type_name,description,sort_order) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE description=VALUES(description), sort_order=VALUES(sort_order)');
    foreach ($seeds as $seed) $insert->execute($seed);
    echo "Divisional report catalog ready.\n";
    echo "\n=== Divisional Reports Migration Completed Successfully ===\n";
} catch (Throwable $exception) {
    die('Migration error: ' . $exception->getMessage() . "\n");
}
