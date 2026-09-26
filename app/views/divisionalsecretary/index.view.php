<?php
require_once __DIR__ . '/../partials/icons.view.php';
$e = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$date = static fn($value) => ($time = strtotime((string) $value)) ? date('d M Y, H:i', $time) : 'Not recorded';
$healthLabel = static fn($status) => $status === 'Green' ? 'Healthy' : ($status === 'Yellow' ? 'At Risk' : 'Dormant');
$healthClass = static fn($status) => strtolower((string) $status);

$title = 'Dashboard - YouthNexus';
$pageTitle = 'Dashboard';
$pageDescription = $division->division_name . ' - events, attendance, and club activity';
$currentRoute = 'divisionalsecretary';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css', ROOT . '/assets/css/divisional-dashboard.css'];
$pageScripts = [ROOT . '/assets/js/divisional-workflows.js'];
$summaryCards = [
    ['value' => $summary['upcoming_events'], 'label' => 'Upcoming Events', 'note' => 'Approved future events', 'icon' => 'calendar', 'tone' => 'blue'],
    ['value' => $summary['attendance_pending'], 'label' => 'Attendance Pending', 'note' => 'Completed events without records', 'icon' => 'clock', 'tone' => 'amber'],
    ['value' => number_format((float) $summary['attendance_rate'], 1) . '%', 'label' => 'Attendance Rate', 'note' => 'Recorded attendance this year', 'icon' => 'check', 'tone' => 'green'],
    ['value' => $summary['clubs'], 'label' => 'Active Clubs', 'note' => 'Clubs in this division', 'icon' => 'users', 'tone' => 'blue'],
];
require __DIR__ . '/../layouts/dashboard-start.view.php';
?>
<section class="dw-page dtd-page" aria-label="Divisional Secretary dashboard">
  <div class="dw-summary-grid" aria-label="Division activity summary"><?php foreach ($summaryCards as $card): ?><?php require __DIR__ . '/../partials/divisional/summary-card.view.php'; ?><?php endforeach; ?></div>
  <div class="dtd-grid">
    <section class="dw-panel dtd-panel" aria-labelledby="secretary-events-title">
      <header class="dw-panel__header"><div><h2 id="secretary-events-title">Upcoming Events</h2><p>Next approved events in this division</p></div><a class="yn-btn yn-btn--ghost dw-button dw-button--ghost db-view-button" href="<?= ROOT ?>/manageevents">Manage Events</a></header>
      <?php if ($events): ?><div class="dtd-list"><?php foreach ($events as $event): ?><article class="dtd-list__item"><div><strong><?= $e($event->title) ?></strong><span><?= $e($event->organiser ?: 'Division') ?> - <?= $e($event->location ?: 'Location not set') ?></span></div><div class="dtd-list__value"><strong><?= $e($date($event->start_datetime)) ?></strong><span><?= $e($event->event_type ?: 'General') ?></span></div><?php $status = 'Approved'; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?></article><?php endforeach; ?></div><?php else: ?><div class="dtd-empty"><span><?= yn_icon('calendar') ?></span><strong>No upcoming events</strong><p>Approved future events will appear here.</p></div><?php endif; ?>
    </section>

    <section class="dw-panel dtd-panel" aria-labelledby="secretary-attendance-title">
      <header class="dw-panel__header"><div><h2 id="secretary-attendance-title">Attendance Follow-up</h2><p>Recently completed events and recording progress</p></div><a class="yn-btn yn-btn--ghost dw-button dw-button--ghost db-view-button" href="<?= ROOT ?>/attendance">Manage Attendance</a></header>
      <?php if ($attendanceFollowUps): ?><div class="dtd-list"><?php foreach ($attendanceFollowUps as $event): $recorded = (int) $event->recorded; ?><article class="dtd-list__item"><div><strong><?= $e($event->title) ?></strong><span><?= $e($event->organiser ?: 'Division') ?> - ended <?= $e($date($event->end_datetime)) ?></span></div><div class="dtd-list__value"><strong><?= $recorded ?> recorded</strong><span><?= (int) $event->present ?> present</span></div><?php $status = $recorded > 0 ? 'Recorded' : 'Pending'; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?></article><?php endforeach; ?></div><?php else: ?><div class="dtd-empty"><span><?= yn_icon('check') ?></span><strong>No attendance follow-up required</strong><p>Completed events will appear here.</p></div><?php endif; ?>
    </section>

    <section class="dw-panel dtd-panel" aria-labelledby="secretary-health-title">
      <header class="dw-panel__header"><div><h2 id="secretary-health-title">Club Health Overview</h2><p>Lowest current health scores are shown first</p></div><a class="yn-btn yn-btn--ghost dw-button dw-button--ghost db-view-button" href="<?= ROOT ?>/divisionalclubhealth">View Details</a></header>
      <?php if ($clubHealth): ?><div class="dtd-health-list"><?php foreach ($clubHealth as $club): $score = max(0, min(100, (float) $club->overall_health_score)); ?><article class="dtd-health"><div class="dtd-health__score dtd-health__score--<?= $e($healthClass($club->health_status)) ?>"><strong><?= $e(number_format($score, 0)) ?></strong><span>/100</span></div><div class="dtd-health__body"><div><strong><?= $e($club->club_name) ?></strong><?php if ((int) $club->open_flags > 0): ?><span><?= (int) $club->open_flags ?> open <?= (int) $club->open_flags === 1 ? 'concern' : 'concerns' ?></span><?php endif; ?></div><div class="dtd-health__bar"><span class="dtd-health__fill--<?= $e($healthClass($club->health_status)) ?>" style="width: <?= $e(number_format($score, 2, '.', '')) ?>%"></span></div></div><span class="dtd-health__label dtd-health__label--<?= $e($healthClass($club->health_status)) ?>"><?= $e($healthLabel($club->health_status)) ?></span></article><?php endforeach; ?></div><?php else: ?><div class="dtd-empty"><span><?= yn_icon('info') ?></span><strong>No club health results</strong><p>Run Club Health to calculate current scores.</p></div><?php endif; ?>
    </section>

    <section class="dw-panel dtd-panel" aria-labelledby="secretary-actions-title">
      <header class="dw-panel__header"><div><h2 id="secretary-actions-title">Quick Actions</h2><p>Common divisional secretary workflows</p></div></header>
      <div class="dtd-list"><a class="dtd-list__item" href="<?= ROOT ?>/manageevents"><div><strong>Manage events</strong><span>Create and maintain divisional events</span></div><?= yn_icon('calendar') ?></a><a class="dtd-list__item" href="<?= ROOT ?>/attendance"><div><strong>Record attendance</strong><span>Update participant attendance</span></div><?= yn_icon('check') ?></a><a class="dtd-list__item" href="<?= ROOT ?>/divisionalreports"><div><strong>Manage reports</strong><span>Generate event and attendance reports</span></div><?= yn_icon('reports') ?></a></div>
    </section>
  </div>
</section>
<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
