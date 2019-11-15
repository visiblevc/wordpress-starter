#!/bin/bash
# shellcheck disable=SC1091

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
declare -A plugin_deps
declare -A theme_deps

# FIXME: Deprecation of old version of $URL_REPLACE
if [[ $URL_REPLACE =~ , ]]; then
    URL_REPLACE=${URL_REPLACE%%,*}
    printf '\e[1;33;7mDEPRECATED\e[0m %s\n' "URL_REPLACE must only contain AFTER_URL. BEFORE_URL,AFTER_URL form has been deprecated"
fi

# Configuration
# -------------
mkdir -p ~/.wp-cli
echo -e "
path: /app
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

# Apache config adustments
sudo sed -i \
    -e "/^[[:blank:]]*.ServerName www.example.com/{c\\" \
    -e "\\tServerName ${SERVER_NAME:-localhost} \\" \
    -e "\\tServerAlias www.${SERVER_NAME:-localhost}" \
    -e '}' /etc/apache2/sites-available/000-default.conf

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
    wp --color config create --force |& logger

    h2 'Checking database'
    check_database

    if [[ "$MULTISITE" == 'true' ]]; then
        h2 'Enabling Multisite'
        wp --color core multisite-convert |& logger
    fi

    h2 'Checking themes'
    check_themes

    h2 'Checking plugins'
    check_plugins

    h2 'Finalizing'
    if [[ "$MULTISITE" != 'true' ]]; then
        wp --color rewrite structure \
            "${PERMALINKS:-/%year%/%monthnum%/%postname%/}" |& logger
    fi

    if [[ -e /docker-entrypoint-initwp.d ]]; then
        h2 'Executing user init scripts'
        for file in /docker-entrypoint-initwp.d/*; do
            [[ -x $file ]] && "$file"
        done
    fi

    h1 'WordPress Configuration Complete!'

    sudo rm -f /var/run/apache2/apache2.pid
    sudo apache2-foreground
}

# Config Functions
# ---------------------

init() {
    declare raw_line
    declare -a keyvalue

    check_volumes

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
    if [[ ${#theme_deps[@]} == 0 && $(check_volumes -t) == "" ]]; then
        theme_deps[twentytwenty]=twentytwenty
    fi

    sudo chown -R admin:admin /app

    if [[ ! -f /app/wp-settings.php ]]; then
        h2 'Downloading WordPress'
        wp --color core download |& logger
    fi
}

check_database() {
    wp core is-installed 2> /dev/null && return

    wp --color db create |& logger

    declare data_path
    data_path=$(find /data -name '*.sql' -print -quit 2> /dev/null)
    if [[ ! "$data_path" ]]; then
        wp --color core install |& logger
        return
    fi

    wp --color db import "$data_path" |& logger

    if [[ -n "$URL_REPLACE" ]]; then
        wp --color search-replace \
            --skip-columns=guid \
            --report-changed-only \
            --no-report \
            "$(wp option get siteurl)" \
            "$URL_REPLACE" |& logger
    fi

    wp --color core update-db |& logger
}

# Install / remove plugins based on $PLUGINS in parallel threads
check_plugins() {
    declare -a plugin_volumes
    mapfile -t plugin_volumes < <(check_volumes -p)

    (
        # Obtain keys of plugins to install
        mapfile -t plugin_keys < <(comm -23 \
            <(echo "${!plugin_deps[@]}" | tr ' ' '\n' | sort -u) \
            <(wp plugin list --field=name | sort -u))

        # Transform keys to values
        mapfile -t plugin_values < <(
            for key in "${plugin_keys[@]}"; do
                echo "${plugin_deps[$key]}"
            done
        )

        if [[ "${#plugin_keys[@]}" -gt 0 ]]; then
            wp --color plugin install "${plugin_values[@]}" |& logger
            # Silence nonsensical "plugin already activated" warning messages
            wp plugin activate "${plugin_keys[@]}" --quiet
        fi
    ) &

    (
        mapfile -t remove_list < <(comm -13 \
            <(echo "${!plugin_deps[@]}" "${plugin_volumes[@]}" | tr ' ' '\n' | sort -u) \
            <(wp plugin list --field=name | sort -u))

        if [[ ${#remove_list[@]} -gt 0 ]]; then
            wp --color plugin uninstall --deactivate "${remove_list[@]}" |& logger
        fi
    ) &

    wait
}

# Install / remove themes based on $THEMES in parallel threads
check_themes() {
    declare -a theme_volumes
    mapfile -t theme_volumes < <(check_volumes -t)

    (
        # Obtain keys of themes to install
        mapfile -t theme_keys < <(comm -23 \
            <(echo "${!theme_deps[@]}" "${theme_volumes[@]}" | tr ' ' '\n' | sort -u) \
            <(wp theme list --field=name | sort -u))

        # Transform keys to values
        mapfile -t theme_values < <(
            for key in "${theme_keys[@]}"; do
                echo "${theme_deps[$key]}"
            done
        )

        if [[ "${#theme_values[@]}" -gt 0 ]]; then
            wp --color theme install "${theme_values[@]}" |& logger
        fi
    ) &

    (
        mapfile -t remove_list < <(comm -13 \
            <(echo "${!theme_deps[@]}" "${theme_volumes[@]}" | tr ' ' '\n' | sort -u) \
            <(wp theme list --field=name | sort -u))

        if [[ ${#remove_list[@]} -gt 0 ]]; then
            wp --color theme delete "${remove_list[@]}" |& logger
        fi
    ) &

    wait
}

check_volumes() {
    if [[ ! -f ~/.dockercache ]]; then
        {
            (
                find /app/wp-content/{plugins,mu-plugins} \
                    -maxdepth 1 \
                    -type d \
                    -printf 'plugin\t%f\n' 2> /dev/null
            ) &
            (
                find /app/wp-content/{plugins,mu-plugins} \
                    -maxdepth 1 \
                    -type f \
                    -name '*.php' \
                    -exec basename '{}' .php \; \
                    | awk '{ print "plugin\t" $0 }'
            ) &
            (
                find /app/wp-content/themes \
                    -maxdepth 1 \
                    -type d \
                    -printf 'theme\t%f\n' 2> /dev/null
            ) &
            wait
        } > ~/.dockercache
    fi

    declare opt OPTIND
    while getopts 'pt' opt; do
        case "$opt" in
            p)
                awk '/^plugin/{ print $2 }' ~/.dockercache
                ;;
            t)
                awk '/^theme/{ print $2 }' ~/.dockercache
                ;;
            *)
                exit 1
                ;;
        esac
    done
    shift "$((OPTIND - 1))"
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
