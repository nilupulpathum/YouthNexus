<?php
require_once __DIR__ . '/../partials/icons.view.php';

$e = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$money = static fn($value) => 'Rs. ' . number_format((float) $value, 2);
$title = 'Allocate Funds - YouthNexus';
$pageTitle = 'Allocate Funds';
$pageDescription = 'Review club requests and allocate funds from the division ledger';
$currentRoute = 'divisionalallocations';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css'];
$pageScripts = [
    ROOT . '/assets/js/divisional-workflows.js',
    ROOT . '/assets/js/divisional-allocations.js',
];
$summaryCards = [
    ['value' => $money($summary['balance']), 'label' => 'Division Balance', 'note' => $division->division_name, 'icon' => 'file', 'tone' => 'blue'],
    ['value' => (string) $summary['pending'], 'label' => 'Pending Requests', 'note' => 'Awaiting review', 'icon' => 'clock', 'tone' => 'amber'],
];

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>
<section class="dw-page" aria-label="Division fund allocations">
  <?php if ($flash): ?>
    <div class="dw-alert dw-alert--<?= $flash['type'] === 'success' ? 'success' : 'error' ?>" role="status">
      <?= yn_icon($flash['type'] === 'success' ? 'check' : 'info') ?>
      <span><?= $e($flash['message']) ?></span>
    </div>
  <?php endif; ?>

  <?php if (!$sourceAccount): ?>
    <div class="dw-alert dw-alert--warning" role="status">
      <?= yn_icon('info') ?>
      <span>No active division bank account is available. Run the finance migration before allocating funds.</span>
    </div>
  <?php endif; ?>

  <div class="dw-summary-grid dw-summary-grid--two" aria-label="Allocation summary">
    <?php foreach ($summaryCards as $card): ?>
      <?php require __DIR__ . '/../partials/divisional/summary-card.view.php'; ?>
    <?php endforeach; ?>
  </div>

  <div class="dw-toolbar" aria-label="Allocation tools">
    <div class="dw-toolbar__search dw-search dw-search--plain">
      <label class="visually-hidden" for="allocation-search">Search allocations</label>
      <input id="allocation-search" type="search" placeholder="Search by club, reference, purpose, or category" data-allocation-search>
    </div>
    <button class="dw-button dw-button--secondary" type="button" data-filter-toggle aria-controls="allocation-filters" aria-expanded="false">Filters</button>
    <button class="dw-button dw-button--primary" type="button" data-modal-open="new-allocation"<?= $sourceAccount ? '' : ' disabled' ?>>New Allocation</button>
  </div>

  <section class="dw-filter-panel" id="allocation-filters" hidden>
    <h2 class="dw-filter-panel__heading">Advanced Filters for Fund Allocations</h2>
    <div class="dw-filter-grid">
      <div class="dw-field">
        <label for="allocation-club-filter">Club</label>
        <select id="allocation-club-filter" data-allocation-club-filter>
          <option value="">All clubs</option>
          <?php foreach ($clubs as $club): ?>
            <option value="<?= (int) $club->club_id ?>"><?= $e($club->club_name) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="dw-field">
        <label for="allocation-category-filter">Fund category</label>
        <select id="allocation-category-filter" data-allocation-category-filter>
          <option value="">All categories</option>
          <?php foreach ($categories as $category): ?>
            <option value="<?= $e(strtolower($category)) ?>"><?= $e($category) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="dw-field">
        <label for="allocation-status-filter">Status</label>
        <select id="allocation-status-filter" data-allocation-status-filter>
          <option value="">All statuses</option>
          <option value="pendingapproval">Pending approval</option>
          <option value="completed">Completed</option>
          <option value="rejected">Rejected</option>
          <option value="processing">Processing</option>
          <option value="failed">Failed</option>
        </select>
      </div>
      <div class="dw-field">
        <label for="allocation-sort">Sort by</label>
        <select id="allocation-sort" data-allocation-sort>
          <option value="newest">Date (newest)</option>
          <option value="oldest">Date (oldest)</option>
          <option value="amount-high">Amount (high to low)</option>
          <option value="amount-low">Amount (low to high)</option>
        </select>
      </div>
      <div class="dw-field">
        <label for="allocation-min-amount">Minimum amount</label>
        <input id="allocation-min-amount" type="number" min="0" step="0.01" data-allocation-min-amount>
      </div>
      <div class="dw-field">
        <label for="allocation-max-amount">Maximum amount</label>
        <input id="allocation-max-amount" type="number" min="0" step="0.01" data-allocation-max-amount>
      </div>
      <div class="dw-field">
        <label for="allocation-date-from">Date from</label>
        <input id="allocation-date-from" type="date" data-allocation-date-from>
      </div>
      <div class="dw-field">
        <label for="allocation-date-to">Date to</label>
        <input id="allocation-date-to" type="date" data-allocation-date-to>
      </div>
    </div>
    <div class="dw-filter-actions">
      <button class="dw-button dw-button--secondary" type="button" data-allocation-filter-reset>Reset all</button>
      <button class="dw-button dw-button--primary" type="button" data-allocation-filter-apply>Apply filters</button>
    </div>
  </section>

  <section class="dw-panel" data-allocation-section="pending">
    <header class="dw-panel__header">
      <div><h2>Pending Fund Requests</h2><p>Requests submitted by clubs in <?= $e($division->division_name) ?></p></div>
      <span class="dw-count" data-allocation-count="pending"><?= count($pendingRequests) ?> pending</span>
    </header>
    <div class="dw-table-wrap">
      <table class="dw-table">
        <thead><tr><th>Club</th><th>Purpose</th><th>Category</th><th>Amount</th><th>Requested</th><th>Action</th></tr></thead>
        <tbody data-allocation-rows="pending">
          <?php foreach ($pendingRequests as $request): ?>
            <?php $searchText = strtolower(implode(' ', [$request->club_name, $request->reference_no, $request->purpose_description, $request->fund_category])); ?>
            <tr data-allocation-row
                data-section="pending"
                data-search="<?= $e($searchText) ?>"
                data-club="<?= (int) $request->to_id ?>"
                data-category="<?= $e(strtolower((string) $request->fund_category)) ?>"
                data-status="pendingapproval"
                data-amount="<?= $e($request->amount) ?>"
                data-date="<?= $e(date('Y-m-d', strtotime($request->created_at))) ?>">
              <td class="dw-table__description"><?= $e($request->club_name) ?></td>
              <td><?= $e($request->purpose_description) ?></td>
              <td><?= $e($request->fund_category ?: 'Uncategorised') ?></td>
              <td class="dw-money"><?= $e($money($request->amount)) ?></td>
              <td><?= $e(date('d M Y', strtotime($request->created_at))) ?></td>
              <td>
                <div class="dw-row-actions">
                  <button class="dw-button dw-button--primary" type="button"
                          data-review-allocation
                          data-modal-open="review-allocation"
                          data-action="<?= ROOT ?>/divisionalallocations/decide/<?= (int) $request->allocation_id ?>"
                          data-club-name="<?= $e($request->club_name) ?>"
                          data-reference="<?= $e($request->reference_no) ?>"
                          data-purpose="<?= $e($request->purpose_description) ?>"
                          data-category-label="<?= $e($request->fund_category ?: 'Uncategorised') ?>"
                          data-amount-label="<?= $e($money($request->amount)) ?>"
                          data-requested-label="<?= $e(date('d M Y', strtotime($request->created_at))) ?>">Allocate</button>
                  <form action="<?= ROOT ?>/divisionalallocations/decide/<?= (int) $request->allocation_id ?>" method="post" data-reject-allocation-form>
                    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                    <input type="hidden" name="decision" value="reject">
                    <button class="dw-button dw-button--secondary" type="submit">Reject</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php
    $emptyTitle = 'No pending requests found';
    $emptyMessage = 'There are no club fund requests matching the current search and filters.';
    $emptyVisible = count($pendingRequests) === 0;
    require __DIR__ . '/../partials/divisional/empty-state.view.php';
    ?>
  </section>

  <section class="dw-panel" data-allocation-section="history">
    <header class="dw-panel__header">
      <div><h2>Allocation History</h2><p>Reviewed and completed club allocations</p></div>
      <span class="dw-count" data-allocation-count="history"><?= count($history) ?> records</span>
    </header>
    <div class="dw-table-wrap">
      <table class="dw-table">
        <thead><tr><th>Reference</th><th>Club</th><th>Amount</th><th>Fund Category</th><th>Date</th><th>Method</th><th>Status</th></tr></thead>
        <tbody data-allocation-rows="history">
          <?php foreach ($history as $allocation): ?>
            <?php $searchText = strtolower(implode(' ', [$allocation->club_name, $allocation->reference_no, $allocation->purpose_description, $allocation->fund_category])); ?>
            <tr data-allocation-row
                data-section="history"
                data-search="<?= $e($searchText) ?>"
                data-club="<?= (int) $allocation->to_id ?>"
                data-category="<?= $e(strtolower((string) $allocation->fund_category)) ?>"
                data-status="<?= $e(strtolower($allocation->status)) ?>"
                data-amount="<?= $e($allocation->amount) ?>"
                data-date="<?= $e($allocation->transfer_date) ?>">
              <td class="dw-table__reference"><?= $e($allocation->reference_no) ?></td>
              <td class="dw-table__description"><?= $e($allocation->club_name) ?></td>
              <td class="dw-money"><?= $e($money($allocation->amount)) ?></td>
              <td><?= $e($allocation->fund_category ?: 'Uncategorised') ?></td>
              <td><?= $e(date('d M Y', strtotime($allocation->transfer_date))) ?></td>
              <td><?= $allocation->disbursement_method === 'RTGS' ? 'Bank Transfer' : 'Cheque / SLIPS' ?></td>
              <td><?php $status = $allocation->status; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php
    $emptyTitle = 'No allocation history found';
    $emptyMessage = 'Create an allocation or change the current search and filters.';
    $emptyVisible = count($history) === 0;
    require __DIR__ . '/../partials/divisional/empty-state.view.php';
    ?>
  </section>
