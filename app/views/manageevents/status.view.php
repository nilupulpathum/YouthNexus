<?php
/**
 * Event Status — Divisional Secretary dashboard
 *
 * Uses the shared dashboard layout shell (dashboard-start / dashboard-end).
 */
$title           = $title ?? 'Event Status — YouthNexus';
$pageTitle       = 'Event Details & Submission Status';
$pageDescription = 'Track review progress and specifications for this event';
$currentRoute    = 'manageevents';

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

            <div class="me-status-page">

                <!-- Back button (styled as me-btn-secondary) -->
                <a href="<?= ROOT ?>/manageevents" class="me-btn-secondary me-back-btn">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
                    Back to Manage Events
                </a>

                <div class="me-status-grid">

                    <!-- Left: Event Information Card -->
                    <div class="me-detail-card">
                        <div class="me-detail-card-header">
                            <div>
                                <div class="me-badges-group">
                                    <?php if (!empty($event->organizer_division_id)): ?>
                                        <span class="me-badge me-badge-divisional">Divisional Event</span>
                                    <?php else: ?>
                                        <span class="me-badge me-badge-club">Club Event: <?= htmlspecialchars($event->organizer_club_name ?? 'Club') ?></span>
                                    <?php endif; ?>

                                    <?php if ($event->status === 'PendingApproval'): ?>
                                        <span class="me-badge me-badge-status-pending">Pending Approval</span>
                                    <?php elseif ($event->status === 'Approved'): ?>
                                        <span class="me-badge me-badge-status-approved">Approved</span>
                                    <?php elseif ($event->status === 'Rejected'): ?>
                                        <span class="me-badge me-badge-status-rejected">Rejected</span>
                                    <?php else: ?>
                                        <span class="me-badge me-badge-status-completed"><?= htmlspecialchars($event->status) ?></span>
                                    <?php endif; ?>
                                </div>
                                <h2><?= htmlspecialchars($event->title) ?></h2>
                            </div>

                            <?php if ($can_edit): ?>
                                <button type="button" class="me-btn-secondary" id="btnOpenEditModal">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    Edit Event
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="me-fields-table">
                            <div class="me-field-item">
                                <span class="me-field-label">Event Type</span>
                                <span class="me-field-value"><?= htmlspecialchars($event->event_type ?: 'General') ?></span>
                            </div>

                            <div class="me-field-item">
                                <span class="me-field-label">Target Audience</span>
                                <span class="me-field-value">
                                    <?php if ($event->target_scope === 'AllInScope'): ?>
                                        <span class="me-target-all">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                            All Clubs in Division
                                        </span>
                                    <?php elseif (!empty($targets)): ?>
                                        <ul class="me-target-list">
                                            <?php foreach ($targets as $t): ?>
                                                <?php if (!empty($t->target_club_id)): ?>
                                                    <li class="me-target-list-item">
                                                        <span><?= htmlspecialchars($t->target_club_name ?? 'Club') ?></span>
                                                        <small class="me-club-code"><?= htmlspecialchars($t->target_club_code ?? '') ?></small>
                                                        <?php if (!empty($t->max_attendance)): ?>
                                                            <small class="me-target-max">Max: <?= (int)$t->max_attendance ?></small>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <em style="color: var(--db-text-grey);">No clubs targeted</em>
                                    <?php endif; ?>
                                </span>
                            </div>

                            <div class="me-field-item">
                                <span class="me-field-label">Start Date &amp; Time</span>
                                <span class="me-field-value"><?= date('F j, Y • g:i A', strtotime($event->start_datetime)) ?></span>
                            </div>

                            <div class="me-field-item">
                                <span class="me-field-label">End Date &amp; Time</span>
                                <span class="me-field-value"><?= date('F j, Y • g:i A', strtotime($event->end_datetime)) ?></span>
                            </div>

                            <div class="me-field-item">
                                <span class="me-field-label">Location / Venue</span>
                                <span class="me-field-value"><?= htmlspecialchars($event->location ?: 'Not Specified') ?></span>
                            </div>

                            <div class="me-field-item">
                                <span class="me-field-label">Max Attendees</span>
                                <span class="me-field-value"><?= !empty($event->max_attendance) ? (int)$event->max_attendance . ' attendees (event-wide)' : 'Unlimited / Not specified' ?></span>
                            </div>

                            <div class="me-field-item">
                                <span class="me-field-label">Organized By</span>
                                <span class="me-field-value">
                                    <?php if (!empty($event->organizer_division_id)): ?>
                                        <?= htmlspecialchars($event->organizer_division_name ?? 'Divisional Secretariat') ?>
                                    <?php else: ?>
                                        <?= htmlspecialchars($event->organizer_club_name ?? 'Club') ?>
                                    <?php endif; ?>
                                </span>
                            </div>

                            <div class="me-field-item">
                                <span class="me-field-label">Created By</span>
                                <span class="me-field-value"><?= htmlspecialchars($event->creator_name ?? 'Secretary') ?> <small style="color:var(--db-text-grey)">(<?= htmlspecialchars($event->creator_role ?? 'User') ?>)</small></span>
                            </div>

                            <div class="me-field-item full">
                                <span class="me-field-label">Description &amp; Objectives</span>
                                <div class="me-field-value desc">
                                    <?= !empty($event->description) ? nl2br(htmlspecialchars($event->description)) : '<em>No detailed description provided.</em>' ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Submission Status Panel -->
                    <div class="me-submission-panel">
                        <h3 class="me-panel-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--db-sidebar-bg)" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                            Submission Status
                        </h3>

                        <div class="me-timeline">
                            <!-- Step 1: Inserted -->
                            <div class="me-timeline-step">
                                <div class="me-timeline-dot done">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                </div>
                                <div class="me-timeline-content">
                                    <h4>Event Inserted</h4>
                                    <p>Created by <?= htmlspecialchars($event->creator_name ?? 'Divisional Secretary') ?></p>
                                    <span class="me-timeline-time"><?= date('F j, Y • g:i A', strtotime($event->created_at)) ?></span>
                                </div>
                            </div>

                            <!-- Step 2: Review / Decision -->
                            <?php if ($event->status === 'PendingApproval'): ?>
                                <div class="me-timeline-step">
                                    <div class="me-timeline-dot active">
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><circle cx="12" cy="12" r="1"/></svg>
                                    </div>
                                    <div class="me-timeline-content">
                                        <h4>Pending Zonal Coordinator Approval</h4>
                                        <p>Awaiting review and approval from the Zonal Coordinator.</p>
                                        <span class="me-timeline-time">In Progress</span>
                                    </div>
                                </div>
                            <?php elseif ($event->status === 'Approved'): ?>
                                <div class="me-timeline-step">
                                    <div class="me-timeline-dot done">
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                    </div>
                                    <div class="me-timeline-content">
                                        <h4>Approved</h4>
                                        <p>Approved by <?= htmlspecialchars($event->approver_name ?: 'Zonal Coordinator') ?></p>
                                    </div>
                                </div>
                            <?php elseif ($event->status === 'Rejected'): ?>
                                <div class="me-timeline-step">
                                    <div class="me-timeline-dot rejected">
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                    </div>
                                    <div class="me-timeline-content">
                                        <h4 style="color: #b91c1c;">Rejected</h4>
                                        <p style="color: #991b1b;"><?= !empty($event->rejection_remarks) ? htmlspecialchars($event->rejection_remarks) : 'Application rejected.' ?></p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="me-timeline-step">
                                    <div class="me-timeline-dot done">
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                    </div>
                                    <div class="me-timeline-content">
                                        <h4><?= htmlspecialchars($event->status) ?></h4>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="me-status-notice">
                            <strong style="display: block; margin-bottom: 4px; color: var(--db-text-dark);">Governance Notice</strong>
                            <?php if ($event->status === 'PendingApproval'): ?>
                                Events submitted at the divisional level remain pending until reviewed and approved by the Zonal Coordinator. Divisional Secretaries do not possess approval privileges.
                            <?php elseif ($event->status === 'Approved'): ?>
                                This event has been approved by the Zonal Coordinator and is officially scheduled.
                            <?php elseif ($event->status === 'Rejected'): ?>
                                This event was not approved. Please consult the remarks provided above or reach out to the Zonal Coordinator.
                            <?php else: ?>
                                Event status is currently <?= htmlspecialchars($event->status) ?>.
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

            </div>
        </main>
    </div>
