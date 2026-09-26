<?php
/**
 * Direct View: Fund Transfer Details
 * Renders the transfer details within the dashboard layout if accessed directly.
 */
$title                   = $title ?? 'Transfer Details — YouthNexus';
$pageTitle               = 'Transaction Details: ' . htmlspecialchars($transfer->reference_no);
$pageDescription         = 'Fund disbursement transaction voucher and audit record';
$currentRoute            = 'fundtransfer';
$unreadNotificationCount = 0;
$pageStyles              = [ROOT . '/assets/css/fundtransfer.css'];

require __DIR__ . '/../layouts/dashboard-start.view.php';

$hubDetail = trim(($transfer->target_province ?? '') . ($transfer->target_hub_name ? ' - ' . $transfer->target_hub_name : ''));
$isCompleted = ($transfer->status === 'Completed');
?>


<div class="fund-transfer-module">

    <div style="margin-bottom: 20px;">
        <a href="<?= ROOT ?>/fundtransfer" class="ft-btn ft-btn-outline db-secondary-action">
            &larr; Back to Fund Transfer Ledger
        </a>
    </div>

    <div class="ft-popup ft-popup-wide" style="margin: 0 auto; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;">
        <div class="ft-top-strip"></div>

        <div class="ft-popup-header ft-details-header">
            <div class="ft-header-left">
                <h2>Transaction Details</h2>
                <span class="ft-ref-chip"><?= htmlspecialchars($transfer->reference_no) ?></span>
                <span class="ft-status-chip <?= $isCompleted ? 'ft-status-completed' : 'ft-status-processing' ?>">
                    &#9679; <?= htmlspecialchars($transfer->status) ?>
                </span>
                <p>Fund disbursement for <?= htmlspecialchars($transfer->target_zone_name ?? 'Zonal Office') ?></p>
            </div>
            <a href="<?= ROOT ?>/fundtransfer" class="ft-close-btn ft-details-close" title="Back">&times;</a>
        </div>

        <div class="ft-amount-band">
            <div>
                <div class="ft-amount-band-label">TRANSFER AMOUNT</div>
                <div class="ft-amount-band-value">LKR <?= number_format((float)$transfer->amount, 2) ?></div>
            </div>
            <div class="ft-amount-band-datetime">&#128337; <?= date('Y-m-d - h:i A', strtotime($transfer->created_at)) ?></div>
        </div>

        <div class="ft-details-grid">
            <div class="ft-detail-info-card">
                <h2>&#127974; Recipient &amp; Account</h2>
                <div class="ft-detail-row">
                    <span class="ft-dk">Target Zonal Office</span>
                    <span class="ft-dv"><?= htmlspecialchars($transfer->target_zone_name) ?><?= $hubDetail ? " ({$hubDetail})" : '' ?></span>
                </div>
                <div class="ft-detail-row">
                    <span class="ft-dk">Bank</span>
                    <span class="ft-dv"><?= htmlspecialchars($transfer->bank_name) ?></span>
                </div>
                <div class="ft-detail-row">
                    <span class="ft-dk">Branch</span>
                    <span class="ft-dv"><?= htmlspecialchars($transfer->branch_name) ?></span>
                </div>
                <div class="ft-detail-row">
                    <span class="ft-dk">Account Number</span>
                    <span class="ft-dv">
                        <?= htmlspecialchars($transfer->account_number) ?>
                        <span class="ft-verified-tag">(Verified Core Account)</span>
                    </span>
                </div>
            </div>

            <div class="ft-detail-info-card">
                <h2>&#128196; Payment &amp; Authorization</h2>
                <div class="ft-detail-row">
                    <span class="ft-dk">Reference / Cheque No.</span>
                    <span class="ft-dv"><?= htmlspecialchars($transfer->reference_no) ?></span>
                </div>
                <div class="ft-detail-row">
                    <span class="ft-dk">Payment Method</span>
                    <span class="ft-dv"><?= ($transfer->disbursement_method === 'RTGS') ? 'BOC SLIPS / RTGS Direct' : 'Gov Cheque / SLIPS' ?></span>
                </div>
                <div class="ft-detail-row">
                    <span class="ft-dk">Date of Transfer</span>
                    <span class="ft-dv"><?= date('F j, Y', strtotime($transfer->transfer_date)) ?></span>
                </div>
                <div class="ft-detail-row">
                    <span class="ft-dk">Authorized By</span>
                    <span class="ft-dv"><?= htmlspecialchars($transfer->authorized_by_name ?? 'NYSC Administrator') ?></span>
                </div>
            </div>
        </div>

        <div class="ft-purpose-wrap">
            <h2>&#128196; Purpose &amp; Description</h2>
            <div class="ft-purpose-box">
                <?= nl2br(htmlspecialchars($transfer->purpose_description)) ?>
            </div>
        </div>

        <div class="ft-popup-footer ft-details-footer">
            <a href="<?= ROOT ?>/fundtransfer" class="ft-btn ft-btn-close">Close</a>
            <a href="<?= ROOT ?>/fundtransfer/receipt/<?= (int)$transfer->allocation_id ?>" class="ft-btn ft-btn-download" target="_blank">
                &#8681; Download Receipt (PDF)
            </a>
        </div>
    </div>

</div>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
