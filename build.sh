#!/usr/bin/env bash
set -e

: "${npm_package_version?Script must be ran using npm}"

# Ascending order is important here
declare -a php_versions=(
    7.2
    7.3
    7.4
)

# NOTE: Not building this stack of images concurrently due to a known issue
# with docker concurrent builds. https://github.com/moby/moby/issues/9656
for php_version in "${php_versions[@]}"; do
    docker build \
        --build-arg PHP_VERSION="$php_version" \
        --build-arg VERSION="$npm_package_version" \
        -t "visiblevc/wordpress:latest" \
        -t "visiblevc/wordpress:latest-php${php_version}" \
        -t "visiblevc/wordpress:$npm_package_version-php${php_version}" \
        .
done

echo "

Successfully built images with the following tags:"

docker images visiblevc/wordpress --format "{{.Tag}}" | sort -r
