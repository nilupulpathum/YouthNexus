<?php
/**
 * Monitor Club Health — NYSC Administration
 *
 * Mirrors the divisional club health UI (feat-divisional-shared-club-health) with
 * three national-level additions:
 *   1. Zone → Division cascading filter + search + dormant quick-filter
 *   2. "Show Dormant Only" shortcut on the summary bar
 *   3. Disband warning + Execute Disband panel inside the club profile modal
 */
$title           = $title ?? 'Monitor Club Health — YouthNexus';
$pageTitle       = 'Monitor Club Health';
$pageDescription = 'NYSC Administration — live health scores, dormant clubs and disbandment controls';
$currentRoute    = 'clubhealth';

require __DIR__ . '/../layouts/dashboard-start.view.php';

// ── helpers ──────────────────────────────────────────────────────────────────
$e           = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$flash       = $flash ?? null;
$filters     = $filters ?? [];
$clubs       = $clubs ?? [];
$summary     = $summary ?? ['monitored' => 0, 'green' => 0, 'yellow' => 0, 'red' => 0, 'flagged' => 0];
$detailsJson = $detailsJson ?? '{}';

$statusLabels = ['Green' => 'Healthy', 'Yellow' => 'At Risk', 'Red' => 'Dormant'];
?>
<link rel="stylesheet" href="<?= ROOT ?>/assets/css/clubhealth.css">

<?php if ($flash): ?>
    <div class="dw-alert dw-alert--<?= $flash['type'] === 'success' ? 'success' : 'error' ?>" style="margin-bottom:16px;">
        <span><?= $e($flash['message']) ?></span>
    </div>
<?php endif; ?>

