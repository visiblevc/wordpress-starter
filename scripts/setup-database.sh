#!/bin/bash

sleep 10

DB_HOST=$MYSQL_PORT_3306_TCP_ADDR
DB_PORT=$MYSQL_PORT_3306_TCP_PORT
DB_USER='root'
DB_NAME=$DB_NAME
DB_PASS=$MYSQL_ENV_MYSQL_ROOT_PASSWORD

echo "=> Setup Database:"
echo "====================================="
echo "  Database Host Address:  $DB_HOST"
echo "  Database Port number:   $DB_PORT"
echo "  Database Name:          $DB_NAME"
echo "  Database Username:      $DB_USER"
echo "====================================="

for ((i=0;i<10;i++))
do
    DB_CONNECTABLE=$(mysql -u$DB_USER -p$DB_PASS -h$DB_HOST -P$DB_PORT -e 'status' >/dev/null 2>&1; echo "$?")
    if [[ DB_CONNECTABLE -eq 0 ]]; then
        break
    fi
    sleep 5
done

if [[ $DB_CONNECTABLE -eq 0 ]]; then
    DB_EXISTS=$(mysql -u$DB_USER -p$DB_PASS -h$DB_HOST -P$DB_PORT -e "SHOW DATABASES LIKE '"$DB_NAME"';" 2>&1 |grep "$DB_NAME" > /dev/null ; echo "$?")

    if [[ DB_EXISTS -eq 1 ]]; then
        echo "=> Creating database $DB_NAME"
        RET=$(mysql -u$DB_USER -p$DB_PASS -h$DB_HOST -P$DB_PORT -e "CREATE DATABASE $DB_NAME")
        if [[ RET -ne 0 ]]; then
            echo "Cannot create database for wordpress"
            exit RET
        fi
    else
        echo "=> Skipped creation of database $DB_NAME – it already exists."
    fi

    if [ $(mysql -N -s -u$DB_USER -p$DB_PASS -h$DB_HOST -P$DB_PORT -e \
        "select count(*) from information_schema.tables where \
            table_schema='$DB_NAME' and table_name='wp_posts';") -eq 0 ]; then
        if [ -f /data/database.sql ]; then
            echo "=> Loading initial database data to $DB_NAME"
            RET=$(mysql -u$DB_USER -p$DB_PASS -h$DB_HOST -P$DB_PORT $DB_NAME < /data/database.sql)
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
