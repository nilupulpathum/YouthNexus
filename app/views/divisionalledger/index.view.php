<?php
require_once __DIR__ . '/../partials/icons.view.php';

$e = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$money = static fn($value) => 'LKR ' . number_format((float) $value, 2);
$title = 'Manage Ledger - YouthNexus';
$pageTitle = 'Manage Ledger';
$pageDescription = 'Review, reconcile, and manage all divisional financial entries';
$currentRoute = 'divisionalledger';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css', ROOT . '/assets/css/divisional-tabs.css'];
$pageScripts = [
    ROOT . '/assets/js/divisional-workflows.js',
    ROOT . '/assets/js/divisional-ledger.js',
];

$summaryCards = [
    ['value' => $money($summary['balance']), 'label' => 'Current Ledger Balance', 'badge' => $division->division_name, 'subtitle' => 'Available balance in division ledger', 'tone' => 'blue'],
    ['value' => $money($summary['income']), 'label' => 'Income This Year', 'badge' => date('Y') . ' FY', 'subtitle' => 'Approved division income', 'tone' => 'green'],
    ['value' => $money($summary['expenses']), 'label' => 'Expenses This Year', 'badge' => date('Y') . ' FY', 'subtitle' => 'Approved division expenses', 'tone' => 'red'],
    ['value' => (string) $summary['review_count'], 'label' => 'Entries Requiring Review', 'badge' => (int) $summary['review_count'] > 0 ? 'Needs Review' : 'Up to Date', 'subtitle' => 'Pending or missing receipts', 'tone' => (int) $summary['review_count'] > 0 ? 'amber' : 'green'],
];

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>
<section class="dw-page dw-ledger-page" aria-label="Division general ledger" data-finance-refresh-on-change>
  <?php if ($flash): ?>
    <div class="dw-alert dw-alert--<?= $flash['type'] === 'success' ? 'success' : 'error' ?>" role="status">
      <?= yn_icon($flash['type'] === 'success' ? 'check' : 'info') ?>
      <span><?= $e($flash['message']) ?></span>
    </div>
  <?php endif; ?>

  <div class="dw-top-actions" style="display: flex; justify-content: flex-end; align-items: center; gap: 10px; margin-bottom: 16px;">
    <a class="dw-button dw-button--secondary" href="<?= ROOT ?>/divisionalledger/export">
      <?= yn_icon('download') ?> Export Ledger
    </a>
    <button class="dw-button dw-button--primary db-primary-action" type="button" data-modal-open="add-transaction"><?= yn_icon('plus') ?> Log Transaction</button>
  </div>

  <div class="dw-summary-grid" aria-label="Ledger summary">
    <?php foreach ($summaryCards as $card): ?>
      <?php require __DIR__ . '/../partials/divisional/summary-card.view.php'; ?>
    <?php endforeach; ?>
  </div>

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

  <div class="dw-toolbar" aria-label="Ledger tools">
    <div class="dw-toolbar__search dw-search dw-search--plain">
      <label class="visually-hidden" for="ledger-search">Search ledger entries</label>
      <input id="ledger-search" type="search" placeholder="Search by reference, description, or category" data-ledger-search>
    </div>
    <button class="dw-button dw-button--secondary" type="button" data-filter-toggle aria-controls="ledger-filters" aria-expanded="false">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
      Filters
    </button>
  </div>

  <section class="dw-filter-panel" id="ledger-filters" hidden>
    <h2 class="dw-filter-panel__heading">Advanced Filters for Ledger Entries</h2>
    <div class="dw-filter-grid">
      <div class="dw-field">
        <label for="filter-entry-type">Entry type</label>
        <select id="filter-entry-type" data-ledger-quick-type>
          <option value="">All entry types</option>
          <option value="income">Income</option>
          <option value="expense">Expense</option>
        </select>
      </div>
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
        <label for="filter-category">Category</label>
        <select id="filter-category" data-filter-category>
          <option value="">All categories</option>
          <?php foreach (($categories ?? []) as $cat): ?>
            <option value="<?= $e(strtolower($cat)) ?>"><?= $e($cat) ?></option>
          <?php endforeach; ?>
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

  <div class="dw-content-grid dw-content-grid--ledger">
    <section class="dw-panel">
      <header class="dw-panel__header">
        <div>
          <h2>Divisional General Ledger</h2>
          <p><?= $e($division->division_name) ?> financial entries</p>
        </div>
        <span class="dw-count" data-entry-count><?= count($entries) ?> <?= count($entries) === 1 ? 'entry' : 'entries' ?></span>
      </header>
      <div class="dw-table-wrap">
        <table class="dw-table dw-ledger-table">
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
              $canEdit = $entry->status === 'Approved' && (int) $entry->has_pending_void !== 1 && $entry->allocation_id === null;
              $searchValue = strtolower(implode(' ', [$entry->reference_no, $entry->description, $entry->category, $entry->type]));
              $displayStatus = $entry->status === 'Approved' && $entry->type === 'Expense' && !$hasReceipt
                  ? 'Missing Receipt'
                  : $entry->status;
              ?>
              <tr data-ledger-row
                  data-search="<?= $e($searchValue) ?>"
                  data-type="<?= $e(strtolower($entry->type)) ?>"
                  data-type-label="<?= $e($entry->type) ?>"
                  data-status="<?= $e(strtolower($entry->status)) ?>"
                  data-category="<?= $e(strtolower((string) $entry->category)) ?>"
                  data-category-label="<?= $e($entry->category ?: '') ?>"
                  data-reconciled="<?= $isReconciled ? 'yes' : 'no' ?>"
                  data-receipt="<?= $hasReceipt ? 'attached' : 'missing' ?>"
                  data-date="<?= $e($entry->date) ?>"
                  data-entry-id="<?= (int) $entry->entry_id ?>"
                  data-amount="<?= $e($entry->amount) ?>"
                  data-description="<?= $e($entry->description) ?>"
                  data-receipt-url="<?= $hasReceipt ? $e(ROOT . '/financereceipt/show/' . (int) $entry->entry_id) : '' ?>">
                <td><?= $e(date('d M Y', strtotime($entry->date))) ?></td>
                <td class="dw-table__reference"><?= $e($entry->reference_no) ?></td>
                <td class="dw-table__description"><?= $e($entry->description) ?></td>
                <td><?= $e($entry->category ?: 'Uncategorised') ?></td>
                <td><?php $status = $entry->type; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?></td>
                <td class="dw-money dw-money--expense"><?= $entry->type === 'Expense' ? $e($money($entry->amount)) : '' ?></td>
                <td class="dw-money dw-money--income"><?= $entry->type === 'Income' ? $e($money($entry->amount)) : '' ?></td>
                <td class="dw-money"><?= $e($money($entry->running_balance)) ?></td>
                <td><?php $status = $displayStatus; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?></td>
                <td>
                  <div class="dw-row-actions">
                    <?php if ($hasReceipt): ?>
                      <a class="dw-button dw-button--ghost db-view-button" href="<?= ROOT ?>/financereceipt/show/<?= (int) $entry->entry_id ?>" target="_blank" rel="noopener" aria-label="View receipt for <?= $e($entry->reference_no) ?>"><?= yn_icon('eye') ?> View Receipt</a>
                    <?php endif; ?>
                    <?php if ($canEdit): ?>
                      <button class="dw-button dw-button--ghost db-secondary-action" type="button" data-edit-transaction data-modal-open="edit-transaction"><?= yn_icon('pen') ?> Edit</button>
                    <?php endif; ?>
                    <?php if ($entry->status === 'Approved' && (int) $entry->has_pending_void !== 1): ?>
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
  </div>
