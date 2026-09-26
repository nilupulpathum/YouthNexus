<?php
/**
 * Zonal Secretary Overview.
 * UI follows the divisional standard (dw-* classes + shared partials).
 */
$e = static function ($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';

$links = [
    ['title' => 'Zonal Events', 'desc' => 'Schedule events and notify divisions and clubs', 'href' => 'zonalsecretary/events', 'icon' => 'calendar'],
    ['title' => 'Attendance Statistics', 'desc' => 'Review read-only attendance summaries', 'href' => 'zonalsecretary/attendance', 'icon' => 'file'],
    ['title' => 'Aggregate Reports', 'desc' => 'Division rollups across the zone', 'href' => 'zonalreports', 'icon' => 'file'],
    ['title' => 'Announcements', 'desc' => 'Zone-wide communications', 'href' => 'announcements', 'icon' => 'bell'],
];
$announcements = $announcements ?? [];
$upcomingEvents = $upcomingEvents ?? [];

$title = 'Zonal Secretary Overview - YouthNexus';
$pageTitle = 'Zonal Secretary Overview';
$pageDescription = 'Zone programme, announcements and upcoming events';
$currentRoute = 'zonalsecretary';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css', ROOT . '/assets/css/member-dashboard.css'];
$pageScripts = [ROOT . '/assets/js/divisional-workflows.js'];

$summaryCards = [
    ['value' => (string) ($eventCount ?? 0), 'label' => 'Scheduled events', 'note' => 'in the zone programme', 'icon' => 'calendar', 'tone' => 'blue'],
    ['value' => 'LKR ' . number_format((float) ($budget ?? 0), 0), 'label' => 'Available event budget', 'note' => 'zonal balance', 'icon' => 'file', 'tone' => 'green'],
    ['value' => (string) (($attendanceSummary['rate'] ?? 0) . '%'), 'label' => 'Attendance rate', 'note' => (string) (($attendanceSummary['present'] ?? 0) . ' check-ins'), 'icon' => 'check', 'tone' => 'amber'],
];

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<section class="dw-page" aria-labelledby="zonalsecretary-overview-heading">
    <h1 id="zonalsecretary-overview-heading" class="visually-hidden">Zonal secretary overview</h1>

    <div class="dw-summary-grid dw-summary-grid--three" aria-label="Zone summary">
        <?php foreach ($summaryCards as $card): ?>
            <?php require __DIR__ . '/../partials/divisional/summary-card.view.php'; ?>
        <?php endforeach; ?>
    </div>

    <section class="dw-panel" aria-labelledby="zonalsecretary-links-heading">
        <header class="dw-panel__header">
            <div>
                <p><?= $e($zoneName ?? 'Zone') ?></p>
                <h2 id="zonalsecretary-links-heading">Secretary workspace</h2>
            </div>
            <span class="dw-count"><?= count($links) ?> shortcuts</span>
        </header>
        <div class="dw-panel__body">
            <div class="dw-record-grid">
                <?php foreach ($links as $s): ?>
                    <article class="dw-record-card">
                        <div class="dw-record-card__header">
                            <div class="dw-record-card__identity">
                                <span class="dw-record-card__icon" aria-hidden="true"><?= yn_icon($s['icon']) ?></span>
                                <div class="dw-record-card__meta"><span>Secretary action</span></div>
                            </div>
                        </div>
                        <h3 class="dw-record-card__title"><?= $e($s['title']) ?></h3>
                        <div class="dw-record-card__details">
                            <span><?= $e($s['desc']) ?></span>
                        </div>
                        <div class="dw-record-card__footer">
                            <span class="dw-record-card__reference">Shortcut</span>
                            <div class="dw-record-card__actions">
                                <a class="dw-button dw-button--ghost" href="<?= ROOT ?>/<?= $e($s['href']) ?>">Open</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="member-panel" aria-labelledby="zonalsecretary-announcements-heading">
        <div class="member-panel-header"><div><p class="member-eyebrow">Stay informed</p><h2 id="zonalsecretary-announcements-heading">Zone announcements</h2></div><a class="member-panel-link" href="<?= ROOT ?>/announcements">View all</a></div>
        <div class="member-announcement-list">
            <?php foreach ($announcements as $announcement): ?>
                <article class="member-announcement-item<?= !empty($announcement['is_new']) ? ' is-new' : '' ?>"><span class="member-list-dot" aria-hidden="true"></span><div class="member-list-copy"><div class="member-list-meta"><span><?= $e($announcement['age']) ?></span><?php if (!empty($announcement['is_new'])): ?><span class="member-badge member-badge--new">New</span><?php endif; ?></div><h3><?= $e($announcement['title']) ?></h3><p><?= $e($announcement['summary']) ?></p><span class="member-scope-tag">Zonal</span></div></article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="member-panel" aria-labelledby="zonalsecretary-upcoming-heading">
        <div class="member-panel-header"><div><p class="member-eyebrow">Plan ahead</p><h2 id="zonalsecretary-upcoming-heading">Upcoming zonal programmes</h2></div><a class="member-panel-link" href="<?= ROOT ?>/zonalsecretary/events">Open events</a></div>
        <div class="member-event-list">
            <?php foreach ($upcomingEvents as $event): ?>
                <article class="member-event-item"><div class="member-event-icon" aria-hidden="true"><?= yn_icon('calendar') ?></div><div class="member-event-copy"><div class="member-event-heading"><h3><?= $e($event['title']) ?></h3><span class="member-scope-text">Zonal</span></div><p><?= $e($event['date']) ?>, <?= $e($event['location']) ?></p></div><span class="member-status member-status--<?= $e($event['status_key']) ?>"><?= $e($event['status']) ?></span></article>
            <?php endforeach; ?>
        </div>
    </section>
</section>


<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
