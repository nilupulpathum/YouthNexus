<?php
/**
 * Annual Financial Audit — Report view (Phases 2–4)
 * Math check panel, red flag table, and the auditor actions:
 * Request Clarification / Approve & Sign-Off.
 */

$title                   = $title ?? 'Audit Report — YouthNexus';
$pageTitle               = $pageTitle ?? 'Annual Financial Audit Report';
$pageDescription         = $pageDescription ?? 'Math verification, red flags and sign-off for the selected entity & financial year.';
$currentRoute            = 'audit';

require __DIR__ . '/../layouts/dashboard-start.view.php';

// ── Formatters ───────────────────────────────────────────────────
$fmtLKR = fn($v) => 'LKR ' . number_format((float)$v, 2);

// ── Derived state ────────────────────────────────────────────────
$locked        = (bool)$audit->locked;
$mathPassed    = $audit->math_check_status === 'Passed';
$variance      = (float)$audit->actual_closing_balance - (float)$audit->expected_closing_balance;
$unresolvedIds = [];
foreach ($audit->red_flags as $rf) {
    if ($rf->status !== 'Resolved') $unresolvedIds[] = (int)$rf->red_flag_id;
}

$flagLabels = [
    'MathMismatch'   => ['label' => 'LEDGER MISMATCH', 'class' => 'red'],
    'MissingReceipt' => ['label' => 'MISSING RECEIPT', 'class' => 'red'],
    'FundHoarding'   => ['label' => 'FUND HOARDING',   'class' => 'orange'],
    'HighVoidRate'   => ['label' => 'DISCIPLINE NOTICE', 'class' => 'gray'],
];

// Ledger verification steps (Phase 2 core equation)
$steps = [
    ['label' => 'Opening Balance',    'value' => $fmtLKR($audit->opening_balance),               'color' => 'normal'],
    ['label' => '+ Total Income',     'value' => $fmtLKR($audit->total_income),                 'color' => 'green'],
    ['label' => '+ Transfers In',     'value' => $fmtLKR($audit->total_transfers_received),     'color' => 'green'],
    ['label' => '– Expenses',         'value' => $fmtLKR($audit->total_expenses),               'color' => 'red'],
    ['label' => '– Transfers Out',    'value' => $fmtLKR($audit->total_transfers_distributed),  'color' => 'red'],
    ['label' => '= Expected Balance', 'value' => $fmtLKR($audit->expected_closing_balance),     'color' => 'blue'],
];
?>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/annualaudit.css?v=<?= time() ?>">

