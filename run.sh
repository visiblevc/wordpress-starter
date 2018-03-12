#!/bin/bash
# shellcheck disable=SC1091

# Environment
# ------------
declare -x TERM="${TERM:-xterm}"
declare ADMIN_EMAIL=${ADMIN_EMAIL:-"admin@${DB_NAME:-wordpress}.com"}
declare AFTER_URL="${URL_REPLACE#*,}"
declare BEFORE_URL="${URL_REPLACE%,*}"
declare DB_HOST=${DB_HOST:-db}
declare DB_NAME=${DB_NAME:-wordpress}
declare DB_PASS=${DB_PASS:-root}
declare DB_PREFIX=${DB_PREFIX:-wp_}
declare DB_USER=${DB_USER:-root}
declare PERMALINKS=${PERMALINKS:-'/%year%/%monthnum%/%postname%/'}
declare PLUGINS="${PLUGINS//,/}"
declare SERVER_NAME=${SERVER_NAME:-localhost}
declare THEMES="${THEMES//,/}"
declare URL_REPLACE=${URL_REPLACE:-''}
declare WP_VERSION=${WP_VERSION:-latest}

declare -A plugin_deps
declare -A theme_deps

main() {
    h1 'Begin WordPress Installation'
    init

    h2 'Waiting for MySQL to initialize...'
    while ! mysqladmin ping \
        --host="$DB_HOST" \
        --user="$DB_USER" \
        --password="$DB_PASS" \
        --silent >/dev/null; do
        sleep 1
    done

    h2 'Configuring WordPress'
    configure

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
        wp --color rewrite structure "$PERMALINKS" --hard |& logger
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

    for raw_line in $PLUGINS; do
        mapfile -d$'\n' -t keyvalue < <(
            sed -n '
                s/.*\[\(.*\)\]\([^[:blank:]]*\).*/\1\n\2/p # Matches [key]value form
                t                                          # If previous match succeeds, skip to end
                {p; p;}                                    # Assumes normal form
            ' <<<"$raw_line")
        plugin_deps[${keyvalue[0]}]="${keyvalue[1]}"
    done

    for raw_line in ${THEMES:=twentyseventeen}; do
        mapfile -d$'\n' -t keyvalue < <(
            sed -n '
                s/.*\[\(.*\)\]\([^[:blank:]]*\).*/\1\n\2/p # Matches [key]value form
                t                                          # If previous match succeeds, skip to end
                {p; p;}                                    # Assumes normal form
            ' <<<"$raw_line")
        theme_deps[${keyvalue[0]}]="${keyvalue[1]}"
    done

    if [[ ! -f /app/wp-settings.php ]]; then
        h2 'Downloading WordPress'
        wp --color core download --version="$WP_VERSION" --skip-content |& logger
    fi
}

check_database() {
    wp core is-installed 2>/dev/null && return

    wp --color db create |& logger

    declare data_path
    data_path=$(find /data -name '*.sql' -print -quit 2>/dev/null)
    if [[ ! "$data_path" ]]; then
        wp --color core install |& logger
        return
    fi

    wp --color db import "$data_path" |& logger

    if [[ -n "$URL_REPLACE" ]]; then
        wp --color search-replace --skip-columns=guid "$BEFORE_URL" "$AFTER_URL" \
            | grep --color 'replacement' \
            |& logger
    fi
}

# Install / remove plugins based on $PLUGINS in parallel threads
check_plugins() {
    (
        declare -a add_list
        mapfile -t add_list < <(comm -23 \
            <(echo "${!plugin_deps[@]}" | tr ' ' '\n' | sort) \
            <(wp plugin list --field=name | sort))

        if [[ "${#add_list[@]}" -gt 0 ]]; then
            wp --color plugin install --activate "${add_list[@]}" |& logger
        fi
    ) &

    (
        declare -a remove_list
        mapfile -t remove_list < <(comm -13 \
            <(echo "${!plugin_deps[@]}" | tr ' ' '\n' | sort) \
            <(wp plugin list --field=name | sort))

        if [[ ${#remove_list[@]} -gt 0 ]]; then
            wp --color plugin uninstall --deactivate "${remove_list[@]}" |& logger
        fi
    ) &

    wait
}

# Install / remove themes based on $THEMES in parallel threads
check_themes() {
    (
        declare -a add_list
        mapfile -t add_list < <(comm -23 \
            <(echo "${!theme_deps[@]}" | tr ' ' '\n' | sort) \
            <(wp theme list --field=name | sort))

        if [[ "${#add_list[@]}" -gt 0 ]]; then
            wp --color theme install "${add_list[@]}" |& logger
        fi
    ) &

    (
        declare -a remove_list
        mapfile -t remove_list < <(comm -13 \
            <(echo "${!theme_deps[@]}" | tr ' ' '\n' | sort) \
            <(wp theme list --field=name | sort))

        if [[ ${#remove_list[@]} -gt 0 ]]; then
            wp --color theme delete "${remove_list[@]}" |& logger
        fi
    ) &

    wait
}

configure() {
    # Ensures that this only runs on the initial build
    [[ -f ~/.wp-cli/config.yml ]] && return

    # Apache config adustments
    sudo sed -i \
        -e "/^[[:blank:]]*.ServerName/{c\\" \
        -e "\\tServerName ${SERVER_NAME} \\" \
        -e "\\tServerAlias www.${SERVER_NAME}" \
        -e '}' /etc/apache2/sites-available/000-default.conf

    # Source bash completion now and on login
    echo '. /etc/bash_completion.d/wp-cli' >>~/.bashrc
    . /etc/bash_completion.d/wp-cli

    # WP-CLI defaults
    mkdir -p ~/.wp-cli
    cat <<EOF >~/.wp-cli/config.yml
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
    
    # Create wp-config.php file
    wp --color config create --force |& logger
}

# Helpers
# ---------------------

declare -i term_width=70

h1() {
    declare border padding text
    border='\e[1;34m'"$(printf '=%.0s' $(seq 1 "$term_width"))"'\e[0m'
    padding="$(printf ' %.0s' $(seq 1 $(((term_width - $(wc -m <<<"$*")) / 2))))"
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
