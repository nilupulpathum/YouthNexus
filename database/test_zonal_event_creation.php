<?php

/**
 * Local regression test for the zonal event creation lifecycle (Plan 02).
 *
 * Covers: secretary create -> pending in zone lists -> coordinator approve ->
 * secretary edits approved event -> back to pending -> coordinator rejects ->
 * secretary edits + resubmits rejected event -> pending -> coordinator rejects
 * again -> secretary deletes rejected event. Plus scope guards (foreign zone
 * edit/delete refused) and the attendance delete guard.
 *
 * Usage: c:\xampp\php\php.exe database\test_zonal_event_creation.php
 *
 * Synthetic data only. Every row created here is removed before exit.
 */

require_once __DIR__ . '/../app/core/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/models/EventModel.php';

function expectZonalEvent($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$pdo = Database::getInstance()->getConnection();
$eventModel = new EventModel();
$createdEventIds = [];

try {
    echo "=== Zonal Event Creation Regression Test ===\n";

    $zone = $pdo->query(
        "SELECT z.zonal_id FROM Zone z
          JOIN Division d ON d.zonal_id = z.zonal_id
         GROUP BY z.zonal_id HAVING COUNT(*) >= 1
         ORDER BY z.zonal_id LIMIT 1"
    )->fetch();
    expectZonalEvent((bool) $zone, 'The local database needs one zone with a division assignment.');
    $zonalId = (int) $zone->zonal_id;

    $division = $pdo->query(
        "SELECT division_id FROM Division WHERE zonal_id = {$zonalId} ORDER BY division_id LIMIT 1"
    )->fetch();
    expectZonalEvent((bool) $division, 'The chosen zone needs at least one division.');
    $divisionId = (int) $division->division_id;

    $secretaryId = (int) $pdo->query(
        "SELECT user_id FROM User WHERE role = 'ZonalSecretary' AND zonal_id = {$zonalId} ORDER BY user_id LIMIT 1"
    )->fetchColumn();
    if ($secretaryId < 1) {
        $secretaryId = (int) $pdo->query('SELECT user_id FROM User ORDER BY user_id LIMIT 1')->fetchColumn();
    }
    expectZonalEvent($secretaryId > 0, 'The local database needs at least one user.');
    $coordinatorId = (int) $pdo->query(
        "SELECT user_id FROM User WHERE role = 'ZonalCoordinator' AND zonal_id = {$zonalId} ORDER BY user_id LIMIT 1"
    )->fetchColumn();
    if ($coordinatorId < 1) {
        $coordinatorId = $secretaryId;
    }
    $otherZonalId = (int) $pdo->query(
        "SELECT COALESCE(MAX(zonal_id), 0) + 1 FROM Zone"
    )->fetchColumn();

    $suffix = strtoupper(bin2hex(random_bytes(4)));
    $future = (new DateTimeImmutable('+30 days'))->format('Y-m-d H:i:s');
    $futureEnd = (new DateTimeImmutable('+30 days +2 hours'))->format('Y-m-d H:i:s');

    // --- 1. create -> pending -----------------------------------------
    $eventId = $eventModel->createZonalEvent($zonalId, $secretaryId, [
        'title' => "[DEMO-ZE] Lifecycle {$suffix}",
        'type' => 'Workshop',
        'location' => 'Test Hall',
        'start' => $future,
        'end' => $futureEnd,
    ], $divisionId);
    $createdEventIds[] = $eventId;
    expectZonalEvent($eventId > 0, 'The secretary must be able to create a pending zonal event.');

    $row = $eventModel->getZonalEventForSecretary($zonalId, $eventId);
    expectZonalEvent($row && $row->status === 'PendingApproval', 'A new zonal event must be pending approval.');
    expectZonalEvent(
        $eventModel->getZonalEventForSecretary($otherZonalId, $eventId) === false,
        'Another zone must not see this event.'
    );
    echo "1. Secretary create -> pending: PASS\n";

    // --- 2. edit pending stays pending ---------------------------------
    expectZonalEvent(
        $eventModel->updateZonalEvent($zonalId, $eventId, [
            'title' => "[DEMO-ZE] Lifecycle edited {$suffix}",
            'type' => 'Training',
            'location' => 'Edited Hall',
            'start' => $future,
            'end' => $futureEnd,
        ], null) === 1,
        'The secretary must be able to edit a pending event.'
    );
    $row = $eventModel->getZonalEventForSecretary($zonalId, $eventId);
    expectZonalEvent($row && $row->status === 'PendingApproval', 'An edited pending event must stay pending.');
    expectZonalEvent(($row->title ?? '') === "[DEMO-ZE] Lifecycle edited {$suffix}", 'The edited title must be stored.');
    expectZonalEvent(($row->target_scope ?? '') === 'AllInScope', 'Switching to all divisions must clear the target row.');
    expectZonalEvent(count($pdo->query("SELECT target_id FROM EventTarget WHERE event_id = {$eventId}")->fetchAll()) === 0, 'No target row must remain for all-divisions.');
    echo "2. Edit pending stays pending: PASS\n";

    // --- 3. approve -> edit approved returns to pending ----------------
    expectZonalEvent(
        $eventModel->decideZonalEvent($zonalId, $eventId, $coordinatorId, 'approve', 'Looks good.') === 1,
        'The coordinator must be able to approve the event.'
    );
    expectZonalEvent(
        $eventModel->updateZonalEvent($zonalId, $eventId, [
            'title' => "[DEMO-ZE] Lifecycle re-edited {$suffix}",
            'type' => 'Meeting',
            'location' => 'Re-edited Hall',
            'start' => $future,
            'end' => $futureEnd,
        ], $divisionId) === 1,
        'The secretary must be able to edit an approved event.'
    );
    $row = $eventModel->getZonalEventForSecretary($zonalId, $eventId);
    expectZonalEvent($row && $row->status === 'PendingApproval', 'An edited approved event must return to pending approval.');
    expectZonalEvent($row->approved_by === null, 'Re-approval must clear the previous approver.');
    expectZonalEvent(count($pdo->query("SELECT target_id FROM EventTarget WHERE event_id = {$eventId} AND target_division_id = {$divisionId}")->fetchAll()) === 1, 'The division target must be stored.');
    echo "3. Edit approved -> pending again: PASS\n";

    // --- 4. foreign zone cannot edit ------------------------------------
    expectZonalEvent(
        $eventModel->updateZonalEvent($otherZonalId, $eventId, [
            'title' => 'Scope probe',
            'type' => 'Meeting',
            'location' => 'Nowhere',
            'start' => $future,
            'end' => $futureEnd,
        ], null) === 0,
        'Another zone must not edit this event.'
    );
    echo "4. Foreign-zone edit refused: PASS\n";

    // --- 5. reject -> edit + resubmit rejected -> pending ----------------
    expectZonalEvent(
        $eventModel->decideZonalEvent($zonalId, $eventId, $coordinatorId, 'request-changes', 'Add an agenda.') === 1,
        'The coordinator must be able to return the event.'
    );
    $row = $eventModel->getZonalEventForSecretary($zonalId, $eventId);
    expectZonalEvent($row && $row->status === 'Rejected', 'A returned event must be rejected.');
    expectZonalEvent(
        $eventModel->updateZonalEvent($zonalId, $eventId, [
            'title' => "[DEMO-ZE] Lifecycle resubmitted {$suffix}",
            'type' => 'Workshop',
            'location' => 'Resubmitted Hall',
            'start' => $future,
            'end' => $futureEnd,
        ], $divisionId) === 1,
        'The secretary must be able to edit and resubmit a rejected event.'
    );
    $row = $eventModel->getZonalEventForSecretary($zonalId, $eventId);
    expectZonalEvent($row && $row->status === 'PendingApproval', 'A resubmitted event must be pending again.');
    expectZonalEvent($row->rejection_remarks === null, 'Resubmission must clear the coordinator remark.');
    echo "5. Edit + resubmit rejected -> pending: PASS\n";

    // --- 6. reject -> delete ---------------------------------------------
    expectZonalEvent(
        $eventModel->decideZonalEvent($zonalId, $eventId, $coordinatorId, 'request-changes', 'Still not ready.') === 1,
        'The coordinator must be able to reject the resubmitted event.'
    );
    expectZonalEvent(
        $eventModel->deleteRejectedZonalEvent($otherZonalId, $eventId)['deleted'] === false,
        'Another zone must not delete this event.'
    );
    $deleted = $eventModel->deleteRejectedZonalEvent($zonalId, $eventId);
    expectZonalEvent($deleted['deleted'] === true, 'The secretary must be able to delete a rejected event.');
    expectZonalEvent(
        $eventModel->getZonalEventForSecretary($zonalId, $eventId) === false,
        'A deleted event must be gone from its zone.'
    );
    array_pop($createdEventIds);
    echo "6. Delete rejected event: PASS\n";

    // --- 7. attendance blocks delete --------------------------------------
    $guardedId = $eventModel->createZonalEvent($zonalId, $secretaryId, [
        'title' => "[DEMO-ZE] Guarded {$suffix}",
        'type' => 'Workshop',
        'location' => 'Guarded Hall',
        'start' => $future,
        'end' => $futureEnd,
    ], null);
    $createdEventIds[] = $guardedId;
    $eventModel->decideZonalEvent($zonalId, $guardedId, $coordinatorId, 'request-changes', 'Needs work.');
    $attendeeId = (int) $pdo->query('SELECT user_id FROM User ORDER BY user_id LIMIT 1')->fetchColumn();
    $pdo->prepare(
        "INSERT INTO Attendance (event_id, user_id, status, recorded_by) VALUES (?, ?, 'Present', ?)"
    )->execute([$guardedId, $attendeeId, $attendeeId]);
    $blocked = $eventModel->deleteRejectedZonalEvent($zonalId, $guardedId);
    expectZonalEvent($blocked['deleted'] === false && !empty($blocked['blockers']), 'Recorded attendance must block deletion.');
    $pdo->prepare("DELETE FROM Attendance WHERE event_id = ?")->execute([$guardedId]);
    expectZonalEvent(
        $eventModel->deleteRejectedZonalEvent($zonalId, $guardedId)['deleted'] === true,
        'Deletion must succeed once attendance is gone.'
    );
    array_pop($createdEventIds);
    echo "7. Attendance delete guard: PASS\n";

    echo "All zonal event lifecycle checks passed.\n";
} finally {
    foreach ($createdEventIds as $leftoverId) {
        $pdo->prepare("DELETE FROM EventTarget WHERE event_id = ?")->execute([(int) $leftoverId]);
        $pdo->prepare("DELETE FROM Attendance WHERE event_id = ?")->execute([(int) $leftoverId]);
        $pdo->prepare("DELETE FROM Event WHERE event_id = ?")->execute([(int) $leftoverId]);
    }
}
