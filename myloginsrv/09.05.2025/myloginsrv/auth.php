<?php
// Datei: auth.php – Zugriffsschutz für Admin-Tabs
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
date_default_timezone_set('Europe/Berlin');

// Prüfung auf Login
if (!isset($_SESSION['username']) || empty($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

// Optional: Rollenprüfung
function requireRole($role) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        header("Location: login.php");
        exit;
    }
}
?>
