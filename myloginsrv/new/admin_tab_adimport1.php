<?php
// Datei: admin_tab_adimport.php – Version: 2025-05-09_01
date_default_timezone_set('Europe/Berlin');
require_once "auth.php";
requireRole('admin');
require_once 'config_support.php';

$notice = '';
$error = '';
$imported = 0;
$users = [];
// Sortierung anwenden
$sort = $_GET['sort'] ?? 'username';
usort($users, function($a, $b) use ($sort) {
    return strcmp(strtolower($a[$sort]), strtolower($b[$sort]));
});


$env = parseEnvFile('.envad');
$key = getEncryptionKey($env);

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
                $uid = $entries[$i]['samaccountname'][0] ?? '';
                $email = $entries[$i]['mail'][0] ?? '';
                $dn = $entries[$i]['dn'] ?? '';
                if ($uid && $email) {
                    $users[] = ['username' => $uid, 'email' => $email, 'dn' => $dn];
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import']) && isset($_POST['usernames'])) {
    $db = new PDO('sqlite:users.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    foreach ($_POST['usernames'] as $i) {
        $row = $users[$i];
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = :u");
        $stmt->execute([':u' => $row['username']]);
        if ($stmt->fetchColumn() == 0) {
            $stmt = $db->prepare("INSERT INTO users (username, email, role, active) VALUES (:u, :e, 'user', 0)");
            $stmt->execute([':u' => $row['username'], ':e' => $row['email']]);
            $imported++;
        }
    }
    $notice = "$imported Benutzer importiert.";
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>AD-Benutzer importieren</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">
<?php include 'admin_tab_nav.php'; ?>
<h4>AD-Benutzer importieren</h4>

<?php if ($notice): ?><div class="alert alert-success"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php
usort($users, fn($a, $b) => strcmp($a['username'], $b['username']));
$db = new PDO('sqlite:users.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$existing = $db->query("SELECT username FROM users")->fetchAll(PDO::FETCH_COLUMN);
$users = array_values(array_filter($users, fn($u) => !in_array($u['username'], $existing)));
?>
<?php if ($users): ?>
<script>
function toggleCheckboxes(state) {
    document.querySelectorAll('input[name="usernames[]"]').forEach(cb => cb.checked = state);
}
</script>
<div class="mb-2 d-flex gap-2 align-items-center">
    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleCheckboxes(true)">Alle auswaehlen</button>
    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleCheckboxes(false)">Alle abwaehlen</button>
</div>


<form method="post" onsubmit="return confirm('Ausgewählte Benutzer wirklich importieren?')">
    <table class="table table-sm table-bordered bg-white">
        <thead><tr><th>#</th><th>Benutzername</th><th>E-Mail</th><th>Auswahl</th></tr></thead>
        <tbody>
        <?php foreach ($users as $i => $u): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><input type="checkbox" name="usernames[]" value="<?= $i ?>" checked></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <button type="submit" name="import" class="btn btn-outline-primary">Ausgewaehlte Benutzer importieren</button>
   </form>
<?php else: ?>
    <p>Keine Benutzer gefunden oder LDAP-Verbindung fehlgeschlagen.</p>
<?php endif; ?>
</body>
</html>

<script>
function toggleAll(state) {
    document.querySelectorAll('.chk-import').forEach(cb => cb.checked = state);
}
</script>
