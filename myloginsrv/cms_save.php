<?php
// cms_save.php
// Version: 2025-05-19_01
// Beschreibung: Speichert die bearbeiteten Inhalte des CMS und verarbeitet Bild-Uploads.
// Erstellt: 2025-05-19
// Geändert: 2025-05-19

require_once 'config.php'; // Deine Konfigurationsdatei (Datenbankverbindung)
require_once 'functions.php'; // Deine Hilfsfunktionen wie htmlspecialchars()

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sicherstellen, dass der Admin angemeldet ist
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        die('Zugriff verweigert');
    }

    // Verarbeite Textinhalt
    if (isset($_POST['section_name']) && isset($_POST['text_content'])) {
        $section_name = htmlspecialchars($_POST['section_name']);
        $text_content = htmlspecialchars($_POST['text_content']);

        // Update oder Insert in die Datenbank
        $stmt = $pdo->prepare("REPLACE INTO page_content (section_name, text_content) VALUES (?, ?)");
        $stmt->execute([$section_name, $text_content]);
    }

    // Verarbeite Bild-Upload
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $file_info = $_FILES['image_file'];
        $file_tmp = $file_info['tmp_name'];
        $file_name = basename($file_info['name']);
        $file_type = mime_content_type($file_tmp);

        // Überprüfen des MIME-Typs (nur JPEG, PNG erlauben)
        $allowed_types = ['image/jpeg', 'image/png'];
        if (in_array($file_type, $allowed_types)) {
            // Zielpfad für das Bild
            $upload_dir = './uploads/';
            $upload_path = $upload_dir . $file_name;

            // Bild speichern
            if (move_uploaded_file($file_tmp, $upload_path)) {
                // Bildpfad in der Datenbank speichern
                $stmt = $pdo->prepare("UPDATE page_content SET image_path = ? WHERE section_name = ?");
                $stmt->execute([$upload_path, $section_name]);
            } else {
                die('Fehler beim Speichern des Bildes');
            }
        } else {
            die('Ungültiger MIME-Typ für das Bild');
        }
    }

    // Erfolgreiche Speicherung
    header('Location: dashboard.php?success=1');
    exit;
} else {
    die('Ungültige Anfrage');
}
