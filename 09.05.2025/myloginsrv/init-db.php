<?php
// Datei: init-db.php – Stand: 2025-04-22 11:03 Europe/Berlin

date_default_timezone_set('Europe/Berlin');

try {
    $db = new PDO('sqlite:users.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "🔍 Prüfe Datenbankstruktur...\n";

    // Tabelle 'users'
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT DEFAULT NULL,
        email TEXT,
        role TEXT NOT NULL DEFAULT 'user',
        active INTEGER NOT NULL DEFAULT 1,
        redirect_urls TEXT DEFAULT '[]',
        reset_token TEXT DEFAULT NULL,
        reset_expires INTEGER DEFAULT NULL
    )");

    $exists = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ((int)$exists === 0) {
        $adminPass = password_hash('adminpass', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, password, email, role, active, redirect_urls) VALUES ('admin', :p, '', 'admin', 1, '[]')");
        $stmt->execute([':p' => $adminPass]);
        echo "✅ Admin-Benutzer 'admin' mit Passwort 'adminpass' wurde angelegt.\n";
    }

    // Tabelle 'link_requests'
    $db->exec("CREATE TABLE IF NOT EXISTS link_requests (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        alias TEXT NOT NULL,
        url TEXT NOT NULL,
        created_at TEXT NOT NULL,
        status TEXT DEFAULT 'open'
    )");

    // Tabelle 'user_links'
    $db->exec("CREATE TABLE IF NOT EXISTS user_links (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        alias TEXT NOT NULL,
        url TEXT NOT NULL
    )");

    // Logdateien initialisieren
    foreach (['audit.log', 'error.log'] as $logfile) {
        if (!file_exists($logfile)) {
            file_put_contents($logfile, '');
            echo "📄 Logdatei '$logfile' wurde erstellt.\n";
        }
    }

    // .env prüfen oder ergänzen
    $envFile = __DIR__ . '/.env';
    $defaultEnv = [
        'SMTP_HOST'   => '',
        'SMTP_PORT'   => '',
        'SMTP_FROM'   => '',
        'SMTP_SECURE' => 'none',
        'SMTP_AUTH'   => 'off',
        'ADMIN_EMAIL' => ''
    ];

    if (!file_exists($envFile)) {
        $content = "";
        foreach ($defaultEnv as $key => $val) {
            $content .= "$key=$val\n";
        }
        file_put_contents($envFile, $content);
        echo ".env-Datei wurde erstellt.\n";
    } else {
        $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $envKeys = array_map(fn($line) => explode('=', $line, 2)[0], $envLines);
        $newLines = [];

        foreach ($defaultEnv as $key => $default) {
            if (!in_array($key, $envKeys)) {
                $newLines[] = "$key=$default";
                echo "$key zur .env-Datei ergänzt.\n";
            }
        }

        if ($newLines) {
            file_put_contents($envFile, "\n" . implode("\n", $newLines), FILE_APPEND);
        }
    }

    echo "✅ Initialisierung abgeschlossen.\n";

} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    file_put_contents("error.log", date('c') . " Fehler in init-db.php: " . $e->getMessage() . "\n", FILE_APPEND);
    exit(1);
}
