#!/bin/bash

[ "$DB_NAME" ]  || DB_NAME='wordpress'
[ "$DB_PASS" ]  || DB_PASS='root'
[ "$THEMES" ]   || THEMES='twentysixteen'
[ "$WP_DEBUG" ] || WP_DEBUG='false'
[ "$WP_DEBUG_LOG" ] || WP_DEBUG_LOG='false'
[ "$WP_DEBUG_DISPLAY" ] || WP_DEBUG_DISPLAY='true'
[ "$ADMIN_EMAIL" ] || ADMIN_EMAIL="admin@${DB_NAME}.com"
[ "$SEARCH_REPLACE" ] && \
  BEFORE_URL=$(echo "$SEARCH_REPLACE" | cut -d ',' -f 1) && \
  AFTER_URL=$(echo "$SEARCH_REPLACE" | cut -d ',' -f 2) || \
  SEARCH_REPLACE=false

ERROR () {
  echo -e "\n=> $(tput -T xterm setaf 1)$(tput -T xterm bold)ERROR$(tput -T xterm sgr 0) (Line $1): $2.";
  exit 1;
}

# Configure wp-cli
# ----------------
cat > /app/wp-cli.yml <<EOF
quiet: true
apache_modules:
  - mod_rewrite

core config:
  dbuser: root
  dbpass: $DB_PASS
  dbname: $DB_NAME
  dbhost: db:3306
  extra-php: |
    define('WP_DEBUG', ${WP_DEBUG,,});
    define('WP_DEBUG_LOG', ${WP_DEBUG_LOG,,});
    define('WP_DEBUG_DISPLAY', ${WP_DEBUG_DISPLAY,,});

core install:
  url: $([ "$AFTER_URL" ] && echo "$AFTER_URL" || echo localhost:8080)
  title: $DB_NAME
  admin_user: root
  admin_password: $DB_PASS
  admin_email: $ADMIN_EMAIL
  skip-email: true
EOF


# Download WordPress
# ------------------
if [ ! -f /app/wp-settings.php ]; then
  printf "=> Downloading wordpress... "
  chown -R www-data:www-data /app /var/www/html
  sudo -u www-data wp core download >/dev/null 2>&1 || \
    ERROR $LINENO "Failed to download wordpress"
  printf "Done!\n"
fi


# Wait for MySQL
# --------------
printf "=> Waiting for MySQL to initialize... \n"
while ! mysqladmin ping --host=db --password=$DB_PASS --silent; do
  sleep 1
done


printf "\t%s\n" \
  "=======================================" \
  "    Begin WordPress Configuration" \
  "======================================="


# wp-config.php
# -------------
printf "=> Generating wp.config.php file... "
rm -f /app/wp-config.php
sudo -u www-data wp core config >/dev/null 2>&1 || \
  ERROR $LINENO "Could not generate wp-config.php file"
printf "Done!\n"

# Setup database
# --------------
printf "=> Create database '%s'... " "$DB_NAME"
if [ ! "$(wp core is-installed --allow-root >/dev/null 2>&1 && echo $?)" ]; then
  sudo -u www-data wp db create >/dev/null 2>&1 || \
    ERROR $LINENO "Database creation failed"
  printf "Done!\n"

  # If an SQL file exists in /data => load it
  if [ "$(stat -t /data/*.sql >/dev/null 2>&1 && echo $?)" ]; then
    DATA_PATH=$(find /data/*.sql | head -n 1)
    printf "=> Loading data backup from %s... " "$DATA_PATH"
    sudo -u www-data wp db import "$DATA_PATH" >/dev/null 2>&1 || \
      ERROR $LINENO "Could not import database"
    printf "Done!\n"

    # If SEARCH_REPLACE is set => Replace URLs
    if [ "$SEARCH_REPLACE" != false ]; then
      printf "=> Replacing URLs... "
      REPLACEMENTS=$(sudo -u www-data wp search-replace "$BEFORE_URL" "$AFTER_URL" \
        --no-quiet --skip-columns=guid | grep replacement) || \
        ERROR $((LINENO-2)) "Could not execute SEARCH_REPLACE on database"
      echo -ne "$REPLACEMENTS\n"
    fi
  else
    printf "=> No database backup found. Initializing new database... "
    sudo -u www-data wp core install >/dev/null 2>&1 || \
      ERROR $LINENO "WordPress Install Failed"
    printf "Done!\n"
  fi
else
  printf "Already exists!\n"
fi


# .htaccess
# ---------
if [ ! -f /app/.htaccess ]; then
  printf "=> Generating .htaccess file... "
  sudo -u www-data wp rewrite flush --hard >/dev/null 2>&1 || \
    ERROR $LINENO "Could not generate .htaccess file"
  printf "Done!\n"
else
  printf "=> .htaccess exists. SKIPPING...\n"
fi


# Filesystem Permissions
# ----------------------
printf "=> Adjusting filesystem permissions... "
groupadd -f docker && usermod -aG docker www-data
find /app -type d -exec chmod 755 {} \;
find /app -type f -exec chmod 644 {} \;
mkdir -p /app/wp-content/uploads
chmod -R 775 /app/wp-content/uploads && \
  chown -R :docker /app/wp-content/uploads
printf "Done!\n"


# Install Plugins
# ---------------
if [ "$PLUGINS" ]; then
  printf "=> Checking plugins...\n"
  while IFS=',' read -ra plugin; do
    for i in "${!plugin[@]}"; do
      plugin_name=$(echo "${plugin[$i]}" | xargs)
      sudo -u www-data wp plugin is-installed "${plugin_name}"
      if [ $? -eq 0 ]; then
        printf "=> ($((i+1))/${#plugin[@]}) Plugin '%s' found. SKIPPING...\n" "${plugin_name}"
      else
        printf "=> ($((i+1))/${#plugin[@]}) Plugin '%s' not found. Installing...\n" "${plugin_name}"
        sudo -u www-data wp plugin install "${plugin_name}"
      fi
    done
  done <<< "$PLUGINS"
else
  printf "=> No plugin dependencies listed. SKIPPING...\n"
fi


# Operations to perform on first build
# ------------------------------------
if [ -d /app/wp-content/plugins/akismet ]; then
  printf "=> Removing default plugins... "
  sudo -u www-data wp plugin uninstall akismet hello --deactivate
  printf "Done!\n"

  printf "=> Removing unneeded themes... "
  REMOVE_LIST=(twentyfourteen twentyfifteen twentysixteen)
  THEME_LIST=()
  while IFS=',' read -ra theme; do
    for i in "${!theme[@]}"; do
      REMOVE_LIST=( "${REMOVE_LIST[@]/${theme[$i]}}" )
      THEME_LIST+="${theme[$i]}"
    done
    sudo -u www-data wp theme delete "${REMOVE_LIST[@]}"
  done <<< $THEMES
  printf "Done!\n"

  printf "=> Installing needed themes... "
  sudo -u www-data wp theme install "${THEME_LIST[@]}"
  printf "Done!\n"
fi


printf "\t%s\n" \
  "=======================================" \
  "   WordPress Configuration Complete!" \
  "======================================="


# Start apache
# ------------

rm -f /var/run/apache2/apache2.pid
source /etc/apache2/envvars
exec apache2 -D FOREGROUND
