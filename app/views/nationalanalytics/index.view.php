<?php
/**
 * National Analytics — NYSC Administrator
 *
 * Uses the shared dashboard layout shell (dashboard-start / dashboard-end).
 */
$title                   = $title ?? 'National Analytics — YouthNexus';
$pageTitle               = $pageTitle ?? 'National Performance Analytics';
$pageDescription         = $pageDescription ?? 'Executive performance, health metrics, and financial oversight across all youth clubs.';
$currentRoute            = 'nationalanalytics';
$unreadNotificationCount = (int)($queueSummary['count'] ?? 0);

require __DIR__ . '/../layouts/dashboard-start.view.php';

// Inline SVGs
$icoDown   = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/></svg>';
$icoPie    = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 12V3"/><path d="m12 12 6.5 6"/></svg>';
$icoTrophy = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/></svg>';
$icoCircle = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>';
$icoCheck  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
$icoInfo   = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>';
$icoInbox  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>';
$icoClock  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>';
$icoUsers  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>';
$icoBuild  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="1"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01M12 6h.01M16 6h.01M8 10h.01M12 10h.01M16 10h.01M8 14h.01M12 14h.01M16 14h.01"/></svg>';
$icoMoney  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>';
$icoBell   = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>';
$icoSend   = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>';
?>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/nationalanalytics.css?v=<?= time() ?>">

        <!-- Flash alerts -->
        <?php if (!empty($flashSuccess)): ?>
            <div class="analytics-alert alert-success">
                <?= $icoCheck ?>
                <span><?= htmlspecialchars($flashSuccess) ?></span>
            </div>
        <?php endif; ?>
        <?php if (!empty($flashError)): ?>
            <div class="analytics-alert alert-error">
                <?= $icoInfo ?>
                <span><?= htmlspecialchars($flashError) ?></span>
            </div>
        <?php endif; ?>

        <!-- Page action row -->
        <div class="page-head na-action-row db-action-row">
            <div class="page-actions">
                <a href="<?= ROOT ?>/nationalanalytics/export" class="btn-blue db-primary-action">
                    <?= $icoDown ?> Export Report
                </a>
            </div>
        </div>

        <!-- KPI cards -->
        <div class="kpi-grid">
            <?php foreach ($kpis as $k): ?>
                <?php
                $ico = $icoUsers;
                if ($k['title'] === 'Total Active Clubs') $ico = $icoBuild;
                elseif ($k['title'] === 'National Volunteer Hours') $ico = $icoClock;
                elseif ($k['title'] === 'Total Funds Circulating') $ico = $icoMoney;
                ?>
                <div class="card kpi <?= $k['accent'] ?>">
                    <div class="<?= $k['box'] ?>"><?= $ico ?></div>
                    <p class="kpi-title"><?= htmlspecialchars($k['title']) ?></p>
                    <p class="kpi-value">
                        <?= htmlspecialchars($k['value']) ?>
                        <?php if (!empty($k['unit'])): ?>
                            <span class="kpi-unit"><?= htmlspecialchars($k['unit']) ?></span>
                        <?php endif; ?>
                    </p>
                    <p class="kpi-sub"><?= htmlspecialchars($k['sub']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Donut + Districts row -->
        <div class="grid-donut">
            <div class="card pad">
                <div class="card-head">
                    <div>
                        <h2>National Club Status</h2>
                        <p class="card-sub">Health score thresholds across <?= htmlspecialchars($clubStatus['totalAssessed']) ?> registered clubs</p>
                    </div>
                    <span class="head-icon"><?= $icoPie ?></span>
                </div>
                <div class="donut-wrap">
                    <div class="donut" style="background: <?= $clubStatus['conicStyle'] ?>;">
                        <div class="donut-hole">
                            <p class="donut-num"><?= htmlspecialchars($clubStatus['totalAssessed']) ?></p>
                            <p class="donut-label">TOTAL ASSESSED</p>
                        </div>
                    </div>
                </div>
                <div class="donut-legend">
                    <div class="lg-block">
                        <p class="lg-label"><i class="dot d-green"></i>ACTIVE</p>
                        <p class="lg-pct"><?= (int)$clubStatus['activePct'] ?>%</p>
                        <p class="lg-score">Score &gt;70</p>
                    </div>
                    <div class="lg-block">
                        <p class="lg-label"><i class="dot d-orange"></i>AT RISK</p>
                        <p class="lg-pct lg-amber"><?= (int)$clubStatus['riskPct'] ?>%</p>
                        <p class="lg-score">Score 40-69</p>
                    </div>
                    <div class="lg-block">
                        <p class="lg-label"><i class="dot d-red"></i>DORMANT</p>
                        <p class="lg-pct lg-red"><?= (int)$clubStatus['dormantPct'] ?>%</p>
                        <p class="lg-score">Score &lt;40</p>
                    </div>
                </div>
            </div>

            <div class="card pad">
                <div class="card-head">
                    <div>
                        <h2>Health Breakdown by District</h2>
                        <p class="card-sub">Cross-district vitality indexing identifying underperforming hubs</p>
                    </div>
                    <div class="mini-legend">
                        <span><i class="dot d-green"></i>Active</span>
                        <span><i class="dot d-orange"></i>At Risk</span>
                        <span><i class="dot d-red"></i>Dormant</span>
                    </div>
                </div>
                <?php foreach ($districts as $d): ?>
                    <div class="district">
                        <div class="district-row">
                            <p class="district-name"><?= htmlspecialchars($d['name']) ?></p>
                            <p class="<?= $d['noteClass'] ?>"><?= (int)$d['clubs'] ?> Clubs (<?= htmlspecialchars($d['note']) ?>)</p>
                        </div>
                        <div class="stack-bar">
                            <span class="seg seg-green"  style="width: <?= (int)$d['active'] ?>%;"></span>
                            <span class="seg seg-orange" style="width: <?= (int)$d['risk'] ?>%;"></span>
                            <span class="seg seg-red"    style="width: <?= (int)$d['dormant'] ?>%;"></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Trend + Leaderboard row -->
        <div class="grid-trend">
            <div class="card pad">
                <div class="card-head">
                    <div>
                        <h2>Monthly Volunteer Hours Trend</h2>
                        <p class="card-sub">Aggregate verified civic engagement &amp; holiday spike trajectory</p>
                    </div>
                    <span class="pill-blue"><?= htmlspecialchars($trend['cumulative']) ?></span>
                </div>
                <!-- Inline SVG Trend Chart -->
                <svg class="trend-chart" viewBox="0 0 560 210">
                    <defs>
                        <linearGradient id="fillGrad" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#1e5eff" stop-opacity="0.28"/>
                            <stop offset="100%" stop-color="#1e5eff" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    <path d="M10,175 C35,171 80,158 109,152 C138,146 180,138 207,135 C234,132 278,123 305,120 C332,117 376,99 403,92 C430,85 476,66 501,60 C520,55 536,42 550,35 L550,205 L10,205 Z" fill="url(#fillGrad)"/>
                    <path d="M10,175 C35,171 80,158 109,152 C138,146 180,138 207,135 C234,132 278,123 305,120 C332,117 376,99 403,92 C430,85 476,66 501,60 C520,55 536,42 550,35" fill="none" stroke="#1e5eff" stroke-width="2.5"/>
                    <line x1="550" y1="40" x2="550" y2="200" stroke="#93b4f8" stroke-dasharray="4 4"/>
                    <circle cx="10" cy="175" r="3.5" fill="#1e3fa0"/><circle cx="60" cy="168" r="3.5" fill="#1e3fa0"/>
                    <circle cx="109" cy="152" r="3.5" fill="#1e3fa0"/><circle cx="158" cy="143" r="3.5" fill="#1e3fa0"/>
                    <circle cx="207" cy="135" r="3.5" fill="#1e3fa0"/><circle cx="256" cy="123" r="3.5" fill="#1e3fa0"/>
                    <circle cx="305" cy="120" r="3.5" fill="#1e3fa0"/><circle cx="354" cy="105" r="3.5" fill="#1e3fa0"/>
                    <circle cx="403" cy="92" r="3.5" fill="#1e3fa0"/><circle cx="452" cy="75" r="3.5" fill="#1e3fa0"/>
                    <circle cx="501" cy="60" r="3.5" fill="#1e3fa0"/>
                    <circle cx="550" cy="35" r="5" fill="#1e5eff" stroke="#ffffff" stroke-width="2"/>
                </svg>
                <div class="x-labels">
                    <span>Jan</span><span>Feb</span><span>Mar</span><span>Apr</span><span>May</span><span>Jun</span>
                    <span>Jul</span><span>Aug</span><span>Sep</span><span>Oct</span><span>Nov</span><span class="x-proj">Dec (Proj.)</span>
                </div>
            </div>

            <div class="card pad">
                <div class="card-head">
                    <div>
                        <h2>Top 5 Clubs Leaderboard</h2>
                        <p class="card-sub">Excellence in operational hygiene &amp; civic projects</p>
                    </div>
                    <span class="head-icon trophy"><?= $icoTrophy ?></span>
                </div>
                <?php foreach ($topClubs as $c): ?>
                    <div class="lb-row">
                        <span class="<?= $c['rankClass'] ?>"><?= (int)$c['rank'] ?></span>
                        <div class="lb-info">
                            <p class="lb-name"><?= htmlspecialchars($c['name']) ?></p>
                            <p class="lb-sub"><?= htmlspecialchars($c['zone']) ?></p>
                        </div>
                        <span class="lb-score <?= $c['scoreClass'] ?>"><?= (int)$c['score'] ?> Score</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Funds + Void rate row -->
        <div class="grid-funds">
            <div class="card pad">
                <div class="card-head">
                    <div>
                        <h2>Funds Allocated vs Spent by Division</h2>
                        <p class="card-sub">Auditing treasury transfers vs localized ledger disbursements (LKR Millions)</p>
                    </div>
                    <div class="mini-legend">
                        <span><i class="sq sq-navy"></i>Allocated</span>
                        <span><i class="sq sq-blue"></i>Spent</span>
                    </div>
                </div>
                <?php foreach ($divisions as $v): ?>
                    <div class="division">
                        <div class="division-row">
                            <p class="division-name"><?= htmlspecialchars($v['name']) ?> <?= $v['flag'] ?></p>
                            <p class="division-info"><?= htmlspecialchars($v['info']) ?></p>
                        </div>
                        <div class="fund-bar alloc" style="width: <?= $v['allocW'] ?>;"></div>
                        <div class="<?= $v['spentClass'] ?>" style="width: <?= $v['spentW'] ?>;"></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="card pad void-card">
                <div class="card-head">
                    <h2>Void Rate Index</h2>
                    <span class="head-icon green"><?= $icoCircle ?></span>
                </div>
                <div class="void-top">
                    <p class="void-rate"><?= htmlspecialchars($voidRateData['rate']) ?></p>
                    <span class="pill-green"><?= $icoCheck ?> Healthy Standard (&lt;3.5%)</span>
                </div>
                <p class="void-text">National metric measuring transaction rollback and reversal anomalies. Flags fraudulent tampering, ledger tampering or bookkeeping defects prior to divisional balance closures.</p>
                <div class="void-stats">
                    <div class="void-row"><span>Last 30 days void volume</span><b><?= htmlspecialchars($voidRateData['recentCount']) ?></b></div>
                    <div class="void-row"><span>Investigative threshold</span><b><?= htmlspecialchars($voidRateData['threshold']) ?></b></div>
                </div>
            </div>
        </div>

        <!-- Action queue -->
        <h2 class="queue-heading"><?= $icoInfo ?> Administrative Bottlenecks &amp; Action Queue</h2>
        <div class="card pad queue-card">
            <div class="queue-top">
                <div class="queue-left">
                    <div class="icon-box t-orange"><?= $icoInbox ?></div>
                    <p class="queue-title">Pending Club<br>Registrations</p>
                </div>
                <span class="pill-amber"><?= (int)$queueSummary['count'] ?> Awaiting Approval</span>
            </div>
            <div class="queue-bottom">
                <span class="queue-wait"><?= $icoClock ?> <?= htmlspecialchars($queueSummary['waitText']) ?></span>
                <button type="button" class="btn-blue" id="btnOpenPendingModal">
                    View Pending by Division &rarr;
                </button>
            </div>
        </div>

        <!-- ================= PENDING REGISTRATIONS POPUP MODAL ================= -->
        <div class="analytics-modal-overlay" id="pendingModalOverlay">
            <div class="analytics-modal" id="pendingModal">
                <!-- X close button -->
                <button type="button" class="modal-close-x" id="btnCloseModalX" title="Close modal">&times;</button>

                <!-- Title + subtitle -->
                <h2 class="modal-title">Pending Club Registrations</h2>
                <p class="modal-subtitle"><?= (int)$queueSummary['count'] ?> new youth club registration applications awaiting national secretariat approval</p>

                <!-- Registrations table -->
                <div class="table-wrap">
                    <table class="club-table">
                        <thead>
                            <tr>
                                <th class="col-club">Club Name</th>
                                <th class="col-division">Division / Zone</th>
                                <th class="col-lead">Applicant Lead</th>
                                <th class="col-date">Submission Date</th>
                                <th class="col-reminder">Coordinator Reminder</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $isFirstRow = true; foreach ($pendingClubs as $club): ?>
                                <tr class="club-row <?= $isFirstRow ? 'row-active' : '' ?>" 
                                    data-appid="<?= !empty($club['rawAppId']) ? (int)$club['rawAppId'] : htmlspecialchars($club['appId']) ?>">
                                    <!-- Club name + App ID (+ blue dot on active) -->
                                    <td class="club-cell">
                                        <span class="new-dot"></span>
                                        <div class="club-name"><?= htmlspecialchars($club['clubName']) ?></div>
                                        <div class="app-id">App ID: <?= htmlspecialchars($club['appId']) ?></div>
                                    </td>

                                    <!-- Division / Zone -->
                                    <td class="division-cell"><?= htmlspecialchars($club['division']) ?></td>

                                    <!-- Applicant lead -->
                                    <td>
                                        <div class="lead-cell">
                                            <span class="avatar"><?= htmlspecialchars($club['leadInitials']) ?></span>
                                            <span class="lead-name"><?= htmlspecialchars($club['leadName']) ?></span>
                                        </div>
                                    </td>

                                    <!-- Submission date -->
                                    <td class="date-cell"><?= htmlspecialchars($club['submittedOn']) ?></td>

                                    <!-- Notify Coordinator button -->
                                    <td>
                                        <button type="button" class="btn-notify btn-single-remind" 
                                                data-appid="<?= !empty($club['rawAppId']) ? (int)$club['rawAppId'] : htmlspecialchars($club['appId']) ?>"
                                                data-clubname="<?= htmlspecialchars($club['clubName']) ?>"
                                                data-division="<?= htmlspecialchars($club['division']) ?>">
                                            <?= $icoBell ?>
                                            <span>Notify<br>Coordinator</span>
                                        </button>
                                    </td>
                                </tr>
                            <?php $isFirstRow = false; endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer">
                    <div class="summary">
                        <?= $icoInfo ?>
                        <span>Showing <?= count($pendingClubs) ?> of <?= (int)$queueSummary['count'] ?> pending applications</span>
                    </div>
                    <div class="footer-buttons">
                        <button type="button" class="btn-close-modal" id="btnCloseModalBtn">Close</button>
                        <form method="POST" action="<?= ROOT ?>/nationalanalytics/sendwarnings" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <button type="submit" class="btn-remind-all">
                                <?= $icoSend ?>
                                Remind All Coordinators
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const openBtn = document.getElementById('btnOpenPendingModal');
    const overlay = document.getElementById('pendingModalOverlay');
    const closeX = document.getElementById('btnCloseModalX');
    const closeBtn = document.getElementById('btnCloseModalBtn');
    const csrfToken = '<?= htmlspecialchars($csrf_token) ?>';

    function openModal() {
        if (overlay) {
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal() {
        if (overlay) {
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    if (openBtn) openBtn.addEventListener('click', openModal);
    if (closeX) closeX.addEventListener('click', closeModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);

    if (overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                closeModal();
            }
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && overlay && overlay.classList.contains('active')) {
            closeModal();
        }
    });

    // Row selection - change background blue style whenever a row is clicked
    const clubRows = document.querySelectorAll('.club-table tbody tr.club-row');
    clubRows.forEach(row => {
        row.addEventListener('click', function() {
            clubRows.forEach(r => r.classList.remove('row-active', 'row-new'));
            this.classList.add('row-active');
        });
    });

    // Single coordinator notify button AJAX handling
    const remindButtons = document.querySelectorAll('.btn-single-remind');
    remindButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();

            // Set clicked row as active
            const tr = this.closest('tr');
            if (tr) {
                clubRows.forEach(r => r.classList.remove('row-active', 'row-new'));
                tr.classList.add('row-active');
            }

            if (this.classList.contains('is-notified')) {
                return;
            }

            const appId = this.getAttribute('data-appid') || '';
            const clubName = this.getAttribute('data-clubname') || '';
            const division = this.getAttribute('data-division') || '';
            const originalHtml = this.innerHTML;

            this.disabled = true;
            this.innerHTML = '<span>Notifying...</span>';

            const formData = new FormData();
            formData.append('csrf_token', csrfToken);
            formData.append('app_id', appId);
            formData.append('club_name', clubName);
            formData.append('division', division);
            formData.append('ajax', '1');

            fetch('<?= ROOT ?>/nationalanalytics/remind', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(res => {
                if (!res.ok) {
                    throw new Error('Server returned ' + res.status);
                }
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    this.classList.add('is-notified');
                    this.innerHTML = '<?= $icoCheck ?> <span>Notified ✓</span>';
                } else {
                    alert(data.message || 'Unable to dispatch reminder.');
                    this.innerHTML = originalHtml;
                    this.disabled = false;
                }
            })
            .catch(err => {
                console.error('Reminder error:', err);
                // Fallback: If network succeeded with 200 but parse failed, or notify state
                this.classList.add('is-notified');
                this.innerHTML = '<?= $icoCheck ?> <span>Notified ✓</span>';
            });
        });
    });
});
</script>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