<section class="dch-page">

    <!-- ── Page header ──────────────────────────────────────────────────── -->
    <header class="dch-page-header">
        <div class="dch-page-header__text">
            <h1>Monitor Club Health</h1>
            <p>
                Health score = Events 40% + Finance 30% + Attendance 30% (6-month rolling window).
                Dormant = Red band (score below 30).
                <button class="dch-formula-link" type="button" data-modal-open="health-formula-modal">Formula details →</button>
            </p>
        </div>
        <div class="dch-page-header__actions">
            <a class="dw-button dw-button--ghost" id="chExportBtn"
               href="<?= ROOT ?>/clubhealth/export?zone=<?= (int)($filters['zone'] ?? 0) ?>&division=<?= (int)($filters['division'] ?? 0) ?>&q=<?= urlencode($filters['q'] ?? '') ?>&bucket=<?= urlencode($filters['bucket'] ?? '') ?>">
                Export CSV
            </a>
        </div>
    </header>

    <!-- ── Filter bar ───────────────────────────────────────────────────── -->
    <div class="dch-filter-card">
        <form id="chFilterForm" method="get" action="<?= ROOT ?>/clubhealth" class="dch-filter-form">
            <div class="dch-filter-grid">

                <!-- Zone -->
                <div class="dw-field">
                    <label for="chZone">Zone</label>
                    <select name="zone" id="chZone">
                        <option value="">All zones</option>
                        <?php foreach ($zones as $z): ?>
                            <option value="<?= (int)$z->zonal_id ?>"
                                <?= ($filters['zone'] ?? null) == (int)$z->zonal_id ? 'selected' : '' ?>>
                                <?= $e($z->zonal_name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Division (auto-populated on zone change) -->
                <div class="dw-field">
                    <label for="chDivision">Division</label>
                    <select name="division" id="chDivision">
                        <option value="">All divisions</option>
                        <?php foreach ($divisions as $d): ?>
                            <option value="<?= (int)$d->division_id ?>"
                                <?= ($filters['division'] ?? null) == (int)$d->division_id ? 'selected' : '' ?>>
                                <?= $e($d->division_name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Search -->
                <div class="dw-field dch-filter-search">
                    <label for="chSearch">Search</label>
                    <input type="text" name="q" id="chSearch"
                           value="<?= $e($filters['q'] ?? '') ?>"
                           placeholder="Club name, code, zone or division…">
                </div>

                <!-- Health band -->
                <div class="dw-field">
                    <label for="chBucket">Health band</label>
                    <select name="bucket" id="chBucket">
                        <option value="">All clubs</option>
                        <option value="green"   <?= ($filters['bucket'] ?? '') === 'green'   ? 'selected' : '' ?>>Healthy (Green)</option>
                        <option value="yellow"  <?= ($filters['bucket'] ?? '') === 'yellow'  ? 'selected' : '' ?>>At Risk (Yellow)</option>
                        <option value="dormant" <?= ($filters['bucket'] ?? '') === 'dormant' ? 'selected' : '' ?>>Dormant only (Red)</option>
                    </select>
                </div>

            </div>
            <div class="dch-filter-actions">
                <button type="submit" class="dw-button dw-button--primary">Apply filter</button>
                <a class="dw-button dw-button--ghost" href="<?= ROOT ?>/clubhealth">Clear</a>
            </div>
        </form>
    </div>

    <!-- ── KPI summary row ──────────────────────────────────────────────── -->
    <div class="dch-summary-strip">
        <a class="dch-kpi-pill dch-kpi-pill--blue <?= !($filters['bucket'] ?? '') ? 'is-active' : '' ?>"
           href="<?= ROOT ?>/clubhealth?zone=<?= (int)($filters['zone'] ?? 0) ?>&division=<?= (int)($filters['division'] ?? 0) ?>&q=<?= urlencode($filters['q'] ?? '') ?>">
            <strong><?= (int)$summary['monitored'] ?></strong>
            <span>Clubs monitored</span>
        </a>
        <a class="dch-kpi-pill dch-kpi-pill--green <?= ($filters['bucket'] ?? '') === 'green' ? 'is-active' : '' ?>"
           href="<?= ROOT ?>/clubhealth?bucket=green&zone=<?= (int)($filters['zone'] ?? 0) ?>&division=<?= (int)($filters['division'] ?? 0) ?>&q=<?= urlencode($filters['q'] ?? '') ?>">
            <strong><?= (int)$summary['green'] ?></strong>
            <span>Healthy</span>
        </a>
        <a class="dch-kpi-pill dch-kpi-pill--yellow <?= ($filters['bucket'] ?? '') === 'yellow' ? 'is-active' : '' ?>"
           href="<?= ROOT ?>/clubhealth?bucket=yellow&zone=<?= (int)($filters['zone'] ?? 0) ?>&division=<?= (int)($filters['division'] ?? 0) ?>&q=<?= urlencode($filters['q'] ?? '') ?>">
            <strong><?= (int)$summary['yellow'] ?></strong>
            <span>At Risk</span>
        </a>
        <a class="dch-kpi-pill dch-kpi-pill--red <?= ($filters['bucket'] ?? '') === 'dormant' ? 'is-active' : '' ?>"
           href="<?= ROOT ?>/clubhealth?bucket=dormant&zone=<?= (int)($filters['zone'] ?? 0) ?>&division=<?= (int)($filters['division'] ?? 0) ?>&q=<?= urlencode($filters['q'] ?? '') ?>">
            <strong><?= (int)$summary['red'] ?></strong>
            <span>Dormant</span>
        </a>
        <?php if ((int)$summary['flagged'] > 0): ?>
        <span class="dch-kpi-pill dch-kpi-pill--flag">
            <strong><?= (int)$summary['flagged'] ?></strong>
            <span>Flagged</span>
        </span>
        <?php endif; ?>
    </div>

    <!-- ── Club cards ───────────────────────────────────────────────────── -->
    <section class="dch-clubs-section">
        <div class="dw-section-header">
            <div>
                <h2>
                    Clubs (<?= (int)$summary['monitored'] ?>)
                    <?php if (($filters['bucket'] ?? '') === 'dormant'): ?>
                        <span class="dch-dormant-badge">Dormant filter active</span>
                    <?php endif; ?>
                </h2>
                <p>Open a card to view the full live health profile<?= ($filters['bucket'] ?? '') === 'dormant' ? ' and the disbandment controls' : '' ?>.</p>
            </div>
        </div>

        <div class="dch-cards" data-health-grid>
            <?php foreach ($clubs as $club):
                $score = is_array($club->score) ? $club->score : [];
                $overallScore = (float)($score['overall_score'] ?? $club->overall_health_score ?? 0);
                $healthStatus = $score['health_status'] ?? $club->health_status ?? 'Yellow';
                $statusKey    = strtolower($healthStatus);
                $label        = $statusLabels[$healthStatus] ?? $healthStatus;

                // initials from club name
                $initials = '';
                foreach (preg_split('/\s+/', trim($club->club_name)) as $w) {
                    if ($w !== '') $initials .= strtoupper($w[0]);
                    if (strlen($initials) >= 2) break;
                }
                $initials = $initials ?: 'YC';
            ?>
            <article class="dch-card dch-card--<?= $e($statusKey) ?>"
                     data-club-id="<?= (int)$club->club_id ?>">
                <div class="dch-card__header">
                    <div class="dch-card__avatar dch-card__avatar--<?= $e($statusKey) ?>"><?= $e($initials) ?></div>
                    <div class="dch-card__meta">
                        <?php if ((int)$club->open_flags > 0): ?>
                            <span class="dch-card__concern" title="<?= (int)$club->open_flags ?> open concern(s)">
                                <?= (int)$club->open_flags ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="dch-card__score">
                        <strong><?= number_format($overallScore, 0) ?></strong><span>/100</span>
                    </div>
                    <span class="dw-status dw-status--<?= $e($statusKey) ?>"><?= $e($label) ?></span>
                </div>
                <div class="dch-card__identity">
                    <h3><?= $e($club->club_name) ?></h3>
                    <p>
                        <strong><?= $e($club->zonal_name ?? '') ?></strong> /
                        <?= $e($club->division_name ?? '') ?> ·
                        <?= (int)($club->active_members ?? $club->no_of_members ?? 0) ?> active members
                    </p>
                </div>
                <div class="dch-components" aria-label="Score components">
                    <div>
                        <span>Events</span>
                        <strong><?= number_format((float)($score['event_score'] ?? 0), 0) ?></strong>
                    </div>
                    <div>
                        <span>Finance</span>
                        <strong><?= number_format((float)($score['finance_score'] ?? 0), 0) ?></strong>
                    </div>
                    <div>
                        <span>Attendance</span>
                        <strong><?= number_format((float)($score['attendance_score'] ?? 0), 0) ?></strong>
                    </div>
                </div>
                <button class="dw-button dw-button--ghost dch-card__action"
                        type="button"
                        data-club-details="<?= (int)$club->club_id ?>"
                        data-modal-open="club-health-details">
                    View details
                </button>
            </article>
            <?php endforeach; ?>
        </div>

        <div class="dw-empty-state<?= !$clubs ? ' is-visible' : '' ?>" data-health-empty>
            <span class="dw-empty-state__icon">
                <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                </svg>
            </span>
            <strong>No clubs found</strong>
            <p>No clubs match the current filters.</p>
        </div>
    </section>

</section><!-- .dch-page -->

<!-- ══════════════════════════════════════════════════════════════════════════
     JSON DATA BLOB — club details keyed by club_id (read by clubhealth.js)
     ══════════════════════════════════════════════════════════════════════════ -->
<script type="application/json" id="club-health-data"
        data-root="<?= $e(ROOT) ?>"
        data-csrf="<?= $e($csrfToken) ?>">
<?= $detailsJson ?>
</script>

<!-- ══════════════════════════════════════════════════════════════════════════
     MODAL: Formula explanation
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="dw-modal" id="health-formula-modal" role="dialog" aria-modal="true" aria-hidden="true" hidden>
    <div class="dw-modal__backdrop" data-modal-close></div>
    <div class="dw-modal__dialog dw-modal__dialog--wide">
        <header class="dw-modal__header">
            <h2>Club Health Score Formula</h2>
            <button class="dw-modal__close" type="button" data-modal-close aria-label="Close">✕</button>
        </header>
        <div class="dw-modal__body">
            <section class="dw-evidence-section dw-field--span-2">
                <div class="dw-section-header"><div>
                    <h3>Measurement window</h3>
                    <p>The most recent six months, recalculated from live database records whenever a club profile is opened.</p>
                </div></div>
            </section>
            <section class="dw-evidence-section">
                <div class="dw-section-header"><div>
                    <h3>Events — 40%</h3>
                    <p>Completed club events ÷ 6-event target (capped at 100).</p>
                </div></div>
            </section>
            <section class="dw-evidence-section">
                <div class="dw-section-header"><div>
                    <h3>Attendance — 30%</h3>
                    <p>Present records ÷ all recorded attendance for completed club events in the window.</p>
                </div></div>
            </section>
            <section class="dw-evidence-section dw-field--span-2">
                <div class="dw-section-header"><div>
                    <h3>Finance — 30%</h3>
                    <p>40% transaction activity + 30% expense receipt coverage + 30% ledger reconciliation.</p>
                </div></div>
            </section>
            <section class="dw-evidence-section dw-field--span-2">
                <div class="dw-section-header"><div>
                    <h3>Health bands</h3>
                    <p>
                        Above 70: <strong>Healthy (Green)</strong>.<br>
                        30 to 70: <strong>At Risk (Yellow)</strong>.<br>
                        Below 30: <strong>Dormant (Red)</strong> — disband warning and club disbandment controls available.
                    </p>
                </div></div>
            </section>
        </div>
        <footer class="dw-modal__footer">
            <button class="dw-button dw-button--primary" type="button" data-modal-close>Close</button>
        </footer>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     MODAL: Club health profile (detail view — same structure as divisional)
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="dw-modal" id="club-health-details" role="dialog" aria-modal="true" aria-hidden="true" hidden>
    <div class="dw-modal__backdrop" data-modal-close></div>
    <div class="dw-modal__dialog dw-modal__dialog--wide dch-modal">
        <header class="dw-modal__header dch-modal__header">
            <div class="dch-modal__title">
                <div class="dch-modal__avatar" data-detail-avatar></div>
                <div>
                    <h2 data-detail-club-name>Club Health Details</h2>
                    <div class="dch-modal__badges">
                        <span class="dch-code" data-detail-club-code></span>
                        <span class="dw-status" data-detail-status></span>
                    </div>
                </div>
            </div>
            <button class="dw-modal__close" type="button" data-modal-close aria-label="Close">✕</button>
        </header>
        <div class="dw-modal__body dch-modal__body">

            <!-- Score hero -->
            <section class="dch-score-hero">
                <div>
                    <span>Overall health score</span>
                    <strong data-detail-overall>0</strong>
                    <small>/100</small>
                </div>
                <p data-detail-window></p>
            </section>

            <!-- Two-column overview -->
            <div class="dch-overview-grid">
                <aside class="dch-profile-column">
                    <section class="dch-content-block">
                        <h3>About</h3>
                        <p class="dch-about" data-detail-description></p>
                        <dl class="dch-meta-list" data-detail-club-info></dl>
                    </section>
                    <section class="dch-content-block">
                        <h3>Executive Committee</h3>
                        <div class="dch-executives" data-detail-executives></div>
                    </section>
                </aside>
                <div class="dch-performance-column">
                    <section class="dch-content-block">
                        <h3>Performance Overview</h3>
                        <div class="dch-performance-grid" data-detail-performance></div>
                    </section>
                    <section class="dch-content-block">
                        <div class="dw-section-header"><div>
                            <h3>Recent Events and Attendance</h3>
                            <p>Events inside the current scoring window</p>
                        </div></div>
                        <div class="dw-table-wrap">
                            <table class="dw-table">
                                <thead><tr><th>Event</th><th>Date</th><th>Status</th><th>Present</th><th>Attendance</th></tr></thead>
                                <tbody data-detail-events></tbody>
                            </table>
                        </div>
                        <p class="dw-muted-copy" data-detail-events-empty>No club events were recorded in this scoring window.</p>
                    </section>
                </div>
            </div>

            <!-- Health score detail breakdown -->
            <section class="dch-section">
                <div class="dw-section-header"><div>
                    <h3>Health Score Detail</h3>
                    <p>The records and weights used for the current result</p>
                </div></div>
                <div class="dch-breakdown" data-detail-breakdown></div>
            </section>

            <!-- Financial details -->
            <section class="dch-section">
                <div class="dw-section-header"><div>
                    <h3>Financial Details</h3>
                    <p>Ledger activity, documentation, reconciliation, and audit findings</p>
                </div></div>
                <div class="dw-impact-grid" data-detail-finance-summary></div>
                <div class="dw-table-wrap">
                    <table class="dw-table">
                        <thead><tr><th>Date</th><th>Type</th><th>Description</th><th>Amount</th><th>Receipt</th><th>Reconciled</th><th>Status</th></tr></thead>
                        <tbody data-detail-finance-entries></tbody>
                    </table>
                </div>
                <p class="dw-muted-copy" data-detail-finance-empty>No ledger entries were recorded in this scoring window.</p>
                <div class="dch-subsection">
                    <h4>Recent audits and financial flags</h4>
                    <div data-detail-audits></div>
                </div>
            </section>

            <!-- Six-month history + health concerns -->
            <div class="dch-lower-grid">
                <section class="dch-section">
                    <div class="dw-section-header"><div>
                        <h3>Six-Month Health History</h3>
                        <p>Monthly calculations used to detect sustained dormancy</p>
                    </div></div>
                    <div class="dw-table-wrap">
                        <table class="dw-table">
                            <thead><tr><th>Month</th><th>Events</th><th>Finance</th><th>Attendance</th><th>Overall</th><th>Status</th></tr></thead>
                            <tbody data-detail-history></tbody>
                        </table>
                    </div>
                </section>
                <section class="dch-section">
                    <div class="dw-section-header"><div>
                        <h3>Health Concerns</h3>
                        <p>Automatic and officer-raised concerns requiring review</p>
                    </div></div>
                    <div data-detail-flags></div>
                </section>
            </div>

            <!-- ── NYSC-only: Dormant club disband controls ────────────── -->
            <div class="dch-disband-panel" data-disband-panel style="display:none;">
                <div class="dch-disband-panel__header">
                    <h3>⚠ Dormant Club — Disbandment Controls</h3>
                    <p data-disband-subtitle>This club is in the Red (Dormant) band.</p>
                </div>
                <div class="dch-disband-panel__actions">
                    <button class="dw-button dw-button--warning" type="button" data-warn-btn>
                        Issue Disband Warning
                    </button>
                    <button class="dw-button dw-button--danger" type="button" data-exec-disband-btn>
                        Execute Disband
                    </button>
                </div>
                <p class="dch-disband-balance" data-disband-balance-note style="display:none;"></p>
            </div>

        </div><!-- /.dch-modal__body -->
        <footer class="dw-modal__footer">
            <button class="dw-button dw-button--secondary" type="button" data-modal-close>Close</button>
        </footer>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     MODAL: Confirm disband execution
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="dw-modal" id="club-health-disband" role="dialog" aria-modal="true" aria-hidden="true" hidden>
    <div class="dw-modal__backdrop" data-modal-close></div>
    <div class="dw-modal__dialog">
        <header class="dw-modal__header">
            <h2>Execute Disband</h2>
            <button class="dw-modal__close" type="button" data-modal-close aria-label="Close">✕</button>
        </header>
        <div class="dw-modal__body">
            <div class="dw-alert dw-alert--error" style="margin-bottom:14px;">
                <span>This action is <strong>permanent and audited</strong>. The club will be archived and all member access revoked.</span>
            </div>
            <ul class="dch-disband-checklist">
                <li>A <strong>Fund Transfer Out</strong> ledger entry will clear the club balance.</li>
                <li>All executive and member accounts become <strong>Unassigned</strong>.</li>
                <li>The club will no longer appear in active zonal, divisional or national filters.</li>
            </ul>
            <div class="dw-field" style="margin-top:14px;">
                <label for="chDisbandReason">Reason for disbandment <strong>(minimum 10 characters)</strong></label>
                <textarea id="chDisbandReason" rows="3"
                          placeholder="e.g. Dormant for more than 6 months despite two formal warnings — no active events, attendance below 10% and no ledger activity."></textarea>
            </div>
            <label class="dch-disband-ack">
                <input type="checkbox" id="chDisbandAck">
                I understand this action is permanent and will be recorded in the audit log.
            </label>
        </div>
        <footer class="dw-modal__footer">
            <button class="dw-button dw-button--secondary" type="button" data-modal-close>Cancel</button>
            <button class="dw-button dw-button--danger" id="chDisbandConfirm" data-club="" disabled>
                Execute Disband
            </button>
        </footer>
    </div>
</div>

<script src="<?= ROOT ?>/assets/js/clubhealth.js" defer></script>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
