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

// Prüft, ob ein Benutzer eingeloggt ist
function isAuthenticated() {
    return isset($_SESSION['username']);
}

// Prüft, ob Benutzer eine bestimmte Rolle hat (z. B. "admin")
function hasRole($requiredRole) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $requiredRole;
}
?>
