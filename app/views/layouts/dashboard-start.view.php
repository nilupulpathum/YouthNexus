<?php
/**
 * Shared dashboard layout entry point.
 *
 * A dashboard view should set optional variables before including this file:
 * $title, $pageTitle, $pageDescription, $currentRoute, $userRole,
 * $userName, $userEmail, and $unreadNotificationCount.
 */
$title = $title ?? 'Dashboard — YouthNexus';
$pageTitle = $pageTitle ?? 'Dashboard';
$pageDescription = $pageDescription ?? '';
$currentRoute = $currentRoute ?? trim((string) ($_GET['url'] ?? 'home'), '/');
$userRole = $userRole ?? ($_SESSION['user_role'] ?? 'UnassignedUser');
$userName = $userName ?? ($_SESSION['user_name'] ?? 'YouthNexus User');
$userEmail = $userEmail ?? ($_SESSION['user_email'] ?? '');
$unreadNotificationCount = (int) ($unreadNotificationCount ?? 0);
// Zonal, divisional and NYSC actors have no Social CV: their avatar
// "My profile" link goes to Settings, which opens on the Edit Profile pane
// (the CV page still serves a hardcoded mock person, including a NIC, to
// every viewer). Club roles keep /profile. A page may still pass its own
// $profileUrl to override this default.
$roleKey = is_string($userRole) ? strtolower($userRole) : '';
$roleHasNoCv = (is_string($userRole)
    && (str_starts_with($userRole, 'Zonal') || str_starts_with($userRole, 'Divisional')))
    || in_array($roleKey, ['nyscadministrator', 'nyscadmin', 'nysc', 'admin'], true);
$profileDefault = (defined('ROOT') ? ROOT : '')
    . ($roleHasNoCv ? '/settings' : '/profile');
$profileUrl = $profileUrl ?? $profileDefault;
$notificationUrl = $notificationUrl ?? (defined('ROOT') ? ROOT . '/notifications' : '/notifications');
$pageStyles = isset($pageStyles) && is_array($pageStyles) ? $pageStyles : [];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#1e40af">
  <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
  <style>
    /* Keep the navigation cover usable while the page's larger stylesheets load. */
    .yn-route-overlay { display: none; position: fixed; inset: 0; z-index: 2147483647; align-items: center; justify-content: center; background: #f4f7fb; color: #1e3a8a; font: 600 15px system-ui, sans-serif; }
    .yn-nav-loading .yn-route-overlay { display: flex; }
    .yn-route-overlay__content { display: flex; flex-direction: column; align-items: center; gap: 16px; padding: 24px; text-align: center; }
    .yn-route-overlay__spinner { width: 36px; height: 36px; border: 3px solid #cbd5e1; border-top-color: #1e40af; border-radius: 50%; animation: yn-route-spin .7s linear infinite; }
    @keyframes yn-route-spin { to { transform: rotate(360deg); } }
    @media (prefers-reduced-motion: reduce) { .yn-route-overlay__spinner { animation-duration: 1.5s; } }
  </style>
  <script>
    // Carry the indicator across full-page sidebar navigations when possible.
    try {
      var started = Number(sessionStorage.getItem('yn-sidebar-navigation'));
      if (started && Date.now() - started < 60000) {
        document.documentElement.classList.add('yn-nav-loading');
      } else {
        sessionStorage.removeItem('yn-sidebar-navigation');
      }
    } catch (error) { /* Navigation still works when storage is unavailable. */ }
  </script>
  <link rel="stylesheet" href="<?= ROOT ?>/assets/css/common.css">
  <link rel="stylesheet" href="<?= ROOT ?>/assets/css/dashboard.css">
  <?php foreach ($pageStyles as $pageStyle): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars((string) $pageStyle, ENT_QUOTES, 'UTF-8') ?>">
  <?php endforeach; ?>
</head>
<body class="dashboard dashboard-page" data-user-role="<?= htmlspecialchars($userRole, ENT_QUOTES, 'UTF-8') ?>">
  <a class="dashboard-skip-link" href="#main-content">Skip to main content</a>
  <div class="yn-route-overlay" role="status" aria-live="polite" aria-label="Loading page">
    <div class="yn-route-overlay__content">
      <span class="yn-route-overlay__spinner" aria-hidden="true"></span>
      <span>Loading your workspace…</span>
    </div>
  </div>

  <div class="db-app dashboard-app">
    <?php require __DIR__ . '/../partials/dashboard-sidebar.view.php'; ?>
    <div class="dashboard-sidebar-scrim" data-sidebar-scrim hidden></div>

    <div class="db-main dashboard-main">
      <?php require __DIR__ . '/../partials/dashboard-header.view.php'; ?>

      <main class="db-content dashboard-content" id="main-content" tabindex="-1">
        <div class="dashboard-main__inner">
