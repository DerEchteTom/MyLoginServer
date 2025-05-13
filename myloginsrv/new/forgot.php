<?php
// Datei: forgot_lowercase.php – Passwort-Reset mit lowercase – Stand: 2025-05-13 Europe/Berlin
date_default_timezone_set('Europe/Berlin');
require_once 'config_support.php';

$info = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = strtolower(trim($_POST['username'] ?? ''));
    $email = strtolower(trim($_POST['email'] ?? ''));

    if ($username && $email) {
        $db = new PDO('sqlite:users.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $db->prepare("SELECT * FROM users WHERE LOWER(username) = :u AND LOWER(email) = :e");
        $stmt->execute([':u' => $username, ':e' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $token = bin2hex(random_bytes(16));
            $reset_url = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/reset.php?token=$token";

            $tmpfile = __DIR__ . "/reset_" . $token . ".txt";
            file_put_contents($tmpfile, $username . "
" . time());

            $mail = getConfiguredMailer();
            if ($mail) {
                $mail->addAddress($email);
                $mail->Subject = "Passwort-Zurücksetzung";
                $mail->Body = "Hallo $username,

Zur Zurücksetzung deines Passworts klicke bitte auf den folgenden Link:
$reset_url

Dieser Link ist für 1 Stunde gültig.
";

                try {
                    $mail->send();
                    file_put_contents("audit.log", date("c") . " Reset-Link gesendet an $email
", FILE_APPEND);
                    $info = "Ein Link zur Zurücksetzung wurde gesendet.";
                } catch (Exception $e) {
                    file_put_contents("error.log", date("c") . " Fehler beim Senden an $email: " . $mail->ErrorInfo . "
", FILE_APPEND);
                    $error = "Fehler beim Mailversand.";
                }
            } else {
                $error = "Mailkonfiguration fehlt.";
            }
        } else {
            $error = "Benutzer nicht gefunden oder E-Mail passt nicht.";
        }
    } else {
        $error = "Bitte Benutzername und E-Mail eingeben.";
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
<body class="container mt-5" style="max-width: 540px;">
    <div class="card bg-white border border-secondary">
        <div class="card-body">
            <h5 class="text-secondary">Passwort vergessen?</h5>
            <?php if ($info): ?><div class="alert alert-success"><?= htmlspecialchars($info) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Benutzername</label>
                    <input type="text" name="username" class="form-control form-control-sm" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">E-Mail-Adresse</label>
                    <input type="email" name="email" class="form-control form-control-sm" required>
                </div>
                <button type="submit" class="btn btn-outline-primary w-100">Zurücksetzen</button>
                <a href="login.php" class="btn btn-link w-100 mt-2">Zurück zum Login</a>
            </form>
        </div>
    </div>
</body>
</html>
