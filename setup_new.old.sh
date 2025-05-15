#!/bin/bash
# Datei: setup.sh – Version 2025-05-15_final_debuglog

# Farbcodes
RED='\e[31m'
GRN='\e[32m'
YEL='\e[33m'
BLU='\e[36m'
NC='\e[0m'

# Flags
FORCE=false
DEBUG=false
DEBUG_FILE="debug-$(date +%F).log"

# Argumentverarbeitung
for arg in "$@"; do
  case $arg in
    --force) FORCE=true ;;
    --debug) DEBUG=true ;;
  esac
done

# Logging-Funktionen
log_info()     { echo -e "${BLU}[INFO]${NC} $1"; $DEBUG && echo "[INFO] $(date -Is) $1" >> "$DEBUG_FILE"; }
log_success()  { echo -e "${GRN}[OK]${NC}   $1"; $DEBUG && echo "[OK] $(date -Is) $1" >> "$DEBUG_FILE"; }
log_warn()     { echo -e "${YEL}[WARN]${NC} $1"; $DEBUG && echo "[WARN] $(date -Is) $1" >> "$DEBUG_FILE"; }
log_error()    { echo -e "${RED}[FAIL]${NC} $1"; $DEBUG && echo "[FAIL] $(date -Is) $1" >> "$DEBUG_FILE"; }

divider()      { echo -e "${BLU}----------------------------------------${NC}"; }

# Zielverzeichnis
TARGET_DIR="myloginsrv"
cd "$TARGET_DIR" || { log_error "Directory '$TARGET_DIR' not found."; exit 1; }

divider
log_info "Starting Docker containers ..."
docker-compose up -d
sleep 3

# PHP Container suchen
PHP_CONTAINER=$(docker ps --format '{{.Names}}' | grep php)
if [ -z "$PHP_CONTAINER" ]; then
  log_error "PHP container not found. Aborting."
  exit 1
fi

log_success "PHP container: $PHP_CONTAINER"
divider
log_info "Checking .env and .envad presence ..."

docker exec "$PHP_CONTAINER" bash -c "test -f /var/www/html/.env || cp /var/www/html/.env.example /var/www/html/.env"
docker exec "$PHP_CONTAINER" bash -c "test -f /var/www/html/.envad || cp /var/www/html/.envad.example /var/www/html/.envad"

log_success ".env and .envad ensured"

divider
log_info "Checking init-db.php syntax ..."
docker exec "$PHP_CONTAINER" php -l /var/www/html/init-db.php || {
  log_error "init-db.php has syntax errors"
  exit 1
}

divider
log_info "Running init-db.php ..."
docker exec "$PHP_CONTAINER" php /var/www/html/init-db.php && log_success "Database initialized"

divider
log_info "Preparing logs and permissions ..."
docker exec "$PHP_CONTAINER" bash -c "touch /var/www/html/audit.log /var/www/html/error.log"
docker exec "$PHP_CONTAINER" bash -c "chmod 666 /var/www/html/*.log /var/www/html/.env /var/www/html/.envad"
docker exec "$PHP_CONTAINER" bash -c "chown www-data:www-data /var/www/html/*.log /var/www/html/.env /var/www/html/.envad"
log_success "Log files ready and permissions set"

divider
log_info "Checking PHP-LDAP module ..."
docker exec "$PHP_CONTAINER" php -m | grep -iq ldap
if [ $? -eq 0 ] && [ "$FORCE" = false ]; then
  log_success "PHP-LDAP already installed"
else
  log_info "Installing PHP-LDAP in container ..."
  docker exec "$PHP_CONTAINER" bash -c "
    apt-get update -qq &&
    apt-get install -yqq libldap2-dev &&
    docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu &&
    docker-php-ext-install ldap
  " && log_success "PHP-LDAP installed"
fi

divider
log_info "Checking PHPMailer ..."
docker exec "$PHP_CONTAINER" test -d /var/www/html/vendor/phpmailer/phpmailer
if [ $? -ne 0 ]; then
  if [ "$FORCE" = true ]; then
    log_info "Installing PHPMailer ..."
    docker exec "$PHP_CONTAINER" bash -c "
      apt-get install -yqq php-curl php-zip unzip curl &&
      curl -sS https://getcomposer.org/installer | php &&
      php composer.phar require phpmailer/phpmailer
    " && log_success "PHPMailer installed"
  else
    read -p "PHPMailer not found. Install now? (y/n): " answer
    if [[ "$answer" == "y" ]]; then
      docker exec "$PHP_CONTAINER" bash -c "
        apt-get install -yqq php-curl php-zip unzip curl &&
        curl -sS https://getcomposer.org/installer | php &&
        php composer.phar require phpmailer/phpmailer
      " && log_success "PHPMailer installed"
    else
      log_warn "PHPMailer skipped"
    fi
  fi
else
  log_success "PHPMailer already present"
fi

divider
log_info "Module summary:"

# LDAP-Status prüfen
docker exec "$PHP_CONTAINER" php -m | grep -iq ldap
[ $? -eq 0 ] && log_success "LDAP module: ready" || log_warn "LDAP module: missing"

# PHPMailer prüfen
docker exec "$PHP_CONTAINER" test -d /var/www/html/vendor/phpmailer/phpmailer
[ $? -eq 0 ] && log_success "PHPMailer: installed" || log_warn "PHPMailer: missing"

# .env prüfen
docker exec "$PHP_CONTAINER" test -f /var/www/html/.env && log_success ".env file: present" || log_warn ".env file: missing"
docker exec "$PHP_CONTAINER" test -f /var/www/html/.envad && log_success ".envad file: present" || log_warn ".envad file: missing"

# audit.log prüfen
docker exec "$PHP_CONTAINER" test -f /var/www/html/audit.log && log_success "audit.log: present" || log_warn "audit.log: missing"
docker exec "$PHP_CONTAINER" test -f /var/www/html/error.log && log_success "error.log: present" || log_warn "error.log: missing"

# Schreibrecht prüfen
docker exec "$PHP_CONTAINER" bash -c 'echo test > /var/www/html/.test_write && rm /var/www/html/.test_write' >/dev/null 2>&1
[ $? -eq 0 ] && log_success "Filesystem: writable" || log_warn "Filesystem: write error"

divider
log_success "All checks complete. Setup ready to use."


divider
log_info "Setup complete."
INTERNAL_IP=$(hostname -I | awk '{print $1}')
echo -e "${GRN}Access your login server at: http://${INTERNAL_IP}:8080${NC}"
echo -e "Debug log (if enabled): ${DEBUG_FILE}"
