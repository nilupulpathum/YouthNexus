<?php
/**
 * Club disbandment columns — the NYSC ClubHealthModel reads/writes
 * Club.disband_reason, Club.disbanded_at and Club.disbanded_by
 * (getNationwideClubs + executeDisband) but no earlier migration
 * created them, so /clubhealth 500s with "Unknown column 'c.disband_reason'".
 *
 * Re-run safe: only adds columns that are missing.
 * Usage: C:\xampp\php\php.exe database/migrate_club_disband_columns.php
 */

require_once __DIR__ . '/../app/core/config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $columns = [];
    foreach ($pdo->query('SHOW COLUMNS FROM `Club`') as $column) {
        $columns[$column['Field']] = true;
    }

    $extra = [
        'disband_reason' => 'TEXT NULL',
        'disbanded_at'   => 'DATETIME NULL',
        'disbanded_by'   => 'INT NULL',
    ];
    foreach ($extra as $name => $definition) {
        if (!isset($columns[$name])) {
            $pdo->exec("ALTER TABLE `Club` ADD COLUMN `$name` $definition");
            echo "Added Club.$name.\n";
        }
    }

    echo "Club disbandment columns ready.\n";
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
