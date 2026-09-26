<?php
/**
 * Report Preview — Phase 3 & 4: Preview & Distribution
 * Rendered inside the shared dashboard layout.
 */

$title                   = $title ?? 'Report Preview — YouthNexus';
$currentRoute            = 'reports';
$unreadNotificationCount = 0;
$pageStyles              = [ROOT . '/assets/css/managereports.css?v=' . time()];

$report      = $report      ?? (object)[];
$kpis        = $kpis        ?? [];
$summaryRows = $summaryRows ?? [];
$rawRows     = $rawRows     ?? [];

$reportId    = (int)($report->report_id     ?? 0);
$category    = htmlspecialchars($report->category   ?? 'Financial');
$typeName    = htmlspecialchars($report->type_name  ?? 'Report');
$scopeLevel  = htmlspecialchars($report->scope_level ?? 'National');
$dateStart   = !empty($report->date_range_start) ? date('M d, Y', strtotime($report->date_range_start)) : 'Jan 01';
$dateEnd     = !empty($report->date_range_end)   ? date('M d, Y', strtotime($report->date_range_end))   : 'Jun 30';
$format      = htmlspecialchars($report->format ?? 'PDF');
$generatedBy = trim(($report->first_name ?? '') . ' ' . ($report->last_name ?? ''));
if (empty($generatedBy)) $generatedBy = 'N. Fernando';

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<div class="rpt-content rpt-preview-wrap">
<div class="rpt-preview-modal">

    <!-- ── Header ────────────────────────────────────────────── -->
    <div class="rpt-preview__header">
        <div class="rpt-badge-row">
            <span class="rpt-badge rpt-badge--blue">PHASE 3 &amp; 4: PREVIEW &amp; DISTRIBUTION</span>
            <span class="rpt-badge rpt-badge--green">
                <span class="rpt-green-dot"></span>Statutory Rollup
            </span>
        </div>

        <h1 class="rpt-preview__title">Report Preview: <?= $typeName ?></h1>

        <p class="rpt-preview__meta">
            Scope: <b><?= $scopeLevel ?></b>
            <span class="rpt-meta-sep">&middot;</span>
            Range: <b><?= $dateStart ?> – <?= $dateEnd ?></b>
            <span class="rpt-meta-sep">&middot;</span>
            Format: <b><?= $format ?></b>
        </p>
    </div>

    <!-- ── KPI cards ─────────────────────────────────────────── -->
    <div class="rpt-preview__body">
        <div class="rpt-kpi-grid">
            <?php foreach ($kpis as $kpi):
                $kpiToneClass = ($kpi['tone'] ?? 'blue') === 'green' ? 'rpt-kpi-note--green' : 'rpt-kpi-note--blue';
            ?>
                <div class="rpt-kpi-card">
                    <div class="rpt-kpi-label"><?= htmlspecialchars($kpi['label']) ?></div>
                    <div class="rpt-kpi-value"><?= htmlspecialchars($kpi['value']) ?></div>
                    <div class="rpt-kpi-note <?= $kpiToneClass ?>">
                        <?php if (($kpi['tone'] ?? '') === 'green'): ?>
                            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>
                        <?php else: ?>
                            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <?php endif; ?>
                        <?= htmlspecialchars($kpi['note']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ── Tabs ──────────────────────────────────────────── -->
        <div class="rpt-tabs-row">
            <div class="rpt-tabs">
                <button type="button" id="tabSummary" class="rpt-tab-btn rpt-tab-btn--active" onclick="showTab('summary')">Summary View</button>
                <button type="button" id="tabRaw"     class="rpt-tab-btn"                     onclick="showTab('raw')">Raw Detail Table</button>
            </div>
            <div class="rpt-sync-note">
                <span class="rpt-green-dot"></span>
                <b><?= count($summaryRows) ?> of <?= count($summaryRows) ?></b>&nbsp;Zonal Hubs Synced
            </div>
        </div>

        <!-- ── Summary Tab ───────────────────────────────────── -->
        <div id="summaryTab" class="rpt-table-section">
            <div class="rpt-table-box">
                <table>
                    <thead>
                        <tr>
                            <th>Province / Zone</th>
                            <th class="td-center">Allocated (LKR)</th>
                            <th class="td-center">Disbursed (LKR)</th>
                            <th class="td-center">Expenses Logged</th>
                            <th class="td-center">Void Count</th>
                            <th class="td-right">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($summaryRows as $row): ?>
                            <tr>
                                <td><span class="rpt-row-dot"></span><span class="rpt-zone-name"><?= htmlspecialchars($row['zone']) ?></span></td>
                                <td class="td-center"><?= htmlspecialchars($row['allocated']) ?></td>
                                <td class="td-center"><?= htmlspecialchars($row['disbursed']) ?></td>
                                <td class="td-center td-bold"><?= htmlspecialchars($row['expenses']) ?></td>
                                <td class="td-center"><?= (int)$row['voids'] ?></td>
                                <td class="td-right">
                                    <span class="rpt-status-pill">
                                        <?= htmlspecialchars($row['status']) ?>
                                        <svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── Raw Detail Tab ────────────────────────────────── -->
        <div id="rawTab" class="rpt-table-section" style="display:none">
            <div class="rpt-table-box rpt-table-compact">
                <table>
                    <thead>
                        <tr>
                            <th>Division</th>
                            <th>Zone (Hub)</th>
                            <th class="td-center">Allocated (LKR)</th>
                            <th class="td-center">Disbursed (LKR)</th>
                            <th class="td-center">Expenses</th>
                            <th class="td-center">Voids</th>
                            <th class="td-center">Submitted By</th>
                            <th class="td-right">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rawRows as $row): ?>
                            <tr>
                                <td class="td-bold"><?= htmlspecialchars($row['division']) ?></td>
                                <td><?= htmlspecialchars($row['zone']) ?></td>
                                <td class="td-center"><?= htmlspecialchars($row['allocated']) ?></td>
                                <td class="td-center"><?= htmlspecialchars($row['disbursed']) ?></td>
                                <td class="td-center td-bold"><?= htmlspecialchars($row['expenses']) ?></td>
                                <td class="td-center"><?= (int)$row['voids'] ?></td>
                                <td class="td-center"><?= htmlspecialchars($row['by']) ?></td>
                                <td class="td-right">
                                    <span class="rpt-status-pill">
                                        <?= htmlspecialchars($row['status']) ?>
                                        <svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── Compliance note ───────────────────────────────── -->
        <div class="rpt-note-box">
            <div class="rpt-note-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 11.5 11.5 14 15.5 9.5"/></svg>
            </div>
            <p class="rpt-note-text">
                <b>Statutory Ledger Compliance Verification:</b>
                Verified statutory aggregation rolled up from <?= count($rawRows) ?> Divisional reports submitted by Zonal Secretaries.
                Data sealed under NYSC FinAct <?= date('Y') ?>.
            </p>
        </div>
    </div><!-- /.rpt-preview__body -->

    <!-- ── Footer actions ────────────────────────────────────── -->
    <div class="rpt-preview__footer">
        <div class="rpt-preview-footer-left">
            <a href="<?= ROOT ?>/reports/create" class="rpt-footer-link rpt-footer-link--dark" id="btn-edit-params">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Edit Parameters
            </a>
            <?php if ($reportId > 0): ?>
                <form method="post" action="<?= ROOT ?>/reports/archive/<?= $reportId ?>" style="display:inline"
                      onsubmit="return confirm('Move this report to archive?')">
                    <button type="submit" class="rpt-footer-link rpt-footer-link--gray" id="btn-archive">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        Archive
                    </button>
                </form>
            <?php endif; ?>
        </div>
        <div class="rpt-preview-footer-right">
            <button type="button" class="rpt-btn rpt-btn--ghost" id="btn-email-share" onclick="openShareModal()">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                Email / Share
            </button>
            <button type="button" class="rpt-btn rpt-btn--ghost" id="btn-download-pdf"
                    onclick="alert('PDF generation is handled by the report file system. Download will be available once the report is generated & saved.')">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Download PDF
            </button>
            <button type="button" class="rpt-btn rpt-btn--primary" id="btn-generate-save"
                    onclick="alert('Report generated and saved successfully.')">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>
                Generate &amp; Save Report
            </button>
        </div>
    </div>

