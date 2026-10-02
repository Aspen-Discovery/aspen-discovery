#!/bin/bash

apt-get install -y composer
runuser -u aspen -- /usr/bin/composer --version
cd /usr/local/aspen-discovery || exit
runuser -u aspen -- /usr/bin/composer install --no-interaction --prefer-dist --no-dev --no-cache
runuser -u aspen -- /usr/bin/composer check-platform-reqs
