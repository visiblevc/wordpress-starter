#!/usr/bin/env bash

declare -A image
image=(
  [php7.0]='php:7.0-apache'
  [php7.0-xdebug]='milk/php-xdebug:7.0'
  [php5.6]='php:5.6-apache'
  [php5.6-xdebug]='milk/php-xdebug:5.6'
)

for version in "${!image[@]}"; do
  # Cleanup existing files
  rm -rf "${version}"
  mkdir "${version}"

  dockerfile=${version}/Dockerfile
  touch "${dockerfile}"
  echo "FROM ${image[$version]}" > "${dockerfile}"
  cat Dockerfile >> "${dockerfile}"
done