<div class="audit-content">

    <!-- Flash message alerts -->
    <?php if (!empty($flashSuccess)): ?>
        <div class="audit-flash-alert audit-flash-success" role="alert">
            <div>&#10003;&nbsp; <?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?></div>
            <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;font-size:16px;color:inherit;">&times;</button>
        </div>
    <?php endif; ?>

    <?php if (!empty($flashError)): ?>
        <div class="audit-flash-alert audit-flash-error" role="alert">
            <div>&#9888;&nbsp; <?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
            <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;font-size:16px;color:inherit;">&times;</button>
        </div>
    <?php endif; ?>

    <!-- ── Report header bar ─────────────────────────────────────── -->
    <div class="audit-report-top">
        <div class="audit-report-meta">
            <h1><?= htmlspecialchars($audit->scope_details->title) ?></h1>
            <p>
                FY <?= (int)$audit->financial_year ?> &nbsp;&bull;&nbsp;
                <?= htmlspecialchars($audit->scope_details->subtitle) ?> &nbsp;&bull;&nbsp;
                Initiated by <?= htmlspecialchars(trim(($audit->init_first ?? '') . ' ' . ($audit->init_last ?? '')) ?: 'NYSC Admin') ?>
                <?= $audit->initiated_at ? ' on ' . date('M j, Y', strtotime($audit->initiated_at)) : '' ?>
            </p>
            <?php if ($locked && $audit->signed_off_at): ?>
                <p class="audit-signed-line">
                    &#128274; Signed off by <?= htmlspecialchars(trim(($audit->sign_first ?? '') . ' ' . ($audit->sign_last ?? '')) ?: 'NYSC Admin') ?>
                    on <?= date('M j, Y', strtotime($audit->signed_off_at)) ?> — this financial year is locked.
                </p>
            <?php endif; ?>
        </div>
        <div class="audit-report-actions">
            <a href="<?= ROOT ?>/audit" class="audit-btn audit-btn-light">&larr; All Audits</a>
            <a href="<?= ROOT ?>/audit/export?audit_id=<?= (int)$audit->audit_id ?>" class="audit-btn audit-btn-light">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Export Summary
            </a>
            <?php if (!$locked): ?>
                <form method="POST" action="<?= ROOT ?>/audit/rerun" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES) ?>">
                    <input type="hidden" name="audit_id" value="<?= (int)$audit->audit_id ?>">
                    <button type="submit" class="audit-btn audit-btn-light">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                        Re-run Check
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Stat cards ────────────────────────────────────────────── -->
    <div class="audit-stats">
        <div class="audit-stat-card">
            <h3>Self-Raised Income</h3>
            <div class="num"><?= $fmtLKR($audit->total_income) ?></div>
            <div class="sub gray">Fundraising &amp; events (FY <?= (int)$audit->financial_year ?>)</div>
        </div>

        <div class="audit-stat-card">
            <h3>Transfers Received</h3>
            <div class="num"><?= $fmtLKR($audit->total_transfers_received) ?></div>
            <div class="sub gray">Grants from upper levels</div>
        </div>

        <div class="audit-stat-card">
            <h3>Total Expenses</h3>
            <div class="num"><?= $fmtLKR($audit->total_expenses) ?></div>
            <div class="sub gray">Logged by the entity</div>
        </div>

        <div class="audit-stat-card <?= $unresolvedCount > 0 ? 'audit-stat-alert' : '' ?>">
            <h3>
                Audit Status
                <?php if ($locked): ?>
                    <span class="audit-pill audit-pill-green">Completed</span>
                <?php elseif ($unresolvedCount > 0): ?>
                    <span class="audit-pill audit-pill-red">Action Required</span>
                <?php else: ?>
                    <span class="audit-pill audit-pill-yellow">Pending</span>
                <?php endif; ?>
            </h3>
            <?php if ($locked): ?>
                <div class="num" style="color:#1e9e5a;">Signed Off</div>
                <div class="sub green">&#10003;&nbsp; Ledger locked for FY <?= (int)$audit->financial_year ?></div>
            <?php elseif ($unresolvedCount > 0): ?>
                <div class="num" style="color:#b91c1c;"><?= (int)$unresolvedCount ?></div>
                <div class="sub red">&#9679;&nbsp; Unresolved red flag<?= $unresolvedCount !== 1 ? 's' : '' ?></div>
            <?php else: ?>
                <div class="num">Ready</div>
                <div class="sub gray">All flags resolved — eligible for sign-off</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Phase 2: Math check panel ─────────────────────────────── -->
    <div class="audit-panel">
        <div class="audit-panel-head">
            <h2>Core Mathematical Ledger Verification</h2>
            <?php if ($mathPassed): ?>
                <span class="audit-pill audit-pill-green">Math Check Passed</span>
            <?php else: ?>
                <span class="audit-pill audit-pill-red">Math Check Failed</span>
            <?php endif; ?>
        </div>

        <div class="audit-ledger-steps">
            <?php foreach ($steps as $s): ?>
                <div class="audit-step <?= ($s['color'] == 'blue') ? 'expected' : '' ?>">
                    <div class="step-label"><?= htmlspecialchars($s['label']) ?></div>
                    <div class="step-value <?= htmlspecialchars($s['color']) ?>"><?= htmlspecialchars($s['value']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="audit-ledger-result">
            Actual Closing Balance in Ledger: <b><?= $fmtLKR($audit->actual_closing_balance) ?></b>
            &nbsp;&nbsp;|&nbsp;&nbsp;
            Variance:
            <?php if ($mathPassed): ?>
                <b class="variance-ok"><?= $fmtLKR($variance) ?></b>
            <?php else: ?>
                <b class="variance-bad"><?= $fmtLKR($variance) ?></b>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Phase 3: Red flags table ──────────────────────────────── -->
    <div class="audit-exceptions-head">
        <div class="left">
            <h2>Red Flags &amp; Audit Exceptions</h2>
            <span class="audit-count-badge<?= $unresolvedCount === 0 ? ' audit-count-ok' : '' ?>">
                <?= $unresolvedCount === 0 ? (count($audit->red_flags) . ' resolved') : ($unresolvedCount . ' open') ?>
            </span>
        </div>

        <!-- Phase 4 auditor actions live inside the report -->
        <?php if (!$locked): ?>
            <div class="right">
                <?php if ($unresolvedCount > 0): ?>
                    <button type="button" class="audit-btn audit-btn-blue" onclick="openClarifyModal()">
                        Request Clarification
                    </button>
                    <button type="button" class="audit-btn audit-btn-disabled" disabled
                            title="Resolve all red flags and pass the math check to enable sign-off">
                        Approve &amp; Sign-Off Audit
                    </button>
                <?php elseif ($mathPassed): ?>
                    <button type="button" class="audit-btn audit-btn-blue" onclick="openApproveModal()">
                        &#128274; Approve &amp; Sign-Off Audit
                    </button>
                <?php else: ?>
                    <button type="button" class="audit-btn audit-btn-disabled" disabled
                            title="The math check must pass before sign-off">
                        Approve &amp; Sign-Off Audit
                    </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if (empty($audit->red_flags)): ?>
        <div class="audit-empty-state">
            <svg viewBox="0 0 24 24" width="38" height="38" fill="none" stroke="currentColor" stroke-width="1.4" style="color:#d1d5db;margin-bottom:12px"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <h3>No red flags detected</h3>
            <p>The ledger scan found no missing receipts, idle funds, or void-rate anomalies for this scope and year.</p>
        </div>
    <?php else: ?>
        <div class="audit-table-card">
            <table class="audit-table">
                <thead>
                    <tr>
                        <th>FLAG</th>
                        <th>DETAILS</th>
                        <th>STATUS</th>
                        <?php if (!$locked): ?><th>ACTION</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($audit->red_flags as $rf):
                        $meta = $flagLabels[$rf->flag_type] ?? ['label' => strtoupper($rf->flag_type), 'class' => 'gray'];
                    ?>
                        <tr>
                            <td><span class="audit-ex-tag <?= htmlspecialchars($meta['class']) ?>"><?= htmlspecialchars($meta['label']) ?></span></td>
                            <td>
                                <div class="audit-flag-desc"><?= htmlspecialchars($rf->description) ?></div>
                                <?php if ($rf->entry_date): ?>
                                    <div class="audit-td-sub">Entry dated <?= date('M j, Y', strtotime($rf->entry_date)) ?><?= $rf->entry_amount ? ' • ' . $fmtLKR($rf->entry_amount) : '' ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($rf->status === 'Resolved'): ?>
                                    <span class="audit-pill audit-pill-green">Resolved</span>
                                <?php elseif ($rf->status === 'ClarificationRequested'): ?>
                                    <span class="audit-pill audit-pill-yellow">Clarification Sent</span>
                                <?php else: ?>
                                    <span class="audit-pill audit-pill-red">Open</span>
                                <?php endif; ?>
                            </td>
                            <?php if (!$locked): ?>
                                <td>
                                    <?php if ($rf->status !== 'Resolved'): ?>
                                        <form method="POST" action="<?= ROOT ?>/audit/resolveflag/<?= (int)$rf->red_flag_id ?>" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES) ?>">
                                            <button type="submit" class="audit-btn-outline">Resolve / Accept</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="audit-td-sub">Accepted</span>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div><!-- /audit-content -->

