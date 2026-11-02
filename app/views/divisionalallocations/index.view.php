<?php
require_once __DIR__ . '/../partials/icons.view.php';

$e = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$money = static fn($value) => 'LKR ' . number_format((float) $value, 2);
$fmtDate = static fn($date) => $date ? date('M j, Y', strtotime($date)) : '—';
$title = 'Allocate Funds - YouthNexus';
$pageTitle = 'Allocate Funds';
$pageDescription = 'Review club requests and allocate funds from the division ledger';
$currentRoute = 'divisionalallocations';

$pageStyles = [
    ROOT . '/assets/css/fundtransfer.css?v=20261102',
    ROOT . '/assets/css/divisional-workflows.css',
    ROOT . '/assets/css/divisional-tabs.css',
    ROOT . '/assets/css/divisional-allocations.css?v=20261102f',
];
$pageScripts = [
    ROOT . '/assets/js/divisional-workflows.js',
    ROOT . '/assets/js/divisional-pagination.js',
    ROOT . '/assets/js/divisional-allocations.js',
    ROOT . '/assets/js/divisional-tabs.js',
];

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<div class="fund-transfer-module divisional-allocations-module">

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

    <!-- Top Action Row (Above Cards, Right Corner) -->
    <div class="ft-top-actions-row">
        <a class="dw-button dw-button--secondary" href="<?= ROOT ?>/divisionalallocations/accounts" id="btnManageBankAccounts">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="21" width="18" height="2" rx="1"/><polygon points="12 2 2 7 22 7"/><line x1="5" y1="10" x2="5" y2="18"/><line x1="10" y1="10" x2="10" y2="18"/><line x1="14" y1="10" x2="14" y2="18"/><line x1="19" y1="10" x2="19" y2="18"/></svg>
            Manage Bank Accounts
        </a>
        <a class="dw-button dw-button--secondary" href="<?= ROOT ?>/divisionalledger/exportcsv" id="btnDownloadLedger">
            <?= yn_icon('download') ?>
            Download Ledger
        </a>
        <button class="dw-button dw-button--primary db-primary-action" type="button" id="btnOpenCreateModal" data-modal-open="new-allocation"<?= $sourceAccount ? '' : ' disabled' ?>>
            <?= yn_icon('plus') ?>
            New Fund Allocation
        </button>
    </div>

    <!-- Stat Cards Row (4 Cards) -->
    <div class="ft-cards-row ft-cards-row--four" aria-label="Allocation summary">
        <!-- 1. Division Ledger Balance -->
        <div class="ft-stat-card">
            <div class="ft-stat-head">
                <span class="ft-stat-title">Division Ledger Balance</span>
                <span class="ft-badge ft-badge-blue"><?= $e($division->division_name) ?></span>
            </div>
            <div class="ft-stat-big"><?= $e($money($summary['balance'])) ?></div>
            <div class="ft-stat-sub">Available balance for club disbursement</div>
        </div>

        <!-- 2. Disbursed This Quarter -->
        <div class="ft-stat-card">
            <div class="ft-stat-head">
                <span class="ft-stat-title">Disbursed This Quarter</span>
                <span class="ft-badge ft-badge-blue"><?= $e($summary['quarter_label'] ?? ('Q4 ' . date('Y'))) ?></span>
            </div>
            <div class="ft-stat-big"><?= $e($money($summary['quarter_allocated'] ?? 0)) ?></div>
            <div class="ft-stat-sub">Completed disbursements in <?= $e($summary['quarter_label'] ?? 'current quarter') ?></div>
        </div>

        <!-- 3. Total Allocations -->
        <div class="ft-stat-card">
            <div class="ft-stat-head">
                <span class="ft-stat-title">Completed Allocations</span>
                <span class="ft-badge ft-badge-blue"><?= date('Y') ?> FY</span>
            </div>
            <div class="ft-stat-big"><?= (int) ($summary['total_allocations'] ?? 0) ?></div>
            <div class="ft-stat-sub">Total disbursements to <?= count($clubs) ?> clubs</div>
        </div>

        <!-- 4. Pending Club Requests -->
        <div class="ft-stat-card" data-tab-target="pending">
            <div class="ft-stat-head">
                <span class="ft-stat-title">Pending Club Requests</span>
                <?php if ($summary['pending'] > 0): ?>
                    <span class="ft-badge ft-badge-yellow">Needs Review</span>
                <?php else: ?>
                    <span class="ft-badge ft-badge-blue">Up to Date</span>
                <?php endif; ?>
            </div>
            <div class="ft-stat-big"><?= (int) $summary['pending'] ?></div>
            <div class="ft-stat-sub">Club funding requests awaiting review</div>
        </div>
    </div>

    <!-- Search & Filters Toolbar (Only searchbar and filter button) -->
    <div class="dw-toolbar dw-toolbar--search-only" aria-label="Allocation tools">
        <div class="dw-toolbar__search dw-search dw-search--plain">
            <label class="visually-hidden" for="allocation-search">Search allocations</label>
            <input id="allocation-search" type="search" placeholder="Search by club, reference, purpose, or category" data-allocation-search>
        </div>
        <button class="dw-button dw-button--secondary" type="button" data-filter-toggle aria-controls="allocation-filters" aria-expanded="false">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            Filters
        </button>
    </div>

    <!-- Filter Panel matching other Divisional Treasurer modules -->
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
                <label for="allocation-category-filter">Fund Category</label>
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
                <label for="allocation-min-amount">Minimum Amount (LKR)</label>
                <input id="allocation-min-amount" type="number" min="0" step="0.01" placeholder="0.00" data-allocation-min-amount>
            </div>
            <div class="dw-field">
                <label for="allocation-max-amount">Maximum Amount (LKR)</label>
                <input id="allocation-max-amount" type="number" min="0" step="0.01" placeholder="Unlimited" data-allocation-max-amount>
            </div>
            <div class="dw-field">
                <label for="allocation-date-from">Date From</label>
                <input id="allocation-date-from" type="date" data-allocation-date-from>
            </div>
            <div class="dw-field">
                <label for="allocation-date-to">Date To</label>
                <input id="allocation-date-to" type="date" data-allocation-date-to>
            </div>
        </div>
        <div class="dw-filter-actions">
            <button class="dw-button dw-button--secondary" type="button" data-allocation-filter-reset>Reset all</button>
            <button class="dw-button dw-button--primary" type="button" data-allocation-filter-apply>Apply filters</button>
        </div>
    </section>

    <!-- Tab Container -->
    <div class="dv-tabs-container" data-tab-container>
        <div class="dv-tablist" role="tablist" aria-label="Fund allocation sections">
            <button class="dv-tab" type="button" role="tab" id="tab-pending" data-tab="pending" aria-controls="panel-pending" aria-selected="true" tabindex="0">
                <span>Pending Requests</span>
                <span class="dv-tab__badge<?= count($pendingRequests) > 0 ? ' dv-tab__badge--attention' : '' ?>" data-tab-count="pending" data-tab-attention><?= count($pendingRequests) ?></span>
            </button>
            <button class="dv-tab" type="button" role="tab" id="tab-history" data-tab="history" aria-controls="panel-history" aria-selected="false" tabindex="-1">
                <span>Allocation History</span>
                <span class="dv-tab__badge" data-tab-count="history"><?= count($history) ?></span>
            </button>
        </div>

        <!-- Panel 1: Pending Requests -->
        <div class="dv-tabpanel" role="tabpanel" id="panel-pending" aria-labelledby="tab-pending" data-tab="pending">
            <div class="dv-tab-search-hint" data-tab-hint hidden></div>
            <section class="ft-table-card" data-allocation-section="pending">
                <header class="ft-panel-header">
                    <div>
                        <h2>Pending Club Fund Requests</h2>
                        <p>Requests submitted by registered clubs in <?= $e($division->division_name) ?></p>
                    </div>
                    <span class="ft-count-badge" data-allocation-count="pending"><?= count($pendingRequests) ?> pending</span>
                </header>
                <div class="ft-table-wrap">
                    <table class="ft-table" id="pendingAllocationsTable">
                        <thead>
                            <tr>
                                <th>RECIPIENT CLUB</th>
                                <th>PURPOSE &amp; REASON</th>
                                <th>CATEGORY</th>
                                <th>AMOUNT (LKR)</th>
                                <th>REQUESTED DATE</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody id="allocation-pending-tbody" data-allocation-rows="pending">
                            <?php foreach ($pendingRequests as $request): ?>
                                <?php $searchText = strtolower(implode(' ', [$request->club_name, $request->reference_no, $request->purpose_description, $request->fund_category])); ?>
                                <tr data-allocation-row
                                    data-section="pending"
                                    data-filter-match="true"
                                    data-search="<?= $e($searchText) ?>"
                                    data-club="<?= (int) $request->to_id ?>"
                                    data-category="<?= $e(strtolower((string) $request->fund_category)) ?>"
                                    data-status="pendingapproval"
                                    data-amount="<?= $e($request->amount) ?>"
                                    data-date="<?= $e(date('Y-m-d', strtotime($request->created_at))) ?>">
                                    <td class="ft-td-zone">
                                        <strong><?= $e($request->club_name) ?></strong>
                                        <div class="ft-sub-text">Ref: <?= $e($request->reference_no) ?></div>
                                    </td>
                                    <td><?= $e($request->purpose_description) ?></td>
                                    <td><span class="ft-badge ft-badge-blue"><?= $e($request->fund_category ?: 'Uncategorised') ?></span></td>
                                    <td class="ft-td-amount">
                                        <span class="ft-amount-outflow"><?= $e($money($request->amount)) ?></span>
                                    </td>
                                    <td class="ft-td-date"><?= $e($fmtDate($request->created_at)) ?></td>
                                    <td class="ft-td-actions">
                                        <div class="ft-row-actions">
                                            <button class="ft-btn-table-sm ft-btn-allocate" type="button"
                                                data-review-allocation
                                                data-modal-open="review-allocation"
                                                data-action="<?= ROOT ?>/divisionalallocations/decide/<?= (int) $request->allocation_id ?>"
                                                data-club-name="<?= $e($request->club_name) ?>"
                                                data-reference="<?= $e($request->reference_no) ?>"
                                                data-purpose="<?= $e($request->purpose_description) ?>"
                                                data-category-label="<?= $e($request->fund_category ?: 'Uncategorised') ?>"
                                                data-amount-label="<?= $e($money($request->amount)) ?>"
                                                data-requested-label="<?= $e($fmtDate($request->created_at)) ?>">
                                                Allocate
                                            </button>
                                            <form action="<?= ROOT ?>/divisionalallocations/decide/<?= (int) $request->allocation_id ?>" method="post" data-reject-allocation-form>
                                                <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                                                <input type="hidden" name="decision" value="reject">
                                                <button class="ft-btn-table-sm ft-btn-reject" type="submit">Reject</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="ft-empty-table-state<?= count($pendingRequests) === 0 ? ' is-visible' : '' ?>" data-empty-state>
                    <div class="ft-empty-table-icon" aria-hidden="true"><?= yn_icon('file') ?></div>
                    <h3 class="ft-empty-table-title">No pending requests found</h3>
                    <p class="ft-empty-table-desc">There are no club fund requests matching the current search and filters.</p>
                </div>

                <nav class="yn-pagination" data-yn-pagination data-yn-page-target="allocation-pending-tbody" data-yn-page-size="10"></nav>
            </section>
        </div>

        <!-- Panel 2: Allocation History -->
        <div class="dv-tabpanel" role="tabpanel" id="panel-history" aria-labelledby="tab-history" data-tab="history">
            <div class="dv-tab-search-hint" data-tab-hint hidden></div>
            <section class="ft-table-card" data-allocation-section="history">
                <header class="ft-panel-header">
                    <div>
                        <h2>Allocation History</h2>
                        <p>Reviewed and completed disbursements to division clubs</p>
                    </div>
                    <span class="ft-count-badge" data-allocation-count="history"><?= count($history) ?> records</span>
                </header>
                <div class="ft-table-wrap">
                    <table class="ft-table" id="historyAllocationsTable">
                        <thead>
                            <tr>
                                <th>DATE &amp; REF</th>
                                <th>RECIPIENT CLUB</th>
                                <th>CATEGORY</th>
                                <th>METHOD &amp; ACCOUNT</th>
                                <th>AMOUNT (LKR)</th>
                                <th>STATUS</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody id="allocation-history-tbody" data-allocation-rows="history">
                            <?php foreach ($history as $allocation): ?>
                                <?php
                                $searchText = strtolower(implode(' ', [$allocation->club_name, $allocation->reference_no, $allocation->purpose_description, $allocation->fund_category]));
                                $isCompleted = $allocation->status === 'Completed';
                                $isPending = $allocation->status === 'PendingApproval' || $allocation->status === 'Processing';
                                $isRejected = $allocation->status === 'Rejected' || $allocation->status === 'Failed';
                                $badgeClass = $isCompleted ? 'ft-badge-green' : ($isRejected ? 'ft-badge-red' : 'ft-badge-yellow');
                                $methodLabel = $allocation->disbursement_method === 'RTGS' ? 'Bank Transfer (RTGS)' : 'Cheque / SLIPS';
                                ?>
                                <tr data-allocation-row
                                    data-section="history"
                                    data-filter-match="true"
                                    data-search="<?= $e($searchText) ?>"
                                    data-club="<?= (int) $allocation->to_id ?>"
                                    data-category="<?= $e(strtolower((string) $allocation->fund_category)) ?>"
                                    data-status="<?= $e(strtolower($allocation->status)) ?>"
                                    data-amount="<?= $e($allocation->amount) ?>"
                                    data-date="<?= $e($allocation->transfer_date) ?>">
                                    <td class="ft-td-date">
                                        <strong><?= $e($fmtDate($allocation->transfer_date)) ?></strong>
                                        <div class="ft-sub-text"><?= $e($allocation->reference_no) ?></div>
                                    </td>
                                    <td class="ft-td-zone">
                                        <strong><?= $e($allocation->club_name) ?></strong>
                                    </td>
                                    <td>
                                        <span class="ft-badge ft-badge-blue"><?= $e($allocation->fund_category ?: 'General') ?></span>
                                    </td>
                                    <td class="ft-td-method">
                                        <?= $e($methodLabel) ?>
                                        <div class="ft-sub-text"><?= $sourceAccount ? $e($sourceAccount->account_label) : 'Divisional Fund' ?></div>
                                    </td>
                                    <td class="ft-td-amount">
                                        <span class="ft-amount-outflow"><?= $e($money($allocation->amount)) ?></span>
                                    </td>
                                    <td>
                                        <span class="ft-badge <?= $badgeClass ?>">&bull; <?= $e($allocation->status) ?></span>
                                    </td>
                                    <td class="ft-td-actions">
                                        <div class="ft-row-actions">
                                            <button type="button" class="ft-link-btn ft-btn-view-details"
                                                data-id="<?= (int) $allocation->allocation_id ?>"
                                                data-ref="<?= $e($allocation->reference_no) ?>"
                                                data-status="<?= $e($allocation->status) ?>"
                                                data-amount="<?= number_format((float) $allocation->amount, 2) ?>"
                                                data-datetime="<?= date('Y-m-d - h:i A', strtotime($allocation->created_at)) ?>"
                                                data-date="<?= $e($fmtDate($allocation->transfer_date)) ?>"
                                                data-club="<?= $e($allocation->club_name) ?>"
                                                data-category="<?= $e($allocation->fund_category ?: 'General') ?>"
                                                data-bank="<?= $sourceAccount ? $e($sourceAccount->bank_name) : 'Bank of Ceylon' ?>"
                                                data-branch="<?= $sourceAccount ? $e($sourceAccount->branch_name) : 'General Branch' ?>"
                                                data-account="<?= $sourceAccount ? $e($sourceAccount->account_number) : '—' ?>"
                                                data-method="<?= $e($methodLabel) ?>"
                                                data-authorized="<?= $e($userName) ?> (Divisional Treasurer)"
                                                data-purpose="<?= $e($allocation->purpose_description) ?>"
                                                id="btnViewAlloc<?= (int) $allocation->allocation_id ?>">
                                                View Details
                                            </button>
                                            <a href="<?= ROOT ?>/divisionalallocations/receipt/<?= (int) $allocation->allocation_id ?>" class="ft-link-btn" target="_blank">
                                                Receipt
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="ft-empty-table-state<?= count($history) === 0 ? ' is-visible' : '' ?>" data-empty-state>
                    <div class="ft-empty-table-icon" aria-hidden="true"><?= yn_icon('file') ?></div>
                    <h3 class="ft-empty-table-title">No allocation history found</h3>
                    <p class="ft-empty-table-desc">Create an allocation or change the current search and filters.</p>
                </div>

                <nav class="yn-pagination" data-yn-pagination data-yn-page-target="allocation-history-tbody" data-yn-page-size="10"></nav>
            </section>
        </div>
    </div>

