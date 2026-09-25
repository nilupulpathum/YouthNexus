<?php
/**
 * Division Void Requests — zonal review queue for division-originated voids.
 * Approve/reject decisions post to the existing backend contract.
 * UI follows the divisional standard (dw-* classes + shared partials).
 */
$e = static function ($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';

$title = 'Division Void Requests - YouthNexus';
$pageTitle = 'Division Void Requests';
$pageDescription = 'Pending void requests from divisions under Gampaha Zone.';
$currentRoute = 'zonaltreasurer/voids';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css'];
$pageScripts = [ROOT . '/assets/js/divisional-workflows.js', ROOT . '/assets/js/zonal.js'];

$summaryCards = [
    ['value' => (string) ($pendingCount ?? 0), 'label' => 'Pending review', 'note' => 'Awaiting zonal decision', 'icon' => 'clock', 'tone' => 'amber'],
    ['value' => (string) ($divisionCount ?? 0) . ' divisions', 'label' => 'Queue scope', 'note' => 'Divisions in this zone', 'icon' => 'users', 'tone' => 'blue'],
];

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<section class="dw-page" aria-labelledby="zonal-voids-heading">
    <h1 id="zonal-voids-heading" class="visually-hidden">Division void requests</h1>

    <?php if (!empty($flash)): ?><div class="dw-alert dw-alert--<?= ($flash['type'] ?? '') === 'success' ? 'success' : 'error' ?>" role="status"><?= $e($flash['message'] ?? '') ?></div><?php endif; ?>

    <div class="dw-summary-grid dw-summary-grid--two" aria-label="Void queue summary">
        <?php foreach ($summaryCards as $card): ?>
            <?php require __DIR__ . '/../partials/divisional/summary-card.view.php'; ?>
        <?php endforeach; ?>
    </div>

    <div class="dw-toolbar" aria-label="Void queue tools">
        <a class="dw-button dw-button--secondary" href="<?= ROOT ?>/zonaltreasurer/exportvoids"><?= yn_icon('download') ?> Export</a>
    </div>

    <form method="get" action="<?= ROOT ?>/zonaltreasurer/voids" class="dw-filter-panel" role="search" aria-label="Filter void requests">
        <h2 class="dw-filter-panel__heading">Filter Void Requests</h2>
        <div class="dw-filter-grid">
            <div class="dw-field">
                <label for="zonal-void-search">Search requests</label>
                <input id="zonal-void-search" type="search" name="search" value="<?= $e($search) ?>" placeholder="Search division or reference">
            </div>
            <div class="dw-field">
                <label for="zonal-void-status">Status</label>
                <select id="zonal-void-status" name="status"><option value="Pending" <?= $status === 'Pending' ? 'selected' : '' ?>>Pending</option><option value="Approved" <?= $status === 'Approved' ? 'selected' : '' ?>>Approved</option><option value="Rejected" <?= $status === 'Rejected' ? 'selected' : '' ?>>Rejected</option><option value="" <?= $status === '' ? 'selected' : '' ?>>All statuses</option></select>
            </div>
        </div>
        <div class="dw-filter-actions">
            <button type="submit" class="dw-button dw-button--primary">Apply filters</button>
        </div>
    </form>

    <section class="dw-panel" aria-labelledby="zonal-void-queue-heading">
        <header class="dw-panel__header">
            <div>
                <h2 id="zonal-void-queue-heading">Division void request queue</h2>
                <p>Only division-originated requests appear here. Club requests are reviewed at the divisional tier.</p>
            </div>
            <span class="dw-count"><?= count($requests) ?> <?= count($requests) === 1 ? 'request' : 'requests' ?></span>
        </header>
        <div class="dw-table-wrap">
            <table class="dw-table">
                <thead>
                    <tr>
                        <th>Division</th>
                        <th>Transaction</th>
                        <th>Amount</th>
                        <th>Requested</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $request): ?>
                        <tr>
                            <td><strong><?= $e($request['division']) ?></strong><br><span class="dw-table__reference"><?= $e($request['id']) ?></span></td>
                            <td><?= $e($request['description']) ?><br><span class="dw-table__reference"><?= $e($request['reference']) ?></span></td>
                            <td class="dw-money">LKR <?= number_format($request['amount'], 2) ?></td>
                            <td><?= $e($request['requested_on']) ?></td>
                            <td><?= $e($request['reason']) ?></td>
                            <td><?php $status = $request['status']; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?></td>
                            <td>
                                <?php if ($request['status'] === 'Pending'): ?>
                                    <div class="dw-row-actions">
                                        <button type="button" class="dw-button dw-button--ghost" data-review="<?= $e($request['id']) ?>" data-division="<?= $e($request['division']) ?>" data-reference="<?= $e($request['reference']) ?>">Review</button>
                                    </div>
                                <?php elseif (!empty($request['remark'])): ?>
                                    <span class="dw-muted-copy"><?= $e($request['remark']) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        $emptyTitle = 'No division void requests match these filters';
        $emptyMessage = 'Try changing the current search or filters.';
        $emptyVisible = count($requests) === 0;
        require __DIR__ . '/../partials/divisional/empty-state.view.php';
        ?>
    </section>
</section>

<div id="void-review" class="dw-modal" role="dialog" aria-modal="true" aria-labelledby="void-review-title" aria-hidden="true" hidden>
    <div class="dw-modal__backdrop" data-modal-close></div>
    <div class="dw-modal__dialog">
        <header class="dw-modal__header">
            <div>
                <p>Zonal Treasurer decision</p>
                <h2 id="void-review-title">Review void request</h2>
            </div>
            <button type="button" class="dw-modal__close" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button>
        </header>
        <div class="dw-modal__body">
            <p id="void-review-reference"></p>
            <form id="void-decision-form" method="post" data-base-action="<?= ROOT ?>/zonaltreasurer/">
                <input type="hidden" name="csrf_token" value="<?= $e($csrf_token) ?>">
                <input id="void-review-id" type="hidden" name="void_id">
                <div class="dw-field dw-field--span-2">
                    <label for="void-remark">Decision remark</label>
                    <textarea id="void-remark" name="remark" rows="4" maxlength="1000" required></textarea>
                </div>
            </form>
            <div class="dw-alert dw-alert--warning" role="note"><strong>Decision effect</strong><p>Approval voids the requested divisional transaction in the future backend. Rejection leaves it unchanged. The division receives the decision remark.</p></div>
        </div>
        <footer class="dw-modal__footer">
            <button type="button" class="dw-button dw-button--secondary" data-modal-close>Cancel</button>
            <button type="submit" class="dw-button dw-button--secondary" form="void-decision-form" data-decision="rejectvoid">Reject</button>
            <button type="submit" class="dw-button dw-button--primary" form="void-decision-form" data-decision="approvevoid">Approve void</button>
        </footer>
    </div>
</div>


<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
