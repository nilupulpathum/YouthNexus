<?php
/**
 * Migration Script: Notification Preferences (CRUD-03)
 *
 * Adds the per-user "Send Announcements via Email" preference column to
 * User. "Enable Email Notifications" already exists as
 * email_notifications_enabled. Non-destructive and idempotent.
 *
 * Usage: c:\xampp\php\php.exe database/migrate_notification_prefs.php
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
    foreach ($pdo->query("SHOW COLUMNS FROM `User`") as $col) {
        $cols[] = $col->Field;
    }

    if (in_array('announcement_email_enabled', $cols, true)) {
        echo "Column `announcement_email_enabled` already exists - skipped." . PHP_EOL;
    } else {
        $pdo->exec("ALTER TABLE `User` ADD COLUMN announcement_email_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER email_notifications_enabled");
        echo "Added column `announcement_email_enabled`." . PHP_EOL;
    }

    echo PHP_EOL . "=== Notification Preferences Migration Completed Successfully ===" . PHP_EOL;
} catch (Throwable $exception) {
    echo "MIGRATION FAILED: " . $exception->getMessage() . PHP_EOL;
    exit(1);
}
