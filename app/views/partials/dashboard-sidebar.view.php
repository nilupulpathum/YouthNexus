<?php
$dashboardRoot = defined('ROOT') ? ROOT : '';
$sidebarDisplayName = trim((string) ($userName ?? 'YouthNexus User')) ?: 'YouthNexus User';
$sidebarInitials = function_exists('mb_substr')
    ? strtoupper(mb_substr($sidebarDisplayName, 0, 1))
    : strtoupper(substr($sidebarDisplayName, 0, 1));
$sidebarRoleLabel = ucwords(str_replace(['_', '-'], ' ', (string) $userRole));
$activePath = trim((string) ($currentRoute ?? $_GET['url'] ?? 'home'), '/');
$roleKey = strtolower(trim((string) $userRole));

$navigation = [
    'president' => [
        ['label' => 'Overview', 'route' => 'president', 'href' => $dashboardRoot . '/president', 'icon' => 'grid'],
        ['label' => 'My Dashboard', 'route' => 'member', 'href' => $dashboardRoot . '/member', 'icon' => 'user'],
        ['label' => 'Browse Events', 'route' => 'events', 'href' => $dashboardRoot . '/events', 'icon' => 'calendar'],
        ['label' => 'Announcements', 'route' => 'announcements', 'href' => $dashboardRoot . '/announcements', 'icon' => 'megaphone', 'badge' => (int) ($unreadNotificationCount ?? 0)],
        ['label' => 'Social CV', 'route' => 'profile', 'href' => $dashboardRoot . '/profile', 'icon' => 'certificate'],
        ['label' => 'Members', 'route' => 'club/members', 'href' => $dashboardRoot . '/club/members', 'icon' => 'users'],
        ['label' => 'Club Events', 'route' => 'club/events', 'href' => $dashboardRoot . '/club/events', 'icon' => 'calendar'],
        ['label' => 'Assets', 'route' => 'club/assets', 'href' => $dashboardRoot . '/club/assets', 'icon' => 'briefcase'],
        ['label' => 'Ledger', 'route' => 'club/ledger', 'href' => $dashboardRoot . '/club/ledger', 'icon' => 'wallet'],
        ['label' => 'Settings', 'route' => 'settings', 'href' => $dashboardRoot . '/settings', 'icon' => 'settings'],
        ['label' => 'Help', 'route' => 'help', 'href' => $dashboardRoot . '/help', 'icon' => 'help'],
    ],
    'secretary' => [
        ['label' => 'Overview', 'route' => 'secretary', 'href' => $dashboardRoot . '/secretary', 'icon' => 'grid'],
        ['label' => 'My Dashboard', 'route' => 'member', 'href' => $dashboardRoot . '/member', 'icon' => 'user'],
        ['label' => 'Browse Events', 'route' => 'events', 'href' => $dashboardRoot . '/events', 'icon' => 'calendar'],
        ['label' => 'Announcements', 'route' => 'announcements', 'href' => $dashboardRoot . '/announcements', 'icon' => 'megaphone', 'badge' => (int) ($unreadNotificationCount ?? 0)],
        ['label' => 'Social CV', 'route' => 'profile', 'href' => $dashboardRoot . '/profile', 'icon' => 'certificate'],
        ['label' => 'Members', 'route' => 'club/members', 'href' => $dashboardRoot . '/club/members', 'icon' => 'users'],
        ['label' => 'Club Events', 'route' => 'club/events', 'href' => $dashboardRoot . '/club/events', 'icon' => 'calendar'],
        ['label' => 'Assets', 'route' => 'club/assets', 'href' => $dashboardRoot . '/club/assets', 'icon' => 'briefcase'],
        ['label' => 'Manage Attendance', 'route' => 'club/attendance', 'href' => $dashboardRoot . '/club/attendance', 'icon' => 'check'],
        ['label' => 'Settings', 'route' => 'settings', 'href' => $dashboardRoot . '/settings', 'icon' => 'settings'],
        ['label' => 'Help', 'route' => 'help', 'href' => $dashboardRoot . '/help', 'icon' => 'help'],
    ],
    'treasurer' => [
        ['label' => 'Overview', 'route' => 'treasurer', 'href' => $dashboardRoot . '/treasurer', 'icon' => 'grid'],
        ['label' => 'My Dashboard', 'route' => 'member', 'href' => $dashboardRoot . '/member', 'icon' => 'user'],
        ['label' => 'Browse Events', 'route' => 'events', 'href' => $dashboardRoot . '/events', 'icon' => 'calendar'],
        ['label' => 'Announcements', 'route' => 'announcements', 'href' => $dashboardRoot . '/announcements', 'icon' => 'megaphone', 'badge' => (int) ($unreadNotificationCount ?? 0)],
        ['label' => 'Social CV', 'route' => 'profile', 'href' => $dashboardRoot . '/profile', 'icon' => 'certificate'],
        ['label' => 'Assets', 'route' => 'club/assets', 'href' => $dashboardRoot . '/club/assets', 'icon' => 'briefcase'],
        ['label' => 'Ledger', 'route' => 'club/ledger', 'href' => $dashboardRoot . '/club/ledger', 'icon' => 'wallet'],
        ['label' => 'Settings', 'route' => 'settings', 'href' => $dashboardRoot . '/settings', 'icon' => 'settings'],
        ['label' => 'Help', 'route' => 'help', 'href' => $dashboardRoot . '/help', 'icon' => 'help'],
    ],
    'member' => [
        ['label' => 'Dashboard', 'route' => 'member', 'href' => $dashboardRoot . '/member', 'icon' => 'grid'],
        ['label' => 'Events', 'route' => 'events', 'href' => $dashboardRoot . '/events', 'icon' => 'calendar'],
        ['label' => 'Attendance', 'route' => 'club/attendance', 'href' => $dashboardRoot . '/club/attendance', 'icon' => 'check'],
        ['label' => 'Announcements', 'route' => 'announcements', 'href' => $dashboardRoot . '/announcements', 'icon' => 'megaphone', 'badge' => (int) ($unreadNotificationCount ?? 0)],
        ['label' => 'Social CV', 'route' => 'profile', 'href' => $dashboardRoot . '/profile', 'icon' => 'certificate'],
        ['label' => 'Settings', 'route' => 'settings', 'href' => $dashboardRoot . '/settings', 'icon' => 'settings'],
        ['label' => 'Help', 'route' => 'help', 'href' => $dashboardRoot . '/help', 'icon' => 'help'],
    ],
    'zonalcoordinator' => [
        ['label' => 'Overview', 'route' => 'home', 'href' => $dashboardRoot . '/home', 'icon' => 'grid'],
        ['label' => 'Pending Approvals', 'route' => 'approvals', 'href' => $dashboardRoot . '/approvals', 'icon' => 'clock'],
        ['label' => 'Clubs in Zone', 'route' => 'clubs', 'href' => $dashboardRoot . '/clubs', 'icon' => 'users'],
        ['label' => 'Reports', 'route' => 'reports', 'href' => $dashboardRoot . '/reports', 'icon' => 'chart'],
        ['label' => 'Announcements', 'route' => 'announcements', 'href' => $dashboardRoot . '/announcements', 'icon' => 'megaphone'],
        ['label' => 'Help', 'route' => 'help', 'href' => $dashboardRoot . '/help', 'icon' => 'help'],
    ],
    'divisionalcoordinator' => [
        ['label' => 'Overview',             'route' => 'home',              'href' => $dashboardRoot . '/home',              'icon' => 'grid'],
        ['label' => 'Approve Registration', 'route' => 'clubregistrationapproval',  'href' => $dashboardRoot . '/clubregistrationapproval/index', 'icon' => 'clipboard'],
        ['label' => 'Approve Events',       'route' => 'eventapproval',     'href' => $dashboardRoot . '/eventapproval',     'icon' => 'calendar'],
        ['label' => 'Clubs in Division',    'route' => 'clubs',             'href' => $dashboardRoot . '/clubs',             'icon' => 'users'],
        ['label' => 'Reports',              'route' => 'reports',           'href' => $dashboardRoot . '/reports',           'icon' => 'chart'],
        ['label' => 'Announcements',        'route' => 'announcements',     'href' => $dashboardRoot . '/announcements',     'icon' => 'megaphone'],
        ['label' => 'Help',                 'route' => 'help',              'href' => $dashboardRoot . '/help',              'icon' => 'help'],
    ],
    'divisionalsecretary' => [
        ['label' => 'Overview', 'route' => 'home', 'href' => $dashboardRoot . '/home', 'icon' => 'grid'],
        ['label' => 'Manage Events', 'route' => 'manageevents', 'href' => $dashboardRoot . '/manageevents', 'icon' => 'calendar'],
        ['label' => 'Manage Attendance', 'route' => 'attendance', 'href' => $dashboardRoot . '/attendance', 'icon' => 'check'],
        ['label' => 'Clubs in Division', 'route' => 'clubs', 'href' => $dashboardRoot . '/clubs', 'icon' => 'users'],
        ['label' => 'Reports', 'route' => 'reports', 'href' => $dashboardRoot . '/reports', 'icon' => 'chart'],
        ['label' => 'Announcements', 'route' => 'announcements', 'href' => $dashboardRoot . '/announcements', 'icon' => 'megaphone'],
        ['label' => 'Help', 'route' => 'help', 'href' => $dashboardRoot . '/help', 'icon' => 'help'],
    ],
    'divisionaltreasurer' => [
        ['label' => 'Overview', 'route' => 'home', 'href' => $dashboardRoot . '/home', 'icon' => 'grid'],
        ['label' => 'Fund Ledger', 'route' => 'ledger', 'href' => $dashboardRoot . '/ledger', 'icon' => 'wallet'],
        ['label' => 'Assets', 'route' => 'assets', 'href' => $dashboardRoot . '/assets', 'icon' => 'briefcase'],
        ['label' => 'Clubs in Division', 'route' => 'clubs', 'href' => $dashboardRoot . '/clubs', 'icon' => 'users'],
        ['label' => 'Reports', 'route' => 'reports', 'href' => $dashboardRoot . '/reports', 'icon' => 'chart'],
        ['label' => 'Help', 'route' => 'help', 'href' => $dashboardRoot . '/help', 'icon' => 'help'],
    ],
    'coordinator' => [
        ['label' => 'Overview',             'route' => 'home',             'href' => $dashboardRoot . '/home',              'icon' => 'grid'],
        ['label' => 'Approve Registration', 'route' => 'clubregistrationapproval', 'href' => $dashboardRoot . '/clubregistrationapproval/index', 'icon' => 'clipboard'],
        ['label' => 'Clubs',               'route' => 'clubs',            'href' => $dashboardRoot . '/clubs',             'icon' => 'users'],
        ['label' => 'Reports',             'route' => 'reports',          'href' => $dashboardRoot . '/reports',           'icon' => 'chart'],
        ['label' => 'Announcements',       'route' => 'announcements',    'href' => $dashboardRoot . '/announcements',     'icon' => 'megaphone'],
        ['label' => 'Help',                'route' => 'help',             'href' => $dashboardRoot . '/help',              'icon' => 'help'],
    ],
    'zonalsecretary' => [
        ['label' => 'Overview', 'route' => 'home', 'href' => $dashboardRoot . '/home', 'icon' => 'grid'],
        ['label' => 'Manage Events', 'route' => 'events', 'href' => $dashboardRoot . '/events', 'icon' => 'calendar'],
        ['label' => 'Manage Attendance', 'route' => 'attendance', 'href' => $dashboardRoot . '/attendance', 'icon' => 'check'],
        ['label' => 'Clubs in Zone', 'route' => 'clubs', 'href' => $dashboardRoot . '/clubs', 'icon' => 'users'],
        ['label' => 'Reports', 'route' => 'reports', 'href' => $dashboardRoot . '/reports', 'icon' => 'chart'],
        ['label' => 'Announcements', 'route' => 'announcements', 'href' => $dashboardRoot . '/announcements', 'icon' => 'megaphone'],
        ['label' => 'Help', 'route' => 'help', 'href' => $dashboardRoot . '/help', 'icon' => 'help'],
    ],
    'zonaltreasurer' => [
        ['label' => 'Overview', 'route' => 'home', 'href' => $dashboardRoot . '/home', 'icon' => 'grid'],
        ['label' => 'Fund Ledger', 'route' => 'ledger', 'href' => $dashboardRoot . '/ledger', 'icon' => 'wallet'],
        ['label' => 'Assets', 'route' => 'assets', 'href' => $dashboardRoot . '/assets', 'icon' => 'briefcase'],
        ['label' => 'Clubs in Zone', 'route' => 'clubs', 'href' => $dashboardRoot . '/clubs', 'icon' => 'users'],
        ['label' => 'Reports', 'route' => 'reports', 'href' => $dashboardRoot . '/reports', 'icon' => 'chart'],
        ['label' => 'Help', 'route' => 'help', 'href' => $dashboardRoot . '/help', 'icon' => 'help'],
    ],
    'admin' => [
        ['label' => 'Dashboard', 'route' => 'nationaldashboard', 'href' => $dashboardRoot . '/nationaldashboard', 'icon' => 'grid'],
        ['label' => 'Manage Events', 'route' => 'manageevents', 'href' => $dashboardRoot . '/manageevents', 'icon' => 'calendar'],
        ['label' => 'View Attendance', 'route' => 'attendance', 'href' => $dashboardRoot . '/attendance', 'icon' => 'check-square'],
        ['label' => 'Manage Reports', 'route' => 'reports', 'href' => $dashboardRoot . '/reports', 'icon' => 'reports'],
        ['label' => 'Annual Audit', 'route' => 'audit', 'href' => $dashboardRoot . '/audit', 'icon' => 'audit'],
        ['label' => 'Club Applications', 'route' => 'applications', 'href' => $dashboardRoot . '/applications', 'icon' => 'clipboard'],
        ['label' => 'All Clubs', 'route' => 'clubs', 'href' => $dashboardRoot . '/clubs', 'icon' => 'users'],
        ['label' => 'All Members', 'route' => 'members', 'href' => $dashboardRoot . '/members', 'icon' => 'user'],
        ['label' => 'Analytics', 'route' => 'analytics', 'href' => $dashboardRoot . '/analytics', 'icon' => 'chart'],
        ['label' => 'Announcements', 'route' => 'announcements', 'href' => $dashboardRoot . '/announcements', 'icon' => 'megaphone'],
        ['label' => 'Audit Log', 'route' => 'audit-log', 'href' => $dashboardRoot . '/audit-log', 'icon' => 'shield'],
        ['label' => 'Help', 'route' => 'help', 'href' => $dashboardRoot . '/help', 'icon' => 'help'],
    ],
    'nyscadmin' => [
        ['label' => 'Dashboard', 'route' => 'nationaldashboard', 'href' => $dashboardRoot . '/nationaldashboard', 'icon' => 'grid'],
        ['label' => 'Manage Events', 'route' => 'manageevents', 'href' => $dashboardRoot . '/manageevents', 'icon' => 'calendar'],
        ['label' => 'View Attendance', 'route' => 'attendance', 'href' => $dashboardRoot . '/attendance', 'icon' => 'check-square'],
        ['label' => 'Fund Transfer', 'route' => 'fundtransfer', 'href' => $dashboardRoot . '/fundtransfer', 'icon' => 'transfer'],
        ['label' => 'Manage Reports', 'route' => 'reports', 'href' => $dashboardRoot . '/reports', 'icon' => 'reports'],
        ['label' => 'Manage User', 'route' => 'manageuser', 'href' => $dashboardRoot . '/manageuser', 'icon' => 'user-cog'],
        ['label' => 'Manage Assets', 'route' => 'manageassets', 'href' => $dashboardRoot . '/manageassets', 'icon' => 'assets'],
        ['label' => 'Annual Audit', 'route' => 'audit', 'href' => $dashboardRoot . '/audit', 'icon' => 'audit'],
        ['label' => 'Club Applications', 'route' => 'applications', 'href' => $dashboardRoot . '/applications', 'icon' => 'clipboard'],
        ['label' => 'All Clubs', 'route' => 'clubs', 'href' => $dashboardRoot . '/clubs', 'icon' => 'users'],
        ['label' => 'All Members', 'route' => 'members', 'href' => $dashboardRoot . '/members', 'icon' => 'user'],
        ['label' => 'Analytics', 'route' => 'analytics', 'href' => $dashboardRoot . '/analytics', 'icon' => 'chart'],
        ['label' => 'Announcements', 'route' => 'announcements', 'href' => $dashboardRoot . '/announcements', 'icon' => 'megaphone'],
        ['label' => 'Audit Log', 'route' => 'audit-log', 'href' => $dashboardRoot . '/audit-log', 'icon' => 'shield'],
        ['label' => 'Settings', 'route' => 'settings', 'href' => $dashboardRoot . '/settings', 'icon' => 'settings'],
        ['label' => 'Help', 'route' => 'help', 'href' => $dashboardRoot . '/help', 'icon' => 'help'],
    ],
];

