<?php
/**
 * National Dashboard — NYSC Administrator
 *
 * Uses the shared dashboard layout shell (dashboard-start / dashboard-end).
 */
$title                   = $title ?? 'National Dashboard — YouthNexus';
$pageTitle               = $pageTitle ?? 'National Dashboard';
$pageDescription         = $pageDescription ?? 'National governance overview';
$currentRoute            = $currentRoute ?? 'nationaldashboard';
$unreadNotificationCount = (int)($pendingApps ?? 0);

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

            <!-- Quick Actions -->
            <div class="nd-quick-actions">
                <a class="nd-quick-card" href="<?= ROOT ?>/manageevents">
                    <h3>Create Event</h3>
                    <p>Schedule a national-level event</p>
                </a>
                <a class="nd-quick-card" href="<?= ROOT ?>/audit">
                    <h3>View Overdue Audits</h3>
                    <p><?= (int)$overdueAudits ?> audits past due date</p>
                </a>
            </div>

            <!-- Stats Row -->
            <div class="nd-stats-row">
                <div class="nd-stat-card">
                    <div class="nd-stat-top">
                        <span class="nd-stat-label">All active clubs</span>
                        <div class="nd-stat-circle blue">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </div>
                    </div>
                    <div class="nd-stat-value"><?= number_format($totalYouth) ?></div>
                    <div class="nd-stat-desc">Total Registered Youth</div>
                </div>
                <div class="nd-stat-card">
                    <div class="nd-stat-top">
                        <span class="nd-stat-label">Across all zones</span>
                        <div class="nd-stat-circle orange">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        </div>
                    </div>
                    <div class="nd-stat-value"><?= number_format($totalClubs) ?></div>
                    <div class="nd-stat-desc">Total Active Clubs</div>
                </div>
                <div class="nd-stat-card">
                    <div class="nd-stat-top">
                        <span class="nd-stat-label">This year</span>
                        <div class="nd-stat-circle purple">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        </div>
                    </div>
                    <div class="nd-stat-value"><?= number_format($totalHours) ?></div>
                    <div class="nd-stat-desc">National Volunteer Hours</div>
                </div>
                <div class="nd-stat-card">
                    <div class="nd-stat-top">
                        <span class="nd-stat-label">Sum of active fund balances</span>
                        <div class="nd-stat-circle green">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 6h13a2 2 0 0 1 2 2v10H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h11"/><path d="M20 11h-5a2 2 0 0 0 0 4h5"/></svg>
                        </div>
                    </div>
                    <div class="nd-stat-value">LKR <?= number_format($totalFunds / 1000000, 1) ?>M</div>
                    <div class="nd-stat-desc">Total Funds Circulating</div>
                </div>
            </div>

            <!-- Middle Section -->
            <div class="nd-middle-section">
                <!-- Bar Chart Panel -->
                <div class="nd-panel nd-panel-left">
                    <div class="nd-panel-header">
                        <h3>Club Health Score Distribution</h3>
                        <a href="<?= ROOT ?>/clubhealth">View all &rsaquo;</a>
                    </div>
                    <div class="nd-bar-chart">
                        <?php
                        $maxH = max($health['Green'], $health['Yellow'], $health['Red'], 1);
                        $bars = [
                            ['label' => 'Healthy', 'val' => $health['Green'], 'color' => 'blue'],
                            ['label' => 'At Risk', 'val' => $health['Yellow'], 'color' => 'orange'],
                            ['label' => 'Dormant', 'val' => $health['Red'], 'color' => 'red'],
                        ];
                        foreach ($bars as $b):
                            $h = $b['val'] > 0 ? round(($b['val'] / $maxH) * 140) : 20;
                        ?>
                        <div class="nd-bar-wrap">
                            <div class="nd-bar-value"><?= $b['val'] ?></div>
                            <div class="nd-bar nd-bar-<?= $b['color'] ?>" style="height:<?= $h ?>px;"></div>
                            <div class="nd-bar-label"><?= $b['label'] ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Bottlenecks Panel -->
                <div class="nd-panel nd-panel-right">
                    <div class="nd-panel-header">
                        <h3>Administrative Bottlenecks</h3>
                    </div>
                    <ul class="nd-bottleneck-list">
                        <li>
                            <span class="nd-b-dot orange"></span>
                            <div class="nd-b-info">
                                <h4>Pending Club Registrations</h4>
                                <p><?= (int)$pendingApps ?> awaiting approval</p>
                            </div>
                        </li>
                        <li>
                            <span class="nd-b-dot red"></span>
                            <div class="nd-b-info">
                                <h4>Overdue Asset Audits</h4>
                                <p><?= (int)$overdueAudits ?> clubs not completed</p>
                            </div>
                        </li>
                        <li>
                            <span class="nd-b-dot red"></span>
                            <div class="nd-b-info">
                                <h4>Unresolved Red Flags</h4>
                                <p><?= (int)$unresolvedFlags ?> flagged ledger entries</p>
                            </div>
                        </li>
                        <li>
                            <span class="nd-b-dot brown"></span>
                            <div class="nd-b-info">
                                <h4>Divisions Missing Reports</h4>
                                <p><?= (int)$missingReports ?> divisions</p>
                            </div>
                        </li>
                    </ul>
                    <a href="<?= ROOT ?>/nationalanalytics" class="nd-view-link">View Analytics &rsaquo;</a>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="nd-activity-panel">
                <h3>Recent National Activity</h3>
                <ul class="nd-activity-list">
                    <?php foreach ($activities as $act): ?>
                    <li>
                        <div class="nd-activity-left">
                            <span class="nd-a-dot"></span>
                            <span class="nd-activity-text"><?= htmlspecialchars($act->details ?? '') ?></span>
                        </div>
                        <span class="nd-activity-time"><?= htmlspecialchars($act->timestamp ?? '') ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/nationaldashboard.css">
<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
