#!/usr/bin/env bash

WS_VERSION=0.13.0

declare -A image
image=(
  [php7.0]='php:7.0-apache'
  [php7.0-xdebug]='milk/php-xdebug:7.0'
  [php5.6]='php:5.6-apache'
  [php5.6-xdebug]='milk/php-xdebug:5.6'
)

for version in "${!image[@]}"; do
  tags=$(echo " -t visiblevc/wordpress:latest-${version} -t visiblevc/wordpress:${WS_VERSION}-${version}")

  if [[ "${version}" == 'php7.0' ]]; then
    tags+=$(echo " -t visiblevc/wordpress:latest -t visiblevc/wordpress:${WS_VERSION}")
  fi

  docker build --no-cache $(echo "${tags} ${version}")
done
