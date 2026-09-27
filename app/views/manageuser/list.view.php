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
$title            = $title ?? 'Manage User — YouthNexus';
$pageTitle        = $pageTitle ?? 'National Personnel & User Governance';
$pageDescription  = $pageDescription ?? 'Manage NYSC administrative personnel across all zones and divisions.';
$currentRoute     = 'manageuser';

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
<style>
/* ---- variables ---- */
:root {
    --mu-blue:      #1e40af;
    --mu-blue-lt:   #2563eb;
    --mu-indigo:    #e0e7ff;
    --mu-sky:       #e0f2fe;
    --mu-green-lt:  #d1fae5;
    --mu-green:     #059669;
    --mu-gray-lt:   #f3f4f6;
    --mu-gray:      #6b7280;
    --mu-border:    #eef0f4;
    --mu-radius:    10px;
    --mu-shadow:    0 1px 3px rgba(0,0,0,.07);
    --mu-red:       #ef4444;
}

/* ---- page head / action row ---- */
.mu-page-head {
    display: flex !important;
    justify-content: flex-end !important;
    align-items: center;
    margin-bottom: 24px;
    gap: 16px;
    flex-wrap: wrap;
    width: 100%;
}
.mu-head-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-left: auto;
}
.mu-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 13.5px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: opacity .15s, background .15s, box-shadow .15s;
    text-decoration: none;
    white-space: nowrap;
}
.mu-btn:hover { opacity: .88; }
.mu-btn-light {
    background: #fff;
    color: #374151;
    border: 1px solid #d1d5db;
    box-shadow: var(--mu-shadow);
}
.mu-btn-primary {
    background: var(--mu-blue-lt);
    color: #fff;
    box-shadow: 0 2px 6px rgba(37,99,235,.25);
}
.mu-btn-primary:hover { background: var(--mu-blue); }

/* ---- stat cards ---- */
.mu-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 22px;
}
@media (max-width: 900px) { .mu-stats { grid-template-columns: repeat(2,1fr); } }
@media (max-width: 540px) { .mu-stats { grid-template-columns: 1fr; } }

