<?php
/**
 * Fund Transfer — NYSC Administration
 * Main dashboard showing transfer ledger, aggregate stats, and reactive popup modals.
 */
$title                   = $title ?? 'Fund Transfer — YouthNexus';
$pageTitle               = $pageTitle ?? 'Fund Transfer';
$pageDescription         = $pageDescription ?? 'NYSC National Administration — Colombo Division & Zonal Ledger';
$transferRoute = $transferRoute ?? 'fundtransfer';
$listRoute = $listRoute ?? $transferRoute;
$isZonalMode = $isZonalMode ?? false;
$currentRoute = $listRoute;
// The controller passes the real unread-announcement count + items; only
// fall back to the legacy hardcode when it did not (e.g. direct renders).
$unreadNotificationCount = $unreadNotificationCount ?? ($isZonalMode ? 2 : 0);
$pageStyles = [ROOT . '/assets/css/fundtransfer.css'];

require __DIR__ . '/../layouts/dashboard-start.view.php';

// Formatters
$fmtLKR  = fn($v) => 'LKR ' . number_format((float)$v, 2);
$fmtDate = fn($d) => $d ? date('Y-m-d', strtotime($d)) : '—';
$fmtDisplayDate = fn($d) => $d ? date('M j, Y', strtotime($d)) : '—';

$qLabel       = $stats['quarter_label'] ?? ('Q4 ' . date('Y'));
$budgetCap    = (float)($stats['budget_cap'] ?? 45000000);
$yearTotal    = (float)($stats['year_total'] ?? 0);
$remBudget    = (float)($stats['remaining_budget'] ?? max(0, $budgetCap - $yearTotal));
$utilizedPct  = (int)($stats['utilized_pct'] ?? 0);
$coreAccount  = $stats['core_account'] ?? null;

// Filter states
$activeSearch  = htmlspecialchars($filters['search'] ?? '', ENT_QUOTES);
$activeZone    = (int)($filters['zone_id'] ?? 0);
$activeStatus  = $filters['status'] ?? 'All';
$activeQuarter = $filters['quarter'] ?? '';

// Flash session
$old    = $_SESSION['form_old']    ?? [];
$errors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_old'], $_SESSION['form_errors']);
?>


