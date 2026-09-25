<?php
/**
 * Monitor Club Health — Z1.
 * Divisional Figma shape at zonal scope: band tiles (Healthy 85-100,
 * At Risk 50-69, Dormant below 50 or inactivity), search + division
 * filter + sort + status chips + export, division averages, club cards
 * sorted highest first. Card opens a detail modal (about, performance,
 * recent events, executive committee, health detail, report download)
 * with a flag-for-NYSC-Admin flow. Read-only mock; disband stays
 * Admin-only per the divisional and disbanding workflows.
 * Presentation-only: no DB writes; backend contract lands in Z12.
 */
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';

$zoneHealth = $zoneHealth ?? [];
$divisions  = $divisions ?? [];
$clubs      = $clubs ?? [];
?>

<section class="club-page" aria-labelledby="zonal-clubs-heading">
    <h1 id="zonal-clubs-heading" class="sr-only">Monitor club health</h1>

    <div class="club-stat-grid health-bands" aria-label="Club health bands">
        <article class="club-stat-card health-band health-band--healthy">
            <p class="club-stat-value"><?= $escape($zoneHealth['healthy'] ?? 0) ?></p>
            <p class="club-stat-label">Healthy</p>
            <p class="club-note">Score: 85–100</p>
        </article>
        <article class="club-stat-card health-band health-band--atrisk">
            <p class="club-stat-value"><?= $escape($zoneHealth['atrisk'] ?? 0) ?></p>
            <p class="club-stat-label">At Risk</p>
            <p class="club-note">Score: 50–69</p>
        </article>
        <article class="club-stat-card health-band health-band--dormant">
            <p class="club-stat-value"><?= $escape($zoneHealth['dormant'] ?? 0) ?></p>
            <p class="club-stat-label">Dormant</p>
            <p class="club-note">Score: &lt;50 / Inactivity</p>
        </article>
    </div>

    <section class="club-panel" aria-labelledby="zonal-division-avg-heading">
        <div class="club-panel-header">
            <div>
                <p class="club-eyebrow">Gampaha Zone</p>
                <h2 id="zonal-division-avg-heading">Average club health per division</h2>
            </div>
        </div>

        <div class="club-stat-grid" aria-label="Division averages">
            <?php foreach ($divisions as $d): ?>
                <article class="club-stat-card">
                    <p class="club-stat-label"><?= $escape($d['division'] ?? '') ?></p>
                    <p class="club-stat-value club-stat-value--small"><?= $escape($d['average'] ?? 0) ?><span class="club-stat-unit">/100</span></p>
                    <p><span class="club-pill club-pill--<?= $escape($d['status_key'] ?? 'pending') ?>"><?= $escape($d['status'] ?? '') ?></span>
                    <span class="club-note"><?= $escape($d['clubs'] ?? 0) ?> clubs</span></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="club-panel" aria-labelledby="zonal-clubs-list-heading">
        <div class="club-panel-header">
            <div>
                <p class="club-eyebrow">Gampaha Zone</p>
                <h2 id="zonal-clubs-list-heading">Clubs</h2>
            </div>
        </div>

        <div class="health-toolbar" role="search">
            <input type="search" id="health-search" class="health-search" placeholder="Search clubs..." aria-label="Search clubs">
            <select id="health-division" class="health-select" aria-label="Filter by division">
                <option value="all">All divisions</option>
                <?php foreach ($divisions as $d): ?>
                    <option value="<?= $escape($d['division'] ?? '') ?>"><?= $escape($d['division'] ?? '') ?></option>
                <?php endforeach; ?>
            </select>
            <select id="health-sort" class="health-select" aria-label="Sort clubs">
                <option value="score-desc">Sort: Highest Score First</option>
                <option value="score-asc">Sort: Lowest Score First</option>
                <option value="name-asc">Sort: Name A–Z</option>
            </select>
            <button type="button" class="club-btn-secondary" id="health-export">Export</button>
        </div>
        <div class="health-chips" role="group" aria-label="Filter by status">
            <button type="button" class="club-btn-small health-chip is-active" data-band="all">All</button>
            <button type="button" class="club-btn-small health-chip" data-band="healthy">Healthy</button>
            <button type="button" class="club-btn-small health-chip" data-band="atrisk">At Risk</button>
            <button type="button" class="club-btn-small health-chip" data-band="dormant">Dormant</button>
        </div>

        <div class="health-grid" id="health-grid">
            <?php foreach ($clubs as $c): ?>
                <article class="health-card health-card--<?= $escape($c['band'] ?? 'healthy') ?>"
                    data-name="<?= $escape(strtolower($c['name'] ?? '')) ?>"
                    data-band="<?= $escape($c['band'] ?? 'healthy') ?>"
                    data-division="<?= $escape($c['division'] ?? '') ?>"
                    data-score="<?= $escape($c['score'] ?? 0) ?>"
                    data-club='<?= $escape(json_encode(['id' => $c['id'] ?? '', 'name' => $c['name'] ?? '', 'division' => $c['division'] ?? '', 'score' => $c['score'] ?? 0, 'band' => $c['band'] ?? 'healthy', 'status' => $c['status'] ?? '', 'status_key' => $c['status_key'] ?? 'pending', 'members' => $c['members'] ?? 0, 'members_note' => $c['members_note'] ?? '', 'about' => $c['about'] ?? '', 'category' => $c['category'] ?? '', 'location' => $c['location'] ?? '', 'established' => $c['established'] ?? '', 'avg_attendance' => $c['avg_attendance'] ?? '', 'attendance_trend' => $c['attendance_trend'] ?? '', 'trigger' => $c['trigger'] ?? '', 'events' => ($c['events']['points'] ?? 0) . '/' . ($c['events']['max'] ?? 40), 'finances' => ($c['finances']['points'] ?? 0) . '/' . ($c['finances']['max'] ?? 30), 'attendance' => ($c['attendance']['points'] ?? 0) . '/' . ($c['attendance']['max'] ?? 30), 'recent_events' => $c['recent_events'] ?? [], 'execs' => $c['execs'] ?? []])) ?>'>
                    <div class="health-avatar health-avatar--<?= $escape($c['band'] ?? 'healthy') ?>" aria-hidden="true">
                        <?= yn_icon('user') ?>
                        <?php if (($c['band'] ?? '') === 'dormant'): ?><span class="health-flag" title="Flagged">!</span><?php endif; ?>
                    </div>
                    <?php if (($c['band'] ?? '') === 'dormant'): ?>
                        <p><span class="club-pill club-pill--dormant">Intervention required</span></p>
                    <?php endif; ?>
                    <p class="health-score health-score--<?= $escape($c['band'] ?? 'healthy') ?>"><?= $escape($c['score'] ?? 0) ?><span>/100</span></p>
                    <p><span class="club-pill club-pill--<?= $escape($c['status_key'] ?? 'pending') ?>"><?= $escape(strtoupper($c['status'] ?? '')) ?></span></p>
                    <h3><?= $escape($c['name'] ?? '') ?></h3>
                    <p class="club-note"><?= $escape($c['division'] ?? '') ?> · <?= $escape($c['members_note'] ?? '') ?></p>
                    <button type="button" class="health-open<?= ($c['band'] ?? '') === 'dormant' ? ' health-open--dormant' : '' ?>" data-action="club-detail" aria-label="Open <?= $escape($c['name'] ?? '') ?> details"><span aria-hidden="true">›</span></button>
                </article>
            <?php endforeach; ?>
        </div>
        <p id="health-empty" class="club-note" hidden>No clubs match these filters.</p>
    </section>

    <div id="club-health-modal" class="popup-overlay" hidden>
        <div class="popup-content popup-content--wide" role="dialog" aria-modal="true" aria-labelledby="ch-title">
            <button type="button" class="popup-close" data-close aria-label="Close"><?= yn_icon('close') ?></button>
            <div class="health-detail-head">
                <div class="health-detail-id" aria-hidden="true"><?= yn_icon('user') ?></div>
                <div>
                    <h2 id="ch-title">Club</h2>
                    <p><span class="club-pill" id="ch-id">GC-000</span>
                    <span class="club-pill" id="ch-band">0/100</span></p>
                </div>
            </div>
            <div class="health-detail-grid">
                <section aria-labelledby="ch-about-h">
                    <h3 id="ch-about-h" class="health-detail-label">About</h3>
                    <div class="health-detail-box">
                        <p id="ch-about"></p>
                        <p class="club-review-row"><span>Category</span><strong id="ch-category"></strong></p>
                        <p class="club-review-row"><span>Location</span><strong id="ch-location"></strong></p>
                        <p class="club-review-row"><span>Established</span><strong id="ch-established"></strong></p>
                    </div>
                </section>
                <section aria-labelledby="ch-perf-h">
                    <h3 id="ch-perf-h" class="health-detail-label">Performance overview</h3>
                    <div class="health-perf-row">
                        <div class="health-detail-box health-perf-box">
                            <p class="club-stat-label">Active Members</p>
                            <p class="health-perf-big" id="ch-members"></p>
                        </div>
                        <div class="health-detail-box health-perf-box">
                            <p class="club-stat-label">Avg. Attendance</p>
                            <p class="health-perf-big"><span id="ch-attendance"></span> <span class="health-trend" id="ch-trend"></span></p>
                        </div>
                    </div>
                </section>
            </div>
            <section aria-labelledby="ch-events-h">
                <h3 id="ch-events-h" class="health-detail-label">Recent events</h3>
                <div class="club-table-wrap">
                    <table class="club-table">
                        <thead>
                            <tr>
                                <th>Event Name</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="ch-events"></tbody>
                    </table>
                </div>
            </section>
            <section aria-labelledby="ch-execs-h">
                <h3 id="ch-execs-h" class="health-detail-label">Executive committee</h3>
                <div class="health-execs" id="ch-execs"></div>
            </section>
            <section aria-labelledby="ch-health-h">
                <h3 id="ch-health-h" class="health-detail-label">Health detail</h3>
                <div class="health-detail-box">
                    <div id="ch-scores"></div>
                    <p class="club-note" id="ch-trigger-note"></p>
                </div>
            </section>
            <div class="club-modal-footer health-detail-footer">
                <button type="button" class="club-btn-secondary" id="ch-open-flag">Flag for NYSC Admin Review</button>
                <button type="button" class="club-btn-secondary" id="ch-edit">Edit Details</button>
                <button type="button" class="club-btn-primary" id="ch-report">Download Report</button>
            </div>
        </div>
    </div>

    <div id="club-flag-modal" class="popup-overlay" hidden>
        <div class="popup-content club-modal" role="dialog" aria-modal="true" aria-labelledby="cf-title">
            <button type="button" class="popup-close" data-close aria-label="Close"><?= yn_icon('close') ?></button>
            <h2 id="cf-title">Flag Club for NYSC Admin Review</h2>
            <div class="health-flag-summary">
                <p class="club-review-row"><span>Club</span><strong id="cf-club"></strong></p>
                <p class="club-review-row"><span>Overall Health Score</span><strong id="cf-score"></strong></p>
                <p class="club-review-row"><span>Trigger</span><strong id="cf-trigger"></strong></p>
            </div>
            <div class="club-field">
                <label for="cf-severity">Severity</label>
                <select id="cf-severity">
                    <option value="Low">Low</option>
                    <option value="Medium">Medium</option>
                    <option value="High">High</option>
                </select>
            </div>
            <div class="club-field">
                <label for="cf-remarks">Comment (required)</label>
                <textarea id="cf-remarks" rows="3" placeholder="Describe the governance concern for NYSC Admin..."></textarea>
            </div>
            <p id="cf-error" class="club-form-error" hidden></p>
            <div class="club-error-banner" role="note">
                <strong>Admin-only disband</strong>
                <p>Only NYSC Admin can disband a club — this submits a flag and notification, it does not disband anything.</p>
            </div>
            <div class="club-modal-footer">
                <button type="button" class="club-btn-secondary" data-close>Cancel</button>
                <button type="button" class="club-btn-primary" id="cf-submit">Submit Flag to NYSC Admin</button>
            </div>
        </div>
    </div>
