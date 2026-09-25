<?php
/**
 * Migration Script: Divisional Treasurer — Finance Schema
 *
 * Non-destructively extends the existing Ledger / LedgerEntry / FundAllocation /
 * BankAccount tables (owned by feat-fund-transfer) to support the Divisional
 * Treasurer scope, and creates the new VoidRequest table.
 *
 * This script does NOT create Ledger, LedgerEntry, FundAllocation, BankAccount,
 * AssetStock, AssetTransfer, Audit, or Notification — those already exist
 * (created by migrate_fund_transfer.php / migrate_manage_assets.php /
 * migrate_annual_audit.php). Run this AFTER those have already run at least once.
 *
 * Changes made here:
 *   1. LedgerEntry   — add `reconciled` column (manual reconciliation flag; no
 *                       real bank-feed integration, this is a mockup flag only)
 *   2. FundAllocation — extend `status` ENUM to add 'PendingApproval' and
 *                       'Rejected', so a FundAllocation row can represent a
 *                       club/division-initiated request awaiting approval,
 *                       not just an already-completed transfer
 *   3. BankAccount    — seed one mock account per Zone and per Division, so
 *                       Divisional/Club-level disbursements have a believable
 *                       `source_bank_account_id` to reference, matching how
 *                       National already has two seeded accounts. No real
 *                       gateway — this only makes the mockup look complete.
 *   4. VoidRequest    — new table. Serves both "Request Void (Div.)"
 *                       (Club->Division and Division->Zonal) and
 *                       "Approve Void (Club)" — same mechanism, both directions.
 *
 * Usage: c:\xampp\php\php.exe database/migrate_divisional_treasurer.php
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
    // 0. SAFETY CHECK — confirm the tables this script extends
    //    already exist (i.e. feat-fund-transfer / feat-annual-audit
    //    / feat-manage-assets-NYSC migrations have run)
    // -------------------------------------------------------------

    $requiredTables = [
    'Ledger',
    'LedgerEntry',
    'FundAllocation',
    'BankAccount',
    'Zone',
    'Division',
    'User',
];

$tableCheck = $pdo->prepare("
    SELECT COUNT(*)
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = ?
");

foreach ($requiredTables as $table) {
    $tableCheck->execute([$table]);

    if ((int) $tableCheck->fetchColumn() === 0) {
        throw new RuntimeException(
            "Required table `$table` does not exist. " .
            "Run migrate_fund_transfer.php first, then retry."
        );
    }
}

echo "All required base tables found.\n";


    // -------------------------------------------------------------
    // 1. EXTEND LEDGERENTRY — add `reconciled` flag (manual, mockup only)
    // -------------------------------------------------------------
    $leCols = $pdo->query("SHOW COLUMNS FROM `LedgerEntry`")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('reconciled', $leCols, true)) {
        $pdo->exec("ALTER TABLE `LedgerEntry` ADD COLUMN `reconciled` BOOLEAN NOT NULL DEFAULT FALSE AFTER `status`");
        echo "Added `reconciled` column to LedgerEntry.\n";
    } else {
        echo "LedgerEntry.reconciled already present, skipping.\n";
    }

    // -------------------------------------------------------------
    // 2. EXTEND FUNDALLOCATION — status ENUM gains 'PendingApproval' and 'Rejected'
    //    Safe: FundTransferModel always passes status explicitly
    //    ($data['status'] ?? 'Processing'), never relies on the column
    //    default, so widening the default here does not change existing
    //    Fund Transfer behaviour.
    // -------------------------------------------------------------
    $pdo->exec("ALTER TABLE `FundAllocation`
                MODIFY COLUMN `status`
                ENUM('PendingApproval','Rejected','Processing','Completed','Failed')
                NOT NULL DEFAULT 'PendingApproval'");
    echo "FundAllocation.status ENUM extended with PendingApproval / Rejected.\n";

    $allocationCols = $pdo->query("SHOW COLUMNS FROM `FundAllocation`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('fund_category', $allocationCols, true)) {
        $pdo->exec("ALTER TABLE `FundAllocation`
                    ADD COLUMN `fund_category` VARCHAR(100) NULL AFTER `amount`");
        echo "Added `fund_category` column to FundAllocation.\n";
    } else {
        echo "FundAllocation.fund_category already present, skipping.\n";
    }

    // -------------------------------------------------------------
    // 3. SEED BANKACCOUNT — one mock account per Zone and per Division
    //    (owner_level already supports 'Zonal'/'Divisional' — additive only)
    // -------------------------------------------------------------
    $checkBank  = $pdo->prepare("SELECT bank_account_id FROM `BankAccount` WHERE `owner_level` = ? AND `owner_id` = ?");
    $insertBank = $pdo->prepare("INSERT INTO `BankAccount`
        (`owner_level`, `owner_id`, `bank_name`, `branch_name`, `account_number`, `account_label`, `gateway_type`, `verification_status`, `status`)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'Verified', 'Active')");

    // 3a. Zonal accounts
    $zones = $pdo->query("SELECT zonal_id, zonal_name FROM `Zone`")->fetchAll();
    $zoneSeeded = 0;
    foreach ($zones as $z) {
        $checkBank->execute(['Zonal', $z->zonal_id]);
        if (!$checkBank->fetchColumn()) {
            $acctNo = '9' . str_pad($z->zonal_id, 3, '0', STR_PAD_LEFT) . '-0011-' . rand(1000, 9999) . '-' . rand(1000, 9999);
            $insertBank->execute([
                'Zonal',
                $z->zonal_id,
                "People's Bank",
                $z->zonal_name . ' Zonal Branch',
                $acctNo,
                $z->zonal_name . ' Zonal Treasury Fund',
                'SLIPS',
            ]);
            $zoneSeeded++;
        }
    }
    echo "Seeded $zoneSeeded new Zonal BankAccount row(s) (" . count($zones) . " zones checked).\n";

    // 3b. Divisional accounts
    $divisions = $pdo->query("SELECT division_id, division_name FROM `Division`")->fetchAll();
    $divSeeded = 0;
    foreach ($divisions as $d) {
        $checkBank->execute(['Divisional', $d->division_id]);
        if (!$checkBank->fetchColumn()) {
            $acctNo = '7' . str_pad($d->division_id, 3, '0', STR_PAD_LEFT) . '-0022-' . rand(1000, 9999) . '-' . rand(1000, 9999);
            $insertBank->execute([
                'Divisional',
                $d->division_id,
                'Bank of Ceylon',
                $d->division_name . ' Divisional Branch',
                $acctNo,
                $d->division_name . ' Divisional Treasury Fund',
                'Cheque',
            ]);
            $divSeeded++;
        }
    }
    echo "Seeded $divSeeded new Divisional BankAccount row(s) (" . count($divisions) . " divisions checked).\n";

    // -------------------------------------------------------------
    // 4. CREATE VOIDREQUEST TABLE
    //    Serves both directions: Club -> Division and Division -> Zonal.
    //    "Locking" an entry while a request is pending is enforced at the
    //    UI/controller layer (disable Edit while an open VoidRequest exists
    //    for that entry_id) — no lock column needed here.
    // -------------------------------------------------------------
    $pdo->exec("CREATE TABLE IF NOT EXISTS `VoidRequest` (
        `void_request_id` INT AUTO_INCREMENT PRIMARY KEY,
        `entry_id`         INT NOT NULL,
        `requested_by`     INT NOT NULL,
        `requested_to`     INT NOT NULL,
        `scope_direction`  ENUM('ClubToDivision','DivisionToZonal') NOT NULL,
        `reason`           TEXT NOT NULL,
        `status`           ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
        `remarks`          TEXT NULL,
        `requested_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `decided_at`       TIMESTAMP NULL DEFAULT NULL,
        `decided_by`       INT NULL,
        CONSTRAINT `fk_void_request_entry`
            FOREIGN KEY (`entry_id`)
            REFERENCES `LedgerEntry`(`entry_id`)
            ON UPDATE CASCADE
            ON DELETE RESTRICT,
        CONSTRAINT `fk_void_request_requested_by`
            FOREIGN KEY (`requested_by`)
            REFERENCES `User`(`user_id`)
            ON DELETE RESTRICT,
        CONSTRAINT `fk_void_request_requested_to`
            FOREIGN KEY (`requested_to`)
            REFERENCES `User`(`user_id`)
            ON DELETE RESTRICT,
        CONSTRAINT `fk_void_request_decided_by`
            FOREIGN KEY (`decided_by`)
            REFERENCES `User`(`user_id`)
            ON DELETE RESTRICT,
        INDEX idx_entry (`entry_id`),
        INDEX idx_status (`status`),
        INDEX idx_requested_to (`requested_to`, `status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Table `VoidRequest` ready.\n";

    echo "\n=== Divisional Treasurer Finance Schema Migration Completed Successfully ===\n";
    echo "NOTE: This script does not touch Auth.php. Add the DivisionalTreasurer\n";
    echo "case to the redirect switch and a requireRole guard separately (see\n";
    echo "ManageEvents.php / EventApproval.php for the existing pattern).\n";

} catch (Exception $e) {
    die("Migration error: " . $e->getMessage() . "\n");
}
