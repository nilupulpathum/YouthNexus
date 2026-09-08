<?php
/**
 * Club Ledger — C1 shell (read-only summary). Log + void flows land in C8.
 */
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';

$stats        = $stats ?? [];
$transactions = $transactions ?? [];
$can_log      = !empty($can_log);
?>

<section class="club-page" aria-labelledby="club-ledger-heading">
    <h1 id="club-ledger-heading" class="sr-only">Club ledger</h1>

    <div class="club-stat-grid" aria-label="Fund summary">
        <article class="club-stat-card">
            <p class="club-stat-label">Fund balance</p>
            <p class="club-stat-value club-stat-value--small"><?= $escape($stats['balance'] ?? '') ?></p>
        </article>
        <article class="club-stat-card">
            <p class="club-stat-label">Total income</p>
            <p class="club-stat-value club-stat-value--small"><?= $escape($stats['income'] ?? '') ?></p>
        </article>
        <article class="club-stat-card">
            <p class="club-stat-label">Total expenses</p>
            <p class="club-stat-value club-stat-value--small"><?= $escape($stats['expenses'] ?? '') ?></p>
        </article>
    </div>

    <section class="club-panel" aria-labelledby="club-ledger-list-heading">
        <div class="club-panel-header">
            <div>
                <p class="club-eyebrow">Gampaha Youth Development Club</p>
                <h2 id="club-ledger-list-heading">Transactions</h2>
            </div>
        </div>

        <div class="club-table-wrap">
            <table class="club-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Balance</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td><?= $escape($t['date'] ?? '') ?></td>
                            <td><?= $escape($t['description'] ?? '') ?></td>
                            <td><span class="club-pill club-pill--<?= $escape($t['type_key'] ?? 'income') ?>"><?= $escape($t['type'] ?? '') ?></span></td>
                            <td><?= $escape($t['amount'] ?? '') ?></td>
                            <td><?= $escape($t['balance'] ?? '') ?></td>
                            <td><span class="club-pill club-pill--<?= $escape($t['status_key'] ?? 'verified') ?>"><?= $escape($t['status'] ?? '') ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($can_log): ?>
            <p class="club-note">Log-transaction / void actions land in C8 — this shell is read-only.</p>
        <?php endif; ?>
    </section>
</section>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/club.css">

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
