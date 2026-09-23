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
$statusTone = static fn($status) => $status === 'Green' ? 'green' : ($status === 'Yellow' ? 'amber' : 'red');
$title = 'Monitor Club Health - YouthNexus';
$pageTitle = 'Monitor Club Health';
$pageDescription = 'Review calculated club performance and concerns within your division';
$currentRoute = 'divisionalclubhealth';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css', ROOT . '/assets/css/divisional-club-health.css'];
$pageScripts = [ROOT . '/assets/js/divisional-workflows.js', ROOT . '/assets/js/divisional-club-health.js'];
$summaryCards = [
    ['value' => $summary['green'], 'label' => 'Healthy Clubs', 'note' => 'Score above 70', 'icon' => 'check', 'tone' => 'green'],
    ['value' => $summary['yellow'], 'label' => 'At Risk Clubs', 'note' => 'Score from 30 to 70', 'icon' => 'clock', 'tone' => 'amber'],
    ['value' => $summary['red'], 'label' => 'Dormant Clubs', 'note' => 'Score below 30', 'icon' => 'info', 'tone' => 'red'],
    ['value' => $summary['flagged'], 'label' => 'Open Concerns', 'note' => 'Clubs requiring review', 'icon' => 'file', 'tone' => 'red'],
];
$flagLabels = [
    'DivisionalTreasurer' => 'Raise Financial Concern',
    'DivisionalSecretary' => 'Raise Event or Attendance Concern',
    'DivisionalCoordinator' => 'Raise Concern',
];
$flagCategoryLabels = [
    'FinancialConcern' => 'Financial concern',
    'EventAttendanceConcern' => 'Event or attendance concern',
    'GovernanceConcern' => 'Governance concern',
];
$detailsJson = json_encode($clubDetails, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?: '{}';
require __DIR__ . '/../layouts/dashboard-start.view.php';
?>
<section class="dw-page" aria-label="Club health monitoring">
  <?php if ($flash): ?>
    <div class="dw-alert dw-alert--<?= $flash['type'] === 'success' ? 'success' : 'error' ?>" role="status"><?= yn_icon($flash['type'] === 'success' ? 'check' : 'info') ?><span><?= $e($flash['message']) ?></span></div>
  <?php endif; ?>

  <div class="dw-summary-grid" aria-label="Club health summary">
    <?php foreach ($summaryCards as $card): ?><?php require __DIR__ . '/../partials/divisional/summary-card.view.php'; ?><?php endforeach; ?>
  </div>

  <div class="dw-toolbar" aria-label="Club health tools">
    <div class="dw-toolbar__search dw-search dw-search--plain"><label class="visually-hidden" for="health-search">Search clubs</label><input id="health-search" type="search" placeholder="Search by club name, code, or division" data-health-search></div>
    <button class="dw-button dw-button--secondary" type="button" data-filter-toggle aria-controls="health-filters" aria-expanded="false">Filters</button>
    <a class="dw-button dw-button--secondary" href="<?= ROOT ?>/divisionalclubhealth/export"><?= yn_icon('download') ?> Export</a>
  </div>

  <section class="dw-filter-panel" id="health-filters" hidden>
    <h2 class="dw-filter-panel__heading">Advanced Filters for Club Health</h2>
    <div class="dw-filter-grid">
      <div class="dw-field"><label for="health-status-filter">Health status</label><select id="health-status-filter" data-health-status><option value="">All statuses</option><option value="green">Healthy</option><option value="yellow">At Risk</option><option value="red">Dormant</option></select></div>
      <div class="dw-field"><label for="health-flag-filter">Concern status</label><select id="health-flag-filter" data-health-flag><option value="">All clubs</option><option value="flagged">Has open concern</option><option value="clear">No open concern</option></select></div>
      <div class="dw-field"><label for="health-min-score">Minimum score</label><input id="health-min-score" type="number" min="0" max="100" data-health-min></div>
      <div class="dw-field"><label for="health-max-score">Maximum score</label><input id="health-max-score" type="number" min="0" max="100" data-health-max></div>
      <div class="dw-field"><label for="health-sort">Sort by</label><select id="health-sort" data-health-sort><option value="score-high">Health score (high to low)</option><option value="score-low">Health score (low to high)</option><option value="name">Club name</option><option value="flags">Open concerns</option></select></div>
    </div>
    <div class="dw-filter-actions"><button class="dw-button dw-button--secondary" type="button" data-health-reset>Reset all</button><button class="dw-button dw-button--primary" type="button" data-health-apply>Apply filters</button></div>
  </section>

  <section class="dw-formula-note" aria-labelledby="health-formula-title">
    <div><h2 id="health-formula-title">How the score is calculated</h2><p>A rolling six-month score from verified system records. Overall score = Events 40% + Finances 30% + Attendance 30%.</p></div>
    <button class="dw-button dw-button--ghost" type="button" data-modal-open="health-formula-modal">View formula</button>
  </section>

  <section aria-labelledby="club-health-list-title">
    <div class="dw-section-header"><div><h2 id="club-health-list-title">Clubs in <?= $e($division->division_name) ?></h2><p>Open a club to review the records behind its score</p></div><span class="dw-count" data-health-count><?= count($clubs) ?> clubs</span></div>
    <div class="dch-grid" data-health-grid>
      <?php foreach ($clubs as $club): ?>
        <?php
        $score = $club->score;
        $tone = $statusTone($score['health_status']);
        $label = $statusLabel($score['health_status']);
        $initials = '';
        foreach (preg_split('/\s+/', trim($club->club_name)) as $word) if ($word !== '') $initials .= strtoupper(substr($word, 0, 1));
        $initials = substr($initials, 0, 2) ?: 'CL';
        $search = strtolower(implode(' ', [$club->club_name, $club->club_code, $club->division_name, $label]));
        ?>
        <article class="dch-card dch-card--<?= $e($tone) ?>" data-health-card data-search="<?= $e($search) ?>" data-status="<?= $e(strtolower($score['health_status'])) ?>" data-score="<?= $e($score['overall_score']) ?>" data-flags="<?= (int) $club->open_flags ?>" data-name="<?= $e(strtolower($club->club_name)) ?>">
          <div class="dch-card__visual">
            <div class="dch-card__avatar dch-card__avatar--<?= $e($tone) ?>"><?php if (!empty($club->club_logo_path)): ?><img src="<?= $mediaUrl($club->club_logo_path) ?>" alt="<?= $e($club->club_name) ?> profile image"><?php else: ?><span><?= $e($initials) ?></span><?php endif; ?></div>
            <?php if ((int) $club->open_flags > 0): ?><span class="dch-card__concern" title="<?= (int) $club->open_flags ?> open <?= (int) $club->open_flags === 1 ? 'concern' : 'concerns' ?>"><?= (int) $club->open_flags ?></span><?php endif; ?>
          </div>
          <div class="dch-card__score"><strong><?= $e(number_format($score['overall_score'], 0)) ?></strong><span>/100</span></div>
          <span class="dw-status dw-status--<?= $e(strtolower($score['health_status'])) ?>"><?= $e($label) ?></span>
          <div class="dch-card__identity"><h3><?= $e($club->club_name) ?></h3><p><?= $e($club->division_name) ?> - <?= (int) $club->active_members ?> active members</p></div>
          <div class="dch-components" aria-label="Score components">
            <div><span>Events</span><strong><?= $e(number_format($score['event_score'], 0)) ?></strong></div>
            <div><span>Finance</span><strong><?= $e(number_format($score['finance_score'], 0)) ?></strong></div>
            <div><span>Attendance</span><strong><?= $e(number_format($score['attendance_score'], 0)) ?></strong></div>
          </div>
          <button class="dw-button dw-button--ghost dch-card__action" type="button" data-club-details="<?= (int) $club->club_id ?>" data-modal-open="club-health-details"><?= yn_icon('eye') ?> View details</button>
        </article>
      <?php endforeach; ?>
    </div>
    <div class="dw-empty-state<?= !$clubs ? ' is-visible' : '' ?>" data-health-empty><span class="dw-empty-state__icon"><?= yn_icon('file') ?></span><strong>No clubs found</strong><p>No clubs match the current division and filters.</p></div>
  </section>
</section>

<script type="application/json" id="club-health-data" data-root="<?= $e(ROOT) ?>"><?= $detailsJson ?></script>

<div class="dw-modal" id="health-formula-modal" role="dialog" aria-modal="true" aria-hidden="true" hidden><div class="dw-modal__backdrop" data-modal-close></div><div class="dw-modal__dialog dw-modal__dialog--wide"><header class="dw-modal__header"><h2>Club Health Score Formula</h2><button class="dw-modal__close" type="button" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button></header><div class="dw-modal__body"><section class="dw-evidence-section dw-field--span-2"><div class="dw-section-header"><div><h3>Measurement window</h3><p>The most recent six months, recalculated from database records.</p></div></div></section><section class="dw-evidence-section"><div class="dw-section-header"><div><h3>Events - 40%</h3><p>Completed club events divided by a six-event target, capped at 100.</p></div></div></section><section class="dw-evidence-section"><div class="dw-section-header"><div><h3>Attendance - 30%</h3><p>Present records divided by all recorded attendance for completed club events.</p></div></div></section><section class="dw-evidence-section dw-field--span-2"><div class="dw-section-header"><div><h3>Finances - 30%</h3><p>40% transaction activity, 30% expense receipt coverage, and 30% ledger reconciliation.</p></div></div></section><section class="dw-evidence-section dw-field--span-2"><div class="dw-section-header"><div><h3>Health bands</h3><p>Above 70: Healthy. From 30 through 70: At Risk. Below 30: Dormant. Six consecutive dormant monthly calculations create an automatic administrative review flag.</p></div></div></section></div><footer class="dw-modal__footer"><button class="dw-button dw-button--primary" type="button" data-modal-close>Close</button></footer></div></div>

<div class="dw-modal" id="club-health-details" role="dialog" aria-modal="true" aria-hidden="true" hidden><div class="dw-modal__backdrop" data-modal-close></div><div class="dw-modal__dialog dw-modal__dialog--wide dch-modal"><header class="dw-modal__header dch-modal__header"><div class="dch-modal__title"><div class="dch-modal__avatar" data-detail-avatar></div><div><h2 data-detail-club-name>Club Health Details</h2><div class="dch-modal__badges"><span class="dch-code" data-detail-club-code></span><span class="dw-status" data-detail-status></span></div></div></div><button class="dw-modal__close" type="button" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button></header><div class="dw-modal__body dch-modal__body">
  <section class="dch-score-hero"><div><span>Overall health score</span><strong data-detail-overall>0</strong><small>/100</small></div><p data-detail-window></p></section>
  <div class="dch-overview-grid">
    <aside class="dch-profile-column">
      <section class="dch-content-block"><h3>About</h3><p class="dch-about" data-detail-description></p><dl class="dch-meta-list" data-detail-club-info></dl></section>
      <section class="dch-content-block"><h3>Executive Committee</h3><div class="dch-executives" data-detail-executives></div></section>
    </aside>
    <div class="dch-performance-column">
      <section class="dch-content-block"><h3>Performance Overview</h3><div class="dch-performance-grid" data-detail-performance></div></section>
      <section class="dch-content-block"><div class="dw-section-header"><div><h3>Recent Events and Attendance</h3><p>Events inside the current scoring window</p></div></div><div class="dw-table-wrap"><table class="dw-table"><thead><tr><th>Event</th><th>Date</th><th>Status</th><th>Present</th><th>Attendance</th></tr></thead><tbody data-detail-events></tbody></table></div><p class="dw-muted-copy" data-detail-events-empty>No club events were recorded in this scoring window.</p></section>
    </div>
  </div>
  <section class="dch-section"><div class="dw-section-header"><div><h3>Health Score Detail</h3><p>The records and weights used for the current result</p></div></div><div class="dch-breakdown" data-detail-breakdown></div></section>
  <section class="dch-section"><div class="dw-section-header"><div><h3>Financial Details</h3><p>Ledger activity, documentation, reconciliation, and audit findings</p></div></div><div class="dw-impact-grid" data-detail-finance-summary></div><div class="dw-table-wrap"><table class="dw-table"><thead><tr><th>Date</th><th>Type</th><th>Description</th><th>Amount</th><th>Receipt</th><th>Reconciled</th><th>Status</th></tr></thead><tbody data-detail-finance-entries></tbody></table></div><p class="dw-muted-copy" data-detail-finance-empty>No club ledger entries were recorded in this scoring window.</p><div class="dch-subsection"><h4>Recent audits and financial flags</h4><div data-detail-audits></div></div></section>
  <div class="dch-lower-grid"><section class="dch-section"><div class="dw-section-header"><div><h3>Six-Month Health History</h3><p>Monthly calculations used to detect sustained dormancy</p></div></div><div class="dw-table-wrap"><table class="dw-table"><thead><tr><th>Month</th><th>Events</th><th>Finance</th><th>Attendance</th><th>Overall</th><th>Status</th></tr></thead><tbody data-detail-history></tbody></table></div></section><section class="dch-section"><div class="dw-section-header"><div><h3>Health Concerns</h3><p>Automatic and officer-raised concerns requiring review</p></div></div><div data-detail-flags></div></section></div>
</div><footer class="dw-modal__footer"><button class="dw-button dw-button--secondary" type="button" data-modal-close>Close</button><button class="dw-button dw-button--danger" type="button" data-open-health-flag data-modal-open="club-health-flag"><?= $e($flagLabels[$actorRole] ?? 'Raise Concern') ?></button></footer></div></div>

<div class="dw-modal" id="club-health-flag" role="dialog" aria-modal="true" aria-hidden="true" hidden>
  <div class="dw-modal__backdrop" data-modal-close></div>
  <form class="dw-modal__dialog" action="" method="post" data-health-flag-form data-action-base="<?= ROOT ?>/divisionalclubhealth/flag/">
    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
    <header class="dw-modal__header">
      <h2><?= $e($flagLabels[$actorRole] ?? 'Raise Club Health Concern') ?></h2>
      <button class="dw-modal__close" type="button" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
    </header>
    <div class="dw-modal__body">
      <div class="dw-detail-box dw-field--span-2"><span>Club</span><strong data-flag-club-name></strong></div>
      <?php if (count($flagCategories) > 1): ?>
        <div class="dw-field dw-field--span-2">
          <label for="health-flag-category">Concern type</label>
          <select id="health-flag-category" name="flag_category" required>
            <option value="">Select a concern type</option>
            <?php foreach ($flagCategories as $flagCategory): ?>
              <option value="<?= $e($flagCategory) ?>"><?= $e($flagCategoryLabels[$flagCategory] ?? $flagCategory) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php elseif ($flagCategories): ?>
        <?php $flagCategory = $flagCategories[0]; ?>
        <input type="hidden" name="flag_category" value="<?= $e($flagCategory) ?>">
        <div class="dw-detail-box dw-field--span-2"><span>Concern type</span><strong><?= $e($flagCategoryLabels[$flagCategory] ?? $flagCategory) ?></strong></div>
      <?php endif; ?>
      <div class="dw-field dw-field--span-2">
        <label for="health-flag-reason">Reason for review</label>
        <textarea id="health-flag-reason" name="reason" minlength="10" maxlength="1000" required placeholder="Describe the records or issue that require administrative review"></textarea>
      </div>
      <div class="dw-alert dw-alert--warning dw-field--span-2"><?= yn_icon('info') ?><span>This records a concern and notifies administrators. It does not change the calculated score or disband the club.</span></div>
    </div>
    <footer class="dw-modal__footer">
      <button class="dw-button dw-button--secondary" type="button" data-modal-close>Cancel</button>
      <button class="dw-button dw-button--danger" type="submit">Submit Concern</button>
    </footer>
  </form>
</div>
<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
