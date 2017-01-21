#!/bin/bash

# Runtime
# --------
export TERM=${TERM:-xterm}
VERBOSE=${VERBOSE:-false}

# Environment
# ------------
DB_HOST=${DB_HOST:-'db'}
DB_NAME=${DB_NAME:-'wordpress'}
DB_PASS=${DB_PASS:-'root'}
DB_PREFIX=${DB_PREFIX:-'wp_'}
SERVER_NAME=${SERVER_NAME:-'localhost'}
ADMIN_EMAIL=${ADMIN_EMAIL:-"admin@${DB_NAME}.com"}
PERMALINKS=${PERMALINKS:-'/%year%/%monthnum%/%postname%/'}
WP_DEBUG_DISPLAY=${WP_DEBUG_DISPLAY:-'true'}
WP_DEBUG_LOG=${WB_DEBUG_LOG:-'false'}
WP_DEBUG=${WP_DEBUG:-'false'}
WP_VERSION=${WP_VERSION:-'latest'}
[ "$SEARCH_REPLACE" ] && \
  BEFORE_URL=$(echo "$SEARCH_REPLACE" | cut -d ',' -f 1) && \
  AFTER_URL=$(echo "$SEARCH_REPLACE" | cut -d ',' -f 2) || \
  SEARCH_REPLACE=false

# WP-CLI configuration
# ---------------------
cat > /app/wp-cli.yml <<EOF
apache_modules:
  - mod_rewrite

core config:
  dbuser: root
  dbpass: $DB_PASS
  dbname: $DB_NAME
  dbprefix: $DB_PREFIX
  dbhost: $DB_HOST:3306
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

# Apache configuration
# --------------------
sed -i "s/#ServerName www.example.com/ServerName $SERVER_NAME/" /etc/apache2/sites-available/000-default.conf

main() {
  h1 "Begin WordPress Installation"

  # Download WordPress
  # ------------------
  if [ ! -f /app/wp-settings.php ]; then
    h2 "Installing WordPress"
    h3 "Downloading..."
    chown -R www-data:www-data /app /var/www/html
    WP core download --version="$WP_VERSION" |& loglevel
    STATUS "${PIPESTATUS[0]}"
  fi

  # Wait for MySQL
  # --------------
  h2 "Waiting for MySQL to initialize..."
  printf "%b " "${CYAN}${BOLD}  ->${NC} "
  while ! mysqladmin ping --host="$DB_HOST" --password="$DB_PASS" --silent; do
    sleep 1
  done

  h2 "Configuring WordPress"
  h3 "Generating wp-config.php file..."
  rm -f /app/wp-config.php
  WP core config |& loglevel
  STATUS "${PIPESTATUS[0]}"

  h2 "Checking database"
  check_database

  # Make multisite
  # NOTE: This will likely cause issues down the road.
  #       Multisite should ideally be a completely separate build.
  # ---------------
  h2 "Checking for multisite"
  if [ "$MULTISITE" == "true" ]; then
    h3 "Multisite found. Enabling..."
    WP core multisite-convert |& loglevel
    STATUS "${PIPESTATUS[0]}"
  else
    h3 "Multisite not found. SKIPPING..."
    STATUS SKIP
  fi

  h2 "Checking themes"
  check_themes

  h2 "Checking plugins"
  check_plugins

  h2 "Finalizing"
  if [ ! -f /app/.htaccess ]; then
    h3 "Generating .htaccess file"
    if [[ "$MULTISITE" == 'true' ]]; then
      STATUS 1
      h3warn "Cannot generate .htaccess for multisite!"
    else
      WP rewrite structure "$PERMALINKS" |& loglevel
      WP rewrite flush --hard |& loglevel
      STATUS "${PIPESTATUS[0]}"
    fi
  else
    h3 ".htaccess exists. SKIPPING..."
    STATUS SKIP
  fi

  h3 "Adjusting file permissions"
  groupadd -f docker && usermod -aG docker www-data
  find /app -type d -exec chmod 755 {} \;
  find /app -type f -exec chmod 644 {} \;
  mkdir -p /app/wp-content/uploads
  chmod -R 775 /app/wp-content/uploads && \
    chown -R :docker /app/wp-content/uploads
  STATUS $?

  h1 "WordPress Configuration Complete!"

  rm -f /var/run/apache2/apache2.pid
  source /etc/apache2/envvars
  exec apache2 -D FOREGROUND
}


