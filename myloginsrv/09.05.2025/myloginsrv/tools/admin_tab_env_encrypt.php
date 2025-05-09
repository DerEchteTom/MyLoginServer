<?php
// Datei: admin_tab_env_encrypt.php – Stand: 2025-04-24 11:41:57 Europe/Berlin
session_start();
date_default_timezone_set("Europe/Berlin");

$output = "";
$usedKey = "";
$showOutput = false;
$statusType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $userKey = trim($_POST["key"] ?? "");
    $usedKey = $userKey !== "" ? $userKey : "1qay2wsx3edc";
    putenv("ENCRYPTION_KEY=" . $usedKey);

    // Backup .envad → .envorg (einmalig, nur wenn noch kein Tag existiert)
    $envPath = ".envad";
    $tag = "ENCRYPTION_DONE";
    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $tagExists = false;
        foreach ($lines as $line) {
            if (strpos($line, $tag) !== false) {
                $tagExists = true;
                break;
            }
        }
        if (!$tagExists && !file_exists(".envorg")) {
            copy($envPath, ".envorg");
        }
    }

    ob_start();
    include "encrypt_env_fields.php";
    $output = ob_get_clean();
    $showOutput = true;

    if (strpos($output, "✔️") !== false) {
        $statusType = "success";
    } elseif (strpos($output, "bereits verschlüsselt") !== false) {
        $statusType = "info";
    } else {
        $statusType = "neutral";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>.envad verschlüsseln</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; max-width: 750px; margin: 20px auto; }
        input[type=text] { width: 100%; padding: 6px; margin: 6px 0; }
        textarea { width: 100%; height: 200px; margin-top: 10px; }
        .btn { padding: 8px 14px; margin-top: 10px; }
        nav a { margin-right: 12px; }
        .success { background: #e0ffe0; border-left: 4px solid #0a0; padding: 10px; }
        .info { background: #e0f0ff; border-left: 4px solid #06c; padding: 10px; }
        .neutral { background: #f0f0f0; border-left: 4px solid #aaa; padding: 10px; }
    </style>
</head>
<body>
<?php include "admin_tab_nav.php"; ?>

<h2>.envad Passwortverschlüsselung</h2>
<p>Dieses Tool verschlüsselt Klartext-Passwörter in der Datei <code>.envad</code>.</p>

<form method="post">
    <label for="key">Schlüssel (leer lassen für Default "1qay2wsx3edc"):</label>
    <input type="text" name="key" id="key" value="<?= htmlspecialchars($usedKey) ?>">
    <button class="btn" type="submit">Jetzt verschlüsseln</button>
</form>

<?php if ($showOutput): ?>
    <div class="<?= $statusType ?>">
        <?= nl2br(htmlspecialchars($output)) ?>
    </div>
<?php endif; ?>
</body>
</html>
