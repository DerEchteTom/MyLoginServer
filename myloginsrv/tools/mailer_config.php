<?php
// Datei: mailer_config.php – Stand: 2025-04-24 10:45:26 Europe/Berlin
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/vendor/autoload.php';

function parseEnvFile($path = ".env") {
    $env = [];
    if (!file_exists($path)) return $env;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($key, $value) = array_map("trim", explode("=", $line, 2));
        $env[$key] = $value;
    }
    return $env;
}

function decryptEnvValue($ciphertext_b64, $key) {
    $ciphertext = base64_decode($ciphertext_b64);
    $iv = substr($ciphertext, 0, 16);
    $raw = substr($ciphertext, 16);
    return openssl_decrypt($raw, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
}


function getMailer() {
    $env = parseEnvFile();
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $env["SMTP_HOST"] ?? '';
        $mail->Port = (int)($env["SMTP_PORT"] ?? 25);
        $mail->SMTPAuth = strtolower($env["SMTP_AUTH"] ?? '') === 'on';
        $mail->SMTPSecure = ($env["SMTP_SECURE"] ?? '') === 'on' ? PHPMailer::ENCRYPTION_STARTTLS : false;

        if ($mail->SMTPAuth) {
            $mail->Username = $env["SMTP_USER"] ?? '';
            $mail->Password = $env["SMTP_PASSWORD"] ?? '';
        }

        $mail->setFrom($env["ADMIN_EMAIL"] ?? 'noreply@example.com', 'Login Server');
        return $mail;
    } catch (Exception $e) {
        file_put_contents("error.log", date("Y-m-d H:i:s") . " MAIL ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
        return null;
    }
}

// Optional: SMTP-Info anzeigen
function debugSMTPEnv() {
    $env = parseEnvFile();
    echo "<pre>";
    echo "SMTP_HOST: " . htmlspecialchars($env["SMTP_HOST"] ?? '') . "\n";
    echo "SMTP_PORT: " . htmlspecialchars($env["SMTP_PORT"] ?? '') . "\n";
    echo "SMTP_SECURE: " . htmlspecialchars($env["SMTP_SECURE"] ?? '') . "\n";
    echo "SMTP_AUTH: " . htmlspecialchars($env["SMTP_AUTH"] ?? '') . "\n";
    echo "SMTP_USER: " . htmlspecialchars($env["SMTP_USER"] ?? '') . "\n";
    echo "ADMIN_EMAIL: " . htmlspecialchars($env["ADMIN_EMAIL"] ?? '') . "\n";
    echo "</pre>";
}
?>
