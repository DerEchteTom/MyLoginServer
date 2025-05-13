<?php
// Datei: admin_tab_adimport.php – Version: 2025-05-12_01 (2-Stufen-Import mit Vorschau)
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
                $uid = $entries[$i]['samaccountname'][0] ?? '';
                $email = $entries[$i]['mail'][0] ?? '';
                if ($uid && $email) {
                    $users[] = ['username' => $uid, 'email' => $email];
                }
            }
        }
    }
}
$db = new PDO('sqlite:users.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$existing = $db->query("SELECT username FROM users")->fetchAll(PDO::FETCH_COLUMN);
$users = array_values(array_filter($users, fn($u) => !in_array($u['username'], $existing)));
usort($users, fn($a, $b) => strcmp($a['username'], $b['username']));

// Vorschau: Daten aus POST wiederherstellen
if ($stage === 'preview' && isset($_POST['selected'])) {
    foreach ($_POST['selected'] as $idx) {
        $username = $_POST['u'][$idx] ?? '';
        $email = $_POST['e'][$idx] ?? '';
        if ($username && $email) {
            $preview[] = ['username' => $username, 'email' => $email];
        }
    }
}

// Importphase
if ($stage === 'import' && isset($_POST['u'], $_POST['e'], $_POST['role'], $_POST['active'])) {
    $imported = 0;
    foreach ($_POST['u'] as $i => $username) {
        $email = $_POST['e'][$i];
        $role = $_POST['role'][$i];
        $active = $_POST['active'][$i] == '1' ? 1 : 0;

        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = :u");
        $stmt->execute([':u' => $username]);
        if ($stmt->fetchColumn() == 0) {
            $stmt = $db->prepare("INSERT INTO users (username, email, role, active) VALUES (:u, :e, :r, :a)");
            $stmt->execute([':u' => $username, ':e' => $email, ':r' => $role, ':a' => $active]);
            $imported++;
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
<body class="container-fluid mt-4">
<h4>AD-Benutzer importieren (2-Stufen)</h4>
<?php if ($notice): ?><div class="alert alert-success"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php if ($stage === 'select'): ?>
<form method="post">
    <input type="hidden" name="stage" value="preview">
    <div class="mb-2 d-flex gap-2 align-items-center">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleCheckboxes(true)">Alle auswählen</button>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleCheckboxes(false)">Alle abwählen</button>
        <button type="submit" class="btn btn-outline-primary btn-sm">Auswahl zur Vorschau</button>
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
        <thead><tr><th>#</th><th>Benutzername</th><th>E-Mail</th><th>Rolle</th><th>Aktiv</th></tr></thead>
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
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <button type="submit" class="btn btn-outline-success">Importieren</button>
</form>
<?php elseif ($stage === 'preview'): ?>
<p class="text-muted">Keine gültigen Benutzer ausgewählt.</p>
<?php endif; ?>
</body>
</html>