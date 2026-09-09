<?php
/**
 * President Overview — C2.
 * Health score + 40/30/30 breakdown tiles, pending club events cards
 * (review happens on club/events), exec-roster summary table.
 * Presentation-only: no DB writes; backend contract lands in C13.
 */
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';
require __DIR__ . '/../layouts/dashboard-start.view.php';

$health        = $health ?? [];
$pendingEvents = $pendingEvents ?? [];
$pendingMembers = $pendingMembers ?? [];
$execRoster    = $execRoster ?? [];
?>

<section class="club-page" aria-labelledby="president-overview-heading">
    <h1 id="president-overview-heading" class="sr-only">President overview</h1>

    <p class="club-back-row"><a class="club-btn-secondary" href="<?= ROOT ?>/member"><span aria-hidden="true">‹</span> My Dashboard</a></p>

    <div class="club-stat-grid" aria-label="Club health">
        <article class="club-stat-card">
            <p class="club-stat-label">Club health score</p>
            <p class="club-stat-value"><?= $escape($health['score'] ?? 0) ?><span class="club-stat-unit">/100</span></p>
            <p><span class="club-pill club-pill--approved"><?= $escape(($health['label'] ?? '') . ' · ' . ($health['state'] ?? '')) ?></span></p>
        </article>
        <article class="club-stat-card">
            <p class="club-stat-label">Events (40%)</p>
            <p class="club-stat-value"><?= $escape(($health['events']['points'] ?? 0) . '/' . ($health['events']['max'] ?? 40)) ?></p>
        </article>
        <article class="club-stat-card">
            <p class="club-stat-label">Finances (30%)</p>
            <p class="club-stat-value"><?= $escape(($health['finances']['points'] ?? 0) . '/' . ($health['finances']['max'] ?? 30)) ?></p>
        </article>
        <article class="club-stat-card">
            <p class="club-stat-label">Attendance (30%)</p>
            <p class="club-stat-value"><?= $escape(($health['attendance']['points'] ?? 0) . '/' . ($health['attendance']['max'] ?? 30)) ?></p>
        </article>
    </div>

    <section class="club-panel" aria-labelledby="president-pending-heading">
        <div class="club-panel-header">
            <div>
                <p class="club-eyebrow">Awaiting your review</p>
                <h2 id="president-pending-heading">Pending Club Events (<?= count($pendingEvents) ?>)</h2>
            </div>
        </div>

        <div class="club-list">
            <?php foreach ($pendingEvents as $e): ?>
                <article class="club-list-item">
                    <div class="club-list-icon" aria-hidden="true"><?= yn_icon('calendar') ?></div>
                    <div class="club-list-copy">
                        <h3><?= $escape($e['title'] ?? '') ?></h3>
                        <p><span class="icon"><?= yn_icon('calendar') ?></span> <?= $escape($e['date'] ?? '') ?> · <span class="icon"><?= yn_icon('pin') ?></span> <?= $escape($e['location'] ?? '') ?></p>
                        <p class="club-event-meta"><?= $escape($e['type'] ?? '') ?> · Budget <?= $escape($e['budget'] ?? '') ?> · Submitted by <?= $escape($e['submitted_by'] ?? '') ?></p>
                    </div>
                    <div class="club-event-side">
                        <span class="club-pill club-pill--pending">Pending Approval</span>
                        <a class="club-btn-small" href="<?= ROOT ?>/club/events">Review</a>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php if (empty($pendingEvents)): ?>
                <p class="club-note">Nothing awaiting review.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="club-panel" aria-labelledby="president-members-heading">
        <div class="club-panel-header">
            <div>
                <p class="club-eyebrow">Awaiting your approval</p>
                <h2 id="president-members-heading">Pending Member Approvals (<?= count($pendingMembers) ?>)</h2>
            </div>
        </div>

        <div class="club-list" id="president-members-list">
            <?php foreach ($pendingMembers as $m): ?>
                <article class="club-list-item"
                    data-name="<?= $escape($m['name'] ?? '') ?>"
                    data-email="<?= $escape($m['email'] ?? '') ?>"
                    data-phone="<?= $escape($m['phone'] ?? '') ?>"
                    data-address="<?= $escape($m['address'] ?? '') ?>"
                    data-nic="<?= $escape($m['nic'] ?? '') ?>"
                    data-joined="<?= $escape($m['joined'] ?? '') ?>"
                    data-registered-by="<?= $escape($m['registered_by'] ?? '') ?>">
                    <div class="club-list-icon" aria-hidden="true"><?= yn_icon('user') ?></div>
                    <div class="club-list-copy">
                        <h3><?= $escape($m['name'] ?? '') ?></h3>
                        <p><?= $escape($m['email'] ?? '') ?> · <?= $escape($m['phone'] ?? '') ?></p>
                        <p class="club-event-meta">Joined <?= $escape($m['joined'] ?? '') ?> · Registered by <?= $escape($m['registered_by'] ?? '') ?></p>
                    </div>
                    <div class="club-event-side">
                        <span class="club-pill club-pill--pending">Pending</span>
                        <button type="button" class="club-btn-small" data-action="member-review">Review</button>
                    </div>
                </article>
            <?php endforeach; ?>
            <p id="president-members-empty" class="club-note"<?= empty($pendingMembers) ? '' : ' hidden' ?>>Nothing awaiting approval.</p>
        </div>
    </section>

    <section class="club-panel" aria-labelledby="president-exec-heading">
        <div class="club-panel-header">
            <div>
                <p class="club-eyebrow">Gampaha Youth Development Club</p>
                <h2 id="president-exec-heading">Executive Roster</h2>
            </div>
        </div>

        <div class="club-table-wrap">
            <table class="club-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($execRoster as $m): ?>
                        <tr>
                            <td><strong><?= $escape($m['name'] ?? '') ?></strong></td>
                            <td><?= $escape($m['role'] ?? '') ?></td>
                            <td><span class="club-pill club-pill--<?= $escape($m['status_key'] ?? 'active') ?>"><?= $escape($m['status'] ?? '') ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <div id="member-review-modal" class="popup-overlay" hidden>
        <div class="popup-content club-modal" role="dialog" aria-modal="true" aria-labelledby="mr-title">
            <button type="button" class="popup-close" data-close aria-label="Close"><?= yn_icon('close') ?></button>
            <p class="club-eyebrow">Member approval</p>
            <h2 id="mr-title">Review member</h2>
            <div id="mr-details" class="club-review-details"></div>
            <div class="club-field">
                <label for="mr-result">Review result</label>
                <select id="mr-result">
                    <option value="approve">Approve member</option>
                    <option value="reject">Reject application</option>
                </select>
            </div>
            <div class="club-field">
                <label for="mr-remarks">Decision note (required if rejecting)</label>
                <textarea id="mr-remarks" rows="3" placeholder="Reason for this decision..."></textarea>
            </div>
            <p id="mr-error" class="club-form-error" hidden></p>
            <div class="club-impact" role="note">
                <strong>What happens next</strong>
                <p>Approving adds them as a General Member of the club. Rejecting discards the application with your note.</p>
            </div>
            <div class="club-modal-footer">
                <button type="button" class="club-btn-secondary" data-close>Cancel</button>
                <button type="button" class="club-btn-primary" id="mr-confirm">Confirm &amp; submit decision</button>
            </div>
        </div>
    </div>
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

    // Member review modal (president: full details, approve or reject with note).
    const list = document.getElementById('president-members-list');
    const modal = document.getElementById('member-review-modal');
    if (list && modal) {
        const titleEl = document.getElementById('mr-title');
        const detailsEl = document.getElementById('mr-details');
        const resultSel = document.getElementById('mr-result');
        const remarks = document.getElementById('mr-remarks');
        const err = document.getElementById('mr-error');
        const emptyNote = document.getElementById('president-members-empty');
        const heading = document.getElementById('president-members-heading');
        const confirmBtn = document.getElementById('mr-confirm');
        const FIELDS = [
            ['Email', 'email'], ['Phone', 'phone'], ['Address', 'address'],
            ['NIC', 'nic'], ['Joined', 'joined'], ['Registered by', 'registeredBy'],
        ];
        let target = null;

        const close = () => { modal.hidden = true; document.body.style.overflow = ''; };
        modal.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', close));
        modal.addEventListener('click', (e) => { if (e.target === modal) close(); });

        list.querySelectorAll('[data-action="member-review"]').forEach(btn => {
            btn.addEventListener('click', () => {
                target = btn.closest('.club-list-item');
                titleEl.textContent = target.getAttribute('data-name') || 'Review member';
                detailsEl.textContent = '';
                FIELDS.forEach(([label, key]) => {
                    const row = document.createElement('p');
                    row.className = 'club-review-row';
                    const lab = document.createElement('span');
                    lab.textContent = label;
                    const val = document.createElement('strong');
                    val.textContent = target.getAttribute('data-' + key) || '—';
                    row.appendChild(lab);
                    row.appendChild(val);
                    detailsEl.appendChild(row);
                });
                resultSel.value = 'approve';
                remarks.value = '';
                err.hidden = true;
                modal.hidden = false;
                document.body.style.overflow = 'hidden';
            });
        });

        confirmBtn.addEventListener('click', () => {
            const reject = resultSel.value === 'reject';
            if (reject && !remarks.value.trim()) {
                err.textContent = 'Please add a note explaining the rejection.';
                err.hidden = false;
                remarks.focus();
                return;
            }
            const who = target ? (target.getAttribute('data-name') || 'Member') : 'Member';
            if (target) target.remove();
            const remaining = list.querySelectorAll('.club-list-item').length;
            if (heading) heading.textContent = 'Pending Member Approvals (' + remaining + ')';
            if (remaining === 0 && emptyNote) emptyNote.hidden = false;
            close();
            showToast(reject
                ? who + '’s application rejected with note (demo).'
                : who + ' approved as General Member (demo — persists in C13 backend).');
        });
    }
});
</script>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/club.css">

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
