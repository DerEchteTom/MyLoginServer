<?php
try {
    $db = new PDO('sqlite:users.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Haupttabelle für Benutzer
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'user',
        active INTEGER NOT NULL DEFAULT 1,
        redirect_urls TEXT DEFAULT '[]',
        reset_token TEXT DEFAULT NULL,
        reset_expires INTEGER DEFAULT NULL
    )");

    // Neue Tabelle: Benutzerlinks
    $db->exec("CREATE TABLE IF NOT EXISTS user_links (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        alias TEXT NOT NULL,
        url TEXT NOT NULL,
        FOREIGN KEY(user_id) REFERENCES users(id)
    )");

    // Admin-Benutzer anlegen, falls noch leer
    $check = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ((int)$check === 0) {
        $adminUser = 'admin';
        $adminPass = password_hash('adminpass', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, password, role, active, redirect_urls) VALUES (:u, :p, 'admin', 1, '[]')");
        $stmt->execute([':u' => $adminUser, ':p' => $adminPass]);
        echo "Admin-Benutzer 'admin' mit Passwort 'adminpass' erstellt.\n";
    } else {
        echo "Datenbank bereits initialisiert.\n";
    }
} catch (Exception $e) {
    echo "Fehler beim Initialisieren der Datenbank: " . $e->getMessage();
    exit(1);
}
?>
