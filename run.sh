#!/bin/bash

if ! sudo mount -a 2> /dev/null; then
    printf '\e[1;31mERROR:\e[0m %s' \
        'Container running with improper privileges.

    Be sure your service is configured with the following options:
    ___
    services:
      wordpress:
        cap_add:
          - SYS_ADMIN
        devices:
          - /dev/fuse
        # needed on certain cloud hosts
        security_opt:
          - apparmor:unconfined
    ___

    OR (use first option if possible)
    ___
    services:
    wordpress:
        privileged: true
    ___

    ' | sed 's/^    //'
    exit 1
fi

# Environment
# ------------
declare -x TERM="${TERM:-xterm}"
declare PLUGINS="${PLUGINS//,/}"
declare THEMES="${THEMES//,/}"

# Runtime
# ------------
declare default_theme=twentytwenty
declare -A plugin_deps
declare -A theme_deps

# Configuration
# -------------
mkdir -p ~/.wp-cli
echo -e "
path: /app
color: true
apache_modules:
    - mod_rewrite

config create:
    dbhost: ${DB_HOST:-db}:3306
    dbname: ${DB_NAME:-wordpress}
    dbpass: ${DB_PASS:-root}
    dbprefix: ${DB_PREFIX:-wp_}
    dbuser: ${DB_USER:-root}
    dbcharset: ${DB_CHARSET:-utf8}
    extra-php: |
        define('WP_DEBUG', ${WP_DEBUG:-false});
        define('WP_DEBUG_LOG', ${WP_DEBUG_LOG:-false});
        define('WP_DEBUG_DISPLAY', ${WP_DEBUG_DISPLAY:-true});
        $(sed '1 ! s/.*/        \0/' < <(echo -e "${EXTRA_PHP:-}"))

core download:
    locale: ${WP_LOCALE:-en_US}
    skip-content: true
    version: ${WP_VERSION:-latest}

core install:
    admin_email: ${ADMIN_EMAIL:-admin@${SERVER_NAME:-localhost.com}}
    admin_password: ${DB_PASS:-root}
    admin_user: ${DB_USER:-root}
    skip-email: true
    title: ${DB_NAME:-wordpress}
    url: ${URL_REPLACE:-localhost:8080}

rewrite structure:
    hard: true

" > ~/.wp-cli/config.yml

