<?php
// Datei: admin_tab_adimport_execute.php – Führt den Import der ausgewählten AD-Benutzer durch
date_default_timezone_set('Europe/Berlin');
require_once "auth.php";
requireRole('admin');
require_once "config_support.php";

$db = new SQLite3("users.db");
$imported = 0;
$users = $_POST['users'] ?? [];

foreach ($users as $username => $data) {
    if (!isset($data['import']) || !$data['import']) continue;

    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = :u");
    $stmt->bindValue(':u', $username);
    if ($stmt->execute()->fetchArray()[0] > 0) continue;

    $email = trim($data['email'] ?? '');
    $active = isset($data['active']) ? 1 : 0;
    $role = in_array($data['role'], ['user', 'admin']) ? $data['role'] : 'user';

    $insert = $db->prepare("INSERT INTO users (username, password, email, role, active) VALUES (:u, NULL, :e, :r, :a)");
    $insert->bindValue(':u', $username);
    $insert->bindValue(':e', $email);
    $insert->bindValue(':r', $role);
    $insert->bindValue(':a', $active);
    $insert->execute();

    logAction("audit.log", "AD-Benutzer $username importiert (Rolle: $role, Aktiv: $active)");

    if (!empty($data['import_links'])) {
        $json = json_decode(file_get_contents("default_links.json"), true);
        if (is_array($json)) {
            foreach ($json as $entry) {
                $alias = $entry['alias'] ?? '';
                $url = $entry['url'] ?? '';
                if ($alias && $url) {
                    $s = $db->prepare("INSERT INTO user_links (user_id, alias, url) VALUES ((SELECT id FROM users WHERE username = :u), :a, :l)");
                    $s->bindValue(':u', $username);
                    $s->bindValue(':a', $alias);
                    $s->bindValue(':l', $url);
                    $s->execute();
                }
            }
        }
    }

    $imported++;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Import abgeschlossen</title>
    <link href="./assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">
    <div class="alert alert-success">
        ✅ <?= $imported ?> Benutzer wurden erfolgreich importiert.
    </div>
    <a href="admin_tab_adimport.php" class="btn btn-outline-primary">Zurück zur Auswahl</a>
</body>
</html>