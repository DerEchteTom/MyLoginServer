#!/bin/bash

TARGET_DIR="myloginsrv"

cd "$TARGET_DIR" || { echo "Verzeichnis $TARGET_DIR nicht gefunden."; exit 1; }

echo "Starte Docker-Container ..."
docker-compose up -d
sleep 3

PHP_CONTAINER=$(docker ps --format '{{.Names}}' | grep php)
if [ -z "$PHP_CONTAINER" ]; then
  echo "PHP-Container konnte nicht gefunden werden."
  exit 1
fi

echo "Initialisiere Datenbank ..."
docker exec "$PHP_CONTAINER" php /var/www/html/init-db.php || { echo "Datenbankinitialisierung fehlgeschlagen."; exit 1; }

echo "Lege audit.log-Datei an (falls noch nicht vorhanden) ..."
docker exec "$PHP_CONTAINER" touch /var/www/html/audit.log

echo "Lege error.log-Datei an (falls noch nicht vorhanden) ..."
docker exec "$PHP_CONTAINER" touch /var/www/html/error.log

echo "Setze Dateiberechtigungen für users.db, audit.log und Arbeitsverzeichnis ..."
docker exec "$PHP_CONTAINER" chown -R www-data:www-data /var/www/html

docker exec "$PHP_CONTAINER" chmod -R 777 /var/www/html

echo "Setze Rechte für .env-Datei ..."
docker exec "$PHP_CONTAINER" bash -c "touch /var/www/html/.env && chmod 664 /var/www/html/.env && chown www-data:www-data /var/www/html/.env"

echo "Führe SQLite-Schreibtest durch ..."
docker exec "$PHP_CONTAINER" php /var/www/html/test.php || echo "Schreibtest fehlgeschlagen"

# Prüfe Git-Installation im Container
docker exec "$PHP_CONTAINER" bash -c "command -v git >/dev/null 2>&1"
if [ $? -ne 0 ]; then
  echo "Git ist nicht installiert. Installiere Git ..."
  docker exec "$PHP_CONTAINER" bash -c "apt-get update && apt-get install -y git"
  echo "Git wurde installiert."
else
  echo "Git ist bereits vorhanden."
fi

# Prüfe PHPMailer-Installation und Composer-Abhängigkeiten
echo "Prüfe PHPMailer-Installation im Container ..."
docker exec "$PHP_CONTAINER" bash -c "[ -d /var/www/html/vendor/phpmailer/phpmailer ]"
if [ $? -ne 0 ]; then
  read -p "PHPMailer ist nicht installiert. Jetzt installieren? (y/n): " install_phpmailer
  if [ "$install_phpmailer" = "y" ]; then
    echo "Prüfe erforderliche PHP-Erweiterungen ..."
    docker exec "$PHP_CONTAINER" bash -c "apt-get update && apt-get install -y php-curl php-zip php-openssl unzip curl"
    echo "Installiere Composer & PHPMailer im Container ..."
    docker exec "$PHP_CONTAINER" bash -c "cd /var/www/html && curl -sS https://getcomposer.org/installer | php && php composer.phar require phpmailer/phpmailer"
    echo "PHPMailer erfolgreich installiert."
  else
    echo "PHPMailer wurde übersprungen. Mailfunktionen sind deaktiviert."
  fi
else
  echo "PHPMailer ist bereits vorhanden."
fi

IP=$(hostname -I | awk '{print $1}')
echo "Setup abgeschlossen. Zugriff unter: http://$IP:8080"