<div class="fund-transfer-module">

    <!-- Top Action Bar / Page Subtitle -->
    <div class="ft-header-bar">
        <div class="ft-header-titles">
            <h2 class="ft-section-title"><?= $isZonalMode ? 'Zonal Fund Distribution &amp; Division Ledger' : 'National Fund Disbursement &amp; Zonal Ledger' ?></h2>
            <p class="ft-section-desc"><?= $isZonalMode ? 'Distribute zone funds received to its divisions. Allocations post to both ledgers.' : 'Manage inter-governmental grants, RTGS clearance, and zonal treasury allocations.' ?></p>
        </div>
        <div class="ft-header-actions">
            <a href="<?= ROOT ?>/<?= htmlspecialchars($transferRoute) ?>/exportledger?<?= http_build_query($filters) ?>" class="ft-btn ft-btn-outline" id="btnDownloadLedger" title="Export Ledger to CSV">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Download Ledger
            </a>
            <button type="button" class="ft-btn ft-btn-primary" id="btnOpenCreateModal" aria-haspopup="dialog">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                New Fund Allocation
            </button>
        </div>
    </div>

    <!-- Stat Cards (4 Cards from fundTransfer.php) -->
    <div class="ft-cards-row">
        <!-- 1. Fiscal Window -->
        <div class="ft-stat-card">
            <div class="ft-stat-head">
                <span class="ft-stat-title"><?= htmlspecialchars(strtoupper($qLabel)) ?> FISCAL WINDOW</span>
            </div>
            <div class="ft-stat-big"><?= $fmtLKR($stats['quarter_total'] ?? 0) ?></div>
            <div class="ft-stat-sub">Total across <?= count($zones) ?> <?= $isZonalMode ? 'Divisions' : 'Zonal Offices' ?></div>
        </div>

        <!-- 2. In-flight Transfers -->
        <div class="ft-stat-card">
            <div class="ft-stat-head">
                <span class="ft-stat-title">IN-FLIGHT CLEARANCE</span>
                <?php if (($stats['in_flight_count'] ?? 0) > 0): ?>
                    <span class="ft-badge ft-badge-yellow">Needs Action</span>
                <?php endif; ?>
            </div>
            <div class="ft-stat-big">
                <?= (int)($stats['in_flight_count'] ?? 0) ?>
                <span class="ft-stat-unit">Transfers in-flight</span>
            </div>
            <div class="ft-stat-sub"><?= $fmtLKR($stats['in_flight_total'] ?? 0) ?> awaiting settlement</div>
        </div>

        <!-- 3. Budget Cap & Utilization -->
        <div class="ft-stat-card ft-stat-card-budget">
            <div class="ft-stat-head">
                <span class="ft-stat-title"><?= $isZonalMode ? 'NYSC FUNDS RECEIVED' : 'ANNUAL BUDGET' ?> <?= date('Y') ?></span>
                <span class="ft-badge ft-badge-blue"><?= $utilizedPct ?>% Utilized</span>
            </div>
            <div class="ft-stat-big"><?= $fmtLKR($budgetCap) ?></div>
            <div class="ft-stat-sub">Available: <?= $fmtLKR($remBudget) ?></div><div class="ft-stat-sub">Cap: LKR <?= number_format($budgetCap / 1000000, 1) ?>M &nbsp;&bull;&nbsp; Rem: LKR <?= number_format($remBudget / 1000000, 1) ?>M</div>
            <div class="ft-progress-wrap" title="<?= $utilizedPct ?>% Utilized">
                <div class="ft-progress-bar" style="width: <?= min(100, $utilizedPct) ?>%;"></div>
            </div>
        </div>

        <!-- 4. Core Bank Account Gateway -->
        <div class="ft-stat-card ft-stat-card-bank">
            <div class="ft-stat-head">
                <span class="ft-stat-title">CORE SETTLEMENT ACCOUNT</span>
                <span class="ft-badge ft-badge-green">Active</span>
            </div>
            <div class="ft-stat-big"><?= htmlspecialchars($coreAccount->bank_name ?? 'Bank of Ceylon') ?></div>
            <div class="ft-stat-sub"><?= htmlspecialchars($coreAccount->branch_name ?? 'Colombo Fort Direct API Gateway (001)') ?></div>
            <div class="ft-stat-detail"><?= htmlspecialchars($coreAccount->gateway_type ?? 'RTGS') ?> / SLIPS Connected</div>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="<?= ROOT ?>/<?= htmlspecialchars($listRoute) ?>" id="ftFilterForm" class="ft-filter-bar">
        <div class="ft-search-box-wrap">
            <input type="text" name="search" class="ft-filter-input" placeholder="Search recipient, reference, or purpose..." value="<?= htmlspecialchars($activeSearch ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <svg class="ft-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </div>

        <select name="zone_id" class="ft-filter-select" id="ftFilterZone">
            <option value=""><?= $isZonalMode ? 'All Divisions' : 'All Zones' ?> (<?= count($zones) ?>)</option>
            <?php foreach ($zones as $z): ?>
                <option value="<?= (int)$z->zonal_id ?>" <?= $activeZone === (int)$z->zonal_id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($z->zonal_name) ?><?= !empty($z->province) ? ' — ' . htmlspecialchars($z->province) : '' ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="quarter" class="ft-filter-select" id="ftFilterQuarter">
            <option value="">All Quarters (current: <?= htmlspecialchars($qLabel) ?>)</option>
            <option value="Q1" <?= $activeQuarter === 'Q1' ? 'selected' : '' ?>>Quarter 1 (Q1 <?= date('Y') ?>)</option>
            <option value="Q2" <?= $activeQuarter === 'Q2' ? 'selected' : '' ?>>Quarter 2 (Q2 <?= date('Y') ?>)</option>
            <option value="Q3" <?= $activeQuarter === 'Q3' ? 'selected' : '' ?>>Quarter 3 (Q3 <?= date('Y') ?>)</option>
            <option value="Q4" <?= $activeQuarter === 'Q4' ? 'selected' : '' ?>>Quarter 4 (Q4 <?= date('Y') ?>)</option>
        </select>

        <select name="status" class="ft-filter-select" id="ftFilterStatus">
            <option value="All" <?= $activeStatus === 'All' ? 'selected' : '' ?>>All Statuses</option>
            <option value="Processing" <?= $activeStatus === 'Processing' ? 'selected' : '' ?>>Processing</option>
            <option value="Completed" <?= $activeStatus === 'Completed' ? 'selected' : '' ?>>Completed</option>
            <option value="Failed" <?= $activeStatus === 'Failed' ? 'selected' : '' ?>>Failed</option>
        </select>

        <button type="submit" class="ft-btn ft-btn-sm ft-btn-filter">Filter</button>
        <?php if (!empty($activeSearch) || $activeZone > 0 || $activeStatus !== 'All' || !empty($activeQuarter)): ?>
            <a href="<?= ROOT ?>/<?= htmlspecialchars($listRoute) ?>" class="ft-btn ft-btn-sm ft-btn-clear">Reset</a>
        <?php endif; ?>
    </form>

    <!-- Transfers Table -->
    <div class="ft-table-card">
        <table class="ft-table" id="transfersTable">
            <thead>
                <tr>
                    <th>DATE &amp; REF</th>
                    <th><?= $isZonalMode ? 'TARGET DIVISION' : 'TARGET ZONAL OFFICE' ?></th>
                    <th>METHOD &amp; ACCOUNT</th>
                    <th>AMOUNT (LKR)</th>
                    <th>STATUS</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transfers)): ?>
                    <tr>
                        <td colspan="6" class="ft-empty-td">
                            <div class="ft-empty-state">
                            <span class="ft-empty-icon" aria-hidden="true"></span>
                                <h3>No fund transfers match your criteria</h3>
                                <p>Try adjusting your search terms or filters above, or authorize a new allocation.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transfers as $t):
                        $isProcessing = ($t->status === 'Processing');
                        $methodText   = ($t->disbursement_method === 'RTGS')
                            ? ('BOC Direct / RTGS - ' . substr($t->account_number ?? '0000', -4))
                            : ('Gov Cheque / SLIPS - ' . substr($t->reference_no, -6));
                        $hubDetail    = trim(($t->target_province ?? '') . ($t->target_hub_name ? ' - ' . $t->target_hub_name : ''));
                    ?>
                    <tr class="ft-row" id="rowAlloc<?= (int)$t->allocation_id ?>">
                        <td class="ft-td-date">
                            <strong class="ft-val-date"><?= $fmtDate($t->transfer_date) ?></strong>
                            <div class="ft-val-ref"><?= htmlspecialchars($t->reference_no) ?></div>
                        </td>
                        <td class="ft-td-zone">
                            <strong class="ft-val-zone"><?= htmlspecialchars($t->target_zone_name ?? 'Zonal Office') ?></strong>
                            <div class="ft-val-sub"><?= htmlspecialchars($hubDetail ?: 'Zonal Administrative Hub') ?></div>
                        </td>
                        <td class="ft-td-method">
                            <div class="ft-val-method"><?= htmlspecialchars($methodText) ?></div>
                            <div class="ft-val-sub" title="<?= htmlspecialchars($t->purpose_description) ?>">
                                <?= htmlspecialchars(mb_strimwidth($t->purpose_description, 0, 42, '…')) ?>
                            </div>
                        </td>
                        <td class="ft-td-amount">
                            <span class="ft-amount-outflow">- LKR <?= number_format((float)$t->amount, 2) ?></span>
                        </td>
                        <td class="ft-td-status">
                            <?php if ($isProcessing): ?>
                                <span class="ft-badge ft-badge-yellow">Processing</span>
                            <?php elseif ($t->status === 'Completed'): ?>
                                <span class="ft-badge ft-badge-green">Completed</span>
                            <?php else: ?>
                                <span class="ft-badge ft-badge-red"><?= htmlspecialchars($t->status) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="ft-td-actions">
                            <button type="button" class="ft-link-btn ft-btn-view-details"
                                data-id="<?= (int)$t->allocation_id ?>"
                                data-ref="<?= htmlspecialchars($t->reference_no, ENT_QUOTES) ?>"
                                data-status="<?= htmlspecialchars($t->status, ENT_QUOTES) ?>"
                                data-amount="<?= number_format((float)$t->amount, 2) ?>"
                                data-datetime="<?= date('Y-m-d - h:i A', strtotime($t->created_at)) ?>"
                                data-date="<?= date('F j, Y', strtotime($t->transfer_date)) ?>"
                                data-zone="<?= htmlspecialchars(($t->target_zone_name ?? '') . ($hubDetail ? " ({$hubDetail})" : ''), ENT_QUOTES) ?>"
                                data-bank="<?= htmlspecialchars($t->bank_name ?? 'Bank of Ceylon', ENT_QUOTES) ?>"
                                data-branch="<?= htmlspecialchars($t->branch_name ?? 'Colombo Fort', ENT_QUOTES) ?>"
                                data-account="<?= htmlspecialchars($t->account_number ?? '', ENT_QUOTES) ?>"
                                data-method="<?= htmlspecialchars(($t->disbursement_method === 'RTGS') ? 'BOC SLIPS / RTGS Direct' : 'Gov Cheque / SLIPS', ENT_QUOTES) ?>"
                                data-authorized="<?= htmlspecialchars($t->authorized_by_name ? $t->authorized_by_name . ' (' . ($t->authorizer_role ?? 'NYSC Admin') . ')' : 'NYSC Administrator', ENT_QUOTES) ?>"
                                data-purpose="<?= htmlspecialchars($t->purpose_description, ENT_QUOTES) ?>"
                                id="btnViewAlloc<?= (int)$t->allocation_id ?>">
                                View Details
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Table Footer -->
        <div class="ft-table-footer">
            <div class="ft-footer-count">
                Showing <strong><?= count($transfers) ?></strong> recorded <?= $isZonalMode ? 'division' : 'zonal' ?> transfer<?= count($transfers) !== 1 ? 's' : '' ?> in FY <?= date('Y') ?>
            </div>
            <div class="ft-pages">
                <span class="ft-page-link ft-page-active">1</span>
            </div>
        </div>
    </div>

