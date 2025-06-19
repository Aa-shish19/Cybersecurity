<?php 
session_start();
require_once 'config.php';
require_once 'Website_Project.php'; // Ensure logEvent() is included

$email = $_SESSION['email'] ?? 'Unknown';
$ip = ($_SERVER['REMOTE_ADDR'] === '::1') ? '127.0.0.1' : $_SERVER['REMOTE_ADDR'];
$details = "User logged out from IP: $ip";

// ✅ Centralized logging
logEvent($conn, $email, 'logout', $details);

session_unset();
session_destroy();

header("Location: index.php");
exit();
?>