<?php
/**
 * Zonal Ledger — session-only transaction records with running balances.
 * Log-transaction modal posts to the existing backend contract.
 * UI follows the divisional standard (dw-* classes + shared partials).
 */
$e = static function ($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';

$title = 'Zonal Ledger - YouthNexus';
$pageTitle = 'Zonal Ledger';
$pageDescription = 'Session-only records with running balances.';
$currentRoute = 'zonaltreasurer/ledger';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css'];
$pageScripts = [ROOT . '/assets/js/divisional-workflows.js', ROOT . '/assets/js/zonal.js'];

$summaryCards = [
    ['value' => 'LKR ' . number_format($balance ?? 0, 2), 'label' => 'Running balance', 'note' => 'Gampaha Zone', 'icon' => 'file', 'tone' => 'blue'],
    ['value' => 'LKR ' . number_format($income ?? 0, 2), 'label' => 'Recorded income', 'note' => 'This session', 'icon' => 'award', 'tone' => 'green'],
    ['value' => 'LKR ' . number_format($expenses ?? 0, 2), 'label' => 'Recorded expenses', 'note' => 'This session', 'icon' => 'clock', 'tone' => 'amber'],
];

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<section class="dw-page" aria-labelledby="zonal-ledger-heading">
    <h1 id="zonal-ledger-heading" class="visually-hidden">Zonal ledger</h1>

    <?php if (!empty($flash)): ?><div class="dw-alert dw-alert--<?= ($flash['type'] ?? '') === 'success' ? 'success' : 'error' ?>" role="status"><?= $e($flash['message'] ?? '') ?></div><?php endif; ?>

    <div class="dw-summary-grid dw-summary-grid--three" aria-label="Zonal ledger summary">
        <?php foreach ($summaryCards as $card): ?>
            <?php require __DIR__ . '/../partials/divisional/summary-card.view.php'; ?>
        <?php endforeach; ?>
    </div>

    <div class="dw-toolbar" aria-label="Ledger tools">
        <a class="dw-button dw-button--secondary" href="<?= ROOT ?>/zonaltreasurer/exportzonalledger"><?= yn_icon('download') ?> Export</a>
        <button class="dw-button dw-button--primary" type="button" data-modal-open="log-transaction">Log transaction</button>
    </div>

    <section class="dw-panel" aria-labelledby="zonal-ledger-list-heading">
        <header class="dw-panel__header">
            <div>
                <h2 id="zonal-ledger-list-heading">Transaction ledger</h2>
                <p>Session-only records with running balances. Division allocations are recorded through the allocation workflow.</p>
            </div>
            <span class="dw-count"><?= count($entries) ?> <?= count($entries) === 1 ? 'entry' : 'entries' ?></span>
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
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $entry): ?>
                        <tr>
                            <td><?= $e($entry['date']) ?></td>
                            <td><?= $e($entry['description']) ?></td>
                            <td><?php $status = $entry['type']; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?></td>
                            <?php $moneyTone = ($entry['type_key'] ?? '') === 'income' ? ' dw-money--income' : ((($entry['type_key'] ?? '') === 'expense') ? ' dw-money--expense' : ''); ?>
                            <td class="dw-money<?= $moneyTone ?>">LKR <?= number_format($entry['amount'], 2) ?></td>
                            <td class="dw-money">LKR <?= number_format($entry['balance'], 2) ?></td>
                            <td>
                                <?php if (!empty($entry['has_receipt'])): ?>
                                    <div class="dw-row-actions">
                                        <a class="dw-button dw-button--ghost" href="<?= ROOT ?>/financereceipt/show/<?= (int) $entry['id'] ?>" target="_blank" rel="noopener" aria-label="View receipt"><?= yn_icon('download') ?></a>
                                    </div>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td><?php $status = $entry['status']; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        $emptyTitle = 'No ledger entries recorded';
        $emptyMessage = 'Log a transaction to start the session ledger.';
        $emptyVisible = count($entries) === 0;
        require __DIR__ . '/../partials/divisional/empty-state.view.php';
        ?>
    </section>
</section>

<div id="log-transaction" class="dw-modal" role="dialog" aria-modal="true" aria-labelledby="log-title" aria-hidden="true" hidden>
    <div class="dw-modal__backdrop" data-modal-close></div>
    <div class="dw-modal__dialog">
        <header class="dw-modal__header">
            <div>
                <p>Zonal Treasurer action</p>
                <h2 id="log-title">Log transaction</h2>
            </div>
            <button type="button" class="dw-modal__close" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
        </header>
        <div class="dw-modal__body">
                <form id="log-transaction-form" method="post" action="<?= ROOT ?>/zonaltreasurer/logtransaction" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $e($csrf_token) ?>">
                <div class="dw-field">
                    <label for="ledger-type">Transaction type</label>
                    <select id="ledger-type" name="type"><option>Income</option><option>Expense</option></select>
                </div>
                <div class="dw-field">
                    <label for="ledger-amount">Amount (LKR)</label>
                    <input id="ledger-amount" name="amount" type="number" min="0.01" step="0.01" required>
                </div>
                <div class="dw-field">
                    <label for="ledger-date">Date</label>
                    <input id="ledger-date" name="transaction_date" type="date" required>
                </div>
                <div class="dw-field">
                        <label for="ledger-receipt">Receipt (photo or PDF, required)</label>
                        <input id="ledger-receipt" name="receipt" type="file" accept="image/*,.pdf" required>
                </div>
                <div class="dw-field dw-field--span-2">
                    <label for="ledger-description">Description</label>
                    <input id="ledger-description" name="description" maxlength="255" required>
                </div>
            </form>
        </div>
        <footer class="dw-modal__footer">
            <button type="button" class="dw-button dw-button--secondary" data-modal-close>Cancel</button>
            <button type="submit" class="dw-button dw-button--primary" form="log-transaction-form">Record transaction</button>
        </footer>
    </div>
</div>


<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
