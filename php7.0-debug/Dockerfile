FROM php:7.0-apache

# Install base requirements & sensible defaults + required PHP extensions
RUN echo "deb http://ftp.debian.org/debian jessie-backports main" >> /etc/apt/sources.list \
    && apt-get update \
    && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
        less \
        libpng12-dev \
        libjpeg-dev \
        libxml2-dev \
        mariadb-client \
        unzip \
        sudo \
        vim \
        zip \
    && DEBIAN_FRONTEND=noninteractive apt-get -t jessie-backports install -y \
        python-certbot-apache \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd --with-png-dir=/usr --with-jpeg-dir=/usr \
    && docker-php-ext-install \
        exif \
        gd \
        mysqli \
        opcache \
        soap \
        zip

# Set recommended PHP.ini settings (see https://secure.php.net/manual/en/opcache.installation.php)
RUN echo "memory_limit = 512M" > /usr/local/etc/php/php.ini \
    && { \
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
        -o /composer-installer.php https://getcomposer.org/installer \
    && chmod +x /usr/local/bin/wp /run.sh \
    && php /composer-installer.php --install-dir=/usr/local/bin --filename=composer \
    && sed -i "s/AllowOverride None/AllowOverride All/g" /etc/apache2/apache2.conf \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && a2enmod rewrite expires \
    && service apache2 restart

# Install and enable xdebug
RUN pecl install xdebug-stable \
    && docker-php-ext-enable xdebug

# Configure xdebug
# remote host must be determined by using `ip ro get 8.8.8.8 | grep -oP '(?<=via )([\d\.]+)'`
RUN { \
        echo "xdebug.remote_host=172.22.0.1"; \
        echo "xdebug.remote_port=9000"; \
        echo "xdebug.default_enable=1"; \
        echo "xdebug.remote_enable=1"; \
        echo "xdebug.remote_connect_back=1"; \
        echo "xdebug.profiler_enable=0"; \
        echo "xdebug.profiler_enable_trigger=1"; \
        echo "xdebug.profiler_output_dir=\"/tmp\""; \
    } > /usr/local/etc/php/conf.d/xdebug.ini

# Create install directory and symlink /var/www/html to it
RUN mkdir -p /app && rm -fr /var/www/html && ln -s /app /var/www/html
WORKDIR /app

# Add Composer's vendor/bin/ to the PATH
ENV PATH="$PATH:/app/vendor/bin"

# Run the server
EXPOSE 80 443
CMD ["/run.sh"]