</section>

<div id="club-toast" class="club-toast" role="status" hidden></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const grid = document.getElementById('health-grid');
    const search = document.getElementById('health-search');
    const divisionSel = document.getElementById('health-division');
    const sortSel = document.getElementById('health-sort');
    const chips = Array.from(document.querySelectorAll('.health-chip'));
    const emptyNote = document.getElementById('health-empty');
    const toast = document.getElementById('club-toast');
    let toastTimer = null;
    let activeBand = 'all';

    const showToast = (msg) => {
        if (!toast) return;
        toast.textContent = msg;
        toast.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { toast.hidden = true; }, 3500);
    };

    const cards = () => grid ? Array.from(grid.querySelectorAll('.health-card')) : [];

    const applyFilter = () => {
        if (!grid) return;
        const q = (search ? search.value : '').trim().toLowerCase();
        const div = divisionSel ? divisionSel.value : 'all';
        let visible = 0;
        cards().forEach((card) => {
            const show = (activeBand === 'all' || card.getAttribute('data-band') === activeBand)
                && (div === 'all' || card.getAttribute('data-division') === div)
                && (!q || (card.getAttribute('data-name') || '').includes(q));
            card.hidden = !show;
            if (show) visible += 1;
        });
        if (emptyNote) emptyNote.hidden = visible !== 0;
    };

    const applySort = () => {
        if (!grid || !sortSel) return;
        const mode = sortSel.value;
        const ordered = cards().sort((a, b) => {
            const sa = parseInt(a.getAttribute('data-score') || '0', 10);
            const sb = parseInt(b.getAttribute('data-score') || '0', 10);
            if (mode === 'score-asc') return sa - sb;
            if (mode === 'name-asc') return (a.getAttribute('data-name') || '').localeCompare(b.getAttribute('data-name') || '');
            return sb - sa;
        });
        ordered.forEach((card) => grid.appendChild(card));
    };

    if (search) search.addEventListener('input', applyFilter);
    if (divisionSel) divisionSel.addEventListener('change', applyFilter);
    if (sortSel) sortSel.addEventListener('change', applySort);
    chips.forEach((chip) => {
        chip.addEventListener('click', () => {
            chips.forEach((c) => c.classList.remove('is-active'));
            chip.classList.add('is-active');
            activeBand = chip.getAttribute('data-band') || 'all';
            applyFilter();
        });
    });

    const exportBtn = document.getElementById('health-export');
    if (exportBtn && grid) {
        exportBtn.addEventListener('click', () => {
            const rows = [['Club', 'Division', 'Score', 'Status', 'Members', 'Events', 'Finances', 'Attendance']];
            cards().forEach((card) => {
                if (card.hidden) return;
                try {
                    const d = JSON.parse(card.getAttribute('data-club') || '{}');
                    rows.push([d.name || '', d.division || '', d.score || '', d.status || '', d.members_note || '', d.events || '', d.finances || '', d.attendance || '']);
                } catch (err) { /* skip malformed card */ }
            });
            const csv = rows.map((r) => r.map((v) => '"' + String(v).replace(/"/g, '""') + '"').join(',')).join('\r\n');
            const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }));
            const a = document.createElement('a');
            a.href = url;
            a.download = 'gampaha-zone-club-health.csv';
            document.body.appendChild(a);
            a.click();
            a.remove();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
            showToast('Club health exported (demo CSV).');
        });
    }

    const detail = document.getElementById('club-health-modal');
    const flagModal = document.getElementById('club-flag-modal');
    let current = {};

    const closeOverlay = (overlay) => { overlay.hidden = true; document.body.style.overflow = ''; };
    const openOverlay = (overlay) => { overlay.hidden = false; document.body.style.overflow = 'hidden'; };
    [detail, flagModal].forEach((overlay) => {
        if (!overlay) return;
        overlay.querySelectorAll('[data-close]').forEach((b) => b.addEventListener('click', () => closeOverlay(overlay)));
        overlay.addEventListener('click', (e) => { if (e.target === overlay) closeOverlay(overlay); });
    });

    const esc = (v) => String(v == null ? '' : v);

    const openDetail = (d) => {
        current = d;
        document.getElementById('ch-title').textContent = esc(d.name || 'Club');
        document.getElementById('ch-id').textContent = esc(d.id || '');
        const band = document.getElementById('ch-band');
        band.textContent = esc((d.score || '') + '/100 ' + (d.status || ''));
        band.className = 'club-pill club-pill--' + esc(d.status_key || 'pending');
        document.getElementById('ch-about').textContent = esc(d.about || '—');
        document.getElementById('ch-category').textContent = esc(d.category || '—');
        document.getElementById('ch-location').textContent = esc(d.location || '—');
        document.getElementById('ch-established').textContent = esc(d.established || '—');
        document.getElementById('ch-members').textContent = esc(d.members || 0);
        document.getElementById('ch-attendance').textContent = esc(d.avg_attendance || '—');
        document.getElementById('ch-trend').textContent = esc(d.attendance_trend || '');

        const eventsBody = document.getElementById('ch-events');
        eventsBody.textContent = '';
        (d.recent_events || []).forEach((e) => {
            const tr = document.createElement('tr');
            const nameTd = document.createElement('td');
            const nameStrong = document.createElement('strong');
            nameStrong.textContent = esc(e.name || '');
            nameTd.appendChild(nameStrong);
            const venue = document.createElement('div');
            venue.className = 'club-note';
            venue.textContent = esc(e.venue || '');
            nameTd.appendChild(venue);
            const dateTd = document.createElement('td');
            dateTd.textContent = esc(e.date || '');
            const statusTd = document.createElement('td');
            const pill = document.createElement('span');
            pill.className = 'club-pill club-pill--' + esc(e.status_key || 'pending');
            pill.textContent = esc((e.status || '').toUpperCase());
            statusTd.appendChild(pill);
            tr.appendChild(nameTd);
            tr.appendChild(dateTd);
            tr.appendChild(statusTd);
            eventsBody.appendChild(tr);
        });

        const execsBox = document.getElementById('ch-execs');
        execsBox.textContent = '';
        (d.execs || []).forEach((x) => {
            const row = document.createElement('div');
            row.className = 'health-exec-row';
            const disc = document.createElement('span');
            disc.className = 'health-exec-disc';
            disc.setAttribute('aria-hidden', 'true');
            disc.textContent = esc(x.name || '').split(/\s+/).map((w) => w.charAt(0)).join('').slice(0, 2).toUpperCase() || '–';
            const copy = document.createElement('div');
            const nm = document.createElement('strong');
            nm.textContent = esc(x.name || '');
            copy.appendChild(nm);
            const rl = document.createElement('div');
            rl.className = 'club-note';
            rl.textContent = esc(x.role || '');
            copy.appendChild(rl);
            row.appendChild(disc);
            row.appendChild(copy);
            execsBox.appendChild(row);
        });

        const scoresBox = document.getElementById('ch-scores');
        scoresBox.textContent = '';
        [['Event Score', d.events], ['Finance Score', d.finances], ['Attendance Score', d.attendance], ['Overall Health Score', (d.score || '') + ' / 100']].forEach(([label, val], idx, arr) => {
            const row = document.createElement('p');
            row.className = 'club-review-row' + (idx === arr.length - 1 ? ' health-score-total' : '');
            const lab = document.createElement('span');
            lab.textContent = label;
            const strong = document.createElement('strong');
            strong.textContent = esc(val || '—');
            row.appendChild(lab);
            row.appendChild(strong);
            scoresBox.appendChild(row);
        });
        document.getElementById('ch-trigger-note').textContent = esc(d.trigger || '')
            + ' — this club is read-only and cannot be disbanded except by NYSC Admin.';

        openOverlay(detail);
    };

    if (grid && detail) {
        grid.querySelectorAll('[data-action="club-detail"]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const card = btn.closest('.health-card');
                try {
                    openDetail(JSON.parse(card.getAttribute('data-club') || '{}'));
                } catch (err) { /* ignore malformed payload */ }
            });
        });
    }

    const openFlag = () => {
        if (!flagModal) return;
        document.getElementById('cf-club').textContent = esc(current.name || '');
        document.getElementById('cf-score').textContent = esc((current.score || '') + ' / 100');
        document.getElementById('cf-trigger').textContent = esc(current.trigger || '');
        document.getElementById('cf-severity').value = 'Low';
        document.getElementById('cf-remarks').value = '';
        document.getElementById('cf-error').hidden = true;
        if (detail) closeOverlay(detail);
        openOverlay(flagModal);
    };

    const flagBtn = document.getElementById('ch-open-flag');
    if (flagBtn) flagBtn.addEventListener('click', openFlag);

    const editBtn = document.getElementById('ch-edit');
    if (editBtn) editBtn.addEventListener('click', () => {
        showToast('Club details are read-only for zonal coordinators — contact NYSC Admin. (demo)');
    });

    const reportBtn = document.getElementById('ch-report');
    if (reportBtn) {
        reportBtn.addEventListener('click', () => {
            const d = current;
            const lines = [
                'Club Health Report (demo) — ' + esc(d.name || ''),
                'ID: ' + esc(d.id || '') + ' | Division: ' + esc(d.division || ''),
                'Overall: ' + esc(d.score || '') + '/100 (' + esc(d.status || '') + ')',
                'Events: ' + esc(d.events || '') + ' | Finances: ' + esc(d.finances || '') + ' | Attendance: ' + esc(d.attendance || ''),
                'Members: ' + esc(d.members_note || '') + ' | Avg attendance: ' + esc(d.avg_attendance || ''),
                'Trigger: ' + esc(d.trigger || ''),
            ];
            const url = URL.createObjectURL(new Blob([lines.join('\r\n')], { type: 'text/plain' }));
            const a = document.createElement('a');
            a.href = url;
            a.download = 'club-health-' + esc(d.id || 'report') + '.txt';
            document.body.appendChild(a);
            a.click();
            a.remove();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
            showToast('Club report downloaded (demo).');
        });
    }

    const flagSubmit = document.getElementById('cf-submit');
    if (flagSubmit) {
        flagSubmit.addEventListener('click', () => {
            const remarks = document.getElementById('cf-remarks');
            const err = document.getElementById('cf-error');
            if (!remarks.value.trim()) {
                err.textContent = 'Please describe the governance concern for NYSC Admin.';
                err.hidden = false;
                remarks.focus();
                return;
            }
            const who = current.name || 'Club';
            closeOverlay(flagModal);
            showToast(who + ' flagged for NYSC Admin (demo).');
        });
    }
});
</script>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/club.css">

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