</section>

<div class="dw-modal" id="new-allocation" role="dialog" aria-modal="true" aria-labelledby="new-allocation-title" aria-hidden="true" hidden>
  <div class="dw-modal__backdrop" data-modal-close></div>
  <form class="dw-modal__dialog" action="<?= ROOT ?>/divisionalallocations/create" method="post" data-allocation-form>
    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
    <header class="dw-modal__header">
      <h2 id="new-allocation-title">New Fund Allocation</h2>
      <button class="dw-modal__close" type="button" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
    </header>
    <div class="dw-modal__body">
      <div class="dw-field dw-field--span-2">
        <label for="allocation-club">Club</label>
        <select id="allocation-club" name="club_id" required>
          <option value="">Select a club</option>
          <?php foreach ($clubs as $club): ?>
            <option value="<?= (int) $club->club_id ?>"><?= $e($club->club_name) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="dw-field"><label for="allocation-amount">Amount (Rs.)</label><input id="allocation-amount" name="amount" type="number" min="0.01" max="9999999999999.99" step="0.01" required></div>
      <div class="dw-field"><label for="allocation-category">Fund Category</label><input id="allocation-category" name="fund_category" type="text" maxlength="100" required></div>
      <div class="dw-field"><label for="allocation-date">Allocation Date</label><input id="allocation-date" name="transfer_date" type="date" value="<?= date('Y-m-d') ?>" required></div>
      <div class="dw-field">
        <label for="allocation-method">Disbursement Method</label>
        <select id="allocation-method" name="disbursement_method" required><option value="RTGS">Bank Transfer</option><option value="ChequeSLIPS">Cheque / SLIPS</option></select>
      </div>
      <div class="dw-field dw-field--span-2"><label for="allocation-purpose">Purpose</label><textarea id="allocation-purpose" name="purpose_description" maxlength="2000" required></textarea></div>
      <div class="dw-detail-box dw-field--span-2">
        <span>Source account</span>
        <strong><?= $sourceAccount ? $e($sourceAccount->account_label . ' - ' . $sourceAccount->bank_name) : 'Unavailable' ?></strong>
      </div>
    </div>
    <footer class="dw-modal__footer"><button class="dw-button dw-button--secondary" type="button" data-modal-close>Cancel</button><button class="dw-button dw-button--primary" type="submit"<?= $sourceAccount ? '' : ' disabled' ?>>Confirm Allocation</button></footer>
  </form>
