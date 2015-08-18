#!/bin/bash

mysqldump -h $MYSQL_PORT_3306_TCP_ADDR -u root -p$MYSQL_ENV_MYSQL_ROOT_PASSWORD wordpress > /data/wordpress_bk.sql
echo "Database backed up to /data/wordpress_bk.sql"
