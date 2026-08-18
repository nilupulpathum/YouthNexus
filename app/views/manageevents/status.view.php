<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="<?= ROOT ?>/assets/css/dashboard.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= ROOT ?>/assets/css/manageevents.css?v=<?= time() ?>">
</head>
<body class="dashboard">
<div class="db-app">

    <!-- ============ Sidebar ============ -->
    <aside class="db-sidebar">
        <div class="db-brand">
            <div class="db-brand-mark">
                <svg viewBox="0 0 24 24" fill="var(--db-sidebar-bg)" stroke="none"><path d="M13 2 3 14h7l-1 8 10-12h-7l1-8z"/></svg>
            </div>
            <div class="db-brand-text">
                <b>YouthNexus</b>
                <span>Div. Secretary</span>
            </div>
        </div>

        <nav class="db-nav">
            <a href="<?= ROOT ?>/dashboard" class="db-nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
            <a href="<?= ROOT ?>/manageevents" class="db-nav-link active">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Manage Events
            </a>
            <a href="<?= ROOT ?>/auth/logout" class="db-nav-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                Logout
            </a>
        </nav>

        <div class="db-sidebar-footer">
            <div class="db-avatar"><?= htmlspecialchars($user_initials ?? 'NF') ?></div>
            <div class="db-who">
                <b><?= htmlspecialchars($user_name ?? 'N. Fernando') ?></b>
                <span>Div. Secretary</span>
            </div>
        </div>
    </aside>

    <!-- ============ Main Content ============ -->
    <div class="db-main">
        <header class="db-topbar">
            <div>
                <h1>Event Details & Submission Status</h1>
                <p>Track review progress and specifications for this event</p>
            </div>
            <div class="db-topbar-right">
                <a href="<?= ROOT ?>/manageevents" class="me-btn-secondary">
                    ← Back to Manage Events
                </a>
                <div class="db-topbar-avatar"><?= htmlspecialchars($user_initials ?? 'NF') ?></div>
            </div>
        </header>

        <main class="db-content">
            <div class="me-status-page">

                <a href="<?= ROOT ?>/manageevents" class="me-back-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
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
                                    <?= htmlspecialchars($event->target_club_name ?: 'Divisional Club') ?>
                                    <?php if (!empty($event->target_club_code)): ?>
                                        <small style="color: var(--db-text-grey);"> (<?= htmlspecialchars($event->target_club_code) ?>)</small>
                                    <?php endif; ?>
                                </span>
                            </div>

                            <div class="me-field-item">
                                <span class="me-field-label">Start Date & Time</span>
                                <span class="me-field-value"><?= date('F j, Y • g:i A', strtotime($event->start_datetime)) ?></span>
                            </div>

                            <div class="me-field-item">
                                <span class="me-field-label">End Date & Time</span>
                                <span class="me-field-value"><?= date('F j, Y • g:i A', strtotime($event->end_datetime)) ?></span>
                            </div>

                            <div class="me-field-item">
                                <span class="me-field-label">Location / Venue</span>
                                <span class="me-field-value"><?= htmlspecialchars($event->location ?: 'Not Specified') ?></span>
                            </div>

                            <div class="me-field-item">
                                <span class="me-field-label">Max Attendees</span>
                                <span class="me-field-value"><?= !empty($event->max_attendance) ? (int)$event->max_attendance . ' attendees' : 'Unlimited / Not specified' ?></span>
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
                                <span class="me-field-value"><?= htmlspecialchars($event->creator_name ?? 'Secretary') ?> (<?= htmlspecialchars($event->creator_role ?? 'User') ?>)</span>
                            </div>

                            <div class="me-field-item full">
                                <span class="me-field-label">Description & Objectives</span>
                                <div class="me-field-value desc">
                                    <?= !empty($event->description) ? nl2br(htmlspecialchars($event->description)) : '<em>No detailed description provided.</em>' ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Submission Status Panel (Read-only replacement for Figma approval card) -->
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

                    <div class="me-form-group">
                        <label class="me-form-label">Select Target Audience <span class="required">*</span></label>
                        <select name="target_club_id" class="me-form-select" required>
                            <option value="">-- Choose Target Club --</option>
                            <?php foreach ($clubs as $club): ?>
                                <option value="<?= (int)$club->club_id ?>" <?= ((int)$event->target_club_id === (int)$club->club_id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($club->club_name) ?> (<?= htmlspecialchars($club->club_code) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="me-form-group">
                        <label class="me-form-label">Max Attendees</label>
                        <input type="number" name="max_attendance" class="me-form-input" value="<?= !empty($event->max_attendance) ? (int)$event->max_attendance : '' ?>" min="1">
                    </div>

                    <div class="me-form-group">
                        <label class="me-form-label">Start Date & Time <span class="required">*</span></label>
                        <input type="datetime-local" name="start_datetime" class="me-form-input" value="<?= date('Y-m-d\TH:i', strtotime($event->start_datetime)) ?>" required>
                    </div>

                    <div class="me-form-group">
                        <label class="me-form-label">End Date & Time <span class="required">*</span></label>
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

<script src="<?= ROOT ?>/assets/js/manageevents.js?v=<?= time() ?>"></script>
</body>
</html>
