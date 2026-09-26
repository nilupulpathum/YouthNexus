<?php
require_once __DIR__ . '/../partials/icons.view.php';
$e = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$date = static fn($value) => ($time = strtotime((string) $value)) ? date('d M Y', $time) : 'Not recorded';
$healthLabel = static fn($status) => $status === 'Green' ? 'Healthy' : ($status === 'Yellow' ? 'At Risk' : 'Dormant');
$healthClass = static fn($status) => strtolower((string) $status);

$title = 'Dashboard - YouthNexus';
$pageTitle = 'Dashboard';
$pageDescription = $division->division_name . ' - approvals, clubs, and health monitoring';
$currentRoute = 'divisionalcoordinator';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css', ROOT . '/assets/css/divisional-dashboard.css'];
$pageScripts = [ROOT . '/assets/js/divisional-workflows.js'];
$summaryCards = [
    ['value' => $summary['clubs'], 'label' => 'Active Clubs', 'note' => 'Clubs in this division', 'icon' => 'users', 'tone' => 'blue'],
    ['value' => $summary['pending_applications'], 'label' => 'Pending Registrations', 'note' => 'Applications awaiting review', 'icon' => 'clipboard', 'tone' => 'amber'],
    ['value' => $summary['pending_events'], 'label' => 'Pending Event Approvals', 'note' => 'Events awaiting decision', 'icon' => 'calendar', 'tone' => 'amber'],
    ['value' => $summary['attention_clubs'], 'label' => 'Clubs Requiring Attention', 'note' => 'Health risk or open concern', 'icon' => 'info', 'tone' => 'red'],
];
require __DIR__ . '/../layouts/dashboard-start.view.php';
?>
<section class="dw-page dtd-page" aria-label="Divisional Coordinator dashboard">
  <div class="dw-summary-grid" aria-label="Division overview">
    <?php foreach ($summaryCards as $card): ?><?php require __DIR__ . '/../partials/divisional/summary-card.view.php'; ?><?php endforeach; ?>
  </div>

  <div class="dtd-grid">
    <section class="dw-panel dtd-panel" aria-labelledby="coordinator-applications-title">
      <header class="dw-panel__header"><div><h2 id="coordinator-applications-title">Pending Club Registrations</h2><p>Applications awaiting your decision</p></div><a class="yn-btn yn-btn--ghost dw-button dw-button--ghost db-view-button" href="<?= ROOT ?>/clubregistrationapproval/index">Review All</a></header>
      <?php if ($applications): ?>
        <div class="yn-table-wrap dw-table-wrap"><table class="yn-table dw-table"><thead><tr><th>Club</th><th>Proposer</th><th>Category</th><th>Members</th><th>Submitted</th></tr></thead><tbody>
          <?php foreach ($applications as $application): ?><tr><td class="dw-table__reference"><?= $e($application->club_name) ?></td><td><?= $e($application->proposer_name) ?></td><td><?= $e($application->category ?: 'Not specified') ?></td><td><?= (int) $application->no_of_members ?></td><td><?= $e($date($application->submitted_at)) ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
      <?php else: ?><?php $emptyTitle = 'No pending registrations'; $emptyMessage = 'New club applications will appear here.'; $emptyVisible = true; $emptyIcon = 'check'; require __DIR__ . '/../partials/empty-state.view.php'; ?><?php endif; ?>
    </section>

    <section class="dw-panel dtd-panel" aria-labelledby="coordinator-events-title">
      <header class="dw-panel__header"><div><h2 id="coordinator-events-title">Pending Event Approvals</h2><p>Division and club events requiring a decision</p></div><a class="yn-btn yn-btn--ghost dw-button dw-button--ghost db-view-button" href="<?= ROOT ?>/eventapproval">Review All</a></header>
      <?php if ($events): ?><div class="dtd-list">
        <?php foreach ($events as $event): ?><article class="dtd-list__item"><div><strong><?= $e($event->title) ?></strong><span><?= $e($event->organiser ?: 'Division') ?> - <?= $e($event->event_type ?: 'General') ?></span></div><div class="dtd-list__value"><strong><?= $e($date($event->start_datetime)) ?></strong><span>EVT-<?= str_pad((string) $event->event_id, 4, '0', STR_PAD_LEFT) ?></span></div><?php $status = 'Pending'; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?></article><?php endforeach; ?>
      </div><?php else: ?><?php $emptyTitle = 'No pending event approvals'; $emptyMessage = 'Submitted events will appear here.'; $emptyVisible = true; $emptyIcon = 'check'; require __DIR__ . '/../partials/empty-state.view.php'; ?><?php endif; ?>
    </section>

    <section class="dw-panel dtd-panel" aria-labelledby="coordinator-health-title">
      <header class="dw-panel__header"><div><h2 id="coordinator-health-title">Club Health Overview</h2><p>Lowest current health scores are shown first</p></div><a class="yn-btn yn-btn--ghost dw-button dw-button--ghost db-view-button" href="<?= ROOT ?>/divisionalclubhealth">View Details</a></header>
      <?php if ($clubHealth): ?><div class="dtd-health-list">
        <?php foreach ($clubHealth as $club): $score = max(0, min(100, (float) $club->overall_health_score)); ?><article class="dtd-health"><div class="dtd-health__score dtd-health__score--<?= $e($healthClass($club->health_status)) ?>"><strong><?= $e(number_format($score, 0)) ?></strong><span>/100</span></div><div class="dtd-health__body"><div><strong><?= $e($club->club_name) ?></strong><?php if ((int) $club->open_flags > 0): ?><span><?= (int) $club->open_flags ?> open <?= (int) $club->open_flags === 1 ? 'concern' : 'concerns' ?></span><?php endif; ?></div><div class="dtd-health__bar"><span class="dtd-health__fill--<?= $e($healthClass($club->health_status)) ?>" style="width: <?= $e(number_format($score, 2, '.', '')) ?>%"></span></div></div><span class="dtd-health__label dtd-health__label--<?= $e($healthClass($club->health_status)) ?>"><?= $e($healthLabel($club->health_status)) ?></span></article><?php endforeach; ?>
      </div><?php else: ?><?php $emptyTitle = 'No club health results'; $emptyMessage = 'Run Club Health to calculate current scores.'; $emptyVisible = true; $emptyIcon = 'info'; require __DIR__ . '/../partials/empty-state.view.php'; ?><?php endif; ?>
    </section>

    <section class="dw-panel dtd-panel" aria-labelledby="coordinator-actions-title">
      <header class="dw-panel__header"><div><h2 id="coordinator-actions-title">Quick Actions</h2><p>Common divisional coordination workflows</p></div></header>
      <div class="dtd-list"><a class="dtd-list__item" href="<?= ROOT ?>/clubregistrationapproval/index"><div><strong>Review club registrations</strong><span>Approve or reject submitted applications</span></div><?= yn_icon('clipboard') ?></a><a class="dtd-list__item" href="<?= ROOT ?>/eventapproval"><div><strong>Review events</strong><span>Process pending event approvals</span></div><?= yn_icon('calendar') ?></a><a class="dtd-list__item" href="<?= ROOT ?>/divisionalreports"><div><strong>Manage reports</strong><span>Generate operational reports</span></div><?= yn_icon('reports') ?></a></div>
    </section>
  </div>
</section>
<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
