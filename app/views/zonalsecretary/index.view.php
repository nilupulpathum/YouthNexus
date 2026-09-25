<?php
/**
 * Zonal Secretary Overview.
 */
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';

$links = [
    ['title' => 'Zonal Events', 'desc' => 'Schedule events and notify divisions and clubs', 'href' => 'zonalsecretary/events', 'icon' => 'calendar'],
    ['title' => 'Attendance Statistics', 'desc' => 'Review read-only attendance summaries', 'href' => 'zonalsecretary/attendance', 'icon' => 'file'],
    ['title' => 'Aggregate Reports', 'desc' => 'Division rollups across the zone', 'href' => 'zonalsecretary/reports', 'icon' => 'file'],
    ['title' => 'Announcements', 'desc' => 'Zone-wide communications', 'href' => 'announcements', 'icon' => 'bell'],
];
$announcements = $announcements ?? [];
$upcomingEvents = $upcomingEvents ?? [];
?>

<section class="club-page" aria-labelledby="zonalsecretary-overview-heading">
    <h1 id="zonalsecretary-overview-heading" class="sr-only">Zonal secretary overview</h1>

    <div class="club-stat-grid" aria-label="Zone summary">
        <article class="club-stat-card">
            <p class="club-stat-label">Scheduled events</p>
            <p class="club-stat-value"><?= $escape($eventCount) ?></p>
            <p class="club-stat-unit">in the zone programme</p>
        </article>
        <article class="club-stat-card">
            <p class="club-stat-label">Available event budget</p>
            <p class="club-stat-value club-stat-value--small">LKR <?= number_format((float)$budget, 0) ?></p>
            <p class="club-stat-unit">zonal balance</p>
        </article>
        <article class="club-stat-card">
            <p class="club-stat-label">Attendance rate</p>
            <p class="club-stat-value"><?= $escape($attendanceSummary['rate']) ?>%</p>
            <p class="club-stat-unit"><?= $escape($attendanceSummary['present']) ?> check-ins</p>
        </article>
    </div>

    <section class="club-panel" aria-labelledby="zonalsecretary-links-heading">
        <div class="club-panel-header">
            <div>
                <p class="club-eyebrow">Gampaha Zone</p>
                <h2 id="zonalsecretary-links-heading">Secretary workspace</h2>
            </div>
        </div>

        <div class="club-list">
            <?php foreach ($links as $s): ?>
                <article class="club-list-item">
                    <div class="club-list-icon" aria-hidden="true"><?= yn_icon($s['icon']) ?></div>
                    <div class="club-list-copy">
                        <h3><?= $escape($s['title']) ?></h3>
                        <p><?= $escape($s['desc']) ?></p>
                    </div>
                    <div class="club-event-side">
                        <a class="club-btn-small" href="<?= ROOT ?>/<?= $escape($s['href']) ?>">Open</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="member-panel" aria-labelledby="zonalsecretary-announcements-heading">
        <div class="member-panel-header"><div><p class="member-eyebrow">Stay informed</p><h2 id="zonalsecretary-announcements-heading">Zone announcements</h2></div><a class="member-panel-link" href="<?= ROOT ?>/zonalannouncements">View all</a></div>
        <div class="member-announcement-list">
            <?php foreach ($announcements as $announcement): ?>
                <article class="member-announcement-item<?= !empty($announcement['is_new']) ? ' is-new' : '' ?>"><span class="member-list-dot" aria-hidden="true"></span><div class="member-list-copy"><div class="member-list-meta"><span><?= $escape($announcement['age']) ?></span><?php if (!empty($announcement['is_new'])): ?><span class="member-badge member-badge--new">New</span><?php endif; ?></div><h3><?= $escape($announcement['title']) ?></h3><p><?= $escape($announcement['summary']) ?></p><span class="member-scope-tag">Zonal</span></div></article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="member-panel" aria-labelledby="zonalsecretary-upcoming-heading">
        <div class="member-panel-header"><div><p class="member-eyebrow">Plan ahead</p><h2 id="zonalsecretary-upcoming-heading">Upcoming zonal programmes</h2></div><a class="member-panel-link" href="<?= ROOT ?>/zonalsecretary/events">Open events</a></div>
        <div class="member-event-list">
            <?php foreach ($upcomingEvents as $event): ?>
                <article class="member-event-item"><div class="member-event-icon" aria-hidden="true"><?= yn_icon('calendar') ?></div><div class="member-event-copy"><div class="member-event-heading"><h3><?= $escape($event['title']) ?></h3><span class="member-scope-text">Zonal</span></div><p><?= $escape($event['date']) ?>, <?= $escape($event['location']) ?></p></div><span class="member-status member-status--<?= $escape($event['status_key']) ?>"><?= $escape($event['status']) ?></span></article>
            <?php endforeach; ?>
        </div>
    </section>
</section>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/club.css">
<link rel="stylesheet" href="<?= ROOT ?>/assets/css/member-dashboard.css">

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
