<?php
/**
 * Announcements View — W.14
 */
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<div class="announcements-container">
    <!-- Filter Sidebar -->
    <aside class="announcements-filters">
        <div class="filter-group">
            <h3>LEVEL</h3>
            <label class="filter-option">
                <input type="radio" name="level" value="all" checked>
                <span>All Levels</span>
            </label>
            <label class="filter-option">
                <input type="radio" name="level" value="national">
                <span>National</span>
            </label>
            <label class="filter-option">
                <input type="radio" name="level" value="zonal">
                <span>Zonal</span>
            </label>
            <label class="filter-option">
                <input type="radio" name="level" value="divisional">
                <span>Divisional</span>
            </label>
            <label class="filter-option">
                <input type="radio" name="level" value="club">
                <span>Club-specific</span>
            </label>
        </div>

        <div class="filter-group">
            <h3>DATE POSTED</h3>
            <label class="filter-option">
                <input type="radio" name="date" value="all" checked>
                <span>All Time</span>
            </label>
            <label class="filter-option">
                <input type="radio" name="date" value="7days">
                <span>Last 7 Days</span>
            </label>
            <label class="filter-option">
                <input type="radio" name="date" value="30days">
                <span>Last 30 Days</span>
            </label>
            <label class="filter-option">
                <input type="radio" name="date" value="3months">
                <span>Last 3 Months</span>
            </label>
        </div>

        <div class="filter-group">
            <h3>READ STATUS</h3>
            <label class="filter-option">
                <input type="radio" name="status" value="all" checked>
                <span>All Status</span>
            </label>
            <label class="filter-option">
                <input type="radio" name="status" value="read">
                <span>Read</span>
            </label>
            <label class="filter-option">
                <input type="radio" name="status" value="unread">
                <span>Unread</span>
            </label>
        </div>

        <button type="button" class="clear-filters-btn">
            <span class="icon"><?= yn_icon('close') ?></span> Clear all filters
        </button>

        <div class="sidebar-bottom-action">
            <button type="button" class="mark-all-read-btn">
                <span class="icon"><?= yn_icon('check') ?></span> Mark all as read
            </button>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="announcements-main">
        <header class="announcements-header">
            <div class="results-info">
                Showing <strong><?= count($announcements) ?></strong> announcements · <span class="unread-highlight"><?= $unreadCount ?> unread</span>
            </div>
            <div class="sort-control">
                <span>Sort by:</span>
                <select class="sort-select">
                    <option value="newest">Newest first</option>
                    <option value="oldest">Oldest first</option>
                </select>
            </div>
        </header>

        <div class="announcements-list">
            <?php
            $unread = array_filter($announcements, fn($a) => $a['is_unread']);
            $earlier = array_filter($announcements, fn($a) => !$a['is_unread']);
            ?>

            <?php if (!empty($unread)): ?>
                <section class="announcement-section">
                    <h2 class="section-title">Unread <span class="badge"><?= count($unread) ?></span></h2>
                    <?php foreach ($unread as $item): ?>
                        <?php require __DIR__ . '/../partials/announcement-card.view.php'; ?>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>

            <?php if (!empty($earlier)): ?>
                <section class="announcement-section">
                    <h2 class="section-title">Earlier</h2>
                    <?php foreach ($earlier as $item): ?>
                        <?php require __DIR__ . '/../partials/announcement-card.view.php'; ?>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Detail Popup -->
<div id="announcement-popup" class="popup-overlay" hidden>
    <div class="popup-content">
        <button type="button" class="popup-close" aria-label="Close"><?= yn_icon('close') ?></button>
        <div class="popup-header">
            <p class="popup-meta">Preview</p>
            <h2 id="popup-title"></h2>
            <div class="popup-tags">
                <span id="popup-scope" class="scope-badge"></span>
                <span id="popup-new" class="new-badge" hidden>New</span>
            </div>
            <p class="popup-date-info">
                <span class="icon"><?= yn_icon('calendar') ?></span> <span id="popup-date"></span>
                <span class="separator">·</span>
                <span class="icon"><?= yn_icon('clock') ?></span> <span id="popup-age"></span>
            </p>
        </div>
        <div class="popup-body">
            <p id="popup-summary"></p>
            <div id="popup-attachment-container" class="attachment-box" hidden>
                <div class="attachment-info">
                    <span class="icon"><?= yn_icon('file') ?></span>
                    <div>
                        <p id="popup-attachment-name"></p>
                        <span id="popup-attachment-size"></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="popup-footer">
            <button type="button" class="btn-download">
                <span class="icon"><?= yn_icon('download') ?></span> Download Attachment
            </button>
            <button type="button" class="btn-mark-read">
                <span class="icon"><?= yn_icon('check') ?></span> Mark as Read
            </button>
        </div>
    </div>
</div>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/announcements.css">
<script src="<?= ROOT ?>/assets/js/announcements.js"></script>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
