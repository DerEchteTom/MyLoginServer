<?php
// Datei: login.php – Stand: 2025-04-24 11:55:22 Europe/Berlin
session_start();
date_default_timezone_set('Europe/Berlin');

require_once "mailer_config.php";
require_once "vendor/autoload.php";

function log_audit($msg) {
    file_put_contents("audit.log", date("Y-m-d H:i:s") . " LOGIN: " . $msg . "\n", FILE_APPEND);
}

function log_error($msg) {
    file_put_contents("error.log", date("Y-m-d H:i:s") . " LOGIN ERROR: " . $msg . "\n", FILE_APPEND);
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!$username || !$password) {
        echo "Fehlender Benutzername oder Passwort.";
        exit;
    }

    $db = new SQLite3("users.db");
    $stmt = $db->prepare("SELECT * FROM users WHERE username = :u AND active = 1");
    $stmt->bindValue(":u", $username, SQLITE3_TEXT);
    $res = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    if ($res && password_verify($password, $res["password"])) {
        log_audit("Lokale Anmeldung erfolgreich: { $username }");
        $_SESSION["username"] = $username;
        $_SESSION["is_admin"] = ($res["role"] ?? '') === "admin";
        header("Location: " . ($_SESSION["is_admin"] ? "admin.php" : "dashboard.php"));
        exit;
    } else {
        log_error("Lokale Anmeldung fehlgeschlagen für Benutzer: { $username }");
        echo "Anmeldung fehlgeschlagen.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="./assets/css/bootstrap.min.css">
</head>
<body class="p-4" style="font-family: sans-serif; font-size: 14px;">
<div class="container" style="max-width: 400px;">
    <h2 class="mb-4">Login</h2>
    <form method="post">
        <div class="mb-3">
            <label for="username" class="form-label">Benutzername</label>
            <input type="text" class="form-control" name="username" id="username" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Passwort</label>
            <input type="password" class="form-control" name="password" id="password" required>
        </div>
        <button type="submit" class="btn btn-primary">Anmelden</button>
    </form>
    <div class="mt-3">
        <a href="register.php">Neu registrieren</a><br>
        <a href="forgot.php">Passwort vergessen?</a><br>
        <a href="ad_login.php">Anmeldung mit Active Directory</a>
    </div>
</div>
</body>
</html>
