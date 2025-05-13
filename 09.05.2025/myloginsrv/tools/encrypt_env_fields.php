<?php
// Datei: encrypt_env_fields.php – Stand: 2025-04-24 11:12:05 Europe/Berlin

date_default_timezone_set("Europe/Berlin");

function parseEnvFile($path = ".env") {
    $env = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), "#") === 0 || strpos($line, "=") === false) {
            continue;
        }
        list($k, $v) = array_map("trim", explode("=", $line, 2));
        $env[$k] = $v;
    }
    return $env;
}

function encryptSecret($plain, $key) {
    $iv = openssl_random_pseudo_bytes(16);
    $cipher = 'aes-256-cbc';
    $enc = openssl_encrypt($plain, $cipher, $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $enc);
}

function rewriteEnvFile($path, $data, $originalLines, $tag) {
    $output = [];
    foreach ($originalLines as $line) {
        if (strpos(trim($line), "#") === 0 || strpos($line, "=") === false) {
            $output[] = $line;
            continue;
        }
        list($k) = array_map("trim", explode("=", $line, 2));
        if (array_key_exists($k, $data)) {
            $output[] = $k . "=" . $data[$k];
        } else {
            $output[] = $line;
        }
    }
    $output[] = "# " . $tag;
    file_put_contents($path, implode(PHP_EOL, $output) . PHP_EOL);
}

// Einstellungen
$envPath = ".env";
$tag = "ENCRYPTION_DONE";
$encryptedFields = ["AD_BIND_PASSWORD", "SMTP_PASSWORD"];
$encryptKey = getenv("ENCRYPTION_KEY") ?: "GeheimerSchluessel123";

if (!file_exists($envPath)) {
    echo "Fehler: .env-Datei nicht gefunden.\n";
    exit(1);
}

$original = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($original as $line) {
    if (strpos($line, $tag) !== false) {
        echo "Die .env wurde bereits verschlüsselt. Vorgang wird abgebrochen.\n";
        exit(0);
    }
}

$env = parseEnvFile($envPath);
$changes = [];

foreach ($encryptedFields as $key) {
    if (isset($env[$key]) && strpos($env[$key], "ENC:") !== 0) {
        $enc = encryptSecret($env[$key], $encryptKey);
        $env[$key] = "ENC:" . $enc;
        $changes[$key] = $env[$key];
        echo "✔️  $key wurde verschlüsselt.\n";
    }
}

if (count($changes) > 0) {
    rewriteEnvFile($envPath, $env, $original, $tag);
    echo "\nErfolgreich abgeschlossen.\n";
} else {
    echo "Keine unverschlüsselten Passwörter gefunden.\n";
}
?>
