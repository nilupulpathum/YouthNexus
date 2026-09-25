<?php
/**
 * Monitor Club Health — Z1.
 * Divisional Figma shape at zonal scope: band tiles (Healthy 85-100,
 * At Risk 50-69, Dormant below 50 or inactivity), search + division
 * filter + sort + status chips + export, division averages, club cards
 * sorted highest first. Card opens a detail modal (about, performance,
 * recent events, executive committee, health detail, report download)
 * with a flag-for-NYSC-Admin flow. Read-only mock; disband stays
 * Admin-only per the divisional and disbanding workflows.
 * Presentation-only: no DB writes; backend contract lands in Z12.
 * UI follows the divisional standard (dw-* classes + shared partials).
 */
$e = static function ($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';

$zoneHealth = $zoneHealth ?? [];
$divisions  = $divisions ?? [];
$clubs      = $clubs ?? [];

$title = 'Monitor Club Health - YouthNexus';
$pageTitle = 'Monitor Club Health';
$pageDescription = 'Review club health bands, division averages and club details across Gampaha Zone';
$currentRoute = 'zonalcoordinator/clubs';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css'];
$pageScripts = [ROOT . '/assets/js/divisional-workflows.js', ROOT . '/assets/js/zonal.js'];

$summaryCards = [
    ['value' => (string) ($zoneHealth['healthy'] ?? 0), 'label' => 'Healthy', 'note' => 'Score: 85–100', 'icon' => 'check', 'tone' => 'green'],
    ['value' => (string) ($zoneHealth['atrisk'] ?? 0), 'label' => 'At Risk', 'note' => 'Score: 50–69', 'icon' => 'clock', 'tone' => 'amber'],
    ['value' => (string) ($zoneHealth['dormant'] ?? 0), 'label' => 'Dormant', 'note' => 'Score: <50 / Inactivity', 'icon' => 'info', 'tone' => 'red'],
];

$divisionTones = ['healthy' => 'green', 'atrisk' => 'amber', 'dormant' => 'red'];

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<section class="dw-page" aria-labelledby="zonal-clubs-heading">
    <h1 id="zonal-clubs-heading" class="visually-hidden">Monitor club health</h1>

    <?php if (!empty($flash)): ?>
        <div class="dw-alert dw-alert--<?= ($flash['type'] ?? '') === 'success' ? 'success' : 'error' ?>" role="status">
            <?= $e($flash['message'] ?? '') ?>
        </div>
    <?php endif; ?>

    <div class="dw-summary-grid dw-summary-grid--three" aria-label="Club health bands">
        <?php foreach ($summaryCards as $card): ?>
            <?php require __DIR__ . '/../partials/divisional/summary-card.view.php'; ?>
        <?php endforeach; ?>
    </div>

    <section class="dw-panel" aria-labelledby="zonal-division-avg-heading">
        <header class="dw-panel__header">
            <div>
                <p>Gampaha Zone</p>
                <h2 id="zonal-division-avg-heading">Average club health per division</h2>
            </div>
        </header>

        <div class="dw-panel__body">
            <div class="dw-summary-grid" aria-label="Division averages">
                <?php foreach ($divisions as $d): ?>
                    <?php
                    $card = [
                        'value' => (string) ($d['average'] ?? 0) . '/100',
                        'label' => (string) ($d['division'] ?? ''),
                        'note' => (string) (($d['clubs'] ?? 0) . ' clubs · ' . ($d['status'] ?? '')),
                        'icon' => 'users',
                        'tone' => $divisionTones[$d['status_key'] ?? ''] ?? 'blue',
                    ];
                    ?>
                    <?php require __DIR__ . '/../partials/divisional/summary-card.view.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="dw-panel" aria-labelledby="zonal-clubs-list-heading">
        <header class="dw-panel__header">
            <div>
                <p>Gampaha Zone</p>
                <h2 id="zonal-clubs-list-heading">Clubs</h2>
            </div>
            <span class="dw-count"><?= count($clubs) ?> <?= count($clubs) === 1 ? 'club' : 'clubs' ?></span>
        </header>

        <div class="dw-panel__body">
            <div class="dw-toolbar" role="search">
                <div class="dw-toolbar__search dw-search dw-search--plain">
                    <label class="visually-hidden" for="health-search">Search clubs</label>
                    <input type="search" id="health-search" class="health-search" placeholder="Search clubs..." aria-label="Search clubs">
                </div>
                <select id="health-division" class="dw-select health-select" aria-label="Filter by division">
                    <option value="all">All divisions</option>
                    <?php foreach ($divisions as $d): ?>
                        <option value="<?= $e($d['division'] ?? '') ?>"><?= $e($d['division'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="health-sort" class="dw-select health-select" aria-label="Sort clubs">
                    <option value="score-desc">Sort: Highest Score First</option>
                    <option value="score-asc">Sort: Lowest Score First</option>
                    <option value="name-asc">Sort: Name A–Z</option>
                </select>
                <button type="button" class="dw-button dw-button--secondary" id="health-export">Export</button>
            </div>
            <div class="health-chips" role="group" aria-label="Filter by status">
                <button type="button" class="dw-button dw-button--ghost health-chip is-active" data-band="all">All</button>
                <button type="button" class="dw-button dw-button--ghost health-chip" data-band="healthy">Healthy</button>
                <button type="button" class="dw-button dw-button--ghost health-chip" data-band="atrisk">At Risk</button>
                <button type="button" class="dw-button dw-button--ghost health-chip" data-band="dormant">Dormant</button>
            </div>

            <div class="health-grid" id="health-grid">
                <?php foreach ($clubs as $c): ?>
                    <article class="health-card health-card--<?= $e($c['band'] ?? 'healthy') ?>"
                        data-name="<?= $e(strtolower($c['name'] ?? '')) ?>"
                        data-band="<?= $e($c['band'] ?? 'healthy') ?>"
                        data-division="<?= $e($c['division'] ?? '') ?>"
                        data-score="<?= $e($c['score'] ?? 0) ?>"
                        data-club='<?= $e(json_encode(['id' => $c['id'] ?? '', 'name' => $c['name'] ?? '', 'division' => $c['division'] ?? '', 'score' => $c['score'] ?? 0, 'band' => $c['band'] ?? 'healthy', 'status' => $c['status'] ?? '', 'status_key' => $c['status_key'] ?? 'pending', 'members' => $c['members'] ?? 0, 'members_note' => $c['members_note'] ?? '', 'about' => $c['about'] ?? '', 'category' => $c['category'] ?? '', 'location' => $c['location'] ?? '', 'established' => $c['established'] ?? '', 'avg_attendance' => $c['avg_attendance'] ?? '', 'attendance_trend' => $c['attendance_trend'] ?? '', 'trigger' => $c['trigger'] ?? '', 'events' => ($c['events']['points'] ?? 0) . '/' . ($c['events']['max'] ?? 40), 'finances' => ($c['finances']['points'] ?? 0) . '/' . ($c['finances']['max'] ?? 30), 'attendance' => ($c['attendance']['points'] ?? 0) . '/' . ($c['attendance']['max'] ?? 30), 'recent_events' => $c['recent_events'] ?? [], 'execs' => $c['execs'] ?? []])) ?>'>
                        <div class="health-avatar health-avatar--<?= $e($c['band'] ?? 'healthy') ?>" aria-hidden="true">
                            <?= yn_icon('user') ?>
                            <?php if (($c['band'] ?? '') === 'dormant'): ?><span class="health-flag" title="Flagged">!</span><?php endif; ?>
                        </div>
                        <?php if (($c['band'] ?? '') === 'dormant'): ?>
                            <p><?php $status = 'Intervention required'; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?></p>
                        <?php endif; ?>
                        <p class="health-score health-score--<?= $e($c['band'] ?? 'healthy') ?>"><?= $e($c['score'] ?? 0) ?><span>/100</span></p>
                        <p><?php $status = $c['status'] ?? ''; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?></p>
                        <h3><?= $e($c['name'] ?? '') ?></h3>
                        <p class="dw-muted-copy"><?= $e($c['division'] ?? '') ?> · <?= $e($c['members_note'] ?? '') ?></p>
                        <button type="button" class="health-open<?= ($c['band'] ?? '') === 'dormant' ? ' health-open--dormant' : '' ?>" data-action="club-detail" aria-label="Open <?= $e($c['name'] ?? '') ?> details"><span aria-hidden="true">›</span></button>
                    </article>
                <?php endforeach; ?>
            </div>
            <div id="health-empty"<?= count($clubs) === 0 ? '' : ' hidden' ?>>
                <?php
                $emptyTitle = 'No clubs match these filters';
                $emptyMessage = 'Try changing the current search or filters.';
                $emptyVisible = true;
                require __DIR__ . '/../partials/divisional/empty-state.view.php';
                ?>
            </div>
        </div>
    </section>

    <div id="club-health-modal" class="dw-modal" role="dialog" aria-modal="true" aria-labelledby="ch-title" aria-hidden="true" hidden>
        <div class="dw-modal__backdrop" data-modal-close></div>
        <div class="dw-modal__dialog dw-modal__dialog--wide">
            <header class="dw-modal__header">
                <div>
                    <p>Club health detail</p>
                    <h2 id="ch-title">Club</h2>
                </div>
                <button type="button" class="dw-modal__close" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
            </header>
            <div class="dw-modal__body">
                <div class="health-detail-head dw-field--span-2">
                    <div class="health-detail-id" aria-hidden="true"><?= yn_icon('user') ?></div>
                    <div>
                        <p><span class="dw-status" id="ch-id">GC-000</span>
                        <span class="dw-status" id="ch-band">0/100</span></p>
                    </div>
                </div>
                <div class="health-detail-grid dw-field--span-2">
                    <section aria-labelledby="ch-about-h">
                        <h3 id="ch-about-h" class="health-detail-label">About</h3>
                        <div class="health-detail-box">
                            <p id="ch-about"></p>
                            <p class="dw-metric"><span>Category</span><strong id="ch-category"></strong></p>
                            <p class="dw-metric"><span>Location</span><strong id="ch-location"></strong></p>
                            <p class="dw-metric"><span>Established</span><strong id="ch-established"></strong></p>
                        </div>
                    </section>
                    <section aria-labelledby="ch-perf-h">
                        <h3 id="ch-perf-h" class="health-detail-label">Performance overview</h3>
                        <div class="health-perf-row">
                            <div class="health-detail-box health-perf-box">
                                <p class="dw-summary-card__label">Active Members</p>
                                <p class="health-perf-big" id="ch-members"></p>
                            </div>
                            <div class="health-detail-box health-perf-box">
                                <p class="dw-summary-card__label">Avg. Attendance</p>
                                <p class="health-perf-big"><span id="ch-attendance"></span> <span class="health-trend" id="ch-trend"></span></p>
                            </div>
                        </div>
                    </section>
                </div>
                <section class="dw-field--span-2" aria-labelledby="ch-events-h">
                    <h3 id="ch-events-h" class="health-detail-label">Recent events</h3>
                    <div class="dw-table-wrap">
                        <table class="dw-table">
                            <thead>
                                <tr>
                                    <th>Event Name</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="ch-events"></tbody>
                        </table>
                    </div>
                </section>
                <section class="dw-field--span-2" aria-labelledby="ch-execs-h">
                    <h3 id="ch-execs-h" class="health-detail-label">Executive committee</h3>
                    <div class="health-execs" id="ch-execs"></div>
                </section>
                <section class="dw-field--span-2" aria-labelledby="ch-health-h">
                    <h3 id="ch-health-h" class="health-detail-label">Health detail</h3>
                    <div class="health-detail-box">
                        <div id="ch-scores"></div>
                        <p class="dw-muted-copy" id="ch-trigger-note"></p>
                    </div>
                </section>
            </div>
            <footer class="dw-modal__footer health-detail-footer">
                <button type="button" class="dw-button dw-button--secondary" id="ch-open-flag">Flag for NYSC Admin Review</button>
                <button type="button" class="dw-button dw-button--secondary" id="ch-edit">Edit Details</button>
                <button type="button" class="dw-button dw-button--primary" id="ch-report">Download Report</button>
            </footer>
        </div>
    </div>

    <div id="club-flag-modal" class="dw-modal" role="dialog" aria-modal="true" aria-labelledby="cf-title" aria-hidden="true" hidden>
        <div class="dw-modal__backdrop" data-modal-close></div>
        <div class="dw-modal__dialog">
            <header class="dw-modal__header">
                <div>
                    <p>Zonal coordinator action</p>
                    <h2 id="cf-title">Flag Club for NYSC Admin Review</h2>
                </div>
                <button type="button" class="dw-modal__close" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
            </header>
            <div class="dw-modal__body">
                <div class="health-flag-summary dw-field--span-2">
                    <p class="dw-metric"><span>Club</span><strong id="cf-club"></strong></p>
                    <p class="dw-metric"><span>Overall Health Score</span><strong id="cf-score"></strong></p>
                    <p class="dw-metric"><span>Trigger</span><strong id="cf-trigger"></strong></p>
                </div>
                <form id="club-flag-form" action="<?= ROOT ?>/zonalcoordinator/flagClub" method="post">
                    <input type="hidden" name="csrf_token" value="<?= $e($csrf_token ?? '') ?>">
                    <input type="hidden" id="cf-club-id" name="club_id" value="">
                    <div class="dw-field dw-field--span-2">
                        <label for="cf-category">Concern type</label>
                        <select id="cf-category" name="category">
                            <option value="FinancialConcern">Financial concern</option>
                            <option value="EventAttendanceConcern">Event &amp; attendance concern</option>
                            <option value="GovernanceConcern">Governance concern</option>
                        </select>
                    </div>
                    <div class="dw-field dw-field--span-2">
                        <label for="cf-remarks">Comment (required)</label>
                        <textarea id="cf-remarks" name="remarks" rows="3" placeholder="Describe the concern for NYSC Admin..."></textarea>
                    </div>
                </form>
                <p id="cf-error" class="dw-alert dw-alert--error dw-field--span-2" role="alert" hidden></p>
                <div class="dw-alert dw-alert--warning dw-field--span-2" role="note">
                    <div><strong>Admin-only disband</strong>
                    <p>Only NYSC Admin can disband a club — this submits a flag and notification, it does not disband anything.</p></div>
                </div>
            </div>
            <footer class="dw-modal__footer">
                <button type="button" class="dw-button dw-button--secondary" data-modal-close>Cancel</button>
                <button type="submit" class="dw-button dw-button--primary" form="club-flag-form">Submit Flag to NYSC Admin</button>
            </footer>
        </div>
    </div>
</section>

<div id="club-toast" class="dw-alert dw-alert--success" role="status" hidden></div>



<link rel="stylesheet" href="<?= ROOT ?>/assets/css/club.css">

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
