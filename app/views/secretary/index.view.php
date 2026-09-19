<?php
/**
 * Secretary Overview — C10.
 * Member/event/attendance/announcement shortcuts + queue counts, plus the
 * member-base panels (announcements preview, upcoming events, Social CV
 * summary) per the subclass rule. Presentation-only: no DB writes;
 * backend contract lands in C13.
 */
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';

$shortcuts      = $shortcuts ?? [];
$queue          = $queue ?? [];
$announcements  = $announcements ?? [];
$upcomingEvents = $upcomingEvents ?? [];
$socialCv       = $socialCv ?? [];
?>

<section class="club-page" aria-labelledby="secretary-overview-heading">
    <h1 id="secretary-overview-heading" class="sr-only">Secretary overview</h1>

    <p class="club-back-row"><a class="club-btn-secondary" href="<?= ROOT ?>/member"><span aria-hidden="true">‹</span> My Dashboard</a></p>

    <div class="club-stat-grid" aria-label="Approval queue">
        <article class="club-stat-card">
            <p class="club-stat-label">Pending members</p>
            <p class="club-stat-value"><?= $escape($queue['pending_members'] ?? 0) ?></p>
        </article>
        <article class="club-stat-card">
            <p class="club-stat-label">Pending events</p>
            <p class="club-stat-value"><?= $escape($queue['pending_events'] ?? 0) ?></p>
        </article>
    </div>

    <section class="club-panel" aria-labelledby="secretary-shortcuts-heading">
        <div class="club-panel-header">
            <div>
                <p class="club-eyebrow">Secretary actions</p>
                <h2 id="secretary-shortcuts-heading">Shortcuts</h2>
            </div>
        </div>

        <div class="club-list">
            <?php foreach ($shortcuts as $s): ?>
                <article class="club-list-item">
                    <div class="club-list-icon" aria-hidden="true"><?= yn_icon($s['icon'] ?? 'file') ?></div>
                    <div class="club-list-copy">
                        <h3><?= $escape($s['title'] ?? '') ?></h3>
                        <p><?= $escape($s['desc'] ?? '') ?></p>
                    </div>
                    <div class="club-event-side">
                        <a class="club-btn-small" href="<?= ROOT ?>/<?= $escape($s['href'] ?? '') ?>">Open</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="member-panel" aria-labelledby="secretary-announcements-heading">
        <div class="member-panel-header">
            <div>
                <p class="member-eyebrow">Stay informed</p>
                <h2 id="secretary-announcements-heading">Announcements</h2>
            </div>
            <a class="member-panel-link" href="<?= ROOT ?>/announcements">View all <span aria-hidden="true">›</span></a>
        </div>

        <div class="member-announcement-list">
            <?php foreach ($announcements as $a): ?>
                <article class="member-announcement-item<?= !empty($a['is_new']) ? ' is-new' : '' ?>">
                    <span class="member-list-dot" aria-hidden="true"></span>
                    <div class="member-list-copy">
                        <div class="member-list-meta">
                            <span><?= $escape($a['age'] ?? '') ?></span>
                            <?php if (!empty($a['is_new'])): ?><span class="member-badge member-badge--new">New</span><?php endif; ?>
                        </div>
                        <h3><?= $escape($a['title'] ?? '') ?></h3>
                        <p><?= $escape($a['summary'] ?? '') ?></p>
                        <span class="member-scope-tag"><?= $escape($a['scope'] ?? '') ?></span>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="member-panel" aria-labelledby="secretary-events-heading">
        <div class="member-panel-header">
            <div>
                <p class="member-eyebrow">Plan ahead</p>
                <h2 id="secretary-events-heading">Upcoming Events</h2>
            </div>
            <a class="member-panel-link" href="<?= ROOT ?>/events">View all <span aria-hidden="true">›</span></a>
        </div>

        <div class="member-event-list">
            <?php foreach ($upcomingEvents as $e): ?>
                <article class="member-event-item">
                    <div class="member-event-icon" aria-hidden="true"><?= yn_icon('calendar') ?></div>
                    <div class="member-event-copy">
                        <div class="member-event-heading">
                            <h3><?= $escape($e['title'] ?? '') ?></h3>
                            <span class="member-scope-text"><?= $escape($e['scope'] ?? '') ?></span>
                        </div>
                        <p><?= $escape($e['date'] ?? '') ?> · <?= $escape($e['location'] ?? '') ?></p>
                    </div>
                    <span class="member-status member-status--<?= $escape($e['status_key'] ?? 'pending') ?>"><?= $escape($e['status'] ?? '') ?></span>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="member-panel member-exec-strip" aria-label="Social CV summary">
        <div class="member-exec-health">
            <p class="member-eyebrow">Social CV</p>
            <p class="member-exec-score"><?= $escape($socialCv['volunteer_hours'] ?? 0) ?><span>h</span></p>
            <span class="member-status member-status--attending">Verified</span>
        </div>
        <div class="member-exec-pending">
            <div>
                <strong><?= $escape($socialCv['events_count'] ?? 0) ?></strong>
                <span>Events attended</span>
            </div>
            <div>
                <strong>1</strong>
                <span><?= $escape($socialCv['leadership'] ?? 'Leadership role') ?></span>
            </div>
        </div>
        <a class="member-panel-link" href="<?= ROOT ?>/profile">Open Social CV <span aria-hidden="true">›</span></a>
    </section>
</section>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/club.css">
<link rel="stylesheet" href="<?= ROOT ?>/assets/css/member-dashboard.css">

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
