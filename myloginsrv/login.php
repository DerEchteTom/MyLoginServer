<?php
// Datei: login.php – Stand: 2025-04-24 12:50 Uhr Europe/Berlin

date_default_timezone_set('Europe/Berlin');
session_start();
require_once __DIR__ . '/mailer_config.php';

$error = '';
$ldapDebug = '';
$username = trim($_POST['username'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $action = $_POST['action'] ?? '';

    if ($action === 'local') {
        // Lokale Anmeldung
        $db = new PDO('sqlite:users.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $db->prepare("SELECT * FROM users WHERE username = :u");
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'] ?? '')) {
            if ((int)$user['active'] === 1) {
                $_SESSION['user'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                header("Location: " . ($user['role'] === 'admin' ? 'admin.php' : 'dashboard.php'));
                exit;
            } else {
                $error = "Dein Zugang ist noch nicht aktiviert.";
            }
        } else {
            $error = "Benutzername oder Passwort ungültig.";
        }
    } elseif ($action === 'ad') {
        // AD-Anmeldung
        $ldapHost = 'ldap://your-ad-server';
        $ldapBase = 'dc=example,dc=com';
        $ldapDomain = 'example.com';
        $ldapUser = "$username@$ldapDomain";
        $ldapConn = ldap_connect($ldapHost);

        if ($ldapConn && ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3)) {
            if (@ldap_bind($ldapConn, $ldapUser, $password)) {
                $db = new PDO('sqlite:users.db');
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $email = "$username@$ldapDomain";

                $stmt = $db->prepare("SELECT * FROM users WHERE username = :u");
                $stmt->execute([':u' => $username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user) {
                    $insert = $db->prepare("INSERT INTO users (username, password, email, role, active) VALUES (:u, '', :e, 'user', 0)");
                    $insert->execute([':u' => $username, ':e' => $email]);
                    file_put_contents("audit.log", date('c') . " AD-Import: Benutzer '$username' wurde angelegt (inaktiv)
", FILE_APPEND);

                    $userId = $db->lastInsertId();
                    $defaultLinks = json_decode(file_get_contents('default_links.json'), true);
                    if (is_array($defaultLinks)) {
                        $stmt = $db->prepare("INSERT INTO user_links (user_id, alias, url) VALUES (:uid, :a, :u)");
                        foreach ($defaultLinks as $entry) {
                            $stmt->execute([':uid' => $userId, ':a' => $entry['alias'], ':u' => $entry['url']]);
                        }
                    }

                    // Willkommensmail senden
                    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                        $mail = getMailer($email, "Your login at MyLoginSrv");
                        if ($mail) {
                            $mail->Body = "Hello $username,

Your account has been created using your company login.

To use the system, an administrator still has to activate your access.

Login page: http://localhost:8080/login.php

Best regards";
                            try {
                                $mail->send();
                                file_put_contents("audit.log", date('c') . " Welcome mail sent to $email
", FILE_APPEND);
                            } catch (Exception $e) {
                                file_put_contents("error.log", date('c') . " Mail error to $email: " . $mail->ErrorInfo . "\n", FILE_APPEND);
                            }
                        }
                    }

                    $error = "Account created. Awaiting admin activation.";
                } elseif ((int)$user['active'] === 1) {
                    $_SESSION['user'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    header("Location: " . ($user['role'] === 'admin' ? 'admin.php' : 'dashboard.php'));
                    exit;
                } else {
                    $error = "Your account is not yet activated.";
                }
            } else {
                $error = "AD login failed.";
$ldapDebug = "LDAP bind failed for user: $ldapUser";
            }
        } else {
            $error = "Cannot connect to AD.";
$ldapDebug = "LDAP connection to $ldapHost failed." ;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 500px;">
    <h4 class="mb-4">Anmeldung</h4>
    <?php if ($error): ?><div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post" class="bg-white border rounded p-4">
        <div class="mb-3">
            <label class="form-label">Benutzername</label>
            <input type="text" name="username" class="form-control form-control-sm" value="<?= htmlspecialchars($username) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Passwort</label>
            <input type="password" name="password" class="form-control form-control-sm" required>
        </div>
        <div class="d-flex justify-content-between">
            <button name="action" value="local" class="btn btn-outline-primary btn-sm">Lokal anmelden</button>
            <button name="action" value="ad" class="btn btn-outline-secondary btn-sm">AD anmelden</button>
        </div>
    </form>
<div class="mt-3 text-center">
    <a href="register.php" class="btn btn-link btn-sm">Registrieren</a>
    <a href="forgot.php" class="btn btn-link btn-sm">Passwort vergessen?</a>
</div>
<?php if (!empty($ldapDebug)): ?>
<details class="mt-3 small">
<summary>LDAP-Debug anzeigen</summary>
<pre class="bg-light border p-2"><?= htmlspecialchars($ldapDebug) ?></pre>
</details>
<?php endif; ?>
</div>
</body>
</html>