</div>

<!-- =========================================================================
     POPUP MODAL 1: New Fund Allocation (Zonal Style)
     ========================================================================= -->
<div class="ft-overlay" id="fundAllocationModal" hidden aria-hidden="true" role="dialog" aria-labelledby="allocModalTitle">
    <div class="ft-popup divisional-allocations-module" id="fundAllocationPopup">
        <div class="ft-top-strip"></div>

        <div class="ft-popup-header">
            <div class="ft-popup-title">
                <div class="ft-header-icon" aria-hidden="true"></div>
                <div>
                    <h1 id="allocModalTitle">New Fund Allocation</h1>
                    <p>Transfer funds to club ledger</p>
                </div>
            </div>
            <button type="button" class="ft-close-btn" id="btnCloseAllocModal" data-modal-close aria-label="Close dialog">&times;</button>
        </div>

        <form method="POST" action="<?= ROOT ?>/divisionalallocations/create" id="fundAllocationForm" class="ft-popup-body" data-allocation-form>
            <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
            <input type="hidden" name="disbursement_method" id="selectedMethodInput" value="RTGS">

            <div id="allocErrorAlert" class="ft-alert ft-alert-error" hidden></div>

            <!-- Target Recipient Club -->
            <div class="ft-field">
                <label for="alloc_club_id">Target Club <span class="ft-required">*</span></label>
                <select class="ft-input" name="club_id" id="alloc_club_id" required>
                    <option value="">— Select Target Club —</option>
                    <?php foreach ($clubs as $c): ?>
                        <option value="<?= (int) $c->club_id ?>"><?= $e($c->club_name) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="ft-hint">The youth club authorized to receive allocations through this account.</div>
            </div>

            <!-- Paired Settlement Accounts (Paying from -> Crediting to) -->
            <div class="ft-paired-accounts">
                <!-- 1. Sender: Paying From (Division HQ) -->
                <div class="ft-bank-box ft-bank-box-sender">
                    <div class="ft-bank-label">
                        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 8 8 12 12 16"/><line x1="16" y1="12" x2="8" y2="12"/></svg>
                        PAYING FROM (DIVISION HQ)
                    </div>
                    <div class="ft-bank-content-row">
                        <div class="ft-bank-info">
                            <div class="ft-bank-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="21" width="18" height="2" rx="1"/><polygon points="12 2 2 7 22 7"/><line x1="5" y1="10" x2="5" y2="18"/><line x1="10" y1="10" x2="10" y2="18"/><line x1="14" y1="10" x2="14" y2="18"/><line x1="19" y1="10" x2="19" y2="18"/></svg>
                            </div>
                            <div>
                                <h2><?= $sourceAccount ? $e($sourceAccount->bank_name) : 'Bank of Ceylon' ?>
                                    <span class="ft-bank-sub">(<?= $sourceAccount ? $e($sourceAccount->branch_name) : 'Colombo Fort (001)' ?>)</span>
                                </h2>
                                <p>Account: <?= $sourceAccount ? $e($sourceAccount->account_number) : '—' ?> &nbsp;&bull;&nbsp; <?= $sourceAccount ? $e($sourceAccount->account_label) : ($e($division->division_name ?? 'Division') . ' Fund') ?></p>
                            </div>
                        </div>
                        <span class="ft-verified-badge">Verified Core Account</span>
                    </div>
                    <input type="hidden" name="bank_account_id" value="<?= (int)($sourceAccount->bank_account_id ?? 1) ?>">
                </div>

                <!-- 2. Recipient: Crediting To (Club Recipient Account) -->
                <div class="ft-bank-box ft-bank-box-recipient ft-state-empty" id="recipientAccountBox">
                    <div class="ft-bank-label">
                        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 16 16 12 12 8"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                        CREDITING TO (CLUB RECIPIENT)
                    </div>

                    <!-- State A: No club selected yet -->
                    <div class="ft-bank-empty" id="recipientEmptyState">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span>Select a target club above to load registered recipient account.</span>
                    </div>

                    <!-- State B: Verified Account loaded -->
                    <div class="ft-bank-content-row" id="recipientLoadedState" style="display: none;">
                        <div class="ft-bank-info">
                            <div class="ft-bank-icon ft-bank-icon-recipient" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                            </div>
                            <div>
                                <h2 id="recipientBankName">— <span class="ft-bank-sub" id="recipientBranchName">—</span></h2>
                                <p>Account: <strong id="recipientAccountMasked" class="ft-account-code">—</strong> &nbsp;&bull;&nbsp; <span id="recipientAccountLabel">—</span></p>
                            </div>
                        </div>
                        <span class="ft-verified-badge" id="recipientBadge">Verified Club Account</span>
                    </div>

                    <!-- State C: Amber Warning when club has no verified account -->
                    <div class="ft-bank-warning" id="recipientWarningState" style="display: none;">
                        <div class="ft-warning-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        </div>
                        <div class="ft-warning-text">
                            <strong>No verified bank account registered for this club.</strong>
                            <p>An official commercial receiving account must be registered before tranches can be dispatched. You cannot enter an account number inline.</p>
                            <a href="<?= ROOT ?>/divisionalallocations/accounts" class="ft-warning-link" target="_blank">
                                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                Register Club Bank Account in Management &rarr;
                            </a>
                        </div>
                    </div>

                    <input type="hidden" name="recipient_bank_account_id" id="alloc_recipient_bank_account_id" value="">
                </div>
            </div>

            <!-- Amount + Date Row -->
            <div class="ft-form-row">
                <div class="ft-field">
                    <label for="alloc_amount">Disbursement Amount (LKR) <span class="ft-required">*</span></label>
                    <div class="ft-amount-wrap">
                        <span class="ft-currency">LKR</span>
                        <input type="number" min="0.01" max="9999999999999.99" step="0.01" class="ft-input ft-amount-input" name="amount" id="alloc_amount" placeholder="500,000.00" required>
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
                    <input type="text" class="ft-input" name="reference_no" id="alloc_reference" value="<?= $e($nextReference ?? 'DFA-' . date('Y') . '-0001') ?>" required>
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

            <!-- Fund Category Row -->
            <div class="ft-field">
                <label for="alloc_category">Fund Category <span class="ft-required">*</span></label>
                <select class="ft-input" name="fund_category" id="alloc_category" required>
                    <option value="">— Select Fund Category —</option>
                    <?php 
                    $defaultCategories = ['Sports Development', 'Digital Skills', 'Cultural Arts', 'Leadership Training', 'General Youth Programs', 'Community Service'];
                    $allCategories = array_unique(array_merge($defaultCategories, $categories ?? []));
                    foreach ($allCategories as $cat): ?>
                        <option value="<?= $e($cat) ?>"><?= $e($cat) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="ft-hint">Designate the fund utilization category for ledger audit</div>
            </div>

            <!-- Purpose & Description -->
            <div class="ft-field">
                <label for="alloc_purpose">Purpose &amp; Description <span class="ft-required">*</span></label>
                <textarea class="ft-input ft-textarea" name="purpose_description" id="alloc_purpose" rows="3" placeholder="e.g. Q4 Zonal Youth Club Grants &amp; Vocational Development Training Facility Support" maxlength="2000" required></textarea>
            </div>
        </form>

        <div class="ft-popup-footer">
            <div class="ft-authorizer">
                Authorizing as: <strong><?= $e($userName ?? 'Theresa Perera') ?></strong> (Divisional Treasurer)
            </div>
            <div class="ft-footer-buttons">
                <button type="button" class="ft-btn ft-btn-cancel" id="btnCancelAllocModal" data-modal-close>Cancel</button>
                <button type="submit" form="fundAllocationForm" class="ft-btn ft-btn-authorize" id="btnSubmitAlloc"<?= $sourceAccount ? '' : ' disabled' ?>>
                    Authorize Transfer
                </button>
            </div>
        </div>

    </div>