</div>

<div class="dw-modal" id="review-allocation" role="dialog" aria-modal="true" aria-labelledby="review-allocation-title" aria-hidden="true" hidden>
  <div class="dw-modal__backdrop" data-modal-close></div>
  <form class="dw-modal__dialog" action="" method="post" data-review-allocation-form>
    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
    <input type="hidden" name="decision" value="approve">
    <header class="dw-modal__header">
      <h2 id="review-allocation-title">Review Fund Request</h2>
      <button class="dw-modal__close" type="button" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
    </header>
    <div class="dw-modal__body">
      <dl class="dw-review-list dw-field--span-2">
        <div><dt>Club</dt><dd data-review-club></dd></div>
        <div><dt>Reference</dt><dd data-review-reference></dd></div>
        <div><dt>Amount</dt><dd data-review-amount></dd></div>
        <div><dt>Category</dt><dd data-review-category></dd></div>
        <div><dt>Requested</dt><dd data-review-requested></dd></div>
        <div><dt>Purpose</dt><dd data-review-purpose></dd></div>
      </dl>
      <div class="dw-alert dw-alert--warning dw-field--span-2">
        <?= yn_icon('info') ?>
        <span>Approving this request will deduct the amount from the division ledger and credit the club ledger.</span>
      </div>
    </div>
    <footer class="dw-modal__footer"><button class="dw-button dw-button--secondary" type="button" data-modal-close>Cancel</button><button class="dw-button dw-button--primary" type="submit">Confirm Allocation</button></footer>
  </form>
</div>
<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
