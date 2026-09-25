<?php
/**
 * Migration Script: Zonal Audit Flags (D11)
 *
 * Extends RedFlag with nullable columns the zonal review lifecycle needs:
 * reference/amount (division report context), note (latest clarification or
 * resolution note) and escalated_at (NYSC escalation marker). Non-destructive.
 *
 * Usage: c:\xampp\php\php.exe database/migrate_zonal_audit_flags.php
 */

require_once __DIR__ . '/../app/core/config.php';

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    $cols = [];
    foreach ($pdo->query("SHOW COLUMNS FROM RedFlag") as $col) {
        $cols[] = $col->Field;
    }
    $add = [
        'reference'    => "ADD COLUMN reference VARCHAR(100) NULL AFTER entry_id",
        'amount'       => "ADD COLUMN amount DECIMAL(15,2) NULL AFTER reference",
        'note'         => "ADD COLUMN note TEXT NULL AFTER description",
        'escalated_at' => "ADD COLUMN escalated_at TIMESTAMP NULL AFTER status",
        'division_id'  => "ADD COLUMN division_id INT NULL AFTER escalated_at, ADD CONSTRAINT fk_redflag_division FOREIGN KEY (division_id) REFERENCES Division(division_id)",
    ];
    foreach ($add as $name => $ddl) {
        if (in_array($name, $cols, true)) {
            echo "Column `{$name}` already exists - skipped." . PHP_EOL;
            continue;
        }
        $pdo->exec("ALTER TABLE RedFlag {$ddl}");
        echo "Added column `{$name}`." . PHP_EOL;
    }
    echo PHP_EOL . "=== Zonal Audit Flags Migration Completed Successfully ===" . PHP_EOL;
} catch (Throwable $exception) {
    echo "MIGRATION FAILED: " . $exception->getMessage() . PHP_EOL;
    exit(1);
}
