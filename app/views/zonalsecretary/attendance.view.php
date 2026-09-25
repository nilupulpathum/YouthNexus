<?php
$escape = static function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';
?>
<section class="club-page" aria-labelledby="zonal-attendance-heading">
    <h1 id="zonal-attendance-heading" class="sr-only">Zone attendance</h1>
    <p class="club-back-row"><a class="club-btn-secondary" href="<?= ROOT ?>/zonalsecretary">Back to Secretary Overview</a></p>
    <section class="club-panel" aria-labelledby="zonal-attendance-panel-heading">
        <div class="club-panel-header"><div><p class="club-eyebrow">Gampaha Zone</p><h2 id="zonal-attendance-panel-heading">Attendance statistics</h2></div></div>
        <form class="club-filters" method="get" action="<?= ROOT ?>/zonalsecretary/attendance">
            <label class="club-filter-field"><span class="sr-only">Division</span><select name="division"><option <?= $division === 'All divisions' ? 'selected' : '' ?>>All divisions</option><option <?= $division === 'Gampaha Division' ? 'selected' : '' ?>>Gampaha Division</option><option <?= $division === 'Ja-Ela Division' ? 'selected' : '' ?>>Ja-Ela Division</option><option <?= $division === 'Negombo Division' ? 'selected' : '' ?>>Negombo Division</option></select></label>
            <label class="club-filter-field"><span class="sr-only">Period</span><select name="period"><option value="30" <?= $period === '30' ? 'selected' : '' ?>>Last 30 days</option><option value="90" <?= $period === '90' ? 'selected' : '' ?>>Last 90 days</option><option value="365" <?= $period === '365' ? 'selected' : '' ?>>Last 12 months</option></select></label>
            <button class="club-btn-secondary" type="submit">Apply filters</button>
        </form>
    </section>
    <div class="club-stat-grid" aria-label="Zone attendance summary">
        <article class="club-stat-card"><p class="club-stat-label">Attendance rate</p><p class="club-stat-value"><?= $escape($zoneAttendance['rate']) ?>%</p><p class="club-stat-unit">across <?= $escape($zoneAttendance['sessions']) ?> sessions</p></article>
        <article class="club-stat-card"><p class="club-stat-label">Present check-ins</p><p class="club-stat-value"><?= $escape($zoneAttendance['present']) ?></p><p class="club-stat-unit">of <?= $escape($zoneAttendance['possible']) ?> expected</p></article>
        <article class="club-stat-card"><p class="club-stat-label">Reporting clubs</p><p class="club-stat-value"><?= $escape($zoneAttendance['clubs']) ?></p><p class="club-stat-unit">across the zone</p></article>
    </div>
    <section class="club-panel" aria-labelledby="division-attendance-heading"><div class="club-panel-header"><div><p class="club-eyebrow">Division rollup</p><h2 id="division-attendance-heading">Attendance by division</h2></div></div><div class="club-table-wrap"><table class="club-table"><thead><tr><th scope="col">Division</th><th scope="col">Clubs</th><th scope="col">Sessions</th><th scope="col">Present</th><th scope="col">Attendance rate</th></tr></thead><tbody><?php foreach ($divisionRows as $row): ?><tr><td><strong><?= $escape($row['name']) ?></strong></td><td><?= $escape($row['clubs']) ?></td><td><?= $escape($row['sessions']) ?></td><td><?= $escape($row['present']) ?> / <?= $escape($row['possible']) ?></td><td><span class="club-pill club-pill--approved"><?= $escape($row['rate']) ?>%</span></td></tr><?php endforeach; ?></tbody></table></div><div class="club-panel-sub"><p class="club-sub-note">These figures summarize attendance reported by divisions. No attendance records can be edited or verified here.</p></div></section>
</section>
<link rel="stylesheet" href="<?= ROOT ?>/assets/css/club.css">
<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
