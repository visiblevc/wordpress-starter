#!/bin/bash

sleep 10
mysql --user=root --password=$DB_PASS --host=mysql --port=3306 -e "DROP DATABASE $DB_NAME"
mysql --user=root --password=$DB_PASS --host=mysql --port=3306 -e "CREATE DATABASE $DB_NAME"
mysql --user=root --password=$DB_PASS --host=mysql --port=3306 $DB_NAME < /data/database.sql
echo "Database restored from /data/database.sql"
