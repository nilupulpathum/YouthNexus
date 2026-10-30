<?php

/**
 * Migration: Recipient Bank Account for FundAllocation
 * Adds recipient_bank_account_id to FundAllocation table and backfills existing allocations.
 */

require_once __DIR__ . '/../app/core/config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    echo "Connected to database.\n";

    // 1. Check if recipient_bank_account_id column exists in FundAllocation
    $columns = $pdo->query("SHOW COLUMNS FROM `FundAllocation`")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('recipient_bank_account_id', $columns)) {
        $pdo->exec("ALTER TABLE `FundAllocation`
                    ADD COLUMN `recipient_bank_account_id` INT NULL AFTER `source_bank_account_id`,
                    ADD INDEX `idx_recipient_bank` (`recipient_bank_account_id`),
                    ADD CONSTRAINT `fk_fund_alloc_recipient_ba`
                        FOREIGN KEY (`recipient_bank_account_id`)
                        REFERENCES `BankAccount` (`bank_account_id`)
                        ON DELETE SET NULL");
        echo "Column `recipient_bank_account_id` added to `FundAllocation`.\n";
    } else {
        echo "Column `recipient_bank_account_id` already exists in `FundAllocation`.\n";
    }

    // 2. Backfill existing FundAllocation records where recipient_bank_account_id is NULL
    $stmtBackfill = $pdo->query("
        SELECT fa.allocation_id, fa.to_id, ba.bank_account_id
        FROM `FundAllocation` fa
        JOIN `BankAccount` ba ON (ba.owner_level = 'Zonal' AND ba.owner_id = fa.to_id AND ba.status = 'Active')
        WHERE fa.recipient_bank_account_id IS NULL
    ");
    $toUpdate = $stmtBackfill->fetchAll();

    if (!empty($toUpdate)) {
        $stmtUp = $pdo->prepare("UPDATE `FundAllocation` SET `recipient_bank_account_id` = ? WHERE `allocation_id` = ?");
        foreach ($toUpdate as $row) {
            $stmtUp->execute([$row['bank_account_id'], $row['allocation_id']]);
        }
        echo "Backfilled " . count($toUpdate) . " FundAllocation rows with matching zonal BankAccount.\n";
    } else {
        echo "No backfill required.\n";
    }

    echo "Migration completed successfully.\n";

} catch (Exception $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
    exit(1);
}
