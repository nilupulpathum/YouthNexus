<?php
require_once __DIR__ . '/../partials/icons.view.php';

$e = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$money = static fn($value) => 'Rs. ' . number_format((float) $value, 2);
$requestReference = static fn($id) => 'VR-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
$title = 'Approve Void Requests - YouthNexus';
$pageTitle = 'Approve Void Requests';
$pageDescription = 'Review club ledger-entry void requests within your division';
$currentRoute = 'divisionalvoidapproval';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css'];
$pageScripts = [
    ROOT . '/assets/js/divisional-workflows.js',
    ROOT . '/assets/js/divisional-void-approval.js',
];
$summaryCards = [
    ['value' => $summary['pending'], 'label' => 'Pending From Clubs', 'note' => $division->division_name, 'icon' => 'clock', 'tone' => 'amber'],
    ['value' => $summary['approved'], 'label' => 'Approved This Year', 'note' => 'Club ledger requests', 'icon' => 'check', 'tone' => 'green'],
    ['value' => $summary['rejected'], 'label' => 'Rejected This Year', 'note' => 'Club ledger requests', 'icon' => 'close', 'tone' => 'red'],
];
$reviewEvidenceForView = $reviewEvidence;
foreach ($reviewEvidenceForView as &$evidenceItem) {
    $evidenceItem['receipt_url'] = !empty($evidenceItem['receipt_path'])
        ? ROOT . $evidenceItem['receipt_path']
        : null;
    unset($evidenceItem['receipt_path']);
}
unset($evidenceItem);
$reviewEvidenceJson = json_encode(
    $reviewEvidenceForView,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES
) ?: '{}';

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>
<section class="dw-page" aria-label="Club void request approvals">
  <?php if ($flash): ?>
    <div class="dw-alert dw-alert--<?= $flash['type'] === 'success' ? 'success' : 'error' ?>" role="status">
      <?= yn_icon($flash['type'] === 'success' ? 'check' : 'info') ?>
      <span><?= $e($flash['message']) ?></span>
    </div>
  <?php endif; ?>

  <div class="dw-summary-grid dw-summary-grid--three" aria-label="Void approval summary">
    <?php foreach ($summaryCards as $card): ?>
      <?php require __DIR__ . '/../partials/divisional/summary-card.view.php'; ?>
    <?php endforeach; ?>
  </div>

  <div class="dw-toolbar" aria-label="Void approval tools">
    <div class="dw-toolbar__search dw-search dw-search--plain">
      <label class="visually-hidden" for="void-approval-search">Search club void requests</label>
      <input id="void-approval-search" type="search" placeholder="Search by request, club, ledger entry, or reason" data-approval-search>
    </div>
    <button class="dw-button dw-button--secondary" type="button" data-filter-toggle aria-controls="void-approval-filters" aria-expanded="false">Filters</button>
    <a class="dw-button dw-button--secondary" href="<?= ROOT ?>/divisionalvoidapproval/export"><?= yn_icon('download') ?> Export</a>
  </div>

  <section class="dw-filter-panel" id="void-approval-filters" hidden>
    <h2 class="dw-filter-panel__heading">Advanced Filters for Void Requests</h2>
    <div class="dw-filter-grid">
      <div class="dw-field">
        <label for="approval-club-filter">Club</label>
        <select id="approval-club-filter" data-approval-club-filter>
          <option value="">All clubs</option>
          <?php foreach ($clubs as $club): ?>
            <option value="<?= (int) $club->club_id ?>"><?= $e($club->club_name) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="dw-field">
        <label for="approval-status-filter">Status</label>
        <select id="approval-status-filter" data-approval-status-filter>
          <option value="">All statuses</option>
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
        </select>
      </div>
      <div class="dw-field">
        <label for="approval-type-filter">Ledger type</label>
        <select id="approval-type-filter" data-approval-type-filter>
          <option value="">All types</option>
          <option value="income">Income</option>
          <option value="expense">Expense</option>
        </select>
      </div>
      <div class="dw-field">
        <label for="approval-reason-filter">Reason contains</label>
        <input id="approval-reason-filter" type="text" maxlength="100" data-approval-reason-filter>
      </div>
      <div class="dw-field">
        <label for="approval-min-amount">Minimum amount</label>
        <input id="approval-min-amount" type="number" min="0" step="0.01" data-approval-min-amount>
      </div>
      <div class="dw-field">
        <label for="approval-max-amount">Maximum amount</label>
        <input id="approval-max-amount" type="number" min="0" step="0.01" data-approval-max-amount>
      </div>
      <div class="dw-field">
        <label for="approval-date-from">Requested from</label>
        <input id="approval-date-from" type="date" data-approval-date-from>
      </div>
      <div class="dw-field">
        <label for="approval-date-to">Requested to</label>
        <input id="approval-date-to" type="date" data-approval-date-to>
      </div>
      <div class="dw-field">
        <label for="approval-sort">Sort by</label>
        <select id="approval-sort" data-approval-sort>
          <option value="newest">Requested date (newest)</option>
          <option value="oldest">Requested date (oldest)</option>
          <option value="amount-high">Amount (high to low)</option>
          <option value="amount-low">Amount (low to high)</option>
        </select>
      </div>
    </div>
    <div class="dw-filter-actions">
      <button class="dw-button dw-button--secondary" type="button" data-approval-filter-reset>Reset all</button>
      <button class="dw-button dw-button--primary" type="button" data-approval-filter-apply>Apply filters</button>
    </div>
  </section>

  <section class="dw-panel" aria-labelledby="pending-void-title">
    <header class="dw-panel__header">
      <div><h2 id="pending-void-title">Pending Void Requests</h2><p>Club requests waiting for your decision</p></div>
      <span class="dw-count" data-pending-count><?= count($pendingRequests) ?> <?= count($pendingRequests) === 1 ? 'request' : 'requests' ?></span>
    </header>
    <div class="dw-table-wrap">
      <table class="dw-table">
        <thead><tr><th>Request ID</th><th>Club</th><th>Ledger Entry</th><th>Reason</th><th>Requested</th><th>Action</th></tr></thead>
        <tbody data-pending-body>
          <?php foreach ($pendingRequests as $request): ?>
            <?php
            $reference = $requestReference($request->void_request_id);
            $searchText = strtolower(implode(' ', [$reference, $request->club_name, $request->reference_no, $request->description, $request->reason, $request->requester_name]));
            ?>
            <tr data-approval-row
                data-section="pending"
                data-search="<?= $e($searchText) ?>"
                data-club="<?= (int) $request->club_id ?>"
                data-status="pending"
                data-type="<?= $e(strtolower($request->type)) ?>"
                data-reason="<?= $e(strtolower($request->reason)) ?>"
                data-amount="<?= $e($request->amount) ?>"
                data-date="<?= $e(substr((string) $request->requested_at, 0, 10)) ?>">
              <td class="dw-table__reference"><?= $e($reference) ?></td>
              <td><?= $e($request->club_name) ?></td>
              <td><?= $e($request->reference_no) ?> - <?= $e($money($request->amount)) ?></td>
              <td class="dw-table__description"><?= $e($request->reason) ?></td>
              <td><?= $e(date('d M Y', strtotime($request->requested_at))) ?></td>
              <td>
                <button class="dw-button dw-button--primary" type="button"
                        data-review-request
                        data-request-id="<?= (int) $request->void_request_id ?>"
                        data-request-reference="<?= $e($reference) ?>"
                        data-club-name="<?= $e($request->club_name) ?>"
                        data-entry-reference="<?= $e($request->reference_no) ?>"
                        data-entry-description="<?= $e($request->description) ?>"
                        data-amount-label="<?= $e($money($request->amount)) ?>"
                        data-type-label="<?= $e($request->type) ?>"
                        data-reason-label="<?= $e($request->reason) ?>"
                        data-requester="<?= $e(trim((string) $request->requester_name) ?: 'Club Treasurer') ?>"
                        data-requested-at="<?= $e(date('d M Y, H:i', strtotime($request->requested_at))) ?>"
                        data-modal-open="review-void-request">Review</button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="dw-empty-state<?= !$pendingRequests ? ' is-visible' : '' ?>" data-pending-empty>
      <span class="dw-empty-state__icon" aria-hidden="true"><?= yn_icon('check') ?></span>
      <strong>No pending void requests</strong>
      <p>New requests from clubs in your division appear here.</p>
    </div>
  </section>

  <section class="dw-panel" aria-labelledby="decided-void-title">
    <header class="dw-panel__header">
      <div><h2 id="decided-void-title">Recently Decided</h2><p>Completed club void-request decisions</p></div>
      <span class="dw-count" data-decided-count><?= count($decidedRequests) ?> <?= count($decidedRequests) === 1 ? 'request' : 'requests' ?></span>
    </header>
    <div class="dw-table-wrap">
      <table class="dw-table">
        <thead><tr><th>Request ID</th><th>Club</th><th>Ledger Entry</th><th>Decided On</th><th>Decision</th><th>Remarks</th></tr></thead>
        <tbody data-decided-body>
          <?php foreach ($decidedRequests as $request): ?>
            <?php
            $reference = $requestReference($request->void_request_id);
            $searchText = strtolower(implode(' ', [$reference, $request->club_name, $request->reference_no, $request->description, $request->reason, $request->remarks, $request->status]));
            ?>
            <tr data-approval-row
                data-section="decided"
                data-search="<?= $e($searchText) ?>"
                data-club="<?= (int) $request->club_id ?>"
                data-status="<?= $e(strtolower($request->status)) ?>"
                data-type="<?= $e(strtolower($request->type)) ?>"
                data-reason="<?= $e(strtolower($request->reason)) ?>"
                data-amount="<?= $e($request->amount) ?>"
                data-date="<?= $e(substr((string) $request->requested_at, 0, 10)) ?>">
              <td class="dw-table__reference"><?= $e($reference) ?></td>
              <td><?= $e($request->club_name) ?></td>
              <td><?= $e($request->reference_no) ?> - <?= $e($money($request->amount)) ?></td>
              <td><?= $e($request->decided_at ? date('d M Y', strtotime($request->decided_at)) : '-') ?></td>
              <td><?php $status = $request->status; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?></td>
              <td class="dw-table__description"><?= $e($request->remarks ?: '-') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="dw-empty-state<?= !$decidedRequests ? ' is-visible' : '' ?>" data-decided-empty>
      <span class="dw-empty-state__icon" aria-hidden="true"><?= yn_icon('file') ?></span>
      <strong>No decisions found</strong>
      <p>Approved and rejected requests appear here.</p>
    </div>
  </section>
