<?php
require_once __DIR__ . '/../partials/icons.view.php';

$e = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$money = static fn($value) => 'Rs. ' . number_format((float) $value, 2);
$title = 'Manage Ledger — YouthNexus';
$pageTitle = 'Manage Ledger';
$pageDescription = 'Review, reconcile, and manage all divisional financial entries';
$currentRoute = 'divisionalledger';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css'];
$pageScripts = [
    ROOT . '/assets/js/divisional-workflows.js',
    ROOT . '/assets/js/divisional-ledger.js',
];

$summaryCards = [
    ['value' => $money($summary['balance']), 'label' => 'Current Ledger Balance', 'note' => $division->division_name, 'icon' => 'file', 'tone' => 'blue'],
    ['value' => $money($summary['income']), 'label' => 'Income — Current Year', 'note' => 'Approved entries', 'icon' => 'download', 'tone' => 'green'],
    ['value' => $money($summary['expenses']), 'label' => 'Expenses — Current Year', 'note' => 'Approved entries', 'icon' => 'upload', 'tone' => 'red'],
    ['value' => (string) $summary['review_count'], 'label' => 'Entries Requiring Review', 'note' => 'Pending or missing receipts', 'icon' => 'info', 'tone' => 'amber'],
];

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>
<section class="dw-page" aria-label="Division general ledger">
  <?php if ($flash): ?>
    <div class="dw-alert dw-alert--<?= $flash['type'] === 'success' ? 'success' : 'error' ?>" role="status">
      <?= yn_icon($flash['type'] === 'success' ? 'check' : 'info') ?>
      <span><?= $e($flash['message']) ?></span>
    </div>
  <?php endif; ?>

  <div class="dw-summary-grid" aria-label="Ledger summary">
    <?php foreach ($summaryCards as $card): ?>
      <?php require __DIR__ . '/../partials/divisional/summary-card.view.php'; ?>
    <?php endforeach; ?>
  </div>

  <div class="dw-toolbar" aria-label="Ledger tools">
    <div class="dw-toolbar__search dw-search dw-search--plain">
      <label class="visually-hidden" for="ledger-search">Search ledger entries</label>
      <input id="ledger-search" type="search" placeholder="Search by reference, description, or category" data-ledger-search>
    </div>
    <label class="visually-hidden" for="ledger-type">Entry type</label>
    <select id="ledger-type" class="dw-select" data-ledger-quick-type>
      <option value="">All entry types</option>
      <option value="income">Income</option>
      <option value="expense">Expense</option>
    </select>
    <button class="dw-button dw-button--secondary" type="button" data-filter-toggle aria-controls="ledger-filters" aria-expanded="false">Filters</button>
    <a class="dw-button dw-button--secondary" href="<?= ROOT ?>/divisionalledger/export">
      <?= yn_icon('download') ?> Export
    </a>
    <button class="dw-button dw-button--primary" type="button" data-modal-open="add-ledger-entry">Add Entry</button>
  </div>

  <section class="dw-filter-panel" id="ledger-filters" hidden>
    <h2 class="dw-filter-panel__heading">Advanced Filters — Ledger Entries</h2>
    <div class="dw-filter-grid">
      <div class="dw-field">
        <label for="filter-status">Entry status</label>
        <select id="filter-status" data-filter-status>
          <option value="">All statuses</option>
          <option value="approved">Approved</option>
          <option value="pending">Pending</option>
          <option value="rejected">Rejected</option>
          <option value="voided">Voided</option>
        </select>
      </div>
      <div class="dw-field">
        <label for="filter-reconciliation">Reconciliation</label>
        <select id="filter-reconciliation" data-filter-reconciliation>
          <option value="">Any status</option>
          <option value="yes">Reconciled</option>
          <option value="no">Unreconciled</option>
        </select>
      </div>
      <div class="dw-field">
        <label for="filter-receipt">Receipt status</label>
        <select id="filter-receipt" data-filter-receipt>
          <option value="">Any receipt status</option>
          <option value="attached">Attached</option>
          <option value="missing">Missing</option>
        </select>
      </div>
      <div class="dw-field">
        <label for="filter-date-from">Date from</label>
        <input id="filter-date-from" type="date" data-filter-date-from>
      </div>
      <div class="dw-field">
        <label for="filter-date-to">Date to</label>
        <input id="filter-date-to" type="date" data-filter-date-to>
      </div>
    </div>
    <div class="dw-filter-actions">
      <button class="dw-button dw-button--secondary" type="button" data-filter-reset>Reset all</button>
      <button class="dw-button dw-button--primary" type="button" data-filter-apply>Apply filters</button>
    </div>
  </section>

  <div class="dw-content-grid">
    <section class="dw-panel">
      <header class="dw-panel__header">
        <div>
          <h2>Divisional General Ledger</h2>
          <p><?= $e($division->division_name) ?> financial entries</p>
        </div>
        <span class="dw-count" data-entry-count><?= count($entries) ?> <?= count($entries) === 1 ? 'entry' : 'entries' ?></span>
      </header>
      <div class="dw-table-wrap">
        <table class="dw-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Reference</th>
              <th>Description</th>
              <th>Category</th>
              <th>Type</th>
              <th>Debit</th>
              <th>Credit</th>
              <th>Balance</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody data-ledger-rows>
            <?php foreach ($entries as $entry): ?>
              <?php
              $hasReceipt = !empty($entry->attachment_url);
              $isReconciled = (int) $entry->reconciled === 1;
              $searchValue = strtolower(implode(' ', [$entry->reference_no, $entry->description, $entry->category, $entry->type]));
              $displayStatus = $entry->status === 'Approved' && $entry->type === 'Expense' && !$hasReceipt
                  ? 'Missing Receipt'
                  : $entry->status;
              ?>
              <tr data-ledger-row
                  data-search="<?= $e($searchValue) ?>"
                  data-type="<?= $e(strtolower($entry->type)) ?>"
                  data-status="<?= $e(strtolower($entry->status)) ?>"
                  data-reconciled="<?= $isReconciled ? 'yes' : 'no' ?>"
                  data-receipt="<?= $hasReceipt ? 'attached' : 'missing' ?>"
                  data-date="<?= $e($entry->date) ?>">
                <td><?= $e(date('d M Y', strtotime($entry->date))) ?></td>
                <td class="dw-table__reference"><?= $e($entry->reference_no) ?></td>
                <td class="dw-table__description"><?= $e($entry->description) ?></td>
                <td><?= $e($entry->category ?: 'Uncategorised') ?></td>
                <td><?php $status = $entry->type; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?></td>
                <td class="dw-money dw-money--expense"><?= $entry->type === 'Expense' ? $e($money($entry->amount)) : '—' ?></td>
                <td class="dw-money dw-money--income"><?= $entry->type === 'Income' ? $e($money($entry->amount)) : '—' ?></td>
                <td class="dw-money"><?= $e($money($entry->running_balance)) ?></td>
                <td><?php $status = $displayStatus; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?></td>
                <td>
                  <div class="dw-row-actions">
                    <?php if ($hasReceipt): ?>
                      <a class="dw-button dw-button--ghost" href="<?= $e(ROOT . $entry->attachment_url) ?>" target="_blank" rel="noopener" aria-label="View receipt for <?= $e($entry->reference_no) ?>"><?= yn_icon('eye') ?></a>
                    <?php endif; ?>
                    <?php if ($entry->status === 'Approved'): ?>
                      <button class="dw-button dw-button--ghost" type="button"
                              data-reconcile-entry
                              data-reconciled="<?= $isReconciled ? '1' : '0' ?>"
                              data-csrf="<?= $e($csrfToken) ?>"
                              data-endpoint="<?= ROOT ?>/divisionalledger/reconcile/<?= (int) $entry->entry_id ?>">
                        <?= $isReconciled ? 'Undo' : 'Reconcile' ?>
                      </button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php
      $emptyTitle = 'No ledger entries found';
      $emptyMessage = 'Add an entry or change the current search and filters.';
      $emptyVisible = count($entries) === 0;
      require __DIR__ . '/../partials/divisional/empty-state.view.php';
      ?>
    </section>

    <aside class="dw-side-stack" aria-label="Ledger status">
      <section class="dw-panel">
        <header class="dw-panel__header">
          <div><h3>Reconciliation Status</h3><p>Current ledger progress</p></div>
        </header>
        <div class="dw-panel__body">
          <strong class="dw-reconciliation-score"><?= (int) $reconciliation['percent'] ?>%</strong>
          <div class="dw-progress" aria-label="<?= (int) $reconciliation['percent'] ?> percent reconciled">
            <span style="width: <?= (int) $reconciliation['percent'] ?>%"></span>
          </div>
          <div class="dw-metric-list">
            <div class="dw-metric"><span>Entries reconciled</span><strong><?= (int) $reconciliation['reconciled'] ?> / <?= (int) $reconciliation['total'] ?></strong></div>
            <div class="dw-metric"><span>Receipts attached</span><strong><?= (int) $reconciliation['receipts'] ?> / <?= (int) $reconciliation['total'] ?></strong></div>
            <div class="dw-metric dw-metric--danger"><span>Unreconciled difference</span><strong><?= $e($money(abs($reconciliation['difference']))) ?></strong></div>
          </div>
        </div>
      </section>

      <section class="dw-panel">
        <header class="dw-panel__header"><div><h3>Pending Actions</h3><p>Items requiring attention</p></div></header>
        <div class="dw-panel__body">
          <ul class="dw-action-list">
            <li><span><strong><?= (int) $pendingActions['missing_receipts'] ?> missing receipts</strong>Attach supporting documents to expense entries.</span></li>
            <li><span><strong><?= (int) $pendingActions['pending_voids'] ?> pending void requests</strong>Open requests remain locked until a decision.</span></li>
          </ul>
        </div>
      </section>
    </aside>
  </div>