</section>

<div class="dw-modal" id="add-transaction" role="dialog" aria-modal="true" aria-labelledby="add-transaction-title" aria-hidden="true" hidden>
  <div class="dw-modal__backdrop" data-modal-close></div>
  <form class="dw-modal__dialog" action="<?= ROOT ?>/divisionaltransactions/create" method="post" enctype="multipart/form-data" data-transaction-form>
    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
    <header class="dw-modal__header">
      <h2 id="add-transaction-title">Log Transaction</h2>
      <button class="dw-modal__close" type="button" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
    </header>
    <div class="dw-modal__body">
      <div class="dw-field"><label for="transaction-type">Type</label><select id="transaction-type" name="type" required><option value="Income">Income</option><option value="Expense">Expense</option></select></div>
      <div class="dw-field"><label for="transaction-amount">Amount (LKR)</label><input id="transaction-amount" name="amount" type="number" min="0.01" max="9999999999999.99" step="0.01" placeholder="0.00" required></div>
      <div class="dw-field"><label for="transaction-category">Category</label><input id="transaction-category" name="category" type="text" maxlength="100" placeholder="e.g. Grant" required></div>
      <div class="dw-field"><label for="transaction-date">Date</label><input id="transaction-date" name="date" type="date" value="<?= date('Y-m-d') ?>" required></div>
      <div class="dw-field dw-field--span-2"><label for="transaction-description">Description</label><textarea id="transaction-description" name="description" maxlength="2000" required></textarea></div>
      <div class="dw-field dw-field--span-2">
        <span class="dw-field__label">Receipt (optional)</span>
        <label class="dw-file" for="transaction-receipt"><?= yn_icon('upload') ?><span data-file-name-for="transaction-receipt">Choose a PDF, JPG, or PNG receipt</span><input id="transaction-receipt" name="receipt" type="file" accept="application/pdf,image/jpeg,image/png" data-file-input></label>
        <small>Maximum file size: 5 MB.</small>
      </div>
    </div>
    <footer class="dw-modal__footer"><button class="dw-button dw-button--secondary" type="button" data-modal-close>Cancel</button><button class="dw-button dw-button--primary" type="submit">Save Transaction</button></footer>
  </form>
