#!/bin/bash

#ZIPFILE="myloginsrv-flat-complete.zip"
TARGET_DIR="myloginsrv"

#if [ ! -f "$ZIPFILE" ]; then
#  echo "❌ Fehler: $ZIPFILE nicht gefunden im aktuellen Verzeichnis."
#  exit 1
#fi

#echo "📦 Entpacke $ZIPFILE ..."
#unzip -o "$ZIPFILE" -d .

cd "$TARGET_DIR" || { echo "❌ Verzeichnis $TARGET_DIR nicht gefunden."; exit 1; }

echo "🚀 Starte Docker-Container ..."
docker-compose up -d

echo "⏳ Warte auf PHP-Container ..."
sleep 5
PHP_CONTAINER=$(docker ps --format '{{.Names}}' | grep myloginsrv-php)

if [ -z "$PHP_CONTAINER" ]; then
  echo "❌ PHP-Container konnte nicht gestartet werden!"
  exit 1
fi

echo "✅ Container läuft: $PHP_CONTAINER"

echo "🔧 Setze Dateirechte ..."
docker exec "$PHP_CONTAINER" sh -c 'chmod -R 775 /var/www/html && chown -R www-data:www-data /var/www/html'

if docker exec "$PHP_CONTAINER" test -f /var/www/html/init-db.php; then
  echo "🛠️  Initialisiere Datenbank ..."
  docker exec "$PHP_CONTAINER" php /var/www/html/init-db.php
else
  echo "❌ init-db.php nicht im Container gefunden!"
fi

IP=$(hostname -I | awk '{print $1}')
echo "✅ Setup abgeschlossen. Zugriff unter: http://$IP"
