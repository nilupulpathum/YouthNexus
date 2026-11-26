<?php
/**
 * Report type catalog cleanup.
 *
 * Applies the agreed Manage Report catalogue changes globally:
 *   - rename "Divisional Financial Summary" -> "Financial Summary"
 *   - rename "Club Fund Allocation Report" -> "fund allocation report"
 *   - remove retired types (consolidated financial rollup, audit compliance,
 *     allocation vs utilisation, void rate, club activity aggregate,
 *     health score trend, club registration status, login activity)
 *
 * Existing generated reports that reference a renamed type are repointed to
 * the new catalog entry so their history remains readable.
 *
 * Usage: php database/migrate_report_catalog_cleanup.php
 */

require_once __DIR__ . '/../app/core/config.php';

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    echo "Connected to database " . DB_NAME . ".\n";

    $findType = $pdo->prepare(
        'SELECT report_type_id FROM ReportTypeCatalog WHERE category = ? AND type_name = ? LIMIT 1'
    );

    $renames = [
        ['Financial', 'Divisional Financial Summary', 'Financial Summary'],
        ['Financial', 'Club Fund Allocation Report', 'fund allocation report'],
    ];

    foreach ($renames as [$category, $oldName, $newName]) {
        $findType->execute([$category, $oldName]);
        $oldId = (int) ($findType->fetchColumn() ?: 0);
        if ($oldId === 0) {
            continue;
        }

        $findType->execute([$category, $newName]);
        $newId = (int) ($findType->fetchColumn() ?: 0);

        if ($newId > 0) {
            // Keep reports linked to the surviving catalog entry, then drop the duplicate.
            $pdo->prepare('UPDATE Report SET report_type_id = ? WHERE report_type_id = ?')->execute([$newId, $oldId]);
            $pdo->prepare('DELETE FROM ReportTypeCatalog WHERE report_type_id = ?')->execute([$oldId]);
            echo "Merged '{$oldName}' into '{$newName}'.\n";
        } else {
            $pdo->prepare(
                'UPDATE ReportTypeCatalog SET type_name = ? WHERE report_type_id = ?'
            )->execute([$newName, $oldId]);
            echo "Renamed '{$oldName}' to '{$newName}'.\n";
        }
    }

    $removals = [
        ['Financial', 'National Consolidated Financial Rollup'],
        ['Financial', 'Club Audit Compliance Report'],
        ['Financial', 'Fund Allocation vs Utilisation'],
        ['Financial', 'Void Rate Report'],
        ['Events', 'Club Activity Aggregate'],
        ['Club Health', 'Health Score Trend'],
        ['User Interactions', 'Club Registration Status'],
        ['User Interactions', 'Login Activity'],
    ];

    $deleteReportShares = $pdo->prepare(
        'DELETE FROM ReportShare WHERE report_id IN (SELECT report_id FROM Report WHERE report_type_id = ?)'
    );
    $deleteReports = $pdo->prepare('DELETE FROM Report WHERE report_type_id = ?');
    $deleteType = $pdo->prepare('DELETE FROM ReportTypeCatalog WHERE report_type_id = ?');

    foreach ($removals as [$category, $typeName]) {
        $findType->execute([$category, $typeName]);
        $typeId = (int) ($findType->fetchColumn() ?: 0);
        if ($typeId === 0) {
            continue;
        }

        $deleteReportShares->execute([$typeId]);
        $deleteReports->execute([$typeId]);
        $deleteType->execute([$typeId]);
        echo "Removed retired report type '{$typeName}'.\n";
    }

    $sortOrders = [
        ['Financial', 'Financial Summary', 1],
        ['Financial', 'fund allocation report', 2],
        ['Financial', 'Void Request Activity', 3],
        ['Financial', 'Income Summary', 4],
        ['Financial', 'Expense Summary', 5],
        ['Events', 'Event Approval Summary', 1],
        ['Events', 'Event Status Summary', 2],
        ['Events', 'Event Attendance Rate', 3],
        ['Club Health', 'Club Health Summary', 1],
        ['Club Health', 'Health Status Distribution', 2],
        ['User Interactions', 'Role Distribution', 1],
        ['User Interactions', 'Announcement Read Rate', 2],
        ['User Interactions', 'Volunteer Hours', 3],
    ];

    $setSortOrder = $pdo->prepare(
        'UPDATE ReportTypeCatalog SET sort_order = ? WHERE category = ? AND type_name = ?'
    );
    foreach ($sortOrders as [$category, $typeName, $sortOrder]) {
        $setSortOrder->execute([$sortOrder, $category, $typeName]);
    }

    echo "Report catalog cleanup completed.\n";
} catch (Throwable $exception) {
    die('Migration error: ' . $exception->getMessage() . "\n");
}