</div>

<?php if ($can_edit): ?>
<!-- ============ Edit Event Modal ============ -->
<div class="me-modal-backdrop" id="editEventModal">
    <div class="me-modal">
        <div class="me-modal-header">
            <h3>Edit Divisional Event</h3>
            <button type="button" class="me-modal-close" aria-label="Close modal">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <form action="<?= ROOT ?>/manageevents/edit/<?= (int)$event->event_id ?>" method="POST" id="editEventForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="me-modal-body">
                <div class="me-form-grid">
                    <div class="me-form-group me-form-full">
                        <label class="me-form-label">Event Title <span class="required">*</span></label>
                        <input type="text" name="title" class="me-form-input" value="<?= htmlspecialchars($event->title) ?>" required maxlength="150">
                    </div>

                    <div class="me-form-group me-form-full">
                        <label class="me-form-label">Event Type</label>
                        <input type="text" name="event_type" class="me-form-input" value="<?= htmlspecialchars($event->event_type ?? '') ?>" maxlength="50">
                        <div class="me-chips-container">
                            <button type="button" class="me-chip <?= ($event->event_type === 'Workshop') ? 'is-active' : '' ?>" data-value="Workshop">Workshop</button>
                            <button type="button" class="me-chip <?= ($event->event_type === 'Meeting') ? 'is-active' : '' ?>" data-value="Meeting">Meeting</button>
                            <button type="button" class="me-chip <?= ($event->event_type === 'Community Service') ? 'is-active' : '' ?>" data-value="Community Service">Community Service</button>
                            <button type="button" class="me-chip <?= ($event->event_type === 'Sports') ? 'is-active' : '' ?>" data-value="Sports">Sports</button>
                        </div>
                    </div>

                    <!-- Target Audience — toggle + checklist (pre-populated from existing targets) -->
                    <div class="me-form-group me-form-full">
                        <label class="me-form-label">Target Audience <span class="required">*</span></label>
                        <div class="me-audience-toggle" role="group" aria-label="Target Audience">
                            <label class="me-toggle-option">
                                <input type="radio" name="target_scope" value="AllInScope" <?= ($event->target_scope === 'AllInScope') ? 'checked' : '' ?>>
                                <span class="me-toggle-btn">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                    All Clubs
                                </span>
                            </label>
                            <label class="me-toggle-option">
                                <input type="radio" name="target_scope" value="SelectedClubs" <?= ($event->target_scope === 'SelectedClubs') ? 'checked' : '' ?>>
                                <span class="me-toggle-btn">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                                    Specific Clubs
                                </span>
                            </label>
                        </div>

                        <!-- Pre-populated club checklist -->
                        <div class="me-club-checklist <?= ($event->target_scope === 'SelectedClubs') ? '' : 'hidden' ?>" id="editClubChecklist">
                            <?php foreach ($clubs as $club): ?>
                                <?php
                                $isChecked      = array_key_exists((int)$club->club_id, $target_map);
                                $overrideVal    = $target_map[(int)$club->club_id] ?? null;
                                ?>
                                <label class="me-club-check-row" data-club-id="<?= (int)$club->club_id ?>">
                                    <div class="me-club-check-left">
                                        <input type="checkbox" name="target_clubs[]" value="<?= (int)$club->club_id ?>" class="me-club-checkbox" <?= $isChecked ? 'checked' : '' ?>>
                                        <span class="me-club-check-name">
                                            <?= htmlspecialchars($club->club_name) ?>
                                            <small class="me-club-code"><?= htmlspecialchars($club->club_code) ?></small>
                                        </span>
                                    </div>
                                    <div class="me-club-override <?= $isChecked ? '' : 'hidden' ?>">
                                        <label class="me-override-label" for="edit_max_<?= (int)$club->club_id ?>">Max attendees for this club</label>
                                        <input type="number"
                                               name="max_attendance_club_<?= (int)$club->club_id ?>"
                                               id="edit_max_<?= (int)$club->club_id ?>"
                                               class="me-form-input me-override-input"
                                               placeholder="Optional override"
                                               min="1"
                                               value="<?= !empty($overrideVal) ? (int)$overrideVal : '' ?>">
                                    </div>
                                </label>
                            <?php endforeach; ?>
                            <?php if (empty($clubs)): ?>
                                <p class="me-club-checklist-empty">No active clubs found in your division.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="me-form-group">
                        <label class="me-form-label">Max Attendees <small style="font-weight:400;color:var(--db-text-grey)">(event-wide)</small></label>
                        <input type="number" name="max_attendance" class="me-form-input" value="<?= !empty($event->max_attendance) ? (int)$event->max_attendance : '' ?>" min="1">
                    </div>

                    <div class="me-form-group">
                        <label class="me-form-label">Start Date &amp; Time <span class="required">*</span></label>
                        <input type="datetime-local" name="start_datetime" class="me-form-input" value="<?= date('Y-m-d\TH:i', strtotime($event->start_datetime)) ?>" required>
                    </div>

                    <div class="me-form-group">
                        <label class="me-form-label">End Date &amp; Time <span class="required">*</span></label>
                        <input type="datetime-local" name="end_datetime" class="me-form-input" value="<?= date('Y-m-d\TH:i', strtotime($event->end_datetime)) ?>" required>
                    </div>

                    <!-- Inline Datetime Alert Box -->
                    <div class="me-form-group me-form-full">
                        <div class="me-validation-alert" id="editDateAlert">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            <span class="me-validation-msg">Event start must be after now, and end must be after start</span>
                        </div>
                    </div>

                    <div class="me-form-group me-form-full">
                        <label class="me-form-label">Location</label>
                        <input type="text" name="location" class="me-form-input" value="<?= htmlspecialchars($event->location ?? '') ?>" maxlength="255">
                    </div>

                    <div class="me-form-group me-form-full">
                        <label class="me-form-label">Description</label>
                        <textarea name="description" class="me-form-textarea" maxlength="1000"><?= htmlspecialchars($event->description ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="me-modal-footer">
                <button type="button" class="me-btn-secondary me-btn-cancel">Cancel</button>
                <button type="submit" class="me-btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/manageevents.css">
<script src="<?= ROOT ?>/assets/js/manageevents.js?v=<?= time() ?>"></script>
<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
