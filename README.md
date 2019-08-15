# Visible Wordpress Starter

A Docker Wordpress development environment by the team at
[Visible](https://visible.vc/) and some awesome
[contributors](https://github.com/visiblevc/wordpress-starter/graphs/contributors).
Our goal is to make Wordpress development slightly less frustrating.

-   [Introduction](#introduction)
-   [Example](./example/)
-   [Requirements](#requirements)
-   [Getting Started](#getting-started)
-   [Available Images](#available-images)
-   [Default Wordpress Admin Credentials](#default-wordpress-admin-credentials)
-   [Default Database Credentials](#default-database-credentials)
-   [Service Environment Variables](#service-environment-variables)
    -   [`wordpress`](#wordpress)
    -   [`db`](#db)
-   [Workflow Tips](#workflow-tips)
    -   [Using `wp-cli`](#using-wp-cli)
    -   [Working with Databases](#working-with-databases)
-   [Using in Production](#using-in-production)
-   [Contributing](#contributing)

### Introduction

We wrote a series of articles explaining in depth the philosophy behind this
project:

-   [Intro: A slightly less shitty WordPress developer workflow](https://visible.vc/engineering/wordpress-developer-workflow/)
-   [Part 1: Setup a local development environment for WordPress with Docker](https://visible.vc/engineering/docker-environment-for-wordpress/)
-   [Part 2: Setup an asset pipeline for WordPress theme development](https://visible.vc/engineering/asset-pipeline-for-wordpress-theme-development/)
-   [Part 3: Optimize your wordpress theme assets and deploy to S3](https://visible.vc/engineering/optimize-wordpress-theme-assets-and-deploy-to-s3-cloudfront/)

### Requirements

Well, to run a Docker environment, you will need Docker. The Dockerfile is only
for an Apache+PHP+Wordpress container, you will need a `MySQL` or `MariaDB`
container to run a website. We use Docker Compose 1.6+ for the orchestration.

### Getting started

This project has 2 parts: the Docker environment and a set of tools for theme
development. To quickly get started, you can simply run the following:

```
# copy the files
git clone https://github.com/visiblevc/wordpress-starter.git

# navigate to example directory
cd wordpress-starter/example

# start the website at localhost:8080
docker-compose up -d && docker-compose logs -f wordpress
```

**NOTE:** If you run on MacOS with Docker in VirtualBox, you will want to
forward the port by running this
`VBoxManage controlvm vm-name natpf1 "tcp8080,tcp,127.0.0.1,8080,,8080"`. If you
use another port than `8080`, change it in the command.

### Available Images

| PHP Version | Tags                                        |
| ----------- | ------------------------------------------- |
| **7.3**     | `latest` `latest-php7.3` `<version>-php7.3` |
| **7.2**     | `latest-php7.2` `<version>-php7.2`          |
| **7.1**     | `latest-php7.1` `<version>-php7.1`          |
| **7.0**     | `latest-php7.0` `<version>-php7.0`          |
| **5.6**     | `latest-php5.6` `<version>-php5.6`          |

If you need a specific version, look at the [Changelog](CHANGELOG.md)

### Default Wordpress Admin Credentials

To access the Wordpress Admin at `/wp-admin`, the default values are as follows:

| Credential            | Value                           | Notes                                                      |
| --------------------- | ------------------------------- | ---------------------------------------------------------- |
| **Username or Email** | `root` or `admin@wordpress.com` | Can be changed with the `ADMIN_EMAIL` environment variable |
| **Password**          | `root`                          | Uses the same value as the `DB_PASS` environment variable  |

### Default Database Credentials

| Credential        | Value                  | Notes                                                                                              |
| ----------------- | ---------------------- | -------------------------------------------------------------------------------------------------- |
| **Hostname**      | `db`                   | Can be changed with the `DB_HOST` environment variable **NOTE:**: Must match database service name |
| **Username**      | `root`                 |                                                                                                    |
| **Password**      |                        | Must be set using the `DB_PASS` environment variable                                               |
| **Database Name** | `wordpress`            | Can be changed with the `DB_NAME` environment variable                                             |
| **Admin Email**   | `admin@${DB_NAME}.com` |                                                                                                    |

### Service Environment Variables

**Notes:**

-   Variables marked with ✅ are required
-   Single quotes must surround `boolean` environment variables

#### `wordpress`

| Variable           | Default Value                    | Description                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| ------------------ | -------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `DB_USER`          | `root`                           | Username for both the database and the WordPress installation (if not importing existing)                                                                                                                                                                                                                                                                                                                                                                                                                                               |
| `DB_PASS`✅        |                                  | Password for the database. Value must match `MYSQL_ROOT_PASSWORD` set in the `db` service                                                                                                                                                                                                                                                                                                                                                                                                                                               |
| `DB_HOST`          | `db`                             | Hostname for the database                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               |
| `DB_NAME`          | `wordpress`                      | Name of the database                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
| `DB_PREFIX`        | `wp_`                            | Prefix for the database                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
| `DB_CHARSET`       | `utf8`                           | Select a charset for the wordpress database (legacy versions might not be utf8)                                                                                                                                                                                                                                                                                                                                                                                                                                                         |
| `SERVER_NAME`      | `localhost`                      | Set this to `<your-domain-name>.<your-top-level-domain>` if you plan on obtaining SSL certificates                                                                                                                                                                                                                                                                                                                                                                                                                                      |
| `ADMIN_EMAIL`      | `admin@${DB_NAME}.com`           | Administrator email address                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| `WP_LOCALE`        | `en_US`                          | Set the site language                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   |
| `WP_DEBUG`         | `'false'`                        | [Click here](https://codex.wordpress.org/WP_DEBUG) for more information                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
| `WP_DEBUG_DISPLAY` | `'false'`                        | [Click here](https://codex.wordpress.org/WP_DEBUG#WP_DEBUG_DISPLAY) for more information                                                                                                                                                                                                                                                                                                                                                                                                                                                |
| `WP_DEBUG_LOG`     | `'false'`                        | [Click here](https://codex.wordpress.org/WP_DEBUG#WP_DEBUG_LOG) for more information                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
| `WP_VERSION`       | `latest`                         | Specify the WordPress version to install. Accepts any valid semver number, `latest`, or `nightly` for beta builds.                                                                                                                                                                                                                                                                                                                                                                                                                      |
| `THEMES`           |                                  | Space-separated list of themes you want to install in either of the following forms<ul><li>`theme-slug`: Used when installing theme direct from WordPress.org</li><li>`[theme-slug]https://themesite.com/theme.zip`: Used when installing theme from URL</li></ul>                                                                                                                                                                                                                                                                      |
| `PLUGINS`          |                                  | Space-separated list of plugins you want to install in either of the following forms:<ul><li>`plugin-slug`: Used when installing plugin direct from WordPress.org.</li><li>`[plugin-slug]http://pluginsite.com/plugin.zip`: Used when installing plugin from URL.</li></ul>                                                                                                                                                                                                                                                             |
| `MULTISITE`        | `'false'`                        | Set to `'true'` to enable multisite                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| `PERMALINKS`       | `/%year%/%monthnum%/%postname%/` | A valid WordPress permalink [structure tag](https://codex.wordpress.org/Using_Permalinks#Structure_Tags)                                                                                                                                                                                                                                                                                                                                                                                                                                |
| `URL_REPLACE`      |                                  | <p>Value must be a full replacement URL to use in development environments if importing a database for development use.</p><p><strong>Example:</strong> If your live site's URL is `https://www.example.com` and you intend to use the database on port 8080 of localhost, this value should be set to `http://localhost:8080`.</p><p><strong>Note:</strong> If you are running Docker using Docker Machine, your replacement url MUST be the output of the following command: `echo $(docker-machine ip <your-machine-name>):8080`</p> |
| `EXTRA_PHP`        |                                  | <p>Extra PHP code to add to `wp-config.php`. [Click here](https://developer.wordpress.org/cli/commands/config/create/) for more information.</p><p><strong>IMPORTANT NOTE:</strong> All `$` symbols must be escaped by prepending an extra `$`, otherwise it will be interpreted by docker as an environment variable. In other words, `$variable` must be `$$variable`.</p>                                                                                                                                                                                                                       |

#### `db`

| Variable                | Default Value | Description                                     |
| ----------------------- | ------------- | ----------------------------------------------- |
| `MYSQL_ROOT_PASSWORD`✅ |               | Must match `DB_PASS` of the `wordpress` service |

## Workflow Tips

### Using `wp-cli`

You can access wp-cli by running `npm run wp ...`. Here are some examples:

```
npm run wp plugin install <some-plugin>
npm run wp db import /data/database.sql
```

### Working with Databases

If you have an exported `.sql` file from an existing website, drop the file into
the `data/` folder. The first time you run the container, it will detect the SQL
dump and use it as a database. If it doesn't find one, it will create a fresh
database.

If the SQL dump changes for some reason, you can reload the database by running:

```sh
docker-compose exec wordpress /bin/bash "wp db import $(find /data/*.sql | head -n 1) --allow-root"
```

If you want to create a dump of your development database, you can run:

```sh
docker-compose exec wordpress /bin/bash -c 'wp db export /data/dump.sql --allow-root'
```

Finally, sometimes your development environment runs on a different domain than
your live one. The live will be `example.com` and the development
`localhost:8080`. This project does a search and replace for you. You can set
the `URL_REPLACE: localhost:8080` environment variable in the
`docker-compose.yml`.

## Using in Production

### Adjustments to `docker-compose.yml`

```yml
# If something isn't shown, assume it's the same as the examples above
version: "3"
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

We highly recommend securing your site with SSL encryption. The Let's Encrypt
and Certbot projects have made doing this both free (as in beer) and painless.
We've incorporated these projects into this project.

Assuming your site is running on your production host, follow the below steps to
obtain and renew SSL certificates.

#### Obtaining Certificates

You should first [set `SERVER_NAME` to `<your-domain-name>.<your-top-level-domain>` in your `docker-compose.yml`](#wordpress)

```sh
$ docker-compose ps
Name                   Command                        State
---------------------------------------------------------
project_db_1           docker-entrypoint.sh mysqld     Up
project_wordpress_1    docker-php-entrypoint /run.sh   Up

$ docker-compose exec wordpress /bin/bash
root@4e16c7fe4a10:/app# certbot --apache
```

#### Renewing Certificates

```sh
$ docker-compose ps
Name                   Command                        State
---------------------------------------------------------
project_db_1           docker-entrypoint.sh mysqld     Up
project_wordpress_1    docker-php-entrypoint /run.sh   Up

$ docker-compose exec wordpress /bin/bash
root@4e16c7fe4a10:/app# certbot renew
```

## Contributing

You can find Development instructions in the
[Wiki](https://github.com/visiblevc/wordpress-starter/wiki/Development).
