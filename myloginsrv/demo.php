<?php
// demo.php – Testseite für Quill-Editor mit Text und Bild Upload für die Sektion "header"

session_start();
date_default_timezone_set('Europe/Berlin');
require_once 'config.php';  // Deine Konfigurationsdatei

// Verbindung zur Datenbank
$pdo = new PDO('sqlite:cms.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Feedback für das Speichern
$feedback = '';

// Wenn das Formular abgeschickt wird (Text und Bild speichern)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedSection = 'header';  // Wir arbeiten nur mit der Sektion "header" für diese Demo

    // Text speichern (Quill-HTML-Inhalt speichern)
    if (isset($_POST['content'])) {
        $content = $_POST['content']; // Quill-Editor HTML-Inhalt erhalten
        $stmt = $pdo->prepare("UPDATE page_content SET text_content = ? WHERE section_name = ?");
        $stmt->execute([$content, $selectedSection]);
        $feedback = 'Text has been saved to the database.';
    }

    // Bild speichern (über Quill Upload)
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file_info = $_FILES['image'];
        $file_tmp = $file_info['tmp_name'];
        $file_name = basename($file_info['name']);
        $file_path = './uploads/' . $file_name;

        if (move_uploaded_file($file_tmp, $file_path)) {
            // Bild-URL in der Datenbank speichern
            $stmt = $pdo->prepare("UPDATE page_content SET image_path = ? WHERE section_name = ?");
            $stmt->execute([$file_path, $selectedSection]);
            $feedback = 'Image has been uploaded and saved to the database.';
        }
    }

    // Bild löschen, wenn Checkbox aktiviert
    if (isset($_POST['delete_image']) && $_POST['delete_image'] == '1') {
        $stmt = $pdo->prepare("SELECT image_path FROM page_content WHERE section_name = ?");
        $stmt->execute([$selectedSection]);
        $image_path = $stmt->fetchColumn();

        if ($image_path && file_exists($image_path)) {
            unlink($image_path); // Bild vom Server löschen
            $stmt = $pdo->prepare("UPDATE page_content SET image_path = NULL WHERE section_name = ?");
            $stmt->execute([$selectedSection]);
            $feedback = 'Image has been deleted from the system and database.';
        } else {
            $feedback = 'Image file does not exist, but the database entry has been removed.';
            $stmt = $pdo->prepare("UPDATE page_content SET image_path = NULL WHERE section_name = ?");
            $stmt->execute([$selectedSection]);
        }
    }

    // Nach dem Absenden zurück zur Seite
    header("Location: demo.php");
    exit;
}

// Daten aus der Datenbank für die Sektion "header" holen
$stmt = $pdo->prepare("SELECT text_content, image_path FROM page_content WHERE section_name = 'header'");
$stmt->execute();
$content = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Demo Page</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        .feedback { color: green; margin-top: 20px; }
        .image-preview { max-width: 100%; height: auto; }
    </style>
</head>
<body class="bg-light">
<div class="container mt-4" style="max-width: 90%;"> 
    <h4>Demo Page for Text and Image Upload</h4>

    <!-- Feedback-Meldung nach dem Speichern -->
    <?php if ($feedback): ?>
        <div class="feedback"><?= htmlspecialchars($feedback) ?></div>
    <?php endif; ?>

    <!-- Text-Quill-Editor für "header" -->
    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="content" class="form-label">Text Content for Header (HTML):</label>
            <div id="content-editor"></div>
            <textarea name="content" id="content" style="display:none;"></textarea> <!-- Quill speichert hier den Inhalt -->
        </div>

        <div class="mb-3">
            <label for="image" class="form-label">Upload Image:</label>
            <input type="file" name="image" id="image" class="form-control" accept="image/*">
        </div>

        <button type="submit" class="btn btn-outline-success">Save Changes</button>
    </form>

    <!-- Anzeige des gespeicherten Textes und Bildes -->
    <h5>Saved Content (header):</h5>
    <div>
        <h6>Text Content:</h6>
        <p><?= nl2br(htmlspecialchars($content['text_content'] ?? '', ENT_QUOTES, 'UTF-8')) ?></p>
        <?php if ($content['image_path']): ?>
            <h6>Image:</h6>
            <img src="<?= htmlspecialchars($content['image_path']) ?>" class="image-preview" alt="Image for header">
            <form method="POST" action="demo.php">
                <input type="hidden" name="delete_image" value="1">
                <button type="submit" class="btn btn-sm btn-outline-danger">Delete Image</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
    // Quill Editor initialisieren für das Textfeld
    var quill = new Quill('#content-editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['link', 'image']
            ]
        }
    });

    // Inhalt von Quill speichern
    document.querySelector('form').onsubmit = function() {
        var content = quill.root.innerHTML;
        document.querySelector('textarea[name="content"]').value = content;
    };
</script>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
