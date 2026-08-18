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
                <?php if (!empty($stats['awaiting_approval'])): ?>
                    <span class="db-nav-badge"><?= (int)$stats['awaiting_approval'] ?></span>
                <?php endif; ?>
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
                <h1>Manage Events</h1>
                <p>Track divisional events and club activities across <?= htmlspecialchars($division->division_name ?? 'your division') ?></p>
            </div>
            <div class="db-topbar-right">
                <button class="db-icon-btn" title="Pending Approvals">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <?php if (!empty($stats['awaiting_approval'])): ?>
                        <span class="db-badge-dot"><?= (int)$stats['awaiting_approval'] ?></span>
                    <?php endif; ?>
                </button>
                <div class="db-topbar-avatar"><?= htmlspecialchars($user_initials ?? 'NF') ?></div>
            </div>
        </header>

        <main class="db-content">

            <!-- Top Action Header -->
            <div class="me-header-row">
                <div class="me-header-title">
                    <h2>Events Overview</h2>
                    <p>Overview of scheduled and submitted events in <?= htmlspecialchars($division->division_name ?? 'your division') ?></p>
                </div>
                <button type="button" class="me-btn-primary" id="btnOpenCreateModal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Create Divisional Event
                </button>
            </div>

            <!-- Stat Cards -->
            <div class="me-stats-grid">
                <div class="me-stat-card">
                    <div class="me-stat-icon awaiting">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div class="me-stat-info">
                        <div class="me-stat-value"><?= (int)$stats['awaiting_approval'] ?></div>
                        <div class="me-stat-label">Awaiting Approval</div>
                    </div>
                </div>

                <div class="me-stat-card">
                    <div class="me-stat-icon approved">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <div class="me-stat-info">
                        <div class="me-stat-value"><?= (int)$stats['approved_upcoming'] ?></div>
                        <div class="me-stat-label">Approved / Upcoming</div>
                    </div>
                </div>

                <div class="me-stat-card">
                    <div class="me-stat-icon hosted">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
                    <div class="me-stat-info">
                        <div class="me-stat-value"><?= (int)$stats['hosted_this_year'] ?></div>
                        <div class="me-stat-label">Hosted This Year</div>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Bar -->
            <form method="GET" action="<?= ROOT ?>/manageevents" class="me-filter-bar" id="eventFilterForm">
                <div class="me-search-wrap">
                    <span class="me-search-icon">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </span>
                    <input type="text" name="search" class="me-search-input" placeholder="Search events by title, type, location..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>

                <div class="me-filter-group">
                    <select name="status" class="me-select">
                        <option value="All" <?= ($filters['status'] ?? '') === 'All' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="PendingApproval" <?= ($filters['status'] ?? '') === 'PendingApproval' ? 'selected' : '' ?>>Pending Approval</option>
                        <option value="Approved" <?= ($filters['status'] ?? '') === 'Approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="Rejected" <?= ($filters['status'] ?? '') === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                        <option value="Completed" <?= ($filters['status'] ?? '') === 'Completed' ? 'selected' : '' ?>>Completed</option>
                    </select>

                    <select name="target_club_id" class="me-select">
                        <option value="">All Target Clubs</option>
                        <?php foreach ($clubs as $club): ?>
                            <option value="<?= (int)$club->club_id ?>" <?= ((int)($filters['target_club_id'] ?? 0) === (int)$club->club_id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($club->club_name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <input type="date" name="date_from" class="me-date-input" title="From Date" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
                    <input type="date" name="date_to" class="me-date-input" title="To Date" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">

                    <button type="submit" class="me-btn-secondary">Filter</button>
                    <?php if (!empty($filters['search']) || ($filters['status'] ?? 'All') !== 'All' || !empty($filters['target_club_id']) || !empty($filters['date_from']) || !empty($filters['date_to'])): ?>
                        <a href="<?= ROOT ?>/manageevents" class="me-btn-reset">Reset</a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Events List Grid -->
            <?php if (empty($events)): ?>
                <div class="me-empty-state">
                    <div class="me-empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
                    <h3>No events found</h3>
                    <p>No events match your current filter criteria or no events have been scheduled yet in this division.</p>
                    <button type="button" class="me-btn-primary" onclick="document.getElementById('btnOpenCreateModal').click()">
                        + Create Your First Event
                    </button>
                </div>
            <?php else: ?>
                <div class="me-events-grid">
                    <?php foreach ($events as $event): ?>
                        <div class="me-event-card">
                            <div>
                                <div class="me-card-header">
                                    <div class="me-badges-group">
                                        <?php if (!empty($event->organizer_division_id)): ?>
                                            <span class="me-badge me-badge-divisional">Divisional Event</span>
                                        <?php else: ?>
                                            <span class="me-badge me-badge-club">Club Event: <?= htmlspecialchars($event->organizer_club_name ?? 'Club') ?></span>
                                        <?php endif; ?>
                                    </div>

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

                                <div class="me-card-body">
                                    <h3 class="me-card-title"><?= htmlspecialchars($event->title) ?></h3>
                                    <?php if (!empty($event->event_type)): ?>
                                        <span class="me-card-type-chip"><?= htmlspecialchars($event->event_type) ?></span>
                                    <?php endif; ?>

                                    <div class="me-card-meta">
                                        <div class="me-meta-item">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                            <span><?= date('M d, Y • h:i A', strtotime($event->start_datetime)) ?></span>
                                        </div>
                                        <?php if (!empty($event->location)): ?>
                                            <div class="me-meta-item">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                                <span><?= htmlspecialchars($event->location) ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($event->target_club_name)): ?>
                                            <div class="me-meta-item">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                                <span>Target: <?= htmlspecialchars($event->target_club_name) ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($event->max_attendance)): ?>
                                            <div class="me-meta-item">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                                                <span>Max Attendees: <?= (int)$event->max_attendance ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="me-card-footer">
                                <span class="me-card-author">By <?= htmlspecialchars($event->creator_name ?? 'Secretary') ?></span>
                                <a href="<?= ROOT ?>/manageevents/status/<?= (int)$event->event_id ?>" class="me-btn-view">
                                    View Details
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<!-- ============ Create Divisional Event Modal ============ -->
<div class="me-modal-backdrop" id="createEventModal">
    <div class="me-modal">
        <div class="me-modal-header">
            <h3>Create Divisional Event</h3>
            <button type="button" class="me-modal-close" aria-label="Close modal">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <form action="<?= ROOT ?>/manageevents/create" method="POST" id="createEventForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <div class="me-modal-body">
                <div class="me-form-grid">
                    <div class="me-form-group me-form-full">
                        <label class="me-form-label">Event Title <span class="required">*</span></label>
                        <input type="text" name="title" class="me-form-input" placeholder="e.g., Annual Divisional Youth Leadership Summit" required maxlength="150">
                    </div>

                    <div class="me-form-group me-form-full">
                        <label class="me-form-label">Event Type</label>
                        <input type="text" name="event_type" class="me-form-input" placeholder="Type or select a suggestion below" maxlength="50">
                        <div class="me-chips-container">
                            <button type="button" class="me-chip" data-value="Workshop">Workshop</button>
                            <button type="button" class="me-chip" data-value="Meeting">Meeting</button>
                            <button type="button" class="me-chip" data-value="Community Service">Community Service</button>
                            <button type="button" class="me-chip" data-value="Sports">Sports</button>
                        </div>
                    </div>

                    <div class="me-form-group">
                        <label class="me-form-label">Select Target Audience <span class="required">*</span></label>
                        <select name="target_club_id" class="me-form-select" required>
                            <option value="">-- Choose Target Club --</option>
                            <?php foreach ($clubs as $club): ?>
                                <option value="<?= (int)$club->club_id ?>">
                                    <?= htmlspecialchars($club->club_name) ?> (<?= htmlspecialchars($club->club_code) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="me-form-group">
                        <label class="me-form-label">Max Attendees</label>
                        <input type="number" name="max_attendance" class="me-form-input" placeholder="e.g., 100" min="1">
                    </div>

                    <div class="me-form-group">
                        <label class="me-form-label">Start Date & Time <span class="required">*</span></label>
                        <input type="datetime-local" name="start_datetime" class="me-form-input" required>
                    </div>

                    <div class="me-form-group">
                        <label class="me-form-label">End Date & Time <span class="required">*</span></label>
                        <input type="datetime-local" name="end_datetime" class="me-form-input" required>
                    </div>

                    <!-- Inline Datetime Alert Box -->
                    <div class="me-form-group me-form-full">
                        <div class="me-validation-alert" id="createDateAlert">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            <span class="me-validation-msg">Event start must be after now, and end must be after start</span>
                        </div>
                    </div>

                    <div class="me-form-group me-form-full">
                        <label class="me-form-label">Location</label>
                        <input type="text" name="location" class="me-form-input" placeholder="e.g., Divisional Secretariat Auditorium, Colombo 07" maxlength="255">
                    </div>

                    <div class="me-form-group me-form-full">
                        <label class="me-form-label">Description</label>
                        <textarea name="description" class="me-form-textarea" placeholder="Provide event objectives, schedule, prerequisites or special notes..." maxlength="1000"></textarea>
                    </div>
                </div>
            </div>

            <div class="me-modal-footer">
                <button type="button" class="me-btn-secondary me-btn-cancel">Cancel</button>
                <button type="submit" class="me-btn-primary">Create Event</button>
            </div>
        </form>
    </div>
</div>

<script src="<?= ROOT ?>/assets/js/manageevents.js?v=<?= time() ?>"></script>
</body>
</html>
