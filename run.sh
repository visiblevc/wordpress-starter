#!/bin/bash
# shellcheck disable=SC1091

# Environment
# ------------
ADMIN_EMAIL=${ADMIN_EMAIL:-"admin@${DB_NAME:-wordpress}.com"}
DB_HOST=${DB_HOST:-db}
DB_NAME=${DB_NAME:-wordpress}
DB_PASS=${DB_PASS:-root}
DB_PREFIX=${DB_PREFIX:-wp_}
PERMALINKS=${PERMALINKS:-'/%year%/%monthnum%/%postname%/'}
SERVER_NAME=${SERVER_NAME:-localhost}
WP_VERSION=${WP_VERSION:-latest}
# FIXME: Remove in next version
URL_REPLACE=${URL_REPLACE:-"$SEARCH_REPLACE"}
BEFORE_URL="${URL_REPLACE%,*}"
AFTER_URL="${URL_REPLACE#*,}"

declare -A plugin_deps
declare -A theme_deps
declare -A plugin_volumes
declare -A theme_volumes

# Apache configuration
# --------------------
sed -i "s/#ServerName www.example.com/ServerName $SERVER_NAME\nServerAlias www.$SERVER_NAME/" /etc/apache2/sites-available/000-default.conf

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
    define('WP_DEBUG', ${WP_DEBUG:-false});
    define('WP_DEBUG_LOG', ${WP_DEBUG_LOG:-false});
    define('WP_DEBUG_DISPLAY', ${WP_DEBUG_DISPLAY:-true});

core install:
  url: ${AFTER_URL:-localhost:8080}
  title: $DB_NAME
  admin_user: root
  admin_password: $DB_PASS
  admin_email: $ADMIN_EMAIL
  skip-email: true
EOF

# Helpers
# ---------------------

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

_colorize() {
  local IN
  local success="${GREEN}${BOLD}Success:${NC}"
  local failed="${RED}${BOLD}Error:${NC}"
  local warning="${CYAN}${BOLD}Warning:${NC}"
  while read -r IN; do
    IN="${IN/Success\:/$success}"
    IN="${IN/Error\:/$failed}"
    IN="${IN/Warning\:/$warning}"
    echo -e "$IN"
  done
}

