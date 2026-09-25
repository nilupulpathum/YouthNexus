<?php
/**
 * Migration Script: Divisional Treasurer - Request Void Workflow
 *
 * Extends VoidRequest with the Withdrawn state used when a Divisional
 * Treasurer cancels a request before Zonal review. Safe to run repeatedly.
 *
 * Usage: c:\xampp\php\php.exe database\migrate_divisional_void_request.php
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

    $tableCheck = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?"
    );
    foreach (['VoidRequest', 'Notification'] as $table) {
        $tableCheck->execute([$table]);
        if ((int) $tableCheck->fetchColumn() === 0) {
            throw new RuntimeException(
                "Required table `$table` does not exist. Run the Divisional Treasurer and audit migrations first."
            );
        }
    }

    $pdo->exec(
        "ALTER TABLE `VoidRequest`
         MODIFY COLUMN `status`
         ENUM('Pending','Approved','Rejected','Withdrawn')
         NOT NULL DEFAULT 'Pending'"
    );
    echo "VoidRequest.status supports Withdrawn.\n";

    $indexCheck = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'VoidRequest' AND INDEX_NAME = ?"
    );
    $indexCheck->execute(['idx_direction_status']);
    if ((int) $indexCheck->fetchColumn() === 0) {
        $pdo->exec(
            "ALTER TABLE `VoidRequest`
             ADD INDEX `idx_direction_status` (`scope_direction`, `status`)"
        );
        echo "Added VoidRequest direction/status index.\n";
    } else {
        echo "VoidRequest direction/status index already present.\n";
    }

    echo "\n=== Divisional Void Request Migration Completed Successfully ===\n";
} catch (Throwable $exception) {
    die('Migration error: ' . $exception->getMessage() . "\n");
}
