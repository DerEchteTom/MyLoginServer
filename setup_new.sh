#!/bin/bash

# ------------------------ Setup Flags ------------------------
FORCE_INSTALL=false
DEBUG_MODE=false
DEBUG_FILE="./debug.log"
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')

for arg in "$@"; do
  case $arg in
    --debug) DEBUG_MODE=true ;;
    --force) FORCE_INSTALL=true ;;
  esac
done

# ------------------------ Log Functions ------------------------
log_info()    { echo -e "\033[1;34m[INFO]\033[0m $1"; $DEBUG_MODE && echo "$TIMESTAMP [INFO] $1" >> "$DEBUG_FILE"; }
log_warn()    { echo -e "\033[1;33m[WARN]\033[0m $1"; $DEBUG_MODE && echo "$TIMESTAMP [WARN] $1" >> "$DEBUG_FILE"; }
log_success() { echo -e "\033[1;32m[ OK ]\033[0m $1"; $DEBUG_MODE && echo "$TIMESTAMP [ OK ] $1" >> "$DEBUG_FILE"; }
log_error()   { echo -e "\033[1;31m[ERR!]\033[0m $1"; $DEBUG_MODE && echo "$TIMESTAMP [ERR!] $1" >> "$DEBUG_FILE"; }
divider()     { echo -e "\033[1;30m--------------------------------------------------------\033[0m"; }

# ------------------------ Vorbereitung ------------------------
cd "myloginsrv" || { log_error "Directory 'myloginsrv' not found."; exit 1; }
$DEBUG_MODE && echo "$TIMESTAMP DEBUG MODE ENABLED" >> "$DEBUG_FILE"

divider
log_info "Starting Docker containers..."
docker-compose up -d
sleep 3

PHP_CONTAINER=$(docker ps --format '{{.Names}}' | grep php)
if [ -z "$PHP_CONTAINER" ]; then
  log_error "PHP container not found."
  exit 1
fi

divider
log_info "Check and create required files..."
for f in .env .envad; do
  if [ ! -f "$f" ]; then
    cp "$f.example" "$f" && log_success "$f created from example." || log_error "Could not create $f"
  else
    log_success "$f found."
  fi
done

touch audit.log error.log
chmod 664 *.log .env .envad
chown www-data:www-data *.log .env .envad 2>/dev/null
log_success "Permissions set."

divider
log_info "Check init-db.php syntax..."
docker exec "$PHP_CONTAINER" php -l /var/www/html/init-db.php || { log_error "Syntax error in init-db.php"; exit 1; }

divider
log_info "Running init-db.php..."
docker exec "$PHP_CONTAINER" php /var/www/html/init-db.php
if [ $? -eq 0 ]; then
  log_success "Database initialized."
else
  log_error "Database failed."
  exit 1
fi

# ------------------ PHPMailer Installation ------------------
divider
log_info "Checking PHPMailer..."
docker exec "$PHP_CONTAINER" bash -c "[ -d /var/www/html/vendor/phpmailer/phpmailer ]"
if [ $? -ne 0 ] || $FORCE_INSTALL; then
  log_info "Installing PHPMailer..."
  docker exec "$PHP_CONTAINER" bash -c "apt-get update && apt-get install -y php-curl php-zip unzip curl"
  docker exec "$PHP_CONTAINER" bash -c "cd /var/www/html && curl -sS https://getcomposer.org/installer | php && php composer.phar require phpmailer/phpmailer"
  log_success "PHPMailer installed."
else
  log_success "PHPMailer already present."
fi

# ------------------ LDAP Module ------------------
divider
log_info "Checking PHP-LDAP..."
docker exec "$PHP_CONTAINER" php -m | grep -q ldap
if [ $? -ne 0 ] || $FORCE_INSTALL; then
  log_info "Installing PHP-LDAP module..."
  docker exec "$PHP_CONTAINER" bash -c "apt-get update && apt-get install -y libldap2-dev && docker-php-ext-configure ldap && docker-php-ext-install ldap"
  log_success "LDAP module installed."
else
  log_success "LDAP module already present."
fi

# ------------------ Final Overview ------------------
divider
log_info "Setup complete."
echo
echo -e "🌐 Access: \033[1;36mhttp://localhost:8080\033[0m"
INTERNAL_IP=$(hostname -I | awk '{print $1}')
echo -e "🌐 LAN Access: \033[1;36mhttp://$INTERNAL_IP:8080\033[0m"
divider

$DEBUG_MODE && log_success "Debug log written to $DEBUG_FILE"
