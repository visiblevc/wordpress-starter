ARG PHP_VERSION=7.2
FROM php:${PHP_VERSION}-apache
ARG VERSION=latest
LABEL maintainer="Derek P Sifford <dereksifford@gmail.com>" \
      version="${VERSION}-php${PHP_VERSION}"

# Install base requirements & sensible defaults + required PHP extensions
RUN apt-get update \
    && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
        bash-completion \
        less \
        libpng-dev \
        libjpeg-dev \
        libxml2-dev \
        mariadb-client \
        unzip \
        sudo \
        vim \
        zip \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd --with-png-dir=/usr --with-jpeg-dir=/usr \
    && docker-php-ext-install \
        exif \
        gd \
        mysqli \
        opcache \
        soap \
        zip \
    # See https://secure.php.net/manual/en/opcache.installation.php
    && echo 'memory_limit = 512M' > /usr/local/etc/php/php.ini \
    && { \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=8'; \
        echo 'opcache.max_accelerated_files=4000'; \
        echo 'opcache.revalidate_freq=2'; \
        echo 'opcache.fast_shutdown=1'; \
        echo 'opcache.enable_cli=1'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini \
    && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    # Fixes issue where error is logged stating apache could not resolve the
    # fully qualified domain name
    && echo 'ServerName localhost' > /etc/apache2/conf-available/fqdn.conf \
    # Grab and install wp-cli from remote
    && curl \
        -o /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
        -o /etc/bash_completion.d/wp-cli https://raw.githubusercontent.com/wp-cli/wp-cli/master/utils/wp-completion.bash \
    && a2enconf fqdn \
    && a2enmod rewrite expires \
    && service apache2 restart

# Add admin superuser, create install directory, adjust perms, & add symlink
COPY --chown=www-data:www-data run.sh /run.sh
RUN useradd -ms /bin/bash -G www-data,sudo admin \
    && echo "admin ALL=(root) NOPASSWD:ALL" > /etc/sudoers.d/admin \
    && chmod 0440 /etc/sudoers.d/admin \
    && chmod +x /usr/local/bin/wp /run.sh \
    && mkdir -p /app \
    && rm -fr /var/www/html \
    && chown -R www-data:www-data /app /var/www \
    && chmod g+rw /app /var/www \
    && ln -s /app /var/www/html

USER admin
WORKDIR /app
EXPOSE 80 443
CMD ["/run.sh"]
