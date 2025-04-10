<?php
// .env manuell einlesen, falls phpdotenv nicht verwendet wird
function parseEnvFile($file) {
    if (!file_exists($file)) return;
    foreach (file($file) as $line) {
        if (strpos(trim($line), '=') !== false) {
            list($key, $val) = explode('=', trim($line), 2);
            putenv(trim($key) . '=' . trim($val));
        }
    }
}
parseEnvFile(__DIR__ . '/.env');

session_start();
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$result = null;
$config = [
    'host' => getenv('SMTP_HOST') ?: '',
    'port' => getenv('SMTP_PORT') ?: '',
    'from' => getenv('SMTP_FROM') ?: '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validierung
    if (!filter_var($_POST['from'], FILTER_VALIDATE_EMAIL)) {
        $result = 'Ungültige Absender-E-Mail-Adresse.';
    } elseif (!filter_var($_POST['to'], FILTER_VALIDATE_EMAIL)) {
        $result = 'Ungültige Empfänger-E-Mail-Adresse.';
    } elseif (!is_numeric($_POST['port']) || (int)$_POST['port'] <= 0) {
        $result = 'SMTP-Port muss eine positive Zahl sein.';
    } elseif (empty($_POST['host'])) {
        $result = 'SMTP-Server darf nicht leer sein.';
    } else {
        // Änderungen in die .env-Datei schreiben
        $envFile = __DIR__ . '/.env';
        $env = file_exists($envFile) ? file_get_contents($envFile) : "";
        $env = preg_replace("/^SMTP_HOST=.*/m", "SMTP_HOST=" . $_POST['host'], $env);
        $env = preg_replace("/^SMTP_PORT=.*/m", "SMTP_PORT=" . $_POST['port'], $env);
        $env = preg_replace("/^SMTP_FROM=.*/m", "SMTP_FROM=" . $_POST['from'], $env);
        file_put_contents($envFile, $env);

        putenv("SMTP_HOST={$_POST['host']}");
        putenv("SMTP_PORT={$_POST['port']}");
        putenv("SMTP_FROM={$_POST['from']}");

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $_POST['host'];
            $mail->Port = $_POST['port'];
            $mail->setFrom($_POST['from']);
            $mail->addAddress($_POST['to']);
            $mail->Subject = 'MyLoginSrv - SMTP Test';
            $mail->Body = 'Diese E-Mail bestätigt, dass SMTP funktioniert.';
            $mail->send();
            $result = 'Test-E-Mail erfolgreich gesendet an: ' . htmlspecialchars($_POST['to']);
        } catch (Exception $e) {
            $result = 'Fehler beim Versand: ' . $mail->ErrorInfo;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>SMTP Mail-Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 600px;">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-3">SMTP Mail-Test</h4>
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">SMTP-Server</label>
                    <input type="text" class="form-control" name="host" value="<?= htmlspecialchars($config['host']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">SMTP-Port</label>
                    <input type="text" class="form-control" name="port" value="<?= htmlspecialchars($config['port']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Absender-E-Mail</label>
                    <input type="email" class="form-control" name="from" value="<?= htmlspecialchars($config['from']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Empfänger-E-Mail</label>
                    <input type="email" class="form-control" name="to" required>
                </div>
                <button type="submit" class="btn btn-primary">Test-Mail senden</button>
            </form>
            <?php if ($result): ?>
                <div class="alert alert-info mt-3"><?= htmlspecialchars($result) ?></div>
            <?php endif; ?>
            <div class="alert alert-light border mt-4">
                <strong>Aktuelle Konfiguration:</strong><br>
                SMTP-Server: <?= htmlspecialchars($config['host']) ?><br>
                SMTP-Port: <?= htmlspecialchars($config['port']) ?><br>
                Absender: <?= htmlspecialchars($config['from']) ?>
            </div>
            <a href="admin.php" class="btn btn-secondary mt-3">Zurück zum Adminbereich</a>
        </div>
    </div>
</div>
</body>
</html>
