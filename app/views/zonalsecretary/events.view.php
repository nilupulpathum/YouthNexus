<?php
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$old = is_array($old ?? null) ? $old : [];
$errors = is_array($errors ?? null) ? $errors : [];
$eventStats = $eventStats ?? [];
$selected = static function ($option) use ($old) {
    return (($old['audience'] ?? 'All divisions') === $option) ? ' selected' : '';
};
$formatDate = static function ($event) {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', (string) ($event['date'] ?? ''));
    return $date ? $date->format('M j, Y') . ' at ' . ($event['time'] ?? '') : '';
};

require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<section class="club-page" aria-labelledby="zonal-events-heading">
    <h1 id="zonal-events-heading" class="sr-only">Zonal events</h1>

    <div class="club-stat-grid" aria-label="Zonal event summary">
        <article class="club-stat-card">
            <p class="club-stat-label">Scheduled events</p>
            <p class="club-stat-value"><?= $escape($eventStats['scheduled'] ?? 0) ?></p>
        </article>
        <article class="club-stat-card">
            <p class="club-stat-label">Budget committed</p>
            <p class="club-stat-value club-stat-value--small">LKR <?= number_format((float) ($eventStats['committed'] ?? 0), 0) ?></p>
        </article>
        <article class="club-stat-card">
            <p class="club-stat-label">Available event budget</p>
            <p class="club-stat-value club-stat-value--small">LKR <?= number_format((float) ($eventStats['available'] ?? 0), 0) ?></p>
        </article>
    </div>

    <?php if (!empty($flash)): ?>
        <div class="club-impact" role="status">
            <strong>Event scheduled</strong>
            <p><?= $escape($flash) ?></p>
        </div>
    <?php endif; ?>

    <section class="club-panel" aria-labelledby="zonal-events-list-heading">
        <div class="club-panel-header">
            <div>
                <p class="club-eyebrow">Gampaha Zone</p>
                <h2 id="zonal-events-list-heading">Events</h2>
            </div>
            <button type="button" class="club-btn-primary" id="zonal-event-open">Create Event</button>
        </div>

        <div class="club-filters" role="search" aria-label="Filter zonal events">
            <label class="club-search" for="zonal-event-search">
                <span class="icon"><?= yn_icon('eye') ?></span>
                <span class="sr-only">Search events</span>
                <input id="zonal-event-search" type="search" placeholder="Search title or location..." autocomplete="off">
            </label>
            <label class="club-filter-field">
                <span class="sr-only">Filter by status</span>
                <select id="zonal-event-status">
                    <option value="">All statuses</option>
                    <option value="approved">Approved</option>
                    <option value="pending approval">Pending approval</option>
                    <option value="changes requested">Changes requested</option>
                </select>
            </label>
            <label class="club-filter-field">
                <span class="sr-only">Filter by type</span>
                <select id="zonal-event-type">
                    <option value="">All types</option>
                    <option value="Leadership">Leadership</option>
                    <option value="Training">Training</option>
                    <option value="Community Service">Community Service</option>
                    <option value="Workshop">Workshop</option>
                    <option value="Meeting">Meeting</option>
                </select>
            </label>
        </div>

        <div class="club-list" id="zonal-event-list">
            <?php foreach ($events as $event): ?>
                <article class="club-list-item club-event-item"
                    data-search="<?= $escape(strtolower(($event['title'] ?? '') . ' ' . ($event['location'] ?? ''))) ?>"
                    data-status="<?= $escape(strtolower($event['status'] ?? 'pending approval')) ?>"
                    data-type="<?= $escape($event['type'] ?? '') ?>">
                    <div class="club-list-icon" aria-hidden="true"><?= yn_icon('calendar') ?></div>
                    <div class="club-list-copy">
                        <h3><?= $escape($event['title'] ?? '') ?></h3>
                        <p><span class="icon"><?= yn_icon('calendar') ?></span> <?= $escape($formatDate($event)) ?> <span class="icon"><?= yn_icon('pin') ?></span> <?= $escape($event['location'] ?? '') ?></p>
                        <p class="club-event-meta"><?= $escape($event['type'] ?? '') ?> · Budget LKR <?= number_format((float) ($event['budget'] ?? 0), 2) ?> · <?= $escape($event['audience'] ?? '') ?></p>
                    </div>
                    <div class="club-event-side">
                        <span class="club-pill club-pill--<?= ($event['status'] ?? '') === 'Pending approval' ? 'pending' : (($event['status'] ?? '') === 'Changes requested' ? 'voided' : 'approved') ?>"><?= $escape($event['status'] ?? 'Pending approval') ?></span>
                        <?php if (!empty($event['coordinator_remark'])): ?><p class="club-note">Coordinator remark: <?= $escape($event['coordinator_remark']) ?></p><?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <p id="zonal-event-empty" class="club-note" hidden>No events match these filters.</p>
    </section>