</section>

<div class="dw-modal" id="add-ledger-entry" role="dialog" aria-modal="true" aria-labelledby="add-entry-title" aria-hidden="true" hidden>
  <div class="dw-modal__backdrop" data-modal-close></div>
  <form class="dw-modal__dialog" action="<?= ROOT ?>/divisionalledger/create" method="post" enctype="multipart/form-data" data-ledger-create-form>
    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
    <header class="dw-modal__header">
      <h2 id="add-entry-title">Add Ledger Entry</h2>
      <button class="dw-modal__close" type="button" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
    </header>
    <div class="dw-modal__body">
      <div class="dw-field">
        <label for="entry-type">Type</label>
        <select id="entry-type" name="type" required>
          <option value="Income">Income</option>
          <option value="Expense">Expense</option>
        </select>
      </div>
      <div class="dw-field">
        <label for="entry-amount">Amount (Rs.)</label>
        <input id="entry-amount" name="amount" type="number" min="0.01" max="9999999999999.99" step="0.01" placeholder="0.00" required>
      </div>
      <div class="dw-field dw-field--span-2">
        <label for="entry-description">Description</label>
        <input id="entry-description" name="description" type="text" maxlength="2000" required>
      </div>
      <div class="dw-field">
        <label for="entry-category">Category</label>
        <input id="entry-category" name="category" type="text" maxlength="100" placeholder="e.g. Membership" required>
      </div>
      <div class="dw-field">
        <label for="entry-date">Date</label>
        <input id="entry-date" name="date" type="date" value="<?= date('Y-m-d') ?>" required>
      </div>
      <div class="dw-field dw-field--span-2">
        <span class="dw-field__label">Receipt (optional)</span>
        <label class="dw-file" for="entry-receipt">
          <?= yn_icon('upload') ?>
          <span data-file-name-for="entry-receipt">Choose a PDF, JPG, or PNG receipt</span>
          <input id="entry-receipt" name="receipt" type="file" accept="application/pdf,image/jpeg,image/png" data-file-input>
        </label>
        <small>Maximum file size: 5 MB.</small>
      </div>
      <label class="dw-checkbox dw-field--span-2">
        <input type="checkbox" name="reconciled" value="1">
        Mark this entry as reconciled
      </label>
    </div>
    <footer class="dw-modal__footer">
      <button class="dw-button dw-button--secondary" type="button" data-modal-close>Cancel</button>
      <button class="dw-button dw-button--primary" type="submit">Save Entry</button>
    </footer>
  </form>
</div>
<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