</div>

<div class="dw-modal" id="edit-transaction" role="dialog" aria-modal="true" aria-labelledby="edit-transaction-title" aria-hidden="true" hidden>
  <div class="dw-modal__backdrop" data-modal-close></div>
  <form class="dw-modal__dialog" action="" method="post" enctype="multipart/form-data" data-edit-transaction-form data-update-base="<?= ROOT ?>/divisionaltransactions/update/">
    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
    <header class="dw-modal__header">
      <h2 id="edit-transaction-title">Edit Transaction</h2>
      <button class="dw-modal__close" type="button" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
    </header>
    <div class="dw-modal__body">
      <div class="dw-field"><label for="edit-transaction-type">Type</label><select id="edit-transaction-type" name="type" required><option value="Income">Income</option><option value="Expense">Expense</option></select></div>
      <div class="dw-field"><label for="edit-transaction-amount">Amount (LKR)</label><input id="edit-transaction-amount" name="amount" type="number" min="0.01" max="9999999999999.99" step="0.01" required></div>
      <div class="dw-field"><label for="edit-transaction-category">Category</label><input id="edit-transaction-category" name="category" type="text" maxlength="100" required></div>
      <div class="dw-field"><label for="edit-transaction-date">Date</label><input id="edit-transaction-date" name="date" type="date" required></div>
      <div class="dw-field dw-field--span-2"><label for="edit-transaction-description">Description</label><textarea id="edit-transaction-description" name="description" maxlength="2000" required></textarea></div>
      <div class="dw-field dw-field--span-2">
        <span class="dw-field__label">Replace receipt (optional)</span>
        <label class="dw-file" for="edit-transaction-receipt"><?= yn_icon('upload') ?><span data-file-name-for="edit-transaction-receipt">Choose a PDF, JPG, or PNG receipt</span><input id="edit-transaction-receipt" name="receipt" type="file" accept="application/pdf,image/jpeg,image/png" data-file-input></label>
      </div>
      <label class="dw-checkbox dw-field--span-2" data-remove-receipt-row hidden><input type="checkbox" name="remove_receipt" value="1">Remove the current receipt</label>
    </div>
    <footer class="dw-modal__footer"><button class="dw-button dw-button--secondary" type="button" data-modal-close>Cancel</button><button class="dw-button dw-button--primary" type="submit">Save Changes</button></footer>
  </form>
</div>
<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