</section>

<script type="application/json" id="void-review-evidence"><?= $reviewEvidenceJson ?></script>

<div class="dw-modal" id="review-void-request" role="dialog" aria-modal="true" aria-labelledby="review-void-request-title" aria-hidden="true" hidden>
  <div class="dw-modal__backdrop" data-modal-close></div>
  <form class="dw-modal__dialog dw-modal__dialog--wide" action="" method="post" data-decision-form data-decision-base="<?= ROOT ?>/divisionalvoidapproval/decide/">
    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
    <header class="dw-modal__header">
      <h2 id="review-void-request-title">Review Void Request</h2>
      <button class="dw-modal__close" type="button" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
    </header>
    <div class="dw-modal__body">
      <dl class="dw-review-list dw-field--span-2">
        <div><dt>Request</dt><dd data-review-reference></dd></div>
        <div><dt>Club</dt><dd data-review-club></dd></div>
        <div><dt>Ledger entry</dt><dd data-review-entry></dd></div>
        <div><dt>Description</dt><dd data-review-description></dd></div>
        <div><dt>Type</dt><dd data-review-type></dd></div>
        <div><dt>Reason</dt><dd data-review-reason></dd></div>
        <div><dt>Requested by</dt><dd data-review-requester></dd></div>
        <div><dt>Requested at</dt><dd data-review-date></dd></div>
      </dl>
      <section class="dw-evidence-section dw-field--span-2" aria-labelledby="entry-evidence-title">
        <div class="dw-section-header">
          <div><h3 id="entry-evidence-title">Ledger Entry Evidence</h3><p>Confirm the entry and its supporting record before deciding</p></div>
          <a class="dw-button dw-button--ghost" href="#" target="_blank" rel="noopener" data-review-receipt hidden><?= yn_icon('eye') ?> View Receipt</a>
        </div>
        <dl class="dw-review-list">
          <div><dt>Category</dt><dd data-evidence-category></dd></div>
          <div><dt>Transaction date</dt><dd data-evidence-entry-date></dd></div>
          <div><dt>Recorded by</dt><dd data-evidence-creator></dd></div>
          <div><dt>Recorded at</dt><dd data-evidence-created-at></dd></div>
          <div><dt>Reconciled</dt><dd data-evidence-reconciled></dd></div>
          <div><dt>Fund allocation</dt><dd data-evidence-allocation></dd></div>
          <div><dt>Receipt</dt><dd data-evidence-receipt-status></dd></div>
        </dl>
      </section>
      <section class="dw-evidence-section dw-field--span-2" data-warning-section hidden aria-labelledby="review-warnings-title">
        <div class="dw-section-header"><div><h3 id="review-warnings-title">Review Warnings</h3><p>Items that require confirmation</p></div></div>
        <ul class="dw-evidence-list" data-review-warnings></ul>
      </section>
      <section class="dw-evidence-section dw-field--span-2" aria-labelledby="nearby-entries-title">
        <div class="dw-section-header"><div><h3 id="nearby-entries-title">Nearby Ledger Entries</h3><p>Entries recorded within three days of the requested transaction</p></div></div>
        <div class="dw-table-wrap">
          <table class="dw-table">
            <thead><tr><th>Reference</th><th>Date</th><th>Description</th><th>Amount</th><th>Type</th><th>Check</th></tr></thead>
            <tbody data-nearby-entries></tbody>
          </table>
        </div>
        <p class="dw-muted-copy" data-nearby-empty>No nearby entries found.</p>
      </section>
      <section class="dw-evidence-section dw-field--span-2" aria-labelledby="balance-impact-title">
        <div class="dw-section-header"><div><h3 id="balance-impact-title">Balance Impact</h3><p>Projected club ledger balance if this request is approved</p></div></div>
        <div class="dw-impact-grid">
          <div><span>Current balance</span><strong data-current-balance></strong></div>
          <div><span>Void adjustment</span><strong data-balance-change></strong></div>
          <div><span>Balance after approval</span><strong data-projected-balance></strong></div>
        </div>
      </section>
      <div class="dw-field dw-field--span-2">
        <label for="void-decision">Decision</label>
        <select id="void-decision" name="decision" required data-decision-select>
          <option value="approve">Approve Void</option>
          <option value="reject">Reject Request</option>
        </select>
      </div>
      <div class="dw-field dw-field--span-2">
        <label for="void-decision-remarks">Remarks <span data-remarks-required hidden>(required when rejecting)</span></label>
        <textarea id="void-decision-remarks" name="remarks" maxlength="1000" placeholder="Add a note for the Club Treasurer" data-decision-remarks></textarea>
      </div>
      <div class="dw-alert dw-alert--warning dw-field--span-2">
        <?= yn_icon('info') ?>
        <span>Approving marks the entry as voided, updates the club ledger balance, and notifies the Club Treasurer.</span>
      </div>
    </div>
    <footer class="dw-modal__footer">
      <button class="dw-button dw-button--secondary" type="button" data-modal-close>Cancel</button>
      <button class="dw-button dw-button--primary" type="submit" data-submit-decision>Submit Decision</button>
    </footer>
  </form>
</div>
<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
