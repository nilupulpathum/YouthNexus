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
$pageStyles = isset($pageStyles) && is_array($pageStyles) ? $pageStyles : [];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#1e40af">
  <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="<?= ROOT ?>/assets/css/tokens.css?v=<?= rawurlencode(ASSET_VERSION) ?>">
  <link rel="stylesheet" href="<?= ROOT ?>/assets/css/common.css?v=<?= rawurlencode(ASSET_VERSION) ?>">
  <link rel="stylesheet" href="<?= ROOT ?>/assets/css/dashboard.css?v=<?= rawurlencode(ASSET_VERSION) ?>">
  <link rel="stylesheet" href="<?= ROOT ?>/assets/css/components.css?v=<?= rawurlencode(ASSET_VERSION) ?>">
  <?php foreach ($pageStyles as $pageStyle): ?>
    <?php $styleUrl = (string) $pageStyle; ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($styleUrl . (str_contains($styleUrl, '?') ? '&' : '?') . 'v=' . rawurlencode(ASSET_VERSION), ENT_QUOTES, 'UTF-8') ?>">
  <?php endforeach; ?>
  <?php if (in_array($userRole, ['DivisionalCoordinator', 'DivisionalSecretary', 'DivisionalTreasurer'], true)): ?>
    <link rel="stylesheet" href="<?= ROOT ?>/assets/css/divisional-consistency.css?v=<?= rawurlencode(ASSET_VERSION) ?>">
  <?php endif; ?>
</head>
<body class="dashboard dashboard-page" data-user-role="<?= htmlspecialchars($userRole, ENT_QUOTES, 'UTF-8') ?>">
  <a class="dashboard-skip-link" href="#main-content">Skip to main content</a>

  <div class="db-app dashboard-app">
    <?php require __DIR__ . '/../partials/dashboard-sidebar.view.php'; ?>
    <div class="dashboard-sidebar-scrim" data-sidebar-scrim hidden></div>

    <div class="db-main dashboard-main">
      <?php require __DIR__ . '/../partials/dashboard-header.view.php'; ?>

      <main class="db-content dashboard-content" id="main-content" tabindex="-1">
        <div class="dashboard-main__inner">
