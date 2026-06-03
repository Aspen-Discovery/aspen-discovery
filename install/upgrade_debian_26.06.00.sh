#!/bin/bash

apt-get install -y composer
composer --version
cd /usr/local/aspen-discovery/code/web || exit
runuser -uwww-data -- composer install --no-interaction --prefer-dist
runuser -uwww-data -- composer check-platform-reqs