main() {
    h1 'Begin WordPress Installation'
    init

    h2 'Waiting for MySQL to initialize...'
    while ! mysqladmin ping \
        --host="${DB_HOST:-db}" \
        --user="${DB_USER:-root}" \
        --password="${DB_PASS:-root}" \
        --silent > /dev/null; do
        sleep 1
    done

    h2 'Configuring WordPress'
    wp config create --force |& logger

    h2 'Checking database'
    check_database

    if [[ "$MULTISITE" == 'true' ]]; then
        h2 'Enabling Multisite'
        wp core multisite-convert |& logger
    fi

    h2 'Checking themes'
    check_themes

    h2 'Checking plugins'
    check_plugins

    h2 'Finalizing'
    if [[ $MULTISITE != true ]]; then
        wp rewrite structure \
            "${PERMALINKS:-/%year%/%monthnum%/%postname%/}" |& logger
    fi

    if [[ -e /docker-entrypoint-initwp.d ]]; then
        h2 'Executing user init scripts'
        for file in /docker-entrypoint-initwp.d/*; do
            [[ -x $file ]] && "$file"
        done
    fi

    h1 'WordPress Installation Complete!'

    sudo rm -f /var/run/apache2/apache2.pid
    sudo apache2-foreground
}

# Config Functions
# ---------------------

init() {
    declare raw_line
    declare -a keyvalue

    for raw_line in $PLUGINS; do
        mapfile -t keyvalue < <(
            sed -n '
                s/.*\[\(.*\)\]\([^[:blank:]]*\).*/\1\n\2/p # Matches [key]value form
                t                                          # If previous match succeeds, skip to end
                {p; p;}                                    # Assumes normal form
            ' <<< "$raw_line"
        )
        plugin_deps[${keyvalue[0]}]="${keyvalue[1]}"
    done

    for raw_line in $THEMES; do
        mapfile -t keyvalue < <(
            sed -n '
                s/.*\[\(.*\)\]\([^[:blank:]]*\).*/\1\n\2/p # Matches [key]value form
                t                                          # If previous match succeeds, skip to end
                {p; p;}                                    # Assumes normal form
            ' <<< "$raw_line"
        )
        theme_deps[${keyvalue[0]}]="${keyvalue[1]}"
    done

    # If no theme dependencies or volumes exist, fall back to default
    if [[ ${#theme_deps[@]} == 0 && ! -d /app/wp-content/themes ]]; then
        theme_deps["$default_theme"]="$default_theme"
    fi

    # Apache config adustments
    sudo sed -i \
        -e "/^[[:blank:]]*.ServerName www.example.com/{c\\" \
        -e "\\tServerName ${SERVER_NAME:-localhost} \\" \
        -e "\\tServerAlias www.${SERVER_NAME:-localhost}" \
        -e '}' /etc/apache2/sites-available/000-default.conf

    sudo chown -R admin:admin /app

    if [[ ! -f /app/wp-settings.php ]]; then
        h2 'Downloading WordPress'
        wp core download |& logger
    fi
}

# this is ran in a subshell to isolate nullglob as it for whatever reason
# interacts with our $PLUGINS and $THEMES variables.
# See: https://github.com/visiblevc/wordpress-starter/issues/176
check_database() (
    shopt -s nullglob
    declare file
    declare -i num_imported=0

    wp core is-installed 2> /dev/null && return

    wp db create |& logger

    wp core install |& logger

    for file in /data/*.sql; do
        h2 "Importing $file (this might take a while)..."
        wp db import "$file" |& logger
        ((num_imported++))
    done

    if ((num_imported > 0)); then
        if [[ -n $URL_REPLACE ]]; then
            h2 "Replacing URLs in database (this might take a while)..."
            wp search-replace \
                --skip-columns=guid \
                --report-changed-only \
                --no-report \
                "$(wp option get siteurl)" \
                "$URL_REPLACE" |& logger
        fi

        h2 'Updating database...'
        wp core update-db |& logger
    fi
)

check_plugins() {
    declare key

    for key in "${!plugin_deps[@]}"; do
        wp plugin is-installed "$key" || wp plugin install "${plugin_deps[$key]}" |& logger
        wp plugin is-active "$key" || wp plugin activate "$key" |& logger
    done
}

check_themes() {
    declare key

    for key in "${!theme_deps[@]}"; do
        wp theme is-installed "$key" || wp theme install "${theme_deps[$key]}" |& logger
        wp theme is-active "$key" || wp theme activate "$key" |& logger
    done
}

# Helpers
# ---------------------

declare -i term_width=70

h1() {
    declare border padding text
    border='\e[1;34m'"$(printf '=%.0s' $(seq 1 "$term_width"))"'\e[0m'
    padding="$(printf ' %.0s' $(seq 1 $(((term_width - $(wc -m <<< "$*")) / 2))))"
    text="\\e[1m$*\\e[0m"
    echo -e "$border"
    echo -e "${padding}${text}${padding}"
    echo -e "$border"
}

h2() {
    printf '\e[1;33m==>\e[37;1m %s\e[0m\n' "$*"
}

logger() {
    fold --width $((term_width - 9)) -s | sed -n '
    /^\x1b\[[0-9;]*m/{ # match any line beginning with colorized text
        s/Error:/  \0/ # pads line so its length matches others
        p              # any lines containing color
        b              # branch to end
    }
    s/.*/         \0/p # pads all other lines with 9 spaces
    '
}

main
