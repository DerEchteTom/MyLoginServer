<?php
session_start();

require_once __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = trim($_POST['username'] ?? '');
    if ($input === '') {
        $error = "Bitte Benutzername oder E-Mail eingeben.";
    } else {
        $db = new PDO('sqlite:users.db');
        $stmt = $db->prepare("SELECT * FROM users WHERE username = :input OR email = :input LIMIT 1");
        $stmt->execute([':input' => $input]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = time() + 3600; // 1 Stunde gültig
            $db->prepare("UPDATE users SET reset_token = :token, reset_expires = :expires WHERE id = :id")
                ->execute([':token' => $token, ':expires' => $expires, ':id' => $user['id']]);

            // Mail senden
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = getenv('SMTP_HOST');
                $mail->Port = getenv('SMTP_PORT');
                $mail->setFrom(getenv('SMTP_FROM'));
                $mail->addAddress($user['email']);
                $mail->Subject = 'Passwort zurücksetzen';
                $link = "http://" . $_SERVER['HTTP_HOST'] . "/reset.php?token=$token";
                $mail->Body = "Hallo {$user['username']},\n\nKlicke auf folgenden Link, um dein Passwort zurückzusetzen:\n$link\n\nDieser Link ist 1 Stunde gültig.";
                $mail->send();
                $success = "✅ Falls ein Konto existiert, wurde eine E-Mail zum Zurücksetzen des Passworts gesendet.";
                file_put_contents("audit.log", date('c') . " PASSWORD RESET REQUEST: {$user['username']} FROM {$_SERVER['REMOTE_ADDR']}\n", FILE_APPEND);
            } catch (Exception $e) {
                $error = "Fehler beim Senden der E-Mail: " . $mail->ErrorInfo;
            }
        } else {
            $success = "✅ Falls ein Konto existiert, wurde eine E-Mail zum Zurücksetzen des Passworts gesendet.";
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
<div class="container mt-5" style="max-width: 500px;">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-4">🔑 Passwort vergessen</h4>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <form method="post">
                <div class="mb-3">
                    <label for="username" class="form-label">Benutzername oder E-Mail-Adresse</label>
                    <input type="text" class="form-control" name="username" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Zurücksetzen anfordern</button>
            </form>
            <a href="login.php" class="btn btn-link mt-3">🔙 Zurück zum Login</a>
        </div>
    </div>
</div>
</body>
</html>