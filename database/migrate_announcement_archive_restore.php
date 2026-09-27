<?php

// Archive/restore needs the lifecycle status and audit columns on installations
// that have only run the original broadcast-announcement migration.
require_once __DIR__ . '/../app/core/config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $columns = [];
    foreach ($pdo->query('SHOW COLUMNS FROM `Announcement`') as $column) {
        $columns[$column['Field']] = $column['Type'];
    }
    if (!isset($columns['status'])) {
        throw new RuntimeException('Announcement.status is missing. Run the broadcast announcement migration first.');
    }

    if (!str_contains($columns['status'], "'Archived'")) {
        $pdo->exec("ALTER TABLE `Announcement` MODIFY COLUMN `status`
            ENUM('Draft','Published','Retracted','Archived') NOT NULL DEFAULT 'Draft'");
    }

    $extra = [
        'lifecycle_reason' => 'TEXT NULL',
        'lifecycle_changed_by' => 'INT NULL',
        'lifecycle_changed_at' => 'DATETIME NULL',
    ];
    foreach ($extra as $name => $definition) {
        if (!isset($columns[$name])) {
            $pdo->exec("ALTER TABLE `Announcement` ADD COLUMN `$name` $definition");
        }
    }

    echo "Announcement archive/restore schema ready.\n";
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
