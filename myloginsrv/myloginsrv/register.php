<?php
// Datei: register.php – Stand: 2025-04-22 12:12 Europe/Berlin

date_default_timezone_set('Europe/Berlin');
session_start();
require_once __DIR__ . '/mailer_config.php';

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $email = trim($_POST['email'] ?? '');

    if ($username && $password && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $db = new PDO('sqlite:users.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $db->prepare("INSERT INTO users (username, password, email) VALUES (:u, :p, :e)");
            $stmt->execute([':u' => $username, ':p' => $hash, ':e' => $email]);
            $user_id = $db->lastInsertId();
            file_put_contents("audit.log", date('c') . " Registrierung $username FROM {$_SERVER['REMOTE_ADDR']}\n", FILE_APPEND);

            // Default-Links aus JSON
            $jsonFile = __DIR__ . '/default_links.json';
            if (file_exists($jsonFile)) {
                $defaults = json_decode(file_get_contents($jsonFile), true);
                if (is_array($defaults)) {
                    $stmt = $db->prepare("INSERT INTO user_links (user_id, alias, url) VALUES (:uid, :a, :u)");
                    foreach ($defaults as $entry) {
                        if (!empty($entry['alias']) && !empty($entry['url'])) {
                            $stmt->execute([
                                ':uid' => $user_id,
                                ':a' => $entry['alias'],
                                ':u' => $entry['url']
                            ]);
                        }
                    }
                }
            }

            // Mail senden
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                $mail = getMailer($email, "Willkommen bei MyLoginSrv");
                if ($mail) {
                    $mail->Body = "Hallo $username,\n\nDein Konto wurde erfolgreich eingerichtet.\nLogin: http://" . $_SERVER['HTTP_HOST'] . "/login.php";
                    try {
                        $mail->send();
                        file_put_contents("audit.log", date('c') . " Willkommensmail an $email gesendet\n", FILE_APPEND);
                    } catch (Exception $e) {
                        file_put_contents("error.log", date('c') . " Fehler beim Senden an $email: " . $mail->ErrorInfo . "\n", FILE_APPEND);
                    }
                }
            }

            $success = "Registrierung erfolgreich. Du kannst dich jetzt anmelden.";
        } catch (PDOException $e) {
            $error = "Fehler: Benutzername oder E-Mail ist möglicherweise bereits vergeben.";
            file_put_contents("error.log", date('c') . " Fehler bei Registrierung $username: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    } else {
        $error = "Bitte alle Felder korrekt ausfüllen.";
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Registrieren</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 500px;">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-3">Registrieren</h4>

            <?php if ($error): ?>
                <div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div>
            <?php elseif ($success): ?>
                <div class="alert alert-success small"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Benutzername</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Passwort</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">E-Mail</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Registrieren</button>
                <a href="login.php" class="btn btn-link mt-2">Zurück zum Login</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
