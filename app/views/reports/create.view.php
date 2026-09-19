<?php
/**
 * Create New Report — Phase 1: Configuration
 * Rendered inside the shared dashboard layout.
 */

$title                   = $title ?? 'Create New Report — YouthNexus';
$pageTitle               = $pageTitle ?? 'Create New Report';
$currentRoute            = 'reports';
$unreadNotificationCount = 0;

$catalog = $catalog ?? [];
$scopes  = $scopes  ?? [
    'National' => 'National Summary (Roll up all Zonal submissions)',
    'Zonal'    => 'Zonal Summary (Roll up Divisional submissions)',
    'Divisional' => 'Divisional Summary (Roll up Club submissions)',
    'Club'     => 'Club Summary (Single Club)',
];
$formats = $formats ?? [
    ['id'=>'PDF',      'title'=>'Formal Printable (PDF)',  'desc'=>'Formatted for archival & official signing',    'icon_key'=>'file'],
    ['id'=>'CSV',      'title'=>'Data Spreadsheet (CSV)',  'desc'=>'Raw structured sub-ledger & event rows',       'icon_key'=>'table'],
    ['id'=>'OnScreen', 'title'=>'On-Screen Dashboard',     'desc'=>'Interactive summary cards & drilldown charts', 'icon_key'=>'bar-chart'],
];

$defaultCategory = array_key_first($catalog) ?? 'Financial';
$defaultFrom     = date('Y-01-01');
$defaultTo       = date('Y-06-30');
$defaultScopeKey = 'National';

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/managereports.css?v=<?= time() ?>">