</div>

<!-- =========================================================================
     POPUP MODAL 1: New Fund Allocation (from fundAllocation.php)
     ========================================================================= -->
<div class="ft-overlay" id="fundAllocationModal" hidden aria-hidden="true" role="dialog" aria-labelledby="allocModalTitle">
    <div class="ft-popup" id="fundAllocationPopup">

        <!-- Popup Header -->
        <div class="ft-popup-header">
            <div class="ft-popup-title">
                <div class="ft-header-icon" aria-hidden="true"></div>
                <div>
                    <h1 id="allocModalTitle">New Fund Allocation</h1>
                    <p><?= $isZonalMode ? 'Transfer zone funds to a division' : 'Transfer funds to zonal office ledger' ?></p>
                </div>
            </div>
            <button type="button" class="ft-close-btn" id="btnCloseAllocModal" aria-label="Close dialog">&times;</button>
        </div>

        <!-- Popup Form Body -->
        <form method="POST" action="<?= ROOT ?>/<?= htmlspecialchars($transferRoute) ?>/create" id="fundAllocationForm" class="ft-popup-body">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES) ?>">
            <input type="hidden" name="disbursement_method" id="selectedMethodInput" value="RTGS">

            <div id="allocErrorAlert" class="ft-alert ft-alert-error" hidden></div>

            <!-- Target recipient -->
            <div class="ft-field">
                <label for="alloc_zone_id"><?= $isZonalMode ? 'Target Division' : 'Target Zonal Office' ?> <span class="ft-required">*</span></label>
                <select class="ft-input" name="zone_id" id="alloc_zone_id" required>
                    <option value="">— Select <?= $isZonalMode ? 'Target Division' : 'Target Zonal Office' ?> —</option>
                    <?php foreach ($zones as $z): ?>
                        <option value="<?= (int)$z->zonal_id ?>">
                            <?= htmlspecialchars($z->zonal_name) ?><?= !empty($z->province) ? ' — ' . htmlspecialchars($z->province) : '' ?><?= !empty($z->hub_name) ? ' (' . htmlspecialchars($z->hub_name) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Core Bank Account Card -->
            <div class="ft-bank-box">
                <div class="ft-bank-info">
                    <div class="ft-bank-icon" aria-hidden="true"></div>
                    <div>
                        <h2><?= htmlspecialchars($coreAccount->bank_name ?? 'Bank of Ceylon') ?>
                            <span class="ft-bank-sub">(<?= htmlspecialchars($coreAccount->branch_name ?? 'Colombo Fort 001') ?>)</span>
                        </h2>
                        <p>Account: <?= htmlspecialchars($coreAccount->account_number ?? '8620-0012-3456-7890') ?> &nbsp;&bull;&nbsp; <?= htmlspecialchars($coreAccount->account_label ?? 'NYSC Colombo Operational Fund') ?></p>
                    </div>
                </div>
                <input type="hidden" name="bank_account_id" value="<?= (int)($coreAccount->bank_account_id ?? 1) ?>">
                <span class="ft-verified-badge"><?= $isZonalMode ? 'Verified Zonal Account' : 'Verified Core Account' ?></span>
            </div>

            <!-- Amount + Date Row -->
            <div class="ft-form-row">
                <div class="ft-field">
                    <label for="alloc_amount">Disbursement Amount (LKR) <span class="ft-required">*</span></label>
                    <div class="ft-amount-wrap">
                        <span class="ft-currency">LKR</span>
                        <input type="text" class="ft-input ft-amount-input" name="amount" id="alloc_amount" placeholder="500,000.00" required>
                    </div>
                    <div class="ft-hint" id="alloc_amount_words">Enter disbursement sum in Sri Lankan Rupees</div>
                </div>
                <div class="ft-field">
                    <label for="alloc_date">Transfer Date <span class="ft-required">*</span></label>
                    <input type="date" class="ft-input" name="transfer_date" id="alloc_date" value="<?= date('Y-m-d') ?>" required>
                    <div class="ft-hint">Value date for central settlement</div>
                </div>
            </div>

            <!-- Reference + Method Row -->
            <div class="ft-form-row">
                <div class="ft-field">
                    <label for="alloc_reference">Reference / Cheque No. <span class="ft-required">*</span></label>
                    <input type="text" class="ft-input" name="reference" id="alloc_reference" value="<?= htmlspecialchars($nextReference) ?>" required>
                </div>
                <div class="ft-field">
                    <label>Disbursement Method <span class="ft-required">*</span></label>
                    <div class="ft-methods">
                        <button type="button" class="ft-method-btn ft-method-selected" data-method="RTGS" id="btnMethodRTGS">
                            BOC Direct / RTGS
                        </button>
                        <button type="button" class="ft-method-btn" data-method="ChequeSLIPS" id="btnMethodCheque">
                            Cheque / SLIPS
                        </button>
                    </div>
                </div>
            </div>

            <!-- Purpose & Description -->
            <div class="ft-field">
                <label for="alloc_purpose">Purpose &amp; Description <span class="ft-required">*</span></label>
                <textarea class="ft-input ft-textarea" name="purpose" id="alloc_purpose" rows="3" placeholder="e.g. Q4 Zonal Youth Club Grants &amp; Vocational Development Training Facility Support" required></textarea>
            </div>
        </form>

        <!-- Popup Footer -->
        <div class="ft-popup-footer">
            <div class="ft-authorizer">
                Authorizing as: <strong><?= htmlspecialchars($userName ?? 'N. Fernando') ?></strong> (<?= htmlspecialchars($userDesignation ?? 'Div. Secretary') ?>)
            </div>
            <div class="ft-footer-buttons">
                <button type="button" class="ft-btn ft-btn-cancel" id="btnCancelAllocModal">Cancel</button>
                <button type="submit" form="fundAllocationForm" class="ft-btn ft-btn-authorize" id="btnSubmitAlloc">
                    Authorize Transfer
                </button>
            </div>
        </div>

    </div>
