PLEASE READ THIS BEFORE POSTING AN ISSUE!

This is a template to make sure you provide us with all the information we will be to help you out. Please take the time to fill detailed issues, it will be faster for us to provide solutions! In the following sections, you should replace the content with your own. The content that is there is just presented as an example.

## Overview

- What's your problem about?
- What is your operating system?
- What is your docker version? `docker version`

## `docker-compose.yml`

```yml
version: '2'
services:
  wordpress:
# Replace this with your own docker-compose.yml content
```

## Project structure

```
/data
  /database.sql
/wp-content
  /plugins
    /my-plugin
  /themes
    /my-theme
```

## `docker-compose up` output

```
wordpress_1  | ===============================================================================
wordpress_1  |                          Begin WordPress Installation
wordpress_1  | ===============================================================================
wordpress_1  | ==> Installing wordpress
wordpress_1  |   -> Downloading... ✘
wordpress_1  | ==> Waiting for MySQL to initialize...
wordpress_1  |   ->  mysqld is alive
wordpress_1  | ==> Configuring wordpress
wordpress_1  |   -> Generating wp-config.php file... ✘
wordpress_1  | ==> Checking database
wordpress_1  |   -> Creating database wordpress ✘
wordpress_1  |   -> Loading data backup from /data/database.sql ✘
wordpress_1  | ==> Checking for multisite
wordpress_1  |   -> Multisite not found. SKIPPING...
```
