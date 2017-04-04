# Visible Wordpress Starter

A Docker Wordpress development environment by the team at [Visible](https://visible.vc/) and some awesome [contributors](https://github.com/visiblevc/wordpress-starter/graphs/contributors). Our goal is to make Wordpress development slightly less frustrating.

- [Requirements](#requirements)
- [Getting Started](#getting-started)
- [Available Images](#available-images)
- [Introduction](#introduction)
- [Example](#example)
- [Default Database Credentials](#default-database-credentials)
- [Service Environment Variables](#service-environment-variables)
  - [`wordpress`](#wordpress)
  - [`db`](#db)
- [Workflow Tips](#workflow-tips)
  - [Using `wp-cli`](#using-wp-cli)
  - [Working with Databases](#working-with-databases)
- [Using in Production](#using-in-production)
- [Contributing](#contributing)

### Requirements

Well, to run a Docker environment, you will need Docker. The Dockerfile is only for an Apache+PHP+Wordpress container, you will need a `MySQL` or `MariaDB` container to run a website. We use Docker Compose 1.6+ for the orchestration.

### Getting started

This project has 2 parts: the Docker environment and a set of tools for theme development. To quickly get started, you can simply run the following:

```
# copy the files
git clone https://github.com/visiblevc/wordpress-starter.git
rm -rf .git Dockerfile run.sh README.md CHANGELOG.md ISSUE_TEMPLATE.md

# start the website at localhost:8080
docker-compose up
```

**NOTE:** If you run on MacOS with Docker in VirtualBox, you will want to forward the port by running this `VBoxManage controlvm vm-name natpf1 "tcp8080,tcp,127.0.0.1,8080,,8080"`. If you use another port than `8080`, change it in the command.

##### This repository does 2 things:

1. Include the files to create a wordpress Docker image (visiblevc/wordpress)
2. Include build tools to develop wordpress themes (gulp)

If you don't plan to build the Docker image yourself, you shouldn't care for 1. We publish the image on Docker Hub and you can grab it directly from there. That's why you can safely remove the Dockerfile and run.sh.

The reason we remove `.git`, `README.md` and `CHANGELOG.md` is because we assume you will start your own repository, named after your project. There is virtually no benefit keeping ties with our remote git repository.

### Available Images

| PHP Version | Tags |
| ----------- | ---- |
| **7.0**     | `latest` `latest-php7.0` `<version>-php7.0` |
| **5.6**     | `latest-php5.6` `<version>-php5.6`|

If you need a specific version, look at the [Changelog](CHANGELOG.md)

### Introduction

We wrote a series of articles explaining in depth the philosophy behind this project:

- [Intro: A slightly less shitty WordPress developer workflow](https://visible.vc/engineering/wordpress-developer-workflow/)
- [Part 1: Setup a local development environment for WordPress with Docker](https://visible.vc/engineering/docker-environment-for-wordpress/)
- [Part 2: Setup an asset pipeline for WordPress theme development](https://visible.vc/engineering/asset-pipeline-for-wordpress-theme-development/)
- [Part 3: Optimize your wordpress theme assets and deploy to S3](https://visible.vc/engineering/optimize-wordpress-theme-assets-and-deploy-to-s3-cloudfront/)
- Part 4: Auto deploy your site on your server (coming)

### Example

The only thing you need to get started is a `docker-compose.yml` file:

```yml
version: '2'
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
        [local]my-local-plugin
      THEMES: >-
        [local]my-local-theme
      SEARCH_REPLACE: yoursite.com,localhost:8080
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
version: '2'
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

### Default Database Credentials

Credential | Value | Notes
--- | --- | ---
**Hostname** | `db` | Can be changed with the `DB_HOST` environment variable  **NOTE:**: Must match database service name
**Username** | `root` | |
**Password** |  | Must be set using the `DB_PASS` environment variable
**Database Name** | `wordpress` | Can be changed with the `DB_NAME` environment variable
**Admin Email** | `admin@${DB_NAME}.com` | |

### Service Environment Variables
**Notes:**
- Variables marked with ✅ are required
- Single quotes must surround `boolean` environment variables

#### `wordpress`

Variable | Default Value | Description
---|---|---
`DB_PASS`✅ | | Password for the database. Value must match `MYSQL_ROOT_PASSWORD` set in the `db` service
`DB_HOST` | `db` | Hostname for the database
`DB_NAME` | `wordpress` | Name of the database
`DB_PREFIX` | `wp_` | Prefix for the database
`SERVER_NAME` | `localhost` | Set this to `<your-domain-name>.<your-domain-extension>` if you plan on obtaining SSL certificates
`ADMIN_EMAIL` | `admin@${DB_NAME}.com` | Administrator email address
`WP_DEBUG` | `'false'` | [Click here](https://codex.wordpress.org/WP_DEBUG) for more information
`WP_DEBUG_DISPLAY` | `'false'` | [Click here](https://codex.wordpress.org/WP_DEBUG#WP_DEBUG_DISPLAY) for more information
`WP_DEBUG_LOG` | `'false'` | [Click here](https://codex.wordpress.org/WP_DEBUG#WP_DEBUG_LOG) for more information
`WP_VERSION` | `latest` | Specify the WordPress version to install. Accepts any valid semver number, `latest`, or `nightly` for beta builds.
`THEMES` | | Comma-separated list of themes you want to install in either of the following forms<ul><li>`theme-slug`: Used when installing theme direct from WordPress.org</li><li>`[theme-slug]https://themesite.com/theme.zip`: Used when installing theme from URL</li><li>`[local]theme-slug`: Used when you have the theme downloaded to a local folder that you have volumed to the `./wp-content/themes` directory.</li></ul>
`PLUGINS` | | Comma-separated list of plugins you want to install in either of the following forms:<ul><li>`plugin-slug`: Used when installing plugin direct from WordPress.org.</li><li>`[plugin-slug]http://pluginsite.com/plugin.zip`: Used when installing plugin from URL.</li><li>`[local]plugin-slug`: Used when you have the plugin downloaded to a local folder that you have volumed to the `./wp-content/plugins` directory.</li></ul>
`MULTISITE` | `'false'` | Set to `'true'` to enable multisite
`PERMALINKS` | `/%year%/%monthnum%/%postname%/` | A valid WordPress permalink [structure tag](https://codex.wordpress.org/Using_Permalinks#Structure_Tags)
`SEARCH_REPLACE` | | Comma-separated string in the form of `current-url,replacement-url`<ul><li>When defined, `current-url` will be replaced with `replacement-url` on build (useful for development environments utilizing a database copied from a live site)<li>**Note:** If you are running Docker using Docker Machine, your replacement url MUST be the output of the following command: `echo $(docker-machine ip <your-machine-name>):8080`</li></ul>
`VERBOSE` | `'false'` | Set to `'true'` to run build with verbose logging

#### `db`
Variable | Default Value | Description
---|---|---
`MYSQL_ROOT_PASSWORD`✅ | | Must match `DB_PASS` of the `wordpress` service

## Workflow Tips

### Using `wp-cli`

You can access wp-cli by running `npm run wp ...`. Here are some examples:

```
npm run wp plugin install <some-plugin>
npm run wp db import /data/database.sql
```

### Working with Databases

If you have an exported `.sql` file from an existing website, drop the file into the `data/` folder. The first time you run the container, it will detect the SQL dump and use it as a database. If it doesn't find one, it will create a fresh database.

If the SQL dump changes for some reason, you can reload the database by running:

```sh
docker exec wordpress /bin/bash "wp db import $(find /data/*.sql | head -n 1) --allow-root"
```

If you want to create a dump of your development database, you can run:

```sh
npm run wp db export /data --allow-root
```

Finally, sometimes your development environment runs on a different domain than your live one. The live will be `example.com` and the development `localhost:8080`. This project does a search and replace for you. You can set the `SEARCH_REPLACE: example.com,localhost:8080` environment variable in the `docker-compose.yml`.

## Using in Production

### Adjustments to `docker-compose.yml`

```yml
# If something isn't shown, assume it's the same as the examples above
version: '2'
services:
  wordpress:
    ports:
      - 80:80
      - 443:443
    restart: always
    environment:
      SERVER_NAME: mysite.com
      DB_PASS: ${SECURE_PASSWORD} # Stored in .env file
    volumes:
      - ./letsencrypt:/etc/letsencrypt
      - ./data:/data
      # anything else you'd like to be able to back up
  db:
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: ${SECURE_PASSWORD} # Stored in .env file
```

### SSL Certificates

We highly recommend securing your site with SSL encryption. The Let's Encrypt and Certbot projects have made doing this both free (as in beer) and painless. We've incorporated these projects into this project.

Assuming your site is running on your production host, follow the below steps to obtain and renew SSL certificates.

#### Obtaining Certificates

```sh
$ docker-compose ps
Name                   Command                        State
---------------------------------------------------------
project_db_1           docker-entrypoint.sh mysqld     Up
project_wordpress_1    docker-php-entrypoint /run.sh   Up

$ docker exec -it project_wordpress_1  /bin/bash
root@4e16c7fe4a10:/app# certbot --apache
```

#### Renewing Certificates

```sh
$ docker-compose ps
Name                   Command                        State
---------------------------------------------------------
project_db_1           docker-entrypoint.sh mysqld     Up
project_wordpress_1    docker-php-entrypoint /run.sh   Up

$ docker exec -it project_wordpress_1  /bin/bash
root@4e16c7fe4a10:/app# certbot renew
```

## Contributing

You can find Development instructions in the [Wiki](https://github.com/visiblevc/wordpress-starter/wiki/Development).
