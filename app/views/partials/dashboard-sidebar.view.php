<?php
$dashboardRoot = defined('ROOT') ? ROOT : '';
$activePath = trim((string) ($currentRoute ?? $_GET['url'] ?? 'home'), '/');
$roleKey = strtolower(trim((string) $userRole));

$navigation = [
    'president' => [
        ['label' => 'Overview', 'route' => 'home', 'href' => $dashboardRoot . '/home', 'icon' => 'grid'],
        ['label' => 'Members', 'route' => 'club/members', 'href' => $dashboardRoot . '/club/members', 'icon' => 'users'],
        ['label' => 'Events', 'route' => 'club/events', 'href' => $dashboardRoot . '/club/events', 'icon' => 'calendar'],
        ['label' => 'Assets', 'route' => 'club/assets', 'href' => $dashboardRoot . '/club/assets', 'icon' => 'briefcase'],
        ['label' => 'Ledger', 'route' => 'club/ledger', 'href' => $dashboardRoot . '/club/ledger', 'icon' => 'wallet'],
        ['label' => 'Announcements', 'route' => 'announcements', 'href' => $dashboardRoot . '/announcements', 'icon' => 'megaphone'],
        ['label' => 'Settings', 'route' => 'settings', 'href' => $dashboardRoot . '/settings', 'icon' => 'settings'],
        ['label' => 'Help', 'route' => 'help', 'href' => $dashboardRoot . '/help', 'icon' => 'help'],
    ],
    'secretary' => [
        ['label' => 'Overview', 'route' => 'home', 'href' => $dashboardRoot . '/home', 'icon' => 'grid'],
        ['label' => 'Members', 'route' => 'club/members', 'href' => $dashboardRoot . '/club/members', 'icon' => 'users'],
        ['label' => 'Events', 'route' => 'club/events', 'href' => $dashboardRoot . '/club/events', 'icon' => 'calendar'],
        ['label' => 'Announcements', 'route' => 'announcements', 'href' => $dashboardRoot . '/announcements', 'icon' => 'megaphone'],
        ['label' => 'Settings', 'route' => 'settings', 'href' => $dashboardRoot . '/settings', 'icon' => 'settings'],
        ['label' => 'Help', 'route' => 'help', 'href' => $dashboardRoot . '/help', 'icon' => 'help'],
    ],
    'treasurer' => [
        ['label' => 'Overview', 'route' => 'home', 'href' => $dashboardRoot . '/home', 'icon' => 'grid'],
        ['label' => 'Assets', 'route' => 'club/assets', 'href' => $dashboardRoot . '/club/assets', 'icon' => 'briefcase'],
        ['label' => 'Ledger', 'route' => 'club/ledger', 'href' => $dashboardRoot . '/club/ledger', 'icon' => 'wallet'],
        ['label' => 'Announcements', 'route' => 'announcements', 'href' => $dashboardRoot . '/announcements', 'icon' => 'megaphone'],
        ['label' => 'Settings', 'route' => 'settings', 'href' => $dashboardRoot . '/settings', 'icon' => 'settings'],
        ['label' => 'Help', 'route' => 'help', 'href' => $dashboardRoot . '/help', 'icon' => 'help'],
    ],
    'member' => [
        ['label' => 'Overview', 'route' => 'home', 'href' => $dashboardRoot . '/home', 'icon' => 'grid'],
        ['label' => 'My Profile', 'route' => 'profile', 'href' => $dashboardRoot . '/profile', 'icon' => 'user'],
        ['label' => 'My Attendance', 'route' => 'attendance', 'href' => $dashboardRoot . '/attendance', 'icon' => 'check'],
        ['label' => 'Events', 'route' => 'events', 'href' => $dashboardRoot . '/events', 'icon' => 'calendar'],
        ['label' => 'Announcements', 'route' => 'announcements', 'href' => $dashboardRoot . '/announcements', 'icon' => 'megaphone'],
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
        ['label' => 'Overview', 'route' => 'home', 'href' => $dashboardRoot . '/home', 'icon' => 'grid'],
        ['label' => 'Pending Approvals', 'route' => 'approvals', 'href' => $dashboardRoot . '/approvals', 'icon' => 'clock'],
        ['label' => 'Clubs in Division', 'route' => 'clubs', 'href' => $dashboardRoot . '/clubs', 'icon' => 'users'],
        ['label' => 'Fund Ledger', 'route' => 'ledger', 'href' => $dashboardRoot . '/ledger', 'icon' => 'wallet'],
        ['label' => 'Reports', 'route' => 'reports', 'href' => $dashboardRoot . '/reports', 'icon' => 'chart'],
        ['label' => 'Announcements', 'route' => 'announcements', 'href' => $dashboardRoot . '/announcements', 'icon' => 'megaphone'],
        ['label' => 'Help', 'route' => 'help', 'href' => $dashboardRoot . '/help', 'icon' => 'help'],
    ],
    'divisionaltreasurer' => [
        ['label' => 'Overview', 'route' => 'home', 'href' => $dashboardRoot . '/home', 'icon' => 'grid'],
        ['label' => 'Pending Approvals', 'route' => 'approvals', 'href' => $dashboardRoot . '/approvals', 'icon' => 'clock'],
        ['label' => 'Clubs in Division', 'route' => 'clubs', 'href' => $dashboardRoot . '/clubs', 'icon' => 'users'],
        ['label' => 'Fund Ledger', 'route' => 'ledger', 'href' => $dashboardRoot . '/ledger', 'icon' => 'wallet'],
        ['label' => 'Reports', 'route' => 'reports', 'href' => $dashboardRoot . '/reports', 'icon' => 'chart'],
        ['label' => 'Help', 'route' => 'help', 'href' => $dashboardRoot . '/help', 'icon' => 'help'],
    ],
    'coordinator' => [
        ['label' => 'Overview', 'route' => 'home', 'href' => $dashboardRoot . '/home', 'icon' => 'grid'],
        ['label' => 'Pending Approvals', 'route' => 'approvals', 'href' => $dashboardRoot . '/approvals', 'icon' => 'clock'],
        ['label' => 'Clubs', 'route' => 'clubs', 'href' => $dashboardRoot . '/clubs', 'icon' => 'users'],
        ['label' => 'Reports', 'route' => 'reports', 'href' => $dashboardRoot . '/reports', 'icon' => 'chart'],
        ['label' => 'Announcements', 'route' => 'announcements', 'href' => $dashboardRoot . '/announcements', 'icon' => 'megaphone'],
        ['label' => 'Help', 'route' => 'help', 'href' => $dashboardRoot . '/help', 'icon' => 'help'],
    ],
    'admin' => [
        ['label' => 'Overview', 'route' => 'home', 'href' => $dashboardRoot . '/home', 'icon' => 'grid'],
        ['label' => 'Club Applications', 'route' => 'applications', 'href' => $dashboardRoot . '/applications', 'icon' => 'clipboard'],
        ['label' => 'All Clubs', 'route' => 'clubs', 'href' => $dashboardRoot . '/clubs', 'icon' => 'users'],
        ['label' => 'All Members', 'route' => 'members', 'href' => $dashboardRoot . '/members', 'icon' => 'user'],
        ['label' => 'Analytics', 'route' => 'analytics', 'href' => $dashboardRoot . '/analytics', 'icon' => 'chart'],
        ['label' => 'Announcements', 'route' => 'announcements', 'href' => $dashboardRoot . '/announcements', 'icon' => 'megaphone'],
        ['label' => 'Audit Log', 'route' => 'audit-log', 'href' => $dashboardRoot . '/audit-log', 'icon' => 'shield'],
        ['label' => 'Help', 'route' => 'help', 'href' => $dashboardRoot . '/help', 'icon' => 'help'],
    ],
    'nyscadmin' => [
        ['label' => 'Overview', 'route' => 'home', 'href' => $dashboardRoot . '/home', 'icon' => 'grid'],
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
    'divisional' => 'divisionalcoordinator',
    'nysc' => 'nyscadmin',
];
$normalisedRoleKey = $roleAliases[$normalisedRoleKey] ?? $normalisedRoleKey;
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
    'settings' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3.5"/><path d="m19.4 15 .1.1a2 2 0 0 1-2.8 2.8l-.1-.1a2 2 0 0 0-3.4 1.4v.2a2 2 0 0 1-4 0v-.2a2 2 0 0 0-3.4-1.4l-.1.1a2 2 0 0 1-2.8-2.8l.1-.1A2 2 0 0 0 1.7 12a2 2 0 0 0 1.4-3.4L3 8.5A2 2 0 0 1 5.8 5.7l.1.1A2 2 0 0 0 9.3 4.4v-.2a2 2 0 0 1 4 0v.2a2 2 0 0 0 3.4 1.4l.1-.1a2 2 0 0 1 2.8 2.8l-.1.1A2 2 0 0 0 20.9 12a2 2 0 0 0-1.5 3Z"/></svg>',
    'help' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="8.5"/><path d="M9.6 9a2.6 2.6 0 1 1 4.5 1.8c-1.2 1.1-2.1 1.3-2.1 3M12 17h.01" stroke-linecap="round"/></svg>',
    'clock' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="8.5"/><path d="M12 7v5l3 2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    'chart' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 19V9M12 19V5M19 19v-7" stroke-linecap="round"/><path d="M3 19h18" stroke-linecap="round"/></svg>',
    'clipboard' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4.5V3h6v1.5M8 10h8M8 14h6" stroke-linecap="round"/></svg>',
    'shield' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m12 3 7 3v5c0 4.5-2.5 7.7-7 10-4.5-2.3-7-5.5-7-10V6l7-3Z" stroke-linejoin="round"/><path d="m9 12 2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    'check' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m5 12 4 4L19 6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
];
?>
<aside class="dashboard-sidebar" id="dashboard-sidebar" aria-label="Primary navigation">
  <div class="dashboard-sidebar__top">
    <div class="dashboard-sidebar__role">
      <span class="dashboard-sidebar__role-dot"></span>
      <span><?= htmlspecialchars(ucwords(str_replace(['_', '-'], ' ', (string) $userRole)), ENT_QUOTES, 'UTF-8') ?> workspace</span>
    </div>
    <button class="dashboard-icon-button dashboard-sidebar__close" type="button" data-sidebar-close aria-label="Close navigation menu">
      <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke-linecap="round"/></svg>
    </button>
  </div>

  <nav class="dashboard-nav">
    <p class="dashboard-nav__label">Main menu</p>
    <ul>
      <?php foreach ($roleItems as $item):
        $itemRoute = trim((string) $item['route'], '/');
        $isActive = $activePath === $itemRoute || ($itemRoute !== 'home' && str_starts_with($activePath, $itemRoute . '/'));
      ?>
        <li>
          <a class="dashboard-nav__link<?= $isActive ? ' is-active' : '' ?>" href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
            <span class="dashboard-nav__icon" aria-hidden="true"><?= $icons[$item['icon']] ?? $icons['grid'] ?></span>
            <span><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </nav>

  <div class="dashboard-sidebar__support">
    <div class="dashboard-sidebar__support-icon" aria-hidden="true">
      <?= $icons['help'] ?>
    </div>
    <strong>Need a hand?</strong>
    <p>Visit Help for quick guidance on your workspace.</p>
    <a href="<?= $dashboardRoot ?>/help">Open Help <span aria-hidden="true">→</span></a>
  </div>
</aside>