$normalisedRoleKey = preg_replace('/[^a-z]/', '', $roleKey);
$roleAliases = [
    'clubpresident' => 'president',
    'clubsecretary' => 'secretary',
    'clubtreasurer' => 'treasurer',
    'clubmember' => 'member',
    'zonal' => 'zonalcoordinator',
    'zonalcoordinator' => 'zonalcoordinator',
    'divisionalsecretary' => 'divisionalsecretary',
    'divisional' => 'divisionalcoordinator',
    'nyscadministrator' => 'nyscadmin',
    'nyscadmin' => 'nyscadmin',
    'nysc' => 'nyscadmin',
];
$normalisedRoleKey = $roleAliases[$normalisedRoleKey] ?? $normalisedRoleKey;
if ($normalisedRoleKey === 'member') {
    $sidebarRoleLabel = 'Member';
}
$sidebarBrandLabel = $normalisedRoleKey === 'member' ? 'Pulse' : $sidebarRoleLabel;
$roleItems = $navigation[$normalisedRoleKey] ?? [
    ['label' => 'Overview', 'route' => 'home', 'href' => $dashboardRoot . '/home', 'icon' => 'grid'],
    ['label' => 'Help', 'route' => 'help', 'href' => $dashboardRoot . '/help', 'icon' => 'help'],
];

