<?php
// cms_edit.php – Edit CMS Content with Section Name Change, Image Upload (AJAX), Text Formatting, Timer Edit, Image Deletion, Section Collapse

session_start();
date_default_timezone_set('Europe/Berlin');
require_once 'config.php';  // Deine Konfigurationsdatei
require_once 'auth.php';     // Authentifizierung und Rollenzuweisung

// Funktion zum Schreiben in das Audit-Log
function writeAuditLog($message) {
    file_put_contents(__DIR__ . '/audit.log', date('c') . " [AUDIT] " . $message . "\n", FILE_APPEND);
}

// Funktion zum Schreiben in das Error-Log
function writeErrorLog($message) {
    file_put_contents(__DIR__ . '/error.log', date('c') . " [ERROR] " . $message . "\n", FILE_APPEND);
}

// Admin-Check
if (!isAuthenticated() || !hasRole("admin")) {
    die('Access denied');
}

// Überprüfen, ob der Upload-Ordner existiert, wenn nicht, erstellen
$uploadDir = './uploads/';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        writeErrorLog("Failed to create upload directory.");
        die('Failed to create upload directory');
    } else {
        writeAuditLog("Created upload directory.");
    }
}

// Berechtigungen überprüfen (sollten 0777 sein)
if (!is_writable($uploadDir)) {
    writeErrorLog("Upload directory is not writable.");
    die('Upload directory is not writable. Please check the permissions.');
}

