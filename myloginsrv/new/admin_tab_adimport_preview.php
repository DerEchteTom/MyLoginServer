<?php
// Datei: admin_tab_adimport_preview.php – Vorschau & finaler Import – mit Logging & Redirect
require_once 'auth.php';
requireRole('admin');
require_once 'config_support.php';

$db = new SQLite3('users.db');
$import_notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalize_import'])) {
    $count = 0;
    $roles = $_POST['role'] ?? [];
    $actives = $_POST['active'] ?? [];
    $emails = $_POST['email'] ?? [];
    $withLinks = isset($_POST['import_links']);

    foreach ($_POST['selected'] ?? [] as $username) {
        $email = $emails[$username] ?? '';
        $role = $roles[$username] ?? 'user';
        $active = in_array($username, $actives) ? 1 : 0;

        $check = $db->prepare("SELECT COUNT(*) FROM users WHERE username = :u");
        $check->bindValue(':u', $username);
        if ($check->execute()->fetchArray(SQLITE3_NUM)[0] == 0) {
            $stmt = $db->prepare("INSERT INTO users (username, password, email, role, active) VALUES (:u, NULL, :e, :r, :a)");
            $stmt->bindValue(':u', $username);
            $stmt->bindValue(':e', $email);
            $stmt->bindValue(':r', $role);
            $stmt->bindValue(':a', $active);
            $stmt->execute();
            logAction("audit.log", "AD-Benutzer $username importiert (Rolle: $role, Aktiv: $active)");
            $count++;

            if ($withLinks && file_exists("default_links.json")) {
                $links = json_decode(file_get_contents("default_links.json"), true);
                foreach ($links as $link) {
                    $alias = $link['alias'] ?? '';
                    $url = $link['url'] ?? '';
                    if ($alias && $url) {
                        $s = $db->prepare("INSERT INTO user_links (user_id, alias, url)
                                           SELECT id, :a, :u FROM users WHERE username = :u2");
                        $s->bindValue(':a', $alias);
                        $s->bindValue(':u', $url);
                        $s->bindValue(':u2', $username);
                        $s->execute();
                    }
                }
            }
        }
    }
    $import_notice = "$count Benutzer importiert.";
    if ($count == 0) {
        logAction("error.log", "Kein Benutzer importiert – möglicherweise alle bereits vorhanden.");
    }
}

$selected = $_POST['selected'] ?? [];
$emails = $_POST['email'] ?? [];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>AD-Benutzer Import – Vorschau</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container-fluid mt-4">

<?php include "admin_tab_nav.php"; ?>
<h4>Importvorschau</h4>

<?php if ($import_notice): ?>
<div class="alert alert-success"><?= htmlspecialchars($import_notice) ?></div>
<script>setTimeout(() => { window.location.href = 'admin_tab_users.php'; }, 1500);</script>
<?php endif; ?>

<form method="post">
    <input type="hidden" name="finalize_import" value="1">
    <div class="form-check mb-3">
        <input type="checkbox" class="form-check-input" id="import_links" name="import_links" checked>
        <label class="form-check-label" for="import_links">Standard-Links aus default_links.json zuweisen</label>
    </div>

    <table class="table table-sm table-bordered bg-white">
        <thead class="table-light">
            <tr>
                <th>Benutzername</th>
                <th>E-Mail</th>
                <th>Rolle</th>
                <th>Aktiv</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($selected as $username): ?>
            <tr>
                <td>
                    <?= htmlspecialchars($username) ?>
                    <input type="hidden" name="selected[]" value="<?= htmlspecialchars($username) ?>">
                </td>
                <td>
                    <input type="hidden" name="email[<?= htmlspecialchars($username) ?>]" value="<?= htmlspecialchars($emails[$username] ?? '') ?>">
                    <?= htmlspecialchars($emails[$username] ?? '') ?>
                </td>
                <td>
                    <select name="role[<?= htmlspecialchars($username) ?>]" class="form-select form-select-sm">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </td>
                <td class="text-center">
                    <input type="checkbox" name="active[]" value="<?= htmlspecialchars($username) ?>">
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="d-flex gap-2">
        <a href="admin_tab_adimport.php" class="btn btn-outline-secondary">Zurück</a>
        <button type="submit" class="btn btn-outline-primary">Import jetzt durchführen</button>
    </div>
</form>
</body>
</html>