<?php if (!$locked && $unresolvedCount > 0): ?>
<!-- ════════════════════════════════════════════════════════════════
     PHASE 4 MODAL 1: Request Clarification
     ════════════════════════════════════════════════════════════════ -->
<div class="audit-overlay" id="clarifyModal">
    <div class="audit-modal audit-clarify-modal">

        <div class="audit-modal-head">
            <div>
                <h1>Request Clarification</h1>
                <p>Notify the entity's treasurer &amp; coordinator about the flagged items. The audit stays <b>Pending</b> until resolved.</p>
            </div>
            <button type="button" class="audit-close-x" onclick="closeClarifyModal()" aria-label="Close dialog">&times;</button>
        </div>

        <form method="POST" action="<?= ROOT ?>/audit/clarify">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES) ?>">
            <input type="hidden" name="audit_id" value="<?= (int)$audit->audit_id ?>">

            <div class="audit-modal-body">

                <label class="audit-field-label">Designated Recipients</label>
                <div class="audit-recipients">
                    <div><span class="mail">&#9993;</span> <?= htmlspecialchars($audit->scope_details->recipients_label) ?></div>
                    <div class="verified">Verified Contacts</div>
                </div>

                <label class="audit-field-label">Flagged Items to Include</label>
                <div class="audit-flag-list">
                    <?php foreach ($audit->red_flags as $rf): ?>
                        <?php if ($rf->status === 'Resolved') continue; ?>
                        <label class="audit-flag-option">
                            <input type="checkbox" name="flag_ids[]" value="<?= (int)$rf->red_flag_id ?>" checked>
                            <span><?= htmlspecialchars(mb_strimwidth($rf->description, 0, 110, '…')) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <label class="audit-field-label" for="auditQuery">Audit Query Details</label>
                <textarea name="query" id="auditQuery" class="audit-textarea" placeholder="Please provide the missing receipts or a written justification for the flagged items.">Please provide the missing receipts or a written justification for the flagged items within the deadline.</textarea>

                <div class="audit-two-row">
                    <div class="audit-field-col">
                        <label class="audit-field-label" for="auditDeadline">Resolution Deadline</label>
                        <select name="deadline_days" id="auditDeadline" class="audit-field-box audit-field-select">
                            <option value="3">3 Business Days</option>
                            <option value="5">5 Business Days</option>
                            <option value="7" selected>7 Business Days</option>
                            <option value="14">14 Business Days</option>
                        </select>
                    </div>
                    <div class="audit-field-col">
                        <label class="audit-field-label">Notice Type</label>
                        <div class="audit-field-box">Official Audit Clarification Query</div>
                    </div>
                </div>

            </div>

            <div class="audit-modal-foot-clarify">
                <div class="audit-foot-note">Note: recipients are notified in-app. The audit status remains Pending while items are clarified.</div>
                <div class="audit-foot-btns">
                    <button type="button" class="audit-btn-cancel-border" onclick="closeClarifyModal()">Cancel</button>
                    <button type="submit" class="audit-btn-send">&#9655; Send Clarification Query</button>
                </div>
            </div>
        </form>

    </div>
