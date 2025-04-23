<?php
// Datei: admin_tab_links.php – Stand: 2025-04-23 12:19 Europe/Berlin

date_default_timezone_set('Europe/Berlin');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
requireRole('admin');

$db = new PDO('sqlite:users.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$info = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_json']) && isset($_FILES['uploadfile'])) {
    $tmpName = $_FILES['uploadfile']['tmp_name'];
    if (is_uploaded_file($tmpName)) {
        $json = json_decode(file_get_contents($tmpName), true);
        if (is_array($json)) {
            $count = 0;
            foreach ($json as $entry) {
                if (!isset($entry['user_id'], $entry['alias'], $entry['url'])) continue;
                $check = $db->prepare("SELECT COUNT(*) FROM user_links WHERE user_id = :uid AND alias = :alias");
                $check->execute([':uid' => $entry['user_id'], ':alias' => $entry['alias']]);
                if ($check->fetchColumn() == 0) {
                    $stmt = $db->prepare("INSERT INTO user_links (user_id, alias, url) VALUES (:uid, :alias, :url)");
                    $stmt->execute([':uid' => $entry['user_id'], ':alias' => $entry['alias'], ':url' => $entry['url']]);
                    $count++;
                }
            }
            $info = "$count Links aus Upload importiert.";
        } else {
            $error = "Ungültige JSON-Datei.";
        }
    } else {
        $error = "Upload fehlgeschlagen.";
    }
}

// JSON-Datei aus Upload importieren
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_json']) && isset($_FILES['uploadfile'])) {
    $tmpName = $_FILES['uploadfile']['tmp_name'];
    if (is_uploaded_file($tmpName)) {
        $json = json_decode(file_get_contents($tmpName), true);
        if (is_array($json)) {
            $count = 0;
            foreach ($json as $entry) {
                if (!isset($entry['user_id'], $entry['alias'], $entry['url'])) continue;
                $check = $db->prepare("SELECT COUNT(*) FROM user_links WHERE user_id = :uid AND alias = :alias");
                $check->execute([':uid' => $entry['user_id'], ':alias' => $entry['alias']]);
                if ($check->fetchColumn() == 0) {
                    $stmt = $db->prepare("INSERT INTO user_links (user_id, alias, url) VALUES (:uid, :alias, :url)");
                    $stmt->execute([':uid' => $entry['user_id'], ':alias' => $entry['alias'], ':url' => $entry['url']]);
                    $count++;
                }
            }
            $info = "$count Links aus Upload-Datei importiert.";
        } else {
            $error = "Ungültige JSON-Struktur in Upload.";
        }
    } else {
        $error = "Upload fehlgeschlagen.";
    }}
        $info = "$inserted Standardlinks wurden global verteilt.";

$error = '';

$selectedUserId = (int)($_POST['selected_user'] ?? $_GET['selected_user'] ?? 0);

// Link hinzufügen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_link']) && $selectedUserId > 0) {
    $alias = trim($_POST['alias'] ?? '');
    $url = trim($_POST['url'] ?? '');
    if ($alias && $url) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM user_links WHERE user_id = :uid AND alias = :alias");
        $stmt->execute([':uid' => $selectedUserId, ':alias' => $alias]);
        if ($stmt->fetchColumn() == 0) {
            $insert = $db->prepare("INSERT INTO user_links (user_id, alias, url) VALUES (:uid, :alias, :url)");
            $insert->execute([':uid' => $selectedUserId, ':alias' => $alias, ':url' => $url]);
            $info = "Link hinzugefügt.";
            file_put_contents("audit.log", date('c') . " Link '$alias' für Benutzer ID $selectedUserId hinzugefügt\n", FILE_APPEND);
        } else {
            $error = "Alias '$alias' existiert bereits.";
        }
    } else {
        $error = "Alias und URL müssen ausgefüllt sein.";
    }
}

