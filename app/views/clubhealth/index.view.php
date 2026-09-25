<?php
require_once __DIR__ . '/../partials/icons.view.php';
$e = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$mediaUrl = static function ($path) use ($e): string {
    $path = trim((string) $path);
    if ($path === '') return '';
    if (preg_match('/^https?:\/\//i', $path)) return $e($path);
    return $e(rtrim(ROOT, '/') . '/' . ltrim($path, '/'));
};
$statusLabel = static fn($status) => $status === 'Green' ? 'Healthy' : ($status === 'Yellow' ? 'At Risk' : 'Dormant');
$statusTone  = static fn($status) => $status === 'Green' ? 'green' : ($status === 'Yellow' ? 'amber' : 'red');

$title           = $title ?? 'Monitor Club Health — NYSC Administrator';
$pageTitle       = $pageTitle ?? 'Monitor Club Health';
$pageDescription = $pageDescription ?? 'National Club Performance Monitoring, Health Scoring & Disbandment Governance';
$currentRoute    = 'clubhealth';

$pageStyles = [
    ROOT . '/assets/css/divisional-workflows.css',
    ROOT . '/assets/css/divisional-club-health.css',
    ROOT . '/assets/css/nysc-club-health.css'
];
$pageScripts = [
    ROOT . '/assets/js/divisional-workflows.js',
    ROOT . '/assets/js/nysc-club-health.js'
];

$summaryCards = [
    ['value' => $summary['green'],   'label' => 'Healthy Clubs',    'note' => 'Score above 70',         'icon' => 'check', 'tone' => 'green'],
    ['value' => $summary['yellow'],  'label' => 'At Risk Clubs',    'note' => 'Score from 30 to 70',     'icon' => 'clock', 'tone' => 'amber'],
    ['value' => $summary['red'],     'label' => 'Dormant Clubs',    'note' => 'Score below 30',         'icon' => 'info',  'tone' => 'red'],
    ['value' => $summary['flagged'], 'label' => 'Open Concerns',    'note' => 'Clubs requiring review', 'icon' => 'file',  'tone' => 'red'],
];

