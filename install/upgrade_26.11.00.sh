#!/bin/bash

/usr/local/aspen-discovery/install/install_composer.sh || exit 1
runuser -u apache -- /usr/local/bin/composer --version
cd /usr/local/aspen-discovery || exit
runuser -u apache -- /usr/local/bin/composer install --no-interaction --prefer-dist --no-dev --no-cache
runuser -u apache -- /usr/local/bin/composer check-platform-reqs
