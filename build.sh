#!/usr/bin/env bash
set -e

: "${npm_package_version?Script must be ran using npm}"

for dir in ./images/*; do
    docker build \
        -f "$dir/Dockerfile" \
        --build-arg VERSION="$npm_package_version" \
        --platform linux/arm64 \
        -t "hanneshapke/wordpress:latest" \
        -t "hanneshapke/wordpress:latest-php${dir##*/}" \
        -t "hanneshapke/wordpress:$npm_package_version-php${dir##*/}" \
        .
done

echo "

Successfully built images with the following tags:"

docker images visiblevc/wordpress --format "{{.Tag}}" | sort -r
