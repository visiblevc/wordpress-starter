#!/bin/bash

cd /app
wp core download --allow-root \
    --skip-plugins=akismet,hello \
    --skip-themes=twentyfifteen,twentyfourteen
chown -R www-data:www-data /app/wp-content /var/www/html

# Configure wp-config.php
[ ! $DB_NAME ] && DB_NAME='wordpress'
[ ! $DB_PASS ] && DB_PASS='root'

cp /app/wp-config-sample.php /app/wp-config.php
curl https://api.wordpress.org/secret-key/1.1/salt/ -o /usr/local/src/wp.keys
sed -i "s/database_name_here/$DB_NAME/" /app/wp-config.php
sed -i "s/username_here/root/" /app/wp-config.php
sed -i "s/password_here/$DB_PASS/" /app/wp-config.php
sed -i "s/'localhost'/\"mysql:3306\"/" /app/wp-config.php
sed -i '/#@-/r /usr/local/src/wp.keys' /app/wp-config.php
sed -i "/#@+/,/#@-/d" /app/wp-config.php

# .htaccess
cat > /app/.htaccess <<EOF
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
