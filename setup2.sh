#!/bin/bash

TARGET_DIR="myloginsrv"

cd "$TARGET_DIR" || { echo "❌ Verzeichnis $TARGET_DIR nicht gefunden."; exit 1; }

echo "🚀 Starte Docker-Container ..."
docker-compose up -d
sleep 3

PHP_CONTAINER=$(docker ps --format '{{.Names}}' | grep php)
if [ -z "$PHP_CONTAINER" ]; then
  echo "❌ PHP-Container konnte nicht gefunden werden."
  exit 1
fi

echo "🔧 Initialisiere Datenbank ..."
docker exec "$PHP_CONTAINER" php /var/www/html/init-db.php || { echo "❌ Datenbankinitialisierung fehlgeschlagen."; exit 1; }

chmod -R 755 .

IP=$(hostname -I | awk '{print $1}')
echo "✅ Setup abgeschlossen. Zugriff unter: http://$IP:8080"
