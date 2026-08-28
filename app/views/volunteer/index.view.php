<?php
/**
 * Volunteer Hours View — A.09
 */
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<div class="volunteer-container">
    <!-- Stats Overview -->
    <div class="volunteer-stats">
        <div class="v-stat-card">
            <span class="v-stat-label">TOTAL HOURS</span>
            <div class="v-stat-value"><?= $escape($stats['total']) ?>h</div>
        </div>
        <div class="v-stat-card approved">
            <span class="v-stat-label">APPROVED</span>
            <div class="v-stat-value"><?= $escape($stats['approved']) ?>h</div>
        </div>
        <div class="v-stat-card pending">
            <span class="v-stat-label">PENDING</span>
            <div class="v-stat-value"><?= $escape($stats['pending']) ?>h</div>
        </div>
        <div class="v-stat-card rejected">
            <span class="v-stat-label">REJECTED</span>
            <div class="v-stat-value"><?= $escape($stats['rejected']) ?>h</div>
        </div>
    </div>

    <div class="volunteer-content-grid">
        <!-- Submission Form -->
        <section class="volunteer-form-section">
            <div class="section-header">
                <h2>Submit Volunteer Hours</h2>
                <p>Submit evidence for verification by the club secretary.</p>
            </div>
            <form class="volunteer-form">
                <div class="form-group">
                    <label for="event-name">Event / Activity Name</label>
                    <input type="text" id="event-name" placeholder="e.g. Community Clean-up" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="event-date">Date</label>
                        <input type="date" id="event-date" required>
                    </div>
                    <div class="form-group">
                        <label for="event-hours">Hours</label>
                        <input type="number" id="event-hours" min="1" max="24" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Upload Evidence (Images/Certificates)</label>
                    <div class="file-upload-zone" id="drop-zone">
                        <span class="icon">☁</span>
                        <p>Drag and drop files here or <span>browse</span></p>
                        <input type="file" id="file-input" hidden multiple>
                    </div>
                </div>
                <button type="submit" class="btn-submit-hours">Send for Verification</button>
            </form>
        </section>

        <!-- History Table -->
        <section class="volunteer-history-section">
            <div class="section-header">
                <h2>Volunteer History</h2>
                <div class="header-actions">
                    <button class="btn-filter">Filter</button>
                    <button class="btn-export">Export</button>
                </div>
            </div>
            <div class="table-container">
                <table class="v-history-table">
                    <thead>
                        <tr>
                            <th>EVENT / ACTIVITY</th>
                            <th>DATE</th>
                            <th>HOURS</th>
                            <th>STATUS</th>
                            <th>ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $item): ?>
                            <tr>
                                <td>
                                    <div class="event-cell">
                                        <strong><?= $escape($item['event']) ?></strong>
                                        <?php if ($item['status_key'] === 'rejected'): ?>
                                            <span class="reason-tooltip" title="<?= $escape($item['reason']) ?>">ⓘ</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?= $escape($item['date']) ?></td>
                                <td><?= $escape($item['hours']) ?>h</td>
                                <td>
                                    <span class="status-pill <?= $item['status_key'] ?>">
                                        <?= $escape($item['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn-view-evidence">View</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/volunteer.css">
<script src="<?= ROOT ?>/assets/js/volunteer.js"></script>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
