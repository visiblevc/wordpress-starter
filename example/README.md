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
        image: visiblevc/wordpress

        # required for mounting bindfs
        cap_add:
            - SYS_ADMIN
        devices:
            - /dev/fuse
        # required on certain cloud hosts
        security_opt:
            - apparmor:unconfined

        ports:
            - 8080:80
            - 443:443
        volumes:
            - ./data:/data
            - ./scripts:/docker-entrypoint-initwp.d
        environment:
            DB_NAME: wordpress
            DB_PASS: root
            PLUGINS: >-
                academic-bloggers-toolkit
                co-authors-plus
                [WP-API]https://github.com/WP-API/WP-API/archive/master.zip

    db:
        image: mariadb:10 # or mysql:5.7
        volumes:
            - data:/var/lib/mysql
        environment:
            MYSQL_ROOT_PASSWORD: root

    phpmyadmin:
        image: phpmyadmin/phpmyadmin
        ports:
            - 22222:80

volumes:
    data:
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
