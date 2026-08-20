<?php
/**
 * Club Member Dashboard — T0.09
 *
 * This view owns only Member page content. The shared authenticated shell
 * is provided by dashboard-start and dashboard-end.
 */
$title           = $title ?? 'Member Dashboard — YouthNexus Pulse';
$pageTitle       = $pageTitle ?? 'Welcome back, Jamie';
$pageDescription = $pageDescription ?? 'Here’s what’s happening with your activities.';
$currentRoute    = $currentRoute ?? 'member';
$memberDashboard = $memberDashboard ?? [];

$member       = $memberDashboard['member'] ?? [];
$tiles        = $memberDashboard['tiles'] ?? [];
$announcements = $memberDashboard['announcements'] ?? [];
$events       = $memberDashboard['upcoming_events_list'] ?? [];
$activity     = $memberDashboard['recent_activity'] ?? [];

$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<section class="member-dashboard" aria-labelledby="member-dashboard-heading">
    <h1 id="member-dashboard-heading" class="sr-only">Member dashboard</h1>

    <div class="member-stat-grid" aria-label="Member activity summary">
        <article class="member-stat-card member-stat-card--blue">
            <div class="member-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="21" height="21" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="3.5" width="14" height="17" rx="2"/><path d="M8 7h8M8 11h8M8 15h4" stroke-linecap="round"/><path d="m15.5 15.5 1.4 1.4 2.7-3" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <div>
                <p class="member-stat-label">Total volunteer hours</p>
                <p class="member-stat-value"><?= $escape($tiles['volunteer_hours'] ?? 0) ?></p>
                <p class="member-stat-note">Across approved activities</p>
            </div>
        </article>

        <article class="member-stat-card member-stat-card--orange">
            <div class="member-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="21" height="21" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16M8 14h3M8 17h5" stroke-linecap="round"/></svg>
            </div>
            <div>
                <p class="member-stat-label">Upcoming events</p>
                <p class="member-stat-value"><?= $escape($tiles['upcoming_events'] ?? 0) ?></p>
                <p class="member-stat-note">Next 30 days</p>
            </div>
        </article>

        <article class="member-stat-card member-stat-card--red">
            <div class="member-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="21" height="21" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Z"/><path d="M10 21h4" stroke-linecap="round"/></svg>
            </div>
            <div>
                <p class="member-stat-label">Unread announcements</p>
                <p class="member-stat-value"><?= $escape($tiles['unread_announcements'] ?? 0) ?></p>
                <p class="member-stat-note">Needs your attention</p>
            </div>
        </article>

        <article class="member-stat-card member-stat-card--purple">
            <div class="member-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="21" height="21" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 3.5h9l3 3V20H6z"/><path d="M15 3.5V7h3M9 12h6M9 15h6" stroke-linecap="round"/><path d="m9 18 1.6 1.6L14 16.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <div>
                <p class="member-stat-label">Latest certificate</p>
                <p class="member-stat-value member-stat-value--status">Verified</p>
                <p class="member-stat-note"><?= $escape($tiles['latest_certificate'] ?? 'No certificate yet') ?></p>
            </div>
        </article>
    </div>

    <div class="member-content-grid">
        <section class="member-panel member-panel--announcements" aria-labelledby="member-announcements-heading">
            <div class="member-panel-header">
                <div>
                    <p class="member-eyebrow">Stay informed</p>
                    <h2 id="member-announcements-heading">Announcements</h2>
                </div>
                <a class="member-panel-link" href="<?= ROOT ?>/announcements">View all <span aria-hidden="true">›</span></a>
            </div>

            <div class="member-announcement-list">
                <?php foreach ($announcements as $announcement): ?>
                    <article class="member-announcement-item<?= !empty($announcement['is_new']) ? ' is-new' : '' ?>">
                        <span class="member-list-dot" aria-hidden="true"></span>
                        <div class="member-list-copy">
                            <div class="member-list-meta">
                                <span><?= $escape($announcement['age'] ?? '') ?></span>
                                <?php if (!empty($announcement['is_new'])): ?><span class="member-badge member-badge--new">New</span><?php endif; ?>
                            </div>
                            <h3><?= $escape($announcement['title'] ?? '') ?></h3>
                            <p><?= $escape($announcement['summary'] ?? '') ?></p>
                            <span class="member-scope-tag"><?= $escape($announcement['scope'] ?? '') ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="member-panel member-panel--events" aria-labelledby="member-events-heading">
            <div class="member-panel-header">
                <div>
                    <p class="member-eyebrow">Plan ahead</p>
                    <h2 id="member-events-heading">Upcoming Events</h2>
                </div>
                <a class="member-panel-link" href="<?= ROOT ?>/events">View all <span aria-hidden="true">›</span></a>
            </div>

            <div class="member-event-list">
                <?php foreach ($events as $event): ?>
                    <article class="member-event-item">
                        <div class="member-event-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16" stroke-linecap="round"/></svg>
                        </div>
                        <div class="member-event-copy">
                            <div class="member-event-heading">
                                <h3><?= $escape($event['title'] ?? '') ?></h3>
                                <span class="member-scope-text"><?= $escape($event['scope'] ?? '') ?></span>
                            </div>
                            <p><?= $escape($event['date'] ?? '') ?> · <?= $escape($event['location'] ?? '') ?></p>
                        </div>
                        <span class="member-status member-status--<?= $escape($event['status_key'] ?? 'pending') ?>"><?= $escape($event['status'] ?? '') ?></span>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <div class="member-lower-grid">
        <section class="member-panel member-panel--activity" aria-labelledby="member-activity-heading">
            <div class="member-panel-header">
                <div>
                    <p class="member-eyebrow">Your timeline</p>
                    <h2 id="member-activity-heading">Recent Activity</h2>
                </div>
                <a class="member-panel-link" href="<?= ROOT ?>/profile">My profile <span aria-hidden="true">›</span></a>
            </div>
            <div class="member-activity-list">
                <?php foreach ($activity as $item): ?>
                    <article class="member-activity-item">
                        <span class="member-activity-icon member-activity-icon--<?= $escape($item['icon'] ?? 'read') ?>" aria-hidden="true">
                            <?php if (($item['icon'] ?? '') === 'check'): ?>✓<?php elseif (($item['icon'] ?? '') === 'hours'): ?>◌<?php elseif (($item['icon'] ?? '') === 'event'): ?>↗<?php else: ?>○<?php endif; ?>
                        </span>
                        <div>
                            <h3><?= $escape($item['label'] ?? '') ?></h3>
                            <p><?= $escape($item['meta'] ?? '') ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="member-cv-card" aria-labelledby="member-cv-heading">
            <div class="member-cv-glow member-cv-glow--top" aria-hidden="true"></div>
            <div class="member-cv-glow member-cv-glow--bottom" aria-hidden="true"></div>
            <div class="member-cv-header">
                <div>
                    <p class="member-cv-kicker">Your contribution record</p>
                    <h2 id="member-cv-heading">My Social CV</h2>
                </div>
                <a href="<?= ROOT ?>/profile" class="member-cv-link">Full view <span aria-hidden="true">→</span></a>
            </div>
            <div class="member-cv-summary">
                <div><strong><?= $escape($tiles['upcoming_events'] ?? 0) ?></strong><span>Upcoming events</span></div>
                <div><strong><?= $escape($tiles['volunteer_hours'] ?? 0) ?>h</strong><span>Volunteer hours</span></div>
                <div><strong>3</strong><span>Leadership roles</span></div>
            </div>
            <div class="member-cv-footer">
                <span>Certificate status</span>
                <span class="member-cv-pill">2 Verified</span>
                <span class="member-cv-pill member-cv-pill--muted">1 Pending</span>
                <button type="button" class="member-cv-button" disabled title="Available after backend integration">Download CV</button>
                <button type="button" class="member-cv-button member-cv-button--ghost" disabled title="Available after backend integration">Generate QR</button>
            </div>
        </section>
    </div>
</section>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/member-dashboard.css">

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
