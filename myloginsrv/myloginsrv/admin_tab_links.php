<?php
// Datei: admin_tab_links.php – Stand: 2025-04-22 09:21 Europe/Berlin

date_default_timezone_set('Europe/Berlin');
require_once __DIR__ . '/auth.php';
requireRole('admin');

$db = new PDO('sqlite:users.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$info = '';
$error = '';
$user_id = (int)($_POST['selected_user'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['import_defaults'])) {
        $file = __DIR__ . '/default_links.json';
        if (file_exists($file)) {
            $json = file_get_contents($file);
            $defaults = json_decode($json, true);
            if (is_array($defaults)) {
                $users = $db->query("SELECT id FROM users")->fetchAll(PDO::FETCH_COLUMN);
                $inserted = 0;
                foreach ($users as $uid) {
                    foreach ($defaults as $link) {
                        $alias = trim($link['alias']);
                        $url = trim($link['url']);
                        $stmt = $db->prepare("SELECT COUNT(*) FROM user_links WHERE user_id = :uid AND alias = :a");
                        $stmt->execute([':uid' => $uid, ':a' => $alias]);
                        if ($stmt->fetchColumn() == 0) {
                            $stmt = $db->prepare("INSERT INTO user_links (user_id, alias, url) VALUES (:uid, :a, :u)");
                            $stmt->execute([':uid' => $uid, ':a' => $alias, ':u' => $url]);
                            $inserted++;
                        }
                    }
                }
                $info = "Standardlinks importiert: " . $inserted . " neue Einträge.";
            } else {
                $error = "Fehler beim Parsen der JSON.";
            }
        } else {
            $error = "Datei default_links.json nicht gefunden.";
        }
    }

    if (isset($_POST['action']) && $user_id > 0) {
        $alias = trim($_POST['alias'] ?? '');
        $url = trim($_POST['url'] ?? '');

        if ($_POST['action'] === 'add' && $alias && $url) {
            $stmt = $db->prepare("INSERT INTO user_links (user_id, alias, url) VALUES (:uid, :a, :u)");
            $stmt->execute([':uid' => $user_id, ':a' => $alias, ':u' => $url]);
            $info = "Link hinzugefügt.";
        } elseif ($_POST['action'] === 'delete') {
            $id = (int)$_POST['link_id'];
            $db->prepare("DELETE FROM user_links WHERE id = :id")->execute([':id' => $id]);
            $info = "Link gelöscht.";
        } elseif ($_POST['action'] === 'update' && $alias && $url) {
            $id = (int)$_POST['link_id'];
            $db->prepare("UPDATE user_links SET alias = :a, url = :u WHERE id = :id")->execute([':a' => $alias, ':u' => $url, ':id' => $id]);
            $info = "Link aktualisiert.";
        }
    }
}

$users = $db->query("SELECT id, username FROM users ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Benutzer-Links verwalten</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container-fluid mt-4">
    <?php include __DIR__ . '/admin_tab_nav.php'; ?>
    <h4 class="mb-4">Benutzer-Linkverwaltung</h4>

    <?php if ($info): ?><div class="alert alert-success small"><?= htmlspecialchars($info) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="post" class="mb-3">
        <div class="row g-2 align-items-center">
            <div class="col-auto">
                <label for="selected_user" class="col-form-label">Benutzer auswählen:</label>
            </div>
            <div class="col-auto">
                <select name="selected_user" id="selected_user" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="0">– Benutzer wählen –</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= $u['id'] == $user_id ? 'selected' : '' ?>><?= htmlspecialchars($u['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" name="import_defaults" class="btn btn-sm btn-outline-primary">Standardlinks global einfügen</button>
            </div>
        </div>
    </form>

    <?php if ($user_id > 0): ?>
        <?php
        $stmt = $db->prepare("SELECT id, alias, url FROM user_links WHERE user_id = :uid ORDER BY alias");
        $stmt->execute([':uid' => $user_id]);
        $links = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <form method="post" class="mb-2">
            <input type="hidden" name="selected_user" value="<?= $user_id ?>">
            <div class="row g-2 mb-2">
                <div class="col"><input type="text" name="alias" class="form-control form-control-sm" placeholder="Neuer Alias" required></div>
                <div class="col"><input type="text" name="url" class="form-control form-control-sm" placeholder="Neuer Link" required></div>
                <div class="col-auto"><button type="submit" name="action" value="add" class="btn btn-sm btn-outline-success">Hinzufügen</button></div>
            </div>
        </form>

        <?php foreach ($links as $link): ?>
            <form method="post" class="row g-2 align-items-center mb-1">
                <input type="hidden" name="selected_user" value="<?= $user_id ?>">
                <input type="hidden" name="link_id" value="<?= $link['id'] ?>">
                <div class="col"><input type="text" name="alias" class="form-control form-control-sm" value="<?= htmlspecialchars($link['alias']) ?>"></div>
                <div class="col"><input type="text" name="url" class="form-control form-control-sm" value="<?= htmlspecialchars($link['url']) ?>"></div>
                <div class="col-auto">
                    <button type="submit" name="action" value="update" class="btn btn-sm btn-outline-primary">Speichern</button>
                    <button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-danger">Löschen</button>
                </div>
            </form>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>
