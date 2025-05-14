<?php
// Datei: admin_tab_linkeditor.php – Webeditor für Linkzuweisung – Stand: 2025-05-13 Europe/Berlin
date_default_timezone_set('Europe/Berlin');
require_once "auth.php";
requireRole('admin');
require_once "config_support.php";

$log = [];
$error = '';
$success = '';
$db = new PDO("sqlite:users.db");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Benutzerliste laden
$users = $db->query("SELECT username FROM users ORDER BY username ASC")->fetchAll(PDO::FETCH_COLUMN);

function importLinksToUsers(array $linkdata, array $targets, PDO $db, &$log): void {
    foreach ($targets as $username) {
        $stmt = $db->prepare("SELECT id FROM users WHERE LOWER(username) = :u");
        $stmt->execute([':u' => strtolower($username)]);
        $user_id = $stmt->fetchColumn();

        if (!$user_id) {
            $log[] = "⚠ Benutzer '$username' nicht gefunden.";
            continue;
        }

        foreach ($linkdata as $entry) {
            $alias = strtolower(trim($entry['alias'] ?? ''));
            $url = strtolower(trim($entry['url'] ?? ''));

            if (!$alias || !filter_var($url, FILTER_VALIDATE_URL)) {
                $log[] = "⚠ Ungültiger Link für '$username': $alias / $url";
                continue;
            }

            $check = $db->prepare("SELECT COUNT(*) FROM user_links WHERE user_id = :id AND alias = :a");
            $check->execute([':id' => $user_id, ':a' => $alias]);
            if ($check->fetchColumn() > 0) {
                $log[] = "⏭ Alias '$alias' für '$username' existiert bereits – übersprungen.";
                continue;
            }

            $ins = $db->prepare("INSERT INTO user_links (user_id, alias, url) VALUES (:id, :a, :u)");
            $ins->execute([':id' => $user_id, ':a' => $alias, ':u' => $url]);
            $log[] = "✅ '$alias' für '$username' hinzugefügt.";
            file_put_contents("audit.log", date("c") . " Link '$alias' zugewiesen an $username ($url)
", FILE_APPEND);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jsontext = trim($_POST['json'] ?? '');
    $selected = $_POST['users'] ?? [];
    $to_all = isset($_POST['to_all']);

    if (!$jsontext) {
        $error = "Keine JSON-Daten übermittelt.";
    } else {
        $data = json_decode($jsontext, true);
        if (!is_array($data)) {
            $error = "Ungültiges JSON.";
        } else {
            $targets = $to_all ? $users : $selected;
            if (empty($targets)) {
                $error = "Kein Benutzer ausgewählt.";
            } else {
                importLinksToUsers($data, $targets, $db, $log);
                $success = "Import abgeschlossen.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Linkeditor für Benutzer</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        textarea { font-family: monospace; font-size: 0.9rem; }
    </style>
</head>
<body class="bg-light">
<div class="container-fluid mt-4">
<?php include "admin_tab_nav.php"; ?>
<div style="width: 90%; margin: 0 auto;">
<h4>Links via JSON zuweisen</h4>

<?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<form method="post">
    <div class="mb-3">
        <label class="form-label">Links im JSON-Format:</label>
        <textarea name="json" class="form-control" rows="8" required>[{"alias":"Heise","url":"https://heise.de"}]</textarea>
    </div>
    <div class="mb-3">
        <label class="form-label">Benutzer auswählen:</label>
        <select name="users[]" class="form-select" multiple size="6">
            <?php foreach ($users as $u): ?>
                <option value="<?= htmlspecialchars($u) ?>"><?= htmlspecialchars($u) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" name="to_all" id="to_all">
            <label class="form-check-label" for="to_all">Allen Benutzern zuweisen (ignoriert obige Auswahl)</label>
        </div>
    </div>
    <button type="submit" class="btn btn-outline-primary">Import starten</button>
</form>

<?php if (!empty($log)): ?>
<div class="mt-4">
    <h6>Import-Log:</h6>
    <pre class="bg-light border p-2 small"><?= htmlspecialchars(implode("\n", $log)) ?></pre>
</div>
<?php endif; ?>
</body>
</html>