$icons = [
    'grid' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/></svg>',
    'users' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="8" r="3"/><path d="M3.5 19c.5-3.2 2.2-5 5.5-5s5 1.8 5.5 5" stroke-linecap="round"/><path d="M16 5.5a3 3 0 0 1 0 5.8M17 14.4c2.1.8 3.2 2.3 3.5 4.6" stroke-linecap="round"/></svg>',
    'user' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3.5"/><path d="M5 20c.7-3.4 3-5 7-5s6.3 1.6 7 5" stroke-linecap="round"/></svg>',
    'calendar' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16" stroke-linecap="round"/></svg>',
    'briefcase' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18M10 12v2h4v-2" stroke-linecap="round"/></svg>',
    'wallet' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 6h13a2 2 0 0 1 2 2v10H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h11" stroke-linecap="round" stroke-linejoin="round"/><path d="M20 11h-5a2 2 0 0 0 0 4h5M16 13h.01" stroke-linecap="round"/></svg>',
    'megaphone' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m4 13 13 5V6L4 11v2Z" stroke-linejoin="round"/><path d="M17 10.5V6M7 14l2 5h3l-2-4.2M20 10v4" stroke-linecap="round"/></svg>',
    'settings' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>',
    'help' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="8.5"/><path d="M9.6 9a2.6 2.6 0 1 1 4.5 1.8c-1.2 1.1-2.1 1.3-2.1 3M12 17h.01" stroke-linecap="round"/></svg>',
    'clock' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="8.5"/><path d="M12 7v5l3 2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    'chart' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 19V9M12 19V5M19 19v-7" stroke-linecap="round"/><path d="M3 19h18" stroke-linecap="round"/></svg>',
    'clipboard' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4.5V3h6v1.5M8 10h8M8 14h6" stroke-linecap="round"/></svg>',
    'shield' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m12 3 7 3v5c0 4.5-2.5 7.7-7 10-4.5-2.3-7-5.5-7-10V6l7-3Z" stroke-linejoin="round"/><path d="m9 12 2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    'check' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m5 12 4 4L19 6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    'certificate' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 3.5h10a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-10a2 2 0 0 1 2-2Z"/><path d="M8.5 8h7M8.5 11.5h7M8.5 15h3" stroke-linecap="round"/><path d="m15 17 1.5 3 1.5-1 1.5 1 1.5-3" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    'hours' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="8.5"/><path d="M12 7v5l3 2M5.5 5.5l-1.5-1.5M18.5 5.5 20 4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    'logout' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10 5H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h4M14 16l4-4-4-4M18 12H9" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    'reports' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18"/><path d="M9 20V9"/><path d="M15 20V9"/></svg>',
    'check-square' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    'audit' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><circle cx="11.5" cy="17.5" r="2.5"/><line x1="13.3" y1="19.3" x2="16" y2="22"/></svg>',
    'assets' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/><circle cx="7" cy="15" r="1"/></svg>',
    'user-cog' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4"/><circle cx="19" cy="19" r="2"/><path d="M19 15v2M19 21v1M15.46 16.5l1.73 1M21.81 21.5l1 .57M15.46 21.5l1.73-1M21.81 16.5l1-.57"/></svg>',
    'transfer' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/><rect x="8" y="8" width="8" height="8" rx="1"/></svg>',
];
?>
<aside class="db-sidebar dashboard-sidebar" id="dashboard-sidebar" aria-label="Primary navigation">
  <div class="db-brand">
    <span class="db-brand-mark" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="var(--db-sidebar-bg)" stroke="none"><path d="M13 2 3 14h7l-1 8 10-12h-7l1-8z"/></svg>
    </span>
    <span class="db-brand-text">
      <b>YouthNexus</b>
      <span><?= htmlspecialchars($sidebarBrandLabel, ENT_QUOTES, 'UTF-8') ?></span>
    </span>
  </div>

  <div class="dashboard-sidebar__top">
    <div class="dashboard-sidebar__role">
      <span class="dashboard-sidebar__role-dot"></span>
      <span><?= htmlspecialchars(ucwords(str_replace(['_', '-'], ' ', (string) $userRole)), ENT_QUOTES, 'UTF-8') ?> workspace</span>
    </div>
    <button class="dashboard-icon-button dashboard-sidebar__close" type="button" data-sidebar-close aria-label="Close navigation menu">
      <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke-linecap="round"/></svg>
    </button>
  </div>

  <nav class="db-nav dashboard-nav" aria-label="Workspace navigation">
    <p class="dashboard-nav__label">Main menu</p>
    <ul>
      <?php foreach ($roleItems as $item):
        $itemRoute = trim((string) $item['route'], '/');
        $isActive = $activePath === $itemRoute
          || ($itemRoute === 'audit' && in_array($activePath, ['audit', 'annualaudit']))
          || ($itemRoute !== 'home' && str_starts_with($activePath, $itemRoute . '/'));
      ?>
        <li>
          <a class="db-nav-link dashboard-nav__link<?= $isActive ? ' active is-active' : '' ?>" href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
            <span class="db-nav-icon dashboard-nav__icon" aria-hidden="true"><?= $icons[$item['icon']] ?? $icons['grid'] ?></span>
            <span><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php if (!empty($item['badge'])): ?>
              <span class="dashboard-nav__badge" aria-label="<?= htmlspecialchars((string) $item['badge'], ENT_QUOTES, 'UTF-8') ?> unread"><?= htmlspecialchars((string) $item['badge'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </nav>

  <div class="db-sidebar-footer dashboard-sidebar__support">
    <span class="db-avatar" aria-hidden="true"><?= htmlspecialchars($sidebarInitials, ENT_QUOTES, 'UTF-8') ?></span>
    <span class="db-who">
      <b><?= htmlspecialchars($sidebarDisplayName, ENT_QUOTES, 'UTF-8') ?></b>
      <span><?= htmlspecialchars($sidebarRoleLabel, ENT_QUOTES, 'UTF-8') ?></span>
    </span>
  </div>

  <?php if ($normalisedRoleKey === 'member'): ?>
    <a class="dashboard-sidebar__logout" href="<?= $dashboardRoot ?>/auth/logout">
      <span class="db-nav-icon dashboard-nav__icon" aria-hidden="true"><?= $icons['logout'] ?></span>
      <span>Logout</span>
    </a>
  <?php endif; ?>
</aside>
