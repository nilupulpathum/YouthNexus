<?php
require_once __DIR__ . '/../partials/icons.view.php';

$e = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$money = static fn($value) => 'Rs. ' . number_format((float) $value, 2);
$title = 'Log Transactions - YouthNexus';
$pageTitle = 'Log Transactions';
$pageDescription = 'Record and review division-level income and expenses';
$currentRoute = 'divisionaltransactions';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css'];
$pageScripts = [
    ROOT . '/assets/js/divisional-workflows.js',
    ROOT . '/assets/js/divisional-transactions.js',
];
$summaryCards = [
    ['value' => $money($summary['income']), 'label' => 'Income This Year', 'note' => 'Approved transactions', 'icon' => 'download', 'tone' => 'green'],
    ['value' => $money($summary['expenses']), 'label' => 'Expenses This Year', 'note' => 'Approved transactions', 'icon' => 'upload', 'tone' => 'red'],
    ['value' => $money($summary['balance']), 'label' => 'Division Balance', 'note' => $division->division_name, 'icon' => 'file', 'tone' => 'blue'],
];

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>
<section class="dw-page" aria-label="Division transactions">
  <?php if ($flash): ?>
    <div class="dw-alert dw-alert--<?= $flash['type'] === 'success' ? 'success' : 'error' ?>" role="status">
      <?= yn_icon($flash['type'] === 'success' ? 'check' : 'info') ?>
      <span><?= $e($flash['message']) ?></span>
    </div>
  <?php endif; ?>

  <div class="dw-summary-grid dw-summary-grid--three" aria-label="Transaction summary">
    <?php foreach ($summaryCards as $card): ?>
      <?php require __DIR__ . '/../partials/divisional/summary-card.view.php'; ?>
    <?php endforeach; ?>
  </div>

  <div class="dw-toolbar" aria-label="Transaction tools">
    <div class="dw-toolbar__search dw-search dw-search--plain">
      <label class="visually-hidden" for="transaction-search">Search transactions</label>
      <input id="transaction-search" type="search" placeholder="Search by reference, description, or category" data-transaction-search>
    </div>
    <button class="dw-button dw-button--secondary" type="button" data-filter-toggle aria-controls="transaction-filters" aria-expanded="false">Filters</button>
    <button class="dw-button dw-button--primary" type="button" data-modal-open="add-transaction">Add Transaction</button>
  </div>

  <section class="dw-filter-panel" id="transaction-filters" hidden>
    <h2 class="dw-filter-panel__heading">Advanced Filters for Transactions</h2>
    <div class="dw-filter-grid">
      <div class="dw-field">
        <label for="transaction-type-filter">Type</label>
        <select id="transaction-type-filter" data-transaction-type-filter>
          <option value="">All types</option>
          <option value="income">Income</option>
          <option value="expense">Expense</option>
        </select>
      </div>
      <div class="dw-field">
        <label for="transaction-category-filter">Category</label>
        <select id="transaction-category-filter" data-transaction-category-filter>
          <option value="">All categories</option>
          <?php foreach ($categories as $category): ?>
            <option value="<?= $e(strtolower($category)) ?>"><?= $e($category) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="dw-field">
        <label for="transaction-receipt-filter">Receipt status</label>
        <select id="transaction-receipt-filter" data-transaction-receipt-filter>
          <option value="">Any status</option>
          <option value="attached">Attached</option>
          <option value="missing">Not attached</option>
        </select>
      </div>
      <div class="dw-field">
        <label for="transaction-sort">Sort by</label>
        <select id="transaction-sort" data-transaction-sort>
          <option value="newest">Date (newest)</option>
          <option value="oldest">Date (oldest)</option>
          <option value="amount-high">Amount (high to low)</option>
          <option value="amount-low">Amount (low to high)</option>
        </select>
      </div>
      <div class="dw-field">
        <label for="transaction-min-amount">Minimum amount</label>
        <input id="transaction-min-amount" type="number" min="0" step="0.01" data-transaction-min-amount>
      </div>
      <div class="dw-field">
        <label for="transaction-max-amount">Maximum amount</label>
        <input id="transaction-max-amount" type="number" min="0" step="0.01" data-transaction-max-amount>
      </div>
      <div class="dw-field">
        <label for="transaction-date-from">Date from</label>
        <input id="transaction-date-from" type="date" data-transaction-date-from>
      </div>
      <div class="dw-field">
        <label for="transaction-date-to">Date to</label>
        <input id="transaction-date-to" type="date" data-transaction-date-to>
      </div>
    </div>
    <div class="dw-filter-actions">
      <button class="dw-button dw-button--secondary" type="button" data-transaction-filter-reset>Reset all</button>
      <button class="dw-button dw-button--primary" type="button" data-transaction-filter-apply>Apply filters</button>
    </div>
  </section>

  <div class="dw-section-header">
    <div><h2>Transactions</h2><p>Income and expense entries for <?= $e($division->division_name) ?></p></div>
    <span class="dw-count" data-transaction-count><?= count($transactions) ?> <?= count($transactions) === 1 ? 'transaction' : 'transactions' ?></span>
  </div>

  <div class="dw-record-grid" data-transaction-grid>
    <?php foreach ($transactions as $transaction): ?>
      <?php
      $isIncome = $transaction->type === 'Income';
      $hasReceipt = !empty($transaction->attachment_url);
      $canEdit = $transaction->status === 'Approved' && (int) $transaction->has_pending_void !== 1;
      $searchText = strtolower(implode(' ', [
          $transaction->reference_no,
          $transaction->description,
          $transaction->category,
          $transaction->type,
      ]));
      ?>
      <article class="dw-record-card dw-record-card--<?= $isIncome ? 'income' : 'expense' ?>"
               data-transaction-card
               data-search="<?= $e($searchText) ?>"
               data-type="<?= $e(strtolower($transaction->type)) ?>"
               data-category="<?= $e(strtolower((string) $transaction->category)) ?>"
               data-receipt="<?= $hasReceipt ? 'attached' : 'missing' ?>"
               data-amount="<?= $e($transaction->amount) ?>"
               data-date="<?= $e($transaction->date) ?>"
               data-entry-id="<?= (int) $transaction->entry_id ?>"
               data-description="<?= $e($transaction->description) ?>"
               data-category-label="<?= $e($transaction->category) ?>"
               data-type-label="<?= $e($transaction->type) ?>"
               data-receipt-url="<?= $hasReceipt ? $e(ROOT . $transaction->attachment_url) : '' ?>">
        <header class="dw-record-card__header">
          <div class="dw-record-card__identity">
            <span class="dw-record-card__icon" aria-hidden="true"><?= yn_icon($isIncome ? 'download' : 'upload') ?></span>
            <div>
              <div class="dw-record-card__meta"><span><?= $e(date('d M Y', strtotime($transaction->date))) ?></span><span><?= $e($transaction->category ?: 'Uncategorised') ?></span></div>
              <div class="dw-record-card__amount"><?= $e($money($transaction->amount)) ?></div>
            </div>
          </div>
          <?php $status = $transaction->type; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?>
        </header>
        <h3 class="dw-record-card__title"><?= $e($transaction->description) ?></h3>
        <div class="dw-record-card__details">
          <span><?= $hasReceipt ? 'Receipt attached' : 'No receipt' ?></span>
          <?php $status = $transaction->status; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?>
        </div>
        <footer class="dw-record-card__footer">
          <span class="dw-record-card__reference"><?= $e($transaction->reference_no) ?></span>
          <div class="dw-record-card__actions">
            <?php if ($hasReceipt): ?>
              <a class="dw-button dw-button--ghost" href="<?= $e(ROOT . $transaction->attachment_url) ?>" target="_blank" rel="noopener"><?= yn_icon('eye') ?> View Receipt</a>
            <?php endif; ?>
            <?php if ($canEdit): ?>
              <button class="dw-button dw-button--ghost" type="button" data-edit-transaction data-modal-open="edit-transaction"><?= yn_icon('pen') ?> Edit</button>
            <?php endif; ?>
          </div>
        </footer>
      </article>
    <?php endforeach; ?>
  </div>
  <?php
  $emptyTitle = 'No transactions found';
  $emptyMessage = 'Add a transaction or change the current search and filters.';
  $emptyVisible = count($transactions) === 0;
  require __DIR__ . '/../partials/divisional/empty-state.view.php';
  ?>
