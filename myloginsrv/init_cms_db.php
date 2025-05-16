<?php
// File: init_cms_db.php – Initializes 'info.db' for Mini CMS
$db_file = __DIR__ . '/info.db';

try {
    $pdo = new PDO("sqlite:$db_file");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS page_content (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        section_name TEXT NOT NULL UNIQUE,
        text_content TEXT,
        image_path TEXT,
        link_url TEXT,
        link_text TEXT
    )");

    // Insert default sections only if table is empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM page_content");
    if ($stmt->fetchColumn() == 0) {
        $sections = [
            'header', 'text1', 'text2', 'text3',
            'link1_text', 'link1_url', 'link2_text', 'link2_url'
        ];
        $insert = $pdo->prepare("INSERT INTO page_content (section_name) VALUES (:section)");
        foreach ($sections as $section) {
            $insert->execute([':section' => $section]);
        }
    }

} catch (PDOException $e) {
    // Log to error.log instead of dying silently
    file_put_contents(__DIR__ . '/error.log', date('c') . " [CMS DB ERROR] " . $e->getMessage() . "\n", FILE_APPEND);
    exit("Database initialization failed.");
}
?>
