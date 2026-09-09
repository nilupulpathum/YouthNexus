<?php
/**
 * Club Attendance — C5 club-scoped mock.
 * Secretary: event list, CSV bulk OR single entry tabs, start/end time +
 * remarks, per-row toggles; unlisted members default to absent. Member:
 * personal summary + history. Presentation-only: no DB writes; the
 * divisional Attendance flow is untouched. Backend contract lands in C13.
 */
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';

$attendanceEvents = $attendanceEvents ?? [];
$mySummary        = $mySummary ?? [];
$can_mark         = !empty($can_mark);
?>

<section class="club-page" aria-labelledby="club-attendance-heading">
    <h1 id="club-attendance-heading" class="sr-only">Club attendance</h1>

    <?php if ($can_mark): ?>
        <section class="club-panel" aria-labelledby="club-att-mark-heading">
            <div class="club-panel-header">
                <div>
                    <p class="club-eyebrow">Secretary action</p>
                    <h2 id="club-att-mark-heading">Mark Attendance</h2>
                </div>
            </div>

            <div class="club-panel-sub">
                <div class="club-form-grid">
                    <div class="club-field club-field--full">
                        <label for="att-event">Event</label>
                        <select id="att-event">
                            <?php foreach ($attendanceEvents as $i => $ev): ?>
                                <option value="<?= (int) $i ?>"><?= $escape(($ev['title'] ?? '') . ' — ' . ($ev['date'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="club-field">
                        <label for="att-start">Start time</label>
                        <input id="att-start" type="time" required>
                    </div>
                    <div class="club-field">
                        <label for="att-end">End time</label>
                        <input id="att-end" type="time" required>
                    </div>
                    <div class="club-field club-field--full">
                        <label for="att-remarks">Session remarks</label>
                        <input id="att-remarks" type="text" maxlength="255" autocomplete="off" placeholder="e.g., Outdoor session, registers closed at 10:15 AM">
                    </div>
                </div>
                <p id="att-session-error" class="club-form-error" hidden></p>
                <div class="club-form-footer">
                    <button type="button" class="club-btn-primary" id="att-save-session">Save session</button>
                </div>
            </div>

            <div class="club-divider" aria-hidden="true"></div>

            <div class="club-panel-sub" aria-labelledby="club-att-entry-heading">
                <div class="club-tabs" role="tablist" aria-label="Entry method">
                    <button type="button" class="club-tab is-active" id="tab-single" role="tab" aria-selected="true" aria-controls="pane-single">Single entry</button>
                    <button type="button" class="club-tab" id="tab-bulk" role="tab" aria-selected="false" aria-controls="pane-bulk">Bulk CSV</button>
                </div>

                <div id="pane-single" role="tabpanel" aria-labelledby="tab-single">
                    <form id="att-single-form" class="club-form" novalidate>
                        <div class="club-form-grid">
                            <div class="club-field">
                                <label for="att-member">Member</label>
                                <select id="att-member"></select>
                            </div>
                            <div class="club-field">
                                <label for="att-status">Status</label>
                                <select id="att-status">
                                    <option value="present">Present</option>
                                    <option value="absent">Absent</option>
                                </select>
                            </div>
                        </div>
                        <div class="club-form-footer">
                            <button type="submit" class="club-btn-primary">Apply entry</button>
                        </div>
                    </form>
                </div>

                <div id="pane-bulk" role="tabpanel" aria-labelledby="tab-bulk" hidden>
                    <form id="att-bulk-form" class="club-form" novalidate>
                        <div class="club-field">
                            <label for="att-csv">Attendance CSV (name, status per row)</label>
                            <input id="att-csv" type="file" accept=".csv,text/csv">
                        </div>
                        <p id="att-csv-name" class="club-sub-note" hidden></p>
                        <p id="att-bulk-error" class="club-form-error" hidden></p>
                        <div class="club-form-footer">
                            <button type="submit" class="club-btn-primary">Upload CSV</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <section class="club-panel" aria-labelledby="club-att-roster-heading">
            <div class="club-panel-header">
                <div>
                    <p class="club-eyebrow" id="att-roster-event">Session roster</p>
                    <h2 id="club-att-roster-heading">Attendance</h2>
                </div>
            </div>

            <div class="club-table-wrap">
                <table class="club-table">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="club-att-body"></tbody>
                </table>
            </div>
            <p class="club-note">Members without an entry are marked Absent by default.</p>
        </section>
    <?php else: ?>
        <div class="club-stat-grid" aria-label="My attendance summary">
            <article class="club-stat-card">
                <p class="club-stat-label">Sessions attended</p>
                <p class="club-stat-value"><?= $escape($mySummary['sessions'] ?? 0) ?></p>
            </article>
            <article class="club-stat-card">
                <p class="club-stat-label">Attendance rate</p>
                <p class="club-stat-value"><?= $escape($mySummary['rate'] ?? '') ?></p>
            </article>
            <article class="club-stat-card">
                <p class="club-stat-label">Times absent</p>
                <p class="club-stat-value"><?= $escape($mySummary['absent'] ?? 0) ?></p>
            </article>
        </div>

        <section class="club-panel" aria-labelledby="club-att-history-heading">
            <div class="club-panel-header">
                <div>
                    <p class="club-eyebrow">My record</p>
                    <h2 id="club-att-history-heading">My Attendance</h2>
                </div>
            </div>

            <div class="club-list">
                <?php foreach (($mySummary['history'] ?? []) as $h): ?>
                    <article class="club-list-item">
                        <div class="club-list-icon" aria-hidden="true"><?= yn_icon('calendar') ?></div>
                        <div class="club-list-copy">
                            <h3><?= $escape($h['title'] ?? '') ?></h3>
                            <p><?= $escape($h['date'] ?? '') ?></p>
                        </div>
                        <div class="club-event-side">
                            <span class="club-pill club-pill--<?= $escape($h['status_key'] ?? 'present') ?>"><?= $escape($h['status'] ?? '') ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</section>

<div id="club-toast" class="club-toast" role="status" hidden></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const toast = document.getElementById('club-toast');
    let toastTimer = null;
    const showToast = (msg) => {
        if (!toast) return;
        toast.textContent = msg;
        toast.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { toast.hidden = true; }, 3500);
    };

    const DATA = <?= json_encode(array_values($attendanceEvents)) ?>;
    const pillFor = (key, label) => {
        const s = document.createElement('span');
        s.className = 'club-pill club-pill--' + key;
        s.textContent = label;
        return s;
    };

    // Secretary marking UI.
    const eventSel = document.getElementById('att-event');
    const body = document.getElementById('club-att-body');
    if (eventSel && body) {
        const rosterEvent = document.getElementById('att-roster-event');
        const memberSel = document.getElementById('att-member');
        let current = 0;

        const renderRoster = () => {
            const ev = DATA[current];
            if (rosterEvent && ev) rosterEvent.textContent = ev.title + ' — ' + ev.date;
            body.textContent = '';
            memberSel.textContent = '';
            (ev ? ev.roster : []).forEach(m => {
                const tr = document.createElement('tr');
                tr.setAttribute('data-name', m.name);
                const nameTd = document.createElement('td');
                const strong = document.createElement('strong');
                strong.textContent = m.name;
                nameTd.appendChild(strong);
                tr.appendChild(nameTd);
                const statusTd = document.createElement('td');
                statusTd.appendChild(pillFor(m.status_key, m.status));
                tr.appendChild(statusTd);
                const actionTd = document.createElement('td');
                const toggle = document.createElement('button');
                toggle.type = 'button';
                toggle.className = 'club-btn-small';
                toggle.textContent = m.status_key === 'present' ? 'Mark absent' : 'Mark present';
                toggle.addEventListener('click', () => {
                    const nowPresent = statusTd.querySelector('.club-pill').className.includes('present');
                    statusTd.textContent = '';
                    statusTd.appendChild(pillFor(nowPresent ? 'absent' : 'present', nowPresent ? 'Absent' : 'Present'));
                    toggle.textContent = nowPresent ? 'Mark present' : 'Mark absent';
                    showToast(m.name + ' marked ' + (nowPresent ? 'Absent' : 'Present') + ' (demo).');
                });
                actionTd.appendChild(toggle);
                tr.appendChild(actionTd);
                body.appendChild(tr);

                const opt = document.createElement('option');
                opt.value = m.name;
                opt.textContent = m.name;
                memberSel.appendChild(opt);
            });
        };

        eventSel.addEventListener('change', () => {
            current = parseInt(eventSel.value, 10) || 0;
            renderRoster();
        });
        renderRoster();

        // Tabs.
        const tabSingle = document.getElementById('tab-single');
        const tabBulk = document.getElementById('tab-bulk');
        const paneSingle = document.getElementById('pane-single');
        const paneBulk = document.getElementById('pane-bulk');
        const selectTab = (single) => {
            tabSingle.classList.toggle('is-active', single);
            tabBulk.classList.toggle('is-active', !single);
            tabSingle.setAttribute('aria-selected', single ? 'true' : 'false');
            tabBulk.setAttribute('aria-selected', single ? 'false' : 'true');
            paneSingle.hidden = !single;
            paneBulk.hidden = single;
        };
        tabSingle.addEventListener('click', () => selectTab(true));
        tabBulk.addEventListener('click', () => selectTab(false));

        // Save session (times + remarks).
        const saveBtn = document.getElementById('att-save-session');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => {
                const err = document.getElementById('att-session-error');
                const start = document.getElementById('att-start').value;
                const end = document.getElementById('att-end').value;
                err.hidden = true;
                if (!start || !end) {
                    err.textContent = 'Set both start and end time.';
                    err.hidden = false;
                    return;
                }
                if (end <= start) {
                    err.textContent = 'End time must be after start time.';
                    err.hidden = false;
                    return;
                }
                showToast('Session saved for ' + DATA[current].title + ' (demo — persists in C13 backend).');
            });
        }

        // Single entry.
        const singleForm = document.getElementById('att-single-form');
        if (singleForm) {
            singleForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const who = memberSel.value;
                const present = document.getElementById('att-status').value === 'present';
                body.querySelectorAll('tr').forEach(tr => {
                    if (tr.getAttribute('data-name') === who) {
                        const cell = tr.children[1];
                        cell.textContent = '';
                        cell.appendChild(pillFor(present ? 'present' : 'absent', present ? 'Present' : 'Absent'));
                        tr.querySelector('button').textContent = present ? 'Mark absent' : 'Mark present';
                    }
                });
                showToast(who + ' marked ' + (present ? 'Present' : 'Absent') + ' (demo).');
            });
        }

        // Bulk CSV.
        const bulkForm = document.getElementById('att-bulk-form');
        if (bulkForm) {
            const csv = document.getElementById('att-csv');
            const csvName = document.getElementById('att-csv-name');
            const bulkErr = document.getElementById('att-bulk-error');
            csv.addEventListener('change', () => {
                if (csv.files.length) {
                    csvName.textContent = 'Selected: ' + csv.files[0].name;
                    csvName.hidden = false;
                } else {
                    csvName.hidden = true;
                }
            });
            bulkForm.addEventListener('submit', (e) => {
                e.preventDefault();
                bulkErr.hidden = true;
                if (!csv.files.length) {
                    bulkErr.textContent = 'Choose a CSV file first.';
                    bulkErr.hidden = false;
                    return;
                }
                const reader = new FileReader();
                reader.onload = () => {
                    const lines = String(reader.result).split(/\r?\n/).filter(l => l.trim() !== '');
                    const count = Math.max(0, lines.length - 1);
                    showToast(count + ' record' + (count === 1 ? '' : 's') + ' parsed from CSV (demo — no database writes).');
                };
                reader.readAsText(csv.files[0]);
            });
        }
    }
});
</script>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/club.css">

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
