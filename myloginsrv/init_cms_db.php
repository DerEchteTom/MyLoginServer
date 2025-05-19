<?php
// init_cms_db.php – Initialisiert 'cms.db' und fügt die 'settings'-Tabelle hinzu
$db_file = __DIR__ . '/cms.db';  // Verwendet 'cms.db'

try {
    // Verbindung zur SQLite-Datenbank herstellen
    $pdo = new PDO("sqlite:$db_file");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Tabelle 'page_content' für die CMS-Inhalte erstellen
    $pdo->exec("CREATE TABLE IF NOT EXISTS page_content (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        section_name TEXT NOT NULL UNIQUE,
        text_content TEXT,
        image_path TEXT,
        link_url TEXT,
        link_text TEXT
    )");

    // Tabelle 'settings' für Konfigurationen hinzufügen
    $stmt = $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='settings'");
    if ($stmt->fetchColumn() == 0) {
        // 'settings'-Tabelle erstellen, falls nicht vorhanden
        $pdo->exec("CREATE TABLE settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_name TEXT NOT NULL UNIQUE,
            setting_value TEXT NOT NULL
        )");
    }

    // 'redirect_timer' in 'settings'-Tabelle einfügen, falls noch nicht vorhanden
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_name = 'redirect_timer'");
    $stmt->execute();
    $existing_value = $stmt->fetchColumn();

    if ($existing_value === false) {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_name, setting_value) VALUES ('redirect_timer', '5')");
        $stmt->execute();
        echo "Standardwert für 'redirect_timer' wurde auf 5 gesetzt.\n";
    } else {
        echo "'redirect_timer' existiert bereits mit dem Wert: " . $existing_value . "\n";
    }

} catch (PDOException $e) {
    echo "Datenbankinitialisierung fehlgeschlagen: " . $e->getMessage();
    exit();
}
?>
