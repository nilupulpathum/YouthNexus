<?php
/**
 * Club Member Dashboard — T0.09
 *
 * This view owns only Member page content. The shared authenticated shell
 * is provided by dashboard-start and dashboard-end.
 * UI follows the divisional standard (dw-* classes + shared partials).
 */
$title           = $title ?? 'Member Dashboard — YouthNexus Pulse';
$pageTitle       = $pageTitle ?? 'Welcome back';
$pageDescription = $pageDescription ?? 'Here’s what’s happening with your activities.';
$currentRoute    = $currentRoute ?? 'member';
$memberDashboard = $memberDashboard ?? [];

$member       = $memberDashboard['member'] ?? [];
$tiles        = $memberDashboard['tiles'] ?? [];
$announcements = $memberDashboard['announcements'] ?? [];
$events       = $memberDashboard['upcoming_events_list'] ?? [];
$activity     = $memberDashboard['recent_activity'] ?? [];
$presidentSummary = $presidentSummary ?? null;
$treasurerSummary = $treasurerSummary ?? null;
$secretarySummary = $secretarySummary ?? null;

$e = static function ($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

$activityIcons = [
    'check' => 'check',
    'hours' => 'clock',
    'event' => 'calendar',
    'read'  => 'eye',
];

$summaryCards = [
    ['value' => (string) ($tiles['volunteer_hours'] ?? 0), 'label' => 'Total volunteer hours', 'note' => 'Across approved activities', 'icon' => 'clock', 'tone' => 'blue'],
    ['value' => (string) ($tiles['upcoming_events'] ?? 0), 'label' => 'Upcoming events', 'note' => 'Next 30 days', 'icon' => 'calendar', 'tone' => 'amber'],
    ['value' => (string) ($tiles['unread_announcements'] ?? 0), 'label' => 'Unread announcements', 'note' => 'Needs your attention', 'icon' => 'info', 'tone' => 'red'],
    ['value' => 'Verified', 'label' => 'Latest certificate', 'note' => (string) ($tiles['latest_certificate'] ?? 'No certificate yet'), 'icon' => 'award', 'tone' => 'green'],
];

$pageStyles = [ROOT . '/assets/css/divisional-workflows.css'];
$pageScripts = [ROOT . '/assets/js/divisional-workflows.js'];

require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<section class="dw-page" aria-labelledby="member-dashboard-heading">
    <h1 id="member-dashboard-heading" class="visually-hidden">Member dashboard</h1>

    <?php if (!empty($presidentSummary)): ?>
        <section class="dw-panel" aria-label="President summary">
            <header class="dw-panel__header">
                <div>
                    <p>President summary</p>
                    <h2>Club health <?= $e($presidentSummary['health_score'] ?? 0) ?>/100</h2>
                </div>
                <?php $status = $presidentSummary['health_label'] ?? ''; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?>
            </header>
            <div class="dw-panel__body">
                <div class="dw-metric-list">
                    <div class="dw-metric"><span>Event approval</span><strong><?= $e($presidentSummary['pending_events'] ?? 0) ?></strong></div>
                    <div class="dw-metric"><span>Member approval</span><strong><?= $e($presidentSummary['pending_members'] ?? 0) ?></strong></div>
                </div>
                <div class="dw-filter-actions">
                    <a class="dw-button dw-button--ghost" href="<?= ROOT ?>/president">Open president overview <span aria-hidden="true">›</span></a>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($treasurerSummary)): ?>
        <section class="dw-panel" aria-label="Treasurer summary">
            <header class="dw-panel__header">
                <div>
                    <p>Treasurer summary</p>
                    <h2><?= $e($treasurerSummary['balance'] ?? '') ?></h2>
                </div>
                <?php $status = ($treasurerSummary['pending_voids'] ?? 0) . ' pending void'; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?>
            </header>
            <div class="dw-panel__body">
                <div class="dw-metric-list">
                    <div class="dw-metric"><span>Income</span><strong><?= $e($treasurerSummary['income'] ?? '') ?></strong></div>
                    <div class="dw-metric"><span>Expenses</span><strong><?= $e($treasurerSummary['expenses'] ?? '') ?></strong></div>
                </div>
                <div class="dw-filter-actions">
                    <a class="dw-button dw-button--ghost" href="<?= ROOT ?>/treasurer">Open treasurer overview <span aria-hidden="true">›</span></a>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($secretarySummary)): ?>
        <section class="dw-panel" aria-label="Secretary summary">
            <header class="dw-panel__header">
                <div>
                    <p>Secretary summary</p>
                    <h2><?= $e($secretarySummary['pending_members'] ?? 0) ?> + <?= $e($secretarySummary['pending_events'] ?? 0) ?> awaiting</h2>
                </div>
                <?php $status = 'Queue'; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?>
            </header>
            <div class="dw-panel__body">
                <div class="dw-metric-list">
                    <div class="dw-metric"><span>Member approval</span><strong><?= $e($secretarySummary['pending_members'] ?? 0) ?></strong></div>
                    <div class="dw-metric"><span>Event approval</span><strong><?= $e($secretarySummary['pending_events'] ?? 0) ?></strong></div>
                </div>
                <div class="dw-filter-actions">
                    <a class="dw-button dw-button--ghost" href="<?= ROOT ?>/secretary">Open secretary overview <span aria-hidden="true">›</span></a>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <div class="dw-summary-grid" aria-label="Member activity summary">
        <?php foreach ($summaryCards as $card): ?>
            <?php require __DIR__ . '/../partials/divisional/summary-card.view.php'; ?>
        <?php endforeach; ?>
    </div>

    <section class="dw-panel" aria-labelledby="member-announcements-heading">
        <header class="dw-panel__header">
            <div>
                <p>Stay informed</p>
                <h2 id="member-announcements-heading">Announcements</h2>
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

    <section class="dw-panel" aria-labelledby="member-events-heading">
        <header class="dw-panel__header">
            <div>
                <p>Plan ahead</p>
                <h2 id="member-events-heading">Upcoming Events</h2>
            </div>
            <a class="dw-button dw-button--ghost" href="<?= ROOT ?>/events">View all <span aria-hidden="true">›</span></a>
        </header>
        <div class="dw-panel__body">
            <div class="dw-record-grid">
                <?php foreach ($events as $event): ?>
                    <article class="dw-record-card">
                        <div class="dw-record-card__header">
                            <div class="dw-record-card__identity">
                                <span class="dw-record-card__icon" aria-hidden="true"><?= yn_icon('calendar') ?></span>
                                <div class="dw-record-card__meta"><span><?= $e($event['scope'] ?? '') ?></span></div>
                            </div>
                            <?php $status = $event['status'] ?? ''; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?>
                        </div>
                        <h3 class="dw-record-card__title"><?= $e($event['title'] ?? '') ?></h3>
                        <div class="dw-record-card__details">
                            <span><?= $e($event['date'] ?? '') ?> · <?= $e($event['location'] ?? '') ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php
            $emptyTitle = 'No upcoming events';
            $emptyMessage = 'Scheduled events will appear here.';
            $emptyVisible = count($events) === 0;
            require __DIR__ . '/../partials/divisional/empty-state.view.php';
            ?>
        </div>
    </section>

    <section class="dw-panel" aria-labelledby="member-activity-heading">
        <header class="dw-panel__header">
            <div>
                <p>Your timeline</p>
                <h2 id="member-activity-heading">Recent Activity</h2>
            </div>
            <a class="dw-button dw-button--ghost" href="<?= $e($profileUrl) ?>">My profile <span aria-hidden="true">›</span></a>
        </header>
        <div class="dw-panel__body">
            <div class="dw-record-grid">
                <?php foreach ($activity as $item): ?>
                    <article class="dw-record-card">
                        <div class="dw-record-card__header">
                            <div class="dw-record-card__identity">
                                <span class="dw-record-card__icon" aria-hidden="true"><?= yn_icon($activityIcons[$item['icon'] ?? 'read'] ?? 'eye') ?></span>
                                <div class="dw-record-card__meta"><span>Activity</span></div>
                            </div>
                        </div>
                        <h3 class="dw-record-card__title"><?= $e($item['label'] ?? '') ?></h3>
                        <div class="dw-record-card__details">
                            <span><?= $e($item['meta'] ?? '') ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php
            $emptyTitle = 'No recent activity';
            $emptyMessage = 'Your activity will appear here.';
            $emptyVisible = count($activity) === 0;
            require __DIR__ . '/../partials/divisional/empty-state.view.php';
            ?>
        </div>
    </section>

    <section class="dw-panel" aria-labelledby="member-cv-heading">
        <header class="dw-panel__header">
            <div>
                <p>Your contribution record</p>
                <h2 id="member-cv-heading">My Social CV</h2>
            </div>
            <a class="dw-button dw-button--ghost" href="<?= $e($profileUrl) ?>">Full view <span aria-hidden="true">→</span></a>
        </header>
        <div class="dw-panel__body">
            <div class="dw-metric-list">
                <div class="dw-metric"><span>Upcoming events</span><strong><?= $e($tiles['upcoming_events'] ?? 0) ?></strong></div>
                <div class="dw-metric"><span>Volunteer hours</span><strong><?= $e($tiles['volunteer_hours'] ?? 0) ?>h</strong></div>
                <div class="dw-metric"><span>Leadership roles</span><strong>3</strong></div>
            </div>
            <div class="dw-filter-actions">
                <span>Certificate status</span>
                <?php $status = '2 Verified'; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?>
                <?php $status = '1 Pending'; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?>
                <button type="button" class="dw-button dw-button--secondary" disabled title="Available after backend integration">Download CV</button>
                <button type="button" class="dw-button dw-button--ghost" disabled title="Available after backend integration">Generate QR</button>
            </div>
        </div>
    </section>
</section>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