_get_volumes() {
  local volume_type="$1"
  local filenames dirnames
  local names=()

  filenames=$(
    find /app/wp-content/"$volume_type"/* -maxdepth 0 -type f ! -name 'index*' -group 1000 -print0 |
    xargs -0 -I {} basename {} .php
  )
  dirnames=$(
    find /app/wp-content/"$volume_type"/* -maxdepth 0 -type d -group 1000 -print0 |
    xargs -0 basename -a 2>/dev/null
  )
  names=( $filenames $dirnames )

  echo "${names[@]}"
}

_wp() {
  wp --allow-root "$@" |& _colorize
}

# FIXME: Remove in next version
# Deprecations
# ---------------------
_local_deprecation() {
  local local_type="$1" # 'plugin' or 'theme'
  echo "Warning: [local]$local_type-name has been deprecated and will be dropped in the next version." |& _colorize
}

_search_replace_depreaction() {
  echo "Warning: SEARCH_REPLACE environment variable has been renamed to URL_REPLACE and will be dropped in the next version." |& _colorize
}

# Config Functions
# ---------------------

init() {
  local plugins themes i IFS=$'\n'

  # FIXME: Remove in next version
  [[ -n $SEARCH_REPLACE ]] && _search_replace_depreaction

  # Download WordPress
  # ------------------
  if [[ ! -f /app/wp-settings.php ]]; then
    h2 "Downloading WordPress"
    _wp core download --version="$WP_VERSION"
  fi

  PLUGINS="${PLUGINS/%,},"
  THEMES="${THEMES/%,},"

  if [[ -f /app/.dockercache ]]; then
    . /app/.dockercache
  else
    plugins=$( _get_volumes plugins )
    themes=$( _get_volumes themes )
    echo "plugins='$plugins'" >> /app/.dockercache
    echo "themes='$themes'" >> /app/.dockercache
  fi

  while read -r i; do
    [[ ! "$i" ]] && continue
    plugin_volumes[$i]="$i"
  done <<< "$plugins"

  while read -r i; do
    [[ ! "$i" ]] && continue
    theme_volumes[$i]="$i"
  done <<< "$themes"

  local key value

  while read -r -d, i; do
    [[ ! "$i" ]] && continue
    i="${i# }"          # Trim leading whitespace
    key="${i%]*}"       # Trim right bracket to end of string
    key="${key//[\[ ]}" # Trim left bracket
    value="${i##\[*\]}" # Trim bracketed text inclusive
    # FIXME: Remove in next version
    [[ "$key" == 'local' ]] && _local_deprecation plugin && continue
    plugin_deps[$key]="$value"
  done <<< "$PLUGINS"

  while read -r -d, i; do
    [[ ! "$i" ]] && continue
    i="${i# }"          # Trim leading whitespace
    key="${i%]*}"       # Trim right bracket to end of string
    key="${key//[\[ ]}" # Trim left bracket
    value="${i##\[*\]}" # Trim bracketed text inclusive
    # FIXME: Remove in next version
    [[ "$key" == 'local' ]] && _local_deprecation theme && continue
    theme_deps[$key]="$value"
  done <<< "$THEMES"

  chown -R www-data /app /var/www/html
}

check_database() {
  local data_path

  # Already installed
  wp core is-installed --allow-root 2>/dev/null && return

  _wp db create

  # No backups found
  if [[ "$( find /data -name "*.sql" | wc -l )" -eq 0 ]]; then
    _wp core install
    return
  fi

  data_path=$( find /data -name "*.sql" -print -quit )
  _wp db import "$data_path"

  if [[ -n "$URL_REPLACE" ]]; then
    wp search-replace --skip-columns=guid "$BEFORE_URL" "$AFTER_URL" --allow-root \
    | grep 'replacement' \
    |& _colorize
  fi
}

check_plugins() {
  local key
  local plugin
  local to_install=()
  local to_remove=()

  if [[ "${#plugin_deps[@]}" -gt 0 ]]; then
    for key in "${!plugin_deps[@]}"; do
      if ! wp plugin is-installed --allow-root "$key"; then
        to_install+=( "${plugin_deps[$key]}" )
      fi
    done
  fi

  for plugin in $(wp plugin list --field=name --allow-root); do
    [[ ${plugin_deps[$plugin]} ]] && continue
    [[ ${plugin_volumes[$plugin]} ]] && continue
    to_remove+=( "$plugin" )
  done

  [[ "${#to_install}" -gt 0 ]] && wp plugin install --allow-root "${to_install[@]}" | tail -n 1 |& _colorize
  [[ "${#to_remove}" -gt 0 ]] && _wp plugin delete "${to_remove[@]}"
  _wp plugin activate --all
}

check_themes() {
  local key
  local theme
  local to_activate
  local to_install=()
  local to_remove=()

  to_activate=$( "${!theme_volumes[@]}" | awk '{ print $1 }' )

  if [[ "${#theme_deps[@]}" -gt 0 ]]; then
    for key in "${!theme_deps[@]}"; do
      if ! wp theme is-installed --allow-root "$key"; then
        to_install+=( "${theme_deps[$key]}" )
        [[ ! "$to_activate" ]] && to_activate="$key"
      fi
    done
  fi

  [[ "${#to_install}" -gt 0 ]] && wp theme install --allow-root "${to_install[@]}" | tail -n 1 |& _colorize
  [[ "$to_activate" ]] && _wp theme activate "$to_activate"

  for theme in $(wp theme list --field=name --status=inactive --allow-root); do
    [[ ${theme_deps[$theme]} ]] && continue
    [[ ${theme_volumes[$theme]} ]] && continue
    to_remove+=( "$theme" )
  done

  [[ "${#to_remove}" -gt 0 ]] && _wp theme delete "${to_remove[@]}"
}

main() {
  h1 "Begin WordPress Installation"
  init

  # Wait for MySQL
  # --------------
  h2 "Waiting for MySQL to initialize..."
  while ! mysqladmin ping --host="$DB_HOST" --password="$DB_PASS" --silent; do
    sleep 1
  done

  h2 "Configuring WordPress"
  rm -f /app/wp-config.php
  _wp core config

  h2 "Checking database"
  check_database

  if [[ "$MULTISITE" == "true" ]]; then
    h2 "Enabling Multisite"
    _wp core multisite-convert
  fi

  h2 "Checking themes"
  check_themes

  h2 "Checking plugins"
  check_plugins

  h2 "Finalizing"
  if [[ ! -f /app/.htaccess ]] && [[ "$MULTISITE" != 'true' ]]; then
    _wp rewrite structure "$PERMALINKS"
  fi

  chown -R www-data /app /var/www/html
  find /app -type d -exec chmod 755 {} \;
  find /app -type f -exec chmod 644 {} \;
  find /app \( -type f -or -type d \) -group '1000' -exec chmod g+rw {} \;

  h1 "WordPress Configuration Complete!"

  rm -f /var/run/apache2/apache2.pid
  . /etc/apache2/envvars
  exec apache2 -D FOREGROUND
}

main
