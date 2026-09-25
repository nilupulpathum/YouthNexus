<?php
/**
 * Events View — W.10
 */
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$pageStyles = [ROOT . '/assets/css/events.css'];

require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<div class="events-container">
    <!-- Filter Sidebar -->
    <aside class="events-filters">
        <div class="filter-group">
            <h3>PERSONAL RSVP</h3>
            <label class="filter-option is-active">
                <input type="radio" name="rsvp" value="all" checked>
                <span>All Events</span>
            </label>
            <label class="filter-option">
                <input type="radio" name="rsvp" value="attending">
                <span>Events I'm Attending</span>
            </label>
            <label class="filter-option">
                <input type="radio" name="rsvp" value="declined">
                <span>Events I Declined</span>
            </label>
            <label class="filter-option">
                <input type="radio" name="rsvp" value="no-response">
                <span>Events I Haven't Responded To</span>
            </label>
        </div>

        <div class="filter-group">
            <h3>LEVEL</h3>
            <label class="filter-option">
                <input type="checkbox" name="level[]" value="all" checked>
                <span>All Levels</span>
            </label>
            <label class="filter-option">
                <input type="checkbox" name="level[]" value="national">
                <span>National</span>
            </label>
            <label class="filter-option">
                <input type="checkbox" name="level[]" value="zonal">
                <span>Zonal</span>
            </label>
            <label class="filter-option">
                <input type="checkbox" name="level[]" value="divisional">
                <span>Divisional</span>
            </label>
            <label class="filter-option">
                <input type="checkbox" name="level[]" value="club">
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
            <label class="filter-option">
                <input type="radio" name="date" value="this-month">
                <span>This Month</span>
            </label>
        </div>

        <div class="filter-group">
            <h3>STATUS</h3>
            <label class="filter-option">
                <input type="checkbox" name="status[]" value="all" checked>
                <span>All Events</span>
            </label>
            <label class="filter-option">
                <input type="checkbox" name="status[]" value="pending">
                <span>Pending Approval</span>
            </label>
            <label class="filter-option">
                <input type="checkbox" name="status[]" value="upcoming">
                <span>Upcoming</span>
            </label>
            <label class="filter-option">
                <input type="checkbox" name="status[]" value="completed">
                <span>Completed</span>
            </label>
        </div>

        <button type="button" class="clear-filters-btn">
            <span class="icon"><?= yn_icon('close') ?></span> Clear all filters
        </button>
    </aside>

    <!-- Main Content -->
    <main class="events-main">
        <header class="events-header">
            <div class="results-info">
                Showing <strong><?= count($events) ?></strong> events
            </div>
            <div class="sort-control">
                <span>Sort by:</span>
                <select class="sort-select">
                    <option value="upcoming">Date (Upcoming first)</option>
                    <option value="recent">Date (Recently posted)</option>
                    <option value="attendance-high">Highest Attendance</option>
                    <option value="attendance-low">Lowest Attendance</option>
                </select>
            </div>
        </header>

        <div class="events-grid">
            <?php foreach ($events as $item): ?>
                <?php require __DIR__ . '/../partials/event-card.view.php'; ?>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<!-- Event Detail Popup -->
<div id="event-popup" class="popup-overlay" hidden>
    <div class="popup-content event-popup-content">
        <button type="button" class="popup-close" aria-label="Close"><?= yn_icon('close') ?></button>
        <div class="popup-header">
            <div class="popup-countdown" id="popup-remaining"></div>
            <h2 id="popup-title"></h2>
            <div class="popup-tags">
                <span id="popup-scope" class="scope-badge"></span>
                <span id="popup-status" class="status-badge"></span>
            </div>
            <div class="popup-info-grid">
                <div class="info-item">
                    <span class="icon"><?= yn_icon('calendar') ?></span>
                    <span id="popup-date"></span>
                </div>
                <div class="info-item">
                    <span class="icon"><?= yn_icon('pin') ?></span>
                    <span id="popup-location"></span>
                </div>
            </div>
        </div>
        <div class="popup-body">
            <h3>About this event</h3>
            <p id="popup-description"></p>
        </div>
        <div class="popup-footer">
            <button type="button" class="btn-participate">
                <span class="icon"><?= yn_icon('check') ?></span> Participate
            </button>
            <button type="button" class="btn-not-participate">
                <span class="icon"><?= yn_icon('close') ?></span> Not participate
            </button>
        </div>
    </div>
</div>


<script src="<?= ROOT ?>/assets/js/events.js"></script>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
