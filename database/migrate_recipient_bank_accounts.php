<?php
/**
 * Migration Script: Recipient Bank Accounts on FundAllocation
 *
 * Adds `FundAllocation.recipient_bank_account_id` — the BankAccount that
 * actually receives an allocation's funds — and repairs BankAccount schema
 * drift (`gateway_type`, `verification_status`) so older databases converge
 * with what FundTransferModel and migrate_fund_transfer.php expect.
 *
 * Recipient account rules:
 *   - Only Zonal allocations (to_level = 'Zonal') are backfilled, because
 *     `to_id` in FundAllocation references different entities per level
 *     (Zone id when 'Zonal', Division id when 'Divisional', Club id when
 *     'Club'). Backfilling without the `to_level = 'Zonal'` guard would
 *     wrongly assign a zonal bank account as the recipient of
 *     Zone→Division and Division→Club allocations.
 *   - Divisional / Club allocations get their recipient via the application
 *     layer (DivisionalAllocationModel / Club flows) — never by this backfill.
 *
 * Idempotent and safe to re-run: every step checks current state first, and
 * the cleanup in step 4 self-heals any database that was backfilled by an
 * earlier buggy version of this script (recipient set on non-Zonal rows).
 *
 * Usage: c:\xampp\php\php.exe database/migrate_recipient_bank_accounts.php
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
    // 0. SAFETY CHECK — the tables this script touches must exist
    // -------------------------------------------------------------
    $tableCheck = $pdo->prepare("
        SELECT COUNT(*)
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
    ");

    foreach (['FundAllocation', 'BankAccount'] as $table) {
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
    // 1. REPAIR BANKACCOUNT SCHEMA DRIFT — add gateway_type /
    //    verification_status if this database was built before those
    //    columns existed (migrate_fund_transfer.php's CREATE TABLE
    //    includes them; older / partially-migrated databases may not).
    // -------------------------------------------------------------
    $baCols = $pdo->query("SHOW COLUMNS FROM `BankAccount`")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('gateway_type', $baCols, true)) {
        $pdo->exec("ALTER TABLE `BankAccount`
                    ADD COLUMN `gateway_type`
                    ENUM('RTGS','SLIPS','API','Cheque') NOT NULL DEFAULT 'RTGS'
                    AFTER `account_label`");
        echo "Added `gateway_type` column to BankAccount.\n";
    } else {
        echo "BankAccount.gateway_type already present, skipping.\n";
    }

    if (!in_array('verification_status', $baCols, true)) {
        $pdo->exec("ALTER TABLE `BankAccount`
                    ADD COLUMN `verification_status`
                    ENUM('Verified','Unverified') NOT NULL DEFAULT 'Verified'
                    AFTER `gateway_type`");
        echo "Added `verification_status` column to BankAccount.\n";
    } else {
        echo "BankAccount.verification_status already present, skipping.\n";
    }

    // -------------------------------------------------------------
    // 2. ADD FUNDALLOCATION.recipient_bank_account_id
    //    Nullable: source account (source_bank_account_id) is NOT NULL;
    //    the recipient is only known for Zonal destinations until the
    //    app layer sets it for Divisional/Club flows.
    // -------------------------------------------------------------
    $faCols = $pdo->query("SHOW COLUMNS FROM `FundAllocation`")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('recipient_bank_account_id', $faCols, true)) {
        $pdo->exec("ALTER TABLE `FundAllocation`
                    ADD COLUMN `recipient_bank_account_id` INT NULL
                    AFTER `source_bank_account_id`,
                    ADD INDEX `idx_recipient_bank` (`recipient_bank_account_id`),
                    ADD CONSTRAINT `fk_fund_alloc_recipient_ba`
                        FOREIGN KEY (`recipient_bank_account_id`)
                        REFERENCES `BankAccount`(`bank_account_id`)
                        ON DELETE SET NULL");
        echo "Added `recipient_bank_account_id` column to FundAllocation.\n";
    } else {
        echo "FundAllocation.recipient_bank_account_id already present, skipping.\n";
    }

    // -------------------------------------------------------------
    // 3. CLEANUP (self-heal) — clear recipients wrongly backfilled by
    //    an earlier version of this script that omitted the
    //    `to_level = 'Zonal'` guard. Zone→Division and Division→Club
    //    allocations must never carry a zonal recipient account.
    // -------------------------------------------------------------
    $cleared = $pdo->exec(
        "UPDATE `FundAllocation`
         SET `recipient_bank_account_id` = NULL
         WHERE `to_level` <> 'Zonal'
           AND `recipient_bank_account_id` IS NOT NULL"
    );
    echo "Cleanup: cleared {$cleared} wrongly-set non-Zonal recipient(s).\n";

    // -------------------------------------------------------------
    // 4. BACKFILL — Zonal allocations only.
    //    Recipient = the destination zone's active bank account
    //    (lowest bank_account_id wins if a zone somehow has several).
    //    The `to_level = 'Zonal'` guard is REQUIRED: to_id only refers
    //    to a Zone row when to_level is 'Zonal'.
    // -------------------------------------------------------------
    $backfilled = $pdo->exec(
        "UPDATE `FundAllocation` fa
         JOIN (
             SELECT ba1.owner_id, MIN(ba1.bank_account_id) AS bank_account_id
             FROM `BankAccount` ba1
             WHERE ba1.owner_level = 'Zonal'
               AND ba1.status = 'Active'
             GROUP BY ba1.owner_id
         ) ba ON ba.owner_id = fa.to_id
         SET fa.recipient_bank_account_id = ba.bank_account_id
         WHERE fa.to_level = 'Zonal'
           AND fa.recipient_bank_account_id IS NULL"
    );
    echo "Backfill: assigned recipient to {$backfilled} Zonal allocation(s).\n";

    // Rows left NULL are Zonal allocations whose zone has no active
    // BankAccount (Zone seeded without an account) — reported, not failed.
    $pending = $pdo->query(
        "SELECT COUNT(*)
         FROM `FundAllocation`
         WHERE `to_level` = 'Zonal'
           AND `recipient_bank_account_id` IS NULL"
    )->fetchColumn();

    if ((int) $pending > 0) {
        echo "Note: {$pending} Zonal allocation(s) still have no recipient — their zone has no active BankAccount row.\n";
    }

    echo "Done.\n";
} catch (Throwable $e) {
    echo 'Migration failed: ', $e->getMessage(), "\n";
    exit(1);
}