</section>

<div id="zonal-event-modal" class="popup-overlay"<?= !empty($errors) ? '' : ' hidden' ?>>
    <div class="popup-content club-modal" role="dialog" aria-modal="true" aria-labelledby="zonal-event-modal-title">
        <button type="button" class="popup-close" data-close aria-label="Close"><?= yn_icon('close') ?></button>
        <p class="club-eyebrow">Zonal secretary action</p>
        <h2 id="zonal-event-modal-title">Create Event</h2>
        <p class="club-sub-note">New events are submitted to the Zonal Coordinator for approval before divisions and clubs are notified.</p>
        <form id="zonal-event-form" class="club-form" method="post" action="<?= ROOT ?>/zonalsecretary/events" novalidate>
            <input type="hidden" name="csrf_token" value="<?= $escape($csrf_token) ?>">
            <?php if (!empty($errors['general'])): ?>
                <div class="club-error-banner" role="alert">
                    <span class="club-error-icon" aria-hidden="true"><?= yn_icon('info') ?></span>
                    <div><strong>Unable to schedule this event</strong><p><?= $escape($errors['general']) ?></p></div>
                </div>
            <?php endif; ?>
            <div id="zonal-event-date-banner" class="club-error-banner" hidden>
                <span class="club-error-icon" aria-hidden="true"><?= yn_icon('info') ?></span>
                <div><strong>Event date must be in the future</strong><p>Choose a future date and time to continue.</p></div>
            </div>
            <div class="club-form-grid">
                <div class="club-field club-field--full">
                    <label for="zonal-event-title">Event title</label>
                    <input id="zonal-event-title" name="title" type="text" required maxlength="150" autocomplete="off" placeholder="Zone Youth Leadership Forum" value="<?= $escape($old['title'] ?? '') ?>">
                    <?php if (!empty($errors['title'])): ?><p class="club-field-error"><?= $escape($errors['title']) ?></p><?php endif; ?>
                </div>
                <div class="club-field">
                    <label for="zonal-event-date">Date</label>
                    <input id="zonal-event-date" name="event_date" type="date" required value="<?= $escape($old['event_date'] ?? '') ?>">
                    <p id="zonal-event-date-error" class="club-field-error"<?= !empty($errors['event_date']) ? '' : ' hidden' ?>><?= $escape($errors['event_date'] ?? 'This date has already passed') ?></p>
                </div>
                <div class="club-field">
                    <label for="zonal-event-time">Time</label>
                    <input id="zonal-event-time" name="event_time" type="time" required value="<?= $escape($old['event_time'] ?? '') ?>">
                    <?php if (!empty($errors['event_time'])): ?><p class="club-field-error"><?= $escape($errors['event_time']) ?></p><?php endif; ?>
                </div>
                <div class="club-field club-field--full">
                    <label for="zonal-event-location">Location</label>
                    <input id="zonal-event-location" name="location" type="text" required maxlength="255" autocomplete="off" placeholder="Venue or coordinates" value="<?= $escape($old['location'] ?? '') ?>">
                    <?php if (!empty($errors['location'])): ?><p class="club-field-error"><?= $escape($errors['location']) ?></p><?php endif; ?>
                </div>
                <div class="club-field">
                    <label for="zonal-event-type-input">Event type</label>
                    <select id="zonal-event-type-input" name="event_type" required>
                        <option value="">Select type...</option>
                        <?php foreach (['Leadership', 'Training', 'Community Service', 'Workshop', 'Meeting'] as $type): ?>
                            <option<?= ($old['event_type'] ?? '') === $type ? ' selected' : '' ?>><?= $escape($type) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['event_type'])): ?><p class="club-field-error"><?= $escape($errors['event_type']) ?></p><?php endif; ?>
                </div>
                <div class="club-field">
                    <label for="zonal-event-budget">Estimated budget (LKR)</label>
                    <input id="zonal-event-budget" name="budget" type="number" required min="1" step="0.01" placeholder="0.00" value="<?= $escape($old['budget'] ?? '') ?>">
                    <?php if (!empty($errors['budget'])): ?><p class="club-field-error"><?= $escape($errors['budget']) ?></p><?php endif; ?>
                </div>
                <div class="club-field club-field--full">
                    <label for="zonal-event-audience">Notify</label>
                    <select id="zonal-event-audience" name="audience" required>
                        <option value="All divisions"<?= $selected('All divisions') ?>>All divisions and their clubs</option>
                        <option value="Gampaha Division"<?= $selected('Gampaha Division') ?>>Gampaha Division and its clubs</option>
                        <option value="Ja-Ela Division"<?= $selected('Ja-Ela Division') ?>>Ja-Ela Division and its clubs</option>
                        <option value="Negombo Division"<?= $selected('Negombo Division') ?>>Negombo Division and its clubs</option>
                    </select>
                    <?php if (!empty($errors['audience'])): ?><p class="club-field-error"><?= $escape($errors['audience']) ?></p><?php endif; ?>
                </div>
            </div>
            <p id="zonal-event-error" class="club-form-error" hidden></p>
            <div class="club-modal-footer">
                <button type="button" class="club-btn-secondary" data-close>Cancel</button>
                <button type="submit" class="club-btn-primary">Create Event</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('zonal-event-list');
    const search = document.getElementById('zonal-event-search');
    const status = document.getElementById('zonal-event-status');
    const type = document.getElementById('zonal-event-type');
    const empty = document.getElementById('zonal-event-empty');
    const applyFilters = () => {
        let visible = 0;
        list.querySelectorAll('.club-event-item').forEach((item) => {
            const matchesSearch = !search.value.trim() || item.dataset.search.includes(search.value.trim().toLowerCase());
            const matchesStatus = !status.value || item.dataset.status === status.value;
            const matchesType = !type.value || item.dataset.type.toLowerCase() === type.value.toLowerCase();
            item.hidden = !(matchesSearch && matchesStatus && matchesType);
            if (!item.hidden) visible += 1;
        });
        empty.hidden = visible !== 0;
    };
    [search, status, type].forEach((element) => {
        element.addEventListener('input', applyFilters);
        element.addEventListener('change', applyFilters);
    });

    const modal = document.getElementById('zonal-event-modal');
    const open = document.getElementById('zonal-event-open');
    const form = document.getElementById('zonal-event-form');
    const date = document.getElementById('zonal-event-date');
    const time = document.getElementById('zonal-event-time');
    const dateBanner = document.getElementById('zonal-event-date-banner');
    const dateError = document.getElementById('zonal-event-date-error');
    const formError = document.getElementById('zonal-event-error');
    const close = () => { modal.hidden = true; document.body.style.overflow = ''; };
    const clearDateError = () => {
        dateBanner.hidden = true;
        dateError.hidden = true;
        date.classList.remove('is-invalid');
    };
    open.addEventListener('click', () => { form.reset(); formError.hidden = true; clearDateError(); modal.hidden = false; document.body.style.overflow = 'hidden'; });
    modal.querySelectorAll('[data-close]').forEach((button) => button.addEventListener('click', close));
    modal.addEventListener('click', (event) => { if (event.target === modal) close(); });
    date.addEventListener('input', clearDateError);
    time.addEventListener('input', clearDateError);
    form.addEventListener('submit', (event) => {
        if (!form.checkValidity()) {
            event.preventDefault();
            formError.textContent = 'Complete all required fields.';
            formError.hidden = false;
            return;
        }
        const eventAt = new Date(date.value + 'T' + time.value);
        if (Number.isNaN(eventAt.getTime()) || eventAt <= new Date()) {
            event.preventDefault();
            dateBanner.hidden = false;
            dateError.hidden = false;
            date.classList.add('is-invalid');
            date.focus();
        }
    });
});
</script>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/club.css">

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