</div>

<!-- =========================================================================
     POPUP MODAL 2: Transaction Details (from transferDetails.php)
     ========================================================================= -->
<div class="ft-overlay" id="transferDetailsModal" hidden aria-hidden="true" role="dialog" aria-labelledby="dtModalTitle">
    <div class="ft-popup ft-popup-wide" id="transferDetailsPopup">
        <div class="ft-top-strip"></div>

        <!-- Details Header -->
        <div class="ft-popup-header ft-details-header">
            <div class="ft-header-left">
                <h1 id="dtModalTitle">Transaction Details</h1>
                <span class="ft-ref-chip" id="dtModalRef">TRF-2026-0048</span>
        <span class="ft-status-chip" id="dtModalStatusChip">Processing</span>
                <p id="dtModalSubtitle">Fund disbursement for Divisional Secretariat Western Province Hub</p>
            </div>
            <button type="button" class="ft-close-btn ft-details-close" id="btnCloseDetailsModal" aria-label="Close dialog">&times;</button>
        </div>

        <!-- Amount Band -->
        <div class="ft-amount-band">
            <div>
                <div class="ft-amount-band-label">TRANSFER AMOUNT</div>
                <div class="ft-amount-band-value" id="dtModalAmount">LKR 750,000.00</div>
            </div>
            <div class="ft-amount-band-datetime" id="dtModalDatetime">2026-10-28 - 10:42 AM</div>
        </div>

        <!-- Two Info Cards Grid -->
        <div class="ft-details-grid">
            <!-- Recipient & Account -->
            <div class="ft-detail-info-card">
                <h2>Recipient &amp; Account</h2>
                <div class="ft-detail-row">
                    <span class="ft-dk"><?= $isZonalMode ? 'Target Division' : 'Target Zonal Office' ?></span>
                    <span class="ft-dv" id="dtModalZone">Colombo Zone</span>
                </div>
                <div class="ft-detail-row">
                    <span class="ft-dk">Bank</span>
                    <span class="ft-dv" id="dtModalBank">Bank of Ceylon</span>
                </div>
                <div class="ft-detail-row">
                    <span class="ft-dk">Branch</span>
                    <span class="ft-dv" id="dtModalBranch">Colombo Fort Branch (001)</span>
                </div>
                <div class="ft-detail-row">
                    <span class="ft-dk">Account Number</span>
                    <span class="ft-dv" id="dtModalAccount">
                        8620-0012-3456-7890
                        <span class="ft-verified-tag">(<?= $isZonalMode ? 'Verified Zonal Account' : 'Verified Core Account' ?>)</span>
                    </span>
                </div>
            </div>

            <!-- Payment & Authorization -->
            <div class="ft-detail-info-card">
                <h2>Payment &amp; Authorization</h2>
                <div class="ft-detail-row">
                    <span class="ft-dk">Reference / Cheque No.</span>
                    <span class="ft-dv" id="dtModalRef2">TRF-2026-0048</span>
                </div>
                <div class="ft-detail-row">
                    <span class="ft-dk">Payment Method</span>
                    <span class="ft-dv" id="dtModalMethod">BOC SLIPS / RTGS Direct</span>
                </div>
                <div class="ft-detail-row">
                    <span class="ft-dk">Date of Transfer</span>
                    <span class="ft-dv" id="dtModalDate">October 28, 2026</span>
                </div>
                <div class="ft-detail-row">
                    <span class="ft-dk">Authorized By</span>
                    <span class="ft-dv" id="dtModalAuthorized">N. Fernando (Divisional Secretariat)</span>
                </div>
            </div>
        </div>

        <!-- Purpose & Description Box -->
        <div class="ft-purpose-wrap">
            <h2>Purpose &amp; Description</h2>
            <div class="ft-purpose-box" id="dtModalPurpose">
                Annual Youth Club Tech Grants &amp; Agro Seed Capital Tranche 2
            </div>
        </div>

        <!-- Details Footer -->
        <div class="ft-popup-footer ft-details-footer">
            <button type="button" class="ft-btn ft-btn-close" id="btnCloseDetailsModal2">Close</button>
            <a href="#" class="ft-btn ft-btn-download" id="btnReceiptDownload" target="_blank">
                Print Receipt / Save PDF
            </a>
        </div>

    </div>
</div>

<script>
    window.YouthNexusFundTransfer = {
        rootUrl: <?= json_encode(ROOT) ?>,
        route: <?= json_encode($transferRoute) ?>,
        listRoute: <?= json_encode($listRoute) ?>,
        csrfToken: <?= json_encode($csrf_token) ?>,
    };
</script>
<script src="<?= ROOT ?>/assets/js/fundtransfer.js" defer></script>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
