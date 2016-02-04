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
# stops apache complaining about existing pid file
rm -f $APACHE_PID_FILE
exec apache2 -D FOREGROUND
