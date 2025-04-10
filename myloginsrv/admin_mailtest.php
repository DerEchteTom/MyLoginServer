<?php
session_start();
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = getenv('SMTP_HOST');
        $mail->Port = getenv('SMTP_PORT');
        $mail->setFrom(getenv('SMTP_FROM'));
        $mail->addAddress($_POST['email']);
        $mail->Subject = 'MyLoginSrv - SMTP Test';
        $mail->Body = 'Diese E-Mail bestätigt, dass SMTP funktioniert.';
        $mail->send();
        $result = '✅ Test-E-Mail erfolgreich gesendet an: ' . htmlspecialchars($_POST['email']);
    } catch (Exception $e) {
        $result = '❌ Fehler beim Versand: ' . $mail->ErrorInfo;
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
<div class="container mt-5" style="max-width: 500px;">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-3">📧 SMTP Mail-Test</h4>
            <form method="post">
                <div class="mb-3">
                    <label for="email" class="form-label">Empfänger-E-Mail-Adresse</label>
                    <input type="email" class="form-control" name="email" id="email" required>
                </div>
                <button type="submit" class="btn btn-primary">Test-Mail senden</button>
            </form>
            <?php if ($result): ?>
                <div class="alert alert-info mt-3"><?= $result ?></div>
            <?php endif; ?>
            <a href="admin.php" class="btn btn-secondary mt-3">Zurück zum Adminbereich</a>
        </div>
    </div>
</div>
</body>
</html>
