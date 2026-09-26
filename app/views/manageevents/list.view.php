<?php
/**
 * Manage Events — Divisional Secretary & NYSC Administrator dashboard
 *
 * Uses the shared dashboard layout shell (dashboard-start / dashboard-end).
 */
$isNyscAdmin             = !empty($is_nysc_admin);
$title                   = $title ?? ($isNyscAdmin ? 'National Event Management — YouthNexus' : 'Manage Events — YouthNexus');
$pageTitle               = $isNyscAdmin ? 'National Event Management' : 'Manage Events';
$pageDescription         = $isNyscAdmin 
    ? 'Schedule national events and monitor all youth activities across all zones, divisions, and clubs'
    : 'Track divisional events and club activities across ' . htmlspecialchars($division->division_name ?? 'your division');
$currentRoute            = 'manageevents';
$unreadNotificationCount = (int)($stats['awaiting_approval'] ?? 0);
$pageStyles              = [
    ROOT . '/assets/css/manageevents.css?v=20260924',
    ROOT . '/assets/css/divisional-summary-standard.css?v=20260924',
];
$pageScripts             = [ROOT . '/assets/js/manageevents.js?v=20260924'];

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

            <?php if (!empty($flash)): ?>
                <div class="me-validation-alert is-visible" role="status"><span class="me-validation-msg"><?= htmlspecialchars($flash['message']) ?></span></div>
            <?php endif; ?>

            <!-- Action Row -->
            <div class="me-header-row me-header-row-actions">
                <button type="button" class="me-btn-primary db-primary-action" id="btnOpenCreateModal">
                    <?= $isNyscAdmin ? 'Create National Event' : 'Create Event' ?>
                </button>
            </div>

            <!-- Stat Cards -->
            <div class="me-stats-grid<?= $isNyscAdmin ? ' me-stats-grid-admin' : '' ?>">
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

                <?php if ($isNyscAdmin && isset($stats['national_events'])): ?>
                    <div class="me-stat-card">
                        <div class="me-stat-icon national">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        </div>
                        <div class="me-stat-info">
                            <div class="me-stat-value"><?= (int)$stats['national_events'] ?></div>
                            <div class="me-stat-label">National Events</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Search & Filter Toolbar -->
            <form method="GET" action="<?= ROOT ?>/manageevents" id="eventFilterForm">
                <?php
                $activeFilters = 0;
                if (!empty($filters['status']) && $filters['status'] !== 'All') $activeFilters++;
                if (!empty($filters['event_type'])) $activeFilters++;
                if (!empty($filters['event_level']) && $filters['event_level'] !== 'All') $activeFilters++;
                if (!empty($filters['zone_id'])) $activeFilters++;
                if (!empty($filters['division_id'])) $activeFilters++;
                if (!empty($filters['club_id'])) $activeFilters++;
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
                            <input type="text" name="search" id="meSearchInput" class="me-search-input" placeholder="Search events by title, organizer, location, type..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                        </div>
                    </div>
                    <button type="button" class="me-filter-btn" id="meFilterBtn" aria-expanded="<?= $activeFilters > 0 ? 'true' : 'false' ?>" aria-controls="meFilterPanel">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>
                        Filters
                        <?php if ($activeFilters > 0): ?>
                            <span class="me-filter-count"><?= $activeFilters ?></span>
                        <?php endif; ?>
                    </button>
                </div>

                <!-- Expandable Filter Panel -->
                <div class="me-filter-panel<?= $activeFilters > 0 ? ' open' : '' ?>" id="meFilterPanel">
                    
                    <?php if ($isNyscAdmin): ?>
                        <!-- Event Level (Admin Only) -->
                        <div class="me-filter-field">
                            <label for="meFilterLevel">Event Level</label>
                            <select id="meFilterLevel" name="event_level">
                                <option value="All" <?= ($filters['event_level'] ?? 'All') === 'All' ? 'selected' : '' ?>>All Levels</option>
                                <option value="National" <?= ($filters['event_level'] ?? '') === 'National' ? 'selected' : '' ?>>National Events</option>
                                <option value="Zonal" <?= ($filters['event_level'] ?? '') === 'Zonal' ? 'selected' : '' ?>>Zonal Events</option>
                                <option value="Divisional" <?= ($filters['event_level'] ?? '') === 'Divisional' ? 'selected' : '' ?>>Divisional Events</option>
                                <option value="Club" <?= ($filters['event_level'] ?? '') === 'Club' ? 'selected' : '' ?>>Club Events</option>
                            </select>
                        </div>

                        <!-- Zone Filter (Admin Only) -->
                        <div class="me-filter-field">
                            <label for="meFilterZone">Zone</label>
                            <select id="meFilterZone" name="zone_id">
                                <option value="">All Zones</option>
                                <?php foreach ($zones as $zone): ?>
                                    <option value="<?= (int)$zone->zonal_id ?>" <?= ((int)($filters['zone_id'] ?? 0) === (int)$zone->zonal_id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($zone->zonal_name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Division Filter (Admin Only) -->
                        <div class="me-filter-field">
                            <label for="meFilterDivision">Division</label>
                            <select id="meFilterDivision" name="division_id">
                                <option value="">All Divisions</option>
                                <?php foreach ($divisions as $div): ?>
                                    <option value="<?= (int)$div->division_id ?>" <?= ((int)($filters['division_id'] ?? 0) === (int)$div->division_id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($div->division_name) ?> (<?= htmlspecialchars($div->zonal_name ?? 'Zone') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

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
                            <option value="Draft" <?= ($filters['status'] ?? '') === 'Draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="PendingApproval" <?= ($filters['status'] ?? '') === 'PendingApproval' ? 'selected' : '' ?>>Pending Approval</option>
                            <option value="Approved" <?= ($filters['status'] ?? '') === 'Approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="CancellationPending" <?= ($filters['status'] ?? '') === 'CancellationPending' ? 'selected' : '' ?>>Cancellation Pending</option>
                            <option value="Cancelled" <?= ($filters['status'] ?? '') === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            <option value="Withdrawn" <?= ($filters['status'] ?? '') === 'Withdrawn' ? 'selected' : '' ?>>Withdrawn</option>
                            <option value="Rejected" <?= ($filters['status'] ?? '') === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                            <option value="Completed" <?= ($filters['status'] ?? '') === 'Completed' ? 'selected' : '' ?>>Completed</option>
                        </select>
                    </div>

                    <!-- Target Audience — two-tiered -->
                    <div class="me-filter-field">
                        <label for="meFilterAudienceScope">Target Audience</label>
                        <select id="meFilterAudienceScope" name="target_scope">
                            <option value="">All Events</option>
                            <option value="AllInScope" <?= ($filters['target_scope'] ?? '') === 'AllInScope' ? 'selected' : '' ?>><?= $isNyscAdmin ? 'All Clubs Nationwide' : 'All Clubs in Division' ?></option>
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
                                    <?= !empty($club->division_name) ? ' — ' . htmlspecialchars($club->division_name) : '' ?>
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
                    <p><?= $isNyscAdmin ? 'No events match your current filter criteria or no events have been created yet.' : 'No events match your current filter criteria or no events have been scheduled yet in this division.' ?></p>
                    <button type="button" class="me-btn-primary db-primary-action" onclick="document.getElementById('btnOpenCreateModal').click()">
                        <?= $isNyscAdmin ? 'Create National Event' : 'Create Your First Event' ?>
                    </button>
                </div>
            <?php else: ?>
                <div class="me-events-grid">
                    <?php foreach ($events as $event):
                        $isNational = empty($event->organizer_division_id) && empty($event->organizer_club_id) && empty($event->organizer_zonal_id);
                        $isZonal = !empty($event->organizer_zonal_id);
                        $isDivisional = !empty($event->organizer_division_id);
                    ?>
                        <div class="me-event-card<?= $isNational ? ' me-event-card-national' : '' ?>">
                            <div>
                                <div class="me-card-header">
                                    <div class="me-badges-group">
                                        <?php if ($isNational): ?>
                                            <span class="me-badge me-badge-national">
                                                <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor" style="margin-right:3px;vertical-align:-1px;"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                                National Event
                                            </span>
                                        <?php elseif ($isZonal): ?>
                                            <span class="me-badge me-badge-zonal">Zonal: <?= htmlspecialchars($event->organizer_zonal_name ?? 'Zone') ?></span>
                                        <?php elseif ($isDivisional): ?>
                                            <span class="me-badge me-badge-divisional">Divisional: <?= htmlspecialchars($event->organizer_division_name ?? 'Division') ?></span>
                                        <?php else: ?>
                                            <span class="me-badge me-badge-club">Club: <?= htmlspecialchars($event->organizer_club_name ?? 'Club') ?></span>
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
                                                <span><?= $isNational ? 'All Clubs Nationwide' : 'All Clubs in Division' ?></span>
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
                                <span class="me-card-author">By <?= htmlspecialchars($event->creator_name ?? ($isNational ? 'NYSC Admin' : 'Secretary')) ?></span>
                                <a href="<?= ROOT ?>/manageevents/status/<?= (int)$event->event_id ?>" class="me-btn-view db-view-button">
                                    View Details
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

<!-- ============ Create Event Modal (National / Divisional) ============ -->
<div class="me-modal-backdrop" id="createEventModal">
    <div class="me-modal">
        <div class="me-modal-header">
            <h3><?= $isNyscAdmin ? 'Create National Event' : 'Create Divisional Event' ?></h3>
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
                        <input type="text" name="title" class="me-form-input" placeholder="<?= $isNyscAdmin ? 'e.g., National Youth Leadership Conference 2026' : 'e.g., Annual Divisional Youth Leadership Summit' ?>" required maxlength="150">
                    </div>

                    <div class="me-form-group me-form-full">
                        <label class="me-form-label">Event Type</label>
                        <input type="text" name="event_type" class="me-form-input" placeholder="Type or select a suggestion below" maxlength="50">
                        <div class="me-chips-container">
                            <?php if ($isNyscAdmin): ?>
                                <button type="button" class="me-chip" data-value="National Conference">National Conference</button>
                                <button type="button" class="me-chip" data-value="Youth Summit">Youth Summit</button>
                                <button type="button" class="me-chip" data-value="Workshop">Workshop</button>
                                <button type="button" class="me-chip" data-value="Community Service">Community Service</button>
                                <button type="button" class="me-chip" data-value="Sports Meet">Sports Meet</button>
                            <?php else: ?>
                                <button type="button" class="me-chip" data-value="Workshop">Workshop</button>
                                <button type="button" class="me-chip" data-value="Meeting">Meeting</button>
                                <button type="button" class="me-chip" data-value="Community Service">Community Service</button>
                                <button type="button" class="me-chip" data-value="Sports">Sports</button>
                            <?php endif; ?>
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
                                    <?= $isNyscAdmin ? 'All Clubs Nationwide' : 'All Clubs' ?>
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
                                            <?php if (!empty($club->division_name)): ?>
                                                <small style="color:var(--db-text-grey);font-size:11px;">(<?= htmlspecialchars($club->division_name) ?>)</small>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="me-club-override hidden">
                                        <label class="me-override-label" for="create_max_<?= (int)$club->club_id ?>">Max attendees for this club</label>
                                        <input type="number" name="max_attendance_club_<?= (int)$club->club_id ?>" id="create_max_<?= (int)$club->club_id ?>" class="me-form-input me-override-input" placeholder="Optional override" min="1">
                                    </div>
                                </label>
                            <?php endforeach; ?>
                            <?php if (empty($clubs)): ?>
                                <p class="me-club-checklist-empty">No active clubs found.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="me-form-group">
                        <label class="me-form-label">Max Attendees <small style="font-weight:400;color:var(--db-text-grey)">(event-wide)</small></label>
                        <input type="number" name="max_attendance" class="me-form-input" placeholder="e.g., 500" min="1">
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
                        <input type="text" name="location" class="me-form-input" placeholder="<?= $isNyscAdmin ? 'e.g., Sugathadasa Indoor Stadium / BMICH, Colombo' : 'e.g., Divisional Secretariat Auditorium, Colombo 07' ?>" maxlength="255">
                    </div>

                    <div class="me-form-group me-form-full">
                        <label class="me-form-label">Description</label>
                        <textarea name="description" class="me-form-textarea" placeholder="Provide event objectives, schedule, prerequisites or special notes..." maxlength="1000"></textarea>
                    </div>
                </div>
            </div>

            <div class="me-modal-footer">
                <button type="button" class="me-btn-secondary me-btn-cancel">Cancel</button>
                <?php if (!$isNyscAdmin): ?>
                    <button type="submit" class="me-btn-secondary" data-submission-mode="draft">Save Draft</button>
                <?php endif; ?>
                <button type="submit" class="me-btn-primary"><?= $isNyscAdmin ? 'Create National Event' : 'Create Event' ?></button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
