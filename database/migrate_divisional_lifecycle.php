<?php

require_once __DIR__ . '/../app/core/config.php';

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $requiredTables = [
        'Event', 'Announcement', 'LedgerEntry', 'VoidRequest', 'AssetStock',
        'AssetRequest', 'Audit', 'Report', 'ClubApplication',
    ];
    $tableCheck = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    foreach ($requiredTables as $table) {
        $tableCheck->execute([$table]);
        if ((int) $tableCheck->fetchColumn() === 0) {
            throw new RuntimeException("Required table `$table` does not exist.");
        }
    }

    $columns = static function (PDO $connection, string $table): array {
        return $connection->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN);
    };
    $addColumn = static function (PDO $connection, string $table, array &$known, string $column, string $definition): void {
        if (!in_array($column, $known, true)) {
            $connection->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            $known[] = $column;
            echo "Added $table.$column.\n";
        }
    };

    $eventColumns = $columns($pdo, 'Event');
    $pdo->exec("ALTER TABLE `Event` MODIFY COLUMN `status`
        ENUM('Draft','PendingApproval','Withdrawn','Approved','CancellationPending','Cancelled','Rejected','Completed')
        NOT NULL DEFAULT 'PendingApproval'");
    $addColumn($pdo, 'Event', $eventColumns, 'lifecycle_reason', 'TEXT NULL AFTER `rejection_remarks`');
    $addColumn($pdo, 'Event', $eventColumns, 'lifecycle_requested_by', 'INT NULL AFTER `lifecycle_reason`');
    $addColumn($pdo, 'Event', $eventColumns, 'lifecycle_requested_at', 'DATETIME NULL AFTER `lifecycle_requested_by`');
    $addColumn($pdo, 'Event', $eventColumns, 'lifecycle_decided_by', 'INT NULL AFTER `lifecycle_requested_at`');
    $addColumn($pdo, 'Event', $eventColumns, 'lifecycle_decided_at', 'DATETIME NULL AFTER `lifecycle_decided_by`');

    $announcementColumns = $columns($pdo, 'Announcement');
    $pdo->exec("ALTER TABLE `Announcement` MODIFY COLUMN `status`
        ENUM('Draft','Published','Retracted','Archived') NOT NULL DEFAULT 'Draft'");
    $addColumn($pdo, 'Announcement', $announcementColumns, 'lifecycle_reason', 'TEXT NULL AFTER `deleted_at`');
    $addColumn($pdo, 'Announcement', $announcementColumns, 'lifecycle_changed_by', 'INT NULL AFTER `lifecycle_reason`');
    $addColumn($pdo, 'Announcement', $announcementColumns, 'lifecycle_changed_at', 'DATETIME NULL AFTER `lifecycle_changed_by`');
    $pdo->exec("CREATE TABLE IF NOT EXISTS `AnnouncementAttachmentHistory` (
        `history_id` INT AUTO_INCREMENT PRIMARY KEY,
        `announcement_id` INT NOT NULL,
        `original_attachment_id` INT NOT NULL,
        `file_name` VARCHAR(255) NOT NULL,
        `file_path` VARCHAR(255) NOT NULL,
        `file_size` INT NOT NULL,
        `removed_by` INT NOT NULL,
        `removed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT `fk_announcement_attachment_history_announcement` FOREIGN KEY (`announcement_id`) REFERENCES `Announcement` (`announcement_id`) ON DELETE RESTRICT,
        CONSTRAINT `fk_announcement_attachment_history_user` FOREIGN KEY (`removed_by`) REFERENCES `User` (`user_id`) ON DELETE RESTRICT,
        INDEX `idx_announcement_attachment_history` (`announcement_id`, `removed_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $ledgerColumns = $columns($pdo, 'LedgerEntry');
    $addColumn($pdo, 'LedgerEntry', $ledgerColumns, 'reversal_of_entry_id', 'INT NULL AFTER `allocation_id`');
    $addColumn($pdo, 'LedgerEntry', $ledgerColumns, 'void_request_id', 'INT NULL AFTER `reversal_of_entry_id`');
    $indexCheck = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?'
    );
    foreach ([
        ['LedgerEntry', 'uq_ledger_reversal_source', 'ADD UNIQUE INDEX `uq_ledger_reversal_source` (`reversal_of_entry_id`)'],
        ['LedgerEntry', 'uq_ledger_void_request', 'ADD UNIQUE INDEX `uq_ledger_void_request` (`void_request_id`)'],
    ] as [$table, $index, $definition]) {
        $indexCheck->execute([$table, $index]);
        if ((int) $indexCheck->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE `$table` $definition");
            echo "Added $index.\n";
        }
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS `AssetRetirement` (
        `retirement_id` INT AUTO_INCREMENT PRIMARY KEY,
        `stock_id` INT NOT NULL,
        `quantity` INT NOT NULL,
        `action_type` ENUM('Retired','WrittenOff') NOT NULL,
        `reason` TEXT NOT NULL,
        `recorded_by` INT NOT NULL,
        `recorded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT `fk_asset_retirement_stock` FOREIGN KEY (`stock_id`) REFERENCES `AssetStock` (`stock_id`) ON DELETE RESTRICT,
        CONSTRAINT `fk_asset_retirement_user` FOREIGN KEY (`recorded_by`) REFERENCES `User` (`user_id`) ON DELETE RESTRICT,
        INDEX `idx_asset_retirement_stock` (`stock_id`, `recorded_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("ALTER TABLE `Audit` MODIFY COLUMN `audit_status`
        ENUM('Pending','ClarificationRequested','Completed','InProgress','Overdue','Cancelled','Reopened')
        NOT NULL DEFAULT 'Pending'");
    $auditColumns = $columns($pdo, 'Audit');
    $addColumn($pdo, 'Audit', $auditColumns, 'lifecycle_reason', 'TEXT NULL AFTER `clarification_reason`');
    $addColumn($pdo, 'Audit', $auditColumns, 'lifecycle_changed_by', 'INT NULL AFTER `lifecycle_reason`');
    $addColumn($pdo, 'Audit', $auditColumns, 'lifecycle_changed_at', 'DATETIME NULL AFTER `lifecycle_changed_by`');

    $reportColumns = $columns($pdo, 'Report');
    $addColumn($pdo, 'Report', $reportColumns, 'archive_reason', 'TEXT NULL AFTER `archived_by`');
    $addColumn($pdo, 'Report', $reportColumns, 'restored_by', 'INT NULL AFTER `archive_reason`');
    $addColumn($pdo, 'Report', $reportColumns, 'restored_at', 'DATETIME NULL AFTER `restored_by`');

    $applicationColumns = $columns($pdo, 'ClubApplication');
    $pdo->exec("ALTER TABLE `ClubApplication` MODIFY COLUMN `status`
        ENUM('Pending','Approved','Rejected','Withdrawn','Archived') NOT NULL DEFAULT 'Pending'");
    $addColumn($pdo, 'ClubApplication', $applicationColumns, 'archived_by', 'INT NULL AFTER `rejection_remarks`');
    $addColumn($pdo, 'ClubApplication', $applicationColumns, 'archived_at', 'DATETIME NULL AFTER `archived_by`');
    $addColumn($pdo, 'ClubApplication', $applicationColumns, 'archive_reason', 'TEXT NULL AFTER `archived_at`');

    echo "Divisional lifecycle schema is ready.\n";
} catch (Throwable $exception) {
    die('Migration error: ' . $exception->getMessage() . "\n");
}
