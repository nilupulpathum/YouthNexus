<?php
/**
 * Zone attendance — read-only divisional rollup.
 * UI follows the divisional standard (dw-* classes + shared partials).
 */
$e = static function ($value) { return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8'); };

require __DIR__ . '/../partials/icons.view.php';

$title = 'Zone Attendance - YouthNexus';
$pageTitle = 'Zone Attendance';
$pageDescription = 'Read-only attendance summaries across the zone';
$currentRoute = 'zonalsecretary/attendance';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css'];
$pageScripts = [ROOT . '/assets/js/divisional-workflows.js'];

$summaryCards = [
    ['value' => (string) (($zoneAttendance['rate'] ?? 0) . '%'), 'label' => 'Attendance rate', 'note' => 'across ' . (string) ($zoneAttendance['sessions'] ?? 0) . ' sessions', 'icon' => 'check', 'tone' => 'green'],
    ['value' => (string) ($zoneAttendance['present'] ?? 0), 'label' => 'Present check-ins', 'note' => 'of ' . (string) ($zoneAttendance['possible'] ?? 0) . ' expected', 'icon' => 'user', 'tone' => 'blue'],
    ['value' => (string) ($zoneAttendance['clubs'] ?? 0), 'label' => 'Reporting clubs', 'note' => 'across the zone', 'icon' => 'users', 'tone' => 'amber'],
];

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>
<section class="dw-page" aria-labelledby="zonal-attendance-heading">
    <h1 id="zonal-attendance-heading" class="visually-hidden">Zone attendance</h1>
    <p><a class="dw-button dw-button--ghost" href="<?= ROOT ?>/zonalsecretary"><span aria-hidden="true">‹</span> Back to Secretary Overview</a></p>
    <section class="dw-panel" aria-labelledby="zonal-attendance-panel-heading">
        <header class="dw-panel__header"><div><p>Gampaha Zone</p><h2 id="zonal-attendance-panel-heading">Attendance statistics</h2></div></header>
        <div class="dw-panel__body">
            <form class="dw-filter-panel" method="get" action="<?= ROOT ?>/zonalsecretary/attendance">
                <h2 class="dw-filter-panel__heading">Filter Attendance</h2>
                <div class="dw-filter-grid">
                    <div class="dw-field">
                        <label for="zonal-attendance-division">Division</label>
                        <select id="zonal-attendance-division" name="division"><option <?= $division === 'All divisions' ? 'selected' : '' ?>>All divisions</option><option <?= $division === 'Gampaha Division' ? 'selected' : '' ?>>Gampaha Division</option><option <?= $division === 'Ja-Ela Division' ? 'selected' : '' ?>>Ja-Ela Division</option><option <?= $division === 'Negombo Division' ? 'selected' : '' ?>>Negombo Division</option></select>
                    </div>
                    <div class="dw-field">
                        <label for="zonal-attendance-period">Period</label>
                        <select id="zonal-attendance-period" name="period"><option value="30" <?= $period === '30' ? 'selected' : '' ?>>Last 30 days</option><option value="90" <?= $period === '90' ? 'selected' : '' ?>>Last 90 days</option><option value="365" <?= $period === '365' ? 'selected' : '' ?>>Last 12 months</option></select>
                    </div>
                </div>
                <div class="dw-filter-actions">
                    <a class="dw-button dw-button--secondary" href="<?= ROOT ?>/zonalsecretary/attendance">Reset</a>
                    <button class="dw-button dw-button--primary" type="submit">Apply filters</button>
                </div>
            </form>
        </div>
    </section>
    <div class="dw-summary-grid dw-summary-grid--three" aria-label="Zone attendance summary">
        <?php foreach ($summaryCards as $card): ?>
            <?php require __DIR__ . '/../partials/divisional/summary-card.view.php'; ?>
        <?php endforeach; ?>
    </div>
    <section class="dw-panel" aria-labelledby="division-attendance-heading">
        <header class="dw-panel__header"><div><p>Division rollup</p><h2 id="division-attendance-heading">Attendance by division</h2></div><span class="dw-count"><?= count($divisionRows) ?> divisions</span></header>
        <div class="dw-table-wrap"><table class="dw-table"><thead><tr><th scope="col">Division</th><th scope="col">Clubs</th><th scope="col">Sessions</th><th scope="col">Present</th><th scope="col">Attendance rate</th></tr></thead><tbody><?php foreach ($divisionRows as $row): ?><tr><td><strong><?= $e($row['name']) ?></strong></td><td><?= $e($row['clubs']) ?></td><td><?= $e($row['sessions']) ?></td><td><?= $e($row['present']) ?> / <?= $e($row['possible']) ?></td><td><?php $status = ($row['rate'] ?? '') . '%'; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?></td></tr><?php endforeach; ?></tbody></table></div>
        <?php
        $emptyTitle = 'No attendance records found';
        $emptyMessage = 'Try changing the current division or period filters.';
        $emptyVisible = count($divisionRows) === 0;
        require __DIR__ . '/../partials/divisional/empty-state.view.php';
        ?>
        <div class="dw-panel__body"><p>These figures summarize attendance reported by divisions. No attendance records can be edited or verified here.</p></div>
    </section>
</section>
<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
