#!/bin/bash

cd /app
wp core download --allow-root
chown -R www-data:www-data /app/wp-content /var/www/html

# Configure wp-config.php
DB_NAME_CONFIG="getenv('DB_NAME')"
DB_USER_CONFIG="root"
DB_PASSWORD_CONFIG="getenv('MYSQL_ENV_MYSQL_ROOT_PASSWORD')"
DB_HOST_CONFIG="getenv('MYSQL_PORT_3306_TCP_ADDR').':'.getenv('MYSQL_PORT_3306_TCP_PORT')"

cp /app/wp-config-sample.php /app/wp-config.php
curl https://api.wordpress.org/secret-key/1.1/salt/ -o /usr/local/src/wp.keys
sed -i "s/'database_name_here'/$DB_NAME_CONFIG/" /app/wp-config.php
sed -i "s/username_here/$DB_USER_CONFIG/" /app/wp-config.php
sed -i "s/'password_here'/$DB_PASSWORD_CONFIG/" /app/wp-config.php
sed -i "s/'localhost'/$DB_HOST_CONFIG/" /app/wp-config.php
sed -i '/#@-/r /usr/local/src/wp.keys' /app/wp-config.php
sed -i "/#@+/,/#@-/d" /app/wp-config.php

# .htaccess
bash -c "cat > /app/.htaccess" <<EOF
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
