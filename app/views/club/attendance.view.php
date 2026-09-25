<?php
/**
 * Club Attendance — C5 club-scoped mock.
 * Secretary: event list, CSV bulk OR single entry tabs, start/end time +
 * remarks, per-row toggles; unlisted members default to absent. Member:
 * personal summary + history. Presentation-only: no DB writes; the
 * divisional Attendance flow is untouched. Backend contract lands in C13.
 * UI follows the divisional standard (dw-* classes + shared partials).
 */
$e = static function ($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

require __DIR__ . '/../partials/icons.view.php';

$attendanceEvents = $attendanceEvents ?? [];
$mySummary        = $mySummary ?? [];
$can_mark         = !empty($can_mark);

$title = 'Club Attendance - YouthNexus';
$pageTitle = 'Club Attendance';
$pageDescription = 'Session marking and personal attendance record';
$currentRoute = 'club/attendance';
$pageStyles = [ROOT . '/assets/css/divisional-workflows.css'];
$pageScripts = [
    ROOT . '/assets/js/divisional-workflows.js',
    ROOT . '/assets/js/club.js',
];

$summaryCards = [
    ['value' => (string) ($mySummary['sessions'] ?? 0), 'label' => 'Sessions attended', 'note' => 'Club sessions on record', 'icon' => 'calendar', 'tone' => 'blue'],
    ['value' => (string) ($mySummary['rate'] ?? ''), 'label' => 'Attendance rate', 'note' => 'Across recorded sessions', 'icon' => 'check', 'tone' => 'green'],
    ['value' => (string) ($mySummary['absent'] ?? 0), 'label' => 'Times absent', 'note' => 'Unlisted defaults to absent', 'icon' => 'clock', 'tone' => 'amber'],
];

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<section class="dw-page" aria-labelledby="club-attendance-heading">
    <h1 id="club-attendance-heading" class="visually-hidden">Club attendance</h1>

    <div class="dw-alert dw-alert--success" id="club-toast" role="status" hidden></div>

    <?php if ($can_mark): ?>
        <section class="dw-panel" aria-labelledby="club-att-mark-heading">
            <header class="dw-panel__header">
                <div>
                    <h2 id="club-att-mark-heading">Mark Attendance</h2>
                    <p>Secretary action — session times, remarks and roster entries</p>
                </div>
            </header>

            <div class="dw-panel__body">
                <div class="dw-field">
                    <label for="att-event">Event</label>
                    <select id="att-event">
                        <?php foreach ($attendanceEvents as $i => $ev): ?>
                            <option value="<?= (int) $i ?>"><?= $e(($ev['title'] ?? '') . ' — ' . ($ev['date'] ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="dw-field">
                    <label for="att-start">Start time</label>
                    <input id="att-start" type="time" required>
                </div>
                <div class="dw-field">
                    <label for="att-end">End time</label>
                    <input id="att-end" type="time" required>
                </div>
                <div class="dw-field">
                    <label for="att-remarks">Session remarks</label>
                    <input id="att-remarks" type="text" maxlength="255" autocomplete="off" placeholder="e.g., Outdoor session, registers closed at 10:15 AM">
                </div>
                <div class="dw-alert dw-alert--error" id="att-session-error" role="alert" hidden></div>
                <div class="dw-filter-actions">
                    <button type="button" class="dw-button dw-button--primary" id="att-save-session">Save session</button>
                </div>
            </div>

            <hr aria-hidden="true">

            <div class="dw-panel__body" aria-labelledby="club-att-entry-heading">
                <h3 id="club-att-entry-heading" class="visually-hidden">Entry method</h3>
                <div class="dw-audit-tabs" role="tablist" aria-label="Entry method">
                    <button type="button" class="is-active" id="tab-single" role="tab" aria-selected="true" aria-controls="pane-single">Single entry</button>
                    <button type="button" id="tab-bulk" role="tab" aria-selected="false" aria-controls="pane-bulk">Bulk CSV</button>
                </div>

                <div id="pane-single" role="tabpanel" aria-labelledby="tab-single">
                    <form id="att-single-form" novalidate>
                        <div class="dw-field">
                            <label for="att-member">Member</label>
                            <select id="att-member"></select>
                        </div>
                        <div class="dw-field">
                            <label for="att-status">Status</label>
                            <select id="att-status">
                                <option value="present">Present</option>
                                <option value="absent">Absent</option>
                            </select>
                        </div>
                        <div class="dw-filter-actions">
                            <button type="submit" class="dw-button dw-button--primary">Apply entry</button>
                        </div>
                    </form>
                </div>

                <div id="pane-bulk" role="tabpanel" aria-labelledby="tab-bulk" hidden>
                    <form id="att-bulk-form" novalidate>
                        <div class="dw-field">
                            <label for="att-csv">Attendance CSV (name, status per row)</label>
                            <input id="att-csv" type="file" accept=".csv,text/csv">
                        </div>
                        <p id="att-csv-name" hidden></p>
                        <div class="dw-alert dw-alert--error" id="att-bulk-error" role="alert" hidden></div>
                        <div class="dw-filter-actions">
                            <button type="submit" class="dw-button dw-button--primary">Upload CSV</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <section class="dw-panel" aria-labelledby="club-att-roster-heading">
            <header class="dw-panel__header">
                <div>
                    <h2 id="club-att-roster-heading">Attendance</h2>
                    <p id="att-roster-event">Session roster</p>
                </div>
            </header>

            <div class="dw-table-wrap">
                <table class="dw-table">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="club-att-body" data-attendance-events="<?= $e(json_encode(array_values($attendanceEvents ?? []))) ?>"></tbody>
                </table>
            </div>
            <div class="dw-panel__body">
                <p>Members without an entry are marked Absent by default.</p>
            </div>
        </section>
    <?php else: ?>
        <div class="dw-summary-grid dw-summary-grid--three" aria-label="My attendance summary">
            <?php foreach ($summaryCards as $card): ?>
                <?php require __DIR__ . '/../partials/divisional/summary-card.view.php'; ?>
            <?php endforeach; ?>
        </div>

        <section class="dw-panel" aria-labelledby="club-att-history-heading">
            <header class="dw-panel__header">
                <div>
                    <h2 id="club-att-history-heading">My Attendance</h2>
                    <p>My record</p>
                </div>
                <span class="dw-count"><?= count($mySummary['history'] ?? []) ?> <?= count($mySummary['history'] ?? []) === 1 ? 'session' : 'sessions' ?></span>
            </header>

            <div class="dw-record-grid">
                <?php foreach (($mySummary['history'] ?? []) as $h): ?>
                    <article class="dw-record-card">
                        <div class="dw-record-card__header">
                            <div class="dw-record-card__identity">
                                <span class="dw-record-card__icon" aria-hidden="true"><?= yn_icon('calendar') ?></span>
                                <div class="dw-record-card__meta"><span><?= $e($h['date'] ?? '') ?></span></div>
                            </div>
                            <?php $status = $h['status'] ?? ''; require __DIR__ . '/../partials/divisional/status-pill.view.php'; ?>
                        </div>
                        <h3 class="dw-record-card__title"><?= $e($h['title'] ?? '') ?></h3>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</section>



<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
