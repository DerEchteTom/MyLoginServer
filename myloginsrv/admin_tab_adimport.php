<?php
// Datei: admin_tab_adimport_lowercase.php – Version: 2025-05-13_lowercase
date_default_timezone_set('Europe/Berlin');
require_once "auth.php";
requireRole('admin');
require_once 'config_support.php';

$env = parseEnvFile('.envad');
$key = getEncryptionKey($env);
$notice = '';
$error = '';
$stage = $_POST['stage'] ?? 'select';
$preview = [];

$users = [];
$host = decryptValue($env['AD_HOST'] ?? '', $key);
$port = (int)($env['AD_PORT'] ?? 389);
$basedn = decryptValue($env['AD_BASE_DN'] ?? '', $key);
$binddn = decryptValue($env['AD_BIND_DN'] ?? '', $key);
$bindpw = decryptValue($env['AD_BIND_PW'] ?? '', $key);

$conn = @ldap_connect($host, $port);
if ($conn) {
    ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
    if (@ldap_bind($conn, $binddn, $bindpw)) {
        $filter = "(&(objectClass=user)(mail=*))";
        $search = @ldap_search($conn, $basedn, $filter, ['sAMAccountName', 'mail']);
        if ($search) {
            $entries = ldap_get_entries($conn, $search);
            for ($i = 0; $i < $entries['count']; $i++) {
                $uid = strtolower($entries[$i]['samaccountname'][0] ?? '');
                $email = strtolower($entries[$i]['mail'][0] ?? '');
                if ($uid && $email) {
                    $users[] = ['username' => $uid, 'email' => $email];
                }
            }
        }
    }
}
$db = new PDO('sqlite:users.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$existing = $db->query("SELECT LOWER(username) FROM users")->fetchAll(PDO::FETCH_COLUMN);
$users = array_values(array_filter($users, fn($u) => !in_array(strtolower($u['username']), $existing)));
usort($users, fn($a, $b) => strcmp($a['username'], $b['username']));

if ($stage === 'preview' && isset($_POST['selected'])) {
    foreach ($_POST['selected'] as $idx) {
        $username = strtolower($_POST['u'][$idx] ?? '');
        $email = strtolower($_POST['e'][$idx] ?? '');
        if ($username && $email) {
            $preview[] = ['username' => $username, 'email' => $email];
        }
    }
}

if ($stage === 'import' && isset($_POST['u'], $_POST['e'], $_POST['role'], $_POST['active'])) {
    $imported = 0;
    $mails = $_POST['mail'] ?? [];
    foreach ($_POST['u'] as $i => $username) {
        $username = strtolower($username);
        $email = strtolower($_POST['e'][$i]);
        $role = $_POST['role'][$i];
        $active = $_POST['active'][$i] == '1' ? 1 : 0;
        $sendmail = isset($mails[$i]);

        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE LOWER(username) = :u");
        $stmt->execute([':u' => $username]);
        if ($stmt->fetchColumn() == 0) {
            $stmt = $db->prepare("INSERT INTO users (username, email, role, active) VALUES (:u, :e, :r, :a)");
            $stmt->execute([':u' => $username, ':e' => $email, ':r' => $role, ':a' => $active]);
            file_put_contents("audit.log", date("c") . " AD-Benutzer importiert: $username <$email> (Rolle: $role, Aktiv: $active)
", FILE_APPEND);
            $imported++;

            if (file_exists("default_links.json")) {
                $json = json_decode(file_get_contents("default_links.json"), true);
                foreach ($json as $entry) {
                    $alias = strtolower($entry['alias'] ?? '');
                    $url = strtolower($entry['url'] ?? '');
                    if ($alias && $url) {
                        $s = $db->prepare("INSERT INTO user_links (user_id, alias, url) VALUES ((SELECT id FROM users WHERE username = :u), :a, :l)");
                        $s->execute([':u' => $username, ':a' => $alias, ':l' => $url]);
                    }
                }
            }

            if ($active && $sendmail && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $server = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $mail = getConfiguredMailer();
                if ($mail) {
                    $mail->addAddress($email);
                    $mail->Subject = "Willkommen bei MyLoginSrv";
                    $mail->Body = "Hallo $username,

Dein Zugang wurde erstellt.

Sobald dein Zugang durch den Administrator freigeschaltet wurde, kannst du dich hier anmelden: http://$server/login.php

Mit freundlichen Gruessen.";
                    try {
                        $mail->send();
                        file_put_contents("audit.log", date('c') . " Willkommensmail an $email gesendet
", FILE_APPEND);
                    } catch (Exception $e) {
                        file_put_contents("error.log", date('c') . " Fehler beim Senden an $email: " . $mail->ErrorInfo . "
", FILE_APPEND);
                    }
                }
            }
        }
    }
    $notice = "$imported Benutzer erfolgreich importiert.";
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>AD-Benutzer importieren</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container-fluid mt-4">
<?php include "admin_tab_nav.php"; ?>
<h4>AD-Benutzer importieren</h4>
<?php if ($notice): ?><div class="alert alert-success"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php if ($stage === 'select'): ?>
<form method="post">
    <input type="hidden" name="stage" value="preview">
    <div class="mb-2 d-flex gap-2 align-items-center">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleCheckboxes(true)">Alle auswählen</button>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleCheckboxes(false)">Alle abwählen</button>
        <button type="submit" class="btn btn-outline-success btn-sm">Auswahl zur Vorschau</button>
    </div>
    <table class="table table-sm table-bordered bg-white">
        <thead><tr><th>#</th><th>Benutzername</th><th>E-Mail</th><th>Auswahl</th></tr></thead>
        <tbody>
        <?php foreach ($users as $i => $u): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td>
                    <input type="checkbox" name="selected[]" value="<?= $i ?>" checked>
                    <input type="hidden" name="u[<?= $i ?>]" value="<?= htmlspecialchars($u['username']) ?>">
                    <input type="hidden" name="e[<?= $i ?>]" value="<?= htmlspecialchars($u['email']) ?>">
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</form>
<script>
function toggleCheckboxes(state) {
    document.querySelectorAll('input[type=checkbox][name^="selected"]').forEach(cb => cb.checked = state);
}
</script>

<?php elseif ($stage === 'preview' && $preview): ?>
<form method="post">
    <input type="hidden" name="stage" value="import">
    <p>Bitte Benutzer prüfen, Rolle und Aktivierung wählen. Danach Import starten:</p>
    <table class="table table-sm table-bordered bg-white">
        <thead><tr><th>#</th><th>Benutzername</th><th>E-Mail</th><th>Rolle</th><th>Aktiv</th><th>Info E-Mail</th></tr></thead>
        <tbody>
        <?php foreach ($preview as $i => $u): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td>
                    <?= htmlspecialchars($u['username']) ?>
                    <input type="hidden" name="u[]" value="<?= htmlspecialchars($u['username']) ?>">
                </td>
                <td>
                    <?= htmlspecialchars($u['email']) ?>
                    <input type="hidden" name="e[]" value="<?= htmlspecialchars($u['email']) ?>">
                </td>
                <td>
                    <select name="role[]" class="form-select form-select-sm">
                        <option value="user">user</option>
                        <option value="admin">admin</option>
                    </select>
                </td>
                <td>
                    <input type="checkbox" name="active[<?= $i ?>]" value="1" checked>
                </td>
                <td>
                    <input type="checkbox" name="mail[<?= $i ?>]" value="1">
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <button type="submit" class="btn btn-outline-primary">Importieren</button>
</form>
<?php elseif ($stage === 'preview'): ?>
<p class="text-muted">Keine gültigen Benutzer ausgewählt.</p>
<?php endif; ?>
</div>
</body>
</html>
