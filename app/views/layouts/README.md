# Shared dashboard layout

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

The shell reads the signed-in user’s `user_role`, `user_name`, and `user_email` values from the session by default. Controllers may pass explicit values through the view data array when needed. The sidebar uses a role-specific navigation map for President, Secretary, Treasurer, Member, Zonal Coordinator, Divisional Coordinator, Divisional Treasurer, and NYSC Admin. Unknown or unassigned roles receive only Overview and Help.

The shared dashboard stylesheet and script are loaded by the layout entry/exit partials. They are written with plain CSS and vanilla JavaScript and do not require a frontend framework.
