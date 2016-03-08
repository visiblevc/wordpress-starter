#!/bin/bash

sleep 10
mysqldump --host=mysql  --user=root --password=$DB_PASS wordpress > /data/wordpress_bk.sql
echo "Database backed up to /data/wordpress_bk.sql"
