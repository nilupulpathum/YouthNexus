<?php
/**
 * President Overview — C2.
 * Health score + 40/30/30 breakdown tiles, pending club events cards
 * (review happens on club/events), exec-roster summary table.
 * Presentation-only: no DB writes; backend contract lands in C13.
 * UI follows the divisional standard (dw-* classes + shared partials).
 */
$e = static function ($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';

$health        = $health ?? [];
$pendingEvents = $pendingEvents ?? [];
$pendingMembers = $pendingMembers ?? [];
$execRoster    = $execRoster ?? [];
$announcements  = $announcements ?? [];
$upcomingEvents = $upcomingEvents ?? [];
$socialCv       = $socialCv ?? [];

$title = 'President Overview - YouthNexus';
$pageTitle = 'President Overview';
$pageDescription = 'Club health, pending approvals and executive roster';
$currentRoute = 'president';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css'];
$pageScripts = [ROOT . '/assets/js/divisional-workflows.js', ROOT . '/assets/js/club.js'];

$summaryCards = [
    ['value' => (string) ($health['score'] ?? 0) . '/100', 'label' => 'Club health score', 'note' => (string) (($health['label'] ?? '') . ' · ' . ($health['state'] ?? '')), 'icon' => 'award', 'tone' => 'green'],
    ['value' => (string) (($health['events']['points'] ?? 0) . '/' . ($health['events']['max'] ?? 40)), 'label' => 'Events (40%)', 'note' => 'Activity delivery', 'icon' => 'calendar', 'tone' => 'blue'],
    ['value' => (string) (($health['finances']['points'] ?? 0) . '/' . ($health['finances']['max'] ?? 30)), 'label' => 'Finances (30%)', 'note' => 'Fund stewardship', 'icon' => 'file', 'tone' => 'amber'],
    ['value' => (string) (($health['attendance']['points'] ?? 0) . '/' . ($health['attendance']['max'] ?? 30)), 'label' => 'Attendance (30%)', 'note' => 'Participation record', 'icon' => 'clock', 'tone' => 'blue'],
];

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<section class="dw-page" aria-labelledby="president-overview-heading">
    <h1 id="president-overview-heading" class="visually-hidden">President overview</h1>

    <div class="dw-alert dw-alert--success" id="club-toast" role="status" hidden></div>

    <p><a class="dw-button dw-button--ghost" href="<?= ROOT ?>/member"><span aria-hidden="true">‹</span> My Dashboard</a></p>

    <div class="dw-summary-grid" aria-label="Club health">
        <?php foreach ($summaryCards as $card): ?>
            <?php require __DIR__ . '/../partials/divisional/summary-card.view.php'; ?>
        <?php endforeach; ?>
    </div>

    <section class="dw-panel" aria-labelledby="president-pending-heading">
        <header class="dw-panel__header">
            <div>
                <p>Awaiting your review</p>
                <h2 id="president-pending-heading">Pending Club Events (<?= count($pendingEvents) ?>)</h2>
            </div>
            <span class="dw-count"><?= count($pendingEvents) ?> pending</span>
        </header>
        <div class="dw-panel__body">
            <div class="dw-record-grid">
                <?php foreach ($pendingEvents as $pending): ?>
                    <article class="dw-record-card">
                        <div class="dw-record-card__header">
                            <div class="dw-record-card__identity">
                                <span class="dw-record-card__icon" aria-hidden="true"><?= yn_icon('calendar') ?></span>
                                <div class="dw-record-card__meta"><span><?= $e($pending['type'] ?? '') ?></span></div>
                            </div>
                            <?php $status = 'Pending Approval'; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?>
                        </div>
                        <h3 class="dw-record-card__title"><?= $e($pending['title'] ?? '') ?></h3>
                        <div class="dw-record-card__details">
                            <span><?= yn_icon('calendar') ?> <?= $e($pending['date'] ?? '') ?> · <?= yn_icon('pin') ?> <?= $e($pending['location'] ?? '') ?></span>
                            <span><?= $e($pending['type'] ?? '') ?> · Submitted by <?= $e($pending['submitted_by'] ?? '') ?></span>
                        </div>
                        <div class="dw-record-card__footer">
                            <span class="dw-record-card__reference">Review on Club Events</span>
                            <div class="dw-record-card__actions">
                                <a class="dw-button dw-button--ghost" href="<?= ROOT ?>/club/events">Review</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php
            $emptyTitle = 'Nothing awaiting review';
            $emptyMessage = 'New club events will appear here for review.';
            $emptyVisible = count($pendingEvents) === 0;
            require __DIR__ . '/../partials/divisional/empty-state.view.php';
            ?>
        </div>
    </section>

    <section class="dw-panel" aria-labelledby="president-members-heading">
        <header class="dw-panel__header">
            <div>
                <p>Awaiting your approval</p>
                <h2 id="president-members-heading">Pending Member Approvals (<?= count($pendingMembers) ?>)</h2>
            </div>
            <span class="dw-count"><?= count($pendingMembers) ?> pending</span>
        </header>
        <div class="dw-panel__body">
            <div class="dw-record-grid" id="president-members-list">
                <?php foreach ($pendingMembers as $m): ?>
                    <article class="dw-record-card"
                        data-name="<?= $e($m['name'] ?? '') ?>"
                        data-email="<?= $e($m['email'] ?? '') ?>"
                        data-phone="<?= $e($m['phone'] ?? '') ?>"
                        data-address="<?= $e($m['address'] ?? '') ?>"
                        data-nic="<?= $e($m['nic'] ?? '') ?>"
                        data-joined="<?= $e($m['joined'] ?? '') ?>"
                        data-registered-by="<?= $e($m['registered_by'] ?? '') ?>">
                        <div class="dw-record-card__header">
                            <div class="dw-record-card__identity">
                                <span class="dw-record-card__icon" aria-hidden="true"><?= yn_icon('user') ?></span>
                                <div class="dw-record-card__meta"><span>Joined <?= $e($m['joined'] ?? '') ?></span></div>
                            </div>
                            <?php $status = 'Pending'; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?>
                        </div>
                        <h3 class="dw-record-card__title"><?= $e($m['name'] ?? '') ?></h3>
                        <div class="dw-record-card__details">
                            <span><?= $e($m['email'] ?? '') ?> · <?= $e($m['phone'] ?? '') ?></span>
                            <span>Registered by <?= $e($m['registered_by'] ?? '') ?></span>
                        </div>
                        <div class="dw-record-card__footer">
                            <span class="dw-record-card__reference">Member approval</span>
                            <div class="dw-record-card__actions">
                                <button type="button" class="dw-button dw-button--ghost" data-action="member-review" data-modal-open="member-review-modal">Review</button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <div id="president-members-empty"<?= empty($pendingMembers) ? '' : ' hidden' ?>>
                <?php
                $emptyTitle = 'Nothing awaiting approval';
                $emptyMessage = 'New member applications will appear here for review.';
                $emptyVisible = true;
                require __DIR__ . '/../partials/divisional/empty-state.view.php';
                ?>
            </div>
        </div>
    </section>

    <section class="dw-panel" aria-labelledby="president-exec-heading">
        <header class="dw-panel__header">
            <div>
                <p>Gampaha Youth Development Club</p>
                <h2 id="president-exec-heading">Executive Roster</h2>
            </div>
            <a class="dw-button dw-button--secondary" href="<?= ROOT ?>/president/handover">Initiate handover</a>
        </header>
        <div class="dw-table-wrap">
            <table class="dw-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($execRoster as $m): ?>
                        <tr>
                            <td><strong><?= $e($m['name'] ?? '') ?></strong></td>
                            <td><?= $e($m['role'] ?? '') ?></td>
                            <td><?php $status = $m['status'] ?? ''; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        $emptyTitle = 'No executives listed';
        $emptyMessage = 'Executive assignments will appear here once confirmed.';
        $emptyVisible = count($execRoster) === 0;
        require __DIR__ . '/../partials/divisional/empty-state.view.php';
        ?>
    </section>

    <section class="dw-panel" aria-labelledby="president-announcements-heading">
        <header class="dw-panel__header">
            <div>
                <p>Stay informed</p>
                <h2 id="president-announcements-heading">Announcements</h2>
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

    <section class="dw-panel" aria-labelledby="president-events-heading">
        <header class="dw-panel__header">
            <div>
                <p>Plan ahead</p>
                <h2 id="president-events-heading">Upcoming Events</h2>
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

    <div id="member-review-modal" class="dw-modal" role="dialog" aria-modal="true" aria-labelledby="mr-title" aria-hidden="true" hidden>
        <div class="dw-modal__backdrop" data-modal-close></div>
        <div class="dw-modal__dialog">
            <header class="dw-modal__header">
                <div>
                    <p>Member approval</p>
                    <h2 id="mr-title">Review member</h2>
                </div>
                <button type="button" class="dw-modal__close" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
            </header>
            <div class="dw-modal__body">
                <div id="mr-details" class="dw-metric-list"></div>
                <div class="dw-field">
                    <label for="mr-result">Review result</label>
                    <select id="mr-result">
                        <option value="approve">Approve member</option>
                        <option value="reject">Reject application</option>
                    </select>
                </div>
                <div class="dw-field">
                    <label for="mr-remarks">Decision note (required if rejecting)</label>
                    <textarea id="mr-remarks" rows="3" placeholder="Reason for this decision..."></textarea>
                </div>
                <div class="dw-alert dw-alert--error" id="mr-error" role="alert" hidden></div>
                <div role="note">
                    <strong>What happens next</strong>
                    <p>Approving adds them as a General Member of the club. Rejecting discards the application with your note.</p>
                </div>
            </div>
            <footer class="dw-modal__footer">
                <button type="button" class="dw-button dw-button--secondary" data-modal-close>Cancel</button>
                <button type="button" class="dw-button dw-button--primary" id="mr-confirm">Confirm &amp; submit decision</button>
            </footer>
        </div>
    </div>
</section>



<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
