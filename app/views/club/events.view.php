<?php
/**
 * Club Events — C4 full UI.
 * President: pending list + approve/request-changes decision modal
 * (mirrors eventapproval decision panel; remarks mandatory on
 * request-changes). Secretary: create-event form (title, date/time,
 * location, type, budget + future-date validation) + list.
 * Presentation-only: no DB writes; backend contract lands in C13.
 */
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';

$stats       = $stats ?? [];
$clubEvents  = $clubEvents ?? [];
$can_approve = !empty($can_approve);
$can_create  = !empty($can_create);
?>

<section class="club-page" aria-labelledby="club-events-heading">
    <h1 id="club-events-heading" class="sr-only">Club events</h1>

    <div class="club-stat-grid" aria-label="Event summary">
        <article class="club-stat-card">
            <p class="club-stat-label">Pending approval</p>
            <p class="club-stat-value"><?= $escape($stats['pending'] ?? 0) ?></p>
        </article>
        <article class="club-stat-card">
            <p class="club-stat-label">Approved</p>
            <p class="club-stat-value"><?= $escape($stats['approved'] ?? 0) ?></p>
        </article>
        <article class="club-stat-card">
            <p class="club-stat-label">Completed</p>
            <p class="club-stat-value"><?= $escape($stats['completed'] ?? 0) ?></p>
        </article>
    </div>

    <section class="club-panel" aria-labelledby="club-events-list-heading">
        <div class="club-panel-header">
            <div>
                <p class="club-eyebrow">Gampaha Youth Development Club</p>
                <h2 id="club-events-list-heading">Events</h2>
            </div>
        </div>

        <div class="club-filters" role="search" aria-label="Filter events">
            <label class="club-search" for="club-event-search">
                <span class="icon"><?= yn_icon('eye') ?></span>
                <span class="sr-only">Search events</span>
                <input id="club-event-search" type="search" placeholder="Search title or location..." autocomplete="off">
            </label>
            <label class="club-filter-field">
                <span class="sr-only">Filter by status</span>
                <select id="club-event-status">
                    <option value="">All statuses</option>
                    <option value="pending">Pending approval</option>
                    <option value="approved">Approved</option>
                    <option value="completed">Completed</option>
                </select>
            </label>
            <label class="club-filter-field">
                <span class="sr-only">Filter by type</span>
                <select id="club-event-type">
                    <option value="">All types</option>
                    <option value="workshop">Workshop</option>
                    <option value="meeting">Meeting</option>
                    <option value="community service">Community Service</option>
                    <option value="fundraiser">Fundraiser</option>
                </select>
            </label>
        </div>

        <div class="club-list" id="club-event-list">
            <?php foreach ($clubEvents as $e): ?>
                <article class="club-list-item club-event-item"
                    data-search="<?= $escape(strtolower(($e['title'] ?? '') . ' ' . ($e['location'] ?? ''))) ?>"
                    data-status="<?= $escape($e['status_key'] ?? 'pending') ?>"
                    data-type="<?= $escape(strtolower($e['type'] ?? '')) ?>"
                    data-title="<?= $escape($e['title'] ?? '') ?>"
                    data-meta="<?= $escape(($e['date'] ?? '') . ' · ' . ($e['location'] ?? '')) ?>"
                    data-submitter="<?= $escape($e['submitted_by'] ?? '') ?>">
                    <div class="club-list-icon" aria-hidden="true"><?= yn_icon('calendar') ?></div>
                    <div class="club-list-copy">
                        <h3><?= $escape($e['title'] ?? '') ?></h3>
                        <p><span class="icon"><?= yn_icon('calendar') ?></span> <?= $escape($e['date'] ?? '') ?> · <span class="icon"><?= yn_icon('pin') ?></span> <?= $escape($e['location'] ?? '') ?></p>
                        <p class="club-event-meta"><?= $escape($e['type'] ?? '') ?> · Budget <?= $escape($e['budget'] ?? '') ?> · Submitted by <?= $escape($e['submitted_by'] ?? '') ?></p>
                    </div>
                    <div class="club-event-side">
                        <span class="club-pill club-pill--<?= $escape($e['status_key'] ?? 'pending') ?>"><?= $escape($e['status'] ?? '') ?></span>
                        <?php if ($can_approve && (($e['status_key'] ?? '') === 'pending')): ?>
                            <button type="button" class="club-btn-small" data-action="review">Review</button>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <p id="club-event-empty" class="club-note" hidden>No events match these filters.</p>

        <?php if ($can_create): ?>
            <div class="club-divider" aria-hidden="true"></div>
            <div class="club-panel-sub" aria-labelledby="club-event-create-heading">
                <div>
                    <p class="club-eyebrow">Secretary action</p>
                    <h2 id="club-event-create-heading">Create Event</h2>
                    <p class="club-sub-note">New events enter as Pending Approval. The start date must be in the future.</p>
                </div>
                <form id="club-event-form" class="club-form" novalidate>
                    <div class="club-form-grid">
                        <div class="club-field club-field--full">
                            <label for="ev-title">Event title</label>
                            <input id="ev-title" name="title" type="text" required maxlength="150" autocomplete="off" placeholder="e.g., Youth First-Aid Training Day">
                        </div>
                        <div class="club-field">
                            <label for="ev-datetime">Date &amp; time</label>
                            <input id="ev-datetime" name="datetime" type="datetime-local" required>
                        </div>
                        <div class="club-field">
                            <label for="ev-location">Location</label>
                            <input id="ev-location" name="location" type="text" required maxlength="255" autocomplete="off" placeholder="e.g., Club Centre, Gampaha">
                        </div>
                        <div class="club-field">
                            <label for="ev-type">Event type</label>
                            <select id="ev-type" name="type" required>
                                <option value="">Select type...</option>
                                <option value="Workshop">Workshop</option>
                                <option value="Meeting">Meeting</option>
                                <option value="Community Service">Community Service</option>
                                <option value="Fundraiser">Fundraiser</option>
                            </select>
                        </div>
                        <div class="club-field">
                            <label for="ev-budget">Budget (Rs.)</label>
                            <input id="ev-budget" name="budget" type="number" required min="1" step="1" placeholder="e.g., 15000">
                        </div>
                    </div>
                    <p id="ev-error" class="club-form-error" hidden></p>
                    <div class="club-form-footer">
                        <button type="submit" class="club-btn-primary">Submit for approval</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </section>