// Link löschen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_link']) && isset($_POST['link_id'])) {
    $linkId = (int)$_POST['link_id'];
    $stmt = $db->prepare("DELETE FROM user_links WHERE id = :id");
    $stmt->execute([':id' => $linkId]);
    $info = "Link ID $linkId gelöscht.";
    file_put_contents("audit.log", date('c') . " Link ID $linkId gelöscht durch Admin\n", FILE_APPEND);
}

// JSON importieren
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_links'])) {
    $filename = trim($_POST['import_filename'] ?? 'import_links.json');
    if (file_exists($filename)) {
        $json = json_decode(file_get_contents($filename), true);
        if (is_array($json)) {
            $count = 0;
            foreach ($json as $entry) {
                if (!isset($entry['user_id'], $entry['alias'], $entry['url'])) continue;
                $check = $db->prepare("SELECT COUNT(*) FROM user_links WHERE user_id = :uid AND alias = :alias");
                $check->execute([':uid' => $entry['user_id'], ':alias' => $entry['alias']]);
                if ($check->fetchColumn() == 0) {
                    $stmt = $db->prepare("INSERT INTO user_links (user_id, alias, url) VALUES (:uid, :alias, :url)");
                    $stmt->execute([':uid' => $entry['user_id'], ':alias' => $entry['alias'], ':url' => $entry['url']]);
                    $count++;
                }
            }
            $info = "$count Links importiert.";
        } else {
            $error = "Ungültige JSON-Struktur.";
        }
    } else {
        $error = "Datei '$filename' nicht gefunden.";
    }
}

$users = $db->query("SELECT id, username FROM users ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);
$links = $selectedUserId > 0
    ? $db->query("SELECT * FROM user_links WHERE user_id = $selectedUserId ORDER BY alias")->fetchAll(PDO::FETCH_ASSOC)
    : [];
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Benutzer-Links</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container-fluid mt-4">
    <?php include __DIR__ . '/admin_tab_nav.php'; ?>

    <h4 class="mb-3">Benutzer-Links verwalten</h4>

    <?php if ($info): ?><div class="alert alert-success small"><?= htmlspecialchars($info) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    


<form method="post" class="row gx-2 gy-1 align-items-end mb-3">

        <div class="col-auto">
            <label class="form-label small">Benutzer wählen</label>
            <select name="selected_user" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">-- Benutzer wählen --</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $u['id'] == $selectedUserId ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['username']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <label class="form-label small">Import-Datei</label>
            <input type="text" name="import_filename" class="form-control form-control-sm" placeholder="import_links.json">
        </div>
        <div class="col-auto mt-2">
            <button name="import_links" value="1" class="btn btn-sm btn-outline-secondary">Links importieren</button>
        </div>
    </form>

    <?php if ($selectedUserId): ?>
        <form method="post" class="row gx-2 gy-1 mb-3">
            <input type="hidden" name="selected_user" value="<?= $selectedUserId ?>">
            <div class="col">
                <label class="form-label small mb-0">Alias</label>
                <input type="text" name="alias" class="form-control form-control-sm" required>
            </div>
            <div class="col">
                <label class="form-label small mb-0">URL</label>
                <input type="url" name="url" class="form-control form-control-sm" required>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0 d-block">&nbsp;</label>
                <button name="add_link" value="1" class="btn btn-sm btn-outline-primary">Link hinzufügen</button>
            </div>
        </form>

        <table class="table table-sm table-bordered bg-white">
            <thead class="table-light">
                <tr>
                    <th>Alias</th>
                    <th>URL</th>
                    <th style="width: 120px;">Aktion</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($links as $l): ?>
                    <tr>
                        <td><?= htmlspecialchars($l['alias']) ?></td>
                        <td><?= htmlspecialchars($l['url']) ?></td>
                        <td>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="link_id" value="<?= $l['id'] ?>">
                                <input type="hidden" name="selected_user" value="<?= $selectedUserId ?>">
                                <button type="submit" name="delete_link" value="1" class="btn btn-sm btn-outline-danger">Löschen</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($links)): ?>
                    <tr><td colspan="3" class="text-center text-muted">Keine Links vorhanden.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>