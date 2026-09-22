<?php
require_once __DIR__ . '/../partials/icons.view.php';

$e = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$title = 'Audit Club Finance - YouthNexus';
$pageTitle = 'Audit Club Finance';
$pageDescription = 'Review club ledgers, receipts, and financial audit status';
$currentRoute = 'divisionalaudits';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css'];
$pageScripts = [ROOT . '/assets/js/divisional-workflows.js', ROOT . '/assets/js/divisional-audits.js'];
$summaryCards = [
    ['value' => (string) $summary['pending'], 'label' => 'Pending Audits', 'note' => 'Require review', 'icon' => 'clock', 'tone' => 'amber'],
    ['value' => (string) $summary['completed'], 'label' => 'Completed This Year', 'note' => 'Signed off', 'icon' => 'check', 'tone' => 'green'],
    ['value' => (string) $summary['flagged'], 'label' => 'Flagged Audits', 'note' => 'Open findings', 'icon' => 'info', 'tone' => 'red'],
];

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>
<section class="dw-page" aria-label="Club finance audit queue">
  <?php if ($flash): ?>
    <div class="dw-alert dw-alert--<?= $flash['type'] === 'success' ? 'success' : 'error' ?>" role="status">
      <?= yn_icon($flash['type'] === 'success' ? 'check' : 'info') ?><span><?= $e($flash['message']) ?></span>
    </div>
  <?php endif; ?>

  <div class="dw-summary-grid dw-summary-grid--three" aria-label="Audit summary">
    <?php foreach ($summaryCards as $card): ?><?php require __DIR__ . '/../partials/divisional/summary-card.view.php'; ?><?php endforeach; ?>
  </div>

  <div class="dw-toolbar" aria-label="Audit tools">
    <div class="dw-toolbar__search dw-search dw-search--plain">
      <label class="visually-hidden" for="audit-search">Search clubs</label>
      <input id="audit-search" type="search" placeholder="Search clubs" data-audit-search>
    </div>
    <button class="dw-button dw-button--secondary" type="button" data-filter-toggle aria-controls="audit-filters" aria-expanded="false">Filters</button>
    <button class="dw-button dw-button--primary" type="button" data-modal-open="start-audit"<?= $clubs ? '' : ' disabled' ?>>Start Audit</button>
  </div>

  <section class="dw-filter-panel" id="audit-filters" hidden>
    <h2 class="dw-filter-panel__heading">Advanced Filters for Club Audits</h2>
    <div class="dw-filter-grid">
      <div class="dw-field"><label for="audit-type-filter">Audit Type</label><select id="audit-type-filter" data-audit-type-filter><option value="">All types</option><option value="weekly">Weekly</option><option value="biweekly">Bi-weekly</option><option value="monthly">Monthly</option><option value="notassigned">Not assigned</option></select></div>
      <div class="dw-field"><label for="audit-status-filter">Audit Status</label><select id="audit-status-filter" data-audit-status-filter><option value="">All statuses</option><option value="notstarted">Not started</option><option value="pending">Pending</option><option value="inprogress">In progress</option><option value="clarificationrequested">Clarification requested</option><option value="completed">Completed</option><option value="overdue">Overdue</option></select></div>
      <div class="dw-field"><label for="audit-flag-filter">Findings</label><select id="audit-flag-filter" data-audit-flag-filter><option value="">Any</option><option value="flagged">Open findings</option><option value="clear">No open findings</option></select></div>
      <div class="dw-field"><label for="audit-sort">Sort By</label><select id="audit-sort" data-audit-sort><option value="club">Club name</option><option value="recent">Most recently audited</option><option value="oldest">Oldest audit first</option></select></div>
    </div>
    <div class="dw-filter-actions"><button class="dw-button dw-button--secondary" type="button" data-audit-filter-reset>Reset all</button><button class="dw-button dw-button--primary" type="button" data-audit-filter-apply>Apply Filters</button></div>
  </section>

  <div class="dw-section-header"><div><h2>Audit Queue</h2><p>Clubs within <?= $e($division->division_name) ?></p></div><span class="dw-count" data-audit-count><?= count($queue) ?> clubs</span></div>

  <div class="dw-audit-grid" data-audit-grid>
    <?php foreach ($queue as $item): ?>
      <?php
      $statusText = $item->audit_id ? $item->audit_status : 'Not Started';
      $typeText = $item->audit_id && $item->audit_type
          ? ($item->audit_type === 'BiWeekly' ? 'Bi-weekly' : $item->audit_type)
          : 'Not assigned';
      $lastDate = $item->signed_off_at ?: $item->initiated_at;
      ?>
      <article class="dw-audit-card<?= (int) $item->open_flags > 0 ? ' dw-audit-card--flagged' : '' ?>"
               data-audit-card
               data-search="<?= $e(strtolower($item->club_name)) ?>"
               data-type="<?= $e(strtolower($item->audit_type ?: 'notassigned')) ?>"
               data-status="<?= $e(strtolower(str_replace(' ', '', $statusText))) ?>"
               data-flags="<?= (int) $item->open_flags > 0 ? 'flagged' : 'clear' ?>"
               data-date="<?= $lastDate ? $e(date('Y-m-d', strtotime($lastDate))) : '' ?>">
        <header class="dw-audit-card__header"><div><span class="dw-audit-card__cycle"><?= $e($typeText) ?></span><h3><?= $e($item->club_name) ?></h3></div><?php $status = $statusText; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?></header>
        <div class="dw-audit-card__details">
          <span><?= $lastDate ? 'Last activity ' . $e(date('d M Y', strtotime($lastDate))) : 'No audit recorded' ?></span>
          <span><?= (int) $item->open_flags ?> open <?= (int) $item->open_flags === 1 ? 'finding' : 'findings' ?></span>
        </div>
        <footer class="dw-audit-card__footer">
          <?php if ($item->audit_id): ?>
            <a class="dw-button dw-button--ghost" href="<?= ROOT ?>/divisionalaudits/review/<?= (int) $item->audit_id ?>"><?= $item->audit_status === 'Completed' ? 'View Log' : 'Review' ?></a>
          <?php elseif ((int) $item->has_active_ledger === 1): ?>
            <button class="dw-button dw-button--ghost" type="button" data-start-club="<?= (int) $item->club_id ?>" data-modal-open="start-audit">Start Audit</button>
          <?php else: ?>
            <button class="dw-button dw-button--ghost" type="button" disabled>Ledger Unavailable</button>
          <?php endif; ?>
        </footer>
      </article>
    <?php endforeach; ?>
  </div>
  <?php $emptyTitle = 'No clubs found'; $emptyMessage = 'Change the current search and filters.'; $emptyVisible = count($queue) === 0; require __DIR__ . '/../partials/divisional/empty-state.view.php'; ?>