</section>

<?php if ($can_approve): ?>
    <div id="event-decision-modal" class="popup-overlay" hidden>
        <div class="popup-content club-modal" role="dialog" aria-modal="true" aria-labelledby="ed-title">
            <button type="button" class="popup-close" data-close aria-label="Close"><?= yn_icon('close') ?></button>
            <p class="club-eyebrow">President decision</p>
            <h2 id="ed-title">Review event</h2>
            <p id="ed-meta" class="club-sub-note"></p>
            <p id="ed-submitter" class="club-sub-note"></p>
            <div class="club-field">
                <label for="ed-result">Review result</label>
                <select id="ed-result">
                    <option value="approve">Approve event</option>
                    <option value="request-changes">Request changes</option>
                </select>
            </div>
            <div class="club-field">
                <label for="ed-remarks">Official review remarks (required if requesting changes)</label>
                <textarea id="ed-remarks" rows="3" placeholder="Provide the reason for this decision..."></textarea>
            </div>
            <p id="ed-error" class="club-form-error" hidden></p>
            <div id="ed-impact" class="club-impact" role="note">
                <strong>Impact of approval</strong>
                <p>Approving publishes this event to the club calendar and notifies the secretary. It becomes visible for attendance tracking once it occurs.</p>
            </div>
            <div class="club-modal-footer">
                <button type="button" class="club-btn-secondary" data-close>Cancel</button>
                <button type="button" class="club-btn-primary" id="ed-confirm">Confirm &amp; submit decision</button>
            </div>
        </div>
    </div>
<?php endif; ?>

