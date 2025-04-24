<?php
// Datei: admin_upload_users.php – Stand: 2025-04-23 17:42 Europe/Berlin

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['userfile'])) {
    $importSuccess = 0;
    $importFailed = 0;

    $file = $_FILES['userfile']['tmp_name'];
    if (is_uploaded_file($file)) {
        $data = json_decode(file_get_contents($file), true);
        if (is_array($data)) {
            $db = new PDO('sqlite:users.db');
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            foreach ($data as $user) {
                $username = trim($user['username'] ?? '');
                $email    = trim($user['email'] ?? '');
                $password = $user['password'] ?? '';
                $role     = $user['role'] ?? 'user';
                $active   = isset($user['active']) ? (int)$user['active'] : 0;

                if ($username && $email && $password) {
                    $exists = $db->prepare("SELECT COUNT(*) FROM users WHERE username = :u");
                    $exists->execute([':u' => $username]);
                    if ($exists->fetchColumn() == 0) {
                        try {
                            $hash = password_hash($password, PASSWORD_DEFAULT);
                            $stmt = $db->prepare("INSERT INTO users (username, password, email, role, active) VALUES (:u, :p, :e, :r, :a)");
                            $stmt->execute([':u' => $username, ':p' => $hash, ':e' => $email, ':r' => $role, ':a' => $active]);
                            file_put_contents("audit.log", date("c") . " Import: Benutzer '$username' erfolgreich importiert\n", FILE_APPEND);
                            $importSuccess++;
                        } catch (Exception $e) {
                            $importFailed++;
                            file_put_contents("error.log", date("c") . " Fehler beim Import von '$username': {$e->getMessage()}\n", FILE_APPEND);
                        }
                    } else {
                        $importFailed++;
                        file_put_contents("error.log", date("c") . " Benutzer '$username' bereits vorhanden – übersprungen\n", FILE_APPEND);
                    }
                }
            }
        }
    }
    echo "<div class='alert alert-info small mt-2'>Import abgeschlossen: $importSuccess erfolgreich, $importFailed übersprungen oder fehlgeschlagen.</div>";
}
?>

<form method="post" enctype="multipart/form-data" class="mb-3 bg-white p-3 rounded border">
    <div class="row g-2 align-items-center">
        <div class="col-auto">
            <label class="form-label mb-0">Benutzerliste (.json) hochladen:</label>
        </div>
        <div class="col-auto">
            <input type="file" name="userfile" accept=".json" class="form-control form-control-sm" required>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-sm btn-outline-primary">Importieren</button>
        </div>
    </div>
</form>
