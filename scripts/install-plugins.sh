#!/bin/bash

echo ""
echo "=> Install plugins:"
echo "====================================="

cd /app/wp-content/plugins

while read -r plugin; do
  if [ ! -d "/app/wp-content/plugins/${plugin}" ]; then
    echo ""
    echo "Install $plugin"
    curl -LOk# http://wordpress.org/extend/plugins/download/${plugin}.zip
    unzip -q ${plugin}.zip
    rm ${plugin}.zip
  else
    echo "$plugin already installed"
  fi
done < /data/plugins
