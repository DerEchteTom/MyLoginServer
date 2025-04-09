<?php
session_start();
require_once 'phpmailer/PHPMailerAutoload.php';
$error = "";
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!$email) {
        $error = "Bitte geben Sie eine E-Mail-Adresse ein.";
    } else {
        $db = new PDO('sqlite:users.db');
        $stmt = $db->prepare("SELECT * FROM users WHERE username = :email AND active = 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = time() + 3600;
            $update = $db->prepare("UPDATE users SET reset_token = :token, reset_expires = :expires WHERE id = :id");
            $update->execute([':token' => $token, ':expires' => $expires, ':id' => $user['id']]);

            $mail = new PHPMailer;
            $mail->setFrom(getenv('SMTP_FROM'));
            $mail->addAddress($email);
            $mail->Subject = "Passwort zurücksetzen";
            $mail->Body = "Klicken Sie hier, um Ihr Passwort zurückzusetzen: http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset.php?token=$token";

            if ($mail->send()) {
                file_put_contents("audit.log", date('c') . " PASSWORD RESET MAIL to $email\n", FILE_APPEND);
                $success = true;
            } else {
                $error = "Fehler beim Senden der E-Mail.";
            }
        } else {
            $error = "Benutzer nicht gefunden oder deaktiviert.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Passwort vergessen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 400px;">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-4">Passwort vergessen</h4>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php elseif ($success): ?>
                <div class="alert alert-success">E-Mail zum Zurücksetzen wurde gesendet.</div>
            <?php endif; ?>
            <form method="post">
                <div class="mb-3">
                    <label for="email" class="form-label">E-Mail-Adresse</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Absenden</button>
            </form>
            <div class="mt-3 text-center">
                <a href="login.php">Zurück zum Login</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
