#!/bin/bash
# shellcheck disable=SC1091

# Environment
# ------------
ADMIN_EMAIL=${ADMIN_EMAIL:-"admin@${DB_NAME:-wordpress}.com"}
DB_HOST=${DB_HOST:-db}
DB_USER=${DB_USER:-root}
DB_NAME=${DB_NAME:-wordpress}
DB_PASS=${DB_PASS:-root}
DB_PREFIX=${DB_PREFIX:-wp_}
PERMALINKS=${PERMALINKS:-'/%year%/%monthnum%/%postname%/'}
SERVER_NAME=${SERVER_NAME:-localhost}
WP_VERSION=${WP_VERSION:-latest}
URL_REPLACE=${URL_REPLACE:-''}
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
cat > ~/.wp-cli/config.yml <<EOF
apache_modules:
  - mod_rewrite

core config:
  dbuser: $DB_USER
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
  admin_user: $DB_USER
  admin_password: $DB_PASS
  admin_email: $ADMIN_EMAIL
  skip-email: true
EOF

# WP-CLI bash completions
# ------------------
echo "
. /etc/bash_completion.d/wp-cli
" >> /root/.bashrc
. /etc/bash_completion.d/wp-cli

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

_log_last_exit_colorize() {
  if [ $? -eq 0 ]; then
    echo "$1" |& _colorize
  else
    echo "$2" |& _colorize
    exit 1
  fi
}

_get_volumes() {
  local volume_type="$1"
  local filenames dirnames
  local names=()

  filenames=$(
    find /app/wp-content/"$volume_type"/* -maxdepth 0 -type f ! -name 'index*' -print0 2>/dev/null |
    xargs -0 -I {} basename {} .php
  )
  dirnames=$(
    find /app/wp-content/"$volume_type"/* -maxdepth 0 -type d -print0 2>/dev/null |
    xargs -0 basename -a 2>/dev/null
  )
  names=( $filenames $dirnames )

  echo "${names[@]}"
}

_wp() {
  wp --allow-root "$@"
}

# Config Functions
# ---------------------

init() {
  local plugins themes i

  PLUGINS="${PLUGINS/%,},"
  THEMES="${THEMES/%,},"

  if [[ -f /root/.dockercache ]]; then
    . /root/.dockercache
  else
    plugins=$( _get_volumes plugins )
    themes=$( _get_volumes themes )
    echo "plugins='$plugins'" >> ~/.dockercache
    echo "themes='$themes'" >> ~/.dockercache
  fi

  for i in $plugins; do
    plugin_volumes[$i]="$i"
  done

  for i in $themes; do
    theme_volumes[$i]="$i"
  done

  local key value IFS=$'\n'
  while read -r -d, i; do
    [[ ! "$i" ]] && continue
    i="${i# }"          # Trim leading whitespace
    key="${i%]*}"       # Trim right bracket to end of string
    key="${key//[\[ ]}" # Trim left bracket
    value="${i##\[*\]}" # Trim bracketed text inclusive
    plugin_deps[$key]="$value"
  done <<< "$PLUGINS"

  while read -r -d, i; do
    [[ ! "$i" ]] && continue
    i="${i# }"          # Trim leading whitespace
    key="${i%]*}"       # Trim right bracket to end of string
    key="${key//[\[ ]}" # Trim left bracket
    value="${i##\[*\]}" # Trim bracketed text inclusive
    theme_deps[$key]="$value"
  done <<< "$THEMES"

  # Download WordPress
  # ------------------
  if [[ ! -f /app/wp-settings.php ]]; then
    h2 "Downloading WordPress"
    _wp core download --version="$WP_VERSION"
    _log_last_exit_colorize "Success: Wordpress downloaded" "Error: Wordpress download failed!"
  fi

  chown -R www-data /app/wp-content
}

check_database() {
  local data_path

  # Already installed
  wp core is-installed --allow-root 2>/dev/null && return

  _wp db create
  _log_last_exit_colorize "Success: db create" "Error: db create failed!"

  # No backups found
  if [[ "$( find /data -name "*.sql" 2>/dev/null | wc -l )" -eq 0 ]]; then
    _wp core install
    _log_last_exit_colorize "Success: core install" "Error: core install failed!"

    return
  fi

  data_path=$( find /data -name "*.sql" -print -quit )
  _wp db import "$data_path"
  _log_last_exit_colorize "Success: db import" "Error: db import failed!"

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

  for key in "${to_install[@]}"; do
    wp plugin install --allow-root "$key"
    _log_last_exit_colorize "Success: $key plugin installed" "Error: $key plugin install failure!"
  done

  [[ "${#to_remove}" -gt 0 ]] && _wp plugin delete "${to_remove[@]}"
  _wp plugin activate --all
  _log_last_exit_colorize "Success: plugin activate all" "Error: plugin activate all failed!"
 }

check_themes() {
  local key
  local theme
  local to_install=()
  local to_remove=()

  if [[ "${#theme_deps[@]}" -gt 0 ]]; then
    for key in "${!theme_deps[@]}"; do
      if ! wp theme is-installed --allow-root "$key"; then
        to_install+=( "${theme_deps[$key]}" )
      fi
    done
  fi

  for key in "${to_install[@]}"; do
    wp theme install --allow-root "$key"
    _log_last_exit_colorize "Success: $key theme install " "Error: $key theme install failed!"
  done

  for theme in $(wp theme list --field=name --status=inactive --allow-root); do
    [[ ${theme_deps[$theme]} ]] && continue
    [[ ${theme_volumes[$theme]} ]] && continue
    to_remove+=( "$theme" )
  done

  for key in "${to_remove[@]}"; do
    wp theme delete --allow-root "$key"
    _log_last_exit_colorize "Success: $key theme deleted" "Error: $key theme delete failed!"
  done

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
  _log_last_exit_colorize "Success: core config" "Error: core config failed!"

  h2 "Checking database"
  check_database

  if [[ "$MULTISITE" == "true" ]]; then
    h2 "Enabling Multisite"
    _wp core multisite-convert
    _log_last_exit_colorize "Success: core multisite-convert" "Error: core multisite-convert failed!"
  fi

  h2 "Checking themes"
  check_themes

  h2 "Checking plugins"
  check_plugins

  h2 "Finalizing"
  if [[ "$MULTISITE" != 'true' ]]; then
    _wp rewrite structure "$PERMALINKS"
    _log_last_exit_colorize "Success: rewrite structure" "Error: rewrite structure failed!"

    _wp rewrite flush --hard
    _log_last_exit_colorize "Success: rewrite flush" "Error: rewrite flush failed!"
  fi

  chown -R www-data /app /var/www/html
  find /app -type d -exec chmod 755 {} \;
  find /app -type f -exec chmod 644 {} \;
  find /app \( -type f -or -type d \) ! -group root -exec chmod g+rw {} \;

  h1 "WordPress Configuration Complete!"

  rm -f /var/run/apache2/apache2.pid
  . /etc/apache2/envvars
  exec apache2 -D FOREGROUND
}

main