// Abrufen des Timer-Werts aus der settings-Tabelle
$pdo = new PDO('sqlite:cms.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_name = 'redirect_timer'");
$timerSetting = $stmt->fetchColumn();
if (!$timerSetting) {
    // Falls der Timer-Wert nicht gesetzt ist, setzen wir den Standardwert (5 Sekunden)
    $timerSetting = 5;
}

// Abrufen von allen Sektionen und deren Inhalt
$stmt = $pdo->query("SELECT section_name, text_content, image_path FROM page_content");
$sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Wenn keine Sektionen vorhanden sind
if (empty($sections)) {
    die("No CMS content available to edit.");
}

// Bildskalierungs-Grenzwert (editierbar)
$maxImageWidth = 800;  // Max. Breite des Bildes (in Pixel) - diesen Wert kannst du hier ändern

// Wenn das Formular abgeschickt wird (Text, Section Name und Bild speichern)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($sections as $section) {
        $sectionName = $section['section_name'];

        // Abschnittsname bearbeiten
        if (isset($_POST['section_name'][$sectionName]) && !empty($_POST['section_name'][$sectionName])) {
            $newSectionName = htmlspecialchars($_POST['section_name'][$sectionName]);
            $stmt = $pdo->prepare("UPDATE page_content SET section_name = ? WHERE section_name = ?");
            $stmt->execute([$newSectionName, $sectionName]);
            writeAuditLog("Section name for '$sectionName' changed to '$newSectionName'.");
        }

        // Text speichern mit Formatierung
        if (isset($_POST['content'][$sectionName])) {
            $newText = htmlspecialchars($_POST['content'][$sectionName]);
            $stmt = $pdo->prepare("UPDATE page_content SET text_content = ? WHERE section_name = ?");
            $stmt->execute([$newText, $sectionName]);
            writeAuditLog("Text content for '$sectionName' updated.");
        }

        // Bild speichern (wenn hochgeladen)
        if (isset($_FILES['image'][$sectionName]) && $_FILES['image'][$sectionName]['error'] === UPLOAD_ERR_OK) {
            $file_info = $_FILES['image'][$sectionName];
            $file_tmp = $file_info['tmp_name'];
            $file_name = basename($file_info['name']);
            $file_type = mime_content_type($file_tmp);

            // Überprüfen des MIME-Typs (nur JPEG, PNG erlauben)
            $allowed_types = ['image/jpeg', 'image/png'];
            if (in_array($file_type, $allowed_types)) {
                $ext = pathinfo($file_name, PATHINFO_EXTENSION);
                $filename = uniqid('img_', true) . '.' . $ext;
                $upload_path = $uploadDir . $filename;

                // Bildskalierung (Max. Breite 800px)
                list($width, $height) = getimagesize($file_tmp);
                if ($width > $maxImageWidth) {
                    $newHeight = intval($height * ($maxImageWidth / $width));
                    $src = imagecreatefromstring(file_get_contents($file_tmp));
                    $dst = imagecreatetruecolor($maxImageWidth, $newHeight);
                    imagecopyresampled($dst, $src, 0, 0, 0, 0, $maxImageWidth, $newHeight, $width, $height);
                    imagejpeg($dst, $upload_path, 85);
                    imagedestroy($src);
                    imagedestroy($dst);
                } else {
                    move_uploaded_file($file_tmp, $upload_path);
                }

                // Bildpfad in der Datenbank speichern
                $stmt = $pdo->prepare("UPDATE page_content SET image_path = ? WHERE section_name = ?");
                $stmt->execute([$upload_path, $sectionName]);
                writeAuditLog("Image uploaded for '$sectionName' and saved as '$upload_path'.");
            } else {
                writeErrorLog("Invalid image type for '$sectionName'. Only JPEG and PNG are allowed.");
                die('Invalid image type. Only JPEG and PNG are allowed.');
            }
        }

        // Bild löschen, falls gewünscht
        if (isset($_POST['delete_image'][$sectionName]) && $_POST['delete_image'][$sectionName] == '1') {
            $stmt = $pdo->prepare("SELECT image_path FROM page_content WHERE section_name = ?");
            $stmt->execute([$sectionName]);
            $imagePath = $stmt->fetchColumn();

            if ($imagePath && file_exists($imagePath)) {
                unlink($imagePath);  // Bild löschen
                writeAuditLog("Image for '$sectionName' deleted.");
            }

            // Bildpfad aus der Datenbank entfernen
            $stmt = $pdo->prepare("UPDATE page_content SET image_path = NULL WHERE section_name = ?");
            $stmt->execute([$sectionName]);
        }
    }

    // Timer-Wert speichern
    if (isset($_POST['timer_value'])) {
        $newTimerValue = intval($_POST['timer_value']);
        $stmt = $pdo->prepare("INSERT OR REPLACE INTO settings (setting_name, setting_value) VALUES ('redirect_timer', ?)");
        $stmt->execute([$newTimerValue]);
        writeAuditLog("Timer value updated to '$newTimerValue' seconds.");
    }

    // Nach dem Speichern zurück zur Dashboard-Seite
    header("Location: dashboard.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit CMS Content</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- TinyMCE WYSIWYG Editor -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        // TinyMCE Initialisierung für jedes Textarea
        tinymce.init({
            selector: '.textarea',  // Selektor für die Textarea
            menubar: false,          // Keine Menüleiste
            toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright | bullist numlist | image',  // Toolbar Optionen
            plugins: 'image',        // Plugins aktivieren (für Bild-Upload)
            file_picker_types: 'image',  // Nur Bilder auswählbar
            images_upload_url: 'upload.php', // Upload-URL für Bilder
            automatic_uploads: true,  // Automatisches Hochladen von Bildern
        });

        // Funktion zum Umschalten der Sichtbarkeit der Sektionen
        function toggleSection(sectionName) {
            const sectionContent = document.getElementById('section-content-' + sectionName);
            sectionContent.style.display = sectionContent.style.display === 'none' ? 'block' : 'none';
        }
    </script>
    <style>
        .form-control { width: 100%; }
        .textarea { height: 5em; resize: vertical; overflow-y: auto; }
        .image-preview { max-width: 300px; }
        .section-name { width: 100%; max-width: 300px; }
        .btn-container { margin-top: 20px; }
        .section-collapse { cursor: pointer; padding: 10px; border: 1px solid #ddd; margin-top: 10px; }
        .section-content { margin-top: 10px; display: none; }
        .section-collapse:before {
            content: "+";
            margin-right: 10px;
        }
        .section-content.show {
            display: block;
        }
        /* Zusatzstil für AJAX */
        .ajax-status { font-weight: bold; margin-top: 10px; }
    </style>
</head>
<body>
<div class="container mt-4">
    <h4>Edit CMS Content</h4>

    <form method="POST" enctype="multipart/form-data">
        <?php foreach ($sections as $section): ?>
            <!-- Klappbare Sektion -->
            <div class="section-collapse" onclick="toggleSection('<?= htmlspecialchars($section['section_name']) ?>')">
                <?= ucfirst(htmlspecialchars($section['section_name'])) ?>
            </div>
            <div id="section-content-<?= htmlspecialchars($section['section_name']) ?>" class="section-content">
                <div class="mb-3">
                    <!-- Section Name bearbeiten -->
                    <label for="section_name_<?= htmlspecialchars($section['section_name']) ?>" class="form-label">Section Name (<?= ucfirst(htmlspecialchars($section['section_name'])) ?>):</label>
                    <input type="text" class="form-control section-name" id="section_name_<?= htmlspecialchars($section['section_name']) ?>" name="section_name[<?= htmlspecialchars($section['section_name']) ?>]" value="<?= htmlspecialchars($section['section_name']) ?>" required>
                </div>

                <div class="mb-3">
                    <!-- Text Editor für den Inhalt (TinyMCE) -->
                    <label for="content_<?= htmlspecialchars($section['section_name']) ?>" class="form-label">Content for <?= ucfirst(htmlspecialchars($section['section_name'])) ?>:</label>
                    <textarea id="content_<?= htmlspecialchars($section['section_name']) ?>" name="content[<?= htmlspecialchars($section['section_name']) ?>]" class="form-control textarea"><?= htmlspecialchars($section['text_content']) ?></textarea>
                </div>

                <!-- Bild Upload (AJAX Version) -->
                <div class="mb-3">
                    <label for="image_<?= htmlspecialchars($section['section_name']) ?>" class="form-label">Upload Image for <?= ucfirst(htmlspecialchars($section['section_name'])) ?> (AJAX):</label>
                    <input type="file" id="image_<?= htmlspecialchars($section['section_name']) ?>" name="image[<?= htmlspecialchars($section['section_name']) ?>]" class="form-control" onchange="ajaxImageUpload(<?= htmlspecialchars(json_encode($section['section_name'])) ?>)">
                    <div id="ajax-status-<?= htmlspecialchars($section['section_name']) ?>" class="ajax-status"></div>
                </div>

                <!-- Bild Vorschau, falls vorhanden -->
                <?php if ($section['image_path']): ?>
                    <div class="mb-3">
                        <label for="image_preview_<?= htmlspecialchars($section['section_name']) ?>" class="form-label">Current Image:</label><br>
                        <img src="<?= htmlspecialchars($section['image_path']) ?>" class="image-preview" alt="Image Preview">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="delete_image[<?= htmlspecialchars($section['section_name']) ?>]" value="1" id="delete_<?= htmlspecialchars($section['section_name']) ?>">
                            <label class="form-check-label text-danger" for="delete_<?= htmlspecialchars($section['section_name']) ?>">Delete Image</label>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <!-- Timer Wert bearbeiten -->
        <div class="mb-3">
            <label for="timer_value" class="form-label">Redirect Timer (seconds):</label>
            <input type="number" class="form-control" id="timer_value" name="timer_value" value="<?= htmlspecialchars($timerSetting) ?>" min="1">
        </div>

        <button type="submit" class="btn btn-outline-success">Save Changes</button>
    </form>
</div>

<!-- JavaScript for AJAX Image Upload -->
<script>
    function ajaxImageUpload(sectionName) {
        let formData = new FormData();
        let imageFile = document.querySelector(`#image_${sectionName}`).files[0];
        formData.append('image', imageFile);
        formData.append('section_name', sectionName);

        // AJAX request
        let xhr = new XMLHttpRequest();
        xhr.open('POST', 'upload.php', true);

        xhr.onload = function () {
            if (xhr.status === 200) {
                document.getElementById(`ajax-status-${sectionName}`).innerHTML = "Image uploaded successfully.";
                // Optionally update the image preview here
            } else {
                document.getElementById(`ajax-status-${sectionName}`).innerHTML = "Failed to upload image.";
            }
        };

        xhr.onerror = function () {
            document.getElementById(`ajax-status-${sectionName}`).innerHTML = "An error occurred during the upload.";
        };

        xhr.send(formData);
    }
</script>

</body>
</html>
