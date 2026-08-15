<?php
session_start();
require '../app/core/init.php';
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM User WHERE username = 'coord_colombo'");
$stmt->execute();
$user = $stmt->fetch();

$_SESSION['user_id']    = $user->user_id;
$_SESSION['user_role']  = $user->role;
$_SESSION['division_id'] = $user->division_id;
$_SESSION['user_name']  = $user->first_name . ' ' . $user->last_name;
$_SESSION['user_initials'] = strtoupper(substr($user->first_name,0,1) . substr($user->last_name,0,1));

header('Location: ' . ROOT . '/clubregistration/index');