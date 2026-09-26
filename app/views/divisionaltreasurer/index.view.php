<?php
require_once __DIR__ . '/../partials/icons.view.php';
$e = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$money = static fn($value) => 'Rs. ' . number_format((float) $value, 2);
$date = static function ($value): string {
    $timestamp = strtotime((string) $value);
    return $timestamp ? date('d M Y', $timestamp) : 'Not recorded';
};
$healthLabel = static fn($status) => $status === 'Green' ? 'Healthy' : ($status === 'Yellow' ? 'At Risk' : 'Dormant');
$healthClass = static fn($status) => strtolower((string) $status);
$auditLabel = static function ($audit): string {
    if (!$audit->audit_id) return 'Audit not started';
    if ((int) $audit->open_flags > 0) return (int) $audit->open_flags . ((int) $audit->open_flags === 1 ? ' open finding' : ' open findings');
    if ($audit->audit_status === 'Overdue') return 'Audit overdue';
    if ($audit->audit_status === 'ClarificationRequested') return 'Clarification required';
    if ($audit->audit_status === 'InProgress') return 'Audit in progress';
    return 'Audit pending';
};
$auditStatus = static function ($audit): string {
    if (!$audit->audit_id) return 'Pending';
    if ((int) $audit->open_flags > 0) return 'Open';
    return (string) $audit->audit_status;
};

