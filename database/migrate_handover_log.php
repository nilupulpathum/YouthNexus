<?php
/**
 * Migration Script: Leadership Handover Log (D6)
 *
 * Creates the HandoverLog table recording presidency transfers:
 * outgoing/incoming users, club scope, acknowledged inventory checklist
 * (JSON catalog item ids) and audit trail. Non-destructive.
 *
 * Usage: c:\xampp\php\php.exe database/migrate_handover_log.php
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
        "CREATE TABLE IF NOT EXISTS HandoverLog (
            handover_id      INT AUTO_INCREMENT PRIMARY KEY,
            club_id          INT NOT NULL,
            outgoing_user_id INT NOT NULL,
            incoming_user_id INT NOT NULL,
            asset_checklist  TEXT NULL,
            created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (club_id) REFERENCES Club(club_id),
            FOREIGN KEY (outgoing_user_id) REFERENCES User(user_id),
            FOREIGN KEY (incoming_user_id) REFERENCES User(user_id),
            INDEX idx_handover_club (club_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    echo "Table `HandoverLog` ready." . PHP_EOL;
    echo PHP_EOL . "=== Handover Log Migration Completed Successfully ===" . PHP_EOL;
} catch (Throwable $exception) {
    echo "MIGRATION FAILED: " . $exception->getMessage() . PHP_EOL;
    exit(1);
}