</div>
<!-- =========================================================================
     POPUP MODAL 2: Review Fund Request (Zonal Style)
     ========================================================================= -->
<div class="ft-overlay" id="review-allocation" hidden aria-hidden="true" role="dialog" aria-labelledby="reviewModalTitle">
    <div class="ft-popup divisional-allocations-module" id="reviewAllocationPopup">
        <div class="ft-top-strip"></div>

        <div class="ft-popup-header">
            <div class="ft-popup-title">
                <div class="ft-header-icon" aria-hidden="true"></div>
                <div>
                    <h1 id="reviewModalTitle">Review Fund Request</h1>
                    <p>Confirm allocation from the division ledger to the requested club</p>
                </div>
            </div>
            <button type="button" class="ft-close-btn" data-modal-close aria-label="Close dialog">&times;</button>
        </div>

        <!-- Amount Band -->
        <div class="ft-amount-band">
            <div>
                <div class="ft-amount-band-label">REQUESTED ALLOCATION AMOUNT</div>
                <div class="ft-amount-band-value" data-review-amount>LKR 0.00</div>
            </div>
            <div class="ft-amount-band-datetime">
                <span>Submitted: </span><strong data-review-requested>—</strong>
            </div>
        </div>

        <form method="POST" action="" id="reviewAllocationForm" class="ft-popup-body" data-review-allocation-form>
            <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
            <input type="hidden" name="decision" value="approve">

            <!-- Clear Structured Table -->
            <div class="ft-review-table-wrap">
                <table class="ft-review-table">
                    <tbody>
                        <tr>
                            <th scope="row">Recipient Club</th>
                            <td><strong class="ft-review-club-name" data-review-club>—</strong></td>
                        </tr>
                        <tr>
                            <th scope="row">Reference Number</th>
                            <td><span class="ft-ref-chip ft-review-chip" data-review-reference>—</span></td>
                        </tr>
                        <tr>
                            <th scope="row">Disbursement Amount</th>
                            <td><strong class="ft-amount-outflow ft-review-amount-text" data-review-amount>—</strong></td>
                        </tr>
                        <tr>
                            <th scope="row">Fund Category</th>
                            <td><span class="ft-badge ft-badge-blue" data-review-category>—</span></td>
                        </tr>
                        <tr>
                            <th scope="row">Requested Date</th>
                            <td data-review-requested>—</td>
                        </tr>
                        <tr>
                            <th scope="row">Purpose &amp; Description</th>
                            <td class="ft-review-purpose-cell" data-review-purpose>—</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="ft-alert ft-alert-warning" style="margin-bottom: 0;">
                <?= yn_icon('info') ?>
                <span>Approving this request will immediately deduct the amount from the division ledger and credit the club ledger.</span>
            </div>
        </form>

        <div class="ft-popup-footer">
            <div class="ft-authorizer">
                Authorizing as: <strong><?= $e($userName) ?></strong> (Divisional Treasurer)
            </div>
            <div class="ft-footer-buttons">
                <button type="button" class="ft-btn ft-btn-cancel" data-modal-close>Cancel</button>
                <button type="submit" form="reviewAllocationForm" class="ft-btn ft-btn-authorize">
                    Confirm Allocation
                </button>
            </div>
        </div>

    </div>
