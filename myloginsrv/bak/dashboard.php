<?php
// Datei: dashboard.php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

// Rollenbasiert weiterleiten
$user = $_SESSION['user'] ?? null;
$role = $_SESSION['role'] ?? 'user';

if (!$user) {
    header("Location: login.php");
    exit;
}

// Zielseite je nach Rolle
$target = $role === 'admin' ? 'admin.php' : 'links.php';

// Optionale Wartezeit für Benutzerinfo
sleep(2); // kurze Pause für Debug oder Anzeige

header("Location: $target");
exit;
