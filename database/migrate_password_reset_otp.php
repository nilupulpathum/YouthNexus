<?php
/**
 * Migration: OTP-based password reset.
 *
 * Adds token_hash (hashed email-link token), attempts (brute-force counter)
 * and an index to PasswordReset. Invalidates any legacy reset rows because
 * their otp_code held a link token hashed under the old flow.
 *
 * Idempotent — safe to run repeatedly.
 *
 * Usage: /Applications/XAMPP/xamppfiles/bin/php database/migrate_password_reset_otp.php
 */

if (file_exists(__DIR__ . '/../app/core/config.local.php')) {
    require_once __DIR__ . '/../app/core/config.local.php';
}
require_once __DIR__ . '/../app/core/config.php';

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    $columns = [];
    foreach ($pdo->query("SHOW COLUMNS FROM `PasswordReset`") as $col) {
        $columns[] = $col->Field;
    }

    if (!in_array('token_hash', $columns, true)) {
        $pdo->exec("ALTER TABLE `PasswordReset` ADD COLUMN `token_hash` VARCHAR(255) NULL AFTER `otp_code`");
        echo "Added column `token_hash`." . PHP_EOL;
    } else {
        echo "Column `token_hash` already exists - skipped." . PHP_EOL;
    }

    if (!in_array('attempts', $columns, true)) {
        $pdo->exec("ALTER TABLE `PasswordReset` ADD COLUMN `attempts` TINYINT NOT NULL DEFAULT 0 AFTER `token_hash`");
        echo "Added column `attempts`." . PHP_EOL;
    } else {
        echo "Column `attempts` already exists - skipped." . PHP_EOL;
    }

    // MySQL can implicitly give the first TIMESTAMP column ON UPDATE CURRENT_TIMESTAMP.
    // That would reset the expiry on every failed attempt, instantly expiring the code.
    $pdo->exec("ALTER TABLE `PasswordReset` MODIFY COLUMN `expires_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
    echo "Normalised `expires_at` (removed implicit ON UPDATE)." . PHP_EOL;

    $index = $pdo->query("SHOW INDEX FROM `PasswordReset` WHERE Key_name = 'idx_password_reset_token'")->fetch();
    if (!$index) {
        $pdo->exec("CREATE INDEX `idx_password_reset_token` ON `PasswordReset` (`token_hash`)");
        echo "Added index `idx_password_reset_token`." . PHP_EOL;
    } else {
        echo "Index `idx_password_reset_token` already exists - skipped." . PHP_EOL;
    }

    // Old rows stored a link-token hash in otp_code and have no token_hash.
    $invalidated = $pdo->exec("UPDATE `PasswordReset` SET `is_used` = TRUE WHERE `token_hash` IS NULL AND `is_used` = FALSE");
    echo "Invalidated $invalidated legacy reset record(s)." . PHP_EOL;

    echo PHP_EOL . "=== Password Reset OTP Migration Completed Successfully ===" . PHP_EOL;
} catch (Throwable $exception) {
    echo "MIGRATION FAILED: " . $exception->getMessage() . PHP_EOL;
    exit(1);
}
