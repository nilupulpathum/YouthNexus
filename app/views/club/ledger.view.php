<?php
/**
 * Club Ledger — C8 full UI.
 * Balance header + filterable running-balance table for president and
 * treasurer. Treasurer-only: "Log Transaction" button + modal (income /
 * expense + mandatory receipt upload, blocked-with-error if missing; new
 * entries post as Active with recalculated balance, per the treasurer
 * workflow) and per-row "Request void" action + reason modal (sent to the
 * Divisional Treasurer → row flips to Pending Void; Voided/Disapproved
 * outcomes land with the backend in C13). Presentation-only: no DB writes.
 * UI follows the divisional standard (dw-* classes + shared partials).
 */
$e = static function ($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';

$stats        = $stats ?? [];
$transactions = $transactions ?? [];
$can_log      = !empty($can_log);

$title = 'Club Ledger - YouthNexus';
$pageTitle = 'Club Ledger';
$pageDescription = 'Running balance and transaction history';
$currentRoute = 'club/ledger';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css'];
$pageScripts = [
    ROOT . '/assets/js/divisional-workflows.js',
    ROOT . '/assets/js/club.js',
];

$summaryCards = [
    ['value' => (string) ($stats['balance'] ?? ''), 'label' => 'Fund balance', 'note' => 'Gampaha Youth Development Club', 'icon' => 'wallet', 'tone' => 'blue'],
    ['value' => (string) ($stats['income'] ?? ''), 'label' => 'Total income', 'note' => 'Verified receipts', 'icon' => 'download', 'tone' => 'green'],
    ['value' => (string) ($stats['expenses'] ?? ''), 'label' => 'Total expenses', 'note' => 'Verified payments', 'icon' => 'upload', 'tone' => 'amber'],
];

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<section class="dw-page" aria-labelledby="club-ledger-heading">
    <h1 id="club-ledger-heading" class="visually-hidden">Club ledger</h1>

    <div class="dw-alert dw-alert--success" id="club-toast" role="status" hidden></div>
    <?php if (!empty($flash)): ?>
        <div class="dw-alert dw-alert--<?= ($flash['type'] ?? '') === 'success' ? 'success' : 'error' ?>" role="status">
            <?= $e($flash['message'] ?? '') ?>
        </div>
    <?php endif; ?>

    <div class="dw-summary-grid" aria-label="Fund summary">
        <?php foreach ($summaryCards as $card): ?>
            <?php require __DIR__ . '/../partials/divisional/summary-card.view.php'; ?>
        <?php endforeach; ?>
    </div>

    <div class="dw-toolbar" aria-label="Transaction tools">
        <div class="dw-toolbar__search dw-search dw-search--plain">
            <label class="visually-hidden" for="club-ledger-search">Search transactions</label>
            <input id="club-ledger-search" type="search" placeholder="Search description..." autocomplete="off">
        </div>
        <button class="dw-button dw-button--secondary" type="button" data-filter-toggle aria-controls="club-ledger-filters" aria-expanded="false">Filters</button>
        <?php if ($can_log): ?>
            <button type="button" class="dw-button dw-button--primary" id="club-log-open" data-modal-open="club-log-modal">Log Transaction</button>
        <?php endif; ?>
    </div>

    <section class="dw-filter-panel" id="club-ledger-filters" hidden>
        <h2 class="dw-filter-panel__heading">Filter Transactions</h2>
        <div class="dw-filter-grid">
            <div class="dw-field">
                <label for="club-ledger-type">Filter by type</label>
                <select id="club-ledger-type">
                    <option value="">All types</option>
                    <option value="income">Income</option>
                    <option value="expense">Expense</option>
                </select>
            </div>
            <div class="dw-field">
                <label for="club-ledger-status">Filter by status</label>
                <select id="club-ledger-status">
                    <option value="">All statuses</option>
                    <option value="verified">Verified</option>
                    <option value="pending-void">Pending Void</option>
                    <option value="voided">Voided</option>
                </select>
            </div>
        </div>
    </section>

    <section class="dw-panel" aria-labelledby="club-ledger-list-heading">
        <header class="dw-panel__header">
            <div>
                <h2 id="club-ledger-list-heading">Transactions</h2>
                <p>Gampaha Youth Development Club</p>
            </div>
            <span class="dw-count"><?= count($transactions) ?> <?= count($transactions) === 1 ? 'transaction' : 'transactions' ?></span>
        </header>

        <div class="dw-table-wrap">
            <table class="dw-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Balance</th>
                        <th>Receipt</th>
                        <th>Status</th>
                        <?php if ($can_log): ?>
                            <th>Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody id="club-ledger-body">
                    <?php foreach ($transactions as $t): ?>
                        <tr data-search="<?= $e(strtolower($t['description'] ?? '')) ?>"
                            data-type="<?= $e($t['type_key'] ?? 'income') ?>"
                            data-status="<?= $e($t['status_key'] ?? 'approved') ?>"
                            data-id="<?= (int) ($t['id'] ?? 0) ?>"
                            data-desc="<?= $e($t['description'] ?? '') ?>">
                            <td><?= $e($t['date'] ?? '') ?></td>
                            <td><?= $e($t['description'] ?? '') ?></td>
                            <td><?php $status = $t['type'] ?? ''; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?></td>
                            <td class="dw-money dw-money--<?= $e($t['type_key'] ?? 'income') ?>"><?= $e($t['amount'] ?? '') ?></td>
                            <td class="dw-money"><?= $e($t['balance'] ?? '') ?></td>
                            <td>
                                <?php if (!empty($t['has_receipt'])): ?>
                                    <div class="dw-row-actions">
                                        <a class="dw-button dw-button--ghost" href="<?= ROOT ?>/financereceipt/show/<?= (int) $t['id'] ?>" target="_blank" rel="noopener" aria-label="View receipt"><?= yn_icon('download') ?></a>
                                    </div>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td><?php $status = $t['status'] ?? ''; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?></td>
                            <?php if ($can_log): ?>
                                <td>
                                    <div class="dw-row-actions">
                                        <?php if (!empty($t['voidable'])): ?>
                                            <button type="button" class="dw-button dw-button--ghost" data-action="void" data-modal-open="club-void-modal">Request void</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        $emptyTitle = 'No transactions match these filters';
        $emptyMessage = 'Try changing the current search or filters.';
        $emptyVisible = count($transactions) === 0;
        require __DIR__ . '/../partials/divisional/empty-state.view.php';
        ?>
    </section>
</section>

<?php if ($can_log): ?>
    <div id="club-log-modal" class="dw-modal" role="dialog" aria-modal="true" aria-labelledby="log-modal-title" aria-hidden="true" hidden>
        <div class="dw-modal__backdrop" data-modal-close></div>
        <div class="dw-modal__dialog">
            <header class="dw-modal__header">
                <div>
                    <p>Treasurer action</p>
                    <h2 id="log-modal-title">Log Transaction</h2>
                </div>
                <button type="button" class="dw-modal__close" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
            </header>
            <div class="dw-modal__body">
                <p>New entries post as Active and the balance is recalculated. A receipt upload is mandatory.</p>
                <form id="club-log-form" action="<?= ROOT ?>/club/logTransaction" method="post" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= $e($csrf_token ?? '') ?>">
                    <div id="log-banner" class="dw-alert dw-alert--warning" hidden>
                        <span aria-hidden="true"><?= yn_icon('info') ?></span>
                        <div>
                            <strong>Receipt is required</strong>
                            <p>Every transaction needs a receipt. Please attach a photo or PDF to continue.</p>
                        </div>
                    </div>
                    <div class="dw-field">
                        <label for="log-type">Transaction type</label>
                        <select id="log-type" name="type" required>
                            <option value="Income">Income</option>
                            <option value="Expense">Expense</option>
                        </select>
                    </div>
                    <div class="dw-field">
                        <label for="log-amount">Amount (LKR)</label>
                        <input id="log-amount" name="amount" type="number" required min="1" step="0.01" placeholder="0.00">
                    </div>
                    <div class="dw-field">
                        <label for="log-date">Date</label>
                        <input id="log-date" name="date" type="date" required>
                    </div>
                    <div class="dw-field">
                        <label for="log-receipt">Receipt (photo or PDF)</label>
                        <input id="log-receipt" name="receipt" type="file" accept="image/*,.pdf" required>
                    </div>
                    <div class="dw-field dw-field--span-2">
                        <label for="log-desc">Description</label>
                        <input id="log-desc" name="description" type="text" required maxlength="255" autocomplete="off" placeholder="e.g., Hall hire for workshop">
                    </div>
                    <p id="log-file-name" class="dw-muted-copy" hidden></p>
                    <div class="dw-alert dw-alert--error" id="log-error" role="alert" hidden></div>
                </form>
            </div>
            <footer class="dw-modal__footer">
                <button type="button" class="dw-button dw-button--secondary" data-modal-close>Cancel</button>
                <button type="submit" class="dw-button dw-button--primary" form="club-log-form">Log Transaction</button>
            </footer>
        </div>
    </div>

    <div id="club-void-modal" class="dw-modal" role="dialog" aria-modal="true" aria-labelledby="void-title" aria-hidden="true" hidden>
        <div class="dw-modal__backdrop" data-modal-close></div>
        <div class="dw-modal__dialog">
            <header class="dw-modal__header">
                <div>
                    <p>Treasurer action</p>
                    <h2 id="void-title">Request void</h2>
                </div>
                <button type="button" class="dw-modal__close" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
            </header>
            <div class="dw-modal__body">
                <p id="void-desc" class="dw-muted-copy"></p>
                <form id="club-void-form" action="<?= ROOT ?>/club/requestVoid" method="post" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= $e($csrf_token ?? '') ?>">
                    <input type="hidden" id="void-entry-id" name="entry_id" value="">
                    <div class="dw-field">
                        <label for="void-reason">Reason for voiding (required)</label>
                        <textarea id="void-reason" name="reason" rows="3" placeholder="Explain why this transaction should be voided..."></textarea>
                    </div>
                </form>
                <div class="dw-alert dw-alert--error" id="void-error" role="alert" hidden></div>
                <div role="note">
                    <strong>Where this goes</strong>
                    <p>Void requests go to the Divisional Treasurer. The entry flips to Pending Void until they void it (totals recalculated) or disapprove (entry unchanged).</p>
                </div>
            </div>
            <footer class="dw-modal__footer">
                <button type="button" class="dw-button dw-button--secondary" data-modal-close>Cancel</button>
                <button type="submit" class="dw-button dw-button--primary" form="club-void-form">Send void request</button>
            </footer>
        </div>
    </div>
<?php endif; ?>



<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
