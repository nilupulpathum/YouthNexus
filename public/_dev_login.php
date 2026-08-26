<?php
session_start();
require '../app/core/init.php';

$roleParam = $_GET['role'] ?? null;

if (!$roleParam) {
    // Show a selection menu
    echo "<h1>Developer Login</h1>";
    echo "<ul>";
    echo "<li><a href='?role=secretary'>Log in as Divisional Secretary (Manage Events)</a></li>";
    echo "<li><a href='?role=coordinator'>Log in as Divisional Coordinator (Club Registration)</a></li>";
    echo "<li><a href='?role=monitor'>Log in as Divisional Coordinator (Monitor Club Health)</a></li>";
    echo "<li><a href='?role=eventapproval'>Log in as Divisional Coordinator (Approve Events)</a></li>";
    echo "<li><a href='?role=treasurer'>Log in as Divisional Treasurer (Monitor Club Health)</a></li>";
    echo "</ul>";
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    if ($roleParam === 'coordinator' || $roleParam === 'monitor' || $roleParam === 'eventapproval') {
        $stmt = $db->prepare("SELECT * FROM User WHERE role = 'DivisionalCoordinator' LIMIT 1");
    } elseif ($roleParam === 'treasurer') {
        $stmt = $db->prepare("SELECT * FROM User WHERE role = 'DivisionalTreasurer' LIMIT 1");
    } else {
        $stmt = $db->prepare("SELECT * FROM User WHERE role = 'DivisionalSecretary' LIMIT 1");
    }
    $stmt->execute();
    $user = $stmt->fetch();
} catch (Exception $e) {
    $user = null;
}

if ($roleParam === 'coordinator') {
    $_SESSION['user_id']       = $user ? $user->user_id : 1;
    $_SESSION['user_role']     = 'DivisionalCoordinator';
    $_SESSION['division_id']   = $user ? $user->division_id : 1;
    $_SESSION['user_name']     = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : 'Sarah Perera';
    $_SESSION['user_initials'] = $user ? strtoupper(substr($user->first_name ?? 'S', 0, 1) . substr($user->last_name ?? 'P', 0, 1)) : 'SP';
    header('Location: ' . ROOT . '/clubregistration/index');
} elseif ($roleParam === 'monitor') {
    $_SESSION['user_id']       = $user ? $user->user_id : 1;
    $_SESSION['user_role']     = 'DivisionalCoordinator';
    $_SESSION['division_id']   = $user ? $user->division_id : 1;
    $_SESSION['user_name']     = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : 'Sarah Perera';
    $_SESSION['user_initials'] = $user ? strtoupper(substr($user->first_name ?? 'S', 0, 1) . substr($user->last_name ?? 'P', 0, 1)) : 'SP';
    header('Location: ' . ROOT . '/monitorclubhealth');
} elseif ($roleParam === 'eventapproval') {
    $_SESSION['user_id']       = $user ? $user->user_id : 1;
    $_SESSION['user_role']     = 'DivisionalCoordinator';
    $_SESSION['division_id']   = $user ? $user->division_id : 1;
    $_SESSION['user_name']     = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : 'Sarah Perera';
    $_SESSION['user_initials'] = $user ? strtoupper(substr($user->first_name ?? 'S', 0, 1) . substr($user->last_name ?? 'P', 0, 1)) : 'SP';
    header('Location: ' . ROOT . '/eventapproval');
} elseif ($roleParam === 'treasurer') {
    $_SESSION['user_id']       = $user ? $user->user_id : 20; // fallback if no treasurer exists in DB seed yet
    $_SESSION['user_role']     = 'DivisionalTreasurer';
    $_SESSION['division_id']   = $user ? $user->division_id : 1;
    $_SESSION['user_name']     = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : 'T. Perera';
    $_SESSION['user_initials'] = $user ? strtoupper(substr($user->first_name ?? 'T', 0, 1) . substr($user->last_name ?? 'P', 0, 1)) : 'TP';
    header('Location: ' . ROOT . '/monitorclubhealth');
} else {
    $_SESSION['user_id']       = $user ? $user->user_id : 19;
    $_SESSION['user_role']     = 'DivisionalSecretary';
    $_SESSION['division_id']   = $user ? $user->division_id : 1;
    $_SESSION['user_name']     = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : 'N. Fernando';
    $_SESSION['user_initials'] = $user ? strtoupper(substr($user->first_name ?? 'N', 0, 1) . substr($user->last_name ?? 'F', 0, 1)) : 'NF';
    header('Location: ' . ROOT . '/manageevents');
}
exit;