.mu-stat-card {
    background: #fff;
    border-radius: var(--mu-radius);
    border: 1px solid var(--mu-border);
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: var(--mu-shadow);
    transition: box-shadow .2s;
}
.mu-stat-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,.09); }
.mu-stat-label {
    font-size: 12.5px;
    color: var(--mu-gray);
    margin-bottom: 10px;
    font-weight: 500;
}
.mu-stat-num {
    font-size: 30px;
    font-weight: 800;
    color: #111827;
    line-height: 1;
}
.mu-stat-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.mu-icon-blue   { background: #dbeafe; color: var(--mu-blue); }
.mu-icon-sky    { background: var(--mu-sky); color: #0284c7; }
.mu-icon-indigo { background: var(--mu-indigo); color: #4338ca; }
.mu-icon-gray   { background: var(--mu-gray-lt); color: #6b7280; }

/* ---- panel ---- */
.mu-panel {
    background: #fff;
    border-radius: 12px;
    border: 1px solid var(--mu-border);
    overflow: hidden;
    box-shadow: var(--mu-shadow);
}

/* ---- filters (attendance-style toolbar + collapsible panel) ---- */
.mu-toolbar {
    width: 100%;
    display: flex;
    align-items: center;
    flex-wrap: nowrap;
    gap: 12px;
    margin-bottom: 16px;
}
.mu-search-group {
    position: relative;
    flex: 1 1 auto;
    min-width: 0;
    height: 40px;
    box-sizing: border-box;
    overflow: hidden;
    display: flex;
    align-items: center;
    background: #ffffff;
    border: 1px solid var(--mu-border, #e2e8f0);
    border-radius: 10px;
    box-shadow: 0 1px 2px rgba(18, 20, 26, 0.04);
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.mu-search-group:hover { border-color: #cbd5e1; }
.mu-search-group:focus-within {
    border-color: #5b82df;
    box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.10);
}
.mu-search-icon {
    width: 18px;
    height: 18px;
    margin-right: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 18px;
    color: #94a3b8;
    pointer-events: none;
}
.mu-search-icon svg { width: 16px; height: 16px; }
.mu-search-input-wrapper {
    flex: 1 1 auto;
    width: 100%;
    min-width: 0;
    height: 100%;
    box-sizing: border-box;
    display: flex;
    align-items: center;
    margin: 0 !important;
    padding: 0 10px 0 14px !important;
    background: transparent !important;
    border: 0 !important;
    border-radius: 0 !important;
    outline: none !important;
    box-shadow: none !important;
    transform: none !important;
}
.dashboard .mu-search-input,
.dashboard .mu-search-input:hover,
.dashboard .mu-search-input:focus,
.dashboard .mu-search-input:focus-visible,
.dashboard .mu-search-input:active {
    position: static !important;
    flex: 1 1 auto;
    width: 100%;
    min-width: 0;
    height: 100%;
    box-sizing: border-box;
    margin: 0 !important;
    padding: 0 !important;
    appearance: none;
    -webkit-appearance: none;
    background: transparent !important;
    color: var(--db-text-dark, #111827);
    border: 0 !important;
    border-radius: 0 !important;
    outline: none !important;
    box-shadow: none !important;
    font-family: var(--db-font-family) !important;
    font-size: 14px !important;
    font-weight: 400 !important;
    line-height: normal !important;
    transform: none !important;
    transition: none !important;
}
.dashboard .mu-search-input::placeholder { color: #94a3b8; opacity: 1; }
.mu-filter-btn {
    flex: 0 0 auto;
    height: 40px;
    min-height: 40px;
    box-sizing: border-box;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin: 0;
    padding: 0 16px;
    background: #ffffff;
    color: var(--db-text-dark, #111827);
    border: 1px solid var(--mu-border, #e2e8f0);
    border-radius: 10px;
    box-shadow: 0 1px 2px rgba(18, 20, 26, 0.04);
    font-family: var(--db-font-family);
    font-size: 13px;
    font-weight: 600;
    line-height: 1;
    white-space: nowrap;
    cursor: pointer;
    transition: color 0.15s ease, background-color 0.15s ease, border-color 0.15s ease;
}
.mu-filter-btn:hover,
.mu-filter-btn[aria-expanded="true"] {
    color: var(--db-sidebar-bg, #1e40af);
    background: #f8fafc;
    border-color: #b9c8ee;
}
.mu-filter-count {
    min-width: 19px;
    height: 19px;
    box-sizing: border-box;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 5px;
    background: var(--db-sidebar-bg, #1e40af);
    color: #ffffff;
    border-radius: 999px;
    font-family: var(--db-font-family);
    font-size: 11px;
    font-weight: 600;
    line-height: 1;
}
.mu-filter-count.hidden { display: none; }
.mu-filter-panel {
    display: none;
    align-items: flex-end;
    gap: 16px;
    flex-wrap: wrap;
    background: #ffffff;
    border: 1px solid var(--mu-border, #e2e8f0);
    border-radius: 12px;
    padding: 16px 18px;
    margin: 0 0 16px;
}
.mu-filter-panel.open { display: flex; }
.mu-filter-grid {
    flex: 1 1 560px;
    display: flex;
    align-items: flex-end;
    flex-wrap: wrap;
    gap: 16px;
}
.mu-filter-field {
    flex: 1 1 180px;
    min-width: 180px;
    display: flex;
    flex-direction: column;
    gap: 5px;
}
.mu-filter-field label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.3px;
    color: var(--db-text-grey, #64748b);
    text-transform: uppercase;
}
.mu-filter-field select {
    height: 38px;
    box-sizing: border-box;
    padding: 9px 10px;
    border: 1px solid var(--mu-border, #e2e8f0);
    border-radius: 8px;
    font-family: var(--db-font-family);
    font-size: 13px;
    background: #ffffff;
    color: var(--db-text-dark, #111827);
    outline: none;
    cursor: pointer;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.mu-filter-field select:focus {
    border-color: #5b82df;
    box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.10);
}
.mu-filter-actions {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    margin-left: auto;
}
.mu-filter-actions .mu-filter-apply {
    min-height: 34px;
    box-sizing: border-box;
    padding: 7px 12px;
    font-size: 12px;
    font-weight: 600;
    line-height: 1;
    background: var(--mu-blue, #1e40af);
    color: #ffffff !important;
    border: 1px solid var(--mu-blue, #1e40af);
    border-radius: 8px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-family: var(--db-font-family);
}
.mu-filter-actions .mu-filter-apply:hover {
    background: #1d3c9f;
    border-color: #1d3c9f;
}
.mu-filter-reset {
    min-height: 34px;
    box-sizing: border-box;
    padding: 7px 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    background: #ffffff;
    color: var(--db-text-dark, #111827);
    border: 1px solid var(--mu-border, #e2e8f0);
    border-radius: 8px;
    font-family: var(--db-font-family);
    font-size: 12px;
    font-weight: 600;
    line-height: 1;
    cursor: pointer;
    white-space: nowrap;
}
.mu-filter-reset:hover { background: #f8fafc; border-color: #cbd5e1; }

/* ---- table summary ---- */
.mu-showing {
    padding: 12px 20px;
    font-size: 13px;
    color: #374151;
    border-bottom: 1px solid #f0f0f0;
    background: #fafbfd;
}

/* ---- users table container ---- */
.mu-table-container {
    width: 100%;
    overflow-x: auto;
    overflow-y: auto;
    max-height: calc(100vh - 360px);
    min-height: 250px;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 #f8fafc;
    position: relative;
    -webkit-overflow-scrolling: touch;
}
.mu-table-container::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.mu-table-container::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
.mu-table-container::-webkit-scrollbar-track {
    background: #f8fafc;
}

/* ---- users table ---- */
.mu-table { width: 100%; border-collapse: collapse; }
.mu-table th {
    position: sticky;
    top: 0;
    z-index: 5;
    text-align: left;
    font-size: 11px;
    color: var(--mu-gray);
    font-weight: 700;
    letter-spacing: .6px;
    padding: 12px 18px;
    border-bottom: 1px solid #f0f0f0;
    text-transform: uppercase;
    background: #fafbfd;
    box-shadow: inset 0 -1px 0 #f0f0f0;
}
.mu-table td {
    padding: 15px 18px;
    border-bottom: 1px solid var(--mu-border);
    font-size: 13.5px;
    vertical-align: middle;
    color: #374151;
}
.mu-table tbody tr:hover td { background: #f8faff; }
.mu-table tbody tr:last-child td { border-bottom: none; }

/* user cell */
.mu-user-cell { display: flex; align-items: center; gap: 11px; }
.mu-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: var(--mu-indigo);
    color: var(--mu-blue);
    font-weight: 700;
    font-size: 12.5px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    letter-spacing: .5px;
}
.mu-user-name  { font-weight: 600; color: #111827; font-size: 13.5px; }
.mu-user-joined { font-size: 11.5px; color: var(--mu-gray); margin-top: 2px; }

/* level / role cell */
.mu-role     { color: #111827; font-weight: 500; font-size: 13px; }
.mu-level    { font-size: 11.5px; color: var(--mu-blue-lt); margin-top: 2px; }

/* contact cell */
.mu-phone { font-size: 12px; color: var(--mu-gray); margin-top: 2px; }

/* badge */
.mu-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 11px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
}
.mu-dot { width: 7px; height: 7px; border-radius: 50%; }
.mu-badge-active  { background: var(--mu-green-lt); color: var(--mu-green); }
.mu-badge-active .mu-dot  { background: #10b981; }
.mu-badge-inactive { background: var(--mu-gray-lt); color: var(--mu-gray); }
.mu-badge-inactive .mu-dot { background: #9ca3af; }

/* actions */
.mu-actions { display: flex; align-items: center; gap: 4px; }
.mu-action-btn {
    width: 32px;
    height: 32px;
    border-radius: 7px;
    border: 1px solid #e5e7eb;
    background: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #374151;
    transition: background .15s, border-color .15s, color .15s;
    font-size: 13px;
}
.mu-action-btn:hover { background: #f0f4ff; border-color: var(--mu-blue-lt); color: var(--mu-blue); }
.mu-action-btn.deactivate:hover  { background: #fff0f0; border-color: #fca5a5; color: var(--mu-red); }
.mu-action-btn.reactivate:hover  { background: #f0fdf4; border-color: #86efac; color: #16a34a; }
.mu-action-btn.delete { color: #9ca3af; }
.mu-action-btn.delete:hover     { background: #fef2f2; border-color: #ef4444; color: #dc2626; }
.mu-action-btn[disabled]  { opacity: .35; cursor: default; pointer-events: none; }

/* ---- panel footer ---- */
.mu-panel-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 20px;
    font-size: 12.5px;
    color: var(--mu-gray);
    border-top: 1px solid #f0f0f0;
    flex-wrap: wrap;
    gap: 10px;
}
.mu-pagination { display: flex; align-items: center; gap: 5px; }
.mu-page-link {
    min-width: 32px;
    height: 32px;
    border-radius: 7px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    text-decoration: none;
    color: #374151;
    border: 1px solid #e5e7eb;
    background: #fff;
    transition: background .15s;
}
.mu-page-link:hover:not(.current) { background: #f0f4ff; border-color: var(--mu-blue-lt); color: var(--mu-blue); }
.mu-page-link.current { background: var(--mu-blue); color: #fff; border-color: var(--mu-blue); font-weight: 700; }
.mu-page-link.disabled { opacity: .4; pointer-events: none; }

/* ---- empty state ---- */
.mu-empty {
    text-align: center;
    padding: 60px 20px;
    color: var(--mu-gray);
}
.mu-empty svg { opacity: .3; margin-bottom: 14px; }
.mu-empty h3 { font-size: 15px; color: #374151; margin-bottom: 6px; }
.mu-empty p  { font-size: 13px; }

/* ================================================================
   MODAL OVERLAY
   ================================================================ */
.mu-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.45);
    z-index: 1000;
    justify-content: center;
    align-items: center;
    padding: 20px;
    backdrop-filter: blur(2px);
}
.mu-overlay.show { display: flex; }

.mu-modal {
    background: #fff;
    width: 860px;
    max-width: 100%;
    max-height: 92vh;
    overflow-y: auto;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,.2);
    animation: muSlideIn .2s ease;
}
@keyframes muSlideIn {
    from { opacity: 0; transform: translateY(-18px) scale(.97); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

.mu-modal-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 26px 32px;
    border-bottom: 1px solid #f0f0f0;
    position: sticky;
    top: 0;
    background: #fff;
    z-index: 1;
}
.mu-modal-head h2 { font-size: 20px; color: #111827; font-weight: 700; }
.mu-modal-head p  { font-size: 13px; color: var(--mu-gray); margin-top: 3px; }
.mu-modal-close {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    background: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6b7280;
    transition: background .15s;
    flex-shrink: 0;
}
.mu-modal-close:hover { background: #f3f4f6; color: #111827; }

.mu-modal-body { padding: 28px 32px; }

.mu-section-title {
    font-size: 15px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 18px;
    padding-bottom: 10px;
    border-bottom: 1px solid #f0f0f0;
}

/* form grid */
.mu-form-row { display: grid; gap: 18px; margin-bottom: 18px; }
.mu-form-row.cols-2 { grid-template-columns: 1fr 1fr; }
.mu-form-row.cols-3 { grid-template-columns: 1fr 1fr 1fr; }
@media (max-width: 640px) {
    .mu-form-row.cols-2,
    .mu-form-row.cols-3 { grid-template-columns: 1fr; }
}

.mu-field label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 7px;
}
.mu-field label .req { color: var(--mu-red); }
.mu-input-wrap { position: relative; }
.mu-input-wrap .mu-field-icon {
    position: absolute;
    left: 13px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    pointer-events: none;
    display: flex;
}
.mu-input-wrap input,
.mu-input-wrap select {
    width: 100%;
    padding: 12px 14px 12px 40px;
    background: #eef2ff;
    border: 1px solid #e0e7ff;
    border-radius: 8px;
    font-size: 13.5px;
    color: #374151;
    outline: none;
    transition: border-color .15s, background .15s;
    appearance: none;
}
.mu-input-wrap input:focus,
.mu-input-wrap select:focus {
    border-color: var(--mu-blue-lt);
    background: #fff;
}
.mu-input-wrap select { cursor: pointer; }
.mu-select-arrow {
    position: absolute;
    right: 13px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    color: #9ca3af;
}
.mu-field-helper { font-size: 11.5px; color: #9ca3af; margin-top: 6px; }
.mu-field-error  { font-size: 11.5px; color: var(--mu-red); margin-top: 5px; display: none; }
.mu-field-error.show { display: block; }

/* info box */
.mu-info-box {
    display: flex;
    align-items: flex-start;
    gap: 11px;
    background: #eef2ff;
    border: 1px solid #e0e7ff;
    border-radius: 8px;
    padding: 14px 16px;
    font-size: 13px;
    color: #374151;
    margin-top: 6px;
    line-height: 1.5;
}
.mu-info-box svg { flex-shrink: 0; color: var(--mu-blue-lt); margin-top: 1px; }

/* success box (shown after create with temp password) */
.mu-success-box {
    display: none;
    background: #ecfdf5;
    border: 1px solid #6ee7b7;
    border-radius: 10px;
    padding: 18px 20px;
    margin-bottom: 20px;
}
.mu-success-box.show { display: block; }
.mu-success-box h4 { font-size: 14px; color: #065f46; margin-bottom: 8px; }
.mu-temp-pass {
    font-family: monospace;
    font-size: 16px;
    background: #fff;
    padding: 8px 14px;
    border-radius: 6px;
    border: 1px solid #a7f3d0;
    display: inline-block;
    color: #047857;
    letter-spacing: .5px;
    word-break: break-all;
}

/* modal footer */
.mu-modal-foot {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 18px 32px;
    border-top: 1px solid #f0f0f0;
    position: sticky;
    bottom: 0;
    background: #fff;
}
.mu-btn-cancel {
    background: #fff;
    color: #374151;
    border: 1px solid #d1d5db;
}
.mu-btn-submit {
    background: var(--mu-blue);
    color: #fff !important;
    box-shadow: 0 2px 6px rgba(30,64,175,.2);
}
.mu-btn-submit:hover { background: #1d3c9f; color: #fff !important; }
.mu-btn-submit:disabled { opacity: .6; cursor: not-allowed; }

/* spinner inside button */
.mu-spinner {
    display: none;
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255,255,255,.4);
    border-top-color: #fff;
    border-radius: 50%;
    animation: mu-spin .6s linear infinite;
}
@keyframes mu-spin { to { transform: rotate(360deg); } }

/* ---- toast notification ---- */
.mu-toast {
    position: fixed;
    bottom: 28px;
    right: 28px;
    padding: 14px 20px;
    border-radius: 10px;
    font-size: 13.5px;
    font-weight: 500;
    box-shadow: 0 8px 24px rgba(0,0,0,.14);
    z-index: 9999;
    opacity: 0;
    transform: translateY(12px);
    transition: opacity .25s, transform .25s;
    max-width: 340px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.mu-toast.show { opacity: 1; transform: translateY(0); }
.mu-toast-success { background: #065f46; color: #fff; }
.mu-toast-error   { background: #991b1b; color: #fff; }
.mu-toast-warning { background: #b45309; color: #fff; }
</style>

<!-- ============================================================
     ACTION ROW
     ============================================================ -->
<div class="mu-page-head mu-action-row db-action-row">
    <div class="mu-head-actions">
        <button class="mu-btn mu-btn-light db-secondary-action" id="mu-export-btn" type="button">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export CSV
        </button>
        <button class="mu-btn mu-btn-primary db-primary-action" id="mu-add-user-btn" type="button">
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

    <!-- search + collapsible filters (attendance-style) -->
    <?php
    $muActiveFilters = 0;
    foreach (['level', 'position', 'status'] as $muKey) {
        if ($filters[$muKey] !== '') {
            $muActiveFilters++;
        }
    }
    ?>
    <form method="GET" action="<?= ROOT ?>/manageuser" id="mu-filter-form">
        <div class="mu-toolbar">
            <div class="mu-search-group">
                <div class="mu-search-input-wrapper">
                    <input type="text"
                           name="search"
                           class="mu-search-input"
                           placeholder="Search by name, NIC, or email…"
                           value="<?= htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8') ?>"
                           id="mu-search-input"
                           aria-label="Search users"
                           autocomplete="off">
                </div>
                <span class="mu-search-icon" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </span>
            </div>

            <button type="button"
                    class="mu-filter-btn"
                    id="muFilterBtn"
                    aria-expanded="<?= $muActiveFilters > 0 ? 'true' : 'false' ?>"
                    aria-controls="muFilterPanel">
                Filters
                <span class="mu-filter-count<?= $muActiveFilters > 0 ? '' : ' hidden' ?>" id="muFilterCount"><?= $muActiveFilters ?></span>
            </button>
        </div>

        <div class="mu-filter-panel<?= $muActiveFilters > 0 ? ' open' : '' ?>" id="muFilterPanel">
            <div class="mu-filter-grid">
                <div class="mu-filter-field">
                    <label for="mu-filter-level">Level</label>
                    <select name="level" id="mu-filter-level">
                        <option value="">All Levels</option>
                        <?php foreach (array_keys($levelRoles) as $lvl): ?>
                            <option value="<?= htmlspecialchars($lvl) ?>" <?= $filters['level'] === $lvl ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lvl) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mu-filter-field">
                    <label for="mu-filter-position">Position</label>
                    <select name="position" id="mu-filter-position">
                        <option value="">All Positions</option>
                        <?php foreach ($allRoles as $r): ?>
                            <option value="<?= $r ?>" <?= $filters['position'] === $r ? 'selected' : '' ?>>
                                <?= htmlspecialchars($roleLabels[$r] ?? $r) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mu-filter-field">
                    <label for="mu-filter-status">Status</label>
                    <select name="status" id="mu-filter-status">
                        <option value="">All Statuses</option>
                        <option value="Active"   <?= $filters['status'] === 'Active'   ? 'selected' : '' ?>>Active</option>
                        <option value="Inactive" <?= $filters['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="mu-filter-actions">
                <button type="submit" class="mu-btn mu-btn-submit mu-filter-apply">Apply Filters</button>
                <button type="button" class="mu-filter-reset" id="mu-reset-btn">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.5"/></svg>
                    Reset
                </button>
            </div>
        </div>
    </form>

    <!-- showing row -->
    <div class="mu-showing">
        Showing <b><?= count($users) ?></b> of <b><?= $total ?></b> users
        <?php if ($filters['search'] || $filters['level'] || $filters['position'] || $filters['status']): ?>
            &nbsp;—&nbsp;
            <a href="<?= ROOT ?>/manageuser" style="color:var(--mu-blue-lt);font-size:12.5px;">Clear filters</a>
        <?php endif; ?>
    </div>

    <!-- table -->
    <div class="mu-table-container">
    <table class="mu-table">
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
                        <!-- Permanent delete (deactivated accounts only) -->
                        <button type="button"
                                class="mu-action-btn delete mu-delete-btn"
                                data-uid="<?= (int)$u->user_id ?>"
                                data-name="<?= $fullName ?>"
                                title="Permanently delete account">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
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
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;opacity:.6"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            Soft delete policy enforced: Deactivated accounts retain complete financial ledger audit history. Only accounts with no financial, audit, or operational history can be permanently deleted.
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
        <p style="font-size:13px;color:#065f46;margin-bottom:8px;">
            Temporary credentials for the new account:
        </p>
        <div class="mu-temp-pass" id="mu-temp-pass-display">—</div>
        <p id="mu-email-status" style="font-size:12px;color:#6b7280;margin-top:10px;">
            These credentials have also been emailed to the user, who will be prompted to change this password on first login.
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
                        <input type="text" id="add-nic" name="nic" maxlength="12" placeholder="e.g. 199028403912 or 902843912V">
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
            <div class="mu-section-title" style="margin-top:24px;">Role &amp; Jurisdiction</div>

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
            <button type="button" class="mu-btn mu-btn-cancel" data-close="mu-add-modal">Cancel</button>
            <button type="submit" class="mu-btn mu-btn-submit" id="mu-add-submit">
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
                        <input type="text" id="edit-nic" name="nic" maxlength="12">
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

            <div class="mu-section-title" style="margin-top:24px;">Role &amp; Jurisdiction</div>

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
            <button type="button" class="mu-btn mu-btn-cancel" data-close="mu-edit-modal">Cancel</button>
            <button type="submit" class="mu-btn mu-btn-submit" id="mu-edit-submit">
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
<script>
(function () {
    'use strict';

    // ---- Config ----
    const ROOT      = <?= json_encode(ROOT) ?>;
    const CSRF      = <?= json_encode($csrfToken) ?>;

    // Zones and roles data from PHP
    const ZONES     = <?= json_encode(array_map(fn($z) => ['id' => $z->zonal_id, 'name' => $z->zonal_name], $zones)) ?>;
    const LEVEL_ROLES = <?= json_encode(array_map(
        fn($roles) => array_map(fn($r) => ['value' => $r, 'label' => $roleLabels[$r] ?? $r], $roles),
        $levelRoles
    )) ?>;

    // ----------------------------------------------------------------
    // TOAST
    // ----------------------------------------------------------------
    const toast = document.getElementById('mu-toast');
    let toastTimer;
    function showToast(msg, type = 'success') {
        clearTimeout(toastTimer);
        toast.textContent = msg;
        toast.className = 'mu-toast mu-toast-' + type + ' show';
        toastTimer = setTimeout(() => toast.classList.remove('show'), 4000);
    }

    // ----------------------------------------------------------------
    // MODAL OPEN / CLOSE
    // ----------------------------------------------------------------
    function openModal(id) {
        document.getElementById(id).classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    function closeModal(id) {
        document.getElementById(id).classList.remove('show');
        document.body.style.overflow = '';
    }

    // Close buttons
    document.querySelectorAll('[data-close]').forEach(btn => {
        btn.addEventListener('click', () => closeModal(btn.dataset.close));
    });
    // Click outside modal
    ['mu-add-modal', 'mu-edit-modal'].forEach(id => {
        document.getElementById(id).addEventListener('click', function(e) {
            if (e.target === this) closeModal(id);
        });
    });
    // ESC key
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            closeModal('mu-add-modal');
            closeModal('mu-edit-modal');
        }
    });

    // ----------------------------------------------------------------
    // OPEN ADD MODAL
    // ----------------------------------------------------------------
    document.getElementById('mu-add-user-btn').addEventListener('click', () => {
        resetForm('mu-add-form');
        document.getElementById('mu-add-success').classList.remove('show');
        openModal('mu-add-modal');
    });

    // ----------------------------------------------------------------
    // DYNAMIC ROLE + JURISDICTION DROPDOWNS
    // ----------------------------------------------------------------

    function populatePositions(levelSel, posSel, currentVal = '') {
        const level  = levelSel.value;
        const roles  = LEVEL_ROLES[level] || [];
        posSel.innerHTML = '<option value="">Select position…</option>';
        roles.forEach(r => {
            const opt = document.createElement('option');
            opt.value = r.value;
            opt.textContent = r.label;
            if (r.value === currentVal) opt.selected = true;
            posSel.appendChild(opt);
        });
    }

    function populateJurisdiction(levelSel, jurisSel, zonalHid, divHid, currentZonal = null, currentDiv = null) {
        const level = levelSel.value;
        jurisSel.innerHTML = '<option value="">Loading…</option>';
        zonalHid.value = '';
        divHid.value   = '';

        if (level === 'Zonal Level') {
            jurisSel.innerHTML = '<option value="">Select zone…</option>';
            ZONES.forEach(z => {
                const opt = document.createElement('option');
                opt.value = z.id;
                opt.textContent = z.name;
                if (z.id == currentZonal) opt.selected = true;
                jurisSel.appendChild(opt);
            });
            jurisSel.onchange = () => {
                zonalHid.value = jurisSel.value;
                divHid.value   = '';
            };
            if (currentZonal) zonalHid.value = currentZonal;

        } else if (level === 'Divisional Level') {
            // Fetch divisions via AJAX
            fetch(ROOT + '/manageuser/getdivisions', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                jurisSel.innerHTML = '<option value="">Select division…</option>';
                (data.divisions || []).forEach(d => {
                    const opt = document.createElement('option');
                    opt.value = d.division_id;
                    opt.textContent = d.division_name;
                    if (d.division_id == currentDiv) opt.selected = true;
                    jurisSel.appendChild(opt);
                });
                jurisSel.onchange = () => {
                    divHid.value   = jurisSel.value;
                    zonalHid.value = '';
                };
                if (currentDiv) divHid.value = currentDiv;
            })
            .catch(() => { jurisSel.innerHTML = '<option value="">Error loading divisions</option>'; });
        } else {
            jurisSel.innerHTML = '<option value="">Select level first…</option>';
        }
    }

    // Add modal level/position wiring
    const addLevel = document.getElementById('add-level');
    const addPos   = document.getElementById('add-position');
    const addJuris = document.getElementById('add-jurisdiction');
    const addZon   = document.getElementById('add-zonal-id');
    const addDiv   = document.getElementById('add-division-id');

    addLevel.addEventListener('change', () => {
        populatePositions(addLevel, addPos);
        populateJurisdiction(addLevel, addJuris, addZon, addDiv);
    });

    // Edit modal level/position wiring
    const editLevel = document.getElementById('edit-level');
    const editPos   = document.getElementById('edit-position');
    const editJuris = document.getElementById('edit-jurisdiction');
    const editZon   = document.getElementById('edit-zonal-id');
    const editDiv   = document.getElementById('edit-division-id');

    editLevel.addEventListener('change', () => {
        populatePositions(editLevel, editPos);
        populateJurisdiction(editLevel, editJuris, editZon, editDiv);
    });

    // ----------------------------------------------------------------
    // FIELD ERROR HELPERS
    // ----------------------------------------------------------------
    function clearErrors(prefix) {
        document.querySelectorAll('[id^="err-' + prefix + '"]').forEach(el => {
            el.textContent = '';
            el.classList.remove('show');
        });
    }
    function showErrors(prefix, errors) {
        Object.entries(errors).forEach(([field, msg]) => {
            const el = document.getElementById('err-' + prefix + field);
            if (el) { el.textContent = msg; el.classList.add('show'); }
        });
    }
    function resetForm(formId) {
        const form = document.getElementById(formId);
        if (form) form.reset();
        const prefix = formId === 'mu-add-form' ? 'add-' : 'edit-';
        clearErrors(prefix);
    }

    // ----------------------------------------------------------------
    // FETCH WRAPPER
    // ----------------------------------------------------------------
    function postJSON(url, formData) {
        return fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData,
        }).then(r => r.json());
    }

    // ----------------------------------------------------------------
    // LOADING STATE
    // ----------------------------------------------------------------
    function setLoading(submitBtn, spinnerId, iconId, on) {
        const btn = document.getElementById(submitBtn);
        const sp  = document.getElementById(spinnerId);
        const ic  = document.getElementById(iconId);
        if (on) {
            btn.disabled = true;
            sp.style.display  = 'block';
            if (ic) ic.style.display = 'none';
        } else {
            btn.disabled = false;
            sp.style.display  = 'none';
            if (ic) ic.style.display = '';
        }
    }

    // ----------------------------------------------------------------
    // ADD USER FORM SUBMIT
    // ----------------------------------------------------------------
    document.getElementById('mu-add-form').addEventListener('submit', function(e) {
        e.preventDefault();
        clearErrors('add-');
        setLoading('mu-add-submit', 'mu-add-spinner', 'mu-add-icon', true);

        const fd = new FormData(this);
        postJSON(ROOT + '/manageuser/create', fd)
            .then(data => {
                setLoading('mu-add-submit', 'mu-add-spinner', 'mu-add-icon', false);
                if (data.success) {
                    // show temp password + email delivery status
                    const createdEmail = document.getElementById('add-email').value;
                    const sb = document.getElementById('mu-add-success');
                    document.getElementById('mu-temp-pass-display').textContent = data.tempPassword || '—';
                    const emailStatus = document.getElementById('mu-email-status');
                    if (data.emailSent) {
                        emailStatus.textContent = 'Login details were emailed to ' + createdEmail + '. The user will be prompted to change this password on first login.';
                        emailStatus.style.color = '#065f46';
                    } else {
                        emailStatus.textContent = 'The notification email could not be sent — share the temporary password with ' + createdEmail + ' directly.';
                        emailStatus.style.color = '#b45309';
                    }
                    sb.classList.add('show');
                    // Reset the form
                    this.reset();
                    addPos.innerHTML   = '<option value="">Select position…</option>';
                    addJuris.innerHTML = '<option value="">Select level first…</option>';
                    showToast(data.message, 'success');
                    // Reload page after 3s to show new user in table
                    setTimeout(() => location.reload(), 3000);
                } else {
                    if (data.errors) showErrors('add-', data.errors);
                    else showToast(data.message || 'Failed to create user.', 'error');
                }
            })
            .catch(() => {
                setLoading('mu-add-submit', 'mu-add-spinner', 'mu-add-icon', false);
                showToast('Network error. Please try again.', 'error');
            });
    });

    // ----------------------------------------------------------------
    // EDIT — open and pre-fill
    // ----------------------------------------------------------------
    document.querySelectorAll('.mu-edit-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const uid = btn.dataset.uid;
            resetForm('mu-edit-form');
            document.getElementById('edit-user-id').value = uid;

            // Fetch user data
            fetch(ROOT + '/manageuser/getuser/' + uid, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) { showToast('Could not load user.', 'error'); return; }
                const u = data.user;
                document.getElementById('edit-first-name').value = u.first_name || '';
                document.getElementById('edit-last-name').value  = u.last_name  || '';
                document.getElementById('edit-nic').value        = u.NIC        || '';
                document.getElementById('edit-email').value      = u.email      || '';
                document.getElementById('edit-phone').value      = u.phone_number || '';

                // Set level, then populate positions, then set position
                editLevel.value = u.level;
                populatePositions(editLevel, editPos, u.role);
                populateJurisdiction(editLevel, editJuris, editZon, editDiv, u.zonal_id, u.division_id);

                openModal('mu-edit-modal');
            })
            .catch(() => showToast('Network error loading user.', 'error'));
        });
    });

    // ----------------------------------------------------------------
    // EDIT FORM SUBMIT
    // ----------------------------------------------------------------
    document.getElementById('mu-edit-form').addEventListener('submit', function(e) {
        e.preventDefault();
        clearErrors('edit-');
        const uid = document.getElementById('edit-user-id').value;
        setLoading('mu-edit-submit', 'mu-edit-spinner', 'mu-edit-icon', true);

        const fd = new FormData(this);
        postJSON(ROOT + '/manageuser/update/' + uid, fd)
            .then(data => {
                setLoading('mu-edit-submit', 'mu-edit-spinner', 'mu-edit-icon', false);
                if (data.success) {
                    closeModal('mu-edit-modal');
                    showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    if (data.errors) showErrors('edit-', data.errors);
                    else showToast(data.message || 'Failed to update user.', 'error');
                }
            })
            .catch(() => {
                setLoading('mu-edit-submit', 'mu-edit-spinner', 'mu-edit-icon', false);
                showToast('Network error. Please try again.', 'error');
            });
    });

    // ----------------------------------------------------------------
    // STATUS TOGGLE (deactivate / reactivate)
    // ----------------------------------------------------------------
    document.querySelectorAll('.mu-status-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const uid       = btn.dataset.uid;
            const newStatus = btn.dataset.newStatus;
            const name      = btn.dataset.name;
            const verb      = newStatus === 'Active' ? 'reactivate' : 'deactivate';

            if (!confirm('Are you sure you want to ' + verb + ' the account for ' + name + '?')) return;

            const fd = new FormData();
            fd.append('csrf_token', CSRF);
            fd.append('new_status', newStatus);

            postJSON(ROOT + '/manageuser/setstatus/' + uid, fd)
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => location.reload(), 1200);
                    } else {
                        showToast(data.message || 'Action failed.', 'error');
                    }
                })
                .catch(() => showToast('Network error.', 'error'));
        });
    });

    // ----------------------------------------------------------------
    // PERMANENT DELETE (deactivated accounts only)
    // ----------------------------------------------------------------
    document.querySelectorAll('.mu-delete-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const uid  = btn.dataset.uid;
            const name = btn.dataset.name;

            const msg = 'Permanently delete the account for ' + name + '?\n\n'
                + 'This removes the account forever and cannot be undone. '
                + 'Accounts with any financial, audit, or operational history are protected and cannot be deleted.';
            if (!confirm(msg)) return;

            const fd = new FormData();
            fd.append('csrf_token', CSRF);

            postJSON(ROOT + '/manageuser/delete/' + uid, fd)
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => location.reload(), 1200);
                    } else if (data.suspended) {
                        showToast(data.message, 'warning');
                        setTimeout(() => location.reload(), 1600);
                    } else {
                        showToast(data.message || 'Delete failed.', 'error');
                    }
                })
                .catch(() => showToast('Network error.', 'error'));
        });
    });

    // ----------------------------------------------------------------
    // FILTER PANEL — attendance-style toggle
    // ----------------------------------------------------------------
    const muFilterBtn   = document.getElementById('muFilterBtn');
    const muFilterPanel = document.getElementById('muFilterPanel');

    if (muFilterBtn && muFilterPanel) {
        muFilterBtn.addEventListener('click', () => {
            const open = muFilterPanel.classList.toggle('open');
            muFilterBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    // Search with debounce (submits the GET form)
    const searchInput = document.getElementById('mu-search-input');
    if (searchInput) {
        let searchTimer;
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => document.getElementById('mu-filter-form').submit(), 500);
        });
    }

    // Reset filters
    document.getElementById('mu-reset-btn').addEventListener('click', () => {
        window.location.href = ROOT + '/manageuser';
    });

    // ----------------------------------------------------------------
    // EXPORT CSV (placeholder — triggers page with ?export=1)
    // ----------------------------------------------------------------
    document.getElementById('mu-export-btn').addEventListener('click', () => {
        window.location.href = ROOT + '/manageuser?export=1&' +
            new URLSearchParams(<?= json_encode($filters) ?>).toString();
    });

})();
</script>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
