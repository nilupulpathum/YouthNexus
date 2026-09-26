<?php
/**
 * Migration: Social CV skill catalog + endorsements (OUTSIDE item 1 follow-up).
 *
 * Creates:
 *   Skill       - competency catalog seeded from the live distinct
 *                 Event.event_type values (idempotent, re-run safe).
 *   Endorsement - member endorsements with a one-per-(member, endorser) rule
 *                 enforced by UNIQUE(member_user_id, endorser_user_id).
 *
 * No demo rows. Visualization seeds live in database/seed_cv_demo.php
 * (local demo DB only, removable with --clean). Non-destructive.
 *
 * Usage: c:\xampp\php\php.exe database/migrate_cv_skill_endorsement.php
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
        "CREATE TABLE IF NOT EXISTS Skill (
            skill_id    INT AUTO_INCREMENT PRIMARY KEY,
            name        VARCHAR(100) NOT NULL UNIQUE,
            icon        VARCHAR(30) NOT NULL DEFAULT 'check',
            description VARCHAR(255) NULL,
            INDEX idx_skill_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    echo "Table `Skill` ready." . PHP_EOL;

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS Endorsement (
            endorsement_id   INT AUTO_INCREMENT PRIMARY KEY,
            member_user_id   INT NOT NULL,
            endorser_user_id INT NOT NULL,
            endorser_role    VARCHAR(60) NOT NULL DEFAULT '',
            text             VARCHAR(1000) NOT NULL,
            created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_endorsement_pair (member_user_id, endorser_user_id),
            FOREIGN KEY (member_user_id) REFERENCES User(user_id),
            FOREIGN KEY (endorser_user_id) REFERENCES User(user_id),
            INDEX idx_endorsement_member (member_user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    echo "Table `Endorsement` ready." . PHP_EOL;

    // Catalog rows mirror the live event-type vocabulary so skill badges can
    // only ever name categories that really exist. INSERT IGNORE keeps this
    // re-run safe and preserves any owner-added rows.
    $iconFor = [
        'Leadership'        => 'award',
        'Training'          => 'user',
        'Community Service' => 'heart',
        'Workshop'          => 'calendar',
        'Meeting'           => 'check',
    ];
    $types = $pdo->query(
        "SELECT DISTINCT event_type FROM Event
         WHERE event_type IS NOT NULL AND TRIM(event_type) <> ''"
    )->fetchAll(PDO::FETCH_COLUMN);
    $seeded = 0;
    $ins = $pdo->prepare("INSERT IGNORE INTO Skill (name, icon) VALUES (?, ?)");
    foreach ($types as $type) {
        $type = trim((string) $type);
        if ($type === '') {
            continue;
        }
        $ins->execute([$type, $iconFor[$type] ?? 'check']);
        $seeded += $ins->rowCount();
    }
    echo "Skill catalog in sync ({$seeded} new rows, " . count($types) . " live event types)." . PHP_EOL;

    echo PHP_EOL . "=== CV Skill + Endorsement Migration Completed Successfully ===" . PHP_EOL;
} catch (Throwable $exception) {
    echo "MIGRATION FAILED: " . $exception->getMessage() . PHP_EOL;
    exit(1);
}
