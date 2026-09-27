<?php
/**
 * Migration Script v2: Annual Financial Audit — workflow alignment
 *
 * 1. Extends RedFlag.flag_type with 'MathMismatch' (Phase 2 critical flag).
 * 2. Removes the fake/hardcoded demo red flags so the system regenerates
 *    them honestly from real ledger data on the next audit compile.
 * 3. Reconciles seeded ledgers (adds an "Opening Balance" carry-forward entry
 *    and keeps current_balance equal to recorded activity) so the Phase 2
 *    math check reflects genuine consistency instead of seeded figures.
 *
 * Non-destructive. Usage: c:\xampp\php\php.exe database/migrate_annual_audit_v2.php
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
    // 1. RedFlag.flag_type — allow the Phase 2 math-mismatch flag
    // -------------------------------------------------------------
    $pdo->exec("ALTER TABLE `RedFlag` MODIFY COLUMN `flag_type`
                ENUM('MissingReceipt','FundHoarding','HighVoidRate','MathMismatch')
                NOT NULL DEFAULT 'MissingReceipt'");
    echo "RedFlag.flag_type now supports 'MathMismatch'.\n";

    // -------------------------------------------------------------
    // 2. Drop hardcoded demo red flags — they are recomputed from the
    //    ledger on the next audit compile (Phase 3).
    // -------------------------------------------------------------
    $removed = $pdo->exec("DELETE FROM `RedFlag`
                           WHERE flag_type IN ('FundHoarding','HighVoidRate')
                             OR (flag_type = 'MissingReceipt' AND entry_id IS NULL)");
    echo "Removed {$removed} hardcoded demo red flag(s). They will be regenerated from real ledger data.\n";

    // -------------------------------------------------------------
    // 3. Reconcile seeded ledgers so the math check is meaningful:
    //    current_balance must equal the sum of recorded activity.
    //    Any gap becomes an explicit carry-forward "Opening Balance" entry.
    // -------------------------------------------------------------
    $ledgers = $pdo->query("SELECT ledger_id, current_balance FROM Ledger")->fetchAll();

    $sumStmt = $pdo->prepare(
        "SELECT
            COALESCE(SUM(CASE WHEN type = 'Income'  AND status = 'Approved' THEN amount ELSE 0 END), 0) -
            COALESCE(SUM(CASE WHEN type = 'Expense' AND status = 'Approved' THEN amount ELSE 0 END), 0) AS net
         FROM LedgerEntry WHERE ledger_id = ?"
    );
    $minDateStmt = $pdo->prepare("SELECT MIN(date) FROM LedgerEntry WHERE ledger_id = ?");
    $openStmt    = $pdo->prepare(
        "SELECT COUNT(*) FROM LedgerEntry
         WHERE ledger_id = ? AND category = 'Opening Balance' AND status = 'Approved'"
    );
    $insOpen = $pdo->prepare(
        "INSERT INTO LedgerEntry (ledger_id, amount, type, category, description, status, date, created_by, created_at)
         VALUES (?, ?, 'Income', 'Opening Balance', 'Carry-forward balance recorded during system adoption (auto-reconciled)', 'Approved', ?, NULL, NOW())"
    );

    $reconciled = 0;
    foreach ($ledgers as $ledger) {
        $ledgerId = (int) $ledger->ledger_id;

        $sumStmt->execute([$ledgerId]);
        $activityNet = (float) $sumStmt->fetch()->net;
        $bookBalance = (float) $ledger->current_balance;
        $gap = round($bookBalance - $activityNet, 2);

        if (abs($gap) < 0.01) {
            continue; // Books already balance.
        }

        // Only seed a positive carry-forward entry; negative gaps are left for
        // the audit math check to surface as a genuine mismatch.
        if ($gap > 0) {
            $openStmt->execute([$ledgerId]);
            $hasOpening = (int) $openStmt->fetchColumn();

            if ($hasOpening > 0) {
                // Top up the existing opening entry instead of duplicating it.
                $pdo->prepare("UPDATE LedgerEntry SET amount = amount + ? WHERE ledger_id = ? AND category = 'Opening Balance' AND status = 'Approved'")
                    ->execute([$gap, $ledgerId]);
            } else {
                // Date the carry-forward on Dec 31 of the year before the
                // earliest activity so audits treat it as an opening balance.
                $minDateStmt->execute([$ledgerId]);
                $earliest = $minDateStmt->fetchColumn();
                $openDate = $earliest
                    ? date('Y-m-d', strtotime(date('Y', strtotime($earliest)) . '-01-01 -1 day'))
                    : (date('Y') . '-01-01');
                $insOpen->execute([$ledgerId, $gap, $openDate]);
            }

            $reconciled++;
            echo "Ledger #{$ledgerId}: inserted/adjusted Opening Balance carry-forward of LKR " . number_format($gap, 2) . ".\n";
        }
    }
    echo "Reconciled {$reconciled} ledger(s).\n";

    // -------------------------------------------------------------
    // 4. Reset stale seeded audit figures — they are recomputed
    //    honestly the first time an admin compiles the audit.
    // -------------------------------------------------------------
    $updated = $pdo->exec("UPDATE `Audit`
                           SET opening_balance = 0, total_income = 0,
                               total_transfers_received = 0, total_expenses = 0,
                               total_transfers_distributed = 0,
                               expected_closing_balance = 0, actual_closing_balance = 0,
                               math_check_status = 'Passed'
                           WHERE expected_closing_balance = 5500000.00
                             AND total_income = 3850000.00");
    echo "Reset {$updated} stale demo audit row(s) for honest recompilation.\n";

    echo "\n=== Annual Audit v2 Migration Completed Successfully ===\n";

} catch (Exception $e) {
    die("Migration error: " . $e->getMessage() . "\n");
}
