<?php
require_once __DIR__ . '/../partials/icons.view.php';
$e = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$money = static fn($value) => 'Rs. ' . number_format((float) $value, 2);
$title = 'Allocation Details - YouthNexus';
$pageTitle = 'Fund Allocation Details';
$pageDescription = 'Review the club disbursement, settlement account, and authorization record';
$currentRoute = 'divisionalallocations';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css', ROOT . '/assets/css/divisional-allocations.css'];
$methodLabel = $allocation->disbursement_method === 'RTGS' ? 'Bank Transfer / RTGS' : 'Cheque / SLIPS';
require __DIR__ . '/../layouts/dashboard-start.view.php';
?>
<section class="dw-page da-details" aria-label="Fund allocation details">
  <div class="da-back-row">
    <a class="dw-button dw-button--ghost db-view-button db-view-button--back" href="<?= ROOT ?>/divisionalallocations">Back to Allocations</a>
  </div>

  <article class="dw-panel da-voucher">
    <header class="da-voucher__header">
      <div>
        <div class="da-voucher__badges">
          <span class="da-reference"><?= $e($allocation->reference_no) ?></span>
          <?php $status = $allocation->status; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?>
        </div>
        <h2><?= $e($allocation->club_name) ?></h2>
        <p>Allocation from <?= $e($allocation->division_name) ?> divisional ledger</p>
      </div>
      <div class="da-voucher__amount"><span>Transfer amount</span><strong><?= $e($money($allocation->amount)) ?></strong><small><?= $e(date('d M Y', strtotime($allocation->transfer_date))) ?></small></div>
    </header>

    <div class="da-voucher__body">
      <section class="da-info-card">
        <h3>Recipient and allocation</h3>
        <dl class="dw-review-list">
          <div><dt>Target club</dt><dd><?= $e($allocation->club_name) ?></dd></div>
          <div><dt>Fund category</dt><dd><?= $e($allocation->fund_category ?: 'Uncategorised') ?></dd></div>
          <div><dt>Reference</dt><dd><?= $e($allocation->reference_no) ?></dd></div>
          <div><dt>Created</dt><dd><?= $e(date('d M Y, H:i', strtotime($allocation->created_at))) ?></dd></div>
        </dl>
      </section>

      <section class="da-info-card">
        <h3>Settlement and authorization</h3>
        <dl class="dw-review-list">
          <div><dt>Method</dt><dd><?= $e($methodLabel) ?></dd></div>
          <div><dt>Source bank</dt><dd><?= $e($allocation->bank_name ?: 'Not recorded') ?></dd></div>
          <div><dt>Account</dt><dd><?= $e($allocation->account_number ?: 'Not recorded') ?></dd></div>
          <div><dt>Authorized by</dt><dd><?= $e(trim((string) $allocation->authorized_by_name) ?: 'Divisional Treasurer') ?></dd></div>
        </dl>
      </section>

      <section class="da-purpose">
        <h3>Purpose and description</h3>
        <p><?= nl2br($e($allocation->purpose_description)) ?></p>
      </section>
    </div>

    <footer class="da-voucher__footer">
      <a class="dw-button dw-button--secondary db-close-action" href="<?= ROOT ?>/divisionalallocations">Close</a>
      <a class="dw-button dw-button--primary" href="<?= ROOT ?>/divisionalallocations/receipt/<?= (int) $allocation->allocation_id ?>" target="_blank" rel="noopener"><?= yn_icon('download') ?> Print / Save Receipt</a>
    </footer>
  </article>
</section>
<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
