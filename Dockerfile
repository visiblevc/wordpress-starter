FROM tutum/apache-php

# Install mysql-client
RUN apt-get update && apt-get -y upgrade
RUN apt-get install -y mysql-client unzip
ENV TERM xterm

# Install wp-cli
ADD https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar /usr/local/bin/wp
RUN chmod +x /usr/local/bin/wp

# Configure Apache
RUN sed -i "s/AllowOverride None/AllowOverride All/g" /etc/apache2/apache2.conf
RUN a2enmod rewrite

# Add some scripts for development
RUN mkdir -p /scripts
ADD ./scripts/setup-database.sh /scripts/setup-database.sh
ADD ./scripts/setup-wordpress.sh /scripts/setup-wordpress.sh
ADD ./scripts/backup-db.sh /scripts/backup-db.sh
ADD ./scripts/restore-db.sh /scripts/restore-db.sh
ADD ./scripts/install-plugins.sh /scripts/install-plugins.sh
ADD ./scripts/remove-plugins.sh /scripts/remove-plugins.sh
RUN chmod 755 /scripts/*.sh

# Run the server
EXPOSE 80 443
WORKDIR /app
ADD ./scripts/run.sh /run.sh
RUN chmod 755 /run.sh
CMD ["/run.sh"]
