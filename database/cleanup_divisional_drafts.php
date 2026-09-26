<?php

require_once __DIR__ . '/../app/core/config.php';

$apply = in_array('--apply', $argv, true);
$days = 180;
foreach ($argv as $argument) {
    if (preg_match('/^--days=(\d+)$/', $argument, $matches)) {
        $days = max(30, min(730, (int) $matches[1]));
    }
}

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $eventQuery = $pdo->query(
        "SELECT event_id, title, created_at FROM Event
         WHERE organizer_division_id IS NOT NULL AND status = 'Draft'
           AND created_at < DATE_SUB(NOW(), INTERVAL {$days} DAY)"
    );
    $events = $eventQuery->fetchAll();
    $announcementQuery = $pdo->query(
        "SELECT announcement_id, title, created_at FROM Announcement
         WHERE level = 'Divisional' AND status = 'Draft' AND deleted_at IS NULL
           AND created_at < DATE_SUB(NOW(), INTERVAL {$days} DAY)"
    );
    $announcements = $announcementQuery->fetchAll();

    echo count($events) . " divisional event draft(s) and " . count($announcements) . " announcement draft(s) are older than {$days} days.\n";
    if (!$apply) {
        echo "Dry run only. Re-run with --apply after reviewing the counts.\n";
        exit(0);
    }

    $pdo->beginTransaction();
    $eventUpdate = $pdo->prepare(
        "UPDATE Event SET status = 'Withdrawn', lifecycle_reason = 'Automatic abandoned draft cleanup',
            lifecycle_requested_at = NOW()
         WHERE event_id = ? AND status = 'Draft'"
    );
    foreach ($events as $event) $eventUpdate->execute([$event->event_id]);
    $announcementUpdate = $pdo->prepare(
        "UPDATE Announcement SET deleted_at = NOW(), lifecycle_reason = 'Automatic abandoned draft cleanup',
            lifecycle_changed_at = NOW()
         WHERE announcement_id = ? AND status = 'Draft' AND deleted_at IS NULL"
    );
    foreach ($announcements as $announcement) $announcementUpdate->execute([$announcement->announcement_id]);
    $pdo->commit();
    echo "Abandoned divisional drafts were moved out of active work queues.\n";
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, 'Cleanup error: ' . $exception->getMessage() . "\n");
    exit(1);
}
