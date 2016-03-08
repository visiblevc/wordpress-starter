FROM tutum/apache-php
ENV DEBIAN_FRONTEND noninteractive

# Install mysql-client
RUN apt-get update && apt-get install -y --no-install-recommends \
        mysql-client \
        unzip \
    && rm -rf /var/lib/apt/lists/*

# Install wp-cli, configure Apache, & add scripts
WORKDIR /app
RUN mkdir /scripts && curl \
        -o /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
        -o /scripts/setup-database.sh https://raw.githubusercontent.com/visiblevc/wordpress-starter/master/scripts/setup-database.sh \
        -o /scripts/setup-wordpress.sh https://raw.githubusercontent.com/visiblevc/wordpress-starter/master/scripts/setup-wordpress.sh \
        -o /scripts/backup-db.sh https://raw.githubusercontent.com/visiblevc/wordpress-starter/master/scripts/backup-db.sh \
        -o /scripts/restore-db.sh https://raw.githubusercontent.com/visiblevc/wordpress-starter/master/scripts/restore-db.sh \
        -o /scripts/install-plugins.sh https://raw.githubusercontent.com/visiblevc/wordpress-starter/master/scripts/install-plugins.sh \
        -o /scripts/remove-plugins.sh https://raw.githubusercontent.com/visiblevc/wordpress-starter/master/scripts/remove-plugins.sh \
        -o /run.sh https://raw.githubusercontent.com/visiblevc/wordpress-starter/master/scripts/run.sh \
    && chmod -R +x /usr/local/bin/wp /scripts /run.sh \
    && sed -i "s/AllowOverride None/AllowOverride All/g" /etc/apache2/apache2.conf \
    && a2enmod rewrite

# Run the server
EXPOSE 80 443
CMD ["/run.sh"]