<div class="rpt-content rpt-create-wrap">

    <!-- modal card -->
    <div class="rpt-modal">

        <!-- Header -->
        <div class="rpt-modal__header">
            <a href="<?= ROOT ?>/reports" class="rpt-modal__close" title="Cancel" id="btn-modal-close">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </a>
            <div class="rpt-modal__title-row">
                <h1 class="rpt-modal__h1">Create New Report</h1>
                <span class="rpt-phase-badge">PHASE 1: CONFIGURATION</span>
            </div>
            <p class="rpt-modal__sub">Select report parameters, aggregation scope, and export format.</p>
        </div>

        <!-- Form body -->
        <form method="post" action="<?= ROOT ?>/reports/compile" class="rpt-modal__body" id="create-report-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">

            <!-- Row 1: Category + Type -->
            <div class="rpt-form-row">
                <div class="rpt-field">
                    <label class="rpt-field__label" for="rpt-category">Report Category</label>
                    <div class="rpt-select-wrap rpt-select-wrap--full">
                        <select id="rpt-category" name="category" onchange="updateTypes()">
                            <?php foreach ($catalog as $catName => $types): ?>
                                <option value="<?= htmlspecialchars($catName) ?>" <?= $catName === $defaultCategory ? 'selected' : '' ?>><?= htmlspecialchars($catName) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" class="rpt-select-arrow"><polyline points="7 6 12 11 17 6"/><polyline points="7 13 12 18 17 13"/></svg>
                    </div>
                </div>
                <div class="rpt-field">
                    <label class="rpt-field__label" for="rpt-type">Report Type</label>
                    <div class="rpt-select-wrap rpt-select-wrap--full">
                        <select id="rpt-type" name="type">
                            <?php foreach (($catalog[$defaultCategory] ?? []) as $typeName): ?>
                                <option value="<?= htmlspecialchars($typeName) ?>"><?= htmlspecialchars($typeName) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" class="rpt-select-arrow"><polyline points="7 6 12 11 17 6"/><polyline points="7 13 12 18 17 13"/></svg>
                    </div>
                </div>
            </div>

            <!-- Row 2: Aggregation Scope -->
            <div class="rpt-form-row rpt-form-row--single">
                <div class="rpt-field">
                    <label class="rpt-field__label" for="rpt-scope">Aggregation Scope</label>
                    <div class="rpt-select-wrap rpt-select-wrap--full">
                        <select id="rpt-scope" name="scope">
                            <?php foreach ($scopes as $scopeKey => $scopeLabel): ?>
                                <option value="<?= htmlspecialchars($scopeLabel) ?>" <?= $scopeKey === $defaultScopeKey ? 'selected' : '' ?>><?= htmlspecialchars($scopeLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" class="rpt-select-arrow"><polyline points="7 6 12 11 17 6"/><polyline points="7 13 12 18 17 13"/></svg>
                    </div>
                    <div class="rpt-field__hint">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        National Summary aggregates verified figures already submitted by Zonal Secretaries.
                    </div>
                </div>
            </div>

            <!-- Row 3: Date range -->
            <div class="rpt-form-row">
                <div class="rpt-field">
                    <label class="rpt-field__label" for="rpt-from">From Date</label>
                    <input type="date" id="rpt-from" name="date_from" class="rpt-date-input" value="<?= $defaultFrom ?>" onchange="updateDateInfo()">
                </div>
                <div class="rpt-field">
                    <label class="rpt-field__label" for="rpt-to">To Date</label>
                    <input type="date" id="rpt-to" name="date_to" class="rpt-date-input" value="<?= $defaultTo ?>" onchange="updateDateInfo()">
                </div>
            </div>
            <!-- Date validation message -->
            <div id="dateInfo" class="rpt-date-info rpt-date-info--valid">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                <span>Valid date range: <span id="dateLabel">Q1 - Q2 FY<?= date('Y') ?></span> (<span id="dateDays">181</span> days)</span>
            </div>
            <div id="dateError" class="rpt-date-info rpt-date-info--invalid" style="display:none">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span>Invalid date range: To Date must be after From Date</span>
            </div>

            <!-- Row 4: Output Format cards -->
            <div class="rpt-form-row rpt-form-row--single">
                <div class="rpt-field">
                    <label class="rpt-field__label">Select Output Format</label>
                    <input type="hidden" id="formatValue" name="format" value="PDF">
                    <div class="rpt-format-grid" id="format-grid">
                        <?php
                        $fmtIcons = [
                            'file'      => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',
                            'table'     => '<rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="12" y1="3" x2="12" y2="21"/>',
                            'bar-chart' => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
                        ];
                        $fmtColorMap = [
                            'PDF'      => ['bg'=>'#dbeafe','icon'=>'#2563eb'],
                            'CSV'      => ['bg'=>'#dcfce7','icon'=>'#16a34a'],
                            'OnScreen' => ['bg'=>'#ede9fe','icon'=>'#7c3aed'],
                        ];
                        foreach ($formats as $i => $f):
                            $fmtCol = $fmtColorMap[$f['id']] ?? ['bg'=>'#f3f4f6','icon'=>'#374151'];
                        ?>
                            <div class="rpt-format-card <?= $i === 0 ? 'rpt-format-card--selected' : '' ?>"
                                 onclick="selectFormat('<?= $f['id'] ?>')"
                                 id="fmt_<?= $f['id'] ?>">
                                <div class="rpt-format-top">
                                    <div class="rpt-format-icon" style="background:<?= $fmtCol['bg'] ?>;color:<?= $fmtCol['icon'] ?>">
                                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><?= $fmtIcons[$f['icon_key']] ?? '' ?></svg>
                                    </div>
                                    <span class="rpt-format-radio" id="radio_<?= $f['id'] ?>"></span>
                                </div>
                                <div class="rpt-format-name"><?= htmlspecialchars($f['title']) ?></div>
                                <div class="rpt-format-desc"><?= htmlspecialchars($f['desc']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </form><!-- #create-report-form -->

        <!-- Footer -->
        <div class="rpt-modal__footer">
            <div class="rpt-status-note">
                <span class="rpt-status-dot"></span>
                <b>System ready</b>
                <span class="rpt-sep">&bull;</span>
                <span>Data aggregation verified</span>
            </div>
            <div class="rpt-footer-actions">
                <a href="<?= ROOT ?>/reports" class="rpt-cancel-btn" id="btn-cancel">Cancel</a>
                <button type="submit" form="create-report-form" class="rpt-compile-btn" id="btn-compile">
                    Compile &amp; Preview Report
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </button>
            </div>
        </div>

    </div><!-- /.rpt-modal -->

</div><!-- /.rpt-create-wrap -->

<script>
/* ── Catalog data from PHP ── */
var typesByCategory = <?= json_encode($catalog) ?>;

function updateTypes() {
    var cat  = document.getElementById('rpt-category').value;
    var sel  = document.getElementById('rpt-type');
    sel.options.length = 0;
    var types = typesByCategory[cat] || [];
    for (var i = 0; i < types.length; i++) {
        sel.options[i] = new Option(types[i], types[i]);
    }
}

function updateDateInfo() {
    var from = document.getElementById('rpt-from').value;
    var to   = document.getElementById('rpt-to').value;
    var ok   = document.getElementById('dateInfo');
    var err  = document.getElementById('dateError');
    if (!from || !to) { ok.style.display = 'none'; err.style.display = 'none'; return; }
    var p1 = from.split('-'), p2 = to.split('-');
    var d1 = new Date(p1[0], p1[1]-1, p1[2]);
    var d2 = new Date(p2[0], p2[1]-1, p2[2]);
    var days = Math.round((d2 - d1) / 86400000) + 1;
    if (days > 0) {
        var fromQ = 'Q' + (Math.floor(d1.getMonth()/3)+1);
        var toQ   = 'Q' + (Math.floor(d2.getMonth()/3)+1);
        var label;
        if (d1.getFullYear() === d2.getFullYear() && fromQ === toQ) {
            label = fromQ + ' FY' + d1.getFullYear();
        } else if (d1.getFullYear() === d2.getFullYear()) {
            label = fromQ + ' - ' + toQ + ' FY' + d1.getFullYear();
        } else {
            label = fromQ + ' FY' + d1.getFullYear() + ' - ' + toQ + ' FY' + d2.getFullYear();
        }
        document.getElementById('dateLabel').textContent = label;
        document.getElementById('dateDays').textContent  = days;
        ok.style.display = 'flex'; err.style.display = 'none';
    } else {
        ok.style.display = 'none'; err.style.display = 'flex';
    }
}

function selectFormat(name) {
    var ids = ['PDF','CSV','OnScreen'];
    for (var i = 0; i < ids.length; i++) {
        var card = document.getElementById('fmt_' + ids[i]);
        if (!card) continue;
        if (ids[i] === name) {
            card.classList.add('rpt-format-card--selected');
        } else {
            card.classList.remove('rpt-format-card--selected');
        }
    }
    document.getElementById('formatValue').value = name;
}

updateDateInfo();
</script>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
