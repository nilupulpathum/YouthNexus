<?php
/**
 * Zonal Ledger — shell placeholder.
 * Full C8 running-balance shape at zonal scope lands in Z9.
 */
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<section class="club-page" aria-labelledby="zonal-ledger-heading">
    <h1 id="zonal-ledger-heading" class="sr-only">Zonal ledger</h1>

    <p class="club-back-row"><a class="club-btn-secondary" href="<?= ROOT ?>/zonaltreasurer"><span aria-hidden="true">‹</span> Treasurer Overview</a></p>

    <section class="club-panel" aria-labelledby="zonal-ledger-panel-heading">
        <div class="club-panel-header">
            <div>
                <p class="club-eyebrow">Gampaha Zone</p>
                <h2 id="zonal-ledger-panel-heading">Zonal ledger</h2>
            </div>
        </div>

        <p class="club-muted">Review zonal income, expenses, and running balance.</p>
    </section>
</section>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/club.css">

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
