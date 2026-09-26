<?php
/**
 * Migration: member event RSVP store (OUTSIDE item 6 follow-up).
 *
 * Creates EventRsvp: one response per (member, event) pair, enforced by
 * UNIQUE(user_id, event_id); re-responding revises the row. Deliberately
 * separate from Attendance (secretary-marked actuals) so self-RSVP never
 * pollutes attendance stats. Non-destructive, re-run safe.
 *
 * Usage: c:\xampp\php\php.exe database/migrate_event_rsvp.php
 */

require_once __DIR__ . '/../app/core/config.php';

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS EventRsvp (
            rsvp_id    INT AUTO_INCREMENT PRIMARY KEY,
            user_id    INT NOT NULL,
            event_id   INT NOT NULL,
            response   ENUM('attending','declined') NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_rsvp_pair (user_id, event_id),
            FOREIGN KEY (user_id) REFERENCES User(user_id),
            FOREIGN KEY (event_id) REFERENCES Event(event_id),
            INDEX idx_rsvp_event (event_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    echo "Table `EventRsvp` ready." . PHP_EOL;

    echo PHP_EOL . "=== Event RSVP Migration Completed Successfully ===" . PHP_EOL;
} catch (Throwable $exception) {
    echo "MIGRATION FAILED: " . $exception->getMessage() . PHP_EOL;
    exit(1);
}
