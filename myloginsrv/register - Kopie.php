<?php
// Datei: register.php – Stand: 2025-04-23 09:37 Europe/Berlin

date_default_timezone_set('Europe/Berlin');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer_config.php';

$info = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $email && $password) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Ungültige E-Mail-Adresse.";
        } else {
            try {
                $db = new PDO('sqlite:users.db');
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $stmt = $db->prepare("INSERT INTO users (username, password, email, active) VALUES (:u, :p, :e, :a)");
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt->execute([':u' => $username, ':p' => $hash, ':e' => $email, ':a' => 0]);

                file_put_contents("audit.log", date("c") . " Registrierung (inaktiv) angelegt: $username <$email>\n", FILE_APPEND);
                $info = "Dein Konto wurde angelegt. Nach Freigabe durch einen Administrator kannst du dich anmelden.";

                if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                    $mail = getMailer($email, "Willkommen bei MyLoginSrv");
                    if ($mail) {
                        $mail->Body = "Hallo $username,\n\nVielen Dank für deine Registrierung.\n\nSobald dein Zugang durch den Administrator freigeschaltet wurde, kannst du dich hier anmelden:\nhttp://localhost:8080/login.php\n\nViele Grüße";
                        try {
                            $mail->send();
                            file_put_contents("audit.log", date('c') . " Willkommensmail an $email gesendet\n", FILE_APPEND);
                        } catch (Exception $e) {
                            file_put_contents("error.log", date('c') . " Fehler beim Senden an $email: " . $mail->ErrorInfo . "\n", FILE_APPEND);
                        }
                    }
                }
            } catch (Exception $e) {
                $error = "Registrierung fehlgeschlagen: " . $e->getMessage();
                file_put_contents("error.log", date("c") . " Fehler bei Registrierung $username: " . $e->getMessage() . "\n", FILE_APPEND);
            }
        }
    } else {
        $error = "Bitte alle Felder ausfüllen.";
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Registrierung</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 540px;">
    <div class="card shadow-sm">
        <div class="card-body">
            <h4 class="mb-3">Registrieren</h4>
            <?php if ($info): ?><div class="alert alert-success small"><?= htmlspecialchars($info) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Benutzername</label>
                    <input type="text" name="username" class="form-control form-control-sm" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">E-Mail-Adresse</label>
                    <input type="email" name="email" class="form-control form-control-sm" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Passwort</label>
                    <input type="password" name="password" class="form-control form-control-sm" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Registrieren</button>
                <a href="login.php" class="btn btn-link w-100 mt-2">Zurück zum Login</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