</section>

<div class="dw-modal" id="start-audit" role="dialog" aria-modal="true" aria-labelledby="start-audit-title" aria-hidden="true" hidden>
  <div class="dw-modal__backdrop" data-modal-close></div>
  <form class="dw-modal__dialog" action="<?= ROOT ?>/divisionalaudits/start" method="post" data-start-audit-form>
    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
    <header class="dw-modal__header"><h2 id="start-audit-title">Start Club Audit</h2><button class="dw-modal__close" type="button" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button></header>
    <div class="dw-modal__body">
      <div class="dw-field dw-field--span-2"><label for="audit-club">Club</label><select id="audit-club" name="club_id" required><option value="">Select a club</option><?php foreach ($clubs as $club): ?><option value="<?= (int) $club->club_id ?>"><?= $e($club->club_name) ?></option><?php endforeach; ?></select></div>
      <div class="dw-field"><label for="audit-type">Audit Type</label><select id="audit-type" name="audit_type" required><option value="Weekly">Weekly</option><option value="BiWeekly">Bi-weekly</option><option value="Monthly" selected>Monthly</option></select></div>
      <div class="dw-field"><label for="audit-period-end">Period End</label><input id="audit-period-end" name="period_end" type="date" value="<?= date('Y-m-d') ?>" required></div>
      <div class="dw-field dw-field--span-2"><label for="audit-period-start">Period Start</label><input id="audit-period-start" name="period_start" type="date" value="<?= date('Y-m-01') ?>" required></div>
    </div>
    <footer class="dw-modal__footer"><button class="dw-button dw-button--secondary" type="button" data-modal-close>Cancel</button><button class="dw-button dw-button--primary" type="submit">Start Audit</button></footer>
  </form>
</div>
<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
