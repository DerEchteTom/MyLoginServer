<?php
// dashboard.php – Anzeigen von Inhalten mit Bildern aus der cms.db

session_start();
date_default_timezone_set('Europe/Berlin');
require_once 'config.php';  // Deine Konfigurationsdatei
require_once 'auth.php';     // Authentifizierung und Rollenzuweisung

// Admin-Check
$isAdmin = isAuthenticated() && hasRole("admin");
$username = $_SESSION['username'] ?? null;
$role = $_SESSION['role'] ?? 'user';

// Redirect-Ziel
$target = ($role === 'admin') ? 'admin.php' : 'links.php';

// Prüfen, ob die Datenbank existiert, und falls nicht, initialisieren
if (!file_exists('cms.db')) {
    include('init_cms_db.php');  // Initialisiert die Datenbank, wenn sie nicht existiert
}

// Wir laden Inhalte aus der Tabelle `page_content`
$pdo = new PDO('sqlite:cms.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Abrufen des Timer-Werts aus der settings-Tabelle
$stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_name = 'redirect_timer'");
$timerSetting = $stmt->fetchColumn();
if (!$timerSetting) {
    // Falls der Timer-Wert nicht gesetzt ist, Standardwert (5 Sekunden)
    $timerSetting = 5;
}

$redirectSeconds = $timerSetting;  // Timer aus der Datenbank

// Abrufen von Daten aus der Tabelle page_content
$stmt = $pdo->query("SELECT section_name, text_content, image_path FROM page_content");
$contents = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $contents[$row['section_name']] = [
        'text' => $row['text_content'],
        'image' => $row['image_path']
    ];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .timer { font-size: 1.5rem; font-weight: bold; margin-top: 10px; }
        .btn-container { margin-top: 20px; }
        .status { font-size: 1rem; color: red; margin-top: 10px; }
        .image-preview { max-width: 100%; height: auto; }
        .content-container { margin-top: 30px; }
        .functional-container { border-bottom: 2px solid #ccc; padding-bottom: 20px; margin-bottom: 20px; }
    </style>
</head>
<body class="bg-light">
<div class="container mt-4" style="max-width: 90%;">

    <!-- Funktions-Container -->
    <div class="functional-container">
        <h4>Welcome, <?= htmlspecialchars($username) ?> <?= $isAdmin ? '(Admin)' : '' ?></h4>

        <!-- Anzeige des Timers -->
        <p class="text-muted">You will be redirected in <span id="timer"><?= $redirectSeconds ?></span> seconds.</p>

        <!-- Buttons für Pause und Weiter -->
        <div class="btn-container d-flex">
            <button onclick="pauseRedirect()" class="btn btn-sm btn-outline-warning me-2">Pause Redirect</button>
            <a href="<?= htmlspecialchars($target) ?>" class="btn btn-sm btn-outline-primary me-2">Go Now</a>
            <?php if ($isAdmin): ?>
                <a href="cms_edit.php" class="btn btn-sm btn-outline-success">Edit CMS Content</a>
            <?php endif; ?>
        </div>

        <!-- Statusanzeige für die Weiterleitung -->
        <div id="status" class="status"></div>
    </div>

    <!-- CMS-Inhalt-Container -->
    <div class="content-container">
        <?php if (!empty($contents)): ?>
            <h5>CMS Content:</h5>
            <?php foreach ($contents as $section => $content): ?>
                <div class="cms-section">
                    <h6><?= ucfirst($section) ?>:</h6>
                    <p><?= nl2br(htmlspecialchars($content['text'])) ?></p>
                    <?php if ($content['image']): ?>
                        <div class="mb-3">
                            <img src="<?= htmlspecialchars($content['image']) ?>" class="image-preview" alt="Image for <?= htmlspecialchars($section) ?>">
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No CMS content available.</p>
        <?php endif; ?>
    </div>

</div>

<script>
// JavaScript for the Timer and Redirection
let timerElement = document.getElementById("timer");
let redirectTimeout;
let remainingTime = <?= $redirectSeconds ?>;

function startRedirect() {
    // Update the timer display every second
    setInterval(function() {
        if (remainingTime > 0) {
            remainingTime--;
            timerElement.textContent = remainingTime;
        }
    }, 1000);

    // Start redirect after the specified time
    redirectTimeout = setTimeout(function() {
        // Redirect to admin.php or links.php based on the role
        <?php if ($_SESSION['role'] == 'admin') { ?>
            window.location.href = 'admin.php'; // Redirect to the admin page
        <?php } else { ?>
            window.location.href = 'links.php'; // Redirect to the user page
        <?php } ?>
    }, <?= $redirectSeconds ?> * 1000); // Redirect after the set time
}

function pauseRedirect() {
    clearTimeout(redirectTimeout);
    document.getElementById("status").textContent = "Redirect paused.";
}

window.onload = startRedirect;
</script>
</body>
</html>
