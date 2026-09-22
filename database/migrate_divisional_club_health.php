<?php
/**
 * Additive migration for reusable club-health scoring and review flags.
 * Run after the event, attendance, finance, and divisional audit migrations.
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

    $required = [
        'Club',
        'User',
        'Event',
        'Attendance',
        'Ledger',
        'LedgerEntry',
        'Audit',
        'RedFlag',
        'Notification',
        'AuditLog',
    ];
    $check = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    foreach ($required as $table) {
        $check->execute([$table]);
        if ((int) $check->fetchColumn() === 0) {
            throw new RuntimeException("Required table `$table` does not exist. Run its base migration first.");
        }
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS `ClubHealthSnapshot` (
        `snapshot_id` INT AUTO_INCREMENT PRIMARY KEY,
        `club_id` INT NOT NULL,
        `score_month` DATE NOT NULL,
        `window_start` DATE NOT NULL,
        `window_end` DATE NOT NULL,
        `event_score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        `finance_score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        `attendance_score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        `overall_score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        `health_status` ENUM('Green','Yellow','Red') NOT NULL,
        `completed_events` INT NOT NULL DEFAULT 0,
        `attendance_present` INT NOT NULL DEFAULT 0,
        `attendance_recorded` INT NOT NULL DEFAULT 0,
        `approved_entries` INT NOT NULL DEFAULT 0,
        `expense_entries` INT NOT NULL DEFAULT 0,
        `receipted_expenses` INT NOT NULL DEFAULT 0,
        `reconciled_entries` INT NOT NULL DEFAULT 0,
        `calculated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT `fk_health_snapshot_club` FOREIGN KEY (`club_id`) REFERENCES `Club` (`club_id`) ON DELETE CASCADE,
        UNIQUE KEY `uq_health_snapshot_month` (`club_id`, `score_month`),
        INDEX `idx_health_snapshot_status` (`score_month`, `health_status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table `ClubHealthSnapshot` ready.\n";

    $pdo->exec("CREATE TABLE IF NOT EXISTS `ClubHealthFlag` (
        `health_flag_id` INT AUTO_INCREMENT PRIMARY KEY,
        `club_id` INT NOT NULL,
        `flag_category` ENUM('AutomaticDormancy','FinancialConcern','EventAttendanceConcern','GovernanceConcern') NOT NULL,
        `source` ENUM('System','Manual') NOT NULL DEFAULT 'Manual',
        `reason` TEXT NOT NULL,
        `status` ENUM('Open','UnderReview','Resolved','Dismissed') NOT NULL DEFAULT 'Open',
        `raised_by` INT NULL,
        `raised_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `resolved_by` INT NULL,
        `resolved_at` TIMESTAMP NULL DEFAULT NULL,
        `resolution_notes` TEXT NULL,
        CONSTRAINT `fk_health_flag_club` FOREIGN KEY (`club_id`) REFERENCES `Club` (`club_id`) ON DELETE CASCADE,
        CONSTRAINT `fk_health_flag_raised_by` FOREIGN KEY (`raised_by`) REFERENCES `User` (`user_id`) ON DELETE RESTRICT,
        CONSTRAINT `fk_health_flag_resolved_by` FOREIGN KEY (`resolved_by`) REFERENCES `User` (`user_id`) ON DELETE RESTRICT,
        INDEX `idx_health_flag_club_status` (`club_id`, `status`),
        INDEX `idx_health_flag_category` (`flag_category`, `status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table `ClubHealthFlag` ready.\n";

    echo "\n=== Divisional Club Health Migration Completed Successfully ===\n";
} catch (Throwable $exception) {
    die('Migration error: ' . $exception->getMessage() . "\n");
}
