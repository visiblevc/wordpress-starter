# Visible Wordpress Starter

A project to make Wordpress development slightly less frustrating created by the team at [Visible](https://visible.vc/).

## Requirements

- Docker
- Docker Compose 1.6+

## Getting started

```
# close this project
git clone https://github.com/visiblevc/wordpress-starter.git
# remove the .git folder and Dockerfile (we use the docker hub image)
rm -rf .git Dockerfile
# start the containers
docker-compose up
# visit localhost:8080
```

## Documentation

We wrote a series of articles to document the project:

- [Intro: A slightly less shitty WordPress developer workflow](https://visible.vc/engineering/wordpress-developer-workflow/)
- [Part 1: Setup a local development environment for WordPress with Docker](https://visible.vc/engineering/docker-environment-for-wordpress/)
- [Part 2: Setup an asset pipeline for WordPress theme development](https://visible.vc/engineering/asset-pipeline-for-wordpress-theme-development/)
- [Part 3: Optimize your wordpress theme assets and deploy to S3](https://visible.vc/engineering/optimize-wordpress-theme-assets-and-deploy-to-s3-cloudfront/)
- Part 4: Auto deploy your site on your server (coming)

## Development

You can find Development instructions in the [Wiki](https://github.com/visiblevc/wordpress-starter/wiki/Development).
