#!/usr/bin/env bash

npm_package_version="${npm_package_version?Script must be run using npm}"

docker login
[ $? -eq 1 ] && exit 0

# NOTE: Not building this stack of images concurrently due to a known issue
# with docker concurrent builds. https://github.com/moby/moby/issues/9656

docker build \
  -t "visiblevc/wordpress:latest" \
  -t "visiblevc/wordpress:latest-php7.1" \
  -t "visiblevc/wordpress:$npm_package_version-php7.1" \
./php7.1/

docker build \
  -t "visiblevc/wordpress:latest-php7.0" \
  -t "visiblevc/wordpress:$npm_package_version-php7.0" \
./php7.0/

docker build \
  -t "visiblevc/wordpress:latest-php5.6" \
  -t "visiblevc/wordpress:$npm_package_version-php5.6" \
./php5.6/

echo "

Successfully built images with the following tags:"

docker images visiblevc/wordpress --format "{{.Tag}}" | sort -r

docker push visiblevc/wordpress
