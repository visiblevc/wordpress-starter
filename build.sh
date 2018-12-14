#!/usr/bin/env bash
set -e

# Ascending order is important here
declare -a php_versions=(
    7.0
    7.1
    7.2
    7.3
)
declare npm_package_version="${npm_package_version?Script must be run using npm}"
declare dockerfile_dir
dockerfile_dir="$(dirname "${BASH_SOURCE[0]}")"

# NOTE: Not building this stack of images concurrently due to a known issue
# with docker concurrent builds. https://github.com/moby/moby/issues/9656
for php_version in "${php_versions[@]}"; do
    docker build \
        --build-arg PHP_VERSION="$php_version" \
        --build-arg VERSION="$npm_package_version" \
        -t "visiblevc/wordpress:latest" \
        -t "visiblevc/wordpress:latest-php${php_version}" \
        -t "visiblevc/wordpress:$npm_package_version-php${php_version}" \
        "$dockerfile_dir"
done

echo "

Successfully built images with the following tags:"

docker images visiblevc/wordpress --format "{{.Tag}}" | sort -r
