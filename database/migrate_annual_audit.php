<?php
/**
 * Migration Script: Annual Financial Audit & Red Flags Module
 *
 * Non-destructively extends `Audit`, `RedFlag`, and `LedgerEntry` tables,
 * ensures `Notification` exists, and seeds representative FY 2026 audit data.
 *
 * Usage: c:\xampp\php\php.exe database/migrate_annual_audit.php
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
    // 1. EXTEND / GENERALIZE AUDIT TABLE
    // -------------------------------------------------------------
    $pdo->exec("CREATE TABLE IF NOT EXISTS `Audit` (
        `audit_id`                    INT AUTO_INCREMENT PRIMARY KEY,
        `financial_year`              INT NOT NULL,
        `scope_level`                 ENUM('National','Zonal','Divisional','Club') NOT NULL DEFAULT 'National',
        `scope_id`                    INT NULL,
        `opening_balance`             DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `total_income`                DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `total_transfers_received`    DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `total_expenses`              DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `total_transfers_distributed` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `expected_closing_balance`    DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `actual_closing_balance`      DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `math_check_status`           ENUM('Passed','Mismatch') NOT NULL DEFAULT 'Passed',
        `receipt_threshold_amount`    DECIMAL(15,2) NOT NULL DEFAULT 5000.00,
        `hoarding_margin_pct`         DECIMAL(5,2) NOT NULL DEFAULT 80.00,
        `void_rate_threshold_pct`     DECIMAL(5,2) NOT NULL DEFAULT 10.00,
        `audit_status`                ENUM('Pending','ClarificationRequested','Completed') NOT NULL DEFAULT 'Pending',
        `initiated_by`                INT NOT NULL,
        `initiated_at`                TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `signed_off_by`               INT NULL,
        `signed_off_at`               TIMESTAMP NULL DEFAULT NULL,
        `locked`                      TINYINT(1) NOT NULL DEFAULT 0,
        INDEX idx_scope (`scope_level`, `scope_id`, `financial_year`),
        INDEX idx_status (`audit_status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $auditCols = $pdo->query("SHOW COLUMNS FROM `Audit`")->fetchAll(PDO::FETCH_COLUMN);

    if (in_array('club_id', $auditCols)) {
        // If club_id exists, make it nullable so it does not block non-club audits
        $pdo->exec("ALTER TABLE `Audit` MODIFY COLUMN `club_id` INT NULL");
    }

    if (!in_array('scope_level', $auditCols)) {
        $pdo->exec("ALTER TABLE `Audit` ADD COLUMN `scope_level` ENUM('National','Zonal','Divisional','Club') NOT NULL DEFAULT 'National' AFTER `financial_year`");
        echo "Added `scope_level` to Audit.\n";
    }
    if (!in_array('scope_id', $auditCols)) {
        $pdo->exec("ALTER TABLE `Audit` ADD COLUMN `scope_id` INT NULL AFTER `scope_level`");
        echo "Added `scope_id` to Audit.\n";
    }
    if (!in_array('opening_balance', $auditCols)) {
        $pdo->exec("ALTER TABLE `Audit` ADD COLUMN `opening_balance` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `scope_id`");
        echo "Added `opening_balance` to Audit.\n";
    }
    if (!in_array('total_income', $auditCols)) {
        $pdo->exec("ALTER TABLE `Audit` ADD COLUMN `total_income` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `opening_balance`");
        echo "Added `total_income` to Audit.\n";
    }
    if (!in_array('total_transfers_received', $auditCols)) {
        $pdo->exec("ALTER TABLE `Audit` ADD COLUMN `total_transfers_received` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `total_income`");
        echo "Added `total_transfers_received` to Audit.\n";
    }
    if (!in_array('total_expenses', $auditCols)) {
        $pdo->exec("ALTER TABLE `Audit` ADD COLUMN `total_expenses` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `total_transfers_received`");
        echo "Added `total_expenses` to Audit.\n";
    }
    if (!in_array('total_transfers_distributed', $auditCols)) {
        $pdo->exec("ALTER TABLE `Audit` ADD COLUMN `total_transfers_distributed` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `total_expenses`");
        echo "Added `total_transfers_distributed` to Audit.\n";
    }
    if (!in_array('expected_closing_balance', $auditCols)) {
        $pdo->exec("ALTER TABLE `Audit` ADD COLUMN `expected_closing_balance` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `total_transfers_distributed`");
        echo "Added `expected_closing_balance` to Audit.\n";
    }
    if (!in_array('actual_closing_balance', $auditCols)) {
        $pdo->exec("ALTER TABLE `Audit` ADD COLUMN `actual_closing_balance` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `expected_closing_balance`");
        echo "Added `actual_closing_balance` to Audit.\n";
    }
    if (!in_array('math_check_status', $auditCols)) {
        $pdo->exec("ALTER TABLE `Audit` ADD COLUMN `math_check_status` ENUM('Passed','Mismatch') NOT NULL DEFAULT 'Passed' AFTER `actual_closing_balance`");
        echo "Added `math_check_status` to Audit.\n";
    }
    if (!in_array('receipt_threshold_amount', $auditCols)) {
        $pdo->exec("ALTER TABLE `Audit` ADD COLUMN `receipt_threshold_amount` DECIMAL(15,2) NOT NULL DEFAULT 5000.00 AFTER `math_check_status`");
        echo "Added `receipt_threshold_amount` to Audit.\n";
    }
    if (!in_array('hoarding_margin_pct', $auditCols)) {
        $pdo->exec("ALTER TABLE `Audit` ADD COLUMN `hoarding_margin_pct` DECIMAL(5,2) NOT NULL DEFAULT 80.00 AFTER `receipt_threshold_amount`");
        echo "Added `hoarding_margin_pct` to Audit.\n";
    }
    if (!in_array('void_rate_threshold_pct', $auditCols)) {
        $pdo->exec("ALTER TABLE `Audit` ADD COLUMN `void_rate_threshold_pct` DECIMAL(5,2) NOT NULL DEFAULT 10.00 AFTER `hoarding_margin_pct`");
        echo "Added `void_rate_threshold_pct` to Audit.\n";
    }
    // Update audit_status ENUM to include 'ClarificationRequested' if not already there
    $pdo->exec("ALTER TABLE `Audit` MODIFY COLUMN `audit_status` ENUM('Pending','ClarificationRequested','Completed','InProgress','Overdue') NOT NULL DEFAULT 'Pending'");

    if (!in_array('initiated_by', $auditCols)) {
        $pdo->exec("ALTER TABLE `Audit` ADD COLUMN `initiated_by` INT NULL AFTER `audit_status`");
        echo "Added `initiated_by` to Audit.\n";
    }
    if (!in_array('initiated_at', $auditCols)) {
        $pdo->exec("ALTER TABLE `Audit` ADD COLUMN `initiated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `initiated_by`");
        echo "Added `initiated_at` to Audit.\n";
    }
    if (!in_array('signed_off_by', $auditCols)) {
        $pdo->exec("ALTER TABLE `Audit` ADD COLUMN `signed_off_by` INT NULL AFTER `initiated_at`");
        echo "Added `signed_off_by` to Audit.\n";
    }
    if (!in_array('signed_off_at', $auditCols)) {
        $pdo->exec("ALTER TABLE `Audit` ADD COLUMN `signed_off_at` TIMESTAMP NULL DEFAULT NULL AFTER `signed_off_by`");
        echo "Added `signed_off_at` to Audit.\n";
    }

    echo "Audit table structure verified.\n";

    // -------------------------------------------------------------
    // 2. EXTEND / GENERALIZE REDFLAG TABLE
    // -------------------------------------------------------------
    $pdo->exec("CREATE TABLE IF NOT EXISTS `RedFlag` (
        `red_flag_id` INT AUTO_INCREMENT PRIMARY KEY,
        `audit_id`    INT NOT NULL,
        `entry_id`    INT NULL,
        `flag_type`   ENUM('MissingReceipt','FundHoarding','HighVoidRate') NOT NULL DEFAULT 'MissingReceipt',
        `description` TEXT NOT NULL,
        `status`      ENUM('Open','ClarificationRequested','Resolved') NOT NULL DEFAULT 'Open',
        `flagged_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_audit (`audit_id`),
        INDEX idx_entry (`entry_id`),
        INDEX idx_status (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $flagCols = $pdo->query("SHOW COLUMNS FROM `RedFlag`")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('flag_type', $flagCols)) {
        $pdo->exec("ALTER TABLE `RedFlag` ADD COLUMN `flag_type` ENUM('MissingReceipt','FundHoarding','HighVoidRate') NOT NULL DEFAULT 'MissingReceipt' AFTER `audit_id`");
        echo "Added `flag_type` to RedFlag.\n";
    }
    if (!in_array('description', $flagCols)) {
        $pdo->exec("ALTER TABLE `RedFlag` ADD COLUMN `description` TEXT NULL AFTER `flag_type`");
        if (in_array('reason', $flagCols)) {
            $pdo->exec("UPDATE `RedFlag` SET `description` = `reason` WHERE `description` IS NULL OR `description` = ''");
        }
        echo "Added `description` to RedFlag.\n";
    }

    $pdo->exec("ALTER TABLE `RedFlag` MODIFY COLUMN `status` ENUM('Open','ClarificationRequested','Resolved','Unresolved') NOT NULL DEFAULT 'Open'");
    $pdo->exec("UPDATE `RedFlag` SET `status` = 'Open' WHERE `status` = 'Unresolved'");

    echo "RedFlag table structure verified.\n";

    // -------------------------------------------------------------
    // 3. EXTEND LEDGER ENTRY TABLE (Ensure 'Voided' status)
    // -------------------------------------------------------------
    $pdo->exec("ALTER TABLE `LedgerEntry` MODIFY COLUMN `status` ENUM('Pending','Approved','Rejected','Voided') NOT NULL DEFAULT 'Pending'");
    echo "LedgerEntry status updated to allow 'Voided'.\n";

    // -------------------------------------------------------------
    // 4. ENSURE NOTIFICATION TABLE EXISTS
    // -------------------------------------------------------------
    $pdo->exec("CREATE TABLE IF NOT EXISTS `Notification` (
        `notification_id`     INT AUTO_INCREMENT PRIMARY KEY,
        `recipient_id`        INT NOT NULL,
        `type`                VARCHAR(50) NOT NULL DEFAULT 'General',
        `message`             VARCHAR(500) NOT NULL,
        `related_entity_type` VARCHAR(50) DEFAULT NULL,
        `related_entity_id`   INT DEFAULT NULL,
        `read_status`         BOOLEAN NOT NULL DEFAULT FALSE,
        `created_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`recipient_id`) REFERENCES `User`(`user_id`) ON DELETE CASCADE,
        INDEX idx_recipient (`recipient_id`, `read_status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Notification table verified.\n";

    // -------------------------------------------------------------
    // 5. SEED REPRESENTATIVE AUDIT DATA FOR KANDY ZONE FY 2026
    // -------------------------------------------------------------
    // Get Admin User
    $adminUserId = $pdo->query("SELECT user_id FROM `User` WHERE `role` = 'NYSCAdministrator' LIMIT 1")->fetchColumn();
    if (!$adminUserId) {
        $adminUserId = $pdo->query("SELECT user_id FROM `User` LIMIT 1")->fetchColumn() ?: 1;
    }

    // Get Kandy Zone ID
    $kandyZoneId = $pdo->query("SELECT zonal_id FROM `Zone` WHERE `zonal_name` LIKE '%Kandy%' LIMIT 1")->fetchColumn();
    if (!$kandyZoneId) {
        $kandyZoneId = 6;
    }

    // Ensure Kandy Ledger exists
    $kandyLedgerId = $pdo->query("SELECT ledger_id FROM `Ledger` WHERE `owner_type` = 'Zone' AND `owner_id` = $kandyZoneId LIMIT 1")->fetchColumn();
    if (!$kandyLedgerId) {
        $pdo->prepare("INSERT INTO `Ledger` (`owner_type`, `owner_level`, `owner_id`, `current_balance`, `status`) VALUES ('Zone', 'Zonal', ?, 5500000.00, 'Active')")->execute([$kandyZoneId]);
        $kandyLedgerId = $pdo->lastInsertId();
    } else {
        // Set balance to 5,500,000.00 to match demo numbers
        $pdo->prepare("UPDATE `Ledger` SET `current_balance` = 5500000.00 WHERE `ledger_id` = ?")->execute([$kandyLedgerId]);
    }

    // Ensure Kandy Zonal Treasurer and Regional Coordinator exist for clarification recipients
    $kandyTreasurer = $pdo->query("SELECT user_id FROM `User` WHERE `role` = 'ZonalTreasurer' AND `zonal_id` = $kandyZoneId LIMIT 1")->fetchColumn();
    if (!$kandyTreasurer) {
        // Check if there is any ZonalTreasurer or create/assign one
        $existingTreas = $pdo->query("SELECT user_id FROM `User` WHERE `role` = 'ZonalTreasurer' LIMIT 1")->fetchColumn();
        if ($existingTreas) {
            $pdo->exec("UPDATE `User` SET `zonal_id` = $kandyZoneId WHERE `user_id` = $existingTreas");
            $kandyTreasurer = $existingTreas;
        } else {
            $hash = password_hash('password123', PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO `User` (`username`, `email`, `password_hash`, `status`, `first_name`, `last_name`, `role`, `zonal_id`)
                           VALUES ('kandy_treasurer', 'treasurer.kandy@youthnexus.com', ?, 'Active', 'M.', 'Perera', 'ZonalTreasurer', ?)")
                ->execute([$hash, $kandyZoneId]);
            $kandyTreasurer = $pdo->lastInsertId();
            echo "Created sample Kandy Zonal Treasurer (M. Perera).\n";
        }
    }

    $kandyCoord = $pdo->query("SELECT user_id FROM `User` WHERE `role` = 'ZonalCoordinator' AND `zonal_id` = $kandyZoneId LIMIT 1")->fetchColumn();
    if (!$kandyCoord) {
        $hash = password_hash('password123', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO `User` (`username`, `email`, `password_hash`, `status`, `first_name`, `last_name`, `role`, `zonal_id`)
                       VALUES ('kandy_coord', 'coord.kandy@youthnexus.com', ?, 'Active', 'Regional', 'Coordinator', 'ZonalCoordinator', ?)")
            ->execute([$hash, $kandyZoneId]);
        $kandyCoord = $pdo->lastInsertId();
        echo "Created sample Kandy Regional Coordinator.\n";
    }

    // Ensure sample LedgerEntries for Kandy Zone FY 2026 (Missing receipt expenses and entries)
    // 1. Entry #KND-2026-EXP-088: PA Sound Rental & Logistics — LKR 48,500 (No attachment)
    $entry1 = $pdo->prepare("SELECT entry_id FROM `LedgerEntry` WHERE `description` LIKE '%PA Sound Rental%' LIMIT 1");
    $entry1->execute();
    $e1Id = $entry1->fetchColumn();
    if (!$e1Id) {
        $pdo->prepare("INSERT INTO `LedgerEntry` (`ledger_id`, `amount`, `type`, `category`, `status`, `date`, `description`, `attachment_url`, `created_by`)
                       VALUES (?, 48500.00, 'Expense', 'Equipment & Logistics', 'Approved', '2026-06-12', 'PA Sound Rental & Logistics — Provincial Youth Conference (#KND-2026-EXP-088)', NULL, ?)")
            ->execute([$kandyLedgerId, $kandyTreasurer]);
        $e1Id = $pdo->lastInsertId();
    }

    // 2. Entry #KND-2026-EXP-114: Zonal Youth Sports Consumables — LKR 18,200 (No attachment)
    $entry2 = $pdo->prepare("SELECT entry_id FROM `LedgerEntry` WHERE `description` LIKE '%Zonal Youth Sports Consumables%' LIMIT 1");
    $entry2->execute();
    $e2Id = $entry2->fetchColumn();
    if (!$e2Id) {
        $pdo->prepare("INSERT INTO `LedgerEntry` (`ledger_id`, `amount`, `type`, `category`, `status`, `date`, `description`, `attachment_url`, `created_by`)
                       VALUES (?, 18200.00, 'Expense', 'Sports & Supplies', 'Approved', '2026-07-20', 'Zonal Youth Sports Consumables & Hydration Units (#KND-2026-EXP-114)', NULL, ?)")
            ->execute([$kandyLedgerId, $kandyTreasurer]);
        $e2Id = $pdo->lastInsertId();
    }

    // Check if Audit record for Kandy Zone FY 2026 exists
    $chkAudit = $pdo->prepare("SELECT audit_id FROM `Audit` WHERE `scope_level` = 'Zonal' AND `scope_id` = ? AND `financial_year` = 2026 LIMIT 1");
    $chkAudit->execute([$kandyZoneId]);
    $auditId = $chkAudit->fetchColumn();

    if (!$auditId) {
        $insAudit = $pdo->prepare("INSERT INTO `Audit` (
            `financial_year`, `scope_level`, `scope_id`,
            `opening_balance`, `total_income`, `total_transfers_received`,
            `total_expenses`, `total_transfers_distributed`,
            `expected_closing_balance`, `actual_closing_balance`, `math_check_status`,
            `receipt_threshold_amount`, `hoarding_margin_pct`, `void_rate_threshold_pct`,
            `audit_status`, `initiated_by`, `locked`
        ) VALUES (
            2026, 'Zonal', ?,
            1250000.00, 3850000.00, 12000000.00,
            9200000.00, 2400000.00,
            5500000.00, 5500000.00, 'Passed',
            5000.00, 80.00, 10.00,
            'Pending', ?, 0
        )");
        $insAudit->execute([$kandyZoneId, $adminUserId]);
        $auditId = $pdo->lastInsertId();
        echo "Created Kandy Zone FY 2026 Audit (ID: $auditId).\n";
    } else {
        $pdo->prepare("UPDATE `Audit` SET
            `opening_balance` = 1250000.00,
            `total_income` = 3850000.00,
            `total_transfers_received` = 12000000.00,
            `total_expenses` = 9200000.00,
            `total_transfers_distributed` = 2400000.00,
            `expected_closing_balance` = 5500000.00,
            `actual_closing_balance` = 5500000.00,
            `math_check_status` = 'Passed',
            `receipt_threshold_amount` = 5000.00,
            `hoarding_margin_pct` = 80.00,
            `void_rate_threshold_pct` = 10.00
            WHERE `audit_id` = ?")->execute([$auditId]);
        echo "Updated Kandy Zone FY 2026 Audit (ID: $auditId).\n";
    }

    // Seed/refresh RedFlag items for this audit
    $flagCount = $pdo->query("SELECT COUNT(*) FROM `RedFlag` WHERE `audit_id` = $auditId")->fetchColumn();
    if ($flagCount < 4) {
        $pdo->prepare("DELETE FROM `RedFlag` WHERE `audit_id` = ?")->execute([$auditId]);

        $insFlag = $pdo->prepare("INSERT INTO `RedFlag` (`audit_id`, `entry_id`, `flag_type`, `description`, `status`, `flagged_at`)
                                  VALUES (?, ?, ?, ?, 'Open', NOW())");

        // 1. Missing receipt 1
        $insFlag->execute([
            $auditId,
            $e1Id,
            'MissingReceipt',
            'PA Sound Rental & Logistics — Provincial Youth Conference (Ref: #KND-2026-EXP-088) • Beneficiary: SoundKraft Audio Services • Expense: LKR 48,500 (Exceeds LKR 5,000 threshold)'
        ]);

        // 2. Missing receipt 2
        $insFlag->execute([
            $auditId,
            $e2Id,
            'MissingReceipt',
            'Zonal Youth Sports Consumables & Hydration Units (Ref: #KND-2026-EXP-114) • Beneficiary: Metro Sports Supplies • Expense: LKR 18,200 (Exceeds LKR 5,000 threshold)'
        ]);

        // 3. Fund hoarding
        $insFlag->execute([
            $auditId,
            null,
            'FundHoarding',
            'Youth Leadership Empowerment Grant (Ref: #CPH-GRANT-89) • Disbursed: LKR 2.50M • Unspent: LKR 2.22M (88.8% idle margin > 20% limit)'
        ]);

        // 4. High void rate
        $insFlag->execute([
            $auditId,
            null,
            'HighVoidRate',
            'Kandy Zonal Sub-Ledger Void Rate: 14.2% (Permissible ceiling: 10%) (Ref: REG-DISC-VOIDS) • 18 voided entries recorded out of 127 journal transactions'
        ]);

        echo "Seeded 4 RedFlag exceptions for Audit ID: $auditId.\n";
    }

    echo "\n=== Annual Financial Audit Migration Completed Successfully ===\n";

} catch (Exception $e) {
    die("Migration error: " . $e->getMessage() . "\n");
}
