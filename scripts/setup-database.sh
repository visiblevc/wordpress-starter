#!/bin/bash

sleep 10

[ ! $DB_NAME ] && DB_NAME = 'wordpress'
[ ! $DB_PASS ] && DB_PASS = 'root'

echo "=> Setup Database:"
echo "====================================="
echo "  Database Host Address:  mysql"
echo "  Database Port number:   3306"
echo "  Database Name:          $DB_NAME"
echo "  Database Username:      root"
echo "====================================="

for ((i=0;i<10;i++))
do
    DB_CONNECTABLE=$(mysql --user=root --password=$DB_PASS --host=mysql --port=3306 -e 'status' >/dev/null 2>&1; echo "$?")
    if [[ DB_CONNECTABLE -eq 0 ]]; then
        break
    fi
    sleep 5
done

if [[ $DB_CONNECTABLE -eq 0 ]]; then
    DB_EXISTS=$(mysql --user=root --password=$DB_PASS --host=mysql --port=3306 -e "SHOW DATABASES LIKE '"$DB_NAME"';" 2>&1 |grep "$DB_NAME" > /dev/null ; echo "$?")

    if [[ DB_EXISTS -eq 1 ]]; then
        echo "=> Creating database $DB_NAME"
        RET=$(mysql --user=root --password=$DB_PASS --host=mysql --port=3306 -e "CREATE DATABASE $DB_NAME")
        if [[ RET -ne 0 ]]; then
            echo "Cannot create database for wordpress"
            exit RET
        fi
    else
        echo "=> Skipped creation of database $DB_NAME – it already exists."
    fi

    if [ $(mysql -N -s --user=root --password=$DB_PASS --host=mysql --port=3306 -e \
        "select count(*) from information_schema.tables where \
            table_schema='$DB_NAME' and table_name='wp_posts';") -eq 0 ]; then
        if [ -f /data/database.sql ]; then
            echo "=> Loading initial database data to $DB_NAME"
            RET=$(mysql --user=root --password=$DB_PASS --host=mysql --port=3306 $DB_NAME < /data/database.sql)
            if [[ RET -ne 0 ]]; then
                echo "Cannot load initial database data for wordpress"
                exit RET
            fi
        fi
    fi
else
    echo "Cannot connect to Mysql"
    exit $DB_CONNECTABLE
fi

echo "Database setup!"

touch /app/.mysql_db_created
