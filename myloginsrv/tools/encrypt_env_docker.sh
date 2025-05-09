#!/bin/bash
# Datei: encrypt_env_docker.sh – Stand: 2025-04-24 10:53:33 Europe/Berlin

# Aufruf: ./encrypt_env_docker.sh <klartext> <schluessel>

if [ "$#" -ne 2 ]; then
  echo "Verwendung: $0 <klartext> <schluessel>"
  exit 1
fi

PLAINTEXT="$1"
KEY="$2"

docker exec -it myloginsrv-php php /var/www/html/encrypt_env.php "$PLAINTEXT" "$KEY"
