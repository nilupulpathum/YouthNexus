<?php
/**
 * Migration Script: Manage Reports Module
 *
 * Creates ReportTypeCatalog, Report, and ReportShare tables.
 * Non-destructive: uses CREATE TABLE IF NOT EXISTS + column existence checks.
 *
 * Usage: c:\xampp\php\php.exe database/migrate_manage_reports.php
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

    // ---------------------------------------------------------------
    // 1. ReportTypeCatalog — lookup table for categories & types
    // ---------------------------------------------------------------
    $pdo->exec("CREATE TABLE IF NOT EXISTS `ReportTypeCatalog` (
        `report_type_id`  INT AUTO_INCREMENT PRIMARY KEY,
        `category`        ENUM('Financial','Assets','Events','Attendance','Club Health','User Interactions') NOT NULL,
        `type_name`       VARCHAR(120) NOT NULL,
        `description`     TEXT NULL,
        `sort_order`      TINYINT UNSIGNED NOT NULL DEFAULT 0,
        UNIQUE KEY uq_cat_type (`category`, `type_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "ReportTypeCatalog table ensured.\n";

    // ---------------------------------------------------------------
    // 2. Report — core report record (ensure table + alter for new columns)
    // ---------------------------------------------------------------
    $pdo->exec("CREATE TABLE IF NOT EXISTS `Report` (
        `report_id`         INT AUTO_INCREMENT PRIMARY KEY,
        `report_type_id`    INT NULL,
        `scope_level`       VARCHAR(50) NOT NULL DEFAULT 'National',
        `scope_id`          INT NULL COMMENT 'NULL when National',
        `date_range_start`  DATE NULL,
        `date_range_end`    DATE NULL,
        `format`            ENUM('PDF','CSV','OnScreen') NOT NULL DEFAULT 'PDF',
        `file_path`         VARCHAR(500) NULL,
        `status`            VARCHAR(50) NOT NULL DEFAULT 'Active',
        `generated_by`      INT NULL,
        `generated_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Add missing columns if Report table previously existed from older schema
    $existingCols = $pdo->query("SHOW COLUMNS FROM `Report`")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('report_type_id', $existingCols)) {
        $pdo->exec("ALTER TABLE `Report` ADD COLUMN `report_type_id` INT NULL AFTER `report_id`");
    }
    if (!in_array('date_range_start', $existingCols)) {
        $pdo->exec("ALTER TABLE `Report` ADD COLUMN `date_range_start` DATE NULL");
    }
    if (!in_array('date_range_end', $existingCols)) {
        $pdo->exec("ALTER TABLE `Report` ADD COLUMN `date_range_end` DATE NULL");
    }
    if (!in_array('format', $existingCols)) {
        $pdo->exec("ALTER TABLE `Report` ADD COLUMN `format` ENUM('PDF','CSV','OnScreen') NOT NULL DEFAULT 'PDF'");
    }
    if (!in_array('file_path', $existingCols)) {
        $pdo->exec("ALTER TABLE `Report` ADD COLUMN `file_path` VARCHAR(500) NULL");
    }
    // Widen scope_level and status column types to accommodate both legacy and new values
    $pdo->exec("ALTER TABLE `Report` MODIFY COLUMN `scope_level` VARCHAR(50) NOT NULL DEFAULT 'National'");
    $pdo->exec("ALTER TABLE `Report` MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'Active'");
    // Make scope_id nullable (may have been NOT NULL in older schema)
    $pdo->exec("ALTER TABLE `Report` MODIFY COLUMN `scope_id` INT NULL");
    // Make generated_by nullable too
    $pdo->exec("ALTER TABLE `Report` MODIFY COLUMN `generated_by` INT NULL");

    echo "Report table ensured and columns updated.\n";

    // ---------------------------------------------------------------
    // 3. ReportShare — audit trail for email/share actions
    // ---------------------------------------------------------------
    $pdo->exec("CREATE TABLE IF NOT EXISTS `ReportShare` (
        `share_id`        INT AUTO_INCREMENT PRIMARY KEY,
        `report_id`       INT NOT NULL,
        `shared_by`       INT NOT NULL,
        `recipient_email` VARCHAR(255) NOT NULL,
        `method`          ENUM('Email','Link') NOT NULL DEFAULT 'Email',
        `shared_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_rpt  (`report_id`),
        INDEX idx_user (`shared_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "ReportShare table ensured.\n";

    // ---------------------------------------------------------------
    // 4. Seed ReportTypeCatalog (idempotent — uses INSERT IGNORE)
    // ---------------------------------------------------------------
    $seeds = [
        ['Financial',         'National Consolidated Financial Rollup',   'Aggregated SUM(Income), SUM(Expense), Allocated vs Spent, Void Rate across all zones.', 1],
        ['Financial',         'Income Summary',                           'Total income broken down by source across the selected scope and date range.',           2],
        ['Financial',         'Expense Summary',                          'Total expenditure by category across the selected scope and date range.',                  3],
        ['Financial',         'Fund Allocation vs Utilisation',           'Side-by-side comparison of funds allocated and actual funds spent per zone/division.',    4],
        ['Financial',         'Void Rate Report',                         'Rate of voided/reversed ledger entries; identifies compliance outliers.',                  5],
        ['Assets',            'Inventory by Category',                    'Asset counts grouped by category (Equipment, Furniture, IT, etc.).',                      1],
        ['Assets',            'Inventory by Condition',                   'Asset distribution by condition (Good, Fair, Poor, Condemned).',                           2],
        ['Assets',            'Request Status Summary',                    'Summary of asset distribution requests by status (Pending, Approved, Rejected).',        3],
        ['Events',            'Event Status Summary',                     'Count of events by status (Upcoming, Ongoing, Completed, Cancelled) in scope.',            1],
        ['Events',            'Event Attendance Rate',                    'Ratio of actual attendees to expected attendees per event within the date range.',          2],
        ['Attendance',        'Attendance Rate by Club / Division / Zone','Member attendance rate aggregated at club, divisional, or zonal level.',                   1],
        ['Club Health',       'Health Status Distribution',               'Distribution of clubs across Green/Yellow/Red health bands.',                              1],
        ['Club Health',       'Health Score Trend',                       'Month-over-month club health score movement within the selected scope.',                   2],
        ['User Interactions', 'Login Activity',                           'Login frequency, unique active users, and inactive user count.',                           1],
        ['User Interactions', 'Role Distribution',                        'Breakdown of registered users by system role.',                                             2],
        ['User Interactions', 'Announcement Read Rate',                   'Percentage of users who have read each broadcast announcement.',                           3],
        ['User Interactions', 'Volunteer Hours',                          'Total and average volunteer hours logged per member within the date range.',                4],
    ];

    $insertType = $pdo->prepare(
        "INSERT IGNORE INTO `ReportTypeCatalog` (`category`, `type_name`, `description`, `sort_order`) VALUES (?, ?, ?, ?)"
    );
    foreach ($seeds as $row) {
        $insertType->execute($row);
    }
    echo "ReportTypeCatalog seeded (" . count($seeds) . " types).\n";

    // ---------------------------------------------------------------
    // 5. Seed sample Report rows (if no catalog-linked reports exist)
    // ---------------------------------------------------------------
    $reportCount = (int)$pdo->query("SELECT COUNT(*) FROM `Report` WHERE `report_type_id` IS NOT NULL")->fetchColumn();
    if ($reportCount === 0) {
        $typeIds = [];
        $rows = $pdo->query("SELECT report_type_id, type_name FROM ReportTypeCatalog")->fetchAll();
        foreach ($rows as $r) {
            $typeIds[$r->type_name] = (int)$r->report_type_id;
        }

        $adminId = 1;
        $insRpt  = $pdo->prepare(
            "INSERT INTO `Report` (report_type_id, scope_level, scope_id, date_range_start, date_range_end, format, status, generated_by, generated_at)
             VALUES (?, ?, ?, ?, ?, ?, 'Active', ?, ?)"
        );

        $sampleReports = [
            [$typeIds['Attendance Rate by Club / Division / Zone'] ?? 11, 'Zonal',     null, '2026-04-01', '2026-06-30', 'PDF',      $adminId, '2026-07-05 09:10:00'],
            [$typeIds['Role Distribution']                         ?? 15, 'Zonal',     null, '2026-04-01', '2026-06-30', 'CSV',      $adminId, '2026-06-28 14:22:00'],
            [$typeIds['Event Status Summary']                      ?? 9,  'Divisional', null, '2026-04-01', '2026-06-30', 'OnScreen', $adminId, '2026-06-20 11:05:00'],
            [$typeIds['Fund Allocation vs Utilisation']           ?? 4,  'Zonal',     null, '2026-01-01', '2026-03-31', 'PDF',      $adminId, '2026-04-04 08:30:00'],
            [$typeIds['Health Status Distribution']               ?? 12, 'National',  null, '2026-01-01', '2026-06-30', 'CSV',      $adminId, '2026-06-15 16:45:00'],
            [$typeIds['Void Rate Report']                          ?? 5,  'National',  null, '2026-01-01', '2026-06-30', 'PDF',      $adminId, '2026-06-10 10:00:00'],
        ];

        foreach ($sampleReports as $sr) {
            $insRpt->execute($sr);
        }
        echo "Seeded " . count($sampleReports) . " sample Report rows.\n";
    } else {
        echo "Report table already has active catalog-linked data - skipping sample seed.\n";
    }

    echo "\nMigration complete. Manage Reports module is ready.\n";

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
