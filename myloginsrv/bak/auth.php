<?php
// Datei: auth.php – Stand: 2025-04-22 09:01 Europe/Berlin

date_default_timezone_set('Europe/Berlin');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireRole($role = 'admin') {
    if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== $role) {
        @file_put_contents("error.log", date('c') . " Zugriffsverweigerung – Session: " . print_r($_SESSION, true), FILE_APPEND);
        header("Location: login.php");
        exit;
    }
}
