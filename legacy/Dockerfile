FROM tutum/apache-php
MAINTAINER Karel Ledru-Mathe <karel@ledrumathe.com>

# Install mysql-client
RUN apt-get update \
    && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
        mysql-client \
        unzip \
    && rm -rf /var/lib/apt/lists/*

# Install wp-cli, configure Apache, & add scripts
WORKDIR /app
RUN curl \
        -o /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
        -o /run.sh https://raw.githubusercontent.com/visiblevc/wordpress-starter/master/run.sh \
    && chmod +x /usr/local/bin/wp /run.sh \
    && sed -i "s/AllowOverride None/AllowOverride All/g" /etc/apache2/apache2.conf \
    && a2enmod rewrite \
    && service apache2 restart

# Run the server
EXPOSE 80 443
CMD ["/run.sh"]
