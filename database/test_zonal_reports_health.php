<?php

/**
 * Local regression test for zonal divisional-parity flows.
 *
 * Covers: zone club-health refresh/list/details/summary, zone flagging
 * (categories per role, zone guard, audit trail), zone report catalogs per
 * role, report create -> preview -> archive -> restore, and zone guards.
 *
 * Usage: c:\xampp\php\php.exe database\test_zonal_reports_health.php
 *
 * Synthetic data only. Every row created here is removed before exit.
 */

require_once __DIR__ . '/../app/core/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/models/DivisionalClubHealthModel.php';
require_once __DIR__ . '/../app/models/ZoneClubHealthModel.php';
require_once __DIR__ . '/../app/models/ZoneReportModel.php';

function expectZonalParity($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$pdo = Database::getInstance()->getConnection();
$health = new ZoneClubHealthModel();
$reports = new ZoneReportModel();
$createdFlagIds = [];
$createdReportIds = [];
$flaggedReset = [];

try {
    echo "=== Zonal Reports + Club Health Regression Test ===\n";

    $zone = $pdo->query(
        "SELECT z.zonal_id FROM Zone z
          JOIN Division d ON d.zonal_id = z.zonal_id
          JOIN Club c ON c.division_id = d.division_id
         GROUP BY z.zonal_id HAVING COUNT(*) >= 1
         ORDER BY z.zonal_id LIMIT 1"
    )->fetch();
    expectZonalParity((bool) $zone, 'The local database needs one zone with a club.');
    $zonalId = (int) $zone->zonal_id;
    $otherZonalId = (int) $pdo->query('SELECT COALESCE(MAX(zonal_id), 0) + 1 FROM Zone')->fetchColumn();

    $coordinatorId = (int) $pdo->query(
        "SELECT user_id FROM User WHERE role = 'ZonalCoordinator' AND zonal_id = {$zonalId} ORDER BY user_id LIMIT 1"
    )->fetchColumn();
    if ($coordinatorId < 1) {
        $coordinatorId = (int) $pdo->query('SELECT user_id FROM User ORDER BY user_id LIMIT 1')->fetchColumn();
    }
    expectZonalParity($coordinatorId > 0, 'The local database needs at least one user.');

    // --- 1. zone health refresh + list ----------------------------------
    $autoBefore = $pdo->query(
        "SELECT health_flag_id FROM ClubHealthFlag WHERE flag_category = 'AutomaticDormancy' AND status IN ('Open','UnderReview')"
    )->fetchAll(PDO::FETCH_COLUMN);
    $scores = $health->refreshZone($zonalId);
    expectZonalParity(count($scores) >= 1, 'Zone health refresh must score the zone clubs.');
    $clubs = $health->getZoneClubs($zonalId, $scores);
    expectZonalParity(count($clubs) === count($scores), 'Every scored club must be listed.');
    $summary = $health->getSummary($clubs);
    expectZonalParity(
        $summary['green'] + $summary['yellow'] + $summary['red'] === count($clubs),
        'Summary bands must cover every club.'
    );
    $autoAfter = $pdo->query(
        "SELECT health_flag_id FROM ClubHealthFlag WHERE flag_category = 'AutomaticDormancy' AND status IN ('Open','UnderReview')"
    )->fetchAll(PDO::FETCH_COLUMN);
    foreach (array_diff($autoAfter, $autoBefore) as $newFlagId) {
        $pdo->prepare('DELETE FROM ClubHealthFlag WHERE health_flag_id = ?')->execute([(int) $newFlagId]);
    }
    echo "1. Zone health refresh + list + summary: PASS\n";

    // --- 2. zone club details + zone guard --------------------------------
    $clubId = (int) $clubs[0]->club_id;
    $details = $health->getZoneClubDetails($zonalId, $clubId);
    expectZonalParity(is_array($details), 'Zone club details must load.');
    foreach (['club', 'score', 'executives', 'events', 'finance', 'flags', 'history'] as $key) {
        expectZonalParity(array_key_exists($key, $details), "Details must include {$key}.");
    }
    expectZonalParity(
        $health->getZoneClubDetails($otherZonalId, $clubId) === null,
        'Another zone must not read this club.'
    );
    echo "2. Zone club details + zone guard: PASS\n";

    // --- 3. flag categories + zone flag lifecycle --------------------------
    expectZonalParity(
        $health->getZonalFlagCategories('ZonalSecretary') === ['EventAttendanceConcern'],
        'The secretary must be limited to event/attendance concerns.'
    );
    expectZonalParity(
        count($health->getZonalFlagCategories('ZonalCoordinator')) === 3,
        'The coordinator must see all concern types.'
    );
    expectZonalParity(
        $health->getZonalFlagCategories('ZonalTreasurer') === ['FinancialConcern'],
        'The treasurer must be limited to financial concerns.'
    );
    $priorFlagged = (int) $pdo->query("SELECT flagged FROM Club WHERE club_id = {$clubId}")->fetchColumn();
    $health->raiseZoneFlag($zonalId, $clubId, $coordinatorId, 'ZonalCoordinator', 'GovernanceConcern', 'Synthetic parity check concern.');
    $flagId = (int) $pdo->query('SELECT MAX(health_flag_id) FROM ClubHealthFlag')->fetchColumn();
    $createdFlagIds[] = $flagId;
    $flaggedReset[$clubId] = $priorFlagged;
    expectZonalParity($flagId > 0, 'A zone flag must be recorded.');
    $audit = (int) $pdo->query(
        "SELECT COUNT(*) FROM AuditLog WHERE action_type = 'ClubHealthFlagRaised' AND target_id = {$flagId}"
    )->fetchColumn();
    expectZonalParity($audit === 1, 'A zone flag must write its audit row.');
    $caught = false;
    try {
        $health->raiseZoneFlag($otherZonalId, $clubId, $coordinatorId, 'ZonalCoordinator', 'GovernanceConcern', 'Scope probe concern here.');
    } catch (RuntimeException $e) {
        $caught = true;
    }
    expectZonalParity($caught, 'Another zone must not flag this club.');
    echo "3. Zone flag categories + lifecycle + guard: PASS\n";

    // --- 4. report catalogs per role ---------------------------------------
    $secCatalog = $reports->getCatalog('ZonalSecretary');
    $secTypes = [];
    foreach ($secCatalog as $types) foreach ($types as $t) $secTypes[] = $t->type_name;
    expectZonalParity(in_array('Club Activity Aggregate', $secTypes, true), 'The secretary catalog must offer the aggregate type.');
    $coordTypes = [];
    foreach ($reports->getCatalog('ZonalCoordinator') as $types) foreach ($types as $t) $coordTypes[] = $t->type_name;
    expectZonalParity(in_array('Event Approval Summary', $coordTypes, true) && !in_array('Void Request Activity', $coordTypes, true), 'The coordinator catalog must match its role.');
    $treasTypes = [];
    foreach ($reports->getCatalog('ZonalTreasurer') as $types) foreach ($types as $t) $treasTypes[] = $t->type_name;
    expectZonalParity(in_array('Void Request Activity', $treasTypes, true), 'The treasurer catalog must offer financial types.');
    echo "4. Report catalogs per role: PASS\n";

    // --- 5. create -> preview -> archive -> restore -------------------------
    $typeId = (int) $pdo->query(
        "SELECT report_type_id FROM ReportTypeCatalog WHERE type_name = 'Club Health Summary' LIMIT 1"
    )->fetchColumn();
    expectZonalParity($typeId > 0, 'The catalog must contain Club Health Summary.');
    $start = (new DateTimeImmutable('-90 days'))->format('Y-m-d');
    $end = (new DateTimeImmutable('today'))->format('Y-m-d');
    $reportId = $reports->createReport($zonalId, $coordinatorId, 'ZonalCoordinator', $typeId, $start, $end, 'OnScreen');
    $createdReportIds[] = $reportId;
    expectZonalParity($reportId > 0, 'A zonal report must be created.');
    $report = $reports->getReport($zonalId, $reportId, 'ZonalCoordinator');
    expectZonalParity($report && $report->scope_level === 'Zonal', 'A zonal report must be readable in its zone.');
    $data = $reports->getReportData($zonalId, $report);
    expectZonalParity(isset($data['kpis'], $data['columns'], $data['rows']), 'Preview data must use the library shape.');
    expectZonalParity(
        $reports->getReport($otherZonalId, $reportId, 'ZonalCoordinator') === false,
        'Another zone must not read this report.'
    );
    expectZonalParity($reports->archiveReport($zonalId, $reportId, $coordinatorId, 'ZonalCoordinator'), 'A zonal report must archive.');
    expectZonalParity($reports->restoreReport($zonalId, $reportId, 'ZonalCoordinator'), 'A zonal report must restore.');
    echo "5. Report create -> preview -> archive -> restore: PASS\n";

    echo "All zonal parity checks passed.\n";
} finally {
    foreach ($createdReportIds as $reportId) {
        $pdo->prepare('DELETE FROM Report WHERE report_id = ?')->execute([(int) $reportId]);
    }
    foreach ($createdFlagIds as $flagId) {
        $pdo->prepare('DELETE FROM Notification WHERE related_entity_type = ? AND related_entity_id = ?')->execute(['ClubHealthFlag', (int) $flagId]);
        $pdo->prepare('DELETE FROM AuditLog WHERE action_type = ? AND target_id = ?')->execute(['ClubHealthFlagRaised', (int) $flagId]);
        $pdo->prepare('DELETE FROM ClubHealthFlag WHERE health_flag_id = ?')->execute([(int) $flagId]);
    }
    foreach ($flaggedReset as $clubId => $prior) {
        $pdo->prepare('UPDATE Club SET flagged = ? WHERE club_id = ?')->execute([(int) $prior, (int) $clubId]);
    }
}
