FROM tutum/apache-php

# Install mysql-client
RUN apt-get update && apt-get -y upgrade
RUN apt-get install -y mysql-client unzip

# Install wp-cli
ADD https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar /usr/local/bin/wp
RUN chmod +x /usr/local/bin/wp

# Install wordpress
RUN cd /app
WORKDIR /app
RUN wp core download --allow-root
RUN chown -R www-data:www-data /app/wp-content /var/www/html

# Configure wordpress
RUN mkdir -p /scripts
ADD ./scripts/setup-wordpress.sh /scripts/setup-wordpress.sh
RUN chmod 755 /scripts/*.sh
RUN /scripts/setup-wordpress.sh

# Configure Apache
RUN sed -i "s/AllowOverride None/AllowOverride All/g" /etc/apache2/apache2.conf
RUN a2enmod rewrite

# Add some scripts for development
ADD ./scripts/backup-db.sh /scripts/backup-db.sh
ADD ./scripts/restore-db.sh /scripts/restore-db.sh
ADD ./scripts/install-plugins.sh /scripts/install-plugins.sh
ADD ./scripts/remove-plugins.sh /scripts/remove-plugins.sh
RUN chmod 755 /scripts/*.sh

# Run the server
EXPOSE 80 443
ADD ./scripts/run.sh /run.sh
RUN chmod 755 /*.sh
CMD ["/run.sh"]
