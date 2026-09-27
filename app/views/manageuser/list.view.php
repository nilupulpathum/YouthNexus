<?php
/**
 * manageuser/list.view.php
 * ============================================================
 * NYSC Administration → User Management
 *
 * Uses the shared dashboard layout shell.
 * Variables injected by Manageuser::index():
 *   $users, $total, $page, $perPage, $totalPages,
 *   $stats, $zones, $filters,
 *   $roleLabels, $levelRoles, $allRoles,
 *   $csrfToken, $title, $currentRoute, $userRole, $userName
 * ============================================================
 */

// Ensure layout variables are set
$title            = $title ?? 'User Management — YouthNexus';
$pageTitle        = $pageTitle ?? 'User Management';
$pageDescription  = $pageDescription ?? 'Manage NYSC administrative personnel across all zones and divisions.';
$currentRoute     = 'manageuser';

$pageStyles = [ROOT . '/assets/css/manageuser.css'];
$pageScripts = [ROOT . '/assets/js/manageuser.js'];
require __DIR__ . '/../layouts/dashboard-start.view.php';

// ---- Helpers ----
function mu_initials(string $name): string {
    $parts = array_filter(explode(' ', $name));
    $first = strtoupper(substr($parts[0] ?? 'U', 0, 1));
    $last  = strtoupper(substr(end($parts) ?: 'N', 0, 1));
    return $first . $last;
}

function mu_level(string $role): string {
    $z = ['ZonalCoordinator','ZonalSecretary','ZonalTreasurer'];
    return in_array($role, $z) ? 'Zonal Level' : 'Divisional Level';
}

function mu_entity($user): string {
    if (!empty($user->zonal_name))    return htmlspecialchars($user->zonal_name);
    if (!empty($user->division_name)) return htmlspecialchars($user->division_name);
    return '—';
}

function mu_joined(string $createdAt): string {
    return date('M Y', strtotime($createdAt));
}

function mu_status_badge(string $status): string {
    if ($status === 'Active') {
        return '<span class="mu-badge mu-badge-active"><span class="mu-dot"></span>Active</span>';
    }
    return '<span class="mu-badge mu-badge-inactive"><span class="mu-dot"></span>Inactive</span>';
}
?>

<!-- ============================================================
     PAGE STYLES
     ============================================================ -->

<!-- ============================================================
     PAGE HEADING
     ============================================================ -->
<div class="mu-page-head">
    <div class="mu-head-actions yn-ml-auto">
        <button class="yn-btn yn-btn--secondary mu-btn mu-btn-light" id="mu-export-btn" type="button">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export CSV
        </button>
        <button class="yn-btn yn-btn--primary mu-btn mu-btn-primary" id="mu-add-user-btn" type="button">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 1 0-16 0"/><line x1="12" y1="15" x2="12" y2="21"/><line x1="9" y1="18" x2="15" y2="18"/></svg>
            Add User
        </button>
    </div>
</div>

<!-- ============================================================
     STAT CARDS
     ============================================================ -->
<div class="mu-stats">
    <!-- Total Active -->
    <div class="mu-stat-card">
        <div>
            <div class="mu-stat-label">Total Active Users</div>
            <div class="mu-stat-num"><?= $stats['totalActive'] ?></div>
        </div>
        <div class="mu-stat-icon mu-icon-blue">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
    </div>

    <!-- Zonal Personnel -->
    <div class="mu-stat-card">
        <div>
            <div class="mu-stat-label">Zonal Personnel</div>
            <div class="mu-stat-num"><?= $stats['zonalPersonnel'] ?></div>
        </div>
        <div class="mu-stat-icon mu-icon-sky">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        </div>
    </div>

    <!-- Divisional Officers -->
    <div class="mu-stat-card">
        <div>
            <div class="mu-stat-label">Divisional Officers</div>
            <div class="mu-stat-num"><?= $stats['divisionalOfficers'] ?></div>
        </div>
        <div class="mu-stat-icon mu-icon-indigo">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/><line x1="12" y1="12" x2="12" y2="16"/><line x1="10" y1="14" x2="14" y2="14"/></svg>
        </div>
    </div>

    <!-- Deactivated -->
    <div class="mu-stat-card">
        <div>
            <div class="mu-stat-label">Deactivated Accounts</div>
            <div class="mu-stat-num"><?= $stats['deactivated'] ?></div>
        </div>
        <div class="mu-stat-icon mu-icon-gray">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
        </div>
    </div>
