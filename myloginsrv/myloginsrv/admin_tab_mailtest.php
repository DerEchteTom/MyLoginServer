<?php
// Datei: admin_tab_mailtest.php – Stand: 2025-04-22 10:35 Europe/Berlin

date_default_timezone_set('Europe/Berlin');
require_once __DIR__ . '/auth.php';
requireRole('admin');
require_once __DIR__ . '/mailer_config.php';

$info = '';
$error = '';
$debugLog = '';
$debugEnv = debugSMTPEnv();
$envPath = __DIR__ . '/.env';

if (!isset($_SESSION['last_mail_time'])) {
    $_SESSION['last_mail_time'] = 0;
}
$now = time();
$cooldown = 10;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_testmail'])) {
        if (($now - $_SESSION['last_mail_time']) < $cooldown) {
            $error = "Zu viele Anfragen. Bitte warten Sie mindestens $cooldown Sekunden.";
        } else {
            $_SESSION['last_mail_time'] = $now;
            $to = trim($_POST['recipient'] ?? '');
            if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $mail = getMailer($to, "Testmail von MyLoginSrv");
                if ($mail) {
                    $mail->Body = "Dies ist eine Testmail vom System.\n\nMyLoginSrv Adminbereich\n" . date('Y-m-d H:i');

                    $debugLog .= "--- Mail Preview ---\n";
                    $debugLog .= "To: " . $to . "\n";
                    $debugLog .= "From: " . $mail->From . "\n";
                    $debugLog .= "Subject: " . $mail->Subject . "\n";
                    $debugLog .= "Body:\n" . $mail->Body . "\n";
                    $debugLog .= "--------------------\n";

                    try {
                        $mail->send();
                        $info = "Testmail erfolgreich gesendet an $to.";
                        @file_put_contents("audit.log", date('c') . " Testmail an $to gesendet durch Admin {\$_SESSION['user']}
", FILE_APPEND);
                    } catch (Exception $e) {
                        $error = "Fehler beim Senden: " . $mail->ErrorInfo;
                        $debugLog .= "Fehler beim Senden an $to: " . $mail->ErrorInfo . "\n";
                        @file_put_contents("error.log", date('c') . " Fehler beim Senden an $to: " . $mail->ErrorInfo . "\n", FILE_APPEND);
                    }
                } else {
                    $error = "Mailer konnte nicht initialisiert werden.";
                }
            } else {
                $error = "Ungültige E-Mail-Adresse.";
            }
        }
    }

    if (isset($_POST['save_env']) && isset($_POST['env_content'])) {
        $content = trim($_POST['env_content']);
        if ($content) {
            file_put_contents($envPath, $content);
            $info = ".env gespeichert.";
            $debugLog .= ".env aktualisiert durch Admin\n";
            @file_put_contents("audit.log", date('c') . " .env bearbeitet durch Admin {\$_SESSION['user']}
", FILE_APPEND);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Mail-Konfiguration</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container-fluid mt-4">
    <?php include __DIR__ . '/admin_tab_nav.php'; ?>
    <h4 class="mb-4">Mail-Test & Konfiguration</h4>

    <?php if ($info): ?><div class="alert alert-success small"><?= htmlspecialchars($info) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="post" class="row g-2 mb-4">
        <div class="col-4">
            <input type="email" name="recipient" class="form-control form-control-sm" placeholder="Empfängeradresse" required>
        </div>
        <div class="col-auto">
            <button type="submit" name="send_testmail" class="btn btn-sm btn-outline-primary">Testmail senden</button>
        </div>
    </form>

    <div class="bg-white border rounded p-3 mb-4">
        <h6 class="mb-3">Aktuelle .env-Konfiguration</h6>
        <table class="table table-sm table-bordered">
            <thead class="table-light"><tr><th>Variable</th><th>Wert</th></tr></thead>
            <tbody>
                <?php foreach ($debugEnv as $key => $val): ?>
                    <tr><td><?= htmlspecialchars($key) ?></td><td><?= htmlspecialchars($val) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <form method="post" class="mb-4">
        <h6 class="mb-2">.env-Datei bearbeiten</h6>
        <textarea name="env_content" rows="10" class="form-control small mb-2"><?= htmlspecialchars(file_get_contents($envPath)) ?></textarea>
        <button name="save_env" class="btn btn-sm btn-outline-success">Speichern</button>
    </form>

    <div class="bg-light border rounded p-3 small">
        <h6 class="mb-2">Debug-Ausgabe</h6>
        <pre class="mb-0"><?= htmlspecialchars($debugLog) ?></pre>
    </div>
</div>
</body>
</html>
