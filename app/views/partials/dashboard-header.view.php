<?php
$dashboardRoot = defined('ROOT') ? ROOT : '';
$displayName = trim((string) $userName) !== '' ? trim((string) $userName) : 'YouthNexus User';
$initials = function_exists('mb_substr')
    ? strtoupper(mb_substr($displayName, 0, 1))
    : strtoupper(substr($displayName, 0, 1));
$roleLabel = strtolower((string) $userRole) === 'clubmember' ? 'Member' : ucwords(str_replace(['_', '-'], ' ', (string) $userRole));
?>
<header class="db-topbar dashboard-header">
  <div class="db-topbar-title">
    <button class="dashboard-icon-button dashboard-menu-button" type="button" data-sidebar-toggle aria-controls="dashboard-sidebar" aria-expanded="false" aria-label="Open navigation menu">
      <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round"/>
      </svg>
    </button>
    <div class="db-topbar-heading">
      <h1><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>
      <?php if ($pageDescription !== ''): ?>
        <p><?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>
    </div>
  </div>

  <div class="db-topbar-right dashboard-header__right">
    <label class="db-search-top" for="dashboard-global-search">
      <input id="dashboard-global-search" type="search" placeholder="Search..." autocomplete="off">
      <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <circle cx="11" cy="11" r="7.5"/><path d="m20 20-3.7-3.7" stroke-linecap="round"/>
      </svg>
      <span class="visually-hidden">Search this workspace</span>
    </label>

    <div class="dashboard-notifications" data-notif-menu>
      <button class="db-icon-btn dashboard-notification" type="button" data-notif-toggle aria-controls="dashboard-notif-menu" aria-expanded="false" aria-label="Notifications<?= $unreadNotificationCount > 0 ? ', ' . $unreadNotificationCount . ' unread' : '' ?>">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
          <path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <?php if ($unreadNotificationCount > 0): ?>
          <span class="db-badge-dot"><?= $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount ?></span>
        <?php endif; ?>
      </button>

      <div class="dashboard-notif__menu" id="dashboard-notif-menu" data-notif-dropdown hidden>
        <div class="dashboard-notif__menu-heading">
          <strong>Notifications</strong>
          <span><?= $unreadNotificationCount > 0 ? $unreadNotificationCount . ' unread' : 'All caught up' ?></span>
        </div>
        <?php if (isset($headerNotifications) && is_array($headerNotifications)): ?>
        <?php if (!$headerNotifications): ?>
        <span class="dashboard-notif__empty">No new announcements.</span>
        <?php else: ?>
        <?php foreach ($headerNotifications as $headerItem): ?>
        <a href="<?= $dashboardRoot ?>/announcements/view/<?= (int) ($headerItem['id'] ?? 0) ?>">
          <strong><?= htmlspecialchars((string) ($headerItem['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
          <span><?= htmlspecialchars((string) ($headerItem['age'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
        </a>
        <?php endforeach; ?>
        <?php endif; ?>
        <?php else: ?>
        <?php if ($unreadNotificationCount > 0): ?>
        <a href="<?= $dashboardRoot ?>/announcements">
          <strong>Divisional Leadership Summit 2026</strong>
          <span>Confirm your attendance by Friday · 2 days ago</span>
        </a>
        <a href="<?= $dashboardRoot ?>/announcements">
          <strong>Volunteer hour submission guidelines</strong>
          <span>Submit within 7 days of the activity · 4 days ago</span>
        </a>
        <?php else: ?>
        <span class="dashboard-notif__empty">No new announcements.</span>
        <?php endif; ?>
        <?php endif; ?>
        <div class="dashboard-profile__menu-divider"></div>
        <a class="dashboard-notif__view-all" href="<?= $dashboardRoot ?>/announcements">
          View all announcements
        </a>
      </div>
    </div>

    <div class="dashboard-profile" data-profile-menu>
      <button class="dashboard-profile__trigger db-topbar-avatar" type="button" data-profile-toggle aria-controls="dashboard-profile-menu" aria-expanded="false" aria-label="Open profile menu">
        <span class="dashboard-avatar" aria-hidden="true"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
        <span class="visually-hidden"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?></span>
      </button>

      <div class="dashboard-profile__menu" id="dashboard-profile-menu" data-profile-dropdown hidden>
        <div class="dashboard-profile__menu-heading">
          <strong><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></strong>
          <span><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?><?= $userEmail !== '' ? ' · ' . htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8') : '' ?></span>
        </div>
        <a href="<?= htmlspecialchars($profileUrl, ENT_QUOTES, 'UTF-8') ?>">
          <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 20c.7-3.4 3-5 7-5s6.3 1.6 7 5" stroke-linecap="round"/></svg>
          My profile
        </a>
        <a href="<?= $dashboardRoot ?>/settings">
          <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>
          Settings
        </a>
        <div class="dashboard-profile__menu-divider"></div>
        <a class="dashboard-profile__logout" href="<?= $dashboardRoot ?>/auth/logout">
          <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M10 5H6.5A1.5 1.5 0 0 0 5 6.5v11A1.5 1.5 0 0 0 6.5 19H10M14 16l4-4-4-4M18 12H9" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Sign out
        </a>
      </div>
    </div>
  </div>
</header>
