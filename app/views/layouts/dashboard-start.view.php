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
$profileUrl = $profileUrl ?? (defined('ROOT') ? ROOT . '/profile' : '/profile');
$notificationUrl = $notificationUrl ?? (defined('ROOT') ? ROOT . '/notifications' : '/notifications');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#173a8f">
  <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="<?= ROOT ?>/assets/css/common.css">
  <link rel="stylesheet" href="<?= ROOT ?>/assets/css/dashboard.css">
</head>
<body class="dashboard-page" data-user-role="<?= htmlspecialchars($userRole, ENT_QUOTES, 'UTF-8') ?>">
  <a class="dashboard-skip-link" href="#main-content">Skip to main content</a>
  <div class="dashboard-app">
    <?php require __DIR__ . '/../partials/dashboard-header.view.php'; ?>

    <div class="dashboard-workspace">
      <?php require __DIR__ . '/../partials/dashboard-sidebar.view.php'; ?>
      <div class="dashboard-sidebar-scrim" data-sidebar-scrim hidden></div>

      <main class="dashboard-main" id="main-content" tabindex="-1">
        <div class="dashboard-main__inner">
          <div class="dashboard-page-heading">
            <div>
              <p class="dashboard-eyebrow">YouthNexus workspace</p>
              <h1><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>
              <?php if ($pageDescription !== ''): ?>
                <p class="dashboard-page-description"><?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?></p>
              <?php endif; ?>
            </div>
          </div>
