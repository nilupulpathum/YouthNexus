<?php
$escape = static function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$totalBalance = max(0, (float)$income - (float)$expenses);
require __DIR__ . '/../partials/icons.view.php';
$pageScripts = [ROOT . '/assets/js/divisional-workflows.js', ROOT . '/assets/js/zonal.js'];
require __DIR__ . '/../layouts/dashboard-start.view.php';
?>
<link rel="stylesheet" href="<?= ROOT ?>/assets/css/annualaudit.css">

<section class="audit-content" aria-labelledby="zonal-audit-heading">
    <div class="audit-page-head">
        <div><h1 id="zonal-audit-heading">Audit Divisional Finance</h1><p>Gampaha Zone review of divisional reports. NYSC retains final sign-off and ledger-lock authority.</p></div>
        <div class="audit-head-right"><span class="audit-fy-badge">FY <b>2026</b></span><a class="audit-btn audit-btn-light" href="<?= ROOT ?>/zonaltreasurer/exportaudit"><?= yn_icon('download') ?> Export audit summary</a><button class="audit-btn audit-btn-blue" type="button" data-modal-open="flag-modal">Flag discrepancy</button></div>
    </div>

    <?php if ($flash): ?><div class="audit-flash-alert audit-flash-success" role="status"><div><?= $escape($flash) ?></div></div><?php endif; ?>

    <div class="audit-stats" aria-label="Zonal audit summary">
        <article class="audit-stat-card"><h3>Divisional income reviewed</h3><div class="num">LKR <?= number_format((float)$income, 0) ?></div><div class="sub green">Gampaha Zone divisions</div></article>
        <article class="audit-stat-card"><h3>Reported expenses</h3><div class="num">LKR <?= number_format((float)$expenses, 0) ?></div><div class="sub gray">Across <?= count($reports) ?> divisions</div></article>
        <article class="audit-stat-card"><h3>Reported closing balance</h3><div class="num num-amber">LKR <?= number_format($totalBalance, 0) ?></div><div class="sub amber">Subject to report review</div></article>
        <article class="audit-stat-card audit-stat-alert"><h3>Open audit items</h3><div class="attention"><?= $escape($unresolvedCount) ?> require review</div><div class="sub red"><?= $reviewReady ? 'Zonal review can be exported' : 'Resolve or escalate each item' ?></div></article>
    </div>

    <section class="audit-panel" aria-labelledby="division-report-heading">
        <h2 id="division-report-heading">Divisional financial reports</h2>
        <div class="audit-ledger-steps">
            <?php foreach ($reports as $report): ?><article class="audit-step"><div class="step-label"><?= $escape($report['division']) ?></div><div class="step-value blue">LKR <?= number_format((float)$report['balance'], 0) ?></div><div class="step-label">Income <?= number_format((float)$report['income'], 0) ?> · Expenses <?= number_format((float)$report['expenses'], 0) ?></div></article><?php endforeach; ?>
        </div>
        <div class="audit-ledger-result">Review scope is limited to Gampaha, Ja-Ela and Negombo divisions. The Zonal Treasurer cannot sign off or lock a division ledger.</div>
    </section>

    <div class="audit-exceptions-head"><div class="left"><h2>Audit flags</h2><span class="audit-count-badge"><?= count($flags) ?> items</span></div></div>
    <?php foreach ($flags as $flag): ?>
        <?php $statusClass = $flag['status'] === 'Resolved' ? 'green' : ($flag['status'] === 'Escalated to NYSC' ? 'orange' : 'red'); ?>
        <article class="audit-exception" data-flag="<?= $escape($flag['id']) ?>" data-division="<?= $escape($flag['division']) ?>" data-status="<?= $escape($flag['status']) ?>">
            <div class="audit-exception-info"><div class="top"><span class="audit-ex-tag <?= $statusClass ?>"><?= $escape($flag['status']) ?></span><span class="audit-ex-ref"><?= $escape($flag['id']) ?> · <?= $escape($flag['reference']) ?></span></div><div class="title"><?= $escape($flag['type']) ?> — <?= $escape($flag['division']) ?></div><div class="detail">Amount: <b>LKR <?= number_format((float)$flag['amount'], 0) ?></b> · Reason: <?= $escape($flag['reason']) ?><?php if ($flag['note']): ?><br>Latest note: <?= $escape($flag['note']) ?><?php endif; ?></div></div>
            <div class="audit-head-right">
                <?php if (in_array($flag['status'], ['Open', 'Clarification requested'], true)): ?><button type="button" class="audit-btn-outline" data-modal-open="clarify-modal" data-flag-id="<?= $escape($flag['id']) ?>" data-division="<?= $escape($flag['division']) ?>">Request clarification</button><?php endif; ?>
                <?php if ($flag['status'] === 'Response received'): ?><button type="button" class="audit-btn-outline" data-modal-open="resolve-modal" data-flag-id="<?= $escape($flag['id']) ?>">Resolve with note</button><?php endif; ?>
                <?php if (!in_array($flag['status'], ['Resolved', 'Escalated to NYSC'], true)): ?><button type="button" class="audit-btn-outline" data-modal-open="escalate-modal" data-flag-id="<?= $escape($flag['id']) ?>">Escalate to NYSC</button><?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
    <?php if ($reviewReady): ?><div class="audit-ledger-result">All audit flags are resolved or escalated. Exporting this summary completes the zonal review; NYSC remains responsible for final sign-off.</div><?php endif; ?>
