<?php
// Datei: admin_tab_env_encrypt.php – Verschlüsselung von Passwörtern in .envad
date_default_timezone_set('Europe/Berlin');
session_start();
require_once "mailer_config.php";

function encrypt_value($value, $key = "1qay2wsx3edc") {
    $encrypted = openssl_encrypt($value, "AES-128-ECB", $key, OPENSSL_RAW_DATA);
    return "ENC:" . base64_encode($encrypted);
}

$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["encrypt"])) {
    $custom_key = trim($_POST["custom_key"] ?? "");
    $use_key = $custom_key !== "" ? $custom_key : "1qay2wsx3edc";

    $envad_file = __DIR__ . "/.envad";
    $backup_file = __DIR__ . "/.envadorg";

    if (!file_exists($envad_file)) {
        $message = "<div class='alert alert-danger'>❌ .envad nicht gefunden!</div>";
    } else {
        if (!file_exists($backup_file)) {
            copy($envad_file, $backup_file);
        }

        $lines = file($envad_file);
        $new_lines = [];
        foreach ($lines as $line) {
            if (stripos(trim($line), "AD_BIND_PW=") === 0) {
                list($k, $v) = explode('=', trim($line), 2);
                if (strpos($v, "ENC:") !== 0) {
                    $encrypted = encrypt_value($v, $use_key);
                    $new_lines[] = "$k=$encrypted
";
                    continue;
                }
            }
            $new_lines[] = $line;
        }

        file_put_contents($envad_file, implode("", $new_lines));
        $message = "<div class='alert alert-success'>✅ Verschlüsselung erfolgreich durchgeführt!</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<title>.envad Verschlüsselung</title>
<link rel="stylesheet" href="./assets/css/bootstrap.min.css">
</head>
<body class="p-3">
<?php include "admin_tab_nav.php"; ?>
<div class="container" style="max-width: 600px;">
    <h3>.envad Passwortverschlüsselung</h3>
    <?= $message ?>
    <form method="post" class="mt-3">
        <div class="mb-3">
            <label for="custom_key" class="form-label">Verschlüsselungsschlüssel (optional)</label>
            <input type="text" class="form-control" id="custom_key" name="custom_key" placeholder="Standard: 1qay2wsx3edc">
        </div>
        <button type="submit" name="encrypt" class="btn btn-primary">Passwörter verschlüsseln</button>
    </form>
</div>
</body>
</html>
