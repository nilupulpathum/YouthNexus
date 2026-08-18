<?php
session_start();
require '../app/core/init.php';

$roleParam = $_GET['role'] ?? 'secretary';

try {
    $db = Database::getInstance()->getConnection();
    if ($roleParam === 'coordinator') {
        $stmt = $db->prepare("SELECT * FROM User WHERE role = 'DivisionalCoordinator' LIMIT 1");
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
} else {
    $_SESSION['user_id']       = $user ? $user->user_id : 14;
    $_SESSION['user_role']     = 'DivisionalSecretary';
    $_SESSION['division_id']   = $user ? $user->division_id : 1;
    $_SESSION['user_name']     = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : 'N. Fernando';
    $_SESSION['user_initials'] = $user ? strtoupper(substr($user->first_name ?? 'N', 0, 1) . substr($user->last_name ?? 'F', 0, 1)) : 'NF';
    header('Location: ' . ROOT . '/manageevents');
}
exit;