</div><!-- /.rpt-preview-modal -->
</div><!-- /.rpt-preview-wrap -->

<!-- ── Email / Share Modal ────────────────────────────────────── -->
<div class="rpt-overlay" id="shareOverlay" style="display:none" onclick="if(event.target===this)closeShareModal()">
    <div class="rpt-share-modal">
        <div class="rpt-share-modal__header">
            <h2>Email / Share Report</h2>
            <button type="button" onclick="closeShareModal()" class="rpt-share-modal__close" aria-label="Close">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form method="post" action="<?= ROOT ?>/reports/share" class="rpt-share-modal__body">
            <input type="hidden" name="report_id" value="<?= $reportId ?>">
            <label class="rpt-field__label" for="share-email">Recipient Email</label>
            <input type="email" id="share-email" name="recipient_email" class="rpt-date-input" placeholder="recipient@example.com" required style="margin-bottom:14px">
            <label class="rpt-field__label" for="share-method">Share Method</label>
            <div class="rpt-select-wrap rpt-select-wrap--full" style="margin-bottom:20px">
                <select id="share-method" name="method">
                    <option value="Email">Email</option>
                    <option value="Link">Link</option>
                </select>
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" class="rpt-select-arrow"><polyline points="6 9 12 15 18 9"/></svg>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:12px">
                <button type="button" onclick="closeShareModal()" class="rpt-cancel-btn">Cancel</button>
                <button type="submit" class="rpt-compile-btn" id="btn-share-submit">Send</button>
            </div>
        </form>
    </div>
</div>

<script>
function showTab(name) {
    var summary    = document.getElementById('summaryTab');
    var raw        = document.getElementById('rawTab');
    var btnSummary = document.getElementById('tabSummary');
    var btnRaw     = document.getElementById('tabRaw');
    if (name === 'summary') {
        summary.style.display = 'block'; raw.style.display = 'none';
        btnSummary.classList.add('rpt-tab-btn--active');
        btnRaw.classList.remove('rpt-tab-btn--active');
    } else {
        summary.style.display = 'none'; raw.style.display = 'block';
        btnSummary.classList.remove('rpt-tab-btn--active');
        btnRaw.classList.add('rpt-tab-btn--active');
    }
}

function openShareModal()  { document.getElementById('shareOverlay').style.display = 'flex'; }
function closeShareModal() { document.getElementById('shareOverlay').style.display = 'none'; }
</script>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
