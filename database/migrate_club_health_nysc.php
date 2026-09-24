<?php
/**
 * Migration Script: Monitor Club Health (NYSC Administration level)
 *
 * Adds, non-destructively:
 *  - Club.disband_reason / Club.disbanded_at / Club.disbanded_by  (Execute Disband)
 *  - Ensures the Notification table exists (manage_assets variant — the schema
 *    the running code actually writes to: recipient_id / read_status).
 *  - Ensures the ClubHealthScore history table exists (national_analytics).
 *
 * Usage:  php database/migrate_club_health_nysc.php
 */

require_once __DIR__ . '/../app/core/config.php';

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    echo "Connected to database.\n";

    // -------------------------------------------------------------
    // 1. Club columns needed by the disband action (idempotent)
    // -------------------------------------------------------------
    echo "Checking Club table columns...\n";
    $clubCols = $pdo->query("SHOW COLUMNS FROM `Club`")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('disband_reason', $clubCols)) {
        $pdo->exec("ALTER TABLE `Club` ADD COLUMN `disband_reason` VARCHAR(500) NULL AFTER `flagged`");
        echo "Added column `disband_reason` to Club.\n";
    }
    if (!in_array('disbanded_at', $clubCols)) {
        $pdo->exec("ALTER TABLE `Club` ADD COLUMN `disbanded_at` DATETIME NULL AFTER `disband_reason`");
        echo "Added column `disbanded_at` to Club.\n";
    }
    if (!in_array('disbanded_by', $clubCols)) {
        $pdo->exec("ALTER TABLE `Club` ADD COLUMN `disbanded_by` INT NULL AFTER `disbanded_at`");
        echo "Added column `disbanded_by` to Club.\n";
    }

    // -------------------------------------------------------------
    // 2. Ensure Notification table (authoritative schema in use)
    // -------------------------------------------------------------
    echo "Checking / Creating Notification table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS Notification (
        notification_id     INT AUTO_INCREMENT PRIMARY KEY,
        recipient_id        INT NOT NULL,
        type                VARCHAR(50) NOT NULL DEFAULT 'General',
        message             VARCHAR(500) NOT NULL,
        related_entity_type VARCHAR(50) DEFAULT NULL,
        related_entity_id   INT DEFAULT NULL,
        read_status         BOOLEAN NOT NULL DEFAULT FALSE,
        created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (recipient_id) REFERENCES User(user_id) ON DELETE CASCADE,
        INDEX idx_recipient (recipient_id, read_status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table `Notification` ready.\n";

    // -------------------------------------------------------------
    // 3. Ensure ClubHealthScore history table (national analytics)
    // -------------------------------------------------------------
    echo "Checking / Creating ClubHealthScore table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `ClubHealthScore` (
        `score_id`         INT AUTO_INCREMENT PRIMARY KEY,
        `club_id`          INT NOT NULL,
        `calculated_date`  DATE NOT NULL,
        `health_status`    ENUM('Green','Yellow','Red') NOT NULL DEFAULT 'Green',
        `overall_score`    DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        `governance_score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        `activity_score`   DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        `finance_score`    DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        `reporting_score`  DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        FOREIGN KEY (`club_id`) REFERENCES `Club`(`club_id`) ON DELETE CASCADE,
        INDEX idx_club_date (`club_id`, `calculated_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table `ClubHealthScore` ready.\n";

    echo "\nMigration completed successfully.\n";

} catch (PDOException $e) {
    echo "DATABASE ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
