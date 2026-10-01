#!/bin/bash

apt-get install -y composer
runuser -u www-data -- /usr/bin/composer --version
cd /usr/local/aspen-discovery || exit
runuser -u www-data -- /usr/bin/composer install --no-interaction --prefer-dist --no-dev
runuser -u www-data -- /usr/bin/composer check-platform-reqs
