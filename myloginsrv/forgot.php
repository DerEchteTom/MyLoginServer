<?php
session_start();
if (isset($_SESSION['user'])) {
    header("Location: links.php");
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $db = new PDO('sqlite:users.db');
        $stmt = $db->prepare("SELECT id, username FROM users WHERE email = :email AND active = 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = time() + 3600; // 1 Stunde gültig

            $stmt = $db->prepare("UPDATE users SET reset_token = :token, reset_expires = :expires WHERE id = :id");
            $stmt->execute([':token' => $token, ':expires' => $expires, ':id' => $user['id']]);

            // Mail versenden
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = getenv('SMTP_HOST');
            $mail->Port = getenv('SMTP_PORT');
            $mail->setFrom(getenv('SMTP_FROM'));
            $mail->addAddress($email);
            $mail->Subject = "Passwort-Zurücksetzen";
            $mail->Body = "Hallo {$user['username']},\n\nfolgender Link führt dich zur Passwort-Zurücksetzen-Seite:\n\nhttp://" . $_SERVER['HTTP_HOST'] . "/reset.php?token=$token\n\nDieser Link ist 1 Stunde gültig.";

            $mail->SMTPAuth = (getenv('SMTP_AUTH') === 'true');
            if ($mail->SMTPAuth) {
                $mail->Username = getenv('SMTP_USER');
                $mail->Password = getenv('SMTP_PASS');
            }
            $secure = getenv('SMTP_SECURE');
            if (in_array($secure, ['tls', 'ssl'])) {
                $mail->SMTPSecure = $secure;
            }
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            try {
                $mail->send();
                $success = "Eine E-Mail mit weiteren Anweisungen wurde an $email gesendet.";
            } catch (Exception $e) {
                $error = "Fehler beim Senden der E-Mail: " . $mail->ErrorInfo;
            }
        } else {
            $error = "Keine Benutzerkonto mit dieser E-Mail gefunden.";
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 500px;">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-4">Passwort vergessen</h4>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php elseif ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="mb-3">
                    <label for="email" class="form-label">E-Mail-Adresse</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Zurücksetzen anfordern</button>
            </form>
            <div class="mt-3 text-center">
                <a href="login.php">Zurück zum Login</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>