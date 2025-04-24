#!/bin/bash

TARGET_DIR="myloginsrv"
IMPORT_FILE="import_users.json"

cd "$TARGET_DIR" || { echo "❌ Verzeichnis $TARGET_DIR nicht gefunden."; exit 1; }

echo "🔧 Starte Docker-Container ..."
docker-compose up -d
sleep 4

PHP_CONTAINER=$(docker ps --format '{{.Names}}' | grep php)
if [ -z "$PHP_CONTAINER" ]; then
  echo "❌ PHP-Container konnte nicht gefunden werden."
  exit 1
fi


if [ "$RESET_SESSIONS" = true ]; then
  echo "🚫 Lösche alle PHP-Sessions im Container ..."
  docker exec "$PHP_CONTAINER" bash -c 'rm -f /tmp/sess_* || true'
  docker exec "$PHP_CONTAINER" bash -c "echo \"$(date -Iseconds) 🧹 Alle PHP-Sessions gelöscht (setup.sh)\" >> /var/www/html/audit.log"
fi

# Stelle sicher, dass CA-Zertifikate installiert sind
echo "Installiere Zertifikatsunterstützung im Container ..."
docker exec "$PHP_CONTAINER" bash -c "apt-get update && apt-get install -y ca-certificates"


# Syntaxcheck init-db.php
echo "✅ PHP-Datei 'init-db.php' vor Ausführung prüfen ..."
docker exec "$PHP_CONTAINER" php -l /var/www/html/init-db.php || {
  echo "❌ Syntaxfehler in init-db.php. Abbruch."
  exit 1
}

echo "🚀 Führe init-db.php aus ..."
docker exec "$PHP_CONTAINER" php /var/www/html/init-db.php || { echo "❌ Datenbankinitialisierung fehlgeschlagen."; exit 1; }

# Logdateien
echo "📄 Lege audit.log und error.log an (falls nicht vorhanden) ..."
docker exec "$PHP_CONTAINER" touch /var/www/html/audit.log /var/www/html/error.log

# Rechte
echo "🔐 Setze Dateiberechtigungen ..."
docker exec "$PHP_CONTAINER" chown -R www-data:www-data /var/www/html
docker exec "$PHP_CONTAINER" chmod -R 777 /var/www/html
docker exec "$PHP_CONTAINER" bash -c "touch /var/www/html/.env && chmod 664 /var/www/html/.env && chown www-data:www-data /var/www/html/.env"

# SQLite-Test
echo "🧪 Führe SQLite-Schreibtest durch ..."
docker exec "$PHP_CONTAINER" php /var/www/html/test.php || echo "⚠️ Schreibtest fehlgeschlagen."

# ADMIN_EMAIL ergänzen
docker exec "$PHP_CONTAINER" bash -c "
  grep -q '^ADMIN_EMAIL=' /var/www/html/.env || echo 'ADMIN_EMAIL=admin@example.com' >> /var/www/html/.env
"

# .env.example schreiben/überschreiben
cat > .env.example <<EOF
SMTP_HOST=smtp.example.com
SMTP_PORT=25
SMTP_FROM=noreply@example.com
SMTP_SECURE=off
SMTP_USERNAME
SMTP_AUTH=off
ADMIN_EMAIL=admin@example.com
EOF

# Git prüfen
echo.
docker exec "$PHP_CONTAINER" bash -c "command -v git >/dev/null 2>&1"
if [ $? -ne 0 ]; then
  echo "📦 Git wird installiert ..."
  docker exec "$PHP_CONTAINER" bash -c "apt-get update && apt-get install -y git"
else
  echo "✅ Git ist bereits installiert."
fi

# PHPMailer
docker exec "$PHP_CONTAINER" bash -c "[ -d /var/www/html/vendor/phpmailer/phpmailer ]"
if [ $? -ne 0 ]; then
  read -p "📧 PHPMailer nicht gefunden. Jetzt installieren? (y/n): " install_phpmailer
  if [ "$install_phpmailer" = "y" ]; then
    docker exec "$PHP_CONTAINER" bash -c "apt-get update && apt-get install -y php-curl php-zip php-openssl unzip curl"
    docker exec "$PHP_CONTAINER" bash -c "cd /var/www/html && curl -sS https://getcomposer.org/installer | php && php composer.phar require phpmailer/phpmailer"
    echo "✅ PHPMailer installiert."
  else
    echo "⚠️ PHPMailer-Installation übersprungen."
  fi
else
  echo "✅ PHPMailer ist bereits installiert."
fi

# Benutzerimport aus JSON-Datei
if [ -f "$IMPORT_FILE" ]; then
  read -p "👤 Benutzer aus '$IMPORT_FILE' importieren? (y/n): " import_confirm
  if [ "$import_confirm" = "y" ]; then
    echo "📥 Importiere Benutzer..."
    docker cp "$IMPORT_FILE" "$PHP_CONTAINER":/var/www/html/import_users.json
    docker exec "$PHP_CONTAINER" php -r "
      \$_FILES = ['import_file' => ['tmp_name' => 'import_users.json', 'error' => 0]];
      include 'admin_userimport.php';
    "
  else
    echo "⏭️ Benutzerimport übersprungen."
  fi
else
  echo "ℹ️ Keine Importdatei '$IMPORT_FILE' gefunden – übersprungen."
fi

echo "📦 Aktuelle .env-Konfiguration:"
docker exec "$PHP_CONTAINER" bash -c "cat /var/www/html/.env | grep -E '^SMTP_|^ADMIN_EMAIL=' || echo 'Keine .env gefunden.'"

IP=$(hostname -I | awk '{print $1}')
echo "✅ Setup abgeschlossen. Zugriff unter: http://$IP:8080"