</section>

<div class="audit-overlay" id="flag-modal"><div class="audit-modal audit-clarify-modal"><div class="audit-modal-head"><div><h1>Flag discrepancy</h1><p>Only divisions in Gampaha Zone can be audited here.</p></div><button type="button" class="audit-close-x" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button></div><form method="post" action="<?= ROOT ?>/zonaltreasurer/flag"><input type="hidden" name="csrf_token" value="<?= $escape($csrf_token) ?>"><div class="audit-modal-body"><label class="audit-field-label" for="audit-division">Division</label><select id="audit-division" class="audit-field-box" name="division"><?php foreach ($reports as $report): ?><option><?= $escape($report['division']) ?></option><?php endforeach; ?></select><label class="audit-field-label" for="audit-type">Discrepancy type</label><input id="audit-type" class="audit-field-box" name="type" required maxlength="100"><label class="audit-field-label" for="audit-reference">Report or ledger reference</label><input id="audit-reference" class="audit-field-box" name="reference" required maxlength="100"><label class="audit-field-label" for="audit-amount">Amount (LKR)</label><input id="audit-amount" class="audit-field-box" name="amount" type="number" min="0.01" step="0.01" required><label class="audit-field-label" for="audit-reason">Discrepancy reason</label><textarea id="audit-reason" class="audit-textarea" name="reason" required maxlength="1000"></textarea></div><div class="audit-modal-foot-clarify"><button type="button" class="audit-btn-cancel-border" data-modal-close>Cancel</button><button type="submit" class="audit-btn-send">Create audit flag</button></div></form></div></div>

<div class="audit-overlay" id="clarify-modal"><div class="audit-modal audit-clarify-modal"><div class="audit-modal-head"><div><h1>Request clarification</h1><p>The request goes to the Divisional Treasurer; the Divisional Coordinator is notified.</p></div><button type="button" class="audit-close-x" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button></div><form method="post" action="<?= ROOT ?>/zonaltreasurer/clarify"><input type="hidden" name="csrf_token" value="<?= $escape($csrf_token) ?>"><input type="hidden" name="flag_id"><div class="audit-modal-body"><label class="audit-field-label">Audit query</label><textarea class="audit-textarea" name="query" required maxlength="1000" placeholder="Request the missing evidence or explanation."></textarea><div class="audit-field-box">Response deadline: 7 business days</div></div><div class="audit-modal-foot-clarify"><button type="button" class="audit-btn-cancel-border" data-modal-close>Cancel</button><button type="submit" class="audit-btn-send">Send clarification request</button></div></form></div></div>

<div class="audit-overlay" id="resolve-modal"><div class="audit-modal audit-clarify-modal"><div class="audit-modal-head"><div><h1>Resolve audit flag</h1><p>Only a flag with a received division response can be resolved.</p></div><button type="button" class="audit-close-x" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button></div><form method="post" action="<?= ROOT ?>/zonaltreasurer/resolve"><input type="hidden" name="csrf_token" value="<?= $escape($csrf_token) ?>"><input type="hidden" name="flag_id"><div class="audit-modal-body"><label class="audit-field-label">Resolution note</label><textarea class="audit-textarea" name="resolution_note" required maxlength="1000"></textarea></div><div class="audit-modal-foot-clarify"><button type="button" class="audit-btn-cancel-border" data-modal-close>Cancel</button><button type="submit" class="audit-btn-send">Resolve flag</button></div></form></div></div>

<div class="audit-overlay" id="escalate-modal"><div class="audit-modal audit-clarify-modal"><div class="audit-modal-head"><div><h1>Escalate to NYSC</h1><p>NYSC provides final authority; this does not lock a division ledger.</p></div><button type="button" class="audit-close-x" data-modal-close aria-label="Close"><?= yn_icon('close') ?></button></div><form method="post" action="<?= ROOT ?>/zonaltreasurer/escalate"><input type="hidden" name="csrf_token" value="<?= $escape($csrf_token) ?>"><input type="hidden" name="flag_id"><div class="audit-modal-body"><label class="audit-field-label">Escalation reason</label><textarea class="audit-textarea" name="escalation_reason" required maxlength="1000"></textarea></div><div class="audit-modal-foot-clarify"><button type="button" class="audit-btn-cancel-border" data-modal-close>Cancel</button><button type="submit" class="audit-btn-send">Escalate flag</button></div></form></div></div>


<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
