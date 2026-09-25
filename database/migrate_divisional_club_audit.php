<?php
/**
 * Migration: Divisional Treasurer - Club Finance Audit
 *
 * Creates the shared audit tables when absent and adds the period fields used
 * by recurring club audits. The migration is additive and safe to rerun.
 *
 * Usage: c:\xampp\php\php.exe database\migrate_divisional_club_audit.php
 */

require_once __DIR__ . '/../app/core/config.php';

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    echo "Connected to database.\n";

    $requiredTables = ['Club', 'User', 'Ledger', 'LedgerEntry'];
    $tableCheck = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    foreach ($requiredTables as $table) {
        $tableCheck->execute([$table]);
        if ((int) $tableCheck->fetchColumn() === 0) {
            throw new RuntimeException("Required table `$table` does not exist.");
        }
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS `Audit` (
        `audit_id` INT AUTO_INCREMENT PRIMARY KEY,
        `financial_year` INT NOT NULL,
        `scope_level` ENUM('National','Zonal','Divisional','Club') NOT NULL DEFAULT 'Club',
        `scope_id` INT NULL,
        `opening_balance` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `total_income` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `total_transfers_received` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `total_expenses` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `total_transfers_distributed` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `expected_closing_balance` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `actual_closing_balance` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `math_check_status` ENUM('Passed','Mismatch') NOT NULL DEFAULT 'Passed',
        `receipt_threshold_amount` DECIMAL(15,2) NOT NULL DEFAULT 5000.00,
        `hoarding_margin_pct` DECIMAL(5,2) NOT NULL DEFAULT 80.00,
        `void_rate_threshold_pct` DECIMAL(5,2) NOT NULL DEFAULT 10.00,
        `audit_status` ENUM('Pending','ClarificationRequested','Completed','InProgress','Overdue') NOT NULL DEFAULT 'Pending',
        `initiated_by` INT NULL,
        `initiated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `signed_off_by` INT NULL,
        `signed_off_at` TIMESTAMP NULL DEFAULT NULL,
        `locked` TINYINT(1) NOT NULL DEFAULT 0,
        `audit_type` ENUM('Weekly','BiWeekly','Monthly') NOT NULL DEFAULT 'Monthly',
        `period_start` DATE NULL,
        `period_end` DATE NULL,
        `auditor_notes` TEXT NULL,
        `clarification_reason` VARCHAR(100) NULL,
        INDEX `idx_audit_scope` (`scope_level`, `scope_id`, `financial_year`),
        INDEX `idx_audit_status` (`audit_status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $columns = $pdo->query("SHOW COLUMNS FROM `Audit`")->fetchAll(PDO::FETCH_COLUMN);
    $additions = [
        'audit_type' => "ADD COLUMN `audit_type` ENUM('Weekly','BiWeekly','Monthly') NOT NULL DEFAULT 'Monthly' AFTER `locked`",
        'period_start' => "ADD COLUMN `period_start` DATE NULL AFTER `audit_type`",
        'period_end' => "ADD COLUMN `period_end` DATE NULL AFTER `period_start`",
        'auditor_notes' => "ADD COLUMN `auditor_notes` TEXT NULL AFTER `period_end`",
        'clarification_reason' => "ADD COLUMN `clarification_reason` VARCHAR(100) NULL AFTER `auditor_notes`",
    ];
    foreach ($additions as $column => $definition) {
        if (!in_array($column, $columns, true)) {
            $pdo->exec("ALTER TABLE `Audit` $definition");
            echo "Added `$column` to Audit.\n";
        }
    }
    $pdo->exec("ALTER TABLE `Audit` MODIFY COLUMN `audit_status`
        ENUM('Pending','ClarificationRequested','Completed','InProgress','Overdue')
        NOT NULL DEFAULT 'Pending'");
    echo "Table `Audit` ready.\n";

    $pdo->exec("CREATE TABLE IF NOT EXISTS `RedFlag` (
        `red_flag_id` INT AUTO_INCREMENT PRIMARY KEY,
        `audit_id` INT NOT NULL,
        `entry_id` INT NULL,
        `flag_type` ENUM('MissingReceipt','FundHoarding','HighVoidRate') NOT NULL DEFAULT 'MissingReceipt',
        `description` TEXT NOT NULL,
        `status` ENUM('Open','ClarificationRequested','Resolved','Unresolved') NOT NULL DEFAULT 'Open',
        `flagged_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_red_flag_audit` (`audit_id`),
        INDEX `idx_red_flag_entry` (`entry_id`),
        INDEX `idx_red_flag_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table `RedFlag` ready.\n";

    $pdo->exec("CREATE TABLE IF NOT EXISTS `Notification` (
        `notification_id` INT AUTO_INCREMENT PRIMARY KEY,
        `recipient_id` INT NOT NULL,
        `type` VARCHAR(50) NOT NULL DEFAULT 'General',
        `message` VARCHAR(500) NOT NULL,
        `related_entity_type` VARCHAR(50) DEFAULT NULL,
        `related_entity_id` INT DEFAULT NULL,
        `read_status` BOOLEAN NOT NULL DEFAULT FALSE,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_notification_recipient` (`recipient_id`, `read_status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table `Notification` ready.\n";

    echo "\n=== Divisional Club Audit Migration Completed Successfully ===\n";
} catch (Throwable $exception) {
    die('Migration error: ' . $exception->getMessage() . "\n");
}
