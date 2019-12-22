#!/usr/bin/env bash
set -e

: "${npm_package_version?Script must be ran using npm}"

for dir in ./images/*; do
    docker build \
        -f "$dir/Dockerfile" \
        --build-arg VERSION="$npm_package_version" \
        -t "visiblevc/wordpress:latest" \
        -t "visiblevc/wordpress:latest-php${dir##*/}" \
        -t "visiblevc/wordpress:$npm_package_version-php${dir##*/}" \
        .
done

echo "

Successfully built images with the following tags:"

docker images visiblevc/wordpress --format "{{.Tag}}" | sort -r
