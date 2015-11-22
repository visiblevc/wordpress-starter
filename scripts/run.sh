#!/bin/bash

if [ ! -f /app/wp-config.php ]; then
  /scripts/setup-wordpress.sh
else
  echo "Wordpress already setup!"
fi

if [ ! -f /app/.mysql_db_created ]; then
  /scripts/setup-database.sh
else
  echo "Database already setup!"
fi

source /etc/apache2/envvars
exec apache2 -D FOREGROUND
