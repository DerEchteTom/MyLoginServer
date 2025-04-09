<?php
try {
    $db = new PDO('sqlite:users.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
    echo "SQLite-Datenbank initialisiert.";
} catch (Exception $e) {
    echo "Fehler beim Initialisieren der Datenbank: " . $e->getMessage();
    exit(1);
}
?>
