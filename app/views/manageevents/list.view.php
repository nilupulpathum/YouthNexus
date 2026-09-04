<?php
/**
 * Manage Events — Divisional Secretary dashboard
 *
 * Uses the shared dashboard layout shell (dashboard-start / dashboard-end).
 */
$title                   = $title ?? 'Manage Events — YouthNexus';
$pageTitle               = 'Manage Events';
$pageDescription         = 'Track divisional events and club activities across ' . htmlspecialchars($division->division_name ?? 'your division');
$currentRoute            = 'manageevents';
$unreadNotificationCount = (int)($stats['awaiting_approval'] ?? 0);

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>


            <!-- Action Row -->
            <div class="me-header-row" style="justify-content: flex-end;">
                <button type="button" class="me-btn-primary" id="btnOpenCreateModal">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Create Event
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

            <!-- Search & Filter Toolbar (matches cr-toolbar pattern) -->
            <form method="GET" action="<?= ROOT ?>/manageevents" id="eventFilterForm">
                <?php
                $activeFilters = 0;
                if (!empty($filters['status']) && $filters['status'] !== 'All') $activeFilters++;
                if (!empty($filters['event_type'])) $activeFilters++;
                if (!empty($filters['target_scope'])) $activeFilters++;
                if (!empty($filters['target_club_id'])) $activeFilters++;
                if (!empty($filters['date_from']) || !empty($filters['date_to'])) $activeFilters++;
                ?>
                <div class="me-toolbar">
                    <div class="me-search-group">
                        <div class="me-search-input-wrapper">
                            <span class="me-search-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                            </span>
                            <input type="text" name="search" id="meSearchInput" class="me-search-input" placeholder="Search events by title, type, location..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                        </div>
                    </div>
                    <button type="button" class="me-filter-btn" id="meFilterBtn" aria-expanded="<?= $activeFilters > 0 ? 'true' : 'false' ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>
                        Filters
                        <?php if ($activeFilters > 0): ?>
                            <span class="me-filter-count"><?= $activeFilters ?></span>
                        <?php endif; ?>
                    </button>
                </div>

                <!-- Expandable Filter Panel -->
                <div class="me-filter-panel<?= $activeFilters > 0 ? ' open' : '' ?>" id="meFilterPanel">
                    <!-- Date Range -->
                    <div class="me-filter-field">
                        <label for="meFilterDateFrom">Date From</label>
                        <input type="date" id="meFilterDateFrom" name="date_from" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
                    </div>
                    <div class="me-filter-field">
                        <label for="meFilterDateTo">Date To</label>
                        <input type="date" id="meFilterDateTo" name="date_to" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
                    </div>

                    <!-- Event Type -->
                    <div class="me-filter-field">
                        <label for="meFilterType">Event Type</label>
                        <select id="meFilterType" name="event_type">
                            <option value="">All Types</option>
                            <?php foreach ($event_types as $et): ?>
                                <option value="<?= htmlspecialchars($et->event_type) ?>" <?= ($filters['event_type'] ?? '') === $et->event_type ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($et->event_type) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Event Status -->
                    <div class="me-filter-field">
                        <label for="meFilterStatus">Event Status</label>
                        <select id="meFilterStatus" name="status">
                            <option value="All" <?= ($filters['status'] ?? 'All') === 'All' ? 'selected' : '' ?>>All Statuses</option>
                            <option value="PendingApproval" <?= ($filters['status'] ?? '') === 'PendingApproval' ? 'selected' : '' ?>>Pending Approval</option>
                            <option value="Approved" <?= ($filters['status'] ?? '') === 'Approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="Rejected" <?= ($filters['status'] ?? '') === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                            <option value="Completed" <?= ($filters['status'] ?? '') === 'Completed' ? 'selected' : '' ?>>Completed</option>
                        </select>
                    </div>

                    <!-- Target Audience — two-tiered -->
                    <div class="me-filter-field">
                        <label for="meFilterAudienceScope">Target Audience</label>
                        <select id="meFilterAudienceScope" name="target_scope">
                            <option value="">All Events</option>
                            <option value="AllInScope" <?= ($filters['target_scope'] ?? '') === 'AllInScope' ? 'selected' : '' ?>>All Clubs</option>
                            <option value="SelectedClubs" <?= ($filters['target_scope'] ?? '') === 'SelectedClubs' ? 'selected' : '' ?>>Specific Club</option>
                        </select>
                    </div>
                    <div class="me-filter-field me-filter-club-picker<?= ($filters['target_scope'] ?? '') === 'SelectedClubs' ? '' : ' hidden' ?>" id="meFilterClubPickerWrap">
                        <label for="meFilterTargetClub">Club</label>
                        <select id="meFilterTargetClub" name="target_club_id">
                            <option value="">Any Club</option>
                            <?php foreach ($clubs as $club): ?>
                                <option value="<?= (int)$club->club_id ?>" <?= ((int)($filters['target_club_id'] ?? 0) === (int)$club->club_id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($club->club_name) ?> (<?= htmlspecialchars($club->club_code) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="me-filter-actions">
                        <a href="<?= ROOT ?>/manageevents" class="me-btn" id="meClearFilterBtn">Clear Filters</a>
                        <button type="submit" class="me-btn me-btn-primary" id="meAddFilterBtn">Apply Filters</button>
                    </div>
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
                                        <div class="me-meta-item">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                            <?php if ($event->target_scope === 'AllInScope'): ?>
                                                <span>All Clubs in Division</span>
                                            <?php elseif (!empty($event->target_club_names)): ?>
                                                <span title="<?= htmlspecialchars($event->target_club_names) ?>">
                                                    <?= htmlspecialchars(mb_strlen($event->target_club_names) > 40 ? mb_substr($event->target_club_names, 0, 37) . '…' : $event->target_club_names) ?>
                                                </span>
                                            <?php else: ?>
                                                <span>Specific Clubs</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($event->max_attendance)): ?>
                                            <div class="me-meta-item">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                                                <span>Max: <?= (int)$event->max_attendance ?></span>
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

                    <!-- Target Audience — toggle + checklist -->
                    <div class="me-form-group me-form-full">
                        <label class="me-form-label">Target Audience <span class="required">*</span></label>
                        <div class="me-audience-toggle" role="group" aria-label="Target Audience">
                            <label class="me-toggle-option">
                                <input type="radio" name="target_scope" value="AllInScope" checked>
                                <span class="me-toggle-btn">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                    All Clubs
                                </span>
                            </label>
                            <label class="me-toggle-option">
                                <input type="radio" name="target_scope" value="SelectedClubs">
                                <span class="me-toggle-btn">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                                    Specific Clubs
                                </span>
                            </label>
                        </div>

                        <!-- Club checklist (shown only when Specific Clubs is selected) -->
                        <div class="me-club-checklist hidden" id="createClubChecklist">
                            <?php foreach ($clubs as $club): ?>
                                <label class="me-club-check-row" data-club-id="<?= (int)$club->club_id ?>">
                                    <div class="me-club-check-left">
                                        <input type="checkbox" name="target_clubs[]" value="<?= (int)$club->club_id ?>" class="me-club-checkbox">
                                        <span class="me-club-check-name">
                                            <?= htmlspecialchars($club->club_name) ?>
                                            <small class="me-club-code"><?= htmlspecialchars($club->club_code) ?></small>
                                        </span>
                                    </div>
                                    <div class="me-club-override hidden">
                                        <label class="me-override-label" for="create_max_<?= (int)$club->club_id ?>">Max attendees for this club</label>
                                        <input type="number" name="max_attendance_club_<?= (int)$club->club_id ?>" id="create_max_<?= (int)$club->club_id ?>" class="me-form-input me-override-input" placeholder="Optional override" min="1">
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
                        <input type="number" name="max_attendance" class="me-form-input" placeholder="e.g., 100" min="1">
                    </div>

                    <div class="me-form-group">
                        <label class="me-form-label">Start Date &amp; Time <span class="required">*</span></label>
                        <input type="datetime-local" name="start_datetime" class="me-form-input" required>
                    </div>

                    <div class="me-form-group">
                        <label class="me-form-label">End Date &amp; Time <span class="required">*</span></label>
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

<link rel="stylesheet" href="<?= ROOT ?>/assets/css/manageevents.css">
<link rel="stylesheet" href="<?= ROOT ?>/assets/css/divisional-components.css?v=<?= time() ?>">
<script src="<?= ROOT ?>/assets/js/manageevents.js?v=<?= time() ?>"></script>
<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