</section>

<div class="dw-modal" id="add-transaction" role="dialog" aria-modal="true" aria-labelledby="add-transaction-title" aria-hidden="true" hidden>
  <div class="dw-modal__backdrop" data-modal-close></div>
  <form class="dw-modal__dialog" action="<?= ROOT ?>/divisionaltransactions/create" method="post" enctype="multipart/form-data" data-transaction-form>
    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
    <header class="dw-modal__header">
      <h2 id="add-transaction-title">Add Transaction</h2>
      <button class="dw-modal__close" type="button" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
    </header>
    <div class="dw-modal__body">
      <div class="dw-field"><label for="transaction-type">Type</label><select id="transaction-type" name="type" required><option value="Income">Income</option><option value="Expense">Expense</option></select></div>
      <div class="dw-field"><label for="transaction-amount">Amount (Rs.)</label><input id="transaction-amount" name="amount" type="number" min="0.01" max="9999999999999.99" step="0.01" required></div>
      <div class="dw-field"><label for="transaction-category">Category</label><input id="transaction-category" name="category" type="text" maxlength="100" required></div>
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
      <div class="dw-field"><label for="edit-transaction-amount">Amount (Rs.)</label><input id="edit-transaction-amount" name="amount" type="number" min="0.01" max="9999999999999.99" step="0.01" required></div>
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
