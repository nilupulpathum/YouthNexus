<?php
/**
 * Migration Script: Align `Notification` table to the canonical application contract
 *
 * PROBLEM
 *   Four migrations create `Notification` with two different shapes:
 *     - migrate_national_analytics.php  → LEGACY:  user_id, title, is_read
 *     - migrate_manage_assets.php       → CANONICAL: recipient_id, related_entity_*,
 *     - migrate_annual_audit.php          read_status
 *     - migrate_divisional_club_audit.php
 *   Because every definition uses CREATE TABLE IF NOT EXISTS, whichever runs
 *   FIRST wins. run_migrations.php runs migrate_national_analytics.php before
 *   the other three, so databases built through the runner carry the legacy
 *   shape — and all 19 canonical INSERTs (14 models) fail with:
 *     SQLSTATE[42S22] ... Unknown column 'recipient_id' in 'field list'
 *
 * WHAT THIS SCRIPT DOES (idempotent, safe to re-run)
 *   1. Creates the canonical table if it does not exist at all.
 *   2. Renames user_id  -> recipient_id (data preserved) if needed.
 *   3. Renames is_read  -> read_status  (data preserved) if needed.
 *   4. Adds related_entity_type / related_entity_id if missing.
 *   5. Makes legacy `title` nullable (it is NOT NULL but no canonical INSERT
 *      supplies it — keeping it NOT NULL would break strict-mode inserts).
 *   6. Removes orphan rows, then adds the FK to User + the (recipient_id, read_status)
 *      index used by the canonical definitions.
 *
 * Usage: c:\xampp\php\php.exe database/migrate_notification_align.php
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
    // 0. CREATE the table if it does not exist (canonical definition,
    //    identical to migrate_annual_audit.php)
    // -------------------------------------------------------------
    $tableCheck = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Notification'"
    );
    $tableCheck->execute();

    if ((int)$tableCheck->fetchColumn() === 0) {
        $pdo->exec("
            CREATE TABLE `Notification` (
                `notification_id`     INT AUTO_INCREMENT PRIMARY KEY,
                `recipient_id`        INT NOT NULL,
                `type`                VARCHAR(50) NOT NULL DEFAULT 'General',
                `message`             VARCHAR(500) NOT NULL,
                `related_entity_type` VARCHAR(50) DEFAULT NULL,
                `related_entity_id`   INT DEFAULT NULL,
                `read_status`         BOOLEAN NOT NULL DEFAULT FALSE,
                `created_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`recipient_id`) REFERENCES `User`(`user_id`) ON DELETE CASCADE,
                INDEX idx_recipient (`recipient_id`, `read_status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "Notification table was missing — created canonical schema. Done.\n";
        exit(0);
    }
    echo "Notification table found — inspecting shape...\n";

    // -------------------------------------------------------------
    // 1. Inspect the CURRENT columns
    // -------------------------------------------------------------
    $cols = $pdo->query("SHOW COLUMNS FROM `Notification`")->fetchAll(PDO::FETCH_COLUMN);
    $has  = fn(string $c): bool => in_array($c, $cols, true);

    // -------------------------------------------------------------
    // 2. recipient_id  (rename user_id -> recipient_id, preserving data)
    // -------------------------------------------------------------
    if (!$has('recipient_id')) {
        if ($has('user_id')) {
            $pdo->exec("ALTER TABLE `Notification` ADD COLUMN `recipient_id` INT NULL AFTER `notification_id`");
            $pdo->exec("UPDATE `Notification` SET `recipient_id` = `user_id`");
            $pdo->exec("ALTER TABLE `Notification` MODIFY `recipient_id` INT NOT NULL");
            $pdo->exec("ALTER TABLE `Notification` DROP COLUMN `user_id`"); // drops legacy idx_user_read with it
            echo "Renamed user_id -> recipient_id (data preserved).\n";
        } else {
            $pdo->exec("ALTER TABLE `Notification` ADD COLUMN `recipient_id` INT NOT NULL AFTER `notification_id`");
            echo "Added missing column recipient_id.\n";
        }
    } else {
        echo "recipient_id already present, skipping.\n";
    }

    // -------------------------------------------------------------
    // 3. related_entity_type / related_entity_id
    // -------------------------------------------------------------
    if (!$has('related_entity_type')) {
        $after = $has('message') ? 'message' : 'type';
        $pdo->exec("ALTER TABLE `Notification` ADD COLUMN `related_entity_type` VARCHAR(50) DEFAULT NULL AFTER `{$after}`");
        echo "Added column related_entity_type.\n";
    }
    if (!$has('related_entity_id')) {
        $pdo->exec("ALTER TABLE `Notification` ADD COLUMN `related_entity_id` INT DEFAULT NULL AFTER `related_entity_type`");
        echo "Added column related_entity_id.\n";
    }

    // -------------------------------------------------------------
    // 4. read_status  (rename is_read -> read_status, preserving data)
    // -------------------------------------------------------------
    if (!$has('read_status')) {
        if ($has('is_read')) {
            $pdo->exec("ALTER TABLE `Notification` ADD COLUMN `read_status` BOOLEAN NOT NULL DEFAULT FALSE AFTER `related_entity_id`");
            $pdo->exec("UPDATE `Notification` SET `read_status` = `is_read`");
            $pdo->exec("ALTER TABLE `Notification` DROP COLUMN `is_read`");
            echo "Renamed is_read -> read_status (data preserved).\n";
        } else {
            $pdo->exec("ALTER TABLE `Notification` ADD COLUMN `read_status` BOOLEAN NOT NULL DEFAULT FALSE AFTER `related_entity_id`");
            echo "Added missing column read_status.\n";
        }
    } else {
        echo "read_status already present, skipping.\n";
    }

    // -------------------------------------------------------------
    // 5. Legacy `title` is NOT NULL but no canonical INSERT supplies it
    //    → make it nullable so strict-mode INSERTs succeed.
    //    (Nothing in app/ reads title. Optional cleanup: DROP COLUMN title.)
    // -------------------------------------------------------------
    $cols = $pdo->query("SHOW COLUMNS FROM `Notification`")->fetchAll(PDO::FETCH_COLUMN); // refresh
    if (in_array('title', $cols, true)) {
        $pdo->exec("ALTER TABLE `Notification` MODIFY `title` VARCHAR(255) NULL DEFAULT NULL");
        echo "Legacy column `title` made nullable (kept, unused by the app).\n";
    }

    // -------------------------------------------------------------
    // 6. Orphan cleanup, then FK + index
    // -------------------------------------------------------------
    $fkCheck = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'Notification'
           AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
    );
    $fkCheck->execute();

    if ((int)$fkCheck->fetchColumn() === 0) {
        $orphans = $pdo->query(
            "SELECT COUNT(*) FROM Notification n
             LEFT JOIN User u ON u.user_id = n.recipient_id
             WHERE u.user_id IS NULL"
        )->fetchColumn();

        if ((int)$orphans > 0) {
            $pdo->exec(
                "DELETE n FROM Notification n
                 LEFT JOIN User u ON u.user_id = n.recipient_id
                 WHERE u.user_id IS NULL"
            );
            echo "Removed {$orphans} orphan notification(s) with no matching user (required before adding the FK).\n";
        }

        $pdo->exec(
            "ALTER TABLE `Notification`
             ADD CONSTRAINT `fk_notification_recipient`
             FOREIGN KEY (`recipient_id`) REFERENCES `User`(`user_id`) ON DELETE CASCADE"
        );
        echo "Added FK fk_notification_recipient -> User(user_id).\n";
    } else {
        echo "Foreign key already present, skipping.\n";
    }

    $idxCheck = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'Notification'
           AND INDEX_NAME = 'idx_recipient'"
    );
    $idxCheck->execute();

    if ((int)$idxCheck->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE `Notification` ADD INDEX `idx_recipient` (`recipient_id`, `read_status`)");
        echo "Added INDEX idx_recipient (recipient_id, read_status).\n";
    } else {
        echo "Index idx_recipient already present, skipping.\n";
    }

    // -------------------------------------------------------------
    // 7. FINAL VERIFICATION — print the converged shape
    // -------------------------------------------------------------
    echo "\nFinal Notification columns:\n";
    foreach ($pdo->query("SHOW COLUMNS FROM `Notification`") as $c) {
        echo "  - {$c->Field} ({$c->Type})\n";
    }

    $required = ['notification_id', 'recipient_id', 'type', 'message',
                 'related_entity_type', 'related_entity_id', 'read_status', 'created_at'];
    $final = $pdo->query("SHOW COLUMNS FROM `Notification`")->fetchAll(PDO::FETCH_COLUMN);
    $missing = array_diff($required, $final);

    echo $missing
        ? "\nFAILED: still missing: " . implode(', ', $missing) . "\n"
        : "\n=== Success: Notification now matches the canonical application contract ===\n";

    if ($missing) exit(1);

} catch (Throwable $e) {
    echo 'Migration failed: ', $e->getMessage(), "\n";
    exit(1);
}
