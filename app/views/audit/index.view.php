<?php
/**
 * Annual Financial Audit — Phase 1 dashboard
 * Scope & financial-year selection plus the register of compiled audits.
 */

$title                   = $title ?? 'Annual Audit — YouthNexus';
$pageTitle               = $pageTitle ?? 'Annual Financial Audit';
$pageDescription         = $pageDescription ?? 'Select an entity and financial year to compile its statutory audit.';
$currentRoute            = 'audit';
$unreadNotificationCount = 0;
$pageStyles              = [ROOT . '/assets/css/annualaudit.css?v=' . time()];

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

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

    <!-- ── Phase 1: Initiation & scope selection ─────────────────── -->
    <div class="audit-panel">
        <h2>Start an Annual Audit</h2>
        <p class="audit-panel-sub">Choose the entity and financial year. The system compiles the ledger, runs the core math check and scans for red flags.</p>

        <form method="POST" action="<?= ROOT ?>/audit/run" class="audit-compile-form" id="auditRunForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES) ?>">

            <div class="audit-compile-row">
                <div class="audit-compile-field audit-compile-entity">
                    <label for="auditEntity">Audit Scope <span class="audit-required">*</span></label>
                    <select name="entity" id="auditEntity" required>
                        <option value="National:0">National Summary — NYSC Central Ledger</option>
                        <?php foreach (['Zonal' => 'Zones', 'Divisional' => 'Divisions', 'Club' => 'Clubs'] as $levelKey => $levelLabel): ?>
                            <?php if (!empty($scopes[$levelKey])): ?>
                                <optgroup label="<?= htmlspecialchars($levelLabel) ?>">
                                    <?php foreach ($scopes[$levelKey] as $s): ?>
                                        <option value="<?= htmlspecialchars($s['scope_level'] . ':' . (int)$s['scope_id']) ?>">
                                            <?= htmlspecialchars($s['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="audit-compile-field">
                    <label for="auditYear">Financial Year <span class="audit-required">*</span></label>
                    <select name="year" id="auditYear">
                        <?php foreach ($years as $y): ?>
                            <option value="<?= (int)$y ?>" <?= (int)$y === (int)date('Y') ? 'selected' : '' ?>>FY <?= (int)$y ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="audit-btn audit-btn-blue audit-compile-btn">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                    Compile Audit
                </button>
            </div>
        </form>
    </div>

    <!-- ── Audit reports register ─────────────────────────────────── -->
    <div class="audit-exceptions-head">
        <div class="left">
            <h2>Audit Reports</h2>
            <span class="audit-count-muted"><?= count($audits) ?> compiled</span>
        </div>
    </div>

    <?php if (empty($audits)): ?>
        <div class="audit-empty-state">
            <svg viewBox="0 0 24 24" width="38" height="38" fill="none" stroke="currentColor" stroke-width="1.4" style="color:#d1d5db;margin-bottom:12px"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            <h3>No audits compiled yet</h3>
            <p>Select an entity and financial year above, then click Compile Audit to produce the first report.</p>
        </div>
    <?php else: ?>
        <div class="audit-table-card">
            <table class="audit-table">
                <thead>
                    <tr>
                        <th>FY</th>
                        <th>ENTITY</th>
                        <th>MATH CHECK</th>
                        <th>RED FLAGS</th>
                        <th>STATUS</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($audits as $a): ?>
                        <tr>
                            <td class="audit-td-strong"><?= (int)$a->financial_year ?></td>
                            <td>
                                <span class="audit-td-strong"><?= htmlspecialchars($a->scope_label) ?></span>
                                <div class="audit-td-sub">
                                    <?= $a->locked
                                        ? 'Signed off ' . ($a->signed_off_at ? date('M j, Y', strtotime($a->signed_off_at)) : '')
                                        : 'Initiated ' . ($a->initiated_at ? date('M j, Y', strtotime($a->initiated_at)) : '') ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($a->math_check_status === 'Passed'): ?>
                                    <span class="audit-pill audit-pill-green">Passed</span>
                                <?php else: ?>
                                    <span class="audit-pill audit-pill-red">Mismatch</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ((int)$a->total_flags === 0): ?>
                                    <span class="audit-pill audit-pill-green">None</span>
                                <?php elseif ((int)$a->open_flags === 0): ?>
                                    <span class="audit-pill audit-pill-blue"><?= (int)$a->total_flags ?> resolved</span>
                                <?php else: ?>
                                    <span class="audit-pill audit-pill-red"><?= (int)$a->open_flags ?> open<?= ((int)$a->total_flags > (int)$a->open_flags) ? ' / ' . (int)$a->total_flags : '' ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($a->locked): ?>
                                    <span class="audit-pill audit-pill-green">Completed</span>
                                <?php else: ?>
                                    <span class="audit-pill audit-pill-yellow">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a class="audit-btn-outline" href="<?= ROOT ?>/audit/report/<?= (int)$a->audit_id ?>">View Report</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div><!-- /audit-content -->

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
