<?php
// Datei: forgot.php – Stand: 2025-04-23 10:12 Europe/Berlin

date_default_timezone_set('Europe/Berlin');
session_start();
require_once __DIR__ . '/mailer_config.php';

$error = '';
$info = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $db = new PDO('sqlite:users.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $db->prepare("SELECT * FROM users WHERE email = :e");
        $stmt->execute([':e' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $token = bin2hex(random_bytes(16));
            $expires = time() + 3600;

            $db->prepare("UPDATE users SET reset_token = :t, reset_expires = :e WHERE id = :id")->execute([
                ':t' => $token,
                ':e' => $expires,
                ':id' => $user['id']
            ]);

            $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/reset.php?token=" . $token;
            $mail = getMailer($email, "Passwort zurücksetzen");
            if ($mail) {
                $mail->Body = "Hallo " . $user['username'] . ",

Klicke auf folgenden Link, um dein Passwort zurückzusetzen:
" . $resetLink . "

Dieser Link ist 1 Stunde gültig.";
                try {
                    $mail->send();
                    file_put_contents("audit.log", date('c') . " Passwort-Resetlink an $email gesendet
", FILE_APPEND);
                    $info = "Ein Link zum Zurücksetzen wurde gesendet.";
                } catch (Exception $e) {
                    file_put_contents("error.log", date('c') . " Fehler beim Mailversand an $email: " . $mail->ErrorInfo . "
", FILE_APPEND);
                    $error = "Fehler beim Senden der E-Mail.";
                }
            }
        } else {
            $info = "Falls diese E-Mail registriert ist, wurde ein Link gesendet.";
        }
    } else {
        $error = "Bitte eine gültige E-Mail-Adresse eingeben.";
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Passwort vergessen</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width:500px;">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-3">Passwort zurücksetzen</h4>

            <?php if ($error): ?><div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($info): ?><div class="alert alert-success small"><?= htmlspecialchars($info) ?></div><?php endif; ?>

            <form method="post">
                <div class="mb-3">
                    <label class="form-label">E-Mail-Adresse</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Link senden</button>
                <a href="login.php" class="btn btn-link mt-2">Zurück zum Login</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