<div id="club-toast" class="club-toast" role="status" hidden></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('club-event-list');
    const search = document.getElementById('club-event-search');
    const statusSel = document.getElementById('club-event-status');
    const typeSel = document.getElementById('club-event-type');
    const empty = document.getElementById('club-event-empty');

    const applyFilters = () => {
        if (!list) return;
        const q = (search ? search.value.trim().toLowerCase() : '');
        const s = statusSel ? statusSel.value : '';
        const t = typeSel ? typeSel.value.toLowerCase() : '';
        let visible = 0;
        list.querySelectorAll('.club-event-item').forEach(card => {
            const okQ = !q || (card.getAttribute('data-search') || '').includes(q);
            const okS = !s || (card.getAttribute('data-status') || '') === s;
            const okT = !t || (card.getAttribute('data-type') || '') === t;
            const show = okQ && okS && okT;
            card.style.display = show ? '' : 'none';
            if (show) visible += 1;
        });
        if (empty) empty.hidden = visible !== 0;
    };
    [search, statusSel, typeSel].forEach(el => {
        if (el) el.addEventListener('input', applyFilters);
        if (el) el.addEventListener('change', applyFilters);
    });

    const toast = document.getElementById('club-toast');
    let toastTimer = null;
    const showToast = (msg) => {
        if (!toast) return;
        toast.textContent = msg;
        toast.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { toast.hidden = true; }, 3500);
    };

    // President decision modal (mirrors eventapproval: remarks mandatory on request-changes).
    const modal = document.getElementById('event-decision-modal');
    if (modal && list) {
        const titleEl = document.getElementById('ed-title');
        const metaEl = document.getElementById('ed-meta');
        const submitterEl = document.getElementById('ed-submitter');
        const resultSel = document.getElementById('ed-result');
        const remarks = document.getElementById('ed-remarks');
        const err = document.getElementById('ed-error');
        const impact = document.getElementById('ed-impact');
        const confirmBtn = document.getElementById('ed-confirm');
        let target = null;

        const refreshImpact = () => {
            const changes = resultSel.value === 'request-changes';
            impact.classList.toggle('is-reject', changes);
            impact.querySelector('strong').textContent = changes ? 'Impact of requesting changes' : 'Impact of approval';
            impact.querySelector('p').textContent = changes
                ? 'Requesting changes notifies the secretary with your official remarks. The event stays unapproved and off the club calendar.'
                : 'Approving publishes this event to the club calendar and notifies the secretary. It becomes visible for attendance tracking once it occurs.';
        };
        resultSel.addEventListener('change', () => { err.hidden = true; refreshImpact(); });

        const close = () => { modal.hidden = true; document.body.style.overflow = ''; };
        modal.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', close));
        modal.addEventListener('click', (e) => { if (e.target === modal) close(); });

        list.querySelectorAll('[data-action="review"]').forEach(btn => {
            btn.addEventListener('click', () => {
                target = btn.closest('.club-event-item');
                titleEl.textContent = target.getAttribute('data-title') || 'Review event';
                metaEl.textContent = target.getAttribute('data-meta') || '';
                const by = target.getAttribute('data-submitter') || '';
                submitterEl.textContent = by ? ('Submitted by ' + by) : '';
                resultSel.value = 'approve';
                remarks.value = '';
                err.hidden = true;
                refreshImpact();
                modal.hidden = false;
                document.body.style.overflow = 'hidden';
            });
        });

        confirmBtn.addEventListener('click', () => {
            const changes = resultSel.value === 'request-changes';
            if (changes && !remarks.value.trim()) {
                err.textContent = 'Please provide remarks explaining the requested changes.';
                err.hidden = false;
                remarks.focus();
                return;
            }
            if (target) {
                const pill = target.querySelector('.club-pill');
                const btn = target.querySelector('[data-action="review"]');
                if (!changes) {
                    target.setAttribute('data-status', 'approved');
                    if (pill) { pill.textContent = 'Approved'; pill.className = 'club-pill club-pill--approved'; }
                    if (btn) btn.remove();
                    showToast('Event approved — published to the club calendar (demo).');
                } else {
                    showToast('Change request sent to the secretary (demo).');
                }
            }
            close();
            applyFilters();
        });
    }

    // Secretary create-event form.
    const form = document.getElementById('club-event-form');
    if (form && list) {
        const err = document.getElementById('ev-error');
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const title = document.getElementById('ev-title').value.trim();
            const dtVal = document.getElementById('ev-datetime').value;
            const location = document.getElementById('ev-location').value.trim();
            const type = document.getElementById('ev-type').value;
            const budgetVal = document.getElementById('ev-budget').value.trim();
            const fail = (m) => { err.textContent = m; err.hidden = false; };
            err.hidden = true;
            if (!title || !dtVal || !location || !type || !budgetVal) return fail('All fields are required.');
            const dt = new Date(dtVal);
            if (isNaN(dt.getTime())) return fail('Enter a valid date and time.');
            if (dt <= new Date()) return fail('Event date must be in the future.');
            const budgetNum = Number(budgetVal);
            if (!isFinite(budgetNum) || budgetNum <= 0) return fail('Budget must be a positive amount.');
            const nice = dt.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + ' · ' +
                dt.toLocaleString('en-US', { hour: 'numeric', minute: '2-digit' });
            const card = document.createElement('article');
            card.className = 'club-list-item club-event-item';
            card.setAttribute('data-search', (title + ' ' + location).toLowerCase());
            card.setAttribute('data-status', 'pending');
            card.setAttribute('data-type', type.toLowerCase());
            card.setAttribute('data-title', title);
            const budgetText = 'Rs. ' + Math.round(budgetNum).toLocaleString('en-US');
            const cardTitle = document.createElement('h3');
            cardTitle.textContent = title;
            const iconCal = document.createElement('span');
            iconCal.textContent = nice + ' · ' + location;
            const meta = document.createElement('p');
            meta.className = 'club-event-meta';
            meta.textContent = type + ' · Budget ' + budgetText + ' · Submitted by you';
            const copy = document.createElement('div');
            copy.className = 'club-list-copy';
            copy.appendChild(cardTitle);
            const line = document.createElement('p');
            line.appendChild(iconCal);
            copy.appendChild(line);
            copy.appendChild(meta);
            const icon = document.createElement('div');
            icon.className = 'club-list-icon';
            icon.setAttribute('aria-hidden', 'true');
            const firstIcon = list.querySelector('.club-list-icon');
            if (firstIcon) icon.innerHTML = firstIcon.innerHTML;
            const side = document.createElement('div');
            side.className = 'club-event-side';
            const pill = document.createElement('span');
            pill.className = 'club-pill club-pill--pending';
            pill.textContent = 'Pending Approval';
            side.appendChild(pill);
            card.appendChild(icon);
            card.appendChild(copy);
            card.appendChild(side);
            list.prepend(card);
            form.reset();
            applyFilters();
            showToast(title + ' submitted for approval (demo — persists in C13 backend).');
        });
    }
});
</script>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/club.css">

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
