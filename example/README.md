# Quickstart

Download this example to your current working directory

```sh
$ curl https://codeload.github.com/visiblevc/wordpress-starter/tar.gz/master | tar -xz --strip 1 wordpress-starter-master/example
```

The only thing you need to get started is a `docker-compose.yml` file:

```yml
version: '3'
services:
  wordpress:
    image: visiblevc/wordpress:latest
    ports:
      - 8080:80
      - 443:443
    volumes:
      - ./data:/data # Required if importing an existing database
      - ./tweaks.ini:/usr/local/etc/php/conf.d/tweaks.ini # Optional tweaks to the php.ini config
      - ./wp-content/uploads:/app/wp-content/uploads
      - ./yourplugin:/app/wp-content/plugins/yourplugin # Plugin development
      - ./yourtheme:/app/wp-content/themes/yourtheme   # Theme development
    environment:
      DB_HOST: db # must match db service name below
      DB_NAME: wordpress
      DB_PASS: root # must match below
      PLUGINS: >-
        academic-bloggers-toolkit,
        co-authors-plus,
        [WP-API]https://github.com/WP-API/WP-API/archive/master.zip,
      URL_REPLACE: localhost:8080
      WP_DEBUG: 'true'
  db:
    image: mysql:5.7 # or mariadb:10
    volumes:
      - data:/var/lib/mysql
    environment:
      MYSQL_ROOT_PASSWORD: root
volumes:
  data: {}
```

**Need PHPMyAdmin? Add it as a service**

```yml
version: '3'
services:
  wordpress:
    # same as above
  db:
    # same as above
  phpmyadmin:
    image: phpmyadmin/phpmyadmin
    environment:
      MYSQL_ROOT_PASSWORD: root
    ports:
      - 22222:80
volumes:
  data:
```

## Running the example

1.  Run the following command in the root of the example directory.

```sh
$ docker-compose up -d && docker-compose logs -f wordpress
```

2.  When the build is finished, hit <kbd>ctrl</kbd>-<kbd>c</kbd> to detach from the logs and visit `localhost:8080` in your browser.
