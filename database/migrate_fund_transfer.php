<?php
/**
 * Migration Script: Fund Transfer & Fund Allocation Module
 *
 * Adds new tables and non-destructively extends existing tables
 * according to the specification.
 *
 * Usage: c:\xampp\php\php.exe database/migrate_fund_transfer.php
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
    // 1. EXTEND ZONE TABLE (non-destructively add province & hub_name)
    // -------------------------------------------------------------
    $zoneCols = $pdo->query("SHOW COLUMNS FROM `Zone`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('province', $zoneCols)) {
        $pdo->exec("ALTER TABLE `Zone` ADD COLUMN `province` VARCHAR(100) NULL AFTER `zonal_name`");
        echo "Added column `province` to Zone.\n";
    }
    if (!in_array('hub_name', $zoneCols)) {
        $pdo->exec("ALTER TABLE `Zone` ADD COLUMN `hub_name` VARCHAR(100) NULL AFTER `province`");
        echo "Added column `hub_name` to Zone.\n";
    }

    // Update / ensure the 9 standard Zones exist
    $standardZones = [
        ['name' => 'Colombo Zone',    'province' => 'Western Province',       'hub' => 'Hub 01'],
        ['name' => 'Gampaha Zone',    'province' => 'Western Province',       'hub' => 'Youth Circle Hub'],
        ['name' => 'Kandy Zone',      'province' => 'Central Province',       'hub' => 'Vocational Office Hub'],
        ['name' => 'Galle Zone',      'province' => 'Southern Province',      'hub' => 'Coastal Training Hub'],
        ['name' => 'Kurunegala Zone', 'province' => 'North Western Province', 'hub' => 'Hub 05'],
        ['name' => 'Jaffna Zone',     'province' => 'Northern Province',      'hub' => 'Hub 06'],
        ['name' => 'Badulla Zone',    'province' => 'Uva Province',            'hub' => 'Hub 07'],
        ['name' => 'Anuradhapura Zone','province' => 'North Central Province', 'hub' => 'Hub 08'],
        ['name' => 'Ratnapura Zone',  'province' => 'Sabaragamuwa Province',  'hub' => 'Hub 09'],
    ];

    foreach ($standardZones as $sz) {
        $chk = $pdo->prepare("SELECT zonal_id FROM `Zone` WHERE `zonal_name` = ? OR `zonal_name` LIKE ?");
        $chk->execute([$sz['name'], '%' . explode(' ', $sz['name'])[0] . '%']);
        $existingId = $chk->fetchColumn();

        if ($existingId) {
            $upd = $pdo->prepare("UPDATE `Zone` SET `zonal_name` = ?, `province` = ?, `hub_name` = ? WHERE `zonal_id` = ?");
            $upd->execute([$sz['name'], $sz['province'], $sz['hub'], $existingId]);
        } else {
            $ins = $pdo->prepare("INSERT INTO `Zone` (`zonal_name`, `province`, `hub_name`) VALUES (?, ?, ?)");
            $ins->execute([$sz['name'], $sz['province'], $sz['hub']]);
        }
    }
    echo "Zones updated/seeded.\n";

    // -------------------------------------------------------------
    // 2. EXTEND LEDGER TABLE (add owner_level if missing, ensure National ledger)
    // -------------------------------------------------------------
    $ledgerCols = $pdo->query("SHOW COLUMNS FROM `Ledger`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('owner_level', $ledgerCols)) {
        $pdo->exec("ALTER TABLE `Ledger` ADD COLUMN `owner_level` VARCHAR(50) NULL AFTER `owner_type`");
        $pdo->exec("UPDATE `Ledger` SET `owner_level` = `owner_type` WHERE `owner_level` IS NULL");
        echo "Added column `owner_level` to Ledger.\n";
    }

    // Ensure a National Ledger exists (owner_level = 'National', owner_id = 0)
    $natLedger = $pdo->query("SELECT ledger_id FROM `Ledger` WHERE `owner_level` = 'National' LIMIT 1")->fetchColumn();
    if (!$natLedger) {
        // Find if any Zone ledger exists or insert National ledger
        $pdo->exec("INSERT INTO `Ledger` (`owner_type`, `owner_level`, `owner_id`, `current_balance`, `status`, `created_at`)
                    VALUES ('Zone', 'National', 0, 100000000.00, 'Active', NOW())");
        $natLedgerId = $pdo->lastInsertId();
        echo "Created National Ledger (ID: $natLedgerId) with LKR 100,000,000.00 balance.\n";
    } else {
        echo "National Ledger exists (ID: $natLedger).\n";
    }

    // Ensure 1:1 Ledger exists for each Zone
    $allZones = $pdo->query("SELECT zonal_id FROM `Zone`")->fetchAll(PDO::FETCH_COLUMN);
    $checkZoneLedger = $pdo->prepare("SELECT ledger_id FROM `Ledger` WHERE `owner_type` = 'Zone' AND `owner_id` = ?");
    $insertZoneLedger = $pdo->prepare("INSERT INTO `Ledger` (`owner_type`, `owner_level`, `owner_id`, `current_balance`, `status`, `created_at`) VALUES ('Zone', 'Zonal', ?, 2500000.00, 'Active', NOW())");

    foreach ($allZones as $zid) {
        $checkZoneLedger->execute([$zid]);
        if (!$checkZoneLedger->fetchColumn()) {
            $insertZoneLedger->execute([$zid]);
        }
    }
    echo "Each zone verified to have a 1:1 Ledger.\n";

    // -------------------------------------------------------------
    // 3. CREATE BANK ACCOUNT TABLE
    // -------------------------------------------------------------
    $pdo->exec("CREATE TABLE IF NOT EXISTS `BankAccount` (
        `bank_account_id`     INT AUTO_INCREMENT PRIMARY KEY,
        `owner_level`         ENUM('National','Zonal','Divisional','Club') NOT NULL DEFAULT 'National',
        `owner_id`            INT NULL,
        `bank_name`           VARCHAR(100) NOT NULL,
        `branch_name`         VARCHAR(100) NOT NULL,
        `account_number`      VARCHAR(50)  NOT NULL,
        `account_label`       VARCHAR(200) NOT NULL,
        `gateway_type`        ENUM('RTGS','SLIPS','API','Cheque') NOT NULL DEFAULT 'RTGS',
        `verification_status` ENUM('Verified','Unverified') NOT NULL DEFAULT 'Verified',
        `status`              ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
        `created_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_owner (`owner_level`, `owner_id`),
        INDEX idx_status (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table `BankAccount` ready.\n";

    // Seed Core Bank Account if empty
    $baCount = $pdo->query("SELECT COUNT(*) FROM `BankAccount`")->fetchColumn();
    if ($baCount == 0) {
        $pdo->exec("INSERT INTO `BankAccount`
            (`owner_level`, `owner_id`, `bank_name`, `branch_name`, `account_number`, `account_label`, `gateway_type`, `verification_status`, `status`)
            VALUES
            ('National', NULL, 'Bank of Ceylon', 'Colombo Fort (001)', '8620-0012-3456-7890', 'NYSC Colombo Operational Fund', 'RTGS', 'Verified', 'Active'),
            ('National', NULL, 'People\'s Bank', 'Headquarters (010)', '7120-0098-4321-1234', 'NYSC Central Reserve Fund', 'SLIPS', 'Verified', 'Active')");
        echo "Seeded BankAccount.\n";
    }

    // -------------------------------------------------------------
    // 4. CREATE FUND ALLOCATION TABLE
    // -------------------------------------------------------------
    $pdo->exec("CREATE TABLE IF NOT EXISTS `FundAllocation` (
        `allocation_id`           INT AUTO_INCREMENT PRIMARY KEY,
        `from_level`              ENUM('National','Zonal','Divisional') NOT NULL DEFAULT 'National',
        `from_id`                 INT NULL,
        `to_level`                ENUM('Zonal','Divisional','Club') NOT NULL DEFAULT 'Zonal',
        `to_id`                   INT NOT NULL,
        `source_bank_account_id`  INT NOT NULL,
        `amount`                  DECIMAL(15,2) NOT NULL,
        `transfer_date`           DATE NOT NULL,
        `reference_no`            VARCHAR(50) NOT NULL UNIQUE,
        `disbursement_method`     ENUM('RTGS','ChequeSLIPS') NOT NULL DEFAULT 'RTGS',
        `purpose_description`     TEXT NOT NULL,
        `status`                  ENUM('Processing','Completed','Failed') NOT NULL DEFAULT 'Processing',
        `authorized_by`           INT NOT NULL,
        `created_at`              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`source_bank_account_id`) REFERENCES `BankAccount`(`bank_account_id`),
        FOREIGN KEY (`authorized_by`) REFERENCES `User`(`user_id`),
        INDEX idx_status (`status`),
        INDEX idx_date (`transfer_date`),
        INDEX idx_dest (`to_level`, `to_id`),
        INDEX idx_ref (`reference_no`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table `FundAllocation` ready.\n";

    // -------------------------------------------------------------
    // 5. EXTEND LEDGER ENTRY TABLE (add category, attachment_url, created_by, created_at, allocation_id)
    // -------------------------------------------------------------
    $leCols = $pdo->query("SHOW COLUMNS FROM `LedgerEntry`")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('category', $leCols)) {
        $pdo->exec("ALTER TABLE `LedgerEntry` ADD COLUMN `category` VARCHAR(100) NULL AFTER `type`");
        echo "Added column `category` to LedgerEntry.\n";
    }
    if (!in_array('attachment_url', $leCols)) {
        $pdo->exec("ALTER TABLE `LedgerEntry` ADD COLUMN `attachment_url` VARCHAR(255) NULL AFTER `description`");
        echo "Added column `attachment_url` to LedgerEntry.\n";
    }
    if (!in_array('created_by', $leCols)) {
        $pdo->exec("ALTER TABLE `LedgerEntry` ADD COLUMN `created_by` INT NULL AFTER `status`");
        echo "Added column `created_by` to LedgerEntry.\n";
    }
    if (!in_array('created_at', $leCols)) {
        $pdo->exec("ALTER TABLE `LedgerEntry` ADD COLUMN `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `created_by`");
        echo "Added column `created_at` to LedgerEntry.\n";
    }
    if (!in_array('allocation_id', $leCols)) {
        $pdo->exec("ALTER TABLE `LedgerEntry` ADD COLUMN `allocation_id` INT NULL AFTER `created_at`");
        $pdo->exec("ALTER TABLE `LedgerEntry` ADD CONSTRAINT `fk_ledger_entry_allocation` FOREIGN KEY (`allocation_id`) REFERENCES `FundAllocation`(`allocation_id`) ON DELETE SET NULL");
        echo "Added column `allocation_id` with foreign key to LedgerEntry.\n";
    }

    // -------------------------------------------------------------
    // 6. CREATE FUND BUDGET TABLE
    // -------------------------------------------------------------
    $pdo->exec("CREATE TABLE IF NOT EXISTS `FundBudget` (
        `budget_id`     INT AUTO_INCREMENT PRIMARY KEY,
        `fiscal_year`   INT NOT NULL,
        `quarter`       VARCHAR(10) NOT NULL,
        `total_cap`     DECIMAL(15,2) NOT NULL,
        `set_by`        INT NOT NULL,
        `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`set_by`) REFERENCES `User`(`user_id`),
        UNIQUE KEY `uq_year_quarter` (`fiscal_year`, `quarter`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table `FundBudget` ready.\n";

    // Seed FundBudget if empty
    $adminUser = $pdo->query("SELECT user_id FROM `User` WHERE `role` = 'NYSCAdministrator' LIMIT 1")->fetchColumn();
    if (!$adminUser) {
        $adminUser = $pdo->query("SELECT user_id FROM `User` LIMIT 1")->fetchColumn();
    }

    $fbCount = $pdo->query("SELECT COUNT(*) FROM `FundBudget`")->fetchColumn();
    if ($fbCount == 0 && $adminUser) {
        $pdo->prepare("INSERT INTO `FundBudget` (`fiscal_year`, `quarter`, `total_cap`, `set_by`) VALUES
            (2026, 'Q1', 35000000.00, ?),
            (2026, 'Q2', 40000000.00, ?),
            (2026, 'Q3', 42000000.00, ?),
            (2026, 'Q4', 45000000.00, ?)")->execute([$adminUser, $adminUser, $adminUser, $adminUser]);
        echo "Seeded FundBudget for 2026.\n";
    }

    // -------------------------------------------------------------
    // 7. SEED INITIAL TRANSFERS FROM fundTransfer.php (with paired LedgerEntries)
    // -------------------------------------------------------------
    $faCount = $pdo->query("SELECT COUNT(*) FROM `FundAllocation`")->fetchColumn();
    if ($faCount == 0 && $adminUser) {
        $coreBankId = $pdo->query("SELECT bank_account_id FROM `BankAccount` WHERE `owner_level` = 'National' LIMIT 1")->fetchColumn();
        $natLedgerId = $pdo->query("SELECT ledger_id FROM `Ledger` WHERE `owner_level` = 'National' LIMIT 1")->fetchColumn();

        // Get Zone IDs
        $colomboId    = $pdo->query("SELECT zonal_id FROM `Zone` WHERE `zonal_name` LIKE '%Colombo%' LIMIT 1")->fetchColumn() ?: 1;
        $gampahaId    = $pdo->query("SELECT zonal_id FROM `Zone` WHERE `zonal_name` LIKE '%Gampaha%' LIMIT 1")->fetchColumn() ?: 2;
        $kandyId      = $pdo->query("SELECT zonal_id FROM `Zone` WHERE `zonal_name` LIKE '%Kandy%' LIMIT 1")->fetchColumn() ?: 3;
        $galleId      = $pdo->query("SELECT zonal_id FROM `Zone` WHERE `zonal_name` LIKE '%Galle%' LIMIT 1")->fetchColumn() ?: 4;
        $kurunegalaId = $pdo->query("SELECT zonal_id FROM `Zone` WHERE `zonal_name` LIKE '%Kurunegala%' LIMIT 1")->fetchColumn() ?: 5;

        $seedTransfers = [
            [
                'ref'    => 'TRF-2026-0048',
                'zoneId' => $gampahaId,
                'amount' => 750000.00,
                'date'   => '2026-10-28',
                'method' => 'RTGS',
                'desc'   => 'Gov Grant Q4 — Youth Circle Hub Support',
                'status' => 'Processing',
            ],
            [
                'ref'    => 'CHQ-2026-0045',
                'zoneId' => $kandyId,
                'amount' => 500000.00,
                'date'   => '2026-10-25',
                'method' => 'ChequeSLIPS',
                'desc'   => 'Quarterly Sports Alloc. Central Province Vocational Office',
                'status' => 'Completed',
            ],
            [
                'ref'    => 'TRF-2026-0042',
                'zoneId' => $colomboId,
                'amount' => 1200000.00,
                'date'   => '2026-10-20',
                'method' => 'RTGS',
                'desc'   => 'National Skills Camp 2026 — Colombo Div. Central Office',
                'status' => 'Completed',
            ],
            [
                'ref'    => 'CHQ-2026-0039',
                'zoneId' => $galleId,
                'amount' => 600000.00,
                'date'   => '2026-10-14',
                'method' => 'ChequeSLIPS',
                'desc'   => 'Eco Youth Initiatives — Southern Coastal Training Hub',
                'status' => 'Completed',
            ],
            [
                'ref'    => 'TRF-2026-0031',
                'zoneId' => $kurunegalaId,
                'amount' => 450000.00,
                'date'   => '2026-10-08',
                'method' => 'RTGS',
                'desc'   => 'Agro Youth Seed Capital — North Western Province',
                'status' => 'Completed',
            ],
        ];

        $insAlloc = $pdo->prepare("INSERT INTO `FundAllocation`
            (`from_level`, `from_id`, `to_level`, `to_id`, `source_bank_account_id`, `amount`, `transfer_date`, `reference_no`, `disbursement_method`, `purpose_description`, `status`, `authorized_by`)
            VALUES ('National', NULL, 'Zonal', ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $insLedgerEntry = $pdo->prepare("INSERT INTO `LedgerEntry`
            (`ledger_id`, `amount`, `type`, `category`, `description`, `status`, `date`, `created_by`, `allocation_id`)
            VALUES (?, ?, ?, ?, ?, 'Approved', ?, ?, ?)");

        $updBalance = $pdo->prepare("UPDATE `Ledger` SET `current_balance` = `current_balance` + ? WHERE `ledger_id` = ?");

        foreach ($seedTransfers as $st) {
            $insAlloc->execute([
                $st['zoneId'],
                $coreBankId,
                $st['amount'],
                $st['date'],
                $st['ref'],
                $st['method'],
                $st['desc'],
                $st['status'],
                $adminUser,
            ]);
            $allocId = $pdo->lastInsertId();

            // Find destination zone's ledger ID
            $zoneLedgerId = $pdo->prepare("SELECT `ledger_id` FROM `Ledger` WHERE `owner_type` = 'Zone' AND `owner_id` = ? LIMIT 1");
            $zoneLedgerId->execute([$st['zoneId']]);
            $zLedger = $zoneLedgerId->fetchColumn();

            // 1. National Outflow (Expense)
            if ($natLedgerId) {
                $insLedgerEntry->execute([
                    $natLedgerId,
                    $st['amount'],
                    'Expense',
                    'Zonal Fund Allocation',
                    'Transfer to Zone #' . $st['zoneId'] . ' (' . $st['ref'] . ')',
                    $st['date'],
                    $adminUser,
                    $allocId,
                ]);
                $updBalance->execute([-$st['amount'], $natLedgerId]);
            }

            // 2. Destination Zone Inflow (Income)
            if ($zLedger) {
                $insLedgerEntry->execute([
                    $zLedger,
                    $st['amount'],
                    'Income',
                    'National Grant / Zonal Allocation',
                    'Allocation received from NYSC National Admin (' . $st['ref'] . ')',
                    $st['date'],
                    $adminUser,
                    $allocId,
                ]);
                $updBalance->execute([$st['amount'], $zLedger]);
            }
        }
        echo "Seeded FundAllocation with paired LedgerEntry records and updated ledger balances.\n";
    }

    echo "\n=== Fund Transfer Migration Completed Successfully ===\n";

} catch (Exception $e) {
    die("Migration error: " . $e->getMessage() . "\n");
}
