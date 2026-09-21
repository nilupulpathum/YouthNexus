<?php
require_once __DIR__ . '/../partials/icons.view.php';

$e = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$money = static fn($value) => 'Rs. ' . number_format((float) $value, 2);
$requestReference = static fn($id) => 'VR-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
$title = 'Request Void - YouthNexus';
$pageTitle = 'Request Void';
$pageDescription = 'Request Zonal review of division ledger entries';
$currentRoute = 'divisionalvoidrequest';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css'];
$pageScripts = [
    ROOT . '/assets/js/divisional-workflows.js',
    ROOT . '/assets/js/divisional-void-request.js',
];
$summaryCards = [
    ['value' => $summary['sent_this_month'], 'label' => 'Sent This Month', 'note' => 'Division to Zonal', 'icon' => 'file', 'tone' => 'blue'],
    ['value' => $summary['pending'], 'label' => 'Awaiting Zonal Review', 'note' => 'Pending requests', 'icon' => 'clock', 'tone' => 'amber'],
    ['value' => $summary['approved'], 'label' => 'Approved by Zonal', 'note' => 'All recorded requests', 'icon' => 'check', 'tone' => 'green'],
];

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>
<section class="dw-page" aria-label="Division void requests">
  <?php if ($flash): ?>
    <div class="dw-alert dw-alert--<?= $flash['type'] === 'success' ? 'success' : 'error' ?>" role="status">
      <?= yn_icon($flash['type'] === 'success' ? 'check' : 'info') ?>
      <span><?= $e($flash['message']) ?></span>
    </div>
  <?php endif; ?>

  <?php if (!$recipient): ?>
    <div class="dw-alert dw-alert--warning" role="status">
      <?= yn_icon('info') ?>
      <span>An active Zonal Treasurer is required before a void request can be submitted.</span>
    </div>
  <?php endif; ?>

  <div class="dw-summary-grid dw-summary-grid--three" aria-label="Void request summary">
    <?php foreach ($summaryCards as $card): ?>
      <?php require __DIR__ . '/../partials/divisional/summary-card.view.php'; ?>
    <?php endforeach; ?>
  </div>

  <div class="dw-toolbar" aria-label="Void request tools">
    <div class="dw-toolbar__search dw-search dw-search--plain">
      <label class="visually-hidden" for="void-request-search">Search ledger entries and requests</label>
      <input id="void-request-search" type="search" placeholder="Search by entry, description, or reason" data-void-search>
    </div>
    <button class="dw-button dw-button--secondary" type="button" data-filter-toggle aria-controls="void-request-filters" aria-expanded="false">Filters</button>
    <button class="dw-button dw-button--primary" type="button" data-modal-open="new-void-request"<?= (!$recipient || !$entries) ? ' disabled' : '' ?>>New Void Request</button>
  </div>

  <section class="dw-filter-panel" id="void-request-filters" hidden>
    <h2 class="dw-filter-panel__heading">Advanced Filters for Void Requests</h2>
    <div class="dw-filter-grid">
      <div class="dw-field">
        <label for="void-status-filter">Status</label>
        <select id="void-status-filter" data-void-status-filter>
          <option value="">All statuses</option>
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
          <option value="withdrawn">Withdrawn</option>
        </select>
      </div>
      <div class="dw-field">
        <label for="void-type-filter">Ledger type</label>
        <select id="void-type-filter" data-void-type-filter>
          <option value="">All types</option>
          <option value="income">Income</option>
          <option value="expense">Expense</option>
        </select>
      </div>
      <div class="dw-field">
        <label for="void-min-amount">Minimum amount</label>
        <input id="void-min-amount" type="number" min="0" step="0.01" data-void-min-amount>
      </div>
      <div class="dw-field">
        <label for="void-max-amount">Maximum amount</label>
        <input id="void-max-amount" type="number" min="0" step="0.01" data-void-max-amount>
      </div>
      <div class="dw-field">
        <label for="void-date-from">Date from</label>
        <input id="void-date-from" type="date" data-void-date-from>
      </div>
      <div class="dw-field">
        <label for="void-date-to">Date to</label>
        <input id="void-date-to" type="date" data-void-date-to>
      </div>
      <div class="dw-field">
        <label for="void-sort">Sort by</label>
        <select id="void-sort" data-void-sort>
          <option value="newest">Date (newest)</option>
          <option value="oldest">Date (oldest)</option>
          <option value="amount-high">Amount (high to low)</option>
          <option value="amount-low">Amount (low to high)</option>
        </select>
      </div>
      <div class="dw-field">
        <label for="void-recipient">Sent to</label>
        <input id="void-recipient" type="text" value="<?= $e($recipient ? $recipient->zonal_name . ' Zonal Treasurer' : 'Not assigned') ?>" disabled>
      </div>
    </div>
    <div class="dw-filter-actions">
      <button class="dw-button dw-button--secondary" type="button" data-void-filter-reset>Reset all</button>
      <button class="dw-button dw-button--primary" type="button" data-void-filter-apply>Apply filters</button>
    </div>
  </section>

  <section class="dw-panel" aria-labelledby="eligible-entries-title">
    <header class="dw-panel__header">
      <div>
        <h2 id="eligible-entries-title">Division Ledger - Eligible Entries</h2>
        <p>Approved entries without a pending void request</p>
      </div>
      <span class="dw-count" data-eligible-count><?= count($entries) ?> <?= count($entries) === 1 ? 'entry' : 'entries' ?></span>
    </header>
    <div class="dw-table-wrap">
      <table class="dw-table">
        <thead><tr><th>Entry ID</th><th>Date</th><th>Description</th><th>Amount</th><th>Type</th><th>Action</th></tr></thead>
        <tbody data-eligible-body>
          <?php foreach ($entries as $entry): ?>
            <?php $searchText = strtolower(implode(' ', [$entry->reference_no, $entry->description, $entry->category, $entry->type])); ?>
            <tr data-eligible-row
                data-search="<?= $e($searchText) ?>"
                data-type="<?= $e(strtolower($entry->type)) ?>"
                data-amount="<?= $e($entry->amount) ?>"
                data-date="<?= $e($entry->date) ?>">
              <td class="dw-table__reference"><?= $e($entry->reference_no) ?></td>
              <td><?= $e(date('d M Y', strtotime($entry->date))) ?></td>
              <td class="dw-table__description"><?= $e($entry->description) ?></td>
              <td class="dw-money"><?= $e($money($entry->amount)) ?></td>
              <td><?php $status = $entry->type; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?></td>
              <td>
                <button class="dw-button dw-button--ghost" type="button"
                        data-request-entry="<?= (int) $entry->entry_id ?>"
                        data-modal-open="new-void-request"<?= !$recipient ? ' disabled' : '' ?>>Request Void</button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="dw-empty-state<?= !$entries ? ' is-visible' : '' ?>" data-eligible-empty>
      <span class="dw-empty-state__icon" aria-hidden="true"><?= yn_icon('file') ?></span>
      <strong>No eligible entries found</strong>
      <p>Approved entries appear here when they do not have a pending void request.</p>
    </div>
  </section>

  <section class="dw-panel" aria-labelledby="sent-requests-title">
    <header class="dw-panel__header">
      <div>
        <h2 id="sent-requests-title">Sent Void Requests - Status</h2>
        <p>Requests submitted from <?= $e($division->division_name) ?> to the Zonal Treasurer</p>
      </div>
      <span class="dw-count" data-request-count><?= count($requests) ?> <?= count($requests) === 1 ? 'request' : 'requests' ?></span>
    </header>
    <div class="dw-table-wrap">
      <table class="dw-table">
        <thead><tr><th>Request ID</th><th>Ledger Entry</th><th>Reason</th><th>Sent To</th><th>Date Sent</th><th>Status</th><th>Action</th></tr></thead>
        <tbody data-request-body>
          <?php foreach ($requests as $request): ?>
            <?php
            $sentTo = trim((string) $request->recipient_name) ?: (($request->zonal_name ?: 'Zonal') . ' Treasurer');
            $searchText = strtolower(implode(' ', [
                $requestReference($request->void_request_id),
                $request->reference_no,
                $request->description,
                $request->reason,
                $sentTo,
                $request->status,
            ]));
            ?>
            <tr data-request-row
                data-search="<?= $e($searchText) ?>"
                data-status="<?= $e(strtolower($request->status)) ?>"
                data-type="<?= $e(strtolower($request->type)) ?>"
                data-amount="<?= $e($request->amount) ?>"
                data-date="<?= $e(substr((string) $request->requested_at, 0, 10)) ?>">
              <td class="dw-table__reference"><?= $e($requestReference($request->void_request_id)) ?></td>
              <td><?= $e($request->reference_no) ?> - <?= $e($money($request->amount)) ?></td>
              <td class="dw-table__description"><?= $e($request->reason) ?></td>
              <td><?= $e($sentTo) ?></td>
              <td><?= $e(date('d M Y', strtotime($request->requested_at))) ?></td>
              <td><?php $status = $request->status; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?></td>
              <td>
                <button class="dw-button dw-button--ghost" type="button"
                        data-request-status
                        data-request-id="<?= (int) $request->void_request_id ?>"
                        data-request-reference="<?= $e($requestReference($request->void_request_id)) ?>"
                        data-entry-reference="<?= $e($request->reference_no) ?>"
                        data-entry-description="<?= $e($request->description) ?>"
                        data-amount-label="<?= $e($money($request->amount)) ?>"
                        data-reason="<?= $e($request->reason) ?>"
                        data-recipient="<?= $e($sentTo) ?>"
                        data-status-label="<?= $e($request->status) ?>"
                        data-can-withdraw="<?= (int) $request->requested_by === (int) $currentUserId ? '1' : '0' ?>"
                        data-requested-at="<?= $e(date('d M Y, H:i', strtotime($request->requested_at))) ?>"
                        data-decided-at="<?= $request->decided_at ? $e(date('d M Y, H:i', strtotime($request->decided_at))) : '' ?>"
                        data-remarks="<?= $e($request->remarks ?: '') ?>"
                        data-modal-open="void-request-status">View Status</button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="dw-empty-state<?= !$requests ? ' is-visible' : '' ?>" data-request-empty>
      <span class="dw-empty-state__icon" aria-hidden="true"><?= yn_icon('file') ?></span>
      <strong>No void requests found</strong>
      <p>Submitted requests and their review status appear here.</p>
    </div>
  </section>
