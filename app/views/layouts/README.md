# Shared YouthNexus dashboard layout

T0.05 provides a reusable shell for authenticated YouthNexus dashboards. A dashboard view should set its page context, include the start partial, render its page-specific content, and include the end partial:

```php
<?php
$title = 'Club dashboard — YouthNexus';
$pageTitle = 'Club overview';
$pageDescription = 'A quick view of your club activity and recent updates.';
$currentRoute = 'home';
$unreadNotificationCount = 3;

require __DIR__ . '/../layouts/dashboard-start.view.php';
?>

<section class="dashboard-card">
  <!-- Page-specific dashboard content goes here. -->
</section>

<?php require __DIR__ . '/../layouts/dashboard-end.view.php'; ?>
```

The shell reads the signed-in user’s `user_role`, `user_name`, and `user_email` values from the session by default. Controllers may pass explicit values through the view data array when needed. The sidebar is driven by the workflow actor model and supports Club President, Club Secretary, Club Treasurer, Club Member, Zonal Coordinator, Zonal Secretary, Zonal Treasurer, Divisional Coordinator, Divisional Secretary, Divisional Treasurer, NYSC Admin, and generic Coordinator/Admin aliases. Unknown or unassigned roles receive only Overview and Help.

The visual system follows the approved dashboard references: a royal-blue navigation rail with a white active item, compact YouthNexus identity, a white topbar with page title/description, global search, notification badge, and avatar menu, plus a light content canvas, bordered feature cards, and NYSC footer attribution. Feature pages should add their own page-specific CSS after the shared stylesheet rather than changing the shell tokens.

The shared dashboard stylesheet and script are loaded by the layout entry/exit partials. They are written with plain CSS and vanilla JavaScript and do not require a frontend framework. The shared script preserves profile-menu toggling, outside-click and Escape handling, and responsive sidebar state changes.
