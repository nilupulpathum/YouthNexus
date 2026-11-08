<?php
/**
 * Migration Script: Enforce One Bank Account per Zonal Office
 *
 * Business rule: one Zone = one BankAccount row (owner_level = 'Zonal'),
 * regardless of Active/Inactive status.
 *
 * Fixes two defects in Fund Transfer -> Manage Bank Account:
 *   1. Administrators could register unlimited accounts for the same zone.
 *   2. The "Target Zonal Office" dropdown listed zones that already had an account.
 *
 * What it does (idempotent, safe to re-run):
 *   Step 1  Detect zones that own more than one BankAccount row.
 *   Step 2  Keep exactly one row per duplicated zone (newest Active wins,
 *           otherwise newest of any status) and repoint every FundAllocation
 *           reference from the removed rows to the keeper.
 *   Step 3  Delete the redundant rows.
 *   Step 4  Add generated column + UNIQUE index enforcing the rule at the DB
 *           level for Zonal rows ONLY (National/Divisional/Club rows keep
 *           zonal_uniq = NULL, and unique indexes allow unlimited NULLs).
 *
 * Requires MySQL >= 5.7 or MariaDB >= 10.2 (indexed generated columns).
 *
 * Usage: c:\xampp\php\php.exe database/migrate_zonal_account_uniqueness.php
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
    // 0. SAFETY CHECK
    // -------------------------------------------------------------
    $tableCheck = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?"
    );

    foreach (['BankAccount', 'FundAllocation'] as $table) {
        $tableCheck->execute([$table]);
        if ((int)$tableCheck->fetchColumn() === 0) {
            throw new RuntimeException(
                "Required table `$table` does not exist. Run migrate_fund_transfer.php first, then retry."
            );
        }
    }
    echo "Required tables found.\n";

    // -------------------------------------------------------------
    // 1. FIND ZONES THAT OWN MORE THAN ONE ACCOUNT
    // -------------------------------------------------------------
    $dupZones = $pdo->query(
        "SELECT owner_id, COUNT(*) AS account_count
         FROM BankAccount
         WHERE owner_level = 'Zonal' AND owner_id IS NOT NULL
         GROUP BY owner_id
         HAVING COUNT(*) > 1"
    )->fetchAll();

    if (empty($dupZones)) {
        echo "No duplicated zonal accounts found — nothing to merge.\n";
    }

    $keeperStmt = $pdo->prepare(
        "SELECT bank_account_id, status, bank_name
         FROM BankAccount
         WHERE owner_level = 'Zonal' AND owner_id = ?
         ORDER BY (status = 'Active') DESC, bank_account_id DESC
         LIMIT 1"
    );

    foreach ($dupZones as $dup) {
        $keeperStmt->execute([(int)$dup->owner_id]);
        $keeper = $keeperStmt->fetch();

        $losersStmt = $pdo->prepare(
            "SELECT bank_account_id FROM BankAccount
             WHERE owner_level = 'Zonal' AND owner_id = ?
               AND bank_account_id <> ?"
        );
        $losersStmt->execute([(int)$dup->owner_id, (int)$keeper->bank_account_id]);
        $loserIds = $losersStmt->fetchAll(PDO::FETCH_COLUMN);

        echo "Zone #{$dup->owner_id}: keeping account #{$keeper->bank_account_id}"
           . " ({$keeper->status}, {$keeper->bank_name}); merging "
           . count($loserIds) . " duplicate(s): " . implode(', ', $loserIds) . "\n";

        foreach ($loserIds as $loserId) {
            // ---------------------------------------------------------
            // 2. REPOINT HISTORY BEFORE DELETING
            //    FundAllocation holds the only two FKs to BankAccount:
            //      - recipient_bank_account_id (ON DELETE SET NULL)
            //      - source_bank_account_id    (ON DELETE RESTRICT)
            //    Repointing explicitly keeps statements meaningful instead
            //    of silently NULLing them.
            // ---------------------------------------------------------
            $pdo->prepare(
                "UPDATE FundAllocation
                 SET recipient_bank_account_id = ?
                 WHERE recipient_bank_account_id = ?"
            )->execute([(int)$keeper->bank_account_id, (int)$loserId]);

            $pdo->prepare(
                "UPDATE FundAllocation
                 SET source_bank_account_id = ?
                 WHERE source_bank_account_id = ?"
            )->execute([(int)$keeper->bank_account_id, (int)$loserId]);

            // ---------------------------------------------------------
            // 3. REMOVE THE REDUNDANT ROW
            // ---------------------------------------------------------
            $pdo->prepare(
                "DELETE FROM BankAccount
                 WHERE bank_account_id = ? AND owner_level = 'Zonal'"
            )->execute([(int)$loserId]);
        }
    }

    // -------------------------------------------------------------
    // 4. ADD THE UNIQUE ENFORCEMENT (Zonal rows only)
    //    zonal_uniq = owner_id for Zonal rows, NULL for everything else.
    //    MySQL/MariaDB unique indexes treat NULLs as distinct, so the
    //    National (owner_id NULL), Divisional and Club accounts written
    //    by other modules are completely unaffected.
    // -------------------------------------------------------------
    $baCols = $pdo->query("SHOW COLUMNS FROM `BankAccount`")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('zonal_uniq', $baCols, true)) {
        $pdo->exec(
            "ALTER TABLE `BankAccount`
             ADD COLUMN `zonal_uniq` INT
                 GENERATED ALWAYS AS (IF(`owner_level` = 'Zonal', `owner_id`, NULL)) STORED"
        );
        echo "Added generated column `zonal_uniq`.\n";
    } else {
        echo "Column `zonal_uniq` already present, skipping.\n";
    }

    $idxCheck = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME   = 'BankAccount'
           AND INDEX_NAME   = 'uq_bankaccount_zonal_owner'"
    );
    $idxCheck->execute();

    if ((int)$idxCheck->fetchColumn() === 0) {
        $pdo->exec(
            "ALTER TABLE `BankAccount`
             ADD UNIQUE KEY `uq_bankaccount_zonal_owner` (`zonal_uniq`)"
        );
        echo "Added UNIQUE KEY `uq_bankaccount_zonal_owner`.\n";
    } else {
        echo "UNIQUE KEY already present, skipping.\n";
    }

    // -------------------------------------------------------------
    // 5. FINAL REPORT
    // -------------------------------------------------------------
    $remaining = $pdo->query(
        "SELECT COUNT(*) FROM (
            SELECT owner_id FROM BankAccount
            WHERE owner_level = 'Zonal' AND owner_id IS NOT NULL
            GROUP BY owner_id HAVING COUNT(*) > 1
         ) AS d"
    )->fetchColumn();

    if ((int)$remaining === 0) {
        echo "\n=== Success: every zone now owns exactly one bank account (DB-enforced) ===\n";
    } else {
        echo "\nWARNING: {$remaining} zone(s) still have duplicates — resolve manually, then re-run.\n";
        exit(1);
    }

} catch (Throwable $e) {
    echo 'Migration failed: ', $e->getMessage(), "\n";
    exit(1);
}