</section>

<div class="dw-modal" id="new-void-request" role="dialog" aria-modal="true" aria-labelledby="new-void-request-title" aria-hidden="true" hidden>
  <div class="dw-modal__backdrop" data-modal-close></div>
  <form class="dw-modal__dialog" action="<?= ROOT ?>/divisionalvoidrequest/create" method="post">
    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
    <header class="dw-modal__header">
      <h2 id="new-void-request-title">New Void Request to Zonal Treasurer</h2>
      <button class="dw-modal__close" type="button" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
    </header>
    <div class="dw-modal__body">
      <div class="dw-field dw-field--span-2">
        <label for="void-entry">Ledger entry</label>
        <select id="void-entry" name="entry_id" required data-void-entry-select>
          <option value="">Select an approved ledger entry</option>
          <?php foreach ($entries as $entry): ?>
            <option value="<?= (int) $entry->entry_id ?>"><?= $e($entry->reference_no . ' - ' . $entry->description . ' - ' . $money($entry->amount)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="dw-detail-box dw-field--span-2">
        <span>Sent to</span>
        <strong><?= $e($recipient ? $recipient->zonal_name . ' Zonal Treasurer' : 'No active Zonal Treasurer assigned') ?></strong>
      </div>
      <div class="dw-field dw-field--span-2">
        <label for="void-reason">Reason for void</label>
        <textarea id="void-reason" name="reason" minlength="10" maxlength="1000" required placeholder="Explain why this ledger entry should be voided"></textarea>
        <small>Provide enough detail for the Zonal Treasurer to review the request.</small>
      </div>
      <div class="dw-alert dw-alert--warning dw-field--span-2">
        <?= yn_icon('lock') ?>
        <span>The ledger entry cannot be edited while this request is pending.</span>
      </div>
    </div>
    <footer class="dw-modal__footer">
      <button class="dw-button dw-button--secondary" type="button" data-modal-close>Cancel</button>
      <button class="dw-button dw-button--primary" type="submit"<?= !$recipient ? ' disabled' : '' ?>>Submit to Zonal</button>
    </footer>
  </form>
</div>

<div class="dw-modal" id="void-request-status" role="dialog" aria-modal="true" aria-labelledby="void-request-status-title" aria-hidden="true" hidden>
  <div class="dw-modal__backdrop" data-modal-close></div>
  <div class="dw-modal__dialog">
    <header class="dw-modal__header">
      <h2 id="void-request-status-title">Void Request Status</h2>
      <button class="dw-modal__close" type="button" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
    </header>
    <div class="dw-modal__body">
      <dl class="dw-review-list dw-field--span-2">
        <div><dt>Request</dt><dd data-status-request-reference></dd></div>
        <div><dt>Ledger entry</dt><dd data-status-entry></dd></div>
        <div><dt>Description</dt><dd data-status-description></dd></div>
        <div><dt>Reason</dt><dd data-status-reason></dd></div>
        <div><dt>Sent to</dt><dd data-status-recipient></dd></div>
        <div><dt>Status</dt><dd data-status-state></dd></div>
        <div data-status-remarks-row hidden><dt>Remarks</dt><dd data-status-remarks></dd></div>
      </dl>
      <ol class="dw-status-timeline dw-field--span-2" aria-label="Request progress">
        <li class="is-complete"><strong>Submitted to Zonal</strong><span data-status-submitted-date></span></li>
        <li data-status-review-step><strong>Under Zonal Review</strong><span data-status-review-copy></span></li>
        <li data-status-decision-step><strong>Decision Recorded</strong><span data-status-decision-copy></span></li>
      </ol>
    </div>
    <footer class="dw-modal__footer">
      <form method="post" action="" data-withdraw-form data-withdraw-base="<?= ROOT ?>/divisionalvoidrequest/withdraw/" hidden>
        <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
        <button class="dw-button dw-button--secondary" type="submit" data-confirm="Withdraw this pending void request?">Withdraw Request</button>
      </form>
      <button class="dw-button dw-button--primary" type="button" data-modal-close>Close</button>
    </footer>
  </div>
</div>
<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