</div>

<!-- ============================================================
     FILTERS + TABLE PANEL
     ============================================================ -->
<div class="mu-panel">

    <!-- filter bar -->
    <form method="GET" action="<?= ROOT ?>/manageuser" id="mu-filter-form">
        <div class="mu-filters">
            <!-- search -->
            <div class="mu-search-wrap">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text"
                       name="search"
                       class="mu-search"
                       placeholder="Search by name, NIC, or email…"
                       value="<?= htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8') ?>"
                       id="mu-search-input">
            </div>

            <!-- level -->
            <select name="level" class="mu-filter-select" id="mu-filter-level">
                <option value="">All Levels</option>
                <?php foreach (array_keys($levelRoles) as $lvl): ?>
                    <option value="<?= htmlspecialchars($lvl) ?>" <?= $filters['level'] === $lvl ? 'selected' : '' ?>>
                        <?= htmlspecialchars($lvl) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- position -->
            <select name="position" class="mu-filter-select" id="mu-filter-position">
                <option value="">All Positions</option>
                <?php foreach ($allRoles as $r): ?>
                    <option value="<?= $r ?>" <?= $filters['position'] === $r ? 'selected' : '' ?>>
                        <?= htmlspecialchars($roleLabels[$r] ?? $r) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- status -->
            <select name="status" class="mu-filter-select" id="mu-filter-status">
                <option value="">All Statuses</option>
                <option value="Active"   <?= $filters['status'] === 'Active'   ? 'selected' : '' ?>>Active</option>
                <option value="Inactive" <?= $filters['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>

            <button type="button" class="mu-reset-btn" id="mu-reset-btn">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" class="mu-inline-icon"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.5"/></svg>
                Reset
            </button>
        </div>
    </form>

    <!-- showing row -->
    <div class="mu-showing">
        Showing <b><?= count($users) ?></b> of <b><?= $total ?></b> users
        <?php if ($filters['search'] || $filters['level'] || $filters['position'] || $filters['status']): ?>
            &nbsp;—&nbsp;
            <a href="<?= ROOT ?>/manageuser" class="mu-filter-clear">Clear filters</a>
        <?php endif; ?>
    </div>

    <!-- table -->
    <div class="mu-table-container">
    <table class="yn-table mu-table">
        <thead>
            <tr>
                <th>USER</th>
                <th>NIC NUMBER</th>
                <th>CONTACT</th>
                <th>LEVEL &amp; ROLE</th>
                <th>ASSIGNED ENTITY</th>
                <th>STATUS</th>
                <th>ACTIONS</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($users)): ?>
            <tr>
                <td colspan="7">
                    <div class="mu-empty">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        <h3>No users found</h3>
                        <p>Try adjusting the filters or add a new user.</p>
                    </div>
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($users as $u): ?>
            <?php
                $fullName  = htmlspecialchars($u->full_name, ENT_QUOTES, 'UTF-8');
                $initials  = mu_initials($u->full_name);
                $joined    = mu_joined($u->created_at);
                $uStatus   = ($u->status === 'Active') ? 'Active' : 'Inactive';
                $isActive  = ($u->status === 'Active');
                $level     = mu_level($u->role);
                $entity    = mu_entity($u);
                $roleLabel = htmlspecialchars($roleLabels[$u->role] ?? $u->role, ENT_QUOTES, 'UTF-8');
            ?>
            <tr data-uid="<?= (int)$u->user_id ?>">
                <!-- User -->
                <td>
                    <div class="mu-user-cell">
                        <div class="mu-avatar"><?= $initials ?></div>
                        <div>
                            <div class="mu-user-name"><?= $fullName ?></div>
                            <div class="mu-user-joined">Joined <?= $joined ?></div>
                        </div>
                    </div>
                </td>
                <!-- NIC -->
                <td><?= htmlspecialchars($u->NIC ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <!-- Contact -->
                <td>
                    <?= htmlspecialchars($u->email, ENT_QUOTES, 'UTF-8') ?>
                    <div class="mu-phone"><?= htmlspecialchars($u->phone_number ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                </td>
                <!-- Level & Role -->
                <td>
                    <div class="mu-role"><?= $roleLabel ?></div>
                    <div class="mu-level"><?= htmlspecialchars($level) ?></div>
                </td>
                <!-- Entity -->
                <td><?= $entity ?></td>
                <!-- Status -->
                <td><?= mu_status_badge($u->status) ?></td>
                <!-- Actions -->
                <td>
                    <div class="mu-actions">
                        <!-- Edit -->
                        <button type="button"
                                class="mu-action-btn mu-edit-btn"
                                data-uid="<?= (int)$u->user_id ?>"
                                title="Edit user">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </button>

                        <?php if ($isActive): ?>
                        <!-- Deactivate -->
                        <button type="button"
                                class="mu-action-btn deactivate mu-status-btn"
                                data-uid="<?= (int)$u->user_id ?>"
                                data-new-status="Disabled"
                                data-name="<?= $fullName ?>"
                                title="Deactivate account">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                        </button>
                        <?php else: ?>
                        <!-- Reactivate -->
                        <button type="button"
                                class="mu-action-btn reactivate mu-status-btn"
                                data-uid="<?= (int)$u->user_id ?>"
                                data-new-status="Active"
                                data-name="<?= $fullName ?>"
                                title="Reactivate account">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.5"/></svg>
                        </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
    </div>

    <!-- panel footer -->
    <div class="mu-panel-footer">
        <div>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mu-inline-icon-muted"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            Soft delete policy enforced: Deactivated accounts retain complete financial ledger audit history.
        </div>

        <!-- pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="mu-pagination">
            <?php
            $base = ROOT . '/manageuser?' . http_build_query(array_merge($filters, []));
            ?>

            <!-- prev -->
            <?php if ($page > 1): ?>
                <a href="<?= $base ?>&page=<?= $page - 1 ?>" class="mu-page-link">&#8249;</a>
            <?php else: ?>
                <span class="mu-page-link disabled">&#8249;</span>
            <?php endif; ?>

            <?php
            // Show max 5 page numbers
            $startP = max(1, $page - 2);
            $endP   = min($totalPages, $page + 2);
            if ($startP > 1) echo '<span class="mu-page-link disabled">…</span>';
            for ($p = $startP; $p <= $endP; $p++):
            ?>
                <a href="<?= $base ?>&page=<?= $p ?>" class="mu-page-link<?= $p === $page ? ' current' : '' ?>"><?= $p ?></a>
            <?php endfor; ?>
            <?php if ($endP < $totalPages) echo '<span class="mu-page-link disabled">…</span>'; ?>

            <!-- next -->
            <?php if ($page < $totalPages): ?>
                <a href="<?= $base ?>&page=<?= $page + 1 ?>" class="mu-page-link">&#8250;</a>
            <?php else: ?>
                <span class="mu-page-link disabled">&#8250;</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /mu-panel -->


<!-- ================================================================
     ADD USER MODAL
     ================================================================ -->
<div class="mu-overlay" id="mu-add-modal" role="dialog" aria-modal="true" aria-labelledby="mu-add-title">
  <div class="mu-modal">

    <div class="mu-modal-head">
        <div>
            <h2 id="mu-add-title">Add New User</h2>
            <p>Create a new NYSC administrative personnel account.</p>
        </div>
        <button type="button" class="mu-modal-close" data-close="mu-add-modal" aria-label="Close">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>

    <!-- success box (shown after creation) -->
    <div class="mu-success-box" id="mu-add-success">
        <h4>✅ User Created Successfully!</h4>
        <p class="mu-form-success-note">
            Temporary credentials for the new account:
        </p>
        <div class="mu-temp-pass" id="mu-temp-pass-display">—</div>
        <p class="mu-form-helper-note">
            An automated onboarding email has been queued. The user will be prompted to change this password on first login.
        </p>
    </div>

    <form id="mu-add-form" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <div class="mu-modal-body">

            <!-- Section 1: Personal Details -->
            <div class="mu-section-title">Personal &amp; Identification Details</div>

            <div class="mu-form-row cols-2">
                <div class="mu-field">
                    <label for="add-first-name">First Name <span class="req">*</span></label>
                    <div class="mu-input-wrap">
                        <span class="mu-field-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c1-3.8 3.6-6 8-6s7 2.2 8 6"/></svg></span>
                        <input type="text" id="add-first-name" name="first_name" placeholder="e.g. Kasun" required>
                    </div>
                    <div class="mu-field-error" id="err-add-first_name"></div>
                </div>
                <div class="mu-field">
                    <label for="add-last-name">Last Name <span class="req">*</span></label>
                    <div class="mu-input-wrap">
                        <span class="mu-field-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c1-3.8 3.6-6 8-6s7 2.2 8 6"/></svg></span>
                        <input type="text" id="add-last-name" name="last_name" placeholder="e.g. Perera" required>
                    </div>
                    <div class="mu-field-error" id="err-add-last_name"></div>
                </div>
            </div>

            <div class="mu-form-row">
                <div class="mu-field">
                    <label for="add-nic">National Identity Card (NIC) <span class="req">*</span></label>
                    <div class="mu-input-wrap">
                        <span class="mu-field-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></span>
                        <input type="text" id="add-nic" name="nic" placeholder="e.g. 199028403912 or 902843912V">
                    </div>
                    <div class="mu-field-helper">12-digit NIC or 9-digit + V/X (Sri Lanka).</div>
                    <div class="mu-field-error" id="err-add-NIC"></div>
                </div>
            </div>

            <div class="mu-form-row cols-2">
                <div class="mu-field">
                    <label for="add-email">Official Email Address <span class="req">*</span></label>
                    <div class="mu-input-wrap">
                        <span class="mu-field-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
                        <input type="email" id="add-email" name="email" placeholder="k.perera@nysc.gov.lk" required>
                    </div>
                    <div class="mu-field-error" id="err-add-email"></div>
                </div>
                <div class="mu-field">
                    <label for="add-phone">Contact Phone Number</label>
                    <div class="mu-input-wrap">
                        <span class="mu-field-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.77 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 8.91a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg></span>
                        <input type="text" id="add-phone" name="phone" placeholder="+94 77 123 4567">
                    </div>
                </div>
            </div>

            <!-- Section 2: Role & Jurisdiction -->
            <div class="mu-section-title yn-mt-6">Role &amp; Jurisdiction</div>

            <div class="mu-form-row cols-3">
                <div class="mu-field">
                    <label for="add-level">Administrative Level <span class="req">*</span></label>
                    <div class="mu-input-wrap">
                        <span class="mu-field-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg></span>
                        <select id="add-level" name="level" required>
                            <option value="">Select level…</option>
                            <option value="Zonal Level">Zonal Level</option>
                            <option value="Divisional Level">Divisional Level</option>
                        </select>
                        <span class="mu-select-arrow"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></span>
                    </div>
                </div>

                <div class="mu-field">
                    <label for="add-position">Designated Position <span class="req">*</span></label>
                    <div class="mu-input-wrap">
                        <span class="mu-field-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg></span>
                        <select id="add-position" name="position" required>
                            <option value="">Select position…</option>
                        </select>
                        <span class="mu-select-arrow"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></span>
                    </div>
                    <div class="mu-field-error" id="err-add-role"></div>
                </div>

                <div class="mu-field" id="add-jurisdiction-wrap">
                    <label for="add-jurisdiction">Assigned Jurisdiction <span class="req">*</span></label>
                    <div class="mu-input-wrap">
                        <span class="mu-field-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg></span>
                        <select id="add-jurisdiction" name="entity_id" required>
                            <option value="">Select level first…</option>
                        </select>
                        <span class="mu-select-arrow"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></span>
                    </div>
                    <!-- hidden fields populated by JS -->
                    <input type="hidden" name="zonal_id"    id="add-zonal-id">
                    <input type="hidden" name="division_id" id="add-division-id">
                    <div class="mu-field-error" id="err-add-zonal_id"></div>
                    <div class="mu-field-error" id="err-add-division_id"></div>
                </div>
            </div>

            <!-- info notice -->
            <div class="mu-info-box">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                An automated onboarding notification with temporary credentials will be dispatched to the official email address. The user will be required to change their password on first login.
            </div>

        </div><!-- /modal-body -->

        <div class="mu-modal-foot">
            <button type="button" class="yn-btn yn-btn--secondary mu-btn mu-btn-cancel" data-close="mu-add-modal">Cancel</button>
            <button type="submit" class="yn-btn yn-btn--primary mu-btn mu-btn-submit" id="mu-add-submit">
                <div class="mu-spinner" id="mu-add-spinner"></div>
                <svg id="mu-add-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 1 0-16 0"/><line x1="12" y1="15" x2="12" y2="21"/><line x1="9" y1="18" x2="15" y2="18"/></svg>
                Create User
            </button>
        </div>
    </form>

  </div>
</div><!-- /add modal -->


<!-- ================================================================
     EDIT USER MODAL
     ================================================================ -->
<div class="mu-overlay" id="mu-edit-modal" role="dialog" aria-modal="true" aria-labelledby="mu-edit-title">
  <div class="mu-modal">

    <div class="mu-modal-head">
        <div>
            <h2 id="mu-edit-title">Edit User</h2>
            <p>Update account details for this administrative personnel.</p>
        </div>
        <button type="button" class="mu-modal-close" data-close="mu-edit-modal" aria-label="Close">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>

    <form id="mu-edit-form" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" id="edit-user-id" name="user_id" value="">
        <div class="mu-modal-body">

            <div class="mu-section-title">Personal &amp; Identification Details</div>

            <div class="mu-form-row cols-2">
                <div class="mu-field">
                    <label for="edit-first-name">First Name <span class="req">*</span></label>
                    <div class="mu-input-wrap">
                        <span class="mu-field-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c1-3.8 3.6-6 8-6s7 2.2 8 6"/></svg></span>
                        <input type="text" id="edit-first-name" name="first_name" required>
                    </div>
                    <div class="mu-field-error" id="err-edit-first_name"></div>
                </div>
                <div class="mu-field">
                    <label for="edit-last-name">Last Name <span class="req">*</span></label>
                    <div class="mu-input-wrap">
                        <span class="mu-field-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c1-3.8 3.6-6 8-6s7 2.2 8 6"/></svg></span>
                        <input type="text" id="edit-last-name" name="last_name" required>
                    </div>
                    <div class="mu-field-error" id="err-edit-last_name"></div>
                </div>
            </div>

            <div class="mu-form-row">
                <div class="mu-field">
                    <label for="edit-nic">National Identity Card (NIC)</label>
                    <div class="mu-input-wrap">
                        <span class="mu-field-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></span>
                        <input type="text" id="edit-nic" name="nic">
                    </div>
                    <div class="mu-field-error" id="err-edit-NIC"></div>
                </div>
            </div>

            <div class="mu-form-row cols-2">
                <div class="mu-field">
                    <label for="edit-email">Official Email Address <span class="req">*</span></label>
                    <div class="mu-input-wrap">
                        <span class="mu-field-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
                        <input type="email" id="edit-email" name="email" required>
                    </div>
                    <div class="mu-field-error" id="err-edit-email"></div>
                </div>
                <div class="mu-field">
                    <label for="edit-phone">Contact Phone Number</label>
                    <div class="mu-input-wrap">
                        <span class="mu-field-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.77 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 8.91a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg></span>
                        <input type="text" id="edit-phone" name="phone">
                    </div>
                </div>
            </div>

            <div class="mu-section-title yn-mt-6">Role &amp; Jurisdiction</div>

            <div class="mu-form-row cols-3">
                <div class="mu-field">
                    <label for="edit-level">Administrative Level <span class="req">*</span></label>
                    <div class="mu-input-wrap">
                        <span class="mu-field-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg></span>
                        <select id="edit-level" name="level">
                            <option value="">Select level…</option>
                            <option value="Zonal Level">Zonal Level</option>
                            <option value="Divisional Level">Divisional Level</option>
                        </select>
                        <span class="mu-select-arrow"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></span>
                    </div>
                </div>
                <div class="mu-field">
                    <label for="edit-position">Designated Position <span class="req">*</span></label>
                    <div class="mu-input-wrap">
                        <span class="mu-field-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg></span>
                        <select id="edit-position" name="position">
                            <option value="">Select position…</option>
                        </select>
                        <span class="mu-select-arrow"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></span>
                    </div>
                    <div class="mu-field-error" id="err-edit-role"></div>
                </div>
                <div class="mu-field">
                    <label for="edit-jurisdiction">Assigned Jurisdiction <span class="req">*</span></label>
                    <div class="mu-input-wrap">
                        <span class="mu-field-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg></span>
                        <select id="edit-jurisdiction" name="entity_id">
                            <option value="">Select level first…</option>
                        </select>
                        <span class="mu-select-arrow"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></span>
                    </div>
                    <input type="hidden" name="zonal_id"    id="edit-zonal-id">
                    <input type="hidden" name="division_id" id="edit-division-id">
                    <div class="mu-field-error" id="err-edit-zonal_id"></div>
                    <div class="mu-field-error" id="err-edit-division_id"></div>
                </div>
            </div>

        </div><!-- /modal-body -->

        <div class="mu-modal-foot">
            <button type="button" class="yn-btn yn-btn--secondary mu-btn mu-btn-cancel" data-close="mu-edit-modal">Cancel</button>
            <button type="submit" class="yn-btn yn-btn--primary mu-btn mu-btn-submit" id="mu-edit-submit">
                <div class="mu-spinner" id="mu-edit-spinner"></div>
                <svg id="mu-edit-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Save Changes
            </button>
        </div>
    </form>

  </div>
</div><!-- /edit modal -->

<!-- Toast notification -->
<div class="mu-toast" id="mu-toast" role="alert" aria-live="polite"></div>

<!-- ================================================================
     JAVASCRIPT
     ================================================================ -->
<?php
$manageUserConfig = [
    'root' => ROOT,
    'csrf' => $csrfToken,
    'zones' => array_map(fn($z) => ['id' => $z->zonal_id, 'name' => $z->zonal_name], $zones),
    'levelRoles' => array_map(fn($roles) => array_map(fn($r) => ['value' => $r, 'label' => $roleLabels[$r] ?? $r], $roles), $levelRoles),
    'filters' => $filters,
];
?>
<script type="application/json" id="mu-page-config"><?= json_encode($manageUserConfig, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
