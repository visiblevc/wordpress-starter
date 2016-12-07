FROM php:5.6-apache
MAINTAINER Derek P Sifford <dereksifford@gmail.com>
LABEL version 0.12.1-php5.6

# Install base requirements & sensible defaults + required PHP extensions
RUN apt-get update \
    && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
        less \
        libpng12-dev \
        libjpeg-dev \
        mariadb-client \
        unzip \
        sudo \
        vim \
        zip \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd --with-png-dir=/usr --with-jpeg-dir=/usr \
    && docker-php-ext-install gd mysqli opcache zip

# Set recommended PHP.ini settings (see https://secure.php.net/manual/en/opcache.installation.php)
RUN { \
  echo 'opcache.memory_consumption=128'; \
  echo 'opcache.interned_strings_buffer=8'; \
  echo 'opcache.max_accelerated_files=4000'; \
  echo 'opcache.revalidate_freq=2'; \
  echo 'opcache.fast_shutdown=1'; \
  echo 'opcache.enable_cli=1'; \
} > /usr/local/etc/php/conf.d/opcache-recommended.ini

# Install wp-cli, configure apache, & add scripts
RUN curl \
        -o /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
        -o /run.sh https://raw.githubusercontent.com/visiblevc/wordpress-starter/master/run.sh \
    && chmod +x /usr/local/bin/wp /run.sh \
    && sed -i "s/AllowOverride None/AllowOverride All/g" /etc/apache2/apache2.conf \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && a2enmod rewrite expires \
    && service apache2 restart

# Create install directory and symlink /var/www/html to it
RUN mkdir -p /app && rm -fr /var/www/html && ln -s /app /var/www/html
WORKDIR /app

# Run the server
EXPOSE 80 443
CMD ["/run.sh"]
