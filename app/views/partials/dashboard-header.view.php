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
      <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <circle cx="11" cy="11" r="7.5"/><path d="m20 20-3.7-3.7" stroke-linecap="round"/>
      </svg>
      <span class="visually-hidden">Search this workspace</span>
      <input id="dashboard-global-search" type="search" placeholder="Search..." autocomplete="off">
    </label>

    <a class="db-icon-btn dashboard-notification" href="<?= htmlspecialchars($notificationUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="Notifications<?= $unreadNotificationCount > 0 ? ', ' . $unreadNotificationCount . ' unread' : '' ?>">
      <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        <path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <?php if ($unreadNotificationCount > 0): ?>
        <span class="db-badge-dot"><?= $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount ?></span>
      <?php endif; ?>
    </a>

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
          <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="3.5"/><path d="m19.4 15 .1.1a2 2 0 0 1-2.8 2.8l-.1-.1a2 2 0 0 0-3.4 1.4v.2a2 2 0 0 1-4 0v-.2a2 2 0 0 0-3.4-1.4l-.1.1a2 2 0 0 1-2.8-2.8l.1-.1A2 2 0 0 0 1.7 12a2 2 0 0 0 1.4-3.4L3 8.5A2 2 0 0 1 5.8 5.7l.1.1A2 2 0 0 0 9.3 4.4v-.2a2 2 0 0 1 4 0v.2a2 2 0 0 0 3.4 1.4l.1-.1a2 2 0 0 1 2.8 2.8l-.1.1A2 2 0 0 0 20.9 12a2 2 0 0 0-1.5 3Z"/></svg>
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
