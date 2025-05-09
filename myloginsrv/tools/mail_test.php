<?php
// Datei: mail_test.php – Stand: 2025-04-24 10:49:27 Europe/Berlin
date_default_timezone_set('Europe/Berlin');
require_once "mailer_config.php";

$log_success = "audit.log";
$log_error = "error.log";

$recipient = $_POST["to"] ?? "";
$message = $_POST["message"] ?? "Dies ist ein Testversand vom Login-Server.";

$status = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $mail = getMailer();
    if ($mail) {
        try {
            $mail->addAddress($recipient);
            $mail->Subject = "Testmail vom Login-Server";
            $mail->Body = $message;
            $mail->send();
            $status = "Mail erfolgreich an {$recipient} gesendet.";
            file_put_contents($log_success, date("Y-m-d H:i:s") . " MAILTEST: Erfolg an {$recipient}\n", FILE_APPEND);
        } catch (Exception $e) {
            $status = "Fehler: " . $mail->ErrorInfo;
            file_put_contents($log_error, date("Y-m-d H:i:s") . " MAILTEST FEHLER: " . $mail->ErrorInfo . "\n", FILE_APPEND);
        }
    } else {
        $status = "Mailer konnte nicht initialisiert werden.";
        file_put_contents($log_error, date("Y-m-d H:i:s") . " MAILTEST FEHLER: Mailer nicht initialisiert\n", FILE_APPEND);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Mail Test</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; max-width: 600px; margin: 20px auto; }
        input[type=text], textarea { width: 100%; padding: 6px; margin: 4px 0; }
        .info { background: #e0f0ff; padding: 10px; border: 1px solid #06c; margin-top: 10px; }
    </style>
</head>
<body>
<h2>Testmail versenden</h2>

<?php if ($status): ?>
    <div class="info"><?=htmlspecialchars($status)?></div>
<?php endif; ?>

<form method="post">
    <label>Empfängeradresse (To)</label>
    <input type="text" name="to" value="<?=htmlspecialchars($recipient)?>">
    <label>Nachricht</label>
    <textarea name="message" rows="5"><?=htmlspecialchars($message)?></textarea>
    <br><br>
    <button type="submit">Testmail senden</button>
</form>
</body>
</html>