check_database() {
  WP core is-installed |& loglevel
  if [ "${PIPESTATUS[0]}" == '1' ]; then
    h3 "Creating database $DB_NAME"
    WP db create |& loglevel
    STATUS "${PIPESTATUS[0]}"

    # If an SQL file exists in /data => load it
    if [[ "$(find /data -name '*.sql' 2>/dev/null | wc -l)" != "0" ]]; then
      DATA_PATH=$(find /data/*.sql | head -n 1)
      h3 "Loading data backup from $DATA_PATH"

      WP db import "$DATA_PATH" |& loglevel
      STATUS "${PIPESTATUS[0]}"

      # If SEARCH_REPLACE is set => Replace URLs
      if [ "$SEARCH_REPLACE" != false ]; then
        h3 "Replacing URLs"
        REPLACEMENTS=$(WP search-replace "$BEFORE_URL" "$AFTER_URL" \
          --skip-columns=guid | grep replacement) || \
          ERROR $((LINENO-2)) "Could not execute SEARCH_REPLACE on database"
        echo -ne "$REPLACEMENTS\n"
      fi
    else
      h3 "No database backup found. Initializing new database"
      WP core install |& loglevel
      STATUS "${PIPESTATUS[0]}"
    fi
  else
    h3 "Database exists. SKIPPING..."
    STATUS SKIP
  fi
}


check_themes() {
  declare -A themes
  local -i theme_count=0
  local -i i=1
  local theme_name
  local theme_url

  # If $THEMES is not set => prune all existing themes
  if [[ ! "${THEMES-}" ]]; then
    h3 "No theme dependencies listed"
    STATUS SKIP
    h2 "Checking for orphaned themes"
    while read -r theme_name; do
      if [[ "$theme_name" == 'twentyseventeen' ]]; then continue; fi
      h3 "'$theme_name' no longer needed. Pruning"
      WP theme delete --quiet "$theme_name"
      STATUS $?
    done <<< "$(WP theme list --field=name)"
    return
  fi

  # Correct for cases where user forgets to add trailing comma
  [[ "${THEMES:(-1)}" != ',' ]] && THEMES+=','

  # Set $theme_count to the total number of themes set in $THEMES
  while read -r -d,; do ((theme_count++)); done <<< "$THEMES"

  # Iterate over each theme set in $THEMES
  while read -r -d, theme_name; do
    theme_url=  # reset to null

    # If $theme_name matches a URL using the old format => attempt to install it and continue
    if [[ $theme_name =~ ^https?://[www]?.+ ]]; then
      h3warn "$theme_name"
      h3warn "Can't check if theme is already installed using above format!"
      h3warn "Switch your compose file to '[theme-slug]http://themeurl.com/themefile.zip' for better checks"
      h3 "($i/$theme_count) '$theme_name' not found. Installing"
      WP theme install --quiet "$theme_name"
      STATUS $?
      ((i++))
      continue
    fi

    # Locally volumed themes
    if [[ $theme_name =~ ^\[local\] ]]; then
      themes["${theme_name##*]}"]="${theme_name##*]}"
      h3 "($i/$theme_count) '${theme_name##*]}' listed as a local volume. SKIPPING..."
      STATUS SKIP
      ((i++))
      continue
    fi

    # If $theme_name matches a URL using the new format => set $theme_name & $theme_url
    if [[ $theme_name =~ ^\[.+\]https?://[www]?.+ ]]; then
      theme_url=${theme_name##\[*\]}
      theme_name="$(echo "$theme_name" | grep -oP '\[\K(.+)(?=\])')"
    fi

    theme_url=${theme_url:-$theme_name}

    if WP theme is-installed "$theme_name"; then
      h3 "($i/$theme_count) '$theme_name' found. SKIPPING..."
      STATUS SKIP
    else
      h3 "($i/$theme_count) '$theme_name' not found. Installing"
      WP theme install --quiet "$theme_url"
      STATUS $?
    fi

    # Make sure the first listed theme is active so that others can be removed
    if [[ $i == 1 && $(WP theme status "$theme_name" | grep -Po 'Status.+' | awk '{print $2}') != 'Active' ]]; then
      h3 "Activating '$theme_name'"
      WP theme activate --quiet "$theme_name"
      STATUS $?
    fi

    themes[$theme_name]=$theme_url
    ((i++))
  done <<< "$THEMES"

  h2 "Checking for orphaned themes"
  while read -r theme_name; do
    if [[ ! ${themes[$theme_name]} ]]; then
      h3 "'$theme_name' no longer needed. Pruning"
      WP theme delete --quiet "$theme_name"
      STATUS $?
    fi
  done <<< "$(WP theme list --field=name)"
}


check_plugins() {
  declare -A plugins
  local -i plugin_count=0
  local -i i=1
  local plugin_name
  local plugin_url

  # If $PLUGINS is not set => prune all existing plugins
  if [[ ! "${PLUGINS-}" ]]; then
    h3 "No plugin dependencies listed"
    STATUS SKIP
    h2 "Checking for orphaned plugins"
    while read -r plugin_name; do
      h3 "'$plugin_name' no longer needed. Pruning..."
      WP plugin uninstall --deactivate --quiet "$plugin_name"
      STATUS $?
    done <<< "$(WP plugin list --field=name)"
    return
  fi

  # Correct for cases where user forgets to add trailing comma
  [[ "${PLUGINS:(-1)}" != ',' ]] && PLUGINS+=','

  # Set $plugin_count to the total number of plugins set in $PLUGINS
  while read -r -d,; do ((plugin_count++)); done <<< "$PLUGINS"

  # Iterate over each plugin set in $PLUGINS
  while read -r -d, plugin_name; do
    plugin_url=  # reset to null

    # If $plugin_name matches a URL using the old format => attempt to install it and continue
    if [[ $plugin_name =~ ^https?://[www]?.+ ]]; then
      h3warn "$plugin_name"
      h3warn "Can't check if plugin is already installed using above format!"
      h3warn "Switch your compose file to '[plugin-slug]http://pluginurl.com/pluginfile.zip' for better checks"
      h3 "($i/$plugin_count) '$plugin_name' not found. Installing..."
      WP plugin install --activate --quiet "$plugin_name"
      STATUS $?
      ((i++))
      continue
    fi

    # Locally volumed plugins
    if [[ $plugin_name =~ ^\[local\] ]]; then
      plugins["${plugin_name##*]}"]="${plugin_name##*]}"
      h3 "($i/$plugin_count) '${plugin_name##*]}' listed as a local volume. Activating..."
      WP plugin activate --quiet "${plugin_name##*]}"
      STATUS SKIP
      ((i++))
      continue
    fi

    # If $plugin_name matches a URL using the new format => set $plugin_name & $plugin_url
    if [[ $plugin_name =~ ^\[.+\]https?://[www]?.+ ]]; then
      plugin_url=${plugin_name##\[*\]}
      plugin_name="$(echo "$plugin_name" | grep -oP '\[\K(.+)(?=\])')"
    fi

    plugin_url=${plugin_url:-$plugin_name}

    if WP plugin is-installed "$plugin_name"; then
      h3 "($i/$plugin_count) '$plugin_name' found. SKIPPING..."
      STATUS SKIP
    else
      h3 "($i/$plugin_count) '$plugin_name' not found. Installing..."
      WP plugin install --activate --quiet "$plugin_url"
      STATUS $?
      # Pretty much guarenteed to need/want 'restful' if you are using 'rest-api'
      if [ "$plugin_name" == 'rest-api' ]; then
        h3 "($i.5/$plugin_count) Installing 'restful' WP-CLI package..."
        wp package install wp-cli/restful --quiet --allow-root
        STATUS $?
      fi
    fi

    plugins[$plugin_name]=$plugin_url
    ((i++))
  done <<< "$PLUGINS"

  h2 "Checking for orphaned plugins"
  while read -r plugin_name; do
    if [[ ! ${plugins[$plugin_name]} ]]; then
      h3 "'$plugin_name' no longer needed. Pruning..."
      WP plugin uninstall --deactivate --quiet "$plugin_name"
      STATUS $?
    fi
  done <<< "$(WP plugin list --field=name)"
}


# Helpers
# --------------

RED='\033[0;31m'
GREEN='\033[0;32m'
ORANGE='\033[0;33m'
PURPLE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\E[1m'
NC='\033[0m'

h1() {
  local len=$(($(tput cols)-1))
  local input=$*
  local size=$(((len - ${#input})/2))

  for ((i = 0; i < len; i++)); do echo -ne "${PURPLE}${BOLD}="; done; echo ""
  for ((i = 0; i < size; i++)); do echo -n " "; done; echo -e "${NC}${BOLD}$input"
  for ((i = 0; i < len; i++)); do echo -ne "${PURPLE}${BOLD}="; done; echo -e "${NC}"
}

h2() {
  echo -e "${ORANGE}${BOLD}==>${NC}${BOLD} $*${NC}"
}

h3() {
  printf "%b " "${CYAN}${BOLD}  ->${NC} $*"
}

h3warn() {
  printf "%b " "${RED}${BOLD}  [!]|${NC} $*" && echo ""
}

STATUS() {
  local status=$1
  if [[ $1 == 'SKIP' ]]; then
    echo ""
    return
  fi
  if [[ $status != 0 ]]; then
    echo -e "${RED}✘${NC}"
    return
  fi
  echo -e "${GREEN}✓${NC}"
}

ERROR() {
  echo -e "${RED}=> ERROR (Line $1): $2.${NC}";
  exit 1;
}

WP() {
  sudo -u www-data wp "$@"
}

loglevel() {
  [[ "$VERBOSE" == "false" ]] && return
  local IN
  while read -r IN; do
    echo "$IN"
  done
}

main
