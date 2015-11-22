#!/bin/bash

cd /app/wp-content/plugins

while read -r plugin; do
  if [ ! -d "/app/wp-content/plugins/${plugin}" ]; then
    echo ""
    echo "Install $plugin"
    curl -LOk# https://downloads.wordpress.org/plugin/${plugin}.zip
    unzip -q ${plugin}.zip
    rm ${plugin}.zip
  else
    echo "$plugin already installed"
  fi
done < /data/plugins