</div>
<?php endif; ?>

<?php if (!$locked && $canSignOff): ?>
<!-- ════════════════════════════════════════════════════════════════
     PHASE 4 MODAL 2: Approve & Sign-Off Audit
     ════════════════════════════════════════════════════════════════ -->
<div class="audit-overlay" id="approveModal">
    <div class="audit-modal audit-approve-modal">

        <div class="audit-modal-head">
            <div>
                <h1>Approve &amp; Sign-Off Audit</h1>
                <p>Formal certification and financial-year lock for <?= htmlspecialchars($audit->scope_details->title) ?> — FY <?= (int)$audit->financial_year ?></p>
            </div>
            <button type="button" class="audit-close-x" onclick="closeApproveModal()" aria-label="Close dialog">&times;</button>
        </div>

        <form method="POST" action="<?= ROOT ?>/audit/signoff">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES) ?>">
            <input type="hidden" name="audit_id" value="<?= (int)$audit->audit_id ?>">

            <div class="audit-modal-body">

                <div class="audit-summary">
                    <div class="row">
                        <span class="label">SCOPE</span>
                        <span class="value"><?= htmlspecialchars($audit->scope_details->title) ?> (FY <?= (int)$audit->financial_year ?>)</span>
                    </div>
                    <div class="row">
                        <span class="label">MATH CHECK</span>
                        <span class="value">Passed • Expected <?= $fmtLKR($audit->expected_closing_balance) ?> matches ledger</span>
                    </div>
                    <div class="row">
                        <span class="label">RED FLAGS</span>
                        <span class="value">All <?= count($audit->red_flags) ?> item<?= count($audit->red_flags) !== 1 ? 's' : '' ?> resolved</span>
                    </div>
                </div>

                <div class="audit-lock-notice">
                    <span class="lock">&#128274;</span>
                    <span><b>Ledger Lock Notice:</b> upon sign-off, no new entries or voids can be recorded for FY <?= (int)$audit->financial_year ?> on this entity's ledger. The audit is permanently marked Completed.</span>
                </div>

                <label class="audit-field-label">Auditor Name &amp; Designation</label>
                <div class="audit-auditor-box">
                    <div><?= htmlspecialchars($auditorName) ?> — <?= htmlspecialchars($auditorRole === 'NYSCAdministrator' ? 'NYSC National Administration' : $auditorRole) ?></div>
                    <div class="id-badge">VERIFIED ID</div>
                </div>

            </div>

            <div class="audit-modal-foot-approve">
                <button type="button" class="audit-btn-cancel" onclick="closeApproveModal()">Cancel</button>
                <button type="submit" class="audit-btn-confirm">&#128274; Confirm Sign-Off &amp; Lock FY <?= (int)$audit->financial_year ?></button>
            </div>
        </form>

    </div>
</div>
<?php endif; ?>

<script>
    window.YouthNexusAudit = {
        rootUrl: <?= json_encode(ROOT) ?>,
        csrfToken: <?= json_encode($csrf_token) ?>
    };
</script>
<script src="<?= ROOT ?>/assets/js/annualaudit.js?v=<?= time() ?>" defer></script>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
