#!/bin/bash

apt-get install -y composer
runuser -u www-data -- /usr/bin/composer --version
cd /usr/local/aspen-discovery/code/web || exit
runuser -u www-data -- /usr/bin/composer install --no-interaction --prefer-dist
runuser -u www-data -- /usr/bin/composer check-platform-reqs
