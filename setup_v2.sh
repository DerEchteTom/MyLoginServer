#!/bin/bash
# setup.sh – Optimiert und strukturiert – Stand: 2025-05-14 Europe/Berlin

TARGET_DIR="myloginsrv"
IMPORT_FILE="import_users.json"

cd "$TARGET_DIR" || { echo "❌ Verzeichnis $TARGET_DIR nicht gefunden."; exit 1; }

echo "----------------------------------------"
echo "🔧 [1/9] Starte Docker-Container ..."
docker-compose up -d
sleep 4

PHP_CONTAINER=$(docker ps --format '{{.Names}}' | grep php)
if [ -z "$PHP_CONTAINER" ]; then
  echo "❌ PHP-Container konnte nicht gefunden werden."
  exit 1
fi

echo "----------------------------------------"
echo "🧹 [2/9] PHP-Sessions löschen (optional) ..."
if [ "$RESET_SESSIONS" = true ]; then
  docker exec "$PHP_CONTAINER" bash -c 'rm -f /tmp/sess_* || true'
  timestamp=$(date -Iseconds)
  docker exec "$PHP_CONTAINER" bash -c "echo '$timestamp 🧹 Alle PHP-Sessions gelöscht (setup.sh)' >> /var/www/html/audit.log"
fi

echo "----------------------------------------"
echo "📜 [3/9] Zertifikate installieren ..."
docker exec "$PHP_CONTAINER" bash -c "apt-get update && apt-get install -y ca-certificates"

echo "----------------------------------------"
echo "✅ [4/9] Syntaxprüfung init-db.php ..."
docker exec "$PHP_CONTAINER" php -l /var/www/html/init-db.php || { echo "❌ Syntaxfehler in init-db.php. Abbruch."; exit 1; }

echo "----------------------------------------"
echo "🛠️ [5/9] Datenbank initialisieren ..."
docker exec "$PHP_CONTAINER" php /var/www/html/init-db.php || { echo "❌ DB-Initialisierung fehlgeschlagen."; exit 1; }

echo "----------------------------------------"
echo "📁 [6/9] audit.log und error.log anlegen ..."
docker exec "$PHP_CONTAINER" bash -c "touch /var/www/html/audit.log /var/www/html/error.log"

echo "----------------------------------------"
echo "🔐 [7/9] Dateiberechtigungen setzen ..."
docker exec "$PHP_CONTAINER" bash -c "
  chown -R www-data:www-data /var/www/html &&
  chmod -R 777 /var/www/html &&
  touch /var/www/html/.env &&
  chmod 664 /var/www/html/.env &&
  chown www-data:www-data /var/www/html/.env"

echo "----------------------------------------"
echo "📤 [8/9] Git & PHPMailer prüfen/installieren ..."
docker exec "$PHP_CONTAINER" bash -c "command -v git >/dev/null || apt-get install -y git"
docker exec "$PHP_CONTAINER" bash -c "[ -d /var/www/html/vendor/phpmailer/phpmailer ]" || {
  echo "📧 PHPMailer nicht gefunden. Installation wird versucht ..."
  docker exec "$PHP_CONTAINER" bash -c "apt-get update && apt-get install -y php-curl php-zip php-openssl unzip curl"
  docker exec "$PHP_CONTAINER" bash -c "cd /var/www/html && curl -sS https://getcomposer.org/installer | php && php composer.phar require phpmailer/phpmailer"
}

echo "----------------------------------------"
echo "🧪 [9/9] Systemprüfung ..."
docker exec "$PHP_CONTAINER" bash -c "php -v | head -n1"
[ -f crypt.php ] && echo "✅ crypt.php gefunden" || echo "⚠️ crypt.php fehlt"
[ -f .envad ] && echo "✅ .envad gefunden" || echo "❌ .envad fehlt"
docker ps --filter "name=myloginsrv-php" --format "{{.Status}}" || echo "❌ PHP-Container nicht aktiv?"
docker ps --filter "name=myloginsrv-nginx" --format "{{.Status}}" || echo "❌ NGINX-Container nicht aktiv?"

echo "----------------------------------------"
echo "🌐 Zugriff lokal:     http://localhost:8080"
echo "🌐 Zugriff im LAN:    http://$(hostname -I | awk '{print $1}'):8080"
echo "✅ Setup abgeschlossen."
