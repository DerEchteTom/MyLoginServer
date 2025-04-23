<?php
// Datei: mailer_config.php – Stand: 2025-04-23 06:53 Europe/Berlin

require_once __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function parseEnvFile($file) {
    if (!file_exists($file)) return;
    foreach (file($file) as $line) {
        if (strpos(trim($line), '=') !== false) {
            list($key, $val) = explode('=', trim($line), 2);
            putenv(trim($key) . '=' . trim($val));
        }
    }
}
parseEnvFile(__DIR__ . '/.env');

function debugSMTPEnv() {
    return [
        'SMTP_HOST'   => getenv('SMTP_HOST'),
        'SMTP_PORT'   => getenv('SMTP_PORT'),
        'SMTP_FROM'   => getenv('SMTP_FROM'),
        'SMTP_SECURE' => getenv('SMTP_SECURE'),
        'SMTP_AUTH'   => getenv('SMTP_AUTH'),
        'SMTP_USER'   => getenv('SMTP_USER'),
        'SMTP_PASS'   => getenv('SMTP_PASS'),
        'ADMIN_EMAIL' => getenv('ADMIN_EMAIL')
    ];
}

function getMailer(string $to, string $subject): ?PHPMailer {
    $host   = getenv('SMTP_HOST');
    $port   = getenv('SMTP_PORT');
    $from   = getenv('SMTP_FROM');
    $secure = strtolower(getenv('SMTP_SECURE') ?? '');
    $auth   = strtolower(getenv('SMTP_AUTH') ?? '') === 'on';

    if (!in_array($secure, ['', 'ssl', 'tls'])) {
        file_put_contents("error.log", date('c') . " ⚠️ Ungültiger Wert für SMTP_SECURE in .env: $secure\n", FILE_APPEND);
        $secure = '';
    }

    if (!$host || !$port || !$from) {
        file_put_contents("error.log", date('c') . " ❌ SMTP-Parameter unvollständig – Mailversand abgebrochen\n", FILE_APPEND);
        return null;
    }

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->Port = $port;
        $mail->setFrom($from);
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->isHTML(false);

        if ($secure) {
            $mail->SMTPSecure = $secure;
        }

        $mail->SMTPAuth = $auth;
        if ($auth) {
            $user = getenv('SMTP_USER');
            $pass = getenv('SMTP_PASS');
            if ($user && $pass) {
                $mail->Username = $user;
                $mail->Password = $pass;
            }
        }

        return $mail;
    } catch (Exception $e) {
        file_put_contents("error.log", date('c') . " ❌ Fehler beim Initialisieren von PHPMailer: " . $e->getMessage() . "\n", FILE_APPEND);
        return null;
    }
}
