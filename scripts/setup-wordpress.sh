#!/bin/bash

DB_NAME_CONFIG="getenv('DB_NAME')"
DB_USER_CONFIG="getenv('DB_NAME')"
DB_PASSWORD_CONFIG="getenv('MYSQL_ENV_MYSQL_ROOT_PASSWORD')"
DB_HOST_CONFIG="getenv('MYSQL_PORT_3306_TCP_ADDR').':'.getenv('MYSQL_PORT_3306_TCP_PORT')"

sudo cp /app/wp-config-sample.php /app/wp-config.php
sudo curl https://api.wordpress.org/secret-key/1.1/salt/ -o /usr/local/src/wp.keys
sudo sed -i "s/'database_name_here'/getenv\('DB_NAME'\)/" /app/wp-config.php
sudo sed -i "s/username_here/root/" /app/wp-config.php
sudo sed -i "s/'password_here'/$DB_PASSWORD_CONFIG/" /app/wp-config.php
sudo sed -i "s/'localhost'/$DB_HOST_CONFIG/" /app/wp-config.php
sudo sed -i '/#@-/r /usr/local/src/wp.keys' /app/wp-config.php
sudo sed -i "/#@+/,/#@-/d" /app/wp-config.php

# .htaccess
sudo bash -c "cat > /app/.htaccess" <<EOF
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
EOF

echo "Wordpress setup!"
