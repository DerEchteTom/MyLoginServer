<?php
session_start();
$username = $_SESSION['user'] ?? 'UNKNOWN';
$ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
file_put_contents("audit.log", date('c') . " LOGOUT $username FROM $ip\n", FILE_APPEND);
session_unset();
session_destroy();
header("Location: login.php");
exit;