$detailsJson   = json_encode($clubDetails, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?: '{}';
$divisionsJson = json_encode($divisions,   JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?: '[]';

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<section class="dw-page" aria-label="National club health monitoring">
  <?php if ($flash): ?>
    <div class="dw-alert dw-alert--<?= $flash['type'] === 'success' ? 'success' : 'error' ?>" role="status">
      <?= yn_icon($flash['type'] === 'success' ? 'check' : 'info') ?>
      <span><?= $e($flash['message']) ?></span>
    </div>
  <?php endif; ?>

  <!-- Summary Statistics Grid -->
  <div class="dw-summary-grid" aria-label="Nationwide club health summary">
    <?php foreach ($summaryCards as $card): ?>
      <?php require __DIR__ . '/../partials/divisional/summary-card.view.php'; ?>
    <?php endforeach; ?>
  </div>

  <!-- Multi-Level Filter Toolbar (Province, Division, Dormant/Flagged Status, Search) -->
  <div class="nysc-toolbar" aria-label="National Filter Controls">
    <div class="nysc-filter-row">
      <!-- Search Input -->
      <div class="nysc-filter-group">
        <label for="health-search">Search Clubs</label>
        <input id="health-search" type="search" placeholder="Search club, division, or province..." data-health-search>
      </div>

      <!-- Province Dropdown -->
      <div class="nysc-filter-group">
        <label for="filter-zone">Province</label>
        <select id="filter-zone" data-zone-filter>
          <option value="">All Provinces</option>
          <?php foreach ($provinces as $prov): ?>
            <option value="<?= $e($prov->province) ?>"><?= $e($prov->province) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Division Dropdown (Populated based on Province) -->
      <div class="nysc-filter-group">
        <label for="filter-division">Division</label>
        <select id="filter-division" data-division-filter>
          <option value="">All Divisions</option>
          <?php foreach ($divisions as $division): ?>
            <option value="<?= (int)$division->division_id ?>" data-province="<?= $e($division->province ?? '') ?>"><?= $e($division->division_name) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Type / Dormant / Flagged Filter -->
      <div class="nysc-filter-group">
        <label for="filter-type">Status Type</label>
        <select id="filter-type" data-type-filter>
          <option value="">All Types</option>
          <option value="active">Active</option>
          <option value="flagged">Flagged</option>
          <option value="dormant">Dormant Clubs</option>
        </select>
      </div>

      <!-- Action Buttons -->
      <div class="nysc-filter-buttons">
        <button class="dw-button dw-button--primary" type="button" data-health-apply>Apply</button>
        <button class="dw-button dw-button--secondary" type="button" data-health-reset>Clear</button>
        <a class="dw-button dw-button--ghost" href="<?= ROOT ?>/clubhealth/export" title="Export CSV Report">
          <?= yn_icon('download') ?> Export
        </a>
      </div>
    </div>
  </div>

  <!-- Formula Note Banner -->
  <section class="dw-formula-note" aria-labelledby="health-formula-title">
    <div>
      <h2 id="health-formula-title">National Club Health Scoring System</h2>
      <p>Verified 6-month rolling audit: Events (40%) + Finances (30%) + Attendance (30%). Clubs below 30 are designated Dormant and eligible for disbandment review.</p>
    </div>
    <button class="dw-button dw-button--ghost" type="button" data-modal-open="health-formula-modal">View Formula</button>
  </section>

  <!-- Club Cards Grid -->
  <section aria-labelledby="club-health-list-title">
    <div class="dw-section-header">
      <div>
        <h2 id="club-health-list-title">Clubs Nationwide</h2>
        <p>Click any club card to inspect general details, records, balance, and proceed with disbandment or warning</p>
      </div>
      <span class="dw-count" data-health-count><?= count($clubs) ?> clubs</span>
    </div>

    <div class="dch-grid" data-health-grid>

      <!-- DEMO: Healthy Club Card (Green) -->
      <article class="dch-card dch-card--green"
               data-health-card
               data-club-id="demo-1"
               data-zone-id="1"
               data-division-id="1"
               data-province="Western Province"
               data-name="western youth excellence club"
               data-name-original="Western Youth Excellence Club"
               data-search="western youth excellence club colombo division western province healthy green"
               data-status="green"
               data-club-status="Active"
               data-score="88"
               data-flags="0"
               data-flagged="0"
               data-demo="1">
        <div class="dch-card__visual">
          <div class="dch-card__avatar dch-card__avatar--green"><span>WY</span></div>
        </div>
        <div class="dch-card__score"><strong>88</strong><span>/100</span></div>
        <span class="dw-status dw-status--green">Healthy</span>
        <div class="dch-card__identity">
          <h3>Western Youth Excellence Club</h3>
          <p>Colombo Division • Western Province</p>
          <span class="dch-card__zone-badge">47 Members</span>
        </div>
        <div class="dch-components" aria-label="Score components">
          <div><span>Events</span><strong>90</strong></div>
          <div><span>Finance</span><strong>85</strong></div>
          <div><span>Attendance</span><strong>88</strong></div>
        </div>
        <button class="dw-button dw-button--ghost dch-card__action" type="button" disabled title="Demo record — not linked to a real club">
          <?= yn_icon('eye') ?> Demo Record
        </button>
      </article>

      <!-- DEMO: At Risk Club Card (Yellow) -->
      <article class="dch-card dch-card--amber"
               data-health-card
               data-club-id="demo-2"
               data-zone-id="2"
               data-division-id="4"
               data-province="Central Province"
               data-name="kandy future leaders youth club"
               data-name-original="Kandy Future Leaders Youth Club"
               data-search="kandy future leaders youth club kandy division central province at risk yellow"
               data-status="yellow"
               data-club-status="Active"
               data-score="52"
               data-flags="0"
               data-flagged="0"
               data-demo="1">
        <div class="dch-card__visual">
          <div class="dch-card__avatar dch-card__avatar--amber"><span>KF</span></div>
        </div>
        <div class="dch-card__score"><strong>52</strong><span>/100</span></div>
        <span class="dw-status dw-status--yellow">At Risk</span>
        <div class="dch-card__identity">
          <h3>Kandy Future Leaders Youth Club</h3>
          <p>Kandy Division • Central Province</p>
          <span class="dch-card__zone-badge">23 Members</span>
        </div>
        <div class="dch-components" aria-label="Score components">
          <div><span>Events</span><strong>55</strong></div>
          <div><span>Finance</span><strong>60</strong></div>
          <div><span>Attendance</span><strong>40</strong></div>
        </div>
        <button class="dw-button dw-button--ghost dch-card__action" type="button" disabled title="Demo record — not linked to a real club">
          <?= yn_icon('eye') ?> Demo Record
        </button>
      </article>

      <!-- Real DB Club Cards -->
      <?php foreach ($clubs as $club): ?>
        <?php
        $score = $club->score;
        $tone = $statusTone($score['health_status']);
        $label = $statusLabel($score['health_status']);
        $isFlagged = ((int)$club->flagged === 1 || (int)$club->open_flags > 0);
        $initials = '';
        foreach (preg_split('/\s+/', trim($club->club_name)) as $word) {
            if ($word !== '') $initials .= strtoupper(substr($word, 0, 1));
        }
        $initials = substr($initials, 0, 2) ?: 'CL';
        // Derive province from zone name stored in DB (Zone table has province column via JOIN)
        $clubProvince = $club->province ?? $club->zonal_name ?? '';
        $searchString = strtolower(implode(' ', [
            $club->club_name,
            $club->club_code,
            $club->division_name,
            $club->zonal_name,
            $clubProvince,
            $label,
            $isFlagged ? 'flagged' : '',
            $score['health_status'] === 'Red' ? 'dormant' : ''
        ]));
        ?>
        <article class="dch-card dch-card--<?= $e($tone) ?>"
                 data-health-card
                 data-club-id="<?= (int)$club->club_id ?>"
                 data-zone-id="<?= (int)$club->zonal_id ?>"
                 data-division-id="<?= (int)$club->division_id ?>"
                 data-province="<?= $e($clubProvince) ?>"
                 data-name="<?= $e(strtolower($club->club_name)) ?>"
                 data-name-original="<?= $e($club->club_name) ?>"
                 data-search="<?= $e($searchString) ?>"
                 data-status="<?= $e(strtolower($score['health_status'])) ?>"
                 data-club-status="<?= $e($club->status) ?>"
                 data-score="<?= $e($score['overall_score']) ?>"
                 data-flags="<?= (int)$club->open_flags ?>"
                 data-flagged="<?= $isFlagged ? '1' : '0' ?>">

          <!-- Red Flag Badge at Top Right Corner if Flagged -->
          <?php if ($isFlagged): ?>
            <div class="dch-card__flag-badge" title="Flagged Club / Open Review Concern">
              <?= yn_icon('file') ?> Flagged
            </div>
          <?php endif; ?>

          <div class="dch-card__visual">
            <div class="dch-card__avatar dch-card__avatar--<?= $e($tone) ?>">
              <?php if (!empty($club->club_logo_path)): ?>
                <img src="<?= $mediaUrl($club->club_logo_path) ?>" alt="<?= $e($club->club_name) ?> profile image">
              <?php else: ?>
                <span><?= $e($initials) ?></span>
              <?php endif; ?>
            </div>
          </div>

          <div class="dch-card__score">
            <strong><?= $e(number_format($score['overall_score'], 0)) ?></strong>
            <span>/100</span>
          </div>

          <span class="dw-status dw-status--<?= $e(strtolower($score['health_status'])) ?>">
            <?= $e($label) ?>
          </span>

          <div class="dch-card__identity">
            <h3><?= $e($club->club_name) ?></h3>
            <p><?= $e($club->division_name) ?> • <?= $e($club->zonal_name) ?></p>
            <span class="dch-card__zone-badge"><?= (int)$club->active_members ?> Members</span>
          </div>

          <div class="dch-components" aria-label="Score components">
            <div>
              <span>Events</span>
              <strong><?= $e(number_format($score['event_score'], 0)) ?></strong>
            </div>
            <div>
              <span>Finance</span>
              <strong><?= $e(number_format($score['finance_score'], 0)) ?></strong>
            </div>
            <div>
              <span>Attendance</span>
              <strong><?= $e(number_format($score['attendance_score'], 0)) ?></strong>
            </div>
          </div>

          <button class="dw-button dw-button--ghost dch-card__action"
                  type="button"
                  data-club-details="<?= (int)$club->club_id ?>">
            <?= yn_icon('eye') ?> View Details &amp; Profile
          </button>
        </article>
      <?php endforeach; ?>
    </div>

    <div class="dw-empty-state<?= !$clubs ? ' is-visible' : '' ?>" data-health-empty>
      <span class="dw-empty-state__icon"><?= yn_icon('file') ?></span>
      <strong>No clubs found</strong>
      <p>No clubs match the selected filters. Try changing or clearing your filter criteria.</p>
    </div>
  </section>
</section>

<!-- Data JSON Caches for Client-side Speed & Responsiveness -->
<script type="application/json" id="club-health-data" data-root="<?= $e(ROOT) ?>"><?= $detailsJson ?></script>
<script type="application/json" id="division-data"><?= $divisionsJson ?></script>

<!-- Formula Modal -->
<div class="dw-modal" id="health-formula-modal" role="dialog" aria-modal="true" aria-hidden="true" hidden>
  <div class="dw-modal__backdrop" data-modal-close></div>
  <div class="dw-modal__dialog dw-modal__dialog--wide">
    <header class="dw-modal__header">
      <h2>National Club Health Score Formula</h2>
      <button class="dw-modal__close" type="button" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
    </header>
    <div class="dw-modal__body">
      <section class="dw-evidence-section dw-field--span-2">
        <div class="dw-section-header">
          <div>
            <h3>Rolling Window Calculation</h3>
            <p>Scores are calculated over a continuous rolling six-month evaluation period from live database records.</p>
          </div>
        </div>
      </section>
      <section class="dw-evidence-section">
        <div class="dw-section-header">
          <div>
            <h3>Events Component — 40%</h3>
            <p>Target of 6 club events within 6 months. Normalized as (completed_events / 6) * 100.</p>
          </div>
        </div>
      </section>
      <section class="dw-evidence-section">
        <div class="dw-section-header">
          <div>
            <h3>Attendance Component — 30%</h3>
            <p>Percentage of present attendees relative to all recorded attendees across completed club events.</p>
          </div>
        </div>
      </section>
      <section class="dw-evidence-section dw-field--span-2">
        <div class="dw-section-header">
          <div>
            <h3>Finances Component — 30%</h3>
            <p>Evaluates financial activity (40%), expense receipt coverage (30%), and ledger reconciliation (30%).</p>
          </div>
        </div>
      </section>
      <section class="dw-evidence-section dw-field--span-2">
        <div class="dw-section-header">
          <div>
            <h3>Health Thresholds &amp; Disbandment Rules</h3>
            <p>Above 70: Healthy (Green). 30 to 70: At Risk (Yellow). Below 30: Dormant (Red). 6 consecutive dormant months trigger an automatic review flag with administrative disbandment recommendations.</p>
          </div>
        </div>
      </section>
    </div>
    <footer class="dw-modal__footer">
      <button class="dw-button dw-button--primary" type="button" data-modal-close>Close</button>
    </footer>
  </div>
</div>

<!-- Main Club Health & General Details Modal -->
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
      <button class="dw-modal__close" type="button" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
    </header>

    <div class="dw-modal__body dch-modal__body">
      <!-- Overall Score Banner -->
      <section class="dch-score-hero">
        <div>
          <span>Overall Health Score</span>
          <strong data-detail-overall>0</strong>
          <small>/100</small>
        </div>
        <p data-detail-window></p>
      </section>

      <!-- General Details Highlights (Start date, President, Events, Ledger Balance, Asset count) -->
      <section class="nysc-general-details" aria-label="General club details">
        <div class="nysc-detail-metric">
          <span>Start Date</span>
          <strong data-detail-start-date>—</strong>
        </div>
        <div class="nysc-detail-metric">
          <span>President Name</span>
          <strong data-detail-president-name>—</strong>
        </div>
        <div class="nysc-detail-metric">
          <span>Events Conducted</span>
          <strong data-detail-events-conducted>0 Events</strong>
        </div>
        <div class="nysc-detail-metric nysc-detail-metric--fund">
          <span>Balance in Ledger</span>
          <strong data-detail-ledger-balance>Rs. 0.00</strong>
        </div>
        <div class="nysc-detail-metric">
          <span>Total Asset Count</span>
          <strong data-detail-asset-count>0 Assets</strong>
        </div>
      </section>

      <!-- Governance / Disbandment Action Bar (Visible if Flagged or Dormant) -->
      <section class="nysc-disband-actions" data-disband-panel hidden>
        <div class="nysc-disband-actions__header">
          <div class="nysc-disband-actions__title">
            <?= yn_icon('file') ?>
            <span>Administrative Governance Review (Dormancy / Concern Detected)</span>
          </div>
          <div class="nysc-disband-actions__buttons">
            <!-- Issue Disband Warning Button -->
            <button class="dw-button nysc-btn-warning" type="button" data-open-disband-warning>
              Issue Disband Warning
            </button>

            <!-- Retrieve Remaining Funds Button (if Balance > 0) -->
            <button class="dw-button nysc-btn-funds" type="button" data-btn-retrieve-funds hidden>
              Retrieve Remaining Funds
            </button>

            <!-- Execute Disband Button -->
            <button class="dw-button nysc-btn-disband" type="button" data-btn-execute-disband>
              Execute Disband
            </button>
          </div>
        </div>
        <p class="dw-alert dw-alert--warning" data-disband-lock-msg hidden style="margin: 0; font-size: 12px;"></p>
      </section>

      <!-- Profile & Performance Columns -->
      <div class="dch-overview-grid">
        <aside class="dch-profile-column">
          <section class="dch-content-block">
            <h3>About Club</h3>
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
            <div class="dw-section-header">
              <div>
                <h3>Recent Events &amp; Attendance Records</h3>
                <p>Events recorded inside the active six-month scoring window</p>
              </div>
            </div>
            <div class="dw-table-wrap">
              <table class="dw-table">
                <thead>
                  <tr>
                    <th>Event</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Present</th>
                    <th>Attendance Rate</th>
                  </tr>
                </thead>
                <tbody data-detail-events></tbody>
              </table>
            </div>
            <p class="dw-muted-copy" data-detail-events-empty>No completed club events in this scoring window.</p>
          </section>
        </div>
      </div>

      <!-- Health Score Detail Breakdown -->
      <section class="dch-section">
        <div class="dw-section-header">
          <div>
            <h3>Health Score Detail</h3>
            <p>Mathematical component weights and verified system inputs</p>
          </div>
        </div>
        <div class="dch-breakdown" data-detail-breakdown></div>
      </section>

      <!-- Financial Details -->
      <section class="dch-section">
        <div class="dw-section-header">
          <div>
            <h3>Financial Details &amp; Ledger Activity</h3>
            <p>Ledger balances, receipts, reconciliations, and annual audit findings</p>
          </div>
        </div>
        <div class="dw-impact-grid" data-detail-finance-summary></div>
        <div class="dw-table-wrap">
          <table class="dw-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Description</th>
                <th>Amount</th>
                <th>Receipt</th>
                <th>Reconciled</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody data-detail-finance-entries></tbody>
          </table>
        </div>
        <p class="dw-muted-copy" data-detail-finance-empty>No ledger entries recorded in this scoring window.</p>
        <div class="dch-subsection">
          <h4>Recent Audits &amp; Financial Concerns</h4>
          <div data-detail-audits></div>
        </div>
      </section>

      <!-- Health History & Concerns -->
      <div class="dch-lower-grid">
        <section class="dch-section">
          <div class="dw-section-header">
            <div>
              <h3>Six-Month Health History</h3>
              <p>Historical monthly score calculations used to evaluate prolonged dormancy</p>
            </div>
          </div>
          <div class="dw-table-wrap">
            <table class="dw-table">
              <thead>
                <tr>
                  <th>Month</th>
                  <th>Events</th>
                  <th>Finance</th>
                  <th>Attendance</th>
                  <th>Overall</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody data-detail-history></tbody>
            </table>
          </div>
        </section>

        <section class="dch-section">
          <div class="dw-section-header">
            <div>
              <h3>Health Concerns &amp; Flags</h3>
              <p>System automatic flags and officer-raised governance warnings</p>
            </div>
          </div>
          <div data-detail-flags></div>
        </section>
      </div>
    </div>

    <footer class="dw-modal__footer">
      <button class="dw-button dw-button--secondary" type="button" data-modal-close>Close</button>
    </footer>
  </div>
</div>

<!-- Popup 1: Issue Disband Warning Modal -->
<div class="dw-modal" id="nysc-warning-modal" role="dialog" aria-modal="true" aria-hidden="true" hidden>
  <div class="dw-modal__backdrop" data-modal-close></div>
  <form class="dw-modal__dialog" action="" method="post" data-warning-form data-action-base="<?= ROOT ?>/clubhealth/warning/">
    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
    <header class="dw-modal__header">
      <h2>Issue Disband Warning</h2>
      <button class="dw-modal__close" type="button" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
    </header>
    <div class="dw-modal__body">
      <div class="dw-detail-box dw-field--span-2">
        <span>Target Club</span>
        <strong data-warning-club-name></strong>
      </div>
      <div class="dw-detail-box dw-field--span-2">
        <span>Recipients (President &amp; Secretary)</span>
        <span data-warning-recipients style="font-size: 13px; font-weight: 600; color: #334155;"></span>
      </div>
      <div class="dw-field dw-field--span-2">
        <label for="warning-subject">Warning Notice Subject</label>
        <input id="warning-subject" name="subject" type="text" required value="Urgent Disband Warning: Inadequate Club Health Performance">
      </div>
      <div class="dw-field dw-field--span-2">
        <label for="warning-message">Official Notification Message</label>
        <textarea id="warning-message" name="message" minlength="10" maxlength="1000" rows="5" required
                  placeholder="Enter the official notification message outlining performance deficiencies, corrective expectations, and deadlines."></textarea>
      </div>
      <div class="dw-alert dw-alert--warning dw-field--span-2">
        <?= yn_icon('info') ?>
        <span>Submitting this form immediately:
          <br>1. Dispatches an official warning email to the Club President &amp; Secretary.
          <br>2. Posts an urgent high-priority system notification to club executive dashboards.
          <br>3. Records a governance review flag in the national audit registry.
        </span>
      </div>
    </div>
    <footer class="dw-modal__footer">
      <button class="dw-button dw-button--secondary" type="button" data-modal-close>Cancel</button>
      <button class="dw-button nysc-btn-warning" type="submit">Send Warning Notification</button>
    </footer>
  </form>
</div>

<!-- Popup 2: Retrieve Remaining Funds Confirmation Modal -->
<div class="dw-modal" id="nysc-retrieve-funds-modal" role="dialog" aria-modal="true" aria-hidden="true" hidden>
  <div class="dw-modal__backdrop" data-modal-close></div>
  <form class="dw-modal__dialog" action="" method="post" data-retrieve-funds-form data-action-base="<?= ROOT ?>/clubhealth/retrievefunds/">
    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
    <header class="dw-modal__header">
      <h2>Retrieve Remaining Club Funds</h2>
      <button class="dw-modal__close" type="button" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
    </header>
    <div class="dw-modal__body">
      <div class="dw-detail-box dw-field--span-2">
        <span>Target Club</span>
        <strong data-retrieve-club-name></strong>
      </div>
      <div class="dw-detail-box dw-field--span-2">
        <span>Remaining Ledger Balance to Sweep</span>
        <strong data-retrieve-amount style="color: #047857; font-size: 18px;"></strong>
      </div>
      <div class="nysc-confirm-box dw-field--span-2" style="background: #f0fdf4; border-color: #22c55e; color: #15803d;">
        <strong>Treasury Fund Sweep Operation</strong>
        <ul>
          <li>A <strong>Fund Transfer Out</strong> expense entry is posted in the Club Ledger (Balance becomes 0.00).</li>
          <li>A <strong>Fund Allocation In</strong> income entry is credited into the NYSC National Central Ledger.</li>
          <li>Zeroing the club funds unlocks the <strong>Execute Disband</strong> operation.</li>
        </ul>
      </div>
    </div>
    <footer class="dw-modal__footer">
      <button class="dw-button dw-button--secondary" type="button" data-modal-close>Cancel</button>
      <button class="dw-button nysc-btn-funds" type="submit">Confirm &amp; Retrieve Funds</button>
    </footer>
  </form>
</div>

<!-- Popup 3: Execute Disband Confirmation Modal -->
<div class="dw-modal" id="nysc-disband-modal" role="dialog" aria-modal="true" aria-hidden="true" hidden>
  <div class="dw-modal__backdrop" data-modal-close></div>
  <form class="dw-modal__dialog" action="" method="post" data-disband-form data-action-base="<?= ROOT ?>/clubhealth/disband/">
    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
    <header class="dw-modal__header">
      <h2>Execute Club Disbandment</h2>
      <button class="dw-modal__close" type="button" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
    </header>
    <div class="dw-modal__body">
      <div class="nysc-confirm-box dw-field--span-2">
        <strong>Are you sure? This will archive all club data and revoke access.</strong>
        <ul>
          <li>Club status is set to <strong>Disbanded</strong> and archived.</li>
          <li>Club executives are revoked (their role becomes <strong>"UnassignedUser"</strong>).</li>
          <li>The club will <strong>NO LONGER appear</strong> in active Zonal or Divisional filter listings.</li>
          <li>Club ledger will be permanently closed.</li>
        </ul>
      </div>
      <div class="dw-detail-box dw-field--span-2">
        <span>Target Club</span>
        <strong data-disband-club-name></strong>
      </div>
      <div class="dw-field dw-field--span-2">
        <label for="disband-reason">Official Disbandment Justification / Reason</label>
        <textarea id="disband-reason" name="reason" minlength="10" maxlength="500" rows="4" required
                  placeholder="State the regulatory grounds for disbandment (e.g. Chronic Dormancy, failure to remediate warnings, financial non-compliance)."></textarea>
      </div>
    </div>
    <footer class="dw-modal__footer">
      <button class="dw-button dw-button--secondary" type="button" data-modal-close>No, Return to Dashboard</button>
      <button class="dw-button nysc-btn-disband" type="submit">Yes, Execute Disband</button>
    </footer>
  </form>
</div>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