$title = 'Dashboard - YouthNexus';
$pageTitle = 'Dashboard';
$pageDescription = $division->division_name . ' - finance, club health, and pending work';
$currentRoute = 'divisionaltreasurer';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css', ROOT . '/assets/css/divisional-dashboard.css'];
$pageScripts = [ROOT . '/assets/js/divisional-workflows.js'];
$summaryCards = [
    ['value' => $money($summary['balance']), 'label' => 'Divisional Balance', 'note' => 'Current ledger balance', 'icon' => 'file', 'tone' => 'blue'],
    ['value' => $summary['pending_voids'], 'label' => 'Pending Void Approvals', 'note' => 'Club requests awaiting review', 'icon' => 'clock', 'tone' => 'amber'],
    ['value' => $summary['pending_funds'], 'label' => 'Pending Fund Requests', 'note' => 'Club requests awaiting decision', 'icon' => 'file', 'tone' => 'amber'],
    ['value' => $summary['attention_clubs'], 'label' => 'Clubs Requiring Attention', 'note' => 'Health risk or open concern', 'icon' => 'info', 'tone' => 'red'],
];
require __DIR__ . '/../layouts/dashboard-start.view.php';
?>
<section class="dw-page dtd-page" aria-label="Divisional Treasurer dashboard" data-finance-refresh-on-change>
  <div class="dw-summary-grid" aria-label="Division finance summary">
    <?php foreach ($summaryCards as $card): ?><?php require __DIR__ . '/../partials/divisional/summary-card.view.php'; ?><?php endforeach; ?>
  </div>

  <div class="dtd-grid">
    <section class="dw-panel dtd-panel" aria-labelledby="dashboard-void-title">
      <header class="dw-panel__header">
        <div><h2 id="dashboard-void-title">Pending Void Requests</h2><p>Club ledger entries requiring a decision</p></div>
        <a class="yn-btn yn-btn--ghost dw-button dw-button--ghost db-view-button" href="<?= ROOT ?>/divisionalvoidapproval">Review All</a>
      </header>
      <?php if ($pendingVoidRequests): ?>
        <div class="dw-table-wrap">
          <table class="yn-table dw-table">
            <thead><tr><th>Request</th><th>Club</th><th>Entry</th><th>Reason</th><th>Requested</th></tr></thead>
            <tbody>
              <?php foreach ($pendingVoidRequests as $request): ?>
                <tr>
                  <td class="dw-table__reference">VR-<?= str_pad((string) $request->void_request_id, 4, '0', STR_PAD_LEFT) ?></td>
                  <td><?= $e($request->club_name) ?></td>
                  <td><strong><?= $e($request->reference_no) ?></strong><br><span class="dtd-muted"><?= $e($money($request->amount)) ?> - <?= $e($request->type) ?></span></td>
                  <td class="dw-table__description"><?= $e($request->reason) ?></td>
                  <td><?= $e($date($request->requested_at)) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="dtd-empty"><span><?= yn_icon('check') ?></span><strong>No pending void requests</strong><p>New requests from clubs will appear here.</p></div>
      <?php endif; ?>
    </section>

    <section class="dw-panel dtd-panel" aria-labelledby="dashboard-allocation-title">
      <header class="dw-panel__header">
        <div><h2 id="dashboard-allocation-title">Recent Allocations</h2><p>Latest fund activity for clubs in this division</p></div>
        <a class="yn-btn yn-btn--ghost dw-button dw-button--ghost db-view-button" href="<?= ROOT ?>/divisionalallocations">View All</a>
      </header>
      <?php if ($recentAllocations): ?>
        <div class="dtd-list">
          <?php foreach ($recentAllocations as $allocation): ?>
            <article class="dtd-list__item">
              <div><strong><?= $e($allocation->club_name) ?></strong><span><?= $e($allocation->reference_no) ?> - <?= $e($allocation->fund_category ?: 'Uncategorised') ?></span></div>
              <div class="dtd-list__value"><strong><?= $e($money($allocation->amount)) ?></strong><span><?= $e($date($allocation->transfer_date)) ?></span></div>
              <?php $status = $allocation->status; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="dtd-empty"><span><?= yn_icon('file') ?></span><strong>No fund allocations</strong><p>Completed and pending club allocations will appear here.</p></div>
      <?php endif; ?>
    </section>

    <section class="dw-panel dtd-panel" aria-labelledby="dashboard-health-title">
      <header class="dw-panel__header">
        <div><h2 id="dashboard-health-title">Club Health Overview</h2><p>Lowest current health scores are shown first</p></div>
        <a class="yn-btn yn-btn--ghost dw-button dw-button--ghost db-view-button" href="<?= ROOT ?>/divisionalclubhealth">View Details</a>
      </header>
      <?php if ($clubHealth): ?>
        <div class="dtd-health-list">
          <?php foreach ($clubHealth as $club): ?>
            <?php $score = max(0, min(100, (float) $club->overall_health_score)); ?>
            <article class="dtd-health">
              <div class="dtd-health__score dtd-health__score--<?= $e($healthClass($club->health_status)) ?>"><strong><?= $e(number_format($score, 0)) ?></strong><span>/100</span></div>
              <div class="dtd-health__body">
                <div><strong><?= $e($club->club_name) ?></strong><?php if ((int) $club->open_flags > 0): ?><span><?= (int) $club->open_flags ?> open <?= (int) $club->open_flags === 1 ? 'concern' : 'concerns' ?></span><?php endif; ?></div>
                <div class="dtd-health__bar"><span class="dtd-health__fill--<?= $e($healthClass($club->health_status)) ?>" style="width: <?= $e(number_format($score, 2, '.', '')) ?>%"></span></div>
              </div>
              <span class="dtd-health__label dtd-health__label--<?= $e($healthClass($club->health_status)) ?>"><?= $e($healthLabel($club->health_status)) ?></span>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="dtd-empty"><span><?= yn_icon('info') ?></span><strong>No club health results</strong><p>Run the Club Health workflow to calculate current scores.</p></div>
      <?php endif; ?>
    </section>

    <section class="dw-panel dtd-panel" aria-labelledby="dashboard-audit-title">
      <header class="dw-panel__header">
        <div><h2 id="dashboard-audit-title">Audit Reminders</h2><p>Clubs with audits or findings that need attention</p></div>
        <a class="yn-btn yn-btn--ghost dw-button dw-button--ghost db-view-button" href="<?= ROOT ?>/divisionalaudits">Open Audits</a>
      </header>
      <?php if ($auditReminders): ?>
        <div class="dtd-reminders">
          <?php foreach ($auditReminders as $audit): ?>
            <article class="dtd-reminder">
              <span class="dtd-reminder__icon" aria-hidden="true"><?= yn_icon((int) $audit->open_flags > 0 || $audit->audit_status === 'Overdue' ? 'info' : 'clock') ?></span>
              <div><strong><?= $e($audit->club_name) ?></strong><span><?= $e($auditLabel($audit)) ?><?php if ($audit->period_end): ?> - period ended <?= $e($date($audit->period_end)) ?><?php endif; ?></span></div>
              <?php $status = $auditStatus($audit); require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="dtd-empty"><span><?= yn_icon('check') ?></span><strong>No audit reminders</strong><p>There are no pending audits or open findings.</p></div>
      <?php endif; ?>
    </section>
  </div>
</section>
<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
