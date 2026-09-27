<?php
/**
 * Secretary Overview — C10.
 * Member/event/attendance/announcement shortcuts + queue counts, plus the
 * member-base panels (announcements preview, upcoming events, Social CV
 * summary) per the subclass rule. Presentation-only: no DB writes;
 * backend contract lands in C13.
 * UI follows the divisional standard (dw-* classes + shared partials).
 */
$e = static function ($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';

$shortcuts      = $shortcuts ?? [];
$queue          = $queue ?? [];
$announcements  = $announcements ?? [];
$upcomingEvents = $upcomingEvents ?? [];
$socialCv       = $socialCv ?? [];

$title = 'Secretary Overview - YouthNexus';
$pageTitle = 'Secretary Overview';
$pageDescription = 'Approval queue, shortcuts and club updates';
$currentRoute = 'secretary';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css'];
$pageScripts = [ROOT . '/assets/js/divisional-workflows.js'];

$summaryCards = [
    ['value' => (string) ($queue['pending_members'] ?? 0), 'label' => 'Pending members', 'note' => 'Member approval queue', 'icon' => 'users', 'tone' => 'blue'],
    ['value' => (string) ($queue['pending_events'] ?? 0), 'label' => 'Pending events', 'note' => 'Event approval queue', 'icon' => 'calendar', 'tone' => 'amber'],
];

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<section class="dw-page" aria-labelledby="secretary-overview-heading">
    <h1 id="secretary-overview-heading" class="visually-hidden">Secretary overview</h1>

    <p><a class="dw-button dw-button--ghost" href="<?= ROOT ?>/member"><span aria-hidden="true">‹</span> My Dashboard</a></p>

    <div class="dw-summary-grid dw-summary-grid--two" aria-label="Approval queue">
        <?php foreach ($summaryCards as $card): ?>
            <?php require __DIR__ . '/../partials/divisional/summary-card.view.php'; ?>
        <?php endforeach; ?>
    </div>

    <section class="dw-panel" aria-labelledby="secretary-shortcuts-heading">
        <header class="dw-panel__header">
            <div>
                <p>Secretary actions</p>
                <h2 id="secretary-shortcuts-heading">Shortcuts</h2>
            </div>
            <span class="dw-count"><?= count($shortcuts) ?> shortcuts</span>
        </header>
        <div class="dw-panel__body">
            <div class="dw-record-grid">
                <?php foreach ($shortcuts as $s): ?>
                    <article class="dw-record-card">
                        <div class="dw-record-card__header">
                            <div class="dw-record-card__identity">
                                <span class="dw-record-card__icon" aria-hidden="true"><?= yn_icon($s['icon'] ?? 'file') ?></span>
                                <div class="dw-record-card__meta"><span>Secretary action</span></div>
                            </div>
                        </div>
                        <h3 class="dw-record-card__title"><?= $e($s['title'] ?? '') ?></h3>
                        <div class="dw-record-card__details">
                            <span><?= $e($s['desc'] ?? '') ?></span>
                        </div>
                        <div class="dw-record-card__footer">
                            <span class="dw-record-card__reference">Shortcut</span>
                            <div class="dw-record-card__actions">
                                <a class="dw-button dw-button--ghost" href="<?= ROOT ?>/<?= $e($s['href'] ?? '') ?>">Open</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php
            $emptyTitle = 'No shortcuts available';
            $emptyMessage = 'Shortcuts will appear here once configured.';
            $emptyVisible = count($shortcuts) === 0;
            require __DIR__ . '/../partials/divisional/empty-state.view.php';
            ?>
        </div>
    </section>

    <section class="dw-panel" aria-labelledby="secretary-announcements-heading">
        <header class="dw-panel__header">
            <div>
                <p>Stay informed</p>
                <h2 id="secretary-announcements-heading">Announcements</h2>
            </div>
            <a class="dw-button dw-button--ghost" href="<?= ROOT ?>/announcements">View all <span aria-hidden="true">›</span></a>
        </header>
        <div class="dw-panel__body">
            <div class="dw-record-grid">
                <?php foreach ($announcements as $announcement): ?>
                    <article class="dw-record-card">
                        <div class="dw-record-card__header">
                            <div class="dw-record-card__identity">
                                <span class="dw-record-card__icon" aria-hidden="true"><?= yn_icon('info') ?></span>
                                <div class="dw-record-card__meta"><span><?= $e($announcement['age'] ?? '') ?></span></div>
                            </div>
                            <?php if (!empty($announcement['is_new'])): ?><?php $status = 'New'; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?><?php endif; ?>
                        </div>
                        <h3 class="dw-record-card__title"><?= $e($announcement['title'] ?? '') ?></h3>
                        <p><?= $e($announcement['summary'] ?? '') ?></p>
                        <div class="dw-record-card__footer">
                            <span class="dw-record-card__reference"><?= $e($announcement['scope'] ?? '') ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php
            $emptyTitle = 'No announcements yet';
            $emptyMessage = 'New announcements will appear here.';
            $emptyVisible = count($announcements) === 0;
            require __DIR__ . '/../partials/divisional/empty-state.view.php';
            ?>
        </div>
    </section>

    <section class="dw-panel" aria-labelledby="secretary-events-heading">
        <header class="dw-panel__header">
            <div>
                <p>Plan ahead</p>
                <h2 id="secretary-events-heading">Upcoming Events</h2>
            </div>
            <a class="dw-button dw-button--ghost" href="<?= ROOT ?>/events">View all <span aria-hidden="true">›</span></a>
        </header>
        <div class="dw-panel__body">
            <div class="dw-record-grid">
                <?php foreach ($upcomingEvents as $upcoming): ?>
                    <article class="dw-record-card">
                        <div class="dw-record-card__header">
                            <div class="dw-record-card__identity">
                                <span class="dw-record-card__icon" aria-hidden="true"><?= yn_icon('calendar') ?></span>
                                <div class="dw-record-card__meta"><span><?= $e($upcoming['scope'] ?? '') ?></span></div>
                            </div>
                            <?php $status = $upcoming['status'] ?? ''; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?>
                        </div>
                        <h3 class="dw-record-card__title"><?= $e($upcoming['title'] ?? '') ?></h3>
                        <div class="dw-record-card__details">
                            <span><?= $e($upcoming['date'] ?? '') ?> · <?= $e($upcoming['location'] ?? '') ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php
            $emptyTitle = 'No upcoming events';
            $emptyMessage = 'Scheduled events will appear here.';
            $emptyVisible = count($upcomingEvents) === 0;
            require __DIR__ . '/../partials/divisional/empty-state.view.php';
            ?>
        </div>
    </section>

    <section class="dw-panel" aria-label="Social CV summary">
        <header class="dw-panel__header">
            <div>
                <p>Social CV</p>
                <h2>Verified volunteer record</h2>
            </div>
            <?php $status = 'Verified'; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?>
        </header>
        <div class="dw-panel__body">
            <div class="dw-metric-list">
                <div class="dw-metric"><span>Volunteer hours</span><strong><?= $e($socialCv['volunteer_hours'] ?? 0) ?>h</strong></div>
                <div class="dw-metric"><span>Events attended</span><strong><?= $e($socialCv['events_count'] ?? 0) ?></strong></div>
                <div class="dw-metric"><span><?= $e($socialCv['leadership'] ?? 'Leadership role') ?></span><strong>1</strong></div>
            </div>
            <div class="dw-filter-actions">
                <a class="dw-button dw-button--ghost" href="<?= ROOT ?>/profile">Open Social CV <span aria-hidden="true">›</span></a>
            </div>
        </div>
    </section>
</section>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