</div>

<!-- =========================================================================
     POPUP MODAL 3: Transaction Details (Zonal Style)
     ========================================================================= -->
<div class="ft-overlay" id="transferDetailsModal" hidden aria-hidden="true" role="dialog" aria-labelledby="dtModalTitle">
    <div class="ft-popup ft-popup-wide divisional-allocations-module" id="transferDetailsPopup">
        <div class="ft-top-strip"></div>

        <div class="ft-popup-header ft-details-header">
            <div class="ft-header-left">
                <h1 id="dtModalTitle">Disbursement Details</h1>
                <span class="ft-ref-chip" id="dtModalRef">DFA-2026-0001</span>
                <span class="ft-status-chip" id="dtModalStatusChip">Completed</span>
                <p id="dtModalSubtitle">Club fund disbursement details</p>
            </div>
            <button type="button" class="ft-close-btn ft-details-close" id="btnCloseDetailsModal" data-modal-close aria-label="Close dialog">&times;</button>
        </div>

        <div class="ft-amount-band">
            <div>
                <div class="ft-amount-band-label">DISBURSEMENT AMOUNT</div>
                <div class="ft-amount-band-value" id="dtModalAmount">LKR 0.00</div>
            </div>
            <div class="ft-amount-band-datetime" id="dtModalDatetime">—</div>
        </div>

        <div class="ft-details-grid">
            <div class="ft-detail-info-card">
                <h2>Recipient &amp; Allocation</h2>
                <div class="ft-detail-row">
                    <span class="ft-dk">Recipient Club</span>
                    <span class="ft-dv" id="dtModalClub">—</span>
                </div>
                <div class="ft-detail-row">
                    <span class="ft-dk">Division</span>
                    <span class="ft-dv"><?= $e($division->division_name) ?></span>
                </div>
                <div class="ft-detail-row">
                    <span class="ft-dk">Fund Category</span>
                    <span class="ft-dv" id="dtModalCategory">—</span>
                </div>
                <div class="ft-detail-row">
                    <span class="ft-dk">Transfer Date</span>
                    <span class="ft-dv" id="dtModalDate">—</span>
                </div>
            </div>

            <div class="ft-detail-info-card">
                <h2>Payment &amp; Source Account</h2>
                <div class="ft-detail-row">
                    <span class="ft-dk">Reference No.</span>
                    <span class="ft-dv" id="dtModalRef2">—</span>
                </div>
                <div class="ft-detail-row">
                    <span class="ft-dk">Payment Method</span>
                    <span class="ft-dv" id="dtModalMethod">—</span>
                </div>
                <div class="ft-detail-row">
                    <span class="ft-dk">Source Bank</span>
                    <span class="ft-dv" id="dtModalBank">—</span>
                </div>
                <div class="ft-detail-row">
                    <span class="ft-dk">Source Account</span>
                    <span class="ft-dv" id="dtModalAccount">—</span>
                </div>
                <div class="ft-detail-row">
                    <span class="ft-dk">Authorized By</span>
                    <span class="ft-dv" id="dtModalAuthorized">—</span>
                </div>
            </div>
        </div>

        <div class="ft-purpose-wrap">
            <h2>Purpose &amp; Description</h2>
            <div class="ft-purpose-box" id="dtModalPurpose">—</div>
        </div>

        <div class="ft-popup-footer ft-details-footer">
            <button type="button" class="ft-btn ft-btn-close" id="btnCloseDetailsModal2" data-modal-close>Close</button>
            <a href="#" class="ft-btn ft-btn-download" id="btnReceiptDownload" target="_blank">
                Print Receipt / Save PDF
            </a>
        </div>

    </div>
</div>

<script>
    window.YouthNexusDivisionalAllocations = {
        rootUrl: <?= json_encode(ROOT) ?>,
        csrfToken: <?= json_encode($csrfToken) ?>,
        clubAccounts: <?= json_encode($clubAccounts ?? []) ?>,
    };
</script>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
