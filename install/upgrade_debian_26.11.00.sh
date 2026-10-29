#!/bin/bash

# Install Composer dependencies (async-aws/s3 for S3 storage) from the repo root, where composer.json now lives
apt-get install -y composer
cd /usr/local/aspen-discovery || exit
runuser -u www-data -- /usr/bin/composer install --no-interaction --prefer-dist --no-dev
runuser -u www-data -- /usr/bin/composer check-platform-reqs --no-dev
